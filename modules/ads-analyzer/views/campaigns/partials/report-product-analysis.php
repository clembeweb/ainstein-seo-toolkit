<?php
/**
 * Report: Analisi Prodotti Shopping/PMax
 * Visibile SOLO se $productData non è null (campagne Shopping/PMax con dati prodotto)
 *
 * Variables from parent:
 * - $productData: ['top_products' => [...], 'brands' => [...], 'categories' => [...], 'waste' => [...]]
 * - $aiResponse: contiene product_analysis se generata dall'AI
 */
if (empty($productData)) return;

$brands = $productData['brands'] ?? [];
$waste = $productData['waste'] ?? [];
$topProducts = $productData['top_products'] ?? [];
$categories = $productData['categories'] ?? [];
$aiProductAnalysis = $aiResponse['product_analysis'] ?? null;
$maxBrandCost = !empty($brands) ? max(array_column($brands, 'total_cost')) : 1;

function productRoasClass(float $roas): string {
    if ($roas >= 4) return 'bg-emerald-600';
    if ($roas >= 2) return 'bg-blue-600';
    return 'bg-red-600';
}

function productRoasTextClass(float $roas): string {
    if ($roas >= 4) return 'text-emerald-400';
    if ($roas >= 2) return 'text-amber-400';
    return 'text-red-400';
}
?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 space-y-5"
     x-data="{ showAllProducts: false }">

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Analisi Prodotti
            <span class="text-xs text-slate-400 font-normal">(ultimi 30 giorni)</span>
        </h2>
    </div>

    <!-- Brand Performance Bars -->
    <?php if (!empty($brands)): ?>
    <div class="space-y-3">
        <h3 class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-medium">Performance per Brand</h3>
        <div class="space-y-2">
            <?php foreach (array_slice($brands, 0, 8) as $brand): ?>
            <?php
            $brandRoas = (float)($brand['brand_roas'] ?? 0);
            $barWidth = $maxBrandCost > 0 ? max(5, round((float)$brand['total_cost'] / $maxBrandCost * 100)) : 5;
            ?>
            <div class="flex items-center gap-3">
                <span class="w-20 text-xs text-slate-300 text-right truncate"><?= e($brand['product_brand'] ?? 'N/D') ?></span>
                <div class="flex-1 bg-slate-700/30 rounded-full h-6 overflow-hidden relative">
                    <div class="h-full rounded-full <?= productRoasClass($brandRoas) ?> transition-all" style="width: <?= $barWidth ?>%"></div>
                    <span class="absolute inset-0 flex items-center justify-center text-[10px] text-white font-medium">
                        ROAS <?= number_format($brandRoas, 1, ',', '.') ?>x
                        &middot; &euro;<?= number_format((float)($brand['total_cost'] ?? 0), 0, ',', '.') ?>
                        &middot; <?= number_format((float)($brand['total_conversions'] ?? 0), 0, ',', '.') ?> conv.
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Waste + Opportunities Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Waste Products -->
        <?php if (!empty($waste)): ?>
        <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30 rounded-lg p-4">
            <h3 class="text-xs font-semibold text-red-700 dark:text-red-400 mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Prodotti Spreco
                <span class="text-red-500 font-normal">
                    (&euro;<?= number_format(array_sum(array_column($waste, 'cost')), 0, ',', '.') ?>/mese, 0 conv.)
                </span>
            </h3>
            <ul class="space-y-2">
                <?php foreach (array_slice($waste, 0, 5) as $w): ?>
                <li class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 dark:text-slate-300 truncate max-w-[200px]"><?= e($w['product_title'] ?? $w['product_item_id'] ?? 'N/D') ?></span>
                    <span class="text-red-600 dark:text-red-400 font-medium flex-shrink-0 ml-2">&euro;<?= number_format((float)($w['cost'] ?? 0), 0, ',', '.') ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($waste) > 5): ?>
            <p class="text-[10px] text-slate-500 mt-2">+<?= count($waste) - 5 ?> altri prodotti</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- AI Opportunities (from AI response) -->
        <?php if (!empty($aiProductAnalysis['opportunities'])): ?>
        <div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/30 rounded-lg p-4">
            <h3 class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Opportunita
            </h3>
            <ul class="space-y-2">
                <?php foreach (array_slice($aiProductAnalysis['opportunities'], 0, 5) as $opp): ?>
                <li class="text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-medium"><?= e($opp['title'] ?? $opp['item_id'] ?? '') ?></span>
                    <?php if (!empty($opp['recommendation'])): ?>
                    — <span class="text-emerald-600 dark:text-emerald-400"><?= e($opp['recommendation']) ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php elseif (!empty($categories)): ?>
        <!-- Fallback: Category summary if no AI opportunities -->
        <div class="bg-slate-50 dark:bg-slate-700/20 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
            <h3 class="text-xs font-semibold text-slate-700 dark:text-slate-400 mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Per Categoria
            </h3>
            <ul class="space-y-2">
                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                <li class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 dark:text-slate-300"><?= e($cat['product_category_l1'] ?? 'N/D') ?></span>
                    <span class="<?= productRoasTextClass((float)($cat['cat_roas'] ?? 0)) ?> font-medium">
                        ROAS <?= number_format((float)($cat['cat_roas'] ?? 0), 1, ',', '.') ?>x
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- AI Product Summary -->
    <?php if (!empty($aiProductAnalysis['summary'])): ?>
    <div class="bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800/30 rounded-lg p-4">
        <div class="flex items-start gap-2">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            <p class="text-sm text-purple-700 dark:text-purple-300"><?= e($aiProductAnalysis['summary']) ?></p>
        </div>
    </div>
    <?php endif; ?>

</div>
