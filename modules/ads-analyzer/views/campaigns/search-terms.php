<?php
$currentPage = 'search-term-analysis';
include __DIR__ . '/../partials/project-nav.php';

$config = json_encode([
    'projectId' => $project['id'],
    'baseUrl' => url("/ads-analyzer/projects/{$project['id']}/search-term-analysis"),
    'csrfToken' => csrf_token(),
    'runs' => array_map(fn($r) => [
        'id' => $r['id'],
        'label' => date('d/m/Y H:i', strtotime($r['created_at'])),
        'date_start' => $r['date_range_start'] ?? null,
        'date_end' => $r['date_range_end'] ?? null,
        'run_type' => $r['run_type'],
    ], $searchTermRuns),
    'selectedRunId' => $selectedRun ? $selectedRun['id'] : null,
    'initialStats' => $searchTermStats,
    'initialAdGroups' => array_map(fn($ag) => [
        'id' => $ag['id'],
        'name' => $ag['name'],
        'terms_count' => (int)$ag['terms_count'],
        'zero_ctr_count' => (int)$ag['zero_ctr_count'],
        'wasted_impressions' => (int)$ag['wasted_impressions'],
        'landing_url' => $ag['landing_url'] ?? '',
        'extracted_context' => $ag['extracted_context'] ?? '',
        'negatives_count' => (int)($ag['negatives_count'] ?? 0),
    ], $adGroups),
    'analyses' => array_map(fn($a) => [
        'id' => $a['id'],
        'name' => $a['name'],
        'status' => $a['status'],
        'total_keywords' => (int)($a['total_keywords'] ?? 0),
        'total_categories' => (int)($a['total_categories'] ?? 0),
        'run_id' => $a['run_id'] ?? null,
        'created_at' => date('d/m/Y H:i', strtotime($a['created_at'])),
    ], $analyses),
    'userCredits' => $userCredits,
]);
?>

<div class="space-y-6" x-data="searchTermAnalysis(<?= htmlspecialchars($config) ?>)">

    <?php if (empty($searchTermRuns)): ?>
    <!-- Empty State: nessun run con search terms -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun dato Search Terms</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Lo script Google Ads deve raccogliere i termini di ricerca. Verifica che la configurazione includa "Search Terms" o "Entrambi".
        </p>
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            Configura Script
        </a>
    </div>
    <?php else: ?>

    <!-- SEZIONE 1: Run Selector + Stats -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Termini di Ricerca</h2>
            <div class="flex items-center gap-3">
                <label class="text-sm text-slate-500 dark:text-slate-400">Run:</label>
                <select x-model="selectedRunId" @change="loadRunData()" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <template x-for="run in runs" :key="run.id">
                        <option :value="run.id" x-text="run.label + (run.date_start ? ' (' + run.date_start + ' - ' + run.date_end + ')' : '')"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="stats.total_terms"></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Termini totali</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="stats.zero_ctr_count"></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Zero CTR</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" x-text="formatNumber(stats.wasted_impressions)"></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Impression sprecate</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="'€' + stats.total_cost.toFixed(2)"></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Spesa totale</p>
            </div>
        </div>
    </div>

    <!-- SEZIONE 2: Search Terms Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700" x-show="adGroups.length > 0">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <button @click="showTerms = !showTerms" class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400">
                <svg class="w-4 h-4 transition-transform" :class="showTerms && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                Mostra termini di ricerca
                <span class="text-xs text-slate-400" x-text="'(' + stats.total_terms + ' termini)'"></span>
            </button>
            <div class="flex items-center gap-3" x-show="showTerms">
                <label class="inline-flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 cursor-pointer">
                    <input type="checkbox" x-model="showOnlyZeroCtr" class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                    Solo zero-CTR
                </label>
                <input type="text" x-model="searchFilter" placeholder="Cerca termine..."
                       class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white w-48">
            </div>
        </div>

        <div x-show="showTerms && !loadingRunData" x-collapse>
            <!-- Ad Group tabs -->
            <div class="border-b border-slate-200 dark:border-slate-700 overflow-x-auto" x-show="adGroups.length > 1">
                <nav class="flex -mb-px">
                    <template x-for="ag in adGroups" :key="ag.id">
                        <button @click="activeAdGroupTab = ag.id"
                                :class="activeAdGroupTab === ag.id ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                                class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                            <span x-text="ag.name"></span>
                            <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-700" x-text="ag.terms_count"></span>
                        </button>
                    </template>
                </nav>
            </div>

            <!-- Terms list -->
            <div class="max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Termine</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Click</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Impression</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">CTR</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <template x-for="term in filteredTerms" :key="term.id">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">
                                    <span x-text="term.term"></span>
                                    <span x-show="term.is_zero_ctr" class="ml-1 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400">0 CTR</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-right text-slate-700 dark:text-slate-300" x-text="term.clicks"></td>
                                <td class="px-4 py-2 text-sm text-right text-slate-700 dark:text-slate-300" x-text="formatNumber(term.impressions)"></td>
                                <td class="px-4 py-2 text-sm text-right" :class="term.ctr === 0 ? 'text-red-500' : 'text-slate-700 dark:text-slate-300'" x-text="term.ctr.toFixed(2) + '%'"></td>
                                <td class="px-4 py-2 text-sm text-right text-slate-700 dark:text-slate-300" x-text="'€' + term.cost.toFixed(2)"></td>
                            </tr>
                        </template>
                        <template x-if="filteredTerms.length === 0">
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">
                                    <span x-show="loadingRunData">Caricamento...</span>
                                    <span x-show="!loadingRunData">Nessun termine trovato</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SEZIONE 3: Contesto & Analisi -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6" x-show="adGroups.length > 0">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Analisi Keyword Negative</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Colonna sinistra: URL Landing -->
            <div>
                <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">URL Landing Pages</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                    Rileva automaticamente gli URL delle landing page dagli annunci Google Ads per estrarre il contesto business.
                </p>

                <!-- Bottone rileva URL -->
                <button @click="detectUrls()" :disabled="isDetecting"
                        class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors mb-4">
                    <svg x-show="!isDetecting" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <svg x-show="isDetecting" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="isDetecting ? 'Rilevamento...' : 'Rileva URL dagli annunci'"></span>
                </button>

                <!-- Ad Groups con URL -->
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <template x-for="ag in adGroups" :key="ag.id">
                        <div class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-700/30 text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300 flex-shrink-0" x-text="ag.name"></span>
                            <template x-if="ag.landing_url">
                                <div class="flex items-center gap-1 min-w-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate" x-text="ag.landing_url"></span>
                                </div>
                            </template>
                            <template x-if="!ag.landing_url">
                                <span class="text-xs text-slate-400">Nessun URL</span>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Bottone estrai contesti -->
                <div class="mt-4" x-show="adGroups.filter(a => a.landing_url).length > 0">
                    <button @click="extractContexts()" :disabled="isExtracting"
                            class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <svg x-show="!isExtracting" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                        </svg>
                        <svg x-show="isExtracting" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="isExtracting ? 'Estrazione contesto...' : 'Estrai contesto landing pages'"></span>
                    </button>
                </div>
            </div>

            <!-- Colonna destra: Contesto Business + Analisi -->
            <div>
                <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Contesto Business</h3>
                <textarea x-model="businessContext" rows="5" placeholder="Descrivi l'attivita, i prodotti/servizi offerti, il target di riferimento..."
                          class="w-full text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                <p class="text-xs text-slate-400 mt-1">
                    I contesti estratti dalle landing pages verranno integrati automaticamente. Min 20 caratteri.
                </p>

                <!-- Bottone Analisi AI -->
                <div class="mt-4">
                    <button @click="startAnalysis()" :disabled="isAnalyzing || businessContext.length < 20"
                            class="inline-flex items-center px-4 py-2.5 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <svg x-show="!isAnalyzing" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        <svg x-show="isAnalyzing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="isAnalyzing ? 'Analisi in corso...' : 'Analizza con AI'"></span>
                    </button>
                    <span class="ml-2 text-xs text-slate-400" x-text="'Crediti: ' + userCredits"></span>
                </div>
            </div>
        </div>

        <!-- Status messages -->
        <div x-show="statusMessage" class="mt-4">
            <div :class="statusType === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'"
                 class="rounded-lg border px-4 py-3 text-sm">
                <span x-text="statusMessage"></span>
            </div>
        </div>
    </div>

    <!-- SEZIONE 4: Risultati -->
    <div x-show="analysisResults !== null" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Header risultati -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword Negative Trovate</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span x-text="selectedCount"></span> selezionate su <span x-text="totalCount"></span> totali
                </p>
            </div>
            <div class="flex items-center gap-2">
                <!-- Export buttons -->
                <a :href="baseUrl + '/export?analysis_id=' + currentAnalysisId + '&format=csv'"
                   x-show="currentAnalysisId"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    CSV
                </a>
                <a :href="baseUrl + '/export?analysis_id=' + currentAnalysisId + '&format=google-ads-editor'"
                   x-show="currentAnalysisId"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-300 dark:hover:bg-blue-800/50 transition-colors">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Google Ads Editor
                </a>
            </div>
        </div>

        <!-- Analisi selector (se piu analisi) -->
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700" x-show="analyses.length > 1">
            <select x-model="currentAnalysisId" @change="loadResults(currentAnalysisId)"
                    class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <template x-for="a in analyses" :key="a.id">
                    <option :value="a.id" x-text="a.name + ' (' + a.total_keywords + ' kw)'"></option>
                </template>
            </select>
        </div>

        <!-- Ad Group tabs -->
        <div class="border-b border-slate-200 dark:border-slate-700 overflow-x-auto" x-show="Object.keys(categoriesByAdGroup).length > 1">
            <nav class="flex -mb-px">
                <template x-for="(cats, agId) in categoriesByAdGroup" :key="agId">
                    <button @click="activeResultTab = parseInt(agId)"
                            :class="activeResultTab === parseInt(agId) ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                            class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        <span x-text="adGroupNames[agId] || 'Gruppo ' + agId"></span>
                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-700"
                              x-text="cats.reduce((sum, c) => sum + c.keywords.length, 0)"></span>
                    </button>
                </template>
            </nav>
        </div>

        <!-- Categories & Keywords -->
        <div class="p-4 space-y-4">
            <template x-for="(cats, agId) in categoriesByAdGroup" :key="'results-' + agId">
                <div x-show="activeResultTab === parseInt(agId) || Object.keys(categoriesByAdGroup).length === 1">
                    <template x-for="cat in cats" :key="cat.id">
                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden mb-3" x-data="{ expanded: true }">
                            <!-- Category Header -->
                            <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-3 flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="cat.priority === 'high' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : cat.priority === 'medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                                          x-text="cat.priority === 'high' ? 'Alta' : cat.priority === 'medium' ? 'Media' : 'Valuta'"></span>
                                    <span class="font-medium text-slate-900 dark:text-white text-sm" x-text="cat.category_name"></span>
                                    <span class="text-xs text-slate-400" x-text="cat.selected_keywords + '/' + cat.total_keywords + ' selezionate'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Bulk actions -->
                                    <button @click.stop="toggleCategoryAction(cat.id, 'select_all')" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Tutti</button>
                                    <button @click.stop="toggleCategoryAction(cat.id, 'deselect_all')" class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400">Nessuno</button>
                                    <button @click.stop="toggleCategoryAction(cat.id, 'invert')" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Inverti</button>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                            <!-- Description -->
                            <div x-show="expanded && cat.description" class="px-4 py-2 bg-slate-50/50 dark:bg-slate-700/20 border-b border-slate-200 dark:border-slate-700">
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="cat.description"></p>
                            </div>
                            <!-- Keywords -->
                            <div x-show="expanded" class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="kw in cat.keywords" :key="kw.id">
                                        <button @click="toggleKeywordAction(kw.id, kw, cat)"
                                                :class="kw.is_selected ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-900/50 dark:text-blue-300 dark:border-blue-700' : 'bg-slate-100 text-slate-500 border-slate-200 line-through dark:bg-slate-700 dark:text-slate-400 dark:border-slate-600'"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border transition-colors hover:opacity-80">
                                            <span x-text="kw.keyword"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Storico analisi precedenti -->
    <div x-show="analyses.length > 0 && analysisResults === null" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Analisi Precedenti</h2>
        <div class="space-y-2">
            <template x-for="a in analyses" :key="a.id">
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors cursor-pointer"
                     @click="loadResults(a.id)">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="a.name"></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="a.created_at + ' - ' + a.total_keywords + ' keyword, ' + a.total_categories + ' categorie'"></p>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </template>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function searchTermAnalysis(config) {
    return {
        // Config
        projectId: config.projectId,
        baseUrl: config.baseUrl,
        csrfToken: config.csrfToken,
        runs: config.runs,
        userCredits: config.userCredits,

        // State
        selectedRunId: config.selectedRunId,
        stats: config.initialStats || { total_terms: 0, zero_ctr_count: 0, wasted_impressions: 0, total_clicks: 0, total_impressions: 0, total_cost: 0 },
        adGroups: config.initialAdGroups || [],
        searchTermsByGroup: {},
        analyses: config.analyses || [],

        // UI
        showTerms: false,
        showOnlyZeroCtr: false,
        searchFilter: '',
        activeAdGroupTab: (config.initialAdGroups && config.initialAdGroups.length > 0) ? config.initialAdGroups[0].id : null,
        loadingRunData: false,

        // Analysis
        businessContext: '',
        isDetecting: false,
        isExtracting: false,
        isAnalyzing: false,
        statusMessage: '',
        statusType: 'info',

        // Results
        analysisResults: null,
        categoriesByAdGroup: {},
        adGroupNames: {},
        currentAnalysisId: null,
        selectedCount: 0,
        totalCount: 0,
        activeResultTab: null,

        formatNumber(n) {
            return new Intl.NumberFormat('it-IT').format(n);
        },

        get filteredTerms() {
            const terms = this.searchTermsByGroup[this.activeAdGroupTab] || [];
            return terms.filter(t => {
                if (this.showOnlyZeroCtr && !t.is_zero_ctr) return false;
                if (this.searchFilter && !t.term.toLowerCase().includes(this.searchFilter.toLowerCase())) return false;
                return true;
            });
        },

        async loadRunData() {
            this.loadingRunData = true;
            try {
                const resp = await fetch(this.baseUrl + '/run-data?run_id=' + this.selectedRunId);
                const data = await resp.json();
                if (data.success) {
                    this.adGroups = data.adGroups;
                    this.stats = data.stats;
                    this.searchTermsByGroup = data.searchTermsByGroup;
                    if (this.adGroups.length > 0) {
                        this.activeAdGroupTab = this.adGroups[0].id;
                    }
                }
            } catch (e) {
                console.error('loadRunData error:', e);
            }
            this.loadingRunData = false;
        },

        async detectUrls() {
            this.isDetecting = true;
            this.statusMessage = '';
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('run_id', this.selectedRunId);

                const resp = await fetch(this.baseUrl + '/detect-urls', { method: 'POST', body: formData });
                const data = await resp.json();

                if (data.success) {
                    this.statusMessage = `URL rilevati: ${data.matched} su ${data.total_ad_groups} Ad Group`;
                    this.statusType = 'info';
                    await this.loadRunData();
                } else {
                    this.statusMessage = data.error || 'Errore nel rilevamento URL';
                    this.statusType = 'error';
                }
            } catch (e) {
                this.statusMessage = 'Errore di connessione';
                this.statusType = 'error';
            }
            this.isDetecting = false;
        },

        async extractContexts() {
            this.isExtracting = true;
            this.statusMessage = 'Estrazione contesti in corso... potrebbe richiedere qualche minuto.';
            this.statusType = 'info';
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('run_id', this.selectedRunId);

                const resp = await fetch(this.baseUrl + '/extract-contexts', { method: 'POST', body: formData });

                if (!resp.ok) {
                    this.statusMessage = 'Errore del server (timeout). Riprova tra qualche istante.';
                    this.statusType = 'error';
                    this.isExtracting = false;
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    const ok = data.results.filter(r => r.success).length;
                    const fail = data.results.filter(r => !r.success).length;
                    this.statusMessage = `Contesti estratti: ${ok} OK` + (fail > 0 ? `, ${fail} errori` : '');
                    this.statusType = 'info';

                    // Aggiorna i contesti negli ad groups
                    data.results.forEach(r => {
                        if (r.success) {
                            const ag = this.adGroups.find(a => a.id === r.ad_group_id);
                            if (ag) ag.extracted_context = r.context;
                        }
                    });

                    // Prepopola business context con primo contesto
                    if (!this.businessContext && ok > 0) {
                        const first = data.results.find(r => r.success);
                        if (first) this.businessContext = first.context;
                    }
                } else {
                    this.statusMessage = data.error || 'Errore estrazione';
                    this.statusType = 'error';
                }
            } catch (e) {
                this.statusMessage = 'Errore di connessione';
                this.statusType = 'error';
            }
            this.isExtracting = false;
        },

        async startAnalysis() {
            this.isAnalyzing = true;
            this.statusMessage = 'Analisi AI in corso... potrebbe richiedere qualche minuto.';
            this.statusType = 'info';
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('run_id', this.selectedRunId);
                formData.append('business_context', this.businessContext);

                const resp = await fetch(this.baseUrl + '/analyze', { method: 'POST', body: formData });

                if (!resp.ok) {
                    this.statusMessage = 'Errore del server (timeout). Riprova.';
                    this.statusType = 'error';
                    this.isAnalyzing = false;
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    this.statusMessage = `Analisi completata: ${data.total_negatives} keyword negative in ${data.total_categories} categorie`;
                    this.statusType = 'info';
                    this.currentAnalysisId = data.analysis_id;

                    // Aggiungi all'elenco
                    this.analyses.unshift({
                        id: data.analysis_id,
                        name: 'Analisi KW Negative ' + new Date().toLocaleString('it-IT'),
                        status: 'completed',
                        total_keywords: data.total_negatives,
                        total_categories: data.total_categories,
                        run_id: this.selectedRunId,
                        created_at: new Date().toLocaleString('it-IT'),
                    });

                    await this.loadResults(data.analysis_id);
                } else {
                    this.statusMessage = data.error || 'Errore analisi';
                    this.statusType = 'error';
                }
            } catch (e) {
                this.statusMessage = 'Errore di connessione';
                this.statusType = 'error';
            }
            this.isAnalyzing = false;
        },

        async loadResults(analysisId) {
            try {
                const resp = await fetch(this.baseUrl + '/results?analysis_id=' + analysisId);
                const data = await resp.json();
                if (data.success) {
                    this.analysisResults = data;
                    this.categoriesByAdGroup = data.categoriesByAdGroup;
                    this.adGroupNames = data.adGroupNames;
                    this.selectedCount = data.selectedCount;
                    this.totalCount = data.totalCount;
                    this.currentAnalysisId = analysisId;

                    // Seleziona primo tab risultati
                    const firstKey = Object.keys(this.categoriesByAdGroup)[0];
                    if (firstKey) this.activeResultTab = parseInt(firstKey);
                }
            } catch (e) {
                console.error('loadResults error:', e);
            }
        },

        async toggleKeywordAction(keywordId, kw, cat) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);

                const resp = await fetch(this.baseUrl + '/keywords/' + keywordId + '/toggle', { method: 'POST', body: formData });
                const data = await resp.json();

                if (data.success) {
                    kw.is_selected = data.is_selected;
                    this.selectedCount = data.selected_count;

                    // Aggiorna conteggio categoria
                    cat.selected_keywords += data.is_selected ? 1 : -1;
                }
            } catch (e) {
                console.error('toggleKeyword error:', e);
            }
        },

        async toggleCategoryAction(categoryId, action) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);

                const resp = await fetch(this.baseUrl + '/categories/' + categoryId + '/' + action, { method: 'POST', body: formData });
                const data = await resp.json();

                if (data.success) {
                    this.selectedCount = data.selected_count;
                    // Reload results to refresh keyword states
                    if (this.currentAnalysisId) {
                        await this.loadResults(this.currentAnalysisId);
                    }
                }
            } catch (e) {
                console.error('toggleCategory error:', e);
            }
        }
    };
}
</script>
