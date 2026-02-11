<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Core\Middleware;

class AiLogsController
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
            'module' => $_GET['module'] ?? '',
            'provider' => $_GET['provider'] ?? '',
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        // Costruisci query
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['module'])) {
            $where[] = 'module_slug = ?';
            $params[] = $filters['module'];
        }

        if (!empty($filters['provider'])) {
            $where[] = 'provider = ?';
            $params[] = $filters['provider'];
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
            $where[] = '(request_payload LIKE ? OR response_payload LIKE ? OR error_message LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Count totale
        $totalQuery = "SELECT COUNT(*) as total FROM ai_logs WHERE {$whereClause}";
        $total = Database::fetch($totalQuery, $params)['total'] ?? 0;
        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;

        // Fetch logs
        $query = "
            SELECT id, user_id, module_slug, provider, model,
                   tokens_input, tokens_output, tokens_total,
                   duration_ms, status, error_message, fallback_from,
                   estimated_cost, credits_used, created_at
            FROM ai_logs
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $logs = Database::fetchAll($query, $params);

        // Moduli distinti per filtro
        $modules = Database::fetchAll("SELECT DISTINCT module_slug FROM ai_logs ORDER BY module_slug");

        // Statistiche rapide
        $stats = $this->getStats();

        return View::render('admin/ai-logs/index', [
            'title' => 'AI Logs',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'logs' => $logs,
            'filters' => $filters,
            'modulesList' => array_column($modules, 'module_slug'),
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
        $log = Database::fetch("SELECT * FROM ai_logs WHERE id = ?", [$id]);

        if (!$log) {
            $_SESSION['_flash']['error'] = 'Log non trovato';
            header('Location: ' . url('/admin/ai-logs'));
            exit;
        }

        // Decodifica payload per visualizzazione
        $log['request_decoded'] = json_decode($log['request_payload'] ?? '{}', true);
        $log['response_decoded'] = json_decode($log['response_payload'] ?? 'null', true);

        return View::render('admin/ai-logs/show', [
            'title' => 'AI Log #' . $log['id'],
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
            header('Location: ' . url('/admin/ai-logs'));
            exit;
        }

        // Verifica CSRF
        Middleware::csrf();

        $days = intval($_POST['days'] ?? 30);
        $days = max(7, min(365, $days)); // Min 7, max 365

        $result = Database::execute(
            "DELETE FROM ai_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        $_SESSION['_flash']['success'] = "Eliminati {$result} log più vecchi di {$days} giorni";
        header('Location: ' . url('/admin/ai-logs'));
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
                SUM(CASE WHEN status = 'fallback' THEN 1 ELSE 0 END) as fallbacks,
                SUM(tokens_total) as tokens,
                SUM(estimated_cost) as cost
            FROM ai_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");

        // Ultimi 30 giorni
        $last30d = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(tokens_total) as tokens,
                SUM(estimated_cost) as cost
            FROM ai_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        // Breakdown per provider (ultimi 30 giorni)
        $byProvider = Database::fetchAll("
            SELECT
                provider,
                COUNT(*) as calls,
                SUM(tokens_total) as tokens,
                SUM(estimated_cost) as cost,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN status = 'fallback' THEN 1 ELSE 0 END) as fallbacks
            FROM ai_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY provider
            ORDER BY calls DESC
        ");

        // Modello piu' usato
        $topModel = Database::fetch("
            SELECT model, COUNT(*) as cnt
            FROM ai_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'success'
            GROUP BY model
            ORDER BY cnt DESC
            LIMIT 1
        ");

        // Trend giornaliero (ultimi 7 giorni)
        $dailyTrend = Database::fetchAll("
            SELECT
                DATE(created_at) as day,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(tokens_total) as tokens,
                SUM(estimated_cost) as cost
            FROM ai_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

        return [
            'last_24h' => $last24h ?: ['total' => 0, 'success' => 0, 'errors' => 0, 'fallbacks' => 0, 'tokens' => 0, 'cost' => 0],
            'last_30d' => $last30d ?: ['total' => 0, 'tokens' => 0, 'cost' => 0],
            'by_provider' => $byProvider ?: [],
            'top_model' => $topModel ?: null,
            'daily_trend' => $dailyTrend ?: [],
        ];
    }
}
