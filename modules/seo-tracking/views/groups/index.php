<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking/projects/' . $project['id']) ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gruppi Keyword</h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= e($project['name']) ?> - <?= count($groups) ?> gruppi configurati
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Gruppo
            </a>
        </div>
    </div>

    <?php if (empty($groups)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun gruppo configurato</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
            Organizza le tue keyword in gruppi per analizzarle meglio.
        </p>
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea il primo gruppo
        </a>
    </div>
    <?php else: ?>

    <!-- Groups Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($groups as $group): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: <?= e($group['color'] ?? '#006e96') ?>"></div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white"><?= e($group['name']) ?></h3>
                            <?php if ($group['description']): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5"><?= e(substr($group['description'], 0, 60)) ?><?= strlen($group['description']) > 60 ? '...' : '' ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/edit') ?>" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700" title="Modifica">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Stats -->
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($group['keyword_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Keyword</p>
                    </div>
                    <div>
                        <?php
                        $avgPos = (float)($group['avg_position'] ?? 0);
                        $posClass = $avgPos <= 3 ? 'text-emerald-600 dark:text-emerald-400' :
                                   ($avgPos <= 10 ? 'text-blue-600 dark:text-blue-400' :
                                   ($avgPos <= 20 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400'));
                        ?>
                        <p class="text-2xl font-bold <?= $posClass ?>"><?= $avgPos > 0 ? number_format($avgPos, 1) : '-' ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pos. media</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400"><?= number_format($group['total_clicks'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Click</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-slate-700 dark:text-slate-300"><?= number_format($group['total_impressions'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Impressioni</p>
                    </div>
                </div>

                <!-- Position Distribution Bar -->
                <?php if (($group['keyword_count'] ?? 0) > 0): ?>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                        <span>Distribuzione posizioni</span>
                    </div>
                    <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden flex">
                        <?php
                        $total = (int)$group['keyword_count'];
                        $top3 = (int)($group['top3_count'] ?? 0);
                        $top10 = (int)($group['top10_count'] ?? 0) - $top3;
                        $rest = $total - $top3 - $top10;
                        ?>
                        <?php if ($top3 > 0): ?>
                        <div class="bg-emerald-500" style="width: <?= ($top3 / $total) * 100 ?>%"></div>
                        <?php endif; ?>
                        <?php if ($top10 > 0): ?>
                        <div class="bg-blue-500" style="width: <?= ($top10 / $total) * 100 ?>%"></div>
                        <?php endif; ?>
                        <?php if ($rest > 0): ?>
                        <div class="bg-slate-400" style="width: <?= ($rest / $total) * 100 ?>%"></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-xs">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Top 3: <?= $top3 ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Top 10: <?= $top10 ?>
                        </span>
                        <span class="flex items-center gap-1 text-slate-400">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            Altro: <?= $rest ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id']) ?>" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                    Visualizza dettagli
                    <svg class="w-4 h-4 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Comparison Table -->
    <?php if (count($groups) > 1): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Confronto Gruppi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Gruppo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. Media</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Top 10 %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($comparison as $item): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $item['id']) ?>" class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= e($item['color']) ?>"></span>
                                <span class="font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"><?= e($item['name']) ?></span>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($item['keyword_count']) ?></td>
                        <td class="px-6 py-4 text-right">
                            <?php
                            $posClass = $item['avg_position'] <= 3 ? 'text-emerald-600 dark:text-emerald-400' :
                                       ($item['avg_position'] <= 10 ? 'text-blue-600 dark:text-blue-400' :
                                       ($item['avg_position'] <= 20 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600'));
                            ?>
                            <span class="text-sm font-medium <?= $posClass ?>"><?= $item['avg_position'] > 0 ? number_format($item['avg_position'], 1) : '-' ?></span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($item['total_clicks']) ?></td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format($item['total_impressions']) ?></td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $item['top10_percent'] >= 50 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' ?>">
                                <?= number_format($item['top10_percent'], 1) ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
