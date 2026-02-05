<?php

namespace Modules\SeoOnpage\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Core\Credits;
use Core\Database;
use Modules\SeoOnpage\Models\Project;
use Modules\SeoOnpage\Models\Page;
use Modules\SeoOnpage\Models\Job;
use Modules\SeoOnpage\Models\Queue;
use Modules\SeoOnpage\Models\Analysis;
use Modules\SeoOnpage\Models\Issue;
use Services\DataForSeoService;

/**
 * AuditController
 * Gestisce audit batch con SSE (Server-Sent Events)
 */
class AuditController
{
    private Project $project;
    private Page $page;
    private Job $job;
    private Queue $queue;
    private Analysis $analysis;
    private Issue $issue;

    public function __construct()
    {
        $this->project = new Project();
        $this->page = new Page();
        $this->job = new Job();
        $this->queue = new Queue();
        $this->analysis = new Analysis();
        $this->issue = new Issue();
    }

    /**
     * Vista principale audit
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        // Conta pagine da analizzare
        $pendingCount = $this->page->countByProject($projectId, 'pending');
        $totalCount = $this->page->countByProject($projectId);

        // Job attivo
        $activeJob = $this->job->getActiveForProject($projectId);

        // Costo stimato
        $costPerPage = Credits::getCost('audit_page', 'seo-onpage');
        $estimatedCost = $totalCount * $costPerPage;

        // Crediti utente
        $userCredits = Credits::getBalance($user['id']);

        return View::render('seo-onpage/audit/index', [
            'title' => 'Audit - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'pendingCount' => $pendingCount,
            'totalCount' => $totalCount,
            'activeJob' => $activeJob,
            'costPerPage' => $costPerPage,
            'estimatedCost' => $estimatedCost,
            'userCredits' => $userCredits,
        ]);
    }

    /**
     * Avvia audit (crea job)
     */
    public function start(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Verifica se c'e gia un job attivo
        $activeJob = $this->job->getActiveForProject($projectId);
        if ($activeJob) {
            echo json_encode([
                'success' => false,
                'error' => 'Un audit e gia in corso',
                'job_id' => $activeJob['id']
            ]);
            return;
        }

        // Ottieni pagine da analizzare
        $scope = $_POST['scope'] ?? 'all'; // all, pending
        if ($scope === 'pending') {
            $pages = $this->page->getPending($projectId, 1000);
        } else {
            $pages = $this->page->allByProject($projectId, ['limit' => 1000]);
        }

        if (empty($pages)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna pagina da analizzare. Importa prima alcune pagine.']);
            return;
        }

        // Verifica crediti
        $costPerPage = Credits::getCost('audit_page', 'seo-onpage');
        $totalCost = count($pages) * $costPerPage;

        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$totalCost}, Disponibili: " . Credits::getBalance($user['id'])
            ]);
            return;
        }

        // Verifica configurazione DataForSEO
        $dataForSeo = new DataForSeoService();
        if (!$dataForSeo->isConfigured()) {
            echo json_encode(['success' => false, 'error' => 'DataForSEO non configurato. Contatta l\'amministratore.']);
            return;
        }

        // Crea job
        $jobId = $this->job->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => 'audit',
            'pages_requested' => count($pages),
        ]);

        // Popola queue
        $device = $project['default_device'] ?? 'desktop';
        $this->queue->bulkAdd($jobId, $projectId, $pages, $device);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'pages_count' => count($pages),
            'estimated_cost' => $totalCost,
        ]);
    }

    /**
     * SSE Stream per progress real-time
     */
    public function stream(int $projectId): void
    {
        // Auth check (senza redirect per SSE)
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            exit;
        }

        $project = $this->project->find($projectId, $user['id']);
        if (!$project) {
            http_response_code(404);
            exit;
        }

        $jobId = $_GET['job_id'] ?? null;
        if (!$jobId) {
            http_response_code(400);
            exit;
        }

        $activeJob = $this->job->find((int) $jobId);
        if (!$activeJob || $activeJob['project_id'] != $projectId) {
            http_response_code(404);
            exit;
        }

        // Rilascia sessione PRIMA del loop SSE
        session_write_close();

        // Pulisci output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Headers SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Avvia job
        $this->job->start($jobId);
        $this->sendEvent('started', ['job_id' => $jobId]);

        // Carica servizi
        $dataForSeo = new DataForSeoService();
        $costPerPage = Credits::getCost('audit_page', 'seo-onpage');

        $pagesCompleted = 0;
        $pagesFailed = 0;
        $totalIssues = 0;
        $scoreSum = 0;
        $maxPages = (int) $activeJob['pages_requested'];

        // Processing loop
        while ($pagesCompleted + $pagesFailed < $maxPages) {
            // Riconnetti DB
            Database::reconnect();

            // Check cancellazione
            if ($this->job->isCancelled($jobId)) {
                $this->sendEvent('cancelled', ['message' => 'Audit annullato']);
                break;
            }

            // Prendi prossimo item
            $queueItem = $this->queue->getNextPendingForJob($jobId);
            if (!$queueItem) {
                break;
            }

            $url = $queueItem['url'];
            $pageId = $queueItem['page_id'];

            // Aggiorna stato
            $this->queue->updateStatus($queueItem['id'], 'processing');
            $this->job->updateProgress($jobId, ['current_url' => $url]);

            $this->sendEvent('progress', [
                'url' => $url,
                'completed' => $pagesCompleted,
                'failed' => $pagesFailed,
                'total' => $maxPages,
                'percent' => round((($pagesCompleted + $pagesFailed) / $maxPages) * 100, 1),
            ]);

            // Analizza pagina
            try {
                $result = $dataForSeo->analyzeInstantPage($url, [
                    'enable_javascript' => true,
                    'enable_browser_rendering' => false,
                ]);

                if ($result['success']) {
                    // Crea analisi
                    $analysisId = $this->analysis->createFromApiResponse(
                        $projectId,
                        $pageId,
                        $user['id'],
                        $result,
                        $queueItem['device'] ?? 'desktop',
                        $costPerPage
                    );

                    // Crea issues dai checks
                    $checks = $result['checks'] ?? [];
                    $issuesCreated = $this->issue->createFromChecks($analysisId, $pageId, $checks);

                    // Aggiorna pagina
                    $score = $result['onpage_score'] ?? 50;
                    $this->page->updateScore($pageId, $score);

                    // Consuma crediti
                    Credits::consume($user['id'], $costPerPage, 'audit_page', 'seo-onpage', [
                        'page_id' => $pageId,
                        'url' => $url,
                    ]);

                    // Aggiorna queue
                    $this->queue->markCompleted($queueItem['id'], $analysisId);
                    $this->job->incrementCompleted($jobId, $costPerPage);

                    $pagesCompleted++;
                    $scoreSum += $score;
                    $totalIssues += $issuesCreated;

                    $this->sendEvent('page_completed', [
                        'url' => $url,
                        'score' => $score,
                        'issues' => $issuesCreated,
                        'completed' => $pagesCompleted,
                        'total' => $maxPages,
                    ]);

                } else {
                    // Errore analisi
                    $this->queue->markError($queueItem['id'], $result['error'] ?? 'Errore sconosciuto');
                    $this->job->incrementFailed($jobId);
                    $this->page->update($pageId, ['status' => 'error']);

                    $pagesFailed++;

                    $this->sendEvent('page_error', [
                        'url' => $url,
                        'error' => $result['error'] ?? 'Errore analisi',
                        'failed' => $pagesFailed,
                    ]);
                }

            } catch (\Exception $e) {
                $this->queue->markError($queueItem['id'], $e->getMessage());
                $this->job->incrementFailed($jobId);
                $pagesFailed++;

                $this->sendEvent('page_error', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            // Pausa per rate limiting
            usleep(500000); // 500ms
        }

        // Completa job
        $avgScore = $pagesCompleted > 0 ? round($scoreSum / $pagesCompleted, 1) : null;
        $this->job->complete($jobId, [
            'avg_score' => $avgScore,
            'total_issues' => $totalIssues,
        ]);

        $this->sendEvent('completed', [
            'completed' => $pagesCompleted,
            'failed' => $pagesFailed,
            'total_issues' => $totalIssues,
            'avg_score' => $avgScore,
            'credits_used' => $pagesCompleted * $costPerPage,
        ]);
    }

    /**
     * Polling fallback per status
     */
    public function status(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = $_GET['job_id'] ?? null;
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'Job ID mancante']);
            return;
        }

        $jobResponse = $this->job->getJobResponse((int) $jobId);

        echo json_encode(['success' => true, 'job' => $jobResponse]);
    }

    /**
     * Annulla audit
     */
    public function cancel(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $jobId = $_POST['job_id'] ?? null;
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'Job ID mancante']);
            return;
        }

        $this->job->cancel((int) $jobId);

        echo json_encode(['success' => true, 'message' => 'Audit annullato']);
    }

    /**
     * Helper: Invia evento SSE
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }
}
