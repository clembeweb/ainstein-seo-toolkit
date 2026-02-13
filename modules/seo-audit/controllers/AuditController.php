<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\CrawlSession;
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
    private CrawlSession $sessionModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
        $this->sessionModel = new CrawlSession();
    }

    /**
     * Dashboard progetto audit
     */
    public function dashboard(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
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

        // Sessione crawl attiva o ultima
        $crawlSession = $this->sessionModel->findActiveByProject($id)
            ?? $this->sessionModel->findLatestByProject($id);

        $sessionStats = $crawlSession ? $this->sessionModel->getStats($crawlSession['id']) : null;

        // Crediti
        $creditBalance = Credits::getBalance($user['id']);
        $aiOverviewCost = Credits::getCost('ai_overview') ?? 15;
        $crawlCost = Credits::getCost('crawl_per_page') ?? 0.2;

        return View::render('seo-audit/audit/dashboard', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'dashboard',
            'categoryStats' => $categoryStats,
            'topIssues' => $topIssues,
            'pageStats' => $pageStats,
            'issueCounts' => $issueCounts,
            'session' => $sessionStats,
            'credits' => [
                'balance' => $creditBalance,
                'ai_cost' => $aiOverviewCost,
                'crawl_cost' => $crawlCost,
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Verifica categoria valida
        if (!isset(Issue::CATEGORIES[$slug])) {
            $_SESSION['_flash']['error'] = 'Categoria non valida';
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
            'currentPage' => 'category',
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
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
            'currentPage' => 'issues',
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $page = $this->pageModel->findWithDetails($pageId, $id);

        if (!$page) {
            $_SESSION['_flash']['error'] = 'Pagina non trovata';
            header('Location: ' . url('/seo-audit/project/' . $id . '/pages'));
            exit;
        }

        // Issues della pagina
        $pageIssues = $this->issueModel->getByPage($pageId);

        // Decodifica JSON fields - ensure arrays even if JSON is invalid
        $h1Texts = json_decode($page['h1_texts'] ?? '[]', true) ?: [];
        $imagesData = json_decode($page['images_data'] ?? '[]', true) ?: [];
        $linksData = json_decode($page['links_data'] ?? '[]', true) ?: [];
        $schemaTypes = json_decode($page['schema_types'] ?? '[]', true) ?: [];
        $hreflangTags = json_decode($page['hreflang_tags'] ?? '[]', true) ?: [];

        return View::render('seo-audit/audit/page-detail', [
            'title' => 'Dettaglio Pagina - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'page-detail',
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Filtri
        $page = (int) ($_GET['page'] ?? 1);
        $filters = [];

        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
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
            'currentPage' => 'pages',
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
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
            'currentPage' => 'analysis',
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Verifica categoria valida
        if (!isset(Issue::CATEGORIES[$category])) {
            $_SESSION['_flash']['error'] = 'Categoria non valida';
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
            'currentPage' => 'analysis-category',
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

    /**
     * Elimina pagine selezionate (API JSON)
     */
    public function deletePages(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Verify CSRF
        if (empty($input['_csrf_token']) || $input['_csrf_token'] !== csrf_token()) {
            echo json_encode(['error' => true, 'message' => 'Token CSRF non valido']);
            exit;
        }

        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['error' => true, 'message' => 'Nessuna pagina selezionata']);
            exit;
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        // Delete pages
        $deleted = $this->pageModel->deleteMultiple($ids, $id);

        // Also delete related issues
        if ($deleted > 0) {
            Database::execute(
                "DELETE FROM sa_issues WHERE project_id = ? AND page_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")",
                array_merge([$id], $ids)
            );

            // Update project stats
            $pendingCount = Database::fetch(
                "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'pending'",
                [$id]
            )['cnt'] ?? 0;

            $crawledCount = Database::fetch(
                "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'crawled'",
                [$id]
            )['cnt'] ?? 0;

            $this->projectModel->update($id, [
                'pages_found' => $pendingCount + $crawledCount,
                'pages_crawled' => $crawledCount,
            ]);
        }

        echo json_encode([
            'success' => true,
            'deleted' => $deleted,
            'message' => "{$deleted} pagine eliminate",
        ]);
        exit;
    }

    /**
     * Storico scansioni
     */
    public function history(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            Router::redirect('/seo-audit');
        }

        $sessions = Database::fetchAll("
            SELECT id, status, pages_found, pages_crawled, issues_found,
                   health_score, critical_count, warning_count, notice_count,
                   created_at, completed_at
            FROM sa_crawl_sessions
            WHERE project_id = ?
            ORDER BY id DESC
        ", [$id]);

        return View::render('seo-audit/audit/history', [
            'project' => $project,
            'sessions' => $sessions,
            'currentPage' => 'history',
            'title' => 'Storico Scansioni - ' . $project['name'],
            'user' => $user,
            'modules' => \Core\ModuleLoader::getUserModules($user['id']),
        ]);
    }
}
