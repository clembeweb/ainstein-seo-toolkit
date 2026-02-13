# CONTENT CREATOR - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `content-creator` |
| **Prefisso DB** | `cc_` |
| **Stato** | 90% (implementato, da testare) |
| **Ultimo update** | 2026-02-12 |

Modulo per generazione massiva di contenuti HTML completi di pagina (body content) tramite AI per e-commerce, servizi, articoli. Output: HTML body content con H1, H2, paragrafi, liste, FAQ.

> **Nota:** Questo modulo NON genera meta tag (titoli/descrizioni SEO). La generazione meta tag e' gestita dal modulo `ai-content`.

---

## Architettura

```
modules/content-creator/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php      # CRUD progetti
│   ├── UrlController.php          # Import URL (CSV, Sitemap, CMS, Manual, KR)
│   ├── GeneratorController.php    # Scraping SSE + AI Generation SSE
│   ├── ConnectorController.php    # Connettori CMS + plugin download
│   └── ExportController.php       # Export CSV + CMS Push SSE
├── models/
│   ├── Project.php                # CRUD cc_projects
│   ├── Url.php                    # CRUD cc_urls + status workflow
│   ├── Connector.php              # CRUD cc_connectors
│   ├── Job.php                    # CRUD cc_jobs (scrape/generate/cms_push)
│   └── OperationLog.php           # CRUD cc_operations_log
├── services/
│   ├── ContentScraperService.php  # Wrapper ScraperService per contesto AI
│   └── connectors/
│       ├── ConnectorInterface.php # Interfaccia comune CMS
│       ├── WordPressConnector.php  # Plugin seo-toolkit-connector + API
│       ├── ShopifyConnector.php    # API nativa Shopify
│       ├── PrestaShopConnector.php # Plugin seotoolkitconnector
│       └── MagentoConnector.php    # Extension SeoToolkit_Connector
└── views/
    ├── projects/
    │   ├── index.php              # Lista progetti (card grid)
    │   ├── create.php             # Form creazione progetto
    │   ├── show.php               # Dashboard progetto
    │   └── settings.php           # Impostazioni progetto
    ├── urls/
    │   ├── import.php             # Form import (5 tab)
    │   └── preview.php            # Preview singola URL
    ├── results/
    │   └── index.php              # Risultati generazione
    └── connectors/
        ├── index.php              # Lista connettori
        └── create.php             # Form creazione connettore
```

---

## Database Schema

### cc_projects

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT | Utente proprietario |
| name | VARCHAR(255) | Nome progetto |
| description | TEXT | Descrizione |
| base_url | VARCHAR(500) | URL base sito |
| content_type | ENUM | product, category, article, service, custom |
| language | VARCHAR(10) | Lingua contenuto (default: it) |
| tone | VARCHAR(50) | Tono di scrittura (default: professionale) |
| connector_id | INT | FK a cc_connectors (opzionale) |
| ai_settings | JSON | `{min_words: int, custom_prompt: string}` |
| status | ENUM | active, paused, archived |
| created_at | TIMESTAMP | Data creazione |
| updated_at | TIMESTAMP | Data aggiornamento |

### cc_urls

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| project_id | INT | FK a cc_projects |
| user_id | INT | Utente |
| url | VARCHAR(700) | URL pagina |
| slug | VARCHAR(500) | Slug estratto |
| keyword | VARCHAR(255) | Keyword target |
| secondary_keywords | JSON | Keyword secondarie |
| intent | VARCHAR(50) | Search intent |
| source_type | ENUM | manual, csv, sitemap, cms, keyword_research |
| category | VARCHAR(255) | Categoria |
| scraped_title | VARCHAR(500) | Titolo scraping |
| scraped_h1 | VARCHAR(500) | H1 scraping |
| scraped_content | LONGTEXT | Contenuto scraping (contesto AI) |
| scraped_at | DATETIME | Data scraping |
| ai_content | LONGTEXT | Contenuto HTML generato da AI |
| ai_h1 | VARCHAR(500) | H1 generato da AI |
| ai_word_count | INT | Conteggio parole contenuto AI |
| cms_entity_id | VARCHAR(100) | ID entita CMS |
| cms_entity_type | VARCHAR(50) | Tipo entita CMS |
| status | ENUM | pending, scraped, generating, generated, approved, rejected, published, error |
| error_message | TEXT | Messaggio errore |
| created_at | TIMESTAMP | Data creazione |

### cc_connectors

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT | Utente |
| name | VARCHAR(255) | Nome connettore |
| type | ENUM | wordpress, shopify, prestashop, magento, custom_api |
| config | JSON | Configurazione connessione |
| api_key | VARCHAR(100) | API key connettore |
| is_active | BOOLEAN | Stato attivo/disattivo |
| last_sync_at | DATETIME | Ultimo sync |
| categories_cache | JSON | Cache categorie CMS |
| seo_plugin | VARCHAR(50) | Plugin SEO installato |
| created_at | TIMESTAMP | Data creazione |

### cc_jobs

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| project_id | INT | FK a cc_projects |
| user_id | INT | Utente |
| type | ENUM | scrape, generate, cms_push |
| status | ENUM | pending, running, completed, error, cancelled |
| items_requested | INT | Totale items da processare |
| items_completed | INT | Items completati |
| items_failed | INT | Items falliti |
| current_item | VARCHAR(500) | Item in elaborazione |
| current_item_id | INT | ID item in elaborazione |
| started_at | TIMESTAMP | Inizio elaborazione |
| completed_at | TIMESTAMP | Fine elaborazione |
| created_at | TIMESTAMP | Data creazione |

### cc_operations_log

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT | Utente |
| project_id | INT | FK a cc_projects |
| operation | VARCHAR(50) | Tipo operazione |
| credits_used | INT | Crediti consumati |
| status | ENUM | success, error |
| details | JSON | Dettagli operazione |
| created_at | TIMESTAMP | Data creazione |

### SQL Schema

```sql
CREATE TABLE cc_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    base_url VARCHAR(500),
    content_type ENUM('product','category','article','service','custom') NOT NULL DEFAULT 'product',
    language VARCHAR(10) DEFAULT 'it',
    tone VARCHAR(50) DEFAULT 'professionale',
    connector_id INT DEFAULT NULL,
    ai_settings JSON DEFAULT NULL,
    status ENUM('active','paused','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cc_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    url VARCHAR(700) NOT NULL,
    slug VARCHAR(500),
    keyword VARCHAR(255),
    secondary_keywords JSON DEFAULT NULL,
    intent VARCHAR(50) DEFAULT NULL,
    source_type ENUM('manual','csv','sitemap','cms','keyword_research') DEFAULT 'manual',
    category VARCHAR(255),
    scraped_title VARCHAR(500),
    scraped_h1 VARCHAR(500),
    scraped_content LONGTEXT,
    scraped_at DATETIME,
    ai_content LONGTEXT DEFAULT NULL,
    ai_h1 VARCHAR(500) DEFAULT NULL,
    ai_word_count INT DEFAULT 0,
    cms_entity_id VARCHAR(100),
    cms_entity_type VARCHAR(50),
    status ENUM('pending','scraped','generating','generated','approved','rejected','published','error') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE
);

CREATE TABLE cc_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('wordpress','shopify','prestashop','magento','custom_api') NOT NULL,
    config JSON NOT NULL,
    api_key VARCHAR(100) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at DATETIME,
    categories_cache JSON DEFAULT NULL,
    seo_plugin VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cc_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('scrape','generate','cms_push') NOT NULL,
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    items_requested INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500),
    current_item_id INT,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_project (project_id)
);

CREATE TABLE cc_operations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    operation VARCHAR(50) NOT NULL,
    credits_used INT DEFAULT 0,
    status ENUM('success','error') NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Flow Operativo

```
IMPORT URL --> (opt) SCRAPING --> AI GENERATION --> REVIEW --> EXPORT / CMS PUSH
  ^
  | Sources: CSV, Sitemap, CMS, Manual, Keyword Research
```

### 1. Import URL (5 modalita)

| Modalita | Descrizione | Source Type |
|----------|-------------|------------|
| **CSV** | Upload file con colonne url, keyword, category | `csv` |
| **Sitemap XML** | Parsing sitemap con filtro pattern URL | `sitemap` |
| **CMS** | Import da WordPress/Shopify/PrestaShop/Magento via connettore | `cms` |
| **Manuale** | Inserimento singola URL con keyword | `manual` |
| **Keyword Research** | Cluster architettura sito da modulo KR | `keyword_research` |

**Import da Keyword Research:**
- Route sorgente: `POST /keyword-research/project/{id}/architecture/{researchId}/send-to-content-creator`
- Mapping: `main_keyword` -> `keyword`, `keywords[]` -> `secondary_keywords`, `intent`, cluster name -> `category`
- Controllo duplicati per URL

### 2. Scraping (opzionale)

- Via `ScraperService` con Readability (servizio condiviso)
- Estrae: title, H1, contenuto testuale per contesto AI
- SSE real-time con polling fallback
- **Costo:** 1 credito per URL

### 3. AI Generation (core feature)

- Prompt specifico per `content_type` (product, category, article, service, custom)
- Output: HTML body content tra marcatori ` ```html `
- H1 estratto separatamente, word count calcolato
- Utilizza `secondary_keywords` e `intent` se disponibili
- Parametro `min_words` configurabile per content_type via `ai_settings`
- SSE real-time con polling fallback
- **Costo:** 3 crediti per URL

### 4. Review

- Preview HTML del contenuto con conteggio parole
- Edit manuale H1 e contenuto
- Workflow Approve/Reject per singola URL
- Bulk approve per approvazione massiva

### 5. Export

- **CSV**: esporta url, slug, keyword, ai_h1, ai_content, ai_word_count, status
- **CMS Push**: invio contenuto a CMS via connettore SSE (`connector.updateItem` con content + h1)

---

## AI Prompt per Content Type

| Content Type | Struttura Output |
|--------------|-----------------|
| **product** | Descrizione prodotto, caratteristiche, vantaggi, specifiche tecniche, FAQ |
| **category** | Descrizione categoria, guida all'acquisto, sottocategorie |
| **article** | Articolo completo con H2, introduzione, sviluppo, conclusione |
| **service** | Panoramica servizio, benefici, processo, FAQ, CTA |
| **custom** | Contenuto generico basato su keyword e contesto |

---

## CMS Connectors

### Connettori Disponibili (4 + Custom API)

| Tipo | Autenticazione | Note |
|------|---------------|------|
| **WordPress** | Plugin `seo-toolkit-connector` + header `X-SEO-Toolkit-Key` | Endpoints: `/wp-json/seo-toolkit/v1/` |
| **Shopify** | API nativa | Campo `body_html` per products/pages |
| **PrestaShop** | Plugin `seotoolkitconnector` | Endpoints via front controller |
| **Magento 2** | Extension `SeoToolkit_Connector` | REST API endpoints |
| **Custom API** | Configurabile URL + headers | Flessibile |

### Data Contract

```php
// Dati inviati al connettore per ogni URL
[
    'content' => 'HTML body content',
    'h1'      => 'Titolo H1'
]
```

### Plugin Download

I plugin CMS sono scaricabili direttamente dall'interfaccia connettori via `ConnectorController::downloadPlugin()`.

---

## Crediti

| Azione | Costo | Note |
|--------|-------|------|
| Scrape URL | 1 | Per singola URL |
| AI generate content | 3 | Per singola URL |
| CMS push | 0 | Gratuito |
| CSV export | 0 | Gratuito |

---

## Routes

```php
// === Progetti ===
GET  /content-creator                                        # Lista progetti
GET  /content-creator/projects/create                        # Form creazione
GET  /content-creator/projects/{id}                          # Dashboard progetto
GET  /content-creator/projects/{id}/settings                 # Impostazioni
POST /content-creator/projects/{id}/update                   # Aggiorna progetto
POST /content-creator/projects/{id}/delete                   # Elimina progetto

// === Import URL (5 modalita) ===
GET  /content-creator/projects/{id}/import                   # Form import (tabs)
POST /content-creator/projects/{id}/import/csv               # Import CSV
POST /content-creator/projects/{id}/import/sitemap           # Import Sitemap
POST /content-creator/projects/{id}/import/cms               # Import da CMS
POST /content-creator/projects/{id}/import/manual            # Import manuale
POST /content-creator/projects/{id}/import/keyword-research  # Import da KR

// === URL Management ===
POST /content-creator/projects/{id}/urls/bulk-approve        # Approvazione massiva
POST /content-creator/projects/{id}/urls/bulk-delete         # Eliminazione massiva
POST /content-creator/projects/{id}/urls/{urlId}/update      # Modifica URL
POST /content-creator/projects/{id}/urls/{urlId}/approve     # Approva
POST /content-creator/projects/{id}/urls/{urlId}/reject      # Rifiuta
POST /content-creator/projects/{id}/urls/{urlId}/delete      # Elimina

// === SSE Scraping ===
POST /content-creator/projects/{id}/start-scrape-job         # Avvia job scraping
GET  /content-creator/projects/{id}/scrape-stream            # SSE streaming
GET  /content-creator/projects/{id}/scrape-job-status        # Polling fallback
POST /content-creator/projects/{id}/cancel-scrape-job        # Annulla scraping

// === SSE AI Generation ===
POST /content-creator/projects/{id}/start-generate-job       # Avvia job generazione
GET  /content-creator/projects/{id}/generate-stream          # SSE streaming
GET  /content-creator/projects/{id}/generate-job-status      # Polling fallback
POST /content-creator/projects/{id}/cancel-generate-job      # Annulla generazione

// === Risultati ===
GET  /content-creator/projects/{id}/results                  # Lista risultati

// === Export & CMS Push ===
GET  /content-creator/projects/{id}/export/csv               # Export CSV
POST /content-creator/projects/{id}/start-push-job           # Avvia push CMS
GET  /content-creator/projects/{id}/push-stream              # SSE push streaming
GET  /content-creator/projects/{id}/push-job-status          # Polling push fallback
POST /content-creator/projects/{id}/cancel-push-job          # Annulla push

// === URL Preview ===
GET  /content-creator/projects/{id}/urls/{urlId}             # Preview singola URL

// === Connettori CMS ===
GET  /content-creator/connectors                             # Lista connettori
GET  /content-creator/connectors/create                      # Form creazione
POST /content-creator/connectors                             # Store connettore
POST /content-creator/connectors/{id}/test                   # Test connessione
POST /content-creator/connectors/{id}/delete                 # Elimina connettore
POST /content-creator/connectors/{id}/toggle                 # Attiva/disattiva
GET  /content-creator/connectors/download-plugin/{type}      # Download plugin CMS
POST /content-creator/connectors/{id}/sync-categories        # Sync categorie CMS
GET  /content-creator/connectors/{id}/items                  # Lista items CMS
```

---

## SSE Pattern

Il modulo usa 3 job SSE distinti, tutti con lo stesso pattern:

| Job | Controller | Tipo cc_jobs |
|-----|-----------|--------------|
| Scraping URL | GeneratorController | `scrape` |
| Generazione AI | GeneratorController | `generate` |
| Push CMS | ExportController | `cms_push` |

### Pattern Backend

```php
// 1. POST /start-{type}-job -> Crea job, ritorna job_id
// 2. GET /{type}-stream -> SSE real-time
// 3. GET /{type}-job-status -> Polling fallback
// 4. POST /cancel-{type}-job -> Annulla job

public function processStream(int $projectId): void
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    ignore_user_abort(true);   // Proxy SiteGround chiude SSE
    set_time_limit(0);
    session_write_close();     // Sblocca sessione

    while ($item = $queue->getNext($jobId)) {
        Database::reconnect();  // Dopo ogni chiamata API/AI

        if ($jobModel->isCancelled($jobId)) {
            $this->sendEvent('cancelled', [...]);
            break;
        }

        // Elabora item...
        $this->sendEvent('item_completed', [...]);
    }

    $this->sendEvent('completed', [...]);
}
```

### Eventi SSE Standard

- `started` - Job avviato con totale items
- `progress` - Aggiornamento progresso (current_item, percent)
- `item_completed` - Singolo item completato con risultati
- `item_error` - Errore su singolo item
- `completed` - Job terminato con successo
- `cancelled` - Job annullato dall'utente

### Polling Fallback

Quando il proxy SiteGround chiude la connessione SSE, il frontend passa automaticamente a polling via endpoint `GET /{type}-job-status` che legge lo stato dal DB.

---

## Integrazione Keyword Research

Il modulo Keyword Research (architettura sito) puo inviare cluster direttamente al Content Creator.

| Aspetto | Dettaglio |
|---------|-----------|
| **Route sorgente** | `POST /keyword-research/project/{id}/architecture/{researchId}/send-to-content-creator` |
| **Mapping main_keyword** | `cc_urls.keyword` |
| **Mapping keywords[]** | `cc_urls.secondary_keywords` (JSON) |
| **Mapping intent** | `cc_urls.intent` |
| **Mapping cluster name** | `cc_urls.category` |
| **Source type** | `keyword_research` |
| **Duplicati** | Controllo per URL, skip se gia presente |

---

## Checklist Implementazione

- [x] Database schema (5 tabelle)
- [x] Models CRUD (Project, Url, Connector, Job, OperationLog)
- [x] ProjectController (CRUD + settings)
- [x] UrlController + import (CSV, Sitemap, CMS, Manual, KR)
- [x] GeneratorController + AI prompts per content_type
- [x] SSE scraping + generation con polling fallback
- [x] ConnectorController + plugin download
- [x] WordPress Connector (plugin `seo-toolkit-connector`)
- [x] Shopify Connector (API nativa)
- [x] PrestaShop Connector
- [x] Magento Connector
- [x] ExportController (CSV + CMS Push SSE)
- [x] Views complete (projects, urls, results, connectors)
- [x] Import da Keyword Research
- [x] Deploy produzione + migration
- [ ] Test end-to-end browser
- [ ] Test CMS push con plugin reali

---

## Note Implementazione

1. **AiService centralizzato**: `new AiService('content-creator')` - mai curl diretto
2. **ScraperService condiviso**: `ScraperService::scrape()` per lo scraping opzionale di contesto AI
3. **SSE robusto**: `ignore_user_abort(true)`, `set_time_limit(0)`, `session_write_close()` prima del loop
4. **Database::reconnect()**: dopo ogni chiamata AI nel loop SSE
5. **Polling fallback**: endpoint GET che legge dal DB quando SSE si disconnette (proxy timeout)
6. **WordPress**: usa plugin dedicato `seo-toolkit-connector` (NON Application Passwords)
7. **Output AI**: HTML body content tra marcatori ` ```html `, H1 estratto separatamente
8. **Risultati nel DB**: salvati PRIMA dell'evento `completed` SSE (per polling fallback)
9. **ob_flush() guard**: `if (ob_get_level()) ob_flush()` per evitare errori su server senza buffer
10. **Content type prompt**: ogni content_type ha un prompt AI specifico ottimizzato per quel tipo di pagina

---

*Spec aggiornata - 2026-02-12*
