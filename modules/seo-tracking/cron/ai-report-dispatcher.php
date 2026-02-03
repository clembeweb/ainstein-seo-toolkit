<?php
/**
 * AI Report Auto Dispatcher (CRON Scheduler)
 *
 * Genera automaticamente report AI settimanali per tutti i progetti
 * secondo la schedulazione configurata dall'admin.
 *
 * Crontab: ogni ora (o una volta al giorno)
 * 0 * * * * php /path/to/modules/seo-tracking/cron/ai-report-dispatcher.php
 *
 * Settings (da /admin/modules/{id}/settings - modules.settings JSON):
 * - ai_reports_enabled: true/false per attivare
 * - ai_reports_day: giorno della settimana (0-6, 0=dom)
 * - ai_reports_time: Orario di avvio (es. "07:00")
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
use Modules\SeoTracking\Services\AiReportService;

// Log file giornaliero
define('LOG_FILE', BASE_PATH . '/storage/logs/ai_report_' . date('Y-m-d') . '.log');
define('LAST_RUN_FILE', BASE_PATH . '/storage/logs/ai_report_last_run.txt');

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
 * Log message
 */
function logAiReport(string $message, string $level = 'INFO'): void
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
 * Verifica se oggi e' il giorno schedulato
 */
function isScheduledDay(int $scheduledDay): bool
{
    $today = (int) date('w'); // 0-6, 0=domenica
    return $today === $scheduledDay;
}

/**
 * Verifica se e' l'orario giusto per eseguire
 */
function isScheduledTime(string $scheduledTime, ?string $lastRun): bool
{
    // Se gia' eseguito questa settimana, non eseguire di nuovo
    $thisWeek = date('Y-W');
    $lastRunWeek = $lastRun ? date('Y-W', strtotime($lastRun)) : null;

    if ($lastRunWeek === $thisWeek) {
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

    // Se ora corrente >= orario schedulato
    if ($currentHour > $scheduledHour) {
        return true;
    }
    if ($currentHour === $scheduledHour && $currentMinute >= $scheduledMinute) {
        return true;
    }

    return false;
}

/**
 * Ottieni progetti con dati GSC per generare report
 */
function getProjectsForReport(): array
{
    return Database::fetchAll(
        "SELECT p.*, u.id as owner_id
         FROM st_projects p
         INNER JOIN users u ON p.user_id = u.id
         WHERE p.gsc_connected = 1
         ORDER BY p.id"
    );
}

/**
 * Main dispatcher
 */
function runAiReportDispatcher(): void
{
    logAiReport("=== AI REPORT DISPATCHER START ===");

    // Verifica se i report automatici sono abilitati
    $enabled = ModuleLoader::getSetting('seo-tracking', 'ai_reports_enabled', false);
    if (!$enabled) {
        logAiReport("AI Reports automatici disabilitati");
        logAiReport("=== AI REPORT DISPATCHER END ===\n");
        return;
    }

    // Leggi configurazione schedulazione
    $scheduledDay = (int) ModuleLoader::getSetting('seo-tracking', 'ai_reports_day', 1); // default lunedi
    $scheduledTime = ModuleLoader::getSetting('seo-tracking', 'ai_reports_time', '07:00');

    $dayNames = ['Domenica', 'Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato'];
    $scheduledDayName = $dayNames[$scheduledDay] ?? 'Lunedi';

    // Leggi last_run
    $lastRun = getLastRunDate();

    logAiReport("Configurazione: giorno={$scheduledDayName}, ora={$scheduledTime}, ultimo run=" . ($lastRun ?? 'mai'));

    // Verifica se e' il giorno giusto
    if (!isScheduledDay($scheduledDay)) {
        $todayName = $dayNames[(int) date('w')];
        logAiReport("Oggi ({$todayName}) non e' il giorno schedulato ({$scheduledDayName})");
        logAiReport("=== AI REPORT DISPATCHER END ===\n");
        return;
    }

    // Verifica orario
    if (!isScheduledTime($scheduledTime, $lastRun)) {
        logAiReport("Non e' ancora l'orario schedulato ({$scheduledTime}) o gia' eseguito questa settimana");
        logAiReport("=== AI REPORT DISPATCHER END ===\n");
        return;
    }

    // E' il momento giusto - genera report per tutti i progetti
    logAiReport("Avvio generazione report AI settimanali...");

    $projects = getProjectsForReport();
    logAiReport("Trovati " . count($projects) . " progetti con GSC connesso");

    if (empty($projects)) {
        logAiReport("Nessun progetto per cui generare report");
        setLastRunDate(date('Y-m-d'));
        logAiReport("=== AI REPORT DISPATCHER END ===\n");
        return;
    }

    $aiReportService = new AiReportService();

    if (!$aiReportService->isConfigured()) {
        logAiReport("ERRORE: AiService non configurato (API key mancante)", 'ERROR');
        logAiReport("=== AI REPORT DISPATCHER END ===\n");
        return;
    }

    $reportsGenerated = 0;
    $reportsFailed = 0;

    foreach ($projects as $project) {
        logAiReport("--- Progetto: {$project['name']} ({$project['domain']}) ---");

        try {
            $result = $aiReportService->generateWeeklyDigest($project['id'], $project['owner_id']);

            // Reconnect al database dopo chiamata API lunga
            Database::reconnect();

            if ($result && isset($result['id'])) {
                $reportsGenerated++;
                logAiReport("  OK: Report generato (ID: {$result['id']})");
            } else {
                $reportsFailed++;
                logAiReport("  ERRORE: Generazione fallita (nessun risultato)", 'WARN');
            }

        } catch (\Exception $e) {
            // Reconnect in caso di errore
            Database::reconnect();

            $reportsFailed++;
            logAiReport("  ERRORE: " . $e->getMessage(), 'ERROR');
        }

        // Pausa tra progetti per non sovraccaricare l'API
        sleep(5);
    }

    // Aggiorna last_run
    setLastRunDate(date('Y-m-d'));

    // Statistiche finali
    logAiReport("=== RIEPILOGO ===");
    logAiReport("Report generati: {$reportsGenerated}");
    logAiReport("Report falliti: {$reportsFailed}");
    logAiReport("=== AI REPORT DISPATCHER END ===\n");
}

// Esegui
try {
    runAiReportDispatcher();
} catch (\Exception $e) {
    logAiReport("FATAL: " . $e->getMessage(), 'FATAL');
    exit(1);
}

exit(0);
