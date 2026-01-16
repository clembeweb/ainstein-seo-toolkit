<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Ga4Daily;
use Modules\SeoTracking\Models\Alert;
use Modules\SeoTracking\Models\AiReport;
use Modules\SeoTracking\Models\KeywordRevenue;
use Modules\SeoTracking\Services\DataMergeService;
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
    private Ga4Daily $ga4Daily;
    private Alert $alert;
    private AiReport $aiReport;
    private KeywordRevenue $keywordRevenue;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscDaily = new GscDaily();
        $this->ga4Daily = new Ga4Daily();
        $this->alert = new Alert();
        $this->aiReport = new AiReport();
        $this->keywordRevenue = new KeywordRevenue();
    }

    /**
     * Dashboard principale progetto
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

        // Date range
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevEndDate = date('Y-m-d', strtotime('-8 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));

        // Metriche GSC
        $gscComparison = $this->gscDaily->comparePeriods($id, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // Metriche GA4
        $ga4Comparison = $this->ga4Daily->comparePeriods($id, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // Top keywords per click
        $topKeywords = $this->keyword->getTopByClicks($id, 10);

        // Top movers
        $topMovers = $this->keyword->getTopMovers($id, 5);

        // Alert recenti
        $recentAlerts = $this->alert->getNew($id, 5);

        // Ultimi report AI
        $recentReports = $this->aiReport->getByProject($id, ['limit' => 3]);

        // Ultimo quick wins per widget AI
        $lastQuickWins = $this->aiReport->getByProject($id, [
            'type' => 'quick_wins',
            'limit' => 1
        ])[0] ?? null;

        // Stats riassuntive
        $stats = $this->project->getStats($id);

        return View::render('seo-tracking/dashboard/index', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'stats' => $stats,
            'gscComparison' => $gscComparison,
            'ga4Comparison' => $ga4Comparison,
            'topKeywords' => $topKeywords,
            'topMovers' => $topMovers,
            'recentAlerts' => $recentAlerts,
            'recentReports' => $recentReports,
            'lastQuickWins' => $lastQuickWins,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
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

    /**
     * Vista pages con paginazione SQL
     */
    public function pages(int $id): string
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
            'sort' => $_GET['sort'] ?? 'clicks',
            'dir' => $_GET['dir'] ?? 'desc',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d', strtotime('-1 day')),
        ];

        // Query paginata SQL (NON carica tutto in memoria)
        $result = $this->getFilteredPagesPaginated($id, $filters);

        // Stats aggregate (query separata, leggera)
        $stats = $this->calculatePagesStats($id, $filters);

        return View::render('seo-tracking/dashboard/pages', [
            'title' => $project['name'] . ' - Pagine',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'pages' => $result['items'],
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
     * Query pagine con paginazione SQL (LIMIT/OFFSET)
     */
    private function getFilteredPagesPaginated(int $projectId, array $filters): array
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

        $searchWhere = '';
        $params = [$projectId, $dateFrom, $dateTo];

        if (!empty($search)) {
            $searchWhere = "AND page LIKE ?";
            $params[] = '%' . $search . '%';
        }

        $sortBy = in_array($filters['sort'] ?? 'clicks', ['clicks', 'impressions', 'position', 'ctr', 'keywords'])
                  ? $filters['sort'] : 'clicks';
        $sortDir = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Query principale con LIMIT - molto più veloce
        $sql = "
            SELECT
                page as url,
                COUNT(DISTINCT query) as keywords,
                SUM(clicks) as clicks,
                SUM(impressions) as impressions,
                ROUND(AVG(position), 1) as position,
                ROUND(SUM(clicks) * 100.0 / NULLIF(SUM(impressions), 0), 2) as ctr
            FROM st_gsc_data
            WHERE project_id = ?
            AND date BETWEEN ? AND ?
            AND page IS NOT NULL AND page != ''
            {$searchWhere}
            GROUP BY page
            ORDER BY {$sortBy} {$sortDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // COUNT con limit per evitare timeout
        $countParams = [$projectId, $dateFrom, $dateTo];
        if (!empty($search)) {
            $countParams[] = '%' . $search . '%';
        }

        $countSql = "SELECT COUNT(*) as cnt FROM (
            SELECT 1 FROM st_gsc_data
            WHERE project_id = ? AND date BETWEEN ? AND ? AND page IS NOT NULL AND page != '' {$searchWhere}
            GROUP BY page
            LIMIT 10001
        ) as sub";

        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $countResult = $stmt->fetch(\PDO::FETCH_ASSOC);
        $total = min((int)$countResult['cnt'], 10000);

        $totalPages = max(1, ceil($total / $perPage));

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => min($totalPages, 200),
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages && $page < 200,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }

    /**
     * Stats pagine (query aggregata leggera)
     */
    private function calculatePagesStats(int $projectId, array $filters): array
    {
        $where = "project_id = ? AND date BETWEEN ? AND ? AND page IS NOT NULL AND page != ''";
        $params = [$projectId, $filters['date_from'], $filters['date_to']];

        if (!empty($filters['search'])) {
            $where .= " AND page LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql = "
            SELECT
                COUNT(DISTINCT page) as total_pages,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions
            FROM st_gsc_data
            WHERE {$where}
        ";

        $row = Database::fetch($sql, $params);

        return [
            'total_pages' => (int) ($row['total_pages'] ?? 0),
            'total_clicks' => (int) ($row['total_clicks'] ?? 0),
            'total_impressions' => (int) ($row['total_impressions'] ?? 0),
            'avg_ctr' => ($row['total_impressions'] ?? 0) > 0
                ? round(($row['total_clicks'] / $row['total_impressions']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Elimina singola pagina (record GSC)
     */
    public function deletePage(int $projectId, int $pageId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato'], 404);
            return;
        }

        Database::query(
            "DELETE FROM st_gsc_data WHERE id = ? AND project_id = ?",
            [$pageId, $projectId]
        );

        $this->jsonResponse(['success' => true, 'message' => 'Pagina eliminata']);
    }

    /**
     * Elimina bulk pagine
     */
    public function bulkDeletePages(int $projectId): void
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

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $params = array_merge($ids, [$projectId]);
        Database::query(
            "DELETE FROM st_gsc_data WHERE id IN ({$placeholders}) AND project_id = ?",
            $params
        );

        $this->jsonResponse(['success' => true, 'message' => count($ids) . ' pagine eliminate']);
    }

    /**
     * Vista revenue
     */
    public function revenue(int $id): string
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

        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $prevEndDate = date('Y-m-d', strtotime('-31 days'));
        $prevStartDate = date('Y-m-d', strtotime('-60 days'));

        // Revenue comparison
        $ga4Comparison = $this->ga4Daily->comparePeriods($id, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // Top keyword per revenue
        $topKeywordsByRevenue = $this->keywordRevenue->getTopByRevenue($id, $startDate, $endDate, 30);

        // Revenue giornaliero
        $dailyRevenue = $this->keywordRevenue->getDailyRevenue($id, $startDate, $endDate);

        // Top pages per revenue
        $topPagesByRevenue = $this->keywordRevenue->getTopPagesByRevenue($id, $startDate, $endDate, 20);

        return View::render('seo-tracking/dashboard/revenue', [
            'title' => $project['name'] . ' - Revenue',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'ga4Comparison' => $ga4Comparison,
            'topKeywordsByRevenue' => $topKeywordsByRevenue,
            'dailyRevenue' => $dailyRevenue,
            'topPagesByRevenue' => $topPagesByRevenue,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}
