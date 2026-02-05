<?php

namespace Modules\SeoOnpage\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoOnpage\Models\Project;
use Modules\SeoOnpage\Models\Page;
use Modules\SeoOnpage\Models\Issue;

/**
 * IssueController
 * Gestisce visualizzazione e stato degli issues
 */
class IssueController
{
    private Project $project;
    private Page $page;
    private Issue $issue;

    public function __construct()
    {
        $this->project = new Project();
        $this->page = new Page();
        $this->issue = new Issue();
    }

    /**
     * Lista issues del progetto
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

        // Filtri
        $filters = [
            'severity' => $_GET['severity'] ?? null,
            'category' => $_GET['category'] ?? null,
            'status' => $_GET['status'] ?? 'open',
        ];

        // Issues raggruppati per check_name
        $issuesGrouped = $this->issue->getGroupedByProject($projectId, $filters);

        // Statistiche
        $stats = $this->issue->getProjectStats($projectId);

        return View::render('seo-onpage/issues/index', [
            'title' => 'Issues - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'issuesGrouped' => $issuesGrouped,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Aggiorna stato issue
     */
    public function updateStatus(int $projectId, int $issueId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['open', 'fixed', 'ignored'])) {
            echo json_encode(['success' => false, 'error' => 'Stato non valido']);
            return;
        }

        $this->issue->updateStatus($issueId, $status);

        echo json_encode(['success' => true, 'message' => 'Stato aggiornato']);
    }

    /**
     * Aggiorna stato bulk (per check_name)
     */
    public function bulkUpdate(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $checkName = $_POST['check_name'] ?? '';
        $status = $_POST['status'] ?? '';

        if (empty($checkName) || !in_array($status, ['open', 'fixed', 'ignored'])) {
            echo json_encode(['success' => false, 'error' => 'Parametri non validi']);
            return;
        }

        $updated = $this->issue->bulkUpdateByCheckName($projectId, $checkName, $status);

        echo json_encode([
            'success' => true,
            'message' => "{$updated} issues aggiornati",
            'updated' => $updated,
        ]);
    }
}
