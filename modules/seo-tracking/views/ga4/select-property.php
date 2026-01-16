<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/settings') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alle impostazioni
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Seleziona Property GA4</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Seleziona la property di Google Analytics 4 da collegare a <strong><?= e($project['name']) ?></strong>
        </p>
    </div>

    <?php if (!empty($matchingProperties)): ?>
    <!-- Matching Properties -->
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
            <?php foreach ($matchingProperties as $prop): ?>
            <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/ga4/select-property') ?>" method="POST" class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="property_id" value="<?= e($prop['property_id']) ?>">
                <input type="hidden" name="property_name" value="<?= e($prop['property_name']) ?>">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center">
                            <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.84 2.02c-.12-.12-.27-.18-.42-.18h-6.84c-.32 0-.58.26-.58.58 0 .32.26.58.58.58h5.68l-8.42 8.42c-.23.23-.23.6 0 .82.11.11.26.17.41.17s.3-.06.41-.17l8.42-8.42v5.68c0 .32.26.58.58.58.32 0 .58-.26.58-.58V2.44c0-.16-.06-.3-.18-.42z"/>
                                <path d="M17.5 22H4.5C3.12 22 2 20.88 2 19.5v-13C2 5.12 3.12 4 4.5 4h6.25c.55 0 1 .45 1 1s-.45 1-1 1H4.5c-.28 0-.5.22-.5.5v13c0 .28.22.5.5.5h13c.28 0 .5-.22.5-.5v-6.25c0-.55.45-1 1-1s1 .45 1 1v6.25c0 1.38-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($prop['property_name']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                ID: <?= e($prop['property_id']) ?>
                                <?php if (!empty($prop['account_name'])): ?>
                                    &bull; <?= e($prop['account_name']) ?>
                                <?php endif; ?>
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

    <?php if (!empty($otherProperties)): ?>
    <!-- Other Properties -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-medium text-slate-900 dark:text-white">Altre property disponibili</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Property non corrispondenti al dominio del progetto</p>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($otherProperties as $prop): ?>
            <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/ga4/select-property') ?>" method="POST" class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="property_id" value="<?= e($prop['property_id']) ?>">
                <input type="hidden" name="property_name" value="<?= e($prop['property_name']) ?>">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                            <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($prop['property_name']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                ID: <?= e($prop['property_id']) ?>
                                <?php if (!empty($prop['account_name'])): ?>
                                    &bull; <?= e($prop['account_name']) ?>
                                <?php endif; ?>
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

    <?php if (empty($matchingProperties) && empty($otherProperties)): ?>
    <!-- No Properties -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna property trovata</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            L'account Google collegato non ha accesso a nessuna property GA4.
            Assicurati di avere i permessi corretti in Google Analytics.
        </p>
        <div class="flex gap-3 justify-center">
            <a href="https://analytics.google.com/" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Vai a Google Analytics
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/ga4/connect') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
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
                    <li>&bull; Scegli la property GA4 che corrisponde al dominio del tuo progetto</li>
                    <li>&bull; Se hai piu property per lo stesso dominio, scegli quella con dati piu completi</li>
                    <li>&bull; I dati verranno sincronizzati solo per il traffico organico (Organic Search)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
