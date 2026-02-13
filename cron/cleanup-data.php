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
    // --- Esistenti ---
    'sa_pages_html_days'        => 90,   // NULL html_content dopo 90 giorni
    'il_urls_raw_html_days'     => 30,   // NULL raw_html dopo 30 giorni
    'ga_runs_keep_per_project'  => 5,    // Mantieni ultimi 5 run per progetto
    'st_gsc_data_months'        => 16,   // Dati GSC: 16 mesi
    'st_positions_months'       => 16,   // Posizioni keyword: 16 mesi
    'st_alerts_days'            => 90,   // Alert letti/dismissati: 90 giorni
    'aic_jobs_days'             => 30,   // Process jobs completati: 30 giorni
    'st_rank_queue_days'        => 7,    // Rank queue completati: 7 giorni
    'ga_eval_queue_days'        => 30,   // Auto-eval queue completati: 30 giorni

    // --- Nuovi: seo-tracking ---
    'st_rank_checks_months'     => 6,    // Rank checks: 6 mesi
    'st_rank_jobs_days'         => 30,   // Rank jobs completati: 30 giorni
    'st_sync_log_days'          => 60,   // Sync log: 60 giorni

    // --- Nuovi: seo-audit ---
    'sa_gsc_performance_months' => 6,    // GSC performance: 6 mesi
    'sa_gsc_sync_log_days'      => 60,   // GSC sync log: 60 giorni
    'sa_activity_logs_days'     => 180,  // Activity logs: 180 giorni

    // --- Nuovi: internal-links ---
    'il_activity_logs_days'     => 180,  // Activity logs: 180 giorni

    // --- Nuovi: ai-content ---
    'aic_scrape_jobs_days'      => 7,    // Scrape jobs completati: 7 giorni
    'aic_sources_content_days'  => 90,   // Sources content_extracted: 90 giorni

    // --- Nuovi: keyword-research ---
    'kr_cache_days'             => 14,   // Cache keyword API: 14 giorni

    // --- Nuovi: content-creator ---
    'cc_jobs_days'              => 7,    // Jobs completati: 7 giorni
    'cc_operations_log_days'    => 90,   // Operations log: 90 giorni
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
// 10. st_ga4_data/daily + st_gsc_daily + st_keyword_revenue → per-project retention
// ============================================

function cleanupStProjectData(array $config): int
{
    if (!tableExists('st_projects')) {
        logMsg("  Tabella st_projects non trovata, skip");
        return 0;
    }

    $projects = Database::fetchAll("SELECT id, COALESCE(data_retention_months, 16) as retention FROM st_projects");
    if (empty($projects)) {
        logMsg("  Nessun progetto seo-tracking");
        return 0;
    }

    $tables = ['st_ga4_data', 'st_ga4_daily', 'st_gsc_daily', 'st_keyword_revenue'];
    $totalDeleted = 0;

    foreach ($projects as $p) {
        $projectId = (int) $p['id'];
        $retention = (int) $p['retention'];
        $cutoff = date('Y-m-d', strtotime("-{$retention} months"));

        foreach ($tables as $table) {
            if (!tableExists($table)) {
                continue;
            }

            $deleted = Database::execute(
                "DELETE FROM {$table} WHERE project_id = ? AND date < ?",
                [$projectId, $cutoff]
            );

            if ($deleted > 0) {
                logMsg("  {$table} progetto {$projectId}: {$deleted} righe > {$retention} mesi");
                $totalDeleted += $deleted;
            }
        }
    }

    return $totalDeleted;
}

// ============================================
// 11. st_rank_checks → DELETE > 6 mesi
// ============================================

function cleanupRankChecks(array $config, int $deleteBatchSize): int
{
    if (!tableExists('st_rank_checks')) {
        logMsg("  Tabella st_rank_checks non trovata, skip");
        return 0;
    }

    $months = $config['st_rank_checks_months'];
    $cutoff = date('Y-m-d', strtotime("-{$months} months"));

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM st_rank_checks WHERE checked_at < ? LIMIT ?",
            [$cutoff, $deleteBatchSize]
        );
        $totalDeleted += $deleted;

        if ($deleted === $deleteBatchSize) {
            usleep(100000);
        }
    } while ($deleted === $deleteBatchSize);

    return $totalDeleted;
}

// ============================================
// 12. st_rank_jobs → DELETE completati > 30 giorni
// ============================================

function cleanupRankJobs(array $config, int $deleteBatchSize): int
{
    if (!tableExists('st_rank_jobs')) {
        logMsg("  Tabella st_rank_jobs non trovata, skip");
        return 0;
    }

    $days = $config['st_rank_jobs_days'];

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM st_rank_jobs WHERE status IN ('completed', 'error', 'cancelled') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
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
// 13. st_sync_log → DELETE > 60 giorni
// ============================================

function cleanupSyncLog(array $config, int $deleteBatchSize): int
{
    if (!tableExists('st_sync_log')) {
        logMsg("  Tabella st_sync_log non trovata, skip");
        return 0;
    }

    $days = $config['st_sync_log_days'];

    $deleted = Database::execute(
        "DELETE FROM st_sync_log WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );

    return $deleted;
}

// ============================================
// 14. sa_gsc_performance → DELETE > 6 mesi
// ============================================

function cleanupSaGscPerformance(array $config, int $deleteBatchSize): int
{
    if (!tableExists('sa_gsc_performance')) {
        logMsg("  Tabella sa_gsc_performance non trovata, skip");
        return 0;
    }

    $months = $config['sa_gsc_performance_months'];
    $cutoff = date('Y-m-d', strtotime("-{$months} months"));

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM sa_gsc_performance WHERE date < ? LIMIT ?",
            [$cutoff, $deleteBatchSize]
        );
        $totalDeleted += $deleted;

        if ($deleted === $deleteBatchSize) {
            usleep(100000);
        }
    } while ($deleted === $deleteBatchSize);

    return $totalDeleted;
}

// ============================================
// 15. sa_gsc_sync_log → DELETE > 60 giorni
// ============================================

function cleanupSaGscSyncLog(array $config): int
{
    if (!tableExists('sa_gsc_sync_log')) {
        logMsg("  Tabella sa_gsc_sync_log non trovata, skip");
        return 0;
    }

    $days = $config['sa_gsc_sync_log_days'];

    return Database::execute(
        "DELETE FROM sa_gsc_sync_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
}

// ============================================
// 16. sa_activity_logs → DELETE > 180 giorni
// ============================================

function cleanupSaActivityLogs(array $config): int
{
    if (!tableExists('sa_activity_logs')) {
        logMsg("  Tabella sa_activity_logs non trovata, skip");
        return 0;
    }

    $days = $config['sa_activity_logs_days'];

    return Database::execute(
        "DELETE FROM sa_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
}

// ============================================
// 17. il_activity_logs → DELETE > 180 giorni
// ============================================

function cleanupIlActivityLogs(array $config): int
{
    if (!tableExists('il_activity_logs')) {
        logMsg("  Tabella il_activity_logs non trovata, skip");
        return 0;
    }

    $days = $config['il_activity_logs_days'];

    return Database::execute(
        "DELETE FROM il_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
}

// ============================================
// 18. aic_scrape_jobs → DELETE completati > 7 giorni
// ============================================

function cleanupScrapeJobs(array $config, int $deleteBatchSize): int
{
    if (!tableExists('aic_scrape_jobs')) {
        logMsg("  Tabella aic_scrape_jobs non trovata, skip");
        return 0;
    }

    $days = $config['aic_scrape_jobs_days'];

    $totalDeleted = 0;
    do {
        $deleted = Database::execute(
            "DELETE FROM aic_scrape_jobs WHERE status IN ('completed', 'error') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT ?",
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
// 19. aic_sources.content_extracted → SET NULL su articoli pubblicati > 90 giorni
// ============================================

function cleanupAicSourcesContent(array $config, int $batchSize): int
{
    if (!tableExists('aic_sources') || !tableExists('aic_articles')) {
        logMsg("  Tabelle aic_sources/aic_articles non trovate, skip");
        return 0;
    }

    $days = $config['aic_sources_content_days'];

    $count = Database::fetch(
        "SELECT COUNT(*) as total FROM aic_sources s
         JOIN aic_articles a ON s.article_id = a.id
         WHERE s.content_extracted IS NOT NULL
         AND a.status = 'published'
         AND s.scraped_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
    $toClean = $count['total'] ?? 0;

    if ($toClean === 0) {
        logMsg("  Nessuna source content da pulire");
        return 0;
    }

    logMsg("  Sources con content > {$days} giorni (articoli pubblicati): {$toClean}");

    $totalCleaned = 0;
    do {
        $affected = Database::execute(
            "UPDATE aic_sources s
             JOIN aic_articles a ON s.article_id = a.id
             SET s.content_extracted = NULL
             WHERE s.content_extracted IS NOT NULL
             AND a.status = 'published'
             AND s.scraped_at < DATE_SUB(NOW(), INTERVAL ? DAY)
             LIMIT ?",
            [$days, $batchSize]
        );
        $totalCleaned += $affected;

        if ($affected === $batchSize) {
            usleep(200000);
        }
    } while ($affected === $batchSize);

    return $totalCleaned;
}

// ============================================
// 20. kr_keyword_cache → DELETE expired > 14 giorni
// ============================================

function cleanupKrCache(array $config): int
{
    if (!tableExists('kr_keyword_cache')) {
        logMsg("  Tabella kr_keyword_cache non trovata, skip");
        return 0;
    }

    $days = $config['kr_cache_days'];

    return Database::execute(
        "DELETE FROM kr_keyword_cache WHERE cached_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days]
    );
}

// ============================================
// 21. cc_jobs → DELETE completed/cancelled > 7 giorni
// ============================================

function cleanupCcJobs(array $config, int $batchSize): int
{
    if (!tableExists('cc_jobs')) {
        logMsg("  Tabella cc_jobs non trovata, skip");
        return 0;
    }

    $days = $config['cc_jobs_days'];

    return batchDelete(
        'cc_jobs',
        "status IN ('completed', 'error', 'cancelled') AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days],
        $batchSize
    );
}

// ============================================
// 22. cc_operations_log → DELETE > 90 giorni
// ============================================

function cleanupCcOperationsLog(array $config, int $batchSize): int
{
    if (!tableExists('cc_operations_log')) {
        logMsg("  Tabella cc_operations_log non trovata, skip");
        return 0;
    }

    $days = $config['cc_operations_log_days'];

    return batchDelete(
        'cc_operations_log',
        "created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$days],
        $batchSize
    );
}

// ============================================
// ESECUZIONE PRINCIPALE
// ============================================

logMsg("========================================");
logMsg("=== Inizio cleanup dati ===");
logMsg("========================================");

$sections = [
    // --- Esistenti (1-9) ---
    ['sa_pages (html_content)',      'cleanupSaPages',          [$config, $batchSize]],
    ['il_urls (raw_html)',           'cleanupIlUrls',           [$config, $batchSize]],
    ['ga_* (run vecchi)',            'cleanupOldRuns',           [$config]],
    ['st_gsc_data (> 16 mesi)',     'cleanupGscData',           [$config]],
    ['st_keyword_positions',         'cleanupKeywordPositions',  [$config]],
    ['st_alerts (letti > 90gg)',    'cleanupAlerts',            [$config]],
    ['aic_process_jobs (> 30gg)',   'cleanupProcessJobs',       [$config, $deleteBatchSize]],
    ['st_rank_queue (> 7gg)',       'cleanupRankQueue',         [$config, $deleteBatchSize]],
    ['ga_auto_eval_queue (> 30gg)', 'cleanupAutoEvalQueue',     [$config, $deleteBatchSize]],

    // --- Nuovi (10-20) ---
    ['st_ga4/gsc/revenue (retention)', 'cleanupStProjectData',    [$config]],
    ['st_rank_checks (> 6 mesi)',      'cleanupRankChecks',       [$config, $deleteBatchSize]],
    ['st_rank_jobs (> 30gg)',          'cleanupRankJobs',         [$config, $deleteBatchSize]],
    ['st_sync_log (> 60gg)',           'cleanupSyncLog',          [$config, $deleteBatchSize]],
    ['sa_gsc_performance (> 6 mesi)',  'cleanupSaGscPerformance', [$config, $deleteBatchSize]],
    ['sa_gsc_sync_log (> 60gg)',       'cleanupSaGscSyncLog',     [$config]],
    ['sa_activity_logs (> 180gg)',      'cleanupSaActivityLogs',   [$config]],
    ['il_activity_logs (> 180gg)',      'cleanupIlActivityLogs',   [$config]],
    ['aic_scrape_jobs (> 7gg)',        'cleanupScrapeJobs',       [$config, $deleteBatchSize]],
    ['aic_sources (content > 90gg)',   'cleanupAicSourcesContent', [$config, $batchSize]],
    ['kr_keyword_cache (> 14gg)',      'cleanupKrCache',          [$config]],

    // --- Nuovi (21-22): content-creator ---
    ['cc_jobs (> 7gg)',               'cleanupCcJobs',           [$config, $deleteBatchSize]],
    ['cc_operations_log (> 90gg)',    'cleanupCcOperationsLog',  [$config, $deleteBatchSize]],
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
