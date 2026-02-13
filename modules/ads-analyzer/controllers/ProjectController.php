<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Services\ValidationService;

class ProjectController
{
    public function index(): string
    {
        $user = Auth::user();

        $projectsByType = Project::allGroupedByType($user['id']);

        return View::render('ads-analyzer/projects/index', [
            'title' => 'Progetti - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'campaignProjects' => $projectsByType['campaign'] ?? [],
            'creatorProjects' => $projectsByType['campaign-creator'] ?? [],
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
        $type = $_POST['type'] ?? 'campaign';

        if ($type === 'campaign-creator') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'brief' => trim($_POST['brief'] ?? ''),
                'campaign_type_gads' => $_POST['campaign_type_gads'] ?? '',
                'landing_url' => trim($_POST['landing_url'] ?? ''),
                'user_id' => $user['id'],
                'type' => 'campaign-creator',
            ];

            $errors = ValidationService::validateCampaignCreator($data);

            if (!empty($errors)) {
                $_SESSION['flash_error'] = implode(', ', $errors);
                $_SESSION['old_input'] = $data;
                header('Location: ' . url('/ads-analyzer/projects/create?type=campaign-creator'));
                exit;
            }

            $projectId = Project::create($data);

            $_SESSION['flash_success'] = 'Progetto creato con successo';
            header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-creator"));
            exit;
        }

        // Default: campaign (analisi)
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'user_id' => $user['id'],
            'type' => 'campaign',
        ];

        $errors = ValidationService::validateProject($data);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(', ', $errors);
            $_SESSION['old_input'] = $data;
            header('Location: ' . url('/ads-analyzer/projects/create'));
            exit;
        }

        $projectId = Project::create($data);
        Project::generateToken($projectId);

        $_SESSION['flash_success'] = 'Progetto creato con successo';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/script"));
        exit;
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
