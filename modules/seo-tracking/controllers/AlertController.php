<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Alert;
use Modules\SeoTracking\Models\AlertSettings;
use Modules\SeoTracking\Services\AlertService;

/**
 * AlertController
 * Gestisce visualizzazione e gestione alert
 */
class AlertController
{
    private Project $project;
    private Alert $alert;
    private AlertSettings $alertSettings;
    private AlertService $alertService;

    public function __construct()
    {
        $this->project = new Project();
        $this->alert = new Alert();
        $this->alertSettings = new AlertSettings();
        $this->alertService = new AlertService();
    }

    /**
     * Lista alert progetto
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $filters = [
            'type' => $_GET['type'] ?? null,
            'severity' => $_GET['severity'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];

        $alerts = $this->alert->getByProject($projectId, $filters);
        $stats = $this->alert->getStats($projectId);

        return View::render('seo-tracking/alerts/index', [
            'title' => $project['name'] . ' - Alert',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'alerts' => $alerts,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Dettaglio alert
     */
    public function show(int $id): string
    {
        $user = Auth::user();
        $alert = $this->alert->find($id);

        if (!$alert) {
            $_SESSION['_flash']['error'] = 'Alert non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $project = $this->project->find($alert['project_id'], $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Marca come letto
        if ($alert['status'] === 'new') {
            $this->alert->markAsRead($id);
        }

        return View::render('seo-tracking/alerts/show', [
            'title' => 'Alert - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'alert' => $alert,
        ]);
    }

    /**
     * Marca alert come letto
     */
    public function markRead(int $id): string
    {
        $user = Auth::user();
        $alert = $this->alert->find($id);

        if (!$alert) {
            return View::json(['error' => 'Alert non trovato'], 404);
        }

        $project = $this->project->find($alert['project_id'], $user['id']);

        if (!$project) {
            return View::json(['error' => 'Accesso negato'], 403);
        }

        $this->alert->markAsRead($id);

        return View::json(['success' => true]);
    }

    /**
     * Marca tutti come letti
     */
    public function markAllRead(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $this->alert->markAllAsRead($projectId);

        $_SESSION['_flash']['success'] = 'Tutti gli alert sono stati marcati come letti';
        Router::redirect('/seo-tracking/projects/' . $projectId . '/alerts');
    }

    /**
     * Archivia alert
     */
    public function archive(int $id): string
    {
        $user = Auth::user();
        $alert = $this->alert->find($id);

        if (!$alert) {
            return View::json(['error' => 'Alert non trovato'], 404);
        }

        $project = $this->project->find($alert['project_id'], $user['id']);

        if (!$project) {
            return View::json(['error' => 'Accesso negato'], 403);
        }

        $this->alert->archive($id);

        return View::json(['success' => true]);
    }

    /**
     * Esegui check alert manuale
     */
    public function runCheck(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $alerts = $this->alertService->checkAlerts($projectId);

        if (count($alerts) > 0) {
            $_SESSION['_flash']['success'] = 'Trovati ' . count($alerts) . ' nuovi alert';
        } else {
            $_SESSION['_flash']['info'] = 'Nessun nuovo alert rilevato';
        }

        Router::redirect('/seo-tracking/projects/' . $projectId . '/alerts');
    }

    /**
     * API: Conta alert non letti
     */
    public function unreadCount(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $count = $this->alert->countUnread($projectId);

        return View::json(['count' => $count]);
    }
}
