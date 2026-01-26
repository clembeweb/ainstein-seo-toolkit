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
     * Trend posizione media nel periodo (basato su rank checker)
     */
    public function getPositionTrend(int $groupId, int $days = 7): array
    {
        // Ottieni project_id dal gruppo
        $group = $this->groupModel->find($groupId);
        if (!$group) {
            return ['direction' => 'stable', 'change' => 0, 'data' => []];
        }
        $projectId = $group['project_id'];

        $sql = "
            SELECT
                DATE(rc.checked_at) as date,
                AVG(rc.serp_position) as avg_position,
                COUNT(DISTINCT rc.keyword) as keywords_checked
            FROM st_rank_checks rc
            JOIN st_keyword_group_members m ON m.group_id = ?
            JOIN st_keywords k ON m.keyword_id = k.id AND k.keyword = rc.keyword
            WHERE rc.project_id = ?
              AND rc.checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND rc.serp_position IS NOT NULL
            GROUP BY DATE(rc.checked_at)
            ORDER BY date ASC
        ";

        $data = Database::fetchAll($sql, [$groupId, $projectId, $days]);

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
     * Top performer del gruppo (migliori posizioni da rank checker)
     */
    public function getTopPerformers(int $groupId, int $limit = 5): array
    {
        // Ottieni project_id dal gruppo
        $group = $this->groupModel->find($groupId);
        if (!$group) {
            return [];
        }
        $projectId = $group['project_id'];

        $sql = "
            SELECT
                k.*,
                rc_latest.serp_position as current_position,
                rc_latest.serp_url as ranking_url
            FROM st_keywords k
            JOIN st_keyword_group_members m ON k.id = m.keyword_id
            JOIN (
                SELECT rc1.keyword, rc1.serp_position, rc1.serp_url
                FROM st_rank_checks rc1
                WHERE rc1.project_id = ?
                  AND rc1.serp_position IS NOT NULL
                  AND rc1.checked_at = (
                      SELECT MAX(rc2.checked_at)
                      FROM st_rank_checks rc2
                      WHERE rc2.project_id = rc1.project_id
                        AND rc2.keyword = rc1.keyword
                        AND rc2.serp_position IS NOT NULL
                  )
            ) rc_latest ON k.keyword = rc_latest.keyword
            WHERE m.group_id = ?
            ORDER BY rc_latest.serp_position ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $groupId, $limit]);
    }

    /**
     * Keyword con maggiori variazioni nel gruppo (da rank checker)
     */
    public function getTopMovers(int $groupId, int $limit = 5): array
    {
        // Ottieni project_id dal gruppo
        $group = $this->groupModel->find($groupId);
        if (!$group) {
            return [];
        }
        $projectId = $group['project_id'];

        $sql = "
            SELECT
                k.*,
                rc_new.serp_position as current_position,
                rc_old.serp_position as prev_position,
                (rc_old.serp_position - rc_new.serp_position) as position_change
            FROM st_keywords k
            JOIN st_keyword_group_members m ON k.id = m.keyword_id
            JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
            ) rc_new ON k.keyword = rc_new.keyword AND rc_new.rn = 1
            JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
            ) rc_old ON k.keyword = rc_old.keyword AND rc_old.rn = 2
            WHERE m.group_id = ?
            ORDER BY ABS(rc_old.serp_position - rc_new.serp_position) DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $projectId, $groupId, $limit]);
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
     * Storico variazioni gruppo (basato su rank checker)
     */
    public function getHistoricalComparison(int $groupId, int $periodDays = 7): array
    {
        // Ottieni project_id dal gruppo
        $group = $this->groupModel->find($groupId);
        if (!$group) {
            return [
                'current' => ['avg_position' => 0, 'improved' => 0, 'declined' => 0],
                'previous' => ['avg_position' => 0],
                'changes' => ['position' => 0],
            ];
        }
        $projectId = $group['project_id'];

        // Posizione media attuale (ultima verifica per ogni keyword)
        $currentSql = "
            SELECT AVG(latest.serp_position) as avg_position
            FROM st_keyword_group_members m
            JOIN st_keywords k ON m.keyword_id = k.id
            JOIN (
                SELECT rc1.keyword, rc1.serp_position
                FROM st_rank_checks rc1
                WHERE rc1.project_id = ?
                  AND rc1.serp_position IS NOT NULL
                  AND rc1.checked_at = (
                      SELECT MAX(rc2.checked_at)
                      FROM st_rank_checks rc2
                      WHERE rc2.project_id = rc1.project_id
                        AND rc2.keyword = rc1.keyword
                        AND rc2.serp_position IS NOT NULL
                  )
            ) latest ON k.keyword = latest.keyword
            WHERE m.group_id = ?
        ";

        // Posizione media N giorni fa
        $previousSql = "
            SELECT AVG(rc.serp_position) as avg_position
            FROM st_keyword_group_members m
            JOIN st_keywords k ON m.keyword_id = k.id
            JOIN st_rank_checks rc ON k.keyword = rc.keyword
            WHERE m.group_id = ?
              AND rc.project_id = ?
              AND rc.serp_position IS NOT NULL
              AND DATE(rc.checked_at) = (
                  SELECT MAX(DATE(checked_at))
                  FROM st_rank_checks
                  WHERE project_id = ?
                    AND DATE(checked_at) <= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              )
        ";

        // Conta miglioramenti/peggioramenti
        $variationsSql = "
            SELECT
                SUM(CASE WHEN rc_new.serp_position < rc_old.serp_position THEN 1 ELSE 0 END) as improved,
                SUM(CASE WHEN rc_new.serp_position > rc_old.serp_position THEN 1 ELSE 0 END) as declined
            FROM st_keyword_group_members m
            JOIN st_keywords k ON m.keyword_id = k.id
            JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
            ) rc_new ON k.keyword = rc_new.keyword AND rc_new.rn = 1
            JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
            ) rc_old ON k.keyword = rc_old.keyword AND rc_old.rn = 2
            WHERE m.group_id = ?
        ";

        $current = Database::fetch($currentSql, [$projectId, $groupId]);
        $previous = Database::fetch($previousSql, [$groupId, $projectId, $projectId, $periodDays]);
        $variations = Database::fetch($variationsSql, [$projectId, $projectId, $groupId]);

        $currentPos = round((float)($current['avg_position'] ?? 0), 1);
        $previousPos = round((float)($previous['avg_position'] ?? 0), 1);

        return [
            'current' => [
                'avg_position' => $currentPos,
                'improved' => (int)($variations['improved'] ?? 0),
                'declined' => (int)($variations['declined'] ?? 0),
            ],
            'previous' => [
                'avg_position' => $previousPos,
            ],
            'changes' => [
                'position' => $previousPos > 0 ? round($previousPos - $currentPos, 1) : 0,
            ],
        ];
    }
}
