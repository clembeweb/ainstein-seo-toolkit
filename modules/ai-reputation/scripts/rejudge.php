<?php
// Rifà il giudizio di alcune risposte di un run (per provare il judge su casi veri senza rifare tutto il run).
// Una chiamata AI per risposta, nessun credito addebitato. Con --dry non salva nulla e confronta col giudizio salvato.
// Uso: php modules/ai-reputation/scripts/rejudge.php <runId> <responseId,responseId,...|rep|negative|all> [--dry] [--effort=low|medium|high] [--model=claude-sonnet-5-5] [--quiet]
//   rep      = tutte le risposte alle domande sulla reputazione
//   negative = tutte le risposte oggi giudicate negative
//   all      = tutte le risposte raccolte
// Dopo un salvataggio: php modules/ai-reputation/scripts/rebuild-plan.php <runId> per rifare il piano.
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Modules\AiReputation\Models\Analysis;
use Modules\AiReputation\Services\JudgeService;

$runId = (int) ($argv[1] ?? 0);
$which = (string) ($argv[2] ?? '');
$dry = in_array('--dry', $argv, true);
$quiet = in_array('--quiet', $argv, true);
$effort = null;
$model = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--effort=')) {
        $effort = substr($a, 9);
    }
    if (str_starts_with($a, '--model=')) {
        $model = substr($a, 8);
    }
}
if ($runId <= 0 || $which === '') {
    fwrite(STDERR, "Uso: php modules/ai-reputation/scripts/rejudge.php <runId> <ids|rep|negative|all> [--dry] [--effort=low] [--model=...] [--quiet]\n");
    exit(1);
}
$run = Database::fetch("SELECT * FROM ar_runs WHERE id = ?", [$runId]);
if (!$run) {
    fwrite(STDERR, "Run {$runId} non trovato\n");
    exit(1);
}
$project = Database::fetch("SELECT * FROM ar_projects WHERE id = ?", [(int) $run['project_id']]);

$sql = "SELECT r.*, a.verdict AS old_verdict, a.negative AS old_negative, a.brand_mentioned AS old_mentioned,
               p.text AS prompt_text, p.cluster AS prompt_cluster
        FROM ar_responses r JOIN ar_prompts p ON p.id = r.prompt_id
        LEFT JOIN ar_analyses a ON a.response_id = r.id WHERE r.run_id = ? AND r.status = 'ok'";
$params = [$runId];
if ($which === 'rep') {
    $sql .= " AND p.cluster = 'rep'";
} elseif ($which === 'negative') {
    $sql .= " AND a.negative = 1";
} elseif ($which !== 'all') {
    $ids = array_filter(array_map('intval', explode(',', $which)));
    $sql .= ' AND r.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
    $params = array_merge($params, $ids);
}
$items = Database::fetchAll($sql . ' ORDER BY r.id', $params);

$judge = new JudgeService();
if ($effort !== null) {
    $judge->effort = $effort ?: null;
}
if ($model) {
    $judge->model = $model;
}
$analysis = new Analysis();
$logFrom = Database::fetchColumn("SELECT COALESCE(MAX(id), 0) FROM ai_logs");
$t = microtime(true);
$stats = ['judged' => 0, 'errors' => 0, 'verdict_same' => 0, 'negative_same' => 0, 'mention_same' => 0, 'compared' => 0, 'doubts' => 0, 'neg_urls' => 0];
printf("Run %d: %d risposte, modello %s, effort %s%s\n\n", $runId, count($items), $judge->model ?? $judge->model(), $judge->effort ?? 'default', $dry ? ', PROVA (non salva)' : '');
foreach ($items as $item) {
    $item['citations'] = json_decode((string) $item['citations'], true) ?: [];
    $item['sources_read'] = json_decode((string) $item['sources_read'], true) ?: [];
    $v = $judge->judge((int) $run['user_id'], $project, $item);
    Database::reconnect();
    if (isset($v['error'])) {
        $stats['errors']++;
        echo "#{$item['id']} ERRORE: {$v['error']}\n";
        continue;
    }
    $stats['judged']++;
    $stats['doubts'] += count($v['doubts']);
    $stats['neg_urls'] += count($v['negative_urls']);
    $diff = '';
    if ($item['old_verdict'] !== null) {
        $stats['compared']++;
        $stats['verdict_same'] += $item['old_verdict'] === $v['verdict'] ? 1 : 0;
        $stats['negative_same'] += (int) $item['old_negative'] === (int) $v['negative'] ? 1 : 0;
        $stats['mention_same'] += (int) $item['old_mentioned'] === (int) $v['brand_mentioned'] ? 1 : 0;
        if ($item['old_verdict'] !== $v['verdict'] || (int) $item['old_negative'] !== (int) $v['negative']) {
            $diff = "   ≠ prima: {$item['old_verdict']}" . ((int) $item['old_negative'] ? ' (negativa)' : '');
        }
    }
    if ($quiet && $diff === '' && !$v['doubts'] && !$v['negative_urls']) {
        continue;
    }
    printf("#%d [%s] %s → %s%s%s\n", $item['id'], $item['engine'], mb_strimwidth($item['prompt_text'], 0, 60, '…'), $v['verdict'], $v['negative'] ? ' (negativa)' : '', $diff);
    foreach ($v['negative_urls'] as $u) {
        echo "    fonte negativa: {$u}\n";
    }
    foreach ($v['doubts'] as $d) {
        echo "    dubbio: {$d['doubt']}\n      → {$d['fix']}\n";
    }
    if (!$dry) {
        $analysis->save((int) $item['id'], $runId, (int) $run['project_id'], $v);
    }
}
$cost = (float) Database::fetchColumn("SELECT COALESCE(SUM(estimated_cost), 0) FROM ai_logs WHERE id > ? AND module_slug = 'ai-reputation'", [$logFrom]);
if ($stats['compared']) {
    printf("\nUguali al giudizio salvato: verdetto %d/%d · negativa sì/no %d/%d · citato sì/no %d/%d\n",
        $stats['verdict_same'], $stats['compared'], $stats['negative_same'], $stats['compared'], $stats['mention_same'], $stats['compared']);
}
printf("Giudicate %d, errori %d, dubbi %d, fonti negative %d\n", $stats['judged'], $stats['errors'], $stats['doubts'], $stats['neg_urls']);
printf("%.1f s, costo AI %.4f \$ (%.4f \$ a risposta)\n", microtime(true) - $t, $cost, $items ? $cost / count($items) : 0);
