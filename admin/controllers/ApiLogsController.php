<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Core\Middleware;
use Services\ApiLoggerService;

/**
 * ApiLogsController
 *
 * Gestione log chiamate API esterne (DataForSEO, SerpAPI, Serper, Google, ecc.)
 */
class ApiLogsController
{
    public function __construct()
    {
        Middleware::admin();
    }

    /**
     * Lista paginata con filtri
     */
    public function index(): string
    {
        // Parametri filtri
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'provider' => $_GET['provider'] ?? '',
            'module' => $_GET['module'] ?? '',
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        // Costruisci query
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['provider'])) {
            $where[] = 'provider = ?';
            $params[] = $filters['provider'];
        }

        if (!empty($filters['module'])) {
            $where[] = 'module_slug = ?';
            $params[] = $filters['module'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(endpoint LIKE ? OR request_payload LIKE ? OR response_payload LIKE ? OR error_message LIKE ? OR context LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Count totale
        $totalQuery = "SELECT COUNT(*) as total FROM api_logs WHERE {$whereClause}";
        $total = Database::fetch($totalQuery, $params)['total'] ?? 0;
        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;

        // Fetch logs
        $query = "
            SELECT id, user_id, module_slug, provider, endpoint, method,
                   response_code, duration_ms, cost, credits_used,
                   status, error_message, context, created_at
            FROM api_logs
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $logs = Database::fetchAll($query, $params);

        // Provider distinti per filtro
        $providers = Database::fetchAll("SELECT DISTINCT provider FROM api_logs ORDER BY provider");

        // Moduli distinti per filtro
        $modulesList = Database::fetchAll("SELECT DISTINCT module_slug FROM api_logs ORDER BY module_slug");

        // Statistiche rapide
        $stats = $this->getStats();

        return View::render('admin/api-logs/index', [
            'title' => 'API Logs',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'logs' => $logs,
            'filters' => $filters,
            'providersList' => array_column($providers, 'provider'),
            'modulesList' => array_column($modulesList, 'module_slug'),
            'stats' => $stats,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'total_items' => $total,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Dettaglio singolo log
     */
    public function show(int $id): string
    {
        $log = Database::fetch("SELECT * FROM api_logs WHERE id = ?", [$id]);

        if (!$log) {
            $_SESSION['_flash']['error'] = 'Log non trovato';
            header('Location: ' . url('/admin/api-logs'));
            exit;
        }

        // Decodifica payload per visualizzazione
        $log['request_decoded'] = json_decode($log['request_payload'] ?? '{}', true);
        $log['response_decoded'] = json_decode($log['response_payload'] ?? 'null', true);

        return View::render('admin/api-logs/show', [
            'title' => 'API Log #' . $log['id'],
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'log' => $log,
        ]);
    }

    /**
     * Cleanup log vecchi
     */
    public function cleanup(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/admin/api-logs'));
            exit;
        }

        // Verifica CSRF
        Middleware::csrf();

        $days = intval($_POST['days'] ?? 30);
        $days = max(7, min(365, $days)); // Min 7, max 365

        $result = Database::execute(
            "DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        $_SESSION['_flash']['success'] = "Eliminati {$result} log più vecchi di {$days} giorni";
        header('Location: ' . url('/admin/api-logs'));
        exit;
    }

    /**
     * Statistiche per dashboard
     */
    private function getStats(): array
    {
        // Ultime 24h
        $last24h = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN status = 'rate_limited' THEN 1 ELSE 0 END) as rate_limited,
                SUM(cost) as cost,
                AVG(duration_ms) as avg_duration
            FROM api_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");

        // Ultimi 30 giorni
        $last30d = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(cost) as cost,
                SUM(credits_used) as credits
            FROM api_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        // Breakdown per provider (ultimi 30 giorni)
        $byProvider = Database::fetchAll("
            SELECT
                provider,
                COUNT(*) as calls,
                SUM(cost) as cost,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM api_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY provider
            ORDER BY calls DESC
        ");

        // Trend giornaliero (ultimi 7 giorni)
        $dailyTrend = Database::fetchAll("
            SELECT
                DATE(created_at) as day,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(cost) as cost
            FROM api_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

        return [
            'last_24h' => $last24h ?: ['total' => 0, 'success' => 0, 'errors' => 0, 'rate_limited' => 0, 'cost' => 0, 'avg_duration' => 0],
            'last_30d' => $last30d ?: ['total' => 0, 'cost' => 0, 'credits' => 0],
            'by_provider' => $byProvider ?: [],
            'daily_trend' => $dailyTrend ?: [],
        ];
    }
}
