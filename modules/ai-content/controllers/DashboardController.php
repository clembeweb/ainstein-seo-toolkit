<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\AiContent\Models\Project;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\WpSite;

/**
 * DashboardController
 *
 * Handles dashboard display for AI Content module
 */
class DashboardController
{
    private Project $project;
    private Keyword $keyword;
    private Article $article;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->article = new Article();
    }

    /**
     * Display module dashboard
     *
     * @param int|null $projectId Optional project ID to filter by
     */
    public function index(?int $projectId = null): string
    {
        $user = Auth::user();
        $project = null;

        // Se projectId specificato, verifica appartenga all'utente
        if ($projectId !== null) {
            $project = $this->project->find($projectId, $user['id']);

            if (!$project) {
                $_SESSION['_flash']['error'] = 'Progetto non trovato';
                Router::redirect('/ai-content');
                exit;
            }
        }

        // Get stats (filtrati per progetto se specificato)
        if ($project) {
            $stats = $this->article->getStatsByProject($projectId);
            $keywordsCount = $this->keyword->countByProject($projectId);
            // Carica lista keyword per il progetto
            $keywordsData = $this->keyword->allByProject($projectId);
        } else {
            $stats = $this->article->getStats($user['id']);
            $keywordsCount = $this->keyword->countByUser($user['id']);
            // Carica lista keyword per l'utente
            $keywordsData = $this->keyword->allByUser($user['id']);
        }

        // Get recent articles (filtrati per progetto se specificato)
        if ($project) {
            $recentArticles = $this->article->getRecentByProject($projectId, 5);
        } else {
            $recentArticles = $this->article->getRecent($user['id'], 5);
        }

        // Load linked WP site for project (if any)
        $linkedWpSite = null;
        if ($project && !empty($project['wp_site_id'])) {
            $wpSiteModel = new WpSite();
            $linkedWpSite = $wpSiteModel->find($project['wp_site_id'], $user['id']);
        }

        $title = $project ? $project['name'] : 'AI SEO Content Generator';

        return View::render('ai-content/dashboard', [
            'title' => $title,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'projectId' => $projectId,
            'linkedWpSite' => $linkedWpSite,
            'stats' => [
                'keywords' => $keywordsCount,
                'articles' => $stats['total'] ?? 0,
                'published' => $stats['published'] ?? 0,
                'ready' => $stats['ready'] ?? 0,
                'total_words' => $stats['total_words'] ?? 0,
                'total_credits' => $stats['total_credits'] ?? 0,
            ],
            'recentArticles' => $recentArticles,
            'keywords' => $keywordsData['data'] ?? []
        ]);
    }
}
