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
            <p class="text-xs text-slate-400"><?= $hasAnalyses ? "citato in {$metrics['mentioned_n']} risposte su {$metrics['judged']} (pesate per engine)" : 'dopo l\'analisi' ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Rischio reputazione</p>
            <p class="text-2xl font-bold <?= $riskClass ?>"><?= $hasAnalyses ? $metrics['risk_label'] : '–' ?> <?php if ($hasAnalyses): ?><span class="text-sm font-normal text-slate-400"><?= $metrics['risk'] ?>/100</span><?php endif; ?></p>
            <p class="text-xs text-slate-400"><?= $hasAnalyses ? ($metrics['negative_domains'] . ' fonti negative' . ($metrics['sentiment'] !== null ? ' · sentiment ' . $metrics['sentiment'] : '')) : 'dopo l\'analisi' ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Risposte negative</p>
            <p class="text-2xl font-bold <?= $metrics['negative'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' ?>"><?= $hasAnalyses ? $metrics['negative'] : '–' ?> <?php if ($hasAnalyses): ?><span class="text-sm font-normal text-slate-400">/ <?= $metrics['judged'] ?></span><?php endif; ?></p>
            <p class="text-xs text-slate-400"><?php if ($hasAnalyses && $metrics['negative_by_engine']): ?><?= e(implode(' · ', array_map(fn($k, $v) => ($engineLabels[$k] ?? $k) . ' ' . $v, array_keys($metrics['negative_by_engine']), $metrics['negative_by_engine']))) ?><?php elseif ($hasAnalyses): ?>nessuna<?php else: ?>dopo l'analisi<?php endif; ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Divergenza tra engine</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $hasAnalyses ? count($metrics['divergent']) : '–' ?> <?php if ($hasAnalyses): ?><span class="text-sm font-normal text-slate-400">domande</span><?php endif; ?></p>
            <p class="text-xs text-slate-400"><?= $hasAnalyses ? ($metrics['divergent'] ? 'le AI non sono d\'accordo' : 'le AI concordano') : 'dopo l\'analisi' ?> · costo API <?= number_format((float) $run['cost_total'], 3) ?> $</p>
        </div>
    </div>

    <?php if ($hasAnalyses && $metrics['divergent']): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-5 py-4">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-2">Dove le AI non sono d'accordo</h3>
        <ul class="space-y-1 text-sm">
            <?php foreach ($metrics['divergent'] as $d): ?>
            <li class="text-slate-700 dark:text-slate-200">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-1 <?= $clusterClass($d['cluster']) ?>"><?= strtoupper($d['cluster']) ?></span>
                "<?= e($d['prompt']) ?>" → negativo per <strong><?= e(implode(', ', array_map(fn($x) => $engineLabels[$x] ?? $x, $d['negative_engines']))) ?></strong>, non per gli altri (<?= $d['negative'] ?>/<?= $d['total'] ?>)
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Griglia domanda x engine -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Cosa risponde ogni AI</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Clicca una cella per leggere la risposta, il giudizio e le fonti.</p>
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
                    <?php foreach ($grid as $row): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 align-top">
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-2 <?= $clusterClass($row['prompt']['cluster']) ?>"><?= strtoupper($row['prompt']['cluster']) ?></span>
                            <?= e($row['prompt']['text']) ?>
                        </td>
                        <?php foreach ($engines as $engine): ?>
                        <td class="px-4 py-3">
                            <?php foreach ($row['cells'][$engine] ?? [] as $r): ?>
                                <?php if ($r['status'] === 'pending'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">in attesa</span>
                                <?php elseif ($r['status'] === 'error'): ?>
                                <button type="button" @click="open(<?= (int) $r['id'] ?>)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300" title="<?= e((string) $r['error_message']) ?>">errore</button>
                                <?php else: ?>
                                <?php [$label, $cls] = $verdictChip($r['analysis'], $r['mentioned']); ?>
                                <button type="button" @click="open(<?= (int) $r['id'] ?>)" class="group text-left block mb-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $cls ?>"><?= e($label) ?></span>
                                    <span class="block mt-1 text-xs text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 max-w-[14rem]">
                                        <?= $r['analysis'] && $r['analysis']['summary'] ? e(mb_substr($r['analysis']['summary'], 0, 110)) . (mb_strlen($r['analysis']['summary']) > 110 ? '…' : '') : count($r['citations']) . ' fonti · ' . round(((int) $r['latency_ms']) / 1000) . ' s' ?>
                                    </span>
                                </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Fonti citate -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Fonti che le AI citano</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Chi alimenta le risposte. "Negativa" = sostiene fatti negativi. "Rumore" = non parla del soggetto.</p>
            </div>
            <?php if (empty($sources)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna fonte citata.</div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dominio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cit.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagine</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($sources as $d): ?>
                        <?php [$sl, $sc] = match ($d['status']) {
                            'negative' => ['negativa', 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
                            'noise' => ['rumore', 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'],
                            default => [$hasAnalyses ? 'ok' : '–', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
                        }; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 align-top">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($d['domain']) ?><div class="text-xs font-normal text-slate-400"><?= e(implode(', ', array_map(fn($en) => $engineLabels[$en] ?? $en, array_keys($d['engines'])))) ?></div></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= (int) $d['count'] ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sc ?>"><?= $sl ?></span></td>
                            <td class="px-4 py-3 text-xs">
                                <?php foreach (array_slice($d['urls'], 0, 3, true) as $u => $info): ?>
                                <a href="<?= e($u) ?>" target="_blank" rel="noopener" class="block truncate max-w-[16rem] <?= $info['negative'] ? 'text-red-600 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400' ?> hover:underline" title="<?= e($u) ?>"><?= e($info['title'] ?: $u) ?></a>
                                <?php endforeach; ?>
                                <?php if (count($d['urls']) > 3): ?><span class="text-slate-400">+<?= count($d['urls']) - 3 ?></span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Piano d'azione + competitor -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Piano d'azione</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Cosa far rimuovere, cosa far scrivere.</p>
                </div>
                <?php if (empty($actions)): ?>
                <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400"><?= $hasAnalyses ? 'Nessuna azione necessaria: nessun contenuto negativo e soggetto citato dove conta.' : 'Compare dopo l\'analisi.' ?></div>
                <?php else: ?>
                <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($actions as $a): ?>
                    <li class="px-5 py-3 flex gap-3 items-start">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap mt-0.5 <?= $actionClass[$a['type']] ?? '' ?>"><?= $actionLabel[$a['type']] ?? $a['type'] ?></span>
                        <div class="min-w-0 text-sm">
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($a['title']) ?></p>
                            <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5"><?= e((string) $a['rationale']) ?></p>
                            <?php if (!empty($a['target_url'])): ?><a href="<?= e($a['target_url']) ?>" target="_blank" rel="noopener" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline break-all"><?= e($a['target_url']) ?></a><?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($competitors)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-2">Chi citano al posto suo</h3>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($competitors as $c): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200"><?= e($c['name']) ?> <span class="ml-1 text-slate-400"><?= (int) $c['count'] ?></span></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Da confermare (ADR-008) -->
    <?php if (!empty($homonyms)): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 px-5 py-4">
        <h3 class="text-base font-semibold text-amber-900 dark:text-amber-200">Da confermare: possibili omonimi</h3>
        <p class="text-sm text-amber-800 dark:text-amber-300 mb-3">Le AI hanno attribuito al nome fatti che non tornano col profilo. Il tool non decide: dillo tu.</p>
        <ul class="space-y-2">
            <?php foreach ($homonyms as $h): ?>
            <li class="flex flex-wrap items-center justify-between gap-3 bg-white/70 dark:bg-slate-800/60 rounded-lg px-4 py-3 text-sm">
                <span class="text-slate-800 dark:text-slate-100"><?= e($h['text']) ?></span>
                <?php if ($canEdit): ?>
                <span class="flex gap-2">
                    <form method="POST" action="<?= url("{$basePath}/facts/{$h['id']}/confirm") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><input type="hidden" name="back" value="<?= e("{$basePath}/runs/{$run['id']}") ?>"><button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-medium hover:bg-slate-700">Sì, è lui</button></form>
                    <form method="POST" action="<?= url("{$basePath}/facts/{$h['id']}/reject") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><input type="hidden" name="back" value="<?= e("{$basePath}/runs/{$run['id']}") ?>"><button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">No, è un altro</button></form>
                </span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

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
