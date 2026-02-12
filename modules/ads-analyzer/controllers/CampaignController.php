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
use Modules\AdsAnalyzer\Services\ContextExtractorService;

class CampaignController
{
    /**
     * Dashboard progetto campagne
     */
    public function dashboard(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
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
        $evaluations = CampaignEvaluation::getByProject($projectId, 5);

        // Conteggi generali
        $totalCampaigns = 0;
        $totalAds = 0;
        if ($latestRun) {
            $totalCampaigns = count(Campaign::getByRun($latestRun['id']));
            $totalAds = count(Ad::getByRun($latestRun['id']));
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
            'currentPage' => 'dashboard',
            'userCredits' => Credits::getBalance($user['id']),
        ]);
    }

    /**
     * Lista dati campagne raggruppati per run
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
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
            'userCredits' => Credits::getBalance($user['id']),
        ]);
    }

    /**
     * Dettaglio run: campagne, annunci, estensioni
     */
    public function show(int $projectId, int $runId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        $run = ScriptRun::find($runId);
        if (!$run || $run['project_id'] != $projectId) {
            $_SESSION['flash_error'] = 'Esecuzione non trovata';
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
            $project = Project::findByUserAndId($user['id'], $projectId);

            if (!$project) {
                jsonResponse(['error' => 'Progetto non trovato'], 404);
            }

            if (($project['type'] ?? 'negative-kw') !== 'campaign') {
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
                jsonResponse(['error' => 'Nessun dato campagne disponibile. Esegui prima lo script Google Ads.'], 400);
            }

            // Verifica crediti
            $cost = Credits::getCost('campaign_evaluation', 'ads-analyzer', 7);
            if (!Credits::hasEnough($user['id'], $cost)) {
                jsonResponse(['error' => "Crediti insufficienti. Necessari: {$cost}"], 400);
            }

            // Carica dati
            $campaigns = Campaign::getByRun($run['id']);
            $ads = Ad::getByRun($run['id']);
            $extensions = Extension::getByRun($run['id']);
            $adGroupsData = CampaignAdGroup::getByRun($run['id']);
            $keywordsData = AdGroupKeyword::getByRun($run['id']);

            if (empty($campaigns)) {
                jsonResponse(['error' => 'Nessuna campagna trovata nel run selezionato'], 400);
            }

            // Chiudi sessione per non bloccare altre request
            session_write_close();

            // Debug: scrive direttamente su file (error_log non funziona dal web)
            $debugLog = __DIR__ . '/../../../../storage/logs/campaign_eval_debug.log';
            file_put_contents($debugLog, date('H:i:s') . " START project={$projectId}\n", FILE_APPEND);

            error_log("=== CAMPAIGN EVAL: START project={$projectId} ===");

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
                'status' => 'analyzing',
            ]);

            file_put_contents($debugLog, date('H:i:s') . " record created id={$evalId}\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: record created id={$evalId} ===");

            // Fase A: Scraping + AI estrazione contesto landing pages (best effort)
            $landingContexts = [];
            $uniqueUrls = Ad::getUniqueUrls($run['id']);

            file_put_contents($debugLog, date('H:i:s') . " " . count($uniqueUrls) . " unique URLs found\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: " . count($uniqueUrls) . " unique URLs found ===");

            $extractor = new ContextExtractorService();
            $landingCount = 0;

            file_put_contents($debugLog, date('H:i:s') . " ContextExtractorService created, starting loop\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: ContextExtractorService created, starting loop ===");

            // Max 10 URL, ordinati per quelli usati da piu annunci
            foreach (array_slice($uniqueUrls, 0, 10) as $i => $urlRow) {
                $url = $urlRow['final_url'];
                file_put_contents($debugLog, date('H:i:s') . " scraping URL " . ($i + 1) . ": {$url}\n", FILE_APPEND);
                error_log("=== CAMPAIGN EVAL: scraping URL " . ($i + 1) . ": {$url} ===");
                try {
                    set_time_limit(0);
                    $result = $extractor->extractFromUrl($user['id'], $url, 'campaign');
                    Database::reconnect();

                    if ($result['success']) {
                        $landingContexts[$url] = $result['extracted_context'];
                        $landingCount++;
                        file_put_contents($debugLog, date('H:i:s') . " URL " . ($i + 1) . " OK\n", FILE_APPEND);
                        error_log("=== CAMPAIGN EVAL: URL " . ($i + 1) . " OK, context extracted ===");
                    } else {
                        file_put_contents($debugLog, date('H:i:s') . " URL " . ($i + 1) . " FAILED: " . ($result['error'] ?? 'unknown') . "\n", FILE_APPEND);
                        error_log("=== CAMPAIGN EVAL: URL " . ($i + 1) . " FAILED: " . ($result['error'] ?? 'unknown') . " ===");
                    }
                } catch (\Exception $e) {
                    file_put_contents($debugLog, date('H:i:s') . " URL " . ($i + 1) . " EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
                    error_log("=== CAMPAIGN EVAL: URL " . ($i + 1) . " EXCEPTION: " . $e->getMessage() . " ===");
                }
            }

            file_put_contents($debugLog, date('H:i:s') . " scraping done, {$landingCount} contexts\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: scraping done, {$landingCount} contexts extracted ===");

            // Aggiorna contatore landing nel record evaluation
            Database::reconnect();
            CampaignEvaluation::update($evalId, [
                'landing_pages_analyzed' => $landingCount,
            ]);

            // Fase B: Valutazione AI completa (metriche + landing + copy + keyword)
            file_put_contents($debugLog, date('H:i:s') . " starting AI evaluation\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: starting AI evaluation ===");
            set_time_limit(0);
            $evaluator = new CampaignEvaluatorService();
            $aiResult = $evaluator->evaluate(
                $user['id'],
                $campaigns,
                $ads,
                $extensions,
                $landingContexts,
                $adGroupsData,
                $keywordsData
            );

            file_put_contents($debugLog, date('H:i:s') . " AI evaluation done\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: AI evaluation done ===");
            Database::reconnect();

            // Salva risultato
            CampaignEvaluation::update($evalId, [
                'ai_response' => json_encode($aiResult, JSON_UNESCAPED_UNICODE),
                'credits_used' => $cost,
            ]);
            CampaignEvaluation::updateStatus($evalId, 'completed');
            file_put_contents($debugLog, date('H:i:s') . " COMPLETED evalId={$evalId}\n", FILE_APPEND);
            error_log("=== CAMPAIGN EVAL: COMPLETED evalId={$evalId} ===");

            // Consuma crediti
            Credits::consume($user['id'], $cost, 'campaign_evaluation', 'ads-analyzer', [
                'run_id' => $run['id'],
                'campaigns' => count($campaigns),
                'ad_groups' => count($adGroupsData),
                'keywords' => count($keywordsData),
                'landing_pages' => $landingCount,
            ]);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'evaluation_id' => $evalId,
                'redirect' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}"),
            ]);
            exit;

        } catch (\Exception $e) {
            $debugLog = $debugLog ?? __DIR__ . '/../../../../storage/logs/campaign_eval_debug.log';
            file_put_contents($debugLog, date('H:i:s') . " EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            error_log("Campaign evaluation error: " . $e->getMessage());

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
     * Mostra risultato valutazione AI
     */
    public function evaluationShow(int $projectId, int $evalId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }

        $evaluation = CampaignEvaluation::find($evalId);
        if (!$evaluation || $evaluation['project_id'] != $projectId) {
            $_SESSION['flash_error'] = 'Valutazione non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns"));
            exit;
        }

        $aiResponse = json_decode($evaluation['ai_response'] ?? '{}', true) ?: [];

        return View::render('ads-analyzer/campaigns/evaluation', [
            'title' => 'Valutazione Campagne - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'evaluation' => $evaluation,
            'aiResponse' => $aiResponse,
        ]);
    }
}
