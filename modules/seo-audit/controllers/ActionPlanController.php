<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Services\ActionPlanService;

/**
 * ActionPlanController
 *
 * Gestisce il Piano d'Azione AI con fix raggruppati per pagina
 */
class ActionPlanController
{
    private Project $projectModel;
    private Issue $issueModel;
    private ActionPlanService $actionPlanService;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->issueModel = new Issue();
        $this->actionPlanService = new ActionPlanService();
    }

    /**
     * Vista principale Piano d'Azione
     */
    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            exit;
        }

        // Ottieni piano esistente
        $plan = $this->actionPlanService->getPlan($id);

        // Stats per preview (se piano non esiste)
        $issueStats = $this->actionPlanService->getIssueStats($id);

        // Costi e crediti
        $generateCost = $this->actionPlanService->getCost('action_plan_generate');
        $creditBalance = Credits::getBalance($user['id']);

        // Se piano esiste, recupera fix per ogni pagina
        $pagesWithFixes = [];
        if ($plan) {
            foreach ($plan['pages'] as $pageData) {
                $pagesWithFixes[$pageData['page_id']] = $this->actionPlanService->getPageFixes(
                    $plan['id'],
                    $pageData['page_id']
                );
            }
        }

        return View::render('seo-audit/audit/action-plan', [
            'title' => $project['name'] . ' - Piano d\'Azione',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'plan' => $plan,
            'pagesWithFixes' => $pagesWithFixes,
            'issueStats' => $issueStats,
            'credits' => [
                'balance' => $creditBalance,
                'generate_cost' => $generateCost,
            ],
        ]);
    }

    /**
     * Genera piano (POST AJAX)
     */
    public function generate(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Verifica CSRF
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['_token']) || $input['_token'] !== csrf_token()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Token CSRF non valido']);
            exit;
        }

        // Verifica che ci siano issues
        $issueCounts = $this->issueModel->countBySeverity($id);
        if ($issueCounts['total'] === 0) {
            echo json_encode([
                'error' => true,
                'message' => 'Nessun problema rilevato. Health Score: 100!'
            ]);
            exit;
        }

        // Genera piano
        $sessionId = $project['current_session_id'] ?? null;
        $result = $this->actionPlanService->generatePlan($id, $sessionId, $user['id']);

        echo json_encode($result);
        exit;
    }

    /**
     * Toggle fix completato (POST AJAX)
     */
    public function toggleFix(int $id, int $fixId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Verifica CSRF
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['_token']) || $input['_token'] !== csrf_token()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Token CSRF non valido']);
            exit;
        }

        $result = $this->actionPlanService->toggleFixComplete($fixId);

        echo json_encode($result);
        exit;
    }

    /**
     * Export To-Do List (GET)
     */
    public function export(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            exit;
        }

        $markdown = $this->actionPlanService->exportMarkdown($id);

        if (!$markdown) {
            $_SESSION['_flash']['error'] = 'Nessun piano d\'azione da esportare';
            Router::redirect('/seo-audit/project/' . $id . '/action-plan');
            exit;
        }

        // Generate filename
        $domain = parse_url($project['base_url'], PHP_URL_HOST) ?: 'sito';
        $date = date('Y-m-d');
        $filename = "piano-azione-seo-{$domain}-{$date}.md";

        // Send as download
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($markdown));

        echo $markdown;
        exit;
    }

    /**
     * Elimina piano esistente (POST AJAX)
     */
    public function delete(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Verifica CSRF
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['_token']) || $input['_token'] !== csrf_token()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Token CSRF non valido']);
            exit;
        }

        $plan = $this->actionPlanService->getPlan($id);
        if (!$plan) {
            echo json_encode(['error' => true, 'message' => 'Nessun piano da eliminare']);
            exit;
        }

        $deleted = $this->actionPlanService->deletePlan($plan['id'], $id);

        echo json_encode([
            'success' => $deleted,
            'message' => $deleted ? 'Piano eliminato' : 'Errore durante l\'eliminazione',
        ]);
        exit;
    }

    /**
     * API: Ottieni fix per una pagina (GET AJAX)
     */
    public function getPageFixes(int $id, int $pageId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        $plan = $this->actionPlanService->getPlan($id);
        if (!$plan) {
            echo json_encode(['error' => true, 'message' => 'Nessun piano esistente']);
            exit;
        }

        $fixes = $this->actionPlanService->getPageFixes($plan['id'], $pageId);

        echo json_encode([
            'success' => true,
            'fixes' => $fixes,
        ]);
        exit;
    }
}
