<?php
// Test senza rete del "Punto della situazione": periodi, brand regex, variazioni, parsing AI e righe GA4.
// Uso: php modules/seo-tracking/scripts/test-situation.php
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Modules\SeoTracking\Services\Ga4Service;
use Modules\SeoTracking\Services\SituationService as S;

$fail = 0;
$check = function (string $name, $got, $want) use (&$fail) {
    $ok = $got === $want;
    if (!$ok) {
        $fail++;
    }
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($got) . ' invece di ' . json_encode($want)) . "\n";
};

$p = S::periods('last_28', '2026-10-13');
$check('28 giorni: B', $p['b'], ['2026-09-14', '2026-10-11']);
$check('28 giorni: A precedente', $p['a'], ['2026-08-17', '2026-09-13']);
$check('28 giorni: Y anno prima', $p['y'], ['2025-09-14', '2025-10-11']);
$m = S::periods('last_month', '2026-10-13');
$check('mese scorso', $m['b'], ['2026-09-01', '2026-09-30']);
$check('mese scorso: A di pari durata', $m['a'], ['2026-08-02', '2026-08-31']);
$q = S::periods('last_quarter', '2026-10-13');
$check('trimestre scorso', $q['b'], ['2026-07-01', '2026-09-30']);
$q1 = S::periods('last_quarter', '2026-02-10');
$check('trimestre scorso a febbraio', $q1['b'], ['2025-10-01', '2025-12-31']);
$check('periodo sconosciuto → 28', S::periods('boh', '2026-10-13')['key'], 'last_28');

$check('pct', S::pct(120.0, 100.0), 20.0);
$check('pct su zero', S::pct(5.0, 0.0), null);
$check('brand regex', S::brandRegex('Stabia Boat Rental', 'www.stabiaboatrental.com'), 'stabia ?boat ?rental|stabiaboatrental');
$check('brand regex con trattino', S::brandRegex('Ainstein', 'https://ainstein.it'), 'ainstein');
$check('brand regex: "(test)" non è brand', S::brandRegex('Stabia Boat Rental (test)', 'www.stabiaboatrental.com'), 'stabia ?boat ?rental|stabiaboatrental');
$check('brand regex: nota dopo il trattino', S::brandRegex('Rossi Arredamenti - sito nuovo', 'rossiarredamenti.it'), 'rossi ?arredamenti|rossiarredamenti');
$check('brand regex: parola prova', S::brandRegex('Prova Beweb', 'beweb.it'), 'beweb');

$par = S::parseNarrative("```json\n{\"paragrafi\":[{\"titolo\":\"Il punto\",\"testo\":\"Va bene.\",\"refs\":[\"gsc_clicks\",\"inventato\"]}]}\n```");
$check('parse: refs in whitelist', $par[0]['refs'], ['gsc_clicks']);
$check('parse: testo', $par[0]['testo'], 'Va bene.');
try {
    S::parseNarrative('niente json');
    $check('parse: errore su testo libero', false, true);
} catch (\RuntimeException $e) {
    $check('parse: errore su testo libero', true, true);
}

$rows = Ga4Service::parseRows([
    'dimensionHeaders' => [['name' => 'sessionDefaultChannelGroup'], ['name' => 'dateRange']],
    'metricHeaders' => [['name' => 'sessions']],
    'rows' => [['dimensionValues' => [['value' => 'Organic Search'], ['value' => 'b']], 'metricValues' => [['value' => '42']]]],
]);
$check('GA4 righe', $rows, [['sessionDefaultChannelGroup' => 'Organic Search', 'dateRange' => 'b', 'sessions' => 42.0]]);

echo $fail ? "\n{$fail} FALLITI\n" : "\nTutti passati\n";
exit($fail ? 1 : 0);
