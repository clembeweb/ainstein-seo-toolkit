<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Alert Model
 * Gestisce la tabella st_alerts
 */
class Alert
{
    protected string $table = 'st_alerts';

    /**
     * Trova alert per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Alert per progetto
     */
    public function getByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND alert_type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['severity'])) {
            $sql .= " AND severity = ?";
            $params[] = $filters['severity'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Alert nuovi (non letti)
     */
    public function getNew(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND status = 'new'
             ORDER BY severity = 'critical' DESC, created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Conta alert nuovi
     */
    public function countNew(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ? AND status = ?', [$projectId, 'new']);
    }

    /**
     * Crea alert
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Crea multipli alert
     */
    public function createMany(array $alerts): int
    {
        $count = 0;
        foreach ($alerts as $alert) {
            $this->create($alert);
            $count++;
        }
        return $count;
    }

    /**
     * Marca come letto
     */
    public function markRead(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'read',
            'read_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Marca come gestito
     */
    public function markActioned(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'actioned',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Ignora alert
     */
    public function dismiss(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'dismissed',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna stato email
     */
    public function markEmailSent(int $id): void
    {
        Database::update($this->table, [
            'email_sent' => 1,
            'email_sent_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * Alert non inviati per email
     */
    public function getPendingEmail(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND email_sent = 0 AND status = 'new'
             ORDER BY created_at ASC",
            [$projectId]
        );
    }

    /**
     * Elimina alert vecchi
     */
    public function deleteOld(int $projectId, int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));

        $stmt = Database::query(
            "DELETE FROM {$this->table}
             WHERE project_id = ? AND created_at < ? AND status IN ('read', 'dismissed', 'actioned')",
            [$projectId, $cutoffDate]
        );

        return $stmt->rowCount();
    }

    /**
     * Storico alert per tipo
     */
    public function getHistoryByType(int $projectId, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT
                alert_type,
                severity,
                COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ? AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY alert_type, severity
            ORDER BY count DESC
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate]);
    }

    /**
     * Conta alert non letti (alias di countNew)
     */
    public function countUnread(int $projectId): int
    {
        return $this->countNew($projectId);
    }

    /**
     * Alert recenti (alias di getNew per compatibilita)
     */
    public function getRecent(int $projectId, int $limit = 5): array
    {
        return $this->getNew($projectId, $limit);
    }

    /**
     * Statistiche alert per progetto
     */
    public function getStats(int $projectId): array
    {
        $total = Database::count($this->table, 'project_id = ?', [$projectId]);
        $unread = Database::count($this->table, 'project_id = ? AND status = ?', [$projectId, 'new']);
        $critical = Database::count($this->table, 'project_id = ? AND severity = ? AND status = ?', [$projectId, 'critical', 'new']);
        $high = Database::count($this->table, 'project_id = ? AND severity = ? AND status = ?', [$projectId, 'high', 'new']);
        $medium = Database::count($this->table, 'project_id = ? AND severity = ? AND status = ?', [$projectId, 'medium', 'new']);

        return [
            'total' => $total,
            'unread' => $unread,
            'critical' => $critical,
            'high' => $high,
            'medium' => $medium,
        ];
    }

    /**
     * Marca tutti come letti
     */
    public function markAllRead(int $projectId): int
    {
        $stmt = Database::query(
            "UPDATE {$this->table} SET status = 'read', read_at = NOW() WHERE project_id = ? AND status = 'new'",
            [$projectId]
        );

        return $stmt->rowCount();
    }
}
