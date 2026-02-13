# AGENTE: Content Creator

> **Ultimo aggiornamento:** 2026-02-13

## CONTESTO

**Modulo:** `content-creator`
**Stato:** 0% - Da implementare
**Prefisso DB:** `cc_`

Modulo per la creazione di contenuti HTML (body) per pagine web, con supporto multi-CMS per pubblicazione diretta. Gestisce 5 tipi di contenuto e 5 metodi di import URL.

---

## ARCHITETTURA UI

### Dashboard Principale (`/content-creator`)

La landing page mostra i progetti dell'utente con statistiche (URLs totali, generate, pubblicate) e un bottone per creare un nuovo progetto.

### Navigazione Sidebar

```
Content Creator ▼
  └── [Nome Progetto] ▼
        ├── Dashboard
        ├── Import URLs
        ├── Risultati
        ├── ─── separator ───
        └── Impostazioni
  Connettori CMS (sempre visibile, no progetto)
```

---

## DATABASE

### 5 Tabelle

#### cc_projects
```sql
CREATE TABLE cc_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) DEFAULT NULL,
    default_content_type ENUM('product', 'category', 'article', 'service', 'custom') DEFAULT 'article',
    settings JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX idx_user (user_id)
);
```

#### cc_urls
```sql
CREATE TABLE cc_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    content_type ENUM('product', 'category', 'article', 'service', 'custom') DEFAULT 'article',
    main_keyword VARCHAR(255) DEFAULT NULL,
    secondary_keywords JSON DEFAULT NULL,
    intent VARCHAR(50) DEFAULT NULL,
    scraped_content LONGTEXT DEFAULT NULL,
    scraped_title VARCHAR(500) DEFAULT NULL,
    scraped_at TIMESTAMP NULL,
    generated_content LONGTEXT DEFAULT NULL,
    generated_at TIMESTAMP NULL,
    status ENUM('pending', 'scraped', 'generating', 'generated', 'approved', 'pushed', 'error') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    source ENUM('csv', 'sitemap', 'cms', 'manual', 'keyword_research') DEFAULT 'manual',
    job_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_job (job_id)
);
```

#### cc_connectors
```sql
CREATE TABLE cc_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('wordpress', 'shopify', 'prestashop', 'magento', 'custom') NOT NULL,
    site_url VARCHAR(500) NOT NULL,
    api_key VARCHAR(500) DEFAULT NULL,
    api_secret VARCHAR(500) DEFAULT NULL,
    settings JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (type)
);
```

#### cc_jobs
```sql
CREATE TABLE cc_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('scrape', 'generate', 'push') NOT NULL,
    status ENUM('pending', 'running', 'completed', 'error', 'cancelled') DEFAULT 'pending',
    items_requested INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500) DEFAULT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_project (project_id)
);
```

#### cc_operations_log
```sql
CREATE TABLE cc_operations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    url_id INT DEFAULT NULL,
    operation VARCHAR(100) NOT NULL,
    details JSON DEFAULT NULL,
    credits_used DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id)
);
```

---

## 5 CONTENT TYPES

| Tipo | Slug | Descrizione | Struttura HTML tipica |
|------|------|-------------|----------------------|
| **Prodotto** | `product` | Pagina prodotto e-commerce | H1 nome, descrizione, caratteristiche (ul/table), vantaggi |
| **Categoria** | `category` | Pagina categoria/listing | H1 categoria, intro, sottocategorie (H2), guida acquisto |
| **Articolo** | `article` | Articolo blog/informativo | H1 titolo, intro, sezioni H2/H3, conclusione |
| **Servizio** | `service` | Pagina servizio aziendale | H1 servizio, benefici, processo, FAQ, CTA |
| **Personalizzato** | `custom` | Tipo libero con istruzioni | Struttura definita dall'utente nel prompt |

Il prompt AI varia in base al `content_type` per generare HTML appropriato alla tipologia di pagina.

---

## 5 IMPORT METHODS

| Metodo | Source | Servizio | Endpoint |
|--------|--------|----------|----------|
| **CSV** | `csv` | `CsvImportService` | `POST /content-creator/projects/{id}/import/csv` |
| **Sitemap** | `sitemap` | `SitemapService` | `POST /content-creator/projects/{id}/import/sitemap` |
| **CMS** | `cms` | Connector fetch | `POST /content-creator/projects/{id}/import/cms` |
| **Manuale** | `manual` | Form input | `POST /content-creator/projects/{id}/import/store` |
| **Keyword Research** | `keyword_research` | kr_clusters data | `POST /content-creator/projects/{id}/import/keyword-research` |

### CSV Import
Usa `CsvImportService` condiviso. Supporta URL + keyword opzionale. Delimiter auto-detect.

### Sitemap Import
Usa `SitemapService` condiviso. Discovery da robots.txt, filtro URL pattern, preview.

### CMS Import
Fetch items dal CMS connesso (connector configurato). Importa URL + titolo + contenuto esistente come contesto per la rigenerazione.

### Manual Import
Form con textarea per inserire URL (una per riga), con keyword opzionale separata da tab/virgola.

### Keyword Research Import
Riceve cluster da `kr_clusters` (modulo keyword-research). Ogni cluster diventa una cc_url con:
- `main_keyword` = keyword principale del cluster
- `secondary_keywords` = JSON array delle keyword secondarie
- `intent` = intent del cluster
- `source` = `keyword_research`

---

## FLOW OPERATIVO

```
1. Import URLs → cc_urls (status: pending)
       ↓
2. [Opzionale] Scrape per contesto → cc_urls.scraped_content (status: scraped)
       ↓
3. AI Generate HTML → cc_urls.generated_content (status: generated)
       ↓
4. Review/Approve → (status: approved)
       ↓
5. Push to CMS  oppure  Export CSV → (status: pushed)
```

Ogni fase puo' essere eseguita in batch tramite SSE streaming con job tracking.

---

## CMS CONNECTORS

### WordPress
- **Plugin:** `seo-toolkit-connector` (stesso del modulo ai-content)
- **Header auth:** `X-SEO-Toolkit-Key: {api_key}`
- **Endpoints:**
  - `GET /wp-json/seo-toolkit/v1/all-content` - Fetch pagine
  - `POST /wp-json/seo-toolkit/v1/update-content` - Push contenuto generato

### Shopify
- **Auth:** API nativa Shopify (Admin API)
- **Endpoints:** Products, Pages, Collections, Articles (blog)

### PrestaShop
- **Plugin:** `seo-toolkit-connector` per PrestaShop
- **Auth:** `X-SEO-Toolkit-Key` header
- **Endpoints:** Products, Categories, CMS Pages

### Magento
- **Plugin:** `seo-toolkit-connector` per Magento
- **Auth:** `X-SEO-Toolkit-Key` header
- **Endpoints:** Products, Categories, CMS Blocks/Pages

### Custom API
- **Config:** URL base + header auth personalizzabili
- **Endpoints:** GET per fetch, POST/PUT per push
- **Formato:** JSON con campi mappabili (url, title, content)

---

## CONTROLLERS

### ProjectController
- `index()` - Lista progetti utente
- `create()` / `store()` - Nuovo progetto
- `show(int $id)` - Dashboard progetto con statistiche
- `edit(int $id)` / `update(int $id)` - Modifica progetto
- `delete(int $id)` - Elimina progetto (cascading)

### UrlController
- `index(int $projectId)` - Lista URL con filtri status/content_type
- `import(int $projectId)` - Pagina import con tabs (CSV/Sitemap/CMS/Manual/KR)
- `importCsv(int $projectId)` - POST import CSV
- `importSitemap(int $projectId)` - POST import sitemap
- `importCms(int $projectId)` - POST import da CMS connesso
- `store(int $projectId)` - POST import manuale
- `importKeywordResearch(int $projectId)` - POST import da KR clusters
- `delete(int $projectId, int $urlId)` - Elimina singola URL
- `bulkDelete(int $projectId)` - Elimina URL selezionate

### GeneratorController
- `startScrapeJob(int $projectId)` - POST: crea job scrape, ritorna job_id
- `scrapeStream(int $projectId)` - GET SSE: scraping real-time
- `startGenerateJob(int $projectId)` - POST: crea job generate, ritorna job_id
- `generateStream(int $projectId)` - GET SSE: generazione AI real-time
- `jobStatus(int $projectId)` - GET: polling fallback
- `cancelJob(int $projectId)` - POST: annulla job

### ExportController
- `exportCsv(int $projectId)` - GET: export CSV con URL + contenuto generato
- `startPushJob(int $projectId)` - POST: crea job push to CMS
- `pushStream(int $projectId)` - GET SSE: push real-time
- `pushStatus(int $projectId)` - GET: polling fallback push

### ConnectorController
- `index()` - Lista connettori utente (project-independent)
- `create()` / `store()` - Nuovo connettore
- `edit(int $id)` / `update(int $id)` - Modifica connettore
- `test(int $id)` - POST: test connessione CMS
- `delete(int $id)` - Elimina connettore

---

## MODELS

### Project (`cc_projects`)
- `find(id)`, `findByUser(userId)`, `allByUser(userId)`
- `allWithStats()` - con subquery per conteggio URL per status
- `create(data)`, `update(id, data)`, `delete(id)` - cascading delete

### Url (`cc_urls`)
- `find(id)`, `findByProject(projectId, filters)`
- `findPendingScrape(projectId, jobId)` - URL da scrappare
- `findPendingGenerate(projectId, jobId)` - URL da generare
- `findApproved(projectId)` - URL approvate per push
- `bulkImport(projectId, urls)` - Import bulk con dedup
- `updateStatus(id, status, data)` - Aggiorna status + dati
- `approve(id)` / `bulkApprove(ids)` - Approva per push
- `getStats(projectId)` - Conteggi per status

### Job (`cc_jobs`)
- `find(id)`, `findByProject(projectId)`
- `create(data)`, `updateProgress(id, completed, failed, currentItem)`
- `complete(id)`, `cancel(id)`, `fail(id, error)`
- `isCancelled(id)` - Check cancellazione
- `getJobResponse(id)` - Dati formattati per API

### OperationLog (`cc_operations_log`)
- `log(userId, operation, details)` - Log operazione
- `getByProject(projectId, limit)` - Storico operazioni
- `getByUser(userId, limit)` - Storico utente

---

## SERVICES

### ContentScraperService (module-local)

**File:** `modules/content-creator/services/ContentScraperService.php`

Wrapper locale che usa `ScraperService::scrape()` (servizio condiviso) per ottenere contenuto pulito via Readability. Aggiunge logica specifica del modulo:
- Salvataggio in `cc_urls.scraped_content` e `cc_urls.scraped_title`
- Update status a `scraped`
- Log operazione in `cc_operations_log`

```php
use Services\ScraperService;

$scraper = new ScraperService();
$result = $scraper->scrape($url);

if ($result['success']) {
    $urlModel->updateStatus($urlId, 'scraped', [
        'scraped_content' => $result['content'],
        'scraped_title' => $result['title'],
        'scraped_at' => date('Y-m-d H:i:s')
    ]);
}
```

### Connector Classes

**Directory:** `modules/content-creator/services/connectors/`

| Classe | CMS | File |
|--------|-----|------|
| `WordPressConnector` | WordPress | `WordPressConnector.php` |
| `ShopifyConnector` | Shopify | `ShopifyConnector.php` |
| `PrestaShopConnector` | PrestaShop | `PrestaShopConnector.php` |
| `MagentoConnector` | Magento | `MagentoConnector.php` |
| `CustomApiConnector` | Custom | `CustomApiConnector.php` |

Ogni connector implementa:
- `testConnection()` - Verifica connessione
- `fetchItems()` - Recupera lista pagine/prodotti
- `pushContent(url, content)` - Pubblica contenuto generato

---

## CREDITI

| Operazione | Chiave module.json | Default | Note |
|------------|--------------------|---------|------|
| Scrape URL | `cost_cc_scrape` | 1 | Per URL scrappata |
| Genera contenuto | `cost_cc_generate` | 3 | Per URL generata (AI call) |
| Push/Export | — | 0 | Sempre gratuito |

**Pattern:**
```php
$cost = Credits::getCost('cc_generate', 'content-creator');
if (!Credits::hasEnough($userId, $cost)) { /* errore */ }
// ... AI call ...
Database::reconnect();
Credits::consume($userId, $cost, 'cc_generate', 'content-creator', [...]);
```

---

## AI INTEGRATION

### AiService Usage

```php
use Services\AiService;

$ai = new AiService('content-creator');

if (!$ai->isConfigured()) {
    return ['error' => true, 'message' => 'AI non configurata'];
}

// Il prompt varia in base a content_type
$prompt = $this->buildPrompt($url, $contentType, $scrapedContent, $keywords);
$result = $ai->analyze($userId, $prompt, $context, 'content-creator');

Database::reconnect();
```

### Prompt per Content Type

Il prompt AI include:
- **Tipo contenuto** (product/category/article/service/custom)
- **Keyword principale** e secondarie (con intent)
- **Contenuto scrappato** (se disponibile, come contesto)
- **Istruzioni strutturali:** genera HTML body con H1, H2/H3, liste, tabelle dove appropriato
- **Lingua:** italiano (o configurabile per progetto)

### Output AI

HTML body strutturato:
```html
<h1>Titolo Pagina Ottimizzato</h1>
<p>Introduzione con keyword principale...</p>

<h2>Sezione Principale</h2>
<p>Contenuto dettagliato...</p>

<h3>Sotto-sezione</h3>
<ul>
    <li>Punto 1</li>
    <li>Punto 2</li>
</ul>

<!-- Struttura varia per content_type -->
```

---

## SSE PATTERN

3 stream SSE separati: scrape, generate, push. Tutti seguono lo stesso pattern.

**Punti critici (ordine nel controller):**
1. `ignore_user_abort(true)` - CRITICO: proxy SiteGround chiude SSE
2. `set_time_limit(0)` - Rimuove timeout PHP
3. `session_write_close()` PRIMA del loop (sblocca sessione)
4. `Database::reconnect()` dopo ogni chiamata API/AI
5. `if (ob_get_level()) ob_flush(); flush();` - Guard per server senza buffer
6. **Salvare risultati nel DB PRIMA dell'evento `completed`** - Se SSE cade, polling recupera

**Polling fallback:**
- Endpoint: `GET /content-creator/projects/{id}/job-status?job_id=X`
- Legge progresso e risultati dalla tabella `cc_jobs` + `cc_urls`
- Frontend auto-poll quando SSE si disconnette

**Eventi SSE (scrape stream):**

| Evento | Dati | Quando |
|--------|------|--------|
| `started` | total_items | Inizio scraping |
| `item_completed` | url, title, word_count, index | URL scrappata |
| `item_error` | url, error, index | Errore su URL |
| `completed` | total, success, failed | Fine scraping |
| `cancelled` | total, completed | Job annullato |

**Eventi SSE (generate stream):**

| Evento | Dati | Quando |
|--------|------|--------|
| `started` | total_items | Inizio generazione |
| `item_completed` | url, content_type, word_count, index | Contenuto generato |
| `item_error` | url, error, index | Errore generazione |
| `completed` | total, success, failed | Fine generazione |
| `cancelled` | total, completed | Job annullato |

**Eventi SSE (push stream):**

| Evento | Dati | Quando |
|--------|------|--------|
| `started` | total_items | Inizio push |
| `item_completed` | url, cms_response, index | Push riuscito |
| `item_error` | url, error, index | Errore push |
| `completed` | total, success, failed | Fine push |

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
    this.startPolling(); // recupera dal DB
};
```

---

## INTEGRATION: Keyword Research -> Content Creator

Il modulo keyword-research (modalita' Architettura Sito) puo' inviare cluster al Content Creator.

**Flusso:**
1. Utente in KR seleziona cluster da inviare a Content Creator
2. POST a `/content-creator/projects/{id}/import/keyword-research`
3. Ogni cluster diventa una `cc_url` con:
   - `url` = `suggested_url` dal cluster (o vuoto)
   - `main_keyword` = keyword principale
   - `secondary_keywords` = JSON array keyword secondarie
   - `intent` = intent del cluster
   - `content_type` = mappato da intent (transactional -> product/service, informational -> article)
   - `source` = `keyword_research`

---

## ROUTES

```
# Progetti
GET  /content-creator                                    # Lista progetti
GET  /content-creator/projects/create                    # Form nuovo progetto
POST /content-creator/projects/store                     # Salva progetto
GET  /content-creator/projects/{id}                      # Dashboard progetto
GET  /content-creator/projects/{id}/edit                 # Form modifica
POST /content-creator/projects/{id}/update               # Salva modifiche
POST /content-creator/projects/{id}/delete               # Elimina progetto

# Import URLs
GET  /content-creator/projects/{id}/import               # Pagina import (tabs)
POST /content-creator/projects/{id}/import/csv           # Import CSV
POST /content-creator/projects/{id}/import/sitemap       # Import Sitemap
POST /content-creator/projects/{id}/import/cms           # Import da CMS
POST /content-creator/projects/{id}/import/store         # Import manuale
POST /content-creator/projects/{id}/import/keyword-research  # Import da KR

# URLs Management
GET  /content-creator/projects/{id}/urls                 # Lista URLs
POST /content-creator/projects/{id}/urls/{urlId}/delete  # Elimina URL
POST /content-creator/projects/{id}/urls/bulk-delete     # Elimina bulk

# Risultati / Review
GET  /content-creator/projects/{id}/results              # Risultati generati
GET  /content-creator/projects/{id}/results/{urlId}      # Preview singolo
POST /content-creator/projects/{id}/results/{urlId}/approve  # Approva

# SSE Streams
POST /content-creator/projects/{id}/scrape/start         # Avvia scrape job
GET  /content-creator/projects/{id}/scrape/stream        # SSE scraping
POST /content-creator/projects/{id}/generate/start       # Avvia generate job
GET  /content-creator/projects/{id}/generate/stream      # SSE generazione
POST /content-creator/projects/{id}/push/start           # Avvia push job
GET  /content-creator/projects/{id}/push/stream          # SSE push
GET  /content-creator/projects/{id}/job-status           # Polling fallback
POST /content-creator/projects/{id}/cancel-job           # Annulla job

# Export
GET  /content-creator/projects/{id}/export/csv           # Export CSV

# Connettori CMS (project-independent)
GET  /content-creator/connectors                         # Lista connettori
GET  /content-creator/connectors/create                  # Form nuovo
POST /content-creator/connectors/store                   # Salva connettore
GET  /content-creator/connectors/{id}/edit               # Form modifica
POST /content-creator/connectors/{id}/update             # Salva modifiche
POST /content-creator/connectors/{id}/test               # Test connessione
POST /content-creator/connectors/{id}/delete             # Elimina connettore

# API (Sitemap)
POST /content-creator/api/sitemap-discover               # Discover sitemaps
POST /content-creator/api/sitemap                        # Parse/import sitemap
```

---

## VIEWS

| Directory | File | Descrizione |
|-----------|------|-------------|
| `views/projects/` | `index.php` | Lista progetti con stats |
| `views/projects/` | `create.php` | Form creazione progetto |
| `views/projects/` | `dashboard.php` | Dashboard progetto con contatori e azioni |
| `views/urls/` | `index.php` | Lista URLs con filtri e azioni bulk |
| `views/urls/` | `import.php` | Tabs import (CSV/Sitemap/CMS/Manual/KR) |
| `views/results/` | `index.php` | Griglia risultati generati con preview |
| `views/results/` | `preview.php` | Preview HTML generato con approve/reject |
| `views/connectors/` | `index.php` | Lista connettori CMS |
| `views/connectors/` | `create.php` | Form creazione connettore |
| `views/connectors/` | `edit.php` | Form modifica connettore |

---

## FILE DI RIFERIMENTO

| File | Descrizione |
|------|-------------|
| `controllers/ProjectController.php` | CRUD progetti |
| `controllers/UrlController.php` | Import URLs (5 metodi) + gestione |
| `controllers/GeneratorController.php` | SSE scrape + generate + job management |
| `controllers/ExportController.php` | Export CSV + push to CMS |
| `controllers/ConnectorController.php` | CRUD connettori CMS + test connessione |
| `models/Project.php` | Progetto con stats aggregate |
| `models/Url.php` | URL con status workflow + bulk ops |
| `models/Job.php` | Job tracking per SSE (scrape/generate/push) |
| `models/OperationLog.php` | Log operazioni per audit |
| `services/ContentScraperService.php` | Wrapper ScraperService per modulo |
| `services/connectors/WordPressConnector.php` | Connettore WordPress |
| `services/connectors/ShopifyConnector.php` | Connettore Shopify |
| `services/connectors/PrestaShopConnector.php` | Connettore PrestaShop |
| `services/connectors/MagentoConnector.php` | Connettore Magento |
| `services/connectors/CustomApiConnector.php` | Connettore API custom |
| `module.json` | Settings schema con groups e costs |

---

## TROUBLESHOOTING

| Problema | Causa | Soluzione |
|----------|-------|-----------|
| "AI non configurata" | ai_provider/ai_model mancanti | Admin > Moduli > Content Creator > Impostazioni |
| SSE non invia eventi | Output buffering | `if (ob_get_level()) ob_flush(); flush();` |
| SSE blocca pagine | Sessione non chiusa | `session_write_close()` prima del loop |
| SSE "Connessione persa" | Proxy SiteGround chiude SSE | `ignore_user_abort(true)` + polling fallback |
| SSE dati persi dopo disconnect | Risultati solo via SSE | Salvare nel DB PRIMA dell'evento `completed` |
| "MySQL gone away" dopo AI | Connessione scaduta | `Database::reconnect()` prima di salvare |
| Scraping fallisce | Sito protetto/JS-rendered | Verificare con ScraperService::scrape() manualmente |
| Push CMS fallisce | Connettore non configurato | Verificare connessione con test endpoint |
| "Errore di connessione" | Campo CSRF sbagliato | `_csrf_token` (con underscore!), NON `csrf_token` |
| Import KR vuoto | Nessun cluster selezionato | Verificare selezione in keyword-research |
| `getModuleSetting()` undefined | Metodo sbagliato | `ModuleLoader::getSetting(slug, key, default)` |
| Modulo non visibile in prod | Tabelle/modulo non creati | Creare tabelle + INSERT in `modules` |

---

## DEPLOY PRODUZIONE

```bash
# 1. Push codice
git push origin main

# 2. SSH e pull
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html && git pull origin main

# 3. Creare tabelle (prima volta)
mysql -u USER -pPASS DB < modules/content-creator/database/schema.sql

# 4. Attivare modulo (prima volta)
mysql -u USER -pPASS DB -e "INSERT INTO modules (slug, name, description, version, is_active) VALUES ('content-creator', 'Content Creator', 'Creazione contenuti HTML con AI e push CMS', '1.0.0', 1);"
```

---

*Documento agente - 2026-02-13*
