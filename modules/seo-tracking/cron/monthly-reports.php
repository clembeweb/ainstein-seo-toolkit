<?php
/**
 * Cron Job: Monthly Executive Reports
 * Genera report mensili executive per i progetti configurati
 *
 * Eseguire ogni giorno alle 09:00 (controlla se e il giorno giusto)
 * 0 9 * * * php /path/to/modules/seo-tracking/cron/monthly-reports.php
 */

require_once dirname(__DIR__, 3) . '/core/bootstrap.php';

use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Services\AiReportService;

echo "[" . date('Y-m-d H:i:s') . "] Starting monthly reports...\n";

$currentDayOfMonth = (int) date('j'); // 1-31

$project = new Project();
$aiReportService = new AiReportService();

if (!$aiReportService->isConfigured()) {
    echo "ERROR: Claude API not configured. Exiting.\n";
    exit(1);
}

// Prendi progetti con report AI abilitato e giorno corrispondente
$projects = $project->getForMonthlyReport($currentDayOfMonth);

echo "Found " . count($projects) . " projects for monthly report (day: $currentDayOfMonth)\n";

foreach ($projects as $proj) {
    echo "\n--- Generating executive report for: {$proj['name']} ---\n";

    try {
        $result = $aiReportService->generateMonthlyExecutive($proj['id'], $proj['user_id']);

        if ($result) {
            echo "Report generated: ID {$result['id']}\n";
        } else {
            echo "Failed to generate report (check credits)\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: {$e->getMessage()}\n";
    }

    sleep(10); // Pausa piu lunga per report executive
}

echo "\n[" . date('Y-m-d H:i:s') . "] Monthly reports completed.\n";
