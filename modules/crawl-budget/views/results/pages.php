<?php
/**
 * Crawl Budget Optimizer — Results: Tutte le Pagine
 */
$projectId = $project['id'];

$statusBadges = [
    'crawled' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
    'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
    'error' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
];

// Funzione per header colonne ordinabili
$sortUrl = function (string $col) use ($baseUrl, $filters, $sort, $dir) {
    $newDir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter($filters, fn($v) => $v !== '' && $v !== null);
    $params['sort'] = $col;
    $params['dir'] = $newDir;
    return $baseUrl . '?' . http_build_query($params);
};
$sortIcon = function (string $col) use ($sort, $dir) {
    if ($sort !== $col) return '';
    $arrow = $dir === 'ASC' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7';
    return '<svg class="w-3 h-3 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $arrow . '"/></svg>';
};
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-4">
    <a href="<?= url('/crawl-budget/projects/' . $projectId) ?>" class="hover:text-orange-600 dark:hover:text-orange-400"><?= e($project['name']) ?></a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Risultati</span>
</div>

<!-- Tabs Navigation -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex -mb-px overflow-x-auto">
            <?php
            $tabs = [
                'results' => ['label' => 'Overview', 'url' => '/crawl-budget/projects/' . $projectId . '/results'],
                'redirects' => ['label' => 'Redirect', 'url' => '/crawl-budget/projects/' . $projectId . '/results/redirects'],
                'waste' => ['label' => 'Pagine Spreco', 'url' => '/crawl-budget/projects/' . $projectId . '/results/waste'],
                'indexability' => ['label' => 'Indexability', 'url' => '/crawl-budget/projects/' . $projectId . '/results/indexability'],
                'pages' => ['label' => 'Tutte le Pagine', 'url' => '/crawl-budget/projects/' . $projectId . '/results/pages'],
            ];
            foreach ($tabs as $tabKey => $tab):
                $isActive = ($currentTab ?? '') === $tabKey;
            ?>
            <a href="<?= url($tab['url']) ?>"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 <?= $isActive ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300' ?>">
                <?= $tab['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
    <form method="GET" action="<?= url('/crawl-budget/projects/' . $projectId . '/results/pages') ?>" class="flex flex-wrap items-end gap-3">
        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cerca URL</label>
            <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>"
                   placeholder="Cerca URL..."
                   class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>

        <!-- Status Code -->
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status</label>
            <select name="status_code"
                    class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Tutti</option>
                <option value="2xx" <?= ($filters['status_code'] ?? '') === '2xx' ? 'selected' : '' ?>>2xx</option>
                <option value="3xx" <?= ($filters['status_code'] ?? '') === '3xx' ? 'selected' : '' ?>>3xx</option>
                <option value="4xx" <?= ($filters['status_code'] ?? '') === '4xx' ? 'selected' : '' ?>>4xx</option>
                <option value="5xx" <?= ($filters['status_code'] ?? '') === '5xx' ? 'selected' : '' ?>>5xx</option>
            </select>
        </div>

        <!-- Indexable -->
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Indexabile</label>
            <select name="is_indexable"
                    class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Tutti</option>
                <option value="1" <?= isset($filters['is_indexable']) && $filters['is_indexable'] == 1 ? 'selected' : '' ?>>Si</option>
                <option value="0" <?= isset($filters['is_indexable']) && $filters['is_indexable'] == 0 && ($filters['is_indexable'] ?? '') !== '' ? 'selected' : '' ?>>No</option>
            </select>
        </div>

        <!-- Parametri -->
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Parametri</label>
            <select name="has_parameters"
                    class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Tutti</option>
                <option value="1" <?= isset($filters['has_parameters']) && $filters['has_parameters'] == 1 ? 'selected' : '' ?>>Con parametri</option>
                <option value="0" <?= isset($filters['has_parameters']) && $filters['has_parameters'] == 0 && ($filters['has_parameters'] ?? '') !== '' ? 'selected' : '' ?>>Senza parametri</option>
            </select>
        </div>

        <!-- Buttons -->
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-colors">
                Filtra
            </button>
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/pages') ?>"
               class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-300 dark:border-slate-600 rounded-lg transition-colors">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Pages Table -->
<?php if (empty($pages)): ?>
    <?= \Core\View::partial('components/table-empty-state', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
        'heading' => 'Nessuna pagina trovata',
        'message' => 'Nessuna pagina corrisponde ai filtri selezionati.',
    ]) ?>
<?php else: ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="dark:bg-slate-700/50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <a href="<?= $sortUrl('url') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">URL <?= $sortIcon('url') ?></a>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <a href="<?= $sortUrl('http_status') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">Status <?= $sortIcon('http_status') ?></a>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <a href="<?= $sortUrl('word_count') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">Parole <?= $sortIcon('word_count') ?></a>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <a href="<?= $sortUrl('redirect_hops') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">Redirect <?= $sortIcon('redirect_hops') ?></a>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <a href="<?= $sortUrl('depth') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">Depth <?= $sortIcon('depth') ?></a>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <a href="<?= $sortUrl('internal_links_in') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">Links In <?= $sortIcon('internal_links_in') ?></a>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Indexabile</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($pages as $p):
                    $httpStatus = (int) ($p['http_status'] ?? 0);
                    $statusClass = 'text-slate-500';
                    if ($httpStatus >= 200 && $httpStatus < 300) $statusClass = 'text-emerald-600 dark:text-emerald-400';
                    elseif ($httpStatus >= 300 && $httpStatus < 400) $statusClass = 'text-blue-600 dark:text-blue-400';
                    elseif ($httpStatus >= 400 && $httpStatus < 500) $statusClass = 'text-amber-600 dark:text-amber-400';
                    elseif ($httpStatus >= 500) $statusClass = 'text-red-600 dark:text-red-400';
                ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200 max-w-md">
                        <span class="block truncate" title="<?= e($p['url']) ?>"><?= e($p['url']) ?></span>
                        <?php if (!empty($p['title'])): ?>
                        <span class="block truncate text-xs text-slate-500 dark:text-slate-400" title="<?= e($p['title']) ?>"><?= e($p['title']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center text-sm font-medium <?= $statusClass ?>"><?= $httpStatus ?: '-' ?></td>
                    <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= number_format((int) ($p['word_count'] ?? 0)) ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ((int) ($p['redirect_hops'] ?? 0) > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                            <?= (int) $p['redirect_hops'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-sm text-slate-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= (int) ($p['depth'] ?? 0) ?></td>
                    <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= (int) ($p['internal_links_in'] ?? 0) ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ((int) ($p['is_indexable'] ?? 0)): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Si</span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= \Core\View::partial('components/table-pagination', [
        'pagination' => $pagination,
        'baseUrl' => $baseUrl,
        'filters' => array_merge(
            array_filter($filters, fn($v) => $v !== '' && $v !== null),
            ['sort' => $sort, 'dir' => $dir]
        ),
    ]) ?>
</div>
<?php endif; ?>
