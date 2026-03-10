<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\View;
use Core\Database;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\UnifiedReport;
use Modules\SeoAudit\Services\UnifiedReportService;

class UnifiedReportController
{
    private Project $projectModel;
    private UnifiedReport $reportModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->reportModel = new UnifiedReport();
    }

    /**
     * GET: Visualizza report (ultimo disponibile o CTA genera)
     */
    public function view(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $userId = $user['id'];

        $project = $this->projectModel->findAccessible($id, $userId);
        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect("/seo-audit/project/{$id}/audit");
        }

        $report = $this->reportModel->findLatest($id);
        $reportData = null;
        $siteProfile = null;

        if ($report) {
            $decoded = json_decode($report['html_content'] ?? '{}', true);
            // html_content may be a wrapper {ai_report: {...}} or direct AI response
            $reportData = $decoded['ai_report'] ?? $decoded;
            $siteProfile = json_decode($report['site_profile'] ?? '{}', true);
        }

        $cost = ModuleLoader::getSetting('seo-audit', 'cost_unified_report', 15);

        return View::render('seo-audit::report/unified-view', [
            'title' => 'Report AI Unificato — ' . ($project['name'] ?? ''),
            'user' => $user,
            'project' => $project,
            'report' => $report,
            'reportData' => $reportData,
            'siteProfile' => $siteProfile,
            'cost' => $cost,
            'currentPage' => 'report',
            'modules' => \Core\ModuleLoader::getActiveModules(),
        ]);
    }

    /**
     * POST AJAX: Genera report AI
     */
    public function generate(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();

        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');
        session_write_close();

        $user = Auth::user();
        $userId = $user['id'];

        $project = $this->projectModel->findAccessible($id, $userId);
        if (!$project) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        try {
            $service = new UnifiedReportService();
            $result = $service->generate($id, $userId);

            ob_end_clean();
            echo json_encode($result);
        } catch (\Exception $e) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
        exit;
    }

    /**
     * GET: Scarica report HTML standalone
     */
    public function download(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();
        $userId = $user['id'];

        $project = $this->projectModel->findAccessible($id, $userId);
        if (!$project) {
            http_response_code(404);
            echo 'Progetto non trovato';
            exit;
        }

        $report = $this->reportModel->findLatest($id);
        if (!$report) {
            http_response_code(404);
            echo 'Report non trovato. Genera prima un report.';
            exit;
        }

        $decoded = json_decode($report['html_content'] ?? '{}', true);
        $reportData = $decoded['ai_report'] ?? $decoded;
        $siteProfile = json_decode($report['site_profile'] ?? '{}', true);
        $domain = $project['domain'] ?? $project['url'] ?? '';

        // Render standalone HTML
        ob_start();
        $healthScore = (int)($report['health_score'] ?? $project['health_score'] ?? 0);
        $budgetScore = (int)($report['budget_score'] ?? 0);
        $generatedAt = date('d/m/Y H:i', strtotime($report['created_at'] ?? 'now'));
        $reportId = $report['id'] ?? 0;

        include __DIR__ . '/../views/report/unified-template.php';
        $html = ob_get_clean();

        $filename = 'report-seo-' . preg_replace('/[^a-z0-9-]/', '', strtolower($domain)) . '-' . date('Y-m-d') . '.html';

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($html));
        echo $html;
        exit;
    }
}
