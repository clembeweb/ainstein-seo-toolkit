<?php
/**
 * Cron Job: Weekly Reports
 * Genera report settimanali AI per i progetti configurati
 *
 * Eseguire ogni giorno alle 08:00 (controlla se e il giorno giusto)
 * 0 8 * * * php /path/to/modules/seo-tracking/cron/weekly-reports.php
 */

require_once dirname(__DIR__, 3) . '/core/bootstrap.php';

use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\AiReportService;
use Modules\SeoTracking\Services\AlertService;

echo "[" . date('Y-m-d H:i:s') . "] Starting weekly reports...\n";

$currentDayOfWeek = (int) date('w'); // 0 = Sunday, 1 = Monday, etc.

$project = new Project();
$aiReportService = new AiReportService();
$alertService = new AlertService();

if (!$aiReportService->isConfigured()) {
    echo "ERROR: Claude API not configured. Exiting.\n";
    exit(1);
}

// Prendi progetti con report AI abilitato e giorno corrispondente
$projects = $project->getForWeeklyReport($currentDayOfWeek);

echo "Found " . count($projects) . " projects for weekly report (day: $currentDayOfWeek)\n";

foreach ($projects as $proj) {
    echo "\n--- Generating report for: {$proj['name']} ---\n";

    try {
        $result = $aiReportService->generateWeeklyDigest($proj['id'], $proj['user_id']);

        if ($result) {
            echo "Report generated: ID {$result['id']}\n";

            // Invia email digest se configurato
            $alertService->sendEmailDigest($proj['id']);
            echo "Email digest sent\n";
        } else {
            echo "Failed to generate report (check credits)\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: {$e->getMessage()}\n";
    }

    sleep(5); // Pausa tra report per rispettare rate limit API
}

echo "\n[" . date('Y-m-d H:i:s') . "] Weekly reports completed.\n";
