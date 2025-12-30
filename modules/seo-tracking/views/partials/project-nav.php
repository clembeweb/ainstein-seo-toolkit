<?php
/**
 * Project Sub-Navigation
 * Include this partial in project views to show the navigation tabs
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page (dashboard, keywords, pages, revenue, alerts, reports, settings)
 */

$projectId = $project['id'];
$baseUrl = '/seo-tracking/projects/' . $projectId;

$navItems = [
    'dashboard' => [
        'label' => 'Dashboard',
        'url' => $baseUrl,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    ],
    'keywords' => [
        'label' => 'Keywords',
        'url' => $baseUrl . '/keywords',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
    ],
    'pages' => [
        'label' => 'Pagine',
        'url' => $baseUrl . '/pages',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    ],
    'revenue' => [
        'label' => 'Revenue',
        'url' => $baseUrl . '/revenue',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],
    'alerts' => [
        'label' => 'Alert',
        'url' => $baseUrl . '/alerts',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        'badge' => isset($unreadAlerts) && $unreadAlerts > 0 ? $unreadAlerts : null,
    ],
    'reports' => [
        'label' => 'Report AI',
        'url' => $baseUrl . '/reports',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    ],
    'settings' => [
        'label' => 'Impostazioni',
        'url' => $baseUrl . '/settings',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ],
];

$currentPage = $currentPage ?? 'dashboard';
?>

<div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 mb-6">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Project Info -->
        <div class="flex items-center gap-3">
            <a href="<?= url('/seo-tracking') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-slate-900 dark:text-white"><?= e($project['name']) ?></h2>
                <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($project['domain']) ?></p>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="flex items-center gap-2">
            <?php if ($project['gsc_connected'] ?? false): ?>
            <span class="flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                GSC
            </span>
            <?php else: ?>
            <span class="flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                GSC
            </span>
            <?php endif; ?>

            <?php if ($project['ga4_connected'] ?? false): ?>
            <span class="flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                GA4
            </span>
            <?php else: ?>
            <span class="flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                GA4
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="flex overflow-x-auto scrollbar-hide">
        <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= url($item['url']) ?>"
           class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                  <?= $currentPage === $key
                      ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400'
                      : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-300' ?>">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <?= $item['icon'] ?>
            </svg>
            <?= $item['label'] ?>
            <?php if (!empty($item['badge'])): ?>
            <span class="px-1.5 py-0.5 text-xs font-bold rounded-full bg-red-500 text-white"><?= $item['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
