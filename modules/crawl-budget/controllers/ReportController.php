<?php

namespace Modules\CrawlBudget\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\CrawlBudget\Models\Project;
use Modules\CrawlBudget\Models\CrawlSession;
use Modules\CrawlBudget\Models\Report;
use Modules\CrawlBudget\Services\BudgetReportService;

/**
 * ReportController
 *
 * Generazione e visualizzazione report AI.
 * Pattern AJAX lungo (non SSE — singola chiamata AI 30-60s).
 */
class ReportController
{
    private Project $projectModel;
    private CrawlSession $sessionModel;
    private Report $reportModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->sessionModel = new CrawlSession();
        $this->reportModel = new Report();
    }

    /**
     * POST /crawl-budget/projects/{id}/report/generate
     *
     * Genera report AI. Pattern AJAX lungo con ob_start.
     */
    public function generate(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        // Pattern AJAX lungo
        ignore_user_abort(true);
        set_time_limit(300);
        ob_start();
        header('Content-Type: application/json');
        session_write_close();

        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            ob_end_clean();
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Trova ultima sessione completata
        $session = $this->sessionModel->findLatestByProject($id);
        if (!$session || $session['status'] !== CrawlSession::STATUS_COMPLETED) {
            ob_end_clean();
            echo json_encode(['error' => true, 'message' => 'Nessun crawl completato per questo progetto']);
            exit;
        }

        // Verifica crediti
        $cost = Credits::getCost('report_generate', 'crawl-budget', 5);
        if (!Credits::hasEnough($user['id'], $cost)) {
            ob_end_clean();
            echo json_encode(['error' => true, 'message' => "Crediti insufficienti ({$cost} necessari)"]);
            exit;
        }

        // Genera report AI
        $reportService = new BudgetReportService();
        $result = $reportService->generate($id, (int) $session['id'], $user['id']);

        Database::reconnect();

        ob_end_clean();
        echo json_encode($result);
        exit;
    }

    /**
     * GET /crawl-budget/projects/{id}/report
     *
     * Visualizza ultimo report AI.
     */
    public function view(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        // Trova ultimo report
        $report = $this->reportModel->findLatestByProject($id);

        if (!$report) {
            $_SESSION['_flash']['error'] = 'Nessun report disponibile. Genera prima un report AI.';
            header('Location: ' . url('/crawl-budget/projects/' . $id));
            exit;
        }

        // Decode JSON fields
        if (!empty($report['priority_actions']) && is_string($report['priority_actions'])) {
            $report['priority_actions'] = json_decode($report['priority_actions'], true);
        }
        if (!empty($report['estimated_impact']) && is_string($report['estimated_impact'])) {
            $report['estimated_impact'] = json_decode($report['estimated_impact'], true);
        }

        $reportCost = Credits::getCost('report_generate', 'crawl-budget', 5);
        $creditBalance = Credits::getBalance($user['id']);

        return View::render('crawl-budget::report/view', [
            'title' => $project['name'] . ' - Report AI',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'report' => $report,
            'currentPage' => 'report',
            'credits' => [
                'balance' => $creditBalance,
                'report_cost' => $reportCost,
            ],
        ]);
    }
}
