<?php $currentPage = 'dashboard'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format(count($campaignRuns)) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Script Runs</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalCampaigns) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Campagne</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalAds) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Annunci</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format(count($evaluations)) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Valutazioni AI</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <?php if (empty($campaignRuns)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Configura lo Script</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Genera lo script Google Ads per iniziare a raccogliere automaticamente i dati delle tue campagne.
        </p>
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            Configura Script
        </a>
    </div>
    <?php else: ?>

    <!-- Latest Run Summary -->
    <?php if ($latestRun): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ultimo Run</h2>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/' . $latestRun['id']) ?>" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700">
                Dettagli
                <svg class="w-4 h-4 ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-slate-500 dark:text-slate-400">Data</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($latestRun['created_at'])) ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Tipo</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= e($latestRun['run_type']) ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Items</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= number_format($latestRun['items_received'] ?? 0) ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Periodo</p>
                <p class="font-medium text-slate-900 dark:text-white">
                    <?php if ($latestRun['date_range_start'] && $latestRun['date_range_end']): ?>
                    <?= date('d/m', strtotime($latestRun['date_range_start'])) ?> - <?= date('d/m/Y', strtotime($latestRun['date_range_end'])) ?>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Evaluations + Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Valutazioni recenti -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valutazioni AI</h2>
            </div>
            <?php if (empty($evaluations)): ?>
            <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                Nessuna valutazione ancora
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($evaluations as $eval): ?>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $eval['id']) ?>" class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white text-sm"><?= e($eval['name']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                <?= $eval['campaigns_evaluated'] ?> campagne, <?= $eval['ads_evaluated'] ?> annunci
                            </p>
                        </div>
                        <?php
                        $statusColors = [
                            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'analyzing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        ];
                        ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$eval['status']] ?? 'bg-slate-100 text-slate-600' ?>">
                            <?= ucfirst($eval['status']) ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Azioni rapide -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Azioni</h2>
            <div class="space-y-3">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Vedi Campagne</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Dati e performance campagne</p>
                    </div>
                </a>

                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Configura Script</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Genera e gestisci lo script Google Ads</p>
                    </div>
                </a>
            </div>
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
    </div>
</div>
