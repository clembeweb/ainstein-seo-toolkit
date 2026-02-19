<?php

namespace Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Models\GlobalProject;

/**
 * GlobalProjectController
 * Gestisce CRUD progetti globali e attivazione moduli.
 */
class GlobalProjectController
{
    private GlobalProject $project;

    public function __construct()
    {
        $this->project = new GlobalProject();
    }

    /**
     * Lista progetti globali
     * GET /projects
     */
    public function index(): string
    {
        Middleware::auth();
        $user = Auth::user();

        $projects = $this->project->allWithModuleStats($user['id']);

        return View::render('projects/index', [
            'title' => 'Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
    }

    /**
     * Form creazione progetto
     * GET /projects/create
     */
    public function create(): string
    {
        Middleware::auth();
        $user = Auth::user();

        return View::render('projects/create', [
            'title' => 'Nuovo Progetto',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Salva nuovo progetto
     * POST /projects
     */
    public function store(): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? 'blue');

        // Validazione
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (strlen($name) > 255) {
            $errors[] = 'Il nome del progetto non può superare 255 caratteri';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/projects/create');
            return;
        }

        // Normalizza dominio se presente
        if (!empty($domain)) {
            $domain = rtrim($domain, '/');
            // Rimuovi protocollo se presente per uniformità
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');
        }

        try {
            $projectId = $this->project->create([
                'user_id' => $user['id'],
                'name' => $name,
                'domain' => $domain ?: null,
                'description' => $description ?: null,
                'color' => $color,
                'status' => 'active',
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo!';
            Router::redirect('/projects/' . $projectId);

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/projects/create');
        }
    }

    /**
     * Dashboard progetto globale
     * GET /projects/{id}
     */
    public function dashboard(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return '';
        }

        $activeModules = $this->project->getActiveModules($id);
        $moduleStats = $this->project->getModuleStats($id);
        $availableModules = ModuleLoader::getActiveModules();
        $moduleConfig = $this->project->getModuleConfig();

        $moduleTypes = $this->project->getModuleTypes();

        return View::render('projects/dashboard', [
            'title' => $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'activeModules' => $activeModules,
            'moduleStats' => $moduleStats,
            'availableModules' => $availableModules,
            'moduleConfig' => $moduleConfig,
            'moduleTypes' => $moduleTypes,
        ]);
    }

    /**
     * Impostazioni progetto
     * GET /projects/{id}/settings
     */
    public function settings(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return '';
        }

        return View::render('projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
        ]);
    }

    /**
     * Aggiorna progetto
     * POST /projects/{id}/settings
     */
    public function update(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? $project['color'] ?? 'blue');

        // Validazione
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (strlen($name) > 255) {
            $errors[] = 'Il nome del progetto non può superare 255 caratteri';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/projects/' . $id . '/settings');
            return;
        }

        // Normalizza dominio se presente
        if (!empty($domain)) {
            $domain = rtrim($domain, '/');
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'domain' => $domain ?: null,
                'description' => $description ?: null,
                'color' => $color,
            ], $user['id']);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/projects/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/projects/' . $id . '/settings');
        }
    }

    /**
     * Attiva modulo per progetto globale
     * POST /projects/{id}/activate-module
     */
    public function activateModule(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return;
        }

        $moduleSlug = trim($_POST['module'] ?? '');
        $moduleConfig = $this->project->getModuleConfig();

        // Verifica che il modulo sia valido
        if (empty($moduleSlug) || !isset($moduleConfig[$moduleSlug])) {
            $_SESSION['_flash']['error'] = 'Modulo non valido';
            Router::redirect('/projects/' . $id);
            return;
        }

        // Verifica se il modulo è già attivo per questo progetto
        $activeModules = $this->project->getActiveModules($id);
        foreach ($activeModules as $active) {
            if ($active['slug'] === $moduleSlug) {
                // Già attivo, redirect alla pagina del modulo
                $routePrefix = $moduleConfig[$moduleSlug]['route_prefix'];
                Router::redirect($routePrefix . '/' . $active['module_project_id']);
                return;
            }
        }

        // Prepara dati extra (tipo per moduli tipizzati)
        $extraData = [];
        $type = trim($_POST['type'] ?? '');
        if (!empty($type)) {
            $extraData['type'] = $type;
        }

        // Attiva il modulo
        $moduleProjectId = $this->project->activateModule($id, $moduleSlug, $user['id'], $extraData);

        if ($moduleProjectId) {
            $_SESSION['_flash']['success'] = $moduleConfig[$moduleSlug]['label'] . ' attivato con successo!';
            $routePrefix = $moduleConfig[$moduleSlug]['route_prefix'];
            Router::redirect($routePrefix . '/' . $moduleProjectId);
        } else {
            $_SESSION['_flash']['error'] = 'Errore nell\'attivazione del modulo';
            Router::redirect('/projects/' . $id);
        }
    }

    /**
     * Elimina progetto
     * POST /projects/{id}/delete
     */
    public function destroy(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/projects');
            return;
        }

        try {
            $this->project->delete($id, $user['id']);

            $_SESSION['_flash']['success'] = 'Progetto eliminato con successo';
            Router::redirect('/projects');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
            Router::redirect('/projects/' . $id . '/settings');
        }
    }
}
