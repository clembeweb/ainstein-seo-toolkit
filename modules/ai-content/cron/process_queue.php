<?php
/**
 * AI Content Queue Processor (CRON Worker)
 *
 * Processes pending items from aic_queue for AUTO projects.
 *
 * Usage:
 *   php process_queue.php                           # Process next pending items (legacy mode)
 *   php process_queue.php --job_id=123              # Process specific job
 *   php process_queue.php --project_id=8 --max=5   # Process project with limit
 *
 * Crontab:
 *   0,15,30,45 * * * * php /path/to/process_queue.php
 */

// Bootstrap application (CLI mode)
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

// Helper functions for CLI context
if (!function_exists('getModuleSetting')) {
    function getModuleSetting(string $moduleSlug, string $key, mixed $default = null): mixed
    {
        return \Core\ModuleLoader::getSetting($moduleSlug, $key, $default);
    }
}

use Core\Database;
use Modules\AiContent\Models\Queue;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\SerpResult;
use Modules\AiContent\Models\Source;
use Modules\AiContent\Models\AutoConfig;
use Modules\AiContent\Models\ProcessJob;
use Modules\AiContent\Services\SerpApiService;
use Modules\AiContent\Services\ContentScraperService;
use Modules\AiContent\Services\BriefBuilderService;
use Modules\AiContent\Services\ArticleGeneratorService;

// Configuration
define('DEFAULT_MAX_ITEMS', 3);
define('LOG_FILE', BASE_PATH . '/storage/logs/queue_processor.log');

// Global state
$currentJobId = null;
$processJob = null;

/**
 * Parse CLI arguments
 */
function parseArgs(): array
{
    global $argv;
    $args = [
        'job_id' => null,
        'project_id' => null,
        'max_items' => DEFAULT_MAX_ITEMS,
    ];

    foreach ($argv as $arg) {
        if (preg_match('/^--job_id=(\d+)$/', $arg, $m)) {
            $args['job_id'] = (int) $m[1];
        }
        if (preg_match('/^--project_id=(\d+)$/', $arg, $m)) {
            $args['project_id'] = (int) $m[1];
        }
        if (preg_match('/^--max=(\d+)$/', $arg, $m)) {
            $args['max_items'] = (int) $m[1];
        }
    }

    return $args;
}

/**
 * Log message to file and stdout
 */
function logMessage(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";

    // Log to file
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);

    // Echo to stdout (for CLI monitoring)
    echo $logLine;
}

/**
 * Update job progress
 */
function updateJobProgress(int $jobId, array $data): void
{
    global $processJob;
    if (!$processJob) {
        $processJob = new ProcessJob();
    }
    $processJob->updateProgress($jobId, $data);
}

/**
 * Check if job was cancelled
 */
function isJobCancelled(int $jobId): bool
{
    global $processJob;
    if (!$processJob) {
        $processJob = new ProcessJob();
    }
    return $processJob->isCancelled($jobId);
}

/**
 * Main processing function
 */
function processQueue(array $args): void
{
    global $currentJobId, $processJob;

    $jobId = $args['job_id'];
    $projectId = $args['project_id'];
    $maxItems = $args['max_items'];

    $currentJobId = $jobId;
    $processJob = new ProcessJob();
    $queue = new Queue();
    $autoConfig = new AutoConfig();

    logMessage("=== Starting Queue Processor ===");
    if ($jobId) {
        logMessage("Job ID: {$jobId}");
    }

    // If job_id provided, start the job
    if ($jobId) {
        $job = $processJob->find($jobId);
        if (!$job) {
            logMessage("Job #{$jobId} not found", 'ERROR');
            return;
        }

        if ($job['status'] === ProcessJob::STATUS_CANCELLED) {
            logMessage("Job #{$jobId} was cancelled", 'WARN');
            return;
        }

        $processJob->start($jobId);
        $projectId = $job['project_id'];
        $maxItems = $job['keywords_requested'];
    }

    $processed = 0;
    $failed = 0;

    while ($processed + $failed < $maxItems) {
        // Check for cancellation
        if ($jobId && isJobCancelled($jobId)) {
            logMessage("Job #{$jobId} cancelled by user", 'WARN');
            break;
        }

        // Get next pending item
        if ($projectId) {
            $item = $queue->getNextPendingForProject($projectId);
        } else {
            $item = $queue->getNextPending();
        }

        if (!$item) {
            logMessage("No more pending items found");
            break;
        }

        // Link queue item to job if applicable
        if ($jobId) {
            Database::update('aic_queue', ['job_id' => $jobId], 'id = ?', [$item['id']]);
        }

        logMessage("Processing item #{$item['id']}: '{$item['keyword']}'");

        // Update job progress
        if ($jobId) {
            updateJobProgress($jobId, [
                'current_queue_id' => $item['id'],
                'current_keyword' => $item['keyword'],
                'current_step' => ProcessJob::STEP_SERP,
            ]);
        }

        try {
            // Mark queue item as processing
            $queue->updateStatus($item['id'], 'processing');

            // Process the item with step callbacks
            $result = processQueueItem($item, $jobId);

            if ($result['success']) {
                // Link generated records and mark completed
                $queue->linkGenerated(
                    $item['id'],
                    $result['keyword_id'],
                    $result['article_id']
                );

                // Update auto config daily counter
                $autoConfig->incrementArticlesToday($item['project_id']);

                // Update job progress
                if ($jobId) {
                    $processJob->incrementCompleted($jobId);
                    if (isset($result['credits_used'])) {
                        $processJob->addCreditsUsed($jobId, $result['credits_used']);
                    }
                }

                logMessage("SUCCESS: Item #{$item['id']} completed. Article ID: {$result['article_id']}");
                $processed++;
            } else {
                // Mark as error
                $queue->updateStatus($item['id'], 'error', $result['error']);

                if ($jobId) {
                    $processJob->incrementFailed($jobId);
                }

                logMessage("ERROR: Item #{$item['id']} failed: {$result['error']}", 'ERROR');
                $failed++;
            }

        } catch (\Exception $e) {
            $queue->updateStatus($item['id'], 'error', $e->getMessage());

            if ($jobId) {
                $processJob->incrementFailed($jobId);
            }

            logMessage("EXCEPTION: Item #{$item['id']}: " . $e->getMessage(), 'ERROR');
            $failed++;
        }

        // Small delay between items
        sleep(2);
    }

    // Complete job
    if ($jobId) {
        $processJob->complete($jobId);
        $autoConfig->updateLastRun($projectId);
    }

    logMessage("=== Queue Processor Finished. Processed: {$processed}, Failed: {$failed} ===");
}

/**
 * Process a single queue item with step tracking
 */
function processQueueItem(array $item, ?int $jobId = null): array
{
    $userId = $item['user_id'];
    $projectId = $item['project_id'];
    $keywordText = $item['keyword'];
    $language = $item['language'] ?? 'it';
    $location = $item['location'] ?? 'Italy';
    $sourcesCount = $item['sources_count'] ?? 3;

    $totalCreditsUsed = 0;

    // === STEP 1: SERP ===
    logMessage("  - Extracting SERP for: {$keywordText}");
    if ($jobId) {
        updateJobProgress($jobId, ['current_step' => ProcessJob::STEP_SERP]);
    }

    try {
        $serpService = new SerpApiService();
        $serpData = $serpService->search($keywordText, $language, $location);
    } catch (\Exception $e) {
        return ['success' => false, 'error' => 'SERP extraction failed: ' . $e->getMessage()];
    }

    if (empty($serpData['organic'])) {
        return ['success' => false, 'error' => 'No SERP results found'];
    }

    logMessage("  - Found " . count($serpData['organic']) . " organic results");

    // Check cancellation
    if ($jobId && isJobCancelled($jobId)) {
        return ['success' => false, 'error' => 'Job cancelled'];
    }

    // === STEP 2: Create keyword record ===
    $keywordModel = new Keyword();

    if ($keywordModel->exists($keywordText, $userId)) {
        logMessage("  - Keyword already exists, finding existing record");
        $existingKw = Database::fetch(
            "SELECT id FROM aic_keywords WHERE keyword = ? AND user_id = ?",
            [$keywordText, $userId]
        );
        $keywordId = $existingKw['id'];
    } else {
        $keywordId = $keywordModel->create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'keyword' => $keywordText,
            'language' => $language,
            'location' => $location
        ]);
        logMessage("  - Created keyword record ID: {$keywordId}");
    }

    // Save SERP results
    $serpResultModel = new SerpResult();
    $serpResultModel->saveForKeyword($keywordId, $serpData['organic']);

    if (!empty($serpData['paa'])) {
        $keywordModel->savePaaQuestions($keywordId, $serpData['paa']);
    }

    $keywordModel->markSerpExtracted($keywordId);

    // === STEP 3: SCRAPING ===
    if ($jobId) {
        updateJobProgress($jobId, ['current_step' => ProcessJob::STEP_SCRAPING]);
    }

    $selectedSources = array_slice($serpData['organic'], 0, $sourcesCount);
    $scrapedSources = [];

    logMessage("  - Scraping top {$sourcesCount} sources");

    $scraperService = new ContentScraperService();
    foreach ($selectedSources as $source) {
        // Check cancellation
        if ($jobId && isJobCancelled($jobId)) {
            return ['success' => false, 'error' => 'Job cancelled'];
        }

        $scrapeResult = $scraperService->extractContent($source['url'], $userId);

        if ($scrapeResult['success']) {
            $scrapedSources[] = [
                'url' => $source['url'],
                'title' => $scrapeResult['title'] ?? $source['title'],
                'content' => $scrapeResult['content'],
                'headings' => $scrapeResult['headings'],
                'word_count' => $scrapeResult['word_count']
            ];
            logMessage("    - Scraped: {$source['url']} ({$scrapeResult['word_count']} words)");
        } else {
            logMessage("    - Failed to scrape: {$source['url']}", 'WARN');
        }

        usleep(500000);
    }

    if (empty($scrapedSources)) {
        return ['success' => false, 'error' => 'Failed to scrape any sources'];
    }

    // === STEP 4: BRIEF ===
    if ($jobId) {
        updateJobProgress($jobId, ['current_step' => ProcessJob::STEP_BRIEF]);
    }

    logMessage("  - Generating brief");

    $briefBuilder = new BriefBuilderService();
    $brief = $briefBuilder->build(
        ['keyword' => $keywordText, 'language' => $language, 'location' => $location],
        $serpData['organic'],
        $serpData['paa'] ?? [],
        $scrapedSources,
        $userId
    );

    $brief['scraped_sources'] = $scrapedSources;

    // Add internal links pool if available
    if ($projectId) {
        $internalLinksPool = new \Modules\AiContent\Models\InternalLinksPool();
        $internalLinks = $internalLinksPool->getActiveByProject($projectId, 50);
        if (!empty($internalLinks)) {
            $brief['internal_links_pool'] = $internalLinks;
            logMessage("  - Internal links pool: " . count($internalLinks) . " links");
        }
    }

    $targetWords = $brief['recommended_word_count'] ?? 1500;
    logMessage("  - Brief generated. Target words: {$targetWords}");

    // Check cancellation
    if ($jobId && isJobCancelled($jobId)) {
        return ['success' => false, 'error' => 'Job cancelled'];
    }

    // === STEP 5: CREATE ARTICLE RECORD ===
    $articleModel = new Article();
    $articleId = $articleModel->create([
        'user_id' => $userId,
        'project_id' => $projectId,
        'keyword_id' => $keywordId,
        'status' => 'generating',
        'brief_data' => json_encode($brief)
    ]);

    logMessage("  - Created article record ID: {$articleId}");

    // Save sources
    $sourceModel = new Source();
    foreach ($scrapedSources as $source) {
        $sourceId = $sourceModel->create([
            'article_id' => $articleId,
            'url' => $source['url'],
            'title' => $source['title'],
            'is_custom' => false
        ]);

        $sourceModel->updateScraped($sourceId, [
            'content' => $source['content'],
            'headings' => $source['headings'],
            'word_count' => $source['word_count']
        ]);
    }

    // === STEP 6: ARTICLE GENERATION ===
    if ($jobId) {
        updateJobProgress($jobId, ['current_step' => ProcessJob::STEP_ARTICLE]);
    }

    logMessage("  - Generating article content with AI");

    $articleGenerator = new ArticleGeneratorService();

    if (!$articleGenerator->isConfigured()) {
        $articleModel->updateStatus($articleId, 'failed');
        return ['success' => false, 'error' => 'AI service not configured'];
    }

    $generationResult = $articleGenerator->generate($brief, $targetWords, $userId);

    if (!$generationResult['success']) {
        $articleModel->updateStatus($articleId, 'failed');
        return ['success' => false, 'error' => 'Article generation failed: ' . ($generationResult['error'] ?? 'Unknown error')];
    }

    // === STEP 7: SAVING ===
    if ($jobId) {
        updateJobProgress($jobId, ['current_step' => ProcessJob::STEP_SAVING]);
    }

    $articleModel->updateContent($articleId, [
        'title' => $generationResult['title'],
        'content' => $generationResult['content'],
        'meta_description' => $generationResult['meta_description'],
        'word_count' => $generationResult['word_count'],
        'ai_model' => $generationResult['model_used'] ?? 'claude',
        'generation_time_ms' => $generationResult['generation_time_ms'] ?? 0,
        'status' => 'ready'
    ]);

    logMessage("  - Article generated: '{$generationResult['title']}' ({$generationResult['word_count']} words)");

    // Save brief to keyword
    $keywordModel->saveBrief($keywordId, $brief);

    // Auto-publish if configured
    $autoConfig = new AutoConfig();
    $config = $autoConfig->findByProject($projectId);

    if ($config && $config['auto_publish'] && $config['wp_site_id']) {
        logMessage("  - Auto-publishing to WordPress site ID: {$config['wp_site_id']}");
        // TODO: Implement auto-publish
    }

    return [
        'success' => true,
        'keyword_id' => $keywordId,
        'article_id' => $articleId,
        'credits_used' => $totalCreditsUsed
    ];
}

// Run the processor
try {
    $args = parseArgs();
    processQueue($args);
} catch (\Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'FATAL');

    // Mark job as error if applicable
    global $currentJobId, $processJob;
    if ($currentJobId && $processJob) {
        $processJob->markError($currentJobId, $e->getMessage());
    }

    exit(1);
}

exit(0);
