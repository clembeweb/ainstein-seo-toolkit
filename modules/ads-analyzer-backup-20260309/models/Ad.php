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

    /**
     * URL uniche degli annunci attivi, ordinate per numero di annunci che le usano
     */
    public static function getUniqueUrls(int $runId): array
    {
        return Database::fetchAll(
            "SELECT a.final_url, COUNT(*) as ad_count,
                    GROUP_CONCAT(DISTINCT a.ad_group_name SEPARATOR ', ') as ad_groups
             FROM ga_ads a
             INNER JOIN ga_campaigns c ON c.campaign_id_google = a.campaign_id_google AND c.run_id = a.run_id
             WHERE a.run_id = ? AND a.final_url IS NOT NULL AND a.final_url != ''
               AND (a.ad_status IS NULL OR a.ad_status = 'ENABLED')
               AND (c.campaign_status IS NULL OR c.campaign_status = 'ENABLED')
             GROUP BY a.final_url
             ORDER BY ad_count DESC, a.final_url",
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

    /**
     * Raggruppa annunci per ad_group_id_google
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

    public static function deleteByRun(int $runId): bool
    {
        return Database::delete('ga_ads', 'run_id = ?', [$runId]) >= 0;
    }

    /**
     * Per ogni ad_group, restituisce la final_url più comune dagli annunci ENABLED
     */
    public static function getLandingUrlsByAdGroup(int $runId): array
    {
        return Database::fetchAll(
            "SELECT ad_group_name, ad_group_id_google, final_url, COUNT(*) as url_count
             FROM ga_ads
             WHERE run_id = ? AND final_url IS NOT NULL AND final_url != ''
               AND (ad_status IS NULL OR ad_status = 'ENABLED')
             GROUP BY ad_group_name, ad_group_id_google, final_url
             ORDER BY ad_group_name, url_count DESC",
            [$runId]
        );
    }
}
