<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\Analysis;
use Modules\AdsAnalyzer\Models\NegativeCategory;
use Modules\AdsAnalyzer\Models\NegativeKeyword;
use Modules\AdsAnalyzer\Models\BusinessContext;
use Modules\AdsAnalyzer\Services\CsvParserService;
use Modules\AdsAnalyzer\Services\KeywordAnalyzerService;
use Modules\AdsAnalyzer\Services\ValidationService;
use Modules\AdsAnalyzer\Services\ContextExtractorService;

class AnalysisController
{
    private CsvParserService $csvParser;
    private KeywordAnalyzerService $analyzer;

    public function __construct()
    {
        $this->csvParser = new CsvParserService();
        $this->analyzer = new KeywordAnalyzerService();
    }

    /**
     * Step 1: Upload CSV
     */
    public function upload(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        return View::render('ads-analyzer/analysis/upload', [
            'title' => 'Carica CSV - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project
        ]);
    }

    /**
     * Step 1: Process CSV upload
     */
    public function processUpload(int $projectId): void
    {
        error_log("=== ADS-ANALYZER UPLOAD DEBUG ===");
        error_log("Project ID: " . $projectId);
        error_log("FILES: " . print_r($_FILES, true));
        error_log("POST keys: " . implode(', ', array_keys($_POST)));

        $user = Auth::user();
        error_log("User ID: " . ($user['id'] ?? 'NULL'));

        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            error_log("ERROR: Progetto non trovato");
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }

        // Valida file
        if (!isset($_FILES['csv_file'])) {
            error_log("ERROR: Nessun file in \$_FILES");
            jsonResponse(['error' => 'Nessun file caricato'], 400);
        }

        error_log("File ricevuto: " . $_FILES['csv_file']['name'] . " (" . $_FILES['csv_file']['size'] . " bytes)");

        $errors = ValidationService::validateCsvUpload($_FILES['csv_file']);
        if (!empty($errors)) {
            error_log("Validation errors: " . implode(', ', $errors));
            jsonResponse(['error' => implode(', ', $errors)], 400);
        }

        try {
            $csvContent = file_get_contents($_FILES['csv_file']['tmp_name']);
            $adGroups = $this->csvParser->parse($csvContent);

            if (empty($adGroups)) {
                jsonResponse(['error' => 'Nessun termine trovato nel CSV'], 400);
            }

            // Pulisci dati precedenti
            NegativeKeyword::deleteByProject($projectId);
            NegativeCategory::deleteByProject($projectId);
            SearchTerm::deleteByProject($projectId);
            AdGroup::deleteByProject($projectId);

            // Salva Ad Group e termini
            $totalTerms = 0;
            foreach ($adGroups as $name => $data) {
                $adGroupId = AdGroup::create([
                    'project_id' => $projectId,
                    'name' => $name,
                    'terms_count' => $data['stats']['total'],
                    'zero_ctr_count' => $data['stats']['zero_ctr'],
                    'wasted_impressions' => $data['stats']['wasted_imp']
                ]);

                foreach ($data['terms'] as $term) {
                    SearchTerm::create([
                        'project_id' => $projectId,
                        'ad_group_id' => $adGroupId,
                        'term' => $term['term'],
                        'clicks' => $term['clicks'],
                        'impressions' => $term['impressions'],
                        'ctr' => $term['ctr'],
                        'cost' => $term['cost'],
                        'conversions' => $term['conversions'],
                        'is_zero_ctr' => $term['is_zero_ctr']
                    ]);
                }

                $totalTerms += $data['stats']['total'];
            }

            // Aggiorna progetto
            Project::update($projectId, [
                'total_terms' => $totalTerms,
                'total_ad_groups' => count($adGroups),
                'status' => 'draft'
            ]);

            jsonResponse([
                'success' => true,
                'ad_groups' => count($adGroups),
                'total_terms' => $totalTerms,
                'redirect' => url("/ads-analyzer/projects/{$projectId}/landing-urls")
            ]);

        } catch (\Exception $e) {
            error_log("=== UPLOAD EXCEPTION ===");
            error_log("Message: " . $e->getMessage());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 2: Contesto business
     */
    public function context(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $adGroups = AdGroup::getByProject($projectId);
        $savedContexts = BusinessContext::getByUser($user['id']);

        // Stima crediti
        $adGroupCount = count($adGroups);
        $estimatedCredits = $adGroupCount <= 3 ? $adGroupCount * 2 : ceil($adGroupCount * 1.5);

        return View::render('ads-analyzer/analysis/context', [
            'title' => 'Contesto Business - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'adGroups' => $adGroups,
            'savedContexts' => $savedContexts,
            'estimatedCredits' => $estimatedCredits,
            'userCredits' => Credits::getBalance($user['id'])
        ]);
    }

    /**
     * Step 3: Esegui analisi AI
     */
    public function analyze(int $projectId): void
    {
        error_log("=== ADS-ANALYZER: ANALYZE START ===");
        error_log("Project ID: " . $projectId);
        error_log("POST data: " . print_r($_POST, true));

        try {
            $user = Auth::user();
            error_log("User ID: " . ($user['id'] ?? 'NULL'));

            $project = Project::findByUserAndId($user['id'], $projectId);

            if (!$project) {
                error_log("ERROR: Project not found for user {$user['id']}");
                jsonResponse(['error' => 'Progetto non trovato'], 404);
            }

            $businessContext = $_POST['business_context'] ?? '';
            $analysisName = $_POST['analysis_name'] ?? ('Analisi ' . date('d/m/Y H:i'));
            $contextMode = $_POST['context_mode'] ?? 'manual';
            error_log("Business Context length: " . strlen($businessContext));

            // Valida
            $validationErrors = ValidationService::validateBusinessContext($businessContext);
            if (!empty($validationErrors)) {
                error_log("Validation errors: " . implode(', ', $validationErrors));
                jsonResponse(['error' => implode(', ', $validationErrors)], 400);
            }

            // Salva contesto nel progetto
            Project::update($projectId, ['business_context' => $businessContext]);
            error_log("Business context saved");

            // Verifica crediti
            $adGroups = AdGroup::getByProject($projectId);
            $adGroupCount = count($adGroups);
            error_log("Ad Groups count: " . $adGroupCount);

            // Calcola crediti necessari (costo singolo vs bulk discount)
            $singleCost = Credits::getCost('ad_group_analysis', 'ads-analyzer', 2);
            $bulkDiscount = Credits::getCost('bulk_analysis_discount', 'ads-analyzer', 0.75);
            $creditsNeeded = $adGroupCount <= 3
                ? $adGroupCount * $singleCost
                : ceil($adGroupCount * $singleCost * $bulkDiscount);
            $creditsAvailable = Credits::getBalance($user['id']);
            error_log("Credits needed: $creditsNeeded, available: $creditsAvailable");

            if (!Credits::hasEnough($user['id'], $creditsNeeded)) {
                error_log("ERROR: Insufficient credits");
                jsonResponse(['error' => "Crediti insufficienti. Necessari: {$creditsNeeded}"], 400);
            }

            // Crea record Analysis
            $analysisId = Analysis::create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'name' => $analysisName,
                'business_context' => $businessContext,
                'context_mode' => $contextMode
            ]);
            error_log("Created Analysis ID: $analysisId");

            // Avvia analisi
            Analysis::updateStatus($analysisId, 'analyzing');
            Project::update($projectId, ['status' => 'analyzing']);
            error_log("Analysis and Project status set to 'analyzing'");

            $results = [];
            $totalNegatives = 0;
            $totalCategories = 0;
            $errors = [];

            foreach ($adGroups as $index => $adGroup) {
                error_log("--- Processing Ad Group $index: {$adGroup['name']} (ID: {$adGroup['id']}) ---");

                try {
                    AdGroup::update($adGroup['id'], ['analysis_status' => 'analyzing']);

                    // Prendi termini
                    $terms = SearchTerm::getByAdGroup($adGroup['id']);
                    error_log("Terms count for {$adGroup['name']}: " . count($terms));

                    if (empty($terms)) {
                        error_log("WARNING: No terms for ad group {$adGroup['id']}");
                        continue;
                    }

                    error_log("Calling KeywordAnalyzerService->analyzeAdGroup()...");

                    // Analizza con AI
                    $aiResult = $this->analyzer->analyzeAdGroup(
                        $user['id'],
                        $businessContext,
                        $terms
                    );

                    error_log("Analysis result keys: " . implode(', ', array_keys($aiResult)));
                    error_log("Categories count: " . count($aiResult['categories'] ?? []));

                    // Salva categorie e keyword con analysis_id
                    error_log("Saving analysis results...");
                    $this->saveAnalysisResults($projectId, $adGroup['id'], $aiResult, $analysisId);
                    error_log("Results saved for Ad Group {$adGroup['id']}");

                    $keywordsCount = 0;
                    foreach ($aiResult['categories'] ?? [] as $cat) {
                        $keywordsCount += count($cat['keywords'] ?? []);
                    }
                    $totalNegatives += $keywordsCount;
                    $totalCategories += count($aiResult['categories'] ?? []);

                    AdGroup::update($adGroup['id'], [
                        'analysis_status' => 'completed',
                        'analyzed_at' => date('Y-m-d H:i:s')
                    ]);

                    $results[$adGroup['name']] = [
                        'success' => true,
                        'categories' => count($aiResult['categories'] ?? []),
                        'keywords' => $keywordsCount
                    ];

                    error_log("Ad Group {$adGroup['name']} completed: {$keywordsCount} keywords");

                } catch (\Exception $e) {
                    error_log("ERROR in Ad Group {$adGroup['name']}: " . $e->getMessage());
                    AdGroup::update($adGroup['id'], ['analysis_status' => 'error']);
                    $errors[] = "{$adGroup['name']}: {$e->getMessage()}";
                    $results[$adGroup['name']] = ['success' => false, 'error' => $e->getMessage()];
                }
            }

            // Aggiorna Analysis
            Analysis::update($analysisId, [
                'ad_groups_analyzed' => $adGroupCount,
                'total_categories' => $totalCategories,
                'total_keywords' => $totalNegatives,
                'credits_used' => $creditsNeeded
            ]);
            Analysis::updateStatus($analysisId, 'completed');

            // Aggiorna progetto
            Project::update($projectId, [
                'status' => 'completed',
                'total_negatives_found' => $totalNegatives
            ]);
            error_log("Analysis and Project status updated to 'completed', total negatives: $totalNegatives");

            error_log("=== ADS-ANALYZER: ANALYZE SUCCESS ===");

            jsonResponse([
                'success' => true,
                'analysis_id' => $analysisId,
                'results' => $results,
                'total_negatives' => $totalNegatives,
                'errors' => $errors,
                'redirect' => url("/ads-analyzer/projects/{$projectId}/analyses/{$analysisId}")
            ]);

        } catch (\Exception $e) {
            error_log("=== ADS-ANALYZER: ANALYZE EXCEPTION ===");
            error_log("Message: " . $e->getMessage());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());

            // Se l'analisi era stata creata, segna errore
            if (isset($analysisId)) {
                Analysis::updateStatus($analysisId, 'error', $e->getMessage());
            }

            jsonResponse(['error' => 'Errore: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 4: Risultati
     */
    public function results(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $adGroups = AdGroup::getByProject($projectId);

        // Carica categorie e keyword per ogni Ad Group
        $analysisData = [];
        foreach ($adGroups as $adGroup) {
            $categories = NegativeCategory::getByAdGroupWithCounts($adGroup['id']);
            foreach ($categories as &$cat) {
                $cat['keywords'] = NegativeKeyword::getByCategory($cat['id']);
            }
            $analysisData[$adGroup['id']] = [
                'ad_group' => $adGroup,
                'categories' => $categories
            ];
        }

        $selectedCount = NegativeKeyword::countSelectedByProject($projectId);
        $totalNegatives = NegativeKeyword::countByProject($projectId);

        return View::render('ads-analyzer/analysis/results', [
            'title' => 'Risultati - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'adGroups' => $adGroups,
            'analysisData' => $analysisData,
            'selectedCount' => $selectedCount,
            'totalNegatives' => $totalNegatives
        ]);
    }

    /**
     * Toggle selezione keyword
     */
    public function toggleKeyword(int $projectId, int $keywordId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
        }

        $keyword = NegativeKeyword::find($keywordId);
        if (!$keyword || $keyword['project_id'] != $projectId) {
            jsonResponse(['error' => 'Keyword non trovata'], 404);
        }

        $newValue = !$keyword['is_selected'];
        NegativeKeyword::update($keywordId, ['is_selected' => $newValue]);

        $selectedCount = NegativeKeyword::countSelectedByProject($projectId);

        jsonResponse([
            'success' => true,
            'is_selected' => $newValue,
            'selected_count' => $selectedCount
        ]);
    }

    /**
     * Bulk toggle categoria
     */
    public function toggleCategory(int $projectId, int $categoryId, string $action): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
        }

        if ($action === 'invert') {
            NegativeKeyword::invertByCategory($categoryId);
        } else {
            $newValue = $action === 'select_all';
            NegativeKeyword::updateByCategory($categoryId, ['is_selected' => $newValue]);
        }

        $selectedCount = NegativeKeyword::countSelectedByProject($projectId);

        jsonResponse([
            'success' => true,
            'selected_count' => $selectedCount
        ]);
    }

    /**
     * Salva risultati analisi in DB
     */
    private function saveAnalysisResults(int $projectId, int $adGroupId, array $analysis, ?int $analysisId = null): void
    {
        // Non pulisce piu i dati precedenti - ogni analisi e' indipendente
        // I dati vecchi senza analysis_id rimangono per retrocompatibilita

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
                'sort_order' => $sortOrder++
            ]);

            foreach ($data['keywords'] ?? [] as $keyword) {
                NegativeKeyword::create([
                    'project_id' => $projectId,
                    'ad_group_id' => $adGroupId,
                    'analysis_id' => $analysisId,
                    'category_id' => $categoryId,
                    'keyword' => $keyword,
                    'is_selected' => ($data['priority'] ?? 'medium') !== 'evaluate'
                ]);
            }
        }
    }

    private function formatCategoryName(string $key): string
    {
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }

    // ============================================
    // LANDING URLS - Estrazione contesto automatica
    // ============================================

    /**
     * Step 2 alternativo: Assegna URL landing agli Ad Group
     */
    public function landingUrls(int $projectId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $adGroups = AdGroup::getByProject($projectId);

        return View::render('ads-analyzer/analysis/landing-urls', [
            'title' => 'Assegna Landing Pages - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'adGroups' => $adGroups,
            'userCredits' => Credits::getBalance($user['id'])
        ]);
    }

    /**
     * Salva URL landing per Ad Group (AJAX)
     */
    public function saveLandingUrl(int $projectId, int $adGroupId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
            return;
        }

        $url = trim($_POST['landing_url'] ?? '');

        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            jsonResponse(['error' => 'URL non valido'], 400);
            return;
        }

        AdGroup::updateLandingUrl($adGroupId, $url);

        jsonResponse(['success' => true]);
    }

    /**
     * Scrape e estrai contesto per un Ad Group (AJAX)
     */
    public function extractContext(int $projectId, int $adGroupId): void
    {
        error_log("=== EXTRACT CONTEXT START ===");

        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
            return;
        }

        $adGroup = AdGroup::find($adGroupId);
        if (!$adGroup || empty($adGroup['landing_url'])) {
            jsonResponse(['error' => 'URL landing non impostato'], 400);
            return;
        }

        // Verifica crediti per estrazione contesto
        $contextCost = Credits::getCost('context_extraction', 'ads-analyzer');
        if (!Credits::hasEnough($user['id'], $contextCost)) {
            jsonResponse(['error' => "Crediti insufficienti (servono {$contextCost} crediti)"], 400);
            return;
        }

        $extractor = new ContextExtractorService();
        $result = $extractor->extractFromUrl($user['id'], $adGroup['landing_url']);

        if (!$result['success']) {
            jsonResponse(['error' => $result['error']], 500);
            return;
        }

        // Salva risultati
        AdGroup::saveScrapedContent($adGroupId, $result['scraped_content']);
        AdGroup::saveExtractedContext($adGroupId, $result['extracted_context']);

        // Consuma crediti per scraping+AI
        Credits::consume($user['id'], $contextCost, 'context_extraction', 'ads-analyzer');

        error_log("=== EXTRACT CONTEXT SUCCESS ===");

        jsonResponse([
            'success' => true,
            'context' => $result['extracted_context']
        ]);
    }

    /**
     * Scrape tutti gli Ad Group con URL (AJAX)
     */
    public function extractAllContexts(int $projectId): void
    {
        error_log("=== EXTRACT ALL CONTEXTS START ===");

        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
            return;
        }

        $adGroups = AdGroup::getByProject($projectId);
        $adGroupsWithUrl = array_filter($adGroups, fn($ag) => !empty($ag['landing_url']));

        if (empty($adGroupsWithUrl)) {
            jsonResponse(['error' => 'Nessun Ad Group con URL landing'], 400);
            return;
        }

        $contextCost = Credits::getCost('context_extraction', 'ads-analyzer');
        $creditsNeeded = count($adGroupsWithUrl) * $contextCost;
        if (!Credits::hasEnough($user['id'], $creditsNeeded)) {
            jsonResponse(['error' => "Crediti insufficienti (servono $creditsNeeded crediti)"], 400);
            return;
        }

        $extractor = new ContextExtractorService();
        $results = [];

        foreach ($adGroupsWithUrl as $adGroup) {
            error_log("Extracting context for: {$adGroup['name']}");

            $result = $extractor->extractFromUrl($user['id'], $adGroup['landing_url']);

            if ($result['success']) {
                AdGroup::saveScrapedContent($adGroup['id'], $result['scraped_content']);
                AdGroup::saveExtractedContext($adGroup['id'], $result['extracted_context']);
                Credits::consume($user['id'], $contextCost, 'context_extraction', 'ads-analyzer', ['ad_group' => $adGroup['name']]);

                $results[] = [
                    'ad_group_id' => $adGroup['id'],
                    'name' => $adGroup['name'],
                    'success' => true,
                    'context' => $result['extracted_context']
                ];
            } else {
                $results[] = [
                    'ad_group_id' => $adGroup['id'],
                    'name' => $adGroup['name'],
                    'success' => false,
                    'error' => $result['error']
                ];
            }
        }

        error_log("=== EXTRACT ALL CONTEXTS COMPLETE ===");

        jsonResponse([
            'success' => true,
            'results' => $results
        ]);
    }
}
