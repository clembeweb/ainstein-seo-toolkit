<?php
/**
 * Cleanup Dati Obsoleti - Tutti i moduli
 *
 * Eseguire quotidianamente tramite cron:
 * 0 2 * * * php /path/to/seo-toolkit/cron/cleanup-data.php >> /path/to/logs/cron.log 2>&1
 *
 * Pulisce dati vecchi da tutti i moduli per mantenere il DB sotto controllo.
 * Ogni sezione è indipendente: se una fallisce, le altre continuano.
 */

// Bootstrap CLI
require_once __DIR__ . '/bootstrap.php';

use Core\Database;

// ============================================
// CONFIGURAZIONE RETENTION
// ============================================

$config = [
    'sa_pages_html_days'        => 90,   // NULL html_content dopo 90 giorni
    'il_urls_raw_html_days'     => 30,   // NULL raw_html dopo 30 giorni
    'ga_runs_keep_per_project'  => 5,    // Mantieni ultimi 5 run per progetto
    'st_gsc_data_months'        => 16,   // Dati GSC: 16 mesi
    'st_positions_months'       => 16,   // Posizioni keyword: 16 mesi
    'st_alerts_days'            => 90,   // Alert letti/dismissati: 90 giorni
    'aic_jobs_days'             => 30,   // Process jobs completati: 30 giorni
    'st_rank_queue_days'        => 7,    // Rank queue completati: 7 giorni
    'ga_eval_queue_days'        => 30,   // Auto-eval queue completati: 30 giorni
];

$logFile = BASE_PATH . '/storage/logs/data-cleanup.log';
$batchSize = 50; // Per UPDATE su colonne LONGTEXT (righe pesanti)
$deleteBatchSize = 1000; // Per DELETE normali

$totalStats = [];

// ============================================
// FUNZIONI UTILITY
// ============================================

function logMsg(string $message, string $level = 'INFO'): void
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

function tableExists(string $table): bool
{
    try {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        );
        return ($result['cnt'] ?? 0) > 0;
    } catch (\Exception $e) {
        return false;
    }
}

// ============================================
// 1. sa_pages.html_content → SET NULL
// ============================================

function cleanupSaPages(array $config, int $batchSize): int
{
    if (!tableExists('sa_pages')) {
        logMsg("  Tabella sa_pages non trovata, skip");
        return 0;
    }

    $days = $config['sa_pages_html_days'];
    $count = Database::fetch(
        "SELECT COUNT(*) as total FROM sa_pages WHERE html_content IS NOT NULL AND crawled_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
    $toClean = $count['total'] ?? 0;

    if ($toClean === 0) {
        logMsg("  Nessuna pagina con html_content da pulire");
        return 0;
    }

    logMsg("  Pagine con html_content > {$days} giorni: {$toClean}");

    $totalCleaned = 0;
    do {
        $affected = Database::execute(
            "UPDATE sa_pages SET html_content = NULL WHERE html_content IS NOT NULL AND crawled_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
            [$days, $batchSize]
        );
        $totalCleaned += $affected;

        if ($affected > 0) {
            logMsg("  Batch: {$affected} pagine pulite (totale: {$totalCleaned})");
        }

        if ($affected === $batchSize) {
            usleep(200000); // 200ms - pause più lunga per UPDATE su LONGTEXT
        }
    } while ($affected === $batchSize);

    return $totalCleaned;
}

// ============================================
// 2. il_urls.raw_html → SET NULL
// ============================================

function cleanupIlUrls(array $config, int $batchSize): int
{
    if (!tableExists('il_urls')) {
        logMsg("  Tabella il_urls non trovata, skip");
        return 0;
    }

    $days = $config['il_urls_raw_html_days'];
    $count = Database::fetch(
        "SELECT COUNT(*) as total FROM il_urls WHERE raw_html IS NOT NULL AND status = 'scraped' AND scraped_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
    $toClean = $count['total'] ?? 0;

    if ($toClean === 0) {
        logMsg("  Nessun URL con raw_html da pulire");
        return 0;
    }

    logMsg("  URL con raw_html > {$days} giorni: {$toClean}");

    $totalCleaned = 0;
    do {
        $affected = Database::execute(
            "UPDATE il_urls SET raw_html = NULL WHERE raw_html IS NOT NULL AND status = 'scraped' AND scraped_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
            [$days, $batchSize]
        );
        $totalCleaned += $affected;

        if ($affected > 0) {
            logMsg("  Batch: {$affected} URL puliti (totale: {$totalCleaned})");
        }

        if ($affected === $batchSize) {
            usleep(200000);
        }
    } while ($affected === $batchSize);

    return $totalCleaned;
}

// ============================================
// 3. ga_* → DELETE run vecchi
// ============================================

function cleanupOldRuns(array $config): int
{
    if (!tableExists('ga_script_runs')) {
        logMsg("  Tabella ga_script_runs non trovata, skip");
        return 0;
    }

    $keepPerProject = $config['ga_runs_keep_per_project'];
    $totalDeleted = 0;

    // Trova tutti i progetti con script runs
    $projects = Database::fetchAll("SELECT DISTINCT project_id FROM ga_script_runs");

    if (empty($projects)) {
        logMsg("  Nessun progetto con script runs");
        return 0;
    }

    foreach ($projects as $proj) {
        $projectId = (int) $proj['project_id'];

        // Trova gli ID dei run da MANTENERE (ultimi N)
        $keepRuns = Database::fetchAll(
            "SELECT id FROM ga_script_runs WHERE project_id = ? ORDER BY created_at DESC LIMIT ?",
            [$projectId, $keepPerProject]
        );
        $keepIds = array_column($keepRuns, 'id');

        if (empty($keepIds)) {
            continue;
        }

        // Conta run da eliminare
        $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
        $params = array_merge([$projectId], $keepIds);

        $count = Database::fetch(
            "SELECT COUNT(*) as total FROM ga_script_runs WHERE project_id = ? AND id NOT IN ({$placeholders})",
            $params
        );
        $toDelete = $count['total'] ?? 0;

        if ($toDelete === 0) {
            continue;
        }

        // Trova ID run da eliminare
        $oldRuns = Database::fetchAll(
            "SELECT id FROM ga_script_runs WHERE project_id = ? AND id NOT IN ({$placeholders})",
            $params
        );
        $oldRunIds = array_column($oldRuns, 'id');
        $oldPlaceholders = implode(',', array_fill(0, count($oldRunIds), '?'));

        logMsg("  Progetto {$projectId}: {$toDelete} run vecchi da eliminare (mantieni ultimi {$keepPerProject})");

        // Elimina dati collegati ai run vecchi
        $tables = ['ga_search_terms', 'ga_ad_groups', 'ga_campaigns', 'ga_ads', 'ga_extensions', 'ga_campaign_evaluations', 'ga_auto_eval_queue'];

        foreach ($tables as $table) {
            if (!tableExists($table)) {
                continue;
            }

            $deleted = Database::execute(
                "DELETE FROM {$table} WHERE run_id IN ({$oldPlaceholders})",
                $oldRunIds
            );

            if ($deleted > 0) {
                logMsg("    {$table}: {$deleted} righe eliminate");
                $totalDeleted += $deleted;
            }
        }

        // Elimina i run stessi
        $deletedRuns = Database::execute(
            "DELETE FROM ga_script_runs WHERE id IN ({$oldPlaceholders})",
            $oldRunIds
        );
        logMsg("    ga_script_runs: {$deletedRuns} run eliminati");
        $totalDeleted += $deletedRuns;
    }

    // Pulisci anche dati orfani (run_id che punta a run inesistenti, ma non NULL)
    $orphanTables = ['ga_search_terms', 'ga_ad_groups', 'ga_campaigns', 'ga_ads', 'ga_extensions'];
    foreach ($orphanTables as $table) {
        if (!tableExists($table)) {
            continue;
        }

        $orphans = Database::execute(
            "DELETE FROM {$table} WHERE run_id IS NOT NULL AND run_id NOT IN (SELECT id FROM ga_script_runs)"
        );

        if ($orphans > 0) {
            logMsg("    {$table}: {$orphans} righe orfane eliminate");
            $totalDeleted += $orphans;
        }
    }

    return $totalDeleted;
}

// ============================================
// 4. st_gsc_data → DELETE > 16 mesi
// ============================================

function cleanupGscData(array $config): int
{
    if (!tableExists('st_gsc_data')) {
        logMsg("  Tabella st_gsc_data non trovata, skip");
        return 0;
    }

    $months = $config['st_gsc_data_months'];
    $cutoffDate = date('Y-m-d', strtotime("-{$months} months"));
    $totalDeleted = 0;

    $projects = Database::fetchAll("SELECT DISTINCT project_id FROM st_gsc_data WHERE date < ?", [$cutoffDate]);

    if (empty($projects)) {
        logMsg("  Nessun dato GSC > {$months} mesi");
        return 0;
    }

    foreach ($projects as $proj) {
        $projectId = (int) $proj['project_id'];
        $deleted = Database::execute(
            "DELETE FROM st_gsc_data WHERE project_id = ? AND date < ?",
            [$projectId, $cutoffDate]
        );

        if ($deleted > 0) {
            logMsg("  Progetto {$projectId}: {$deleted} righe GSC > {$months} mesi eliminate");
            $totalDeleted += $deleted;
        }
    }

    return $totalDeleted;
}

// ============================================
// 5. st_keyword_positions → DELETE > 16 mesi
// ============================================

function cleanupKeywordPositions(array $config): int
{
    if (!tableExists('st_keyword_positions')) {
        logMsg("  Tabella st_keyword_positions non trovata, skip");
        return 0;
    }

    $months = $config['st_positions_months'];
    $cutoffDate = date('Y-m-d', strtotime("-{$months} months"));

    $count = Database::fetch(
        "SELECT COUNT(*) as total FROM st_keyword_positions WHERE date < ?",
        [$cutoffDate]
    );
    $toDelete = $count['total'] ?? 0;

    if ($toDelete === 0) {
        logMsg("  Nessuna posizione keyword > {$months} mesi");
        return 0;
    }

    logMsg("  Posizioni keyword > {$months} mesi: {$toDelete}");

    $deleted = Database::execute(
        "DELETE FROM st_keyword_positions WHERE date < ?",
        [$cutoffDate]
    );

    return $deleted;
}

// ============================================
// 6. st_alerts → DELETE letti/dismissati > 90 giorni
// ============================================

function cleanupAlerts(array $config): int
{
    if (!tableExists('st_alerts')) {
        logMsg("  Tabella st_alerts non trovata, skip");
        return 0;
    }

    $days = $config['st_alerts_days'];
    $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));

    $deleted = Database::execute(
        "DELETE FROM st_alerts WHERE created_at < ? AND status IN ('read', 'dismissed', 'actioned')",
        [$cutoffDate]
    );

    return $deleted;
}

// ============================================
// 7. aic_process_jobs → DELETE completati > 30 giorni
// ============================================

function cleanupProcessJobs(array $config, int $deleteBatchSize): int
{
    if (!tableExists('aic_process_jobs')) {
        logMsg("  Tabella aic_process_jobs non trovata, skip");
        return 0;
    }

    $days = $config['aic_jobs_days'];

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM aic_process_jobs WHERE status IN ('completed', 'error', 'cancelled') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
            [$days, $deleteBatchSize]
        );
        $totalDeleted += $deleted;

        if ($deleted === $deleteBatchSize) {
            usleep(100000);
        }
    } while ($deleted === $deleteBatchSize);

    return $totalDeleted;
}

// ============================================
// 8. st_rank_queue → DELETE completati > 7 giorni
// ============================================

function cleanupRankQueue(array $config, int $deleteBatchSize): int
{
    if (!tableExists('st_rank_queue')) {
        logMsg("  Tabella st_rank_queue non trovata, skip");
        return 0;
    }

    $days = $config['st_rank_queue_days'];

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM st_rank_queue WHERE status IN ('completed', 'error') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
            [$days, $deleteBatchSize]
        );
        $totalDeleted += $deleted;

        if ($deleted === $deleteBatchSize) {
            usleep(100000);
        }
    } while ($deleted === $deleteBatchSize);

    return $totalDeleted;
}

// ============================================
// 9. ga_auto_eval_queue → DELETE completati > 30 giorni
// ============================================

function cleanupAutoEvalQueue(array $config, int $deleteBatchSize): int
{
    if (!tableExists('ga_auto_eval_queue')) {
        logMsg("  Tabella ga_auto_eval_queue non trovata, skip");
        return 0;
    }

    $days = $config['ga_eval_queue_days'];

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM ga_auto_eval_queue WHERE status IN ('completed', 'skipped', 'error') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
            [$days, $deleteBatchSize]
        );
        $totalDeleted += $deleted;

        if ($deleted === $deleteBatchSize) {
            usleep(100000);
        }
    } while ($deleted === $deleteBatchSize);

    return $totalDeleted;
}

// ============================================
// ESECUZIONE PRINCIPALE
// ============================================

logMsg("========================================");
logMsg("=== Inizio cleanup dati ===");
logMsg("========================================");

$sections = [
    ['sa_pages (html_content)',      'cleanupSaPages',          [$config, $batchSize]],
    ['il_urls (raw_html)',           'cleanupIlUrls',           [$config, $batchSize]],
    ['ga_* (run vecchi)',            'cleanupOldRuns',           [$config]],
    ['st_gsc_data (> 16 mesi)',     'cleanupGscData',           [$config]],
    ['st_keyword_positions',         'cleanupKeywordPositions',  [$config]],
    ['st_alerts (letti > 90gg)',    'cleanupAlerts',            [$config]],
    ['aic_process_jobs (> 30gg)',   'cleanupProcessJobs',       [$config, $deleteBatchSize]],
    ['st_rank_queue (> 7gg)',       'cleanupRankQueue',         [$config, $deleteBatchSize]],
    ['ga_auto_eval_queue (> 30gg)', 'cleanupAutoEvalQueue',     [$config, $deleteBatchSize]],
];

foreach ($sections as [$label, $func, $args]) {
    logMsg("--- {$label} ---");
    try {
        $result = call_user_func_array($func, $args);
        $totalStats[$label] = $result;
        if ($result > 0) {
            logMsg("  Totale: {$result} righe processate");
        }
    } catch (\Exception $e) {
        logMsg("  ERRORE: " . $e->getMessage(), 'ERROR');
        $totalStats[$label] = 'ERRORE';
    }
    logMsg("");
}

// ============================================
// STATISTICHE FINALI
// ============================================

logMsg("=== Riepilogo ===");
foreach ($totalStats as $label => $count) {
    $status = is_int($count) ? ($count > 0 ? "{$count} righe" : "nessuna modifica") : $count;
    logMsg("  {$label}: {$status}");
}

// Dimensione DB
try {
    $dbSize = Database::fetch(
        "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
         FROM information_schema.tables
         WHERE table_schema = DATABASE()"
    );
    $sizeMb = $dbSize['size_mb'] ?? 'N/A';
    logMsg("  Dimensione DB totale: {$sizeMb} MB");

    // Top 5 tabelle
    $topTables = Database::fetchAll(
        "SELECT table_name AS tbl_name,
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
         ORDER BY (data_length + index_length) DESC
         LIMIT 5"
    );
    if (!empty($topTables)) {
        logMsg("  Top 5 tabelle:");
        foreach ($topTables as $t) {
            logMsg("    - {$t['tbl_name']}: {$t['size_mb']} MB");
        }
    }
} catch (\Exception $e) {
    logMsg("  Errore lettura dimensione DB: " . $e->getMessage(), 'ERROR');
}

logMsg("========================================");
logMsg("=== Fine cleanup dati ===");
logMsg("========================================");
logMsg("");

exit(0);
