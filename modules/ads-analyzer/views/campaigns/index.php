<?php $currentPage = 'campaigns'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="campaignPageManager()">

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Campagne -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_campaigns'] ?? 0) ?></p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Campagne</p>
                </div>
            </div>
        </div>

        <!-- Costo totale -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_cost'] ?? 0, 2, ',', '.') ?> &euro;</p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Costo totale</p>
                </div>
            </div>
        </div>

        <!-- Click totali -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_clicks'] ?? 0) ?></p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Click totali</p>
                </div>
            </div>
        </div>

        <!-- Conversioni -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_conversions'] ?? 0, 1) ?></p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Conversioni</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$latestRun): ?>
    <!-- No data state -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Nessun dato ricevuto</p>
                <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                    Configura lo script Google Ads per iniziare a ricevere dati delle campagne automaticamente.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- Valuta con AI Button -->
                <button
                    @click="startEvaluation()"
                    :disabled="loading || !canEvaluate"
                    class="inline-flex items-center px-4 py-2 rounded-lg font-medium transition-colors"
                    :class="canEvaluate && !loading
                        ? 'bg-amber-600 text-white hover:bg-amber-700'
                        : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed'"
                >
                    <template x-if="!loading">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </template>
                    <template x-if="loading">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="loading ? 'Avvio valutazione...' : 'Valuta con AI'"></span>
                </button>
                <?php $evalCost = \Core\Credits::getCost('campaign_evaluation', 'ads-analyzer', 7); ?>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-medium">Costo:</span> <?= number_format($evalCost, 1) ?> crediti
                    <span class="mx-1">&bull;</span>
                    <span>Disponibili: <strong class="<?= $userCredits >= $evalCost ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>"><?= number_format($userCredits, 1) ?></strong></span>
                </div>
            </div>

            <!-- Script Setup Link -->
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                Configura Script
            </a>
        </div>

        <!-- Error message -->
        <template x-if="errorMsg">
            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300" x-text="errorMsg"></div>
        </template>
    </div>

    <!-- Run List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ricezioni Dati</h2>
        </div>

        <?php if (empty($campaignRuns)): ?>
        <div class="p-12 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-4l-2-2H6a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <h3 class="text-sm font-medium text-slate-900 dark:text-white">Nessun dato ricevuto</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                I dati arriveranno automaticamente dopo aver configurato lo script Google Ads.
            </p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Periodo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Elementi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($campaignRuns as $run): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                            <?= date('d/m/Y H:i', strtotime($run['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php if ($run['run_type'] === 'scheduled'): ?>
                                bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300
                                <?php else: ?>
                                bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300
                                <?php endif; ?>
                            ">
                                <?= $run['run_type'] === 'scheduled' ? 'Schedulato' : 'Manuale' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                            <?php if (!empty($run['date_range_start']) && !empty($run['date_range_end'])): ?>
                            <?= date('d/m/Y', strtotime($run['date_range_start'])) ?> - <?= date('d/m/Y', strtotime($run['date_range_end'])) ?>
                            <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                            <?= number_format($run['items_received'] ?? 0) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?php
                            $runStatusColors = [
                                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                            ];
                            $runStatusLabels = [
                                'completed' => 'Completato',
                                'processing' => 'In elaborazione',
                                'error' => 'Errore',
                                'pending' => 'In attesa',
                            ];
                            $rsColor = $runStatusColors[$run['status']] ?? $runStatusColors['pending'];
                            $rsLabel = $runStatusLabels[$run['status']] ?? ucfirst($run['status']);
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $rsColor ?>">
                                <?= $rsLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <?php if ($run['status'] === 'completed'): ?>
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/' . $run['id']) ?>" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Valutazioni AI Section -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valutazioni AI</h2>
        </div>

        <?php if (empty($evaluations)): ?>
        <div class="p-12 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <h3 class="text-sm font-medium text-slate-900 dark:text-white">Nessuna valutazione</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Avvia una valutazione AI per analizzare le performance delle campagne.
            </p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($evaluations as $evaluation): ?>
            <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <?php if ($evaluation['status'] === 'completed'): ?>
                            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $evaluation['id']) ?>" class="font-medium text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 truncate">
                                <?= e($evaluation['name']) ?>
                            </a>
                            <?php else: ?>
                            <span class="font-medium text-slate-900 dark:text-white truncate">
                                <?= e($evaluation['name']) ?>
                            </span>
                            <?php endif; ?>

                            <?php
                            $evalStatusColors = [
                                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                            ];
                            $evalStatusLabels = [
                                'completed' => 'Completata',
                                'running' => 'In corso',
                                'error' => 'Errore',
                                'pending' => 'In attesa',
                            ];
                            $esColor = $evalStatusColors[$evaluation['status']] ?? $evalStatusColors['pending'];
                            $esLabel = $evalStatusLabels[$evaluation['status']] ?? ucfirst($evaluation['status']);
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium flex-shrink-0 <?= $esColor ?>">
                                <?= $esLabel ?>
                            </span>
                        </div>

                        <div class="mt-1.5 flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <?= date('d/m/Y H:i', strtotime($evaluation['created_at'])) ?>
                            </span>

                            <?php if (!empty($evaluation['campaigns_evaluated'])): ?>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                <?= number_format($evaluation['campaigns_evaluated']) ?> campagne
                            </span>
                            <?php endif; ?>

                            <?php if (!empty($evaluation['ads_evaluated'])): ?>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <?= number_format($evaluation['ads_evaluated']) ?> annunci
                            </span>
                            <?php endif; ?>

                            <?php if (!empty($evaluation['credits_used'])): ?>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?= number_format($evaluation['credits_used'], 1) ?> crediti
                            </span>
                            <?php endif; ?>

                            <?php if ($evaluation['status'] === 'completed' && !empty($evaluation['completed_at'])): ?>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Completata il <?= date('d/m/Y H:i', strtotime($evaluation['completed_at'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ml-4 flex-shrink-0">
                        <?php if ($evaluation['status'] === 'completed'): ?>
                        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $evaluation['id']) ?>" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <?php elseif ($evaluation['status'] === 'running'): ?>
                        <svg class="w-5 h-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Campaign Selection Modal -->
<template x-if="showCampaignModal">
    <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" @click="showCampaignModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full max-h-[80vh] flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Seleziona Campagne</h3>
                    <button @click="showCampaignModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">
                        Il progetto ha molte campagne. Seleziona quelle da valutare (max <span x-text="maxCampaigns"></span>).
                    </p>

                    <!-- Select All / None -->
                    <div class="flex items-center gap-3 mb-3">
                        <button @click="selectAllCampaigns()" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">Seleziona tutte</button>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <button @click="selectedCampaigns = []" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:underline">Deseleziona tutte</button>
                        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="selectedCampaigns.length"></span>/<span x-text="maxCampaigns"></span> selezionate
                        </span>
                    </div>

                    <!-- Campaign List -->
                    <div class="space-y-1.5">
                        <template x-for="c in allCampaigns" :key="c.id_google">
                            <label class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                                   :class="selectedCampaigns.includes(c.id_google) ? 'bg-amber-50 dark:bg-amber-900/20' : ''">
                                <input type="checkbox"
                                       :value="c.id_google"
                                       x-model="selectedCampaigns"
                                       :disabled="!selectedCampaigns.includes(c.id_google) && selectedCampaigns.length >= maxCampaigns"
                                       class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="c.name"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        <span x-text="c.type"></span> &bull;
                                        <span x-text="c.status"></span> &bull;
                                        Costo: <span x-text="c.cost.toFixed(2)"></span>&euro; &bull;
                                        Click: <span x-text="c.clicks"></span>
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-show="selectedCampaigns.length >= maxCampaigns">
                        Limite raggiunto
                    </p>
                    <div class="flex items-center gap-3 ml-auto">
                        <button @click="showCampaignModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                            Annulla
                        </button>
                        <button @click="doEvaluate(selectedCampaigns)"
                                :disabled="selectedCampaigns.length === 0"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="selectedCampaigns.length > 0 ? 'bg-amber-600 text-white hover:bg-amber-700' : 'bg-slate-200 text-slate-400 cursor-not-allowed'">
                            Valuta <span x-text="selectedCampaigns.length"></span> campagne
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Hidden CSRF token for AJAX -->
<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

<script>
function campaignPageManager() {
    return {
        loading: false,
        errorMsg: '',
        canEvaluate: <?= ($latestRun && $userCredits >= $evalCost) ? 'true' : 'false' ?>,
        showCampaignModal: false,
        allCampaigns: <?= json_encode($campaignsList ?? [], JSON_UNESCAPED_UNICODE) ?>,
        selectedCampaigns: [],
        maxCampaigns: 15,

        startEvaluation() {
            if (!this.canEvaluate || this.loading) return;

            // Se > 10 campagne, mostra modale di selezione
            if (this.allCampaigns.length > 10) {
                // Preseleziona top 10 ENABLED per costo
                this.selectedCampaigns = this.allCampaigns
                    .filter(c => c.status === 'ENABLED')
                    .sort((a, b) => b.cost - a.cost)
                    .slice(0, 10)
                    .map(c => c.id_google);
                this.showCampaignModal = true;
            } else {
                // <= 10 campagne: valuta tutte
                this.doEvaluate([]);
            }
        },

        selectAllCampaigns() {
            this.selectedCampaigns = this.allCampaigns
                .slice(0, this.maxCampaigns)
                .map(c => c.id_google);
        },

        async doEvaluate(filter) {
            this.showCampaignModal = false;
            this.loading = true;
            this.errorMsg = '';

            try {
                const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value;
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                if (filter && filter.length > 0) {
                    formData.append('campaigns_filter', JSON.stringify(filter));
                }

                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluate') ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!resp.ok) {
                    this.errorMsg = 'Valutazione avviata. L\'analisi potrebbe richiedere qualche minuto. Ricarica la pagina tra poco.';
                    this.loading = false;
                    setTimeout(() => location.reload(), 15000);
                    return;
                }

                const data = await resp.json();

                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.error) {
                    this.errorMsg = data.error;
                    this.loading = false;
                } else {
                    this.errorMsg = 'Errore imprevisto durante l\'avvio della valutazione.';
                    this.loading = false;
                }
            } catch (err) {
                console.error('Evaluation start failed:', err);
                this.errorMsg = 'Valutazione avviata. L\'analisi potrebbe richiedere qualche minuto. Ricarica la pagina tra poco.';
                this.loading = false;
                setTimeout(() => location.reload(), 15000);
            }
        }
    };
}
</script>
