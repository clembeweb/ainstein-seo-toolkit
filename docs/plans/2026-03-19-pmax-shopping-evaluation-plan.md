# PMax & Shopping — Evaluation Report Completamento

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Estendere il report evaluation v2 (che funziona bene per Search) a PMax e Shopping, con asset inventory, product analysis, e supporto dashboard per campagne miste.

**Architecture:** Il report Search usa il pattern flat-document: card campagna → ad group card con annunci+landing affiancati + keywords + genera inline. Per PMax si applica lo stesso pattern con dati diversi: asset group card con inventory per tipo + AI analysis affiancati + genera sostituzione inline. Per Shopping: stessa struttura Search + sezione prodotti prominente. Dashboard: mostra asset groups per PMax, nasconde widget irrilevanti.

**Tech Stack:** PHP 8+, Alpine.js, Tailwind CSS (stack esistente). Nessuna nuova dipendenza.

**Spec di riferimento:** `docs/plans/2026-03-17-ads-evaluation-redesign-design.md` (sezioni 4, 5, 10, 10b)

---

## File Map

### Files to Modify

| File | Changes |
|---|---|
| `controllers/CampaignController.php` | `evaluationShow()`: passare `$assetGroups`+`$assetsByAg` alla v2. `loadSyncMetrics()`: caricare asset groups per PMax. `dashboard()`: caricare asset groups per PMax nella performance table. |
| `views/campaigns/evaluation-v2.php` | Fix dedup per campagna (reset `$coveredTypes` per ogni campaign). Aggiungere PMax optimizations (replace_asset, add_asset) all'estrazione. |
| `views/campaigns/partials/report-campaign-table.php` | Riscrivere sezione PMax: asset inventory raggruppato per tipo, Ad Strength prominente, LOW evidenziati, genera inline, search themes, audience signals. |
| `views/campaigns/dashboard.php` | Sezione performance table: per PMax mostrare asset groups con Ad Strength invece di ad groups con keyword count. |

### Files NOT Modified

| File | Why |
|---|---|
| `views/campaigns/partials/report-product-analysis.php` | Già completo — brand bars, waste, opportunities, AI summary. Funziona se `$productData` è popolato. |
| `services/CampaignEvaluatorService.php` | Prompt PMax già strutturato con asset group analysis, search themes, audience signals. |
| `services/EvaluationGeneratorService.php` | `replace_asset` e `add_asset` già implementati nel match/generate. |
| `models/AssetGroup.php`, `models/AssetGroupAsset.php` | Già completi con tutti i metodi necessari. |
| `models/ProductPerformance.php` | Già completo con saveBatch, getTopBySpend, getBrandSummary, etc. |
| `services/CampaignSyncService.php` | `syncProductPerformance()` già implementato, chiama `shopping_performance_view`. |

---

## Data Flow: come arrivano i dati PMax

### Sync (già funzionante)
```
Google Ads API → CampaignSyncService:
  ga_campaigns (campaign_type = 'PERFORMANCE_MAX')
  ga_asset_groups (asset_group_name, ad_strength, clicks, cost, conversions, search_themes JSON, audience_signals JSON)
  ga_asset_group_assets (field_type, performance_label, text_content, asset_group_id_google)
  ga_product_performance (se Shopping/PMax con feed)
```

### Report v2 (da fixare)
```
evaluationShow() →
  loadSyncMetrics($syncId) → ritorna campaigns[].ad_groups[] (vuoto per PMax!)
  MANCA: campaigns[].asset_groups[].assets[] per PMax
  MANCA: $assetGroups/$assetsByAg nella view v2

DOPO IL FIX:
  loadSyncMetrics($syncId) →
    Per SEARCH/SHOPPING/DISPLAY: campaigns[].ad_groups[].ads[].keywords[]  (come ora)
    Per PERFORMANCE_MAX: campaigns[].asset_groups[].assets[]  (NUOVO)
  evaluationShow() → passa anche $assetGroups, $assetsByAg alla view v2
```

### Dashboard (da fixare)
```
dashboard() → $campaignsPerformance[].ad_groups[] (vuoto per PMax)

DOPO IL FIX:
  Per PMax: $campaignsPerformance[].asset_groups[] con Ad Strength e metriche
```

---

## Task 1: Controller — loadSyncMetrics() PMax-aware

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` (metodo `loadSyncMetrics()` ~riga 1875)

- [ ] **Step 1: Leggere il metodo loadSyncMetrics() attuale**

Il metodo carica campaigns → ad_groups → ads → keywords. Per ogni campagna costruisce `$result[] = array_merge($camp, ['ad_groups' => $adGroupsResult])`. Serve aggiungere logica PMax DOPO la costruzione della struttura.

- [ ] **Step 2: Aggiungere caricamento asset groups per PMax**

Dopo la riga `$result[] = array_merge($camp, ['ad_groups' => $adGroupsResult]);` (circa riga 1952), aggiungere:

```php
// Per campagne PMax: carica asset groups + assets
if (strtoupper($camp['campaign_type'] ?? '') === 'PERFORMANCE_MAX') {
    $campId = $camp['campaign_id_google'];

    // Asset groups per questa campagna
    $campAssetGroups = Database::fetchAll(
        "SELECT * FROM ga_asset_groups WHERE sync_id = ? AND campaign_id_google = ? ORDER BY cost DESC",
        [$syncId, $campId]
    );

    // Assets per tutti gli asset group di questa campagna
    $agIds = array_column($campAssetGroups, 'asset_group_id_google');
    $allAssets = [];
    if (!empty($agIds)) {
        $placeholders = implode(',', array_fill(0, count($agIds), '?'));
        $allAssets = Database::fetchAll(
            "SELECT * FROM ga_asset_group_assets WHERE sync_id = ? AND asset_group_id_google IN ({$placeholders}) ORDER BY field_type, performance_label",
            array_merge([$syncId], $agIds)
        );
    }

    // Raggruppa assets per asset_group_id_google
    $assetsByAgId = [];
    foreach ($allAssets as $asset) {
        $assetsByAgId[$asset['asset_group_id_google']][] = $asset;
    }

    // Costruisci struttura gerarchica
    $assetGroupsResult = [];
    foreach ($campAssetGroups as $ag) {
        // ROAS/CPA per asset groups (colonna conversions_value, non conversion_value)
        $agCost = (float)($ag['cost'] ?? 0);
        $agConv = (float)($ag['conversions'] ?? 0);
        $agConvValue = (float)($ag['conversions_value'] ?? 0);
        $ag['roas'] = $agCost > 0 ? round($agConvValue / $agCost, 1) : 0;
        $ag['cpa'] = $agConv > 0 ? round($agCost / $agConv, 2) : 0;
        $ag['conversion_value'] = $agConvValue; // normalize per la view

        $agId = $ag['asset_group_id_google'];
        $ag['assets'] = $assetsByAgId[$agId] ?? [];
        $ag['search_themes'] = json_decode($ag['search_themes'] ?? '[]', true) ?: [];
        $ag['audience_signals'] = json_decode($ag['audience_signals'] ?? '[]', true) ?: [];
        $assetGroupsResult[] = $ag;
    }

    // Aggiungi asset_groups all'ultimo elemento di $result
    $lastIdx = count($result) - 1;
    $result[$lastIdx]['asset_groups'] = $assetGroupsResult;
}
```

**NOTA**: NON usare `$computeRoasCpa($ag)` per asset groups perche la colonna e `conversions_value` (non `conversion_value`). Calcolo inline.

- [ ] **Step 3: Verificare sintassi**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
```

---

## Task 2: Controller — evaluationShow() passa dati PMax alla v2

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` (metodo `evaluationShow()` ~riga 1226)

- [ ] **Step 1: Aggiungere assetGroups e assetsByAg al render v2**

Nella chiamata `View::render('ads-analyzer/campaigns/evaluation-v2', [...])` aggiungere:

```php
'assetGroups' => $assetGroups,
'assetsByAg' => $assetsByAg,
```

Queste variabili sono già caricate più sopra nel metodo (righe 1177-1182). Basta aggiungerle all'array del render v2.

- [ ] **Step 2: Verificare sintassi**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
```

---

## Task 3: evaluation-v2.php — Fix dedup per campagna + PMax optimizations

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation-v2.php` (~riga 71-135)

- [ ] **Step 1: Aggiungere PMax types a $validFixTypes**

Alla riga 37 di evaluation-v2.php, aggiungere `replace_asset` e `add_asset`:

```php
$validFixTypes = ['rewrite_ads', 'rewrite_ad', 'add_negatives', 'remove_duplicates', 'add_extensions', 'replace_asset', 'add_asset'];
```

Senza questo, i bottoni "Genera" per PMax non appariranno mai.

- [ ] **Step 2: Verificare che il dedup non filtri tipi PMax**

Il `$coveredTypes` e gia resettato per ogni campagna (verificato: riga 73). I tipi PMax (`replace_asset`, `add_asset`) sono diversi dai tipi Search (`rewrite_ads`, `add_extensions`), quindi il dedup non li filtra. Nessuna modifica necessaria.

- [ ] **Step 3: Bridge $syncAdGroups per PMax**

**CRITICO**: In `report-campaign-table.php` riga 140, `$syncAdGroups = $campaign['ad_groups'] ?? [];`. Per PMax, `ad_groups` e vuoto — gli asset groups sono in `$campaign['asset_groups']`. Modificare riga 140:

```php
$syncAdGroups = $isPmax ? ($campaign['asset_groups'] ?? []) : ($campaign['ad_groups'] ?? []);
```

Questo fa si che il loop `foreach ($syncAdGroups as $agIdx => $ag)` iteri gli asset groups per PMax, e il blocco `<?php if ($isPmax): ?>` nel body renderizzi il template PMax corretto.

- [ ] **Step 3: Verificare sintassi**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
```

---

## Task 4: report-campaign-table.php — Riscrivere sezione PMax

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php` (~riga 418-462)

Questo è il task principale. La sezione PMax attuale è:
- Ad Strength badge (piccolo)
- AI comment
- Tabella piatta di tutti gli asset

Deve diventare (stesso pattern consulenziale di Search):

### Layout per ogni Asset Group PMax

```
┌─────────────────────────────────────────────────────────────┐
│ Asset Group Name        Ad Strength: ████ GOOD              │
│                         "Buone performance — margine con +asset" │
│ 91 click · 9,74% CTR · 212€ · 3 conv. · 0,00x ROAS        │
├──────────────────────────────┬──────────────────────────────┤
│ 📊 Inventario Asset          │ 🤖 Analisi AI               │
│                              │                              │
│ HEADLINE     8/15            │ AI: "Ad Strength GOOD,       │
│  ██ 3 BEST ██ 3 GOOD        │ 2 headline LOW da            │
│  ██ 1 LOW  ██ 1 LEARNING    │ sostituire..."               │
│                              │                              │
│ DESCRIPTION  2/5             │ 🔎 Search Themes:            │
│  ██ 1 GOOD ██ 1 LEARNING    │ luxury wedding, amalfi coast │
│                              │                              │
│ IMAGE       12/20            │ 👥 Audience:                 │
│  ██ 8 GOOD ██ 4 LEARNING    │ In-market: Wedding services  │
│                              │                              │
│ LOGO         3/5             │                              │
│  ██ 3 GOOD                   │                              │
├──────────────────────────────┴──────────────────────────────┤
│ ⚠️ Asset LOW (da sostituire)                                 │
│ HEADLINE: "Scarpe Online" — Performance LOW                  │
│   [💡 Genera Sostituzione (1 credito)]                       │
│ HEADLINE: "Offerte Speciali" — Performance LOW               │
│   [💡 Genera Sostituzione (1 credito)]                       │
│                                                              │
│ ⚠️ Asset Mancanti: DESCRIPTION (2/5), YOUTUBE_VIDEO (0/5)   │
│   [💡 Genera Asset Mancanti (1 credito)]                     │
└──────────────────────────────────────────────────────────────┘
```

- [ ] **Step 1: Definire costanti Google recommended**

All'inizio del file (dopo i badge configs), aggiungere:

```php
$recommendedAssets = [
    'HEADLINE' => ['min' => 3, 'max' => 15, 'label' => 'Headline'],
    'LONG_HEADLINE' => ['min' => 1, 'max' => 5, 'label' => 'Long Headline'],
    'DESCRIPTION' => ['min' => 2, 'max' => 5, 'label' => 'Descrizione'],
    'BUSINESS_NAME' => ['min' => 1, 'max' => 1, 'label' => 'Nome Attivita'],
    'MARKETING_IMAGE' => ['min' => 1, 'max' => 20, 'label' => 'Immagine'],
    'SQUARE_MARKETING_IMAGE' => ['min' => 1, 'max' => 20, 'label' => 'Imm. Quadrata'],
    'LOGO' => ['min' => 1, 'max' => 5, 'label' => 'Logo'],
    'YOUTUBE_VIDEO' => ['min' => 0, 'max' => 5, 'label' => 'Video YouTube'],
];

$adStrengthExplanation = [
    'EXCELLENT' => 'Massime performance — nessun intervento necessario',
    'GOOD' => 'Buone performance — margine di miglioramento con piu asset',
    'AVERAGE' => 'Performance nella media — aggiungere asset per migliorare',
    'POOR' => 'Performance scarse — intervento urgente su asset e varieta',
    'UNSPECIFIED' => '',
];
```

- [ ] **Step 2: Riscrivere il blocco `<?php else: ?>` (PMax)**

Sostituire righe 418-462 con la nuova struttura. Il codice deve:

1. Leggere `$ag['asset_groups']` dalla struttura syncMetrics (caricata in Task 1). Se vuoto, fallback a `$ag` stesso (single asset group = l'ad group è l'asset group).

2. Per ogni asset group:
   - **Header**: nome + Ad Strength badge grande + spiegazione + metriche
   - **Grid 2 colonne (lg:grid-cols-2)**:
     - LEFT: Asset inventory raggruppato per `field_type`, con conteggio per performance label, barra visuale `count/max`, evidenziare tipi sotto il minimo
     - RIGHT: AI analysis + search themes + audience signals
   - **Sotto (full width)**: asset LOW elencati con bottone "Genera Sostituzione" ciascuno + asset mancanti con bottone "Genera Asset Mancanti"

3. I bottoni "Genera" usano lo stesso pattern di Search: chiamano `generateFix('replace_asset', context, key)` dal componente Alpine padre. Il context include `asset_group_name`, `asset_group_id_google`, `target_asset_type`, `target_asset_text`.

**Dati disponibili:**
- `$ag` dall'iterazione syncAdGroups — per PMax contiene le colonne di `ga_asset_groups` (ad_strength, clicks, cost, search_themes, audience_signals)
- `$ag['assets']` — array di `ga_asset_group_assets` con field_type, performance_label, text_content
- `$aiAg` — AI analysis per questo asset group (da `$aiCampaign['asset_group_analysis'][]`)

**Raggruppamento asset per tipo (PHP):**
```php
$assetsByType = [];
foreach ($ag['assets'] ?? [] as $asset) {
    $ft = $asset['field_type'] ?? 'UNKNOWN';
    $assetsByType[$ft][] = $asset;
}
// Conteggio per performance label per tipo
$inventoryByType = [];
foreach ($assetsByType as $ft => $assets) {
    $counts = ['BEST' => 0, 'GOOD' => 0, 'LOW' => 0, 'LEARNING' => 0, 'UNSPECIFIED' => 0];
    foreach ($assets as $a) {
        $pl = strtoupper($a['performance_label'] ?? 'UNSPECIFIED');
        $counts[$pl] = ($counts[$pl] ?? 0) + 1;
    }
    $inventoryByType[$ft] = ['total' => count($assets), 'breakdown' => $counts];
}
```

- [ ] **Step 3: Verificare sintassi**

```bash
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
```

---

## Task 5: Dashboard — Performance table PMax-aware

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` (metodo `dashboard()` ~riga 87-170)
- Modify: `modules/ads-analyzer/views/campaigns/dashboard.php` (~riga 359-477)

- [ ] **Step 1: Controller — caricare asset groups per PMax**

**Prima**: nella costruzione di `$campaignsPerformance` (circa riga 156), aggiungere `'campaign_id_google' => $c['campaign_id_google']` alla mappa array. Attualmente manca e serve per la query asset groups.

**Poi**: nel metodo `dashboard()`, dopo la costruzione di `$campaignsPerformance` (circa riga 170), caricare asset groups per PMax:

```php
// Aggiungi asset groups per campagne PMax
if ($latestSync) {
    require_once __DIR__ . '/../models/AssetGroup.php';
    foreach ($campaignsPerformance as &$campPerf) {
        if (strtoupper($campPerf['type'] ?? '') === 'PERFORMANCE_MAX') {
            // Usa getBySyncAndCampaign() che gia esiste in AssetGroup.php
            $campPerf['asset_groups'] = \Modules\AdsAnalyzer\Models\AssetGroup::getBySyncAndCampaign(
                $latestSync['id'], $campPerf['campaign_id_google'] ?? ''
            );
        }
    }
    unset($campPerf);
}
```

**NOTA**: usare `getBySyncAndCampaign()` che gia esiste nel model. NON creare nuovi metodi.

- [ ] **Step 2: View — mostrare asset groups per PMax**

In `dashboard.php`, dopo la riga `<?php if ($hasAdGroups): ?>` (~riga 405), aggiungere un `elseif` per PMax:

```php
<?php elseif (!empty($camp['asset_groups'])): ?>
<!-- PMax: Asset Groups con Ad Strength -->
<?php foreach ($camp['asset_groups'] as $agi => $assetGroup): ?>
<tr x-show="expandedCampaign === <?= $ci ?>" x-cloak
    class="bg-slate-50/50 dark:bg-slate-700/20 transition-colors">
    <td class="px-4 py-2.5">
        <div class="flex items-center gap-2 pl-6">
            <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-sm text-slate-700 dark:text-slate-300"><?= e($assetGroup['asset_group_name'] ?? '') ?></span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                <?= match(strtoupper($assetGroup['ad_strength'] ?? '')) {
                    'EXCELLENT' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                    'GOOD' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                    'AVERAGE' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                    'POOR' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                    default => 'bg-slate-100 text-slate-600',
                } ?>">
                <?= e(strtoupper($assetGroup['ad_strength'] ?? 'N/D')) ?>
            </span>
        </div>
    </td>
    <td class="px-4 py-2.5 text-right text-sm text-slate-600 dark:text-slate-400"><?= number_format((int)($assetGroup['clicks'] ?? 0)) ?></td>
    <td class="px-4 py-2.5 text-right text-sm text-slate-600 dark:text-slate-400"><?= number_format((float)($assetGroup['ctr'] ?? 0), 2) ?>%</td>
    <td class="px-4 py-2.5 text-right text-sm text-slate-600 dark:text-slate-400"><?= number_format((float)($assetGroup['cost'] ?? 0), 2) ?>&euro;</td>
    <td class="px-4 py-2.5 text-right text-sm text-slate-600 dark:text-slate-400"><?= number_format((float)($assetGroup['conversions'] ?? 0), 1) ?></td>
    <td class="px-4 py-2.5 text-right text-sm text-slate-400">
        <?php $agRoas = (float)($assetGroup['cost'] ?? 0) > 0 ? round((float)($assetGroup['conversions_value'] ?? 0) / (float)$assetGroup['cost'], 2) : 0; ?>
        <?= $agRoas > 0 ? number_format($agRoas, 2) . 'x' : '-' ?>
    </td>
    <td class="px-4 py-2.5 text-right text-sm text-slate-400">-</td>
</tr>
<?php endforeach; ?>
```

- [ ] **Step 3: Dashboard — conteggio corretto per PMax**

Nella riga che mostra "N ad group" (riga 388), aggiungere gestione PMax:

```php
<?php if ($hasAdGroups): ?>
<span class="text-xs text-slate-400 dark:text-slate-500"><?= count($camp['ad_groups']) ?> ad group</span>
<?php elseif (!empty($camp['asset_groups'])): ?>
<span class="text-xs text-slate-400 dark:text-slate-500"><?= count($camp['asset_groups']) ?> asset group</span>
<?php endif; ?>
```

- [ ] **Step 4: Verificare sintassi**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/views/campaigns/dashboard.php
php -l modules/ads-analyzer/models/AssetGroup.php
```

---

## Task 6: Verifica prodotti + integration test

- [ ] **Step 1: Verificare che $productData arrivi alla view v2**

In `evaluationShow()` il codice carica `$productData` se ci sono campagne SHOPPING/PERFORMANCE_MAX. Verificare che sia passato al render v2 (dovrebbe essere gia a riga 1237).

- [ ] **Step 2: Verificare che il partial sia incluso**

In `evaluation-v2.php`, verificare che `report-product-analysis.php` sia incluso dopo la campaign table e prima dell'AI summary:

```php
<?php if (!empty($productData)): ?>
<?php include __DIR__ . '/partials/report-product-analysis.php'; ?>
<?php endif; ?>
```

- [ ] **Step 3: PHP syntax check tutti i file modificati**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/views/campaigns/evaluation-v2.php
php -l modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php
php -l modules/ads-analyzer/views/campaigns/dashboard.php
php -l modules/ads-analyzer/models/AssetGroup.php
```

- [ ] **Step 4: Test browser — Search (regressione)**

1. Aprire `http://localhost/seo-toolkit/ads-analyzer/projects/11/campaigns/evaluations/37`
2. Verificare che il report Search funzioni come prima:
   - KPI bar, tabella panoramica, card campagna, annunci+landing affiancati, keywords, estensioni, AI summary
   - Bottone "Genera Riscrittura" presente e funzionante
   - Bottone "Genera Estensioni" presente nella sezione estensioni
3. Nessuna regressione

- [ ] **Step 5: Test browser — Dashboard**

1. Aprire `http://localhost/seo-toolkit/ads-analyzer/projects/11/campaign-dashboard`
2. Se ci sono campagne PMax: verificare che mostri asset groups con Ad Strength nella tabella performance
3. Se solo Search: verificare che tutto funzioni come prima (regressione check)

- [ ] **Step 6: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php \
      modules/ads-analyzer/views/campaigns/evaluation-v2.php \
      modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php \
      modules/ads-analyzer/views/campaigns/dashboard.php \
      modules/ads-analyzer/models/AssetGroup.php
git commit -m "feat(ads-analyzer): PMax/Shopping support in evaluation v2 report + dashboard"
```

---

## Note per l'implementatore

### Dati PMax disponibili nel sync

**ga_asset_groups**: `asset_group_name`, `ad_strength` (POOR/AVERAGE/GOOD/EXCELLENT), `clicks`, `impressions`, `cost`, `conversions`, `conversions_value`, `ctr`, `search_themes` (JSON array), `audience_signals` (JSON object)

**ga_asset_group_assets**: `field_type` (HEADLINE/LONG_HEADLINE/DESCRIPTION/BUSINESS_NAME/MARKETING_IMAGE/SQUARE_MARKETING_IMAGE/LOGO/YOUTUBE_VIDEO), `performance_label` (BEST/GOOD/LOW/LEARNING/UNSPECIFIED), `text_content`, `url_content`

### Pattern "Genera" inline (da Search, da replicare per PMax)

```php
<!-- Esempio per replace_asset -->
<button @click="generateFix('replace_asset', {
    campaign_name: '...',
    asset_group_name: '...',
    asset_group_id_google: '...',
    target_asset_type: 'HEADLINE',
    target_asset_text: 'testo asset LOW'
}, 'popt_0_0_0')" ...>
    Genera Sostituzione (1 credito)
</button>
```

### Shopping: flusso naturale Search + prodotti

Le campagne Shopping hanno ad groups e annunci come Search — passano nel template Search automaticamente. Il valore aggiunto per Shopping e la sezione prodotti (`report-product-analysis.php`) che appare se `$productData` e popolato. Nessuna modifica speciale necessaria per Shopping nella view.

### Nomi colonne corretti per asset data

- `ga_asset_group_assets.field_type` (NON `asset_type` o `type`)
- `ga_asset_group_assets.text_content` (NON `content` o `text`)
- `ga_asset_group_assets.performance_label` (NON `performance`)
- `ga_asset_groups.conversions_value` (NON `conversion_value`)

### PMax: niente keyword/landing analysis

Per PMax NON mostrare:
- Sezione keywords (PMax non ha keyword tradizionali — i search themes sono nell'asset group)
- Analisi landing per ad group (PMax gestisce landing internamente)
- Bottone "Genera Riscrittura annunci" (PMax non ha annunci RSA — ha asset)

### Apply limitazioni PMax (v1)

Per ottimizzazioni PMax (`replace_asset`, `add_asset`):
- **Genera**: funziona (AI produce nuovi asset testuali)
- **Esporta CSV**: funziona
- **Applica su Google Ads**: NON disponibile in v1 (manca `mutateAssetGroupAssets()` in GoogleAdsService)
- Il bottone "Applica su Google Ads" NON deve apparire per PMax. Mostrare: "Esporta per applicazione manuale"
