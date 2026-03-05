<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\ScriptRun;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\Ad;
use Modules\AdsAnalyzer\Models\Extension;
use Modules\AdsAnalyzer\Models\CampaignAdGroup;
use Modules\AdsAnalyzer\Models\AdGroupKeyword;
use Modules\AdsAnalyzer\Models\CampaignEvaluation;
use Modules\AdsAnalyzer\Services\CampaignEvaluatorService;
use Modules\AdsAnalyzer\Services\MetricComparisonService;
use Core\Logger;

class CampaignController
{
    /**
     * Dashboard progetto campagne
     */
    public function dashboard(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        // Prendi tutti i run con dati campagna
        $runs = ScriptRun::getByProject($projectId, 10);
        $campaignRuns = array_values(array_filter($runs, fn($r) =>
            in_array($r['run_type'], ['campaign_performance', 'both']) && $r['status'] === 'completed'
        ));

        $latestRun = !empty($campaignRuns) ? reset($campaignRuns) : null;
        $latestStats = $latestRun ? Campaign::getStatsByRun($latestRun['id']) : [];

        // Valutazioni recenti
        $evaluations = CampaignEvaluation::getByProject($projectId, 10);

        // Conteggi generali
        $totalCampaigns = 0;
        $totalAds = 0;
        if ($latestRun) {
            $totalCampaigns = count(Campaign::getByRun($latestRun['id']));
            $totalAds = count(Ad::getByRun($latestRun['id']));
        }

        // Ultima valutazione completata CON AI response reale (per Health Score)
        $latestEvalWithAi = CampaignEvaluation::getLatestWithAiByProject($projectId);
        $latestAiResponse = $latestEvalWithAi ? json_decode($latestEvalWithAi['ai_response'] ?? '{}', true) : null;

        // Ultima eval in assoluto (per link dettagli, può essere no_change)
        $latestEval = $latestEvalWithAi ?: CampaignEvaluation::getLatestByProject($projectId);

        // KPI deltas (confronto con run precedente)
        $kpiDeltas = null;
        if ($latestRun && count($campaignRuns) >= 2) {
            $previousRun = $campaignRuns[1] ?? null;
            if ($previousRun) {
                $previousStats = Campaign::getStatsByRun($previousRun['id']);
                $kpiDeltas = MetricComparisonService::computeDeltas($latestStats, $previousStats);
            }
        }

        // Auto-eval status
        $autoEvalEnabled = (bool)($project['auto_evaluate'] ?? false);

        // Trend storico KPI (tutti i run completati, ordine cronologico)
        $kpiTrend = [];
        foreach (array_reverse($campaignRuns) as $run) {
            $runStats = ($run['id'] == ($latestRun['id'] ?? 0)) ? $latestStats : Campaign::getStatsByRun($run['id']);
            $kpiTrend[] = [
                'date' => $run['date_range_end'] ?? date('Y-m-d', strtotime($run['created_at'])),
                'label' => date('d/m', strtotime($run['date_range_end'] ?? $run['created_at'])),
                'clicks' => (int)($runStats['total_clicks'] ?? 0),
                'cost' => round((float)($runStats['total_cost'] ?? 0), 2),
                'conversions' => round((float)($runStats['total_conversions'] ?? 0), 1),
                'ctr' => round((float)($runStats['avg_ctr'] ?? 0) * 100, 2),
            ];
        }

        return View::render('ads-analyzer/campaigns/dashboard', [
            'title' => $project['name'] . ' - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'campaignRuns' => $campaignRuns,
            'latestRun' => $latestRun,
            'latestStats' => $latestStats,
            'evaluations' => $evaluations,
            'totalCampaigns' => $totalCampaigns,
            'totalAds' => $totalAds,
            'latestEval' => $latestEval,
            'latestEvalWithAi' => $latestEvalWithAi,
            'latestAiResponse' => $latestAiResponse,
            'kpiDeltas' => $kpiDeltas,
            'kpiTrend' => $kpiTrend,
            'autoEvalEnabled' => $autoEvalEnabled,
            'currentPage' => 'dashboard',
            'userCredits' => Credits::getBalance($user['id']),
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * Lista dati campagne raggruppati per run
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        // Prendi tutti i run con dati campagna
        $runs = ScriptRun::getByProject($projectId, 20);
        $campaignRuns = array_filter($runs, fn($r) =>
            in_array($r['run_type'], ['campaign_performance', 'both']) && $r['status'] === 'completed'
        );

        // Stats per l'ultimo run
        $latestRun = !empty($campaignRuns) ? reset($campaignRuns) : null;
        $latestStats = $latestRun ? Campaign::getStatsByRun($latestRun['id']) : [];
        $latestAdStats = $latestRun ? Ad::getStatsByRun($latestRun['id']) : [];

        // Valutazioni recenti
        $evaluations = CampaignEvaluation::getByProject($projectId, 5);

        // Lista campagne per modale selezione
        $campaignsList = [];
        if ($latestRun) {
            $allCampaigns = Campaign::getByRun($latestRun['id']);
            $campaignsList = array_map(fn($c) => [
                'id_google' => $c['campaign_id_google'],
                'name' => $c['campaign_name'],
                'status' => $c['campaign_status'] ?? 'UNKNOWN',
                'type' => $c['campaign_type'] ?? 'SEARCH',
                'cost' => (float)$c['cost'],
                'clicks' => (int)$c['clicks'],
                'conversions' => (float)$c['conversions'],
            ], $allCampaigns);
        }

        return View::render('ads-analyzer/campaigns/index', [
            'title' => 'Dati Campagne - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'campaignRuns' => array_values($campaignRuns),
            'latestRun' => $latestRun,
            'latestStats' => $latestStats,
            'latestAdStats' => $latestAdStats,
            'evaluations' => $evaluations,
            'campaignsList' => $campaignsList,
            'userCredits' => Credits::getBalance($user['id']),
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * Dettaglio run: campagne, annunci, estensioni
     */
    public function show(int $projectId, int $runId): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        $run = ScriptRun::find($runId);
        if (!$run || $run['project_id'] != $projectId) {
            $_SESSION['_flash']['error'] = 'Esecuzione non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns"));
            exit;
        }

        $campaigns = Campaign::getByRun($runId);
        $ads = Ad::getByRun($runId);
        $extensions = Extension::getByRunGrouped($runId);
        $campaignStats = Campaign::getStatsByRun($runId);
        $adStats = Ad::getStatsByRun($runId);

        // Raggruppa annunci per campagna
        $adsByCampaign = [];
        foreach ($ads as $ad) {
            $key = $ad['campaign_name'] ?? 'Sconosciuta';
            $adsByCampaign[$key][] = $ad;
        }

        return View::render('ads-analyzer/campaigns/show', [
            'title' => 'Dettaglio Run - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'run' => $run,
            'campaigns' => $campaigns,
            'adsByCampaign' => $adsByCampaign,
            'extensions' => $extensions,
            'campaignStats' => $campaignStats,
            'adStats' => $adStats,
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * Avvia valutazione AI campagne
     */
    public function evaluate(int $projectId): void
    {
        // Operazione lunga: scraping + AI (pattern da ai-content WizardController)
        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $projectId);

            if (!$project) {
                ob_end_clean();
                jsonResponse(['error' => 'Progetto non trovato'], 404);
            }

            // Viewer cannot perform write operations
            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                jsonResponse(['error' => 'Non hai i permessi per questa operazione'], 403);
            }

            if (($project['type'] ?? 'negative-kw') !== 'campaign') {
                ob_end_clean();
                jsonResponse(['error' => 'Operazione non disponibile per questo tipo di progetto'], 400);
            }

            // Determina run da valutare
            $runId = (int) ($_POST['run_id'] ?? 0);
            if ($runId) {
                $run = ScriptRun::find($runId);
            } else {
                $run = ScriptRun::getLatestByProject($projectId, 'campaign_performance');
            }

            if (!$run) {
                ob_end_clean();
                jsonResponse(['error' => 'Nessun dato campagne disponibile. Esegui prima lo script Google Ads.'], 400);
            }

            // Route credits to project owner
            $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

            // Verifica crediti
            $cost = Credits::getCost('campaign_evaluation', 'ads-analyzer', 7);
            if (!Credits::hasEnough($creditUserId, $cost)) {
                ob_end_clean();
                jsonResponse(['error' => "Crediti insufficienti. Necessari: {$cost}"], 400);
            }

            // Filtro campagne (selezione utente)
            $campaignsFilter = null;
            if (!empty($_POST['campaigns_filter'])) {
                $campaignsFilter = json_decode($_POST['campaigns_filter'], true);
                if (!is_array($campaignsFilter)) {
                    $campaignsFilter = null;
                }
                if ($campaignsFilter && count($campaignsFilter) > 15) {
                    $campaignsFilter = array_slice($campaignsFilter, 0, 15);
                }
            }

            // Carica dati
            $campaigns = Campaign::getByRun($run['id']);
            $ads = Ad::getByRun($run['id']);
            $extensions = Extension::getByRun($run['id']);
            $adGroupsData = CampaignAdGroup::getByRun($run['id']);
            $keywordsData = AdGroupKeyword::getByRun($run['id']);

            if (empty($campaigns)) {
                ob_end_clean();
                jsonResponse(['error' => 'Nessuna campagna trovata nel run selezionato'], 400);
            }

            // Chiudi sessione per non bloccare altre request
            session_write_close();

            // Crea record valutazione
            $evalId = CampaignEvaluation::create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'run_id' => $run['id'],
                'name' => 'Valutazione ' . date('d/m/Y H:i'),
                'campaigns_evaluated' => count($campaigns),
                'ads_evaluated' => count($ads),
                'ad_groups_evaluated' => count($adGroupsData),
                'keywords_evaluated' => count($keywordsData),
                'landing_pages_analyzed' => 0,
                'campaigns_filter' => $campaignsFilter ? json_encode($campaignsFilter) : null,
                'status' => 'analyzing',
            ]);

            // Scraping landing pages (max 5 URL uniche dagli annunci)
            $landingContexts = [];
            $uniqueUrls = [];
            foreach ($ads as $ad) {
                $url = $ad['final_url'] ?? '';
                if (!empty($url) && !isset($uniqueUrls[$url]) && count($uniqueUrls) < 5) {
                    $uniqueUrls[$url] = true;
                }
            }

            if (!empty($uniqueUrls)) {
                require_once __DIR__ . '/../../../services/ScraperService.php';
                $scraper = new \Services\ScraperService();
                foreach (array_keys($uniqueUrls) as $url) {
                    try {
                        $scraped = $scraper->scrape($url);
                        Database::reconnect();
                        if (!empty($scraped['content'])) {
                            // Limita contenuto per non esplodere il prompt
                            $content = mb_substr($scraped['content'], 0, 3000);
                            $landingContexts[$url] = "Titolo: " . ($scraped['title'] ?? 'N/D')
                                . "\nWord count: " . ($scraped['word_count'] ?? 0)
                                . "\nContenuto: " . $content;
                        }
                    } catch (\Exception $e) {
                        Logger::channel('ai')->warning("Landing scrape failed", ['url' => $url, 'error' => $e->getMessage()]);
                    }
                }
            }

            $landingPagesAnalyzed = count($landingContexts);

            set_time_limit(0);
            $evaluator = new CampaignEvaluatorService();
            $aiResult = $evaluator->evaluate(
                $user['id'],
                $campaigns,
                $ads,
                $extensions,
                $landingContexts,
                $adGroupsData,
                $keywordsData,
                $campaignsFilter
            );

            Database::reconnect();

            // Salva risultato
            CampaignEvaluation::update($evalId, [
                'ai_response' => json_encode($aiResult, JSON_UNESCAPED_UNICODE),
                'credits_used' => $cost,
                'landing_pages_analyzed' => $landingPagesAnalyzed,
            ]);
            CampaignEvaluation::updateStatus($evalId, 'completed');

            // Consuma crediti
            Credits::consume($creditUserId, $cost, 'campaign_evaluation', 'ads-analyzer', [
                'run_id' => $run['id'],
                'campaigns' => count($campaigns),
                'ad_groups' => count($adGroupsData),
                'keywords' => count($keywordsData),
                'landing_pages' => 0,
            ]);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'evaluation_id' => $evalId,
                'redirect' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}"),
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ai')->error("Campaign evaluation error", ['error' => $e->getMessage()]);

            if (isset($evalId)) {
                Database::reconnect();
                CampaignEvaluation::updateStatus($evalId, 'error', $e->getMessage());
            }

            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Toggle auto-valutazione per progetto
     */
    public function toggleAutoEvaluate(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Operazione non disponibile']);
            exit;
        }

        $current = (bool)($project['auto_evaluate'] ?? false);
        Project::update($projectId, ['auto_evaluate' => $current ? 0 : 1]);

        echo json_encode([
            'success' => true,
            'auto_evaluate' => !$current,
        ]);
        exit;
    }

    /**
     * Run disponibili per selettore periodo (AJAX)
     */
    public function availableRuns(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Progetto non valido']);
            exit;
        }

        $runs = ScriptRun::getCompletedCampaignRuns($projectId, 30);

        $periods = ['7' => null, '14' => null, '30' => null];
        foreach ($runs as $run) {
            $days = (int)($run['period_days'] ?? 0);
            $bucket = null;
            if ($days >= 5 && $days <= 9) $bucket = '7';
            elseif ($days >= 12 && $days <= 16) $bucket = '14';
            elseif ($days >= 25 && $days <= 35) $bucket = '30';

            if ($bucket && $periods[$bucket] === null) {
                $periods[$bucket] = [
                    'available' => true,
                    'run_id' => (int)$run['id'],
                    'date_start' => $run['date_range_start'],
                    'date_end' => $run['date_range_end'],
                    'period_days' => $days,
                    'has_evaluation' => !empty($run['evaluation_id']),
                    'evaluation_id' => $run['evaluation_id'] ? (int)$run['evaluation_id'] : null,
                ];
            }
        }

        foreach ($periods as $key => &$val) {
            if ($val === null) {
                $val = ['available' => false, 'run_id' => null, 'has_evaluation' => false, 'evaluation_id' => null];
            }
        }

        echo json_encode(['periods' => $periods]);
        exit;
    }

    /**
     * Mostra risultato valutazione AI
     */
    public function evaluationShow(int $projectId, int $evalId): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        $evaluation = CampaignEvaluation::find($evalId);
        if (!$evaluation || $evaluation['project_id'] != $projectId) {
            $_SESSION['_flash']['error'] = 'Valutazione non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns"));
            exit;
        }

        $aiResponse = json_decode($evaluation['ai_response'] ?? '{}', true) ?: [];

        // Run info for this evaluation
        $currentRun = null;
        if (!empty($evaluation['run_id'])) {
            $currentRun = ScriptRun::find($evaluation['run_id']);
        }

        // Available runs for period selector
        $availableRuns = ScriptRun::getCompletedCampaignRuns($projectId, 30);
        $periods = ['7' => null, '14' => null, '30' => null];
        $currentPeriod = null;

        foreach ($availableRuns as $run) {
            $days = (int)($run['period_days'] ?? 0);
            $bucket = null;
            if ($days >= 5 && $days <= 9) $bucket = '7';
            elseif ($days >= 12 && $days <= 16) $bucket = '14';
            elseif ($days >= 25 && $days <= 35) $bucket = '30';

            if ($bucket && $periods[$bucket] === null) {
                $periods[$bucket] = [
                    'available' => true,
                    'run_id' => (int)$run['id'],
                    'has_evaluation' => !empty($run['evaluation_id']),
                    'evaluation_id' => $run['evaluation_id'] ? (int)$run['evaluation_id'] : null,
                ];
            }

            if ($currentRun && (int)$run['id'] === (int)$currentRun['id'] && $bucket) {
                $currentPeriod = $bucket;
            }
        }

        foreach ($periods as $key => &$val) {
            if ($val === null) {
                $val = ['available' => false, 'run_id' => null, 'has_evaluation' => false, 'evaluation_id' => null];
            }
        }

        return View::render('ads-analyzer/campaigns/evaluation', [
            'title' => 'Valutazione Campagne - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'evaluation' => $evaluation,
            'aiResponse' => $aiResponse,
            'generateUrl' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}/generate"),
            'access_role' => $project['access_role'] ?? 'owner',
            'currentRun' => $currentRun,
            'periods' => $periods,
            'currentPeriod' => $currentPeriod,
        ]);
    }

    /**
     * Genera contenuto AI per fix issue/suggerimento dalla valutazione (AJAX lungo)
     */
    public function generateFix(int $projectId, int $evalId): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $projectId);

            if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Progetto non valido']);
                exit;
            }

            // Viewer cannot perform write operations
            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
                exit;
            }

            $evaluation = CampaignEvaluation::find($evalId);
            if (!$evaluation || $evaluation['project_id'] != $projectId || $evaluation['status'] !== 'completed') {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['error' => 'Valutazione non trovata']);
                exit;
            }

            $type = $_POST['type'] ?? '';
            $context = json_decode($_POST['context'] ?? '{}', true) ?: [];

            $allowedTypes = ['extensions', 'copy', 'keywords'];
            if (!in_array($type, $allowedTypes)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Tipo generazione non valido']);
                exit;
            }

            // Route credits to project owner
            $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

            // AiService::complete() verifica e consuma crediti internamente
            if (!Credits::hasEnough($creditUserId, 1)) {
                ob_end_clean();
                echo json_encode(['error' => 'Crediti insufficienti. Necessario almeno 1 credito.']);
                exit;
            }

            session_write_close();

            // Carica dati campagna (stessi model usati in evaluate())
            $runId = $evaluation['run_id'];
            $campaignData = [
                'campaigns' => Campaign::getByRun($runId),
                'ads' => Ad::getByRun($runId),
                'extensions' => Extension::getByRun($runId),
                'keywords' => AdGroupKeyword::getByRun($runId),
                'business_context' => $project['business_context'] ?? '',
            ];

            set_time_limit(0);

            require_once __DIR__ . '/../services/EvaluationGeneratorService.php';
            $service = new \Modules\AdsAnalyzer\Services\EvaluationGeneratorService();
            $result = $service->generate($user['id'], $type, $context, $campaignData);

            Database::reconnect();

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'type' => $type,
                'data' => $result,
                'content' => self::formatFixForDisplay($type, $result),
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ai')->error("Evaluation generateFix error", ['error' => $e->getMessage()]);

            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Export PDF della valutazione campagne
     */
    public function exportPdf(int $projectId, int $evalId): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            $_SESSION['_flash']['error'] = 'Operazione non disponibile per questo tipo di progetto';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        $evaluation = CampaignEvaluation::find($evalId);
        if (!$evaluation || $evaluation['project_id'] != $projectId || $evaluation['status'] !== 'completed') {
            $_SESSION['_flash']['error'] = 'Valutazione non trovata o non completata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns"));
            exit;
        }

        $aiResponse = json_decode($evaluation['ai_response'] ?? '{}', true) ?: [];

        if (empty($aiResponse)) {
            $_SESSION['_flash']['error'] = 'Nessun dato AI disponibile per questa valutazione';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}"));
            exit;
        }

        require_once __DIR__ . '/../services/EvaluationPdfService.php';
        $service = new \Modules\AdsAnalyzer\Services\EvaluationPdfService();

        try {
            $pdfContent = $service->generate($evaluation, $aiResponse, $project);
        } catch (\Exception $e) {
            Logger::channel('ai')->error("PDF export error", ['error' => $e->getMessage()]);
            $_SESSION['_flash']['error'] = 'Errore nella generazione del PDF: ' . $e->getMessage();
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}"));
            exit;
        }

        // Genera filename: report-{slug}-{date}.pdf
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($project['name'] ?? 'progetto'));
        $slug = trim($slug, '-');
        $dateStr = date('Y-m-d', strtotime($evaluation['created_at'] ?? 'now'));
        $filename = "report-{$slug}-{$dateStr}.pdf";

        // Pulisci buffer output per garantire header puliti
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
        exit;
    }

    /**
     * Export CSV Google Ads Editor da fix AI generato
     */
    public function exportCsv(int $projectId, int $evalId): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Progetto non valido']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        $type = $_POST['type'] ?? '';
        $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
        $campaignName = $_POST['campaign_name'] ?? $project['name'] ?? 'Campagna';

        if (empty($type) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati mancanti']);
            exit;
        }

        require_once __DIR__ . '/../services/EvaluationCsvService.php';
        $service = new \Modules\AdsAnalyzer\Services\EvaluationCsvService();
        $csv = $service->generate($type, $data, $campaignName);

        $typeLabel = match (true) {
            str_contains($type, 'copy') => 'copy',
            str_contains($type, 'ext') => 'extensions',
            str_contains($type, 'key') => 'keywords',
            default => 'export',
        };
        $filename = "ads-editor-{$typeLabel}-" . date('Y-m-d') . '.csv';

        // Pulisci buffer output per garantire header puliti
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $csv;
        exit;
    }

    /**
     * Formatta dati strutturati AI in testo leggibile per il bottone "Copia"
     */
    private static function formatFixForDisplay(string $type, array $data): string
    {
        $lines = [];

        if (str_contains($type, 'copy')) {
            $lines[] = "HEADLINES:";
            foreach (($data['headlines'] ?? []) as $i => $h) {
                $lines[] = ($i + 1) . ". " . $h . " (" . mb_strlen($h) . " car.)";
            }
            $lines[] = "";
            $lines[] = "DESCRIPTIONS:";
            foreach (($data['descriptions'] ?? []) as $i => $d) {
                $lines[] = ($i + 1) . ". " . $d . " (" . mb_strlen($d) . " car.)";
            }
            if (!empty($data['paths'])) {
                $lines[] = "";
                $lines[] = "PATHS: /" . ($data['paths']['path1'] ?? '') . " / " . ($data['paths']['path2'] ?? '');
            }
        } elseif (str_contains($type, 'extensions')) {
            if (!empty($data['sitelinks'])) {
                $lines[] = "SITELINKS:";
                foreach ($data['sitelinks'] as $i => $sl) {
                    $lines[] = ($i + 1) . ". " . ($sl['title'] ?? '') . " (" . mb_strlen($sl['title'] ?? '') . " car.)";
                    $lines[] = "   Desc 1: " . ($sl['desc1'] ?? '');
                    $lines[] = "   Desc 2: " . ($sl['desc2'] ?? '');
                    $lines[] = "   URL: " . ($sl['url'] ?? '');
                }
            }
            if (!empty($data['callouts'])) {
                $lines[] = "";
                $lines[] = "CALLOUTS:";
                foreach ($data['callouts'] as $i => $c) {
                    $lines[] = ($i + 1) . ". " . $c . " (" . mb_strlen($c) . " car.)";
                }
            }
            if (!empty($data['structured_snippets'])) {
                $lines[] = "";
                $lines[] = "STRUCTURED SNIPPETS:";
                foreach ($data['structured_snippets'] as $ss) {
                    $lines[] = "Header: " . ($ss['header'] ?? '');
                    $lines[] = "Valori: " . implode(', ', $ss['values'] ?? []);
                }
            }
        } elseif (str_contains($type, 'keywords')) {
            $lines[] = "KEYWORD NEGATIVE:";
            foreach (($data['keywords'] ?? []) as $i => $kw) {
                $match = $kw['match_type'] ?? 'phrase';
                $lines[] = ($i + 1) . ". " . ($kw['keyword'] ?? '') . " [{$match}]" . (!empty($kw['reason']) ? " - " . $kw['reason'] : '');
            }
        }

        return implode("\n", $lines);
    }
}
