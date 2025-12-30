<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Ga4Daily;
use Modules\SeoTracking\Models\Alert;
use Modules\SeoTracking\Models\KeywordRevenue;
use Modules\SeoTracking\Models\KeywordPosition;

/**
 * ApiController
 * Endpoint AJAX per grafici e dati real-time
 */
class ApiController
{
    private Project $project;
    private Keyword $keyword;
    private GscDaily $gscDaily;
    private Ga4Daily $ga4Daily;
    private Alert $alert;
    private KeywordRevenue $keywordRevenue;
    private KeywordPosition $keywordPosition;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscDaily = new GscDaily();
        $this->ga4Daily = new Ga4Daily();
        $this->alert = new Alert();
        $this->keywordRevenue = new KeywordRevenue();
        $this->keywordPosition = new KeywordPosition();
    }

    /**
     * Verifica accesso progetto
     */
    private function checkProject(int $id): ?array
    {
        $user = Auth::user();
        return $this->project->find($id, $user['id']);
    }

    /**
     * Dati grafico traffico
     */
    public function trafficChart(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $days = (int) ($_GET['days'] ?? 30);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $gscData = $this->gscDaily->getByDateRange($id, $startDate, $endDate);
        $ga4Data = $this->ga4Daily->getByDateRange($id, $startDate, $endDate);

        // Indicizza GA4 per data
        $ga4ByDate = [];
        foreach ($ga4Data as $row) {
            $ga4ByDate[$row['date']] = $row;
        }

        $labels = [];
        $clicks = [];
        $impressions = [];
        $sessions = [];

        foreach ($gscData as $row) {
            $labels[] = date('d/m', strtotime($row['date']));
            $clicks[] = (int) $row['total_clicks'];
            $impressions[] = (int) $row['total_impressions'];
            $sessions[] = (int) ($ga4ByDate[$row['date']]['sessions'] ?? 0);
        }

        return View::json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Click',
                    'data' => $clicks,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Sessioni',
                    'data' => $sessions,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
        ]);
    }

    /**
     * Dati grafico revenue
     */
    public function revenueChart(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $days = (int) ($_GET['days'] ?? 30);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $dailyRevenue = $this->keywordRevenue->getDailyRevenue($id, $startDate, $endDate);

        $labels = [];
        $revenue = [];
        $purchases = [];

        foreach ($dailyRevenue as $row) {
            $labels[] = date('d/m', strtotime($row['date']));
            $revenue[] = round((float) $row['total_revenue'], 2);
            $purchases[] = (int) $row['total_purchases'];
        }

        return View::json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (€)',
                    'data' => $revenue,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Acquisti',
                    'data' => $purchases,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'yAxisID' => 'y1',
                ],
            ],
        ]);
    }

    /**
     * Dati grafico posizioni
     */
    public function positionsChart(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $keywordId = (int) ($_GET['keyword_id'] ?? 0);
        $days = (int) ($_GET['days'] ?? 90);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        if ($keywordId > 0) {
            // Posizioni singola keyword
            $positions = $this->keywordPosition->getByKeyword($keywordId, $startDate, $endDate);

            $labels = [];
            $data = [];

            foreach ($positions as $row) {
                $labels[] = date('d/m', strtotime($row['date']));
                $data[] = round((float) $row['avg_position'], 1);
            }

            return View::json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Posizione Media',
                        'data' => $data,
                        'borderColor' => '#3b82f6',
                        'fill' => false,
                    ],
                ],
            ]);
        } else {
            // Posizione media progetto
            $gscData = $this->gscDaily->getByDateRange($id, $startDate, $endDate);

            $labels = [];
            $data = [];

            foreach ($gscData as $row) {
                $labels[] = date('d/m', strtotime($row['date']));
                $data[] = round((float) $row['avg_position'], 1);
            }

            return View::json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Posizione Media',
                        'data' => $data,
                        'borderColor' => '#3b82f6',
                        'fill' => false,
                    ],
                ],
            ]);
        }
    }

    /**
     * Stato sync
     */
    public function syncStatus(int $id): string
    {
        $project = $this->checkProject($id);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        return View::json([
            'sync_status' => $project['sync_status'],
            'last_sync_at' => $project['last_sync_at'],
            'gsc_connected' => (bool) $project['gsc_connected'],
            'ga4_connected' => (bool) $project['ga4_connected'],
        ]);
    }

    /**
     * Stats riassuntive
     */
    public function stats(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $stats = $this->project->getStats($id);

        return View::json($stats);
    }

    /**
     * Top keywords
     */
    public function topKeywords(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $limit = (int) ($_GET['limit'] ?? 10);
        $orderBy = $_GET['order_by'] ?? 'clicks';

        if ($orderBy === 'revenue') {
            $endDate = date('Y-m-d', strtotime('-1 day'));
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $keywords = $this->keywordRevenue->getTopByRevenue($id, $startDate, $endDate, $limit);
        } else {
            $keywords = $this->keyword->getTopByClicks($id, $limit);
        }

        return View::json(['keywords' => $keywords]);
    }

    /**
     * Alert recenti
     */
    public function recentAlerts(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $limit = (int) ($_GET['limit'] ?? 5);
        $alerts = $this->alert->getNew($id, $limit);

        return View::json(['alerts' => $alerts]);
    }
}
