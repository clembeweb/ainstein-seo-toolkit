<?php
// Dubbi delle AI (caso Stabia, run 1 del 2026-10-13): niente "fonti negative" per indizi, un intervento "clarify",
// contenuti delle aziende sul sito proprio. Senza DB. Run: php modules/ai-reputation/scripts/test-clarify.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionBriefService.php';

use Modules\AiReputation\Services\ReportBuilderService as R;
use Modules\AiReputation\Services\ActionBriefService as B;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };

$project = ['id' => 1, 'subject_name' => 'Stabia Boat Rental', 'subject_type' => 'company', 'website' => 'https://stabiaboatrental.com'];
$mk = fn(int $id, int $pid, string $text, string $cluster) => ['id' => $id, 'engine' => 'openai', 'prompt_id' => $pid, 'prompt_text' => $text, 'prompt_cluster' => $cluster, 'citations' => []];
$responses = [
    $mk(1, 16, 'Is Stabia Boat Rental legit?', 'rep'),
    $mk(2, 20, 'Noleggio barche senza patente a Castellammare?', 'comm'),
];
$an = fn(array $o = []) => $o + ['negative_urls' => [], 'doubts' => [], 'negative' => 0, 'is_homonym' => 'no', 'brand_mentioned' => 1, 'competitors' => [], 'verdict' => 'positive', 'summary' => ''];
$analyses = [
    1 => $an(['negative' => 1, 'verdict' => 'mixed', 'doubts' => [
        ['doubt' => 'Lo stesso telefono compare su più siti quasi identici', 'fix' => 'Spiegare nella pagina Chi siamo il legame con gli altri siti', 'evidence_urls' => ['https://sorrentoboatrental.com/it/']],
        ['doubt' => 'Non trova ragione sociale e partita IVA', 'fix' => 'Pubblicare ragione sociale e P.IVA nel piè di pagina', 'evidence_urls' => []],
    ]]),
    2 => $an(['brand_mentioned' => 0, 'verdict' => 'not_mentioned', 'competitors' => ['Sorrento Boats', 'Click&Boat']]),
];
$sources = [['status' => 'ok', 'domain' => 'clickandboat.com', 'count' => 9]];
$labels = ['openai' => 'ChatGPT (API)'];
$acts = (new R())->actions($project, $responses, $analyses, $sources, $labels);
$types = array_column($acts, 'type');

$check('nessuna rimozione per gli indizi', !in_array('removal', $types, true));
$check('un intervento clarify', count(array_keys($types, 'clarify', true)) === 1);
$check('niente contro-contenuto se la negativa è solo dubbi', !in_array('counter_content', $types, true));
$clarify = $acts[array_search('clarify', $types, true)];
$check('clarify sul sito del soggetto', $clarify['target_domain'] === 'stabiaboatrental.com');
$check('clarify elenca dubbio e rimedio', str_contains($clarify['rationale'], 'partita IVA') && str_contains($clarify['rationale'], 'piè di pagina'));
$check('clarify con la domanda coperta', R::coveredPrompts($clarify) === [['id' => 16, 'text' => 'Is Stabia Boat Rental legit?']]);
$gap = $acts[array_search('gap_article', $types, true)] ?? null;
$check('azienda: articolo sul sito proprio, non sul portale', $gap && $gap['target_domain'] === 'stabiaboatrental.com' && str_contains($gap['rationale'], 'sul vostro sito'));

// Negativa con fonte negativa vera: resta rimozione + contro-contenuto
$analyses[1]['negative_urls'] = ['https://giornale.it/truffa-noleggi'];
$responses[0]['citations'] = [['url' => 'https://giornale.it/truffa-noleggi', 'title' => 'Truffa dei noleggi']];
$acts2 = (new R())->actions($project, $responses, $analyses, $sources, $labels);
$types2 = array_column($acts2, 'type');
$check('fonte negativa vera: rimozione', in_array('removal', $types2, true));
$check('fonte negativa vera: contro-contenuto da azienda', ($cc = $acts2[array_search('counter_content', $types2, true)] ?? null) && str_contains($cc['rationale'], 'dati societari'));

// Persona: contenuti ancora sulla testata suggerita
$person = ['subject_type' => 'person', 'subject_name' => 'Mario Rossi'] + $project;
$acts3 = (new R())->actions($person, $responses, $analyses, $sources, $labels);
$g3 = $acts3[array_search('gap_article', array_column($acts3, 'type'), true)];
$check('persona: articolo sulla testata citata', $g3['target_domain'] === 'clickandboat.com');

// Validazione scheda clarify
$action = ['type' => 'clarify', 'target_url' => 'https://stabiaboatrental.com', 'target_urls' => null];
$ok = B::validate(['channel' => 'external', 'channel_rationale' => 'x', 'suggested_outlets' => ['clickandboat.com'], 'brief' => [
    'kind' => 'clarify',
    'items' => [['doubt' => 'P.IVA assente', 'where' => 'Piè di pagina', 'text' => 'Cavaliere Boat Rental Srl — P.IVA [DA COMPLETARE: partita IVA]'], ['doubt' => '', 'text' => 'scartato']],
    'schema_jsonld' => ['@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => 'Stabia Boat Rental'],
    'checklist' => ['Scheda Google Business con lo stesso telefono'],
]], $action, ['clickandboat.com']);
$check('clarify: valido', is_array($ok));
$check('clarify: canale forzato a sito proprio, niente testate', is_array($ok) && $ok['channel'] === 'own_site' && $ok['suggested_outlets'] === []);
$check('clarify: item senza dubbio scartato', is_array($ok) && count($ok['brief']['items']) === 1);
$check('clarify: JSON-LD oggetto → testo', is_array($ok) && str_contains($ok['brief']['schema_jsonld'], '"@type": "LocalBusiness"'));
$check('clarify senza testi → errore', is_string(B::validate(['channel' => 'own_site', 'brief' => ['kind' => 'clarify', 'items' => []]], $action, [])));
$check('clarify con kind content → errore', is_string(B::validate(['channel' => 'own_site', 'brief' => ['kind' => 'content', 'points' => ['a', 'b', 'c'], 'title' => 't']], $action, [])));

echo $fail ? "\n{$fail} FALLITI\n" : "\nTutti i casi passano\n";
exit($fail ? 1 : 0);
