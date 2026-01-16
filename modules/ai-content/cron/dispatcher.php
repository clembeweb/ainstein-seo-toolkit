<?php
/**
 * AI Content Dispatcher (Master CRON Scheduler)
 *
 * Checks all active AUTO projects and spawns workers for those
 * that should run based on their publish_times configuration.
 *
 * Usage:
 *   php dispatcher.php
 *
 * Crontab (run every minute to check publish times):
 *   * * * * * php /path/to/dispatcher.php
 */

require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Modules\AiContent\Models\AutoConfig;
use Modules\AiContent\Models\ProcessJob;
use Modules\AiContent\Models\Queue;

// Configuration
define('LOG_FILE', BASE_PATH . '/storage/logs/dispatcher.log');
define('WORKER_SCRIPT', __DIR__ . '/process_queue.php');

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
 * Check if there's already a running job for this project
 */
function hasRunningJob(int $projectId): bool
{
    $processJob = new ProcessJob();
    $activeJob = $processJob->getActiveForProject($projectId);
    return $activeJob !== null;
}

/**
 * Get pending keywords count for project
 */
function getPendingCount(int $projectId): int
{
    $queue = new Queue();
    return $queue->countByProject($projectId, 'pending');
}

/**
 * Create a new process job for the project
 */
function createJob(int $projectId, int $userId, int $keywordsCount): int
{
    $processJob = new ProcessJob();
    return $processJob->create([
        'project_id' => $projectId,
        'user_id' => $userId,
        'type' => ProcessJob::TYPE_CRON,
        'keywords_requested' => $keywordsCount,
    ]);
}

/**
 * Spawn worker process for a job
 */
function spawnWorker(int $jobId): bool
{
    $phpBinary = PHP_BINARY;
    $workerScript = WORKER_SCRIPT;

    // Check if script exists
    if (!file_exists($workerScript)) {
        logMessage("Worker script not found: {$workerScript}", 'ERROR');
        return false;
    }

    // Build command based on OS
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows: use start /B for background execution
        $cmd = "start /B {$phpBinary} {$workerScript} --job_id={$jobId} > NUL 2>&1";
        pclose(popen($cmd, 'r'));
    } else {
        // Unix: use nohup and &
        $cmd = "nohup {$phpBinary} {$workerScript} --job_id={$jobId} > /dev/null 2>&1 &";
        exec($cmd);
    }

    logMessage("Spawned worker for job #{$jobId}");
    return true;
}

/**
 * Main dispatcher function
 */
function runDispatcher(): void
{
    logMessage("=== Dispatcher Started ===");

    $autoConfig = new AutoConfig();

    // Get all projects that should run now
    $projectsToRun = $autoConfig->getProjectsToRun();

    if (empty($projectsToRun)) {
        logMessage("No projects scheduled to run at this time");
        logMessage("=== Dispatcher Finished ===");
        return;
    }

    logMessage("Found " . count($projectsToRun) . " project(s) to process");

    $jobsCreated = 0;

    foreach ($projectsToRun as $config) {
        $projectId = (int) $config['project_id'];
        $userId = (int) $config['user_id'];
        $projectName = $config['project_name'] ?? "Project #{$projectId}";

        logMessage("Checking project: {$projectName} (ID: {$projectId})");

        // Check if already running
        if (hasRunningJob($projectId)) {
            logMessage("  - Skipped: Already has a running job", 'WARN');
            continue;
        }

        // Check pending keywords
        $pendingCount = getPendingCount($projectId);
        if ($pendingCount === 0) {
            logMessage("  - Skipped: No pending keywords in queue");
            continue;
        }

        // Check daily limit
        $remaining = $autoConfig->getRemainingToday($projectId);
        if ($remaining <= 0) {
            logMessage("  - Skipped: Daily limit reached");
            continue;
        }

        // Calculate how many to process (min of pending, remaining daily limit)
        $toProcess = min($pendingCount, $remaining);

        logMessage("  - Creating job for {$toProcess} keyword(s)");

        // Create job
        $jobId = createJob($projectId, $userId, $toProcess);

        if ($jobId) {
            logMessage("  - Job #{$jobId} created");

            // Spawn worker
            if (spawnWorker($jobId)) {
                $jobsCreated++;
            }
        } else {
            logMessage("  - Failed to create job", 'ERROR');
        }
    }

    logMessage("=== Dispatcher Finished. Jobs created: {$jobsCreated} ===");
}

// Run the dispatcher
try {
    runDispatcher();
} catch (\Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'FATAL');
    exit(1);
}

exit(0);
