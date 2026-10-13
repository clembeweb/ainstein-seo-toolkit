<?php
// Test senza rete del controllo AI gratuito della home: URL, anti-SSRF, voto, highlight.
// Uso: php scripts/test-public-ai-check.php
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}
require_once dirname(__DIR__) . '/cron/bootstrap.php';

use Services\PublicAiCheckService as P;

$fail = 0;
$check = function (string $name, $got, $want) use (&$fail) {
    $ok = $got === $want;
    $fail += $ok ? 0 : 1;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($got) . ' invece di ' . json_encode($want)) . "\n";
};

// URL
$check('dominio nudo', P::normalizeUrl('esempio.it'), 'https://esempio.it');
$check('spazi e maiuscole', P::normalizeUrl('  HTTPS://Www.Esempio.it/chi-siamo '), 'https://www.esempio.it/chi-siamo');
$check('ftp rifiutato', P::normalizeUrl('ftp://esempio.it'), null);
$check('porta strana rifiutata', P::normalizeUrl('http://esempio.it:8080'), null);
$check('porta 443 ok', P::normalizeUrl('https://esempio.it:443/'), 'https://esempio.it:443/');
$check('userinfo rifiutato', P::normalizeUrl('https://admin@esempio.it'), null);
$check('host senza punto rifiutato', P::normalizeUrl('http://localhost'), null);
$check('IP privato letterale rifiutato', P::normalizeUrl('http://192.168.1.10'), null);
$check('metadati cloud rifiutati', P::normalizeUrl('http://169.254.169.254/latest'), null);
$check('vuoto', P::normalizeUrl(''), null);

// IP
foreach (['127.0.0.1', '10.0.0.1', '172.16.5.4', '192.168.1.1', '169.254.169.254', '100.64.1.1', '0.0.0.0', '::1', 'fc00::1', 'fe80::1'] as $ip) {
    $check("IP non pubblico {$ip}", P::isPublicIp($ip), false);
}
$check('IP pubblico 8.8.8.8', P::isPublicIp('8.8.8.8'), true);
$check('IP pubblico 2a00:1450::1', P::isPublicIp('2a00:1450:4001:80b::200e'), true);

// Voto e highlight
$findings = [
    ['area' => 'access', 'level' => 'ok', 'title' => 'I crawler delle AI entrano nel sito', 'detail' => 'x'],
    ['area' => 'schema', 'level' => 'bad', 'title' => 'Nessun dato strutturato su chi sei', 'detail' => 'segreto'],
    ['area' => 'theme:company', 'level' => 'bad', 'title' => 'Dati societari', 'detail' => 'segreto'],
    ['area' => 'files', 'level' => 'info', 'title' => 'llms.txt assente', 'detail' => 'x'],
];
$s = P::score($findings);
$check('voto: accesso ok + testo ok + sitemap ok = 3/5', [$s['score'], $s['total']], [3, 5]);
$h = P::highlights($findings);
$check('highlight senza dettagli', array_map(fn($x) => array_keys($x), $h), [['level', 'title'], ['level', 'title'], ['level', 'title']]);
$check('highlight senza info', count(array_filter($h, fn($x) => $x['level'] === 'info')), 0);
$bad = P::score([['area' => 'access', 'level' => 'bad', 'title' => 'x', 'detail' => ''], ['area' => 'readable', 'level' => 'bad', 'title' => 'x', 'detail' => ''], ['area' => 'files', 'level' => 'warn', 'title' => 'Sitemap non trovata', 'detail' => '']]);
$check('voto: accesso bloccato, testo vuoto, niente sitemap, niente schema = 0', $bad['score'], 0);

echo $fail ? "\n{$fail} FALLITI\n" : "\nTutti passati\n";
exit($fail ? 1 : 0);
