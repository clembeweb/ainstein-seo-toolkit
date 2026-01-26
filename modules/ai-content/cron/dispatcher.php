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
function processKeyword(array $queueItem, array $config, int $userId): array
{
    $keyword = $queueItem['keyword'];
    $queueId = $queueItem['id'];
    $projectId = $queueItem['project_id'];
    $autoSelectSources = (int) ($config['auto_select_sources'] ?? 3);

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

        $targetWords = $brief['recommended_word_count'] ?? 1500;
        $articleGenerator = new ArticleGeneratorService();
        $articleResult = $articleGenerator->generate($brief, (int) $targetWords, $userId);

        if (!$articleResult['success']) {
            throw new \Exception($articleResult['error'] ?? 'Generazione articolo fallita');
        }
        Database::reconnect();

        // Step 5: Save to database
        logDispatcher("    [5/5] Salvataggio...");

        // Create keyword record
        $keywordModel = new Keyword();
        $keywordId = $keywordModel->create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'keyword' => $keyword,
            'language' => $queueItem['language'] ?? 'it',
            'location' => $queueItem['location'] ?? 'Italy'
        ]);

        // Create article record
        $articleModel = new Article();
        $articleId = $articleModel->create([
            'keyword_id' => $keywordId,
            'user_id' => $userId,
            'project_id' => $projectId,
            'status' => 'draft'
        ]);

        // Update with content
        $articleModel->updateContent($articleId, [
            'title' => $articleResult['title'],
            'meta_description' => $articleResult['meta_description'],
            'content' => $articleResult['content'],
            'word_count' => $articleResult['word_count'],
            'model_used' => $articleResult['model_used'] ?? null
        ]);

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
 */
function runDispatcher(): void
{
    logDispatcher("=== DISPATCHER START ===");

    $currentTime = date('H:i');
    logDispatcher("Ora corrente: {$currentTime}");

    $autoConfig = new AutoConfig();
    $projectsToRun = $autoConfig->getProjectsToRun();

    if (empty($projectsToRun)) {
        logDispatcher("Nessun progetto da processare a quest'ora");
        logDispatcher("=== DISPATCHER END ===\n");
        return;
    }

    logDispatcher("Progetti da processare: " . count($projectsToRun));

    $totalArticles = 0;
    $totalErrors = 0;

    foreach ($projectsToRun as $config) {
        $projectId = (int) $config['project_id'];
        $userId = (int) $config['user_id'];
        $projectName = $config['project_name'] ?? "Project #{$projectId}";

        logDispatcher("--- Progetto: {$projectName} (ID: {$projectId}) ---");

        // Reset contatore se nuovo giorno
        $autoConfig->resetDailyCounterIfNeeded($projectId);

        // Verifica limite giornaliero
        $remaining = $autoConfig->getRemainingToday($projectId);
        if ($remaining <= 0) {
            logDispatcher("  Limite giornaliero raggiunto, skip");
            continue;
        }
        logDispatcher("  Articoli rimanenti oggi: {$remaining}");

        // Verifica job già in corso
        $processJob = new ProcessJob();
        $activeJob = $processJob->getActiveForProject($projectId);
        if ($activeJob) {
            logDispatcher("  Job già in corso (ID: {$activeJob['id']}), skip");
            continue;
        }

        // Verifica keyword in coda
        $queue = new Queue();
        $pendingItems = $queue->getPending($projectId, 1); // 1 per esecuzione CRON

        if (empty($pendingItems)) {
            logDispatcher("  Nessuna keyword in coda, skip");
            continue;
        }

        // Verifica crediti
        if (!Credits::hasEnough($userId, 10)) {
            logDispatcher("  Crediti insufficienti, skip");
            continue;
        }

        $queueItem = $pendingItems[0];
        $keyword = $queueItem['keyword'];
        $queueId = $queueItem['id'];

        logDispatcher("  Processo keyword: {$keyword}");

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

        // Elabora keyword
        $result = processKeyword($queueItem, $config, $userId);

        if ($result['success']) {
            // Successo
            $queue->linkGenerated($queueId, $result['keyword_id'], $result['article_id']);
            $processJob->incrementCompleted($jobId);
            $processJob->complete($jobId);
            $autoConfig->incrementArticlesToday($projectId);
            $autoConfig->updateLastRun($projectId);

            $totalArticles++;
            logDispatcher("  ✓ COMPLETATO! Articolo ID: {$result['article_id']}");

            // Auto-publish se configurato
            if (!empty($config['auto_publish']) && !empty($config['wp_site_id'])) {
                logDispatcher("  Pubblicazione WordPress...");
                if (publishToWordPress($result['article_id'], $config['wp_site_id'], $userId)) {
                    logDispatcher("    ✓ Pubblicato");
                } else {
                    logDispatcher("    ✗ Pubblicazione fallita", 'WARN');
                }
            }
        } else {
            // Errore
            $queue->updateStatus($queueId, 'error', $result['error']);
            $processJob->incrementFailed($jobId);
            $processJob->markError($jobId, $result['error']);

            $totalErrors++;
            logDispatcher("  ✗ ERRORE: {$result['error']}", 'ERROR');
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
