<?php

namespace Modules\AiReputation\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\AiReputation\Models\Project;

/**
 * ProjectController - lista, impostazioni, eliminazione progetti AI Reputation Radar
 */
class ProjectController
{
    private Project $project;

    public function __construct()
    {
        $this->project = new Project();
    }

    public function index(): string
    {
        $user = Auth::user();
        $projects = $this->project->allWithStats($user['id']);

        return View::render('ai-reputation::projects/index', [
            'title' => 'AI Reputation Radar',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
    }

    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-reputation');
            exit;
        }

        return View::render('ai-reputation::projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'engineLabels' => Project::ENGINE_LABELS,
            'availableEngines' => Project::defaultEngines(),
        ]);
    }

    public function updateSettings(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project || ($project['access_role'] ?? 'owner') === 'viewer') {
            $_SESSION['_flash']['error'] = 'Progetto non trovato o permessi insufficienti';
            Router::redirect('/ai-reputation');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $subjectName = trim($_POST['subject_name'] ?? '');
        $subjectType = ($_POST['subject_type'] ?? 'person') === 'company' ? 'company' : 'person';
        $website = trim($_POST['website'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $notes = trim($_POST['disambiguation_notes'] ?? '');
        $repeats = max(1, min(5, (int) ($_POST['repeats'] ?? 1)));
        $engines = array_values(array_intersect(Project::ENGINES, (array) ($_POST['engines'] ?? [])));

        if ($name === '' || $subjectName === '') {
            $_SESSION['_flash']['error'] = 'Nome progetto e nome del soggetto sono obbligatori';
            Router::redirect("/ai-reputation/project/{$id}/settings");
            return;
        }
        if (empty($engines)) {
            $_SESSION['_flash']['error'] = 'Seleziona almeno un engine';
            Router::redirect("/ai-reputation/project/{$id}/settings");
            return;
        }

        $this->project->update($id, [
            'name' => $name,
            'subject_name' => $subjectName,
            'subject_type' => $subjectType,
            'website' => $website ?: null,
            'city' => $city ?: null,
            'disambiguation_notes' => $notes ?: null,
            'repeats' => $repeats,
            'engines' => $engines,
        ]);

        $_SESSION['_flash']['success'] = 'Impostazioni salvate';
        Router::redirect("/ai-reputation/project/{$id}/settings");
    }

    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Solo il proprietario può eliminare il progetto';
            Router::redirect('/ai-reputation');
            return;
        }

        $this->project->delete($id);
        $_SESSION['_flash']['success'] = 'Progetto eliminato';
        Router::redirect('/ai-reputation');
    }
}
