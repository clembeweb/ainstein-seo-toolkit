<?php $currentPage = 'situation'; ?>
<div class="space-y-6">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <div class="max-w-2xl bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Scegli la proprietà Google Analytics</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Quella del sito <?= e($project['domain']) ?>.</p>
        <?php if (!$properties): ?>
        <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">L'account Google che hai collegato non vede nessuna proprietà GA4. Ricollega con l'account giusto.</p>
        <a href="<?= url('/seo-tracking/project/' . (int) $project['id'] . '/ga4/connect') ?>" class="mt-3 inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Ricollega</a>
        <?php else: ?>
        <?php
        // Prima le proprietà che sembrano del sito del progetto
        $host = preg_replace('/^www\./', '', strtolower((string) $project['domain']));
        $label = explode('.', $host)[0] ?? '';
        usort($properties, fn($a, $b) => (int) (stripos($b['property_name'] . ' ' . $b['account_name'], $label) !== false) <=> (int) (stripos($a['property_name'] . ' ' . $a['account_name'], $label) !== false));
        ?>
        <form method="POST" action="<?= url('/seo-tracking/project/' . (int) $project['id'] . '/ga4/select-property') ?>" class="mt-4 space-y-2">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <?php foreach ($properties as $i => $p): ?>
            <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                <input type="radio" name="property_id" value="<?= e($p['property_id']) ?>" <?= $i === 0 ? 'checked' : '' ?> class="mt-1">
                <span>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white"><?= e($p['property_name']) ?></span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400"><?= e($p['account_name']) ?> · ID <?= e($p['property_id']) ?></span>
                </span>
            </label>
            <?php endforeach; ?>
            <button type="submit" class="mt-2 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Collega questa proprietà</button>
        </form>
        <?php endif; ?>
    </div>
</div>
