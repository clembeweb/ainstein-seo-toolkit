<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$basePath = '/seo-audit/project/' . ($project['id'] ?? 0) . '/links';
?>

<!-- Sub-navigation -->
<div class="flex items-center gap-2 mb-6">
    <a href="<?= url($basePath) ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Panoramica</a>
    <a href="<?= url($basePath . '/orphans') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Pagine Orfane</a>
    <a href="<?= url($basePath . '/anchors') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Anchor Text</a>
    <a href="<?= url($basePath . '/graph') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Grafo</a>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Link Interni Totali</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($anchorData['total_links']) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Anchor Unici</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($anchorData['unique_anchors']) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Anchor Vuoti</p>
        <p class="text-2xl font-bold <?= $anchorData['empty_anchors'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white' ?>"><?= $anchorData['empty_anchors'] ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">Anchor Generici</p>
        <p class="text-2xl font-bold <?= count($anchorData['generic_anchors']) > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white' ?>"><?= count($anchorData['generic_anchors']) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Anchor Text -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Anchor Text pi&ugrave; Usati</h3>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Anchor Text</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Occorrenze</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destinazioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($anchorData['top_anchors'] as $anchor): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300 truncate max-w-xs"><?= e($anchor['anchor']) ?></td>
                        <td class="px-4 py-3 text-center font-medium text-slate-900 dark:text-white"><?= $anchor['count'] ?></td>
                        <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-400"><?= $anchor['targets'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($anchorData['top_anchors'])): ?>
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">Nessun dato disponibile</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Anchor Generici (Problema SEO) -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Anchor Generici</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Testi come "clicca qui", "leggi", "scopri" non aiutano il SEO. Usa anchor descrittivi.</p>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Anchor Text</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Occorrenze</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($anchorData['generic_anchors'] as $anchor): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-yellow-700 dark:text-yellow-400">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <?= e($anchor['anchor']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-medium text-slate-900 dark:text-white"><?= $anchor['count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($anchorData['generic_anchors'])): ?>
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center">
                            <svg class="w-8 h-8 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400">Nessun anchor generico rilevato</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
