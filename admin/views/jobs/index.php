<?php
$tabModules = $tabModules ?? [];
$activeTab = $activeTab ?? 'ai-content';
$jobs = $jobs ?? [];
$stats = $stats ?? [];
$globalStats = $globalStats ?? ['running' => 0, 'pending' => 0, 'errors_24h' => 0];
$users = $users ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['current' => 1, 'total' => 1, 'total_items' => 0, 'per_page' => 50];

$statusConfig = [
    'pending'   => ['label' => 'In Attesa',     'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'],
    'running'   => ['label' => 'In Esecuzione', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'],
    'completed' => ['label' => 'Completato',    'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
    'error'     => ['label' => 'Errore',        'class' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
    'cancelled' => ['label' => 'Cancellato',    'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'],
];
?>

<div class="space-y-6" x-data="jobsAdmin()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Jobs Monitor</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Monitoraggio centralizzato dei job in background</p>
        </div>
    </div>

    <!-- Global Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold <?= $globalStats['running'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-900 dark:text-white' ?>">
                        <?= $globalStats['running'] ?>
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">In Esecuzione (tutti i moduli)</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <?php if ($globalStats['running'] > 0): ?>
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                    <?php else: ?>
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold <?= $globalStats['pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?>">
                        <?= $globalStats['pending'] ?>
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">In Attesa (tutti i moduli)</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold <?= $globalStats['errors_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' ?>">
                        <?= $globalStats['errors_24h'] ?>
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Errori ultime 24h</div>
                </div>
                <div class="h-10 w-10 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-1 -mb-px overflow-x-auto">
            <?php foreach ($tabModules as $key => $mod): ?>
            <a href="<?= url('/admin/jobs?tab=' . $key) ?>"
               class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                      <?= $activeTab === $key
                          ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                          : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600' ?>">
                <?= e($mod['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Tab Stats -->
    <div class="grid grid-cols-2 md:grid-cols-7 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['running'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Running</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($stats['pending'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pending</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['completed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Completati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['errors'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Errori</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-slate-500 dark:text-slate-400"><?= number_format($stats['cancelled'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Cancellati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-purple-600 dark:text-purple-400"><?= number_format($stats['total_credits'] ?? 0, 1) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Crediti</p>
        </div>
    </div>

    <!-- Filters + Actions -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
            <!-- Filtri -->
            <form method="GET" class="flex-1 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

                <div>
                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Stato</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Tutti</option>
                        <option value="running" <?= ($filters['status'] ?? '') === 'running' ? 'selected' : '' ?>>In Esecuzione</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>In Attesa</option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completati</option>
                        <option value="error" <?= ($filters['status'] ?? '') === 'error' ? 'selected' : '' ?>>Errori</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancellati</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Utente</label>
                    <select name="user" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Tutti</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($filters['user'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name'] ?: $u['email']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Da</label>
                    <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">A</label>
                    <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
                        Filtra
                    </button>
                    <a href="<?= url('/admin/jobs?tab=' . $activeTab) ?>" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 text-sm font-medium transition-colors">
                        Reset
                    </a>
                </div>
            </form>

            <!-- Azioni globali -->
            <div class="flex gap-2 lg:border-l lg:border-slate-200 lg:dark:border-slate-700 lg:pl-4">
                <button @click="cancelStuck()"
                        :disabled="actionLoading"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-400 text-sm transition-colors disabled:opacity-50"
                        title="Cancella job running da oltre 30 minuti">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Bloccati
                </button>

                <button @click="showCleanupModal = true"
                        :disabled="actionLoading"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm transition-colors disabled:opacity-50"
                        title="Elimina job vecchi">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Cleanup
                </button>
            </div>
        </div>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Utente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progetto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progresso</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Crediti</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($jobs)): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium">Nessun job trovato</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php
                    $tabConfig = $tabModules[$activeTab] ?? [];
                    $itemsCol = $tabConfig['items_col'] ?? 'items_requested';
                    $completedCol = $tabConfig['completed_col'] ?? 'items_completed';
                    $failedCol = $tabConfig['failed_col'] ?? 'items_failed';
                    ?>
                    <?php foreach ($jobs as $job):
                        $st = $statusConfig[$job['status']] ?? $statusConfig['pending'];
                        $requested = (int) ($job[$itemsCol] ?? 0);
                        $completed = (int) ($job[$completedCol] ?? 0);
                        $failed = (int) ($job[$failedCol] ?? 0);
                        $progress = $requested > 0 ? min(100, round((($completed + $failed) / $requested) * 100)) : 0;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-slate-600 dark:text-slate-400">#<?= $job['id'] ?></td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?= e($job['user_name'] ?? '-') ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400"><?= e($job['user_email'] ?? '') ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white truncate max-w-[180px]" title="<?= e($job['project_name'] ?? '') ?>">
                            <?= e($job['project_name'] ?? 'N/D') ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                <?= e(strtoupper($job['type'] ?? '-')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $st['class'] ?>">
                                <?php if ($job['status'] === 'running'): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5 animate-pulse"></span>
                                <?php endif; ?>
                                <?= $st['label'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($requested > 0): ?>
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?= $job['status'] === 'error' ? 'bg-red-500' : ($progress >= 100 ? 'bg-emerald-500' : 'bg-blue-500') ?>"
                                         style="width: <?= $progress ?>%"></div>
                                </div>
                                <span class="text-xs text-slate-600 dark:text-slate-400 tabular-nums">
                                    <?= $completed ?>/<?= $requested ?>
                                </span>
                            </div>
                            <?php if ($failed > 0): ?>
                            <span class="text-xs text-red-500"><?= $failed ?> falliti</span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-xs text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right tabular-nums text-slate-600 dark:text-slate-400">
                            <?= number_format((float) ($job['credits_used'] ?? 0), 1) ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            <div><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></div>
                            <?php if ($job['started_at']): ?>
                            <div class="text-xs text-slate-400">
                                <?php if ($job['completed_at']): ?>
                                    <?php
                                    $duration = strtotime($job['completed_at']) - strtotime($job['started_at']);
                                    if ($duration >= 60) {
                                        echo floor($duration / 60) . 'm ' . ($duration % 60) . 's';
                                    } else {
                                        echo $duration . 's';
                                    }
                                    ?>
                                <?php else: ?>
                                    in corso...
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if (in_array($job['status'], ['pending', 'running'])): ?>
                            <button @click="cancelJob(<?= $job['id'] ?>)"
                                    :disabled="actionLoading"
                                    class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors disabled:opacity-50"
                                    title="Cancella job">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                            <?php if ($job['status'] === 'error' && !empty($job['error_message'])): ?>
                            <button @click="showError('<?= e(addslashes($job['error_message'])) ?>')"
                                    class="p-1.5 rounded-lg text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                                    title="Visualizza errore">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <?php endif; ?>
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
                <?php
                $paginationFilters = array_merge($filters, ['tab' => $activeTab]);
                ?>
                <?php if ($pagination['current'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($paginationFilters, ['page' => $pagination['current'] - 1])) ?>"
                   class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <?php endif; ?>
                <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-lg font-medium text-sm">
                    <?= $pagination['current'] ?> / <?= $pagination['total'] ?>
                </span>
                <?php if ($pagination['current'] < $pagination['total']): ?>
                <a href="?<?= http_build_query(array_merge($paginationFilters, ['page' => $pagination['current'] + 1])) ?>"
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

    <!-- Cleanup Modal -->
    <div x-show="showCleanupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCleanupModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showCleanupModal = false"></div>
            <div x-show="showCleanupModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Pulisci Storico Job</h3>
                <div class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Elimina job completati/errore/cancellati del modulo <strong><?= e($tabModules[$activeTab]['label'] ?? '') ?></strong> piu vecchi di:
                    </p>
                    <select x-model="cleanupDays" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="1">1 giorno</option>
                        <option value="3">3 giorni</option>
                        <option value="7">7 giorni</option>
                        <option value="14">14 giorni</option>
                        <option value="30">30 giorni</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showCleanupModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </button>
                    <button type="button" @click="cleanup()" :disabled="actionLoading"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Detail Modal -->
    <div x-show="showErrorModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showErrorModal" x-transition class="fixed inset-0 bg-slate-900/50" @click="showErrorModal = false"></div>
            <div x-show="showErrorModal" x-transition class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Dettaglio Errore</h3>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 max-h-64 overflow-y-auto">
                    <p class="text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap" x-text="errorMessage"></p>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="showErrorModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function jobsAdmin() {
    const csrfToken = '<?= csrf_token() ?>';
    const activeTab = '<?= e($activeTab) ?>';
    const baseUrl = '<?= url('/admin/jobs') ?>';

    return {
        actionLoading: false,
        showCleanupModal: false,
        showErrorModal: false,
        errorMessage: '',
        cleanupDays: '7',

        showError(msg) {
            this.errorMessage = msg;
            this.showErrorModal = true;
        },

        async cancelJob(id) {
            try {
                await window.ainstein.confirm('Cancellare il job #' + id + '?', {destructive: true});
            } catch (e) { return; }

            this.actionLoading = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                formData.append('job_id', id);
                formData.append('module', activeTab);

                const resp = await fetch(baseUrl + '/cancel', { method: 'POST', body: formData });
                const data = await resp.json();

                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: data.message || data.error, type: data.success ? 'success' : 'error' }
                }));
                if (data.success) setTimeout(() => location.reload(), 500);
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        },

        async cancelStuck() {
            try {
                await window.ainstein.confirm('Cancellare i job bloccati (running > 30 min) per ' + activeTab + '?', {destructive: true});
            } catch (e) { return; }

            this.actionLoading = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                formData.append('module', activeTab);
                formData.append('minutes', 30);

                const resp = await fetch(baseUrl + '/cancel-stuck', { method: 'POST', body: formData });
                const data = await resp.json();

                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: data.message || data.error, type: data.success ? 'success' : 'error' }
                }));
                if (data.success) setTimeout(() => location.reload(), 500);
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        },

        async cleanup() {
            this.actionLoading = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                formData.append('module', activeTab);
                formData.append('days', this.cleanupDays);

                const resp = await fetch(baseUrl + '/cleanup', { method: 'POST', body: formData });
                const data = await resp.json();

                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: data.message || data.error, type: data.success ? 'success' : 'error' }
                }));
                if (data.success) {
                    this.showCleanupModal = false;
                    setTimeout(() => location.reload(), 500);
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.actionLoading = false;
            }
        }
    };
}
</script>
