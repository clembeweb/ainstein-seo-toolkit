<?php
$currentPage = 'dashboard';
include __DIR__ . '/../partials/project-nav.php';
$basePath = '/ai-reputation/project/' . $project['id'];
$csrf = csrf_token();
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
$statusLabel = match ($run['status']) {
    'completed' => 'Completato', 'running' => 'In corso', 'failed' => 'Fallito', 'cancelled' => 'Annullato', default => 'In attesa',
};
$clusterClass = fn(string $c) => match ($c) {
    'rep' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
    'comm' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'comp' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
};
$verdictChip = function (?array $a, ?bool $mentioned): array {
    if (!$a) {
        return [$mentioned ? 'citato' : 'non citato', $mentioned ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'];
    }
    if ($a['outcome'] === 'clarification_requested') {
        return ['chiede chiarimenti', 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300'];
    }
    if ($a['outcome'] === 'refused') {
        return ['rifiuta', 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200'];
    }
    $suffix = $a['is_homonym'] === 'uncertain' ? ' · omonimo?' : '';
    return match ($a['verdict']) {
        'negative' => ['negativo' . $suffix, 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
        'mixed' => ['misto' . $suffix, 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300'],
        'positive' => ['positivo' . $suffix, 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
        'neutral' => ['neutro' . $suffix, 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300'],
        default => ['non citato', 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
    };
};
$riskClass = match ($metrics['risk_label']) {
    'Alto' => 'text-red-600 dark:text-red-400',
    'Medio' => 'text-amber-600 dark:text-amber-400',
    'Basso' => 'text-emerald-600 dark:text-emerald-400',
    default => 'text-slate-400',
};
$actionLabel = ['removal' => 'rimozione', 'counter_content' => 'contro-contenuto', 'gap_article' => 'articolo gap', 'correction' => 'correzione'];
$actionClass = ['removal' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'counter_content' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300', 'gap_article' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300', 'correction' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'];
$isActive = in_array($run['status'], ['pending', 'running'], true);
?>

<div class="space-y-6" x-data="arReport()">
    <!-- Header report -->
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="<?= url($basePath) ?>" class="text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400">← Overview</a>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">Report run #<?= (int) $run['id'] ?> · <?= date('d/m/Y H:i', strtotime($run['created_at'])) ?></h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?= (int) $run['prompts_total'] ?> domande · <?= count($engines) ?> engine · <?= (int) $run['responses_total'] ?> risposte · <?= $statusLabel ?>
                <?php if ($hasAnalyses): ?> · <?= $metrics['judged'] ?> analizzate<?php endif; ?>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php foreach ($engines as $engine): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"><?= e($engineLabels[$engine] ?? $engine) ?></span>
            <?php endforeach; ?>
            <?php if ($hasAnalyses && !empty($actions)): ?>
            <a href="<?= url("{$basePath}/runs/{$run['id']}/export/plan.pdf") ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 text-sm font-medium transition-colors" title="Scarica il piano degli interventi in PDF">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Esporta PDF
            </a>
            <?php endif; ?>
            <?php if ($canEdit && !$isActive): ?>
            <button type="button" x-show="!analyzing" @click="analyze(<?= $hasAnalyses ? 'true' : 'false' ?>)" class="inline-flex items-center px-3 py-1.5 rounded-lg <?= $hasAnalyses ? 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700' : 'bg-indigo-600 text-white hover:bg-indigo-700' ?> text-sm font-medium transition-colors">
                <?= $hasAnalyses ? 'Rianalizza' : 'Analizza le risposte' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Avanzamento analisi -->
    <div x-show="analyzing || message" x-cloak class="bg-indigo-50/60 dark:bg-indigo-900/10 rounded-xl border border-indigo-100 dark:border-indigo-900/40 px-5 py-4">
        <div class="flex items-center justify-between text-sm mb-2">
            <span class="text-slate-700 dark:text-slate-200" x-text="message || ('Analisi in corso: ' + done + ' / ' + total)"></span>
            <span class="text-slate-500 dark:text-slate-400" x-text="percent + '%'"></span>
        </div>
        <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden"><div class="h-2 bg-indigo-600 transition-all" :style="'width:' + percent + '%'"></div></div>
        <ul class="mt-2 space-y-0.5 text-xs text-slate-600 dark:text-slate-300 max-h-32 overflow-y-auto"><template x-for="l in log" :key="l.id"><li x-text="l.text"></li></template></ul>
    </div>

    <?php if (!$hasAnalyses && !$isActive): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 px-5 py-4 text-sm text-amber-800 dark:text-amber-200">
        Risposte raccolte ma non ancora analizzate: i verdetti, il rischio e il piano d'azione compaiono dopo "Analizza le risposte" (una chiamata AI per risposta).
    </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">AI share of voice</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $hasAnalyses ? $metrics['share'] . '%' : '–' ?></p>
            <p class="text-xs text-slate-400"><?= $hasAnalyses ? "citato in {$metrics['mentioned_n']} risposte neutre su {$metrics['judged']} (pesate per engine)" : 'dopo l\'analisi' ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Rischio reputazione</p>
            <p class="text-2xl font-bold <?= $riskClass ?>"><?= $hasAnalyses ? $metrics['risk_label'] : '–' ?> <?php if ($hasAnalyses): ?><span class="text-sm font-normal text-slate-400"><?= $metrics['risk'] ?>%</span><?php endif; ?></p>
            <p class="text-xs text-slate-400"><?= $hasAnalyses ? e($metrics['risk_basis']) . ' sono negative · ' . $metrics['negative_domains'] . ' fonti negative' : 'dopo l\'analisi' ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Risposte negative (domande neutre)</p>
            <p class="text-2xl font-bold <?= $metrics['negative'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' ?>"><?= $hasAnalyses ? $metrics['negative'] : '–' ?> <?php if ($hasAnalyses): ?><span class="text-sm font-normal text-slate-400">/ <?= $metrics['judged'] ?></span><?php endif; ?></p>
            <?php if ($hasAnalyses && !empty($metrics['contradictions']['total'])): ?>
            <p class="text-xs text-amber-600 dark:text-amber-400" title="Risposte che affermano cose in contrasto con il profilo confermato (es. 'nessun procedimento' quando la confisca è confermata)"><?= (int) $metrics['contradictions']['total'] ?> in contrasto con i fatti confermati (<?= e(implode(', ', array_map(fn($k, $v) => ($engineLabels[$k] ?? $k) . ' ' . $v, array_keys($metrics['contradictions']['by_engine']), $metrics['contradictions']['by_engine']))) ?>)</p>
            <?php endif; ?>
            <p class="text-xs text-slate-400"><?php if ($hasAnalyses && $metrics['negative_by_engine']): ?><?= e(implode(' · ', array_map(fn($k, $v) => ($engineLabels[$k] ?? $k) . ' ' . $v, array_keys($metrics['negative_by_engine']), $metrics['negative_by_engine']))) ?><?php elseif ($hasAnalyses): ?>nessuna<?php else: ?>dopo l'analisi<?php endif; ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Divergenza tra engine</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $hasAnalyses ? count($metrics['divergent']) : '–' ?> <?php if ($hasAnalyses): ?><span class="text-sm font-normal text-slate-400">domande</span><?php endif; ?></p>
            <p class="text-xs text-slate-400"><?= $hasAnalyses ? ($metrics['divergent'] ? 'le AI non sono d\'accordo' : 'le AI concordano') : 'dopo l\'analisi' ?> · costo API <?= number_format((float) $run['cost_total'], 3) ?> $</p>
            <?php if (!empty($metrics['stability'])): ?>
            <p class="text-xs text-slate-400" title="Domande poste più volte allo stesso engine: in quante l'esito è stato sempre lo stesso">stabilità ripetizioni: <?= (int) $metrics['stability']['stable'] ?> su <?= (int) $metrics['stability']['cells'] ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // ---- dati per la parte centrale: interventi raggruppati, filtri della griglia ----
    $removals = array_values(array_filter($actions, fn($a) => $a['type'] === 'removal'));
    $writes = array_values(array_filter($actions, fn($a) => $a['type'] !== 'removal'));
    $divergentIds = array_flip(array_map(fn($d) => (int) $d['prompt_id'], $metrics['divergent'] ?? []));
    $rowFlags = [];
    foreach ($grid as $pid => $row) {
        $neg = false;
        foreach ($row['cells'] as $cells) {
            foreach ($cells as $r) {
                if (!empty($r['analysis']) && (int) $r['analysis']['negative'] === 1) {
                    $neg = true;
                }
            }
        }
        $rowFlags[$pid] = ['neg' => $neg, 'div' => isset($divergentIds[(int) $pid])];
    }
    $countRep = count(array_filter($grid, fn($r) => $r['prompt']['cluster'] === 'rep'));
    $countNeg = count(array_filter($rowFlags, fn($f) => $f['neg']));
    $countDiv = count($divergentIds);
    $ld = $metrics['leading'] ?? ['total' => 0];
    $actionRow = function (array $a) use ($actionClass, $actionLabel): string {
        $urls = array_values(array_filter((array) (json_decode((string) ($a['target_urls'] ?? ''), true) ?: ($a['target_url'] ? [$a['target_url']] : [])), fn($u) => preg_match('#^https?://#i', (string) $u)));
        ob_start(); ?>
        <li class="px-5 py-2.5" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="w-full flex items-center gap-3 text-left">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap <?= $actionClass[$a['type']] ?? '' ?>"><?= $actionLabel[$a['type']] ?? $a['type'] ?></span>
                <span class="flex-1 min-w-0 truncate text-sm font-medium text-slate-900 dark:text-white"><?= e($a['title']) ?></span>
                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </button>
            <div x-show="open" x-cloak class="mt-2 ml-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 text-xs space-y-1">
                <p class="text-slate-600 dark:text-slate-300"><?= e((string) $a['rationale']) ?></p>
                <?php foreach (array_slice($urls, 0, 8) as $u): ?>
                <a href="<?= e($u) ?>" target="_blank" rel="noopener" class="block text-indigo-600 dark:text-indigo-400 hover:underline break-all"><?= e(mb_strimwidth($u, 0, 120, '…')) ?></a>
                <?php endforeach; ?>
                <?php if (count($urls) > 8): ?><p class="text-slate-400">+<?= count($urls) - 8 ?> pagine</p><?php endif; ?>
            </div>
        </li>
        <?php return (string) ob_get_clean();
    };
    ?>

    <!-- Interventi suggeriti: subito sotto i numeri -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700" x-data="{ allRem: false }">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Interventi suggeriti</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= count($writes) ?> contenuti da pubblicare · <?= count($removals) ?> siti da contattare · clicca una riga per i dettagli</p>
        </div>
        <?php if (empty($actions)): ?>
        <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400"><?= $hasAnalyses ? 'Nessun intervento necessario: nessun contenuto negativo e soggetto citato dove conta.' : 'Compaiono dopo l\'analisi.' ?></div>
        <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 lg:divide-x divide-slate-200 dark:divide-slate-700">
            <div>
                <p class="px-5 pt-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Da pubblicare</p>
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    <?php foreach ($writes as $a): ?><?= $actionRow($a) ?><?php endforeach; ?>
                    <?php if (!$writes): ?><li class="px-5 py-3 text-sm text-slate-400">Nessuno</li><?php endif; ?>
                </ul>
            </div>
            <div>
                <p class="px-5 pt-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Da far rimuovere o aggiornare</p>
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    <?php foreach ($removals as $i => $a): ?>
                    <?php if ($i >= 6): ?><template x-if="allRem"><div><?= $actionRow($a) ?></div></template><?php else: ?><?= $actionRow($a) ?><?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$removals): ?><li class="px-5 py-3 text-sm text-slate-400">Nessuno</li><?php endif; ?>
                </ul>
                <?php if (count($removals) > 6): ?>
                <button type="button" @click="allRem = !allRem" class="px-5 py-2.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline" x-text="allRem ? 'Mostra meno' : 'Mostra tutti i <?= count($removals) ?> siti'"></button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Dettaglio in schede: niente scroll infinito -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700" x-data="{ tab: 'answers', filter: 'rep' }">
        <div class="border-b border-slate-200 dark:border-slate-700 px-3 flex flex-wrap gap-1">
            <?php
            $tabs = ['answers' => 'Risposte delle AI', 'sources' => 'Fonti (' . count($sources) . ')'];
            if (!empty($competitors)) $tabs['competitors'] = 'Chi citano al posto suo (' . count($competitors) . ')';
            if (!empty($homonyms)) $tabs['homonyms'] = 'Da confermare (' . count($homonyms) . ')';
            ?>
            <?php foreach ($tabs as $k => $label): ?>
            <button type="button" @click="tab = '<?= $k ?>'" class="px-3 py-3 text-sm font-medium border-b-2 -mb-px transition-colors" :class="tab === '<?= $k ?>' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                <?= e($label) ?><?php if ($k === 'homonyms'): ?><span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-500 align-middle"></span><?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Scheda: risposte -->
        <div x-show="tab === 'answers'">
            <div class="px-5 py-3 flex flex-wrap items-center gap-2 border-b border-slate-100 dark:border-slate-700/60">
                <?php foreach (['rep' => "Reputazione ({$countRep})", 'div' => "Le AI non concordano ({$countDiv})", 'neg' => "Con risposte negative ({$countNeg})", 'all' => 'Tutte (' . count($grid) . ')'] as $k => $label): ?>
                <button type="button" @click="filter = '<?= $k ?>'" class="px-2.5 py-1 rounded-full text-xs font-medium transition-colors" :class="filter === '<?= $k ?>' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"><?= e($label) ?></button>
                <?php endforeach; ?>
                <?php if ($hasAnalyses && ($ld['total'] ?? 0) > 0): ?>
                <span class="ml-auto text-xs text-slate-500 dark:text-slate-400" title="Domande che nominano già un fatto negativo: fuori dal rischio">Domande "mirate": <?= (int) $ld['negative'] ?> negative su <?= (int) $ld['total'] ?></span>
                <?php endif; ?>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-2/5">Domanda</th>
                            <?php foreach ($engines as $engine): ?>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= e($engineLabels[$engine] ?? $engine) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($grid as $pid => $row): ?>
                        <?php $f = $rowFlags[$pid]; $cl = $row['prompt']['cluster']; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 align-top"
                            x-show="filter === 'all' || (filter === 'rep' && <?= $cl === 'rep' ? 'true' : 'false' ?>) || (filter === 'div' && <?= $f['div'] ? 'true' : 'false' ?>) || (filter === 'neg' && <?= $f['neg'] ? 'true' : 'false' ?>)">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-1 <?= $clusterClass($cl) ?>"><?= strtoupper($cl) ?></span>
                                <?php if (!empty($row['prompt']['leading'])): ?><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-1 bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200" title="Nomina già un fatto negativo: esclusa dal rischio">mirata</span><?php endif; ?>
                                <?php if ($f['div']): ?><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-1 bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300" title="Almeno un'AI risponde in negativo e almeno una no">disaccordo</span><?php endif; ?>
                                <?= e($row['prompt']['text']) ?>
                            </td>
                            <?php foreach ($engines as $engine): ?>
                            <td class="px-4 py-3">
                                <?php $cells = $row['cells'][$engine] ?? []; ?>
                                <div class="flex flex-wrap gap-1">
                                <?php foreach ($cells as $r): ?>
                                    <?php if (in_array($r['status'], ['pending', 'processing'], true)): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">in attesa</span>
                                    <?php elseif ($r['status'] === 'error'): ?>
                                    <button type="button" @click="open(<?= (int) $r['id'] ?>)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300" title="<?= e((string) $r['error_message']) ?>">errore</button>
                                    <?php else: ?>
                                    <?php [$label, $cls] = $verdictChip($r['analysis'], $r['mentioned']); ?>
                                    <button type="button" @click="open(<?= (int) $r['id'] ?>)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium hover:ring-2 hover:ring-indigo-300 <?= $cls ?>" title="<?= e((string) ($r['analysis']['summary'] ?? '')) ?>"><?= e($label) ?></button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </div>
                                <?php if (count($cells) === 1 && !empty($cells[0]['analysis']['summary'])): ?>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 max-w-[14rem] line-clamp-2"><?= e($cells[0]['analysis']['summary']) ?></p>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="px-5 py-2.5 text-xs text-slate-400 border-t border-slate-100 dark:border-slate-700/60">Clicca un esito per leggere risposta, giudizio e fonti. Più esiti nella stessa cella = ripetizioni.</p>
        </div>

        <!-- Scheda: fonti -->
        <div x-show="tab === 'sources'" x-cloak x-data="{ allSrc: false }">
            <?php if (empty($sources)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna fonte citata.</div>
            <?php else: ?>
            <p class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-700/60">"Negativa" = sostiene fatti negativi · "rumore" = non parla del soggetto.</p>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sito</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Citazioni</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">AI che lo citano</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($sources as $i => $d): ?>
                        <?php [$sl, $sc] = match ($d['status']) {
                            'negative' => ['negativa', 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
                            'noise' => ['rumore', 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'],
                            default => [$hasAnalyses ? 'ok' : '–', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
                        }; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50" <?= $i >= 10 ? 'x-show="allSrc"' : '' ?>>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                <?php $firstUrl = (string) array_key_first($d['urls']); ?>
                                <a href="<?= e($firstUrl) ?>" target="_blank" rel="noopener" class="hover:underline" title="<?= e((string) ($d['urls'][$firstUrl]['title'] ?? '')) ?>"><?= e($d['domain']) ?></a>
                                <?php if (count($d['urls']) > 1): ?><span class="text-xs font-normal text-slate-400"> · <?= count($d['urls']) ?> pagine</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= (int) $d['count'] ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sc ?>"><?= $sl ?></span></td>
                            <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400"><?= e(implode(', ', array_map(fn($en) => $engineLabels[$en] ?? $en, array_keys($d['engines'])))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($sources) > 10): ?>
            <button type="button" @click="allSrc = !allSrc" class="px-5 py-2.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline" x-text="allSrc ? 'Mostra meno' : 'Mostra tutti i <?= count($sources) ?> siti'"></button>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($competitors)): ?>
        <!-- Scheda: concorrenti -->
        <div x-show="tab === 'competitors'" x-cloak class="px-5 py-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Nomi che le AI propongono quando la domanda è commerciale o competitiva. Il numero è quante volte compaiono.</p>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($competitors as $c): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200"><?= e($c['name']) ?> <span class="ml-1 text-slate-400"><?= (int) $c['count'] ?></span></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($homonyms)): ?>
        <!-- Scheda: omonimi da confermare (ADR-008) -->
        <div x-show="tab === 'homonyms'" x-cloak class="px-5 py-4">
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Le AI hanno attribuito al nome fatti che non tornano col profilo. Il tool non decide: dillo tu.</p>
            <ul class="space-y-2">
                <?php foreach ($homonyms as $h): ?>
                <li class="flex flex-wrap items-center justify-between gap-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-4 py-3 text-sm">
                    <span class="text-slate-800 dark:text-slate-100"><?= e($h['text']) ?></span>
                    <?php if ($canEdit): ?>
                    <span class="flex gap-2">
                        <form method="POST" action="<?= url("{$basePath}/profile/facts/{$h['id']}/homonym-yes") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><input type="hidden" name="back" value="<?= e("{$basePath}/runs/{$run['id']}") ?>"><button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-medium hover:bg-slate-700">Sì, è lui</button></form>
                        <form method="POST" action="<?= url("{$basePath}/profile/facts/{$h['id']}/homonym-no") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><input type="hidden" name="back" value="<?= e("{$basePath}/runs/{$run['id']}") ?>"><button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">No, è un altro</button></form>
                    </span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal risposta -->
    <div x-show="current" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60" @click.self="current = null" @keydown.escape.window="current = null">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[85vh] flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="current && (labels[current.engine] || current.engine) + ' · ' + (current.model || '')"></p>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white" x-text="current && current.prompt"></h3>
                </div>
                <button type="button" @click="current = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 overflow-y-auto space-y-4 text-sm">
                <template x-if="current && current.status === 'error'"><div class="p-3 rounded-lg bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300" x-text="current.error"></div></template>
                <template x-if="current && current.status === 'ok'">
                    <div class="space-y-4">
                        <template x-if="current.analysis">
                            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 space-y-1">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Giudizio</p>
                                <p class="text-slate-800 dark:text-slate-100" x-text="current.analysis.summary"></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'Verdetto: ' + current.analysis.verdict + ' · sentiment ' + current.analysis.sentiment + (current.analysis.is_homonym !== 'no' ? ' · omonimia: ' + current.analysis.is_homonym : '') + (current.analysis.outcome !== 'answered' ? ' · esito: ' + current.analysis.outcome : '')"></p>
                                <p class="text-xs text-amber-700 dark:text-amber-300" x-show="current.analysis.homonym_note" x-text="current.analysis.homonym_note"></p>
                                <p class="text-xs text-amber-700 dark:text-amber-300" x-show="current.analysis.contradiction_note" x-text="'In contrasto con i fatti confermati: ' + current.analysis.contradiction_note"></p>
                                <ul class="text-xs text-red-700 dark:text-red-300 list-disc ml-4" x-show="current.analysis.negative_reasons.length"><template x-for="n in current.analysis.negative_reasons" :key="n"><li x-text="n"></li></template></ul>
                            </div>
                        </template>
                        <div class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed" x-text="current.text"></div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Fonti citate (<span x-text="current.citations.length"></span>)</p>
                            <ul class="space-y-1">
                                <template x-for="c in current.citations" :key="c.url">
                                    <li>
                                        <a :href="c.url" target="_blank" rel="noopener" class="hover:underline break-all" :class="current.analysis && current.analysis.negative_urls.includes(c.url) ? 'text-red-600 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400'"><span x-text="c.title || c.url"></span></a>
                                        <span class="text-slate-400" x-text="'· ' + c.domain"></span>
                                        <span class="text-xs text-slate-400" x-show="current.analysis && current.analysis.citations_noise.includes(c.url)">· rumore</span>
                                        <span class="text-xs text-red-500" x-show="current.analysis && current.analysis.negative_urls.includes(c.url)">· negativa</span>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="!current.citations.length" class="text-slate-400">Nessuna fonte citata: l'engine ha risposto senza cercare.</p>
                        </div>
                        <div x-show="current.queries.length">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Come ha cercato</p>
                            <ul class="space-y-0.5 text-slate-600 dark:text-slate-300"><template x-for="q in current.queries" :key="q"><li x-text="'· ' + q"></li></template></ul>
                        </div>
                        <p class="text-xs text-slate-400" x-text="(current.latency_ms/1000).toFixed(1) + ' s · ' + (current.search_count ?? '?') + ' ricerche · ' + Number(current.cost).toFixed(4) + ' $'"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function arReport() {
    const base = '<?= url($basePath) ?>';
    const csrf = '<?= $csrf ?>';
    const runId = <?= (int) $run['id'] ?>;
    return {
        responses: <?= $responsesJson ?>,
        labels: <?= json_encode($engineLabels) ?>,
        current: null,
        analyzing: false, total: 0, done: 0, percent: 0, message: '', log: [], es: null,
        open(id) { this.current = this.responses.find(r => r.id === id) || null; },
        async analyze(reset) {
            try {
                if (reset) {
                    if (!confirm('Rifare tutti i giudizi di questo run? Costa una chiamata AI per risposta.')) return;
                    const fd = new FormData(); fd.append('_csrf_token', csrf);
                    const resp = await fetch(base + '/runs/' + runId + '/reanalyze', { method: 'POST', body: fd });
                    if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                    const data = await resp.json();
                    if (!data.success) throw new Error(data.error || 'Errore');
                }
                this.analyzing = true; this.message = ''; this.log = []; this.done = 0; this.percent = 0;
                this.es = new EventSource(base + '/runs/stream?run_id=' + runId);
                this.es.addEventListener('snapshot', e => { const d = JSON.parse(e.data); this.total = d.total; this.done = d.done; this.percent = d.total ? Math.round(d.done / d.total * 100) : 0; });
                this.es.addEventListener('stalled', e => { const d = JSON.parse(e.data); this.message = d.message; this.analyzing = false; this.es.close(); });
                this.es.addEventListener('failed', e => { const d = JSON.parse(e.data); this.message = d.message; this.analyzing = false; this.es.close(); });
                this.es.addEventListener('phase', e => { const d = JSON.parse(e.data); this.total = d.total; this.done = 0; this.percent = 0; });
                this.es.addEventListener('analysis_completed', e => { const d = JSON.parse(e.data); this.done++; this.percent = this.total ? Math.round(this.done / this.total * 100) : 0; this.log.unshift({ id: d.response_id, text: (this.labels[d.engine] || d.engine) + ': ' + d.verdict + ' · ' + (d.summary || '') }); });
                this.es.addEventListener('analysis_error', e => { const d = JSON.parse(e.data); this.done++; this.log.unshift({ id: 'e' + d.response_id, text: (this.labels[d.engine] || d.engine) + ': errore judge · ' + d.error }); });
                this.es.addEventListener('completed', () => { this.message = 'Analisi completata, ricarico il report…'; this.percent = 100; this.es.close(); setTimeout(() => location.reload(), 800); });
                this.es.addEventListener('cancelled', () => { this.message = 'Annullato'; this.analyzing = false; this.es.close(); });
                this.es.onerror = () => { this.message = 'Connessione persa: ricarica la pagina tra qualche secondo.'; this.analyzing = false; if (this.es) this.es.close(); };
            } catch (e) { alert(e.message); this.analyzing = false; }
        },
    };
}
</script>
