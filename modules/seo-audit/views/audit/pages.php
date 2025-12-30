<?php
// Status code colors
$statusColors = [
    '2xx' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    '3xx' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    '4xx' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    '5xx' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
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

<div class="space-y-6">
    <!-- Breadcrumb + Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/seo-audit') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">SEO Audit</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Pagine</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pagine Crawlate</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= $pagination['total'] ?> pagine analizzate</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $pageStats['total'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-emerald-600"><?= $pageStats['status_2xx'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Status 2xx</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-blue-600"><?= $pageStats['status_3xx'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Status 3xx</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-red-600"><?= ($pageStats['status_4xx'] ?? 0) + ($pageStats['status_5xx'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Errori 4xx/5xx</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $pageStats['indexable'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Indicizzabili</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" action="<?= url('/seo-audit/project/' . $project['id'] . '/pages') ?>" class="flex flex-wrap items-end gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cerca URL</label>
                <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cerca per URL..." class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
            </div>

            <!-- Status Code Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status Code</label>
                <select name="status_code" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Tutti</option>
                    <option value="2xx" <?= ($filters['status_code'] ?? '') === '2xx' ? 'selected' : '' ?>>2xx (Successo)</option>
                    <option value="3xx" <?= ($filters['status_code'] ?? '') === '3xx' ? 'selected' : '' ?>>3xx (Redirect)</option>
                    <option value="4xx" <?= ($filters['status_code'] ?? '') === '4xx' ? 'selected' : '' ?>>4xx (Client Error)</option>
                    <option value="5xx" <?= ($filters['status_code'] ?? '') === '5xx' ? 'selected' : '' ?>>5xx (Server Error)</option>
                </select>
            </div>

            <!-- Indexable Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Indicizzabile</label>
                <select name="is_indexable" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Tutti</option>
                    <option value="1" <?= ($filters['is_indexable'] ?? '') === '1' ? 'selected' : '' ?>>Indicizzabili</option>
                    <option value="0" <?= ($filters['is_indexable'] ?? '') === '0' ? 'selected' : '' ?>>Non indicizzabili</option>
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
                Reset filtri
            </a>
            <?php endif; ?>
        </form>
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
            Avvia un crawl per analizzare le pagine del sito.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Issues</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tempo</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Index</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azione</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($pages as $page): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $page['id']) ?>" class="text-sm text-slate-900 dark:text-white hover:text-primary-600 max-w-xs truncate block" title="<?= e($page['url']) ?>">
                                <?= e(strlen($page['url']) > 60 ? '...' . substr($page['url'], -57) : $page['url']) ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= getStatusColorClass($page['status_code']) ?>">
                                <?= $page['status_code'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate" title="<?= e($page['title'] ?? '') ?>">
                                <?= e($page['title'] ? (strlen($page['title']) > 50 ? substr($page['title'], 0, 50) . '...' : $page['title']) : '-') ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if (($page['issues_count'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                <?= $page['issues_count'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-emerald-600 dark:text-emerald-400">
                                <svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                <?= $page['load_time_ms'] ?? '-' ?><span class="text-xs">ms</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($page['is_indexable']): ?>
                            <span class="text-emerald-600 dark:text-emerald-400" title="Indicizzabile">
                                <svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <?php else: ?>
                            <span class="text-red-500 dark:text-red-400" title="<?= e($page['indexability_reason'] ?? 'Non indicizzabile') ?>">
                                <svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $page['id']) ?>" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 text-sm font-medium">
                                Dettagli
                            </a>
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
</div>
