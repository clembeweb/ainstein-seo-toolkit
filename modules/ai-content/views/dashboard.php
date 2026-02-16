<?php
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

<?php
// Determina URL base per i link (con o senza progetto)
$baseUrl = isset($project) && $project ? '/ai-content/projects/' . $project['id'] : '/ai-content';
?>

<?php if (isset($project) && $project): ?>
<?php $currentPage = 'dashboard'; ?>
<?php include __DIR__ . '/partials/project-nav.php'; ?>
<?php endif; ?>

<div class="space-y-6">
    <?php if (!isset($project) || !$project): ?>
    <!-- Header (global view) -->
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="aic-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI SEO Content Generator</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera articoli SEO-ottimizzati con intelligenza artificiale</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3" data-tour="aic-quickactions">
            <a href="<?= url($baseUrl . '/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Keyword
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-tour="aic-stats">
        <!-- Keywords -->
        <a href="<?= url($baseUrl . '/keywords') ?>" class="block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['keywords']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Keywords</p>
                </div>
            </div>
        </a>

        <!-- Articles -->
        <a href="<?= url($baseUrl . '/articles') ?>" class="block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['articles']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Articoli</p>
                </div>
            </div>
        </a>

        <!-- Published -->
        <a href="<?= url($baseUrl . '/articles?status=published') ?>" class="block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['published']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pubblicati</p>
                </div>
            </div>
        </a>

        <!-- WP Site (linked to project) -->
        <?php if (isset($project) && $project): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <?php if (!empty($linkedWpSite)): ?>
                    <p class="font-medium text-slate-900 dark:text-white truncate"><?= e($linkedWpSite['name']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($linkedWpSite['url']) ?></p>
                    <?php else: ?>
                    <p class="font-medium text-slate-500 dark:text-slate-400">Nessun sito collegato</p>
                    <a href="<?= url('/ai-content/projects/' . $project['id'] . '/settings') ?>" class="text-xs text-primary-600 hover:text-primary-700">Configura nelle impostazioni</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                <a href="<?= url('/ai-content/wordpress') ?>" class="text-xs text-slate-500 hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Gestisci tutti i siti WP
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Additional Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Total Words -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Parole generate</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($stats['total_words']) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Credits Used -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Crediti utilizzati</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($stats['total_credits']) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center">
                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions + Recent Articles -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Azioni rapide</h2>
            <div class="space-y-3">
                <a href="<?= url($baseUrl . '/keywords') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Aggiungi Keyword</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Inizia una nuova ricerca</p>
                    </div>
                </a>

                <a href="<?= url($baseUrl . '/articles') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Vedi Articoli</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Gestisci i tuoi contenuti</p>
                    </div>
                </a>

                <a href="<?= url('/ai-content/wordpress') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Siti WordPress</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Gestisci tutti i siti</p>
                    </div>
                </a>

                <?php if (isset($project) && $project): ?>
                <a href="<?= url($baseUrl . '/settings') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Impostazioni</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configura il progetto</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Articles -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Articoli recenti</h2>
                <a href="<?= url($baseUrl . '/articles') ?>" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vedi tutti
                </a>
            </div>

            <?php if (empty($recentArticles)): ?>
            <div class="text-center py-8">
                <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun articolo generato</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Inizia aggiungendo una keyword e generando il tuo primo articolo</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentArticles as $article): ?>
                <a href="<?= url($baseUrl . '/articles/' . $article['id']) ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center flex-shrink-0
                            <?php if ($article['status'] === 'published'): ?>
                            bg-emerald-100 dark:bg-emerald-900/50
                            <?php elseif ($article['status'] === 'ready'): ?>
                            bg-blue-100 dark:bg-blue-900/50
                            <?php elseif ($article['status'] === 'generating'): ?>
                            bg-amber-100 dark:bg-amber-900/50
                            <?php else: ?>
                            bg-slate-100 dark:bg-slate-700
                            <?php endif; ?>
                        ">
                            <?php if ($article['status'] === 'published'): ?>
                            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <?php elseif ($article['status'] === 'ready'): ?>
                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php elseif ($article['status'] === 'generating'): ?>
                            <svg class="h-4 w-4 text-amber-600 dark:text-amber-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?php else: ?>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white truncate"><?= e($article['title'] ?: $article['keyword']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= $article['word_count'] ? number_format($article['word_count']) . ' parole' : 'In attesa' ?>
                                &bull; <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                        <?php if ($article['status'] === 'published'): ?>
                        bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                        <?php elseif ($article['status'] === 'ready'): ?>
                        bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300
                        <?php elseif ($article['status'] === 'generating'): ?>
                        bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                        <?php else: ?>
                        bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
                        <?php endif; ?>
                    ">
                        <?= ucfirst($article['status']) ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Credits Info -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Costi Crediti</h3>
                <p class="text-purple-100 text-sm mt-1">Informazioni sui costi per le operazioni</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <p class="text-2xl font-bold"><?= number_format($creditCosts['serp'] ?? 3, 0) ?></p>
                    <p class="text-xs text-purple-200">Estrazione SERP</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold"><?= number_format($creditCosts['scrape'] ?? 1, 0) ?></p>
                    <p class="text-xs text-purple-200">Scraping URL</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold"><?= number_format($creditCosts['article'] ?? 10, 0) ?></p>
                    <p class="text-xs text-purple-200">Generazione AI</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold">0</p>
                    <p class="text-xs text-purple-200">Pubblica WP</p>
                </div>
            </div>
        </div>
    </div>
</div>
