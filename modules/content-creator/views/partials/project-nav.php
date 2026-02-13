<?php
/**
 * Navigation tabs per progetto content-creator
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page
 */

$projectId = $project['id'] ?? 0;
$basePath = "/content-creator/projects/{$projectId}";

$tabs = [
    'dashboard' => ['path' => '', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
    'import' => ['path' => '/import', 'label' => 'Import URL', 'icon' => 'cloud-upload'],
    'results' => ['path' => '/results', 'label' => 'Risultati', 'icon' => 'document-text'],
    'settings' => ['path' => '/settings', 'label' => 'Impostazioni', 'icon' => 'cog'],
];

function isActiveTabCc($tabKey, $currentPage) {
    $currentPage = $currentPage ?? 'dashboard';
    $aliases = [
        'dashboard' => ['dashboard', 'overview'],
        'import' => ['import'],
        'results' => ['results', 'preview'],
        'settings' => ['settings'],
    ];
    return in_array($currentPage, $aliases[$tabKey] ?? [$tabKey]);
}

$icons = [
    'chart-bar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'cloud-upload' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>',
    'document-text' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    'cog' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
];

$currentPage = $currentPage ?? 'dashboard';

$contentTypes = [
    'product' => ['label' => 'Prodotto', 'color' => 'teal'],
    'category' => ['label' => 'Categoria', 'color' => 'blue'],
    'article' => ['label' => 'Articolo', 'color' => 'purple'],
    'custom' => ['label' => 'Custom', 'color' => 'slate'],
];
$ct = $contentTypes[$project['content_type'] ?? 'product'] ?? $contentTypes['product'];
?>

<!-- Header -->
<div class="sm:flex sm:items-center sm:justify-between mb-4">
    <div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/content-creator') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" title="Torna ai progetti">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-<?= $ct['color'] ?>-100 text-<?= $ct['color'] ?>-700 dark:bg-<?= $ct['color'] ?>-900/50 dark:text-<?= $ct['color'] ?>-300"><?= strtoupper($ct['label']) ?></span>
        </div>
        <?php if (!empty($project['description'])): ?>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['description']) ?></p>
        <?php else: ?>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            <?= e($project['language'] ?? 'it') ?> / <?= e($project['tone'] ?? 'professionale') ?>
        </p>
        <?php endif; ?>
    </div>
    <div class="mt-4 sm:mt-0 flex items-center gap-2">
        <a href="<?= url('/content-creator/connectors') ?>"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
           title="Connettori CMS">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        </a>
        <a href="<?= url($basePath . '/import') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Importa URL
        </a>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="border-b border-slate-200 dark:border-slate-700 mb-6">
    <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
        <?php foreach ($tabs as $key => $tab):
            $isActive = isActiveTabCc($key, $currentPage);
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
