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
    ['id' => 4, 'type' => 'gap_article', 'status' => 'accepted', 'title' => 'Non citato: "migliori consulenti a Napoli"', 'rationale' => 'Le AI citano altri.', 'target_url' => null, 'target_urls' => null, 'target_domain' => null,
     'channel' => 'both', 'channel_rationale' => 'Il sito ufficiale non è mai citato.', 'suggested_outlets' => json_encode(['wired.it']),
     'brief' => json_encode(['kind' => 'content', 'title' => 'Titolo di prova', 'angle' => 'Taglio', 'points' => ['p1', 'p2', 'p3'], 'facts' => [['fact' => 'Fatto', 'source' => 'wired.it']], 'avoid' => ['a1'], 'length_words' => 900, 'language' => 'it', 'own_site_note' => 'Pagina chi siamo']),
     'brief_model' => 'claude-opus-5-5', 'brief_generated_at' => '2026-10-08 11:00:00', 'brief_error' => ''],
    ['id' => 5, 'type' => 'removal', 'status' => 'accepted', 'title' => 'Fonte negativa: blog-y.it (1 pagina)', 'rationale' => 'Citata 2 volte.', 'target_url' => 'https://blog-y.it/p', 'target_urls' => json_encode(['https://blog-y.it/p']), 'target_domain' => 'blog-y.it', 'channel' => null,
     'brief' => json_encode(['kind' => 'removal', 'recipient' => 'redazione@blog-y.it', 'request' => 'deindex', 'basis' => 'Contenuto diffamatorio', 'pages' => ['https://blog-y.it/p'], 'fallback' => 'Segnalare a Google']),
     'brief_model' => 'claude-opus-5-5', 'brief_generated_at' => '2026-10-08 11:00:00', 'brief_error' => ''],
    ['id' => 3, 'type' => 'gap_article', 'status' => 'dismissed', 'title' => 'SCARTATO: non deve comparire', 'rationale' => '', 'target_url' => null, 'target_urls' => null, 'target_domain' => null, 'channel' => null, 'brief' => null, 'brief_error' => null],
];

$svc = new \Modules\AiReputation\Services\ActionPlanPdfService();
$html = $svc->html($project, $run, $actions, $metrics);
$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$check('intestazione con soggetto e rischio', str_contains($html, 'Mario Rossi') && str_contains($html, 'Alto') && str_contains($html, '47%'));
$check('conteggi', str_contains($html, '2 contenut') && str_contains($html, '2 sit'));
$check('contenuti prima delle rimozioni', strpos($html, 'Pagina autorevole') < strpos($html, 'Fonte negativa'));
$check('pagine della rimozione come link', substr_count($html, 'href="https://giornale-x.it/') === 2);
$check('scartati esclusi', !str_contains($html, 'SCARTATO'));
$check('scheda non generata', substr_count($html, 'Scheda operativa non generata') === 2);
$check('scheda nel pdf: canale e testata', str_contains($html, 'Sito proprietario + siti esterni') && str_contains($html, 'wired.it'));
$check('scheda nel pdf: brief', str_contains($html, 'Titolo di prova') && str_contains($html, 'p3') && str_contains($html, 'Pagina chi siamo'));
$check('scheda rimozione: destinatario e richiesta', str_contains($html, 'A chi scrivere') && str_contains($html, 'redazione@blog-y.it') && str_contains($html, 'Deindicizzazione'));
$check('niente nome modello', !str_contains($html, 'claude-opus'));
$check('niente costi', !preg_match('/€|\$\s?\d/', $html));

$pdf = $svc->render($project, $run, $actions, $metrics);
$check('pdf binario', str_starts_with($pdf, '%PDF-') && strlen($pdf) > 2000);
$check('filename', $svc->filename($project, $run) === 'piano-interventi-mario-rossi-run4-2026-10-08.pdf');
file_put_contents(BASE_PATH . '/storage/cache/test-action-plan.pdf', $pdf);
echo "PDF scritto in storage/cache/test-action-plan.pdf\n";
exit($fail ? 1 : 0);
