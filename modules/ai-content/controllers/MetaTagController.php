<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Core\Credits;
use Core\Pagination;
use Modules\AiContent\Models\Project;
use Modules\AiContent\Models\MetaTag;
use Modules\AiContent\Models\WpSite;
use Services\SitemapService;
use Services\ScraperService;
use Services\AiService;
use Modules\AiContent\Models\ScrapeJob;

/**
 * MetaTagController
 *
 * Gestisce la generazione di meta tag SEO ottimizzati.
 * Workflow: Import URL -> Scrape -> Generate con AI -> Preview -> Publish su WP
 */
class MetaTagController
{
    private Project $project;
    private MetaTag $metaTag;
    /** Costo crediti scraping: livello Base (1 cr) */
    private const SCRAPE_CREDIT_COST = 1;
    /** Costo crediti generazione AI: livello Standard (3 cr) */
    private const GENERATE_CREDIT_COST = 3;

    public function __construct()
    {
        $this->project = new Project();
        $this->metaTag = new MetaTag();
    }

    /**
     * Verifica progetto e ownership
     */
    private function getProject(int $id, int $userId): ?array
    {
        $project = $this->project->findAccessible($id, $userId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            return null;
        }

        if ($project['type'] !== 'meta-tag') {
            $_SESSION['_flash']['error'] = 'Tipo progetto non valido';
            return null;
        }

        return $project;
    }

    /**
     * Dashboard meta tags
     * GET /ai-content/projects/{id}/meta-tags
     */
    public function dashboard(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        $stats = $this->metaTag->getStats($projectId);

        // Ultimi meta tag aggiunti
        $recent = $this->metaTag->getByProject($projectId, null, 10);

        // Verifica WP collegato
        $wpSite = null;
        if ($project['wp_site_id']) {
            $wpSiteModel = new WpSite();
            $wpSite = $wpSiteModel->find($project['wp_site_id']);
        }

        return View::render('ai-content/meta-tags/dashboard', [
            'title' => 'SEO Meta Tag - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'stats' => $stats,
            'recent' => $recent,
            'wpSite' => $wpSite,
            'currentPage' => 'meta-tags',
        ]);
    }

    /**
     * Lista meta tags con filtri e paginazione
     * GET /ai-content/projects/{id}/meta-tags/list
     */
    public function list(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $filters = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['q'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'dir' => $_GET['dir'] ?? null,
        ];

        $result = $this->metaTag->getPaginated($projectId, $page, $perPage, $filters);
        $stats = $this->metaTag->getStats($projectId);

        return View::render('ai-content/meta-tags/list', [
            'title' => 'Meta Tags - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'metaTags' => $result['data'],
            'pagination' => [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'from' => $result['from'],
                'to' => $result['to'],
            ],
            'stats' => $stats,
            'filters' => $filters,
            'currentPage' => 'list',
        ]);
    }

    /**
     * Wizard import
     * GET /ai-content/projects/{id}/meta-tags/import
     */
    public function import(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        // Load WP sites per import
        $wpSiteModel = new WpSite();
        $wpSites = $wpSiteModel->allByUser($user['id']);

        return View::render('ai-content/meta-tags/import', [
            'title' => 'Importa URL - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'wpSites' => $wpSites,
            'currentPage' => 'import',
        ]);
    }

    /**
     * Scopri sitemap da URL sito (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/discover
     */
    public function discover(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $siteUrl = trim($_POST['site_url'] ?? '');

        if (empty($siteUrl)) {
            echo json_encode(['success' => false, 'error' => 'Inserisci URL del sito']);
            return;
        }

        // Normalizza URL
        if (!preg_match('#^https?://#', $siteUrl)) {
            $siteUrl = 'https://' . $siteUrl;
        }
        $siteUrl = rtrim($siteUrl, '/');

        if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'URL non valido']);
            return;
        }

        try {
            $sitemapService = new SitemapService();
            $sitemaps = $sitemapService->discoverFromRobotsTxt($siteUrl, true);

            if (empty($sitemaps)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Nessuna sitemap trovata. Verifica che il sito abbia una sitemap.'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'site_url' => $siteUrl,
                'sitemaps' => $sitemaps,
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import da sitemap (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/import/sitemap
     */
    public function storeFromSitemap(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $sitemapUrls = $_POST['sitemaps'] ?? [];
        $filter = trim($_POST['filter'] ?? '');

        if (empty($sitemapUrls)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una sitemap']);
            return;
        }

        try {
            $sitemapService = new SitemapService();
            $result = $sitemapService->previewMultiple($sitemapUrls, $filter, 2000);

            if (empty($result['urls'])) {
                echo json_encode(['success' => false, 'error' => 'Nessuna URL trovata nelle sitemap']);
                return;
            }

            $inserted = $this->metaTag->addBulk($projectId, $user['id'], $result['urls']);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($result['urls']),
                'message' => "Importate {$inserted} URL",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import da WordPress (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/import/wp
     */
    public function storeFromWp(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $wpSiteId = (int) ($_POST['wp_site_id'] ?? 0);
        $postTypes = $_POST['post_types'] ?? ['post', 'page'];
        $limit = min(500, max(10, (int) ($_POST['limit'] ?? 100)));

        if (!$wpSiteId) {
            echo json_encode(['success' => false, 'error' => 'Seleziona un sito WordPress']);
            return;
        }

        $wpSiteModel = new WpSite();
        $wpSite = $wpSiteModel->find($wpSiteId);

        if (!$wpSite) {
            echo json_encode(['success' => false, 'error' => 'Sito WordPress non trovato']);
            return;
        }

        try {
            // Usa lo stesso pattern di WordPressController (che funziona)
            $scraper = new ScraperService();
            $apiUrl = rtrim($wpSite['url'], '/') . '/wp-json/seo-toolkit/v1/all-content';

            $queryParams = http_build_query([
                'per_page' => $limit,
                'post_types' => implode(',', (array) $postTypes),
            ]);

            // Usa fetchJson come nel pattern funzionante
            $result = $scraper->fetchJson($apiUrl . '?' . $queryParams, [
                'timeout' => 30,
                'api_mode' => true,
                'headers' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-SEO-Toolkit-Key: ' . $wpSite['api_key'],
                ],
            ]);

            // Gestione errori (pattern WordPressController)
            if (isset($result['error'])) {
                $errorMessage = $result['message'] ?? 'Errore connessione';

                // Mappatura errori user-friendly
                if (strpos($errorMessage, 'resolve') !== false) {
                    $errorMessage = 'Impossibile risolvere il dominio. Verifica l\'URL.';
                } elseif (strpos($errorMessage, 'connect') !== false) {
                    $errorMessage = 'Impossibile connettersi al server. Verifica che il sito sia online.';
                } elseif (strpos($errorMessage, 'timeout') !== false) {
                    $errorMessage = 'Connessione scaduta. Il server non risponde.';
                }

                echo json_encode(['success' => false, 'error' => $errorMessage]);
                return;
            }

            // Controlla HTTP status code
            $httpCode = $result['http_code'] ?? 0;
            if ($httpCode === 401) {
                echo json_encode(['success' => false, 'error' => 'API Key non valida. Verifica la chiave nel plugin WordPress.']);
                return;
            }
            if ($httpCode === 404) {
                echo json_encode(['success' => false, 'error' => 'Endpoint non trovato. Il plugin SEO Toolkit Connector è installato e attivo?']);
                return;
            }
            if ($httpCode >= 400) {
                echo json_encode(['success' => false, 'error' => "Errore HTTP {$httpCode} da WordPress"]);
                return;
            }

            // fetchJson restituisce i dati in $result['data']
            $data = $result['data'] ?? [];

            if (!isset($data['success']) || !$data['success']) {
                $wpError = $data['message'] ?? $data['error'] ?? $data['code'] ?? 'Errore risposta WordPress';
                echo json_encode(['success' => false, 'error' => $wpError]);
                return;
            }

            if (empty($data['posts'])) {
                echo json_encode(['success' => false, 'error' => 'Nessun contenuto trovato su WordPress']);
                return;
            }

            $inserted = $this->metaTag->addBulkFromWp($projectId, $user['id'], $wpSiteId, $data['posts']);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($data['posts']),
                'message' => "Importate {$inserted} pagine da WordPress",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import da CSV (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/import/csv
     */
    public function storeFromCsv(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            echo json_encode(['success' => false, 'error' => 'Nessun file caricato']);
            return;
        }

        $hasHeader = isset($_POST['has_header']);
        $delimiter = $_POST['delimiter'] ?? ',';
        $urlColumn = (int) ($_POST['url_column'] ?? 0);

        // Auto-detect delimiter
        if ($delimiter === 'auto') {
            $firstLine = fgets(fopen($_FILES['csv_file']['tmp_name'], 'r'));
            if (str_contains($firstLine, ';')) {
                $delimiter = ';';
            } elseif (str_contains($firstLine, "\t")) {
                $delimiter = "\t";
            } else {
                $delimiter = ',';
            }
        }

        try {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $urls = [];
            $lineNum = 0;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNum++;

                if ($lineNum === 1 && $hasHeader) {
                    continue;
                }

                $url = trim($row[$urlColumn] ?? '');

                if (empty($url)) {
                    continue;
                }

                // Normalizza URL
                if (!preg_match('#^https?://#', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }

                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }

            fclose($handle);

            if (empty($urls)) {
                echo json_encode(['success' => false, 'error' => 'Nessuna URL valida trovata nel CSV']);
                return;
            }

            $inserted = $this->metaTag->addBulk($projectId, $user['id'], $urls);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($urls),
                'message' => "Importate {$inserted} URL dal CSV",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import manuale (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/import/manual
     */
    public function storeManual(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlsText = $_POST['urls_text'] ?? '';

        if (empty(trim($urlsText))) {
            echo json_encode(['success' => false, 'error' => 'Inserisci almeno un URL']);
            return;
        }

        try {
            $lines = explode("\n", $urlsText);
            $urls = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Estrai URL (prima colonna se separata)
                $parts = preg_split('/[\t,]/', $line, 2);
                $url = trim($parts[0]);

                if (empty($url)) {
                    continue;
                }

                // Normalizza URL
                if (!preg_match('#^https?://#', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }

                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }

            if (empty($urls)) {
                echo json_encode(['success' => false, 'error' => 'Nessuna URL valida trovata']);
                return;
            }

            $inserted = $this->metaTag->addBulk($projectId, $user['id'], $urls);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($urls),
                'message' => "Importate {$inserted} URL",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Scrape batch di URL pending (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/scrape
     */
    public function scrape(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $batchSize = (int) ($_POST['batch_size'] ?? 10);
        $batchSize = min(50, max(1, $batchSize));

        // Verifica crediti (1 per URL come da spec)
        $cost = $batchSize * 1;
        if (!Credits::hasEnough($user['id'], $cost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$cost}"
            ]);
            return;
        }

        $pending = $this->metaTag->getNextPending($projectId, $batchSize);

        if (empty($pending)) {
            echo json_encode([
                'success' => true,
                'scraped' => 0,
                'message' => 'Nessuna URL da elaborare'
            ]);
            return;
        }

        $scraper = new ScraperService();
        $scraped = 0;
        $errors = 0;

        foreach ($pending as $item) {
            try {
                // ScraperService::scrape() usa Readability e lancia eccezioni sugli errori
                $result = $scraper->scrape($item['url']);

                // Readability estrae contenuto pulito
                $content = $result['content'] ?? '';
                $wordCount = $result['word_count'] ?? str_word_count($content);

                $this->metaTag->updateScrapeData($item['id'], [
                    'original_title' => $result['title'] ?? null,
                    'original_h1' => $result['headings']['h1'][0] ?? null,
                    'current_meta_title' => $result['title'] ?? null,
                    'current_meta_desc' => $result['description'] ?? null,
                    'scraped_content' => mb_substr($content, 0, 50000),
                    'scraped_word_count' => $wordCount,
                ]);

                $scraped++;

            } catch (\Exception $e) {
                $this->metaTag->markScrapeError($item['id'], $e->getMessage());
                $errors++;
            }
        }

        // Reconnect DB dopo operazioni lunghe
        Database::reconnect();

        // Consuma crediti solo per quelli effettivamente scrappati
        if ($scraped > 0) {
            Credits::consume($user['id'], $scraped, 'meta_scrape', 'ai-content', [
                'project_id' => $projectId,
                'scraped' => $scraped,
            ]);
        }

        $stats = $this->metaTag->getStats($projectId);

        echo json_encode([
            'success' => true,
            'scraped' => $scraped,
            'errors' => $errors,
            'pending' => $stats['pending'],
            'scraped_total' => $stats['scraped'],
            'message' => "Elaborate {$scraped} URL" . ($errors > 0 ? ", {$errors} errori" : ''),
        ]);
    }

    /**
     * Genera meta tag con AI (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/generate
     */
    public function generate(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $batchSize = (int) ($_POST['batch_size'] ?? 10);
        $batchSize = min(20, max(1, $batchSize)); // Max 20 per batch

        // Verifica crediti (2 per generazione come da spec)
        $cost = $batchSize * 2;
        if (!Credits::hasEnough($user['id'], $cost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$cost}"
            ]);
            return;
        }

        $scraped = $this->metaTag->getNextScraped($projectId, $batchSize);

        if (empty($scraped)) {
            echo json_encode([
                'success' => true,
                'generated' => 0,
                'message' => 'Nessuna URL da elaborare. Esegui prima lo scraping.'
            ]);
            return;
        }

        $ai = new AiService('ai-content');

        if (!$ai->isConfigured()) {
            echo json_encode(['success' => false, 'error' => 'AI non configurata']);
            return;
        }

        $generated = 0;
        $errors = 0;

        foreach ($scraped as $item) {
            try {
                $prompt = $this->buildMetaTagPrompt($item);

                $result = $ai->analyze($user['id'], $prompt, '', 'ai-content', [
                    'skip_credits' => true, // Gestiamo i crediti manualmente
                ]);

                if (isset($result['error'])) {
                    $this->metaTag->markGenerationError($item['id'], $result['message'] ?? 'AI error');
                    $errors++;
                    continue;
                }

                // Parsa la risposta AI (AiService::analyze() ritorna 'result', non 'content')
                $parsed = $this->parseMetaTagResponse($result['result'] ?? '');

                if (empty($parsed['title']) || empty($parsed['description'])) {
                    $this->metaTag->markGenerationError($item['id'], 'Risposta AI non valida');
                    $errors++;
                    continue;
                }

                $this->metaTag->updateGeneratedData($item['id'], $parsed['title'], $parsed['description']);
                $generated++;

            } catch (\Exception $e) {
                $this->metaTag->markGenerationError($item['id'], $e->getMessage());
                $errors++;
            }
        }

        // Reconnect DB dopo chiamate AI
        Database::reconnect();

        // Consuma crediti solo per quelli effettivamente generati
        if ($generated > 0) {
            Credits::consume($user['id'], $generated * 2, 'meta_generate', 'ai-content', [
                'project_id' => $projectId,
                'generated' => $generated,
            ]);
        }

        $stats = $this->metaTag->getStats($projectId);

        echo json_encode([
            'success' => true,
            'generated' => $generated,
            'errors' => $errors,
            'scraped_remaining' => $stats['scraped'],
            'generated_total' => $stats['generated'],
            'message' => "Generati {$generated} meta tag" . ($errors > 0 ? ", {$errors} errori" : ''),
        ]);
    }

    /**
     * Costruisce il prompt per generare meta tag
     */
    private function buildMetaTagPrompt(array $item): string
    {
        $title = $item['original_title'] ?? $item['original_h1'] ?? 'Pagina senza titolo';
        $content = $item['scraped_content'] ?? '';
        $currentTitle = $item['current_meta_title'] ?? '';
        $currentDesc = $item['current_meta_desc'] ?? '';

        // Tronca contenuto per non eccedere limiti token (max ~4000 chars)
        $contentExcerpt = mb_substr($content, 0, 4000);

        return <<<PROMPT
You are an expert SEO specialist in meta tag optimization.

## CRITICAL RULES
1. DETECT the language of the page content below and write BOTH title and description in THAT SAME LANGUAGE.
2. You MUST always provide both TITLE and DESCRIPTION. Never skip either one.
3. If the content is in Italian, write in Italian. If in English, write in English. If in French, write in French. Etc.

## PAGE DATA
- URL: {$item['url']}
- Original title: {$title}
- Current meta title: {$currentTitle}
- Current meta description: {$currentDesc}

## PAGE CONTENT (excerpt)
{$contentExcerpt}

## REQUIREMENTS

### META TITLE (max 60 characters)
- Must be compelling and include the main keyword
- Must encourage clicks on the SERP
- Do NOT exceed 60 characters (ideal 50-60)
- Include brand/site name only if relevant

### META DESCRIPTION (max 155 characters)
- Must accurately describe the content
- Must include an implicit call-to-action
- Do NOT exceed 155 characters (ideal 140-155)
- Include main keywords naturally

## RESPONSE FORMAT
Reply ONLY with this exact format, nothing else:

TITLE: [the optimized meta title in the page language]
DESCRIPTION: [the optimized meta description in the page language]
PROMPT;
    }

    /**
     * Parsa la risposta AI per estrarre title e description
     */
    private function parseMetaTagResponse(string $response): array
    {
        $result = ['title' => '', 'description' => ''];

        // Cerca TITLE:
        if (preg_match('/TITLE:\s*(.+?)(?:\n|DESCRIPTION:|$)/is', $response, $matches)) {
            $result['title'] = trim($matches[1]);
        }

        // Cerca DESCRIPTION:
        if (preg_match('/DESCRIPTION:\s*(.+?)$/is', $response, $matches)) {
            $result['description'] = trim($matches[1]);
        }

        // Tronca se necessario
        $result['title'] = mb_substr($result['title'], 0, 70);
        $result['description'] = mb_substr($result['description'], 0, 200);

        return $result;
    }

    /**
     * Preview singolo meta tag
     * GET /ai-content/projects/{id}/meta-tags/{tagId}
     */
    public function preview(int $projectId, int $tagId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        $metaTag = $this->metaTag->findByProject($tagId, $projectId);

        if (!$metaTag) {
            $_SESSION['_flash']['error'] = 'Meta tag non trovato';
            Router::redirect("/ai-content/projects/{$projectId}/meta-tags/list");
            exit;
        }

        return View::render('ai-content/meta-tags/preview', [
            'title' => 'Preview Meta Tag - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'metaTag' => $metaTag,
            'currentPage' => 'list',
        ]);
    }

    /**
     * Update singolo meta tag (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/{tagId}/update
     */
    public function update(int $projectId, int $tagId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $metaTag = $this->metaTag->findByProject($tagId, $projectId);

        if (!$metaTag) {
            echo json_encode(['success' => false, 'error' => 'Meta tag non trovato']);
            return;
        }

        $title = trim($_POST['generated_title'] ?? '');
        $desc = trim($_POST['generated_desc'] ?? '');

        if (empty($title) && empty($desc)) {
            echo json_encode(['success' => false, 'error' => 'Inserisci almeno title o description']);
            return;
        }

        try {
            $this->metaTag->update($tagId, [
                'generated_title' => $title ?: null,
                'generated_desc' => $desc ?: null,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Meta tag aggiornato',
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Approve singolo meta tag (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/{tagId}/approve
     */
    public function approve(int $projectId, int $tagId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $metaTag = $this->metaTag->findByProject($tagId, $projectId);

        if (!$metaTag) {
            echo json_encode(['success' => false, 'error' => 'Meta tag non trovato']);
            return;
        }

        if (empty($metaTag['generated_title']) && empty($metaTag['generated_desc'])) {
            echo json_encode(['success' => false, 'error' => 'Genera prima i meta tag']);
            return;
        }

        try {
            $this->metaTag->approve($tagId);
            echo json_encode(['success' => true, 'message' => 'Meta tag approvato']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk approve (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/bulk-approve
     */
    public function bulkApprove(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno un meta tag']);
            return;
        }

        $ids = array_map('intval', $ids);

        try {
            $count = $this->metaTag->approveBulk($ids);
            echo json_encode([
                'success' => true,
                'approved' => $count,
                'message' => "Approvati {$count} meta tag",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Publish singolo meta tag su WordPress (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/{tagId}/publish
     */
    public function publish(int $projectId, int $tagId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $metaTag = $this->metaTag->findByProject($tagId, $projectId);

        if (!$metaTag) {
            echo json_encode(['success' => false, 'error' => 'Meta tag non trovato']);
            return;
        }

        if (!in_array($metaTag['status'], ['approved', 'generated'])) {
            echo json_encode(['success' => false, 'error' => 'Meta tag deve essere approvato prima della pubblicazione']);
            return;
        }

        if (!$metaTag['wp_site_id'] || !$metaTag['wp_post_id']) {
            echo json_encode(['success' => false, 'error' => 'Meta tag non collegato a WordPress']);
            return;
        }

        $wpSiteModel = new WpSite();
        $wpSite = $wpSiteModel->find($metaTag['wp_site_id']);

        if (!$wpSite) {
            echo json_encode(['success' => false, 'error' => 'Sito WordPress non trovato']);
            return;
        }

        try {
            // Usa lo stesso pattern di WordPressController
            $scraper = new ScraperService();
            $apiUrl = rtrim($wpSite['url'], '/') . '/wp-json/seo-toolkit/v1/posts/' . $metaTag['wp_post_id'] . '/seo-meta';

            $result = $scraper->postJson($apiUrl, [
                'seo_title' => $metaTag['generated_title'],
                'seo_description' => $metaTag['generated_desc'],
            ], [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-SEO-Toolkit-Key: ' . $wpSite['api_key'],
            ], ['api_mode' => true, 'timeout' => 30]);

            if (isset($result['error'])) {
                $errorMsg = $result['message'] ?? 'Errore pubblicazione';
                if (strpos($errorMsg, 'resolve') !== false) {
                    $errorMsg = 'Impossibile risolvere il dominio.';
                } elseif (strpos($errorMsg, 'connect') !== false) {
                    $errorMsg = 'Impossibile connettersi al server.';
                }
                $this->metaTag->markPublishError($tagId, $errorMsg);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                return;
            }

            // Controlla HTTP status
            $httpCode = $result['http_code'] ?? 0;
            if ($httpCode === 401) {
                $this->metaTag->markPublishError($tagId, 'API Key non valida');
                echo json_encode(['success' => false, 'error' => 'API Key non valida']);
                return;
            }
            if ($httpCode === 404) {
                $this->metaTag->markPublishError($tagId, 'Post non trovato su WordPress');
                echo json_encode(['success' => false, 'error' => 'Post non trovato su WordPress']);
                return;
            }

            if (!isset($result['data']['success']) || !$result['data']['success']) {
                $error = $result['data']['error'] ?? $result['data']['message'] ?? 'Errore risposta WordPress';
                $this->metaTag->markPublishError($tagId, $error);
                echo json_encode(['success' => false, 'error' => $error]);
                return;
            }

            $this->metaTag->markPublished($tagId);

            echo json_encode([
                'success' => true,
                'message' => 'Meta tag pubblicato su WordPress',
            ]);

        } catch (\Exception $e) {
            $this->metaTag->markPublishError($tagId, $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk publish (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/bulk-publish
     */
    public function bulkPublish(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            // Se nessun ID specificato, prendi tutti gli approvati
            $approved = $this->metaTag->getApproved($projectId, 50);
            $ids = array_column($approved, 'id');
        }

        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'Nessun meta tag da pubblicare']);
            return;
        }

        $published = 0;
        $errors = 0;

        foreach ($ids as $tagId) {
            $metaTag = $this->metaTag->findByProject((int) $tagId, $projectId);

            if (!$metaTag || !$metaTag['wp_site_id'] || !$metaTag['wp_post_id']) {
                $errors++;
                continue;
            }

            // Simula chiamata publish
            $_POST['ids'] = [];
            ob_start();
            $this->publish($projectId, (int) $tagId);
            $response = json_decode(ob_get_clean(), true);

            if ($response['success'] ?? false) {
                $published++;
            } else {
                $errors++;
            }
        }

        echo json_encode([
            'success' => true,
            'published' => $published,
            'errors' => $errors,
            'message' => "Pubblicati {$published} meta tag" . ($errors > 0 ? ", {$errors} errori" : ''),
        ]);
    }

    /**
     * Bulk delete (AJAX)
     * POST /ai-content/projects/{id}/meta-tags/bulk-delete
     */
    public function bulkDelete(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno un meta tag']);
            return;
        }

        $ids = array_map('intval', $ids);

        try {
            $count = $this->metaTag->deleteBulk($ids);
            echo json_encode([
                'success' => true,
                'deleted' => $count,
                'message' => "Eliminati {$count} meta tag",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Delete singolo meta tag
     * POST /ai-content/projects/{id}/meta-tags/{tagId}/delete
     */
    public function delete(int $projectId, int $tagId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $metaTag = $this->metaTag->findByProject($tagId, $projectId);

        if (!$metaTag) {
            echo json_encode(['success' => false, 'error' => 'Meta tag non trovato']);
            return;
        }

        try {
            $this->metaTag->delete($tagId);
            echo json_encode(['success' => true, 'message' => 'Meta tag eliminato']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Reset errori scraping
     * POST /ai-content/projects/{id}/meta-tags/reset-scrape-errors
     */
    public function resetScrapeErrors(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        try {
            $count = $this->metaTag->resetScrapeErrors($projectId);
            echo json_encode([
                'success' => true,
                'reset' => $count,
                'message' => "Reset {$count} errori scraping",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Reset errori generazione
     * POST /ai-content/projects/{id}/meta-tags/reset-generation-errors
     */
    public function resetGenerationErrors(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        try {
            $count = $this->metaTag->resetGenerationErrors($projectId);
            echo json_encode([
                'success' => true,
                'reset' => $count,
                'message' => "Reset {$count} errori generazione",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // =========================================
    // BACKGROUND JOB PROCESSING (SSE)
    // =========================================

    /**
     * Avvia un job di scraping in background
     * POST /ai-content/projects/{id}/meta-tags/start-scrape-job
     */
    public function startScrapeJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Verifica che non ci sia gia un job attivo per questo progetto
        $jobModel = new ScrapeJob();
        $activeJob = $jobModel->getActiveForProject($projectId, ScrapeJob::TYPE_SCRAPE);
        if ($activeJob) {
            echo json_encode([
                'success' => false,
                'error' => 'Esiste gia un job in esecuzione per questo progetto',
                'active_job_id' => $activeJob['id']
            ]);
            return;
        }

        // Conta URL pending
        $stats = $this->metaTag->getStats($projectId);
        $pendingCount = $stats['pending'];

        if ($pendingCount === 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Nessuna URL da elaborare'
            ]);
            return;
        }

        // Verifica crediti
        $totalCost = $pendingCount * self::SCRAPE_CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Necessari: {$totalCost}, disponibili: " . Credits::getBalance($user['id'])
            ]);
            return;
        }

        // Crea il job
        $jobId = $jobModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => ScrapeJob::TYPE_SCRAPE,
            'items_requested' => $pendingCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $pendingCount,
            'estimated_credits' => $totalCost,
        ]);
    }

    /**
     * SSE Stream per progress scraping in tempo reale
     * GET /ai-content/projects/{id}/meta-tags/scrape-stream?job_id=X
     *
     * Elabora le URL pending e invia eventi SSE
     */
    public function scrapeStream(int $projectId): void
    {
        // Verifica auth manualmente per evitare redirect su SSE
        $user = Auth::user();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }

        $project = $this->project->findAccessible($projectId, $user['id']);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            exit('Progetto non trovato');
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            header('HTTP/1.1 400 Bad Request');
            exit('job_id richiesto');
        }

        $jobModel = new ScrapeJob();
        $job = $jobModel->findByUser($jobId, $user['id']);
        if (!$job || $job['project_id'] != $projectId) {
            header('HTTP/1.1 404 Not Found');
            exit('Job non trovato');
        }

        // Chiudi la sessione PRIMA del loop per non bloccare altre richieste
        session_write_close();

        // CRITICAL: Prevent proxy/PHP timeout killing the script
        // Batch scraping 10-50 URLs can take 100-1500s
        ignore_user_abort(true);
        set_time_limit(0);

        // Setup SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Funzione helper per inviare eventi SSE
        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        };

        // Avvia il job se pending
        if ($job['status'] === ScrapeJob::STATUS_PENDING) {
            $jobModel->start($jobId);
            $sendEvent('started', [
                'job_id' => $jobId,
                'total_items' => $job['items_requested'],
            ]);
        }

        // Setup scraper
        $scraper = new ScraperService();

        $completed = 0;
        $failed = 0;
        $creditsUsed = 0;

        // Loop di elaborazione
        while (true) {
            // Riconnetti DB per evitare timeout
            Database::reconnect();

            // Verifica cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'message' => 'Job annullato dall\'utente',
                ]);
                break;
            }

            // Ottieni prossimo item pending
            $pending = $this->metaTag->getNextPending($projectId, 1);

            if (empty($pending)) {
                // Nessun altro item - job completato
                $jobModel->complete($jobId);
                $sendEvent('completed', [
                    'job_id' => $jobId,
                    'total_completed' => $completed,
                    'total_failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            $item = $pending[0];

            // Aggiorna job con URL corrente
            $jobModel->updateProgress($jobId, [
                'current_item_id' => $item['id'],
                'current_item' => $item['url'],
            ]);

            // Invia evento progress
            $sendEvent('progress', [
                'job_id' => $jobId,
                'current_url' => $item['url'],
                'current_id' => $item['id'],
                'completed' => $completed,
                'total' => $job['items_requested'],
                'percent' => $job['items_requested'] > 0 ? round(($completed / $job['items_requested']) * 100) : 0,
            ]);

            try {
                // Esegui scraping
                $result = $scraper->scrape($item['url']);

                Database::reconnect();

                // Estrai dati
                $content = $result['content'] ?? '';
                $wordCount = $result['word_count'] ?? str_word_count($content);

                // Aggiorna meta tag con dati scraping
                $this->metaTag->updateScrapeData($item['id'], [
                    'original_title' => $result['title'] ?? null,
                    'original_h1' => $result['headings']['h1'][0] ?? null,
                    'current_meta_title' => $result['title'] ?? null,
                    'current_meta_desc' => $result['description'] ?? null,
                    'scraped_content' => mb_substr($content, 0, 50000),
                    'scraped_word_count' => $wordCount,
                ]);

                // Scala crediti
                Credits::consume($user['id'], self::SCRAPE_CREDIT_COST, 'meta_scrape', 'ai-content', [
                    'project_id' => $projectId,
                    'meta_tag_id' => $item['id'],
                    'job_id' => $jobId,
                ]);

                $completed++;
                $creditsUsed += self::SCRAPE_CREDIT_COST;

                // Aggiorna job
                $jobModel->incrementCompleted($jobId);
                $jobModel->addCreditsUsed($jobId, self::SCRAPE_CREDIT_COST);

                // Invia evento item completato
                $sendEvent('item_completed', [
                    'id' => $item['id'],
                    'url' => $item['url'],
                    'title' => $result['title'] ?? '',
                    'word_count' => $wordCount,
                    'credits_remaining' => Credits::getBalance($user['id']),
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                // Marca come errore
                $this->metaTag->markScrapeError($item['id'], $e->getMessage());
                $jobModel->incrementFailed($jobId);
                $failed++;

                $sendEvent('item_error', [
                    'id' => $item['id'],
                    'url' => $item['url'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Pausa tra le chiamate per non sovraccaricare
            usleep(300000); // 300ms
        }

        exit;
    }

    /**
     * Polling fallback per status job
     * GET /ai-content/projects/{id}/meta-tags/scrape-job-status?job_id=X
     */
    public function scrapeJobStatus(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            return;
        }

        $jobModel = new ScrapeJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
    }

    /**
     * Annulla un job in esecuzione
     * POST /ai-content/projects/{id}/meta-tags/cancel-scrape-job
     */
    public function cancelScrapeJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            return;
        }

        $jobModel = new ScrapeJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        if (!in_array($job['status'], [ScrapeJob::STATUS_PENDING, ScrapeJob::STATUS_RUNNING])) {
            echo json_encode(['success' => false, 'error' => 'Il job non puo essere annullato']);
            return;
        }

        $cancelled = $jobModel->cancel($jobId);

        echo json_encode([
            'success' => $cancelled,
            'message' => $cancelled ? 'Job annullato' : 'Impossibile annullare il job',
        ]);
    }

    // =========================================
    // GENERATE BACKGROUND JOB PROCESSING (SSE)
    // =========================================

    /**
     * Avvia un job di generazione AI in background
     * POST /ai-content/projects/{id}/meta-tags/start-generate-job
     */
    public function startGenerateJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Verifica che non ci sia gia un job attivo
        $jobModel = new ScrapeJob();
        $activeJob = $jobModel->getActiveForProject($projectId, ScrapeJob::TYPE_GENERATE);
        if ($activeJob) {
            echo json_encode([
                'success' => false,
                'error' => 'Esiste gia un job di generazione in esecuzione',
                'active_job_id' => $activeJob['id']
            ]);
            return;
        }

        // Conta URL scraped pronte per generazione
        $stats = $this->metaTag->getStats($projectId);
        $scrapedCount = $stats['scraped'];

        if ($scrapedCount === 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Nessuna URL pronta per la generazione. Esegui prima lo scraping.'
            ]);
            return;
        }

        // Verifica AI configurata
        $ai = new AiService('ai-content');
        if (!$ai->isConfigured()) {
            echo json_encode(['success' => false, 'error' => 'AI non configurata']);
            return;
        }

        // Verifica crediti
        $totalCost = $scrapedCount * self::GENERATE_CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Necessari: {$totalCost}, disponibili: " . Credits::getBalance($user['id'])
            ]);
            return;
        }

        // Recupera gli ID delle righe che verranno elaborate
        $scrapedItems = $this->metaTag->getNextScraped($projectId, 500);
        $itemIds = array_column($scrapedItems, 'id');

        // Crea il job
        $jobId = $jobModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => ScrapeJob::TYPE_GENERATE,
            'items_requested' => $scrapedCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $scrapedCount,
            'item_ids' => $itemIds,
            'estimated_credits' => $totalCost,
        ]);
    }

    /**
     * SSE Stream per progress generazione AI in tempo reale
     * GET /ai-content/projects/{id}/meta-tags/generate-stream?job_id=X
     */
    public function generateStream(int $projectId): void
    {
        $user = Auth::user();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }

        $project = $this->project->findAccessible($projectId, $user['id']);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            exit('Progetto non trovato');
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            header('HTTP/1.1 400 Bad Request');
            exit('job_id richiesto');
        }

        $jobModel = new ScrapeJob();
        $job = $jobModel->findByUser($jobId, $user['id']);
        if (!$job || $job['project_id'] != $projectId) {
            header('HTTP/1.1 404 Not Found');
            exit('Job non trovato');
        }

        // Chiudi sessione, poi abilita guardie timeout
        session_write_close();

        // CRITICAL: Prevent proxy/PHP timeout killing the script
        // AI generation for 10-20 meta tags can take 150-600s
        ignore_user_abort(true);
        set_time_limit(0);

        // Setup SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        };

        // Avvia il job se pending
        if ($job['status'] === ScrapeJob::STATUS_PENDING) {
            $jobModel->start($jobId);
            $sendEvent('started', [
                'job_id' => $jobId,
                'total_items' => $job['items_requested'],
            ]);
        }

        // Setup AI
        $ai = new AiService('ai-content');
        $completed = 0;
        $failed = 0;
        $creditsUsed = 0;

        while (true) {
            Database::reconnect();

            // Verifica cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'message' => 'Job annullato dall\'utente',
                ]);
                break;
            }

            // Prossimo item scraped
            $scraped = $this->metaTag->getNextScraped($projectId, 1);

            if (empty($scraped)) {
                $jobModel->complete($jobId);
                $sendEvent('completed', [
                    'job_id' => $jobId,
                    'total_completed' => $completed,
                    'total_failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            $item = $scraped[0];

            $jobModel->updateProgress($jobId, [
                'current_item_id' => $item['id'],
                'current_item' => $item['url'],
            ]);

            $sendEvent('progress', [
                'job_id' => $jobId,
                'current_url' => $item['url'],
                'current_id' => $item['id'],
                'completed' => $completed,
                'total' => $job['items_requested'],
                'percent' => $job['items_requested'] > 0 ? round(($completed / $job['items_requested']) * 100) : 0,
            ]);

            try {
                $prompt = $this->buildMetaTagPrompt($item);

                $result = $ai->analyze($user['id'], $prompt, '', 'ai-content', [
                    'skip_credits' => true,
                ]);

                Database::reconnect();

                if (isset($result['error'])) {
                    $this->metaTag->markGenerationError($item['id'], $result['message'] ?? 'AI error');
                    $jobModel->incrementFailed($jobId);
                    $failed++;

                    $sendEvent('item_error', [
                        'id' => $item['id'],
                        'url' => $item['url'],
                        'error' => $result['message'] ?? 'AI error',
                    ]);
                    continue;
                }

                $parsed = $this->parseMetaTagResponse($result['result'] ?? '');

                if (empty($parsed['title']) && empty($parsed['description'])) {
                    $this->metaTag->markGenerationError($item['id'], 'Risposta AI non valida');
                    $jobModel->incrementFailed($jobId);
                    $failed++;

                    $sendEvent('item_error', [
                        'id' => $item['id'],
                        'url' => $item['url'],
                        'error' => 'Risposta AI non valida',
                    ]);
                    continue;
                }

                $this->metaTag->updateGeneratedData($item['id'], $parsed['title'], $parsed['description']);

                Credits::consume($user['id'], self::GENERATE_CREDIT_COST, 'meta_generate', 'ai-content', [
                    'project_id' => $projectId,
                    'meta_tag_id' => $item['id'],
                    'job_id' => $jobId,
                ]);

                $completed++;
                $creditsUsed += self::GENERATE_CREDIT_COST;
                $jobModel->incrementCompleted($jobId);
                $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);

                $sendEvent('item_completed', [
                    'id' => $item['id'],
                    'url' => $item['url'],
                    'generated_title' => $parsed['title'],
                    'generated_desc' => $parsed['description'],
                    'title_length' => mb_strlen($parsed['title']),
                    'desc_length' => mb_strlen($parsed['description']),
                    'credits_remaining' => Credits::getBalance($user['id']),
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                $this->metaTag->markGenerationError($item['id'], $e->getMessage());
                $jobModel->incrementFailed($jobId);
                $failed++;

                $sendEvent('item_error', [
                    'id' => $item['id'],
                    'url' => $item['url'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Pausa tra le chiamate AI
            usleep(500000); // 500ms
        }

        exit;
    }

    /**
     * Polling fallback per status job generazione
     * GET /ai-content/projects/{id}/meta-tags/generate-job-status?job_id=X
     */
    public function generateJobStatus(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            return;
        }

        $jobModel = new ScrapeJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
    }

    /**
     * Annulla job di generazione
     * POST /ai-content/projects/{id}/meta-tags/cancel-generate-job
     */
    public function cancelGenerateJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            return;
        }

        $jobModel = new ScrapeJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        if (!in_array($job['status'], [ScrapeJob::STATUS_PENDING, ScrapeJob::STATUS_RUNNING])) {
            echo json_encode(['success' => false, 'error' => 'Il job non puo essere annullato']);
            return;
        }

        $cancelled = $jobModel->cancel($jobId);

        echo json_encode([
            'success' => $cancelled,
            'message' => $cancelled ? 'Job annullato' : 'Impossibile annullare il job',
        ]);
    }
}
