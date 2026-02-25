<?php
/**
 * Dashboard Module Capabilities Block
 *
 * Mostra un blocco "Cosa puoi fare" per un modulo.
 *
 * Parametri:
 * - $block: array con slug, name, tagline, capabilities[], color, iconPath
 */

$slug = $block['slug'];
$name = $block['name'];
$tagline = $block['tagline'];
$capabilities = $block['capabilities'] ?? [];
$colorName = $block['color'] ?? 'slate';

$colorMap = [
    'amber' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-700/50', 'dot' => 'text-amber-400'],
    'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-700/50', 'dot' => 'text-emerald-400'],
    'blue' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-700/50', 'dot' => 'text-blue-400'],
    'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-400', 'border' => 'border-purple-200 dark:border-purple-700/50', 'dot' => 'text-purple-400'],
    'rose' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600 dark:text-rose-400', 'border' => 'border-rose-200 dark:border-rose-700/50', 'dot' => 'text-rose-400'],
    'cyan' => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-cyan-600 dark:text-cyan-400', 'border' => 'border-cyan-200 dark:border-cyan-700/50', 'dot' => 'text-cyan-400'],
    'orange' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-600 dark:text-orange-400', 'border' => 'border-orange-200 dark:border-orange-700/50', 'dot' => 'text-orange-400'],
];

$colors = $colorMap[$colorName] ?? $colorMap['blue'];
$iconPath = $block['iconPath'] ?? 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';
?>

<div class="bg-white dark:bg-slate-800 rounded-xl border <?= $colors['border'] ?> shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
    <!-- Header -->
    <div class="px-5 pt-5 pb-3">
        <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-xl <?= $colors['bg'] ?> flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5 <?= $colors['text'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $iconPath ?>"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($name) ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($tagline) ?></p>
            </div>
        </div>
    </div>

    <!-- Capabilities list -->
    <div class="px-5 pb-3 space-y-2">
        <?php foreach ($capabilities as $cap): ?>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-3.5 h-3.5 <?= $colors['dot'] ?> flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="text-sm text-slate-700 dark:text-slate-300 truncate"><?= htmlspecialchars($cap['text']) ?></span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500 flex-shrink-0 font-medium"><?= htmlspecialchars($cap['cost']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CTA -->
    <div class="border-t border-slate-100 dark:border-slate-700/50 px-5 py-3">
        <a href="<?= url('/projects/create') ?>"
           class="inline-flex items-center gap-1.5 text-sm font-medium <?= $colors['text'] ?> hover:opacity-80 transition-opacity">
            Inizia
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
    </div>
</div>
