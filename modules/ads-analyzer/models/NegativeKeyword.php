<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class NegativeKeyword
{
    public static function create(array $data): int
    {
        return Database::insert('ga_negative_keywords', [
            'project_id' => $data['project_id'],
            'ad_group_id' => $data['ad_group_id'],
            'category_id' => $data['category_id'],
            'keyword' => $data['keyword'],
            'is_selected' => $data['is_selected'] ?? true,
            'suggested_match_type' => $data['suggested_match_type'] ?? 'phrase'
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_negative_keywords WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function getByCategory(int $categoryId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_keywords WHERE category_id = ? ORDER BY keyword ASC",
            [$categoryId]
        );
    }

    public static function getByAdGroup(int $adGroupId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_keywords WHERE ad_group_id = ? ORDER BY keyword ASC",
            [$adGroupId]
        );
    }

    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_keywords WHERE project_id = ? ORDER BY keyword ASC",
            [$projectId]
        );
    }

    public static function getSelectedByAdGroup(int $adGroupId): array
    {
        $sql = "
            SELECT
                nk.*,
                nc.category_name,
                nc.priority
            FROM ga_negative_keywords nk
            JOIN ga_negative_categories nc ON nk.category_id = nc.id
            WHERE nk.ad_group_id = ? AND nk.is_selected = 1
            ORDER BY nc.priority DESC, nk.keyword ASC
        ";

        return Database::fetchAll($sql, [$adGroupId]);
    }

    public static function getSelectedByProject(int $projectId): array
    {
        $sql = "
            SELECT
                nk.*,
                nc.category_name,
                nc.priority
            FROM ga_negative_keywords nk
            JOIN ga_negative_categories nc ON nk.category_id = nc.id
            WHERE nk.project_id = ? AND nk.is_selected = 1
            ORDER BY nc.priority DESC, nk.keyword ASC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }

    public static function getSelectedByProjectWithAdGroup(int $projectId): array
    {
        $sql = "
            SELECT
                nk.*,
                nc.category_name,
                nc.priority,
                ag.name as ad_group_name
            FROM ga_negative_keywords nk
            JOIN ga_negative_categories nc ON nk.category_id = nc.id
            JOIN ga_ad_groups ag ON nk.ad_group_id = ag.id
            WHERE nk.project_id = ? AND nk.is_selected = 1
            ORDER BY ag.name ASC, nc.priority DESC, nk.keyword ASC
        ";

        return Database::fetchAll($sql, [$projectId]);
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

        $sql = "UPDATE ga_negative_keywords SET " . implode(', ', $fields) . " WHERE id = ?";

        return Database::execute($sql, $params) > 0;
    }

    public static function updateByCategory(int $categoryId, array $data): int
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $categoryId;

        $sql = "UPDATE ga_negative_keywords SET " . implode(', ', $fields) . " WHERE category_id = ?";

        return Database::execute($sql, $params);
    }

    public static function invertByCategory(int $categoryId): int
    {
        $sql = "UPDATE ga_negative_keywords SET is_selected = NOT is_selected WHERE category_id = ?";
        return Database::execute($sql, [$categoryId]);
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_negative_keywords', 'id = ?', [$id]) > 0;
    }

    public static function deleteByCategory(int $categoryId): bool
    {
        return Database::delete('ga_negative_keywords', 'category_id = ?', [$categoryId]) >= 0;
    }

    public static function deleteByAdGroup(int $adGroupId): bool
    {
        return Database::delete('ga_negative_keywords', 'ad_group_id = ?', [$adGroupId]) >= 0;
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_negative_keywords', 'project_id = ?', [$projectId]) >= 0;
    }

    public static function countByProject(int $projectId): int
    {
        return Database::count('ga_negative_keywords', 'project_id = ?', [$projectId]);
    }

    public static function countSelectedByProject(int $projectId): int
    {
        return Database::count('ga_negative_keywords', 'project_id = ? AND is_selected = 1', [$projectId]);
    }

    public static function countSelectedByAdGroup(int $adGroupId): int
    {
        return Database::count('ga_negative_keywords', 'ad_group_id = ? AND is_selected = 1', [$adGroupId]);
    }
}
