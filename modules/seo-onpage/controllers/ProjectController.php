<?php

namespace Modules\SeoOnpage\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoOnpage\Models\Project;

/**
 * ProjectController
 * Gestisce CRUD progetti SEO Onpage Optimizer
 */
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

        return View::render('seo-onpage/projects/index', [
            'title' => 'AI SEO Onpage Optimizer',
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

        return View::render('seo-onpage/projects/create', [
            'title' => 'Nuovo Progetto - SEO Onpage',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Salva nuovo progetto
     */
    public function store(): void
    {
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $defaultDevice = $_POST['default_device'] ?? 'desktop';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (empty($domain)) {
            $errors[] = 'Il dominio è obbligatorio';
        }

        if (!in_array($defaultDevice, ['desktop', 'mobile'])) {
            $defaultDevice = 'desktop';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/seo-onpage/projects/create');
            return;
        }

        try {
            // Normalizza dominio
            $domain = $this->normalizeDomain($domain);

            $projectId = $this->project->create([
                'user_id' => $user['id'],
                'name' => $name,
                'domain' => $domain,
                'default_device' => $defaultDevice,
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo! Ora importa le pagine da analizzare.';
            Router::redirect('/seo-onpage/project/' . $projectId . '/pages/import');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/seo-onpage/projects/create');
        }
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        return View::render('seo-onpage/projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
        ]);
    }

    /**
     * Aggiorna impostazioni progetto
     */
    public function updateSettings(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $defaultDevice = $_POST['default_device'] ?? 'desktop';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (empty($domain)) {
            $errors[] = 'Il dominio è obbligatorio';
        }

        if (!in_array($defaultDevice, ['desktop', 'mobile'])) {
            $defaultDevice = 'desktop';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/seo-onpage/project/' . $id . '/settings');
            return;
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'domain' => $this->normalizeDomain($domain),
                'default_device' => $defaultDevice,
            ], $user['id']);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/seo-onpage/project/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/seo-onpage/project/' . $id . '/settings');
        }
    }

    /**
     * Elimina progetto
     */
    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            return;
        }

        try {
            $this->project->delete($id, $user['id']);

            $_SESSION['_flash']['success'] = 'Progetto eliminato con successo';
            Router::redirect('/seo-onpage');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
            Router::redirect('/seo-onpage/project/' . $id . '/settings');
        }
    }

    /**
     * Normalizza dominio
     */
    private function normalizeDomain(string $domain): string
    {
        // Rimuovi protocollo
        $domain = preg_replace('#^https?://#', '', $domain);

        // Rimuovi www.
        $domain = preg_replace('/^www\./i', '', $domain);

        // Rimuovi path e query
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];

        return strtolower(trim($domain));
    }
}
