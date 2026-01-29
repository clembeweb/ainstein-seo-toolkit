<?php

namespace Modules\AiOptimizer\Models;

use Core\Database;

/**
 * Model per progetti AI Optimizer
 */
class Project
{
    protected string $table = 'aio_projects';

    /**
     * Trova progetto per ID e user
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::fetch($sql, $params) ?: null;
    }

    /**
     * Lista progetti utente
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM aio_optimizations WHERE project_id = p.id) as optimization_count,
                    (SELECT COUNT(*) FROM aio_optimizations WHERE project_id = p.id AND status = 'refactored') as completed_count
             FROM {$this->table} p
             WHERE p.user_id = ?
             ORDER BY p.updated_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Crea nuovo progetto
     */
    public function create(array $data): int
    {
        Database::insert($this->table, [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'domain' => $data['domain'] ?? null,
            'description' => $data['description'] ?? null,
            'language' => $data['language'] ?? 'it',
            'location_code' => $data['location_code'] ?? 'IT',
        ]);

        return Database::lastInsertId();
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'domain', 'description', 'language', 'location_code'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        $sets = [];
        $params = [];
        foreach ($updateData as $key => $value) {
            $sets[] = "{$key} = ?";
            $params[] = $value;
        }
        $params[] = $id;

        return Database::query(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?",
            $params
        ) !== false;
    }

    /**
     * Elimina progetto
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::query(
            "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) !== false;
    }

    /**
     * Conta progetti utente
     */
    public function countByUser(int $userId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ?",
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }
}
