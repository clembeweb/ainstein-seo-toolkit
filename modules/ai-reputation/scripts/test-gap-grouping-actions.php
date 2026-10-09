<?php
// Piano d'azione con e senza raggruppamento AI, su dati finti e senza DB. Run: php modules/ai-reputation/scripts/test-gap-grouping-actions.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';

use Modules\AiReputation\Services\ReportBuilderService as R;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };

$project = ['id' => 1, 'subject_name' => 'Mario Rossi', 'website' => 'https://mariorossi.it'];
$mk = fn(int $id, int $pid, string $text, string $cluster) => ['id' => $id, 'engine' => 'openai', 'prompt_id' => $pid, 'prompt_text' => $text, 'prompt_cluster' => $cluster, 'citations' => []];
$responses = [
    $mk(1, 10, 'Chi sono i migliori a Roma?', 'comm'),
    $mk(2, 11, 'Who are the best in Rome?', 'comm'),
    $mk(3, 12, 'Esperti di hotel di lusso?', 'comp'),
    $mk(4, 13, 'Mario Rossi è affidabile?', 'rep'),
    $mk(5, 14, 'Chi è il leader del settore?', 'comm'),
];
$an = fn(array $o = []) => $o + ['negative_urls' => [], 'negative' => 0, 'is_homonym' => 'no', 'brand_mentioned' => 0, 'competitors' => ['Tizio', 'Caio'], 'verdict' => 'not_mentioned', 'summary' => ''];
$analyses = [1 => $an(), 2 => $an(), 3 => $an(['competitors' => ['Sempronio']]), 4 => $an(['brand_mentioned' => 1, 'competitors' => []]), 5 => $an(['competitors' => []])];
$sources = [['status' => 'ok', 'domain' => 'italiaoggi.it', 'count' => 3]];
$labels = ['openai' => 'ChatGPT (API)'];
$svc = new R();

$gap = $svc->gapQuestions($responses, $analyses, $labels);
$check('gapQuestions: solo comm/comp non citate con competitor', array_keys($gap) === [10, 11, 12]);
$check('gapQuestions: testo e competitor', $gap[10]['prompt'] === 'Chi sono i migliori a Roma?' && array_keys($gap[12]['competitors']) === ['Sempronio']);

// Senza gruppi: una riga per domanda (comportamento attuale) + covered_prompts della singola domanda
$acts = $svc->actions($project, $responses, $analyses, $sources, $labels);
$gaps = array_values(array_filter($acts, fn($a) => $a['type'] === 'gap_article'));
$check('fallback: 3 articoli "Non citato"', count($gaps) === 3 && str_starts_with($gaps[0]['title'], 'Non citato: "Chi sono i migliori a Roma?"'));
$check('fallback: covered_prompts della domanda', R::coveredPrompts($gaps[0]) === [['id' => 10, 'text' => 'Chi sono i migliori a Roma?']]);
$check('fallback: nessun gap_pending', !array_filter($acts, fn($a) => $a['type'] === 'gap_pending'));

// Con gruppi: articoli con titolo AI e domande coperte, pending con prova richiesta, id sconosciuti ignorati
$groups = ['articles' => [['title' => 'Il real estate a Roma', 'why' => 'Le AI citano altri.', 'questions' => [10, 11, 99]], ['title' => 'Vuoto', 'why' => 'w', 'questions' => [99]]],
    'pending' => [['prompt_id' => 12, 'needed' => 'un hotel documentato'], ['prompt_id' => 98, 'needed' => 'x']]];
$acts = $svc->actions($project, $responses, $analyses, $sources, $labels, $groups);
$gaps = array_values(array_filter($acts, fn($a) => $a['type'] === 'gap_article'));
$pend = array_values(array_filter($acts, fn($a) => $a['type'] === 'gap_pending'));
$check('gruppi: 1 articolo (quello con solo id sconosciuti sparisce)', count($gaps) === 1 && $gaps[0]['title'] === 'Il real estate a Roma');
$check('gruppi: rationale con "Copre 2 domande"', str_contains($gaps[0]['rationale'], 'Le AI citano altri.') && str_contains($gaps[0]['rationale'], 'Copre 2 domande: "Chi sono i migliori a Roma?", "Who are the best in Rome?"'));
$check('gruppi: covered_prompts con 2 domande', array_column(R::coveredPrompts($gaps[0]), 'id') === [10, 11]);
$check('gruppi: testata suggerita', $gaps[0]['target_domain'] === 'italiaoggi.it');
$check('gruppi: 1 pending (id sconosciuto ignorato)', count($pend) === 1 && $pend[0]['title'] === 'Esperti di hotel di lusso?');
$check('gruppi: rationale del pending', $pend[0]['rationale'] === 'Serve una prova dal cliente: un hotel documentato');
$check('gruppi: pending con covered_prompts', R::coveredPrompts($pend[0]) === [['id' => 12, 'text' => 'Esperti di hotel di lusso?']]);
$check('gruppi: articoli prima dei pending', array_search($gaps[0], $acts, true) < array_search($pend[0], $acts, true));

// Chiave stabile tra ricalcoli
$check('actionKey: gap per insieme di domande, ordine indifferente', R::actionKey(['type' => 'gap_article', 'covered_prompts' => json_encode([['id' => 11], ['id' => 10]])]) === 'gap_article|10,11');
$check('actionKey: pending', R::actionKey(['type' => 'gap_pending', 'covered_prompts' => json_encode([['id' => 12]])]) === 'gap_pending|12');
$check('actionKey: rimozione per tipo|url|titolo', R::actionKey(['type' => 'removal', 'target_url' => 'https://x.it/a', 'title' => 'T']) === 'removal|https://x.it/a|T');

exit($fail ? 1 : 0);
