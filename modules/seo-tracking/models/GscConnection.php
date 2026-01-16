<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * GscConnection Model
 * Gestisce la tabella st_gsc_connections (OAuth tokens GSC)
 */
class GscConnection
{
    protected string $table = 'st_gsc_connections';

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
     * Alias di findByProject per compatibilità con GscService
     */
    public function getByProject(int $projectId): ?array
    {
        return $this->findByProject($projectId);
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
     * Aggiorna tokens
     */
    public function updateTokens(int $projectId, string $accessToken, string $refreshToken, int $expiresAt): void
    {
        Database::update($this->table, [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => date('Y-m-d H:i:s', $expiresAt),
        ], 'project_id = ?', [$projectId]);
    }

    /**
     * Aggiorna solo access token
     */
    public function updateAccessToken(int $projectId, string $accessToken, int $expiresAt): void
    {
        Database::update($this->table, [
            'access_token' => $accessToken,
            'token_expires_at' => date('Y-m-d H:i:s', $expiresAt),
        ], 'project_id = ?', [$projectId]);
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
     * Upsert connessione (INSERT o UPDATE basato su project_id)
     */
    public function upsert(int $projectId, array $data): array
    {
        $existing = $this->findByProject($projectId);

        if ($existing) {
            // UPDATE
            $this->update($projectId, $data);
            return array_merge($existing, $data);
        } else {
            // INSERT
            $insertData = array_merge(['project_id' => $projectId], $data);
            $id = $this->create($insertData);
            return array_merge(['id' => $id, 'project_id' => $projectId], $data);
        }
    }

    /**
     * Aggiorna site_url/property_url
     */
    public function updateSiteUrl(int $projectId, string $siteUrl): bool
    {
        return $this->update($projectId, ['property_url' => $siteUrl]);
    }

    /**
     * Elimina connessione
     */
    public function delete(int $projectId): bool
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]) > 0;
    }

    /**
     * Verifica se token è scaduto
     */
    public function isTokenExpired(array $connection): bool
    {
        if (empty($connection['token_expires_at'])) {
            return true;
        }

        $expiresAt = strtotime($connection['token_expires_at']);
        return $expiresAt <= time() + 300; // 5 minuti di margine
    }

    /**
     * Imposta proprietà selezionata
     */
    public function setProperty(int $projectId, string $propertyUrl, string $propertyType): void
    {
        Database::update($this->table, [
            'property_url' => $propertyUrl,
            'property_type' => $propertyType,
        ], 'project_id = ?', [$projectId]);
    }

    /**
     * Attiva/disattiva connessione
     */
    public function setActive(int $projectId, bool $active): void
    {
        Database::update($this->table, ['is_active' => $active ? 1 : 0], 'project_id = ?', [$projectId]);
    }

    /**
     * Connessioni attive con token validi
     */
    public function getActiveConnections(): array
    {
        return Database::fetchAll(
            "SELECT gc.*, p.name as project_name, p.domain, p.user_id
             FROM {$this->table} gc
             JOIN st_projects p ON gc.project_id = p.id
             WHERE gc.is_active = 1 AND p.sync_enabled = 1"
        );
    }
}
