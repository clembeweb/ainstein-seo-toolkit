<?php
/**
 * Dashboard "Come funziona" Component
 * Shows numbered workflow steps in a horizontal layout.
 *
 * Params:
 * @param array  $steps - Array of ['title' => '...', 'description' => '...']
 * @param string $color - Accent color for step numbers: blue|emerald|amber|purple|rose|cyan|orange
 */

$steps = $steps ?? [];
$color = $color ?? 'blue';

$colorClasses = [
    'blue'    => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    'emerald' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    'amber'   => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    'purple'  => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    'rose'    => 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
    'cyan'    => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
    'orange'  => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
];

$numClass = $colorClasses[$color] ?? $colorClasses['blue'];
$count = count($steps);
?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
    <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-4">Come funziona</h3>
    <div class="grid grid-cols-2 md:grid-cols-<?= min($count, 5) ?> gap-4">
        <?php foreach ($steps as $i => $step): ?>
        <div class="text-center">
            <div class="w-10 h-10 rounded-full <?= $numClass ?> flex items-center justify-center mx-auto mb-2 text-sm font-bold">
                <?= $i + 1 ?>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($step['title']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($step['description']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
