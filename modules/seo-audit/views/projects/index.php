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

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="sa-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">SEO Audit</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Audit SEO completo dei tuoi siti web</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/seo-audit/create') ?>" data-tour="sa-newaudit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
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
        <div class="mx-auto h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun audit</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            Crea il tuo primo audit SEO per analizzare in profondità la salute tecnica del tuo sito.
        </p>
        <div class="mt-6">
            <a href="<?= url('/seo-audit/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Audit
            </a>
        </div>
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
