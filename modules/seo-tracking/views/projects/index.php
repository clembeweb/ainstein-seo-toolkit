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
<div class="mb-6">
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'Le tue posizioni su Google, con i dati reali',
    'description' => 'Ainstein monitora le posizioni per le tue keyword e, collegando Google Search Console, incrocia i dati per mostrarti quanti click reali riceve ogni keyword, il CTR e le tendenze. Report periodici con insight e anomalie.',
    'color' => 'blue',
    'badge' => 'Come funziona',
    'storageKey' => 'ainstein_hero_seo_tracking',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>', 'title' => 'Aggiungi keyword', 'subtitle' => 'Le keyword da monitorare'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>', 'title' => 'Collega GSC', 'subtitle' => 'Click e CTR reali'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>', 'title' => 'Report AI', 'subtitle' => 'Insight e anomalie'],
    ],
    'ctaText' => 'Crea il primo progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>
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

<!-- ═══════════ SEZIONE EDUCATIVA ═══════════ -->

<!-- Separator -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-blue-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- Hero Educativo -->
<div class="bg-gradient-to-br from-blue-50/50 via-white to-indigo-50/30 dark:from-blue-950/20 dark:via-slate-900 dark:to-indigo-950/10 rounded-2xl border border-blue-200/50 dark:border-blue-800/30 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <!-- Left: Text -->
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                SEO Tracking
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white mt-4">Monitora le posizioni su Google e scopri come cambia la tua visibilita</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">Ainstein monitora le posizioni delle tue keyword su Google, le aggiorna automaticamente e, collegando Search Console, incrocia i dati per mostrarti click reali, impressioni e CTR per ogni keyword. Report settimanali AI con insight su trend, anomalie e opportunita.</p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-blue-500 text-white font-semibold hover:bg-blue-600 shadow-lg shadow-blue-500/25 transition-all mt-8">
                Inizia a monitorare
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- Right: Keyword Ranking Table Mockup -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Posizioni Keyword</span>
                </div>
                <!-- Keyword rows -->
                <div class="p-4 space-y-3">
                    <!-- Row 1 -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">scarpe running uomo</span>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">3</span>
                            <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                2
                            </span>
                            <span class="text-[10px] text-slate-400 w-12 text-right">8.100</span>
                        </div>
                    </div>
                    <!-- Row 2 -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">miglior materasso</span>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">12</span>
                            <span class="inline-flex items-center text-[10px] font-medium text-red-600 dark:text-red-400">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                3
                            </span>
                            <span class="text-[10px] text-slate-400 w-12 text-right">14.800</span>
                        </div>
                    </div>
                    <!-- Row 3 -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">ristorante milano centro</span>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">5</span>
                            <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                1
                            </span>
                            <span class="text-[10px] text-slate-400 w-12 text-right">6.600</span>
                        </div>
                    </div>
                    <!-- Row 4 -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">hotel roma economico</span>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">28</span>
                            <span class="inline-flex items-center text-[10px] font-medium text-slate-400">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                0
                            </span>
                            <span class="text-[10px] text-slate-400 w-12 text-right">9.900</span>
                        </div>
                    </div>
                </div>
                <!-- Bottom stats -->
                <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-t border-blue-200 dark:border-blue-800/30 flex items-center justify-between">
                    <span class="text-xs text-blue-600 dark:text-blue-300 font-medium">24 keyword monitorate</span>
                    <span class="text-xs text-blue-500 dark:text-blue-400">Pos. media: 14.2</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dall'aggiunta keyword al report AI in 3 passaggi</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold shadow-md">1</div>
            <div class="h-12 w-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aggiungi le keyword</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Importa le keyword da monitorare: manualmente, da file CSV, o sincronizzando Google Search Console. Per ogni keyword puoi specificare paese, lingua e dispositivo target. Esempio: aggiungi 'scarpe running uomo' per il mercato italiano.</p>
        </div>

        <!-- Step 2 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold shadow-md">2</div>
            <div class="h-12 w-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Monitora le posizioni</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Rank check automatici verificano la posizione di ogni keyword su Google. Per ogni keyword vedi posizione attuale, variazione, pagina posizionata e storico completo con grafici trend giornalieri.</p>
        </div>

        <!-- Step 3 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold shadow-md">3</div>
            <div class="h-12 w-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Report AI settimanali</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Ogni settimana l'AI analizza le variazioni: keyword in salita, keyword in discesa, pagine che cambiano posizione, anomalie da investigare. Un report chiaro con insight concreti, non solo numeri.</p>
        </div>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: Rank check (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Rank check automatici con storico completo</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Le posizioni vengono verificate automaticamente per ogni keyword. Per ognuna vedi: posizione attuale, variazione rispetto al check precedente, la URL che si posiziona, il volume di ricerca e lo storico completo. I grafici trend mostrano l'andamento nel tempo e ti aiutano a identificare pattern e stagionalita.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-blue-600 dark:text-blue-400 font-medium hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        Inizia il monitoraggio
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Keyword monitorate</span>
                        </div>
                        <!-- Table header -->
                        <div class="px-4 py-2 bg-slate-50/50 dark:bg-slate-700/30 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                            <span class="text-[10px] font-medium text-slate-400 uppercase flex-1">Keyword</span>
                            <span class="text-[10px] font-medium text-slate-400 uppercase w-12 text-center">Pos.</span>
                            <span class="text-[10px] font-medium text-slate-400 uppercase w-12 text-center">Trend</span>
                            <span class="text-[10px] font-medium text-slate-400 uppercase w-24 text-right">URL</span>
                        </div>
                        <!-- Rows -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">scarpe running</span>
                                <span class="inline-flex items-center justify-center w-12 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">3</span>
                                <span class="inline-flex items-center justify-center w-12 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>2
                                </span>
                                <span class="text-[10px] text-slate-400 w-24 text-right truncate">/blog/scarpe-running</span>
                            </div>
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">materasso memory</span>
                                <span class="inline-flex items-center justify-center w-12 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">12</span>
                                <span class="inline-flex items-center justify-center w-12 text-[10px] font-medium text-red-600 dark:text-red-400">
                                    <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>1
                                </span>
                                <span class="text-[10px] text-slate-400 w-24 text-right truncate">/prodotti/materasso</span>
                            </div>
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">hotel roma</span>
                                <span class="inline-flex items-center justify-center w-12 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">7</span>
                                <span class="inline-flex items-center justify-center w-12 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>4
                                </span>
                                <span class="text-[10px] text-slate-400 w-24 text-right truncate">/landing/hotel</span>
                            </div>
                        </div>
                        <!-- Mini sparkline chart -->
                        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">
                            <div class="flex items-end gap-1 h-8">
                                <div class="flex-1 bg-blue-200 dark:bg-blue-800/40 rounded-t" style="height: 60%"></div>
                                <div class="flex-1 bg-blue-200 dark:bg-blue-800/40 rounded-t" style="height: 45%"></div>
                                <div class="flex-1 bg-blue-200 dark:bg-blue-800/40 rounded-t" style="height: 70%"></div>
                                <div class="flex-1 bg-blue-200 dark:bg-blue-800/40 rounded-t" style="height: 55%"></div>
                                <div class="flex-1 bg-blue-200 dark:bg-blue-800/40 rounded-t" style="height: 80%"></div>
                                <div class="flex-1 bg-blue-200 dark:bg-blue-800/40 rounded-t" style="height: 65%"></div>
                                <div class="flex-1 bg-blue-300 dark:bg-blue-700/50 rounded-t" style="height: 90%"></div>
                                <div class="flex-1 bg-blue-300 dark:bg-blue-700/50 rounded-t" style="height: 75%"></div>
                                <div class="flex-1 bg-blue-400 dark:bg-blue-600/50 rounded-t" style="height: 85%"></div>
                                <div class="flex-1 bg-blue-500 dark:bg-blue-500/60 rounded-t" style="height: 100%"></div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-[9px] text-slate-400">14 giorni fa</span>
                                <span class="text-[9px] text-slate-400">Oggi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: GSC (visual LEFT, text RIGHT, slate bg) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Google Search Console integrato</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Collegando GSC ottieni i dati reali di Google: <strong class="text-blue-600 dark:text-blue-400">click</strong>, <strong class="text-blue-600 dark:text-blue-400">impressioni</strong>, <strong class="text-blue-600 dark:text-blue-400">CTR</strong> e <strong class="text-blue-600 dark:text-blue-400">posizione media</strong>. Ainstein incrocia i dati del rank check con quelli di Search Console per una visione completa. Scopri non solo dove sei posizionato, ma quanti click stai effettivamente ricevendo.
                    </p>
                    <a href="<?= url('/docs/seo-tracking') ?>" class="inline-flex items-center mt-6 text-blue-600 dark:text-blue-400 font-medium hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        Scopri l'integrazione GSC
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Google Search Console</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">Connesso</span>
                        </div>
                        <!-- 2x2 Metric cards -->
                        <div class="p-4 grid grid-cols-2 gap-3">
                            <!-- Click -->
                            <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40">
                                <p class="text-[10px] font-medium text-blue-500 dark:text-blue-400 uppercase">Click</p>
                                <div class="flex items-end justify-between mt-1">
                                    <span class="text-lg font-bold text-blue-700 dark:text-blue-300">1.234</span>
                                    <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        12%
                                    </span>
                                </div>
                            </div>
                            <!-- Impressioni -->
                            <div class="p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800/40">
                                <p class="text-[10px] font-medium text-purple-500 dark:text-purple-400 uppercase">Impressioni</p>
                                <div class="flex items-end justify-between mt-1">
                                    <span class="text-lg font-bold text-purple-700 dark:text-purple-300">45.600</span>
                                    <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        8%
                                    </span>
                                </div>
                            </div>
                            <!-- CTR -->
                            <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40">
                                <p class="text-[10px] font-medium text-emerald-500 dark:text-emerald-400 uppercase">CTR</p>
                                <div class="flex items-end justify-between mt-1">
                                    <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">2.7%</span>
                                    <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        5%
                                    </span>
                                </div>
                            </div>
                            <!-- Pos. Media -->
                            <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40">
                                <p class="text-[10px] font-medium text-amber-500 dark:text-amber-400 uppercase">Pos. Media</p>
                                <div class="flex items-end justify-between mt-1">
                                    <span class="text-lg font-bold text-amber-700 dark:text-amber-300">14.2</span>
                                    <span class="inline-flex items-center text-[10px] font-medium text-red-600 dark:text-red-400">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        2%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Report AI (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Report AI con insight e anomalie</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Ogni settimana ricevi un report AI che analizza le variazioni delle posizioni. L'AI identifica le keyword in salita e in discesa, le pagine che guadagnano o perdono visibilita, e segnala anomalie come cali improvvisi, nuovi posizionamenti o cambiamenti di URL posizionata. Non devi scorrere numeri: l'AI ti dice cosa sta succedendo.
                    </p>
                    <a href="<?= url('/docs/seo-tracking') ?>" class="inline-flex items-center mt-6 text-blue-600 dark:text-blue-400 font-medium hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        Scopri i report AI
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Report Settimanale AI</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">17-23 Feb 2026</span>
                        </div>
                        <div class="p-4 space-y-4">
                            <!-- Keyword in salita -->
                            <div class="border-l-4 border-emerald-400 pl-3">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 mb-2">Keyword in salita</p>
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">scarpe running uomo</span>
                                        <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            5 → 3
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">hotel roma centro</span>
                                        <span class="inline-flex items-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            18 → 7
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Keyword in discesa -->
                            <div class="border-l-4 border-red-400 pl-3">
                                <p class="text-xs font-semibold text-red-700 dark:text-red-300 mb-2">Keyword in discesa</p>
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">miglior materasso</span>
                                        <span class="inline-flex items-center text-[10px] font-medium text-red-600 dark:text-red-400">
                                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            9 → 12
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Anomalie -->
                            <div class="border-l-4 border-amber-400 pl-3">
                                <p class="text-xs font-semibold text-amber-700 dark:text-amber-300 mb-2">Anomalie</p>
                                <p class="text-xs text-slate-600 dark:text-slate-300">La pagina /blog/scarpe-running ha sostituito /prodotti/scarpe come URL posizionata per 3 keyword.</p>
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
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con SEO Tracking</h3>
            <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dal monitoraggio posizioni ai report automatici con insight AI</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Monitoraggio posizioni -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Monitoraggio posizioni</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Rank check automatici per le tue keyword target con posizione, trend e storico completo.</p>
            </div>
            <!-- Integrazione GSC -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Integrazione GSC</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Click, impressioni, CTR e posizione media direttamente da Google Search Console.</p>
            </div>
            <!-- Report AI settimanali -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Report AI settimanali</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Insight automatici su keyword in salita/discesa, anomalie e opportunita.</p>
            </div>
            <!-- Storico posizioni -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Storico posizioni</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Grafici trend giornalieri per ogni keyword, con variazioni e pattern stagionali.</p>
            </div>
            <!-- Import keyword -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Import keyword</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Da CSV, inserimento manuale o sincronizzazione automatica con Google Search Console.</p>
            </div>
            <!-- Multi-progetto -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Multi-progetto</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Monitora piu siti e domini dalla stessa dashboard con progetti separati.</p>
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
                    <span class="font-medium text-slate-900 dark:text-white">Quanti crediti costa il rank check?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Il rank check costa <strong>1 credito per batch</strong> di keyword controllate (fino a 50 keyword per batch). Se hai 100 keyword, servono 2 crediti per un check completo.</p>
                </div>
            </div>
            <!-- FAQ 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Come collego Google Search Console?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Dalla dashboard del progetto, clicca <strong>'Collega GSC'</strong> e autorizza l'accesso con il tuo account Google. I dati vengono sincronizzati automaticamente ogni ora. Servono i permessi di lettura su Search Console.</p>
                </div>
            </div>
            <!-- FAQ 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 3 ? openFaq = null : openFaq = 3" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Ogni quanto vengono aggiornate le posizioni?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Il rank check puo essere <strong>avviato manualmente</strong> in qualsiasi momento o <strong>programmato automaticamente</strong> con frequenza giornaliera o settimanale. I dati GSC vengono sincronizzati ogni ora automaticamente.</p>
                </div>
            </div>
            <!-- FAQ 4 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 4 ? openFaq = null : openFaq = 4" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso monitorare keyword in lingue diverse?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Si, puoi specificare <strong>paese e lingua</strong> per ogni progetto. Il rank check viene eseguito dalla localita selezionata per risultati accurati. Supportiamo le principali lingue e localita Google.</p>
                </div>
            </div>
            <!-- FAQ 5 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 5 ? openFaq = null : openFaq = 5" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Come funzionano i report AI?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 5 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">L'AI analizza le <strong>variazioni settimanali</strong>: identifica keyword che salgono o scendono, pagine che cambiano posizione, URL che si scambiano nei risultati, e segnala anomalie come cali improvvisi. Il report viene generato automaticamente.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Finale -->
    <div class="mt-20 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-500 p-8 lg:p-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Smetti di indovinare, inizia a misurare</h3>
                    <p class="mt-2 text-blue-100">Monitora le posizioni reali e ricevi insight AI ogni settimana</p>
                </div>
                <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-blue-600 font-semibold hover:bg-blue-50 shadow-lg transition-all flex-shrink-0">
                    Crea progetto
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
