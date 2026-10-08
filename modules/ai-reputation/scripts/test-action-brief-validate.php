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

$check('parseJson con fence', (S::parseJson("```json\n{\"a\":1}\n```")['a'] ?? null) === 1);
$check('parseJson senza oggetto → null', S::parseJson('niente') === null);
exit($fail ? 1 : 0);
