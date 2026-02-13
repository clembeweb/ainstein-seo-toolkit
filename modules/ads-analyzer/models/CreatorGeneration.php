<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class CreatorGeneration
{
    public static function create(array $data): int
    {
        return Database::insert('ga_creator_generations', [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'step' => $data['step'],
            'status' => $data['status'] ?? 'pending',
            'credits_used' => $data['credits_used'] ?? 0,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_creator_generations WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_creator_generations', $data, 'id = ?', [$id]) > 0;
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $data = ['status' => $status];
        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($error !== null) {
            $data['error_message'] = $error;
        }
        return Database::update('ga_creator_generations', $data, 'id = ?', [$id]) > 0;
    }

    public static function getLatestByProjectAndStep(int $projectId, string $step): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_creator_generations WHERE project_id = ? AND step = ? ORDER BY id DESC LIMIT 1",
            [$projectId, $step]
        ) ?: null;
    }

    public static function getLatestCompletedByProjectAndStep(int $projectId, string $step): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_creator_generations WHERE project_id = ? AND step = ? AND status = 'completed' ORDER BY id DESC LIMIT 1",
            [$projectId, $step]
        ) ?: null;
    }

    public static function getAiResponse(int $id): ?array
    {
        $gen = self::find($id);
        if (!$gen || empty($gen['ai_response'])) {
            return null;
        }
        return json_decode($gen['ai_response'], true);
    }

    public static function deleteByProject(int $projectId): bool
    {
        return Database::delete('ga_creator_generations', 'project_id = ?', [$projectId]) > 0;
    }

    public static function deleteByProjectAndStep(int $projectId, string $step): bool
    {
        return Database::delete('ga_creator_generations', 'project_id = ? AND step = ?', [$projectId, $step]) > 0;
    }
}
