<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AssetGroupAsset
{
    public static function create(array $data): int
    {
        return Database::insert('ga_asset_group_assets', [
            'sync_id' => $data['sync_id'],
            'project_id' => $data['project_id'],
            'asset_group_id_google' => $data['asset_group_id_google'],
            'asset_id_google' => $data['asset_id_google'] ?? null,
            'field_type' => $data['field_type'],
            'performance_label' => $data['performance_label'] ?? 'UNSPECIFIED',
            'primary_status' => $data['primary_status'] ?? null,
            'text_content' => $data['text_content'] ?? null,
            'url_content' => $data['url_content'] ?? null,
        ]);
    }

    public static function getBySyncId(int $syncId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_group_assets WHERE sync_id = ? ORDER BY asset_group_id_google, field_type",
            [$syncId]
        );
    }

    public static function getByAssetGroup(int $syncId, string $assetGroupIdGoogle): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_group_assets WHERE sync_id = ? AND asset_group_id_google = ? ORDER BY field_type, performance_label",
            [$syncId, $assetGroupIdGoogle]
        );
    }

    /**
     * Raggruppa asset per asset_group_id_google
     */
    public static function getBySyncGrouped(int $syncId): array
    {
        $rows = self::getBySyncId($syncId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['asset_group_id_google']][] = $row;
        }
        return $grouped;
    }

    /**
     * Conta asset per tipo e performance label per un asset group
     * Ritorna: ['HEADLINE' => ['BEST' => 2, 'GOOD' => 3, 'LOW' => 1], ...]
     */
    public static function getPerformanceSummary(int $syncId, string $assetGroupIdGoogle): array
    {
        $rows = Database::fetchAll(
            "SELECT field_type, performance_label, COUNT(*) as cnt
             FROM ga_asset_group_assets
             WHERE sync_id = ? AND asset_group_id_google = ?
             GROUP BY field_type, performance_label
             ORDER BY field_type, performance_label",
            [$syncId, $assetGroupIdGoogle]
        );

        $summary = [];
        foreach ($rows as $row) {
            $summary[$row['field_type']][$row['performance_label']] = (int) $row['cnt'];
        }
        return $summary;
    }

    public static function deleteBySyncId(int $syncId): bool
    {
        return Database::delete('ga_asset_group_assets', 'sync_id = ?', [$syncId]) >= 0;
    }
}
