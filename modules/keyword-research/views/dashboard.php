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

<!-- Hero Value Block -->
<div class="mb-6">
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'Ricerca keyword AI con clustering semantico',
    'description' => 'Dalla keyword seed alla strategia completa: l\'AI genera centinaia di varianti, le organizza in cluster tematici per intento di ricerca e crea un piano editoriale pronto per la produzione contenuti.',
    'color' => 'purple',
    'badge' => 'Come funziona',
    'storageKey' => 'ainstein_hero_keyword_research',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>', 'title' => 'Keyword seed', 'subtitle' => 'Espansione AI'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>', 'title' => 'Cluster semantici', 'subtitle' => 'Raggruppamento per intent'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>', 'title' => 'Piano editoriale', 'subtitle' => 'Export in AI Content'],
    ],
    'ctaText' => 'Nuova Ricerca',
    'ctaUrl' => url('/projects/create'),
]) ?>
</div>

<div class="space-y-8">
    <!-- Header -->
    <div data-tour="kr-header">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Keyword Research</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ricerca keyword potenziata da AI con clustering semantico e architettura sito</p>
    </div>

    <!-- 4 Mode Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?= \Core\View::partial('components/dashboard-mode-card', [
            'title' => 'Research Guidata',
            'description' => 'Progetti SEO/Ads con clustering AI. Parti da seed keyword, espandi automaticamente e ottieni cluster semantici con intent e note strategiche.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
            'gradient' => 'from-emerald-500 to-teal-600',
            'url' => url('/keyword-research/projects?type=research'),
            'cost' => '3 cr',
            'costColor' => 'amber',
            'dataTour' => 'kr-guided',
        ]) ?>

        <?= \Core\View::partial('components/dashboard-mode-card', [
            'title' => 'Architettura Sito',
            'description' => "Struttura URL e slug per un nuovo sito. L'AI propone pagine, H1 e URL basati sui volumi di ricerca reali.",
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
            'gradient' => 'from-blue-500 to-indigo-600',
            'url' => url('/keyword-research/projects?type=architecture'),
            'cost' => '10 cr',
            'costColor' => 'purple',
            'dataTour' => 'kr-architecture',
        ]) ?>

        <?= \Core\View::partial('components/dashboard-mode-card', [
            'title' => 'Piano Editoriale',
            'description' => 'Piano editoriale mensile basato su keyword e analisi competitor. Esporta direttamente in AI Content.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'gradient' => 'from-violet-500 to-purple-600',
            'url' => url('/keyword-research/projects?type=editorial'),
            'cost' => '10 cr',
            'costColor' => 'purple',
            'dataTour' => 'kr-editorial',
        ]) ?>

        <?= \Core\View::partial('components/dashboard-mode-card', [
            'title' => 'Quick Check',
            'description' => 'Check istantaneo di una singola keyword. Ottieni volume, CPC, competition e correlate senza creare un progetto.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
            'gradient' => 'from-amber-500 to-orange-600',
            'url' => url('/keyword-research/quick-check'),
            'ctaText' => 'Cerca keyword',
            'cost' => 'Gratis',
            'costColor' => 'emerald',
            'dataTour' => 'kr-quickcheck',
        ]) ?>
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
</div><!-- /space-y-8 -->

<!-- ═══════════ SEZIONE EDUCATIVA ═══════════ -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-purple-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- Hero Educativo -->
<div class="bg-gradient-to-br from-purple-50/50 via-white to-indigo-50/30 dark:from-purple-950/20 dark:via-slate-900 dark:to-indigo-950/10 rounded-2xl border border-purple-200/50 dark:border-purple-800/30 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <!-- Left: Text -->
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                Keyword Research
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white mt-4">Trova le keyword giuste e trasformale in un piano editoriale</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">Dalla ricerca seed alla strategia contenuti completa: scopri keyword ad alto potenziale, organizzale in cluster tematici e genera un piano editoriale pronto per la produzione — tutto guidato dall'AI.</p>
            <a href="<?= url('/keyword-research/projects?type=research') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-purple-500 text-white font-semibold hover:bg-purple-600 shadow-lg shadow-purple-500/25 transition-all mt-8">
                Inizia la tua prima ricerca
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- Right: Cluster Tree Visualization -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Cluster Keywords</span>
                </div>
                <!-- Cluster visualization -->
                <div class="p-5">
                    <!-- Seed keyword (center) -->
                    <div class="flex justify-center mb-4">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-100 dark:bg-purple-900/30 border-2 border-purple-400 dark:border-purple-500">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">scarpe running</span>
                        </div>
                    </div>
                    <!-- Connecting lines (visual) -->
                    <div class="flex justify-center mb-2">
                        <div class="w-px h-4 bg-purple-300 dark:bg-purple-600"></div>
                    </div>
                    <div class="flex justify-center mb-2">
                        <div class="w-3/4 h-px bg-purple-300 dark:bg-purple-600 relative">
                            <div class="absolute left-0 top-0 w-px h-3 bg-purple-300 dark:bg-purple-600"></div>
                            <div class="absolute left-1/2 top-0 w-px h-3 bg-purple-300 dark:bg-purple-600 -translate-x-1/2"></div>
                            <div class="absolute right-0 top-0 w-px h-3 bg-purple-300 dark:bg-purple-600"></div>
                        </div>
                    </div>
                    <!-- Branch keywords -->
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <div class="text-center">
                            <div class="inline-block px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40">
                                <p class="text-xs font-medium text-blue-700 dark:text-blue-300">migliori scarpe running principianti</p>
                                <p class="text-[10px] text-blue-500 dark:text-blue-400 mt-0.5">1.900 vol/mese</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="inline-block px-2.5 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40">
                                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">scarpe running pronazione</p>
                                <p class="text-[10px] text-emerald-500 dark:text-emerald-400 mt-0.5">2.400 vol/mese</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="inline-block px-2.5 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40">
                                <p class="text-xs font-medium text-amber-700 dark:text-amber-300">scarpe running donna offerte</p>
                                <p class="text-[10px] text-amber-500 dark:text-amber-400 mt-0.5">3.100 vol/mese</p>
                            </div>
                        </div>
                    </div>
                    <!-- Second level branches (one branch expanded) -->
                    <div class="mt-3 flex justify-center">
                        <div class="w-px h-3 bg-blue-300 dark:bg-blue-600 ml-[-33%]"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 w-2/3 mx-auto mt-0">
                        <div class="text-center">
                            <div class="inline-block px-2 py-1 rounded bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                                <p class="text-[10px] text-slate-600 dark:text-slate-300">scarpe running principianti uomo</p>
                                <p class="text-[9px] text-slate-400">880 vol</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="inline-block px-2 py-1 rounded bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                                <p class="text-[10px] text-slate-600 dark:text-slate-300">scarpe running economiche 2026</p>
                                <p class="text-[9px] text-slate-400">1.200 vol</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bottom stats -->
                <div class="px-4 py-3 bg-purple-50 dark:bg-purple-900/20 border-t border-purple-200 dark:border-purple-800/30 flex items-center justify-between">
                    <span class="text-xs text-purple-600 dark:text-purple-300 font-medium">5 keyword trovate</span>
                    <span class="text-xs text-purple-500 dark:text-purple-400">Volume totale: 9.480/mese</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Da keyword seed a piano editoriale completo in 3 passaggi</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-sm font-bold shadow-md">1</div>
            <div class="h-12 w-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Parti da una keyword seed</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Inserisci una keyword di partenza e il settore. L'AI genera centinaia di varianti: long-tail, domande degli utenti, keyword correlate. Esempio: da "scarpe running" ottieni "migliori scarpe running principianti", "scarpe running pronazione", "scarpe running donna offerte".</p>
        </div>

        <!-- Step 2 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-sm font-bold shadow-md">2</div>
            <div class="h-12 w-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Organizza in cluster</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">L'AI raggruppa le keyword per intento di ricerca e topic, creando cluster tematici. Ogni cluster diventa un potenziale articolo o pagina. Esempio: "materassi memory foam" raggruppa keyword su vantaggi, prezzi, manutenzione, confronto con lattice.</p>
        </div>

        <!-- Step 3 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-sm font-bold shadow-md">3</div>
            <div class="h-12 w-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Genera il Piano Editoriale</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Trasforma i cluster in un piano editoriale completo: titoli articolo, priorità, difficoltà stimata, volume di ricerca. Esporta direttamente in AI Content per la generazione automatica degli articoli.</p>
        </div>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: 4 modalità (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">4 modalità per ogni esigenza di ricerca</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        <strong class="text-purple-600 dark:text-purple-400">Research Guidata</strong>: parti da una seed keyword e lascia che l'AI trovi centinaia di keyword correlate con clustering automatico.
                        <strong class="text-purple-600 dark:text-purple-400">Architettura Sito</strong>: definisci la struttura URL ideale basata sui volumi di ricerca.
                        <strong class="text-purple-600 dark:text-purple-400">Piano Editoriale</strong>: trasforma le keyword in un calendario contenuti con priorità.
                        <strong class="text-purple-600 dark:text-purple-400">Quick Check</strong>: verifica veloce volume e difficoltà di una keyword — completamente gratis.
                    </p>
                    <a href="<?= url('/keyword-research/projects') ?>" class="inline-flex items-center mt-6 text-purple-600 dark:text-purple-400 font-medium hover:text-purple-700 dark:hover:text-purple-300 transition-colors">
                        Esplora le modalità
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Scegli la modalità</span>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-3">
                            <!-- Research Guidata -->
                            <div class="p-3 rounded-lg border-2 border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                </div>
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Research Guidata</p>
                                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">3 crediti</p>
                            </div>
                            <!-- Architettura Sito -->
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/30">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Architettura Sito</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">10 crediti</p>
                            </div>
                            <!-- Piano Editoriale -->
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/30">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Piano Editoriale</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">10 crediti</p>
                            </div>
                            <!-- Quick Check -->
                            <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/30">
                                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Quick Check</p>
                                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">Gratis</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Clustering AI (visual LEFT, text RIGHT, slate bg) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Clustering AI che organizza il caos</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Centinaia di keyword diventano ingestibili. L'AI le raggruppa automaticamente per intento e topic, suggerendo quali keyword targetizzare insieme nella stessa pagina. Per un e-commerce di mobili, <strong class="text-purple-600 dark:text-purple-400">'tavolo da pranzo allungabile'</strong>, <strong class="text-purple-600 dark:text-purple-400">'tavolo 6 persone'</strong> e <strong class="text-purple-600 dark:text-purple-400">'tavolo legno massello'</strong> finiscono nello stesso cluster perché l'utente sta cercando lo stesso prodotto.
                    </p>
                    <a href="<?= url('/keyword-research/projects?type=research') ?>" class="inline-flex items-center mt-6 text-purple-600 dark:text-purple-400 font-medium hover:text-purple-700 dark:hover:text-purple-300 transition-colors">
                        Scopri il clustering
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Cluster generati</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">2 cluster</span>
                        </div>
                        <div class="p-4 space-y-4">
                            <!-- Cluster 1 -->
                            <div class="rounded-lg border border-blue-200 dark:border-blue-800/40 overflow-hidden">
                                <div class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded bg-blue-500 flex items-center justify-center">
                                            <span class="text-[10px] font-bold text-white">1</span>
                                        </div>
                                        <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">Tavoli da pranzo</span>
                                    </div>
                                    <span class="text-[10px] text-blue-500 dark:text-blue-400">Intent: commerciale</span>
                                </div>
                                <div class="px-3 py-2 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">tavolo da pranzo allungabile</span>
                                        <span class="text-[10px] text-slate-400">2.900</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">tavolo 6 persone</span>
                                        <span class="text-[10px] text-slate-400">1.600</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">tavolo legno massello</span>
                                        <span class="text-[10px] text-slate-400">2.100</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Cluster 2 -->
                            <div class="rounded-lg border border-emerald-200 dark:border-emerald-800/40 overflow-hidden">
                                <div class="px-3 py-2 bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded bg-emerald-500 flex items-center justify-center">
                                            <span class="text-[10px] font-bold text-white">2</span>
                                        </div>
                                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Sedie da pranzo</span>
                                    </div>
                                    <span class="text-[10px] text-emerald-500 dark:text-emerald-400">Intent: commerciale</span>
                                </div>
                                <div class="px-3 py-2 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">sedie da pranzo moderne</span>
                                        <span class="text-[10px] text-slate-400">3.400</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600 dark:text-slate-300">sedie imbottite sala da pranzo</span>
                                        <span class="text-[10px] text-slate-400">1.200</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Piano Editoriale → AI Content (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Dal piano editoriale alla produzione in un click</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Il Piano Editoriale non resta un foglio Excel. Esporta le keyword selezionate direttamente nel modulo AI Content: per ogni keyword verrà creato automaticamente il processo completo di analisi SERP, brief e generazione articolo. Esempio: <strong class="text-purple-600 dark:text-purple-400">selezioni 10 keyword dal piano → 10 articoli in coda di produzione</strong>.
                    </p>
                    <a href="<?= url('/keyword-research/projects?type=editorial') ?>" class="inline-flex items-center mt-6 text-purple-600 dark:text-purple-400 font-medium hover:text-purple-700 dark:hover:text-purple-300 transition-colors">
                        Crea un piano editoriale
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Piano Editoriale</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">3 di 10 selezionati</span>
                        </div>
                        <!-- Table rows -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <div class="w-4 h-4 rounded border-2 border-purple-500 bg-purple-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">come scegliere un materasso</span>
                                <span class="text-[10px] text-slate-400 w-14 text-right">12.100</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 w-12 text-center">Facile</span>
                            </div>
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <div class="w-4 h-4 rounded border-2 border-purple-500 bg-purple-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">miglior materasso memory foam</span>
                                <span class="text-[10px] text-slate-400 w-14 text-right">8.500</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 w-12 text-center">Media</span>
                            </div>
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <div class="w-4 h-4 rounded border-2 border-purple-500 bg-purple-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate">materasso ortopedico opinioni</span>
                                <span class="text-[10px] text-slate-400 w-14 text-right">5.200</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 w-12 text-center">Facile</span>
                            </div>
                            <div class="px-4 py-2.5 flex items-center gap-3 opacity-50">
                                <div class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 flex-shrink-0"></div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 flex-1 truncate">materasso lattice naturale</span>
                                <span class="text-[10px] text-slate-400 w-14 text-right">3.800</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 w-12 text-center">Difficile</span>
                            </div>
                        </div>
                        <!-- Export button -->
                        <div class="px-4 py-3 bg-purple-50 dark:bg-purple-900/20 border-t border-purple-200 dark:border-purple-800/30">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-purple-600 dark:text-purple-300">3 keyword selezionate → AI Content</span>
                                <div class="inline-flex items-center px-3 py-1.5 rounded-lg bg-purple-500 text-white text-xs font-medium">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    Esporta in AI Content
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
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con Keyword Research</h3>
            <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dalla ricerca di nicchia all'export automatico verso la produzione contenuti</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Ricerca keyword di nicchia -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Ricerca keyword di nicchia</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Trova keyword long-tail con bassa competizione e alto potenziale di traffico. Ideale per siti nuovi o di nicchia.</p>
            </div>
            <!-- Analisi competitor -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Analisi competitor</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Scopri su quali keyword si posizionano i competitor e trova opportunità inesplorate nel tuo settore.</p>
            </div>
            <!-- Struttura sito web -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Struttura sito web</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pianifica l'architettura delle pagine del tuo sito basandoti su dati di ricerca reali e volumi.</p>
            </div>
            <!-- Calendario contenuti -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Calendario contenuti</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Crea un piano editoriale mensile con priorità, scadenze e keyword assegnate a ogni contenuto.</p>
            </div>
            <!-- Keyword gap analysis -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 12m0 0l-4.5 4.5M21 12H7.5"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Keyword gap analysis</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Identifica le keyword che i competitor coprono e tu no. Colma i gap nella tua strategia SEO.</p>
            </div>
            <!-- Export automatico -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Export automatico</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Passa le keyword selezionate direttamente al modulo AI Content per la generazione automatica degli articoli.</p>
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
                    <span class="font-medium text-slate-900 dark:text-white">Quanti crediti costa una ricerca keyword?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">La <strong>Research Guidata</strong> costa 3 crediti per analisi. L'<strong>Architettura Sito</strong> e il <strong>Piano Editoriale</strong> costano 10 crediti ciascuno. Il <strong>Quick Check</strong> è completamente gratuito e non consuma crediti.</p>
                </div>
            </div>
            <!-- FAQ 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Che differenza c'è tra le 4 modalità?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed"><strong>Research Guidata</strong>: parti da keyword seed, espansione automatica e clustering AI. Ideale per scoprire keyword e organizzarle. <strong>Architettura Sito</strong>: genera una struttura di pagine con URL e H1 basati sui volumi di ricerca. Perfetto per nuovi siti. <strong>Piano Editoriale</strong>: crea un calendario contenuti con priorità e keyword assegnate, esportabile in AI Content. <strong>Quick Check</strong>: verifica rapida di volume, CPC e competizione per una singola keyword.</p>
                </div>
            </div>
            <!-- FAQ 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 3 ? openFaq = null : openFaq = 3" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso esportare le keyword in AI Content?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Sì, dal <strong>Piano Editoriale</strong> puoi selezionare le keyword e inviarle direttamente al modulo AI Content. Per ogni keyword verrà creato automaticamente il processo di analisi SERP, generazione brief e scrittura articolo.</p>
                </div>
            </div>
            <!-- FAQ 4 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 4 ? openFaq = null : openFaq = 4" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Da dove vengono i dati di volume e difficoltà?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">I dati provengono da <strong>Google Keyword Insight</strong> tramite RapidAPI, che fornisce volumi di ricerca mensili reali, CPC, livello di competizione e keyword correlate. I dati vengono aggiornati regolarmente.</p>
                </div>
            </div>
            <!-- FAQ 5 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 5 ? openFaq = null : openFaq = 5" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Il Quick Check è davvero gratuito?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 5 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Sì, il Quick Check non consuma crediti. Puoi verificare volume di ricerca, CPC, competizione e keyword correlate per qualsiasi keyword senza costi. È il modo perfetto per fare un controllo rapido prima di avviare una ricerca completa.</p>
                </div>
            </div>
            <!-- FAQ 6 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 6 ? openFaq = null : openFaq = 6" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso fare ricerche in lingue diverse dall'italiano?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 6 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 6" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Sì, puoi specificare lingua e paese target per ogni progetto di ricerca. Ainstein supporta le principali lingue europee e permette di analizzare keyword per mercati specifici come Italia, USA, UK, Francia, Germania e Spagna.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Finale -->
    <div class="mt-20 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-500 to-indigo-500 p-8 lg:p-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Scopri le keyword che i tuoi competitor non stanno sfruttando</h3>
                    <p class="mt-2 text-purple-100">Inizia la tua ricerca e costruisci una strategia SEO basata sui dati</p>
                </div>
                <a href="<?= url('/keyword-research/projects') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-purple-600 font-semibold hover:bg-purple-50 shadow-lg transition-all flex-shrink-0">
                    Crea progetto
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
