<?php
// Prepara i dati dei cluster con indice originale per Alpine.js
$clustersForTable = array_values(array_map(function($c, $i) {
    $c['_index'] = $i + 1;
    return $c;
}, $clusters, array_keys($clusters)));

// Raccogli intent unici per il filtro
$uniqueIntents = array_unique(array_filter(array_map(fn($c) => strtolower($c['intent'] ?? ''), $clusters)));
sort($uniqueIntents);
?>
<?php include __DIR__ . '/../partials/table-helpers.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/architecture') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white">Architettura</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Architettura Sito Proposta</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= date('d/m/Y H:i', strtotime($research['created_at'])) ?> | <?= e($brief['business'] ?? '') ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/architecture') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Analisi
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= count($clusters) ?></p>
            <p class="text-xs text-slate-500 mt-1">Pagine proposte</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($research['filtered_keywords_count'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1">Keyword analizzate</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalVolume) ?></p>
            <p class="text-xs text-slate-500 mt-1">Volume totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $research['credits_used'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 mt-1">Crediti usati</p>
        </div>
    </div>

    <!-- Strategy Note -->
    <?php if (!empty($research['strategy_note'])): ?>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-1">Nota Strategica</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300"><?= e($research['strategy_note']) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Architecture Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
         x-data='architectureTable(<?= json_encode($clustersForTable, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Struttura Pagine</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left cursor-pointer select-none" @click="toggleSort('_index')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                # <span x-html="sortField === null ? '' : krSortIcon('_index', '_none', 'asc')"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-left cursor-pointer select-none" @click="toggleSort('name')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Pagina <span x-html="krSortIcon('name', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">H1</th>
                        <th class="px-6 py-3 text-right cursor-pointer select-none" @click="toggleSort('total_volume')">
                            <span class="inline-flex items-center justify-end text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Volume <span x-html="krSortIcon('total_volume', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-center cursor-pointer select-none" @click="toggleSort('keywords_count')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Kw <span x-html="krSortIcon('keywords_count', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-center cursor-pointer select-none" @click="toggleSort('intent')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Intent <span x-html="krSortIcon('intent', sortField, sortDir)"></span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="c in sortedClusters" :key="c._index">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-6 py-4 text-slate-400 font-bold" x-text="c._index"></td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900 dark:text-white" x-text="c.name"></div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Main: <span x-text="c.main_keyword"></span> (<span x-text="(c.main_volume || 0).toLocaleString()"></span>)
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <template x-if="c.suggested_url">
                                <code class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-primary-600 dark:text-primary-400" x-text="c.suggested_url"></code>
                            </template>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300" x-text="c.suggested_h1 || ''"></td>
                        <td class="px-6 py-4 text-right font-medium text-slate-900 dark:text-white" x-text="(c.total_volume || 0).toLocaleString()"></td>
                        <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300" x-text="c.keywords_count"></td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="krIntentClass(c.intent)" x-text="c.intent ? c.intent.charAt(0).toUpperCase() + c.intent.slice(1) : 'N/A'"></span>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cluster Detail Cards -->
    <div x-data="{ intentFilter: '' }">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Dettaglio per pagina</h2>
            <select x-model="intentFilter"
                    class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Tutti gli intent</option>
                <?php foreach ($uniqueIntents as $intent): ?>
                <option value="<?= e($intent) ?>"><?= e(ucfirst($intent)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="space-y-4">
            <?php foreach ($clusters as $i => $cluster): ?>
                <div x-show="!intentFilter || intentFilter === '<?= strtolower(addslashes($cluster['intent'] ?? '')) ?>'"
                     x-transition>
                    <?php $index = $i; include __DIR__ . '/../partials/cluster-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Meta info -->
    <div class="text-center text-xs text-slate-400 dark:text-slate-500">
        API: <?= number_format(($research['api_time_ms'] ?? 0) / 1000, 1) ?>s | AI: <?= number_format(($research['ai_time_ms'] ?? 0) / 1000, 1) ?>s | Crediti: <?= $research['credits_used'] ?? 0 ?>
    </div>
</div>
