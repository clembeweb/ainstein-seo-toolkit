<?php
/**
 * Cron Job: Daily Sync
 * Sincronizza dati GSC per tutti i progetti attivi
 *
 * Eseguire giornalmente alle 06:00
 * 0 6 * * * php /path/to/modules/seo-tracking/cron/daily-sync.php
 */

// Bootstrap
require_once dirname(__DIR__, 3) . '/core/bootstrap.php';

use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\GscService;
use Modules\SeoTracking\Services\AlertService;

echo "[" . date('Y-m-d H:i:s') . "] Starting daily sync...\n";

$project = new Project();
$gscService = new GscService();
$alertService = new AlertService();

// Prendi tutti i progetti con sync abilitato
$projects = $project->getActiveForSync();

echo "Found " . count($projects) . " projects to sync\n";

foreach ($projects as $proj) {
    echo "\n--- Processing: {$proj['name']} ({$proj['domain']}) ---\n";

    // Sync GSC
    if ($proj['gsc_connected']) {
        echo "Syncing GSC... ";
        try {
            $result = $gscService->syncSearchAnalytics($proj['id']);
            echo "OK ({$result['records_fetched']} records)\n";
        } catch (\Exception $e) {
            echo "ERROR: {$e->getMessage()}\n";
        }
    }

    // Check alerts
    echo "Checking alerts... ";
    try {
        $alerts = $alertService->checkAlerts($proj['id']);
        echo "OK (" . count($alerts) . " new alerts)\n";
    } catch (\Exception $e) {
        echo "ERROR: {$e->getMessage()}\n";
    }

    // Pausa tra progetti per non sovraccaricare le API
    sleep(2);
}

echo "\n[" . date('Y-m-d H:i:s') . "] Daily sync completed.\n";
