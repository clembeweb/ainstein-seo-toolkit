<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * SyncLog Model
 * Gestisce la tabella st_sync_log
 */
class SyncLog
{
    protected string $table = 'st_sync_log';

    /**
     * Crea log sync
     */
    public function create(array $data): int
    {
        $data['started_at'] = $data['started_at'] ?? date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Aggiorna log con risultato
     */
    public function complete(int $id, array $result): void
    {
        $startedAt = Database::fetch("SELECT started_at FROM {$this->table} WHERE id = ?", [$id]);
        $duration = null;

        if ($startedAt) {
            $duration = time() - strtotime($startedAt['started_at']);
        }

        Database::update($this->table, [
            'status' => $result['status'] ?? 'completed',
            'records_fetched' => $result['records_fetched'] ?? 0,
            'records_inserted' => $result['records_inserted'] ?? 0,
            'records_updated' => $result['records_updated'] ?? 0,
            'error_message' => $result['error_message'] ?? null,
            'completed_at' => date('Y-m-d H:i:s'),
            'duration_seconds' => $duration,
        ], 'id = ?', [$id]);
    }

    /**
     * Marca come fallito
     */
    public function fail(int $id, string $errorMessage): void
    {
        $this->complete($id, [
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Log per progetto
     */
    public function getByProject(int $projectId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ?
             ORDER BY started_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Ultimo sync per progetto
     */
    public function getLastSync(int $projectId, ?string $syncType = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($syncType) {
            $sql .= " AND sync_type = ?";
            $params[] = $syncType;
        }

        $sql .= " ORDER BY started_at DESC LIMIT 1";

        return Database::fetch($sql, $params);
    }

    /**
     * Ultimo sync completato
     */
    public function getLastCompletedSync(int $projectId, ?string $syncType = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND status = 'completed'";
        $params = [$projectId];

        if ($syncType) {
            $sql .= " AND sync_type = ?";
            $params[] = $syncType;
        }

        $sql .= " ORDER BY completed_at DESC LIMIT 1";

        return Database::fetch($sql, $params);
    }

    /**
     * Sync in corso
     */
    public function getRunning(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND status = 'running'
             ORDER BY started_at DESC
             LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Conta sync per tipo nel periodo
     */
    public function countByTypeInPeriod(int $projectId, string $syncType, string $startDate, string $endDate): int
    {
        return Database::count(
            $this->table,
            'project_id = ? AND sync_type = ? AND DATE(started_at) BETWEEN ? AND ?',
            [$projectId, $syncType, $startDate, $endDate]
        );
    }

    /**
     * Elimina log vecchi
     */
    public function deleteOld(int $projectId, int $daysToKeep = 30): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));

        $stmt = Database::query(
            "DELETE FROM {$this->table} WHERE project_id = ? AND started_at < ?",
            [$projectId, $cutoffDate]
        );

        return $stmt->rowCount();
    }
}
