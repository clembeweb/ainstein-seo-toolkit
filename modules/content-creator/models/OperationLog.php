<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * OperationLog Model for Content Creator Module
 *
 * Logging delle operazioni (scrape, ai_generate, cms_push, export)
 * con tracciamento crediti utilizzati.
 */
class OperationLog
{
    protected string $table = 'cc_operations_log';

    /**
     * Registra una nuova operazione
     */
    public function log(array $data): int
    {
        return Database::insert($this->table, [
            'user_id' => (int) $data['user_id'],
            'project_id' => (int) $data['project_id'],
            'url_id' => isset($data['url_id']) ? (int) $data['url_id'] : null,
            'operation' => $data['operation'],
            'credits_used' => (float) ($data['credits_used'] ?? 0),
            'status' => $data['status'] ?? 'success',
            'details' => isset($data['details']) ? json_encode($data['details']) : null,
        ]);
    }

    /**
     * Operazioni di un progetto (ordine cronologico inverso)
     */
    public function getByProject(int $projectId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Operazioni di un utente
     */
    public function getByUser(int $userId, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT ol.*, p.name as project_name
             FROM {$this->table} ol
             LEFT JOIN cc_projects p ON ol.project_id = p.id
             WHERE ol.user_id = ?
             ORDER BY ol.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Statistiche operazioni per progetto
     */
    public function getStats(int $projectId): array
    {
        $stats = Database::fetch(
            "SELECT
                COUNT(*) as total_operations,
                COALESCE(SUM(credits_used), 0) as total_credits,
                SUM(operation = 'scrape' AND status = 'success') as scrape_success,
                SUM(operation = 'scrape' AND status = 'error') as scrape_errors,
                SUM(operation = 'ai_generate' AND status = 'success') as generate_success,
                SUM(operation = 'ai_generate' AND status = 'error') as generate_errors,
                SUM(operation = 'cms_push' AND status = 'success') as push_success,
                SUM(operation = 'cms_push' AND status = 'error') as push_errors,
                SUM(operation = 'export' AND status = 'success') as export_success
             FROM {$this->table}
             WHERE project_id = ?",
            [$projectId]
        );

        return [
            'total_operations' => (int) ($stats['total_operations'] ?? 0),
            'total_credits' => round((float) ($stats['total_credits'] ?? 0), 2),
            'scrape_success' => (int) ($stats['scrape_success'] ?? 0),
            'scrape_errors' => (int) ($stats['scrape_errors'] ?? 0),
            'generate_success' => (int) ($stats['generate_success'] ?? 0),
            'generate_errors' => (int) ($stats['generate_errors'] ?? 0),
            'push_success' => (int) ($stats['push_success'] ?? 0),
            'push_errors' => (int) ($stats['push_errors'] ?? 0),
            'export_success' => (int) ($stats['export_success'] ?? 0),
        ];
    }

    /**
     * Crediti totali utilizzati da un utente per questo modulo
     */
    public function getTotalCreditsUsed(int $userId): float
    {
        $row = Database::fetch(
            "SELECT COALESCE(SUM(credits_used), 0) as total
             FROM {$this->table}
             WHERE user_id = ?",
            [$userId]
        );
        return round((float) ($row['total'] ?? 0), 2);
    }
}
