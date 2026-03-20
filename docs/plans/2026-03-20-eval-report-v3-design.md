# Evaluation Report v3 — Design Spec

> Data: 2026-03-20 · Mockup: `public/mockup-eval-v3.html`

## Obiettivo

Redesign completo del report evaluation ads-analyzer per supportare 3 tipi di campagna (Search, PMax, Shopping) con layout accordion, dati reali da sync, e bottoni AI generativi con spiegazione inline.

## Target Utente

Professionista Google Ads che analizza 3-5 campagne miste per progetto. Vuole: (1) overview rapido con AI score, (2) drill-down per campagna, (3) azioni concrete con un click.

---

## Stati Pagina (carry-over da v2)

La view gestisce 4 stati mutuamente esclusivi, mantenuti invariati da v2:

| Stato | Condizione | UI |
|-------|-----------|-----|
| **ERROR** | `status === 'error'` | Box rosso con messaggio errore + bottone "Torna alle campagne" |
| **ANALYZING** | `status === 'analyzing'` | Spinner animato + auto-refresh ogni 5s via JS |
| **NO CHANGE** | `status === 'completed'` e `credits_used == 0` | Box grigio "Nessuna variazione rilevata" |
| **RESULTS** | `hasResults` (completed + credits > 0) | Layout completo descritto sotto |

Il layout accordion si applica SOLO allo stato RESULTS.

---

## Struttura Pagina (dall'alto in basso)

### 1. Header
- Breadcrumb + nome progetto + badge "CAMPAGNE"
- Periodo date, sync date, conteggio campagne
- Bottoni: PDF export, Indietro

### 2. KPI Bar (6 metriche aggregate)
- Click, Spesa, Conversioni, CTR, ROAS, CPA
- Delta % rispetto al periodo precedente (verde/rosso)
- Calcolati da `$syncMetrics['totals']`

### 3. Analisi Complessiva AI (IN ALTO, prima degli accordion)
- **NOTA IMPLEMENTATIVA**: In v2 questa sezione e in fondo alla pagina (dopo le campagne). In v3 va SPOSTATA qui, prima degli accordion. Questo e un cambio strutturale deliberato nel render order del PHP.
- Score cerchio grande (0-10, colore: <5 rosso, 5-7 amber, >7 verde)
- Summary testuale AI (cita dati specifici per ogni tipo campagna)
- 3 Raccomandazioni Principali in card grid 3 colonne (titolo + dettaglio)
- Dati da: `$aiResponse['overall_score']`, `$aiResponse['summary']`, `$aiResponse['top_recommendations']`

### 4. Filtri + Ordinamento
- Filtro tipo: Tutte | PMax (N) | Search (N) | Shopping (N) — toggle buttons
  - Bottoni per tipi con 0 campagne: **nascosti** (non mostrati, non disabilitati)
  - Se filtro attivo produce 0 risultati: mostrare empty state con `View::partial('components/table-empty-state', [...])`
- Ordinamento: Spesa ↓ | Score AI ↓ | ROAS ↓ | Nome A-Z — select dropdown
- Filtro e ordinamento via Alpine.js client-side (dati gia caricati)

### 5. Accordion Campagne

Ogni campagna e un accordion cliccabile. Header sempre visibile mostra:
- Score AI cerchio (0-10, colorato)
- Badge tipo (PMax viola, Search blu, Shopping amber — **NOTA**: Shopping passa da emerald ad amber, aggiornare `$campaignTypeConfig` in evaluation-v2.php e report-campaign-table.php)
- Nome campagna (troncato)
- Metriche inline: click, spesa, conv, ROAS, CPA
- Conteggio sotto-livelli: "N asset group" (PMax) o "N ad group · N annunci" (Search)
- Alert badge se problemi AI (es. "⚠ 2 problemi")
- Chevron expand/collapse

Ordine default: raggruppati per tipo (PMax prima, poi Search, poi Shopping), poi per spesa decrescente dentro ogni tipo.

---

## Contenuto Accordion per Tipo

### PMax (PERFORMANCE_MAX)

**Commento AI campagna** — box con analisi metriche + problemi principali

**Sub-accordion per Asset Group** (espandibile):
- Header: Ad Strength badge (POOR/AVERAGE/GOOD/EXCELLENT) + nome + metriche (click, CTR, spesa, conv, ROAS)
- Expanded — grid 2 colonne:

  **Colonna sinistra: Inventario Asset**
  - Per ogni field_type (Headline, Long Headline, Descrizione, Immagine, Imm. Quadrata, Logo, Video):
    - Label tipo
    - Conteggio attuale/raccomandato (es. "4/15")
    - Mini-barra stacked (flex row): ogni segmento e un div con `rounded-full h-1.5` e width proporzionale al conteggio. Denominatore = totale asset di quel field_type nel gruppo. Colori: BEST `bg-emerald-500`, GOOD `bg-blue-500`, LOW `bg-red-500`, LEARNING `bg-amber-500`, UNSPECIFIED non mostrato. Dati da `AssetGroupAsset::getPerformanceSummary($syncId, $agIdGoogle)`.
    - Conteggio per label (es. "3 BEST · 1 GOOD")
  - Box "Asset Mancanti" con alert amber se sotto minimo
  - Bottone: "✨ Genera Asset Mancanti · 1 cr — L'AI crea headline e description ottimizzate basandosi sui prodotti e search themes dell'asset group"
    - `fix_type: add_asset`

  **Colonna destra: Analisi AI**
  - Commento AI per l'asset group
  - Search Themes (tag pills da `ga_asset_groups.search_themes` JSON)
  - Audience Signals (lista da `ga_asset_groups.audience_signals` JSON, oppure warning "nessun signal configurato")

  **Sezione sotto: Asset LOW** (se presenti)
  - Lista asset con performance_label = LOW
  - Per ogni: badge tipo, contenuto (testo o URL troncato), bottone:
    - Per asset testuali (HEADLINE, DESCRIPTION): "✨ Genera Sostituzione · 1 cr — L'AI riscrive il testo mantenendo coerenza con gli altri asset"
      - `fix_type: replace_asset`
    - Per asset media (LOGO, IMAGE, VIDEO): "✨ Genera Brief · L'AI analizza e suggerisce specifiche per la sostituzione"
      - Nessun credito (solo testo suggerimento, non genera media)

**Estensioni Campagna** (card in fondo all'accordion PMax):
- Score estensioni + badge tipo con conteggio e impression
- Alert se tutte a 0 impression
- Bottone: "✨ Genera Estensioni Ottimizzate · 1 cr — Crea sitelink, callout e snippet per il mercato target"
  - `fix_type: add_extensions`

### Search (SEARCH)

**Commento AI campagna** — analisi metriche + suggerimenti principali

**Per ogni Ad Group** (card, non sub-accordion — tipicamente 1-3):
- Header: nome ad group + metriche

- **Grid 2 colonne:**

  **Colonna sinistra: Annunci**
  - Tabella per ogni annuncio RSA: H1, H2, H3, D1, CTR, Click
  - Evidenziazione riga con CTR piu basso
  - Commento AI sotto
  - Bottone: "✨ Riscrivi Annuncio · 1 cr — Genera 5 headline + 3 description alternative per A/B testing"
    - `fix_type: rewrite_ad`

  **Colonna destra: Landing Page**
  - URL cliccabile
  - Badge coerenza (0-10)
  - Analisi testuale
  - Suggerimenti (checkmark verdi + warning ambra)
  - Dati da scraping via ScraperService (durante evaluate)

- **Top Keywords** (max 5):
  - Keyword text, match type badge, click, CTR
  - Evidenziazione keyword "waste" (0 conv + alto costo)

**Estensioni Campagna** (in fondo):
- Stessa struttura PMax

### Shopping (SHOPPING)

**Commento AI campagna** — analisi metriche + focus su prodotti

**Product Performance** (card principale):
- **Grid 2 colonne:**

  **Colonna sinistra: Top Brand per ROAS**
  - Barre orizzontali: nome brand, barra proporzionale alla spesa, ROAS numerico
  - Colore: verde ≥4x, blu ≥2x, rosso <2x
  - Dati da `ga_product_performance` aggregati per brand **filtrati per campaign_id_google** (non aggregate globali). Se un progetto ha 2 Shopping, ogni accordion mostra solo i propri prodotti. Richiede aggiornare `ProductPerformance::getBrandSummary()` e `getWasteProducts()` per accettare `$campaignIdGoogle` opzionale.

  **Colonna destra: Prodotti Spreco**
  - Lista prodotti con 0 conversioni ma costo > 0
  - Per ogni: nome prodotto, click, costo
  - Totale spreco stimato

- **Opportunita AI** (sotto):
  - Card con suggerimenti (es. "Aumentare bid Ray-Ban", "Escludere Prada")
  - Dati da `$aiResponse['product_analysis']['opportunities']`

**Estensioni Campagna** (in fondo):
- Stessa struttura PMax/Search

---

## Prerequisiti Sync (da fixare PRIMA del redesign view)

### P1. Search Themes + Audience Signals (ga_asset_groups)
- **Problema**: colonne JSON esistono ma sono TUTTE NULL
- **Fix**: `CampaignSyncService::syncAudienceSignals()` — verificare che viene chiamato dopo syncAssetGroups, fixare GAQL query
- **GAQL**: `SELECT asset_group_signal.audience, asset_group_signal.search_theme FROM asset_group_signal WHERE campaign.id = {$campaignId}`
- **Verifica**: almeno 1 asset group con search_themes non-null dopo sync

### P2. Product Performance (ga_product_performance)
- **Problema**: tabella con schema completo ma 0 righe
- **Fix**: `CampaignSyncService::syncProductPerformance()` — verificare che viene chiamato per campagne Shopping/PMax
- **GAQL**: `SELECT segments.product_item_id, segments.product_title, segments.product_brand, segments.product_bidding_category_level1, metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions, metrics.conversions_value FROM shopping_performance_view WHERE campaign.id = {$campaignId} AND segments.date DURING LAST_30_DAYS ORDER BY metrics.cost_micros DESC LIMIT 200`
- **Verifica**: righe popolate con product_title, brand, roas calcolato

### P3. Extension Metrics (ga_extensions)
- **Problema**: 1515 estensioni ma clicks=0 e impressions=0 su tutte
- **Fix**: aggiungere `metrics.impressions, metrics.clicks` alla GAQL query per campaign_asset
- **Verifica**: almeno alcune estensioni con impressions > 0 dopo sync

### P4. Performance Label sanitization (gia fixato)
- **Fix applicato**: `CampaignSyncService` riga 1061 — valori non ENUM mappati a UNSPECIFIED
- **Verifica**: nessun SQL warning "Data truncated" dopo sync

---

## Bottoni "Genera" — Mappa Completa

| Contesto | Bottone | fix_type | Costo | Cosa fa |
|----------|---------|----------|-------|---------|
| PMax: asset mancanti | "Genera Asset Mancanti" | `add_asset` | 1 cr | Genera headline/description ottimizzate per i prodotti e search themes |
| PMax: asset LOW testuali | "Genera Sostituzione" | `replace_asset` | 1 cr | Riscrive il testo mantenendo coerenza con altri asset del gruppo |
| PMax: asset LOW media | "Genera Brief" | nessun fix_type | 0 cr | Analizza e suggerisce specifiche per sostituzione (brief creativo) |
| Search: annuncio | "Riscrivi Annuncio" | `rewrite_ad` | 1 cr | Genera 5 headline + 3 description alternative per A/B test. Nota: `rewrite_ads` (plurale) e alias legacy accettato dal service ma il frontend usa sempre `rewrite_ad` (singolare). |
| Tutte: estensioni | "Genera Estensioni" | `add_extensions` | 1 cr | Crea sitelink, callout e snippet per il mercato target |

Ogni bottone ha sotto-testo che spiega l'azione in linguaggio semplice.

---

## Dati Non Disponibili (design graceful)

| Dato | Se mancante | UI |
|------|-------------|-----|
| Search themes | NULL in DB | Mostra "Nessun search theme configurato" in testo grigio |
| Audience signals | NULL in DB | Mostra "⚠ Nessun audience signal — Google non puo ottimizzare targeting" in rosso |
| Product performance | 0 righe | Mostra "Dati prodotto non disponibili — sincronizza per caricarli" con link sync |
| Landing page (PMax) | Non disponibile da API | Sezione non mostrata (by design) |
| Extension metrics | Tutti 0 | Mostra "0 impr" come dato reale (potrebbe essere approvazione) con warning |

---

## File da Modificare/Creare

| File | Azione | Note |
|------|--------|------|
| `services/CampaignSyncService.php` | Modificare | Fix sync search themes, product perf, extension metrics |
| `views/campaigns/evaluation-v2.php` | Riscrivere | Layout accordion, AI in alto, filtri |
| `views/campaigns/partials/report-campaign-table.php` | Riscrivere | Accordion innestati, 3 template per tipo |
| `controllers/CampaignController.php` | Modificare | Passare dati aggiuntivi alla view (product data, search themes) |
| `services/EvaluationGeneratorService.php` | Modificare | Migliorare prompt con context asset group per PMax |
| `models/AssetGroupAsset.php` | Modificare | `getPerformanceSummary()` esiste. **Creare** `getLowAssets(int $syncId, string $assetGroupIdGoogle): array` — ritorna asset con performance_label=LOW |
| `models/ProductPerformance.php` | Modificare | Aggiungere parametro `$campaignIdGoogle` opzionale a `getBrandSummary()` e `getWasteProducts()` |

---

## Non in Scope (v3)

- Dashboard table filtering/sorting (issue separato, segnalato dall'utente)
- "Applica su Google Ads" per PMax (limitazione API, solo brief)
- Before→After preview per ottimizzazioni batch
- SSE progress evaluation (AJAX lungo funziona)
- Schema ga_ads 15 headline (accettabile con 3 per v3)
