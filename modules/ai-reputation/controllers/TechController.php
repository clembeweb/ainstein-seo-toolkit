<?php

namespace Modules\AiReputation\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Services\GeoTechService;

/**
 * TechController - "Controllo tecnico" dell'AI GEO Audit: le AI entrano nel sito, lo leggono, capiscono chi sei?
 * Gratis (nessuna chiamata AI). Spec: docs/superpowers/specs/2026-10-13-ai-reputation-diagnosi-tecnica-design.md
 */
class TechController
{
    private Project $project;

    public function __construct()
    {
        $this->project = new Project();
    }

    /** GET /ai-reputation/project/{id}/tech */
    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-reputation');
            exit;
        }
        $check = GeoTechService::latest($id);

        return View::render('ai-reputation::tech/index', [
            'title' => $project['name'] . ' - Controllo tecnico GEO',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'check' => $check,
            'findings' => $check ? GeoTechService::findings($check, (string) ($project['subject_type'] ?? 'company')) : [],
            'bots' => GeoTechService::BOTS,
            'answerBots' => GeoTechService::ANSWER_BOTS,
        ]);
    }

    /** POST /ai-reputation/project/{id}/tech/run (JSON) - 5-30 s: pattern AJAX lungo */
    public function run(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(120);
        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato o permessi insufficienti']);
            exit;
        }
        $website = trim((string) ($project['website'] ?? ''));
        if ($website === '' || !ProjectController::isPublicWebUrl($website)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Indica il sito del progetto in Impostazioni (indirizzo web pubblico)']);
            exit;
        }
        // Un controllo al minuto per progetto: sono richieste vere al sito del cliente
        $last = Database::fetchColumn("SELECT created_at FROM ar_tech_checks WHERE project_id = ? ORDER BY id DESC LIMIT 1", [$id]);
        if ($last && strtotime((string) $last) > time() - 60) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Controllo appena fatto: riprova tra un minuto']);
            exit;
        }
        session_write_close();

        try {
            $service = new GeoTechService();
            $result = $service->check($website);
            Database::reconnect();
            $service->save($id, null, $result);
        } catch (\Throwable $e) {
            error_log('[ai-reputation] controllo tecnico progetto ' . $id . ': ' . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Controllo non riuscito, riprova tra poco']);
            exit;
        }
        ob_end_clean();
        echo json_encode(['success' => true]);
        exit;
    }
}
