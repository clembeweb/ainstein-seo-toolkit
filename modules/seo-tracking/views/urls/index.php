<?php
/**
 * URLs Posizionate View
 * Visualizza URLs con ranking e keyword associate in formato accordion
 */

$currentPage = 'urls';
?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Page Title -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">URLs Posizionate</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pagine del sito con keyword posizionate in SERP</p>
        </div>
        <div>
            <a href="<?= url("/seo-tracking/project/{$project['id']}/keywords") ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Gestisci Keywords
            </a>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="get" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Cerca URL</label>
                <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Filtra per URL..."
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Da</label>
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">A</label>
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Min Keywords</label>
                <input type="number" name="min_keywords" value="<?= e($filters['min_keywords'] ?? '') ?>" min="1" placeholder="1"
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Max Posizione</label>
                <select name="max_position" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    <option value="">Tutte</option>
                    <option value="3" <?= ($filters['max_position'] ?? 0) == 3 ? 'selected' : '' ?>>Top 3</option>
                    <option value="10" <?= ($filters['max_position'] ?? 0) == 10 ? 'selected' : '' ?>>Top 10</option>
                    <option value="20" <?= ($filters['max_position'] ?? 0) == 20 ? 'selected' : '' ?>>Top 20</option>
                    <option value="50" <?= ($filters['max_position'] ?? 0) == 50 ? 'selected' : '' ?>>Top 50</option>
                    <option value="100" <?= ($filters['max_position'] ?? 0) == 100 ? 'selected' : '' ?>>Top 100</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-md hover:bg-slate-200 dark:hover:bg-slate-600 text-sm font-medium transition-colors">
                Filtra
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['min_keywords']) || !empty($filters['max_position'])): ?>
            <a href="<?= url("/seo-tracking/project/{$project['id']}/urls") ?>"
               class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-sm">
                Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">URLs Totali</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($stats['total_urls'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-sm text-blue-600 dark:text-blue-400">Keywords Totali</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= number_format($stats['total_keywords'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-800 p-4">
            <p class="text-sm text-amber-600 dark:text-amber-400">Posizione Media</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"><?= isset($stats['avg_position']) && $stats['avg_position'] > 0 ? number_format($stats['avg_position'], 1) : '-' ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-800 p-4">
            <p class="text-sm text-emerald-600 dark:text-emerald-400">Miglior Posizione</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?= isset($stats['best_position']) && $stats['best_position'] > 0 ? number_format($stats['best_position'], 1) : '-' ?></p>
        </div>
    </div>

    <!-- URLs Accordion -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <?php if (empty($urls)): ?>
        <div class="px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <p class="mt-4 text-slate-500 dark:text-slate-400">Nessuna URL trovata</p>
            <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">
                <?php if (!empty($filters['search']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['min_keywords']) || !empty($filters['max_position'])): ?>
                Prova a modificare i filtri di ricerca
                <?php else: ?>
                Aggiungi keyword e aggiorna le posizioni per vedere le URLs posizionate
                <?php endif; ?>
            </p>
            <a href="<?= url("/seo-tracking/project/{$project['id']}/keywords") ?>"
               class="mt-4 inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                Vai alle Keywords
            </a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($urls as $index => $urlData): ?>
            <div x-data="{ open: false }" class="group">
                <!-- URL Header (Clickable) -->
                <button @click="open = !open"
                        class="w-full px-4 py-4 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <!-- Chevron Icon -->
                        <div class="flex-shrink-0 text-slate-400 dark:text-slate-500">
                            <svg x-show="!open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <svg x-show="open" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        <!-- URL Path -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate" title="<?= e($urlData['url']) ?>">
                                <?= e($urlData['url_path'] ?? $urlData['url']) ?>
                            </p>
                            <?php if (!empty($urlData['url']) && $urlData['url'] !== ($urlData['url_path'] ?? '')): ?>
                            <p class="text-xs text-slate-400 dark:text-slate-500 truncate"><?= e($urlData['url']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                        <!-- Keyword Count Badge -->
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                            <?= $urlData['keyword_count'] ?? 0 ?> keyword<?= ($urlData['keyword_count'] ?? 0) != 1 ? 's' : '' ?>
                        </span>

                        <!-- Avg Position -->
                        <?php
                        $avgPos = $urlData['avg_position'] ?? 0;
                        $avgPosClass = $avgPos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                      ($avgPos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                      ($avgPos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                      'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                        ?>
                        <div class="text-right">
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">Media</span>
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $avgPosClass ?>">
                                <?= $avgPos > 0 ? number_format($avgPos, 1) : '-' ?>
                            </span>
                        </div>

                        <!-- Best Position -->
                        <?php
                        $bestPos = $urlData['best_position'] ?? 0;
                        $bestPosClass = $bestPos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                       ($bestPos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                       ($bestPos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                       'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                        ?>
                        <div class="text-right">
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">Migliore</span>
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $bestPosClass ?>">
                                <?= $bestPos > 0 ? number_format($bestPos, 0) : '-' ?>
                            </span>
                        </div>

                        <!-- Last Check Date -->
                        <div class="text-right w-24">
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">Ultimo check</span>
                            <span class="text-xs text-slate-600 dark:text-slate-300">
                                <?= !empty($urlData['last_check']) ? date('d/m/Y', strtotime($urlData['last_check'])) : '-' ?>
                            </span>
                        </div>
                    </div>
                </button>

                <!-- Expanded Keywords Table -->
                <div x-show="open" x-cloak x-collapse class="border-t border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-800/50">
                    <div class="px-4 py-3">
                        <?php if (!empty($urlData['keywords'])): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Location</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Device</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Data</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <?php foreach ($urlData['keywords'] as $kw): ?>
                                    <tr class="hover:bg-white dark:hover:bg-slate-700/50">
                                        <td class="px-3 py-2">
                                            <span class="text-sm text-slate-900 dark:text-white"><?= e($kw['keyword']) ?></span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <?php
                                            $kwPos = $kw['serp_position'] ?? 0;
                                            $kwPosClass = $kwPos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                                         ($kwPos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                                         ($kwPos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                                         'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                                            ?>
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $kwPosClass ?>">
                                                <?= $kwPos > 0 ? number_format($kwPos, 0) : '-' ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-4 rounded-sm bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase">
                                                <?= e($kw['location'] ?? 'IT') ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                                <?= ucfirst($kw['device'] ?? 'desktop') ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                <?= !empty($kw['checked_at']) ? date('d/m/Y H:i', strtotime($kw['checked_at'])) : '-' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Nessuna keyword associata a questa URL</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
            'baseUrl' => url("/seo-tracking/project/{$project['id']}/urls"),
            'filters' => array_filter([
                'search' => $filters['search'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'min_keywords' => $filters['min_keywords'] ?? '',
                'max_position' => $filters['max_position'] ?? '',
            ], fn($v) => $v !== ''),
        ]) ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
