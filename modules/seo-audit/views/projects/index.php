<?php
// Onboarding tour
$onboardingConfig = require BASE_PATH . '/config/onboarding.php';
$onboardingModuleSlug = 'seo-audit';
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
    'title' => 'Audit SEO completo con piano d\'azione',
    'description' => 'Ainstein scansiona il sito, analizza struttura, performance, contenuti e fattori tecnici. Non si limita a segnalare i problemi: li mette in ordine di impatto e crea un piano d\'azione con priorita chiare.',
    'color' => 'emerald',
    'badge' => 'Come funziona',
    'storageKey' => 'ainstein_hero_seo_audit',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>', 'title' => 'Inserisci l\'URL', 'subtitle' => 'Scansione completa'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>', 'title' => 'Analisi AI', 'subtitle' => 'Problemi e opportunita'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'title' => 'Piano d\'azione', 'subtitle' => 'Priorita per impatto'],
    ],
    'ctaText' => 'Nuovo Audit',
    'ctaUrl' => url('/projects/create'),
]) ?>
</div>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="sa-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">SEO Audit</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Audit SEO completo dei tuoi siti web</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/projects/create') ?>" data-tour="sa-newaudit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Audit
            </a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Scopri cosa migliorare nel tuo sito</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 max-w-md mx-auto">
            Inserisci un URL e ricevi un audit completo con le azioni da fare, in ordine di priorita.
        </p>
        <div class="flex flex-col items-center gap-3 max-w-sm mx-auto mb-6">
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Scansione struttura, performance e contenuti
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Problemi ordinati per impatto SEO
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Piano d'azione AI concreto e pronto da eseguire
            </div>
        </div>
        <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuovo Audit
        </a>
        <p class="mt-3">
            <a href="<?= url('/docs/seo-audit') ?>" class="text-xs text-slate-400 hover:text-primary-500 transition-colors">Scopri di piu &rarr;</a>
        </p>
    </div>
    <?php else: ?>
    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project): ?>
        <?php
        $statusColors = [
            'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
            'crawling' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
            'analyzing' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
            'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        ];
        $statusLabels = [
            'pending' => 'In attesa',
            'crawling' => 'Scansione...',
            'analyzing' => 'Analisi...',
            'completed' => 'Completato',
            'failed' => 'Errore',
        ];
        $statusColor = $statusColors[$project['status']] ?? $statusColors['pending'];
        $statusLabel = $statusLabels[$project['status']] ?? $project['status'];

        // Health score color
        $score = $project['health_score'] ?? 0;
        if ($score >= 80) {
            $scoreColor = 'text-emerald-600 dark:text-emerald-400';
            $scoreBg = 'bg-emerald-500';
        } elseif ($score >= 50) {
            $scoreColor = 'text-yellow-600 dark:text-yellow-400';
            $scoreBg = 'bg-yellow-500';
        } else {
            $scoreColor = 'text-red-600 dark:text-red-400';
            $scoreBg = 'bg-red-500';
        }
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($project['name']) ?></h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[180px]"><?= e($project['base_url']) ?></p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                        <?= $statusLabel ?>
                    </span>
                </div>

                <!-- Health Score -->
                <?php if ($project['status'] === 'completed' && $project['health_score'] !== null): ?>
                <div class="mt-4 flex items-center gap-4">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Health Score</span>
                            <span class="text-lg font-bold <?= $scoreColor ?>"><?= $score ?>/100</span>
                        </div>
                        <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full <?= $scoreBg ?> rounded-full transition-all" style="width: <?= $score ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($project['total_pages'] ?? $project['pages_crawled'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pagine</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2">
                        <p class="text-lg font-bold text-red-600 dark:text-red-400"><?= number_format($project['critical_issues'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Critici</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2">
                        <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400"><?= number_format($project['warning_issues'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Warning</p>
                    </div>
                </div>

            </div>

            <!-- Actions -->
            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                </span>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    <?= $project['status'] === 'completed' ? 'Visualizza' : 'Apri' ?>
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Separator -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-emerald-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- Hero Educativo -->
<div class="bg-gradient-to-br from-emerald-50/50 via-white to-teal-50/30 dark:from-emerald-950/20 dark:via-slate-900 dark:to-teal-950/10 rounded-2xl border border-emerald-200/50 dark:border-emerald-800/30 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <!-- Left: Text -->
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                SEO Audit
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white mt-4">Scansiona il tuo sito, scopri cosa migliorare e ricevi un piano d'azione concreto</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">Ainstein scansiona le pagine del tuo sito e analizza struttura tecnica, performance, contenuti, meta tags e accessibilita. Non si limita a trovare i problemi: li ordina per impatto reale sulla SEO e genera un piano d'azione con task concreti, pronti da eseguire.</p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-emerald-500 text-white font-semibold hover:bg-emerald-600 shadow-lg shadow-emerald-500/25 transition-all mt-8">
                Avvia il tuo primo audit
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- Right: Audit Report Visualization -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Audit Report</span>
                </div>
                <!-- Score visualization -->
                <div class="p-5">
                    <!-- Big circular score -->
                    <div class="flex justify-center mb-5">
                        <div class="relative w-28 h-28">
                            <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="52" fill="none" stroke-width="8" class="stroke-slate-200 dark:stroke-slate-700"/>
                                <circle cx="60" cy="60" r="52" fill="none" stroke-width="8" stroke-linecap="round" class="stroke-emerald-500" stroke-dasharray="326.73" stroke-dashoffset="91.48"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">72</span>
                                <span class="text-[10px] text-slate-400">/100</span>
                            </div>
                        </div>
                    </div>
                    <!-- Category bars -->
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-600 dark:text-slate-300">Performance</span>
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">85</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 85%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-600 dark:text-slate-300">Technical</span>
                                <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">68</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: 68%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-600 dark:text-slate-300">Content</span>
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">74</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: 74%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-600 dark:text-slate-300">Accessibility</span>
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">92</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 92%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bottom stats -->
                <div class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border-t border-emerald-200 dark:border-emerald-800/30 flex items-center justify-center gap-3">
                    <span class="text-xs font-medium text-red-600 dark:text-red-400">12 critici</span>
                    <span class="text-xs text-slate-300 dark:text-slate-600">&middot;</span>
                    <span class="text-xs font-medium text-amber-600 dark:text-amber-400">28 warning</span>
                    <span class="text-xs text-slate-300 dark:text-slate-600">&middot;</span>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">45 info</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Da URL a piano d'azione completo in 3 passaggi</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold shadow-md">1</div>
            <div class="h-12 w-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Inserisci l'URL del sito</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Scegli l'URL di partenza e il numero di pagine. Ainstein segue i link interni e scansiona automaticamente tutte le pagine raggiungibili dalla sitemap o tramite crawling. Esempio: inserisci 'example.com' e l'audit analizza tutte le pagine del sito.</p>
        </div>

        <!-- Step 2 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold shadow-md">2</div>
            <div class="h-12 w-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Analisi AI multi-dimensionale</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Performance, struttura tecnica, contenuti, meta tags, accessibilita: ogni aspetto viene valutato con un punteggio 0-100. L'AI incrocia i risultati per calcolare l'Health Score complessivo del sito.</p>
        </div>

        <!-- Step 3 -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold shadow-md">3</div>
            <div class="h-12 w-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Piano d'azione prioritizzato</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">L'AI ordina i problemi per impatto SEO e genera un Action Plan con task concreti. Ogni task ha descrizione dettagliata, pagine coinvolte e impatto stimato. Non devi decidere da dove iniziare: lo decide l'AI.</p>
        </div>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: Health Score (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Health Score e panoramica a colpo d'occhio</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Ogni audit produce un punteggio da 0 a 100 che sintetizza la salute SEO del sito. Le 5 categorie — Performance, Technical SEO, Content Quality, Accessibility, Meta Tags — mostrano esattamente dove il sito eccelle e dove ha margine di miglioramento. Basta un'occhiata per capire su cosa concentrarsi.
                    </p>
                    <a href="<?= url('/docs/seo-audit') ?>" class="inline-flex items-center mt-6 text-emerald-600 dark:text-emerald-400 font-medium hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                        Scopri come funziona
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Health Score</span>
                        </div>
                        <div class="p-5">
                            <!-- Big score number with ring -->
                            <div class="flex justify-center mb-5">
                                <div class="relative w-24 h-24">
                                    <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="42" fill="none" stroke-width="7" class="stroke-slate-200 dark:stroke-slate-700"/>
                                        <circle cx="50" cy="50" r="42" fill="none" stroke-width="7" stroke-linecap="round" class="stroke-emerald-500" stroke-dasharray="263.89" stroke-dashoffset="73.89"/>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">72</span>
                                    </div>
                                </div>
                            </div>
                            <!-- 5 category bars -->
                            <div class="space-y-2.5">
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 w-24 text-right">Performance</span>
                                    <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: 85%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 w-6">85</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 w-24 text-right">Technical SEO</span>
                                    <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: 62%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 w-6">62</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 w-24 text-right">Content Quality</span>
                                    <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" style="width: 74%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 w-6">74</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 w-24 text-right">Accessibility</span>
                                    <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: 92%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 w-6">92</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 w-24 text-right">Meta Tags</span>
                                    <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-violet-500 rounded-full" style="width: 58%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 w-6">58</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Problemi ordinati (visual LEFT, text RIGHT, slate bg) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Problemi ordinati per impatto reale</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Non una lista infinita di errori tutti uguali. L'AI raggruppa i problemi per severita — <strong class="text-red-600 dark:text-red-400">Critico</strong>, <strong class="text-amber-600 dark:text-amber-400">Warning</strong>, <strong class="text-blue-600 dark:text-blue-400">Info</strong> — e li ordina per impatto SEO reale. I primi problemi in lista sono quelli che, se risolti, porteranno il miglioramento maggiore.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-emerald-600 dark:text-emerald-400 font-medium hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                        Avvia un audit
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Problemi trovati</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">85 problemi</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <!-- Issue 1 -->
                            <div class="px-4 py-3 flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">Meta description mancante</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Alto</span>
                                <span class="text-[10px] text-slate-400">12 pagine</span>
                            </div>
                            <!-- Issue 2 -->
                            <div class="px-4 py-3 flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">Immagini senza alt text</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Medio</span>
                                <span class="text-[10px] text-slate-400">34 pagine</span>
                            </div>
                            <!-- Issue 3 -->
                            <div class="px-4 py-3 flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">H1 duplicato</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Medio</span>
                                <span class="text-[10px] text-slate-400">5 pagine</span>
                            </div>
                            <!-- Issue 4 -->
                            <div class="px-4 py-3 flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">Link HTTP su pagina HTTPS</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Basso</span>
                                <span class="text-[10px] text-slate-400">3 pagine</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Action Plan (text LEFT, visual RIGHT, white bg) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Action Plan AI: dall'analisi all'azione</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        L'audit non finisce con la lista dei problemi. L'AI genera un Action Plan completo: per ogni problema, un task concreto con descrizione, le pagine coinvolte, la priorita e l'impatto stimato. Puoi seguire il piano dall'alto verso il basso — le azioni piu importanti sono sempre in cima.
                    </p>
                    <a href="<?= url('/projects/create') ?>" class="inline-flex items-center mt-6 text-emerald-600 dark:text-emerald-400 font-medium hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                        Crea un Action Plan
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Action Plan</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">6 task</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <!-- Task 1 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <div class="w-4 h-4 rounded border-2 border-emerald-500 bg-emerald-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">Aggiungi meta description alle 12 pagine mancanti</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Critico</span>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap">12 pagine</span>
                            </div>
                            <!-- Task 2 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <div class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 flex-shrink-0"></div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">Ottimizza alt text immagini principali</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Warning</span>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap">34 pagine</span>
                            </div>
                            <!-- Task 3 -->
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <div class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 flex-shrink-0"></div>
                                <span class="text-xs text-slate-900 dark:text-white flex-1">Correggi heading structure duplicata</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Warning</span>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap">5 pagine</span>
                            </div>
                        </div>
                        <!-- Bottom impact bar -->
                        <div class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border-t border-emerald-200 dark:border-emerald-800/30">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-300">Impatto stimato: +15 punti Health Score</span>
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
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con SEO Audit</h3>
            <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dall'analisi completa al piano d'azione personalizzato</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Audit completo -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Audit completo del sito</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Scansione automatica di tutte le pagine raggiungibili, con analisi approfondita di ogni aspetto SEO.</p>
            </div>
            <!-- Analisi meta tags -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Analisi meta tags</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Verifica title, description, heading structure, Open Graph e dati strutturati per ogni pagina.</p>
            </div>
            <!-- Controllo performance -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Controllo performance</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Core Web Vitals, velocita di caricamento, compressione risorse e ottimizzazione immagini.</p>
            </div>
            <!-- Verifica struttura tecnica -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Verifica struttura tecnica</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Sitemap XML, robots.txt, canonical, redirect chain, SSL e configurazione server.</p>
            </div>
            <!-- Analisi contenuti -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Analisi contenuti</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Qualita del testo, lunghezza, leggibilita, keyword density e struttura heading.</p>
            </div>
            <!-- Action Plan AI -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Action Plan AI</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Piano d'azione con task concreti, priorita per impatto e pagine coinvolte.</p>
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
                    <span class="font-medium text-slate-900 dark:text-white">Quanti crediti costa un audit?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Un audit SEO completo costa <strong>5 crediti</strong>. Il costo include la scansione delle pagine, l'analisi AI multi-dimensionale e la generazione dell'Action Plan con task prioritizzati.</p>
                </div>
            </div>
            <!-- FAQ 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Quante pagine vengono scansionate?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Fino a <strong>100 pagine</strong> per audit. Le pagine vengono scoperte automaticamente dalla sitemap XML o tramite crawling dei link interni. Puoi anche importare URL specifiche manualmente o da WordPress.</p>
                </div>
            </div>
            <!-- FAQ 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 3 ? openFaq = null : openFaq = 3" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Cosa include l'Action Plan?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Una lista di <strong>task prioritizzati</strong>: per ogni problema trovato, l'AI genera una descrizione dettagliata, le pagine coinvolte, la severita (critico/warning/info) e l'impatto stimato sulla SEO. I task sono ordinati per impatto decrescente.</p>
                </div>
            </div>
            <!-- FAQ 4 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 4 ? openFaq = null : openFaq = 4" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso importare URL da WordPress?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Si, collegando il <strong>connettore WordPress</strong> puoi importare le pagine direttamente dal tuo sito. Basta installare il plugin Ainstein sul tuo WordPress e configurare la connessione dalla dashboard del progetto.</p>
                </div>
            </div>
            <!-- FAQ 5 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 5 ? openFaq = null : openFaq = 5" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Come viene calcolato l'Health Score?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 5 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">E la <strong>media ponderata</strong> di 5 categorie: Performance, Technical SEO, Content Quality, Accessibility e Meta Tags. Ogni categoria analizza fattori specifici e assegna un punteggio da 0 a 100. Il peso di ogni categoria riflette il suo impatto sulla SEO.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Finale -->
    <div class="mt-20 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 p-8 lg:p-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Scopri i problemi nascosti nel tuo sito</h3>
                    <p class="mt-2 text-emerald-100">Avvia un audit completo e ricevi un piano d'azione personalizzato</p>
                </div>
                <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-emerald-600 font-semibold hover:bg-emerald-50 shadow-lg transition-all flex-shrink-0">
                    Avvia audit
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
