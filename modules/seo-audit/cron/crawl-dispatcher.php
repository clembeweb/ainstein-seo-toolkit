<?php
/**
 * Cron: Reset stuck crawl/WordPress import jobs + cleanup
 *
 * Eseguire ogni 5 minuti.
 * SiteGround: /usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/seo-audit/cron/crawl-dispatcher.php
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
use Modules\SeoAudit\Models\CrawlJob;
use Modules\SeoAudit\Models\CrawlSession;

$prefix = '[seo-audit-crawl-dispatcher]';

try {
    $jobModel = new CrawlJob();
    $sessionModel = new CrawlSession();

    // 1. Reset stuck jobs (running da oltre 30 minuti)
    $resetCount = $jobModel->resetStuckJobs(30);

    if ($resetCount > 0) {
        error_log("{$prefix} Reset {$resetCount} stuck crawl job(s)");

        // Aggiorna le sessioni corrispondenti ai job bloccati
        // I job appena resettati hanno status='error' e error_message che inizia con 'Timeout'
        $stuckSessions = Database::fetchAll(
            "SELECT DISTINCT j.session_id
             FROM sa_crawl_jobs j
             JOIN sa_crawl_sessions s ON s.id = j.session_id
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

    // 2. Pulizia job vecchi (mantieni ultimi 20 per progetto)
    $cleanedCount = $jobModel->cleanOldJobs(20);

    if ($cleanedCount > 0) {
        error_log("{$prefix} Eliminati {$cleanedCount} job vecchi");
    }

    // Log riepilogativo solo se ci sono state operazioni
    if ($resetCount > 0 || $cleanedCount > 0) {
        error_log("{$prefix} Completato: reset={$resetCount}, cleaned={$cleanedCount}");
    }

} catch (\Exception $e) {
    error_log("{$prefix} ERRORE FATALE: " . $e->getMessage());
    exit(1);
}

exit(0);
