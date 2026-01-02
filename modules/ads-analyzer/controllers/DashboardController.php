<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;

class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();

        $projects = Project::getAllByUser($user['id']);
        $stats = Project::getStats($user['id']);
        $recentProjects = Project::getRecent($user['id'], 5);

        View::render('ads-analyzer/dashboard/index', [
            'title' => 'Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
            'stats' => $stats,
            'recentProjects' => $recentProjects
        ]);
    }
}
