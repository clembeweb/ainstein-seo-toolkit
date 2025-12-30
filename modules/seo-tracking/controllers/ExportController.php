<?php

namespace Modules\SeoTracking\Controllers;

use Core\Auth;
use Core\Router;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscData;
use Modules\SeoTracking\Models\Ga4Data;
use Modules\SeoTracking\Models\KeywordRevenue;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Ga4Daily;

/**
 * ExportController
 * Gestisce export dati in CSV
 */
class ExportController
{
    private Project $project;
    private Keyword $keyword;
    private GscData $gscData;
    private Ga4Data $ga4Data;
    private KeywordRevenue $keywordRevenue;
    private GscDaily $gscDaily;
    private Ga4Daily $ga4Daily;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscData = new GscData();
        $this->ga4Data = new Ga4Data();
        $this->keywordRevenue = new KeywordRevenue();
        $this->gscDaily = new GscDaily();
        $this->ga4Daily = new Ga4Daily();
    }

    /**
     * Export keywords
     */
    public function keywords(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $keywords = $this->keyword->allWithPositions($projectId, 30);

        $filename = "keywords_{$project['domain']}_" . date('Y-m-d') . ".csv";

        $this->outputCsv($filename, [
            ['Keyword', 'Gruppo', 'Posizione', 'Variazione', 'Click', 'Impressioni', 'CTR', 'Tracciata', 'Target URL'],
        ], array_map(fn($k) => [
            $k['keyword'],
            $k['group_name'] ?? '',
            $k['last_position'] ?? '',
            $k['position_change'] ?? 0,
            $k['last_clicks'] ?? 0,
            $k['last_impressions'] ?? 0,
            ($k['last_impressions'] ?? 0) > 0 ? round(($k['last_clicks'] / $k['last_impressions']) * 100, 2) . '%' : '0%',
            $k['is_tracked'] ? 'Si' : 'No',
            $k['target_url'] ?? '',
        ], $keywords));
    }

    /**
     * Export GSC data
     */
    public function gscData(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $data = $this->gscDaily->getByDateRange($projectId, $startDate, $endDate);

        $filename = "gsc_data_{$project['domain']}_" . date('Y-m-d') . ".csv";

        $this->outputCsv($filename, [
            ['Data', 'Click', 'Impressioni', 'CTR', 'Posizione Media', 'Query Uniche', 'Pagine Uniche'],
        ], array_map(fn($d) => [
            $d['date'],
            $d['total_clicks'],
            $d['total_impressions'],
            round($d['avg_ctr'] * 100, 2) . '%',
            round($d['avg_position'], 1),
            $d['unique_queries'] ?? 0,
            $d['unique_pages'] ?? 0,
        ], $data));
    }

    /**
     * Export GA4 data
     */
    public function ga4Data(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $data = $this->ga4Daily->getByDateRange($projectId, $startDate, $endDate);

        $filename = "ga4_data_{$project['domain']}_" . date('Y-m-d') . ".csv";

        $this->outputCsv($filename, [
            ['Data', 'Sessioni', 'Utenti', 'Nuovi Utenti', 'Bounce Rate', 'Engagement Rate', 'Add to Cart', 'Acquisti', 'Revenue'],
        ], array_map(fn($d) => [
            $d['date'],
            $d['sessions'],
            $d['users'],
            $d['new_users'],
            round($d['bounce_rate'] * 100, 2) . '%',
            round($d['engagement_rate'] * 100, 2) . '%',
            $d['add_to_carts'],
            $d['purchases'],
            number_format($d['revenue'], 2),
        ], $data));
    }

    /**
     * Export revenue attribution
     */
    public function revenue(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $data = $this->keywordRevenue->getTopByRevenue($projectId, $startDate, $endDate, 1000);

        $filename = "revenue_attribution_{$project['domain']}_" . date('Y-m-d') . ".csv";

        $this->outputCsv($filename, [
            ['Keyword', 'Landing Page', 'Click', 'Acquisti', 'Revenue'],
        ], array_map(fn($d) => [
            $d['keyword'],
            $d['landing_page'] ?? '',
            $d['total_clicks'],
            round($d['total_purchases'], 1),
            number_format($d['total_revenue'], 2),
        ], $data));
    }

    /**
     * Export completo progetto
     */
    public function full(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        // Crea ZIP con tutti i CSV
        $zipFilename = "seo_export_{$project['domain']}_" . date('Y-m-d') . ".zip";
        $tempDir = sys_get_temp_dir() . '/seo_export_' . uniqid();
        mkdir($tempDir);

        $days = (int) ($_GET['days'] ?? 30);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        // Keywords
        $keywords = $this->keyword->allWithPositions($projectId, $days);
        $this->writeCsvFile($tempDir . '/keywords.csv', [
            ['Keyword', 'Gruppo', 'Posizione', 'Variazione', 'Click', 'Impressioni', 'Tracciata'],
        ], array_map(fn($k) => [
            $k['keyword'], $k['group_name'] ?? '', $k['last_position'] ?? '', $k['position_change'] ?? 0,
            $k['last_clicks'] ?? 0, $k['last_impressions'] ?? 0, $k['is_tracked'] ? 'Si' : 'No',
        ], $keywords));

        // GSC Daily
        $gscData = $this->gscDaily->getByDateRange($projectId, $startDate, $endDate);
        $this->writeCsvFile($tempDir . '/gsc_daily.csv', [
            ['Data', 'Click', 'Impressioni', 'CTR', 'Posizione Media'],
        ], array_map(fn($d) => [
            $d['date'], $d['total_clicks'], $d['total_impressions'],
            round($d['avg_ctr'] * 100, 2) . '%', round($d['avg_position'], 1),
        ], $gscData));

        // GA4 Daily
        $ga4Data = $this->ga4Daily->getByDateRange($projectId, $startDate, $endDate);
        $this->writeCsvFile($tempDir . '/ga4_daily.csv', [
            ['Data', 'Sessioni', 'Utenti', 'Acquisti', 'Revenue'],
        ], array_map(fn($d) => [
            $d['date'], $d['sessions'], $d['users'], $d['purchases'], number_format($d['revenue'], 2),
        ], $ga4Data));

        // Revenue
        $revenueData = $this->keywordRevenue->getTopByRevenue($projectId, $startDate, $endDate, 500);
        $this->writeCsvFile($tempDir . '/revenue.csv', [
            ['Keyword', 'Click', 'Acquisti', 'Revenue'],
        ], array_map(fn($d) => [
            $d['keyword'], $d['total_clicks'], round($d['total_purchases'], 1), number_format($d['total_revenue'], 2),
        ], $revenueData));

        // Crea ZIP
        $zip = new \ZipArchive();
        $zipPath = $tempDir . '/' . $zipFilename;
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach (glob($tempDir . '/*.csv') as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        // Output ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);

        // Cleanup
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        exit;
    }

    /**
     * Output CSV al browser
     */
    private function outputCsv(string $filename, array $headers, array $data): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        foreach ($headers as $row) {
            fputcsv($output, $row, ';');
        }

        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Scrivi CSV su file
     */
    private function writeCsvFile(string $path, array $headers, array $data): void
    {
        $output = fopen($path, 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($headers as $row) {
            fputcsv($output, $row, ';');
        }

        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
    }
}
