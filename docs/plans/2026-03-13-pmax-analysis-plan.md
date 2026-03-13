# Performance Max Analysis Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract PMax-specific data (asset groups, asset performance, audience signals) via Google Ads API and provide professional-grade AI evaluation of Performance Max campaigns.

**Architecture:** New sync methods in CampaignSyncService query asset_group, asset_group_asset, asset_group_signal GAQL resources. Two new DB tables store data. CampaignEvaluatorService builds a PMax-specific section in the evaluation prompt with asset quality, diversity, audience signal analysis. View adapts to show asset_group_analysis instead of ad_group_analysis for PMax campaigns.

**Tech Stack:** PHP 8+, MySQL, Google Ads API REST v18+, Alpine.js, Tailwind CSS

**Spec:** `docs/plans/2026-03-13-pmax-analysis-design.md`

---

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `database/migrations/2026_03_13_pmax_asset_groups.sql` | DB schema for ga_asset_groups + ga_asset_group_assets |
| Create | `modules/ads-analyzer/models/AssetGroup.php` | CRUD for ga_asset_groups |
| Create | `modules/ads-analyzer/models/AssetGroupAsset.php` | CRUD for ga_asset_group_assets |
| Modify | `modules/ads-analyzer/services/CampaignSyncService.php` | Add syncAssetGroups(), syncAssetGroupAssets(), syncAudienceSignals() |
| Modify | `modules/ads-analyzer/services/CampaignEvaluatorService.php` | Add PMax section to prompt + PMax benchmarks |
| Modify | `modules/ads-analyzer/services/EvaluationGeneratorService.php` | Adapt buildCopyPrompt for PMax asset groups |
| Modify | `modules/ads-analyzer/controllers/CampaignController.php` | Load asset group data for PMax evaluations |
| Modify | `modules/ads-analyzer/views/campaigns/evaluation.php` | Render asset_group_analysis for PMax campaigns |

---

## Chunk 1: Database & Models

### Task 1: Database Migration

**Files:**
- Create: `database/migrations/2026_03_13_pmax_asset_groups.sql`

- [ ] **Step 1: Create migration file**

```sql
-- PMax Asset Groups & Assets tables
CREATE TABLE IF NOT EXISTS ga_asset_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_id INT NOT NULL,
    project_id INT NOT NULL,
    campaign_id_google VARCHAR(50) NOT NULL,
    campaign_name VARCHAR(255) DEFAULT NULL,
    asset_group_id_google VARCHAR(50) NOT NULL,
    asset_group_name VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    ad_strength ENUM('POOR','AVERAGE','GOOD','EXCELLENT','UNSPECIFIED') DEFAULT 'UNSPECIFIED',
    primary_status VARCHAR(50) DEFAULT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    cost DECIMAL(12,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    conversions_value DECIMAL(12,2) DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    avg_cpc DECIMAL(8,2) DEFAULT 0,
    audience_signals JSON DEFAULT NULL,
    search_themes JSON DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync (sync_id),
    INDEX idx_project (project_id),
    INDEX idx_campaign (campaign_id_google),
    INDEX idx_asset_group (asset_group_id_google)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ga_asset_group_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_id INT NOT NULL,
    project_id INT NOT NULL,
    asset_group_id_google VARCHAR(50) NOT NULL,
    asset_id_google VARCHAR(50) DEFAULT NULL,
    field_type VARCHAR(50) NOT NULL,
    performance_label ENUM('BEST','GOOD','LOW','LEARNING','UNSPECIFIED') DEFAULT 'UNSPECIFIED',
    primary_status VARCHAR(50) DEFAULT NULL,
    text_content TEXT DEFAULT NULL,
    url_content VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync (sync_id),
    INDEX idx_asset_group (asset_group_id_google),
    INDEX idx_field_type (field_type),
    INDEX idx_performance (performance_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run migration locally**

```bash
mysql -u root seo_toolkit < database/migrations/2026_03_13_pmax_asset_groups.sql
```

- [ ] **Step 3: Verify tables exist**

```bash
mysql -u root seo_toolkit -e "DESCRIBE ga_asset_groups; DESCRIBE ga_asset_group_assets;"
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_13_pmax_asset_groups.sql
git commit -m "feat(ads-analyzer): add PMax asset_groups and asset_group_assets tables"
```

---

### Task 2: AssetGroup Model

**Files:**
- Create: `modules/ads-analyzer/models/AssetGroup.php`

Pattern: follows `CampaignAdGroup.php` exactly (static methods, `Database::` calls).

- [ ] **Step 1: Create model**

```php
<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AssetGroup
{
    public static function create(array $data): int
    {
        return Database::insert('ga_asset_groups', [
            'sync_id' => $data['sync_id'],
            'project_id' => $data['project_id'],
            'campaign_id_google' => $data['campaign_id_google'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'asset_group_id_google' => $data['asset_group_id_google'],
            'asset_group_name' => $data['asset_group_name'] ?? null,
            'status' => $data['status'] ?? null,
            'ad_strength' => $data['ad_strength'] ?? 'UNSPECIFIED',
            'primary_status' => $data['primary_status'] ?? null,
            'impressions' => $data['impressions'] ?? 0,
            'clicks' => $data['clicks'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'conversions' => $data['conversions'] ?? 0,
            'conversions_value' => $data['conversions_value'] ?? 0,
            'ctr' => $data['ctr'] ?? 0,
            'avg_cpc' => $data['avg_cpc'] ?? 0,
            'audience_signals' => isset($data['audience_signals']) ? json_encode($data['audience_signals'], JSON_UNESCAPED_UNICODE) : null,
            'search_themes' => isset($data['search_themes']) ? json_encode($data['search_themes'], JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function getBySyncId(int $syncId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_groups WHERE sync_id = ? ORDER BY campaign_name, cost DESC",
            [$syncId]
        );
    }

    public static function getBySyncAndCampaign(int $syncId, string $campaignIdGoogle): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_groups WHERE sync_id = ? AND campaign_id_google = ? ORDER BY cost DESC",
            [$syncId, $campaignIdGoogle]
        );
    }

    public static function getBySyncGrouped(int $syncId): array
    {
        $rows = self::getBySyncId($syncId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['campaign_id_google']][] = $row;
        }
        return $grouped;
    }

    public static function deleteBySyncId(int $syncId): bool
    {
        return Database::delete('ga_asset_groups', 'sync_id = ?', [$syncId]) >= 0;
    }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/models/AssetGroup.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/models/AssetGroup.php
git commit -m "feat(ads-analyzer): add AssetGroup model for PMax data"
```

---

### Task 3: AssetGroupAsset Model

**Files:**
- Create: `modules/ads-analyzer/models/AssetGroupAsset.php`

- [ ] **Step 1: Create model**

```php
<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AssetGroupAsset
{
    public static function create(array $data): int
    {
        return Database::insert('ga_asset_group_assets', [
            'sync_id' => $data['sync_id'],
            'project_id' => $data['project_id'],
            'asset_group_id_google' => $data['asset_group_id_google'],
            'asset_id_google' => $data['asset_id_google'] ?? null,
            'field_type' => $data['field_type'],
            'performance_label' => $data['performance_label'] ?? 'UNSPECIFIED',
            'primary_status' => $data['primary_status'] ?? null,
            'text_content' => $data['text_content'] ?? null,
            'url_content' => $data['url_content'] ?? null,
        ]);
    }

    public static function getBySyncId(int $syncId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_group_assets WHERE sync_id = ? ORDER BY asset_group_id_google, field_type",
            [$syncId]
        );
    }

    public static function getByAssetGroup(int $syncId, string $assetGroupIdGoogle): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_asset_group_assets WHERE sync_id = ? AND asset_group_id_google = ? ORDER BY field_type, performance_label",
            [$syncId, $assetGroupIdGoogle]
        );
    }

    /**
     * Raggruppa asset per asset_group_id_google
     */
    public static function getBySyncGrouped(int $syncId): array
    {
        $rows = self::getBySyncId($syncId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['asset_group_id_google']][] = $row;
        }
        return $grouped;
    }

    /**
     * Conta asset per tipo e performance label per un asset group
     * Ritorna: ['HEADLINE' => ['BEST' => 2, 'GOOD' => 3, 'LOW' => 1], ...]
     */
    public static function getPerformanceSummary(int $syncId, string $assetGroupIdGoogle): array
    {
        $rows = Database::fetchAll(
            "SELECT field_type, performance_label, COUNT(*) as cnt
             FROM ga_asset_group_assets
             WHERE sync_id = ? AND asset_group_id_google = ?
             GROUP BY field_type, performance_label
             ORDER BY field_type, performance_label",
            [$syncId, $assetGroupIdGoogle]
        );

        $summary = [];
        foreach ($rows as $row) {
            $summary[$row['field_type']][$row['performance_label']] = (int) $row['cnt'];
        }
        return $summary;
    }

    public static function deleteBySyncId(int $syncId): bool
    {
        return Database::delete('ga_asset_group_assets', 'sync_id = ?', [$syncId]) >= 0;
    }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/models/AssetGroupAsset.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/models/AssetGroupAsset.php
git commit -m "feat(ads-analyzer): add AssetGroupAsset model for PMax asset data"
```

---

## Chunk 2: Sync Service — PMax Data Extraction

### Task 4: Add PMax sync methods to CampaignSyncService

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignSyncService.php`

**Context:** The service has a `syncAll()` method that calls sub-syncs in sequence. We add 3 new methods called after `syncExtensions()`, only for PMax campaigns. The service uses `$this->gadsService->searchStream($gaql)` for API calls and `$this->extractRows($response)` to flatten results. Metrics from API come as `costMicros` (divide by 1M), `averageCpc` (micros), `ctr` (decimal 0-1).

**Important:** The Google Ads API returns `asset_group.id` as the asset group ID and `assetGroupAsset.asset` as the asset resource name (format: `customers/{customerId}/assets/{assetId}`). We extract the numeric ID from the resource name.

- [ ] **Step 1: Add require_once for new models at top of file (after line 13)**

```php
use Modules\AdsAnalyzer\Models\AssetGroup;
use Modules\AdsAnalyzer\Models\AssetGroupAsset;
```

- [ ] **Step 2: Add PMax sync calls in syncAll() after extensions sync (after line 96)**

Insert after the `// 6. Sync estensioni` block and before `// 7. Sync termini di ricerca`:

```php
            // 6b. Sync PMax asset groups + assets + audience signals
            $pmaxCounts = $this->syncPmaxData($dateFrom, $dateTo);
            $counts['asset_groups_synced'] = $pmaxCounts['asset_groups'];
            $counts['assets_synced'] = $pmaxCounts['assets'];
            Sync::updateCounts($this->syncId, $counts);
            Database::reconnect();
```

Also add `'asset_groups_synced' => 0, 'assets_synced' => 0,` to the `$counts` array init (after line 69).

- [ ] **Step 3: Add syncPmaxData() orchestrator method**

Add at end of class, before closing `}`:

```php
    /**
     * Sync PMax-specific data: asset groups, assets, audience signals.
     * Only runs for PERFORMANCE_MAX campaigns in the current sync.
     *
     * @return array ['asset_groups' => int, 'assets' => int]
     */
    private function syncPmaxData(string $dateFrom, string $dateTo): array
    {
        // Get PMax campaign IDs from this sync
        $pmaxCampaigns = Database::fetchAll(
            "SELECT campaign_id_google, campaign_name FROM ga_campaigns
             WHERE sync_id = ? AND campaign_type = 'PERFORMANCE_MAX'",
            [$this->syncId]
        );

        if (empty($pmaxCampaigns)) {
            return ['asset_groups' => 0, 'assets' => 0];
        }

        $totalAg = 0;
        $totalAssets = 0;

        foreach ($pmaxCampaigns as $campaign) {
            $campaignId = $campaign['campaign_id_google'];
            $campaignName = $campaign['campaign_name'];

            // 1. Sync asset groups
            $agCount = $this->syncAssetGroups($campaignId, $campaignName, $dateFrom, $dateTo);
            $totalAg += $agCount;

            // 2. Sync asset group assets + fetch asset content
            $assetCount = $this->syncAssetGroupAssets($campaignId);
            $totalAssets += $assetCount;

            // 3. Sync audience signals and search themes (updates ga_asset_groups JSON columns)
            $this->syncAudienceSignals($campaignId);

            Database::reconnect();
        }

        return ['asset_groups' => $totalAg, 'assets' => $totalAssets];
    }
```

- [ ] **Step 4: Add syncAssetGroups() method**

```php
    /**
     * Sync asset groups for a PMax campaign
     */
    private function syncAssetGroups(string $campaignIdGoogle, string $campaignName, string $dateFrom, string $dateTo): int
    {
        $gaql = "SELECT asset_group.id, asset_group.name, asset_group.status, " .
                "asset_group.ad_strength, asset_group.primary_status, " .
                "metrics.impressions, metrics.clicks, metrics.cost_micros, " .
                "metrics.conversions, metrics.conversions_value " .
                "FROM asset_group " .
                "WHERE campaign.id = {$campaignIdGoogle} " .
                "AND segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}'";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        // Aggregate by asset_group.id (rows are per-day with segments.date)
        $aggregated = [];
        foreach ($rows as $row) {
            $ag = $row['assetGroup'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $agId = (string) ($ag['id'] ?? '');
            if (empty($agId)) continue;

            if (!isset($aggregated[$agId])) {
                $aggregated[$agId] = [
                    'id' => $agId,
                    'name' => $ag['name'] ?? '',
                    'status' => $ag['status'] ?? null,
                    'ad_strength' => $ag['adStrength'] ?? 'UNSPECIFIED',
                    'primary_status' => $ag['primaryStatus'] ?? null,
                    'clicks' => 0, 'impressions' => 0, 'cost_micros' => 0,
                    'conversions' => 0.0, 'conversions_value' => 0.0,
                ];
            }

            $agg = &$aggregated[$agId];
            $agg['clicks'] += (int) ($metrics['clicks'] ?? 0);
            $agg['impressions'] += (int) ($metrics['impressions'] ?? 0);
            $agg['cost_micros'] += (int) ($metrics['costMicros'] ?? 0);
            $agg['conversions'] += (float) ($metrics['conversions'] ?? 0);
            $agg['conversions_value'] += (float) ($metrics['conversionsValue'] ?? 0);
            unset($agg);
        }

        $count = 0;
        foreach ($aggregated as $agg) {
            $clicks = $agg['clicks'];
            $impressions = $agg['impressions'];
            $cost = $agg['cost_micros'] / 1000000;

            AssetGroup::create([
                'sync_id' => $this->syncId,
                'project_id' => $this->projectId,
                'campaign_id_google' => $campaignIdGoogle,
                'campaign_name' => $campaignName,
                'asset_group_id_google' => $agg['id'],
                'asset_group_name' => $agg['name'],
                'status' => $agg['status'],
                'ad_strength' => $agg['ad_strength'],
                'primary_status' => $agg['primary_status'],
                'impressions' => $impressions,
                'clicks' => $clicks,
                'cost' => round($cost, 2),
                'conversions' => round($agg['conversions'], 2),
                'conversions_value' => round($agg['conversions_value'], 2),
                'ctr' => $this->calcCtr($clicks, $impressions),
                'avg_cpc' => $clicks > 0 ? round($cost / $clicks, 2) : 0,
            ]);
            $count++;
        }

        return $count;
    }
```

- [ ] **Step 5: Add syncAssetGroupAssets() method**

```php
    /**
     * Sync individual assets for each asset group of a PMax campaign.
     * Two-step: (1) get asset group -> asset links with performance labels,
     *           (2) batch-fetch asset content (text/url).
     */
    private function syncAssetGroupAssets(string $campaignIdGoogle): int
    {
        // Step 1: Get asset links + performance labels
        $gaql = "SELECT asset_group.id, asset_group_asset.asset, " .
                "asset_group_asset.field_type, asset_group_asset.performance_label, " .
                "asset_group_asset.primary_status " .
                "FROM asset_group_asset " .
                "WHERE campaign.id = {$campaignIdGoogle}";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        if (empty($rows)) return 0;

        // Collect asset resource names for bulk content fetch
        $assetLinks = [];
        $resourceNames = [];
        foreach ($rows as $row) {
            $ag = $row['assetGroup'] ?? [];
            $aga = $row['assetGroupAsset'] ?? [];
            $assetResourceName = $aga['asset'] ?? '';
            if (empty($assetResourceName)) continue;

            $assetLinks[] = [
                'asset_group_id' => (string) ($ag['id'] ?? ''),
                'asset_resource_name' => $assetResourceName,
                'field_type' => $aga['fieldType'] ?? 'UNSPECIFIED',
                'performance_label' => $aga['performanceLabel'] ?? 'UNSPECIFIED',
                'primary_status' => $aga['primaryStatus'] ?? null,
            ];
            $resourceNames[$assetResourceName] = true;
        }

        // Step 2: Bulk fetch asset content
        $assetContent = $this->fetchAssetContent(array_keys($resourceNames));

        // Step 3: Save to DB
        $count = 0;
        foreach ($assetLinks as $link) {
            $content = $assetContent[$link['asset_resource_name']] ?? [];
            // Extract numeric asset ID from resource name: customers/123/assets/456 -> 456
            $assetId = '';
            if (preg_match('/assets\/(\d+)$/', $link['asset_resource_name'], $m)) {
                $assetId = $m[1];
            }

            AssetGroupAsset::create([
                'sync_id' => $this->syncId,
                'project_id' => $this->projectId,
                'asset_group_id_google' => $link['asset_group_id'],
                'asset_id_google' => $assetId,
                'field_type' => $link['field_type'],
                'performance_label' => $link['performance_label'],
                'primary_status' => $link['primary_status'],
                'text_content' => $content['text'] ?? null,
                'url_content' => $content['url'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Fetch content (text/image URL/video ID) for a batch of asset resource names.
     * Returns: ['customers/X/assets/Y' => ['text' => '...', 'url' => '...'], ...]
     */
    private function fetchAssetContent(array $resourceNames): array
    {
        if (empty($resourceNames)) return [];

        // Build IN clause with quoted resource names
        $quoted = array_map(fn($rn) => "'" . addslashes($rn) . "'", $resourceNames);
        $inClause = implode(',', $quoted);

        $gaql = "SELECT asset.resource_name, asset.type, " .
                "asset.text_asset.text, " .
                "asset.image_asset.full_size.url, " .
                "asset.youtube_video_asset.youtube_video_id " .
                "FROM asset " .
                "WHERE asset.resource_name IN ({$inClause})";

        $response = $this->gadsService->searchStream($gaql);
        $rows = $this->extractRows($response);

        $content = [];
        foreach ($rows as $row) {
            $asset = $row['asset'] ?? [];
            $rn = $asset['resourceName'] ?? '';
            if (empty($rn)) continue;

            $text = $asset['textAsset']['text'] ?? null;
            $imageUrl = $asset['imageAsset']['fullSize']['url'] ?? null;
            $videoId = $asset['youtubeVideoAsset']['youtubeVideoId'] ?? null;

            $content[$rn] = [
                'text' => $text,
                'url' => $imageUrl ?: ($videoId ? "https://youtube.com/watch?v={$videoId}" : null),
            ];
        }

        return $content;
    }
```

- [ ] **Step 6: Add syncAudienceSignals() method**

```php
    /**
     * Sync audience signals and search themes for PMax asset groups.
     * Updates the JSON columns in ga_asset_groups.
     */
    private function syncAudienceSignals(string $campaignIdGoogle): void
    {
        $gaql = "SELECT asset_group.id, " .
                "asset_group_signal.audience, " .
                "asset_group_signal.search_theme " .
                "FROM asset_group_signal " .
                "WHERE campaign.id = {$campaignIdGoogle}";

        try {
            $response = $this->gadsService->searchStream($gaql);
            $rows = $this->extractRows($response);
        } catch (\Exception $e) {
            // asset_group_signal may not be available for all accounts/API versions
            Logger::channel('ads')->warning('PMax audience signals fetch failed', [
                'campaign_id' => $campaignIdGoogle,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if (empty($rows)) return;

        // Group signals by asset_group.id
        $signalsByAg = [];
        foreach ($rows as $row) {
            $agId = (string) ($row['assetGroup']['id'] ?? '');
            if (empty($agId)) continue;

            $audience = $row['assetGroupSignal']['audience'] ?? null;
            $searchTheme = $row['assetGroupSignal']['searchTheme'] ?? null;

            if (!isset($signalsByAg[$agId])) {
                $signalsByAg[$agId] = ['audiences' => [], 'search_themes' => []];
            }

            if ($audience) {
                $signalsByAg[$agId]['audiences'][] = $this->parseAudienceSignal($audience);
            }
            if ($searchTheme) {
                $themeText = $searchTheme['text'] ?? '';
                if ($themeText) {
                    $signalsByAg[$agId]['search_themes'][] = $themeText;
                }
            }
        }

        // Update ga_asset_groups with audience signals JSON
        foreach ($signalsByAg as $agId => $signals) {
            $audienceJson = !empty($signals['audiences']) ? json_encode($signals['audiences'], JSON_UNESCAPED_UNICODE) : null;
            $themesJson = !empty($signals['search_themes']) ? json_encode($signals['search_themes'], JSON_UNESCAPED_UNICODE) : null;

            Database::query(
                "UPDATE ga_asset_groups SET audience_signals = ?, search_themes = ?
                 WHERE sync_id = ? AND asset_group_id_google = ?",
                [$audienceJson, $themesJson, $this->syncId, $agId]
            );
        }
    }

    /**
     * Parse audience signal proto into simplified structure
     */
    private function parseAudienceSignal(array $audience): array
    {
        $result = ['type' => 'unknown', 'values' => []];

        // audience.userInterest
        if (!empty($audience['userInterest'])) {
            $result['type'] = 'interest';
            $result['values'][] = $audience['userInterest']['userInterestCategory'] ?? 'unknown';
        }
        // audience.userList
        if (!empty($audience['userList'])) {
            $result['type'] = 'user_list';
            $result['values'][] = $audience['userList']['userList'] ?? 'unknown';
        }
        // audience.customAudience
        if (!empty($audience['customAudience'])) {
            $result['type'] = 'custom_audience';
            $result['values'][] = $audience['customAudience']['customAudience'] ?? 'unknown';
        }
        // audience.detailedDemographic
        if (!empty($audience['detailedDemographic'])) {
            $result['type'] = 'demographic';
            $result['values'][] = $audience['detailedDemographic']['detailedDemographic'] ?? 'unknown';
        }

        return $result;
    }
```

- [ ] **Step 7: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/services/CampaignSyncService.php
```

- [ ] **Step 8: Commit**

```bash
git add modules/ads-analyzer/services/CampaignSyncService.php
git commit -m "feat(ads-analyzer): add PMax sync — asset groups, assets, audience signals"
```

---

## Chunk 3: AI Evaluation — PMax Prompt

### Task 5: Add PMax section to CampaignEvaluatorService

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignEvaluatorService.php`

**Context:** `buildPrompt()` assembles prompt sections. The current method calls `buildAdGroupDetailSection()` which creates ad group → keyword → ad text. For PMax campaigns, we replace this with asset group → asset performance data. The approach: detect PMax campaigns in the data and build a dedicated section for them. The response JSON structure adds `asset_group_analysis` alongside the existing `ad_group_analysis` (PMax campaigns won't have ad_group_analysis, Search campaigns won't have asset_group_analysis).

- [ ] **Step 1: Add model requires at top of file**

After existing require/use statements:

```php
require_once __DIR__ . '/../models/AssetGroup.php';
require_once __DIR__ . '/../models/AssetGroupAsset.php';
use Modules\AdsAnalyzer\Models\AssetGroup;
use Modules\AdsAnalyzer\Models\AssetGroupAsset;
```

- [ ] **Step 2: Add method to load PMax data for evaluation**

Add this method to the class:

```php
    /**
     * Load PMax-specific data (asset groups + assets) for given sync.
     * Returns: ['asset_groups' => [...], 'assets_by_ag' => [...]]
     */
    private function loadPmaxData(int $syncId): array
    {
        $assetGroups = AssetGroup::getBySyncId($syncId);
        $assetsGrouped = AssetGroupAsset::getBySyncGrouped($syncId);

        return [
            'asset_groups' => $assetGroups,
            'assets_by_ag' => $assetsGrouped,
        ];
    }
```

- [ ] **Step 3: Add buildPmaxSection() method**

This builds the PMax-specific text block for the AI prompt, replacing the ad group detail section for PMax campaigns.

```php
    /**
     * Build PMax-specific prompt section with asset group data.
     * Called for PERFORMANCE_MAX campaigns instead of buildAdGroupDetailSection().
     */
    private function buildPmaxSection(array $pmaxCampaigns, array $pmaxData): string
    {
        $assetGroups = $pmaxData['asset_groups'] ?? [];
        $assetsByAg = $pmaxData['assets_by_ag'] ?? [];

        if (empty($assetGroups)) return '';

        // Group asset groups by campaign
        $agByCampaign = [];
        foreach ($assetGroups as $ag) {
            $agByCampaign[$ag['campaign_id_google']][] = $ag;
        }

        // Asset requirements reference
        $section = "\n\n--- DATI PERFORMANCE MAX ---\n\n";
        $section .= "REQUISITI ASSET GOOGLE (minimi → ideali):\n";
        $section .= "- HEADLINE: 3→15 (max 30 char) | LONG_HEADLINE: 1→5 (max 90 char)\n";
        $section .= "- DESCRIPTION: 2→5 (max 90 char) | MARKETING_IMAGE: 3→15+ (1200x628)\n";
        $section .= "- SQUARE_MARKETING_IMAGE: 1→5+ (1200x1200) | PORTRAIT_MARKETING_IMAGE: 0→5+\n";
        $section .= "- LOGO: 1→5 (1200x1200) | LANDSCAPE_LOGO: 0→5\n";
        $section .= "- YOUTUBE_VIDEO: 0→5 (FORTEMENTE raccomandato, senza Google genera video auto di bassa qualità)\n";
        $section .= "- BUSINESS_NAME: 1 (max 25 char)\n\n";

        foreach ($pmaxCampaigns as $camp) {
            $campId = $camp['campaign_id_google'] ?? '';
            $groups = $agByCampaign[$campId] ?? [];
            if (empty($groups)) continue;

            $section .= "CAMPAGNA PMAX: \"{$camp['campaign_name']}\"";
            $section .= " | Budget: €" . number_format((float)($camp['budget_amount'] ?? 0), 0) . "/giorno";
            $section .= " | Strategia: " . ($camp['bidding_strategy'] ?? '?') . "\n";

            foreach ($groups as $ag) {
                $agId = $ag['asset_group_id_google'];
                $section .= "\n  ASSET GROUP: \"{$ag['asset_group_name']}\"";
                $section .= " | Ad Strength: {$ag['ad_strength']}";
                $section .= " | Status: {$ag['status']}\n";
                $section .= "  Metriche: {$ag['clicks']} click, {$ag['impressions']} imp, ";
                $section .= "€{$ag['cost']} costo, {$ag['conversions']} conv";
                if ((float)$ag['cost'] > 0 && (float)$ag['conversions'] > 0) {
                    $roas = round((float)$ag['conversions_value'] / (float)$ag['cost'], 2);
                    $cpa = round((float)$ag['cost'] / (float)$ag['conversions'], 2);
                    $section .= ", ROAS {$roas}x, CPA €{$cpa}";
                }
                $section .= "\n";

                // Asset summary per type
                $assets = $assetsByAg[$agId] ?? [];
                $byType = [];
                $lowAssets = [];
                foreach ($assets as $asset) {
                    $ft = $asset['field_type'] ?? 'OTHER';
                    $pl = $asset['performance_label'] ?? 'UNSPECIFIED';
                    if (!isset($byType[$ft])) $byType[$ft] = [];
                    $byType[$ft][] = $pl;

                    // Collect LOW text assets for AI to suggest replacements
                    if ($pl === 'LOW' && !empty($asset['text_content'])) {
                        $lowAssets[] = "{$ft}: \"{$asset['text_content']}\"";
                    }
                }

                $section .= "  Asset: ";
                $assetParts = [];
                foreach ($byType as $type => $labels) {
                    $total = count($labels);
                    $counts = array_count_values($labels);
                    $parts = [];
                    foreach (['BEST', 'GOOD', 'LOW', 'LEARNING'] as $l) {
                        if (isset($counts[$l])) $parts[] = "{$counts[$l]} {$l}";
                    }
                    $assetParts[] = "{$total} {$type} (" . implode(', ', $parts) . ")";
                }
                $section .= implode(', ', $assetParts) . "\n";

                // Show LOW asset text content
                if (!empty($lowAssets)) {
                    $section .= "  Asset LOW da sostituire: " . implode('; ', array_slice($lowAssets, 0, 10)) . "\n";
                }

                // Missing asset types check
                $minRequired = [
                    'HEADLINE' => 3, 'LONG_HEADLINE' => 1, 'DESCRIPTION' => 2,
                    'MARKETING_IMAGE' => 3, 'SQUARE_MARKETING_IMAGE' => 1,
                    'LOGO' => 1, 'BUSINESS_NAME' => 1,
                ];
                $missing = [];
                foreach ($minRequired as $type => $min) {
                    $current = count($byType[$type] ?? []);
                    if ($current < $min) {
                        $missing[] = "{$type} ({$current}/{$min} min)";
                    }
                }
                if (empty($byType['YOUTUBE_VIDEO'] ?? [])) {
                    $missing[] = "YOUTUBE_VIDEO (0, raccomandato)";
                }
                if (!empty($missing)) {
                    $section .= "  ⚠ MANCANO: " . implode(', ', $missing) . "\n";
                }

                // Audience signals
                $audiences = json_decode($ag['audience_signals'] ?? 'null', true);
                $themes = json_decode($ag['search_themes'] ?? 'null', true);

                if (!empty($audiences)) {
                    $signalTypes = array_column($audiences, 'type');
                    $section .= "  Audience Signals: " . implode(', ', array_unique($signalTypes)) . "\n";
                } else {
                    $section .= "  Audience Signals: NESSUNO ⚠\n";
                }

                if (!empty($themes)) {
                    $section .= "  Search Themes: " . implode(', ', array_slice($themes, 0, 10)) . "\n";
                } else {
                    $section .= "  Search Themes: NESSUNO ⚠\n";
                }
            }
        }

        return $section;
    }
```

- [ ] **Step 4: Add PMax evaluation rules to the prompt response structure**

In the `buildPrompt()` method, find the section that defines the JSON response structure (the part with `"campaigns": [...]`). Add PMax-specific instructions after the existing rules:

```php
        // After existing campaign response structure, add PMax rules:
        $pmaxRules = <<<PMAX

REGOLE AGGIUNTIVE PER CAMPAGNE PERFORMANCE MAX:

Per campagne PERFORMANCE_MAX, usa "asset_group_analysis" al posto di "ad_groups":
"asset_group_analysis": [{
  "asset_group_name": "Nome",
  "ad_strength": "POOR|AVERAGE|GOOD|EXCELLENT",
  "issues": [{"severity": "...", "area": "assets|audience|performance|structure", "fix_type": "rewrite_ads|add_extensions|add_negatives|null", "description": "...", "recommendation": "..."}],
  "strengths": ["..."]
}]

CRITERI VALUTAZIONE PMAX:
1. Ad Strength: POOR = critico, AVERAGE = warning, GOOD = ok, EXCELLENT = ottimo (+6% conv)
2. Asset sotto minimo = CRITICAL. Sotto ideale = WARNING. Video mancante = WARNING.
3. performance_label LOW su >30% asset di un tipo = critico. Suggerisci sostituzione con fix_type="rewrite_ads"
4. Audience signals assenti = CRITICAL. Manca customer match/first-party = WARNING.
5. <30 conversioni/mese = WARNING (dati insufficienti per ottimizzare). Budget < 3x CPA target = WARNING.
6. Asset group con >50% budget ma <30% conversioni = spesa inefficiente.
7. Per PMax NON valutare: keyword coherence, match type, quality score, ad copy RSA (non esistono).
8. fix_type per PMax: "rewrite_ads" = genera headline/description sostitutivi, "add_extensions" = genera asset mancanti, "add_negatives" = keyword negative campagna, null = non automatizzabile.
PMAX;
```

- [ ] **Step 5: Integrate PMax data into the evaluate() method**

In the `evaluate()` method, after loading campaigns/adGroups/keywords/ads/extensions from the sync, add PMax data loading:

```php
        // After existing data loading, before buildPrompt():
        $pmaxData = $this->loadPmaxData($syncId);
```

Then pass `$pmaxData` to `buildPrompt()` (add parameter) and in `buildPrompt()`, separate PMax campaigns from Search campaigns:

```php
        // In buildPrompt(), separate campaign types:
        $pmaxCampaigns = array_filter($campaigns, fn($c) => ($c['campaign_type'] ?? '') === 'PERFORMANCE_MAX');
        $searchCampaigns = array_filter($campaigns, fn($c) => ($c['campaign_type'] ?? '') !== 'PERFORMANCE_MAX');

        // For Search campaigns: use existing buildAdGroupDetailSection()
        // For PMax campaigns: use new buildPmaxSection()
        if (!empty($pmaxCampaigns)) {
            $prompt .= $this->buildPmaxSection($pmaxCampaigns, $pmaxData);
        }
```

- [ ] **Step 6: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/services/CampaignEvaluatorService.php
```

- [ ] **Step 7: Commit**

```bash
git add modules/ads-analyzer/services/CampaignEvaluatorService.php
git commit -m "feat(ads-analyzer): PMax AI evaluation — asset group analysis, audience signals, asset quality"
```

---

## Chunk 4: Controller & View Adaptation

### Task 6: Update CampaignController for PMax evaluation data

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php`

**Context:** `evaluationShow()` loads evaluation data and passes to view. For PMax, we also load asset group data from the sync so the view can show asset group details. The `evaluate()` method needs no changes — it already calls `CampaignEvaluatorService::evaluate()` which will now include PMax data.

- [ ] **Step 1: Add require_once for new models**

After existing require_once statements at top of controller:

```php
require_once __DIR__ . '/../models/AssetGroup.php';
require_once __DIR__ . '/../models/AssetGroupAsset.php';
```

- [ ] **Step 2: In evaluationShow(), load PMax data and build agIdMap for asset groups**

After the existing `$agIdMap` building code (which maps ad_group_name → ad_group_id_google), add:

```php
        // Load PMax asset group data if this evaluation has a sync
        $assetGroups = [];
        $assetsByAg = [];
        $assetGroupIdMap = [];
        if (!empty($evaluation['sync_id'])) {
            $assetGroups = \Modules\AdsAnalyzer\Models\AssetGroup::getBySyncId($evaluation['sync_id']);
            $assetsByAg = \Modules\AdsAnalyzer\Models\AssetGroupAsset::getBySyncGrouped($evaluation['sync_id']);

            // Map asset_group_name → asset_group_id_google (for fix generation targeting)
            foreach ($assetGroups as $ag) {
                $assetGroupIdMap[$ag['asset_group_name'] ?? ''] = $ag['asset_group_id_google'] ?? '';
            }
        }
```

Pass these to the view:

```php
        return View::render('ads-analyzer/campaigns/evaluation', [
            // ... existing params ...
            'assetGroups' => $assetGroups,
            'assetsByAg' => $assetsByAg,
            'assetGroupIdMap' => $assetGroupIdMap,
        ]);
```

- [ ] **Step 3: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): load PMax asset group data in evaluation view"
```

---

### Task 7: Update evaluation.php view for PMax campaigns

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation.php`

**Context:** The view uses Alpine.js `evaluationDashboard()` component. Campaign data is prepared in PHP and injected via `json_encode`. The current code builds `adGroups` array per campaign from `ad_group_analysis`. For PMax, we build `assetGroups` from `asset_group_analysis` instead. The drawer that shows campaign detail needs to render asset groups with ad_strength badges and asset performance chips when the campaign is PMax.

- [ ] **Step 1: In PHP data preparation section, add PMax asset group data**

Find the `$campaignsData` construction (the `array_map` that builds campaign data for Alpine). After the `adGroups` section, add asset group handling:

```php
            // For PMax campaigns, use asset_group_analysis instead of ad_groups
            $isPmax = ($c['campaign_type'] ?? '') === 'PERFORMANCE_MAX';
            $assetGroupAnalysis = $c['asset_group_analysis'] ?? [];

            $assetGroupsData = [];
            if ($isPmax && !empty($assetGroupAnalysis)) {
                foreach ($assetGroupAnalysis as $agi => $aga) {
                    $agaIssues = [];
                    foreach ($aga['issues'] ?? [] as $ii => $issue) {
                        $fixType = $issue['fix_type'] ?? null;
                        $genType = in_array($fixType, $validFixTypes) ? $fixType : null;
                        $genKey = "ag_{$ci}_{$agi}_{$ii}";

                        $agaIssues[] = [
                            'severity' => $issue['severity'] ?? 'medium',
                            'area' => $issue['area'] ?? '',
                            'description' => $issue['description'] ?? '',
                            'recommendation' => $issue['recommendation'] ?? '',
                            'genType' => $genType,
                            'genKey' => $genKey,
                            'sevClass' => match($issue['severity'] ?? '') {
                                'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            },
                        ];
                    }

                    $agName = $aga['asset_group_name'] ?? 'Asset Group ' . ($agi + 1);
                    $assetGroupsData[] = [
                        'name' => $agName,
                        'adStrength' => $aga['ad_strength'] ?? 'UNSPECIFIED',
                        'adStrengthClass' => match($aga['ad_strength'] ?? '') {
                            'EXCELLENT' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'GOOD' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'AVERAGE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'POOR' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                        },
                        'issues' => $agaIssues,
                        'strengths' => $aga['strengths'] ?? [],
                        'assetGroupIdGoogle' => $assetGroupIdMap[$agName] ?? '',
                    ];
                }
            }
```

Add `isPmax` and `assetGroups` to the campaign data object:

```php
            return [
                // ... existing fields ...
                'isPmax' => $isPmax,
                'assetGroups' => $assetGroupsData,
            ];
```

- [ ] **Step 2: Add PMax asset group rendering in the campaign detail drawer**

After the existing ad group rendering block (the `template x-for="ag in campaignsData[selectedCampaign].adGroups"` section), add a PMax block:

```html
<!-- PMax Asset Groups (shown instead of ad groups for PMax campaigns) -->
<template x-if="campaignsData[selectedCampaign]?.isPmax && campaignsData[selectedCampaign]?.assetGroups?.length">
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            Asset Groups
        </h3>
        <template x-for="(aga, agaIdx) in campaignsData[selectedCampaign].assetGroups" :key="agaIdx">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="$el.closest('[x-for]') || (aga._open = !aga._open)"
                        x-init="aga._open = false"
                        @click="aga._open = !aga._open"
                        class="w-full px-4 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-200" x-text="aga.name"></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="aga.adStrengthClass"
                              x-text="'Ad Strength: ' + aga.adStrength"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-if="aga.issues.filter(i => i.severity === 'critical' || i.severity === 'high').length">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                  x-text="aga.issues.filter(i => i.severity === 'critical' || i.severity === 'high').length"></span>
                        </template>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="aga._open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="aga._open" x-collapse class="border-t border-slate-200 dark:border-slate-700">
                    <!-- Issues -->
                    <template x-if="aga.issues.length">
                        <div class="p-4 space-y-3">
                            <h4 class="text-sm font-semibold text-slate-600 dark:text-slate-400">Problemi</h4>
                            <template x-for="(agaIss, issIdx) in aga.issues" :key="issIdx">
                                <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0"
                                          :class="agaIss.sevClass" x-text="agaIss.severity"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-slate-800 dark:text-slate-200" x-text="agaIss.description"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="agaIss.recommendation"></p>
                                    </div>
                                    <template x-if="agaIss.genType">
                                        <button @click="generateFix(agaIss.genType, {
                                                    campaign_name: campaignsData[selectedCampaign].name,
                                                    ad_group_name: aga.name,
                                                    ad_group_id_google: aga.assetGroupIdGoogle || '',
                                                    issue: agaIss.description,
                                                    recommendation: agaIss.recommendation,
                                                    scope: 'asset_group'
                                                }, agaIss.genKey)"
                                                :disabled="generators[agaIss.genKey]?.loading"
                                                class="shrink-0 inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30 transition-colors disabled:opacity-50">
                                            <template x-if="!generators[agaIss.genKey]?.loading">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            </template>
                                            <template x-if="generators[agaIss.genKey]?.loading">
                                                <svg class="w-3.5 h-3.5 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </template>
                                            Genera con AI
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <!-- Generated fix result -->
                    <template x-for="(agaIss, issIdx) in aga.issues.filter(i => i.genType)" :key="'gen_'+issIdx">
                        <template x-if="generators[agaIss.genKey]?.html">
                            <div class="px-4 pb-4">
                                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 p-4"
                                     x-html="generators[agaIss.genKey].html"></div>
                            </div>
                        </template>
                    </template>
                    <!-- Strengths -->
                    <template x-if="aga.strengths && aga.strengths.length">
                        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                            <h4 class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2">Punti di forza</h4>
                            <ul class="space-y-1">
                                <template x-for="(str, sIdx) in aga.strengths" :key="sIdx">
                                    <li class="text-sm text-slate-600 dark:text-slate-400 flex items-start gap-2">
                                        <svg class="w-4 h-4 text-green-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span x-text="str"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</template>
```

- [ ] **Step 3: Hide ad group section for PMax campaigns**

Wrap the existing ad group `template x-for` with a check:

```html
<template x-if="!campaignsData[selectedCampaign]?.isPmax">
    <!-- existing ad group rendering here -->
</template>
```

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation.php
git commit -m "feat(ads-analyzer): PMax asset group rendering in evaluation view"
```

---

## Chunk 5: Generator Adaptation & Deploy

### Task 8: Update EvaluationGeneratorService for PMax asset copy generation

**Files:**
- Modify: `modules/ads-analyzer/services/EvaluationGeneratorService.php`

**Context:** When `scope = 'asset_group'` is passed in context, the copy prompt should generate PMax asset format (15 headlines, 5 descriptions, 1 long headline) instead of RSA format (5 headlines, 3 descriptions, paths).

- [ ] **Step 1: Update buildCopyPrompt to detect PMax scope**

In `buildCopyPrompt()`, add PMax detection at the beginning:

```php
    private function buildCopyPrompt(array $context, array $data): string
    {
        $scope = $context['scope'] ?? 'ad_group';

        if ($scope === 'asset_group') {
            return $this->buildPmaxCopyPrompt($context, $data);
        }

        // ... existing RSA prompt code unchanged ...
    }
```

- [ ] **Step 2: Add buildPmaxCopyPrompt() method**

```php
    /**
     * Prompt per generare nuovi asset per PMax asset group
     * Genera 15 headline (30 char) + 5 description (90 char) + 1 long headline (90 char)
     */
    private function buildPmaxCopyPrompt(array $context, array $data): string
    {
        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';
        $assetGroupName = $context['ad_group_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Get existing text assets from the evaluation data
        $existingAssets = [];
        // Try to find assets from the sync data passed in $data
        foreach (($data['ads'] ?? []) as $ad) {
            // In PMax context, ads array may contain asset group assets
            if (!empty($assetGroupName) && ($ad['ad_group_name'] ?? '') !== $assetGroupName) continue;
            for ($i = 1; $i <= 15; $i++) {
                $h = $ad["headline_{$i}"] ?? '';
                if ($h) $existingAssets[] = "Headline: {$h}";
            }
            for ($i = 1; $i <= 4; $i++) {
                $d = $ad["description_{$i}"] ?? '';
                if ($d) $existingAssets[] = "Description: {$d}";
            }
            if (count($existingAssets) >= 20) break;
        }
        $existingText = !empty($existingAssets) ? implode("\n", $existingAssets) : 'Nessun asset testuale esistente';

        return <<<PROMPT
Sei un esperto Performance Max Google Ads specializzato in asset creativi ad alta conversione.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA PERFORMANCE MAX: {$campaignName}
ASSET GROUP: {$assetGroupName}

ASSET TESTUALI ESISTENTI:
{$existingText}

PROBLEMA IDENTIFICATO:
{$issue}

RACCOMANDAZIONE:
{$recommendation}

ISTRUZIONI:
Genera nuovi asset testuali per risolvere il problema identificato nell'asset group Performance Max.
I testi devono essere diversificati: non ripetere lo stesso concetto con parole diverse.
IMPORTANTE: I testi devono essere nella STESSA LINGUA degli asset esistenti e del contesto business.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "headlines": ["H1", "H2", "H3", "H4", "H5", "H6", "H7", "H8", "H9", "H10", "H11", "H12", "H13", "H14", "H15"],
  "long_headlines": ["Long Headline 1"],
  "descriptions": ["Desc 1", "Desc 2", "Desc 3", "Desc 4", "Desc 5"]
}

Regole:
- Esattamente 15 headlines, ciascuna max 30 caratteri
- Esattamente 1 long headline, max 90 caratteri
- Esattamente 5 descriptions, ciascuna max 90 caratteri
- Headlines: mix di benefit, feature, CTA, social proof, urgency
- Diversifica: ogni headline deve comunicare un messaggio DIVERSO
- Includi keyword rilevanti per il business
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }
```

- [ ] **Step 3: Update formatFixForDisplay() for PMax copy format**

In `CampaignController::formatFixForDisplay()`, add handling for the `long_headlines` field:

```php
        // After existing headlines display, add long_headlines:
        if (!empty($response['long_headlines'])) {
            $html .= '<div class="mt-3"><p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">LONG HEADLINE</p>';
            foreach ($response['long_headlines'] as $lh) {
                $len = mb_strlen($lh);
                $html .= "<p class='text-sm text-slate-800 dark:text-slate-200'>" . htmlspecialchars($lh) . " <span class='text-xs text-slate-400'>({$len}/90)</span></p>";
            }
            $html .= '</div>';
        }
```

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/services/EvaluationGeneratorService.php modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): PMax asset copy generation with 15 headlines + long headline format"
```

---

### Task 9: Deploy to production

**Files:** None (deployment task)

- [ ] **Step 1: Run migration on production**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 << 'SSHEOF'
mysql -u ainstein -pAinstein_DB_2026\!Secure ainstein_seo < /var/www/ainstein.it/public_html/database/migrations/2026_03_13_pmax_asset_groups.sql
echo "Tables created:"
mysql -u ainstein -pAinstein_DB_2026\!Secure ainstein_seo -e "SHOW TABLES LIKE 'ga_asset_group%'"
SSHEOF
```

- [ ] **Step 2: Deploy code**

```bash
git push origin main
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && git pull origin main"
```

- [ ] **Step 3: Verify — run a sync on a PMax project and check DB**

After syncing a PMax project from the UI, verify:

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 << 'SSHEOF'
mysql -u ainstein -pAinstein_DB_2026\!Secure ainstein_seo -e "
  SELECT COUNT(*) as asset_groups FROM ga_asset_groups;
  SELECT COUNT(*) as assets FROM ga_asset_group_assets;
  SELECT asset_group_name, ad_strength, clicks, conversions, audience_signals IS NOT NULL as has_signals
  FROM ga_asset_groups ORDER BY cost DESC LIMIT 5;
"
SSHEOF
```

- [ ] **Step 4: Verify — run AI evaluation on PMax campaigns and check output**

Run a "Valuta con AI" from the browser on a PMax project. Verify:
- PMax campaigns show `asset_group_analysis` in the AI response
- Asset group issues have correct `fix_type` values
- Ad strength badges render correctly in the view
- "Genera con AI" buttons work for PMax issues

---

## Summary

| Task | Description | Files |
|------|-------------|-------|
| 1 | DB migration (2 tables) | `database/migrations/2026_03_13_pmax_asset_groups.sql` |
| 2 | AssetGroup model | `modules/ads-analyzer/models/AssetGroup.php` |
| 3 | AssetGroupAsset model | `modules/ads-analyzer/models/AssetGroupAsset.php` |
| 4 | Sync service (3 new GAQL methods) | `CampaignSyncService.php` |
| 5 | Evaluator (PMax prompt section) | `CampaignEvaluatorService.php` |
| 6 | Controller (load PMax data) | `CampaignController.php` |
| 7 | View (asset group rendering) | `evaluation.php` |
| 8 | Generator (PMax copy format) | `EvaluationGeneratorService.php` + `CampaignController.php` |
| 9 | Deploy + verify | Production |
