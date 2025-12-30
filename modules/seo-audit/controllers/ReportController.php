<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Services\AuditAnalyzer;

/**
 * ReportController
 *
 * Gestisce export e report audit
 */
class ReportController
{
    private Project $projectModel;
    private AuditAnalyzer $analyzer;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->analyzer = new AuditAnalyzer();
    }

    /**
     * Export issues in CSV
     */
    public function exportCsv(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Get category filter if present
        $category = $_GET['category'] ?? null;

        // Get data
        $rows = $this->analyzer->exportIssuesForCsv($id, $category);

        // Generate filename
        $filename = 'seo-audit-' . $this->slugify($project['name']);
        if ($category) {
            $filename .= '-' . $category;
        }
        $filename .= '-' . date('Y-m-d') . '.csv';

        // Send headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV
        $output = fopen('php://output', 'w');

        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Export category issues in CSV
     */
    public function exportCategoryCsv(int $id, string $category): void
    {
        $_GET['category'] = $category;
        $this->exportCsv($id);
    }

    /**
     * Export pages in CSV
     */
    public function exportPagesCsv(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $pageModel = new \Modules\SeoAudit\Models\Page();
        $pagesData = $pageModel->getByProject($id, 1, 10000, []);

        // Generate filename
        $filename = 'seo-audit-pages-' . $this->slugify($project['name']) . '-' . date('Y-m-d') . '.csv';

        // Send headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV
        $output = fopen('php://output', 'w');

        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        fputcsv($output, [
            'URL',
            'Status Code',
            'Title',
            'Title Length',
            'Meta Description',
            'Description Length',
            'H1 Count',
            'Word Count',
            'Images Count',
            'Internal Links',
            'External Links',
            'Load Time (ms)',
            'Indexable',
            'Issues Count',
            'Crawled At'
        ], ';');

        foreach ($pagesData['data'] as $page) {
            fputcsv($output, [
                $page['url'],
                $page['status_code'],
                $page['title'] ?? '',
                $page['title_length'] ?? 0,
                $page['meta_description'] ?? '',
                $page['meta_description_length'] ?? 0,
                $page['h1_count'] ?? 0,
                $page['word_count'] ?? 0,
                $page['images_count'] ?? 0,
                $page['internal_links_count'] ?? 0,
                $page['external_links_count'] ?? 0,
                $page['load_time_ms'] ?? 0,
                $page['is_indexable'] ? 'Si' : 'No',
                $page['issues_count'] ?? 0,
                $page['crawled_at'] ?? ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Export summary report (placeholder for PDF)
     */
    public function exportPdf(int $id): void
    {
        // Placeholder - PDF export requires additional library
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Export PDF in fase di sviluppo. Utilizza export CSV.',
            'available_exports' => [
                'csv_issues' => url('/seo-audit/project/' . $id . '/export/csv'),
                'csv_pages' => url('/seo-audit/project/' . $id . '/export/pages-csv'),
            ]
        ]);
        exit;
    }

    /**
     * Summary data for reports (JSON)
     */
    public function summary(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $summary = $this->analyzer->getAuditSummary($id);
        $categoryStats = $this->analyzer->getStatsByCategory($id);
        $categoryScores = $this->analyzer->getCategoryScores($id);
        $topIssues = $this->analyzer->getTopIssuesByImpact($id, 20);

        header('Content-Type: application/json');
        echo json_encode([
            'project' => [
                'id' => $project['id'],
                'name' => $project['name'],
                'base_url' => $project['base_url'],
                'health_score' => $project['health_score'],
                'status' => $project['status'],
                'last_crawl' => $project['last_crawl_at'],
            ],
            'summary' => $summary,
            'categories' => $categoryStats,
            'category_scores' => $categoryScores,
            'top_issues' => $topIssues,
            'generated_at' => date('c'),
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Slugify string for filename
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return $text ?: 'export';
    }
}
