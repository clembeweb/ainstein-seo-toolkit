<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * SerpResult Model
 *
 * Manages aic_serp_results table
 */
class SerpResult
{
    protected string $table = 'aic_serp_results';

    /**
     * Find SERP result by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get all SERP results for a keyword
     */
    public function getByKeyword(int $keywordId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE keyword_id = ? ORDER BY position ASC";
        return Database::fetchAll($sql, [$keywordId]);
    }

    /**
     * Save SERP results for keyword (replaces existing)
     */
    public function saveForKeyword(int $keywordId, array $results): int
    {
        // Delete existing results
        Database::delete($this->table, 'keyword_id = ?', [$keywordId]);

        $count = 0;
        foreach ($results as $result) {
            Database::insert($this->table, [
                'keyword_id' => $keywordId,
                'position' => $result['position'],
                'title' => $result['title'] ?? '',
                'url' => $result['url'],
                'snippet' => $result['snippet'] ?? '',
                'domain' => $result['domain'] ?? parse_url($result['url'], PHP_URL_HOST)
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get top N results for keyword
     */
    public function getTopResults(int $keywordId, int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE keyword_id = ? ORDER BY position ASC LIMIT ?";
        return Database::fetchAll($sql, [$keywordId, $limit]);
    }

    /**
     * Count results for keyword
     */
    public function countByKeyword(int $keywordId): int
    {
        return Database::count($this->table, 'keyword_id = ?', [$keywordId]);
    }

    /**
     * Delete all results for keyword
     */
    public function deleteByKeyword(int $keywordId): int
    {
        return Database::delete($this->table, 'keyword_id = ?', [$keywordId]);
    }

    /**
     * Get unique domains from SERP results
     */
    public function getUniqueDomains(int $keywordId): array
    {
        $sql = "SELECT DISTINCT domain FROM {$this->table} WHERE keyword_id = ? AND domain IS NOT NULL";
        $results = Database::fetchAll($sql, [$keywordId]);
        return array_column($results, 'domain');
    }
}
