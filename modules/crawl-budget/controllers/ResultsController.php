<?php

namespace Modules\CrawlBudget\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Pagination;
use Modules\CrawlBudget\Models\Project;
use Modules\CrawlBudget\Models\CrawlSession;
use Modules\CrawlBudget\Models\Issue;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Services\BudgetAnalyzerService;

/**
 * ResultsController
 *
 * Visualizzazione risultati: overview, redirect, waste, indexability, pages
 */
class ResultsController
{
    private Project $projectModel;
    private CrawlSession $sessionModel;
    private Issue $issueModel;
    private Page $pageModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->sessionModel = new CrawlSession();
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
    }

    /**
     * Overview risultati con score e metriche aggregate
     */
    public function overview(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        $session = $this->sessionModel->findLatestByProject($id);
        if (!$session || $session['status'] !== CrawlSession::STATUS_COMPLETED) {
            $_SESSION['_flash']['error'] = 'Nessun crawl completato per questo progetto';
            header('Location: ' . url('/crawl-budget/projects/' . $id));
            exit;
        }

        $sessionId = (int) $session['id'];

        $score = (int) ($project['crawl_budget_score'] ?? 0);
        $scoreLabel = BudgetAnalyzerService::getScoreLabel($score);
        $scoreColor = BudgetAnalyzerService::getScoreColor($score);

        $totalPages = $this->pageModel->countBySession($sessionId, 'crawled');
        $statusDistribution = $this->pageModel->getStatusDistribution($sessionId);
        $issueSummary = $this->issueModel->getSummaryBySession($sessionId);
        $severityCounts = $this->issueModel->countBySeverity($sessionId);
        $topChains = $this->pageModel->getTopRedirectChains($sessionId, 5);

        return View::render('crawl-budget::results/overview', [
            'title' => $project['name'] . ' - Risultati',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'session' => $session,
            'score' => $score,
            'scoreLabel' => $scoreLabel,
            'scoreColor' => $scoreColor,
            'totalPages' => $totalPages,
            'statusDistribution' => $statusDistribution,
            'issueSummary' => $issueSummary,
            'severityCounts' => $severityCounts,
            'topChains' => $topChains,
            'currentPage' => 'results',
        ]);
    }

    /**
     * Tab: Redirect issues
     */
    public function redirects(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        return $this->renderIssueTab($id, $user, 'redirect', 'redirects', 'Redirect');
    }

    /**
     * Tab: Waste pages
     */
    public function waste(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        return $this->renderIssueTab($id, $user, 'waste', 'waste', 'Pagine Spreco');
    }

    /**
     * Tab: Indexability conflicts
     */
    public function indexability(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        return $this->renderIssueTab($id, $user, 'indexability', 'indexability', 'Indexability');
    }

    /**
     * Tab: Tutte le pagine
     */
    public function pages(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        $session = $this->sessionModel->findLatestByProject($id);
        if (!$session) {
            $_SESSION['_flash']['error'] = 'Nessun crawl disponibile';
            header('Location: ' . url('/crawl-budget/projects/' . $id));
            exit;
        }

        $sessionId = (int) $session['id'];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $orderBy = $_GET['sort'] ?? 'id';
        $orderDir = $_GET['dir'] ?? 'ASC';

        $filters = [];
        if (!empty($_GET['status_code'])) {
            $filters['status_code'] = $_GET['status_code'];
        }
        if (isset($_GET['is_indexable']) && $_GET['is_indexable'] !== '') {
            $filters['is_indexable'] = (int) $_GET['is_indexable'];
        }
        if (isset($_GET['has_parameters']) && $_GET['has_parameters'] !== '') {
            $filters['has_parameters'] = (int) $_GET['has_parameters'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $pages = $this->pageModel->getBySession($sessionId, $filters, $page, $perPage, $orderBy, $orderDir);
        $totalFiltered = $this->pageModel->countFiltered($sessionId, $filters);
        $pagination = Pagination::make($totalFiltered, $page, $perPage);

        $baseUrl = url('/crawl-budget/projects/' . $id . '/results/pages');

        return View::render('crawl-budget::results/pages', [
            'title' => $project['name'] . ' - Pagine',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'session' => $session,
            'pages' => $pages,
            'pagination' => $pagination,
            'filters' => $filters,
            'sort' => $orderBy,
            'dir' => $orderDir,
            'baseUrl' => $baseUrl,
            'currentPage' => 'results',
            'currentTab' => 'pages',
        ]);
    }

    /**
     * Render generico per tab issue (redirect, waste, indexability).
     */
    private function renderIssueTab(int $id, array $user, string $category, string $viewName, string $tabLabel): string
    {
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/crawl-budget/projects'));
            exit;
        }

        $session = $this->sessionModel->findLatestByProject($id);
        if (!$session) {
            $_SESSION['_flash']['error'] = 'Nessun crawl disponibile';
            header('Location: ' . url('/crawl-budget/projects/' . $id));
            exit;
        }

        $sessionId = (int) $session['id'];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $severity = !empty($_GET['severity']) ? $_GET['severity'] : null;

        $issues = $this->issueModel->getBySession($sessionId, $category, $severity, $page, $perPage);
        $totalFiltered = $this->issueModel->countBySession($sessionId, $category, $severity);
        $pagination = Pagination::make($totalFiltered, $page, $perPage);

        $baseUrl = url('/crawl-budget/projects/' . $id . '/results/' . $viewName);

        return View::render('crawl-budget::results/' . $viewName, [
            'title' => $project['name'] . ' - ' . $tabLabel,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'session' => $session,
            'issues' => $issues,
            'pagination' => $pagination,
            'category' => $category,
            'severity' => $severity,
            'baseUrl' => $baseUrl,
            'currentPage' => 'results',
            'currentTab' => $viewName,
        ]);
    }
}
