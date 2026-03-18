<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Sync
{
    public static function create(array $data): int
    {
        return Database::insert('ga_syncs', [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'sync_type' => $data['sync_type'],
            'status' => $data['status'] ?? 'pending',
            'date_range_start' => $data['date_range_start'] ?? null,
            'date_range_end' => $data['date_range_end'] ?? null,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_syncs WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_syncs', $data, 'id = ?', [$id]) > 0;
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $data = ['status' => $status];
        if ($error !== null) {
            $data['error_message'] = $error;
        }
        if ($status === 'completed' || $status === 'error') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        return Database::update('ga_syncs', $data, 'id = ?', [$id]) > 0;
    }

    public static function updateCounts(int $id, array $counts): bool
    {
        $data = [];
        $allowed = ['campaigns_synced', 'ad_groups_synced', 'keywords_synced', 'ads_synced', 'search_terms_synced', 'extensions_synced', 'asset_groups_synced', 'assets_synced'];
        foreach ($allowed as $field) {
            if (isset($counts[$field])) {
                $data[$field] = (int) $counts[$field];
            }
        }
        if (empty($data)) {
            return false;
        }
        return Database::update('ga_syncs', $data, 'id = ?', [$id]) > 0;
    }

    public static function getByProject(int $projectId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_syncs WHERE project_id = ? ORDER BY started_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    public static function getLatestByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_syncs WHERE project_id = ? AND status = 'completed' ORDER BY started_at DESC LIMIT 1",
            [$projectId]
        ) ?: null;
    }

    public static function getCompletedSyncs(int $projectId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_syncs WHERE project_id = ? AND status = 'completed' ORDER BY started_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    public static function getRunningSync(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_syncs WHERE project_id = ? AND status = 'running' LIMIT 1",
            [$projectId]
        ) ?: null;
    }

    public static function findPreviousCompleted(int $projectId, int $currentSyncId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_syncs WHERE project_id = ? AND id < ? AND status = 'completed' ORDER BY id DESC LIMIT 1",
            [$projectId, $currentSyncId]
        ) ?: null;
    }
}
