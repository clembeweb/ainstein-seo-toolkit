<?php
// Onboarding tour
$onboardingConfig = require BASE_PATH . '/config/onboarding.php';
$onboardingModuleSlug = 'seo-tracking';
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
<div x-data="{ show: !localStorage.getItem('ainstein_hero_seo_tracking') }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mb-6">
    <div class="relative overflow-hidden rounded-xl border border-blue-500/20 bg-gradient-to-br from-blue-950 via-slate-900 to-slate-900">
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-500/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>

        <div class="relative p-6 sm:p-8">
            <button @click="show = false; localStorage.setItem('ainstein_hero_seo_tracking', '1')" class="absolute top-4 right-4 p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="max-w-3xl">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300 mb-3">
                    Come funziona
                </span>
                <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">Le tue posizioni su Google, con i dati reali</h2>
                <p class="text-slate-300 text-sm mb-6">Ainstein monitora le posizioni per le tue keyword e, collegando Google Search Console, incrocia i dati per mostrarti quanti click reali riceve ogni keyword, il CTR e le tendenze. Report periodici con insight e anomalie.</p>

                <!-- 3-step workflow -->
                <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-6">
                    <div class="text-center">
                        <div class="h-12 w-12 rounded-xl bg-blue-500/20 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <p class="text-xs font-medium text-white">Aggiungi keyword</p>
                        <p class="text-xs text-slate-400 mt-0.5">Le keyword da monitorare</p>
                    </div>
                    <div class="text-center">
                        <div class="h-12 w-12 rounded-xl bg-blue-500/20 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-white">Collega GSC</p>
                        <p class="text-xs text-slate-400 mt-0.5">Click e CTR reali</p>
                    </div>
                    <div class="text-center">
                        <div class="h-12 w-12 rounded-xl bg-blue-500/20 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-white">Report AI</p>
                        <p class="text-xs text-slate-400 mt-0.5">Insight e anomalie</p>
                    </div>
                </div>

                <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-500 text-white text-sm font-medium hover:bg-blue-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Crea il primo progetto
                </a>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="st-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">SEO Position Tracking</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Monitora posizioni, traffico e revenue dei tuoi siti web</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/create') ?>" data-tour="st-newproject" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Inizia a sapere dove sei su Google</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 max-w-md mx-auto">
            Crea un progetto, aggiungi le keyword e scopri le posizioni reali su Google.
        </p>
        <div class="flex flex-col items-center gap-3 max-w-sm mx-auto mb-6">
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Posizioni aggiornate automaticamente
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Click reali da Google Search Console
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Report settimanali AI con insight e anomalie
            </div>
        </div>
        <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea il primo progetto
        </a>
        <p class="mt-3">
            <a href="<?= url('/docs/seo-tracking') ?>" class="text-xs text-slate-400 hover:text-primary-500 transition-colors">Scopri di piu &rarr;</a>
        </p>
    </div>
    <?php else: ?>
    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <!-- Project Header -->
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/dashboard') ?>" class="block">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">
                                <?= e($project['name']) ?>
                            </h3>
                        </a>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1"><?= e($project['domain']) ?></p>
                    </div>
                    <div class="flex items-center gap-2 ml-2">
                        <?php if ($project['gsc_connected']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300" title="GSC Connesso">
                            GSC
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($project['keyword_count'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tracciate</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($project['checked_count'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Verificate</p>
                </div>
                <div class="p-4 text-center">
                    <?php
                    $avgPos = $project['avg_position'] ?? 0;
                    $posColor = $avgPos <= 10 ? 'text-emerald-600 dark:text-emerald-400' : ($avgPos <= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white');
                    ?>
                    <p class="text-xl font-bold <?= $posColor ?>"><?= $avgPos > 0 ? number_format($avgPos, 1) : '-' ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pos. Media</p>
                </div>
            </div>

            <!-- Sync Status -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <?php if ($project['sync_status'] === 'running'): ?>
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                        </span>
                        <span class="text-xs text-blue-600 dark:text-blue-400">Sync in corso...</span>
                        <?php elseif ($project['last_sync_at']): ?>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Ultimo sync: <?= date('d/m H:i', strtotime($project['last_sync_at'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Mai sincronizzato</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/dashboard') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Dashboard">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </a>
                        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/settings') ?>" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
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
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Come funziona</h3>
                <p class="text-blue-100 text-sm mt-1">Aggiungi keyword e verifica le posizioni sui motori di ricerca</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">1</span>
                    </div>
                    <p class="text-xs text-blue-200">Crea progetto</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">2</span>
                    </div>
                    <p class="text-xs text-blue-200">Aggiungi keyword</p>
                </div>
                <div class="text-center">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-1">
                        <span class="text-lg font-bold">3</span>
                    </div>
                    <p class="text-xs text-blue-200">Verifica posizioni</p>
                </div>
            </div>
        </div>
    </div>
</div>
