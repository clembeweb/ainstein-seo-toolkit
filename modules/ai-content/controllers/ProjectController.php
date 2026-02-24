<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\AiContent\Models\Project;
use Modules\AiContent\Models\AutoConfig;
use Modules\AiContent\Models\WpSite;

/**
 * ProjectController
 * Gestisce CRUD progetti AI Content
 */
class ProjectController
{
    private Project $project;

    public function __construct()
    {
        $this->project = new Project();
    }

    /**
     * Entry dashboard con 3 mode cards (nuova homepage modulo)
     */
    public function entryDashboard(): string
    {
        $user = Auth::user();

        $stats = $this->project->getGlobalStats($user['id']);
        $recentProjects = $this->project->getRecentProjects($user['id'], 6);

        return View::render('ai-content/entry-dashboard', [
            'title' => 'AI Content Generator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'stats' => $stats,
            'recentProjects' => $recentProjects,
        ]);
    }

    /**
     * Lista progetti (tabs per tipo)
     */
    public function index(): string
    {
        $user = Auth::user();

        // Progetti raggruppati per tipo con stats specifiche
        $projectsByType = $this->project->allGroupedByType($user['id']);

        // Tab attivo da query string (default: primo tab con progetti)
        $activeTab = $_GET['tab'] ?? null;
        if (!$activeTab || !in_array($activeTab, ['manual', 'auto', 'meta-tag'])) {
            // Seleziona il primo tab con progetti
            if (!empty($projectsByType['manual'])) {
                $activeTab = 'manual';
            } elseif (!empty($projectsByType['auto'])) {
                $activeTab = 'auto';
            } elseif (!empty($projectsByType['meta-tag'])) {
                $activeTab = 'meta-tag';
            } else {
                $activeTab = 'manual'; // Default
            }
        }

        return View::render('ai-content/projects/index', [
            'title' => 'AI Content Generator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projectsByType' => $projectsByType,
            'activeTab' => $activeTab,
            // Legacy: tutti i progetti per retrocompatibilità
            'projects' => array_merge(
                $projectsByType['manual'],
                $projectsByType['auto'],
                $projectsByType['meta-tag']
            ),
        ]);
    }

    /**
     * Form creazione progetto
     */
    public function create(): string
    {
        $user = Auth::user();

        return View::render('ai-content/projects/create', [
            'title' => 'Nuovo Progetto - AI Content',
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
        $description = trim($_POST['description'] ?? '');
        $defaultLanguage = trim($_POST['default_language'] ?? 'it');
        $defaultLocation = trim($_POST['default_location'] ?? 'Italy');

        // Tipo progetto (manual, auto, meta-tag)
        $type = trim($_POST['type'] ?? 'manual');
        if (!in_array($type, ['manual', 'auto', 'meta-tag'])) {
            $type = 'manual';
        }

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (strlen($name) > 255) {
            $errors[] = 'Il nome del progetto non può superare 255 caratteri';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/ai-content/projects/create');
            return;
        }

        try {
            $projectId = $this->project->create([
                'user_id' => $user['id'],
                'type' => $type,
                'name' => $name,
                'description' => $description ?: null,
                'default_language' => $defaultLanguage,
                'default_location' => $defaultLocation,
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo!';

            // Redirect diverso in base al tipo
            if ($type === 'auto') {
                // Crea config default per automazione
                $autoConfig = new AutoConfig();
                $autoConfig->create($projectId);

                Router::redirect('/ai-content/projects/' . $projectId . '/auto');
            } elseif ($type === 'meta-tag') {
                Router::redirect('/ai-content/projects/' . $projectId . '/meta-tags');
            } else {
                Router::redirect('/ai-content/projects/' . $projectId);
            }

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/ai-content/projects/create');
        }
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            exit;
        }

        // Settings: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            Router::redirect('/ai-content/projects/' . $id);
            return '';
        }

        // Reload with stats for settings view
        $project = array_merge($project, ['stats' => $this->project->getStats($id)]);

        // Load user's WP sites for dropdown selection
        $wpSiteModel = new WpSite();
        $wpSites = $wpSiteModel->allByUser($user['id']);

        return View::render('ai-content/projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'wpSites' => $wpSites,
        ]);
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        // Update: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            Router::redirect('/ai-content/projects/' . $id . '/settings');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $defaultLanguage = trim($_POST['default_language'] ?? 'it');
        $defaultLocation = trim($_POST['default_location'] ?? 'Italy');
        $wpSiteId = !empty($_POST['wp_site_id']) ? (int) $_POST['wp_site_id'] : null;

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        // Validate WP site belongs to user if specified
        if ($wpSiteId !== null) {
            $wpSiteModel = new WpSite();
            $wpSite = $wpSiteModel->find($wpSiteId, $user['id']);
            if (!$wpSite) {
                $errors[] = 'Sito WordPress non valido';
                $wpSiteId = null;
            }
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/ai-content/projects/' . $id . '/settings');
            return;
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'description' => $description ?: null,
                'default_language' => $defaultLanguage,
                'default_location' => $defaultLocation,
                'wp_site_id' => $wpSiteId,
            ], $user['id']);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/ai-content/projects/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/ai-content/projects/' . $id . '/settings');
        }
    }

    /**
     * Elimina progetto
     */
    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/ai-content');
            return;
        }

        // Delete: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            Router::redirect('/ai-content/projects/' . $id . '/settings');
            return;
        }

        try {
            $this->project->delete($id, $user['id']);

            $_SESSION['_flash']['success'] = 'Progetto eliminato con successo';
            Router::redirect('/ai-content');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
            Router::redirect('/ai-content/projects/' . $id . '/settings');
        }
    }
}
