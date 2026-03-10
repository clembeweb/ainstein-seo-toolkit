# Platform Audit Fixes — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all 11 CRITICAL issues found in the full-platform compliance audit + top WARNING fixes.

**Architecture:** Targeted surgical fixes — each task modifies 1-3 files with minimal blast radius. No refactoring, no feature changes.

**Tech Stack:** PHP 8+, Alpine.js/JavaScript, Tailwind CSS

---

## TASK GROUP A: Security Fixes (CRITICAL)

### Task A1: Core — Disable display_errors in production

**Files:**
- Modify: `public/index.php:7-20`

**Fix:** Replace hardcoded `display_errors=1` with environment-aware setting. Add session cookie security.

```php
// Replace lines 7-12 with:
// Error reporting (display in dev only)
error_reporting(E_ALL);
$isDebug = (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1');
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');

// Session security
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
```

**Verify:** `php -l public/index.php`
**Test browser:** Any page on production should NOT show PHP errors. Inspect session cookie — should have HttpOnly and Secure flags.

---

### Task A2: seo-audit — Fix IDOR in ApiController::importWordPress()

**Files:**
- Modify: `modules/seo-audit/controllers/ApiController.php:259`

**Fix:** Replace raw query with ownership check.

```php
// Replace:
$project = Database::fetch("SELECT * FROM sa_projects WHERE id = ?", [$id]);

// With:
$project = $this->projectModel->findAccessible($id, $user['id']);
```

**Verify:** `php -l modules/seo-audit/controllers/ApiController.php`

---

### Task A3: seo-audit — Fix CSRF mismatch in ActionPlanController

**Files:**
- Modify: `modules/seo-audit/controllers/ActionPlanController.php:105-112,154-161,220-227`

**Fix:** Remove redundant manual CSRF checks (Middleware::csrf() in routes already validates). Remove 3 blocks:

In `generate()` (~line 105-112): Remove the entire `// Verifica CSRF` block.
In `toggleFix()` (~line 154-161): Remove the entire CSRF check block.
In `delete()` (~line 220-227): Remove the entire CSRF check block.

**Verify:** `php -l modules/seo-audit/controllers/ActionPlanController.php`
**Test browser:** On a seo-audit project with crawl data, go to Action Plan tab → click "Genera Piano". Should work (currently returns 403).

---

### Task A4: content-creator — Fix IDOR in Url::approveBulk() and deleteBulk()

**Files:**
- Modify: `modules/content-creator/models/Url.php:447-461,501-511`

**Fix:** Add `project_id` constraint to both methods.

```php
// approveBulk — change signature and query:
public function approveBulk(array $ids, int $projectId): int
{
    if (empty($ids)) return 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $this->db->prepare("
        UPDATE {$this->table}
        SET status = 'approved'
        WHERE id IN ({$placeholders}) AND project_id = ? AND status IN ('generated', 'error')
    ");
    $params = array_merge($ids, [$projectId]);
    $stmt->execute($params);
    return $stmt->rowCount();
}

// deleteBulk — same pattern:
public function deleteBulk(array $ids, int $projectId): int
{
    if (empty($ids)) return 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id IN ({$placeholders}) AND project_id = ?");
    $params = array_merge($ids, [$projectId]);
    $stmt->execute($params);
    return $stmt->rowCount();
}
```

Then update callers in `UrlController` to pass `$projectId`:
- `bulkApprove()`: `$this->urlModel->approveBulk($urlIds, $project['id'])`
- `bulkDelete()`: `$this->urlModel->deleteBulk($urlIds, $project['id'])`

**Verify:** `php -l modules/content-creator/models/Url.php && php -l modules/content-creator/controllers/UrlController.php`

---

## TASK GROUP B: ob_start/ob_end_clean Fixes (CRITICAL)

### Task B1: ai-content — Add ob_start() to SerpController::extract()

**Files:**
- Modify: `modules/ai-content/controllers/SerpController.php:32-102`

**Fix:** Add `ob_start()` after `set_time_limit(60)` at line 35, add `ob_end_clean()` before each `echo json_encode()`.

```php
public function extract(int $id): void
{
    ignore_user_abort(true);
    set_time_limit(60);
    ob_start();
    header('Content-Type: application/json');
    // ... existing code ...
    // Before EVERY echo json_encode():
    ob_end_clean();
    echo json_encode([...]);
    exit;
}
```

There are 5 echo points: lines 43, 48, 55, 84, 96. Each needs `ob_end_clean();` before it.

**Verify:** `php -l modules/ai-content/controllers/SerpController.php`

---

### Task B2: keyword-research — Add ob_end_clean() to ResearchController early returns

**Files:**
- Modify: `modules/keyword-research/controllers/ResearchController.php:310-355`

**Fix:** Add `ob_end_clean();` before each early `echo json_encode()` and change `return` to `exit`.

Lines to fix: 311, 317, 325, 334, 351, ~404 (each early return). Pattern:

```php
// Before (broken):
echo json_encode(['success' => false, 'error' => '...']);
return;

// After (fixed):
ob_end_clean();
echo json_encode(['success' => false, 'error' => '...']);
exit;
```

**Verify:** `php -l modules/keyword-research/controllers/ResearchController.php`

---

### Task B3: seo-tracking — Fix ob_start without ob_end_clean in KeywordController

**Files:**
- Modify: `modules/seo-tracking/controllers/KeywordController.php`

**Fix:** In `refreshPositions()` and `refreshAll()`, add `ob_end_clean()` before each `return View::json()`.

Pattern for ALL `return View::json()` calls inside these two methods:

```php
// Before:
return View::json([...]);

// After:
ob_end_clean();
echo json_encode([...]);
exit;
```

Affected locations in `refreshPositions()`: ~lines 1040, 1049, 1183 + any other early returns.
Affected locations in `refreshAll()`: ~lines 1210, 1219, 1381 + any other early returns.

**Verify:** `php -l modules/seo-tracking/controllers/KeywordController.php`

---

## TASK GROUP C: Missing Long-Op Protection (CRITICAL)

### Task C1: seo-audit — Add ignore_user_abort to CrawlController::start()

**Files:**
- Modify: `modules/seo-audit/controllers/CrawlController.php:46-48`

**Fix:** Add protection after `ob_start()`:

```php
public function start(int $id): void
{
    ignore_user_abort(true);
    set_time_limit(300);
    ob_start();
    // ... rest of method
```

**Verify:** `php -l modules/seo-audit/controllers/CrawlController.php`

---

### Task C2: seo-tracking — Add long-op protection to GscController::sync()

**Files:**
- Modify: `modules/seo-tracking/controllers/GscController.php:239-280`

**Fix:** Add protection at top of `sync()`:

```php
public function sync(int $id): void
{
    ignore_user_abort(true);
    set_time_limit(300);
    ob_start();
    header('Content-Type: application/json');

    // ... existing code ...

    // Before EVERY jsonResponse() or echo, add:
    // ob_end_clean();
```

For the early returns (lines 245, 251, 255) that call `$this->jsonResponse()`, add `ob_end_clean();` before each.
For the final response block (~line 270), add `ob_end_clean();` before `$this->jsonResponse()`.

**Verify:** `php -l modules/seo-tracking/controllers/GscController.php`

---

### Task C3: seo-tracking — Add long-op protection to AiController::analyzePage()

**Files:**
- Modify: `modules/seo-tracking/controllers/AiController.php:384-449`

**Fix:** Add protection at top of `analyzePage()`:

```php
public function analyzePage(int $projectId): string
{
    ignore_user_abort(true);
    set_time_limit(300);
    ob_start();
    header('Content-Type: application/json');

    // ... existing validation code stays as-is ...

    // For early returns (View::json calls at lines 390, 398, 403, 414):
    // These are short validation checks BEFORE the long op — leave as View::json but add ob_end_clean()

    // After the long $this->pageAnalyzer->analyze() call:
    Database::reconnect();

    // Replace the final return View::json() with:
    ob_end_clean();
    echo json_encode([...]);
    exit;
}
```

Since `analyzePage` returns `string`, change return type to `void` and replace all `return View::json()` with `ob_end_clean(); echo json_encode(); exit;`.

**Verify:** `php -l modules/seo-tracking/controllers/AiController.php`

---

## TASK GROUP D: Content-Creator Redirect Fix (CRITICAL)

### Task D1: content-creator — Add exit after redirect in GeneratorController::results()

**Files:**
- Modify: `modules/content-creator/controllers/GeneratorController.php:816-817`

**Fix:**

```php
// Replace:
Router::redirect('/content-creator');

// With:
Router::redirect('/content-creator');
exit;
```

**Verify:** `php -l modules/content-creator/controllers/GeneratorController.php`

---

## TASK GROUP E: Top WARNING Fixes (batch)

### Task E1: Cross-module — Standardize CSRF field name to `_csrf_token`

**Files to modify (replace `_token` with `_csrf_token` in JS POST bodies):**
- `modules/seo-audit/views/audit/action-plan.php`: lines 371, 397
- `modules/seo-audit/views/audit/pages.php`: all `_token` occurrences
- `modules/ai-content/views/keywords/wizard.php`: lines 923, 1005, 1069, 1144
- `modules/ai-content/views/keywords/serp-results.php`: line 263
- `modules/ai-content/views/articles/index.php`: lines 435, 480
- `modules/ai-content/views/articles/show.php`: lines 800, 842, 880
- `modules/ai-content/views/wordpress/index.php`: lines 437, 478, 516, 552, 592

**Fix pattern:** `_token:` → `_csrf_token:` in all JS objects.

---

### Task E2: Cross-module — Add response.ok checks to AJAX calls

**Pattern to apply in all affected views:**

```javascript
// Before:
const data = await response.json();

// After:
if (!response.ok) {
    const text = await response.text();
    throw new Error(text || `HTTP ${response.status}`);
}
const data = await response.json();
```

**Key files (highest impact):**
- `modules/ads-analyzer/views/campaigns/dashboard.php:596`
- `modules/ads-analyzer/views/campaigns/search-terms.php:450,537`
- `modules/seo-audit/views/audit/pages.php:355,389,476,504`
- `modules/seo-audit/views/audit/action-plan.php:374,400`

---

## Execution Sequence

| Phase | Tasks | Parallel? | Estimated |
|-------|-------|-----------|-----------|
| 1 — Security | A1, A2, A3, A4 | Yes (4 agents) | 5 min |
| 2 — ob_start | B1, B2, B3 | Yes (3 agents) | 5 min |
| 3 — Long-op | C1, C2, C3, D1 | Yes (4 agents) | 5 min |
| 4 — Warnings | E1, E2 | Yes (2 agents) | 5 min |
| 5 — Deploy + Test | git commit, push, pull on VPS, browser test | Sequential | 10 min |

**Total: ~30 min with parallel agents**

---

## Browser Test Plan (Production)

After deploy, verify on https://ainstein.it with admin@seo-toolkit.local:

1. **Core security**: Inspect session cookie → must have HttpOnly+Secure. Trigger PHP error → must NOT display on page.
2. **seo-audit Action Plan**: Go to any seo-audit project with crawl data → Action Plan → "Genera Piano" → must work (not 403).
3. **seo-audit Crawl**: Start a crawl → must complete (ignore_user_abort protection).
4. **seo-tracking Refresh**: Go to a seo-tracking project → "Aggiorna posizioni" → must return valid JSON.
5. **seo-tracking GSC Sync**: Go to GSC-connected project → "Sincronizza" → must complete.
6. **seo-tracking AI Analysis**: Analyze a page → must complete without timeout.
7. **keyword-research AI**: Start a research → AI cluster analysis → must return error messages properly on validation failures.
8. **ai-content SERP**: Extract SERP for a keyword → must return valid JSON.
9. **content-creator bulk**: Select URLs → bulk approve/delete → must only affect current project's URLs.
