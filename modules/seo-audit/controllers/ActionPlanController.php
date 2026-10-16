<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
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
        // Pagine tra cui scegliere (solo se non c'è un piano pronto)
        $selectablePages = (!$plan || $plan['status'] === 'generating')
            ? $this->actionPlanService->getSelectablePages($id)
            : [];

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
            'currentPage' => 'action-plan',
            'plan' => $plan,
            'pagesWithFixes' => $pagesWithFixes,
            'issueStats' => $issueStats,
            'selectablePages' => $selectablePages,
            'planLimits' => [
                'max' => ActionPlanService::MAX_PAGES,
                'default' => ActionPlanService::DEFAULT_PAGES,
                'seconds_per_page' => ActionPlanService::SECONDS_PER_PAGE,
            ],
            'credits' => [
                'balance' => $creditBalance,
                'generate_cost' => $generateCost,
            ],
        ]);
    }

    /**
     * Crea il piano con le pagine scelte (POST AJAX). Il lavoro vero lo fa stream(), una pagina alla volta.
     * Prima era una sola richiesta con una chiamata AI per OGNI pagina: 12 pagine = 5 minuti senza avanzamento.
     */
    public function generate(int $id): void
    {
        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            if (ob_get_level()) ob_end_clean();
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        $issueCounts = $this->issueModel->countBySeverity($id);
        if ($issueCounts['total'] === 0) {
            if (ob_get_level()) ob_end_clean();
            echo json_encode(['error' => true, 'message' => 'Nessun problema rilevato: non c\'è niente da correggere.']);
            exit;
        }

        // Prezzo unico, quello mostrato in pagina: addebitato a fine generazione (stream)
        $cost = $this->actionPlanService->getCost('action_plan_generate');
        if (!Credits::hasEnough($user['id'], $cost)) {
            if (ob_get_level()) ob_end_clean();
            echo json_encode(['error' => true, 'message' => "Crediti insufficienti: servono {$cost} crediti."]);
            exit;
        }

        $pageIds = array_map('intval', (array) ($_POST['page_ids'] ?? []));
        $sessionId = $project['current_session_id'] ?? null;
        $result = $this->actionPlanService->startPlan($id, $sessionId, $user['id'], $pageIds);

        if (ob_get_level()) ob_end_clean();
        echo json_encode($result);
        exit;
    }

    /**
     * Genera le correzioni pagina per pagina (GET SSE ?plan_id=X).
     * Eventi: started, page_started, page_completed, completed, error. Riprende da dove era rimasto.
     */
    public function stream(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(300);

        $user = Auth::user();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }
        $project = $this->projectModel->findWithStats($id, $user['id']);
        $planId = (int) ($_GET['plan_id'] ?? 0);
        if (!$project || !$planId) {
            header('HTTP/1.1 404 Not Found');
            exit('Piano non trovato');
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        session_write_close();
        while (ob_get_level()) {
            ob_end_flush();
        }

        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level()) ob_flush();
            flush();
        };

        // Un solo lavoratore per piano: una seconda scheda (o una riconnessione) guarda e basta
        $lock = fopen(sys_get_temp_dir() . "/ainstein-sa-plan-{$planId}.lock", 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->watchPlan($planId, $id, $sendEvent);
            exit;
        }

        $result = $this->actionPlanService->processPlan($planId, $id, $user['id'], $sendEvent);
        Database::reconnect();

        if (!empty($result['error'])) {
            $sendEvent('error', ['message' => $result['message']]);
        } else {
            // Addebito una volta sola: lo fa chi ha chiuso il piano
            if (!empty($result['finalized'])) {
                $cost = $this->actionPlanService->getCost('action_plan_generate');
                Credits::consume($user['id'], $cost, 'action_plan_generate', 'seo-audit', ['project_id' => $id, 'plan_id' => $planId]);
            }
            $sendEvent('completed', $result);
        }

        flock($lock, LOCK_UN);
        fclose($lock);
        exit;
    }

    /**
     * Avanzamento di un piano che sta lavorando un'altra richiesta: legge il DB ogni 3 s.
     */
    private function watchPlan(int $planId, int $projectId, callable $sendEvent): void
    {
        $last = -1;
        for ($i = 0; $i < 400; $i++) {
            if (connection_aborted()) {
                return;
            }
            Database::reconnect();
            $plan = Database::fetch("SELECT status, pages_done, total_pages FROM sa_action_plans WHERE id = ? AND project_id = ?", [$planId, $projectId]);
            if (!$plan) {
                $sendEvent('error', ['message' => 'Il piano non c\'è più: riprova a generarlo.']);
                return;
            }
            if ((int) $plan['pages_done'] !== $last) {
                $last = (int) $plan['pages_done'];
                $sendEvent('page_completed', ['index' => $last, 'total' => (int) $plan['total_pages'], 'url' => '', 'fixes' => null, 'error' => null]);
            }
            if ($plan['status'] !== 'generating') {
                $sendEvent('completed', ['success' => true, 'plan_id' => $planId]);
                return;
            }
            sleep(3);
        }
    }

    /**
     * Toggle fix completato (POST AJAX)
     */
    public function toggleFix(int $id, int $fixId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
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
        $project = $this->projectModel->findAccessible($id, $user['id']);

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
        $project = $this->projectModel->findAccessible($id, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
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
        $project = $this->projectModel->findAccessible($id, $user['id']);

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
