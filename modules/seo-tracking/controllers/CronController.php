<?php

namespace Modules\SeoTracking\Controllers;

use Core\Database;
use Modules\SeoTracking\Services\GscService;

/**
 * CronController
 * Gestisce sync automatici programmati
 */
class CronController
{
    /**
     * Sync giornaliero per tutti i progetti attivi
     * URL: /seo-tracking/cron/daily-sync?secret=XXX
     *
     * Esegue sync degli ultimi 5 giorni per compensare:
     * - GSC: delay di 3 giorni nei dati
     *
     * Setup cron (ogni giorno alle 4:00):
     * 0 4 * * * curl -s "https://tuodominio.com/seo-tracking/cron/daily-sync?secret=CRON_SECRET"
     */
    public function dailySync(): void
    {
        // Verifica secret
        $secret = $_GET['secret'] ?? '';
        $expectedSecret = getenv('CRON_SECRET') ?: 'cron-secret-change-me';

        if (!hash_equals($expectedSecret, $secret)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid secret']);
            exit;
        }

        // Aumenta limiti per operazioni lunghe
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $db = Database::getInstance();

        // Trova tutti i progetti con GSC connesso
        $projects = Database::fetchAll("
            SELECT p.id, p.name, p.gsc_connected
            FROM st_projects p
            WHERE p.gsc_connected = 1
            AND p.deleted_at IS NULL
        ");

        $results = [];
        $startTime = microtime(true);

        foreach ($projects as $project) {
            $result = [
                'project_id' => $project['id'],
                'name' => $project['name'],
                'gsc' => null
            ];

            // Sync GSC (ultimi 5 giorni per compensare delay)
            if ($project['gsc_connected']) {
                try {
                    $gscService = new GscService();
                    $endDate = date('Y-m-d', strtotime('-3 days'));
                    $startDate = date('Y-m-d', strtotime('-7 days')); // 5 giorni di dati

                    $count = $gscService->syncDateRange($project['id'], $startDate, $endDate);
                    $result['gsc'] = ['success' => true, 'records' => $count];

                    // Aggiorna timestamp
                    Database::execute(
                        "UPDATE st_projects SET last_sync_at = NOW(), sync_status = 'completed' WHERE id = ?",
                        [$project['id']]
                    );

                } catch (\Throwable $e) {
                    $result['gsc'] = ['success' => false, 'error' => $e->getMessage()];
                    error_log("[Cron] GSC sync failed for project {$project['id']}: " . $e->getMessage());
                }
            }

            $results[] = $result;

            // Piccola pausa tra progetti per non sovraccaricare API
            usleep(500000); // 500ms
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        // Response JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'elapsed_seconds' => $elapsed,
            'projects_synced' => count($results),
            'results' => $results
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Status endpoint per verificare che il cron sia configurato
     * URL: /seo-tracking/cron/status?secret=XXX
     */
    public function status(): void
    {
        $secret = $_GET['secret'] ?? '';
        $expectedSecret = getenv('CRON_SECRET') ?: 'cron-secret-change-me';

        if (!hash_equals($expectedSecret, $secret)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid secret']);
            exit;
        }

        // Conta progetti attivi
        $stats = Database::fetch("
            SELECT
                COUNT(*) as total_projects,
                SUM(gsc_connected) as gsc_connected
            FROM st_projects
            WHERE deleted_at IS NULL
        ");

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'projects' => [
                'total' => (int) $stats['total_projects'],
                'gsc_connected' => (int) $stats['gsc_connected'],
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Sync singolo progetto (per test o re-sync manuale)
     * URL: /seo-tracking/cron/sync-project?secret=XXX&id=123&days=30
     */
    public function syncProject(): void
    {
        $secret = $_GET['secret'] ?? '';
        $expectedSecret = getenv('CRON_SECRET') ?: 'cron-secret-change-me';

        if (!hash_equals($expectedSecret, $secret)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid secret']);
            exit;
        }

        $projectId = (int) ($_GET['id'] ?? 0);
        $days = (int) ($_GET['days'] ?? 5);

        if ($projectId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID progetto mancante']);
            exit;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $project = Database::fetch(
            "SELECT * FROM st_projects WHERE id = ? AND deleted_at IS NULL",
            [$projectId]
        );

        if (!$project) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $result = [
            'project_id' => $projectId,
            'name' => $project['name'],
            'days' => $days,
            'gsc' => null
        ];

        // Sync GSC
        if ($project['gsc_connected']) {
            try {
                $gscService = new GscService();
                $endDate = date('Y-m-d', strtotime('-3 days'));
                $startDate = date('Y-m-d', strtotime("-" . ($days + 3) . " days"));

                $count = $gscService->syncDateRange($projectId, $startDate, $endDate);
                $result['gsc'] = ['success' => true, 'records' => $count];

                Database::execute(
                    "UPDATE st_projects SET last_sync_at = NOW(), sync_status = 'completed' WHERE id = ?",
                    [$projectId]
                );

            } catch (\Throwable $e) {
                $result['gsc'] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
}
