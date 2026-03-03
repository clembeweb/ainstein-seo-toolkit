<?php

namespace Modules\InternalLinks\Models;

use Core\Database;

/**
 * Suggestion Model
 *
 * Manages il_link_suggestions table — AI-powered link suggestions
 */
class Suggestion
{
    protected string $table = 'il_link_suggestions';

    /**
     * Find suggestion by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Find suggestion with joined URL data
     */
    public function findWithUrls(int $id, int $projectId): ?array
    {
        $sql = "
            SELECT s.*,
                   src.url as source_url, src.keyword as source_keyword,
                   dst.url as destination_url, dst.keyword as destination_keyword
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE s.id = ? AND s.project_id = ?
        ";
        return Database::fetch($sql, [$id, $projectId]);
    }

    /**
     * Get suggestions for a project with pagination and filters
     */
    public function getByProject(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where = ['s.project_id = ?'];
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['reason'])) {
            $where[] = 's.reason = ?';
            $params[] = $filters['reason'];
        }

        if (!empty($filters['min_score'])) {
            $where[] = 's.total_score >= ?';
            $params[] = (int) $filters['min_score'];
        }

        if (!empty($filters['confidence'])) {
            $where[] = 's.ai_confidence = ?';
            $params[] = $filters['confidence'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(src.url LIKE ? OR dst.url LIKE ? OR src.keyword LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countSql = "
            SELECT COUNT(*) as cnt
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE {$whereStr}
        ";
        $total = (int) Database::fetch($countSql, $params)['cnt'];

        $sql = "
            SELECT s.*,
                   src.url as source_url, src.keyword as source_keyword,
                   dst.url as destination_url, dst.keyword as destination_keyword
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE {$whereStr}
            ORDER BY s.total_score DESC, s.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;

        return [
            'data' => Database::fetchAll($sql, $params),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
        ];
    }

    /**
     * Bulk insert suggestions from deterministic phase
     */
    public function bulkInsert(int $projectId, array $suggestions): int
    {
        if (empty($suggestions)) return 0;

        $inserted = 0;
        foreach ($suggestions as $s) {
            try {
                Database::insert($this->table, [
                    'project_id' => $projectId,
                    'source_url_id' => $s['source_url_id'],
                    'destination_url_id' => $s['destination_url_id'],
                    'keyword_score' => $s['keyword_score'],
                    'category_bonus' => $s['category_bonus'],
                    'total_score' => $s['total_score'],
                    'reason' => $s['reason'],
                    'status' => 'pending',
                ]);
                $inserted++;
            } catch (\Exception $e) {
                // Duplicate — skip (UNIQUE constraint)
            }
        }
        return $inserted;
    }

    /**
     * Get suggestions pending AI validation (Phase 2)
     */
    public function getPendingAiValidation(int $projectId, int $limit = 30): array
    {
        $sql = "
            SELECT s.*,
                   src.url as source_url, src.keyword as source_keyword,
                   src.content_html as source_content,
                   dst.url as destination_url, dst.keyword as destination_keyword,
                   dst.content_html as destination_content
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE s.project_id = ? AND s.status = 'pending'
            ORDER BY s.total_score DESC
            LIMIT ?
        ";
        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Update AI validation results (Phase 2)
     */
    public function updateAiValidation(int $id, array $data): bool
    {
        return Database::update($this->table, [
            'ai_relevance_score' => $data['relevance_score'] ?? null,
            'ai_suggested_anchors' => isset($data['suggested_anchors']) ? json_encode($data['suggested_anchors']) : null,
            'ai_placement_hint' => $data['placement_hint'] ?? null,
            'ai_confidence' => $data['confidence'] ?? null,
            'ai_anchor_diversity_note' => $data['anchor_diversity_note'] ?? null,
            'ai_analyzed_at' => date('Y-m-d H:i:s'),
            'status' => ($data['confidence'] ?? '') === 'low' ? 'dismissed' : 'ai_validated',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Update AI snippet (Phase 3)
     */
    public function updateSnippet(int $id, array $data): bool
    {
        return Database::update($this->table, [
            'ai_snippet_html' => $data['snippet_html'] ?? null,
            'ai_original_paragraph' => $data['original_paragraph'] ?? null,
            'ai_insertion_method' => $data['insertion_method'] ?? null,
            'ai_anchor_used' => $data['anchor_used'] ?? null,
            'ai_snippet_generated_at' => date('Y-m-d H:i:s'),
            'status' => 'snippet_ready',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark suggestion as applied
     */
    public function markApplied(int $id, string $method = 'manual_copy'): bool
    {
        return Database::update($this->table, [
            'status' => 'applied',
            'applied_at' => date('Y-m-d H:i:s'),
            'applied_method' => $method,
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark suggestion as dismissed
     */
    public function markDismissed(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'dismissed',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Get stats for a project
     */
    public function getStats(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'ai_validated' THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN status = 'snippet_ready' THEN 1 ELSE 0 END) as snippet_ready,
                SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END) as applied,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                AVG(CASE WHEN ai_relevance_score IS NOT NULL THEN ai_relevance_score END) as avg_ai_score
            FROM {$this->table}
            WHERE project_id = ?
        ";
        $result = Database::fetch($sql, [$projectId]);
        return [
            'total' => (int) ($result['total'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0),
            'validated' => (int) ($result['validated'] ?? 0),
            'snippet_ready' => (int) ($result['snippet_ready'] ?? 0),
            'applied' => (int) ($result['applied'] ?? 0),
            'dismissed' => (int) ($result['dismissed'] ?? 0),
            'actionable' => (int) ($result['validated'] ?? 0) + (int) ($result['snippet_ready'] ?? 0),
            'avg_ai_score' => $result['avg_ai_score'] ? round((float) $result['avg_ai_score'], 1) : null,
        ];
    }

    /**
     * Get anchor text distribution for the project (for diversity analysis)
     */
    public function getAnchorDistribution(int $projectId): array
    {
        // Get anchors from existing links
        $sql = "
            SELECT anchor_text_clean as anchor, COUNT(*) as count
            FROM il_internal_links
            WHERE project_id = ? AND anchor_text_clean IS NOT NULL AND anchor_text_clean != ''
            GROUP BY anchor_text_clean
            ORDER BY count DESC
            LIMIT 30
        ";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Delete all suggestions for a project (before regeneration)
     */
    public function deleteByProject(int $projectId, bool $keepApplied = true): int
    {
        $where = 'project_id = ?';
        if ($keepApplied) {
            $where .= " AND status != 'applied'";
        }
        return Database::delete($this->table, $where, [$projectId]);
    }

    /**
     * Count suggestions by project
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $ids, string $status, int $projectId): int
    {
        if (empty($ids)) return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$projectId]);

        $extra = '';
        if ($status === 'applied') {
            $extra = ", applied_at = NOW(), applied_method = 'manual_copy'";
        }

        $sql = "UPDATE {$this->table} SET status = ?{$extra} WHERE id IN ({$placeholders}) AND project_id = ?";
        array_unshift($params, $status);
        $stmt = Database::query($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }
}
