<?php
$currentPage = 'dashboard';
include __DIR__ . '/../partials/project-nav.php';
$basePath = '/ai-reputation/project/' . $project['id'];
$statusLabel = match ($run['status']) {
    'completed' => 'Completato', 'running' => 'In corso', 'failed' => 'Fallito', 'cancelled' => 'Annullato', default => 'In attesa',
};
$clusterClass = fn(string $c) => match ($c) {
    'rep' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
    'comm' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'comp' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
};
?>

<div class="space-y-6" x-data="arReport()">
    <!-- Header report -->
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="<?= url($basePath) ?>" class="text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400">← Overview</a>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">Report run #<?= (int) $run['id'] ?> · <?= date('d/m/Y H:i', strtotime($run['created_at'])) ?></h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?= (int) $run['prompts_total'] ?> domande · <?= count($engines) ?> engine · <?= (int) $run['responses_total'] ?> risposte · <?= $statusLabel ?>
                <?php if ($run['status'] === 'running'): ?>
                <a href="<?= url($basePath) ?>" class="text-indigo-600 dark:text-indigo-400">(segui l'avanzamento nell'Overview)</a>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($engines as $engine): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"><?= e($engineLabels[$engine] ?? $engine) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KPI (fetta 1: menzione = match testuale; sentiment, rischio e piano d'azione arrivano con il judge) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">AI share of voice</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['share'] ?>%</p>
            <p class="text-xs text-slate-400">citato in <?= $stats['mentioned'] ?> risposte su <?= $stats['ok'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Fonti citate</p>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?= $stats['domains'] ?></p>
            <p class="text-xs text-slate-400">domini distinti</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Errori</p>
            <p class="text-2xl font-bold <?= $stats['errors'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' ?>"><?= $stats['errors'] ?></p>
            <p class="text-xs text-slate-400">risposte non raccolte</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Costo API</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['cost'], 3) ?> <span class="text-sm font-normal text-slate-400">$</span></p>
            <p class="text-xs text-slate-400"><?= number_format((float) $run['credits_used'], 1) ?> crediti</p>
        </div>
    </div>

    <!-- Griglia domanda x engine -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Cosa risponde ogni AI</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Clicca una cella per leggere la risposta completa e le fonti citate.</p>
            </div>
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
                                <button type="button" @click="open(<?= (int) $r['id'] ?>)" class="group text-left">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $r['mentioned'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' ?>">
                                        <?= $r['mentioned'] ? 'citato' : 'non citato' ?>
                                    </span>
                                    <span class="block mt-1 text-xs text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"><?= count($r['citations']) ?> fonti · <?= $r['search_count'] !== null ? (int) $r['search_count'] . ' ricerche · ' : '' ?><?= round(((int) $r['latency_ms']) / 1000) ?> s</span>
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

    <!-- Fonti citate -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Fonti che le AI citano</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Domini citati nelle risposte di questo run. Il giudizio positivo/negativo su ogni fonte arriva con l'analisi AI.</p>
        </div>
        <?php if (empty($domains)): ?>
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna fonte citata.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dominio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Citazioni</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Engine</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagine</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($domains as $d): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 align-top">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($d['domain']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><?= (int) $d['count'] ?></td>
                        <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400"><?= e(implode(', ', array_map(fn($en) => $engineLabels[$en] ?? $en, array_keys($d['engines'])))) ?></td>
                        <td class="px-4 py-3 text-xs">
                            <?php foreach (array_slice($d['urls'], 0, 3, true) as $u => $t): ?>
                            <a href="<?= e($u) ?>" target="_blank" rel="noopener" class="block truncate max-w-xs text-indigo-600 hover:underline dark:text-indigo-400" title="<?= e($u) ?>"><?= e($t ?: $u) ?></a>
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

    <!-- Modal risposta -->
    <div x-show="current" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60" @click.self="current = null" @keydown.escape.window="current = null">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[85vh] flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="current && (labels[current.engine] || current.engine) + ' · ' + (current.model || '')"></p>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white" x-text="current && current.prompt"></h3>
                </div>
                <button type="button" @click="current = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 overflow-y-auto space-y-4 text-sm">
                <template x-if="current && current.status === 'error'">
                    <div class="p-3 rounded-lg bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300" x-text="current.error"></div>
                </template>
                <template x-if="current && current.status === 'ok'">
                    <div class="space-y-4">
                        <div class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed" x-text="current.text"></div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Fonti citate (<span x-text="current.citations.length"></span>)</p>
                            <ul class="space-y-1">
                                <template x-for="c in current.citations" :key="c.url">
                                    <li><a :href="c.url" target="_blank" rel="noopener" class="text-indigo-600 hover:underline dark:text-indigo-400 break-all"><span x-text="c.title || c.url"></span></a> <span class="text-slate-400" x-text="'· ' + c.domain"></span></li>
                                </template>
                            </ul>
                            <p x-show="!current.citations.length" class="text-slate-400">Nessuna fonte citata: l'engine ha risposto senza cercare.</p>
                        </div>
                        <div x-show="current.queries.length">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Come ha cercato</p>
                            <ul class="space-y-0.5 text-slate-600 dark:text-slate-300">
                                <template x-for="q in current.queries" :key="q"><li x-text="'· ' + q"></li></template>
                            </ul>
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
    return {
        responses: <?= $responsesJson ?>,
        labels: <?= json_encode($engineLabels) ?>,
        current: null,
        open(id) { this.current = this.responses.find(r => r.id === id) || null; },
    };
}
</script>
