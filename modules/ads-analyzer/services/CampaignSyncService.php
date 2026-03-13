<?php

namespace Modules\AdsAnalyzer\Services;

use Services\GoogleAdsService;
use Modules\AdsAnalyzer\Models\Sync;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\CampaignAdGroup;
use Modules\AdsAnalyzer\Models\AdGroupKeyword;
use Modules\AdsAnalyzer\Models\Ad;
use Modules\AdsAnalyzer\Models\Extension;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\AssetGroup;
use Modules\AdsAnalyzer\Models\AssetGroupAsset;
use Modules\AdsAnalyzer\Models\Project;
use Core\Database;
use Core\Logger;

/**
 * CampaignSyncService - Orchestratore sincronizzazione dati Google Ads
 *
 * Sincronizza campagne, gruppi annunci, keyword, annunci, estensioni e termini
 * di ricerca dall'API Google Ads REST v18 verso le tabelle ga_* locali.
 *
 * Flusso: syncAll() crea un record ga_syncs, esegue ogni sub-sync in ordine,
 * aggiorna i conteggi e imposta lo stato finale.
 */
class CampaignSyncService
{
    private GoogleAdsService $gadsService;
    private int $projectId;
    private int $syncId = 0;

    public function __construct(GoogleAdsService $gadsService, int $projectId)
    {
        $this->gadsService = $gadsService;
        $this->projectId = $projectId;
    }

    /**
     * Full sync: campaigns -> ad groups -> keywords -> ads -> extensions -> search terms
     *
     * Crea un record ga_syncs,à con le view esistenti),
     * esegue tutti i sub-sync in ordine, aggiorna conteggi e stato.
     *
     * @param string $dateFrom Data inizio (YYYY-MM-DD)
     * @param string $dateTo Data fine (YYYY-MM-DD)
     * @param int $userId ID utente che ha avviato il sync
     * @param string $syncType Tipo sync: 'manual' o 'scheduled'
     * @return array Riepilogo con conteggi per entità
     */
    public function syncAll(string $dateFrom, string $dateTo, int $userId, string $syncType = 'manual'): array
    {
        // 1. Crea record sync
        $this->syncId = Sync::create([
            'project_id' => $this->projectId,
            'user_id' => $userId,
            'sync_type' => $syncType,
            'status' => 'running',
            'date_range_start' => $dateFrom,
            'date_range_end' => $dateTo,
        ]);

        $counts = [
            'campaigns_synced' => 0,
            'ad_groups_synced' => 0,
            'keywords_synced' => 0,
            'ads_synced' => 0,
            'extensions_synced' => 0,
            'asset_groups_synced' => 0,
            'assets_synced' => 0,
            'search_terms_synced' => 0,
        ];

        try {
            // 2. Sync campagne
            $counts['campaigns_synced'] = $this->syncCampaigns($dateFrom, $dateTo);
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 3. Sync gruppi annunci
            $counts['ad_groups_synced'] = $this->syncAdGroups($dateFrom, $dateTo);
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 4. Sync keyword
            $counts['keywords_synced'] = $this->syncKeywords($dateFrom, $dateTo);
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 5. Sync annunci
            $counts['ads_synced'] = $this->syncAds($dateFrom, $dateTo);
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 6. Sync estensioni
            $counts['extensions_synced'] = $this->syncExtensions();
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 6b. Sync PMax asset groups + assets + audience signals
            $pmaxCounts = $this->syncPmaxData($dateFrom, $dateTo);
            $counts['asset_groups_synced'] = $pmaxCounts['asset_groups'];
            $counts['assets_synced'] = $pmaxCounts['assets'];
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 7. Sync termini di ricerca
            $counts['search_terms_synced'] = $this->syncSearchTerms($dateFrom, $dateTo);
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();

            // 8. Successo: aggiorna stato sync e script run
            Sync::updateStatus($this->syncId, 'completed');

            // 9. Aggiorna ga_projects.last_sync_at
            Project::update($this->projectId, [
                'last_sync_at' => date('Y-m-d H:i:s'),
            ]);

            Database::reconnect();

            Logger::channel('ads')->info('CampaignSync completed', [
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'counts' => $counts,
            ]);

            return [
                'success' => true,
                'sync_id' => $this->syncId,
                'counts' => $counts,
            ];

        } catch (\Exception $e) {
            Database::reconnect();

            Sync::updateStatus($this->syncId, 'error', $e->getMessage());

            Logger::channel('ads')->error('CampaignSync failed', [
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'error' => $e->getMessage(),
                'counts_so_far' => $counts,
            ]);

            return [
                'success' => false,
                'sync_id' => $this->syncId,
                'error' => $e->getMessage(),
                'counts' => $counts,
            ];
        }
    }

    /**
     * Sincronizza le campagne dall'API Google Ads
     *
     * @return int Numero di campagne sincronizzate
     */
    public function syncCampaigns(string $dateFrom, string $dateTo): int
    {
        $gaql = "SELECT campaign.id, campaign.name, campaign.status, " .
                "campaign.advertising_channel_type, campaign_budget.amount_micros, " .
                "campaign.bidding_strategy_type, " .
                "metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.average_cpc, metrics.cost_micros, " .
                "metrics.conversions, metrics.conversions_value " .
                "FROM campaign " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggrega per campaign.id (le righe sono per-giorno con segments.date)
        $aggregated = [];
        foreach ($rows as $row) {
            $campaign = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $budget = $row['campaignBudget'] ?? [];

            $campaignId = (string) ($campaign['id'] ?? '');
            if (empty($campaignId)) {
                continue;
            }

            if (!isset($aggregated[$campaignId])) {
                $aggregated[$campaignId] = [
                    'campaign_id' => $campaignId,
                    'name' => $campaign['name'] ?? '',
                    'status' => $campaign['status'] ?? null,
                    'type' => $campaign['advertisingChannelType'] ?? null,
                    'bidding_strategy' => $campaign['biddingStrategyType'] ?? null,
                    'budget_micros' => (int) ($budget['amountMicros'] ?? 0),
                    'clicks' => 0,
                    'impressions' => 0,
                    'cost_micros' => 0,
                    'conversions' => 0.0,
                    'conversions_value' => 0.0,
                    'ctr_sum' => 0.0,
                    'cpc_sum' => 0.0,
                    'row_count' => 0,
                ];
            }

            $agg = &$aggregated[$campaignId];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            $agg['conversions'] += (float) ($metrics['conversions'] ?? 0);
            $agg['conversions_value'] += (float) ($metrics['conversionsValue'] ?? 0);
            $agg['ctr_sum'] += (float) ($metrics['ctr'] ?? 0);
            $agg['cpc_sum'] += (int) ($metrics['averageCpc'] ?? 0);
            $agg['row_count']++;
            unset($agg);
        }

        $count = 0;
        foreach ($aggregated as $agg) {
            $impressions = $agg['impressions'];
            $clicks = $agg['clicks'];
            $cost = $agg['cost_micros'] / 1000000;
            $ctr = $this->calcCtr($clicks, $impressions);
            $avgCpc = $clicks > 0 ? $cost / $clicks : 0;
            $convRate = $clicks > 0 ? min(($agg['conversions'] / $clicks) * 100, 100.0) : 0;

            Campaign::create([
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'campaign_id_google' => $agg['campaign_id'],
                'campaign_name' => $agg['name'],
                'campaign_status' => $agg['status'],
                'campaign_type' => $agg['type'],
                'bidding_strategy' => $agg['bidding_strategy'],
                'budget_amount' => $agg['budget_micros'] / 1000000,
                'budget_type' => 'DAILY',
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'avg_cpc' => round($avgCpc, 2),
                'cost' => round($cost, 2),
                'conversions' => round($agg['conversions'], 2),
                'conversion_value' => round($agg['conversions_value'], 2),
                'conv_rate' => round($convRate, 2),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Sincronizza i gruppi di annunci dall'API Google Ads
     *
     * @return int Numero di ad group sincronizzati
     */
    public function syncAdGroups(string $dateFrom, string $dateTo): int
    {
        $gaql = "SELECT ad_group.id, ad_group.name, ad_group.status, " .
                "ad_group.type, campaign.id, campaign.name, campaign.status, " .
                "metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.average_cpc, metrics.cost_micros, " .
                "metrics.conversions " .
                "FROM ad_group " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED' " .
                "AND ad_group.status != 'REMOVED'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggrega per ad_group.id
        $aggregated = [];
        foreach ($rows as $row) {
            $adGroup = $row['adGroup'] ?? [];
            $campaign = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $adGroupId = (string) ($adGroup['id'] ?? '');
            if (empty($adGroupId)) {
                continue;
            }

            if (!isset($aggregated[$adGroupId])) {
                $aggregated[$adGroupId] = [
                    'ad_group_id' => $adGroupId,
                    'name' => $adGroup['name'] ?? '',
                    'status' => $adGroup['status'] ?? null,
                    'type' => $adGroup['type'] ?? null,
                    'campaign_id' => (string) ($campaign['id'] ?? ''),
                    'campaign_name' => $campaign['name'] ?? '',
                    'clicks' => 0,
                    'impressions' => 0,
                    'cost_micros' => 0,
                    'conversions' => 0.0,
                    'conversion_value' => 0.0,
                ];
            }

            $agg = &$aggregated[$adGroupId];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            $agg['conversions'] += (float) ($metrics['conversions'] ?? 0);
            unset($agg);
        }

        $count = 0;
        foreach ($aggregated as $agg) {
            $impressions = $agg['impressions'];
            $clicks = $agg['clicks'];
            $cost = $agg['cost_micros'] / 1000000;
            $ctr = $this->calcCtr($clicks, $impressions);
            $avgCpc = $clicks > 0 ? $cost / $clicks : 0;
            $convRate = $clicks > 0 ? min(($agg['conversions'] / $clicks) * 100, 100.0) : 0;

            CampaignAdGroup::create([
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'campaign_id_google' => $agg['campaign_id'],
                'campaign_name' => $agg['campaign_name'],
                'campaign_type' => $agg['type'],
                'ad_group_id_google' => $agg['ad_group_id'],
                'ad_group_name' => $agg['name'],
                'ad_group_status' => $agg['status'],
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'avg_cpc' => round($avgCpc, 2),
                'cost' => round($cost, 2),
                'conversions' => round($agg['conversions'], 2),
                'conversion_value' => round($agg['conversion_value'] ?? 0, 2),
                'conv_rate' => round($convRate, 2),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Sincronizza le keyword dei gruppi di annunci dall'API Google Ads
     *
     * @return int Numero di keyword sincronizzate
     */
    public function syncKeywords(string $dateFrom, string $dateTo): int
    {
        $gaql = "SELECT ad_group_criterion.criterion_id, ad_group_criterion.keyword.text, " .
                "ad_group_criterion.keyword.match_type, ad_group_criterion.status, " .
                "ad_group.id, ad_group.name, campaign.id, campaign.status, " .
                "metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.average_cpc, metrics.cost_micros " .
                "FROM keyword_view " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED' " .
                "AND ad_group.status != 'REMOVED'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggrega per criterion_id (righe giornaliere)
        $aggregated = [];
        foreach ($rows as $row) {
            $criterion = $row['adGroupCriterion'] ?? [];
            $keyword = $criterion['keyword'] ?? [];
            $adGroup = $row['adGroup'] ?? [];
            $campaign = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $criterionId = (string) ($criterion['criterionId'] ?? '');
            if (empty($criterionId)) {
                continue;
            }

            $key = $criterionId . '_' . ($adGroup['id'] ?? '');

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'keyword_text' => $keyword['text'] ?? '',
                    'match_type' => $keyword['matchType'] ?? null,
                    'status' => $criterion['status'] ?? null,
                    'ad_group_id' => (string) ($adGroup['id'] ?? ''),
                    'ad_group_name' => $adGroup['name'] ?? '',
                    'campaign_id' => (string) ($campaign['id'] ?? ''),
                    'clicks' => 0,
                    'impressions' => 0,
                    'cost_micros' => 0,
                ];
            }

            $agg = &$aggregated[$key];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            unset($agg);
        }

        // Recupera nomi campagne da campagne già sincronizzate
        $campaignNames = $this->getCampaignNamesMap();

        $count = 0;
        foreach ($aggregated as $agg) {
            $impressions = $agg['impressions'];
            $clicks = $agg['clicks'];
            $cost = $agg['cost_micros'] / 1000000;
            $ctr = $this->calcCtr($clicks, $impressions);
            $avgCpc = $clicks > 0 ? $cost / $clicks : 0;

            AdGroupKeyword::create([
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'campaign_id_google' => $agg['campaign_id'],
                'campaign_name' => $campaignNames[$agg['campaign_id']] ?? null,
                'ad_group_id_google' => $agg['ad_group_id'],
                'ad_group_name' => $agg['ad_group_name'],
                'keyword_text' => $agg['keyword_text'],
                'match_type' => $agg['match_type'],
                'keyword_status' => $agg['status'],
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'avg_cpc' => round($avgCpc, 2),
                'cost' => round($cost, 2),
                'conversions' => 0,
                'quality_score' => null,
                'first_page_cpc' => null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Sincronizza gli annunci dall'API Google Ads
     *
     * @return int Numero di annunci sincronizzati
     */
    public function syncAds(string $dateFrom, string $dateTo): int
    {
        $gaql = "SELECT ad_group_ad.ad.id, ad_group_ad.ad.type, " .
                "ad_group_ad.ad.responsive_search_ad.headlines, " .
                "ad_group_ad.ad.responsive_search_ad.descriptions, " .
                "ad_group_ad.ad.final_urls, ad_group_ad.status, " .
                "ad_group.id, campaign.id, campaign.status, " .
                "metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.average_cpc, metrics.cost_micros, " .
                "metrics.conversions " .
                "FROM ad_group_ad " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED' " .
                "AND ad_group.status != 'REMOVED'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggrega per ad.id + ad_group.id (righe giornaliere)
        $aggregated = [];
        foreach ($rows as $row) {
            $adGroupAd = $row['adGroupAd'] ?? [];
            $ad = $adGroupAd['ad'] ?? [];
            $adGroup = $row['adGroup'] ?? [];
            $campaign = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $adId = (string) ($ad['id'] ?? '');
            if (empty($adId)) {
                continue;
            }

            $key = $adId . '_' . ($adGroup['id'] ?? '');

            if (!isset($aggregated[$key])) {
                // Estrai headline e description da responsive_search_ad
                $rsa = $ad['responsiveSearchAd'] ?? [];
                $headlines = $this->extractAdTexts($rsa['headlines'] ?? []);
                $descriptions = $this->extractAdTexts($rsa['descriptions'] ?? []);

                // final_urls è un array
                $finalUrls = $ad['finalUrls'] ?? [];
                $finalUrl = !empty($finalUrls) ? $finalUrls[0] : null;

                $aggregated[$key] = [
                    'ad_id' => $adId,
                    'ad_type' => $ad['type'] ?? null,
                    'headline1' => $headlines[0] ?? null,
                    'headline2' => $headlines[1] ?? null,
                    'headline3' => $headlines[2] ?? null,
                    'description1' => $descriptions[0] ?? null,
                    'description2' => $descriptions[1] ?? null,
                    'final_url' => $finalUrl,
                    'status' => $adGroupAd['status'] ?? null,
                    'ad_group_id' => (string) ($adGroup['id'] ?? ''),
                    'campaign_id' => (string) ($campaign['id'] ?? ''),
                    'clicks' => 0,
                    'impressions' => 0,
                    'cost_micros' => 0,
                    'conversions' => 0.0,
                ];
            }

            $agg = &$aggregated[$key];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            $agg['conversions'] += (float) ($metrics['conversions'] ?? 0);
            unset($agg);
        }

        // Recupera nomi campagne e ad group da dati già sincronizzati
        $campaignNames = $this->getCampaignNamesMap();
        $adGroupNames = $this->getAdGroupNamesMap();

        $count = 0;
        foreach ($aggregated as $agg) {
            $impressions = $agg['impressions'];
            $clicks = $agg['clicks'];
            $cost = $agg['cost_micros'] / 1000000;
            $ctr = $this->calcCtr($clicks, $impressions);
            $avgCpc = $clicks > 0 ? $cost / $clicks : 0;

            Ad::create([
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'campaign_id_google' => $agg['campaign_id'],
                'campaign_name' => $campaignNames[$agg['campaign_id']] ?? null,
                'ad_group_id_google' => $agg['ad_group_id'],
                'ad_group_name' => $adGroupNames[$agg['ad_group_id']] ?? null,
                'ad_type' => $agg['ad_type'],
                'headline1' => $agg['headline1'],
                'headline2' => $agg['headline2'],
                'headline3' => $agg['headline3'],
                'description1' => $agg['description1'],
                'description2' => $agg['description2'],
                'final_url' => $agg['final_url'],
                'path1' => null,
                'path2' => null,
                'ad_status' => $agg['status'],
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'avg_cpc' => round($avgCpc, 2),
                'cost' => round($cost, 2),
                'conversions' => round($agg['conversions'], 2),
                'quality_score' => null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Sincronizza le estensioni (asset campagna) dall'API Google Ads
     *
     * @return int Numero di estensioni sincronizzate
     */
    public function syncExtensions(): int
    {
        $gaql = "SELECT asset.id, asset.name, asset.type, asset.resource_name, " .
                "asset.callout_asset.callout_text, " .
                "asset.sitelink_asset.link_text, asset.sitelink_asset.description1, asset.sitelink_asset.description2, " .
                "asset.structured_snippet_asset.header, asset.structured_snippet_asset.values, " .
                "asset.image_asset.file_size, " .
                "campaign_asset.status, campaign.id, campaign.status " .
                "FROM campaign_asset " .
                "WHERE campaign.status = 'ENABLED'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        $count = 0;
        foreach ($rows as $row) {
            $asset = $row['asset'] ?? [];
            $campaignAsset = $row['campaignAsset'] ?? [];
            $campaign = $row['campaign'] ?? [];

            $assetId = (string) ($asset['id'] ?? '');
            if (empty($assetId)) {
                continue;
            }

            // Estrai testo in base al tipo di asset
            $text = $this->extractAssetText($asset);

            Extension::create([
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'campaign_id_google' => (string) ($campaign['id'] ?? ''),
                'extension_type' => $asset['type'] ?? 'UNKNOWN',
                'extension_text' => $text,
                'status' => $campaignAsset['status'] ?? null,
                'clicks' => 0,
                'impressions' => 0,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Estrae il testo leggibile da un asset in base al tipo
     */
    private function extractAssetText(array $asset): ?string
    {
        $type = $asset['type'] ?? '';

        // Callout: testo del callout
        if (!empty($asset['calloutAsset']['calloutText'])) {
            return $asset['calloutAsset']['calloutText'];
        }

        // Sitelink: testo del link + descrizioni
        if (!empty($asset['sitelinkAsset']['linkText'])) {
            $text = $asset['sitelinkAsset']['linkText'];
            $desc1 = $asset['sitelinkAsset']['description1'] ?? '';
            $desc2 = $asset['sitelinkAsset']['description2'] ?? '';
            if ($desc1 || $desc2) {
                $text .= ' — ' . trim("$desc1 $desc2");
            }
            return $text;
        }

        // Structured snippet: header + valori
        if (!empty($asset['structuredSnippetAsset']['header'])) {
            $header = $asset['structuredSnippetAsset']['header'];
            $values = $asset['structuredSnippetAsset']['values'] ?? [];
            if (is_array($values)) {
                return $header . ': ' . implode(', ', $values);
            }
            return $header;
        }

        // Fallback: nome asset
        return $asset['name'] ?? null;
    }

    /**
     * Sincronizza i termini di ricerca dall'API Google Ads
     *
     * @return int Numero di termini di ricerca sincronizzati
     */
    public function syncSearchTerms(string $dateFrom, string $dateTo): int
    {
        // Aumenta memory per account con tanti search terms
        $prevMemory = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        $gaql = "SELECT search_term_view.search_term, search_term_view.status, " .
                "campaign.id, campaign.name, campaign.status, ad_group.id, ad_group.name, " .
                "metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.cost_micros, metrics.conversions, metrics.conversions_value " .
                "FROM search_term_view " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED' " .
                "AND ad_group.status != 'REMOVED' " .
                "AND metrics.impressions > 0 " .
                "ORDER BY metrics.impressions DESC " .
                "LIMIT 50000";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggrega per search_term + ad_group.id (righe giornaliere)
        $aggregated = [];
        foreach ($rows as $row) {
            $stView = $row['searchTermView'] ?? [];
            $campaign = $row['campaign'] ?? [];
            $adGroup = $row['adGroup'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $term = $stView['searchTerm'] ?? '';
            if (empty($term)) {
                continue;
            }

            $key = md5($term . '_' . ($adGroup['id'] ?? ''));

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'term' => $term,
                    'status' => $stView['status'] ?? null,
                    'campaign_id' => (string) ($campaign['id'] ?? ''),
                    'campaign_name' => $campaign['name'] ?? '',
                    'ad_group_id' => (string) ($adGroup['id'] ?? ''),
                    'ad_group_name' => $adGroup['name'] ?? '',
                    'clicks' => 0,
                    'impressions' => 0,
                    'cost_micros' => 0,
                    'conversions' => 0.0,
                    'conversion_value' => 0.0,
                ];
            }

            $agg = &$aggregated[$key];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            $agg['conversions'] += (float) ($metrics['conversions'] ?? 0);
            $agg['conversion_value'] += (float) ($metrics['conversionsValue'] ?? 0);
            unset($agg);
        }

        // Per i search term serve un ad_group_id (intero) dalla tabella ga_ad_groups.
        // Se non esiste, creiamolo al volo per compatibilità con il modello SearchTerm.
        $adGroupLocalIds = $this->getOrCreateAdGroupLocalIds($aggregated);

        $count = 0;
        foreach ($aggregated as $key => $agg) {
            $impressions = $agg['impressions'];
            $clicks = $agg['clicks'];
            $cost = $agg['cost_micros'] / 1000000;
            $ctr = $this->calcCtr($clicks, $impressions);
            $isZeroCtr = ($ctr == 0 && $impressions > 0) ? 1 : 0;

            $localAdGroupId = $adGroupLocalIds[$agg['ad_group_id']] ?? 0;

            SearchTerm::create([
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'ad_group_id' => $localAdGroupId,
                'term' => $agg['term'],
                'match_type' => null,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'cost' => round($cost, 2),
                'conversions' => round($agg['conversions'], 2),
                'conversion_value' => round($agg['conversion_value'], 2),
                'is_zero_ctr' => $isZeroCtr,
                'campaign_name' => $agg['campaign_name'] ?? null,
                'ad_group_name' => $agg['ad_group_name'] ?? null,
            ]);
            $count++;
        }

        // Ripristina memory limit
        ini_set('memory_limit', $prevMemory);

        return $count;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Calcola CTR con cap al 100% per evitare valori impossibili.
     *
     * Google Ads search_term_view può restituire clicks > impressions
     * per artefatti dell'attribution model (broad match, sitelinks).
     * Valori > 100% sono fuorvianti per l'analisi, quindi li limitiamo.
     */
    private function calcCtr(int $clicks, int $impressions): float
    {
        if ($impressions <= 0) {
            return 0.0;
        }
        $ctr = ($clicks / $impressions) * 100;
        return min(round($ctr, 2), 100.0);
    }

    /**
     * Estrae le righe dalla risposta searchStream.
     *
     * La risposta searchStream è un array di batch, ognuno con una chiave 'results'.
     * La risposta search ha direttamente 'results'.
     *
     * @param array $response Risposta API
     * @return array Righe estratte
     */
    private function extractRows(array $response): array
    {
        // searchStream restituisce array di batch
        if (isset($response[0]['results'])) {
            $rows = [];
            foreach ($response as $batch) {
                foreach ($batch['results'] ?? [] as $row) {
                    $rows[] = $row;
                }
            }
            return $rows;
        }

        // search restituisce direttamente 'results'
        return $response['results'] ?? [];
    }

    /**
     * Estrae testi da array di AdTextAsset (headlines/descriptions RSA)
     *
     * Formato API: [{"text": "Headline 1", "pinnedField": "HEADLINE_1"}, ...]
     *
     * @param array $assets Array di asset testo
     * @return array Array di stringhe di testo
     */
    private function extractAdTexts(array $assets): array
    {
        $texts = [];
        foreach ($assets as $asset) {
            if (isset($asset['text'])) {
                $texts[] = $asset['text'];
            }
        }
        return $texts;
    }

    /**
     * Mappa campaign_id_google => campaign_name dal run corrente
     *
     * @return array<string, string>
     */
    private function getCampaignNamesMap(): array
    {
        $campaigns = Campaign::getByRun($this->syncId);
        $map = [];
        foreach ($campaigns as $c) {
            $map[$c['campaign_id_google']] = $c['campaign_name'];
        }
        return $map;
    }

    /**
     * Mappa ad_group_id_google => ad_group_name dal run corrente
     *
     * @return array<string, string>
     */
    private function getAdGroupNamesMap(): array
    {
        $adGroups = CampaignAdGroup::getByRun($this->syncId);
        $map = [];
        foreach ($adGroups as $ag) {
            $map[$ag['ad_group_id_google']] = $ag['ad_group_name'];
        }
        return $map;
    }

    /**
     * Ottiene o crea record ga_ad_groups locali per i termini di ricerca.
     *
     * La tabella ga_search_terms referenzia ga_ad_groups.id (locale).
     * Cerchiamo ad group per nome; se non esiste, lo creiamo.
     *
     * @param array $aggregated Array aggregato dei search terms
     * @return array<string, int> Mappa ad_group_id_google => ga_ad_groups.id
     */
    private function getOrCreateAdGroupLocalIds(array $aggregated): array
    {
        // Raccolta ad group unici
        $adGroupInfos = [];
        foreach ($aggregated as $agg) {
            $agId = $agg['ad_group_id'];
            if (!isset($adGroupInfos[$agId])) {
                $adGroupInfos[$agId] = $agg['ad_group_name'];
            }
        }

        // Cerca ad group esistenti per questo progetto
        $existing = AdGroup::getByProject($this->projectId);
        $nameToId = [];
        foreach ($existing as $eg) {
            $nameToId[strtolower($eg['name'])] = $eg['id'];
        }

        $map = [];
        foreach ($adGroupInfos as $googleId => $name) {
            $key = strtolower($name);
            if (isset($nameToId[$key])) {
                $map[$googleId] = $nameToId[$key];
            } else {
                // Crea nuovo ad group locale
                $localId = AdGroup::create([
                    'project_id' => $this->projectId,
                    'sync_id' => $this->syncId,
                    'name' => $name,
                    'terms_count' => 0,
                    'zero_ctr_count' => 0,
                    'wasted_impressions' => 0,
                ]);
                $nameToId[$key] = $localId;
                $map[$googleId] = $localId;
            }
        }

        return $map;
    }

    // =========================================================================
    // PMAX SYNC METHODS
    // =========================================================================

    /**
     * Sync PMax-specific data: asset groups, assets, audience signals.
     * Only runs for PERFORMANCE_MAX campaigns in the current sync.
     *
     * @return array ['asset_groups' => int, 'assets' => int]
     */
    private function syncPmaxData(string $dateFrom, string $dateTo): array
    {
        // Get PMax campaign IDs from this sync
        $pmaxCampaigns = Database::fetchAll(
            "SELECT campaign_id_google, campaign_name FROM ga_campaigns
             WHERE sync_id = ? AND campaign_type = 'PERFORMANCE_MAX'",
            [$this->syncId]
        );

        if (empty($pmaxCampaigns)) {
            return ['asset_groups' => 0, 'assets' => 0];
        }

        $totalAg = 0;
        $totalAssets = 0;

        foreach ($pmaxCampaigns as $campaign) {
            $campaignId = $campaign['campaign_id_google'];
            $campaignName = $campaign['campaign_name'];

            // 1. Sync asset groups
            $agCount = $this->syncAssetGroups($campaignId, $campaignName, $dateFrom, $dateTo);
            $totalAg += $agCount;

            // 2. Sync asset group assets + fetch asset content
            $assetCount = $this->syncAssetGroupAssets($campaignId);
            $totalAssets += $assetCount;

            // 3. Sync audience signals and search themes (updates ga_asset_groups JSON columns)
            $this->syncAudienceSignals($campaignId);

            Database::reconnect();
        }

        return ['asset_groups' => $totalAg, 'assets' => $totalAssets];
    }

    /**
     * Sync asset groups for a PMax campaign
     */
    private function syncAssetGroups(string $campaignIdGoogle, string $campaignName, string $dateFrom, string $dateTo): int
    {
        $gaql = "SELECT asset_group.id, asset_group.name, asset_group.status, " .
                "asset_group.ad_strength, asset_group.primary_status, " .
                "metrics.impressions, metrics.clicks, metrics.cost_micros, " .
                "metrics.conversions, metrics.conversions_value " .
                "FROM asset_group " .
                "WHERE campaign.id = {$campaignIdGoogle} " .
                "AND segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggregate by asset_group.id (rows are per-day with segments.date)
        $aggregated = [];
        foreach ($rows as $row) {
            $ag = $row['assetGroup'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $agId = (string) ($ag['id'] ?? '');
            if (empty($agId)) continue;

            if (!isset($aggregated[$agId])) {
                $aggregated[$agId] = [
                    'id' => $agId,
                    'name' => $ag['name'] ?? '',
                    'status' => $ag['status'] ?? null,
                    'ad_strength' => $ag['adStrength'] ?? 'UNSPECIFIED',
                    'primary_status' => $ag['primaryStatus'] ?? null,
                    'clicks' => 0, 'impressions' => 0, 'cost_micros' => 0,
                    'conversions' => 0.0, 'conversions_value' => 0.0,
                ];
            }

            $agg = &$aggregated[$agId];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            $agg['conversions'] += (float) ($metrics['conversions'] ?? 0);
            $agg['conversions_value'] += (float) ($metrics['conversionsValue'] ?? 0);
            unset($agg);
        }

        $count = 0;
        foreach ($aggregated as $agg) {
            $clicks = $agg['clicks'];
            $impressions = $agg['impressions'];
            $cost = $agg['cost_micros'] / 1000000;

            AssetGroup::create([
                'sync_id' => $this->syncId,
                'project_id' => $this->projectId,
                'campaign_id_google' => $campaignIdGoogle,
                'campaign_name' => $campaignName,
                'asset_group_id_google' => $agg['id'],
                'asset_group_name' => $agg['name'],
                'status' => $agg['status'],
                'ad_strength' => $agg['ad_strength'],
                'primary_status' => $agg['primary_status'],
                'impressions' => $impressions,
                'clicks' => $clicks,
                'cost' => round($cost, 2),
                'conversions' => round($agg['conversions'], 2),
                'conversions_value' => round($agg['conversions_value'], 2),
                'ctr' => $this->calcCtr($clicks, $impressions),
                'avg_cpc' => $clicks > 0 ? round($cost / $clicks, 2) : 0,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Sync individual assets for each asset group of a PMax campaign.
     * Two-step: (1) get asset group -> asset links with performance labels,
     *           (2) batch-fetch asset content (text/url).
     */
    private function syncAssetGroupAssets(string $campaignIdGoogle): int
    {
        // Step 1: Get asset links + performance labels
        $gaql = "SELECT asset_group.id, asset_group_asset.asset, " .
                "asset_group_asset.field_type, asset_group_asset.performance_label, " .
                "asset_group_asset.primary_status " .
                "FROM asset_group_asset " .
                "WHERE campaign.id = {$campaignIdGoogle}";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        if (empty($rows)) return 0;

        // Collect asset resource names for bulk content fetch
        $assetLinks = [];
        $resourceNames = [];
        foreach ($rows as $row) {
            $ag = $row['assetGroup'] ?? [];
            $aga = $row['assetGroupAsset'] ?? [];
            $assetResourceName = $aga['asset'] ?? '';
            if (empty($assetResourceName)) continue;

            $assetLinks[] = [
                'asset_group_id' => (string) ($ag['id'] ?? ''),
                'asset_resource_name' => $assetResourceName,
                'field_type' => $aga['fieldType'] ?? 'UNSPECIFIED',
                'performance_label' => $aga['performanceLabel'] ?? 'UNSPECIFIED',
                'primary_status' => $aga['primaryStatus'] ?? null,
            ];
            $resourceNames[$assetResourceName] = true;
        }

        // Step 2: Bulk fetch asset content
        $assetContent = $this->fetchAssetContent(array_keys($resourceNames));

        // Step 3: Save to DB
        $count = 0;
        foreach ($assetLinks as $link) {
            $content = $assetContent[$link['asset_resource_name']] ?? [];
            // Extract numeric asset ID from resource name: customers/123/assets/456 -> 456
            $assetId = '';
            if (preg_match('/assets\/(\d+)$/', $link['asset_resource_name'], $m)) {
                $assetId = $m[1];
            }

            AssetGroupAsset::create([
                'sync_id' => $this->syncId,
                'project_id' => $this->projectId,
                'asset_group_id_google' => $link['asset_group_id'],
                'asset_id_google' => $assetId,
                'field_type' => $link['field_type'],
                'performance_label' => $link['performance_label'],
                'primary_status' => $link['primary_status'],
                'text_content' => $content['text'] ?? null,
                'url_content' => $content['url'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Fetch content (text/image URL/video ID) for a batch of asset resource names.
     * Returns: ['customers/X/assets/Y' => ['text' => '...', 'url' => '...'], ...]
     */
    private function fetchAssetContent(array $resourceNames): array
    {
        if (empty($resourceNames)) return [];

        // Build IN clause with quoted resource names
        $quoted = array_map(fn($rn) => "'" . addslashes($rn) . "'", $resourceNames);
        $inClause = implode(',', $quoted);

        $gaql = "SELECT asset.resource_name, asset.type, " .
                "asset.text_asset.text, " .
                "asset.image_asset.full_size.url, " .
                "asset.youtube_video_asset.youtube_video_id " .
                "FROM asset " .
                "WHERE asset.resource_name IN ({$inClause})";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        $content = [];
        foreach ($rows as $row) {
            $asset = $row['asset'] ?? [];
            $rn = $asset['resourceName'] ?? '';
            if (empty($rn)) continue;

            $text = $asset['textAsset']['text'] ?? null;
            $imageUrl = $asset['imageAsset']['fullSize']['url'] ?? null;
            $videoId = $asset['youtubeVideoAsset']['youtubeVideoId'] ?? null;

            $content[$rn] = [
                'text' => $text,
                'url' => $imageUrl ?: ($videoId ? "https://youtube.com/watch?v={$videoId}" : null),
            ];
        }

        return $content;
    }

    /**
     * Sync audience signals and search themes for PMax asset groups.
     * Updates the JSON columns in ga_asset_groups.
     */
    private function syncAudienceSignals(string $campaignIdGoogle): void
    {
        $gaql = "SELECT asset_group.id, " .
                "asset_group_signal.audience, " .
                "asset_group_signal.search_theme " .
                "FROM asset_group_signal " .
                "WHERE campaign.id = {$campaignIdGoogle}";

        try {
            $response = $this->gadsService->searchStream($gaql);
            $rows = $this->extractRows($response);
        } catch (\Exception $e) {
            // asset_group_signal may not be available for all accounts/API versions
            Logger::channel('ads')->warning('PMax audience signals fetch failed', [
                'campaign_id' => $campaignIdGoogle,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if (empty($rows)) return;

        // Group signals by asset_group.id
        $signalsByAg = [];
        foreach ($rows as $row) {
            $agId = (string) ($row['assetGroup']['id'] ?? '');
            if (empty($agId)) continue;

            $audience = $row['assetGroupSignal']['audience'] ?? null;
            $searchTheme = $row['assetGroupSignal']['searchTheme'] ?? null;

            if (!isset($signalsByAg[$agId])) {
                $signalsByAg[$agId] = ['audiences' => [], 'search_themes' => []];
            }

            if ($audience) {
                $signalsByAg[$agId]['audiences'][] = $this->parseAudienceSignal($audience);
            }
            if ($searchTheme) {
                $themeText = $searchTheme['text'] ?? '';
                if ($themeText) {
                    $signalsByAg[$agId]['search_themes'][] = $themeText;
                }
            }
        }

        // Update ga_asset_groups with audience signals JSON
        foreach ($signalsByAg as $agId => $signals) {
            $audienceJson = !empty($signals['audiences']) ? json_encode($signals['audiences'], JSON_UNESCAPED_UNICODE) : null;
            $themesJson = !empty($signals['search_themes']) ? json_encode($signals['search_themes'], JSON_UNESCAPED_UNICODE) : null;

            Database::query(
                "UPDATE ga_asset_groups SET audience_signals = ?, search_themes = ?
                 WHERE sync_id = ? AND asset_group_id_google = ?",
                [$audienceJson, $themesJson, $this->syncId, $agId]
            );
        }
    }

    /**
     * Parse audience signal proto into simplified structure
     */
    private function parseAudienceSignal(array $audience): array
    {
        $result = ['type' => 'unknown', 'values' => []];

        // audience.userInterest
        if (!empty($audience['userInterest'])) {
            $result['type'] = 'interest';
            $result['values'][] = $audience['userInterest']['userInterestCategory'] ?? 'unknown';
        }
        // audience.userList
        if (!empty($audience['userList'])) {
            $result['type'] = 'user_list';
            $result['values'][] = $audience['userList']['userList'] ?? 'unknown';
        }
        // audience.customAudience
        if (!empty($audience['customAudience'])) {
            $result['type'] = 'custom_audience';
            $result['values'][] = $audience['customAudience']['customAudience'] ?? 'unknown';
        }
        // audience.detailedDemographic
        if (!empty($audience['detailedDemographic'])) {
            $result['type'] = 'demographic';
            $result['values'][] = $audience['detailedDemographic']['detailedDemographic'] ?? 'unknown';
        }

        return $result;
    }
}
