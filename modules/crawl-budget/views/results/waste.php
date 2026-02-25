<?php
/**
 * Crawl Budget Optimizer — Results: Waste Pages
 */
$projectId = $project['id'];

$issueLabels = [
    'soft_404' => 'Soft 404',
    'empty_page' => 'Pagina Vuota',
    'thin_content' => 'Thin Content',
    'parameter_url_crawled' => 'URL con Parametri',
    'deep_page' => 'Pagina Profonda',
    'orphan_page' => 'Pagina Orfana',
    'duplicate_title' => 'Titolo Duplicato',
];

$sevColors = [
    'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
    'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
    'notice' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
];
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

<!-- Severity Filter -->
<div class="flex items-center gap-2 mb-4">
    <span class="text-sm text-slate-500 dark:text-slate-400">Filtra:</span>
    <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/waste') ?>"
       class="px-3 py-1 text-xs font-medium rounded-full <?= empty($severity) ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600' ?>">
        Tutti
    </a>
    <?php foreach (['critical' => 'Critici', 'warning' => 'Warning', 'notice' => 'Notice'] as $sev => $sevLabel): ?>
    <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/waste?severity=' . $sev) ?>"
       class="px-3 py-1 text-xs font-medium rounded-full <?= ($severity ?? '') === $sev ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600' ?>">
        <?= $sevLabel ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Issues Table -->
<?php if (empty($issues)): ?>
    <?= \Core\View::partial('components/table-empty-state', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9.75m3 0v3.375m0 0H9.75m3.375 0h3.375M6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>',
        'heading' => 'Nessuna pagina spreco',
        'message' => 'Non sono state rilevate pagine spreco in questo crawl.',
    ]) ?>
<?php else: ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="dark:bg-slate-700/50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Severit&agrave;</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dettagli</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($issues as $issue):
                    $details = is_string($issue['details'] ?? null) ? json_decode($issue['details'], true) : ($issue['details'] ?? []);
                    $typeLabel = $issueLabels[$issue['type'] ?? ''] ?? ucfirst(str_replace('_', ' ', $issue['type'] ?? 'N/A'));
                ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200 max-w-xs">
                        <span class="block truncate" title="<?= e($issue['page_url'] ?? $issue['url'] ?? '') ?>"><?= e($issue['page_url'] ?? $issue['url'] ?? 'N/A') ?></span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= $typeLabel ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sevColors[$issue['severity']] ?? '' ?>">
                            <?= ucfirst($issue['severity']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 max-w-sm">
                        <?php if (!empty($details['word_count'])): ?>
                            <span class="text-xs"><?= (int) $details['word_count'] ?> parole</span>
                        <?php endif; ?>
                        <?php if (!empty($details['depth'])): ?>
                            <span class="text-xs">Profondita: <?= (int) $details['depth'] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($details['title'])): ?>
                            <span class="block truncate text-xs" title="<?= e($details['title']) ?>">Titolo: <?= e($details['title']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($details['duplicate_count'])): ?>
                            <span class="text-xs"><?= (int) $details['duplicate_count'] ?> pagine con stesso titolo</span>
                        <?php endif; ?>
                        <?php if (!empty($details['internal_links_in'])): ?>
                            <span class="text-xs"><?= (int) $details['internal_links_in'] ?> link in entrata</span>
                        <?php endif; ?>
                        <?php if (!empty($details['reason'])): ?>
                            <span class="block text-xs"><?= e($details['reason']) ?></span>
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
        'filters' => array_filter(['severity' => $severity ?? '']),
    ]) ?>
</div>
<?php endif; ?>
