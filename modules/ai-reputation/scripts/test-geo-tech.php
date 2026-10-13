<?php
// Diagnosi tecnica GEO senza rete né DB (casi presi dalle prove del 2026-10-13: Stabia, corriere.it, nytimes.com).
// Run: php modules/ai-reputation/scripts/test-geo-tech.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/GeoTechService.php';

use Modules\AiReputation\Services\GeoTechService as G;
use Modules\AiReputation\Services\ReportBuilderService as R;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };

// robots.txt
$robots = "User-agent: *\nDisallow: /admin\n\nUser-agent: GPTBot\nUser-agent: ClaudeBot\nDisallow: /\n\nUser-agent: ChatGPT-User\nDisallow: / # niente\n\nUser-agent: PerplexityBot\nDisallow: /\nAllow: /\n\nSitemap: https://x.it/sitemap.xml\n";
$check('robots: gruppo con più user-agent', G::robotsVerdict($robots, 'claudebot') === 'blocked' && G::robotsVerdict($robots, 'gptbot') === 'blocked');
$check('robots: commento ignorato', G::robotsVerdict($robots, 'chatgpt-user') === 'blocked');
$check('robots: Allow / riapre', G::robotsVerdict($robots, 'perplexitybot') === 'allowed');
$check('robots: bot senza gruppo → *', G::robotsVerdict($robots, 'oai-searchbot') === 'allowed');
$check('robots: * che blocca tutto', G::robotsVerdict("User-agent: *\nDisallow: /", 'bingbot') === 'blocked');
$check('robots: sitemap', G::robotsSitemaps($robots) === ['https://x.it/sitemap.xml']);

// Pagine-sfida
$check('isOk: 200 normale', G::isOk(['http_code' => 200, 'body' => '<html><title>Ok</title></html>']));
$check('isOk: 403', !G::isOk(['http_code' => 403, 'body' => 'Forbidden']));
$check('isOk: SiteGround sgcaptcha', !G::isOk(['http_code' => 202, 'body' => '<meta http-equiv="refresh" content="0;/.well-known/sgcaptcha/?r=%2F">']));
$check('isOk: Cloudflare Just a moment', !G::isOk(['http_code' => 200, 'body' => '<title>Just a moment...</title>']));

// JSON-LD (quello vero di Stabia, ridotto) + @graph
$html = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"LocalBusiness","name":"Stabia Boat Rental","telephone":"+39 333 539 7011","address":{"@type":"PostalAddress","addressLocality":"Castellammare di Stabia","postalCode":"80053"}}</script>'
    . '<script type="application/ld+json">{"@graph":[{"@type":"WebSite","name":"x"},{"@type":["Organization"],"legalName":"Cavaliere Boat Rental Srl","vatID":"IT01234567890","sameAs":["https://www.facebook.com/stabia"]}]}</script>';
$sf = G::schemaFacts(G::jsonLd($html));
$check('schema: tipi senza WebSite', $sf['types'] === ['LocalBusiness', 'Organization']);
$check('schema: nome, telefono, città', $sf['name'] === 'Stabia Boat Rental' && $sf['telephone'] === '+39 333 539 7011' && $sf['locality'] === 'Castellammare di Stabia');
$check('schema: legalName e vatID da @graph', $sf['legal_name'] === 'Cavaliere Boat Rental Srl' && $sf['vat'] === '01234567890');
$check('schema: via assente', $sf['street'] === null);

// Fatti visibili
$page = '<footer><p>Cavaliere Boat Rental Srl - P. Iva n. 01234567890</p><p>Via Bonito, 12 - 80053 Castellammare</p>'
    . '<a href="tel:+393335397011">Chiama</a> <span>WhatsApp +39 331 774 6095</span> <span>+39 333 539 7011</span>'
    . '<a href="https://g.page/r/abc/review">Recensioni</a><script>var x="P.IVA 99999999999";</script></footer>';
$vf = G::visibleFacts(['home' => $page], 'https://stabiaboatrental.com/');
$check('fatti: P.IVA con "n."', $vf['vat'] === ['01234567890']);
$check('fatti: P.IVA dentro <script> ignorata', !in_array('99999999999', $vf['vat'], true));
$check('fatti: via', count($vf['street']) === 1 && str_starts_with($vf['street'][0], 'Via Bonito'));
$check('fatti: 2 telefoni distinti (tel: e testo uniti)', $vf['phones'] === ['+39 333 539 7011', '+39 331 774 6095']);
$check('fatti: link Google recensioni', $vf['review_links'] === ['https://g.page/r/abc/review']);

// Pagine Chi siamo / Contatti
$nav = '<a href="/chi-siamo/">Chi siamo</a><a href="https://altro.it/contatti">Altro</a><a href="contatti.html">Contattaci</a><a href="#top">Su</a>';
$pages = G::findPages($nav, 'https://stabiaboatrental.com/');
$check('pagine: chi siamo e contatti dello stesso sito', $pages === ['about' => 'https://stabiaboatrental.com/chi-siamo/', 'contact' => 'https://stabiaboatrental.com/contatti.html']);

// Diagnosi: Stabia (tutti entrano, P.IVA assente, due telefoni) e corriere (GPTBot/ClaudeBot bloccati dal server)
$ok = fn(string $robots = 'allowed') => ['robots' => $robots, 'http_code' => 200, 'verdict' => $robots === 'blocked' ? 'blocked_robots' : 'ok'];
$stabia = [
    'home' => ['ok' => true], 'bots' => array_fill_keys(array_keys(G::BOTS), $ok()), 'readable' => ['words' => 1377],
    'schema' => ['types' => ['LocalBusiness'], 'name' => 'Stabia Boat Rental', 'legal_name' => null, 'vat' => null, 'street' => null, 'locality' => 'Castellammare di Stabia', 'telephone' => '+39 333 539 7011', 'same_as' => []],
    'facts' => ['vat' => [], 'street' => [], 'phones' => ['+39 333 539 7011', '+39 331 774 6095'], 'review_links' => ['https://g.page/r/abc/review']],
    'sitemap' => ['found' => true], 'llms' => ['found' => true],
];
$notes = G::themeNotes($stabia);
$check('Stabia: P.IVA manca → contenuto', $notes['company']['level'] === 'bad' && str_contains($notes['company']['text'], 'va pubblicato'));
$check('Stabia: due telefoni e nessuna via', $notes['identity']['level'] === 'bad' && str_contains($notes['identity']['text'], '2 numeri'));
$check('Stabia: Google collegato', $notes['reviews']['level'] === 'ok');
$fs = G::findings($stabia);
$check('Stabia: crawler ok', (bool) array_filter($fs, fn($f) => $f['area'] === 'access' && $f['level'] === 'ok'));
$check('Stabia: esiti gravi per primi', $fs[0]['level'] === 'bad');
$stabiaVat = $stabia;
$stabiaVat['facts']['vat'] = ['01234567890'];
$check('P.IVA nel testo ma non nel JSON-LD → vatID', G::themeNotes($stabiaVat)['company']['level'] === 'warn' && str_contains(G::themeNotes($stabiaVat)['company']['text'], 'vatID'));

$corriere = $stabia;
$corriere['bots']['gptbot'] = ['robots' => 'allowed', 'http_code' => 403, 'verdict' => 'blocked_server'];
$corriere['bots']['claudebot'] = ['robots' => 'allowed', 'http_code' => 403, 'verdict' => 'blocked_server'];
$fc = G::findings($corriere);
$check('addestramento bloccato = info, non errore', !array_filter($fc, fn($f) => $f['area'] === 'access' && $f['level'] === 'bad') && (bool) array_filter($fc, fn($f) => $f['area'] === 'access' && $f['level'] === 'info'));
$corriere['bots']['oai-searchbot'] = ['robots' => 'allowed', 'http_code' => 403, 'verdict' => 'blocked_server'];
$fc = G::findings($corriere);
$bad = array_values(array_filter($fc, fn($f) => $f['area'] === 'access' && $f['level'] === 'bad'));
$check('crawler di risposta bloccato = errore con HTTP', $bad && str_contains($bad[0]['detail'], 'OAI-SearchBot (bloccato dal server, HTTP 403)'));

$nyt = ['error' => 'Il sito non risponde normalmente', 'home' => ['ok' => false], 'bots' => array_fill_keys(array_keys(G::BOTS), ['robots' => 'allowed', 'http_code' => null, 'verdict' => 'unverifiable'])];
$fn = G::findings($nyt);
$check('sito che blocca anche il browser: non verificabile, nessun "bloccato"', !array_filter($fn, fn($f) => $f['level'] === 'bad') && G::themeNotes($nyt) === []);

$person = G::findings($stabia, 'person');
$check('persona: niente P.IVA né via', !array_filter($person, fn($f) => str_starts_with($f['area'], 'theme:')) && !str_contains(json_encode($person), 'vatID'));

// Piano: "Sul sito oggi" nel clarify e intervento tecnico se un crawler di risposta è bloccato
$project = ['id' => 1, 'subject_name' => 'Stabia Boat Rental', 'subject_type' => 'company', 'website' => 'https://stabiaboatrental.com'];
$responses = [['id' => 1, 'engine' => 'openai', 'prompt_id' => 16, 'prompt_text' => 'Is Stabia Boat Rental legit?', 'prompt_cluster' => 'rep', 'citations' => []]];
$analyses = [1 => ['negative_urls' => [], 'negative' => 1, 'is_homonym' => 'no', 'brand_mentioned' => 1, 'competitors' => [], 'verdict' => 'mixed', 'summary' => '',
    'doubts' => [['doubt' => 'Non trova la partita IVA', 'fix' => 'Pubblicarla nel piè di pagina', 'evidence_urls' => []]]]];
$acts = (new R())->actions($project, $responses, $analyses, [], ['openai' => 'ChatGPT (API)'], null, $stabia);
$cl = array_values(array_filter($acts, fn($a) => $a['type'] === 'clarify'))[0];
$check('piano: clarify con la causa dal sito', str_contains($cl['rationale'], '[Sul sito oggi: Partita IVA non trovata'));
$check('piano: nessun intervento tecnico se i crawler entrano', !array_filter($acts, fn($a) => $a['type'] === 'correction'));
$acts2 = (new R())->actions($project, $responses, $analyses, [], ['openai' => 'ChatGPT (API)'], null, $corriere);
$check('piano: crawler di risposta bloccato → intervento tecnico', count(array_filter($acts2, fn($a) => $a['type'] === 'correction')) === 1);

echo $fail ? "\n{$fail} FALLITI\n" : "\nTutti i casi passano\n";
exit($fail ? 1 : 0);
