<?php
$currentPage = 'dashboard';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6">
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Score Medio -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Score Medio</p>
                    <?php
                    $avgScore = $project['stats']['avg_score'] ?? null;
                    $scoreColor = $avgScore >= 80 ? 'text-emerald-600 dark:text-emerald-400' :
                                 ($avgScore >= 60 ? 'text-amber-600 dark:text-amber-400' :
                                 ($avgScore ? 'text-red-600 dark:text-red-400' : 'text-slate-400'));
                    ?>
                    <p class="text-2xl font-bold <?= $scoreColor ?>"><?= $avgScore ? round($avgScore) : '-' ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pagine Analizzate -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pagine</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?= $project['stats']['pages_completed'] ?? 0 ?> / <?= $project['stats']['pages_total'] ?? 0 ?>
                    </p>
                </div>
                <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Issues Critici -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Issues Critici</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $project['stats']['issues_critical'] ?? 0 ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Issues Warning -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Avvisi</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $project['stats']['issues_warning'] ?? 0 ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-3">
        <?php if (($project['stats']['pages_total'] ?? 0) === 0): ?>
        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/import') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Importa Pagine
        </a>
        <?php else: ?>
        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/audit') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Avvia Audit
        </a>
        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/import') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Aggiungi Pagine
        </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pagine Problematiche -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pagine da Ottimizzare</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Le pagine con score piu basso</p>
            </div>
            <?php if (empty($problematicPages)): ?>
            <div class="p-8 text-center">
                <p class="text-slate-500 dark:text-slate-400">Nessuna pagina analizzata</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($problematicPages as $page): ?>
                <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/' . $page['id']) ?>" class="flex items-center justify-between p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= e(parse_url($page['url'], PHP_URL_PATH) ?: '/') ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($page['title'] ?? 'Senza titolo') ?></p>
                    </div>
                    <div class="ml-4 flex items-center gap-2">
                        <?php
                        $pageScore = $page['onpage_score'] ?? 0;
                        $pageBg = $pageScore >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                 ($pageScore >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300');
                        ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $pageBg ?>">
                            <?= $pageScore ?>
                        </span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Issues per Categoria -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Issues per Categoria</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Distribuzione dei problemi rilevati</p>
            </div>
            <?php if (empty($issuesByCategory)): ?>
            <div class="p-8 text-center">
                <p class="text-slate-500 dark:text-slate-400">Nessun issue rilevato</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php
                $categoryLabels = [
                    'meta' => 'Meta Tags',
                    'content' => 'Contenuto',
                    'images' => 'Immagini',
                    'links' => 'Link',
                    'technical' => 'Tecnico',
                    'performance' => 'Performance',
                ];
                foreach ($issuesByCategory as $cat):
                ?>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= $categoryLabels[$cat['category']] ?? ucfirst($cat['category']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= $cat['total'] ?> issues totali</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($cat['critical'] > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                            <?= $cat['critical'] ?> critici
                        </span>
                        <?php endif; ?>
                        <?php if ($cat['warning'] > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                            <?= $cat['warning'] ?> avvisi
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Issues Comuni -->
    <?php if (!empty($commonIssues)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Issues Piu Comuni</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">I problemi che si ripetono su piu pagine</p>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($commonIssues as $issue): ?>
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <?php
                    $severityColor = match($issue['severity']) {
                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                        default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $severityColor ?>">
                        <?= $issue['severity'] === 'critical' ? 'Critico' : ($issue['severity'] === 'warning' ? 'Avviso' : 'Info') ?>
                    </span>
                    <p class="text-sm text-slate-900 dark:text-white"><?= e($issue['message']) ?></p>
                </div>
                <span class="text-sm text-slate-500 dark:text-slate-400"><?= $issue['occurrences'] ?> pagine</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
