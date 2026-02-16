<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/dashboard') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?> - Alert</h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= number_format($stats['total'] ?? 0) ?> alert totali, <?= number_format($stats['unread'] ?? 0) ?> non letti
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/alerts/check') ?>" method="POST" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Controlla ora
                </button>
            </form>
            <?php if (($stats['unread'] ?? 0) > 0): ?>
            <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/alerts/read-all') ?>" method="POST" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                    Segna tutti come letti
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['unread'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Non letti</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $stats['critical'] ?? 0 ?></p>
            <p class="text-xs text-red-600 dark:text-red-400">Critici</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $stats['high'] ?? 0 ?></p>
            <p class="text-xs text-amber-600 dark:text-amber-400">Alti</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= $stats['medium'] ?? 0 ?></p>
            <p class="text-xs text-blue-600 dark:text-blue-400">Medi</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <select name="type" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutti i tipi</option>
                <option value="position_drop" <?= ($filters['type'] ?? '') === 'position_drop' ? 'selected' : '' ?>>Calo posizione</option>
                <option value="position_gain" <?= ($filters['type'] ?? '') === 'position_gain' ? 'selected' : '' ?>>Aumento posizione</option>
                <option value="traffic_drop" <?= ($filters['type'] ?? '') === 'traffic_drop' ? 'selected' : '' ?>>Calo traffico</option>
                <option value="revenue_drop" <?= ($filters['type'] ?? '') === 'revenue_drop' ? 'selected' : '' ?>>Calo revenue</option>
            </select>
            <select name="severity" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutte le severita</option>
                <option value="critical" <?= ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' ?>>Critico</option>
                <option value="high" <?= ($filters['severity'] ?? '') === 'high' ? 'selected' : '' ?>>Alto</option>
                <option value="medium" <?= ($filters['severity'] ?? '') === 'medium' ? 'selected' : '' ?>>Medio</option>
            </select>
            <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutti gli stati</option>
                <option value="new" <?= ($filters['status'] ?? '') === 'new' ? 'selected' : '' ?>>Non letti</option>
                <option value="read" <?= ($filters['status'] ?? '') === 'read' ? 'selected' : '' ?>>Letti</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-sm">
                Filtra
            </button>
        </div>
    </form>

    <!-- Alerts List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <?php if (empty($alerts)): ?>
        <div class="p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun alert</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Tutto sembra funzionare correttamente!</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($alerts as $alert): ?>
            <?php
            $iconClass = match($alert['alert_type']) {
                'position_drop' => 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400',
                'position_gain' => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400',
                'traffic_drop' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400',
                'revenue_drop' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400',
                default => 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400',
            };
            $severityClass = match($alert['severity']) {
                'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                'high' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
            };
            ?>
            <div class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50 <?= $alert['status'] === 'new' ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' ?>">
                <div class="flex items-start gap-4">
                    <div class="h-10 w-10 rounded-lg <?= $iconClass ?> flex items-center justify-center flex-shrink-0">
                        <?php if ($alert['alert_type'] === 'position_drop'): ?>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <?php elseif ($alert['alert_type'] === 'position_gain'): ?>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <?php else: ?>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-xs font-medium <?= $severityClass ?>">
                                <?= ucfirst($alert['severity']) ?>
                            </span>
                            <?php if ($alert['status'] === 'new'): ?>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                Nuovo
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($alert['message']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            <?= date('d/m/Y H:i', strtotime($alert['created_at'])) ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/alerts/' . $alert['id']) ?>" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Dettaglio">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
