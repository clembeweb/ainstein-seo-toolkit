<?php
/**
 * Crawl Budget Optimizer — Results: Indexability Issues
 */
$projectId = $project['id'];

$issueLabels = [
    'noindex_in_sitemap' => 'Noindex in Sitemap',
    'mixed_signals' => 'Segnali Misti',
    'blocked_but_linked' => 'Bloccata ma Linkata',
    'canonical_mismatch' => 'Canonical Mismatch',
    'blocked_in_robots' => 'Bloccata da Robots.txt',
    'canonical_chain' => 'Canonical Chain',
    'noindex_receives_links' => 'Noindex con Link Interni',
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
    <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/indexability') ?>"
       class="px-3 py-1 text-xs font-medium rounded-full <?= empty($severity) ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600' ?>">
        Tutti
    </a>
    <?php foreach (['critical' => 'Critici', 'warning' => 'Warning', 'notice' => 'Notice'] as $sev => $sevLabel): ?>
    <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/indexability?severity=' . $sev) ?>"
       class="px-3 py-1 text-xs font-medium rounded-full <?= ($severity ?? '') === $sev ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600' ?>">
        <?= $sevLabel ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Issues Table -->
<?php if (empty($issues)): ?>
    <?= \Core\View::partial('components/table-empty-state', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>',
        'heading' => 'Nessun problema indexability',
        'message' => 'Non sono stati rilevati conflitti di indexability in questo crawl.',
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
                        <?php if (!empty($details['canonical'])): ?>
                            <span class="block truncate text-xs" title="<?= e($details['canonical']) ?>">Canonical: <?= e($details['canonical']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($details['meta_robots'])): ?>
                            <span class="text-xs">Meta robots: <?= e($details['meta_robots']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($details['robots_txt_status'])): ?>
                            <span class="text-xs">Robots.txt: <?= e($details['robots_txt_status']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($details['in_sitemap'])): ?>
                            <span class="text-xs">In sitemap: Si</span>
                        <?php endif; ?>
                        <?php if (!empty($details['internal_links_in'])): ?>
                            <span class="text-xs"><?= (int) $details['internal_links_in'] ?> link in entrata</span>
                        <?php endif; ?>
                        <?php if (!empty($details['chain_length'])): ?>
                            <span class="text-xs">Chain: <?= (int) $details['chain_length'] ?> hop</span>
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
