<?php
namespace Modules\AiContent\Models;

use Core\Database;

class AutoConfig
{
    private $db;
    private $table = 'aic_auto_config';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Trova config per progetto
     */
    public function findByProject(int $projectId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        $config = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $config ?: null;
    }

    /**
     * Crea config per progetto
     */
    public function create(int $projectId, array $data = []): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (project_id, is_active, auto_publish, wp_site_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $projectId,
            (int) ($data['is_active'] ?? 1),
            (int) ($data['auto_publish'] ?? 0),
            $data['wp_site_id'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Aggiorna config
     */
    public function update(int $projectId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                is_active = ?,
                auto_publish = ?,
                wp_site_id = ?,
                updated_at = NOW()
            WHERE project_id = ?
        ");
        return $stmt->execute([
            (int) ($data['is_active'] ?? 1),
            (int) ($data['auto_publish'] ?? 0),
            $data['wp_site_id'] ?? null,
            $projectId
        ]);
    }

    /**
     * Crea o aggiorna (upsert)
     */
    public function upsert(int $projectId, array $data): bool
    {
        $existing = $this->findByProject($projectId);
        if ($existing) {
            return $this->update($projectId, $data);
        } else {
            return $this->create($projectId, $data) > 0;
        }
    }

    /**
     * Attiva/disattiva automazione
     */
    public function toggle(int $projectId, bool $active): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET is_active = ?, updated_at = NOW()
            WHERE project_id = ?
        ");
        return $stmt->execute([$active, $projectId]);
    }

    /**
     * Elimina config (cascade da FK, ma metodo esplicito)
     */
    public function delete(int $projectId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE project_id = ?");
        return $stmt->execute([$projectId]);
    }

    /**
     * Update last run timestamp
     */
    public function updateLastRun(int $projectId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET last_run_at = NOW()
            WHERE project_id = ?
        ");
        return $stmt->execute([$projectId]);
    }

    /**
     * Get all active projects that have pending queue items ready to process
     *
     * Ritorna progetti attivi che hanno item in aic_queue con:
     * - scheduled_at <= NOW()
     * - status = 'pending'
     */
    public function getProjectsToRun(): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                ac.*,
                p.name as project_name,
                p.user_id
            FROM {$this->table} ac
            JOIN aic_projects p ON p.id = ac.project_id
            JOIN aic_queue q ON q.project_id = ac.project_id
            WHERE ac.is_active = 1
              AND p.type = 'auto'
              AND q.status = 'pending'
              AND q.scheduled_at <= NOW()
        ");
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
