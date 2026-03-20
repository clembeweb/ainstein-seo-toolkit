# Platform Audit Fix Plan — Ainstein SEO Toolkit

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all bugs, security issues, inconsistencies, and improvements found during comprehensive platform audit across all active production modules.

**Architecture:** Organized in 6 tiers by severity. Each tier is independent. Within each tier, tasks are ordered by impact.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js, HTMX

**Scope:** ALL active production modules: ai-content, seo-audit, seo-tracking, ads-analyzer, keyword-research, content-creator + core framework, services, admin, shared views, auth/profile, global projects.

---

## Tier 1 — CRITICAL Security & Data (IDOR, XSS, CSRF)

### Task 1.1: Fix IDOR vulnerabilities in ai-content ArticleController

**Files:**
- Modify: `modules/ai-content/controllers/ArticleController.php`

**Context:** `find($id)` without user ownership check allows any authenticated user to update/delete/view another user's articles.

**Step 1: Fix update() — add ownership check**

In `ArticleController.php`, the `update()` method around line 336:

```php
// BEFORE (vulnerable):
$article = $this->article->find($id);
if (!$article) {

// AFTER (secure):
$article = $this->article->findByUser($id, $user['id']);
if (!$article) {
```

Apply the same pattern to ALL these methods:
- `update(int $id)` ~line 336
- `delete(int $id)` ~line 403 — ALSO fix null dereference: move `$projectId = $article['project_id']` AFTER the null check
- `regenerate(int $id)`
- `resetStatus(int $id)`
- `removeCover(int $id)`
- `serveCover(int $id)`
- `show(int $id)` (if exists)

If `findByUser()` doesn't exist on the Article model, use:
```php
$article = Database::fetch("SELECT * FROM aic_articles WHERE id = ? AND user_id = ?", [$id, $user['id']]);
```

Or verify via project ownership:
```php
$article = $this->article->find($id);
if (!$article) { /* 404 */ }
$project = Project::findAccessible($user['id'], $article['project_id']);
if (!$project) { /* 403 */ }
```

**Step 2: Fix delete() null dereference**

```php
// BEFORE (line ~408-414):
$projectId = $article['project_id'] ?? null;  // ← accessed BEFORE null check
if (!$article) {

// AFTER:
if (!$article) {
    $_SESSION['_flash']['error'] = 'Articolo non trovato';
    Router::redirect('/ai-content/projects');
}
$projectId = $article['project_id'] ?? null;  // ← AFTER null check
```

**Step 3: Run `php -l` on modified file**

```bash
php -l modules/ai-content/controllers/ArticleController.php
```

**Step 4: Commit**

```bash
git add modules/ai-content/controllers/ArticleController.php
git commit -m "fix(ai-content): add ownership checks to ArticleController (IDOR fix)"
```

---

### Task 1.2: Fix IDOR in ai-content SerpController and WizardController

**Files:**
- Modify: `modules/ai-content/controllers/SerpController.php`
- Modify: `modules/ai-content/controllers/WizardController.php`

**Step 1: Fix SerpController::analyze() ~line 39**

```php
// BEFORE:
$keyword = Keyword::find($id);

// AFTER — verify ownership through project:
$keyword = Keyword::find($id);
if (!$keyword) { jsonResponse(['error' => 'Keyword non trovata'], 404); }
$project = Project::findAccessible($user['id'], $keyword['project_id']);
if (!$project) { jsonResponse(['error' => 'Non autorizzato'], 403); }
```

**Step 2: Fix WizardController ~line 55**

```php
// BEFORE:
$keyword = Keyword::find($keywordId);

// AFTER:
$keyword = Keyword::find($keywordId);
if (!$keyword) { jsonResponse(['error' => 'Keyword non trovata'], 404); }
$project = Project::findAccessible($user['id'], $keyword['project_id']);
if (!$project) { jsonResponse(['error' => 'Non autorizzato'], 403); }
```

**Step 3: Run syntax check + commit**

```bash
php -l modules/ai-content/controllers/SerpController.php
php -l modules/ai-content/controllers/WizardController.php
git add modules/ai-content/controllers/SerpController.php modules/ai-content/controllers/WizardController.php
git commit -m "fix(ai-content): add ownership checks to Serp and Wizard controllers (IDOR fix)"
```

---

### Task 1.3: Fix missing CSRF on content-creator ConnectorController

**Files:**
- Modify: `modules/content-creator/controllers/ConnectorController.php`

**Context:** ALL POST methods in ConnectorController have zero CSRF protection. Any of these endpoints can be exploited via CSRF attack.

**Step 1: Add `Middleware::csrf()` to every POST method**

Add `use Core\Middleware;` at the top if missing, then add `Middleware::csrf();` as the first line of:
- `store()`
- `update()`
- `delete()`
- `testConnection()`
- `sync()`
- Any other POST handler

```php
public function store(): void
{
    Middleware::csrf();  // ← ADD THIS
    // ... existing code
}
```

**Step 2: Run syntax check + commit**

```bash
php -l modules/content-creator/controllers/ConnectorController.php
git add modules/content-creator/controllers/ConnectorController.php
git commit -m "fix(content-creator): add CSRF protection to ConnectorController"
```

---

### Task 1.4: Fix missing CSRF on content-creator approve/reject routes

**Files:**
- Modify: `modules/content-creator/routes.php`

**Context:** Lines ~156-166, approve/reject POST routes have no CSRF middleware.

**Step 1: Add CSRF middleware**

```php
// BEFORE:
Router::post('/content-creator/projects/{id}/approve/{contentId}', function ($id, $contentId) {
    Middleware::auth();
    // ...

// AFTER:
Router::post('/content-creator/projects/{id}/approve/{contentId}', function ($id, $contentId) {
    Middleware::auth();
    Middleware::csrf();
    // ...
```

Apply to both approve and reject routes.

**Step 2: Commit**

```bash
php -l modules/content-creator/routes.php
git add modules/content-creator/routes.php
git commit -m "fix(content-creator): add CSRF to approve/reject routes"
```

---

### Task 1.5: Fix missing CSRF on ads-analyzer POST routes

**Files:**
- Modify: `modules/ads-analyzer/routes.php`

**Context:** 5 POST routes missing CSRF (lines ~299, 306, 363, 370, 412): search-term toggle/category toggle, campaign-creator toggle-kw/update-match, and one more.

**Step 1: Add `Middleware::csrf()` to each route**

```php
// Add Middleware::csrf(); after Middleware::auth(); in these routes:
// Line ~299: /search-term-analysis/keywords/{keywordId}/toggle
// Line ~306: /search-term-analysis/categories/{categoryId}/{action}
// Line ~363: /campaign-creator/toggle-kw/{kwId}
// Line ~370: /campaign-creator/update-match/{kwId}
// Line ~412: (check exact route)
```

**Step 2: Ensure frontend sends CSRF token**

These are AJAX toggle routes. Check the corresponding views send CSRF header. If they use `fetch()`, ensure:
```js
headers: { 'X-CSRF-TOKEN': csrfToken }
```

`Middleware::csrf()` checks `X-CSRF-TOKEN` header as fallback.

**Step 3: Commit**

```bash
php -l modules/ads-analyzer/routes.php
git add modules/ads-analyzer/routes.php
git commit -m "fix(ads-analyzer): add CSRF protection to 5 POST routes"
```

---

### Task 1.6: Fix XSS in auth reset-password view

**Files:**
- Modify: `shared/views/auth/reset-password.php`

**Context:** Line 17 — `$error` displayed without escaping.

**Step 1: Escape the error output**

```php
// BEFORE:
<?= $error ?>

// AFTER:
<?= e($error) ?>
```

**Step 2: Commit**

```bash
php -l shared/views/auth/reset-password.php
git add shared/views/auth/reset-password.php
git commit -m "fix(auth): escape error output in reset-password (XSS fix)"
```

---

### Task 1.7: Fix XSS risk in admin email settings

**Files:**
- Modify: `admin/controllers/AdminController.php`

**Context:** Lines ~530-537 — `email_logo_url` and `email_footer_text` not validated/sanitized.

**Step 1: Validate email_logo_url**

```php
// After receiving email_logo_url:
$logoUrl = trim($_POST['email_logo_url'] ?? '');
if ($logoUrl && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
    $_SESSION['_flash']['error'] = 'URL logo non valido';
    Router::redirect('/admin/settings');
}
```

**Step 2: Sanitize email_footer_text**

```php
$footerText = strip_tags(trim($_POST['email_footer_text'] ?? ''), '<a><br><p><strong><em>');
```

**Step 3: Commit**

```bash
php -l admin/controllers/AdminController.php
git add admin/controllers/AdminController.php
git commit -m "fix(admin): validate email_logo_url and sanitize email_footer_text (XSS prevention)"
```

---

### Task 1.8: Fix ai-content KeywordController missing import + IDOR in bulkDelete

**Files:**
- Modify: `modules/ai-content/controllers/KeywordController.php`

**Context:**
- Line 1-13: missing `use Core\Middleware;`
- Line ~403: `bulkDelete()` has no user_id filter
- Line ~407-408: undefined variable `$articles` (should be `$keywords`)

**Step 1: Add missing import**

```php
use Core\Middleware;
```

**Step 2: Fix bulkDelete ownership + variable name**

```php
// Add user ownership check to bulk delete query
// Fix $articles → $keywords (or correct variable name)
```

**Step 3: Commit**

```bash
php -l modules/ai-content/controllers/KeywordController.php
git add modules/ai-content/controllers/KeywordController.php
git commit -m "fix(ai-content): fix KeywordController import, IDOR, and variable name"
```

---

## Tier 2 — HIGH Functional Bugs (Broken Features)

### Task 2.1: Fix ads-analyzer CampaignCreatorController strict comparison bug

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignCreatorController.php`

**Context:** Line 356 — `$kw['project_id'] !== $id` uses strict comparison. DB returns string `"5"`, route parameter is int `5`. Result: `!==` always true → toggleKeyword always returns 404.

**Step 1: Fix comparison**

```php
// BEFORE (line 356):
if (!$kw || $kw['project_id'] !== $id) {

// AFTER:
if (!$kw || (int)$kw['project_id'] !== $id) {
```

**Step 2: Search for same pattern in ALL ads-analyzer controllers**

```bash
grep -n "!== \$id\|!== \$projectId" modules/ads-analyzer/controllers/*.php
```

Fix any similar strict comparisons between DB strings and route ints.

**Step 3: Commit**

```bash
php -l modules/ads-analyzer/controllers/CampaignCreatorController.php
git add modules/ads-analyzer/controllers/CampaignCreatorController.php
git commit -m "fix(ads-analyzer): fix strict comparison in toggleKeyword (was always 404)"
```

---

### Task 2.2: Fix ads-analyzer CampaignController jsonResponse inside ob_start

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php`

**Context:** Lines ~255-300 — `jsonResponse()` called inside `ob_start()` context. `jsonResponse()` echoes + exits without `ob_end_clean()`, so buffered output corrupts JSON.

**Step 1: Replace early-exit `jsonResponse()` calls inside ob_start with proper pattern**

```php
// BEFORE:
ob_start();
// ...
jsonResponse(['error' => 'Progetto non trovato'], 404);  // ← corrupted by ob buffer

// AFTER:
ob_start();
// ...
if (!$project) {
    ob_end_clean();
    jsonResponse(['error' => 'Progetto non trovato'], 404);
}
```

Add `ob_end_clean();` before EVERY `jsonResponse()` call within the `ob_start()` context (approximately lines 255, 259, 271, 277, 300).

Also ensure the success path has:
```php
ob_end_clean();
echo json_encode(['success' => true, ...]);
exit;
```

**Step 2: Commit**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "fix(ads-analyzer): fix JSON corruption from ob_start/jsonResponse conflict"
```

---

### Task 2.3: Fix ALL ads-analyzer flash message format

**Files:**
- Modify: ALL controllers in `modules/ads-analyzer/controllers/` (except ScriptController)

**Context:** Using `$_SESSION['flash_success']` and `$_SESSION['flash_error']` — wrong format. Must be `$_SESSION['_flash']['success']` and `$_SESSION['_flash']['error']`. Result: flash messages NEVER display.

**Step 1: Find and replace in each controller**

```bash
grep -rn "flash_success\|flash_error\|flash_warning" modules/ads-analyzer/controllers/
```

Replace ALL occurrences:
```php
// BEFORE:
$_SESSION['flash_success'] = 'Messaggio';
$_SESSION['flash_error'] = 'Errore';

// AFTER:
$_SESSION['_flash']['success'] = 'Messaggio';
$_SESSION['_flash']['error'] = 'Errore';
```

**Step 2: Commit**

```bash
php -l modules/ads-analyzer/controllers/*.php
git add modules/ads-analyzer/controllers/
git commit -m "fix(ads-analyzer): fix flash message format in all controllers"
```

---

### Task 2.4: Fix content-creator flash message format

**Files:**
- Modify: `modules/content-creator/controllers/GeneratorController.php` (lines ~764-765)
- Modify: `modules/content-creator/controllers/ExportController.php` (lines ~67, 80)

**Step 1: Same fix as Task 2.3**

```php
// BEFORE:
$_SESSION['flash_error'] = 'message';

// AFTER:
$_SESSION['_flash']['error'] = 'message';
```

**Step 2: Fix raw header('Location') in GeneratorController ~line 765**

```php
// BEFORE:
header('Location: /content-creator/...');

// AFTER:
Router::redirect('/content-creator/...');
```

**Step 3: Commit**

```bash
php -l modules/content-creator/controllers/GeneratorController.php
php -l modules/content-creator/controllers/ExportController.php
git add modules/content-creator/controllers/GeneratorController.php modules/content-creator/controllers/ExportController.php
git commit -m "fix(content-creator): fix flash messages and raw redirects"
```

---

### Task 2.5: Fix content-creator missing 'service' content type

**Files:**
- Modify: `modules/content-creator/controllers/ProjectController.php` (~line 78)
- Modify: `modules/content-creator/views/projects/index.php` (~lines 63-68)
- Modify: `modules/content-creator/views/partials/project-nav.php` (~lines 40-45)

**Context:** `service` type is valid in DB but missing from validation whitelist and UI mapping → projects of type 'service' can't be created and display incorrectly.

**Step 1: Add 'service' to validation array in ProjectController**

```php
// Find content_type validation array and add 'service':
$validTypes = ['blog', 'page', 'product', 'social', 'service'];
```

**Step 2: Add 'service' to contentTypes maps in views**

```php
'service' => ['label' => 'Servizio', 'icon' => '...', 'color' => '...'],
```

**Step 3: Commit**

```bash
php -l modules/content-creator/controllers/ProjectController.php
git add modules/content-creator/controllers/ProjectController.php modules/content-creator/views/projects/index.php modules/content-creator/views/partials/project-nav.php
git commit -m "fix(content-creator): add missing 'service' content type"
```

---

### Task 2.6: Fix content-creator Connector model categories_cache bug

**Files:**
- Modify: `modules/content-creator/models/Connector.php`

**Context:** Line ~75-97 — `categories_cache` column not in the update whitelist. When sync updates categories, the update silently fails → stale categories forever.

**Step 1: Add `categories_cache` to allowed columns**

```php
// Find the ALLOWED_COLUMNS or similar whitelist constant/array
// Add 'categories_cache' to it
```

**Step 2: Commit**

```bash
php -l modules/content-creator/models/Connector.php
git add modules/content-creator/models/Connector.php
git commit -m "fix(content-creator): add categories_cache to Connector update whitelist"
```

---

### Task 2.7: Fix seo-audit CSRF field name in action-plan and pages views

**Files:**
- Modify: `modules/seo-audit/views/audit/action-plan.php`
- Modify: `modules/seo-audit/views/audit/pages.php`

**Context:** Views send `_token` in AJAX. While `Middleware::csrf()` accepts `_token` as fallback, there's a bug: for JSON body requests, the middleware reads `$_POST` which is empty for `Content-Type: application/json`. The JSON body needs to be decoded or use header instead.

**Step 1: Check if requests actually work**

Verify: does `Middleware::csrf()` parse JSON body? If not, these AJAX calls silently fail CSRF.

If using `fetch()` with JSON body, switch to sending CSRF via header:
```js
// BEFORE:
body: JSON.stringify({ _token: this.csrfToken })

// AFTER:
headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': this.csrfToken
},
body: JSON.stringify({})
```

**Step 2: Standardize to `_csrf_token` for form-encoded requests**

For URL-encoded body requests in pages.php:
```js
// BEFORE:
body: '_token=' + csrfToken

// AFTER:
body: '_csrf_token=' + csrfToken
```

**Step 3: Commit**

```bash
git add modules/seo-audit/views/audit/action-plan.php modules/seo-audit/views/audit/pages.php
git commit -m "fix(seo-audit): standardize CSRF token name in AJAX calls"
```

---

### Task 2.8: Fix seo-audit CrawlController respect_robots logic bug

**Files:**
- Modify: `modules/seo-audit/controllers/CrawlController.php`

**Context:** Line ~86 — ternary logic always evaluates `respect_robots` to 1.

**Step 1: Fix ternary**

```php
// BEFORE (broken):
'respect_robots' => $_POST['respect_robots'] ?? 1 ? 1 : 0,

// AFTER (correct):
'respect_robots' => !empty($_POST['respect_robots']) ? 1 : 0,
```

**Step 2: Commit**

```bash
php -l modules/seo-audit/controllers/CrawlController.php
git add modules/seo-audit/controllers/CrawlController.php
git commit -m "fix(seo-audit): fix respect_robots ternary always evaluating to 1"
```

---

### Task 2.9: Fix seo-audit notification URLs

**Files:**
- Modify: `modules/seo-audit/controllers/CrawlController.php`

**Context:** Lines ~700, 762 — notification URLs use `/projects/` (plural, wrong route) and `/results` (non-existent route).

**Step 1: Fix notification action_url**

```php
// BEFORE:
'action_url' => "/seo-audit/projects/{$projectId}/results"

// AFTER — use correct route:
'action_url' => url("/seo-audit/projects/{$projectId}/audit")
```

Verify the correct route by checking `modules/seo-audit/routes.php`.

**Step 2: Commit**

```bash
php -l modules/seo-audit/controllers/CrawlController.php
git add modules/seo-audit/controllers/CrawlController.php
git commit -m "fix(seo-audit): fix notification URLs to use correct routes"
```

---

### Task 2.10: Fix seo-audit UnifiedReportController non-existent view

**Files:**
- Modify: `modules/seo-audit/controllers/UnifiedReportController.php`

**Context:** Line 36 — renders `seo-audit::errors/not-found` which doesn't exist.

**Step 1: Either create the view or use redirect**

Option A (preferred — redirect):
```php
// BEFORE:
return View::render('seo-audit::errors/not-found', [...]);

// AFTER:
$_SESSION['_flash']['error'] = 'Report non trovato';
Router::redirect("/seo-audit/projects/{$projectId}/audit");
```

**Step 2: Commit**

```bash
php -l modules/seo-audit/controllers/UnifiedReportController.php
git add modules/seo-audit/controllers/UnifiedReportController.php
git commit -m "fix(seo-audit): fix non-existent error view in UnifiedReportController"
```

---

### Task 2.11: Fix core Router 404 handler (blank page)

**Files:**
- Modify: `core/Router.php`

**Context:** Line 88 — `View::render('errors/404')` result not echoed. 404 pages show blank.

**Step 1: Add echo**

```php
// BEFORE (line 88):
View::render('errors/404');

// AFTER:
echo View::render('errors/404', [], null);
```

Note: pass `null` for layout if 404 should render without full layout, or omit for default.

**Step 2: Commit**

```bash
php -l core/Router.php
git add core/Router.php
git commit -m "fix(core): echo 404 View::render result (was blank page)"
```

---

### Task 2.12: Fix profile page missing $plan variable

**Files:**
- Modify: `public/index.php` (profile route, ~line 584-595)

**Context:** `$plan` used in `profile.php:88` but never passed by the route handler. Shows PHP warning and always displays "Free".

**Step 1: Pass $plan to the view**

```php
// In the /profile GET route (~line 584):
Router::get('/profile', function () {
    Middleware::auth();
    $user = Auth::user();

    // Fetch user plan
    $plan = Database::fetch("SELECT * FROM plans WHERE id = ?", [$user['plan_id'] ?? 0]);

    return View::render('profile', [
        'title' => 'Profilo',
        'user' => $user,
        'plan' => $plan,  // ← ADD THIS
        'modules' => ModuleLoader::getUserModules($user['id']),
    ]);
});
```

Verify the plans table structure first: `SHOW COLUMNS FROM plans;`

**Step 2: Commit**

```bash
php -l public/index.php
git add public/index.php
git commit -m "fix(profile): pass plan variable to profile view"
```

---

### Task 2.13: Fix keyword-research missing owner billing for shared projects

**Files:**
- Modify: `modules/keyword-research/controllers/ArchitectureController.php`
- Modify: `modules/keyword-research/controllers/EditorialController.php`

**Context:** Lines ~274-278 and ~388-394 — credits consumed from current user instead of project owner for shared projects.

**Step 1: Use ProjectAccessService for owner billing**

```php
// BEFORE:
Credits::consume($user['id'], $cost, 'operation', 'keyword-research');

// AFTER:
$ownerId = \Services\ProjectAccessService::getOwnerId($project['global_project_id'] ?? $project['id']);
Credits::consume($ownerId ?? $user['id'], $cost, 'operation', 'keyword-research');
```

Also add viewer check — viewers should not be able to trigger credit-consuming operations:
```php
if (\Services\ProjectAccessService::isViewer($project['global_project_id'] ?? null, $user['id'])) {
    $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
    Router::redirect("/keyword-research/projects/{$id}");
}
```

**Step 2: Commit**

```bash
php -l modules/keyword-research/controllers/ArchitectureController.php
php -l modules/keyword-research/controllers/EditorialController.php
git add modules/keyword-research/controllers/ArchitectureController.php modules/keyword-research/controllers/EditorialController.php
git commit -m "fix(keyword-research): use owner billing for shared projects"
```

---

### Task 2.14: Fix content-creator GeneratorController getActiveModules + missing title

**Files:**
- Modify: `modules/content-creator/controllers/GeneratorController.php`

**Context:** Lines ~797, 805 — uses `getActiveModules()` instead of `getUserModules()` in View::render. Also missing `'title'` key.

**Step 1: Fix View::render calls**

```php
// BEFORE:
return View::render('content-creator::generator/preview', [
    'modules' => ModuleLoader::getActiveModules(),
    // missing 'title'
]);

// AFTER:
return View::render('content-creator::generator/preview', [
    'title' => 'Preview Contenuto',
    'user' => $user,
    'modules' => ModuleLoader::getUserModules($user['id']),
]);
```

**Step 2: Commit**

```bash
php -l modules/content-creator/controllers/GeneratorController.php
git add modules/content-creator/controllers/GeneratorController.php
git commit -m "fix(content-creator): fix getUserModules and add missing title in GeneratorController"
```

---

## Tier 3 — MEDIUM Security Hardening

### Task 3.1: Fix remember_token cookie missing secure flag

**Files:**
- Modify: `core/Auth.php`

**Context:** Line ~45-50 — `setcookie()` missing `'secure' => true`. Cookie sent over HTTP in production.

**Step 1: Add secure flag**

```php
// BEFORE (line 45-50):
setcookie('remember_token', $token, [
    'expires' => time() + (86400 * 30),
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// AFTER:
setcookie('remember_token', $token, [
    'expires' => time() + (86400 * 30),
    'path' => '/',
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax',
]);
```

Using `!empty($_SERVER['HTTPS'])` so it works both locally (HTTP) and production (HTTPS).

**Step 2: Commit**

```bash
php -l core/Auth.php
git add core/Auth.php
git commit -m "fix(auth): add secure flag to remember_token cookie"
```

---

### Task 3.2: Fix Router::back() open redirect

**Files:**
- Modify: `core/Router.php`

**Context:** Line 102-107 — `back()` uses `HTTP_REFERER` without validating it belongs to the same domain.

**Step 1: Validate referer**

```php
// BEFORE:
public static function back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? self::$basePath . '/';
    header('Location: ' . $referer);
    exit;
}

// AFTER:
public static function back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Only allow same-origin redirects
    if ($referer && parse_url($referer, PHP_URL_HOST) === $host) {
        header('Location: ' . $referer);
    } else {
        header('Location: ' . self::$basePath . '/');
    }
    exit;
}
```

**Step 2: Commit**

```bash
php -l core/Router.php
git add core/Router.php
git commit -m "fix(core): validate referer in Router::back() to prevent open redirect"
```

---

### Task 3.3: Fix ScraperService and SitemapService disabled SSL verification

**Files:**
- Modify: `services/ScraperService.php`
- Modify: `services/SitemapService.php`

**Context:** SSL verification disabled unconditionally (`CURLOPT_SSL_VERIFYPEER => false`).

**Step 1: Enable SSL verification (remove the disabling option)**

```php
// BEFORE:
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_SSL_VERIFYHOST => 0,

// AFTER: remove these lines entirely, or set to true:
CURLOPT_SSL_VERIFYPEER => true,
CURLOPT_SSL_VERIFYHOST => 2,
```

If there are legitimate reasons for disabling (e.g., scraping sites with bad certs), make it opt-in:
```php
CURLOPT_SSL_VERIFYPEER => $options['verify_ssl'] ?? true,
```

**Step 2: Commit**

```bash
php -l services/ScraperService.php
php -l services/SitemapService.php
git add services/ScraperService.php services/SitemapService.php
git commit -m "fix(services): enable SSL verification in ScraperService and SitemapService"
```

---

### Task 3.4: Fix DataForSeoService missing API logging

**Files:**
- Modify: `services/DataForSeoService.php`

**Context:** `analyzeInstantPage()` (lines ~693-822) and `checkSerpPositionBatch()` (lines ~479-636) make API calls without ApiLoggerService logging.

**Step 1: Add logging to both methods**

```php
$startTime = microtime(true);
// ... existing API call ...
ApiLoggerService::log('dataforseo', '/endpoint', $request, $response, $httpCode, $startTime, [
    'module' => 'seo-audit',  // or appropriate module
    'context' => 'instant_page_analysis'
]);
```

**Step 2: Commit**

```bash
php -l services/DataForSeoService.php
git add services/DataForSeoService.php
git commit -m "fix(services): add API logging to DataForSeoService methods"
```

---

### Task 3.5: Fix AiService credits consumed on empty response

**Files:**
- Modify: `services/AiService.php`

**Context:** Lines ~180-195 — credits consumed even when AI returns empty/error response.

**Step 1: Add response validation before consuming credits**

```php
// After receiving AI response, before Credits::consume():
if (empty($result) || empty($result['content'])) {
    // Don't consume credits for failed responses
    return ['success' => false, 'error' => 'Risposta AI vuota'];
}
Credits::consume($userId, $cost, $operation, $module);
```

**Step 2: Commit**

```bash
php -l services/AiService.php
git add services/AiService.php
git commit -m "fix(services): don't consume credits on empty AI response"
```

---

### Task 3.6: Fix EmailService using env() instead of Settings::get()

**Files:**
- Modify: `services/EmailService.php`

**Context:** Lines ~146, 243, 279, 447, 466 — uses `env('APP_URL')` instead of `Settings::get('app_url')`.

**Step 1: Replace all occurrences**

```php
// BEFORE:
env('APP_URL')

// AFTER:
Settings::get('app_url', 'https://ainstein.it')
```

Search: `grep -n "env(" services/EmailService.php`

**Step 2: Commit**

```bash
php -l services/EmailService.php
git add services/EmailService.php
git commit -m "fix(services): replace env() with Settings::get() in EmailService"
```

---

## Tier 4 — MEDIUM UX/Functional Issues

### Task 4.1: Fix layout.php flash messages — missing warning/info styles

**Files:**
- Modify: `shared/views/layout.php`

**Context:** Lines 311-328 — only `error` (red) and default (green) styles. `warning` and `info` flash types display as green/success.

**Step 1: Add warning and info styles**

```php
// BEFORE (line 312):
<?= $type === 'error' ? 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' ?>

// AFTER:
<?php
$flashClasses = match($type) {
    'error' => 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    'info' => 'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    default => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
};
?>
<?= $flashClasses ?>
```

Also update the SVG icons for warning/info:
```php
<?php if ($type === 'error'): ?>
    <!-- X circle icon -->
<?php elseif ($type === 'warning'): ?>
    <!-- Exclamation triangle icon -->
<?php elseif ($type === 'info'): ?>
    <!-- Information circle icon -->
<?php else: ?>
    <!-- Check circle icon -->
<?php endif; ?>
```

**Step 2: Commit**

```bash
php -l shared/views/layout.php
git add shared/views/layout.php
git commit -m "fix(layout): add warning and info flash message styles"
```

---

### Task 4.2: Fix View::$data static accumulation

**Files:**
- Modify: `core/View.php`

**Context:** Line 12 — `self::$data = array_merge(self::$data, $data)` accumulates data between renders. If same request renders multiple views, data leaks between them.

**Step 1: Reset data at the start of render**

```php
// BEFORE (line 12):
self::$data = array_merge(self::$data, $data);

// AFTER:
self::$data = $data;
```

Note: Verify no code depends on accumulated data (e.g., calling `View::set()` before `View::render()`). If so, keep merge but reset at end:

```php
// Alternative: reset after render completes
public static function render(...): string
{
    self::$data = array_merge(self::$data, $data);
    // ... existing render logic ...
    $result = ...;
    self::$data = [];  // ← Reset after render
    return $result;
}
```

**Step 2: Commit**

```bash
php -l core/View.php
git add core/View.php
git commit -m "fix(core): prevent View::data accumulation between renders"
```

---

### Task 4.3: Fix old() function never clearing _old_input

**Files:**
- Modify: `core/View.php` (or wherever sessions are managed)

**Context:** Line 168 — `old()` reads `$_SESSION['_old_input']` but it's never cleared, causing stale form data on subsequent page loads.

**Step 1: Clear _old_input after layout render**

In `layout.php`, after flash messages are cleared (line 327):

```php
<?php unset($_SESSION['_flash']); ?>
<?php unset($_SESSION['_old_input']); ?>  // ← ADD THIS
```

**Step 2: Commit**

```bash
php -l shared/views/layout.php
git add shared/views/layout.php
git commit -m "fix(core): clear _old_input session after render"
```

---

### Task 4.4: Fix GlobalProjectController form data lost on validation error

**Files:**
- Modify: `controllers/GlobalProjectController.php`

**Context:** Lines ~151-154 — on validation error, redirects back but doesn't save form data to `$_SESSION['_old_input']`.

**Step 1: Save form data before redirect**

```php
// Before the redirect on validation error:
$_SESSION['_old_input'] = $_POST;
$_SESSION['_flash']['error'] = 'Errore di validazione';
Router::redirect('/projects/create');
```

**Step 2: Commit**

```bash
php -l controllers/GlobalProjectController.php
git add controllers/GlobalProjectController.php
git commit -m "fix(projects): preserve form data on validation error"
```

---

### Task 4.5: Fix GlobalProjectController sharing() uses getActiveModules

**Files:**
- Modify: `controllers/GlobalProjectController.php`

**Context:** Line ~929 — `getActiveModules()` instead of `getUserModules($user['id'])`.

**Step 1: Fix**

```php
// BEFORE:
'modules' => ModuleLoader::getActiveModules(),

// AFTER:
'modules' => ModuleLoader::getUserModules($user['id']),
```

**Step 2: Commit**

```bash
php -l controllers/GlobalProjectController.php
git add controllers/GlobalProjectController.php
git commit -m "fix(projects): use getUserModules in sharing view"
```

---

### Task 4.6: Fix profile.php — Google OAuth users see unusable password form

**Files:**
- Modify: `shared/views/profile.php`

**Context:** Lines ~39-73 — Google OAuth users (who have no password set) see a "Change Password" form that will always fail because `password_verify()` on an empty hash fails.

**Step 1: Conditionally show password form**

```php
<?php if (!empty($user['password'])): ?>
    <!-- Password change form -->
    ...
<?php else: ?>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Password</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Hai effettuato l'accesso tramite Google. La password non è necessaria.
            </p>
        </div>
    </div>
<?php endif; ?>
```

**Step 2: Commit**

```bash
php -l shared/views/profile.php
git add shared/views/profile.php
git commit -m "fix(profile): hide password form for Google OAuth users"
```

---

### Task 4.7: Fix registration error display with HTML escaping

**Files:**
- Modify: `public/index.php`

**Context:** Line ~280 — registration errors joined with `<br>` but the view escapes HTML, so user sees literal `<br>` tags.

**Step 1: Use array for errors instead of HTML-joined string**

```php
// BEFORE:
$_SESSION['_flash']['error'] = implode('<br>', $errors);

// AFTER:
$_SESSION['_flash']['error'] = implode('. ', $errors);
```

Or if multiple errors need separate lines, use the first error only:
```php
$_SESSION['_flash']['error'] = $errors[0];
```

**Step 2: Commit**

```bash
php -l public/index.php
git add public/index.php
git commit -m "fix(auth): fix registration error display (no HTML in flash messages)"
```

---

### Task 4.8: Fix keyword-research wizard missing resp.ok check

**Files:**
- Modify: `modules/keyword-research/views/research/wizard.php`

**Context:** Line ~531 — `resp.json()` called without first checking `resp.ok`. If server returns 500, `resp.json()` may throw or parse error HTML.

**Step 1: Add response check**

```js
// BEFORE:
const data = await resp.json();

// AFTER:
if (!resp.ok) {
    throw new Error(`Errore server: ${resp.status}`);
}
const data = await resp.json();
```

**Step 2: Search and fix all similar patterns in keyword-research views**

```bash
grep -n "\.json()" modules/keyword-research/views/**/*.php
```

**Step 3: Commit**

```bash
git add modules/keyword-research/views/
git commit -m "fix(keyword-research): add resp.ok check before JSON parsing"
```

---

### Task 4.9: Fix ai-content ArticleController SSE missing guards

**Files:**
- Modify: `modules/ai-content/controllers/ArticleController.php`

**Context:** Line ~261-275 — `progress()` SSE method missing `ignore_user_abort(true)`, `set_time_limit(0)`, `session_write_close()`.

**Step 1: Add SSE guards**

```php
public function progress(int $id): void
{
    ignore_user_abort(true);     // ← ADD
    set_time_limit(0);           // ← ADD
    session_write_close();       // ← ADD

    header('Content-Type: text/event-stream');
    // ... existing code
}
```

**Step 2: Fix WizardController SSE missing session_write_close**

In `modules/ai-content/controllers/WizardController.php` ~line 38-46, add `session_write_close()`.

**Step 3: Commit**

```bash
php -l modules/ai-content/controllers/ArticleController.php
php -l modules/ai-content/controllers/WizardController.php
git add modules/ai-content/controllers/ArticleController.php modules/ai-content/controllers/WizardController.php
git commit -m "fix(ai-content): add SSE guards to ArticleController and WizardController"
```

---

### Task 4.10: Fix keyword-research missing AJAX guards

**Files:**
- Modify: `modules/keyword-research/controllers/ArchitectureController.php`
- Modify: `modules/keyword-research/controllers/EditorialController.php`

**Context:** Long AJAX operations missing `ob_start()`, `ignore_user_abort(true)`, `session_write_close()`.

**Step 1: Add guards to analyze/generate methods**

```php
ignore_user_abort(true);
set_time_limit(0);
ob_start();
header('Content-Type: application/json');
session_write_close();

// ... operation + Database::reconnect() ...

ob_end_clean();
echo json_encode($result);
exit;
```

**Step 2: Commit**

```bash
php -l modules/keyword-research/controllers/ArchitectureController.php
php -l modules/keyword-research/controllers/EditorialController.php
git add modules/keyword-research/controllers/ArchitectureController.php modules/keyword-research/controllers/EditorialController.php
git commit -m "fix(keyword-research): add AJAX long operation guards"
```

---

## Tier 5 — LOW Improvements & Code Quality

### Task 5.1: Fix admin JobsController missing seo-audit module

**Files:**
- Modify: `admin/controllers/JobsController.php`

**Context:** seo-audit not included in monitored modules list for the jobs dashboard.

**Step 1: Add seo-audit to modules array**

Find the modules list and add seo-audit with its job table/status patterns.

**Step 2: Commit**

```bash
php -l admin/controllers/JobsController.php
git add admin/controllers/JobsController.php
git commit -m "fix(admin): add seo-audit to jobs monitoring"
```

---

### Task 5.2: Fix EmailService placeholder regex missing camelCase

**Files:**
- Modify: `services/EmailService.php`

**Context:** Line ~234 — cleanup regex `[a-z_]+` doesn't match camelCase like `{{userName}}`.

**Step 1: Fix regex**

```php
// BEFORE:
preg_replace('/\{\{[a-z_]+\}\}/', '', $body);

// AFTER:
preg_replace('/\{\{[a-zA-Z_]+\}\}/', '', $body);
```

**Step 2: Commit**

```bash
php -l services/EmailService.php
git add services/EmailService.php
git commit -m "fix(services): fix email placeholder regex to match camelCase"
```

---

### Task 5.3: Fix AiService memory waste in token estimation

**Files:**
- Modify: `services/AiService.php`

**Context:** Line ~225 — `str_repeat('x', $contentSize)` creates huge strings just for token estimation.

**Step 1: Use math instead**

```php
// BEFORE:
$estimatedTokens = strlen(str_repeat('x', $contentSize)) / 4;

// AFTER:
$estimatedTokens = (int)ceil($contentSize / 4);
```

**Step 2: Commit**

```bash
php -l services/AiService.php
git add services/AiService.php
git commit -m "fix(services): use math for token estimation instead of str_repeat"
```

---

### Task 5.4: Fix onboarding JSON routes missing Content-Type header

**Files:**
- Modify: `public/index.php`

**Context:** Lines ~679-708 — onboarding AJAX routes return JSON without setting Content-Type header.

**Step 1: Add Content-Type header**

```php
header('Content-Type: application/json');
echo json_encode([...]);
```

Or use `View::json()` / `jsonResponse()`.

**Step 2: Commit**

```bash
php -l public/index.php
git add public/index.php
git commit -m "fix(onboarding): add Content-Type header to JSON routes"
```

---

### Task 5.5: Fix auth register Terms/Privacy links (404)

**Files:**
- Modify: `shared/views/auth/register.php`

**Context:** Lines ~74-75 — Terms of Service and Privacy Policy links point to pages that don't exist.

**Step 1: Update links or create placeholder pages**

Option A: Point to existing pages
```php
<a href="<?= url('/terms') ?>">Termini di Servizio</a>
<a href="<?= url('/privacy') ?>">Privacy Policy</a>
```

Then create routes + views if they don't exist. If pages exist, just verify URLs.

Option B: Remove links until pages exist (less preferable for production).

**Step 2: Commit**

```bash
git add shared/views/auth/register.php
git commit -m "fix(auth): update Terms/Privacy links in registration page"
```

---

## Tier 6 — LOW Cosmetic & Edge Cases

### Task 6.1: Standardize CSRF field name across all views

**Files:** All views using `_token` instead of `_csrf_token`

**Step 1: Search all views**

```bash
grep -rn "_token" modules/*/views/ shared/views/ --include="*.php" | grep -v "_csrf_token" | grep -v "csrfToken\|csrf_token\|remember_token"
```

**Step 2: Replace `_token` with `_csrf_token` in form fields**

The middleware accepts both, but standardize per codebase convention.

**Step 3: Commit**

```bash
git add -A
git commit -m "chore: standardize CSRF field name to _csrf_token across all views"
```

---

### Task 6.2: Audit all View::render calls for missing 'user' key

**Step 1: Find violations**

```bash
grep -rn "View::render(" modules/*/controllers/ controllers/ admin/controllers/ --include="*.php" | grep -v "'user'"
```

**Step 2: Fix any controllers that render views with layout but don't pass `'user' => $user`**

**Step 3: Commit per module**

---

### Task 6.3: Audit all View::render calls for missing 'return'

**Step 1: Find violations**

```bash
grep -rn "View::render(" modules/*/controllers/ controllers/ --include="*.php" | grep -v "return\|echo"
```

**Step 2: Add `return` where missing**

**Step 3: Commit per module**

---

## SEO Tracking — Dedicated Fix Section

> These fixes are specific to the seo-tracking module. Organized by severity within the module.

### Task ST-1: Fix routes calling non-existent ReportController methods (CRITICAL — Fatal Error)

**Files:**
- Modify: `modules/seo-tracking/routes.php`

**Context:** Routes at lines 651, 657, 690 call methods that don't exist on ReportController: `weekly()`, `monthly()`, `download()`. These routes will 500-error immediately.

**Step 1: Fix or remove broken routes**

Option A — Remove routes if features not implemented:
```php
// Comment out or remove these routes until methods are implemented:
// Line 651: Router::get('.../reports/weekly', ...) → weekly() doesn't exist
// Line 657: Router::get('.../reports/monthly', ...) → monthly() doesn't exist
// Line 690: Router::get('.../reports/{reportId}/download', ...) → download() doesn't exist
```

Option B — Fix download route to use existing method:
```php
// Line 690 — download() doesn't exist, but downloadPdf() does:
// BEFORE:
return (new ReportController())->download((int) $id, (int) $reportId);

// AFTER:
return (new ReportController())->downloadPdf((int) $reportId);
```

For `weekly()` and `monthly()`, either implement the methods or remove the routes.

**Step 2: Commit**

```bash
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/routes.php
git commit -m "fix(seo-tracking): fix routes calling non-existent ReportController methods"
```

---

### Task ST-2: Fix routes calling non-existent AlertController methods (CRITICAL — Fatal Error)

**Files:**
- Modify: `modules/seo-tracking/routes.php`

**Context:** Routes at lines 549, 555, 561 call `settings()`, `updateSettings()`, `history()` — none exist on AlertController.

**Step 1: Remove or implement**

```php
// Line 549: GET /alerts/settings → settings() doesn't exist
// Line 555: POST /alerts/settings → updateSettings() doesn't exist
// Line 561: GET /alerts/history → history() doesn't exist

// Option A: Comment out until implemented
// Option B: Implement the methods in AlertController
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/routes.php
git commit -m "fix(seo-tracking): fix routes calling non-existent AlertController methods"
```

---

### Task ST-3: Fix AlertController::markRead() argument mismatch (HIGH — Feature Broken)

**Files:**
- Modify: `modules/seo-tracking/routes.php`

**Context:** Route at line 565 passes `(int) $id, (int) $alertId` to `markRead()`, but method only accepts one argument. The project ID is passed instead of the alert ID → mark-read always fails or operates on wrong record.

**Step 1: Fix route to pass correct argument**

```php
// BEFORE (line 565-569):
Router::post('/seo-tracking/project/{id}/alerts/{alertId}/read', function ($id, $alertId) {
    return (new AlertController())->markRead((int) $id, (int) $alertId);
});

// AFTER:
Router::post('/seo-tracking/project/{id}/alerts/{alertId}/read', function ($id, $alertId) {
    return (new AlertController())->markRead((int) $alertId);
});
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/routes.php
git commit -m "fix(seo-tracking): pass alertId instead of projectId to markRead()"
```

---

### Task ST-4: Fix SQL injection in Keyword::allByProject() (HIGH — Security)

**Files:**
- Modify: `modules/seo-tracking/models/Keyword.php`

**Context:** Line 70 — `$filters['order_by']` interpolated directly into SQL without allowlist validation.

**Step 1: Add allowlist**

```php
// BEFORE:
$orderBy = $filters['order_by'] ?? 'last_position';
$sql .= " ORDER BY {$orderBy} {$orderDir}";

// AFTER:
$allowedColumns = ['last_position', 'keyword', 'search_volume', 'position_change', 'best_position', 'created_at', 'updated_at'];
$orderBy = in_array($filters['order_by'] ?? 'last_position', $allowedColumns)
    ? $filters['order_by']
    : 'last_position';

$orderDir = strtoupper($filters['order_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$sql .= " ORDER BY {$orderBy} {$orderDir}";
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/models/Keyword.php
git add modules/seo-tracking/models/Keyword.php
git commit -m "fix(seo-tracking): add allowlist validation for SQL ORDER BY (SQL injection fix)"
```

---

### Task ST-5: Add CSRF to all state-mutating POST routes (HIGH — Security)

**Files:**
- Modify: `modules/seo-tracking/routes.php`

**Context:** ~15 POST routes missing `Middleware::csrf()`, including credit-consuming operations.

**Step 1: Add `Middleware::csrf()` to these routes**

Priority routes (consume credits or modify state):
```
Line 158: POST /keywords/delete/{keywordId}
Line 164: POST /keywords/bulk-delete
Line 228: POST /rank-check/single
Line 234: POST /rank-check/import-keywords
Line 244: POST /rank-check/start-job
Line 261: POST /rank-check/cancel-job
Line 321: POST /keywords/start-positions-job
Line 338: POST /keywords/cancel-positions-job
Line 394: POST /keywords/update-volumes (deprecated)
Line 410: POST /keywords/refresh-volumes
Line 503: POST /groups/{groupId}/add-keyword
Line 509: POST /groups/{groupId}/remove-keyword
Line 515: POST /groups/sync-from-keywords
Line 588: POST /quick-wins/analyze
Line 599: POST /groups/{groupId}/quick-wins/analyze
Line 615: POST /analyze-page
```

For each, add after `Middleware::auth();`:
```php
Middleware::csrf();
```

**Step 2: Ensure frontend sends CSRF token**

Check all corresponding JS fetch calls include CSRF header:
```js
headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || csrfToken }
```

**Step 3: Commit**

```bash
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/routes.php
git commit -m "fix(seo-tracking): add CSRF protection to all state-mutating POST routes"
```

---

### Task ST-6: Fix RankCheckController SSE missing guards (HIGH)

**Files:**
- Modify: `modules/seo-tracking/controllers/RankCheckController.php`

**Context:** `processStream()` (line ~845) missing `ignore_user_abort(true)` and `set_time_limit(0)`. Also `ob_flush()` without `ob_get_level()` check at line ~886.

**Step 1: Add guards at start of processStream()**

```php
private function processStream(...): void
{
    ignore_user_abort(true);  // ← ADD
    set_time_limit(0);        // ← ADD
    session_write_close();    // should already exist
    // ...
```

**Step 2: Fix ob_flush guard**

```php
// BEFORE (line ~886):
ob_flush();
flush();

// AFTER:
if (ob_get_level()) ob_flush();
flush();
```

**Step 3: Commit**

```bash
php -l modules/seo-tracking/controllers/RankCheckController.php
git add modules/seo-tracking/controllers/RankCheckController.php
git commit -m "fix(seo-tracking): add SSE guards to RankCheckController processStream"
```

---

### Task ST-7: Fix AiReportService missing Database::reconnect() (HIGH)

**Files:**
- Modify: `modules/seo-tracking/services/AiReportService.php`

**Context:** AI call via `$this->aiService->analyze()` can take 30-60+ seconds. No `Database::reconnect()` before subsequent DB operations → "MySQL gone away".

**Step 1: Add reconnect after AI call**

```php
$result = $this->aiService->analyze($userId, $prompt, $content, 'seo-tracking');
Database::reconnect();  // ← ADD after AI call
$this->aiReport->create([...]);
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/services/AiReportService.php
git add modules/seo-tracking/services/AiReportService.php
git commit -m "fix(seo-tracking): add Database::reconnect() after AI call in AiReportService"
```

---

### Task ST-8: Fix CompareController flash messages + raw redirects (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/controllers/CompareController.php`

**Context:** Lines 27, 134 — `$_SESSION['flash_error']` (wrong format) + `header('Location: /seo-tracking')` (raw, no base URL).

**Step 1: Fix both issues**

```php
// BEFORE (line 27):
$_SESSION['flash_error'] = 'Progetto non trovato';
header('Location: /seo-tracking');
exit;

// AFTER:
$_SESSION['_flash']['error'] = 'Progetto non trovato';
Router::redirect('/seo-tracking');
```

Apply to both line 27 and line 134.

**Step 2: Commit**

```bash
php -l modules/seo-tracking/controllers/CompareController.php
git add modules/seo-tracking/controllers/CompareController.php
git commit -m "fix(seo-tracking): fix CompareController flash format and raw redirects"
```

---

### Task ST-9: Fix RankCheckController raw redirects (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/controllers/RankCheckController.php`

**Context:** Lines 39, 398 — `header('Location: /seo-tracking')` breaks in subdirectory installs.

**Step 1: Replace with Router::redirect()**

```php
// BEFORE:
header('Location: /seo-tracking');
exit;

// AFTER:
Router::redirect('/seo-tracking');
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/controllers/RankCheckController.php
git add modules/seo-tracking/controllers/RankCheckController.php
git commit -m "fix(seo-tracking): use Router::redirect() in RankCheckController"
```

---

### Task ST-10: Fix GscController getActiveModules (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/controllers/GscController.php`

**Context:** Line 616 — `getActiveModules()` instead of `getUserModules()`.

**Step 1: Fix**

```php
// BEFORE:
'modules' => ModuleLoader::getActiveModules(),

// AFTER:
'modules' => ModuleLoader::getUserModules($user['id']),
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/controllers/GscController.php
git add modules/seo-tracking/controllers/GscController.php
git commit -m "fix(seo-tracking): use getUserModules in GscController"
```

---

### Task ST-11: Fix ReportController::downloadPdf() XSS (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/controllers/ReportController.php`

**Context:** Lines 208-215 — `$report['title']`, `$project['name']`, `$project['domain']` output without `htmlspecialchars()`.

**Step 1: Escape output**

```php
// BEFORE:
echo "<h1>{$report['title']}</h1>";
echo "<p><small>{$project['name']} - {$project['domain']}</small></p>";

// AFTER:
echo "<h1>" . htmlspecialchars($report['title']) . "</h1>";
echo "<p><small>" . htmlspecialchars($project['name']) . " - " . htmlspecialchars($project['domain']) . "</small></p>";
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/controllers/ReportController.php
git add modules/seo-tracking/controllers/ReportController.php
git commit -m "fix(seo-tracking): escape HTML output in downloadPdf (XSS fix)"
```

---

### Task ST-12: Fix export/positions duplicate route (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/routes.php`

**Context:** Lines 709-712 — `/export/positions` calls `keywords()` instead of a positions export method.

**Step 1: Fix or remove**

```php
// BEFORE:
Router::get('/seo-tracking/project/{id}/export/positions', function ($id) {
    return (new ExportController())->keywords((int) $id);  // ← wrong!
});

// AFTER — if positions() method exists:
Router::get('/seo-tracking/project/{id}/export/positions', function ($id) {
    return (new ExportController())->positions((int) $id);
});

// Or remove the route if no separate positions export is needed
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/routes.php
git commit -m "fix(seo-tracking): fix export/positions route calling wrong method"
```

---

### Task ST-13: Add Database::reconnect() to GscService sync loops (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/services/GscService.php`

**Context:** Multiple API calls to Google without reconnect between batches → "MySQL gone away" on long syncs.

**Step 1: Add reconnect after each batch**

```php
// After each curl_exec() batch in the sync loop:
Database::reconnect();
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/services/GscService.php
git add modules/seo-tracking/services/GscService.php
git commit -m "fix(seo-tracking): add Database::reconnect() to GscService sync loops"
```

---

### Task ST-14: Add ApiLoggerService to GscService (MEDIUM)

**Files:**
- Modify: `modules/seo-tracking/services/GscService.php`

**Context:** All `curl_exec()` calls to Google Search Console API missing `ApiLoggerService::log()`.

**Step 1: Add logging**

```php
$startTime = microtime(true);
// ... existing curl call ...
ApiLoggerService::log('google_gsc', $endpoint, $requestData, $responseData, $httpCode, $startTime, [
    'module' => 'seo-tracking',
    'context' => 'gsc_sync'
]);
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/services/GscService.php
git add modules/seo-tracking/services/GscService.php
git commit -m "fix(seo-tracking): add ApiLoggerService logging to GscService"
```

---

### Task ST-15: Fix module.json costs group order + minor cleanup (LOW)

**Files:**
- Modify: `modules/seo-tracking/module.json`

**Context:** Line 82 — costs group `order: 4` should be `order: 99` per standard.

**Step 1: Fix**

```json
// BEFORE:
"costs": { "order": 4, "collapsed": true }

// AFTER:
"costs": { "order": 99, "collapsed": true }
```

**Step 2: Commit**

```bash
git add modules/seo-tracking/module.json
git commit -m "fix(seo-tracking): fix module.json costs group order to 99"
```

---

### Task ST-16: Remove or restrict debug-serp endpoint (LOW)

**Files:**
- Modify: `modules/seo-tracking/routes.php`

**Context:** Lines 434-451 — debug endpoint exposed to all authenticated users. Should be admin-only or removed.

**Step 1: Add admin check**

```php
Router::get('/seo-tracking/project/{id}/debug-serp', function ($id) {
    Middleware::auth();
    Middleware::admin();  // ← ADD — or remove entire route
    // ...
});
```

**Step 2: Commit**

```bash
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/routes.php
git commit -m "fix(seo-tracking): restrict debug-serp endpoint to admin only"
```

---

## Summary

| Tier | Tasks | Severity | Focus |
|------|-------|----------|-------|
| 1 | 1.1–1.8 | CRITICAL | IDOR, XSS, CSRF (ai-content, content-creator, ads-analyzer, auth, admin) |
| 2 | 2.1–2.14 | HIGH | Broken features (ads-analyzer, content-creator, seo-audit, core, profile, keyword-research) |
| 3 | 3.1–3.6 | MEDIUM | Security hardening (core, services) |
| 4 | 4.1–4.10 | MEDIUM | UX, guards, robustness (layout, views, projects, profile, keyword-research, ai-content) |
| 5 | 5.1–5.5 | LOW | Improvements (admin, services, auth) |
| 6 | 6.1–6.3 | LOW | Standardization (CSRF, View::render audit) |
| ST | ST-1–ST-16 | CRITICAL→LOW | SEO Tracking dedicated fixes (routes, CSRF, SQL injection, SSE, flash, redirects) |

**Total: ~56 tasks** across all active production modules + core + services + admin + auth.

**Excluded:** internal-links (standby), crawl-budget (legacy), ai-optimizer (dev), seo-onpage (dev).

**Estimated effort:** ~4-5 hours for Tier 1-2 + ST critical/high, ~2-3 hours for Tier 3-4 + ST medium, ~1 hour for Tier 5-6 + ST low.
