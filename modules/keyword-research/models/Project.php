<?php

namespace Modules\KeywordResearch\Models;

use Core\Database;

class Project
{
    protected string $table = 'kr_projects';

    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::fetch($sql, $params);
    }

    public function allByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public function getRecentByUser(int $userId, int $limit = 5): array
    {
        $sql = "
            SELECT p.*,
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as researches_count,
                (SELECT SUM(filtered_keywords_count) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as total_keywords,
                (SELECT COUNT(*) FROM kr_clusters c JOIN kr_researches r ON c.research_id = r.id WHERE r.project_id = p.id) as total_clusters,
                (SELECT MAX(r2.created_at) FROM kr_researches r2 WHERE r2.project_id = p.id) as last_research_at
            FROM {$this->table} p
            WHERE p.user_id = ?
            ORDER BY p.updated_at DESC
            LIMIT ?
        ";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public function allWithStats(int $userId): array
    {
        $sql = "
            SELECT p.*,
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = p.id) as researches_count,
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as completed_count,
                (SELECT SUM(filtered_keywords_count) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as total_keywords,
                (SELECT COUNT(*) FROM kr_clusters c JOIN kr_researches r ON c.research_id = r.id WHERE r.project_id = p.id) as total_clusters
            FROM {$this->table} p
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ";
        return Database::fetchAll($sql, [$userId]);
    }

    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    public function create(array $data): int
    {
        Database::insert($this->table, [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'default_location' => $data['default_location'] ?? 'IT',
            'default_language' => $data['default_language'] ?? 'it',
        ]);
        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        Database::update($this->table, $data, 'id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        // Elimina keyword e cluster delle ricerche del progetto
        $researchIds = Database::fetchAll(
            "SELECT id FROM kr_researches WHERE project_id = ?",
            [$id]
        );

        foreach ($researchIds as $r) {
            Database::delete('kr_keywords', 'research_id = ?', [$r['id']]);
            Database::delete('kr_clusters', 'research_id = ?', [$r['id']]);
        }

        Database::delete('kr_researches', 'project_id = ?', [$id]);
        Database::delete($this->table, 'id = ?', [$id]);
    }
}
