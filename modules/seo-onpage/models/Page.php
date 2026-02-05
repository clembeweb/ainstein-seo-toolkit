<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * Page Model for SEO Onpage Optimizer Module
 * Manages sop_pages table
 */
class Page
{
    protected string $table = 'sop_pages';

    /**
     * Find page by ID
     */
    public function find(int $id, ?int $projectId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($projectId !== null) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        }

        return Database::fetch($sql, $params);
    }

    /**
     * Find page by URL hash
     */
    public function findByUrlHash(int $projectId, string $urlHash): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND url_hash = ?",
            [$projectId, $urlHash]
        );
    }

    /**
     * Get all pages for a project
     */
    public function allByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        // Filter by score range
        if (isset($filters['min_score'])) {
            $sql .= " AND onpage_score >= ?";
            $params[] = $filters['min_score'];
        }
        if (isset($filters['max_score'])) {
            $sql .= " AND onpage_score <= ?";
            $params[] = $filters['max_score'];
        }

        // Order
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $validColumns = ['created_at', 'onpage_score', 'url', 'last_analyzed_at'];
        if (!in_array($orderBy, $validColumns)) {
            $orderBy = 'created_at';
        }

        $sql .= " ORDER BY {$orderBy} {$orderDir}";

        // Limit
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get pages with latest analysis data
     */
    public function allWithAnalysis(int $projectId, array $filters = []): array
    {
        $sql = "
            SELECT
                p.*,
                a.issues_critical,
                a.issues_warning,
                a.issues_notice,
                a.created_at as analysis_date
            FROM {$this->table} p
            LEFT JOIN (
                SELECT page_id, issues_critical, issues_warning, issues_notice, created_at
                FROM sop_analyses
                WHERE (page_id, created_at) IN (
                    SELECT page_id, MAX(created_at)
                    FROM sop_analyses
                    GROUP BY page_id
                )
            ) a ON p.id = a.page_id
            WHERE p.project_id = ?
        ";
        $params = [$projectId];

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY p.onpage_score ASC, p.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get top problematic pages
     */
    public function getProblematic(int $projectId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND status = 'completed' AND onpage_score IS NOT NULL
             ORDER BY onpage_score ASC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Create new page
     */
    public function create(array $data): int
    {
        // Generate URL hash if not provided
        if (empty($data['url_hash']) && !empty($data['url'])) {
            $data['url_hash'] = hash('sha256', $data['url']);
        }

        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Create or update page by URL
     */
    public function upsert(int $projectId, string $url): int
    {
        $urlHash = hash('sha256', $url);
        $existing = $this->findByUrlHash($projectId, $urlHash);

        if ($existing) {
            return $existing['id'];
        }

        return $this->create([
            'project_id' => $projectId,
            'url' => $url,
            'url_hash' => $urlHash,
            'status' => 'pending',
        ]);
    }

    /**
     * Bulk insert pages
     */
    public function bulkInsert(int $projectId, array $urls): array
    {
        $inserted = 0;
        $skipped = 0;
        $ids = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }

            $urlHash = hash('sha256', $url);
            $existing = $this->findByUrlHash($projectId, $urlHash);

            if ($existing) {
                $skipped++;
                $ids[] = $existing['id'];
            } else {
                $id = $this->create([
                    'project_id' => $projectId,
                    'url' => $url,
                    'url_hash' => $urlHash,
                    'status' => 'pending',
                ]);
                $inserted++;
                $ids[] = $id;
            }
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'ids' => $ids,
        ];
    }

    /**
     * Update page
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Update page score from analysis
     */
    public function updateScore(int $id, int $score): bool
    {
        return $this->update($id, [
            'onpage_score' => $score,
            'status' => 'completed',
            'last_analyzed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete page
     */
    public function delete(int $id, int $projectId): bool
    {
        return Database::delete($this->table, 'id = ? AND project_id = ?', [$id, $projectId]) > 0;
    }

    /**
     * Count pages for project
     */
    public function countByProject(int $projectId, ?string $status = null): int
    {
        if ($status) {
            return Database::count($this->table, 'project_id = ? AND status = ?', [$projectId, $status]);
        }
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Get pages pending analysis
     */
    public function getPending(int $projectId, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND status = 'pending' ORDER BY created_at ASC LIMIT ?",
            [$projectId, $limit]
        );
    }
}
