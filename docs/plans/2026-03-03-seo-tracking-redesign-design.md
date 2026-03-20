# SEO Tracking Redesign — Semrush-Style UI/UX

> Data: 2026-03-03 | Stato: Approvato | Scope: Tier 1 (Dashboard) + Tier 2 (Keywords Table)

## Obiettivo

Ridisegnare il modulo SEO Tracking per allinearlo visivamente e funzionalmente a Semrush Position Tracking, introducendo metriche avanzate (Visibility Score, Estimated Traffic), chart over-time e una keyword table arricchita con confronto date.

## Decisioni di Design

| Decisione | Scelta |
|-----------|--------|
| Curva CTR | Standard AWR 2024 (pos 1: 31.7%, pos 2: 24.7%, ...) |
| Storico chart | 30 giorni |
| Tab naming | Semrush-style (Landscape, Overview, Pages, Tags) |
| Date compare | Si, con date picker (data1 vs data2 + diff colorato) |
| Approccio | Refactor incrementale (modifica file esistenti) |

## Tab Renaming

| Prima | Dopo | Route (invariata) |
|-------|------|-------------------|
| Overview | Landscape | `/seo-tracking/project/{id}` |
| Keywords | Overview | `/seo-tracking/project/{id}/keywords` |
| URLs | Pages | `/seo-tracking/project/{id}/urls` |
| Gruppi | Tags | `/seo-tracking/project/{id}/groups` |
| Storico | Storico | invariato |
| Quick Wins | Quick Wins | invariato |
| Page Analyzer | Page Analyzer | invariato |

## Nuovo Service: VisibilityService

**File:** `modules/seo-tracking/services/VisibilityService.php`

Responsabilita:
- Calcolo Visibility Score % = SUM(CTR[pos]) / num_keywords * 100
- Calcolo Estimated Traffic = SUM(volume * CTR[pos])
- Curva CTR standard hardcoded (posizioni 1-100)

Metodi:
- `getCtrForPosition(int $position): float`
- `calculateVisibility(array $keywords): float`
- `calculateEstTraffic(array $keywords): float`
- `calculateKeywordVisibility(int $position): float`
- `calculateKeywordEstTraffic(int $volume, int $position): float`
- `getDistributionOverTime(int $projectId, int $days): array`

Curva CTR:
```
Pos 1:  31.7%   Pos 6:  6.2%
Pos 2:  24.7%   Pos 7:  4.2%
Pos 3:  18.6%   Pos 8:  3.2%
Pos 4:  13.6%   Pos 9:  2.8%
Pos 5:   9.5%   Pos 10: 2.6%
Pos 11-20: 1.0% decrescente
Pos 21-50: 0.5% decrescente
Pos 51-100: 0.1%
Pos 100+: 0%
```

## Dashboard "Landscape" — Layout

Dall'alto verso il basso:

### 1. Header + Nav (invariato, nuove label)

### 2. KPI Row — 4 card
- **Visibility Score**: `21.56%` + variazione vs periodo precedente (verde/rosso)
- **Estimated Traffic**: `29.58` + variazione
- **Average Position**: `21.57` + variazione
- **Keywords Tracked**: `7` (invariato)

### 3. Rankings Distribution + Keywords Grid (grid 2 col)
- **Sinistra**: Stacked bar chart (Chart.js) — 30 giorni, 6 bucket:
  - Top 3 (emerald), 4-10 (blue), 11-20 (amber), 21-50 (orange), 51-100 (slate), Out (red)
- **Destra**: Mini-grid conteggi per bucket con frecce improved/declined/new

### 4. AI Summary Box
- Box con testo dall'ultimo report settimanale
- Se assente: bottone "Genera riepilogo AI"

### 5. Tre mini-tabelle (grid 3 col)
- **Top Keywords**: keyword con posizione migliore
- **Positive Impact**: keyword con maggior incremento visibility
- **Negative Impact**: keyword con maggior calo visibility

### 6. Gainers / Losers tables (invariati)

## Keywords Table "Overview" — Layout

### 1. Date Range Picker
In alto a destra, seleziona intervallo date. Default: ultimi 7 giorni.

### 2. Chart sopra tabella
Tabs switchable: Visibility % | Est. Traffic | Avg. Position
Line chart (Chart.js) con dati dal nuovo endpoint API.

### 3. Filter Bar
- Input ricerca keyword
- Dropdown Intent: Tutti / Informational / Navigational / Commercial / Transactional
- Dropdown Position range: Tutte / Top 3 / Top 10 / Top 20 / Top 50 / 51-100 / 100+
- Dropdown Volume range: Tutti / 0-100 / 100-1K / 1K-10K / 10K+

### 4. Tabella keyword
Colonne: Checkbox | Keyword | Intent badge (I/N/C/T colorato) | Volume | Pos. Data1 | Pos. Data2 | Diff (colorato) | Visibility % | Est. Traffic | Azioni

Intent badge colors:
- I (Informational) = blue
- N (Navigational) = purple
- C (Commercial) = amber
- T (Transactional) = emerald

Diff colors: verde/freccia su = migliorato, rosso/freccia giu = peggiorato, grigio = invariato

### 5. Paginazione (invariata)

## Nuovi Endpoint API

```
GET /seo-tracking/api/project/{id}/visibility-stats
  Response: { visibility, est_traffic, avg_position, tracked_count,
              prev_visibility, prev_est_traffic, prev_avg_position }

GET /seo-tracking/api/project/{id}/distribution-history?days=30
  Response: [{ date, top3, top10, top20, top50, top100, out }]

GET /seo-tracking/api/project/{id}/visibility-trend?days=30
  Response: [{ date, visibility, est_traffic, avg_position }]

GET /seo-tracking/api/project/{id}/keywords-compare?date_from=&date_to=
  Response: [{ keyword_id, keyword, intent, volume, pos_from, pos_to,
               diff, visibility, est_traffic }]
```

## File Modificati

| File | Azione |
|------|--------|
| `modules/seo-tracking/services/VisibilityService.php` | NUOVO |
| `modules/seo-tracking/views/partials/project-nav.php` | MODIFICA (label) |
| `modules/seo-tracking/views/dashboard/index.php` | RISCRITTURA |
| `modules/seo-tracking/views/keywords/index.php` | RISCRITTURA |
| `modules/seo-tracking/controllers/DashboardController.php` | MODIFICA |
| `modules/seo-tracking/controllers/KeywordController.php` | MODIFICA |
| `modules/seo-tracking/controllers/ApiController.php` | MODIFICA |
| `modules/seo-tracking/routes.php` | MODIFICA |

## Non in Scope (futuri)

- Tab Cannibalization
- Tab Competitors Discovery
- SERP Features tracking
- Devices & Locations
- Featured Snippets
