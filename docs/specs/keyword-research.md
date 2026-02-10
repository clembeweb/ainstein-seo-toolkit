# AI KEYWORD RESEARCH - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `keyword-research` |
| **Prefisso DB** | `kr_` |
| **Files** | 20 |
| **Stato** | ✅ Attivo (100%) |
| **Ultimo update** | 2026-02-06 |

Modulo per keyword research potenziata da AI con 3 modalità: Research Guidata (clustering semantico), Architettura Sito (struttura pagine con URL/H1), Quick Check (ricerca istantanea gratis).

> **Differenziazione:** Output azionabili (cluster, architettura sito, note strategiche) invece di soli dati grezzi.

---

## Architettura

```
modules/keyword-research/
├── module.json
├── routes.php
├── database/
│   └── schema.sql
├── controllers/
│   ├── DashboardController.php    # Entry point modulo (3 card modalità)
│   ├── ProjectController.php      # CRUD progetti
│   ├── ResearchController.php     # Research Guidata (wizard + SSE + AI)
│   ├── ArchitectureController.php # Architettura Sito (wizard + SSE + AI)
│   └── QuickCheckController.php   # Quick Check (no progetto, gratis)
├── models/
│   ├── Project.php                # CRUD kr_projects
│   ├── Research.php               # CRUD kr_researches + status
│   └── Cluster.php                # CRUD kr_clusters + kr_keywords
├── services/
│   └── KeywordInsightService.php  # Wrapper Google Keyword Insight API (RapidAPI)
└── views/
    ├── dashboard.php              # Landing 3 modalità + progetti recenti
    ├── projects/
    │   ├── index.php              # Lista progetti (card grid)
    │   ├── create.php             # Form creazione
    │   └── settings.php           # Settings + danger zone
    ├── research/
    │   ├── wizard.php             # Wizard 4-step (Alpine.js)
    │   └── results.php            # Dashboard risultati cluster
    ├── architecture/
    │   ├── wizard.php             # Wizard 3-step (Alpine.js)
    │   └── results.php            # Struttura sito proposta
    ├── quick-check/
    │   └── index.php              # Form + risultati inline
    └── partials/
        └── cluster-card.php       # Componente cluster riutilizzabile
```

---

## Database Schema

```sql
-- Progetti
kr_projects              -- Progetti keyword research (user_id, name, location, language)

-- Ricerche
kr_researches            -- Ricerche/analisi (project_id, type, status, brief JSON, risultati)

-- Cluster e keyword
kr_clusters              -- Cluster semantici generati da AI (research_id, name, intent, URL/H1)
kr_keywords              -- Keyword singole (research_id, cluster_id, text, volume, CPC, intent)
```

### Tabelle

#### kr_projects
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT | Utente proprietario |
| name | VARCHAR(255) | Nome progetto |
| description | TEXT | Descrizione |
| default_location | VARCHAR(10) | Location predefinita (IT, US, ...) |
| default_language | VARCHAR(10) | Lingua predefinita (it, en, ...) |
| created_at | TIMESTAMP | Data creazione |
| updated_at | TIMESTAMP | Data aggiornamento |

#### kr_researches
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| project_id | INT | FK a kr_projects |
| user_id | INT | Utente |
| type | ENUM | 'research' o 'architecture' |
| status | ENUM | draft, collecting, analyzing, completed, error |
| brief | JSON | Input utente (business, target, seeds, etc.) |
| raw_keywords_count | INT | Keyword grezze da API |
| filtered_keywords_count | INT | Keyword dopo filtro |
| ai_response | JSON | Risposta AI completa |
| strategy_note | TEXT | Nota strategica globale |
| credits_used | DECIMAL | Crediti consumati |
| api_time_ms | INT | Tempo API in ms |
| ai_time_ms | INT | Tempo AI in ms |

#### kr_clusters
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| research_id | INT | FK a kr_researches |
| name | VARCHAR(255) | Nome cluster/pagina |
| main_keyword | VARCHAR(500) | Keyword principale |
| main_volume | INT | Volume keyword principale |
| total_volume | INT | Volume totale cluster |
| keywords_count | INT | Numero keyword nel cluster |
| intent | VARCHAR(50) | Intent (informational, transactional, etc.) |
| note | TEXT | Nota AI per il cluster |
| suggested_url | VARCHAR(500) | URL suggerito (solo architettura) |
| suggested_h1 | VARCHAR(500) | H1 suggerito (solo architettura) |
| sort_order | INT | Ordine visualizzazione |

#### kr_keywords
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| research_id | INT | FK a kr_researches |
| cluster_id | INT | FK a kr_clusters (NULL se esclusa) |
| text | VARCHAR(500) | Testo keyword |
| volume | INT | Volume di ricerca mensile |
| competition_level | VARCHAR(20) | Livello competizione |
| competition_index | INT | Indice competizione (0-100) |
| low_bid | DECIMAL(10,6) | CPC minimo |
| high_bid | DECIMAL(10,6) | CPC massimo |
| trend | DECIMAL(10,2) | Trend ricerca |
| intent | VARCHAR(50) | Search intent |
| is_main | TINYINT(1) | 1 se keyword principale del cluster |
| is_excluded | TINYINT(1) | 1 se esclusa dal clustering |
| source | VARCHAR(50) | Fonte (keysuggest) |

---

## API Esterna

### Google Keyword Insight (RapidAPI)

| Aspetto | Dettaglio |
|---------|-----------|
| **Host** | `google-keyword-insight1.p.rapidapi.com` |
| **API Key** | `Settings::get('rapidapi_keyword_key')` (condivisa con seo-tracking) |
| **Service** | `KeywordInsightService.php` (modulo-locale) |
| **Logging** | `ApiLoggerService::log('rapidapi_keyword_insight', ...)` |

### Endpoint Utilizzati

| Endpoint | Metodo | Utilizzo |
|----------|--------|----------|
| `/keysuggest` | GET | Keyword suggestions con volumi, CPC, intent |
| `/topkeys` | GET | Top keyword correlate |
| `/questions` | GET | Domande correlate |

### KeywordInsightService

```php
// Metodi principali
$service = new KeywordInsightService();
$service->isConfigured(): bool
$service->keySuggest(keyword, location, lang, options): array
$service->topKeys(keyword, location, lang, num): array
$service->questions(keyword, location, lang): array
$service->expandSeeds(seeds[], location, lang): array   // Loop + merge + deduplica
$service->filterKeywords(keywords[], exclusions[], minVolume): array
```

---

## 3 Modalità

### 1. Research Guidata (wizard 4 step)

**Step 1 - Brief** (form Alpine.js)
- Business description, target (B2B/B2C/Both), geography, objective (SEO/Ads/Content)
- Seed keywords (tag input, min 1, max da settings)
- Exclusions (tag input, opzionale)

**Step 2 - Raccolta Keyword** (SSE)
- POST `/research/start` → crea research, ritorna research_id
- GET `/research/stream?research_id=X` → SSE per ogni seed
- Per ogni seed: `KeywordInsightService::keySuggest()` con ApiLoggerService
- Pre-filtro: duplicati, exclusions, volume < min_search_volume
- Eventi: `started`, `seed_started`, `seed_completed`, `seed_error`, `filtering`, `completed`

**Step 3 - AI Clustering** (loading spinner)
- POST `/research/analyze` → `AiService::analyzeWithSystem()` con module 'keyword-research'
- `Database::reconnect()` prima di salvare
- `Credits::consume()` dopo successo
- Salvataggio: kr_clusters + kr_keywords

**Step 4 - Risultati** (redirect a results view)
- Stats, cluster cards, keyword per cluster, export CSV

**Crediti:** 2 (< 100 kw) o 5 (> 100 kw) - configurabile da admin

### 2. Architettura Sito (wizard 3 step)

**Step 1 - Brief**: Business, site_type (corporate/ecommerce/blog/saas/local/portfolio), target, seeds
**Step 2 - Raccolta + AI**: Collection SSE → auto-starts AI analysis
**Step 3 - Risultati**: Redirect a results con tabella struttura

**Output AI:** Pagine con name, main_keyword, suggested_url, suggested_h1, keywords[], intent, note
**Crediti:** 5 - configurabile da admin

### 3. Quick Check (no progetto)

- Pagina singola, no progetto necessario, **gratis** (0 crediti)
- Form: keyword + location
- POST `/quick-check/search` → API diretta (no AI)
- Risultato inline: card keyword principale + tabella top 20 correlate
- Nessun salvataggio DB

---

## Crediti

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| AI Clustering (< 100 kw) | `cost_kr_ai_clustering` | 2 | Clustering semantico AI |
| AI Clustering (> 100 kw) | `cost_kr_ai_clustering_large` | 5 | Clustering AI (large) |
| Architettura sito AI | `cost_kr_ai_architecture` | 5 | Struttura sito con URL/H1 |
| Quick Check | — | 0 | Gratis (solo API) |

---

## Routes

```
# Dashboard
GET  /keyword-research                                      # Landing 3 modalità

# Progetti
GET  /keyword-research/projects                             # Lista
GET  /keyword-research/projects/create                      # Form crea
POST /keyword-research/projects                             # Store
GET  /keyword-research/project/{id}/settings                # Settings
POST /keyword-research/project/{id}/settings                # Update settings
POST /keyword-research/project/{id}/delete                  # Delete

# Research Guidata
GET  /keyword-research/project/{id}/research                # Wizard
POST /keyword-research/project/{id}/research/start          # Avvia raccolta
GET  /keyword-research/project/{id}/research/stream         # SSE streaming
POST /keyword-research/project/{id}/research/analyze        # AI clustering
GET  /keyword-research/project/{id}/research/{researchId}   # Risultati
GET  /keyword-research/project/{id}/research/{researchId}/export  # Export CSV

# Architettura Sito
GET  /keyword-research/project/{id}/architecture            # Wizard
POST /keyword-research/project/{id}/architecture/start      # Avvia raccolta
GET  /keyword-research/project/{id}/architecture/stream     # SSE streaming
POST /keyword-research/project/{id}/architecture/analyze    # AI analisi
GET  /keyword-research/project/{id}/architecture/{researchId} # Risultati

# Quick Check
GET  /keyword-research/quick-check                          # Form
POST /keyword-research/quick-check/search                   # Ricerca
```

---

## Module Settings (module.json)

### General
| Setting | Tipo | Default | Descrizione |
|---------|------|---------|-------------|
| default_location | select | IT | Location predefinita |
| default_language | select | it | Lingua predefinita |
| max_seeds | number | 5 | Max seed per ricerca |
| min_search_volume | number | 10 | Volume minimo |
| max_clusters | number | 8 | Max cluster AI |

### Costs (admin_only)
| Setting | Tipo | Default | Descrizione |
|---------|------|---------|-------------|
| cost_kr_ai_clustering | number | 2 | Crediti clustering (< 100 kw) |
| cost_kr_ai_clustering_large | number | 5 | Crediti clustering (> 100 kw) |
| cost_kr_ai_architecture | number | 5 | Crediti architettura sito |

---

## SSE Pattern (Raccolta Keyword)

Il modulo usa SSE per la raccolta keyword in real-time:

```php
// Controller pattern
public function collectionStream(int $projectId): void
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    session_write_close();  // CRITICO: sblocca sessione

    foreach ($seeds as $seed) {
        $result = $keywordService->keySuggest($seed, $location, $lang);
        Database::reconnect();  // Dopo ogni chiamata API
        $this->sendEvent('seed_completed', [...]);
    }
    $this->sendEvent('completed', ['keywords' => $filtered]);
}
```

**Eventi SSE:**
- `started` - Raccolta avviata con totale seeds
- `seed_started` - Inizio elaborazione seed
- `seed_completed` - Seed completato con conteggio keyword
- `seed_error` - Errore su seed
- `filtering` - Filtraggio in corso
- `completed` - Raccolta completata con array keyword filtrate

---

## Sidebar Navigation

Accordion in `shared/views/components/nav-items.php`:

```
Keyword Research ▼
  └── [Nome Progetto] (se dentro progetto)
        ├── Research Guidata
        ├── Architettura Sito
        ├── ─── separator ───
        └── Impostazioni
  Quick Check (sempre visibile)
```

---

## Note Implementazione

1. **API Key condivisa**: usa `Settings::get('rapidapi_keyword_key')`, stessa key di seo-tracking ma host diverso
2. **KeywordInsightService locale**: non in `/services/` condiviso, ma in `modules/keyword-research/services/`
3. **Keyword passate via frontend**: dopo raccolta SSE, le keyword filtrate sono inviate dal frontend al POST analyze (non ri-lette da DB)
4. **AI prompt JSON-only**: cleanup risposta con `preg_replace('/^```(?:json)?\s*/i', '', $content)`
5. **Export CSV UTF-8**: BOM `\xEF\xBB\xBF` per compatibilità Excel
6. **Architecture auto-chain**: dopo collection SSE, il frontend auto-avvia AI analysis (no click utente)

---

*Spec creata - 2026-02-06*
