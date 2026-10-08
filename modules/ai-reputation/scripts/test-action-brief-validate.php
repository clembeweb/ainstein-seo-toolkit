<?php
// Validazione dell'output AI per le schede, senza rete. Run: php modules/ai-reputation/scripts/test-action-brief-validate.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionBriefService.php';

use Modules\AiReputation\Services\ActionBriefService as S;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$content = ['type' => 'counter_content', 'target_urls' => null, 'target_url' => null];
$removal = ['type' => 'removal', 'target_urls' => json_encode(['https://x.it/a', 'https://x.it/b']), 'target_url' => 'https://x.it/a'];
$ok = ['wired.it', 'ilsole24ore.com'];

$good = ['channel' => 'both', 'channel_rationale' => 'perché', 'suggested_outlets' => ['wired.it', 'inventata.com'],
    'brief' => ['kind' => 'content', 'title' => 'T', 'angle' => 'A', 'points' => ['1', '2', '3'], 'facts' => [['fact' => 'f', 'source' => 'wired.it']], 'avoid' => ['x'], 'length_words' => 900, 'language' => 'it', 'own_site_note' => '']];
$r = S::validate($good, $content, $ok);
$check('contenuto valido', is_array($r) && $r['channel'] === 'both');
$check('testata inventata scartata', is_array($r) && $r['suggested_outlets'] === ['wired.it']);

$r = S::validate(['channel' => 'ovunque'] + $good, $content, $ok);
$check('canale fuori enum → errore', is_string($r));

$few = $good; $few['brief']['points'] = ['solo uno'];
$check('meno di 3 punti → errore', is_string(S::validate($few, $content, $ok)));

$rem = ['channel' => 'external', 'channel_rationale' => '', 'suggested_outlets' => [],
    'brief' => ['kind' => 'removal', 'recipient' => 'redazione', 'request' => 'update', 'basis' => 'notizia superata', 'pages' => ['https://x.it/a', 'https://altro.it/z'], 'fallback' => 'contro-contenuto']];
$r = S::validate($rem, $removal, $ok);
$check('rimozione: canale forzato a null', is_array($r) && $r['channel'] === null);
$check('rimozione: pagine fuori target scartate', is_array($r) && $r['brief']['pages'] === ['https://x.it/a']);

$wrongKind = $good; $wrongKind['brief']['kind'] = 'removal';
$check('kind incoerente → errore', is_string(S::validate($wrongKind, $content, $ok)));

// --- Fix round 1 ---
$norm = $good; $norm['suggested_outlets'] = ['https://www.wired.it/', 'WIRED.it', 'ilsole24ore.com/'];
$r = S::validate($norm, $content, $ok);
$check('outlet normalizzati (url, maiuscole, duplicati)', is_array($r) && $r['suggested_outlets'] === ['wired.it', 'ilsole24ore.com']);

$own = ['channel' => 'own_site'] + $good;
$r = S::validate($own, $content, $ok);
$check('own_site svuota le testate', is_array($r) && $r['channel'] === 'own_site' && $r['suggested_outlets'] === []);

$badReq = $rem; $badReq['brief']['request'] = 'cancella';
$check('rimozione con request non valida → errore', is_string(S::validate($badReq, $removal, $ok)));

$outside = $rem; $outside['brief']['pages'] = ['https://altro.it/z'];
$r = S::validate($outside, $removal, $ok);
$check('rimozione: pagine tutte fuori target → pagine dell\'intervento', is_array($r) && $r['brief']['pages'] === ['https://x.it/a', 'https://x.it/b']);

$noPages = ['type' => 'removal', 'target_urls' => null, 'target_url' => null];
$check('rimozione senza pagine proprie né valide → errore', is_string(S::validate($rem, $noPages, $ok)));

$noRecipient = $rem; $noRecipient['brief']['recipient'] = '';
$check('rimozione senza destinatario → errore', is_string(S::validate($noRecipient, $removal, $ok)));
$noBasis = $rem; $noBasis['brief']['basis'] = ' ';
$check('rimozione senza motivazione → errore', is_string(S::validate($noBasis, $removal, $ok)));

$noSrc = $good; $noSrc['brief']['facts'] = [['fact' => 'a', 'source' => ''], ['fact' => 'b', 'source' => 'wired.it']];
$r = S::validate($noSrc, $content, $ok);
$check('fatto con fonte vuota scartato', is_array($r) && count($r['brief']['facts']) === 1 && $r['brief']['facts'][0]['fact'] === 'b');

$allowed = ['https://mario.it/chi-sono', 'mario.it', 'wired.it'];
$srcs = $good;
$srcs['brief']['facts'] = [
    ['fact' => 'inventata', 'source' => 'Wikipedia'],
    ['fact' => 'dal profilo', 'source' => 'Profilo confermato dal cliente'],
    ['fact' => 'da url', 'source' => 'https://mario.it/chi-sono'],
    ['fact' => 'da dominio', 'source' => 'articolo su wired.it'],
];
$r = S::validate($srcs, $content, $ok, $allowed);
$check('allowedSources: fonte inventata scartata, profilo e fonti note tenute',
    is_array($r) && array_column($r['brief']['facts'], 'fact') === ['dal profilo', 'da url', 'da dominio']);
$r = S::validate($srcs, $content, $ok);
$check('senza allowedSources il controllo sulla fonte non si applica (solo non vuota)', is_array($r) && count($r['brief']['facts']) === 4);

$check('parseJson con fence', (S::parseJson("```json\n{\"a\":1}\n```")['a'] ?? null) === 1);
$check('parseJson senza oggetto → null', S::parseJson('niente') === null);
exit($fail ? 1 : 0);
