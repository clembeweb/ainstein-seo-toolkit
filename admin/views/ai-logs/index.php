<?php
$stats = $stats ?? ['last_24h' => [], 'last_30d' => [], 'by_provider' => [], 'top_model' => null, 'daily_trend' => []];
$filters = $filters ?? [];
$modulesList = $modulesList ?? [];
$logs = $logs ?? [];
$pagination = $pagination ?? ['current' => 1, 'total' => 1, 'total_items' => 0, 'per_page' => 50];

// Provider colors
$providerCardColors = [
    'anthropic' => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'border' => 'border-orange-200 dark:border-orange-800', 'text' => 'text-orange-700 dark:text-orange-300', 'accent' => 'bg-orange-500'],
    'openai'    => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-200 dark:border-emerald-800', 'text' => 'text-emerald-700 dark:text-emerald-300', 'accent' => 'bg-emerald-500'],
];

$providerNames = [
    'anthropic' => 'Anthropic (Claude)',
    'openai'    => 'OpenAI (GPT)',
];
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
              x-data @submit.prevent="window.ainstein.confirm('Eliminare i log più vecchi di 30 giorni?', {destructive: true}).then(() => $el.submit())">
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
    <?php
    $total24h = max(1, $stats['last_24h']['total'] ?? 1);
    $successRate = round((($stats['last_24h']['success'] ?? 0) / $total24h) * 100);
    $fallbacks24h = $stats['last_24h']['fallbacks'] ?? 0;
    $fallbackRate = $total24h > 1 ? round(($fallbacks24h / $total24h) * 100) : 0;
    $avgCost = ($stats['last_30d']['total'] ?? 0) > 0 ? ($stats['last_30d']['cost'] ?? 0) / ($stats['last_30d']['total'] ?? 1) : 0;
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['last_24h']['total'] ?? 0) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Chiamate 24h</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full" style="width: <?= $successRate ?>%"></div>
            </div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= $successRate ?>% successo</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['last_24h']['tokens'] ?? 0) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Token 24h</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">30gg: <?= number_format($stats['last_30d']['tokens'] ?? 0) ?></div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">$<?= number_format($stats['last_30d']['cost'] ?? 0, 4) ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Costo 30 giorni</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">Media: $<?= number_format($avgCost, 4) ?>/chiamata</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?= $fallbackRate ?>%</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Tasso fallback</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400"><?= $fallbacks24h ?> fallback nelle 24h</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white truncate" title="<?= e($stats['top_model']['model'] ?? '-') ?>">
                        <?= e($stats['top_model']['model'] ?? '-') ?>
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Modello top</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
            <?php if ($stats['top_model']): ?>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400"><?= number_format($stats['top_model']['cnt'] ?? 0) ?> chiamate</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Provider Breakdown -->
    <?php if (!empty($stats['by_provider'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider">Breakdown per Provider (30 giorni)</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($stats['by_provider'] as $provider):
                $key = $provider['provider'];
                $colors = $providerCardColors[$key] ?? ['bg' => 'bg-slate-50 dark:bg-slate-700/50', 'border' => 'border-slate-200 dark:border-slate-700', 'text' => 'text-slate-700 dark:text-slate-300', 'accent' => 'bg-slate-500'];
                $name = $providerNames[$key] ?? ucfirst($key);
                $calls = (int)$provider['calls'];
                $errors = (int)$provider['errors'];
                $fallbacks = (int)$provider['fallbacks'];
                $tokens = (int)$provider['tokens'];
                $cost = (float)$provider['cost'];
                $rate = $calls > 0 ? round((($calls - $errors) / $calls) * 100) : 100;
            ?>
                <div class="<?= $colors['bg'] ?> border <?= $colors['border'] ?> rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full <?= $colors['accent'] ?>"></div>
                            <span class="text-base font-semibold <?= $colors['text'] ?>"><?= e($name) ?></span>
                        </div>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= $rate ?>% successo</span>
                    </div>
                    <div class="grid grid-cols-4 gap-3 text-center">
                        <div>
                            <div class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($calls) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Chiamate</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($tokens) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Token</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">$<?= number_format($cost, 4) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Costo</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold <?= $errors > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' ?>"><?= $errors ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Errori</div>
                        </div>
                    </div>
                    <!-- Success rate bar -->
                    <div class="mt-3 h-1.5 bg-white/60 dark:bg-slate-600/50 rounded-full overflow-hidden">
                        <div class="h-full rounded-full <?= $rate >= 80 ? 'bg-green-500' : ($rate >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>" style="width: <?= $rate ?>%"></div>
                    </div>
                    <?php if ($fallbacks > 0): ?>
                    <div class="mt-2 text-xs text-yellow-600 dark:text-yellow-400"><?= $fallbacks ?> fallback</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Trend Giornaliero (Chart) -->
    <?php if (!empty($stats['daily_trend'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Trend Chiamate AI (ultimi 7 giorni)</h3>
        <div class="h-48">
            <canvas id="aiTrendChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <!-- Date From -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Da</label>
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">A</label>
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Modulo -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Modulo</label>
                <select name="module" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
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
                <select name="provider" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti</option>
                    <option value="anthropic" <?= ($filters['provider'] ?? '') === 'anthropic' ? 'selected' : '' ?>>Anthropic</option>
                    <option value="openai" <?= ($filters['provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Successo</option>
                    <option value="error" <?= ($filters['status'] ?? '') === 'error' ? 'selected' : '' ?>>Errore</option>
                    <option value="fallback" <?= ($filters['status'] ?? '') === 'fallback' ? 'selected' : '' ?>>Fallback</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
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
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Token</th>
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
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">OpenAI</span>
                                    <?php endif; ?>
                                    <?php if (!empty($log['fallback_from'])): ?>
                                        <span class="text-xs text-yellow-600 dark:text-yellow-400 block">fallback da <?= e($log['fallback_from']) ?></span>
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
                                            Successo
                                        </span>
                                    <?php elseif ($log['status'] === 'error'): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            Errore
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            Fallback
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= url('/admin/ai-logs/' . $log['id']) ?>"
                                       class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300" title="Dettaglio">
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

                    <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-lg font-medium">
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

<?php if (!empty($stats['daily_trend'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const trendData = <?= json_encode($stats['daily_trend']) ?>;
    const labels = trendData.map(d => {
        const date = new Date(d.day);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
    });

    new Chart(document.getElementById('aiTrendChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Chiamate',
                    data: trendData.map(d => parseInt(d.total)),
                    borderColor: '#006e96',
                    backgroundColor: 'rgba(0, 110, 150, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Errori',
                    data: trendData.map(d => parseInt(d.errors)),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: { color: textColor, boxWidth: 12, padding: 16, font: { size: 11 } }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });
});
</script>
<?php endif; ?>
