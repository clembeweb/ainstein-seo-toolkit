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
            'analysis_id' => $data['analysis_id'] ?? null,
            'category_key' => $data['category_key'],
            'category_name' => $data['category_name'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'keywords_count' => $data['keywords_count'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    public static function createForAnalysis(int $analysisId, array $data): int
    {
        return Database::insert('ga_negative_categories', [
            'project_id' => $data['project_id'],
            'ad_group_id' => $data['ad_group_id'],
            'analysis_id' => $analysisId,
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
        return Database::update('ga_negative_categories', $data, 'id = ?', [$id]) > 0;
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

    // === Analysis-based methods ===

    public static function getByAnalysis(int $analysisId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_categories WHERE analysis_id = ? ORDER BY priority DESC, sort_order ASC",
            [$analysisId]
        );
    }

    public static function getByAnalysisWithCounts(int $analysisId): array
    {
        $sql = "
            SELECT
                nc.*,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.category_id = nc.id) as total_keywords,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.category_id = nc.id AND nk.is_selected = 1) as selected_keywords
            FROM ga_negative_categories nc
            WHERE nc.analysis_id = ?
            ORDER BY
                CASE nc.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                nc.sort_order ASC
        ";

        return Database::fetchAll($sql, [$analysisId]);
    }

    public static function getByAnalysisAndAdGroup(int $analysisId, int $adGroupId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_negative_categories WHERE analysis_id = ? AND ad_group_id = ? ORDER BY priority DESC, sort_order ASC",
            [$analysisId, $adGroupId]
        );
    }

    public static function deleteByAnalysis(int $analysisId): bool
    {
        return Database::delete('ga_negative_categories', 'analysis_id = ?', [$analysisId]) >= 0;
    }

    public static function countByAnalysis(int $analysisId): int
    {
        return Database::count('ga_negative_categories', 'analysis_id = ?', [$analysisId]);
    }
}
