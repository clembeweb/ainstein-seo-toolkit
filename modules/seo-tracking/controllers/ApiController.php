<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Alert;
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
    private Alert $alert;
    private KeywordPosition $keywordPosition;

    public function __construct()
    {
        $this->project = new Project();
        $this->keyword = new Keyword();
        $this->gscDaily = new GscDaily();
        $this->alert = new Alert();
        $this->keywordPosition = new KeywordPosition();
    }

    /**
     * Verifica accesso progetto
     */
    private function checkProject(int $id): ?array
    {
        $user = Auth::user();
        return $this->project->findAccessible($id, $user['id']);
    }

    /**
     * Dati grafico traffico (GSC-based)
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

        $labels = [];
        $clicks = [];
        $impressions = [];

        foreach ($gscData as $row) {
            $labels[] = date('d/m', strtotime($row['date']));
            $clicks[] = (int) $row['total_clicks'];
            $impressions[] = (int) $row['total_impressions'];
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
                    'label' => 'Impressioni',
                    'data' => $impressions,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
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
        $keywords = $this->keyword->getTopByClicks($id, $limit);

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

    /**
     * Keyword tracciate del progetto (per rank-check)
     */
    public function trackedKeywords(int $id): string
    {
        if (!$this->checkProject($id)) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        // Prendi tutte le keyword del progetto (stesso metodo usato in Keywords view)
        $keywords = $this->keyword->allWithPositions($id, 30);

        // Filtra solo le tracciate (is_tracked = 1)
        $tracked = array_filter($keywords, fn($k) => !empty($k['is_tracked']));

        return View::json([
            'success' => true,
            'keywords' => array_values($tracked),
            'count' => count($tracked),
        ]);
    }
}
