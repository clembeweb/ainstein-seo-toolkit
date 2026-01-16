<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Content Generator</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera articoli SEO ottimizzati con intelligenza artificiale</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/ai-content/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
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
        <div class="mx-auto h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Crea il tuo primo progetto per iniziare a generare articoli SEO con l'intelligenza artificiale.
        </p>
        <a href="<?= url('/ai-content/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
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

            <!-- Stats -->
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
                    <?php $published = $project['articles_published'] ?? 0; ?>
                    <p class="text-xl font-bold <?= $published > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' ?>">
                        <?= number_format($published) ?>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pubblicati</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <?php if ($project['articles_ready'] ?? 0 > 0): ?>
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

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Come funziona</h3>
                <p class="text-purple-100 text-sm mt-1">Genera articoli SEO ottimizzati in pochi click</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">1</span>
                    </div>
                    <p class="text-xs text-purple-200">Crea progetto</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">2</span>
                    </div>
                    <p class="text-xs text-purple-200">Aggiungi keyword</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">3</span>
                    </div>
                    <p class="text-xs text-purple-200">Genera articolo</p>
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
