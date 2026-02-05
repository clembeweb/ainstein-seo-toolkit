<?php
$currentPage = 'pages';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pagine</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= count($pages) ?> pagine nel progetto</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/import') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Importa Pagine
            </a>
        </div>
    </div>

    <?php if (empty($pages)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna pagina</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Importa le pagine del tuo sito per iniziare l'analisi.</p>
        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/import') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Importa da Sitemap
        </a>
    </div>
    <?php else: ?>
    <!-- Pages Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Issues</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($pages as $page): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <div class="min-w-0">
                                <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/' . $page['id']) ?>"
                                   class="text-sm font-medium text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 truncate block max-w-md">
                                    <?= e(parse_url($page['url'], PHP_URL_PATH) ?: '/') ?>
                                </a>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-md"><?= e($page['title'] ?? $page['url']) ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($page['onpage_score'] !== null): ?>
                            <?php
                            $score = $page['onpage_score'];
                            $scoreBg = $score >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                      ($score >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                      'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300');
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?= $scoreBg ?>">
                                <?= $score ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if (isset($page['issues_critical']) && $page['issues_critical'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                <?= $page['issues_critical'] ?>
                            </span>
                            <?php endif; ?>
                            <?php if (isset($page['issues_warning']) && $page['issues_warning'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 ml-1">
                                <?= $page['issues_warning'] ?>
                            </span>
                            <?php endif; ?>
                            <?php if (empty($page['issues_critical']) && empty($page['issues_warning'])): ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                'analyzing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                            ];
                            $statusLabels = [
                                'pending' => 'In attesa',
                                'analyzing' => 'Analisi...',
                                'completed' => 'Completata',
                                'error' => 'Errore',
                            ];
                            $status = $page['status'] ?? 'pending';
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                <?= $statusLabels[$status] ?? $status ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages/' . $page['id']) ?>"
                               class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 text-sm font-medium">
                                Dettagli
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
