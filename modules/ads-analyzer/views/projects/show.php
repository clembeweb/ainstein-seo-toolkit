<?php $currentPage = 'dashboard'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">

    <!-- Status Badge -->
    <div class="flex items-center gap-4">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
            <?php if ($project['status'] === 'completed'): ?>
            bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
            <?php elseif ($project['status'] === 'analyzing'): ?>
            bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
            <?php elseif ($project['status'] === 'archived'): ?>
            bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
            <?php else: ?>
            bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300
            <?php endif; ?>
        ">
            <?= ucfirst($project['status']) ?>
        </span>
        <span class="text-sm text-slate-500 dark:text-slate-400">
            Creato il <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
        </span>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_ad_groups']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ad Groups</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($termStats['total_terms'] ?? 0) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Termini</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalNegatives) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Negative</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($selectedCount) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Selezionate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Ad Groups Table -->
    <?php if (!empty($adGroups)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ad Groups</h2>
        </div>
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Termini</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">CTR 0%</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Negative</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($adGroups as $adGroup): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                        <?= e($adGroup['name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= number_format($adGroup['terms_count']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= number_format($adGroup['zero_ctr_count']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                        <?= number_format($adGroup['negatives_count'] ?? 0) ?>
                        <?php if (($adGroup['selected_count'] ?? 0) > 0): ?>
                        <span class="text-emerald-600">(<?= $adGroup['selected_count'] ?> sel.)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php if ($adGroup['analysis_status'] === 'completed'): ?>
                            bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                            <?php elseif ($adGroup['analysis_status'] === 'analyzing'): ?>
                            bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                            <?php elseif ($adGroup['analysis_status'] === 'error'): ?>
                            bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300
                            <?php else: ?>
                            bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400
                            <?php endif; ?>
                        ">
                            <?= ucfirst($adGroup['analysis_status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Recent Analyses -->
    <?php if (!empty($recentAnalyses)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Analisi Recenti</h2>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses') ?>" class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 font-medium">
                Vedi tutte (<?= $totalAnalyses ?>)
            </a>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($recentAnalyses as $analysis): ?>
            <div class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses/' . $analysis['id']) ?>" class="font-medium text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">
                            <?= e($analysis['name']) ?>
                        </a>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            <?= date('d/m/Y H:i', strtotime($analysis['created_at'])) ?>
                            <?php if ($analysis['total_keywords'] > 0): ?>
                            - <?= $analysis['total_keywords'] ?> keyword trovate
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php
                        $statusColors = [
                            'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                            'analyzing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                        ];
                        $statusColor = $statusColors[$analysis['status']] ?? $statusColors['draft'];
                        ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                            <?= ucfirst($analysis['status']) ?>
                        </span>
                        <?php if ($analysis['status'] === 'completed'): ?>
                        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses/' . $analysis['id']) ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/delete') ?>" method="POST"
              x-data @submit.prevent="window.ainstein.confirm('Eliminare questo progetto e tutti i dati associati?', {destructive: true}).then(() => $el.submit())">
            <?= csrf_field() ?>
            <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                Elimina progetto
            </button>
        </form>

        <div class="flex gap-3">
            <?php if ($project['total_ad_groups'] > 0): ?>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyses') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Storico Analisi
            </a>
            <?php endif; ?>

            <?php if ($project['status'] === 'draft' && $project['total_ad_groups'] > 0): ?>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/landing-urls') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                Continua Analisi
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php elseif ($project['status'] === 'completed' && $project['total_ad_groups'] > 0): ?>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/landing-urls') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Analisi
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
