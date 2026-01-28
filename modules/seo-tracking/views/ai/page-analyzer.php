<?php $currentPage = 'page-analyzer'; ?>
<div class="space-y-6" x-data="pageAnalyzerApp()">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">SEO Page Analyzer</h3>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Analizza le tue pagine e confrontale con i <strong>top 3 competitor</strong> su Google.
                    L'AI ti suggerisce come ottimizzare contenuti, titoli e struttura per migliorare il posizionamento.
                </p>
                <ul class="mt-3 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Scraping contenuto della tua pagina
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Analisi top 3 competitor dalla SERP
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Suggerimenti AI personalizzati
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= count($keywords) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Keyword con URL</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $creditCost ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Crediti per analisi</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-violet-100 dark:bg-violet-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $userCredits ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">I tuoi crediti</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    <?php if (!$isConfigured): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">
                API AI o SERP non configurate. Verifica le impostazioni nelle preferenze admin.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Keywords Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword da Analizzare</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Seleziona una keyword per analizzare la pagina e confrontarla con i competitor</p>
        </div>

        <?php if (empty($keywords)): ?>
        <div class="p-8 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna keyword con URL</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Non ci sono keyword con URL associato. Esegui un Rank Check per le keyword o imposta manualmente il target_url.
            </p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Posizione</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Impressioni</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($keywords as $kw): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-900 dark:text-white"><?= e($kw['keyword']) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= e($kw['url']) ?>" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate block max-w-xs" title="<?= e($kw['url']) ?>">
                                <?= e(strlen($kw['url']) > 50 ? substr($kw['url'], 0, 50) . '...' : $kw['url']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($kw['position']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $kw['position'] <= 3 ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : ($kw['position'] <= 10 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300') ?>">
                                #<?= $kw['position'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-slate-600 dark:text-slate-300">
                            <?= number_format($kw['impressions'] ?? 0) ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($isConfigured && $userCredits >= $creditCost): ?>
                            <button
                                @click="analyzeKeyword('<?= e(addslashes($kw['keyword'])) ?>', '<?= e(addslashes($kw['url'])) ?>', <?= (int)$kw['position'] ?>)"
                                :disabled="analyzing"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Analizza
                            </button>
                            <?php else: ?>
                            <span class="text-sm text-slate-400">Non disponibile</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Analyses -->
    <?php if (!empty($recentAnalyses)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Analisi Recenti</h3>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($recentAnalyses as $analysis): ?>
            <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer" @click="loadAnalysis(<?= $analysis['id'] ?>)">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white"><?= e($analysis['keyword']) ?></p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate max-w-md"><?= e($analysis['target_url']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y H:i', strtotime($analysis['created_at'])) ?>
                        </p>
                        <p class="text-xs text-slate-400">
                            <?= $analysis['competitors_analyzed'] ?? 0 ?> competitor analizzati
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Analysis Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-500 dark:bg-slate-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" @click="showModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white" x-text="modalTitle">Analisi Pagina</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400" x-text="modalSubtitle"></p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <!-- Loading State -->
                    <div x-show="analyzing" class="text-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-4 text-slate-600 dark:text-slate-300">Analisi in corso...</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Scraping pagina e competitor, analisi AI</p>
                    </div>

                    <!-- Error State -->
                    <div x-show="error && !analyzing" class="text-center py-8">
                        <div class="mx-auto h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center mb-4">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-slate-900 dark:text-white">Errore</p>
                        <p class="text-sm text-red-600 dark:text-red-400 mt-1" x-text="error"></p>
                    </div>

                    <!-- Results -->
                    <div x-show="analysisData && !analyzing && !error" class="space-y-6">

                        <!-- Summary -->
                        <template x-if="analysisData?.summary">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Riepilogo</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300" x-text="analysisData.summary"></p>
                            </div>
                        </template>

                        <!-- Score -->
                        <template x-if="analysisData?.score !== undefined">
                            <div class="flex items-center gap-4">
                                <div class="text-center">
                                    <div class="text-4xl font-bold" :class="analysisData.score >= 70 ? 'text-green-600' : (analysisData.score >= 40 ? 'text-amber-600' : 'text-red-600')" x-text="analysisData.score + '/100'"></div>
                                    <p class="text-sm text-slate-500">Punteggio SEO</p>
                                </div>
                            </div>
                        </template>

                        <!-- Strengths -->
                        <template x-if="analysisData?.strengths?.length > 0">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Punti di Forza
                                </h4>
                                <ul class="space-y-2">
                                    <template x-for="strength in analysisData.strengths" :key="strength">
                                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                                            <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span x-text="strength"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <!-- Improvements -->
                        <template x-if="analysisData?.improvements?.length > 0">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Miglioramenti Suggeriti
                                </h4>
                                <ul class="space-y-3">
                                    <template x-for="(improvement, index) in analysisData.improvements" :key="index">
                                        <li class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                                            <p class="font-medium text-amber-800 dark:text-amber-200" x-text="improvement.title || improvement"></p>
                                            <p x-show="improvement.description" class="text-sm text-amber-700 dark:text-amber-300 mt-1" x-text="improvement.description"></p>
                                            <p x-show="improvement.priority" class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                                Priorita: <span class="font-medium" x-text="improvement.priority"></span>
                                            </p>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <!-- Competitor Analysis -->
                        <template x-if="analysisData?.competitor_analysis">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Analisi Competitor
                                </h4>
                                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                                    <p class="text-sm text-slate-600 dark:text-slate-300" x-text="analysisData.competitor_analysis"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Content Gaps -->
                        <template x-if="analysisData?.content_gaps?.length > 0">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Gap di Contenuto
                                </h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Argomenti trattati dai competitor ma mancanti nella tua pagina:</p>
                                <ul class="space-y-1">
                                    <template x-for="gap in analysisData.content_gaps" :key="gap">
                                        <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span x-text="gap"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <!-- Action Plan -->
                        <template x-if="analysisData?.action_plan?.length > 0">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                    Piano d'Azione
                                </h4>
                                <ol class="space-y-2">
                                    <template x-for="(action, index) in analysisData.action_plan" :key="index">
                                        <li class="flex items-start gap-3">
                                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm font-medium" x-text="index + 1"></span>
                                            <span class="text-sm text-slate-600 dark:text-slate-300" x-text="action"></span>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </template>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function pageAnalyzerApp() {
    return {
        analyzing: false,
        showModal: false,
        modalTitle: '',
        modalSubtitle: '',
        analysisData: null,
        error: null,
        projectId: <?= $project['id'] ?>,

        async analyzeKeyword(keyword, url, position) {
            this.analyzing = true;
            this.showModal = true;
            this.modalTitle = keyword;
            this.modalSubtitle = url;
            this.analysisData = null;
            this.error = null;

            try {
                const formData = new FormData();
                formData.append('keyword', keyword);
                formData.append('url', url);
                formData.append('position', position || '');
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`/seo-toolkit/seo-tracking/project/${this.projectId}/analyze-page`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Errore durante l\'analisi');
                }

                this.analysisData = data.data;

            } catch (err) {
                this.error = err.message;
            } finally {
                this.analyzing = false;
            }
        },

        async loadAnalysis(analysisId) {
            this.analyzing = true;
            this.showModal = true;
            this.modalTitle = 'Caricamento...';
            this.modalSubtitle = '';
            this.analysisData = null;
            this.error = null;

            try {
                const response = await fetch(`/seo-toolkit/seo-tracking/project/${this.projectId}/page-analysis/${analysisId}`);
                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Errore caricamento analisi');
                }

                this.modalTitle = data.analysis.keyword;
                this.modalSubtitle = data.analysis.target_url;
                this.analysisData = JSON.parse(data.analysis.analysis_json || '{}');

            } catch (err) {
                this.error = err.message;
            } finally {
                this.analyzing = false;
            }
        }
    };
}
</script>
