<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Project
{
    public static function create(array $data): int
    {
        $record = [
            'user_id' => $data['user_id'],
            'type' => $data['type'] ?? 'campaign',
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'business_context' => $data['business_context'] ?? '',
            'status' => $data['status'] ?? 'draft'
        ];

        // Campi Campaign Creator
        if (isset($data['landing_url'])) {
            $record['landing_url'] = $data['landing_url'];
        }
        if (isset($data['campaign_type_gads'])) {
            $record['campaign_type_gads'] = $data['campaign_type_gads'];
        }
        if (isset($data['brief'])) {
            $record['brief'] = $data['brief'];
        }
        if (isset($data['input_mode'])) {
            $record['input_mode'] = $data['input_mode'];
        }

        return Database::insert('ga_projects', $record);
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

    /**
     * Find a project accessible by the user (owner or shared member).
     */
    public static function findAccessible(int $userId, int $id): ?array
    {
        // Fast path: direct owner
        $project = self::findByUserAndId($userId, $id);
        if ($project) {
            $project['access_role'] = 'owner';
            return $project;
        }

        // Shared access: find project without user filter, then check sharing
        $project = self::find($id);
        if (!$project || empty($project['global_project_id'])) {
            return null;
        }

        $role = \Services\ProjectAccessService::getRole((int)$project['global_project_id'], $userId);
        if ($role === null) {
            return null;
        }

        // Check module-level access
        if ($role !== 'owner' && !\Services\ProjectAccessService::canAccessModule(
            (int)$project['global_project_id'], $userId, 'ads-analyzer'
        )) {
            return null;
        }

        $project['access_role'] = $role;
        return $project;
    }

    public static function getAllByUser(int $userId, ?string $status = null, ?string $type = null): array
    {
        $ids = \Services\ProjectAccessService::getAccessibleModuleProjectIds($userId, 'ads-analyzer', 'ga_projects');
        if (empty($ids)) {
            return [];
        }
        $in = \Services\ProjectAccessService::sqlInClause($ids);

        $sql = "SELECT * FROM ga_projects WHERE id IN {$in['sql']}";
        $params = $in['params'];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($status && $status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Progetti raggruppati per tipo con stats specifiche (propri + condivisi)
     */
    public static function allGroupedByType(int $userId): array
    {
        $allIds = \Services\ProjectAccessService::getAccessibleModuleProjectIds($userId, 'ads-analyzer', 'ga_projects');
        if (empty($allIds)) {
            return ['campaign' => [], 'campaign-creator' => []];
        }
        $in = \Services\ProjectAccessService::sqlInClause($allIds);

        $campaign = Database::fetchAll("
            SELECT p.*,
                CASE WHEN p.user_id = ? THEN 'owner' ELSE 'shared' END as access_role,
                (SELECT COUNT(*) FROM ga_script_runs WHERE project_id = p.id AND status = 'completed') as total_runs,
                (SELECT COUNT(DISTINCT c.id) FROM ga_campaigns c WHERE c.project_id = p.id) as total_campaigns,
                (SELECT COUNT(*) FROM ga_campaign_evaluations WHERE project_id = p.id AND status = 'completed') as total_evaluations
            FROM ga_projects p
            WHERE p.id IN {$in['sql']} AND p.type = 'campaign'
            ORDER BY p.updated_at DESC
        ", array_merge([$userId], $in['params']));

        $creator = Database::fetchAll("
            SELECT p.*,
                CASE WHEN p.user_id = ? THEN 'owner' ELSE 'shared' END as access_role,
                (SELECT COUNT(*) FROM ga_creator_generations WHERE project_id = p.id AND step = 'keywords' AND status = 'completed') as kw_generations,
                (SELECT COUNT(*) FROM ga_creator_campaigns WHERE project_id = p.id) as campaigns_generated
            FROM ga_projects p
            WHERE p.id IN {$in['sql']} AND p.type = 'campaign-creator'
            ORDER BY p.updated_at DESC
        ", array_merge([$userId], $in['params']));

        return [
            'campaign' => $campaign,
            'campaign-creator' => $creator,
        ];
    }

    /**
     * Stats globali per entry dashboard (propri + condivisi)
     */
    public static function getGlobalStats(int $userId): array
    {
        $allIds = \Services\ProjectAccessService::getAccessibleModuleProjectIds($userId, 'ads-analyzer', 'ga_projects');

        if (empty($allIds)) {
            return [
                'total_projects' => 0, 'campaign_count' => 0, 'creator_count' => 0,
                'total_campaigns' => 0, 'total_evaluations' => 0,
                'total_terms' => 0, 'total_negatives' => 0, 'campaigns_generated' => 0,
            ];
        }

        $in = \Services\ProjectAccessService::sqlInClause($allIds);

        $counts = Database::fetch("
            SELECT
                COUNT(*) as total_projects,
                SUM(CASE WHEN type = 'campaign' THEN 1 ELSE 0 END) as campaign_count,
                SUM(CASE WHEN type = 'campaign-creator' THEN 1 ELSE 0 END) as creator_count
            FROM ga_projects WHERE id IN {$in['sql']}
        ", $in['params']) ?: [];

        $campaignStats = Database::fetch("
            SELECT
                (SELECT COUNT(DISTINCT c.id) FROM ga_campaigns c WHERE c.project_id IN {$in['sql']}) as total_campaigns,
                (SELECT COUNT(*) FROM ga_campaign_evaluations e WHERE e.project_id IN {$in['sql']} AND e.status = 'completed') as total_evaluations
        ", array_merge($in['params'], $in['params'])) ?: [];

        $negStats = Database::fetch("
            SELECT
                COALESCE(SUM(p.total_terms), 0) as total_terms,
                (SELECT COUNT(*) FROM ga_negative_keywords nk
                 INNER JOIN ga_analyses a ON nk.analysis_id = a.id
                 WHERE a.project_id IN {$in['sql']} AND nk.is_selected = 1) as total_negatives
            FROM ga_projects p
            WHERE p.id IN {$in['sql']} AND p.type = 'campaign'
        ", array_merge($in['params'], $in['params'])) ?: [];

        $creatorStats = Database::fetch("
            SELECT COUNT(*) as campaigns_generated
            FROM ga_creator_campaigns cc
            WHERE cc.project_id IN {$in['sql']}
        ", $in['params']) ?: [];

        return [
            'total_projects' => (int) ($counts['total_projects'] ?? 0),
            'campaign_count' => (int) ($counts['campaign_count'] ?? 0),
            'creator_count' => (int) ($counts['creator_count'] ?? 0),
            'total_campaigns' => (int) ($campaignStats['total_campaigns'] ?? 0),
            'total_evaluations' => (int) ($campaignStats['total_evaluations'] ?? 0),
            'total_terms' => (int) ($negStats['total_terms'] ?? 0),
            'total_negatives' => (int) ($negStats['total_negatives'] ?? 0),
            'campaigns_generated' => (int) ($creatorStats['campaigns_generated'] ?? 0),
        ];
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

    public static function getStats(int $userId, ?string $type = null): array
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
        $params = [$userId];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        return Database::fetch($sql, $params) ?: [
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
        $allIds = \Services\ProjectAccessService::getAccessibleModuleProjectIds($userId, 'ads-analyzer', 'ga_projects');
        if (empty($allIds)) {
            return [];
        }
        $in = \Services\ProjectAccessService::sqlInClause($allIds);
        return Database::fetchAll(
            "SELECT p.*, CASE WHEN p.user_id = ? THEN 'owner' ELSE 'shared' END as access_role
             FROM ga_projects p
             WHERE p.id IN {$in['sql']}
             ORDER BY p.updated_at DESC LIMIT ?",
            array_merge([$userId], $in['params'], [$limit])
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

    /**
     * KPI standardizzati per il progetto (usato da GlobalProject hub).
     *
     * @return array{metrics: array, lastActivity: ?string}
     */
    public static function getProjectKpi(int $projectId): array
    {
        // Campagne e valutazioni
        $campaignCount = 0;
        try {
            $cRow = Database::fetch(
                "SELECT COUNT(DISTINCT id) as cnt FROM ga_campaigns WHERE project_id = ?",
                [$projectId]
            );
            $campaignCount = (int) ($cRow['cnt'] ?? 0);
        } catch (\Exception $e) {
            // Graceful degradation
        }

        $evalStats = Database::fetch("
            SELECT
                COUNT(*) as eval_count,
                MAX(created_at) as last_activity
            FROM ga_campaign_evaluations
            WHERE project_id = ? AND status = 'completed'
        ", [$projectId]);

        // Ultimo score con delta rispetto al precedente
        $lastScoreMetric = ['label' => 'Ultimo score', 'value' => 0];
        try {
            $recentEvals = Database::fetchAll("
                SELECT ai_response
                FROM ga_campaign_evaluations
                WHERE project_id = ? AND status = 'completed'
                ORDER BY created_at DESC
                LIMIT 2
            ", [$projectId]);

            if (!empty($recentEvals)) {
                $latestAi = json_decode($recentEvals[0]['ai_response'] ?? '{}', true);
                $latestScore = (float) ($latestAi['overall_score'] ?? 0);
                $lastScoreMetric['value'] = $latestScore;

                if (count($recentEvals) >= 2) {
                    $prevAi = json_decode($recentEvals[1]['ai_response'] ?? '{}', true);
                    $prevScore = (float) ($prevAi['overall_score'] ?? 0);
                    if ($prevScore > 0) {
                        $delta = round($latestScore - $prevScore, 1);
                        $lastScoreMetric['delta'] = $delta;
                        $lastScoreMetric['deltaGood'] = $delta >= 0;
                    }
                }
            }
        } catch (\Exception $e) {
            // Graceful degradation
        }

        $metrics = [
            ['label' => 'Campagne', 'value' => $campaignCount],
            ['label' => 'Valutazioni', 'value' => (int) ($evalStats['eval_count'] ?? 0)],
            $lastScoreMetric,
        ];

        return [
            'metrics' => $metrics,
            'lastActivity' => $evalStats['last_activity'] ?? null,
        ];
    }
}
