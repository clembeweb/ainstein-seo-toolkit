<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
                <?php if ($project['gsc_connected']): ?>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">GSC</span>
                <?php endif; ?>
                <?php if ($project['ga4_connected']): ?>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">GA4</span>
                <?php endif; ?>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['domain']) ?></p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/settings') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Impostazioni
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-6">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/dashboard') ?>" class="py-3 px-1 border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-medium text-sm">
                Overview
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords-overview') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Keywords
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/pages') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Pagine
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/revenue') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Revenue
            </a>
        </nav>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Clicks -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Click</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        <?= number_format($gscComparison['current']['total_clicks'] ?? 0) ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
            </div>
            <?php
            $clicksChange = $gscComparison['clicks_change_pct'] ?? 0;
            $clicksColor = $clicksChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
            ?>
            <p class="mt-2 text-sm <?= $clicksColor ?>">
                <?= $clicksChange >= 0 ? '+' : '' ?><?= number_format($clicksChange, 1) ?>% vs settimana prec.
            </p>
        </div>

        <!-- Impressions -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Impressioni</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        <?= number_format($gscComparison['current']['total_impressions'] ?? 0) ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
            </div>
            <?php
            $impChange = $gscComparison['impressions_change_pct'] ?? 0;
            $impColor = $impChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
            ?>
            <p class="mt-2 text-sm <?= $impColor ?>">
                <?= $impChange >= 0 ? '+' : '' ?><?= number_format($impChange, 1) ?>% vs settimana prec.
            </p>
        </div>

        <!-- Sessions -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Sessioni Organiche</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        <?= number_format($ga4Comparison['current']['sessions'] ?? 0) ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <?php
            $sessionsChange = $ga4Comparison['sessions_change_pct'] ?? 0;
            $sessionsColor = $sessionsChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
            ?>
            <p class="mt-2 text-sm <?= $sessionsColor ?>">
                <?= $sessionsChange >= 0 ? '+' : '' ?><?= number_format($sessionsChange, 1) ?>% vs settimana prec.
            </p>
        </div>

        <!-- Avg Position -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Posizione Media</p>
                    <?php $avgPos = $gscComparison['current']['avg_position'] ?? 0; ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        <?= $avgPos > 0 ? number_format($avgPos, 1) : '-' ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <?php
            $posChange = $gscComparison['position_change_pct'] ?? 0;
            // Per posizione, negativo e meglio (posizione piu bassa = migliore)
            $posColor = $posChange <= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
            ?>
            <p class="mt-2 text-sm <?= $posColor ?>">
                <?= $posChange <= 0 ? '' : '+' ?><?= number_format($posChange, 1) ?>% vs settimana prec.
            </p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Traffic Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Traffico</h2>
                <select id="trafficDays" onchange="loadTrafficChart()" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="7">7 giorni</option>
                    <option value="30" selected>30 giorni</option>
                    <option value="90">90 giorni</option>
                </select>
            </div>
            <div class="h-64">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <!-- Position Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Posizione Media</h2>
                <select id="positionDays" onchange="loadPositionChart()" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="30">30 giorni</option>
                    <option value="90" selected>90 giorni</option>
                </select>
            </div>
            <div class="h-64">
                <canvas id="positionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Keywords -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Top Keywords</h2>
                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords-overview') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                    Vedi tutte
                </a>
            </div>
            <?php if (empty($topKeywords)): ?>
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato disponibile. Esegui una sincronizzazione GSC.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($topKeywords as $kw): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-xs"><?= e($kw['keyword']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($kw['last_clicks'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format($kw['last_impressions'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-right">
                                <?php
                                $pos = $kw['last_position'] ?? 0;
                                $posClass = $pos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                           ($pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                           ($pos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                                ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>">
                                    <?= $pos > 0 ? number_format($pos, 1) : '-' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Top Movers -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Maggiori Variazioni</h2>
                </div>
                <?php if (empty($topMovers)): ?>
                <div class="p-5 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna variazione significativa</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($topMovers as $mover): ?>
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div class="min-w-0 flex-1 mr-3">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= e($mover['keyword']) ?></p>
                        </div>
                        <?php
                        $change = $mover['position_change'] ?? 0;
                        $isUp = $change < 0; // Negative change = position improved
                        $changeClass = $isUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                        ?>
                        <span class="flex items-center gap-1 text-sm font-medium <?= $changeClass ?>">
                            <?php if ($isUp): ?>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                            <?php else: ?>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                            <?php endif; ?>
                            <?= abs($change) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Alerts -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Alert Recenti</h2>
                    <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/alerts') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                        Tutti
                    </a>
                </div>
                <?php if (empty($recentAlerts)): ?>
                <div class="p-5 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Nessun alert recente</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($recentAlerts as $alert): ?>
                    <div class="px-5 py-3">
                        <div class="flex items-start gap-3">
                            <?php
                            $alertIcon = match($alert['alert_type']) {
                                'position_drop' => '<svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>',
                                'position_gain' => '<svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>',
                                'traffic_drop' => '<svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>',
                                default => '<svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                            };
                            ?>
                            <?= $alertIcon ?>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-900 dark:text-white"><?= e($alert['message']) ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= date('d/m H:i', strtotime($alert['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const projectId = <?= $project['id'] ?>;
let trafficChart, positionChart;

// Dark mode detection
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
const textColor = isDark ? '#94a3b8' : '#64748b';

// Chart defaults
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = textColor;

async function loadTrafficChart() {
    const days = document.getElementById('trafficDays').value;
    const response = await fetch(`<?= url('/seo-tracking/api/projects/' . $project['id'] . '/chart/traffic') ?>?days=${days}`);
    const data = await response.json();

    if (trafficChart) trafficChart.destroy();

    const ctx = document.getElementById('trafficChart').getContext('2d');
    trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(ds => ({
                ...ds,
                tension: 0.3,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 4,
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                },
                y: {
                    grid: { color: gridColor },
                    beginAtZero: true,
                }
            }
        }
    });
}

async function loadPositionChart() {
    const days = document.getElementById('positionDays').value;
    const response = await fetch(`<?= url('/seo-tracking/api/projects/' . $project['id'] . '/chart/positions') ?>?days=${days}`);
    const data = await response.json();

    if (positionChart) positionChart.destroy();

    const ctx = document.getElementById('positionChart').getContext('2d');
    positionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(ds => ({
                ...ds,
                tension: 0.3,
                pointRadius: 0,
                pointHoverRadius: 4,
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                },
                y: {
                    grid: { color: gridColor },
                    reverse: true, // Lower position = better
                    min: 1,
                }
            }
        }
    });
}

// Load charts on page load
document.addEventListener('DOMContentLoaded', () => {
    loadTrafficChart();
    loadPositionChart();
});
</script>
