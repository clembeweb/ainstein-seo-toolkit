<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Ga4Daily;
use Modules\SeoTracking\Models\Alert;
use Modules\SeoTracking\Models\AiReport;
use Modules\SeoTracking\Models\KeywordRevenue;

/**
 * DashboardController
 * Gestisce le dashboard del progetto
 */
class DashboardController
{
    private Project $project;
    private Keyword $keyword;
    private GscDaily $gscDaily;
    private Ga4Daily $ga4Daily;
    private Alert $alert;
    private AiReport $aiReport;
    private KeywordRevenue $keywordRevenue;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscDaily = new GscDaily();
        $this->ga4Daily = new Ga4Daily();
        $this->alert = new Alert();
        $this->aiReport = new AiReport();
        $this->keywordRevenue = new KeywordRevenue();
    }

    /**
     * Dashboard principale progetto
     */
    public function index(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithConnections($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        // Date range
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevEndDate = date('Y-m-d', strtotime('-8 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));

        // Metriche GSC
        $gscComparison = $this->gscDaily->comparePeriods($id, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // Metriche GA4
        $ga4Comparison = $this->ga4Daily->comparePeriods($id, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // Top keywords per click
        $topKeywords = $this->keyword->getTopByClicks($id, 10);

        // Top movers
        $topMovers = $this->keyword->getTopMovers($id, 5);

        // Alert recenti
        $recentAlerts = $this->alert->getNew($id, 5);

        // Ultimi report AI
        $recentReports = $this->aiReport->getByProject($id, ['limit' => 3]);

        // Stats riassuntive
        $stats = $this->project->getStats($id);

        return View::render('seo-tracking/dashboard/index', [
            'title' => $project['name'] . ' - Dashboard',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'stats' => $stats,
            'gscComparison' => $gscComparison,
            'ga4Comparison' => $ga4Comparison,
            'topKeywords' => $topKeywords,
            'topMovers' => $topMovers,
            'recentAlerts' => $recentAlerts,
            'recentReports' => $recentReports,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Vista keyword overview
     */
    public function keywords(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));

        // Keyword con posizioni
        $keywords = $this->keyword->allWithPositions($id, 30);

        // Distribuzione posizioni
        $positionDistribution = [
            'top3' => 0,
            'top10' => 0,
            'top20' => 0,
            'top50' => 0,
            'beyond50' => 0,
        ];

        foreach ($keywords as $kw) {
            $pos = $kw['last_position'] ?? 100;
            if ($pos <= 3) $positionDistribution['top3']++;
            elseif ($pos <= 10) $positionDistribution['top10']++;
            elseif ($pos <= 20) $positionDistribution['top20']++;
            elseif ($pos <= 50) $positionDistribution['top50']++;
            else $positionDistribution['beyond50']++;
        }

        // Gruppi keyword
        $groups = $this->keyword->getGroups($id);

        return View::render('seo-tracking/dashboard/keywords', [
            'title' => $project['name'] . ' - Keyword Overview',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'keywords' => $keywords,
            'positionDistribution' => $positionDistribution,
            'groups' => $groups,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Vista pages
     */
    public function pages(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));

        // Top pages per sessioni (da GA4)
        $ga4Data = new \Modules\SeoTracking\Models\Ga4Data();
        $topPagesBySessions = $ga4Data->getTopPagesBySessions($id, $startDate, $endDate, 50);

        // Top pages per revenue
        $topPagesByRevenue = $this->keywordRevenue->getTopPagesByRevenue($id, $startDate, $endDate, 50);

        return View::render('seo-tracking/dashboard/pages', [
            'title' => $project['name'] . ' - Pages',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'topPagesBySessions' => $topPagesBySessions,
            'topPagesByRevenue' => $topPagesByRevenue,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Vista revenue
     */
    public function revenue(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $prevEndDate = date('Y-m-d', strtotime('-31 days'));
        $prevStartDate = date('Y-m-d', strtotime('-60 days'));

        // Revenue comparison
        $ga4Comparison = $this->ga4Daily->comparePeriods($id, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // Top keyword per revenue
        $topKeywordsByRevenue = $this->keywordRevenue->getTopByRevenue($id, $startDate, $endDate, 30);

        // Revenue giornaliero
        $dailyRevenue = $this->keywordRevenue->getDailyRevenue($id, $startDate, $endDate);

        // Top pages per revenue
        $topPagesByRevenue = $this->keywordRevenue->getTopPagesByRevenue($id, $startDate, $endDate, 20);

        return View::render('seo-tracking/dashboard/revenue', [
            'title' => $project['name'] . ' - Revenue',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'ga4Comparison' => $ga4Comparison,
            'topKeywordsByRevenue' => $topKeywordsByRevenue,
            'dailyRevenue' => $dailyRevenue,
            'topPagesByRevenue' => $topPagesByRevenue,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}
