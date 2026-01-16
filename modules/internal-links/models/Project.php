<?php

namespace Modules\InternalLinks\Models;

use Core\Database;

/**
 * Project Model
 *
 * Manages il_projects table and related operations
 */
class Project
{
    protected string $table = 'il_projects';

    /**
     * Find project by ID (with user check)
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::fetch($sql, $params);
    }

    /**
     * Get all projects for a user
     */
    public function allByUser(int $userId, string $status = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get all projects with stats for a user
     */
    public function allWithStats(int $userId): array
    {
        $sql = "
            SELECT
                p.*,
                COALESCE(ps.total_urls, 0) as total_urls,
                COALESCE(ps.scraped_urls, 0) as scraped_urls,
                COALESCE(ps.error_urls, 0) as error_urls,
                COALESCE(ps.total_links, 0) as total_links,
                ps.avg_relevance_score
            FROM {$this->table} p
            LEFT JOIN il_project_stats ps ON p.id = ps.project_id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ";

        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Get project with stats
     */
    public function findWithStats(int $id, int $userId): ?array
    {
        $sql = "
            SELECT
                p.*,
                COALESCE(ps.total_urls, 0) as total_urls,
                COALESCE(ps.scraped_urls, 0) as scraped_urls,
                COALESCE(ps.error_urls, 0) as error_urls,
                COALESCE(ps.total_links, 0) as total_links,
                COALESCE(ps.internal_links, 0) as internal_links,
                COALESCE(ps.external_links, 0) as external_links,
                COALESCE(ps.analyzed_links, 0) as analyzed_links,
                ps.avg_relevance_score,
                COALESCE(ps.orphan_pages, 0) as orphan_pages
            FROM {$this->table} p
            LEFT JOIN il_project_stats ps ON p.id = ps.project_id
            WHERE p.id = ? AND p.user_id = ?
        ";

        return Database::fetch($sql, [$id, $userId]);
    }

    /**
     * Create new project
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Create project with initial stats record
     */
    public function createWithStats(array $data): int
    {
        $projectId = $this->create($data);

        // Create empty stats record
        Database::insert('il_project_stats', ['project_id' => $projectId]);

        // Log activity
        $this->logActivity($projectId, $data['user_id'], 'project_created', ['name' => $data['name']]);

        return $projectId;
    }

    /**
     * Update project
     */
    public function update(int $id, array $data, int $userId): bool
    {
        return Database::update($this->table, $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Delete project
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Update project stats cache
     */
    public function updateStats(int $projectId): void
    {
        $sql = "
            INSERT INTO il_project_stats (
                project_id,
                total_urls,
                scraped_urls,
                error_urls,
                total_links,
                internal_links,
                external_links,
                analyzed_links,
                avg_relevance_score,
                orphan_pages
            )
            SELECT
                ?,
                (SELECT COUNT(*) FROM il_urls WHERE project_id = ?),
                (SELECT COUNT(*) FROM il_urls WHERE project_id = ? AND status = 'scraped'),
                (SELECT COUNT(*) FROM il_urls WHERE project_id = ? AND status = 'error'),
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ?),
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ? AND is_internal = 1),
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ? AND is_internal = 0),
                (SELECT COUNT(*) FROM il_internal_links WHERE project_id = ? AND ai_relevance_score IS NOT NULL),
                (SELECT AVG(ai_relevance_score) FROM il_internal_links WHERE project_id = ? AND ai_relevance_score IS NOT NULL),
                (SELECT COUNT(*) FROM il_urls u WHERE u.project_id = ?
                    AND NOT EXISTS (
                        SELECT 1 FROM il_internal_links il
                        WHERE il.project_id = ?
                        AND LOWER(TRIM(TRAILING '/' FROM il.destination_url)) = LOWER(TRIM(TRAILING '/' FROM u.url))
                    )
                )
            ON DUPLICATE KEY UPDATE
                total_urls = VALUES(total_urls),
                scraped_urls = VALUES(scraped_urls),
                error_urls = VALUES(error_urls),
                total_links = VALUES(total_links),
                internal_links = VALUES(internal_links),
                external_links = VALUES(external_links),
                analyzed_links = VALUES(analyzed_links),
                avg_relevance_score = VALUES(avg_relevance_score),
                orphan_pages = VALUES(orphan_pages),
                updated_at = CURRENT_TIMESTAMP
        ";

        Database::query($sql, array_fill(0, 11, $projectId));
    }

    /**
     * Get scraping progress for project
     */
    public function getScrapingProgress(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'scraped' THEN 1 ELSE 0 END) as scraped,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'scraping' THEN 1 ELSE 0 END) as scraping,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN status = 'no_content' THEN 1 ELSE 0 END) as no_content
            FROM il_urls
            WHERE project_id = ?
        ";

        $result = Database::fetch($sql, [$projectId]);

        $total = (int) $result['total'];
        $processed = (int) $result['scraped'] + (int) $result['errors'] + (int) $result['no_content'];

        return [
            'total' => $total,
            'scraped' => (int) $result['scraped'],
            'pending' => (int) $result['pending'],
            'scraping' => (int) $result['scraping'],
            'errors' => (int) $result['errors'],
            'no_content' => (int) $result['no_content'],
            'processed' => $processed,
            'progress' => $total > 0 ? round(($processed / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get recent activity for project
     */
    public function getActivity(int $projectId, int $limit = 10): array
    {
        $sql = "
            SELECT * FROM il_activity_logs
            WHERE project_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Log activity
     */
    public function logActivity(int $projectId, int $userId, string $action, array $details = []): void
    {
        Database::insert('il_activity_logs', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'action' => $action,
            'details' => json_encode($details),
        ]);
    }

    /**
     * Normalize base URL
     */
    public static function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);
        $url = rtrim($url, '/');

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Count projects for user
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }
}
