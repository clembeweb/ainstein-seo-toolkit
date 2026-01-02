<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\NegativeCategory;
use Modules\AdsAnalyzer\Models\NegativeKeyword;
use Modules\AdsAnalyzer\Models\BusinessContext;
use Modules\AdsAnalyzer\Services\CsvParserService;
use Modules\AdsAnalyzer\Services\KeywordAnalyzerService;
use Modules\AdsAnalyzer\Services\ValidationService;

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
    public function upload(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        View::render('ads-analyzer/analysis/upload', [
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
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }

        // Valida file
        if (!isset($_FILES['csv_file'])) {
            jsonResponse(['error' => 'Nessun file caricato'], 400);
        }

        $errors = ValidationService::validateCsvUpload($_FILES['csv_file']);
        if (!empty($errors)) {
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
                'redirect' => url("/ads-analyzer/projects/{$projectId}/context")
            ]);

        } catch (\Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 2: Contesto business
     */
    public function context(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        $adGroups = AdGroup::getByProject($projectId);
        $savedContexts = BusinessContext::getByUser($user['id']);

        // Stima crediti
        $adGroupCount = count($adGroups);
        $estimatedCredits = $adGroupCount <= 3 ? $adGroupCount * 2 : ceil($adGroupCount * 1.5);

        View::render('ads-analyzer/analysis/context', [
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
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }

        $businessContext = $_POST['business_context'] ?? '';

        // Valida
        $errors = ValidationService::validateBusinessContext($businessContext);
        if (!empty($errors)) {
            jsonResponse(['error' => implode(', ', $errors)], 400);
        }

        // Salva contesto nel progetto
        Project::update($projectId, ['business_context' => $businessContext]);

        // Verifica crediti
        $adGroups = AdGroup::getByProject($projectId);
        $adGroupCount = count($adGroups);
        $creditsNeeded = $adGroupCount <= 3 ? $adGroupCount * 2 : ceil($adGroupCount * 1.5);

        if (!Credits::hasEnough($user['id'], $creditsNeeded)) {
            jsonResponse(['error' => "Crediti insufficienti. Necessari: {$creditsNeeded}"], 400);
        }

        // Avvia analisi
        Project::update($projectId, ['status' => 'analyzing']);

        $results = [];
        $totalNegatives = 0;
        $errors = [];

        foreach ($adGroups as $adGroup) {
            try {
                AdGroup::update($adGroup['id'], ['analysis_status' => 'analyzing']);

                // Prendi termini
                $terms = SearchTerm::getByAdGroup($adGroup['id']);

                // Analizza con AI
                $analysis = $this->analyzer->analyzeAdGroup(
                    $user['id'],
                    $businessContext,
                    $terms
                );

                // Salva categorie e keyword
                $this->saveAnalysisResults($projectId, $adGroup['id'], $analysis);

                $keywordsCount = 0;
                foreach ($analysis['categories'] ?? [] as $cat) {
                    $keywordsCount += count($cat['keywords'] ?? []);
                }
                $totalNegatives += $keywordsCount;

                AdGroup::update($adGroup['id'], [
                    'analysis_status' => 'completed',
                    'analyzed_at' => date('Y-m-d H:i:s')
                ]);

                $results[$adGroup['name']] = [
                    'success' => true,
                    'categories' => count($analysis['categories'] ?? []),
                    'keywords' => $keywordsCount
                ];

            } catch (\Exception $e) {
                AdGroup::update($adGroup['id'], ['analysis_status' => 'error']);
                $errors[] = "{$adGroup['name']}: {$e->getMessage()}";
                $results[$adGroup['name']] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Aggiorna progetto
        Project::update($projectId, [
            'status' => 'completed',
            'total_negatives_found' => $totalNegatives
        ]);

        jsonResponse([
            'success' => true,
            'results' => $results,
            'total_negatives' => $totalNegatives,
            'errors' => $errors,
            'redirect' => url("/ads-analyzer/projects/{$projectId}/results")
        ]);
    }

    /**
     * Step 4: Risultati
     */
    public function results(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
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

        View::render('ads-analyzer/analysis/results', [
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
    private function saveAnalysisResults(int $projectId, int $adGroupId, array $analysis): void
    {
        // Pulisci categorie precedenti
        NegativeKeyword::deleteByAdGroup($adGroupId);
        NegativeCategory::deleteByAdGroup($adGroupId);

        $sortOrder = 0;
        foreach ($analysis['categories'] ?? [] as $key => $data) {
            $categoryId = NegativeCategory::create([
                'project_id' => $projectId,
                'ad_group_id' => $adGroupId,
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
}
