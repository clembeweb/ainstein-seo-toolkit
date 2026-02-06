<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * Article Model
 *
 * Manages aic_articles table
 */
class Article
{
    protected string $table = 'aic_articles';

    /**
     * Find article by ID (with user check)
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
     * Get article with related data
     */
    public function findWithRelations(int $id, int $userId): ?array
    {
        $sql = "
            SELECT
                a.*,
                k.keyword,
                k.language,
                k.location,
                ws.name as wp_site_name,
                ws.url as wp_site_url
            FROM {$this->table} a
            JOIN aic_keywords k ON a.keyword_id = k.id
            LEFT JOIN aic_wp_sites ws ON a.wp_site_id = ws.id
            WHERE a.id = ? AND a.user_id = ?
        ";

        $article = Database::fetch($sql, [$id, $userId]);

        if ($article) {
            // Get sources
            $sourceModel = new Source();
            $article['sources'] = $sourceModel->getByArticle($id);
        }

        return $article;
    }

    /**
     * Get all articles for a user
     */
    public function allByUser(int $userId, int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                a.*,
                k.keyword,
                k.language,
                ws.name as wp_site_name
            FROM {$this->table} a
            JOIN aic_keywords k ON a.keyword_id = k.id
            LEFT JOIN aic_wp_sites ws ON a.wp_site_id = ws.id
            WHERE a.user_id = ?
        ";
        $params = [$userId];

        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $data = Database::fetchAll($sql, $params);
        $total = $this->countByUser($userId, $status);

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage
        ];
    }

    /**
     * Create new article
     */
    public function create(array $data): int
    {
        $insertData = [
            'user_id' => $data['user_id'],
            'keyword_id' => $data['keyword_id'],
            'status' => $data['status'] ?? 'draft',
            'brief_data' => isset($data['brief_data']) ? json_encode($data['brief_data']) : null
        ];

        // Aggiungi project_id se fornito
        if (isset($data['project_id']) && $data['project_id']) {
            $insertData['project_id'] = $data['project_id'];
        }

        return Database::insert($this->table, $insertData);
    }

    /**
     * Create or update article with brief data
     */
    public function createWithBrief(int $keywordId, int $userId, array $brief, array $sources, array $customUrls, array $selectedPaa): int
    {
        // Check if article already exists for this keyword
        $existing = Database::fetch(
            "SELECT id FROM {$this->table} WHERE keyword_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1",
            [$keywordId, $userId]
        );

        $briefData = [
            'brief' => $brief,
            'sources' => $sources,
            'custom_urls' => $customUrls,
            'selected_paa' => $selectedPaa,
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            // Update existing article
            Database::update($this->table, [
                'brief_data' => json_encode($briefData),
                'status' => 'draft',
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);

            return $existing['id'];
        }

        // Get project_id from keyword (if exists)
        $keyword = Database::fetch("SELECT project_id FROM aic_keywords WHERE id = ?", [$keywordId]);
        $projectId = $keyword['project_id'] ?? null;

        // Create new article
        $insertData = [
            'user_id' => $userId,
            'keyword_id' => $keywordId,
            'status' => 'draft',
            'brief_data' => json_encode($briefData),
        ];

        if ($projectId) {
            $insertData['project_id'] = $projectId;
        }

        return Database::insert($this->table, $insertData);
    }

    /**
     * Update article
     */
    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $where = 'id = ?';
        $params = [$id];

        if ($userId !== null) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }

        // Handle JSON fields
        if (isset($data['brief_data']) && is_array($data['brief_data'])) {
            $data['brief_data'] = json_encode($data['brief_data']);
        }

        return Database::update($this->table, $data, $where, $params) > 0;
    }

    /**
     * Update article content after generation
     */
    public function updateContent(int $id, array $data): bool
    {
        return Database::update($this->table, [
            'title' => $data['title'],
            'content' => $data['content'],
            'meta_description' => $data['meta_description'] ?? null,
            'word_count' => $data['word_count'] ?? 0,
            'status' => 'ready',
            'ai_model' => $data['model_used'] ?? $data['ai_model'] ?? null,
            'generation_time_ms' => $data['generation_time_ms'] ?? null,
            'credits_used' => $data['credits_used'] ?? 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Update article status
     */
    public function updateStatus(int $id, string $status): bool
    {
        return Database::update($this->table, ['status' => $status], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark as published
     */
    public function markPublished(int $id, int $wpSiteId, int $wpPostId): bool
    {
        return Database::update($this->table, [
            'status' => 'published',
            'wp_site_id' => $wpSiteId,
            'wp_post_id' => $wpPostId,
            'published_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna path immagine di copertina
     */
    public function updateCoverImage(int $id, string $path): bool
    {
        return Database::update($this->table, [
            'cover_image_path' => $path,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Rimuovi immagine di copertina
     */
    public function removeCoverImage(int $id): bool
    {
        return Database::update($this->table, [
            'cover_image_path' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Delete article
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Count articles for user
     */
    public function countByUser(int $userId, ?string $status = null): int
    {
        if ($status) {
            return Database::count($this->table, 'user_id = ? AND status = ?', [$userId, $status]);
        }
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * Get articles by keyword
     */
    public function getByKeyword(int $keywordId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE keyword_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$keywordId]);
    }

    /**
     * Get stats for user
     */
    public function getStats(int $userId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'generating' THEN 1 ELSE 0 END) as generating,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                COALESCE(SUM(word_count), 0) as total_words,
                COALESCE(SUM(credits_used), 0) as total_credits
            FROM {$this->table}
            WHERE user_id = ?
        ";

        return Database::fetch($sql, [$userId]);
    }

    /**
     * Get recent articles
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        $sql = "
            SELECT a.*, k.keyword
            FROM {$this->table} a
            JOIN aic_keywords k ON a.keyword_id = k.id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Get stats for a specific project
     */
    public function getStatsByProject(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'generating' THEN 1 ELSE 0 END) as generating,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                COALESCE(SUM(word_count), 0) as total_words,
                COALESCE(SUM(credits_used), 0) as total_credits
            FROM {$this->table}
            WHERE project_id = ?
        ";

        return Database::fetch($sql, [$projectId]);
    }

    /**
     * Get recent articles for a specific project
     */
    public function getRecentByProject(int $projectId, int $limit = 5): array
    {
        $sql = "
            SELECT a.*, k.keyword
            FROM {$this->table} a
            JOIN aic_keywords k ON a.keyword_id = k.id
            WHERE a.project_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Count articles for project
     */
    public function countByProject(int $projectId, ?string $status = null): int
    {
        if ($status) {
            return Database::count($this->table, 'project_id = ? AND status = ?', [$projectId, $status]);
        }
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Get all articles for a project
     */
    public function allByProject(int $projectId, int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                a.*,
                k.keyword,
                k.language,
                ws.name as wp_site_name
            FROM {$this->table} a
            JOIN aic_keywords k ON a.keyword_id = k.id
            LEFT JOIN aic_wp_sites ws ON a.wp_site_id = ws.id
            WHERE a.project_id = ?
        ";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $data = Database::fetchAll($sql, $params);
        $total = $this->countByProject($projectId, $status);

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'per_page' => $perPage
        ];
    }
}
