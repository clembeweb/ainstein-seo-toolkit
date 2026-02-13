# AI KEYWORD RESEARCH - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `keyword-research` |
| **Prefisso DB** | `kr_` |
| **Files** | 25 |
| **Stato** | ✅ Attivo (100%) |
| **Ultimo update** | 2026-02-13 |

Modulo per keyword research potenziata da AI con 4 modalità: Research Guidata (clustering semantico), Architettura Sito (struttura pagine con URL/H1), Piano Editoriale (piano articoli mensile con SERP + AI), Quick Check (ricerca istantanea gratis).

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
│   ├── DashboardController.php    # Entry point modulo (4 card modalità)
│   ├── ProjectController.php      # CRUD progetti
│   ├── ResearchController.php     # Research Guidata (wizard + SSE + AI)
│   ├── ArchitectureController.php # Architettura Sito (wizard + SSE + AI)
│   ├── EditorialController.php    # Piano Editoriale (wizard + SSE + SERP + AI)
│   └── QuickCheckController.php   # Quick Check (no progetto, gratis)
├── models/
│   ├── Project.php                # CRUD kr_projects
│   ├── Research.php               # CRUD kr_researches + status
│   ├── Cluster.php                # CRUD kr_clusters + kr_keywords
│   └── EditorialItem.php          # CRUD kr_editorial_items
├── services/
│   └── KeywordInsightService.php  # Wrapper Google Keyword Insight API (RapidAPI)
└── views/
    ├── dashboard.php              # Landing 4 modalità + progetti recenti
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
    ├── editorial/
    │   ├── wizard.php             # Wizard 4-step (Alpine.js, tema viola)
    │   └── results.php            # Tabella mensile + export AI Content
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

-- Piano Editoriale
kr_editorial_items       -- Articoli piano editoriale (research_id, month, category, title, keyword)
kr_serp_cache            -- Cache SERP 7 giorni (query, organic_results, paa, related)
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
| type | ENUM | 'research', 'architecture' o 'editorial' |
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

#### kr_editorial_items
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| research_id | INT | FK a kr_researches |
| month_number | INT | Mese (1-based) |
| week_number | INT | Settimana (opzionale, 1-4) |
| category | VARCHAR(255) | Categoria articolo |
| title | VARCHAR(500) | Titolo suggerito |
| main_keyword | VARCHAR(500) | Keyword principale |
| main_volume | INT | Volume keyword principale |
| secondary_keywords | JSON | [{text, volume}] |
| intent | VARCHAR(50) | Search intent |
| difficulty | VARCHAR(20) | low/medium/high |
| content_type | VARCHAR(100) | guida/tutorial/listicle/case-study/etc |
| notes | TEXT | Note AI strategiche |
| seasonal_note | VARCHAR(500) | Nota stagionalità |
| serp_gap | TEXT | Gap competitor SERP |
| sort_order | INT | Ordine visualizzazione |
| sent_to_content | TINYINT(1) | 1 se inviato a AI Content |
| sent_at | TIMESTAMP | Data invio |

#### kr_serp_cache
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| query | VARCHAR(500) | Query di ricerca |
| location | VARCHAR(10) | Location (IT, US, ...) |
| language | VARCHAR(10) | Lingua (it, en, ...) |
| organic_results | JSON | Risultati organici SERP |
| paa | JSON | People Also Ask |
| related_searches | JSON | Ricerche correlate |
| cached_at | TIMESTAMP | TTL 7 giorni |

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

## 4 Modalità

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

### 3. Piano Editoriale (wizard 4 step)

**Step 1 - Brief** (form Alpine.js)
- Theme (textarea, required), categories (tag input violet, min 2, max 6)
- Period: 3, 6, 12 mesi | Articles/month: 2, 4, 6, 8
- Target (B2B/B2C/Entrambi), geography

**Step 2 - Raccolta Dati** (SSE)
- POST `/editorial/start` → crea research type='editorial', ritorna research_id
- GET `/editorial/stream?research_id=X` → SSE per ogni categoria
- Per ogni categoria: `KeywordInsightService::keySuggest()` + `SerpApiService::search()` (SERP titles)
- Cache SERP in `kr_serp_cache` (7 giorni TTL, UPSERT)
- Eventi: `started`, `category_started`, `category_keywords`, `category_serp`, `category_completed`, `completed`

**Step 3 - AI Piano** (AJAX lungo con `ob_start()`)
- POST `/editorial/analyze` → `AiService::analyzeWithSystem()` con module 'keyword-research'
- Prompt: tema, categorie, top 30 keyword per categoria (con volumi), titoli SERP competitor
- `Database::reconnect()` prima di salvare
- `Credits::consume()` dopo successo
- Salvataggio: `kr_editorial_items` per ogni articolo

**Step 4 - Risultati** (redirect a results view)
- Tabella mensile, export CSV, invio a AI Content

**Crediti:** 5 - configurabile da admin

**Integrazione cross-modulo:**
- `SerpApiService` da ai-content (opzionale, graceful degradation)
- Export in `aic_queue` con controllo duplicati

### 4. Quick Check (no progetto)

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
| Piano Editoriale AI | `cost_kr_editorial_plan` | 5 | Piano editoriale mensile |
| Quick Check | — | 0 | Gratis (solo API) |

---

## Routes

```
# Dashboard
GET  /keyword-research                                      # Landing 4 modalità

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

# Piano Editoriale
GET  /keyword-research/project/{id}/editorial                # Wizard
POST /keyword-research/project/{id}/editorial/start          # Avvia raccolta
GET  /keyword-research/project/{id}/editorial/stream         # SSE streaming (kw + SERP)
GET  /keyword-research/project/{id}/editorial/collection-results  # Polling fallback
POST /keyword-research/project/{id}/editorial/analyze        # AI piano editoriale
GET  /keyword-research/project/{id}/editorial/{researchId}   # Risultati tabella mensile
GET  /keyword-research/project/{id}/editorial/{researchId}/export  # Export CSV
POST /keyword-research/project/{id}/editorial/{researchId}/send-to-content  # Invio AI Content

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
| cost_kr_editorial_plan | number | 5 | Crediti piano editoriale |

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
        ├── Piano Editoriale
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
7. **Editorial SERP cross-module**: usa `SerpApiService` da ai-content, wrappato in try/catch per graceful degradation
8. **Editorial SERP cache**: `kr_serp_cache` con TTL 7 giorni, UPSERT con `ON DUPLICATE KEY UPDATE`
9. **Editorial AI prompt lungo**: pattern AJAX lungo con `ob_start()` / `ob_end_clean()` (NON `jsonResponse()`)
10. **Editorial export AI Content**: inserisce in `aic_queue` con `scheduled_at = date('Y-m-d H:i:s')` (NOT NULL)
11. **Input handling robusto**: `categories` e `item_ids` gestiscono sia array PHP che stringa JSON/CSV

---

*Spec aggiornata - 2026-02-13*
