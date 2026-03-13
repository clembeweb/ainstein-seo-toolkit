# Performance Max Campaign Analysis — Design Spec

> **Data**: 2026-03-13
> **Modulo**: ads-analyzer
> **Obiettivo**: Estrarre dati PMax-specifici via Google Ads API e fornire valutazione AI professionale delle campagne Performance Max.

---

## Problema

Il sync e la valutazione attuali sono costruiti per campagne Search: keyword, match type, quality score, RSA ad copy. Le campagne Performance Max hanno una struttura completamente diversa (asset group, asset con performance label, audience signals, search themes) e oggi vengono valutate con metriche inadeguate — nessun dato su qualità asset, diversità creativa, segnali audience.

## Soluzione

### 1. Sync PMax — 3 nuove query GAQL

Integrato in `CampaignSyncService::syncAll()`. Per ogni campagna con `campaign_type = PERFORMANCE_MAX`:

#### 1a. `syncAssetGroups($syncId, $projectId, $campaignIdGoogle, $dateFrom, $dateTo)`

```sql
SELECT asset_group.id, asset_group.name, asset_group.status,
  asset_group.ad_strength, asset_group.primary_status,
  metrics.impressions, metrics.clicks, metrics.cost_micros,
  metrics.conversions, metrics.conversions_value
FROM asset_group
WHERE campaign.id = {CAMPAIGN_ID}
  AND segments.date BETWEEN '{date_from}' AND '{date_to}'
```

Salva in `ga_asset_groups`.

#### 1b. `syncAssetGroupAssets($syncId, $projectId, $campaignIdGoogle)`

Due step:

**Step 1** — Recupera link asset → asset group + performance label:
```sql
SELECT asset_group.id, asset_group_asset.asset,
  asset_group_asset.field_type, asset_group_asset.performance_label,
  asset_group_asset.primary_status
FROM asset_group_asset
WHERE campaign.id = {CAMPAIGN_ID}
```

**Step 2** — Fetch bulk contenuto asset (resource names raccolti dallo step 1):
```sql
SELECT asset.resource_name, asset.type,
  asset.text_asset.text,
  asset.image_asset.full_size.url,
  asset.youtube_video_asset.youtube_video_id
FROM asset
WHERE asset.resource_name IN ({RESOURCE_NAMES})
```

Salva in `ga_asset_group_assets`. Il `text_content` contiene testo per headline/description/business_name. `url_content` contiene URL immagine o YouTube video ID.

#### 1c. `syncAudienceSignals($syncId, $projectId, $campaignIdGoogle)`

```sql
SELECT asset_group.id,
  asset_group_signal.audience,
  asset_group_signal.search_theme
FROM asset_group_signal
WHERE campaign.id = {CAMPAIGN_ID}
```

Parsato e salvato come JSON nelle colonne `audience_signals` e `search_themes` di `ga_asset_groups`. Struttura JSON semplificata:

```json
// audience_signals
{
  "custom_segments": [{"type": "search_terms", "value": "occhiali da sole"}, {"type": "url", "value": "competitor.com"}],
  "interests": ["Fashion", "Accessories"],
  "demographics": {"age": "25-54", "gender": "all"},
  "user_lists": ["converters", "website_visitors"]
}

// search_themes
["occhiali da sole online", "sunglasses shop", "ray ban italia"]
```

### 2. Database — 2 nuove tabelle

#### `ga_asset_groups`

```sql
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
```

#### `ga_asset_group_assets`

```sql
CREATE TABLE IF NOT EXISTS ga_asset_group_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_id INT NOT NULL,
    project_id INT NOT NULL,
    asset_group_id_google VARCHAR(50) NOT NULL,
    asset_id_google VARCHAR(50) DEFAULT NULL,
    field_type VARCHAR(50) NOT NULL COMMENT 'HEADLINE, LONG_HEADLINE, DESCRIPTION, MARKETING_IMAGE, SQUARE_MARKETING_IMAGE, PORTRAIT_MARKETING_IMAGE, YOUTUBE_VIDEO, LOGO, LANDSCAPE_LOGO, BUSINESS_NAME, CALL_TO_ACTION_SELECTION',
    performance_label ENUM('BEST','GOOD','LOW','LEARNING','UNSPECIFIED') DEFAULT 'UNSPECIFIED',
    primary_status VARCHAR(50) DEFAULT NULL,
    text_content TEXT DEFAULT NULL COMMENT 'Testo per headline/description/business_name',
    url_content VARCHAR(500) DEFAULT NULL COMMENT 'URL immagine o YouTube video ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync (sync_id),
    INDEX idx_asset_group (asset_group_id_google),
    INDEX idx_field_type (field_type),
    INDEX idx_performance (performance_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Modelli — 2 nuovi

#### `AssetGroup.php`

```php
class AssetGroup {
    static function createBatch(int $syncId, int $projectId, array $rows): void;
    static function getBySyncId(int $syncId): array;
    static function getBySyncAndCampaign(int $syncId, string $campaignIdGoogle): array;
    static function deleteBySyncId(int $syncId): void;
}
```

#### `AssetGroupAsset.php`

```php
class AssetGroupAsset {
    static function createBatch(int $syncId, int $projectId, array $rows): void;
    static function getByAssetGroup(int $syncId, string $assetGroupIdGoogle): array;
    static function getBySyncId(int $syncId): array;
    static function deleteBySyncId(int $syncId): void;
}
```

### 4. Valutazione AI — Prompt unificato con sezione PMax

Il `CampaignEvaluatorService::buildEvaluationPrompt()` viene esteso (non sostituito). Quando il batch contiene campagne PMax, aggiunge una sezione dedicata con dati e istruzioni PMax-specifiche.

#### Dati PMax nel prompt

Per ogni campagna PMax, il prompt include:

```
CAMPAGNA PERFORMANCE MAX: "NL | OCCHIALI DA SOLE > Sales-Performance Max-1"
Tipo: PERFORMANCE_MAX | Budget: €50/giorno | Strategia: Maximize Conversions

METRICHE CAMPAGNA (ultimi 7gg):
Click: 1.200 | Impressioni: 45.000 | CTR: 2.67% | CPC: €0.41
Costo: €492 | Conversioni: 28 | Valore Conv: €2.380 | ROAS: 4.84x

ASSET GROUPS (2):
  1. "Main - Sunglasses" | Ad Strength: GOOD | Status: ENABLED
     Metriche: 900 click, €380 costo, 22 conv, ROAS 5.2x
     Asset: 8 headline (2 BEST, 4 GOOD, 2 LOW), 3 description (1 BEST, 2 GOOD),
            6 immagini (1 BEST, 3 GOOD, 2 LEARNING), 1 video, 1 logo, 1 business_name
     Headline LOW: "Buy Sunglasses Now", "Best Prices Online"
     Audience Signals: customer_match, website_visitors, search_terms: ["sunglasses online", "ray ban"]
     Search Themes: ["buy sunglasses", "designer sunglasses", "ray ban shop"]

  2. "Promo - Summer" | Ad Strength: POOR | Status: ENABLED
     Metriche: 300 click, €112 costo, 6 conv, ROAS 3.1x
     Asset: 3 headline (0 BEST, 1 GOOD, 2 LOW), 2 description (0 BEST, 2 LOW),
            2 immagini (1 GOOD, 1 LOW), 0 video, 1 logo, 1 business_name
     Headline LOW: "Summer Sale", "Cheap Sunglasses"
     ⚠ MANCANO: video (0/1 min), immagini sotto minimo (2/3 min)
     Audience Signals: NESSUNO
     Search Themes: ["summer sale"]
```

#### Istruzioni valutazione PMax nel prompt

```
REGOLE VALUTAZIONE PERFORMANCE MAX:

1. AD STRENGTH: POOR = critico (servono più asset), AVERAGE = warning, GOOD = ok, EXCELLENT = ottimo
   Google riporta +6% conversioni passando da GOOD a EXCELLENT.

2. ASSET COVERAGE (minimi Google obbligatori):
   - Headline: min 3, ideale 15 (max 30 char)
   - Long headline: min 1 (max 90 char)
   - Description: min 2, ideale 5 (max 90 char)
   - Marketing image: min 3, ideale 15+ (1200x628, rapporto 1.91:1)
   - Square image: min 1, ideale 5+ (1200x1200)
   - Logo: min 1 (1200x1200)
   - Video: min 0 ma FORTEMENTE raccomandato (senza video Google ne genera uno automatico di bassa qualità)
   - Business name: 1 obbligatorio (max 25 char)
   Segnala asset sotto il minimo come CRITICAL, sotto l'ideale come WARNING.

3. ASSET PERFORMANCE:
   - LOW = asset sottoperformante, deve essere sostituito. Se >30% degli asset di un tipo è LOW → critico.
   - BEST = asset top performer, può essere replicato in altri asset group.
   - LEARNING = dati insufficienti, normale per asset recenti (<2 settimane).
   - Rapporto BEST:LOW < 1:1 → problema di qualità creativa.

4. AUDIENCE SIGNALS:
   - Nessun audience signal = CRITICAL (PMax parte da zero, spreca budget in learning)
   - Manca customer match/website visitors = WARNING (segnali first-party sono i più potenti)
   - Search themes troppo generici o <5 = WARNING
   - Nessun custom segment con URL competitor = suggerimento

5. CONVERSION READINESS:
   - <30 conversioni/mese per campagna = WARNING (PMax non ha abbastanza dati per ottimizzare)
   - Budget giornaliero < 3x target CPA = WARNING (budget insufficiente per learning)

6. METRICHE PER ASSET GROUP:
   - Asset group con >50% del budget ma <30% delle conversioni = spesa inefficiente
   - Asset group con CPA >2x la media = candidato per pausa/ristrutturazione
   - ROAS per asset group: confronta tra loro, non con benchmark Search

fix_type per issues PMax:
- Asset LOW da sostituire: fix_type = "rewrite_ads" (genera nuovi headline/description)
- Asset mancanti (coverage gap): fix_type = "add_extensions" (genera asset mancanti)
- Keyword negative mancanti: fix_type = "add_negatives"
- Problemi non automatizzabili (audience, budget, strategia): fix_type = null
```

#### Output JSON — struttura

La struttura output è la stessa delle campagne Search, con `asset_group_analysis` al posto di `ad_group_analysis`:

```json
{
  "campaigns": [{
    "campaign_name": "...",
    "campaign_type": "PERFORMANCE_MAX",
    "score": 5.5,
    "type_specific_insights": "...",
    "strengths": ["..."],
    "issues": [
      {"severity": "critical", "area": "audience", "fix_type": null,
       "description": "Nessun audience signal in asset group 'Promo - Summer'",
       "recommendation": "Aggiungere customer match, website visitors e almeno 10 search themes"}
    ],
    "asset_group_analysis": [{
      "asset_group_name": "Promo - Summer",
      "ad_strength": "POOR",
      "issues": [
        {"severity": "high", "area": "assets", "fix_type": "rewrite_ads",
         "description": "2 headline su 3 con performance LOW",
         "recommendation": "Sostituire 'Summer Sale' e 'Cheap Sunglasses' con headline più specifiche"}
      ],
      "strengths": []
    }]
  }],
  "campaign_suggestions": [
    {"fix_type": "add_extensions", "suggestion": "Aggiungere video asset...", "expected_impact": "..."}
  ]
}
```

### 5. View — Adattamento evaluation.php

La view `evaluation.php` mostra già campagne con issues e ad_group_analysis. Per PMax:

- **Stesso layout**: card per campagna → espandi → lista issues + asset group analysis
- **Differenza**: invece di "Gruppi Annunci" mostra "Asset Groups" con ad_strength badge
- **Asset details**: per ogni asset group, mostra distribuzione asset per tipo e performance label (chip colorati: verde=BEST, giallo=GOOD, rosso=LOW, grigio=LEARNING)
- **Bottone "Genera con AI"**: funziona identico — `fix_type=rewrite_ads` genera headline/description per l'asset group specifico (non ad group)

### 6. EvaluationGeneratorService — Adattamento per PMax

`buildCopyPrompt()` adattato: se il contesto include `scope = 'asset_group'`:
- Prompt usa terminologia PMax (asset group, non ad group)
- Genera 15 headline (max 30 char) + 5 description (max 90 char) + 1 long headline (max 90 char) invece del formato RSA
- Output JSON:
```json
{
  "headlines": ["H1", "H2", ..., "H15"],
  "long_headlines": ["LH1"],
  "descriptions": ["D1", "D2", "D3", "D4", "D5"]
}
```

### 7. Cosa NON cambia

- **Campagne Search**: nessuna modifica al flusso esistente
- **CampaignCreatorService**: già supporta PMax, nessuna modifica
- **Keyword Negative**: il flusso esistente funziona anche per PMax (supportato da gennaio 2025)
- **API version**: rimane v18/v20, le query asset_group sono supportate
- **Tabelle esistenti**: nessuna modifica a `ga_campaigns`, `ga_campaign_ad_groups`, `ga_ads`, etc.

---

## File coinvolti

| Azione | File |
|--------|------|
| Modifica | `modules/ads-analyzer/services/CampaignSyncService.php` |
| Modifica | `modules/ads-analyzer/services/CampaignEvaluatorService.php` |
| Modifica | `modules/ads-analyzer/services/EvaluationGeneratorService.php` |
| Modifica | `modules/ads-analyzer/controllers/CampaignController.php` |
| Modifica | `modules/ads-analyzer/views/campaigns/evaluation.php` |
| Nuovo | `modules/ads-analyzer/models/AssetGroup.php` |
| Nuovo | `modules/ads-analyzer/models/AssetGroupAsset.php` |
| Nuovo | `database/migrations/2026_03_13_pmax_asset_groups.sql` |

## Crediti

La valutazione PMax usa la stessa chiamata AI della valutazione Search (prompt unificato). Nessun costo aggiuntivo per l'utente — stessi 7 crediti.
