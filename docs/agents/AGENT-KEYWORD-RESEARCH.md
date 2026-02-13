# AGENTE: AI Keyword Research

> **Ultimo aggiornamento:** 2026-02-13

## CONTESTO

**Modulo:** `keyword-research`
**Stato:** 100% Completato
**Prefisso DB:** `kr_`

Modulo per keyword research potenziata da AI con **4 modalità operative**:

---

## ARCHITETTURA UI

### Dashboard Principale (`/keyword-research`)

La landing page presenta 4 card modalità + progetti recenti + statistiche utente.

| Card | Modalità | Crediti | Descrizione |
|------|----------|---------|-------------|
| **Research Guidata** | Wizard 4-step | 2-5 | Clustering semantico AI di keyword |
| **Architettura Sito** | Wizard 3-step | 5 | Struttura pagine con URL/H1 suggeriti |
| **Piano Editoriale** | Wizard 4-step | 5 | Piano articoli mensile con SERP + AI |
| **Quick Check** | Form singolo | 0 | Ricerca keyword istantanea gratis |

### Navigazione Sidebar

```
Keyword Research ▼
  └── [Nome Progetto] (se dentro progetto)
        ├── Research Guidata
        ├── Architettura Sito
        ├── Piano Editoriale
        ├── ─── separator ───
        └── Impostazioni
  Quick Check (sempre visibile, no progetto)
```

---

## 4 MODALITA'

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

### 3. Piano Editoriale

**Route:** `/keyword-research/project/{id}/editorial`
**Controller:** `EditorialController.php`
**View:** `views/editorial/wizard.php` (Alpine.js single-page)

**Flusso 4 step:**

| Step | Nome | Azione |
|------|------|--------|
| 1 | Brief | Form con theme, categories (tag, 2-6), months (3/6/12), articles/month (2/4/6/8), target, geography |
| 2 | Raccolta | SSE real-time: per ogni categoria chiama `keySuggest()` + `SerpApiService::search()` (SERP titles) |
| 3 | AI Piano | POST: `AiService::analyzeWithSystem()` con prompt piano editoriale (pattern AJAX lungo con `ob_start()`) |
| 4 | Risultati | Redirect a `/editorial/{researchId}` con tabella mensile |

**Brief JSON (kr_researches.brief):**
```json
{
    "theme": "Tema principale blog",
    "categories": ["Categoria 1", "Categoria 2", "Categoria 3"],
    "months": 3,
    "articles_per_month": 4,
    "target": "B2B",
    "geography": "IT"
}
```

**AI Output (piano editoriale):**
```json
{
    "months": [{
        "month_number": 1,
        "month_note": "Nota strategica mensile",
        "articles": [{
            "title": "Titolo articolo",
            "main_keyword": "keyword principale",
            "main_volume": 720,
            "secondary_keywords": [{"text": "kw2", "volume": 50}],
            "category": "SEO Tecnico",
            "content_type": "guida",
            "intent": "informational",
            "difficulty": "medium",
            "notes": "Nota AI",
            "seasonal_note": "Nota stagionalità",
            "serp_gap": "Gap competitor SERP"
        }]
    }],
    "strategy_note": "Nota strategica globale"
}
```

**Integrazione cross-modulo:**
- `SerpApiService` da `modules/ai-content/services/` per SERP competitor titles
- `kr_serp_cache` per cache SERP 7 giorni (evita chiamate duplicate)
- Export in AI Content: inserisce articoli selezionati in `aic_queue`
- Controllo duplicati su `sent_to_content` flag

**Crediti:** 5 - configurabile da admin

### 4. Quick Check

**Route:** `/keyword-research/quick-check`
**Controller:** `QuickCheckController.php`
**View:** `views/quick-check/index.php`

- Nessun progetto richiesto
- Gratis (0 crediti)
- Form: keyword + location → POST → risultato inline
- Mostra: volume, CPC, competition, intent, tutte le correlate (sorting + filtri + paginazione)
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
- `delete(id)` - cascading delete editorial_items/cluster/keywords

### EditorialItem (`kr_editorial_items`)
- `find(id)`, `findByResearch(researchId)` - lista articoli
- `findGroupedByMonth(researchId)` - raggruppati per mese (vista risultati)
- `create(data)`, `markSentToContent(id)` - segna inviato a AI Content
- `getStats(researchId)` - totali per la vista
- `deleteByResearch(researchId)` - cascading delete

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
| Piano Editoriale | `cost_kr_editorial_plan` | 5 | Configurabile admin |
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

**Punti critici (ordine nel controller):**
1. `ignore_user_abort(true)` → CRITICO: proxy SiteGround chiude SSE, senza questo PHP aborta e non salva nel DB
2. `set_time_limit(0)` → rimuove timeout PHP
3. `session_write_close()` PRIMA del loop (sblocca sessione)
4. `Database::reconnect()` dopo ogni chiamata API
5. `if (ob_get_level()) ob_flush(); flush();` → guard per server senza buffer
6. **Salvare risultati nel DB PRIMA dell'evento `completed`** → se SSE cade, il polling fallback recupera dal DB

**Polling fallback:**
- Endpoint: `GET /collection-results?research_id=X`
- Legge keyword filtrate dal campo `ai_response` JSON della research (salvate durante collection)
- Frontend auto-poll quando SSE disconnette a progresso >= 85%

**Eventi:**
| Evento | Dati | Quando |
|--------|------|--------|
| `started` | total_seeds | Inizio raccolta |
| `seed_started` | seed, index | Prima di ogni seed |
| `seed_completed` | seed, count, total | Dopo keySuggest() |
| `seed_error` | seed, error | Errore su seed (es. HTTP 429) |
| `filtering` | raw_count | Pre-filtro |
| `completed` | keywords[], total, filtered | Fine con array keyword |

**Frontend SSE error handling:**
```javascript
// Errori custom dal server (event: error nel stream)
this.eventSource.addEventListener('error', (e) => {
    try {
        const d = JSON.parse(e.data);
        this.status = 'Errore: ' + d.message;
        this.eventSource.close();
    } catch (_) {
        // Errore nativo SSE (no e.data) - gestito da onerror
    }
});
// Disconnessione SSE (proxy timeout)
this.eventSource.onerror = () => {
    this.eventSource.close();
    if (this.progress >= 85) {
        this.pollCollectionResults(); // recupera dal DB
    }
};
```

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
| `controllers/ResearchController.php` | Wizard + SSE + polling fallback + AI clustering |
| `controllers/ArchitectureController.php` | Variante architettura (auto-chain SSE→AI) + polling fallback |
| `controllers/EditorialController.php` | Piano Editoriale (wizard + SSE + SERP + AI + export AI Content) |
| `controllers/QuickCheckController.php` | Pattern API-only senza crediti |
| `views/research/wizard.php` | Alpine.js wizard 4-step con SSE + polling fallback |
| `views/architecture/wizard.php` | Alpine.js wizard 3-step con SSE + polling fallback |
| `views/editorial/wizard.php` | Alpine.js wizard 4-step con SSE + SERP + AI (tema viola) |
| `views/editorial/results.php` | Tabella mensile con export CSV + invio AI Content |
| `views/partials/cluster-card.php` | Componente cluster con sorting Alpine.js |
| `views/partials/table-helpers.php` | JS condiviso: sort, filtri, paginazione, colori intent/comp |
| `views/quick-check/index.php` | Tabella correlate con sorting, filtri, paginazione |
| `views/research/results.php` | Risultati con filtro intent + espandi/comprimi cluster |
| `views/architecture/results.php` | Tabella struttura con sorting + filtro intent cluster |
| `services/KeywordInsightService.php` | Wrapper API con ApiLoggerService |
| `module.json` | Settings schema con groups e admin_only costs |

---

## TROUBLESHOOTING

| Problema | Causa | Soluzione |
|----------|-------|-----------|
| "API key non configurata" | rapidapi_keyword_key mancante | Admin > Impostazioni > Integrazioni |
| SSE non invia eventi | Output buffering | `if (ob_get_level()) ob_flush(); flush();` |
| SSE blocca pagine | Sessione non chiusa | `session_write_close()` prima del loop |
| SSE "Connessione persa" 90% | Proxy SiteGround chiude SSE | `ignore_user_abort(true)` + polling fallback |
| SSE dati persi dopo disconnect | Risultati solo via SSE | Salvare nel DB PRIMA dell'evento `completed` |
| `ob_flush(): Failed to flush` | Nessun buffer attivo | `if (ob_get_level()) ob_flush()` |
| `getModuleSetting()` undefined | Metodo sbagliato | `ModuleLoader::getSetting(slug, key, default)` |
| "MySQL gone away" dopo AI | Connessione scaduta | `Database::reconnect()` prima di salvare |
| AI risponde con markdown | Prompt non chiaro | Cleanup con `preg_replace` su backticks |
| 0 keyword raccolte | API quota esaurita (HTTP 429) | Verificare crediti RapidAPI |
| "Errore di connessione" al click | Campo CSRF sbagliato | `_csrf_token` (con underscore!), NON `csrf_token` |
| Export CSV caratteri rotti | Manca BOM | Aggiungere `\xEF\xBB\xBF` a inizio output |
| Modulo non visibile in prod | Tabelle/modulo non creati | Creare tabelle + INSERT in `modules` |

## DEPLOY PRODUZIONE

```bash
# 1. Push codice
git push origin main

# 2. SSH e pull
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html && git pull origin main

# 3. Creare tabelle (prima volta)
mysql -u USER -pPASS DB < modules/keyword-research/database/schema.sql

# 4. Attivare modulo (prima volta)
mysql -u USER -pPASS DB -e "INSERT INTO modules (slug, name, description, version, is_active) VALUES ('keyword-research', 'AI Keyword Research', 'Research e analisi keyword con AI', '1.0.0', 1);"
```

---

*Documento agente - 2026-02-13*
