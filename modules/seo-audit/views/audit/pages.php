<?php
// Status code colors
$statusColors = [
    '2xx' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    '3xx' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    '4xx' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    '5xx' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];

// Page status colors
$pageStatusColors = [
    'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'crawled' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];

function getStatusColorClass($code) {
    global $statusColors;
    if ($code >= 200 && $code < 300) return $statusColors['2xx'];
    if ($code >= 300 && $code < 400) return $statusColors['3xx'];
    if ($code >= 400 && $code < 500) return $statusColors['4xx'];
    if ($code >= 500) return $statusColors['5xx'];
    return 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';
}
?>

<div x-data="{...pagesManager(), ...crawlManager()}" class="space-y-6">
    <!-- Breadcrumb + Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/seo-audit') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">SEO Audit</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Gestione Pagine</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestione Pagine</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    <?= $pageStats['total'] ?? 0 ?> pagine totali
                    <?php if (($pageStats['pending'] ?? 0) > 0): ?>
                    <span class="text-amber-600 dark:text-amber-400">(<?= $pageStats['pending'] ?> da analizzare)</span>
                    <?php endif; ?>
                </p>
            </div>
            <!-- Bulk Actions -->
            <div class="flex items-center gap-2">
                <?php if (($pageStats['pending'] ?? 0) > 0): ?>
                <!-- Pulsante Avvia Analisi (non in crawl) -->
                <button x-show="!crawlRunning && !crawlComplete" @click="startCrawl()" :disabled="crawlStarting"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-medium rounded-lg hover:from-amber-600 hover:to-orange-600 transition shadow disabled:opacity-50">
                    <svg x-show="!crawlStarting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="crawlStarting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="crawlStarting ? 'Avvio...' : 'Analizza <?= $pageStats['pending'] ?> URL'"></span>
                </button>
                <!-- Progress durante crawl -->
                <div x-show="crawlRunning" class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="crawledCount + '/' + totalCount + ' analizzate'"></span>
                    </div>
                    <div class="w-32 h-2 bg-amber-200 dark:bg-amber-700 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 transition-all duration-300" :style="'width: ' + crawlPercent + '%'"></div>
                    </div>
                    <button @click="stopCrawl()" :disabled="crawlStopping"
                            class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 disabled:opacity-50">
                        <span x-text="crawlStopping ? '...' : 'STOP'"></span>
                    </button>
                </div>
                <!-- Completato -->
                <div x-show="crawlComplete" class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Completato!</span>
                </div>
                <?php endif; ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/import') ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Importa URL
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $pageStats['total'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-800 p-4 <?= ($pageStats['pending'] ?? 0) > 0 ? 'ring-2 ring-amber-400' : '' ?>">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $pageStats['pending'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">In Attesa</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-emerald-600"><?= $pageStats['crawled'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Analizzate</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-red-600"><?= $pageStats['error'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Errori</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-emerald-600"><?= $pageStats['status_2xx'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Status 2xx</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $pageStats['indexable'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Indicizzabili</p>
        </div>
    </div>

    <!-- Filters & Bulk Actions Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" action="<?= url('/seo-audit/project/' . $project['id'] . '/pages') ?>" class="flex flex-wrap items-end gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cerca URL</label>
                <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cerca per URL..." class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
            </div>

            <!-- Page Status Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Stato</label>
                <select name="status" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Tutti</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>In Attesa</option>
                    <option value="crawled" <?= ($filters['status'] ?? '') === 'crawled' ? 'selected' : '' ?>>Analizzate</option>
                    <option value="error" <?= ($filters['status'] ?? '') === 'error' ? 'selected' : '' ?>>Errori</option>
                </select>
            </div>

            <!-- Status Code Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status HTTP</label>
                <select name="status_code" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Tutti</option>
                    <option value="2xx" <?= ($filters['status_code'] ?? '') === '2xx' ? 'selected' : '' ?>>2xx</option>
                    <option value="3xx" <?= ($filters['status_code'] ?? '') === '3xx' ? 'selected' : '' ?>>3xx</option>
                    <option value="4xx" <?= ($filters['status_code'] ?? '') === '4xx' ? 'selected' : '' ?>>4xx</option>
                    <option value="5xx" <?= ($filters['status_code'] ?? '') === '5xx' ? 'selected' : '' ?>>5xx</option>
                </select>
            </div>

            <!-- Has Issues Filter -->
            <div class="flex items-center gap-2">
                <input type="checkbox" name="has_issues" id="has_issues" value="1" <?= !empty($filters['has_issues']) ? 'checked' : '' ?> class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                <label for="has_issues" class="text-sm text-slate-600 dark:text-slate-400">Con problemi</label>
            </div>

            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                Filtra
            </button>

            <?php if (!empty($filters)): ?>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/pages') ?>" class="text-sm text-slate-500 hover:text-slate-700">
                Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bulk Actions (shown when items selected) -->
    <div x-show="selectedIds.length > 0" x-cloak class="bg-primary-50 dark:bg-primary-900/20 rounded-xl border border-primary-200 dark:border-primary-800 p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                <span x-text="selectedIds.length"></span> pagine selezionate
            </span>
            <div class="flex items-center gap-2">
                <button @click="deleteSelected()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Elimina selezionate
                </button>
                <button @click="selectedIds = []" class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-800">
                    Deseleziona
                </button>
            </div>
        </div>
    </div>

    <!-- Pages Table -->
    <?php if (empty($pages)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessuna pagina trovata</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            <?php if (!empty($filters)): ?>
            Nessun risultato con i filtri selezionati.
            <?php else: ?>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/import') ?>" class="text-primary-600 hover:text-primary-700">Importa URL</a> per iniziare.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="px-4 py-3 text-left w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">HTTP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">Issues</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Azione</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($pages as $page): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-4">
                            <input type="checkbox" value="<?= $page['id'] ?>" x-model="selectedIds" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($page['status'] === 'crawled'): ?>
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $page['id']) ?>" class="text-sm text-slate-900 dark:text-white hover:text-primary-600 max-w-xs truncate block" title="<?= e($page['url']) ?>">
                                <?= e(strlen($page['url']) > 60 ? '...' . substr($page['url'], -57) : $page['url']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate block" title="<?= e($page['url']) ?>">
                                <?= e(strlen($page['url']) > 60 ? '...' . substr($page['url'], -57) : $page['url']) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $pageStatusColors[$page['status'] ?? 'pending'] ?>">
                                <?php
                                $statusLabels = ['pending' => 'In Attesa', 'crawled' => 'Analizzata', 'error' => 'Errore'];
                                echo $statusLabels[$page['status'] ?? 'pending'];
                                ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <?php if ($page['status_code']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= getStatusColorClass($page['status_code']) ?>">
                                <?= $page['status_code'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate" title="<?= e($page['title'] ?? '') ?>">
                                <?= e($page['title'] ? (strlen($page['title']) > 40 ? substr($page['title'], 0, 40) . '...' : $page['title']) : '-') ?>
                            </p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <?php if (($page['issues_count'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                <?= $page['issues_count'] ?>
                            </span>
                            <?php elseif ($page['status'] === 'crawled'): ?>
                            <span class="text-emerald-600 dark:text-emerald-400">
                                <svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($page['status'] === 'crawled'): ?>
                                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $page['id']) ?>" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 text-sm font-medium">
                                    Dettagli
                                </a>
                                <?php endif; ?>
                                <button @click="deleteSingle(<?= $page['id'] ?>)" class="text-red-500 hover:text-red-700" title="Elimina">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Mostrando <?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['total'] ?>
            </p>
            <div class="flex items-center gap-2">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/pages?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1]))) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                    Precedente
                </a>
                <?php endif; ?>
                <span class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400">
                    Pagina <?= $pagination['current_page'] ?> di <?= $pagination['last_page'] ?>
                </span>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/pages?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1]))) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                    Successiva
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Info Box: Scraping vs AI -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
        <div class="flex gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm">
                <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Come funziona l'analisi?</p>
                <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                    <li><strong>Scraping + Issue Detection</strong> = Automatico, nessun costo di crediti AI</li>
                    <li><strong>Analisi AI</strong> = Su richiesta, disponibile nella dashboard dopo l'analisi</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';

function crawlManager() {
    return {
        crawlStarting: false,
        crawlRunning: false,
        crawlStopping: false,
        crawlComplete: false,
        crawledCount: 0,
        totalCount: <?= $pageStats['pending'] ?? 0 ?>,
        crawlPercent: 0,
        error: null,

        async startCrawl() {
            this.crawlStarting = true;
            this.error = null;

            try {
                const response = await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/crawl/start') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '_token=' + csrfToken
                });

                const data = await response.json();

                if (data.error) {
                    alert(data.message || 'Errore avvio crawl');
                    this.crawlStarting = false;
                    return;
                }

                this.totalCount = data.urls_found || this.totalCount;
                this.crawlStarting = false;
                this.crawlRunning = true;

                // Avvia polling batch
                this.pollBatch();

            } catch (e) {
                alert('Errore di connessione');
                this.crawlStarting = false;
            }
        },

        async pollBatch() {
            if (!this.crawlRunning) return;

            try {
                const response = await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/crawl/batch') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '_token=' + csrfToken
                });

                const data = await response.json();

                if (data.error) {
                    alert(data.message);
                    this.crawlRunning = false;
                    return;
                }

                // Aggiorna progress
                if (data.progress) {
                    this.crawledCount = data.progress.pages_crawled || 0;
                    this.totalCount = data.progress.pages_found || this.totalCount;
                    this.crawlPercent = this.totalCount > 0 ? Math.round((this.crawledCount / this.totalCount) * 100) : 0;
                }

                if (data.stopped) {
                    this.crawlRunning = false;
                    this.crawlStopping = false;
                    setTimeout(() => window.location.reload(), 1000);
                    return;
                }

                if (data.complete) {
                    this.crawlRunning = false;
                    this.crawlComplete = true;
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                // Continua polling
                setTimeout(() => this.pollBatch(), 500);

            } catch (e) {
                alert('Errore durante analisi');
                this.crawlRunning = false;
            }
        },

        async stopCrawl() {
            this.crawlStopping = true;
            try {
                await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/crawl/stop') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '_token=' + csrfToken
                });
            } catch (e) {
                console.error('Stop error:', e);
            }
        }
    };
}

function pagesManager() {
    return {
        selectedIds: [],
        allSelected: false,
        pageIds: <?= json_encode(array_column($pages, 'id')) ?>,

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...this.pageIds];
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        async deleteSelected() {
            if (!confirm(`Eliminare ${this.selectedIds.length} pagine selezionate?`)) return;

            try {
                const response = await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/pages/delete') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        ids: this.selectedIds
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Errore durante l\'eliminazione');
                }
            } catch (e) {
                alert('Errore di connessione');
            }
        },

        async deleteSingle(id) {
            if (!confirm('Eliminare questa pagina?')) return;

            try {
                const response = await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/pages/delete') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        ids: [id]
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Errore durante l\'eliminazione');
                }
            } catch (e) {
                alert('Errore di connessione');
            }
        }
    };
}
</script>
