<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * Project Model
 *
 * Gestisce la tabella cb_projects e operazioni correlate
 */
class Project
{
    protected string $table = 'cb_projects';

    /**
     * Trova progetto per ID (con verifica utente)
     */
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

    /**
     * Find a project accessible by the user (owner or shared member).
     */
    public function findAccessible(int $id, ?int $userId = null): ?array
    {
        $project = $this->find($id, $userId);
        if ($project) {
            $project['access_role'] = 'owner';
            return $project;
        }

        if ($userId === null) {
            return null;
        }

        $project = $this->find($id);
        if (!$project || empty($project['global_project_id'])) {
            return null;
        }

        $role = \Services\ProjectAccessService::getRole((int)$project['global_project_id'], $userId);
        if ($role === null) {
            return null;
        }

        if ($role !== 'owner' && !\Services\ProjectAccessService::canAccessModule(
            (int)$project['global_project_id'], $userId, 'crawl-budget'
        )) {
            return null;
        }

        $project['access_role'] = $role;
        return $project;
    }

    /**
     * Ottieni tutti i progetti di un utente (propri + condivisi)
     */
    public function allByUser(int $userId, ?string $status = null): array
    {
        $ids = \Services\ProjectAccessService::getAccessibleModuleProjectIds($userId, 'crawl-budget', $this->table);
        if (empty($ids)) {
            return [];
        }
        $in = \Services\ProjectAccessService::sqlInClause($ids);

        $sql = "SELECT * FROM {$this->table} WHERE id IN {$in['sql']}";
        $params = $in['params'];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Ottieni tutti i progetti con statistiche
     */
    public function allWithStats(int $userId): array
    {
        $ids = \Services\ProjectAccessService::getAccessibleModuleProjectIds($userId, 'crawl-budget', $this->table);
        if (empty($ids)) {
            return [];
        }
        $in = \Services\ProjectAccessService::sqlInClause($ids);

        $sql = "
            SELECT
                p.*,
                CASE WHEN p.user_id = ? THEN 'owner' ELSE 'shared' END as access_role,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = p.id AND severity = 'critical') as critical_issues,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = p.id AND severity = 'warning') as warning_issues,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = p.id AND severity = 'notice') as notice_issues
            FROM {$this->table} p
            WHERE p.id IN {$in['sql']}
            ORDER BY p.created_at DESC
        ";

        return Database::fetchAll($sql, array_merge([$userId], $in['params']));
    }

    /**
     * Ottieni progetto con statistiche dettagliate
     */
    public function findWithStats(int $id, int $userId): ?array
    {
        $project = $this->findAccessible($id, $userId);
        if (!$project) {
            return null;
        }

        $stats = Database::fetch("
            SELECT
                (SELECT COUNT(*) FROM cb_pages WHERE project_id = ?) as total_pages,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = ?) as total_issues,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = ? AND severity = 'critical') as critical_issues,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = ? AND severity = 'warning') as warning_issues,
                (SELECT COUNT(*) FROM cb_issues WHERE project_id = ? AND severity = 'notice') as notice_issues
        ", [$id, $id, $id, $id, $id]);

        return array_merge($project, $stats ?: []);
    }

    /**
     * Crea nuovo progetto
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Crea progetto con record di configurazione sito
     */
    public function createWithConfig(array $data): int
    {
        $projectId = $this->create($data);
        Database::insert('cb_site_config', ['project_id' => $projectId]);
        return $projectId;
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $where = 'id = ?';
        $params = [$id];

        if ($userId !== null) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }

        return Database::update($this->table, $data, $where, $params) > 0;
    }

    /**
     * Elimina progetto
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Normalizza URL base
     */
    public static function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);
        $url = rtrim($url, '/');

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Conta progetti per utente
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * KPI standardizzati per il progetto (usato da GlobalProject hub).
     */
    public function getProjectKpi(int $projectId): array
    {
        $project = Database::fetch(
            "SELECT crawl_budget_score, last_crawl_at, current_session_id FROM {$this->table} WHERE id = ?",
            [$projectId]
        );

        $criticalCount = 0;
        $pagesCrawled = 0;
        $sessionId = (int) ($project['current_session_id'] ?? 0);

        if ($sessionId) {
            try {
                $row = Database::fetch(
                    "SELECT COUNT(*) as cnt FROM cb_issues WHERE project_id = ? AND severity = 'critical'",
                    [$projectId]
                );
                $criticalCount = (int) ($row['cnt'] ?? 0);

                $row2 = Database::fetch(
                    "SELECT pages_crawled FROM cb_crawl_sessions WHERE id = ?",
                    [$sessionId]
                );
                $pagesCrawled = (int) ($row2['pages_crawled'] ?? 0);
            } catch (\Exception $e) {
                // Graceful degradation
            }
        }

        $score = (int) ($project['crawl_budget_score'] ?? 0);

        return [
            'metrics' => [
                ['label' => 'Budget Score', 'value' => $score],
                ['label' => 'Issues critiche', 'value' => $criticalCount],
                ['label' => 'Pagine analizzate', 'value' => $pagesCrawled],
            ],
            'lastActivity' => $project['last_crawl_at'] ?? null,
        ];
    }
}
