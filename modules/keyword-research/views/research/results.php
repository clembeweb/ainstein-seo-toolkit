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

    <?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

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
    <div x-data="clusterResults()">
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
                <input type="text" x-model="searchQuery" placeholder="Cerca cluster o keyword..."
                       class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 w-48 focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex items-center gap-2">
                <!-- View Toggle -->
                <div class="inline-flex rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden">
                    <button @click="viewMode = 'card'" :class="viewMode === 'card' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-600'" class="px-3 py-1.5 text-xs font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                    <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-600'" class="px-3 py-1.5 text-xs font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                </div>
                <button x-show="viewMode === 'card'" @click="allExpanded = !allExpanded; $dispatch('toggle-all-clusters', { expand: allExpanded })"
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
        </div>

        <!-- Table View -->
        <div x-show="viewMode === 'table'" x-cloak>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('name')">
                                    <span class="inline-flex items-center gap-1">Cluster <span x-html="tableSortIcon('name')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Main Keyword</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('keywords_count')">
                                    <span class="inline-flex items-center gap-1">Keywords <span x-html="tableSortIcon('keywords_count')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('total_volume')">
                                    <span class="inline-flex items-center gap-1 justify-end">Volume Tot. <span x-html="tableSortIcon('total_volume')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer select-none" @click="toggleTableSort('main_volume')">
                                    <span class="inline-flex items-center gap-1 justify-end">Vol. Main <span x-html="tableSortIcon('main_volume')"></span></span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Intent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <template x-for="(cluster, idx) in filteredTableClusters" :key="cluster.name">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-bold text-slate-400 mr-1" x-text="'#' + cluster._index"></span>
                                        <span class="font-medium text-slate-900 dark:text-white" x-text="cluster.name"></span>
                                        <template x-if="cluster.note">
                                            <p class="text-xs text-slate-500 mt-0.5 truncate max-w-xs" x-text="cluster.note"></p>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300" x-text="cluster.main_keyword"></td>
                                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900 dark:text-white" x-text="cluster.keywords_count"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-emerald-600 dark:text-emerald-400" x-text="cluster.total_volume.toLocaleString()"></td>
                                    <td class="px-4 py-3 text-right text-sm text-slate-700 dark:text-slate-300" x-text="cluster.main_volume.toLocaleString()"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="intentBadgeClass(cluster.intent)" x-text="cluster.intent ? cluster.intent.charAt(0).toUpperCase() + cluster.intent.slice(1) : '-'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card View -->
        <div x-show="viewMode === 'card'" class="space-y-4">
            <?php foreach ($clusters as $i => $cluster): ?>
                <div x-show="cardMatchesFilter(<?= $i ?>)"
                     x-transition>
                    <?php $index = $i; include __DIR__ . '/../partials/cluster-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<script>
function clusterResults() {
    const allClusters = <?= json_encode(array_values(array_map(function($c, $i) {
        return [
            '_index' => $i + 1,
            'name' => $c['name'] ?? '',
            'main_keyword' => $c['main_keyword'] ?? '',
            'keywords_count' => $c['keywords_count'] ?? 0,
            'total_volume' => $c['total_volume'] ?? 0,
            'main_volume' => $c['main_volume'] ?? 0,
            'intent' => strtolower($c['intent'] ?? ''),
            'note' => $c['note'] ?? '',
            'searchText' => strtolower(($c['name'] ?? '') . ' ' . ($c['main_keyword'] ?? '') . ' ' . implode(' ', array_column($c['keywords_list'] ?? [], 'text'))),
        ];
    }, $clusters, array_keys($clusters))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;

    return {
        viewMode: 'card',
        intentFilter: '',
        searchQuery: '',
        allExpanded: false,
        tableSortField: 'total_volume',
        tableSortDir: 'desc',

        get filteredTableClusters() {
            let list = [...allClusters];
            if (this.intentFilter) {
                list = list.filter(c => c.intent === this.intentFilter);
            }
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(c => c.name.toLowerCase().includes(q) || c.main_keyword.toLowerCase().includes(q));
            }
            const field = this.tableSortField;
            const dir = this.tableSortDir === 'asc' ? 1 : -1;
            list.sort((a, b) => {
                const va = typeof a[field] === 'string' ? a[field].toLowerCase() : a[field];
                const vb = typeof b[field] === 'string' ? b[field].toLowerCase() : b[field];
                return va < vb ? -dir : va > vb ? dir : 0;
            });
            return list;
        },

        cardMatchesFilter(index) {
            const c = allClusters[index];
            if (!c) return false;
            if (this.intentFilter && c.intent !== this.intentFilter) return false;
            if (this.searchQuery && !c.searchText.includes(this.searchQuery.toLowerCase())) return false;
            return true;
        },

        toggleTableSort(field) {
            if (this.tableSortField === field) {
                this.tableSortDir = this.tableSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.tableSortField = field;
                this.tableSortDir = field === 'name' ? 'asc' : 'desc';
            }
        },

        tableSortIcon(field) {
            if (this.tableSortField !== field) return '<svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
            return this.tableSortDir === 'asc'
                ? '<svg class="w-3 h-3 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                : '<svg class="w-3 h-3 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        },

        intentBadgeClass(intent) {
            const map = {
                'informational': 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'transactional': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                'commercial': 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'navigational': 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
            };
            return map[(intent || '').toLowerCase()] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
        }
    };
}
</script>

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
