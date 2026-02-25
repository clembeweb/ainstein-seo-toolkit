<?php

namespace Modules\CrawlBudget\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\Database;
use Modules\CrawlBudget\Models\Project;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Models\Issue;
use Modules\CrawlBudget\Models\CrawlSession;
use Modules\CrawlBudget\Models\CrawlJob;
use Modules\CrawlBudget\Services\BudgetCrawlerService;
use Modules\CrawlBudget\Services\BudgetAnalyzerService;
use Core\ModuleLoader;

/**
 * CrawlController
 *
 * Gestisce avvio, SSE stream, polling e cancellazione del crawl.
 * Pattern copiato da seo-audit/CrawlController, adattato per crawl-budget.
 */
class CrawlController
{
    private Project $projectModel;
    private Page $pageModel;
    private Issue $issueModel;
    private CrawlSession $sessionModel;
    private CrawlJob $jobModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
        $this->sessionModel = new CrawlSession();
        $this->jobModel = new CrawlJob();
    }

    /**
     * POST /crawl-budget/projects/{id}/crawl/start
     *
     * Avvia crawl: crea sessione, fetch robots+sitemap, seed URL, crea job.
     */
    public function start(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
            return;
        }

        // Verifica no sessione attiva
        $activeSession = $this->sessionModel->findActiveByProject($id);
        if ($activeSession) {
            $activeJob = $this->jobModel->findActiveByProject($id);
            $sessionAge = $activeSession['started_at']
                ? (time() - strtotime($activeSession['started_at']))
                : 0;

            if (!$activeJob && $sessionAge > 1800) {
                // Sessione orfana > 30 min — auto-reset
                $this->sessionModel->fail($activeSession['id'], 'Timeout - sessione orfana resettata');
                $this->projectModel->update($id, ['status' => 'pending', 'current_session_id' => null]);
            } else {
                jsonResponse([
                    'error' => true,
                    'message' => 'Crawl già in corso',
                    'session_id' => $activeSession['id'],
                ]);
                return;
            }
        }

        // Configurazione crawl
        $maxPagesDefault = (int) ModuleLoader::getSetting('crawl-budget', 'max_pages_per_crawl', 500);
        $delayDefault = (int) ModuleLoader::getSetting('crawl-budget', 'crawl_delay_ms', 500);
        $respectRobots = (bool) ModuleLoader::getSetting('crawl-budget', 'respect_robots', true);

        $config = [
            'max_pages' => min((int) ($_POST['max_pages'] ?? $project['max_pages'] ?? $maxPagesDefault), 5000),
            'crawl_delay_ms' => max(100, (int) ($_POST['crawl_delay_ms'] ?? $delayDefault)),
            'respect_robots' => isset($_POST['respect_robots']) ? (bool) $_POST['respect_robots'] : $respectRobots,
        ];

        try {
            // Pulisci dati crawl precedente
            $this->pageModel->deleteByProject($id);
            $this->issueModel->deleteByProject($id);

            // Crea sessione
            $sessionId = $this->sessionModel->create($id, $config);

            // Avvia sessione
            $this->sessionModel->start($sessionId);

            // Init crawler
            $crawler = new BudgetCrawlerService();
            $crawler->init($id, $sessionId, $project['domain'], $config);

            // Fase 1: Fetch robots.txt + sitemap
            $crawler->fetchRobotsAndSitemap();
            Database::reconnect();

            // Fase 2: Seed URL (homepage + sitemap URL)
            $seedCount = $crawler->seedUrls();
            Database::reconnect();

            if ($seedCount === 0) {
                $this->sessionModel->fail($sessionId, 'Nessun URL trovato per il crawl');
                $this->projectModel->update($id, ['status' => 'failed']);
                jsonResponse(['error' => true, 'message' => 'Nessun URL trovato']);
                return;
            }

            // Aggiorna sessione con pagine trovate
            $this->sessionModel->update($sessionId, ['pages_found' => $seedCount]);

            // Crea job
            $jobId = $this->jobModel->create([
                'project_id' => $id,
                'session_id' => $sessionId,
                'user_id' => $user['id'],
                'items_total' => $seedCount,
                'config' => json_encode($config),
            ]);

            // Aggiorna progetto
            $this->projectModel->update($id, [
                'status' => 'crawling',
                'current_session_id' => $sessionId,
            ]);

            jsonResponse([
                'success' => true,
                'session_id' => $sessionId,
                'job_id' => $jobId,
                'pages_found' => $seedCount,
                'config' => $config,
                'message' => "Trovati {$seedCount} URL da analizzare",
            ]);

        } catch (\Exception $e) {
            if (isset($sessionId)) {
                $this->sessionModel->fail($sessionId, $e->getMessage());
            }
            $this->projectModel->update($id, ['status' => 'failed']);
            jsonResponse(['error' => true, 'message' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /crawl-budget/projects/{id}/crawl/stream?job_id=X
     *
     * SSE stream: crawla le pagine una ad una, analizza, emette eventi.
     */
    public function processStream(int $id): void
    {
        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        ignore_user_abort(true);
        set_time_limit(0);
        session_write_close();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $job = $this->jobModel->find($jobId);

        if (!$job || (int) $job['project_id'] !== $id) {
            $this->sendEvent('error', ['message' => 'Job non trovato']);
            exit;
        }

        $sessionId = (int) $job['session_id'];
        $projectId = $id;

        // Se il job è già running, un altro processo lo sta gestendo.
        // Non ri-processare: relay polling via SSE al client.
        if ($job['status'] === CrawlJob::STATUS_RUNNING) {
            $this->relayJobStatus($jobId, $sessionId);
            exit;
        }

        // Se job già completato/errore/cancellato, notifica il client
        if (in_array($job['status'], [CrawlJob::STATUS_COMPLETED, CrawlJob::STATUS_ERROR, CrawlJob::STATUS_CANCELLED])) {
            $eventName = $job['status'] === CrawlJob::STATUS_ERROR ? 'error' : $job['status'];
            $this->sendEvent($eventName, $this->jobModel->getJobResponse($jobId));
            exit;
        }

        // Avvia job (solo se pending)
        $this->jobModel->start($jobId);

        // Recupera progetto per dominio
        $project = $this->projectModel->find($projectId);
        $config = json_decode($job['config'] ?? '{}', true) ?: [];

        // Init crawler
        $crawler = new BudgetCrawlerService();
        $crawler->init($projectId, $sessionId, $project['domain'], $config);

        // Init analyzer
        $analyzer = new BudgetAnalyzerService();

        $this->sendEvent('started', [
            'total' => (int) $job['items_total'],
            'job_id' => $jobId,
        ]);

        $completed = 0;
        $totalIssuesFound = 0;

        while (true) {
            Database::reconnect();

            // Check cancellazione
            if ($this->jobModel->isCancelled($jobId)) {
                $this->sessionModel->requestStop($sessionId);
                $this->finalizeCrawl($projectId, $sessionId, true);
                $this->sessionModel->stop($sessionId);
                $this->projectModel->update($projectId, ['status' => 'stopped']);

                $this->sendEvent('cancelled', $this->jobModel->getJobResponse($jobId));

                try {
                    Database::reconnect();
                    \Services\NotificationService::send((int) $job['user_id'], 'operation_failed',
                        'Analisi Crawl Budget interrotta', [
                        'icon' => 'exclamation-triangle',
                        'color' => 'red',
                        'action_url' => '/crawl-budget/projects/' . $projectId . '/results',
                        'body' => 'La scansione è stata annullata dall\'utente.',
                        'data' => ['module' => 'crawl-budget', 'project_id' => $projectId],
                    ]);
                } catch (\Exception $e) {
                    // silently fail
                }

                break;
            }

            // Prossima pagina pending
            $pendingPage = $this->pageModel->findPending($sessionId);

            if (!$pendingPage) {
                // Tutte le pagine processate — post-analysis e finalizzazione
                Database::reconnect();

                // Post-analysis: orphan pages, duplicate titles, canonical chains
                $postIssues = $analyzer->runPostAnalysis($projectId, $sessionId);
                $totalIssuesFound += $postIssues;
                Database::reconnect();

                $this->finalizeCrawl($projectId, $sessionId, false);
                $this->jobModel->complete($jobId);
                $this->sessionModel->complete($sessionId);

                $this->sendEvent('completed', $this->jobModel->getJobResponse($jobId));

                try {
                    Database::reconnect();
                    $projectName = $project['domain'] ?? "Progetto #{$projectId}";
                    \Services\NotificationService::send((int) $job['user_id'], 'operation_completed',
                        "Analisi Crawl Budget completata per {$projectName}", [
                        'icon' => 'check-circle',
                        'color' => 'emerald',
                        'action_url' => '/crawl-budget/projects/' . $projectId . '/results',
                        'body' => "Scansionate {$completed} pagine, trovati {$totalIssuesFound} problemi.",
                        'data' => ['module' => 'crawl-budget', 'project_id' => $projectId],
                    ]);
                } catch (\Exception $e) {
                    // silently fail
                }

                break;
            }

            $pageId = (int) $pendingPage['id'];
            $url = $pendingPage['url'];

            try {
                // Segna come in crawling
                $this->pageModel->markCrawling($pageId);

                // Crawl la pagina
                $pageData = $crawler->crawlPage($url);
                Database::reconnect();

                if ($pageData && !isset($pageData['error'])) {
                    // Aggiungi depth dalla pagina pending
                    $pageData['depth'] = (int) ($pendingPage['depth'] ?? 0);
                    $pageData['discovered_from'] = $pendingPage['discovered_from'] ?? null;

                    // Salva risultati crawl nella pagina
                    $this->pageModel->markCrawled($pageId, [
                        'http_status' => $pageData['http_status'] ?? 0,
                        'content_type' => $pageData['content_type'] ?? null,
                        'response_time_ms' => $pageData['response_time_ms'] ?? 0,
                        'content_length' => $pageData['content_length'] ?? 0,
                        'word_count' => $pageData['word_count'] ?? 0,
                        'title' => mb_substr($pageData['title'] ?? '', 0, 500),
                        'meta_robots' => $pageData['meta_robots'] ?? null,
                        'canonical_url' => $pageData['canonical_url'] ?? null,
                        'canonical_matches' => $pageData['canonical_matches'] ?? 1,
                        'is_indexable' => $pageData['is_indexable'] ?? 1,
                        'indexability_reason' => $pageData['indexability_reason'] ?? null,
                        'redirect_target' => $pageData['redirect_target'] ?? null,
                        'redirect_chain' => is_array($pageData['redirect_chain'] ?? null) ? json_encode($pageData['redirect_chain']) : ($pageData['redirect_chain'] ?? null),
                        'redirect_hops' => $pageData['redirect_hops'] ?? 0,
                        'in_robots_allowed' => $pageData['in_robots_allowed'] ?? 1,
                        'has_parameters' => $pageData['has_parameters'] ?? 0,
                        'internal_links_out' => $pageData['internal_links_out'] ?? 0,
                    ]);

                    // Scopri nuovi URL interni e inseriscili come pending
                    $internalLinks = $pageData['internal_links'] ?? [];
                    $newDepth = ($pageData['depth'] ?? 0) + 1;
                    foreach ($internalLinks as $linkUrl) {
                        // Inserisci solo se non esiste ancora
                        $this->pageModel->upsert($projectId, $sessionId, $linkUrl, [
                            'status' => 'pending',
                            'depth' => $newDepth,
                            'discovered_from' => $url,
                        ]);
                    }
                    Database::reconnect();

                    // Aggiorna pages_found con nuovi URL scoperti
                    $totalPending = $this->pageModel->countBySession($sessionId);
                    $this->sessionModel->update($sessionId, ['pages_found' => $totalPending]);
                    $this->jobModel->update($jobId, ['items_total' => $totalPending]);

                    // Controlla limite max_pages
                    $maxPages = $config['max_pages'] ?? 5000;
                    if ($totalPending > $maxPages) {
                        // Rimuovi pagine pending oltre il limite (le piu profonde)
                        Database::execute(
                            "DELETE FROM cb_pages WHERE session_id = ? AND status = 'pending'
                             AND depth > (SELECT min_depth FROM (SELECT MIN(depth) as min_depth FROM cb_pages WHERE session_id = ? AND status = 'pending') t)
                             ORDER BY depth DESC, id DESC
                             LIMIT ?",
                            [$sessionId, $sessionId, $totalPending - $maxPages]
                        );
                    }

                    // Analizza la pagina per issue
                    $pageData['page_id'] = $pageId;
                    $issues = $analyzer->analyzePage($pageData, $projectId, $sessionId);
                    $issueCount = count($issues);
                    $totalIssuesFound += $issueCount;
                    Database::reconnect();

                    $completed++;
                    $this->jobModel->incrementCompleted($jobId);

                    // Aggiorna sessione
                    $this->sessionModel->incrementPagesCrawled($sessionId);
                    if ($issueCount > 0) {
                        $this->sessionModel->incrementIssuesFound($sessionId, $issueCount);
                    }

                    $this->sendEvent('item_completed', [
                        'url' => $url,
                        'completed' => $completed,
                        'total' => max((int) $job['items_total'], $totalPending),
                        'issues' => $issueCount,
                        'http_status' => $pageData['http_status'] ?? 0,
                        'percent' => round(($completed / max($totalPending, 1)) * 100, 1),
                    ]);
                } else {
                    // Errore crawl
                    $this->pageModel->markError($pageId, $pageData['error'] ?? 'Errore durante il crawl');
                    $this->jobModel->incrementFailed($jobId);

                    $this->sendEvent('item_error', [
                        'url' => $url,
                        'error' => $pageData['error'] ?? 'Errore sconosciuto',
                    ]);
                }
            } catch (\Exception $e) {
                Database::reconnect();
                $this->pageModel->markError($pageId, $e->getMessage());
                $this->jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            // Rate limiting
            $delay = $config['crawl_delay_ms'] ?? 500;
            usleep($delay * 1000);
        }

        exit;
    }

    /**
     * POST /crawl-budget/projects/{id}/crawl/cancel
     */
    public function cancel(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
            return;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);

        if ($jobId) {
            $job = $this->jobModel->find($jobId);
            if ($job && !empty($job['session_id'])) {
                $this->sessionModel->requestStop((int) $job['session_id']);
            }
            $this->jobModel->cancel($jobId);
        }

        $this->projectModel->update($id, ['status' => 'stopping']);

        jsonResponse([
            'success' => true,
            'message' => 'Stop richiesto. Il crawl si fermerà al termine della pagina corrente.',
        ]);
    }

    /**
     * GET /crawl-budget/projects/{id}/crawl/status
     *
     * Restituisce stato crawl corrente: active_job_id, sessione, issues.
     * Usato dalla UI per recovery dopo navigazione (pattern SEO Audit).
     */
    public function status(int $id): void
    {
        header('Content-Type: application/json');

        Middleware::auth();
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
            exit;
        }

        $activeJob = $this->jobModel->findActiveByProject($id);
        $session = $this->sessionModel->findActiveByProject($id);

        $issues = null;
        $realCounts = null;
        if ($session) {
            $sessionId = (int) $session['id'];
            $issues = $this->issueModel->countBySeverity($sessionId);
            // Conteggio reale dalle pagine (piu affidabile dei contatori sessione)
            $realCounts = Database::fetch(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN status IN ('crawled','error') THEN 1 ELSE 0 END) as done
                 FROM cb_pages WHERE session_id = ?",
                [$sessionId]
            );
        }

        echo json_encode([
            'success' => true,
            'active_job_id' => $activeJob ? (int) $activeJob['id'] : null,
            'session' => $session ? [
                'id' => (int) $session['id'],
                'pages_found' => (int) ($realCounts['total'] ?? $session['pages_found'] ?? 0),
                'pages_crawled' => (int) ($realCounts['done'] ?? $session['pages_crawled'] ?? 0),
                'issues_found' => (int) ($session['issues_found'] ?? 0),
                'current_url' => $session['current_url'] ?? null,
                'status' => $session['status'] ?? null,
            ] : null,
            'issues' => $issues,
        ]);
        exit;
    }

    /**
     * GET /crawl-budget/projects/{id}/crawl/job-status?job_id=X
     *
     * Polling fallback per quando SSE non funziona.
     */
    public function jobStatus(int $id): void
    {
        header('Content-Type: application/json');

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $rawJob = $this->jobModel->find($jobId);

        if (!$rawJob || (int) $rawJob['project_id'] !== $id) {
            echo json_encode(['success' => false, 'message' => 'Job non trovato']);
            exit;
        }

        $jobResponse = $this->jobModel->getJobResponse($jobId);

        $sessionId = (int) $rawJob['session_id'];
        $sessionStats = null;
        if ($sessionId) {
            $sessionStats = $this->sessionModel->getStats($sessionId);

            // Conteggio reale dalle pagine (piu affidabile dei contatori)
            $realCounts = Database::fetch(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN status IN ('crawled','error') THEN 1 ELSE 0 END) as done
                 FROM cb_pages WHERE session_id = ?",
                [$sessionId]
            );
            if ($realCounts) {
                $total = (int) $realCounts['total'];
                $done = (int) $realCounts['done'];
                $jobResponse['items_total'] = $total;
                $jobResponse['items_completed'] = $done;
                $jobResponse['progress'] = $total > 0 ? round(($done / $total) * 100, 1) : 0;
            }
        }

        echo json_encode([
            'success' => true,
            'job' => $jobResponse,
            'session' => $sessionStats,
            'issues' => $this->issueModel->countBySeverity($sessionId),
        ]);
        exit;
    }

    /**
     * Finalizza crawl: calcola score, aggiorna progetto.
     */
    private function finalizeCrawl(int $projectId, int $sessionId, bool $stopped = false): void
    {
        $analyzer = new BudgetAnalyzerService();
        $score = $analyzer->calculateScore($sessionId);

        $session = $this->sessionModel->find($sessionId);
        $pagesCrawled = (int) ($session['pages_crawled'] ?? 0);

        $this->projectModel->update($projectId, [
            'status' => $stopped ? 'stopped' : 'completed',
            'crawl_budget_score' => $score,
            'last_crawl_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Relay: quando un altro processo sta già eseguendo il job,
     * questo metodo fa polling dal DB e inoltra gli aggiornamenti via SSE.
     */
    private function relayJobStatus(int $jobId, int $sessionId): void
    {
        $this->sendEvent('started', ['total' => 0, 'job_id' => $jobId, 'relay' => true]);

        $lastCompleted = -1;

        while (true) {
            Database::reconnect();

            $job = $this->jobModel->find($jobId);
            if (!$job) break;

            // Conteggio reale dal DB
            $realCounts = Database::fetch(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN status IN ('crawled','error') THEN 1 ELSE 0 END) as done
                 FROM cb_pages WHERE session_id = ?",
                [$sessionId]
            );
            $total = (int) ($realCounts['total'] ?? 0);
            $done = (int) ($realCounts['done'] ?? 0);
            $issues = $this->issueModel->countBySeverity($sessionId);
            $issueTotal = ($issues['critical'] ?? 0) + ($issues['warning'] ?? 0) + ($issues['notice'] ?? 0);

            if ($done !== $lastCompleted) {
                $lastCompleted = $done;
                $this->sendEvent('item_completed', [
                    'completed' => $done,
                    'total' => $total,
                    'issues' => 0,
                    'percent' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
                    'url' => '',
                ]);
            }

            // Job terminato?
            if ($job['status'] === CrawlJob::STATUS_COMPLETED) {
                $this->sendEvent('completed', $this->jobModel->getJobResponse($jobId));
                break;
            }
            if ($job['status'] === CrawlJob::STATUS_CANCELLED) {
                $this->sendEvent('cancelled', []);
                break;
            }
            if ($job['status'] === CrawlJob::STATUS_ERROR) {
                $this->sendEvent('error', ['message' => $job['error_message'] ?? 'Errore']);
                break;
            }

            sleep(2);
        }
    }

    /**
     * Helper SSE: invia evento al client.
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }
}
