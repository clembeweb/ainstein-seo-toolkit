<?php
/**
 * Skeleton Loader Component
 *
 * Varianti: 'card', 'table-row', 'kpi-card', 'text'
 *
 * Usage:
 *   View::partial('components/skeleton-loader', ['variant' => 'kpi-card', 'count' => 4])
 *   View::partial('components/skeleton-loader', ['variant' => 'table-row', 'count' => 5, 'cols' => 6])
 */

$variant = $variant ?? 'card';
$count = $count ?? 3;
$cols = $cols ?? 5;
?>

<?php if ($variant === 'kpi-card'): ?>
<div class="grid grid-cols-2 md:grid-cols-<?= min($count, 5) ?> gap-4 animate-pulse">
    <?php for ($i = 0; $i < $count; $i++): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="h-7 w-16 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
        <div class="h-3 w-20 bg-slate-100 dark:bg-slate-700/50 rounded"></div>
    </div>
    <?php endfor; ?>
</div>

<?php elseif ($variant === 'table-row'): ?>
<div class="animate-pulse divide-y divide-slate-200 dark:divide-slate-700">
    <?php for ($i = 0; $i < $count; $i++): ?>
    <div class="flex items-center gap-4 px-4 py-3">
        <?php for ($j = 0; $j < $cols; $j++): ?>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded flex-1"></div>
        <?php endfor; ?>
    </div>
    <?php endfor; ?>
</div>

<?php elseif ($variant === 'card'): ?>
<div class="space-y-4 animate-pulse">
    <?php for ($i = 0; $i < $count; $i++): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
        <div class="h-5 w-48 bg-slate-200 dark:bg-slate-700 rounded mb-3"></div>
        <div class="h-3 w-full bg-slate-100 dark:bg-slate-700/50 rounded mb-2"></div>
        <div class="h-3 w-3/4 bg-slate-100 dark:bg-slate-700/50 rounded"></div>
    </div>
    <?php endfor; ?>
</div>

<?php elseif ($variant === 'text'): ?>
<div class="animate-pulse space-y-2">
    <?php for ($i = 0; $i < $count; $i++): ?>
    <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded" style="width: <?= rand(60, 100) ?>%"></div>
    <?php endfor; ?>
</div>
<?php endif; ?>
