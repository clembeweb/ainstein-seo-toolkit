<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Services\BudgetScoreCalculator;

/**
 * BudgetResultsController
 *
 * Gestisce la sezione "Crawl Budget" nel SEO Audit.
 * Mostra punteggio budget, redirect, pagine spreco e indicizzabilita.
 */
class BudgetResultsController
{
    private Project $projectModel;
    private Page $pageModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
    }

    /**
     * Panoramica Crawl Budget: score + KPI + distribuzione status + top redirect chains
     */
    public function overview(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $budgetCalc = new BudgetScoreCalculator();
        $budgetResult = $budgetCalc->calculate($id);

        $statusDist = $this->pageModel->getStatusDistribution($id);
        $topChains = $this->pageModel->getTopRedirectChains($id);

        return View::render('seo-audit/budget/overview', [
            'title' => 'Crawl Budget — ' . ($project['domain'] ?? $project['name']),
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'budget',
            'budgetScore' => $budgetResult,
            'statusDistribution' => $statusDist,
            'topChains' => $topChains,
        ]);
    }

    /**
     * Problemi Redirect con paginazione
     */
    public function redirects(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $page = (int) ($_GET['page'] ?? 1);
        $severity = $_GET['severity'] ?? null;

        $issuesData = $this->pageModel->getBudgetIssuePages($id, 'redirect', $page, 25, $severity);

        return View::render('seo-audit/budget/redirects', [
            'title' => 'Redirect — Crawl Budget — ' . ($project['domain'] ?? $project['name']),
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'budget',
            'issues' => $issuesData['data'],
            'pagination' => [
                'current_page' => $issuesData['current_page'],
                'last_page' => $issuesData['last_page'],
                'total' => $issuesData['total'],
                'from' => $issuesData['total'] > 0 ? (($issuesData['current_page'] - 1) * $issuesData['per_page']) + 1 : 0,
                'to' => min($issuesData['current_page'] * $issuesData['per_page'], $issuesData['total']),
            ],
            'filters' => ['severity' => $severity],
            'activeSubTab' => 'redirects',
        ]);
    }

    /**
     * Pagine Spreco con paginazione
     */
    public function waste(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $page = (int) ($_GET['page'] ?? 1);
        $severity = $_GET['severity'] ?? null;

        $issuesData = $this->pageModel->getBudgetIssuePages($id, 'waste', $page, 25, $severity);

        return View::render('seo-audit/budget/waste', [
            'title' => 'Pagine Spreco — Crawl Budget — ' . ($project['domain'] ?? $project['name']),
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'budget',
            'issues' => $issuesData['data'],
            'pagination' => [
                'current_page' => $issuesData['current_page'],
                'last_page' => $issuesData['last_page'],
                'total' => $issuesData['total'],
                'from' => $issuesData['total'] > 0 ? (($issuesData['current_page'] - 1) * $issuesData['per_page']) + 1 : 0,
                'to' => min($issuesData['current_page'] * $issuesData['per_page'], $issuesData['total']),
            ],
            'filters' => ['severity' => $severity],
            'activeSubTab' => 'waste',
        ]);
    }

    /**
     * Problemi Indicizzabilita con paginazione
     */
    public function indexability(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $page = (int) ($_GET['page'] ?? 1);
        $severity = $_GET['severity'] ?? null;

        $issuesData = $this->pageModel->getBudgetIssuePages($id, 'indexability', $page, 25, $severity);

        return View::render('seo-audit/budget/indexability', [
            'title' => 'Indicizzabilità — Crawl Budget — ' . ($project['domain'] ?? $project['name']),
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'budget',
            'issues' => $issuesData['data'],
            'pagination' => [
                'current_page' => $issuesData['current_page'],
                'last_page' => $issuesData['last_page'],
                'total' => $issuesData['total'],
                'from' => $issuesData['total'] > 0 ? (($issuesData['current_page'] - 1) * $issuesData['per_page']) + 1 : 0,
                'to' => min($issuesData['current_page'] * $issuesData['per_page'], $issuesData['total']),
            ],
            'filters' => ['severity' => $severity],
            'activeSubTab' => 'indexability',
        ]);
    }
}
