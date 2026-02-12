<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class CampaignAdGroup
{
    public static function create(array $data): int
    {
        return Database::insert('ga_campaign_ad_groups', [
            'project_id' => $data['project_id'],
            'run_id' => $data['run_id'],
            'campaign_id_google' => $data['campaign_id_google'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'campaign_type' => $data['campaign_type'] ?? null,
            'ad_group_id_google' => $data['ad_group_id_google'],
            'ad_group_name' => $data['ad_group_name'] ?? null,
            'ad_group_status' => $data['ad_group_status'] ?? null,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'avg_cpc' => $data['avg_cpc'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'conversion_value' => $data['conversion_value'] ?? 0,
            'conv_rate' => $data['conv_rate'] ?? 0,
        ]);
    }

    public static function getByRun(int $runId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_campaign_ad_groups WHERE run_id = ? ORDER BY campaign_name, cost DESC",
            [$runId]
        );
    }

    /**
     * Raggruppa ad groups per campaign_id_google
     */
    public static function getByRunGrouped(int $runId): array
    {
        $rows = self::getByRun($runId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['campaign_id_google']][] = $row;
        }
        return $grouped;
    }

    public static function getStatsByRun(int $runId): array
    {
        return Database::fetch(
            "SELECT COUNT(*) as total_ad_groups,
                    COUNT(DISTINCT campaign_id_google) as total_campaigns,
                    SUM(clicks) as total_clicks, SUM(impressions) as total_impressions,
                    SUM(cost) as total_cost, SUM(conversions) as total_conversions
             FROM ga_campaign_ad_groups WHERE run_id = ?",
            [$runId]
        ) ?: [];
    }

    public static function countByRun(int $runId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_campaign_ad_groups WHERE run_id = ?",
            [$runId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function deleteByRun(int $runId): bool
    {
        return Database::delete('ga_campaign_ad_groups', 'run_id = ?', [$runId]) >= 0;
    }
}
