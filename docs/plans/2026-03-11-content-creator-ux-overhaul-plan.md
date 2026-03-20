# Content Creator UX Overhaul — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix critical bugs, improve UX flows, enforce visual consistency, and add the educational landing page to the content-creator module.

**Architecture:** Five incremental phases — each phase produces a working, testable module. Phase 1 fixes bugs that break user flows. Phase 2 improves UX logic (status transitions, missing tabs, CMS push). Phase 3 enforces visual consistency (colors, CSS classes). Phase 4 adds the 7-section educational landing page. Phase 5 adds nice-to-have improvements.

**Tech Stack:** PHP 8+, Tailwind CSS, Alpine.js, MySQL — following Ainstein Golden Rules and existing module patterns.

---

## Chunk 1: Critical Bug Fixes

### Task 1: Fix double redirect on project creation errors

The `store()` method redirects errors to `/content-creator/projects/create`, which then redirects again to `/projects/create` (global hub), losing flash messages in the double redirect.

**Files:**
- Modify: `modules/content-creator/controllers/ProjectController.php:82-114`

- [ ] **Step 1: Fix error redirect in store()**

Change both redirect targets from `/content-creator/projects/create` to `/projects/create` (the actual destination users reach):

```php
// Line 84: change from
Router::redirect('/content-creator/projects/create');
// to
Router::redirect('/projects/create');

// Line 114: change from
Router::redirect('/content-creator/projects/create');
// to
Router::redirect('/projects/create');
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l modules/content-creator/controllers/ProjectController.php`
Expected: No syntax errors detected

- [ ] **Step 3: Test manually**

1. Go to `/projects/create`, select Content Creator
2. Submit empty form → should redirect back with error flash visible
3. Submit with valid data → should create project

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/controllers/ProjectController.php
git commit -m "fix(content-creator): redirect errors to /projects/create to avoid double redirect losing flash messages"
```

---

### Task 2: Add `response.ok` check in connectors view (GR #24)

Three AJAX calls in `connectors/index.php` call `resp.json()` without checking `resp.ok` first — will silently fail on 4xx/5xx.

**Files:**
- Modify: `modules/content-creator/views/connectors/index.php:224,255,292`

- [ ] **Step 1: Fix testConnector() — line 224**

```javascript
// Replace:
const data = await resp.json();
// With:
if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
const data = await resp.json();
```

- [ ] **Step 2: Fix toggleConnector() — line 255**

```javascript
// Replace:
const data = await resp.json();
// With:
if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
const data = await resp.json();
```

- [ ] **Step 3: Fix deleteConnector() — line 292**

```javascript
// Replace:
const data = await resp.json();
// With:
if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
const data = await resp.json();
```

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/views/connectors/index.php
git commit -m "fix(content-creator): add response.ok check before .json() in connector AJAX calls (GR #24)"
```

---

### Task 3: Add missing 'service' content type in project-nav

The `$contentTypes` map in `project-nav.php` is missing `'service'`, while `ProjectController::store()` accepts it at line 78. URLs with type 'service' fall back to 'product' silently.

**Files:**
- Modify: `modules/content-creator/views/partials/project-nav.php:40-45`

- [ ] **Step 1: Add 'service' type**

```php
// Replace lines 40-45:
$contentTypes = [
    'product' => ['label' => 'Prodotto', 'color' => 'teal'],
    'category' => ['label' => 'Categoria', 'color' => 'blue'],
    'article' => ['label' => 'Articolo', 'color' => 'purple'],
    'custom' => ['label' => 'Custom', 'color' => 'slate'],
];
// With:
$contentTypes = [
    'product' => ['label' => 'Prodotto', 'color' => 'teal'],
    'category' => ['label' => 'Categoria', 'color' => 'blue'],
    'article' => ['label' => 'Articolo', 'color' => 'purple'],
    'service' => ['label' => 'Servizio', 'color' => 'orange'],
    'custom' => ['label' => 'Custom', 'color' => 'slate'],
];
```

- [ ] **Step 2: Commit**

```bash
git add modules/content-creator/views/partials/project-nav.php
git commit -m "fix(content-creator): add missing 'service' content type in project-nav"
```

---

## Chunk 2: UX Flow Improvements

### Task 4: Allow re-approve from 'rejected' status

The `approve()` and `approveBulk()` methods in `Url.php` only accept `'generated'` and `'error'` — they reject items with `'rejected'` status, making the rejection irreversible. The `reject()` method correctly allows from `'approved'` (bidirectional), but approve doesn't allow from `'rejected'`.

**Files:**
- Modify: `modules/content-creator/models/Url.php:419-428,447-459`

- [ ] **Step 1: Fix approve() to include 'rejected'**

```php
// Line 424: change from
WHERE id = ? AND status IN ('generated', 'error')
// to
WHERE id = ? AND status IN ('generated', 'error', 'rejected')
```

- [ ] **Step 2: Fix approveBulk() to include 'rejected'**

```php
// Line 454: change from
WHERE id IN ({$placeholders}) AND project_id = ? AND status IN ('generated', 'error')
// to
WHERE id IN ({$placeholders}) AND project_id = ? AND status IN ('generated', 'error', 'rejected')
```

- [ ] **Step 3: Verify PHP syntax**

Run: `php -l modules/content-creator/models/Url.php`
Expected: No syntax errors detected

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/models/Url.php
git commit -m "fix(content-creator): allow re-approve from 'rejected' status (bidirectional status flow)"
```

---

### Task 5: Show approve/reject buttons for more statuses in views

Currently approve/reject buttons only show for `status === 'generated'`. They should also show for `'rejected'` (re-approve) and `'approved'` (revoke approval).

**Files:**
- Modify: `modules/content-creator/views/urls/preview.php:306`
- Modify: `modules/content-creator/views/results/index.php:444`
- Modify: `modules/content-creator/views/projects/show.php` (if inline approve exists)

- [ ] **Step 1: Fix preview.php — show buttons for generated, rejected, approved**

Replace the single `if ($status === 'generated')` block (line 306) with conditional buttons:

```php
<?php if (in_array($status, ['generated', 'rejected', 'error'])): ?>
<!-- Approve -->
<form action="<?= url("/content-creator/projects/{$projectId}/urls/{$urlId}/approve") ?>" method="POST" class="inline">
    <?= csrf_field() ?>
    <button type="submit"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Approva
    </button>
</form>
<?php endif; ?>

<?php if (in_array($status, ['generated', 'approved', 'error'])): ?>
<!-- Reject -->
<form action="<?= url("/content-creator/projects/{$projectId}/urls/{$urlId}/reject") ?>" method="POST" class="inline">
    <?= csrf_field() ?>
    <button type="submit"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Rifiuta
    </button>
</form>
<?php endif; ?>
```

- [ ] **Step 2: Fix results/index.php — expand inline action buttons**

At line 444, replace:
```php
<?php if ($itemStatus === 'generated'): ?>
```
With:
```php
<?php if (in_array($itemStatus, ['generated', 'rejected', 'error'])): ?>
```

And add a reject button for `'approved'` status after the approve block (around line 460):
```php
<?php endif; ?>
<?php if (in_array($itemStatus, ['generated', 'approved'])): ?>
<!-- Rifiuta rapido -->
<button type="button"
        @click="rejectOne(<?= $item['id'] ?>)"
        class="p-2 rounded-lg text-slate-500 hover:text-orange-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        title="Rifiuta">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>
<?php endif; ?>
```

Note: Read the full current block structure at lines 444-465 before editing to avoid duplicating the existing reject button.

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/views/urls/preview.php modules/content-creator/views/results/index.php
git commit -m "feat(content-creator): show approve/reject buttons for all applicable statuses"
```

---

### Task 6: Add 'rejected' tab in dashboard status filters

The dashboard `show.php` lists status tabs but omits 'rejected'.

**Files:**
- Modify: `modules/content-creator/views/projects/show.php:48-56`

- [ ] **Step 1: Add rejected tab between approved and published**

```php
$statusTabs = [
    '' => ['label' => 'Tutti', 'count' => $stats['total']],
    'pending' => ['label' => 'In Attesa', 'count' => $stats['pending']],
    'scraped' => ['label' => 'Scrappate', 'count' => $stats['scraped']],
    'generated' => ['label' => 'Generate', 'count' => $stats['generated']],
    'approved' => ['label' => 'Approvate', 'count' => $stats['approved']],
    'rejected' => ['label' => 'Rifiutate', 'count' => $stats['rejected'] ?? 0],
    'published' => ['label' => 'Pubblicate', 'count' => $stats['published']],
    'error' => ['label' => 'Errori', 'count' => $stats['errors']],
];
```

- [ ] **Step 2: Ensure controller provides 'rejected' count in stats**

Check `ProjectController::show()` — if `$stats` comes from `Url::getStatsByProject()`, verify it includes a 'rejected' count. If not, add it.

Reference: look at how the model computes stats. The query likely groups by status — 'rejected' should already be in the result if any URLs have that status, but the controller might not pass it.

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/views/projects/show.php
git commit -m "feat(content-creator): add 'rejected' tab in dashboard status filters"
```

---

### Task 7: Add CMS Push button in results view

The CMS push backend exists (`ExportController::startPushJob`, `pushStream`) with routes, but NO view exposes the button. Users have no way to trigger CMS publishing from the UI.

**Files:**
- Modify: `modules/content-creator/views/results/index.php` (add push button + SSE progress)
- Reference: `modules/content-creator/controllers/ExportController.php` (routes and API)

- [ ] **Step 1: Read the full results/index.php to understand the toolbar area**

Read lines 1-100 of `modules/content-creator/views/results/index.php` to find the toolbar where CSV export exists. The CMS push button should go next to it.

- [ ] **Step 2: Add CMS Push button in the toolbar**

Near the CSV export button, add (only if project has a `connector_id`):

```php
<?php if (!empty($project['connector_id'])): ?>
<button type="button"
        @click="startCmsPush()"
        :disabled="pushing"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 disabled:opacity-50 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
    </svg>
    <span x-text="pushing ? 'Pubblicazione...' : 'Pubblica su CMS'"></span>
</button>
<?php endif; ?>
```

- [ ] **Step 3: Add Alpine.js push logic in the component**

Add these methods to the Alpine component:

```javascript
pushing: false,
pushJobId: null,

async startCmsPush() {
    if (!confirm('Pubblicare tutte le URL approvate sul CMS?')) return;
    this.pushing = true;

    try {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        const resp = await fetch(`<?= url("/content-creator/projects/{$project['id']}/push/start") ?>`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
        const data = await resp.json();

        if (data.error) {
            this.showNotification('error', data.message);
            this.pushing = false;
            return;
        }

        this.pushJobId = data.job_id;
        this.listenPushStream(data.job_id);
    } catch (err) {
        this.showNotification('error', err.message || 'Errore avvio push');
        this.pushing = false;
    }
},

listenPushStream(jobId) {
    const es = new EventSource(`<?= url("/content-creator/projects/{$project['id']}/push/stream") ?>?job_id=${jobId}`);

    es.addEventListener('item_completed', (e) => {
        const d = JSON.parse(e.data);
        this.showNotification('success', `Pubblicata: ${d.url}`);
    });

    es.addEventListener('item_error', (e) => {
        const d = JSON.parse(e.data);
        this.showNotification('error', `Errore: ${d.url} — ${d.error}`);
    });

    es.addEventListener('completed', (e) => {
        const d = JSON.parse(e.data);
        es.close();
        this.pushing = false;
        this.showNotification('success', `Push completato: ${d.completed} pubblicati, ${d.failed} errori`);
        setTimeout(() => location.reload(), 2000);
    });

    es.addEventListener('cancelled', () => {
        es.close();
        this.pushing = false;
        this.showNotification('info', 'Push annullato');
    });

    es.onerror = () => {
        es.close();
        this.pushing = false;
        this.showNotification('error', 'Connessione SSE persa');
    };
},
```

- [ ] **Step 4: Verify approved count is visible**

The button should only be meaningful when there are approved URLs. Consider adding a tooltip or disabling when `$stats['approved'] === 0`.

- [ ] **Step 5: Commit**

```bash
git add modules/content-creator/views/results/index.php
git commit -m "feat(content-creator): add CMS Push button with SSE progress in results view"
```

---

## Chunk 3: Visual Consistency

### Task 8: Fix teal→orange color in module views

Content Creator's module color is `orange` (confirmed in `GlobalProject.php`), but several views use `teal` for accents, badges, and buttons.

**Files:**
- Modify: `modules/content-creator/views/projects/show.php` — teal badges/buttons → orange
- Modify: `modules/content-creator/views/results/index.php` — teal approve buttons → orange accent
- Modify: `modules/content-creator/views/urls/preview.php` — teal approve button → orange
- Modify: `modules/content-creator/views/connectors/index.php` — check for teal

**Note:** Approve buttons can stay teal/green (semantic: success action). The module color `orange` should be used for module-level accents (badges, headers, empty states, info boxes). The reject button already uses orange which is correct.

- [ ] **Step 1: Search for teal usage across content-creator views**

Run: `grep -rn "teal" modules/content-creator/views/`

Identify which usages are module-color (should be orange) vs semantic-approve (can stay teal).

- [ ] **Step 2: Replace module-level teal with orange**

Focus on:
- Empty state icons/borders
- Info boxes
- Module badges in project-nav
- Status highlights specific to the module (NOT approve buttons)

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/views/
git commit -m "style(content-creator): use module color orange for accents instead of teal"
```

---

### Task 9: Fix rounded-2xl → rounded-xl (GR #20)

Two views use `rounded-2xl` instead of the standard `rounded-xl`.

**Files:**
- Modify: `modules/content-creator/views/projects/create.php:17` — `rounded-2xl` → `rounded-xl`
- Modify: `modules/content-creator/views/connectors/create.php:17` — `rounded-2xl` → `rounded-xl`

- [ ] **Step 1: Fix both files**

Search and replace `rounded-2xl` → `rounded-xl` in both files.

- [ ] **Step 2: Commit**

```bash
git add modules/content-creator/views/projects/create.php modules/content-creator/views/connectors/create.php
git commit -m "style(content-creator): fix rounded-2xl to rounded-xl per GR #20"
```

---

### Task 10: Improve CSV import labels

The import wizard uses technical jargon like "0-based" and "-1 = nessuna" for column mapping.

**Files:**
- Modify: `modules/content-creator/views/urls/import.php`

- [ ] **Step 1: Find and replace technical labels**

Search for "0-based" and "-1" labels in the import view. Replace with user-friendly Italian text:

- "Colonna 0-based" → "Numero colonna (1 = prima colonna)"
- "-1 = nessuna" → "Lascia vuoto per ignorare"
- Or use `<select>` dropdowns instead of numeric inputs if feasible

- [ ] **Step 2: Commit**

```bash
git add modules/content-creator/views/urls/import.php
git commit -m "ux(content-creator): improve CSV import labels — remove technical jargon"
```

---

## Chunk 4: Landing Educational Page

### Task 11: Add 7-section educational landing page

The `index.php` only has a basic hero banner and project list. It should have the full 7-section educational landing page like `keyword-research/views/dashboard.php`.

**Reference:** `modules/keyword-research/views/dashboard.php` (lines 150-700)

**Files:**
- Modify: `modules/content-creator/views/projects/index.php` — append after project list

- [ ] **Step 1: Read the keyword-research landing page as reference**

Read: `modules/keyword-research/views/dashboard.php` lines 150-700 to understand the 7-section structure:
1. Separator divider
2. Hero educativo (2-col grid)
3. Come funziona (3-step cards)
4. Feature blocks (3x alternating white/slate)
5. Cosa puoi fare (6-card grid)
6. FAQ accordion (Alpine.js)
7. CTA finale (gradient)

- [ ] **Step 2: Create the 7-section content**

Append after the project list in `index.php`, using module color `orange`. Content topics:

**Come funziona (3 steps):**
1. Importa URL — CSV, sitemap, CMS o manuale
2. Genera con AI — Contenuti HTML ottimizzati per SEO
3. Pubblica — Approva e invia al CMS con un click

**Feature blocks (3x):**
1. Generazione bulk intelligente — AI analizza ogni pagina e genera contenuto su misura
2. 4 connettori CMS — WordPress, Shopify, PrestaShop, Magento
3. Workflow completo — Scraping → Generazione → Revisione → Pubblicazione

**Cosa puoi fare (6 cards):**
1. Descrizioni prodotto in massa
2. Meta tag ottimizzati SEO
3. Articoli blog generati da keyword
4. Landing page per servizi
5. Contenuti categorie e-commerce
6. Pubblicazione diretta su CMS

**FAQ (5-6 domande):**
- Quanti contenuti posso generare alla volta?
- Quali CMS sono supportati?
- Posso personalizzare il tono dei contenuti?
- Come funziona il sistema di approvazione?
- Quanto costano i crediti per la generazione?
- Il contenuto generato e SEO-friendly?

**CTA finale:** "Pronto a generare contenuti in massa?" → `/projects/create`

- [ ] **Step 3: Verify dark mode compatibility**

All sections must use `dark:` variants for backgrounds, text, and borders.

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/views/projects/index.php
git commit -m "feat(content-creator): add 7-section educational landing page (Scopri cosa puoi fare)"
```

---

## Chunk 5: Nice-to-have Improvements

### Task 12: Add Keyword Research import tab in import wizard

The `UrlController::importFromKR()` method exists (line 715) but no UI tab exposes it. Other modules use `import-tabs` shared component.

**Files:**
- Modify: `modules/content-creator/views/urls/import.php` — add 5th tab "Da Keyword Research"
- Reference: `modules/keyword-research/controllers/EditorialController.php` for cross-module export pattern

- [ ] **Step 1: Read the current import tabs structure**

Read `modules/content-creator/views/urls/import.php` lines 1-50 to understand the tab system (Alpine.js `x-data` with `activeTab`).

- [ ] **Step 2: Add 5th tab header**

Add a "Keyword Research" tab button after the Manual tab:

```html
<button @click="activeTab = 'kr'"
        :class="activeTab === 'kr' ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-500'"
        class="px-4 py-2 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
    <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
    </svg>
    Keyword Research
</button>
```

- [ ] **Step 3: Add tab content panel**

```html
<div x-show="activeTab === 'kr'" x-cloak>
    <form action="<?= url("/content-creator/projects/{$project['id']}/urls/import/kr") ?>" method="POST">
        <?= csrf_field() ?>
        <div class="space-y-4">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Importa URL dal modulo Keyword Research. Seleziona un progetto KR collegato.
            </p>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Progetto Keyword Research
                </label>
                <select name="kr_project_id" required
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Seleziona progetto...</option>
                    <?php foreach ($krProjects ?? [] as $krp): ?>
                    <option value="<?= $krp['id'] ?>"><?= e($krp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors">
                Importa URL
            </button>
        </div>
    </form>
</div>
```

- [ ] **Step 4: Pass KR projects from controller**

In `UrlController::importForm()`, add KR projects to the view data:

```php
// After existing data, add:
$krProjects = [];
try {
    $krProjectModel = new \Modules\KeywordResearch\Models\Project();
    $krProjects = $krProjectModel->allByUser($user['id']);
} catch (\Exception $e) {
    // Module might not be active
}

// Pass to view:
'krProjects' => $krProjects,
```

- [ ] **Step 5: Commit**

```bash
git add modules/content-creator/views/urls/import.php modules/content-creator/controllers/UrlController.php
git commit -m "feat(content-creator): add Keyword Research import tab in URL import wizard"
```

---

### Task 13: Clean up orphaned create.php view

The `create.php` view exists but is never reached (GET route redirects to global hub). It should be deleted to avoid confusion.

**Files:**
- Delete: `modules/content-creator/views/projects/create.php`

- [ ] **Step 1: Verify no other route/controller references this view**

Run: `grep -rn "content-creator/projects/create" modules/content-creator/`

Confirm only the redirect route and the `ProjectController::create()` reference it. Since the route redirects before reaching the controller method, the view is dead code.

- [ ] **Step 2: Delete the orphaned view**

```bash
rm modules/content-creator/views/projects/create.php
```

- [ ] **Step 3: Remove the create() method from ProjectController**

Since `create()` (lines 41-53) is never called, remove it to avoid confusion. Keep `store()` which IS used.

- [ ] **Step 4: Commit**

```bash
git add -A modules/content-creator/
git commit -m "chore(content-creator): remove orphaned create.php view and unused create() controller method"
```

---

## Summary of Changes

| Phase | Tasks | Impact |
|-------|-------|--------|
| Chunk 1: Critical Fixes | Tasks 1-3 | Fix broken flows, GR compliance |
| Chunk 2: UX Flow | Tasks 4-7 | Bidirectional status, CMS push UI |
| Chunk 3: Visual | Tasks 8-10 | Color consistency, CSS standards |
| Chunk 4: Landing | Task 11 | Educational content, marketing |
| Chunk 5: Nice-to-have | Tasks 12-13 | KR import, cleanup |

**Files touched:** ~12 files across controllers, models, and views
**Estimated total:** 13 tasks, ~45 steps
