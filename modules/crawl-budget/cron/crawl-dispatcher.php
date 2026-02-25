<?php
/**
 * Cron: Reset stuck crawl budget jobs + cleanup orphaned sessions
 *
 * Eseguire ogni 5 minuti.
 * SiteGround: /usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/crawl-budget/cron/crawl-dispatcher.php
 */

// Solo CLI
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}

// Timezone Italia
date_default_timezone_set('Europe/Rome');

// Bootstrap
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

set_time_limit(0);

use Core\Database;
use Modules\CrawlBudget\Models\CrawlJob;
use Modules\CrawlBudget\Models\CrawlSession;

$prefix = '[crawl-budget-dispatcher]';

try {
    $jobModel = new CrawlJob();
    $sessionModel = new CrawlSession();

    // 1. Reset stuck jobs (running da oltre 30 minuti)
    $resetCount = $jobModel->resetStuckJobs(30);

    if ($resetCount > 0) {
        error_log("{$prefix} Reset {$resetCount} stuck crawl job(s)");

        // Aggiorna sessioni corrispondenti ai job bloccati
        $stuckSessions = Database::fetchAll(
            "SELECT DISTINCT j.session_id
             FROM cb_crawl_jobs j
             JOIN cb_crawl_sessions s ON s.id = j.session_id
             WHERE j.status = 'error'
               AND j.error_message LIKE 'Timeout%'
               AND s.status IN ('pending', 'running', 'paused', 'stopping')"
        );

        $sessionsUpdated = 0;
        foreach ($stuckSessions as $row) {
            $sessionId = (int) $row['session_id'];
            $sessionModel->fail($sessionId, 'Timeout - job rimasto in esecuzione per oltre 30 minuti');
            $sessionsUpdated++;
        }

        if ($sessionsUpdated > 0) {
            error_log("{$prefix} Aggiornate {$sessionsUpdated} sessione/i a 'failed'");
        }
    }

    // 2. Reset orphaned sessions (running >30 min senza job attivo)
    $cutoffTime = date('Y-m-d H:i:s', time() - (30 * 60));
    $orphanedSessions = Database::fetchAll(
        "SELECT s.id, s.project_id
         FROM cb_crawl_sessions s
         WHERE s.status IN ('pending', 'running', 'paused', 'stopping')
           AND s.started_at < ?
           AND NOT EXISTS (
               SELECT 1 FROM cb_crawl_jobs j
               WHERE j.session_id = s.id AND j.status IN ('pending', 'running')
           )",
        [$cutoffTime]
    );

    $orphansFixed = 0;
    foreach ($orphanedSessions as $row) {
        $sessionId = (int) $row['id'];
        $projectId = (int) $row['project_id'];

        $sessionModel->fail($sessionId, 'Timeout - sessione orfana senza job attivo');

        // Reset anche lo stato del progetto
        Database::update('cb_projects', [
            'status' => 'idle',
            'current_session_id' => null,
        ], 'id = ? AND status = ?', [$projectId, 'crawling']);

        $orphansFixed++;
    }

    if ($orphansFixed > 0) {
        error_log("{$prefix} Reset {$orphansFixed} sessione/i orfana/e e relativi progetti");
    }

    // 3. Pulizia job vecchi (mantieni ultimi 20 per progetto)
    $cleanedCount = $jobModel->cleanOldJobs(20);

    if ($cleanedCount > 0) {
        error_log("{$prefix} Eliminati {$cleanedCount} job vecchi");
    }

    // Log riepilogativo solo se ci sono state operazioni
    if ($resetCount > 0 || $cleanedCount > 0 || $orphansFixed > 0) {
        error_log("{$prefix} Completato: reset={$resetCount}, orphans={$orphansFixed}, cleaned={$cleanedCount}");
    }

} catch (\Exception $e) {
    error_log("{$prefix} ERRORE FATALE: " . $e->getMessage());
    exit(1);
}

exit(0);
