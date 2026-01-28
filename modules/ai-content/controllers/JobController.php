<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Modules\AiContent\Models\Project;
use Modules\AiContent\Models\ProcessJob;

/**
 * JobController
 * Gestisce la visualizzazione e controllo dei job di elaborazione
 */
class JobController
{
    private Project $project;
    private ProcessJob $processJob;

    public function __construct()
    {
        $this->project = new Project();
        $this->processJob = new ProcessJob();
    }

    /**
     * Lista tutti i job
     * GET /ai-content/jobs
     */
    public function index(): string
    {
        $user = Auth::user();
        $userId = $user['id'];

        // Filtri
        $statusFilter = $_GET['status'] ?? null;
        $projectFilter = $_GET['project'] ?? null;

        // Query base
        $sql = "SELECT j.*, p.name as project_name, p.type as project_type
                FROM aic_process_jobs j
                JOIN aic_projects p ON j.project_id = p.id
                WHERE j.user_id = ?";
        $params = [$userId];

        // Filtro per stato
        if ($statusFilter && in_array($statusFilter, ['pending', 'running', 'completed', 'error', 'cancelled'])) {
            $sql .= " AND j.status = ?";
            $params[] = $statusFilter;
        }

        // Filtro per progetto
        if ($projectFilter) {
            $sql .= " AND j.project_id = ?";
            $params[] = (int) $projectFilter;
        }

        $sql .= " ORDER BY j.created_at DESC LIMIT 100";

        $jobs = Database::fetchAll($sql, $params);

        // Carica progetti per filtro dropdown
        $projects = $this->project->allByUser($userId);

        // Statistiche
        $stats = Database::fetch(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'running') as running,
                SUM(status = 'pending') as pending,
                SUM(status = 'completed') as completed,
                SUM(status = 'error') as errors,
                SUM(status = 'cancelled') as cancelled
             FROM aic_process_jobs
             WHERE user_id = ?",
            [$userId]
        );

        return View::render('ai-content/jobs/index', [
            'title' => 'Gestione Job - AI Content',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'jobs' => $jobs,
            'projects' => $projects,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'projectFilter' => $projectFilter,
        ]);
    }

    /**
     * Cancella job in corso (running/pending)
     * POST /ai-content/jobs/{id}/cancel
     */
    public function cancel(int $id): void
    {
        $user = Auth::user();
        $job = $this->processJob->findByUser($id, $user['id']);

        if (!$job) {
            $this->jsonResponse(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        if (!in_array($job['status'], ['pending', 'running'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Solo job pending o running possono essere cancellati']);
            return;
        }

        $this->processJob->cancel($id);

        $this->jsonResponse(['success' => true, 'message' => 'Job cancellato']);
    }

    /**
     * Elimina job dallo storico
     * POST /ai-content/jobs/{id}/delete
     */
    public function delete(int $id): void
    {
        $user = Auth::user();
        $job = $this->processJob->findByUser($id, $user['id']);

        if (!$job) {
            $this->jsonResponse(['success' => false, 'error' => 'Job non trovato']);
            return;
        }

        if (in_array($job['status'], ['pending', 'running'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Non puoi eliminare job attivi. Prima cancellali.']);
            return;
        }

        Database::delete('aic_process_jobs', 'id = ? AND user_id = ?', [$id, $user['id']]);

        $this->jsonResponse(['success' => true, 'message' => 'Job eliminato']);
    }

    /**
     * Elimina tutti i job completati/errore/cancellati più vecchi di N giorni
     * POST /ai-content/jobs/cleanup
     */
    public function cleanup(): void
    {
        $user = Auth::user();
        $days = (int) ($_POST['days'] ?? 7);

        if ($days < 1) {
            $days = 7;
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = Database::query(
            "DELETE FROM aic_process_jobs
             WHERE user_id = ?
               AND status IN ('completed', 'error', 'cancelled')
               AND created_at < ?",
            [$user['id'], $cutoffDate]
        );

        $deleted = $result ? $result->rowCount() : 0;

        $this->jsonResponse([
            'success' => true,
            'message' => "Eliminati {$deleted} job più vecchi di {$days} giorni"
        ]);
    }

    /**
     * Cancella tutti i job bloccati (running da più di 30 minuti)
     * POST /ai-content/jobs/cancel-stuck
     */
    public function cancelStuck(): void
    {
        $user = Auth::user();
        $minutes = (int) ($_POST['minutes'] ?? 30);

        if ($minutes < 5) {
            $minutes = 30;
        }

        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $result = Database::query(
            "UPDATE aic_process_jobs
             SET status = 'cancelled',
                 error_message = 'Cancellato automaticamente (bloccato)',
                 completed_at = NOW()
             WHERE user_id = ?
               AND status = 'running'
               AND started_at < ?",
            [$user['id'], $cutoffTime]
        );

        $cancelled = $result ? $result->rowCount() : 0;

        $this->jsonResponse([
            'success' => true,
            'message' => "Cancellati {$cancelled} job bloccati (running da più di {$minutes} minuti)"
        ]);
    }

    /**
     * Helper per risposta JSON
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
