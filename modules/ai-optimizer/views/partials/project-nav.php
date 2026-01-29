<?php
/**
 * Navigation tabs per progetto AI Optimizer
 *
 * Variabili disponibili:
 * - $project: dati progetto
 * - $projectId: ID progetto
 * - $currentPage: pagina corrente (dashboard, optimize, settings)
 */

$currentPage = $currentPage ?? 'dashboard';
$projectId = $projectId ?? $project['id'] ?? 0;

$tabs = [
    'dashboard' => [
        'label' => 'Dashboard',
        'url' => url('/ai-optimizer/project/' . $projectId),
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>',
    ],
    'optimize' => [
        'label' => 'Nuova Ottimizzazione',
        'url' => url('/ai-optimizer/project/' . $projectId . '/optimize'),
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
    ],
    'settings' => [
        'label' => 'Impostazioni',
        'url' => url('/ai-optimizer/project/' . $projectId . '/settings'),
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ],
];
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-4">
    <a href="<?= url('/ai-optimizer') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">AI Optimizer</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium"><?= e($project['name'] ?? 'Progetto') ?></span>
</div>

<!-- Header con nome progetto -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name'] ?? 'Progetto') ?></h1>
        <?php if (!empty($project['domain'])): ?>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?= e($project['domain']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="border-b border-slate-200 dark:border-slate-700 mb-6">
    <nav class="-mb-px flex gap-4" aria-label="Tabs">
        <?php foreach ($tabs as $key => $tab): ?>
        <a href="<?= $tab['url'] ?>"
           class="group inline-flex items-center gap-2 px-1 py-3 border-b-2 text-sm font-medium transition-colors <?= $currentPage === $key ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200' ?>">
            <?= $tab['icon'] ?>
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
