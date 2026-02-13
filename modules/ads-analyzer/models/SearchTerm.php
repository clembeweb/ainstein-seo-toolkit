<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class SearchTerm
{
    public static function create(array $data): int
    {
        $record = [
            'project_id' => $data['project_id'],
            'ad_group_id' => $data['ad_group_id'],
            'term' => $data['term'],
            'match_type' => $data['match_type'] ?? null,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'conversion_value' => $data['conversion_value'] ?? 0,
            'is_zero_ctr' => (int)($data['is_zero_ctr'] ?? 0)
        ];

        if (isset($data['run_id'])) {
            $record['run_id'] = $data['run_id'];
        }

        return Database::insert('ga_search_terms', $record);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_search_terms WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_search_terms WHERE project_id = ? ORDER BY impressions DESC",
            [$projectId]
        );
    }

    public static function getByAdGroup(int $adGroupId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_search_terms WHERE ad_group_id = ? ORDER BY impressions DESC",
            [$adGroupId]
        );
    }

    public static function getZeroCtrByAdGroup(int $adGroupId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_search_terms WHERE ad_group_id = ? AND is_zero_ctr = 1 ORDER BY impressions DESC",
            [$adGroupId]
        );
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_search_terms', 'project_id = ?', [$projectId]) >= 0;
    }

    public static function deleteByAdGroup(int $adGroupId): bool
    {
        return Database::delete('ga_search_terms', 'ad_group_id = ?', [$adGroupId]) >= 0;
    }

    public static function countByProject(int $projectId): int
    {
        return Database::count('ga_search_terms', 'project_id = ?', [$projectId]);
    }

    public static function countZeroCtrByProject(int $projectId): int
    {
        return Database::count('ga_search_terms', 'project_id = ? AND is_zero_ctr = 1', [$projectId]);
    }

    public static function getByRun(int $runId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_search_terms WHERE run_id = ? ORDER BY impressions DESC",
            [$runId]
        );
    }

    public static function getByRunAndAdGroup(int $runId, int $adGroupId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_search_terms WHERE run_id = ? AND ad_group_id = ? ORDER BY impressions DESC",
            [$runId, $adGroupId]
        );
    }

    public static function getStatsByRun(int $runId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_terms,
                SUM(CASE WHEN is_zero_ctr = 1 THEN 1 ELSE 0 END) as zero_ctr_count,
                SUM(CASE WHEN is_zero_ctr = 1 THEN impressions ELSE 0 END) as wasted_impressions,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                SUM(cost) as total_cost
            FROM ga_search_terms
            WHERE run_id = ?
        ";

        return Database::fetch($sql, [$runId]) ?: [
            'total_terms' => 0,
            'zero_ctr_count' => 0,
            'wasted_impressions' => 0,
            'total_clicks' => 0,
            'total_impressions' => 0,
            'total_cost' => 0
        ];
    }

    public static function countByRun(int $runId): int
    {
        return Database::count('ga_search_terms', 'run_id = ?', [$runId]);
    }

    public static function getStatsByProject(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_terms,
                SUM(CASE WHEN is_zero_ctr = 1 THEN 1 ELSE 0 END) as zero_ctr_count,
                SUM(CASE WHEN is_zero_ctr = 1 THEN impressions ELSE 0 END) as wasted_impressions,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                SUM(cost) as total_cost
            FROM ga_search_terms
            WHERE project_id = ?
        ";

        return Database::fetch($sql, [$projectId]) ?: [
            'total_terms' => 0,
            'zero_ctr_count' => 0,
            'wasted_impressions' => 0,
            'total_clicks' => 0,
            'total_impressions' => 0,
            'total_cost' => 0
        ];
    }
}
