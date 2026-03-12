<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\Sync;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\Ad;
use Modules\AdsAnalyzer\Models\Extension;
use Modules\AdsAnalyzer\Models\CampaignAdGroup;
use Modules\AdsAnalyzer\Models\AdGroupKeyword;
use Modules\AdsAnalyzer\Models\CampaignEvaluation;
use Modules\AdsAnalyzer\Services\CampaignEvaluatorService;
use Modules\AdsAnalyzer\Services\CampaignSyncService;
use Modules\AdsAnalyzer\Services\MetricComparisonService;
use Services\GoogleAdsService;
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

        // Date range dai parametri (default ultimi 7 giorni)
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // Prendi tutti i sync completati
        $syncs = Sync::getByProject($projectId, 10);
        $campaignSyncs = array_values(array_filter($syncs, fn($s) =>
            $s['status'] === 'completed'
        ));

        $latestSync = Sync::getLatestByProject($projectId);
        $latestStats = $latestSync ? Campaign::getStatsByRun($latestSync['id']) : [];

        // Valutazioni recenti
        $evaluations = CampaignEvaluation::getByProject($projectId, 10);

        // Conteggi generali
        $totalCampaigns = 0;
        $totalAds = 0;
        if ($latestSync) {
            $totalCampaigns = count(Campaign::getByRun($latestSync['id']));
            $totalAds = count(Ad::getByRun($latestSync['id']));
        }

        // Ultima valutazione completata CON AI response reale (per Health Score)
        $latestEvalWithAi = CampaignEvaluation::getLatestWithAiByProject($projectId);
        $latestAiResponse = $latestEvalWithAi ? json_decode($latestEvalWithAi['ai_response'] ?? '{}', true) : null;

        // Ultima eval in assoluto (per link dettagli, può essere no_change)
        $latestEval = $latestEvalWithAi ?: CampaignEvaluation::getLatestByProject($projectId);

        // KPI deltas (confronto con sync precedente)
        $kpiDeltas = null;
        if ($latestSync && count($campaignSyncs) >= 2) {
            $previousSync = $campaignSyncs[1] ?? null;
            if ($previousSync) {
                $previousStats = Campaign::getStatsByRun($previousSync['id']);
                $kpiDeltas = MetricComparisonService::computeDeltas($latestStats, $previousStats);
            }
        }

        // Auto-eval status
        $autoEvalEnabled = (bool)($project['auto_evaluate'] ?? false);

        // Trend storico KPI (tutti i sync completati, ordine cronologico)
        $kpiTrend = [];
        foreach (array_reverse($campaignSyncs) as $sync) {
            $syncStats = ($sync['id'] == ($latestSync['id'] ?? 0)) ? $latestStats : Campaign::getStatsByRun($sync['id']);
            $kpiTrend[] = [
                'date' => $sync['date_range_end'] ?? date('Y-m-d', strtotime($sync['started_at'] ?? $sync['created_at'] ?? 'now')),
                'label' => date('d/m', strtotime($sync['date_range_end'] ?? $sync['started_at'] ?? $sync['created_at'] ?? 'now')),
                'clicks' => (int)($syncStats['total_clicks'] ?? 0),
                'cost' => round((float)($syncStats['total_cost'] ?? 0), 2),
                'conversions' => round((float)($syncStats['total_conversions'] ?? 0), 1),
                'ctr' => round((float)($syncStats['avg_ctr'] ?? 0), 2),
            ];
        }

        return View::render('ads-analyzer/campaigns/dashboard', [
            'title' => $project['name'] . ' - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'campaignSyncs' => $campaignSyncs,
            'latestSync' => $latestSync,
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
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'currentPage' => 'dashboard',
            'userCredits' => Credits::getBalance($user['id']),
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * Lista dati campagne raggruppati per sync
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

        // Prendi tutti i sync completati
        $syncs = Sync::getByProject($projectId, 20);
        $campaignSyncs = array_filter($syncs, fn($s) =>
            $s['status'] === 'completed'
        );

        // Stats per l'ultimo sync
        $latestSync = !empty($campaignSyncs) ? reset($campaignSyncs) : null;
        $latestStats = $latestSync ? Campaign::getStatsByRun($latestSync['id']) : [];
        $latestAdStats = $latestSync ? Ad::getStatsByRun($latestSync['id']) : [];

        // Valutazioni recenti
        $evaluations = CampaignEvaluation::getByProject($projectId, 5);

        // Lista campagne per modale selezione
        $campaignsList = [];
        if ($latestSync) {
            $allCampaigns = Campaign::getByRun($latestSync['id']);
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
            'campaignSyncs' => array_values($campaignSyncs),
            'latestSync' => $latestSync,
            'latestRun' => $latestSync, // alias per compatibilità view
            'latestStats' => $latestStats,
            'latestAdStats' => $latestAdStats,
            'evaluations' => $evaluations,
            'campaignsList' => $campaignsList,
            'userCredits' => Credits::getBalance($user['id']),
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * Sincronizza dati campagne da Google Ads API (AJAX lungo)
     */
    public function sync(int $projectId): void
    {
        ignore_user_abort(true);
        set_time_limit(300);

        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $projectId);

            if (!$project) {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['error' => 'Progetto non trovato']);
                exit;
            }

            // Viewer cannot perform write operations
            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
                exit;
            }

            if (($project['type'] ?? 'negative-kw') !== 'campaign') {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Operazione non disponibile per questo tipo di progetto']);
                exit;
            }

            // Verifica che il progetto abbia un account Google Ads collegato
            $customerId = $project['google_ads_customer_id'] ?? '';
            if (empty($customerId)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Nessun account Google Ads collegato. Vai nelle impostazioni per collegarlo.']);
                exit;
            }

            // Verifica se un sync è già in corso
            $runningSync = Sync::getRunningSync($projectId);
            if ($runningSync) {
                ob_end_clean();
                http_response_code(409);
                echo json_encode(['error' => 'Una sincronizzazione è già in corso. Attendi il completamento.']);
                exit;
            }

            // Date range dal POST (default ultimi 7 giorni)
            $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_POST['date_to'] ?? date('Y-m-d');

            // Validazione formato date (prevenzione GAQL injection)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)
                || strtotime($dateFrom) === false || strtotime($dateTo) === false) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Formato date non valido. Usa YYYY-MM-DD.']);
                exit;
            }

            // Chiudi sessione per non bloccare altre request
            session_write_close();

            // Crea servizi (usa login_customer_id dal progetto per account non sotto MCC)
            // login_customer_id: valore = usa come MCC, NULL = account diretto (no header)
            $loginCustomerId = isset($project['login_customer_id']) ? $project['login_customer_id'] : '';
            $gadsService = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);
            $syncService = new CampaignSyncService($gadsService, $projectId);

            // Esegui sync completo
            $result = $syncService->syncAll($dateFrom, $dateTo, $user['id'], 'manual');

            Database::reconnect();

            ob_end_clean();
            echo json_encode([
                'success' => $result['success'] ?? false,
                'sync_id' => $result['sync_id'] ?? null,
                'counts' => $result['counts'] ?? [],
                'error' => $result['error'] ?? null,
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ads')->error("Campaign sync error", ['error' => $e->getMessage()]);

            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante la sincronizzazione: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Stato sincronizzazione corrente (AJAX)
     */
    public function syncStatus(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Progetto non valido']);
            exit;
        }

        $runningSync = Sync::getRunningSync($projectId);
        $latestSync = Sync::getLatestByProject($projectId);

        echo json_encode([
            'is_running' => $runningSync !== null,
            'running_sync' => $runningSync ? [
                'id' => (int)$runningSync['id'],
                'status' => $runningSync['status'],
                'campaigns_synced' => (int)($runningSync['campaigns_synced'] ?? 0),
                'ads_synced' => (int)($runningSync['ads_synced'] ?? 0),
                'keywords_synced' => (int)($runningSync['keywords_synced'] ?? 0),
            ] : null,
            'latest_sync' => $latestSync ? [
                'id' => (int)$latestSync['id'],
                'status' => $latestSync['status'],
                'completed_at' => $latestSync['completed_at'] ?? null,
                'campaigns_synced' => (int)($latestSync['campaigns_synced'] ?? 0),
                'ads_synced' => (int)($latestSync['ads_synced'] ?? 0),
                'keywords_synced' => (int)($latestSync['keywords_synced'] ?? 0),
            ] : null,
        ]);
        exit;
    }

    /**
     * KPI live da Google Ads API (AJAX)
     *
     * Endpoint chiamato dal frontend al cambio periodo.
     * Usa LiveKpiService: API con cache 15min → fallback DB.
     */
    public function liveKpis(int $projectId): void
    {
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $projectId);

            if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
                http_response_code(400);
                echo json_encode(['error' => 'Progetto non valido']);
                exit;
            }

            $customerId = $project['google_ads_customer_id'] ?? '';
            if (empty($customerId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Nessun account Google Ads collegato']);
                exit;
            }

            // Validazione date
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                http_response_code(400);
                echo json_encode(['error' => 'Formato date non valido']);
                exit;
            }

            // Limite massimo 90 giorni
            $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
            if ($daysDiff > 90 || $daysDiff < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Intervallo date non valido (max 90 giorni)']);
                exit;
            }

            $loginCustomerId = isset($project['login_customer_id']) ? $project['login_customer_id'] : '';
            $gadsService = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);

            $service = new \Modules\AdsAnalyzer\Services\LiveKpiService($gadsService, $projectId);
            $kpis = $service->getKpis($dateFrom, $dateTo);

            echo json_encode([
                'success' => true,
                'kpis' => $kpis,
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ads')->error("LiveKPI error", [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Errore nel recupero metriche: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Dettaglio sync: campagne, annunci, estensioni
     */
    public function show(int $projectId, int $syncId): string
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

        $sync = Sync::find($syncId);
        if (!$sync || $sync['project_id'] != $projectId) {
            $_SESSION['_flash']['error'] = 'Sincronizzazione non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns"));
            exit;
        }

        $campaigns = Campaign::getByRun($syncId);
        $ads = Ad::getByRun($syncId);
        $extensions = Extension::getByRunGrouped($syncId);
        $campaignStats = Campaign::getStatsByRun($syncId);
        $adStats = Ad::getStatsByRun($syncId);

        // Raggruppa annunci per campagna
        $adsByCampaign = [];
        foreach ($ads as $ad) {
            $key = $ad['campaign_name'] ?? 'Sconosciuta';
            $adsByCampaign[$key][] = $ad;
        }

        return View::render('ads-analyzer/campaigns/show', [
            'title' => 'Dettaglio Sync - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'sync' => $sync,
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
        set_time_limit(300);

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

            // Determina sync da valutare
            $syncId = (int) ($_POST['sync_id'] ?? 0);
            if ($syncId) {
                $sync = Sync::find($syncId);
            } else {
                $sync = Sync::getLatestByProject($projectId);
            }

            if (!$sync) {
                ob_end_clean();
                jsonResponse(['error' => 'Nessun dato campagne disponibile. Esegui prima una sincronizzazione Google Ads.'], 400);
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

            // Carica dati — solo campagne e ad group attivi
            $campaigns = array_values(array_filter(
                Campaign::getByRun($sync['id']),
                fn($c) => ($c['campaign_status'] ?? '') === 'ENABLED'
            ));
            $ads = Ad::getByRun($sync['id']);
            $extensions = Extension::getByRun($sync['id']);
            $adGroupsData = array_values(array_filter(
                CampaignAdGroup::getByRun($sync['id']),
                fn($ag) => ($ag['ad_group_status'] ?? '') === 'ENABLED'
            ));
            $keywordsData = AdGroupKeyword::getByRun($sync['id']);

            if (empty($campaigns)) {
                ob_end_clean();
                jsonResponse(['error' => 'Nessuna campagna trovata nella sincronizzazione selezionata'], 400);
            }

            // Chiudi sessione per non bloccare altre request
            session_write_close();

            // Crea record valutazione
            $evalId = CampaignEvaluation::create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'sync_id' => $sync['id'],
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

            set_time_limit(300);
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
                'sync_id' => $sync['id'],
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
     * Sync disponibili per selettore periodo (AJAX)
     */
    public function availableSyncs(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Progetto non valido']);
            exit;
        }

        $syncs = Sync::getCompletedSyncs($projectId);

        $result = [];
        foreach ($syncs as $sync) {
            $result[] = [
                'sync_id' => (int)$sync['id'],
                'date_start' => $sync['date_range_start'] ?? null,
                'date_end' => $sync['date_range_end'] ?? null,
                'completed_at' => $sync['completed_at'] ?? null,
                'campaigns_synced' => (int)($sync['campaigns_synced'] ?? 0),
                'ads_synced' => (int)($sync['ads_synced'] ?? 0),
                'keywords_synced' => (int)($sync['keywords_synced'] ?? 0),
            ];
        }

        echo json_encode(['syncs' => $result]);
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

        // Sync info for this evaluation
        $currentSync = null;
        if (!empty($evaluation['sync_id'])) {
            $currentSync = Sync::find($evaluation['sync_id']);
        }

        // Available syncs for period selector
        $availableSyncs = Sync::getCompletedSyncs($projectId);

        return View::render('ads-analyzer/campaigns/evaluation', [
            'title' => 'Valutazione Campagne - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'evaluation' => $evaluation,
            'aiResponse' => $aiResponse,
            'generateUrl' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}/generate"),
            'applyUrl' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}/apply"),
            'access_role' => $project['access_role'] ?? 'owner',
            'currentSync' => $currentSync,
            'availableSyncs' => $availableSyncs,
        ]);
    }

    /**
     * Genera contenuto AI per fix issue/suggerimento dalla valutazione (AJAX lungo)
     */
    public function generateFix(int $projectId, int $evalId): void
    {
        ignore_user_abort(true);
        set_time_limit(300);

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

            // Verifica crediti per generazione fix AI
            $fixCost = Credits::getCost('generate_fix', 'ads-analyzer');
            if (!Credits::hasEnough($creditUserId, $fixCost)) {
                ob_end_clean();
                echo json_encode(['error' => "Crediti insufficienti. Necessari almeno {$fixCost} crediti."]);
                exit;
            }

            session_write_close();

            // Carica dati campagna (stessi model usati in evaluate())
            $syncId = $evaluation['sync_id'];
            $campaignData = [
                'campaigns' => Campaign::getByRun($syncId),
                'ads' => Ad::getByRun($syncId),
                'extensions' => Extension::getByRun($syncId),
                'keywords' => AdGroupKeyword::getByRun($syncId),
                'business_context' => $project['business_context'] ?? '',
            ];

            set_time_limit(300);

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
     * Applica suggerimento AI generato direttamente su Google Ads
     */
    public function applyToGoogleAds(int $projectId, int $evalId): void
    {
        ignore_user_abort(true);
        set_time_limit(300);
        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $projectId);

            if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
                ob_end_clean();
                echo json_encode(['error' => 'Progetto non valido']);
                exit;
            }

            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
                exit;
            }

            $customerId = $project['google_ads_customer_id'] ?? '';
            if (empty($customerId)) {
                ob_end_clean();
                echo json_encode(['error' => 'Account Google Ads non collegato. Vai alla sezione Connessione.']);
                exit;
            }

            $type = $_POST['type'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $targetCampaign = $_POST['target_campaign'] ?? '';

            if (empty($type) || empty($data)) {
                ob_end_clean();
                echo json_encode(['error' => 'Dati mancanti']);
                exit;
            }

            session_write_close();

            $loginCustomerId = $project['login_customer_id'] ?? '';
            $gads = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);

            $applied = 0;
            $details = [];

            // Determina la campagna target (prima campagna attiva se non specificata)
            if (empty($targetCampaign)) {
                $latestSync = Sync::getLatestByProject($projectId);
                if ($latestSync) {
                    $campaigns = Campaign::getByRun($latestSync['id']);
                    foreach ($campaigns as $c) {
                        if (($c['campaign_status'] ?? '') === 'ENABLED' && !empty($c['campaign_id_google'])) {
                            $targetCampaign = "customers/{$customerId}/campaigns/{$c['campaign_id_google']}";
                            break;
                        }
                    }
                }
            }

            if (str_contains($type, 'extensions')) {
                // === ESTENSIONI: sitelink, callout, structured snippet ===
                $mutateOps = [];

                if (!empty($data['sitelinks'])) {
                    foreach ($data['sitelinks'] as $sl) {
                        $mutateOps[] = [
                            'extensionFeedItemOperation' => [
                                'create' => [
                                    'extensionType' => 'SITELINK',
                                    'sitelinkFeedItem' => [
                                        'linkText' => $sl['title'] ?? '',
                                        'line1' => $sl['desc1'] ?? '',
                                        'line2' => $sl['desc2'] ?? '',
                                        'finalUrls' => [$sl['url'] ?? '/'],
                                    ]
                                ]
                            ]
                        ];
                        $applied++;
                    }
                }

                if (!empty($data['callouts'])) {
                    foreach ($data['callouts'] as $callout) {
                        $text = is_string($callout) ? $callout : ($callout['text'] ?? '');
                        if (empty($text)) continue;
                        $mutateOps[] = [
                            'extensionFeedItemOperation' => [
                                'create' => [
                                    'extensionType' => 'CALLOUT',
                                    'calloutFeedItem' => [
                                        'calloutText' => $text
                                    ]
                                ]
                            ]
                        ];
                        $applied++;
                    }
                }

                if (!empty($mutateOps)) {
                    $gads->groupedMutate($mutateOps);
                    Database::reconnect();
                    $details[] = count($data['sitelinks'] ?? []) . ' sitelink';
                    $details[] = count($data['callouts'] ?? []) . ' callout';
                }

            } elseif (str_contains($type, 'copy')) {
                // === COPY: crea nuovo RSA nell'ad group della prima campagna ===
                $headlines = array_map(fn($h) => ['text' => $h], $data['headlines'] ?? []);
                $descriptions = array_map(fn($d) => ['text' => $d], $data['descriptions'] ?? []);

                if (empty($headlines) || empty($descriptions)) {
                    ob_end_clean();
                    echo json_encode(['error' => 'Headline o description mancanti']);
                    exit;
                }

                // Trova primo ad group della campagna target
                $adGroupResource = '';
                $finalUrl = '';
                $latestSync = Sync::getLatestByProject($projectId);
                if ($latestSync) {
                    $ads = Ad::getByRun($latestSync['id']);
                    if (!empty($ads)) {
                        $firstAd = $ads[0];
                        $adGroupResource = "customers/{$customerId}/adGroups/{$firstAd['ad_group_id_google']}";
                        $finalUrl = $firstAd['final_url'] ?? '';
                    }
                }

                if (empty($adGroupResource)) {
                    ob_end_clean();
                    echo json_encode(['error' => 'Nessun ad group trovato per creare l\'annuncio']);
                    exit;
                }

                $gads->mutateAdGroupAds([
                    [
                        'create' => [
                            'adGroup' => $adGroupResource,
                            'status' => 'PAUSED',
                            'ad' => [
                                'responsiveSearchAd' => [
                                    'headlines' => $headlines,
                                    'descriptions' => $descriptions,
                                ],
                                'finalUrls' => [$finalUrl ?: ($project['landing_url'] ?? 'https://example.com')]
                            ]
                        ]
                    ]
                ]);
                Database::reconnect();
                $applied = 1;
                $details[] = count($data['headlines'] ?? []) . ' headline, ' . count($data['descriptions'] ?? []) . ' description';

            } elseif (str_contains($type, 'keyword')) {
                if (($data['action'] ?? '') === 'remove_duplicates') {
                    // === RIMOZIONE KEYWORD DUPLICATE ===
                    // Trova le keyword da rimuovere tramite GAQL query
                    foreach ($data['duplicates'] ?? [] as $dup) {
                        $keyword = $dup['keyword'] ?? '';
                        $removeFrom = $dup['remove_from'] ?? [];
                        if (empty($keyword) || empty($removeFrom)) continue;

                        // Query per trovare i resource name delle keyword duplicate
                        $escapedKw = addslashes($keyword);
                        $gaql = "SELECT ad_group_criterion.resource_name, ad_group_criterion.keyword.text, ad_group.name " .
                                "FROM ad_group_criterion " .
                                "WHERE ad_group_criterion.keyword.text = '{$escapedKw}' " .
                                "AND ad_group_criterion.type = 'KEYWORD' " .
                                "AND ad_group_criterion.status != 'REMOVED'";

                        $results = $gads->search($gaql);
                        Database::reconnect();

                        foreach ($results as $row) {
                            $agName = $row['adGroup']['name'] ?? '';
                            $resourceName = $row['adGroupCriterion']['resourceName'] ?? '';
                            if (in_array($agName, $removeFrom) && !empty($resourceName)) {
                                $gads->mutateAdGroupCriteria([
                                    ['remove' => $resourceName]
                                ]);
                                Database::reconnect();
                                $applied++;
                                $details[] = "'{$keyword}' rimossa da '{$agName}'";
                            }
                        }
                    }
                } else {
                    // === KEYWORD NEGATIVE ===
                    if (empty($targetCampaign)) {
                        ob_end_clean();
                        echo json_encode(['error' => 'Nessuna campagna target trovata']);
                        exit;
                    }

                    $ops = [];
                    foreach ($data['keywords'] ?? [] as $kw) {
                        $text = is_string($kw) ? $kw : ($kw['keyword'] ?? $kw['text'] ?? '');
                        if (empty($text)) continue;
                        $matchType = strtoupper($kw['match_type'] ?? 'PHRASE');
                        if (!in_array($matchType, ['EXACT', 'PHRASE', 'BROAD'])) $matchType = 'PHRASE';

                        $ops[] = [
                            'create' => [
                                'campaign' => $targetCampaign,
                                'negative' => true,
                                'keyword' => [
                                    'text' => $text,
                                    'matchType' => $matchType
                                ]
                            ]
                        ];
                        $applied++;
                    }

                    if (!empty($ops)) {
                        $gads->mutateCampaignCriteria($ops);
                        Database::reconnect();
                    }
                }
            } else {
                ob_end_clean();
                echo json_encode(['error' => 'Tipo non supportato: ' . $type]);
                exit;
            }

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'applied' => $applied,
                'details' => implode(', ', $details),
                'message' => "{$applied} elementi applicati su Google Ads"
            ]);
        } catch (\Exception $e) {
            Database::reconnect();
            Logger::channel('ai')->error("Evaluation applyToGoogleAds error", [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
                'eval_id' => $evalId
            ]);
            ob_end_clean();
            echo json_encode(['error' => 'Errore applicazione: ' . $e->getMessage()]);
        }
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
            if (($data['action'] ?? '') === 'remove_duplicates') {
                $lines[] = "KEYWORD DUPLICATE DA RIMUOVERE:";
                $lines[] = "";
                foreach (($data['duplicates'] ?? []) as $i => $dup) {
                    $lines[] = ($i + 1) . '. "' . ($dup['keyword'] ?? '') . '"';
                    $lines[] = "   Mantieni in: " . ($dup['keep_in'] ?? '?');
                    $lines[] = "   Rimuovi da: " . implode(', ', $dup['remove_from'] ?? []);
                    $lines[] = "   Motivo: " . ($dup['reason'] ?? '');
                    $lines[] = "";
                }
            } else {
                $lines[] = "KEYWORD NEGATIVE:";
                foreach (($data['keywords'] ?? []) as $i => $kw) {
                    $match = $kw['match_type'] ?? 'phrase';
                    $lines[] = ($i + 1) . ". " . ($kw['keyword'] ?? '') . " [{$match}]" . (!empty($kw['reason']) ? " - " . $kw['reason'] : '');
                }
            }
        }

        return implode("\n", $lines);
    }
}
