<?php
/**
 * Dashboard Mode Card Component
 * Card for selecting a module mode/type (like keyword-research mode selection).
 *
 * Params:
 * @param string $title       - Mode title
 * @param string $description - Mode description
 * @param string $icon        - SVG path for Heroicons
 * @param string $gradient    - Gradient classes (es. "from-emerald-500 to-teal-600")
 * @param string $url         - Link URL
 * @param string $ctaText     - (optional) CTA text, default "Vai ai progetti"
 * @param string $cost        - (optional) Cost badge text (es. "10 cr")
 * @param string $costColor   - (optional) Cost badge color: amber|purple|emerald
 * @param string $badge       - (optional) Extra badge text (es. "3 progetti")
 * @param string $dataTour    - (optional) data-tour attribute
 */

$ctaText = $ctaText ?? 'Vai ai progetti';
$cost = $cost ?? null;
$costColor = $costColor ?? 'amber';
$badge = $badge ?? null;
$dataTour = $dataTour ?? null;

$costColors = [
    'amber'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'purple'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
];

$costClass = $costColors[$costColor] ?? $costColors['amber'];
?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow"<?= $dataTour ? ' data-tour="' . htmlspecialchars($dataTour) . '"' : '' ?>>
    <div class="p-5">
        <div class="h-12 w-12 rounded-xl bg-gradient-to-br <?= htmlspecialchars($gradient) ?> flex items-center justify-center shadow-sm mb-4">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?= $icon ?>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($title) ?></h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 line-clamp-2"><?= htmlspecialchars($description) ?></p>
        <div class="mt-3 flex items-center gap-2">
            <?php if ($cost): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $costClass ?>"><?= htmlspecialchars($cost) ?></span>
            <?php endif; ?>
            <?php if ($badge): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300"><?= htmlspecialchars($badge) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="px-5 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
        <a href="<?= htmlspecialchars($url) ?>" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
            <?= htmlspecialchars($ctaText) ?>
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
