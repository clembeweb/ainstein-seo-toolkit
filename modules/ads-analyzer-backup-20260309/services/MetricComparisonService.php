<?php

namespace Modules\AdsAnalyzer\Services;

use Modules\AdsAnalyzer\Models\Campaign;

class MetricComparisonService
{
    /** Variazione % minima per considerare un cambiamento "significativo" */
    private const DEFAULT_SIGNIFICANCE_THRESHOLD = 0.10; // 10%

    /** Soglie per generare alert */
    private const ALERT_THRESHOLDS = [
        'clicks_drop' => -0.15,
        'conversions_drop' => -0.10,
        'cost_increase' => 0.20,
        'ctr_drop' => -0.15,
        'cpc_increase' => 0.20,
    ];

    /**
     * Confronta stats aggregate di due run
     *
     * @return array ['deltas' => [...], 'alerts' => [...], 'is_significant' => bool, 'summary' => string]
     */
    public static function compareRuns(int $currentRunId, int $previousRunId, float $threshold = null): array
    {
        $threshold = $threshold ?? self::DEFAULT_SIGNIFICANCE_THRESHOLD;

        $currentStats = Campaign::getStatsByRun($currentRunId);
        $previousStats = Campaign::getStatsByRun($previousRunId);

        $deltas = self::computeDeltas($currentStats, $previousStats);
        $alerts = self::detectAlerts($deltas);
        $isSignificant = self::isSignificant($deltas, $threshold);
        $summary = self::buildDeltaSummary($deltas, $alerts);

        return [
            'deltas' => $deltas,
            'alerts' => $alerts,
            'is_significant' => $isSignificant,
            'summary' => $summary,
        ];
    }

    /**
     * Calcola delta % tra stats correnti e precedenti
     */
    public static function computeDeltas(array $current, array $previous): array
    {
        $metrics = [
            'total_clicks' => ['label' => 'Click', 'invert' => false],
            'total_impressions' => ['label' => 'Impressioni', 'invert' => false],
            'total_cost' => ['label' => 'Costo', 'invert' => true],     // aumento = negativo
            'total_conversions' => ['label' => 'Conversioni', 'invert' => false],
            'total_value' => ['label' => 'Valore Conv.', 'invert' => false],
            'avg_ctr' => ['label' => 'CTR Medio', 'invert' => false],
            'avg_cpc' => ['label' => 'CPC Medio', 'invert' => true],    // aumento = negativo
        ];

        $deltas = [];
        foreach ($metrics as $key => $config) {
            $curr = (float)($current[$key] ?? 0);
            $prev = (float)($previous[$key] ?? 0);

            $absolute = $curr - $prev;
            $percent = $prev > 0 ? (($curr - $prev) / $prev) : ($curr > 0 ? 1.0 : 0.0);

            $deltas[$key] = [
                'label' => $config['label'],
                'current' => $curr,
                'previous' => $prev,
                'absolute' => $absolute,
                'percent' => round($percent, 4),
                'percent_display' => round($percent * 100, 1),
                'invert' => $config['invert'],
                // positive_is_good: true = aumento migliora, false = aumento peggiora
                'positive_is_good' => !$config['invert'],
            ];
        }

        return $deltas;
    }

    /**
     * Identifica alert basati sulle soglie
     */
    private static function detectAlerts(array $deltas): array
    {
        $alerts = [];

        // Click drop
        if (($deltas['total_clicks']['percent'] ?? 0) <= self::ALERT_THRESHOLDS['clicks_drop']) {
            $pct = abs($deltas['total_clicks']['percent_display']);
            $alerts[] = [
                'type' => 'clicks_drop',
                'severity' => 'high',
                'message' => "Calo click del {$pct}% rispetto al periodo precedente",
                'metric' => 'total_clicks',
                'delta_percent' => $deltas['total_clicks']['percent_display'],
            ];
        }

        // Conversions drop
        if (($deltas['total_conversions']['percent'] ?? 0) <= self::ALERT_THRESHOLDS['conversions_drop']) {
            $pct = abs($deltas['total_conversions']['percent_display']);
            $alerts[] = [
                'type' => 'conversions_drop',
                'severity' => 'high',
                'message' => "Calo conversioni del {$pct}% rispetto al periodo precedente",
                'metric' => 'total_conversions',
                'delta_percent' => $deltas['total_conversions']['percent_display'],
            ];
        }

        // Cost increase
        if (($deltas['total_cost']['percent'] ?? 0) >= self::ALERT_THRESHOLDS['cost_increase']) {
            $pct = $deltas['total_cost']['percent_display'];
            $alerts[] = [
                'type' => 'cost_increase',
                'severity' => 'medium',
                'message' => "Aumento costo del {$pct}% rispetto al periodo precedente",
                'metric' => 'total_cost',
                'delta_percent' => $deltas['total_cost']['percent_display'],
            ];
        }

        // CTR drop
        if (($deltas['avg_ctr']['percent'] ?? 0) <= self::ALERT_THRESHOLDS['ctr_drop']) {
            $pct = abs($deltas['avg_ctr']['percent_display']);
            $alerts[] = [
                'type' => 'ctr_drop',
                'severity' => 'medium',
                'message' => "Calo CTR del {$pct}% rispetto al periodo precedente",
                'metric' => 'avg_ctr',
                'delta_percent' => $deltas['avg_ctr']['percent_display'],
            ];
        }

        // CPC increase
        if (($deltas['avg_cpc']['percent'] ?? 0) >= self::ALERT_THRESHOLDS['cpc_increase']) {
            $pct = $deltas['avg_cpc']['percent_display'];
            $alerts[] = [
                'type' => 'cpc_increase',
                'severity' => 'medium',
                'message' => "Aumento CPC del {$pct}% rispetto al periodo precedente",
                'metric' => 'avg_cpc',
                'delta_percent' => $deltas['avg_cpc']['percent_display'],
            ];
        }

        return $alerts;
    }

    /**
     * Determina se i cambiamenti sono significativi (almeno una metrica chiave oltre soglia)
     */
    private static function isSignificant(array $deltas, float $threshold): bool
    {
        $keyMetrics = ['total_clicks', 'total_conversions', 'total_cost', 'avg_ctr', 'avg_cpc'];

        foreach ($keyMetrics as $key) {
            if (isset($deltas[$key]) && abs($deltas[$key]['percent']) >= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera summary testuale dei cambiamenti per il prompt AI
     */
    public static function buildDeltaSummary(array $deltas, array $alerts): string
    {
        $lines = [];

        foreach ($deltas as $key => $d) {
            $sign = $d['percent_display'] >= 0 ? '+' : '';
            $lines[] = sprintf(
                "- %s: %.2f → %.2f (%s%s%%)",
                $d['label'],
                $d['previous'],
                $d['current'],
                $sign,
                $d['percent_display']
            );
        }

        if (!empty($alerts)) {
            $lines[] = "";
            $lines[] = "ALERT RILEVATI:";
            foreach ($alerts as $alert) {
                $lines[] = "- [{$alert['severity']}] {$alert['message']}";
            }
        }

        return implode("\n", $lines);
    }
}
