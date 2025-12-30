<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * Source Model
 *
 * Manages aic_sources table (scraped sources for articles)
 */
class Source
{
    protected string $table = 'aic_sources';

    /**
     * Find source by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get all sources for an article
     */
    public function getByArticle(int $articleId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE article_id = ? ORDER BY is_custom ASC, id ASC";
        return Database::fetchAll($sql, [$articleId]);
    }

    /**
     * Get scraped sources for an article
     */
    public function getScrapedByArticle(int $articleId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE article_id = ? AND scrape_status = 'success' ORDER BY id ASC";
        return Database::fetchAll($sql, [$articleId]);
    }

    /**
     * Get pending sources for an article
     */
    public function getPendingByArticle(int $articleId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE article_id = ? AND scrape_status = 'pending' ORDER BY id ASC";
        return Database::fetchAll($sql, [$articleId]);
    }

    /**
     * Create source
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, [
            'article_id' => (int) $data['article_id'],
            'url' => $data['url'],
            'title' => $data['title'] ?? null,
            'is_custom' => !empty($data['is_custom']) ? 1 : 0,
            'scrape_status' => 'pending'
        ]);
    }

    /**
     * Bulk create sources for article
     */
    public function createBulk(int $articleId, array $urls, bool $isCustom = false): int
    {
        $count = 0;
        foreach ($urls as $url) {
            if (is_array($url)) {
                $this->create([
                    'article_id' => $articleId,
                    'url' => $url['url'],
                    'title' => $url['title'] ?? null,
                    'is_custom' => $isCustom
                ]);
            } else {
                $this->create([
                    'article_id' => $articleId,
                    'url' => $url,
                    'is_custom' => $isCustom
                ]);
            }
            $count++;
        }
        return $count;
    }

    /**
     * Update source with scraped content
     */
    public function updateScraped(int $id, array $data): bool
    {
        return Database::update($this->table, [
            'content_extracted' => $data['content'] ?? null,
            'headings_json' => isset($data['headings']) ? json_encode($data['headings']) : null,
            'word_count' => $data['word_count'] ?? 0,
            'scrape_status' => 'success',
            'scraped_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark source as failed
     */
    public function markFailed(int $id, string $error = null): bool
    {
        return Database::update($this->table, [
            'scrape_status' => 'failed',
            'error_message' => $error,
            'scraped_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Delete source
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Delete all sources for article
     */
    public function deleteByArticle(int $articleId): int
    {
        return Database::delete($this->table, 'article_id = ?', [$articleId]);
    }

    /**
     * Count sources for article
     */
    public function countByArticle(int $articleId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN scrape_status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN scrape_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN scrape_status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM {$this->table}
            WHERE article_id = ?
        ";

        return Database::fetch($sql, [$articleId]);
    }

    /**
     * Get total word count from scraped sources
     */
    public function getTotalWordCount(int $articleId): int
    {
        $sql = "SELECT COALESCE(SUM(word_count), 0) as total FROM {$this->table} WHERE article_id = ? AND scrape_status = 'success'";
        $result = Database::fetch($sql, [$articleId]);
        return (int) $result['total'];
    }
}
