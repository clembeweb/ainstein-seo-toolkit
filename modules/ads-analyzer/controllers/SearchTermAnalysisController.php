<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\Analysis;
use Modules\AdsAnalyzer\Models\Ad;
use Modules\AdsAnalyzer\Models\NegativeCategory;
use Modules\AdsAnalyzer\Models\NegativeKeyword;
use Modules\AdsAnalyzer\Models\Sync;
use Modules\AdsAnalyzer\Models\BusinessContext;
use Services\GoogleAdsService;
use Modules\AdsAnalyzer\Services\KeywordAnalyzerService;
use Modules\AdsAnalyzer\Services\ContextExtractorService;
use Core\Logger;

class SearchTermAnalysisController
{
    /**
     * Type guard: solo progetti campaign
     */
    private function requireCampaignProject(int $projectId): array
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

        return ['user' => $user, 'project' => $project];
    }

    /**
     * Type guard per AJAX (ritorna JSON)
     */
    private function requireCampaignProjectJson(int $projectId): array
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        if (($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Operazione non disponibile per questo tipo di progetto']);
            exit;
        }

        return ['user' => $user, 'project' => $project];
    }

    /**
     * Pagina principale analisi keyword negative (single-page)
     */
    public function index(int $projectId): string
    {
        $ctx = $this->requireCampaignProject($projectId);
        $user = $ctx['user'];
        $project = $ctx['project'];

        // Sync completate con search terms
        $syncs = Sync::getCompletedSyncs($projectId);
        $searchTermSyncs = array_values(array_filter($syncs, fn($s) =>
            (int)($s['search_terms_synced'] ?? 0) > 0
        ));

        $selectedSync = !empty($searchTermSyncs) ? $searchTermSyncs[0] : null;

        // Stats per la sync corrente
        $searchTermStats = [];
        if ($selectedSync) {
            $searchTermStats = SearchTerm::getStatsByRun($selectedSync['id']);
        }

        // Ultima analisi completata
        $analyses = Analysis::getCompletedByProjectWithSync($projectId);
        $latestAnalysis = !empty($analyses) ? $analyses[0] : null;

        // Storico negative applicate
        $appliedCount = 0;
        $lastAppliedDate = null;
        $appliedKeywords = Database::fetch(
            "SELECT COUNT(*) as cnt, MAX(applied_at) as last_applied FROM ga_negative_keywords WHERE project_id = ? AND applied_at IS NOT NULL",
            [$projectId]
        );
        if ($appliedKeywords) {
            $appliedCount = (int)$appliedKeywords['cnt'];
            $lastAppliedDate = $appliedKeywords['last_applied'];
        }

        // Contesti business salvati dall'utente
        $savedContexts = BusinessContext::getByUser($user['id']);

        return View::render('ads-analyzer/campaigns/search-terms', [
            'title' => 'Keyword Negative - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'searchTermSyncs' => $searchTermSyncs,
            'selectedSync' => $selectedSync,
            'searchTermStats' => $searchTermStats,
            'analyses' => $analyses,
            'latestAnalysis' => $latestAnalysis,
            'savedContexts' => $savedContexts,
            'appliedCount' => $appliedCount,
            'lastAppliedDate' => $lastAppliedDate,
            'currentPage' => 'search-term-analysis',
            'userCredits' => Credits::getBalance($user['id']),
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * AJAX: Dati per una sync specifica
     */
    public function getSyncData(int $projectId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $syncId = (int)($_GET['sync_id'] ?? 0);
        if (!$syncId) {
            http_response_code(400);
            echo json_encode(['error' => 'sync_id richiesto']);
            exit;
        }

        $sync = Sync::find($syncId);
        if (!$sync || $sync['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Sincronizzazione non trovata']);
            exit;
        }

        $adGroups = AdGroup::getByRunWithStats($syncId);
        $stats = SearchTerm::getStatsByRun($syncId);

        // Search terms raggruppati per ad group
        $searchTermsByGroup = [];
        foreach ($adGroups as $ag) {
            $terms = SearchTerm::getByRunAndAdGroup($syncId, $ag['id']);
            $searchTermsByGroup[$ag['id']] = array_map(fn($t) => [
                'id' => $t['id'],
                'term' => $t['term'],
                'clicks' => (int)$t['clicks'],
                'impressions' => (int)$t['impressions'],
                'ctr' => round((float)$t['ctr'], 2),
                'cost' => round((float)$t['cost'], 2),
                'conversions' => (int)$t['conversions'],
                'is_zero_ctr' => (bool)$t['is_zero_ctr'],
            ], $terms);
        }

        echo json_encode([
            'success' => true,
            'adGroups' => array_map(fn($ag) => [
                'id' => $ag['id'],
                'name' => $ag['name'],
                'terms_count' => (int)$ag['terms_count'],
                'zero_ctr_count' => (int)$ag['zero_ctr_count'],
                'wasted_impressions' => (int)$ag['wasted_impressions'],
                'landing_url' => $ag['landing_url'] ?? '',
                'extracted_context' => $ag['extracted_context'] ?? '',
                'negatives_count' => (int)($ag['negatives_count'] ?? 0),
            ], $adGroups),
            'stats' => [
                'total_terms' => (int)($stats['total_terms'] ?? 0),
                'zero_ctr_count' => (int)($stats['zero_ctr_count'] ?? 0),
                'wasted_impressions' => (int)($stats['wasted_impressions'] ?? 0),
                'total_clicks' => (int)($stats['total_clicks'] ?? 0),
                'total_impressions' => (int)($stats['total_impressions'] ?? 0),
                'total_cost' => round((float)($stats['total_cost'] ?? 0), 2),
            ],
            'searchTermsByGroup' => $searchTermsByGroup,
        ]);
        exit;
    }

    /**
     * AJAX: Rileva URL landing dagli annunci
     */
    public function detectLandingUrls(int $projectId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $syncId = (int)($_POST['sync_id'] ?? 0);
        if (!$syncId) {
            http_response_code(400);
            echo json_encode(['error' => 'sync_id richiesto']);
            exit;
        }

        // La sync include tutti i dati (campagne, annunci, search terms)
        $sync = Sync::find($syncId);
        if (!$sync || $sync['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Sincronizzazione non trovata']);
            exit;
        }

        $urlData = Ad::getLandingUrlsByAdGroup($syncId);

        // Raggruppa: per ogni ad_group_name prendi l'URL con più annunci
        $bestUrls = [];
        foreach ($urlData as $row) {
            $agName = $row['ad_group_name'];
            if (!isset($bestUrls[$agName]) || $row['url_count'] > $bestUrls[$agName]['url_count']) {
                $bestUrls[$agName] = $row;
            }
        }

        // Match con ad groups della sync (case-insensitive)
        $adGroups = AdGroup::getByRun($syncId);
        $matched = 0;

        foreach ($adGroups as $ag) {
            $agNameLower = strtolower($ag['name']);
            foreach ($bestUrls as $name => $urlRow) {
                if (strtolower($name) === $agNameLower) {
                    AdGroup::updateLandingUrl($ag['id'], $urlRow['final_url']);
                    $matched++;
                    break;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'matched' => $matched,
            'total_ad_groups' => count($adGroups),
            'urls_found' => count($bestUrls),
        ]);
        exit;
    }

    /**
     * AJAX: Estrai contesti da landing pages (bulk)
     */
    public function extractContexts(int $projectId): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        header('Content-Type: application/json');
        ob_start();

        $ctx = $this->requireCampaignProjectJson($projectId);
        $user = $ctx['user'];
        $project = $ctx['project'];

        $syncId = (int)($_POST['sync_id'] ?? 0);
        if (!$syncId) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['error' => 'sync_id richiesto']);
            exit;
        }

        $adGroups = AdGroup::getByRun($syncId);
        $withUrl = array_filter($adGroups, fn($ag) => !empty($ag['landing_url']));

        if (empty($withUrl)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['error' => 'Nessun Ad Group con URL landing. Rileva prima gli URL.']);
            exit;
        }

        // Route credits to project owner
        $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

        // Verifica crediti
        $contextCost = Credits::getCost('context_extraction', 'ads-analyzer');
        $creditsNeeded = count($withUrl) * $contextCost;
        if (!Credits::hasEnough($creditUserId, $creditsNeeded)) {
            ob_end_clean();
            http_response_code(402);
            echo json_encode(['error' => "Crediti insufficienti (servono {$creditsNeeded} crediti)"]);
            exit;
        }

        session_write_close();

        $extractor = new ContextExtractorService();
        $results = [];

        foreach ($withUrl as $ag) {
            set_time_limit(0);
            $result = $extractor->extractFromUrl($user['id'], $ag['landing_url'], 'negative-kw');

            Database::reconnect();

            if ($result['success']) {
                AdGroup::saveScrapedContent($ag['id'], $result['scraped_content']);
                AdGroup::saveExtractedContext($ag['id'], $result['extracted_context']);
                Credits::consume($creditUserId, $contextCost, 'context_extraction', 'ads-analyzer', [
                    'ad_group' => $ag['name'],
                    'sync_id' => $syncId,
                ]);

                $results[] = [
                    'ad_group_id' => $ag['id'],
                    'name' => $ag['name'],
                    'success' => true,
                    'context' => $result['extracted_context'],
                ];
            } else {
                $results[] = [
                    'ad_group_id' => $ag['id'],
                    'name' => $ag['name'],
                    'success' => false,
                    'error' => $result['error'],
                ];
            }
        }

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'results' => $results,
        ]);
        exit;
    }

    /**
     * AJAX: Avvia analisi AI keyword negative
     */
    public function analyze(int $projectId): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        header('Content-Type: application/json');
        ob_start();

        try {
            $ctx = $this->requireCampaignProjectJson($projectId);
            $user = $ctx['user'];
            $project = $ctx['project'];

            $syncId = (int)($_POST['sync_id'] ?? 0);

            if (!$syncId) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'sync_id richiesto']);
                exit;
            }

            // Contesto business: opzionale dall'utente, arricchito automaticamente
            $manualContext = trim($_POST['business_context'] ?? '');
            $autoContext = $this->buildAutoContext($projectId, $syncId);

            $businessContext = $manualContext;
            if (!empty($autoContext)) {
                $businessContext = (!empty($manualContext) ? $manualContext . "\n\n" : '') . $autoContext;
            }

            if (strlen($businessContext) < 20) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Impossibile generare contesto automatico. Aggiungi una descrizione del business.']);
                exit;
            }

            // Carica ad groups distinti dai search terms (ga_ad_groups potrebbe non avere il sync_id corretto)
            $distinctAdGroupIds = Database::fetchAll(
                "SELECT DISTINCT ad_group_id FROM ga_search_terms WHERE sync_id = ? AND project_id = ?",
                [$syncId, $projectId]
            );

            $adGroupsWithTerms = [];
            foreach ($distinctAdGroupIds as $row) {
                $agId = (int)$row['ad_group_id'];
                $ag = AdGroup::find($agId);
                if (!$ag) continue;

                $terms = SearchTerm::getByRunAndAdGroup($syncId, $agId);
                if (!empty($terms)) {
                    $adGroupsWithTerms[] = ['adGroup' => $ag, 'terms' => $terms];
                }
            }

            if (empty($adGroupsWithTerms)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Nessun termine di ricerca trovato']);
                exit;
            }

            // Calcola crediti - livello Standard (3 cr) per ogni ad group, nessuno sconto bulk
            $adGroupCount = count($adGroupsWithTerms);
            // Route credits to project owner
            $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

            $singleCost = Credits::getCost('ad_group_analysis', 'ads-analyzer', 3);
            $creditsNeeded = $adGroupCount * $singleCost;

            if (!Credits::hasEnough($creditUserId, $creditsNeeded)) {
                ob_end_clean();
                http_response_code(402);
                echo json_encode(['error' => "Crediti insufficienti. Necessari: {$creditsNeeded}"]);
                exit;
            }

            session_write_close();

            // Crea Analysis con sync_id
            $analysisId = Analysis::create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'sync_id' => $syncId,
                'name' => 'Analisi KW Negative ' . date('d/m/Y H:i'),
                'business_context' => $businessContext,
                'context_mode' => 'auto',
            ]);

            Analysis::updateStatus($analysisId, 'analyzing');

            $analyzer = new KeywordAnalyzerService();
            $totalNegatives = 0;
            $totalCategories = 0;
            $errors = [];

            // Struttura campagne per il prompt AI
            $campaignStructure = $this->buildCampaignStructureForPrompt($projectId, $syncId);

            foreach ($adGroupsWithTerms as $item) {
                $ag = $item['adGroup'];
                $terms = $item['terms'];

                try {
                    // Usa contesto estratto dall'ad group se disponibile
                    $agContext = $businessContext;
                    if (!empty($ag['extracted_context'])) {
                        $agContext = $businessContext . "\n\nCONTESTO LANDING PAGE ({$ag['name']}):\n" . $ag['extracted_context'];
                    }

                    set_time_limit(0);
                    // Determina nome campagna dal primo termine
                    $currentCampaignName = $terms[0]['campaign_name'] ?? '';
                    $aiResult = $analyzer->analyzeAdGroup(
                        $user['id'],
                        $agContext,
                        $terms,
                        300,
                        $campaignStructure,
                        $ag['name'] ?? '',
                        $currentCampaignName
                    );

                    Database::reconnect();

                    // Salva risultati
                    $this->saveAnalysisResults($projectId, $ag['id'], $aiResult, $analysisId);

                    $kwCount = 0;
                    foreach ($aiResult['categories'] ?? [] as $cat) {
                        $kwCount += count($cat['keywords'] ?? []);
                    }
                    $totalNegatives += $kwCount;
                    $totalCategories += count($aiResult['categories'] ?? []);

                    AdGroup::update($ag['id'], [
                        'analysis_status' => 'completed',
                        'analyzed_at' => date('Y-m-d H:i:s'),
                    ]);

                } catch (\Exception $e) {
                    Database::reconnect();
                    AdGroup::update($ag['id'], ['analysis_status' => 'error']);
                    $errors[] = "{$ag['name']}: {$e->getMessage()}";
                }
            }

            Database::reconnect();

            // Aggiorna Analysis
            Analysis::update($analysisId, [
                'ad_groups_analyzed' => $adGroupCount,
                'total_categories' => $totalCategories,
                'total_keywords' => $totalNegatives,
                'credits_used' => $creditsNeeded,
            ]);
            Analysis::updateStatus($analysisId, 'completed');

            // Consuma crediti
            Credits::consume($creditUserId, $creditsNeeded, 'negative_kw_analysis', 'ads-analyzer', [
                'sync_id' => $syncId,
                'ad_groups' => $adGroupCount,
                'source' => 'campaign_project',
            ]);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'analysis_id' => $analysisId,
                'total_negatives' => $totalNegatives,
                'total_categories' => $totalCategories,
                'errors' => $errors,
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ai')->error("SearchTermAnalysis error", ['error' => $e->getMessage()]);

            if (isset($analysisId)) {
                Database::reconnect();
                Analysis::updateStatus($analysisId, 'error', $e->getMessage());
            }

            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * AJAX: Risultati analisi
     */
    public function getResults(int $projectId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $analysisId = (int)($_GET['analysis_id'] ?? 0);
        if (!$analysisId) {
            http_response_code(400);
            echo json_encode(['error' => 'analysis_id richiesto']);
            exit;
        }

        $analysis = Analysis::find($analysisId);
        if (!$analysis || $analysis['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Analisi non trovata']);
            exit;
        }

        // Carica categorie con keyword
        $categories = NegativeCategory::getByAnalysisWithCounts($analysisId);
        $result = [];

        foreach ($categories as $cat) {
            $keywords = NegativeKeyword::getByCategory($cat['id']);
            $result[] = [
                'id' => $cat['id'],
                'ad_group_id' => $cat['ad_group_id'],
                'category_name' => $cat['category_name'],
                'description' => $cat['description'] ?? '',
                'priority' => $cat['priority'],
                'total_keywords' => (int)$cat['total_keywords'],
                'selected_keywords' => (int)$cat['selected_keywords'],
                'keywords' => array_map(fn($kw) => [
                    'id' => $kw['id'],
                    'keyword' => $kw['keyword'],
                    'is_selected' => (bool)$kw['is_selected'],
                    'suggested_match_type' => $kw['suggested_match_type'] ?? 'phrase',
                    'suggested_level' => $kw['suggested_level'] ?? 'campaign',
                    'applied_at' => $kw['applied_at'] ?? null,
                ], $keywords),
            ];
        }

        // Cross-analysis comparison
        $previousAnalysis = null;
        $resolvedKeywords = [];
        $comparison = ['resolved' => 0, 'recurring' => 0, 'new' => 0, 'has_previous' => false, 'previous_analysis_name' => ''];

        $completedAnalyses = Analysis::getCompletedByProject($projectId);
        foreach ($completedAnalyses as $ca) {
            if ((int)$ca['id'] !== $analysisId) {
                $previousAnalysis = $ca;
                break;
            }
        }

        if ($previousAnalysis) {
            $comparison['has_previous'] = true;
            $comparison['previous_analysis_name'] = $previousAnalysis['name'];

            $prevKeywords = NegativeKeyword::getDistinctKeywordTextByAnalysis((int)$previousAnalysis['id']);
            $prevSet = array_flip($prevKeywords);

            $currentKeywords = NegativeKeyword::getDistinctKeywordTextByAnalysis($analysisId);
            $currentSet = array_flip($currentKeywords);

            // Classify each current keyword
            foreach ($result as &$cat) {
                foreach ($cat['keywords'] as &$kw) {
                    $kwLower = strtolower($kw['keyword']);
                    $kw['status'] = isset($prevSet[$kwLower]) ? 'recurring' : 'new';
                }
                unset($kw);
            }
            unset($cat);

            // Resolved: in previous but not in current
            foreach ($prevKeywords as $pk) {
                if (!isset($currentSet[$pk])) {
                    $resolvedKeywords[] = $pk;
                    $comparison['resolved']++;
                } else {
                    $comparison['recurring']++;
                }
            }

            // New count
            foreach ($currentKeywords as $ck) {
                if (!isset($prevSet[$ck])) {
                    $comparison['new']++;
                }
            }
        }

        // Raggruppa per ad_group_id
        $byAdGroup = [];
        foreach ($result as $cat) {
            $agId = $cat['ad_group_id'];
            $byAdGroup[$agId][] = $cat;
        }

        // Nomi ad group
        $adGroupNames = [];
        foreach ($byAdGroup as $agId => $cats) {
            $ag = AdGroup::find($agId);
            $adGroupNames[$agId] = $ag ? $ag['name'] : 'Sconosciuto';
        }

        $selectedCount = NegativeKeyword::countSelectedByAnalysis($analysisId);
        $totalCount = NegativeKeyword::countByAnalysis($analysisId);

        echo json_encode([
            'success' => true,
            'analysis' => [
                'id' => $analysis['id'],
                'name' => $analysis['name'],
                'status' => $analysis['status'],
                'total_categories' => (int)($analysis['total_categories'] ?? 0),
                'total_keywords' => (int)($analysis['total_keywords'] ?? 0),
                'created_at' => $analysis['created_at'],
            ],
            'categoriesByAdGroup' => $byAdGroup,
            'adGroupNames' => $adGroupNames,
            'selectedCount' => $selectedCount,
            'totalCount' => $totalCount,
            'comparison' => $comparison,
            'resolvedKeywords' => $resolvedKeywords,
        ]);
        exit;
    }

    /**
     * AJAX: Toggle selezione keyword
     */
    public function toggleKeyword(int $projectId, int $keywordId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $keyword = NegativeKeyword::find($keywordId);
        if (!$keyword || $keyword['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Keyword non trovata']);
            exit;
        }

        $newValue = !$keyword['is_selected'];
        NegativeKeyword::update($keywordId, ['is_selected' => $newValue]);

        $analysisId = $keyword['analysis_id'];
        $selectedCount = $analysisId
            ? NegativeKeyword::countSelectedByAnalysis($analysisId)
            : NegativeKeyword::countSelectedByProject($projectId);

        echo json_encode([
            'success' => true,
            'is_selected' => $newValue,
            'selected_count' => $selectedCount,
        ]);
        exit;
    }

    /**
     * AJAX: Azioni bulk categoria
     */
    public function toggleCategory(int $projectId, int $categoryId, string $action): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $category = NegativeCategory::find($categoryId);
        if (!$category || $category['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoria non trovata']);
            exit;
        }

        if ($action === 'invert') {
            NegativeKeyword::invertByCategory($categoryId);
        } else {
            $newValue = $action === 'select_all';
            NegativeKeyword::updateByCategory($categoryId, ['is_selected' => $newValue]);
        }

        $analysisId = $category['analysis_id'];
        $selectedCount = $analysisId
            ? NegativeKeyword::countSelectedByAnalysis($analysisId)
            : NegativeKeyword::countSelectedByProject($projectId);

        echo json_encode([
            'success' => true,
            'selected_count' => $selectedCount,
        ]);
        exit;
    }

    /**
     * Export CSV / Google Ads Editor
     */
    public function export(int $projectId): void
    {
        $ctx = $this->requireCampaignProject($projectId);

        $analysisId = (int)($_GET['analysis_id'] ?? 0);
        $format = $_GET['format'] ?? 'csv';

        if (!$analysisId) {
            $_SESSION['_flash']['error'] = 'Analisi non specificata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/search-term-analysis"));
            exit;
        }

        $analysis = Analysis::find($analysisId);
        if (!$analysis || $analysis['project_id'] != $projectId) {
            $_SESSION['_flash']['error'] = 'Analisi non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/search-term-analysis"));
            exit;
        }

        if ($format === 'google-ads-editor') {
            $keywords = NegativeKeyword::getSelectedByAnalysisWithAdGroup($analysisId);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="google-ads-editor-import.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Campaign', 'Ad Group', 'Keyword', 'Match Type', 'Status']);

            foreach ($keywords as $kw) {
                fputcsv($output, [
                    '',
                    $kw['ad_group_name'],
                    $kw['keyword'],
                    'Phrase',
                    'Enabled',
                ]);
            }

            fclose($output);
            exit;
        }

        // Default: CSV
        $keywords = NegativeKeyword::getSelectedByAnalysisWithAdGroup($analysisId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="negative-keywords-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Keyword', 'Ad Group', 'Categoria', 'Priorita'], ';');

        foreach ($keywords as $kw) {
            fputcsv($output, [
                $kw['keyword'],
                $kw['ad_group_name'] ?? '',
                $kw['category_name'] ?? '',
                $kw['priority'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * AJAX: Copia keyword selezionate come testo
     */
    public function copyText(int $projectId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $analysisId = (int)($_POST['analysis_id'] ?? 0);
        $adGroupId = (int)($_POST['ad_group_id'] ?? 0);

        if (!$analysisId) {
            http_response_code(400);
            echo json_encode(['error' => 'analysis_id richiesto']);
            exit;
        }

        $analysis = Analysis::find($analysisId);
        if (!$analysis || $analysis['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Analisi non trovata']);
            exit;
        }

        if ($adGroupId) {
            // Keyword selezionate per singolo Ad Group
            $keywords = NegativeKeyword::getByAnalysisAndAdGroup($analysisId, $adGroupId);
            $keywords = array_filter($keywords, fn($kw) => $kw['is_selected']);
        } else {
            // Tutte le keyword selezionate dell'analisi
            $keywords = NegativeKeyword::getSelectedByAnalysis($analysisId);
        }

        $text = implode("\n", array_column($keywords, 'keyword'));

        echo json_encode([
            'success' => true,
            'text' => $text,
            'count' => count($keywords),
        ]);
        exit;
    }

    /**
     * AJAX: Lista campagne disponibili per il modale di applicazione
     */
    public function campaignsList(int $projectId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);
        $project = $ctx['project'];

        // Carica campagne dall'ultima sync
        $latestSync = Sync::getLatestByProject($projectId);
        if (!$latestSync) {
            echo json_encode(['success' => true, 'campaigns' => []]);
            exit;
        }

        $campaigns = Database::fetchAll(
            "SELECT DISTINCT campaign_name, campaign_id_google FROM ga_campaigns WHERE project_id = ? AND sync_id = ? ORDER BY campaign_name ASC",
            [$projectId, $latestSync['id']]
        );

        $customerId = $project['google_ads_customer_id'] ?? '';

        $result = array_map(function ($c) use ($customerId) {
            $gadsId = $c['campaign_id_google'] ?? '';
            return [
                'name' => $c['campaign_name'],
                'resource_name' => $gadsId ? "customers/{$customerId}/campaigns/{$gadsId}" : '',
            ];
        }, $campaigns);

        // Filtra campagne senza resource name valido
        $result = array_values(array_filter($result, fn($c) => !empty($c['resource_name'])));

        echo json_encode(['success' => true, 'campaigns' => $result]);
        exit;
    }

    /**
     * AJAX: Applica negative keywords selezionate direttamente su Google Ads via API
     */
    public function applyNegativeKeywords(int $projectId): void
    {
        \Core\Middleware::auth();
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $role = $project['access_role'] ?? 'owner';
        if ($role === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per applicare modifiche']);
            exit;
        }

        $customerId = $project['google_ads_customer_id'] ?? '';
        if (empty($customerId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Account Google Ads non connesso. Vai nelle impostazioni per collegarlo.']);
            exit;
        }

        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');
        session_write_close();

        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (empty($keywordIds) || !is_array($keywordIds)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['error' => 'Nessuna keyword selezionata']);
            exit;
        }

        $keywords = NegativeKeyword::findByIds($keywordIds);

        if (empty($keywords)) {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(['error' => 'Nessuna keyword trovata']);
            exit;
        }

        try {
            $loginCustomerId = $project['login_customer_id'] ?? '';
            $gads = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);

            // Raggruppa per livello suggerito
            $campaignOps = [];
            $adGroupOps = [];

            // Fallback: prima campagna disponibile
            $defaultCampaignResource = $this->getDefaultCampaignResource($projectId, $customerId);

            foreach ($keywords as $kw) {
                $matchType = strtoupper($kw['suggested_match_type'] ?? 'PHRASE');
                if (!in_array($matchType, ['EXACT', 'PHRASE', 'BROAD'])) {
                    $matchType = 'PHRASE';
                }

                $level = $kw['suggested_level'] ?? 'campaign';

                if ($level === 'ad_group' && !empty($kw['suggested_ad_group_resource'])) {
                    $adGroupOps[] = [
                        'create' => [
                            'adGroup' => $kw['suggested_ad_group_resource'],
                            'negative' => true,
                            'keyword' => [
                                'text' => $kw['keyword'],
                                'matchType' => $matchType,
                            ],
                        ],
                    ];
                } else {
                    $campaignResource = $kw['suggested_campaign_resource'] ?? $defaultCampaignResource;
                    if (!empty($campaignResource)) {
                        $campaignOps[] = [
                            'create' => [
                                'campaign' => $campaignResource,
                                'negative' => true,
                                'keyword' => [
                                    'text' => $kw['keyword'],
                                    'matchType' => $matchType,
                                ],
                            ],
                        ];
                    }
                }
            }

            $appliedCount = 0;

            if (!empty($campaignOps)) {
                $gads->mutateCampaignCriteria($campaignOps);
                $appliedCount += count($campaignOps);
            }

            Database::reconnect();

            if (!empty($adGroupOps)) {
                $gads->mutateAdGroupCriteria($adGroupOps);
                $appliedCount += count($adGroupOps);
            }

            Database::reconnect();

            NegativeKeyword::markAsApplied($keywordIds);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'applied' => $appliedCount,
                'campaign_level' => count($campaignOps),
                'ad_group_level' => count($adGroupOps),
                'message' => $appliedCount . ' negative keywords applicate su Google Ads (' . count($campaignOps) . ' a livello campagna, ' . count($adGroupOps) . ' a livello ad group)',
            ]);
            exit;

        } catch (\Exception $e) {
            Database::reconnect();
            Logger::channel('api')->error('applyNegativeKeywords error', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore Google Ads API: ' . $e->getMessage()]);
            exit;
        }
    }

    private function getDefaultCampaignResource(int $projectId, string $customerId): string
    {
        $campaign = Database::fetch(
            "SELECT campaign_id_google FROM ga_campaigns WHERE project_id = ? AND campaign_id_google IS NOT NULL ORDER BY id DESC LIMIT 1",
            [$projectId]
        );
        if ($campaign && !empty($campaign['campaign_id_google'])) {
            return "customers/{$customerId}/campaigns/{$campaign['campaign_id_google']}";
        }
        return '';
    }

    /**
     * Salva risultati analisi AI in DB (replica AnalysisController::saveAnalysisResults)
     */
    private function saveAnalysisResults(int $projectId, int $adGroupId, array $analysis, int $analysisId): void
    {
        // Mappa nomi campagna/ad group a resource names
        $campaignResources = $this->getCampaignResourceMap($projectId);
        $adGroupResources = $this->getAdGroupResourceMap($projectId);

        // Fallback: resource dell'ad group corrente (per quando target_name AI non matcha esattamente)
        $currentAdGroup = AdGroup::find($adGroupId);
        $currentAdGroupResource = null;
        if ($currentAdGroup) {
            // Cerca il resource name dell'ad group corrente nella mappa
            $currentAdGroupResource = $adGroupResources[$currentAdGroup['name']] ?? null;
        }

        // Fallback campagna: prima campagna disponibile (il progetto ha solitamente 1 campagna)
        $defaultCampaignResource = !empty($campaignResources) ? reset($campaignResources) : null;

        $sortOrder = 0;
        foreach ($analysis['categories'] ?? [] as $key => $data) {
            $categoryId = NegativeCategory::create([
                'project_id' => $projectId,
                'ad_group_id' => $adGroupId,
                'analysis_id' => $analysisId,
                'category_key' => $key,
                'category_name' => $this->formatCategoryName($key),
                'description' => $data['description'] ?? '',
                'priority' => $data['priority'] ?? 'medium',
                'keywords_count' => count($data['keywords'] ?? []),
                'sort_order' => $sortOrder++,
            ]);

            foreach ($data['keywords'] ?? [] as $keyword) {
                // Supporta sia formato vecchio (stringa) che nuovo (oggetto)
                if (is_string($keyword)) {
                    NegativeKeyword::create([
                        'project_id' => $projectId,
                        'ad_group_id' => $adGroupId,
                        'analysis_id' => $analysisId,
                        'category_id' => $categoryId,
                        'keyword' => $keyword,
                        'is_selected' => ($data['priority'] ?? 'medium') !== 'evaluate',
                    ]);
                } else {
                    $level = $keyword['level'] ?? 'campaign';
                    $targetName = $keyword['target_name'] ?? '';

                    // Risolvi resource names con fallback
                    $campaignResource = null;
                    $adGroupResource = null;

                    if ($level === 'ad_group') {
                        // Cerca il resource per nome esatto, fallback all'ad group corrente
                        $adGroupResource = $adGroupResources[$targetName] ?? $currentAdGroupResource;
                    } else {
                        // Campaign level: cerca per nome, fallback alla prima campagna
                        $campaignResource = $campaignResources[$targetName] ?? $defaultCampaignResource;
                    }

                    NegativeKeyword::create([
                        'project_id' => $projectId,
                        'ad_group_id' => $adGroupId,
                        'analysis_id' => $analysisId,
                        'category_id' => $categoryId,
                        'keyword' => $keyword['text'] ?? $keyword['keyword'] ?? '',
                        'is_selected' => ($data['priority'] ?? 'medium') !== 'evaluate',
                        'suggested_match_type' => $keyword['match_type'] ?? 'phrase',
                        'suggested_level' => $level,
                        'suggested_campaign_resource' => $campaignResource,
                        'suggested_ad_group_resource' => $adGroupResource,
                    ]);
                }
            }
        }
    }

    private function formatCategoryName(string $key): string
    {
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }

    /**
     * Costruisce contesto business automatico dalla struttura campagne
     */
    private function buildAutoContext(int $projectId, int $syncId): string
    {
        $parts = [];

        // 1. Struttura campagne → ad groups
        $campaignAdGroups = Database::fetchAll(
            "SELECT campaign_name, ad_group_name FROM ga_campaign_ad_groups WHERE project_id = ? AND sync_id = ? ORDER BY campaign_name, ad_group_name",
            [$projectId, $syncId]
        );
        $adGroupsByCampaign = [];
        foreach ($campaignAdGroups as $cag) {
            $adGroupsByCampaign[$cag['campaign_name']][] = $cag['ad_group_name'];
        }
        if (!empty($adGroupsByCampaign)) {
            $structure = "STRUTTURA ACCOUNT:\n";
            foreach ($adGroupsByCampaign as $campName => $agNames) {
                $structure .= "- Campagna: {$campName}\n";
                foreach ($agNames as $agName) {
                    $structure .= "  - Ad Group: {$agName}\n";
                }
            }
            $parts[] = $structure;
        }

        // 2. Landing URL dagli annunci
        $landingUrls = Database::fetchAll(
            "SELECT DISTINCT ad_group_name, final_url FROM ga_ads WHERE project_id = ? AND sync_id = ? AND final_url IS NOT NULL AND final_url != '' ORDER BY ad_group_name",
            [$projectId, $syncId]
        );
        if (!empty($landingUrls)) {
            $urlSection = "LANDING PAGE PER AD GROUP:\n";
            foreach ($landingUrls as $lu) {
                $urlSection .= "- {$lu['ad_group_name']}: {$lu['final_url']}\n";
            }
            $parts[] = $urlSection;
        }

        // 3. Keyword attive
        $activeKeywords = Database::fetchAll(
            "SELECT campaign_name, ad_group_name, keyword_text, match_type FROM ga_ad_group_keywords WHERE project_id = ? AND sync_id = ? AND keyword_status = 'ENABLED' ORDER BY campaign_name, ad_group_name LIMIT 100",
            [$projectId, $syncId]
        );
        if (!empty($activeKeywords)) {
            $kwSection = "KEYWORD ATTIVE (max 100):\n";
            foreach ($activeKeywords as $kw) {
                $kwSection .= "- [{$kw['match_type']}] {$kw['keyword_text']} ({$kw['campaign_name']} > {$kw['ad_group_name']})\n";
            }
            $parts[] = $kwSection;
        }

        // 4. Contesti landing estratti (se disponibili)
        $adGroupsWithContext = AdGroup::getByProject($projectId);
        $contexts = array_filter($adGroupsWithContext, fn($ag) => !empty($ag['extracted_context']));
        if (!empty($contexts)) {
            $ctxSection = "CONTESTO LANDING PAGE (estratto da AI):\n";
            foreach ($contexts as $ag) {
                $ctxSection .= "- {$ag['name']}: {$ag['extracted_context']}\n";
            }
            $parts[] = $ctxSection;
        }

        return implode("\n", $parts);
    }

    /**
     * Costruisce la struttura campagne per il prompt AI
     */
    private function buildCampaignStructureForPrompt(int $projectId, int $syncId): string
    {
        $campaignAdGroups = Database::fetchAll(
            "SELECT campaign_name, ad_group_name, ad_group_id_google, campaign_id_google FROM ga_campaign_ad_groups WHERE project_id = ? AND sync_id = ? ORDER BY campaign_name, ad_group_name",
            [$projectId, $syncId]
        );

        $structure = [];
        foreach ($campaignAdGroups as $cag) {
            $structure[$cag['campaign_name']][] = $cag['ad_group_name'];
        }

        $lines = [];
        foreach ($structure as $campName => $agNames) {
            $lines[] = "Campagna '{$campName}': Ad Groups = " . implode(', ', $agNames);
        }
        return implode("\n", $lines);
    }

    /**
     * Mappa nomi campagna → resource names
     */
    private function getCampaignResourceMap(int $projectId): array
    {
        $project = Project::find($projectId);
        $customerId = $project['google_ads_customer_id'] ?? '';
        $campaigns = Database::fetchAll(
            "SELECT campaign_name, campaign_id_google FROM ga_campaigns WHERE project_id = ? ORDER BY id DESC",
            [$projectId]
        );
        $map = [];
        foreach ($campaigns as $c) {
            if (!empty($c['campaign_id_google']) && !empty($customerId)) {
                $map[$c['campaign_name']] = "customers/{$customerId}/campaigns/{$c['campaign_id_google']}";
            }
        }
        return $map;
    }

    /**
     * Mappa nomi ad group → resource names
     */
    private function getAdGroupResourceMap(int $projectId): array
    {
        $project = Project::find($projectId);
        $customerId = $project['google_ads_customer_id'] ?? '';
        $adGroups = Database::fetchAll(
            "SELECT ad_group_name, ad_group_id_google FROM ga_campaign_ad_groups WHERE project_id = ? ORDER BY id DESC",
            [$projectId]
        );
        $map = [];
        foreach ($adGroups as $ag) {
            if (!empty($ag['ad_group_id_google']) && !empty($customerId)) {
                $map[$ag['ad_group_name']] = "customers/{$customerId}/adGroups/{$ag['ad_group_id_google']}";
            }
        }
        return $map;
    }
}
