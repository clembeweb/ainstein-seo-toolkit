<?php
/**
 * Cleanup AI Logs
 *
 * Eseguire quotidianamente tramite cron:
 * 0 3 * * * php /path/to/seo-toolkit/cron/cleanup-ai-logs.php >> /path/to/logs/cron.log 2>&1
 *
 * Elimina i log piu vecchi di 30 giorni dalla tabella ai_logs
 */

// Bootstrap CLI
require_once __DIR__ . '/bootstrap.php';

use Core\Database;

// Configurazione
$retentionDays = 30;
$logFile = BASE_PATH . '/storage/logs/ai-cleanup.log';

// Funzione log
function logMessage(string $message): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$message}\n";
    echo $line;

    // Crea directory se non esiste
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $line, FILE_APPEND);
}

try {
    logMessage("=== Inizio cleanup AI logs ===");

    // Conta log da eliminare
    $countQuery = "SELECT COUNT(*) as total FROM ai_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $count = Database::fetch($countQuery, [$retentionDays]);
    $toDelete = $count['total'] ?? 0;

    logMessage("Log piu vecchi di {$retentionDays} giorni: {$toDelete}");

    if ($toDelete > 0) {
        // Elimina in batch per evitare lock lunghi
        $batchSize = 1000;
        $totalDeleted = 0;

        do {
            $deleted = Database::execute(
                "DELETE FROM ai_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
                [$retentionDays, $batchSize]
            );
            $totalDeleted += $deleted;

            if ($deleted > 0) {
                logMessage("Eliminati batch: {$deleted} (totale: {$totalDeleted})");
            }

            // Pausa tra batch per non sovraccaricare
            if ($deleted === $batchSize) {
                usleep(100000); // 100ms
            }

        } while ($deleted === $batchSize);

        logMessage("Cleanup completato. Totale eliminati: {$totalDeleted}");
    } else {
        logMessage("Nessun log da eliminare");
    }

    // Statistiche post-cleanup
    $stats = Database::fetch("SELECT COUNT(*) as total, MIN(created_at) as oldest FROM ai_logs");
    $oldest = $stats['oldest'] ?? 'N/A';
    $total = $stats['total'] ?? 0;
    logMessage("Log rimanenti: {$total} | Log piu vecchio: {$oldest}");

    logMessage("=== Fine cleanup AI logs ===");
    logMessage("");

} catch (Exception $e) {
    logMessage("ERRORE: " . $e->getMessage());
    exit(1);
}

exit(0);
