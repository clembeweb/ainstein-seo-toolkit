<?php $currentPage = 'revenue'; ?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Revenue Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Total Revenue -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg shadow-sm p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-100">Revenue Totale</p>
                    <p class="text-3xl font-bold mt-1">
                        €<?= number_format($ga4Comparison['current']['revenue'] ?? 0, 2) ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <?php
            $revenueChange = $ga4Comparison['revenue_change_pct'] ?? 0;
            ?>
            <p class="mt-2 text-sm text-emerald-100">
                <?= $revenueChange >= 0 ? '+' : '' ?><?= number_format($revenueChange, 1) ?>% vs mese prec.
            </p>
        </div>

        <!-- Purchases -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Acquisti</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        <?= number_format($ga4Comparison['current']['purchases'] ?? 0) ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <?php
            $purchasesChange = $ga4Comparison['purchases_change_pct'] ?? 0;
            $purchasesColor = $purchasesChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
            ?>
            <p class="mt-2 text-sm <?= $purchasesColor ?>">
                <?= $purchasesChange >= 0 ? '+' : '' ?><?= number_format($purchasesChange, 1) ?>%
            </p>
        </div>

        <!-- Avg Order Value -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">AOV</p>
                    <?php
                    $purchases = $ga4Comparison['current']['purchases'] ?? 0;
                    $revenue = $ga4Comparison['current']['revenue'] ?? 0;
                    $aov = $purchases > 0 ? $revenue / $purchases : 0;
                    ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        €<?= number_format($aov, 2) ?>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Valore medio ordine</p>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Conv. Rate</p>
                    <?php
                    $sessions = $ga4Comparison['current']['sessions'] ?? 0;
                    $convRate = $sessions > 0 ? ($purchases / $sessions) * 100 : 0;
                    ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        <?= number_format($convRate, 2) ?>%
                    </p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Sessioni → Acquisti</p>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Andamento Revenue</h2>
        </div>
        <div class="h-72">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Keywords by Revenue -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Top Keywords per Revenue</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Revenue attribuito da click organici</p>
            </div>
            <?php if (empty($topKeywordsByRevenue)): ?>
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato revenue per keyword disponibile.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acquisti</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($topKeywordsByRevenue as $kw): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-xs"><?= e($kw['keyword']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    €<?= number_format($kw['total_revenue'] ?? 0, 2) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format($kw['total_purchases'] ?? 0, 1) ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format($kw['total_clicks'] ?? 0) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top Pages by Revenue -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Top Pagine per Revenue</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Landing page con piu conversioni</p>
            </div>
            <?php if (empty($topPagesByRevenue)): ?>
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato revenue per pagina disponibile.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagina</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php
                        $totalRevenue = array_sum(array_column($topPagesByRevenue, 'total_revenue'));
                        foreach ($topPagesByRevenue as $page):
                            $pct = $totalRevenue > 0 ? ($page['total_revenue'] / $totalRevenue) * 100 : 0;
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-xs" title="<?= e($page['landing_page']) ?>">
                                    <?= e($page['landing_page']) ?>
                                </p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    €<?= number_format($page['total_revenue'] ?? 0, 2) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: <?= min($pct, 100) ?>%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 w-10 text-right"><?= number_format($pct, 1) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
const textColor = isDark ? '#94a3b8' : '#64748b';

Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = textColor;

// Revenue Chart Data
const dailyRevenue = <?= json_encode($dailyRevenue) ?>;
const labels = dailyRevenue.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
});
const revenueData = dailyRevenue.map(d => parseFloat(d.total_revenue || 0));
const purchasesData = dailyRevenue.map(d => parseInt(d.total_purchases || 0));

const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Revenue (€)',
                data: revenueData,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1,
                yAxisID: 'y',
            },
            {
                label: 'Acquisti',
                data: purchasesData,
                type: 'line',
                borderColor: 'rgb(139, 92, 246)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.3,
                fill: false,
                yAxisID: 'y1',
            }
        ]
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
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label.includes('Revenue')) {
                            return context.dataset.label + ': €' + context.parsed.y.toFixed(2);
                        }
                        return context.dataset.label + ': ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { color: gridColor },
            },
            y: {
                type: 'linear',
                position: 'left',
                grid: { color: gridColor },
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '€' + value;
                    }
                }
            },
            y1: {
                type: 'linear',
                position: 'right',
                grid: { display: false },
                beginAtZero: true,
            }
        }
    }
});
</script>
