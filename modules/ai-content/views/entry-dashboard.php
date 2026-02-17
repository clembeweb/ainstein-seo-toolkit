<?php
// Credit badge helper per costi dinamici
include_once BASE_PATH . '/shared/views/components/credit-badge.php';
$manualCost = ($creditCosts['serp'] ?? 3) + 3 + ($creditCosts['brief'] ?? 3) + ($creditCosts['article'] ?? 10);
$metaCost = 1 + ($creditCosts['brief'] ?? 3);

// Onboarding tour
$onboardingConfig = require BASE_PATH . '/config/onboarding.php';
$onboardingModuleSlug = 'ai-content';
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
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="aic-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Content Generator</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera articoli SEO, contenuti automatici e meta tag ottimizzati con intelligenza artificiale</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3" data-tour="aic-quickactions">
            <a href="<?= url('/ai-content/wordpress') ?>" class="inline-flex items-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9.004 9.004 0 008.354-5.646M12 21a9.004 9.004 0 01-8.354-5.646M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                </svg>
                WordPress
            </a>
            <a href="<?= url('/ai-content/projects/create') ?>" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- 3 Mode Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" data-tour="aic-modes">
        <!-- Articoli Manuali -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Articoli Manuali</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Controllo totale con wizard 4 step: keyword, SERP, Brief AI, Articolo. Modifica brief e contenuto prima di pubblicare.
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <?= credit_badge($manualCost) ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500">/articolo</span>
                    <?php if ($stats['manual_count'] > 0): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= $stats['manual_count'] ?> progetti</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/ai-content/projects?tab=manual') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vai ai progetti
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Articoli Automatici -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Articoli Automatici</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Elaborazione in bulk automatica. Aggiungi keyword alla coda e il sistema genera articoli uno dopo l'altro.
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <?= credit_badge($manualCost) ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500">/articolo</span>
                    <?php if ($stats['auto_count'] > 0): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= $stats['auto_count'] ?> progetti</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/ai-content/projects?tab=auto') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vai ai progetti
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- SEO Meta Tags -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm mb-4">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h.008v.008H6V6z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">SEO Meta Tags</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Scrapa pagine esistenti e genera title e description ottimizzati con AI. Pubblica direttamente su WordPress.
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <?= credit_badge($metaCost) ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500">/pagina</span>
                    <?php if ($stats['meta_count'] > 0): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= $stats['meta_count'] ?> progetti</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/ai-content/projects?tab=meta-tag') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vai ai progetti
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
            <a href="<?= url('/ai-content/projects') ?>" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                Vedi tutti
                <svg class="w-4 h-4 ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($recentProjects as $project):
                    // Colore e icona per tipo
                    $typeConfig = [
                        'manual' => [
                            'gradient' => 'from-blue-500 to-indigo-600',
                            'badge_bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'label' => 'Manuale',
                            'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
                        ],
                        'auto' => [
                            'gradient' => 'from-purple-500 to-purple-600',
                            'badge_bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                            'label' => 'Automatico',
                            'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
                        ],
                        'meta-tag' => [
                            'gradient' => 'from-emerald-500 to-teal-600',
                            'badge_bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'label' => 'Meta Tags',
                            'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z',
                        ],
                    ];
                    $cfg = $typeConfig[$project['type']] ?? $typeConfig['manual'];

                    // Link al progetto
                    $projectUrl = url('/ai-content/projects/' . $project['id']);
                ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br <?= $cfg['gradient'] ?> flex items-center justify-center shadow-sm">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $cfg['icon'] ?>"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-slate-900 dark:text-white"><?= e($project['name']) ?></h4>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $cfg['badge_bg'] ?>">
                                    <?= $cfg['label'] ?>
                                </span>
                                <?php if ($project['type'] === 'meta-tag' && isset($project['meta_tags_count'])): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= $project['meta_tags_count'] ?> URL</span>
                                <?php else: ?>
                                    <?php if (($project['keywords_count'] ?? 0) > 0): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= $project['keywords_count'] ?> kw</span>
                                    <?php endif; ?>
                                    <?php if (($project['articles_count'] ?? 0) > 0): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= $project['articles_count'] ?> articoli</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    <?= date('d/m/Y', strtotime($project['updated_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <a href="<?= $projectUrl ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
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
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" data-tour="aic-stats">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_keywords']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Keyword</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_articles']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Articoli</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['published']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pubblicati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_words']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Parole generate</p>
        </div>
    </div>
    <?php endif; ?>
</div>
