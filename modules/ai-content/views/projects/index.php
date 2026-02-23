<div class="space-y-6" x-data="{ activeTab: '<?= e($activeTab) ?>' }">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="aic-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Content Generator</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera articoli SEO e meta tag ottimizzati con intelligenza artificiale</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3" data-tour="aic-quickactions">
            <!-- Quick Links -->
            <a href="<?= url('/ai-content/wordpress') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Siti WordPress">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                </svg>
            </a>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
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
            <!-- Manual Tab -->
            <button @click="activeTab = 'manual'"
                    :class="activeTab === 'manual' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Articoli Manuali
                <?php if (count($projectsByType['manual'] ?? []) > 0): ?>
                <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-0.5 px-2 rounded-full text-xs"><?= count($projectsByType['manual']) ?></span>
                <?php endif; ?>
            </button>

            <!-- Auto Tab -->
            <button @click="activeTab = 'auto'"
                    :class="activeTab === 'auto' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Articoli Automatici
                <?php if (count($projectsByType['auto'] ?? []) > 0): ?>
                <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-0.5 px-2 rounded-full text-xs"><?= count($projectsByType['auto']) ?></span>
                <?php endif; ?>
            </button>

            <!-- Meta-Tag Tab -->
            <button @click="activeTab = 'meta-tag'"
                    :class="activeTab === 'meta-tag' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                SEO Meta Tags
                <?php if (count($projectsByType['meta-tag'] ?? []) > 0): ?>
                <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-0.5 px-2 rounded-full text-xs"><?= count($projectsByType['meta-tag']) ?></span>
                <?php endif; ?>
            </button>
        </nav>
    </div>

    <!-- Tab Content: Manual -->
    <div x-show="activeTab === 'manual'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <?php if (empty($projectsByType['manual'])): ?>
        <!-- Empty State Manual -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto manuale</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                Crea un progetto manuale per generare articoli uno alla volta con controllo completo sul contenuto.
            </p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crea progetto manuale
            </a>
        </div>
        <?php else: ?>
        <!-- Projects Grid Manual -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projectsByType['manual'] as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ai-content/projects/' . $project['id']) ?>" class="block">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">
                                    <?= e($project['name']) ?>
                                </h3>
                            </a>
                            <?php if (!empty($project['description'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                            <?php else: ?>
                            <p class="text-sm text-slate-400 dark:text-slate-500 italic mt-1">
                                <?= e($project['default_language']) ?> / <?= e($project['default_location']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['keywords_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Keywords</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['articles_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Articoli</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold <?= ($project['articles_published'] ?? 0) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' ?>">
                            <?= number_format($project['articles_published'] ?? 0) ?>
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pubblicati</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <?php if (($project['articles_ready'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                <?= $project['articles_ready'] ?> da pubblicare
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Creato: <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="<?= url('/ai-content/projects/' . $project['id']) ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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

        <!-- Info Box Manual -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Modalita Manuale</h3>
                    <p class="text-blue-100 text-sm mt-1">Controllo completo su ogni articolo generato</p>
                </div>
                <div class="flex gap-6">
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">1</span>
                        </div>
                        <p class="text-xs text-blue-200">Aggiungi keyword</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">2</span>
                        </div>
                        <p class="text-xs text-blue-200">Analizza SERP</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">3</span>
                        </div>
                        <p class="text-xs text-blue-200">Genera articolo</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">4</span>
                        </div>
                        <p class="text-xs text-blue-200">Pubblica su WP</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content: Auto -->
    <div x-show="activeTab === 'auto'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <?php if (empty($projectsByType['auto'])): ?>
        <!-- Empty State Auto -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto automatico</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                Crea un progetto automatico per generare articoli in batch da una lista di keyword.
            </p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crea progetto automatico
            </a>
        </div>
        <?php else: ?>
        <!-- Projects Grid Auto -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projectsByType['auto'] as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto') ?>" class="block">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">
                                    <?= e($project['name']) ?>
                                </h3>
                            </a>
                            <?php if (!empty($project['description'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                            <?php else: ?>
                            <p class="text-sm text-slate-400 dark:text-slate-500 italic mt-1">
                                <?= e($project['default_language']) ?> / <?= e($project['default_location']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($project['queue_pending'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">In Coda</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['articles_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Generati</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xl font-bold <?= ($project['articles_published'] ?? 0) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' ?>">
                            <?= number_format($project['articles_published'] ?? 0) ?>
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pubblicati</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <?php if (($project['queue_processing'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <?= $project['queue_processing'] ?> in elaborazione
                            </span>
                            <?php elseif (($project['queue_pending'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                <?= $project['queue_pending'] ?> da elaborare
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Creato: <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </a>
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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

        <!-- Info Box Auto -->
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-6 text-white mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Modalita Automatica</h3>
                    <p class="text-purple-100 text-sm mt-1">Genera articoli in batch da una lista di keyword</p>
                </div>
                <div class="flex gap-6">
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">1</span>
                        </div>
                        <p class="text-xs text-purple-200">Carica keywords</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">2</span>
                        </div>
                        <p class="text-xs text-purple-200">Configura coda</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">3</span>
                        </div>
                        <p class="text-xs text-purple-200">Avvia processo</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">4</span>
                        </div>
                        <p class="text-xs text-purple-200">Pubblica su WP</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content: Meta-Tag -->
    <div x-show="activeTab === 'meta-tag'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <?php if (empty($projectsByType['meta-tag'])): ?>
        <!-- Empty State Meta-Tag -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto Meta Tags</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                Crea un progetto Meta Tags per generare title e description SEO ottimizzati per le tue pagine.
            </p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crea progetto Meta Tags
            </a>
        </div>
        <?php else: ?>
        <!-- Projects Grid Meta-Tag -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projectsByType['meta-tag'] as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/meta-tags') ?>" class="block">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">
                                    <?= e($project['name']) ?>
                                </h3>
                            </a>
                            <?php if (!empty($project['description'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                            <?php else: ?>
                            <p class="text-sm text-slate-400 dark:text-slate-500 italic mt-1">
                                SEO Meta Tags
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-4 divide-x divide-slate-200 dark:divide-slate-700">
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['urls_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">URL</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= number_format($project['urls_scraped'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Scrappate</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-purple-600 dark:text-purple-400"><?= number_format($project['urls_generated'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Generate</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($project['urls_published'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pubblicate</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <?php if (($project['urls_approved'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <?= $project['urls_approved'] ?> da pubblicare
                            </span>
                            <?php elseif (($project['urls_generated'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                <?= $project['urls_generated'] ?> da approvare
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Creato: <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/meta-tags') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </a>
                            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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

        <!-- Info Box Meta-Tag -->
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">SEO Meta Tags</h3>
                    <p class="text-emerald-100 text-sm mt-1">Genera title e description ottimizzati per la SERP</p>
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
                        <p class="text-xs text-emerald-200">Scrape contenuto</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">3</span>
                        </div>
                        <p class="text-xs text-emerald-200">Genera meta</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">4</span>
                        </div>
                        <p class="text-xs text-emerald-200">Approva</p>
                    </div>
                    <div class="text-center">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                            <span class="text-lg font-bold">5</span>
                        </div>
                        <p class="text-xs text-emerald-200">Pubblica WP</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
