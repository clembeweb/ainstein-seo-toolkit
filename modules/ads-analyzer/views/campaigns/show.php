<?php $currentPage = 'campaigns'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="campaignRunDetail()">

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Campagne -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format(count($campaigns)) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Campagne</p>
                </div>
            </div>
        </div>

        <!-- Annunci -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($adStats['total_ads'] ?? 0) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Annunci</p>
                </div>
            </div>
        </div>

        <!-- Costo -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($campaignStats['total_cost'] ?? 0, 2, ',', '.') ?> &euro;</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Costo totale</p>
                </div>
            </div>
        </div>

        <!-- Click -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($campaignStats['total_clicks'] ?? 0, 0, ',', '.') ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Click</p>
                </div>
            </div>
        </div>

        <!-- Quality Score Medio -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div>
                    <?php
                    $avgQs = $adStats['avg_quality_score'] ?? 0;
                    $qsColor = $avgQs >= 7 ? 'text-emerald-600 dark:text-emerald-400' : ($avgQs >= 4 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                    ?>
                    <p class="text-2xl font-bold <?= $qsColor ?>"><?= $avgQs > 0 ? number_format($avgQs, 1, ',', '.') : '-' ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">QS medio</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Campagne Section -->
    <?php if (empty($campaigns)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessuna campagna trovata</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Questo run non contiene dati sulle campagne.
            </p>
        </div>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Campagne (<?= count($campaigns) ?>)</h2>

        <?php foreach ($campaigns as $index => $campaign): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
             x-data="{ expanded: <?= $index === 0 ? 'true' : 'false' ?> }">

            <!-- Campaign Header (clickable) -->
            <div class="px-6 py-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" @click="expanded = !expanded">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Campaign icon -->
                        <div class="h-8 w-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </div>
                        <!-- Campaign name and info -->
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900 dark:text-white truncate"><?= e($campaign['campaign_name']) ?></h3>
                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                <?php if (!empty($campaign['campaign_type'])): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($campaign['campaign_type']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($campaign['bidding_strategy'])): ?>
                                <span class="text-slate-300 dark:text-slate-600">|</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($campaign['bidding_strategy']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($campaign['budget_amount']) && $campaign['budget_amount'] > 0): ?>
                                <span class="text-slate-300 dark:text-slate-600">|</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Budget: <?= number_format($campaign['budget_amount'], 2, ',', '.') ?> &euro;</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Status badge -->
                        <?php
                        $campStatusColors = [
                            'ENABLED' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'PAUSED' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                            'REMOVED' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        ];
                        $campStatusLabels = [
                            'ENABLED' => 'Attiva',
                            'PAUSED' => 'In pausa',
                            'REMOVED' => 'Rimossa',
                        ];
                        $campStatusColor = $campStatusColors[$campaign['campaign_status']] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';
                        $campStatusLabel = $campStatusLabels[$campaign['campaign_status']] ?? e($campaign['campaign_status']);
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $campStatusColor ?>">
                            <?= $campStatusLabel ?>
                        </span>
                        <!-- Quick stats -->
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 hidden sm:inline">
                            <?= number_format($campaign['clicks'] ?? 0, 0, ',', '.') ?> click
                        </span>
                        <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:inline">
                            <?= number_format($campaign['cost'] ?? 0, 2, ',', '.') ?> &euro;
                        </span>
                        <!-- Expand/Collapse chevron -->
                        <svg :class="expanded ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Campaign Detail (expandable) -->
            <div x-show="expanded" x-collapse>
                <!-- Performance Metrics -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($campaign['clicks'] ?? 0, 0, ',', '.') ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Click</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($campaign['impressions'] ?? 0, 0, ',', '.') ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Impression</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format(($campaign['ctr'] ?? 0) * 100, 2, ',', '.') ?>%</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">CTR</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($campaign['avg_cpc'] ?? 0, 2, ',', '.') ?> &euro;</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">CPC medio</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($campaign['cost'] ?? 0, 2, ',', '.') ?> &euro;</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Costo</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($campaign['conversions'] ?? 0, 0, ',', '.') ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Conversioni</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($campaign['conversion_value'] ?? 0, 2, ',', '.') ?> &euro;</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Valore conv.</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format(($campaign['conv_rate'] ?? 0) * 100, 2, ',', '.') ?>%</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Tasso conv.</p>
                        </div>
                    </div>
                </div>

                <!-- Annunci Table -->
                <?php
                $campaignAds = $adsByCampaign[$campaign['campaign_name']] ?? [];
                ?>
                <?php if (!empty($campaignAds)): ?>
                <div class="border-t border-slate-200 dark:border-slate-700">
                    <div class="px-6 py-3 bg-slate-50/50 dark:bg-slate-700/20">
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Annunci (<?= count($campaignAds) ?>)
                        </h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gruppo</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Titoli</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Descrizioni</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">QS</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">CTR</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <?php foreach ($campaignAds as $ad): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                        <?= e($ad['ad_group_name'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm max-w-xs">
                                        <?php
                                        $headlines = array_filter([
                                            $ad['headline1'] ?? '',
                                            $ad['headline2'] ?? '',
                                            $ad['headline3'] ?? '',
                                        ]);
                                        ?>
                                        <?php if (!empty($headlines)): ?>
                                        <div class="text-slate-900 dark:text-white font-medium">
                                            <?= e(implode(' | ', $headlines)) ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400 max-w-xs">
                                        <?php
                                        $descriptions = array_filter([
                                            $ad['description1'] ?? '',
                                            $ad['description2'] ?? '',
                                        ]);
                                        ?>
                                        <?php if (!empty($descriptions)): ?>
                                        <div class="truncate" title="<?= e(implode(' | ', $descriptions)) ?>">
                                            <?= e(implode(' | ', $descriptions)) ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm max-w-xs">
                                        <?php if (!empty($ad['final_url'])): ?>
                                        <div class="truncate">
                                            <span class="text-blue-600 dark:text-blue-400" title="<?= e($ad['final_url']) ?>">
                                                <?= e($ad['final_url']) ?>
                                            </span>
                                            <?php if (!empty($ad['path1'])): ?>
                                            <div class="text-xs text-slate-400 mt-0.5">
                                                /<?= e($ad['path1']) ?><?php if (!empty($ad['path2'])): ?>/<?= e($ad['path2']) ?><?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center whitespace-nowrap">
                                        <?php if (!empty($ad['quality_score']) && $ad['quality_score'] > 0): ?>
                                        <?php
                                        $qs = (int) $ad['quality_score'];
                                        $qsBg = $qs >= 7 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                                            : ($qs >= 4 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300');
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold <?= $qsBg ?>">
                                            <?= $qs ?>/10
                                        </span>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300 whitespace-nowrap font-medium">
                                        <?= number_format($ad['clicks'] ?? 0, 0, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <?= number_format(($ad['ctr'] ?? 0) * 100, 2, ',', '.') ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="border-t border-slate-200 dark:border-slate-700 px-6 py-6">
                    <p class="text-sm text-slate-500 dark:text-slate-400 text-center">Nessun annuncio trovato per questa campagna.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Estensioni Section -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Estensioni annuncio</h2>
        </div>

        <?php if (empty($extensions)): ?>
        <div class="px-6 py-12">
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                </svg>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nessuna estensione trovata per questo run.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php
            $extensionTypeLabels = [
                'SITELINK' => 'Sitelink',
                'CALLOUT' => 'Callout',
                'STRUCTURED_SNIPPET' => 'Snippet strutturati',
                'CALL' => 'Chiamata',
                'LOCATION' => 'Posizione',
                'PRICE' => 'Prezzo',
                'PROMOTION' => 'Promozione',
                'IMAGE' => 'Immagine',
            ];
            $extensionTypeIcons = [
                'SITELINK' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                'CALLOUT' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
                'STRUCTURED_SNIPPET' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
            ];
            ?>
            <?php foreach ($extensions as $type => $items): ?>
            <div class="px-6 py-4" x-data="{ showExt: true }">
                <!-- Extension Type Header -->
                <div class="flex items-center justify-between mb-3 cursor-pointer" @click="showExt = !showExt">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $extensionTypeIcons[$type] ?? 'M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z' ?>"/>
                        </svg>
                        <h3 class="font-medium text-slate-900 dark:text-white">
                            <?= $extensionTypeLabels[$type] ?? e($type) ?>
                        </h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                            <?= count($items) ?>
                        </span>
                    </div>
                    <svg :class="showExt ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                <!-- Extension Items -->
                <div x-show="showExt" x-collapse>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Testo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Impression</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <?php foreach ($items as $ext): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="px-4 py-2.5 text-sm text-slate-900 dark:text-white">
                                        <?= e($ext['extension_text'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm whitespace-nowrap">
                                        <?php
                                        $extStatus = $ext['status'] ?? 'UNKNOWN';
                                        $extStatusColors = [
                                            'ENABLED' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                            'PAUSED' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                                            'REMOVED' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                        ];
                                        $extStatusLabelsMap = [
                                            'ENABLED' => 'Attiva',
                                            'PAUSED' => 'In pausa',
                                            'REMOVED' => 'Rimossa',
                                        ];
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $extStatusColors[$extStatus] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                                            <?= $extStatusLabelsMap[$extStatus] ?? e($extStatus) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-right text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap">
                                        <?= number_format($ext['clicks'] ?? 0, 0, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-right text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <?= number_format($ext['impressions'] ?? 0, 0, ',', '.') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function campaignRunDetail() {
    return {
        // Placeholder for future interactivity (filters, export, etc.)
    };
}
</script>
