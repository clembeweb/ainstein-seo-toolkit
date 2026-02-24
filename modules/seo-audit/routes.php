<?php

/**
 * SEO Audit Module - Routes
 *
 * Modulo per audit SEO completo con Piano d'Azione AI
 */

use Core\Router;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\ModuleLoader;
use Modules\SeoAudit\Controllers\ProjectController;
use Modules\SeoAudit\Controllers\CrawlController;
use Modules\SeoAudit\Controllers\AuditController;
use Modules\SeoAudit\Controllers\ReportController;
use Modules\SeoAudit\Controllers\ActionPlanController;

$moduleSlug = 'seo-audit';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECTS ROUTES
// ============================================

// Lista progetti (dashboard modulo)
Router::get('/seo-audit', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Nuovo progetto - form
Router::get('/seo-audit/create', function () {
    \Core\Router::redirect('/projects/create');
});

// Nuovo progetto - store
Router::post('/seo-audit/store', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->store();
});

// Dashboard progetto (redirect a project/{id}/dashboard)
Router::get('/seo-audit/project/{id}', function ($id) {
    header('Location: ' . url('/seo-audit/project/' . $id . '/dashboard'));
    exit;
});

// Impostazioni progetto - view
Router::get('/seo-audit/project/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->settings((int) $id);
});

// Impostazioni progetto - update
Router::post('/seo-audit/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->updateSettings((int) $id);
});

// Elimina progetto
Router::post('/seo-audit/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->destroy((int) $id);
});

// ============================================
// CRAWL ROUTES
// ============================================

// Avvia crawl
Router::post('/seo-audit/project/{id}/crawl/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->start((int) $id);
});

// Stato crawl (polling AJAX)
Router::get('/seo-audit/project/{id}/crawl/status', function ($id) {
    Middleware::auth();
    $controller = new CrawlController();
    return $controller->status((int) $id);
});

// Processa batch di pagine (polling AJAX)
Router::post('/seo-audit/project/{id}/crawl/batch', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->crawlBatch((int) $id);
});

// Stop crawl (richiesta stop)
Router::post('/seo-audit/project/{id}/crawl/stop', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->stop((int) $id);
});

// Conferma stop e finalizza
Router::post('/seo-audit/project/{id}/crawl/confirm-stop', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->confirmStop((int) $id);
});

// Background job routes (SSE crawl processing)
Router::get('/seo-audit/project/{id}/crawl/stream', function ($id) {
    Middleware::auth();
    $controller = new CrawlController();
    $controller->processStream((int) $id);
});

Router::get('/seo-audit/project/{id}/crawl/job-status', function ($id) {
    Middleware::auth();
    $controller = new CrawlController();
    $controller->jobStatus((int) $id);
});

Router::post('/seo-audit/project/{id}/crawl/cancel-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    $controller->cancelJob((int) $id);
});

// ============================================
// AUDIT DASHBOARD & CATEGORIES ROUTES
// ============================================

// Dashboard progetto
Router::get('/seo-audit/project/{id}/dashboard', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->dashboard((int) $id);
});

// Lista pagine crawlate
Router::get('/seo-audit/project/{id}/pages', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->pages((int) $id);
});

// Elimina pagine selezionate (API JSON)
Router::post('/seo-audit/project/{id}/pages/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AuditController();
    return $controller->deletePages((int) $id);
});

// Dettaglio singola pagina
Router::get('/seo-audit/project/{id}/page/{pageId}', function ($id, $pageId) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->pageDetail((int) $id, (int) $pageId);
});

// Lista issues
Router::get('/seo-audit/project/{id}/issues', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->issues((int) $id);
});

// Dettaglio categoria
Router::get('/seo-audit/project/{id}/category/{slug}', function ($id, $slug) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->category((int) $id, $slug);
});

// Storico scansioni
Router::get('/seo-audit/project/{id}/history', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->history((int) $id);
});

// ============================================
// STRUTTURA LINK ROUTES
// ============================================

use Modules\SeoAudit\Controllers\LinkStructureController;

// Dashboard struttura link
Router::get('/seo-audit/project/{id}/links', function ($id) {
    Middleware::auth();
    $controller = new LinkStructureController();
    return $controller->overview((int) $id);
});

// Pagine orfane
Router::get('/seo-audit/project/{id}/links/orphans', function ($id) {
    Middleware::auth();
    $controller = new LinkStructureController();
    return $controller->orphans((int) $id);
});

// Analisi anchor text
Router::get('/seo-audit/project/{id}/links/anchors', function ($id) {
    Middleware::auth();
    $controller = new LinkStructureController();
    return $controller->anchors((int) $id);
});

// Grafo link
Router::get('/seo-audit/project/{id}/links/graph', function ($id) {
    Middleware::auth();
    $controller = new LinkStructureController();
    return $controller->graph((int) $id);
});

// ============================================
// ACTION PLAN AI ROUTES
// ============================================

// Vista principale Piano d'Azione
Router::get('/seo-audit/project/{id}/action-plan', function ($id) {
    Middleware::auth();
    $controller = new ActionPlanController();
    return $controller->index((int) $id);
});

// Genera piano (POST AJAX)
Router::post('/seo-audit/project/{id}/action-plan/generate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ActionPlanController();
    $controller->generate((int) $id);
});

// Toggle fix completato (POST AJAX)
Router::post('/seo-audit/project/{id}/fix/{fixId}/toggle', function ($id, $fixId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ActionPlanController();
    $controller->toggleFix((int) $id, (int) $fixId);
});

// Export To-Do List Markdown
Router::get('/seo-audit/project/{id}/action-plan/export', function ($id) {
    Middleware::auth();
    $controller = new ActionPlanController();
    $controller->export((int) $id);
});

// Elimina piano (POST AJAX)
Router::post('/seo-audit/project/{id}/action-plan/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ActionPlanController();
    $controller->delete((int) $id);
});

// API: Ottieni fix per una pagina (GET AJAX)
Router::get('/seo-audit/project/{id}/action-plan/page/{pageId}/fixes', function ($id, $pageId) {
    Middleware::auth();
    $controller = new ActionPlanController();
    $controller->getPageFixes((int) $id, (int) $pageId);
});

// ============================================
// EXPORT / REPORTS ROUTES
// ============================================

// Export CSV issues
Router::get('/seo-audit/project/{id}/export/csv', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportCsv((int) $id);
});

// Export CSV per categoria
Router::get('/seo-audit/project/{id}/export/csv/{category}', function ($id, $category) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportCategoryCsv((int) $id, $category);
});

// Export CSV pagine
Router::get('/seo-audit/project/{id}/export/pages-csv', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportPagesCsv((int) $id);
});

// Export PDF (placeholder)
Router::get('/seo-audit/project/{id}/export/pdf', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportPdf((int) $id);
});

// Report JSON summary
Router::get('/seo-audit/project/{id}/export/summary', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->summary((int) $id);
});

// Pagina esporta (redirect a CSV per ora)
Router::get('/seo-audit/project/{id}/export', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportCsv((int) $id);
});

// ============================================
// URL IMPORT ROUTES
// ============================================

// Pagina import URL
Router::get('/seo-audit/project/{id}/import', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->import((int) $id);
});

// Store imported URLs
Router::post('/seo-audit/project/{id}/urls/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\SeoAudit\Controllers\ApiController();
    $controller->storeUrls((int) $id);
});

// WordPress import SSE stream
Router::get('/seo-audit/project/{id}/import/wordpress/stream', function ($id) {
    Middleware::auth();
    $controller = new \Modules\SeoAudit\Controllers\ApiController();
    $controller->importWordPress((int) $id);
});

// ============================================
// API ROUTES
// ============================================

use Services\SitemapService;
use Modules\SeoAudit\Models\Project;

// Sitemap discovery API
Router::post('/seo-audit/api/sitemap-discover', function () {
    Middleware::auth();
    Middleware::csrf();
    header('Content-Type: application/json');

    $user = Auth::user();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int) ($input['project_id'] ?? 0);

    $projectModel = new Project();
    $project = $projectModel->findAccessible($projectId, $user['id']);

    if (!$project) {
        echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
        exit;
    }

    $baseUrl = rtrim($project['base_url'], '/');

    // Use shared SitemapService for discovery
    $sitemapService = new SitemapService();
    $sitemaps = $sitemapService->discoverFromRobotsTxt($baseUrl, false);

    echo json_encode(['success' => true, 'sitemaps' => $sitemaps]);
    exit;
});

// Sitemap import/preview API
Router::post('/seo-audit/api/sitemap', function () {
    Middleware::auth();
    Middleware::csrf();
    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $projectId = (int) ($input['project_id'] ?? 0);
        $action = $input['action'] ?? 'preview';

        $projectModel = new Project();
        $project = $projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $sitemapUrls = $input['sitemap_urls'] ?? [$input['sitemap_url'] ?? ''];
        $urlFilter = $input['url_filter'] ?? '';
        $maxUrls = min((int) ($input['max_urls'] ?? 5000), 5000);

        // Filter out empty URLs
        $sitemapUrls = array_filter($sitemapUrls, fn($url) => !empty($url));

        if (empty($sitemapUrls)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna sitemap specificata']);
            exit;
        }

        // Use shared SitemapService for parsing
        $sitemapService = new SitemapService();
        $sitemapService->setMaxUrls($maxUrls);

        $result = $sitemapService->previewMultiple($sitemapUrls, $urlFilter ?: null, $maxUrls);

        $uniqueUrls = $result['urls'];
        $duplicatesRemoved = $result['duplicates_removed'];

        if ($action === 'import') {
            // Store URLs for crawling
            $controller = new \Modules\SeoAudit\Controllers\ApiController();
            $imported = $controller->saveUrlsForCrawl($projectId, $uniqueUrls);

            echo json_encode([
                'success' => true,
                'imported' => $imported,
                'skipped' => count($uniqueUrls) - $imported,
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'urls' => $uniqueUrls,
                'total_unique' => count($uniqueUrls),
                'duplicates_removed' => $duplicatesRemoved,
                'preview_urls' => array_slice($uniqueUrls, 0, 100),
                'total_found' => count($uniqueUrls),
            ]);
        }
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Errore: ' . $e->getMessage()
        ]);
    }
    exit;
});

// Spider crawl API - Discovery + Auto-start crawl
Router::post('/seo-audit/api/spider', function () {
    Middleware::auth();
    Middleware::csrf();
    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $projectId = (int) ($input['project_id'] ?? 0);
        $maxPages = min((int) ($input['max_pages'] ?? 500), 2000);
        $maxDepth = min((int) ($input['max_depth'] ?? 3), 10);
        $respectRobots = (bool) ($input['respect_robots'] ?? true);
        $autoCrawl = (bool) ($input['auto_crawl'] ?? true); // Default: auto-start crawl

        // Impostazioni avanzate
        $requestDelay = max(0, min((int) ($input['request_delay'] ?? 200), 5000));
        $timeout = max(5, min((int) ($input['timeout'] ?? 30), 60));
        $maxRetries = max(0, min((int) ($input['max_retries'] ?? 2), 5));
        $userAgentSetting = $input['user_agent'] ?? 'default';
        $followRedirects = (bool) ($input['follow_redirects'] ?? true);

        // Mappa user-agent presets
        $userAgents = [
            'default' => 'SEOToolkit Spider/1.0',
            'googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'googlebot-mobile' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
        $userAgent = $userAgents[$userAgentSetting] ?? $userAgentSetting;

        $projectModel = new Project();
        $project = $projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $baseUrl = rtrim($project['base_url'], '/');

        // Spider crawl - discover URLs by following links
        $discovered = [$baseUrl];
        $toVisit = [$baseUrl];
        $visited = [];
        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        while (!empty($toVisit) && count($discovered) < $maxPages) {
            $url = array_shift($toVisit);

            if (isset($visited[$url])) {
                continue;
            }
            $visited[$url] = true;

            // Delay tra richieste
            if ($requestDelay > 0 && count($visited) > 1) {
                usleep($requestDelay * 1000);
            }

            // HTTP fetch con impostazioni avanzate
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'user_agent' => $userAgent,
                    'follow_location' => $followRedirects,
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);

            $html = @file_get_contents($url, false, $ctx);
            if (!$html) continue;

            // Extract links
            preg_match_all('/<a[^>]+href=["\']([^"\'#]+)["\'][^>]*>/i', $html, $matches);

            foreach ($matches[1] as $href) {
                // Normalize URL
                if (strpos($href, '//') === 0) {
                    $href = 'https:' . $href;
                } elseif (strpos($href, '/') === 0) {
                    $href = rtrim($baseUrl, '/') . $href;
                } elseif (strpos($href, 'http') !== 0) {
                    continue; // Skip relative or invalid
                }

                // Remove query strings and fragments for deduplication
                $href = strtok($href, '?');
                $href = strtok($href, '#');
                $href = rtrim($href, '/');

                // Check same domain
                $hrefDomain = parse_url($href, PHP_URL_HOST);
                if ($hrefDomain !== $baseDomain) continue;

                // Skip resources
                if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|pdf|zip|xml|json|woff|woff2|ttf|eot)$/i', $href)) continue;

                if (!in_array($href, $discovered)) {
                    $discovered[] = $href;
                    $toVisit[] = $href;
                }

                if (count($discovered) >= $maxPages) break;
            }

            // Rate limit
            usleep(100000); // 100ms
        }

        // Auto-save URLs and start crawl
        if ($autoCrawl && !empty($discovered)) {
            // Save URLs as pending pages
            $controller = new \Modules\SeoAudit\Controllers\ApiController();
            $imported = $controller->saveUrlsForCrawl($projectId, $discovered);

            // Create crawl session
            $sessionModel = new \Modules\SeoAudit\Models\CrawlSession();

            // Check for active session
            $activeSession = $sessionModel->findActiveByProject($projectId);
            if ($activeSession) {
                echo json_encode([
                    'success' => true,
                    'urls' => $discovered,
                    'total' => count($discovered),
                    'imported' => $imported,
                    'crawl_active' => true,
                    'session_id' => $activeSession['id'],
                    'message' => 'Crawl già in corso'
                ]);
                exit;
            }

            // Create new session con impostazioni avanzate
            $config = [
                'max_pages' => $maxPages,
                'crawl_mode' => 'both',
                'respect_robots' => $respectRobots ? 1 : 0,
                'include_external' => 0,
                // Avanzate
                'request_delay' => $requestDelay,
                'timeout' => $timeout,
                'max_retries' => $maxRetries,
                'user_agent' => $userAgent,
                'follow_redirects' => $followRedirects ? 1 : 0,
            ];

            $sessionId = $sessionModel->create($projectId, $config);
            $sessionModel->start($sessionId);
            $sessionModel->setPagesFound($sessionId, $imported);

            // Update project status - CRITICAL: reset pages_crawled to 0!
            $projectModel->update($projectId, [
                'current_session_id' => $sessionId,
                'crawl_config' => json_encode($config),
                'status' => 'crawling',
                'pages_found' => $imported,
                'pages_crawled' => 0, // Reset per nuova sessione
            ]);

            echo json_encode([
                'success' => true,
                'urls' => $discovered,
                'total' => count($discovered),
                'imported' => $imported,
                'auto_crawl' => true,
                'session_id' => $sessionId,
                'message' => "Trovati {$imported} URL. Analisi avviata automaticamente."
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'urls' => $discovered,
                'total' => count($discovered),
                'auto_crawl' => false,
            ]);
        }

    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Errore spider: ' . $e->getMessage()
        ]);
    }
    exit;
});
