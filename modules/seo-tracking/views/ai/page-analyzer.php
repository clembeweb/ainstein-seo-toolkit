<?php $currentPage = 'page-analyzer'; ?>
<div class="space-y-6" x-data="pageAnalyzerApp()">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
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
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $pagination['total_count'] ?? count($keywords) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Keyword con URL</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
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

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
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
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
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
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword da Analizzare</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Seleziona una keyword per analizzare la pagina e confrontarla con i competitor</p>
                </div>
                <?php if (!empty($pagination)): ?>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    <?php
                    $start = ($pagination['current_page'] - 1) * $pagination['per_page'] + 1;
                    $end = min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count']);
                    ?>
                    <span class="font-medium"><?= $start ?>-<?= $end ?></span> di <span class="font-medium"><?= number_format($pagination['total_count']) ?></span> keyword
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtri -->
        <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <form method="get" class="flex flex-wrap gap-4 items-end">
                <!-- Ricerca testuale -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Cerca Keyword</label>
                    <input type="text" name="search"
                           value="<?= e($filters['search'] ?? '') ?>"
                           placeholder="Filtra per keyword..."
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Filtro posizione -->
                <div class="w-36">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Max Posizione</label>
                    <select name="max_position" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tutte</option>
                        <option value="3" <?= ($filters['max_position'] ?? 0) == 3 ? 'selected' : '' ?>>Top 3</option>
                        <option value="10" <?= ($filters['max_position'] ?? 0) == 10 ? 'selected' : '' ?>>Top 10</option>
                        <option value="20" <?= ($filters['max_position'] ?? 0) == 20 ? 'selected' : '' ?>>Top 20</option>
                        <option value="50" <?= ($filters['max_position'] ?? 0) == 50 ? 'selected' : '' ?>>Top 50</option>
                    </select>
                </div>

                <!-- Filtro impressioni -->
                <div class="w-40">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Min Impressioni</label>
                    <select name="min_impressions" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tutte</option>
                        <option value="10" <?= ($filters['min_impressions'] ?? 0) == 10 ? 'selected' : '' ?>>10+</option>
                        <option value="100" <?= ($filters['min_impressions'] ?? 0) == 100 ? 'selected' : '' ?>>100+</option>
                        <option value="500" <?= ($filters['min_impressions'] ?? 0) == 500 ? 'selected' : '' ?>>500+</option>
                        <option value="1000" <?= ($filters['min_impressions'] ?? 0) == 1000 ? 'selected' : '' ?>>1.000+</option>
                    </select>
                </div>

                <!-- Bottoni -->
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Filtra
                    </button>

                    <?php
                    $hasFilters = !empty($filters['search']) || !empty($filters['max_position']) || !empty($filters['min_impressions']);
                    if ($hasFilters):
                    ?>
                    <a href="<?= url("/seo-tracking/project/{$project['id']}/page-analyzer") ?>"
                       class="inline-flex items-center px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-md hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
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
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
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
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
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

        <!-- Paginazione -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1):
            $paginationPerPage = $pagination['per_page'] ?? 20;
            $paginationFrom = ($pagination['current_page'] - 1) * $paginationPerPage + 1;
            $paginationTo = min($pagination['current_page'] * $paginationPerPage, $pagination['total_count']);
        ?>
        <?= \Core\View::partial('components/table-pagination', [
            'pagination' => [
                'current_page' => $pagination['current_page'],
                'last_page' => $pagination['total_pages'],
                'total' => $pagination['total_count'],
                'per_page' => $paginationPerPage,
                'from' => $paginationFrom,
                'to' => $paginationTo,
            ],
            'baseUrl' => url("/seo-tracking/project/{$project['id']}/page-analyzer"),
            'filters' => array_filter([
                'search' => $filters['search'] ?? '',
                'max_position' => $filters['max_position'] ?? '',
                'min_impressions' => $filters['min_impressions'] ?? '',
            ], fn($v) => $v !== '' && $v !== 0),
        ]) ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Analyses -->
    <?php if (!empty($recentAnalyses)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
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

                        <!-- Summary & Score -->
                        <template x-if="analysisData?.summary">
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    <!-- Score -->
                                    <div class="flex-shrink-0 text-center">
                                        <div class="text-4xl font-bold" :class="analysisData.summary.score >= 70 ? 'text-green-600' : (analysisData.summary.score >= 40 ? 'text-amber-600' : 'text-red-600')" x-text="analysisData.summary.score + '/100'"></div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Punteggio SEO</p>
                                        <template x-if="analysisData.summary.estimated_position_gain">
                                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                                +<span x-text="analysisData.summary.estimated_position_gain"></span> posizioni potenziali
                                            </p>
                                        </template>
                                    </div>
                                    <!-- Main Issues -->
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Problemi Principali</h4>
                                        <ul class="space-y-1">
                                            <template x-for="issue in analysisData.summary.main_issues" :key="issue">
                                                <li class="flex items-start gap-2 text-sm text-blue-700 dark:text-blue-300">
                                                    <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                    <span x-text="issue"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- On-Page SEO -->
                        <template x-if="analysisData?.on_page_seo">
                            <div class="bg-white dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
                                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-600">
                                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                        </svg>
                                        Ottimizzazione On-Page
                                    </h4>
                                </div>
                                <div class="divide-y divide-slate-200 dark:divide-slate-600">
                                    <!-- Title -->
                                    <template x-if="analysisData.on_page_seo.title">
                                        <div class="p-4">
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-1">Title Tag</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">Attuale: <span class="font-medium" x-text="analysisData.on_page_seo.title.current || '-'"></span></p>
                                            <template x-if="analysisData.on_page_seo.title.suggestion">
                                                <p class="text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded px-2 py-1">
                                                    Suggerito: <span class="font-medium" x-text="analysisData.on_page_seo.title.suggestion"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </template>
                                    <!-- Meta Description -->
                                    <template x-if="analysisData.on_page_seo.meta_description">
                                        <div class="p-4">
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-1">Meta Description</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">Attuale: <span class="font-medium" x-text="analysisData.on_page_seo.meta_description.current || '-'"></span></p>
                                            <template x-if="analysisData.on_page_seo.meta_description.suggestion">
                                                <p class="text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded px-2 py-1">
                                                    Suggerito: <span class="font-medium" x-text="analysisData.on_page_seo.meta_description.suggestion"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </template>
                                    <!-- H1 -->
                                    <template x-if="analysisData.on_page_seo.h1">
                                        <div class="p-4">
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-1">H1</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">Attuale: <span class="font-medium" x-text="analysisData.on_page_seo.h1.current || '-'"></span></p>
                                            <template x-if="analysisData.on_page_seo.h1.suggestion">
                                                <p class="text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded px-2 py-1">
                                                    Suggerito: <span class="font-medium" x-text="analysisData.on_page_seo.h1.suggestion"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </template>
                                    <!-- Heading Structure -->
                                    <template x-if="analysisData.on_page_seo.heading_structure?.suggestions?.length > 0">
                                        <div class="p-4">
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-2">Struttura Heading Suggerita</p>
                                            <ul class="space-y-1">
                                                <template x-for="h in analysisData.on_page_seo.heading_structure.suggestions" :key="h">
                                                    <li class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                        </svg>
                                                        <span x-text="h"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Gap Analysis -->
                        <template x-if="analysisData?.gap_analysis">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Gap Analysis vs Competitor
                                </h4>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <!-- Missing Topics -->
                                    <template x-if="analysisData.gap_analysis.missing_topics?.length > 0">
                                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                                            <p class="text-xs font-medium text-red-800 dark:text-red-300 uppercase mb-2">Topic Mancanti</p>
                                            <ul class="space-y-1">
                                                <template x-for="topic in analysisData.gap_analysis.missing_topics" :key="topic">
                                                    <li class="text-sm text-red-700 dark:text-red-300 flex items-start gap-2">
                                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                        </svg>
                                                        <span x-text="topic"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                    <!-- Missing Sections -->
                                    <template x-if="analysisData.gap_analysis.missing_sections?.length > 0">
                                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                            <p class="text-xs font-medium text-amber-800 dark:text-amber-300 uppercase mb-2">Sezioni Mancanti</p>
                                            <ul class="space-y-1">
                                                <template x-for="section in analysisData.gap_analysis.missing_sections" :key="section">
                                                    <li class="text-sm text-amber-700 dark:text-amber-300 flex items-start gap-2">
                                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                        </svg>
                                                        <span x-text="section"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                                <!-- Content Depth -->
                                <template x-if="analysisData.gap_analysis.content_depth">
                                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3" x-text="analysisData.gap_analysis.content_depth"></p>
                                </template>
                            </div>
                        </template>

                        <!-- Content Recommendations -->
                        <template x-if="analysisData?.content">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Contenuto
                                </h4>
                                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 mb-3">
                                    <p class="text-sm text-emerald-700 dark:text-emerald-300" x-text="analysisData.content.word_count_analysis"></p>
                                    <template x-if="analysisData.content.recommended_word_count">
                                        <p class="text-sm text-emerald-800 dark:text-emerald-200 font-medium mt-2">
                                            Parole consigliate: <span x-text="analysisData.content.recommended_word_count"></span>
                                        </p>
                                    </template>
                                </div>
                                <!-- Sections to Add -->
                                <template x-if="analysisData.content.sections_to_add?.length > 0">
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Sezioni da Aggiungere</p>
                                        <template x-for="(section, index) in analysisData.content.sections_to_add" :key="index">
                                            <div class="bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-lg p-3">
                                                <div class="flex items-start justify-between">
                                                    <p class="font-medium text-slate-900 dark:text-white" x-text="section.title"></p>
                                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                                          :class="section.priority === 'alta' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : (section.priority === 'media' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-600 dark:text-slate-300')"
                                                          x-text="section.priority"></span>
                                                </div>
                                                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1" x-text="section.description"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Quick Wins -->
                        <template x-if="analysisData?.quick_wins?.length > 0">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Quick Wins
                                </h4>
                                <div class="space-y-3">
                                    <template x-for="(win, index) in analysisData.quick_wins" :key="index">
                                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex-1">
                                                    <p class="font-medium text-yellow-800 dark:text-yellow-200" x-text="win.action"></p>
                                                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1" x-text="win.details"></p>
                                                </div>
                                                <div class="flex gap-2 flex-shrink-0">
                                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                                          :class="win.impact === 'alto' ? 'bg-green-100 text-green-700' : (win.impact === 'medio' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700')"
                                                          x-text="'Impatto: ' + win.impact"></span>
                                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                                          :class="win.effort === 'facile' ? 'bg-green-100 text-green-700' : (win.effort === 'medio' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                          x-text="'Sforzo: ' + win.effort"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Competitor Insights -->
                        <template x-if="analysisData?.competitor_insights?.length > 0">
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Insight dai Competitor
                                </h4>
                                <ul class="space-y-2">
                                    <template x-for="insight in analysisData.competitor_insights" :key="insight">
                                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3">
                                            <svg class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-text="insight"></span>
                                        </li>
                                    </template>
                                </ul>
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
