<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class CreatorCampaign
{
    public static function create(array $data): int
    {
        return Database::insert('ga_creator_campaigns', [
            'project_id' => $data['project_id'],
            'generation_id' => $data['generation_id'],
            'campaign_type' => $data['campaign_type'],
            'campaign_name' => $data['campaign_name'],
            'assets_json' => is_array($data['assets_json']) ? json_encode($data['assets_json']) : $data['assets_json'],
        ]);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            "SELECT * FROM ga_creator_campaigns WHERE id = ?",
            [$id]
        );
        return $row ? self::decodeAssets($row) : null;
    }

    public static function getLatestByProject(int $projectId): ?array
    {
        $row = Database::fetch(
            "SELECT * FROM ga_creator_campaigns WHERE project_id = ? ORDER BY id DESC LIMIT 1",
            [$projectId]
        );
        return $row ? self::decodeAssets($row) : null;
    }

    public static function update(int $id, array $data): bool
    {
        if (isset($data['assets_json']) && is_array($data['assets_json'])) {
            $data['assets_json'] = json_encode($data['assets_json']);
        }
        return Database::update('ga_creator_campaigns', $data, 'id = ?', [$id]) > 0;
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_creator_campaigns', 'project_id = ?', [$projectId]) > 0;
    }

    private static function decodeAssets(array $row): array
    {
        if (!empty($row['assets_json'])) {
            $row['assets'] = json_decode($row['assets_json'], true) ?: [];
        } else {
            $row['assets'] = [];
        }
        return $row;
    }
}
