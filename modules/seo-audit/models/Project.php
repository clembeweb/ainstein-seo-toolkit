<?php

namespace Modules\SeoAudit\Models;

use Core\Database;

/**
 * Project Model
 *
 * Gestisce la tabella sa_projects e operazioni correlate
 */
class Project
{
    protected string $table = 'sa_projects';

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
     * Ottieni tutti i progetti di un utente
     */
    public function allByUser(int $userId, ?string $status = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];

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
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'critical') as critical_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'warning') as warning_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'notice') as notice_issues
            FROM {$this->table} p
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ";

        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Ottieni progetto con statistiche dettagliate
     */
    public function findWithStats(int $id, int $userId): ?array
    {
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM sa_pages WHERE project_id = p.id) as total_pages,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id) as total_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'critical') as critical_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'warning') as warning_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'notice') as notice_issues,
                (SELECT COUNT(*) FROM sa_issues WHERE project_id = p.id AND severity = 'info') as info_issues
            FROM {$this->table} p
            WHERE p.id = ? AND p.user_id = ?
        ";

        return Database::fetch($sql, [$id, $userId]);
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

        // Crea record vuoto configurazione sito
        Database::insert('sa_site_config', ['project_id' => $projectId]);

        // Log attività
        $this->logActivity($projectId, $data['user_id'], 'project_created', ['name' => $data['name']]);

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
     * Aggiorna statistiche progetto con conteggi reali da sa_pages
     */
    public function updateStats(int $projectId): void
    {
        // Conta pagine per status (conteggi reali)
        $counts = Database::fetch(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'crawled' THEN 1 ELSE 0 END) as crawled,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
             FROM sa_pages WHERE project_id = ?",
            [$projectId]
        );

        // Conta issues
        $issuesCount = Database::count('sa_issues', 'project_id = ?', [$projectId]);

        // Calcola health score
        $healthScore = $this->calculateHealthScore($projectId);

        // NON aggiornare pages_found qui! Viene settato da discoverUrls()
        // pages_found = URL totali scoperte, pages_crawled = URL effettivamente processate
        $this->update($projectId, [
            'pages_crawled' => (int) ($counts['crawled'] ?? 0),
            'issues_count' => $issuesCount,
            'health_score' => $healthScore,
        ]);
    }

    /**
     * Calcola health score del sito (0-100)
     */
    public function calculateHealthScore(int $projectId): int
    {
        $totalPages = Database::count('sa_pages', 'project_id = ?', [$projectId]);

        if ($totalPages === 0) {
            return 0;
        }

        // Conta issues per severity
        $sql = "
            SELECT
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END) as notice
            FROM sa_issues
            WHERE project_id = ?
        ";
        $issues = Database::fetch($sql, [$projectId]);

        $criticalCount = (int) ($issues['critical'] ?? 0);
        $warningCount = (int) ($issues['warning'] ?? 0);
        $noticeCount = (int) ($issues['notice'] ?? 0);

        // Formula bilanciata con cap per severity (diminishing returns)
        $criticalPenalty = min($criticalCount * 5, 40); // max 40 punti persi
        $warningPenalty = min($warningCount * 1, 30);   // max 30 punti persi
        $noticePenalty = min($noticeCount * 0.2, 10);   // max 10 punti persi

        return max(0, (int) round(100 - $criticalPenalty - $warningPenalty - $noticePenalty));
    }

    /**
     * Ottieni progresso crawl - usa conteggi reali da sa_pages
     */
    public function getCrawlProgress(int $projectId): array
    {
        $project = $this->find($projectId);

        if (!$project) {
            return [
                'pages_found' => 0,
                'pages_crawled' => 0,
                'pages_pending' => 0,
                'progress' => 0,
                'status' => 'pending',
            ];
        }

        // Conta pagine reali dalla tabella sa_pages
        $counts = Database::fetch(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status != 'pending' THEN 1 ELSE 0 END) as crawled,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
             FROM sa_pages WHERE project_id = ?",
            [$projectId]
        );

        // pages_found: usa sa_projects.pages_found (set da discoverUrls) come fonte principale
        // perché le URL scoperte sono in sa_site_config, non in sa_pages
        $pagesFound = (int) ($project['pages_found'] ?? $counts['total'] ?? 0);
        $pagesCrawled = (int) ($counts['crawled'] ?? 0);
        $pagesPending = (int) ($counts['pending'] ?? 0);

        return [
            'pages_found' => $pagesFound,
            'pages_crawled' => $pagesCrawled,
            'pages_pending' => $pagesPending,
            'progress' => $pagesFound > 0 ? round(($pagesCrawled / $pagesFound) * 100, 1) : 0,
            'status' => $project['status'],
        ];
    }

    /**
     * Ottieni statistiche issues per categoria
     */
    public function getIssuesByCategory(int $projectId): array
    {
        $sql = "
            SELECT
                category,
                COUNT(*) as total,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END) as notice,
                SUM(CASE WHEN severity = 'info' THEN 1 ELSE 0 END) as info
            FROM sa_issues
            WHERE project_id = ?
            GROUP BY category
            ORDER BY critical DESC, warning DESC, total DESC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni attività recente
     */
    public function getActivity(int $projectId, int $limit = 10): array
    {
        $sql = "
            SELECT * FROM sa_activity_logs
            WHERE project_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Log attività
     */
    public function logActivity(int $projectId, int $userId, string $action, array $details = []): void
    {
        Database::insert('sa_activity_logs', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'action' => $action,
            'details' => json_encode($details),
        ]);
    }

    /**
     * Verifica se GSC è connesso
     */
    public function isGscConnected(int $projectId): bool
    {
        $project = $this->find($projectId);
        return $project && (bool) $project['gsc_connected'];
    }

    /**
     * Aggiorna stato GSC
     */
    public function updateGscStatus(int $projectId, bool $connected, ?string $property = null): void
    {
        $this->update($projectId, [
            'gsc_connected' => $connected,
            'gsc_property' => $property,
        ]);
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
     * Ottieni top issues (problemi principali)
     */
    public function getTopIssues(int $projectId, int $limit = 10): array
    {
        $sql = "
            SELECT
                i.*,
                p.url as page_url
            FROM sa_issues i
            LEFT JOIN sa_pages p ON i.page_id = p.id
            WHERE i.project_id = ?
            ORDER BY
                FIELD(i.severity, 'critical', 'warning', 'notice', 'info'),
                i.created_at DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }
}
