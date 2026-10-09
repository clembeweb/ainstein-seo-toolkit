<?php
// Ricalcola il piano degli interventi di un run SENZA rifare il judge (ADR-014): usa risposte e verdetti gia' salvati,
// rifa' rimozioni, contro-contenuti e il raggruppamento AI degli articoli gap (una chiamata, ~0,05 $).
// Stato e schede gia' generate si conservano (chiave stabile). Run: php modules/ai-reputation/scripts/rebuild-plan.php <runId>
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Modules\AiReputation\Models\Analysis;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Models\Run;
use Modules\AiReputation\Services\ReportBuilderService;

$runId = (int) ($argv[1] ?? 0);
if ($runId <= 0) {
    fwrite(STDERR, "Uso: php modules/ai-reputation/scripts/rebuild-plan.php <runId>\n");
    exit(1);
}
$run = (new Run())->find($runId);
if (!$run) {
    fwrite(STDERR, "Run {$runId} non trovato\n");
    exit(1);
}
$project = Database::fetch("SELECT * FROM ar_projects WHERE id = ?", [(int) $run['project_id']]);
$responses = (new Run())->responses($runId);
$analyses = (new Analysis())->byRun($runId);
$briefsBefore = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = ? AND brief IS NOT NULL", [$runId]);
$t = microtime(true);
$built = (new ReportBuilderService())->persist($runId, (int) $run['project_id'], $project, $responses, $analyses, Project::ENGINE_LABELS);
$briefsAfter = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = ? AND brief IS NOT NULL", [$runId]);
$counts = [];
foreach ($built['actions'] as $a) {
    $counts[$a['type']] = ($counts[$a['type']] ?? 0) + 1;
}
printf("Run %d: piano ricalcolato in %.1fs — %s — schede conservate: %d su %d\n", $runId, microtime(true) - $t, json_encode($counts), $briefsAfter, $briefsBefore);
foreach ($built['actions'] as $a) {
    if (in_array($a['type'], ['gap_article', 'gap_pending'], true)) {
        echo "  [{$a['type']}] {$a['title']}\n";
    }
}
