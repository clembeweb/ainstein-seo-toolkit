# Dashboard Redesign - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the chaotic 8-section dashboard with a dual-mode "Smart Actions" dashboard: Launchpad for new users, Task Manager for active users.

**Architecture:** Two-mode view controlled by `$isNewUser = ($projectsCount ?? 0) === 0`. The route controller is simplified to remove unused queries (sparklines, 30d chart, doughnut, activity feed). The view is rewritten from scratch with no Chart.js dependency.

**Tech Stack:** PHP 8+, Tailwind CSS, Alpine.js (minimal), Heroicons SVG

**Design doc:** `docs/plans/2026-02-18-dashboard-redesign-design.md`

---

### Task 1: Simplify dashboard route controller

**Files:**
- Modify: `public/index.php:320-542` (route `/dashboard`)

**What to do:**

Remove these queries (no longer needed):
- `$usageYesterday` (line 332-334)
- `$usageLastMonth` (line 340-342)
- `$operationsToday` (line 344-346)
- `$operationsYesterday` (line 348-350)
- `$dailyUsage7d` (line 352-355)
- `$dailyOps7d` (line 357-360)
- `$dailyUsage30d` (line 362-365)
- `$creditsByModule` (line 367-370)
- `$activityFeed` (line 372-375)
- `$onboardingWelcomeCompleted` (line 378)
- `$onboardingCompletedModules` (line 379)

Keep these queries:
- `$usageToday` (for header badge)
- `$usageMonth` (for header badge)
- `$projectsCount` (for new/active user switch)
- `$pipelineData` (for smart actions)
- `$widgetData` (for smart actions + compact module cards)

Simplify `View::render()` to pass only:

```php
return View::render('dashboard', [
    'title' => 'Dashboard',
    'user' => $user,
    'modules' => $modules,
    'usageToday' => $usageToday,
    'usageMonth' => $usageMonth,
    'projectsCount' => $projectsCount,
    'pipelineData' => $pipelineData,
    'widgetData' => $widgetData,
]);
```

**Verify:** `php -l public/index.php`

**Commit:** `refactor(dashboard): simplify route controller, remove unused queries`

---

### Task 2: Rewrite dashboard view - data maps and header

**Files:**
- Modify: `shared/views/dashboard.php` (full rewrite)

**What to do:**

Replace the entire file. Start with:

1. **Data maps** (keep `$moduleIcons`, `$moduleColors`, `$moduleNames` — remove `$actionLabels`)
2. **Helper variables:**

```php
$_credits = (float)($user['credits'] ?? 0);
$_pipe = $pipelineData ?? [];
$_w = $widgetData ?? [];
$_hour = (int) date('H');
$_greeting = $_hour < 12 ? 'Buongiorno' : ($_hour < 18 ? 'Buon pomeriggio' : 'Buonasera');
$isNewUser = ($projectsCount ?? 0) === 0;
```

3. **Header section** — single compact row:

```html
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
        <?= $_greeting ?>, <?= htmlspecialchars($user['name'] ?? 'Utente') ?>!
    </h1>
    <div class="flex items-center gap-3">
        <!-- Credit badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg
            <?php if ($_credits < 3): ?>
                bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
            <?php elseif ($_credits < 10): ?>
                bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800
            <?php else: ?>
                bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700
            <?php endif; ?>">
            <svg class="w-4 h-4 <?= $_credits < 3 ? 'text-red-500' : ($_credits < 10 ? 'text-amber-500' : 'text-primary-500') ?>"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-semibold <?= $_credits < 3 ? 'text-red-700 dark:text-red-300' : ($_credits < 10 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white') ?>">
                <?= number_format($_credits, 1) ?> crediti
            </span>
        </div>
        <!-- Usage stats (only for active users) -->
        <?php if (!$isNewUser): ?>
        <div class="hidden sm:flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span>Oggi: <?= number_format($usageToday, 1) ?></span>
            <span class="text-slate-300 dark:text-slate-600">|</span>
            <span>Mese: <?= number_format($usageMonth, 1) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
```

**Verify:** `php -l shared/views/dashboard.php`

---

### Task 3: Launchpad mode (new users)

**Files:**
- Modify: `shared/views/dashboard.php` (continue writing)

**What to do:**

After the header, add the Launchpad section wrapped in `<?php if ($isNewUser): ?>`:

```php
$launchpadCards = [
    [
        'slug' => 'keyword-research',
        'task' => 'Trovare keyword strategiche',
        'desc' => 'L\'AI analizza il tuo settore, espande le keyword e le raggruppa per intento di ricerca.',
        'cost' => 'Da 2 crediti',
    ],
    [
        'slug' => 'ai-content',
        'task' => 'Scrivere articoli SEO per il tuo blog',
        'desc' => 'Dai la keyword, Ainstein studia i top Google, scrive e pubblica su WordPress.',
        'cost' => 'Da 3 crediti',
    ],
    [
        'slug' => 'seo-audit',
        'task' => 'Scoprire cosa migliorare nel tuo sito',
        'desc' => 'Audit completo con piano d\'azione ordinato per impatto.',
        'cost' => 'Da 2 crediti',
    ],
    [
        'slug' => 'seo-tracking',
        'task' => 'Monitorare le posizioni su Google',
        'desc' => 'Tracking keyword con click reali da Google Search Console.',
        'cost' => 'Da 1 credito',
    ],
    [
        'slug' => 'ads-analyzer',
        'task' => 'Analizzare o creare campagne Google Ads',
        'desc' => 'Trova sprechi di budget o crea campagne complete da zero.',
        'cost' => 'Da 2 crediti',
    ],
];
```

Grid layout: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4`

Each card: module icon (colored background), task title (bold, action-oriented), description (2 lines, muted), cost badge bottom-left, "Inizia" CTA bottom-right. Card links to `url('/' . $card['slug'])`.

---

### Task 4: Task Manager mode - Smart Actions

**Files:**
- Modify: `shared/views/dashboard.php` (continue writing)

**What to do:**

Inside `<?php else: ?>` (active user mode), build smart actions array:

```php
$smartActions = [];

if ($_credits < 3) {
    $smartActions[] = [
        'text' => 'Crediti quasi esauriti (' . number_format($_credits, 1) . ')',
        'cta' => 'Ricarica',
        'url' => url('/profile#credits'),
        'slug' => null,
        'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        'color' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-600 dark:text-red-400', 'border' => 'border-l-red-500'],
    ];
}
if (($_pipe['aic_ready'] ?? 0) > 0) {
    $n = $_pipe['aic_ready'];
    $smartActions[] = [
        'text' => $n . ' articol' . ($n > 1 ? 'i pronti' : 'o pronto') . ' da pubblicare su WordPress',
        'cta' => 'Pubblica',
        'url' => url('/ai-content'),
        'slug' => 'ai-content',
    ];
}
if (!($_pipe['wp_connected'] ?? false) && ($_pipe['aic_keywords'] ?? 0) > 0) {
    $smartActions[] = [
        'text' => 'Collega WordPress per la pubblicazione automatica',
        'cta' => 'Collega',
        'url' => url('/ai-content/wordpress'),
        'slug' => 'ai-content',
    ];
}
if (($_pipe['aic_keywords'] ?? 0) > 0 && ($_pipe['aic_articles'] ?? 0) === 0) {
    $smartActions[] = [
        'text' => 'Keyword in coda ma nessun articolo generato. Avvia la generazione!',
        'cta' => 'Genera',
        'url' => url('/ai-content'),
        'slug' => 'ai-content',
    ];
}
$saWidget = $_w['seo-audit'] ?? null;
if ($saWidget && ($saWidget['issues'] ?? 0) > 0) {
    $smartActions[] = [
        'text' => $saWidget['issues'] . ' problemi trovati nell\'ultimo audit',
        'cta' => 'Vedi piano',
        'url' => url('/seo-audit'),
        'slug' => 'seo-audit',
    ];
}
$stWidget = $_w['seo-tracking'] ?? null;
if ($stWidget && ($stWidget['keywords'] ?? 0) > 0 && !($stWidget['gsc_connected'] ?? false)) {
    $smartActions[] = [
        'text' => 'Collega Google Search Console per dati click reali',
        'cta' => 'Collega',
        'url' => url('/seo-tracking'),
        'slug' => 'seo-tracking',
    ];
}
if ($_credits >= 3 && $_credits < 10) {
    $smartActions[] = [
        'text' => 'Crediti in esaurimento (' . number_format($_credits, 1) . ')',
        'cta' => 'Ricarica',
        'url' => url('/profile#credits'),
        'slug' => null,
        'color' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-l-amber-500'],
    ];
}
```

Each action card: colored left border (module color), icon, text, CTA button.

If `empty($smartActions)`: show "Tutto in ordine!" message with quick links.

Limit to max 5 actions: `$smartActions = array_slice($smartActions, 0, 5);`

---

### Task 5: Task Manager mode - Compact Module Grid

**Files:**
- Modify: `shared/views/dashboard.php` (continue writing)

**What to do:**

Below smart actions, heading "I tuoi strumenti", grid `grid-cols-2 lg:grid-cols-3 gap-3`.

Each card is a compact `<a>` with:
- Left: module icon (small colored circle)
- Center: module name (bold) + 1 metric below (muted text)
- Right: arrow chevron

Module metrics from `$widgetData`:
- `ai-content`: `$w['articles_total']` articoli
- `seo-tracking`: `$w['keywords']` keyword
- `keyword-research`: `$w['projects']` progetti
- `seo-audit`: `$w['health_score']` (show "Score: XX" or projects count)
- `ads-analyzer`: `$w['total']` campagne
- `internal-links`: `$w['projects']` progetti

Fallback if no widget data: "Inizia"

Close the `<?php endif; ?>` for the isNewUser conditional.

---

### Task 6: Verify and commit

**Steps:**
1. `php -l shared/views/dashboard.php`
2. `php -l public/index.php`
3. Test in browser: `http://localhost/seo-toolkit/dashboard`
4. Verify both modes (simulate new user vs active user)
5. Commit: `feat(dashboard): redesign with Smart Actions dual-mode layout`
