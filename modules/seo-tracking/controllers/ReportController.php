<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\AiReport;
use Modules\SeoTracking\Services\AiReportService;

/**
 * ReportController
 * Gestisce report AI
 */
class ReportController
{
    private Project $project;
    private AiReport $aiReport;
    private AiReportService $aiReportService;

    public function __construct()
    {
        $this->project = new Project();
        $this->aiReport = new AiReport();
        $this->aiReportService = new AiReportService();
    }

    /**
     * Lista report progetto
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
        ];

        $reports = $this->aiReport->getByProject($projectId, $filters);

        return View::render('seo-tracking/reports/index', [
            'title' => $project['name'] . ' - Report AI',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'reports' => $reports,
            'filters' => $filters,
            'isConfigured' => $this->aiReportService->isConfigured(),
        ]);
    }

    /**
     * Visualizza report
     */
    public function show(int $id): string
    {
        $user = Auth::user();
        $report = $this->aiReport->find($id);

        if (!$report) {
            $_SESSION['_flash']['error'] = 'Report non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $project = $this->project->find($report['project_id'], $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        return View::render('seo-tracking/reports/show', [
            'title' => $report['title'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'report' => $report,
        ]);
    }

    /**
     * Genera nuovo report
     */
    public function create(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        if (!$this->aiReportService->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Claude API Key non configurata';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            exit;
        }

        return View::render('seo-tracking/reports/create', [
            'title' => 'Genera Report AI - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
        ]);
    }

    /**
     * Genera report
     */
    public function generate(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $reportType = $_POST['report_type'] ?? 'weekly_digest';

        try {
            $result = match($reportType) {
                'weekly_digest' => $this->aiReportService->generateWeeklyDigest($projectId, $user['id']),
                'monthly_executive' => $this->aiReportService->generateMonthlyExecutive($projectId, $user['id']),
                default => null,
            };

            if ($result) {
                $_SESSION['_flash']['success'] = 'Report generato con successo';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Errore nella generazione del report. Verifica i crediti disponibili.';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/create');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/create');
        }
    }

    /**
     * Elimina report
     */
    public function destroy(int $id): void
    {
        $user = Auth::user();
        $report = $this->aiReport->find($id);

        if (!$report) {
            $_SESSION['_flash']['error'] = 'Report non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $project = $this->project->find($report['project_id'], $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $this->aiReport->delete($id);

        $_SESSION['_flash']['success'] = 'Report eliminato';
        Router::redirect('/seo-tracking/projects/' . $report['project_id'] . '/reports');
    }

    /**
     * Download PDF (placeholder)
     */
    public function downloadPdf(int $id): void
    {
        $user = Auth::user();
        $report = $this->aiReport->find($id);

        if (!$report) {
            $_SESSION['_flash']['error'] = 'Report non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $project = $this->project->find($report['project_id'], $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        // Per ora output come HTML stampabile
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><title>{$report['title']}</title>";
        echo "<style>body{font-family:system-ui;max-width:800px;margin:40px auto;padding:20px;line-height:1.6}</style>";
        echo "</head><body>";
        echo "<h1>{$report['title']}</h1>";
        echo "<p><small>{$project['name']} - {$project['domain']}</small></p>";
        echo "<hr>";
        echo \Parsedown::instance()->text($report['content']);
        echo "</body></html>";
        exit;
    }
}
