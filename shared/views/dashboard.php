<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour-welcome="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Benvenuto, <?= htmlspecialchars($user['name'] ?? 'Utente') ?>!</p>
        </div>
        <?php if (isset($_GET['upgrade'])): ?>
        <a href="#plans" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
            Aumenta crediti
        </a>
        <?php endif; ?>
    </div>

    <!-- Warning crediti bassi -->
    <?php $_dashCredits = (float)($user['credits'] ?? 0); ?>
    <?php if ($_dashCredits < 10): ?>
    <div class="rounded-xl border <?= $_dashCredits < 3 ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20' : 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20' ?> px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0 <?= $_dashCredits < 3 ? 'text-red-500' : 'text-amber-500' ?>" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <p class="text-sm <?= $_dashCredits < 3 ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300' ?>">
            <?php if ($_dashCredits < 3): ?>
                Crediti quasi esauriti (<strong><?= number_format($_dashCredits, 1) ?></strong> disponibili). La maggior parte delle operazioni non sara disponibile.
            <?php else: ?>
                I tuoi crediti stanno per esaurirsi (<strong><?= number_format($_dashCredits, 1) ?></strong> disponibili). Alcune operazioni potrebbero non essere disponibili.
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Crediti disponibili -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6" data-tour-welcome="credits">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Crediti disponibili</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= number_format((float)($user['credits'] ?? 0), 1) ?>
            </dd>
        </div>

        <!-- Crediti usati oggi -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Usati oggi</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= number_format($usageToday ?? 0, 1) ?>
            </dd>
        </div>

        <!-- Crediti usati questo mese -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Usati questo mese</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= number_format($usageMonth ?? 0, 1) ?>
            </dd>
        </div>

        <!-- Progetti -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">I tuoi progetti</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= $projectsCount ?? 0 ?>
            </dd>
        </div>
    </div>

    <!-- Guida rapida (onboarding) -->
    <?php
    $completedModules = $onboardingCompletedModules ?? [];
    $onbConfig = $onboardingConfig ?? [];
    $modulesSlugs = array_column($modules ?? [], 'slug');
    $pendingTours = array_diff($modulesSlugs, $completedModules);
    ?>
    <?php if (!empty($pendingTours) && !empty($modules)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Guida rapida</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($completedModules) ?>/<?= count($modulesSlugs) ?> completati</span>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($modules as $module):
                $slug = $module['slug'];
                if (!isset($onbConfig[$slug])) continue;
                $isCompleted = in_array($slug, $completedModules);
                $tourName = $onbConfig[$slug]['name'] ?? $module['name'];
                $stepCount = count($onbConfig[$slug]['steps'] ?? []);
            ?>
            <div class="rounded-lg border <?= $isCompleted ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800' ?> p-4 flex items-center gap-3">
                <div class="flex-shrink-0">
                    <?php if ($isCompleted): ?>
                    <div class="h-9 w-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <?php else: ?>
                    <div class="h-9 w-9 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium <?= $isCompleted ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-900 dark:text-white' ?>"><?= e($tourName) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= $isCompleted ? 'Tour completato' : $stepCount . ' step da scoprire' ?></p>
                </div>
                <a href="<?= url('/' . $slug) ?>" class="flex-shrink-0 text-xs font-medium <?= $isCompleted ? 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700' : 'text-primary-600 dark:text-primary-400 hover:text-primary-700' ?> transition-colors">
                    <?= $isCompleted ? 'Apri' : 'Inizia' ?>
                    <span aria-hidden="true"> &rarr;</span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mappe icone e colori per moduli (usate in piu sezioni) -->
    <?php
    $moduleIcons = [
        'ai-content' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'seo-audit' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'seo-tracking' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'keyword-research' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        'ads-analyzer' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'internal-links' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'content-creator' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
    ];
    $defaultIcon = 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';
    $moduleColors = [
        'ai-content' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        'seo-audit' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
        'seo-tracking' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        'keyword-research' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        'ads-analyzer' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
        'internal-links' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
        'content-creator' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    ];
    $defaultColor = 'bg-primary-100 dark:bg-primary-900 text-primary-600';
    ?>

    <!-- Cosa vuoi fare? (guida task-oriented per nuovi utenti) -->
    <?php if (($projectsCount ?? 0) < 3): ?>
    <?php
    $taskGuides = [
        'ai-content' => ['task' => 'Scrivere articoli SEO con AI', 'desc' => 'Genera articoli completi con analisi SERP, brief strategico e pubblicazione WordPress'],
        'seo-tracking' => ['task' => 'Monitorare le posizioni su Google', 'desc' => 'Traccia keyword, rileva variazioni e integra Google Search Console'],
        'keyword-research' => ['task' => 'Trovare nuove keyword', 'desc' => 'Ricerca keyword con AI, clustering semantico e piano editoriale'],
        'seo-audit' => ['task' => 'Analizzare problemi tecnici SEO', 'desc' => 'Audit completo del sito con punteggio salute e piano d\'azione AI'],
        'ads-analyzer' => ['task' => 'Ottimizzare le campagne Google Ads', 'desc' => 'Analisi campagne, valutazione AI e keyword negative automatiche'],
    ];
    ?>
    <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Cosa vuoi fare?</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($modules as $module):
                $slug = $module['slug'];
                if (!isset($taskGuides[$slug])) continue;
                $guide = $taskGuides[$slug];
                $iconPath = $moduleIcons[$slug] ?? $defaultIcon;
                $colorClass = $moduleColors[$slug] ?? $defaultColor;
            ?>
            <a href="<?= url('/' . $slug) ?>" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 hover:border-primary-400 hover:ring-1 hover:ring-primary-400 transition-all group">
                <div class="flex items-start gap-3">
                    <div class="h-9 w-9 rounded-lg <?= $colorClass ?> flex items-center justify-center flex-shrink-0">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $iconPath ?>"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"><?= $guide['task'] ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2"><?= $guide['desc'] ?></p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Moduli disponibili -->
    <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4" data-tour-welcome="modules">Moduli disponibili</h2>
        <?php if (empty($modules)): ?>
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nessun modulo attivo</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">I moduli SEO saranno disponibili presto.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($modules as $module):
                $slug = $module['slug'];
                $iconPath = $moduleIcons[$slug] ?? $defaultIcon;
                $colorClass = $moduleColors[$slug] ?? $defaultColor;
            ?>
            <a href="<?= \Core\Router::url('/' . $slug) ?>" class="relative rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-4 shadow-sm flex items-center space-x-3 hover:border-primary-400 hover:ring-1 hover:ring-primary-400 transition-all">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg <?= $colorClass ?> flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $iconPath ?>"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($module['name']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($module['description'] ?? '') ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Utilizzo recente -->
    <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Utilizzo recente</h2>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <?php if (empty($recentUsage)): ?>
            <div class="text-center py-8">
                <p class="text-sm text-gray-500 dark:text-gray-400">Nessuna attivita recente</p>
            </div>
            <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Azione</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modulo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Crediti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recentUsage as $usage): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($usage['action']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($usage['module_slug'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= number_format($usage['credits_used'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= date('d/m/Y H:i', strtotime($usage['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
