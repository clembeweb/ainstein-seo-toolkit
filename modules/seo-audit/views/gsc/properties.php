<?php $currentPage = 'gsc-properties'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Seleziona Proprietà GSC</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scegli la proprietà Search Console da collegare al progetto</p>
    </div>

    <?php if ($isMockMode): ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex items-center gap-2 text-yellow-700 dark:text-yellow-300">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-sm font-medium">Modalità Demo - Proprietà di esempio</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($properties)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessuna proprietà trovata</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
            Non sono state trovate proprietà Search Console associate al tuo account Google.
            Verifica di aver verificato almeno un sito su Search Console.
        </p>
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/connect') ?>" class="inline-flex items-center mt-6 text-sm text-primary-600 hover:text-primary-700">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna indietro
        </a>
    </div>
    <?php else: ?>
    <form action="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/select-property') ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="font-semibold text-slate-900 dark:text-white">Proprietà Disponibili</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= count($properties) ?> proprietà trovate</p>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($properties as $i => $property): ?>
                <label class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors">
                    <input type="radio" name="property_url" value="<?= e($property['url']) ?>" <?= $i === 0 ? 'checked' : '' ?> class="w-4 h-4 text-primary-600 border-slate-300 focus:ring-primary-500">
                    <input type="hidden" name="property_type" value="<?= e($property['type']) ?>">

                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <?php if ($property['type'] === 'DOMAIN'): ?>
                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                Dominio
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                URL Prefix
                            </span>
                            <?php endif; ?>
                            <span class="font-medium text-slate-900 dark:text-white"><?= e($property['url']) ?></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Permessi: <?= e($property['permission_level']) ?>
                        </p>
                    </div>

                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/connect') ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    Annulla
                </a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    Collega Proprietà
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
