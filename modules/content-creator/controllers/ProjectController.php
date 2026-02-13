<?php

namespace Modules\ContentCreator\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Url;
use Modules\ContentCreator\Models\Connector;

class ProjectController
{
    private Project $project;

    public function __construct()
    {
        $this->project = new Project();
    }

    /**
     * Lista progetti
     */
    public function index(): string
    {
        $user = Auth::user();
        $projects = $this->project->allWithStats($user['id']);

        return View::render('content-creator/projects/index', [
            'title' => 'Content Creator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
    }

    /**
     * Form creazione progetto
     */
    public function create(): string
    {
        $user = Auth::user();
        $connectorModel = new Connector();
        $connectors = $connectorModel->getActive($user['id']);

        return View::render('content-creator/projects/create', [
            'title' => 'Nuovo Progetto - Content Creator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'connectors' => $connectors,
        ]);
    }

    /**
     * Salva nuovo progetto
     */
    public function store(): void
    {
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $baseUrl = trim($_POST['base_url'] ?? '');
        $contentType = trim($_POST['content_type'] ?? 'product');
        $language = trim($_POST['language'] ?? 'it');
        $tone = trim($_POST['tone'] ?? 'professionale');
        $connectorId = !empty($_POST['connector_id']) ? (int) $_POST['connector_id'] : null;

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }
        if (strlen($name) > 255) {
            $errors[] = 'Il nome non può superare 255 caratteri';
        }
        if (!in_array($contentType, ['product', 'category', 'article', 'custom'])) {
            $contentType = 'product';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/content-creator/projects/create');
            return;
        }

        // Validate connector belongs to user
        if ($connectorId) {
            $connectorModel = new Connector();
            $connector = $connectorModel->findByUser($connectorId, $user['id']);
            if (!$connector) {
                $connectorId = null;
            }
        }

        try {
            $projectId = $this->project->create([
                'user_id' => $user['id'],
                'name' => $name,
                'description' => $description ?: null,
                'base_url' => $baseUrl ?: null,
                'content_type' => $contentType,
                'language' => $language,
                'tone' => $tone,
                'connector_id' => $connectorId,
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo!';
            Router::redirect('/content-creator/projects/' . $projectId);

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/content-creator/projects/create');
        }
    }

    /**
     * Dashboard progetto
     */
    public function show(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/content-creator');
            exit;
        }

        $stats = $this->project->getStats($id);
        $urlModel = new Url();
        $recentUrls = $urlModel->getByProject($id, null, 20);

        return View::render('content-creator/projects/show', [
            'title' => $project['name'] . ' - Content Creator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'stats' => $stats,
            'recentUrls' => $recentUrls,
            'currentPage' => 'dashboard',
        ]);
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/content-creator');
            exit;
        }

        $connectorModel = new Connector();
        $connectors = $connectorModel->getActive($user['id']);

        // Decode ai_settings JSON
        $aiSettings = !empty($project['ai_settings']) ? json_decode($project['ai_settings'], true) : [];

        return View::render('content-creator/projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'connectors' => $connectors,
            'aiSettings' => $aiSettings,
            'currentPage' => 'settings',
        ]);
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/content-creator');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $baseUrl = trim($_POST['base_url'] ?? '');
        $contentType = trim($_POST['content_type'] ?? 'product');
        $language = trim($_POST['language'] ?? 'it');
        $tone = trim($_POST['tone'] ?? 'professionale');
        $connectorId = !empty($_POST['connector_id']) ? (int) $_POST['connector_id'] : null;

        $errors = [];
        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/content-creator/projects/' . $id . '/settings');
            return;
        }

        // AI settings from form
        $aiSettings = [
            'min_words' => (int) ($_POST['min_words'] ?? 300),
            'custom_prompt' => trim($_POST['custom_prompt'] ?? ''),
        ];

        // Validate connector
        if ($connectorId) {
            $connectorModel = new Connector();
            if (!$connectorModel->findByUser($connectorId, $user['id'])) {
                $connectorId = null;
            }
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'description' => $description ?: null,
                'base_url' => $baseUrl ?: null,
                'content_type' => $contentType,
                'language' => $language,
                'tone' => $tone,
                'connector_id' => $connectorId,
                'ai_settings' => json_encode($aiSettings),
            ]);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/content-creator/projects/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/content-creator/projects/' . $id . '/settings');
        }
    }

    /**
     * Elimina progetto
     */
    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/content-creator');
            return;
        }

        try {
            $this->project->delete($id);

            $_SESSION['_flash']['success'] = 'Progetto eliminato con successo';
            Router::redirect('/content-creator');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
            Router::redirect('/content-creator/projects/' . $id . '/settings');
        }
    }
}
