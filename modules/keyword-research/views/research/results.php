<?php
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
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white">Risultati</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Risultati Research</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= date('d/m/Y H:i', strtotime($research['created_at'])) ?> | <?= e($brief['business'] ?? '') ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research/' . $research['id'] . '/export') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Esporta CSV
            </a>
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Ricerca
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($research['raw_keywords_count'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Keyword raccolte</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($research['filtered_keywords_count'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Dopo filtro</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= count($clusters) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cluster</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalKeywords) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kw nei cluster</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalVolume) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Volume totale</p>
        </div>
    </div>

    <!-- Strategy Note -->
    <?php if (!empty($research['strategy_note'])): ?>
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-emerald-800 dark:text-emerald-200 mb-1">Nota Strategica</h3>
                <p class="text-sm text-emerald-700 dark:text-emerald-300"><?= e($research['strategy_note']) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Clusters -->
    <div x-data="{ intentFilter: '', allExpanded: false }">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    <span x-show="!intentFilter"><?= count($clusters) ?> Cluster identificati</span>
                    <span x-show="intentFilter" x-cloak>
                        Cluster <span x-text="intentFilter.charAt(0).toUpperCase() + intentFilter.slice(1)"></span>
                    </span>
                </h2>
                <select x-model="intentFilter"
                        class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti gli intent</option>
                    <?php foreach ($uniqueIntents as $intent): ?>
                    <option value="<?= e($intent) ?>"><?= e(ucfirst($intent)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button @click="allExpanded = !allExpanded; $dispatch('toggle-all-clusters', { expand: allExpanded })"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg x-show="!allExpanded" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                <svg x-show="allExpanded" x-cloak class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M15 15h4.5M15 15v4.5m0-4.5l5.5 5.5"/>
                </svg>
                <span x-text="allExpanded ? 'Comprimi tutti' : 'Espandi tutti'"></span>
            </button>
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

    <!-- Excluded Keywords -->
    <?php if (!empty($excludedKeywords)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden" x-data="{ showExcluded: false }">
        <div class="px-6 py-4 cursor-pointer flex items-center justify-between" @click="showExcluded = !showExcluded">
            <h3 class="font-semibold text-slate-900 dark:text-white">
                Keyword escluse dall'AI
                <span class="text-sm font-normal text-slate-500 ml-1">(<?= count($excludedKeywords) ?>)</span>
            </h3>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="showExcluded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="showExcluded" x-transition class="border-t border-slate-200 dark:border-slate-700 px-6 py-4">
            <div class="flex flex-wrap gap-2">
                <?php foreach ($excludedKeywords as $kw): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                    <?= e($kw['text']) ?>
                    <?php if ($kw['volume'] > 0): ?>
                    <span class="ml-1 text-slate-400">(<?= number_format($kw['volume']) ?>)</span>
                    <?php endif; ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meta info -->
    <div class="text-center text-xs text-slate-400 dark:text-slate-500">
        API: <?= number_format(($research['api_time_ms'] ?? 0) / 1000, 1) ?>s | AI: <?= number_format(($research['ai_time_ms'] ?? 0) / 1000, 1) ?>s | Crediti: <?= $research['credits_used'] ?? 0 ?>
    </div>
</div>
