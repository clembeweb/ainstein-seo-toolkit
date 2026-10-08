<?php
/**
 * Test CLI del partial views/partials/action-brief.php (nessuna chiamata AI, nessun DB).
 * Uso (dalla root del repo):
 *   php -d display_errors=1 -d error_reporting=E_ALL modules/ai-reputation/scripts/test-action-brief-partial.php
 * Exit code 0 = tutto ok.
 */

if (PHP_SAPI !== 'cli') {
    exit("Solo CLI\n");
}

$root = dirname(__DIR__, 3);
require_once $root . '/core/Router.php';
require_once $root . '/core/View.php';

// Qualsiasi warning/notice/deprecation = fallimento
$problems = [];
set_error_handler(function (int $no, string $str, string $file, int $line) use (&$problems) {
    $problems[] = "PHP [{$no}] {$str} in " . basename($file) . ":{$line}";
    return true;
});

$base = [
    'id' => 42, 'run_id' => 7, 'project_id' => 3, 'type' => 'counter_content', 'title' => 'Intervento di prova',
    'channel' => null, 'channel_rationale' => null, 'suggested_outlets' => null,
    'brief' => null, 'brief_model' => null, 'brief_generated_at' => null, 'brief_error' => null,
];
$contentBrief = [
    'kind' => 'content', 'title' => 'Federico Marcaccini: il profilo professionale', 'angle' => 'Taglio informativo e neutro',
    'points' => ['Percorso professionale', 'Ruolo attuale <b>x</b>'],
    'facts' => [['fact' => 'Fondatore di Esempio Srl', 'source' => 'https://esempio.it/chi-siamo'], ['fact' => 'Fatto senza fonte', 'source' => '']],
    'avoid' => ['Toni promozionali'], 'length_words' => 900, 'language' => 'it', 'own_site_note' => 'Pubblicare anche in homepage',
];
$removalBrief = [
    'kind' => 'removal', 'recipient' => 'Redazione di Esempio News', 'request' => 'update',
    'basis' => 'Notizia superata da sentenza del 2024', 'pages' => ['https://esempio-news.it/articolo-1', 'https://esempio-news.it/articolo-2'],
    'fallback' => 'Richiedere la deindicizzazione a Google',
];

$cases = [
    'nessuna scheda' => [
        'a' => $base, 'canEdit' => true, 'briefCost' => 1,
        'must' => ['id="brief-42"', 'data-brief-url=', '/ai-reputation/project/3/runs/7/actions/42/brief', 'generateBrief(42, $event, false)', 'Genera scheda', '1 credito'],
        'mustNot' => ['Generazione non riuscita', 'Rigenera'],
    ],
    'nessuna scheda, 1.5 crediti' => [
        'a' => $base, 'canEdit' => true, 'briefCost' => 1.5,
        'must' => ['1,5 crediti'], 'mustNot' => [],
    ],
    'nessuna scheda, viewer' => [
        'a' => $base, 'canEdit' => false, 'briefCost' => 1,
        'must' => ['Scheda operativa non ancora generata.'], 'mustNot' => ['<button', 'Genera scheda'],
    ],
    'errore' => [
        'a' => ['brief_error' => 'Risposta AI non valida <script>'] + $base, 'canEdit' => true, 'briefCost' => 1,
        'must' => ['Generazione non riuscita: Risposta AI non valida &lt;script&gt;', 'Riprova', 'generateBrief(42, $event, false)'],
        'mustNot' => ['<script>', 'Genera scheda'],
    ],
    'scheda contenuto' => [
        'a' => ['channel' => 'both', 'channel_rationale' => 'Serve presidio ufficiale e terzi', 'suggested_outlets' => json_encode(['esempio.it', 'altro.com']),
                'brief' => json_encode($contentBrief), 'brief_model' => 'claude-opus-5-5', 'brief_generated_at' => '2026-10-08 10:30:00'] + $base,
        'canEdit' => true, 'briefCost' => 1,
        'must' => ['Sito proprietario + esterni', 'href="https://esempio.it"', 'href="https://altro.com"', 'Serve presidio ufficiale e terzi',
                   'Titolo proposto:', 'Federico Marcaccini: il profilo professionale', 'Taglio:', 'Punti da coprire:', 'Ruolo attuale &lt;b&gt;x&lt;/b&gt;',
                   'Fatti da citare:', '(fonte: https://esempio.it/chi-siamo)', 'Fatto senza fonte', 'Da evitare:', 'Toni promozionali',
                   '900 parole', 'IT', 'Sul sito ufficiale:', 'Scheda generata il 08/10/2026 10:30 con claude-opus-5-5', 'Rigenera', 'generateBrief(42, $event, true)'],
        'mustNot' => ['Genera scheda', '<b>x</b>', 'A chi scrivere:'],
    ],
    'scheda contenuto, viewer' => [
        'a' => ['channel' => 'external', 'brief' => json_encode($contentBrief), 'brief_model' => 'claude-opus-5-5', 'brief_generated_at' => '2026-10-08 10:30:00'] + $base,
        'canEdit' => false, 'briefCost' => 1,
        'must' => ['Siti esterni', 'Titolo proposto:'], 'mustNot' => ['Rigenera', '<button'],
    ],
    'scheda rimozione' => [
        'a' => ['type' => 'removal', 'brief' => json_encode($removalBrief), 'brief_model' => 'claude-opus-5-5', 'brief_generated_at' => '2026-10-08 11:00:00'] + $base,
        'canEdit' => true, 'briefCost' => 1,
        'must' => ['A chi scrivere:', 'Redazione di Esempio News', 'Aggiornamento del contenuto', 'Su quale base:', 'Notizia superata da sentenza del 2024',
                   'Pagine:', 'href="https://esempio-news.it/articolo-1"', 'href="https://esempio-news.it/articolo-2"', 'Se rifiutano:', 'Richiedere la deindicizzazione a Google',
                   'Scheda generata il 08/10/2026 11:00', 'Rigenera'],
        'mustNot' => ['Titolo proposto:', 'Genera scheda'],
    ],
];

$fail = 0;
foreach ($cases as $name => $c) {
    $problems = [];
    $html = \Core\View::partial('ai-reputation::partials/action-brief', [
        'a' => $c['a'], 'basePath' => '/ai-reputation/project/3', 'run' => ['id' => 7], 'csrf' => 'tok', 'canEdit' => $c['canEdit'], 'briefCost' => $c['briefCost'],
    ]);
    $errs = [];
    if (trim($html) === '') {
        $errs[] = 'HTML vuoto (partial non risolto?)';
    }
    foreach ($c['must'] as $needle) {
        if (!str_contains($html, $needle)) {
            $errs[] = "manca: {$needle}";
        }
    }
    foreach ($c['mustNot'] as $needle) {
        if (str_contains($html, $needle)) {
            $errs[] = "non dovrebbe esserci: {$needle}";
        }
    }
    foreach ($problems as $p) {
        $errs[] = $p;
    }
    if ($errs) {
        $fail++;
        echo "FAIL  {$name}\n";
        foreach ($errs as $e) {
            echo "        - {$e}\n";
        }
    } else {
        echo "OK    {$name}\n";
    }
}

echo $fail === 0 ? "\nTutti i casi passano (" . count($cases) . ")\n" : "\n{$fail} caso/i falliti\n";
exit($fail === 0 ? 0 : 1);
