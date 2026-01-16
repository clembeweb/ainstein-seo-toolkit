<?php

/**
 * Internal Links Analyzer - Routes
 *
 * Modulo per l'analisi e ottimizzazione dei link interni
 * Uses shared SitemapService for sitemap operations
 */

use Core\Router;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\ModuleLoader;
use Modules\InternalLinks\Controllers\ProjectController;
use Modules\InternalLinks\Models\Project;
use Modules\InternalLinks\Models\Url;
use Modules\InternalLinks\Models\InternalLink;
use Modules\InternalLinks\Models\Snapshot;
use Modules\InternalLinks\Services\Scraper;
use Services\SitemapService;

$moduleSlug = 'internal-links';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECTS ROUTES
// ============================================

// Dashboard modulo (lista progetti)
Router::get('/internal-links', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Nuovo progetto - form
Router::get('/internal-links/projects/create', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->create();
});

// Nuovo progetto - store
Router::post('/internal-links/projects', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->store();
});

// Dashboard progetto
Router::get('/internal-links/project/{id}', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->show((int) $id);
});

// Impostazioni progetto
Router::get('/internal-links/project/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->settings((int) $id);
});

// Aggiorna progetto
Router::post('/internal-links/project/{id}/update', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->update((int) $id);
});

// Elimina progetto
Router::post('/internal-links/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->delete((int) $id);
});

// ============================================
// URLS ROUTES
// ============================================

// Lista URL progetto
Router::get('/internal-links/project/{id}/urls', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $urlModel = new Url();
    $page = (int) ($_GET['page'] ?? 1);
    $currentStatus = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    // Note: search parameter will be added to getByProject method if needed
    $urlsData = $urlModel->getByProject((int) $id, $page, 50, $currentStatus);
    $stats = $urlModel->getStats((int) $id);

    // Build statusStats for the view (format: ['pending' => count, 'scraped' => count, ...])
    $statusStats = [
        'pending' => $stats['pending'] ?? 0,
        'scraped' => $stats['scraped'] ?? 0,
        'error' => $stats['errors'] ?? 0,
        'no_content' => $stats['no_content'] ?? 0,
    ];

    return View::render('internal-links/urls/index', [
        'title' => $project['name'] . ' - URL',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'urls' => $urlsData['data'],
        'statusStats' => $statusStats,
        'currentStatus' => $currentStatus,
        'search' => $search,
        'pagination' => [
            'current_page' => $urlsData['current_page'],
            'last_page' => $urlsData['last_page'],
            'total' => $urlsData['total'],
            'from' => $urlsData['from'] ?? (($urlsData['current_page'] - 1) * 50 + 1),
            'to' => $urlsData['to'] ?? min($urlsData['current_page'] * 50, $urlsData['total']),
        ],
    ]);
});

// Import URL
Router::post('/internal-links/project/{id}/urls/import', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $urlModel = new Url();
    $urls = [];

    // Parse URLs from textarea
    $urlText = $_POST['urls'] ?? '';
    $lines = explode("\n", $urlText);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            // Support URL,Keyword format
            $parts = str_getcsv($line);
            $urls[] = [
                'url' => $parts[0] ?? $line,
                'keyword' => $parts[1] ?? null,
            ];
        }
    }

    $result = $urlModel->bulkImport((int) $id, $urls);
    $projectModel->updateStats((int) $id);
    $projectModel->logActivity((int) $id, $user['id'], 'urls_imported', $result);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'result' => $result]);
        exit;
    }

    $_SESSION['flash_success'] = "Importati {$result['imported']} URL, saltati {$result['skipped']}";
    header('Location: ' . url('/internal-links/project/' . $id . '/urls'));
    exit;
});

// Elimina URL
Router::post('/internal-links/url/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $urlModel = new Url();
    $url = $urlModel->find((int) $id);

    if (!$url) {
        header('Location: ' . url('/internal-links'));
        exit;
    }

    // Verify ownership via project
    $projectModel = new Project();
    $project = $projectModel->find($url['project_id'], $user['id']);

    if (!$project) {
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $urlModel->delete((int) $id);
    $projectModel->updateStats($url['project_id']);

    $_SESSION['flash_success'] = 'URL eliminato';
    header('Location: ' . url('/internal-links/project/' . $url['project_id'] . '/urls'));
    exit;
});

// ============================================
// SCRAPING ROUTES
// ============================================

// Pagina scraping
Router::get('/internal-links/project/{id}/scrape', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $progress = $projectModel->getScrapingProgress((int) $id);
    $creditBalance = Credits::getBalance($user['id']);
    $scrapeCost = Credits::getCost('scrape_url');

    return View::render('internal-links/scraper/index', [
        'title' => $project['name'] . ' - Scraping',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'progress' => $progress,
        'credits' => [
            'available' => $creditBalance,
            'cost_per_scrape' => $scrapeCost,
        ],
    ]);
});

// Start scraping (AJAX)
Router::post('/internal-links/project/{id}/scrape/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    header('Content-Type: application/json');

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        echo json_encode(['error' => 'Progetto non trovato']);
        exit;
    }

    $mode = $_POST['mode'] ?? 'pending';
    $batchSize = min((int) ($_POST['batch_size'] ?? 10), 50);

    try {
        $scraper = new Scraper();
        $scraper->init((int) $id, $user['id']);

        $result = $scraper->scrapeBatch($mode, $batchSize);
        $progress = $projectModel->getScrapingProgress((int) $id);

        echo json_encode([
            'success' => true,
            'result' => $result,
            'progress' => $progress,
            'complete' => $progress['pending'] === 0,
        ]);

    } catch (\Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
});

// Scraping status (polling)
Router::get('/internal-links/project/{id}/scrape/status', function ($id) {
    Middleware::auth();

    header('Content-Type: application/json');

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        echo json_encode(['error' => 'Progetto non trovato']);
        exit;
    }

    $progress = $projectModel->getScrapingProgress((int) $id);

    echo json_encode([
        'progress' => $progress,
        'complete' => $progress['pending'] === 0,
    ]);
    exit;
});

// Scrape batch (AJAX - returns individual URL results)
Router::post('/internal-links/project/{id}/scrape/batch', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find((int) $id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $batchSize = min((int) ($input['batch_size'] ?? 10), 50);

        $urlModel = new Url();
        $pendingUrls = $urlModel->getPending((int) $id, $batchSize);

        if (empty($pendingUrls)) {
            $progress = $projectModel->getScrapingProgress((int) $id);
            echo json_encode([
                'success' => true,
                'results' => [],
                'progress' => $progress,
            ]);
            exit;
        }

        $scraper = new Scraper();
        $scraper->init((int) $id, $user['id']);

        $results = [];
        $creditCost = Credits::getCost('scrape_url');

        foreach ($pendingUrls as $urlData) {
            // Check credits
            if (!Credits::hasEnough($user['id'], $creditCost)) {
                $results[] = [
                    'url' => $urlData['url'],
                    'success' => false,
                    'error' => 'Crediti insufficienti',
                ];
                break;
            }

            $result = $scraper->scrapeUrl($urlData);

            if ($result['success']) {
                Credits::consume($user['id'], $creditCost, 'scrape_url', 'internal-links', [
                    'project_id' => (int) $id,
                    'url' => $urlData['url'],
                ]);
            }

            $results[] = [
                'url' => $urlData['url'],
                'success' => $result['success'],
                'links_found' => $result['links_count'] ?? 0,
                'error' => $result['error'] ?? null,
            ];

            // Small delay between requests
            $delay = $project['scrape_delay'] ?? 1000;
            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        $projectModel->updateStats((int) $id);
        $progress = $projectModel->getScrapingProgress((int) $id);

        echo json_encode([
            'success' => true,
            'results' => $results,
            'progress' => $progress,
        ]);

    } catch (\Throwable $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => DEBUG ? $e->getTraceAsString() : null,
        ]);
    }
    exit;
});

// Reset scraping
Router::post('/internal-links/project/{id}/scrape/reset', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $urlModel = new Url();
    $linkModel = new InternalLink();

    $count = $urlModel->resetAllInProject((int) $id);
    $linkModel->deleteByProject((int) $id);
    $projectModel->updateStats((int) $id);
    $projectModel->logActivity((int) $id, $user['id'], 'scraping_reset', ['urls_reset' => $count]);

    $_SESSION['flash_success'] = "Reset {$count} URL";
    header('Location: ' . url('/internal-links/project/' . $id . '/scrape'));
    exit;
});

// ============================================
// LINKS ROUTES
// ============================================

// Lista link interni
Router::get('/internal-links/project/{id}/links', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $page = (int) ($_GET['page'] ?? 1);
    $filters = [
        'is_internal' => $_GET['is_internal'] ?? null,
        'juice_flow' => $_GET['juice_flow'] ?? null,
        'analyzed' => $_GET['analyzed'] ?? null,
        'search' => $_GET['search'] ?? null,
    ];

    $linksData = $linkModel->getByProject((int) $id, $page, 50, array_filter($filters));
    $stats = $linkModel->getStats((int) $id);

    return View::render('internal-links/links/index', [
        'title' => $project['name'] . ' - Link',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'links' => $linksData['data'],
        'stats' => $stats,
        'filters' => $filters,
        'currentPage' => $linksData['current_page'],
        'totalPages' => $linksData['last_page'],
        'totalLinks' => $linksData['total'],
    ]);
});

// Pagine orfane
Router::get('/internal-links/project/{id}/orphans', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $orphanPages = $linkModel->getOrphanPages((int) $id);

    return View::render('internal-links/links/orphans', [
        'title' => $project['name'] . ' - Pagine Orfane',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'orphanPages' => $orphanPages,
    ]);
});

// ============================================
// ANALYSIS ROUTES
// ============================================

// Pagina analisi AI
Router::get('/internal-links/project/{id}/analysis', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $stats = $linkModel->getStats((int) $id);
    $scoreDistribution = $linkModel->getScoreDistribution((int) $id);

    $creditBalance = Credits::getBalance($user['id']);
    $aiCost = Credits::getCost('ai_analysis_medium');

    return View::render('internal-links/analysis/index', [
        'title' => $project['name'] . ' - Analisi AI',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'stats' => $stats,
        'credits' => [
            'available' => $creditBalance,
            'cost_per_analysis' => $aiCost,
        ],
    ]);
});

// Start AI analysis (AJAX)
Router::post('/internal-links/project/{id}/analysis/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    header('Content-Type: application/json');

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        echo json_encode(['error' => 'Progetto non trovato']);
        exit;
    }

    $batchSize = min((int) ($_POST['batch_size'] ?? 25), 50);

    try {
        // Use platform's AiService
        $aiService = new \Services\AiService();

        $linkModel = new InternalLink();
        $pendingLinks = $linkModel->getPendingAnalysis((int) $id, $batchSize);

        if (empty($pendingLinks)) {
            echo json_encode(['success' => true, 'analyzed' => 0, 'message' => 'Nessun link da analizzare']);
            exit;
        }

        // Check credits
        $aiCost = Credits::getCost('ai_analysis_medium');
        $totalCost = $aiCost * count($pendingLinks);

        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode(['error' => 'Crediti insufficienti']);
            exit;
        }

        $analyzed = 0;

        // Group links by source URL for batch analysis
        $grouped = [];
        foreach ($pendingLinks as $link) {
            $sourceId = $link['source_url_id'];
            if (!isset($grouped[$sourceId])) {
                $grouped[$sourceId] = [];
            }
            $grouped[$sourceId][] = $link;
        }

        foreach ($grouped as $sourceLinks) {
            // Build prompt for batch
            $prompt = buildAnalysisPrompt($sourceLinks);

            $result = $aiService->analyze($prompt, $user['id']);

            if ($result['success']) {
                // Parse AI response and update links
                $analyses = parseAnalysisResponse($result['response'], $sourceLinks);

                foreach ($analyses as $linkId => $analysis) {
                    $linkModel->updateAnalysis($linkId, $analysis);
                    $analyzed++;
                }
            }
        }

        $projectModel->updateStats((int) $id);
        $projectModel->logActivity((int) $id, $user['id'], 'ai_analysis_completed', ['analyzed' => $analyzed]);

        echo json_encode([
            'success' => true,
            'analyzed' => $analyzed,
            'credits_used' => $totalCost,
        ]);

    } catch (\Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
});

// ============================================
// HELPER FUNCTIONS
// ============================================

function buildAnalysisPrompt(array $links): string
{
    if (empty($links)) {
        return '';
    }

    $firstLink = $links[0];
    $sourceUrl = $firstLink['source_url'] ?? 'Unknown';
    $sourceKeyword = $firstLink['source_keyword'] ?? '';

    $contentSummary = '';
    if (!empty($firstLink['content_html'])) {
        $text = strip_tags($firstLink['content_html']);
        $text = preg_replace('/\s+/', ' ', $text);
        $contentSummary = substr(trim($text), 0, 500);
    }

    $linksText = "";
    foreach ($links as $index => $link) {
        $linksText .= sprintf(
            "%d. Anchor: \"%s\" -> Destination: %s\n",
            $index + 1,
            $link['anchor_text'] ?? '[no anchor]',
            $link['destination_url']
        );
    }

    return <<<PROMPT
Sei un esperto SEO. Analizza questi link interni.

## PAGINA SORGENTE
URL: {$sourceUrl}
Keyword: {$sourceKeyword}
Contenuto: {$contentSummary}

## LINK DA ANALIZZARE
{$linksText}

Per ogni link fornisci JSON con:
- link_index: numero del link
- relevance_score: 1-10
- anchor_quality: 1-10
- juice_flow: "optimal"|"good"|"weak"|"poor"
- notes: max 100 caratteri
- suggestion: max 150 caratteri

Rispondi SOLO con array JSON.
PROMPT;
}

function parseAnalysisResponse(string $response, array $links): array
{
    $results = [];

    // Extract JSON from response
    if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
        $response = $matches[0];
    }

    $parsed = json_decode($response, true);

    if (!is_array($parsed)) {
        return $results;
    }

    foreach ($parsed as $item) {
        $index = ($item['link_index'] ?? 0) - 1;

        if (!isset($links[$index])) {
            continue;
        }

        $linkId = $links[$index]['id'];

        $results[$linkId] = [
            'relevance_score' => max(1, min(10, (int) ($item['relevance_score'] ?? 5))),
            'anchor_quality' => max(1, min(10, (int) ($item['anchor_quality'] ?? 5))),
            'juice_flow' => in_array($item['juice_flow'] ?? '', ['optimal', 'good', 'weak', 'poor']) ? $item['juice_flow'] : 'weak',
            'notes' => substr($item['notes'] ?? '', 0, 500),
            'suggestion' => substr($item['suggestion'] ?? '', 0, 500),
        ];
    }

    return $results;
}

// ============================================
// ADDITIONAL ROUTES (COMPLETE FEATURE PARITY)
// ============================================

// Alternative URL patterns with /projects/ prefix
Router::get('/internal-links/projects/{id}', function ($id) {
    header('Location: ' . url('/internal-links/project/' . $id));
    exit;
});

Router::get('/internal-links/projects/{id}/urls', function ($id) {
    header('Location: ' . url('/internal-links/project/' . $id . '/urls'));
    exit;
});

Router::get('/internal-links/projects/{id}/links', function ($id) {
    header('Location: ' . url('/internal-links/project/' . $id . '/links'));
    exit;
});

// URL Import page
Router::get('/internal-links/project/{id}/urls/import', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    return View::render('internal-links/urls/import', [
        'title' => $project['name'] . ' - Import URL',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
    ]);
});

// URL Store (from import form)
Router::post('/internal-links/project/{id}/urls/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $urlModel = new Url();
    $urls = [];
    $importType = $_POST['import_type'] ?? 'manual';

    if ($importType === 'csv' && isset($_FILES['csv_file'])) {
        // Handle CSV upload
        $file = $_FILES['csv_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $hasHeader = isset($_POST['has_header']);
            $delimiter = $_POST['delimiter'] ?? ',';
            $urlColumn = (int) ($_POST['url_column'] ?? 0);
            $keywordColumn = $_POST['keyword_column'] !== '' ? (int) $_POST['keyword_column'] : null;

            if ($delimiter === 'auto') {
                $delimiter = ',';
            }

            $handle = fopen($file['tmp_name'], 'r');
            $lineNum = 0;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNum++;
                if ($hasHeader && $lineNum === 1) continue;

                $url = $row[$urlColumn] ?? null;
                if ($url) {
                    $urls[] = [
                        'url' => trim($url),
                        'keyword' => $keywordColumn !== null ? ($row[$keywordColumn] ?? null) : null,
                    ];
                }
            }
            fclose($handle);
        }
    } elseif ($importType === 'manual') {
        // Handle manual textarea input
        $urlText = $_POST['urls_text'] ?? '';
        $lines = explode("\n", $urlText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            // Support URL, Keyword or URL\tKeyword format
            if (str_contains($line, "\t")) {
                $parts = explode("\t", $line, 2);
            } elseif (str_contains($line, ',')) {
                $parts = str_getcsv($line);
            } else {
                $parts = [$line];
            }

            $urls[] = [
                'url' => trim($parts[0] ?? $line),
                'keyword' => isset($parts[1]) ? trim($parts[1]) : null,
            ];
        }
    }

    $result = $urlModel->bulkImport((int) $id, $urls);
    $projectModel->updateStats((int) $id);
    $projectModel->logActivity((int) $id, $user['id'], 'urls_imported', $result);

    $_SESSION['flash_success'] = "Importati {$result['imported']} URL, saltati {$result['skipped']}";
    header('Location: ' . url('/internal-links/project/' . $id . '/urls'));
    exit;
});

// URL Delete Single
Router::post('/internal-links/project/{id}/urls/delete/{urlId}', function ($id, $urlId) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $urlModel = new Url();

    // Verify URL belongs to project
    $url = $urlModel->find((int) $urlId);
    if (!$url || $url['project_id'] != (int) $id) {
        $_SESSION['flash_error'] = 'URL non trovato';
        header('Location: ' . url('/internal-links/project/' . $id . '/urls'));
        exit;
    }

    $urlModel->delete((int) $urlId);
    $projectModel->updateStats((int) $id);

    $_SESSION['flash_success'] = 'URL eliminato';
    header('Location: ' . url('/internal-links/project/' . $id . '/urls'));
    exit;
});

// URL Bulk Actions (AJAX)
Router::post('/internal-links/project/{id}/urls/bulk', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find((int) $id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $urlIds = $input['url_ids'] ?? [];

        if (empty($urlIds)) {
            echo json_encode(['success' => false, 'error' => 'Nessun URL selezionato']);
            exit;
        }

        $urlModel = new Url();
        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($urlIds as $urlId) {
                    // Verify URL belongs to project before deleting
                    $url = $urlModel->find((int) $urlId);
                    if ($url && $url['project_id'] == (int) $id) {
                        $urlModel->delete((int) $urlId);
                        $count++;
                    }
                }
                $message = "Eliminati {$count} URL";
                break;

            case 'reset':
                foreach ($urlIds as $urlId) {
                    $urlModel->resetStatus((int) $urlId);
                    $count++;
                }
                $message = "Reset {$count} URL a pending";
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Azione non valida']);
                exit;
        }

        $projectModel->updateStats((int) $id);

        echo json_encode(['success' => true, 'message' => $message, 'count' => $count]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
});

// Link Graph Visualization
Router::get('/internal-links/project/{id}/links/graph', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $urlModel = new Url();

    // Build graph data
    $nodes = [];
    $edges = [];
    $urlsData = $urlModel->getScrapedWithContent((int) $id);

    // Create nodes from URLs
    $urlMap = [];
    foreach ($urlsData as $url) {
        $urlMap[strtolower(rtrim($url['url'], '/'))] = $url['id'];
        $nodes[$url['id']] = [
            'id' => $url['id'],
            'label' => parse_url($url['url'], PHP_URL_PATH) ?: '/',
            'url' => $url['url'],
            'incoming' => 0,
            'outgoing' => 0,
        ];
    }

    // Get all internal links
    $links = $linkModel->getByProject((int) $id, 1, 10000, ['is_internal' => 'yes']);
    foreach ($links['data'] as $link) {
        $sourceId = $link['source_url_id'];
        $destUrl = strtolower(rtrim($link['destination_url'], '/'));
        $destId = $urlMap[$destUrl] ?? null;

        if ($sourceId && $destId && isset($nodes[$sourceId]) && isset($nodes[$destId])) {
            $edges[] = [
                'from' => $sourceId,
                'to' => $destId,
                'score' => $link['ai_relevance_score'] ?? null,
            ];
            $nodes[$sourceId]['outgoing']++;
            $nodes[$destId]['incoming']++;
        }
    }

    return View::render('internal-links/links/graph', [
        'title' => $project['name'] . ' - Link Graph',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'graphData' => [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ],
    ]);
});

// ============================================
// REPORTS ROUTES
// ============================================

// Anchor Text Analysis Report
Router::get('/internal-links/project/{id}/reports/anchors', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $filter = $_GET['filter'] ?? 'all';
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 50;

    // Get anchor text stats
    $mostUsedAnchors = $linkModel->getMostUsedAnchors((int) $id, 500);
    $duplicateAnchors = $linkModel->findDuplicateAnchors((int) $id);

    // Filter anchors
    if ($filter === 'duplicates') {
        $anchors = $duplicateAnchors;
    } elseif ($filter === 'single') {
        $anchors = array_filter($mostUsedAnchors, fn($a) => ($a['count'] ?? 1) === 1);
    } else {
        $anchors = $mostUsedAnchors;
    }

    // Pagination
    $total = count($anchors);
    $anchors = array_slice($anchors, ($page - 1) * $perPage, $perPage);

    // Calculate stats
    $stats = [
        'unique_anchors' => count($mostUsedAnchors),
        'total_links' => array_sum(array_column($mostUsedAnchors, 'count')),
        'duplicate_anchors' => count($duplicateAnchors),
        'avg_length' => $total > 0 ? round(array_sum(array_map(fn($a) => strlen($a['anchor'] ?? ''), $mostUsedAnchors)) / count($mostUsedAnchors)) : 0,
    ];

    return View::render('internal-links/reports/anchors', [
        'title' => $project['name'] . ' - Anchor Analysis',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'anchors' => $anchors,
        'duplicateAnchors' => $duplicateAnchors,
        'stats' => $stats,
        'filter' => $filter,
        'pagination' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $total),
        ],
    ]);
});

// Link Juice Distribution Report
Router::get('/internal-links/project/{id}/reports/juice', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $urlModel = new Url();
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 50;

    // Get juice flow distribution
    $juiceStats = $linkModel->getJuiceFlowDistribution((int) $id);

    // Build pages with juice data
    $urlsData = $urlModel->getByProject((int) $id, $page, $perPage, 'scraped');
    $pages = [];

    foreach ($urlsData['data'] as $url) {
        $incomingLinks = $linkModel->getIncomingLinks((int) $id, $url['url']);
        $outgoingLinks = $linkModel->getBySourceUrl($url['id']);

        $incomingCount = count($incomingLinks);
        $outgoingCount = count(array_filter($outgoingLinks, fn($l) => $l['is_internal']));

        // Calculate juice ratio
        if ($incomingCount === 0) {
            $juiceRatio = 'orphan';
        } elseif ($outgoingCount === 0) {
            $juiceRatio = 'sink';
        } else {
            $juiceRatio = round($incomingCount / $outgoingCount, 1);
        }

        // Calculate avg incoming score
        $scores = array_filter(array_column($incomingLinks, 'ai_relevance_score'));
        $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;

        $pages[] = [
            'url' => $url['url'],
            'keyword' => $url['keyword'],
            'incoming_count' => $incomingCount,
            'outgoing_count' => $outgoingCount,
            'avg_incoming_score' => $avgScore,
            'juice_ratio' => $juiceRatio,
        ];
    }

    return View::render('internal-links/reports/juice', [
        'title' => $project['name'] . ' - Link Juice',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'pages' => $pages,
        'juiceStats' => $juiceStats,
        'pagination' => [
            'total' => $urlsData['total'],
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $urlsData['last_page'],
            'from' => $urlsData['from'],
            'to' => $urlsData['to'],
        ],
    ]);
});

// Orphan Pages Report
Router::get('/internal-links/project/{id}/reports/orphans', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $urlModel = new Url();
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 50;

    // Get orphan pages
    $orphanPages = $linkModel->getOrphanPages((int) $id);
    $totalUrls = $urlModel->countByProject((int) $id);
    $orphanCount = count($orphanPages);

    // Add outgoing count for each orphan
    foreach ($orphanPages as &$orphan) {
        $outgoing = $linkModel->getBySourceUrl($orphan['id']);
        $orphan['outgoing_count'] = count(array_filter($outgoing, fn($l) => $l['is_internal']));
    }

    // Pagination
    $paginatedOrphans = array_slice($orphanPages, ($page - 1) * $perPage, $perPage);

    return View::render('internal-links/reports/orphans', [
        'title' => $project['name'] . ' - Orphan Pages',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'orphanPages' => $paginatedOrphans,
        'orphanCount' => $orphanCount,
        'totalUrls' => $totalUrls,
        'pagination' => [
            'total' => $orphanCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($orphanCount / $perPage) ?: 1,
            'from' => $orphanCount > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $orphanCount),
        ],
    ]);
});

// ============================================
// COMPARE ROUTES (SNAPSHOTS)
// ============================================

// Compare Snapshots page
Router::get('/internal-links/project/{id}/compare', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    // Get snapshots
    $snapshotModel = new Snapshot();
    $snapshots = $snapshotModel->getByProject((int) $id, $user['id']);

    // Handle comparison if both snapshots selected
    $comparison = null;
    $selectedSnapshot1 = $_GET['snapshot1'] ?? null;
    $selectedSnapshot2 = $_GET['snapshot2'] ?? null;

    if ($selectedSnapshot1 && $selectedSnapshot2) {
        $comparison = $snapshotModel->compare((int) $selectedSnapshot1, (int) $selectedSnapshot2, $user['id']);
    }

    return View::render('internal-links/compare/index', [
        'title' => $project['name'] . ' - Compare',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'snapshots' => $snapshots,
        'comparison' => $comparison,
        'selectedSnapshot1' => $selectedSnapshot1,
        'selectedSnapshot2' => $selectedSnapshot2,
    ]);
});

// Create Snapshot
Router::post('/internal-links/project/{id}/compare/create', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $name = 'Snapshot ' . date('Y-m-d H:i');
    }

    try {
        $snapshotModel = new Snapshot();
        $snapshotId = $snapshotModel->createFromCurrent((int) $id, $user['id'], $name, $description ?: null);

        $projectModel->logActivity((int) $id, $user['id'], 'snapshot_created', [
            'snapshot_id' => $snapshotId,
            'name' => $name,
        ]);

        $_SESSION['flash_success'] = __('Snapshot created successfully');
    } catch (\Exception $e) {
        $_SESSION['flash_error'] = __('Error creating snapshot') . ': ' . $e->getMessage();
    }

    header('Location: ' . url('/internal-links/project/' . $id . '/compare'));
    exit;
});

// Delete Snapshot
Router::post('/internal-links/project/{id}/compare/delete/{snapshotId}', function ($id, $snapshotId) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $snapshotModel = new Snapshot();
    $snapshot = $snapshotModel->find((int) $snapshotId, $user['id']);

    if (!$snapshot || $snapshot['project_id'] != $id) {
        $_SESSION['flash_error'] = __('Snapshot not found');
        header('Location: ' . url('/internal-links/project/' . $id . '/compare'));
        exit;
    }

    $snapshotModel->delete((int) $snapshotId, $user['id']);

    $projectModel->logActivity((int) $id, $user['id'], 'snapshot_deleted', [
        'snapshot_id' => $snapshotId,
        'name' => $snapshot['name'],
    ]);

    $_SESSION['flash_success'] = __('Snapshot deleted');
    header('Location: ' . url('/internal-links/project/' . $id . '/compare'));
    exit;
});

// Compare two specific snapshots (alternative URL)
Router::get('/internal-links/project/{id}/compare/{snapshot1}/{snapshot2}', function ($id, $snapshot1, $snapshot2) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $snapshotModel = new Snapshot();
    $snapshots = $snapshotModel->getByProject((int) $id, $user['id']);
    $comparison = $snapshotModel->compare((int) $snapshot1, (int) $snapshot2, $user['id']);

    return View::render('internal-links/compare/index', [
        'title' => $project['name'] . ' - Compare',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'snapshots' => $snapshots,
        'comparison' => $comparison,
        'selectedSnapshot1' => $snapshot1,
        'selectedSnapshot2' => $snapshot2,
    ]);
});

// ============================================
// ANALYZER ROUTES (ALTERNATIVE AI ANALYSIS)
// ============================================

// AI Analyzer page (alternative route)
Router::get('/internal-links/project/{id}/analyzer', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->findWithStats((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();
    $stats = $linkModel->getStats((int) $id);
    $scoreDistribution = $linkModel->getScoreDistribution((int) $id);
    $juiceDistribution = $linkModel->getJuiceFlowDistribution((int) $id);

    // Get low score links
    $lowScoreLinks = [];
    $linksData = $linkModel->getByProject((int) $id, 1, 20, ['max_score' => 4]);
    $lowScoreLinks = $linksData['data'];

    // Check if API is configured
    $isConfigured = !empty(config('claude_api_key')) || !empty(config('anthropic_api_key'));

    $creditBalance = Credits::getBalance($user['id']);
    $aiCost = Credits::getCost('ai_analysis_medium');

    return View::render('internal-links/analyzer/index', [
        'title' => $project['name'] . ' - AI Analyzer',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'stats' => array_merge($stats, [
            'estimated_cost' => ($stats['pending'] ?? 0) * $aiCost,
            'avg_relevance' => $stats['avg_score'] ?? null,
        ]),
        'scoreDistribution' => $scoreDistribution,
        'juiceDistribution' => $juiceDistribution,
        'lowScoreLinks' => $lowScoreLinks,
        'isConfigured' => $isConfigured,
    ]);
});

// ============================================
// API ROUTES
// ============================================

// Sitemap discovery API
Router::post('/internal-links/api/sitemap-discover', function () {
    Middleware::auth();

    header('Content-Type: application/json');

    $user = Auth::user();
    $projectId = (int) ($_POST['project_id'] ?? json_decode(file_get_contents('php://input'), true)['project_id'] ?? 0);

    $projectModel = new Project();
    $project = $projectModel->find($projectId, $user['id']);

    if (!$project) {
        echo json_encode(['error' => 'Progetto non trovato']);
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
Router::post('/internal-links/api/sitemap', function () {
    Middleware::auth();

    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $projectId = (int) ($input['project_id'] ?? 0);
        $action = $input['action'] ?? 'preview';

        $projectModel = new Project();
        $project = $projectModel->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $sitemapUrls = $input['sitemap_urls'] ?? [$input['sitemap_url'] ?? ''];
        $urlFilter = $input['url_filter'] ?? '';
        $maxUrls = min((int) ($input['max_urls'] ?? 10000), 10000);

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
            if (empty($uniqueUrls)) {
                echo json_encode([
                    'success' => true,
                    'imported' => 0,
                    'skipped' => 0,
                    'message' => 'Nessun URL trovato nelle sitemap specificate'
                ]);
                exit;
            }

            $urlModel = new Url();
            $result = $urlModel->bulkImport($projectId, array_map(fn($u) => ['url' => $u], $uniqueUrls));
            $projectModel->updateStats($projectId);

            echo json_encode([
                'success' => true,
                'imported' => $result['imported'] ?? 0,
                'skipped' => $result['skipped'] ?? 0,
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
            'error' => 'Errore durante l\'elaborazione: ' . $e->getMessage()
        ]);
    }
    exit;
});

// AI Analysis API
Router::post('/internal-links/api/analyze', function () {
    Middleware::auth();

    header('Content-Type: application/json');

    $user = Auth::user();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int) ($input['project_id'] ?? 0);
    $batchSize = min((int) ($input['batch_size'] ?? 25), 50);

    $projectModel = new Project();
    $project = $projectModel->find($projectId, $user['id']);

    if (!$project) {
        echo json_encode(['error' => 'Progetto non trovato']);
        exit;
    }

    try {
        $aiService = new \Services\AiService();

        $linkModel = new InternalLink();
        $pendingLinks = $linkModel->getPendingAnalysis($projectId, $batchSize);

        if (empty($pendingLinks)) {
            $stats = $linkModel->getStats($projectId);
            echo json_encode([
                'success' => true,
                'complete' => true,
                'result' => ['analyzed' => 0, 'errors' => 0],
                'stats' => [
                    'total_links' => $stats['internal'] ?? 0,
                    'analyzed' => $stats['analyzed'] ?? 0,
                    'pending' => $stats['pending'] ?? 0,
                ],
            ]);
            exit;
        }

        // Check credits
        $aiCost = Credits::getCost('ai_analysis_medium');
        $totalCost = $aiCost * count($pendingLinks);

        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode(['error' => 'Crediti insufficienti']);
            exit;
        }

        $analyzed = 0;
        $errors = 0;

        // Group links by source URL
        $grouped = [];
        foreach ($pendingLinks as $link) {
            $sourceId = $link['source_url_id'];
            if (!isset($grouped[$sourceId])) {
                $grouped[$sourceId] = [];
            }
            $grouped[$sourceId][] = $link;
        }

        foreach ($grouped as $sourceLinks) {
            $prompt = buildAnalysisPrompt($sourceLinks);
            $result = $aiService->analyze($prompt, $user['id']);

            if ($result['success']) {
                $analyses = parseAnalysisResponse($result['response'], $sourceLinks);

                foreach ($analyses as $linkId => $analysis) {
                    $linkModel->updateAnalysis($linkId, $analysis);
                    $analyzed++;
                }
            } else {
                $errors += count($sourceLinks);
            }
        }

        $projectModel->updateStats($projectId);

        $stats = $linkModel->getStats($projectId);

        echo json_encode([
            'success' => true,
            'result' => ['analyzed' => $analyzed, 'errors' => $errors],
            'stats' => [
                'total_links' => $stats['internal'] ?? 0,
                'analyzed' => $stats['analyzed'] ?? 0,
                'pending' => $stats['pending'] ?? 0,
            ],
            'complete' => ($stats['pending'] ?? 0) === 0,
        ]);

    } catch (\Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
});

// ============================================
// BULK DELETE ROUTES
// ============================================

// Link Delete Single
Router::post('/internal-links/project/{id}/links/delete/{linkId}', function ($id, $linkId) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $linkModel = new InternalLink();

    // Verify link belongs to project
    $link = $linkModel->find((int) $linkId);
    if (!$link || $link['project_id'] != (int) $id) {
        $_SESSION['flash_error'] = 'Link non trovato';
        header('Location: ' . url('/internal-links/project/' . $id . '/links'));
        exit;
    }

    $linkModel->delete((int) $linkId);
    $projectModel->updateStats((int) $id);

    $_SESSION['flash_success'] = 'Link eliminato';
    header('Location: ' . url('/internal-links/project/' . $id . '/links'));
    exit;
});

// Link Bulk Actions (AJAX)
Router::post('/internal-links/project/{id}/links/bulk', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find((int) $id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $linkIds = $input['link_ids'] ?? [];

        if (empty($linkIds)) {
            echo json_encode(['success' => false, 'error' => 'Nessun link selezionato']);
            exit;
        }

        $linkModel = new InternalLink();
        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($linkIds as $linkId) {
                    // Verify link belongs to project before deleting
                    $link = $linkModel->find((int) $linkId);
                    if ($link && $link['project_id'] == (int) $id) {
                        $linkModel->delete((int) $linkId);
                        $count++;
                    }
                }
                $message = "Eliminati {$count} link";
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Azione non valida']);
                exit;
        }

        $projectModel->updateStats((int) $id);

        echo json_encode(['success' => true, 'message' => $message, 'count' => $count]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
});

// Anchor Bulk Actions (AJAX) - Delete links by anchor text
Router::post('/internal-links/project/{id}/links/bulk-anchors', function ($id) {
    Middleware::auth();
    Middleware::csrf();

    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find((int) $id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $anchors = $input['anchors'] ?? [];

        if (empty($anchors)) {
            echo json_encode(['success' => false, 'error' => 'Nessun anchor selezionato']);
            exit;
        }

        $linkModel = new InternalLink();
        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($anchors as $anchor) {
                    $deleted = $linkModel->deleteByAnchor((int) $id, $anchor);
                    $count += $deleted;
                }
                $message = "Eliminati {$count} link";
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Azione non valida']);
                exit;
        }

        $projectModel->updateStats((int) $id);

        echo json_encode(['success' => true, 'message' => $message, 'count' => $count]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
});

// Export API
Router::get('/internal-links/project/{id}/export', function ($id) {
    Middleware::auth();

    $user = Auth::user();
    $projectModel = new Project();
    $project = $projectModel->find((int) $id, $user['id']);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/internal-links'));
        exit;
    }

    $type = $_GET['type'] ?? 'links';
    $linkModel = new InternalLink();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    if ($type === 'anchors') {
        fputcsv($output, ['Anchor Text', 'Usage Count', 'Unique Destinations', 'Destinations']);
        $anchors = $linkModel->getMostUsedAnchors((int) $id, 10000);
        foreach ($anchors as $anchor) {
            fputcsv($output, [
                $anchor['anchor'] ?? '',
                $anchor['count'] ?? 0,
                $anchor['unique_destinations'] ?? 1,
                $anchor['destinations'] ?? '',
            ]);
        }
    } elseif ($type === 'orphans') {
        fputcsv($output, ['URL', 'Keyword']);
        $orphans = $linkModel->getOrphanPages((int) $id);
        foreach ($orphans as $orphan) {
            fputcsv($output, [$orphan['url'], $orphan['keyword'] ?? '']);
        }
    } else {
        fputcsv($output, ['Source URL', 'Destination URL', 'Anchor Text', 'Is Internal', 'Relevance Score', 'Juice Flow']);
        $links = $linkModel->getByProject((int) $id, 1, 100000);
        foreach ($links['data'] as $link) {
            fputcsv($output, [
                $link['source_url'] ?? '',
                $link['destination_url'] ?? '',
                $link['anchor_text'] ?? '',
                $link['is_internal'] ? 'Yes' : 'No',
                $link['ai_relevance_score'] ?? '',
                $link['ai_juice_flow'] ?? '',
            ]);
        }
    }

    fclose($output);
    exit;
});
