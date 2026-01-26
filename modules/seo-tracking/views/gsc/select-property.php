<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/settings') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alle impostazioni
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Seleziona Property GSC</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Seleziona la property di Google Search Console da collegare a <strong><?= e($project['name']) ?></strong>
        </p>
    </div>

    <?php if (!empty($matchingSites)): ?>
    <!-- Matching Sites -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="font-medium text-emerald-900 dark:text-emerald-100">Property consigliate</h2>
            </div>
            <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">Corrispondono al dominio del progetto: <?= e($project['domain']) ?></p>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($matchingSites as $site): ?>
            <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/select-property') ?>" method="POST" class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="site_url" value="<?= e($site['siteUrl']) ?>">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($site['siteUrl']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= $site['permissionLevel'] ?? 'N/A' ?>
                            </p>
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors text-sm">
                        Seleziona
                    </button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($otherSites)): ?>
    <!-- Other Sites -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-medium text-slate-900 dark:text-white">Altre property disponibili</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Property non corrispondenti al dominio del progetto</p>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($otherSites as $site): ?>
            <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/select-property') ?>" method="POST" class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="site_url" value="<?= e($site['siteUrl']) ?>">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                            <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($site['siteUrl']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= $site['permissionLevel'] ?? 'N/A' ?>
                            </p>
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-sm">
                        Seleziona
                    </button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($matchingSites) && empty($otherSites)): ?>
    <!-- No Sites -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna property trovata</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            L'account Google collegato non ha accesso a nessuna property in Search Console.
            Assicurati di aver aggiunto il sito in GSC e di avere i permessi corretti.
        </p>
        <div class="flex gap-3 justify-center">
            <a href="https://search.google.com/search-console" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Vai a Search Console
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/connect') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Riprova con altro account
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Help Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Quale property scegliere?</h4>
                <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• <strong>Property dominio</strong> (es. sc-domain:example.com): include tutti i sottodomini e protocolli</li>
                    <li>• <strong>Property URL</strong> (es. https://www.example.com/): solo quel prefisso specifico</li>
                    <li>• Scegli la property che corrisponde al dominio del tuo progetto per dati completi</li>
                </ul>
            </div>
        </div>
    </div>
</div>
