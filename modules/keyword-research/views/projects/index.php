<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white"><?= $currentType ? $typeConfig['label'] : 'Progetti' ?></span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                <?= $currentType ? 'Progetti ' . $typeConfig['label'] : 'Tutti i Progetti' ?>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= $currentType ? 'Gestisci i tuoi progetti di ' . strtolower($typeConfig['label']) : 'Gestisci tutti i tuoi progetti di keyword research' ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Tab Filtro -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="<?= url('/keyword-research/projects') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= !$currentType ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
            Tutti
        </a>
        <?php foreach ($allTypeConfigs as $typeKey => $tc): ?>
        <a href="<?= url('/keyword-research/projects?type=' . $typeKey) ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentType === $typeKey ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
            <?= $tc['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($projects)): ?>
    <!-- Empty State -->
    <?php
        $emptyTc = $currentType ? $typeConfig : $allTypeConfigs['research'];
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-br <?= $emptyTc['gradient'] ?> bg-opacity-20 flex items-center justify-center mb-4" style="background: linear-gradient(135deg, rgba(0,0,0,0.05), rgba(0,0,0,0.1))">
            <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $emptyTc['icon'] ?>"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun progetto<?= $currentType ? ' di ' . strtolower($typeConfig['label']) : '' ?></h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            <?php if ($currentType): ?>
            Crea il tuo primo progetto di <?= strtolower($typeConfig['label']) ?> per iniziare.
            <?php else: ?>
            Crea il tuo primo progetto per iniziare una keyword research potenziata da AI.
            <?php endif; ?>
        </p>
        <div class="mt-6">
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project):
            $tc = $allTypeConfigs[$project['type'] ?? 'research'] ?? $allTypeConfigs['research'];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br <?= $tc['gradient'] ?> flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tc['icon'] ?>"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($project['name']) ?></h3>
                            <?php if (!$currentType): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $tc['badge_bg'] ?>">
                                <?= $tc['label'] ?>
                            </span>
                            <?php elseif (!empty($project['description'])): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[180px]"><?= e($project['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['researches_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Ricerche</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2">
                        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($project['total_clusters'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cluster</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['total_keywords'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Keywords</p>
                    </div>
                </div>

                <!-- Location Badge -->
                <div class="mt-3 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= e($project['default_location'] ?? 'IT') ?> / <?= e($project['default_language'] ?? 'it') ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                </span>
                <a href="<?= url('/keyword-research/project/' . $project['id'] . '/' . $tc['route_segment']) ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Apri
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
