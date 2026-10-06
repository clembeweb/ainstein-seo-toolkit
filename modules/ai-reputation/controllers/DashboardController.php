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

        return View::render('ai-reputation::dashboard/index', [
            'title' => $project['name'] . ' - AI Reputation Radar',
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
            'lastRun' => $lastRun,
            'profileConfirmed' => $profileConfirmed,
            'lastCompletedRun' => $lastCompletedRun,
        ]);
    }
}
