<?php
/**
 * Report Shopping Section — Product performance, brand ROAS, waste products
 *
 * Variables from parent scope (report-campaign-table.php router):
 *   $camp, $syncCamp, $aiCamp, $cIdx, $canEdit
 *   $productDataByCampaign
 *   Helper functions: roasColorClass(), formatNum()
 */

$campIdGoogle = $syncCamp['campaign_id_google'] ?? '';
$campProductData = $productDataByCampaign[$campIdGoogle] ?? null;
$aiProductAnalysis = $aiCamp['product_analysis'] ?? [];
?>

<?php if ($campProductData): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <!-- LEFT: Brand ROAS -->
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-900/30 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
            </svg>
            <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">Performance Brand</span>
        </div>
        <div class="p-3 space-y-2">
            <?php $brands = $campProductData['brands'] ?? []; ?>
            <?php if (!empty($brands)): ?>
            <?php
            // Find max cost for bar scaling
            $maxBrandCost = 0;
            foreach ($brands as $brand) {
                $bc = (float)($brand['cost'] ?? 0);
                if ($bc > $maxBrandCost) $maxBrandCost = $bc;
            }
            ?>
            <?php foreach ($brands as $brand): ?>
            <?php
                $brandName = $brand['brand'] ?? $brand['name'] ?? 'N/D';
                $brandCost = (float)($brand['cost'] ?? 0);
                $brandConv = (float)($brand['conversions'] ?? 0);
                $brandConvValue = (float)($brand['conversion_value'] ?? 0);
                $brandRoas = $brandCost > 0 ? $brandConvValue / $brandCost : 0;
                $brandClicks = (int)($brand['clicks'] ?? 0);
                $barWidth = $maxBrandCost > 0 ? round(($brandCost / $maxBrandCost) * 100) : 0;
            ?>
            <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-slate-900 dark:text-white truncate max-w-[150px]"><?= e($brandName) ?></span>
                    <span class="font-bold <?= roasColorClass($brandRoas) ?>"><?= $brandCost > 0 ? formatNum($brandRoas, 2) . 'x' : '-' ?></span>
                </div>
                <!-- Bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full <?= $brandRoas >= 4 ? 'bg-emerald-500' : ($brandRoas >= 2 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= $barWidth ?>%"></div>
                </div>
                <div class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-slate-400">
                    <span><?= formatNum($brandCost, 2) ?> &euro; spesa</span>
                    <span><?= $brandClicks ?> click</span>
                    <span><?= formatNum($brandConv) ?> conv.</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-xs text-slate-400 italic">Nessun dato brand disponibile</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: Waste Products -->
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-900/30 flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span class="text-xs font-semibold text-red-800 dark:text-red-300">Prodotti Spreco</span>
        </div>
        <div class="p-3 space-y-2">
            <?php $wasteProducts = $campProductData['waste'] ?? []; ?>
            <?php if (!empty($wasteProducts)): ?>
            <?php foreach (array_slice($wasteProducts, 0, 10) as $product): ?>
            <?php
                $prodTitle = $product['title'] ?? $product['product_title'] ?? 'Prodotto sconosciuto';
                $prodCost = (float)($product['cost'] ?? 0);
                $prodClicks = (int)($product['clicks'] ?? 0);
                $prodConv = (float)($product['conversions'] ?? 0);
            ?>
            <div class="flex items-start justify-between gap-2 text-xs">
                <div class="flex-1 min-w-0">
                    <span class="text-slate-700 dark:text-slate-300 truncate block"><?= e(mb_strimwidth($prodTitle, 0, 50, '...')) ?></span>
                    <span class="text-[10px] text-slate-400"><?= $prodClicks ?> click</span>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="text-red-600 dark:text-red-400 font-medium"><?= formatNum($prodCost, 2) ?> &euro;</span>
                    <div class="text-[10px] text-red-500"><?= formatNum($prodConv) ?> conv.</div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($wasteProducts) > 10): ?>
            <p class="text-[10px] text-slate-400">+<?= count($wasteProducts) - 10 ?> altri prodotti</p>
            <?php endif; ?>
            <?php else: ?>
            <p class="text-xs text-slate-400 italic">Nessun prodotto con spreco significativo</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AI Opportunities -->
<?php $opportunities = $aiProductAnalysis['opportunities'] ?? []; ?>
<?php if (!empty($opportunities)): ?>
<div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
    <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/>
        </svg>
        Opportunita AI
    </h4>
    <ul class="space-y-1.5">
        <?php foreach ($opportunities as $opp): ?>
        <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400">
            <svg class="w-3 h-3 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <?= e(is_string($opp) ? $opp : ($opp['text'] ?? $opp['description'] ?? '')) ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- AI Product Comment -->
<?php $productComment = $aiProductAnalysis['comment'] ?? $aiProductAnalysis['analysis'] ?? ''; ?>
<?php if (!empty($productComment)): ?>
<div class="flex gap-2.5 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
    <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
    </svg>
    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($productComment) ?></p>
</div>
<?php endif; ?>

<?php else: ?>
<div class="text-sm text-slate-400 dark:text-slate-500 italic p-4 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-200 dark:border-slate-700/30">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>Dati prodotto non disponibili per questa campagna. Verifica che l'account abbia un Merchant Center collegato.</span>
    </div>
</div>
<?php endif; ?>
