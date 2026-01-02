<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class NegativeCategory
{
    public static function create(array $data): int
    {
        return Database::insert('ga_negative_categories', [
            'project_id' => $data['project_id'],
            'ad_group_id' => $data['ad_group_id'],
            'category_key' => $data['category_key'],
            'category_name' => $data['category_name'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'keywords_count' => $data['keywords_count'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_negative_categories WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_categories WHERE project_id = ? ORDER BY priority DESC, sort_order ASC",
            [$projectId]
        );
    }

    public static function getByAdGroup(int $adGroupId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_categories WHERE ad_group_id = ? ORDER BY priority DESC, sort_order ASC",
            [$adGroupId]
        );
    }

    public static function getByAdGroupWithCounts(int $adGroupId): array
    {
        $sql = "
            SELECT
                nc.*,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.category_id = nc.id) as total_keywords,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.category_id = nc.id AND nk.is_selected = 1) as selected_keywords
            FROM ga_negative_categories nc
            WHERE nc.ad_group_id = ?
            ORDER BY
                CASE nc.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                nc.sort_order ASC
        ";

        return Database::fetchAll($sql, [$adGroupId]);
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

        $sql = "UPDATE ga_negative_categories SET " . implode(', ', $fields) . " WHERE id = ?";

        return Database::execute($sql, $params) > 0;
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_negative_categories', 'id = ?', [$id]) > 0;
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_negative_categories', 'project_id = ?', [$projectId]) >= 0;
    }

    public static function deleteByAdGroup(int $adGroupId): bool
    {
        return Database::delete('ga_negative_categories', 'ad_group_id = ?', [$adGroupId]) >= 0;
    }

    public static function countByProject(int $projectId): int
    {
        return Database::count('ga_negative_categories', 'project_id = ?', [$projectId]);
    }
}
