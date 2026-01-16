<?php
/**
 * Project Sub-Navigation
 * Include this partial in project views to show the navigation tabs
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page (overview, keywords, groups, pages, quickwins)
 */

$projectId = $project['id'];
$baseUrl = '/seo-tracking/projects/' . $projectId;

$navItems = [
    'overview' => [
        'label' => 'Overview',
        'url' => $baseUrl . '/dashboard',
    ],
    'keywords' => [
        'label' => 'Keywords',
        'url' => $baseUrl . '/keywords-overview',
    ],
    'groups' => [
        'label' => 'Gruppi',
        'url' => $baseUrl . '/groups',
    ],
    'pages' => [
        'label' => 'Pagine',
        'url' => $baseUrl . '/pages',
    ],
    'quickwins' => [
        'label' => 'Quick Wins',
        'url' => $baseUrl . '/ai/quick-wins',
        'icon' => '<svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
    ],
];

$currentPage = $currentPage ?? 'overview';
?>

<!-- Header -->
<div class="sm:flex sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/seo-tracking') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <?php if ($project['gsc_connected'] ?? false): ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">GSC</span>
            <?php endif; ?>
            <?php if ($project['ga4_connected'] ?? false): ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">GA4</span>
            <?php endif; ?>
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['domain']) ?></p>
    </div>
    <div class="mt-4 sm:mt-0 flex gap-3">
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/settings') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Impostazioni
        </a>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="border-b border-slate-200 dark:border-slate-700">
    <nav class="flex gap-6">
        <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= url($item['url']) ?>"
           class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-1
                  <?= $currentPage === $key
                      ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                      : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
            <?php if (!empty($item['icon'])): ?>
            <?= $item['icon'] ?>
            <?php endif; ?>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
