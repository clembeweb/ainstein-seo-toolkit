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
<div x-data="{ show: !localStorage.getItem('ainstein_hero_ads_analyzer') }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mb-6">
    <div class="relative overflow-hidden rounded-xl border border-rose-500/20 bg-gradient-to-br from-rose-950 via-slate-900 to-slate-900">
        <div class="absolute top-0 right-0 w-64 h-64 bg-rose-500/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-rose-500/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>

        <div class="relative p-6 sm:p-8">
            <button @click="show = false; localStorage.setItem('ainstein_hero_ads_analyzer', '1')" class="absolute top-4 right-4 p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="max-w-3xl">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-500/20 text-rose-300 mb-3">
                    Come funziona
                </span>
                <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">Le tue campagne Google Ads, analizzate e create dall'AI</h2>
                <p class="text-slate-300 text-sm mb-6">Carica i dati delle tue campagne per scoprire dove stai sprecando budget. Oppure crea una campagna completa da zero, pronta da importare in Google Ads Editor.</p>

                <!-- Dual mode -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-8 w-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-white">Analisi</h3>
                        </div>
                        <p class="text-xs text-slate-400">Esamina ogni termine di ricerca, identifica pattern di spreco, trova le keyword negative nascoste e valuta la struttura delle campagne.</p>
                    </div>
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-8 w-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-white">Creazione</h3>
                        </div>
                        <p class="text-xs text-slate-400">Analizza la landing page, studia il mercato, seleziona le keyword migliori e crea una campagna completa pronta per Google Ads Editor.</p>
                    </div>
                </div>

                <a href="<?= url('/ads-analyzer/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-500 text-white text-sm font-medium hover:bg-rose-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nuovo Progetto
                </a>
            </div>
        </div>
    </div>
</div>

<div class="space-y-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="aa-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Google Ads Analyzer</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analizza keyword negative e valuta campagne Google Ads con AI</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3" data-tour="aa-newproject">
            <a href="<?= url('/ads-analyzer/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Mode Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl" data-tour="aa-modes">
        <!-- Analisi Campagne -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Analisi Campagne</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Collega Google Ads Script, raccogli dati sulle campagne, ottieni valutazioni AI e analizza keyword negative.
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        10 cr/valutazione
                    </span>
                    <?php if ($stats['campaign_count'] > 0): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= $stats['campaign_count'] ?> progetti</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/ads-analyzer/projects/create?type=campaign') ?>" class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700">
                    Nuovo progetto
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Campaign Creator -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Crea Campagna AI</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Genera da zero una campagna Google Ads completa (Search o PMax) con keyword, copy ed estensioni.
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                        ~14 cr totali
                    </span>
                    <?php if ($stats['creator_count'] > 0): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= $stats['creator_count'] ?> progetti</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/ads-analyzer/projects/create?type=campaign-creator') ?>" class="inline-flex items-center text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700">
                    Nuovo progetto
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
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

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
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
