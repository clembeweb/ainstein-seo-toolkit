<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Alert;
use Modules\SeoTracking\Models\AiReport;
use Modules\SeoTracking\Models\RankCheck;
use Modules\SeoTracking\Helpers\PaginationHelper;
use Core\Database;

/**
 * DashboardController
 * Gestisce le dashboard del progetto
 */
class DashboardController
{
    private Project $project;
    private Keyword $keyword;
    private GscDaily $gscDaily;
    private Alert $alert;
    private AiReport $aiReport;
    private RankCheck $rankCheck;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscDaily = new GscDaily();
        $this->alert = new Alert();
        $this->aiReport = new AiReport();
        $this->rankCheck = new RankCheck();
    }

    /**
     * Dashboard principale progetto (basata su rank checker, non GSC)
     */
    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithConnections($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Rilascia sessione PRIMA delle query pesanti
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // KPI Stats basate su keyword tracciate
        $kpiStats = $this->getKpiStats($id);

        // Distribuzione posizioni per donut chart
        $positionDistribution = $this->getPositionDistribution($id);

        // Trend posizione media (ultimi 30 giorni)
        $positionTrend = $this->getPositionTrend($id, 30);

        // Top 5 Gainers e Losers
        $gainers = $this->getTopMovers($id, 5, 'gainers');
        $losers = $this->getTopMovers($id, 5, 'losers');

        // Movimenti recenti (ultime 10 verifiche)
        $recentMovements = $this->getRecentMovements($id, 10);

        // Ultimo check
        $lastCheck = $this->getLastCheckInfo($id);

        return View::render('seo-tracking/dashboard/index', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'kpiStats' => $kpiStats,
            'positionDistribution' => $positionDistribution,
            'positionTrend' => $positionTrend,
            'gainers' => $gainers,
            'losers' => $losers,
            'recentMovements' => $recentMovements,
            'lastCheck' => $lastCheck,
        ]);
    }

    /**
     * KPI Stats basate sui dati reali di st_rank_checks
     */
    private function getKpiStats(int $projectId): array
    {
        // Keyword tracciate (dalla tabella keywords)
        $trackedCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM st_keywords WHERE project_id = ? AND is_tracked = 1",
            [$projectId]
        )['cnt'] ?? 0;

        // Statistiche dalle ultime verifiche SERP (st_rank_checks)
        // Prende l'ultima posizione per ogni keyword
        $serpStats = Database::fetch(
            "SELECT
                COUNT(DISTINCT keyword) as checked_keywords,
                AVG(serp_position) as avg_pos,
                SUM(CASE WHEN serp_position <= 10 THEN 1 ELSE 0 END) as top10
             FROM (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks
                WHERE project_id = ? AND serp_position IS NOT NULL
             ) latest
             WHERE rn = 1",
            [$projectId]
        );

        // Variazioni ultimi 7 giorni (confronta rank_checks)
        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
        $variations = Database::fetch(
            "SELECT
                SUM(CASE WHEN rc_new.serp_position < rc_old.serp_position THEN 1 ELSE 0 END) as improved,
                SUM(CASE WHEN rc_new.serp_position > rc_old.serp_position THEN 1 ELSE 0 END) as declined
             FROM (
                SELECT keyword, serp_position, checked_at,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ?
             ) rc_new
             JOIN (
                SELECT keyword, serp_position, checked_at,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND DATE(checked_at) <= ?
             ) rc_old ON rc_new.keyword = rc_old.keyword
             WHERE rc_new.rn = 1 AND rc_old.rn = 1
               AND rc_new.serp_position IS NOT NULL AND rc_old.serp_position IS NOT NULL",
            [$projectId, $projectId, $sevenDaysAgo]
        );

        $avgPosition = $serpStats['avg_pos'] ?? null;

        return [
            'tracked_keywords' => (int) $trackedCount,
            'avg_position' => $avgPosition ? round((float) $avgPosition, 1) : null,
            'top10_count' => (int) ($serpStats['top10'] ?? 0),
            'improved_7d' => (int) ($variations['improved'] ?? 0),
            'declined_7d' => (int) ($variations['declined'] ?? 0),
        ];
    }

    /**
     * Distribuzione posizioni per donut chart (basata su st_rank_checks)
     */
    private function getPositionDistribution(int $projectId): array
    {
        // Usa l'ultima posizione SERP per ogni keyword da st_rank_checks
        $result = Database::fetch(
            "SELECT
                SUM(CASE WHEN serp_position <= 3 THEN 1 ELSE 0 END) as top3,
                SUM(CASE WHEN serp_position > 3 AND serp_position <= 10 THEN 1 ELSE 0 END) as top4_10,
                SUM(CASE WHEN serp_position > 10 AND serp_position <= 20 THEN 1 ELSE 0 END) as top11_20,
                SUM(CASE WHEN serp_position > 20 AND serp_position <= 50 THEN 1 ELSE 0 END) as top21_50,
                SUM(CASE WHEN serp_position > 50 THEN 1 ELSE 0 END) as beyond50
             FROM (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks
                WHERE project_id = ? AND serp_position IS NOT NULL
             ) latest
             WHERE rn = 1",
            [$projectId]
        );

        return [
            'top3' => (int) ($result['top3'] ?? 0),
            'top4_10' => (int) ($result['top4_10'] ?? 0),
            'top11_20' => (int) ($result['top11_20'] ?? 0),
            'top21_50' => (int) ($result['top21_50'] ?? 0),
            'beyond50' => (int) ($result['beyond50'] ?? 0),
        ];
    }

    /**
     * Trend posizione media ultimi N giorni
     */
    private function getPositionTrend(int $projectId, int $days): array
    {
        return Database::fetchAll(
            "SELECT
                DATE(checked_at) as date,
                AVG(serp_position) as avg_position,
                COUNT(*) as check_count
             FROM st_rank_checks
             WHERE project_id = ?
               AND checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND serp_position IS NOT NULL
             GROUP BY DATE(checked_at)
             ORDER BY date ASC",
            [$projectId, $days]
        );
    }

    /**
     * Top movers (gainers o losers)
     */
    private function getTopMovers(int $projectId, int $limit, string $type): array
    {
        $orderDir = $type === 'gainers' ? 'ASC' : 'DESC';
        $whereCondition = $type === 'gainers' ? '< 0' : '> 0';

        // Confronta ultima posizione con penultima
        return Database::fetchAll(
            "SELECT
                rc_new.keyword,
                rc_new.serp_position as new_position,
                rc_old.serp_position as old_position,
                (rc_new.serp_position - rc_old.serp_position) as position_diff
             FROM (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
             ) rc_new
             JOIN (
                SELECT keyword, serp_position,
                       ROW_NUMBER() OVER (PARTITION BY keyword ORDER BY checked_at DESC) as rn
                FROM st_rank_checks WHERE project_id = ? AND serp_position IS NOT NULL
             ) rc_old ON rc_new.keyword = rc_old.keyword
             WHERE rc_new.rn = 1 AND rc_old.rn = 2
               AND (rc_new.serp_position - rc_old.serp_position) {$whereCondition}
             ORDER BY position_diff {$orderDir}
             LIMIT ?",
            [$projectId, $projectId, $limit]
        );
    }

    /**
     * Movimenti recenti (ultime verifiche)
     */
    private function getRecentMovements(int $projectId, int $limit): array
    {
        return Database::fetchAll(
            "SELECT
                rc.keyword,
                rc.serp_position,
                rc.serp_url,
                rc.checked_at,
                rc.device,
                (SELECT serp_position FROM st_rank_checks rc2
                 WHERE rc2.project_id = rc.project_id
                   AND rc2.keyword = rc.keyword
                   AND rc2.checked_at < rc.checked_at
                 ORDER BY rc2.checked_at DESC LIMIT 1) as prev_position
             FROM st_rank_checks rc
             WHERE rc.project_id = ?
             ORDER BY rc.checked_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Info ultimo check
     */
    private function getLastCheckInfo(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT MAX(checked_at) as checked_at, COUNT(*) as keywords_checked
             FROM st_rank_checks
             WHERE project_id = ?
             GROUP BY DATE(checked_at), HOUR(checked_at)
             ORDER BY checked_at DESC
             LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Vista keyword overview con paginazione SQL
     */
    public function keywords(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Rilascia sessione PRIMA delle query pesanti
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Filtri da GET
        $filters = [
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 50),
            'search' => trim($_GET['search'] ?? ''),
            'min_clicks' => (int) ($_GET['min_clicks'] ?? 0),
            'max_position' => (float) ($_GET['max_position'] ?? 0),
            'sort' => $_GET['sort'] ?? 'clicks',
            'dir' => $_GET['dir'] ?? 'desc',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d', strtotime('-1 day')),
        ];

        // Query paginata SQL (NON carica tutto in memoria)
        $result = $this->getFilteredKeywordsPaginated($id, $filters);

        // Stats aggregate (query separata, leggera)
        $stats = $this->calculateKeywordStats($id, $filters);

        return View::render('seo-tracking/dashboard/keywords', [
            'title' => $project['name'] . ' - Keyword Overview',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keywords' => $result['items'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'stats' => $stats,
            'dateRange' => [
                'start' => $filters['date_from'],
                'end' => $filters['date_to'],
            ],
        ]);
    }

    /**
     * Query keywords con paginazione SQL (LIMIT/OFFSET)
     */
    private function getFilteredKeywordsPaginated(int $projectId, array $filters): array
    {
        $db = Database::getInstance();

        // CRITICO: Rilascia sessione
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];
        $perPage = min((int)($filters['per_page'] ?? 50), 100);
        $page = max(1, (int)($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $search = $filters['search'] ?? '';

        // QUERY SEMPLIFICATA - Niente COUNT separato, usa subquery con LIMIT
        $searchWhere = '';
        $params = [$projectId, $dateFrom, $dateTo];

        if (!empty($search)) {
            $searchWhere = "AND query LIKE ?";
            $params[] = '%' . $search . '%';
        }

        // HAVING per filtri avanzati
        $havingClauses = [];
        if (($filters['min_clicks'] ?? 0) > 0) {
            $havingClauses[] = "clicks >= " . (int) $filters['min_clicks'];
        }
        if (($filters['max_position'] ?? 0) > 0) {
            $havingClauses[] = "position <= " . (float) $filters['max_position'];
        }
        $having = !empty($havingClauses) ? "HAVING " . implode(" AND ", $havingClauses) : "";

        $sortBy = in_array($filters['sort'] ?? 'clicks', ['clicks', 'impressions', 'position', 'ctr', 'keyword', 'avg_position'])
                  ? $filters['sort'] : 'clicks';
        // Mappa avg_position a position per compatibilità
        if ($sortBy === 'avg_position') $sortBy = 'position';
        $sortDir = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Query principale con LIMIT - molto più veloce (GROUP BY solo query)
        $sql = "
            SELECT
                query as keyword,
                SUM(clicks) as clicks,
                SUM(impressions) as impressions,
                ROUND(AVG(position), 1) as position,
                ROUND(AVG(position), 1) as avg_position,
                ROUND(SUM(clicks) * 100.0 / NULLIF(SUM(impressions), 0), 2) as ctr
            FROM st_gsc_data
            WHERE project_id = ?
            AND date BETWEEN ? AND ?
            AND query != ''
            {$searchWhere}
            GROUP BY query
            {$having}
            ORDER BY {$sortBy} {$sortDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // COUNT approssimativo (molto più veloce) - con LIMIT per evitare timeout
        $countParams = [$projectId, $dateFrom, $dateTo];
        if (!empty($search)) {
            $countParams[] = '%' . $search . '%';
        }

        $countSql = "SELECT COUNT(*) as cnt FROM (
            SELECT 1 FROM st_gsc_data
            WHERE project_id = ? AND date BETWEEN ? AND ? AND query != '' {$searchWhere}
            GROUP BY query
            LIMIT 10001
        ) as sub";

        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $countResult = $stmt->fetch(\PDO::FETCH_ASSOC);
        $total = min((int)$countResult['cnt'], 10000); // Cap a 10k per UI

        $totalPages = max(1, ceil($total / $perPage));

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => min($totalPages, 200), // Max 200 pagine
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages && $page < 200,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }

    /**
     * Stats keywords (query aggregata leggera)
     */
    private function calculateKeywordStats(int $projectId, array $filters): array
    {
        $where = "project_id = ? AND date BETWEEN ? AND ? AND query != ''";
        $params = [$projectId, $filters['date_from'], $filters['date_to']];

        if (!empty($filters['search'])) {
            $where .= " AND query LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql = "
            SELECT
                COUNT(DISTINCT query) as total_keywords,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(position) as avg_position
            FROM st_gsc_data
            WHERE {$where}
        ";

        $row = Database::fetch($sql, $params);

        return [
            'total_keywords' => (int) ($row['total_keywords'] ?? 0),
            'total_clicks' => (int) ($row['total_clicks'] ?? 0),
            'total_impressions' => (int) ($row['total_impressions'] ?? 0),
            'avg_ctr' => ($row['total_impressions'] ?? 0) > 0
                ? round(($row['total_clicks'] / $row['total_impressions']) * 100, 2)
                : 0,
            'avg_position' => round((float) ($row['avg_position'] ?? 0), 1),
        ];
    }

    /**
     * Elimina singola keyword (record GSC)
     */
    public function deleteKeyword(int $projectId, int $keywordId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato'], 404);
            return;
        }

        Database::query(
            "DELETE FROM st_gsc_data WHERE id = ? AND project_id = ?",
            [$keywordId, $projectId]
        );

        $this->jsonResponse(['success' => true, 'message' => 'Keyword eliminata']);
    }

    /**
     * Elimina bulk keywords
     */
    public function bulkDeleteKeywords(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato'], 404);
            return;
        }

        $ids = $_POST['ids'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nessun elemento selezionato'], 400);
            return;
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $params = array_merge($ids, [$projectId]);
        $stmt = Database::query(
            "DELETE FROM st_gsc_data WHERE id IN ({$placeholders}) AND project_id = ?",
            $params
        );

        $this->jsonResponse(['success' => true, 'message' => count($ids) . ' keywords eliminate']);
    }

    /**
     * JSON response helper
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
