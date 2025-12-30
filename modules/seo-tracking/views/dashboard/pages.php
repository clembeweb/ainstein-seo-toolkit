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
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($project['domain']) ?></p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-6">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/dashboard') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Overview
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords-overview') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Keywords
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/pages') ?>" class="py-3 px-1 border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-medium text-sm">
                Pagine
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/revenue') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Revenue
            </a>
        </nav>
    </div>

    <!-- Date Range Info -->
    <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>Periodo: <?= date('d/m/Y', strtotime($dateRange['start'])) ?> - <?= date('d/m/Y', strtotime($dateRange['end'])) ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Pages by Sessions -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Top Pagine per Sessioni</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Traffico organico da GA4</p>
            </div>
            <?php if (empty($topPagesBySessions)): ?>
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato disponibile. Connetti GA4 e sincronizza.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagina</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Sessioni</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Utenti</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Eng. Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($topPagesBySessions as $page): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-xs" title="<?= e($page['landing_page']) ?>">
                                    <?= e($page['landing_page']) ?>
                                </p>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">
                                <?= number_format($page['sessions'] ?? 0) ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format($page['users'] ?? 0) ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format(($page['engagement_rate'] ?? 0) * 100, 1) ?>%
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
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Revenue attribuito da traffico organico</p>
            </div>
            <?php if (empty($topPagesByRevenue)): ?>
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato revenue disponibile.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagina</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acquisti</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($topPagesByRevenue as $page): ?>
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
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format($page['total_purchases'] ?? 0) ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format($page['total_clicks'] ?? 0) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Come funziona il Revenue Attribution</h4>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Il revenue viene attribuito alle pagine basandosi sui dati GA4 per landing page del traffico organico.
                    Per ogni landing page con conversioni, distribuiamo il revenue proporzionalmente ai click delle keyword GSC che hanno portato traffico a quella pagina.
                </p>
            </div>
        </div>
    </div>
</div>
