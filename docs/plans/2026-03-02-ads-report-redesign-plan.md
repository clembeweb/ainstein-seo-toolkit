# Ads Report Redesign - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the ads-analyzer campaign evaluation report from a single-column accordion layout to a tabbed dashboard with period selector and campaign card grid with drawer.

**Architecture:** The view file `evaluation.php` (~1194 lines) is rewritten into a tabbed dashboard. A new `availableRuns()` controller method and route provide run data for the period selector. The `evaluationShow()` controller is extended to accept a `?run_id=` parameter. No DB schema changes. No AI prompt changes.

**Tech Stack:** PHP 8+, Alpine.js (tabs, drawer, generators), Tailwind CSS, existing View/Database/Router framework.

---

### Task 1: Add `getCompletedRunsByProject()` to ScriptRun model

**Files:**
- Modify: `modules/ads-analyzer/models/ScriptRun.php:44-50`

**Step 1: Add new method to ScriptRun model**

Add after the existing `getByProject` method (line 50):

```php
/**
 * Get completed campaign runs grouped by period duration
 */
public static function getCompletedCampaignRuns(int $projectId, int $limit = 20): array
{
    return Database::fetchAll(
        "SELECT r.*, DATEDIFF(r.date_range_end, r.date_range_start) as period_days,
                (SELECT e.id FROM ga_campaign_evaluations e
                 WHERE e.run_id = r.id AND e.status = 'completed'
                 ORDER BY e.created_at DESC LIMIT 1) as evaluation_id
         FROM ga_script_runs r
         WHERE r.project_id = ?
           AND r.status = 'completed'
           AND (r.run_type = 'campaign_performance' OR r.run_type = 'both')
           AND r.date_range_start IS NOT NULL
           AND r.date_range_end IS NOT NULL
         ORDER BY r.created_at DESC
         LIMIT ?",
        [$projectId, $limit]
    );
}
```

**Step 2: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/models/ScriptRun.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/ads-analyzer/models/ScriptRun.php
git commit -m "feat(ads-analyzer): add getCompletedCampaignRuns() for period selector"
```

---

### Task 2: Add `availableRuns()` method to CampaignController + route

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` (add method after `toggleAutoEvaluate` at ~line 438)
- Modify: `modules/ads-analyzer/routes.php` (add route after line 210)

**Step 1: Add availableRuns method to CampaignController**

Add after the `toggleAutoEvaluate()` method (after line 438):

```php
/**
 * Available runs grouped by period for the period selector (AJAX)
 */
public function availableRuns(int $projectId): void
{
    header('Content-Type: application/json');

    $user = Auth::user();
    $project = Project::findAccessible($user['id'], $projectId);

    if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
        http_response_code(400);
        echo json_encode(['error' => 'Progetto non valido']);
        exit;
    }

    $runs = ScriptRun::getCompletedCampaignRuns($projectId, 30);

    // Group by closest period bucket (7, 14, 30 days)
    $periods = ['7' => null, '14' => null, '30' => null];
    foreach ($runs as $run) {
        $days = (int)($run['period_days'] ?? 0);
        $bucket = null;
        if ($days >= 5 && $days <= 9) $bucket = '7';
        elseif ($days >= 12 && $days <= 16) $bucket = '14';
        elseif ($days >= 25 && $days <= 35) $bucket = '30';

        if ($bucket && $periods[$bucket] === null) {
            $periods[$bucket] = [
                'available' => true,
                'run_id' => (int)$run['id'],
                'date_start' => $run['date_range_start'],
                'date_end' => $run['date_range_end'],
                'period_days' => $days,
                'has_evaluation' => !empty($run['evaluation_id']),
                'evaluation_id' => $run['evaluation_id'] ? (int)$run['evaluation_id'] : null,
            ];
        }
    }

    // Fill unavailable periods
    foreach ($periods as $key => &$val) {
        if ($val === null) {
            $val = ['available' => false, 'run_id' => null, 'has_evaluation' => false, 'evaluation_id' => null];
        }
    }

    echo json_encode(['periods' => $periods]);
    exit;
}
```

**Step 2: Add route in routes.php**

Add after line 210 (after toggle-auto-evaluate route):

```php
// Run disponibili per selettore periodo (AJAX)
Router::get('/ads-analyzer/projects/{id}/campaigns/available-runs', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->availableRuns((int) $id);
});
```

**Step 3: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/controllers/CampaignController.php && php -l modules/ads-analyzer/routes.php`
Expected: `No syntax errors detected` (x2)

**Step 4: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php modules/ads-analyzer/routes.php
git commit -m "feat(ads-analyzer): add available-runs endpoint for period selector"
```

---

### Task 3: Extend `evaluationShow()` to support run_id parameter + pass available_runs

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php:443-478`

**Step 1: Modify evaluationShow method**

Replace the current `evaluationShow` method (lines 443-478) with:

```php
/**
 * Mostra risultato valutazione AI
 */
public function evaluationShow(int $projectId, int $evalId): string
{
    $user = Auth::user();
    $project = Project::findAccessible($user['id'], $projectId);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    if (($project['type'] ?? 'negative-kw') !== 'campaign') {
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}"));
        exit;
    }

    $evaluation = CampaignEvaluation::find($evalId);
    if (!$evaluation || $evaluation['project_id'] != $projectId) {
        $_SESSION['flash_error'] = 'Valutazione non trovata';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns"));
        exit;
    }

    $aiResponse = json_decode($evaluation['ai_response'] ?? '{}', true) ?: [];

    // Get run info for this evaluation
    $currentRun = null;
    if (!empty($evaluation['run_id'])) {
        $currentRun = ScriptRun::find($evaluation['run_id']);
    }

    // Available runs for period selector
    $availableRuns = ScriptRun::getCompletedCampaignRuns($projectId, 30);
    $periods = ['7' => null, '14' => null, '30' => null];
    $currentPeriod = null;

    foreach ($availableRuns as $run) {
        $days = (int)($run['period_days'] ?? 0);
        $bucket = null;
        if ($days >= 5 && $days <= 9) $bucket = '7';
        elseif ($days >= 12 && $days <= 16) $bucket = '14';
        elseif ($days >= 25 && $days <= 35) $bucket = '30';

        if ($bucket && $periods[$bucket] === null) {
            $periods[$bucket] = [
                'available' => true,
                'run_id' => (int)$run['id'],
                'has_evaluation' => !empty($run['evaluation_id']),
                'evaluation_id' => $run['evaluation_id'] ? (int)$run['evaluation_id'] : null,
            ];
        }

        // Detect current period
        if ($currentRun && (int)$run['id'] === (int)$currentRun['id'] && $bucket) {
            $currentPeriod = $bucket;
        }
    }

    foreach ($periods as $key => &$val) {
        if ($val === null) {
            $val = ['available' => false, 'run_id' => null, 'has_evaluation' => false, 'evaluation_id' => null];
        }
    }

    return View::render('ads-analyzer/campaigns/evaluation', [
        'title' => 'Valutazione Campagne - ' . $project['name'],
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'evaluation' => $evaluation,
        'aiResponse' => $aiResponse,
        'generateUrl' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}/generate"),
        'access_role' => $project['access_role'] ?? 'owner',
        'currentRun' => $currentRun,
        'periods' => $periods,
        'currentPeriod' => $currentPeriod,
    ]);
}
```

**Step 2: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/controllers/CampaignController.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): pass period data and available runs to evaluation view"
```

---

### Task 4: Rewrite evaluation.php — Header + KPI + Tab skeleton

This is the main task. The view file `evaluation.php` (~1194 lines) is rewritten completely. Since it's a large file, we split into sub-steps.

**Files:**
- Rewrite: `modules/ads-analyzer/views/campaigns/evaluation.php`

**Step 1: Write the new evaluation.php — Part 1 (PHP prologue + helpers + error/analyzing/no-change states)**

Keep the existing PHP prologue (lines 1-111: helpers, `$areaLabels`, `$campaignTypeConfig`, score functions, `renderAiGenerator()`), error/analyzing/no-change states (lines 117-261) **exactly as they are**.

Replace everything from line 263 (`<?php else: ?>`) through line 1194 (end of file) with the new tabbed dashboard layout.

The new structure from line 263 onwards:

```
<?php else: ?>
<!-- ============================================ -->
<!-- TABBED DASHBOARD RESULTS -->
<!-- ============================================ -->

[PHP variables extraction - same as before]

<div x-data="evaluationDashboard()" class="space-y-6">

  <!-- HEADER: Score + Summary + Period Selector + Actions -->
  [header section]

  <!-- KPI Cards -->
  [6-card grid for metric deltas]

  <!-- Tab Navigation -->
  [5 tabs: panoramica, campagne, estensioni, landing, azioni]

  <!-- Tab: Panoramica -->
  [trend, top recommendations, issues heatmap, campaign scores]

  <!-- Tab: Campagne (card grid) -->
  [campaign cards + drawer]

  <!-- Tab: Estensioni -->
  [existing extensions section, slightly reorganized]

  <!-- Tab: Landing Pages -->
  [existing landing section, slightly reorganized]

  <!-- Tab: Azioni -->
  [campaign suggestions + landing suggestions + AI generators]

  <!-- Drawer for campaign detail -->
  [slide-in from right with backdrop]

</div>

<!-- Alpine.js component -->
<script>
function evaluationDashboard() { ... }
</script>

<?php endif; ?>
```

**IMPORTANT IMPLEMENTATION NOTES:**

1. **Preserve all existing functionality**: Every piece of data currently displayed must still be accessible somewhere in the new layout. Nothing is removed, it's reorganized.

2. **Alpine.js component**: Rename from `evaluationFixes()` to `evaluationDashboard()` which includes both the tab management AND the existing `generators`/`generateFix`/`copyResult`/`exportCsv` logic.

3. **Period selector**: Uses the `$periods` and `$currentPeriod` PHP variables passed from controller. Clicking a different period navigates to the evaluation for that period's run. If no evaluation exists for that run, show a tooltip/message.

4. **Drawer**: Alpine.js `selectedCampaign` state. Click on campaign card sets it. Drawer slides in from right with `x-transition`. Backdrop click or X button closes.

5. **Issues heatmap**: A simple table/grid showing campaign names × severity counts, generated from the `$campaigns` data.

6. **Tab content**: Each tab is a `<div x-show="activeTab === 'name'">`  block.

7. **CSS**: Follow Ainstein standard — `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`, Heroicons SVG only.

8. **All existing PHP variables** (`$overallScore`, `$summary`, `$campaigns`, `$topRecommendations`, `$extensionsEval`, `$landingEval`, `$campaignSuggestions`, `$landingSuggestions`, `$trend`, `$changesSummary`, `$newIssues`, `$resolvedIssues`, `$evalType`, `$metricDeltas`) remain extracted at top of results section exactly as before.

**Step 2: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/views/campaigns/evaluation.php`
Expected: `No syntax errors detected`

**Step 3: Visual test in browser**

Navigate to: `http://localhost/seo-toolkit/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}`
Verify:
- Header shows score, summary, trend, metadata
- Period selector shows available periods (buttons for 7g/14g/30g)
- KPI cards show metric deltas
- 5 tabs are visible and clickable
- Panoramica tab: shows top recommendations, issues overview, campaign scores
- Campagne tab: shows card grid, clicking a card opens the drawer
- Drawer: shows all campaign details (strengths, issues, ad groups)
- Estensioni tab: shows extensions evaluation
- Landing tab: shows landing pages evaluation
- Azioni tab: shows suggestions tables and AI generation buttons
- AI generation buttons work (test one)
- PDF export link works
- Dark mode works
- Back link works
- Error/analyzing/no-change states still work (from other evaluations)

**Step 4: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation.php
git commit -m "feat(ads-analyzer): redesign evaluation report with tabbed dashboard and campaign drawer"
```

---

### Task 5: Test period selector navigation

**Files:**
- No new files — testing existing implementation

**Step 1: Test available-runs endpoint**

Navigate to: `http://localhost/seo-toolkit/ads-analyzer/projects/{id}/campaigns/available-runs`
Verify JSON response with periods object.

**Step 2: Test period switching**

If multiple runs with different periods exist, test switching between them in the UI.
If only one period exists, verify other period buttons are disabled.

**Step 3: Test evaluation without run_id**

Navigate to an evaluation URL without `?run_id=` — should work exactly as before (backwards compatible).

---

### Task 6: Final review and cleanup

**Files:**
- All modified files

**Step 1: Run PHP syntax check on all modified files**

```bash
php -l modules/ads-analyzer/models/ScriptRun.php
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/routes.php
php -l modules/ads-analyzer/views/campaigns/evaluation.php
```

**Step 2: Check Ainstein golden rules compliance**

- [ ] Heroicons SVG only (no Lucide/FontAwesome)
- [ ] Italian UI text
- [ ] CSS standard: rounded-xl, px-4 py-3, dark:bg-slate-700/50
- [ ] return View::render() with 'user' => $user
- [ ] CSRF on POST routes
- [ ] Prepared statements in SQL queries

**Step 3: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix(ads-analyzer): post-review cleanup for report redesign"
```

---

## Summary of Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `modules/ads-analyzer/models/ScriptRun.php` | Add method | `getCompletedCampaignRuns()` for period selector |
| `modules/ads-analyzer/controllers/CampaignController.php` | Add method + modify | `availableRuns()` endpoint + extend `evaluationShow()` with period data |
| `modules/ads-analyzer/routes.php` | Add route | GET `/available-runs` endpoint |
| `modules/ads-analyzer/views/campaigns/evaluation.php` | Rewrite | Tabbed dashboard with card grid + drawer replacing accordion layout |

**No changes to:** DB schema, AI prompts, PDF export template, auto-eval system, `evaluate()`, `generateFix()`, `exportPdf()`, `exportCsv()`.
