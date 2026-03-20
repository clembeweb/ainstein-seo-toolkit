# Ads Analyzer — Dashboard UX Redesign + KW Negative Integration

> Data: 2026-03-20
> Modulo: ads-analyzer (ga_ prefix)
> Stato: Design approvato, pronto per implementazione

---

## Problema

### 1. Dashboard: tabella unica = caos informativo
La tabella "Performance Campagne" in `dashboard.php` (righe 324-533) mischia Search, PMax, Shopping, Display, Video in un unico elenco espandibile a 3 livelli. Problemi:

- **Metriche non comparabili**: CTR di Search vs PMax non significano la stessa cosa
- **Gerarchia diversa**: Search ha Ad Group → Ads, PMax ha Asset Groups con Ad Strength — forzarli nella stessa struttura espandibile e innaturale
- **Scroll infinito**: account con 15+ campagne genera 60+ righe espanse senza filtri
- **Zero filtri**: nessun modo di cercare, ordinare, filtrare per status/tipo/performance

### 2. Tab "Keyword Negative" fuori contesto
- L'advertiser non pensa "faccio keyword negative" — pensa "analizzo la campagna Search X e nego i termini spreco"
- La tab e un'isola senza contesto sulla campagna corrente
- Visibile anche per PMax/Shopping/Display dove non serve

### 3. Evaluation appiattita
Il report evaluation (`report-campaign-table.php`) e un documento lineare di 800+ righe. Troppo lungo per account con 5+ campagne miste.

---

## Soluzione

### A) Dashboard — Tabelle separate per tipo, grid 2 colonne

**Layout**: `grid lg:grid-cols-2 gap-6` — ogni tipo campagna ha la sua card con colonne specifiche.

```
┌─────────────────────────────────┬─────────────────────────────────┐
│  🔍 Campagne Search (4)         │  ⚡ Campagne PMax (2)           │
│  ┌─────────────────────────┐   │  ┌─────────────────────────┐   │
│  │ Nome   Click  CTR  CPA  │   │  │ Nome  Strength Conv ROAS│   │
│  │ Brand  1.2K  8.2% €12   │   │  │ Main  GOOD    45  3.2x │   │
│  │ Generic 800  3.1% €28   │   │  │ Retgt AVERAGE 12  2.1x │   │
│  └─────────────────────────┘   │  └─────────────────────────┘   │
│  ⚠ 3 ad group con CTR < 1%    │  ⚠ 1 asset group POOR          │
├─────────────────────────────────┼─────────────────────────────────┤
│  🛍 Campagne Shopping (1)      │  📊 Spreco Budget               │
│  (se presenti)                  │  (widget gia esistente,         │
│                                 │   spostato nella griglia)       │
├─────────────────────────────────┼─────────────────────────────────┤
│  📈 Andamento KPI              │  🤖 Ultima Valutazione AI       │
│  (chart trend gia esistente)   │  (card gia esistente)           │
└─────────────────────────────────┴─────────────────────────────────┘
```

**Colonne specifiche per tipo:**

| Tipo | Colonne tabella |
|------|----------------|
| Search | Nome, Status dot, Click, CTR, Costo, CPA |
| PMax | Nome, Ad Strength badge, Click, Conv, ROAS |
| Shopping | Nome, Click, Conv, ROAS, Costo |
| Display | Nome, Impressioni, Click, CTR, Costo |
| Video | Nome, Views, Click, Costo |

**Regole:**
- Max 5-6 righe per tabella (top per spesa), link "Vedi tutte →" per pagina Campagne filtrata
- **NO expand** — la dashboard e panoramica, il drill-down sta nella evaluation
- Sotto ogni tabella: alert contestualizzato inline (es. "3 ad group CTR < 1%", "1 asset group POOR")
- Tipi con 0 campagne: non mostrati
- Se c'e un solo tipo: occupa full width (no grid 2 col forzato)
- Card vuote per bilanciare il grid non servono — il browser gestisce il flow

**Widget esistenti riposizionati nel grid:**
- "Spreco Budget" → cella del grid (era gia in grid 2 col, resta li)
- "Andamento KPI" (chart) → cella del grid
- "Ultima Valutazione AI" → cella del grid
- KPI bar (6 card) → rimane SOPRA il grid, invariata

### B) Rimozione tab "Keyword Negative"

**Navigazione da 5 tab → 4 tab:**
```
Prima:  Dashboard | Connessione | Campagne | Keyword Negative | Impostazioni
Dopo:   Dashboard | Connessione | Campagne | Impostazioni
```

**File modificati:**
- `views/partials/project-nav.php` → rimuovere entry `search-term-analysis`

**Route**: le route per `search-term-analysis` rimangono funzionanti (backward compat) ma non c'e piu link nella nav. L'accesso e solo via widget dashboard o evaluation.

### C) KW Negative integrato nell'Evaluation Search

Nella view `evaluation-v2.php`, la sezione "Negative KW Summary" attuale (righe 385-455) viene **potenziata** per le sole campagne Search:

**Stato attuale**: box statico con badge priorita + link a pagina separata.

**Nuovo stato**: sezione interattiva inline con:

1. **Header** con stats (spreco totale, termini trovati)
2. **Bottone "Analizza Termini"** (1 credito) — chiama l'endpoint esistente `SearchTermAnalysisController::analyze()`
3. **Risultati inline** organizzati per categoria/priorita:
   - Categorie espandibili (alta/media/da valutare)
   - Checkbox per selezione keyword
   - Match type badge (exact/phrase/broad)
4. **Azioni**:
   - "Seleziona tutto" / "Inverti selezione"
   - "Applica su Google Ads" (riusa logica `applyNegativeKeywords()`)
   - "Copia" / "Esporta CSV"

**Condizione di visibilita**: la sezione appare SOLO se `$evaluation` contiene campagne di tipo SEARCH. Per evaluation solo PMax/Shopping, non viene mostrata.

**Backend**: nessun nuovo endpoint necessario — riusa i 6 endpoint esistenti di `SearchTermAnalysisController`. L'unica differenza e che il frontend li chiama da dentro la view evaluation anziche dalla pagina dedicata.

### D) Filtro tipo nella pagina Campagne (bonus, bassa priorita)

Aggiungere pill filter nella pagina Campagne:
```
[Tutte] [Search (4)] [PMax (2)] [Shopping (1)]
```

Filtro client-side con Alpine.js (no reload). Nasconde/mostra righe per `campaign_type`.

---

## File impattati

| File | Modifica |
|------|----------|
| `views/campaigns/dashboard.php` | Sostituire tabella unica con grid di tabelle per tipo |
| `views/partials/project-nav.php` | Rimuovere tab "Keyword Negative" |
| `views/campaigns/evaluation-v2.php` | Potenziare sezione KW negative inline |
| `controllers/CampaignController.php` | Adattare `dashboard()` per raggruppare campagne per tipo |
| `views/campaigns/search-terms.php` | Mantenerlo (backward compat) ma non piu linkato dalla nav |

**File NON toccati:**
- `SearchTermAnalysisController.php` — gli endpoint restano identici
- `services/KeywordAnalyzerService.php` — logica AI invariata
- `models/NegativeKeyword.php`, `NegativeCategory.php` — CRUD invariato
- Tabelle DB — nessuna migrazione necessaria
- `routes.php` — route restano (backward compat)

---

## Dati per il controller dashboard

Il metodo `CampaignController::dashboard()` attualmente passa `$campaignsPerformance` come array flat. Deve essere modificato per passare:

```php
$campaignsByType = [
    'SEARCH' => [...campagne search...],
    'PERFORMANCE_MAX' => [...campagne pmax...],
    'SHOPPING' => [...campagne shopping...],
    // altri tipi solo se presenti
];
```

Logica: `array_group_by` su `$campaignsPerformance` per campo `type`. Nessuna query aggiuntiva — stessi dati, solo raggruppamento diverso.

---

## Rischi e mitigazioni

| Rischio | Mitigazione |
|---------|------------|
| Account con solo 1 tipo → grid sbilanciato | Se un solo tipo: full width. Se 2: grid 2 col. Se 3+: grid 2 col con flow naturale |
| KW negative inline nell'evaluation → view troppo lunga | La sezione e collassabile (default chiusa se nessuna analisi) |
| Utenti bookmarkano `/search-term-analysis` | Route mantenuta, funziona ancora, solo non in nav |
| Performance: tante campagne | Max 5-6 per tabella in dashboard, paginazione nella pagina Campagne |

---

## Alert contestualizzati per tipo campagna

Ogni tabella per tipo mostra un alert inline sotto le righe, calcolato nel controller e passato alla view come array `$alertsByType`.

| Tipo | Condizione | Testo |
|------|-----------|-------|
| Search | Ad group con CTR < 1% e click > 0 | "N ad group con CTR sotto 1%" |
| Search | Keyword con costo > 10% del costo ad group e 0 conversioni | "N keyword a spreco" |
| PMax | Asset group con ad_strength = POOR | "N asset group con Ad Strength POOR" |
| PMax | Asset group sotto il minimo asset richiesti | "N asset group con asset mancanti" |
| Shopping | Campagna con ROAS < 2 e costo > 0 | "N campagne con ROAS sotto 2x" |
| Display/Video | CTR < 0.5% | "CTR molto basso" |

**Thresholds hardcoded** nel controller (non configurabili). Sono euristiche ragionevoli per un primo rilascio. Se servira, potranno diventare setting nel `module.json` in futuro.

---

## Endpoint usati dalla sezione KW Negative inline

La sezione KW Negative nell'evaluation-v2 riusa questi endpoint esistenti:

| Azione utente | Endpoint | Metodo |
|---------------|----------|--------|
| Carica dati search terms | `/search-term-analysis/sync-data` | GET |
| Lancia analisi AI | `/search-term-analysis/analyze` | POST |
| Carica risultati analisi | `/search-term-analysis/results` | GET |
| Toggle singola keyword | `/search-term-analysis/keywords/{id}/toggle` | POST |
| Toggle categoria intera | `/search-term-analysis/categories/{id}/{action}` | POST |
| Applica su Google Ads | `/search-term-analysis/apply-negatives` | POST |
| Copia testo | `/search-term-analysis/copy-text` | POST |
| Esporta CSV | `/search-term-analysis/export` | GET |

**Non usati inline**: `detect-urls` e `extract-contexts` (scraping landing page) — queste operazioni lunghe restano nella pagina dedicata `/search-term-analysis` (accessibile dal link "Analisi avanzata" nella sezione inline).

---

## Visibilita sezione KW Negative nell'evaluation

La sezione si mostra se **almeno una campagna nell'evaluation e di tipo SEARCH**. Due stati:

1. **Nessuna analisi precedente**: mostra solo header + bottone "Analizza Termini (1 credito)" + testo esplicativo
2. **Analisi completata**: mostra risultati completi con categorie, toggle, azioni

Condizione PHP: `$hasSearchCampaigns = count(array_filter($viewCampaigns, fn($c) => $c['type'] === 'SEARCH')) > 0`

Questo sostituisce la condizione attuale `!empty($negativeSummary)`.

---

## Dipendenza: "Vedi tutte" link → filtro Campagne

Il link "Vedi tutte le Search →" nella dashboard passa `?type=SEARCH` come query param. La pagina Campagne legge questo param e pre-seleziona il filtro pill corrispondente.

**Conseguenza**: il filtro pills (Sezione D) e RICHIESTO, non opzionale. Va implementato insieme alla dashboard per evitare link che atterrano su una pagina non filtrata.

---

## Bug fix incluso: link Spreco Budget

Il widget "Spreco Budget" in `dashboard.php` riga 550 linka a `/negative-keywords` che **non esiste** nelle route. Fix: cambiare in `/search-term-analysis` (la route corretta).

---

## Non in scope

- Redesign completo della pagina Campagne (oltre al filtro pills)
- Modifica del prompt AI per evaluation
- Nuove tabelle DB o migrazioni
- Modifica SSE/background processing
- Redesign del widget Spreco Budget (solo riposizionamento + bugfix link)
- Colonna "Prodotti" per Shopping (rimossa — non c'e un campo product_count nel data model attuale)
