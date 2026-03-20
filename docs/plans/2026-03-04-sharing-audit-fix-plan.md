# Sharing System Audit Fix Plan — Ainstein SEO Toolkit

> **Scope:** Fix 10 security/functional issues found during project sharing system audit.

---

## Finding 1 (HIGH): Viewer Can Perform Write Operations

**Problem:** Only keyword-research `ResearchController` checks viewer role. All other modules allow viewers to add/delete keywords, trigger rank checks, generate AI content, start crawls, consume credits.

**Fix:** Add viewer check helper and apply to all write methods.

### Step 1: Add helper to ProjectAccessService

File: `services/ProjectAccessService.php`

```php
/**
 * Check if user has write access (editor or owner)
 */
public static function canWrite(?string $accessRole): bool
{
    return in_array($accessRole, ['owner', 'editor']);
}
```

### Step 2: Add viewer guard to all write controller methods

Pattern to add after `findAccessible()` in every POST/write method:

```php
if (($project['access_role'] ?? 'owner') === 'viewer') {
    jsonResponse(['error' => 'Non hai i permessi per questa operazione'], 403);
}
```

Or for non-AJAX methods:
```php
if (($project['access_role'] ?? 'owner') === 'viewer') {
    $_SESSION['_flash']['error'] = 'Non hai i permessi per questa operazione';
    Router::redirect('/module/projects/' . $id);
}
```

**Files to modify (all write/state-mutating methods):**

- `modules/seo-tracking/controllers/KeywordController.php` — store(), update(), destroy(), bulkAction()
- `modules/seo-tracking/controllers/RankCheckController.php` — check(), startJob()
- `modules/seo-tracking/controllers/GroupController.php` — store(), update(), destroy()
- `modules/seo-tracking/controllers/DashboardController.php` — deleteKeyword()
- `modules/seo-tracking/controllers/GscController.php` — historicalSync()
- `modules/ai-content/controllers/ArticleController.php` — regenerate(), delete(), resetStatus()
- `modules/content-creator/controllers/GeneratorController.php` — generate methods
- `modules/seo-audit/controllers/CrawlController.php` — start()
- `modules/ads-analyzer/controllers/CampaignController.php` — evaluate()
- `modules/ads-analyzer/controllers/CampaignCreatorController.php` — write methods

---

## Finding 2 (HIGH): Credits Billed to Acting User Instead of Owner

**Problem:** Only `keyword-research/ResearchController::aiAnalyze()` bills the owner. All others bill `$user['id']`.

**Fix:** Create a shared helper and apply everywhere.

### Step 1: Add helper to ProjectAccessService

File: `services/ProjectAccessService.php`

```php
/**
 * Get the user ID to bill for credits (owner of the global project)
 */
public static function getCreditUserId(array $project, int $fallbackUserId): int
{
    if (!empty($project['global_project_id'])) {
        $ownerId = self::getOwnerId((int)$project['global_project_id']);
        if ($ownerId) return $ownerId;
    }
    return $fallbackUserId;
}
```

### Step 2: Replace Credits::consume/hasEnough calls

In every controller that consumes credits:
```php
// BEFORE:
Credits::hasEnough($user['id'], $cost);
Credits::consume($user['id'], $cost, ...);

// AFTER:
$creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
Credits::hasEnough($creditUserId, $cost);
Credits::consume($creditUserId, $cost, ...);
```

**Files (all Credits::consume calls):**
- seo-tracking: RankCheckController, KeywordController, GscController
- ai-content: WizardController, ArticleController, MetaTagController
- ads-analyzer: CampaignController, CampaignCreatorController, SearchTermAnalysisController
- content-creator: GeneratorController
- seo-audit: CrawlController

---

## Finding 3 (HIGH): ai-optimizer and seo-onpage Bypass Sharing

**Problem:** These modules use `->find($id, $user['id'])` instead of `findAccessible()`.

**Fix:** Add `findAccessible()` to their Project models and update controllers.

### Step 1: Add findAccessible() to Project models

Files:
- `modules/ai-optimizer/models/Project.php`
- `modules/seo-onpage/models/Project.php`

Copy the pattern from `modules/seo-tracking/models/Project.php::findAccessible()`.

### Step 2: Update controllers

Replace all `->find($id, $user['id'])` with `->findAccessible($id, $user['id'])`.

---

## Finding 4 (MEDIUM): No Rate Limiting on Invitation Sending

File: `controllers/GlobalProjectController.php` — `invite()` method

Add rate limiting:
```php
Middleware::rateLimit('project_invite_' . $user['id'], 10, 60);
```

---

## Finding 5 (MEDIUM): Module Access Relies on Model Layer Only

**Status:** Acceptable. The model's `findAccessible()` properly checks `canAccessModule()`. No controller-level fix needed since all project operations go through the model.

---

## Finding 6 (LOW): Race Condition in Token Acceptance

**Fix:** Add UNIQUE constraint:
```sql
ALTER TABLE project_members ADD UNIQUE KEY uk_project_user (project_id, user_id);
```

---

## Finding 7 (LOW): No vulnerability — informational only.

---

## Finding 8 (MEDIUM): Invitation Tokens Exposed in View

File: `services/ProjectAccessService.php` — `getProjectInvitations()`

Remove `pi.token` from SELECT query.

---

## Finding 9 (LOW): Insecure Re-fetch in Internal Links

File: `modules/internal-links/controllers/ProjectController.php` lines 136-143

Use `findAccessible($id, $user['id'])` instead of `findWithStats($id, (int)$project['user_id'])`.

---

## Finding 10 (MEDIUM): WP API Keys Visible to Shared Members

File: `controllers/GlobalProjectController.php` — `dashboard()`

Only show WP sites to owner:
```php
$wpSites = [];
$unlinkedWpSites = [];
if (($project['access_role'] ?? 'owner') === 'owner') {
    $wpSiteModel = new \Modules\AiContent\Models\WpSite();
    $wpSites = $wpSiteModel->getAllByProject($id);
    $unlinkedWpSites = $wpSiteModel->getUnlinkedByUser($user['id']);
}
```

---

## Summary

| # | Severity | Action |
|---|----------|--------|
| 1 | HIGH | Add viewer write guard to all module controllers |
| 2 | HIGH | Route credits to project owner via helper |
| 3 | HIGH | Add findAccessible() to ai-optimizer/seo-onpage |
| 4 | MEDIUM | Rate limit invitations |
| 5 | MEDIUM | No fix needed (model layer sufficient) |
| 6 | LOW | UNIQUE constraint on project_members |
| 7 | LOW | No fix needed |
| 8 | MEDIUM | Remove token from invitation query |
| 9 | LOW | Fix re-fetch pattern |
| 10 | MEDIUM | Hide WP sites from non-owners |
