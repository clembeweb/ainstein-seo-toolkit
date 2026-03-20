<?php
/**
 * Report PMax Section — Asset Groups con inventario, performance, AI analysis
 *
 * Variables from parent scope (report-campaign-table.php router):
 *   $camp, $syncCamp, $aiCamp, $cIdx, $canEdit
 *   $assetGroups, $assetsByAg, $assetPerfSummary, $lowAssetsByAg
 *   $adStrengthConfig, $assetPerfConfig (from router)
 *   Helper functions: roasColorClass(), aiScoreDotClass(), formatNum()
 */

// Filter asset groups for this campaign
$campAssetGroups = array_filter($assetGroups ?? [], fn($ag) =>
    ($ag['campaign_id_google'] ?? '') === ($syncCamp['campaign_id_google'] ?? '')
);
// Fallback: use asset_groups from camp data if no filtered results
if (empty($campAssetGroups)) {
    $campAssetGroups = $camp['asset_groups'] ?? [];
}

$aiAssetGroupAnalysis = $aiCamp['asset_group_analysis'] ?? [];

// Recommended asset counts per type
$recommendedAssets = [
    'HEADLINE' => ['min' => 3, 'max' => 15, 'label' => 'Headline'],
    'LONG_HEADLINE' => ['min' => 1, 'max' => 5, 'label' => 'Long Headline'],
    'DESCRIPTION' => ['min' => 2, 'max' => 5, 'label' => 'Descrizione'],
    'BUSINESS_NAME' => ['min' => 1, 'max' => 1, 'label' => 'Nome Attivita'],
    'MARKETING_IMAGE' => ['min' => 1, 'max' => 20, 'label' => 'Immagine'],
    'SQUARE_MARKETING_IMAGE' => ['min' => 1, 'max' => 20, 'label' => 'Imm. Quadrata'],
    'LOGO' => ['min' => 1, 'max' => 5, 'label' => 'Logo'],
    'YOUTUBE_VIDEO' => ['min' => 0, 'max' => 5, 'label' => 'Video YouTube'],
];

$adStrengthExplanation = [
    'EXCELLENT' => 'Massime performance — nessun intervento necessario',
    'GOOD' => 'Buone performance — margine di miglioramento con piu asset',
    'AVERAGE' => 'Performance nella media — aggiungere asset per migliorare',
    'POOR' => 'Performance scarse — intervento urgente su asset e varieta',
    'UNSPECIFIED' => '',
];
?>

<?php if (empty($campAssetGroups)): ?>
<div class="text-sm text-slate-400 dark:text-slate-500 italic p-3 bg-slate-800/30 rounded-xl border border-slate-700/30">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>Nessun asset group trovato per questa campagna PMax.</span>
    </div>
</div>
<?php else: ?>

<div class="space-y-2">
<?php foreach ($campAssetGroups as $agIdx => $ag): ?>
<?php
    $agIdGoogle = $ag['asset_group_id_google'] ?? '';
    $agName = $ag['asset_group_name'] ?? $ag['ad_group_name'] ?? 'Senza nome';

    // Assets from controller-prepared data, fallback to inline
    $agAssets = $assetsByAg[$agIdGoogle] ?? [];
    if (empty($agAssets)) $agAssets = $ag['assets'] ?? [];

    // Performance summary from controller
    $perfSummary = $assetPerfSummary[$agIdGoogle] ?? [];
    $lowAssets = $lowAssetsByAg[$agIdGoogle] ?? [];

    // Ad strength
    $agStrength = strtoupper($ag['ad_strength'] ?? 'UNSPECIFIED');
    $agStrengthClass = $adStrengthConfig[$agStrength] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';

    // Search themes and audience signals
    $searchThemes = $ag['search_themes'] ?? [];
    if (is_string($searchThemes)) $searchThemes = json_decode($searchThemes, true) ?? [];
    $audienceSignals = $ag['audience_signals'] ?? [];
    if (is_string($audienceSignals)) $audienceSignals = json_decode($audienceSignals, true) ?? [];

    // Metrics
    $agClicks = (int)($ag['clicks'] ?? 0);
    $agCtr = (float)($ag['ctr'] ?? 0);
    $agCost = (float)($ag['cost'] ?? 0);
    $agConv = (float)($ag['conversions'] ?? 0);
    $agConvValue = (float)($ag['conversion_value'] ?? 0);
    $agRoas = $agCost > 0 ? $agConvValue / $agCost : 0;

    // AI analysis for this asset group
    $aiAg = null;
    foreach ($aiAssetGroupAnalysis as $aag) {
        if (($aag['asset_group_name'] ?? '') === $agName) { $aiAg = $aag; break; }
    }
    $aiAg = $aiAg ?? [];
    $aiAgComment = $aiAg['comment'] ?? $aiAg['analysis'] ?? '';

    // Group assets by field_type with performance breakdown
    $assetsByType = [];
    $inlineLowAssets = [];
    foreach ($agAssets as $asset) {
        $ft = $asset['field_type'] ?? 'UNKNOWN';
        $pl = strtoupper($asset['performance_label'] ?? 'UNSPECIFIED');
        $assetsByType[$ft][] = $asset;
        if ($pl === 'LOW') $inlineLowAssets[] = $asset;
    }
    // Use controller-prepared low assets if available, otherwise inline
    if (empty($lowAssets)) $lowAssets = $inlineLowAssets;

    $inventoryByType = [];
    foreach ($assetsByType as $ft => $ftAssets) {
        $counts = ['BEST' => 0, 'GOOD' => 0, 'LOW' => 0, 'LEARNING' => 0, 'UNSPECIFIED' => 0];
        foreach ($ftAssets as $a) {
            $pl = strtoupper($a['performance_label'] ?? 'UNSPECIFIED');
            $counts[$pl] = ($counts[$pl] ?? 0) + 1;
        }
        $inventoryByType[$ft] = ['total' => count($ftAssets), 'breakdown' => $counts];
    }

    // Missing assets
    $missingTypes = [];
    foreach ($recommendedAssets as $rType => $rConf) {
        $have = $inventoryByType[$rType]['total'] ?? 0;
        if ($have < $rConf['min']) {
            $missingTypes[$rType] = ['label' => $rConf['label'], 'have' => $have, 'min' => $rConf['min'], 'max' => $rConf['max']];
        }
    }
?>

<!-- Sub-accordion: asset group -->
<?php $agKey = "ag_{$cIdx}_{$agIdx}"; ?>
<div class="bg-white dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-600/30 overflow-hidden"
     <?php if ($agIdx === 0): ?>x-init="expandedAdGroups['<?= $agKey ?>'] = true"<?php endif; ?>>
    <!-- Header -->
    <div @click="expandedAdGroups['<?= $agKey ?>'] = !expandedAdGroups['<?= $agKey ?>']" class="px-4 py-3 flex items-center gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
        <!-- Ad Strength badge -->
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $agStrengthClass ?>">
            <?= e($agStrength) ?>
        </span>

        <!-- Asset group name -->
        <span class="text-sm font-medium text-slate-900 dark:text-white flex-1 truncate"><?= e($agName) ?></span>

        <!-- Metrics -->
        <div class="hidden sm:flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span><?= formatNum($agClicks) ?> click</span>
            <span><?= formatNum($agCost, 2) ?> &euro;</span>
            <?php if ($agConv > 0): ?>
            <span class="font-medium <?= roasColorClass($agRoas) ?>"><?= formatNum($agRoas, 2) ?>x</span>
            <?php endif; ?>
        </div>

        <!-- Chevron -->
        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="expandedAdGroups['<?= $agKey ?>'] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </div>

    <!-- Body -->
    <div x-show="expandedAdGroups['<?= $agKey ?>']" x-transition x-cloak class="border-t border-slate-200 dark:border-slate-600/30 p-4 space-y-3">

        <!-- Ad Strength explanation -->
        <?php if (!empty($adStrengthExplanation[$agStrength])): ?>
        <p class="text-xs text-slate-500 dark:text-slate-400 italic"><?= $adStrengthExplanation[$agStrength] ?></p>
        <?php endif; ?>

        <!-- Grid 2 col: Inventario Asset + Analisi AI -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- LEFT: Asset Inventory -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-3 py-2 bg-purple-50 dark:bg-purple-900/20 border-b border-purple-100 dark:border-purple-900/30 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6z"/>
                    </svg>
                    <span class="text-xs font-semibold text-purple-800 dark:text-purple-300">Inventario Asset</span>
                </div>
                <div class="p-3 space-y-2">
                    <?php foreach ($recommendedAssets as $rType => $rConf): ?>
                    <?php
                    $inv = $inventoryByType[$rType] ?? null;
                    $have = $inv ? $inv['total'] : 0;
                    $isBelowMin = $have < $rConf['min'];
                    $breakdown = $inv['breakdown'] ?? [];
                    ?>
                    <div class="flex items-center gap-2">
                        <span class="text-xs <?= $isBelowMin ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-700 dark:text-slate-300' ?> w-24 truncate"><?= $rConf['label'] ?></span>
                        <span class="text-[10px] <?= $isBelowMin ? 'text-red-500 font-bold' : 'text-slate-500' ?> w-10 text-right"><?= $have ?>/<?= $rConf['max'] ?></span>
                        <!-- Performance breakdown mini-bars -->
                        <?php if ($have > 0): ?>
                        <div class="flex items-center gap-1 flex-1">
                            <?php foreach (['BEST' => 'bg-emerald-500', 'GOOD' => 'bg-blue-500', 'LOW' => 'bg-red-500', 'LEARNING' => 'bg-amber-400'] as $pLabel => $pColor): ?>
                            <?php if (($breakdown[$pLabel] ?? 0) > 0): ?>
                            <span class="inline-flex items-center gap-0.5 text-[9px] text-slate-500">
                                <span class="w-2 h-2 rounded-sm <?= $pColor ?>"></span>
                                <?= $breakdown[$pLabel] ?> <?= $pLabel ?>
                            </span>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif ($rConf['min'] > 0): ?>
                        <span class="text-[10px] text-red-500 font-medium">Mancante</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT: AI Analysis + Search Themes + Audience -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-3 py-2 bg-purple-50 dark:bg-purple-900/20 border-b border-purple-100 dark:border-purple-900/30 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="text-xs font-semibold text-purple-800 dark:text-purple-300">Analisi AI</span>
                </div>
                <div class="p-3 space-y-3">
                    <?php if (!empty($aiAgComment)): ?>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($aiAgComment) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($searchThemes)): ?>
                    <div>
                        <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Themes</p>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach (array_slice($searchThemes, 0, 10) as $theme): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300"><?= e(is_string($theme) ? $theme : ($theme['text'] ?? '')) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($audienceSignals)): ?>
                    <div>
                        <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Audience Signals</p>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            <?php if (is_array($audienceSignals)): ?>
                            <?php foreach (array_slice($audienceSignals, 0, 5) as $key => $val): ?>
                            <div class="truncate"><?= e(is_string($key) ? $key . ': ' : '') ?><?= e(is_string($val) ? $val : (is_array($val) ? implode(', ', array_slice($val, 0, 3)) : json_encode($val))) ?></div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($aiAgComment) && empty($searchThemes) && empty($audienceSignals)): ?>
                    <p class="text-xs text-slate-400 italic">Nessuna analisi AI disponibile per questo asset group</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- LOW Assets (da sostituire) -->
        <?php if (!empty($lowAssets)): ?>
        <div class="rounded-lg border border-red-200 dark:border-red-800/30 bg-red-50/50 dark:bg-red-900/10 p-3">
            <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Asset LOW — da sostituire (<?= count($lowAssets) ?>)
            </p>
            <?php foreach ($lowAssets as $lIdx => $lowAsset): ?>
            <?php $lowOptKey = "popt_{$cIdx}_{$agIdx}_low_{$lIdx}"; ?>
            <div class="flex items-start justify-between gap-2 <?= $lIdx > 0 ? 'mt-2 pt-2 border-t border-red-100 dark:border-red-900/20' : '' ?>">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-medium text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 rounded"><?= e($lowAsset['field_type'] ?? '') ?></span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 truncate"><?= e(mb_strimwidth($lowAsset['text_content'] ?? $lowAsset['url_content'] ?? '-', 0, 60, '...')) ?></span>
                    </div>
                </div>
                <?php if ($canEdit): ?>
                <button @click="generateFix('replace_asset', <?= e(json_encode([
                    'campaign_name' => $aiCamp['campaign_name'] ?? $camp['campaign_name'] ?? '',
                    'asset_group_name' => $agName,
                    'asset_group_id_google' => $agIdGoogle,
                    'target_asset_type' => $lowAsset['field_type'] ?? '',
                    'target_asset_text' => $lowAsset['text_content'] ?? $lowAsset['url_content'] ?? '',
                ])) ?>, '<?= $lowOptKey ?>')"
                        :class="(generators['<?= $lowOptKey ?>'] || {}).loading ? 'opacity-50 cursor-not-allowed' : ''"
                        class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-1 text-[10px] font-medium rounded bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50 transition-colors disabled:opacity-50">
                    <template x-if="generators['<?= $lowOptKey ?>']?.loading">
                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </template>
                    <span x-text="generators['<?= $lowOptKey ?>']?.loading ? '...' : 'Genera Sostituzione'"></span>
                </button>
                <?php endif; ?>
            </div>
            <!-- Inline result -->
            <template x-if="generators['<?= $lowOptKey ?>']?.result">
                <div class="mt-2 p-2 bg-white dark:bg-slate-800 rounded border border-emerald-200 dark:border-emerald-800/30 text-xs">
                    <div class="whitespace-pre-wrap text-emerald-700 dark:text-emerald-300 leading-relaxed" x-text="generators['<?= $lowOptKey ?>']?.result"></div>
                    <p class="text-[10px] text-slate-400 mt-1">Sostituzione generata per l'asset con performance LOW</p>
                    <div class="flex gap-2 mt-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-700/50">
                        <button @click="copyResult('<?= $lowOptKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700"><span x-text="generators['<?= $lowOptKey ?>']?.copied ? 'Copiato!' : 'Copia'"></span></button>
                        <button @click="exportCsv('<?= $lowOptKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700">CSV</button>
                    </div>
                </div>
            </template>
            <template x-if="generators['<?= $lowOptKey ?>']?.error">
                <p class="text-[10px] text-red-500 mt-1" x-text="generators['<?= $lowOptKey ?>']?.error"></p>
            </template>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Missing Assets -->
        <?php if (!empty($missingTypes) && $canEdit): ?>
        <div class="rounded-lg border border-amber-200 dark:border-amber-800/30 bg-amber-50/50 dark:bg-amber-900/10 p-3">
            <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-2">Asset Mancanti</p>
            <div class="flex flex-wrap gap-2 mb-2">
                <?php foreach ($missingTypes as $mType => $mConf): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    <?= e($mConf['label']) ?>: <?= $mConf['have'] ?>/<?= $mConf['min'] ?> min
                </span>
                <?php endforeach; ?>
            </div>
            <?php $addOptKey = "popt_{$cIdx}_{$agIdx}_add"; ?>
            <button @click="generateFix('add_asset', <?= e(json_encode([
                'campaign_name' => $aiCamp['campaign_name'] ?? $camp['campaign_name'] ?? '',
                'asset_group_name' => $agName,
                'asset_group_id_google' => $agIdGoogle,
                'missing_types' => array_keys($missingTypes),
            ])) ?>, '<?= $addOptKey ?>')"
                    :class="(generators['<?= $addOptKey ?>'] || {}).loading ? 'opacity-50 cursor-not-allowed' : ''"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50 transition-colors disabled:opacity-50">
                <template x-if="generators['<?= $addOptKey ?>']?.loading">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <span x-text="generators['<?= $addOptKey ?>']?.loading ? 'Generazione...' : 'Genera Asset Mancanti (1 credito)'"></span>
            </button>
            <p class="text-[10px] text-slate-400 mt-1">Genera headline, descrizioni e altri asset testuali mancanti per completare l'asset group</p>
            <template x-if="generators['<?= $addOptKey ?>']?.result">
                <div class="mt-2 p-2 bg-white dark:bg-slate-800 rounded border border-emerald-200 dark:border-emerald-800/30 text-xs">
                    <div class="whitespace-pre-wrap text-emerald-700 dark:text-emerald-300 leading-relaxed" x-text="generators['<?= $addOptKey ?>']?.result"></div>
                    <div class="flex gap-2 mt-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-700/50">
                        <button @click="copyResult('<?= $addOptKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700"><span x-text="generators['<?= $addOptKey ?>']?.copied ? 'Copiato!' : 'Copia'"></span></button>
                    </div>
                </div>
            </template>
            <template x-if="generators['<?= $addOptKey ?>']?.error">
                <p class="text-[10px] text-red-500 mt-1" x-text="generators['<?= $addOptKey ?>']?.error"></p>
            </template>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php endforeach; ?>
</div>

<?php endif; ?>
