<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/alerts') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Dettaglio Alert</h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= e($project['name']) ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <?php if ($alert['status'] === 'new'): ?>
            <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/alerts/' . $alert['id'] . '/read') ?>" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Segna come letto
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

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
    $typeLabel = match($alert['alert_type']) {
        'position_drop' => 'Calo Posizione',
        'position_gain' => 'Aumento Posizione',
        'traffic_drop' => 'Calo Traffico',
        'revenue_drop' => 'Calo Revenue',
        default => 'Alert',
    };
    ?>

    <!-- Alert Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start gap-4">
            <div class="h-12 w-12 rounded-lg <?= $iconClass ?> flex items-center justify-center flex-shrink-0">
                <?php if ($alert['alert_type'] === 'position_drop'): ?>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
                <?php elseif ($alert['alert_type'] === 'position_gain'): ?>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <?php else: ?>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-1 rounded text-sm font-medium <?= $severityClass ?>">
                        <?= ucfirst($alert['severity']) ?>
                    </span>
                    <span class="px-2.5 py-1 rounded text-sm font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        <?= $typeLabel ?>
                    </span>
                    <?php if ($alert['status'] === 'new'): ?>
                    <span class="px-2.5 py-1 rounded text-sm font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        Non letto
                    </span>
                    <?php else: ?>
                    <span class="px-2.5 py-1 rounded text-sm font-medium bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                        Letto
                    </span>
                    <?php endif; ?>
                </div>
                <p class="text-lg font-medium text-slate-900 dark:text-white"><?= e($alert['message']) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Rilevato il <?= date('d/m/Y', strtotime($alert['created_at'])) ?> alle <?= date('H:i', strtotime($alert['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Alert Data -->
    <?php if (!empty($alert['data'])): ?>
    <?php $data = json_decode($alert['data'], true); ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Dettagli</h3>
        </div>
        <div class="p-6">
            <?php if ($alert['alert_type'] === 'position_drop' || $alert['alert_type'] === 'position_gain'): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php if (isset($data['keyword'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Keyword</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= e($data['keyword']) ?></p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['old_position'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Posizione precedente</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($data['old_position'], 1) ?></p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['new_position'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Posizione attuale</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($data['new_position'], 1) ?></p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['change'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Variazione</p>
                    <p class="text-lg font-semibold <?= $data['change'] > 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                        <?= $data['change'] > 0 ? '+' : '' ?><?= number_format($data['change'], 1) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($alert['alert_type'] === 'traffic_drop'): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php if (isset($data['previous_clicks'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Click precedenti</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($data['previous_clicks']) ?></p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['current_clicks'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Click attuali</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($data['current_clicks']) ?></p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['change_percent'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Variazione %</p>
                    <p class="text-lg font-semibold text-red-600"><?= number_format($data['change_percent'], 1) ?>%</p>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($alert['alert_type'] === 'revenue_drop'): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php if (isset($data['previous_revenue'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Revenue precedente</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($data['previous_revenue'], 2) ?> €</p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['current_revenue'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Revenue attuale</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($data['current_revenue'], 2) ?> €</p>
                </div>
                <?php endif; ?>
                <?php if (isset($data['change_percent'])): ?>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Variazione %</p>
                    <p class="text-lg font-semibold text-red-600"><?= number_format($data['change_percent'], 1) ?>%</p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <pre class="text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900 p-4 rounded-lg overflow-x-auto"><?= e(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Keyword -->
    <?php if (!empty($alert['keyword_id']) && !empty($keyword)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword correlata</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-lg font-medium text-slate-900 dark:text-white"><?= e($keyword['keyword']) ?></p>
                    <?php if (!empty($keyword['group_name'])): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Gruppo: <?= e($keyword['group_name']) ?></p>
                    <?php endif; ?>
                </div>
                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $keyword['id']) ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Visualizza keyword
                    <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex justify-between items-center">
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/alerts') ?>" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
            ← Torna alla lista alert
        </a>
        <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/alerts/' . $alert['id'] . '/dismiss') ?>" method="POST" onsubmit="return confirm('Eliminare questo alert?')">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                Elimina alert
            </button>
        </form>
    </div>
</div>
