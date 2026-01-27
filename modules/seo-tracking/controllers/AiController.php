<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Core\Credits;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\KeywordGroup;
use Modules\SeoTracking\Services\AiReportService;
use Modules\SeoTracking\Services\QuickWinsService;
use Modules\SeoTracking\Services\SeoPageAnalyzerService;

/**
 * AiController
 * Gestisce la generazione di report AI on-demand
 */
class AiController
{
    private Project $project;
    private AiReportService $aiService;
    private QuickWinsService $quickWinsService;
    private SeoPageAnalyzerService $pageAnalyzer;
    private KeywordGroup $keywordGroup;

    public function __construct()
    {
        $this->project = new Project();
        $this->aiService = new AiReportService();
        $this->quickWinsService = new QuickWinsService();
        $this->pageAnalyzer = new SeoPageAnalyzerService();
        $this->keywordGroup = new KeywordGroup();
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
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            return;
        }

        try {
            $result = $this->aiService->generateWeeklyDigest($projectId, $user['id']);

            if ($result) {
                $_SESSION['_flash']['success'] = 'Report settimanale generato con successo';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
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
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            return;
        }

        try {
            $result = $this->aiService->generateMonthlyExecutive($projectId, $user['id']);

            if ($result) {
                $_SESSION['_flash']['success'] = 'Report executive generato con successo';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
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
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            return;
        }

        try {
            // Genera report custom per keyword analysis
            $result = $this->aiService->generateCustomReport($projectId, $user['id'], 'keyword_analysis');

            if ($result) {
                $_SESSION['_flash']['success'] = 'Analisi keyword generata con successo';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
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
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            return;
        }

        try {
            // Genera report custom per revenue
            $result = $this->aiService->generateCustomReport($projectId, $user['id'], 'revenue_attribution');

            if ($result) {
                $_SESSION['_flash']['success'] = 'Analisi revenue generata con successo';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports/' . $result['id']);
            } else {
                $_SESSION['_flash']['error'] = 'Crediti insufficienti per generare il report';
                Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
            }
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore generazione: ' . $e->getMessage();
            Router::redirect('/seo-tracking/project/' . $projectId . '/reports');
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
                    'redirect' => url('/seo-tracking/project/' . $projectId . '/reports/' . $result['id']),
                ]);
            }

            return View::json(['error' => 'Crediti insufficienti'], 402);
        } catch (\Exception $e) {
            return View::json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================
    // QUICK WINS AI
    // =========================================

    /**
     * Pagina Quick Wins (progetto)
     */
    public function quickWins(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $candidates = $this->quickWinsService->getCandidateKeywords($projectId);
        $creditCost = $this->quickWinsService->getCreditCost();
        $userCredits = Credits::getBalance($user['id']);

        return View::render('seo-tracking/ai/quick-wins', [
            'title' => 'Quick Wins AI - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'candidates' => $candidates,
            'creditCost' => $creditCost,
            'userCredits' => $userCredits,
            'isConfigured' => $this->quickWinsService->isConfigured(),
            'group' => null,
        ]);
    }

    /**
     * Analizza Quick Wins (progetto) - POST AJAX
     */
    public function analyzeQuickWins(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        if (!$this->quickWinsService->isConfigured()) {
            return View::json(['error' => 'API AI non configurata'], 400);
        }

        $result = $this->quickWinsService->analyze($projectId, $user['id']);

        if (isset($result['error'])) {
            $code = $result['message'] === 'Crediti insufficienti' ? 402 : 400;
            return View::json(['error' => $result['message']], $code);
        }

        return View::json([
            'success' => true,
            'data' => $result['data'],
            'report_id' => $result['report_id'],
            'keywords_analyzed' => $result['keywords_analyzed'],
            'credits_used' => $result['credits_used'],
        ]);
    }

    /**
     * Pagina Quick Wins (gruppo)
     */
    public function quickWinsGroup(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $group = $this->keywordGroup->findByProject($groupId, $projectId);

        if (!$group) {
            $_SESSION['_flash']['error'] = 'Gruppo non trovato';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups');
            exit;
        }

        $candidates = $this->quickWinsService->getCandidateKeywordsForGroup($groupId);
        $creditCost = $this->quickWinsService->getCreditCost();
        $userCredits = Credits::getBalance($user['id']);

        return View::render('seo-tracking/ai/quick-wins', [
            'title' => 'Quick Wins AI - ' . $group['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'group' => $group,
            'candidates' => $candidates,
            'creditCost' => $creditCost,
            'userCredits' => $userCredits,
            'isConfigured' => $this->quickWinsService->isConfigured(),
        ]);
    }

    /**
     * Analizza Quick Wins (gruppo) - POST AJAX
     */
    public function analyzeQuickWinsGroup(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $group = $this->keywordGroup->findByProject($groupId, $projectId);

        if (!$group) {
            return View::json(['error' => 'Gruppo non trovato'], 404);
        }

        if (!$this->quickWinsService->isConfigured()) {
            return View::json(['error' => 'API AI non configurata'], 400);
        }

        $result = $this->quickWinsService->analyze($projectId, $user['id'], $groupId);

        if (isset($result['error'])) {
            $code = $result['message'] === 'Crediti insufficienti' ? 402 : 400;
            return View::json(['error' => $result['message']], $code);
        }

        return View::json([
            'success' => true,
            'data' => $result['data'],
            'report_id' => $result['report_id'],
            'keywords_analyzed' => $result['keywords_analyzed'],
            'credits_used' => $result['credits_used'],
        ]);
    }

    // =========================================
    // SEO PAGE ANALYZER
    // =========================================

    /**
     * Analizza una pagina specifica per una keyword - POST AJAX
     *
     * Parametri POST:
     * - keyword: string (keyword target)
     * - url: string (URL della pagina da analizzare)
     * - position: int (posizione attuale, opzionale)
     * - location_code: string (default IT)
     * - force_fresh: bool (forza nuovo check SERP)
     */
    public function analyzePage(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        // Parametri richiesti
        $keyword = trim($_POST['keyword'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if (empty($keyword) || empty($url)) {
            return View::json(['error' => 'Keyword e URL sono obbligatori'], 400);
        }

        // Valida URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return View::json(['error' => 'URL non valido'], 400);
        }

        // Parametri opzionali
        $position = !empty($_POST['position']) ? (int) $_POST['position'] : null;
        $locationCode = $_POST['location_code'] ?? 'IT';
        $forceFresh = filter_var($_POST['force_fresh'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Verifica configurazione
        if (!$this->pageAnalyzer->isConfigured()) {
            return View::json([
                'error' => 'Servizio non configurato. Verifica API AI e SERP nelle impostazioni.'
            ], 400);
        }

        // Esegui analisi
        $result = $this->pageAnalyzer->analyze(
            $projectId,
            $user['id'],
            $keyword,
            $url,
            $position,
            [
                'location_code' => $locationCode,
                'force_fresh_serp' => $forceFresh,
            ]
        );

        if (isset($result['error'])) {
            $code = str_contains($result['message'] ?? '', 'Crediti') ? 402 : 400;
            return View::json(['error' => $result['message']], $code);
        }

        return View::json([
            'success' => true,
            'analysis_id' => $result['analysis_id'],
            'data' => $result['data'],
            'target_page' => $result['target_page'],
            'competitors_analyzed' => $result['competitors_analyzed'],
            'credits_used' => $result['credits_used'],
        ]);
    }

    /**
     * Ottieni analisi pagina per ID - GET AJAX
     */
    public function getPageAnalysis(int $projectId, int $analysisId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $analysis = $this->pageAnalyzer->getAnalysis($analysisId);

        if (!$analysis || $analysis['project_id'] != $projectId) {
            return View::json(['error' => 'Analisi non trovata'], 404);
        }

        return View::json([
            'success' => true,
            'analysis' => $analysis,
        ]);
    }

    /**
     * Lista analisi pagine recenti - GET AJAX
     */
    public function listPageAnalyses(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $limit = min((int) ($_GET['limit'] ?? 10), 50);
        $analyses = $this->pageAnalyzer->getRecentAnalyses($projectId, $limit);

        return View::json([
            'success' => true,
            'analyses' => $analyses,
        ]);
    }

    /**
     * Ottieni costo analisi pagina - GET AJAX
     */
    public function getPageAnalysisCost(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $cost = $this->pageAnalyzer->getCreditCost();
        $balance = Credits::getBalance($user['id']);
        $isConfigured = $this->pageAnalyzer->isConfigured();

        return View::json([
            'success' => true,
            'cost' => $cost,
            'balance' => $balance,
            'can_analyze' => $isConfigured && $balance >= $cost,
            'is_configured' => $isConfigured,
        ]);
    }
}
