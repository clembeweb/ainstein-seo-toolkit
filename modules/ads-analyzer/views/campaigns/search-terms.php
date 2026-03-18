<?php
$currentPage = 'search-term-analysis';
$canEdit = ($access_role ?? 'owner') !== 'viewer';
include __DIR__ . '/../partials/project-nav.php';

$config = json_encode([
    'projectId' => $project['id'],
    'baseUrl' => url("/ads-analyzer/projects/{$project['id']}/search-term-analysis"),
    'csrfToken' => csrf_token(),
    'syncs' => array_map(fn($s) => [
        'id' => $s['id'],
        'label' => date('d/m/Y H:i', strtotime($s['started_at'])),
        'date_start' => $s['date_range_start'] ?? null,
        'date_end' => $s['date_range_end'] ?? null,
        'search_terms_synced' => (int)($s['search_terms_synced'] ?? 0),
    ], $searchTermSyncs),
    'selectedSyncId' => $selectedSync ? $selectedSync['id'] : null,
    'initialStats' => $searchTermStats,
    'analyses' => array_map(fn($a) => [
        'id' => $a['id'],
        'name' => $a['name'],
        'status' => $a['status'],
        'total_keywords' => (int)($a['total_keywords'] ?? 0),
        'total_categories' => (int)($a['total_categories'] ?? 0),
        'sync_id' => $a['sync_id'] ?? null,
        'created_at' => date('d/m/Y H:i', strtotime($a['created_at'])),
    ], $analyses),
    'latestAnalysisId' => $latestAnalysis ? $latestAnalysis['id'] : null,
    'appliedCount' => $appliedCount ?? 0,
    'lastAppliedDate' => $lastAppliedDate ? date('d/m/Y', strtotime($lastAppliedDate)) : null,
    'canEdit' => $canEdit,
    'userCredits' => $userCredits ?? 0,
]);
?>

<div x-data="searchTermAnalysis(<?= htmlspecialchars($config, ENT_QUOTES) ?>)" class="space-y-6">

    <!-- ========== KPI HEADER ========== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Termini totali -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                Termini di ricerca
            </div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white" x-text="stats.total_terms?.toLocaleString('it-IT') || '0'"></div>
            <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5" x-show="stats.zero_ctr_count > 0">
                <span x-text="stats.zero_ctr_count?.toLocaleString('it-IT')"></span> con CTR zero
            </div>
        </div>

        <!-- Spreco stimato -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Spreco stimato
            </div>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">&euro;<span x-text="Number(stats.total_cost || 0).toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></div>
            <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5" x-show="stats.wasted_impressions > 0">
                <span x-text="stats.wasted_impressions?.toLocaleString('it-IT')"></span> impression sprecate
            </div>
        </div>

        <!-- Negative applicate -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Negative applicate
            </div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="appliedCount.toLocaleString('it-IT')"></div>
            <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5" x-show="lastAppliedDate" x-text="'Ultima: ' + (lastAppliedDate || '')"></div>
        </div>

        <!-- Ultima sync -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Ultima sync
            </div>
            <template x-if="syncs.length > 0">
                <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white" x-text="syncs[0]?.label || '-'"></div>
                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                        <!-- Dropdown sync secondario -->
                        <template x-if="syncs.length > 1">
                            <select x-model="selectedSyncId" @change="changeSyncId()" class="text-xs bg-transparent border-none p-0 text-slate-400 dark:text-slate-500 cursor-pointer focus:ring-0">
                                <template x-for="s in syncs" :key="s.id">
                                    <option :value="s.id" x-text="s.label + ' (' + s.search_terms_synced + ' termini)'"></option>
                                </template>
                            </select>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="syncs.length === 0">
                <div class="text-lg text-slate-400 dark:text-slate-500">Nessuna sync</div>
            </template>
        </div>
    </div>

    <!-- ========== BANNER POST-APPLICAZIONE ========== -->
    <template x-if="justApplied">
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-medium text-emerald-800 dark:text-emerald-300" x-text="justAppliedMessage"></p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-1">La prossima sincronizzazione mostrera l'effetto delle negative applicate. Consigliamo di attendere 2-3 giorni.</p>
                    <a :href="'<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>'" class="inline-flex items-center gap-1 text-sm text-emerald-700 dark:text-emerald-300 hover:underline mt-2">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Nuova sincronizzazione
                    </a>
                </div>
            </div>
        </div>
    </template>

    <!-- ========== NO SYNC STATE ========== -->
    <template x-if="syncs.length === 0">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Nessuna sincronizzazione disponibile</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">Sincronizza i dati da Google Ads per iniziare l'analisi dei termini di ricerca.</p>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 text-white rounded-lg text-sm font-medium hover:bg-rose-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Vai alla Connessione
            </a>
        </div>
    </template>

    <!-- ========== CTA ANALISI ========== -->
    <template x-if="syncs.length > 0 && !analyzing && !currentAnalysis">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-8 text-center">
            <div class="max-w-lg mx-auto">
                <svg class="w-12 h-12 mx-auto text-rose-500 dark:text-rose-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Analizza i termini di ricerca con AI</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">L'AI analizzera i tuoi termini di ricerca, identifichera quelli fuori target e suggerira le keyword negative da aggiungere su Google Ads.</p>

                <!-- Contesto opzionale collapsato -->
                <div class="mb-6">
                    <button @click="showContextInput = !showContextInput" type="button" class="text-xs text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors flex items-center gap-1 mx-auto">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="showContextInput ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Aggiungi contesto business (opzionale)
                    </button>
                    <div x-show="showContextInput" x-transition class="mt-3 text-left">
                        <textarea x-model="manualContext" rows="3" placeholder="Es: Agenzia che promuove universita telematiche (Mercatorum, Pegaso, San Raffaele)..." class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-rose-500 focus:border-rose-500"></textarea>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Il contesto verra arricchito automaticamente con le informazioni delle campagne.</p>
                    </div>
                </div>

                <button @click="startAnalysis()" :disabled="analyzing" class="inline-flex items-center gap-2 px-6 py-3 bg-rose-600 text-white rounded-lg text-sm font-semibold hover:bg-rose-700 transition-colors disabled:opacity-50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Analizza con AI
                </button>
            </div>
        </div>
    </template>

    <!-- ========== ANALYZING STATE ========== -->
    <template x-if="analyzing">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="animate-spin w-10 h-10 border-4 border-rose-200 dark:border-rose-800 border-t-rose-600 dark:border-t-rose-400 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Analisi in corso...</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">L'AI sta analizzando i termini di ricerca. Questo puo richiedere 30-60 secondi.</p>
        </div>
    </template>

    <!-- ========== RISULTATI ANALISI ========== -->
    <template x-if="currentAnalysis && !analyzing">
        <div class="space-y-4">

            <!-- Riepilogo -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Risultati analisi
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            <span class="font-medium text-slate-700 dark:text-slate-300" x-text="totalCount"></span> keyword negative trovate in
                            <span class="font-medium text-slate-700 dark:text-slate-300" x-text="categories.length"></span> categorie
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="startAnalysis()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Ri-analizza
                        </button>
                    </div>
                </div>

                <!-- Confronto con analisi precedente -->
                <template x-if="comparison.has_previous">
                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 text-xs font-medium" x-show="comparison.resolved > 0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="comparison.resolved"></span> risolte
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 text-xs font-medium" x-show="comparison.recurring > 0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            <span x-text="comparison.recurring"></span> ricorrenti
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 text-xs font-medium" x-show="comparison.new > 0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span x-text="comparison.new"></span> nuove
                        </span>
                    </div>
                </template>
            </div>

            <!-- Categorie accordion raggruppate per Ad Group -->
            <template x-for="(cat, catIdx) in categories" :key="cat.id">
                <div>
                <!-- Ad Group separator -->
                <template x-if="catIdx === 0 || cat._adGroupId !== categories[catIdx - 1]._adGroupId">
                    <div class="flex items-center gap-2 mt-2 mb-1" :class="catIdx > 0 ? 'pt-3 border-t border-slate-200 dark:border-slate-700' : ''">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300" x-text="cat._adGroupName || 'Ad Group'"></span>
                        <span class="text-xs text-slate-400 dark:text-slate-500" x-text="'(' + categories.filter(c => c._adGroupId === cat._adGroupId).length + ' categorie)'"></span>
                    </div>
                </template>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <!-- Header categoria -->
                    <button @click="cat._open = !cat._open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="cat._open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="font-medium text-slate-900 dark:text-white truncate" x-text="cat.category_name"></span>
                            <!-- Priority badge -->
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0"
                                  :class="{
                                      'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300': cat.priority === 'high',
                                      'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300': cat.priority === 'medium',
                                      'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400': cat.priority === 'evaluate'
                                  }" x-text="cat.priority === 'high' ? 'Alta' : cat.priority === 'medium' ? 'Media' : 'Da valutare'"></span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 shrink-0" x-text="cat.keywords.length + ' keyword'"></span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <!-- Select all toggle -->
                            <label @click.stop class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <input type="checkbox" :checked="cat.keywords.every(k => k.is_selected)" @change="toggleAllCategory(cat, $event.target.checked)" class="rounded border-slate-300 dark:border-slate-600 text-rose-600 focus:ring-rose-500">
                                Tutte
                            </label>
                        </div>
                    </button>

                    <!-- Descrizione -->
                    <template x-if="cat.description && cat._open">
                        <div class="px-4 pb-2 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700 pt-2" x-text="cat.description"></div>
                    </template>

                    <!-- Keyword list -->
                    <div x-show="cat._open" x-transition class="border-t border-slate-100 dark:border-slate-700">
                        <template x-for="kw in cat.keywords" :key="kw.id">
                            <div class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/30 border-b border-slate-50 dark:border-slate-700/50 last:border-0">
                                <input type="checkbox" :checked="kw.is_selected" @change="toggleKeyword(kw)" class="rounded border-slate-300 dark:border-slate-600 text-rose-600 focus:ring-rose-500 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm text-slate-900 dark:text-white font-mono" x-text="kw.keyword"></span>
                                </div>
                                <!-- Match type badge -->
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase shrink-0"
                                      :class="{
                                          'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300': kw.suggested_match_type === 'exact',
                                          'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300': kw.suggested_match_type === 'phrase',
                                          'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400': kw.suggested_match_type === 'broad'
                                      }" x-text="kw.suggested_match_type || 'phrase'"></span>
                                <!-- Level badge -->
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium shrink-0"
                                      :class="kw.suggested_level === 'ad_group' ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'">
                                    <template x-if="kw.suggested_level === 'campaign'">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                                    </template>
                                    <template x-if="kw.suggested_level === 'ad_group'">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </template>
                                    <span x-text="kw.suggested_level === 'ad_group' ? 'Ad Group' : 'Campagna'"></span>
                                </span>
                                <!-- Status badge (new/recurring) -->
                                <template x-if="kw.status === 'recurring'">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 shrink-0">Ricorrente</span>
                                </template>
                                <template x-if="kw.status === 'new'">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 shrink-0">Nuova</span>
                                </template>
                                <!-- Applied -->
                                <template x-if="kw.applied_at">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 shrink-0">Applicata</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                </div>
            </template>
        </div>
    </template>

    <!-- ========== BARRA STICKY AZIONI ========== -->
    <template x-if="currentAnalysis && !analyzing && selectedCount > 0">
        <div class="sticky bottom-4 z-30">
            <div class="bg-slate-900 dark:bg-slate-700 rounded-xl shadow-2xl px-5 py-3 flex items-center justify-between gap-4 border border-slate-700 dark:border-slate-600">
                <div class="text-sm text-white">
                    <span class="font-semibold" x-text="selectedCount"></span>/<span x-text="totalCount"></span> selezionate
                </div>
                <div class="flex items-center gap-2">
                    <!-- Export CSV -->
                    <a :href="baseUrl + '/export?analysis_id=' + currentAnalysis.id + '&format=csv'" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-300 hover:text-white bg-slate-800 dark:bg-slate-600 rounded-lg hover:bg-slate-700 dark:hover:bg-slate-500 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        CSV
                    </a>
                    <!-- Export Google Ads Editor -->
                    <a :href="baseUrl + '/export?analysis_id=' + currentAnalysis.id + '&format=google-ads-editor'" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-300 hover:text-white bg-slate-800 dark:bg-slate-600 rounded-lg hover:bg-slate-700 dark:hover:bg-slate-500 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Google Ads Editor
                    </a>
                    <!-- Applica su Google Ads -->
                    <?php if ($canEdit): ?>
                    <button @click="showApplyModal = true" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Applica su Google Ads
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </template>

    <!-- ========== MODALE CONFERMA APPLICAZIONE ========== -->
    <template x-if="showApplyModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showApplyModal = false">
            <div class="absolute inset-0 bg-black/50" @click="showApplyModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Conferma applicazione</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Stai per aggiungere <span class="font-semibold" x-text="selectedCount"></span> negative keywords su Google Ads:
                </p>

                <!-- Riepilogo per livello -->
                <div class="space-y-2">
                    <template x-if="applySummary.campaign > 0">
                        <div class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            <span x-text="applySummary.campaign"></span> a livello campagna
                        </div>
                    </template>
                    <template x-if="applySummary.ad_group > 0">
                        <div class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-cyan-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span x-text="applySummary.ad_group"></span> a livello ad group
                        </div>
                    </template>
                </div>

                <!-- Warning -->
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 text-sm text-amber-800 dark:text-amber-300 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Questa azione applichera le keyword direttamente sul tuo account Google Ads. L'operazione non e reversibile automaticamente.
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button @click="showApplyModal = false" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">Annulla</button>
                    <button @click="applyNegatives()" :disabled="applying" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition-colors disabled:opacity-50">
                        <template x-if="applying">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        <span x-text="applying ? 'Applicazione...' : 'Applica ' + selectedCount + ' keyword'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- ========== STORICO ANALISI ========== -->
    <template x-if="analyses.length > 0">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700" x-data="{ historyOpen: false }">
            <button @click="historyOpen = !historyOpen" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Analisi precedenti (<span x-text="analyses.length"></span>)
                </span>
                <svg class="w-4 h-4 text-slate-400 transition-transform" :class="historyOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="historyOpen" x-transition class="border-t border-slate-100 dark:border-slate-700">
                <template x-for="a in analyses" :key="a.id">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-50 dark:border-slate-700/50 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <div class="min-w-0">
                            <div class="text-sm text-slate-900 dark:text-white truncate" x-text="a.name"></div>
                            <div class="text-xs text-slate-400 dark:text-slate-500" x-text="a.created_at"></div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0 ml-3">
                            <span class="text-xs text-slate-500 dark:text-slate-400" x-text="a.total_keywords + ' keyword'"></span>
                            <button @click="loadAnalysis(a.id)" class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-medium">Carica</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>

<script>
function searchTermAnalysis(config) {
    return {
        // Config
        projectId: config.projectId,
        baseUrl: config.baseUrl,
        csrfToken: config.csrfToken,
        canEdit: config.canEdit,

        // Sync
        syncs: config.syncs || [],
        selectedSyncId: config.selectedSyncId,

        // Stats
        stats: config.initialStats || {},

        // Analisi
        analyses: config.analyses || [],
        currentAnalysis: null,
        categories: [],
        comparison: { has_previous: false, resolved: 0, recurring: 0, new: 0 },

        // Contatori
        selectedCount: 0,
        totalCount: 0,
        appliedCount: config.appliedCount || 0,
        lastAppliedDate: config.lastAppliedDate,

        // UI state
        analyzing: false,
        applying: false,
        showApplyModal: false,
        showContextInput: false,
        manualContext: '',
        justApplied: false,
        justAppliedMessage: '',

        get applySummary() {
            let campaign = 0, ad_group = 0;
            for (const cat of this.categories) {
                for (const kw of cat.keywords) {
                    if (kw.is_selected && !kw.applied_at) {
                        if (kw.suggested_level === 'ad_group') ad_group++;
                        else campaign++;
                    }
                }
            }
            return { campaign, ad_group };
        },

        init() {
            if (config.latestAnalysisId) {
                this.loadAnalysis(config.latestAnalysisId);
            }
        },

        changeSyncId() {
            // Ricarica stats per la sync selezionata
            fetch(this.baseUrl + '/sync-data?sync_id=' + this.selectedSyncId)
                .then(r => {
                    if (!r.ok) throw new Error(`Errore server (${r.status})`);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        this.stats = data.stats;
                    }
                })
                .catch(err => console.error('Error loading sync data:', err));
        },

        async startAnalysis() {
            this.analyzing = true;
            this.currentAnalysis = null;
            this.categories = [];

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('sync_id', this.selectedSyncId);
                if (this.manualContext) {
                    formData.append('business_context', this.manualContext);
                }

                const resp = await fetch(this.baseUrl + '/analyze', {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    const err = await resp.json();
                    throw new Error(err.error || 'Errore analisi');
                }

                const data = await resp.json();
                if (data.error) throw new Error(data.error);

                // Aggiungi alla lista analisi
                this.analyses.unshift({
                    id: data.analysis_id,
                    name: 'Analisi KW Negative ' + new Date().toLocaleString('it-IT'),
                    status: 'completed',
                    total_keywords: data.total_negatives,
                    total_categories: data.total_categories,
                    created_at: new Date().toLocaleString('it-IT'),
                });

                // Carica risultati
                await this.loadAnalysis(data.analysis_id);

            } catch (e) {
                alert('Errore: ' + e.message);
            } finally {
                this.analyzing = false;
            }
        },

        async loadAnalysis(analysisId) {
            try {
                const resp = await fetch(this.baseUrl + '/results?analysis_id=' + analysisId);
                const data = await resp.json();
                if (!data.success) return;

                this.currentAnalysis = data.analysis;
                this.comparison = data.comparison || { has_previous: false };
                this.selectedCount = data.selectedCount || 0;
                this.totalCount = data.totalCount || 0;

                // Categorie raggruppate per ad group con header separatore
                const cats = [];
                const adGroupNames = data.adGroupNames || {};
                for (const [agId, agCats] of Object.entries(data.categoriesByAdGroup || {})) {
                    for (const cat of agCats) {
                        cat._open = false;
                        cat._adGroupId = agId;
                        cat._adGroupName = adGroupNames[agId] || '';
                    }
                    agCats.sort((a, b) => {
                        const prio = { high: 0, medium: 1, evaluate: 2 };
                        return (prio[a.priority] ?? 1) - (prio[b.priority] ?? 1);
                    });
                    cats.push(...agCats);
                }
                this.categories = cats;

            } catch (e) {
                console.error('Error loading analysis:', e);
            }
        },

        async toggleKeyword(kw) {
            kw.is_selected = !kw.is_selected;
            this.recountSelected();

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                const resp = await fetch(this.baseUrl + '/keywords/' + kw.id + '/toggle', {
                    method: 'POST',
                    body: formData,
                });
                if (!resp.ok) {
                    throw new Error(`Errore server (${resp.status})`);
                }
            } catch (e) {
                console.error('Toggle keyword failed:', e);
            }
        },

        async toggleAllCategory(cat, checked) {
            cat.keywords.forEach(kw => kw.is_selected = checked);
            this.recountSelected();

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                const resp = await fetch(this.baseUrl + '/categories/' + cat.id + '/' + (checked ? 'select_all' : 'deselect_all'), {
                    method: 'POST',
                    body: formData,
                });
                if (!resp.ok) {
                    throw new Error(`Errore server (${resp.status})`);
                }
            } catch (e) {
                console.error('Toggle category failed:', e);
            }
        },

        recountSelected() {
            let sel = 0, tot = 0;
            for (const cat of this.categories) {
                for (const kw of cat.keywords) {
                    tot++;
                    if (kw.is_selected) sel++;
                }
            }
            this.selectedCount = sel;
            this.totalCount = tot;
        },

        async applyNegatives() {
            this.applying = true;

            try {
                const selectedIds = [];
                for (const cat of this.categories) {
                    for (const kw of cat.keywords) {
                        if (kw.is_selected && !kw.applied_at) {
                            selectedIds.push(kw.id);
                        }
                    }
                }

                if (selectedIds.length === 0) {
                    alert('Nessuna keyword da applicare');
                    return;
                }

                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                selectedIds.forEach(id => formData.append('keyword_ids[]', id));

                const resp = await fetch(this.baseUrl + '/apply-negatives', {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    const err = await resp.json();
                    throw new Error(err.error || 'Errore applicazione');
                }

                const data = await resp.json();
                if (data.error) throw new Error(data.error);

                // Aggiorna UI
                this.showApplyModal = false;
                this.justApplied = true;
                this.justAppliedMessage = data.message;
                this.appliedCount += data.applied;
                this.lastAppliedDate = new Date().toLocaleDateString('it-IT');

                // Segna le keyword come applicate
                const now = new Date().toISOString();
                for (const cat of this.categories) {
                    for (const kw of cat.keywords) {
                        if (kw.is_selected && selectedIds.includes(kw.id)) {
                            kw.applied_at = now;
                        }
                    }
                }

            } catch (e) {
                alert('Errore: ' + e.message);
            } finally {
                this.applying = false;
            }
        },
    };
}
</script>
