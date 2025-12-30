<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
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
    private Keyword $keyword;
    private Article $article;
    private WpSite $wpSite;

    public function __construct()
    {
        $this->keyword = new Keyword();
        $this->article = new Article();
        $this->wpSite = new WpSite();
    }

    /**
     * Display module dashboard
     */
    public function index(): string
    {
        $user = Auth::user();

        // Get stats
        $stats = $this->article->getStats($user['id']);
        $keywordsCount = $this->keyword->countByUser($user['id']);
        $wpSitesCount = $this->wpSite->countByUser($user['id']);

        // Get recent articles
        $recentArticles = $this->article->getRecent($user['id'], 5);

        return View::render('ai-content/dashboard', [
            'title' => 'AI SEO Content Generator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'stats' => [
                'keywords' => $keywordsCount,
                'articles' => $stats['total'] ?? 0,
                'published' => $stats['published'] ?? 0,
                'ready' => $stats['ready'] ?? 0,
                'total_words' => $stats['total_words'] ?? 0,
                'total_credits' => $stats['total_credits'] ?? 0,
                'wp_sites' => $wpSitesCount
            ],
            'recentArticles' => $recentArticles
        ]);
    }
}
