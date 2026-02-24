# Date Range Position Compare - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a date range selector to the seo-tracking keywords page to compare position changes between two dates, and enhance the Trend page with tracked-keyword data source.

**Architecture:** Two changes: (1) Modify `KeywordController::index()` to accept date_from/date_to GET params, add new `Keyword::allWithPositionComparison()` method that queries `st_keyword_positions` for two dates, and conditionally render a comparison table in the view. (2) Add `PositionCompareService::compareFromPositions()` using `st_keyword_positions` instead of `st_gsc_data`, with a source toggle in the Trend view.

**Tech Stack:** PHP 8+, MySQL (st_keyword_positions table), Alpine.js, Tailwind CSS

**Design doc:** `docs/plans/2026-02-24-date-range-position-compare-design.md`

---

## Task 1: Add `allWithPositionComparison()` to Keyword model

**Files:**
- Modify: `modules/seo-tracking/models/Keyword.php` (after line ~151, after `allWithPositions()`)

**Step 1: Add the new method**

Add after the `allWithPositions()` method (line ~152):

```php
/**
 * Keyword con confronto posizioni tra due date
 * Per ogni keyword, trova la posizione piu vicina a dateStart e dateEnd
 */
public function allWithPositionComparison(
    int $projectId,
    string $dateStart,
    string $dateEnd,
    array $filters = []
): array {
    $sql = "
        SELECT
            k.*,
            kp_start.avg_position as position_start,
            kp_start.date as date_start_actual,
            kp_end.avg_position as position_end,
            kp_end.date as date_end_actual,
            CASE
                WHEN kp_start.avg_position IS NOT NULL AND kp_end.avg_position IS NOT NULL
                THEN ROUND(kp_start.avg_position - kp_end.avg_position, 1)
                ELSE NULL
            END as position_delta,
            CASE
                WHEN kp_start.avg_position IS NULL AND kp_end.avg_position IS NOT NULL THEN 'new'
                WHEN kp_start.avg_position IS NOT NULL AND kp_end.avg_position IS NULL THEN 'lost'
                WHEN kp_start.avg_position IS NOT NULL AND kp_end.avg_position IS NOT NULL
                     AND ROUND(kp_start.avg_position - kp_end.avg_position, 1) >= 1 THEN 'improved'
                WHEN kp_start.avg_position IS NOT NULL AND kp_end.avg_position IS NOT NULL
                     AND ROUND(kp_start.avg_position - kp_end.avg_position, 1) <= -1 THEN 'declined'
                ELSE 'stable'
            END as compare_status,
            kp_end.total_clicks as period_clicks,
            kp_end.total_impressions as period_impressions
        FROM {$this->table} k
        LEFT JOIN (
            SELECT kp1.*
            FROM st_keyword_positions kp1
            INNER JOIN (
                SELECT keyword_id, MIN(ABS(DATEDIFF(date, ?))) as min_diff
                FROM st_keyword_positions
                WHERE project_id = ? AND date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
                GROUP BY keyword_id
            ) closest ON kp1.keyword_id = closest.keyword_id
                AND ABS(DATEDIFF(kp1.date, ?)) = closest.min_diff
                AND kp1.project_id = ?
                AND kp1.date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
        ) kp_start ON k.id = kp_start.keyword_id
        LEFT JOIN (
            SELECT kp2.*
            FROM st_keyword_positions kp2
            INNER JOIN (
                SELECT keyword_id, MIN(ABS(DATEDIFF(date, ?))) as min_diff
                FROM st_keyword_positions
                WHERE project_id = ? AND date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
                GROUP BY keyword_id
            ) closest ON kp2.keyword_id = closest.keyword_id
                AND ABS(DATEDIFF(kp2.date, ?)) = closest.min_diff
                AND kp2.project_id = ?
                AND kp2.date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
        ) kp_end ON k.id = kp_end.keyword_id
        WHERE k.project_id = ?
    ";

    $params = [
        // kp_start subquery
        $dateStart, $projectId, $dateStart, $dateStart,
        $dateStart, $projectId, $dateStart, $dateStart,
        // kp_end subquery
        $dateEnd, $projectId, $dateEnd, $dateEnd,
        $dateEnd, $projectId, $dateEnd, $dateEnd,
        // main WHERE
        $projectId
    ];

    // Filtri (stessi di allWithPositions)
    if (isset($filters['is_tracked']) && $filters['is_tracked'] !== '' && $filters['is_tracked'] !== null) {
        $sql .= " AND k.is_tracked = ?";
        $params[] = (int) $filters['is_tracked'];
    }

    if (!empty($filters['group_name'])) {
        $sql .= " AND k.group_name = ?";
        $params[] = $filters['group_name'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND k.keyword LIKE ?";
        $params[] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['position_max'])) {
        $sql .= " AND (kp_end.avg_position IS NOT NULL AND kp_end.avg_position <= ?)";
        $params[] = (int) $filters['position_max'];
    }

    $sql .= " ORDER BY
        CASE
            WHEN kp_end.avg_position IS NULL AND kp_start.avg_position IS NOT NULL THEN 2
            WHEN kp_end.avg_position IS NULL AND kp_start.avg_position IS NULL THEN 3
            ELSE 1
        END,
        kp_end.avg_position ASC";

    return Database::fetchAll($sql, $params);
}
```

**Note:** The query is complex because it needs closest-date matching. Delta positive = position improved (was 10, now 5 = delta +5). Convention matches `PositionCompareService` (previous - current).

**Step 2: Verify PHP syntax**

Run: `php -l modules/seo-tracking/models/Keyword.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/models/Keyword.php
git commit -m "feat(seo-tracking): add allWithPositionComparison() to Keyword model"
```

---

## Task 2: Update KeywordController to handle date range params

**Files:**
- Modify: `modules/seo-tracking/controllers/KeywordController.php` (the `index()` method, lines 48-86)

**Step 1: Modify the index() method**

In `KeywordController::index()`, after the `$filters` array (line 64), add date range handling:

```php
// Date range comparison
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$compareMode = ($dateFrom && $dateTo);

if ($compareMode) {
    $keywords = $this->keyword->allWithPositionComparison($projectId, $dateFrom, $dateTo, $filters);
} else {
    $keywords = $this->keyword->allWithPositions($projectId, 30, $filters);
}
```

And pass the new variables to the view (add to the `View::render()` array):

```php
'compareMode' => $compareMode,
'dateFrom' => $dateFrom,
'dateTo' => $dateTo,
```

The full modified `index()` should look like:

```php
public function index(int $projectId): string
{
    $user = Auth::user();
    $project = $this->project->find($projectId, $user['id']);

    if (!$project) {
        $_SESSION['_flash']['error'] = 'Progetto non trovato';
        Router::redirect('/seo-tracking');
        exit;
    }

    $filters = [
        'search' => $_GET['search'] ?? '',
        'is_tracked' => $_GET['tracked'] ?? null,
        'group_name' => $_GET['group'] ?? null,
        'position_max' => $_GET['position'] ?? null,
    ];

    // Date range comparison
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $compareMode = ($dateFrom && $dateTo);

    if ($compareMode) {
        $keywords = $this->keyword->allWithPositionComparison($projectId, $dateFrom, $dateTo, $filters);
    } else {
        $keywords = $this->keyword->allWithPositions($projectId, 30, $filters);
    }

    $groups = $this->keyword->getGroups($projectId);
    $stats = $this->keyword->getStats($projectId);

    // Check for active rank check job
    $rankJob = new RankJob();
    $activeJob = $rankJob->getActiveForProject($projectId);

    // Available date range from st_keyword_positions
    $dateRange = null;
    if ($compareMode || isset($_GET['compare'])) {
        $kp = new KeywordPosition();
        $minMax = Database::fetch(
            "SELECT MIN(date) as min_date, MAX(date) as max_date FROM st_keyword_positions WHERE project_id = ?",
            [$projectId]
        );
        $dateRange = [
            'min_date' => $minMax['min_date'] ?? date('Y-m-d', strtotime('-16 months')),
            'max_date' => $minMax['max_date'] ?? date('Y-m-d'),
        ];
    }

    return View::render('seo-tracking/keywords/index', [
        'title' => $project['name'] . ' - Keywords',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'keywords' => $keywords,
        'groups' => $groups,
        'stats' => $stats,
        'filters' => $filters,
        'userCredits' => Credits::getBalance($user['id']),
        'activeJob' => $activeJob,
        'compareMode' => $compareMode,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'dateRange' => $dateRange,
    ]);
}
```

**Important:** Add `use Core\Database;` at the top if not already there (check — it IS already imported at line 10).

**Step 2: Verify PHP syntax**

Run: `php -l modules/seo-tracking/controllers/KeywordController.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/controllers/KeywordController.php
git commit -m "feat(seo-tracking): handle date_from/date_to params in KeywordController"
```

---

## Task 3: Add date range bar and comparison table to Keywords view

**Files:**
- Modify: `modules/seo-tracking/views/keywords/index.php`

This is the largest task. Three changes to the view:

### Step 1: Add date range bar

Insert AFTER the `<!-- Filters -->` form (after line 156 `</form>`) and BEFORE the `<!-- Results info -->` section. This is a new collapsible section:

```php
<!-- Date Range Compare -->
<div x-data="dateRangeCompare()" class="space-y-0">
    <!-- Toggle Button -->
    <button @click="open = !open; if(open && !dateRange) initDates()"
            type="button"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
            :class="compareActive ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-700' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span x-text="compareActive ? 'Confronto attivo: ' + dateFromLabel + ' → ' + dateToLabel : 'Confronta periodo'"></span>
        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Date Range Panel -->
    <div x-show="open" x-collapse class="mt-3">
        <form method="GET" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <!-- Preserve existing filters -->
            <?php if ($filters['search']): ?><input type="hidden" name="search" value="<?= e($filters['search']) ?>"><?php endif; ?>
            <?php if ($filters['is_tracked'] !== null): ?><input type="hidden" name="tracked" value="<?= e($filters['is_tracked']) ?>"><?php endif; ?>
            <?php if ($filters['group_name']): ?><input type="hidden" name="group" value="<?= e($filters['group_name']) ?>"><?php endif; ?>
            <?php if ($filters['position_max']): ?><input type="hidden" name="position" value="<?= e($filters['position_max']) ?>"><?php endif; ?>

            <div class="flex flex-wrap items-end gap-4">
                <!-- Preset -->
                <div>
                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1.5">Periodo</label>
                    <div class="flex gap-1.5">
                        <button type="button" @click="applyPreset('7d')" :class="preset === '7d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300'" class="px-2.5 py-1.5 rounded-md text-xs font-medium border border-blue-200 dark:border-blue-700 transition-colors">7gg</button>
                        <button type="button" @click="applyPreset('14d')" :class="preset === '14d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300'" class="px-2.5 py-1.5 rounded-md text-xs font-medium border border-blue-200 dark:border-blue-700 transition-colors">14gg</button>
                        <button type="button" @click="applyPreset('28d')" :class="preset === '28d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300'" class="px-2.5 py-1.5 rounded-md text-xs font-medium border border-blue-200 dark:border-blue-700 transition-colors">28gg</button>
                        <button type="button" @click="applyPreset('3m')" :class="preset === '3m' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300'" class="px-2.5 py-1.5 rounded-md text-xs font-medium border border-blue-200 dark:border-blue-700 transition-colors">3 mesi</button>
                    </div>
                </div>

                <!-- Date inputs -->
                <div>
                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1.5">Dal</label>
                    <input type="date" name="date_from" x-model="dateFrom" @change="preset = 'custom'"
                           class="rounded-lg border-blue-200 dark:border-blue-700 dark:bg-slate-700 dark:text-white text-sm px-3 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1.5">Al</label>
                    <input type="date" name="date_to" x-model="dateTo" @change="preset = 'custom'"
                           class="rounded-lg border-blue-200 dark:border-blue-700 dark:bg-slate-700 dark:text-white text-sm px-3 py-1.5">
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                        Confronta
                    </button>
                    <?php if ($compareMode): ?>
                    <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') . ($filters['search'] ? '?search=' . urlencode($filters['search']) : '') ?>"
                       class="px-4 py-1.5 rounded-lg bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                        Chiudi confronto
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
```

### Step 2: Modify the table headers and rows conditionally

Wrap the existing table headers in a condition. Replace the `<thead>` and `<tbody>` sections to show different columns when `$compareMode` is active.

Replace the table `<thead>` (lines 205-220) with a conditional:

```php
<thead class="bg-slate-50 dark:bg-slate-700/50">
    <tr>
        <th class="px-4 py-3 text-left">
            <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600" onclick="toggleAll(this)">
        </th>
        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
        <?php if ($compareMode): ?>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. Inizio</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. Fine</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Delta</th>
        <?php else: ?>
            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Location">Loc</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
        <?php endif; ?>
        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Volume</th>
        <?php if (!$compareMode): ?>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CPC</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Comp.</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Intento di ricerca">Intento</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Stagionalit&agrave;">Stagion.</th>
        <?php endif; ?>
        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
            <?= $compareMode ? 'Stato' : 'Aggiornato' ?>
        </th>
        <?php if (!$compareMode): ?>
        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Azioni</th>
        <?php endif; ?>
    </tr>
</thead>
```

For the table body, the existing `<tr>` in the foreach needs a conditional section. After the keyword `<td>` (line 234), add the compare columns conditionally:

```php
<?php if ($compareMode): ?>
    <!-- Position Start -->
    <td class="px-4 py-3 text-right">
        <?php if ($kw['position_start'] !== null): ?>
            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                <?= number_format($kw['position_start'], 1) ?>
            </span>
        <?php else: ?>
            <span class="text-slate-400">-</span>
        <?php endif; ?>
    </td>
    <!-- Position End -->
    <td class="px-4 py-3 text-right">
        <?php if ($kw['position_end'] !== null):
            $posEndClass = $kw['position_end'] <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                          ($kw['position_end'] <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                          ($kw['position_end'] <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
        ?>
            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posEndClass ?>">
                <?= number_format($kw['position_end'], 1) ?>
            </span>
        <?php else: ?>
            <span class="text-slate-400">-</span>
        <?php endif; ?>
    </td>
    <!-- Delta -->
    <td class="px-4 py-3 text-center">
        <?php
        $delta = $kw['position_delta'] ?? null;
        $status = $kw['compare_status'] ?? 'stable';
        if ($status === 'new'):
        ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">NUOVA</span>
        <?php elseif ($status === 'lost'): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300">PERSA</span>
        <?php elseif ($status === 'improved'): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                +<?= number_format(abs($delta), 1) ?>
            </span>
        <?php elseif ($status === 'declined'): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                <?= number_format($delta, 1) ?>
            </span>
        <?php else: ?>
            <span class="text-slate-400">-</span>
        <?php endif; ?>
    </td>
<?php else: ?>
    <!-- (existing Location and Position columns stay here unchanged) -->
<?php endif; ?>
```

Then after Volume column, wrap the CPC/Comp/Intent/Seasonality columns in `<?php if (!$compareMode): ?>...<?php endif; ?>`.

Replace the "Aggiornato" column with a conditional:

```php
<td class="px-4 py-3 text-center text-xs text-slate-500 dark:text-slate-400">
    <?php if ($compareMode): ?>
        <?php
        $statusLabel = match($kw['compare_status'] ?? 'stable') {
            'improved' => 'Migliorata',
            'declined' => 'Peggiorata',
            'new' => 'Nuova',
            'lost' => 'Persa',
            default => 'Stabile'
        };
        ?>
        <?= $statusLabel ?>
    <?php else: ?>
        <?php if (!empty($kw['last_updated_at'])): ?>
            <?= date('d/m H:i', strtotime($kw['last_updated_at'])) ?>
        <?php else: ?>
            <span class="text-slate-400 dark:text-slate-500">Mai</span>
        <?php endif; ?>
    <?php endif; ?>
</td>
```

Wrap the Actions column in `<?php if (!$compareMode): ?>...<?php endif; ?>`.

### Step 3: Add the Alpine.js component for date range

Add at the END of the file (before closing `</div>` or in `<script>` section):

```html
<script>
function dateRangeCompare() {
    return {
        open: <?= $compareMode ? 'true' : 'false' ?>,
        compareActive: <?= $compareMode ? 'true' : 'false' ?>,
        preset: 'custom',
        dateFrom: '<?= e($dateFrom ?? date('Y-m-d', strtotime('-28 days'))) ?>',
        dateTo: '<?= e($dateTo ?? date('Y-m-d')) ?>',

        get dateFromLabel() {
            return this.dateFrom ? new Date(this.dateFrom).toLocaleDateString('it-IT', {day: '2-digit', month: 'short'}) : '';
        },
        get dateToLabel() {
            return this.dateTo ? new Date(this.dateTo).toLocaleDateString('it-IT', {day: '2-digit', month: 'short'}) : '';
        },

        initDates() {
            if (!this.compareActive) {
                this.applyPreset('28d');
            }
        },

        applyPreset(key) {
            this.preset = key;
            const today = new Date();
            let from;

            switch(key) {
                case '7d':
                    from = new Date(today);
                    from.setDate(from.getDate() - 7);
                    break;
                case '14d':
                    from = new Date(today);
                    from.setDate(from.getDate() - 14);
                    break;
                case '28d':
                    from = new Date(today);
                    from.setDate(from.getDate() - 28);
                    break;
                case '3m':
                    from = new Date(today);
                    from.setMonth(from.getMonth() - 3);
                    break;
            }

            this.dateFrom = from.toISOString().split('T')[0];
            this.dateTo = today.toISOString().split('T')[0];
        }
    };
}
</script>
```

### Step 4: Verify PHP syntax

Run: `php -l modules/seo-tracking/views/keywords/index.php`
Expected: `No syntax errors detected`

### Step 5: Manual test in browser

1. Navigate to `http://localhost/seo-toolkit/seo-tracking/project/{id}/keywords`
2. Verify the "Confronta periodo" button appears
3. Click it — date range panel should expand
4. Select 28gg preset and click "Confronta"
5. Verify table changes to show Pos. Inizio, Pos. Fine, Delta columns
6. Click "Chiudi confronto" to return to normal view

### Step 6: Commit

```bash
git add modules/seo-tracking/views/keywords/index.php
git commit -m "feat(seo-tracking): add date range comparison UI to keywords listing"
```

---

## Task 4: Add `compareFromPositions()` to PositionCompareService

**Files:**
- Modify: `modules/seo-tracking/services/PositionCompareService.php` (add new method)

**Step 1: Add the method**

Add after the existing `compare()` method (after line ~45):

```php
/**
 * Confronta posizioni da st_keyword_positions (keyword tracciate)
 * Usa una singola data inizio e una data fine (non due periodi)
 */
public function compareFromPositions(
    string $dateStart,
    string $dateEnd,
    array $filters = []
): array {
    // Posizioni alla data inizio (closest match entro 3 giorni)
    $periodA = $this->getPositionData($dateStart, $filters);

    // Posizioni alla data fine
    $periodB = $this->getPositionData($dateEnd, $filters);

    // Riusa la stessa logica di differenze
    return $this->calculateDifferences($periodA, $periodB);
}

/**
 * Ottiene posizioni da st_keyword_positions per una data
 */
private function getPositionData(string $date, array $filters): array
{
    $sql = "
        SELECT
            k.keyword,
            kp.avg_position,
            kp.total_clicks as total_clicks,
            kp.total_impressions as total_impressions,
            ROUND(kp.total_clicks / NULLIF(kp.total_impressions, 0) * 100, 2) as ctr,
            kp.top_pages as url
        FROM st_keyword_positions kp
        JOIN st_keywords k ON kp.keyword_id = k.id
        WHERE kp.project_id = ?
          AND k.is_tracked = 1
          AND kp.date = (
              SELECT date FROM st_keyword_positions
              WHERE keyword_id = kp.keyword_id AND project_id = ?
                AND date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
              ORDER BY ABS(DATEDIFF(date, ?))
              LIMIT 1
          )
    ";

    $params = [$this->projectId, $this->projectId, $date, $date, $date];

    if (!empty($filters['keyword'])) {
        $sql .= " AND k.keyword LIKE ?";
        $params[] = '%' . $filters['keyword'] . '%';
    }

    $results = Database::fetchAll($sql, $params);

    // Indicizza per keyword
    $indexed = [];
    foreach ($results as $row) {
        // Parse top_pages JSON to get URL
        $url = $row['url'];
        if ($url && str_starts_with($url, '[')) {
            $pages = json_decode($url, true);
            $url = $pages[0]['page'] ?? $pages[0] ?? '';
        }
        $row['url'] = $url ?: '';
        $row['avg_position'] = round((float)$row['avg_position'], 1);
        $indexed[$row['keyword']] = $row;
    }

    return $indexed;
}
```

**Step 2: Verify PHP syntax**

Run: `php -l modules/seo-tracking/services/PositionCompareService.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/seo-tracking/services/PositionCompareService.php
git commit -m "feat(seo-tracking): add compareFromPositions() using st_keyword_positions"
```

---

## Task 5: Add source toggle to CompareController and Trend view

**Files:**
- Modify: `modules/seo-tracking/controllers/CompareController.php` (index and getData methods)
- Modify: `modules/seo-tracking/views/trend/index.php` (add source toggle)

### Step 1: Update CompareController::index()

Add `source` parameter handling. After line 37 (`$preset = ...`), add:

```php
$source = $_GET['source'] ?? 'gsc';
```

After line 50 (`$results = $compareService->compare(...)`), wrap in condition:

```php
if ($source === 'positions') {
    $results = $compareService->compareFromPositions($dateFromB, $dateToB, $filters);
} else {
    $results = $compareService->compare($dateFromA, $dateToA, $dateFromB, $dateToB, $filters);
}
```

Pass `$source` to the view:

```php
'currentSource' => $source,
```

### Step 2: Update CompareController::getData()

In the `getData()` method, add source handling after reading input (line 89):

```php
$source = $input['source'] ?? 'gsc';

if ($source === 'positions') {
    $results = $compareService->compareFromPositions(
        $input['date_from_b'] ?? '',
        $input['date_to_b'] ?? '',
        [
            'keyword' => $input['keyword'] ?? '',
        ]
    );
} else {
    $results = $compareService->compare(
        $input['date_from_a'] ?? '',
        $input['date_to_a'] ?? '',
        $input['date_from_b'] ?? '',
        $input['date_to_b'] ?? '',
        [
            'keyword' => $input['keyword'] ?? '',
            'url' => $input['url'] ?? ''
        ]
    );
}
```

### Step 3: Add source toggle to Trend view

In `views/trend/index.php`, add a source toggle in the controls panel. Insert after the Preset buttons section (after line ~52, before the Date Pickers):

```php
<!-- Fonte Dati -->
<div>
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fonte Dati</label>
    <div class="flex gap-2">
        <button @click="source = 'gsc'; if(source !== prevSource) { prevSource = 'gsc'; }"
                :class="source === 'gsc' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
            GSC (tutte le query)
        </button>
        <button @click="source = 'positions'; if(source !== prevSource) { prevSource = 'positions'; }"
                :class="source === 'positions' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
            Keyword tracciate
        </button>
    </div>
</div>
```

When source is 'positions', hide Period A date fields (only start/end needed, not two periods). Add around the Period A section:

```html
<div class="space-y-2" x-show="source === 'gsc'">
    <!-- existing Period A fields -->
</div>
```

Relabel Period B to just "Periodo" when source is positions:

```html
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300"
       x-text="source === 'positions' ? 'Periodo' : 'Periodo B (Attuale)'"></label>
```

### Step 4: Update the Alpine.js loadData() function

In the `positionCompare()` function, add `source` and `prevSource` properties:

```javascript
source: '<?= e($currentSource ?? 'gsc') ?>',
prevSource: '<?= e($currentSource ?? 'gsc') ?>',
```

Update the `loadData()` method to include source:

```javascript
formData.append('source', this.source);
```

Update `exportCSV()` to include source:

```javascript
source: this.source,
```

### Step 5: Verify PHP syntax

Run: `php -l modules/seo-tracking/controllers/CompareController.php && php -l modules/seo-tracking/views/trend/index.php`
Expected: No syntax errors

### Step 6: Manual test in browser

1. Navigate to `http://localhost/seo-toolkit/seo-tracking/project/{id}/trend`
2. Verify "Fonte Dati" toggle appears (GSC selected by default)
3. Click "Keyword tracciate" — Period A should hide
4. Click "Confronta" — should show tracked keyword comparison
5. Switch back to GSC — both periods visible again

### Step 7: Commit

```bash
git add modules/seo-tracking/controllers/CompareController.php modules/seo-tracking/views/trend/index.php
git commit -m "feat(seo-tracking): add tracked-keyword source toggle to Trend page"
```

---

## Task 6: Final integration test and cleanup

**Step 1: Verify all PHP files**

Run:
```bash
php -l modules/seo-tracking/models/Keyword.php
php -l modules/seo-tracking/controllers/KeywordController.php
php -l modules/seo-tracking/views/keywords/index.php
php -l modules/seo-tracking/services/PositionCompareService.php
php -l modules/seo-tracking/controllers/CompareController.php
php -l modules/seo-tracking/views/trend/index.php
```

Expected: All `No syntax errors detected`

**Step 2: Manual integration test**

Test Keywords page:
1. `/seo-tracking/project/{id}/keywords` — normal view unchanged
2. Click "Confronta periodo" → select 28gg → "Confronta"
3. Verify Pos. Inizio, Pos. Fine, Delta columns show
4. Verify delta colors: green (improved), red (declined), blue (new), gray (lost)
5. Verify filters still work in compare mode
6. Click "Chiudi confronto" → back to normal
7. Test with `?date_from=2026-01-01&date_to=2026-02-24` in URL directly

Test Trend page:
1. `/seo-tracking/project/{id}/trend` — GSC mode works as before
2. Switch to "Keyword tracciate" — Period A hides
3. "Confronta" loads tracked keyword data
4. Export CSV works with both sources

**Step 3: Edge cases to verify**

- Project with no `st_keyword_positions` data → empty comparison, normal view shows normally
- Date range with no data → empty table with appropriate message
- Only one date filled → should not enter compare mode (both required)
- Very old dates (beyond retention) → empty or sparse results
