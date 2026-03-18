# Ads Evaluation Redesign — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the ads-analyzer evaluation page into a unified report with real metrics, hierarchical AI analysis (Campaign → Ad Group → Ad → Landing), Shopping/PMax product-level analysis, batch optimizations with Before→After preview, and one-click apply to Google Ads.

**Architecture:** Create `evaluation-v2.php` with 3 partials, fed by an enhanced `evaluationShow()` controller that merges sync metrics with AI analysis. The AI prompt is restructured for hierarchical output. Batch optimizations reuse existing `EvaluationGeneratorService` + `GoogleAdsService`. SSE replaces AJAX for evaluation progress. Old evaluations (schema_version=1) continue rendering with the existing view.

**Tech Stack:** PHP 8+, Alpine.js, Tailwind CSS (existing stack). No new dependencies.

**Spec:** `docs/plans/2026-03-17-ads-evaluation-redesign-design.md`

---

## File Map

### Files to Create

| File | Responsibility |
|---|---|
| `views/campaigns/evaluation-v2.php` | Main view shell — KPI bar, AI summary, negative KW summary inline. Includes 3 partials. Alpine.js `evaluationReport()` component. ~500-700 lines |
| `views/campaigns/partials/report-campaign-table.php` | 3-level expandable table: campaigns → ad groups → ads/keywords. Search and PMax templates. ~400-500 lines |
| `views/campaigns/partials/report-optimizations.php` | Batch optimizations list with state machine (Suggested → Generated → Applied), Before→After preview, batch toolbar. ~400-500 lines |
| `views/campaigns/partials/report-campaign-filter.php` | Pre-evaluation: campaign checkboxes with metrics, credit cost, "Avvia Analisi AI" button. ~100 lines |
| `database/migration-evaluation-redesign.sql` | Schema changes for ga_generated_fixes + ga_campaign_evaluations + ga_product_performance |
| `models/ProductPerformance.php` | Model for `ga_product_performance` table — CRUD, aggregation by brand/category |
| `views/campaigns/partials/report-product-analysis.php` | Product analysis section: brand performance bars, waste products, opportunities. ~200-300 lines |

### Files to Modify

| File | Changes |
|---|---|
| `controllers/CampaignController.php` | `evaluateStream()` SSE endpoint, `evaluationShow()` enhanced with sync metrics + delta + negatives summary + product data + ads data for Before→After, v2 routing |
| `services/CampaignSyncService.php` | Add `syncProductPerformance()` method — queries `shopping_performance_view` for Shopping/PMax campaigns |
| `services/CampaignEvaluatorService.php` | `buildPrompt()` restructured for hierarchical JSON: per-ad CTR, landing per ad group (1500 char), product analysis section, ENABLED filter, new response schema |
| `services/EvaluationGeneratorService.php` | Add `replace_asset`, `add_asset` PMax fix types, `cost_generate_fix` setting |
| `models/GeneratedFix.php` | Add `target_ad_index`, `asset_group_id_google` fields to create/find queries |
| `module.json` | Add `cost_generate_fix`, `max_landing_pages_per_eval` settings |
| `routes.php` | Add SSE evaluate-stream route |
| `cron/auto-evaluate.php` | Set `schema_version = 2` on new evaluations |

### Files NOT Modified

| File | Why |
|---|---|
| `views/campaigns/evaluation.php` | Kept for schema_version=1 backward compat |
| `controllers/SearchTermAnalysisController.php` | Negative KW stays separate |
| `services/GoogleAdsService.php` | Apply pipeline works as-is |
| `services/EvaluationCsvService.php` | CSV Ads Editor export works as-is |

---

## Data Flow Specification

### ROAS and CPA Calculation (not DB columns — computed in PHP)

```php
// In controller, for each campaign/ad_group/account level:
$roas = $cost > 0 ? round($conversionValue / $cost, 1) : 0;
$cpa = $conversions > 0 ? round($cost / $conversions, 2) : 0;
```

Applied at every level (account totals, per-campaign, per-ad-group) in the controller before passing to view.

### Sync Metrics Structure (passed to view as `$syncMetrics`)

```php
$syncMetrics = [
    'totals' => [
        'clicks' => 4230, 'impressions' => 132000, 'ctr' => 3.2,
        'cost' => 12450, 'conversions' => 134, 'conversion_value' => 52040,
        'roas' => 4.2, 'cpa' => 8.40  // computed
    ],
    'campaigns' => [
        [
            'campaign_id_google' => '123',
            'name' => 'Brand IT', 'type' => 'SEARCH', 'status' => 'ENABLED',
            'budget' => 150, 'bidding' => 'MAXIMIZE_CONVERSIONS',
            'clicks' => 2340, 'ctr' => 4.1, 'cost' => 3200,
            'conversions' => 89, 'conversion_value' => 16690,
            'roas' => 5.2, 'cpa' => 6.20,  // computed
            'ad_groups' => [
                [
                    'ad_group_id_google' => '456',
                    'name' => 'Scarpe Running', 'status' => 'ENABLED',
                    'clicks' => 1200, 'ctr' => 2.8, 'cost' => 3100,
                    'conversions' => 32, 'roas' => 3.6, 'cpa' => 9.70,
                    'ads' => [
                        [
                            'ad_index' => 1,  // sequential within ad group
                            'headline1' => 'Scarpe Running Uomo',
                            'headline2' => 'Spedizione Gratis',
                            'headline3' => 'Saldi -30%',
                            'description1' => 'Oltre 200 modelli...',
                            'description2' => 'Reso facile...',
                            'final_url' => 'https://example.com/scarpe-running',
                            'clicks' => 820, 'ctr' => 3.4
                        ],
                        // ad_index: 2 ...
                    ],
                    'keywords' => [
                        ['text' => 'scarpe running', 'match_type' => 'BROAD',
                         'clicks' => 450, 'ctr' => 3.1, 'quality_score' => 7, 'cpc' => 0.85],
                        // top 20 by clicks
                    ]
                ]
            ]
        ]
    ]
];
```

### Before→After Data Flow

For the optimization preview to show "Before → After":

1. **Before data** comes from `$syncMetrics.campaigns[].ad_groups[].ads[]` — the actual headlines/descriptions from the sync
2. **After data** comes from `$savedFixes[key].data` — the AI-generated replacement (stored in `ga_generated_fixes.ai_response` JSON)
3. **Matching**: the optimization's `target_ad_index` links to `ads[].ad_index` in sync data
4. **Controller** passes both `$syncMetrics` and `$savedFixes` to the view
5. **Alpine.js** does the match client-side: `syncAds[optimization.target_ad_index]` → Before, `generators[key].data` → After

### Delta Calculation

```php
// Controller loads previous completed sync for the same project
$prevSync = Sync::findPreviousCompleted($project['id'], $currentSync['id']);
$prevMetrics = $this->loadSyncMetrics($prevSync['id']);

// Delta per KPI at account level:
$metricDeltas = [
    'clicks' => ['current' => 4230, 'previous' => 3780, 'delta_pct' => 11.9, 'positive_is_good' => true],
    'cost'   => ['current' => 12450, 'previous' => 13560, 'delta_pct' => -8.2, 'positive_is_good' => false],
    // ... for all 6 KPIs
];
```

---

## Task 1: Database Migration + module.json + Model

**Files:**
- Create: `modules/ads-analyzer/database/migration-evaluation-redesign.sql`
- Modify: `modules/ads-analyzer/module.json`
- Modify: `modules/ads-analyzer/models/GeneratedFix.php`

- [ ] **Step 1: Write migration SQL**

```sql
-- Ads Evaluation Redesign Migration
-- Run: mysql -u root seo_toolkit < modules/ads-analyzer/database/migration-evaluation-redesign.sql
-- Prod: mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < migration-evaluation-redesign.sql

-- 1. schema_version for backward compat
ALTER TABLE ga_campaign_evaluations
ADD COLUMN schema_version INT NOT NULL DEFAULT 1 AFTER eval_type;

-- 2. Expand fix_type and scope_level from ENUM to VARCHAR
ALTER TABLE ga_generated_fixes
MODIFY COLUMN fix_type VARCHAR(50) NOT NULL,
MODIFY COLUMN scope_level VARCHAR(30) NOT NULL DEFAULT 'campaign';

-- 3. New columns for ad-level and PMax targeting
ALTER TABLE ga_generated_fixes
ADD COLUMN target_ad_index INT NULL AFTER ad_group_name,
ADD COLUMN asset_group_id_google VARCHAR(50) NULL AFTER ad_group_id_google;

-- 4. Product performance table for Shopping/PMax product analysis
CREATE TABLE IF NOT EXISTS ga_product_performance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  sync_id INT NOT NULL,
  campaign_id_google VARCHAR(50),
  campaign_name VARCHAR(255),
  product_item_id VARCHAR(255),
  product_title VARCHAR(500),
  product_brand VARCHAR(255),
  product_category_l1 VARCHAR(255),
  product_type_l1 VARCHAR(255),
  clicks INT DEFAULT 0,
  impressions INT DEFAULT 0,
  cost DECIMAL(10,2) DEFAULT 0,
  conversions DECIMAL(10,2) DEFAULT 0,
  conversion_value DECIMAL(12,2) DEFAULT 0,
  ctr DECIMAL(5,2) DEFAULT 0,
  avg_cpc DECIMAL(8,4) DEFAULT 0,
  roas DECIMAL(8,2) DEFAULT 0,
  cpa DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_project_sync (project_id, sync_id),
  INDEX idx_product (product_item_id),
  INDEX idx_brand (product_brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Run migration locally and verify**

```bash
mysql -u root seo_toolkit < modules/ads-analyzer/database/migration-evaluation-redesign.sql
mysql -u root seo_toolkit -e "DESCRIBE ga_campaign_evaluations;" | grep schema_version
mysql -u root seo_toolkit -e "DESCRIBE ga_generated_fixes;" | grep -E "fix_type|scope_level|target_ad|asset_group_id"
```

Expected: `schema_version` INT, `fix_type` VARCHAR(50), `scope_level` VARCHAR(30), `target_ad_index` INT, `asset_group_id_google` VARCHAR(50).

- [ ] **Step 3: Update module.json — add settings**

In `credits` object add:
```json
"generate_fix": {
    "cost": 1,
    "description": "Generazione singola ottimizzazione AI"
}
```

In `settings` object add `cost_generate_fix` (group: costs) and `max_landing_pages_per_eval` (group: general). See design spec Section 12.

Verify JSON: `php -r "json_decode(file_get_contents('modules/ads-analyzer/module.json')); echo json_last_error() === 0 ? 'OK' : 'ERROR';"`

- [ ] **Step 4: Update GeneratedFix model**

Add `target_ad_index` and `asset_group_id_google` to `create()` INSERT and to all `SELECT` queries (`findByEvaluationId()`, `findByIds()`).

Read the file first to find exact insertion points.

`php -l modules/ads-analyzer/models/GeneratedFix.php`

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/database/migration-evaluation-redesign.sql modules/ads-analyzer/module.json modules/ads-analyzer/models/GeneratedFix.php
git commit -m "feat(ads-analyzer): foundation — migration, settings, model for evaluation redesign"
```

---

## Task 2: Product Performance Sync + Model

**Files:**
- Create: `modules/ads-analyzer/models/ProductPerformance.php`
- Modify: `modules/ads-analyzer/services/CampaignSyncService.php`

- [ ] **Step 1: Create ProductPerformance model**

Simple model for `ga_product_performance` table. Methods needed:

```php
class ProductPerformance {
    // Save product data from sync
    public static function saveBatch(int $projectId, int $syncId, array $products): void

    // Get top products by spend for a sync
    public static function getTopBySpend(int $projectId, int $syncId, int $limit = 20): array

    // Get aggregated brand performance
    public static function getBrandSummary(int $projectId, int $syncId): array
    // Returns: [{brand, product_count, clicks, cost, conversions, conversion_value, roas, cpa}]

    // Get aggregated category performance
    public static function getCategorySummary(int $projectId, int $syncId): array

    // Get waste products (high spend, zero conversions)
    public static function getWasteProducts(int $projectId, int $syncId, int $limit = 10): array

    // Clean old sync data
    public static function deleteBySyncId(int $syncId): void
}
```

`php -l modules/ads-analyzer/models/ProductPerformance.php`

- [ ] **Step 2: Add `syncProductPerformance()` to CampaignSyncService**

After syncing campaigns/ad groups/ads/keywords, check if any campaign is SHOPPING or PERFORMANCE_MAX. If yes, query `shopping_performance_view`:

```php
private function syncProductPerformance(int $projectId, int $syncId, string $customerId): void
{
    $gaql = "SELECT
        segments.product_item_id, segments.product_title, segments.product_brand,
        segments.product_bidding_category_level1, segments.product_type_l1,
        campaign.id, campaign.name,
        metrics.clicks, metrics.impressions, metrics.cost_micros,
        metrics.conversions, metrics.conversions_value
    FROM shopping_performance_view
    WHERE segments.date DURING LAST_30_DAYS
        AND metrics.impressions > 0
    ORDER BY metrics.cost_micros DESC
    LIMIT 200";

    $results = $this->googleAdsService->search($gaql);
    // Transform cost_micros to EUR, compute ROAS/CPA, save via ProductPerformance::saveBatch()
}
```

Call this from the main `sync()` method, after syncing campaigns. Check if any synced campaign has `campaign_type` in ('SHOPPING', 'PERFORMANCE_MAX') before calling.

Use existing `ApiLoggerService` for logging the API call.

- [ ] **Step 3: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/models/ProductPerformance.php
php -l modules/ads-analyzer/services/CampaignSyncService.php
git add modules/ads-analyzer/models/ProductPerformance.php modules/ads-analyzer/services/CampaignSyncService.php
git commit -m "feat(ads-analyzer): add product performance sync from shopping_performance_view"
```

---

## Task 3: Restructure AI Prompt

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignEvaluatorService.php`

This is the most critical backend change. Read the entire file first (887 lines) to understand current prompt structure.

- [ ] **Step 1: Update `buildAdGroupDetailSection()`**

Changes:
1. **ENABLED filter**: only include ads with `ad_status = 'ENABLED'`, keywords with `keyword_status = 'ENABLED'`, ad groups with `ad_group_status = 'ENABLED'`
2. **Per-ad metrics**: for each ad, include `CTR: {n}% | Click: {n}` clearly on separate lines (current code already has headlines but metrics are less clear)
3. **Ad numbering**: add sequential `#1`, `#2` per ad group so AI can reference `target_ad_index`
4. **Cap ad groups**: keep existing 30 cap

- [ ] **Step 2: Update `buildLandingSection()`**

Changes:
1. **Content truncation**: 1500 chars instead of 3000
2. **Rich mapping**: for each URL, list ALL ad groups AND specific ads that point to it:
   ```
   Landing: https://example.com/scarpe-running
   Usata da: Ad Group "Scarpe Running" → Annuncio #1 (H1: Scarpe Running Uomo), Annuncio #2 (H1: Scarpe Online)
   Title: Scarpe Running Uomo | E-commerce
   Contenuto: [primi 1500 char]
   ```

- [ ] **Step 3: Update JSON response schema in prompt instructions**

Replace existing JSON schema with the new hierarchical one from design spec Section 5. Key additions:
- `campaigns[].metrics_comment` — AI comment citing specific data
- `campaigns[].ad_groups[].ads_analysis[]` — per-ad assessment with `ad_index`, `headlines[]`, `ctr`, `assessment`, `needs_rewrite`
- `campaigns[].ad_groups[].landing_analysis` — `url`, `coherence_score`, `analysis`, `suggestions[]`
- `campaigns[].ad_groups[].optimizations[]` — `type`, `priority`, `target_ad_index`, `reason`, `scope`
- `campaigns[].asset_group_analysis[].optimizations[]` — for PMax
- `top_recommendations[].type`, `.impact`, `.estimated_saving`

Keep `max_tokens: 8192`.

- [ ] **Step 4: Update PMax section**

In `buildPmaxSection()`: add ENABLED filter for asset groups. Response schema already handled in step 3.

- [ ] **Step 5: Add `buildProductSection()` method**

New method that loads product data from `ga_product_performance` for the current sync and formats it for the AI prompt. Only called if Shopping/PMax campaigns are present.

```php
private function buildProductSection(int $projectId, int $syncId): string
{
    $topProducts = ProductPerformance::getTopBySpend($projectId, $syncId, 20);
    $brandSummary = ProductPerformance::getBrandSummary($projectId, $syncId);
    $wasteProducts = ProductPerformance::getWasteProducts($projectId, $syncId, 10);

    if (empty($topProducts)) return '';

    $section = "\nANALISI PRODOTTI (ultimi 30 giorni):\n\n";
    $section .= "Top 20 prodotti per spesa:\n";
    $section .= "| SKU | Titolo | Brand | Cat. | Click | Spesa | Conv. | ROAS |\n";
    foreach ($topProducts as $p) {
        $section .= "| {$p['product_item_id']} | {$p['product_title']} | {$p['product_brand']} | {$p['product_category_l1']} | {$p['clicks']} | €{$p['cost']} | {$p['conversions']} | {$p['roas']}x |\n";
    }
    // ... brand summary table, category summary, waste products ...
    return $section;
}
```

Add product analysis instructions to the JSON schema:
- `product_analysis.summary` — AI assessment of product performance
- `product_analysis.top_brands[]` — brand ranking with assessment
- `product_analysis.waste_products[]` — products to exclude/reduce
- `product_analysis.opportunities[]` — products to push
- `product_analysis.category_insights[]` — category-level observations
- `product_analysis.optimizations[]` — product-level suggested actions

- [ ] **Step 6: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/services/CampaignEvaluatorService.php
git add modules/ads-analyzer/services/CampaignEvaluatorService.php
git commit -m "feat(ads-analyzer): restructure AI prompt with product analysis for Shopping/PMax"
```

---

## Task 4: Enhanced Controller — evaluationShow() + evaluate()

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php`

- [ ] **Step 1: Create `loadSyncMetrics($syncId, $campaignFilter = null)` private method**

Returns the `$syncMetrics` structure documented in Data Flow above. Queries:
- `ga_campaigns` WHERE `sync_id = ? AND campaign_status = 'ENABLED'`
- `ga_campaign_ad_groups` WHERE `sync_id = ? AND ad_group_status = 'ENABLED'`
- `ga_ads` WHERE `sync_id = ? AND ad_status = 'ENABLED'`
- `ga_ad_group_keywords` WHERE `sync_id = ? AND keyword_status = 'ENABLED'`

Computes ROAS and CPA at each level. Groups ad_groups under campaigns, ads under ad_groups, keywords under ad_groups. If `$campaignFilter` provided, filters by `campaign_id_google IN (...)`.

Assigns sequential `ad_index` to each ad within its ad group (1, 2, 3...).

- [ ] **Step 2: Create `calculateMetricDeltas($currentMetrics, $previousMetrics)` private method**

Compares totals from two sync metrics arrays. Returns delta structure for 6 KPIs: clicks, cost, ctr, conversions, roas, cpa. Each with `current`, `previous`, `delta_pct`, `positive_is_good`.

- [ ] **Step 3: Create `buildNegativeKeywordsSummary($projectId)` private method**

Queries `ga_analyses` for latest completed analysis. Counts keywords by priority. Counts applied (where `applied_at IS NOT NULL`). Sums estimated waste from `ga_search_terms`. Returns array or null if no analysis exists.

- [ ] **Step 4: Enhance `evaluationShow()` method**

After loading evaluation and aiResponse (existing code), add:

```php
// Schema version routing
$schemaVersion = (int)($evaluation['schema_version'] ?? 1);

if ($schemaVersion >= 2) {
    // Load sync metrics with ROAS/CPA computed
    $syncMetrics = $this->loadSyncMetrics($evaluation['sync_id'],
        json_decode($evaluation['campaigns_filter'] ?? 'null', true));

    // Load previous sync for deltas
    $prevSync = Sync::findPreviousCompleted($project['id'], $evaluation['sync_id']);
    $metricDeltas = $prevSync
        ? $this->calculateMetricDeltas(
            $syncMetrics['totals'],
            $this->loadSyncMetrics($prevSync['id'])['totals']
          )
        : null;

    // Negative KW summary
    $negativeSummary = $this->buildNegativeKeywordsSummary($project['id']);

    $viewName = 'ads-analyzer::campaigns/evaluation-v2';
    $extraVars = [
        'syncMetrics' => $syncMetrics,
        'metricDeltas' => $metricDeltas,
        'negativeSummary' => $negativeSummary,
    ];
} else {
    $viewName = 'ads-analyzer::campaigns/evaluation';
    $extraVars = [];
}
```

- [ ] **Step 5: Update `evaluate()` — scraping enhancement**

Remove hard-coded 5 URL limit (line ~565). Replace with:
```php
$maxLanding = (int) ModuleLoader::getSetting('ads-analyzer', 'max_landing_pages_per_eval', 25);
```

Collect ALL unique ENABLED `final_url` from `ga_ads`, up to `$maxLanding`. Truncate content to 1500 chars. Map each URL to ad groups + ad indices.

Set `schema_version = 2` when creating the evaluation record.

- [ ] **Step 6: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): enhanced evaluationShow with sync metrics, deltas, negatives"
```

---

## Task 5: SSE Evaluation Endpoint

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` — add `evaluateStream()`
- Modify: `modules/ads-analyzer/routes.php`

- [ ] **Step 1: Create `evaluateStream($projectId)` method**

Pattern reference: `modules/seo-tracking/controllers/RankCheckController.php::processStream()`

Wraps the existing `evaluate()` logic but sends SSE progress events:

```php
public function evaluateStream($projectId): void {
    Middleware::auth();
    $user = Auth::user();
    $project = Project::findAccessible($projectId, $user['id']);
    // ... validation, credit check (same as evaluate()) ...

    ignore_user_abort(true);
    set_time_limit(300);
    session_write_close();

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    // Helper
    $sendEvent = function($event, $data) {
        echo "event: {$event}\ndata: " . json_encode($data) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    };

    $sendEvent('started', ['message' => 'Avvio analisi...']);

    // 1. Scraping landing pages
    $totalUrls = count($uniqueUrls);
    foreach ($uniqueUrls as $i => $url) {
        $sendEvent('progress', [
            'step' => 'scraping',
            'current' => $i + 1,
            'total' => $totalUrls,
            'message' => "Analisi landing page " . ($i + 1) . "/{$totalUrls}..."
        ]);
        // ... scrape ...
    }

    // 2. AI Analysis
    $sendEvent('progress', ['step' => 'analyzing', 'message' => 'Analisi AI in corso...']);
    $aiResult = $evaluator->evaluate(...);
    Database::reconnect();

    // 3. Save results
    $sendEvent('progress', ['step' => 'saving', 'message' => 'Salvataggio risultati...']);
    // ... save to DB, consume credits ...

    // 4. Complete
    $sendEvent('completed', [
        'evaluation_id' => $evaluationId,
        'redirect' => url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evaluationId}")
    ]);
    exit;
}
```

Error handling: wrap in try/catch, send `error` event on exception.

- [ ] **Step 2: Add route**

```php
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluate-stream',
    fn($id) => (new CampaignController())->evaluateStream($id));
```

- [ ] **Step 3: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/routes.php
git add modules/ads-analyzer/controllers/CampaignController.php modules/ads-analyzer/routes.php
git commit -m "feat(ads-analyzer): add SSE evaluate-stream endpoint with progress events"
```

---

## Task 6: PMax Generator + cost_generate_fix

**Files:**
- Modify: `modules/ads-analyzer/services/EvaluationGeneratorService.php`

- [ ] **Step 0: Verify PMax asset mutation API capability**

Before implementing, check if `GoogleAdsService` can mutate PMax asset groups. Currently there is NO `mutateAssetGroups()` method. Check if `groupedMutate()` supports asset group operations.

**If NOT supported**: PMax optimizations show only "Genera" + "Esporta CSV" buttons. NO "Applica su Google Ads" for PMax. Add a note in the UI: "L'applicazione automatica per PMax non è ancora disponibile. Usa il CSV per importare manualmente."

**If supported**: implement apply flow same as Search.

This check MUST happen before writing any PMax apply code.

- [ ] **Step 1: Add `replace_asset` and `add_asset` fix types**

Read the current file first. Follow the pattern of `generateRewriteAds()` for prompt building.

For `replace_asset`:
- Input: current LOW asset text, asset type, asset group context (search themes, audience signals)
- Output: replacement asset text of the same type
- Prompt: "Genera un asset {type} sostitutivo per l'asset group '{name}'. L'asset attuale '{text}' ha performance LOW. Search themes: {themes}. Genera un testo migliore, più specifico e pertinente."

For `add_asset`:
- Input: missing asset types, asset group context
- Output: new assets to fill gaps
- Prompt: "Genera {count} asset {type} per l'asset group '{name}'. Mancano asset {missing_types}. Search themes: {themes}."

- [ ] **Step 2: Use `cost_generate_fix` setting**

Replace any hardcoded credit cost in generate with:
```php
$cost = Credits::getCost('generate_fix', 'ads-analyzer', 1);
```

- [ ] **Step 3: Pass `target_ad_index` through to GeneratedFix::create()**

When the request includes `target_ad_index`, pass it to the model's create method.

- [ ] **Step 4: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
git add modules/ads-analyzer/services/EvaluationGeneratorService.php
git commit -m "feat(ads-analyzer): add PMax fix types and cost_generate_fix to generator"
```

---

## Task 7: Main View — evaluation-v2.php

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/evaluation-v2.php`

- [ ] **Step 1: Create view shell with inline sections**

Structure (following existing evaluation.php patterns and GR #20, #22):

```
project-nav include
<div x-data="evaluationReport()">

  IF isAnalyzing → SSE progress UI (EventSource client)
  IF isError → error card (reuse from evaluation.php)
  IF isNoChange → no change card (reuse from evaluation.php)
  IF hasResults →

    INLINE: Header (period, metadata, PDF export, back link)

    INLINE: KPI Bar — 6 cards grid
      For each of [clicks, cost, ctr, conversions, roas, cpa]:
        Card with: label, value (formatted), delta % with arrow + color

    INCLUDE: partials/report-campaign-table.php

    INLINE: AI Summary card
      Score circle (reuse scoreColorClass pattern)
      Summary text from $aiResponse['summary']
      Top recommendations list

    INCLUDE: partials/report-optimizations.php

    INLINE: Negative KW Summary card (if $negativeSummary)
      Waste total, counts by priority, applied count
      Link to search-term-analysis

</div>

<script>
function evaluationReport() { ... }
</script>
```

The SSE client for analyzing state:
```javascript
function evaluationSSE(streamUrl) {
    return {
        message: 'Avvio analisi...', step: '', current: 0, total: 0, error: null,
        init() {
            const es = new EventSource(streamUrl);
            es.addEventListener('progress', e => {
                const d = JSON.parse(e.data);
                Object.assign(this, d);
            });
            es.addEventListener('completed', e => {
                window.location.href = JSON.parse(e.data).redirect;
            });
            es.addEventListener('error', e => {
                this.error = 'Errore di connessione. Riprova.';
                es.close();
            });
        }
    };
}
```

- [ ] **Step 2: Implement `evaluationReport()` Alpine component**

Key state and methods:
- `expandedCampaigns: {}`, `expandedAdGroups: {}` — toggle objects
- `selectedOptimizations: {}` — checkbox state for batch
- `generators: {}` — generated fix state (loading/result/error/copied/applied)
- `savedFixes` — preloaded from PHP (existing pattern from evaluation.php init())
- `campaignsData` — merged sync + AI data, precomputed in PHP
- `generateFix(type, context, key)` — POST to generate endpoint (reuse from evaluation.php)
- `generateAll()` — sequential fetch loop with counter: "Generazione 3/8..."
- `applySelected()` — single confirmation modal, POST to apply endpoint
- `copyResult(key)`, `exportCsv(key)` — reuse from evaluation.php

For `generateAll()`:
```javascript
async generateAll() {
    const pending = Object.entries(this.allOptimizations).filter(([k,v]) => !this.generators[k]?.result);
    const totalCost = pending.length * <?= Credits::getCost('generate_fix', 'ads-analyzer', 1) ?>;
    if (!confirm(`Generare ${pending.length} ottimizzazioni? Costo: ${totalCost} crediti`)) return;

    this.generatingAll = true;
    this.genAllCurrent = 0;
    this.genAllTotal = pending.length;

    for (const [key, opt] of pending) {
        this.genAllCurrent++;
        await this.generateFix(opt.type, opt.context, key);
    }
    this.generatingAll = false;
}
```

- [ ] **Step 3: PHP data preparation for Alpine**

At the top of the view (after PHP variables from controller), prepare `$viewCampaigns` array that merges sync metrics + AI response for each campaign. This is the single data source Alpine reads:

```php
$viewCampaigns = [];
foreach ($syncMetrics['campaigns'] as $cIdx => $syncCamp) {
    $aiCamp = null;
    foreach ($aiResponse['campaigns'] ?? [] as $ac) {
        if ($ac['campaign_name'] === $syncCamp['name']) { $aiCamp = $ac; break; }
    }
    $viewCampaigns[] = [
        'sync' => $syncCamp,  // metrics, ad_groups with ads
        'ai' => $aiCamp,      // score, metrics_comment, ad_groups with analysis
        'type' => $syncCamp['type'],
        'isPmax' => $syncCamp['type'] === 'PERFORMANCE_MAX',
    ];
}
```

Also extract all optimizations into a flat list for the batch toolbar:
```php
$allOptimizations = [];
foreach ($aiResponse['campaigns'] ?? [] as $cIdx => $camp) {
    foreach ($camp['ad_groups'] ?? [] as $agIdx => $ag) {
        foreach ($ag['optimizations'] ?? [] as $oIdx => $opt) {
            $key = "opt_{$cIdx}_{$agIdx}_{$oIdx}";
            $opt['campaign_name'] = $camp['campaign_name'];
            $opt['ad_group_name'] = $ag['ad_group_name'];
            $allOptimizations[$key] = $opt;
        }
    }
    // Same for asset_group_analysis optimizations
}
```

- [ ] **Step 4: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
git add modules/ads-analyzer/views/campaigns/evaluation-v2.php
git commit -m "feat(ads-analyzer): create evaluation-v2 view with KPI bar, AI summary, SSE client"
```

---

## Task 8: Campaign Table Partial (3-level expandable)

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php`

- [ ] **Step 1: Level 1 — Campaign rows**

Table following GR #20 CSS standards: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`.

Columns: Campagna (with type badge), Click, CTR, Spesa, Conv., Val. Conv., ROAS, CPA, AI Score (dot).

Type badges reuse `$campaignTypeConfig` from existing evaluation.php.
ROAS colored: emerald ≥4, amber ≥2, red <2.
CPA colored: red if above account average CPA.
AI dot: emerald (score ≥7), amber (≥5), red (<5).

Each row is `@click="expandedCampaigns[{idx}] = !expandedCampaigns[{idx}]"`.

Data: `$viewCampaigns[].sync` for metrics, `$viewCampaigns[].ai.score` for AI dot.

- [ ] **Step 2: Level 2 — Ad Groups (inside expanded campaign)**

Shown when `expandedCampaigns[idx]` is true.

For **Search campaigns**:
- AI `metrics_comment` box (from `$viewCampaigns[].ai.metrics_comment`)
- Sub-table: Ad Group, Click, CTR, Spesa, Conv., ROAS, CPA, Landing URL (truncated)
- Each row clickable: `expandedAdGroups['{cIdx}_{agIdx}']`

For **PMax campaigns**:
- Sub-table: Asset Group, Ad Strength (badge), Click, Spesa, Conv., ROAS, CPA
- Ad strength badges: EXCELLENT (green), GOOD (blue), AVERAGE (amber), POOR (red)
- Each row clickable to expand asset detail

Data: `$viewCampaigns[].sync.ad_groups[]` for metrics.

- [ ] **Step 3: Level 3 — Ads + Keywords (inside expanded ad group)**

For **Search ad groups**:
- **Ads table**: #, H1, H2, H3, D1 (first 40 chars), CTR, Click
  - Lowest CTR row highlighted with `bg-red-50 dark:bg-red-900/10`
  - AI per-ad assessment from `$viewCampaigns[].ai.ad_groups[].ads_analysis[]`
- **Landing analysis box**: URL, coherence score badge, AI analysis text, suggestions list
  - From `$viewCampaigns[].ai.ad_groups[].landing_analysis`
- **Top keywords** (10 by click): keyword, match type badge (BROAD/PHRASE/EXACT), Click, CTR

For **PMax asset groups**:
- Asset inventory by type with performance label badges (BEST green, GOOD blue, LOW red, LEARNING gray)
- LOW assets listed with highlight
- Missing asset types flagged
- AI analysis text

Data: `$viewCampaigns[].sync.ad_groups[].ads[]` and `.keywords[]` for metrics, `$viewCampaigns[].ai.ad_groups[].ads_analysis[]` and `.landing_analysis` for AI.

- [ ] **Step 4: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
git add modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
git commit -m "feat(ads-analyzer): add 3-level expandable campaign table partial"
```

---

## Task 9: Product Analysis Partial

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-product-analysis.php`

Visibile SOLO per campagne Shopping/PMax con dati prodotto nel sync.

- [ ] **Step 1: Create product analysis section**

Uses `$productData` passed from controller (aggregated from `ProductPerformance` model).

Structure:
```
📦 Analisi Prodotti (ultimi 30 giorni)

Brand Performance (horizontal bars):
██████████████ Nike     ROAS 5,2x  €5.400  89 conv.
████████       Adidas   ROAS 3,1x  €2.800  34 conv.
███            Geox     ROAS 1,8x  €2.100  12 conv.  ⚠️

⚠️ Prodotti Spreco ({count} prodotti, €{total}/mese con 0 conversioni):
Table: Titolo | Brand | Click | Spesa | Impressioni
[expandable to show all]

💡 Opportunità (alto CTR, basse impressioni):
Table: Titolo | Brand | CTR | Impressioni | Suggerimento AI

AI Analysis:
"{product_analysis.summary from AI response}"
```

Brand bars use simple CSS width proportional to spend: `width: {brand_spend / max_spend * 100}%`.
ROAS colored: emerald ≥4, amber ≥2, red <2.

- [ ] **Step 2: Add product data to controller**

In `evaluationShow()` (Task 4), after loading sync metrics, add:

```php
// Load product data if Shopping/PMax campaigns present
$hasShoppingCampaigns = !empty(array_filter($syncMetrics['campaigns'],
    fn($c) => in_array($c['type'], ['SHOPPING', 'PERFORMANCE_MAX'])));

$productData = null;
if ($hasShoppingCampaigns) {
    $productData = [
        'brands' => ProductPerformance::getBrandSummary($project['id'], $evaluation['sync_id']),
        'waste' => ProductPerformance::getWasteProducts($project['id'], $evaluation['sync_id']),
        'top' => ProductPerformance::getTopBySpend($project['id'], $evaluation['sync_id'], 20),
    ];
}
// Pass $productData to view
```

- [ ] **Step 3: Include partial in evaluation-v2.php**

Add between campaign table and AI summary:
```php
<?php if ($productData): ?>
    <?php include __DIR__ . '/partials/report-product-analysis.php'; ?>
<?php endif; ?>
```

- [ ] **Step 4: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-product-analysis.php
git add modules/ads-analyzer/views/campaigns/partials/report-product-analysis.php
git commit -m "feat(ads-analyzer): add product analysis partial for Shopping/PMax campaigns"
```

---

## Task 10: Optimizations Batch Partial

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-optimizations.php`

- [ ] **Step 1: Optimization cards with state machine**

Each optimization from `$allOptimizations` rendered as a card with three states:

**State: SUGGESTED** (no generated fix yet)
- Checkbox (Alpine: `selectedOptimizations[key]`)
- Priority badge (alta = red, media = amber, bassa = blue)
- Type badge (Annunci, Estensioni, Asset PMax, Landing Page)
- Scope: "Campaign → Ad Group (→ Annuncio #N)"
- Reason text from AI
- Button: "Genera Ottimizzazione" → calls `generateFix()`

**State: GENERATED** (fix exists in generators[key])
- Same as above, plus:
- **Before→After preview box**:
  ```
  For rewrite_ads:
    H1: [old H1 in red strikethrough] → [new H1 in green]
    H2: [old H2 in red strikethrough] → [new H2 in green]
    ...

  For replace_asset:
    [old asset text in red] → [new asset text in green]

  For add_extensions:
    + [new extension text in green]
  ```
- Before data: `campaignsData[campIdx].sync.ad_groups[agIdx].ads[targetAdIndex]`
- After data: `generators[key].data` (from AI generation response)
- Buttons: "Rigenera", "Esporta CSV Ads Editor", "Applica su Google Ads"

**State: APPLIED**
- Green background, checkmark icon
- "Applicato il DD/MM/YYYY HH:MM"
- "Nuovo annuncio RSA creato IN PAUSA" (or "Estensione creata" etc.)

- [ ] **Step 2: Batch toolbar**

Sticky at top of optimizations section:

```html
<div class="sticky top-0 z-10 bg-white dark:bg-slate-800 border-b ...">
    <span>⚡ {total} Ottimizzazioni</span>
    <span>{generated} generate</span>
    <span>{selected} selezionate</span>

    <!-- Genera Tutte with progress counter -->
    <button @click="generateAll()" :disabled="generatingAll">
        <span x-show="!generatingAll">Genera Tutte (costo: {cost} crediti)</span>
        <span x-show="generatingAll">Generazione <span x-text="genAllCurrent"></span>/<span x-text="genAllTotal"></span>...</span>
    </button>

    <!-- Applica Selezionate -->
    <button @click="applySelected()" :disabled="selectedCount === 0">
        Applica Selezionate
    </button>
</div>
```

- [ ] **Step 3: Apply confirmation modal**

Single confirmation (simplified from current double confirmation):

```html
<div x-show="applyModal.open">
    <h3>Applica Ottimizzazioni su Google Ads</h3>
    <p>Stai per applicare {count} ottimizzazioni:</p>
    <ul>
        <li x-show="applyModal.newAds > 0">{N} nuovi annunci (creati IN PAUSA)</li>
        <li x-show="applyModal.newExtensions > 0">{N} nuove estensioni (attive)</li>
        <li x-show="applyModal.newAssets > 0">{N} nuovi asset PMax</li>
    </ul>
    <button @click="executeApply()">Conferma e Applica</button>
    <button @click="applyModal.open = false">Annulla</button>
</div>
```

Uses existing `applyToGoogleAds()` endpoint — no backend changes needed.

- [ ] **Step 4: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-optimizations.php
git add modules/ads-analyzer/views/campaigns/partials/report-optimizations.php
git commit -m "feat(ads-analyzer): add batch optimizations partial with Before/After preview"
```

---

## Task 11: Campaign Filter Partial

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php`

- [ ] **Step 1: Create filter view**

Shown as a modal or inline section when user clicks "Valuta con AI" from the campaigns page. Lists all ENABLED campaigns from the latest completed sync.

```php
<!-- Campaign checkboxes -->
<?php foreach ($filterCampaigns as $fc): ?>
<label class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
    <input type="checkbox" name="campaigns_filter[]" value="<?= $fc['campaign_id_google'] ?>"
           checked class="rounded text-rose-600">
    <div class="flex-1">
        <div class="flex items-center gap-2">
            <span class="badge"><?= $fc['type_label'] ?></span>
            <span class="font-medium"><?= e($fc['name']) ?></span>
        </div>
        <div class="text-xs text-slate-500">
            <?= number_format($fc['clicks']) ?> click · €<?= number_format($fc['cost'], 0, ',', '.') ?> · ROAS <?= number_format($fc['roas'], 1, ',', '.') ?>x
        </div>
    </div>
</label>
<?php endforeach; ?>

<div class="border-t pt-4 flex items-center justify-between">
    <span class="text-sm text-slate-500">
        <span x-text="selectedCount"></span> campagne · Costo: <?= Credits::getCost('campaign_evaluation', 'ads-analyzer', 10) ?> crediti
    </span>
    <button @click="startEvaluation()" class="btn-primary">Avvia Analisi AI</button>
</div>
```

The `startEvaluation()` Alpine method POSTs to the SSE evaluate-stream endpoint with the selected campaign IDs, then starts listening for SSE events.

- [ ] **Step 2: Integrate with campaigns page**

The filter is triggered from the existing "Valuta" button on the campaigns index page. It can be a modal overlay or an inline expandable section above the sync history.

Backend: add a data endpoint or include the campaign list in the existing campaigns index controller.

- [ ] **Step 3: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php
git add modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php
git commit -m "feat(ads-analyzer): add campaign filter partial for pre-evaluation selection"
```

---

## Task 12: Auto-Evaluate Cron Update

**Files:**
- Modify: `modules/ads-analyzer/cron/auto-evaluate.php`

- [ ] **Step 1: Set schema_version=2**

When creating the evaluation record, add `schema_version = 2` to the INSERT. Find the exact line where `ga_campaign_evaluations` is created and add the column.

Also ensure the ENABLED filter is applied when loading campaigns/ad groups for the prompt.

- [ ] **Step 2: Verify syntax and commit**

```bash
php -l modules/ads-analyzer/cron/auto-evaluate.php
git add modules/ads-analyzer/cron/auto-evaluate.php
git commit -m "feat(ads-analyzer): auto-evaluate writes schema_version=2"
```

---

## Task 13: Integration Testing

- [ ] **Step 1: PHP syntax check all files**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/services/CampaignEvaluatorService.php
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
php -l modules/ads-analyzer/models/GeneratedFix.php
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
php -l modules/ads-analyzer/views/campaigns/partials/report-optimizations.php
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php
php -l modules/ads-analyzer/cron/auto-evaluate.php
```

All must pass with "No syntax errors detected".

- [ ] **Step 2: Browser test — Campaign filter + SSE**

1. Login: `http://localhost/seo-toolkit` (admin@seo-toolkit.local / admin123)
2. Navigate to ads-analyzer project with synced data
3. Click "Valuta con AI"
4. Verify campaign filter shows with checkboxes and metrics
5. Select 2 campaigns, click "Avvia Analisi AI"
6. Verify SSE progress: "Analisi landing page 1/5...", "Analisi AI in corso..."
7. After completion: redirect to evaluation-v2 page

- [ ] **Step 3: Browser test — Evaluation v2 report**

On the evaluation results page, verify:
1. **KPI bar**: 6 cards with correct values and delta % (compare with sync data in DB)
2. **Campaign table level 1**: all ENABLED campaigns with metrics, ROAS/CPA computed correctly
3. **Click campaign → level 2**: ad groups with metrics + AI comment box
4. **Click ad group → level 3**: ads table with H1-H3, CTR per ad. AI assessment. Landing analysis. Keywords.
5. **AI Summary**: score, summary text with specific numbers, top recommendations
6. **Optimizations list**: correct issues from AI, priority badges, "Genera" buttons

- [ ] **Step 4: Browser test — Genera + Preview + Applica**

1. Click "Genera Ottimizzazione" on a rewrite_ads item
2. Verify Before→After preview shows (old headlines strikethrough, new in green)
3. Click "Genera Tutte" → verify credit cost confirmation, sequential progress counter
4. Select 2 optimizations with checkboxes
5. Click "Applica Selezionate" → verify single confirmation modal
6. (If Google Ads connected) Verify apply executes and status changes to "Applicato"
7. Verify "Esporta CSV Ads Editor" downloads correct CSV

- [ ] **Step 5: Browser test — PMax**

1. If project has PMax campaigns, verify:
   - Asset group rows instead of ad group rows
   - Ad Strength badges (POOR/GOOD/EXCELLENT)
   - Asset inventory with performance labels
   - PMax-specific optimizations (replace_asset, add_asset)

- [ ] **Step 6: Browser test — Backward compat**

1. Find an old evaluation (schema_version=1 or NULL) in the DB
2. Navigate to it → verify old evaluation.php renders correctly
3. New evaluations → verify evaluation-v2.php renders

- [ ] **Step 7: Fix any issues found and commit**

```bash
git add -A
git commit -m "fix(ads-analyzer): integration fixes for evaluation v2"
```

---

## Task 14: Documentation

**Files:**
- Modify: `shared/views/docs/ads-analyzer.php`
- Modify: `docs/data-model.html`

- [ ] **Step 1: Update user docs**

In `shared/views/docs/ads-analyzer.php`, add/update section about evaluation:
- New report layout: KPI → Tabella → AI → Ottimizzazioni
- Campaign filter pre-evaluation
- Before→After preview
- Batch apply flow
- Search vs PMax differences

- [ ] **Step 2: Update data model**

In `docs/data-model.html`:
- `ga_campaign_evaluations`: add `schema_version INT`
- `ga_generated_fixes`: add `target_ad_index INT NULL`, `asset_group_id_google VARCHAR(50) NULL`, note `fix_type` and `scope_level` changed from ENUM to VARCHAR

- [ ] **Step 3: Commit**

```bash
git add shared/views/docs/ads-analyzer.php docs/data-model.html
git commit -m "docs(ads-analyzer): update user docs and data model for evaluation redesign"
```
