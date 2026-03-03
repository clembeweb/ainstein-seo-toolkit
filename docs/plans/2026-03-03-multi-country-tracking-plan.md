# Multi-Country Keyword Tracking Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add country-first navigation to SEO Tracking with global summary bar, country tabs, and per-country filtered views.

**Architecture:** Query param `?country=XX` drives all filtering. A new reusable partial `country-bar.php` provides summary cards + tab navigation. All controllers read the country param and pass it through to model queries and VisibilityService. No DB migrations — `st_keywords.location_code` already exists.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js, Chart.js

**Design doc:** `docs/plans/2026-03-03-multi-country-tracking-design.md`

---

### Task 1: Add country filtering to Keyword model

**Files:**
- Modify: `modules/seo-tracking/models/Keyword.php`

**Context:** The `allWithPositions()` method (line 86) already accepts a `$filters` array but doesn't filter by `location_code`. We need to add this filter so all downstream consumers (dashboard, keywords, API) can filter by country.

**Step 1: Add location_code filter to allWithPositions()**

In `modules/seo-tracking/models/Keyword.php`, find the filters section after the WHERE clause (around line 129). Add a new filter block for `location_code`:

```php
// After existing filter blocks, add:
if (!empty($filters['location_code'])) {
    $sql .= " AND k.location_code = ?";
    $params[] = $filters['location_code'];
}
```

**Step 2: Add helper method to get active countries**

Add a new method to the Keyword model that returns the distinct countries with keyword counts for a project:

```php
/**
 * Restituisce le country attive (con almeno 1 keyword tracciata) per un progetto.
 */
public function getActiveCountries(int $projectId): array
{
    return Database::fetchAll("
        SELECT
            k.location_code,
            l.name as country_name,
            COUNT(*) as keyword_count
        FROM {$this->table} k
        LEFT JOIN st_locations l ON l.country_code = k.location_code
        WHERE k.project_id = ? AND k.is_tracked = 1
        GROUP BY k.location_code, l.name
        ORDER BY COUNT(*) DESC
    ", [$projectId]);
}
```

**Step 3: Verify syntax**

Run: `php -l modules/seo-tracking/models/Keyword.php`

**Step 4: Commit**

```bash
git add modules/seo-tracking/models/Keyword.php
git commit -m "feat(seo-tracking): add location_code filter and getActiveCountries to Keyword model"
```

---

### Task 2: Add country-summary API endpoint

**Files:**
- Modify: `modules/seo-tracking/controllers/ApiController.php`
- Modify: `modules/seo-tracking/routes.php`

**Context:** The country summary bar needs data from a new API endpoint. Also, existing API endpoints need to accept `?country=XX` filter.

**Step 1: Add countrySummary() method to ApiController**

In `modules/seo-tracking/controllers/ApiController.php`, add after the existing methods:

```php
/**
 * GET /api/project/{id}/country-summary
 * Restituisce metriche aggregate per ogni country attiva.
 */
public function countrySummary(int $id): string
{
    if (!$this->checkProject($id)) {
        return View::json(['error' => 'Progetto non trovato'], 404);
    }

    $countries = $this->keyword->getActiveCountries($id);
    $result = [];

    foreach ($countries as $country) {
        $countryCode = $country['location_code'];
        $keywords = $this->keyword->allWithPositions($id, 30, ['location_code' => $countryCode]);
        $tracked = array_filter($keywords, fn($k) => !empty($k['is_tracked']));

        $visibility = \Modules\SeoTracking\Services\VisibilityService::calculateVisibility($tracked);
        $estTraffic = \Modules\SeoTracking\Services\VisibilityService::calculateEstTraffic($tracked);

        $positions = array_filter(array_map(fn($k) => (int)($k['last_position'] ?? 0), $tracked), fn($p) => $p > 0);
        $avgPosition = !empty($positions) ? round(array_sum($positions) / count($positions), 1) : 0;

        $result[] = [
            'country_code' => $countryCode,
            'country_name' => $country['country_name'] ?? $countryCode,
            'keyword_count' => (int) $country['keyword_count'],
            'visibility' => $visibility,
            'est_traffic' => round($estTraffic, 1),
            'avg_position' => $avgPosition,
        ];
    }

    return View::json($result);
}
```

**Step 2: Add ?country filter to visibilityStats()**

In the existing `visibilityStats()` method (line 237), change:

```php
// Before:
$keywords = $this->keyword->allWithPositions($id, 30);

// After:
$country = $_GET['country'] ?? null;
$filters = $country ? ['location_code' => $country] : [];
$keywords = $this->keyword->allWithPositions($id, 30, $filters);
```

Apply the same pattern to `distributionHistory()`, `visibilityTrend()`, and `keywordsCompare()` — each should read `$_GET['country']` and pass it as a filter to VisibilityService methods.

For `distributionHistory()` and `visibilityTrend()`, pass country to VisibilityService:

```php
$country = $_GET['country'] ?? null;
$data = VisibilityService::getDistributionOverTime($id, $days, $country);
```

This requires adding an optional `$country` parameter to `VisibilityService::getDistributionOverTime()` and `getVisibilityTrend()`.

**Step 3: Update VisibilityService for country filter**

In `modules/seo-tracking/services/VisibilityService.php`, modify `getDistributionOverTime()` and `getVisibilityTrend()` to accept an optional `?string $country = null` parameter. Add `AND k.location_code = ?` to their SQL queries when country is provided.

**Step 4: Add route**

In `modules/seo-tracking/routes.php`, add:

```php
Router::get('/seo-tracking/api/project/{id}/country-summary', function ($id) {
    return (new \Modules\SeoTracking\Controllers\ApiController())->countrySummary((int)$id);
});
```

**Step 5: Verify and commit**

```bash
php -l modules/seo-tracking/controllers/ApiController.php
php -l modules/seo-tracking/services/VisibilityService.php
php -l modules/seo-tracking/routes.php
git add modules/seo-tracking/controllers/ApiController.php modules/seo-tracking/routes.php modules/seo-tracking/services/VisibilityService.php
git commit -m "feat(seo-tracking): add country-summary endpoint and country filter to all API endpoints"
```

---

### Task 3: Create country-bar.php partial

**Files:**
- Create: `modules/seo-tracking/views/partials/country-bar.php`

**Context:** Reusable component that shows: (1) country summary cards when 2+ countries exist, (2) country tab bar with [All] + per-country tabs. Receives `$countries` array and `$activeCountry` string from controller.

**Step 1: Create the partial**

Create `modules/seo-tracking/views/partials/country-bar.php`:

```php
<?php
/**
 * Country bar — summary cards + country tabs.
 *
 * Variables:
 * - $countries: array from Keyword::getActiveCountries() enriched with metrics
 * - $activeCountry: string|null (null = All)
 * - $project: array (project data)
 * - $currentPage: string (for URL building)
 */

$countryFlags = [
    'IT' => '🇮🇹', 'US' => '🇺🇸', 'GB' => '🇬🇧', 'DE' => '🇩🇪', 'FR' => '🇫🇷',
    'ES' => '🇪🇸', 'CH' => '🇨🇭', 'AT' => '🇦🇹', 'NL' => '🇳🇱', 'BE' => '🇧🇪',
    'PT' => '🇵🇹', 'BR' => '🇧🇷', 'CA' => '🇨🇦', 'AU' => '🇦🇺', 'MX' => '🇲🇽',
    'AR' => '🇦🇷', 'CL' => '🇨🇱', 'CO' => '🇨🇴', 'PL' => '🇵🇱', 'SE' => '🇸🇪',
];

// Build base URL for current page
$pageRoutes = [
    'overview' => '/seo-tracking/project/' . $project['id'],
    'keywords' => '/seo-tracking/project/' . $project['id'] . '/keywords',
    'urls' => '/seo-tracking/project/' . $project['id'] . '/urls',
    'groups' => '/seo-tracking/project/' . $project['id'] . '/groups',
];
$baseUrl = url($pageRoutes[$currentPage] ?? $pageRoutes['overview']);

if (count($countries) < 2) return; // Single country: no bar needed
?>

<!-- Country Summary Cards -->
<div class="flex flex-wrap gap-3 mb-4">
    <?php foreach ($countries as $c):
        $flag = $countryFlags[$c['country_code']] ?? '🌐';
        $isActive = $activeCountry === $c['country_code'];
    ?>
    <a href="<?= $baseUrl ?>?country=<?= e($c['country_code']) ?>"
       class="flex items-center gap-2 px-3 py-2 rounded-lg border transition-all text-sm
              <?= $isActive
                  ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-300 dark:border-blue-700 ring-1 ring-blue-200 dark:ring-blue-800'
                  : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700' ?>">
        <span class="text-base"><?= $flag ?></span>
        <span class="font-semibold text-slate-900 dark:text-white"><?= e($c['country_code']) ?></span>
        <span class="text-slate-500 dark:text-slate-400"><?= number_format($c['visibility'] ?? 0, 1) ?>%</span>
        <span class="text-xs text-slate-400 dark:text-slate-500"><?= $c['keyword_count'] ?> kw</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Country Tabs -->
<div class="flex items-center gap-1 mb-4 border-b border-slate-200 dark:border-slate-700 pb-px">
    <a href="<?= $baseUrl ?>"
       class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors
              <?= $activeCountry === null
                  ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 -mb-px'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        All
    </a>
    <?php foreach ($countries as $c):
        $flag = $countryFlags[$c['country_code']] ?? '🌐';
        $isActive = $activeCountry === $c['country_code'];
    ?>
    <a href="<?= $baseUrl ?>?country=<?= e($c['country_code']) ?>"
       class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors flex items-center gap-1
              <?= $isActive
                  ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 -mb-px'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' ?>">
        <span class="text-xs"><?= $flag ?></span>
        <?= e($c['country_code']) ?>
    </a>
    <?php endforeach; ?>
</div>
```

**Step 2: Verify and commit**

```bash
php -l modules/seo-tracking/views/partials/country-bar.php
git add modules/seo-tracking/views/partials/country-bar.php
git commit -m "feat(seo-tracking): create country-bar.php partial with summary cards and tabs"
```

---

### Task 4: Wire country filtering into DashboardController

**Files:**
- Modify: `modules/seo-tracking/controllers/DashboardController.php`

**Context:** The `index()` method (line 44) loads keywords and computes metrics. We need to: (1) read `$_GET['country']`, (2) load active countries with metrics, (3) filter all keyword queries by country, (4) pass `$countries` and `$activeCountry` to view.

**Step 1: Modify index() method**

At the start of the method (after project access check, around line 55), add:

```php
// Country filtering
$activeCountry = $_GET['country'] ?? null;
$activeCountries = $this->keyword->getActiveCountries($projectId);

// Enrich countries with visibility metrics for summary bar
$countriesWithMetrics = [];
foreach ($activeCountries as $c) {
    $cKeywords = array_filter($keywords, fn($k) => ($k['location_code'] ?? 'IT') === $c['location_code']);
    $countriesWithMetrics[] = [
        'country_code' => $c['location_code'],
        'country_name' => $c['country_name'],
        'keyword_count' => $c['keyword_count'],
        'visibility' => \Modules\SeoTracking\Services\VisibilityService::calculateVisibility(array_values($cKeywords)),
    ];
}
```

Then change the keywords loading (line 60-61) from:

```php
$keywords = $this->keyword->allWithPositions($projectId, 30);
$trackedKeywords = array_filter($keywords, fn($k) => !empty($k['is_tracked']));
```

To:

```php
$filters = $activeCountry ? ['location_code' => $activeCountry] : [];
$keywords = $this->keyword->allWithPositions($projectId, 30, $filters);
$trackedKeywords = array_filter($keywords, fn($k) => !empty($k['is_tracked']));
```

Also pass `$activeCountry` to `getDistributionOverTime()`:

```php
// Before:
$distributionHistory = VisibilityService::getDistributionOverTime($projectId, 30);

// After:
$distributionHistory = VisibilityService::getDistributionOverTime($projectId, 30, $activeCountry);
```

**Step 2: Add to View::render()**

Add these to the existing View::render data array (around line 102):

```php
'countries' => $countriesWithMetrics,
'activeCountry' => $activeCountry,
```

**Step 3: Verify and commit**

```bash
php -l modules/seo-tracking/controllers/DashboardController.php
git add modules/seo-tracking/controllers/DashboardController.php
git commit -m "feat(seo-tracking): add country filtering to DashboardController"
```

---

### Task 5: Wire country filtering into KeywordController

**Files:**
- Modify: `modules/seo-tracking/controllers/KeywordController.php`

**Context:** Same pattern as Dashboard — read `?country`, filter keywords, pass countries to view.

**Step 1: Modify index() method**

After project access check (around line 55), add:

```php
$activeCountry = $_GET['country'] ?? null;
$activeCountries = $this->keyword->getActiveCountries($projectId);
```

Add `location_code` to the `$filters` array:

```php
if ($activeCountry) {
    $filters['location_code'] = $activeCountry;
}
```

Pass the country to visibilityTrend:

```php
// Before:
'visibilityTrend' => VisibilityService::getVisibilityTrend($projectId, 30),

// After:
'visibilityTrend' => VisibilityService::getVisibilityTrend($projectId, 30, $activeCountry),
```

Add to View::render data:

```php
'countries' => $activeCountries, // enrichment not needed here — summary bar uses API
'activeCountry' => $activeCountry,
```

**Step 2: Modify add() method**

At line 686, make the dropdown default to the active country from query param:

```php
$defaultCountry = $_GET['country'] ?? 'IT';
```

Pass to view:

```php
'defaultCountry' => $defaultCountry,
```

**Step 3: Verify and commit**

```bash
php -l modules/seo-tracking/controllers/KeywordController.php
git add modules/seo-tracking/controllers/KeywordController.php
git commit -m "feat(seo-tracking): add country filtering to KeywordController"
```

---

### Task 6: Update Dashboard view with country bar

**Files:**
- Modify: `modules/seo-tracking/views/dashboard/index.php`

**Context:** Include the country-bar partial at the top of the dashboard, after the project-nav. The summary bar data comes from the enriched `$countries` array passed by the controller.

**Step 1: Include country-bar partial**

After the project-nav include (first few lines), add:

```php
<?php View::partial('seo-tracking/partials/country-bar', [
    'countries' => $countries ?? [],
    'activeCountry' => $activeCountry ?? null,
    'project' => $project,
    'currentPage' => 'overview',
]); ?>
```

**Step 2: Verify and commit**

```bash
php -l modules/seo-tracking/views/dashboard/index.php
git add modules/seo-tracking/views/dashboard/index.php
git commit -m "feat(seo-tracking): include country bar in Landscape dashboard"
```

---

### Task 7: Update Keywords view with country bar

**Files:**
- Modify: `modules/seo-tracking/views/keywords/index.php`

**Context:** Same pattern — include country-bar after project-nav. Also make the "Aggiungi Keyword" link preserve the country context.

**Step 1: Include country-bar partial**

After the project-nav include, add:

```php
<?php View::partial('seo-tracking/partials/country-bar', [
    'countries' => $countries ?? [],
    'activeCountry' => $activeCountry ?? null,
    'project' => $project,
    'currentPage' => 'keywords',
]); ?>
```

**Step 2: Update "Aggiungi Keyword" link**

Find the `Aggiungi Keyword` button (around line 92) and add country param:

```php
// Before:
href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/add') ?>"

// After:
href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/add') ?><?= !empty($activeCountry) ? '?country=' . e($activeCountry) : '' ?>"
```

**Step 3: Update create.php to use default country**

In `modules/seo-tracking/views/keywords/create.php`, change the location select default (line 39):

```php
// Before:
<?= ($loc['country_code'] === 'IT') ? 'selected' : '' ?>

// After:
<?= ($loc['country_code'] === ($defaultCountry ?? 'IT')) ? 'selected' : '' ?>
```

**Step 4: Verify and commit**

```bash
php -l modules/seo-tracking/views/keywords/index.php
php -l modules/seo-tracking/views/keywords/create.php
git add modules/seo-tracking/views/keywords/index.php modules/seo-tracking/views/keywords/create.php
git commit -m "feat(seo-tracking): include country bar in Overview keywords and wire add-keyword country context"
```

---

### Task 8: Update project widget for multi-country

**Files:**
- Modify: `modules/seo-tracking/models/Project.php`

**Context:** The `getProjectKpi()` method in the global project dashboard should show a compact multi-country breakdown when the project has 2+ countries.

**Step 1: Enhance getProjectKpi()**

In the existing `getProjectKpi()` method, after computing current metrics, add country breakdown:

```php
// Country breakdown for widget
$activeCountries = Database::fetchAll("
    SELECT location_code, COUNT(*) as kw_count
    FROM st_keywords
    WHERE project_id = ? AND is_tracked = 1
    GROUP BY location_code
    ORDER BY COUNT(*) DESC
", [$projectId]);

if (count($activeCountries) > 1) {
    $metrics[] = ['label' => 'Country', 'value' => count($activeCountries)];
}
```

**Step 2: Verify and commit**

```bash
php -l modules/seo-tracking/models/Project.php
git add modules/seo-tracking/models/Project.php
git commit -m "feat(seo-tracking): show country count in project widget when multi-country"
```

---

### Task 9: Browser test and verification

**Step 1: Verify all PHP syntax**

```bash
php -l modules/seo-tracking/models/Keyword.php
php -l modules/seo-tracking/controllers/ApiController.php
php -l modules/seo-tracking/controllers/DashboardController.php
php -l modules/seo-tracking/controllers/KeywordController.php
php -l modules/seo-tracking/services/VisibilityService.php
php -l modules/seo-tracking/views/partials/country-bar.php
php -l modules/seo-tracking/views/dashboard/index.php
php -l modules/seo-tracking/views/keywords/index.php
php -l modules/seo-tracking/views/keywords/create.php
php -l modules/seo-tracking/models/Project.php
php -l modules/seo-tracking/routes.php
```

**Step 2: Browser test**

1. Navigate to `http://localhost/seo-toolkit/seo-tracking/project/5` (or production)
2. Verify: with single country (IT), no country bar shows
3. Add a test keyword with country US via `/keywords/add`
4. Return to dashboard — country summary bar should appear with [IT] [US] cards
5. Click [US] tab — dashboard should show only US metrics
6. Click [All] tab — dashboard should show aggregated metrics
7. Navigate to Overview (keywords) — country bar should appear there too
8. Test API: `/api/project/5/country-summary` should return JSON with per-country metrics

**Step 3: Final commit if any fixes needed**
