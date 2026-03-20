<?php
/**
 * Report Search Section — Annunci + Landing + Keywords per Ad Group
 *
 * Variables from parent scope (report-campaign-table.php router):
 *   $camp, $syncCamp, $aiCamp, $cIdx, $canEdit
 *   $matchTypeConfig (from router)
 *   Helper functions: roasColorClass(), aiScoreDotClass(), formatNum()
 */

$syncAdGroups = $camp['ad_groups'] ?? [];
$aiAdGroups = $aiCamp['ad_groups'] ?? [];
?>

<?php if (empty($syncAdGroups)): ?>
<div class="text-sm text-slate-400 dark:text-slate-500 italic p-3 bg-slate-800/30 rounded-xl border border-slate-700/30">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>Nessun ad group trovato per questa campagna.</span>
    </div>
</div>
<?php else: ?>

<div class="space-y-4">
<?php foreach ($syncAdGroups as $agIdx => $ag): ?>
<?php
    $agClicks = (int)($ag['clicks'] ?? 0);
    $agCtr = (float)($ag['ctr'] ?? 0);
    $agCost = (float)($ag['cost'] ?? 0);
    $agConv = (float)($ag['conversions'] ?? 0);
    $agConvValue = (float)($ag['conversion_value'] ?? 0);
    $agRoas = $agCost > 0 ? $agConvValue / $agCost : 0;
    $agCpa = $agConv > 0 ? $agCost / $agConv : 0;
    $agAds = $ag['ads'] ?? [];
    $agKeywords = array_slice($ag['keywords'] ?? [], 0, 5);

    // AI data for this ad group
    $aiAg = null;
    if (isset($aiAdGroups[$agIdx])) {
        $aiAg = $aiAdGroups[$agIdx];
    } else {
        foreach ($aiAdGroups as $aag) {
            if (($aag['ad_group_name'] ?? '') === ($ag['ad_group_name'] ?? '')) { $aiAg = $aag; break; }
        }
    }
    $aiAg = $aiAg ?? [];
    $aiAgComment = $aiAg['comment'] ?? $aiAg['analysis'] ?? '';
    $aiAdsAnalysis = $aiAg['ads_analysis'] ?? [];
    $aiLanding = $aiAg['landing_analysis'] ?? [];
    $landingUrl = $aiLanding['url'] ?? '';
    $landingCoherence = (float)($aiLanding['coherence_score'] ?? $aiLanding['score'] ?? 0);
    $landingAnalysis = $aiLanding['analysis'] ?? $aiLanding['comment'] ?? '';
    $landingSuggestions = $aiLanding['suggestions'] ?? [];
?>

<!-- Ad Group card -->
<div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <!-- Ad Group header -->
    <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/30 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <h4 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($ag['ad_group_name'] ?? 'Senza nome') ?></h4>
        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span><?= formatNum($agClicks) ?> click</span>
            <span><?= formatNum($agCtr, 2) ?>% CTR</span>
            <span><?= formatNum($agCost, 2) ?> &euro;</span>
            <?php if ($agConv > 0): ?>
            <span><?= formatNum($agConv) ?> conv.</span>
            <span class="font-medium <?= roasColorClass($agRoas) ?>"><?= formatNum($agRoas, 2) ?>x</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="p-4 space-y-3">
        <!-- Grid 2 col: Annunci (left) + Landing (right) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- LEFT: Annunci -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-900/30 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <span class="text-xs font-semibold text-blue-800 dark:text-blue-300">Annunci (<?= count($agAds) ?>)</span>
                </div>
                <?php if (!empty($agAds)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/50">
                                <th class="px-3 py-2 text-left w-6">#</th>
                                <th class="px-3 py-2 text-left">H1</th>
                                <th class="px-3 py-2 text-left">H2</th>
                                <th class="px-3 py-2 text-left">H3</th>
                                <th class="px-3 py-2 text-left">D1</th>
                                <th class="px-3 py-2 text-right">CTR</th>
                                <th class="px-3 py-2 text-right">Click</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <?php
                            $lowestCtr = PHP_FLOAT_MAX;
                            foreach ($agAds as $ad) {
                                $c = (float)($ad['ctr'] ?? 0);
                                if ($c > 0 && $c < $lowestCtr) $lowestCtr = $c;
                            }
                            if ($lowestCtr === PHP_FLOAT_MAX) $lowestCtr = 0;
                            ?>
                            <?php foreach ($agAds as $adIdx => $ad): ?>
                            <?php
                                $adCtr = (float)($ad['ctr'] ?? 0);
                                $isLow = $adCtr > 0 && $adCtr <= $lowestCtr && count($agAds) > 1;
                            ?>
                            <tr class="<?= $isLow ? 'bg-red-50 dark:bg-red-900/10' : '' ?>">
                                <td class="px-3 py-2 text-slate-400"><?= $adIdx + 1 ?></td>
                                <td class="px-3 py-2 text-slate-900 dark:text-white font-medium" title="<?= e($ad['headline1'] ?? '') ?>"><?= e(mb_strimwidth($ad['headline1'] ?? '-', 0, 25, '...')) ?></td>
                                <td class="px-3 py-2 text-slate-600 dark:text-slate-300" title="<?= e($ad['headline2'] ?? '') ?>"><?= e(mb_strimwidth($ad['headline2'] ?? '-', 0, 25, '...')) ?></td>
                                <td class="px-3 py-2 text-slate-600 dark:text-slate-300" title="<?= e($ad['headline3'] ?? '') ?>"><?= e(mb_strimwidth($ad['headline3'] ?? '-', 0, 25, '...')) ?></td>
                                <td class="px-3 py-2 text-slate-500 dark:text-slate-400 max-w-[120px]" title="<?= e($ad['description1'] ?? '') ?>"><div class="truncate text-[11px]"><?= e(mb_strimwidth($ad['description1'] ?? '-', 0, 40, '...')) ?></div></td>
                                <td class="px-3 py-2 text-right <?= $isLow ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-600 dark:text-slate-300' ?>"><?= formatNum($adCtr, 2) ?>%</td>
                                <td class="px-3 py-2 text-right text-slate-600 dark:text-slate-300"><?= (int)($ad['clicks'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- AI comment sugli annunci -->
                <?php if (!empty($aiAgComment)): ?>
                <div class="px-3 py-2 border-t border-slate-100 dark:border-slate-700/50 bg-white dark:bg-slate-800">
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed"><span class="font-medium text-blue-700 dark:text-blue-400">AI:</span> <?= e($aiAgComment) ?></p>
                </div>
                <?php endif; ?>

                <!-- Inline: ottimizzazioni annunci (rewrite_ad) -->
                <?php
                $adOpts = array_filter($aiAg['optimizations'] ?? [], fn($o) => in_array($o['type'] ?? '', ['rewrite_ad', 'rewrite_ads']));
                ?>
                <?php if (!empty($adOpts) && $canEdit): ?>
                <?php foreach ($adOpts as $oIdx => $adOpt): ?>
                <?php $optKey = "opt_{$cIdx}_{$agIdx}_{$oIdx}"; ?>
                <div class="px-3 py-2 border-t border-amber-100 dark:border-amber-900/30 bg-amber-50/50 dark:bg-amber-900/10">
                    <div class="flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-amber-800 dark:text-amber-300 mb-1"><?= e($adOpt['reason'] ?? 'Riscrittura annunci suggerita') ?></p>
                            <!-- Genera button -->
                            <div x-show="!generators['<?= $optKey ?>']?.result" class="mt-1">
                                <button @click="generateFix('<?= e($adOpt['type'] ?? 'rewrite_ad') ?>', <?= e(json_encode(['campaign_name' => $aiCamp['campaign_name'] ?? $camp['campaign_name'] ?? '', 'ad_group_name' => $aiAg['ad_group_name'] ?? '', 'target_ad_index' => $adOpt['target_ad_index'] ?? null, 'ad_group_id_google' => $ag['ad_group_id_google'] ?? ''])) ?>, '<?= $optKey ?>')"
                                        :class="(generators['<?= $optKey ?>'] || {}).loading ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50 transition-colors disabled:opacity-50">
                                    <template x-if="generators['<?= $optKey ?>']?.loading">
                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    </template>
                                    <template x-if="!generators['<?= $optKey ?>']?.loading">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                    </template>
                                    <span x-text="generators['<?= $optKey ?>']?.loading ? 'Generazione...' : 'Genera Riscrittura (1 credito)'"></span>
                                </button>
                                <p class="text-[10px] text-slate-400 mt-1">Genera nuovi headline e description per sostituire l'annuncio con CTR piu basso</p>
                            </div>
                            <!-- Before/After preview inline -->
                            <template x-if="generators['<?= $optKey ?>']?.result">
                                <div class="mt-2 p-2 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 text-xs space-y-1">
                                    <div class="text-[10px] font-medium text-slate-500 mb-1">Before / After</div>
                                    <template x-for="(h, i) in (generators['<?= $optKey ?>']?.data?.headlines || [])" :key="'h'+i">
                                        <div class="flex items-baseline gap-1.5">
                                            <span class="text-slate-400 font-mono w-6" x-text="'H'+(i+1)+':'"></span>
                                            <span class="text-emerald-600 dark:text-emerald-400" x-text="h"></span>
                                        </div>
                                    </template>
                                    <template x-for="(d, i) in (generators['<?= $optKey ?>']?.data?.descriptions || [])" :key="'d'+i">
                                        <div class="flex items-baseline gap-1.5">
                                            <span class="text-slate-400 font-mono w-6" x-text="'D'+(i+1)+':'"></span>
                                            <span class="text-emerald-600 dark:text-emerald-400" x-text="d"></span>
                                        </div>
                                    </template>
                                    <div class="flex gap-2 mt-2 pt-2 border-t border-slate-100 dark:border-slate-700/50">
                                        <button @click="copyResult('<?= $optKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                                            <span x-text="generators['<?= $optKey ?>']?.copied ? 'Copiato!' : 'Copia'"></span>
                                        </button>
                                        <button @click="exportCsv('<?= $optKey ?>')" class="text-[10px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">CSV</button>
                                        <button @click="openApplyModal('<?= $optKey ?>')" class="text-[10px] text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium">Applica su Google Ads</button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="generators['<?= $optKey ?>']?.error">
                                <p class="text-xs text-red-500 mt-1" x-text="generators['<?= $optKey ?>']?.error"></p>
                            </template>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Landing Page -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-3 py-2 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-900/30 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">Landing Page</span>
                    <?php if ($landingCoherence > 0): ?>
                    <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $landingCoherence >= 7 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : ($landingCoherence >= 5 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300') ?>">
                        Coerenza <?= formatNum($landingCoherence, 1) ?>/10
                    </span>
                    <?php endif; ?>
                </div>
                <div class="px-3 py-3">
                    <?php if (!empty($landingUrl)): ?>
                    <a href="<?= e($landingUrl) ?>" target="_blank" rel="noopener" class="text-xs text-blue-600 dark:text-blue-400 hover:underline truncate block mb-2"><?= e(mb_strimwidth($landingUrl, 0, 55, '...')) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($landingAnalysis)): ?>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-2"><?= e($landingAnalysis) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($landingSuggestions)): ?>
                    <ul class="space-y-1">
                        <?php foreach ($landingSuggestions as $sug): ?>
                        <li class="flex items-start gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                            <svg class="w-3 h-3 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <?= e(is_string($sug) ? $sug : ($sug['text'] ?? '')) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if (empty($landingAnalysis) && empty($landingUrl)): ?>
                    <p class="text-xs text-slate-400 italic">Nessuna landing page analizzata per questo ad group</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Keywords (compact, max 5) -->
        <?php if (!empty($agKeywords)): ?>
        <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-3 py-2 bg-slate-50 dark:bg-slate-700/30 border-b border-slate-100 dark:border-slate-700/50 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Top Keyword</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach ($agKeywords as $kw): ?>
                        <?php
                            $kwText = $kw['keyword_text'] ?? $kw['text'] ?? '-';
                            $kwMatch = strtoupper($kw['match_type'] ?? 'BROAD');
                            $kwMatchConf = $matchTypeConfig[$kwMatch] ?? $matchTypeConfig['BROAD'];
                            $kwClicks = (int)($kw['clicks'] ?? 0);
                            $kwCtr = (float)($kw['ctr'] ?? 0);
                            $kwCost = (float)($kw['cost'] ?? 0);
                            $kwConv = (float)($kw['conversions'] ?? 0);
                            $kwWaste = $kwConv == 0 && $kwCost > 0 && $kwCost > ($agCost * 0.1);
                        ?>
                        <tr class="<?= $kwWaste ? 'bg-red-50 dark:bg-red-900/10' : '' ?>">
                            <td class="px-3 py-1.5 text-slate-900 dark:text-white <?= $kwWaste ? 'text-red-700 dark:text-red-300' : '' ?>">
                                <?= e($kwText) ?>
                                <?php if ($kwWaste): ?>
                                <svg class="w-3 h-3 inline text-red-500 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-1.5 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium <?= $kwMatchConf['bg'] ?>"><?= $kwMatchConf['label'] ?></span></td>
                            <td class="px-3 py-1.5 text-right text-slate-600 dark:text-slate-300"><?= $kwClicks ?></td>
                            <td class="px-3 py-1.5 text-right text-slate-600 dark:text-slate-300"><?= formatNum($kwCtr, 2) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endforeach; ?>
</div>

<?php endif; ?>
