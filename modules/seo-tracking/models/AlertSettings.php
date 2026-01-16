<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * AlertSettings Model
 * Gestisce la tabella st_alert_settings
 */
class AlertSettings
{
    protected string $table = 'st_alert_settings';

    /**
     * Trova settings per progetto
     */
    public function findByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ?",
            [$projectId]
        );
    }

    /**
     * Crea settings default
     */
    public function create(int $projectId): int
    {
        return Database::insert($this->table, ['project_id' => $projectId]);
    }

    /**
     * Aggiorna settings
     */
    public function update(int $projectId, array $data): bool
    {
        return Database::update($this->table, $data, 'project_id = ?', [$projectId]) > 0;
    }

    /**
     * Crea o aggiorna
     */
    public function upsert(int $projectId, array $data): void
    {
        $existing = $this->findByProject($projectId);

        if ($existing) {
            $this->update($projectId, $data);
        } else {
            $data['project_id'] = $projectId;
            Database::insert($this->table, $data);
        }
    }

    /**
     * Elimina settings
     */
    public function delete(int $projectId): bool
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]) > 0;
    }
}
