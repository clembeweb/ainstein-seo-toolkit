<?php

namespace Modules\AdsAnalyzer\Services;

use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\Ad;
use Modules\AdsAnalyzer\Models\Extension;
use Modules\AdsAnalyzer\Models\CampaignAdGroup;
use Modules\AdsAnalyzer\Models\AdGroupKeyword;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\ScriptRun;

class IngestService
{
    /**
     * Elabora i termini di ricerca ricevuti via API.
     * Replica la logica di AnalysisController::processUpload() ma:
     * - NON cancella dati esistenti (accumulo storico)
     * - Crea/aggiorna ad_groups e search_terms
     *
     * @return array {ad_groups: int, terms: int}
     */
    public static function processSearchTerms(int $projectId, array $searchTerms, int $runId): array
    {
        // Raggruppa termini per ad_group
        $grouped = [];
        foreach ($searchTerms as $item) {
            $groupName = $item['ad_group'] ?? 'Default';
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'terms' => [],
                    'stats' => ['total' => 0, 'zero_ctr' => 0, 'wasted_imp' => 0]
                ];
            }

            $clicks = (int) ($item['clicks'] ?? 0);
            $impressions = (int) ($item['impressions'] ?? 0);
            $ctr = (float) ($item['ctr'] ?? 0);
            $isZeroCtr = ($ctr == 0 && $impressions > 0) ? 1 : 0;

            $grouped[$groupName]['terms'][] = [
                'term' => $item['term'] ?? '',
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'cost' => (float) ($item['cost'] ?? 0),
                'conversions' => (int) ($item['conversions'] ?? 0),
                'conversion_value' => (float) ($item['conversion_value'] ?? 0),
                'is_zero_ctr' => $isZeroCtr,
            ];

            $grouped[$groupName]['stats']['total']++;
            if ($isZeroCtr) {
                $grouped[$groupName]['stats']['zero_ctr']++;
                $grouped[$groupName]['stats']['wasted_imp'] += $impressions;
            }
        }

        $totalTerms = 0;
        $adGroupCount = 0;

        foreach ($grouped as $name => $data) {
            // Cerca ad_group esistente o creane uno nuovo
            $existingGroups = AdGroup::getByProject($projectId);
            $adGroupId = null;

            foreach ($existingGroups as $eg) {
                if (strtolower($eg['name']) === strtolower($name)) {
                    $adGroupId = $eg['id'];
                    // Aggiorna stats
                    AdGroup::update($adGroupId, [
                        'terms_count' => $eg['terms_count'] + $data['stats']['total'],
                        'zero_ctr_count' => $eg['zero_ctr_count'] + $data['stats']['zero_ctr'],
                        'wasted_impressions' => $eg['wasted_impressions'] + $data['stats']['wasted_imp'],
                    ]);
                    break;
                }
            }

            if (!$adGroupId) {
                $adGroupId = AdGroup::create([
                    'project_id' => $projectId,
                    'run_id' => $runId,
                    'name' => $name,
                    'terms_count' => $data['stats']['total'],
                    'zero_ctr_count' => $data['stats']['zero_ctr'],
                    'wasted_impressions' => $data['stats']['wasted_imp'],
                ]);
                $adGroupCount++;
            } else {
                $adGroupCount++;
            }

            foreach ($data['terms'] as $term) {
                SearchTerm::create([
                    'project_id' => $projectId,
                    'run_id' => $runId,
                    'ad_group_id' => $adGroupId,
                    'term' => $term['term'],
                    'clicks' => $term['clicks'],
                    'impressions' => $term['impressions'],
                    'ctr' => $term['ctr'],
                    'cost' => $term['cost'],
                    'conversions' => $term['conversions'],
                    'conversion_value' => $term['conversion_value'],
                    'is_zero_ctr' => $term['is_zero_ctr'],
                ]);
            }

            $totalTerms += $data['stats']['total'];
        }

        // Aggiorna totali progetto
        $currentProject = Project::find($projectId);
        Project::update($projectId, [
            'total_terms' => ($currentProject['total_terms'] ?? 0) + $totalTerms,
            'total_ad_groups' => AdGroup::countByProject($projectId),
            'status' => ($currentProject['status'] === 'draft') ? 'draft' : $currentProject['status'],
        ]);

        return ['ad_groups' => $adGroupCount, 'terms' => $totalTerms];
    }

    /**
     * Elabora i dati campagne ricevuti via API.
     * Salva in ga_campaigns, ga_ads, ga_extensions.
     *
     * @return array {campaigns: int, ads: int, extensions: int}
     */
    public static function processCampaignData(int $projectId, int $runId, array $data): array
    {
        $campaignsCount = 0;
        $adsCount = 0;
        $extensionsCount = 0;

        // Campagne
        foreach ($data['campaigns'] ?? [] as $campaign) {
            Campaign::create([
                'project_id' => $projectId,
                'run_id' => $runId,
                'campaign_id_google' => $campaign['campaign_id'] ?? '',
                'campaign_name' => $campaign['campaign_name'] ?? '',
                'campaign_status' => $campaign['status'] ?? null,
                'campaign_type' => $campaign['type'] ?? null,
                'bidding_strategy' => $campaign['bidding_strategy'] ?? null,
                'budget_amount' => $campaign['budget'] ?? null,
                'budget_type' => $campaign['budget_type'] ?? null,
                'clicks' => (int) ($campaign['clicks'] ?? 0),
                'impressions' => (int) ($campaign['impressions'] ?? 0),
                'ctr' => (float) ($campaign['ctr'] ?? 0),
                'avg_cpc' => (float) ($campaign['avg_cpc'] ?? 0),
                'cost' => (float) ($campaign['cost'] ?? 0),
                'conversions' => (float) ($campaign['conversions'] ?? 0),
                'conversion_value' => (float) ($campaign['conversion_value'] ?? 0),
                'conv_rate' => (float) ($campaign['conv_rate'] ?? 0),
            ]);
            $campaignsCount++;
        }

        // Annunci
        foreach ($data['ads'] ?? [] as $ad) {
            $headlines = $ad['headlines'] ?? [];
            Ad::create([
                'project_id' => $projectId,
                'run_id' => $runId,
                'campaign_id_google' => $ad['campaign_id'] ?? '',
                'campaign_name' => $ad['campaign_name'] ?? null,
                'ad_group_id_google' => $ad['ad_group_id'] ?? '',
                'ad_group_name' => $ad['ad_group_name'] ?? null,
                'ad_type' => $ad['type'] ?? null,
                'headline1' => $headlines[0] ?? null,
                'headline2' => $headlines[1] ?? null,
                'headline3' => $headlines[2] ?? null,
                'description1' => ($ad['descriptions'] ?? [])[0] ?? null,
                'description2' => ($ad['descriptions'] ?? [])[1] ?? null,
                'final_url' => $ad['final_url'] ?? null,
                'path1' => $ad['path1'] ?? null,
                'path2' => $ad['path2'] ?? null,
                'ad_status' => $ad['status'] ?? null,
                'clicks' => (int) ($ad['clicks'] ?? 0),
                'impressions' => (int) ($ad['impressions'] ?? 0),
                'ctr' => (float) ($ad['ctr'] ?? 0),
                'avg_cpc' => (float) ($ad['avg_cpc'] ?? 0),
                'cost' => (float) ($ad['cost'] ?? 0),
                'conversions' => (float) ($ad['conversions'] ?? 0),
                'quality_score' => isset($ad['quality_score']) ? (int) $ad['quality_score'] : null,
            ]);
            $adsCount++;
        }

        // Estensioni
        foreach ($data['extensions'] ?? [] as $ext) {
            Extension::create([
                'project_id' => $projectId,
                'run_id' => $runId,
                'campaign_id_google' => $ext['campaign_id'] ?? null,
                'extension_type' => $ext['type'] ?? 'UNKNOWN',
                'extension_text' => $ext['text'] ?? null,
                'status' => $ext['status'] ?? null,
                'clicks' => (int) ($ext['clicks'] ?? 0),
                'impressions' => (int) ($ext['impressions'] ?? 0),
            ]);
            $extensionsCount++;
        }

        // Ad Groups aggregate
        $adGroupsCount = 0;
        foreach ($data['ad_groups'] ?? [] as $ag) {
            CampaignAdGroup::create([
                'project_id' => $projectId,
                'run_id' => $runId,
                'campaign_id_google' => $ag['campaign_id'] ?? '',
                'campaign_name' => $ag['campaign_name'] ?? null,
                'campaign_type' => $ag['campaign_type'] ?? null,
                'ad_group_id_google' => $ag['ad_group_id'] ?? '',
                'ad_group_name' => $ag['ad_group_name'] ?? null,
                'ad_group_status' => $ag['status'] ?? null,
                'clicks' => (int) ($ag['clicks'] ?? 0),
                'impressions' => (int) ($ag['impressions'] ?? 0),
                'ctr' => (float) ($ag['ctr'] ?? 0),
                'avg_cpc' => (float) ($ag['avg_cpc'] ?? 0),
                'cost' => (float) ($ag['cost'] ?? 0),
                'conversions' => (float) ($ag['conversions'] ?? 0),
                'conversion_value' => (float) ($ag['conversion_value'] ?? 0),
                'conv_rate' => (float) ($ag['conv_rate'] ?? 0),
            ]);
            $adGroupsCount++;
        }

        // Keyword per ad group
        $keywordsCount = 0;
        foreach ($data['keywords'] ?? [] as $kw) {
            AdGroupKeyword::create([
                'project_id' => $projectId,
                'run_id' => $runId,
                'campaign_id_google' => $kw['campaign_id'] ?? '',
                'campaign_name' => $kw['campaign_name'] ?? null,
                'ad_group_id_google' => $kw['ad_group_id'] ?? '',
                'ad_group_name' => $kw['ad_group_name'] ?? null,
                'keyword_text' => $kw['keyword_text'] ?? '',
                'match_type' => $kw['match_type'] ?? null,
                'keyword_status' => $kw['status'] ?? null,
                'clicks' => (int) ($kw['clicks'] ?? 0),
                'impressions' => (int) ($kw['impressions'] ?? 0),
                'ctr' => (float) ($kw['ctr'] ?? 0),
                'avg_cpc' => (float) ($kw['avg_cpc'] ?? 0),
                'cost' => (float) ($kw['cost'] ?? 0),
                'conversions' => (float) ($kw['conversions'] ?? 0),
                'quality_score' => isset($kw['quality_score']) ? (int) $kw['quality_score'] : null,
                'first_page_cpc' => isset($kw['first_page_cpc']) ? (float) $kw['first_page_cpc'] : null,
            ]);
            $keywordsCount++;
        }

        return [
            'campaigns' => $campaignsCount,
            'ads' => $adsCount,
            'ad_groups' => $adGroupsCount,
            'keywords' => $keywordsCount,
            'extensions' => $extensionsCount,
        ];
    }
}
