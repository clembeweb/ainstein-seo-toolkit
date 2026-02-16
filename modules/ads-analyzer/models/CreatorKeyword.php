<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class CreatorKeyword
{
    public static function create(array $data): int
    {
        return Database::insert('ga_creator_keywords', [
            'project_id' => $data['project_id'],
            'generation_id' => $data['generation_id'],
            'keyword' => $data['keyword'],
            'match_type' => $data['match_type'] ?? 'broad',
            'ad_group_name' => $data['ad_group_name'] ?? null,
            'intent' => $data['intent'] ?? null,
            'search_volume' => $data['search_volume'] ?? null,
            'cpc' => $data['cpc'] ?? null,
            'competition_level' => $data['competition_level'] ?? null,
            'competition_index' => $data['competition_index'] ?? null,
            'is_negative' => $data['is_negative'] ?? 0,
            'is_selected' => $data['is_selected'] ?? 1,
            'reason' => $data['reason'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public static function bulkInsert(array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $kw) {
            self::create($kw);
            $count++;
        }
        return $count;
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_creator_keywords WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function getByProject(int $projectId, bool $includeDeselected = true): array
    {
        $sql = "SELECT * FROM ga_creator_keywords WHERE project_id = ?";
        if (!$includeDeselected) {
            $sql .= " AND is_selected = 1";
        }
        $sql .= " ORDER BY is_negative ASC, ad_group_name ASC, sort_order ASC";
        return Database::fetchAll($sql, [$projectId]);
    }

    public static function getSelectedByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_creator_keywords WHERE project_id = ? AND is_selected = 1 ORDER BY is_negative ASC, ad_group_name ASC, sort_order ASC",
            [$projectId]
        );
    }

    public static function getPositiveByProject(int $projectId, bool $selectedOnly = true): array
    {
        $sql = "SELECT * FROM ga_creator_keywords WHERE project_id = ? AND is_negative = 0";
        if ($selectedOnly) {
            $sql .= " AND is_selected = 1";
        }
        $sql .= " ORDER BY ad_group_name ASC, sort_order ASC";
        return Database::fetchAll($sql, [$projectId]);
    }

    public static function getNegativeByProject(int $projectId, bool $selectedOnly = true): array
    {
        $sql = "SELECT * FROM ga_creator_keywords WHERE project_id = ? AND is_negative = 1";
        if ($selectedOnly) {
            $sql .= " AND is_selected = 1";
        }
        $sql .= " ORDER BY sort_order ASC";
        return Database::fetchAll($sql, [$projectId]);
    }

    public static function toggleSelected(int $id): bool
    {
        $kw = self::find($id);
        if (!$kw) return false;
        $newVal = $kw['is_selected'] ? 0 : 1;
        return Database::update('ga_creator_keywords', ['is_selected' => $newVal], 'id = ?', [$id]) > 0;
    }

    public static function updateMatchType(int $id, string $matchType): bool
    {
        return Database::update('ga_creator_keywords', ['match_type' => $matchType], 'id = ?', [$id]) > 0;
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_creator_keywords', 'project_id = ?', [$projectId]) > 0;
    }

    public static function deleteByGeneration(int $generationId): bool
    {
        return Database::delete('ga_creator_keywords', 'generation_id = ?', [$generationId]) > 0;
    }

    public static function countByProject(int $projectId): array
    {
        $result = Database::fetch(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_selected = 1 AND is_negative = 0 THEN 1 ELSE 0 END) as selected_positive,
                SUM(CASE WHEN is_selected = 1 AND is_negative = 1 THEN 1 ELSE 0 END) as selected_negative,
                COUNT(DISTINCT ad_group_name) as ad_groups
            FROM ga_creator_keywords WHERE project_id = ?",
            [$projectId]
        );
        return $result ?: ['total' => 0, 'selected_positive' => 0, 'selected_negative' => 0, 'ad_groups' => 0];
    }
}
