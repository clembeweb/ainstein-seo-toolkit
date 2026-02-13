<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class CampaignEvaluation
{
    public static function create(array $data): int
    {
        $record = [
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'run_id' => $data['run_id'] ?? null,
            'name' => $data['name'],
            'campaigns_evaluated' => $data['campaigns_evaluated'] ?? 0,
            'ads_evaluated' => $data['ads_evaluated'] ?? 0,
            'status' => $data['status'] ?? 'pending',
        ];

        // Colonne opzionali (migration 003+004+005)
        if (isset($data['ad_groups_evaluated'])) {
            $record['ad_groups_evaluated'] = $data['ad_groups_evaluated'];
        }
        if (isset($data['keywords_evaluated'])) {
            $record['keywords_evaluated'] = $data['keywords_evaluated'];
        }
        if (isset($data['landing_pages_analyzed'])) {
            $record['landing_pages_analyzed'] = $data['landing_pages_analyzed'];
        }
        if (isset($data['eval_type'])) {
            $record['eval_type'] = $data['eval_type'];
        }
        if (isset($data['previous_eval_id'])) {
            $record['previous_eval_id'] = $data['previous_eval_id'];
        }
        if (isset($data['campaigns_filter'])) {
            $record['campaigns_filter'] = $data['campaigns_filter'];
        }

        return Database::insert('ga_campaign_evaluations', $record);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_campaign_evaluations WHERE id = ?",
            [$id]
        ) ?: null;
    }

    public static function findByUserAndId(int $userId, int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_campaign_evaluations WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) ?: null;
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('ga_campaign_evaluations', $data, 'id = ?', [$id]) > 0;
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
        return Database::update('ga_campaign_evaluations', $data, 'id = ?', [$id]) > 0;
    }

    public static function getByProject(int $projectId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_campaign_evaluations WHERE project_id = ? ORDER BY created_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    public static function getLatestByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_campaign_evaluations WHERE project_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        ) ?: null;
    }

    /**
     * Ultima valutazione completata CON ai_response reale (esclude no_change)
     */
    public static function getLatestWithAiByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_campaign_evaluations
             WHERE project_id = ? AND status = 'completed'
             AND ai_response IS NOT NULL AND ai_response != '{}' AND ai_response != 'null'
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        ) ?: null;
    }

    public static function countByProject(int $projectId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_campaign_evaluations WHERE project_id = ?",
            [$projectId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function delete(int $id): bool
    {
        return Database::delete('ga_campaign_evaluations', 'id = ?', [$id]) > 0;
    }

    /**
     * Ottiene la risposta AI decodificata
     */
    public static function getAiResponse(int $id): ?array
    {
        $eval = self::find($id);
        if (!$eval || empty($eval['ai_response'])) {
            return null;
        }
        return json_decode($eval['ai_response'], true);
    }
}
