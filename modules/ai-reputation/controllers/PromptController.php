<?php

namespace Modules\AiReputation\Controllers;

use Core\Auth;
use Core\Router;
use Core\Database;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Models\Prompt;

/**
 * PromptController - gestione manuale delle domande monitorate
 */
class PromptController
{
    private Project $project;
    private Prompt $prompt;

    public function __construct()
    {
        $this->project = new Project();
        $this->prompt = new Prompt();
    }

    /** La lista prompt vive nella dashboard: redirect */
    public function index(int $id): void
    {
        Router::redirect("/ai-reputation/project/{$id}#prompts");
    }

    public function store(int $id): void
    {
        $project = $this->requireEditable($id);

        $text = trim($_POST['text'] ?? '');
        $cluster = $_POST['cluster'] ?? 'nav';

        if (mb_strlen($text) < 5) {
            $_SESSION['_flash']['error'] = 'Scrivi una domanda completa';
            Router::redirect("/ai-reputation/project/{$id}#prompts");
            return;
        }

        $set = $_POST['audit_set'] ?? (($project['audit_type'] ?? 'reputation') === 'reputation' ? 'reputation' : 'purchase');
        $this->prompt->create($id, $text, $cluster, 'it', 'manual', null, $set === 'purchase' ? 'purchase' : 'reputation');
        $_SESSION['_flash']['success'] = 'Domanda aggiunta';
        Router::redirect("/ai-reputation/project/{$id}#prompts");
    }

    /**
     * POST /ai-reputation/project/{id}/prompts/bulk (JSON) - azioni su più domande: activate | deactivate | delete | only
     * ("only" = attiva le selezionate e disattiva le altre dello stesso audit). Sempre filtrato per project_id (IDOR).
     */
    public function bulk(int $id): void
    {
        header('Content-Type: application/json');
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato o permessi insufficienti']);
            exit;
        }
        $action = $_POST['action'] ?? '';
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))));
        if (!$ids || !in_array($action, ['activate', 'deactivate', 'delete', 'only'], true)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna domanda selezionata']);
            exit;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$id], $ids);
        if ($action === 'activate' || $action === 'deactivate') {
            Database::execute("UPDATE ar_prompts SET is_active = ? WHERE project_id = ? AND id IN ({$in})", array_merge([$action === 'activate' ? 1 : 0], $params));
        } elseif ($action === 'only') {
            $sets = Prompt::auditSets((string) ($project['audit_type'] ?? 'reputation'));
            $inSets = implode(',', array_fill(0, count($sets), '?'));
            Database::execute("UPDATE ar_prompts SET is_active = 0 WHERE project_id = ? AND audit_set IN ({$inSets})", array_merge([$id], $sets));
            Database::execute("UPDATE ar_prompts SET is_active = 1 WHERE project_id = ? AND id IN ({$in})", $params);
        } else {
            // Le domande usate in un run passato si disattivano (cancellarle riscriverebbe i report storici)
            $used = array_map('intval', array_column(Database::fetchAll("SELECT DISTINCT prompt_id FROM ar_responses WHERE project_id = ? AND prompt_id IN ({$in})", $params), 'prompt_id'));
            $free = array_values(array_diff($ids, $used));
            if ($used) {
                $inU = implode(',', array_fill(0, count($used), '?'));
                Database::execute("UPDATE ar_prompts SET is_active = 0 WHERE project_id = ? AND id IN ({$inU})", array_merge([$id], $used));
            }
            if ($free) {
                $inF = implode(',', array_fill(0, count($free), '?'));
                Database::execute("DELETE FROM ar_prompts WHERE project_id = ? AND id IN ({$inF})", array_merge([$id], $free));
            }
            $_SESSION['_flash']['success'] = count($free) . ' domande eliminate' . ($used ? ', ' . count($used) . ' disattivate (usate in run passati)' : '');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    /** POST /ai-reputation/project/{id}/audit-type - tipo di audit del progetto (acquisto, reputazione, entrambi) */
    public function auditType(int $id): void
    {
        $this->requireEditable($id);
        $type = $_POST['audit_type'] ?? '';
        if (!isset(Prompt::AUDIT_TYPES[$type])) {
            $_SESSION['_flash']['error'] = 'Tipo di audit non valido';
            Router::redirect("/ai-reputation/project/{$id}#prompts");
            return;
        }
        $this->project->update($id, ['audit_type' => $type]);
        $_SESSION['_flash']['success'] = 'Audit: ' . Prompt::AUDIT_TYPES[$type];
        Router::redirect("/ai-reputation/project/{$id}#prompts");
    }

    public function seed(int $id): void
    {
        $project = $this->requireEditable($id);
        $added = $this->prompt->seed($id, $project['subject_name']);
        $_SESSION['_flash']['success'] = $added > 0
            ? "Aggiunte {$added} domande base"
            : 'Le domande base erano già presenti';
        Router::redirect("/ai-reputation/project/{$id}#prompts");
    }

    public function toggle(int $id, int $promptId): void
    {
        $this->requireEditable($id);
        $this->prompt->toggle($promptId, $id);
        Router::redirect("/ai-reputation/project/{$id}#prompts");
    }

    public function destroy(int $id, int $promptId): void
    {
        $this->requireEditable($id);
        $used = (int) \Core\Database::fetchColumn("SELECT COUNT(*) FROM ar_responses WHERE prompt_id = ? AND project_id = ?", [$promptId, $id]);
        if ($used > 0) {
            // Ha risposte in run passati: cancellarla riscriverebbe i report storici. Si disattiva.
            \Core\Database::update('ar_prompts', ['is_active' => 0], 'id = ? AND project_id = ?', [$promptId, $id]);
            $_SESSION['_flash']['success'] = 'Domanda disattivata: compare nei report passati, quindi non viene cancellata';
        } else {
            $this->prompt->delete($promptId, $id);
            $_SESSION['_flash']['success'] = 'Domanda eliminata';
        }
        Router::redirect("/ai-reputation/project/{$id}#prompts");
    }

    /**
     * POST /ai-reputation/project/{id}/prompts/generate (AJAX lungo, JSON) - prompt engine
     */
    public function generate(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(300);
        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato o permessi insufficienti']);
            exit;
        }
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        // Tipo di domande: quello chiesto dal bottone, altrimenti quello dell'audit del progetto ("both" → acquisto)
        $set = $_POST['set'] ?? (($project['audit_type'] ?? 'reputation') === 'reputation' ? 'reputation' : 'purchase');
        $set = $set === 'purchase' ? 'purchase' : 'reputation';
        $target = max(10, min(60, (int) ($_POST['target'] ?? ($set === 'purchase' ? 30 : 40))));
        session_write_close();

        try {
            $result = (new \Modules\AiReputation\Services\PromptEngineService())->generate($creditUserId, $project, $target, $set);
        } catch (\Throwable $e) {
            $result = ['success' => false, 'error' => 'Errore interno: ' . $e->getMessage()];
        }
        \Core\Database::reconnect();

        ob_end_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireEditable(int $id): array
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            $_SESSION['_flash']['error'] = 'Progetto non trovato o permessi insufficienti';
            Router::redirect('/ai-reputation');
            exit;
        }
        return $project;
    }
}
