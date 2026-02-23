# Dashboard Unification Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Unify all module dashboards and landing pages with shared components for consistent UI/UX across the platform.

**Architecture:** Create 5 shared PHP components (`View::partial`) for KPI cards, hero banners, "Come funziona" sections, and mode cards. Then refactor each module's views to use these components instead of inline HTML. No controller changes needed.

**Tech Stack:** PHP 8+, Tailwind CSS, Alpine.js, Heroicons SVG

**Design Doc:** `docs/plans/2026-02-23-dashboard-unification-design.md`

---

## Dependency Graph

```
Task 1: dashboard-kpi-card.php (shared component)
Task 2: dashboard-stats-row.php (shared component, depends on Task 1)
Task 3: dashboard-hero-banner.php (shared component)
Task 4: dashboard-how-it-works.php (shared component)
Task 5: dashboard-mode-card.php (shared component)
Task 6: ai-content manual dashboard (depends on Tasks 1-2, 4)
Task 7: ai-content auto dashboard (depends on Tasks 1-2, 4)
Task 8: ai-content meta-tag dashboard (depends on Tasks 1-2, 4)
Task 9: seo-tracking dashboard (depends on Tasks 1-2, 4)
Task 10: seo-audit dashboard (depends on Tasks 1-2, 4)
Task 11: internal-links analyzer dashboard (depends on Tasks 1-2, 4)
Task 12: content-creator project dashboard (depends on Tasks 1-2, 4)
Task 13: keyword-research landing (depends on Tasks 3, 5) — CSS alignment
Task 14: ads-analyzer landing (depends on Task 3) — CSS alignment
Task 15: seo-audit landing (depends on Task 3) — add hero banner
Task 16: seo-tracking landing (depends on Task 3) — align hero to shared component
Task 17: internal-links landing (depends on Task 3) — add hero banner
Task 18: content-creator landing (depends on Task 3) — add hero banner
Task 19: Verify tour guidato data-tour attributes on all refactored views
Task 20: Final verification — test all dashboards in browser
```

---

### Task 1: Create `dashboard-kpi-card.php` shared component

**Files:**
- Create: `shared/views/components/dashboard-kpi-card.php`

**Step 1: Create the component**

```php
<?php
/**
 * Dashboard KPI Card Component
 *
 * Params:
 * @param string $label     - Metric label (es. "Keyword monitorate")
 * @param mixed  $value     - Metric value (number or string)
 * @param string $icon      - SVG path(s) for Heroicons
 * @param string $color     - Color name: blue|emerald|amber|purple|rose|cyan|orange
 * @param string $url       - (optional) Makes card a clickable link
 * @param string $suffix    - (optional) Suffix after value (es. "%")
 * @param string $subtitle  - (optional) Small text below value
 */

$color = $color ?? 'blue';
$url = $url ?? null;
$suffix = $suffix ?? '';
$subtitle = $subtitle ?? null;
$value = $value ?? 0;

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
        <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $displayValue ?><?= htmlspecialchars($suffix) ?></p>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($label) ?></p>
            <?php if ($subtitle): ?>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </div>
</<?= $tag ?>>
```

**Step 2: Verify PHP syntax**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/components/dashboard-kpi-card.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-kpi-card.php
git commit -m "feat: add dashboard-kpi-card shared component"
```

---

### Task 2: Create `dashboard-stats-row.php` shared component

**Files:**
- Create: `shared/views/components/dashboard-stats-row.php`

**Step 1: Create the component**

```php
<?php
/**
 * Dashboard Stats Row Component
 * Renders a responsive grid of KPI cards.
 *
 * Params:
 * @param array $cards - Array of card params, each passed to dashboard-kpi-card
 *   Each card: ['label' => '...', 'value' => N, 'icon' => '...', 'color' => '...', 'url' => '...']
 * @param string $dataTour - (optional) data-tour attribute for tour targeting
 */

$cards = $cards ?? [];
$dataTour = $dataTour ?? null;
$count = count($cards);
$cols = min($count, 4);

$gridClass = match($cols) {
    1 => 'grid-cols-1',
    2 => 'grid-cols-2',
    3 => 'grid-cols-2 md:grid-cols-3',
    default => 'grid-cols-2 md:grid-cols-4',
};
?>
<div class="grid <?= $gridClass ?> gap-4"<?= $dataTour ? ' data-tour="' . htmlspecialchars($dataTour) . '"' : '' ?>>
    <?php foreach ($cards as $card): ?>
        <?= \Core\View::partial('components/dashboard-kpi-card', $card) ?>
    <?php endforeach; ?>
</div>
```

**Step 2: Verify PHP syntax**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/components/dashboard-stats-row.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-stats-row.php
git commit -m "feat: add dashboard-stats-row shared component"
```

---

### Task 3: Create `dashboard-hero-banner.php` shared component

**Files:**
- Create: `shared/views/components/dashboard-hero-banner.php`

**Step 1: Create the component**

This follows the pattern already used in `seo-audit/views/projects/index.php` (lines 17-67) and `seo-tracking/views/projects/index.php` (lines 17-67). It's a dark gradient card with workflow steps and optional dismiss button.

```php
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
```

**Step 2: Verify PHP syntax**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/components/dashboard-hero-banner.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-hero-banner.php
git commit -m "feat: add dashboard-hero-banner shared component"
```

---

### Task 4: Create `dashboard-how-it-works.php` shared component

**Files:**
- Create: `shared/views/components/dashboard-how-it-works.php`

**Step 1: Create the component**

```php
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
```

**Step 2: Verify PHP syntax**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/components/dashboard-how-it-works.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-how-it-works.php
git commit -m "feat: add dashboard-how-it-works shared component"
```

---

### Task 5: Create `dashboard-mode-card.php` shared component

**Files:**
- Create: `shared/views/components/dashboard-mode-card.php`

**Step 1: Create the component**

Based on keyword-research mode cards pattern but standardized to `rounded-xl`.

```php
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
```

**Step 2: Verify PHP syntax**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/components/dashboard-mode-card.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-mode-card.php
git commit -m "feat: add dashboard-mode-card shared component"
```

---

### Task 6: Refactor ai-content manual dashboard

**Files:**
- Modify: `modules/ai-content/views/dashboard.php`

**Context:** This dashboard currently has inline KPI cards (lines 47-80) with `h-10 w-10` icons, no "Come funziona" section, and a credits info card at bottom. We need to:
1. Replace inline KPI cards with `dashboard-stats-row` component
2. Add `dashboard-how-it-works` section before the credits info card

**Step 1: Replace inline KPI grid (lines ~47-80) with shared component**

Replace the entire `<div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-tour="aic-stats">` block with:

```php
<?= \Core\View::partial('components/dashboard-stats-row', [
    'dataTour' => 'aic-stats',
    'cards' => [
        [
            'label' => 'Keywords',
            'value' => $stats['keywords'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
            'color' => 'blue',
            'url' => $baseUrl . '/keywords',
        ],
        [
            'label' => 'Articoli',
            'value' => $stats['articles'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'color' => 'purple',
            'url' => $baseUrl . '/articles',
        ],
        [
            'label' => 'Pubblicati',
            'value' => $stats['articles_published'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'color' => 'emerald',
            'url' => $baseUrl . '/articles?status=published',
        ],
        [
            'label' => 'Parole generate',
            'value' => $stats['total_words'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>',
            'color' => 'amber',
        ],
    ],
]) ?>
```

**Step 2: Add "Come funziona" section before the credits info card**

Insert before the credits gradient card (search for "Costi Crediti" or the gradient card):

```php
<!-- Come funziona -->
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'amber',
    'steps' => [
        ['title' => 'Aggiungi Keyword', 'description' => 'Inserisci la keyword target'],
        ['title' => 'Analisi SERP', 'description' => 'AI analizza i competitor'],
        ['title' => 'Genera Articolo', 'description' => 'AI scrive il contenuto SEO'],
        ['title' => 'Pubblica', 'description' => 'Invia a WordPress'],
    ],
]) ?>
```

**Step 3: Verify PHP syntax**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/ai-content/views/dashboard.php`

**Step 4: Test in browser**

Navigate to: `http://localhost/seo-toolkit/ai-content/projects/18`
Verify: KPI cards render with w-12 h-12 icons, "Come funziona" section visible, all links work

**Step 5: Commit**

```bash
git add modules/ai-content/views/dashboard.php
git commit -m "refactor: use shared KPI components in ai-content manual dashboard"
```

---

### Task 7: Refactor ai-content auto dashboard

**Files:**
- Modify: `modules/ai-content/views/auto/dashboard.php`

**Context:** Auto dashboard has process control buttons + real-time progress. We keep those unique elements but add KPI stats row at top and "Come funziona" at bottom.

**Step 1: Read the current file to understand its structure**

Read: `modules/ai-content/views/auto/dashboard.php` fully to identify where to insert KPI row and "Come funziona".

**Step 2: Add KPI stats row after the project-nav include**

```php
<?= \Core\View::partial('components/dashboard-stats-row', [
    'cards' => [
        [
            'label' => 'In coda',
            'value' => $stats['pending'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
            'color' => 'amber',
        ],
        [
            'label' => 'Completate oggi',
            'value' => $stats['completed_today'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'color' => 'emerald',
        ],
        [
            'label' => 'Articoli totali',
            'value' => $stats['articles'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'color' => 'purple',
            'url' => url('/ai-content/projects/' . $project['id'] . '/articles'),
        ],
        [
            'label' => 'Pubblicati',
            'value' => $stats['articles_published'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'color' => 'blue',
        ],
    ],
]) ?>
```

**Step 3: Add "Come funziona" at bottom**

```php
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'purple',
    'steps' => [
        ['title' => 'Importa Keyword', 'description' => 'Lista keyword da elaborare'],
        ['title' => 'Schedula', 'description' => 'Imposta cron automatico'],
        ['title' => 'AI Genera', 'description' => 'Articoli creati in batch'],
        ['title' => 'Pubblica', 'description' => 'Invio automatico a WP'],
    ],
]) ?>
```

**Step 4: Verify PHP syntax and test in browser**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/ai-content/views/auto/dashboard.php`
Test: `http://localhost/seo-toolkit/ai-content/projects/{auto-id}/auto`

**Step 5: Commit**

```bash
git add modules/ai-content/views/auto/dashboard.php
git commit -m "refactor: add shared KPI and how-it-works to ai-content auto dashboard"
```

---

### Task 8: Refactor ai-content meta-tag dashboard

**Files:**
- Modify: `modules/ai-content/views/meta-tags/dashboard.php`

**Context:** Meta-tag dashboard already has a "Come funziona" section. We need to replace the inline 6-column KPI grid with the shared component and verify "Come funziona" matches the shared pattern (or replace it).

**Step 1: Replace inline KPI grid with shared component**

Replace the `<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">` block with:

```php
<?= \Core\View::partial('components/dashboard-stats-row', [
    'cards' => [
        [
            'label' => 'Totale URL',
            'value' => $stats['total'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
            'color' => 'blue',
        ],
        [
            'label' => 'Scrappate',
            'value' => $stats['scraped'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>',
            'color' => 'cyan',
        ],
        [
            'label' => 'Generate',
            'value' => $stats['generated'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
            'color' => 'purple',
        ],
        [
            'label' => 'Pubblicate',
            'value' => $stats['published'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'color' => 'emerald',
        ],
    ],
]) ?>
```

**Note:** We reduce from 6 columns to 4 for consistency. The "Da scrapare" and "Approvate" intermediate stats move to the quick action cards that already display them.

**Step 2: Replace existing "Come funziona" with shared component**

Replace the existing inline "Come funziona" section with:

```php
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'emerald',
    'steps' => [
        ['title' => 'Importa', 'description' => 'URL da WP/Sitemap/CSV'],
        ['title' => 'Scrape', 'description' => 'Analizza contenuto pagine'],
        ['title' => 'Genera', 'description' => 'AI crea title e description'],
        ['title' => 'Approva', 'description' => 'Rivedi e modifica'],
        ['title' => 'Pubblica', 'description' => 'Invia a WordPress'],
    ],
]) ?>
```

**Step 3: Verify and test**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/ai-content/views/meta-tags/dashboard.php`
Test: `http://localhost/seo-toolkit/ai-content/projects/{meta-tag-id}/meta-tags`

**Step 4: Commit**

```bash
git add modules/ai-content/views/meta-tags/dashboard.php
git commit -m "refactor: use shared KPI and how-it-works in ai-content meta-tag dashboard"
```

---

### Task 9: Refactor seo-tracking project dashboard

**Files:**
- Modify: `modules/seo-tracking/views/dashboard/index.php`

**Context:** seo-tracking has the most complete dashboard with KPI cards + charts + gainers/losers tables. We replace inline KPI cards with shared component and add "Come funziona" at bottom. Keep charts and tables untouched.

**Step 1: Replace inline KPI grid with shared component**

Replace the `<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">` block (lines ~8-80) with:

```php
<?= \Core\View::partial('components/dashboard-stats-row', [
    'dataTour' => 'st-stats',
    'cards' => [
        [
            'label' => 'Keywords tracciate',
            'value' => $kpiStats['tracked_keywords'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
            'color' => 'blue',
        ],
        [
            'label' => 'Posizione media',
            'value' => $kpiStats['avg_position'] ? number_format($kpiStats['avg_position'], 1) : '--',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
            'color' => 'amber',
        ],
        [
            'label' => 'In Top 10',
            'value' => $kpiStats['top10_count'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
            'color' => 'emerald',
        ],
        [
            'label' => 'Variazioni 7gg',
            'value' => ($kpiStats['improved_7d'] ?? 0) . '/' . ($kpiStats['declined_7d'] ?? 0),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>',
            'color' => 'purple',
        ],
    ],
]) ?>
```

**Step 2: Add "Come funziona" at bottom of the dashboard (after gainers/losers tables)**

```php
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'blue',
    'steps' => [
        ['title' => 'Aggiungi Keyword', 'description' => 'Le keyword da monitorare'],
        ['title' => 'Rank Check', 'description' => 'Verifica posizioni SERP'],
        ['title' => 'Monitora Trend', 'description' => 'Storico e variazioni'],
        ['title' => 'Report AI', 'description' => 'Analisi e suggerimenti'],
    ],
]) ?>
```

**Step 3: Verify and test**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/seo-tracking/views/dashboard/index.php`
Test: `http://localhost/seo-toolkit/seo-tracking/project/5` (or any active project)

**Step 4: Commit**

```bash
git add modules/seo-tracking/views/dashboard/index.php
git commit -m "refactor: use shared KPI and how-it-works in seo-tracking dashboard"
```

---

### Task 10: Refactor seo-audit project dashboard

**Files:**
- Modify: `modules/seo-audit/views/audit/dashboard.php`

**Context:** seo-audit has a unique circular gauge for health score. We keep that unique element but standardize padding/radius, add "Come funziona" at bottom, and fix any CSS deviations from Golden Rule #20.

**Step 1: Read the file fully to identify all CSS deviations**

Read `modules/seo-audit/views/audit/dashboard.php` and note all `p-6`, `rounded-2xl`, or other non-standard classes.

**Step 2: Fix CSS inconsistencies**

- Replace any `p-6` on card containers with `p-5`
- Replace any `rounded-2xl` with `rounded-xl`
- Ensure dark mode uses `dark:bg-slate-700/50` for headers (not `dark:bg-slate-800/50`)

**Step 3: Add "Come funziona" at bottom**

```php
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'emerald',
    'steps' => [
        ['title' => 'Inserisci URL', 'description' => 'Il sito da analizzare'],
        ['title' => 'Crawl Sito', 'description' => 'Scansione pagine e risorse'],
        ['title' => 'Analisi AI', 'description' => 'Identifica problemi SEO'],
        ['title' => 'Piano d\'Azione', 'description' => 'Suggerimenti prioritizzati'],
    ],
]) ?>
```

**Step 4: Verify and test**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/seo-audit/views/audit/dashboard.php`
Test: `http://localhost/seo-toolkit/seo-audit/project/{id}`

**Step 5: Commit**

```bash
git add modules/seo-audit/views/audit/dashboard.php
git commit -m "refactor: align CSS and add how-it-works to seo-audit dashboard"
```

---

### Task 11: Refactor internal-links analyzer dashboard

**Files:**
- Modify: `modules/internal-links/views/analyzer/index.php`

**Context:** Internal-links analyzer has inline KPI stats cards and a batch processing panel. We replace KPI cards with shared component and add "Come funziona".

**Step 1: Read file and identify inline KPI section**

Read `modules/internal-links/views/analyzer/index.php` to find the stats grid.

**Step 2: Replace inline KPI with shared component**

```php
<?= \Core\View::partial('components/dashboard-stats-row', [
    'cards' => [
        [
            'label' => 'Pagine analizzate',
            'value' => $stats['total_pages'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'color' => 'cyan',
        ],
        [
            'label' => 'Link interni',
            'value' => $stats['internal_links'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
            'color' => 'blue',
        ],
        [
            'label' => 'Da analizzare',
            'value' => $stats['pending'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'color' => 'amber',
        ],
        [
            'label' => 'Relevance media',
            'value' => $stats['avg_score'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
            'color' => 'purple',
        ],
    ],
]) ?>
```

**Step 3: Add "Come funziona" at bottom**

```php
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'cyan',
    'steps' => [
        ['title' => 'Importa URL', 'description' => 'Sitemap o lista pagine'],
        ['title' => 'Scrape Link', 'description' => 'Estrai tutti i link interni'],
        ['title' => 'Analisi Struttura', 'description' => 'Mappa dei collegamenti'],
        ['title' => 'Ottimizza', 'description' => 'Suggerimenti AI per link'],
    ],
]) ?>
```

**Step 4: Verify and test**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/internal-links/views/analyzer/index.php`

**Step 5: Commit**

```bash
git add modules/internal-links/views/analyzer/index.php
git commit -m "refactor: use shared KPI and how-it-works in internal-links dashboard"
```

---

### Task 12: Refactor content-creator project dashboard

**Files:**
- Modify: `modules/content-creator/views/projects/show.php` (or the project dashboard view)

**Context:** Content-creator project dashboard needs KPI standardization and "Come funziona" section.

**Step 1: Read the current project dashboard file**

Find and read the content-creator project dashboard view. May be at `modules/content-creator/views/projects/show.php` or `modules/content-creator/views/dashboard.php`.

**Step 2: Replace inline KPI with shared component**

Adapt based on available stats variables:

```php
<?= \Core\View::partial('components/dashboard-stats-row', [
    'cards' => [
        [
            'label' => 'URL totali',
            'value' => $stats['total'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
            'color' => 'orange',
        ],
        [
            'label' => 'Scrappate',
            'value' => $stats['scraped'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>',
            'color' => 'blue',
        ],
        [
            'label' => 'Generate',
            'value' => $stats['generated'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
            'color' => 'purple',
        ],
        [
            'label' => 'Approvate',
            'value' => $stats['approved'] ?? 0,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'color' => 'emerald',
        ],
    ],
]) ?>
```

**Step 3: Add "Come funziona" at bottom**

```php
<?= \Core\View::partial('components/dashboard-how-it-works', [
    'color' => 'orange',
    'steps' => [
        ['title' => 'Configura', 'description' => 'Template e CMS target'],
        ['title' => 'Importa URL', 'description' => 'Pagine da riscrivere'],
        ['title' => 'Genera', 'description' => 'AI crea i contenuti HTML'],
        ['title' => 'Pubblica', 'description' => 'Invia al CMS'],
    ],
]) ?>
```

**Step 4: Verify and commit**

```bash
git add modules/content-creator/views/projects/show.php
git commit -m "refactor: use shared KPI and how-it-works in content-creator dashboard"
```

---

### Task 13: Align keyword-research landing CSS

**Files:**
- Modify: `modules/keyword-research/views/dashboard.php`

**Context:** Keyword-research uses `rounded-2xl` on mode cards instead of standard `rounded-xl`. Replace with shared `dashboard-mode-card` component.

**Step 1: Replace inline mode cards with shared component**

Replace each mode card (Research Guidata, Architettura Sito, Piano Editoriale, Quick Check) with:

```php
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?= \Core\View::partial('components/dashboard-mode-card', [
        'title' => 'Research Guidata',
        'description' => 'Analisi keyword con clustering AI e volumi di ricerca',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
        'gradient' => 'from-emerald-500 to-teal-600',
        'url' => url('/keyword-research?type=research'),
        'cost' => '3 cr',
        'costColor' => 'amber',
        'badge' => count($projectsByType['research'] ?? []) . ' progetti',
        'dataTour' => 'kr-guided',
    ]) ?>
    <!-- ... repeat for other 3 modes ... -->
</div>
```

Repeat for Architettura (blue→indigo, 10 cr, kr-architecture), Piano Editoriale (violet→purple, 10 cr, kr-editorial), Quick Check (amber→orange, Gratis/emerald, kr-quickcheck).

**Step 2: Verify and test**

Test: `http://localhost/seo-toolkit/keyword-research`
Verify: Cards now use `rounded-xl`, consistent padding, same visual style

**Step 3: Commit**

```bash
git add modules/keyword-research/views/dashboard.php
git commit -m "refactor: use shared mode-card component in keyword-research landing"
```

---

### Task 14: Align ads-analyzer landing CSS

**Files:**
- Modify: `modules/ads-analyzer/views/dashboard.php`

**Context:** Ads-analyzer already has a hero banner and mode cards. Align to shared components.

**Step 1: Replace hero banner with shared component**

Replace inline hero block with `dashboard-hero-banner` partial.

**Step 2: Replace mode cards with shared `dashboard-mode-card` component**

**Step 3: Verify and commit**

```bash
git add modules/ads-analyzer/views/dashboard.php
git commit -m "refactor: use shared hero-banner and mode-card in ads-analyzer landing"
```

---

### Task 15: Add hero banner to seo-audit landing

**Files:**
- Modify: `modules/seo-audit/views/projects/index.php`

**Context:** seo-audit already has an inline hero block (lines 17-67). Replace with shared `dashboard-hero-banner` component.

**Step 1: Replace inline hero with shared component**

Replace the entire hero block with:

```php
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'AI SEO Audit',
    'description' => 'Analizza il tuo sito web e ricevi un piano d\'azione AI per migliorare il posizionamento.',
    'color' => 'emerald',
    'badge' => 'Analisi Completa',
    'storageKey' => 'ainstein_hero_seo_audit',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>', 'title' => 'Inserisci URL', 'subtitle' => 'Il sito da analizzare'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>', 'title' => 'Crawl & Analisi', 'subtitle' => 'Scansione automatica'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'title' => 'Piano d\'Azione', 'subtitle' => 'Suggerimenti AI'],
    ],
    'ctaText' => 'Nuovo Audit',
    'ctaUrl' => url('/projects/create'),
]) ?>
```

**Step 2: Verify and commit**

```bash
git add modules/seo-audit/views/projects/index.php
git commit -m "refactor: use shared hero-banner in seo-audit landing"
```

---

### Task 16: Align seo-tracking landing hero to shared component

**Files:**
- Modify: `modules/seo-tracking/views/projects/index.php`

**Context:** seo-tracking already has an inline hero block (lines 17-67). Replace with shared component.

**Step 1: Replace inline hero with shared component**

```php
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'AI SEO Position Tracking',
    'description' => 'Monitora le posizioni delle tue keyword su Google e ricevi report AI settimanali.',
    'color' => 'blue',
    'badge' => 'Monitoraggio Continuo',
    'storageKey' => 'ainstein_hero_seo_tracking',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>', 'title' => 'Aggiungi Keyword', 'subtitle' => 'Le keyword da monitorare'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>', 'title' => 'Rank Check', 'subtitle' => 'Verifica posizioni SERP'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>', 'title' => 'Report AI', 'subtitle' => 'Analisi e suggerimenti'],
    ],
    'ctaText' => 'Nuovo Progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>
```

**Step 2: Verify and commit**

```bash
git add modules/seo-tracking/views/projects/index.php
git commit -m "refactor: use shared hero-banner in seo-tracking landing"
```

---

### Task 17: Add hero banner to internal-links landing

**Files:**
- Modify: `modules/internal-links/views/projects/index.php`

**Step 1: Read the file and identify where to add hero**

Add hero banner after onboarding component and before the header.

**Step 2: Add shared hero banner component**

```php
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'Internal Links Analyzer',
    'description' => 'Analizza la struttura dei link interni del tuo sito e ottimizza la distribuzione del link juice.',
    'color' => 'cyan',
    'badge' => 'Analisi Link',
    'storageKey' => 'ainstein_hero_internal_links',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>', 'title' => 'Importa URL', 'subtitle' => 'Da sitemap o lista'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>', 'title' => 'Analisi Link', 'subtitle' => 'Mappa la struttura'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>', 'title' => 'Ottimizza', 'subtitle' => 'Suggerimenti AI'],
    ],
    'ctaText' => 'Nuovo Progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>
```

**Step 3: Verify and commit**

```bash
git add modules/internal-links/views/projects/index.php
git commit -m "feat: add shared hero-banner to internal-links landing"
```

---

### Task 18: Add hero banner to content-creator landing

**Files:**
- Modify: `modules/content-creator/views/projects/index.php`

**Step 1: Add shared hero banner component**

```php
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'AI Content Bulk Creator',
    'description' => 'Genera contenuti HTML ottimizzati per le pagine del tuo sito e pubblicali direttamente sul CMS.',
    'color' => 'orange',
    'badge' => 'Generazione Contenuti',
    'storageKey' => 'ainstein_hero_content_creator',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>', 'title' => 'Configura', 'subtitle' => 'Template e CMS target'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>', 'title' => 'Importa URL', 'subtitle' => 'Pagine da processare'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>', 'title' => 'Genera & Pubblica', 'subtitle' => 'AI crea e invia al CMS'],
    ],
    'ctaText' => 'Nuovo Progetto',
    'ctaUrl' => url('/projects/create'),
]) ?>
```

**Step 2: Verify and commit**

```bash
git add modules/content-creator/views/projects/index.php
git commit -m "feat: add shared hero-banner to content-creator landing"
```

---

### Task 19: Verify tour guidato data-tour attributes

**Files:**
- Check all modified views for `data-tour` attributes

**Step 1: Verify each module includes onboarding-spotlight component**

Check that each dashboard view includes:
```php
<?php
$showTour = !\Core\OnboardingService::isModuleCompleted($user['id'], 'module-slug');
if ($showTour && isset($onboardingConfig['module-slug'])):
    echo \Core\View::partial('components/onboarding-spotlight', [...]);
endif;
?>
```

**Step 2: Verify data-tour attributes on KPI sections**

The `dashboard-stats-row` component supports `dataTour` param. Ensure each module passes it:
- ai-content: `data-tour="aic-stats"`
- seo-tracking: `data-tour="st-stats"`
- Others: add if defined in `config/onboarding.php`

**Step 3: Test tours in browser**

For each module, click the "?" button in the sidebar and verify the tour highlights the correct elements.

**Step 4: Commit any fixes**

```bash
git add -A
git commit -m "fix: ensure data-tour attributes on all refactored dashboard views"
```

---

### Task 20: Final verification — test all dashboards in browser

**Step 1: Test each module landing page**

| URL | Expected |
|-----|----------|
| `/ai-content` | Tabs with mode cards, existing info boxes |
| `/keyword-research` | Mode cards with `rounded-xl`, consistent padding |
| `/ads-analyzer` | Hero banner + mode cards |
| `/seo-audit` | Hero banner (shared) + project cards |
| `/seo-tracking` | Hero banner (shared) + project cards |
| `/internal-links` | Hero banner (new) + project cards |
| `/content-creator` | Hero banner (new) + project cards |

**Step 2: Test each module project dashboard**

For each module, enter a project and verify:
- KPI cards use `w-12 h-12` icons in `rounded-xl` boxes
- "Come funziona" section present at bottom
- Dark mode renders correctly
- All links work
- Tour guidato functions

**Step 3: Screenshot key pages for reference**

**Step 4: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix: final adjustments from dashboard unification testing"
```
