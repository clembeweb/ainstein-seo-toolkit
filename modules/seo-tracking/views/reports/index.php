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
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?> - Report AI</h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Report generati con intelligenza artificiale
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Genera Report
            </a>
        </div>
    </div>

    <!-- Credits Info -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium">Crediti AI disponibili</p>
                    <p class="text-sm text-white/80">Ogni report consuma crediti in base alla complessità</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold"><?= number_format($credits ?? 0) ?></p>
                <p class="text-sm text-white/80">crediti</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <select name="type" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutti i tipi</option>
                <option value="weekly_digest" <?= ($filters['type'] ?? '') === 'weekly_digest' ? 'selected' : '' ?>>Digest Settimanale</option>
                <option value="monthly_executive" <?= ($filters['type'] ?? '') === 'monthly_executive' ? 'selected' : '' ?>>Executive Mensile</option>
                <option value="anomaly_analysis" <?= ($filters['type'] ?? '') === 'anomaly_analysis' ? 'selected' : '' ?>>Analisi Anomalie</option>
                <option value="custom" <?= ($filters['type'] ?? '') === 'custom' ? 'selected' : '' ?>>Personalizzato</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-sm">
                Filtra
            </button>
        </div>
    </form>

    <!-- Reports List -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <?php if (empty($reports)): ?>
        <div class="p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun report</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Genera il tuo primo report AI per ottenere insights dettagliati</p>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Genera Report
            </a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($reports as $report): ?>
            <?php
            $typeLabel = match($report['report_type']) {
                'weekly_digest' => 'Digest Settimanale',
                'monthly_executive' => 'Executive Mensile',
                'anomaly_analysis' => 'Analisi Anomalie',
                'custom' => 'Personalizzato',
                default => ucfirst($report['report_type']),
            };
            $typeColor = match($report['report_type']) {
                'weekly_digest' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'monthly_executive' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                'anomaly_analysis' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
            };
            ?>
            <div class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-xs font-medium <?= $typeColor ?>">
                                <?= $typeLabel ?>
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                <?= number_format($report['tokens_used'] ?? 0) ?> token
                            </span>
                        </div>
                        <h3 class="text-sm font-medium text-slate-900 dark:text-white truncate">
                            <?= e($report['title'] ?? 'Report ' . date('d/m/Y', strtotime($report['created_at']))) ?>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Generato il <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
                            <?php if (!empty($report['period_start']) && !empty($report['period_end'])): ?>
                            • Periodo: <?= date('d/m', strtotime($report['period_start'])) ?> - <?= date('d/m/Y', strtotime($report['period_end'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports/' . $report['id']) ?>" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Visualizza">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/reports/' . $report['id'] . '/delete') ?>" method="POST" class="inline" onsubmit="return confirm('Eliminare questo report?')">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Elimina">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
    <div class="flex justify-center">
        <nav class="flex items-center gap-1">
            <?php if ($pagination['current_page'] > 1): ?>
            <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
            <a href="?page=<?= $i ?>" class="px-3 py-2 rounded-lg <?= $i === $pagination['current_page'] ? 'bg-primary-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
            <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>
