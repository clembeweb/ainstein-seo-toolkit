<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Torna alle keyword
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($keyword['keyword']) ?></h1>
                <?php if ($keyword['is_tracked']): ?>
                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <?php endif; ?>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['name']) ?></p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $keyword['id'] . '/edit') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Modifica
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Current Position -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Posizione Attuale</p>
            <?php
            $pos = $keyword['last_position'] ?? 0;
            $posClass = $pos <= 3 ? 'text-emerald-600 dark:text-emerald-400' :
                       ($pos <= 10 ? 'text-blue-600 dark:text-blue-400' :
                       ($pos <= 20 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'));
            ?>
            <p class="text-3xl font-bold <?= $posClass ?> mt-1">
                <?= $pos > 0 ? number_format($pos, 1) : '-' ?>
            </p>
            <?php if ($keyword['target_position']): ?>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Target: <?= $keyword['target_position'] ?></p>
            <?php endif; ?>
        </div>

        <!-- Clicks -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Click (30gg)</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">
                <?= number_format($keyword['last_clicks'] ?? 0) ?>
            </p>
        </div>

        <!-- Impressions -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Impressioni (30gg)</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">
                <?= number_format($keyword['last_impressions'] ?? 0) ?>
            </p>
        </div>

        <!-- CTR -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">CTR</p>
            <?php
            $clicks = $keyword['last_clicks'] ?? 0;
            $impressions = $keyword['last_impressions'] ?? 0;
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            ?>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">
                <?= number_format($ctr, 2) ?>%
            </p>
        </div>
    </div>

    <!-- Position Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Storico Posizioni</h2>
            <select id="chartDays" onchange="loadChart()" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="30">30 giorni</option>
                <option value="90" selected>90 giorni</option>
            </select>
        </div>
        <?php if (empty($positions)): ?>
        <div class="h-64 flex items-center justify-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato di posizione disponibile</p>
        </div>
        <?php else: ?>
        <div class="h-64">
            <canvas id="positionChart"></canvas>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Keyword Info -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="font-semibold text-slate-900 dark:text-white">Informazioni</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Gruppo</span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?= e($keyword['group_name'] ?? '-') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Fonte</span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white capitalize"><?= e($keyword['source'] ?? 'manual') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Tracciata</span>
                    <span class="text-sm font-medium <?= $keyword['is_tracked'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' ?>">
                        <?= $keyword['is_tracked'] ? 'Si' : 'No' ?>
                    </span>
                </div>
                <?php if ($keyword['target_url']): ?>
                <div>
                    <span class="text-sm text-slate-500 dark:text-slate-400 block mb-1">Target URL</span>
                    <a href="<?= e($keyword['target_url']) ?>" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline break-all">
                        <?= e($keyword['target_url']) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($keyword['notes']): ?>
                <div>
                    <span class="text-sm text-slate-500 dark:text-slate-400 block mb-1">Note</span>
                    <p class="text-sm text-slate-900 dark:text-white"><?= nl2br(e($keyword['notes'])) ?></p>
                </div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Aggiunta il</span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?= date('d/m/Y', strtotime($keyword['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Recent GSC Data -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="font-semibold text-slate-900 dark:text-white">Dati GSC Recenti</h2>
            </div>
            <?php if (empty($gscData)): ?>
            <div class="p-6 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato GSC disponibile</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Data</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Pos.</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Click</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Impr.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach (array_slice($gscData, 0, 10) as $row): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-slate-900 dark:text-white"><?= date('d/m', strtotime($row['date'])) ?></td>
                            <td class="px-4 py-2 text-sm text-right text-slate-900 dark:text-white"><?= number_format($row['position'], 1) ?></td>
                            <td class="px-4 py-2 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($row['clicks']) ?></td>
                            <td class="px-4 py-2 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($row['impressions']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (!empty($positions)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const keywordId = <?= $keyword['id'] ?>;
let positionChart;

const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
const textColor = isDark ? '#94a3b8' : '#64748b';

Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = textColor;

async function loadChart() {
    const days = document.getElementById('chartDays').value;
    const response = await fetch(`<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $keyword['id'] . '/chart') ?>?days=${days}`);
    const data = await response.json();

    if (positionChart) positionChart.destroy();

    const ctx = document.getElementById('positionChart').getContext('2d');
    positionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Posizione',
                data: data.datasets[0].data,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 2,
                pointHoverRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { grid: { color: gridColor } },
                y: {
                    grid: { color: gridColor },
                    reverse: true,
                    min: 1,
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', loadChart);
</script>
<?php endif; ?>
