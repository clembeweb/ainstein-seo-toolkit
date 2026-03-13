<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AssetGroup
{
    public static function create(array $data): int
    {
        return Database::insert('ga_asset_groups', [
            'sync_id' => $data['sync_id'],
            'project_id' => $data['project_id'],
            'campaign_id_google' => $data['campaign_id_google'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'asset_group_id_google' => $data['asset_group_id_google'],
            'asset_group_name' => $data['asset_group_name'] ?? null,
            'status' => $data['status'] ?? null,
            'ad_strength' => $data['ad_strength'] ?? 'UNSPECIFIED',
            'primary_status' => $data['primary_status'] ?? null,
            'impressions' => $data['impressions'] ?? 0,
            'clicks' => $data['clicks'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'conversions_value' => $data['conversions_value'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'avg_cpc' => $data['avg_cpc'] ?? 0,
            'audience_signals' => isset($data['audience_signals']) ? json_encode($data['audience_signals'], JSON_UNESCAPED_UNICODE) : null,
            'search_themes' => isset($data['search_themes']) ? json_encode($data['search_themes'], JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function getBySyncId(int $syncId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_groups WHERE sync_id = ? ORDER BY campaign_name, cost DESC",
            [$syncId]
        );
    }

    public static function getBySyncAndCampaign(int $syncId, string $campaignIdGoogle): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_groups WHERE sync_id = ? AND campaign_id_google = ? ORDER BY cost DESC",
            [$syncId, $campaignIdGoogle]
        );
    }

    public static function getBySyncGrouped(int $syncId): array
    {
        $rows = self::getBySyncId($syncId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['campaign_id_google']][] = $row;
        }
        return $grouped;
    }

    public static function deleteBySyncId(int $syncId): bool
    {
        return Database::delete('ga_asset_groups', 'sync_id = ?', [$syncId]) >= 0;
    }
}
