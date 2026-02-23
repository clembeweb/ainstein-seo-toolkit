<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Database;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\CrawlSession;
use Modules\SeoAudit\Models\CrawlJob;
use Modules\SeoAudit\Services\IssueDetector;
use Core\Models\ProjectConnector;
use Services\Connectors\WordPressSeoConnector;

/**
 * ApiController
 *
 * Gestisce API per import URL (sitemap, spider, manual)
 */
class ApiController
{
    private Project $projectModel;

    public function __construct()
    {
        $this->projectModel = new Project();
    }

    /**
     * Store imported URLs (from any source)
     */
    public function storeUrls(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $importType = $input['import_type'] ?? 'manual';
        $urls = [];

        switch ($importType) {
            case 'manual':
                // Parse text area input
                $urlsText = $input['urls_text'] ?? '';
                $lines = explode("\n", $urlsText);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) continue;

                    // Support "url, keyword" or "url\tkeyword" format
                    $parts = preg_split('/[\t,]/', $line, 2);
                    $url = trim($parts[0]);

                    if (!empty($url)) {
                        $urls[] = $this->normalizeUrl($url, $project['base_url']);
                    }
                }
                break;

            case 'spider':
            case 'sitemap':
                $urls = $input['urls'] ?? [];
                break;

            case 'csv':
                // Handle CSV upload - for now just extract URLs
                if (isset($_FILES['csv_file'])) {
                    $urls = $this->parseCsvFile($_FILES['csv_file'], $input);
                }
                break;
        }

        // Filter and deduplicate
        $urls = array_unique(array_filter($urls));

        if (empty($urls)) {
            echo json_encode(['success' => false, 'error' => 'Nessun URL valido trovato']);
            exit;
        }

        // Save URLs for crawling
        $imported = $this->saveUrlsForCrawl($projectId, $urls);

        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'total' => count($urls),
            'message' => "{$imported} URL importati per la scansione",
        ]);
        exit;
    }

    /**
     * Save URLs to sa_pages as pending and to sa_site_config for crawling
     */
    public function saveUrlsForCrawl(int $projectId, array $urls): int
    {
        if (empty($urls)) return 0;

        $newCount = 0;

        // Get existing URLs from sa_pages
        $existingPages = Database::fetchAll(
            "SELECT url FROM sa_pages WHERE project_id = ?",
            [$projectId]
        );
        $existingUrls = array_column($existingPages, 'url');

        // Insert new pending pages
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) continue;

            // Skip if already exists
            if (in_array($url, $existingUrls)) continue;

            try {
                Database::execute("
                    INSERT INTO sa_pages (project_id, url, status, created_at)
                    VALUES (?, ?, 'pending', NOW())
                ", [$projectId, $url]);
                $newCount++;
                $existingUrls[] = $url;
            } catch (\Exception $e) {
                // URL might already exist, skip
                continue;
            }
        }

        // Also update sa_site_config.discovered_urls (for backward compatibility)
        $allUrls = array_unique(array_merge($existingUrls, $urls));
        Database::execute("
            INSERT INTO sa_site_config (project_id, discovered_urls)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE discovered_urls = VALUES(discovered_urls)
        ", [
            $projectId,
            json_encode(array_values($allUrls)),
        ]);

        // Update project pages_found (count pending pages)
        $pendingCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'pending'",
            [$projectId]
        )['cnt'] ?? 0;

        $crawledCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'crawled'",
            [$projectId]
        )['cnt'] ?? 0;

        $this->projectModel->update($projectId, [
            'pages_found' => $pendingCount + $crawledCount,
            'pages_crawled' => $crawledCount,
            'status' => 'pending',
        ]);

        return $newCount > 0 ? $newCount : count($urls);
    }

    /**
     * Normalize URL relative to base
     */
    private function normalizeUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);

        // Already absolute
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        // Protocol-relative
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        // Relative to root
        if (strpos($url, '/') === 0) {
            return rtrim($baseUrl, '/') . $url;
        }

        // Relative to current (append to base)
        return rtrim($baseUrl, '/') . '/' . $url;
    }

    /**
     * Parse CSV file for URLs
     */
    private function parseCsvFile(array $file, array $options): array
    {
        $urls = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $urls;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return $urls;

        $delimiter = $options['delimiter'] ?? ',';
        if ($delimiter === 'auto') {
            $delimiter = ',';
        }

        $urlColumn = (int) ($options['url_column'] ?? 0);
        $hasHeader = isset($options['has_header']);
        $rowNum = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;

            // Skip header
            if ($hasHeader && $rowNum === 1) continue;

            if (isset($row[$urlColumn]) && !empty(trim($row[$urlColumn]))) {
                $urls[] = trim($row[$urlColumn]);
            }
        }

        fclose($handle);
        return $urls;
    }

    // =========================================================================
    // WORDPRESS IMPORT (SSE)
    // =========================================================================

    /**
     * Import e analisi pagine da WordPress via plugin API (SSE)
     * GET /seo-audit/project/{id}/import/wordpress/stream?types=post,page
     *
     * Recupera pagine dal plugin WP seo-toolkit-connector, le salva in sa_pages
     * e esegue IssueDetector su ciascuna. Nessuno scraping necessario.
     */
    public function importWordPress(int $id): void
    {
        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // CRITICAL: Continua esecuzione anche se proxy chiude connessione
        ignore_user_abort(true);
        set_time_limit(0);

        // CRITICAL: Chiudi sessione prima del loop (non blocca altre request)
        session_write_close();

        // Carica progetto seo-audit
        $project = Database::fetch("SELECT * FROM sa_projects WHERE id = ?", [$id]);
        if (!$project) {
            $this->sendSseEvent('error', ['message' => 'Progetto non trovato']);
            exit;
        }

        $globalProjectId = $project['global_project_id'] ?? null;
        if (!$globalProjectId) {
            $this->sendSseEvent('error', ['message' => 'Progetto non collegato a un progetto globale']);
            exit;
        }

        // Recupera connettore WordPress attivo
        $connectorModel = new ProjectConnector();
        $connector = $connectorModel->getActiveByProject($globalProjectId, 'wordpress');
        if (!$connector) {
            $this->sendSseEvent('error', ['message' => 'Nessun connettore WordPress configurato']);
            exit;
        }

        $config = json_decode($connector['config'], true);
        if (empty($config['url']) || empty($config['api_key'])) {
            $this->sendSseEvent('error', ['message' => 'Configurazione connettore incompleta']);
            exit;
        }

        try {
            $wpService = new WordPressSeoConnector($config);
        } catch (\Exception $e) {
            $this->sendSseEvent('error', ['message' => 'Errore connettore: ' . $e->getMessage()]);
            exit;
        }

        $types = $_GET['types'] ?? 'post,page';

        // Pulisci dati precedente per fresh start
        Database::delete('sa_pages', 'project_id = ?', [$id]);
        Database::delete('sa_issues', 'project_id = ?', [$id]);

        // Crea sessione di crawl
        $sessionModel = new CrawlSession();
        $sessionId = $sessionModel->create($id, [
            'max_pages' => 5000,
            'crawl_mode' => 'wordpress',
        ]);
        $sessionModel->start($sessionId);

        // Prima chiamata API per ottenere totale pagine
        $firstPage = $wpService->fetchSeoAudit(1, 50, $types);
        Database::reconnect();

        if (!$firstPage['success']) {
            $sessionModel->fail($sessionId, $firstPage['error'] ?? 'Errore API WordPress');
            $this->sendSseEvent('error', ['message' => $firstPage['error'] ?? 'Errore API WordPress']);
            exit;
        }

        $totalItems = $firstPage['total'] ?? 0;
        $totalApiPages = $firstPage['total_pages'] ?? 1;

        if ($totalItems === 0) {
            $sessionModel->fail($sessionId, 'Nessuna pagina trovata su WordPress');
            $this->sendSseEvent('error', ['message' => 'Nessuna pagina trovata su WordPress']);
            exit;
        }

        // Aggiorna sessione con pagine trovate
        $sessionModel->setPagesFound($sessionId, $totalItems);

        // Crea background job
        $jobModel = new CrawlJob();
        $userId = (int) $project['user_id'];
        $jobId = $jobModel->create([
            'project_id' => $id,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'type' => 'wordpress',
            'items_total' => $totalItems,
            'config' => json_encode(['types' => $types, 'source' => 'wordpress']),
        ]);
        $jobModel->start($jobId);

        // Aggiorna stato progetto
        $this->projectModel->update($id, [
            'current_session_id' => $sessionId,
            'status' => 'crawling',
            'pages_found' => $totalItems,
            'pages_crawled' => 0,
        ]);

        $this->sendSseEvent('started', [
            'total' => $totalItems,
            'job_id' => $jobId,
            'session_id' => $sessionId,
        ]);

        // Inizializza IssueDetector
        $issueDetector = new IssueDetector();
        $issueDetector->init($id);
        $issueDetector->setSessionId($sessionId);

        $pageModel = new Page();
        $completed = 0;
        $totalIssuesFound = 0;

        // Analizza site_info dalla prima risposta (robots.txt, sitemap, SSL)
        $siteInfo = $firstPage['site_info'] ?? null;
        if ($siteInfo) {
            $siteIssues = $issueDetector->analyzeSiteInfo($siteInfo, $id, $sessionId);
            $totalIssuesFound += $siteIssues;
        }

        // Loop paginato sulle API pages di WordPress
        for ($apiPage = 1; $apiPage <= $totalApiPages; $apiPage++) {
            // Riusa prima risposta per pagina 1
            $response = ($apiPage === 1)
                ? $firstPage
                : $wpService->fetchSeoAudit($apiPage, 50, $types);

            Database::reconnect();

            if (!($response['success'] ?? false)) {
                $this->sendSseEvent('page_error', [
                    'error' => 'Errore recupero pagina API ' . $apiPage,
                ]);
                continue;
            }

            foreach ($response['pages'] ?? [] as $wpPage) {
                // Verifica cancellazione dal DB
                if ($jobModel->isCancelled($jobId)) {
                    // CRITICO: Salvare nel DB PRIMA dell'evento cancelled
                    Database::reconnect();
                    $sessionModel->stop($sessionId);
                    $this->projectModel->update($id, [
                        'status' => 'stopped',
                        'pages_crawled' => $completed,
                    ]);

                    $this->sendSseEvent('cancelled', $jobModel->getJobResponse($jobId));
                    exit;
                }

                $pageUrl = $wpPage['url'] ?? '';

                try {
                    // Converti dati WP in formato sa_pages
                    $pageData = $this->convertWpPageToAuditData($wpPage);

                    // Salva pagina via Page model (upsert)
                    $pageId = $this->saveWpPage($id, $sessionId, $pageModel, $pageData);

                    // Esegui IssueDetector
                    $issueCount = $issueDetector->analyzeAndSave($pageData, $pageId);
                    $totalIssuesFound += $issueCount;

                    Database::reconnect();
                    $completed++;

                    // Aggiorna job progress
                    $jobModel->incrementCompleted($jobId);
                    $jobModel->updateProgress($jobId, $completed, $pageUrl);

                    // Aggiorna sessione
                    $sessionModel->updateProgress($sessionId, $completed, $pageUrl, $totalIssuesFound);

                    // Aggiorna progetto
                    $this->projectModel->update($id, [
                        'pages_crawled' => $completed,
                    ]);

                    $this->sendSseEvent('page_analyzed', [
                        'url' => $pageUrl,
                        'completed' => $completed,
                        'total' => $totalItems,
                        'issues' => $issueCount,
                        'percent' => round(($completed / max($totalItems, 1)) * 100, 1),
                    ]);

                } catch (\Exception $e) {
                    $jobModel->incrementFailed($jobId);
                    $this->sendSseEvent('page_error', [
                        'url' => $pageUrl,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Check a livello progetto (duplicati, pagine orfane)
        try {
            Database::reconnect();
            $projectIssues = 0;
            $projectIssues += $issueDetector->detectDuplicates();
            $projectIssues += $issueDetector->detectOrphanPages();
            $totalIssuesFound += $projectIssues;
        } catch (\Exception $e) {
            error_log("[seo-audit-wp] Errore check progetto: " . $e->getMessage());
        }

        // CRITICO: Salvare nel DB PRIMA dell'evento completed (polling fallback)
        Database::reconnect();
        $jobModel->complete($jobId);
        $sessionModel->complete($sessionId);

        // Aggiorna stats progetto
        $this->projectModel->update($id, [
            'status' => 'completed',
            'pages_crawled' => $completed,
            'issues_count' => $totalIssuesFound,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Log attivita
        $this->projectModel->logActivity($id, $userId, 'wordpress_import_completed', [
            'session_id' => $sessionId,
            'job_id' => $jobId,
            'pages_imported' => $completed,
            'issues_found' => $totalIssuesFound,
            'types' => $types,
        ]);

        $this->sendSseEvent('completed', $jobModel->getJobResponse($jobId));
        exit;
    }

    /**
     * Converti dati pagina WordPress nel formato sa_pages
     *
     * @param array $wpPage Dati singola pagina dal plugin WP
     * @return array Dati compatibili con sa_pages + IssueDetector
     */
    private function convertWpPageToAuditData(array $wpPage): array
    {
        $headings = $wpPage['headings'] ?? [];

        // Conta heading per livello
        $hCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
        $h1Texts = [];
        foreach ($headings as $h) {
            $level = (int) ($h['level'] ?? 0);
            if ($level >= 1 && $level <= 6) {
                $hCounts[$level]++;
            }
            if ($level === 1) {
                $h1Texts[] = $h['text'] ?? '';
            }
        }

        // Conta immagini senza alt
        $images = $wpPage['images'] ?? [];
        $imagesWithoutAlt = 0;
        foreach ($images as $img) {
            if (empty($img['alt'])) {
                $imagesWithoutAlt++;
            }
        }

        // Conta link nofollow
        $internalLinks = $wpPage['internal_links'] ?? [];
        $externalLinks = $wpPage['external_links'] ?? [];
        $allLinks = array_merge($internalLinks, $externalLinks);
        $nofollowCount = 0;
        foreach ($allLinks as $link) {
            if (!empty($link['nofollow'])) {
                $nofollowCount++;
            }
        }

        // Schema types
        $schemaTypes = [];
        foreach ($wpPage['schema_json_ld'] ?? [] as $schema) {
            $schemaTypes[] = $schema['@type'] ?? 'Unknown';
        }

        $robotsMeta = $wpPage['robots_meta'] ?? '';

        return [
            'url' => $wpPage['url'] ?? '',
            'source' => 'wordpress',
            'cms_entity_id' => $wpPage['id'] ?? null,
            'cms_entity_type' => $wpPage['type'] ?? 'post',
            'status' => 'crawled',
            'status_code' => 200,
            'title' => $wpPage['title_tag'] ?? '',
            'title_length' => mb_strlen($wpPage['title_tag'] ?? ''),
            'meta_description' => $wpPage['meta_description'] ?? '',
            'meta_description_length' => mb_strlen($wpPage['meta_description'] ?? ''),
            'canonical_url' => $wpPage['canonical'] ?? '',
            'meta_robots' => $robotsMeta,
            'og_title' => $wpPage['og_title'] ?? '',
            'og_description' => $wpPage['og_description'] ?? '',
            'og_image' => $wpPage['og_image'] ?? '',
            'h1_count' => $hCounts[1],
            'h1_texts' => json_encode($h1Texts),
            'h2_count' => $hCounts[2],
            'h3_count' => $hCounts[3],
            'h4_count' => $hCounts[4],
            'h5_count' => $hCounts[5],
            'h6_count' => $hCounts[6],
            'word_count' => $wpPage['word_count'] ?? 0,
            'images_count' => count($images),
            'images_without_alt' => $imagesWithoutAlt,
            'images_data' => json_encode($images),
            'internal_links_count' => count($internalLinks),
            'external_links_count' => count($externalLinks),
            'nofollow_links_count' => $nofollowCount,
            'links_data' => json_encode([
                'internal' => $internalLinks,
                'external' => $externalLinks,
            ]),
            'has_schema' => !empty($wpPage['schema_json_ld']),
            'schema_types' => json_encode($schemaTypes),
            'is_indexable' => !str_contains(strtolower($robotsMeta), 'noindex'),
            'indexability_reason' => str_contains(strtolower($robotsMeta), 'noindex')
                ? 'noindex in meta robots' : null,
        ];
    }

    /**
     * Salva pagina WordPress in sa_pages via Page model
     *
     * @param int $projectId ID progetto sa_projects
     * @param int $sessionId ID sessione audit
     * @param Page $pageModel Istanza Page model
     * @param array $data Dati pagina da convertWpPageToAuditData()
     * @return int ID pagina salvata
     */
    private function saveWpPage(int $projectId, int $sessionId, Page $pageModel, array $data): int
    {
        $url = $data['url'];

        // Aggiungi session_id per tracking storico
        $data['session_id'] = $sessionId;

        // Usa Page::upsert() che gestisce insert/update
        return $pageModel->upsert($projectId, $url, $data);
    }

    /**
     * Helper SSE: invia evento al client
     */
    private function sendSseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }
}
