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
        <div class="mt-4 sm:mt-0">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/add') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Keyword
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-6">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/dashboard') ?>" class="py-3 px-1 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-medium text-sm">
                Overview
            </a>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords-overview') ?>" class="py-3 px-1 border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-medium text-sm">
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

    <!-- Position Distribution -->
    <div class="grid grid-cols-5 gap-4">
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 text-center border border-emerald-200 dark:border-emerald-800">
            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300"><?= $positionDistribution['top3'] ?></p>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Top 3</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center border border-blue-200 dark:border-blue-800">
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300"><?= $positionDistribution['top10'] ?></p>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">4-10</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 text-center border border-amber-200 dark:border-amber-800">
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-300"><?= $positionDistribution['top20'] ?></p>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">11-20</p>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 text-center border border-orange-200 dark:border-orange-800">
            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300"><?= $positionDistribution['top50'] ?></p>
            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">21-50</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 text-center border border-slate-200 dark:border-slate-700">
            <p class="text-2xl font-bold text-slate-700 dark:text-slate-300"><?= $positionDistribution['beyond50'] ?></p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">50+</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="keywordSearch" placeholder="Cerca keyword..."
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       onkeyup="filterKeywords()">
            </div>
            <select id="positionFilter" onchange="filterKeywords()" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutte le posizioni</option>
                <option value="top3">Top 3</option>
                <option value="top10">Top 10</option>
                <option value="top20">Top 20</option>
                <option value="top50">Top 50</option>
            </select>
            <select id="groupFilter" onchange="filterKeywords()" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutti i gruppi</option>
                <?php foreach ($groups as $group): ?>
                <option value="<?= e($group['group_name']) ?>"><?= e($group['group_name']) ?> (<?= $group['count'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Keywords Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <?php if (empty($keywords)): ?>
        <div class="p-12 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna keyword</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Sincronizza GSC per scoprire le keyword o aggiungile manualmente</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full" id="keywordsTable">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                            <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600" onclick="toggleAll(this)">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Variazione</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impressioni</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CTR</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tracciata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($keywords as $kw): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 keyword-row"
                        data-keyword="<?= e(strtolower($kw['keyword'])) ?>"
                        data-position="<?= $kw['last_position'] ?? 100 ?>"
                        data-group="<?= e($kw['group_name'] ?? '') ?>">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 keyword-checkbox" value="<?= $kw['id'] ?>">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($kw['keyword']) ?></p>
                                <?php if ($kw['is_tracked']): ?>
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php endif; ?>
                            </div>
                            <?php if ($kw['group_name']): ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($kw['group_name']) ?></span>
                            <?php endif; ?>
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
                            if ($change != 0):
                                $isUp = $change < 0;
                                $changeClass = $isUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                            ?>
                            <span class="flex items-center justify-end gap-1 text-sm <?= $changeClass ?>">
                                <?php if ($isUp): ?>
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php else: ?>
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php endif; ?>
                                <?= abs($change) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-sm text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($kw['last_clicks'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format($kw['last_impressions'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                            <?php
                            $ctr = ($kw['last_impressions'] ?? 0) > 0 ? ($kw['last_clicks'] / $kw['last_impressions']) * 100 : 0;
                            echo number_format($ctr, 2) . '%';
                            ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="toggleTracking(<?= $kw['id'] ?>, <?= $kw['is_tracked'] ? 'false' : 'true' ?>)"
                                    class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700">
                                <?php if ($kw['is_tracked']): ?>
                                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php else: ?>
                                <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <?php endif; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterKeywords() {
    const search = document.getElementById('keywordSearch').value.toLowerCase();
    const posFilter = document.getElementById('positionFilter').value;
    const groupFilter = document.getElementById('groupFilter').value;

    document.querySelectorAll('.keyword-row').forEach(row => {
        const keyword = row.dataset.keyword;
        const position = parseFloat(row.dataset.position);
        const group = row.dataset.group;

        let show = true;

        if (search && !keyword.includes(search)) show = false;

        if (posFilter) {
            switch (posFilter) {
                case 'top3': if (position > 3) show = false; break;
                case 'top10': if (position > 10) show = false; break;
                case 'top20': if (position > 20) show = false; break;
                case 'top50': if (position > 50) show = false; break;
            }
        }

        if (groupFilter && group !== groupFilter) show = false;

        row.style.display = show ? '' : 'none';
    });
}

function toggleAll(checkbox) {
    document.querySelectorAll('.keyword-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

async function toggleTracking(keywordId, track) {
    const response = await fetch(`<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords') ?>/${keywordId}/toggle-tracking`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ track })
    });

    if (response.ok) {
        location.reload();
    }
}
</script>
