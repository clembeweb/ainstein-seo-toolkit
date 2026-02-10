# AGENTE: AI Keyword Research

> **Ultimo aggiornamento:** 2026-02-06

## CONTESTO

**Modulo:** `keyword-research`
**Stato:** 100% Completato
**Prefisso DB:** `kr_`

Modulo per keyword research potenziata da AI con **3 modalità operative**:

---

## ARCHITETTURA UI

### Dashboard Principale (`/keyword-research`)

La landing page presenta 3 card modalità + progetti recenti + statistiche utente.

| Card | Modalità | Crediti | Descrizione |
|------|----------|---------|-------------|
| **Research Guidata** | Wizard 4-step | 2-5 | Clustering semantico AI di keyword |
| **Architettura Sito** | Wizard 3-step | 5 | Struttura pagine con URL/H1 suggeriti |
| **Quick Check** | Form singolo | 0 | Ricerca keyword istantanea gratis |

### Navigazione Sidebar

```
Keyword Research ▼
  └── [Nome Progetto] (se dentro progetto)
        ├── Research Guidata
        ├── Architettura Sito
        ├── ─── separator ───
        └── Impostazioni
  Quick Check (sempre visibile, no progetto)
```

---

## 3 MODALITA'

### 1. Research Guidata

**Route:** `/keyword-research/project/{id}/research`
**Controller:** `ResearchController.php`
**View:** `views/research/wizard.php` (Alpine.js single-page)

**Flusso 4 step:**

| Step | Nome | Azione |
|------|------|--------|
| 1 | Brief | Form con business, target, geography, objective, seeds, exclusions |
| 2 | Raccolta | SSE real-time: per ogni seed chiama `keySuggest()`, filtra duplicati/volume |
| 3 | AI Clustering | POST: `AiService::analyzeWithSystem()` con prompt clustering |
| 4 | Risultati | Redirect a `/research/{researchId}` con dashboard |

**Brief JSON (kr_researches.brief):**
```json
{
    "business": "Descrizione business",
    "target": "b2b|b2c|both",
    "geography": "IT",
    "objective": "seo|ads|content",
    "seeds": ["keyword1", "keyword2"],
    "exclusions": ["parola1", "parola2"]
}
```

**AI Output (clustering):**
```json
{
    "clusters": [
        {
            "name": "Nome Cluster",
            "main_keyword": "keyword principale",
            "intent": "informational|transactional|commercial|navigational",
            "note": "Nota strategica",
            "keywords": ["kw1", "kw2", "kw3"]
        }
    ],
    "excluded_keywords": ["kw_non_rilevante"],
    "strategy_note": "Nota strategica globale"
}
```

### 2. Architettura Sito

**Route:** `/keyword-research/project/{id}/architecture`
**Controller:** `ArchitectureController.php`
**View:** `views/architecture/wizard.php` (Alpine.js single-page)

**Flusso 3 step:**

| Step | Nome | Azione |
|------|------|--------|
| 1 | Brief | Form con business, site_type, target, location, seeds |
| 2 | Raccolta + AI | SSE collection → auto-starts AI analysis |
| 3 | Risultati | Redirect a `/architecture/{researchId}` |

**site_type values:** corporate, ecommerce, blog, saas, local, portfolio

**AI Output (architettura):**
```json
{
    "pages": [
        {
            "name": "Nome Pagina",
            "suggested_url": "/percorso/pagina",
            "suggested_h1": "Titolo H1 Ottimizzato",
            "main_keyword": "keyword principale",
            "intent": "informational",
            "note": "Nota per questa pagina",
            "keywords": ["kw1", "kw2"]
        }
    ],
    "strategy_note": "Nota strategica"
}
```

### 3. Quick Check

**Route:** `/keyword-research/quick-check`
**Controller:** `QuickCheckController.php`
**View:** `views/quick-check/index.php`

- Nessun progetto richiesto
- Gratis (0 crediti)
- Form: keyword + location → POST → risultato inline
- Mostra: volume, CPC, competition, intent, top 20 correlate
- Nessun salvataggio DB

---

## SERVICE

### KeywordInsightService

**File:** `modules/keyword-research/services/KeywordInsightService.php`
**API:** Google Keyword Insight (RapidAPI)
**Host:** `google-keyword-insight1.p.rapidapi.com`
**Key:** `Settings::get('rapidapi_keyword_key')` (condivisa)

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `keySuggest()` | `/keysuggest` | Suggestions con volume, CPC, intent |
| `topKeys()` | `/topkeys` | Top keyword correlate |
| `questions()` | `/questions` | Domande correlate |
| `expandSeeds()` | loop keySuggest | Espansione multi-seed + dedup |
| `filterKeywords()` | — | Filtra per volume e exclusions |

Tutte le chiamate loggate con `ApiLoggerService::log('rapidapi_keyword_insight', ...)`.

---

## MODELS

### Project (`kr_projects`)
- `find(id)`, `allByUser(userId)`, `getRecentByUser(userId, limit)`
- `allWithStats()` - con subquery per conteggio ricerche/cluster/keyword
- `create(data)`, `update(id, data)`, `delete(id)` - cascading delete

### Research (`kr_researches`)
- `find(id)`, `findByProject(projectId)`
- `create(data)` - brief salvato come JSON
- `updateStatus(id, status)`, `saveResults(id, data)` - update post-AI
- `delete(id)` - cascading delete cluster/keywords

### Cluster (`kr_clusters`)
- `find(id)`, `findByResearch(researchId)`
- `create(data)`, `getKeywords(clusterId)`
- `getExcludedKeywords(researchId)` - keyword con is_excluded=1
- `getAllKeywords(researchId)` - JOIN con cluster_name
- `saveKeyword(data)` - salva singola keyword

---

## CREDITI

| Operazione | Chiave module.json | Default | Note |
|------------|--------------------|---------|------|
| Clustering < 100 kw | `cost_kr_ai_clustering` | 2 | Configurabile admin |
| Clustering > 100 kw | `cost_kr_ai_clustering_large` | 5 | Configurabile admin |
| Architettura sito | `cost_kr_ai_architecture` | 5 | Configurabile admin |
| Quick Check | — | 0 | Sempre gratis |

**Pattern:**
```php
$cost = Credits::getCost('kr_ai_clustering', 'keyword-research');
if (!Credits::hasEnough($userId, $cost)) { /* errore */ }
// ... AI call ...
Database::reconnect();
Credits::consume($userId, $cost, 'kr_ai_clustering', 'keyword-research', [...]);
```

---

## SSE PATTERN

Raccolta keyword usa Server-Sent Events per progress real-time.

**Punti critici:**
1. `session_write_close()` PRIMA del loop (sblocca sessione)
2. `Database::reconnect()` dopo ogni chiamata API
3. `ob_flush(); flush();` dopo ogni evento
4. Check cancellazione non necessario (raccolta breve)

**Eventi:**
| Evento | Dati | Quando |
|--------|------|--------|
| `started` | total_seeds | Inizio raccolta |
| `seed_started` | seed, index | Prima di ogni seed |
| `seed_completed` | seed, count, total | Dopo keySuggest() |
| `seed_error` | seed, error | Errore su seed |
| `filtering` | raw_count | Pre-filtro |
| `completed` | keywords[], total, filtered | Fine con array keyword |

---

## EXPORT CSV

**Route:** `GET /keyword-research/project/{id}/research/{researchId}/export`

**Formato:**
- UTF-8 con BOM (`\xEF\xBB\xBF`)
- Headers: Keyword, Volume, Competizione, CPC Basso, CPC Alto, Intent, Cluster, Main
- Ordinamento: per cluster, poi keyword principale prima

---

## FILE DI RIFERIMENTO

| File | Descrizione |
|------|-------------|
| `controllers/ResearchController.php` | Pattern wizard + SSE + AI (il piu complesso) |
| `controllers/ArchitectureController.php` | Variante architettura (auto-chain SSE→AI) |
| `controllers/QuickCheckController.php` | Pattern API-only senza crediti |
| `views/research/wizard.php` | Alpine.js wizard 4-step con SSE |
| `views/partials/cluster-card.php` | Componente cluster riutilizzabile |
| `services/KeywordInsightService.php` | Wrapper API con ApiLoggerService |
| `module.json` | Settings schema con groups e admin_only costs |

---

## TROUBLESHOOTING

| Problema | Causa | Soluzione |
|----------|-------|-----------|
| "API key non configurata" | rapidapi_keyword_key mancante | Admin > Impostazioni > Integrazioni |
| SSE non invia eventi | Output buffering | `ob_flush(); flush();` dopo echo |
| SSE blocca pagine | Sessione non chiusa | `session_write_close()` prima del loop |
| "MySQL gone away" dopo AI | Connessione scaduta | `Database::reconnect()` prima di salvare |
| AI risponde con markdown | Prompt non chiaro | Cleanup con `preg_replace` su backticks |
| 0 keyword raccolte | API quota esaurita (HTTP 429) | Verificare crediti RapidAPI |
| Export CSV caratteri rotti | Manca BOM | Aggiungere `\xEF\xBB\xBF` a inizio output |

---

*Documento agente - 2026-02-06*
