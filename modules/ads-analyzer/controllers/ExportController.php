<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\Auth;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\NegativeKeyword;

class ExportController
{
    private function requireNegKwType(array $project, int $projectId): void
    {
        if (($project['type'] ?? 'negative-kw') !== 'negative-kw') {
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
            exit;
        }
    }

    /**
     * Export keyword selezionate per Ad Group
     */
    public function exportAdGroup(int $projectId, int $adGroupId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        $adGroup = AdGroup::find($adGroupId);

        if (!$project || !$adGroup || $adGroup['project_id'] != $projectId) {
            http_response_code(404);
            exit('Non trovato');
        }

        $this->requireNegKwType($project, $projectId);

        $keywords = NegativeKeyword::getSelectedByAdGroup($adGroupId);

        $this->downloadCsv(
            $keywords,
            "negative-keywords-{$adGroup['name']}.csv"
        );
    }

    /**
     * Export tutte le keyword selezionate (tutti gli Ad Group)
     */
    public function exportAll(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            http_response_code(404);
            exit('Non trovato');
        }

        $this->requireNegKwType($project, $projectId);

        $keywords = NegativeKeyword::getSelectedByProjectWithAdGroup($projectId);

        $this->downloadCsv(
            $keywords,
            "negative-keywords-{$project['name']}.csv",
            true // Include colonna Ad Group
        );
    }

    /**
     * Export per Google Ads Editor (formato bulk)
     */
    public function exportGoogleAdsEditor(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            http_response_code(404);
            exit('Non trovato');
        }

        $this->requireNegKwType($project, $projectId);

        $keywords = NegativeKeyword::getSelectedByProjectWithAdGroup($projectId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="google-ads-editor-import.csv"');

        $output = fopen('php://output', 'w');

        // Header Google Ads Editor
        fputcsv($output, ['Campaign', 'Ad Group', 'Keyword', 'Match Type', 'Status']);

        foreach ($keywords as $kw) {
            fputcsv($output, [
                '', // Campaign vuoto = usa Ad Group level
                $kw['ad_group_name'],
                $kw['keyword'],
                'Phrase', // Default phrase match
                'Enabled'
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Copia in formato testo semplice (per incolla rapido)
     */
    public function copyText(int $projectId, ?int $adGroupId = null): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);

        if (!$project) {
            jsonResponse(['error' => 'Non trovato'], 404);
        }

        $this->requireNegKwType($project, $projectId);

        if ($adGroupId) {
            $keywords = NegativeKeyword::getSelectedByAdGroup($adGroupId);
        } else {
            $keywords = NegativeKeyword::getSelectedByProject($projectId);
        }

        $text = implode("\n", array_column($keywords, 'keyword'));

        jsonResponse(['success' => true, 'text' => $text, 'count' => count($keywords)]);
    }

    private function downloadCsv(array $keywords, string $filename, bool $includeAdGroup = false): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header
        $header = $includeAdGroup
            ? ['Keyword', 'Ad Group', 'Categoria', 'Priorita']
            : ['Keyword', 'Categoria', 'Priorita'];
        fputcsv($output, $header, ';');

        foreach ($keywords as $kw) {
            $row = $includeAdGroup
                ? [$kw['keyword'], $kw['ad_group_name'] ?? '', $kw['category_name'] ?? '', $kw['priority'] ?? '']
                : [$kw['keyword'], $kw['category_name'] ?? '', $kw['priority'] ?? ''];
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}
