<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Services\AuditAnalyzer;

/**
 * AuditController
 *
 * Gestisce dashboard, categorie e dettagli audit
 */
class AuditController
{
    private Project $projectModel;
    private Page $pageModel;
    private Issue $issueModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
    }

    /**
     * Dashboard progetto audit
     */
    public function dashboard(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Statistiche per categoria
        $analyzer = new AuditAnalyzer();
        $categoryStats = $analyzer->getStatsByCategory($id);

        // Top issues (critiche prima)
        $topIssues = $this->projectModel->getTopIssues($id, 10);

        // Stats pagine
        $pageStats = $this->pageModel->getStats($id);

        // Issue counts by severity
        $issueCounts = $this->issueModel->countBySeverity($id);

        // Crediti per AI
        $creditBalance = Credits::getBalance($user['id']);
        $aiOverviewCost = Credits::getCost('ai_overview') ?? 15;

        return View::render('seo-audit/audit/dashboard', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'categoryStats' => $categoryStats,
            'topIssues' => $topIssues,
            'pageStats' => $pageStats,
            'issueCounts' => $issueCounts,
            'credits' => [
                'balance' => $creditBalance,
                'ai_cost' => $aiOverviewCost,
            ],
        ]);
    }

    /**
     * Dettaglio categoria
     */
    public function category(int $id, string $slug): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Verifica categoria valida
        if (!isset(Issue::CATEGORIES[$slug])) {
            $_SESSION['flash_error'] = 'Categoria non valida';
            header('Location: ' . url('/seo-audit/project/' . $id . '/dashboard'));
            exit;
        }

        $categoryLabel = Issue::CATEGORIES[$slug];

        // Filtri
        $page = (int) ($_GET['page'] ?? 1);
        $severity = $_GET['severity'] ?? null;
        $issueType = $_GET['issue_type'] ?? null;

        $filters = ['category' => $slug];
        if ($severity) $filters['severity'] = $severity;
        if ($issueType) $filters['issue_type'] = $issueType;

        // Issues paginati (25 per pagina)
        $issuesData = $this->issueModel->getByProject($id, $page, 25, $filters);

        // Tipi issue per questa categoria
        $issueTypes = [];
        foreach (Issue::ISSUE_TYPES as $type => $info) {
            if ($info['category'] === $slug) {
                $issueTypes[$type] = $info['title'];
            }
        }

        // Conteggi per filtri
        $categoryCounts = $this->issueModel->countBySeverity($id);

        return View::render('seo-audit/audit/category', [
            'title' => $project['name'] . ' - ' . $categoryLabel,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'category' => $slug,
            'categoryLabel' => $categoryLabel,
            'issues' => $issuesData['data'],
            'issueTypes' => $issueTypes,
            'pagination' => [
                'current_page' => $issuesData['current_page'],
                'last_page' => $issuesData['last_page'],
                'total' => $issuesData['total'],
                'from' => $issuesData['from'],
                'to' => $issuesData['to'],
            ],
            'filters' => [
                'severity' => $severity,
                'issue_type' => $issueType,
            ],
            'categoryCounts' => $categoryCounts,
        ]);
    }

    /**
     * Lista tutte le issues con filtri
     */
    public function issues(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Filtri
        $page = (int) ($_GET['page'] ?? 1);
        $filters = [];

        if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];
        if (!empty($_GET['severity'])) $filters['severity'] = $_GET['severity'];
        if (!empty($_GET['issue_type'])) $filters['issue_type'] = $_GET['issue_type'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

        // Issues paginati (25 per pagina)
        $issuesData = $this->issueModel->getByProject($id, $page, 25, $filters);

        // Stats per filtri
        $issueCounts = $this->issueModel->countBySeverity($id);
        $categoryStats = $this->issueModel->countByCategory($id);

        return View::render('seo-audit/audit/issues', [
            'title' => $project['name'] . ' - Issues',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'issues' => $issuesData['data'],
            'pagination' => [
                'current_page' => $issuesData['current_page'],
                'last_page' => $issuesData['last_page'],
                'total' => $issuesData['total'],
                'from' => $issuesData['from'],
                'to' => $issuesData['to'],
            ],
            'filters' => $filters,
            'issueCounts' => $issueCounts,
            'categoryStats' => $categoryStats,
        ]);
    }

    /**
     * Dettaglio singola pagina
     */
    public function pageDetail(int $id, int $pageId): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $page = $this->pageModel->findWithDetails($pageId, $id);

        if (!$page) {
            $_SESSION['flash_error'] = 'Pagina non trovata';
            header('Location: ' . url('/seo-audit/project/' . $id . '/pages'));
            exit;
        }

        // Issues della pagina
        $pageIssues = $this->issueModel->getByPage($pageId);

        // Decodifica JSON fields
        $h1Texts = json_decode($page['h1_texts'] ?? '[]', true);
        $imagesData = json_decode($page['images_data'] ?? '[]', true);
        $linksData = json_decode($page['links_data'] ?? '[]', true);
        $schemaTypes = json_decode($page['schema_types'] ?? '[]', true);
        $hreflangTags = json_decode($page['hreflang_tags'] ?? '[]', true);

        return View::render('seo-audit/audit/page-detail', [
            'title' => 'Dettaglio Pagina - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'page' => $page,
            'pageIssues' => $pageIssues,
            'h1Texts' => $h1Texts,
            'imagesData' => $imagesData,
            'linksData' => $linksData,
            'schemaTypes' => $schemaTypes,
            'hreflangTags' => $hreflangTags,
        ]);
    }

    /**
     * Lista pagine crawlate
     */
    public function pages(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Filtri
        $page = (int) ($_GET['page'] ?? 1);
        $filters = [];

        if (!empty($_GET['status_code'])) $filters['status_code'] = $_GET['status_code'];
        if (isset($_GET['is_indexable'])) $filters['is_indexable'] = $_GET['is_indexable'];
        if (!empty($_GET['has_issues'])) $filters['has_issues'] = true;
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

        // Pagine (25 per pagina)
        $pagesData = $this->pageModel->getByProject($id, $page, 25, $filters);

        // Stats
        $pageStats = $this->pageModel->getStats($id);

        return View::render('seo-audit/audit/pages', [
            'title' => $project['name'] . ' - Pagine',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'pages' => $pagesData['data'],
            'pagination' => [
                'current_page' => $pagesData['current_page'],
                'last_page' => $pagesData['last_page'],
                'total' => $pagesData['total'],
                'from' => $pagesData['from'],
                'to' => $pagesData['to'],
            ],
            'filters' => $filters,
            'pageStats' => $pageStats,
        ]);
    }

    /**
     * Pagina analisi AI overview
     */
    public function analysis(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Ottieni analisi esistente
        $aiService = new \Modules\SeoAudit\Services\AiAnalysisService();
        $analysis = $aiService->getAnalysis($id, 'overview');

        // Costi
        $overviewCost = $aiService->getCost('overview');
        $creditBalance = Credits::getBalance($user['id']);

        // Statistiche per contesto
        $issueCounts = $this->issueModel->countBySeverity($id);

        return View::render('seo-audit/analysis/overview', [
            'title' => $project['name'] . ' - Analisi AI',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'analysis' => $analysis,
            'issueCounts' => $issueCounts,
            'credits' => [
                'balance' => $creditBalance,
                'overview_cost' => $overviewCost,
            ],
        ]);
    }

    /**
     * Pagina analisi AI per categoria
     */
    public function analysisCategory(int $id, string $category): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Verifica categoria valida
        if (!isset(Issue::CATEGORIES[$category])) {
            $_SESSION['flash_error'] = 'Categoria non valida';
            header('Location: ' . url('/seo-audit/project/' . $id . '/dashboard'));
            exit;
        }

        // Ottieni analisi esistente
        $aiService = new \Modules\SeoAudit\Services\AiAnalysisService();
        $analysis = $aiService->getAnalysis($id, 'category', $category);

        // Costi
        $categoryCost = $aiService->getCost('category');
        $creditBalance = Credits::getBalance($user['id']);

        // Statistiche categoria
        $categoryStats = $this->issueModel->countByCategory($id);
        $stats = $categoryStats[$category] ?? ['total' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0];

        return View::render('seo-audit/analysis/category', [
            'title' => $project['name'] . ' - Analisi ' . Issue::CATEGORIES[$category],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'category' => $category,
            'categoryLabel' => Issue::CATEGORIES[$category],
            'analysis' => $analysis,
            'categoryStats' => $stats,
            'credits' => [
                'balance' => $creditBalance,
                'category_cost' => $categoryCost,
            ],
        ]);
    }

    /**
     * Genera analisi AI overview (POST AJAX)
     */
    public function analyzeOverview(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Verifica che ci siano issues da analizzare
        $issueCounts = $this->issueModel->countBySeverity($id);
        if ($issueCounts['total'] === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna issue da analizzare. Esegui prima un crawl.']);
            exit;
        }

        $aiService = new \Modules\SeoAudit\Services\AiAnalysisService();
        $result = $aiService->analyzeOverview($id, $user['id']);

        echo json_encode($result);
        exit;
    }

    /**
     * Genera analisi AI categoria (POST AJAX)
     */
    public function analyzeCategory(int $id, string $category): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Verifica categoria valida
        if (!isset(Issue::CATEGORIES[$category])) {
            echo json_encode(['error' => true, 'message' => 'Categoria non valida']);
            exit;
        }

        // Verifica che ci siano issues in questa categoria
        $categoryStats = $this->issueModel->countByCategory($id);
        $stats = $categoryStats[$category] ?? ['total' => 0];
        if ($stats['total'] === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna issue in questa categoria da analizzare.']);
            exit;
        }

        $aiService = new \Modules\SeoAudit\Services\AiAnalysisService();
        $result = $aiService->analyzeCategory($id, $category, $user['id']);

        echo json_encode($result);
        exit;
    }
}
