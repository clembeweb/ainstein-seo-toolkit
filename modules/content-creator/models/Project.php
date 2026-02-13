<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * Project Model for Content Creator Module
 * Manages cc_projects table
 */
class Project
{
    protected string $table = 'cc_projects';

    /**
     * Trova progetto per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova progetto per ID con verifica utente
     */
    public function findByUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Tutti i progetti di un utente
     */
    public function allByUser(int $userId, ?string $status = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Tutti i progetti con statistiche URL
     */
    public function allWithStats(int $userId): array
    {
        return Database::fetchAll(
            "SELECT p.*,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id) as total_urls,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id AND status = 'pending') as pending_urls,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id AND status = 'scraped') as scraped_urls,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id AND status = 'generated') as generated_urls,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id AND status = 'approved') as approved_urls,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id AND status = 'published') as published_urls,
                (SELECT COUNT(*) FROM cc_urls WHERE project_id = p.id AND status = 'error') as error_urls
             FROM {$this->table} p
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC",
            [$userId]
        );
    }

    /**
     * Crea nuovo progetto
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, [
            'user_id' => (int) $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_url' => $data['base_url'] ?? null,
            'content_type' => $data['content_type'] ?? 'product',
            'language' => $data['language'] ?? 'it',
            'tone' => $data['tone'] ?? 'professionale',
            'ai_settings' => isset($data['ai_settings']) ? json_encode($data['ai_settings']) : null,
            'connector_id' => $data['connector_id'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina progetto
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Statistiche URL per progetto
     */
    public function getStats(int $projectId): array
    {
        $stats = Database::fetch(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status = 'scraped') as scraped,
                SUM(status = 'generated') as `generated`,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(status = 'published') as published,
                SUM(status = 'error') as errors
             FROM cc_urls
             WHERE project_id = ?",
            [$projectId]
        );

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'scraped' => (int) ($stats['scraped'] ?? 0),
            'generated' => (int) ($stats['generated'] ?? 0),
            'approved' => (int) ($stats['approved'] ?? 0),
            'rejected' => (int) ($stats['rejected'] ?? 0),
            'published' => (int) ($stats['published'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * Verifica che il progetto appartenga all'utente
     */
    public function belongsToUser(int $projectId, int $userId): bool
    {
        return $this->findByUser($projectId, $userId) !== null;
    }

    /**
     * Conta progetti per utente
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }
}
