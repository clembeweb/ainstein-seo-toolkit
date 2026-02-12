<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Ad
{
    public static function create(array $data): int
    {
        return Database::insert('ga_ads', [
            'project_id' => $data['project_id'],
            'run_id' => $data['run_id'],
            'campaign_id_google' => $data['campaign_id_google'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'ad_group_id_google' => $data['ad_group_id_google'],
            'ad_group_name' => $data['ad_group_name'] ?? null,
            'ad_type' => $data['ad_type'] ?? null,
            'headline1' => $data['headline1'] ?? null,
            'headline2' => $data['headline2'] ?? null,
            'headline3' => $data['headline3'] ?? null,
            'description1' => $data['description1'] ?? null,
            'description2' => $data['description2'] ?? null,
            'final_url' => $data['final_url'] ?? null,
            'path1' => $data['path1'] ?? null,
            'path2' => $data['path2'] ?? null,
            'ad_status' => $data['ad_status'] ?? null,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'avg_cpc' => $data['avg_cpc'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'quality_score' => $data['quality_score'] ?? null,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM ga_ads WHERE id = ?", [$id]) ?: null;
    }

    public static function getByRun(int $runId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_ads WHERE run_id = ? ORDER BY campaign_name, ad_group_name, cost DESC",
            [$runId]
        );
    }

    public static function getByCampaign(int $runId, string $campaignIdGoogle): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_ads WHERE run_id = ? AND campaign_id_google = ? ORDER BY ad_group_name, cost DESC",
            [$runId, $campaignIdGoogle]
        );
    }

    public static function getStatsByRun(int $runId): array
    {
        return Database::fetch(
            "SELECT COUNT(*) as total_ads, COUNT(DISTINCT ad_group_id_google) as total_ad_groups,
                    SUM(clicks) as total_clicks, SUM(impressions) as total_impressions,
                    SUM(cost) as total_cost, AVG(quality_score) as avg_quality_score
             FROM ga_ads WHERE run_id = ?",
            [$runId]
        ) ?: [];
    }

    public static function getUniqueUrls(int $runId): array
    {
        return Database::fetchAll(
            "SELECT DISTINCT final_url FROM ga_ads WHERE run_id = ? AND final_url IS NOT NULL AND final_url != '' ORDER BY final_url",
            [$runId]
        );
    }

    public static function countByProject(int $projectId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_ads WHERE project_id = ?",
            [$projectId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function deleteByRun(int $runId): bool
    {
        return Database::delete('ga_ads', 'run_id = ?', [$runId]) >= 0;
    }
}
