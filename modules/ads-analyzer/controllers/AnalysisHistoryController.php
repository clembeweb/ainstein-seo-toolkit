<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\Analysis;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\NegativeCategory;
use Modules\AdsAnalyzer\Models\NegativeKeyword;

class AnalysisHistoryController
{
    /**
     * Lista analisi per un progetto
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

        $analyses = Analysis::findByProjectId($projectId);

        // Arricchisci con dati aggiuntivi
        foreach ($analyses as &$analysis) {
            $analysis['categories_count'] = NegativeCategory::countByAnalysis($analysis['id']);
            $analysis['keywords_count'] = NegativeKeyword::countByAnalysis($analysis['id']);
            $analysis['selected_count'] = NegativeKeyword::countSelectedByAnalysis($analysis['id']);
        }

        return View::render('ads-analyzer/analyses/index', [
            'title' => 'Storico Analisi - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'analyses' => $analyses
        ]);
    }

    /**
     * Dettaglio singola analisi
     */
    public function show(int $projectId, int $analysisId): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $analysis = Analysis::findByUserAndId($user['id'], $analysisId);

        if (!$analysis || $analysis['project_id'] != $projectId) {
            $_SESSION['flash_error'] = 'Analisi non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/analyses"));
            exit;
        }

        // Carica AdGroups del progetto
        $adGroups = AdGroup::getByProject($projectId);

        // Carica categorie e keyword per questa analisi
        $analysisData = [];
        foreach ($adGroups as $adGroup) {
            $categories = NegativeCategory::getByAnalysisAndAdGroup($analysisId, $adGroup['id']);
            foreach ($categories as &$cat) {
                $cat['keywords'] = NegativeKeyword::getByCategory($cat['id']);
            }
            if (!empty($categories)) {
                $analysisData[$adGroup['id']] = [
                    'ad_group' => $adGroup,
                    'categories' => $categories
                ];
            }
        }

        $selectedCount = NegativeKeyword::countSelectedByAnalysis($analysisId);
        $totalKeywords = NegativeKeyword::countByAnalysis($analysisId);

        return View::render('ads-analyzer/analyses/show', [
            'title' => $analysis['name'] . ' - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'analysis' => $analysis,
            'adGroups' => $adGroups,
            'analysisData' => $analysisData,
            'selectedCount' => $selectedCount,
            'totalKeywords' => $totalKeywords
        ]);
    }

    /**
     * Elimina analisi
     */
    public function delete(int $projectId, int $analysisId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
            return;
        }

        $analysis = Analysis::findByUserAndId($user['id'], $analysisId);

        if (!$analysis || $analysis['project_id'] != $projectId) {
            jsonResponse(['error' => 'Analisi non trovata'], 404);
            return;
        }

        // Le FK con ON DELETE CASCADE eliminano automaticamente categories e keywords
        Analysis::delete($analysisId);

        jsonResponse([
            'success' => true,
            'redirect' => url("/ads-analyzer/projects/{$projectId}/analyses")
        ]);
    }

    /**
     * Export keyword selezionate di un'analisi
     */
    public function export(int $projectId, int $analysisId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            $_SESSION['flash_error'] = 'Non autorizzato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $analysis = Analysis::findByUserAndId($user['id'], $analysisId);

        if (!$analysis || $analysis['project_id'] != $projectId) {
            $_SESSION['flash_error'] = 'Analisi non trovata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/analyses"));
            exit;
        }

        $keywords = NegativeKeyword::getSelectedByAnalysisWithAdGroup($analysisId);

        if (empty($keywords)) {
            $_SESSION['flash_error'] = 'Nessuna keyword selezionata';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/analyses/{$analysisId}"));
            exit;
        }

        // Prepara CSV per Google Ads Editor
        $filename = 'negative_keywords_' . $analysis['id'] . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM per Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Google Ads Editor
        fputcsv($output, ['Campaign', 'Ad Group', 'Keyword', 'Match Type', 'Status']);

        // Righe
        foreach ($keywords as $kw) {
            $matchType = $kw['suggested_match_type'] ?? 'Phrase';
            $matchTypeLabel = match($matchType) {
                'exact' => 'Exact',
                'phrase' => 'Phrase',
                'broad' => 'Broad',
                default => 'Phrase'
            };

            fputcsv($output, [
                '', // Campaign - da compilare in Google Ads Editor
                $kw['ad_group_name'],
                $kw['keyword'],
                $matchTypeLabel,
                'Enabled'
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Toggle selezione keyword (per pagina dettaglio analisi)
     */
    public function toggleKeyword(int $projectId, int $analysisId, int $keywordId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
            return;
        }

        $analysis = Analysis::findByUserAndId($user['id'], $analysisId);

        if (!$analysis || $analysis['project_id'] != $projectId) {
            jsonResponse(['error' => 'Analisi non trovata'], 404);
            return;
        }

        $keyword = NegativeKeyword::find($keywordId);
        if (!$keyword || $keyword['analysis_id'] != $analysisId) {
            jsonResponse(['error' => 'Keyword non trovata'], 404);
            return;
        }

        $newValue = !$keyword['is_selected'];
        NegativeKeyword::update($keywordId, ['is_selected' => $newValue]);

        $selectedCount = NegativeKeyword::countSelectedByAnalysis($analysisId);

        jsonResponse([
            'success' => true,
            'is_selected' => $newValue,
            'selected_count' => $selectedCount
        ]);
    }

    /**
     * Bulk toggle categoria (per pagina dettaglio analisi)
     */
    public function toggleCategory(int $projectId, int $analysisId, int $categoryId, string $action): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
            return;
        }

        $analysis = Analysis::findByUserAndId($user['id'], $analysisId);

        if (!$analysis || $analysis['project_id'] != $projectId) {
            jsonResponse(['error' => 'Analisi non trovata'], 404);
            return;
        }

        $category = NegativeCategory::find($categoryId);
        if (!$category || $category['analysis_id'] != $analysisId) {
            jsonResponse(['error' => 'Categoria non trovata'], 404);
            return;
        }

        if ($action === 'invert') {
            NegativeKeyword::invertByCategory($categoryId);
        } else {
            $newValue = $action === 'select_all';
            NegativeKeyword::updateByCategory($categoryId, ['is_selected' => $newValue]);
        }

        $selectedCount = NegativeKeyword::countSelectedByAnalysis($analysisId);

        jsonResponse([
            'success' => true,
            'selected_count' => $selectedCount
        ]);
    }
}
