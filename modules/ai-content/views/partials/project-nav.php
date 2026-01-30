<?php
/**
 * Navigation tabs per progetto ai-content
 * Pattern: tabs orizzontali come seo-tracking
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page (dashboard, keywords, articles, settings, queue, add-keywords)
 */

$projectId = $project['id'] ?? 0;
$projectType = $project['type'] ?? 'manual';
$isAuto = $projectType === 'auto';
$isMetaTag = $projectType === 'meta-tag';
$basePath = "/ai-content/projects/{$projectId}";

// Definizione tabs in base al tipo progetto
if ($isMetaTag) {
    $tabs = [
        'dashboard' => ['path' => '/meta-tags', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
        'list' => ['path' => '/meta-tags/list', 'label' => 'Meta Tags', 'icon' => 'tag'],
        'import' => ['path' => '/meta-tags/import', 'label' => 'Import', 'icon' => 'cloud-upload'],
        'settings' => ['path' => '/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
    ];
} elseif ($isAuto) {
    $tabs = [
        'dashboard' => ['path' => '/auto', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
        'queue' => ['path' => '/auto/queue', 'label' => 'Coda', 'icon' => 'queue-list'],
        'articles' => ['path' => '/articles', 'label' => 'Articoli', 'icon' => 'document-text'],
        'internal-links' => ['path' => '/internal-links', 'label' => 'Link Interni', 'icon' => 'link'],
        'settings' => ['path' => '/auto/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
    ];
} else {
    $tabs = [
        'dashboard' => ['path' => '', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
        'keywords' => ['path' => '/keywords', 'label' => 'Keywords', 'icon' => 'key'],
        'articles' => ['path' => '/articles', 'label' => 'Articoli', 'icon' => 'document-text'],
        'internal-links' => ['path' => '/internal-links', 'label' => 'Link Interni', 'icon' => 'link'],
        'settings' => ['path' => '/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
    ];
}

// Helper per verificare tab attivo
function isActiveTabAic($tabKey, $currentPage) {
    $currentPage = $currentPage ?? 'dashboard';
    $aliases = [
        'dashboard' => ['dashboard', 'overview', 'auto', 'meta-tags'],
        'keywords' => ['keywords'],
        'articles' => ['articles', 'article'],
        'settings' => ['settings'],
        'queue' => ['queue'],
        'add-keywords' => ['add-keywords', 'add'],
        'internal-links' => ['internal-links'],
        'list' => ['list', 'meta-tags-list'],
        'import' => ['import', 'meta-tags-import'],
    ];
    return in_array($currentPage, $aliases[$tabKey] ?? [$tabKey]);
}

// Icone Heroicons inline
$icons = [
    'chart-bar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'key' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>',
    'document-text' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    'cog' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'queue-list' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>',
    'link' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>',
    'clipboard-list' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    'wordpress' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/></svg>',
    'tag' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>',
    'cloud-upload' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>',
];

$currentPage = $currentPage ?? 'dashboard';
?>

<!-- Header -->
<div class="sm:flex sm:items-center sm:justify-between mb-4">
    <div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/ai-content') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" title="Torna ai progetti">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <?php if ($isMetaTag): ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">META TAG</span>
            <?php elseif ($isAuto): ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">AUTO</span>
            <?php else: ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">MANUAL</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($project['description'])): ?>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['description']) ?></p>
        <?php else: ?>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            <?= e($project['default_language'] ?? 'it') ?> / <?= e($project['default_location'] ?? 'Italy') ?>
        </p>
        <?php endif; ?>
    </div>
    <div class="mt-4 sm:mt-0 flex items-center gap-2">
        <!-- Link globali -->
        <a href="<?= url('/ai-content/jobs') ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
           title="Gestione Job">
            <?= $icons['clipboard-list'] ?>
        </a>
        <a href="<?= url('/ai-content/wordpress') ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
           title="Siti WordPress">
            <?= $icons['wordpress'] ?>
        </a>
        <?php if ($isMetaTag): ?>
        <a href="<?= url($basePath . '/meta-tags/import') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Importa URL
        </a>
        <?php elseif ($isAuto): ?>
        <a href="<?= url($basePath . '/auto/add') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Aggiungi Keyword
        </a>
        <?php else: ?>
        <a href="<?= url($basePath . '/keywords') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuova Keyword
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="border-b border-slate-200 dark:border-slate-700 mb-6">
    <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
        <?php foreach ($tabs as $key => $tab):
            $isActive = isActiveTabAic($key, $currentPage);
            $href = $basePath . $tab['path'];
        ?>
        <a href="<?= url($href) ?>"
           class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors
                  <?= $isActive
                      ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                      : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' ?>">
            <?= $icons[$tab['icon']] ?? '' ?>
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
