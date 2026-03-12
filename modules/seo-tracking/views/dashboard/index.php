<?php $currentPage = 'overview'; ?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

    <?= \Core\View::partial('seo-tracking/partials/country-bar', [
        'countries' => $countries ?? [],
        'activeCountry' => $activeCountry ?? null,
        'project' => $project,
        'currentPage' => 'overview',
    ]) ?>

    <!-- ROW 1: KPI Cards (Semrush-style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        // KPI delta helper functions
        function stDeltaColor(float $percent, bool $invert = false): string {
            if (abs($percent) < 0.5) return 'text-slate-400 dark:text-slate-500';
            $positive = $percent > 0;
            if ($invert) $positive = !$positive;
            return $positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
        }
        function stDeltaArrow(float $percent): string {
            if (abs($percent) < 0.5) return '&rarr;';
            return $percent > 0
                ? '<svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                : '<svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        }
        // Calculate deltas from kpiStats (7-day comparison)
        $visibilityDelta = $kpiStats['visibility_delta_7d'] ?? null;
        $trafficDelta = $kpiStats['traffic_delta_7d'] ?? null;
        $positionDelta = $kpiStats['position_delta_7d'] ?? null;
        ?>

        <!-- Visibility Score -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Visibility Score</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($kpiStats['visibility'] ?? 0, 2) ?>%</span>
                <?php if ($visibilityDelta !== null && abs($visibilityDelta) >= 0.5): ?>
                <span class="text-xs font-medium <?= stDeltaColor($visibilityDelta) ?>">
                    <?= stDeltaArrow($visibilityDelta) ?>
                    <?= $visibilityDelta >= 0 ? '+' : '' ?><?= number_format($visibilityDelta, 1) ?>%
                </span>
                <?php endif; ?>
            </div>
            <?php if ($visibilityDelta !== null): ?>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">vs 7gg fa</p>
            <?php endif; ?>
        </div>

        <!-- Estimated Traffic -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Est. Traffic</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($kpiStats['est_traffic'] ?? 0, 1) ?></span>
                <?php if ($trafficDelta !== null && abs($trafficDelta) >= 0.5): ?>
                <span class="text-xs font-medium <?= stDeltaColor($trafficDelta) ?>">
                    <?= stDeltaArrow($trafficDelta) ?>
                    <?= $trafficDelta >= 0 ? '+' : '' ?><?= number_format($trafficDelta, 1) ?>%
                </span>
                <?php endif; ?>
            </div>
            <?php if ($trafficDelta !== null): ?>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">vs 7gg fa</p>
            <?php endif; ?>
        </div>

        <!-- Average Position -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Posizione Media</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold text-slate-900 dark:text-white"><?= ($kpiStats['avg_position'] ?? null) ? number_format($kpiStats['avg_position'], 1) : '--' ?></span>
                <?php if ($positionDelta !== null && abs($positionDelta) >= 0.5): ?>
                <span class="text-xs font-medium <?= stDeltaColor($positionDelta, true) ?>">
                    <?= stDeltaArrow($positionDelta) ?>
                    <?= $positionDelta >= 0 ? '+' : '' ?><?= number_format($positionDelta, 1) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if (($kpiStats['improved_7d'] ?? 0) > 0 || ($kpiStats['declined_7d'] ?? 0) > 0): ?>
            <div class="flex items-center gap-3 mt-1 text-xs">
                <?php if (($kpiStats['improved_7d'] ?? 0) > 0): ?>
                <span class="text-emerald-600 dark:text-emerald-400">
                    <svg class="w-3 h-3 inline mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    <?= $kpiStats['improved_7d'] ?>
                </span>
                <?php endif; ?>
                <?php if (($kpiStats['declined_7d'] ?? 0) > 0): ?>
                <span class="text-red-600 dark:text-red-400">
                    <svg class="w-3 h-3 inline mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <?= $kpiStats['declined_7d'] ?>
                </span>
                <?php endif; ?>
                <span class="text-slate-400">vs 7gg fa</span>
            </div>
            <?php else: ?>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">vs 7gg fa</p>
            <?php endif; ?>
        </div>

        <!-- Keywords Tracked -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keywords</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">
                <?= $kpiStats['tracked_keywords'] ?? 0 ?>
            </div>
        </div>
    </div>

    <!-- ROW 2: Rankings Distribution Chart + Keywords Mini-Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stacked Bar Chart (2/3 width) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Rankings</h2>
            <div class="h-72">
                <canvas id="distributionChart"></canvas>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500"></span> Top 3</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-500"></span> 4-10</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-500"></span> 11-20</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-orange-500"></span> 21-50</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-slate-400"></span> 51-100</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-500"></span> Out</span>
            </div>
        </div>

        <!-- Keywords Mini-Grid (1/3 width) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Keywords per Posizione</h2>
            <div class="space-y-3">
                <?php
                $bucketLabels = [
                    'top3' => ['label' => 'Top 3', 'color' => 'emerald'],
                    'top10' => ['label' => 'Top 10', 'color' => 'blue'],
                    'top20' => ['label' => 'Top 20', 'color' => 'amber'],
                    'top100' => ['label' => 'Top 100', 'color' => 'slate'],
                ];
                foreach ($bucketLabels as $key => $meta):
                    $bucket = $keywordBuckets[$key] ?? ['count' => 0, 'improved' => 0, 'declined' => 0];
                ?>
                <div class="flex items-center justify-between py-2.5 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-<?= $meta['color'] ?>-500"></span>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= $meta['label'] ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-bold text-slate-900 dark:text-white"><?= $bucket['count'] ?></span>
                        <?php if ($bucket['improved'] > 0): ?>
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center">
                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            <?= $bucket['improved'] ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($bucket['declined'] > 0): ?>
                        <span class="text-xs text-red-600 dark:text-red-400 flex items-center">
                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <?= $bucket['declined'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ROW 3: Movimenti (tab consolidato: Gainers / Losers / Top Keywords) -->
    <div x-data="{ movTab: 'gainers' }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-700 px-4">
            <nav class="flex gap-6 -mb-px">
                <button @click="movTab = 'gainers'" :class="movTab === 'gainers' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    Miglioramenti
                    <?php if (!empty($gainers)): ?><span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 text-xs px-1.5 py-0.5 rounded-full"><?= count($gainers) ?></span><?php endif; ?>
                </button>
                <button @click="movTab = 'losers'" :class="movTab === 'losers' ? 'border-red-500 text-red-600 dark:text-red-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    Peggioramenti
                    <?php if (!empty($losers)): ?><span class="bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 text-xs px-1.5 py-0.5 rounded-full"><?= count($losers) ?></span><?php endif; ?>
                </button>
                <button @click="movTab = 'top'" :class="movTab === 'top' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Top Keywords
                </button>
            </nav>
        </div>

        <!-- Tab: Gainers -->
        <div x-show="movTab === 'gainers'" x-cloak>
            <?php if (empty($gainers)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun miglioramento rilevato</div>
            <?php else: ?>
            <table class="w-full">
                <thead class="dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prima</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ora</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Delta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($gainers as $g): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white truncate max-w-[200px]"><?= e($g['keyword']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= (int)$g['old_position'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300"><?= (int)$g['new_position'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-medium text-sm">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                <?= abs((int)$g['position_diff']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Tab: Losers -->
        <div x-show="movTab === 'losers'" x-cloak>
            <?php if (empty($losers)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun peggioramento rilevato</div>
            <?php else: ?>
            <table class="w-full">
                <thead class="dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prima</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ora</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Delta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($losers as $l): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white truncate max-w-[200px]"><?= e($l['keyword']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-slate-400"><?= (int)$l['old_position'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"><?= (int)$l['new_position'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center text-red-600 dark:text-red-400 font-medium text-sm">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                <?= abs((int)$l['position_diff']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Tab: Top Keywords -->
        <div x-show="movTab === 'top'" x-cloak>
            <?php if (empty($topKeywords)): ?>
            <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun dato</div>
            <?php else: ?>
            <table class="w-full">
                <thead class="dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Posizione</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($topKeywords as $kw): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($kw['keyword']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $pos = (int)($kw['last_position'] ?? 0);
                            $posClass = $pos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                ($pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300');
                            ?>
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>"><?= $pos ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ROW 4: AI Summary (collapsibile) -->
    <div x-data="{ aiOpen: false }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <button @click="aiOpen = !aiOpen" class="w-full px-5 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Analisi AI</span>
                <?php if ($lastReport): ?>
                <span class="text-xs text-slate-400"><?= date('d/m/Y', strtotime($lastReport['created_at'])) ?></span>
                <?php endif; ?>
            </div>
            <svg :class="aiOpen ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="aiOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-5 pb-5 border-t border-slate-200 dark:border-slate-700">
            <?php if ($lastReport): ?>
            <div class="prose prose-sm dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 leading-relaxed mt-4">
                <?= nl2br(e(mb_substr(strip_tags($lastReport['content'] ?? $lastReport['summary'] ?? ''), 0, 500))) ?>
                <?php if (mb_strlen($lastReport['content'] ?? $lastReport['summary'] ?? '') > 500): ?>
                <span class="text-slate-400">...</span>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-4">
                Nessun report AI disponibile. I report vengono generati automaticamente ogni settimana.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ROW 6: Movimenti Recenti -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
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
                Vai alla sezione "Overview" per controllare le posizioni delle tue keyword
            </p>
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Gestisci Keywords
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Posizione</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Variazione</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($recentMovements as $m): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-slate-900 dark:text-white"><?= e($m['keyword']) ?></span>
                            <span class="ml-2 text-xs text-slate-400"><?= $m['device'] ?></span>
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate">
                            <?php if ($m['serp_url']): ?>
                            <a href="<?= e($m['serp_url']) ?>" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                <?= e(parse_url($m['serp_url'], PHP_URL_PATH) ?: '/') ?>
                            </a>
                            <?php else: ?>
                            <span class="text-slate-400">Non trovato</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($m['serp_position']): ?>
                                <?php
                                $pos = (int)$m['serp_position'];
                                $posClass = $pos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                           ($pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                           ($pos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                                ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>"><?= $pos ?></span>
                            <?php else: ?>
                                <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($m['prev_position'] && $m['serp_position']): ?>
                                <?php $diff = (int)$m['prev_position'] - (int)$m['serp_position']; ?>
                                <?php if ($diff > 0): ?>
                                <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400 text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    +<?= $diff ?>
                                </span>
                                <?php elseif ($diff < 0): ?>
                                <span class="inline-flex items-center text-red-600 dark:text-red-400 text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    <?= $diff ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400">=</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">nuovo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m H:i', strtotime($m['checked_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ROW 7: CTA -->
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
                <p class="text-primary-100 text-sm mt-1">Non hai ancora effettuato nessuna verifica</p>
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

// Stacked Bar Chart - Rankings Distribution Over Time
const distributionHistory = <?= json_encode($distributionHistory ?? []) ?>;
const distCtx = document.getElementById('distributionChart');

if (distCtx && distributionHistory.length > 0) {
    new Chart(distCtx, {
        type: 'bar',
        data: {
            labels: distributionHistory.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [
                {
                    label: 'Top 3',
                    data: distributionHistory.map(d => parseInt(d.top3) || 0),
                    backgroundColor: '#10b981',
                    borderRadius: 2,
                },
                {
                    label: '4-10',
                    data: distributionHistory.map(d => parseInt(d.top4_10) || 0),
                    backgroundColor: '#3b82f6',
                    borderRadius: 2,
                },
                {
                    label: '11-20',
                    data: distributionHistory.map(d => parseInt(d.top11_20) || 0),
                    backgroundColor: '#f59e0b',
                    borderRadius: 2,
                },
                {
                    label: '21-50',
                    data: distributionHistory.map(d => parseInt(d.top21_50) || 0),
                    backgroundColor: '#f97316',
                    borderRadius: 2,
                },
                {
                    label: '51-100',
                    data: distributionHistory.map(d => parseInt(d.top51_100) || 0),
                    backgroundColor: '#94a3b8',
                    borderRadius: 2,
                },
                {
                    label: 'Out',
                    data: distributionHistory.map(d => parseInt(d.out_of_top) || 0),
                    backgroundColor: '#ef4444',
                    borderRadius: 2,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => items[0].label,
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 15 }
                },
                y: {
                    stacked: true,
                    grid: { color: gridColor },
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
} else if (distCtx) {
    const ctx = distCtx.getContext('2d');
    ctx.fillStyle = textColor;
    ctx.font = '14px Inter, system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Nessun dato di distribuzione disponibile', distCtx.width / 2, distCtx.height / 2);
}
</script>
