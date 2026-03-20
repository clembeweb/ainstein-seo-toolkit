# Crawl Budget Optimizer — Design Document

> Data: 2026-02-25
> Stato: Approvato
> Fase: 1 (Crawl & Analyze)

---

## 1. Obiettivo

Nuovo modulo standalone per identificare sprechi di crawl budget. Separato da SEO Audit (che si focalizza sull'on-page). Il tool analizza redirect chains, pagine spreco e conflitti di indexability per siti fino a 5.000 pagine.

### Target utenti

- SEO freelancer/agency: report presentabili ai clienti
- Webmaster/developer: dashboard operativa per interventi tecnici

### Cosa NON e

- Non e un clone di Screaming Frog (scope piu ristretto e focalizzato)
- Non sostituisce SEO Audit (focus diverso)
- Non fa JS rendering (Fase 1 = HTML statico)

---

## 2. Identita Modulo

| Campo | Valore |
|-------|--------|
| Nome | Crawl Budget Optimizer |
| Slug | `crawl-budget` |
| Prefisso DB | `cb_` |
| Colore | Orange (`orange-500/600`) |
| Icona | Heroicon `magnifying-glass-circle` o `signal` |

---

## 3. Database Schema

### cb_projects

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| user_id | INT FK | |
| global_project_id | INT FK nullable | Hub progetti |
| name | VARCHAR(255) | |
| domain | VARCHAR(255) | Dominio target |
| status | ENUM('pending','crawling','completed','failed') | |
| last_crawl_at | DATETIME nullable | |
| crawl_budget_score | INT nullable | 0-100 |
| settings | JSON | max_pages, respect_robots, follow_subdomains |
| created_at | DATETIME | |
| updated_at | DATETIME | |

### cb_crawl_sessions

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| project_id | INT FK | |
| status | ENUM('pending','running','paused','stopping','stopped','completed','failed') | |
| pages_found | INT DEFAULT 0 | URL scoperte |
| pages_crawled | INT DEFAULT 0 | URL crawlate |
| issues_found | INT DEFAULT 0 | Problemi trovati |
| current_url | VARCHAR(2048) nullable | URL in lavorazione |
| config | JSON | Parametri crawl |
| started_at | DATETIME nullable | |
| completed_at | DATETIME nullable | |
| created_at | DATETIME | |

### cb_crawl_jobs

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| session_id | INT FK | |
| status | ENUM('pending','running','completed','error','cancelled') | |
| items_total | INT DEFAULT 0 | |
| items_completed | INT DEFAULT 0 | |
| items_failed | INT DEFAULT 0 | |
| current_item | VARCHAR(2048) nullable | |
| error_message | TEXT nullable | |
| started_at | DATETIME nullable | |
| completed_at | DATETIME nullable | |
| created_at | DATETIME | |

### cb_pages

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| project_id | INT FK | |
| session_id | INT FK | |
| url | VARCHAR(2048) | |
| status | ENUM('pending','crawling','crawled','error') | |
| http_status | SMALLINT nullable | 200, 301, 302, 404, 500... |
| content_type | VARCHAR(128) nullable | text/html, application/json... |
| response_time_ms | INT nullable | Tempo di risposta |
| content_length | INT nullable | Dimensione body |
| word_count | INT nullable | Conteggio parole |
| title | VARCHAR(512) nullable | |
| meta_robots | VARCHAR(255) nullable | index/noindex, follow/nofollow |
| canonical_url | VARCHAR(2048) nullable | |
| canonical_matches | TINYINT(1) DEFAULT 0 | canonical == url? |
| is_indexable | TINYINT(1) DEFAULT 1 | Risultato netto |
| indexability_reason | VARCHAR(255) nullable | Motivo non indexabile |
| redirect_target | VARCHAR(2048) nullable | Destinazione finale |
| redirect_chain | JSON nullable | Array completo chain |
| redirect_hops | TINYINT DEFAULT 0 | Numero hop |
| in_sitemap | TINYINT(1) DEFAULT 0 | Presente nella sitemap? |
| in_robots_allowed | TINYINT(1) DEFAULT 1 | Consentito da robots.txt? |
| internal_links_in | INT DEFAULT 0 | Link interni entranti |
| internal_links_out | INT DEFAULT 0 | Link interni uscenti |
| has_parameters | TINYINT(1) DEFAULT 0 | URL con query string? |
| depth | TINYINT DEFAULT 0 | Profondita dal root |
| discovered_from | VARCHAR(2048) nullable | URL che l'ha scoperta |
| created_at | DATETIME | |
| updated_at | DATETIME | |

Indici: `(project_id, session_id, status)`, `(project_id, url)` UNIQUE per sessione, `(session_id, http_status)`.

### cb_issues

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| project_id | INT FK | |
| session_id | INT FK | |
| page_id | INT FK nullable | Null per issue globali |
| category | ENUM('redirect','waste','indexability') | |
| type | VARCHAR(100) | Identificativo issue |
| severity | ENUM('critical','warning','notice') | |
| title | VARCHAR(255) | Descrizione breve (italiano) |
| details | JSON | Dati specifici |
| created_at | DATETIME | |

Indici: `(session_id, category, severity)`, `(page_id)`.

### cb_site_config

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| project_id | INT FK UNIQUE | |
| robots_txt | TEXT nullable | Contenuto raw |
| robots_rules | JSON nullable | Regole parsed per user-agent |
| sitemaps | JSON nullable | URL sitemap trovate |
| sitemap_urls | JSON nullable | URL nelle sitemap |
| updated_at | DATETIME | |

### cb_reports

| Colonna | Tipo | Note |
|---------|------|------|
| id | INT PK AUTO_INCREMENT | |
| project_id | INT FK | |
| session_id | INT FK | |
| ai_response | LONGTEXT | Report completo da Claude |
| summary | TEXT nullable | Executive summary |
| priority_actions | JSON nullable | Top azioni prioritarie |
| estimated_impact | JSON nullable | Stima impatto per categoria |
| created_at | DATETIME | |

---

## 4. Architettura

### Directory Structure

```
modules/crawl-budget/
├── controllers/
│   ├── ProjectController.php      # CRUD progetti, dashboard
│   ├── CrawlController.php        # Start/stop crawl, SSE stream, job status
│   ├── ResultsController.php      # Visualizzazione risultati
│   └── ReportController.php       # Generazione report AI
├── models/
│   ├── Project.php
│   ├── CrawlSession.php           # State machine
│   ├── CrawlJob.php
│   ├── Page.php
│   ├── Issue.php
│   ├── SiteConfig.php
│   └── Report.php
├── services/
│   ├── BudgetCrawlerService.php   # Crawler: fetch + redirect tracing
│   ├── BudgetAnalyzerService.php  # Analisi problemi (3 categorie)
│   └── BudgetReportService.php    # Generazione report AI
├── views/
│   ├── projects/
│   │   ├── index.php              # Lista progetti
│   │   ├── create.php             # Redirect a /projects/create
│   │   └── settings.php           # Config crawl
│   ├── dashboard.php              # Dashboard + score + landing educativa
│   ├── crawl/
│   │   └── progress.php           # Crawl in corso (SSE progress)
│   ├── results/
│   │   ├── overview.php           # Summary: score, metriche, distribuzione
│   │   ├── redirects.php          # Tabella redirect chains/loops
│   │   ├── waste.php              # Pagine spreco
│   │   ├── indexability.php       # Conflitti indexability
│   │   └── pages.php              # Lista completa pagine
│   └── report/
│       └── view.php               # Report AI
├── cron/
│   └── crawl-dispatcher.php       # Reset stuck jobs, orphan recovery
├── database/
│   ├── schema.sql
│   └── migrations/
├── routes.php
└── module.json
```

### Routes

```
GET  /crawl-budget/projects                          # Lista
GET  /crawl-budget/projects/{id}                     # Dashboard + score
GET  /crawl-budget/projects/{id}/settings            # Config
POST /crawl-budget/projects/{id}/crawl/start         # Avvia crawl
GET  /crawl-budget/projects/{id}/crawl/stream        # SSE stream
POST /crawl-budget/projects/{id}/crawl/cancel        # Cancella
GET  /crawl-budget/projects/{id}/crawl/job-status    # Polling fallback
GET  /crawl-budget/projects/{id}/results             # Overview
GET  /crawl-budget/projects/{id}/results/redirects   # Tab redirect
GET  /crawl-budget/projects/{id}/results/waste       # Tab waste
GET  /crawl-budget/projects/{id}/results/indexability # Tab indexability
GET  /crawl-budget/projects/{id}/results/pages       # Tab tutte le pagine
POST /crawl-budget/projects/{id}/report/generate     # Genera report AI
GET  /crawl-budget/projects/{id}/report              # Visualizza report
```

---

## 5. Crawler Engine — BudgetCrawlerService

### Flusso crawl

```
POST /crawl/start
  1. Verifica no sessione attiva
  2. Crea cb_crawl_session (pending)
  3. Fetch robots.txt → parse regole → cb_site_config
  4. Fetch sitemap(s) → parse URL → cb_site_config.sitemap_urls
  5. Inserisce URL seed (homepage + sitemap URLs) in cb_pages (pending)
  6. Crea cb_crawl_job (pending)
  7. Ritorna session_id + job_id

GET /crawl/stream?job_id=X (SSE)
  Setup: ignore_user_abort(true), set_time_limit(0), session_write_close()
  Loop:
    a. Database::reconnect()
    b. Check cancellazione
    c. Fetch cb_pages pending (LIMIT 1)
    d. Se nessuna → finalizza + evento 'completed' → break
    e. Mark 'crawling'
    f. BudgetCrawlerService::crawlPage($url)
       - Fetch con redirect manuale (no CURLOPT_FOLLOWLOCATION)
       - Traccia chain: [url1→301→url2→302→url3→200]
       - Parse HTML: title, meta_robots, canonical, links interni
       - Calcola: is_indexable, has_parameters, word_count, depth
    g. Salva risultati in cb_pages
    h. Scopri nuovi URL interni → inserisci in cb_pages se nuovi
    i. BudgetAnalyzerService::analyzePage($pageData) → cb_issues
    j. Update job progress
    k. Evento SSE: item_completed + progress
    l. Rate limit: usleep (default 500ms)
  Fine: calcola crawl_budget_score → update cb_projects
```

### Redirect Chain Tracing

Per ogni URL, segue redirect manualmente (no CURLOPT_FOLLOWLOCATION):

```
URL originale → HTTP request → 301? → segui Location header
    → secondo hop → 302? → segui Location header
    → terzo hop → 200? → fine chain

Salva: redirect_chain = ["url1|301", "url2|302", "url3|200"]
       redirect_hops = 2
       redirect_target = url3
```

Limiti sicurezza:
- Max 10 hop (oltre = probabile loop)
- Timeout 5s per hop
- Loop detection: se URL compare 2+ volte nella chain → loop

### Dati raccolti per pagina

| Dato | Metodo | Scopo |
|------|--------|-------|
| HTTP status | curl response code | Classificazione |
| Redirect chain | Hop manuali | Core feature |
| Content-Type | Header | Distinguere HTML da risorse |
| Response time | curl timing | Performance |
| Content length | Header/strlen | Dimensione |
| Title | Regex su HTML | Identificazione |
| Meta robots | Regex `<meta name="robots"` | Indexability |
| Canonical | Regex `<link rel="canonical"` + Header | Conflitti |
| X-Robots-Tag | HTTP Header | Indexability via header |
| Links interni | DOM/regex parsing | Discovery + link graph |
| Word count | strip_tags + str_word_count | Thin content |
| URL parameters | parse_url query | Parametri spreco |
| Depth | Incrementale | Profondita crawl |

### Robots.txt

- Parse per Googlebot, Bingbot, `*`
- Controlla ogni URL prima di crawlare
- Marca bloccate come `in_robots_allowed = false`
- Le registra comunque (per conflitti "bloccata ma linkata")

### Sitemap

- Fetch sitemap index → resolve nested
- Estrae URL → marca `in_sitemap = true`
- Supporta sitemap.xml e sitemap index

### Rate limiting

- Default: 2 req/s (configurabile in settings)
- Rispetta `Crawl-delay` da robots.txt

---

## 6. Analysis Engine — BudgetAnalyzerService

### Categoria: REDIRECT

| Type | Severity | Condizione |
|------|----------|-----------|
| `redirect_chain` | critical | redirect_hops >= 2 |
| `redirect_loop` | critical | URL compare 2+ volte nella chain |
| `redirect_to_4xx` | critical | Chain termina con 404/410 |
| `redirect_to_5xx` | critical | Chain termina con 500+ |
| `redirect_temporary` | warning | 302/307 usato dove serve 301 |
| `redirect_single` | notice | Singolo redirect (1 hop) |

### Categoria: WASTE

| Type | Severity | Condizione |
|------|----------|-----------|
| `thin_content` | warning | word_count < 100, status=200, type=html |
| `empty_page` | critical | word_count = 0, status=200 |
| `parameter_url_crawled` | warning | has_parameters=true, no canonical verso versione pulita |
| `soft_404` | critical | status=200 ma title "404"/"not found" o word_count<50 |
| `duplicate_title` | warning | Stesso title di altra pagina |
| `orphan_page` | notice | internal_links_in = 0 |
| `deep_page` | notice | depth > 4 |

### Categoria: INDEXABILITY

| Type | Severity | Condizione |
|------|----------|-----------|
| `noindex_in_sitemap` | critical | is_indexable=false MA in_sitemap=true |
| `blocked_but_linked` | warning | in_robots_allowed=false MA internal_links_in > 0 |
| `canonical_mismatch` | warning | canonical_url != url |
| `canonical_chain` | critical | canonical punta a pagina con altro canonical |
| `mixed_signals` | critical | noindex + canonical verso altra pagina |
| `noindex_receives_links` | warning | is_indexable=false MA internal_links_in >= 3 |
| `blocked_in_robots` | notice | in_robots_allowed=false (informativo) |

### Crawl Budget Score (0-100)

```
score = 100
score -= min(40, critical_count * 3)      # Critici: max -40
score -= min(30, warning_count * 1.5)      # Warning: max -30
score -= min(10, notice_count * 0.5)       # Notice: max -10
score -= min(20, waste_percentage * 0.4)   # % pagine spreco: max -20
```

Ranges: 90-100 Eccellente, 70-89 Buono, 50-69 Migliorabile, 0-49 Critico.

---

## 7. Report AI — BudgetReportService

### Input per Claude

Dati aggregati (non tutte le pagine):
- Summary metriche: totale pagine, distribuzione status code, score
- Top 20 issue per severity
- Top redirect chains (piu lunghe/impattanti)
- Distribuzione waste per tipo
- Conflitti indexability piu gravi

### Struttura report

1. **Executive Summary** — 3-4 righe, score, stato generale
2. **Impatto stimato** — Pagine spreco, % crawl budget perso
3. **Top 5 azioni prioritarie** — Ordinate per impatto, con URL specifici
4. **Analisi per categoria** — Redirect / Waste / Indexability con dettagli
5. **Quick wins** — Fix facili con alto impatto

### Costo crediti

| Operazione | Crediti |
|-----------|---------|
| Crawl (per pagina) | 0 (gratuito) |
| Report AI | 5 |

---

## 8. UI/UX

### Dashboard progetto

- **Hero**: Score cerchio colorato, ultimo crawl, contatori severity
- **KPI row**: 4 card — Pagine crawlate, Redirect chains, Pagine spreco, Conflitti indexability
- **CTA**: "Avvia Analisi" (o "Ri-analizza")
- **Tab risultati**: Overview | Redirect | Waste | Indexability | Tutte le pagine
- **Report AI**: Card con bottone "Genera Report" o link al report
- **Landing educativa**: 7 sezioni standard ("Scopri cosa puoi fare")

### Crawl in corso

- Progress bar con %
- URL corrente
- Contatori live: pagine, issues, redirect chains
- Log scrollabile ultimi eventi
- Bottone "Annulla"

### Tabelle risultati

Pattern standard Ainstein (rounded-xl, px-4 py-3, dark mode, pagination, sort).

- **Tab Redirect**: URL origine → chain (hop con frecce) → destinazione → status → severity
- **Tab Waste**: URL → tipo spreco → word count → status → links in/out → severity
- **Tab Indexability**: URL → conflitto → segnali → sitemap? → robots? → severity
- **Tab Pagine**: Lista completa con filtri per status, indexability, depth

---

## 9. Cron

`cron/crawl-dispatcher.php` (ogni 5 minuti):
- Reset job bloccati (running > 30 min senza progresso)
- Reset sessioni orfane (running > 30 min senza job attivo)
- Pulizia job vecchi (max 20 per progetto)

---

## 10. module.json

Gruppi settings: `general` (0) → `crawl_config` (1) → `ai_config` (2) → `costs` (99, collapsed)

Settings principali:
- `max_pages_per_crawl`: default 5000
- `crawl_delay_ms`: default 500
- `respect_robots`: default true
- `ai_provider`, `ai_model`, `ai_fallback_enabled`
- `cost_report_generate`: default 5

---

## 11. Dipendenze esterne

- **ScraperService**: fetch HTTP (riusa curl wrapper)
- **AiService**: generazione report
- **Credits**: consumo crediti per report
- **ProjectAccessService**: accesso condiviso progetti
- **NotificationService**: notifica completamento crawl
- **ApiLoggerService**: log chiamate (se si aggiungono API esterne in futuro)
- **ExportService**: export CSV risultati (Fase 2)

---

## 12. Fuori scope (Fase 2+)

- Crawl scheduling ricorrente con alert su cambiamenti
- Confronto tra sessioni (trend nel tempo)
- JS rendering
- Export CSV/Excel risultati
- Integrazione Google Search Console (crawl stats reali)
- Siti > 5.000 pagine
- Log file analysis (access log Googlebot)
