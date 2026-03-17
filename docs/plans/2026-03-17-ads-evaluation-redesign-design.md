# Ads Analyzer — Evaluation Redesign

> Design document per il redesign completo della pagina evaluation del modulo ads-analyzer.
> Data: 2026-03-17

---

## 1. Contesto e Motivazione

### Problema attuale
L'evaluation attuale (`evaluation.php`, ~1600 righe) ha questi difetti critici:
- **Zero metriche reali**: mostra solo score AI astratti (7.2/10) senza Click, CTR, ROAS, CPA
- **Suggerimenti non actionable**: testo generico, bottoni "Genera con AI" che producono prose non strutturate
- **Landing page rotta**: scrapa max 5 URL, senza contesto ad group/annuncio, mappa per URL non per combinazione
- **No gerarchia ad group → annuncio**: valutazione piatta a livello campagna, ignora la struttura reale
- **Search e PMax trattati uguali**: template di analisi identico per animali completamente diversi
- **Feature duplicata con negative KW**: il report tenta di replicare la sezione search-terms dedicata

### Posizionamento
Ainstein Ads Analyzer NON è un dashboard metriche che compete con Google Ads. È un **consulente AI** che:
1. Mostra le metriche come **contesto** (non come prodotto)
2. Analizza i dati e trova problemi specifici con evidenze
3. Genera soluzioni concrete (headline, description, negative KW)
4. Applica le ottimizzazioni direttamente su Google Ads

### Vincoli di scala
Target: professionista che gestisce max **5 campagne × 5 ad group per progetto** (= max 25 ad group, ~50 annunci, ~15-25 landing uniche). Questo rende fattibile un'unica chiamata AI e scraping di tutte le landing durante l'evaluation.

### Sync dati
Il sync delle metriche da Google Ads API avviene via **cron giornaliero** (non manuale). L'utente apre il report e i dati sono già aggiornati. Il sync manuale resta disponibile come opzione ma non è il flusso principale.

---

## 2. Decisioni di Design

| # | Decisione | Motivazione |
|---|---|---|
| 1 | Metriche annotate dall'AI, non solo mostrate | Altrimenti è Google Ads fatto peggio |
| 2 | Delta % nei KPI (no sparkline — mancano dati giornalieri) | I sync salvano metriche aggregate, non per giorno. Il delta % tra sync basta come indicatore trend |
| 3 | Ogni affermazione AI cita dati specifici | Costruisce fiducia — il professionista verifica |
| 4 | Scraping landing durante evaluation, tutte le URL uniche | Con max 5×5 ad group, max ~15-25 URL — fattibile in 30 sec |
| 5 | Flusso: Analizza → Genera → Preview → Applica | 4 step chiari, fiducia e controllo |
| 6 | Negative KW: riassunto + link a sezione dedicata | No duplicazione feature, UX pulita |
| 7 | Template Search e PMax completamente separati | Struttura, metriche e ottimizzazioni sono diverse |
| 8 | Costo crediti fisso, generazione on-demand | Non spreca crediti se l'utente non applica |

---

## 3. Struttura Pagina Report

La pagina è un flusso verticale (scroll top→bottom). Il professionista legge numeri, interpretazione, poi agisce.

### Sezione 1 — Context Bar (KPI compatti)

**Scopo**: dare il quadro numerico in 5 secondi, senza aprire Google Ads.

```
┌─────────────────────────────────────────────────────────────────────┐
│  Report Campagne                              [7g] [14g] [30g] [PDF]│
│  Periodo: 1-17 Mar 2026 · Sync: oggi 14:30                         │
├──────────┬──────────┬──────────┬──────────┬──────────┬──────────────┤
│  €12.450 │  4.230   │  3,2%    │  134     │  4,2x    │  €8,40      │
│  Spesa   │  Click   │  CTR     │  Conv.   │  ROAS    │  CPA        │
│  ↓-8,2%  │  ↑+12%   │  ↑+0,4%  │  ↓-3%    │  ↓-0,3x  │  ↑+12%     │
│  ▁▂▃▂▄▃▅ │  ▂▃▄▃▅▆▇ │          │  ▃▄▃▂▃▂▂ │          │             │
└──────────┴──────────┴──────────┴──────────┴──────────┴──────────────┘
```

- 6 KPI card compatte con delta % colorato (verde = buono, rosso = cattivo, invertito per CPA/Spesa)
- Delta % calcolato confrontando il sync corrente con il sync precedente (no sparkline — i sync salvano metriche aggregate, non giornaliere)
- Selettore periodo basato sui sync disponibili (ogni sync ha `date_range_start`/`date_range_end`)
- Export PDF

**Dati**: da `ga_campaigns` aggregati per `sync_id`, con confronto sync precedente per delta.

### Sezione 2 — Tabella Campagne (espandibile 3 livelli)

**Scopo**: tutte le metriche come su Google Ads, ma con indicatori AI visivi.

#### Livello 1 — Campagne (visibile di default)

| Campagna | Tipo | Click | CTR | Spesa | Conv. | Val. Conv. | ROAS | CPA | AI |
|---|---|---|---|---|---|---|---|---|---|
| Brand IT | Search | 2.340 | 4,1% | €3.200 | 89 | €16.690 | 5,2x | €6,20 | 🟢 |
| Generic IT | Search | 1.890 | 2,1% | €5.100 | 45 | €15.750 | 3,1x | €11,30 | 🔴 |
| Shopping IT | PMax | 890 | 1,8% | €4.150 | 28 | €19.600 | 4,7x | €14,80 | 🟡 |

- Colonna "AI": pallino colorato (verde/giallo/rosso) basato sullo score AI della campagna
- Click sulla riga → espande livello 2

#### Livello 2 — Ad Group (click per espandere)

```
▼ Generic IT (Search) — Score AI: 5.8/10
  ┌─────────────────────────────────────────────────────────────────┐
  │ "CPA troppo alto. 3 keyword broad match assorbono il 40%       │
  │  del budget (€2.040) senza conversioni." — AI                  │
  └─────────────────────────────────────────────────────────────────┘

  | Ad Group          | Click | CTR  | Spesa  | Conv. | ROAS | CPA    | Landing URL              |
  |-------------------|-------|------|--------|-------|------|--------|--------------------------|
  | Scarpe Running    | 1.200 | 2,8% | €3.100 | 32    | 3,6x | €9,70 | /scarpe-running          |
  | Scarpe Eleganti   |   690 | 1,2% | €2.000 | 13    | 1,8x | €15,40| /scarpe-eleganti         |
```

- AI summary per campagna (commenta i dati, cita numeri specifici)
- Metriche per ad group con landing URL
- Click su ad group → espande livello 3

#### Livello 3 — Annunci + Keyword (dentro ad group)

```
▼ Scarpe Running (2 annunci, 12 keyword)

  Annunci:
  | # | H1                    | H2                  | H3              | CTR  | Click |
  |---|-----------------------|---------------------|-----------------|------|-------|
  | 1 | Scarpe Running Uomo   | Spedizione Gratis   | Saldi -30%      | 3,4% | 820   |
  | 2 | Scarpe Online         | Acquista Ora         | Offerte Speciali| 1,8% | 380   |

  AI: "RSA #2 ha CTR 1,8% vs 3,4% di RSA #1. Le headline sono generiche
       rispetto alle keyword 'scarpe running uomo' (80% del traffico)."

  Landing: /scarpe-running
  AI: "La pagina è pertinente (parla di scarpe running) ma non menziona
       'spedizione gratis' promessa nella H2 di RSA #1. Mismatch parziale."
```

- Tabella annunci con headline 1-3, CTR, Click per annuncio
- AI commenta le differenze tra annunci (quale va bene, quale no, perché)
- Analisi landing nel contesto delle keyword dell'ad group e delle headline degli annunci
- Per PMax: mostra asset group con ad strength, asset inventory, performance labels invece di annunci/keyword

### Sezione 3 — AI Analysis Summary

**Scopo**: riepilogo complessivo con score e raccomandazioni top.

```
┌─────────────────────────────────────────────────────────────────────┐
│ 🤖 Analisi Complessiva                                    7.5/10   │
│                                                                     │
│ Le campagne generano un ROAS medio di 4,2x con €12.450 di spesa.  │
│ Punto di forza: Brand IT (ROAS 5,2x, CPA €6,20).                  │
│ Criticità: Generic IT ha CPA €11,30 — il doppio del Brand.        │
│ 3 keyword broad match sprecano €2.040/mese senza conversioni.     │
│ L'annuncio RSA #2 di "Scarpe Running" ha CTR 47% inferiore a #1.  │
│                                                                     │
│ Raccomandazioni:                                                    │
│ 1. Aggiungere keyword negative in Generic IT (→ vai a Search Terms)│
│ 2. Riscrivere RSA #2 "Scarpe Running" con headline specifiche     │
│ 3. Aggiungere "spedizione gratis" alla landing /scarpe-running     │
└─────────────────────────────────────────────────────────────────────┘
```

- Score complessivo con trend (se auto-eval)
- Summary che cita dati specifici (importi, percentuali, nomi campagne)
- Top 3-5 raccomandazioni ordinate per impatto
- Le raccomandazioni linkano alle ottimizzazioni batch sotto o alla sezione search terms

### Sezione 4 — Ottimizzazioni Batch (Genera + Applica)

**Scopo**: il professionista vede le azioni pronte, genera le soluzioni, le applica.

#### Flusso per ogni ottimizzazione:

```
Stato 1 — SUGGERITA (dopo evaluation)
┌──────────────────────────────────────────────────────────────┐
│ ☐  Alta priorità | Annunci | Generic IT → Scarpe Running    │
│    Riscrivere RSA #2: headline generiche, CTR 1,8% vs 3,4%  │
│    [Genera Ottimizzazione]                                    │
└──────────────────────────────────────────────────────────────┘

Stato 2 — GENERATA (dopo click "Genera")
┌──────────────────────────────────────────────────────────────┐
│ ☑  Alta priorità | Annunci | Generic IT → Scarpe Running    │
│    Riscrivere RSA #2: headline generiche, CTR 1,8% vs 3,4%  │
│                                                               │
│    Preview Before → After:                                    │
│    H1: Scarpe Online → Scarpe Running Uomo | Offerte 2026    │
│    H2: Acquista Ora  → Spedizione Gratis | Reso 30 Giorni    │
│    H3: Offerte       → -30% Saldi Primavera | Solo Online    │
│    D1: Le migliori scarpe → Oltre 200 modelli running uomo.  │
│        Spedizione gratuita sopra €49. Reso facile 30 giorni. │
│                                                               │
│    [Rigenera] [Esporta CSV Ads Editor]                        │
└──────────────────────────────────────────────────────────────┘

Stato 3 — APPLICATA
┌──────────────────────────────────────────────────────────────┐
│ ✅  Applicato | Annunci | Generic IT → Scarpe Running        │
│    Nuovo annuncio RSA creato IN PAUSA — attivare manualmente │
│    Applicato il 17/03/2026 14:45                              │
└──────────────────────────────────────────────────────────────┘
```

#### Tipi di ottimizzazione per Search:

| Tipo | Genera | Preview | Applica |
|---|---|---|---|
| **Riscrittura annunci** | Nuove H1-H3, D1-D2 | Before→After per ogni campo | Crea nuovo RSA in PAUSA nello stesso ad group |
| **Keyword negative** | — | — | Link a sezione Search Terms dedicata |
| **Estensioni mancanti** | Testo estensioni | Lista nuove estensioni | Crea estensioni attive |

#### Tipi di ottimizzazione per PMax:

| Tipo | Genera | Preview | Applica |
|---|---|---|---|
| **Asset testuali LOW** | Nuove headline/description | Vecchio (LOW) → Nuovo | Aggiunge asset, NON rimuove i vecchi (manuale) |
| **Asset mancanti** | Headline/description aggiuntive | Lista nuovi asset | Aggiunge asset all'asset group |

#### Toolbar batch:

```
┌──────────────────────────────────────────────────────────────┐
│ ⚡ 4 Ottimizzazioni  │ 2 generate │ 1 selezionata           │
│                      [Genera Tutte] [Applica Selezionate]    │
└──────────────────────────────────────────────────────────────┘
```

- "Genera Tutte": chiama AI per ogni ottimizzazione non ancora generata (costo: 1 credito per generazione)
- "Applica Selezionate": applica su Google Ads con singola conferma (annunci in pausa, estensioni attive, negative tramite sezione dedicata)

### Sezione 5 — Keyword Negative (Riassunto + Link)

**Scopo**: non duplicare la feature, ma collegare.

```
┌──────────────────────────────────────────────────────────────┐
│ 🔍 Keyword Negative                                          │
│                                                               │
│ Trovate 12 keyword che sprecano €2.040/mese:                 │
│ • 5 alta priorità (€1.450 spreco)                             │
│ • 4 media priorità (€420 spreco)                              │
│ • 3 da valutare (€170 spreco)                                 │
│                                                               │
│ [Vai all'Analisi Search Terms →]                              │
│                                                               │
│ Ultimo aggiornamento: 15/03/2026 — 8 negative già applicate  │
└──────────────────────────────────────────────────────────────┘
```

- Riassunto dello stato keyword negative (se esiste un'analisi search terms)
- Se non esiste: CTA "Lancia Analisi Search Terms" con stima crediti
- Se esistono negative già applicate: mostra conteggio e data
- Link diretto alla sezione dedicata `/ads-analyzer/projects/{id}/search-term-analysis`

---

## 4. Differenze Search vs PMax

### Analisi AI — Search

**Prompt include per ogni ad group:**
- Metriche ad group (Click, CTR, CPC, Cost, Conv, ROAS, CPA)
- Lista keyword con match type, Click, CTR, QS
- Lista annunci RSA con H1-H3, D1-D2, CTR, Click per annuncio
- Landing page content (title, word_count, primi 3000 char) con mapping keyword→landing
- Estensioni attive

**AI valuta:**
- Coerenza keyword → annuncio → landing (triangolo d'oro)
- Performance relativa tra annunci nello stesso ad group
- Keyword che sprecano budget (match broad troppo ampi)
- Landing page pertinenza vs keyword e promesse headline

**Ottimizzazioni possibili:**
- Riscrittura annunci RSA (H1-H3, D1-D2) con preview Before→After
- Link a sezione negative KW
- Suggerimenti landing page (manuali, non applicabili via API)
- Estensioni mancanti

### Analisi AI — PMax

**Prompt include per ogni asset group:**
- Metriche asset group (Click, Impressions, Cost, Conv, Conv Value, ROAS, CPA)
- Ad Strength (POOR/AVERAGE/GOOD/EXCELLENT)
- Asset inventory per tipo con performance label (BEST/GOOD/LOW/LEARNING/UNSPECIFIED)
- Asset testuali LOW elencati per sostituzione
- Asset mancanti per tipo (vs raccomandazioni Google)
- Audience signals e search themes

**AI valuta:**
- Ad Strength e come migliorarlo
- Asset con performance LOW da sostituire
- Gap nell'inventario asset
- Pertinenza search themes vs landing URL
- ROAS e CPA per asset group

**Ottimizzazioni possibili:**
- Nuovi asset testuali (headline/description) per sostituire LOW → preview Vecchio→Nuovo
- Asset aggiuntivi per colmare gap → preview lista nuovi
- Suggerimenti search themes (manuali)
- Negative KW a livello campagna (limitate per PMax)

---

## 5. Schema Dati

### Modifiche alla risposta AI (`ai_response` JSON)

La nuova struttura richiesta all'AI:

```json
{
  "overall_score": 7.5,
  "summary": "Le campagne generano ROAS 4,2x con €12.450 di spesa...",
  "top_recommendations": [
    {
      "text": "Aggiungere keyword negative in Generic IT",
      "type": "negative_keywords",
      "impact": "high",
      "estimated_saving": "€2.040/mese"
    }
  ],
  "campaigns": [
    {
      "campaign_name": "Generic IT",
      "campaign_type": "SEARCH",
      "score": 5.8,
      "metrics_comment": "CPA €11,30 è il doppio di Brand IT...",
      "ad_groups": [
        {
          "ad_group_name": "Scarpe Running",
          "score": 6.2,
          "metrics": {
            "clicks": 1200, "ctr": 2.8, "cost": 3100,
            "conversions": 32, "roas": 3.6, "cpa": 9.70
          },
          "analysis": "CTR sotto benchmark settore (3.5%)...",
          "ads_analysis": [
            {
              "ad_index": 1,
              "headlines": ["Scarpe Running Uomo", "Spedizione Gratis", "Saldi -30%"],
              "ctr": 3.4,
              "assessment": "Buone performance, headline specifiche"
            },
            {
              "ad_index": 2,
              "headlines": ["Scarpe Online", "Acquista Ora", "Offerte Speciali"],
              "ctr": 1.8,
              "assessment": "CTR 47% inferiore a RSA #1. Headline generiche.",
              "needs_rewrite": true
            }
          ],
          "landing_analysis": {
            "url": "/scarpe-running",
            "coherence_score": 6,
            "analysis": "Pagina pertinente ma non menziona spedizione gratis promessa in H2 di RSA #1",
            "suggestions": ["Aggiungere sezione spedizione above the fold"]
          },
          "optimizations": [
            {
              "type": "rewrite_ad",
              "priority": "high",
              "target_ad_index": 2,
              "reason": "RSA #2 CTR 1,8% vs 3,4% di RSA #1",
              "scope": "ad_group"
            }
          ]
        }
      ],
      "asset_group_analysis": null
    },
    {
      "campaign_name": "Shopping IT",
      "campaign_type": "PERFORMANCE_MAX",
      "score": 7.0,
      "metrics_comment": "ROAS 4,7x buono...",
      "ad_groups": null,
      "asset_group_analysis": [
        {
          "asset_group_name": "Prodotti",
          "ad_strength": "GOOD",
          "analysis": "Ad Strength GOOD, 2 headline con performance LOW",
          "low_assets": [
            {"type": "HEADLINE", "text": "Scarpe Online", "performance": "LOW"}
          ],
          "missing_assets": ["DESCRIPTION"],
          "optimizations": [
            {
              "type": "replace_asset",
              "priority": "medium",
              "target_asset_type": "HEADLINE",
              "target_asset_text": "Scarpe Online",
              "reason": "Performance LOW, headline generica"
            }
          ]
        }
      ]
    }
  ],
  "negative_keywords_summary": null
}
```

**NOTA**: `negative_keywords_summary` NON è generato dall'AI. È assemblato dal controller PHP leggendo le tabelle `ga_analyses`/`ga_negative_keywords` esistenti, e passato alla view separatamente dal JSON AI. L'AI non ha accesso allo stato delle analisi search terms.

```php
// Nel controller, DOPO aver salvato ai_response:
$negativesSummary = $this->buildNegativeKeywordsSummary($project['id']);
// Passato alla view come variabile separata, non dentro ai_response
```

### Tabella `ga_campaign_evaluations` — modifiche

Il campo `ai_response` (LONGTEXT) ospita il nuovo JSON. **Breaking change**: il formato JSON è diverso dalle evaluation precedenti.

Nuove colonne:
- `schema_version INT NOT NULL DEFAULT 1` — v1 = formato vecchio, v2 = formato nuovo
- `campaigns_filter` esiste già (JSON, lista campaign_id_google selezionate per l'analisi)

La view controlla `schema_version`: se v1, renderizza con template legacy semplificato; se v2, usa il nuovo template.

**NOTA**: `overall_score` è dentro `ai_response` JSON, non è una colonna dedicata. Va bene così (non serve filtrare/sortare per score).

### Tabella `ga_generated_fixes` — modifiche

Migration SQL (`database/migration-evaluation-redesign.sql`):

```sql
-- Nuova colonna per identificare annuncio specifico
ALTER TABLE ga_generated_fixes ADD COLUMN target_ad_index INT NULL AFTER ad_group_name;

-- Nuovi fix_type per PMax
ALTER TABLE ga_generated_fixes MODIFY COLUMN fix_type VARCHAR(50) NOT NULL;
-- Valori: rewrite_ads, add_negatives, remove_duplicates, add_extensions, replace_asset, add_asset

-- Nuovo scope_level per PMax
ALTER TABLE ga_generated_fixes MODIFY COLUMN scope_level VARCHAR(30) NOT NULL DEFAULT 'campaign';
-- Valori: campaign, ad_group, asset_group

-- Asset group ID per PMax
ALTER TABLE ga_generated_fixes ADD COLUMN asset_group_id_google VARCHAR(50) NULL AFTER ad_group_id_google;
```

Cambio da ENUM a VARCHAR per `fix_type` e `scope_level` per evitare ALTER TABLE a ogni nuovo tipo.

---

## 6. Scraping Landing Pages

### Quando
Durante l'evaluation, PRIMA della chiamata AI. Come oggi ma con scope esteso.

### Cosa scrapa
Tutte le `final_url` uniche da `ga_ads` per il sync corrente. Con max 5 campagne × 5 ad group × ~2 annunci = ~50 annunci, deduplicando per URL = ~15-25 landing uniche.

### Come
- `ScraperService::scrape($url)` — restituisce title, content, word_count, headings
- **Content troncato a 1500 char** (non 3000) per rispettare limiti token AI — vedi sezione Token Budget
- Try/catch per URL — se fallisce, quell'URL è marcata come "non analizzabile" (l'AI analizza comunque con URL + keyword + headline)
- Delay 1 sec tra richieste per evitare rate limit
- 15-25 URL × 2 sec = 30-50 sec di scraping + 30-60 sec chiamata AI = **60-110 sec totali**

### UX durante l'attesa
L'evaluation usa **SSE** (non AJAX lungo) per mostrare progress in tempo reale:
- `scraping` → "Analisi landing page 3/18..."
- `analyzing` → "Analisi AI in corso..."
- `generating_summary` → "Generazione report..."
- `completed` → redirect alla pagina risultati

Questo sostituisce lo spinner muto attuale. Pattern SSE già in uso nel progetto (reference: `seo-tracking/RankCheckController`).

### Mapping nel prompt AI
Per ogni landing URL, il prompt include:
- URL
- Title + word_count + content (primi 3000 char)
- Lista di TUTTI gli ad group e annunci che ci puntano (con le loro keyword)

Questo permette all'AI di valutare la coerenza keyword→annuncio→landing per ogni combinazione.

---

## 7. Flusso Genera + Applica

### Step 1 — Evaluation (automatica)
- Sync dati → scraping landing → chiamata AI → risultato JSON salvato
- Le `optimizations` nel JSON sono SUGGERIMENTI, non hanno ancora contenuto generato
- Costo: 7 crediti (fisso)

### Step 2 — Genera (on-demand, per singola ottimizzazione)
- Click "Genera Ottimizzazione" → POST a `/generate`
- `EvaluationGeneratorService` riceve tipo + contesto (campagna, ad group, annuncio, keyword, landing)
- Genera contenuto specifico (headline/description per riscrittura, asset per PMax)
- Salva in `ga_generated_fixes`
- Costo: 1 credito per generazione

### Step 3 — Preview (automatica dopo genera)
- Mostra Before→After inline
- Per annunci: H1 vecchia barrata → H1 nuova in verde (per ogni campo)
- Per asset PMax: asset LOW → nuovo asset suggerito
- Bottoni: "Rigenera" (nuova generazione), "Esporta CSV Ads Editor"

### Step 4 — Applica (conferma singola)
- Checkbox per selezionare quali ottimizzazioni applicare
- "Applica Selezionate" → singola conferma modale ("Stai per creare 2 annunci in pausa e 3 estensioni attive. Confermi?")
- Esegue via Google Ads API:
  - Annunci RSA: crea nuovo annuncio IN PAUSA nello stesso ad group
  - Estensioni: crea estensioni attive
  - Asset PMax: aggiunge asset (non rimuove i vecchi — quello è manuale)
- Negative KW: redirect a sezione dedicata (no duplicazione)
- Aggiorna stato in `ga_generated_fixes` → `status = 'applied'`

---

## 8. File Coinvolti

### Da riscrivere
| File | Motivo |
|---|---|
| `views/campaigns/evaluation.php` | Riscrittura completa — nuova struttura a sezioni |

### Da modificare
| File | Modifica |
|---|---|
| `services/CampaignEvaluatorService.php` | Nuovo prompt AI con struttura gerarchica, dati per annuncio, landing per ad group |
| `controllers/CampaignController.php` | `evaluate()`: scraping tutte le URL uniche, mapping annuncio→landing nel prompt |
| `services/EvaluationGeneratorService.php` | Supporto `rewrite_ad` con target_ad_index, `replace_asset` per PMax |
| `models/GeneratedFix.php` | Aggiungere colonna `target_ad_index` |

### Invariati
| File | Motivo |
|---|---|
| `controllers/SearchTermAnalysisController.php` | Non modificato — sezione keyword negative resta separata |
| `views/campaigns/search-terms.php` | Non modificato |
| `cron/auto-evaluate.php` | Compatibile — usa lo stesso `CampaignEvaluatorService`, scrive schema_version=2 |
| `models/CampaignEvaluation.php` | Solo aggiunta colonna schema_version nella migration |

### Nuovi
| File | Scopo |
|---|---|
| `views/campaigns/partials/report-kpi-bar.php` | Partial: 6 KPI cards con delta % |
| `views/campaigns/partials/report-campaign-table.php` | Partial: tabella espandibile 3 livelli |
| `views/campaigns/partials/report-ai-summary.php` | Partial: riepilogo AI con score e raccomandazioni |
| `views/campaigns/partials/report-optimizations.php` | Partial: batch ottimizzazioni con genera/preview/applica |
| `views/campaigns/partials/report-negative-summary.php` | Partial: riassunto negative KW con link |
| `database/migration-evaluation-redesign.sql` | ALTER TABLE ga_generated_fixes ADD target_ad_index |

---

## 9. Prompt AI — Struttura

### Per campagne Search

```
Sei un consulente Google Ads esperto. Analizza queste campagne Search.

METRICHE ACCOUNT:
[KPI aggregati: spesa, click, CTR, conv, ROAS, CPA]

PER OGNI CAMPAGNA:
  Campagna: {name} | Tipo: Search | Budget: €{budget}/giorno | Bidding: {strategy}
  Metriche: Click {n} | CTR {n}% | Spesa €{n} | Conv {n} | ROAS {n}x | CPA €{n}

  PER OGNI AD GROUP:
    Ad Group: {name}
    Metriche: Click {n} | CTR {n}% | Spesa €{n} | Conv {n} | ROAS {n}x | CPA €{n}

    Annunci:
    #1: H1: {h1} | H2: {h2} | H3: {h3} | D1: {d1} | D2: {d2} | CTR: {n}% | Click: {n}
    #2: H1: {h1} | H2: {h2} | H3: {h3} | D1: {d1} | D2: {d2} | CTR: {n}% | Click: {n}

    Keyword (top 20 per click):
    [keyword] ({match_type}) — Click: {n} | CTR: {n}% | QS: {n} | CPC: €{n}

    Landing: {url}
    Title: {title} | Word count: {n}
    Contenuto: {primi 1500 char}

KEYWORD NEGATIVE GIÀ APPLICATE: {count} ({ultimo aggiornamento})

ISTRUZIONI:
- Rispondi in italiano
- Cita SEMPRE dati specifici (importi, %, nomi) a supporto delle affermazioni
- Per ogni ad group con 2+ annunci, confronta le performance e identifica il debole
- Valuta coerenza keyword → headline annuncio → contenuto landing
- Identifica keyword che sprecano budget (alto costo, zero o poche conversioni)
- Rispondi in JSON con la struttura fornita
```

### Per campagne PMax

```
[Stessa intestazione]

PER OGNI CAMPAGNA PMAX:
  Campagna: {name} | Tipo: Performance Max | Budget: €{budget}/giorno
  Metriche: Click {n} | Impressions {n} | Spesa €{n} | Conv {n} | Val. Conv €{n} | ROAS {n}x | CPA €{n}

  PER OGNI ASSET GROUP:
    Asset Group: {name} | Ad Strength: {POOR/AVERAGE/GOOD/EXCELLENT}
    Metriche: Click {n} | Spesa €{n} | Conv {n} | ROAS {n}x | CPA €{n}

    Asset Inventory:
    HEADLINE: {count} (BEST: {n}, GOOD: {n}, LOW: {n}, LEARNING: {n})
    DESCRIPTION: {count} (...)

    Asset LOW (da sostituire):
    HEADLINE: "{text}" — Performance: LOW

    Asset mancanti: {list}

    Audience Signals: {json}
    Search Themes: {list}

    Landing URL: {url}

ISTRUZIONI:
- Per PMax usa asset_group_analysis (non ad_groups)
- Valuta Ad Strength e suggerisci come migliorarlo
- Identifica asset LOW da sostituire con proposte concrete
- Valuta search themes vs landing page
```

---

## 10. Token Budget AI

### Stima prompt (caso peggiore: 5 campagne × 5 ad group × 2 annunci)

| Sezione | Stima token |
|---|---|
| Metriche account + istruzioni + JSON schema | ~2.000 |
| 5 campagne × metriche + budget | ~500 |
| 25 ad group × metriche | ~1.500 |
| 50 annunci × H1-H3, D1-D2, CTR | ~3.000 |
| 25 ad group × 20 keyword top (500 keyword) | ~4.000 |
| 15-25 landing × 1500 char contenuto | ~10.000-15.000 |
| **Totale prompt** | **~21.000-26.000** |
| **Risposta richiesta** (max_tokens) | **8.192** |
| **Totale** | **~30.000-34.000** |

Con Claude Sonnet (200K context) questo è ampiamente dentro i limiti. Con Haiku (200K) pure.

### Ottimizzazioni se serve ridurre

1. **Landing content**: già troncato a 1500 char (da 3000 nel design originale)
2. **Keyword**: top 15 per click invece di 20 (risparmio ~1.000 token)
3. **Dedup landing**: se 3 annunci puntano alla stessa URL, il contenuto appare una volta con mapping "usata da: annuncio #1 (AG Scarpe Running), annuncio #3 (AG Scarpe Trail)"

### Modello AI

Usare il modello configurato in module.json (`ai_provider`/`ai_model`). Default: Claude Sonnet. L'evaluation è un'operazione complessa — Sonnet è il minimo raccomandato.

---

## 11. Filtro Campagne per Analisi

L'utente può **selezionare su quali campagne far fare l'analisi AI** prima di lanciare l'evaluation.

### UI: step pre-evaluation

Prima di cliccare "Valuta con AI", l'utente vede la lista campagne sincronizzate con checkbox:

```
┌──────────────────────────────────────────────────────────────┐
│ Seleziona campagne da analizzare                             │
│                                                               │
│ ☑ Brand IT (Search) — 2.340 click, €3.200, ROAS 5,2x        │
│ ☑ Generic IT (Search) — 1.890 click, €5.100, ROAS 3,1x      │
│ ☐ Shopping IT (PMax) — 890 click, €4.150, ROAS 4,7x         │
│ ☑ Retargeting (Display) — 450 click, €800, ROAS 6,1x        │
│                                                               │
│ 3 campagne selezionate · Costo: 7 crediti                    │
│ [Avvia Analisi AI]                                            │
└──────────────────────────────────────────────────────────────┘
```

### Backend

- Il campo `campaigns_filter` (JSON, già esistente in `ga_campaign_evaluations`) salva la lista di `campaign_id_google` selezionati
- Il controller filtra i dati del sync per includere solo le campagne selezionate
- Il prompt AI riceve solo le campagne selezionate
- Default: tutte le campagne ENABLED selezionate
- **Filtro ENABLED obbligatorio**: solo campagne, ad group, annunci e keyword con status ENABLED entrano nel prompt AI e nella tabella metriche. Tutto ciò che è in pausa o rimosso viene ignorato (il sync li salva per completezza ma l'evaluation li esclude)

### Vantaggi

- Risparmi token AI se vuoi analizzare solo 2 campagne su 5
- Focus sull'analisi: se una campagna è in pausa o irrilevante, la escludi
- Riduce il tempo di scraping landing (meno ad group → meno URL)

---

## 12. Costi Crediti

Da `module.json` attuale:

| Operazione | Costo (module.json key) | Note |
|---|---|---|
| Evaluation (analisi AI) | 10 crediti (`cost_campaign_evaluation`) | Già configurato |
| Genera singola ottimizzazione | 1 credito (`cost_generate_fix` — **da aggiungere**) | On-demand |
| "Genera Tutte" | 1 × N ottimizzazioni | Conferma modale: "Generare 8 ottimizzazioni? Costo: 8 crediti" |
| Applica su Google Ads | 0 crediti | Gratuita |

**Settings da aggiungere a module.json** (gruppo `costs`):
- `cost_generate_fix`: 1 credito, "Costo generazione singola ottimizzazione"

**Settings da aggiungere a module.json** (gruppo `general`):
- `max_landing_pages_per_eval`: 25, min 5, max 50, "Max landing pages da scrapare per evaluation"

**Settings esistenti già allineati**:
- `max_campaigns_per_evaluation`: 15 (default), già funzionante nel controller
- `auto_eval_significance_threshold`: 10%, già usato nel cron
- `auto_eval_delay_minutes`: 2, già usato nel cron
- `gads_auto_sync_enabled` + `gads_sync_frequency_hours`: sync automatico già configurabile

**Cap "Genera Tutte"**: il bottone mostra il costo totale prima dell'esecuzione. L'utente conferma esplicitamente.

---

## 12b. Feature Esistenti da Riusare (non ricostruire)

Il backend è quasi tutto pronto. Il lavoro principale è sulla **view** e su wiring mancanti.

| Feature | Stato | Azione |
|---|---|---|
| AI prompt con ad group + ads + keywords | ESISTE | Estendere per annunci con CTR individuale e landing per ad group |
| Scraping landing (ScraperService) | ESISTE | Alzare limite da 5 a `max_landing_pages_per_eval`, troncare content a 1500 char |
| Genera con AI (4 fix types) | ESISTE | Aggiungere `replace_asset` e `add_asset` per PMax |
| Applica su Google Ads (API) | ESISTE | Nessuna modifica — già supporta copy/extensions/negatives |
| CSV Ads Editor export | ESISTE | Solo aggiungere bottone nella view (service già pronto) |
| PMax analysis separata | ESISTE | Nessuna modifica backend |
| Campaign filter (backend) | ESISTE | Solo aggiungere UI checkbox pre-evaluation |
| KPI cards + delta % (view) | PARZIALE | Wiring controller → view (popolare `$metricDeltas`) |
| Period selector (view) | PARZIALE | Wiring controller → view (popolare `$periods`) |
| Tabella annunci (H1-H3, CTR) | ESISTE | Riusare da show.php nel nuovo template |
| **Before→After preview** | **NUOVO** | Componente UI da creare |
| **Batch operations** | **NUOVO** | Checkbox + selezione multipla + applica batch |
| **Ad group espandibili** | **NUOVO** | Livello intermedio nella tabella |
| **SSE progress** | **NUOVO** | Convertire da AJAX lungo a SSE stream |

---

## 13. Export CSV Google Ads Editor

Quando un'ottimizzazione "rewrite_ad" è stata generata, il bottone "Esporta CSV Ads Editor" produce un file con le colonne standard Google Ads Editor:

```csv
Campaign,Ad group,Headline 1,Headline 2,Headline 3,Description 1,Description 2,Final URL,Status
Generic IT,Scarpe Running,Scarpe Running Uomo | Offerte 2026,Spedizione Gratis | Reso 30 Giorni,-30% Saldi Primavera,Oltre 200 modelli running uomo. Spedizione gratuita.,Reso facile. Scegli tra le migliori marche.,https://example.com/scarpe-running,Paused
```

Per estensioni: colonne `Campaign`, `Extension type`, `Extension text`.
Per negative KW: redirect alla sezione dedicata (no CSV qui).

---

## 14. Non in Scope (decisioni esplicite)

| Cosa | Perché esclusa |
|---|---|
| Grafico trend Chart.js / sparkline | Mancano dati giornalieri (sync aggregato). Delta % basta. |
| Replica sezione keyword negative nel report | Duplicazione feature, UX confusa |
| Quality Score nell'UI | Troppo tecnico per target utente |
| Confronto tra evaluation diverse | Complessità alta, valore basso — il delta % basta |
| Export CSV della tabella campagne | Google Ads lo fa meglio, non è il nostro valore |
| Modifica annunci esistenti via API | Troppo rischioso — creiamo nuovi in pausa |
| Rimozione asset LOW PMax via API | Manuale — solo aggiunta nuovi asset |
| Dati giornalieri in sync | Richiederebbe nuova tabella + modifica sync service. Fuori scope v1 |
