<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * Report Model
 *
 * Gestisce la tabella cb_reports (report AI generati)
 */
class Report
{
    private const TABLE = 'cb_reports';

    /**
     * Trova report per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . " WHERE id = ?",
            [$id]
        );
    }

    /**
     * Trova report per sessione
     */
    public function findBySession(int $sessionId): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . " WHERE session_id = ? ORDER BY created_at DESC LIMIT 1",
            [$sessionId]
        );
    }

    /**
     * Trova ultimo report per progetto
     */
    public function findLatestByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . " WHERE project_id = ? ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Crea nuovo report
     */
    public function create(array $data): int
    {
        foreach (['priority_actions', 'estimated_impact'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Database::insert(self::TABLE, $data);
    }

    /**
     * Aggiorna report
     */
    public function update(int $id, array $data): bool
    {
        foreach (['priority_actions', 'estimated_impact'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        return Database::update(self::TABLE, $data, 'id = ?', [$id]) > 0;
    }
}
