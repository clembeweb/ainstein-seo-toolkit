<div class="space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Configurazione Script
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Storico Esecuzioni Script</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Progetto: <?= e($project['name']) ?>
        </p>
    </div>

    <!-- Stats Cards -->
    <?php
    $totalRuns = count($runs);
    $lastRunDate = !empty($runs) ? date('d/m/Y H:i', strtotime($runs[0]['created_at'])) : 'Mai';
    $totalItems = array_sum(array_column($runs, 'items_received'));
    ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Totale esecuzioni -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalRuns) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Totale esecuzioni</p>
                </div>
            </div>
        </div>

        <!-- Ultima esecuzione -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $lastRunDate ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ultima esecuzione</p>
                </div>
            </div>
        </div>

        <!-- Items totali ricevuti -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalItems) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Items totali ricevuti</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($runs)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessuna esecuzione registrata</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Configura e avvia lo script da Google Ads.
            </p>
            <div class="mt-6">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Configura Script
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Runs Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Esecuzioni</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Periodo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Versione</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php
                    $typeLabels = [
                        'search_terms' => 'Termini',
                        'campaign_performance' => 'Campagne',
                        'both' => 'Entrambi'
                    ];
                    $statusColors = [
                        'received' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                        'processing' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                        'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                    ];
                    $statusLabels = [
                        'received' => 'Ricevuto',
                        'processing' => 'In elaborazione',
                        'completed' => 'Completato',
                        'error' => 'Errore'
                    ];
                    ?>
                    <?php foreach ($runs as $run): ?>
                    <?php
                    $isClickable = $run['status'] === 'completed' && in_array($run['run_type'], ['campaign_performance', 'both']);
                    $rowUrl = $isClickable ? url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/' . $run['id']) : '';
                    ?>
                    <tr class="<?= $isClickable ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors' : '' ?>"
                        <?= $isClickable ? 'onclick="window.location.href=\'' . $rowUrl . '\'"' : '' ?>
                        <?= !empty($run['error_message']) ? 'title="' . e($run['error_message']) . '"' : '' ?>
                    >
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                            <?= date('d/m/Y H:i', strtotime($run['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                            <?= $typeLabels[$run['run_type']] ?? e($run['run_type']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                            <?php if (!empty($run['date_range_start']) && !empty($run['date_range_end'])): ?>
                                <?= date('d/m/Y', strtotime($run['date_range_start'])) ?> - <?= date('d/m/Y', strtotime($run['date_range_end'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-medium">
                            <?= number_format($run['items_received'] ?? 0) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$run['status']] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                                <?php if ($run['status'] === 'error'): ?>
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                <?php endif; ?>
                                <?= $statusLabels[$run['status']] ?? e($run['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                            <?= e($run['script_version'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
