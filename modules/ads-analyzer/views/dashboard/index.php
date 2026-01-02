<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Google Ads Analyzer</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analizza termini di ricerca ed estrai keyword negative con AI</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/ads-analyzer/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Progetti -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Progetti</p>
                </div>
            </div>
        </div>

        <!-- Completati -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['completed'] ?? 0) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Completati</p>
                </div>
            </div>
        </div>

        <!-- Termini Analizzati -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_terms'] ?? 0) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Termini</p>
                </div>
            </div>
        </div>

        <!-- Negative Trovate -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_negatives'] ?? 0) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Negative</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions + Recent Projects -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Azioni rapide</h2>
            <div class="space-y-3">
                <a href="<?= url('/ads-analyzer/projects/create') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Nuovo Progetto</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Carica CSV Google Ads</p>
                    </div>
                </a>

                <a href="<?= url('/ads-analyzer/projects') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Vedi Progetti</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Gestisci le analisi</p>
                    </div>
                </a>

                <a href="<?= url('/ads-analyzer/settings') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Impostazioni</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configura il modulo</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Progetti recenti</h2>
                <a href="<?= url('/ads-analyzer/projects') ?>" class="text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700">
                    Vedi tutti
                </a>
            </div>

            <?php if (empty($recentProjects)): ?>
            <div class="text-center py-8">
                <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun progetto creato</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Inizia caricando un export CSV da Google Ads</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentProjects as $project): ?>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center flex-shrink-0
                            <?php if ($project['status'] === 'completed'): ?>
                            bg-emerald-100 dark:bg-emerald-900/50
                            <?php elseif ($project['status'] === 'analyzing'): ?>
                            bg-amber-100 dark:bg-amber-900/50
                            <?php elseif ($project['status'] === 'archived'): ?>
                            bg-slate-100 dark:bg-slate-700
                            <?php else: ?>
                            bg-blue-100 dark:bg-blue-900/50
                            <?php endif; ?>
                        ">
                            <?php if ($project['status'] === 'completed'): ?>
                            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <?php elseif ($project['status'] === 'analyzing'): ?>
                            <svg class="h-4 w-4 text-amber-600 dark:text-amber-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?php else: ?>
                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white truncate"><?= e($project['name']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= number_format($project['total_ad_groups']) ?> Ad Group &bull;
                                <?= number_format($project['total_terms']) ?> termini &bull;
                                <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                        <?php if ($project['status'] === 'completed'): ?>
                        bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                        <?php elseif ($project['status'] === 'analyzing'): ?>
                        bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                        <?php elseif ($project['status'] === 'archived'): ?>
                        bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
                        <?php else: ?>
                        bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300
                        <?php endif; ?>
                    ">
                        <?= ucfirst($project['status']) ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Credits Info -->
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Costi Crediti</h3>
                <p class="text-amber-100 text-sm mt-1">Informazioni sui costi per le operazioni</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <p class="text-2xl font-bold">2</p>
                    <p class="text-xs text-amber-200">Analisi singola</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold">1.5</p>
                    <p class="text-xs text-amber-200">Analisi bulk (4+)</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold">0</p>
                    <p class="text-xs text-amber-200">Export CSV</p>
                </div>
            </div>
        </div>
    </div>
</div>
