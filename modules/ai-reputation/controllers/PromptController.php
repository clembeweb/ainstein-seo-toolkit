<?php

namespace Modules\AiReputation\Controllers;

use Core\Auth;
use Core\Router;
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

        $this->prompt->create($id, $text, $cluster);
        $_SESSION['_flash']['success'] = 'Domanda aggiunta';
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
        $this->prompt->delete($promptId, $id);
        $_SESSION['_flash']['success'] = 'Domanda eliminata';
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
        $target = max(10, min(60, (int) ($_POST['target'] ?? 40)));
        session_write_close();

        try {
            $result = (new \Modules\AiReputation\Services\PromptEngineService())->generate($creditUserId, $project, $target);
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
