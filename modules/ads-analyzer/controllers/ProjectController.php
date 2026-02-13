<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\Analysis;
use Modules\AdsAnalyzer\Models\NegativeKeyword;
use Modules\AdsAnalyzer\Services\ValidationService;

class ProjectController
{
    public function index(): string
    {
        $user = Auth::user();

        $projectsByType = Project::allGroupedByType($user['id']);
        $projects = $projectsByType['campaign'] ?? [];

        return View::render('ads-analyzer/projects/index', [
            'title' => 'Progetti - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
        ]);
    }

    public function create(): string
    {
        $user = Auth::user();
        $preselectedType = $_GET['type'] ?? null;

        return View::render('ads-analyzer/projects/create', [
            'title' => 'Nuovo Progetto - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'preselectedType' => $preselectedType,
        ]);
    }

    public function store(): void
    {
        $user = Auth::user();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'user_id' => $user['id'],
            'type' => 'campaign',
        ];

        // Valida
        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(', ', $errors);
            $_SESSION['old_input'] = $data;
            header('Location: ' . url('/ads-analyzer/projects/create'));
            exit;
        }

        // Crea progetto
        $projectId = Project::create($data);

        // Genera token API per Google Ads Script
        Project::generateToken($projectId);

        $_SESSION['flash_success'] = 'Progetto creato con successo';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/script"));
        exit;
    }

    public function show(int $id): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Type guard: solo negative-kw usa questa dashboard
        if (($project['type'] ?? 'negative-kw') === 'campaign') {
            header('Location: ' . url("/ads-analyzer/projects/{$id}/campaign-dashboard"));
            exit;
        }

        $adGroups = AdGroup::getWithStats($id);
        $termStats = SearchTerm::getStatsByProject($id);
        $selectedCount = NegativeKeyword::countSelectedByProject($id);
        $totalNegatives = NegativeKeyword::countByProject($id);

        // Analisi recenti (ultime 3)
        $recentAnalyses = Analysis::findByProjectId($id);
        $recentAnalyses = array_slice($recentAnalyses, 0, 3);
        $totalAnalyses = Analysis::countByProject($id);

        return View::render('ads-analyzer/projects/show', [
            'title' => $project['name'] . ' - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'adGroups' => $adGroups,
            'termStats' => $termStats,
            'selectedCount' => $selectedCount,
            'totalNegatives' => $totalNegatives,
            'recentAnalyses' => $recentAnalyses,
            'totalAnalyses' => $totalAnalyses,
            'currentPage' => 'dashboard',
        ]);
    }

    public function edit(int $id): string
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        return View::render('ads-analyzer/projects/edit', [
            'title' => 'Modifica ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'settings',
        ]);
    }

    public function update(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        // Valida
        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(', ', $errors);
            header('Location: ' . url("/ads-analyzer/projects/{$id}/edit"));
            exit;
        }

        Project::update($id, $data);

        $_SESSION['flash_success'] = 'Progetto aggiornato';
        header('Location: ' . url("/ads-analyzer/projects/{$id}"));
        exit;
    }

    public function destroy(int $id): void
    {
        $user = Auth::user();

        if (!Project::deleteByUser($user['id'], $id)) {
            $_SESSION['flash_error'] = 'Impossibile eliminare il progetto';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $_SESSION['flash_success'] = 'Progetto eliminato';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    public function duplicate(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $newProjectId = Project::create([
            'user_id' => $user['id'],
            'type' => 'campaign',
            'name' => $project['name'] . ' (copia)',
            'description' => $project['description'],
            'business_context' => $project['business_context'],
            'status' => 'draft'
        ]);

        // Genera token API
        Project::generateToken($newProjectId);

        $_SESSION['flash_success'] = 'Progetto duplicato';
        header('Location: ' . url("/ads-analyzer/projects/{$newProjectId}"));
        exit;
    }

    public function toggleArchive(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $newStatus = $project['status'] === 'archived' ? 'completed' : 'archived';
        Project::update($id, ['status' => $newStatus]);

        $message = $newStatus === 'archived' ? 'Progetto archiviato' : 'Progetto ripristinato';
        $_SESSION['flash_success'] = $message;
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }
}
