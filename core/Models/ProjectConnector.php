<?php

namespace Core\Models;

use Core\Database;

/**
 * ProjectConnector Model
 *
 * Gestisce la tabella `project_connectors` per connessioni CMS centralizzate.
 * Un connettore collega un progetto globale a un CMS esterno (WordPress, Shopify, etc.)
 * tramite API key e configurazione specifica.
 */
class ProjectConnector
{
    protected string $table = 'project_connectors';

    // ─────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────

    /**
     * Trova connettore per ID.
     */
    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Trova connettore per ID con verifica ownership utente.
     */
    public function findForUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Tutti i connettori di un progetto, ordinati per data creazione.
     */
    public function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE project_id = ? ORDER BY created_at DESC",
            [$projectId]
        );
    }

    /**
     * Connettore attivo per progetto e tipo (es. 'wordpress').
     * Ritorna il piu recente se ce ne sono piu di uno.
     */
    public function getActiveByProject(int $projectId, ?string $type = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ? AND is_active = 1";
        $params = [$projectId];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 1";

        return Database::fetch($sql, $params);
    }

    /**
     * Verifica se un progetto ha almeno un connettore attivo del tipo dato.
     */
    public function hasActiveConnector(int $projectId, string $type): bool
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE project_id = ? AND type = ? AND is_active = 1",
            [$projectId, $type]
        );

        return ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Ritorna la configurazione JSON decodificata di un connettore.
     */
    public function getConfig(int $id): array
    {
        $connector = $this->find($id);
        if (!$connector || empty($connector['config'])) {
            return [];
        }

        return json_decode($connector['config'], true) ?: [];
    }

    // ─────────────────────────────────────────────
    // CREATE / UPDATE / DELETE
    // ─────────────────────────────────────────────

    /**
     * Crea un nuovo connettore.
     *
     * @param array $data Campi obbligatori: project_id, user_id, type, name, config
     * @return int ID del connettore creato
     */
    public function create(array $data): int
    {
        $insertData = [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'name' => $data['name'],
            'config' => is_string($data['config']) ? $data['config'] : json_encode($data['config']),
            'is_active' => 1,
        ];

        // Campi opzionali
        if (!empty($data['seo_plugin'])) {
            $insertData['seo_plugin'] = $data['seo_plugin'];
        }
        if (!empty($data['wp_version'])) {
            $insertData['wp_version'] = $data['wp_version'];
        }
        if (!empty($data['plugin_version'])) {
            $insertData['plugin_version'] = $data['plugin_version'];
        }

        return Database::insert($this->table, $insertData);
    }

    /**
     * Aggiorna campi di un connettore.
     * Solo i campi nell'allowlist vengono aggiornati.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'config', 'is_active', 'seo_plugin', 'wp_version', 'plugin_version'];
        $updateData = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'config' && !is_string($value)) {
                    $value = json_encode($value);
                }
                $updateData[$field] = $value;
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return Database::update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina un connettore.
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Toggle stato attivo/disattivo.
     */
    public function toggleActive(int $id): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?",
            [$id]
        ) > 0;
    }

    // ─────────────────────────────────────────────
    // CONNECTION TEST
    // ─────────────────────────────────────────────

    /**
     * Aggiorna stato del test di connessione.
     * Registra timestamp, esito e opzionalmente aggiorna info CMS.
     *
     * @param int    $id      ID connettore
     * @param string $status  'success' o 'error'
     * @param string|null $message Messaggio di dettaglio
     * @param array|null  $extra  Dati extra: seo_plugin, wp_version, plugin_version
     */
    public function updateTestStatus(int $id, string $status, ?string $message = null, ?array $extra = null): bool
    {
        $updateData = [
            'last_test_at' => date('Y-m-d H:i:s'),
            'last_test_status' => $status,
            'last_test_message' => $message,
        ];

        if ($extra) {
            if (isset($extra['seo_plugin'])) {
                $updateData['seo_plugin'] = $extra['seo_plugin'];
            }
            if (isset($extra['wp_version'])) {
                $updateData['wp_version'] = $extra['wp_version'];
            }
            if (isset($extra['plugin_version'])) {
                $updateData['plugin_version'] = $extra['plugin_version'];
            }
        }

        return Database::update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }
}
