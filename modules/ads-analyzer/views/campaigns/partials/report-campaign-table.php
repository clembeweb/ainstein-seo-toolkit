<?php
/**
 * Report Campaign Table — Router per tipo campagna
 *
 * Questo file fa da router e delega a sub-partial specifici per tipo.
 * Viene incluso dentro ogni accordion di campagna in evaluation-v2.php.
 *
 * Variables from parent (evaluation-v2.php accordion loop):
 *   $camp, $syncCamp, $aiCamp, $isPmax, $isSearch, $isShopping, $cIdx
 *   $assetGroups, $assetsByAg, $assetPerfSummary, $lowAssetsByAg
 *   $productDataByCampaign, $syncedExtensions
 *   $generateUrl, $canEdit, $evaluation, $campaignTypeConfig, $viewCampaigns
 */

// Badge & helper configs (shared across sub-partials via parent scope)
if (!function_exists('roasColorClass')) {
    function roasColorClass(float $roas): string {
        if ($roas >= 4) return 'text-emerald-600 dark:text-emerald-400';
        if ($roas >= 2) return 'text-amber-600 dark:text-amber-400';
        return 'text-red-600 dark:text-red-400';
    }
}
if (!function_exists('aiScoreDotClass')) {
    function aiScoreDotClass(float $score): string {
        if ($score >= 7) return 'bg-emerald-500';
        if ($score >= 5) return 'bg-amber-500';
        return 'bg-red-500';
    }
}
if (!function_exists('formatNum')) {
    function formatNum(float $val, int $decimals = 0): string {
        return number_format($val, $decimals, ',', '.');
    }
}

$matchTypeConfig = [
    'BROAD' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Broad'],
    'PHRASE' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Phrase'],
    'EXACT' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'Exact'],
];
$adStrengthConfig = [
    'EXCELLENT' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'GOOD' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'AVERAGE' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'POOR' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];
$assetPerfConfig = [
    'BEST' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'GOOD' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'LOW' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    'LEARNING' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'UNSPECIFIED' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
];

$metricsComment = $aiCamp['metrics_comment'] ?? '';
?>

<div class="p-4 space-y-4">
    <!-- AI Comment sulle metriche della campagna -->
    <?php if ($metricsComment): ?>
    <div class="flex gap-2.5 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
        <svg class="w-4 h-4 text-rose-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/>
        </svg>
        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($metricsComment) ?></p>
    </div>
    <?php endif; ?>

    <!-- Contenuto specifico per tipo campagna -->
    <?php if ($isPmax): ?>
        <?php include __DIR__ . '/report-pmax-section.php'; ?>
    <?php elseif ($isShopping): ?>
        <?php include __DIR__ . '/report-shopping-section.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/report-search-section.php'; ?>
    <?php endif; ?>

    <!-- Estensioni (comune a tutti i tipi) -->
    <?php include __DIR__ . '/report-extensions-section.php'; ?>
</div>
