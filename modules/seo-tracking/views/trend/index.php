<?php
/**
 * Position Compare View
 * Confronta posizioni keyword tra due periodi (stile SEMrush)
 */

use Modules\SeoTracking\Services\PositionCompareService;

$currentPage = 'trend';
$presets = PositionCompareService::getPresets();
$defaultPreset = $presets['28d'];
$dateRange = $dateRange ?? ['min_date' => date('Y-m-d', strtotime('-16 months')), 'max_date' => date('Y-m-d')];
?>
<div class="space-y-6" x-data="positionCompare(<?= (int)$project['id'] ?>)">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Page Info + Actions -->
    <div class="flex justify-between items-center">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Confronta le posizioni delle keyword tra due periodi
        </p>
        <button @click="exportCSV()"
                :disabled="loading || !hasData"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export CSV
        </button>
    </div>

    <!-- Controlli Periodo -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Preset + Fonte Dati -->
            <div class="space-y-4">
                <!-- Preset -->
                <div x-show="source === 'gsc'">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Confronto Rapido</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($presets as $key => $preset): ?>
                        <button @click="applyPreset('<?= $key ?>')"
                                :class="activePreset === '<?= $key ?>' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                            <?= e($preset['label']) ?>
                        </button>
                        <?php endforeach; ?>
                        <button @click="activePreset = 'custom'"
                                :class="activePreset === 'custom' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                            Personalizzato
                        </button>
                    </div>
                </div>

                <!-- Fonte Dati -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fonte Dati</label>
                    <div class="flex gap-2">
                        <button @click="source = 'gsc'"
                                :class="source === 'gsc' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                            </svg>
                            GSC (tutte le query)
                        </button>
                        <button @click="source = 'positions'"
                                :class="source === 'positions' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                            Keyword tracciate
                        </button>
                    </div>
                </div>
            </div>

            <!-- Date Pickers -->
            <div class="grid gap-4" :class="source === 'gsc' ? 'grid-cols-2' : 'grid-cols-1'">
                <!-- Periodo A (Precedente) - solo per GSC -->
                <div class="space-y-2" x-show="source === 'gsc'">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo A (Precedente)</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="dateFromA" @change="activePreset = 'custom'"
                               min="<?= e($dateRange['min_date']) ?>" max="<?= e($dateRange['max_date']) ?>"
                               class="flex-1 rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <input type="date" x-model="dateToA" @change="activePreset = 'custom'"
                               min="<?= e($dateRange['min_date']) ?>" max="<?= e($dateRange['max_date']) ?>"
                               class="flex-1 rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    </div>
                </div>

                <!-- Periodo B (Attuale) / Periodo unico per positions -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                           x-text="source === 'positions' ? 'Periodo' : 'Periodo B (Attuale)'"></label>
                    <div class="flex gap-2">
                        <input type="date" x-model="dateFromB" @change="activePreset = 'custom'"
                               min="<?= e($dateRange['min_date']) ?>" max="<?= e($dateRange['max_date']) ?>"
                               class="flex-1 rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <input type="date" x-model="dateToB" @change="activePreset = 'custom'"
                               min="<?= e($dateRange['min_date']) ?>" max="<?= e($dateRange['max_date']) ?>"
                               class="flex-1 rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri + Azione -->
        <div class="mt-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Filtra Keyword</label>
                <input type="text" x-model="filterKeyword" placeholder="Cerca keyword..."
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Filtra URL</label>
                <input type="text" x-model="filterUrl" placeholder="Cerca URL..."
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <button @click="loadData()" :disabled="loading"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm font-medium transition-colors">
                <span x-show="!loading">Confronta</span>
                <span x-show="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Caricamento...
                </span>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4" x-show="hasData" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Totale Keywords</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1" x-text="stats.total"></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-800 p-4">
            <p class="text-sm text-emerald-600 dark:text-emerald-400">Migliorate</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="stats.improved"></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-4">
            <p class="text-sm text-red-600 dark:text-red-400">Peggiorate</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1" x-text="stats.declined"></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-sm text-blue-600 dark:text-blue-400">Nuove</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" x-text="stats.new"></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-300 dark:border-slate-600 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Perse</p>
            <p class="text-2xl font-bold text-slate-500 dark:text-slate-400 mt-1" x-text="stats.lost"></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700" x-show="hasData" x-cloak>
        <!-- Tab Headers -->
        <div class="border-b border-slate-200 dark:border-slate-700 px-4">
            <nav class="flex gap-6 -mb-px">
                <button @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Tutte <span class="ml-1 text-xs bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full" x-text="stats.total"></span>
                </button>
                <button @click="activeTab = 'improved'"
                        :class="activeTab === 'improved' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Migliorate <span class="ml-1 text-xs bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded-full" x-text="stats.improved"></span>
                </button>
                <button @click="activeTab = 'declined'"
                        :class="activeTab === 'declined' ? 'border-red-500 text-red-600 dark:text-red-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Peggiorate <span class="ml-1 text-xs bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 px-2 py-0.5 rounded-full" x-text="stats.declined"></span>
                </button>
                <button @click="activeTab = 'new'"
                        :class="activeTab === 'new' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Nuove <span class="ml-1 text-xs bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full" x-text="stats.new"></span>
                </button>
                <button @click="activeTab = 'lost'"
                        :class="activeTab === 'lost' ? 'border-slate-500 text-slate-600 dark:text-slate-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Perse <span class="ml-1 text-xs bg-slate-200 dark:bg-slate-600 px-2 py-0.5 rounded-full" x-text="stats.lost"></span>
                </button>
            </nav>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pos. Prec.</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pos. Attuale</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Diff</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click Prec.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click Att.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Impr.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vol.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="row in currentData" :key="row.keyword">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-slate-900 dark:text-white" x-text="row.keyword"></span>
                            </td>
                            <td class="px-4 py-3">
                                <a :href="row.url" target="_blank" x-show="row.url"
                                   class="text-xs text-slate-500 dark:text-slate-400 hover:text-blue-600 truncate block max-w-[200px]"
                                   :title="row.url" x-text="truncateUrl(row.url)"></a>
                                <span x-show="!row.url" class="text-slate-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="row.position_previous !== null"
                                      class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300"
                                      x-text="row.position_previous?.toFixed(1) || '-'"></span>
                                <span x-show="row.position_previous === null" class="text-slate-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="row.position_current !== null"
                                      :class="getPositionClass(row.position_current)"
                                      class="inline-flex px-2 py-0.5 rounded text-xs font-medium"
                                      x-text="row.position_current?.toFixed(1) || '-'"></span>
                                <span x-show="row.position_current === null" class="text-slate-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="getDiffClass(row.diff, row.status)"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                                    <template x-if="row.status === 'improved'">
                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </template>
                                    <template x-if="row.status === 'declined'">
                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </template>
                                    <span x-text="formatDiff(row)"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-400" x-text="row.clicks_previous.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-slate-900 dark:text-white" x-text="row.clicks_current.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-400" x-text="row.impressions_current.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                                <span x-show="row.search_volume" x-text="row.search_volume?.toLocaleString() || '-'"></span>
                                <span x-show="!row.search_volume" class="text-slate-400">-</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty State -->
            <div x-show="currentData.length === 0 && hasData" class="px-4 py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-4 text-slate-500 dark:text-slate-400">Nessuna keyword in questa categoria</p>
            </div>
        </div>
    </div>

    <!-- Initial State -->
    <div x-show="!hasData && !loading" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Confronta le posizioni</h3>
        <p class="mt-2 text-slate-500 dark:text-slate-400">Seleziona due periodi e clicca "Confronta" per analizzare le variazioni di ranking.</p>
    </div>
</div>

<script>
function positionCompare(projectId) {
    const presets = <?= json_encode($presets) ?>;
    const defaultPreset = presets['28d'];

    return {
        projectId: projectId,
        baseUrl: '<?= url('') ?>',
        loading: false,
        hasData: false,
        activeTab: 'all',
        activePreset: '28d',
        source: '<?= e($currentSource ?? 'gsc') ?>',

        // Date ranges
        dateFromA: defaultPreset.dateFromA,
        dateToA: defaultPreset.dateToA,
        dateFromB: defaultPreset.dateFromB,
        dateToB: defaultPreset.dateToB,

        // Filters
        filterKeyword: '',
        filterUrl: '',

        // Data
        data: {
            all: [],
            improved: [],
            declined: [],
            new: [],
            lost: []
        },
        stats: {
            total: 0,
            improved: 0,
            declined: 0,
            new: 0,
            lost: 0
        },

        get currentData() {
            return this.data[this.activeTab] || [];
        },

        applyPreset(key) {
            this.activePreset = key;
            const preset = presets[key];
            if (preset) {
                this.dateFromA = preset.dateFromA;
                this.dateToA = preset.dateToA;
                this.dateFromB = preset.dateFromB;
                this.dateToB = preset.dateToB;
            }
        },

        async loadData() {
            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('date_from_a', this.dateFromA);
                formData.append('date_to_a', this.dateToA);
                formData.append('date_from_b', this.dateFromB);
                formData.append('date_to_b', this.dateToB);
                formData.append('keyword', this.filterKeyword);
                formData.append('url', this.filterUrl);
                formData.append('source', this.source);

                const response = await fetch(`${this.baseUrl}/seo-tracking/project/${this.projectId}/trend/data`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.data = {
                        all: result.data.all || [],
                        improved: result.data.improved || [],
                        declined: result.data.declined || [],
                        new: result.data.new || [],
                        lost: result.data.lost || []
                    };
                    this.stats = result.data.stats || {total: 0, improved: 0, declined: 0, new: 0, lost: 0};
                    this.hasData = true;
                } else {
                    window.ainstein.alert(result.error || 'Errore nel caricamento dei dati', 'error');
                }
            } catch (error) {
                console.error('Error loading data:', error);
                window.ainstein.alert('Errore di rete', 'error');
            } finally {
                this.loading = false;
            }
        },

        exportCSV() {
            const params = new URLSearchParams({
                date_from_a: this.dateFromA,
                date_to_a: this.dateToA,
                date_from_b: this.dateFromB,
                date_to_b: this.dateToB,
                keyword: this.filterKeyword,
                url: this.filterUrl,
                tab: this.activeTab,
                source: this.source
            });

            window.location.href = `${this.baseUrl}/seo-tracking/project/${this.projectId}/trend/export?${params}`;
        },

        truncateUrl(url) {
            if (!url) return '';
            if (url.length <= 50) return url;
            return '...' + url.slice(-47);
        },

        getPositionClass(position) {
            if (position <= 3) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
            if (position <= 10) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
            if (position <= 20) return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
            return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
        },

        getDiffClass(diff, status) {
            if (status === 'new') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
            if (status === 'lost') return 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300';
            if (status === 'improved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
            if (status === 'declined') return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
            return 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
        },

        formatDiff(row) {
            if (row.status === 'new') return 'Nuova';
            if (row.status === 'lost') return 'Persa';
            if (row.diff === null || row.diff === 0) return '0';
            const sign = row.diff > 0 ? '+' : '';
            return sign + row.diff.toFixed(1);
        }
    };
}
</script>
