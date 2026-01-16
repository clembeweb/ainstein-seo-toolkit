<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Analysis
{
    public static function create(array $data): int
    {
        return Database::insert('ga_analyses', [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'business_context' => $data['business_context'] ?? null,
            'context_mode' => $data['context_mode'] ?? 'manual',
            'status' => 'draft'
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_analyses WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function findByUserAndId(int $userId, int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_analyses WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) ?: null;
    }

    public static function findByProjectId(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_analyses WHERE project_id = ? ORDER BY created_at DESC",
            [$projectId]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_analyses', $data, 'id = ?', [$id]) >= 0;
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_analyses', 'id = ?', [$id]) > 0;
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $data = ['status' => $status];

        if ($status === 'analyzing') {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'error' && $error) {
            $data['error_message'] = $error;
        }

        return self::update($id, $data);
    }

    public static function updateStats(int $id): bool
    {
        $stats = Database::fetch(
            "SELECT
                COUNT(DISTINCT nc.id) as total_categories,
                COUNT(DISTINCT nk.id) as total_keywords
             FROM ga_analyses a
             LEFT JOIN ga_negative_categories nc ON nc.analysis_id = a.id
             LEFT JOIN ga_negative_keywords nk ON nk.analysis_id = a.id
             WHERE a.id = ?",
            [$id]
        );

        return self::update($id, [
            'total_categories' => $stats['total_categories'] ?? 0,
            'total_keywords' => $stats['total_keywords'] ?? 0
        ]);
    }

    public static function getLatestByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_analyses WHERE project_id = ? ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        ) ?: null;
    }

    public static function countByProject(int $projectId): int
    {
        return Database::count('ga_analyses', 'project_id = ?', [$projectId]);
    }

    public static function getCompletedByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_analyses WHERE project_id = ? AND status = 'completed' ORDER BY created_at DESC",
            [$projectId]
        );
    }
}
