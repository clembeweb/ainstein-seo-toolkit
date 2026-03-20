# Evaluation Report v3 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign completo del report evaluation ads-analyzer con layout accordion, AI analysis in alto, 3 tipi campagna (PMax/Search/Shopping), dati reali da sync, bottoni AI con spiegazione.

**Architecture:** Fix prerequisiti sync (audience signals, product perf, extension metrics) → Creare metodi model mancanti → Aggiornare controller per passare dati extra → Riscrivere evaluation-v2.php con layout accordion + Alpine.js → Riscrivere report-campaign-table.php con 3 template per tipo → Test browser end-to-end.

**Tech Stack:** PHP 8+, Alpine.js, Tailwind CSS, MySQL, Google Ads API (GAQL)

**Spec:** `docs/plans/2026-03-20-eval-report-v3-design.md`
**Mockup:** `public/mockup-eval-v3.html`

---

## File Map

| File | Azione | Responsabilita |
|------|--------|---------------|
| `services/CampaignSyncService.php` | Modificare | Fix extension metrics GAQL (riga 576-583), debug audience signals + product perf |
| `models/AssetGroupAsset.php` | Modificare | Aggiungere `getLowAssets()` |
| `models/ProductPerformance.php` | Modificare | Aggiungere filtro `$campaignIdGoogle` a getBrandSummary/getWasteProducts |
| `controllers/CampaignController.php` | Modificare | evaluationShow() — passare productData per campagna, search themes, low assets |
| `views/campaigns/evaluation-v2.php` | Riscrivere | Layout principale: stati, KPI, AI summary, filtri, accordion loop |
| `views/campaigns/partials/report-campaign-table.php` | Riscrivere | Router che include il sub-partial corretto per tipo |
| `views/campaigns/partials/report-pmax-section.php` | Creare | Asset groups, inventario, LOW assets, search themes, audience signals |
| `views/campaigns/partials/report-search-section.php` | Creare | Ad groups, annunci H1-H3/D1, landing page, keywords top 5 |
| `views/campaigns/partials/report-shopping-section.php` | Creare | Product performance (brand ROAS, spreco, opportunita AI) |
| `views/campaigns/partials/report-extensions-section.php` | Creare | Estensioni campagna (comune a tutti i tipi) |
| `services/EvaluationGeneratorService.php` | Modificare | Migliorare prompt PMax con context search themes + asset group assets |

---

### Task 1: Fix Extension Metrics nel Sync

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignSyncService.php:574-616`

Il problema: la GAQL a riga 576-583 non include `metrics.impressions, metrics.clicks`. Le righe 609-610 hardcodano `clicks => 0, impressions => 0`.

- [ ] **Step 1: Aggiungere metrics alla GAQL e al parsing**

In `syncExtensions()` (riga 574):

```php
// Riga 576-583: aggiungere metrics alla GAQL
$gaql = "SELECT asset.id, asset.name, asset.type, asset.resource_name, " .
        "asset.callout_asset.callout_text, " .
        "asset.sitelink_asset.link_text, asset.sitelink_asset.description1, asset.sitelink_asset.description2, " .
        "asset.structured_snippet_asset.header, asset.structured_snippet_asset.values, " .
        "asset.image_asset.file_size, " .
        "campaign_asset.status, campaign.id, campaign.status, " .
        "metrics.impressions, metrics.clicks " .   // ← AGGIUNGERE
        "FROM campaign_asset " .
        "WHERE campaign.status = 'ENABLED' " .
        "AND segments.date DURING LAST_30_DAYS";   // ← AGGIUNGERE (metrics richiede date segment)
```

E nel loop parsing (riga 588-612):

```php
// Riga 589: aggiungere estrazione metrics
$metrics = $row['metrics'] ?? [];

// Riga 609-610: usare metrics reali
'clicks' => (int) ($metrics['clicks'] ?? 0),
'impressions' => (int) ($metrics['impressions'] ?? 0),
```

**NOTA CRITICA**: Aggiungere `segments.date DURING LAST_30_DAYS` perche le metriche richiedono un segmento data. Senza, la query fallisce con errore GAQL. Le righe saranno per-giorno, serve aggregare per asset ID.

**RISCHIO API**: `campaign_asset` potrebbe NON supportare `metrics.*`. Wrappare la nuova GAQL in try/catch: se fallisce, usare la query originale senza metrics (estensioni restano a 0 impr, non bloccante per il redesign).

```php
// Prima provare con metrics
try {
    $gaqlWithMetrics = $gaql; // la nuova con metrics
    $response = $this->gadsService->searchStream($gaqlWithMetrics);
    $rows = $this->extractRows($response);
    $hasMetrics = true;
} catch (\Exception $e) {
    // Fallback: query senza metrics
    Logger::channel('ads')->warning('Extension metrics not supported, falling back', [
        'error' => $e->getMessage()
    ]);
    $gaqlFallback = "SELECT asset.id, asset.name, asset.type, asset.resource_name, " .
                    "asset.callout_asset.callout_text, " .
                    "asset.sitelink_asset.link_text, asset.sitelink_asset.description1, asset.sitelink_asset.description2, " .
                    "asset.structured_snippet_asset.header, asset.structured_snippet_asset.values, " .
                    "asset.image_asset.file_size, " .
                    "campaign_asset.status, campaign.id, campaign.status " .
                    "FROM campaign_asset WHERE campaign.status = 'ENABLED'";
    $response = $this->gadsService->searchStream($gaqlFallback);
    $rows = $this->extractRows($response);
    $hasMetrics = false;
}
```

Se `$hasMetrics = true`, le righe saranno per-giorno. Serve aggregare:

```php
// Dopo extractRows, aggregare per asset_id + campaign_id
$aggregated = [];
foreach ($rows as $row) {
    $asset = $row['asset'] ?? [];
    $campaignAsset = $row['campaignAsset'] ?? [];
    $campaign = $row['campaign'] ?? [];
    $metrics = $row['metrics'] ?? [];

    $assetId = (string) ($asset['id'] ?? '');
    $campaignId = (string) ($campaign['id'] ?? '');
    if (empty($assetId)) continue;

    $key = $campaignId . '|' . $assetId;
    if (!isset($aggregated[$key])) {
        $aggregated[$key] = [
            'asset' => $asset,
            'campaignAsset' => $campaignAsset,
            'campaign' => $campaign,
            'clicks' => 0,
            'impressions' => 0,
        ];
    }
    $aggregated[$key]['clicks'] += (int) ($metrics['clicks'] ?? 0);
    $aggregated[$key]['impressions'] += (int) ($metrics['impressions'] ?? 0);
}

// Poi iterare su $aggregated invece di $rows per Extension::create()
$count = 0;
foreach ($aggregated as $entry) {
    $asset = $entry['asset'];
    $campaignAsset = $entry['campaignAsset'];
    $campaign = $entry['campaign'];
    $text = $this->extractAssetText($asset);

    Extension::create([
        'project_id' => $this->projectId,
        'sync_id' => $this->syncId,
        'campaign_id_google' => (string) ($campaign['id'] ?? ''),
        'extension_type' => $asset['type'] ?? 'UNKNOWN',
        'extension_text' => $text,
        'status' => $campaignAsset['status'] ?? null,
        'clicks' => $entry['clicks'],
        'impressions' => $entry['impressions'],
    ]);
    $count++;
}
return $count;
```

- [ ] **Step 2: Verificare sintassi PHP**

Run: `php -l modules/ads-analyzer/services/CampaignSyncService.php`
Expected: No syntax errors

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/services/CampaignSyncService.php
git commit -m "fix(ads-analyzer): add metrics to extension sync GAQL query"
```

---

### Task 2: Debug Audience Signals e Product Performance

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignSyncService.php:1143-1201` (audience signals)
- Modify: `modules/ads-analyzer/services/CampaignSyncService.php:1207-1314` (product perf)

Il codice esiste e sembra corretto. I dati sono NULL/vuoti perche:
- **Audience signals**: la API potrebbe dare errore silente (catch a riga 1155-1161) — verificare nel log
- **Product performance**: `shopping_performance_view` potrebbe restituire 0 righe se l'account non ha Merchant Center

- [ ] **Step 1: Aggiungere logging dettagliato a syncAudienceSignals**

Riga 1152-1162: aggiungere log prima e dopo la query per capire se fallisce o ritorna vuoto:

```php
try {
    Logger::channel('ads')->info('PMax audience signals: starting fetch', [
        'campaign_id' => $campaignIdGoogle,
        'sync_id' => $this->syncId,
    ]);
    $response = $this->gadsService->searchStream($gaql);
    $rows = $this->extractRows($response);
    Logger::channel('ads')->info('PMax audience signals: rows fetched', [
        'campaign_id' => $campaignIdGoogle,
        'row_count' => count($rows),
    ]);
} catch (\Exception $e) {
    Logger::channel('ads')->warning('PMax audience signals fetch failed', [
        'campaign_id' => $campaignIdGoogle,
        'error' => $e->getMessage(),
        'error_class' => get_class($e),
    ]);
    return;
}
```

- [ ] **Step 2: Aggiungere logging a syncProductPerformance**

Riga 1232-1240: aggiungere log dettagliato:

```php
try {
    Logger::channel('ads')->info('Product performance: starting fetch', [
        'sync_id' => $this->syncId,
        'project_id' => $this->projectId,
    ]);
    $response = $this->gadsService->searchStream($gaql);
    $rows = $this->extractRows($response);
    Logger::channel('ads')->info('Product performance: rows fetched', [
        'row_count' => count($rows),
    ]);
} catch (\Exception $e) {
    Logger::channel('ads')->warning('Product performance fetch failed', [
        'error' => $e->getMessage(),
        'error_class' => get_class($e),
    ]);
    return;
}
```

- [ ] **Step 3: Verificare sintassi PHP e commit**

Run: `php -l modules/ads-analyzer/services/CampaignSyncService.php`

```bash
git add modules/ads-analyzer/services/CampaignSyncService.php
git commit -m "fix(ads-analyzer): add detailed logging to audience signals and product perf sync"
```

- [ ] **Step 4: Lanciare sync da browser e controllare log**

1. Navigare a `http://localhost/seo-toolkit/ads-analyzer/projects/18/campaign-dashboard`
2. Cliccare "Sincronizza"
3. Dopo sync, verificare nel DB:

```sql
-- Audience signals: devono essere non-NULL per almeno qualche asset group
SELECT id, asset_group_name, search_themes, audience_signals
FROM ga_asset_groups WHERE sync_id = (SELECT MAX(id) FROM ga_syncs WHERE project_id = 18)
AND (search_themes IS NOT NULL OR audience_signals IS NOT NULL) LIMIT 5;

-- Product performance: devono esserci righe
SELECT COUNT(*) FROM ga_product_performance
WHERE sync_id = (SELECT MAX(id) FROM ga_syncs WHERE project_id = 18);

-- Extension metrics: almeno alcune con impressions > 0
SELECT extension_type, SUM(impressions) as tot_impr, SUM(clicks) as tot_clicks
FROM ga_extensions
WHERE sync_id = (SELECT MAX(id) FROM ga_syncs WHERE project_id = 18)
GROUP BY extension_type;
```

4. Se audience signals ancora NULL: controllare log ads channel per errore specifico
5. Se product performance ancora 0: l'account potrebbe non avere Merchant Center — in quel caso la sezione Shopping mostra graceful fallback ("Dati prodotto non disponibili")

- [ ] **Step 5: Commit risultati debug**

Se necessarie ulteriori fix basate sui log, committarle qui.

---

### Task 3: Aggiungere getLowAssets() al Model

**Files:**
- Modify: `modules/ads-analyzer/models/AssetGroupAsset.php` (dopo riga 73)

- [ ] **Step 1: Aggiungere metodo getLowAssets**

```php
/**
 * Get assets with performance_label = LOW for a specific asset group
 */
public static function getLowAssets(int $syncId, string $assetGroupIdGoogle): array
{
    return Database::fetchAll(
        "SELECT * FROM ga_asset_group_assets
         WHERE sync_id = ? AND asset_group_id_google = ? AND performance_label = 'LOW'
         ORDER BY field_type",
        [$syncId, $assetGroupIdGoogle]
    );
}
```

- [ ] **Step 2: Verificare sintassi**

Run: `php -l modules/ads-analyzer/models/AssetGroupAsset.php`

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/models/AssetGroupAsset.php
git commit -m "feat(ads-analyzer): add getLowAssets() method to AssetGroupAsset model"
```

---

### Task 4: Aggiungere filtro campaign a ProductPerformance

**Files:**
- Modify: `modules/ads-analyzer/models/ProductPerformance.php:61-78` (getBrandSummary)
- Modify: `modules/ads-analyzer/models/ProductPerformance.php:105-113` (getWasteProducts)

- [ ] **Step 1: Aggiungere parametro $campaignIdGoogle opzionale**

In `getBrandSummary()` (riga 61):
```php
public static function getBrandSummary(int $projectId, int $syncId, ?string $campaignIdGoogle = null): array
{
    $where = "project_id = ? AND sync_id = ?";
    $params = [$projectId, $syncId];

    if ($campaignIdGoogle !== null) {
        $where .= " AND campaign_id_google = ?";
        $params[] = $campaignIdGoogle;
    }

    return Database::fetchAll(
        "SELECT product_brand, SUM(clicks) as total_clicks, SUM(cost) as total_cost,
                SUM(conversions) as total_conversions, SUM(conversion_value) as total_value,
                CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(conversion_value) / SUM(cost), 2) ELSE 0 END as roas
         FROM ga_product_performance WHERE {$where}
         GROUP BY product_brand
         ORDER BY total_cost DESC
         LIMIT 10",
        $params
    );
}
```

In `getWasteProducts()` (riga 105):
```php
public static function getWasteProducts(int $projectId, int $syncId, ?string $campaignIdGoogle = null): array
{
    $where = "project_id = ? AND sync_id = ? AND conversions = 0 AND cost > 0";
    $params = [$projectId, $syncId];

    if ($campaignIdGoogle !== null) {
        $where .= " AND campaign_id_google = ?";
        $params[] = $campaignIdGoogle;
    }

    return Database::fetchAll(
        "SELECT * FROM ga_product_performance WHERE {$where} ORDER BY cost DESC LIMIT 10",
        $params
    );
}
```

- [ ] **Step 2: Verificare sintassi e commit**

```bash
php -l modules/ads-analyzer/models/ProductPerformance.php
git add modules/ads-analyzer/models/ProductPerformance.php
git commit -m "feat(ads-analyzer): add campaign filter to ProductPerformance model queries"
```

---

### Task 4b: Migliorare Prompt PMax in EvaluationGeneratorService

**Files:**
- Modify: `modules/ads-analyzer/services/EvaluationGeneratorService.php`

I prompt `buildAddAssetPrompt()` e `buildReplaceAssetPrompt()` per PMax non ricevono il contesto completo: mancano search themes, audience signals, e la lista degli asset esistenti nell'asset group.

- [ ] **Step 1: Aggiornare buildReplaceAssetPrompt per includere contesto asset group**

Il metodo riceve `$data` con `search_themes`. Aggiungere anche la lista degli altri asset testuali dell'asset group per coerenza:

```php
// Nel buildReplaceAssetPrompt, dopo la sezione "Context":
$otherAssets = $data['other_assets'] ?? [];
if (!empty($otherAssets)) {
    $prompt .= "\n\nALTRI ASSET NELLO STESSO GRUPPO (mantieni coerenza):\n";
    foreach (array_slice($otherAssets, 0, 20) as $a) {
        $prompt .= "- [{$a['field_type']}] {$a['text_content']}\n";
    }
}

$audienceSignals = $data['audience_signals'] ?? [];
if (!empty($audienceSignals)) {
    $prompt .= "\n\nAUDIENCE SIGNALS CONFIGURATI:\n";
    foreach ($audienceSignals as $signal) {
        $prompt .= "- Tipo: {$signal['type']}, Valori: " . implode(', ', $signal['values'] ?? []) . "\n";
    }
}
```

- [ ] **Step 2: Aggiornare buildAddAssetPrompt per includere search themes e audience**

Stesso pattern: aggiungere contesto search themes e audience signals al prompt.

- [ ] **Step 3: Aggiornare il controller generate() per passare il contesto extra**

Nel `CampaignController::generate()`, quando il tipo e `replace_asset` o `add_asset`, aggiungere:

```php
// Caricare gli altri asset dell'asset group per contesto
if (in_array($type, ['replace_asset', 'add_asset'])) {
    $agIdGoogle = $context['asset_group_id_google'] ?? '';
    if ($agIdGoogle) {
        $context['other_assets'] = AssetGroupAsset::getByAssetGroup($evaluation['sync_id'], $agIdGoogle);
        // Cercare search themes e audience dall'asset group
        $agRow = Database::fetch(
            "SELECT search_themes, audience_signals FROM ga_asset_groups WHERE sync_id = ? AND asset_group_id_google = ?",
            [$evaluation['sync_id'], $agIdGoogle]
        );
        $context['search_themes_list'] = json_decode($agRow['search_themes'] ?? 'null', true) ?? [];
        $context['audience_signals'] = json_decode($agRow['audience_signals'] ?? 'null', true) ?? [];
    }
}
```

- [ ] **Step 4: Verificare sintassi e commit**

```bash
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
php -l modules/ads-analyzer/controllers/CampaignController.php
git add modules/ads-analyzer/services/EvaluationGeneratorService.php modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): improve PMax AI prompts with asset group context, search themes, audience signals"
```

---

### Task 5: Aggiornare Controller evaluationShow()

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php:1137-1258`

Il controller deve passare dati extra alla view v3: low assets per asset group, performance summary, product data per campagna.

**NOTA**: Rimuovere il vecchio blocco `$productData` globale (righe 1221-1231) e sostituirlo con `$productDataByCampaign` per-campagna per evitare confusione.

- [ ] **Step 1: Aggiungere caricamento dati per v3**

Dopo riga 1192 (dove gia carica `$assetsByAg`), aggiungere:

```php
// Performance summary per asset group (breakdown BEST/GOOD/LOW per field_type)
$assetPerfSummary = [];
foreach ($assetGroups as $ag) {
    $agIdGoogle = $ag['asset_group_id_google'];
    $assetPerfSummary[$agIdGoogle] = AssetGroupAsset::getPerformanceSummary(
        $evaluation['sync_id'], $agIdGoogle
    );
}

// Low assets per asset group
$lowAssetsByAg = [];
foreach ($assetGroups as $ag) {
    $agIdGoogle = $ag['asset_group_id_google'];
    $low = AssetGroupAsset::getLowAssets($evaluation['sync_id'], $agIdGoogle);
    if (!empty($low)) {
        $lowAssetsByAg[$agIdGoogle] = $low;
    }
}

// Product data per campagna (non globale)
$productDataByCampaign = [];
if (!empty($productData)) {
    // Raggruppa brand summary e waste per campaign_id_google
    foreach ($syncMetrics['campaigns'] ?? [] as $camp) {
        $campType = strtoupper($camp['campaign_type'] ?? '');
        $campIdGoogle = $camp['campaign_id_google'] ?? '';
        if (in_array($campType, ['SHOPPING', 'PERFORMANCE_MAX']) && $campIdGoogle) {
            $productDataByCampaign[$campIdGoogle] = [
                'brands' => ProductPerformance::getBrandSummary($projectId, $evaluation['sync_id'], $campIdGoogle),
                'waste' => ProductPerformance::getWasteProducts($projectId, $evaluation['sync_id'], $campIdGoogle),
            ];
        }
    }
}
```

- [ ] **Step 2: Passare alla view**

Aggiungere le nuove variabili al `View::render()` (circa riga 1236):

```php
'assetPerfSummary' => $assetPerfSummary,
'lowAssetsByAg' => $lowAssetsByAg,
'productDataByCampaign' => $productDataByCampaign,
```

- [ ] **Step 3: Importare model AssetGroupAsset se necessario**

Verificare che in cima al controller ci sia:
```php
use Modules\AdsAnalyzer\Models\AssetGroupAsset;
use Modules\AdsAnalyzer\Models\ProductPerformance;
```

- [ ] **Step 4: Verificare sintassi e commit**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): pass extra data to evaluation v3 view (perf summary, low assets, product data per campaign)"
```

---

### Task 6: Riscrivere evaluation-v2.php — Layout principale

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation-v2.php`

Riscrittura completa della sezione RESULTS. Mantenere invariati gli stati ERROR, ANALYZING, NO_CHANGE (righe 1-50 circa).

- [ ] **Step 1: Mantenere header e stati invariati**

Le prime ~100 righe (variabili setup, stati error/analyzing/nochange) restano uguali. La riscrittura parte dalla sezione `<?php if ($hasResults): ?>`.

- [ ] **Step 2: Struttura nuova sezione RESULTS**

Ordine render (segue spec e mockup):

```
1. Header (titolo, periodo, PDF, back) — mantenere esistente
2. KPI Bar (6 metriche) — mantenere esistente
3. Analisi Complessiva AI — SPOSTARE QUI DA FONDO PAGINA
4. Filtri tipo + ordinamento — NUOVO
5. Accordion campagne loop — NUOVO (chiama report-campaign-table.php per ogni campagna)
6. Footer "Torna alla lista" — mantenere
```

- [ ] **Step 3: Implementare sezione AI Complessiva spostata**

Copiare il blocco AI summary (attualmente in fondo, circa riga 323-382) e posizionarlo DOPO la KPI bar. Adattare layout a card con raccomandazioni in grid 3 colonne (come mockup).

- [ ] **Step 4: Costruire variabili filtro e ordinamento PHP-side**

Prima del render RESULTS, calcolare le variabili per i filtri:

```php
// Calcola conteggio per tipo e ordina campagne
$campaignTypes = [];
foreach ($viewCampaigns as $camp) {
    $type = strtoupper($camp['campaign_type'] ?? 'UNKNOWN');
    $campaignTypes[$type] = ($campaignTypes[$type] ?? 0) + 1;
}
$typeLabels = [
    'PERFORMANCE_MAX' => 'PMax',
    'SEARCH' => 'Search',
    'SHOPPING' => 'Shopping',
    'DISPLAY' => 'Display',
    'VIDEO' => 'Video',
];
$typeFilterKeys = [
    'PERFORMANCE_MAX' => 'pmax',
    'SEARCH' => 'search',
    'SHOPPING' => 'shopping',
    'DISPLAY' => 'display',
    'VIDEO' => 'video',
];

// Ordinamento server-side: per tipo (PMax, Search, Shopping) poi per spesa decrescente
$typeOrder = ['PERFORMANCE_MAX' => 0, 'SEARCH' => 1, 'SHOPPING' => 2, 'DISPLAY' => 3, 'VIDEO' => 4];
usort($viewCampaigns, function($a, $b) use ($typeOrder) {
    $ta = $typeOrder[strtoupper($a['campaign_type'] ?? '')] ?? 9;
    $tb = $typeOrder[strtoupper($b['campaign_type'] ?? '')] ?? 9;
    if ($ta !== $tb) return $ta - $tb;
    return ($b['cost'] ?? 0) <=> ($a['cost'] ?? 0); // spesa decrescente
});
```

- [ ] **Step 5: Implementare filtri e sort Alpine.js**

```html
<div x-data="{
    filterType: 'all',
    sortBy: 'spend',
    campaigns: <?= json_encode(array_map(fn($c, $i) => [
        'idx' => $i,
        'type' => strtolower($c['campaign_type'] ?? ''),
        'cost' => (float)($c['cost'] ?? 0),
        'score' => (float)($c['ai_score'] ?? 0),
        'roas' => (float)($c['roas'] ?? 0),
        'name' => $c['campaign_name'] ?? '',
    ], $viewCampaigns, array_keys($viewCampaigns))) ?>,
    openCamp: null,
    get sortedCampaigns() {
        let filtered = this.filterType === 'all'
            ? [...this.campaigns]
            : this.campaigns.filter(c => c.type === this.filterType);
        const sortMap = {
            spend: (a,b) => b.cost - a.cost,
            score: (a,b) => b.score - a.score,
            roas: (a,b) => b.roas - a.roas,
            name: (a,b) => a.name.localeCompare(b.name),
        };
        return filtered.sort(sortMap[this.sortBy] || sortMap.spend);
    }
}">
  <!-- Filter buttons (solo tipi presenti) -->
  <div class="flex items-center gap-2">
    <button @click="filterType='all'" :class="...">Tutte (<?= count($viewCampaigns) ?>)</button>
    <?php foreach ($campaignTypes as $type => $count): ?>
    <?php $fk = $typeFilterKeys[$type] ?? strtolower($type); ?>
    <button @click="filterType='<?= $fk ?>'" :class="...">
      <?= $typeLabels[$type] ?? $type ?> (<?= $count ?>)
    </button>
    <?php endforeach; ?>
  </div>
  <!-- Sort dropdown -->
  <select x-model="sortBy" class="...">
    <option value="spend">Spesa ↓</option>
    <option value="score">Score AI ↓</option>
    <option value="roas">ROAS ↓</option>
    <option value="name">Nome A-Z</option>
  </select>

  <!-- Accordion loop con sort client-side -->
  <template x-for="camp in sortedCampaigns" :key="camp.idx">
    <div>
      <!-- Render PHP per ogni campagna, visibilita via x-show nel template -->
    </div>
  </template>

  <!-- NOTA: Il sort client-side richiede che gli accordion siano generati PHP-side
       ma la visibilita/ordine gestiti da Alpine. Approccio pratico: generare tutti
       gli accordion in PHP con display:none, poi Alpine mostra/ordina con x-show.
       Per semplicita v3: sort solo server-side (PHP usort sopra), filter client-side con x-show. -->

  <!-- Approccio semplificato (consigliato per v3): -->
  <?php foreach ($viewCampaigns as $idx => $camp): ?>
  <?php $campType = strtolower($camp['campaign_type'] ?? ''); ?>
  <div x-show="filterType==='all' || filterType==='<?= $typeFilterKeys[strtoupper($campType)] ?? $campType ?>'">
    <!-- accordion campagna -->
  </div>
  <?php endforeach; ?>

  <!-- Empty state quando filtro non produce risultati -->
  <div x-show="sortedCampaigns.length === 0" class="py-8 text-center text-slate-500">
    <p>Nessuna campagna corrisponde al filtro selezionato.</p>
  </div>
</div>
```

**SORT CLIENT-SIDE**: L'ordinamento funziona cosi:
1. PHP genera tutti gli accordion nascosti (con `data-idx`, `data-cost`, `data-score`, `data-roas`, `data-name`, `data-type`)
2. Alpine mantiene un array ordinato di indici
3. Il container usa CSS `order` per riordinare senza ricreare DOM

```html
<?php foreach ($viewCampaigns as $idx => $camp): ?>
<div x-ref="camp_<?= $idx ?>"
     data-idx="<?= $idx ?>"
     data-cost="<?= (float)($camp['cost'] ?? 0) ?>"
     data-score="<?= (float)($camp['ai_score'] ?? 0) ?>"
     data-roas="<?= (float)($camp['roas'] ?? 0) ?>"
     data-name="<?= e($camp['campaign_name'] ?? '') ?>"
     data-type="<?= strtolower($camp['campaign_type'] ?? '') ?>"
     x-show="filterType==='all' || filterType===($el.dataset.type==='performance_max'?'pmax':$el.dataset.type)"
     :style="'order:' + (sortedOrder.indexOf(<?= $idx ?>) >= 0 ? sortedOrder.indexOf(<?= $idx ?>) : <?= $idx ?>)">
  <!-- accordion content -->
</div>
<?php endforeach; ?>
```

Alpine.js sortedOrder computed:
```javascript
get sortedOrder() {
    const items = Array.from(document.querySelectorAll('[data-idx]')).map(el => ({
        idx: parseInt(el.dataset.idx),
        cost: parseFloat(el.dataset.cost),
        score: parseFloat(el.dataset.score),
        roas: parseFloat(el.dataset.roas),
        name: el.dataset.name,
    }));
    const sortFn = {
        spend: (a,b) => b.cost - a.cost,
        score: (a,b) => b.score - a.score,
        roas: (a,b) => b.roas - a.roas,
        name: (a,b) => a.name.localeCompare(b.name),
    };
    return items.sort(sortFn[this.sortBy] || sortFn.spend).map(i => i.idx);
}
```

Il container deve avere `display: flex; flex-direction: column;` per far funzionare CSS `order`.

- [ ] **Step 5: Implementare accordion campagna**

Ogni campagna e un accordion con `x-data` per open/close:

```html
<div @click="openCamp = openCamp === <?= $idx ?> ? null : <?= $idx ?>"
     class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer ...">
  <!-- Score circle -->
  <!-- Badge tipo -->
  <!-- Nome + metriche -->
  <!-- Chevron -->
</div>
<div x-show="openCamp === <?= $idx ?>" x-transition>
  <?php include __DIR__ . '/partials/report-campaign-table.php'; ?>
</div>
```

- [ ] **Step 6: Rimuovere vecchia posizione AI summary**

Eliminare il blocco AI summary dalla vecchia posizione in fondo.

- [ ] **Step 7: Aggiornare $campaignTypeConfig in ENTRAMBI i file**

Shopping: da emerald ad amber. Aggiornare in:
1. `evaluation-v2.php` (riga 27-34) — `$campaignTypeConfig`
2. `report-campaign-table.php` (riga 14-20) — `$campaignTypeConfig`
3. E in qualsiasi sub-partial che definisca i colori per tipo

- [ ] **Step 8: Verificare sintassi e commit**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
git add modules/ads-analyzer/views/campaigns/evaluation-v2.php
git commit -m "feat(ads-analyzer): rewrite evaluation-v2 with accordion layout and AI summary on top"
```

---

### Task 7: Riscrivere report-campaign-table.php — Contenuto accordion

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php`

Questo e il file piu complesso. Contiene il contenuto DENTRO l'accordion espanso per ogni campagna. 3 template distinti per tipo.

**Contratto variabili sub-partial**: ogni sub-partial eredita lo scope PHP del parent via `include`. Le variabili disponibili sono:
- `$camp` — campagna merged (sync+AI data), ha `campaign_type`, `campaign_name`, `campaign_id_google`, metriche
- `$syncCamp` — dati sync raw (metriche numeriche)
- `$aiCamp` — dati AI response per questa campagna (`metrics_comment`, `ad_groups[]`, `product_analysis`, etc.)
- `$isPmax`, `$isSearch`, `$isShopping` — booleani tipo
- `$assetGroups` — tutti gli asset groups del sync (filtrare per campaign_id_google)
- `$assetsByAg` — assets raggruppati per asset_group_id_google
- `$assetPerfSummary` — performance summary per asset_group_id_google
- `$lowAssetsByAg` — assets LOW per asset_group_id_google
- `$productDataByCampaign` — product data per campaign_id_google (solo Shopping)
- `$syncedExtensions` — tutte le estensioni del sync (filtrare per campaign_id_google)
- `$generateUrl`, `$canEdit`, `$evaluation` — per bottoni AI

- [ ] **Step 1: Struttura generale del partial**

Il file riceve le variabili dalla campagna corrente nel loop. Struttura:

```php
<?php
// Variabili ricevute dal parent (evaluation-v2.php):
// $camp (merged sync+AI data), $isPmax, $isSearch, $isShopping
// $syncCamp (metriche sync), $aiCamp (AI response)
// $assetGroups, $assetsByAg, $assetPerfSummary, $lowAssetsByAg
// $productDataByCampaign, $syncedExtensions
// $generateUrl, $canEdit, $evaluation

// AI comment campagna
$metricsComment = $aiCamp['metrics_comment'] ?? '';
?>

<!-- AI Comment box -->
<?php if ($metricsComment): ?>
<div class="bg-slate-700/30 rounded-lg p-3 flex gap-2 mb-3">
  <!-- icona AI + testo -->
</div>
<?php endif; ?>

<!-- Branch per tipo -->
<?php if ($isPmax): ?>
  <?php include __DIR__ . '/report-pmax-section.php'; ?>
<?php elseif ($isShopping): ?>
  <?php include __DIR__ . '/report-shopping-section.php'; ?>
<?php else: ?>
  <?php include __DIR__ . '/report-search-section.php'; ?>
<?php endif; ?>

<!-- Estensioni campagna (comune a tutti) -->
<?php include __DIR__ . '/report-extensions-section.php'; ?>
```

Considero di splittare in sub-partials per mantenere ogni file focalizzato:
- `report-pmax-section.php` — asset groups, inventory, LOW assets
- `report-search-section.php` — ad groups, annunci, landing, keywords
- `report-shopping-section.php` — product performance
- `report-extensions-section.php` — estensioni (comune)

- [ ] **Step 2: Implementare report-search-section.php**

Mantenere la logica attuale per Search (gia funzionante). Portare il codice delle righe 215-416 del vecchio file in questo nuovo partial.

Contenuto: per ogni ad group → grid 2 col (annunci table + landing page) + keywords top 5.

Ogni bottone "Genera" ha sotto-testo esplicativo.

- [ ] **Step 3: Implementare report-pmax-section.php**

Per ogni asset group della campagna corrente:

```php
<?php
$campAssetGroups = array_filter($assetGroups, fn($ag) =>
    ($ag['campaign_id_google'] ?? '') === ($syncCamp['campaign_id_google'] ?? '')
);
?>

<?php foreach ($campAssetGroups as $ag): ?>
  <?php
  $agIdGoogle = $ag['asset_group_id_google'];
  $perfSummary = $assetPerfSummary[$agIdGoogle] ?? [];
  $lowAssets = $lowAssetsByAg[$agIdGoogle] ?? [];
  $searchThemes = json_decode($ag['search_themes'] ?? 'null', true);
  $audienceSignals = json_decode($ag['audience_signals'] ?? 'null', true);
  ?>

  <!-- Sub-accordion asset group -->
  <div class="bg-slate-800/60 rounded-xl border border-slate-600/40 overflow-hidden mb-2">
    <!-- Header: Ad Strength badge + nome + metriche + chevron -->
    <!-- Expanded: Grid 2 col (Inventario + AI Analysis) + LOW assets -->
  </div>
<?php endforeach; ?>
```

Implementare:
- Inventario Asset con mini-barre (flex row, width proporzionali a conteggio/totale)
- Alert "Asset Mancanti" con conteggio (raccomandati da Google: HEADLINE 15, LONG_HEADLINE 5, DESCRIPTION 5, MARKETING_IMAGE 20, SQUARE_MARKETING_IMAGE 20, LOGO 5, YOUTUBE_VIDEO 5)
- Bottone "Genera Asset Mancanti" con spiegazione
- LOW assets list con bottone "Genera Sostituzione" o "Genera Brief"
- Search themes come tag pills
- Audience signals come lista o warning

- [ ] **Step 4: Implementare report-shopping-section.php**

```php
<?php
$campIdGoogle = $syncCamp['campaign_id_google'] ?? '';
$campProductData = $productDataByCampaign[$campIdGoogle] ?? null;
?>

<?php if ($campProductData): ?>
  <!-- Grid 2 col: Brand ROAS bars + Prodotti spreco -->
  <!-- Opportunita AI (da $aiCamp['product_analysis']['opportunities']) -->
<?php else: ?>
  <div class="text-sm text-slate-400 italic p-4">
    Dati prodotto non disponibili per questa campagna. Verifica che l'account abbia un Merchant Center collegato.
  </div>
<?php endif; ?>
```

- [ ] **Step 5: Implementare report-extensions-section.php**

Estrarre la sezione estensioni dal vecchio codice (riga 665-806). Filtrarle per `campaign_id_google` della campagna corrente.

```php
<?php
$campIdGoogle = $syncCamp['campaign_id_google'] ?? '';
$campExtensions = array_filter($syncedExtensions ?? [], fn($ext) =>
    ($ext['campaign_id_google'] ?? '') === $campIdGoogle
);
// Raggruppa per tipo
$extByType = [];
foreach ($campExtensions as $ext) {
    $type = $ext['extension_type'] ?? 'UNKNOWN';
    $extByType[$type][] = $ext;
}
?>
```

- [ ] **Step 6: Verificare sintassi di tutti i partial**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
php -l modules/ads-analyzer/views/campaigns/partials/report-pmax-section.php
php -l modules/ads-analyzer/views/campaigns/partials/report-search-section.php
php -l modules/ads-analyzer/views/campaigns/partials/report-shopping-section.php
php -l modules/ads-analyzer/views/campaigns/partials/report-extensions-section.php
```

- [ ] **Step 7: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/partials/
git commit -m "feat(ads-analyzer): rewrite campaign table partials — 3 type-specific sections + extensions"
```

---

### Task 7b: Fix campaigns_filter bug nel Controller evaluate()

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php:688-698`

Bug critico: il `campaigns_filter` viene salvato nel DB ma NON usato per filtrare i dati passati all'AI evaluator. Se l'utente seleziona 2 campagne su 94, l'AI valuta tutte le 94.

- [ ] **Step 1: Filtrare campagne dopo il caricamento**

Dopo riga 698 (caricamento `$campaigns`), aggiungere filtro:

```php
// Filtra campagne per campaigns_filter (se selezionate dall'utente)
if (!empty($campaignsFilter)) {
    $campaigns = array_values(array_filter($campaigns, function($c) use ($campaignsFilter) {
        return in_array($c['campaign_id_google'] ?? '', $campaignsFilter);
    }));
}
```

Fare lo stesso per `$adGroupsData` (filtrare per campaign_id delle campagne selezionate):
```php
if (!empty($campaignsFilter)) {
    $selectedCampIds = array_column($campaigns, 'campaign_id_google');
    $adGroupsData = array_values(array_filter($adGroupsData, function($ag) use ($selectedCampIds) {
        return in_array($ag['campaign_id_google'] ?? '', $selectedCampIds);
    }));
}
```

- [ ] **Step 2: Verificare e commit**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "fix(ads-analyzer): apply campaigns_filter to data passed to AI evaluator"
```

---

### Task 7c: Backward Compatibility e Note Implementative

**Note per l'implementatore** (nessun file da modificare, solo checklist):

- [ ] **Step 1: Verificare backward compat**

Il check `$evaluation['schema_version']` esiste gia in `evaluationShow()` (circa riga 1153-1160). Le evaluation con `schema_version = 1` rendono con la vecchia view. Le evaluation `schema_version = 2` rendono con `evaluation-v2.php`. NON creare schema_version 3 — la struttura AI response e la stessa, cambia solo il layout view.

- [ ] **Step 2: Verificare che "Genera Brief" per media funzioni**

Il bottone "Genera Brief" per asset media (LOGO, VIDEO, IMAGE) usa `fix_type: replace_asset`. Il prompt `buildReplaceAssetPrompt()` gia gestisce asset non-testuali restituendo suggerimenti di brief creativo (non genera media). Verificare che il bottone chiami `generateFix('replace_asset', ...)` con il contesto giusto e che il risultato inline mostri il brief testuale.

- [ ] **Step 3: Verificare che report-optimizations.php NON sia incluso**

In v3, le ottimizzazioni sono inline negli accordion (bottoni "Genera" per campagna). Il partial `report-optimizations.php` (che ha un bug JS noto) NON deve essere incluso nella nuova view. Verificare che `evaluation-v2.php` non abbia `include 'report-optimizations.php'`.

---

### Task 8: Test Browser End-to-End

**Prerequisito**: Task 1-7 completati + sync lanciato dopo fix Task 1-2.

- [ ] **Step 1: Lanciare nuova sync Amevista**

Navigare a dashboard Amevista → cliccare Sincronizza. Attendere completamento.

Verificare nel DB:
```sql
-- Extension metrics
SELECT extension_type, SUM(impressions) as impr, SUM(clicks) as cl
FROM ga_extensions WHERE sync_id = (SELECT MAX(id) FROM ga_syncs WHERE project_id = 18)
GROUP BY extension_type;

-- Audience signals
SELECT asset_group_name, search_themes IS NOT NULL as has_themes, audience_signals IS NOT NULL as has_audience
FROM ga_asset_groups WHERE sync_id = (SELECT MAX(id) FROM ga_syncs WHERE project_id = 18) LIMIT 5;

-- Product performance
SELECT COUNT(*) FROM ga_product_performance
WHERE sync_id = (SELECT MAX(id) FROM ga_syncs WHERE project_id = 18);
```

- [ ] **Step 2: Lanciare valutazione AI su 3 campagne miste**

Pagina Campagne → Valuta con AI → Deseleziona tutte → Seleziona:
1. Una PMax (es. "UK | OCCHIALI DA SOLE > Sales-Performance Max-1")
2. Una Search (es. "ITA | BRANDING > SEARCH")
3. Una Shopping (es. "ITA | OCCHIALI DA SOLE > SHOPPING")

Cliccare "Avvia Analisi AI" (7 crediti).

- [ ] **Step 3: Verificare report — Layout generale**

Dopo completamento, il report deve mostrare (dall'alto in basso):
- [ ] Header con periodo, sync date, "3 campagne"
- [ ] 6 KPI con delta %
- [ ] Analisi Complessiva AI con score cerchio + summary + 3 raccomandazioni in card
- [ ] Filtri tipo (Tutte, PMax(1), Search(1), Shopping(1)) + sort
- [ ] 3 accordion campagne chiusi con score, badge tipo, metriche

- [ ] **Step 4: Verificare accordion PMax**

Cliccare accordion PMax:
- [ ] AI comment con metriche specifiche
- [ ] Sub-accordion per asset group con Ad Strength badge
- [ ] Inventario asset con mini-barre colorate (BEST/GOOD/LOW)
- [ ] Alert "Asset Mancanti" con conteggio
- [ ] Bottone "Genera Asset Mancanti" con spiegazione
- [ ] Lista asset LOW con bottoni "Genera Sostituzione" / "Genera Brief"
- [ ] Search themes come tag pills (o "non configurato")
- [ ] Audience signals (o warning)
- [ ] Estensioni campagna con conteggio e impressioni

- [ ] **Step 5: Verificare accordion Search**

Cliccare accordion Search:
- [ ] AI comment
- [ ] Ad group con annunci (H1, H2, H3, D1, CTR, Click)
- [ ] Landing page con coerenza e suggerimenti
- [ ] Top 5 keyword con match type
- [ ] Bottone "Riscrivi Annuncio" con spiegazione
- [ ] Estensioni campagna

- [ ] **Step 6: Verificare accordion Shopping**

Cliccare accordion Shopping:
- [ ] AI comment
- [ ] Brand ROAS barre (se productData disponibili) o fallback "non disponibili"
- [ ] Prodotti spreco (se disponibili)
- [ ] Opportunita AI (se disponibili)
- [ ] Estensioni campagna

- [ ] **Step 7: Verificare filtri**

- [ ] Cliccare "PMax" → solo PMax visibile
- [ ] Cliccare "Search" → solo Search visibile
- [ ] Cliccare "Shopping" → solo Shopping visibile
- [ ] Cliccare "Tutte" → tutte e 3 visibili

- [ ] **Step 8: Verificare bottone "Genera"**

- [ ] Cliccare un bottone "Genera" qualsiasi → verifica che non sia disabilitato senza spiegazione
- [ ] Il bottone mostra spiegazione sotto (cosa fa)
- [ ] Dopo click: spinner di loading
- [ ] Dopo completamento: risultato inline o errore con messaggio

- [ ] **Step 9: Verificare export PDF**

- [ ] Cliccare "PDF" → verifica che il download parta senza errori

- [ ] **Step 10: Verificare su progetto con meno campagne (Noa Wedding)**

Navigare a Noa Wedding → Campagne → prendere l'ultima evaluation o lanciarne una nuova.
- [ ] Verificare che funziona con 1 sola campagna Search
- [ ] Nessun errore 500 o JS

- [ ] **Step 11: Commit finale**

```bash
git add -A
git commit -m "feat(ads-analyzer): evaluation report v3 — accordion layout, AI on top, 3 campaign types"
```

---

### Task 9: Cleanup e Documentazione

- [ ] **Step 1: Rimuovere mockup files**

```bash
rm -f public/mockup-eval-comparison.html public/mockup-eval-v3.html
```

- [ ] **Step 2: Aggiornare docs utente se necessario**

Verificare se `shared/views/docs/ads-analyzer.php` necessita aggiornamento per descrivere il nuovo layout report.

- [ ] **Step 3: Aggiornare data-model.html se nuove colonne**

Se sono state aggiunte colonne o metodi, aggiornare `docs/data-model.html`.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "docs(ads-analyzer): cleanup mockups and update documentation for eval v3"
```
