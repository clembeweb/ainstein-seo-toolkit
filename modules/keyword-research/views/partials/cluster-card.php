<?php
/**
 * Cluster Card Partial
 * Variabili: $cluster, $index
 * Richiede: table-helpers.php incluso nel parent view
 */
$intentColors = [
    'informational' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'transactional' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'commercial' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'navigational' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
];
$intentColor = $intentColors[strtolower($cluster['intent'] ?? '')] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
     x-data='clusterCard(<?= json_encode($cluster["keywords_list"] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'
     @toggle-all-clusters.window="expanded = $event.detail.expand">
    <!-- Card Header -->
    <div class="p-5 cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500">#<?= $index + 1 ?></span>
                    <h3 class="font-semibold text-slate-900 dark:text-white"><?= e($cluster['name']) ?></h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $intentColor ?>">
                        <?= e(ucfirst($cluster['intent'] ?? 'N/A')) ?>
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Main: <span class="font-medium text-slate-700 dark:text-slate-300"><?= e($cluster['main_keyword']) ?></span>
                    <span class="text-slate-400 ml-1">(vol: <?= number_format($cluster['main_volume']) ?>)</span>
                </p>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        <!-- Stats -->
        <div class="mt-3 grid grid-cols-3 gap-3">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2 px-3 text-center">
                <p class="text-lg font-bold text-slate-900 dark:text-white"><?= $cluster['keywords_count'] ?></p>
                <p class="text-xs text-slate-500">Keywords</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2 px-3 text-center">
                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($cluster['total_volume']) ?></p>
                <p class="text-xs text-slate-500">Volume</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg py-2 px-3 text-center">
                <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($cluster['main_volume']) ?></p>
                <p class="text-xs text-slate-500">Vol. Main</p>
            </div>
        </div>

        <?php if (!empty($cluster['note'])): ?>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400 italic"><?= e($cluster['note']) ?></p>
        <?php endif; ?>

        <?php if (!empty($cluster['suggested_url'])): ?>
        <div class="mt-2 text-xs text-slate-400">
            URL suggerito: <span class="font-mono text-primary-600 dark:text-primary-400"><?= e($cluster['suggested_url']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Expanded: Keywords list with sorting -->
    <div x-show="expanded" x-transition class="border-t border-slate-200 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left cursor-pointer select-none" @click="toggleSort('text')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Keyword <span x-html="krSortIcon('text', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-4 py-2 text-right cursor-pointer select-none" @click="toggleSort('volume')">
                            <span class="inline-flex items-center justify-end text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Volume <span x-html="krSortIcon('volume', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-4 py-2 text-center cursor-pointer select-none" @click="toggleSort('competition_level')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Comp. <span x-html="krSortIcon('competition_level', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-4 py-2 text-right cursor-pointer select-none" @click="toggleSort('high_bid')">
                            <span class="inline-flex items-center justify-end text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                CPC <span x-html="krSortIcon('high_bid', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-4 py-2 text-center cursor-pointer select-none" @click="toggleSort('intent')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Intent <span x-html="krSortIcon('intent', sortField, sortDir)"></span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <template x-for="kw in sortedKeywords" :key="kw.text">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30" :class="kw.is_main ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : ''">
                        <td class="px-4 py-2 text-slate-900 dark:text-white">
                            <template x-if="kw.is_main">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span x-text="kw.text"></span>
                                </span>
                            </template>
                            <template x-if="!kw.is_main">
                                <span x-text="kw.text"></span>
                            </template>
                        </td>
                        <td class="px-4 py-2 text-right text-slate-700 dark:text-slate-300 font-medium" x-text="(kw.volume || 0).toLocaleString()"></td>
                        <td class="px-4 py-2 text-center">
                            <span class="text-xs" :class="krCompClass(kw.competition_level)" x-text="kw.competition_level ? kw.competition_level.charAt(0).toUpperCase() + kw.competition_level.slice(1) : '-'"></span>
                        </td>
                        <td class="px-4 py-2 text-right text-slate-500 text-xs" x-text="kw.high_bid > 0 ? kw.high_bid.toFixed(2) : '-'"></td>
                        <td class="px-4 py-2 text-center">
                            <template x-if="kw.intent">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs" :class="krIntentClass(kw.intent)" x-text="kw.intent.charAt(0).toUpperCase() + kw.intent.slice(1)"></span>
                            </template>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
