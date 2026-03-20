# SEO Tracking Redesign (Semrush-Style) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the seo-tracking module UI to match Semrush Position Tracking — new Visibility/Traffic metrics, stacked bar chart, enriched keyword table with date comparison, and tab renaming.

**Architecture:** Refactor incremental — modify existing views and controllers in-place, add one new service (`VisibilityService`) for CTR/visibility calculations, and 4 new API endpoints for chart data. No new database tables required.

**Tech Stack:** PHP 8+, Chart.js, Tailwind CSS, Alpine.js, MySQL

**Design doc:** `docs/plans/2026-03-03-seo-tracking-redesign-design.md`

---

## Task 1: Create VisibilityService

**Files:**
- Create: `modules/seo-tracking/services/VisibilityService.php`

**Step 1: Create the service file**

```php
<?php

namespace Modules\SeoTracking\Services;

use Core\Database;

/**
 * VisibilityService
 * Calculates Visibility Score and Estimated Traffic using standard CTR curves.
 * Reference: Advanced Web Ranking 2024 CTR study.
 */
class VisibilityService
{
    /**
     * Standard CTR curve by SERP position (AWR 2024)
     */
    private const CTR_CURVE = [
        1 => 0.317, 2 => 0.247, 3 => 0.186,
        4 => 0.136, 5 => 0.095, 6 => 0.062,
        7 => 0.042, 8 => 0.032, 9 => 0.028, 10 => 0.026,
        11 => 0.019, 12 => 0.017, 13 => 0.015, 14 => 0.013, 15 => 0.012,
        16 => 0.010, 17 => 0.009, 18 => 0.008, 19 => 0.007, 20 => 0.006,
    ];

    /**
     * Get estimated CTR for a given SERP position
     */
    public static function getCtrForPosition(int $position): float
    {
        if ($position <= 0 || $position > 100) {
            return 0.0;
        }
        if (isset(self::CTR_CURVE[$position])) {
            return self::CTR_CURVE[$position];
        }
        // Positions 21-50: 0.5% decaying
        if ($position <= 50) {
            return max(0.001, 0.005 - (($position - 21) * 0.0001));
        }
        // Positions 51-100: 0.1%
        return 0.001;
    }

    /**
     * Calculate Visibility Score % for a set of keywords
     *
     * @param array $keywords Each must have 'last_position' (int|null)
     * @return float Visibility percentage (0-100)
     */
    public static function calculateVisibility(array $keywords): float
    {
        if (empty($keywords)) {
            return 0.0;
        }

        $totalCtr = 0.0;
        $count = 0;

        foreach ($keywords as $kw) {
            $pos = (int) ($kw['last_position'] ?? 0);
            if ($pos > 0) {
                $totalCtr += self::getCtrForPosition($pos);
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        // Visibility = average CTR across all tracked keywords * 100
        return round(($totalCtr / $count) * 100, 2);
    }

    /**
     * Calculate Estimated Traffic for a set of keywords
     *
     * @param array $keywords Each must have 'last_position' and 'search_volume'
     * @return float Estimated monthly organic traffic
     */
    public static function calculateEstTraffic(array $keywords): float
    {
        $traffic = 0.0;

        foreach ($keywords as $kw) {
            $pos = (int) ($kw['last_position'] ?? 0);
            $vol = (int) ($kw['search_volume'] ?? 0);

            if ($pos > 0 && $vol > 0) {
                $traffic += $vol * self::getCtrForPosition($pos);
            }
        }

        return round($traffic, 1);
    }

    /**
     * Calculate visibility for a single keyword position
     */
    public static function calculateKeywordVisibility(int $position): float
    {
        if ($position <= 0) {
            return 0.0;
        }
        return round(self::getCtrForPosition($position) * 100, 3);
    }

    /**
     * Calculate estimated traffic for a single keyword
     */
    public static function calculateKeywordEstTraffic(int $volume, int $position): float
    {
        if ($position <= 0 || $volume <= 0) {
            return 0.0;
        }
        return round($volume * self::getCtrForPosition($position), 2);
    }

    /**
     * Get rankings distribution over time (for stacked bar chart)
     * Returns daily bucket counts for the last N days.
     *
     * @return array [['date' => '2026-03-01', 'top3' => 2, 'top10' => 5, ...], ...]
     */
    public static function getDistributionOverTime(int $projectId, int $days = 30): array
    {
        // Use st_keyword_positions for historical data
        // For each date, count keywords in each position bucket
        return Database::fetchAll(
            "SELECT
                DATE(date) as date,
                SUM(CASE WHEN avg_position > 0 AND avg_position <= 3 THEN 1 ELSE 0 END) as top3,
                SUM(CASE WHEN avg_position > 3 AND avg_position <= 10 THEN 1 ELSE 0 END) as top4_10,
                SUM(CASE WHEN avg_position > 10 AND avg_position <= 20 THEN 1 ELSE 0 END) as top11_20,
                SUM(CASE WHEN avg_position > 20 AND avg_position <= 50 THEN 1 ELSE 0 END) as top21_50,
                SUM(CASE WHEN avg_position > 50 AND avg_position <= 100 THEN 1 ELSE 0 END) as top51_100,
                SUM(CASE WHEN avg_position > 100 OR avg_position IS NULL THEN 1 ELSE 0 END) as out_of_top
            FROM st_keyword_positions
            WHERE project_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(date)
            ORDER BY date ASC",
            [$projectId, $days]
        );
    }

    /**
     * Get visibility trend over time (for line chart)
     * Calculates daily visibility score, est. traffic, and avg position.
     *
     * @return array [['date' => '2026-03-01', 'visibility' => 12.5, 'est_traffic' => 45.2, 'avg_position' => 18.3], ...]
     */
    public static function getVisibilityTrend(int $projectId, int $days = 30): array
    {
        // Get positions + volumes for each day
        $rows = Database::fetchAll(
            "SELECT
                kp.date,
                kp.avg_position,
                k.search_volume
            FROM st_keyword_positions kp
            JOIN st_keywords k ON kp.keyword_id = k.id
            WHERE kp.project_id = ?
              AND kp.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND kp.avg_position > 0
            ORDER BY kp.date ASC",
            [$projectId, $days]
        );

        // Group by date and calculate metrics
        $byDate = [];
        foreach ($rows as $row) {
            $date = $row['date'];
            if (!isset($byDate[$date])) {
                $byDate[$date] = ['positions' => [], 'volumes' => []];
            }
            $byDate[$date]['positions'][] = (float) $row['avg_position'];
            $byDate[$date]['volumes'][] = [
                'position' => (int) round((float) $row['avg_position']),
                'volume' => (int) ($row['search_volume'] ?? 0),
            ];
        }

        $result = [];
        foreach ($byDate as $date => $data) {
            $totalCtr = 0;
            $estTraffic = 0;
            $count = count($data['positions']);

            foreach ($data['volumes'] as $kw) {
                $ctr = self::getCtrForPosition($kw['position']);
                $totalCtr += $ctr;
                $estTraffic += $kw['volume'] * $ctr;
            }

            $result[] = [
                'date' => $date,
                'visibility' => $count > 0 ? round(($totalCtr / $count) * 100, 2) : 0,
                'est_traffic' => round($estTraffic, 1),
                'avg_position' => $count > 0 ? round(array_sum($data['positions']) / $count, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * Get keywords with position comparison between two dates
     * Used by the Overview (keyword table) date comparison feature.
     *
     * @return array Keywords with pos_from, pos_to, diff, visibility, est_traffic
     */
    public static function getKeywordsCompare(int $projectId, string $dateFrom, string $dateTo, array $filters = []): array
    {
        $where = "k.project_id = ? AND k.is_tracked = 1";
        $params = [$projectId];

        // Optional filters
        if (!empty($filters['search'])) {
            $where .= " AND k.keyword LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['intent'])) {
            $where .= " AND COALESCE(k.keyword_intent, JSON_UNQUOTE(JSON_EXTRACT(kv.data, '$.keyword_intent'))) = ?";
            $params[] = $filters['intent'];
        }
        if (!empty($filters['position_range'])) {
            [$minPos, $maxPos] = self::parsePositionRange($filters['position_range']);
            if ($minPos !== null) {
                $where .= " AND k.last_position >= ?";
                $params[] = $minPos;
            }
            if ($maxPos !== null) {
                $where .= " AND k.last_position <= ?";
                $params[] = $maxPos;
            }
        }
        if (!empty($filters['volume_range'])) {
            [$minVol, $maxVol] = self::parseVolumeRange($filters['volume_range']);
            if ($minVol !== null) {
                $where .= " AND k.search_volume >= ?";
                $params[] = $minVol;
            }
            if ($maxVol !== null) {
                $where .= " AND k.search_volume <= ?";
                $params[] = $maxVol;
            }
        }

        $params[] = $dateFrom;
        $params[] = $dateTo;

        $rows = Database::fetchAll(
            "SELECT
                k.id as keyword_id,
                k.keyword,
                k.search_volume,
                k.last_position,
                COALESCE(k.keyword_intent, JSON_UNQUOTE(JSON_EXTRACT(kv.data, '$.keyword_intent'))) as intent,
                kp_from.avg_position as pos_from,
                kp_to.avg_position as pos_to
            FROM st_keywords k
            LEFT JOIN st_keyword_volumes kv ON k.keyword = kv.keyword
            LEFT JOIN st_keyword_positions kp_from ON k.id = kp_from.keyword_id AND kp_from.date = ?
            LEFT JOIN st_keyword_positions kp_to ON k.id = kp_to.keyword_id AND kp_to.date = ?
            WHERE {$where}
            ORDER BY k.search_volume DESC",
            array_merge($params)
        );

        // Enrich with calculated fields
        foreach ($rows as &$row) {
            $posFrom = $row['pos_from'] ? (int) round((float) $row['pos_from']) : null;
            $posTo = $row['pos_to'] ? (int) round((float) $row['pos_to']) : null;
            $vol = (int) ($row['search_volume'] ?? 0);

            $row['pos_from'] = $posFrom;
            $row['pos_to'] = $posTo;
            $row['diff'] = ($posFrom !== null && $posTo !== null) ? $posFrom - $posTo : null;
            $row['visibility'] = $posTo !== null ? self::calculateKeywordVisibility($posTo) : 0;
            $row['est_traffic'] = ($posTo !== null && $vol > 0) ? self::calculateKeywordEstTraffic($vol, $posTo) : 0;
        }

        return $rows;
    }

    /**
     * Parse position range filter string
     * @return array [min, max] (null means no limit)
     */
    private static function parsePositionRange(string $range): array
    {
        return match ($range) {
            'top3' => [1, 3],
            'top10' => [1, 10],
            'top20' => [1, 20],
            'top50' => [1, 50],
            '51-100' => [51, 100],
            '100+' => [101, null],
            default => [null, null],
        };
    }

    /**
     * Parse volume range filter string
     * @return array [min, max] (null means no limit)
     */
    private static function parseVolumeRange(string $range): array
    {
        return match ($range) {
            '0-100' => [0, 100],
            '100-1000' => [100, 1000],
            '1000-10000' => [1000, 10000],
            '10000+' => [10000, null],
            default => [null, null],
        };
    }
}
```

**Step 2: Verify syntax**

Run: `php -l modules/seo-tracking/services/VisibilityService.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/services/VisibilityService.php
git commit -m "feat(seo-tracking): add VisibilityService with CTR curve calculations"
```

---

## Task 2: Rename Tab Labels (Semrush-style)

**Files:**
- Modify: `modules/seo-tracking/views/partials/project-nav.php:17-25`

**Step 1: Update tab labels**

Change the `$tabs` array at lines 17-25 from:

```php
$tabs = [
    'overview' => ['path' => '', 'label' => 'Overview', 'icon' => 'chart-bar'],
    'keywords' => ['path' => '/keywords', 'label' => 'Keywords', 'icon' => 'key'],
    'urls' => ['path' => '/urls', 'label' => 'URLs', 'icon' => 'link'],
    'groups' => ['path' => '/groups', 'label' => 'Gruppi', 'icon' => 'folder'],
    'history' => ['path' => '/rank-check/history', 'label' => 'Storico', 'icon' => 'clock'],
    'quick-wins' => ['path' => '/quick-wins', 'label' => 'Quick Wins', 'icon' => 'zap'],
    'page-analyzer' => ['path' => '/page-analyzer', 'label' => 'Page Analyzer', 'icon' => 'document-search'],
];
```

To:

```php
$tabs = [
    'overview' => ['path' => '', 'label' => 'Landscape', 'icon' => 'chart-bar'],
    'keywords' => ['path' => '/keywords', 'label' => 'Overview', 'icon' => 'key'],
    'urls' => ['path' => '/urls', 'label' => 'Pages', 'icon' => 'link'],
    'groups' => ['path' => '/groups', 'label' => 'Tags', 'icon' => 'folder'],
    'history' => ['path' => '/rank-check/history', 'label' => 'Storico', 'icon' => 'clock'],
    'quick-wins' => ['path' => '/quick-wins', 'label' => 'Quick Wins', 'icon' => 'zap'],
    'page-analyzer' => ['path' => '/page-analyzer', 'label' => 'Page Analyzer', 'icon' => 'document-search'],
];
```

Note: Only the `label` values change. Keys, paths, icons, and routes remain identical.

**Step 2: Verify syntax**

Run: `php -l modules/seo-tracking/views/partials/project-nav.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/views/partials/project-nav.php
git commit -m "feat(seo-tracking): rename tabs to Semrush-style (Landscape, Overview, Pages, Tags)"
```

---

## Task 3: Add New API Endpoints

**Files:**
- Modify: `modules/seo-tracking/controllers/ApiController.php` (add 4 methods)
- Modify: `modules/seo-tracking/routes.php` (add 4 routes)

**Step 1: Add API methods to ApiController**

Add these methods at the end of `ApiController` class (before closing `}`), after the `trackedKeywords()` method (line 232):

```php
/**
 * Visibility stats (KPI data for dashboard)
 */
public function visibilityStats(int $id): string
{
    if (!$this->checkProject($id)) {
        return View::json(['error' => 'Progetto non trovato'], 404);
    }

    $keywords = $this->keyword->allWithPositions($id, 30);
    $trackedKeywords = array_filter($keywords, fn($k) => !empty($k['is_tracked']));

    $visibility = \Modules\SeoTracking\Services\VisibilityService::calculateVisibility($trackedKeywords);
    $estTraffic = \Modules\SeoTracking\Services\VisibilityService::calculateEstTraffic($trackedKeywords);

    // Calculate average position from tracked keywords with positions
    $positions = array_filter(array_map(fn($k) => (int)($k['last_position'] ?? 0), $trackedKeywords), fn($p) => $p > 0);
    $avgPosition = !empty($positions) ? round(array_sum($positions) / count($positions), 1) : 0;

    return View::json([
        'visibility' => $visibility,
        'est_traffic' => $estTraffic,
        'avg_position' => $avgPosition,
        'tracked_count' => count($trackedKeywords),
    ]);
}

/**
 * Rankings distribution history (stacked bar chart data)
 */
public function distributionHistory(int $id): string
{
    if (!$this->checkProject($id)) {
        return View::json(['error' => 'Progetto non trovato'], 404);
    }

    $days = (int) ($_GET['days'] ?? 30);
    $data = \Modules\SeoTracking\Services\VisibilityService::getDistributionOverTime($id, $days);

    return View::json($data);
}

/**
 * Visibility trend over time (line chart data)
 */
public function visibilityTrend(int $id): string
{
    if (!$this->checkProject($id)) {
        return View::json(['error' => 'Progetto non trovato'], 404);
    }

    $days = (int) ($_GET['days'] ?? 30);
    $data = \Modules\SeoTracking\Services\VisibilityService::getVisibilityTrend($id, $days);

    return View::json($data);
}

/**
 * Keywords comparison between two dates
 */
public function keywordsCompare(int $id): string
{
    if (!$this->checkProject($id)) {
        return View::json(['error' => 'Progetto non trovato'], 404);
    }

    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $filters = [
        'search' => $_GET['search'] ?? '',
        'intent' => $_GET['intent'] ?? '',
        'position_range' => $_GET['position_range'] ?? '',
        'volume_range' => $_GET['volume_range'] ?? '',
    ];

    $data = \Modules\SeoTracking\Services\VisibilityService::getKeywordsCompare($id, $dateFrom, $dateTo, $filters);

    return View::json($data);
}
```

**Step 2: Add routes**

Add these 4 routes in `routes.php` in the API AJAX section (after line 754, before the closing CRON comment):

```php
// Visibility Stats (dashboard KPIs)
Router::get('/seo-tracking/api/project/{id}/visibility-stats', function ($id) {
    Middleware::auth();
    return (new ApiController())->visibilityStats((int) $id);
});

// Distribution History (stacked bar chart)
Router::get('/seo-tracking/api/project/{id}/distribution-history', function ($id) {
    Middleware::auth();
    return (new ApiController())->distributionHistory((int) $id);
});

// Visibility Trend (line chart)
Router::get('/seo-tracking/api/project/{id}/visibility-trend', function ($id) {
    Middleware::auth();
    return (new ApiController())->visibilityTrend((int) $id);
});

// Keywords Compare (date range comparison table)
Router::get('/seo-tracking/api/project/{id}/keywords-compare', function ($id) {
    Middleware::auth();
    return (new ApiController())->keywordsCompare((int) $id);
});
```

**Step 3: Verify syntax**

Run: `php -l modules/seo-tracking/controllers/ApiController.php && php -l modules/seo-tracking/routes.php`
Expected: Both `No syntax errors detected`

**Step 4: Commit**

```bash
git add modules/seo-tracking/controllers/ApiController.php modules/seo-tracking/routes.php
git commit -m "feat(seo-tracking): add visibility-stats, distribution-history, visibility-trend, keywords-compare API endpoints"
```

---

## Task 4: Redesign Dashboard Controller (Landscape)

**Files:**
- Modify: `modules/seo-tracking/controllers/DashboardController.php`

**Step 1: Update the `index()` method**

Replace the `index()` method body (lines 44-92) to pass new data to the view:

```php
public function index(int $id): string
{
    $user = Auth::user();
    $project = $this->project->findWithConnectionsAccessible($id, $user['id']);

    if (!$project) {
        $_SESSION['_flash']['error'] = 'Progetto non trovato';
        Router::redirect('/seo-tracking');
        exit;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // All tracked keywords with positions
    $allKeywords = $this->keyword->allWithPositions($id, 30);
    $trackedKeywords = array_filter($allKeywords, fn($k) => !empty($k['is_tracked']));

    // NEW: Visibility metrics
    $visibility = \Modules\SeoTracking\Services\VisibilityService::calculateVisibility($trackedKeywords);
    $estTraffic = \Modules\SeoTracking\Services\VisibilityService::calculateEstTraffic($trackedKeywords);

    // KPI Stats (existing + enhanced)
    $kpiStats = $this->getKpiStats($id);
    $kpiStats['visibility'] = $visibility;
    $kpiStats['est_traffic'] = $estTraffic;

    // NEW: Distribution over time (stacked bar chart - 30 days)
    $distributionHistory = \Modules\SeoTracking\Services\VisibilityService::getDistributionOverTime($id, 30);

    // Distribuzione posizioni corrente (for keywords mini-grid)
    $positionDistribution = $this->getPositionDistribution($id);

    // NEW: Position bucket counts with changes
    $keywordBuckets = $this->getKeywordBuckets($trackedKeywords);

    // Top 5 Gainers e Losers
    $gainers = $this->getTopMovers($id, 5, 'gainers');
    $losers = $this->getTopMovers($id, 5, 'losers');

    // Top keywords by position (for mini-table)
    $topKeywords = $this->getTopKeywordsByPosition($trackedKeywords, 5);

    // Positive/Negative impact (by visibility change)
    $positiveImpact = $this->getImpactKeywords($trackedKeywords, 5, 'positive');
    $negativeImpact = $this->getImpactKeywords($trackedKeywords, 5, 'negative');

    // Movimenti recenti
    $recentMovements = $this->getRecentMovements($id, 10);

    // Ultimo check
    $lastCheck = $this->getLastCheckInfo($id);

    // Last AI report summary
    $lastReport = $this->aiReport->getLatest($id);

    return View::render('seo-tracking/dashboard/index', [
        'title' => $project['name'] . ' - Landscape',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'kpiStats' => $kpiStats,
        'distributionHistory' => $distributionHistory,
        'positionDistribution' => $positionDistribution,
        'keywordBuckets' => $keywordBuckets,
        'topKeywords' => $topKeywords,
        'positiveImpact' => $positiveImpact,
        'negativeImpact' => $negativeImpact,
        'gainers' => $gainers,
        'losers' => $losers,
        'recentMovements' => $recentMovements,
        'lastCheck' => $lastCheck,
        'lastReport' => $lastReport,
    ]);
}
```

**Step 2: Add helper methods**

Add these new private methods to the DashboardController class (after `getLastCheckInfo`):

```php
/**
 * Keywords grouped into position buckets with improved/declined counts
 */
private function getKeywordBuckets(array $keywords): array
{
    $buckets = [
        'top3' => ['count' => 0, 'improved' => 0, 'declined' => 0],
        'top10' => ['count' => 0, 'improved' => 0, 'declined' => 0],
        'top20' => ['count' => 0, 'improved' => 0, 'declined' => 0],
        'top100' => ['count' => 0, 'improved' => 0, 'declined' => 0],
    ];

    foreach ($keywords as $kw) {
        $pos = (int) ($kw['last_position'] ?? 0);
        $change = (int) ($kw['position_change_7d'] ?? 0);

        if ($pos <= 0) continue;

        // Cumulative buckets (like Semrush)
        if ($pos <= 3) {
            $buckets['top3']['count']++;
            if ($change > 0) $buckets['top3']['improved']++;
            elseif ($change < 0) $buckets['top3']['declined']++;
        }
        if ($pos <= 10) {
            $buckets['top10']['count']++;
            if ($change > 0) $buckets['top10']['improved']++;
            elseif ($change < 0) $buckets['top10']['declined']++;
        }
        if ($pos <= 20) {
            $buckets['top20']['count']++;
            if ($change > 0) $buckets['top20']['improved']++;
            elseif ($change < 0) $buckets['top20']['declined']++;
        }
        if ($pos <= 100) {
            $buckets['top100']['count']++;
            if ($change > 0) $buckets['top100']['improved']++;
            elseif ($change < 0) $buckets['top100']['declined']++;
        }
    }

    return $buckets;
}

/**
 * Top keywords sorted by best position
 */
private function getTopKeywordsByPosition(array $keywords, int $limit): array
{
    $withPosition = array_filter($keywords, fn($k) => ($k['last_position'] ?? 0) > 0);
    usort($withPosition, fn($a, $b) => ($a['last_position'] ?? 999) - ($b['last_position'] ?? 999));
    return array_slice($withPosition, 0, $limit);
}

/**
 * Keywords with most positive or negative visibility change
 */
private function getImpactKeywords(array $keywords, int $limit, string $direction): array
{
    $withChange = array_filter($keywords, fn($k) => ($k['position_change_7d'] ?? 0) != 0 && ($k['last_position'] ?? 0) > 0);

    usort($withChange, function ($a, $b) use ($direction) {
        // position_change_7d: positive = improved (moved up), negative = declined
        $changeA = (int) ($a['position_change_7d'] ?? 0);
        $changeB = (int) ($b['position_change_7d'] ?? 0);

        if ($direction === 'positive') {
            return $changeB - $changeA; // Largest positive first
        }
        return $changeA - $changeB; // Largest negative first
    });

    $filtered = $direction === 'positive'
        ? array_filter($withChange, fn($k) => ($k['position_change_7d'] ?? 0) > 0)
        : array_filter($withChange, fn($k) => ($k['position_change_7d'] ?? 0) < 0);

    return array_slice(array_values($filtered), 0, $limit);
}
```

**Step 3: Verify syntax**

Run: `php -l modules/seo-tracking/controllers/DashboardController.php`
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add modules/seo-tracking/controllers/DashboardController.php
git commit -m "feat(seo-tracking): enhance DashboardController with visibility metrics, buckets, and impact keywords"
```

---

## Task 5: Redesign Dashboard View (Landscape)

**Files:**
- Rewrite: `modules/seo-tracking/views/dashboard/index.php`

**Step 1: Rewrite the dashboard view**

This is the largest task. The full new view replaces the existing file entirely. It should implement:

1. KPI Row with 4 cards: Visibility %, Est. Traffic, Avg. Position, Keywords Tracked
2. Stacked bar chart (Rankings Distribution over 30 days) + Keywords mini-grid side by side
3. AI Summary box (from last report)
4. 3 mini-tables: Top Keywords, Positive Impact, Negative Impact
5. Existing Gainers/Losers tables
6. Recent movements table
7. CTA footer

Use the `@frontend-design` skill for the view implementation. Key technical requirements:
- `$currentPage = 'overview';` (keeps internal key, label shown is "Landscape" from nav)
- Chart.js stacked bar chart with 6 colors matching Semrush
- KPI cards show change indicators (green/red arrows)
- All text in Italian (UI lingua ITALIANO)
- Dark mode support
- CSS standards: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`

Data available in the view from controller:
- `$kpiStats` — `['visibility', 'est_traffic', 'avg_position', 'tracked_keywords', 'top10_count', 'improved_7d', 'declined_7d']`
- `$distributionHistory` — `[['date', 'top3', 'top4_10', 'top11_20', 'top21_50', 'top51_100', 'out_of_top'], ...]`
- `$positionDistribution` — `['top3', 'top4_10', 'top11_20', 'top21_50', 'beyond50']`
- `$keywordBuckets` — `['top3' => ['count', 'improved', 'declined'], 'top10' => ..., 'top20' => ..., 'top100' => ...]`
- `$topKeywords` — array of keyword objects (keyword, last_position, search_volume)
- `$positiveImpact` — array of keywords with positive visibility change
- `$negativeImpact` — array of keywords with negative visibility change
- `$gainers`, `$losers` — existing top movers arrays
- `$recentMovements` — recent rank checks
- `$lastCheck` — last check info
- `$lastReport` — latest AI report (or null)

**Step 2: Verify syntax**

Run: `php -l modules/seo-tracking/views/dashboard/index.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/views/dashboard/index.php
git commit -m "feat(seo-tracking): redesign Landscape dashboard with Semrush-style widgets"
```

---

## Task 6: Update KeywordController for Enhanced Filters

**Files:**
- Modify: `modules/seo-tracking/controllers/KeywordController.php:48-120`

**Step 1: Enhance the `index()` method**

Update the `index()` method to:
1. Support intent, position_range, and volume_range filters
2. Calculate per-keyword visibility and est. traffic
3. Pass visibility trend data for the chart above the table

Add to the `$filters` array (after line 64):

```php
$filters = [
    'search' => $_GET['search'] ?? '',
    'is_tracked' => $_GET['tracked'] ?? null,
    'group_name' => $_GET['group'] ?? null,
    'position_max' => $_GET['position'] ?? null,
    'intent' => $_GET['intent'] ?? null,
    'position_range' => $_GET['position_range'] ?? null,
    'volume_range' => $_GET['volume_range'] ?? null,
];
```

After `$keywords` is populated (after line 84), add visibility/traffic enrichment:

```php
// Enrich keywords with visibility and est. traffic
foreach ($keywords as &$kw) {
    $pos = (int) ($kw['last_position'] ?? 0);
    $vol = (int) ($kw['search_volume'] ?? 0);
    $kw['visibility'] = \Modules\SeoTracking\Services\VisibilityService::calculateKeywordVisibility($pos);
    $kw['est_traffic'] = \Modules\SeoTracking\Services\VisibilityService::calculateKeywordEstTraffic($vol, $pos);
}
unset($kw);
```

Add visibility trend data to the View::render call:

```php
'visibilityTrend' => \Modules\SeoTracking\Services\VisibilityService::getVisibilityTrend($projectId, 30),
```

**Step 2: Verify syntax**

Run: `php -l modules/seo-tracking/controllers/KeywordController.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/controllers/KeywordController.php
git commit -m "feat(seo-tracking): enhance KeywordController with visibility enrichment and advanced filters"
```

---

## Task 7: Redesign Keywords View (Overview)

**Files:**
- Rewrite: `modules/seo-tracking/views/keywords/index.php`

**Step 1: Rewrite the keywords view**

This is the second largest task. The full new view should implement:

1. `$currentPage = 'keywords';` (internal key, label "Overview" from nav)
2. Date range picker in header area (date_from, date_to inputs)
3. Chart with switchable tabs: Visibility % | Est. Traffic | Avg. Position
4. Filter bar: search input, intent dropdown, position range dropdown, volume range dropdown
5. Action buttons: Volumi, Posizioni, Tutto, + Aggiungi Keyword (keep existing)
6. Table with columns: Checkbox | Keyword | Intent badge | Volume | Pos. Data1 | Pos. Data2 | Diff | Visibility % | Est. Traffic | Azioni
7. Active job banner (keep existing)
8. Pagination (keep existing)
9. Refresh modals (keep existing)

Use the `@frontend-design` skill for the view implementation. Key technical requirements:
- Intent badges: I=blue, N=purple, C=amber, T=emerald (small rounded pill)
- Position diff: green text + up arrow for improvement, red + down arrow for decline
- Chart.js line chart above table, loaded from `$visibilityTrend` data
- Alpine.js for tab switching on chart
- Date picker inputs with type="date" (HTML5)
- Dark mode support throughout
- CSS: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`
- Preserve all existing JS for job status polling, refresh modals, bulk actions

Data available:
- `$keywords` — enriched with `visibility`, `est_traffic`, `intent` fields
- `$visibilityTrend` — array for chart `[{date, visibility, est_traffic, avg_position}]`
- `$compareMode`, `$dateFrom`, `$dateTo` — date comparison state
- `$stats`, `$groups`, `$filters` — existing filter data
- `$activeJob` — active rank check job (or null)
- `$userCredits` — credit balance

**Step 2: Verify syntax**

Run: `php -l modules/seo-tracking/views/keywords/index.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/views/keywords/index.php
git commit -m "feat(seo-tracking): redesign Overview keyword table with date comparison, filters, and visibility metrics"
```

---

## Task 8: Final Verification & Integration Test

**Step 1: Verify all PHP files have no syntax errors**

Run:
```bash
php -l modules/seo-tracking/services/VisibilityService.php
php -l modules/seo-tracking/views/partials/project-nav.php
php -l modules/seo-tracking/controllers/DashboardController.php
php -l modules/seo-tracking/controllers/KeywordController.php
php -l modules/seo-tracking/controllers/ApiController.php
php -l modules/seo-tracking/views/dashboard/index.php
php -l modules/seo-tracking/views/keywords/index.php
php -l modules/seo-tracking/routes.php
```
Expected: All `No syntax errors detected`

**Step 2: Manual browser test**

1. Navigate to `http://localhost/seo-toolkit/seo-tracking/project/{id}` (Landscape dashboard)
   - Verify 4 KPI cards show Visibility %, Est. Traffic, Avg. Position, Keywords
   - Verify stacked bar chart renders with 30 days of data
   - Verify keywords mini-grid shows bucket counts
   - Verify 3 mini-tables (Top, Positive, Negative) appear

2. Navigate to `http://localhost/seo-toolkit/seo-tracking/project/{id}/keywords` (Overview table)
   - Verify chart above table renders
   - Verify filter dropdowns work (intent, position, volume)
   - Verify date picker changes table data
   - Verify Intent badges appear on keywords
   - Verify Visibility % and Est. Traffic columns show data

3. Verify tab labels show: Landscape | Overview | Pages | Tags | Storico | Quick Wins | Page Analyzer

**Step 3: Test API endpoints**

```bash
# Test visibility stats
curl -s "http://localhost/seo-toolkit/seo-tracking/api/project/1/visibility-stats" | head

# Test distribution history
curl -s "http://localhost/seo-toolkit/seo-tracking/api/project/1/distribution-history?days=30" | head

# Test visibility trend
curl -s "http://localhost/seo-toolkit/seo-tracking/api/project/1/visibility-trend?days=30" | head

# Test keywords compare
curl -s "http://localhost/seo-toolkit/seo-tracking/api/project/1/keywords-compare?date_from=2026-02-24&date_to=2026-03-03" | head
```

**Step 4: Final commit**

```bash
git add -A
git commit -m "feat(seo-tracking): complete Semrush-style redesign - Landscape dashboard + Overview keyword table"
```

---

## Summary

| Task | Description | Files Changed |
|------|-------------|---------------|
| 1 | VisibilityService (CTR curve, calculations) | 1 new |
| 2 | Tab label renaming | 1 modified |
| 3 | New API endpoints | 2 modified |
| 4 | Dashboard controller enhancements | 1 modified |
| 5 | Dashboard view rewrite (Landscape) | 1 rewritten |
| 6 | Keyword controller enhancements | 1 modified |
| 7 | Keywords view rewrite (Overview) | 1 rewritten |
| 8 | Final verification | 0 (testing) |

**Total: 1 new file, 6 modified files, 8 commits**
