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
use Modules\AdsAnalyzer\Models\ScriptRun;
use Modules\AdsAnalyzer\Models\BusinessContext;
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
            $_SESSION['flash_error'] = 'Progetto non trovato';
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

        // Run con search terms
        $runs = ScriptRun::getByProject($projectId, 20);
        $searchTermRuns = array_values(array_filter($runs, fn($r) =>
            in_array($r['run_type'], ['search_terms', 'both']) && $r['status'] === 'completed'
        ));

        $selectedRun = !empty($searchTermRuns) ? $searchTermRuns[0] : null;

        // Dati per il run selezionato
        $adGroups = [];
        $searchTermStats = [];
        $landingUrls = [];

        if ($selectedRun) {
            $adGroups = AdGroup::getByRunWithStats($selectedRun['id']);
            $searchTermStats = SearchTerm::getStatsByRun($selectedRun['id']);

            // Rileva URL dagli annunci (ultimo run campagna)
            $campaignRuns = array_values(array_filter($runs, fn($r) =>
                in_array($r['run_type'], ['campaign_performance', 'both']) && $r['status'] === 'completed'
            ));
            if (!empty($campaignRuns)) {
                $landingUrls = Ad::getLandingUrlsByAdGroup($campaignRuns[0]['id']);
            }
        }

        // Analisi completate per questo progetto (con run_id)
        $analyses = Analysis::getCompletedByProjectWithRun($projectId);

        // Contesti business salvati dall'utente
        $savedContexts = BusinessContext::getByUser($user['id']);

        return View::render('ads-analyzer/campaigns/search-terms', [
            'title' => 'Keyword Negative - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'searchTermRuns' => $searchTermRuns,
            'selectedRun' => $selectedRun,
            'adGroups' => $adGroups,
            'searchTermStats' => $searchTermStats,
            'landingUrls' => $landingUrls,
            'analyses' => $analyses,
            'savedContexts' => $savedContexts,
            'currentPage' => 'search-term-analysis',
            'userCredits' => Credits::getBalance($user['id']),
        ]);
    }

    /**
     * AJAX: Dati per un run specifico
     */
    public function getRunData(int $projectId): void
    {
        header('Content-Type: application/json');
        $ctx = $this->requireCampaignProjectJson($projectId);

        $runId = (int)($_GET['run_id'] ?? 0);
        if (!$runId) {
            echo json_encode(['error' => 'run_id richiesto']);
            exit;
        }

        $run = ScriptRun::find($runId);
        if (!$run || $run['project_id'] != $projectId) {
            http_response_code(404);
            echo json_encode(['error' => 'Run non trovato']);
            exit;
        }

        $adGroups = AdGroup::getByRunWithStats($runId);
        $stats = SearchTerm::getStatsByRun($runId);

        // Search terms raggruppati per ad group
        $searchTermsByGroup = [];
        foreach ($adGroups as $ag) {
            $terms = SearchTerm::getByRunAndAdGroup($runId, $ag['id']);
            $searchTermsByGroup[$ag['id']] = array_map(fn($t) => [
                'id' => $t['id'],
                'term' => $t['term'],
                'clicks' => (int)$t['clicks'],
                'impressions' => (int)$t['impressions'],
                'ctr' => round((float)$t['ctr'] * 100, 2),
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

        $runId = (int)($_POST['run_id'] ?? 0);
        if (!$runId) {
            echo json_encode(['error' => 'run_id richiesto']);
            exit;
        }

        // Trova ultimo run campagna per avere gli annunci
        $runs = ScriptRun::getByProject($projectId, 20);
        $campaignRuns = array_values(array_filter($runs, fn($r) =>
            in_array($r['run_type'], ['campaign_performance', 'both']) && $r['status'] === 'completed'
        ));

        if (empty($campaignRuns)) {
            echo json_encode(['error' => 'Nessun dato campagne disponibile. Lo script deve raccogliere anche i dati delle campagne.']);
            exit;
        }

        $campaignRunId = $campaignRuns[0]['id'];
        $urlData = Ad::getLandingUrlsByAdGroup($campaignRunId);

        // Raggruppa: per ogni ad_group_name prendi l'URL con più annunci
        $bestUrls = [];
        foreach ($urlData as $row) {
            $agName = $row['ad_group_name'];
            if (!isset($bestUrls[$agName]) || $row['url_count'] > $bestUrls[$agName]['url_count']) {
                $bestUrls[$agName] = $row;
            }
        }

        // Match con ad groups del run search terms (case-insensitive)
        $adGroups = AdGroup::getByRun($runId);
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

        $runId = (int)($_POST['run_id'] ?? 0);
        if (!$runId) {
            ob_end_clean();
            echo json_encode(['error' => 'run_id richiesto']);
            exit;
        }

        $adGroups = AdGroup::getByRun($runId);
        $withUrl = array_filter($adGroups, fn($ag) => !empty($ag['landing_url']));

        if (empty($withUrl)) {
            ob_end_clean();
            echo json_encode(['error' => 'Nessun Ad Group con URL landing. Rileva prima gli URL.']);
            exit;
        }

        // Verifica crediti
        $contextCost = Credits::getCost('context_extraction', 'ads-analyzer');
        $creditsNeeded = count($withUrl) * $contextCost;
        if (!Credits::hasEnough($user['id'], $creditsNeeded)) {
            ob_end_clean();
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
                Credits::consume($user['id'], $contextCost, 'context_extraction', 'ads-analyzer', [
                    'ad_group' => $ag['name'],
                    'run_id' => $runId,
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

            $runId = (int)($_POST['run_id'] ?? 0);
            $businessContext = trim($_POST['business_context'] ?? '');

            if (!$runId) {
                ob_end_clean();
                echo json_encode(['error' => 'run_id richiesto']);
                exit;
            }

            if (strlen($businessContext) < 20) {
                ob_end_clean();
                echo json_encode(['error' => 'Il contesto business deve avere almeno 20 caratteri']);
                exit;
            }

            // Carica ad groups per il run
            $adGroups = AdGroup::getByRun($runId);
            if (empty($adGroups)) {
                ob_end_clean();
                echo json_encode(['error' => 'Nessun Ad Group trovato per questo run']);
                exit;
            }

            // Filtra solo quelli con termini
            $adGroupsWithTerms = [];
            foreach ($adGroups as $ag) {
                $terms = SearchTerm::getByRunAndAdGroup($runId, $ag['id']);
                if (!empty($terms)) {
                    $adGroupsWithTerms[] = ['adGroup' => $ag, 'terms' => $terms];
                }
            }

            if (empty($adGroupsWithTerms)) {
                ob_end_clean();
                echo json_encode(['error' => 'Nessun termine di ricerca trovato']);
                exit;
            }

            // Calcola crediti - livello Standard (3 cr) per ogni ad group, nessuno sconto bulk
            $adGroupCount = count($adGroupsWithTerms);
            $singleCost = Credits::getCost('ad_group_analysis', 'ads-analyzer', 3);
            $creditsNeeded = $adGroupCount * $singleCost;

            if (!Credits::hasEnough($user['id'], $creditsNeeded)) {
                ob_end_clean();
                echo json_encode(['error' => "Crediti insufficienti. Necessari: {$creditsNeeded}"]);
                exit;
            }

            session_write_close();

            // Crea Analysis con run_id
            $analysisId = Analysis::create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'run_id' => $runId,
                'name' => 'Analisi KW Negative ' . date('d/m/Y H:i'),
                'business_context' => $businessContext,
                'context_mode' => 'auto',
            ]);

            Analysis::updateStatus($analysisId, 'analyzing');

            $analyzer = new KeywordAnalyzerService();
            $totalNegatives = 0;
            $totalCategories = 0;
            $errors = [];

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
                    $aiResult = $analyzer->analyzeAdGroup(
                        $user['id'],
                        $agContext,
                        $terms
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
            Credits::consume($user['id'], $creditsNeeded, 'negative_kw_analysis', 'ads-analyzer', [
                'run_id' => $runId,
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
                ], $keywords),
            ];
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
            $_SESSION['flash_error'] = 'Analisi non specificata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/search-term-analysis"));
            exit;
        }

        $analysis = Analysis::find($analysisId);
        if (!$analysis || $analysis['project_id'] != $projectId) {
            $_SESSION['flash_error'] = 'Analisi non trovata';
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
     * Salva risultati analisi AI in DB (replica AnalysisController::saveAnalysisResults)
     */
    private function saveAnalysisResults(int $projectId, int $adGroupId, array $analysis, int $analysisId): void
    {
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
                NegativeKeyword::create([
                    'project_id' => $projectId,
                    'ad_group_id' => $adGroupId,
                    'analysis_id' => $analysisId,
                    'category_id' => $categoryId,
                    'keyword' => $keyword,
                    'is_selected' => ($data['priority'] ?? 'medium') !== 'evaluate',
                ]);
            }
        }
    }

    private function formatCategoryName(string $key): string
    {
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }
}
