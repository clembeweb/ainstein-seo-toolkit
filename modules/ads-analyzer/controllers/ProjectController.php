<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\NegativeKeyword;
use Modules\AdsAnalyzer\Services\ValidationService;

class ProjectController
{
    public function index(): void
    {
        $user = Auth::user();

        $status = $_GET['status'] ?? null;
        $projects = Project::getAllByUser($user['id'], $status);
        $stats = Project::getStats($user['id']);

        View::render('ads-analyzer/projects/index', [
            'pageTitle' => 'Progetti - Google Ads Analyzer',
            'projects' => $projects,
            'stats' => $stats,
            'currentStatus' => $status
        ]);
    }

    public function create(): void
    {
        View::render('ads-analyzer/projects/create', [
            'pageTitle' => 'Nuovo Progetto - Google Ads Analyzer'
        ]);
    }

    public function store(): void
    {
        $user = Auth::user();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'user_id' => $user['id']
        ];

        // Valida
        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(', ', $errors);
            $_SESSION['old_input'] = $data;
            redirect('/ads-analyzer/projects/create');
        }

        // Crea progetto
        $projectId = Project::create($data);

        $_SESSION['flash_success'] = 'Progetto creato con successo';
        redirect("/ads-analyzer/projects/{$projectId}/upload");
    }

    public function show(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        $adGroups = AdGroup::getWithStats($id);
        $termStats = SearchTerm::getStatsByProject($id);
        $selectedCount = NegativeKeyword::countSelectedByProject($id);
        $totalNegatives = NegativeKeyword::countByProject($id);

        View::render('ads-analyzer/projects/show', [
            'pageTitle' => $project['name'] . ' - Google Ads Analyzer',
            'project' => $project,
            'adGroups' => $adGroups,
            'termStats' => $termStats,
            'selectedCount' => $selectedCount,
            'totalNegatives' => $totalNegatives
        ]);
    }

    public function edit(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        View::render('ads-analyzer/projects/edit', [
            'pageTitle' => 'Modifica ' . $project['name'],
            'project' => $project
        ]);
    }

    public function update(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        // Valida
        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(', ', $errors);
            redirect("/ads-analyzer/projects/{$id}/edit");
        }

        Project::update($id, $data);

        $_SESSION['flash_success'] = 'Progetto aggiornato';
        redirect("/ads-analyzer/projects/{$id}");
    }

    public function destroy(int $id): void
    {
        $user = Auth::user();

        if (!Project::deleteByUser($user['id'], $id)) {
            $_SESSION['flash_error'] = 'Impossibile eliminare il progetto';
            redirect('/ads-analyzer');
        }

        $_SESSION['flash_success'] = 'Progetto eliminato';
        redirect('/ads-analyzer');
    }

    public function duplicate(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        $newProjectId = Project::create([
            'user_id' => $user['id'],
            'name' => $project['name'] . ' (copia)',
            'description' => $project['description'],
            'business_context' => $project['business_context'],
            'status' => 'draft'
        ]);

        $_SESSION['flash_success'] = 'Progetto duplicato';
        redirect("/ads-analyzer/projects/{$newProjectId}");
    }

    public function toggleArchive(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            redirect('/ads-analyzer');
        }

        $newStatus = $project['status'] === 'archived' ? 'completed' : 'archived';
        Project::update($id, ['status' => $newStatus]);

        $message = $newStatus === 'archived' ? 'Progetto archiviato' : 'Progetto ripristinato';
        $_SESSION['flash_success'] = $message;
        redirect('/ads-analyzer');
    }
}
