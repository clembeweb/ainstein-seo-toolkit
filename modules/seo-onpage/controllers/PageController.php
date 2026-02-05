<?php

namespace Modules\SeoOnpage\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoOnpage\Models\Project;
use Modules\SeoOnpage\Models\Page;
use Services\SitemapService;

/**
 * PageController
 * Gestisce import e visualizzazione pagine
 */
class PageController
{
    private Project $project;
    private Page $page;

    public function __construct()
    {
        $this->project = new Project();
        $this->page = new Page();
    }

    /**
     * Lista pagine del progetto
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'order_by' => $_GET['order_by'] ?? 'onpage_score',
            'order_dir' => $_GET['order_dir'] ?? 'ASC',
        ];

        $pages = $this->page->allWithAnalysis($projectId, $filters);

        return View::render('seo-onpage/pages/index', [
            'title' => 'Pagine - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'pages' => $pages,
            'filters' => $filters,
        ]);
    }

    /**
     * Form import pagine
     */
    public function import(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        return View::render('seo-onpage/pages/import', [
            'title' => 'Importa Pagine - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
        ]);
    }

    /**
     * Import da sitemap
     */
    public function importSitemap(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $sitemapUrl = trim($_POST['sitemap_url'] ?? '');

        if (empty($sitemapUrl)) {
            // Prova a costruire URL sitemap dal dominio
            $sitemapUrl = 'https://' . $project['domain'] . '/sitemap.xml';
        }

        try {
            $sitemapService = new SitemapService();
            $urls = $sitemapService->parse($sitemapUrl);

            if (empty($urls)) {
                $this->jsonResponse(['success' => false, 'error' => 'Nessun URL trovato nella sitemap']);
                return;
            }

            // Filtra solo URL dello stesso dominio
            $projectDomain = strtolower($project['domain']);
            $filteredUrls = array_filter($urls, function($url) use ($projectDomain) {
                $urlDomain = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
                $urlDomain = preg_replace('/^www\./', '', $urlDomain);
                return $urlDomain === $projectDomain || str_ends_with($urlDomain, '.' . $projectDomain);
            });

            if (empty($filteredUrls)) {
                $this->jsonResponse(['success' => false, 'error' => 'Nessun URL trovato per il dominio ' . $project['domain']]);
                return;
            }

            $result = $this->page->bulkInsert($projectId, $filteredUrls);

            $this->jsonResponse([
                'success' => true,
                'message' => "Importati {$result['inserted']} nuovi URL, {$result['skipped']} gia presenti",
                'inserted' => $result['inserted'],
                'skipped' => $result['skipped'],
                'total' => count($filteredUrls),
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Errore lettura sitemap: ' . $e->getMessage()]);
        }
    }

    /**
     * Import da CSV
     */
    public function importCsv(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $csvContent = trim($_POST['csv_content'] ?? '');

        if (empty($csvContent)) {
            $this->jsonResponse(['success' => false, 'error' => 'Contenuto CSV vuoto']);
            return;
        }

        // Parse CSV (una URL per riga)
        $lines = preg_split('/\r\n|\r|\n/', $csvContent);
        $urls = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Se la linea contiene virgole, prendi il primo campo
            if (str_contains($line, ',')) {
                $parts = str_getcsv($line);
                $line = trim($parts[0] ?? '');
            }

            // Valida URL
            if (filter_var($line, FILTER_VALIDATE_URL)) {
                $urls[] = $line;
            } elseif (preg_match('#^[a-z0-9.-]+\.[a-z]{2,}#i', $line)) {
                // Potrebbe essere un URL senza protocollo
                $urls[] = 'https://' . $line;
            }
        }

        if (empty($urls)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nessun URL valido trovato nel CSV']);
            return;
        }

        $result = $this->page->bulkInsert($projectId, $urls);

        $this->jsonResponse([
            'success' => true,
            'message' => "Importati {$result['inserted']} nuovi URL, {$result['skipped']} gia presenti",
            'inserted' => $result['inserted'],
            'skipped' => $result['skipped'],
        ]);
    }

    /**
     * Import manuale
     */
    public function importManual(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlsText = trim($_POST['urls'] ?? '');

        if (empty($urlsText)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nessun URL inserito']);
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', $urlsText);
        $urls = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Aggiungi protocollo se mancante
            if (!preg_match('#^https?://#', $line)) {
                $line = 'https://' . $line;
            }

            if (filter_var($line, FILTER_VALIDATE_URL)) {
                $urls[] = $line;
            }
        }

        if (empty($urls)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nessun URL valido trovato']);
            return;
        }

        $result = $this->page->bulkInsert($projectId, $urls);

        $this->jsonResponse([
            'success' => true,
            'message' => "Importati {$result['inserted']} nuovi URL, {$result['skipped']} gia presenti",
            'inserted' => $result['inserted'],
            'skipped' => $result['skipped'],
        ]);
    }

    /**
     * Dettaglio pagina
     */
    public function show(int $projectId, int $pageId): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        $page = $this->page->find($pageId, $projectId);

        if (!$page) {
            $_SESSION['_flash']['error'] = 'Pagina non trovata';
            Router::redirect('/seo-onpage/project/' . $projectId . '/pages');
            exit;
        }

        // Carica analisi e issues
        $analysis = new \Modules\SeoOnpage\Models\Analysis();
        $issue = new \Modules\SeoOnpage\Models\Issue();
        $aiSuggestion = new \Modules\SeoOnpage\Models\AiSuggestion();

        $latestAnalysis = $analysis->getLatestForPage($pageId);
        $issues = $latestAnalysis ? $issue->allByAnalysis($latestAnalysis['id']) : [];
        $suggestions = $aiSuggestion->allByPage($pageId, ['status' => 'pending']);

        return View::render('seo-onpage/pages/show', [
            'title' => 'Dettaglio Pagina - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'page' => $page,
            'analysis' => $latestAnalysis,
            'issues' => $issues,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Elimina pagina
     */
    public function destroy(int $projectId, int $pageId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            return;
        }

        $this->page->delete($pageId, $projectId);

        $_SESSION['_flash']['success'] = 'Pagina eliminata';
        Router::redirect('/seo-onpage/project/' . $projectId . '/pages');
    }

    /**
     * Helper JSON response
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
