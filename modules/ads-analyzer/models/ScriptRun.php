<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class ScriptRun
{
    public static function create(array $data): int
    {
        return Database::insert('ga_script_runs', [
            'project_id' => $data['project_id'],
            'run_type' => $data['run_type'],
            'status' => $data['status'] ?? 'received',
            'items_received' => $data['items_received'] ?? 0,
            'script_version' => $data['script_version'] ?? null,
            'date_range_start' => $data['date_range_start'] ?? null,
            'date_range_end' => $data['date_range_end'] ?? null,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_script_runs WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_script_runs', $data, 'id = ?', [$id]) > 0;
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $data = ['status' => $status];
        if ($error !== null) {
            $data['error_message'] = $error;
        }
        return Database::update('ga_script_runs', $data, 'id = ?', [$id]) > 0;
    }

    public static function getByProject(int $projectId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_script_runs WHERE project_id = ? ORDER BY created_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    public static function getRecentByProject(int $projectId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_script_runs WHERE project_id = ? ORDER BY created_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    public static function countRecentByProject(int $projectId, int $hours = 1): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_script_runs WHERE project_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)",
            [$projectId, $hours]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function countByProject(int $projectId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_script_runs WHERE project_id = ?",
            [$projectId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function getStatsByUser(int $userId): array
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as total_runs,
                    SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_runs,
                    SUM(r.items_received) as total_items
             FROM ga_script_runs r
             JOIN ga_projects p ON r.project_id = p.id
             WHERE p.user_id = ?",
            [$userId]
        );
        return [
            'total_runs' => (int) ($result['total_runs'] ?? 0),
            'completed_runs' => (int) ($result['completed_runs'] ?? 0),
            'total_items' => (int) ($result['total_items'] ?? 0),
        ];
    }

    public static function getLatestByProject(int $projectId, ?string $runType = null): ?array
    {
        $sql = "SELECT * FROM ga_script_runs WHERE project_id = ? AND status = 'completed'";
        $params = [$projectId];

        if ($runType) {
            $sql .= " AND (run_type = ? OR run_type = 'both')";
            $params[] = $runType;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 1";

        return Database::fetch($sql, $params) ?: null;
    }
}
