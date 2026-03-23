<?php

namespace Modules\ContentCreator\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\Database;
use Core\Credits;
use Core\ModuleLoader;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Image;
use Modules\ContentCreator\Models\ImageVariant;
use Modules\ContentCreator\Models\Job;
use Modules\ContentCreator\Models\Connector;
use Modules\ContentCreator\Services\ImageGenerationService;
use Modules\ContentCreator\Services\Connectors\ImageCapableConnectorInterface;
use Services\ProjectAccessService;

/**
 * SSE Controller per generazione immagini e push CMS.
 *
 * Pattern identico a GeneratorController.php:
 * startJob → stream (SSE) → jobStatus (polling fallback) → cancel
 */
class ImageGeneratorController
{
    private Project $project;
    private Image $image;
    private ImageVariant $variant;

    private const GENERATE_CREDIT_COST = 2; // Per variant
    private const API_DELAY_MS = 2000000;   // 2 seconds between API calls (rate limit protection)

    public function __construct()
    {
        $this->project = new Project();
        $this->image = new Image();
        $this->variant = new ImageVariant();
    }

    /**
     * Send SSE event
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    /**
     * Get project with access check (JSON error response)
     */
    private function getProject(int $id, int $userId): ?array
    {
        $project = $this->project->findAccessible($id, $userId);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return null;
        }
        return $project;
    }

    // ===== IMAGE GENERATION SSE =====

    /**
     * Start generate job
     * POST /content-creator/projects/{id}/images/start-generate-job
     */
    public function startGenerateJob(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();
        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi per generare']);
            return;
        }

        $jobModel = new Job();
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_IMAGE_GENERATE);
        if ($activeJob) {
            echo json_encode(['error' => true, 'message' => 'Generazione già in corso', 'job_id' => (int) $activeJob['id']]);
            return;
        }

        $readyCount = $this->image->countReadyForGeneration($id);
        if ($readyCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessun prodotto pronto per la generazione']);
            return;
        }

        // Estimate credits: items × variants_count × cost_per_variant
        $defaults = ImageGenerationService::getProjectDefaults($project);
        $variantsCount = (int) ($defaults['variants_count'] ?? 3);
        $estimatedCredits = $readyCount * $variantsCount * self::GENERATE_CREDIT_COST;

        $creditUserId = ProjectAccessService::getCreditUserId($project, $user['id']);
        if (!Credits::hasEnough($creditUserId, $estimatedCredits)) {
            $balance = Credits::getBalance($creditUserId);
            echo json_encode(['error' => true, 'message' => "Crediti insufficienti. Richiesti: {$estimatedCredits}, disponibili: {$balance}"]);
            return;
        }

        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_IMAGE_GENERATE,
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
     * SSE stream for image generation
     * GET /content-creator/projects/{id}/images/generate-stream
     */
    public function generateStream(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['type'] !== Job::TYPE_IMAGE_GENERATE) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) return;

        // SSE setup (critical pattern — Golden Rules)
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $jobModel->start($jobId);

        $defaults = ImageGenerationService::getProjectDefaults($project);
        $variantsCount = (int) ($defaults['variants_count'] ?? 3);
        $creditUserId = ProjectAccessService::getCreditUserId($project, $user['id']);

        $service = new ImageGenerationService();
        $completed = 0;
        $failed = 0;
        $creditsUsed = 0;
        $total = (int) $job['items_requested'];

        $this->sendEvent('started', ['job_id' => $jobId, 'total' => $total]);

        while (true) {
            Database::reconnect();

            // Check cancellation
            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            // Get next item
            $items = $this->image->getNextForGeneration($id, 1);
            if (empty($items)) {
                // All done
                $jobModel->complete($jobId);
                $this->sendEvent('completed', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            $item = $items[0];
            $itemName = $item['product_name'];

            $jobModel->updateProgress($jobId, [
                'current_item' => $itemName,
                'current_item_id' => $item['id'],
            ]);

            $this->sendEvent('progress', [
                'current_item' => $itemName,
                'completed' => $completed,
                'failed' => $failed,
                'total' => $total,
                'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
            ]);

            $itemSuccess = false;
            $variantsGenerated = 0;

            // Generate variants
            for ($v = 1; $v <= $variantsCount; $v++) {
                // Check credits before each variant
                if (!Credits::hasEnough($creditUserId, self::GENERATE_CREDIT_COST)) {
                    $this->sendEvent('item_error', [
                        'image_id' => $item['id'],
                        'product_name' => $itemName,
                        'error' => 'Crediti insufficienti',
                        'completed' => $completed,
                        'failed' => $failed,
                        'total' => $total,
                    ]);
                    $failed++;
                    $jobModel->incrementFailed($jobId);
                    break 2; // Exit both loops — no more credits
                }

                set_time_limit(300); // Reset per variant

                $result = $service->generateVariant($item, $defaults, $v);
                Database::reconnect();

                if ($result['success']) {
                    $this->variant->create($result['variant_data']);
                    Credits::consume($creditUserId, self::GENERATE_CREDIT_COST, 'image_generate', 'content-creator');
                    $creditsUsed += self::GENERATE_CREDIT_COST;
                    $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);
                    $variantsGenerated++;
                } else {
                    $error = $result['error'] ?? 'Errore sconosciuto';

                    // Content policy → skip (no retry)
                    if ($service->getProvider()->isContentPolicyError($error)) {
                        $this->image->markError((int) $item['id'], $error);
                        break; // Skip remaining variants for this item
                    }

                    // Transient error → retry once with backoff
                    if ($service->getProvider()->isTransientError($error)) {
                        usleep(2000000); // 2s backoff
                        Database::reconnect();
                        $retryResult = $service->generateVariant($item, $defaults, $v);
                        Database::reconnect();

                        if ($retryResult['success']) {
                            $this->variant->create($retryResult['variant_data']);
                            Credits::consume($creditUserId, self::GENERATE_CREDIT_COST, 'image_generate', 'content-creator');
                            $creditsUsed += self::GENERATE_CREDIT_COST;
                            $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);
                            $variantsGenerated++;
                            continue;
                        }
                    }

                    // Failed variant — continue with remaining variants
                    // Error logged but item not marked as error yet
                }

                // Rate limit delay between API calls
                usleep(self::API_DELAY_MS);
            }

            Database::reconnect();

            if ($variantsGenerated > 0) {
                // At least 1 variant generated — mark as generated
                $this->image->updateStatus((int) $item['id'], Image::STATUS_GENERATED);
                $completed++;
                $jobModel->incrementCompleted($jobId);

                $this->sendEvent('item_completed', [
                    'image_id' => $item['id'],
                    'product_name' => $itemName,
                    'variants_generated' => $variantsGenerated,
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
                ]);
            } else {
                // All variants failed — include last error for debugging
                if ($item['status'] !== Image::STATUS_ERROR) {
                    $lastError = $result['error'] ?? 'Errore sconosciuto';
                    $this->image->markError((int) $item['id'], "Tutte le varianti fallite: {$lastError}");
                }
                $failed++;
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'image_id' => $item['id'],
                    'product_name' => $itemName,
                    'error' => 'Generazione fallita',
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
                ]);
            }
        }

        exit;
    }

    /**
     * Job status (polling fallback)
     * GET /content-creator/projects/{id}/images/generate-job-status
     */
    public function generateJobStatus(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        header('Content-Type: application/json');

        if (!$job) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
    }

    /**
     * Cancel job
     * POST /content-creator/projects/{id}/images/cancel-job
     */
    public function cancelJob(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $jobId = (int) ($_POST['job_id'] ?? $_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        header('Content-Type: application/json');

        if (!$job) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $jobModel->cancel($jobId);
        echo json_encode(['success' => true, 'message' => 'Job annullato']);
    }

    // ===== CMS PUSH SSE =====

    /**
     * Start push job
     * POST /content-creator/projects/{id}/images/start-push-job
     */
    public function startPushJob(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();
        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi']);
            return;
        }

        // Count approved variants ready for push
        $approvedCount = $this->variant->countApprovedByProject($id);
        if ($approvedCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna variante approvata da pubblicare']);
            return;
        }

        $jobModel = new Job();
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_IMAGE_PUSH);
        if ($activeJob) {
            echo json_encode(['error' => true, 'message' => 'Push già in corso', 'job_id' => (int) $activeJob['id']]);
            return;
        }

        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_IMAGE_PUSH,
            'items_requested' => $approvedCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $approvedCount,
        ]);
    }

    /**
     * SSE stream for CMS push
     * GET /content-creator/projects/{id}/images/push-stream
     */
    public function pushStream(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['type'] !== Job::TYPE_IMAGE_PUSH) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || empty($project['connector_id'])) return;

        // SSE setup
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $jobModel->start($jobId);

        // Get connector
        $connectorModel = new Connector();
        $connData = $connectorModel->find($project['connector_id']);
        $connector = $connectorModel->createInstance($connData);

        if (!($connector instanceof ImageCapableConnectorInterface)) {
            $this->sendEvent('completed', ['job_id' => $jobId, 'error' => 'Connettore non supporta upload immagini']);
            exit;
        }

        $service = new ImageGenerationService();
        $defaults = ImageGenerationService::getProjectDefaults($project);
        $pushMode = $defaults['push_mode'] ?? ModuleLoader::getSetting('content-creator', 'image_push_mode', 'add_as_gallery');

        $variants = $this->variant->getApprovedByProject($id);
        $total = count($variants);
        $completed = 0;
        $failed = 0;

        $this->sendEvent('started', ['job_id' => $jobId, 'total' => $total]);

        foreach ($variants as $v) {
            Database::reconnect();

            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', ['job_id' => $jobId, 'completed' => $completed, 'failed' => $failed]);
                break;
            }

            if (empty($v['cms_entity_id'])) {
                $failed++;
                $jobModel->incrementFailed($jobId);
                $this->variant->markPushError((int) $v['id'], 'Nessun entity ID CMS');
                continue;
            }

            $imagePath = $service->getAbsolutePath($v['image_path']);
            $alt = $v['product_name'] . ' - Variante ' . $v['variant_number'];
            $position = $pushMode === 'replace_main' ? 0 : null;

            $result = $connector->uploadImage($v['cms_entity_id'], $v['cms_entity_type'] ?? 'product', $imagePath, [
                'alt' => $alt,
                'position' => $position,
                'filename' => ($v['sku'] ?: 'img') . '-v' . $v['variant_number'] . '.' . pathinfo($v['image_path'], PATHINFO_EXTENSION),
            ]);

            Database::reconnect();

            if ($result['success']) {
                $this->variant->markPushed((int) $v['id']);

                // Update image status to published
                $this->image->markPublished((int) $v['image_id']);

                $completed++;
                $jobModel->incrementCompleted($jobId);

                $this->sendEvent('item_completed', [
                    'variant_id' => $v['id'],
                    'product_name' => $v['product_name'],
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
                ]);
            } else {
                $this->variant->markPushError((int) $v['id'], $result['error'] ?? 'Errore upload');
                $failed++;
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'variant_id' => $v['id'],
                    'product_name' => $v['product_name'],
                    'error' => $result['error'] ?? 'Errore upload',
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                ]);
            }

            usleep(500000); // 500ms between push calls
        }

        if (!$jobModel->isCancelled($jobId)) {
            $jobModel->complete($jobId);
            $this->sendEvent('completed', [
                'job_id' => $jobId,
                'completed' => $completed,
                'failed' => $failed,
            ]);
        }

        exit;
    }

    /**
     * Push job status (polling fallback)
     * GET /content-creator/projects/{id}/images/push-job-status
     */
    public function pushJobStatus(int $id): void
    {
        // Same pattern as generateJobStatus
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        header('Content-Type: application/json');

        if (!$job) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        echo json_encode(['success' => true, 'job' => $jobModel->getJobResponse($jobId)]);
    }
}
