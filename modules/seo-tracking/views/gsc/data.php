<div class="space-y-6" x-data="gscPage()">

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Search Console</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['name']) ?></p>
        </div>

        <?php if ($connection && $connection['is_active']): ?>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <div class="text-right">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Connesso
                </span>
                <?php if ($connection['last_sync_at']): ?>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ultimo sync: <?= date('d/m/Y H:i', strtotime($connection['last_sync_at'])) ?></p>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/sync') ?>">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sincronizza
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/connect') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="currentColor" opacity="0.8"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="currentColor" opacity="0.6"/>
                </svg>
                Connetti Google Search Console
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($topQueries) && empty($topPages)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Nessun dato GSC disponibile</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
            <?php if (!$connection || !$connection['is_active']): ?>
                Connetti Google Search Console per visualizzare click, impressioni, posizioni e pagine del tuo sito.
            <?php else: ?>
                I dati non sono ancora stati sincronizzati. Avvia una sincronizzazione per importare i dati da GSC.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Periodo</label>
                <select name="days" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 giorni</option>
                    <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 giorni</option>
                    <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 giorni</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cerca keyword</label>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Filtra per keyword..." class="w-full text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Posizione</label>
                <select name="pos" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="" <?= $posFilter === '' ? 'selected' : '' ?>>Tutte</option>
                    <option value="3" <?= $posFilter === '3' ? 'selected' : '' ?>>Top 3</option>
                    <option value="10" <?= $posFilter === '10' ? 'selected' : '' ?>>Top 10</option>
                    <option value="20" <?= $posFilter === '20' ? 'selected' : '' ?>>Top 20</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                Filtra
            </button>
            <?php if ($search !== '' || $posFilter !== '' || $days != 30): ?>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc') ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">Cancella filtri</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'queries'" :class="activeTab === 'queries' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Top Queries (<?= count($topQueries) ?>)
                </button>
                <button @click="activeTab = 'pages'" :class="activeTab === 'pages' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Top Pagine (<?= count($topPages) ?>)
                </button>
                <button @click="activeTab = 'performance'" :class="activeTab === 'performance' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Performance
                </button>
            </nav>
        </div>

        <!-- Tab: Top Queries -->
        <div x-show="activeTab === 'queries'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CTR</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagine</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Stato</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($topQueries as $q): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($q['query']) ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white"><?= number_format($q['total_clicks']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($q['total_impressions']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format(($q['avg_ctr'] ?? 0) * 100, 1) ?>%</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php
                            $pos = round($q['avg_position'], 1);
                            $posClass = $pos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                       ($pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                       ($pos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                            ?>
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>"><?= $pos ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= $q['pages_count'] ?? 1 ?></td>
                        <td class="px-4 py-3 text-sm text-center">
                            <?php if (!empty($q['keyword_id'])): ?>
                            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $q['keyword_id']) ?>" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                Tracciata
                            </a>
                            <?php else: ?>
                            <span class="text-xs text-slate-400 dark:text-slate-500">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topQueries)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna query trovata per i filtri selezionati</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tab: Top Pagine -->
        <div x-show="activeTab === 'pages'" x-cloak class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">URL</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CTR</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Query</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($topPages as $p): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm">
                            <a href="<?= e($p['page']) ?>" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline break-all max-w-md block truncate" title="<?= e($p['page']) ?>">
                                <?= e(str_replace(['https://', 'http://'], '', $p['page'])) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white"><?= number_format($p['total_clicks']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($p['total_impressions']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format(($p['avg_ctr'] ?? 0) * 100, 1) ?>%</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php $pos = round($p['avg_position'], 1); ?>
                            <span class="text-slate-900 dark:text-white font-medium"><?= $pos ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= $p['queries_count'] ?? 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topPages)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna pagina trovata</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tab: Performance -->
        <div x-show="activeTab === 'performance'" x-cloak class="p-6">
            <?php if (!empty($dailyData)): ?>
            <div class="h-72">
                <canvas id="performanceChart"></canvas>
            </div>
            <?php else: ?>
            <div class="h-64 flex items-center justify-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato di performance disponibile</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($dailyData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function gscPage() {
    return {
        activeTab: 'queries'
    };
}

document.addEventListener('DOMContentLoaded', function() {
    const dailyData = <?= json_encode($dailyData) ?>;
    if (!dailyData.length) return;

    const ctx = document.getElementById('performanceChart');
    if (!ctx) return;

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dailyData.map(d => {
                const dt = new Date(d.date);
                return dt.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [
                {
                    label: 'Click',
                    data: dailyData.map(d => parseInt(d.total_clicks)),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y',
                },
                {
                    label: 'Impressioni',
                    data: dailyData.map(d => parseInt(d.total_impressions)),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.05)',
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                x: { grid: { color: gridColor } },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: gridColor },
                    title: { display: true, text: 'Click' },
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Impressioni' },
                }
            }
        }
    });
});
</script>
<?php else: ?>
<script>
function gscPage() {
    return { activeTab: 'queries' };
}
</script>
<?php endif; ?>
