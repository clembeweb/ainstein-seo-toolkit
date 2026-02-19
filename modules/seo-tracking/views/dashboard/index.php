<?php $currentPage = 'overview'; ?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

    <!-- ROW 1: KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Keywords Tracciate -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($kpiStats['tracked_keywords']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Keywords Tracciate</p>
                </div>
            </div>
        </div>

        <!-- Posizione Media -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">
                        <?= $kpiStats['avg_position'] ? number_format($kpiStats['avg_position'], 1) : '-' ?>
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Posizione Media</p>
                </div>
            </div>
        </div>

        <!-- In Top 10 -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($kpiStats['top10_count']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">In Top 10</p>
                </div>
            </div>
        </div>

        <!-- Variazioni 7gg -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <div class="flex items-baseline gap-3">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= $kpiStats['improved_7d'] ?></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <span class="text-lg font-bold text-red-600 dark:text-red-400"><?= $kpiStats['declined_7d'] ?></span>
                    </div>
                </div>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Variazioni 7gg</p>
        </div>
    </div>

    <!-- ROW 2: Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Distribuzione Posizioni (Donut) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Posizioni</h2>
            <div class="h-64 flex items-center justify-center">
                <canvas id="positionDistributionChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-5 gap-2 text-center text-xs">
                <div>
                    <div class="w-3 h-3 rounded-full bg-emerald-500 mx-auto mb-1"></div>
                    <span class="text-slate-600 dark:text-slate-400">Top 3</span>
                </div>
                <div>
                    <div class="w-3 h-3 rounded-full bg-blue-500 mx-auto mb-1"></div>
                    <span class="text-slate-600 dark:text-slate-400">4-10</span>
                </div>
                <div>
                    <div class="w-3 h-3 rounded-full bg-amber-500 mx-auto mb-1"></div>
                    <span class="text-slate-600 dark:text-slate-400">11-20</span>
                </div>
                <div>
                    <div class="w-3 h-3 rounded-full bg-orange-500 mx-auto mb-1"></div>
                    <span class="text-slate-600 dark:text-slate-400">21-50</span>
                </div>
                <div>
                    <div class="w-3 h-3 rounded-full bg-slate-400 mx-auto mb-1"></div>
                    <span class="text-slate-600 dark:text-slate-400">50+</span>
                </div>
            </div>
        </div>

        <!-- Trend Posizione Media (Line) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Trend Posizione Media</h2>
            <div class="h-64">
                <canvas id="positionTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ROW 3: Gainers / Losers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top 5 Gainers -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <h2 class="font-semibold text-slate-900 dark:text-white">Top 5 Miglioramenti</h2>
            </div>
            <?php if (empty($gainers)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                Nessun miglioramento rilevato
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Prima</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Ora</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Delta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($gainers as $g): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white truncate max-w-[200px]"><?= e($g['keyword']) ?></td>
                            <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= (int)$g['old_position'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                    <?= (int)$g['new_position'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-medium text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                    <?= abs((int)$g['position_diff']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top 5 Losers -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
                <h2 class="font-semibold text-slate-900 dark:text-white">Top 5 Peggioramenti</h2>
            </div>
            <?php if (empty($losers)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                Nessun peggioramento rilevato
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Prima</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Ora</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Delta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($losers as $l): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white truncate max-w-[200px]"><?= e($l['keyword']) ?></td>
                            <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= (int)$l['old_position'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                    <?= (int)$l['new_position'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center text-red-600 dark:text-red-400 font-medium text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <?= abs((int)$l['position_diff']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ROW 4: Movimenti Recenti -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900 dark:text-white">Movimenti Recenti</h2>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/rank-check/history') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                Vedi tutti
            </a>
        </div>
        <?php if (empty($recentMovements)): ?>
        <div class="p-8 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna verifica effettuata</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Vai alla sezione "Keywords" per controllare le posizioni delle tue keyword
            </p>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Gestisci Keywords
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">URL</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Variazione</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($recentMovements as $m): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-slate-900 dark:text-white"><?= e($m['keyword']) ?></span>
                            <span class="ml-2 text-xs text-slate-400"><?= $m['device'] ?></span>
                        </td>
                        <td class="px-6 py-4 max-w-xs truncate">
                            <?php if ($m['serp_url']): ?>
                            <a href="<?= e($m['serp_url']) ?>" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                <?= e(parse_url($m['serp_url'], PHP_URL_PATH) ?: '/') ?>
                            </a>
                            <?php else: ?>
                            <span class="text-slate-400">Non trovato</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($m['serp_position']): ?>
                                <?php
                                $pos = (int)$m['serp_position'];
                                $posClass = $pos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                           ($pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                           ($pos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                                ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>">
                                    <?= $pos ?>
                                </span>
                            <?php else: ?>
                                <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($m['prev_position'] && $m['serp_position']): ?>
                                <?php
                                $diff = (int)$m['prev_position'] - (int)$m['serp_position'];
                                if ($diff > 0): ?>
                                <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400 text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                    +<?= $diff ?>
                                </span>
                                <?php elseif ($diff < 0): ?>
                                <span class="inline-flex items-center text-red-600 dark:text-red-400 text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <?= $diff ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400">=</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">nuovo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m H:i', strtotime($m['checked_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ROW 5: CTA -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-6 text-white">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold">Gestisci Keywords e Posizioni</h3>
                <?php if ($lastCheck): ?>
                <p class="text-primary-100 text-sm mt-1">
                    Ultimo check: <?= date('d/m/Y H:i', strtotime($lastCheck['checked_at'])) ?>
                    (<?= $lastCheck['keywords_checked'] ?> keyword verificate)
                </p>
                <?php else: ?>
                <p class="text-primary-100 text-sm mt-1">
                    Non hai ancora effettuato nessuna verifica
                </p>
                <?php endif; ?>
            </div>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>"
               class="inline-flex items-center px-6 py-3 rounded-lg bg-white text-primary-600 font-semibold hover:bg-primary-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Vai alle Keywords
            </a>
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

// Donut Chart - Distribuzione Posizioni
const distributionData = <?= json_encode($positionDistribution) ?>;
const distributionCtx = document.getElementById('positionDistributionChart');

if (distributionCtx) {
    new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Top 3', '4-10', '11-20', '21-50', '50+'],
            datasets: [{
                data: [
                    distributionData.top3,
                    distributionData.top4_10,
                    distributionData.top11_20,
                    distributionData.top21_50,
                    distributionData.beyond50
                ],
                backgroundColor: [
                    '#10b981', // emerald
                    '#3b82f6', // blue
                    '#f59e0b', // amber
                    '#f97316', // orange
                    '#94a3b8'  // slate
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Line Chart - Trend Posizione Media
const trendData = <?= json_encode($positionTrend) ?>;
const trendCtx = document.getElementById('positionTrendChart');

if (trendCtx && trendData.length > 0) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [{
                label: 'Posizione Media',
                data: trendData.map(d => parseFloat(d.avg_position).toFixed(1)),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 2,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor }
                },
                y: {
                    grid: { color: gridColor },
                    reverse: true,
                    min: 1,
                    title: {
                        display: true,
                        text: 'Posizione'
                    }
                }
            }
        }
    });
} else if (trendCtx) {
    // Mostra messaggio vuoto nel canvas
    const ctx = trendCtx.getContext('2d');
    ctx.fillStyle = textColor;
    ctx.font = '14px Inter, system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Nessun dato disponibile', trendCtx.width / 2, trendCtx.height / 2);
}
</script>
