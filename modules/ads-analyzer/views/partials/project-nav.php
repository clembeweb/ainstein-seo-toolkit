<?php
/**
 * Navigation tabs per progetto ads-analyzer
 * Pattern: tabs orizzontali come ai-content
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page
 */

$projectId = $project['id'] ?? 0;
$projectType = $project['type'] ?? 'negative-kw';
$isNegKw = $projectType === 'negative-kw';
$isCampaign = $projectType === 'campaign';
$basePath = "/ads-analyzer/projects/{$projectId}";

// Definizione tabs in base al tipo progetto
if ($isCampaign) {
    $tabs = [
        'dashboard'   => ['path' => '/campaign-dashboard', 'label' => 'Dashboard',      'icon' => 'chart-bar'],
        'script'      => ['path' => '/script',             'label' => 'Script Setup',    'icon' => 'code-bracket'],
        'runs'        => ['path' => '/script/runs',        'label' => 'Esecuzioni',      'icon' => 'clock'],
        'campaigns'   => ['path' => '/campaigns',          'label' => 'Campagne',        'icon' => 'presentation-chart-bar'],
        'settings'    => ['path' => '/edit',               'label' => 'Impostazioni',    'icon' => 'cog'],
    ];
} else {
    // negative-kw
    $tabs = [
        'dashboard'  => ['path' => '',              'label' => 'Dashboard',        'icon' => 'chart-bar'],
        'upload'     => ['path' => '/upload',        'label' => 'Upload CSV',       'icon' => 'cloud-upload'],
        'context'    => ['path' => '/landing-urls',  'label' => 'Contesto',         'icon' => 'globe-alt'],
        'results'    => ['path' => '/results',       'label' => 'Risultati',        'icon' => 'document-text'],
        'analyses'   => ['path' => '/analyses',      'label' => 'Storico',          'icon' => 'clock'],
        'export'     => ['path' => '/export/csv',    'label' => 'Export',           'icon' => 'download'],
        'settings'   => ['path' => '/edit',          'label' => 'Impostazioni',     'icon' => 'cog'],
    ];
}

// Helper per verificare tab attivo
function isActiveTabGa($tabKey, $currentPage) {
    $currentPage = $currentPage ?? 'dashboard';
    $aliases = [
        'dashboard'   => ['dashboard', 'overview', 'campaign-dashboard'],
        'upload'      => ['upload'],
        'context'     => ['context', 'landing-urls'],
        'results'     => ['results'],
        'analyses'    => ['analyses', 'analysis'],
        'export'      => ['export'],
        'script'      => ['script'],
        'runs'        => ['runs'],
        'campaigns'   => ['campaigns', 'evaluation', 'evaluations'],
        'settings'    => ['settings', 'edit'],
    ];
    return in_array($currentPage, $aliases[$tabKey] ?? [$tabKey]);
}

// Icone Heroicons inline
$icons = [
    'chart-bar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'cloud-upload' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>',
    'globe-alt' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>',
    'document-text' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    'clock' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    'download' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>',
    'cog' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'code-bracket' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>',
    'presentation-chart-bar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M3 3v18M3 3l6 6m-6 6l6-6m12-6l-6 6m6 6l-6-6M9 17v-4m3 4v-6m3 6v-2"/></svg>',
    'sparkles' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
];

$currentPage = $currentPage ?? 'dashboard';
?>

<!-- Header -->
<div class="sm:flex sm:items-center sm:justify-between mb-4">
    <div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/ads-analyzer/projects?tab=' . $projectType) ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" title="Torna ai progetti">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <?php if ($isCampaign): ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">CAMPAGNE</span>
            <?php else: ?>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">KEYWORD NEGATIVE</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($project['description'])): ?>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['description']) ?></p>
        <?php endif; ?>
    </div>
    <div class="mt-4 sm:mt-0 flex items-center gap-2">
        <?php if ($isCampaign): ?>
        <a href="<?= url($basePath . '/campaigns') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
            <?= $icons['sparkles'] ?>
            <span class="ml-2">Valutazione AI</span>
        </a>
        <?php else: ?>
        <a href="<?= url($basePath . '/landing-urls') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuova Analisi
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="border-b border-slate-200 dark:border-slate-700 mb-6">
    <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
        <?php foreach ($tabs as $key => $tab):
            $isActive = isActiveTabGa($key, $currentPage);
            $href = $basePath . $tab['path'];
        ?>
        <a href="<?= url($href) ?>"
           class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors
                  <?= $isActive
                      ? ($isCampaign ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-amber-500 text-amber-600 dark:text-amber-400')
                      : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' ?>">
            <?= $icons[$tab['icon']] ?? '' ?>
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
