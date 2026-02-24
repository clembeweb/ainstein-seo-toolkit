<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\PositionCompareService;

/**
 * CompareController
 * Gestisce il confronto posizioni tra due periodi
 */
class CompareController
{
    /**
     * Vista principale confronto posizioni
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: /seo-tracking');
            exit;
        }

        $compareService = new PositionCompareService($projectId);
        $dateRange = $compareService->getAvailableDateRange();
        $presets = PositionCompareService::getPresets();

        // Date default: ultimi 7 giorni vs 7 giorni precedenti
        $preset = $_GET['preset'] ?? '7d';
        $presetData = $presets[$preset] ?? $presets['7d'];

        $dateFromA = $_GET['date_from_a'] ?? $presetData['dateFromA'];
        $dateToA = $_GET['date_to_a'] ?? $presetData['dateToA'];
        $dateFromB = $_GET['date_from_b'] ?? $presetData['dateFromB'];
        $dateToB = $_GET['date_to_b'] ?? $presetData['dateToB'];

        $source = $_GET['source'] ?? 'gsc';

        $filters = [
            'keyword' => $_GET['keyword'] ?? '',
            'url' => $_GET['url'] ?? ''
        ];

        if ($source === 'positions') {
            $results = $compareService->compareFromPositions($dateFromB, $dateToB, $filters);
        } else {
            $results = $compareService->compare($dateFromA, $dateToA, $dateFromB, $dateToB, $filters);
        }

        // Tab attiva
        $activeTab = $_GET['tab'] ?? 'all';

        return View::render('seo-tracking/trend/index', [
            'title' => $project['name'] . ' - Trend',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'results' => $results,
            'dateRange' => $dateRange,
            'presets' => $presets,
            'currentPreset' => $preset,
            'dateFromA' => $dateFromA,
            'dateToA' => $dateToA,
            'dateFromB' => $dateFromB,
            'dateToB' => $dateToB,
            'filters' => $filters,
            'activeTab' => $activeTab,
            'currentSource' => $source
        ]);
    }

    /**
     * API per refresh AJAX
     */
    public function getData(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $source = $input['source'] ?? 'gsc';
        $compareService = new PositionCompareService($projectId);

        if ($source === 'positions') {
            $results = $compareService->compareFromPositions(
                $input['date_from_b'] ?? '',
                $input['date_to_b'] ?? '',
                ['keyword' => $input['keyword'] ?? '']
            );
        } else {
            $results = $compareService->compare(
                $input['date_from_a'] ?? '',
                $input['date_to_a'] ?? '',
                $input['date_from_b'] ?? '',
                $input['date_to_b'] ?? '',
                [
                    'keyword' => $input['keyword'] ?? '',
                    'url' => $input['url'] ?? ''
                ]
            );
        }

        echo json_encode(['success' => true, 'data' => $results]);
        exit;
    }

    /**
     * Export CSV
     */
    public function export(int $projectId): void
    {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: /seo-tracking');
            exit;
        }

        $source = $_GET['source'] ?? 'gsc';
        $compareService = new PositionCompareService($projectId);

        if ($source === 'positions') {
            $results = $compareService->compareFromPositions(
                $_GET['date_from_b'] ?? '',
                $_GET['date_to_b'] ?? '',
                ['keyword' => $_GET['keyword'] ?? '']
            );
        } else {
            $results = $compareService->compare(
                $_GET['date_from_a'] ?? '',
                $_GET['date_to_a'] ?? '',
                $_GET['date_from_b'] ?? '',
                $_GET['date_to_b'] ?? '',
                [
                    'keyword' => $_GET['keyword'] ?? '',
                    'url' => $_GET['url'] ?? ''
                ]
            );
        }

        $tab = $_GET['tab'] ?? 'all';
        $data = $results[$tab] ?? $results['all'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="position-compare-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // BOM UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header
        fputcsv($output, [
            'Keyword',
            'Posizione Precedente',
            'Posizione Attuale',
            'Differenza',
            'Status',
            'Click Precedenti',
            'Click Attuali',
            'Impressioni Precedenti',
            'Impressioni Attuali',
            'Traffico %',
            'Volume',
            'URL'
        ], ';');

        // Rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['keyword'],
                $row['position_previous'] ?? '-',
                $row['position_current'] ?? '-',
                $row['diff'] ?? '-',
                $row['status'],
                $row['clicks_previous'],
                $row['clicks_current'],
                $row['impressions_previous'],
                $row['impressions_current'],
                $row['traffic_share'] . '%',
                $row['search_volume'] ?? '-',
                $row['url']
            ], ';');
        }

        fclose($output);
        exit;
    }
}
