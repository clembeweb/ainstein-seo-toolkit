<?php

namespace Modules\ContentCreator\Controllers;

use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Core\Credits;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Url;
use Modules\ContentCreator\Models\Connector;
use Modules\ContentCreator\Models\Job;
use Modules\ContentCreator\Models\OperationLog;

class ExportController
{
    private Project $project;
    private Url $url;

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
    //  CSV EXPORT
    // ─────────────────────────────────────────────

    /**
     * GET - Esporta URL come CSV (generated, approved, published)
     */
    public function exportCsv(int $id): void
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

        // Recupera tutte le URL del progetto con status esportabili
        $allUrls = $this->url->getByProject($id);
        $exportableStatuses = ['generated', 'approved', 'published'];
        $urls = array_filter($allUrls, function ($url) use ($exportableStatuses) {
            return in_array($url['status'], $exportableStatuses);
        });

        if (empty($urls)) {
            $_SESSION['flash_error'] = 'Nessuna URL da esportare';
            header("Location: /content-creator/projects/{$id}/results");
            exit;
        }

        // Nome file sicuro
        $projectName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['name']);
        $date = date('Y-m-d');
        $filename = "content-creator-{$projectName}-{$date}.csv";

        // Headers per download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM UTF-8 per compatibilita Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header colonne
        fputcsv($output, [
            'url',
            'slug',
            'keyword',
            'category',
            'intent',
            'ai_h1',
            'ai_content',
            'ai_word_count',
            'status',
        ], ';');

        // Righe dati
        foreach ($urls as $url) {
            // Tronca contenuto HTML a 32000 chars per compatibilità Excel
            $content = $url['ai_content'] ?? '';
            if (mb_strlen($content) > 32000) {
                $content = mb_substr($content, 0, 32000) . '...';
            }

            fputcsv($output, [
                $url['url'] ?? '',
                $url['slug'] ?? '',
                $url['keyword'] ?? '',
                $url['category'] ?? '',
                $url['intent'] ?? '',
                $url['ai_h1'] ?? '',
                $content,
                $url['ai_word_count'] ?? 0,
                $url['status'] ?? '',
            ], ';');
        }

        fclose($output);

        // Log operazione
        $operationLog = new OperationLog();
        $operationLog->log([
            'user_id' => $user['id'],
            'project_id' => $id,
            'operation' => 'export',
            'credits_used' => 0,
            'status' => 'success',
            'details' => [
                'format' => 'csv',
                'rows' => count($urls),
                'filename' => $filename,
            ],
        ]);

        exit;
    }

    // ─────────────────────────────────────────────
    //  CMS PUSH JOB
    // ─────────────────────────────────────────────

    /**
     * POST - Avvia job di push CMS
     */
    public function startPushJob(int $id): void
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

        // Verifica connettore CMS configurato sul progetto
        $connectorId = (int) ($project['connector_id'] ?? 0);
        if ($connectorId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Nessun connettore CMS configurato per questo progetto']);
            return;
        }

        // Verifica connettore esiste e appartiene all'utente
        $connectorModel = new Connector();
        $connector = $connectorModel->findByUser($connectorId, $user['id']);
        if (!$connector) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Connettore CMS non trovato o non autorizzato']);
            return;
        }

        $jobModel = new Job();

        // Verifica nessun job push attivo
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_CMS_PUSH);
        if ($activeJob) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Un job di push CMS è già in esecuzione',
                'job_id' => (int) $activeJob['id'],
            ]);
            return;
        }

        // Conta URL approvate
        $approvedCount = $this->url->countByProject($id, 'approved');

        if ($approvedCount === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Nessuna URL approvata da pubblicare']);
            return;
        }

        // Crea job
        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_CMS_PUSH,
            'items_requested' => $approvedCount,
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $approvedCount,
        ]);
    }

    /**
     * GET SSE - Stream push CMS in tempo reale
     */
    public function pushStream(int $id): void
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

        $this->sendEvent('started', [
            'job_id' => $jobId,
            'total' => $total,
        ]);

        $connectorModel = new Connector();
        $operationLog = new OperationLog();

        while (true) {
            Database::reconnect();

            // Check cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                ]);
                break;
            }

            // Prossima URL approvata
            $stmt = Database::getConnection()->prepare(
                "SELECT * FROM cc_urls WHERE project_id = ? AND status = 'approved' ORDER BY id LIMIT 1"
            );
            $stmt->execute([$id]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$item) {
                // Nessuna URL rimasta - completa job
                Database::reconnect();
                $jobModel->complete($jobId);

                // Log operazione
                $operationLog->log([
                    'user_id' => $user['id'],
                    'project_id' => $id,
                    'operation' => 'cms_push',
                    'credits_used' => 0,
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
                ]);
                break;
            }

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
                // Carica configurazione connettore
                $connectorId = (int) ($project['connector_id'] ?? 0);
                $connector = $connectorModel->findByUser($connectorId, $user['id']);

                if (!$connector) {
                    throw new \Exception('Connettore CMS non trovato');
                }

                $config = json_decode($connector['config'] ?? '{}', true);
                if (!is_array($config)) {
                    $config = [];
                }

                $connectorService = match ($connector['type']) {
                    'wordpress' => new \Modules\ContentCreator\Services\Connectors\WordPressConnector($config),
                    'shopify' => new \Modules\ContentCreator\Services\Connectors\ShopifyConnector($config),
                    'prestashop' => new \Modules\ContentCreator\Services\Connectors\PrestaShopConnector($config),
                    'magento' => new \Modules\ContentCreator\Services\Connectors\MagentoConnector($config),
                    default => throw new \Exception('Tipo connettore non supportato: ' . $connector['type']),
                };

                $entityId = $item['cms_entity_id'] ?? $item['id'];
                $entityType = $item['cms_entity_type'] ?? 'page';

                $pushResult = $connectorService->updateItem((string) $entityId, $entityType, [
                    'content' => $item['ai_content'] ?? '',
                    'h1' => $item['ai_h1'] ?? '',
                ]);

                if (!$pushResult['success']) {
                    throw new \Exception($pushResult['message'] ?? 'Errore push CMS');
                }

                Database::reconnect();
                $this->url->markPublished($item['id']);

                $completed++;
                $jobModel->incrementCompleted($jobId);

                $this->sendEvent('item_completed', [
                    'url_id' => (int) $item['id'],
                    'url' => $item['url'],
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round((($completed + $failed) / $total) * 100) : 0,
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                $this->url->markPublishError($item['id'], $e->getMessage());

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

            // Pausa tra push (500ms - rate limiting API CMS)
            usleep(500000);
        }

        exit;
    }

    /**
     * GET - Polling fallback per stato push job
     */
    public function pushJobStatus(int $id): void
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
     * POST - Annulla push job
     */
    public function cancelPushJob(int $id): void
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
}
