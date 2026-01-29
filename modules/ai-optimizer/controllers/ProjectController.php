<?php

namespace Modules\AiOptimizer\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\AiOptimizer\Models\Project;
use Modules\AiOptimizer\Models\Optimization;

/**
 * Controller per gestione progetti AI Optimizer
 */
class ProjectController
{
    private Project $projectModel;
    private Optimization $optimizationModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->optimizationModel = new Optimization();
    }

    /**
     * Lista progetti (homepage modulo)
     */
    public function index(): void
    {
        Middleware::auth();
        $user = Auth::user();

        $projects = $this->projectModel->findByUser($user['id']);

        View::render('ai-optimizer::projects/index', [
            'projects' => $projects,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Form creazione progetto
     */
    public function create(): void
    {
        Middleware::auth();
        $user = Auth::user();

        View::render('ai-optimizer::projects/create', [
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Salva nuovo progetto
     */
    public function store(): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $language = $_POST['language'] ?? 'it';
        $locationCode = $_POST['location_code'] ?? 'IT';

        if (empty($name)) {
            $_SESSION['flash_error'] = 'Il nome del progetto è obbligatorio';
            header('Location: ' . url('/ai-optimizer/projects/create'));
            exit;
        }

        // Normalizza dominio (rimuovi protocollo se presente)
        if (!empty($domain)) {
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#^www\.#', '', $domain);
            $domain = rtrim($domain, '/');
        }

        $projectId = $this->projectModel->create([
            'user_id' => $user['id'],
            'name' => $name,
            'domain' => $domain ?: null,
            'description' => $description ?: null,
            'language' => $language,
            'location_code' => $locationCode,
        ]);

        $_SESSION['flash_success'] = 'Progetto creato con successo';
        header('Location: ' . url('/ai-optimizer/project/' . $projectId));
        exit;
    }

    /**
     * Dashboard progetto
     */
    public function show(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        $optimizations = $this->optimizationModel->findByProject($id, $user['id']);
        $stats = $this->optimizationModel->countByStatus($user['id'], $id);

        View::render('ai-optimizer::projects/dashboard', [
            'project' => $project,
            'projectId' => $id,
            'optimizations' => $optimizations,
            'stats' => $stats,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        View::render('ai-optimizer::projects/settings', [
            'project' => $project,
            'projectId' => $id,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $language = $_POST['language'] ?? 'it';
        $locationCode = $_POST['location_code'] ?? 'IT';

        if (empty($name)) {
            $_SESSION['flash_error'] = 'Il nome del progetto è obbligatorio';
            header('Location: ' . url('/ai-optimizer/project/' . $id . '/settings'));
            exit;
        }

        // Normalizza dominio
        if (!empty($domain)) {
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#^www\.#', '', $domain);
            $domain = rtrim($domain, '/');
        }

        $this->projectModel->update($id, [
            'name' => $name,
            'domain' => $domain ?: null,
            'description' => $description ?: null,
            'language' => $language,
            'location_code' => $locationCode,
        ]);

        $_SESSION['flash_success'] = 'Progetto aggiornato con successo';
        header('Location: ' . url('/ai-optimizer/project/' . $id . '/settings'));
        exit;
    }

    /**
     * Elimina progetto
     */
    public function delete(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        $this->projectModel->delete($id, $user['id']);

        $_SESSION['flash_success'] = 'Progetto eliminato con successo';
        header('Location: ' . url('/ai-optimizer'));
        exit;
    }
}
