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
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Aggiungi una keyword oppure importa un piano editoriale da Keyword Research</p>
                <a href="<?= url('/keyword-research') ?>" class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    Vai a Keyword Research
                </a>
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
</div><!-- /space-y-6 -->

<!-- ═══════════ SEZIONE EDUCATIVA ═══════════ -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-amber-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- ═══════════ HERO EDUCATIVO ═══════════ -->
<div class="bg-gradient-to-br from-amber-50/50 via-white to-orange-50/30 dark:from-amber-950/20 dark:via-slate-900 dark:to-orange-950/10 rounded-2xl border border-amber-200/40 dark:border-amber-800/20 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">

        <!-- Left column: Copy -->
        <div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 mb-6">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                AI Content Generator
            </span>

            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white leading-tight">
                Genera contenuti SEO che si posizionano in prima pagina
            </h2>

            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">
                Dall'analisi keyword alla pubblicazione WordPress: il tuo assistente AI studia i competitor, crea brief strategici e scrive articoli ottimizzati — pronti per essere pubblicati con un click.
            </p>

            <a href="<?= url('/ai-content/projects') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/25 transition-all mt-8">
                Crea il tuo primo progetto
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>

        <!-- Right column: CSS mock article preview -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Top bar with fake browser/editor chrome -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <div class="flex-1 text-center">
                        <span class="text-xs text-slate-400 dark:text-slate-500">Anteprima articolo</span>
                    </div>
                </div>
                <!-- Article content -->
                <div class="p-5">
                    <!-- SEO Score badge -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wide">come scegliere un materasso</span>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full border-3 border-emerald-500 flex items-center justify-center">
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">92</span>
                            </div>
                            <span class="text-xs text-slate-500">SEO Score</span>
                        </div>
                    </div>
                    <!-- Fake article title -->
                    <div class="h-5 bg-slate-800 dark:bg-white rounded w-4/5 mb-3"></div>
                    <!-- Fake heading structure -->
                    <div class="space-y-2.5 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono font-bold text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 px-1.5 py-0.5 rounded">H1</span>
                            <div class="h-3 bg-slate-300 dark:bg-slate-600 rounded flex-1"></div>
                        </div>
                        <div class="flex items-center gap-2 pl-3">
                            <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded">H2</span>
                            <div class="h-2.5 bg-slate-200 dark:bg-slate-600/70 rounded w-3/4"></div>
                        </div>
                        <div class="flex items-center gap-2 pl-3">
                            <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded">H2</span>
                            <div class="h-2.5 bg-slate-200 dark:bg-slate-600/70 rounded w-2/3"></div>
                        </div>
                        <div class="flex items-center gap-2 pl-6">
                            <span class="text-[10px] font-mono font-bold text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 px-1.5 py-0.5 rounded">H3</span>
                            <div class="h-2 bg-slate-200 dark:bg-slate-600/70 rounded w-1/2"></div>
                        </div>
                    </div>
                    <!-- Fake text lines -->
                    <div class="space-y-1.5">
                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-full"></div>
                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-11/12"></div>
                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-full"></div>
                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-4/5"></div>
                    </div>
                    <!-- Meta tags row -->
                    <div class="flex gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">Meta Title ✓</span>
                        <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">Meta Desc ✓</span>
                        <span class="text-[10px] px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-medium">2.450 parole</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Da keyword a articolo pubblicato in 4 semplici passaggi</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Step 1: Aggiungi le keyword -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-md">1</div>
            <div class="h-12 w-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aggiungi le keyword</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Inserisci le keyword da posizionare manualmente, via CSV, o importale direttamente da Keyword Research. Il sistema le organizza per progetto.</p>
        </div>

        <!-- Step 2: Studio SERP + Brief AI -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-md">2</div>
            <div class="h-12 w-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Studio SERP + Brief AI</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Per ogni keyword, Ainstein analizza i primi 10 risultati Google, estrae la struttura dei competitor e genera un brief strategico con heading e argomenti da coprire.</p>
        </div>

        <!-- Step 3: Generazione articolo -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-md">3</div>
            <div class="h-12 w-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Generazione articolo</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">L'AI scrive un articolo SEO completo seguendo il brief, con H2/H3 ottimizzati, meta title e description, e il tuo tone of voice. Puoi modificarlo prima della pubblicazione.</p>
        </div>

        <!-- Step 4: Pubblicazione WordPress -->
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-md">4</div>
            <div class="h-12 w-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Pubblicazione WordPress</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Connetti il tuo sito WordPress e pubblica direttamente dalla piattaforma. Cover image DALL-E inclusa. Scheduling automatico supportato.</p>
        </div>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: Analisi SERP (text LEFT, visual RIGHT, bg white) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Text side -->
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Analisi SERP che studia i tuoi competitor</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">Prima di scrivere, Ainstein fa scraping dei primi 10 risultati Google per la tua keyword. Analizza struttura heading, lunghezza, argomenti trattati. Per la keyword <strong class="text-amber-600 dark:text-amber-400">'come scegliere un materasso'</strong>, il sistema esamina i top 10 articoli e identifica che tutti coprono 'materiali', 'dimensioni', 'budget' — così il tuo articolo non mancherà nessun argomento.</p>
                    <a href="<?= url($baseUrl . '/keywords') ?>" class="inline-flex items-center mt-6 text-amber-600 dark:text-amber-400 font-medium hover:text-amber-700 dark:hover:text-amber-300 transition-colors">
                        Scopri l'analisi SERP
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <!-- Visual side: Mini SERP table -->
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <!-- Table header -->
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <span class="w-8">#</span>
                                <span class="flex-1">Pagina</span>
                                <span class="w-16 text-center">Parole</span>
                                <span class="w-12 text-center">H2</span>
                            </div>
                        </div>
                        <!-- Rows -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <div class="px-4 py-3 flex items-center gap-3">
                                <span class="w-8 text-sm font-bold text-amber-500">1</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-slate-900 dark:text-white truncate">materiassi.it/guida-scelta</div>
                                    <div class="text-xs text-slate-400">Guida Completa alla Scelta del Materasso</div>
                                </div>
                                <span class="w-16 text-center text-sm text-slate-600 dark:text-slate-300 font-medium">3.240</span>
                                <span class="w-12 text-center text-sm text-slate-600 dark:text-slate-300">8</span>
                            </div>
                            <div class="px-4 py-3 flex items-center gap-3">
                                <span class="w-8 text-sm font-bold text-slate-400">2</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-slate-900 dark:text-white truncate">dormibene.com/come-scegliere</div>
                                    <div class="text-xs text-slate-400">Come Scegliere il Materasso Giusto</div>
                                </div>
                                <span class="w-16 text-center text-sm text-slate-600 dark:text-slate-300 font-medium">2.890</span>
                                <span class="w-12 text-center text-sm text-slate-600 dark:text-slate-300">6</span>
                            </div>
                            <div class="px-4 py-3 flex items-center gap-3">
                                <span class="w-8 text-sm font-bold text-slate-400">3</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-slate-900 dark:text-white truncate">sonnoqualita.it/materassi-guida</div>
                                    <div class="text-xs text-slate-400">Materassi: Guida all'Acquisto 2026</div>
                                </div>
                                <span class="w-16 text-center text-sm text-slate-600 dark:text-slate-300 font-medium">2.150</span>
                                <span class="w-12 text-center text-sm text-slate-600 dark:text-slate-300">5</span>
                            </div>
                        </div>
                        <!-- Bottom summary -->
                        <div class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-200 dark:border-amber-800/30">
                            <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Media: 2.760 parole &bull; 6.3 heading H2 &bull; Argomenti comuni: materiali, dimensioni, budget</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Brief strategici (visual LEFT, text RIGHT, bg slate-50) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Text side (order-2 on lg) -->
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Brief strategici generati dall'AI</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">Dopo l'analisi, l'AI crea un brief editoriale completo: struttura heading H2/H3 consigliata, argomenti chiave da coprire, domande degli utenti a cui rispondere, e lunghezza target. Puoi modificare il brief prima della generazione dell'articolo. Per <strong class="text-amber-600 dark:text-amber-400">'ricette pasta al forno'</strong>, il brief includerà 'Varianti regionali', 'Tempi di cottura', 'Suggerimenti per la besciamella'.</p>
                    <a href="<?= url($baseUrl . '/articles') ?>" class="inline-flex items-center mt-6 text-amber-600 dark:text-amber-400 font-medium hover:text-amber-700 dark:hover:text-amber-300 transition-colors">
                        Esplora i brief AI
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <!-- Visual side (order-1 on lg): Brief preview -->
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Brief: ricette pasta al forno</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Generato</span>
                        </div>
                        <div class="p-5">
                            <!-- Heading tree -->
                            <div class="space-y-2 mb-5">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Struttura consigliata</p>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono font-bold text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 px-1.5 py-0.5 rounded">H1</span>
                                    <span class="text-sm text-slate-900 dark:text-white">Ricette Pasta al Forno: Guida Completa</span>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded">H2</span>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Varianti Regionali Italiane</span>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded">H2</span>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Ingredienti e Preparazione Base</span>
                                </div>
                                <div class="flex items-center gap-2 ml-8">
                                    <span class="text-[10px] font-mono font-bold text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 px-1.5 py-0.5 rounded">H3</span>
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Suggerimenti per la Besciamella</span>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded">H2</span>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Tempi di Cottura e Temperatura</span>
                                </div>
                            </div>
                            <!-- Checklist -->
                            <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Argomenti da coprire</p>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-sm text-slate-700 dark:text-slate-300">Tipi di pasta consigliati</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-sm text-slate-700 dark:text-slate-300">Formaggi e condimenti</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-sm text-slate-700 dark:text-slate-300">FAQ: domande frequenti degli utenti</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Target word count -->
                            <div class="mt-4 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                                <span>Target: <strong class="text-slate-700 dark:text-slate-300">2.500 parole</strong></span>
                                <span>Competitor avg: <strong class="text-slate-700 dark:text-slate-300">2.430</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Articoli SEO (text LEFT, visual RIGHT, bg white) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Text side -->
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Articoli SEO scritti dal tuo assistente AI</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">L'AI genera articoli completi da 1.500 a 3.000 parole, ottimizzati per la keyword target. Meta title, meta description, struttura heading, internal linking suggestions — tutto incluso. Puoi editare il contenuto direttamente in piattaforma prima della pubblicazione.</p>
                    <a href="<?= url($baseUrl . '/articles') ?>" class="inline-flex items-center mt-6 text-amber-600 dark:text-amber-400 font-medium hover:text-amber-700 dark:hover:text-amber-300 transition-colors">
                        Vedi gli articoli
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <!-- Visual side: Mini editor -->
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <!-- Editor toolbar -->
                        <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex gap-1">
                                    <div class="w-7 h-7 rounded bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-xs font-bold text-slate-500 dark:text-slate-400">B</div>
                                    <div class="w-7 h-7 rounded bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-xs italic text-slate-500 dark:text-slate-400">I</div>
                                    <div class="w-7 h-7 rounded bg-slate-200 dark:bg-slate-600 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Pronto per pubblicare</span>
                        </div>
                        <!-- Article content area -->
                        <div class="p-5">
                            <div class="text-lg font-bold text-slate-900 dark:text-white mb-1">Come Scegliere il Materasso Perfetto: Guida Completa 2026</div>
                            <div class="text-xs text-slate-400 mb-4">meta: Come scegliere il materasso giusto? Scopri materiali, dimensioni e budget nella nostra guida completa...</div>
                            <!-- Fake content lines -->
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">Tipologie di Materassi a Confronto</div>
                                    <div class="space-y-1">
                                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-full"></div>
                                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-11/12"></div>
                                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-10/12"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">Come Scegliere le Dimensioni</div>
                                    <div class="space-y-1">
                                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-full"></div>
                                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded w-4/5"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Bottom SEO bar -->
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div class="flex gap-2">
                                <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">SEO: 94/100</span>
                                <span class="text-[10px] px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-medium">2.680 parole</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">Meta ✓</span>
                                <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">H2/H3 ✓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 4: WordPress publishing (visual LEFT, text RIGHT, bg slate-50) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Text side (order-2 on lg) -->
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Pubblica su WordPress con un click</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">Connetti uno o più siti WordPress e pubblica direttamente gli articoli generati. Cover image generata con DALL-E 3, scheduling automatico per programmare la pubblicazione, supporto categorie e tag. Esempio: <strong class="text-amber-600 dark:text-amber-400">programma 5 articoli alla settimana, ogni lunedì alle 9:00</strong>.</p>
                    <a href="<?= url('/ai-content/wordpress') ?>" class="inline-flex items-center mt-6 text-amber-600 dark:text-amber-400 font-medium hover:text-amber-700 dark:hover:text-amber-300 transition-colors">
                        Gestisci siti WordPress
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <!-- Visual side (order-1 on lg): WordPress publishing card -->
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 19.5c-5.247 0-9.5-4.253-9.5-9.5S6.753 2.5 12 2.5s9.5 4.253 9.5 9.5-4.253 9.5-9.5 9.5z"/><path d="M3.5 12l2.6 7.2L9.5 9.8l3.5 9.4 4.8-13.4"/></svg>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">blog.esempio.it</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Connesso</span>
                        </div>
                        <!-- Scheduled articles list -->
                        <div class="p-4 space-y-3">
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white truncate">Come Scegliere un Materasso</div>
                                    <div class="text-xs text-amber-600 dark:text-amber-400">Programmato: Lun 24 Feb, 09:00</div>
                                </div>
                                <span class="text-[10px] px-2 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium flex-shrink-0">Schedulato</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/30">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white truncate">Ricette Pasta al Forno</div>
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400">Pubblicato: Lun 17 Feb, 09:00</div>
                                </div>
                                <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium flex-shrink-0">Online</span>
                            </div>
                        </div>
                        <!-- Cover image indicator -->
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Cover DALL-E 3
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Categorie auto
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Scheduling
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ COSA PUOI FARE ═══════════ -->
    <div class="mt-20">
        <div class="text-center mb-12">
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con AI Content</h3>
            <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dalla creazione di articoli blog alla gestione di interi cluster tematici</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card 1: Contenuti per blog -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Contenuti per blog</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Articoli long-form ottimizzati per keyword informazionali. Da 1.500 a 3.000 parole con struttura SEO completa.</p>
            </div>

            <!-- Card 2: Pagine prodotto -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Pagine prodotto</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Descrizioni SEO per e-commerce con focus sulla conversione. Ottimizzate per keyword transazionali.</p>
            </div>

            <!-- Card 3: Guide complete -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Guide complete</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Tutorial step-by-step approfonditi su argomenti complessi. Ideali per posizionarsi su keyword a coda lunga.</p>
            </div>

            <!-- Card 4: Cluster tematici -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Cluster tematici</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Serie di articoli interconnessi su un topic. Costruisci autorità tematica con contenuti che si supportano a vicenda.</p>
            </div>

            <!-- Card 5: Refresh contenuti -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Refresh contenuti</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Riscrivi e ottimizza articoli esistenti con dati SERP aggiornati. Migliora il posizionamento di contenuti già pubblicati.</p>
            </div>

            <!-- Card 6: Meta tags in bulk -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Meta tags in bulk</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">Genera meta title e description per pagine esistenti. Ottimizzazione rapida dell'intero sito.</p>
            </div>
        </div>
    </div>

    <!-- ═══════════ FAQ ═══════════ -->
    <div class="mt-20" x-data="{ openFaq: null }">
        <div class="text-center mb-12">
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Domande frequenti</h3>
        </div>
        <div class="max-w-3xl mx-auto space-y-3">
            <!-- FAQ 1 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 1 ? openFaq = null : openFaq = 1" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Quanti crediti costa generare un articolo?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Il processo completo prevede: estrazione SERP (<strong><?= $creditCosts['serp'] ?? 3 ?> crediti</strong>), scraping dei contenuti competitor (<strong><?= $creditCosts['scrape'] ?? 1 ?> credito</strong> per URL), e generazione dell'articolo AI (<strong><?= $creditCosts['article'] ?? 10 ?> crediti</strong>). La pubblicazione su WordPress è gratuita. In totale, un articolo completo costa circa 15-20 crediti.</p>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso modificare l'articolo prima della pubblicazione?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Sì, ogni articolo generato può essere modificato direttamente nell'editor integrato. Puoi cambiare testo, heading, meta tags e struttura. Le tue modifiche vengono salvate e il contenuto aggiornato viene utilizzato per la pubblicazione.</p>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 3 ? openFaq = null : openFaq = 3" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Come funziona la connessione WordPress?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Installa il plugin gratuito <strong>Ainstein WP Connect</strong> sul tuo sito WordPress. Poi inserisci l'URL del sito nelle impostazioni del progetto. Il plugin si autentica automaticamente e permette la pubblicazione diretta, incluse cover image, categorie e tag.</p>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 4 ? openFaq = null : openFaq = 4" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Che differenza c'è tra brief AI e generazione diretta?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Il <strong>brief AI</strong> è un piano strategico che analizza i competitor e suggerisce la struttura dell'articolo (heading, argomenti, lunghezza). La <strong>generazione diretta</strong> salta questo passaggio e scrive subito l'articolo. Il brief produce risultati migliori perché l'AI ha più contesto su cosa scrivere.</p>
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 5 ? openFaq = null : openFaq = 5" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">Posso importare keyword da altri moduli?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 5 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Sì, dal modulo <strong>Keyword Research</strong> puoi esportare le keyword del Piano Editoriale direttamente in AI Content. Le keyword vengono importate con volume di ricerca, difficoltà e priorità, pronte per la generazione degli articoli.</p>
                </div>
            </div>

            <!-- FAQ 6 -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === 6 ? openFaq = null : openFaq = 6" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white">L'AI supporta più lingue?</span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === 6 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 6" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Sì, Ainstein genera contenuti in italiano, inglese, francese, tedesco, spagnolo e portoghese. La lingua viene impostata a livello di progetto nelle impostazioni.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ CTA FINALE ═══════════ -->
    <div class="mt-20 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 p-8 lg:p-12">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Pronto a generare il tuo primo articolo SEO?</h3>
                    <p class="mt-2 text-amber-100">Inizia ora e pubblica contenuti ottimizzati in pochi minuti</p>
                </div>
                <a href="<?= url('/ai-content/projects') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-amber-600 font-semibold hover:bg-amber-50 shadow-lg transition-all flex-shrink-0">
                    Crea progetto
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
