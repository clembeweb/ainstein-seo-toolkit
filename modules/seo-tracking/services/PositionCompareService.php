<?php

namespace Modules\SeoTracking\Services;

use Core\Database;

/**
 * PositionCompareService
 * Confronta posizioni keyword tra due periodi (stile SEMrush)
 */
class PositionCompareService
{
    private int $projectId;

    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Confronta posizioni tra due periodi
     *
     * @param string $dateFromA Data inizio periodo A (precedente)
     * @param string $dateToA Data fine periodo A
     * @param string $dateFromB Data inizio periodo B (attuale)
     * @param string $dateToB Data fine periodo B
     * @param array $filters Filtri opzionali ['keyword' => '', 'url' => '']
     * @return array
     */
    public function compare(
        string $dateFromA,
        string $dateToA,
        string $dateFromB,
        string $dateToB,
        array $filters = []
    ): array {
        // Ottieni dati periodo A (precedente)
        $periodA = $this->getPeriodData($dateFromA, $dateToA, $filters);

        // Ottieni dati periodo B (attuale)
        $periodB = $this->getPeriodData($dateFromB, $dateToB, $filters);

        // Merge e calcola differenze
        return $this->calculateDifferences($periodA, $periodB);
    }

    /**
     * Ottiene dati aggregati per un periodo
     */
    private function getPeriodData(string $dateFrom, string $dateTo, array $filters): array
    {
        $sql = "
            SELECT
                query as keyword,
                ROUND(AVG(position), 1) as avg_position,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                ROUND(SUM(clicks) / NULLIF(SUM(impressions), 0) * 100, 2) as ctr,
                MAX(page) as url
            FROM st_gsc_data
            WHERE project_id = ?
              AND date BETWEEN ? AND ?
        ";

        $params = [$this->projectId, $dateFrom, $dateTo];

        // Filtro keyword
        if (!empty($filters['keyword'])) {
            $sql .= " AND query LIKE ?";
            $params[] = '%' . $filters['keyword'] . '%';
        }

        // Filtro URL
        if (!empty($filters['url'])) {
            $sql .= " AND page LIKE ?";
            $params[] = '%' . $filters['url'] . '%';
        }

        $sql .= " GROUP BY query ORDER BY total_impressions DESC LIMIT 1000";

        $results = Database::fetchAll($sql, $params);

        // Indicizza per keyword per lookup veloce
        $indexed = [];
        foreach ($results as $row) {
            $indexed[$row['keyword']] = $row;
        }

        return $indexed;
    }

    /**
     * Calcola differenze tra i due periodi
     */
    private function calculateDifferences(array $periodA, array $periodB): array
    {
        $results = [
            'all' => [],
            'improved' => [],
            'declined' => [],
            'new' => [],
            'lost' => [],
            'stats' => [
                'total' => 0,
                'improved' => 0,
                'declined' => 0,
                'new' => 0,
                'lost' => 0
            ]
        ];

        $allKeys = array_unique(array_merge(array_keys($periodA), array_keys($periodB)));
        $totalClicksB = array_sum(array_column($periodB, 'total_clicks'));

        foreach ($allKeys as $keyword) {
            $inA = isset($periodA[$keyword]);
            $inB = isset($periodB[$keyword]);

            $row = [
                'keyword' => $keyword,
                'url' => $inB ? $periodB[$keyword]['url'] : ($inA ? $periodA[$keyword]['url'] : ''),
                'position_previous' => $inA ? (float)$periodA[$keyword]['avg_position'] : null,
                'position_current' => $inB ? (float)$periodB[$keyword]['avg_position'] : null,
                'diff' => null,
                'status' => 'stable',
                'clicks_previous' => $inA ? (int)$periodA[$keyword]['total_clicks'] : 0,
                'clicks_current' => $inB ? (int)$periodB[$keyword]['total_clicks'] : 0,
                'impressions_previous' => $inA ? (int)$periodA[$keyword]['total_impressions'] : 0,
                'impressions_current' => $inB ? (int)$periodB[$keyword]['total_impressions'] : 0,
                'ctr_previous' => $inA ? (float)$periodA[$keyword]['ctr'] : 0,
                'ctr_current' => $inB ? (float)$periodB[$keyword]['ctr'] : 0,
                'traffic_share' => 0,
                'search_volume' => $this->getSearchVolume($keyword)
            ];

            // Calcola traffic share
            if ($totalClicksB > 0 && $inB) {
                $row['traffic_share'] = round($periodB[$keyword]['total_clicks'] / $totalClicksB * 100, 2);
            }

            // Determina status
            if ($inA && !$inB) {
                $row['status'] = 'lost';
                $results['lost'][] = $row;
                $results['stats']['lost']++;
            } elseif (!$inA && $inB) {
                $row['status'] = 'new';
                $results['new'][] = $row;
                $results['stats']['new']++;
            } else {
                // Entrambi presenti - calcola diff
                // Diff positivo = migliorato (posizione piu bassa = meglio)
                $row['diff'] = round($periodA[$keyword]['avg_position'] - $periodB[$keyword]['avg_position'], 1);

                if ($row['diff'] >= 1) {
                    $row['status'] = 'improved';
                    $results['improved'][] = $row;
                    $results['stats']['improved']++;
                } elseif ($row['diff'] <= -1) {
                    $row['status'] = 'declined';
                    $results['declined'][] = $row;
                    $results['stats']['declined']++;
                }
            }

            $results['all'][] = $row;
            $results['stats']['total']++;
        }

        // Ordina per impatto (impressions * |diff|)
        usort($results['all'], function ($a, $b) {
            $impactA = $a['impressions_current'] * abs($a['diff'] ?? 0);
            $impactB = $b['impressions_current'] * abs($b['diff'] ?? 0);
            return $impactB <=> $impactA;
        });

        // Ordina improved per diff decrescente
        usort($results['improved'], fn($a, $b) => $b['diff'] <=> $a['diff']);

        // Ordina declined per diff crescente (peggiori prima)
        usort($results['declined'], fn($a, $b) => $a['diff'] <=> $b['diff']);

        // Ordina new e lost per impressions
        usort($results['new'], fn($a, $b) => $b['impressions_current'] <=> $a['impressions_current']);
        usort($results['lost'], fn($a, $b) => $b['impressions_previous'] <=> $a['impressions_previous']);

        return $results;
    }

    /**
     * Ottiene volume ricerca da st_keywords
     */
    private function getSearchVolume(string $keyword): ?int
    {
        static $cache = [];

        if (!isset($cache[$this->projectId])) {
            $sql = "SELECT keyword, search_volume FROM st_keywords WHERE project_id = ?";
            $results = Database::fetchAll($sql, [$this->projectId]);
            $cache[$this->projectId] = [];
            foreach ($results as $row) {
                $cache[$this->projectId][strtolower($row['keyword'])] = $row['search_volume'];
            }
        }

        return $cache[$this->projectId][strtolower($keyword)] ?? null;
    }

    /**
     * Ottiene range date disponibili per il progetto
     */
    public function getAvailableDateRange(): array
    {
        $sql = "SELECT MIN(date) as min_date, MAX(date) as max_date
                FROM st_gsc_data WHERE project_id = ?";
        $result = Database::fetch($sql, [$this->projectId]);

        return [
            'min_date' => $result['min_date'] ?? date('Y-m-d', strtotime('-16 months')),
            'max_date' => $result['max_date'] ?? date('Y-m-d')
        ];
    }

    /**
     * Preset periodi comuni
     */
    public static function getPresets(): array
    {
        $today = date('Y-m-d');

        return [
            '7d' => [
                'label' => 'Ultimi 7 giorni vs precedenti',
                'dateFromB' => date('Y-m-d', strtotime('-6 days')),
                'dateToB' => $today,
                'dateFromA' => date('Y-m-d', strtotime('-13 days')),
                'dateToA' => date('Y-m-d', strtotime('-7 days')),
            ],
            '28d' => [
                'label' => 'Ultimi 28 giorni vs precedenti',
                'dateFromB' => date('Y-m-d', strtotime('-27 days')),
                'dateToB' => $today,
                'dateFromA' => date('Y-m-d', strtotime('-55 days')),
                'dateToA' => date('Y-m-d', strtotime('-28 days')),
            ],
            'month' => [
                'label' => 'Questo mese vs mese scorso',
                'dateFromB' => date('Y-m-01'),
                'dateToB' => $today,
                'dateFromA' => date('Y-m-01', strtotime('-1 month')),
                'dateToA' => date('Y-m-t', strtotime('-1 month')),
            ],
            'yoy' => [
                'label' => 'Anno su anno (ultimi 28gg)',
                'dateFromB' => date('Y-m-d', strtotime('-27 days')),
                'dateToB' => $today,
                'dateFromA' => date('Y-m-d', strtotime('-27 days -1 year')),
                'dateToA' => date('Y-m-d', strtotime('-1 year')),
            ],
        ];
    }
}
