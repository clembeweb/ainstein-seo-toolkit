<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * Project Model for SEO Onpage Optimizer Module
 * Manages sop_projects table
 */
class Project
{
    protected string $table = 'sop_projects';

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
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY updated_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Get all projects with stats
     */
    public function allWithStats(int $userId): array
    {
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM sop_pages WHERE project_id = p.id) as pages_count,
                (SELECT COUNT(*) FROM sop_pages WHERE project_id = p.id AND status = 'completed') as pages_analyzed,
                (SELECT AVG(onpage_score) FROM sop_pages WHERE project_id = p.id AND onpage_score IS NOT NULL) as avg_score,
                (SELECT COUNT(*) FROM sop_issues i
                    JOIN sop_analyses a ON i.analysis_id = a.id
                    WHERE a.project_id = p.id AND i.status = 'open' AND i.severity = 'critical') as critical_issues,
                (SELECT MAX(a.created_at) FROM sop_analyses a WHERE a.project_id = p.id) as last_audit_at
            FROM {$this->table} p
            WHERE p.user_id = ?
            ORDER BY p.updated_at DESC
        ";

        return Database::fetchAll($sql, [$userId]);
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
        // Pages stats
        $pageStats = Database::fetch("
            SELECT
                COUNT(*) as total_pages,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                AVG(onpage_score) as avg_score,
                MIN(onpage_score) as min_score,
                MAX(onpage_score) as max_score
            FROM sop_pages
            WHERE project_id = ?
        ", [$projectId]);

        // Issues stats
        $issueStats = Database::fetch("
            SELECT
                COUNT(*) as total_issues,
                SUM(CASE WHEN severity = 'critical' AND status = 'open' THEN 1 ELSE 0 END) as critical_open,
                SUM(CASE WHEN severity = 'warning' AND status = 'open' THEN 1 ELSE 0 END) as warning_open,
                SUM(CASE WHEN severity = 'notice' AND status = 'open' THEN 1 ELSE 0 END) as notice_open,
                SUM(CASE WHEN status = 'fixed' THEN 1 ELSE 0 END) as fixed
            FROM sop_issues i
            JOIN sop_analyses a ON i.analysis_id = a.id
            WHERE a.project_id = ?
        ", [$projectId]);

        // Last audit
        $lastAudit = Database::fetch("
            SELECT created_at FROM sop_analyses
            WHERE project_id = ?
            ORDER BY created_at DESC LIMIT 1
        ", [$projectId]);

        return [
            'pages_total' => (int) ($pageStats['total_pages'] ?? 0),
            'pages_pending' => (int) ($pageStats['pending'] ?? 0),
            'pages_completed' => (int) ($pageStats['completed'] ?? 0),
            'pages_errors' => (int) ($pageStats['errors'] ?? 0),
            'avg_score' => $pageStats['avg_score'] ? round($pageStats['avg_score'], 1) : null,
            'min_score' => $pageStats['min_score'] ? (int) $pageStats['min_score'] : null,
            'max_score' => $pageStats['max_score'] ? (int) $pageStats['max_score'] : null,
            'issues_total' => (int) ($issueStats['total_issues'] ?? 0),
            'issues_critical' => (int) ($issueStats['critical_open'] ?? 0),
            'issues_warning' => (int) ($issueStats['warning_open'] ?? 0),
            'issues_notice' => (int) ($issueStats['notice_open'] ?? 0),
            'issues_fixed' => (int) ($issueStats['fixed'] ?? 0),
            'last_audit_at' => $lastAudit['created_at'] ?? null,
        ];
    }

    /**
     * Create new project
     */
    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
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
     * Delete project
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
