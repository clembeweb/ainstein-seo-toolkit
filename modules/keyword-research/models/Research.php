<?php

namespace Modules\KeywordResearch\Models;

use Core\Database;

class Research
{
    protected string $table = 'kr_researches';

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

    public function findByProject(int $projectId, ?string $type = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        Database::insert($this->table, [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 'draft',
            'brief' => json_encode($data['brief']),
        ]);
        return (int) Database::lastInsertId();
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $data = array_merge(['status' => $status], $extra);
        Database::update($this->table, $data, 'id = ?', [$id]);
    }

    public function saveResults(int $id, array $data): void
    {
        $update = [];
        if (isset($data['raw_keywords_count'])) $update['raw_keywords_count'] = $data['raw_keywords_count'];
        if (isset($data['filtered_keywords_count'])) $update['filtered_keywords_count'] = $data['filtered_keywords_count'];
        if (isset($data['ai_response'])) $update['ai_response'] = json_encode($data['ai_response']);
        if (isset($data['strategy_note'])) $update['strategy_note'] = $data['strategy_note'];
        if (isset($data['credits_used'])) $update['credits_used'] = $data['credits_used'];
        if (isset($data['api_time_ms'])) $update['api_time_ms'] = $data['api_time_ms'];
        if (isset($data['ai_time_ms'])) $update['ai_time_ms'] = $data['ai_time_ms'];
        if (isset($data['status'])) $update['status'] = $data['status'];

        if (!empty($update)) {
            Database::update($this->table, $update, 'id = ?', [$id]);
        }
    }

    public static function countByUser(int $userId): int
    {
        return Database::count('kr_researches', 'user_id = ?', [$userId]);
    }

    public function delete(int $id): void
    {
        Database::delete('kr_editorial_items', 'research_id = ?', [$id]);
        Database::delete('kr_keywords', 'research_id = ?', [$id]);
        Database::delete('kr_clusters', 'research_id = ?', [$id]);
        Database::delete($this->table, 'id = ?', [$id]);
    }
}
