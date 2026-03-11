<?php

namespace Modules\AdsAnalyzer\Services;

use Services\GoogleAdsService;
use Core\Cache;
use Core\Database;
use Core\Logger;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\Sync;

/**
 * LiveKpiService — Metriche live da Google Ads API con cache
 *
 * Esegue una singola query GAQL aggregata per ottenere KPI di tutte le campagne
 * in un dato periodo. Risultati cachati 15 min per progetto+periodo.
 * Fallback a ultima sync DB se API non disponibile.
 */
class LiveKpiService
{
    private const CACHE_TTL = 900; // 15 minuti

    private GoogleAdsService $gadsService;
    private int $projectId;

    public function __construct(GoogleAdsService $gadsService, int $projectId)
    {
        $this->gadsService = $gadsService;
        $this->projectId = $projectId;
    }

    /**
     * Ottieni KPI aggregati per il periodo specificato
     *
     * @return array{source: string, clicks: int, impressions: int, cost: float, conversions: float, ctr: float, avg_cpc: float, campaigns: int}
     */
    public function getKpis(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "ga_live_kpis_{$this->projectId}_{$dateFrom}_{$dateTo}";

        // 1. Prova cache
        try {
            $cached = Cache::get($cacheKey, function () use ($dateFrom, $dateTo) {
                // Cache miss → chiama API
                return $this->fetchFromApi($dateFrom, $dateTo);
            }, self::CACHE_TTL);

            return $cached;
        } catch (\Exception $e) {
            Logger::channel('ads')->warning('LiveKpi cache/API failed, fallback to DB', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            // 2. Fallback a DB
            return $this->fetchFromDb();
        }
    }

    /**
     * Query GAQL aggregata — una singola chiamata API per tutti i KPI
     */
    private function fetchFromApi(string $dateFrom, string $dateTo): array
    {
        $gaql = "SELECT metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.average_cpc, metrics.cost_micros, " .
                "metrics.conversions, metrics.conversions_value " .
                "FROM campaign " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED'";

        $response = $this->gadsService->searchStream($gaql);

        // Parse e aggrega risultati
        $totals = [
            'clicks' => 0,
            'impressions' => 0,
            'cost' => 0.0,
            'conversions' => 0.0,
            'conversion_value' => 0.0,
            'campaigns' => 0,
        ];

        // searchStream ritorna array con chiave 'results' (o vuoto)
        // La risposta è un array di batch, ciascuno con 'results'
        $results = [];
        if (isset($response['results'])) {
            $results = $response['results'];
        } elseif (is_array($response)) {
            // searchStream: array di batch [{results: [...]}, ...]
            foreach ($response as $batch) {
                if (isset($batch['results'])) {
                    $results = array_merge($results, $batch['results']);
                }
            }
        }

        // Conta campagne uniche e aggrega metriche
        $campaignIds = [];
        foreach ($results as $row) {
            $metrics = $row['metrics'] ?? [];
            $totals['clicks'] += (int)($metrics['clicks'] ?? 0);
            $totals['impressions'] += (int)($metrics['impressions'] ?? 0);
            $totals['cost'] += ((int)($metrics['costMicros'] ?? 0)) / 1_000_000;
            $totals['conversions'] += (float)($metrics['conversions'] ?? 0);
            $totals['conversion_value'] += (float)($metrics['conversionsValue'] ?? 0);

            // Conta campagne uniche
            $cId = $row['campaign']['resourceName'] ?? null;
            if ($cId && !isset($campaignIds[$cId])) {
                $campaignIds[$cId] = true;
            }
        }

        $totals['campaigns'] = count($campaignIds);

        // Calcola metriche derivate
        $ctr = $totals['impressions'] > 0
            ? round(($totals['clicks'] / $totals['impressions']) * 100, 2)
            : 0.0;

        $avgCpc = $totals['clicks'] > 0
            ? round($totals['cost'] / $totals['clicks'], 2)
            : 0.0;

        return [
            'source' => 'api',
            'clicks' => $totals['clicks'],
            'impressions' => $totals['impressions'],
            'cost' => round($totals['cost'], 2),
            'conversions' => round($totals['conversions'], 1),
            'conversion_value' => round($totals['conversion_value'], 2),
            'ctr' => $ctr,
            'avg_cpc' => $avgCpc,
            'campaigns' => $totals['campaigns'],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Fallback: metriche dall'ultima sync completata nel DB
     */
    private function fetchFromDb(): array
    {
        $latestSync = Sync::getLatestByProject($this->projectId);
        if (!$latestSync) {
            return [
                'source' => 'none',
                'clicks' => 0,
                'impressions' => 0,
                'cost' => 0.0,
                'conversions' => 0.0,
                'conversion_value' => 0.0,
                'ctr' => 0.0,
                'avg_cpc' => 0.0,
                'campaigns' => 0,
                'date_from' => null,
                'date_to' => null,
            ];
        }

        $stats = Campaign::getStatsByRun($latestSync['id']);

        return [
            'source' => 'db',
            'clicks' => (int)($stats['total_clicks'] ?? 0),
            'impressions' => (int)($stats['total_impressions'] ?? 0),
            'cost' => round((float)($stats['total_cost'] ?? 0), 2),
            'conversions' => round((float)($stats['total_conversions'] ?? 0), 1),
            'conversion_value' => round((float)($stats['total_value'] ?? 0), 2),
            'ctr' => round((float)($stats['avg_ctr'] ?? 0), 2),
            'avg_cpc' => round((float)($stats['avg_cpc'] ?? 0), 2),
            'campaigns' => (int)($stats['total_campaigns'] ?? 0),
            'date_from' => $latestSync['date_range_start'] ?? null,
            'date_to' => $latestSync['date_range_end'] ?? null,
        ];
    }
}
