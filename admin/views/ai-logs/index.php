<?php
$stats = $stats ?? ['last_24h' => [], 'last_30d' => []];
$filters = $filters ?? [];
$modulesList = $modulesList ?? [];
$logs = $logs ?? [];
$pagination = $pagination ?? ['current' => 1, 'total' => 1, 'total_items' => 0, 'per_page' => 50];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Logs</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cronologia chiamate AI per debug e monitoraggio</p>
        </div>

        <!-- Cleanup Button -->
        <form method="POST" action="<?= url('/admin/ai-logs/cleanup') ?>"
              onsubmit="return confirm('Eliminare i log piu vecchi di 30 giorni?')">
            <?= csrf_field() ?>
            <input type="hidden" name="days" value="30">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Cleanup > 30 giorni
            </button>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['last_24h']['total'] ?? 0) ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Chiamate 24h</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?= number_format($stats['last_24h']['success'] ?? 0) ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Successi 24h</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['last_24h']['errors'] ?? 0) ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Errori 24h</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">$<?= number_format($stats['last_30d']['cost'] ?? 0, 4) ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Costo 30 giorni</div>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <!-- Date From -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Da</label>
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">A</label>
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <!-- Modulo -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Modulo</label>
                <select name="module" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Tutti</option>
                    <?php foreach ($modulesList as $module): ?>
                        <option value="<?= e($module) ?>" <?= ($filters['module'] ?? '') === $module ? 'selected' : '' ?>>
                            <?= e($module) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Provider -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Provider</label>
                <select name="provider" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Tutti</option>
                    <option value="anthropic" <?= ($filters['provider'] ?? '') === 'anthropic' ? 'selected' : '' ?>>Anthropic</option>
                    <option value="openai" <?= ($filters['provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Tutti</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="error" <?= ($filters['status'] ?? '') === 'error' ? 'selected' : '' ?>>Error</option>
                    <option value="fallback" <?= ($filters['status'] ?? '') === 'fallback' ? 'selected' : '' ?>>Fallback</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium transition-colors">
                    Filtra
                </button>
                <a href="<?= url('/admin/ai-logs') ?>" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 text-sm font-medium transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tabella Logs -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data/Ora</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modulo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modello</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tokens</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durata</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                Nessun log trovato
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <?= e($log['module_slug']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($log['provider'] === 'anthropic'): ?>
                                        <span class="text-orange-600 dark:text-orange-400 font-medium">Anthropic</span>
                                    <?php else: ?>
                                        <span class="text-green-600 dark:text-green-400 font-medium">OpenAI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 truncate max-w-[150px]" title="<?= e($log['model']) ?>">
                                    <?= e($log['model']) ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-slate-600 dark:text-slate-300">
                                    <span title="In: <?= number_format($log['tokens_input']) ?> | Out: <?= number_format($log['tokens_output']) ?>">
                                        <?= number_format($log['tokens_total']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-slate-600 dark:text-slate-300">
                                    <?= number_format($log['duration_ms']) ?>ms
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-slate-600 dark:text-slate-300">
                                    $<?= number_format($log['estimated_cost'], 4) ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            Success
                                        </span>
                                    <?php elseif ($log['status'] === 'error'): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            Error
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            Fallback
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= url('/admin/ai-logs/' . $log['id']) ?>"
                                       class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300" title="Dettaglio">
                                        <svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginazione -->
        <?php if ($pagination['total'] > 1): ?>
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    Mostrando <?= (($pagination['current'] - 1) * $pagination['per_page']) + 1 ?> -
                    <?= min($pagination['current'] * $pagination['per_page'], $pagination['total_items']) ?>
                    di <?= number_format($pagination['total_items']) ?>
                </div>
                <div class="flex gap-2">
                    <?php if ($pagination['current'] > 1): ?>
                        <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1])) ?>"
                           class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg font-medium">
                        <?= $pagination['current'] ?> / <?= $pagination['total'] ?>
                    </span>

                    <?php if ($pagination['current'] < $pagination['total']): ?>
                        <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1])) ?>"
                           class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
