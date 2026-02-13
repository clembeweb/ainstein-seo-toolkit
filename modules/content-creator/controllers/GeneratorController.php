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
    private const GENERATE_CREDIT_COST = 2;

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
        $project = $this->project->findByUser($id, $userId);
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

        // Conta URL scrappate (pronte per generazione)
        $scrapedCount = $this->url->countByProject($id, 'scraped');

        if ($scrapedCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna URL scrappata pronta per la generazione']);
            return;
        }

        // Verifica crediti
        $estimatedCredits = $scrapedCount * self::GENERATE_CREDIT_COST;
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
            'items_requested' => $scrapedCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $scrapedCount,
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
            'meta_title_min' => (int) ModuleLoader::getSetting('content-creator', 'meta_title_min', 30),
            'meta_title_max' => (int) ModuleLoader::getSetting('content-creator', 'meta_title_max', 60),
            'meta_desc_min' => (int) ModuleLoader::getSetting('content-creator', 'meta_desc_min', 120),
            'meta_desc_max' => (int) ModuleLoader::getSetting('content-creator', 'meta_desc_max', 160),
            'page_desc_min' => (int) ModuleLoader::getSetting('content-creator', 'page_desc_min', 300),
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

            // Prossima URL scrappata
            $items = $this->url->getNextScraped($id, 1);
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

                // Chiamata AI (skip_credits gestito manualmente)
                $result = $ai->analyze($user['id'], $prompt, '', 'content-creator');

                Database::reconnect();

                if (isset($result['error'])) {
                    throw new \Exception($result['message'] ?? 'Errore AI sconosciuto');
                }

                $aiContent = $result['result'] ?? '';

                // Pulizia markdown code fences
                $aiContent = preg_replace('/^```(?:json)?\s*/i', '', $aiContent);
                $aiContent = preg_replace('/\s*```$/i', '', $aiContent);
                $aiContent = trim($aiContent);

                // Parse JSON
                $parsed = json_decode($aiContent, true);

                if (!$parsed || !isset($parsed['meta_title'])) {
                    throw new \Exception('Risposta AI non valida: formato JSON non riconosciuto');
                }

                $metaTitle = trim($parsed['meta_title'] ?? '');
                $metaDescription = trim($parsed['meta_description'] ?? '');
                $pageDescription = trim($parsed['page_description'] ?? '');

                // Salva risultati nel DB PRIMA dell'evento completed
                $this->url->updateGeneratedData($item['id'], $metaTitle, $metaDescription, $pageDescription);

                // Consuma crediti per generazione
                Credits::consume($user['id'], self::GENERATE_CREDIT_COST, 'cc_generate', 'content-creator', [
                    'url' => $item['url'],
                    'meta_title_len' => mb_strlen($metaTitle),
                    'meta_desc_len' => mb_strlen($metaDescription),
                    'page_desc_len' => mb_strlen($pageDescription),
                ]);

                $completed++;
                $creditsUsed += self::GENERATE_CREDIT_COST;

                $jobModel->incrementCompleted($jobId);
                $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);

                $this->sendEvent('item_completed', [
                    'url_id' => (int) $item['id'],
                    'url' => $item['url'],
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                    'page_description' => mb_substr($pageDescription, 0, 200) . (mb_strlen($pageDescription) > 200 ? '...' : ''),
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

            // Pausa tra items (300ms)
            usleep(300000);
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
        $urls = $this->url->getPaginated($id, $page, 50, $filters);

        // Statistiche
        $stats = $this->url->getStats($id);

        return View::render('content-creator/results/index', [
            'user' => $user,
            'project' => $project,
            'urls' => $urls,
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
     * Costruisce il prompt AI per la generazione contenuti
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

        // Limiti lunghezza (priorita: progetto > modulo defaults)
        $metaTitleMin = (int) ($aiSettings['meta_title_min'] ?? $settings['meta_title_min']);
        $metaTitleMax = (int) ($aiSettings['meta_title_max'] ?? $settings['meta_title_max']);
        $metaDescMin = (int) ($aiSettings['meta_desc_min'] ?? $settings['meta_desc_min']);
        $metaDescMax = (int) ($aiSettings['meta_desc_max'] ?? $settings['meta_desc_max']);
        $pageDescMin = (int) ($aiSettings['page_desc_min'] ?? $settings['page_desc_min']);

        // Estratto contenuto (max 3000 chars per il prompt)
        $contentExcerpt = mb_substr($item['scraped_content'] ?? '', 0, 3000);

        // Prezzo
        $priceInfo = !empty($item['scraped_price']) ? $item['scraped_price'] : 'Non disponibile';

        // Custom prompt
        $customPrompt = '';
        if (!empty($aiSettings['custom_prompt'])) {
            $customPrompt = "\nISTRUZIONI AGGIUNTIVE:\n" . $aiSettings['custom_prompt'];
        }

        // Mappa content type per il prompt
        $typeLabels = [
            'product' => 'prodotto',
            'category' => 'categoria',
            'article' => 'articolo/blog post',
            'service' => 'servizio',
            'custom' => 'pagina',
        ];
        $typeLabel = $typeLabels[$contentType] ?? 'pagina';

        $prompt = <<<PROMPT
Sei un esperto SEO e copywriter. Genera contenuti ottimizzati per la seguente pagina di tipo "{$typeLabel}".

DATI PAGINA:
- URL: {$item['url']}
- Titolo attuale: {$item['scraped_title']}
- H1: {$item['scraped_h1']}
- Meta Title attuale: {$item['scraped_meta_title']}
- Meta Description attuale: {$item['scraped_meta_description']}
- Prezzo: {$priceInfo}
- Contenuto (estratto):
{$contentExcerpt}

VINCOLI:
- Lingua: {$language}
- Tono: {$tone}
- Meta Title: {$metaTitleMin}-{$metaTitleMax} caratteri
- Meta Description: {$metaDescMin}-{$metaDescMax} caratteri
- Page Description: minimo {$pageDescMin} caratteri
{$customPrompt}

ISTRUZIONI:
- Ottimizza per SEO mantenendo naturalezza e leggibilita
- Il meta title deve includere la keyword principale e essere accattivante
- La meta description deve invogliare al click e contenere una call-to-action
- La page description deve essere un testo descrittivo completo, ottimizzato SEO, con paragrafi ben strutturati

Rispondi SOLO con un JSON valido (senza markdown, senza commenti):
{"meta_title": "...", "meta_description": "...", "page_description": "..."}
PROMPT;

        return $prompt;
    }
}
