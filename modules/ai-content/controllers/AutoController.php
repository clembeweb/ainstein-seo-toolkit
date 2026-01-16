<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\AiContent\Models\Project;
use Modules\AiContent\Models\Queue;
use Modules\AiContent\Models\AutoConfig;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\WpSite;
use Modules\AiContent\Models\ProcessJob;

/**
 * AutoController
 * Gestisce modalità automatica per generazione articoli schedulata
 */
class AutoController
{
    private Project $project;
    private Queue $queue;
    private AutoConfig $autoConfig;
    private Article $article;
    private WpSite $wpSite;
    private ProcessJob $processJob;

    public function __construct()
    {
        $this->project = new Project();
        $this->queue = new Queue();
        $this->autoConfig = new AutoConfig();
        $this->article = new Article();
        $this->wpSite = new WpSite();
        $this->processJob = new ProcessJob();
    }

    /**
     * Verifica progetto e ownership
     */
    private function getAutoProject(int $id, int $userId): ?array
    {
        $project = $this->project->find($id, $userId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            return null;
        }

        if ($project['type'] !== 'auto') {
            $_SESSION['_flash']['error'] = 'Questo progetto non è in modalità automatica';
            return null;
        }

        return $project;
    }

    /**
     * Dashboard automazione
     * GET /ai-content/projects/{id}/auto
     */
    public function dashboard(int $id): string
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        // Carica config automazione
        $config = $this->autoConfig->findByProject($id);
        if (!$config) {
            // Crea config default se non esiste
            $this->autoConfig->create($id);
            $config = $this->autoConfig->findByProject($id);
        }

        // Statistiche coda
        $stats = $this->queue->getStats($id);
        $todayStats = $this->queue->getTodayStats($id);

        // Ultimi items per status
        $pendingItems = $this->queue->getByProject($id, 'pending', 10);
        $completedItems = $this->queue->getByProject($id, 'completed', 10);
        $errorItems = $this->queue->getByProject($id, 'error', 10);

        return View::render('ai-content/auto/dashboard', [
            'title' => $project['name'] . ' - Automazione',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'config' => $config,
            'stats' => $stats,
            'todayStats' => $todayStats,
            'pendingItems' => $pendingItems,
            'completedItems' => $completedItems,
            'errorItems' => $errorItems,
        ]);
    }

    /**
     * Form aggiunta keyword bulk
     * GET /ai-content/projects/{id}/auto/add
     */
    public function addKeywords(int $id): string
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        $config = $this->autoConfig->findByProject($id);

        return View::render('ai-content/auto/add-keywords', [
            'title' => 'Aggiungi Keyword - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'config' => $config,
        ]);
    }

    /**
     * Salva keyword in coda
     * POST /ai-content/projects/{id}/auto/add
     */
    public function storeKeywords(int $id): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        $keywordsText = trim($_POST['keywords'] ?? '');

        if (empty($keywordsText)) {
            $_SESSION['_flash']['error'] = 'Inserisci almeno una keyword';
            Router::redirect('/ai-content/projects/' . $id . '/auto/add');
            return;
        }

        // Parse keywords (una per riga)
        $lines = explode("\n", $keywordsText);
        $keywords = [];
        foreach ($lines as $line) {
            $kw = trim($line);
            if (!empty($kw)) {
                $keywords[] = $kw;
            }
        }

        if (empty($keywords)) {
            $_SESSION['_flash']['error'] = 'Nessuna keyword valida trovata';
            Router::redirect('/ai-content/projects/' . $id . '/auto/add');
            return;
        }

        // Rimuovi duplicati
        $keywords = array_unique($keywords);

        // Calcola slot di scheduling
        $slots = $this->autoConfig->calculateScheduleSlots($id, count($keywords));

        if (empty($slots)) {
            $_SESSION['_flash']['error'] = 'Impossibile calcolare gli slot di scheduling. Verifica la configurazione.';
            Router::redirect('/ai-content/projects/' . $id . '/auto/add');
            return;
        }

        try {
            $inserted = $this->queue->addBulk($user['id'], $id, $keywords, $slots);

            $_SESSION['_flash']['success'] = "Aggiunte {$inserted} keyword alla coda di generazione";
            Router::redirect('/ai-content/projects/' . $id . '/auto/queue');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'inserimento: ' . $e->getMessage();
            Router::redirect('/ai-content/projects/' . $id . '/auto/add');
        }
    }

    /**
     * Lista completa coda
     * GET /ai-content/projects/{id}/auto/queue
     */
    public function queue(int $id): string
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        // Filtro status da query string
        $statusFilter = $_GET['status'] ?? null;
        $validStatuses = ['pending', 'processing', 'completed', 'error'];
        if ($statusFilter && !in_array($statusFilter, $validStatuses)) {
            $statusFilter = null;
        }

        $items = $this->queue->getByProject($id, $statusFilter, 200);
        $stats = $this->queue->getStats($id);

        return View::render('ai-content/auto/queue', [
            'title' => 'Coda - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'items' => $items,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Form impostazioni automazione
     * GET /ai-content/projects/{id}/auto/settings
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            exit;
        }

        $config = $this->autoConfig->findByProject($id);
        if (!$config) {
            $this->autoConfig->create($id);
            $config = $this->autoConfig->findByProject($id);
        }

        // Lista siti WordPress per select
        $wpSites = $this->wpSite->allByUser($user['id']);

        return View::render('ai-content/auto/settings', [
            'title' => 'Impostazioni Auto - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'config' => $config,
            'wpSites' => $wpSites,
        ]);
    }

    /**
     * Salva impostazioni automazione
     * POST /ai-content/projects/{id}/auto/settings
     */
    public function updateSettings(int $id): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        // Parse input
        $articlesPerDay = (int) ($_POST['articles_per_day'] ?? 1);
        $articlesPerDay = max(1, min(10, $articlesPerDay)); // Limita 1-10

        // Parse orari pubblicazione
        $publishTimesRaw = trim($_POST['publish_times'] ?? '09:00');
        $publishTimes = [];
        foreach (explode(',', $publishTimesRaw) as $time) {
            $time = trim($time);
            if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                $publishTimes[] = $time;
            }
        }
        if (empty($publishTimes)) {
            $publishTimes = ['09:00'];
        }

        $autoSelectSources = (int) ($_POST['auto_select_sources'] ?? 3);
        $autoSelectSources = max(1, min(10, $autoSelectSources)); // Limita 1-10

        $autoPublish = isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1';
        $wpSiteId = !empty($_POST['wp_site_id']) ? (int) $_POST['wp_site_id'] : null;

        // Se auto_publish ma nessun sito selezionato
        if ($autoPublish && !$wpSiteId) {
            $_SESSION['_flash']['error'] = 'Seleziona un sito WordPress per la pubblicazione automatica';
            Router::redirect('/ai-content/projects/' . $id . '/auto/settings');
            return;
        }

        try {
            $this->autoConfig->upsert($id, [
                'articles_per_day' => $articlesPerDay,
                'publish_times' => $publishTimes,
                'auto_select_sources' => $autoSelectSources,
                'auto_publish' => $autoPublish,
                'wp_site_id' => $wpSiteId,
            ]);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/ai-content/projects/' . $id . '/auto/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/ai-content/projects/' . $id . '/auto/settings');
        }
    }

    /**
     * Attiva/disattiva automazione
     * POST /ai-content/projects/{id}/auto/toggle
     */
    public function toggle(int $id): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        $config = $this->autoConfig->findByProject($id);
        $newStatus = !($config['is_active'] ?? true);

        try {
            $this->autoConfig->toggle($id, $newStatus);

            $statusText = $newStatus ? 'attivata' : 'disattivata';
            $_SESSION['_flash']['success'] = "Automazione {$statusText}";

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel cambio stato: ' . $e->getMessage();
        }

        Router::redirect('/ai-content/projects/' . $id . '/auto');
    }

    /**
     * Elimina singolo item dalla coda
     * POST /ai-content/projects/{id}/auto/queue/{queueId}/delete
     */
    public function deleteQueueItem(int $id, int $queueId): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        try {
            $deleted = $this->queue->delete($queueId, $user['id']);

            if ($deleted) {
                $_SESSION['_flash']['success'] = 'Keyword rimossa dalla coda';
            } else {
                $_SESSION['_flash']['error'] = 'Keyword non trovata o già elaborata';
            }

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella rimozione: ' . $e->getMessage();
        }

        Router::redirect('/ai-content/projects/' . $id . '/auto/queue');
    }

    /**
     * Svuota tutti i pending dalla coda
     * POST /ai-content/projects/{id}/auto/queue/clear
     */
    public function clearQueue(int $id): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        try {
            $cleared = $this->queue->clearPending($id);

            if ($cleared > 0) {
                $_SESSION['_flash']['success'] = "Rimosse {$cleared} keyword dalla coda";
            } else {
                $_SESSION['_flash']['info'] = 'Nessuna keyword in attesa da rimuovere';
            }

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nello svuotamento: ' . $e->getMessage();
        }

        Router::redirect('/ai-content/projects/' . $id . '/auto/queue');
    }

    /**
     * Retry singolo item in errore
     * POST /ai-content/projects/{id}/auto/queue/{queueId}/retry
     */
    public function retryQueueItem(int $id, int $queueId): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        $item = $this->queue->find($queueId, $user['id']);

        if (!$item || $item['status'] !== 'error') {
            $_SESSION['_flash']['error'] = 'Item non trovato o non in stato di errore';
            Router::redirect('/ai-content/projects/' . $id . '/auto/queue');
            return;
        }

        try {
            // Calcola nuovo slot
            $slots = $this->autoConfig->calculateScheduleSlots($id, 1);
            $newSchedule = $slots[0] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Reset status a pending con nuovo schedule
            $this->queue->updateStatus($queueId, 'pending');

            $_SESSION['_flash']['success'] = 'Keyword rimessa in coda per elaborazione';

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel retry: ' . $e->getMessage();
        }

        Router::redirect('/ai-content/projects/' . $id . '/auto/queue');
    }

    // ========================================
    // API Methods for Process Control
    // ========================================

    /**
     * Start manual processing
     * POST /ai-content/projects/{id}/auto/process/start
     * Returns JSON
     */
    public function startProcess(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project || $project['type'] !== 'auto') {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Check if already running
        $activeJob = $this->processJob->getActiveForProject($id);
        if ($activeJob) {
            echo json_encode([
                'success' => false,
                'error' => 'Un processo è già in esecuzione',
                'job_id' => $activeJob['id']
            ]);
            return;
        }

        // Get pending count
        $pendingCount = $this->queue->countByProject($id, 'pending');
        if ($pendingCount === 0) {
            echo json_encode(['success' => false, 'error' => 'Nessuna keyword in coda']);
            return;
        }

        // Get max to process from request or use pending count
        $maxItems = (int) ($_POST['max_items'] ?? $pendingCount);
        $maxItems = min($maxItems, $pendingCount);

        // Create job
        $jobId = $this->processJob->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => ProcessJob::TYPE_MANUAL,
            'keywords_requested' => $maxItems,
        ]);

        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'Impossibile creare il job']);
            return;
        }

        // Make pending items immediately processable (bypass scheduled_at)
        $this->queue->makeImmediatelyProcessable($id, $maxItems);

        // Spawn worker process
        $spawned = $this->spawnWorker($jobId);

        if (!$spawned) {
            $this->processJob->markError($jobId, 'Impossibile avviare il worker');
            echo json_encode(['success' => false, 'error' => 'Impossibile avviare il worker']);
            return;
        }

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'keywords_requested' => $maxItems,
            'message' => "Avviato processo per {$maxItems} keyword"
        ]);
    }

    /**
     * Get process status (for polling)
     * GET /ai-content/projects/{id}/auto/process/status
     * Returns JSON
     */
    public function getProcessStatus(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project || $project['type'] !== 'auto') {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Get job_id from request or get latest
        $jobId = (int) ($_GET['job_id'] ?? 0);

        if ($jobId) {
            $job = $this->processJob->findByUser($jobId, $user['id']);
        } else {
            $job = $this->processJob->getLatestForProject($id);
        }

        if (!$job) {
            echo json_encode([
                'success' => true,
                'has_job' => false,
                'message' => 'Nessun job trovato'
            ]);
            return;
        }

        // Calculate progress percentage
        $total = (int) $job['keywords_requested'];
        $completed = (int) $job['keywords_completed'];
        $failed = (int) $job['keywords_failed'];
        $processed = $completed + $failed;
        $progress = $total > 0 ? round(($processed / $total) * 100) : 0;

        // Get step label
        $stepLabels = [
            ProcessJob::STEP_PENDING => 'In attesa',
            ProcessJob::STEP_SERP => 'Estrazione SERP',
            ProcessJob::STEP_SCRAPING => 'Scraping contenuti',
            ProcessJob::STEP_BRIEF => 'Generazione brief',
            ProcessJob::STEP_ARTICLE => 'Generazione articolo',
            ProcessJob::STEP_SAVING => 'Salvataggio',
            ProcessJob::STEP_DONE => 'Completato',
        ];

        $currentStepLabel = $stepLabels[$job['current_step']] ?? $job['current_step'];

        echo json_encode([
            'success' => true,
            'has_job' => true,
            'job' => [
                'id' => (int) $job['id'],
                'status' => $job['status'],
                'type' => $job['type'],
                'keywords_requested' => $total,
                'keywords_completed' => $completed,
                'keywords_failed' => $failed,
                'articles_generated' => (int) $job['articles_generated'],
                'credits_used' => (float) $job['credits_used'],
                'current_keyword' => $job['current_keyword'],
                'current_step' => $job['current_step'],
                'current_step_label' => $currentStepLabel,
                'progress' => $progress,
                'error_message' => $job['error_message'],
                'started_at' => $job['started_at'],
                'completed_at' => $job['completed_at'],
            ],
            'is_running' => in_array($job['status'], [ProcessJob::STATUS_PENDING, ProcessJob::STATUS_RUNNING]),
            'is_completed' => $job['status'] === ProcessJob::STATUS_COMPLETED,
            'is_error' => $job['status'] === ProcessJob::STATUS_ERROR,
            'is_cancelled' => $job['status'] === ProcessJob::STATUS_CANCELLED,
        ]);
    }

    /**
     * Cancel running process
     * POST /ai-content/projects/{id}/auto/process/cancel
     * Returns JSON
     */
    public function cancelProcess(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project || $project['type'] !== 'auto') {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);

        if (!$jobId) {
            // Try to get active job
            $activeJob = $this->processJob->getActiveForProject($id);
            if ($activeJob) {
                $jobId = $activeJob['id'];
            }
        }

        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'Nessun job da annullare']);
            return;
        }

        // Verify ownership
        $job = $this->processJob->findByUser($jobId, $user['id']);
        if (!$job) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        if (!in_array($job['status'], [ProcessJob::STATUS_PENDING, ProcessJob::STATUS_RUNNING])) {
            echo json_encode(['success' => false, 'error' => 'Il job non può essere annullato']);
            return;
        }

        $cancelled = $this->processJob->cancel($jobId);

        if ($cancelled) {
            echo json_encode([
                'success' => true,
                'message' => 'Processo annullato'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Impossibile annullare il processo'
            ]);
        }
    }

    /**
     * Spawn worker process for a job
     */
    private function spawnWorker(int $jobId): bool
    {
        $phpBinary = PHP_BINARY;
        $workerScript = dirname(__DIR__) . '/cron/process_queue.php';

        if (!file_exists($workerScript)) {
            error_log("Worker script not found: {$workerScript}");
            return false;
        }

        // Log file for worker output
        $logFile = \ROOT_PATH . '/storage/logs/worker_' . $jobId . '.log';

        // Build command based on OS
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Use cmd.exe with /c to properly spawn background process
            // Escape paths for Windows
            $phpBinary = str_replace('/', '\\', $phpBinary);
            $workerScript = str_replace('/', '\\', $workerScript);
            $logFile = str_replace('/', '\\', $logFile);

            // Use WScript or PowerShell for true background execution on Windows
            // Fallback to simple cmd approach that works in most cases
            $cmd = "start \"Worker\" /B cmd /c \"\"{$phpBinary}\" \"{$workerScript}\" --job_id={$jobId}\" > \"{$logFile}\" 2>&1";

            // Alternative using PowerShell (more reliable on modern Windows)
            // $psCmd = "powershell -Command \"Start-Process -NoNewWindow -FilePath '{$phpBinary}' -ArgumentList '{$workerScript}','--job_id={$jobId}'\"";

            $handle = popen($cmd, 'r');
            if ($handle) {
                pclose($handle);
            }

            // Give the process a moment to start
            usleep(100000); // 100ms
        } else {
            // Unix: use nohup and &
            $cmd = "nohup {$phpBinary} \"{$workerScript}\" --job_id={$jobId} > \"{$logFile}\" 2>&1 &";
            exec($cmd);
        }

        return true;
    }
}
