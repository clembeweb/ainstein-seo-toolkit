<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Core\Credits;
use Core\Database;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\KeywordPosition;
use Modules\SeoTracking\Models\KeywordGroup;
use Modules\SeoTracking\Models\GscData;
use Modules\SeoTracking\Models\Location;
use Modules\SeoTracking\Models\RankCheck;
use Modules\SeoTracking\Models\RankJob;
use Modules\SeoTracking\Models\RankQueue;
use Modules\SeoTracking\Services\RankCheckerService;

/**
 * KeywordController
 * Gestisce CRUD e analisi keyword
 */
class KeywordController
{
    private Project $project;
    private Keyword $keyword;
    private KeywordPosition $keywordPosition;
    private KeywordGroup $keywordGroup;
    private GscData $gscData;
    private Location $location;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->keywordPosition = new KeywordPosition();
        $this->keywordGroup = new KeywordGroup();
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

        // Check for active rank check job
        $rankJob = new RankJob();
        $activeJob = $rankJob->getActiveForProject($projectId);

        return View::render('seo-tracking/keywords/index', [
            'title' => $project['name'] . ' - Keywords',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keywords' => $keywords,
            'groups' => $groups,
            'stats' => $stats,
            'filters' => $filters,
            'userCredits' => Credits::getBalance($user['id']),
            'activeJob' => $activeJob,
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
        $autoFetch = isset($_POST['auto_fetch']) ? 1 : 0;

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
        $keywordIds = []; // Raccoglie gli ID delle keyword inserite

        foreach ($lines as $keyword) {
            if (empty($keyword)) continue;

            // Verifica duplicato
            $existing = $this->keyword->findByKeyword($projectId, $keyword);

            if ($existing) {
                $skipped++;
                continue;
            }

            $newId = $this->keyword->create([
                'project_id' => $projectId,
                'keyword' => $keyword,
                'location_code' => $locationCode,
                'group_name' => $groupName ?: null,
                'is_tracked' => $isTracked,
                'source' => 'manual',
            ]);

            if ($newId) {
                $keywordIds[] = $newId;
                // Sincronizza keyword con gruppo in st_keyword_groups
                if (!empty($groupName)) {
                    $this->keywordGroup->syncKeywordToGroup($projectId, $newId, $groupName);
                }
            }
            $added++;
        }

        // Auto-fetch dati keyword se richiesto
        $fetchedMsg = '';
        if ($autoFetch && $added > 0 && !empty($keywordIds)) {
            // Verifica DataForSEO configurato
            $dataForSeo = new \Services\DataForSeoService();

            if ($dataForSeo->isConfigured()) {
                // Calcola costo (0.5 crediti/kw, 0.3 se 10+)
                $cost = $added >= 10 ? $added * 0.3 : $added * 0.5;

                if (Credits::hasEnough($user['id'], $cost)) {
                    // Aggiorna volumi solo per le keyword appena inserite
                    $result = $this->keyword->updateSearchVolumesForIds($keywordIds);

                    Database::reconnect();
                    Credits::consume($user['id'], $cost, 'auto_fetch_volumes', 'seo-tracking', [
                        'project_id' => $projectId,
                        'keywords' => $added,
                        'updated' => $result['updated'] ?? 0,
                    ]);

                    $updatedCount = $result['updated'] ?? 0;
                    $fetchedMsg = " Dati recuperati per {$updatedCount} keyword ({$cost} crediti).";
                } else {
                    $fetchedMsg = " (crediti insufficienti per recupero dati automatico)";
                }
            } else {
                $fetchedMsg = " (DataForSEO non configurato)";
            }
        }

        if ($added > 0) {
            $_SESSION['_flash']['success'] = "Aggiunte {$added} keyword" . ($skipped > 0 ? " ({$skipped} duplicate ignorate)" : '') . $fetchedMsg;
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

        // Sincronizza keyword con gruppo in st_keyword_groups
        $this->keywordGroup->syncKeywordToGroup($projectId, $keywordId, $groupName ?: null);

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
                    // Sincronizza keyword con gruppo in st_keyword_groups
                    $this->keywordGroup->syncKeywordToGroup($projectId, (int) $id, $groupName ?: null);
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

            $groupName = trim($row[1] ?? '') ?: null;

            $newId = $this->keyword->create([
                'project_id' => $projectId,
                'keyword' => $keyword,
                'group_name' => $groupName,
                'target_url' => trim($row[2] ?? '') ?: null,
                'is_tracked' => 1,
                'source' => 'csv_import',
            ]);

            // Sincronizza keyword con gruppo in st_keyword_groups
            if ($newId && !empty($groupName)) {
                $this->keywordGroup->syncKeywordToGroup($projectId, $newId, $groupName);
            }

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
     * Verifica configurazione provider volumi (DataForSEO o Keywords Everywhere)
     */
    public function checkVolumeService(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        $dataForSeo = new \Services\DataForSeoService();
        $kwEverywhere = new \Services\KeywordsEverywhereService();

        $dataForSeoConfigured = $dataForSeo->isConfigured();
        $kwEverywhereConfigured = $kwEverywhere->isConfigured();

        return View::json([
            'configured' => $dataForSeoConfigured || $kwEverywhereConfigured,
            'providers' => [
                'dataforseo' => $dataForSeoConfigured,
                'keywordseverywhere' => $kwEverywhereConfigured,
            ],
            'active_provider' => $dataForSeoConfigured ? 'DataForSEO' : ($kwEverywhereConfigured ? 'Keywords Everywhere' : null),
        ]);
    }

    // =============================================
    // REFRESH DATI KEYWORD (con crediti)
    // =============================================

    /**
     * Calcola costo crediti per refresh
     */
    private function calculateRefreshCost(int $keywordCount, string $type = 'volumes'): float
    {
        if ($type === 'volumes') {
            // 0.5 crediti/kw singolo, 0.3 crediti/kw per bulk (10+)
            return $keywordCount >= 10 ? $keywordCount * 0.3 : $keywordCount * 0.5;
        } elseif ($type === 'positions') {
            // 1 credito/kw
            return $keywordCount * 1.0;
        } elseif ($type === 'all') {
            // Somma di volumi bulk + posizioni
            $volumeCost = $keywordCount >= 10 ? $keywordCount * 0.3 : $keywordCount * 0.5;
            $positionCost = $keywordCount * 1.0;
            return $volumeCost + $positionCost;
        }
        return 0;
    }

    /**
     * API: Calcola costo refresh (preview per UI)
     */
    public function getRefreshCost(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        // Conta keyword tracciate
        $keywords = $this->keyword->allByProject($projectId, ['is_tracked' => 1]);
        $count = count($keywords);
        $allCount = $this->keyword->countByProject($projectId);

        // Verifica servizi configurati
        $dataForSeo = new \Services\DataForSeoService();
        $rankChecker = new RankCheckerService();

        return View::json([
            'success' => true,
            'keyword_count' => $count,
            'all_keyword_count' => $allCount,
            'costs' => [
                'volumes' => [
                    'per_keyword' => $allCount >= 10 ? 0.3 : 0.5,
                    'total' => $this->calculateRefreshCost($allCount, 'volumes'),
                    'configured' => $dataForSeo->isConfigured(),
                ],
                'positions' => [
                    'per_keyword' => 1.0,
                    'total' => $this->calculateRefreshCost($count, 'positions'),
                    'configured' => $rankChecker->isConfigured(),
                ],
                'all' => [
                    'total' => $this->calculateRefreshCost($count, 'all'),
                ],
            ],
            'user_balance' => Credits::getBalance($user['id']),
        ]);
    }

    /**
     * Refresh volumi di ricerca (DataForSEO) con consumo crediti
     * Supporta keyword_ids[] per aggiornare solo keyword specifiche
     */
    public function refreshVolumes(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        // Verifica che almeno un provider volumi sia configurato (cascade: RapidAPI -> DataForSEO -> KeywordsEverywhere)
        $rapidApi = new \Services\RapidApiKeywordService();
        $dataForSeo = new \Services\DataForSeoService();
        $kwEverywhere = new \Services\KeywordsEverywhereService();

        if (!$rapidApi->isConfigured() && !$dataForSeo->isConfigured() && !$kwEverywhere->isConfigured()) {
            return View::json(['success' => false, 'error' => 'Nessun provider volumi configurato. Vai in Admin > Impostazioni per configurare RapidAPI, DataForSEO o Keywords Everywhere']);
        }

        // Verifica se sono stati passati ID specifici
        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (!empty($keywordIds)) {
            // Usa solo le keyword selezionate
            $keywordIds = array_map('intval', $keywordIds);
            $keywordCount = count($keywordIds);

            // Calcola costo
            $cost = $this->calculateRefreshCost($keywordCount, 'volumes');

            // Verifica crediti
            if (!Credits::hasEnough($user['id'], $cost)) {
                return View::json([
                    'success' => false,
                    'error' => "Crediti insufficienti. Richiesti: {$cost}, disponibili: " . Credits::getBalance($user['id'])
                ]);
            }

            // Esegui refresh per ID specifici
            $result = $this->keyword->updateSearchVolumesForIds($keywordIds);
        } else {
            // Conta keyword
            $keywordCount = $this->keyword->countByProject($projectId);
            if ($keywordCount === 0) {
                return View::json(['success' => false, 'error' => 'Nessuna keyword nel progetto']);
            }

            // Calcola costo
            $cost = $this->calculateRefreshCost($keywordCount, 'volumes');

            // Verifica crediti
            if (!Credits::hasEnough($user['id'], $cost)) {
                return View::json([
                    'success' => false,
                    'error' => "Crediti insufficienti. Richiesti: {$cost}, disponibili: " . Credits::getBalance($user['id'])
                ]);
            }

            // Esegui refresh completo
            $result = $this->keyword->updateSearchVolumes($projectId);
        }

        if (!$result['success']) {
            return View::json($result);
        }

        // Consuma crediti
        Database::reconnect();
        Credits::consume($user['id'], $cost, 'refresh_volumes', 'seo-tracking', [
            'project_id' => $projectId,
            'keywords' => $keywordCount,
            'updated' => $result['updated'] ?? 0,
        ]);

        $result['credits_used'] = $cost;
        $result['new_balance'] = Credits::getBalance($user['id']);

        return View::json($result);
    }

    /**
     * Refresh posizioni SERP con consumo crediti
     * Supporta keyword_ids[] per aggiornare solo keyword specifiche
     */
    public function refreshPositions(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        // Verifica RankChecker configurato
        $rankChecker = new RankCheckerService();
        if (!$rankChecker->isConfigured()) {
            return View::json(['success' => false, 'error' => 'Servizio SERP non configurato. Vai in Admin > Impostazioni']);
        }

        // Verifica se sono stati passati ID specifici
        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (!empty($keywordIds)) {
            // Usa solo le keyword selezionate
            $keywords = [];
            foreach ($keywordIds as $id) {
                $kw = $this->keyword->findByProject((int)$id, $projectId);
                if ($kw) {
                    $keywords[] = $kw;
                }
            }
        } else {
            // Altrimenti prendi tutte le tracciate
            $keywords = $this->keyword->allByProject($projectId, ['is_tracked' => 1]);
        }

        $keywordCount = count($keywords);

        if ($keywordCount === 0) {
            return View::json(['success' => false, 'error' => 'Nessuna keyword da aggiornare']);
        }

        // Calcola costo
        $cost = $this->calculateRefreshCost($keywordCount, 'positions');

        // Verifica crediti
        if (!Credits::hasEnough($user['id'], $cost)) {
            return View::json([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$cost}, disponibili: " . Credits::getBalance($user['id'])
            ]);
        }

        // Estrai dominio target dal progetto
        $targetDomain = $project['domain'] ?? '';
        if (empty($targetDomain)) {
            return View::json(['success' => false, 'error' => 'URL sito web non configurato nel progetto']);
        }

        // Esegui check posizioni
        $updated = 0;
        $notFound = 0;
        $errors = [];
        $details = []; // Dettagli per ogni keyword
        $rankCheckModel = new RankCheck();

        foreach ($keywords as $kw) {
            try {
                $result = $rankChecker->checkPosition($kw['keyword'], $targetDomain, [
                    'location_code' => $kw['location_code'] ?? 'IT',
                    'device' => 'desktop'
                ]);

                // Salva nel log rank_checks
                $rankCheckModel->create([
                    'project_id' => $projectId,
                    'user_id' => $user['id'],
                    'keyword' => $kw['keyword'],
                    'target_domain' => $targetDomain,
                    'location' => $kw['location_code'] ?? 'IT',
                    'device' => 'desktop',
                    'serp_position' => $result['found'] ? $result['position'] : null,
                    'serp_url' => $result['url'] ?? null,
                    'serp_title' => $result['title'] ?? null,
                    'gsc_position' => $kw['last_position'],
                    'position_diff' => $result['found'] && $kw['last_position']
                        ? ($result['position'] - $kw['last_position'])
                        : null,
                    'credits_used' => 1,
                ]);

                // Salva dettaglio per risposta
                $details[] = [
                    'keyword' => $kw['keyword'],
                    'found' => $result['found'],
                    'position' => $result['found'] ? $result['position'] : null,
                    'url' => $result['url'] ?? null,
                    'previous_position' => $kw['last_position'],
                ];

                if ($result['found']) {
                    // Aggiorna cache posizione nella keyword
                    $this->keyword->update($kw['id'], [
                        'last_position' => $result['position'],
                        'last_updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Upsert snapshot giornaliero per storico posizioni
                    $this->keywordPosition->upsert([
                        'project_id' => $projectId,
                        'keyword_id' => $kw['id'],
                        'date' => date('Y-m-d'),
                        'avg_position' => $result['position'],
                    ]);

                    $updated++;
                } else {
                    // Keyword non trovata: imposta posizione a NULL per indicare "non in SERP"
                    $this->keyword->update($kw['id'], [
                        'last_position' => null,
                        'last_updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notFound++;
                }

                // Rate limiting: 0.5 secondi tra richieste
                usleep(500000);

            } catch (\Exception $e) {
                $errors[] = $kw['keyword'] . ': ' . $e->getMessage();
                $details[] = [
                    'keyword' => $kw['keyword'],
                    'found' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Consuma crediti
        Database::reconnect();
        Credits::consume($user['id'], $cost, 'refresh_positions', 'seo-tracking', [
            'project_id' => $projectId,
            'keywords' => $keywordCount,
            'updated' => $updated,
            'not_found' => $notFound,
        ]);

        return View::json([
            'success' => true,
            'updated' => $updated,
            'not_found' => $notFound,
            'total' => $keywordCount,
            'credits_used' => $cost,
            'new_balance' => Credits::getBalance($user['id']),
            'message' => "Posizioni aggiornate: {$updated} trovate" . ($notFound > 0 ? ", {$notFound} non in top 100" : ''),
            'details' => $details,
            'errors' => $errors,
        ]);
    }

    /**
     * Refresh completo (volumi + posizioni) con consumo crediti
     * Supporta keyword_ids[] per aggiornare solo keyword specifiche
     */
    public function refreshAll(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        // Verifica servizi configurati
        $dataForSeo = new \Services\DataForSeoService();
        $rankChecker = new RankCheckerService();

        if (!$dataForSeo->isConfigured() && !$rankChecker->isConfigured()) {
            return View::json(['success' => false, 'error' => 'Nessun servizio configurato. Vai in Admin > Impostazioni']);
        }

        // Verifica se sono stati passati ID specifici
        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (!empty($keywordIds)) {
            // Usa solo le keyword selezionate
            $allKeywords = [];
            foreach ($keywordIds as $id) {
                $kw = $this->keyword->findByProject((int)$id, $projectId);
                if ($kw) {
                    $allKeywords[] = $kw;
                }
            }
            // Per posizioni usiamo tutte le selezionate (non solo tracciate)
            $trackedKeywords = $allKeywords;
        } else {
            // Conta keyword
            $allKeywords = $this->keyword->allByProject($projectId);
            $trackedKeywords = array_filter($allKeywords, fn($kw) => $kw['is_tracked']);
        }

        $allCount = count($allKeywords);
        $trackedCount = count($trackedKeywords);

        if ($allCount === 0) {
            return View::json(['success' => false, 'error' => 'Nessuna keyword da aggiornare']);
        }

        // Calcola costo totale
        $volumeCost = $dataForSeo->isConfigured() ? $this->calculateRefreshCost($allCount, 'volumes') : 0;
        $positionCost = $rankChecker->isConfigured() && $trackedCount > 0
            ? $this->calculateRefreshCost($trackedCount, 'positions')
            : 0;
        $totalCost = $volumeCost + $positionCost;

        // Verifica crediti
        if (!Credits::hasEnough($user['id'], $totalCost)) {
            return View::json([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$totalCost}, disponibili: " . Credits::getBalance($user['id'])
            ]);
        }

        $results = [
            'volumes' => null,
            'positions' => null,
        ];
        $details = [];

        // 1. Refresh volumi
        if ($dataForSeo->isConfigured()) {
            if (!empty($keywordIds)) {
                $volumeResult = $this->keyword->updateSearchVolumesForIds(array_map('intval', $keywordIds));
            } else {
                $volumeResult = $this->keyword->updateSearchVolumes($projectId);
            }
            $results['volumes'] = [
                'updated' => $volumeResult['updated'] ?? 0,
                'total' => $allCount,
                'cost' => $volumeCost,
            ];
        }

        // 2. Refresh posizioni
        if ($rankChecker->isConfigured() && $trackedCount > 0) {
            $targetDomain = $project['domain'] ?? '';

            if (!empty($targetDomain)) {
                $updated = 0;
                $notFound = 0;
                $rankCheckModel = new RankCheck();

                foreach ($trackedKeywords as $kw) {
                    try {
                        $result = $rankChecker->checkPosition($kw['keyword'], $targetDomain, [
                            'location_code' => $kw['location_code'] ?? 'IT',
                            'device' => 'desktop'
                        ]);

                        $rankCheckModel->create([
                            'project_id' => $projectId,
                            'user_id' => $user['id'],
                            'keyword' => $kw['keyword'],
                            'target_domain' => $targetDomain,
                            'location' => $kw['location_code'] ?? 'IT',
                            'device' => 'desktop',
                            'serp_position' => $result['found'] ? $result['position'] : null,
                            'serp_url' => $result['url'] ?? null,
                            'gsc_position' => $kw['last_position'],
                            'position_diff' => $result['found'] && $kw['last_position']
                                ? ($result['position'] - $kw['last_position'])
                                : null,
                            'credits_used' => 1,
                        ]);

                        // Salva dettaglio per risposta
                        $details[] = [
                            'keyword' => $kw['keyword'],
                            'found' => $result['found'],
                            'position' => $result['found'] ? $result['position'] : null,
                            'url' => $result['url'] ?? null,
                            'previous_position' => $kw['last_position'],
                        ];

                        if ($result['found']) {
                            $this->keyword->update($kw['id'], [
                                'last_position' => $result['position'],
                                'last_updated_at' => date('Y-m-d H:i:s'),
                            ]);

                            // Upsert snapshot giornaliero per storico posizioni
                            $this->keywordPosition->upsert([
                                'project_id' => $projectId,
                                'keyword_id' => $kw['id'],
                                'date' => date('Y-m-d'),
                                'avg_position' => $result['position'],
                            ]);

                            $updated++;
                        } else {
                            $this->keyword->update($kw['id'], [
                                'last_position' => null,
                                'last_updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notFound++;
                        }

                        usleep(500000);
                    } catch (\Exception $e) {
                        $details[] = [
                            'keyword' => $kw['keyword'],
                            'found' => false,
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                $results['positions'] = [
                    'updated' => $updated,
                    'not_found' => $notFound,
                    'total' => $trackedCount,
                    'cost' => $positionCost,
                ];
            }
        }

        // Consuma crediti
        Database::reconnect();
        Credits::consume($user['id'], $totalCost, 'refresh_all', 'seo-tracking', [
            'project_id' => $projectId,
            'volumes' => $results['volumes'],
            'positions' => $results['positions'],
        ]);

        return View::json([
            'success' => true,
            'results' => $results,
            'credits_used' => $totalCost,
            'new_balance' => Credits::getBalance($user['id']),
            'message' => $this->buildRefreshMessage($results),
            'details' => $details,
        ]);
    }

    /**
     * Helper: costruisce messaggio risultato refresh
     */
    private function buildRefreshMessage(array $results): string
    {
        $parts = [];

        if ($results['volumes']) {
            $parts[] = "Volumi: {$results['volumes']['updated']}/{$results['volumes']['total']}";
        }

        if ($results['positions']) {
            $found = $results['positions']['updated'];
            $notFound = $results['positions']['not_found'];
            $parts[] = "Posizioni: {$found} trovate" . ($notFound > 0 ? ", {$notFound} non trovate" : '');
        }

        return implode(' | ', $parts);
    }

    // =========================================
    // BACKGROUND JOB PROCESSING PER POSIZIONI
    // =========================================

    private const POSITION_CREDIT_COST = 1;

    /**
     * Avvia un job di refresh posizioni in background
     * POST /seo-tracking/project/{id}/keywords/start-positions-job
     */
    public function startPositionsJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        // Input - keyword_ids[] opzionale, altrimenti tutte le tracciate
        $keywordIds = $_POST['keyword_ids'] ?? [];

        // Se passate come stringa comma-separated
        if (is_string($keywordIds)) {
            $keywordIds = array_filter(array_map('intval', explode(',', $keywordIds)));
        }

        // Se nessun ID specificato, prendi tutte le tracciate
        if (empty($keywordIds)) {
            $trackedKeywords = $this->keyword->allByProject($projectId, ['is_tracked' => 1]);
            $keywordIds = array_column($trackedKeywords, 'id');
        }

        if (empty($keywordIds)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna keyword da aggiornare']);
            exit;
        }

        // Verifica crediti
        $totalCost = count($keywordIds) * self::POSITION_CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Necessari: {$totalCost}, disponibili: " . Credits::getBalance($user['id'])
            ]);
            exit;
        }

        // Verifica che non ci sia già un job attivo per questo progetto
        $jobModel = new RankJob();

        // Prima, resetta eventuali job "stuck" (running da più di 1 ora)
        $jobModel->resetStuckJobs($projectId, 60);

        $activeJob = $jobModel->getActiveForProject($projectId);
        if ($activeJob) {
            // Verifica se il job è effettivamente stuck (running ma vecchio)
            $startedAt = strtotime($activeJob['started_at'] ?? $activeJob['created_at']);
            $minutesRunning = (time() - $startedAt) / 60;

            if ($minutesRunning > 30) {
                // Job stuck - marcalo come errore
                $jobModel->markError($activeJob['id'], 'Timeout - job rimasto in sospeso per troppo tempo');
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Esiste già un job in esecuzione per questo progetto. Attendi il completamento o annullalo.',
                    'active_job_id' => $activeJob['id'],
                    'minutes_running' => round($minutesRunning, 1)
                ]);
                exit;
            }
        }

        // Verifica service configurato
        $service = new RankCheckerService();
        if (!$service->isConfigured()) {
            echo json_encode(['success' => false, 'error' => 'Nessun provider SERP configurato. Vai in Admin > Impostazioni.']);
            exit;
        }

        // Crea il job
        $jobId = $jobModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => RankJob::TYPE_MANUAL,
            'keywords_requested' => count($keywordIds),
        ]);

        // Aggiungi keywords alla coda
        $queueModel = new RankQueue();
        $device = $_POST['device'] ?? 'desktop';
        $inserted = $queueModel->addBulkForJob($projectId, $jobId, $keywordIds, $device);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'keywords_queued' => $inserted,
            'estimated_credits' => $inserted * self::POSITION_CREDIT_COST,
        ]);
        exit;
    }

    /**
     * SSE Stream per progress posizioni in tempo reale
     * GET /seo-tracking/project/{id}/keywords/positions-stream?job_id=X
     */
    public function processPositionsStream(int $projectId): void
    {
        // Verifica auth manualmente per evitare redirect su SSE
        $user = Auth::user();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }

        $project = $this->project->find($projectId, $user['id']);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            exit('Progetto non trovato');
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            header('HTTP/1.1 400 Bad Request');
            exit('job_id richiesto');
        }

        $jobModel = new RankJob();
        $job = $jobModel->findByUser($jobId, $user['id']);
        if (!$job || $job['project_id'] != $projectId) {
            header('HTTP/1.1 404 Not Found');
            exit('Job non trovato');
        }

        // Setup SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Chiudi la sessione PRIMA del loop per non bloccare altre richieste
        session_write_close();

        // Funzione helper per inviare eventi SSE
        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data) . "\n\n";
            ob_flush();
            flush();
        };

        // Avvia il job se pending
        if ($job['status'] === RankJob::STATUS_PENDING) {
            $jobModel->start($jobId);
            $sendEvent('started', [
                'job_id' => $jobId,
                'total_keywords' => $job['keywords_requested'],
            ]);
        }

        // Setup services
        $service = new RankCheckerService();
        $queueModel = new RankQueue();
        $rankCheckModel = new RankCheck();

        $completed = 0;
        $failed = 0;
        $found = 0;
        $creditsUsed = 0;
        $lastHeartbeat = time();
        $heartbeatInterval = 10; // Invia heartbeat ogni 10 secondi

        // Loop di elaborazione
        while (true) {
            // Invia heartbeat periodico per mantenere la connessione
            if (time() - $lastHeartbeat >= $heartbeatInterval) {
                $sendEvent('heartbeat', ['time' => time()]);
                $lastHeartbeat = time();
            }
            // Riconnetti DB per evitare timeout
            Database::reconnect();

            // Verifica cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'message' => 'Job annullato dall\'utente',
                ]);
                break;
            }

            // Ottieni prossimo item dalla coda
            $item = $queueModel->getNextPendingForJob($jobId);

            if (!$item) {
                // Coda vuota - job completato
                $jobModel->complete($jobId);
                $sendEvent('completed', [
                    'job_id' => $jobId,
                    'total_completed' => $completed,
                    'total_failed' => $failed,
                    'total_found' => $found,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            // Marca come in elaborazione
            $queueModel->updateStatus($item['id'], 'processing');

            // Aggiorna job con keyword corrente
            $jobModel->updateProgress($jobId, [
                'current_keyword_id' => $item['keyword_id'],
                'current_keyword' => $item['keyword'],
            ]);

            // Invia evento progress
            $sendEvent('progress', [
                'job_id' => $jobId,
                'current_keyword' => $item['keyword'],
                'completed' => $completed,
                'total' => $job['keywords_requested'],
                'percent' => round(($completed / $job['keywords_requested']) * 100),
            ]);

            try {
                // Esegui check SERP
                $result = $service->checkPosition($item['keyword'], $item['target_domain'], [
                    'location_code' => $item['location_code'] ?? 'IT',
                    'device' => $item['device'] ?? 'desktop',
                ]);

                Database::reconnect();

                // Recupera posizione GSC per confronto
                $gscPosition = $this->getGscPosition($projectId, $item['keyword']);
                $positionDiff = null;
                if ($result['found'] && $gscPosition !== null) {
                    $positionDiff = $result['position'] - $gscPosition;
                }

                // Salva in st_rank_checks
                $checkId = $rankCheckModel->create([
                    'project_id' => $projectId,
                    'user_id' => $user['id'],
                    'keyword' => $item['keyword'],
                    'target_domain' => $item['target_domain'],
                    'location' => $result['location'] ?? $item['location_code'],
                    'language' => $result['language'] ?? 'it',
                    'device' => $item['device'],
                    'serp_position' => $result['found'] ? $result['position'] : null,
                    'serp_url' => $result['url'],
                    'serp_title' => $result['title'],
                    'serp_snippet' => $result['snippet'],
                    'gsc_position' => $gscPosition,
                    'position_diff' => $positionDiff,
                    'total_organic_results' => $result['total_organic_results'] ?? null,
                    'credits_used' => self::POSITION_CREDIT_COST,
                ]);

                // Aggiorna last_position nella tabella keywords
                if ($item['keyword_id']) {
                    $updateData = [
                        'last_updated_at' => date('Y-m-d H:i:s'),
                    ];
                    if ($result['found'] && $result['position'] !== null) {
                        $updateData['last_position'] = $result['position'];

                        // Upsert snapshot giornaliero per storico posizioni
                        $this->keywordPosition->upsert([
                            'project_id' => $projectId,
                            'keyword_id' => $item['keyword_id'],
                            'date' => date('Y-m-d'),
                            'avg_position' => $result['position'],
                        ]);
                    } else {
                        $updateData['last_position'] = null;
                    }
                    $this->keyword->update($item['keyword_id'], $updateData);
                }

                // Marca coda come completata
                $queueModel->markCompleted($item['id'], $result['position'], $result['url'], $checkId);

                // Scala crediti
                Credits::consume($user['id'], self::POSITION_CREDIT_COST, 'refresh_positions', 'seo-tracking', [
                    'keyword' => $item['keyword'],
                    'project_id' => $projectId,
                    'job_id' => $jobId,
                ]);

                $completed++;
                $creditsUsed += self::POSITION_CREDIT_COST;
                if ($result['found']) {
                    $found++;
                }

                // Aggiorna job
                $jobModel->incrementCompleted($jobId, $result['found']);
                $jobModel->addCreditsUsed($jobId, self::POSITION_CREDIT_COST);

                // Invia evento keyword completata
                $sendEvent('keyword_completed', [
                    'keyword' => $item['keyword'],
                    'found' => $result['found'],
                    'position' => $result['position'],
                    'url' => $result['url'],
                    'gsc_position' => $gscPosition,
                    'position_diff' => $positionDiff,
                    'provider' => $result['provider'] ?? $service->getLastProvider(),
                    'credits_remaining' => Credits::getBalance($user['id']),
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                // Marca come errore
                $queueModel->markError($item['id'], $e->getMessage());
                $jobModel->incrementFailed($jobId);
                $failed++;

                $sendEvent('keyword_error', [
                    'keyword' => $item['keyword'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Pausa tra le chiamate per rate limiting
            usleep(500000); // 500ms
        }

        exit;
    }

    /**
     * Polling fallback per status job posizioni
     * GET /seo-tracking/project/{id}/keywords/positions-job-status?job_id=X
     */
    public function positionsJobStatus(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            exit;
        }

        $jobModel = new RankJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
        exit;
    }

    /**
     * Annulla un job di refresh posizioni
     * POST /seo-tracking/project/{id}/keywords/cancel-positions-job
     */
    public function cancelPositionsJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            exit;
        }

        $jobModel = new RankJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            exit;
        }

        if (!in_array($job['status'], [RankJob::STATUS_PENDING, RankJob::STATUS_RUNNING])) {
            echo json_encode(['success' => false, 'error' => 'Il job non può essere annullato']);
            exit;
        }

        $cancelled = $jobModel->cancel($jobId);

        echo json_encode([
            'success' => $cancelled,
            'message' => $cancelled ? 'Job annullato' : 'Impossibile annullare il job',
        ]);
        exit;
    }

    /**
     * Ottieni posizione media GSC per una keyword
     */
    private function getGscPosition(int $projectId, string $keyword): ?float
    {
        $result = Database::fetch(
            "SELECT AVG(position) as avg_position
             FROM st_gsc_data
             WHERE project_id = ?
               AND query = ?
               AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND impressions > 0",
            [$projectId, $keyword]
        );

        if ($result && $result['avg_position'] !== null) {
            return round((float) $result['avg_position'], 2);
        }

        return null;
    }

    /**
     * API: Ottieni dati stagionalita per una keyword
     * GET /seo-tracking/project/{id}/keywords/{keywordId}/seasonality
     */
    public function getSeasonality(int $projectId, int $keywordId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['success' => false, 'error' => 'Progetto non trovato'], 404);
        }

        $keyword = $this->keyword->findByProject($keywordId, $projectId);

        if (!$keyword) {
            return View::json(['success' => false, 'error' => 'Keyword non trovata'], 404);
        }

        $locationCode = $keyword['location_code'] ?? 'IT';
        $data = $this->keyword->getSeasonalityData($keyword['keyword'], $locationCode);

        if (!$data || empty($data['monthly_searches'])) {
            return View::json([
                'success' => true,
                'has_data' => false,
                'message' => 'Dati stagionalita non disponibili. Aggiorna i volumi.'
            ]);
        }

        // Ordina per data (dal piu vecchio al piu recente)
        $monthlySearches = $data['monthly_searches'];
        usort($monthlySearches, function ($a, $b) {
            $dateA = strtotime(($a['month'] ?? 'January') . ' ' . ($a['year'] ?? date('Y')));
            $dateB = strtotime(($b['month'] ?? 'January') . ' ' . ($b['year'] ?? date('Y')));
            return $dateA - $dateB;
        });

        // Prendi ultimi 12 mesi
        $monthlySearches = array_slice($monthlySearches, -12);

        // Mappa nomi mesi inglese -> italiano abbreviato
        $monthNames = [
            'January' => 'Gen', 'February' => 'Feb', 'March' => 'Mar',
            'April' => 'Apr', 'May' => 'Mag', 'June' => 'Giu',
            'July' => 'Lug', 'August' => 'Ago', 'September' => 'Set',
            'October' => 'Ott', 'November' => 'Nov', 'December' => 'Dic'
        ];

        $labels = [];
        $volumes = [];

        foreach ($monthlySearches as $item) {
            $month = $item['month'] ?? '';
            $year = $item['year'] ?? date('Y');
            $monthLabel = $monthNames[$month] ?? substr($month, 0, 3);
            $labels[] = $monthLabel . ' ' . substr((string) $year, -2);
            $volumes[] = (int) ($item['search_volume'] ?? 0);
        }

        return View::json([
            'success' => true,
            'has_data' => true,
            'keyword' => $keyword['keyword'],
            'labels' => $labels,
            'data' => $volumes,
            'intent' => $data['keyword_intent'],
        ]);
    }
}
