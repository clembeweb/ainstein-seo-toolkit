<?php

namespace Modules\CrawlBudget\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\ModuleLoader;
use Modules\CrawlBudget\Models\Project;
use Modules\CrawlBudget\Models\CrawlSession;
use Modules\CrawlBudget\Models\Issue;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Models\Report;
use Modules\CrawlBudget\Services\BudgetAnalyzerService;

/**
 * ProjectController
 *
 * CRUD progetti e dashboard Crawl Budget Optimizer
 */
class ProjectController
{
    private Project $projectModel;
    private CrawlSession $sessionModel;
    private Issue $issueModel;
    private Page $pageModel;
    private Report $reportModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->sessionModel = new CrawlSession();
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
        $this->reportModel = new Report();
    }

    /**
     * Lista progetti utente
     */
    public function index(): string
    {
        Middleware::auth();
        $user = Auth::user();
        $projects = $this->projectModel->allWithStats($user['id']);

        $reportCost = Credits::getCost('report_generate', 'crawl-budget', 5);
        $creditBalance = Credits::getBalance($user['id']);

        return View::render('crawl-budget::projects/index', [
            'title' => 'Crawl Budget Optimizer - Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
            'credits' => [
                'balance' => $creditBalance,
                'report_cost' => $reportCost,
            ],
        ]);
    }

    /**
     * Dashboard progetto con score e riepilogo
     */
    public function dashboard(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        // Sessione piu recente
        $session = $this->sessionModel->findLatestByProject($id);

        $issueSummary = [];
        $severityCounts = ['critical' => 0, 'warning' => 0, 'notice' => 0];
        $totalPages = 0;
        $score = 0;
        $scoreLabel = 'N/A';
        $scoreColor = 'slate';
        $statusDistribution = [];
        $report = null;

        if ($session && $session['status'] === CrawlSession::STATUS_COMPLETED) {
            $sessionId = (int) $session['id'];

            $issueSummary = $this->issueModel->getSummaryBySession($sessionId);
            $severityCounts = $this->issueModel->countBySeverity($sessionId);
            $totalPages = $this->pageModel->countBySession($sessionId, 'crawled');
            $statusDistribution = $this->pageModel->getStatusDistribution($sessionId);

            $score = (int) ($project['crawl_budget_score'] ?? 0);
            $scoreLabel = BudgetAnalyzerService::getScoreLabel($score);
            $scoreColor = BudgetAnalyzerService::getScoreColor($score);

            $report = $this->reportModel->findBySession($sessionId);
        }

        $reportCost = Credits::getCost('report_generate', 'crawl-budget', 5);
        $creditBalance = Credits::getBalance($user['id']);

        return View::render('crawl-budget::dashboard', [
            'title' => $project['name'] . ' - Crawl Budget',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'session' => $session,
            'issueSummary' => $issueSummary,
            'severityCounts' => $severityCounts,
            'totalPages' => $totalPages,
            'score' => $score,
            'scoreLabel' => $scoreLabel,
            'scoreColor' => $scoreColor,
            'statusDistribution' => $statusDistribution,
            'report' => $report,
            'currentPage' => 'dashboard',
            'credits' => [
                'balance' => $creditBalance,
                'report_cost' => $reportCost,
            ],
        ]);
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url('/crawl-budget/projects/' . $id));
            exit;
        }

        $creditBalance = Credits::getBalance($user['id']);

        return View::render('crawl-budget::projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'settings',
            'credits' => [
                'balance' => $creditBalance,
            ],
        ]);
    }

    /**
     * Aggiorna impostazioni progetto
     */
    public function updateSettings(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url('/crawl-budget/projects/' . $id . '/settings'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $maxPages = (int) ($_POST['max_pages'] ?? 500);

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome è obbligatorio';
        }

        if ($maxPages < 10 || $maxPages > 5000) {
            $errors[] = 'Il limite pagine deve essere tra 10 e 5000';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            header('Location: ' . url('/crawl-budget/projects/' . $id . '/settings'));
            exit;
        }

        try {
            $this->projectModel->update($id, [
                'name' => $name,
                'max_pages' => $maxPages,
            ], $user['id']);

            $_SESSION['_flash']['success'] = 'Impostazioni aggiornate';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        header('Location: ' . url('/crawl-budget/projects/' . $id . '/settings'));
        exit;
    }

    /**
     * Elimina progetto
     */
    public function destroy(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url('/crawl-budget/projects/' . $id . '/settings'));
            exit;
        }

        try {
            $this->projectModel->delete($id, $user['id']);
            $_SESSION['_flash']['success'] = 'Progetto eliminato';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        header('Location: ' . url('/crawl-budget/projects'));
        exit;
    }
}
