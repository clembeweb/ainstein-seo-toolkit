<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$basePath = '/seo-audit/project/' . ($project['id'] ?? 0) . '/links';
?>

<!-- Sub-navigation -->
<div class="flex items-center gap-2 mb-6">
    <a href="<?= url($basePath) ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Panoramica</a>
    <a href="<?= url($basePath . '/orphans') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Pagine Orfane</a>
    <a href="<?= url($basePath . '/anchors') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Anchor Text</a>
    <a href="<?= url($basePath . '/graph') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Grafo</a>
</div>

<?php if (($stats['total_pages'] ?? 0) === 0): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
    </svg>
    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun dato disponibile</h3>
    <p class="text-slate-500 dark:text-slate-400">Avvia una scansione per analizzare la struttura dei link del sito.</p>
</div>
<?php else: ?>

<!-- Stat Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Link Interni Totali</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_internal_links']) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Media per Pagina</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['avg_internal_per_page'] ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Pagine Orfane</p>
        <p class="text-2xl font-bold <?= $stats['orphan_pages'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' ?>"><?= $stats['orphan_pages'] ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Link Esterni</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_external_links']) ?></p>
    </div>
</div>

<!-- Dettagli -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Distribuzione Link -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-slate-600 dark:text-slate-400">Pagine analizzate</span>
                <span class="font-medium text-slate-900 dark:text-white"><?= $stats['total_pages'] ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-slate-600 dark:text-slate-400">Pagine senza link uscenti</span>
                <span class="font-medium <?= $stats['pages_without_outlinks'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white' ?>"><?= $stats['pages_without_outlinks'] ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-slate-600 dark:text-slate-400">Max link su una pagina</span>
                <span class="font-medium text-slate-900 dark:text-white"><?= $stats['max_internal_links'] ?></span>
            </div>
            <?php if ($stats['max_internal_url']): ?>
            <div class="pt-2 border-t border-slate-200 dark:border-slate-700">
                <p class="text-xs text-slate-500 dark:text-slate-400">Pagina con pi&ugrave; link:</p>
                <p class="text-xs text-primary-600 dark:text-primary-400 truncate"><?= e($stats['max_internal_url']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Azioni Rapide -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Azioni Rapide</h3>
        <div class="space-y-3">
            <a href="<?= url($basePath . '/orphans') ?>" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Pagine Orfane</span>
                </div>
                <span class="text-sm font-bold <?= $stats['orphan_pages'] > 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= $stats['orphan_pages'] ?></span>
            </a>
            <a href="<?= url($basePath . '/anchors') ?>" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Analisi Anchor Text</span>
                </div>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="<?= url($basePath . '/graph') ?>" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Visualizza Grafo</span>
                </div>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
