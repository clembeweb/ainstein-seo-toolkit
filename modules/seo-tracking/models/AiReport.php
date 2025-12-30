<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * AiReport Model
 * Gestisce la tabella st_ai_reports
 */
class AiReport
{
    protected string $table = 'st_ai_reports';

    /**
     * Trova report per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Report per progetto
     */
    public function getByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (!empty($filters['type'])) {
            $sql .= " AND report_type = ?";
            $params[] = $filters['type'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Ultimi report per tipo
     */
    public function getLatestByType(int $projectId, string $type, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND report_type = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $type, $limit]
        );
    }

    /**
     * Crea report
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Aggiorna report
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Marca come inviato via email
     */
    public function markEmailSent(int $id, array $recipients): void
    {
        Database::update($this->table, [
            'email_sent' => 1,
            'email_sent_at' => date('Y-m-d H:i:s'),
            'email_recipients' => json_encode($recipients),
        ], 'id = ?', [$id]);
    }

    /**
     * Elimina report
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Conta report per utente nel mese
     */
    public function countByUserThisMonth(int $userId): int
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table} r
            JOIN st_projects p ON r.project_id = p.id
            WHERE p.user_id = ?
              AND MONTH(r.created_at) = MONTH(CURDATE())
              AND YEAR(r.created_at) = YEAR(CURDATE())
        ";

        $result = Database::fetch($sql, [$userId]);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Totale crediti usati nel mese
     */
    public function getCreditsUsedThisMonth(int $projectId): int
    {
        $sql = "
            SELECT COALESCE(SUM(credits_used), 0) as total
            FROM {$this->table}
            WHERE project_id = ?
              AND MONTH(created_at) = MONTH(CURDATE())
              AND YEAR(created_at) = YEAR(CURDATE())
        ";

        $result = Database::fetch($sql, [$projectId]);
        return (int) ($result['total'] ?? 0);
    }
}
