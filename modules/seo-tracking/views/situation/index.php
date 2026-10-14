<?php
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
    $small = $now !== null && max(abs((float) $now), abs((float) $before)) < \Modules\SeoTracking\Services\SituationService::SMALL_BASE;
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
    'AI Assistant' => 'Assistenti AI (ChatGPT, Gemini…)', 'Display' => 'Banner (display)', 'Cross-network' => 'Campagne Google (più reti)', 'Unassigned' => 'Non classificato', 'Affiliates' => 'Affiliati', 'SMS' => 'SMS',
][$n] ?? $n;
$ga4 = $d['ga4'] ?? null;
$gsc = $d['gsc'] ?? null;
$kpiCard = function (string $ref, string $label, string $value, string $sub) {
    return '<div data-kpi="' . $ref . '" class="kpi-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 transition-shadow">'
        . '<p class="text-sm text-slate-500 dark:text-slate-400">' . e($label) . '</p>'
        . '<p class="text-2xl font-bold text-slate-900 dark:text-white">' . $value . '</p>'
        . '<div class="mt-1 flex flex-wrap gap-x-3">' . $sub . '</div></div>';
};
?>
<div class="space-y-6" x-data="situationApp()">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Intro + collegamenti -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
            <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Il punto della situazione del tuo sito</h2>
            <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                Leggo Google Analytics e Search Console, confronto il periodo con quello precedente e con l'anno prima, e l'AI ti spiega
                in parole semplici da dove arriva il traffico, cosa cresce, cosa cala, le criticità e le 3 cose da fare adesso.
            </p>
            <?php if ($canEdit): ?>
            <form class="mt-4 flex flex-wrap items-center gap-3" @submit.prevent="generate($event)">
                <select name="period" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm py-2">
                    <?php foreach ($periods as $k => $label): ?>
                    <option value="<?= e($k) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" :disabled="loading" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-60 transition-colors">
                    <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="loading ? 'Leggo i dati e scrivo il commento…' : 'Fai il punto'"></span>
                </button>
                <span class="text-xs text-blue-700 dark:text-blue-300"><?= $fmtCr($cost) ?> · ne hai <?= $num($balance, 1) ?></span>
            </form>
            <p x-show="error" x-cloak x-text="error" class="mt-3 text-sm text-red-600 dark:text-red-400"></p>
            <?php endif; ?>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Google Analytics 4</p>
                <?php if (!empty($ga4Conn['property_id'])): ?>
                <p class="mt-1 text-sm text-slate-900 dark:text-white">Collegato: <?= e($ga4Conn['property_name'] ?: $ga4Conn['property_id']) ?></p>
                <?php if ($canEdit): ?>
                <form method="POST" action="<?= url("{$base}/ga4/disconnect") ?>" data-confirm="Scollegare Google Analytics da questo progetto?" data-confirm-button="Scollega" data-confirm-destructive="1" class="mt-1">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="text-xs text-slate-500 hover:text-red-600">Scollega</button>
                </form>
                <?php endif; ?>
                <?php elseif (!empty($ga4Conn)): ?>
                <a href="<?= url("{$base}/ga4/select-property") ?>" class="mt-1 inline-block text-sm text-blue-600 dark:text-blue-400 hover:underline">Scegli la proprietà</a>
                <?php elseif ($canEdit && $googleConfigured): ?>
                <a href="<?= url("{$base}/ga4/connect") ?>" class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Collega Google Analytics</a>
                <?php else: ?>
                <p class="mt-1 text-sm text-slate-500">Non collegato</p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Search Console</p>
                <?php if (!empty($gscConn['property_url'])): ?>
                <p class="mt-1 text-sm text-slate-900 dark:text-white break-all">Collegata: <?= e($gscConn['property_url']) ?></p>
                <?php elseif ($canEdit && $googleConfigured): ?>
                <a href="<?= url("{$base}/gsc/connect?back=situation") ?>" class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Collega Search Console</a>
                <?php else: ?>
                <p class="mt-1 text-sm text-slate-500">Non collegata</p>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-400">Basta una delle due: con entrambe il punto è completo.</p>
        </div>
    </div>

    <?php if (!$report): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-10 text-center text-sm text-slate-500 dark:text-slate-400">
        Ancora nessun punto della situazione. Collega almeno una fonte, scegli il periodo e clicca "Fai il punto".
    </div>
    <?php else: ?>

    <!-- Testata del report -->
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            <?= e($d['period']['label'] ?? '') ?> · dal <?= date('d/m/Y', strtotime($d['period']['b'][0])) ?> al <?= date('d/m/Y', strtotime($d['period']['b'][1])) ?>
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Confronto con <?= date('d/m', strtotime($d['period']['a'][0])) ?>–<?= date('d/m/Y', strtotime($d['period']['a'][1])) ?> ("vs prima") e con lo stesso periodo dell'anno scorso ("vs anno") · fatto il <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
        </p>
    </div>

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
    // Ricerche e pagine delle tabelle che l'AI cita nel testo (max 4): [kind, riga, etichetta]. Le pagine si riconoscono dal percorso.
    $citedRows = function (string $text) use ($gsc): array {
        $out = [];
        $seen = [];
        foreach (['query' => 'queries', 'page' => 'pages'] as $kind => $block) {
            foreach (array_merge($gsc[$block]['losers'] ?? [], $gsc[$block]['gainers'] ?? []) as $r) {
                $label = $kind === 'page' ? (parse_url($r['key'], PHP_URL_PATH) ?: '/') : $r['key'];
                $path = rtrim($label, '/');
                // L'AI cita le pagine anche per nome: "/noleggio-barca-senza-patente/" → "noleggio barca senza patente", "/" → "home"
                $needles = $kind === 'query' ? [$label] : ($path === ''
                    ? ['home']
                    : [$path, str_replace('-', ' ', basename($path))]);
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
        return array_slice($out, 0, 4);
    };
    ?>
    <!-- Commento AI -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
        <?php foreach ($report['narrative'] as $par): ?>
        <div class="rounded-lg -mx-2 px-2 py-1 hover:bg-blue-50/60 dark:hover:bg-blue-900/10 transition-colors"
             @mouseenter="highlight(<?= e(json_encode($par['refs'] ?? [])) ?>)" @mouseleave="highlight([])">
            <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($par['titolo']) ?></h3>
            <p class="mt-1 text-sm text-slate-700 dark:text-slate-300 leading-relaxed"><?= nl2br(e($par['testo'])) ?></p>
            <?php if ($rowActions && stripos($par['titolo'], 'cosa fare') !== false && ($cited = $citedRows($par['testo']))): ?>
            <div class="mt-3 space-y-2">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Parti da qui</p>
                <?php foreach ($cited as [$kind, $r, $label]): ?>
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 dark:bg-slate-700/40 px-3 py-2">
                    <span class="text-sm text-slate-800 dark:text-slate-200"><?= $kind === 'query' ? 'Ricerca' : 'Pagina' ?> <strong><?= e($label) ?></strong></span>
                    <?= $actions($kind, $r) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (!empty($d['caveats'])): ?>
        <div class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2">
            <?php foreach ($d['caveats'] as $c): ?><p><?= e($c) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Numeri -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php if ($ga4): $t = $ga4['totals']; ?>
        <?= $kpiCard('ga4_sessions', 'Visite (tutto il sito)', $num($t['sessions']['b']), $delta($t['sessions']['vs_prev_pct'], false, 'vs prima', $t['sessions']['b'], $t['sessions']['a']) . $delta($t['sessions']['vs_year_pct'], false, 'vs anno', $t['sessions']['b'], $t['sessions']['y'])) ?>
        <?= $kpiCard('ga4_key_events', 'Conversioni', $num($t['key_events']['b']), $delta($t['key_events']['vs_prev_pct'], false, 'vs prima', $t['key_events']['b'], $t['key_events']['a']) . $delta($t['key_events']['vs_year_pct'], false, 'vs anno', $t['key_events']['b'], $t['key_events']['y'])) ?>
        <?php if (!empty($ga4['has_revenue'])): ?>
        <?= $kpiCard('ga4_revenue', 'Ricavi', $money($t['revenue']['b']), $delta($t['revenue']['vs_prev_pct']) . $delta($t['revenue']['vs_year_pct'], false, 'vs anno')) ?>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($gsc): $g = $gsc['totals']; ?>
        <?= $kpiCard('gsc_clicks', 'Clic da Google', $num($g['clicks']['b']), $delta($g['clicks']['vs_prev_pct'], false, 'vs prima', $g['clicks']['b'], $g['clicks']['a']) . $delta($g['clicks']['vs_year_pct'], false, 'vs anno', $g['clicks']['b'], $g['clicks']['y'])) ?>
        <?= $kpiCard('gsc_impressions', 'Volte che compari su Google', $num($g['impressions']['b']), $delta($g['impressions']['vs_prev_pct'], false, 'vs prima', $g['impressions']['b'], $g['impressions']['a']) . $delta($g['impressions']['vs_year_pct'], false, 'vs anno', $g['impressions']['b'], $g['impressions']['y'])) ?>
        <?= $kpiCard('gsc_position', 'Posizione media su Search Console', $num($g['position']['b'], 1), '<span class="text-xs text-slate-400">prima ' . $num($g['position']['a'], 1) . ' · anno scorso ' . $num($g['position']['y'], 1) . '</span>') ?>
        <?= $kpiCard('gsc_brand', 'Clic che cercano il tuo nome', $gsc['brand']['quota_b_pct'] === null ? '–' : $num($gsc['brand']['quota_b_pct'], 1) . '%', '<span class="text-xs text-slate-400">prima ' . ($gsc['brand']['quota_a_pct'] === null ? '–' : $num($gsc['brand']['quota_a_pct'], 1) . '%') . '</span>') ?>
        <?php endif; ?>
    </div>

    <?php if ($ga4 && !empty($ga4['channels'])): ?>
    <!-- Sorgenti di traffico -->
    <div data-kpi="channels" class="kpi-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-shadow">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700"><h3 class="font-semibold text-slate-900 dark:text-white">Da dove arriva il traffico</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <?php foreach (['Canale', 'Visite', 'Peso', 'vs prima', 'vs anno', 'Conversioni'] as $h): ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach (array_slice($ga4['channels'], 0, 10) as $c): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?= e($channelName($c['name'])) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300"><?= $num($c['sessions']['b']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300"><?= $c['share_b_pct'] === null ? '–' : $num($c['share_b_pct'], 1) . '%' ?></td>
                        <td class="px-4 py-3"><?= $delta($c['sessions']['vs_prev_pct'], false, '', $c['sessions']['b'], $c['sessions']['a']) ?></td>
                        <td class="px-4 py-3"><?= $delta($c['sessions']['vs_year_pct'], false, '', $c['sessions']['b'], $c['sessions']['y']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300"><?= $num($c['key_events']['b']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Tabelline "chi sale / chi scende"
    $movers = function (string $ref, string $title, array $gainers, array $losers, string $keyField, string $valB, string $valA, string $unit, string $kind = '')
        use ($num, $analysisCost, $rowActions, $fmtCr, $actions) {
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
                        ?>
                        <?php // Le pagine si leggono meglio senza dominio: "/positano/" invece dell'URL intero (intero nel title)
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
        </div>
        <?php return (string) ob_get_clean();
    };
    ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <?php if ($gsc): ?>
        <?= $movers('gsc_queries', 'Ricerche su Google (clic)', $gsc['queries']['gainers'], $gsc['queries']['losers'], 'key', 'clicks_b', 'clicks_a', 'clic', 'query') ?>
        <?= $movers('gsc_pages', 'Pagine su Google (clic)', $gsc['pages']['gainers'], $gsc['pages']['losers'], 'key', 'clicks_b', 'clicks_a', 'clic', 'page') ?>
        <?php endif; ?>
        <?php if ($ga4): ?>
        <?= $movers('organic_pages', 'Pagine di arrivo da Google (visite)', $ga4['organic_pages']['gainers'], $ga4['organic_pages']['losers'], 'page', 'sessions_b', 'sessions_a', 'visite') ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (count($reports) > 1 || ($reports && !$report)): ?>
    <!-- Storico -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700"><h3 class="font-semibold text-slate-900 dark:text-white">Punti precedenti</h3></div>
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
            const ok = await window.ainstein.ask(`Faccio il punto della situazione (${label}): <?= e($fmtCr($cost)) ?>.`, { buttonText: 'Fai il punto' });
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
</script>
