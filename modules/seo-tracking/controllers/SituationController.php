<?php

namespace Modules\SeoTracking\Controllers;

use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Core\Router;
use Core\View;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\Ga4Service;
use Modules\SeoTracking\Services\SituationService;
use Services\GoogleOAuthService;
use Services\ProjectAccessService;

/**
 * Scheda "Punto della situazione" di AI Rank + collegamento GA4.
 * Spec: docs/specs/seo-tracking-punto-situazione.md
 */
class SituationController
{
    private Project $project;
    private Ga4Service $ga4;

    public function __construct()
    {
        $this->project = new Project();
        $this->ga4 = new Ga4Service();
    }

    private function loadProject(int $id, bool $edit = false): array
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }
        if ($edit && ($project['access_role'] ?? 'owner') === 'viewer') {
            $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
            Router::redirect("/seo-tracking/project/{$id}/situation");
            exit;
        }
        return [$user, $project];
    }

    /** GET /seo-tracking/project/{id}/situation */
    public function index(int $id): string
    {
        [$user, $project] = $this->loadProject($id);
        $ga4Conn = $this->ga4->getConnection($id);
        $gscConn = Database::fetch("SELECT property_url FROM st_gsc_connections WHERE project_id = ? AND is_active = 1", [$id]);
        $reports = Database::fetchAll("SELECT id, period_key, b_start, b_end, credits_used, created_at FROM st_situation_reports WHERE project_id = ? ORDER BY id DESC LIMIT 20", [$id]);
        $reportId = (int) ($_GET['report'] ?? ($reports[0]['id'] ?? 0));
        $report = $reportId ? Database::fetch("SELECT * FROM st_situation_reports WHERE id = ? AND project_id = ?", [$reportId, $id]) : null;
        if ($report) {
            $report['data'] = json_decode((string) $report['data'], true) ?: [];
            $report['narrative'] = json_decode((string) $report['narrative'], true) ?: [];
        }
        return View::render('seo-tracking/situation/index', [
            'title' => 'Punto della situazione - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'ga4Conn' => $ga4Conn,
            'gscConn' => $gscConn ?: null,
            'reports' => $reports,
            'report' => $report,
            'periods' => SituationService::PERIODS,
            'cost' => SituationService::cost(),
            'balance' => Credits::getBalance(ProjectAccessService::getCreditUserId($project, $user['id'])),
            'googleConfigured' => (new GoogleOAuthService())->isConfigured(),
        ]);
    }

    /** POST /seo-tracking/project/{id}/situation/generate - AJAX lungo (GR 15/17/23) */
    public function generate(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(300);
        ob_start();
        header('Content-Type: application/json');
        $fail = function (string $msg, int $code = 400): void {
            ob_end_clean();
            http_response_code($code);
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        };

        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) {
            $fail('Progetto non trovato', 404);
        }
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            $fail('Non hai i permessi per questa operazione', 403);
        }
        $period = (string) ($_POST['period'] ?? 'last_28');
        if (!isset(SituationService::PERIODS[$period])) {
            $period = 'last_28';
        }
        $hasGa4 = (bool) ($this->ga4->getConnection($id)['property_id'] ?? null);
        $hasGsc = (bool) Database::fetch("SELECT 1 FROM st_gsc_connections WHERE project_id = ? AND is_active = 1 AND property_url IS NOT NULL", [$id]);
        if (!$hasGa4 && !$hasGsc) {
            $fail('Collega almeno Google Analytics o Search Console.');
        }
        $cost = SituationService::cost();
        $payer = ProjectAccessService::getCreditUserId($project, $user['id']);
        if (!Credits::hasEnough($payer, $cost)) {
            $fail("Crediti insufficienti: servono {$cost} crediti.");
        }
        session_write_close();

        $service = new SituationService();
        try {
            $data = $service->compute($project, $period);
            if ($data['ga4'] === null && $data['gsc'] === null) {
                $fail('Non sono riuscito a leggere i dati: ' . implode(' ', $data['caveats']));
            }
            $paragraphs = $service->narrative($user['id'], $project, $data);
        } catch (\Throwable $e) {
            Database::reconnect();
            $fail($e->getMessage(), 500);
        }
        Database::reconnect();
        $reportId = $service->save($project, (int) $user['id'], $data, $paragraphs, $cost);
        Credits::consume($payer, $cost, 'situation_report', 'seo-tracking', ['project_id' => $id, 'report_id' => $reportId, 'period' => $period]);

        ob_end_clean();
        echo json_encode(['success' => true, 'report_id' => $reportId]);
        exit;
    }

    // ================= GA4: collegamento =================

    /** GET /seo-tracking/project/{id}/ga4/connect */
    public function ga4Connect(int $id): void
    {
        $this->loadProject($id, true);
        $oauth = new GoogleOAuthService();
        if (!$oauth->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Accesso Google non configurato. Contatta l\'amministratore.';
            Router::redirect("/seo-tracking/project/{$id}/situation");
            exit;
        }
        header('Location: ' . $oauth->getAuthUrl('seo-tracking', $id, GoogleOAuthService::SCOPE_GA4, 'ga4'));
        exit;
    }

    /** GET /seo-tracking/ga4/connected - ritorno dal callback OAuth centralizzato */
    public function ga4Connected(): void
    {
        $tokens = $_SESSION['google_oauth_tokens'] ?? null;
        unset($_SESSION['google_oauth_tokens']);
        if (!$tokens || ($tokens['type'] ?? '') !== 'ga4') {
            $_SESSION['_flash']['error'] = 'Sessione di collegamento scaduta, riprova.';
            Router::redirect('/seo-tracking');
            exit;
        }
        $id = (int) $tokens['project_id'];
        $this->loadProject($id, true);
        $this->ga4->saveTokens($id, $tokens['access_token'], $tokens['refresh_token'] ?? null, (int) ($tokens['expires_in'] ?? 3600));
        Router::redirect("/seo-tracking/project/{$id}/ga4/select-property");
        exit;
    }

    /** GET /seo-tracking/project/{id}/ga4/select-property */
    public function ga4SelectProperty(int $id): string
    {
        [$user, $project] = $this->loadProject($id, true);
        try {
            $properties = $this->ga4->listProperties($id);
        } catch (\Throwable $e) {
            $_SESSION['_flash']['error'] = $e->getMessage();
            Router::redirect("/seo-tracking/project/{$id}/situation");
            exit;
        }
        return View::render('seo-tracking/situation/ga4-select', [
            'title' => 'Scegli la proprietà Google Analytics',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'properties' => $properties,
        ]);
    }

    /** POST /seo-tracking/project/{id}/ga4/select-property */
    public function ga4SaveProperty(int $id): void
    {
        $this->loadProject($id, true);
        $propertyId = preg_replace('/\D/', '', (string) ($_POST['property_id'] ?? ''));
        $allowed = [];
        try {
            foreach ($this->ga4->listProperties($id) as $p) {
                $allowed[$p['property_id']] = $p['property_name'];
            }
        } catch (\Throwable $e) {
            $_SESSION['_flash']['error'] = $e->getMessage();
            Router::redirect("/seo-tracking/project/{$id}/situation");
            exit;
        }
        if ($propertyId === '' || !isset($allowed[$propertyId])) {
            $_SESSION['_flash']['error'] = 'Scegli una proprietà dall\'elenco.';
            Router::redirect("/seo-tracking/project/{$id}/ga4/select-property");
            exit;
        }
        $this->ga4->saveProperty($id, $propertyId, $allowed[$propertyId]);
        Database::execute("UPDATE st_projects SET ga4_connected = 1 WHERE id = ?", [$id]);
        $_SESSION['_flash']['success'] = 'Google Analytics collegato.';
        Router::redirect("/seo-tracking/project/{$id}/situation");
        exit;
    }

    /** POST /seo-tracking/project/{id}/ga4/disconnect */
    public function ga4Disconnect(int $id): void
    {
        $this->loadProject($id, true);
        $this->ga4->disconnect($id);
        Database::execute("UPDATE st_projects SET ga4_connected = 0 WHERE id = ?", [$id]);
        $_SESSION['_flash']['success'] = 'Google Analytics scollegato.';
        Router::redirect("/seo-tracking/project/{$id}/situation");
        exit;
    }
}
