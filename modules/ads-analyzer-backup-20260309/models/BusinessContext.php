<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class BusinessContext
{
    public static function create(array $data): int
    {
        return Database::insert('ga_saved_contexts', [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'context' => $data['context']
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_saved_contexts WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function findByUserAndId(int $userId, int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_saved_contexts WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) ?: null;
    }

    public static function getByUser(int $userId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_saved_contexts WHERE user_id = ? ORDER BY name ASC",
            [$userId]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_saved_contexts', $data, 'id = ?', [$id]) > 0;
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_saved_contexts', 'id = ?', [$id]) > 0;
    }

    public static function deleteByUser(int $userId, int $id): bool
    {
        return Database::delete('ga_saved_contexts', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    public static function countByUser(int $userId): int
    {
        return Database::count('ga_saved_contexts', 'user_id = ?', [$userId]);
    }
}
