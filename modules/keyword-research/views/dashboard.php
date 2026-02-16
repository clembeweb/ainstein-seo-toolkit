<?php
// Onboarding tour
$onboardingConfig = require BASE_PATH . '/config/onboarding.php';
$onboardingModuleSlug = 'keyword-research';
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

<div class="space-y-8">
    <!-- Header -->
    <div data-tour="kr-header">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Keyword Research</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ricerca keyword potenziata da AI con clustering semantico e architettura sito</p>
    </div>

    <!-- 4 Mode Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Research Guidata -->
        <div data-tour="kr-guided" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Research Guidata</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Progetti SEO/Ads con clustering AI. Parti da seed keyword, espandi automaticamente e ottieni cluster semantici con intent e note strategiche.
                </p>
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                        2-5 crediti
                    </span>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/keyword-research/projects?type=research') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vai ai progetti
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Architettura Sito -->
        <div data-tour="kr-architecture" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Architettura Sito</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Struttura URL e slug per un nuovo sito. L'AI propone pagine, H1 e URL basati sui volumi di ricerca reali.
                </p>
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        5 crediti
                    </span>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/keyword-research/projects?type=architecture') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vai ai progetti
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Piano Editoriale -->
        <div data-tour="kr-editorial" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Piano Editoriale</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Piano editoriale mensile basato su keyword e analisi competitor. Esporta direttamente in AI Content.
                </p>
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300">
                        5 crediti
                    </span>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/keyword-research/projects?type=editorial') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vai ai progetti
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Quick Check -->
        <div data-tour="kr-quickcheck" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Quick Check</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Check istantaneo di una singola keyword. Ottieni volume, CPC, competition e correlate senza creare un progetto.
                </p>
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                        Gratis
                    </span>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/keyword-research/quick-check') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Cerca keyword
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <?php if (!empty($recentProjects)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Progetti recenti</h2>
            <a href="<?= url('/keyword-research/projects') ?>" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                Vedi tutti
                <svg class="w-4 h-4 ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($recentProjects as $project):
                    $tc = $typeConfigs[$project['type'] ?? 'research'] ?? $typeConfigs['research'];
                ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br <?= $tc['gradient'] ?> flex items-center justify-center shadow-sm">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tc['icon'] ?>"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-medium text-slate-900 dark:text-white"><?= e($project['name']) ?></h4>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $tc['badge_bg'] ?>">
                                    <?= $tc['label'] ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-0.5">
                                <?php if (($project['total_clusters'] ?? 0) > 0): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= $project['total_clusters'] ?> cluster</span>
                                <?php endif; ?>
                                <?php if (($project['total_keywords'] ?? 0) > 0): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= $project['total_keywords'] ?> kw</span>
                                <?php endif; ?>
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    <?= date('d/m/Y', strtotime($project['updated_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <a href="<?= url('/keyword-research/project/' . $project['id'] . '/' . $tc['route_segment']) ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
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

    <!-- Stats -->
    <?php if ($stats['total_projects'] > 0): ?>
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_projects']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Progetti</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_researches']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ricerche</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['total_clusters']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cluster</p>
        </div>
    </div>
    <?php endif; ?>
</div>
