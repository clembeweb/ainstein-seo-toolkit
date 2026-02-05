<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI SEO Onpage Optimizer</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analizza e ottimizza le pagine del tuo sito con 100+ check SEO e suggerimenti AI</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/seo-onpage/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Crea il tuo primo progetto per iniziare ad analizzare e ottimizzare le pagine del tuo sito.
        </p>
        <a href="<?= url('/seo-onpage/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea il primo progetto
        </a>
    </div>
    <?php else: ?>
    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <!-- Project Header -->
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <a href="<?= url('/seo-onpage/project/' . $project['id']) ?>" class="block">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-emerald-600 dark:hover:text-emerald-400">
                                <?= e($project['name']) ?>
                            </h3>
                        </a>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['domain']) ?></p>
                    </div>
                    <?php if ($project['avg_score']): ?>
                    <div class="ml-2">
                        <?php
                        $score = $project['avg_score'];
                        $scoreColor = $score >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                     ($score >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                     'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300');
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?= $scoreColor ?>">
                            <?= round($score) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['pages_count'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pagine</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($project['pages_analyzed'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Analizzate</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-red-600 dark:text-red-400"><?= number_format($project['critical_issues'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Critici</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <?php if ($project['last_audit_at']): ?>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Ultimo audit: <?= date('d/m H:i', strtotime($project['last_audit_at'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Mai analizzato</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="<?= url('/seo-onpage/project/' . $project['id']) ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </a>
                        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Come funziona</h3>
                <p class="text-emerald-100 text-sm mt-1">Analizza le tue pagine e ricevi suggerimenti AI per ottimizzarle</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">1</span>
                    </div>
                    <p class="text-xs text-emerald-200">Importa URL</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">2</span>
                    </div>
                    <p class="text-xs text-emerald-200">Avvia Audit</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">3</span>
                    </div>
                    <p class="text-xs text-emerald-200">Ottimizza</p>
                </div>
            </div>
        </div>
    </div>
</div>
