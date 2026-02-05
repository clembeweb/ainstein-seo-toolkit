<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * Issue Model for SEO Onpage Optimizer Module
 * Manages sop_issues table
 */
class Issue
{
    protected string $table = 'sop_issues';

    // Categories
    public const CATEGORY_META = 'meta';
    public const CATEGORY_CONTENT = 'content';
    public const CATEGORY_IMAGES = 'images';
    public const CATEGORY_LINKS = 'links';
    public const CATEGORY_TECHNICAL = 'technical';
    public const CATEGORY_PERFORMANCE = 'performance';

    // Severities
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_NOTICE = 'notice';

    /**
     * Find issue by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get all issues for analysis
     */
    public function allByAnalysis(int $analysisId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE analysis_id = ? ORDER BY severity ASC, category ASC",
            [$analysisId]
        );
    }

    /**
     * Get all issues for page
     */
    public function allByPage(int $pageId, array $filters = []): array
    {
        $sql = "SELECT i.*, a.created_at as analysis_date
                FROM {$this->table} i
                JOIN sop_analyses a ON i.analysis_id = a.id
                WHERE i.page_id = ?";
        $params = [$pageId];

        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $sql .= " AND i.severity = ?";
            $params[] = $filters['severity'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND i.category = ?";
            $params[] = $filters['category'];
        }

        $sql .= " ORDER BY i.severity ASC, i.category ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get all issues for project
     */
    public function allByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT i.*, p.url, a.onpage_score, a.created_at as analysis_date
                FROM {$this->table} i
                JOIN sop_analyses a ON i.analysis_id = a.id
                JOIN sop_pages p ON i.page_id = p.id
                WHERE a.project_id = ?";
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $sql .= " AND i.severity = ?";
            $params[] = $filters['severity'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND i.category = ?";
            $params[] = $filters['category'];
        }

        // Get only latest analysis issues by default
        if (empty($filters['all_analyses'])) {
            $sql .= " AND a.id = (SELECT MAX(id) FROM sop_analyses WHERE page_id = i.page_id)";
        }

        $sql .= " ORDER BY i.severity ASC, i.category ASC, i.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get issues grouped by category for project
     */
    public function getGroupedByCategory(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT
                i.category,
                COUNT(*) as total,
                SUM(CASE WHEN i.severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN i.severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN i.severity = 'notice' THEN 1 ELSE 0 END) as notice,
                SUM(CASE WHEN i.status = 'fixed' THEN 1 ELSE 0 END) as fixed
             FROM {$this->table} i
             JOIN sop_analyses a ON i.analysis_id = a.id
             WHERE a.project_id = ?
               AND a.id = (SELECT MAX(id) FROM sop_analyses WHERE page_id = i.page_id)
             GROUP BY i.category
             ORDER BY critical DESC, warning DESC",
            [$projectId]
        );
    }

    /**
     * Get most common issues for project
     */
    public function getMostCommon(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT
                i.check_name,
                i.category,
                i.severity,
                i.message,
                COUNT(*) as occurrences
             FROM {$this->table} i
             JOIN sop_analyses a ON i.analysis_id = a.id
             WHERE a.project_id = ? AND i.status = 'open'
               AND a.id = (SELECT MAX(id) FROM sop_analyses WHERE page_id = i.page_id)
             GROUP BY i.check_name, i.category, i.severity, i.message
             ORDER BY occurrences DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Create issue
     */
    public function create(array $data): int
    {
        $data['status'] = $data['status'] ?? 'open';
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Bulk create issues from analysis checks
     */
    public function createFromChecks(int $analysisId, int $pageId, array $checks): int
    {
        $count = 0;

        foreach ($checks as $check) {
            // Skip passed checks
            if (($check['passed'] ?? true) === true) {
                continue;
            }

            $severity = match ($check['priority'] ?? 'low') {
                'high', 'critical' => self::SEVERITY_CRITICAL,
                'medium' => self::SEVERITY_WARNING,
                default => self::SEVERITY_NOTICE,
            };

            $category = $this->mapCategory($check['name'] ?? '');

            $this->create([
                'analysis_id' => $analysisId,
                'page_id' => $pageId,
                'check_name' => $check['name'] ?? 'unknown',
                'category' => $category,
                'severity' => $severity,
                'message' => $check['message'] ?? $check['name'] ?? 'Issue detected',
                'current_value' => isset($check['current_value']) ? json_encode($check['current_value']) : null,
                'recommended_value' => isset($check['recommended_value']) ? json_encode($check['recommended_value']) : null,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Map check name to category
     */
    private function mapCategory(string $checkName): string
    {
        $checkName = strtolower($checkName);

        if (str_contains($checkName, 'title') || str_contains($checkName, 'description') || str_contains($checkName, 'meta') || str_contains($checkName, 'canonical')) {
            return self::CATEGORY_META;
        }
        if (str_contains($checkName, 'content') || str_contains($checkName, 'word') || str_contains($checkName, 'text') || str_contains($checkName, 'heading') || str_contains($checkName, 'h1')) {
            return self::CATEGORY_CONTENT;
        }
        if (str_contains($checkName, 'image') || str_contains($checkName, 'alt') || str_contains($checkName, 'img')) {
            return self::CATEGORY_IMAGES;
        }
        if (str_contains($checkName, 'link') || str_contains($checkName, 'anchor') || str_contains($checkName, 'redirect')) {
            return self::CATEGORY_LINKS;
        }
        if (str_contains($checkName, 'speed') || str_contains($checkName, 'load') || str_contains($checkName, 'size') || str_contains($checkName, 'time') || str_contains($checkName, 'lcp') || str_contains($checkName, 'cls')) {
            return self::CATEGORY_PERFORMANCE;
        }

        return self::CATEGORY_TECHNICAL;
    }

    /**
     * Update issue status
     */
    public function updateStatus(int $id, string $status): bool
    {
        $data = ['status' => $status];

        if ($status === 'fixed') {
            $data['fixed_at'] = date('Y-m-d H:i:s');
        }

        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $data = ['status' => $status];

        if ($status === 'fixed') {
            $data['fixed_at'] = date('Y-m-d H:i:s');
        }

        return Database::execute(
            "UPDATE {$this->table} SET status = ?" . ($status === 'fixed' ? ", fixed_at = ?" : "") . " WHERE id IN ({$placeholders})",
            array_merge([$status], $status === 'fixed' ? [date('Y-m-d H:i:s')] : [], $ids)
        );
    }

    /**
     * Count issues by project
     */
    public function countByProject(int $projectId, ?string $status = null, ?string $severity = null): int
    {
        $sql = "SELECT COUNT(*) as cnt
                FROM {$this->table} i
                JOIN sop_analyses a ON i.analysis_id = a.id
                WHERE a.project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }

        if ($severity) {
            $sql .= " AND i.severity = ?";
            $params[] = $severity;
        }

        $result = Database::fetch($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Get issues grouped by check_name for project
     */
    public function getGroupedByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT
                    i.check_name,
                    i.category,
                    i.severity,
                    i.message,
                    COUNT(*) as occurrences,
                    GROUP_CONCAT(DISTINCT p.id) as page_ids,
                    GROUP_CONCAT(DISTINCT CONCAT(p.id, '|', LEFT(p.url, 200))) as pages_info
                FROM {$this->table} i
                JOIN sop_analyses a ON i.analysis_id = a.id
                JOIN sop_pages p ON i.page_id = p.id
                WHERE a.project_id = ?
                  AND a.id = (SELECT MAX(id) FROM sop_analyses WHERE page_id = i.page_id)";
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $sql .= " AND i.severity = ?";
            $params[] = $filters['severity'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND i.category = ?";
            $params[] = $filters['category'];
        }

        $sql .= " GROUP BY i.check_name, i.category, i.severity, i.message
                  ORDER BY
                    CASE i.severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END,
                    occurrences DESC";

        $results = Database::fetchAll($sql, $params);

        // Parse pages_info into structured array
        foreach ($results as &$row) {
            $pages = [];
            if (!empty($row['pages_info'])) {
                $entries = explode(',', $row['pages_info']);
                foreach ($entries as $entry) {
                    $parts = explode('|', $entry, 2);
                    if (count($parts) === 2) {
                        $pages[] = [
                            'id' => (int) $parts[0],
                            'url' => $parts[1],
                        ];
                    }
                }
            }
            $row['pages'] = array_slice($pages, 0, 5); // Limit to 5 pages for display
            $row['has_more'] = count($pages) > 5;
            unset($row['pages_info'], $row['page_ids']);
        }

        return $results;
    }

    /**
     * Get project stats for issues
     */
    public function getProjectStats(int $projectId): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN i.severity = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN i.severity = 'warning' THEN 1 ELSE 0 END) as warning,
                    SUM(CASE WHEN i.severity = 'notice' THEN 1 ELSE 0 END) as notice,
                    SUM(CASE WHEN i.status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN i.status = 'fixed' THEN 1 ELSE 0 END) as fixed,
                    SUM(CASE WHEN i.status = 'ignored' THEN 1 ELSE 0 END) as ignored
                FROM {$this->table} i
                JOIN sop_analyses a ON i.analysis_id = a.id
                WHERE a.project_id = ?
                  AND a.id = (SELECT MAX(id) FROM sop_analyses WHERE page_id = i.page_id)";

        $result = Database::fetch($sql, [$projectId]);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'critical' => (int) ($result['critical'] ?? 0),
            'warning' => (int) ($result['warning'] ?? 0),
            'notice' => (int) ($result['notice'] ?? 0),
            'open' => (int) ($result['open'] ?? 0),
            'fixed' => (int) ($result['fixed'] ?? 0),
            'ignored' => (int) ($result['ignored'] ?? 0),
        ];
    }

    /**
     * Bulk update by check name for project
     */
    public function bulkUpdateByCheckName(int $projectId, string $checkName, string $status): int
    {
        $data = ['status' => $status];
        $now = date('Y-m-d H:i:s');

        if ($status === 'fixed') {
            $sql = "UPDATE {$this->table} i
                    JOIN sop_analyses a ON i.analysis_id = a.id
                    SET i.status = ?, i.fixed_at = ?
                    WHERE a.project_id = ? AND i.check_name = ?
                      AND a.id = (SELECT MAX(a2.id) FROM sop_analyses a2 WHERE a2.page_id = i.page_id)";
            return Database::execute($sql, [$status, $now, $projectId, $checkName]);
        } else {
            $sql = "UPDATE {$this->table} i
                    JOIN sop_analyses a ON i.analysis_id = a.id
                    SET i.status = ?
                    WHERE a.project_id = ? AND i.check_name = ?
                      AND a.id = (SELECT MAX(a2.id) FROM sop_analyses a2 WHERE a2.page_id = i.page_id)";
            return Database::execute($sql, [$status, $projectId, $checkName]);
        }
    }
}
