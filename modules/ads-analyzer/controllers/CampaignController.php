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
use Modules\AdsAnalyzer\Models\CampaignEvaluation;
use Modules\AdsAnalyzer\Services\CampaignEvaluatorService;
use Modules\AdsAnalyzer\Services\ContextExtractorService;

class CampaignController
{
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
        try {
            $user = Auth::user();
            $project = Project::findByUserAndId($user['id'], $projectId);

            if (!$project) {
                jsonResponse(['error' => 'Progetto non trovato'], 404);
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
            $cost = Credits::getCost('campaign_evaluation', 'ads-analyzer', 3);
            if (!Credits::hasEnough($user['id'], $cost)) {
                jsonResponse(['error' => "Crediti insufficienti. Necessari: {$cost}"], 400);
            }

            // Carica dati
            $campaigns = Campaign::getByRun($run['id']);
            $ads = Ad::getByRun($run['id']);
            $extensions = Extension::getByRun($run['id']);

            if (empty($campaigns)) {
                jsonResponse(['error' => 'Nessuna campagna trovata nel run selezionato'], 400);
            }

            // Crea record valutazione
            $evalId = CampaignEvaluation::create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'run_id' => $run['id'],
                'name' => 'Valutazione ' . date('d/m/Y H:i'),
                'campaigns_evaluated' => count($campaigns),
                'ads_evaluated' => count($ads),
                'status' => 'analyzing',
            ]);

            // Estrai contesto landing pages (opzionale, best effort)
            $landingContexts = [];
            $uniqueUrls = Ad::getUniqueUrls($run['id']);
            $extractor = new ContextExtractorService();

            foreach (array_slice($uniqueUrls, 0, 5) as $urlRow) {
                $url = $urlRow['final_url'];
                try {
                    $result = $extractor->extractFromUrl($user['id'], $url);
                    if ($result['success']) {
                        $landingContexts[$url] = $result['extracted_context'];
                    }
                } catch (\Exception $e) {
                    // Ignora errori scraping, non blocca la valutazione
                    error_log("Landing scrape failed for {$url}: " . $e->getMessage());
                }
            }

            Database::reconnect();

            // Valutazione AI
            $evaluator = new CampaignEvaluatorService();
            $aiResult = $evaluator->evaluate($user['id'], $campaigns, $ads, $extensions, $landingContexts);

            Database::reconnect();

            // Salva risultato
            CampaignEvaluation::update($evalId, [
                'ai_response' => json_encode($aiResult, JSON_UNESCAPED_UNICODE),
                'credits_used' => $cost,
            ]);
            CampaignEvaluation::updateStatus($evalId, 'completed');

            // Consuma crediti
            Credits::consume($user['id'], $cost, 'campaign_evaluation', 'ads-analyzer', [
                'run_id' => $run['id'],
                'campaigns' => count($campaigns),
            ]);

            jsonResponse([
                'success' => true,
                'evaluation_id' => $evalId,
                'redirect' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}"),
            ]);

        } catch (\Exception $e) {
            error_log("Campaign evaluation error: " . $e->getMessage());

            if (isset($evalId)) {
                CampaignEvaluation::updateStatus($evalId, 'error', $e->getMessage());
            }

            jsonResponse(['error' => 'Errore: ' . $e->getMessage()], 500);
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
