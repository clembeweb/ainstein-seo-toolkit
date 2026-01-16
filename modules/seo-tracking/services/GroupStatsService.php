<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Modules\SeoTracking\Models\KeywordGroup;

/**
 * GroupStatsService
 * Calcola statistiche avanzate per gruppi di keyword
 */
class GroupStatsService
{
    protected KeywordGroup $groupModel;

    public function __construct()
    {
        $this->groupModel = new KeywordGroup();
    }

    /**
     * Dashboard stats per gruppo
     */
    public function getDashboardStats(int $groupId): array
    {
        $stats = $this->groupModel->getStats($groupId);
        $trend = $this->getPositionTrend($groupId, 7);

        return array_merge($stats, [
            'position_trend' => $trend['direction'],
            'position_change' => $trend['change'],
        ]);
    }

    /**
     * Trend posizione media nel periodo
     */
    public function getPositionTrend(int $groupId, int $days = 7): array
    {
        $sql = "
            SELECT
                AVG(kp.avg_position) as avg_position,
                kp.date
            FROM st_keyword_positions kp
            JOIN st_keyword_group_members m ON kp.keyword_id = m.keyword_id
            WHERE m.group_id = ?
              AND kp.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY kp.date
            ORDER BY kp.date ASC
        ";

        $data = Database::fetchAll($sql, [$groupId, $days]);

        if (count($data) < 2) {
            return ['direction' => 'stable', 'change' => 0, 'data' => $data];
        }

        $first = (float)$data[0]['avg_position'];
        $last = (float)$data[count($data) - 1]['avg_position'];
        $change = round($first - $last, 1); // Positivo = miglioramento

        $direction = 'stable';
        if ($change > 0.5) {
            $direction = 'up';
        } elseif ($change < -0.5) {
            $direction = 'down';
        }

        return [
            'direction' => $direction,
            'change' => $change,
            'data' => $data,
        ];
    }

    /**
     * Serie temporale click/impressioni per gruppo
     */
    public function getTrafficTimeSeries(int $groupId, int $days = 30): array
    {
        $sql = "
            SELECT
                kp.date,
                SUM(kp.total_clicks) as clicks,
                SUM(kp.total_impressions) as impressions,
                AVG(kp.avg_position) as avg_position
            FROM st_keyword_positions kp
            JOIN st_keyword_group_members m ON kp.keyword_id = m.keyword_id
            WHERE m.group_id = ?
              AND kp.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY kp.date
            ORDER BY kp.date ASC
        ";

        return Database::fetchAll($sql, [$groupId, $days]);
    }

    /**
     * Top performer del gruppo
     */
    public function getTopPerformers(int $groupId, int $limit = 5): array
    {
        $sql = "
            SELECT k.*
            FROM st_keywords k
            JOIN st_keyword_group_members m ON k.id = m.keyword_id
            WHERE m.group_id = ?
              AND k.last_clicks IS NOT NULL
            ORDER BY k.last_clicks DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$groupId, $limit]);
    }

    /**
     * Keyword con maggiori variazioni nel gruppo
     */
    public function getTopMovers(int $groupId, int $limit = 5): array
    {
        $sql = "
            SELECT
                k.*,
                kp.position_change,
                kp.avg_position as current_position
            FROM st_keywords k
            JOIN st_keyword_group_members m ON k.id = m.keyword_id
            JOIN st_keyword_positions kp ON k.id = kp.keyword_id
            WHERE m.group_id = ?
              AND kp.date = (SELECT MAX(date) FROM st_keyword_positions WHERE keyword_id = k.id)
              AND kp.position_change IS NOT NULL
            ORDER BY ABS(kp.position_change) DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$groupId, $limit]);
    }

    /**
     * Distribuzione posizioni nel gruppo
     */
    public function getPositionDistribution(int $groupId): array
    {
        $stats = $this->groupModel->getStats($groupId);

        return [
            'top3' => $stats['top3_count'],
            'top10' => $stats['top10_count'] - $stats['top3_count'],
            'top20' => $stats['top20_count'],
            'beyond20' => $stats['beyond20_count'],
        ];
    }

    /**
     * Confronto tra gruppi di un progetto
     */
    public function compareGroups(int $projectId): array
    {
        $groups = $this->groupModel->allWithStats($projectId);

        $comparison = [];
        foreach ($groups as $group) {
            $comparison[] = [
                'id' => $group['id'],
                'name' => $group['name'],
                'color' => $group['color'],
                'keyword_count' => (int)($group['keyword_count'] ?? 0),
                'avg_position' => round((float)($group['avg_position'] ?? 0), 1),
                'total_clicks' => (int)($group['total_clicks'] ?? 0),
                'total_impressions' => (int)($group['total_impressions'] ?? 0),
                'top10_count' => (int)($group['top10_count'] ?? 0),
                'top10_percent' => $group['keyword_count'] > 0
                    ? round(($group['top10_count'] / $group['keyword_count']) * 100, 1)
                    : 0,
            ];
        }

        // Ordina per click totali
        usort($comparison, fn($a, $b) => $b['total_clicks'] <=> $a['total_clicks']);

        return $comparison;
    }

    /**
     * Storico variazioni gruppo
     */
    public function getHistoricalComparison(int $groupId, int $periodDays = 7): array
    {
        $currentSql = "
            SELECT
                AVG(k.last_position) as avg_position,
                SUM(k.last_clicks) as total_clicks,
                SUM(k.last_impressions) as total_impressions
            FROM st_keywords k
            JOIN st_keyword_group_members m ON k.id = m.keyword_id
            WHERE m.group_id = ?
        ";

        $previousSql = "
            SELECT
                AVG(kp.avg_position) as avg_position,
                SUM(kp.total_clicks) as total_clicks,
                SUM(kp.total_impressions) as total_impressions
            FROM st_keyword_positions kp
            JOIN st_keyword_group_members m ON kp.keyword_id = m.keyword_id
            WHERE m.group_id = ?
              AND kp.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND kp.date < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";

        $current = Database::fetch($currentSql, [$groupId]);
        $previous = Database::fetch($previousSql, [$groupId, $periodDays * 2, $periodDays]);

        $calcChange = function($curr, $prev) {
            if (!$prev || $prev == 0) return null;
            return round((($curr - $prev) / $prev) * 100, 1);
        };

        return [
            'current' => [
                'avg_position' => round((float)($current['avg_position'] ?? 0), 1),
                'total_clicks' => (int)($current['total_clicks'] ?? 0),
                'total_impressions' => (int)($current['total_impressions'] ?? 0),
            ],
            'previous' => [
                'avg_position' => round((float)($previous['avg_position'] ?? 0), 1),
                'total_clicks' => (int)($previous['total_clicks'] ?? 0),
                'total_impressions' => (int)($previous['total_impressions'] ?? 0),
            ],
            'changes' => [
                'position' => round(
                    (float)($previous['avg_position'] ?? 0) - (float)($current['avg_position'] ?? 0),
                    1
                ),
                'clicks_percent' => $calcChange(
                    $current['total_clicks'] ?? 0,
                    $previous['total_clicks'] ?? 0
                ),
                'impressions_percent' => $calcChange(
                    $current['total_impressions'] ?? 0,
                    $previous['total_impressions'] ?? 0
                ),
            ],
        ];
    }
}
