<?php
/**
 * Google Ads Sync Dispatcher
 *
 * Sincronizza automaticamente i dati Google Ads per tutti i progetti configurati.
 * Cron: ogni 6 ore (0 0,6,12,18 * * *)
 *
 * Requisiti per ogni progetto:
 * - sync_enabled = 1
 * - google_ads_customer_id configurato
 * - status != 'archived'
 * - Token OAuth valido (google_oauth_tokens con service = 'google_ads')
 * - Non sincronizzato entro la finestra configurata (default 6h)
 */

// Solo CLI
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}

// Bootstrap CLI
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Core\Settings;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\Sync;
use Modules\AdsAnalyzer\Models\AutoEvalQueue;
use Modules\AdsAnalyzer\Services\CampaignSyncService;
use Services\GoogleAdsService;

// Configurazione
ignore_user_abort(true);
set_time_limit(0);

$logFile = BASE_PATH . '/storage/logs/gads-sync-dispatcher.log';

function logMessage(string $message, string $level = 'INFO'): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}\n";
    echo $line;

    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $line, FILE_APPEND);
}

try {
    logMessage("=== Inizio Google Ads Sync Dispatcher ===");

    // Verifica se il sync automatico e' abilitato globalmente
    $syncEnabled = ModuleLoader::getSetting('ads-analyzer', 'gads_auto_sync_enabled', true);
    if (!$syncEnabled) {
        logMessage("Sync automatico disabilitato nelle impostazioni modulo");
        logMessage("=== Fine Google Ads Sync Dispatcher ===");
        logMessage("");
        exit(0);
    }

    // Finestra di frequenza: skip progetti sincronizzati entro queste ore
    $frequencyHours = (int) Settings::get('gads_sync_frequency_hours', 6);

    // Trova tutti i progetti con sync abilitato e Google Ads configurato
    $projects = Database::fetchAll(
        "SELECT p.*, t.user_id as token_user_id
         FROM ga_projects p
         INNER JOIN google_oauth_tokens t
             ON t.user_id = p.user_id AND t.service = 'google_ads'
         WHERE p.sync_enabled = 1
           AND p.google_ads_customer_id IS NOT NULL
           AND p.google_ads_customer_id != ''
           AND p.status != 'archived'"
    );

    if (empty($projects)) {
        logMessage("Nessun progetto configurato per il sync automatico");
        logMessage("=== Fine Google Ads Sync Dispatcher ===");
        logMessage("");
        exit(0);
    }

    logMessage("Trovati " . count($projects) . " progetti candidati (frequenza: ogni {$frequencyHours}h)");

    $synced = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($projects as $project) {
        $projectId = (int) $project['id'];
        $projectName = $project['name'] ?? "ID #{$projectId}";
        $userId = (int) $project['user_id'];
        $customerId = $project['google_ads_customer_id'];

        logMessage("--- Progetto: {$projectName} (ID: {$projectId}) ---");

        try {
            // Skip se sync gia' in corso
            $runningSync = Sync::getRunningSync($projectId);
            if ($runningSync) {
                logMessage("  SKIP: sync gia' in corso (ID: {$runningSync['id']})");
                $skipped++;
                continue;
            }

            // Skip se sincronizzato di recente
            $lastSync = Sync::getLatestByProject($projectId);
            if ($lastSync) {
                $lastSyncTime = strtotime($lastSync['completed_at'] ?? $lastSync['started_at'] ?? '');
                $hoursSinceSync = (time() - $lastSyncTime) / 3600;

                if ($hoursSinceSync < $frequencyHours) {
                    $hoursAgo = round($hoursSinceSync, 1);
                    logMessage("  SKIP: ultimo sync {$hoursAgo}h fa (soglia: {$frequencyHours}h)");
                    $skipped++;
                    continue;
                }
            }

            // Date range: ultimi 7 giorni
            $dateTo = date('Y-m-d');
            $dateFrom = date('Y-m-d', strtotime('-7 days'));

            logMessage("  Sync periodo: {$dateFrom} — {$dateTo}");

            // Crea GoogleAdsService e CampaignSyncService
            $loginCustomerId = isset($project['login_customer_id']) ? $project['login_customer_id'] : '';
            $gadsService = new GoogleAdsService($userId, $customerId, $loginCustomerId);
            $syncService = new CampaignSyncService($gadsService, $projectId);

            // Esegui sync completo
            $result = $syncService->syncAll($dateFrom, $dateTo, $userId, 'cron');

            Database::reconnect();

            if ($result['success']) {
                $counts = $result['counts'] ?? [];
                $summary = implode(', ', array_map(
                    fn($k, $v) => str_replace('_synced', '', $k) . ": {$v}",
                    array_keys($counts),
                    array_values($counts)
                ));
                logMessage("  OK: sync #{$result['sync_id']} completato ({$summary})");
                $synced++;

                // Se auto_evaluate e' attivo, accoda per valutazione AI
                if (!empty($project['auto_evaluate']) && !empty($result['sync_id'])) {
                    try {
                        AutoEvalQueue::create([
                            'project_id' => $projectId,
                            'sync_id' => $result['sync_id'],
                            'scheduled_for' => date('Y-m-d H:i:s'),
                        ]);
                        logMessage("  Auto-eval accodato (sync_id: {$result['sync_id']})");
                    } catch (\Exception $e) {
                        logMessage("  WARNING: impossibile accodare auto-eval: " . $e->getMessage(), 'WARN');
                    }
                    Database::reconnect();
                }
            } else {
                $error = $result['error'] ?? 'Errore sconosciuto';
                logMessage("  ERRORE: {$error}", 'ERROR');
                $errors++;
            }

        } catch (\Exception $e) {
            Database::reconnect();
            logMessage("  ERRORE: " . $e->getMessage(), 'ERROR');
            $errors++;
        }

        // Pausa tra progetti per rispettare rate limits
        sleep(2);
    }

    logMessage("=== Riepilogo: Sincronizzati {$synced}, Saltati {$skipped}, Errori {$errors} ===");
    logMessage("=== Fine Google Ads Sync Dispatcher ===");
    logMessage("");

} catch (\Exception $e) {
    logMessage("ERRORE FATALE: " . $e->getMessage(), 'FATAL');
    exit(1);
}

exit(0);
