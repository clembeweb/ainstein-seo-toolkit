<?php
// Genera un PDF di prova da dati finti e verifica l'HTML. Run: php modules/ai-reputation/scripts/test-action-plan-pdf.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';

$project = ['id' => 1, 'name' => 'Test', 'subject_name' => 'Mario Rossi', 'website' => 'https://mariorossi.it'];
$run = ['id' => 4, 'created_at' => '2026-10-08 10:00:00'];
$metrics = ['risk' => 47, 'risk_label' => 'Alto'];
$actions = [
    ['id' => 1, 'type' => 'counter_content', 'status' => 'proposed', 'title' => 'Pagina autorevole che risponda a "Mario Rossi è affidabile?"', 'rationale' => 'Le AI rispondono in negativo a 3 domande.', 'target_url' => null, 'target_urls' => null, 'target_domain' => 'wired.it', 'channel' => null, 'brief' => null, 'brief_error' => null],
    ['id' => 2, 'type' => 'removal', 'status' => 'proposed', 'title' => 'Fonte negativa: giornale-x.it (2 pagine)', 'rationale' => 'Citata 5 volte da ChatGPT, Gemini.', 'target_url' => 'https://giornale-x.it/a', 'target_urls' => json_encode(['https://giornale-x.it/a', 'https://giornale-x.it/b']), 'target_domain' => 'giornale-x.it', 'channel' => null, 'brief' => null, 'brief_error' => null],
    ['id' => 3, 'type' => 'gap_article', 'status' => 'dismissed', 'title' => 'SCARTATO: non deve comparire', 'rationale' => '', 'target_url' => null, 'target_urls' => null, 'target_domain' => null, 'channel' => null, 'brief' => null, 'brief_error' => null],
];

$svc = new \Modules\AiReputation\Services\ActionPlanPdfService();
$html = $svc->html($project, $run, $actions, $metrics);
$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$check('intestazione con soggetto e rischio', str_contains($html, 'Mario Rossi') && str_contains($html, 'Alto') && str_contains($html, '47%'));
$check('conteggi', str_contains($html, '1 contenut') && str_contains($html, '1 sit'));
$check('contenuti prima delle rimozioni', strpos($html, 'Pagina autorevole') < strpos($html, 'Fonte negativa'));
$check('pagine della rimozione come link', substr_count($html, 'href="https://giornale-x.it/') === 2);
$check('scartati esclusi', !str_contains($html, 'SCARTATO'));
$check('scheda non generata', substr_count($html, 'Scheda operativa non generata') === 2);
$check('niente costi', !preg_match('/€|\$\s?\d/', $html));

$pdf = $svc->render($project, $run, $actions, $metrics);
$check('pdf binario', str_starts_with($pdf, '%PDF-') && strlen($pdf) > 2000);
$check('filename', $svc->filename($project, $run) === 'piano-interventi-mario-rossi-run4-2026-10-08.pdf');
file_put_contents(BASE_PATH . '/storage/cache/test-action-plan.pdf', $pdf);
echo "PDF scritto in storage/cache/test-action-plan.pdf\n";
exit($fail ? 1 : 0);
