<?php

namespace Modules\AiReputation\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Models\Prompt;
use Modules\AiReputation\Models\ProfileFact;
use Modules\AiReputation\Models\Run;
use Core\Credits;

/**
 * DashboardController - overview del progetto (prompt, run, costi)
 */
class DashboardController
{
    private Project $project;
    private Prompt $prompt;

    public function __construct()
    {
        $this->project = new Project();
        $this->prompt = new Prompt();
    }

    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-reputation');
            exit;
        }

        $prompts = $this->prompt->allByProject($id);
        $promptsActive = count(array_filter($prompts, fn($p) => (int) $p['is_active'] === 1));

        $runs = Database::fetchAll(
            "SELECT * FROM ar_runs WHERE project_id = ? ORDER BY created_at DESC LIMIT 10",
            [$id]
        );
        $runsCompleted = count(array_filter($runs, fn($r) => $r['status'] === 'completed'));
        $costTotal = (float) Database::fetchColumn(
            "SELECT COALESCE(SUM(cost_total), 0) FROM ar_runs WHERE project_id = ?",
            [$id]
        );
        $lastRun = $runs[0] ?? null;
        $factCounts = (new ProfileFact())->counts($id);
        $profileConfirmed = ($factCounts['confirmed'] + $factCounts['corrected']) > 0;
        $lastCompletedRun = array_values(array_filter($runs, fn($r) => $r['status'] === 'completed'))[0] ?? null;
        $creditsTotal = (float) Database::fetchColumn("SELECT COALESCE(SUM(credits_used), 0) FROM ar_runs WHERE project_id = ?", [$id]);

        // Stima del prossimo run, mostrata prima di "Avvia run": stesso calcolo di RunController::start()
        $auditType = (string) ($project['audit_type'] ?? 'reputation');
        $activePrompts = $this->prompt->forAudit($id, $auditType);
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
        $runEstimate = Run::estimate($project, $activePrompts, $creditUserId);
        // AI davvero interrogate: quelle scelte nel progetto che hanno la chiave API (le altre vengono saltate nel run)
        $enginesReady = array_values(array_filter($project['engines'], fn($e) => \Modules\AiReputation\Services\EngineCollectorService::isConfigured($e)));
        $enginesMissing = array_values(array_diff($project['engines'], $enginesReady));

        return View::render('ai-reputation::dashboard/index', [
            'title' => $project['name'] . ' - AI GEO Audit',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'prompts' => $prompts,
            'promptsActive' => $promptsActive,
            'clusters' => Prompt::CLUSTERS,
            'engineLabels' => Project::ENGINE_LABELS,
            'runs' => $runs,
            'runsCompleted' => $runsCompleted,
            'costTotal' => $costTotal,
            'creditsTotal' => $creditsTotal,
            'runEstimate' => $runEstimate,
            'enginesReady' => $enginesReady,
            'enginesMissing' => $enginesMissing,
            'auditType' => $auditType,
            'auditSets' => Prompt::auditSets($auditType),
            'lastRun' => $lastRun,
            'profileConfirmed' => $profileConfirmed,
            'lastCompletedRun' => $lastCompletedRun,
        ]);
    }
}
