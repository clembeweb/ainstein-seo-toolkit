<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$basePath = '/seo-audit/project/' . ($project['id'] ?? 0) . '/links';
?>

<!-- Sub-navigation -->
<div class="flex items-center gap-2 mb-6">
    <a href="<?= url($basePath) ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Panoramica</a>
    <a href="<?= url($basePath . '/orphans') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Pagine Orfane</a>
    <a href="<?= url($basePath . '/anchors') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Anchor Text</a>
    <a href="<?= url($basePath . '/graph') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Grafo</a>
</div>

<!-- Info -->
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-6">
    <div class="flex gap-3">
        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Pagine Orfane</p>
            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                Le pagine orfane non ricevono link interni da altre pagine del sito. Questo pu&ograve; ridurre la loro visibilit&agrave; per i motori di ricerca.
            </p>
        </div>
    </div>
</div>

<?php if (empty($orphans)): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna pagina orfana</h3>
    <p class="text-slate-500 dark:text-slate-400">Tutte le pagine ricevono almeno un link interno.</p>
</div>
<?php else: ?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
        <h3 class="font-semibold text-slate-900 dark:text-white"><?= count($orphans) ?> pagine orfane trovate</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Titolo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Link Uscenti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($orphans as $orphan): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-4 py-3">
                        <a href="<?= url('/seo-audit/project/' . ($project['id'] ?? 0) . '/page/' . $orphan['id']) ?>"
                           class="text-primary-600 dark:text-primary-400 hover:underline truncate block max-w-md" title="<?= e($orphan['url']) ?>">
                            <?= e(strlen($orphan['url']) > 60 ? '...' . substr($orphan['url'], -57) : $orphan['url']) ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                        <?= e($orphan['title'] ?: '-') ?>
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-400">
                        <?= $orphan['outgoing_links'] ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
