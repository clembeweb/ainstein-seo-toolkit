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
