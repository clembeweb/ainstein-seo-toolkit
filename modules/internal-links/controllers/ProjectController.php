<?php

namespace Modules\InternalLinks\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\InternalLinks\Models\Project;
use Modules\InternalLinks\Models\Url;
use Modules\InternalLinks\Models\InternalLink;

/**
 * ProjectController
 *
 * Handles project management operations for Internal Links module
 */
class ProjectController
{
    private Project $project;
    private Url $url;
    private InternalLink $link;

    public function __construct()
    {
        $this->project = new Project();
        $this->url = new Url();
        $this->link = new InternalLink();
    }

    /**
     * Display list of all projects
     */
    public function index(): string
    {
        $user = Auth::user();
        $projects = $this->project->allWithStats($user['id']);

        return View::render('internal-links/projects/index', [
            'title' => 'Internal Links - Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
    }

    /**
     * Show create project form
     */
    public function create(): string
    {
        $user = Auth::user();

        return View::render('internal-links/projects/create', [
            'title' => 'Nuovo Progetto - Internal Links',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Store new project
     */
    public function store(): void
    {
        $user = Auth::user();

        $name = trim($_POST['name'] ?? '');
        $baseUrl = trim($_POST['base_url'] ?? '');
        $cssSelector = trim($_POST['css_selector'] ?? '');
        $scrapeDelay = (int) ($_POST['scrape_delay'] ?? 1000);
        $userAgent = trim($_POST['user_agent'] ?? 'Mozilla/5.0 (compatible; SEOToolkit/1.0)');

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto e obbligatorio';
        }

        if (empty($baseUrl)) {
            $errors[] = 'La URL base e obbligatoria';
        } elseif (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Formato URL non valido';
        }

        if ($scrapeDelay < 100) {
            $errors[] = 'Il delay minimo e 100ms';
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('. ', $errors);
            header('Location: ' . url('/projects/create'));
            exit;
        }

        try {
            $projectId = $this->project->createWithStats([
                'user_id' => $user['id'],
                'name' => $name,
                'base_url' => Project::normalizeBaseUrl($baseUrl),
                'css_selector' => $cssSelector ?: null,
                'scrape_delay' => $scrapeDelay,
                'user_agent' => $userAgent,
            ]);

            $_SESSION['flash_success'] = 'Progetto creato con successo!';
            header('Location: ' . url('/internal-links/project/' . $projectId));
            exit;

        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Errore nella creazione: ' . $e->getMessage();
            header('Location: ' . url('/projects/create'));
            exit;
        }
    }

    /**
     * Show project dashboard
     */
    public function show(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/internal-links'));
            exit;
        }

        // Force stats update if cache seems out of sync
        // (e.g., total_urls is 0 but we have URLs in the database)
        $actualUrlCount = $this->url->countByProject($id);
        if ((int) ($project['total_urls'] ?? 0) !== $actualUrlCount) {
            $this->project->updateStats($id);
            // Refresh project data — keep access_role
            $accessRole = $project['access_role'] ?? 'owner';
            $project = $this->project->findWithStats($id, (int)$project['user_id']);
            if ($project) {
                $project['access_role'] = $accessRole;
            }
        }

        $scrapingProgress = $this->project->getScrapingProgress($id);
        $scoreDistribution = $this->link->getScoreDistribution($id);
        $juiceDistribution = $this->link->getJuiceFlowDistribution($id);
        $orphanPages = $this->link->getOrphanPages($id);
        $recentActivity = $this->project->getActivity($id, 10);

        return View::render('internal-links/projects/show', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'scrapingProgress' => $scrapingProgress,
            'scoreDistribution' => $scoreDistribution,
            'juiceDistribution' => $juiceDistribution,
            'orphanCount' => count($orphanPages),
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Show project settings
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/internal-links'));
            exit;
        }

        // Settings: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['flash_error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url('/internal-links/project/' . $id));
            exit;
        }

        return View::render('internal-links/projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
        ]);
    }

    /**
     * Update project
     */
    public function update(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/internal-links'));
            exit;
        }

        // Update: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['flash_error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url('/internal-links/project/' . $id . '/settings'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $baseUrl = trim($_POST['base_url'] ?? '');
        $cssSelector = trim($_POST['css_selector'] ?? '');
        $scrapeDelay = (int) ($_POST['scrape_delay'] ?? 1000);
        $userAgent = trim($_POST['user_agent'] ?? '');
        $status = $_POST['status'] ?? 'active';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto e obbligatorio';
        }

        if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL base valida obbligatoria';
        }

        if (!in_array($status, ['active', 'paused', 'archived'])) {
            $errors[] = 'Stato non valido';
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('. ', $errors);
            header('Location: ' . url('/internal-links/project/' . $id . '/settings'));
            exit;
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'base_url' => Project::normalizeBaseUrl($baseUrl),
                'css_selector' => $cssSelector ?: null,
                'scrape_delay' => $scrapeDelay,
                'user_agent' => $userAgent,
                'status' => $status,
            ], $user['id']);

            $this->project->logActivity($id, $user['id'], 'project_updated');
            $_SESSION['flash_success'] = 'Progetto aggiornato!';

        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Errore: ' . $e->getMessage();
        }

        header('Location: ' . url('/internal-links/project/' . $id . '/settings'));
        exit;
    }

    /**
     * Delete project
     */
    public function delete(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/internal-links'));
            exit;
        }

        // Delete: solo owner
        if (($project['access_role'] ?? 'owner') !== 'owner') {
            $_SESSION['flash_error'] = 'Non hai i permessi per questa operazione';
            header('Location: ' . url('/internal-links/project/' . $id . '/settings'));
            exit;
        }

        try {
            $this->project->delete($id, $user['id']);
            $_SESSION['flash_success'] = 'Progetto eliminato';

        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Errore: ' . $e->getMessage();
        }

        header('Location: ' . url('/internal-links'));
        exit;
    }
}
