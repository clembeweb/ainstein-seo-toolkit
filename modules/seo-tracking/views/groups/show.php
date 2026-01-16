<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full" style="background-color: <?= e($group['color'] ?? '#006e96') ?>"></div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($group['name']) ?></h1>
                </div>
            </div>
            <?php if ($group['description']): ?>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($group['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/ai/quick-wins') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 text-white font-medium hover:from-amber-600 hover:to-orange-600 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Wins
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/edit') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Modifica
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-primary-100 dark:bg-primary-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_keywords']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Keyword</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <?php
                    $avgPos = $stats['avg_position'];
                    $posClass = $avgPos <= 3 ? 'text-emerald-600 dark:text-emerald-400' :
                               ($avgPos <= 10 ? 'text-blue-600 dark:text-blue-400' :
                               ($avgPos <= 20 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400'));
                    ?>
                    <p class="text-2xl font-bold <?= $posClass ?>">
                        <?= $avgPos > 0 ? number_format($avgPos, 1) : '-' ?>
                        <?php if (isset($stats['position_trend'])): ?>
                        <?php if ($stats['position_trend'] === 'up'): ?>
                        <svg class="w-4 h-4 inline text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <?php elseif ($stats['position_trend'] === 'down'): ?>
                        <svg class="w-4 h-4 inline text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <?php endif; ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Posizione media</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['total_clicks']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Click totali</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-violet-600 dark:text-violet-400"><?= number_format($stats['total_impressions']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Impressioni</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribution and Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Position Distribution -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Posizioni</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-sm text-slate-700 dark:text-slate-300">Top 3</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= $stats['top3_count'] ?></span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= $stats['total_keywords'] > 0 ? ($stats['top3_count'] / $stats['total_keywords']) * 100 : 0 ?>%"></div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-sm text-slate-700 dark:text-slate-300">Top 4-10</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= $stats['top10_count'] - $stats['top3_count'] ?></span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $stats['total_keywords'] > 0 ? (($stats['top10_count'] - $stats['top3_count']) / $stats['total_keywords']) * 100 : 0 ?>%"></div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-sm text-slate-700 dark:text-slate-300">Top 11-20</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= $stats['top20_count'] ?></span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-amber-500 h-2 rounded-full" style="width: <?= $stats['total_keywords'] > 0 ? ($stats['top20_count'] / $stats['total_keywords']) * 100 : 0 ?>%"></div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                        <span class="text-sm text-slate-700 dark:text-slate-300">Oltre 20</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= $stats['beyond20_count'] ?></span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-slate-400 h-2 rounded-full" style="width: <?= $stats['total_keywords'] > 0 ? ($stats['beyond20_count'] / $stats['total_keywords']) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Historical Comparison -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Confronto 7 giorni</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Posizione media</span>
                    <div class="text-right">
                        <span class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($historical['current']['avg_position'], 1) ?></span>
                        <?php if ($historical['changes']['position'] != 0): ?>
                        <span class="ml-2 text-sm <?= $historical['changes']['position'] > 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $historical['changes']['position'] > 0 ? '+' : '' ?><?= number_format($historical['changes']['position'], 1) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Click</span>
                    <div class="text-right">
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($historical['current']['total_clicks']) ?></span>
                        <?php if ($historical['changes']['clicks_percent'] !== null): ?>
                        <span class="ml-2 text-sm <?= $historical['changes']['clicks_percent'] > 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $historical['changes']['clicks_percent'] > 0 ? '+' : '' ?><?= number_format($historical['changes']['clicks_percent'], 1) ?>%
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Impressioni</span>
                    <div class="text-right">
                        <span class="text-lg font-bold text-slate-700 dark:text-slate-300"><?= number_format($historical['current']['total_impressions']) ?></span>
                        <?php if ($historical['changes']['impressions_percent'] !== null): ?>
                        <span class="ml-2 text-sm <?= $historical['changes']['impressions_percent'] > 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $historical['changes']['impressions_percent'] > 0 ? '+' : '' ?><?= number_format($historical['changes']['impressions_percent'], 1) ?>%
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers & Movers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Performers -->
        <?php if (!empty($topPerformers)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Top Performer</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Keyword con più click</p>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($topPerformers as $kw): ?>
                <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                        <?= e($kw['keyword']) ?>
                    </a>
                    <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400"><?= number_format($kw['last_clicks'] ?? 0) ?> click</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Movers -->
        <?php if (!empty($topMovers)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Maggiori Variazioni</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Keyword con più movimento</p>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($topMovers as $kw): ?>
                <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                        <?= e($kw['keyword']) ?>
                    </a>
                    <span class="text-sm font-semibold <?= ($kw['position_change'] ?? 0) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= ($kw['position_change'] ?? 0) > 0 ? '+' : '' ?><?= number_format($kw['position_change'] ?? 0, 1) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Keywords Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword nel Gruppo</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= count($keywords) ?> keyword</p>
            </div>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/edit') ?>" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                Gestisci keyword
            </a>
        </div>

        <?php if (empty($keywords)): ?>
        <div class="p-8 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Nessuna keyword in questo gruppo</p>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/edit') ?>" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
                Aggiungi keyword
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Variazione</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($keywords as $kw): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                <?= e($kw['keyword']) ?>
                            </a>
                        </td>
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
                        <td class="px-6 py-4 text-right">
                            <?php
                            $change = $kw['position_change'] ?? 0;
                            $changeClass = $change > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($change < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-400');
                            ?>
                            <span class="text-sm font-medium <?= $changeClass ?>">
                                <?php if ($change != 0): ?>
                                <?= $change > 0 ? '+' : '' ?><?= number_format($change, 1) ?>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($kw['last_clicks'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format($kw['last_impressions'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-900/50 p-6">
        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Zona Pericolosa</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">L'eliminazione del gruppo non elimina le keyword, solo l'associazione.</p>
        <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questo gruppo?')">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                Elimina Gruppo
            </button>
        </form>
    </div>
</div>
