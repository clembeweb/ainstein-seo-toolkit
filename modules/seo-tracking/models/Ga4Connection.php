<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Ga4Connection Model
 * Gestisce la tabella st_ga4_connections (Service Account GA4)
 */
class Ga4Connection
{
    protected string $table = 'st_ga4_connections';

    /**
     * Trova connessione per progetto
     */
    public function findByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ?",
            [$projectId]
        );
    }

    /**
     * Trova connessione senza JSON sensibile
     */
    public function findByProjectSafe(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT id, project_id, property_id, property_name, is_active,
                    last_sync_at, last_error, created_at, updated_at
             FROM {$this->table} WHERE project_id = ?",
            [$projectId]
        );
    }

    /**
     * Crea connessione
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Aggiorna connessione
     */
    public function update(int $projectId, array $data): bool
    {
        return Database::update($this->table, $data, 'project_id = ?', [$projectId]) > 0;
    }

    /**
     * Aggiorna Service Account JSON
     */
    public function updateServiceAccount(int $projectId, string $encryptedJson): void
    {
        Database::update($this->table, [
            'service_account_json' => $encryptedJson,
        ], 'project_id = ?', [$projectId]);
    }

    /**
     * Imposta proprietà GA4
     */
    public function setProperty(int $projectId, string $propertyId, ?string $propertyName = null): void
    {
        $data = ['property_id' => $propertyId];

        if ($propertyName !== null) {
            $data['property_name'] = $propertyName;
        }

        Database::update($this->table, $data, 'project_id = ?', [$projectId]);
    }

    /**
     * Aggiorna last sync
     */
    public function updateLastSync(int $projectId, ?string $error = null): void
    {
        $data = ['last_sync_at' => date('Y-m-d H:i:s')];

        if ($error !== null) {
            $data['last_error'] = $error;
        } else {
            $data['last_error'] = null;
        }

        Database::update($this->table, $data, 'project_id = ?', [$projectId]);
    }

    /**
     * Elimina connessione
     */
    public function delete(int $projectId): bool
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]) > 0;
    }

    /**
     * Attiva/disattiva connessione
     */
    public function setActive(int $projectId, bool $active): void
    {
        Database::update($this->table, ['is_active' => $active ? 1 : 0], 'project_id = ?', [$projectId]);
    }

    /**
     * Connessioni attive
     */
    public function getActiveConnections(): array
    {
        return Database::fetchAll(
            "SELECT ga4.*, p.name as project_name, p.domain, p.user_id
             FROM {$this->table} ga4
             JOIN st_projects p ON ga4.project_id = p.id
             WHERE ga4.is_active = 1 AND p.sync_enabled = 1"
        );
    }
}
