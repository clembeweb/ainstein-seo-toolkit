# Multi-Country Keyword Tracking — Design

> Data: 2026-03-03 | Stato: Approvato | Scope: Country-First Navigation

## Obiettivo

Aggiungere supporto multi-country al modulo SEO Tracking con navigazione country-first: summary globale, tab per country, metriche specifiche per country (volumi, CPC, posizioni SERP), il tutto sfruttando l'infrastruttura backend gia esistente al 70%.

## Decisioni di Design

| Decisione | Scelta |
|-----------|--------|
| Struttura | Country come primo livello di navigazione |
| Aggiunta keyword | Dropdown country nel modal import, tab automatici |
| Dashboard | Summary globale compatto + tab [All] [IT] [US] [ES] |
| Rank check cron | Tutte insieme, stessa frequenza, ogni keyword usa la sua location_code |
| Migrazioni DB | Nessuna — `st_keywords.location_code` gia esiste |
| URL pattern | Query param `?country=IT` (non route change) |

## Stato Attuale Infrastruttura

| Componente | Stato | Dettaglio |
|-----------|-------|-----------|
| `st_keywords.location_code` | Esiste | VARCHAR(5) DEFAULT 'IT' |
| `st_locations` (20 paesi) | Esiste | IT, US, GB, DE, FR, ES, CH, AT, NL, BE, PT, BR, CA, AU, MX, AR, CL, CO, PL, SE |
| DataForSEO multi-country | Esiste | `checkSerpPosition()` accetta location_code |
| RapidAPI multi-country | Esiste | 22 country mappate in `$countryMap` |
| Volume fetch per-country | Esiste | `updateSearchVolumes()` raggruppa per location_code |
| Rank check cron | Esiste | `st_rank_queue.location_code` gia presente |
| Gruppi per country | Manca | `st_keyword_groups` non ha location context |

## Layout Multi-Country

```
┌─────────────────────────────────────────────────────┐
│  Amevista  -  amevista.com          [Impostazioni]  │
├─────────────────────────────────────────────────────┤
│  Country Summary:                                   │
│  [IT: 2.62% vis | 28kw] [US: 1.8% | 35kw] [ES: 0.9% | 22kw] │
├─────────────────────────────────────────────────────┤
│  [All] [IT●] [US] [ES]     ← country tabs          │
├─────────────────────────────────────────────────────┤
│  Landscape | Overview | Pages | Tags | ...          │
├─────────────────────────────────────────────────────┤
│  (contenuto filtrato per country selezionata)       │
└─────────────────────────────────────────────────────┘
```

### Country Summary Bar

Riga compatta sopra i tab di navigazione modulo. Ogni country mostra: flag emoji + codice, visibility %, conteggio keyword. Cliccando su una country card si seleziona quel tab.

Visibile solo quando il progetto ha keyword in 2+ country. Con una sola country, non appare.

### Country Tabs

- **All**: metriche aggregate di tutte le country
- **[XX]**: tab per ogni country con almeno 1 keyword tracciata
- Query: `SELECT DISTINCT location_code FROM st_keywords WHERE project_id = ? AND is_tracked = 1`
- Tab attivo passa `?country=XX` a tutte le sotto-pagine
- Default: prima country per numero keyword (solitamente IT)

### Comportamento Tab "All"

- Visibility = media ponderata delle visibility per country
- Est. Traffic = somma di tutti i traffic per country
- Pos. Media = media di tutte le posizioni
- Keywords = conteggio totale
- Chart: stacked bar aggregato cross-country
- Mini-tabelle: keyword cross-country ordinate per metrica

## Modifiche per Vista

### Landscape Dashboard

- Aggiungere country summary bar + country tabs sopra la navigation esistente
- KPI cards: filtrate per `?country`
- Distribution chart: filtrato per country
- Mini-grid buckets: filtrato per country
- Mini-tabelle (Top/Positive/Negative): filtrate per country
- Gainers/Losers: filtrati per country

### Overview (Keywords Table)

- Country tabs sopra la tabella
- Trend chart: filtrato per country
- Keyword table: filtrata per country
- Filtri esistenti (intent, position, volume) operano sulla country selezionata
- Colonna "LOC" gia presente — mostra flag della country

### Modal "Aggiungi Keyword"

- Aggiungere dropdown "Country" sopra il textarea keyword
- Default: country del tab attivo (o IT se "All")
- Al cambio country: preview costi aggiornata
- Keyword inserite ricevono `location_code` dalla selezione
- Fetch volumi automatico per la country selezionata

## Modifiche Backend

### Controller (DashboardController, KeywordController)

Tutti i metodi leggono `$_GET['country']` e filtrano le query:

```php
$country = $_GET['country'] ?? null; // null = All
$keywords = $country
    ? Keyword::allWithPositions($projectId, ['location_code' => $country])
    : Keyword::allWithPositions($projectId);
```

### VisibilityService

Nessuna modifica — gia lavora su array di keyword filtrate.

### ApiController

I 4 endpoint (visibility-stats, distribution-history, visibility-trend, keywords-compare) accettano `?country=XX` e filtrano le query per `location_code`.

### Bulk Rank Check

`checkBulk()` deve gestire keyword con location diverse. Raggruppare per `location_code` e checkare in batch per country.

### Nuovo Endpoint API

```
GET /seo-tracking/api/project/{id}/country-summary
  Response: [{ country_code, country_name, flag, visibility, est_traffic,
               avg_position, keyword_count }]
```

## Nuovo Componente View

### `views/partials/country-bar.php`

Componente riutilizzabile che mostra:
1. Country summary cards (se 2+ country)
2. Country tab bar ([All] + tab per country)

Incluso in tutte le viste del progetto, sopra `project-nav.php`.

## File Modificati

| File | Azione |
|------|--------|
| `views/partials/country-bar.php` | NUOVO — componente country summary + tabs |
| `views/dashboard/index.php` | MODIFICA — include country-bar, filtra per country |
| `views/keywords/index.php` | MODIFICA — include country-bar, filtra per country |
| `views/keywords/add-modal.php` o inline | MODIFICA — dropdown country nel modal |
| `controllers/DashboardController.php` | MODIFICA — legge `?country`, filtra query |
| `controllers/KeywordController.php` | MODIFICA — legge `?country`, filtra query |
| `controllers/ApiController.php` | MODIFICA — country param su tutti gli endpoint |
| `controllers/RankCheckController.php` | MODIFICA — bulk check per-country grouping |
| `models/Keyword.php` | MODIFICA — filtro location_code su query principali |

## Non in Scope (futuri)

- Frequenza rank check configurabile per country
- Country come livello gerarchico nel DB (tabella intermedia)
- Competitor tracking per country
- Confronto cross-country per stessa keyword
- Report AI per country
