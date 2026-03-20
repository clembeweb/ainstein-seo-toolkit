<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AdGroup
{
    public static function create(array $data): int
    {
        $record = [
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'terms_count' => $data['terms_count'] ?? 0,
            'zero_ctr_count' => $data['zero_ctr_count'] ?? 0,
            'wasted_impressions' => $data['wasted_impressions'] ?? 0,
            'analysis_status' => $data['analysis_status'] ?? 'pending'
        ];

        if (isset($data['run_id'])) {
            $record['run_id'] = $data['run_id'];
        }

        return Database::insert('ga_ad_groups', $record);
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
        return Database::update('ga_ad_groups', $data, 'id = ?', [$id]) > 0;
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

    public static function getByRun(int $runId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_ad_groups WHERE run_id = ? ORDER BY name ASC",
            [$runId]
        );
    }

    public static function getByRunWithStats(int $runId): array
    {
        $sql = "
            SELECT
                ag.*,
                (SELECT COUNT(*) FROM ga_search_terms st WHERE st.ad_group_id = ag.id) as terms_total,
                (SELECT COUNT(*) FROM ga_search_terms st WHERE st.ad_group_id = ag.id AND st.is_zero_ctr = 1) as zero_ctr_total,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.ad_group_id = ag.id) as negatives_count,
                (SELECT COUNT(*) FROM ga_negative_keywords nk WHERE nk.ad_group_id = ag.id AND nk.is_selected = 1) as selected_count
            FROM ga_ad_groups ag
            WHERE ag.run_id = ?
            ORDER BY ag.name ASC
        ";

        return Database::fetchAll($sql, [$runId]);
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

    /**
     * Aggiorna URL landing per un Ad Group
     */
    public static function updateLandingUrl(int $id, string $url): bool
    {
        return Database::update('ga_ad_groups', ['landing_url' => $url], 'id = ?', [$id]) >= 0;
    }

    /**
     * Salva contenuto scrappato
     */
    public static function saveScrapedContent(int $id, string $content): bool
    {
        return Database::update('ga_ad_groups', ['scraped_content' => $content], 'id = ?', [$id]) >= 0;
    }

    /**
     * Salva contesto estratto da AI
     */
    public static function saveExtractedContext(int $id, string $context): bool
    {
        return Database::update('ga_ad_groups', ['extracted_context' => $context], 'id = ?', [$id]) >= 0;
    }

    /**
     * Trova Ad Groups con URL landing impostato
     */
    public static function getWithLandingUrl(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_ad_groups WHERE project_id = ? AND landing_url IS NOT NULL AND landing_url != '' ORDER BY name ASC",
            [$projectId]
        );
    }
}
