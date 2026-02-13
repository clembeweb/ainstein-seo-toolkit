<?php $currentPage = 'gsc-dashboard'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="gscDashboard()">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Google Search Console</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Dati di performance da Search Console per <?= e($connection['property_url']) ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="syncData()" :disabled="syncing" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50 transition-colors">
                <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': syncing }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="syncing ? 'Sincronizzando...' : 'Sincronizza'"></span>
            </button>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/connect') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Impostazioni
            </a>
        </div>
    </div>

    <?php if ($isMockMode): ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex items-center gap-2 text-yellow-700 dark:text-yellow-300">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-sm font-medium">Modalità Demo - I dati mostrati sono di esempio</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Credits & Sync Info -->
    <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/50 rounded-lg px-4 py-3">
        <div class="flex items-center gap-6 text-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-slate-600 dark:text-slate-400">Crediti disponibili: <strong class="text-slate-900 dark:text-white"><?= number_format($credits['balance']) ?></strong></span>
            </div>
            <div class="text-slate-400">|</div>
            <div class="text-slate-600 dark:text-slate-400">
                Costo sync: <strong class="text-slate-900 dark:text-white"><?= $credits['sync_cost'] ?> crediti</strong>
            </div>
        </div>
        <?php if ($connection['last_sync_at']): ?>
        <div class="text-sm text-slate-500 dark:text-slate-400">
            Ultimo sync: <?= date('d/m/Y H:i', strtotime($connection['last_sync_at'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Clicks -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
                <?php if (isset($stats['comparison']['clicks'])): ?>
                <span class="text-xs font-medium px-2 py-1 rounded-full <?= $stats['comparison']['clicks'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' ?>">
                    <?= $stats['comparison']['clicks'] >= 0 ? '+' : '' ?><?= number_format($stats['comparison']['clicks'], 1) ?>%
                </span>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['totals']['clicks'] ?? 0) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Click totali</p>
            </div>
        </div>

        <!-- Impressions -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <?php if (isset($stats['comparison']['impressions'])): ?>
                <span class="text-xs font-medium px-2 py-1 rounded-full <?= $stats['comparison']['impressions'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' ?>">
                    <?= $stats['comparison']['impressions'] >= 0 ? '+' : '' ?><?= number_format($stats['comparison']['impressions'], 1) ?>%
                </span>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['totals']['impressions'] ?? 0) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Impressioni</p>
            </div>
        </div>

        <!-- CTR -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <?php if (isset($stats['comparison']['ctr'])): ?>
                <span class="text-xs font-medium px-2 py-1 rounded-full <?= $stats['comparison']['ctr'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' ?>">
                    <?= $stats['comparison']['ctr'] >= 0 ? '+' : '' ?><?= number_format($stats['comparison']['ctr'], 2) ?>%
                </span>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format(($stats['totals']['ctr'] ?? 0) * 100, 2) ?>%</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">CTR medio</p>
            </div>
        </div>

        <!-- Position -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <?php if (isset($stats['comparison']['position'])): ?>
                <span class="text-xs font-medium px-2 py-1 rounded-full <?= $stats['comparison']['position'] <= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' ?>">
                    <?= $stats['comparison']['position'] <= 0 ? '' : '+' ?><?= number_format($stats['comparison']['position'], 1) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['totals']['position'] ?? 0, 1) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Posizione media</p>
            </div>
        </div>
    </div>

    <!-- Charts Placeholder -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Trend Performance</h2>
        <div class="h-64 flex items-center justify-center bg-slate-50 dark:bg-slate-700/50 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-600">
            <div class="text-center">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">Grafico trend in arrivo</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Integrazione Chart.js</p>
            </div>
        </div>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Queries -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900 dark:text-white">Top Query</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">Ultimi 28 giorni</span>
            </div>
            <?php if (empty($stats['queries'])): ?>
            <div class="p-8 text-center">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna query trovata</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Sincronizza i dati per visualizzare le query</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Query</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Impr.</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">CTR</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pos.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach (array_slice($stats['queries'], 0, 10) as $query): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-slate-900 dark:text-white truncate block max-w-[200px]" title="<?= e($query['query']) ?>">
                                    <?= e($query['query']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300"><?= number_format($query['clicks']) ?></td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300"><?= number_format($query['impressions']) ?></td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300"><?= number_format($query['ctr'] * 100, 1) ?>%</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $query['position'] <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($query['position'] <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300') ?>">
                                    <?= number_format($query['position'], 1) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($stats['queries']) > 10): ?>
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <button class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Vedi tutte le <?= count($stats['queries']) ?> query
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Top Pages -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900 dark:text-white">Top Pagine</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">Ultimi 28 giorni</span>
            </div>
            <?php if (empty($stats['pages'])): ?>
            <div class="p-8 text-center">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna pagina trovata</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Sincronizza i dati per visualizzare le pagine</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagina</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Impr.</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">CTR</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pos.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach (array_slice($stats['pages'], 0, 10) as $page): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">
                                <?php
                                $parsedUrl = parse_url($page['page']);
                                $displayPath = ($parsedUrl['path'] ?? '/') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
                                ?>
                                <a href="<?= e($page['page']) ?>" target="_blank" class="text-sm font-medium text-primary-600 hover:text-primary-700 truncate block max-w-[200px]" title="<?= e($page['page']) ?>">
                                    <?= e($displayPath) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300"><?= number_format($page['clicks']) ?></td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300"><?= number_format($page['impressions']) ?></td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300"><?= number_format($page['ctr'] * 100, 1) ?>%</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $page['position'] <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($page['position'] <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300') ?>">
                                    <?= number_format($page['position'], 1) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($stats['pages']) > 10): ?>
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
                <button class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Vedi tutte le <?= count($stats['pages']) ?> pagine
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sync Status Message -->
    <div x-show="syncMessage" x-cloak
         :class="syncError ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800'"
         class="rounded-lg border p-4">
        <div class="flex items-center gap-2">
            <template x-if="!syncError">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </template>
            <template x-if="syncError">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </template>
            <span x-text="syncMessage" :class="syncError ? 'text-red-700 dark:text-red-300' : 'text-emerald-700 dark:text-emerald-300'" class="text-sm font-medium"></span>
        </div>
    </div>
</div>

<script>
function gscDashboard() {
    return {
        syncing: false,
        syncMessage: '',
        syncError: false,

        async syncData() {
            if (this.syncing) return;

            this.syncing = true;
            this.syncMessage = '';
            this.syncError = false;

            try {
                const response = await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/gsc/sync') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                    },
                    body: JSON.stringify({
                        _csrf_token: '<?= csrf_token() ?>'
                    })
                });

                const data = await response.json();

                if (data.error) {
                    this.syncError = true;
                    this.syncMessage = data.message;
                } else {
                    this.syncMessage = `Sincronizzazione completata! ${data.queries_synced} query e ${data.pages_synced} pagine sincronizzate. Crediti usati: ${data.credits_used}`;
                    // Reload page after 2 seconds to show updated data
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (error) {
                this.syncError = true;
                this.syncMessage = 'Errore durante la sincronizzazione. Riprova.';
            } finally {
                this.syncing = false;
            }
        }
    }
}
</script>
