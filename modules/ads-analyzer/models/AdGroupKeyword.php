<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AdGroupKeyword
{
    public static function create(array $data): int
    {
        return Database::insert('ga_ad_group_keywords', [
            'project_id' => $data['project_id'],
            'run_id' => $data['run_id'],
            'campaign_id_google' => $data['campaign_id_google'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'ad_group_id_google' => $data['ad_group_id_google'],
            'ad_group_name' => $data['ad_group_name'] ?? null,
            'keyword_text' => $data['keyword_text'],
            'match_type' => $data['match_type'] ?? null,
            'keyword_status' => $data['keyword_status'] ?? null,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'avg_cpc' => $data['avg_cpc'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'quality_score' => $data['quality_score'] ?? null,
            'first_page_cpc' => $data['first_page_cpc'] ?? null,
        ]);
    }

    public static function getByRun(int $runId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_ad_group_keywords WHERE run_id = ? ORDER BY campaign_name, ad_group_name, cost DESC",
            [$runId]
        );
    }

    /**
     * Raggruppa keyword per ad_group_id_google
     */
    public static function getGroupedByAdGroup(int $runId): array
    {
        $rows = self::getByRun($runId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['ad_group_id_google']][] = $row;
        }
        return $grouped;
    }

    public static function countByRun(int $runId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_ad_group_keywords WHERE run_id = ?",
            [$runId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function deleteByRun(int $runId): bool
    {
        return Database::delete('ga_ad_group_keywords', 'run_id = ?', [$runId]) >= 0;
    }
}
