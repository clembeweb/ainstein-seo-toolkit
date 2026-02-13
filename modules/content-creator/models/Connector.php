<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * Connector Model for Content Creator Module
 *
 * Gestisce i connettori CMS (WordPress, Shopify, PrestaShop, Magento, Custom API)
 * per la sincronizzazione dei contenuti generati.
 */
class Connector
{
    protected string $table = 'cc_connectors';

    /**
     * Trova connettore per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova connettore per ID con verifica utente
     */
    public function findByUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Tutti i connettori di un utente
     */
    public function allByUser(int $userId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC",
            [$userId]
        );
    }

    /**
     * Connettori attivi di un utente
     */
    public function getActive(int $userId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE user_id = ? AND is_active = 1 ORDER BY name ASC",
            [$userId]
        );
    }

    /**
     * Crea nuovo connettore
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, [
            'user_id' => (int) $data['user_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'config' => is_array($data['config']) ? json_encode($data['config']) : $data['config'],
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);
    }

    /**
     * Aggiorna connettore
     */
    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['type'])) {
            $updateData['type'] = $data['type'];
        }
        if (isset($data['config'])) {
            $updateData['config'] = is_array($data['config']) ? json_encode($data['config']) : $data['config'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (int) $data['is_active'];
        }

        if (empty($updateData)) {
            return false;
        }

        return Database::update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina connettore
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Attiva/disattiva connettore
     */
    public function toggleActive(int $id): bool
    {
        return Database::query(
            "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Aggiorna stato ultimo test connessione
     */
    public function updateTestStatus(int $id, string $status): bool
    {
        return Database::update($this->table, [
            'last_test_at' => date('Y-m-d H:i:s'),
            'last_test_status' => $status,
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna data ultima sincronizzazione
     */
    public function updateSyncTime(int $id): bool
    {
        return Database::update($this->table, [
            'last_sync_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Ottieni configurazione connettore (decodifica JSON)
     */
    public function getConfig(int $id): ?array
    {
        $row = $this->find($id);
        if (!$row) {
            return null;
        }
        $config = json_decode($row['config'] ?? '{}', true);
        return is_array($config) ? $config : [];
    }
}
