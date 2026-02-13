<?php
/**
 * Navigation tabs per progetto seo-audit
 * Pattern: tabs orizzontali come ai-content
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page (dashboard, pages, issues, analysis, links, action-plan, history, gsc, settings)
 */

$projectId = $project['id'] ?? 0;
$basePath = "/seo-audit/project/{$projectId}";

// Definizione tabs
$tabs = [
    'dashboard' => ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
    'pages' => ['path' => '/pages', 'label' => 'Pagine', 'icon' => 'document-text'],
    'issues' => ['path' => '/issues', 'label' => 'Problemi', 'icon' => 'exclamation-triangle'],
    'analysis' => ['path' => '/analysis', 'label' => 'Analisi AI', 'icon' => 'sparkles'],
    'links' => ['path' => '/links', 'label' => 'Struttura Link', 'icon' => 'link'],
    'action-plan' => ['path' => '/action-plan', 'label' => 'Action Plan', 'icon' => 'clipboard-list'],
    'gsc' => ['path' => '/gsc', 'label' => 'Search Console', 'icon' => 'globe-alt'],
    'history' => ['path' => '/history', 'label' => 'Storico', 'icon' => 'clock'],
    'settings' => ['path' => '/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
];

// Helper per verificare tab attivo
function isActiveTabSa($tabKey, $currentPage) {
    $currentPage = $currentPage ?? 'dashboard';
    $aliases = [
        'dashboard' => ['dashboard', 'overview'],
        'pages' => ['pages', 'page-detail', 'import'],
        'issues' => ['issues', 'category'],
        'analysis' => ['analysis', 'analysis-category'],
        'links' => ['links', 'links-orphans', 'links-anchors', 'links-graph'],
        'action-plan' => ['action-plan'],
        'gsc' => ['gsc', 'gsc-connect', 'gsc-properties', 'gsc-dashboard'],
        'history' => ['history'],
        'settings' => ['settings'],
    ];
    return in_array($currentPage, $aliases[$tabKey] ?? [$tabKey]);
}

// Icone Heroicons inline
$navIcons = [
    'chart-bar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'document-text' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    'exclamation-triangle' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
    'sparkles' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
    'link' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>',
    'clipboard-list' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    'globe-alt' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>',
    'clock' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    'cog' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
];

$currentPage = $currentPage ?? 'dashboard';

// Health score
$score = $project['health_score'] ?? null;
if ($score !== null) {
    if ($score >= 80) {
        $scoreBadge = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
    } elseif ($score >= 50) {
        $scoreBadge = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300';
    } else {
        $scoreBadge = 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
    }
}
?>

<!-- Header -->
<div class="sm:flex sm:items-center sm:justify-between mb-4">
    <div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/seo-audit') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" title="Torna ai progetti">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <?php if ($score !== null): ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium <?= $scoreBadge ?>">Score: <?= $score ?></span>
            <?php endif; ?>
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['base_url'] ?? '') ?></p>
    </div>
    <div class="mt-4 sm:mt-0 flex items-center gap-2">
        <a href="<?= url($basePath . '/history') ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
           title="Storico Scansioni">
            <?= $navIcons['clock'] ?>
        </a>
        <a href="<?= url($basePath . '/settings') ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
           title="Impostazioni">
            <?= $navIcons['cog'] ?>
        </a>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="border-b border-slate-200 dark:border-slate-700 mb-6">
    <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
        <?php foreach ($tabs as $key => $tab):
            $isActive = isActiveTabSa($key, $currentPage);
            $href = $basePath . $tab['path'];
        ?>
        <a href="<?= url($href) ?>"
           class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors
                  <?= $isActive
                      ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                      : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' ?>">
            <?= $navIcons[$tab['icon']] ?? '' ?>
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
