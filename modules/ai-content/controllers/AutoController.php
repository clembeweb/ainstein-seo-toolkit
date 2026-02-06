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
     *
     * Riceve dal form:
     * - keywords: testo con keyword (una per riga)
     *
     * Valori di default (non più inviati dal form):
     * - scheduled_at: domani ore 09:00
     * - sources_count: 3
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

        // Valori di default - scheduled_at NULL (da pianificare dalla coda)
        $scheduledAt = null;
        $sourcesCount = 3;

        if (empty($keywordsText)) {
            $_SESSION['_flash']['error'] = 'Inserisci almeno una keyword';
            Router::redirect('/ai-content/projects/' . $id . '/auto/add');
            return;
        }

        // Parse keywords (una per riga)
        $lines = explode("\n", $keywordsText);
        $keywordsData = [];
        foreach ($lines as $line) {
            $kw = trim($line);
            if (!empty($kw)) {
                $keywordsData[] = [
                    'keyword' => $kw,
                    'scheduled_at' => $scheduledAt,
                    'sources_count' => $sourcesCount
                ];
            }
        }

        if (empty($keywordsData)) {
            $_SESSION['_flash']['error'] = 'Nessuna keyword valida trovata';
            Router::redirect('/ai-content/projects/' . $id . '/auto/add');
            return;
        }

        // Rimuovi duplicati basandosi sulla keyword
        $seen = [];
        $uniqueKeywordsData = [];
        foreach ($keywordsData as $item) {
            if (!isset($seen[$item['keyword']])) {
                $seen[$item['keyword']] = true;
                $uniqueKeywordsData[] = $item;
            }
        }

        try {
            $inserted = $this->queue->addBulk($user['id'], $id, $uniqueKeywordsData);

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
     *
     * Salva solo: auto_publish, wp_site_id (e is_active se presente)
     * Il numero di fonti e la schedulazione sono ora per-keyword
     */
    public function updateSettings(int $id): void
    {
        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/ai-content');
            return;
        }

        $autoPublish = isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1';
        $wpSiteId = !empty($_POST['wp_site_id']) ? (int) $_POST['wp_site_id'] : null;
        $isActive = isset($_POST['is_active']) ? ($_POST['is_active'] === '1') : null;
        $generateCover = isset($_POST['generate_cover']) && $_POST['generate_cover'] === '1';

        // Se auto_publish ma nessun sito selezionato
        if ($autoPublish && !$wpSiteId) {
            $_SESSION['_flash']['error'] = 'Seleziona un sito WordPress per la pubblicazione automatica';
            Router::redirect('/ai-content/projects/' . $id . '/auto/settings');
            return;
        }

        try {
            $data = [
                'auto_publish' => $autoPublish,
                'wp_site_id' => $wpSiteId,
                'generate_cover' => $generateCover,
            ];

            // Includi is_active solo se presente nel form
            if ($isActive !== null) {
                $data['is_active'] = $isActive;
            }

            $this->autoConfig->upsert($id, $data);

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
            // Reset status a pending con scheduled_at impostato a NOW()
            $this->queue->updateStatus($queueId, 'pending');
            $this->queue->updateScheduledAt($queueId, date('Y-m-d H:i:s'));

            $_SESSION['_flash']['success'] = 'Keyword rimessa in coda per elaborazione immediata';

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel retry: ' . $e->getMessage();
        }

        Router::redirect('/ai-content/projects/' . $id . '/auto/queue');
    }

    /**
     * Aggiorna singolo item in coda (AJAX)
     * POST /ai-content/projects/{id}/auto/queue/{queueId}/update
     */
    public function updateQueueItem(int $id, int $queueId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->getAutoProject($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Verifica che l'item appartenga al progetto e sia pending
        $item = $this->queue->findById($queueId);

        if (!$item || $item['project_id'] !== $id || $item['user_id'] !== $user['id']) {
            echo json_encode(['success' => false, 'error' => 'Item non trovato']);
            return;
        }

        if ($item['status'] !== 'pending') {
            echo json_encode(['success' => false, 'error' => 'Solo item in attesa possono essere modificati']);
            return;
        }

        $data = [];
        if (isset($_POST['scheduled_at']) && !empty($_POST['scheduled_at'])) {
            $data['scheduled_at'] = date('Y-m-d H:i:s', strtotime($_POST['scheduled_at']));
        }
        if (isset($_POST['sources_count'])) {
            $data['sources_count'] = max(1, min(10, (int)$_POST['sources_count']));
        }

        if (empty($data)) {
            echo json_encode(['success' => false, 'error' => 'Nessun dato da aggiornare']);
            return;
        }

        $success = $this->queue->update($queueId, $data);
        echo json_encode(['success' => $success]);
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

        // Return job info - actual processing will happen via SSE stream
        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'keywords_requested' => $maxItems,
            'message' => "Job creato per {$maxItems} keyword"
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
            ProcessJob::STEP_COVER => 'Generazione copertina',
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
     * Process queue items via SSE (Server-Sent Events)
     * GET /ai-content/projects/{id}/auto/process/stream
     *
     * This replaces the spawnWorker approach that doesn't work on shared hosting
     */
    public function processStream(int $id): void
    {
        // ===========================================
        // FASE 1: Operazioni che richiedono la sessione
        // ===========================================

        // Auth e salvataggio dati utente in variabili locali
        $user = Auth::user();
        $userId = $user['id'];

        // Verifica progetto
        $project = $this->project->find($id, $userId);
        if (!$project || $project['type'] !== 'auto') {
            // Possiamo ancora usare la sessione qui per errori
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            return;
        }

        // Get active job
        $activeJob = $this->processJob->getActiveForProject($id);
        if (!$activeJob) {
            $_SESSION['_flash']['error'] = 'Nessun job attivo';
            return;
        }

        $jobId = $activeJob['id'];

        // Get config (auto_publish, wp_site_id, generate_cover)
        $config = $this->autoConfig->findByProject($id);
        $autoPublish = (bool) ($config['auto_publish'] ?? false);
        $wpSiteId = $config['wp_site_id'] ?? null;
        $generateCover = (bool) ($config['generate_cover'] ?? true);

        // Load WP site for publishing (richiede userId)
        $wpSite = null;
        if ($autoPublish && $wpSiteId) {
            $wpSite = $this->wpSite->find($wpSiteId, $userId);
        }

        // ===========================================
        // FASE 2: Rilascio sessione e avvio SSE
        // ===========================================

        // CRITICAL: Rilascia il lock della sessione per permettere altre richieste
        // Dopo questo punto NON usare più $_SESSION
        session_write_close();

        // Disable output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Mark job as running
        $this->processJob->start($jobId);
        $this->sendSseEvent('started', ['job_id' => $jobId]);

        // Load services
        $serpService = null;
        $scraperService = new \Services\ScraperService();
        $briefBuilder = new \Modules\AiContent\Services\BriefBuilderService();
        $articleGenerator = new \Modules\AiContent\Services\ArticleGeneratorService();

        $keywordsCompleted = 0;
        $keywordsFailed = 0;
        $articlesGenerated = 0;
        $creditsUsed = 0;
        $maxKeywords = (int) $activeJob['keywords_requested'];

        // Process each pending keyword
        while ($keywordsCompleted + $keywordsFailed < $maxKeywords) {
            // Check if cancelled
            if ($this->processJob->isCancelled($jobId)) {
                $this->sendSseEvent('cancelled', ['message' => 'Processo annullato']);
                break;
            }

            // Get next pending item
            $queueItem = $this->queue->getNextPendingForProject($id);
            if (!$queueItem) {
                break;
            }

            $keyword = $queueItem['keyword'];
            $queueId = $queueItem['id'];

            // Update status to processing
            $this->queue->updateStatus($queueId, 'processing');
            $this->processJob->updateProgress($jobId, [
                'current_queue_id' => $queueId,
                'current_keyword' => $keyword,
                'current_step' => 'serp'
            ]);

            $this->sendSseEvent('progress', [
                'keyword' => $keyword,
                'step' => 'serp',
                'step_label' => 'Estrazione SERP',
                'completed' => $keywordsCompleted,
                'failed' => $keywordsFailed,
                'total' => $maxKeywords
            ]);

            try {
                // Step 1: SERP extraction
                if ($serpService === null) {
                    $serpService = new \Modules\AiContent\Services\SerpApiService();
                }

                $serpResults = $serpService->search(
                    $keyword,
                    $queueItem['language'] ?? 'it',
                    $queueItem['location'] ?? 'Italy'
                );

                // Reconnect DB after external API call
                \Core\Database::reconnect();

                // Step 2: Scrape sources
                $this->processJob->updateProgress($jobId, ['current_step' => 'scraping']);
                $this->sendSseEvent('progress', [
                    'keyword' => $keyword,
                    'step' => 'scraping',
                    'step_label' => 'Scraping fonti',
                    'completed' => $keywordsCompleted,
                    'failed' => $keywordsFailed,
                    'total' => $maxKeywords
                ]);

                $scrapedSources = [];
                // Usa sources_count dalla queue item (default 3 se non presente)
                $sourcesCount = (int) ($queueItem['sources_count'] ?? 3);
                $urlsToScrape = array_slice(
                    array_column($serpResults['organic'], 'url'),
                    0,
                    $sourcesCount
                );

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

                // Reconnect DB
                \Core\Database::reconnect();

                // Step 3: Generate brief
                $this->processJob->updateProgress($jobId, ['current_step' => 'brief']);
                $this->sendSseEvent('progress', [
                    'keyword' => $keyword,
                    'step' => 'brief',
                    'step_label' => 'Generazione brief',
                    'completed' => $keywordsCompleted,
                    'failed' => $keywordsFailed,
                    'total' => $maxKeywords
                ]);

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

                // Reconnect DB
                \Core\Database::reconnect();

                // Step 4: Generate article
                $this->processJob->updateProgress($jobId, ['current_step' => 'article']);
                $this->sendSseEvent('progress', [
                    'keyword' => $keyword,
                    'step' => 'article',
                    'step_label' => 'Generazione articolo',
                    'completed' => $keywordsCompleted,
                    'failed' => $keywordsFailed,
                    'total' => $maxKeywords
                ]);

                // Add scraped sources to brief
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
                $internalLinksPool = new \Modules\AiContent\Models\InternalLinksPool();
                $internalLinks = $internalLinksPool->getActiveByProject($id, 50);
                if (!empty($internalLinks)) {
                    $brief['internal_links_pool'] = $internalLinks;
                }

                $targetWords = $brief['recommended_word_count'] ?? 1500;
                $articleResult = $articleGenerator->generate($brief, (int) $targetWords, $userId);

                if (!$articleResult['success']) {
                    throw new \Exception($articleResult['error'] ?? 'Generazione articolo fallita');
                }

                // Reconnect DB
                \Core\Database::reconnect();

                // Step 5: Save to database
                $this->processJob->updateProgress($jobId, ['current_step' => 'saving']);
                $this->sendSseEvent('progress', [
                    'keyword' => $keyword,
                    'step' => 'saving',
                    'step_label' => 'Salvataggio',
                    'completed' => $keywordsCompleted,
                    'failed' => $keywordsFailed,
                    'total' => $maxKeywords
                ]);

                // Create keyword record
                $keywordModel = new \Modules\AiContent\Models\Keyword();
                $keywordId = $keywordModel->create([
                    'user_id' => $userId,
                    'project_id' => $id,
                    'keyword' => $keyword,
                    'language' => $queueItem['language'] ?? 'it',
                    'location' => $queueItem['location'] ?? 'Italy'
                ]);

                // Create article record (first create, then update with content)
                $articleModel = new \Modules\AiContent\Models\Article();
                $articleId = $articleModel->create([
                    'keyword_id' => $keywordId,
                    'user_id' => $userId,
                    'project_id' => $id,
                    'status' => 'draft'
                ]);

                // Update with generated content
                $articleModel->updateContent($articleId, [
                    'title' => $articleResult['title'],
                    'meta_description' => $articleResult['meta_description'],
                    'content' => $articleResult['content'],
                    'word_count' => $articleResult['word_count'],
                    'model_used' => $articleResult['model_used'] ?? null,
                    'generation_time_ms' => $articleResult['generation_time_ms'] ?? null
                ]);

                // Link queue item
                $this->queue->linkGenerated($queueId, $keywordId, $articleId);

                // Step 5b: Generate cover image (optional)
                if ($generateCover) {
                    $this->processJob->updateProgress($jobId, ['current_step' => 'cover']);
                    $this->sendSseEvent('progress', [
                        'keyword' => $keyword,
                        'step' => 'cover',
                        'step_label' => 'Generazione copertina',
                        'completed' => $keywordsCompleted,
                        'failed' => $keywordsFailed,
                        'total' => $maxKeywords
                    ]);

                    try {
                        $coverService = new \Modules\AiContent\Services\CoverImageService();
                        $coverResult = $coverService->generate(
                            $articleId,
                            $articleResult['title'],
                            $keyword,
                            mb_substr(strip_tags($articleResult['content']), 0, 500),
                            $userId
                        );

                        \Core\Database::reconnect();

                        if ($coverResult['success']) {
                            $articleModel->updateCoverImage($articleId, $coverResult['path']);
                        }
                    } catch (\Exception $e) {
                        // Non blocca: l'immagine è opzionale
                        \Core\Database::reconnect();
                    }
                }

                // Step 6: Publish to WordPress (optional)
                if ($autoPublish && $wpSite) {
                    $this->sendSseEvent('progress', [
                        'keyword' => $keyword,
                        'step' => 'publishing',
                        'step_label' => 'Pubblicazione WordPress',
                        'completed' => $keywordsCompleted,
                        'failed' => $keywordsFailed,
                        'total' => $maxKeywords
                    ]);

                    try {
                        $wpResult = $this->publishToWordPress($wpSite, [
                            'title' => $articleResult['title'],
                            'content' => $articleResult['content'],
                            'meta_description' => $articleResult['meta_description'],
                            'status' => 'draft'
                        ]);

                        if ($wpResult['success'] && !empty($wpResult['post_id'])) {
                            $articleModel->markPublished($articleId, $wpSite['id'], $wpResult['post_id']);
                        }
                    } catch (\Exception $e) {
                        // Log but don't fail the whole process
                    }
                }

                // Success!
                $keywordsCompleted++;
                $articlesGenerated++;
                $this->processJob->incrementCompleted($jobId);

                $this->sendSseEvent('keyword_completed', [
                    'keyword' => $keyword,
                    'article_id' => $articleId,
                    'completed' => $keywordsCompleted,
                    'failed' => $keywordsFailed,
                    'total' => $maxKeywords
                ]);

            } catch (\Exception $e) {
                // Handle error
                $keywordsFailed++;
                $this->queue->updateStatus($queueId, 'error', $e->getMessage());
                $this->processJob->incrementFailed($jobId);

                // Reconnect DB in case error was DB-related
                \Core\Database::reconnect();

                $this->sendSseEvent('keyword_error', [
                    'keyword' => $keyword,
                    'error' => $e->getMessage(),
                    'completed' => $keywordsCompleted,
                    'failed' => $keywordsFailed,
                    'total' => $maxKeywords
                ]);
            }

            // Small delay between keywords
            usleep(500000); // 500ms
        }

        // Mark job as completed
        $this->processJob->complete($jobId);

        $this->sendSseEvent('completed', [
            'completed' => $keywordsCompleted,
            'failed' => $keywordsFailed,
            'articles_generated' => $articlesGenerated,
            'message' => "Completato: {$keywordsCompleted} articoli generati, {$keywordsFailed} errori"
        ]);
    }

    /**
     * Send SSE event
     */
    private function sendSseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Publish article to WordPress site
     */
    private function publishToWordPress(array $wpSite, array $postData): array
    {
        $url = rtrim($wpSite['url'], '/') . '/wp-json/seo-toolkit/v1/posts';
        $apiKey = $wpSite['api_key'];

        $scraper = new \Services\ScraperService();

        try {
            $result = $scraper->postJson($url, $postData, [
                'timeout' => 60,
                'headers' => [
                    'X-SEO-Toolkit-Key: ' . $apiKey,
                    'Content-Type: application/json'
                ]
            ]);

            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'API error'
                ];
            }

            return [
                'success' => true,
                'post_id' => $result['data']['post_id'] ?? null,
                'post_url' => $result['data']['post_url'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
