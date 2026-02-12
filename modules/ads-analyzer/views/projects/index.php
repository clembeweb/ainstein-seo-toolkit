<div class="space-y-6" x-data="{ activeTab: '<?= e($activeTab) ?>' }">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Progetti</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci le tue analisi Google Ads</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/ads-analyzer/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <!-- Keyword Negative Tab -->
            <button @click="activeTab = 'negative-kw'"
                    :class="activeTab === 'negative-kw' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Keyword Negative
                <?php if (count($projectsByType['negative-kw'] ?? []) > 0): ?>
                <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-0.5 px-2 rounded-full text-xs"><?= count($projectsByType['negative-kw']) ?></span>
                <?php endif; ?>
            </button>

            <!-- Analisi Campagne Tab -->
            <button @click="activeTab = 'campaign'"
                    :class="activeTab === 'campaign' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analisi Campagne
                <?php if (count($projectsByType['campaign'] ?? []) > 0): ?>
                <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-0.5 px-2 rounded-full text-xs"><?= count($projectsByType['campaign']) ?></span>
                <?php endif; ?>
            </button>
        </nav>
    </div>

    <!-- Tab Content: Keyword Negative -->
    <div x-show="activeTab === 'negative-kw'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <?php if (empty($projectsByType['negative-kw'])): ?>
        <!-- Empty State NegKW -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto Keyword Negative</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                Carica un export CSV da Google Ads per analizzare i search terms e trovare keyword negative con AI.
            </p>
            <a href="<?= url('/ads-analyzer/projects/create?type=negative-kw') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crea progetto Keyword Negative
            </a>
        </div>
        <?php else: ?>
        <!-- Projects Grid NegKW -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projectsByType['negative-kw'] as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="block">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-amber-600 dark:hover:text-amber-400">
                                    <?= e($project['name']) ?>
                                </h3>
                            </a>
                            <?php if (!empty($project['description'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ml-2
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
                    </div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_ad_groups'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Ad Groups</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_terms'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Termini</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-red-600 dark:text-red-400"><?= number_format($project['total_negatives_found'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Negative</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <?php if (($project['analyses_count'] ?? 0) > 0): ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                <?= $project['analyses_count'] ?> analisi
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/edit') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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

        <!-- Info Box NegKW -->
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg shadow-sm p-6 text-white mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Keyword Negative</h3>
                    <p class="text-amber-100 text-sm mt-1">Trova ed escludi search terms irrilevanti dalle campagne</p>
                </div>
                <div class="hidden md:flex gap-6">
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">1</span>
                        </div>
                        <p class="text-xs text-amber-200">Carica CSV</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">2</span>
                        </div>
                        <p class="text-xs text-amber-200">Contesto business</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">3</span>
                        </div>
                        <p class="text-xs text-amber-200">Analisi AI</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">4</span>
                        </div>
                        <p class="text-xs text-amber-200">Export</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content: Analisi Campagne -->
    <div x-show="activeTab === 'campaign'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <?php if (empty($projectsByType['campaign'])): ?>
        <!-- Empty State Campaign -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto Analisi Campagne</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                Configura Google Ads Script per raccogliere dati sulle campagne e ricevere valutazioni AI.
            </p>
            <a href="<?= url('/ads-analyzer/projects/create?type=campaign') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crea progetto Analisi Campagne
            </a>
        </div>
        <?php else: ?>
        <!-- Projects Grid Campaign -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projectsByType['campaign'] as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="block">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-blue-600 dark:hover:text-blue-400">
                                    <?= e($project['name']) ?>
                                </h3>
                            </a>
                            <?php if (!empty($project['description'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ml-2
                            <?php if ($project['status'] === 'completed'): ?>
                            bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                            <?php else: ?>
                            bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300
                            <?php endif; ?>
                        ">
                            <?= ucfirst($project['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_runs'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Esecuzioni</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_campaigns'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Campagne</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($project['total_evaluations'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Valutazioni</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/edit') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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

        <!-- Info Box Campaign -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-sm p-6 text-white mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Analisi Campagne</h3>
                    <p class="text-blue-100 text-sm mt-1">Raccogli dati e valuta le performance con AI</p>
                </div>
                <div class="hidden md:flex gap-6">
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">1</span>
                        </div>
                        <p class="text-xs text-blue-200">Configura Script</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">2</span>
                        </div>
                        <p class="text-xs text-blue-200">Esegui in Google Ads</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">3</span>
                        </div>
                        <p class="text-xs text-blue-200">Ricevi dati</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">4</span>
                        </div>
                        <p class="text-xs text-blue-200">Valutazione AI</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
