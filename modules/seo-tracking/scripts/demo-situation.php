<?php
/**
 * Report dimostrativo della scheda "Andamento" (ex Punto della situazione) con dati di prova, senza Google:
 * serve a vedere la pagina in locale quando GA4 e Search Console non sono collegati o sono scaduti.
 * Numeri verosimili (sito turistico piccolo), serie giornaliere, visite dalle AI. La lettura la scrive l'AI vera
 * (una chiamata, nessun addebito di crediti) oppure, con --no-ai, un testo fisso.
 *
 * Uso: php modules/seo-tracking/scripts/demo-situation.php <st_project_id> [--no-ai]
 */
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Modules\SeoTracking\Services\SituationService as S;

$projectId = (int) ($argv[1] ?? 0);
$noAi = in_array('--no-ai', $argv, true);
$project = Database::fetch("SELECT * FROM st_projects WHERE id = ?", [$projectId]);
if (!$project) {
    exit("Progetto {$projectId} non trovato\n");
}

mt_srand(42);
$p = S::periods('last_28');
$days = $p['days'];
$date = fn(string $start, int $i) => (new DateTimeImmutable($start))->modify("+{$i} days")->format('Y-m-d');
// Visite: un calo graduale con il weekend più alto; il periodo prima un po' più ricco
$ga4Daily = ['b' => [], 'a' => []];
$gscDaily = ['b' => [], 'a' => []];
$tot = ['b' => [0, 0], 'a' => [0, 0]];
$gscTot = ['b' => [0, 0], 'a' => [0, 0]];
for ($i = 0; $i < $days; $i++) {
    foreach (['b' => 1.0, 'a' => 1.35] as $k => $mult) {
        $dow = (int) (new DateTimeImmutable($date($p[$k][0], $i)))->format('N');
        $base = (3.2 + ($dow >= 6 ? 1.6 : 0) + mt_rand(0, 20) / 10) * $mult * (1 - $i / ($days * 2.2));
        $sessions = (int) round($base);
        $conv = mt_rand(0, 100) < 6 ? 1 : 0;
        $ga4Daily[$k][] = ['date' => $date($p[$k][0], $i), 'sessions' => $sessions, 'key_events' => $conv];
        $tot[$k][0] += $sessions;
        $tot[$k][1] += $conv;
        $clicks = (int) round($base * 0.55);
        $impr = (int) round($base * 24 + mt_rand(0, 30));
        $gscDaily[$k][] = ['date' => $date($p[$k][0], $i), 'clicks' => $clicks, 'impressions' => $impr, 'position' => round(7.5 + ($k === 'b' ? $i / 9 : 0) + mt_rand(-8, 8) / 10, 1)];
        $gscTot[$k][0] += $clicks;
        $gscTot[$k][1] += $impr;
    }
}
$y = fn($b) => round($b * 0.0); // anno scorso: niente dati (sito giovane)
$channels = [
    ['Organic Search', 0.58, 0.60], ['Direct', 0.22, 0.20], ['AI Assistant', 0.08, 0.14], ['Organic Social', 0.07, 0.04], ['Referral', 0.05, 0.02],
];
$chRows = [];
foreach ($channels as [$name, $qb, $qa]) {
    $b = round($tot['b'][0] * $qb);
    $a = round($tot['a'][0] * $qa);
    $chRows[] = ['name' => $name, 'sessions' => S::pack($b, $a, 0), 'key_events' => S::pack($name === 'Organic Search' ? $tot['b'][1] : 0, $name === 'Organic Search' ? $tot['a'][1] : 0, 0),
        'revenue' => S::pack(0, 0, 0), 'share_b_pct' => round($qb * 100, 1)];
}
$aiB = $chRows[2]['sessions']['b'];
$aiA = $chRows[2]['sessions']['a'];
$data = [
    'period' => $p,
    'caveats' => [],
    'ga4' => [
        'property' => 'Demo · ' . $project['domain'],
        'totals' => ['sessions' => S::pack($tot['b'][0], $tot['a'][0], 0), 'key_events' => S::pack($tot['b'][1], $tot['a'][1], 0), 'revenue' => S::pack(0, 0, 0)],
        'has_revenue' => false,
        'channels' => $chRows,
        'daily' => $ga4Daily,
        'ai_traffic' => [
            'sessions' => S::pack($aiB, $aiA, 0), 'key_events' => S::pack(0, 0, 0), 'share_b_pct' => 8.0,
            'sources' => [
                ['name' => 'ChatGPT', 'sessions' => S::pack(round($aiB * 0.7), round($aiA * 0.8), 0)],
                ['name' => 'Perplexity', 'sessions' => S::pack(round($aiB * 0.2), round($aiA * 0.12), 0)],
                ['name' => 'Gemini', 'sessions' => S::pack(round($aiB * 0.1), round($aiA * 0.08), 0)],
            ],
            'pages' => [['page' => '/', 'sessions_b' => round($aiB * 0.5)], ['page' => '/amalfi-coast', 'sessions_b' => round($aiB * 0.3)], ['page' => '/sunset', 'sessions_b' => round($aiB * 0.2)]],
        ],
        'organic_pages' => [
            'top' => [],
            'gainers' => [['page' => '/amalfi-coast', 'sessions_b' => 31, 'sessions_a' => 18, 'diff' => 13, 'vs_prev_pct' => 72.2, 'key_events_b' => 1, 'revenue_b' => 0]],
            'losers' => [['page' => '/capri', 'sessions_b' => 22, 'sessions_a' => 49, 'diff' => -27, 'vs_prev_pct' => -55.1, 'key_events_b' => 0, 'revenue_b' => 0],
                ['page' => '/', 'sessions_b' => 40, 'sessions_a' => 57, 'diff' => -17, 'vs_prev_pct' => -29.8, 'key_events_b' => 1, 'revenue_b' => 0]],
        ],
    ],
    'gsc' => [
        'totals' => [
            'b' => ['clicks' => $gscTot['b'][0], 'impressions' => $gscTot['b'][1], 'ctr' => round($gscTot['b'][0] / max(1, $gscTot['b'][1]) * 100, 2), 'position' => 9.1],
            'a' => ['clicks' => $gscTot['a'][0], 'impressions' => $gscTot['a'][1], 'ctr' => round($gscTot['a'][0] / max(1, $gscTot['a'][1]) * 100, 2), 'position' => 7.6],
            'y' => ['clicks' => 0, 'impressions' => 0, 'ctr' => null, 'position' => null],
        ],
        'brand' => ['regex' => 'demo', 'quota_b_pct' => 72.7, 'quota_a_pct' => 69.6],
        'daily' => $gscDaily,
        'queries' => [
            'gainers' => [['key' => 'tour in barca costiera amalfitana', 'clicks_b' => 9, 'clicks_a' => 3, 'diff' => 6, 'position_b' => 4.2, 'position_a' => 8.9, 'page' => 'https://' . $project['domain'] . '/amalfi-coast']],
            'losers' => [['key' => 'noleggio barche castellammare di stabia', 'clicks_b' => 0, 'clicks_a' => 4, 'diff' => -4, 'position_b' => 12.5, 'position_a' => 3.3, 'page' => 'https://' . $project['domain'] . '/'],
                ['key' => 'gita in barca capri da castellammare', 'clicks_b' => 2, 'clicks_a' => 5, 'diff' => -3, 'position_b' => 6.8, 'position_a' => 4.1, 'page' => 'https://' . $project['domain'] . '/capri']],
        ],
        'pages' => [
            'gainers' => [['key' => 'https://' . $project['domain'] . '/amalfi-coast', 'clicks_b' => 11, 'clicks_a' => 4, 'diff' => 7, 'position_b' => 5.0, 'position_a' => 9.2, 'query' => 'tour in barca costiera amalfitana']],
            'losers' => [['key' => 'https://' . $project['domain'] . '/', 'clicks_b' => 14, 'clicks_a' => 31, 'diff' => -17, 'position_b' => 6.1, 'position_a' => 3.9, 'query' => 'noleggio barche castellammare di stabia'],
                ['key' => 'https://' . $project['domain'] . '/capri', 'clicks_b' => 5, 'clicks_a' => 12, 'diff' => -7, 'position_b' => 7.4, 'position_a' => 4.6, 'query' => 'gita in barca capri da castellammare']],
        ],
    ],
];
// Totali GSC nel formato pack usato dalla vista
$g = $data['gsc']['totals'];
$data['gsc']['totals'] = [
    'clicks' => S::pack($g['b']['clicks'], $g['a']['clicks'], $g['y']['clicks']),
    'impressions' => S::pack($g['b']['impressions'], $g['a']['impressions'], $g['y']['impressions']),
    'ctr' => S::pack($g['b']['ctr'], $g['a']['ctr'], $g['y']['ctr']),
    'position' => S::pack($g['b']['position'], $g['a']['position'], $g['y']['position']),
];

$service = new S();
if ($noAi) {
    $narr = [
        'sintesi' => 'Mese in calo: 92 visite invece di 124, e la ricerca "noleggio barche castellammare di stabia" è scesa dalla posizione 3,3 alla 12,5.',
        'letture' => [
            'numeri' => 'Visite e clic calano insieme; la posizione media peggiora di un punto e mezzo. Con numeri così piccoli conta la direzione, non la percentuale.',
            'andamento' => 'Il calo è graduale lungo tutto il mese, con i weekend sempre sopra la media: nessun crollo in un giorno preciso.',
            'canali' => 'Più di metà del traffico arriva da Google; il diretto tiene, le AI scendono da 17 a 7 visite.',
            'ai' => 'Sette visite da ChatGPT, Perplexity e Gemini: l\'8% del totale, erano 17 nel periodo prima. Arrivano soprattutto sulla home e su Amalfi.',
            'google' => 'La perdita viene quasi tutta da "noleggio barche castellammare di stabia": da 4 clic a 0 perché è scesa dalla posizione 3,3 alla 12,5. Cresce invece "tour in barca costiera amalfitana".',
            'pagine' => 'La home perde 17 clic, Capri 7; la pagina Amalfi è l\'unica che cresce.',
        ],
        'azioni' => [
            ['titolo' => 'Recupera la home su "noleggio barche"', 'testo' => 'La ricerca noleggio barche castellammare di stabia è scesa dalla 3,3 alla 12,5: rivedi titolo e testo della home per quella ricerca.', 'ref' => 'gsc_queries'],
            ['titolo' => 'Rilancia la pagina Capri', 'testo' => 'La pagina /capri perde 7 clic e 27 visite: aggiorna prezzi e foto, aggiungi le domande vere dei clienti.', 'ref' => 'gsc_pages'],
            ['titolo' => 'Spingi Amalfi, che cresce', 'testo' => 'Tour in barca costiera amalfitana sale dalla 8,9 alla 4,2: collega la pagina dalla home e dal blog.', 'ref' => 'organic_pages'],
        ],
    ];
} else {
    $narr = $service->narrative(1, $project, $data);
}
$id = $service->save($project, 1, $data, $narr, 0);
echo "Report demo salvato: id {$id} → /seo-tracking/project/{$projectId}/situation?report={$id}\n";
