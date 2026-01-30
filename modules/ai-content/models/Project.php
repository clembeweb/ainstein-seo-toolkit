<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * Project Model for AI Content Module
 * Manages aic_projects table
 */
class Project
{
    protected string $table = 'aic_projects';

    /**
     * Find project by ID
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
    public function allByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Get all projects with stats (keywords count, articles count)
     */
    public function allWithStats(int $userId): array
    {
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM aic_keywords WHERE project_id = p.id) as keywords_count,
                (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id) as articles_count,
                (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'ready') as articles_ready,
                (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'published') as articles_published
            FROM {$this->table} p
            WHERE p.user_id = ?
            ORDER BY p.updated_at DESC
        ";

        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Get all projects grouped by type with type-specific stats
     */
    public function allGroupedByType(int $userId): array
    {
        // Check which tables exist
        $queueExists = $this->tableExists('aic_queue');
        $metaTagsExists = $this->tableExists('aic_meta_tags');

        // Progetti Manual: stats articoli senza queue
        $sqlManual = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM aic_keywords WHERE project_id = p.id) as keywords_count,
                (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id) as articles_count,
                (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'ready') as articles_ready,
                (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'published') as articles_published
            FROM {$this->table} p
            WHERE p.user_id = ? AND p.type = 'manual'
            ORDER BY p.updated_at DESC
        ";
        $manualProjects = Database::fetchAll($sqlManual, [$userId]);

        // Progetti Auto: stats articoli + queue (se esiste)
        if ($queueExists) {
            $sqlAuto = "
                SELECT
                    p.*,
                    (SELECT COUNT(*) FROM aic_keywords WHERE project_id = p.id) as keywords_count,
                    (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id) as articles_count,
                    (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'ready') as articles_ready,
                    (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'published') as articles_published,
                    (SELECT COUNT(*) FROM aic_queue WHERE project_id = p.id AND status = 'pending') as queue_pending,
                    (SELECT COUNT(*) FROM aic_queue WHERE project_id = p.id AND status = 'processing') as queue_processing
                FROM {$this->table} p
                WHERE p.user_id = ? AND p.type = 'auto'
                ORDER BY p.updated_at DESC
            ";
        } else {
            $sqlAuto = "
                SELECT
                    p.*,
                    (SELECT COUNT(*) FROM aic_keywords WHERE project_id = p.id) as keywords_count,
                    (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id) as articles_count,
                    (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'ready') as articles_ready,
                    (SELECT COUNT(*) FROM aic_articles WHERE project_id = p.id AND status = 'published') as articles_published,
                    0 as queue_pending,
                    0 as queue_processing
                FROM {$this->table} p
                WHERE p.user_id = ? AND p.type = 'auto'
                ORDER BY p.updated_at DESC
            ";
        }
        $autoProjects = Database::fetchAll($sqlAuto, [$userId]);

        // Progetti Meta-Tag: stats meta tags (se tabella esiste)
        $metaTagProjects = [];
        if ($metaTagsExists) {
            $sqlMetaTags = "
                SELECT
                    p.*,
                    (SELECT COUNT(*) FROM aic_meta_tags WHERE project_id = p.id) as urls_count,
                    (SELECT COUNT(*) FROM aic_meta_tags WHERE project_id = p.id AND status = 'scraped') as urls_scraped,
                    (SELECT COUNT(*) FROM aic_meta_tags WHERE project_id = p.id AND status = 'generated') as urls_generated,
                    (SELECT COUNT(*) FROM aic_meta_tags WHERE project_id = p.id AND status = 'approved') as urls_approved,
                    (SELECT COUNT(*) FROM aic_meta_tags WHERE project_id = p.id AND status = 'published') as urls_published
                FROM {$this->table} p
                WHERE p.user_id = ? AND p.type = 'meta-tag'
                ORDER BY p.updated_at DESC
            ";
            $metaTagProjects = Database::fetchAll($sqlMetaTags, [$userId]);
        } else {
            // Se tabella non esiste, prendi comunque i progetti meta-tag senza stats
            $sqlMetaTags = "
                SELECT p.*, 0 as urls_count, 0 as urls_scraped, 0 as urls_generated, 0 as urls_approved, 0 as urls_published
                FROM {$this->table} p
                WHERE p.user_id = ? AND p.type = 'meta-tag'
                ORDER BY p.updated_at DESC
            ";
            $metaTagProjects = Database::fetchAll($sqlMetaTags, [$userId]);
        }

        return [
            'manual' => $manualProjects,
            'auto' => $autoProjects,
            'meta-tag' => $metaTagProjects,
        ];
    }

    /**
     * Check if a table exists in the database
     */
    private function tableExists(string $tableName): bool
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$tableName]
        );
        return ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Get project with full details
     */
    public function findWithStats(int $id, int $userId): ?array
    {
        $project = $this->find($id, $userId);

        if (!$project) {
            return null;
        }

        $project['stats'] = $this->getStats($id);

        return $project;
    }

    /**
     * Get project statistics
     */
    public function getStats(int $projectId): array
    {
        // Keywords count
        $keywordsCount = Database::count('aic_keywords', 'project_id = ?', [$projectId]);

        // Articles by status
        $articleStats = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'generating' THEN 1 ELSE 0 END) as generating,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(word_count) as total_words,
                SUM(credits_used) as total_credits
            FROM aic_articles
            WHERE project_id = ?
        ", [$projectId]);

        return [
            'keywords_count' => $keywordsCount,
            'articles_total' => (int) ($articleStats['total'] ?? 0),
            'articles_draft' => (int) ($articleStats['draft'] ?? 0),
            'articles_generating' => (int) ($articleStats['generating'] ?? 0),
            'articles_ready' => (int) ($articleStats['ready'] ?? 0),
            'articles_published' => (int) ($articleStats['published'] ?? 0),
            'articles_failed' => (int) ($articleStats['failed'] ?? 0),
            'total_words' => (int) ($articleStats['total_words'] ?? 0),
            'total_credits' => (int) ($articleStats['total_credits'] ?? 0),
        ];
    }

    /**
     * Get keywords for a project
     */
    public function getKeywords(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM aic_keywords WHERE project_id = ? ORDER BY created_at DESC",
            [$projectId]
        );
    }

    /**
     * Get articles for a project
     */
    public function getArticles(int $projectId, ?string $status = null): array
    {
        $sql = "SELECT a.*, k.keyword
                FROM aic_articles a
                LEFT JOIN aic_keywords k ON a.keyword_id = k.id
                WHERE a.project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create new project
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Update project
     */
    public function update(int $id, array $data, int $userId): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update($this->table, $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Delete project (cascade will delete keywords and articles)
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Count projects for user
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * Check if project belongs to user
     */
    public function belongsToUser(int $projectId, int $userId): bool
    {
        return $this->find($projectId, $userId) !== null;
    }
}
