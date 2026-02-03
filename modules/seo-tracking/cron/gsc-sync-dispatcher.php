<?php
/**
 * GSC Sync Auto Dispatcher (CRON Scheduler)
 *
 * Sincronizza automaticamente le metriche GSC per le keyword tracciate
 * secondo la schedulazione configurata dall'admin.
 *
 * Crontab: ogni ora
 * 0 * * * * php /path/to/modules/seo-tracking/cron/gsc-sync-dispatcher.php
 *
 * Settings (da /admin/modules/{id}/settings - modules.settings JSON):
 * - gsc_sync_enabled: true/false per attivare
 * - gsc_sync_frequency: preset giorni (daily, mon_thu, mon_wed_fri, weekly)
 * - gsc_sync_time: Orario di avvio (es. "05:00")
 */

// Solo CLI
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}

// Timezone Italia
date_default_timezone_set('Europe/Rome');

// Bootstrap
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\GscService;

// Log file giornaliero
define('LOG_FILE', BASE_PATH . '/storage/logs/gsc_sync_' . date('Y-m-d') . '.log');
define('LAST_RUN_FILE', BASE_PATH . '/storage/logs/gsc_sync_last_run.txt');

/**
 * Converte preset giorni in array di codici giorno
 */
function convertDaysPreset(string $preset): array
{
    return match ($preset) {
        'daily' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
        'mon_thu' => ['mon', 'thu'],
        'mon_wed_fri' => ['mon', 'wed', 'fri'],
        'weekly' => ['mon'],
        default => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], // default: daily
    };
}

/**
 * Legge data ultimo run da file
 */
function getLastRunDate(): ?string
{
    if (!file_exists(LAST_RUN_FILE)) {
        return null;
    }
    $content = trim(file_get_contents(LAST_RUN_FILE));
    return $content ?: null;
}

/**
 * Salva data ultimo run su file
 */
function setLastRunDate(string $date): void
{
    $dir = dirname(LAST_RUN_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(LAST_RUN_FILE, $date);
}

/**
 * Log message per GSC sync
 */
function logGscSync(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";

    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);

    // Echo per monitoraggio CLI
    echo $logLine;
}

/**
 * Verifica se oggi e' un giorno schedulato
 */
function isScheduledDay(array $scheduledDays): bool
{
    if (empty($scheduledDays)) {
        return false;
    }

    $today = strtolower(date('D')); // mon, tue, wed, thu, fri, sat, sun
    return in_array($today, array_map('strtolower', $scheduledDays));
}

/**
 * Verifica se e' l'orario giusto per eseguire
 */
function isScheduledTime(string $scheduledTime, ?string $lastRun): bool
{
    // Se gia' eseguito oggi, non eseguire di nuovo
    $today = date('Y-m-d');
    if ($lastRun === $today) {
        return false;
    }

    // Verifica orario
    $currentTime = date('H:i');
    $scheduledParts = explode(':', $scheduledTime);
    $scheduledHour = (int) ($scheduledParts[0] ?? 0);
    $scheduledMinute = (int) ($scheduledParts[1] ?? 0);

    $currentParts = explode(':', $currentTime);
    $currentHour = (int) $currentParts[0];
    $currentMinute = (int) $currentParts[1];

    // Se ora corrente >= orario schedulato, e' il momento giusto
    if ($currentHour > $scheduledHour) {
        return true;
    }
    if ($currentHour === $scheduledHour && $currentMinute >= $scheduledMinute) {
        return true;
    }

    return false;
}

/**
 * Ottieni progetti con GSC connesso che hanno keyword tracciate
 */
function getProjectsForSync(): array
{
    return Database::fetchAll(
        "SELECT DISTINCT p.*
         FROM st_projects p
         INNER JOIN st_gsc_connections gc ON gc.project_id = p.id
         INNER JOIN st_keywords k ON k.project_id = p.id AND k.is_tracked = 1
         WHERE p.gsc_connected = 1
           AND gc.property_url IS NOT NULL
           AND gc.refresh_token IS NOT NULL"
    );
}

/**
 * Main dispatcher
 */
function runGscSyncDispatcher(): void
{
    logGscSync("=== GSC SYNC DISPATCHER START ===");

    // Verifica se il sync automatico e' abilitato (legge da modules.settings)
    $enabled = ModuleLoader::getSetting('seo-tracking', 'gsc_sync_enabled', false);
    if (!$enabled) {
        logGscSync("GSC sync automatico disabilitato");
        logGscSync("=== GSC SYNC DISPATCHER END ===\n");
        return;
    }

    // Leggi configurazione schedulazione da module settings
    $frequencyPreset = ModuleLoader::getSetting('seo-tracking', 'gsc_sync_frequency', 'daily');
    $scheduledDays = convertDaysPreset($frequencyPreset);
    $scheduledTime = ModuleLoader::getSetting('seo-tracking', 'gsc_sync_time', '05:00');

    // Leggi last_run
    $lastRun = getLastRunDate();

    logGscSync("Configurazione: frequenza={$frequencyPreset}, giorni=" . implode(',', $scheduledDays) . ", ora={$scheduledTime}, ultimo run=" . ($lastRun ?? 'mai'));

    // Verifica se e' il giorno giusto
    if (!isScheduledDay($scheduledDays)) {
        logGscSync("Oggi non e' un giorno schedulato (" . date('D') . ")");
        logGscSync("=== GSC SYNC DISPATCHER END ===\n");
        return;
    }

    // Verifica orario
    if (!isScheduledTime($scheduledTime, $lastRun)) {
        logGscSync("Non e' ancora l'orario schedulato ({$scheduledTime}) o gia' eseguito oggi");
        logGscSync("=== GSC SYNC DISPATCHER END ===\n");
        return;
    }

    // E' il momento giusto - esegui sync per tutti i progetti
    logGscSync("Avvio sync GSC per keyword tracciate...");

    $projects = getProjectsForSync();
    logGscSync("Trovati " . count($projects) . " progetti con GSC connesso e keyword tracciate");

    if (empty($projects)) {
        logGscSync("Nessun progetto da sincronizzare");
        setLastRunDate(date('Y-m-d'));
        logGscSync("=== GSC SYNC DISPATCHER END ===\n");
        return;
    }

    $gscService = new GscService();
    $totalKeywordsUpdated = 0;
    $projectsSynced = 0;
    $projectsFailed = 0;

    foreach ($projects as $project) {
        logGscSync("--- Progetto: {$project['name']} ({$project['domain']}) ---");

        try {
            $result = $gscService->syncTrackedKeywordsOnly($project['id']);

            // Reconnect al database dopo chiamata API lunga
            Database::reconnect();

            $totalKeywordsUpdated += $result['keywords_updated'];
            $projectsSynced++;

            logGscSync("  OK: {$result['keywords_updated']}/{$result['keywords_processed']} keyword aggiornate");

        } catch (\Exception $e) {
            // Reconnect in caso di errore
            Database::reconnect();

            $projectsFailed++;
            logGscSync("  ERRORE: " . $e->getMessage(), 'ERROR');
        }

        // Pausa tra progetti per non sovraccaricare le API Google
        sleep(2);
    }

    // Aggiorna last_run
    setLastRunDate(date('Y-m-d'));

    // Statistiche finali
    logGscSync("=== RIEPILOGO ===");
    logGscSync("Progetti sincronizzati: {$projectsSynced}");
    logGscSync("Progetti con errori: {$projectsFailed}");
    logGscSync("Keyword totali aggiornate: {$totalKeywordsUpdated}");
    logGscSync("=== GSC SYNC DISPATCHER END ===\n");
}

// Esegui
try {
    runGscSyncDispatcher();
} catch (\Exception $e) {
    logGscSync("FATAL: " . $e->getMessage(), 'FATAL');
    exit(1);
}

exit(0);
