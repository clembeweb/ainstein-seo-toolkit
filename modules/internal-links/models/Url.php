<?php

namespace Modules\InternalLinks\Models;

use Core\Database;

/**
 * Url Model
 *
 * Manages il_urls table and related operations
 */
class Url
{
    protected string $table = 'il_urls';

    /**
     * Find URL by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get URLs for a project with pagination
     */
    public function getByProject(int $projectId, int $page = 1, int $perPage = 50, ?string $status = null): array
    {
        $where = 'project_id = ?';
        $params = [$projectId];

        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        $offset = ($page - 1) * $perPage;

        // Count total
        $total = Database::count($this->table, $where, $params);

        // Get records
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $records = Database::fetchAll($sql, $params);

        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Get URL stats for a project
     */
    public function getStats(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'scraped' THEN 1 ELSE 0 END) as scraped,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM {$this->table}
            WHERE project_id = ?
        ";

        $result = Database::fetch($sql, [$projectId]);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'scraped' => (int) ($result['scraped'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0),
            'errors' => (int) ($result['errors'] ?? 0),
        ];
    }

    /**
     * Get pending URLs for scraping
     */
    public function getPending(int $projectId, int $limit = 100): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE project_id = ? AND status = 'pending'
            ORDER BY id ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Get all URLs for scraping (pending + error for retry)
     */
    public function getAllForScraping(int $projectId, int $limit = 100): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE project_id = ? AND status IN ('pending', 'error')
            ORDER BY id ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Mark URL as scraping
     */
    public function markAsScraping(int $id): bool
    {
        return Database::update($this->table, ['status' => 'scraping'], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark URL as scraped
     */
    public function markAsScraped(int $id, string $rawHtml, ?string $contentHtml, int $httpStatus): bool
    {
        $status = $contentHtml ? 'scraped' : 'no_content';

        return Database::update($this->table, [
            'raw_html' => $rawHtml,
            'content_html' => $contentHtml,
            'http_status' => $httpStatus,
            'status' => $status,
            'scraped_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark URL as error
     */
    public function markAsError(int $id, string $errorMessage, ?int $httpStatus = null): bool
    {
        return Database::update($this->table, [
            'status' => 'error',
            'http_status' => $httpStatus,
            'error_message' => $errorMessage,
            'scraped_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Reset URL status to pending
     */
    public function resetStatus(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'pending',
            'raw_html' => null,
            'content_html' => null,
            'http_status' => null,
            'scraped_at' => null,
            'error_message' => null,
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Reset all URLs in project to pending
     */
    public function resetAllInProject(int $projectId): int
    {
        return Database::update($this->table, [
            'status' => 'pending',
            'raw_html' => null,
            'content_html' => null,
            'http_status' => null,
            'scraped_at' => null,
            'error_message' => null,
        ], 'project_id = ?', [$projectId]);
    }

    /**
     * Bulk import URLs
     */
    public function bulkImport(int $projectId, array $urls): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($urls as $urlData) {
            $url = is_array($urlData) ? ($urlData['url'] ?? $urlData[0] ?? null) : $urlData;
            $keyword = is_array($urlData) ? ($urlData['keyword'] ?? $urlData[1] ?? null) : null;

            if (!$url) {
                $skipped++;
                continue;
            }

            $url = self::normalizeUrl($url);

            if (!filter_var($url, FILTER_VALIDATE_URL) && !self::isRelativeUrl($url)) {
                $errors[] = "Invalid URL: {$url}";
                $skipped++;
                continue;
            }

            // Check if exists
            $existing = Database::fetch(
                "SELECT id FROM {$this->table} WHERE project_id = ? AND url = ?",
                [$projectId, $url]
            );

            if ($existing) {
                // Update keyword if provided
                if ($keyword) {
                    Database::update($this->table, ['keyword' => $keyword], 'id = ?', [$existing['id']]);
                }
                $skipped++;
            } else {
                Database::insert($this->table, [
                    'project_id' => $projectId,
                    'url' => $url,
                    'keyword' => $keyword,
                ]);
                $imported++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Find URL by project and url string
     */
    public function findByUrl(int $projectId, string $url): ?array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE project_id = ?
            AND (url = ? OR LOWER(TRIM(TRAILING '/' FROM url)) = LOWER(TRIM(TRAILING '/' FROM ?)))
            LIMIT 1
        ";

        return Database::fetch($sql, [$projectId, $url, $url]);
    }

    /**
     * Get scraped URLs with content
     */
    public function getScrapedWithContent(int $projectId): array
    {
        $sql = "
            SELECT id, url, keyword, content_html
            FROM {$this->table}
            WHERE project_id = ?
            AND status = 'scraped'
            AND content_html IS NOT NULL
            ORDER BY id ASC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Get URL stats by status
     */
    public function getStatusStats(int $projectId): array
    {
        $sql = "
            SELECT status, COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ?
            GROUP BY status
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        $stats = [
            'pending' => 0,
            'scraping' => 0,
            'scraped' => 0,
            'error' => 0,
            'no_content' => 0,
        ];

        foreach ($results as $row) {
            $stats[$row['status']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Delete URL
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Delete all URLs in project
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Search URLs by keyword or URL
     */
    public function search(int $projectId, string $query, int $limit = 50): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE project_id = ?
            AND (url LIKE ? OR keyword LIKE ?)
            ORDER BY url ASC
            LIMIT ?
        ";

        $searchTerm = '%' . $query . '%';
        return Database::fetchAll($sql, [$projectId, $searchTerm, $searchTerm, $limit]);
    }

    /**
     * Normalize URL
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $url = rtrim($url, '/');
        return $url;
    }

    /**
     * Check if URL is relative
     */
    public static function isRelativeUrl(string $url): bool
    {
        return strpos($url, '/') === 0 && strpos($url, '//') !== 0;
    }

    /**
     * Convert relative URL to absolute
     */
    public static function toAbsolute(string $url, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (strpos($url, '/') === 0) {
            $parsed = parse_url($baseUrl);
            return $parsed['scheme'] . '://' . $parsed['host'] . $url;
        }

        return $baseUrl . '/' . ltrim($url, '/');
    }

    /**
     * Count URLs in project
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }
}
