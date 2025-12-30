<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\AiReportService;

/**
 * AiController
 * Gestisce la generazione di report AI on-demand
 */
class AiController
{
    private Project $project;
    private AiReportService $aiService;

    public function __construct()
    {
        $this->project = new Project();
        $this->aiService = new AiReportService();
    }

    /**
     * Genera Weekly Digest
     */
    public function generateWeekly(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$this->aiService->isConfigured()) {
            $_SESSION['_flash']['error'] = 'API Claude non configurata';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            return;
        }

        try {
            $result = $this->aiService->generateWeeklyDigest($projectId, $user['id']);

            if ($result) {
                $_SESSION['_flash']['success'] = 'Report settimanale generato con successo';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
        }
    }

    /**
     * Genera Monthly Executive
     */
    public function generateMonthly(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$this->aiService->isConfigured()) {
            $_SESSION['_flash']['error'] = 'API Claude non configurata';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            return;
        }

        try {
            $result = $this->aiService->generateMonthlyExecutive($projectId, $user['id']);

            if ($result) {
                $_SESSION['_flash']['success'] = 'Report executive generato con successo';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
        }
    }

    /**
     * Genera Keyword Analysis Report
     */
    public function generateKeywordAnalysis(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$this->aiService->isConfigured()) {
            $_SESSION['_flash']['error'] = 'API Claude non configurata';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            return;
        }

        try {
            // Genera report custom per keyword analysis
            $result = $this->aiService->generateCustomReport($projectId, $user['id'], 'keyword_analysis');

            if ($result) {
                $_SESSION['_flash']['success'] = 'Analisi keyword generata con successo';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
        }
    }

    /**
     * Genera Revenue Attribution Report
     */
    public function generateRevenueAttribution(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$this->aiService->isConfigured()) {
            $_SESSION['_flash']['error'] = 'API Claude non configurata';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            return;
        }

        try {
            // Genera report custom per revenue
            $result = $this->aiService->generateCustomReport($projectId, $user['id'], 'revenue_attribution');

            if ($result) {
                $_SESSION['_flash']['success'] = 'Analisi revenue generata con successo';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/reports');
        }
    }

    /**
     * API: Genera report (per AJAX)
     */
    public function generate(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $type = $_POST['type'] ?? 'weekly_digest';
        $customQuestion = $_POST['custom_question'] ?? null;

        if (!$this->aiService->isConfigured()) {
            return View::json(['error' => 'API Claude non configurata'], 400);
        }

        try {
            $result = match($type) {
                'weekly_digest' => $this->aiService->generateWeeklyDigest($projectId, $user['id']),
                'monthly_executive' => $this->aiService->generateMonthlyExecutive($projectId, $user['id']),
                'anomaly_analysis' => $this->aiService->generateAnomalyAnalysis($projectId, $user['id'], []),
                'custom' => $this->aiService->generateCustomReport($projectId, $user['id'], 'custom', $customQuestion),
                default => null,
            };

            if ($result) {
                return View::json([
                    'success' => true,
                    'report_id' => $result['id'],
                    'redirect' => url('/seo-tracking/projects/' . $projectId . '/reports/' . $result['id']),
                ]);
            }

            return View::json(['error' => 'Crediti insufficienti'], 402);
        } catch (\Exception $e) {
            return View::json(['error' => $e->getMessage()], 500);
        }
    }
}
