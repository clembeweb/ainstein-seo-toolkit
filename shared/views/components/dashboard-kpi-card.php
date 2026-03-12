<?php
/**
 * Dashboard KPI Card Component
 *
 * Params:
 * @param string $label       - Metric label (es. "Keyword monitorate")
 * @param mixed  $value       - Metric value (number or string)
 * @param string $icon        - SVG path(s) for Heroicons
 * @param string $color       - Color name: blue|emerald|amber|purple|rose|cyan|orange
 * @param string $url         - (optional) Makes card a clickable link
 * @param string $suffix      - (optional) Suffix after value (es. "%")
 * @param string $subtitle    - (optional) Small text below value
 * @param float  $delta       - (optional) Delta percentage (es. 12.5 or -3.2)
 * @param bool   $invertDelta - (optional) Invert delta color (lower is better, es. cost)
 * @param string $periodLabel - (optional) Period context (es. "vs 7gg fa", "vs analisi precedente")
 */

$color = $color ?? 'blue';
$url = $url ?? null;
$suffix = $suffix ?? '';
$subtitle = $subtitle ?? null;
$value = $value ?? 0;
$delta = $delta ?? null;
$invertDelta = $invertDelta ?? false;
$periodLabel = $periodLabel ?? null;

$colorClasses = [
    'blue'    => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',       'text' => 'text-blue-600 dark:text-blue-400',       'hover' => 'hover:border-blue-300 dark:hover:border-blue-700'],
    'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400', 'hover' => 'hover:border-emerald-300 dark:hover:border-emerald-700'],
    'amber'   => ['bg' => 'bg-amber-100 dark:bg-amber-900/30',     'text' => 'text-amber-600 dark:text-amber-400',     'hover' => 'hover:border-amber-300 dark:hover:border-amber-700'],
    'purple'  => ['bg' => 'bg-purple-100 dark:bg-purple-900/30',   'text' => 'text-purple-600 dark:text-purple-400',   'hover' => 'hover:border-purple-300 dark:hover:border-purple-700'],
    'rose'    => ['bg' => 'bg-rose-100 dark:bg-rose-900/30',       'text' => 'text-rose-600 dark:text-rose-400',       'hover' => 'hover:border-rose-300 dark:hover:border-rose-700'],
    'cyan'    => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30',       'text' => 'text-cyan-600 dark:text-cyan-400',       'hover' => 'hover:border-cyan-300 dark:hover:border-cyan-700'],
    'orange'  => ['bg' => 'bg-orange-100 dark:bg-orange-900/30',   'text' => 'text-orange-600 dark:text-orange-400',   'hover' => 'hover:border-orange-300 dark:hover:border-orange-700'],
];

$c = $colorClasses[$color] ?? $colorClasses['blue'];
$tag = $url ? 'a' : 'div';
$hrefAttr = $url ? ' href="' . htmlspecialchars($url) . '"' : '';
$hoverClass = $url ? $c['hover'] . ' hover:shadow-md' : '';

// Format numeric values
$displayValue = is_numeric($value) ? number_format((float)$value, (floor($value) == $value ? 0 : 1)) : htmlspecialchars($value);
?>
<<?= $tag ?><?= $hrefAttr ?> class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 <?= $hoverClass ?> transition-all block">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl <?= $c['bg'] ?> flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 <?= $c['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?= $icon ?>
            </svg>
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $displayValue ?><?= htmlspecialchars($suffix) ?></p>
                <?php if ($delta !== null && abs($delta) >= 0.5): ?>
                <?php
                    $deltaPositive = $delta > 0;
                    $deltaColorPositive = $invertDelta ? !$deltaPositive : $deltaPositive;
                    $deltaColorClass = $deltaColorPositive
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-red-600 dark:text-red-400';
                    $deltaArrow = $deltaPositive
                        ? '<svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                        : '<svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                ?>
                <span class="text-xs font-medium <?= $deltaColorClass ?> flex items-center gap-0.5">
                    <?= $deltaArrow ?>
                    <?= $delta >= 0 ? '+' : '' ?><?= number_format($delta, 1) ?>%
                </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($label) ?></p>
            <?php if ($delta !== null && $periodLabel): ?>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($periodLabel) ?></p>
            <?php elseif ($subtitle): ?>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </div>
</<?= $tag ?>>
