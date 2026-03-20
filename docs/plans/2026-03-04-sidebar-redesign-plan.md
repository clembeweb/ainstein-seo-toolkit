# Sidebar Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Reorganize Ainstein's sidebar by grouping 9 modules into 4 collapsible macro-areas (AI SEO, AI Content, AI Audit, AI Ads), plus 3 grouped admin sections.

**Architecture:** Pure frontend refactor of `nav-items.php` + small addition to `layout.php` — wrap existing module nav blocks inside Alpine.js accordion groups driven by a `$navGroups` PHP config array. Module slugs, routes, sub-nav logic, and shared access filtering stay untouched. Crawl Budget (legacy, merged into SEO Audit) is excluded from groups.

**Tech Stack:** PHP 8+, Alpine.js (existing), Tailwind CSS (existing)

**Design Doc:** `docs/plans/2026-03-04-sidebar-redesign-design.md`

**Prerequisito manuale:** L'utente rinomina i moduli dall'admin panel (Admin > Moduli) prima o dopo il deploy. Nomi consigliati nel design doc.

---

### Task 1: Add Group Config Array & Helper to nav-items.php

This task adds the `$navGroups` configuration array and the `navGroupHeader()` helper function at the TOP of `nav-items.php`, before any HTML output. This is the foundation for all subsequent tasks.

**Files:**
- Modify: `shared/views/components/nav-items.php` (add after existing helper functions, before the HTML `<div>`)

**Step 1: Add group config and helper after existing functions (before line 205's `<div class="space-y-1">`)**

Insert this PHP block between the closing `?>` at line 203 and the `<div class="space-y-1">` at line 205:

```php
<?php
// === SIDEBAR GROUPS CONFIG ===
// Maps module slugs to macro-areas for grouped navigation
// Note: crawl-budget is legacy (merged into seo-audit) — NOT included
$navGroups = [
    'ai-seo' => [
        'label' => 'AI SEO',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
        'slugs' => ['seo-tracking', 'keyword-research', 'seo-onpage'],
        'prefixes' => ['/seo-tracking', '/keyword-research', '/seo-onpage'],
    ],
    'ai-content' => [
        'label' => 'AI CONTENT',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
        'slugs' => ['ai-content', 'content-creator', 'ai-optimizer'],
        'prefixes' => ['/ai-content', '/content-creator', '/ai-optimizer'],
    ],
    'ai-audit' => [
        'label' => 'AI AUDIT',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
        'slugs' => ['seo-audit', 'internal-links'],
        'prefixes' => ['/seo-audit', '/internal-links'],
    ],
    'ai-ads' => [
        'label' => 'AI ADS',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/></svg>',
        'slugs' => ['ads-analyzer'],
        'prefixes' => ['/ads-analyzer'],
    ],
];

// Admin groups (only rendered for admins)
$navAdminGroups = [
    'admin-users' => [
        'label' => 'UTENTI & PIANI',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>',
        'links' => [
            ['path' => '/admin', 'label' => 'Overview', 'exact' => true, 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'],
            ['path' => '/admin/users', 'label' => 'Utenti', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'],
            ['path' => '/admin/plans', 'label' => 'Piani', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>'],
            ['path' => '/admin/finance', 'label' => 'Finance', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>'],
        ],
    ],
    'admin-config' => [
        'label' => 'CONFIGURAZIONE',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'links' => [
            ['path' => '/admin/modules', 'label' => 'Moduli', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'],
            ['path' => '/admin/settings', 'label' => 'Impostazioni', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'],
            ['path' => '/admin/email-templates', 'label' => 'Template Email', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>'],
        ],
    ],
    'admin-monitor' => [
        'label' => 'MONITORAGGIO',
        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25z"/></svg>',
        'links' => [
            ['path' => '/admin/ai-logs', 'label' => 'AI Logs', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'],
            ['path' => '/admin/api-logs', 'label' => 'API Logs', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'],
            ['path' => '/admin/jobs', 'label' => 'Jobs Monitor', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>'],
            ['path' => '/admin/cache', 'label' => 'Cache & Log', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>'],
        ],
    ],
];

// Determine which group is active based on current path
$_activeGroup = null;
foreach ($navGroups as $groupKey => $group) {
    foreach ($group['prefixes'] as $prefix) {
        if (str_starts_with($currentPath, $prefix)) {
            $_activeGroup = $groupKey;
            break 2;
        }
    }
}

// Admin active group
$_activeAdminGroup = null;
if ($isAdmin && str_starts_with($currentPath, '/admin')) {
    foreach ($navAdminGroups as $groupKey => $group) {
        foreach ($group['links'] as $link) {
            $linkPath = $link['path'];
            $exact = $link['exact'] ?? false;
            if ($exact ? $currentPath === $linkPath : str_starts_with($currentPath, $linkPath)) {
                $_activeAdminGroup = $groupKey;
                break 2;
            }
        }
    }
}

// Helper: render a group header with accordion toggle
if (!function_exists('navGroupHeader')) {
    function navGroupHeader(string $groupKey, string $label, string $icon, bool $isActive): string {
        return sprintf(
            '<button @click="toggleGroup(\'%s\')" class="w-full flex items-center justify-between px-3 py-2 mt-1 text-xs font-semibold uppercase tracking-wider transition-colors %s">'
            . '<span class="flex items-center gap-2">%s %s</span>'
            . '<svg class="w-3.5 h-3.5 transition-transform duration-200" :class="isGroupOpen(\'%s\') && \'rotate-180\'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>'
            . '</button>',
            $groupKey,
            $isActive
                ? 'text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300'
                : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300',
            $icon,
            $label,
            $groupKey
        );
    }
}
?>
```

**Step 2: Verify PHP syntax**

Run: `php -l shared/views/components/nav-items.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add shared/views/components/nav-items.php
git commit -m "feat(sidebar): add macro-area group config and helper functions"
```

---

### Task 2: Add Alpine.js Group State to Layout Body

**Files:**
- Modify: `shared/views/layout.php` (line ~91-98, the `x-data` on `<body>`)

**Step 1: Extend the body `x-data` to include sidebar group state**

Find the existing `x-data` on the `<body>` tag (around line 91-98). It currently looks like:

```html
x-data="{
    darkMode: localStorage.getItem('darkMode') === 'true',
    sidebarOpen: false,
    toast: { show: false, message: '', type: 'success' }
}"
```

Replace with:

```html
x-data="{
    darkMode: localStorage.getItem('darkMode') === 'true',
    sidebarOpen: false,
    toast: { show: false, message: '', type: 'success' },
    sidebarGroups: JSON.parse(localStorage.getItem('sidebarGroups') || '{}'),
    toggleGroup(key) {
        this.sidebarGroups[key] = !this.isGroupOpen(key);
        localStorage.setItem('sidebarGroups', JSON.stringify(this.sidebarGroups));
    },
    isGroupOpen(key) {
        const el = document.querySelector(`[data-nav-group='${key}']`);
        if (el && el.dataset.autoExpand === 'true' && this.sidebarGroups[key] === undefined) {
            return true;
        }
        return this.sidebarGroups[key] ?? false;
    }
}"
```

**Step 2: Verify PHP syntax**

Run: `php -l shared/views/layout.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add shared/views/layout.php
git commit -m "feat(sidebar): add Alpine.js group accordion state to layout"
```

---

### Task 3: Restructure Module Navigation — Wrap in Groups

This is the main task. We replace the flat `foreach ($modules as $module)` loop with a group-based loop that renders each macro-area as an accordion, while preserving ALL existing module-specific sub-navigation HTML.

**Files:**
- Modify: `shared/views/components/nav-items.php` (lines 242-747 — the entire `<div class="mt-6">` modules section)

**Step 1: Replace the modules section**

Replace everything from `<div class="mt-6">` (line 242) through `<?php endif; ?>` (line 747, end of the modules block) with the new grouped structure.

The key structural change:
- Remove the old `<p>Moduli</p>` header and flat `foreach ($modules as $module)` loop
- Add a loop over `$navGroups` that renders each group as an accordion
- Inside each group, loop only over modules whose slug matches that group
- ALL existing per-module HTML blocks (the `if ($module['slug'] === 'internal-links')` etc.) stay **verbatim inside** their group
- Modules not in any group (like `crawl-budget`) are skipped

New structure (pseudocode):

```php
<?php if (!empty($modules)): ?>
<?php
// Shared access filtering logic (KEEP EXISTING — lines 214-241)
// ... $_navGlobalProjectId, $_navAllowedSlugs ...
?>

<?php foreach ($navGroups as $groupKey => $group): ?>
    <!-- Group header (accordion toggle) -->
    <?= navGroupHeader($groupKey, $group['label'], $group['icon'], $_activeGroup === $groupKey) ?>

    <!-- Group content (collapsible) -->
    <div data-nav-group="<?= $groupKey ?>"
         data-auto-expand="<?= $_activeGroup === $groupKey ? 'true' : 'false' ?>"
         x-show="isGroupOpen('<?= $groupKey ?>')"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        <div class="space-y-1 ml-1">
            <?php foreach ($modules as $module): ?>
                <?php if (!in_array($module['slug'], $group['slugs'])) continue; ?>
                <?php if ($_navAllowedSlugs !== null && !in_array($module['slug'], $_navAllowedSlugs, true)) continue; ?>

                <!-- EXISTING per-module HTML block, VERBATIM -->
                <?php if ($module['slug'] === 'seo-tracking'): ?>
                    ... (exact same HTML as current lines 409-460)
                <?php elseif ($module['slug'] === 'keyword-research'): ?>
                    ... (exact same HTML as current lines 570-617)
                <?php elseif ($module['slug'] === 'seo-onpage'): ?>
                    ... (standard navLink, same as current fallback)
                <?php elseif ($module['slug'] === 'ai-content'): ?>
                    ... (exact same HTML as current lines 502-569)
                <?php elseif ($module['slug'] === 'content-creator'): ?>
                    ... (exact same HTML as current lines 687-730)
                <?php elseif ($module['slug'] === 'ai-optimizer'): ?>
                    ... (exact same HTML as current lines 461-501)
                <?php elseif ($module['slug'] === 'seo-audit'): ?>
                    ... (exact same HTML as current lines 313-408)
                <?php elseif ($module['slug'] === 'internal-links'): ?>
                    ... (exact same HTML as current lines 252-312)
                <?php elseif ($module['slug'] === 'ads-analyzer'): ?>
                    ... (exact same HTML as current lines 618-686)
                <?php else: ?>
                    ... (standard navLink fallback, same as current lines 731-742)
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>
```

**CRITICAL**: Do NOT modify any of the per-module HTML. Copy them verbatim. The only changes are:
1. Wrapping in group accordion containers
2. The outer loop iterates `$navGroups` then filters `$modules` by slug
3. Shared access filtering moves inside the inner loop
4. Modules not in any group's `slugs` array are naturally excluded

**Step 2: Verify PHP syntax**

Run: `php -l shared/views/components/nav-items.php`
Expected: `No syntax errors detected`

**Step 3: Visual test in browser**

Open: `http://localhost/seo-toolkit/dashboard`

Verify:
- 4 macro-area headers visible: AI SEO, AI CONTENT, AI AUDIT, AI ADS
- Click each header toggles modules
- The group containing the current page is auto-expanded
- Modules inside groups look identical to before
- Module sub-navigation (project accordion) works unchanged
- crawl-budget does NOT appear in any group

**Step 4: Commit**

```bash
git add shared/views/components/nav-items.php
git commit -m "feat(sidebar): group modules into 4 macro-areas with accordion"
```

---

### Task 4: Restructure Admin Navigation — Wrap in Groups

**Files:**
- Modify: `shared/views/components/nav-items.php` (lines 749-776 area — the admin section)

**Step 1: Replace the admin section**

Replace the existing flat admin section (from `<?php if ($isAdmin): ?>` to the closing `<?php endif; ?>`) with:

```php
<?php if ($isAdmin): ?>
<div class="mt-6">
    <?php foreach ($navAdminGroups as $adminGroupKey => $adminGroup): ?>
        <?= navGroupHeader($adminGroupKey, $adminGroup['label'], $adminGroup['icon'], $_activeAdminGroup === $adminGroupKey) ?>
        <div data-nav-group="<?= $adminGroupKey ?>"
             data-auto-expand="<?= $_activeAdminGroup === $adminGroupKey ? 'true' : 'false' ?>"
             x-show="isGroupOpen('<?= $adminGroupKey ?>')"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-1 ml-1">
                <?php foreach ($adminGroup['links'] as $adminLink): ?>
                    <?= navLink($adminLink['path'], $adminLink['label'], $adminLink['icon'], $currentPath, $adminLink['exact'] ?? false) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
```

**Step 2: Verify PHP syntax**

Run: `php -l shared/views/components/nav-items.php`
Expected: `No syntax errors detected`

**Step 3: Visual test in browser**

Open: `http://localhost/seo-toolkit/admin`

Verify:
- 3 admin group headers visible: UTENTI & PIANI, CONFIGURAZIONE, MONITORAGGIO
- Click each header toggles links
- Active group auto-expands
- All admin links work with their individual icons

**Step 4: Commit**

```bash
git add shared/views/components/nav-items.php
git commit -m "feat(sidebar): group admin navigation into 3 macro-areas"
```

---

### Task 5: Full Integration Test

**Files:** None (testing only)

**Step 1: Test all module links from sidebar**

Navigate to each module landing page and verify:
- [ ] `/seo-tracking` — in AI SEO group, auto-expanded
- [ ] `/keyword-research` — in AI SEO group
- [ ] `/seo-onpage` — in AI SEO group
- [ ] `/ai-content` — in AI CONTENT group, auto-expanded
- [ ] `/content-creator` — in AI CONTENT group
- [ ] `/ai-optimizer` — in AI CONTENT group
- [ ] `/seo-audit` — in AI AUDIT group, auto-expanded
- [ ] `/internal-links` — in AI AUDIT group
- [ ] `/ads-analyzer` — in AI ADS group, auto-expanded

**Step 2: Test project sub-navigation**

Enter a project in at least 2 different modules and verify the sub-nav accordion works:
- Click into a SEO Tracking project → sub-links visible
- Click into an AI Content project → sub-links visible
- Navigate between pages within a project → correct highlighting

**Step 3: Test accordion memory**

- Manually expand AI CONTENT, collapse AI SEO
- Navigate to another page
- Return → groups should remember their state

**Step 4: Test mobile**

- Resize browser to mobile width
- Open hamburger menu
- Verify sidebar groups work in mobile drawer

**Step 5: Test admin groups**

- Log in as admin
- Verify 3 admin groups render
- Verify non-admin user does NOT see admin groups

**Step 6: If issues found, fix and commit**

No code changes expected in this task. If issues found, fix them and commit fixes.
