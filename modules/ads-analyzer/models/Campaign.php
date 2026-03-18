<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Campaign
{
    public static function create(array $data): int
    {
        return Database::insert('ga_campaigns', [
            'project_id' => $data['project_id'],
            'sync_id' => $data['sync_id'] ?? $data['run_id'] ?? null,
            'campaign_id_google' => $data['campaign_id_google'],
            'campaign_name' => $data['campaign_name'],
            'campaign_status' => $data['campaign_status'] ?? null,
            'campaign_type' => $data['campaign_type'] ?? null,
            'bidding_strategy' => $data['bidding_strategy'] ?? null,
            'budget_amount' => $data['budget_amount'] ?? null,
            'budget_type' => $data['budget_type'] ?? null,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'avg_cpc' => $data['avg_cpc'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'conversion_value' => $data['conversion_value'] ?? 0,
            'conv_rate' => $data['conv_rate'] ?? 0,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM ga_campaigns WHERE id = ?", [$id]) ?: null;
    }

    public static function getByRun(int $runId, ?int $projectId = null): array
    {
        if ($projectId) {
            return Database::fetchAll(
                "SELECT * FROM ga_campaigns WHERE sync_id = ? AND project_id = ? ORDER BY cost DESC",
                [$runId, $projectId]
            );
        }
        return Database::fetchAll(
            "SELECT * FROM ga_campaigns WHERE sync_id = ? ORDER BY cost DESC",
            [$runId]
        );
    }

    public static function getByProject(int $projectId, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT c.*, s.started_at as run_date FROM ga_campaigns c
             JOIN ga_syncs s ON c.sync_id = s.id
             WHERE c.project_id = ? ORDER BY s.started_at DESC, c.cost DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    public static function getLatestByProject(int $projectId): array
    {
        $latestSync = Sync::getLatestByProject($projectId);
        if (!$latestSync) {
            return [];
        }
        return self::getByRun($latestSync['id']);
    }

    public static function getStatsByRun(int $runId, ?int $projectId = null): array
    {
        $where = "sync_id = ?";
        $params = [$runId];
        if ($projectId) {
            $where .= " AND project_id = ?";
            $params[] = $projectId;
        }
        return Database::fetch(
            "SELECT COUNT(*) as total_campaigns, SUM(clicks) as total_clicks,
                    SUM(impressions) as total_impressions, SUM(cost) as total_cost,
                    SUM(conversions) as total_conversions, SUM(conversion_value) as total_value,
                    AVG(ctr) as avg_ctr, AVG(avg_cpc) as avg_cpc
             FROM ga_campaigns WHERE {$where}",
            $params
        ) ?: [];
    }

    public static function countByProject(int $projectId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_campaigns WHERE project_id = ?",
            [$projectId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function deleteByRun(int $runId): bool
    {
        return Database::delete('ga_campaigns', 'sync_id = ?', [$runId]) >= 0;
    }
}
