<?php

namespace Modules\SeoAudit\Models;

use Core\Database;

/**
 * UnifiedReport Model
 *
 * Gestisce la tabella sa_unified_reports per i report unificati
 * (on-page + crawl budget) generati dall'AI.
 */
class UnifiedReport
{
    protected string $table = 'sa_unified_reports';

    /**
     * Campi JSON che vengono serializzati automaticamente in create/update
     */
    private const JSON_FIELDS = ['priority_actions', 'estimated_impact', 'site_profile'];

    /**
     * Trova report per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Ottieni tutti i report di un progetto (ordinati dal piu recente)
     */
    public function findByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE project_id = ? ORDER BY created_at DESC",
            [$projectId]
        );
    }

    /**
     * Ottieni l'ultimo report di un progetto
     */
    public function findLatest(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ? ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Crea nuovo report
     */
    public function create(array $data): int
    {
        // Serializza campi JSON
        foreach (self::JSON_FIELDS as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
            }
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Database::insert($this->table, $data);
    }

    /**
     * Elimina report per ID
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina tutti i report di un progetto
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }
}
