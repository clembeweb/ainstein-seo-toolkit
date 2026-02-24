<?php

namespace Modules\ContentCreator\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Core\Credits;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Url;
use Modules\ContentCreator\Models\Job;
use Modules\ContentCreator\Models\OperationLog;
use Modules\ContentCreator\Services\ContentScraperService;
use Services\AiService;

class GeneratorController
{
    private Project $project;
    private Url $url;

    private const SCRAPE_CREDIT_COST = 1;
    private const GENERATE_CREDIT_COST = 3;

    // Marker per parsing output AI
    private const HTML_START = '```html';
    private const HTML_END = '```';

    public function __construct()
    {
        $this->project = new Project();
        $this->url = new Url();
    }

    /**
     * Verifica progetto e ownership
     */
    private function getProject(int $id, int $userId): ?array
    {
        $project = $this->project->findAccessible($id, $userId);
        if (!$project) {
            return null;
        }
        return $project;
    }

    /**
     * Invia evento SSE
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    // ─────────────────────────────────────────────
    //  SCRAPE JOB
    // ─────────────────────────────────────────────

    /**
     * POST - Avvia job di scraping
     */
    public function startScrapeJob(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobModel = new Job();

        // Verifica nessun job attivo di tipo scrape
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_SCRAPE);
        if ($activeJob) {
            echo json_encode([
                'error' => true,
                'message' => 'Un job di scraping è già in esecuzione',
                'job_id' => (int) $activeJob['id'],
            ]);
            return;
        }

        // Conta URL pending
        $stats = $this->project->getStats($id);
        $pendingCount = (int) $stats['pending'];

        if ($pendingCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna URL da scrapare']);
            return;
        }

        // Verifica crediti
        $estimatedCredits = $pendingCount * self::SCRAPE_CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $estimatedCredits)) {
            $balance = Credits::getBalance($user['id']);
            echo json_encode([
                'error' => true,
                'message' => "Crediti insufficienti. Richiesti: {$estimatedCredits}, disponibili: {$balance}",
            ]);
            return;
        }

        // Crea job
        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_SCRAPE,
            'items_requested' => $pendingCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $pendingCount,
            'estimated_credits' => $estimatedCredits,
        ]);
    }

    /**
     * GET SSE - Stream scraping in tempo reale
     */
    public function scrapeStream(int $id): void
    {
        // Verifica auth senza redirect (SSE)
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || (int) $job['project_id'] !== $id) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        // Chiudi sessione PRIMA del loop SSE
        session_write_close();

        // CRITICO: continua esecuzione anche se proxy chiude connessione
        ignore_user_abort(true);
        set_time_limit(0);

        // Headers SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Avvia job se pending
        if ($job['status'] === Job::STATUS_PENDING) {
            $jobModel->start($jobId);
        }

        $total = (int) $job['items_requested'];
        $completed = 0;
        $failed = 0;
        $creditsUsed = 0;

        $this->sendEvent('started', [
            'job_id' => $jobId,
            'total' => $total,
        ]);

        $scraperService = new ContentScraperService();
        $operationLog = new OperationLog();

        while (true) {
            Database::reconnect();

            // Check cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            // Prossima URL pending
            $items = $this->url->getNextPending($id, 1);
            if (empty($items)) {
                // Nessuna URL rimasta - completa job
                Database::reconnect();
                $jobModel->complete($jobId);

                // Log operazione
                $operationLog->log([
                    'user_id' => $user['id'],
                    'project_id' => $id,
                    'operation' => 'scrape',
                    'credits_used' => $creditsUsed,
                    'status' => 'success',
                    'details' => [
                        'completed' => $completed,
                        'failed' => $failed,
                        'job_id' => $jobId,
                    ],
                ]);

                $this->sendEvent('completed', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            $item = $items[0];
            $percent = $total > 0 ? round((($completed + $failed) / $total) * 100) : 0;

            // Aggiorna progresso job
            $jobModel->updateProgress($jobId, [
                'current_item_id' => $item['id'],
                'current_item' => $item['url'],
            ]);

            $this->sendEvent('progress', [
                'current_url' => $item['url'],
                'completed' => $completed,
                'failed' => $failed,
                'total' => $total,
                'percent' => $percent,
            ]);

            try {
                $result = $scraperService->scrapeUrl($item['url']);

                Database::reconnect();

                // Salva dati scraping
                $this->url->updateScrapeData($item['id'], [
                    'scraped_title' => $result['title'],
                    'scraped_h1' => $result['h1'],
                    'scraped_meta_title' => $result['meta_title'],
                    'scraped_meta_description' => $result['meta_description'],
                    'scraped_content' => $result['content'],
                    'scraped_price' => $result['price'],
                    'scraped_word_count' => $result['word_count'],
                ]);

                // Aggiorna slug se estratto
                if (!empty($result['slug'])) {
                    $this->url->update($item['id'], ['slug' => $result['slug']]);
                }

                // Consuma crediti
                Credits::consume($user['id'], self::SCRAPE_CREDIT_COST, 'cc_scrape', 'content-creator', [
                    'url' => $item['url'],
                    'word_count' => $result['word_count'],
                ]);

                $completed++;
                $creditsUsed += self::SCRAPE_CREDIT_COST;

                $jobModel->incrementCompleted($jobId);
                $jobModel->addCreditsUsed($jobId, self::SCRAPE_CREDIT_COST);

                $this->sendEvent('item_completed', [
                    'url_id' => (int) $item['id'],
                    'url' => $item['url'],
                    'title' => $result['title'],
                    'word_count' => $result['word_count'],
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round((($completed + $failed) / $total) * 100) : 0,
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                $this->url->markScrapeError($item['id'], $e->getMessage());

                $failed++;
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'url_id' => (int) $item['id'],
                    'url' => $item['url'],
                    'error' => $e->getMessage(),
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round((($completed + $failed) / $total) * 100) : 0,
                ]);
            }

            // Pausa tra items (300ms)
            usleep(300000);
        }

        exit;
    }

    /**
     * GET - Polling fallback per stato scrape job
     */
    public function scrapeJobStatus(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || (int) $job['project_id'] !== $id) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
    }

    /**
     * POST - Annulla scrape job
     */
    public function cancelScrapeJob(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || (int) $job['project_id'] !== $id) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $jobModel->cancel($jobId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Job annullato',
        ]);
    }

    // ─────────────────────────────────────────────
    //  GENERATE JOB
    // ─────────────────────────────────────────────

    /**
     * POST - Avvia job di generazione AI
     */
    public function startGenerateJob(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        // Verifica AI configurata
        $ai = new AiService('content-creator');
        if (!$ai->isConfigured()) {
            echo json_encode(['error' => true, 'message' => 'AI non configurata. Configura la API key nelle impostazioni.']);
            return;
        }

        $jobModel = new Job();

        // Verifica nessun job attivo di tipo generate
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_GENERATE);
        if ($activeJob) {
            echo json_encode([
                'error' => true,
                'message' => 'Un job di generazione è già in esecuzione',
                'job_id' => (int) $activeJob['id'],
            ]);
            return;
        }

        // Conta URL pronte per generazione (pending + scraped)
        $readyCount = $this->url->countReadyForGeneration($id);

        if ($readyCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna URL pronta per la generazione']);
            return;
        }

        // Verifica crediti
        $estimatedCredits = $readyCount * self::GENERATE_CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $estimatedCredits)) {
            $balance = Credits::getBalance($user['id']);
            echo json_encode([
                'error' => true,
                'message' => "Crediti insufficienti. Richiesti: {$estimatedCredits}, disponibili: {$balance}",
            ]);
            return;
        }

        // Crea job
        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_GENERATE,
            'items_requested' => $readyCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $readyCount,
            'estimated_credits' => $estimatedCredits,
        ]);
    }

    /**
     * GET SSE - Stream generazione AI in tempo reale
     */
    public function generateStream(int $id): void
    {
        // Verifica auth senza redirect (SSE)
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || (int) $job['project_id'] !== $id) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        // Chiudi sessione PRIMA del loop SSE
        session_write_close();

        // CRITICO: continua esecuzione anche se proxy chiude connessione
        ignore_user_abort(true);
        set_time_limit(0);

        // Headers SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Avvia job se pending
        if ($job['status'] === Job::STATUS_PENDING) {
            $jobModel->start($jobId);
        }

        $total = (int) $job['items_requested'];
        $completed = 0;
        $failed = 0;
        $creditsUsed = 0;

        $this->sendEvent('started', [
            'job_id' => $jobId,
            'total' => $total,
        ]);

        $ai = new AiService('content-creator');
        $operationLog = new OperationLog();

        // Carica settings modulo per il prompt
        $settings = [
            'min_words_product' => (int) ModuleLoader::getSetting('content-creator', 'min_words_product', 300),
            'min_words_category' => (int) ModuleLoader::getSetting('content-creator', 'min_words_category', 400),
            'min_words_article' => (int) ModuleLoader::getSetting('content-creator', 'min_words_article', 800),
            'min_words_service' => (int) ModuleLoader::getSetting('content-creator', 'min_words_service', 500),
            'min_words_default' => (int) ModuleLoader::getSetting('content-creator', 'min_words_default', 300),
        ];

        while (true) {
            Database::reconnect();

            // Check cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            // Prossima URL pronta per generazione (pending o scraped)
            $items = $this->url->getNextForGeneration($id, 1);
            if (empty($items)) {
                // Nessuna URL rimasta - completa job
                Database::reconnect();
                $jobModel->complete($jobId);

                // Log operazione
                $operationLog->log([
                    'user_id' => $user['id'],
                    'project_id' => $id,
                    'operation' => 'ai_generate',
                    'credits_used' => $creditsUsed,
                    'status' => 'success',
                    'details' => [
                        'completed' => $completed,
                        'failed' => $failed,
                        'job_id' => $jobId,
                    ],
                ]);

                $this->sendEvent('completed', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            $item = $items[0];
            $percent = $total > 0 ? round((($completed + $failed) / $total) * 100) : 0;

            // Aggiorna progresso job
            $jobModel->updateProgress($jobId, [
                'current_item_id' => $item['id'],
                'current_item' => $item['url'],
            ]);

            $this->sendEvent('progress', [
                'current_url' => $item['url'],
                'keyword' => $item['keyword'] ?? '',
                'completed' => $completed,
                'failed' => $failed,
                'total' => $total,
                'percent' => $percent,
            ]);

            try {
                // Costruisci prompt
                $prompt = $this->buildPrompt($item, $project, $settings);

                // IMPORTANTE: reset time limit prima di ogni chiamata AI
                set_time_limit(0);

                // Chiamata AI
                $result = $ai->analyze($user['id'], $prompt, '', 'content-creator');

                Database::reconnect();

                if (isset($result['error'])) {
                    throw new \Exception($result['message'] ?? 'Errore AI sconosciuto');
                }

                $aiResponse = $result['result'] ?? '';

                // Parse risposta: estrai HTML tra ```html e ```
                $parsed = $this->parseResponse($aiResponse);

                if (!$parsed['success']) {
                    throw new \Exception($parsed['error']);
                }

                $htmlContent = $this->sanitizeHtml($parsed['content']);
                $h1 = $parsed['h1'];
                $wordCount = str_word_count(strip_tags($htmlContent));

                // Salva risultati nel DB PRIMA dell'evento completed
                $this->url->updateGeneratedData($item['id'], $htmlContent, $h1, $wordCount);

                // Consuma crediti per generazione
                Credits::consume($user['id'], self::GENERATE_CREDIT_COST, 'cc_generate', 'content-creator', [
                    'url' => $item['url'],
                    'keyword' => $item['keyword'] ?? '',
                    'word_count' => $wordCount,
                ]);

                $completed++;
                $creditsUsed += self::GENERATE_CREDIT_COST;

                $jobModel->incrementCompleted($jobId);
                $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);

                $this->sendEvent('item_completed', [
                    'url_id' => (int) $item['id'],
                    'url' => $item['url'],
                    'h1' => $h1,
                    'word_count' => $wordCount,
                    'preview' => mb_substr(strip_tags($htmlContent), 0, 150) . '...',
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round((($completed + $failed) / $total) * 100) : 0,
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                $this->url->markGenerationError($item['id'], $e->getMessage());

                $failed++;
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'url_id' => (int) $item['id'],
                    'url' => $item['url'],
                    'error' => $e->getMessage(),
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round((($completed + $failed) / $total) * 100) : 0,
                ]);
            }

            // Pausa tra items (500ms - contenuti più lunghi)
            usleep(500000);
        }

        exit;
    }

    /**
     * GET - Polling fallback per stato generate job
     */
    public function generateJobStatus(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || (int) $job['project_id'] !== $id) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
    }

    /**
     * POST - Annulla generate job
     */
    public function cancelGenerateJob(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            echo json_encode(['error' => true, 'message' => 'Non autenticato']);
            return;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || (int) $job['project_id'] !== $id) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $jobModel->cancel($jobId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Job annullato',
        ]);
    }

    // ─────────────────────────────────────────────
    //  RESULTS
    // ─────────────────────────────────────────────

    /**
     * GET - Pagina risultati con lista URL, filtri, paginazione
     */
    public function results(int $id): string
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $project = $this->getProject($id, $user['id']);
        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: /content-creator');
            exit;
        }

        // Filtri da GET
        $filters = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['q'] ?? null,
            'sort' => $_GET['sort'] ?? 'created_at',
            'dir' => $_GET['dir'] ?? 'desc',
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));

        // URL paginate con filtri
        $paginatedResult = $this->url->getPaginated($id, $page, 50, $filters);

        // Separa dati da paginazione
        $urls = $paginatedResult['data'];
        $pagination = [
            'total' => $paginatedResult['total'],
            'per_page' => $paginatedResult['per_page'],
            'current_page' => $paginatedResult['current_page'],
            'last_page' => $paginatedResult['last_page'],
            'from' => $paginatedResult['from'],
            'to' => $paginatedResult['to'],
        ];

        // Statistiche
        $stats = $this->url->getStats($id);

        return View::render('content-creator/results/index', [
            'user' => $user,
            'project' => $project,
            'urls' => $urls,
            'pagination' => $pagination,
            'stats' => $stats,
            'filters' => $filters,
            'currentPage' => 'results',
            'modules' => ModuleLoader::getActiveModules(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  PROMPT BUILDER
    // ─────────────────────────────────────────────

    /**
     * Costruisce il prompt AI per generazione contenuto HTML completo
     */
    private function buildPrompt(array $item, array $project, array $settings): string
    {
        $contentType = $project['content_type'] ?? 'product';
        $language = $project['language'] ?? 'it';
        $tone = $project['tone'] ?? 'professionale';

        // AI settings dal progetto (JSON)
        $aiSettings = [];
        if (!empty($project['ai_settings'])) {
            $aiSettings = json_decode($project['ai_settings'], true) ?: [];
        }

        // Min words per content type (priorità: progetto > modulo defaults)
        $minWordsKey = 'min_words_' . $contentType;
        $minWords = (int) ($aiSettings['min_words'] ?? $settings[$minWordsKey] ?? $settings['min_words_default']);

        // Keyword e secondary keywords
        $keyword = $item['keyword'] ?? $item['slug'] ?? '';
        $secondaryKw = '';
        if (!empty($item['secondary_keywords'])) {
            $skw = is_string($item['secondary_keywords'])
                ? json_decode($item['secondary_keywords'], true)
                : $item['secondary_keywords'];
            if (is_array($skw)) {
                $secondaryKw = implode(', ', array_slice($skw, 0, 10));
            }
        }

        // Search intent
        $intent = $item['intent'] ?? '';

        // Contesto da scraping (opzionale)
        $contextSection = '';
        if (!empty($item['scraped_content'])) {
            $contentExcerpt = mb_substr($item['scraped_content'], 0, 3000);
            $priceInfo = !empty($item['scraped_price']) ? "\n- Prezzo: " . $item['scraped_price'] : '';
            $contextSection = <<<CTX

CONTESTO PAGINA ESISTENTE (usa come riferimento, NON copiare):
- Titolo attuale: {$item['scraped_title']}
- H1 attuale: {$item['scraped_h1']}{$priceInfo}
- Contenuto attuale (estratto):
{$contentExcerpt}
CTX;
        }

        // Custom prompt
        $customPrompt = '';
        if (!empty($aiSettings['custom_prompt'])) {
            $customPrompt = "\nISTRUZIONI AGGIUNTIVE DELL'UTENTE:\n" . $aiSettings['custom_prompt'];
        }

        // Secondary keywords section
        $secondarySection = '';
        if (!empty($secondaryKw)) {
            $secondarySection = "\n- Keywords secondarie: {$secondaryKw}";
        }

        // Intent section
        $intentSection = '';
        if (!empty($intent)) {
            $intentSection = "\n- Search intent: {$intent}";
        }

        // Istruzioni specifiche per content type
        $typeInstructions = $this->getTypeInstructions($contentType);

        // Mappa content type per il prompt
        $typeLabels = [
            'product' => 'prodotto',
            'category' => 'categoria e-commerce',
            'article' => 'articolo/blog post',
            'service' => 'pagina servizio',
            'custom' => 'pagina generica',
        ];
        $typeLabel = $typeLabels[$contentType] ?? 'pagina';

        $prompt = <<<PROMPT
Sei un esperto SEO copywriter. Genera il CONTENUTO HTML COMPLETO per una pagina di tipo "{$typeLabel}".

DATI PAGINA:
- URL: {$item['url']}
- Slug: {$item['slug']}
- Keyword principale: {$keyword}{$secondarySection}{$intentSection}
- Categoria: {$item['category']}{$contextSection}

VINCOLI:
- Lingua: {$language}
- Tono: {$tone}
- Lunghezza minima: {$minWords} parole
{$customPrompt}

STRUTTURA RICHIESTA PER TIPO "{$typeLabel}":
{$typeInstructions}

ISTRUZIONI GENERALI:
- Genera SOLO il body content HTML (NO <html>, <head>, <body> wrapper)
- Usa tag semantici: h1, h2, h3, p, ul, ol, strong, em
- L'H1 deve contenere la keyword principale ed essere unico
- Includi la keyword principale nelle prime 100 parole
- Distribuisci le keywords secondarie naturalmente nel testo
- Scrivi per gli utenti, non per i motori di ricerca
- Paragrafi brevi (2-4 frasi), buona leggibilità
- NO contenuto inventato (prezzi, specifiche tecniche non fornite)
- NO link esterni o placeholder [link]

FORMATO OUTPUT RICHIESTO:

```html
<h1>Titolo H1 ottimizzato</h1>
<p>Contenuto della pagina...</p>
<h2>Sottosezione</h2>
<p>Altro contenuto...</p>
```
PROMPT;

        return $prompt;
    }

    /**
     * Istruzioni specifiche per tipo di contenuto
     */
    private function getTypeInstructions(string $contentType): string
    {
        switch ($contentType) {
            case 'product':
                return <<<INST
1. H1: Nome prodotto ottimizzato con keyword
2. Introduzione: 2-3 frasi che presentano il prodotto e il suo valore
3. H2 "Caratteristiche principali": lista puntata delle features
4. H2 "Vantaggi": perché scegliere questo prodotto
5. H2 "Specifiche tecniche": tabella o lista (se hai dati dal contesto)
6. H2 "Domande frequenti": 3-4 FAQ con risposte brevi
7. Paragrafo conclusivo con call-to-action
INST;

            case 'category':
                return <<<INST
1. H1: Nome categoria ottimizzato
2. Introduzione: descrizione della categoria e cosa troverà l'utente
3. H2 "Guida alla scelta": consigli per scegliere il prodotto giusto
4. H2 con sottocategorie o tipologie principali (H3 per ciascuna)
5. H2 "Domande frequenti": 3-4 FAQ sulla categoria
6. Paragrafo conclusivo che invita all'esplorazione
INST;

            case 'article':
                return <<<INST
1. H1: Titolo articolo accattivante con keyword
2. Introduzione: hook + anticipazione del contenuto + keyword nelle prime righe
3. 3-5 sezioni H2 con sviluppo approfondito del tema
4. Sottosezioni H3 dove necessario per approfondimenti
5. Liste puntate o numerate per informazioni strutturate
6. Conclusione con takeaway principali
INST;

            case 'service':
                return <<<INST
1. H1: Nome servizio ottimizzato con keyword
2. Introduzione: problema che il servizio risolve + proposta di valore
3. H2 "Come funziona": spiegazione del processo/servizio
4. H2 "Benefici": vantaggi concreti per il cliente
5. H2 "Per chi è adatto": target audience e casi d'uso
6. H2 "Domande frequenti": 3-4 FAQ sul servizio
7. Paragrafo conclusivo con call-to-action forte
INST;

            default: // custom
                return <<<INST
1. H1: Titolo ottimizzato con keyword principale
2. Introduzione chiara e coinvolgente
3. 2-4 sezioni H2 con contenuto pertinente
4. Liste puntate dove utile
5. Conclusione con call-to-action o sintesi
INST;
        }
    }

    // ─────────────────────────────────────────────
    //  RESPONSE PARSING
    // ─────────────────────────────────────────────

    /**
     * Parse risposta AI: estrae HTML e H1
     */
    private function parseResponse(string $response): array
    {
        $html = $this->extractBlock($response, self::HTML_START, self::HTML_END);

        if (empty($html)) {
            // Fallback: prova a usare tutta la risposta come HTML
            $cleaned = trim($response);
            if (preg_match('/<h[1-6]/', $cleaned)) {
                $html = $cleaned;
            } else {
                return ['success' => false, 'error' => 'Impossibile estrarre il contenuto HTML dalla risposta AI'];
            }
        }

        // Estrai H1 dal contenuto
        $h1 = '';
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
            $h1 = trim(strip_tags($matches[1]));
        }

        if (empty($h1)) {
            return ['success' => false, 'error' => 'Contenuto HTML senza tag H1'];
        }

        return [
            'success' => true,
            'content' => trim($html),
            'h1' => $h1,
        ];
    }

    /**
     * Estrae blocco di testo tra marker ```type e ```
     */
    private function extractBlock(string $response, string $startMarker, string $endMarker): string
    {
        $startPos = strpos($response, $startMarker);
        if ($startPos === false) {
            return '';
        }

        $startPos += strlen($startMarker);
        while ($startPos < strlen($response) && ($response[$startPos] === "\n" || $response[$startPos] === "\r")) {
            $startPos++;
        }

        $endPos = strpos($response, $endMarker, $startPos);
        if ($endPos === false) {
            return '';
        }

        while ($endPos > $startPos && ($response[$endPos - 1] === "\n" || $response[$endPos - 1] === "\r")) {
            $endPos--;
        }

        return substr($response, $startPos, $endPos - $startPos);
    }

    /**
     * Sanitizza contenuto HTML generato
     */
    private function sanitizeHtml(string $html): string
    {
        // Rimuovi tag pericolosi
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);

        // Rimuovi event handlers on*
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Pulisci whitespace tra tag
        $html = preg_replace('/>\s+</', ">\n<", $html);

        return trim($html);
    }
}
