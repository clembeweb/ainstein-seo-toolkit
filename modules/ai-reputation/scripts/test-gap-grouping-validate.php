<?php
// Validazione dell'output AI del raggruppamento gap, senza rete. Run: php modules/ai-reputation/scripts/test-gap-grouping-validate.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionBriefService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/GapGroupingService.php';

use Modules\AiReputation\Services\GapGroupingService as G;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$ids = [10, 11, 12, 13]; // numero n ↔ prompt_id $ids[n-1]

// Caso buono: 2 articoli + 1 pending, tutte le domande assegnate
$r = G::validate(['articles' => [['title' => 'Roma', 'why' => 'Perché.', 'questions' => [1, 2]], ['title' => 'Italia', 'why' => 'Perché.', 'questions' => [3]]], 'pending' => [['question' => 4, 'needed' => 'un hotel documentato']]], $ids);
$check('2 articoli con prompt_id reali', count($r['articles']) === 2 && $r['articles'][0]['questions'] === [10, 11] && $r['articles'][1]['questions'] === [12]);
$check('pending con prova richiesta', $r['pending'] === [['prompt_id' => 13, 'needed' => 'un hotel documentato']]);

// Numero inventato (9) e doppione (1 in due articoli): scartati, prima occorrenza vince
$r = G::validate(['articles' => [['title' => 'A', 'why' => 'w', 'questions' => [1, 9]], ['title' => 'B', 'why' => 'w', 'questions' => [1, 2]]], 'pending' => []], $ids);
$check('numero inventato scartato', $r['articles'][0]['questions'] === [10]);
$check('doppione: prima occorrenza vince', $r['articles'][1]['questions'] === [11]);
$check('domande dimenticate → pending "da rivedere"', count($r['pending']) === 2 && $r['pending'][0] === ['prompt_id' => 12, 'needed' => G::PENDING_REVIEW] && $r['pending'][1]['prompt_id'] === 13);

// Domanda sia in articolo sia in pending: resta nell'articolo
$r = G::validate(['articles' => [['title' => 'A', 'why' => 'w', 'questions' => [1]]], 'pending' => [['question' => 1, 'needed' => 'x'], ['question' => 2, 'needed' => '']]], $ids);
$check('pending duplicato dell\'articolo ignorato', count(array_filter($r['pending'], fn($p) => $p['prompt_id'] === 10)) === 0);
$check('pending senza needed → "da rivedere"', in_array(['prompt_id' => 11, 'needed' => G::PENDING_REVIEW], $r['pending'], true));

// Articolo senza titolo o senza domande: scartato, le domande vanno in pending
$r = G::validate(['articles' => [['title' => '', 'why' => 'w', 'questions' => [1]], ['title' => 'B', 'why' => 'w', 'questions' => []]], 'pending' => []], $ids);
$check('articolo senza titolo scartato', $r['articles'] === []);
$check('le sue domande in pending', $r['pending'][0] === ['prompt_id' => 10, 'needed' => G::PENDING_REVIEW]);

// Oltre il tetto: i primi MAX_ARTICLES restano, gli altri vanno in pending
$arts = array_map(fn($n) => ['title' => "T{$n}", 'why' => 'w', 'questions' => [$n]], range(1, 8));
$r = G::validate(['articles' => $arts, 'pending' => []], range(100, 107));
$check('tetto MAX_ARTICLES', count($r['articles']) === G::MAX_ARTICLES && count($r['pending']) === 8 - G::MAX_ARTICLES);

// Numeri come stringhe accettati, tipi strani ignorati
$r = G::validate(['articles' => [['title' => 'A', 'why' => 'w', 'questions' => ['1', 'due', null, 2.5]]], 'pending' => 'no'], $ids);
$check('numeri come stringhe ok, altro ignorato', $r['articles'][0]['questions'] === [10]);

// Troncamenti
$r = G::validate(['articles' => [['title' => str_repeat('t', 600), 'why' => 'w', 'questions' => [1]]], 'pending' => []], $ids);
$check('titolo troncato a 500', mb_strlen($r['articles'][0]['title']) === 500);

// parseJson riusato: fence e testo intorno
$check('parseJson con fence', \Modules\AiReputation\Services\ActionBriefService::parseJson("ecco:\n```json\n{\"articles\":[]}\n```") === ['articles' => []]);

exit($fail ? 1 : 0);
