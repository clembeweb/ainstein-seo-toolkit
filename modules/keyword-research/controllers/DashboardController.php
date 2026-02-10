<?php

namespace Modules\KeywordResearch\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\KeywordResearch\Models\Project;
use Modules\KeywordResearch\Models\Research;
use Modules\KeywordResearch\Models\Cluster;

class DashboardController
{
    private Project $projectModel;

    public function __construct()
    {
        $this->projectModel = new Project();
    }

    public function index(): string
    {
        $user = Auth::user();

        $recentProjects = $this->projectModel->getRecentByUser($user['id'], 5);

        $stats = [
            'total_projects' => $this->projectModel->countByUser($user['id']),
            'total_researches' => Research::countByUser($user['id']),
            'total_clusters' => Cluster::countByUser($user['id']),
        ];

        return View::render('keyword-research::dashboard', [
            'title' => 'Keyword Research',
            'user' => $user,
            'recentProjects' => $recentProjects,
            'stats' => $stats,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }
}
