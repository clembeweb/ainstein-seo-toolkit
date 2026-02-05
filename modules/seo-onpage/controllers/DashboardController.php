<?php

namespace Modules\SeoOnpage\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoOnpage\Models\Project;
use Modules\SeoOnpage\Models\Page;
use Modules\SeoOnpage\Models\Issue;
use Modules\SeoOnpage\Models\Job;

/**
 * DashboardController
 * Overview del progetto SEO Onpage
 */
class DashboardController
{
    private Project $project;
    private Page $page;
    private Issue $issue;
    private Job $job;

    public function __construct()
    {
        $this->project = new Project();
        $this->page = new Page();
        $this->issue = new Issue();
        $this->job = new Job();
    }

    /**
     * Dashboard principale progetto
     */
    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        // Pagine problematiche (score basso)
        $problematicPages = $this->page->getProblematic($id, 5);

        // Issues raggruppati per categoria
        $issuesByCategory = $this->issue->getGroupedByCategory($id);

        // Issues piu comuni
        $commonIssues = $this->issue->getMostCommon($id, 5);

        // Job attivo
        $activeJob = $this->job->getActiveForProject($id);

        // Job recenti
        $recentJobs = $this->job->getRecentForProject($id, 3);

        return View::render('seo-onpage/dashboard/index', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'problematicPages' => $problematicPages,
            'issuesByCategory' => $issuesByCategory,
            'commonIssues' => $commonIssues,
            'activeJob' => $activeJob,
            'recentJobs' => $recentJobs,
        ]);
    }
}
