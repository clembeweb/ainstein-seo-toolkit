<?php
/**
 * Cleanup API Logs
 *
 * Eseguire quotidianamente tramite cron:
 * 0 4 * * * php /path/to/seo-toolkit/cron/cleanup-api-logs.php >> /path/to/logs/cron.log 2>&1
 *
 * Elimina i log più vecchi di 30 giorni dalla tabella api_logs
 */

// Bootstrap CLI
require_once __DIR__ . '/bootstrap.php';

use Core\Database;

// Configurazione
$retentionDays = 30;
$logFile = BASE_PATH . '/storage/logs/api-cleanup.log';
$batchSize = 1000;

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
    logMessage("=== Inizio cleanup API logs ===");

    // Conta log da eliminare
    $countQuery = "SELECT COUNT(*) as total FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $count = Database::fetch($countQuery, [$retentionDays]);
    $toDelete = $count['total'] ?? 0;

    logMessage("Log più vecchi di {$retentionDays} giorni: {$toDelete}");

    if ($toDelete > 0) {
        // Elimina in batch per evitare lock lunghi
        $totalDeleted = 0;

        do {
            $deleted = Database::execute(
                "DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
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
    $stats = Database::fetch("SELECT COUNT(*) as total, MIN(created_at) as oldest FROM api_logs");
    $oldest = $stats['oldest'] ?? 'N/A';
    $total = $stats['total'] ?? 0;
    logMessage("Log rimanenti: {$total} | Log più vecchio: {$oldest}");

    // Statistiche per provider
    $byProvider = Database::fetchAll(
        "SELECT provider, COUNT(*) as count FROM api_logs GROUP BY provider ORDER BY count DESC"
    );
    if (!empty($byProvider)) {
        logMessage("Breakdown per provider:");
        foreach ($byProvider as $row) {
            logMessage("  - {$row['provider']}: {$row['count']}");
        }
    }

    logMessage("=== Fine cleanup API logs ===");
    logMessage("");

} catch (Exception $e) {
    logMessage("ERRORE: " . $e->getMessage());
    exit(1);
}

exit(0);
