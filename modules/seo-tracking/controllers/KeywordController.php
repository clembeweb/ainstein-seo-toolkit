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
use Modules\SeoTracking\Models\GscData;
use Modules\SeoTracking\Models\Location;
use Modules\SeoTracking\Models\RankCheck;
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
            'userCredits' => Credits::getBalance($user['id']),
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

        // Verifica DataForSEO configurato
        $dataForSeo = new \Services\DataForSeoService();
        if (!$dataForSeo->isConfigured()) {
            return View::json(['success' => false, 'error' => 'DataForSEO non configurato. Vai in Admin > Impostazioni']);
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
            'message' => "Posizioni aggiornate: {$updated} trovate" . ($notFound > 0 ? ", {$notFound} non in top 50" : ''),
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
}
