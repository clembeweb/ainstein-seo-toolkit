# SEO Audit via WordPress Plugin (Senza Scraping)

**Data:** 2026-02-23
**Fase:** 1 di 3
**Modulo target:** seo-audit
**Dipendenze:** Global Projects, Plugin WordPress

---

## Obiettivo

Permettere al modulo SEO Audit di analizzare un sito WordPress senza fare scraping, sfruttando il plugin WP gia installato. Il plugin WP estrae tutti i dati SEO direttamente dal database WordPress e li espone via REST API. L'audit li riceve pre-digeriti e li analizza con l'IssueDetector esistente.

## Decisioni di design

1. **Modulo target**: seo-audit (non internal-links, non content-creator)
2. **Approccio dati**: Endpoint WP bulk dedicato `/seo-audit` (non parsing HTML lato Ainstein)
3. **Connettori**: Nuova tabella `project_connectors` a livello core, legata ai Global Projects
4. **Flusso UX**: Nuovo tab "WordPress" nella pagina import URLs dell'audit
5. **Crawl**: Nessun crawl separato - import + analisi in un unico step via SSE
6. **Scope issue**: Tutte le 10 categorie; issue non verificabili via CMS segnalate o omesse
7. **Connettori esistenti (cc_connectors)**: Non migrati in Fase 1. Content-creator continua a usarli.

---

## Architettura

```
Global Project Dashboard
    |
    +-- Sezione "Connessione CMS"
    |       |-- Configura connettore WP (URL + API key)
    |       |-- Test connessione
    |       |-- Download plugin WP
    |       `-- Badge "WP disponibile" sui moduli compatibili
    |
    +-- Modulo SEO Audit
            |
            +-- Import URLs
            |       |-- Tab Sitemap (esistente)
            |       |-- Tab Spider (esistente)
            |       |-- Tab Manuale (esistente)
            |       `-- Tab WordPress (NUOVO)
            |               |-- Seleziona tipi (post/pagine/prodotti)
            |               |-- "Importa e Analizza" (SSE)
            |               `-- Fetch API WP -> sa_pages -> IssueDetector -> health score
            |
            +-- Dashboard (esistente, funziona identico)
            +-- Issues (esistente, funziona identico)
            `-- Action Plan AI (esistente, funziona identico)
```

---

## Schema Database

### Nuova tabella: `project_connectors`

```sql
CREATE TABLE project_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('wordpress','shopify','prestashop','magento') NOT NULL,
    name VARCHAR(255) NOT NULL,
    config JSON NOT NULL,                 -- { "url": "https://...", "api_key": "stk_..." }
    is_active TINYINT(1) DEFAULT 1,
    last_test_at DATETIME NULL,
    last_test_status ENUM('success','error') NULL,
    last_test_message VARCHAR(500) NULL,
    seo_plugin VARCHAR(50) NULL,          -- yoast/rankmath/aioseo/none
    wp_version VARCHAR(20) NULL,
    plugin_version VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### Modifica: `sa_pages`

```sql
ALTER TABLE sa_pages ADD COLUMN source ENUM('crawl','wordpress') DEFAULT 'crawl' AFTER session_id;
ALTER TABLE sa_pages ADD COLUMN cms_entity_id INT NULL AFTER source;
ALTER TABLE sa_pages ADD COLUMN cms_entity_type VARCHAR(50) NULL AFTER cms_entity_id;
```

---

## Plugin WordPress - Endpoint `/seo-audit`

### Request

```
GET /wp-json/seo-toolkit/v1/seo-audit
Headers: X-SEO-Toolkit-Key: stk_abc123...
Params:
  - per_page: 50 (default, max 100)
  - page: 1
  - type: post,page,product (comma-separated, default: post,page)
```

### Response

```json
{
  "total": 156,
  "total_pages": 4,
  "current_page": 1,
  "per_page": 50,
  "site_info": {
    "name": "My Blog",
    "url": "https://myblog.com",
    "wp_version": "6.4",
    "seo_plugin": "yoast",
    "plugin_version": "1.1.0",
    "has_ssl": true,
    "has_robots_txt": true,
    "has_sitemap": true,
    "robots_txt_content": "User-agent: *\nDisallow: /wp-admin/\nSitemap: https://myblog.com/sitemap.xml"
  },
  "pages": [
    {
      "id": 42,
      "type": "post",
      "status": "publish",
      "url": "https://myblog.com/post-esempio/",
      "title_tag": "Titolo SEO | My Blog",
      "meta_description": "La meta description del post...",
      "canonical": "https://myblog.com/post-esempio/",
      "robots_meta": "index, follow",
      "og_title": "Titolo Open Graph",
      "og_description": "Descrizione OG",
      "og_image": "https://myblog.com/wp-content/uploads/img.jpg",
      "headings": [
        {"level": 1, "text": "Titolo H1 del post"},
        {"level": 2, "text": "Primo sottotitolo"},
        {"level": 3, "text": "Dettaglio"}
      ],
      "images": [
        {"src": "https://myblog.com/wp-content/uploads/foto.jpg", "alt": "Descrizione foto", "width": 800, "height": 600}
      ],
      "internal_links": [
        {"url": "https://myblog.com/altro-post/", "anchor": "leggi anche questo", "nofollow": false}
      ],
      "external_links": [
        {"url": "https://example.com", "anchor": "fonte esterna", "nofollow": true}
      ],
      "schema_json_ld": [
        {"@type": "Article", "@context": "https://schema.org", "headline": "..."}
      ],
      "word_count": 1250,
      "modified_at": "2026-02-20T10:30:00"
    }
  ]
}
```

### Dati estratti dal plugin WP (lato server)

| Dato | Metodo WP | Note |
|------|-----------|------|
| URL | `get_permalink()` | Permalink completo |
| Title tag | SEO plugin meta / `wp_title()` | Yoast: `_yoast_wpseo_title`, RankMath: `rank_math_title` |
| Meta description | SEO plugin meta | Yoast: `_yoast_wpseo_metadesc`, etc. |
| Canonical | `wp_get_canonical_url()` / SEO plugin | |
| Robots meta | SEO plugin / `get_robots()` (WP 5.7+) | |
| OG tags | SEO plugin meta | |
| Headings | DOMDocument su `post_content` | Parse HTML del contenuto |
| Images | DOMDocument su `post_content` | src, alt, width, height |
| Internal links | DOMDocument + `wp_parse_url()` | Confronto dominio sito |
| External links | DOMDocument + `wp_parse_url()` | !dominio sito |
| Schema JSON-LD | Output buffer di `wp_head()` o SEO plugin | Cattura `<script type="application/ld+json">` |
| Word count | `str_word_count(wp_strip_all_tags())` | |
| SSL | `is_ssl()` | |
| robots.txt | `file_get_contents(ABSPATH . 'robots.txt')` o WP virtual | |
| Sitemap | Check URL standard sitemap locations | |

---

## Flusso UX - Import via WordPress

### Precondizione

Il Global Project deve avere un connettore WordPress attivo e testato con successo.

### Step-by-step

1. Utente va a `/seo-audit/project/{id}/import`
2. Vede tab "WordPress" (visibile solo se connettore WP presente nel Global Project)
3. Seleziona tipi contenuto: Post, Pagine, Prodotti (checkbox)
4. Click "Importa e Analizza"
5. Backend:
   a. Crea `sa_crawl_sessions` con `config.source = 'wordpress'`
   b. Apre SSE stream
   c. Loop paginato: `GET /seo-audit?page=1&per_page=50&type=post,page`
   d. Per ogni pagina dalla response:
      - Salva in `sa_pages` (source='wordpress', cms_entity_id, cms_entity_type)
      - Esegue `IssueDetector::analyzePage()` (stessa logica del crawl)
      - Salva issue in `sa_issues`
      - Invia SSE event `page_analyzed` con progress
   e. Prima pagina API: salva `site_info` (robots.txt, sitemap check) per issue globali
   f. Alla fine: calcola health score, chiudi sessione
   g. Invia SSE event `completed`
6. Frontend redirect alla dashboard audit

### Issue non verificabili via CMS

| Categoria | Verificabile | Note |
|-----------|-------------|------|
| Meta (title, desc, canonical) | SI | Dal plugin WP |
| Headings (H1, struttura) | SI | Dal contenuto |
| Images (alt, dimensioni) | SI | Dal contenuto |
| Links (interni, esterni) | SI | Dal contenuto |
| Content (word count, thin) | SI | Dal contenuto |
| Schema (JSON-LD) | SI | Dal plugin WP |
| Open Graph | SI | Dal plugin WP |
| Robots meta (noindex) | SI | Dal plugin WP |
| robots.txt | SI | Il plugin legge il file |
| Sitemap | PARZIALE | Il plugin verifica esistenza |
| SSL/HTTPS | SI | `is_ssl()` lato WP |
| Response time | NO | Richiede HTTP diretto |
| Redirect chains | NO | Richiede HTTP diretto |
| Mixed content | PARZIALE | Verifica URL nel contenuto |

Le issue non verificabili (response time, redirect chains) vengono semplicemente omesse dal report. Non create come issue "non verificabile" per evitare rumore.

---

## Dashboard Global Project - Connessione CMS

### Card nella dashboard `/projects/{id}`

**Stato: nessun connettore**
```
Connessione CMS
Nessun CMS collegato. Collega il tuo WordPress per analisi senza scraping.
[Configura WordPress] [Scarica Plugin WP]
```

**Stato: connettore configurato e testato**
```
Connessione CMS
WordPress: myblog.com - Connesso
SEO Plugin: Yoast SEO | WP 6.4 | Plugin v1.1.0
Ultimo test: 5 min fa - Successo
[Testa Connessione] [Modifica] [Rimuovi]
```

**Badge sui moduli** nella sezione "Moduli Attivi":
- SEO Audit: icona WP + "Audit senza scraping disponibile"
- Content Creator: icona WP + "Pubblica su WordPress"
- (Fase 3) Internal Links: icona WP + "Analisi link diretta"

---

## File da creare/modificare

### Nuovi file

| File | Descrizione |
|------|-------------|
| `database/migrations/XXX_create_project_connectors.sql` | Schema tabella project_connectors |
| `modules/seo-audit/database/migrations/006_add_source_to_pages.sql` | ALTER sa_pages |
| `core/Models/ProjectConnector.php` | Model CRUD connettori (find, create, update, delete, test, getByProject) |
| `services/connectors/WordPressSeoConnector.php` | Estende WordPressConnector con fetchSeoAudit() |

### File da modificare

| File | Modifiche |
|------|-----------|
| `storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php` | Nuovo endpoint `/seo-audit` con estrazione dati SEO completa |
| `controllers/GlobalProjectController.php` | CRUD connettori: addConnector(), testConnector(), removeConnector() |
| `shared/views/projects/dashboard.php` | Card "Connessione CMS" con form configurazione |
| `modules/seo-audit/routes.php` | Nuove route per import WordPress |
| `modules/seo-audit/controllers/ApiController.php` | Metodo importFromWordPress() con SSE |
| `modules/seo-audit/views/urls/import.php` | Tab "WordPress" con selezione tipi e connettore |
| `modules/seo-audit/services/CrawlerService.php` | Metodo processWordPressPage() per convertire dati API in formato sa_pages |
| `modules/seo-audit/services/IssueDetector.php` | Gestione source='wordpress': skip issue non verificabili |

### File NON toccati (Fase 1)

- `modules/content-creator/` - Nessuna modifica, cc_connectors resta com'e
- `modules/internal-links/` - Fase 3
- `core/Models/GlobalProject.php` - Solo lettura project_connectors, nessuna modifica al model

---

## Costi crediti

| Operazione | Costo | Note |
|------------|-------|------|
| Import + Analisi WordPress (per pagina) | 0 crediti | Come il crawl standard (gratuito) |
| Action Plan AI | 10 crediti | Invariato (post-analisi) |

L'analisi via WordPress e gratuita come il crawl standard. Il valore aggiunto e la velocita e affidabilita, non un costo maggiore.

---

## Fix: Crawl in Background (Bug attuale)

### Problema

Il crawl attuale dipende interamente dal frontend:
- `setInterval` ogni 1.5s chiama `POST /crawl/batch`
- Navigando via dalla pagina, il JS viene distrutto e il crawl si ferma
- Tornando sulla pagina, il polling riparte e il crawl continua
- Nessun background processing, nessun cron

### Soluzione: Pattern Background Job (come seo-tracking)

Convertiamo il crawl da frontend-driven a backend-driven:

**Nuova tabella `sa_crawl_jobs`:**

```sql
CREATE TABLE sa_crawl_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    config JSON NULL,                    -- batch_size, crawl_mode, max_pages, etc.
    items_total INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500) NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_project (project_id),
    FOREIGN KEY (session_id) REFERENCES sa_crawl_sessions(id) ON DELETE CASCADE
);
```

**Flusso rivisto:**

1. Utente clicca "Avvia Crawl"
2. Backend crea `sa_crawl_sessions` + `sa_crawl_jobs` (status=pending)
3. Frontend apre SSE stream (`GET /crawl/stream?job_id=X`)
4. Backend processa batch nel SSE handler:
   - `ignore_user_abort(true)` + `set_time_limit(0)` → il crawl continua anche se l'utente naviga via
   - Per ogni URL: scrape → save → detect issues → SSE event
   - Salva progress nel DB ad ogni step
5. Se SSE si disconnette (utente naviga via):
   - Il backend continua a processare (ignore_user_abort)
   - Salva risultati nel DB
6. Utente torna sulla pagina:
   - Controlla `sa_crawl_jobs.status`
   - Se `running`: riconnette SSE o usa polling (`GET /crawl/job-status?job_id=X`)
   - Se `completed`: mostra risultati dalla dashboard
7. **Cron dispatcher** (opzionale, per robustezza):
   - `modules/seo-audit/cron/crawl-dispatcher.php` eseguito ogni 5 minuti
   - Riprende job `pending` o `running` rimasti bloccati (crash recovery)

**File aggiuntivi per questa fix:**

| File | Azione |
|------|--------|
| `modules/seo-audit/database/migrations/007_create_crawl_jobs.sql` | Schema sa_crawl_jobs |
| `modules/seo-audit/models/CrawlJob.php` | Model job (create, updateProgress, cancel, isCancelled) |
| `modules/seo-audit/controllers/CrawlController.php` | Refactor: start crea job, stream() per SSE, jobStatus() per polling, cancel() |
| `modules/seo-audit/views/partials/crawl-control.php` | Refactor JS: SSE + polling fallback (rimuovere setInterval/processBatch) |
| `modules/seo-audit/cron/crawl-dispatcher.php` | Cron per riprendere job bloccati |

Questo fix si applica sia al crawl classico (scraping) che all'import WordPress. Entrambi usano lo stesso pattern job.

---

## Fasi future

### Fase 2: Migrazione cc_connectors
- Aggiungere `global_project_id` a `cc_connectors` o migrare dati a `project_connectors`
- Content-creator adotta `project_connectors` come source of truth
- UI connettori in content-creator punta alla dashboard Global Project

### Fase 3: Altri moduli
- Internal-links: tab "WordPress" per import link senza scraping
- keyword-research: eventuale import keyword da WP/SEO plugin
