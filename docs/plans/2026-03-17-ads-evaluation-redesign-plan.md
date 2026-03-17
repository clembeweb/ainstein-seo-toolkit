# Ads Evaluation Redesign — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the ads-analyzer evaluation page into a unified report with real metrics, hierarchical AI analysis (Campaign → Ad Group → Ad → Landing), batch optimizations with Before→After preview, and one-click apply to Google Ads.

**Architecture:** Rewrite `evaluation.php` as a partials-based view fed by an enhanced controller that merges sync metrics with AI analysis. The AI prompt is restructured for hierarchical output (campaign → ad group → ad → landing). Batch optimizations reuse existing `EvaluationGeneratorService` + `GoogleAdsService` apply pipeline. SSE replaces AJAX for progress during evaluation.

**Tech Stack:** PHP 8+, Alpine.js, Tailwind CSS, HTMX (existing stack). No new dependencies.

**Spec:** `docs/plans/2026-03-17-ads-evaluation-redesign-design.md`

---

## File Map

### Files to Create

| File | Responsibility |
|---|---|
| `views/campaigns/evaluation-v2.php` | Main view — includes all partials, Alpine.js `evaluationReport()` component |
| `views/campaigns/partials/report-header.php` | Period selector, PDF export, evaluation metadata |
| `views/campaigns/partials/report-kpi-bar.php` | 6 KPI cards with delta % |
| `views/campaigns/partials/report-campaign-table.php` | 3-level expandable table (campaign → ad group → ads) |
| `views/campaigns/partials/report-ai-summary.php` | AI score, summary, top recommendations |
| `views/campaigns/partials/report-optimizations.php` | Batch optimizations with genera/preview/apply |
| `views/campaigns/partials/report-negative-summary.php` | Negative KW summary + link to dedicated section |
| `views/campaigns/partials/report-campaign-filter.php` | Pre-evaluation campaign selection with checkboxes |
| `database/migration-evaluation-redesign.sql` | Schema changes for ga_generated_fixes + ga_campaign_evaluations |

### Files to Modify

| File | Changes |
|---|---|
| `controllers/CampaignController.php` | `evaluate()`: SSE, all URLs scraping, campaign filter UI data. `evaluationShow()`: merge sync metrics + AI for v2 view. New `evaluateStream()` SSE endpoint |
| `services/CampaignEvaluatorService.php` | `buildPrompt()`: new hierarchical structure with per-ad CTR, landing per ad group, 1500 char content. New JSON response schema |
| `services/EvaluationGeneratorService.php` | Add `replace_asset`, `add_asset` fix types for PMax |
| `models/GeneratedFix.php` | Add `target_ad_index`, `asset_group_id_google` fields |
| `module.json` | Add `cost_generate_fix`, `max_landing_pages_per_eval` settings |
| `routes.php` | Add SSE evaluate stream route, campaign filter data route |

### Files NOT Modified (reused as-is)

| File | Why |
|---|---|
| `controllers/SearchTermAnalysisController.php` | Negative KW feature stays separate |
| `services/GoogleAdsService.php` | Apply pipeline already works |
| `services/EvaluationCsvService.php` | CSV Ads Editor export already works |
| `models/CampaignEvaluation.php` | No structural changes needed |
| `cron/auto-evaluate.php` | Compatible with new prompt (writes schema_version=2) |

---

## Chunk 1: Database + Settings + Models (Foundation)

### Task 1: Database Migration

**Files:**
- Create: `modules/ads-analyzer/database/migration-evaluation-redesign.sql`

- [ ] **Step 1: Write migration SQL**

```sql
-- Ads Evaluation Redesign Migration
-- Run on both local (seo_toolkit) and production (ainstein_seo)

-- 1. ga_campaign_evaluations: add schema version for backward compat
ALTER TABLE ga_campaign_evaluations
ADD COLUMN schema_version INT NOT NULL DEFAULT 1 AFTER eval_type;

-- 2. ga_generated_fixes: change ENUMs to VARCHAR for extensibility
ALTER TABLE ga_generated_fixes
MODIFY COLUMN fix_type VARCHAR(50) NOT NULL,
MODIFY COLUMN scope_level VARCHAR(30) NOT NULL DEFAULT 'campaign';

-- 3. ga_generated_fixes: add PMax and ad-level columns
ALTER TABLE ga_generated_fixes
ADD COLUMN target_ad_index INT NULL AFTER ad_group_name,
ADD COLUMN asset_group_id_google VARCHAR(50) NULL AFTER ad_group_id_google;
```

- [ ] **Step 2: Test migration locally**

```bash
mysql -u root seo_toolkit < modules/ads-analyzer/database/migration-evaluation-redesign.sql
```

Verify: `DESCRIBE ga_generated_fixes;` — check `fix_type` is VARCHAR(50), `target_ad_index` exists, `asset_group_id_google` exists.
`DESCRIBE ga_campaign_evaluations;` — check `schema_version` exists.

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/database/migration-evaluation-redesign.sql
git commit -m "feat(ads-analyzer): add migration for evaluation redesign schema"
```

---

### Task 2: Update module.json Settings

**Files:**
- Modify: `modules/ads-analyzer/module.json`

- [ ] **Step 1: Add new settings**

Add to `settings` object:

```json
"cost_generate_fix": {
    "type": "number",
    "label": "Costo Generazione Ottimizzazione",
    "description": "Crediti per generazione singola ottimizzazione (Before/After)",
    "default": 1,
    "min": 0,
    "step": 0.5,
    "admin_only": true,
    "group": "costs"
},
"max_landing_pages_per_eval": {
    "type": "number",
    "label": "Max landing pages per valutazione",
    "description": "Numero massimo di landing page da analizzare durante la valutazione AI",
    "default": 25,
    "min": 5,
    "max": 50,
    "group": "general"
}
```

Also add to `credits` object:

```json
"generate_fix": {
    "cost": 1,
    "description": "Generazione singola ottimizzazione AI"
}
```

- [ ] **Step 2: Verify PHP syntax loads**

```bash
php -r "json_decode(file_get_contents('modules/ads-analyzer/module.json')); echo json_last_error() === 0 ? 'OK' : 'ERROR';"
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/module.json
git commit -m "feat(ads-analyzer): add generate_fix cost and max_landing_pages settings"
```

---

### Task 3: Update GeneratedFix Model

**Files:**
- Modify: `modules/ads-analyzer/models/GeneratedFix.php`

- [ ] **Step 1: Add new fields to model**

In the `create()` method, add `target_ad_index` and `asset_group_id_google` to the INSERT statement. In `findByEvaluationId()`, add the new columns to the SELECT. Check current code first with `Read` to see exact insertion points.

Key changes:
- `create()`: add `target_ad_index` (INT NULL), `asset_group_id_google` (VARCHAR NULL) to insert
- `findByEvaluationId()` and `findByIds()`: add new columns to SELECT
- No new methods needed — existing `markAsApplied()` works as-is

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/models/GeneratedFix.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/models/GeneratedFix.php
git commit -m "feat(ads-analyzer): add target_ad_index and asset_group_id to GeneratedFix model"
```

---

## Chunk 2: Backend — Enhanced Evaluation Pipeline

### Task 4: Restructure AI Prompt (CampaignEvaluatorService)

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignEvaluatorService.php`

This is the biggest backend change. The prompt must output the new hierarchical JSON schema from the design spec (Section 5).

- [ ] **Step 1: Update `buildPrompt()` method**

Key changes to the prompt builder:
1. In `buildAdGroupDetailSection()`: add **per-ad CTR and click count** (currently only shows headlines/descriptions but not individual ad metrics clearly separated)
2. In `buildLandingSection()`: map each landing URL to **all ad groups and specific ads** that point to it (currently maps to ad group names only)
3. Truncate landing content to **1500 chars** (currently 3000 — see design Section 6)
4. Add **ENABLED filter**: only include campaigns, ad groups, ads, keywords with status ENABLED
5. Update **JSON schema instructions** at the end of the prompt to match new response structure (design Section 5):
   - `campaigns[].ad_groups[].ads_analysis[]` with `ad_index`, `headlines`, `ctr`, `assessment`, `needs_rewrite`
   - `campaigns[].ad_groups[].landing_analysis` with `url`, `coherence_score`, `analysis`, `suggestions`
   - `campaigns[].ad_groups[].optimizations[]` with `type`, `priority`, `target_ad_index`, `reason`, `scope`
6. Update `max_tokens` to 8192 (keep as-is — sufficient for 5×5 scale)

Reference: read current `buildAdGroupDetailSection()` and `buildLandingSection()` methods first.

- [ ] **Step 2: Update `buildPmaxSection()` method**

Changes:
1. Add ENABLED filter for asset groups
2. Response schema: `campaigns[].asset_group_analysis[].optimizations[]` with `type: replace_asset|add_asset`

- [ ] **Step 3: Add `schema_version` to evaluation save**

In `CampaignController::evaluate()`, when saving to `ga_campaign_evaluations`, set `schema_version = 2`.

- [ ] **Step 4: Verify syntax on all modified files**

```bash
php -l modules/ads-analyzer/services/CampaignEvaluatorService.php
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/services/CampaignEvaluatorService.php modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): restructure AI prompt for hierarchical evaluation with per-ad metrics"
```

---

### Task 5: Enhance Scraping in evaluate()

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` — `evaluate()` method

- [ ] **Step 1: Remove 5 URL limit, use setting**

In `evaluate()` around line 565, change the hard-coded limit of 5 URLs to use the module setting:

```php
$maxLanding = (int) ModuleLoader::getSetting('ads-analyzer', 'max_landing_pages_per_eval', 25);
```

Collect ALL unique `final_url` values from `ga_ads` WHERE `ad_status = 'ENABLED'`, up to `$maxLanding`.

- [ ] **Step 2: Map landing URLs to ad groups AND ads**

Currently landing context maps URL → ad group names. Change to map URL → array of `{ad_group_name, ad_index, headlines}` for richer AI context.

- [ ] **Step 3: Truncate content to 1500 chars**

In the scraping loop, change `substr($content, 0, 3000)` to `substr($content, 0, 1500)`.

- [ ] **Step 4: Verify syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): expand landing scraping to all URLs with ad-level mapping"
```

---

### Task 6: SSE Progress for Evaluation

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` — add `evaluateStream()` method
- Modify: `modules/ads-analyzer/routes.php` — add SSE route

- [ ] **Step 1: Create SSE endpoint**

Add `evaluateStream()` method following the existing SSE pattern from `seo-tracking/RankCheckController.php` (`processStream()`).

Key SSE events:
- `started` → `{total_steps: N}`
- `progress` → `{step: 'scraping', current: 3, total: 18, message: 'Analisi landing page 3/18...'}`
- `progress` → `{step: 'analyzing', message: 'Analisi AI in corso...'}`
- `completed` → `{evaluation_id: N, redirect: '/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}'}`
- `error` → `{message: '...'}`

The SSE method wraps the existing `evaluate()` logic but sends progress events instead of waiting for completion.

Pattern reference (mandatory):
- `ignore_user_abort(true)` + `set_time_limit(300)` + `session_write_close()`
- `header('Content-Type: text/event-stream')` + `header('Cache-Control: no-cache')`
- `Database::reconnect()` after AI call
- `if (ob_get_level()) ob_flush(); flush();` after each event

- [ ] **Step 2: Add route**

In `routes.php`:

```php
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluate-stream',
    fn($id) => (new CampaignController())->evaluateStream($id));
```

- [ ] **Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/routes.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php modules/ads-analyzer/routes.php
git commit -m "feat(ads-analyzer): add SSE evaluate-stream endpoint with progress events"
```

---

### Task 7: Update EvaluationGeneratorService for PMax

**Files:**
- Modify: `modules/ads-analyzer/services/EvaluationGeneratorService.php`

- [ ] **Step 1: Add PMax fix types**

Add support for `replace_asset` and `add_asset` fix types in the generator. These generate new headline/description text for PMax asset groups.

The prompt for `replace_asset` should:
- Receive the current LOW-performing asset text
- Receive the asset group context (search themes, audience signals)
- Generate a replacement asset of the same type

The prompt for `add_asset` should:
- Receive missing asset types
- Generate new assets to fill the gap

Follow the existing pattern of `generateRewriteAds()` for building the AI prompt.

- [ ] **Step 2: Add `target_ad_index` to fix creation**

When creating a `GeneratedFix` record for `rewrite_ads`, pass the `target_ad_index` from the request context.

- [ ] **Step 3: Use `cost_generate_fix` setting**

Replace hardcoded credit cost with: `Credits::getCost('generate_fix', 'ads-analyzer', 1)`

- [ ] **Step 4: Verify syntax**

```bash
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/services/EvaluationGeneratorService.php
git commit -m "feat(ads-analyzer): add PMax replace_asset/add_asset fix types to generator"
```

---

### Task 8: Enhance evaluationShow() Controller for v2

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` — `evaluationShow()` method

- [ ] **Step 1: Merge sync metrics with AI response**

The current `evaluationShow()` loads the evaluation and passes `$aiResponse` to the view. For v2, also load:

1. **Sync metrics**: query `ga_campaigns`, `ga_campaign_ad_groups`, `ga_ads`, `ga_ad_group_keywords` for the sync used in this evaluation (`$evaluation['sync_id']`). Filter by ENABLED status only.
2. **Previous sync metrics**: find previous completed sync, query same tables, calculate delta % per metric per campaign.
3. **Negative KW summary**: query `ga_analyses` for latest completed analysis in this project, count applied/pending negatives.
4. **Campaign filter UI data**: for the pre-evaluation view, load all ENABLED campaigns from latest sync with basic metrics.

Pass all of this to the view:
```php
return View::render('ads-analyzer::campaigns/evaluation-v2', [
    'user' => $user,
    'project' => $project,
    'evaluation' => $evaluation,
    'aiResponse' => $aiResponse,
    'syncMetrics' => $syncMetrics,        // campaigns + ad groups + ads from sync
    'metricDeltas' => $metricDeltas,      // delta % per KPI
    'negativeSummary' => $negativeSummary, // from search terms analysis
    'savedFixes' => $savedFixes,          // existing generated fixes
    'modules' => ModuleLoader::getActiveModules(),
    'access_role' => $accessRole,
]);
```

- [ ] **Step 2: Route v2 view based on schema_version**

```php
$viewName = ($evaluation['schema_version'] ?? 1) >= 2
    ? 'ads-analyzer::campaigns/evaluation-v2'
    : 'ads-analyzer::campaigns/evaluation';
```

Old evaluations (schema_version=1) render with the old view. New ones use v2.

- [ ] **Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): enhance evaluationShow with sync metrics, deltas, and v2 routing"
```

---

## Chunk 3: Frontend — View Partials

### Task 9: Main View Shell (evaluation-v2.php)

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/evaluation-v2.php`

- [ ] **Step 1: Create main view**

The main view includes all partials and wraps them in the Alpine.js `evaluationReport()` component. Handles all view states: analyzing (SSE), error, no-change, results.

Structure:
```php
<?php $currentPage = 'evaluations'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="evaluationReport()">
    <?php if ($isAnalyzing): ?>
        <!-- SSE progress UI -->
    <?php elseif ($isError): ?>
        <!-- Error state -->
    <?php elseif ($hasResults): ?>
        <?php include __DIR__ . '/partials/report-header.php'; ?>
        <?php include __DIR__ . '/partials/report-kpi-bar.php'; ?>
        <?php include __DIR__ . '/partials/report-campaign-table.php'; ?>
        <?php include __DIR__ . '/partials/report-ai-summary.php'; ?>
        <?php include __DIR__ . '/partials/report-optimizations.php'; ?>
        <?php include __DIR__ . '/partials/report-negative-summary.php'; ?>
    <?php endif; ?>
</div>

<script>
function evaluationReport() {
    return {
        // Alpine state: expandedCampaigns, expandedAdGroups, selectedOptimizations, generators...
    };
}
</script>
```

Follow GR #22 (user passed to View::render), GR #20 (table CSS standards), existing evaluation.php Alpine patterns.

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation-v2.php
git commit -m "feat(ads-analyzer): add evaluation-v2 main view shell with partial includes"
```

---

### Task 10: Report Header + KPI Bar Partials

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-header.php`
- Create: `modules/ads-analyzer/views/campaigns/partials/report-kpi-bar.php`

- [ ] **Step 1: Create header partial**

Shows: evaluation date, period (from sync date range), PDF export button, "Indietro" link. Reuse the existing header pattern from evaluation.php lines 358-464.

- [ ] **Step 2: Create KPI bar partial**

6 cards: Spesa, Click, CTR, Conversioni, ROAS, CPA. Each with:
- Current value (from `$syncMetrics` aggregated)
- Delta % vs previous sync (from `$metricDeltas`)
- Color: green if good (positive for Click/CTR/Conv/ROAS, negative for Spesa/CPA), red if bad
- Use existing `formatKpiValue()` pattern from evaluation.php line 308

Follow CSS standards: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`.

- [ ] **Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-header.php
php -l modules/ads-analyzer/views/campaigns/partials/report-kpi-bar.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/partials/report-header.php modules/ads-analyzer/views/campaigns/partials/report-kpi-bar.php
git commit -m "feat(ads-analyzer): add report header and KPI bar partials"
```

---

### Task 11: Campaign Table (3-level expandable)

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php`

This is the most complex partial. Uses Alpine.js for expand/collapse.

- [ ] **Step 1: Create level 1 — Campaign rows**

Table with columns: Campagna (with type badge), Click, CTR, Spesa, Conv., Val. Conv., ROAS, CPA, AI (score dot).
- Type badges: Search (blue), PMax (purple), Display (orange), Shopping (emerald) — reuse `$campaignTypeConfig` from evaluation.php
- ROAS color-coded (green ≥4, amber ≥2, red <2)
- CPA color-coded (red if above account average)
- AI dot: green (≥7), amber (≥5), red (<5) based on `campaigns[].score` from AI response
- Click on row toggles `expandedCampaigns[index]`

Data source: `$syncMetrics['campaigns']` for metrics, `$aiResponse['campaigns']` for AI score.

- [ ] **Step 2: Create level 2 — Ad Group rows (inside expanded campaign)**

When campaign is expanded, show:
- AI comment for this campaign (from `campaigns[].metrics_comment`)
- Sub-table: Ad Group, Click, CTR, Spesa, Conv., ROAS, CPA, Landing URL
- Click on ad group toggles `expandedAdGroups[campIdx + '_' + agIdx]`

Data source: `$syncMetrics['campaigns'][N]['ad_groups']` + `$aiResponse['campaigns'][N]['ad_groups']`.

For **PMax campaigns**: show asset groups instead of ad groups. Table columns: Asset Group, Ad Strength, Click, Spesa, Conv., ROAS, CPA. No landing URL column (asset group level).

- [ ] **Step 3: Create level 3 — Ads + Keywords (inside expanded ad group)**

When ad group is expanded, show:
- **Ads table**: #, H1, H2, H3, D1 (truncated), CTR, Click. Color-code: lowest CTR in red.
- **AI per-ad assessment**: from `ad_groups[].ads_analysis[].assessment`
- **Landing analysis**: from `ad_groups[].landing_analysis` — URL, coherence score, analysis text
- **Keywords** (top 10 by click): keyword text, match type badge, Click, CTR

For **PMax asset groups**: show asset inventory by type with performance labels (BEST/GOOD/LOW), LOW assets highlighted in red.

- [ ] **Step 4: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
git commit -m "feat(ads-analyzer): add 3-level expandable campaign table partial"
```

---

### Task 12: AI Summary Partial

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-ai-summary.php`

- [ ] **Step 1: Create AI summary card**

Shows:
- Score circle (reuse `scoreColorClass()` pattern from evaluation.php)
- Trend badge (if auto-eval: improving/stable/declining/mixed)
- Summary text from `$aiResponse['summary']`
- Top 3-5 recommendations from `$aiResponse['top_recommendations']` — each with priority badge and text
- Issue count badges (high/medium/low)

Reuse patterns from evaluation.php lines 358-605 (header + panoramica tab).

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-ai-summary.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/partials/report-ai-summary.php
git commit -m "feat(ads-analyzer): add AI summary partial with score and recommendations"
```

---

### Task 13: Optimizations Batch Partial

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-optimizations.php`

This is the key differentiator — the "consulente AI che agisce" feature.

- [ ] **Step 1: Create optimization list**

Each optimization from `$aiResponse.campaigns[].ad_groups[].optimizations[]` rendered as a card with:
- **Checkbox** for batch selection (Alpine: `selectedOptimizations`)
- **Priority badge** (alta/media/bassa)
- **Type badge** (Annunci, Estensioni, Asset PMax, Landing Page)
- **Scope**: campaign name → ad group name (→ ad #N if ad-specific)
- **Reason text** (from AI: "RSA #2 CTR 1,8% vs 3,4% di RSA #1")
- **State machine**: Suggested → Generated → Applied

- [ ] **Step 2: Implement "Genera" flow with Before→After preview**

When user clicks "Genera Ottimizzazione":
1. POST to existing `/evaluations/{evalId}/generate` endpoint
2. Show loading spinner on that card
3. On success, render **Before→After preview**:
   - For `rewrite_ads`: show old H1/H2/H3/D1/D2 (strikethrough red) → new (green)
   - For `replace_asset`: show old asset (red) → new (green)
   - For `add_extensions`: show new extension text (green)
4. Show "Rigenera", "Esporta CSV Ads Editor", "Applica su Google Ads" buttons

The preview reads from `generators[key].data` (already saved by existing `generateFix()` JS function — reuse from evaluation.php).

- [ ] **Step 3: Implement batch toolbar**

Sticky bar at top of optimizations section:
```
⚡ N Ottimizzazioni | M generate | K selezionate
[Genera Tutte (costo: X crediti)] [Applica Selezionate]
```

- "Genera Tutte": calls generateFix() for each un-generated optimization sequentially. Shows credit cost confirmation first.
- "Applica Selezionate": single confirmation modal → calls existing `applyToGoogleAds()` endpoint for each selected fix

Reuse the existing apply modal from evaluation.php lines 1262-1374, but **simplify**: single confirmation (not double with "CONFERMA" text).

- [ ] **Step 4: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-optimizations.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/partials/report-optimizations.php
git commit -m "feat(ads-analyzer): add batch optimizations partial with Before/After preview"
```

---

### Task 14: Negative KW Summary + Campaign Filter Partials

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/partials/report-negative-summary.php`
- Create: `modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php`

- [ ] **Step 1: Create negative KW summary**

Card showing:
- Total waste estimate (from `$negativeSummary['total_waste']`)
- Count by priority (high/medium/evaluate)
- Count already applied
- CTA: "Vai all'Analisi Search Terms →" linking to `/ads-analyzer/projects/{id}/search-term-analysis`
- If no analysis exists: "Lancia Analisi Search Terms" CTA

Data source: `$negativeSummary` assembled by controller (NOT from AI response).

- [ ] **Step 2: Create campaign filter partial**

Pre-evaluation view showing checkboxes for each ENABLED campaign:
```
☑ Brand IT (Search) — 2.340 click, €3.200, ROAS 5,2x
☑ Generic IT (Search) — 1.890 click, €5.100, ROAS 3,1x
☐ Shopping IT (PMax) — 890 click, €4.150, ROAS 4,7x
```

All checked by default. Submit sends `campaigns_filter[]` to the evaluate-stream endpoint.
Show credit cost and "Avvia Analisi AI" button.

This partial is shown BEFORE evaluation, not after — it's the entry point. Route: the campaigns index page or a modal triggered by "Valuta con AI" button.

- [ ] **Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-negative-summary.php
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/partials/report-negative-summary.php modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php
git commit -m "feat(ads-analyzer): add negative KW summary and campaign filter partials"
```

---

## Chunk 4: Integration + Polish

### Task 15: Wire Alpine.js Component

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation-v2.php` — `evaluationReport()` function

- [ ] **Step 1: Implement Alpine.js state and methods**

```javascript
function evaluationReport() {
    return {
        // State
        expandedCampaigns: {},
        expandedAdGroups: {},
        selectedOptimizations: {},
        generators: {},
        applyModal: { open: false, ... },

        // Pre-loaded data from PHP
        campaignsData: <?= json_encode($viewCampaigns) ?>,
        savedFixes: <?= json_encode($savedFixesMap) ?>,

        // URLs
        generateUrl: '<?= e($generateUrl) ?>',
        applyUrl: '<?= e($applyUrl) ?>',
        csrfToken: '<?= csrf_token() ?>',

        // Methods
        init() { /* restore saved fixes into generators */ },
        toggleCampaign(idx) { ... },
        toggleAdGroup(key) { ... },
        toggleOptimization(key) { ... },
        selectAllOptimizations() { ... },
        generateFix(type, context, key) { /* existing pattern from evaluation.php */ },
        generateAll() { /* sequential generation with credit confirmation */ },
        applySelected() { /* batch apply with single confirmation */ },
        copyResult(key) { ... },
        exportCsv(key) { /* existing CSV export */ },
    };
}
```

Reuse as much as possible from evaluation.php's `evaluationDashboard()` function (lines 1380-1600+), especially `generateFix()`, `copyResult()`, `openApplyModal()`, `executeApply()`.

- [ ] **Step 2: Verify no JS syntax errors**

Open evaluation in browser, check console for errors.

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation-v2.php
git commit -m "feat(ads-analyzer): wire Alpine.js evaluationReport component with all interactions"
```

---

### Task 16: SSE Frontend (Analyzing State)

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation-v2.php`

- [ ] **Step 1: Implement SSE client in analyzing state**

When `$isAnalyzing` or when user triggers evaluation from campaign filter:

```javascript
function evaluationSSE() {
    return {
        progress: 0,
        totalSteps: 0,
        message: 'Avvio analisi...',
        step: '',
        error: null,

        start(url) {
            const source = new EventSource(url);
            source.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.step = data.step;
                this.message = data.message;
                this.progress = data.current || 0;
                this.totalSteps = data.total || 0;
            });
            source.addEventListener('completed', (e) => {
                const data = JSON.parse(e.data);
                window.location.href = data.redirect;
            });
            source.addEventListener('error', (e) => {
                this.error = e.data ? JSON.parse(e.data).message : 'Errore di connessione';
                source.close();
            });
        }
    };
}
```

UI: centered card with progress message, animated dots, step indicator.

- [ ] **Step 2: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation-v2.php
git commit -m "feat(ads-analyzer): add SSE client for evaluation progress display"
```

---

### Task 17: Final Integration Testing

- [ ] **Step 1: Run migration on local DB**

```bash
mysql -u root seo_toolkit < modules/ads-analyzer/database/migration-evaluation-redesign.sql
```

- [ ] **Step 2: PHP syntax check all modified files**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/services/CampaignEvaluatorService.php
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
php -l modules/ads-analyzer/models/GeneratedFix.php
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
php -l modules/ads-analyzer/views/campaigns/partials/report-header.php
php -l modules/ads-analyzer/views/campaigns/partials/report-kpi-bar.php
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
php -l modules/ads-analyzer/views/campaigns/partials/report-ai-summary.php
php -l modules/ads-analyzer/views/campaigns/partials/report-optimizations.php
php -l modules/ads-analyzer/views/campaigns/partials/report-negative-summary.php
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php
```

- [ ] **Step 3: Manual browser test**

1. Login to `http://localhost/seo-toolkit` (admin@seo-toolkit.local / admin123)
2. Go to ads-analyzer project with synced data
3. Click "Valuta con AI" — verify campaign filter shows with checkboxes
4. Select campaigns → verify SSE progress shows step by step
5. After completion → verify evaluation-v2 renders:
   - KPI bar with 6 cards and delta %
   - Campaign table expandable to ad group to ads
   - AI summary with score and recommendations
   - Optimizations list with "Genera" buttons
   - Negative KW summary with link
6. Click "Genera" on an optimization → verify Before→After preview
7. Click "Applica" → verify single confirmation modal
8. Verify old evaluations (schema_version=1) still render with old view

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "fix(ads-analyzer): integration fixes for evaluation v2"
```

---

### Task 18: Update Auto-Evaluate Cron

**Files:**
- Modify: `modules/ads-analyzer/cron/auto-evaluate.php`

- [ ] **Step 1: Set schema_version=2 for auto evaluations**

When the cron creates a new evaluation record, set `schema_version = 2` so auto-evaluations also render with the new view.

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/cron/auto-evaluate.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/cron/auto-evaluate.php
git commit -m "feat(ads-analyzer): auto-evaluate cron writes schema_version=2"
```

---

### Task 19: Update Documentation

**Files:**
- Modify: `shared/views/docs/ads-analyzer.php` (user docs)
- Modify: `docs/data-model.html` (data model)

- [ ] **Step 1: Update user docs**

Add section about the new evaluation report: KPI bar, expandable table, AI analysis, batch optimizations, Before→After, apply flow.

- [ ] **Step 2: Update data model**

Add `schema_version` to `ga_campaign_evaluations`. Add `target_ad_index`, `asset_group_id_google` to `ga_generated_fixes`. Document new `fix_type` values.

- [ ] **Step 3: Commit**

```bash
git add shared/views/docs/ads-analyzer.php docs/data-model.html
git commit -m "docs(ads-analyzer): update user docs and data model for evaluation redesign"
```
