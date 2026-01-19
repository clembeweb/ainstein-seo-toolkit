<?php
/**
 * Cron Job: Daily Sync
 * Sincronizza dati GSC e GA4 per tutti i progetti attivi
 *
 * Approccio Smart Hybrid:
 * - Sync giornaliero: ultimi 10 giorni (delta)
 * - Cleanup automatico: rimuove dati >90 giorni
 * - Pulizia cache: rimuove file scaduti
 *
 * Eseguire giornalmente alle 06:00
 * 0 6 * * * php /path/to/modules/seo-tracking/cron/daily-sync.php
 */

// Bootstrap
require_once dirname(__DIR__, 3) . '/core/bootstrap.php';

use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\GscService;
use Modules\SeoTracking\Services\Ga4Service;
use Modules\SeoTracking\Services\AlertService;
use Modules\SeoTracking\Services\GscDataService;
use Modules\SeoTracking\Services\GscCacheService;

echo "[" . date('Y-m-d H:i:s') . "] Starting daily sync...\n";

$project = new Project();
$gscService = new GscService();
$ga4Service = new Ga4Service();
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

    // Sync GA4
    if ($proj['ga4_connected']) {
        echo "Syncing GA4... ";
        try {
            $result = $ga4Service->syncAnalytics($proj['id']);
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

// === CLEANUP: Rimuovi dati vecchi (>90 giorni) ===
echo "\n--- Cleanup dati storici ---\n";

try {
    $gscDataService = new GscDataService();
    $cacheService = new GscCacheService();

    // Pulisci dati DB vecchi
    echo "Pulizia dati GSC >90 giorni... ";
    $eliminati = $gscDataService->pulisciDatiVecchi();
    echo "OK ({$eliminati} record eliminati)\n";

    // Pulisci cache scaduta
    echo "Pulizia cache scaduta... ";
    $cacheEliminati = $cacheService->pulisciScaduti();
    echo "OK ({$cacheEliminati} file eliminati)\n";

    // Statistiche cache
    $statsCache = $cacheService->getStatistiche();
    echo "Cache attuale: {$statsCache['totale_file']} file, {$statsCache['dimensione_mb']} MB\n";

} catch (\Exception $e) {
    echo "ERROR durante cleanup: {$e->getMessage()}\n";
}

echo "\n[" . date('Y-m-d H:i:s') . "] Daily sync completed.\n";
