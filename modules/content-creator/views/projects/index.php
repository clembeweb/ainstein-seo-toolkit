<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Content Creator</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera massivamente Meta Title, Meta Description e Page Description con AI</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <a href="<?= url('/content-creator/connectors') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Connettori CMS">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </a>
            <a href="<?= url('/content-creator/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Crea il tuo primo progetto per iniziare a generare contenuti SEO ottimizzati per le tue pagine.
        </p>
        <a href="<?= url('/content-creator/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea primo progetto
        </a>
    </div>
    <?php else: ?>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project):
            $contentTypes = [
                'product' => ['label' => 'Prodotto', 'color' => 'teal'],
                'category' => ['label' => 'Categoria', 'color' => 'blue'],
                'article' => ['label' => 'Articolo', 'color' => 'purple'],
                'custom' => ['label' => 'Custom', 'color' => 'slate'],
            ];
            $ct = $contentTypes[$project['content_type'] ?? 'product'] ?? $contentTypes['product'];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <a href="<?= url('/content-creator/projects/' . $project['id']) ?>" class="block">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">
                                <?= e($project['name']) ?>
                            </h3>
                        </a>
                        <?php if (!empty($project['description'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['description']) ?></p>
                        <?php else: ?>
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic mt-1">
                            <?= e($project['language'] ?? 'it') ?> / <?= e($project['tone'] ?? 'professionale') ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-<?= $ct['color'] ?>-100 text-<?= $ct['color'] ?>-700 dark:bg-<?= $ct['color'] ?>-900/50 dark:text-<?= $ct['color'] ?>-300">
                        <?= $ct['label'] ?>
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 divide-x divide-slate-200 dark:divide-slate-700">
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['total_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">URL</p>
                </div>
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= number_format($project['scraped_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Scrappate</p>
                </div>
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-purple-600 dark:text-purple-400"><?= number_format($project['generated_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Generate</p>
                </div>
                <div class="p-3 text-center">
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($project['approved_urls'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Approvate</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <?php if (($project['error_urls'] ?? 0) > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                            <?= $project['error_urls'] ?> errori
                        </span>
                        <?php elseif (($project['published_urls'] ?? 0) > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                            <?= $project['published_urls'] ?> pubblicati
                        </span>
                        <?php else: ?>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Creato: <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="<?= url('/content-creator/projects/' . $project['id']) ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </a>
                        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Workflow Content Creator</h3>
                <p class="text-teal-100 text-sm mt-1">Genera contenuti SEO ottimizzati in pochi click</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">1</span>
                    </div>
                    <p class="text-xs text-teal-200">Importa URL</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">2</span>
                    </div>
                    <p class="text-xs text-teal-200">Scrape</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">3</span>
                    </div>
                    <p class="text-xs text-teal-200">Genera AI</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">4</span>
                    </div>
                    <p class="text-xs text-teal-200">Approva</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">5</span>
                    </div>
                    <p class="text-xs text-teal-200">Pubblica</p>
                </div>
            </div>
        </div>
    </div>
</div>
