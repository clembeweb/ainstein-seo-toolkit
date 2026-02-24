<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\Database;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\CrawlSession;
use Modules\SeoAudit\Models\CrawlJob;
use Modules\SeoAudit\Services\CrawlerService;
use Modules\SeoAudit\Services\IssueDetector;
use Core\Logger;

/**
 * CrawlController
 *
 * Gestisce avvio, monitoraggio e stop del crawl
 * Usa CrawlSession per tracciare stato persistente
 */
class CrawlController
{
    private Project $projectModel;
    private Page $pageModel;
    private Issue $issueModel;
    private CrawlSession $sessionModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
        $this->sessionModel = new CrawlSession();
    }

    /**
     * Avvia crawl (risposta JSON per progress)
     */
    public function start(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
            return;
        }

        // Verifica che non ci sia già una sessione attiva
        $activeSession = $this->sessionModel->findActiveByProject($id);
        if ($activeSession) {
            // Auto-clean: se la sessione è running da >30 min senza job attivo, è orfana
            $jobModel = new CrawlJob();
            $activeJob = $jobModel->findActiveByProject($id);
            $sessionAge = $activeSession['started_at']
                ? (time() - strtotime($activeSession['started_at']))
                : 0;

            if (!$activeJob && $sessionAge > 1800) {
                // Sessione orfana — reset automatico
                $this->sessionModel->fail($activeSession['id'], 'Timeout - sessione orfana resettata automaticamente');
                $this->projectModel->update($id, ['status' => 'idle', 'current_session_id' => null]);
                // Continua con la nuova scansione
            } else {
                jsonResponse([
                    'error' => true,
                    'message' => 'Crawl già in corso',
                    'session_id' => $activeSession['id'],
                ]);
                return;
            }
        }

        // Leggi configurazione da POST o usa defaults
        // crawl_mode forzato a 'spider' - modalità sitemap rimossa
        $config = [
            'max_pages' => min((int) ($_POST['max_pages'] ?? $project['max_pages'] ?? 500), 2000),
            'crawl_mode' => 'spider', // Solo spider mode supportato
            'respect_robots' => isset($_POST['respect_robots']) ? 1 : (int) ($_POST['respect_robots'] ?? 1),
            // Configurazione avanzata spider
            'max_depth' => max(1, min((int) ($_POST['max_depth'] ?? 3), 10)),
            'request_delay' => max(0, min((int) ($_POST['request_delay'] ?? 200), 5000)),
            'timeout' => max(5, min((int) ($_POST['timeout'] ?? 20), 60)),
            'max_retries' => max(0, min((int) ($_POST['max_retries'] ?? 2), 5)),
            'user_agent' => $_POST['user_agent'] ?? 'googlebot',
            'follow_redirects' => isset($_POST['follow_redirects']) ? 1 : 1,
        ];

        // Verifica crediti
        $crawlCost = Credits::getCost('crawl_per_page') ?? 0.2;
        $estimatedCost = $config['max_pages'] * $crawlCost;

        if (!Credits::hasEnough($user['id'], $estimatedCost * 0.1)) {
            jsonResponse(['error' => true, 'message' => 'Crediti insufficienti']);
            return;
        }

        try {
            // Pulisci dati crawl precedente per fresh start
            Database::delete('sa_pages', 'project_id = ?', [$id]);
            Database::delete('sa_issues', 'project_id = ?', [$id]);

            // Crea sessione
            $sessionId = $this->sessionModel->create($id, $config);

            // Salva configurazione nel progetto - reset counters!
            // IMPORTANTE: Aggiorna anche max_pages per CrawlerService::discoverUrls()
            $this->projectModel->update($id, [
                'current_session_id' => $sessionId,
                'crawl_config' => json_encode($config),
                'max_pages' => $config['max_pages'], // FIX: Aggiorna limite pagine per crawler
                'status' => 'crawling',
                'pages_crawled' => 0,
                'pages_found' => 0, // Reset anche questo!
            ]);

            // Avvia sessione
            $this->sessionModel->start($sessionId);

            $crawler = new CrawlerService();
            $crawler->init($id, $user['id']);
            $crawler->setSessionId($sessionId);
            $crawler->setConfig($config); // Passa configurazione spider

            // Fase 1: Discovery URL
            $urls = $crawler->discoverUrls();

            if (empty($urls)) {
                $this->sessionModel->fail($sessionId, 'Nessun URL trovato nel sito');
                $this->projectModel->update($id, ['status' => 'failed']);
                jsonResponse(['error' => true, 'message' => 'Nessun URL trovato nel sito']);
                return;
            }

            // Aggiorna sessione con pagine trovate
            $this->sessionModel->setPagesFound($sessionId, count($urls));

            // Inserisci URL scoperti in sa_pages come pending (per processStream SSE)
            // Necessario perché discoverUrls() salva in sa_site_config ma processStream
            // legge da sa_pages filtrato per session_id
            $pendingCount = 0;
            foreach ($urls as $url) {
                $exists = Database::fetch(
                    "SELECT id FROM sa_pages WHERE project_id = ? AND url = ?",
                    [$id, $url]
                );
                if (!$exists) {
                    Database::insert('sa_pages', [
                        'project_id' => $id,
                        'session_id' => $sessionId,
                        'url' => $url,
                        'status' => 'pending',
                    ]);
                    $pendingCount++;
                } else {
                    // Pagina esiste già, aggiorna session_id e status
                    Database::execute(
                        "UPDATE sa_pages SET session_id = ?, status = 'pending' WHERE id = ?",
                        [$sessionId, $exists['id']]
                    );
                    $pendingCount++;
                }
            }

            // Crea background job per SSE processing
            $jobModel = new CrawlJob();
            $jobId = $jobModel->create([
                'project_id' => $id,
                'session_id' => $sessionId,
                'user_id' => $user['id'],
                'type' => 'crawl',
                'items_total' => (int) $pendingCount,
                'config' => json_encode($config),
            ]);

            // Log activity
            $this->projectModel->logActivity($id, $user['id'], 'crawl_started', [
                'session_id' => $sessionId,
                'job_id' => $jobId,
                'urls_found' => count($urls),
                'crawl_mode' => $config['crawl_mode'],
            ]);

            jsonResponse([
                'success' => true,
                'session_id' => $sessionId,
                'job_id' => $jobId,
                'phase' => 'discovery',
                'urls_found' => count($urls),
                'config' => $config,
                'message' => 'Trovati ' . count($urls) . ' URL da scansionare',
            ]);

        } catch (\Exception $e) {
            Logger::channel('scraping')->error("CRAWL ERROR", ['error' => $e->getMessage()]);

            if (isset($sessionId)) {
                $this->sessionModel->fail($sessionId, $e->getMessage());
            }
            $this->projectModel->update($id, ['status' => 'failed']);
            jsonResponse(['error' => true, 'message' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Crawl batch di pagine (chiamato via polling AJAX)
     *
     * @deprecated Usare processStream() con SSE. Mantenuto per backward compatibility.
     */
    public function crawlBatch(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato']);
        }

        // Se crawl già completato, segnala al JS di ricaricare
        if ($project['status'] === 'completed') {
            jsonResponse(['success' => true, 'complete' => true, 'message' => 'Scansione completata']);
        }

        if (!in_array($project['status'], ['crawling', 'stopping'])) {
            jsonResponse(['error' => true, 'message' => 'Crawl non attivo']);
        }

        // Trova sessione attiva
        $activeSession = $this->sessionModel->findActiveByProject($id);
        if (!$activeSession) {
            jsonResponse(['error' => true, 'message' => 'Nessuna sessione attiva']);
        }

        // Verifica se richiesto stop
        if ($activeSession['status'] === CrawlSession::STATUS_STOPPING) {
            // Finalizza e ferma
            $this->finalizeCrawl($id, $user['id'], true);
            $this->sessionModel->stop($activeSession['id']);
            $this->projectModel->update($id, ['status' => 'stopped']);

            jsonResponse([
                'success' => true,
                'stopped' => true,
                'message' => 'Crawl interrotto',
            ]);
        }

        // Batch molto piccolo (2) per aggiornamenti frequenti della progress bar
        $batchSize = min((int) ($_POST['batch_size'] ?? 2), 5);

        try {
            // Carica configurazione salvata nel progetto
            $savedConfig = $project['crawl_config'] ? json_decode($project['crawl_config'], true) : [];

            $crawler = new CrawlerService();
            $crawler->init($id, $user['id']);
            $crawler->setSessionId($activeSession['id']);
            $crawler->setConfig($savedConfig); // Usa configurazione salvata

            $issueDetector = new IssueDetector();
            $issueDetector->init($id);
            $issueDetector->setSessionId($activeSession['id']);

            // Riconnetti DB per sicurezza
            Database::reconnect();

            // Ottieni URL da processare
            $progress = $this->projectModel->getCrawlProgress($id);
            $pagesCrawled = $progress['pages_crawled'];
            $pagesFound = $progress['pages_found'];

            if ($pagesCrawled >= $pagesFound) {
                // Completa
                $this->finalizeCrawl($id, $user['id']);
                $this->sessionModel->complete($activeSession['id']);

                jsonResponse([
                    'success' => true,
                    'complete' => true,
                    'message' => 'Scansione completata',
                ]);
            }

            // Fetch URL scoperti
            $allUrls = $crawler->getDiscoveredUrls();

            if (empty($allUrls)) {
                // Fallback: nessun URL trovato - termina
                $this->finalizeCrawl($id, $user['id']);
                $this->sessionModel->complete($activeSession['id']);
                jsonResponse([
                    'success' => true,
                    'complete' => true,
                    'message' => 'Nessun URL da scansionare',
                ]);
            }

            // Prendi batch di pagine pending (nuovo sistema con status)
            $pendingPages = Database::fetchAll(
                "SELECT url FROM sa_pages WHERE project_id = ? AND status = 'pending' LIMIT ?",
                [$id, $batchSize]
            );

            $toCrawl = [];
            if (!empty($pendingPages)) {
                // Usa pagine pending dal nuovo sistema
                $toCrawl = array_column($pendingPages, 'url');
            } else {
                // Fallback: vecchio sistema - URL non in sa_pages
                $crawledUrls = Database::fetchAll(
                    "SELECT url FROM sa_pages WHERE project_id = ?",
                    [$id]
                );
                $crawledSet = array_flip(array_column($crawledUrls, 'url'));

                foreach ($allUrls as $url) {
                    if (!isset($crawledSet[$url])) {
                        $toCrawl[] = $url;
                    }
                    if (count($toCrawl) >= $batchSize) break;
                }
            }

            $crawled = 0;
            $totalIssuesFound = 0;
            $stoppedEarly = false;

            foreach ($toCrawl as $url) {
                // Controlla stop prima di ogni pagina
                if ($crawler->shouldStop()) {
                    $stoppedEarly = true;
                    break;
                }

                // Aggiorna URL corrente nella sessione PRIMA del crawl (così la UI vede l'URL in elaborazione)
                $this->sessionModel->updateProgress($activeSession['id'], $pagesCrawled + $crawled, $url);

                // Flush output per assicurare che il DB sia aggiornato
                Database::reconnect();

                // Crawl pagina
                $pageData = $crawler->crawlPage($url);

                if ($pageData && empty($pageData['error'])) {
                    // Salva pagina
                    $pageId = $crawler->savePage($pageData);

                    // Rileva issues
                    $issuesFound = $issueDetector->analyzeAndSave($pageData, $pageId);
                    $totalIssuesFound += $issuesFound;

                    $crawled++;

                    // Aggiorna conteggio progetto
                    $this->projectModel->update($id, [
                        'pages_crawled' => $pagesCrawled + $crawled,
                    ]);

                    // Aggiorna sessione
                    $this->sessionModel->updateProgress(
                        $activeSession['id'],
                        $pagesCrawled + $crawled,
                        $url,
                        $activeSession['issues_found'] + $totalIssuesFound
                    );
                }
            }

            // Se fermato prematuramente
            if ($stoppedEarly) {
                $this->finalizeCrawl($id, $user['id'], true);
                $this->sessionModel->stop($activeSession['id']);
                $this->projectModel->update($id, ['status' => 'stopped']);

                jsonResponse([
                    'success' => true,
                    'stopped' => true,
                    'crawled' => $crawled,
                    'message' => 'Crawl interrotto',
                ]);
            }

            // Riconnetti DB dopo crawl batch (potrebbe essere scaduta la connessione)
            Database::reconnect();

            // Aggiorna stats
            $this->projectModel->updateStats($id);

            $newProgress = $this->projectModel->getCrawlProgress($id);
            $isComplete = $newProgress['pages_crawled'] >= $newProgress['pages_found'];

            if ($isComplete) {
                $this->finalizeCrawl($id, $user['id']);
                $this->sessionModel->complete($activeSession['id']);
            }

            // Get session stats for complete progress info
            $sessionStats = $this->sessionModel->getStats($activeSession['id']);

            jsonResponse([
                'success' => true,
                'crawled' => $crawled,
                'issues_found' => $totalIssuesFound,
                'progress' => [
                    'pages_found' => $newProgress['pages_found'],
                    'pages_crawled' => $newProgress['pages_crawled'],
                    'percent' => $newProgress['progress'],
                    'current_url' => $sessionStats['current_url'] ?? null,
                    'elapsed_seconds' => $sessionStats['elapsed_seconds'] ?? 0,
                ],
                'issues' => $this->issueModel->countBySeverity($id),
                'complete' => $isComplete,
            ]);

        } catch (\Exception $e) {
            $this->sessionModel->fail($activeSession['id'], $e->getMessage());
            jsonResponse(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Stato crawl (polling)
     */
    public function status(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
        }

        // Ottieni sessione attiva o ultima
        $session = $this->sessionModel->findActiveByProject($id)
            ?? $this->sessionModel->findLatestByProject($id);

        $sessionStats = $session ? $this->sessionModel->getStats($session['id']) : null;
        $issueStats = $this->issueModel->countBySeverity($id);

        // Cerca job attivo per SSE
        $jobModel = new CrawlJob();
        $activeJob = $jobModel->findActiveByProject($id);

        // Self-heal: se progetto è in crawling ma nessun job attivo e sessione >30 min, reset
        $projectStatus = $project['status'];
        if (in_array($projectStatus, ['crawling', 'stopping']) && !$activeJob && $session) {
            $sessionAge = $session['started_at']
                ? (time() - strtotime($session['started_at']))
                : 0;

            if ($sessionAge > 1800) {
                $this->sessionModel->fail($session['id'], 'Timeout - auto-recovery durante status check');
                $this->projectModel->update($id, ['status' => 'stopped', 'current_session_id' => null]);
                $projectStatus = 'stopped';

                // Refresh session stats after reset
                $session = $this->sessionModel->findLatestByProject($id);
                $sessionStats = $session ? $this->sessionModel->getStats($session['id']) : null;
            }
        }

        // Riconnetti DB per operazioni lunghe
        Database::reconnect();

        jsonResponse([
            'status' => $projectStatus,
            'session' => $sessionStats,
            'issues' => $issueStats,
            'health_score' => $project['health_score'],
            'active_job_id' => $activeJob ? (int) $activeJob['id'] : null,
            'progress' => $sessionStats ? [
                'pages_found' => $sessionStats['pages_found'],
                'pages_crawled' => $sessionStats['pages_crawled'],
                'percent' => $sessionStats['progress_percent'],
                'current_url' => $sessionStats['current_url'],
                'elapsed_seconds' => $sessionStats['elapsed_seconds'],
            ] : null,
        ]);
    }

    /**
     * Stop crawl
     */
    public function stop(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
        }

        // Trova sessione attiva
        $activeSession = $this->sessionModel->findActiveByProject($id);

        if (!$activeSession) {
            // Nessuna sessione attiva, ma forza reset progetto se in stato crawling/stopping
            if (in_array($project['status'], ['crawling', 'stopping'])) {
                $this->projectModel->update($id, ['status' => 'idle', 'current_session_id' => null]);
                jsonResponse([
                    'success' => true,
                    'stopped' => true,
                    'message' => 'Progetto resettato (nessuna sessione attiva trovata)'
                ]);
            }
            jsonResponse(['error' => true, 'message' => 'Nessun crawl in corso']);
        }

        try {
            // Imposta flag "stopping" - il crawler lo leggerà e si fermerà
            $this->sessionModel->requestStop($activeSession['id']);

            // Aggiorna stato progetto
            $this->projectModel->update($id, ['status' => 'stopping']);

            $this->projectModel->logActivity($id, $user['id'], 'crawl_stop_requested', [
                'session_id' => $activeSession['id'],
                'pages_crawled' => $activeSession['pages_crawled'],
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Stop richiesto. Il crawl si fermerà al termine della pagina corrente.',
                'session_id' => $activeSession['id'],
            ]);

        } catch (\Exception $e) {
            jsonResponse(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Conferma stop e finalizza (chiamato dopo che crawler si è fermato)
     */
    public function confirmStop(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
        }

        $activeSession = $this->sessionModel->findActiveByProject($id);

        if ($activeSession && $activeSession['status'] === CrawlSession::STATUS_STOPPING) {
            // Finalizza
            $this->finalizeCrawl($id, $user['id'], true);
            $this->sessionModel->stop($activeSession['id']);

            $this->projectModel->logActivity($id, $user['id'], 'crawl_stopped', [
                'session_id' => $activeSession['id'],
                'pages_crawled' => $activeSession['pages_crawled'],
            ]);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Crawl interrotto',
        ]);
    }

    /**
     * SSE stream per il crawl in background
     * GET /seo-audit/project/{id}/crawl/stream?job_id=X
     *
     * CRITICAL: ignore_user_abort(true) garantisce che il crawl continui
     * anche se l'utente naviga via dalla pagina.
     */
    public function processStream(int $id): void
    {
        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // CRITICAL: Continua esecuzione anche se proxy chiude connessione
        ignore_user_abort(true);
        set_time_limit(0);

        // CRITICAL: Chiudi sessione prima del loop (non blocca altre request)
        session_write_close();

        $jobId = (int) ($_GET['job_id'] ?? 0);

        $jobModel = new CrawlJob();
        $job = $jobModel->find($jobId);

        if (!$job || (int) $job['project_id'] !== $id) {
            $this->sendEvent('error', ['message' => 'Job non trovato']);
            exit;
        }

        // Avvia il job
        $jobModel->start($jobId);

        $crawlerService = new CrawlerService();
        $crawlerService->init($id, (int) $job['user_id']);
        $crawlerService->setSessionId((int) $job['session_id']);

        // Inizializza configurazione dal job
        $config = json_decode($job['config'] ?? '{}', true) ?: [];
        if (!empty($config)) {
            $crawlerService->setConfig($config);
        }

        $issueDetector = new IssueDetector();
        $issueDetector->init($id);
        $issueDetector->setSessionId((int) $job['session_id']);

        $this->sendEvent('started', [
            'total' => (int) $job['items_total'],
            'job_id' => $jobId,
        ]);

        $completed = 0;
        $totalIssuesFound = 0;

        while (true) {
            Database::reconnect();

            // Verifica cancellazione dal DB (fresco)
            if ($jobModel->isCancelled($jobId)) {
                $this->sessionModel->requestStop((int) $job['session_id']);
                $this->finalizeCrawl($id, (int) $job['user_id'], true);
                $this->sessionModel->stop((int) $job['session_id']);
                $this->projectModel->update($id, ['status' => 'stopped']);

                $this->sendEvent('cancelled', $jobModel->getJobResponse($jobId));
                break;
            }

            // Prossima pagina pending
            $pendingPage = Database::fetch(
                "SELECT id, url FROM sa_pages WHERE project_id = ? AND session_id = ? AND status = 'pending' LIMIT 1",
                [$id, (int) $job['session_id']]
            );

            if (!$pendingPage) {
                // Tutte le pagine processate - finalizza
                // CRITICO: Salvare nel DB PRIMA dell'evento completed (polling fallback)
                Database::reconnect();
                $this->finalizeCrawl($id, (int) $job['user_id']);
                $jobModel->complete($jobId);
                $this->sessionModel->complete((int) $job['session_id']);

                $this->sendEvent('completed', $jobModel->getJobResponse($jobId));
                break;
            }

            try {
                // Segna pagina come in scraping
                Database::execute(
                    "UPDATE sa_pages SET status = 'scraping' WHERE id = ?",
                    [$pendingPage['id']]
                );

                // Crawl la pagina
                $pageData = $crawlerService->crawlPage($pendingPage['url']);
                Database::reconnect();

                if ($pageData && empty($pageData['error'])) {
                    // Salva dati pagina
                    $pageId = $crawlerService->savePage($pageData);

                    // Rileva issues
                    $issueCount = $issueDetector->analyzeAndSave($pageData, $pageId);
                    Database::reconnect();

                    $completed++;
                    $totalIssuesFound += $issueCount;
                    $jobModel->incrementCompleted($jobId);
                    $jobModel->updateProgress($jobId, $completed, $pendingPage['url']);

                    // Aggiorna conteggio progetto
                    $this->projectModel->update($id, [
                        'pages_crawled' => $completed,
                    ]);

                    // Aggiorna sessione
                    $this->sessionModel->updateProgress(
                        (int) $job['session_id'],
                        $completed,
                        $pendingPage['url'],
                        $totalIssuesFound
                    );

                    $this->sendEvent('page_completed', [
                        'url' => $pendingPage['url'],
                        'completed' => $completed,
                        'total' => (int) $job['items_total'],
                        'issues' => $issueCount,
                        'percent' => round(($completed / max((int) $job['items_total'], 1)) * 100, 1),
                    ]);
                } else {
                    // Pagina con errore durante lo scraping
                    Database::execute(
                        "UPDATE sa_pages SET status = 'error' WHERE id = ?",
                        [$pendingPage['id']]
                    );
                    $jobModel->incrementFailed($jobId);

                    $this->sendEvent('page_error', [
                        'url' => $pendingPage['url'],
                        'error' => $pageData['error'] ?? 'Errore durante lo scraping',
                    ]);
                }
            } catch (\Exception $e) {
                Database::reconnect();
                Database::execute(
                    "UPDATE sa_pages SET status = 'error' WHERE id = ?",
                    [$pendingPage['id']]
                );
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('page_error', [
                    'url' => $pendingPage['url'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Rate limiting (dal config)
            $delay = $config['request_delay'] ?? 200;
            usleep($delay * 1000);
        }

        exit;
    }

    /**
     * Polling fallback per stato job
     * GET /seo-audit/project/{id}/crawl/job-status?job_id=X
     */
    public function jobStatus(int $id): void
    {
        header('Content-Type: application/json');

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new CrawlJob();

        // Usa find() per verificare project_id (getJobResponse non lo include)
        $rawJob = $jobModel->find($jobId);
        if (!$rawJob || (int) $rawJob['project_id'] !== $id) {
            echo json_encode(['success' => false, 'message' => 'Job non trovato']);
            exit;
        }

        $jobResponse = $jobModel->getJobResponse($jobId);

        // Aggiungi info sessione per progress dettagliato
        $sessionStats = null;
        if (!empty($rawJob['session_id'])) {
            $sessionStats = $this->sessionModel->getStats((int) $rawJob['session_id']);
        }

        echo json_encode([
            'success' => true,
            'job' => $jobResponse,
            'session' => $sessionStats,
            'issues' => $this->issueModel->countBySeverity($id),
        ]);
        exit;
    }

    /**
     * Annulla un job di crawl
     * POST /seo-audit/project/{id}/crawl/cancel-job
     */
    public function cancelJob(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        header('Content-Type: application/json');

        $jobId = (int) ($_POST['job_id'] ?? 0);
        $jobModel = new CrawlJob();

        // Leggi job PRIMA di cancellarlo per ottenere session_id
        $job = $jobModel->find($jobId);
        if ($job && !empty($job['session_id'])) {
            $this->sessionModel->requestStop((int) $job['session_id']);
        }

        // Cancella il job (il processStream lo rileverà via isCancelled)
        $jobModel->cancel($jobId);
        $this->projectModel->update($id, ['status' => 'stopping']);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Finalizza crawl: check a livello progetto e calcolo score
     */
    private function finalizeCrawl(int $projectId, int $userId, bool $stopped = false): void
    {
        $issueDetector = new IssueDetector();
        $issueDetector->init($projectId);

        // Check a livello progetto
        $issueDetector->runProjectLevelChecks();

        // Aggiorna stats e health score
        $this->projectModel->updateStats($projectId);

        // Ottieni stats per il messaggio
        $project = $this->projectModel->find($projectId);
        $pagesCount = $project['pages_crawled'] ?? 0;
        $issuesCount = $project['issues_count'] ?? 0;

        // Aggiorna stato
        $this->projectModel->update($projectId, [
            'status' => $stopped ? 'stopped' : 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Messaggio flash per feedback utente
        $message = $stopped
            ? "Scansione interrotta. Analizzate {$pagesCount} pagine, trovati {$issuesCount} problemi."
            : "Scansione completata! Analizzate {$pagesCount} pagine, trovati {$issuesCount} problemi.";
        $_SESSION['_flash']['success'] = $message;

        $this->projectModel->logActivity($projectId, $userId, $stopped ? 'crawl_stopped' : 'crawl_completed');
    }

    /**
     * Helper SSE: invia evento al client
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }
}
