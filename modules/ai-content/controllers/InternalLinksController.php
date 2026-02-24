<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Core\Credits;
use Modules\AiContent\Models\Project;
use Modules\AiContent\Models\InternalLinksPool;
use Services\SitemapService;
use Services\ScraperService;

/**
 * InternalLinksController
 *
 * Gestisce il pool di link interni per inserimento automatico negli articoli.
 * - Import URL da sitemap
 * - Scraping title/description
 * - CRUD del pool
 */
class InternalLinksController
{
    private Project $project;
    private InternalLinksPool $linksPool;

    public function __construct()
    {
        $this->project = new Project();
        $this->linksPool = new InternalLinksPool();
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

        return $project;
    }

    /**
     * Lista pool link interni
     * GET /ai-content/projects/{id}/internal-links
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        // Paginazione e filtri
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $filters = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['q'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'dir' => $_GET['dir'] ?? null,
        ];

        // Ottieni link paginati
        $result = $this->linksPool->getPaginated($projectId, $page, $perPage, $filters);
        $stats = $this->linksPool->getStats($projectId);

        return View::render('ai-content/internal-links/index', [
            'title' => 'Link Interni - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'links' => $result['data'],
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
            'currentPage' => 'internal-links',
        ]);
    }

    /**
     * Wizard import da sitemap
     * GET /ai-content/projects/{id}/internal-links/import
     */
    public function import(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        return View::render('ai-content/internal-links/import', [
            'title' => 'Importa Link Interni - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'internal-links',
        ]);
    }

    /**
     * Scopri sitemap da URL sito (AJAX)
     * POST /ai-content/projects/{id}/internal-links/discover
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
     * Preview URL dalle sitemap selezionate (AJAX)
     * POST /ai-content/projects/{id}/internal-links/preview
     */
    public function preview(int $projectId): void
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

            // Raggruppa per sitemap source (semplificato)
            $urlsData = [];
            foreach ($result['urls'] as $url) {
                $urlsData[] = [
                    'url' => $url,
                    'exists' => $this->linksPool->urlExists($projectId, $url),
                ];
            }

            echo json_encode([
                'success' => true,
                'urls' => $urlsData,
                'total' => $result['total'],
                'duplicates_removed' => $result['duplicates_removed'] ?? 0,
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Salva URL selezionate nel pool (AJAX)
     * POST /ai-content/projects/{id}/internal-links/store
     */
    public function store(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urls = $_POST['urls'] ?? [];
        $sitemapSource = $_POST['sitemap_source'] ?? null;

        if (empty($urls)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna URL selezionata']);
            return;
        }

        try {
            $inserted = $this->linksPool->addBulk($projectId, $urls, $sitemapSource);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'message' => "Importate {$inserted} URL nel pool",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Scrape title/description per link pending (AJAX)
     * POST /ai-content/projects/{id}/internal-links/scrape
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
        $batchSize = min(50, max(1, $batchSize)); // Limita 1-50

        // Verifica crediti (0.1 per URL come da spec)
        $cost = $batchSize * 0.1;
        if (!Credits::hasEnough($user['id'], $cost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$cost}"
            ]);
            return;
        }

        $pendingLinks = $this->linksPool->getNextPending($projectId, $batchSize);

        if (empty($pendingLinks)) {
            echo json_encode([
                'success' => true,
                'scraped' => 0,
                'message' => 'Nessun link da elaborare'
            ]);
            return;
        }

        $scraper = new ScraperService();
        $scraped = 0;
        $errors = 0;

        foreach ($pendingLinks as $link) {
            try {
                $result = $scraper->fetchRaw($link['url'], ['timeout' => 15]);

                if (isset($result['error'])) {
                    $this->linksPool->markScrapeError($link['id'], $result['message'] ?? 'Fetch failed');
                    $errors++;
                    continue;
                }

                $meta = $scraper->extractMeta($result['body']);

                $this->linksPool->updateScrapeData($link['id'], [
                    'title' => $meta['title'] ?? null,
                    'description' => $meta['description'] ?? null,
                ]);

                $scraped++;

            } catch (\Exception $e) {
                $this->linksPool->markScrapeError($link['id'], $e->getMessage());
                $errors++;
            }
        }

        // Reconnect DB dopo operazioni lunghe
        Database::reconnect();

        // Consuma crediti solo per quelli effettivamente scrappati
        if ($scraped > 0) {
            $actualCost = $scraped * 0.1;
            Credits::consume($user['id'], $actualCost, 'scrape_internal_links', 'ai-content', [
                'project_id' => $projectId,
                'scraped' => $scraped,
            ]);
        }

        $stats = $this->linksPool->getStats($projectId);

        echo json_encode([
            'success' => true,
            'scraped' => $scraped,
            'errors' => $errors,
            'pending' => $stats['pending'],
            'completed' => $stats['completed'],
            'message' => "Elaborati {$scraped} link" . ($errors > 0 ? ", {$errors} errori" : ''),
        ]);
    }

    /**
     * Form modifica singolo link
     * GET /ai-content/projects/{id}/internal-links/{linkId}/edit
     */
    public function edit(int $projectId, int $linkId): string
    {
        $user = Auth::user();
        $project = $this->getProject($projectId, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        $link = $this->linksPool->findByProject($linkId, $projectId);

        if (!$link) {
            $_SESSION['_flash']['error'] = 'Link non trovato';
            Router::redirect("/ai-content/projects/{$projectId}/internal-links");
            exit;
        }

        return View::render('ai-content/internal-links/edit', [
            'title' => 'Modifica Link - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'link' => $link,
            'currentPage' => 'internal-links',
        ]);
    }

    /**
     * Aggiorna singolo link
     * POST /ai-content/projects/{id}/internal-links/{linkId}/update
     */
    public function update(int $projectId, int $linkId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        $link = $this->linksPool->findByProject($linkId, $projectId);

        if (!$link) {
            $_SESSION['_flash']['error'] = 'Link non trovato';
            Router::redirect("/ai-content/projects/{$projectId}/internal-links");
            return;
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $this->linksPool->update($linkId, $data);
            $_SESSION['_flash']['success'] = 'Link aggiornato';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }

    /**
     * Elimina singolo link
     * POST /ai-content/projects/{id}/internal-links/{linkId}/delete
     */
    public function delete(int $projectId, int $linkId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        $link = $this->linksPool->findByProject($linkId, $projectId);

        if (!$link) {
            $_SESSION['_flash']['error'] = 'Link non trovato';
            Router::redirect("/ai-content/projects/{$projectId}/internal-links");
            return;
        }

        try {
            $this->linksPool->delete($linkId);
            $_SESSION['_flash']['success'] = 'Link eliminato';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }

    /**
     * Toggle attivo/disattivo (AJAX)
     * POST /ai-content/projects/{id}/internal-links/{linkId}/toggle
     */
    public function toggle(int $projectId, int $linkId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $link = $this->linksPool->findByProject($linkId, $projectId);

        if (!$link) {
            echo json_encode(['success' => false, 'error' => 'Link non trovato']);
            return;
        }

        try {
            $this->linksPool->toggleActive($linkId);
            $newStatus = !$link['is_active'];

            echo json_encode([
                'success' => true,
                'is_active' => $newStatus,
                'message' => $newStatus ? 'Link attivato' : 'Link disattivato',
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Azioni bulk
     * POST /ai-content/projects/{id}/internal-links/bulk
     */
    public function bulk(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        $action = $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            $_SESSION['_flash']['error'] = 'Seleziona almeno un link';
            Router::redirect("/ai-content/projects/{$projectId}/internal-links");
            return;
        }

        // Converto a int
        $ids = array_map('intval', $ids);

        try {
            switch ($action) {
                case 'activate':
                    $count = $this->linksPool->setActiveBulk($ids, true);
                    $_SESSION['_flash']['success'] = "Attivati {$count} link";
                    break;

                case 'deactivate':
                    $count = $this->linksPool->setActiveBulk($ids, false);
                    $_SESSION['_flash']['success'] = "Disattivati {$count} link";
                    break;

                case 'delete':
                    $count = $this->linksPool->deleteBulk($ids);
                    $_SESSION['_flash']['success'] = "Eliminati {$count} link";
                    break;

                default:
                    $_SESSION['_flash']['error'] = 'Azione non valida';
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }

    /**
     * Reset errori a pending
     * POST /ai-content/projects/{id}/internal-links/reset-errors
     */
    public function resetErrors(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        try {
            $count = $this->linksPool->resetErrors($projectId);
            if ($count > 0) {
                $_SESSION['_flash']['success'] = "Reset {$count} link in errore";
            } else {
                $_SESSION['_flash']['info'] = 'Nessun link in errore da resettare';
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }

    /**
     * Elimina tutti i link del progetto
     * POST /ai-content/projects/{id}/internal-links/clear
     */
    public function clear(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        try {
            $count = $this->linksPool->deleteByProject($projectId);
            $_SESSION['_flash']['success'] = "Eliminati tutti i {$count} link dal pool";
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }

    /**
     * Import da CSV
     * POST /ai-content/projects/{id}/internal-links/store-csv
     */
    public function storeCsv(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            $_SESSION['_flash']['error'] = 'Nessun file caricato';
            Router::redirect("/ai-content/projects/{$projectId}/internal-links/import");
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
            $imported = 0;
            $skipped = 0;
            $lineNum = 0;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNum++;

                // Salta header
                if ($lineNum === 1 && $hasHeader) {
                    continue;
                }

                $url = trim($row[$urlColumn] ?? '');

                if (empty($url)) {
                    $skipped++;
                    continue;
                }

                // Normalizza URL
                if (!preg_match('#^https?://#', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }

                // Check duplicato
                $existing = Database::fetch(
                    "SELECT id FROM aic_internal_links_pool WHERE project_id = ? AND url = ?",
                    [$projectId, $url]
                );

                if ($existing) {
                    $skipped++;
                    continue;
                }

                Database::insert('aic_internal_links_pool', [
                    'project_id' => $projectId,
                    'url' => $url,
                    'sitemap_source' => 'CSV import',
                    'scrape_status' => 'pending',
                ]);
                $imported++;
            }

            fclose($handle);

            $_SESSION['_flash']['success'] = "Importati {$imported} link" . ($skipped > 0 ? " ({$skipped} saltati)" : '');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }

    /**
     * Import manuale (textarea)
     * POST /ai-content/projects/{id}/internal-links/store-manual
     */
    public function storeManual(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        $urlsText = $_POST['urls_text'] ?? '';

        if (empty(trim($urlsText))) {
            $_SESSION['_flash']['error'] = 'Inserisci almeno un URL';
            Router::redirect("/ai-content/projects/{$projectId}/internal-links/import");
            return;
        }

        try {
            $lines = explode("\n", $urlsText);
            $imported = 0;
            $skipped = 0;

            foreach ($lines as $line) {
                $line = trim($line);

                // Salta righe vuote e commenti
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Estrai URL (prima colonna se separata da tab/virgola)
                $parts = preg_split('/[\t,]/', $line, 2);
                $url = trim($parts[0]);

                if (empty($url)) {
                    $skipped++;
                    continue;
                }

                // Normalizza URL
                if (!preg_match('#^https?://#', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }

                // Check duplicato
                $existing = Database::fetch(
                    "SELECT id FROM aic_internal_links_pool WHERE project_id = ? AND url = ?",
                    [$projectId, $url]
                );

                if ($existing) {
                    $skipped++;
                    continue;
                }

                Database::insert('aic_internal_links_pool', [
                    'project_id' => $projectId,
                    'url' => $url,
                    'sitemap_source' => 'Manual import',
                    'scrape_status' => 'pending',
                ]);
                $imported++;
            }

            $_SESSION['_flash']['success'] = "Importati {$imported} link" . ($skipped > 0 ? " ({$skipped} saltati)" : '');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        Router::redirect("/ai-content/projects/{$projectId}/internal-links");
    }
}
