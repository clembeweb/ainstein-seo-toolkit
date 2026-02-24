<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\RankCheck;

/**
 * UrlController
 * Gestisce la visualizzazione delle URLs raggruppate per keyword
 */
class UrlController
{
    private Project $project;
    private RankCheck $rankCheck;

    public function __construct()
    {
        $this->project = new Project();
        $this->rankCheck = new RankCheck();
    }

    /**
     * Lista URLs raggruppate per keyword
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Paginazione
        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Filtri
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'min_keywords' => (int) ($_GET['min_keywords'] ?? 1),
            'max_position' => !empty($_GET['max_position']) ? (int) $_GET['max_position'] : 0,
            'search' => $_GET['search'] ?? '',
        ];

        // Conta totale per paginazione
        $totalCount = $this->rankCheck->countUrlsGrouped($projectId, $filters);
        $totalPages = max(1, ceil($totalCount / $perPage));

        // Assicurati che la pagina corrente non superi il totale
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        // Aggiungi limit/offset
        $filters['limit'] = $perPage;
        $filters['offset'] = $offset;

        // Ottieni URLs raggruppate
        $urls = $this->rankCheck->getUrlsGrouped($projectId, $filters);

        // Per ogni URL, ottieni le keyword (per accordion)
        foreach ($urls as &$urlData) {
            $urlData['keywords'] = $this->rankCheck->getKeywordsByUrl($projectId, $urlData['url'], [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
            ]);
        }
        unset($urlData); // Rompi riferimento

        // Calcola statistiche globali per le cards
        $stats = $this->calculateGlobalStats($urls, $totalCount);

        return View::render('seo-tracking/urls/index', [
            'title' => 'URLs - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'urls' => $urls,
            'filters' => $filters,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Dettaglio singola URL con tutte le keyword associate
     */
    public function show(int $projectId, string $urlHash): string
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Decodifica URL hash (base64)
        $url = base64_decode($urlHash);

        if (empty($url)) {
            $_SESSION['_flash']['error'] = 'URL non valida';
            Router::redirect('/seo-tracking/project/' . $projectId . '/urls');
            exit;
        }

        // Filtri date
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
        ];

        // Ottieni tutte le keyword per questa URL
        $keywords = $this->rankCheck->getKeywordsByUrl($projectId, $url, $filters);

        if (empty($keywords)) {
            $_SESSION['_flash']['warning'] = 'Nessun dato trovato per questa URL nel periodo selezionato';
        }

        // Calcola statistiche dalla lista keywords
        $stats = $this->calculateUrlStats($keywords);

        return View::render('seo-tracking/urls/show', [
            'title' => 'Dettaglio URL - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'url' => $url,
            'keywords' => $keywords,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * API: Esporta URLs in CSV
     */
    public function export(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->findAccessible($projectId, $user['id']);

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Filtri
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'min_keywords' => (int) ($_GET['min_keywords'] ?? 1),
            'max_position' => !empty($_GET['max_position']) ? (int) $_GET['max_position'] : 0,
            'search' => $_GET['search'] ?? '',
        ];

        // Ottieni tutte le URLs (senza limite per export)
        $urls = $this->rankCheck->getUrlsGrouped($projectId, $filters);

        // Genera CSV
        $filename = 'urls_' . $project['slug'] . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Header CSV
        fputcsv($output, [
            'URL',
            'Numero Keywords',
            'Posizione Media',
            'Posizione Minima',
            'Posizione Massima',
            'Ultimo Check',
        ]);

        // Dati
        foreach ($urls as $urlData) {
            fputcsv($output, [
                $urlData['url'],
                $urlData['keyword_count'] ?? 0,
                round($urlData['avg_position'] ?? 0, 1),
                $urlData['best_position'] ?? '-',
                '-', // max_position non disponibile nel model attuale
                $urlData['last_check'] ?? '-',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Calcola statistiche URL dalla lista keywords
     */
    private function calculateUrlStats(array $keywords): array
    {
        if (empty($keywords)) {
            return [
                'total_keywords' => 0,
                'avg_position' => null,
                'best_position' => null,
                'worst_position' => null,
                'top3_count' => 0,
                'top10_count' => 0,
            ];
        }

        $positions = array_filter(array_column($keywords, 'serp_position'), fn($p) => $p !== null);

        if (empty($positions)) {
            return [
                'total_keywords' => count($keywords),
                'avg_position' => null,
                'best_position' => null,
                'worst_position' => null,
                'top3_count' => 0,
                'top10_count' => 0,
            ];
        }

        return [
            'total_keywords' => count($keywords),
            'avg_position' => round(array_sum($positions) / count($positions), 1),
            'best_position' => min($positions),
            'worst_position' => max($positions),
            'top3_count' => count(array_filter($positions, fn($p) => $p <= 3)),
            'top10_count' => count(array_filter($positions, fn($p) => $p <= 10)),
        ];
    }

    /**
     * Calcola statistiche globali per le cards della vista index
     */
    private function calculateGlobalStats(array $urls, int $totalUrls): array
    {
        if (empty($urls)) {
            return [
                'total_urls' => $totalUrls,
                'total_keywords' => 0,
                'avg_position' => null,
                'best_position' => null,
            ];
        }

        $totalKeywords = 0;
        $positions = [];
        $bestPosition = null;

        foreach ($urls as $urlData) {
            $totalKeywords += (int) ($urlData['keyword_count'] ?? 0);

            if (isset($urlData['avg_position']) && $urlData['avg_position'] > 0) {
                $positions[] = (float) $urlData['avg_position'];
            }

            if (isset($urlData['best_position']) && $urlData['best_position'] > 0) {
                if ($bestPosition === null || $urlData['best_position'] < $bestPosition) {
                    $bestPosition = (int) $urlData['best_position'];
                }
            }
        }

        return [
            'total_urls' => $totalUrls,
            'total_keywords' => $totalKeywords,
            'avg_position' => !empty($positions) ? round(array_sum($positions) / count($positions), 1) : null,
            'best_position' => $bestPosition,
        ];
    }
}
