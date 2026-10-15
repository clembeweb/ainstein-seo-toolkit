<?php
/**
 * Scheda "Andamento" (ex Punto della situazione) di AI Rank: prima i numeri, i grafici e le tabelle, l'AI aggiunge
 * solo la lettura (una o due frasi per blocco) e le 3 cose da fare. Richiesta di Clemente del 2026-10-15: "troppo testo".
 * Report salvati prima (5 paragrafi): stessa pagina, senza grafico dell'andamento, col commento vecchio in fondo.
 */
use Modules\SeoTracking\Services\SituationService;

$currentPage = 'situation';
$pid = (int) $project['id'];
$base = "/seo-tracking/project/{$pid}";
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
$fmtCr = fn(float $v) => rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',') . ' credit' . ($v == 1 ? 'o' : 'i');
$num = fn($v, int $dec = 0) => $v === null ? '–' : number_format((float) $v, $dec, ',', '.');
$money = fn($v) => $v === null ? '–' : '€ ' . number_format((float) $v, 0, ',', '.');
// Badge variazione: verde se sale, rosso se scende (per la posizione media è il contrario: più bassa = meglio)
// Con numeri piccoli (sotto SMALL_BASE sia ora sia prima) la variazione può essere casuale: grigia, con spiegazione al passaggio
$delta = function ($pct, bool $inverse = false, string $label = 'vs prima', $now = null, $before = null): string {
    if ($pct === null) {
        return '<span class="text-xs text-slate-400">' . e($label) . ' –</span>';
    }
    $small = $now !== null && max(abs((float) $now), abs((float) $before)) < SituationService::SMALL_BASE;
    $good = $inverse ? $pct < 0 : $pct > 0;
    $cls = $pct == 0 || $small ? 'text-slate-500' : ($good ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400');
    $title = $small ? ' title="Numeri piccoli (' . number_format((float) $before, 0, ',', '.') . ' → ' . number_format((float) $now, 0, ',', '.') . '): la variazione può essere casuale"' : '';
    return '<span class="text-xs font-medium ' . $cls . '"' . $title . '>' . ($pct > 0 ? '+' : '') . number_format((float) $pct, 1, ',', '.') . '% <span class="font-normal text-slate-400">' . e($label) . '</span></span>';
};
$d = $report['data'] ?? [];
// Nomi dei canali GA4 in italiano semplice
$channelName = fn(string $n) => [
    'Organic Search' => 'Google e motori di ricerca', 'Direct' => 'Diretto (scrivono l\'indirizzo)', 'Organic Social' => 'Social',
    'Paid Search' => 'Annunci sui motori', 'Paid Social' => 'Social a pagamento', 'Referral' => 'Altri siti (link)', 'Email' => 'Email',
    'Organic Shopping' => 'Shopping gratuito', 'Paid Shopping' => 'Shopping a pagamento', 'Organic Video' => 'Video', 'Paid Video' => 'Video a pagamento',
    'AI Assistant' => 'Assistenti AI (ChatGPT, Gemini…)', 'Display' => 'Banner (display)', 'Cross-network' => 'Campagne Google (più reti)', 'Unassigned' => 'Non classificato', 'Affiliates' => 'Affiliazioni',
][$n] ?? $n;
$ga4 = $d['ga4'] ?? null;
$gsc = $d['gsc'] ?? null;
$narr = $report['narrative'] ?? [];
$isNew = $narr && SituationService::isNewNarrative($narr);
$ai = $ga4['ai_traffic'] ?? null;
$dailyGa4 = $ga4['daily'] ?? ['b' => [], 'a' => []];
$dailyGsc = $gsc['daily'] ?? ['b' => [], 'a' => []];
$hasTrend = !empty($dailyGa4['b']) || !empty($dailyGsc['b']);
// Lettura breve dell'AI sotto un blocco (solo formato nuovo)
$iconAi = '<svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>';
$reading = function (string $key) use ($isNew, $narr, $iconAi): string {
    $txt = $isNew ? trim((string) ($narr['letture'][$key] ?? '')) : '';
    return $txt === '' ? '' : '<p class="flex items-start gap-2 px-4 py-3 text-sm text-slate-700 dark:text-slate-300 bg-blue-50/60 dark:bg-blue-900/10 border-t border-blue-100 dark:border-blue-900/30">' . $iconAi . '<span>' . e($txt) . '</span></p>';
};
// Mini-linea dentro un numero grande: id del canvas + valori (il JS li disegna)
$sparks = [];
$kpiTile = function (string $id, string $label, string $value, string $sub, array $series = [], string $color = '#3b82f6') use (&$sparks): string {
    $spark = '';
    if (count(array_filter($series, fn($v) => $v !== null)) >= 3) {
        $sparks[$id] = ['values' => array_map(fn($v) => $v === null ? null : (float) $v, $series), 'color' => $color];
        // Contenitore ad altezza fissa: senza, Chart.js in modalità responsive allarga il canvas all'infinito
        $spark = '<div class="relative mt-2 h-9 w-full"><canvas id="spark-' . $id . '" aria-hidden="true"></canvas></div>';
    }
    return '<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">'
        . '<p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">' . e($label) . '</p>'
        . '<p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">' . $value . '</p>'
        . '<div class="mt-1 flex flex-wrap gap-x-3">' . $sub . '</div>' . $spark . '</div>';
};
$col = fn(array $rows, string $k) => array_map(fn($r) => $r[$k] ?? null, $rows);
?>
<div class="space-y-6" x-data="situationApp()">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Testata: periodo, bottone, collegamenti -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Andamento: come va il sito</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Numeri veri da Google Analytics e Search Console, confrontati col periodo prima e con l'anno scorso. L'AI aggiunge solo la lettura e le 3 cose da fare.</p>
                <?php if ($canEdit): ?>
                <form class="mt-3 flex flex-wrap items-center gap-3" @submit.prevent="generate($event)">
                    <select name="period" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm py-2">
                        <?php foreach ($periods as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= $report && ($report['period_key'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" :disabled="loading" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-60 transition-colors">
                        <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <span x-text="loading ? 'Leggo i dati…' : '<?= $report ? 'Aggiorna' : 'Leggi i dati' ?>'"></span>
                    </button>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= $fmtCr($cost) ?> · ne hai <?= $num($balance, 1) ?></span>
                </form>
                <p x-show="error" x-cloak x-text="error" class="mt-3 text-sm text-red-600 dark:text-red-400"></p>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <?php if (!empty($ga4Conn['property_id'])): ?>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300" title="<?= e($ga4Conn['property_name'] ?: $ga4Conn['property_id']) ?>">● Analytics collegato</span>
                <?php elseif (!empty($ga4Conn)): ?>
                <a href="<?= url("{$base}/ga4/select-property") ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-amber-300 text-amber-700 dark:text-amber-300 hover:bg-amber-50">Analytics: scegli la proprietà</a>
                <?php elseif ($canEdit && $googleConfigured): ?>
                <a href="<?= url("{$base}/ga4/connect") ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">+ Collega Analytics</a>
                <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-500">Analytics non collegato</span>
                <?php endif; ?>
                <?php if (!empty($gscConn['property_url'])): ?>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300" title="<?= e($gscConn['property_url']) ?>">● Search Console collegata</span>
                <?php elseif ($canEdit && $googleConfigured): ?>
                <a href="<?= url("{$base}/gsc/connect?back=situation") ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">+ Collega Search Console</a>
                <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-500">Search Console non collegata</span>
                <?php endif; ?>
                <?php if ($canEdit && !empty($ga4Conn['property_id'])): ?>
                <form method="POST" action="<?= url("{$base}/ga4/disconnect") ?>" data-confirm="Scollegare Google Analytics da questo progetto?" data-confirm-button="Scollega" data-confirm-destructive="1" class="inline">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="px-2 py-1.5 text-slate-400 hover:text-red-600" title="Scollega Analytics">×</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$report): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-10 text-center text-sm text-slate-500 dark:text-slate-400">
        Collega almeno una delle due fonti, scegli il periodo e clicca "Leggi i dati": ci vuole circa un minuto.
    </div>
    <?php else: ?>

    <?php
    // $kind: 'query' (riga = ricerca, con pagina in $r['page']) o 'page' (riga = pagina, con ricerca in $r['query']) → bottoni per riga
    $rowActions = $canEdit && !empty($report);
    $iconDoc = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
    $iconEye = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
    $iconCheck = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    $btn = 'inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors';
    // Bottoni "Analizza" e "Monitora" di una ricerca ($kind = 'query', pagina in $r['page']) o di una pagina ($kind = 'page', ricerca in $r['query'])
    $actions = function (string $kind, array $r) use ($tracked, $analysisOk, $analysisCost, $rowActions, $base, $report, $fmtCr, $iconDoc, $iconEye, $iconCheck, $btn): string {
        if (!$rowActions || !$kind) {
            return '';
        }
        $kw = $kind === 'query' ? $r['key'] : ($r['query'] ?? null);
        $pageUrl = $kind === 'query' ? ($r['page'] ?? null) : $r['key'];
        $trackedId = $kind === 'query' ? ($tracked[mb_strtolower(trim($r['key']))] ?? 0) : 0;
        ob_start(); ?>
                            <span class="flex items-center gap-1 shrink-0">
                                <?php if ($kw && $pageUrl && $analysisOk): ?>
                                <button type="button" :disabled="busy" @click="analyzePage(<?= e(json_encode($kw)) ?>, <?= e(json_encode($pageUrl)) ?>, <?= (int) round((float) ($r['position_b'] ?? 0)) ?>)"
                                        class="<?= $btn ?>" title="Analizza <?= e($pageUrl) ?> per la ricerca &quot;<?= e($kw) ?>&quot; (<?= e($fmtCr($analysisCost)) ?>)"><?= $iconDoc ?>Analizza</button>
                                <?php endif; ?>
                                <?php if ($kind === 'query' && $trackedId): ?>
                                <a href="<?= url("{$base}/keywords/{$trackedId}") ?>" class="<?= $btn ?> text-emerald-600 dark:text-emerald-400" title="Già monitorata: vedi lo storico"><?= $iconCheck ?>Monitorata</a>
                                <?php elseif ($kind === 'query'): ?>
                                <form method="POST" action="<?= url("{$base}/situation/track") ?>" class="inline">
                                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="keyword" value="<?= e($r['key']) ?>">
                                    <input type="hidden" name="report" value="<?= (int) $report['id'] ?>">
                                    <button type="submit" class="<?= $btn ?>" title="Aggiungi alle keyword monitorate (gratis): seguirai la posizione nel tempo"><?= $iconEye ?>Monitora</button>
                                </form>
                                <?php endif; ?>
                            </span>
        <?php return (string) ob_get_clean();
    };
    // Ricerche e pagine delle tabelle citate in un testo (max $max): [kind, riga, etichetta]. Le pagine si riconoscono dal percorso.
    $citedRows = function (string $text, int $max = 4) use ($gsc): array {
        $out = [];
        $seen = [];
        foreach (['query' => 'queries', 'page' => 'pages'] as $kind => $block) {
            foreach (array_merge($gsc[$block]['losers'] ?? [], $gsc[$block]['gainers'] ?? []) as $r) {
                $label = $kind === 'page' ? (parse_url($r['key'], PHP_URL_PATH) ?: '/') : $r['key'];
                $path = rtrim($label, '/');
                // L'AI cita le pagine anche per nome: "/noleggio-barca-senza-patente/" → "noleggio barca senza patente", "/" → "home"
                $needles = $kind === 'query' ? [$label] : ($path === '' ? ['home'] : [$path, str_replace('-', ' ', basename($path))]);
                $hit = false;
                foreach ($needles as $needle) {
                    if (mb_strlen($needle) >= 4 && mb_stripos($text, $needle) !== false) {
                        $hit = true;
                        break;
                    }
                }
                $id = $kind . ($kind === 'page' ? $path : mb_strtolower($label));
                if (!$hit || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $out[] = [$kind, $r, $label];
            }
        }
        return array_slice($out, 0, $max);
    };
    ?>

    <!-- Testata del report + sintesi dell'AI -->
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            <?= e($d['period']['label'] ?? '') ?> · dal <?= date('d/m/Y', strtotime($d['period']['b'][0])) ?> al <?= date('d/m/Y', strtotime($d['period']['b'][1])) ?>
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            "vs prima" = <?= date('d/m', strtotime($d['period']['a'][0])) ?>–<?= date('d/m/Y', strtotime($d['period']['a'][1])) ?> · "vs anno" = stesso periodo dell'anno scorso · letto il <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
        </p>
    </div>
    <?php if ($isNew && !empty($narr['sintesi'])): ?>
    <div class="rounded-xl bg-blue-600 text-white px-5 py-4 flex items-start gap-3">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
        <p class="text-base font-medium leading-snug"><?= e($narr['sintesi']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Numeri grandi con mini-linea -->
    <div data-kpi="kpi" class="kpi-card rounded-xl transition-shadow">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php if ($ga4): $t = $ga4['totals']; ?>
            <?= $kpiTile('sessions', 'Visite', $num($t['sessions']['b']), $delta($t['sessions']['vs_prev_pct'], false, 'vs prima', $t['sessions']['b'], $t['sessions']['a']) . $delta($t['sessions']['vs_year_pct'], false, 'vs anno', $t['sessions']['b'], $t['sessions']['y']), $col($dailyGa4['b'], 'sessions'), '#3b82f6') ?>
            <?= $kpiTile('key_events', 'Conversioni', $num($t['key_events']['b']), $delta($t['key_events']['vs_prev_pct'], false, 'vs prima', $t['key_events']['b'], $t['key_events']['a']) . $delta($t['key_events']['vs_year_pct'], false, 'vs anno', $t['key_events']['b'], $t['key_events']['y']), (float) $t['key_events']['b'] > 0 ? $col($dailyGa4['b'], 'key_events') : [], '#8b5cf6') ?>
            <?php if (!empty($ga4['has_revenue'])): ?>
            <?= $kpiTile('revenue', 'Ricavi', $money($t['revenue']['b']), $delta($t['revenue']['vs_prev_pct']) . $delta($t['revenue']['vs_year_pct'], false, 'vs anno')) ?>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($gsc): $g = $gsc['totals']; ?>
            <?= $kpiTile('clicks', 'Clic da Google', $num($g['clicks']['b']), $delta($g['clicks']['vs_prev_pct'], false, 'vs prima', $g['clicks']['b'], $g['clicks']['a']) . $delta($g['clicks']['vs_year_pct'], false, 'vs anno', $g['clicks']['b'], $g['clicks']['y']), $col($dailyGsc['b'], 'clicks'), '#10b981') ?>
            <?= $kpiTile('impressions', 'Volte che compari su Google', $num($g['impressions']['b']), $delta($g['impressions']['vs_prev_pct'], false, 'vs prima', $g['impressions']['b'], $g['impressions']['a']) . $delta($g['impressions']['vs_year_pct'], false, 'vs anno', $g['impressions']['b'], $g['impressions']['y']), $col($dailyGsc['b'], 'impressions'), '#f59e0b') ?>
            <?= $kpiTile('position', 'Posizione media', $num($g['position']['b'], 1), '<span class="text-xs text-slate-400">prima ' . $num($g['position']['a'], 1) . ' · anno scorso ' . $num($g['position']['y'], 1) . '</span>', $col($dailyGsc['b'], 'position'), '#64748b') ?>
            <?= $kpiTile('brand', 'Clic che cercano il tuo nome', $gsc['brand']['quota_b_pct'] === null ? '–' : $num($gsc['brand']['quota_b_pct'], 1) . '%', '<span class="text-xs text-slate-400">prima ' . ($gsc['brand']['quota_a_pct'] === null ? '–' : $num($gsc['brand']['quota_a_pct'], 1) . '%') . '</span>') ?>
            <?php endif; ?>
        </div>
        <?php if ($r = $reading('numeri')): ?><div class="mt-3 rounded-xl border border-blue-100 dark:border-blue-900/30 overflow-hidden"><?= str_replace('border-t ', '', $r) ?></div><?php endif; ?>
    </div>

    <?php if ($hasTrend): ?>
    <!-- Andamento giorno per giorno -->
    <div data-kpi="trend" class="kpi-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-shadow">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="font-semibold text-slate-900 dark:text-white">Giorno per giorno</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Linea piena = questo periodo · tratteggiata = periodo prima, stesso giorno. Clicca una voce della legenda per nasconderla.</p>
        </div>
        <div class="p-4"><div class="relative h-64 sm:h-72"><canvas id="st-trend"></canvas></div></div>
        <?= $reading('andamento') ?>
    </div>
    <?php endif; ?>

    <?php if ($ga4 && !empty($ga4['channels'])): ?>
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
        <!-- Da dove arriva il traffico: barre per quota -->
        <div data-kpi="channels" class="kpi-card xl:col-span-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-shadow">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700"><h3 class="font-semibold text-slate-900 dark:text-white">Da dove arriva il traffico</h3></div>
            <?php $chTop = array_slice($ga4['channels'], 0, 8); ?>
            <div class="p-4"><div class="relative" style="height: <?= max(160, 34 * count($chTop) + 20) ?>px"><canvas id="st-channels"></canvas></div></div>
            <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-700/60">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <?php foreach (['Canale', 'Visite', 'vs prima', 'vs anno', 'Conversioni'] as $h): ?>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $h ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        <?php foreach ($chTop as $c): ?>
                        <tr class="<?= $c['name'] === 'AI Assistant' ? 'bg-indigo-50/60 dark:bg-indigo-900/10' : '' ?>">
                            <td class="px-4 py-2 text-sm text-slate-900 dark:text-white"><?= e($channelName($c['name'])) ?></td>
                            <td class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300"><?= $num($c['sessions']['b']) ?> <span class="text-xs text-slate-400"><?= $c['share_b_pct'] === null ? '' : $num($c['share_b_pct'], 1) . '%' ?></span></td>
                            <td class="px-4 py-2"><?= $delta($c['sessions']['vs_prev_pct'], false, '', $c['sessions']['b'], $c['sessions']['a']) ?></td>
                            <td class="px-4 py-2"><?= $delta($c['sessions']['vs_year_pct'], false, '', $c['sessions']['b'], $c['sessions']['y']) ?></td>
                            <td class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300"><?= $num($c['key_events']['b']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= $reading('canali') ?>
        </div>

        <!-- Visite dalle AI: traffico vero da ChatGPT, Perplexity, Gemini... (canale GA4 "AI Assistant"). Nessun tool lo mostra: gli altri simulano -->
        <div data-kpi="ai_traffic" class="kpi-card xl:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-indigo-200 dark:border-indigo-800 overflow-hidden transition-shadow">
            <div class="px-4 py-3 border-b border-indigo-100 dark:border-indigo-900/40 bg-indigo-50/60 dark:bg-indigo-900/10">
                <h3 class="font-semibold text-slate-900 dark:text-white">Visite dalle AI</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Persone arrivate da ChatGPT, Perplexity, Gemini, Copilot e simili: traffico vero, non simulato.</p>
            </div>
            <?php if ($ai): $aiB = (float) $ai['sessions']['b']; ?>
            <div class="p-4 space-y-4">
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $num($aiB) ?> <span class="text-sm font-normal text-slate-400"><?= $ai['share_b_pct'] === null ? 'visite' : $num($ai['share_b_pct'], 1) . '% delle visite' ?></span></p>
                    <div class="mt-1 flex flex-wrap gap-x-3"><?= $delta($ai['sessions']['vs_prev_pct'], false, 'vs prima', $aiB, $ai['sessions']['a']) ?><?= $delta($ai['sessions']['vs_year_pct'], false, 'vs anno', $aiB, $ai['sessions']['y']) ?></div>
                    <?php if ((float) $ai['key_events']['b'] > 0): ?><p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= $num($ai['key_events']['b']) ?> conversioni</p><?php endif; ?>
                </div>
                <?php if (!empty($ai['sources'])): ?>
                <div class="relative" style="height: <?= max(90, 30 * min(6, count($ai['sources'])) + 20) ?>px"><canvas id="st-ai"></canvas></div>
                <?php else: ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna visita dalle AI in questo periodo.</p>
                <?php endif; ?>
                <?php if (!empty($ai['pages'])): ?>
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Su quali pagine arrivano</p>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        <?php foreach ($ai['pages'] as $pg): ?>
                        <li class="py-1.5 flex items-center justify-between gap-3 text-sm"><span class="text-slate-800 dark:text-slate-200 truncate" title="<?= e($pg['page']) ?>"><?= e($pg['page']) ?></span><span class="text-slate-600 dark:text-slate-300 shrink-0"><?= $num($pg['sessions_b']) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if (!empty($geoProjectId)): ?>
                <a href="<?= url('/ai-reputation/project/' . (int) $geoProjectId) ?>" class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Vuoi sapere perché ti citano (o no)? Apri il GEO Audit →</a>
                <?php elseif (!empty($project['global_project_id'])): ?>
                <a href="<?= url('/projects/' . (int) $project['global_project_id']) ?>" class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Vuoi sapere perché ti citano (o no)? Attiva il GEO Audit →</a>
                <?php endif; ?>
            </div>
            <?= $reading('ai') ?>
            <?php else: ?>
            <div class="p-4 text-sm text-slate-500 dark:text-slate-400">Questo report è stato fatto prima che il riquadro esistesse: clicca "Aggiorna" per vederlo.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Tabelle "chi sale / chi scende"
    $movers = function (string $ref, string $title, array $gainers, array $losers, string $keyField, string $valB, string $valA, string $unit, string $kind = '', string $readKey = '')
        use ($num, $analysisCost, $rowActions, $fmtCr, $actions, $reading) {
        ob_start(); ?>
        <div data-kpi="<?= $ref ?>" class="kpi-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-shadow">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-baseline justify-between gap-3">
                <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($title) ?></h3>
                <?php if ($rowActions && $kind): ?>
                <span class="text-xs text-slate-400 whitespace-nowrap">Analizza: <?= e($fmtCr($analysisCost)) ?><?= $kind === 'query' ? ' · Monitora: gratis' : '' ?></span>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 md:divide-x divide-slate-200 dark:divide-slate-700">
                <?php foreach ([['Crescono', $gainers, 'text-emerald-600 dark:text-emerald-400'], ['Calano', $losers, 'text-red-600 dark:text-red-400']] as [$lbl, $rows, $cls]): ?>
                <div>
                    <p class="px-4 pt-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $lbl ?></p>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        <?php foreach ($rows as $r):
                            $posInfo = $kind && (($r['position_a'] ?? null) !== null || ($r['position_b'] ?? null) !== null)
                                ? ' · pos. ' . $num($r['position_a'], 1) . ' → ' . $num($r['position_b'], 1) : '';
                            // Le pagine si leggono meglio senza dominio: "/positano/" invece dell'URL intero (intero nel title)
                            $label = $kind === 'page' ? ((parse_url($r[$keyField], PHP_URL_PATH) ?: '/') . (parse_url($r[$keyField], PHP_URL_QUERY) ? '?…' : '')) : $r[$keyField];
                        ?>
                        <li class="px-4 py-2 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex-1 min-w-0 truncate text-slate-800 dark:text-slate-200" title="<?= e($r[$keyField]) ?>"><?= e($label) ?></span>
                                <span class="text-xs font-medium whitespace-nowrap <?= $cls ?>"><?= ($r['diff'] > 0 ? '+' : '') . $num($r['diff']) ?></span>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                <span class="text-xs text-slate-400"><?= $num($r[$valA]) ?> → <?= $num($r[$valB]) ?> <?= $unit ?><?= e($posInfo) ?></span>
                                <?= $actions($kind, $r) ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?><li class="px-4 py-2 text-sm text-slate-400">Nessuna</li><?php endif; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
            <?= $readKey ? $reading($readKey) : '' ?>
        </div>
        <?php return (string) ob_get_clean();
    };
    ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <?php if ($gsc): ?>
        <?= $movers('gsc_queries', 'Ricerche su Google (clic)', $gsc['queries']['gainers'], $gsc['queries']['losers'], 'key', 'clicks_b', 'clicks_a', 'clic', 'query', 'google') ?>
        <?= $movers('gsc_pages', 'Pagine su Google (clic)', $gsc['pages']['gainers'], $gsc['pages']['losers'], 'key', 'clicks_b', 'clicks_a', 'clic', 'page', 'pagine') ?>
        <?php endif; ?>
        <?php if ($ga4): ?>
        <?= $movers('organic_pages', 'Pagine di arrivo da Google (visite)', $ga4['organic_pages']['gainers'], $ga4['organic_pages']['losers'], 'page', 'sessions_b', 'sessions_a', 'visite') ?>
        <?php endif; ?>
    </div>

    <?php if ($isNew && !empty($narr['azioni'])): ?>
    <!-- Cosa fare adesso: 3 schede -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700"><h3 class="font-semibold text-slate-900 dark:text-white">Cosa fare adesso</h3></div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($narr['azioni'] as $i => $a): ?>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-blue-300 dark:hover:border-blue-700 transition-colors"
                 @mouseenter="highlight(<?= e(json_encode($a['ref'] ? [$a['ref']] : [])) ?>)" @mouseleave="highlight([])">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 text-xs font-bold"><?= $i + 1 ?></span>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($a['titolo'] ?: 'Azione ' . ($i + 1)) ?></h4>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($a['testo']) ?></p>
                <?php if ($rowActions && ($cited = $citedRows($a['testo'], 2))): ?>
                <div class="mt-3 space-y-2">
                    <?php foreach ($cited as [$kind, $r, $label]): ?>
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 dark:bg-slate-700/40 px-3 py-2">
                        <span class="text-xs text-slate-700 dark:text-slate-200 truncate" title="<?= e($label) ?>"><?= $kind === 'query' ? 'Ricerca' : 'Pagina' ?> <strong><?= e($label) ?></strong></span>
                        <?= $actions($kind, $r) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif (!$isNew && $narr): ?>
    <!-- Report salvato col formato vecchio: il commento in 5 paragrafi -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
        <p class="text-xs text-slate-400">Commento scritto con la versione precedente della pagina. Clicca "Aggiorna" per avere la lettura breve e le 3 azioni.</p>
        <?php foreach ($narr as $par): ?>
        <div class="rounded-lg -mx-2 px-2 py-1 hover:bg-blue-50/60 dark:hover:bg-blue-900/10 transition-colors" @mouseenter="highlight(<?= e(json_encode($par['refs'] ?? [])) ?>)" @mouseleave="highlight([])">
            <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($par['titolo']) ?></h3>
            <p class="mt-1 text-sm text-slate-700 dark:text-slate-300 leading-relaxed"><?= nl2br(e($par['testo'])) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($d['caveats'])): ?>
    <div class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2">
        <?php foreach ($d['caveats'] as $c): ?><p><?= e($c) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (count($reports) > 1 || ($reports && !$report)): ?>
    <!-- Storico -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700"><h3 class="font-semibold text-slate-900 dark:text-white">Letture precedenti</h3></div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
            <?php foreach ($reports as $r): ?>
            <li class="px-4 py-2.5 flex items-center justify-between text-sm <?= $report && (int) $report['id'] === (int) $r['id'] ? 'bg-blue-50/60 dark:bg-blue-900/10' : '' ?>">
                <a href="<?= url("{$base}/situation?report={$r['id']}") ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                    <?= e($periods[$r['period_key']] ?? $r['period_key']) ?> · <?= date('d/m/Y', strtotime($r['b_start'])) ?>–<?= date('d/m/Y', strtotime($r['b_end'])) ?>
                </a>
                <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<style>.kpi-card.kpi-on { box-shadow: 0 0 0 2px rgb(59 130 246); }</style>
<script>
function situationApp() {
    return {
        loading: false,
        busy: false,
        error: '',
        // "Analizza" su una riga: stessa Analisi pagina della scheda dedicata, poi si apre lì il risultato
        async analyzePage(keyword, url, position) {
            const ok = await window.ainstein.ask(`Analizzo ${url} per la ricerca "${keyword}": <?= e($fmtCr($analysisCost)) ?>. Ci vuole circa un minuto.`, { buttonText: 'Analizza' });
            if (!ok) return;
            this.busy = true;
            this.error = '';
            try {
                const body = new FormData();
                body.append('_csrf_token', '<?= csrf_token() ?>');
                body.append('keyword', keyword);
                body.append('url', url);
                body.append('position', position || '');
                const resp = await fetch('<?= url("{$base}/analyze-page") ?>', { method: 'POST', body });
                let data = null;
                try { data = await resp.json(); } catch (e) { data = null; }
                if (!resp.ok || !data || data.error) {
                    throw new Error((data && data.error) || `Errore server (${resp.status})`);
                }
                window.location.href = '<?= url("{$base}/page-analyzer") ?>?analysis=' + data.analysis_id;
            } catch (e) {
                this.error = e.message;
                this.busy = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        highlight(refs) {
            document.querySelectorAll('.kpi-card').forEach(el => el.classList.toggle('kpi-on', refs.includes(el.dataset.kpi)));
        },
        async generate(ev) {
            const form = ev.target;
            const label = form.period.options[form.period.selectedIndex].text;
            const ok = await window.ainstein.ask(`Leggo Analytics e Search Console (${label}) e l'AI scrive la lettura: <?= e($fmtCr($cost)) ?>.`, { buttonText: 'Leggi i dati' });
            if (!ok) return;
            this.loading = true;
            this.error = '';
            try {
                const body = new FormData();
                body.append('_csrf_token', '<?= csrf_token() ?>');
                body.append('period', form.period.value);
                const resp = await fetch('<?= url("{$base}/situation/generate") ?>', { method: 'POST', body });
                let data = null;
                try { data = await resp.json(); } catch (e) { data = null; }
                if (!resp.ok || !data || !data.success) {
                    throw new Error((data && data.message) || `Errore server (${resp.status})`);
                }
                window.location.href = '<?= url("{$base}/situation") ?>?report=' + data.report_id;
            } catch (e) {
                this.error = e.message;
                this.loading = false;
            }
        },
    };
}

<?php if ($report): ?>
// Grafici (Chart.js è nel layout). Colori sobri, griglia leggera, dark mode dal tema dell'app.
(function () {
    if (typeof Chart === 'undefined') return;
    const dark = document.documentElement.classList.contains('dark');
    const grid = dark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.25)';
    const tick = dark ? '#94a3b8' : '#64748b';
    const fmt = n => new Intl.NumberFormat('it-IT', { maximumFractionDigits: 1 }).format(n);
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    // Mini-linee nei numeri grandi
    const sparks = <?= json_encode($sparks) ?>;
    Object.entries(sparks).forEach(([id, s]) => {
        const el = document.getElementById('spark-' + id);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: { labels: s.values.map((_, i) => i), datasets: [{ data: s.values, borderColor: s.color, borderWidth: 1.5, pointRadius: 0, tension: 0.35, fill: { target: 'origin', above: s.color + '22' }, spanGaps: true }] },
            options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false, reverse: id === 'position' } }, elements: { line: { capBezierPoints: true } } },
        });
    });

    // Andamento giorno per giorno: periodo (pieno) e periodo prima (tratteggiato), allineati per giorno
    const trendEl = document.getElementById('st-trend');
    if (trendEl) {
        const ga4 = <?= json_encode($dailyGa4) ?>;
        const gsc = <?= json_encode($dailyGsc) ?>;
        const days = (ga4.b.length ? ga4.b : gsc.b).map(r => r.date);
        const label = d => { const [y, m, dd] = d.split('-'); return dd + '/' + m; };
        const series = (rows, key) => days.map((_, i) => rows[i] ? rows[i][key] : null);
        const ds = [];
        if (ga4.b.length) {
            ds.push({ label: 'Visite', data: series(ga4.b, 'sessions'), borderColor: '#3b82f6', backgroundColor: '#3b82f6', borderWidth: 2, pointRadius: 0, tension: 0.3 });
            ds.push({ label: 'Visite, periodo prima', data: series(ga4.a, 'sessions'), borderColor: '#93c5fd', borderDash: [4, 4], borderWidth: 1.5, pointRadius: 0, tension: 0.3 });
        }
        if (gsc.b.length) {
            ds.push({ label: 'Clic da Google', data: series(gsc.b, 'clicks'), borderColor: '#10b981', backgroundColor: '#10b981', borderWidth: 2, pointRadius: 0, tension: 0.3 });
            ds.push({ label: 'Clic, periodo prima', data: series(gsc.a, 'clicks'), borderColor: '#6ee7b7', borderDash: [4, 4], borderWidth: 1.5, pointRadius: 0, tension: 0.3, hidden: ga4.b.length > 0 });
        }
        new Chart(trendEl, {
            type: 'line',
            data: { labels: days.map(label), datasets: ds },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { color: tick, boxWidth: 12, usePointStyle: true, pointStyle: 'line' } },
                    tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + fmt(c.parsed.y) } } },
                scales: { x: { grid: { display: false }, ticks: { color: tick, maxTicksLimit: 14, maxRotation: 0 } },
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } } } },
        });
    }

    // Canali: barre orizzontali per quota, le AI in indaco
    const chEl = document.getElementById('st-channels');
    if (chEl) {
        const ch = <?= json_encode(array_map(fn($c) => ['name' => $channelName($c['name']), 'ai' => $c['name'] === 'AI Assistant', 'share' => $c['share_b_pct'] ?? 0, 'sessions' => $c['sessions']['b']], $chTop ?? [])) ?>;
        new Chart(chEl, {
            type: 'bar',
            data: { labels: ch.map(c => c.name), datasets: [{ data: ch.map(c => c.share), backgroundColor: ch.map(c => c.ai ? '#6366f1' : '#3b82f6'), borderRadius: 4, barThickness: 18 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + fmt(ch[c.dataIndex].sessions) + ' visite · ' + fmt(c.parsed.x) + '%' } } },
                scales: { x: { beginAtZero: true, max: 100, grid: { color: grid }, ticks: { color: tick, callback: v => v + '%' } }, y: { grid: { display: false }, ticks: { color: tick } } } },
        });
    }

    // Visite dalle AI per sorgente: ora e periodo prima
    const aiEl = document.getElementById('st-ai');
    if (aiEl) {
        const src = <?= json_encode(array_slice($ai['sources'] ?? [], 0, 6)) ?>;
        new Chart(aiEl, {
            type: 'bar',
            data: { labels: src.map(s => s.name), datasets: [
                { label: 'Questo periodo', data: src.map(s => s.sessions.b), backgroundColor: '#6366f1', borderRadius: 4, barThickness: 10 },
                { label: 'Periodo prima', data: src.map(s => s.sessions.a), backgroundColor: '#c7d2fe', borderRadius: 4, barThickness: 10 },
            ] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: tick, boxWidth: 10 } }, tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + fmt(c.parsed.x) } } },
                scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } }, y: { grid: { display: false }, ticks: { color: tick } } } },
        });
    }
})();
<?php endif; ?>
</script>
