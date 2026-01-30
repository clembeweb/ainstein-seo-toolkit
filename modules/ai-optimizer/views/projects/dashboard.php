<?php
/**
 * Dashboard progetto AI Optimizer
 */
$currentPage = 'dashboard';
?>

<div class="space-y-6">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['total'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-primary-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= ($stats['imported'] ?? 0) + ($stats['analyzed'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">In lavorazione</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= ($stats['refactored'] ?? 0) + ($stats['exported'] ?? 0) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Completati</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['failed'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Falliti</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista Ottimizzazioni -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Articoli Ottimizzati</h3>
            <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize') ?>"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo
            </a>
        </div>

        <?php if (empty($optimizations)): ?>
        <div class="p-8 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun articolo</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-4">Inizia ottimizzando il tuo primo articolo.</p>
            <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ottimizza Articolo
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">URL</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Score</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Parole</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($optimizations as $opt): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-900 dark:text-white"><?= e($opt['keyword']) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= e($opt['original_url']) ?>" target="_blank"
                               class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate block max-w-xs"
                               title="<?= e($opt['original_url']) ?>">
                                <?= e(strlen($opt['original_url']) > 40 ? substr($opt['original_url'], 0, 40) . '...' : $opt['original_url']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($opt['seo_score']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $opt['seo_score'] >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : ($opt['seo_score'] >= 40 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300') ?>">
                                <?= $opt['seo_score'] ?>/100
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $statusColors = [
                                'imported' => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
                                'analyzing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                                'analyzed' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
                                'refactoring' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
                                'refactored' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'exported' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                                'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                            ];
                            $statusLabels = [
                                'imported' => 'Importato',
                                'analyzing' => 'Analisi...',
                                'analyzed' => 'Analizzato',
                                'refactoring' => 'Riscrittura...',
                                'refactored' => 'Riscritto',
                                'exported' => 'Esportato',
                                'failed' => 'Fallito',
                            ];
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$opt['status']] ?? 'bg-slate-100 text-slate-800' ?>">
                                <?= $statusLabels[$opt['status']] ?? $opt['status'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-slate-600 dark:text-slate-300">
                            <?php if ($opt['optimized_word_count']): ?>
                            <span class="text-emerald-600 dark:text-emerald-400"><?= number_format($opt['optimized_word_count']) ?></span>
                            <span class="text-slate-400">/</span>
                            <?php endif; ?>
                            <span><?= number_format($opt['original_word_count']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize/' . $opt['id']) ?>"
                               class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 rounded hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                <?= in_array($opt['status'], ['refactored', 'exported']) ? 'Visualizza' : 'Continua' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
