<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\KeywordGroup;
use Modules\SeoTracking\Services\GroupStatsService;

/**
 * GroupController
 * Gestisce gruppi di keyword
 */
class GroupController
{
    private Project $project;
    private Keyword $keyword;
    private KeywordGroup $group;
    private GroupStatsService $statsService;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->group = new KeywordGroup();
        $this->statsService = new GroupStatsService();
    }

    /**
     * Lista gruppi progetto
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

        $groups = $this->group->allWithStats($projectId);
        $comparison = $this->statsService->compareGroups($projectId);

        return View::render('seo-tracking/groups/index', [
            'title' => 'Gruppi Keyword - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'groups' => $groups,
            'comparison' => $comparison,
            'colors' => KeywordGroup::getDefaultColors(),
        ]);
    }

    /**
     * Form creazione gruppo
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

        $keywords = $this->keyword->allByProject($projectId);

        return View::render('seo-tracking/groups/create', [
            'title' => 'Nuovo Gruppo - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keywords' => $keywords,
            'colors' => KeywordGroup::getDefaultColors(),
        ]);
    }

    /**
     * Salva nuovo gruppo
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

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#006e96';
        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (empty($name)) {
            $_SESSION['_flash']['error'] = 'Il nome del gruppo è obbligatorio';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups/create');
            return;
        }

        // Verifica nome duplicato
        $existing = $this->group->findByName($projectId, $name);
        if ($existing) {
            $_SESSION['_flash']['error'] = 'Esiste già un gruppo con questo nome';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups/create');
            return;
        }

        // Crea gruppo
        $groupId = $this->group->create([
            'project_id' => $projectId,
            'name' => $name,
            'description' => $description ?: null,
            'color' => $color,
        ]);

        // Aggiungi keyword
        if (!empty($keywordIds)) {
            $this->group->addKeywords($groupId, $keywordIds);
        }

        $_SESSION['_flash']['success'] = 'Gruppo creato con successo';
        Router::redirect('/seo-tracking/project/' . $projectId . '/groups/' . $groupId);
    }

    /**
     * Dettaglio gruppo
     */
    public function show(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            $_SESSION['_flash']['error'] = 'Gruppo non trovato';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups');
            exit;
        }

        $keywords = $this->group->getKeywordsWithMetrics($groupId, 30);
        $stats = $this->statsService->getDashboardStats($groupId);
        $trend = $this->statsService->getPositionTrend($groupId, 30);
        $topPerformers = $this->statsService->getTopPerformers($groupId);
        $topMovers = $this->statsService->getTopMovers($groupId);
        $historical = $this->statsService->getHistoricalComparison($groupId, 7);

        return View::render('seo-tracking/groups/show', [
            'title' => $group['name'] . ' - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'group' => $group,
            'keywords' => $keywords,
            'stats' => $stats,
            'trend' => $trend,
            'topPerformers' => $topPerformers,
            'topMovers' => $topMovers,
            'historical' => $historical,
        ]);
    }

    /**
     * Form modifica gruppo
     */
    public function edit(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            $_SESSION['_flash']['error'] = 'Gruppo non trovato';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups');
            exit;
        }

        $allKeywords = $this->keyword->allByProject($projectId);
        $groupKeywordIds = array_column($this->group->getKeywords($groupId), 'id');

        return View::render('seo-tracking/groups/edit', [
            'title' => 'Modifica ' . $group['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'group' => $group,
            'keywords' => $allKeywords,
            'groupKeywordIds' => $groupKeywordIds,
            'colors' => KeywordGroup::getDefaultColors(),
        ]);
    }

    /**
     * Aggiorna gruppo
     */
    public function update(int $projectId, int $groupId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            $_SESSION['_flash']['error'] = 'Gruppo non trovato';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? $group['color'];
        $keywordIds = $_POST['keyword_ids'] ?? [];

        if (empty($name)) {
            $_SESSION['_flash']['error'] = 'Il nome del gruppo è obbligatorio';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups/' . $groupId . '/edit');
            return;
        }

        // Verifica nome duplicato (escludendo il gruppo corrente)
        $existing = $this->group->findByName($projectId, $name);
        if ($existing && (int)$existing['id'] !== $groupId) {
            $_SESSION['_flash']['error'] = 'Esiste già un gruppo con questo nome';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups/' . $groupId . '/edit');
            return;
        }

        // Aggiorna gruppo
        $this->group->update($groupId, [
            'name' => $name,
            'description' => $description ?: null,
            'color' => $color,
        ]);

        // Sync keyword
        $this->group->syncKeywords($groupId, $keywordIds);

        $_SESSION['_flash']['success'] = 'Gruppo aggiornato con successo';
        Router::redirect('/seo-tracking/project/' . $projectId . '/groups/' . $groupId);
    }

    /**
     * Elimina gruppo
     */
    public function destroy(int $projectId, int $groupId): void
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            $_SESSION['_flash']['error'] = 'Gruppo non trovato';
            Router::redirect('/seo-tracking/project/' . $projectId . '/groups');
            return;
        }

        $this->group->delete($groupId);

        $_SESSION['_flash']['success'] = 'Gruppo eliminato';
        Router::redirect('/seo-tracking/project/' . $projectId . '/groups');
    }

    /**
     * Aggiungi keyword al gruppo (AJAX)
     */
    public function addKeyword(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            return View::json(['error' => 'Gruppo non trovato'], 404);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $keywordId = (int)($input['keyword_id'] ?? 0);

        if (!$keywordId) {
            return View::json(['error' => 'ID keyword mancante'], 400);
        }

        $keyword = $this->keyword->findByProject($keywordId, $projectId);

        if (!$keyword) {
            return View::json(['error' => 'Keyword non trovata'], 404);
        }

        $this->group->addKeyword($groupId, $keywordId);

        return View::json([
            'success' => true,
            'message' => 'Keyword aggiunta al gruppo',
            'keyword_count' => $this->group->countKeywords($groupId),
        ]);
    }

    /**
     * Rimuovi keyword dal gruppo (AJAX)
     */
    public function removeKeyword(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            return View::json(['error' => 'Gruppo non trovato'], 404);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $keywordId = (int)($input['keyword_id'] ?? 0);

        if (!$keywordId) {
            return View::json(['error' => 'ID keyword mancante'], 400);
        }

        $this->group->removeKeyword($groupId, $keywordId);

        return View::json([
            'success' => true,
            'message' => 'Keyword rimossa dal gruppo',
            'keyword_count' => $this->group->countKeywords($groupId),
        ]);
    }

    /**
     * API: Dati grafico trend gruppo
     */
    public function trendChart(int $projectId, int $groupId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $group = $this->group->findByProject($groupId, $projectId);

        if (!$group) {
            return View::json(['error' => 'Gruppo non trovato'], 404);
        }

        $days = (int)($_GET['days'] ?? 30);
        $timeSeries = $this->statsService->getTrafficTimeSeries($groupId, $days);

        $labels = [];
        $clicks = [];
        $impressions = [];
        $positions = [];

        foreach ($timeSeries as $row) {
            $labels[] = date('d/m', strtotime($row['date']));
            $clicks[] = (int)$row['clicks'];
            $impressions[] = (int)$row['impressions'];
            $positions[] = round((float)$row['avg_position'], 1);
        }

        return View::json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Click',
                    'data' => $clicks,
                    'borderColor' => '#22c55e',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Impressioni',
                    'data' => $impressions,
                    'borderColor' => '#3b82f6',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Posizione media',
                    'data' => $positions,
                    'borderColor' => '#f97316',
                    'yAxisID' => 'y1',
                ],
            ],
        ]);
    }

    /**
     * Sincronizza gruppi da st_keywords.group_name a st_keyword_groups
     * Utility per migrare dati esistenti
     */
    public function syncFromKeywords(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $stats = $this->group->syncAllFromKeywords($projectId);

        return View::json([
            'success' => true,
            'message' => "Sincronizzazione completata: {$stats['groups_created']} gruppi, {$stats['keywords_linked']} keyword collegate",
            'stats' => $stats,
        ]);
    }
}
