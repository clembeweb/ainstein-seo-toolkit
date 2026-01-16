<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Credits;
use Core\Database;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\CrawlSession;
use Modules\SeoAudit\Services\CrawlerService;
use Modules\SeoAudit\Services\IssueDetector;

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
            jsonResponse([
                'error' => true,
                'message' => 'Crawl già in corso',
                'session_id' => $activeSession['id'],
            ]);
            return;
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

            // Log activity
            $this->projectModel->logActivity($id, $user['id'], 'crawl_started', [
                'session_id' => $sessionId,
                'urls_found' => count($urls),
                'crawl_mode' => $config['crawl_mode'],
            ]);

            jsonResponse([
                'success' => true,
                'session_id' => $sessionId,
                'phase' => 'discovery',
                'urls_found' => count($urls),
                'config' => $config,
                'message' => 'Trovati ' . count($urls) . ' URL da scansionare',
            ]);

        } catch (\Exception $e) {
            error_log("CRAWL ERROR: " . $e->getMessage());

            if (isset($sessionId)) {
                $this->sessionModel->fail($sessionId, $e->getMessage());
            }
            $this->projectModel->update($id, ['status' => 'failed']);
            jsonResponse(['error' => true, 'message' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Crawl batch di pagine (chiamato via polling AJAX)
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

        // Riconnetti DB per operazioni lunghe
        Database::reconnect();

        jsonResponse([
            'status' => $project['status'],
            'session' => $sessionStats,
            'issues' => $issueStats,
            'health_score' => $project['health_score'],
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
}
