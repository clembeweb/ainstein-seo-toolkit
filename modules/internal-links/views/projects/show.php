<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/internal-links') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Internal Links</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white"><?= e($project['name']) ?></span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($project['base_url']) ?></p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <a href="<?= url('/internal-links/project/' . $project['id'] . '/settings') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Impostazioni
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="<?= url('/internal-links/project/' . $project['id'] . '/urls') ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow text-center">
            <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Gestisci URL</p>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= number_format($project['total_urls']) ?> URL</p>
        </a>

        <a href="<?= url('/internal-links/project/' . $project['id'] . '/scrape') ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow text-center">
            <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Scraping</p>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= number_format($scrapingProgress['progress'], 0) ?>% completato</p>
        </a>

        <a href="<?= url('/internal-links/project/' . $project['id'] . '/links') ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow text-center">
            <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Link Interni</p>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= number_format($project['internal_links']) ?> link</p>
        </a>

        <a href="<?= url('/internal-links/project/' . $project['id'] . '/analysis') ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow text-center">
            <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Analisi AI</p>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= number_format($project['analyzed_links']) ?> analizzati</p>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">URL Totali</p>
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_urls']) ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= number_format($project['scraped_urls']) ?> scrapped, <?= number_format($project['error_urls']) ?> errori</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Link Interni</p>
                <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($project['internal_links']) ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= number_format($project['external_links']) ?> esterni</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Score Medio</p>
                <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= $project['avg_relevance_score'] ? number_format($project['avg_relevance_score'], 1) : '-' ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= number_format($project['analyzed_links']) ?> link analizzati</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pagine Orfane</p>
                <div class="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($orphanCount) ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                <a href="<?= url('/internal-links/project/' . $project['id'] . '/orphans') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Vedi dettagli</a>
            </p>
        </div>
    </div>

    <!-- Score Distribution -->
    <?php if (!empty($scoreDistribution) && array_sum($scoreDistribution) > 0): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Score</h3>
            <div class="space-y-3">
                <?php
                $total = array_sum($scoreDistribution);
                $colors = [
                    'high' => 'bg-emerald-500',
                    'medium' => 'bg-amber-500',
                    'low' => 'bg-red-500',
                    'unanalyzed' => 'bg-slate-300 dark:bg-slate-600',
                ];
                $labels = [
                    'high' => 'Alto (7-10)',
                    'medium' => 'Medio (4-6)',
                    'low' => 'Basso (1-3)',
                    'unanalyzed' => 'Non analizzati',
                ];
                foreach (['high', 'medium', 'low', 'unanalyzed'] as $key):
                    $value = $scoreDistribution[$key] ?? 0;
                    $percent = $total > 0 ? ($value / $total) * 100 : 0;
                ?>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-slate-600 dark:text-slate-400"><?= $labels[$key] ?></span>
                        <span class="font-medium text-slate-900 dark:text-white"><?= number_format($value) ?> (<?= number_format($percent, 1) ?>%)</span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full <?= $colors[$key] ?> rounded-full" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Juice Flow</h3>
            <div class="space-y-3">
                <?php
                $juiceTotal = array_sum($juiceDistribution);
                $juiceColors = [
                    'optimal' => 'bg-emerald-500',
                    'good' => 'bg-blue-500',
                    'weak' => 'bg-amber-500',
                    'poor' => 'bg-red-500',
                    'orphan' => 'bg-slate-500',
                    'unanalyzed' => 'bg-slate-300 dark:bg-slate-600',
                ];
                $juiceLabels = [
                    'optimal' => 'Ottimale',
                    'good' => 'Buono',
                    'weak' => 'Debole',
                    'poor' => 'Scarso',
                    'orphan' => 'Orfano',
                    'unanalyzed' => 'Non analizzato',
                ];
                foreach (['optimal', 'good', 'weak', 'poor', 'unanalyzed'] as $key):
                    $value = $juiceDistribution[$key] ?? 0;
                    $percent = $juiceTotal > 0 ? ($value / $juiceTotal) * 100 : 0;
                ?>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-slate-600 dark:text-slate-400"><?= $juiceLabels[$key] ?></span>
                        <span class="font-medium text-slate-900 dark:text-white"><?= number_format($value) ?> (<?= number_format($percent, 1) ?>%)</span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full <?= $juiceColors[$key] ?> rounded-full" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <?php if (!empty($recentActivity)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Attivita Recente</h3>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($recentActivity as $activity): ?>
            <div class="px-6 py-3 flex items-center gap-3">
                <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-900 dark:text-white"><?= e(str_replace('_', ' ', ucfirst($activity['action']))) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
