<?php
// Onboarding tour
$onboardingConfig = require BASE_PATH . '/config/onboarding.php';
$onboardingModuleSlug = 'ads-analyzer';
$showTour = !\Core\OnboardingService::isModuleCompleted($user['id'] ?? 0, $onboardingModuleSlug);
if ($showTour && isset($onboardingConfig[$onboardingModuleSlug])):
    $onboardingSteps = $onboardingConfig[$onboardingModuleSlug]['steps'];
    $onboardingModuleName = $onboardingConfig[$onboardingModuleSlug]['name'];
    echo \Core\View::partial('components/onboarding-spotlight', [
        'onboardingSteps' => $onboardingSteps,
        'onboardingModuleName' => $onboardingModuleName,
        'onboardingModuleSlug' => $onboardingModuleSlug,
    ]);
endif;
?>

<!-- Hero Value Block -->
<div class="mb-6">
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'Le tue campagne Google Ads, analizzate e create dall\'AI',
    'description' => 'Carica i dati delle tue campagne per scoprire dove stai sprecando budget. Oppure crea una campagna completa da zero, pronta da importare in Google Ads Editor.',
    'color' => 'rose',
    'badge' => 'Come funziona',
    'storageKey' => 'ainstein_hero_ads_analyzer',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>', 'title' => 'Importa', 'subtitle' => 'Dati da Google Ads'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>', 'title' => 'Analizza', 'subtitle' => 'AI valuta performance'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>', 'title' => 'Ottimizza', 'subtitle' => 'Suggerimenti AI'],
    ],
    'ctaText' => 'Nuovo Progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>
</div>

<div class="space-y-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="aa-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Google Ads Analyzer</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analizza keyword negative e valuta campagne Google Ads con AI</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3" data-tour="aa-newproject">
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Mode Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl" data-tour="aa-modes">
        <?= \Core\View::partial('components/dashboard-mode-card', [
            'title' => 'Analisi Campagne',
            'description' => 'Collega Google Ads Script, raccogli dati sulle campagne, ottieni valutazioni AI e analizza keyword negative.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            'gradient' => 'from-blue-500 to-indigo-600',
            'url' => url('/projects/create'),
            'ctaText' => 'Nuovo progetto',
            'cost' => '10 cr/valutazione',
            'costColor' => 'amber',
            'badge' => ($stats['campaign_count'] > 0) ? $stats['campaign_count'] . ' progetti' : null,
        ]) ?>

        <?= \Core\View::partial('components/dashboard-mode-card', [
            'title' => 'Crea Campagna AI',
            'description' => 'Genera da zero una campagna Google Ads completa (Search o PMax) con keyword, copy ed estensioni.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
            'gradient' => 'from-amber-500 to-orange-600',
            'url' => url('/projects/create'),
            'ctaText' => 'Nuovo progetto',
            'cost' => '~14 cr totali',
            'costColor' => 'amber',
            'badge' => ($stats['creator_count'] > 0) ? $stats['creator_count'] . ' progetti' : null,
        ]) ?>
    </div>

    <!-- Progetti recenti -->
    <?php if (!empty($recentProjects)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Progetti recenti</h2>
            <a href="<?= url('/ads-analyzer/projects') ?>" class="text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700">
                Vedi tutti
                <svg class="w-4 h-4 ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($recentProjects as $project):
                    if (($project['type'] ?? 'negative-kw') === 'negative-kw') continue;
                    $isCreator = $project['type'] === 'campaign-creator';
                    $typeConfig = $isCreator ? [
                        'gradient' => 'from-amber-500 to-orange-600',
                        'badge_bg' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                        'label' => 'Creator',
                        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                    ] : [
                        'gradient' => 'from-blue-500 to-indigo-600',
                        'badge_bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                        'label' => 'Campagne',
                        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    ];
                    $projectUrl = url('/ads-analyzer/projects/' . $project['id']);
                ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br <?= $typeConfig['gradient'] ?> flex items-center justify-center shadow-sm">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $typeConfig['icon'] ?>"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-slate-900 dark:text-white"><?= e($project['name']) ?></h4>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $typeConfig['badge_bg'] ?>">
                                    <?= $typeConfig['label'] ?>
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                    <?php if ($project['status'] === 'completed'): ?>
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                                    <?php elseif ($project['status'] === 'analyzing'): ?>
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                                    <?php else: ?>
                                    bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
                                    <?php endif; ?>
                                ">
                                    <?= ucfirst($project['status']) ?>
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    <?= date('d/m/Y', strtotime($project['updated_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <a href="<?= $projectUrl ?>" class="inline-flex items-center text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700">
                        Apri
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats globali -->
    <?php if ($stats['total_projects'] > 0): ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_projects']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Progetti Totali</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_terms']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Termini Analizzati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['total_negatives']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Negative Trovate</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['total_evaluations']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Valutazioni AI</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Separator -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-rose-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- Hero Educativo -->
<div class="bg-gradient-to-br from-rose-50/50 via-white to-amber-50/30 dark:from-rose-950/20 dark:via-slate-900 dark:to-amber-950/10 rounded-2xl border border-rose-200/50 dark:border-rose-800/30 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <!-- Left: Text -->
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                Google Ads Analyzer
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white mt-4">Analizza le tue campagne Google Ads e scopri dove stai sprecando budget</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">Due strumenti in uno: importa i dati delle campagne esistenti per scoprire keyword negative, sprechi di budget e aree di miglioramento. Oppure crea campagne Google Ads complete da zero con l'AI — struttura, keyword, copy, estensioni — pronte da importare in Google Ads Editor.</p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-rose-500 text-white font-semibold hover:bg-rose-600 shadow-lg shadow-rose-500/25 transition-all mt-8">
                Inizia l'analisi
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- Right: Campaign Analysis Mockup -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Analisi Campagna</span>
                </div>
                <!-- Score section -->
                <div class="p-5">
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative w-24 h-24">
                            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                                <path class="text-slate-200 dark:text-slate-700" d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"/>
                                <path class="text-amber-500" d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="75, 100"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">7.5</span>
                                <span class="text-[10px] text-slate-400">/10</span>
                            </div>
                        </div>
                    </div>
                    <!-- Mini score bars -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Struttura</span>
                            <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 80%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300 w-8 text-right">8/10</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Copy</span>
                            <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: 60%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300 w-8 text-right">6/10</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Targeting</span>
                            <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 80%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300 w-8 text-right">8/10</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Budget</span>
                            <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: 70%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300 w-8 text-right">7/10</span>
                        </div>
                    </div>
                </div>
                <!-- Bottom stats -->
                <div class="px-4 py-3 bg-rose-50 dark:bg-rose-900/20 border-t border-rose-200 dark:border-rose-800/30 flex items-center justify-between">
                    <span class="text-xs text-rose-600 dark:text-rose-300 font-medium">23 keyword negative trovate</span>
                    <span class="text-xs text-rose-500 dark:text-rose-400">3 suggerimenti</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dall'importazione dei dati all'ottimizzazione delle campagne in 3 passaggi</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center text-sm font-bold shadow-md">1</div>
            <div class="h-12 w-12 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Importa o crea</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Importa i dati delle campagne esistenti tramite Google Ads Script, oppure parti da zero e crea una campagna completa con l'AI. Scegli la modalita piu adatta: analisi delle campagne attive o creazione di nuove campagne.</p>
        </div>

        <!-- Step 2 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center text-sm font-bold shadow-md">2</div>
            <div class="h-12 w-12 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Analisi AI</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">L'AI valuta ogni aspetto della campagna: struttura dei gruppi di annunci, efficacia del copy, targeting, keyword negative mancanti e distribuzione del budget. Per la creazione, genera struttura ottimizzata in base alla keyword.</p>
        </div>

        <!-- Step 3 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center text-sm font-bold shadow-md">3</div>
            <div class="h-12 w-12 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Ottimizza o lancia</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Per le campagne esistenti: applica i suggerimenti AI per ottimizzare performance e ridurre sprechi. Per le campagne nuove: esporta in formato Google Ads Editor e importa direttamente nel tuo account.</p>
        </div>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: Valutazione AI (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Valutazione AI delle campagne esistenti</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Carica i dati delle tue campagne tramite Google Ads Script. L'AI analizza ogni aspetto: struttura dei gruppi di annunci, efficacia delle headline e description, keyword negative mancanti, distribuzione del budget tra campagne e gruppi. Ogni area riceve un punteggio e suggerimenti concreti di miglioramento.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-rose-600 dark:text-rose-400 font-medium hover:text-rose-700 dark:hover:text-rose-300 transition-colors">
                        Analizza una campagna
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Valutazione Campagna</span>
                        </div>
                        <div class="p-5">
                            <!-- Overall score -->
                            <div class="flex items-center justify-center mb-5">
                                <div class="text-center">
                                    <span class="text-4xl font-bold text-amber-600 dark:text-amber-400">7.5</span>
                                    <span class="text-lg text-slate-400">/10</span>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Punteggio complessivo</p>
                                </div>
                            </div>
                            <!-- Category rows -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Struttura</span>
                                    <div class="flex-1 h-2.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: 80%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 w-8 text-right">8/10</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Copy</span>
                                    <div class="flex-1 h-2.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: 60%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 w-8 text-right">6/10</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Targeting</span>
                                    <div class="flex-1 h-2.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: 80%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 w-8 text-right">8/10</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-600 dark:text-slate-300 w-20">Budget</span>
                                    <div class="flex-1 h-2.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: 70%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 w-8 text-right">7/10</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Keyword negative (visual LEFT, text RIGHT, slate bg) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Keyword negative che ti fanno sprecare budget</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        L'AI analizza i termini di ricerca delle tue campagne e identifica le query irrilevanti che consumano budget senza portare conversioni. Le keyword negative vengono organizzate per tema e puoi aggiungerle direttamente alle tue campagne. Esempio: per una campagna 'scarpe running', trova termini come <strong class="text-rose-600 dark:text-rose-400">'scarpe running usate'</strong>, <strong class="text-rose-600 dark:text-rose-400">'scarpe running bambino'</strong> che sprecano click.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-rose-600 dark:text-rose-400 font-medium hover:text-rose-700 dark:hover:text-rose-300 transition-colors">
                        Trova keyword negative
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Keyword Negative</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">23 trovate</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <!-- Row 1 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">scarpe running usate</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">€45.20</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">32 click</span>
                                <span class="text-[10px] text-red-500 w-10 text-right">0 conv</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium cursor-pointer">Aggiungi</span>
                            </div>
                            <!-- Row 2 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">scarpe running bambino</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">€28.50</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">18 click</span>
                                <span class="text-[10px] text-red-500 w-10 text-right">0 conv</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium cursor-pointer">Aggiungi</span>
                            </div>
                            <!-- Row 3 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">scarpe running riparazione</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">€12.80</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">8 click</span>
                                <span class="text-[10px] text-red-500 w-10 text-right">0 conv</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium cursor-pointer">Aggiungi</span>
                            </div>
                            <!-- Row 4 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">scarpe running wikipedia</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">€8.40</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">12 click</span>
                                <span class="text-[10px] text-red-500 w-10 text-right">0 conv</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium cursor-pointer">Aggiungi</span>
                            </div>
                        </div>
                        <!-- Bottom bar -->
                        <div class="px-4 py-3 bg-rose-50 dark:bg-rose-900/20 border-t border-rose-200 dark:border-rose-800/30 flex items-center justify-between">
                            <span class="text-xs text-rose-600 dark:text-rose-300 font-medium">Budget recuperabile: €94.90/mese</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Crea campagne AI (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Crea campagne Google Ads complete con AI</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Dalla keyword iniziale alla campagna pronta da importare. L'AI genera la struttura completa: gruppi di annunci tematici, keyword con match type ottimale, headline e description persuasive, estensioni sitelink e callout. Supporta campagne Search (keyword-based) e Performance Max (PMax). Esporta tutto in formato Google Ads Editor.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-rose-600 dark:text-rose-400 font-medium hover:text-rose-700 dark:hover:text-rose-300 transition-colors">
                        Crea una campagna
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Crea Campagna AI</span>
                        </div>
                        <div class="p-5 space-y-0">
                            <!-- Step 1 -->
                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">1</div>
                                    <div class="w-px h-8 bg-rose-300 dark:bg-rose-600 mt-1"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white">Keyword seed</p>
                                    <div class="mt-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">scarpe running donna</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 2 -->
                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">2</div>
                                    <div class="w-px h-8 bg-rose-300 dark:bg-rose-600 mt-1"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white">Struttura</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">3 gruppi di annunci generati</p>
                                </div>
                            </div>
                            <!-- Step 3 -->
                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">3</div>
                                    <div class="w-px h-8 bg-rose-300 dark:bg-rose-600 mt-1"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white">Copy</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">12 headline · 4 description</p>
                                </div>
                            </div>
                            <!-- Step 4 -->
                            <div class="flex items-start gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Export</p>
                                    <p class="text-[10px] text-emerald-500 dark:text-emerald-400 mt-1">Pronto per Google Ads Editor</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cosa puoi fare -->
    <div class="mt-20">
        <div class="text-center mb-12">
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con Ads Analyzer</h3>
            <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dall'analisi delle campagne esistenti alla creazione di nuove campagne ottimizzate</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Analisi campagne -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Analisi campagne</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Valutazione AI completa di struttura, copy, targeting e budget delle tue campagne Google Ads.</p>
            </div>
            <!-- Keyword negative -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Keyword negative</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Trova i termini di ricerca irrilevanti che sprecano budget senza portare conversioni.</p>
            </div>
            <!-- Crea campagne AI -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Crea campagne AI</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Genera campagne Search e Performance Max complete, dalla keyword alla struttura finale.</p>
            </div>
            <!-- Valutazione copy -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Valutazione copy</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Analisi AI di headline e description con suggerimenti per migliorare CTR e Quality Score.</p>
            </div>
            <!-- Export Google Ads -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Export Google Ads</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Esporta le campagne in formato compatibile con Google Ads Editor per importazione diretta.</p>
            </div>
            <!-- Budget optimization -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Budget optimization</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Identifica dove stai sprecando budget e come riallocare le risorse per massimizzare il ROI.</p>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div class="mt-20" x-data="{ openFaq: null }">
        <div class="text-center mb-12">
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Domande frequenti</h3>
        </div>
        <div class="max-w-3xl mx-auto space-y-3">
            <!-- FAQ 1 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 1 ? openFaq = null : openFaq = 1" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Quanti crediti costa l'analisi?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">La <strong>valutazione campagna</strong> costa 10 crediti. La <strong>creazione campagna AI</strong> costa circa 14 crediti totali (include generazione struttura, keyword, copy ed estensioni). L'analisi keyword negative e inclusa nella valutazione.</p>
                </div>
            </div>
            <!-- FAQ 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Come importo i dati da Google Ads?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Installa il <strong>Google Ads Script</strong> fornito nella sezione script del tuo account Google Ads. Lo script raccoglie i dati delle campagne (keyword, termini di ricerca, performance) e li invia automaticamente ad Ainstein per l'analisi.</p>
                </div>
            </div>
            <!-- FAQ 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 3 ? openFaq = null : openFaq = 3" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Che tipo di campagne posso creare?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Puoi creare campagne <strong>Search</strong> (basate su keyword con annunci testuali) e <strong>Performance Max</strong> (PMax). L'AI genera la struttura completa: gruppi di annunci, keyword con match type, headline, description e estensioni.</p>
                </div>
            </div>
            <!-- FAQ 4 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 4 ? openFaq = null : openFaq = 4" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso esportare le campagne create?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Si, ogni campagna creata viene generata in <strong>formato compatibile con Google Ads Editor</strong>. Puoi scaricare il file e importarlo direttamente nel tuo account Google Ads senza configurazione manuale.</p>
                </div>
            </div>
            <!-- FAQ 5 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 5 ? openFaq = null : openFaq = 5" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Come funziona l'analisi keyword negative?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 5 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">L'AI analizza i <strong>termini di ricerca</strong> delle tue campagne, identifica le query che generano click ma nessuna conversione, calcola il budget sprecato e genera liste di keyword negative organizzate per categoria tematica.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Finale -->
    <div class="mt-20 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-500 to-orange-500 p-8 lg:p-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Smetti di sprecare budget su click che non convertono</h3>
                    <p class="mt-2 text-rose-100">Analizza le campagne esistenti o crea campagne ottimizzate dall'AI</p>
                </div>
                <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-rose-600 font-semibold hover:bg-rose-50 shadow-lg transition-all flex-shrink-0">
                    Inizia ora
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
