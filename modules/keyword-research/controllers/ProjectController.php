<?php

namespace Modules\KeywordResearch\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\ModuleLoader;
use Modules\KeywordResearch\Models\Project;

class ProjectController
{
    private Project $projectModel;

    public function __construct()
    {
        $this->projectModel = new Project();
    }

    public function index(): string
    {
        $user = Auth::user();
        $projects = $this->projectModel->allWithStats($user['id']);

        return View::render('keyword-research::projects/index', [
            'title' => 'Keyword Research - Progetti',
            'user' => $user,
            'projects' => $projects,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    public function create(): string
    {
        $user = Auth::user();

        return View::render('keyword-research::projects/create', [
            'title' => 'Nuovo Progetto - Keyword Research',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    public function store(): void
    {
        $user = Auth::user();
        $userId = $user['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['default_location'] ?? 'IT');
        $language = trim($_POST['default_language'] ?? 'it');

        if (empty($name)) {
            $_SESSION['_flash']['error'] = 'Il nome del progetto è obbligatorio.';
            Router::redirect('/keyword-research/projects/create');
            return;
        }

        $projectId = $this->projectModel->create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description ?: null,
            'default_location' => $location,
            'default_language' => $language,
        ]);

        $_SESSION['_flash']['success'] = 'Progetto creato con successo.';
        Router::redirect('/keyword-research/project/' . $projectId . '/research');
    }

    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            exit;
        }

        // Statistiche progetto
        $stats = Database::fetch("
            SELECT
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = ? AND status = 'completed') as researches_count,
                (SELECT SUM(filtered_keywords_count) FROM kr_researches WHERE project_id = ? AND status = 'completed') as total_keywords,
                (SELECT COUNT(*) FROM kr_clusters c JOIN kr_researches r ON c.research_id = r.id WHERE r.project_id = ?) as total_clusters
        ", [$id, $id, $id]);

        return View::render('keyword-research::projects/settings', [
            'title' => 'Impostazioni - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'stats' => $stats,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    public function updateSettings(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['_flash']['error'] = 'Il nome del progetto è obbligatorio.';
            Router::redirect('/keyword-research/project/' . $id . '/settings');
            return;
        }

        $this->projectModel->update($id, [
            'name' => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'default_location' => trim($_POST['default_location'] ?? 'IT'),
            'default_language' => trim($_POST['default_language'] ?? 'it'),
        ]);

        $_SESSION['_flash']['success'] = 'Impostazioni aggiornate.';
        Router::redirect('/keyword-research/project/' . $id . '/settings');
    }

    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            return;
        }

        $this->projectModel->delete($id);

        $_SESSION['_flash']['success'] = 'Progetto eliminato.';
        Router::redirect('/keyword-research/projects');
    }
}
