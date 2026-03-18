<?php
/**
 * Report Campaign Table — 3-level expandable (Campaign → Ad Group → Ads/Keywords)
 *
 * Expected variables from parent view:
 *   $syncMetrics  — hierarchical sync data: campaigns[] → ad_groups[] → ads[], keywords[]
 *   $aiResponse   — AI response: campaigns[] → ad_groups[] → ads_analysis[], landing_analysis, optimizations[]
 *   $viewCampaigns — merged array (sync + AI per campaign), prepared by parent view
 *
 * Parent view wraps in x-data="evaluationReport()" providing:
 *   expandedCampaigns{}, expandedAdGroups{}
 */

// Campaign type badge configuration
$campaignTypeConfig = [
    'SEARCH' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Search'],
    'SHOPPING' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Shopping'],
    'PERFORMANCE_MAX' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'PMax'],
    'DISPLAY' => ['bg' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300', 'label' => 'Display'],
    'VIDEO' => ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'label' => 'Video'],
];

// Match type badge configuration
$matchTypeConfig = [
    'BROAD' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Broad'],
    'PHRASE' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Phrase'],
    'EXACT' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'Exact'],
];

// Asset performance badge configuration
$assetPerfConfig = [
    'BEST' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'GOOD' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'LOW' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    'LEARNING' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'UNSPECIFIED' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
];

// Ad strength badge configuration
$adStrengthConfig = [
    'EXCELLENT' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'GOOD' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'AVERAGE' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'POOR' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];

// Helper functions
function roasColorClass(float $roas): string {
    if ($roas >= 4) return 'text-emerald-600 dark:text-emerald-400';
    if ($roas >= 2) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
}

function aiScoreDotClass(float $score): string {
    if ($score >= 7) return 'bg-emerald-500';
    if ($score >= 5) return 'bg-amber-500';
    return 'bg-red-500';
}

function aiScoreBorderClass(float $score): string {
    if ($score >= 7) return 'border-emerald-500';
    if ($score >= 5) return 'border-amber-500';
    return 'border-red-500';
}

function formatNum(float $val, int $decimals = 0): string {
    return number_format($val, $decimals, ',', '.');
}

// Calculate account average CPA for comparison
$totalCost = 0;
$totalConversions = 0;
foreach ($viewCampaigns as $c) {
    $totalCost += (float)($c['cost'] ?? 0);
    $totalConversions += (float)($c['conversions'] ?? 0);
}
$accountAvgCpa = $totalConversions > 0 ? $totalCost / $totalConversions : 0;
?>

<?php if (empty($viewCampaigns)): ?>
<!-- Empty state -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12">
    <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessuna campagna trovata</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">I dati delle campagne non sono disponibili per questa valutazione.</p>
    </div>
</div>
<?php else: ?>

<!-- ==================== LEVEL 1: Campaigns Table ==================== -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Campagne (<?= count($viewCampaigns) ?>)</h2>
        <button @click="Object.keys(expandedCampaigns).length ? expandedCampaigns = {} : <?php foreach ($viewCampaigns as $ci => $c): ?>expandedCampaigns[<?= $ci ?>] = true;<?php endforeach; ?>"
                class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
            Espandi/Comprimi tutto
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                    <th class="px-4 py-3 text-left">Campagna</th>
                    <th class="px-4 py-3 text-right">Click</th>
                    <th class="px-4 py-3 text-right">CTR</th>
                    <th class="px-4 py-3 text-right">Spesa</th>
                    <th class="px-4 py-3 text-right">Conv.</th>
                    <th class="px-4 py-3 text-right">Val. Conv.</th>
                    <th class="px-4 py-3 text-right">ROAS</th>
                    <th class="px-4 py-3 text-right">CPA</th>
                    <th class="px-4 py-3 text-center w-10">AI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($viewCampaigns as $cIdx => $campaign): ?>
                <?php
                    $cType = strtoupper($campaign['campaign_type'] ?? 'SEARCH');
                    $cTypeConf = $campaignTypeConfig[$cType] ?? ['bg' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'label' => $cType];
                    $isPmax = $cType === 'PERFORMANCE_MAX';

                    $cClicks = (int)($campaign['clicks'] ?? 0);
                    $cCtr = (float)($campaign['ctr'] ?? 0);
                    $cCost = (float)($campaign['cost'] ?? 0);
                    $cConv = (float)($campaign['conversions'] ?? 0);
                    $cConvValue = (float)($campaign['conversion_value'] ?? 0);
                    $cRoas = $cCost > 0 ? $cConvValue / $cCost : 0;
                    $cCpa = $cConv > 0 ? $cCost / $cConv : 0;
                    $cScore = (float)($campaign['ai_score'] ?? 0);

                    $cpaAboveAvg = $accountAvgCpa > 0 && $cCpa > $accountAvgCpa;

                    // AI data for this campaign
                    $aiCampaign = $campaign['ai_data'] ?? [];
                    $aiAdGroups = $aiCampaign['ad_groups'] ?? [];
                    // Sync ad groups
                    $syncAdGroups = $campaign['ad_groups'] ?? [];
                ?>
                <!-- Campaign Row -->
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                    @click="expandedCampaigns[<?= $cIdx ?>] = !expandedCampaigns[<?= $cIdx ?>]">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <!-- Expand indicator -->
                            <svg :class="expandedCampaigns[<?= $cIdx ?>] ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition-transform duration-200 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <div class="min-w-0">
                                <div class="font-medium text-slate-900 dark:text-white truncate max-w-xs" title="<?= e($campaign['campaign_name'] ?? '') ?>">
                                    <?= e($campaign['campaign_name'] ?? 'Senza nome') ?>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-0.5 <?= $cTypeConf['bg'] ?>">
                                    <?= $cTypeConf['label'] ?>
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($cClicks) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($cCtr, 2) ?>%</td>
                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($cCost, 2) ?> &euro;</td>
                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($cConv) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($cConvValue, 2) ?> &euro;</td>
                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap <?= roasColorClass($cRoas) ?>">
                        <?= $cCost > 0 ? formatNum($cRoas, 2) . 'x' : '-' ?>
                    </td>
                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap <?= $cpaAboveAvg ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' ?>">
                        <?= $cConv > 0 ? formatNum($cCpa, 2) . ' &euro;' : '-' ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($cScore > 0): ?>
                        <span class="w-3 h-3 rounded-full inline-block <?= aiScoreDotClass($cScore) ?>" title="Score AI: <?= formatNum($cScore, 1) ?>/10"></span>
                        <?php else: ?>
                        <span class="w-3 h-3 rounded-full inline-block bg-slate-300 dark:bg-slate-600" title="Non valutata"></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- ==================== LEVEL 2: Expanded Campaign Detail ==================== -->
                <tr x-show="expandedCampaigns[<?= $cIdx ?>]" x-collapse x-cloak>
                    <td colspan="9" class="p-0">
                        <div class="border-l-2 border-rose-500 ml-4 bg-slate-50/50 dark:bg-slate-800/50">

                            <?php // AI metrics comment box ?>
                            <?php $metricsComment = $aiCampaign['metrics_comment'] ?? ''; ?>
                            <?php if (!empty($metricsComment)): ?>
                            <div class="mx-4 mt-4 mb-3 p-3 rounded-lg border-l-4 <?= aiScoreBorderClass($cScore) ?> bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Analisi AI</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($metricsComment) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($isPmax): ?>
                            <!-- ========== PMax: Asset Groups Sub-table ========== -->
                            <?php if (!empty($syncAdGroups)): ?>
                            <div class="mx-4 mb-4">
                                <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 mt-3">Asset Group (<?= count($syncAdGroups) ?>)</h4>
                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                                                    <th class="px-4 py-3 text-left">Asset Group</th>
                                                    <th class="px-4 py-3 text-center">Ad Strength</th>
                                                    <th class="px-4 py-3 text-right">Click</th>
                                                    <th class="px-4 py-3 text-right">Spesa</th>
                                                    <th class="px-4 py-3 text-right">Conv.</th>
                                                    <th class="px-4 py-3 text-right">ROAS</th>
                                                    <th class="px-4 py-3 text-right">CPA</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                <?php foreach ($syncAdGroups as $agIdx => $ag): ?>
                                                <?php
                                                    $agClicks = (int)($ag['clicks'] ?? 0);
                                                    $agCost = (float)($ag['cost'] ?? 0);
                                                    $agConv = (float)($ag['conversions'] ?? 0);
                                                    $agConvValue = (float)($ag['conversion_value'] ?? 0);
                                                    $agRoas = $agCost > 0 ? $agConvValue / $agCost : 0;
                                                    $agCpa = $agConv > 0 ? $agCost / $agConv : 0;
                                                    $agStrength = strtoupper($ag['ad_strength'] ?? 'UNSPECIFIED');
                                                    $agStrengthClass = $adStrengthConfig[$agStrength] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
                                                    $agKey = $cIdx . '_' . $agIdx;
                                                ?>
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                                                    @click.stop="expandedAdGroups['<?= $agKey ?>'] = !expandedAdGroups['<?= $agKey ?>']">
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <svg :class="expandedAdGroups['<?= $agKey ?>'] ? 'rotate-90' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                            </svg>
                                                            <span class="font-medium text-slate-900 dark:text-white truncate max-w-xs" title="<?= e($ag['ad_group_name'] ?? '') ?>">
                                                                <?= e($ag['ad_group_name'] ?? 'Senza nome') ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $agStrengthClass ?>">
                                                            <?= e($agStrength) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agClicks) ?></td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agCost, 2) ?> &euro;</td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agConv) ?></td>
                                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap <?= roasColorClass($agRoas) ?>">
                                                        <?= $agCost > 0 ? formatNum($agRoas, 2) . 'x' : '-' ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                                        <?= $agConv > 0 ? formatNum($agCpa, 2) . ' &euro;' : '-' ?>
                                                    </td>
                                                </tr>

                                                <!-- ==================== LEVEL 3: PMax Asset Group Detail ==================== -->
                                                <tr x-show="expandedAdGroups['<?= $agKey ?>']" x-collapse x-cloak>
                                                    <td colspan="7" class="p-0">
                                                        <div class="border-l-2 border-purple-500 ml-6 p-4 bg-slate-50/80 dark:bg-slate-900/30 space-y-4">

                                                            <?php // AI analysis for this asset group ?>
                                                            <?php
                                                            $aiAg = $aiAdGroups[$agIdx] ?? $aiAdGroups[$ag['ad_group_name'] ?? ''] ?? [];
                                                            $agAnalysis = $aiAg['analysis'] ?? $aiAg['comment'] ?? '';
                                                            ?>
                                                            <?php if (!empty($agAnalysis)): ?>
                                                            <div class="p-3 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Analisi AI Asset Group</p>
                                                                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($agAnalysis) ?></p>
                                                            </div>
                                                            <?php endif; ?>

                                                            <?php // Asset inventory by type ?>
                                                            <?php $assets = $ag['assets'] ?? []; ?>
                                                            <?php if (!empty($assets)): ?>
                                                            <div>
                                                                <h5 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Asset</h5>
                                                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                                                    <table class="w-full text-sm">
                                                                        <thead>
                                                                            <tr class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                                                                                <th class="px-4 py-3 text-left">Tipo</th>
                                                                                <th class="px-4 py-3 text-left">Contenuto</th>
                                                                                <th class="px-4 py-3 text-center">Performance</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                                            <?php foreach ($assets as $asset): ?>
                                                                            <?php
                                                                                $aPerf = strtoupper($asset['performance_label'] ?? $asset['performance'] ?? 'UNSPECIFIED');
                                                                                $aPerfClass = $assetPerfConfig[$aPerf] ?? $assetPerfConfig['UNSPECIFIED'];
                                                                                $isLow = $aPerf === 'LOW';
                                                                            ?>
                                                                            <tr class="<?= $isLow ? 'bg-red-50 dark:bg-red-900/10' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50' ?> transition-colors">
                                                                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                                                                    <span class="text-xs font-medium"><?= e($asset['asset_type'] ?? $asset['type'] ?? '-') ?></span>
                                                                                </td>
                                                                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 max-w-sm">
                                                                                    <div class="truncate" title="<?= e($asset['content'] ?? $asset['text'] ?? '') ?>">
                                                                                        <?= e(mb_strimwidth($asset['content'] ?? $asset['text'] ?? '-', 0, 80, '...')) ?>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="px-4 py-3 text-center">
                                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $aPerfClass ?>">
                                                                                        <?= e($aPerf) ?>
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>

                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php else: ?>
                            <!-- ========== Search/Shopping/Display/Video: Ad Groups Sub-table ========== -->
                            <?php if (!empty($syncAdGroups)): ?>
                            <div class="mx-4 mb-4">
                                <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 mt-3">Gruppi di annunci (<?= count($syncAdGroups) ?>)</h4>
                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                                                    <th class="px-4 py-3 text-left">Gruppo Annunci</th>
                                                    <th class="px-4 py-3 text-right">Click</th>
                                                    <th class="px-4 py-3 text-right">CTR</th>
                                                    <th class="px-4 py-3 text-right">Spesa</th>
                                                    <th class="px-4 py-3 text-right">Conv.</th>
                                                    <th class="px-4 py-3 text-right">ROAS</th>
                                                    <th class="px-4 py-3 text-right">CPA</th>
                                                    <th class="px-4 py-3 text-left">Landing URL</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                <?php foreach ($syncAdGroups as $agIdx => $ag): ?>
                                                <?php
                                                    $agClicks = (int)($ag['clicks'] ?? 0);
                                                    $agCtr = (float)($ag['ctr'] ?? 0);
                                                    $agCost = (float)($ag['cost'] ?? 0);
                                                    $agConv = (float)($ag['conversions'] ?? 0);
                                                    $agConvValue = (float)($ag['conversion_value'] ?? 0);
                                                    $agRoas = $agCost > 0 ? $agConvValue / $agCost : 0;
                                                    $agCpa = $agConv > 0 ? $agCost / $agConv : 0;
                                                    $agLandingUrl = $ag['landing_url'] ?? $ag['final_url'] ?? '';
                                                    $agKey = $cIdx . '_' . $agIdx;
                                                ?>
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                                                    @click.stop="expandedAdGroups['<?= $agKey ?>'] = !expandedAdGroups['<?= $agKey ?>']">
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <svg :class="expandedAdGroups['<?= $agKey ?>'] ? 'rotate-90' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                            </svg>
                                                            <span class="font-medium text-slate-900 dark:text-white truncate max-w-xs" title="<?= e($ag['ad_group_name'] ?? '') ?>">
                                                                <?= e($ag['ad_group_name'] ?? 'Senza nome') ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agClicks) ?></td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agCtr, 2) ?>%</td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agCost, 2) ?> &euro;</td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($agConv) ?></td>
                                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap <?= roasColorClass($agRoas) ?>">
                                                        <?= $agCost > 0 ? formatNum($agRoas, 2) . 'x' : '-' ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                                        <?= $agConv > 0 ? formatNum($agCpa, 2) . ' &euro;' : '-' ?>
                                                    </td>
                                                    <td class="px-4 py-3 max-w-xs">
                                                        <?php if (!empty($agLandingUrl)): ?>
                                                        <span class="text-blue-600 dark:text-blue-400 text-xs truncate block" title="<?= e($agLandingUrl) ?>"><?= e(mb_strimwidth($agLandingUrl, 0, 50, '...')) ?></span>
                                                        <?php else: ?>
                                                        <span class="text-slate-400 text-xs">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- ==================== LEVEL 3: Ads + Keywords ==================== -->
                                                <tr x-show="expandedAdGroups['<?= $agKey ?>']" x-collapse x-cloak>
                                                    <td colspan="8" class="p-0">
                                                        <div class="border-l-2 border-blue-600 ml-6 p-4 bg-slate-50/80 dark:bg-slate-900/30 space-y-4">

                                                            <?php
                                                            // Get ads for this ad group
                                                            $agAds = $ag['ads'] ?? [];
                                                            // AI data for this ad group
                                                            $aiAg = $aiAdGroups[$agIdx] ?? $aiAdGroups[$ag['ad_group_name'] ?? ''] ?? [];
                                                            $aiAdsAnalysis = $aiAg['ads_analysis'] ?? [];
                                                            $aiLanding = $aiAg['landing_analysis'] ?? $aiCampaign['landing_analysis'] ?? [];
                                                            $aiAgComment = $aiAg['comment'] ?? $aiAg['analysis'] ?? '';

                                                            // Find lowest CTR for highlighting
                                                            $lowestCtr = PHP_FLOAT_MAX;
                                                            foreach ($agAds as $ad) {
                                                                $adCtr = (float)($ad['ctr'] ?? 0);
                                                                if ($adCtr < $lowestCtr && $adCtr > 0) {
                                                                    $lowestCtr = $adCtr;
                                                                }
                                                            }
                                                            if ($lowestCtr === PHP_FLOAT_MAX) $lowestCtr = 0;
                                                            ?>

                                                            <!-- Ads Table -->
                                                            <?php if (!empty($agAds)): ?>
                                                            <div>
                                                                <h5 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Annunci (<?= count($agAds) ?>)</h5>
                                                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                                                    <div class="overflow-x-auto">
                                                                        <table class="w-full text-sm">
                                                                            <thead>
                                                                                <tr class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                                                                                    <th class="px-4 py-3 text-left w-8">#</th>
                                                                                    <th class="px-4 py-3 text-left">H1</th>
                                                                                    <th class="px-4 py-3 text-left">H2</th>
                                                                                    <th class="px-4 py-3 text-left">H3</th>
                                                                                    <th class="px-4 py-3 text-left">D1</th>
                                                                                    <th class="px-4 py-3 text-right">CTR</th>
                                                                                    <th class="px-4 py-3 text-right">Click</th>
                                                                                    <th class="px-4 py-3 text-center">Perf.</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                                                <?php foreach ($agAds as $adIdx => $ad): ?>
                                                                                <?php
                                                                                    $adCtr = (float)($ad['ctr'] ?? 0);
                                                                                    $adClicks = (int)($ad['clicks'] ?? 0);
                                                                                    $isLowestCtr = $adCtr > 0 && $adCtr <= $lowestCtr && count($agAds) > 1;
                                                                                    $h1 = $ad['headline1'] ?? '-';
                                                                                    $h2 = $ad['headline2'] ?? '-';
                                                                                    $h3 = $ad['headline3'] ?? '-';
                                                                                    $d1 = $ad['description1'] ?? '-';

                                                                                    // AI performance label for this ad
                                                                                    $aiAdData = $aiAdsAnalysis[$adIdx] ?? [];
                                                                                    $adPerf = strtoupper($aiAdData['performance'] ?? $aiAdData['rating'] ?? '');
                                                                                    $adPerfClass = $assetPerfConfig[$adPerf] ?? '';
                                                                                ?>
                                                                                <tr class="<?= $isLowestCtr ? 'bg-red-50 dark:bg-red-900/10' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50' ?> transition-colors">
                                                                                    <td class="px-4 py-3 text-slate-400 text-xs"><?= $adIdx + 1 ?></td>
                                                                                    <td class="px-4 py-3 text-slate-900 dark:text-white font-medium whitespace-nowrap" title="<?= e($h1) ?>">
                                                                                        <?= e(mb_strimwidth($h1, 0, 30, '...')) ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap" title="<?= e($h2) ?>">
                                                                                        <?= e(mb_strimwidth($h2, 0, 30, '...')) ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap" title="<?= e($h3) ?>">
                                                                                        <?= e(mb_strimwidth($h3, 0, 30, '...')) ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 max-w-xs" title="<?= e($d1) ?>">
                                                                                        <div class="truncate"><?= e(mb_strimwidth($d1, 0, 40, '...')) ?></div>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right whitespace-nowrap <?= $isLowestCtr ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-700 dark:text-slate-300' ?>">
                                                                                        <?= formatNum($adCtr, 2) ?>%
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($adClicks) ?></td>
                                                                                    <td class="px-4 py-3 text-center">
                                                                                        <?php if (!empty($adPerf) && !empty($adPerfClass)): ?>
                                                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $adPerfClass ?>">
                                                                                            <?= e($adPerf) ?>
                                                                                        </span>
                                                                                        <?php else: ?>
                                                                                        <span class="text-slate-400 text-xs">-</span>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>

                                                                <?php // AI per-ad assessment text ?>
                                                                <?php if (!empty($aiAgComment)): ?>
                                                                <div class="mt-2 p-3 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Valutazione annunci</p>
                                                                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($aiAgComment) ?></p>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php endif; ?>

                                                            <!-- Landing Analysis Box -->
                                                            <?php if (!empty($aiLanding)): ?>
                                                            <div>
                                                                <h5 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Analisi Landing Page</h5>
                                                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                                                    <?php
                                                                    $landingUrl = $aiLanding['url'] ?? $agLandingUrl ?? '';
                                                                    $landingCoherence = (float)($aiLanding['coherence_score'] ?? $aiLanding['score'] ?? 0);
                                                                    $landingAnalysis = $aiLanding['analysis'] ?? $aiLanding['comment'] ?? '';
                                                                    $landingSuggestions = $aiLanding['suggestions'] ?? [];
                                                                    ?>
                                                                    <?php if (!empty($landingUrl)): ?>
                                                                    <div class="flex items-center gap-2 mb-3">
                                                                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                                        </svg>
                                                                        <a href="<?= e($landingUrl) ?>" target="_blank" rel="noopener" class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate"><?= e($landingUrl) ?></a>
                                                                        <?php if ($landingCoherence > 0): ?>
                                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium flex-shrink-0 <?= $landingCoherence >= 7 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : ($landingCoherence >= 5 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300') ?>">
                                                                            Coerenza: <?= formatNum($landingCoherence, 1) ?>/10
                                                                        </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php endif; ?>

                                                                    <?php if (!empty($landingAnalysis)): ?>
                                                                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-2"><?= e($landingAnalysis) ?></p>
                                                                    <?php endif; ?>

                                                                    <?php if (!empty($landingSuggestions)): ?>
                                                                    <div class="mt-2">
                                                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Suggerimenti</p>
                                                                        <ul class="space-y-1">
                                                                            <?php foreach ($landingSuggestions as $suggestion): ?>
                                                                            <li class="flex items-start gap-1.5 text-sm text-slate-600 dark:text-slate-400">
                                                                                <svg class="w-3.5 h-3.5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                                                </svg>
                                                                                <?= e(is_string($suggestion) ? $suggestion : ($suggestion['text'] ?? '')) ?>
                                                                            </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>

                                                            <!-- Keywords Table (max 10) -->
                                                            <?php
                                                            $agKeywords = $ag['keywords'] ?? [];
                                                            $agKeywords = array_slice($agKeywords, 0, 10);
                                                            ?>
                                                            <?php if (!empty($agKeywords)): ?>
                                                            <div>
                                                                <h5 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Top Keyword (<?= count($agKeywords) ?>)</h5>
                                                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                                                    <div class="overflow-x-auto">
                                                                        <table class="w-full text-sm">
                                                                            <thead>
                                                                                <tr class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                                                                                    <th class="px-4 py-3 text-left">Keyword</th>
                                                                                    <th class="px-4 py-3 text-center">Tipo</th>
                                                                                    <th class="px-4 py-3 text-right">Click</th>
                                                                                    <th class="px-4 py-3 text-right">CTR</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                                                <?php foreach ($agKeywords as $kw): ?>
                                                                                <?php
                                                                                    $kwText = $kw['keyword_text'] ?? $kw['text'] ?? '-';
                                                                                    $kwMatch = strtoupper($kw['match_type'] ?? 'BROAD');
                                                                                    $kwMatchConf = $matchTypeConfig[$kwMatch] ?? $matchTypeConfig['BROAD'];
                                                                                    $kwClicks = (int)($kw['clicks'] ?? 0);
                                                                                    $kwCtr = (float)($kw['ctr'] ?? 0);
                                                                                    $kwConv = (float)($kw['conversions'] ?? 0);
                                                                                    $kwCost = (float)($kw['cost'] ?? 0);
                                                                                    $kwHighCostNoConv = $kwConv == 0 && $kwCost > 0 && $kwCost > ($cCost * 0.1);
                                                                                ?>
                                                                                <tr class="<?= $kwHighCostNoConv ? 'bg-red-50 dark:bg-red-900/10' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50' ?> transition-colors">
                                                                                    <td class="px-4 py-3">
                                                                                        <span class="text-slate-900 dark:text-white font-medium <?= $kwHighCostNoConv ? 'text-red-700 dark:text-red-300' : '' ?>"><?= e($kwText) ?></span>
                                                                                        <?php if ($kwHighCostNoConv): ?>
                                                                                        <span class="ml-1 text-xs text-red-500" title="Costo elevato senza conversioni">
                                                                                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                                            </svg>
                                                                                        </span>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-center">
                                                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $kwMatchConf['bg'] ?>">
                                                                                            <?= $kwMatchConf['label'] ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($kwClicks) ?></td>
                                                                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= formatNum($kwCtr, 2) ?>%</td>
                                                                                </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>

                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>