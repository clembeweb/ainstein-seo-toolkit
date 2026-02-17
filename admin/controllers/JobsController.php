<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Core\Middleware;

/**
 * JobsController
 *
 * Monitoraggio centralizzato dei job di background per tutti i moduli.
 * Accessibile solo da admin, con tab per modulo.
 */
class JobsController
{
    /**
     * Configurazione tabelle job per modulo
     */
    private array $modules = [
        'ai-content' => [
            'table' => 'aic_process_jobs',
            'label' => 'AI Content',
            'project_table' => 'aic_projects',
            'items_col' => 'keywords_requested',
            'completed_col' => 'keywords_completed',
            'failed_col' => 'keywords_failed',
            'extra_cols' => ['current_step', 'current_keyword', 'articles_generated'],
        ],
        'ai-content-meta' => [
            'table' => 'aic_scrape_jobs',
            'label' => 'AI Content (Meta)',
            'project_table' => 'aic_projects',
            'items_col' => 'items_requested',
            'completed_col' => 'items_completed',
            'failed_col' => 'items_failed',
            'extra_cols' => ['current_item'],
        ],
        'seo-tracking' => [
            'table' => 'st_rank_jobs',
            'label' => 'SEO Tracking',
            'project_table' => 'st_projects',
            'items_col' => 'keywords_requested',
            'completed_col' => 'keywords_completed',
            'failed_col' => 'keywords_failed',
            'extra_cols' => ['keywords_found', 'avg_position', 'current_keyword'],
        ],
        'content-creator' => [
            'table' => 'cc_jobs',
            'label' => 'Content Creator',
            'project_table' => 'cc_projects',
            'items_col' => 'items_requested',
            'completed_col' => 'items_completed',
            'failed_col' => 'items_failed',
            'extra_cols' => ['current_item'],
        ],
        'seo-onpage' => [
            'table' => 'sop_jobs',
            'label' => 'SEO Onpage',
            'project_table' => 'sop_projects',
            'items_col' => 'pages_requested',
            'completed_col' => 'pages_completed',
            'failed_col' => 'pages_failed',
            'extra_cols' => ['current_url', 'avg_score', 'total_issues'],
        ],
    ];

    public function __construct()
    {
        Middleware::admin();
    }

    /**
     * Pagina principale con tab per modulo
     * GET /admin/jobs
     */
    public function index(): string
    {
        $activeTab = $_GET['tab'] ?? 'ai-content';
        if (!isset($this->modules[$activeTab])) {
            $activeTab = 'ai-content';
        }

        $filters = [
            'status' => $_GET['status'] ?? '',
            'user' => $_GET['user'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;

        // Carica dati per il tab attivo
        $tabConfig = $this->modules[$activeTab];
        $jobs = $this->getJobs($tabConfig, $filters, $page, $perPage);
        $total = $this->countJobs($tabConfig, $filters);
        $stats = $this->getStats($tabConfig);

        // Stats aggregate cross-modulo (per header)
        $globalStats = $this->getGlobalStats();

        // Utenti per filtro dropdown
        $users = Database::fetchAll("SELECT DISTINCT u.id, u.name, u.email FROM users u ORDER BY u.name");

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return View::render('admin/jobs/index', [
            'title' => 'Jobs Monitor',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'tabModules' => $this->modules,
            'activeTab' => $activeTab,
            'jobs' => $jobs,
            'stats' => $stats,
            'globalStats' => $globalStats,
            'users' => $users,
            'filters' => $filters,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'total_items' => $total,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Cancella un job specifico
     * POST /admin/jobs/cancel
     */
    public function cancelJob(): void
    {
        Middleware::csrf();

        $jobId = (int) ($_POST['job_id'] ?? 0);
        $module = $_POST['module'] ?? '';

        if (!$jobId || !isset($this->modules[$module])) {
            $this->jsonResponse(['success' => false, 'error' => 'Parametri non validi']);
            return;
        }

        $table = $this->modules[$module]['table'];
        $job = Database::fetch("SELECT * FROM {$table} WHERE id = ?", [$jobId]);

        if (!$job) {
            $this->jsonResponse(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        if (!in_array($job['status'], ['pending', 'running'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Solo job pending o running possono essere cancellati']);
            return;
        }

        Database::update($table, [
            'status' => 'cancelled',
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]);

        $this->jsonResponse(['success' => true, 'message' => 'Job #' . $jobId . ' cancellato']);
    }

    /**
     * Cancella job bloccati (running da troppo tempo)
     * POST /admin/jobs/cancel-stuck
     */
    public function cancelStuck(): void
    {
        Middleware::csrf();

        $module = $_POST['module'] ?? '';
        $minutes = max(5, (int) ($_POST['minutes'] ?? 30));

        if (!isset($this->modules[$module])) {
            $this->jsonResponse(['success' => false, 'error' => 'Modulo non valido']);
            return;
        }

        $table = $this->modules[$module]['table'];
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $result = Database::query(
            "UPDATE {$table}
             SET status = 'error',
                 error_message = 'Timeout - bloccato da oltre {$minutes} minuti (reset admin)',
                 completed_at = NOW()
             WHERE status = 'running'
               AND started_at < ?",
            [$cutoffTime]
        );

        $count = $result ? $result->rowCount() : 0;

        $this->jsonResponse([
            'success' => true,
            'message' => "{$count} job bloccati cancellati in {$this->modules[$module]['label']}"
        ]);
    }

    /**
     * Pulisci job vecchi
     * POST /admin/jobs/cleanup
     */
    public function cleanup(): void
    {
        Middleware::csrf();

        $module = $_POST['module'] ?? '';
        $days = max(1, (int) ($_POST['days'] ?? 7));

        if (!isset($this->modules[$module])) {
            $this->jsonResponse(['success' => false, 'error' => 'Modulo non valido']);
            return;
        }

        $table = $this->modules[$module]['table'];
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = Database::query(
            "DELETE FROM {$table}
             WHERE status IN ('completed', 'error', 'cancelled')
               AND created_at < ?",
            [$cutoffDate]
        );

        $count = $result ? $result->rowCount() : 0;

        $this->jsonResponse([
            'success' => true,
            'message' => "Eliminati {$count} job da {$this->modules[$module]['label']} (> {$days} giorni)"
        ]);
    }

    /**
     * Carica job con filtri e paginazione
     */
    private function getJobs(array $config, array $filters, int $page, int $perPage): array
    {
        $table = $config['table'];
        $projectTable = $config['project_table'];
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "j.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user'])) {
            $where[] = "j.user_id = ?";
            $params[] = (int) $filters['user'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(j.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(j.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        return Database::fetchAll(
            "SELECT j.*, p.name AS project_name, u.name AS user_name, u.email AS user_email
             FROM {$table} j
             LEFT JOIN {$projectTable} p ON j.project_id = p.id
             LEFT JOIN users u ON j.user_id = u.id
             WHERE {$whereClause}
             ORDER BY j.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
    }

    /**
     * Conta job con filtri
     */
    private function countJobs(array $config, array $filters): int
    {
        $table = $config['table'];

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user'])) {
            $where[] = "user_id = ?";
            $params[] = (int) $filters['user'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        $result = Database::fetch(
            "SELECT COUNT(*) AS total FROM {$table} WHERE {$whereClause}",
            $params
        );

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Statistiche per un modulo specifico
     */
    private function getStats(array $config): array
    {
        $table = $config['table'];

        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'running') AS running,
                SUM(status = 'pending') AS pending,
                SUM(status = 'completed') AS completed,
                SUM(status = 'error') AS errors,
                SUM(status = 'cancelled') AS cancelled,
                SUM(credits_used) AS total_credits
             FROM {$table}"
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'running' => (int) ($row['running'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'errors' => (int) ($row['errors'] ?? 0),
            'cancelled' => (int) ($row['cancelled'] ?? 0),
            'total_credits' => round((float) ($row['total_credits'] ?? 0), 2),
        ];
    }

    /**
     * Statistiche globali cross-modulo
     */
    private function getGlobalStats(): array
    {
        $running = 0;
        $pending = 0;
        $errors24h = 0;

        foreach ($this->modules as $config) {
            $table = $config['table'];

            $row = Database::fetch(
                "SELECT
                    SUM(status = 'running') AS running,
                    SUM(status = 'pending') AS pending
                 FROM {$table}"
            );

            $running += (int) ($row['running'] ?? 0);
            $pending += (int) ($row['pending'] ?? 0);

            $errRow = Database::fetch(
                "SELECT COUNT(*) AS cnt FROM {$table}
                 WHERE status = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );

            $errors24h += (int) ($errRow['cnt'] ?? 0);
        }

        return [
            'running' => $running,
            'pending' => $pending,
            'errors_24h' => $errors24h,
        ];
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
