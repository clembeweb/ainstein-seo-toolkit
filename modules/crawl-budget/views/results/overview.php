<?php
/**
 * Crawl Budget Optimizer — Results Overview
 */
$projectId = $project['id'];
$circumference = 2 * 3.14159 * 45;
$dashOffset = $circumference - ($circumference * $score / 100);

$ringColors = [
    'emerald' => 'stroke-emerald-500',
    'blue' => 'stroke-blue-500',
    'amber' => 'stroke-amber-500',
    'red' => 'stroke-red-500',
    'slate' => 'stroke-slate-300',
];
$ringStroke = $ringColors[$scoreColor] ?? $ringColors['slate'];

$textColors = [
    'emerald' => 'text-emerald-600 dark:text-emerald-400',
    'blue' => 'text-blue-600 dark:text-blue-400',
    'amber' => 'text-amber-600 dark:text-amber-400',
    'red' => 'text-red-600 dark:text-red-400',
    'slate' => 'text-slate-500 dark:text-slate-400',
];
$textColor = $textColors[$scoreColor] ?? $textColors['slate'];
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
                $isActive = ($currentTab ?? 'results') === $tabKey || ($tabKey === 'results' && !isset($currentTab));
            ?>
            <a href="<?= url($tab['url']) ?>"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 <?= $isActive ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300' ?>">
                <?= $tab['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- Score + Summary -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Score Card -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col items-center justify-center">
        <div class="relative w-32 h-32">
            <svg class="w-32 h-32 -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke-width="8" class="stroke-slate-200 dark:stroke-slate-700"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke-width="8"
                        class="<?= $ringStroke ?>"
                        stroke-linecap="round"
                        stroke-dasharray="<?= $circumference ?>"
                        stroke-dashoffset="<?= $dashOffset ?>"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold <?= $textColor ?>"><?= $score ?></span>
                <span class="text-xs text-slate-500 dark:text-slate-400">/100</span>
            </div>
        </div>
        <p class="mt-3 text-sm font-semibold <?= $textColor ?>"><?= $scoreLabel ?></p>
        <p class="text-xs text-slate-500 dark:text-slate-400">Crawl Budget Score</p>
    </div>

    <!-- Severity Breakdown -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Problemi per Severità</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Critici
                </span>
                <span class="text-lg font-bold text-slate-900 dark:text-white"><?= $severityCounts['critical'] ?? 0 ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Warning
                </span>
                <span class="text-lg font-bold text-slate-900 dark:text-white"><?= $severityCounts['warning'] ?? 0 ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Notice
                </span>
                <span class="text-lg font-bold text-slate-900 dark:text-white"><?= $severityCounts['notice'] ?? 0 ?></span>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Status Code</h3>
        <div class="space-y-3">
            <?php
            $statusColors = ['2xx' => 'emerald', '3xx' => 'blue', '4xx' => 'amber', '5xx' => 'red'];
            foreach ($statusDistribution as $row):
                $color = $statusColors[$row['status_group']] ?? 'slate';
                $pct = $totalPages > 0 ? round(($row['cnt'] / $totalPages) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-slate-700 dark:text-slate-300"><?= $row['status_group'] ?></span>
                    <span class="text-slate-500 dark:text-slate-400"><?= $row['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-<?= $color ?>-500 h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Top Redirect Chains -->
<?php if (!empty($topChains)): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Top Redirect Chains</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="dark:bg-slate-700/50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL Originale</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destinazione</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hop</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($topChains as $chain): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200 max-w-xs truncate"><?= e($chain['url']) ?></td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate"><?= e($chain['redirect_target'] ?? 'N/A') ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                            <?= $chain['redirect_hops'] ?> hop
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= $chain['http_status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Issue Summary by Category -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Riepilogo per Categoria</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="dark:bg-slate-700/50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoria</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Severità</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Conteggio</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php
                $catLabels = ['redirect' => 'Redirect', 'waste' => 'Pagine Spreco', 'indexability' => 'Indexability'];
                $sevColors = [
                    'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                    'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
                    'notice' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                ];
                foreach ($issueSummary as $row):
                ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200"><?= $catLabels[$row['category']] ?? ucfirst($row['category']) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sevColors[$row['severity']] ?? '' ?>">
                            <?= ucfirst($row['severity']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-slate-900 dark:text-white"><?= $row['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
