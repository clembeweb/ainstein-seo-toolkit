<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Core\Database;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\GscConnection;
use Modules\SeoTracking\Models\Ga4Connection;
use Modules\SeoTracking\Models\AlertSettings;

/**
 * ProjectController
 * Gestisce CRUD progetti SEO Tracking
 */
class ProjectController
{
    private Project $project;
    private GscConnection $gscConnection;
    private Ga4Connection $ga4Connection;
    private AlertSettings $alertSettings;

    public function __construct()
    {
        $this->project = new Project();
        $this->gscConnection = new GscConnection();
        $this->ga4Connection = new Ga4Connection();
        $this->alertSettings = new AlertSettings();
    }

    /**
     * Lista progetti
     */
    public function index(): string
    {
        $user = Auth::user();
        $projects = $this->project->allWithStats($user['id']);

        return View::render('seo-tracking/projects/index', [
            'title' => 'SEO Position Tracking',
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

        return View::render('seo-tracking/projects/create', [
            'title' => 'Nuovo Progetto - SEO Tracking',
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
        $notificationEmails = trim($_POST['notification_emails'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (empty($domain)) {
            $errors[] = 'Il dominio è obbligatorio';
        }

        // Valida email notifiche
        $emails = [];
        if (!empty($notificationEmails)) {
            $emailList = array_map('trim', explode(',', $notificationEmails));
            foreach ($emailList as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Email non valida: {$email}";
                } else {
                    $emails[] = $email;
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/seo-tracking/projects/create');
            return;
        }

        try {
            $projectId = $this->project->create([
                'user_id' => $user['id'],
                'name' => $name,
                'domain' => Project::normalizeDomain($domain),
                'notification_emails' => !empty($emails) ? json_encode($emails) : null,
                'ai_reports_enabled' => isset($_POST['ai_reports_enabled']) ? 1 : 0,
                'weekly_report_day' => (int) ($_POST['weekly_report_day'] ?? 1),
                'monthly_report_day' => (int) ($_POST['monthly_report_day'] ?? 1),
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato con successo! Ora connetti Google Search Console.';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/create');
        }
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithConnections($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Genera redirect URI per GSC OAuth
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'];
        $gscRedirectUri = $baseUrl . '/seo-tracking/gsc/callback';

        return View::render('seo-tracking/projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'gscConnection' => $project['gsc_connection'] ?? null,
            'ga4Connection' => $project['ga4_connection'] ?? null,
            'gscRedirectUri' => $gscRedirectUri,
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
            Router::redirect('/seo-tracking');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $notificationEmails = trim($_POST['notification_emails'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        // Valida email notifiche
        $emails = [];
        if (!empty($notificationEmails)) {
            $emailList = array_map('trim', explode(',', $notificationEmails));
            foreach ($emailList as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Email non valida: {$email}";
                } else {
                    $emails[] = $email;
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'notification_emails' => !empty($emails) ? json_encode($emails) : null,
                'sync_enabled' => isset($_POST['sync_enabled']) ? 1 : 0,
                'ai_reports_enabled' => isset($_POST['ai_reports_enabled']) ? 1 : 0,
                'weekly_report_day' => (int) ($_POST['weekly_report_day'] ?? 1),
                'weekly_report_time' => $_POST['weekly_report_time'] ?? '08:00:00',
                'monthly_report_day' => (int) ($_POST['monthly_report_day'] ?? 1),
                'data_retention_months' => (int) ($_POST['data_retention_months'] ?? 16),
            ], $user['id']);

            // Aggiorna alert settings
            $this->alertSettings->upsert($id, [
                'position_alert_enabled' => isset($_POST['position_alert_enabled']) ? 1 : 0,
                'position_threshold' => (int) ($_POST['position_threshold'] ?? 5),
                'position_alert_keywords' => $_POST['position_alert_keywords'] ?? 'tracked',
                'traffic_alert_enabled' => isset($_POST['traffic_alert_enabled']) ? 1 : 0,
                'traffic_drop_threshold' => (int) ($_POST['traffic_drop_threshold'] ?? 20),
                'revenue_alert_enabled' => isset($_POST['revenue_alert_enabled']) ? 1 : 0,
                'revenue_drop_threshold' => (int) ($_POST['revenue_drop_threshold'] ?? 20),
                'anomaly_alert_enabled' => isset($_POST['anomaly_alert_enabled']) ? 1 : 0,
                'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
                'email_frequency' => $_POST['email_frequency'] ?? 'daily_digest',
            ]);

            $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel salvataggio: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
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
            Router::redirect('/seo-tracking');
            return;
        }

        try {
            $this->project->delete($id, $user['id']);

            $_SESSION['_flash']['success'] = 'Progetto eliminato con successo';
            Router::redirect('/seo-tracking');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nell\'eliminazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
        }
    }

    /**
     * Interrompe sync in corso
     */
    public function stopSync(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            jsonResponse(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        // Reset stato sync
        Database::update('st_projects', [
            'sync_status' => 'idle',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$projectId]);

        error_log("[SeoTracking] Sync interrotto manualmente - project_id: {$projectId}, user_id: {$user['id']}");

        $_SESSION['_flash']['success'] = 'Sincronizzazione interrotta';
        jsonResponse(['success' => true]);
    }
}
