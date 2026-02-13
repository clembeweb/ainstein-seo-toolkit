<?php $currentPage = 'settings'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura le opzioni del progetto SEO Audit</p>
    </div>

    <!-- Impostazioni Generali -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
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

    <!-- Google Search Console -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Google Search Console</h2>
        </div>
        <div class="p-6">
            <?php if (!empty($project['gsc_connected'])): ?>
            <div class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-800 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-green-800 dark:text-green-200">Connesso</p>
                        <p class="text-sm text-green-600 dark:text-green-400"><?= e($project['gsc_property'] ?? 'Proprieta configurata') ?></p>
                    </div>
                </div>
                <form action="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/disconnect') ?>" method="POST" class="inline"
                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler disconnettere GSC?', {destructive: true}).then(() => $el.submit()).catch(() => {})">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        Disconnetti
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="text-center py-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Collega Google Search Console</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Ottieni dati su query, impressioni e click dal tuo sito</p>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/connect') ?>"
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Connetti Google Search Console
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiche Progetto -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
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
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-red-200 dark:border-red-800">
        <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 rounded-t-2xl">
            <h2 class="text-lg font-semibold text-red-700 dark:text-red-400">Zona Pericolosa</h2>
        </div>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-medium text-slate-900 dark:text-white">Elimina Progetto</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Questa azione eliminera permanentemente il progetto, tutte le pagine scansionate, gli issues e le analisi AI.
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
