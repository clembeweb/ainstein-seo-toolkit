<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * AiSuggestion Model for SEO Onpage Optimizer Module
 * Manages sop_ai_suggestions table
 */
class AiSuggestion
{
    protected string $table = 'sop_ai_suggestions';

    // Types
    public const TYPE_TITLE = 'title';
    public const TYPE_DESCRIPTION = 'description';
    public const TYPE_H1 = 'h1';
    public const TYPE_CONTENT = 'content';
    public const TYPE_TECHNICAL = 'technical';
    public const TYPE_OVERALL = 'overall';

    // Priorities
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    /**
     * Find suggestion by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get all suggestions for analysis
     */
    public function allByAnalysis(int $analysisId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE analysis_id = ? ORDER BY priority ASC, suggestion_type ASC",
            [$analysisId]
        );
    }

    /**
     * Get all suggestions for page
     */
    public function allByPage(int $pageId, array $filters = []): array
    {
        $sql = "SELECT s.*, a.created_at as analysis_date, a.onpage_score
                FROM {$this->table} s
                JOIN sop_analyses a ON s.analysis_id = a.id
                WHERE s.page_id = ?";
        $params = [$pageId];

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND s.suggestion_type = ?";
            $params[] = $filters['type'];
        }

        $sql .= " ORDER BY s.priority ASC, s.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get all suggestions for project
     */
    public function allByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT s.*, p.url, a.onpage_score
                FROM {$this->table} s
                JOIN sop_analyses a ON s.analysis_id = a.id
                JOIN sop_pages p ON s.page_id = p.id
                WHERE a.project_id = ?";
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND s.priority = ?";
            $params[] = $filters['priority'];
        }

        // Only latest analysis by default
        if (empty($filters['all_analyses'])) {
            $sql .= " AND a.id = (SELECT MAX(id) FROM sop_analyses WHERE page_id = s.page_id)";
        }

        $sql .= " ORDER BY s.priority ASC, s.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create suggestion
     */
    public function create(array $data): int
    {
        $data['status'] = $data['status'] ?? 'pending';
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Bulk create suggestions
     */
    public function createBulk(int $analysisId, int $pageId, array $suggestions, float $creditsUsed = 0): int
    {
        $count = 0;
        $creditsPerSuggestion = count($suggestions) > 0 ? $creditsUsed / count($suggestions) : 0;

        foreach ($suggestions as $suggestion) {
            $this->create([
                'analysis_id' => $analysisId,
                'page_id' => $pageId,
                'suggestion_type' => $suggestion['suggestion_type'] ?? $suggestion['type'] ?? self::TYPE_OVERALL,
                'priority' => $suggestion['priority'] ?? self::PRIORITY_MEDIUM,
                'current_value' => $suggestion['current_value'] ?? null,
                'suggested_value' => $suggestion['suggested_value'] ?? null,
                'reasoning' => $suggestion['reasoning'] ?? null,
                'credits_used' => $creditsPerSuggestion,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Mark as applied
     */
    public function markApplied(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'applied',
            'applied_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark as rejected
     */
    public function markRejected(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'rejected',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Count suggestions by status for project
     */
    public function countByProject(int $projectId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) as cnt
                FROM {$this->table} s
                JOIN sop_analyses a ON s.analysis_id = a.id
                WHERE a.project_id = ?";
        $params = [$projectId];

        if ($status) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }

        $result = Database::fetch($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Get pending count for page
     */
    public function countPendingForPage(int $pageId): int
    {
        return Database::count($this->table, 'page_id = ? AND status = ?', [$pageId, 'pending']);
    }

    /**
     * Delete suggestions for analysis
     */
    public function deleteByAnalysis(int $analysisId): int
    {
        return Database::delete($this->table, 'analysis_id = ?', [$analysisId]);
    }
}
