<?php
/**
 * Dashboard Hero Banner Component
 * Dark gradient card with title, description, workflow steps, and optional CTA.
 *
 * Params:
 * @param string $title       - Banner title
 * @param string $description - Banner description
 * @param string $color       - Module color: emerald|blue|purple|amber|rose|cyan|orange
 * @param array  $steps       - Array of ['icon' => 'svg path', 'title' => '...', 'subtitle' => '...']
 * @param string $ctaText     - (optional) CTA button text
 * @param string $ctaUrl      - (optional) CTA button URL
 * @param string $storageKey  - (optional) localStorage key for dismiss. If set, banner is dismissible.
 * @param string $badge       - (optional) Badge text above title
 */

$color = $color ?? 'blue';
$steps = $steps ?? [];
$ctaText = $ctaText ?? null;
$ctaUrl = $ctaUrl ?? null;
$storageKey = $storageKey ?? null;
$badge = $badge ?? null;

$colorMap = [
    'amber'   => ['from' => 'from-amber-950',   'badge' => 'bg-amber-500/20 text-amber-300',   'icon' => 'bg-amber-500/20 text-amber-400',   'btn' => 'bg-amber-500 hover:bg-amber-600',   'sub' => 'text-amber-400'],
    'emerald' => ['from' => 'from-emerald-950', 'badge' => 'bg-emerald-500/20 text-emerald-300', 'icon' => 'bg-emerald-500/20 text-emerald-400', 'btn' => 'bg-emerald-500 hover:bg-emerald-600', 'sub' => 'text-emerald-400'],
    'blue'    => ['from' => 'from-blue-950',     'badge' => 'bg-blue-500/20 text-blue-300',     'icon' => 'bg-blue-500/20 text-blue-400',     'btn' => 'bg-blue-500 hover:bg-blue-600',     'sub' => 'text-blue-400'],
    'purple'  => ['from' => 'from-purple-950',   'badge' => 'bg-purple-500/20 text-purple-300', 'icon' => 'bg-purple-500/20 text-purple-400', 'btn' => 'bg-purple-500 hover:bg-purple-600', 'sub' => 'text-purple-400'],
    'rose'    => ['from' => 'from-rose-950',     'badge' => 'bg-rose-500/20 text-rose-300',     'icon' => 'bg-rose-500/20 text-rose-400',     'btn' => 'bg-rose-500 hover:bg-rose-600',     'sub' => 'text-rose-400'],
    'cyan'    => ['from' => 'from-cyan-950',     'badge' => 'bg-cyan-500/20 text-cyan-300',     'icon' => 'bg-cyan-500/20 text-cyan-400',     'btn' => 'bg-cyan-500 hover:bg-cyan-600',     'sub' => 'text-cyan-400'],
    'orange'  => ['from' => 'from-orange-950',   'badge' => 'bg-orange-500/20 text-orange-300', 'icon' => 'bg-orange-500/20 text-orange-400', 'btn' => 'bg-orange-500 hover:bg-orange-600', 'sub' => 'text-orange-400'],
];

$cm = $colorMap[$color] ?? $colorMap['blue'];
$dismissAttr = $storageKey ? " x-data=\"{ hidden: localStorage.getItem('" . htmlspecialchars($storageKey) . "') === '1' }\" x-show=\"!hidden\" x-transition" : '';
?>
<div class="relative overflow-hidden rounded-xl border border-<?= $color ?>-500/20 bg-gradient-to-br <?= $cm['from'] ?> via-slate-900 to-slate-900"<?= $dismissAttr ?>>
    <!-- Background decorations -->
    <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-<?= $color ?>-500/5 -translate-y-1/2 translate-x-1/4"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full bg-<?= $color ?>-500/5 translate-y-1/2 -translate-x-1/4"></div>

    <?php if ($storageKey): ?>
    <button @click="localStorage.setItem('<?= htmlspecialchars($storageKey) ?>', '1'); hidden = true"
            class="absolute top-4 right-4 p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors z-10">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
    <?php endif; ?>

    <div class="relative p-6 sm:p-8">
        <?php if ($badge): ?>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $cm['badge'] ?> mb-3"><?= htmlspecialchars($badge) ?></span>
        <?php endif; ?>

        <h2 class="text-xl sm:text-2xl font-bold text-white mb-2"><?= htmlspecialchars($title) ?></h2>
        <p class="text-slate-300 text-sm mb-6"><?= htmlspecialchars($description) ?></p>

        <?php if (!empty($steps)): ?>
        <div class="grid grid-cols-<?= count($steps) ?> gap-3 sm:gap-4 mb-6">
            <?php foreach ($steps as $step): ?>
            <div class="text-center">
                <div class="h-12 w-12 rounded-xl <?= $cm['icon'] ?> flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?= $step['icon'] ?>
                    </svg>
                </div>
                <p class="text-xs font-medium text-white"><?= htmlspecialchars($step['title']) ?></p>
                <?php if (!empty($step['subtitle'])): ?>
                <p class="text-xs <?= $cm['sub'] ?> mt-0.5"><?= htmlspecialchars($step['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($ctaText && $ctaUrl): ?>
        <a href="<?= htmlspecialchars($ctaUrl) ?>" class="inline-flex items-center px-4 py-2 rounded-lg <?= $cm['btn'] ?> text-white text-sm font-medium transition-colors">
            <?= htmlspecialchars($ctaText) ?>
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
        <?php endif; ?>
    </div>
</div>
