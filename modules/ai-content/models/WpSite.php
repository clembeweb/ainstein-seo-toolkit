<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * WpSite Model
 *
 * Manages aic_wp_sites table (WordPress sites connected)
 */
class WpSite
{
    protected string $table = 'aic_wp_sites';

    /**
     * Find site by ID (with user check)
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $site = Database::fetch($sql, $params);

        if ($site && $site['categories_cache']) {
            $site['categories'] = json_decode($site['categories_cache'], true);
        }

        return $site;
    }

    /**
     * Get all sites for a user
     */
    public function allByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC";
        $sites = Database::fetchAll($sql, [$userId]);

        foreach ($sites as &$site) {
            if ($site['categories_cache']) {
                $site['categories'] = json_decode($site['categories_cache'], true);
            }
        }

        return $sites;
    }

    /**
     * Get active sites for user
     */
    public function getActiveSites(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND is_active = 1 ORDER BY name ASC";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Create new site
     */
    public function create(array $data): int
    {
        $insert = [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'url' => rtrim($data['url'], '/'),
            'api_key' => $data['api_key'],
            'is_active' => $data['is_active'] ?? true,
        ];

        if (!empty($data['global_project_id'])) {
            $insert['global_project_id'] = (int) $data['global_project_id'];
        }

        return Database::insert($this->table, $insert);
    }

    /**
     * Update site
     */
    public function update(int $id, array $data, int $userId): bool
    {
        if (isset($data['url'])) {
            $data['url'] = rtrim($data['url'], '/');
        }

        return Database::update($this->table, $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Update categories cache
     */
    public function updateCategoriesCache(int $id, array $categories): bool
    {
        return Database::update($this->table, [
            'categories_cache' => json_encode($categories),
            'last_sync_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Toggle active status
     */
    public function toggleActive(int $id, int $userId): bool
    {
        $site = $this->find($id, $userId);
        if (!$site) {
            return false;
        }

        return Database::update($this->table, [
            'is_active' => !$site['is_active']
        ], 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Delete site
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Count sites for user
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * Check if URL already exists for user
     */
    public function urlExists(string $url, int $userId): bool
    {
        $url = rtrim($url, '/');
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE url = ? AND user_id = ?";
        $result = Database::fetch($sql, [$url, $userId]);
        return (int) $result['cnt'] > 0;
    }

    /**
     * Log publish action
     */
    public function logPublish(int $articleId, int $siteId, string $status, ?int $wpPostId = null, ?array $responseData = null): int
    {
        return Database::insert('aic_wp_publish_log', [
            'article_id' => $articleId,
            'wp_site_id' => $siteId,
            'wp_post_id' => $wpPostId,
            'status' => $status,
            'response_data' => $responseData ? json_encode($responseData) : null
        ]);
    }

    /**
     * Get publish history for article
     */
    public function getPublishHistory(int $articleId): array
    {
        $sql = "
            SELECT pl.*, ws.name as site_name, ws.url as site_url
            FROM aic_wp_publish_log pl
            JOIN aic_wp_sites ws ON pl.wp_site_id = ws.id
            WHERE pl.article_id = ?
            ORDER BY pl.created_at DESC
        ";

        return Database::fetchAll($sql, [$articleId]);
    }

    // ─────────────────────────────────────────────
    // Project-scoped methods (Global Projects hub)
    // ─────────────────────────────────────────────

    /**
     * Trova sito WP attivo collegato a un progetto globale
     */
    public function getActiveByProject(int $globalProjectId): ?array
    {
        $site = Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE global_project_id = ? AND is_active = 1
             ORDER BY created_at DESC LIMIT 1",
            [$globalProjectId]
        );

        if ($site && $site['categories_cache']) {
            $site['categories'] = json_decode($site['categories_cache'], true);
        }

        return $site;
    }

    /**
     * Tutti i siti WP collegati a un progetto globale
     */
    public function getAllByProject(int $globalProjectId): array
    {
        $sites = Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE global_project_id = ?
             ORDER BY name ASC",
            [$globalProjectId]
        );

        foreach ($sites as &$site) {
            if ($site['categories_cache']) {
                $site['categories'] = json_decode($site['categories_cache'], true);
            }
        }

        return $sites;
    }

    /**
     * Collega sito WP a un progetto globale
     */
    public function linkToProject(int $siteId, int $globalProjectId, int $userId): bool
    {
        return Database::update($this->table, [
            'global_project_id' => $globalProjectId,
        ], 'id = ? AND user_id = ?', [$siteId, $userId]) > 0;
    }

    /**
     * Scollega sito WP dal progetto (senza eliminarlo)
     */
    public function unlinkFromProject(int $siteId, int $userId): bool
    {
        return Database::update($this->table, [
            'global_project_id' => null,
        ], 'id = ? AND user_id = ?', [$siteId, $userId]) > 0;
    }

    /**
     * Siti WP dell'utente non collegati a nessun progetto
     */
    public function getUnlinkedByUser(int $userId): array
    {
        $sites = Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE user_id = ? AND global_project_id IS NULL AND is_active = 1
             ORDER BY name ASC",
            [$userId]
        );

        foreach ($sites as &$site) {
            if ($site['categories_cache']) {
                $site['categories'] = json_decode($site['categories_cache'], true);
            }
        }

        return $sites;
    }
}
