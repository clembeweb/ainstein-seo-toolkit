<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Project
{
    public static function create(array $data): int
    {
        return Database::insert('ga_projects', [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'business_context' => $data['business_context'] ?? '',
            'status' => $data['status'] ?? 'draft'
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_projects WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function findByUserAndId(int $userId, int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_projects WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) ?: null;
    }

    public static function getAllByUser(int $userId, ?string $status = null): array
    {
        $sql = "SELECT * FROM ga_projects WHERE user_id = ?";
        $params = [$userId];

        if ($status && $status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_projects', $data, 'id = ?', [$id]) > 0;
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_projects', 'id = ?', [$id]) > 0;
    }

    public static function deleteByUser(int $userId, int $id): bool
    {
        return Database::delete('ga_projects', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    public static function getStats(int $userId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'analyzing' THEN 1 ELSE 0 END) as analyzing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
                COALESCE(SUM(total_terms), 0) as total_terms,
                COALESCE(SUM(total_negatives_found), 0) as total_negatives
            FROM ga_projects
            WHERE user_id = ?
        ";

        return Database::fetch($sql, [$userId]) ?: [
            'total' => 0,
            'drafts' => 0,
            'analyzing' => 0,
            'completed' => 0,
            'archived' => 0,
            'total_terms' => 0,
            'total_negatives' => 0
        ];
    }

    public static function getRecent(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Trova progetto per API token (usato dall'endpoint API pubblico)
     */
    public static function findByToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_projects WHERE api_token = ?",
            [$token]
        ) ?: null;
    }

    /**
     * Genera un nuovo API token per il progetto
     */
    public static function generateToken(int $projectId): string
    {
        $token = bin2hex(random_bytes(32));

        Database::update('ga_projects', [
            'api_token' => $token,
            'api_token_created_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$projectId]);

        return $token;
    }

    /**
     * Aggiorna la configurazione script del progetto
     */
    public static function updateScriptConfig(int $projectId, array $config): bool
    {
        return Database::update('ga_projects', [
            'script_config' => json_encode($config)
        ], 'id = ?', [$projectId]) > 0;
    }

    /**
     * Ottiene la configurazione script con defaults
     */
    public static function getScriptConfig(int $projectId): array
    {
        $project = self::find($projectId);
        $config = json_decode($project['script_config'] ?? '{}', true) ?: [];

        return array_merge([
            'enable_search_terms' => true,
            'enable_campaign_performance' => true,
            'date_range' => 'LAST_30_DAYS',
            'campaign_filter' => '',
        ], $config);
    }
}
