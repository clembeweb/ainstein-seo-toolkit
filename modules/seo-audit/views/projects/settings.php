<?php $currentPage = 'settings'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura le opzioni del progetto SEO Audit</p>
    </div>

    <!-- Impostazioni Generali -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Impostazioni Generali</h2>
        </div>
        <form action="<?= url('/seo-audit/project/' . $project['id'] . '/settings') ?>" method="POST" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Nome Progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome Progetto
                </label>
                <input type="text" name="name" id="name" value="<?= e($project['name']) ?>" required
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- URL Base (readonly) -->
            <div>
                <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    URL Base
                </label>
                <input type="url" name="base_url" id="base_url" value="<?= e($project['base_url']) ?>" readonly
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-600 text-slate-500 dark:text-slate-400 py-2.5 px-3 cursor-not-allowed">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">L'URL base non puo essere modificato dopo la creazione</p>
            </div>

            <!-- Submit -->
            <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salva Modifiche
                </button>
            </div>
        </form>
    </div>

    <!-- Statistiche Progetto -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Statistiche</h2>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">Pagine Trovate</dt>
                    <dd class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($project['pages_found'] ?? 0) ?></dd>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">Pagine Scansionate</dt>
                    <dd class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($project['pages_crawled'] ?? 0) ?></dd>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">Issues Totali</dt>
                    <dd class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($project['issues_count'] ?? 0) ?></dd>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">Health Score</dt>
                    <dd class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $project['health_score'] ?? '-' ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Zona Pericolosa -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800">
        <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 rounded-t-xl">
            <h2 class="text-lg font-semibold text-red-700 dark:text-red-400">Zona Pericolosa</h2>
        </div>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-medium text-slate-900 dark:text-white">Elimina Progetto</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Questa azione eliminera permanentemente il progetto, tutte le pagine scansionate e gli issues.
                    </p>
                </div>
                <form action="<?= url('/seo-audit/project/' . $project['id'] . '/delete') ?>" method="POST"
                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo progetto? Questa azione non puo essere annullata.', {destructive: true}).then(() => $el.submit()).catch(() => {})">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Elimina Progetto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
