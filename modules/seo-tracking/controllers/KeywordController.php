<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\KeywordPosition;
use Modules\SeoTracking\Models\GscData;
use Modules\SeoTracking\Models\Location;

/**
 * KeywordController
 * Gestisce CRUD e analisi keyword
 */
class KeywordController
{
    private Project $project;
    private Keyword $keyword;
    private KeywordPosition $keywordPosition;
    private GscData $gscData;
    private Location $location;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->keywordPosition = new KeywordPosition();
        $this->gscData = new GscData();
        $this->location = new Location();
    }

    /**
     * Lista keyword progetto
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $filters = [
            'search' => $_GET['search'] ?? '',
            'is_tracked' => $_GET['tracked'] ?? null,
            'group_name' => $_GET['group'] ?? null,
            'position_max' => $_GET['position'] ?? null,
        ];

        $keywords = $this->keyword->allWithPositions($projectId, 30, $filters);
        $groups = $this->keyword->getGroups($projectId);
        $stats = $this->keyword->getStats($projectId);

        return View::render('seo-tracking/keywords/index', [
            'title' => $project['name'] . ' - Keywords',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keywords' => $keywords,
            'groups' => $groups,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Form aggiunta keyword
     */
    public function create(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $groups = $this->keyword->getGroups($projectId);
        $locations = $this->location->all();

        return View::render('seo-tracking/keywords/create', [
            'title' => 'Aggiungi Keyword - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'groups' => $groups,
            'locations' => $locations,
        ]);
    }

    /**
     * Salva nuove keyword
     */
    public function store(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $keywordsInput = trim($_POST['keywords'] ?? '');
        $groupName = trim($_POST['group_name'] ?? '');
        $locationCode = trim($_POST['location_code'] ?? 'IT');
        $isTracked = isset($_POST['is_tracked']) ? 1 : 0;

        if (empty($keywordsInput)) {
            $_SESSION['_flash']['error'] = 'Inserisci almeno una keyword';
            Router::redirect('/seo-tracking/project/' . $projectId . '/keywords/add');
            return;
        }

        // Valida location
        $location = $this->location->findByCountryCode($locationCode);
        if (!$location) {
            $locationCode = 'IT'; // Fallback
        }

        // Parse keywords (una per riga)
        $lines = array_filter(array_map('trim', explode("\n", $keywordsInput)));
        $added = 0;
        $skipped = 0;

        foreach ($lines as $keyword) {
            if (empty($keyword)) continue;

            // Verifica duplicato
            $existing = $this->keyword->findByKeyword($projectId, $keyword);

            if ($existing) {
                $skipped++;
                continue;
            }

            $this->keyword->create([
                'project_id' => $projectId,
                'keyword' => $keyword,
                'location_code' => $locationCode,
                'group_name' => $groupName ?: null,
                'is_tracked' => $isTracked,
                'source' => 'manual',
            ]);

            $added++;
        }

        if ($added > 0) {
            $_SESSION['_flash']['success'] = "Aggiunte {$added} keyword" . ($skipped > 0 ? " ({$skipped} duplicate ignorate)" : '');
        } else {
            $_SESSION['_flash']['warning'] = 'Nessuna keyword aggiunta (tutte duplicate)';
        }

        Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
    }

    /**
     * Dettaglio keyword
     */
    public function show(int $id): string
    {
        $user = Auth::user();
        $keyword = $this->keyword->find($id);

        if (!$keyword) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            Router::redirect('/seo-tracking');
            exit;
        }

        $project = $this->project->find($keyword['project_id'], $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Storico posizioni (90 giorni)
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $positions = $this->keywordPosition->getByKeyword($id, $startDate, $endDate);

        // Dati GSC recenti
        $gscData = $this->gscData->getKeywordPositions($keyword['project_id'], $keyword['keyword'], $startDate, $endDate);

        return View::render('seo-tracking/keywords/show', [
            'title' => $keyword['keyword'] . ' - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keyword' => $keyword,
            'positions' => $positions,
            'gscData' => $gscData,
            'dateRange' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * Modifica keyword
     */
    public function edit(int $id): string
    {
        $user = Auth::user();
        $keyword = $this->keyword->find($id);

        if (!$keyword) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            Router::redirect('/seo-tracking');
            exit;
        }

        $project = $this->project->find($keyword['project_id'], $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $groups = $this->keyword->getGroups($keyword['project_id']);

        return View::render('seo-tracking/keywords/edit', [
            'title' => 'Modifica Keyword',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keyword' => $keyword,
            'groups' => $groups,
        ]);
    }

    /**
     * Aggiorna keyword
     */
    public function update(int $projectId, int $keywordId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $keyword = $this->keyword->find($keywordId);

        if (!$keyword || (int)$keyword['project_id'] !== $projectId) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
            return;
        }

        $groupName = trim($_POST['group_name'] ?? '');
        $targetPosition = !empty($_POST['target_position']) ? (int) $_POST['target_position'] : null;
        $targetUrl = trim($_POST['target_url'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $this->keyword->update($keywordId, [
            'group_name' => $groupName ?: null,
            'target_position' => $targetPosition,
            'target_url' => $targetUrl ?: null,
            'notes' => $notes ?: null,
            'is_tracked' => isset($_POST['is_tracked']) ? 1 : 0,
        ]);

        $_SESSION['_flash']['success'] = 'Keyword aggiornata';
        Router::redirect('/seo-tracking/project/' . $projectId . '/keywords/' . $keywordId);
    }

    /**
     * Elimina keyword
     */
    public function destroy(int $projectId, int $keywordId): string|null
    {
        $user = Auth::user();
        $keyword = $this->keyword->findByProject($keywordId, $projectId);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
                  strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded') !== false;

        if (!$keyword) {
            if ($isAjax) {
                return View::json(['success' => false, 'error' => 'Keyword non trovata'], 404);
            }
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            Router::redirect('/seo-tracking');
            return null;
        }

        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            if ($isAjax) {
                return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
            }
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return null;
        }

        $this->keyword->delete($keywordId);

        if ($isAjax) {
            return View::json(['success' => true, 'message' => 'Keyword eliminata']);
        }

        $_SESSION['_flash']['success'] = 'Keyword eliminata';
        Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
        return null;
    }

    /**
     * Toggle tracking status
     */
    public function toggleTracking(int $id): string
    {
        $user = Auth::user();
        $keyword = $this->keyword->find($id);

        if (!$keyword) {
            return View::json(['error' => 'Keyword non trovata'], 404);
        }

        $project = $this->project->find($keyword['project_id'], $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $track = $input['track'] ?? !$keyword['is_tracked'];

        $this->keyword->update($id, ['is_tracked' => $track ? 1 : 0]);

        return View::json(['success' => true, 'is_tracked' => $track]);
    }

    /**
     * Bulk actions
     */
    public function bulkAction(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $action = $_POST['action'] ?? '';
        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (empty($keywordIds)) {
            $_SESSION['_flash']['error'] = 'Seleziona almeno una keyword';
            Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
            return;
        }

        $count = 0;

        switch ($action) {
            case 'track':
                foreach ($keywordIds as $id) {
                    $this->keyword->update((int) $id, ['is_tracked' => 1]);
                    $count++;
                }
                $_SESSION['_flash']['success'] = "{$count} keyword aggiunte al tracking";
                break;

            case 'untrack':
                foreach ($keywordIds as $id) {
                    $this->keyword->update((int) $id, ['is_tracked' => 0]);
                    $count++;
                }
                $_SESSION['_flash']['success'] = "{$count} keyword rimosse dal tracking";
                break;

            case 'delete':
                foreach ($keywordIds as $id) {
                    $this->keyword->delete((int) $id);
                    $count++;
                }
                $_SESSION['_flash']['success'] = "{$count} keyword eliminate";
                break;

            case 'group':
                $groupName = trim($_POST['group_name'] ?? '');
                foreach ($keywordIds as $id) {
                    $this->keyword->update((int) $id, ['group_name' => $groupName ?: null]);
                    $count++;
                }
                $_SESSION['_flash']['success'] = "{$count} keyword spostate nel gruppo";
                break;

            default:
                $_SESSION['_flash']['error'] = 'Azione non valida';
        }

        Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
    }

    /**
     * API: Dati grafico posizioni keyword
     */
    public function positionChart(int $id): string
    {
        $user = Auth::user();
        $keyword = $this->keyword->find($id);

        if (!$keyword) {
            return View::json(['error' => 'Keyword non trovata'], 404);
        }

        $project = $this->project->find($keyword['project_id'], $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 403);
        }

        $days = (int) ($_GET['days'] ?? 90);
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $positions = $this->keywordPosition->getByKeyword($id, $startDate, $endDate);

        $labels = [];
        $data = [];

        foreach ($positions as $row) {
            $labels[] = date('d/m', strtotime($row['date']));
            $data[] = round((float) $row['avg_position'], 1);
        }

        return View::json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Posizione',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                ],
            ],
        ]);
    }

    /**
     * Import keywords da CSV
     */
    public function import(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['_flash']['error'] = 'Errore nel caricamento del file';
            Router::redirect('/seo-tracking/project/' . $projectId . '/keywords/add');
            return;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);

        $added = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            $keyword = trim($row[0] ?? '');

            if (empty($keyword)) continue;

            $existing = $this->keyword->findByKeyword($projectId, $keyword);

            if ($existing) {
                $skipped++;
                continue;
            }

            $this->keyword->create([
                'project_id' => $projectId,
                'keyword' => $keyword,
                'group_name' => trim($row[1] ?? '') ?: null,
                'target_url' => trim($row[2] ?? '') ?: null,
                'is_tracked' => 1,
                'source' => 'csv_import',
            ]);

            $added++;
        }

        fclose($file);

        $_SESSION['_flash']['success'] = "Importate {$added} keyword" . ($skipped > 0 ? " ({$skipped} duplicate)" : '');
        Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
    }

    /**
     * Form aggiunta keyword (alias per compatibilità route)
     */
    public function add(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $groups = $this->keyword->getGroups($projectId);
        $locations = $this->location->all();

        return View::render('seo-tracking/keywords/create', [
            'title' => 'Aggiungi Keyword - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'groups' => $groups,
            'locations' => $locations,
        ]);
    }

    /**
     * Lista tutte le keyword GSC (non solo tracciate)
     */
    public function all(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $keywords = $this->gscData->getUniqueQueriesByProject($projectId);

        return View::render('seo-tracking/keywords/all', [
            'title' => 'Tutte le Query GSC - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keywords' => $keywords,
        ]);
    }

    /**
     * Dettaglio keyword con storico posizioni
     */
    public function detail(int $projectId, int $keywordId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $keyword = $this->keyword->find($keywordId);

        if (!$keyword || (int)$keyword['project_id'] !== $projectId) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            Router::redirect('/seo-tracking/project/' . $projectId . '/keywords');
            exit;
        }

        // Storico posizioni (90 giorni)
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $positions = $this->keywordPosition->getByKeyword($keywordId, $startDate, $endDate);

        // Dati GSC recenti
        $gscData = $this->gscData->getKeywordPositions($projectId, $keyword['keyword'], $startDate, $endDate);

        return View::render('seo-tracking/keywords/show', [
            'title' => $keyword['keyword'] . ' - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keyword' => $keyword,
            'positions' => $positions,
            'gscData' => $gscData,
            'dateRange' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * Aggiorna volumi di ricerca (AJAX)
     */
    public function updateVolumes(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        $result = $this->keyword->updateSearchVolumes($projectId);

        return View::json($result);
    }

    /**
     * Verifica configurazione DataForSEO
     */
    public function checkVolumeService(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        $dataForSeo = new \Services\DataForSeoService();

        return View::json([
            'configured' => $dataForSeo->isConfigured(),
        ]);
    }
}
