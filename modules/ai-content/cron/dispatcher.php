<?php
/**
 * AI Content Dispatcher (CRON Scheduler)
 *
 * Elabora direttamente le keyword senza usare exec/popen
 * (compatibile con hosting condiviso come SiteGround)
 *
 * Crontab (run every minute):
 *   * * * * * php /path/to/modules/ai-content/cron/dispatcher.php
 */

// Solo CLI
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}

// Bootstrap
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

// Helper functions for CLI context
if (!function_exists('getModuleSetting')) {
    function getModuleSetting(string $moduleSlug, string $key, mixed $default = null): mixed
    {
        return \Core\ModuleLoader::getSetting($moduleSlug, $key, $default);
    }
}

use Core\Database;
use Core\Credits;
use Modules\AiContent\Models\AutoConfig;
use Modules\AiContent\Models\ProcessJob;
use Modules\AiContent\Models\Queue;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\WpSite;
use Modules\AiContent\Services\SerpApiService;
use Modules\AiContent\Services\BriefBuilderService;
use Modules\AiContent\Services\ArticleGeneratorService;
use Services\ScraperService;

// Log file giornaliero
define('LOG_FILE', BASE_PATH . '/storage/logs/dispatcher_' . date('Y-m-d') . '.log');

/**
 * Log message
 */
function logDispatcher(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";

    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);

    // Echo per monitoraggio CLI
    echo $logLine;
}

/**
 * Elabora una singola keyword
 */
function processKeyword(array $queueItem, int $userId): array
{
    $keyword = $queueItem['keyword'];
    $queueId = $queueItem['id'];
    $projectId = $queueItem['project_id'];
    $autoSelectSources = (int) ($queueItem['sources_count'] ?? 3);

    $result = [
        'success' => false,
        'keyword_id' => null,
        'article_id' => null,
        'credits_used' => 0,
        'error' => null
    ];

    try {
        // Step 1: SERP extraction
        logDispatcher("    [1/5] SERP extraction...");
        $serpService = new SerpApiService();
        $serpResults = $serpService->search(
            $keyword,
            $queueItem['language'] ?? 'it',
            $queueItem['location'] ?? 'Italy'
        );
        Database::reconnect();

        // Step 2: Scraping
        logDispatcher("    [2/5] Scraping " . min($autoSelectSources, count($serpResults['organic'])) . " fonti...");
        $scraperService = new ScraperService();
        $scrapedSources = [];
        $urlsToScrape = array_slice(array_column($serpResults['organic'], 'url'), 0, $autoSelectSources);

        foreach ($urlsToScrape as $url) {
            try {
                $scraped = $scraperService->scrape($url);
                if ($scraped && !empty($scraped['content'])) {
                    $scrapedSources[] = $scraped;
                }
            } catch (\Exception $e) {
                // Skip failed URLs
            }
        }

        if (empty($scrapedSources)) {
            throw new \Exception('Impossibile estrarre contenuto dalle fonti');
        }
        Database::reconnect();

        // Step 3: Brief generation
        logDispatcher("    [3/5] Generazione brief...");
        $briefBuilder = new BriefBuilderService();
        $keywordData = [
            'keyword' => $keyword,
            'language' => $queueItem['language'] ?? 'it',
            'location' => $queueItem['location'] ?? 'Italy'
        ];

        $brief = $briefBuilder->build(
            $keywordData,
            $serpResults['organic'],
            $serpResults['paa'],
            $scrapedSources,
            $userId
        );
        Database::reconnect();

        // Step 4: Article generation
        logDispatcher("    [4/5] Generazione articolo...");
        $brief['scraped_sources'] = array_map(function($source) {
            return [
                'url' => $source['url'],
                'title' => $source['title'],
                'content' => $source['content'],
                'headings' => $source['headings'],
                'word_count' => $source['word_count']
            ];
        }, $scrapedSources);

        // Add internal links pool if available
        $projectId = $queueItem['project_id'] ?? null;
        if ($projectId) {
            $internalLinksPool = new \Modules\AiContent\Models\InternalLinksPool();
            $internalLinks = $internalLinksPool->getActiveByProject($projectId, 50);
            if (!empty($internalLinks)) {
                $brief['internal_links_pool'] = $internalLinks;
                logDispatcher("    Pool link interni: " . count($internalLinks) . " link attivi");
            }
        }

        $targetWords = $brief['recommended_word_count'] ?? 1500;
        $articleGenerator = new ArticleGeneratorService();
        $articleResult = $articleGenerator->generate($brief, (int) $targetWords, $userId);

        if (!$articleResult['success']) {
            throw new \Exception($articleResult['error'] ?? 'Generazione articolo fallita');
        }

        // Reconnect robusto con retry (MySQL timeout dopo chiamata AI lunga)
        $maxReconnectAttempts = 3;
        for ($attempt = 1; $attempt <= $maxReconnectAttempts; $attempt++) {
            Database::reconnect();
            usleep(100000); // 100ms delay per permettere a MySQL di accettare la connessione
            if (Database::ping()) {
                break;
            }
            logDispatcher("    Reconnect attempt {$attempt}/{$maxReconnectAttempts}...", 'WARN');
            if ($attempt < $maxReconnectAttempts) {
                sleep(1); // 1 secondo tra i tentativi
            }
        }

        if (!Database::ping()) {
            throw new \Exception('Impossibile ristabilire connessione al database dopo ' . $maxReconnectAttempts . ' tentativi');
        }

        // Step 5: Save to database
        logDispatcher("    [5/5] Salvataggio...");

        // Create keyword record
        logDispatcher("      Creating keyword...");
        $keywordModel = new Keyword();
        $keywordId = $keywordModel->create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'keyword' => $keyword,
            'language' => $queueItem['language'] ?? 'it',
            'location' => $queueItem['location'] ?? 'Italy'
        ]);
        logDispatcher("      Keyword created: ID {$keywordId}");

        // Force reconnect before article creation
        Database::reconnect();

        // Create article record
        logDispatcher("      Creating article...");
        $articleModel = new Article();
        $articleId = $articleModel->create([
            'keyword_id' => $keywordId,
            'user_id' => $userId,
            'project_id' => $projectId,
            'status' => 'draft'
        ]);
        logDispatcher("      Article created: ID {$articleId}");

        // Force reconnect before content update
        Database::reconnect();

        // Update with content
        logDispatcher("      Updating article content...");
        $articleModel->updateContent($articleId, [
            'title' => $articleResult['title'],
            'meta_description' => $articleResult['meta_description'],
            'content' => $articleResult['content'],
            'word_count' => $articleResult['word_count'],
            'model_used' => $articleResult['model_used'] ?? null
        ]);
        logDispatcher("      Article content updated");

        $result['success'] = true;
        $result['keyword_id'] = $keywordId;
        $result['article_id'] = $articleId;
        $result['credits_used'] = $articleResult['credits_used'] ?? 0;

    } catch (\Exception $e) {
        $result['error'] = $e->getMessage();
        Database::reconnect();
    }

    return $result;
}

/**
 * Pubblica su WordPress
 */
function publishToWordPress(int $articleId, int $wpSiteId, int $userId): bool
{
    try {
        $wpSiteModel = new WpSite();
        $wpSite = $wpSiteModel->find($wpSiteId, $userId);

        if (!$wpSite || !$wpSite['is_active']) {
            return false;
        }

        $articleModel = new Article();
        $article = $articleModel->find($articleId, $userId);

        if (!$article || empty($article['content'])) {
            return false;
        }

        $url = rtrim($wpSite['url'], '/') . '/wp-json/seo-toolkit/v1/posts';
        $scraper = new ScraperService();

        $result = $scraper->postJson($url, [
            'title' => $article['title'],
            'content' => $article['content'],
            'meta_description' => $article['meta_description'],
            'status' => 'draft'
        ], [
            'timeout' => 60,
            'headers' => [
                'X-SEO-Toolkit-Key: ' . $wpSite['api_key'],
                'Content-Type: application/json'
            ]
        ]);

        if (isset($result['data']['post_id'])) {
            $articleModel->markPublished($articleId, $wpSiteId, $result['data']['post_id']);
            return true;
        }
    } catch (\Exception $e) {
        logDispatcher("      WordPress publish error: " . $e->getMessage(), 'WARN');
    }

    return false;
}

/**
 * Main dispatcher
 *
 * Logica semplificata:
 * - Trova progetti attivi (is_active=1, type='auto') con item pending schedulati
 * - Processa un item alla volta per esecuzione cron
 * - Nessun limite giornaliero, nessun orario fisso
 */
function runDispatcher(): void
{
    // Allow unlimited execution time for cron processing
    set_time_limit(0);

    logDispatcher("=== DISPATCHER START ===");

    $autoConfig = new AutoConfig();
    $projectsToRun = $autoConfig->getProjectsToRun();

    if (empty($projectsToRun)) {
        logDispatcher("Nessun progetto con item pronti da processare");
        logDispatcher("=== DISPATCHER END ===\n");
        return;
    }

    logDispatcher("Progetti con item pronti: " . count($projectsToRun));

    $totalArticles = 0;
    $totalErrors = 0;

    foreach ($projectsToRun as $config) {
        $projectId = (int) $config['project_id'];
        $userId = (int) $config['user_id'];
        $projectName = $config['project_name'] ?? "Project #{$projectId}";

        logDispatcher("--- Progetto: {$projectName} (ID: {$projectId}) ---");

        // Verifica job già in corso
        $processJob = new ProcessJob();
        $activeJob = $processJob->getActiveForProject($projectId);
        if ($activeJob) {
            logDispatcher("  Job già in corso (ID: {$activeJob['id']}), skip");
            continue;
        }

        // Prendi il prossimo item pending con scheduled_at <= NOW()
        $queue = new Queue();
        $queueItem = $queue->getNextPendingForProject($projectId);

        if (!$queueItem) {
            logDispatcher("  Nessun item pronto, skip");
            continue;
        }

        // Verifica crediti
        if (!Credits::hasEnough($userId, 10)) {
            logDispatcher("  Crediti insufficienti, skip");
            continue;
        }

        $keyword = $queueItem['keyword'];
        $queueId = $queueItem['id'];

        logDispatcher("  Processo keyword: {$keyword} (sources: " . ($queueItem['sources_count'] ?? 3) . ")");

        // Crea job
        $jobId = $processJob->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'type' => ProcessJob::TYPE_CRON,
            'keywords_requested' => 1
        ]);
        $processJob->start($jobId);
        $processJob->updateProgress($jobId, [
            'current_queue_id' => $queueId,
            'current_keyword' => $keyword,
            'current_step' => 'serp'
        ]);

        // Aggiorna queue status
        $queue->updateStatus($queueId, 'processing');

        // Elabora keyword (usa sources_count dal queue item)
        $result = processKeyword($queueItem, $userId);

        // Force reconnect after long AI processing
        // Recreate models with fresh connection
        Database::reconnect();
        $queue = new Queue();
        $processJob = new ProcessJob();
        $autoConfig = new AutoConfig();

        if ($result['success']) {
            // Successo
            $queue->linkGenerated($queueId, $result['keyword_id'], $result['article_id']);
            $processJob->incrementCompleted($jobId);
            $processJob->complete($jobId);
            $autoConfig->updateLastRun($projectId);

            $totalArticles++;
            logDispatcher("  COMPLETATO! Articolo ID: {$result['article_id']}");

            // Notifica completamento
            try {
                Database::reconnect();
                \Services\NotificationService::send($userId, 'operation_completed',
                    "Articolo AI generato: {$keyword}", [
                    'icon' => 'check-circle',
                    'color' => 'emerald',
                    'action_url' => '/ai-content/projects/' . $projectId . '/articles/' . $result['article_id'],
                    'body' => "L'articolo per \"{$keyword}\" e stato generato con successo.",
                    'data' => ['module' => 'ai-content', 'project_id' => $projectId, 'article_id' => $result['article_id']],
                ]);
            } catch (\Exception $e) {
                logDispatcher("  Notifica non inviata: " . $e->getMessage(), 'WARN');
            }

            // Auto-publish se configurato
            if (!empty($config['auto_publish']) && !empty($config['wp_site_id'])) {
                logDispatcher("  Pubblicazione WordPress...");
                if (publishToWordPress($result['article_id'], $config['wp_site_id'], $userId)) {
                    logDispatcher("    Pubblicato");
                } else {
                    logDispatcher("    Pubblicazione fallita", 'WARN');
                }
            }
        } else {
            // Errore
            $queue->updateStatus($queueId, 'error', $result['error']);
            $processJob->incrementFailed($jobId);
            $processJob->markError($jobId, $result['error']);

            $totalErrors++;
            logDispatcher("  ERRORE: {$result['error']}", 'ERROR');

            // Notifica errore
            try {
                Database::reconnect();
                \Services\NotificationService::send($userId, 'operation_failed',
                    'Generazione AI fallita', [
                    'icon' => 'exclamation-triangle',
                    'color' => 'red',
                    'action_url' => '/ai-content/projects/' . $projectId . '/queue',
                    'body' => "Errore durante la generazione per \"{$keyword}\": {$result['error']}",
                    'data' => ['module' => 'ai-content', 'project_id' => $projectId],
                ]);
            } catch (\Exception $e) {
                logDispatcher("  Notifica non inviata: " . $e->getMessage(), 'WARN');
            }
        }
    }

    logDispatcher("=== DISPATCHER END === Articoli: {$totalArticles}, Errori: {$totalErrors}\n");
}

// Esegui
try {
    runDispatcher();
} catch (\Exception $e) {
    logDispatcher("FATAL: " . $e->getMessage(), 'FATAL');
    exit(1);
}

exit(0);
