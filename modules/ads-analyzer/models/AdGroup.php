<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AdGroup
{
    public static function create(array $data): int
    {
        return Database::insert('ga_ad_groups', [
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'terms_count' => $data['terms_count'] ?? 0,
            'zero_ctr_count' => $data['zero_ctr_count'] ?? 0,
            'wasted_impressions' => $data['wasted_impressions'] ?? 0,
            'analysis_status' => $data['analysis_status'] ?? 'pending'
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_ad_groups WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_ad_groups WHERE project_id = ? ORDER BY name ASC",
            [$projectId]
        );
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE ga_ad_groups SET " . implode(', ', $fields) . " WHERE id = ?";

        return Database::execute($sql, $params) > 0;
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_ad_groups', 'id = ?', [$id]) > 0;
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_ad_groups', 'project_id = ?', [$projectId]) >= 0;
    }

    public static function countByProject(int $projectId): int
    {
        return Database::count('ga_ad_groups', 'project_id = ?', [$projectId]);
    }

    public static function getWithStats(int $projectId): array
    {
        $sql = "
            SELECT
                ag.*,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.ad_group_id = ag.id) as negatives_count,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.ad_group_id = ag.id AND nk.is_selected = 1) as selected_count
            FROM ga_ad_groups ag
            WHERE ag.project_id = ?
            ORDER BY ag.name ASC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }
}
