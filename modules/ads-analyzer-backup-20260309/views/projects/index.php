<?php $allProjects = array_merge($campaignProjects ?? [], $creatorProjects ?? []); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Progetti</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci le tue analisi e campagne Google Ads</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Tab Filtro -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="<?= url('/ads-analyzer/projects') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= !$currentType ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
            Tutti
        </a>
        <a href="<?= url('/ads-analyzer/projects?type=campaign') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentType === 'campaign' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
            Analisi Campagne
        </a>
        <a href="<?= url('/ads-analyzer/projects?type=campaign-creator') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentType === 'campaign-creator' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' ?>">
            Crea Campagna AI
        </a>
    </div>

    <?php
        $showCreator = !$currentType || $currentType === 'campaign-creator';
        $showCampaign = !$currentType || $currentType === 'campaign';
        $visibleProjects = [];
        if ($showCreator) $visibleProjects = array_merge($visibleProjects, $creatorProjects ?? []);
        if ($showCampaign) $visibleProjects = array_merge($visibleProjects, $campaignProjects ?? []);
    ?>

    <?php if (empty($visibleProjects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto<?= $currentType === 'campaign' ? ' di analisi campagne' : ($currentType === 'campaign-creator' ? ' di creazione campagne' : '') ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Crea il tuo primo progetto: analizza campagne esistenti o genera una campagna completa con AI.
        </p>
        <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea il primo progetto
        </a>
    </div>
    <?php else: ?>

    <!-- Campaign Creator Projects -->
    <?php if ($showCreator && !empty($creatorProjects)): ?>
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
            <div class="h-6 w-6 rounded bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            Crea Campagna AI
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($creatorProjects as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaign-creator') ?>" class="block">
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white truncate hover:text-amber-600 dark:hover:text-amber-400">
                                    <?= e($project['name']) ?>
                                </h3>
                            </a>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                    <?= ($project['campaign_type_gads'] ?? 'search') === 'pmax' ? 'PMax' : 'Search' ?>
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                    <?php if ($project['status'] === 'completed'): ?>
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                                    <?php else: ?>
                                    bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
                                    <?php endif; ?>
                                ">
                                    <?= ucfirst($project['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 divide-x divide-slate-200 dark:divide-slate-700 border-t border-slate-200 dark:border-slate-700">
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['kw_generations'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">KW Research</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400"><?= number_format($project['campaigns_generated'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Campagne</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                        </span>
                        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaign-creator') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Apri wizard">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Campaign Analysis Projects -->
    <?php if ($showCampaign && !empty($campaignProjects)): ?>
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
            <div class="h-6 w-6 rounded bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            Analisi Campagne
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($campaignProjects as $project): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="block">
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white truncate hover:text-blue-600 dark:hover:text-blue-400">
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
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['total_runs'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Esecuzioni</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['total_campaigns'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Campagne</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= number_format($project['total_evaluations'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Valutazioni</p>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                        </span>
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
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
