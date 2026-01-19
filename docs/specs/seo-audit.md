# SEO AUDIT - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-audit` |
| **Prefisso DB** | `sa_` |
| **Files** | 32 |
| **Stato** | ✅ Completato (100%) |
| **Ultimo update** | 2026-01-09 |

Modulo per audit SEO completo con crawling, analisi issue, integrazione Google Search Console, analisi AI e storico scansioni.

---

## Architettura Implementata

```
modules/seo-audit/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php      # CRUD progetti + settings
│   ├── CrawlController.php        # Gestione crawl batch/polling
│   ├── AuditController.php        # Dashboard, pages, issues, categories, history
│   ├── GscController.php          # OAuth Google Search Console
│   ├── ReportController.php       # Export CSV/PDF
│   └── ApiController.php          # API sitemap/spider/URLs
├── models/
│   ├── Project.php                # Gestione progetti con stats
│   ├── Page.php                   # Pagine crawlate (con session_id)
│   ├── Issue.php                  # Issue rilevate (con session_id)
│   ├── CrawlSession.php           # Sessioni di crawl (con health_score, counts)
│   └── GscConnection.php          # Connessioni GSC (deprecato)
├── services/
│   ├── CrawlerService.php         # Crawl engine (salva session_id)
│   ├── IssueDetector.php          # 50+ tipi issue (salva session_id)
│   ├── AiAnalysisService.php      # Analisi AI via AiService
│   ├── AuditAnalyzer.php          # Analyzer helper
│   └── GscService.php             # API Google Search Console
└── views/
    ├── projects/
    │   ├── index.php              # Lista progetti
    │   ├── create.php             # Form creazione
    │   └── settings.php           # Impostazioni progetto
    ├── audit/
    │   ├── dashboard.php          # Dashboard con health score + trend
    │   ├── pages.php              # Lista pagine crawlate
    │   ├── page-detail.php        # Dettaglio singola pagina
    │   ├── issues.php             # Lista tutte le issue
    │   ├── category.php           # Dettaglio categoria issue
    │   └── history.php            # Storico scansioni con grafico trend
    ├── analysis/
    │   ├── overview.php           # Analisi AI panoramica
    │   └── category.php           # Analisi AI per categoria
    ├── gsc/
    │   ├── connect.php            # OAuth flow
    │   ├── dashboard.php          # Dashboard GSC
    │   └── properties.php         # Selezione proprietà
    ├── urls/
    │   └── import.php             # Import URL da sitemap/spider
    └── partials/
        └── crawl-control.php      # Controlli crawl
```

---

## Database Schema

```sql
-- 13 tabelle con prefisso sa_

sa_projects              -- Progetti audit
  - id, user_id, name, base_url
  - status: pending|idle|crawling|stopping|analyzing|completed|failed|stopped
  - pages_found, pages_crawled, issues_count
  - health_score (0-100)
  - current_session_id (FK)
  - gsc_connected, gsc_property

sa_pages                 -- Pagine crawlate
  - id, project_id, session_id (NEW - tracking storico)
  - url, status: pending|crawled|error
  - title, meta_description, canonical_url
  - h1_count...h6_count, word_count
  - images_count, images_without_alt
  - internal/external/broken_links_count
  - html_content (longtext)

sa_issues                -- Issue rilevate
  - id, project_id, page_id, session_id (NEW - tracking storico)
  - category, issue_type, severity: critical|warning|notice|info
  - title, description, affected_element, recommendation
  - source: crawler|gsc

sa_crawl_sessions        -- Sessioni di crawl (UPDATED)
  - id, project_id
  - status: pending|running|paused|stopping|stopped|completed|failed
  - pages_found, pages_crawled, issues_found
  - health_score (NEW - calcolato a fine crawl)
  - critical_count, warning_count, notice_count (NEW)
  - current_url, max_pages, crawl_mode
  - started_at, stopped_at, completed_at, error_message

sa_ai_analyses           -- Analisi AI generate
sa_site_config           -- Config robots/sitemap
sa_gsc_connections       -- Token OAuth GSC
sa_gsc_performance       -- Dati performance GSC
sa_gsc_coverage          -- Copertura indice
sa_gsc_core_web_vitals   -- LCP, INP, CLS
sa_gsc_mobile_usability  -- Issue mobile
sa_gsc_sync_log          -- Log sync GSC
sa_activity_logs         -- Log attività
```

---

## Routes Principali

```php
// === PROGETTI ===
GET  /seo-audit                              # Lista progetti
GET  /seo-audit/create                       # Form creazione
POST /seo-audit/store                        # Salva progetto
GET  /seo-audit/project/{id}/settings        # Impostazioni
POST /seo-audit/project/{id}/settings        # Salva impostazioni
POST /seo-audit/project/{id}/delete          # Elimina progetto

// === CRAWL ===
POST /seo-audit/project/{id}/crawl/start     # Avvia crawl
GET  /seo-audit/project/{id}/crawl/status    # Stato crawl (polling)
POST /seo-audit/project/{id}/crawl/batch     # Processa batch
POST /seo-audit/project/{id}/crawl/stop      # Stop crawl
POST /seo-audit/project/{id}/crawl/confirm-stop

// === AUDIT DASHBOARD ===
GET  /seo-audit/project/{id}/dashboard       # Dashboard principale
GET  /seo-audit/project/{id}/pages           # Lista pagine
GET  /seo-audit/project/{id}/page/{pageId}   # Dettaglio pagina
GET  /seo-audit/project/{id}/issues          # Lista issues
GET  /seo-audit/project/{id}/category/{slug} # Categoria issue
GET  /seo-audit/project/{id}/history         # Storico scansioni (NEW)

// === GSC ===
GET  /seo-audit/project/{id}/gsc             # Dashboard GSC
GET  /seo-audit/project/{id}/gsc/connect     # Connessione
GET  /seo-audit/project/{id}/gsc/authorize   # Avvia OAuth
GET  /seo-audit/project/{id}/gsc/properties  # Lista proprietà
POST /seo-audit/project/{id}/gsc/select-property
POST /seo-audit/project/{id}/gsc/sync        # Sincronizza dati
POST /seo-audit/project/{id}/gsc/disconnect

// === AI ANALYSIS ===
GET  /seo-audit/project/{id}/analysis        # Pagina analisi
POST /seo-audit/project/{id}/analyze/overview
POST /seo-audit/project/{id}/analyze/{category}

// === EXPORT ===
GET  /seo-audit/project/{id}/export/csv      # Export issues CSV
GET  /seo-audit/project/{id}/export/csv/{category}
GET  /seo-audit/project/{id}/export/pages-csv
GET  /seo-audit/project/{id}/export/pdf      # Export PDF
GET  /seo-audit/project/{id}/export/summary  # JSON summary

// === API ===
POST /seo-audit/api/sitemap-discover         # Discover sitemap
POST /seo-audit/api/sitemap                  # Preview/import sitemap
POST /seo-audit/api/spider                   # Spider crawl
POST /seo-audit/project/{id}/urls/store      # Store URLs
```

---

## Funzionalità Implementate

### Crawling
- [x] Sitemap discovery (robots.txt, index, nested)
- [x] Spider ricorsivo con depth limit
- [x] Rate limiting configurabile
- [x] Progress real-time con polling AJAX
- [x] Sessioni di crawl con stato persistente
- [x] Stop/Resume crawl
- [x] Impostazioni avanzate (delay, timeout, user-agent)
- [x] **Tracking session_id** su pagine e issues

### Issue Detection (50+ tipi)
- [x] **Meta tags** - title, description, OG tags, canonical
- [x] **Headings** - H1 mancante/multiplo/duplicato
- [x] **Images** - alt mancanti, dimensioni
- [x] **Links** - broken, redirect chains, nofollow
- [x] **Content** - thin content, duplicati
- [x] **Technical** - robots, noindex, sitemap
- [x] **Schema** - markup strutturato
- [x] **Security** - HTTPS, mixed content

### Google Search Console
- [x] OAuth2 flow centralizzato (GoogleOAuthService)
- [x] Lista e selezione proprietà
- [x] Import performance data (query, pagine)
- [x] Import coverage data
- [x] Core Web Vitals (LCP, INP, CLS)
- [x] Token refresh automatico

### AI Analysis
- [x] Analisi panoramica globale (15 crediti)
- [x] Analisi per categoria (3 crediti)
- [x] Integrato con AiService centralizzato
- [x] Cache analisi in database

### Export
- [x] CSV issues (tutte o per categoria)
- [x] CSV pagine
- [x] JSON summary
- [x] PDF report (placeholder)

### Storico Scansioni (NEW)
- [x] **Vista /history** con tabella sessioni passate
- [x] **Grafico trend** health score ultimi 10 crawl
- [x] **Stats per sessione** - health_score, critical/warning/notice count
- [x] **Trend dashboard** - confronto vs crawl precedente
- [x] **Tracking completo** - ogni issue/pagina ha session_id

### Sidebar Navigation
- [x] Submenu dinamico quando dentro un progetto
- [x] Badge con conteggio issue critiche
- [x] Badge con pagine pending
- [x] Sezioni: Dashboard, Pagine, Issues, Analisi AI, GSC, **Storico**, Categorie

---

## Health Score

### Formula
```php
// Penalità con cap per evitare score negativi
$criticalPenalty = min($critical * 5, 40);  // max -40 punti
$warningPenalty  = min($warning * 1, 30);   // max -30 punti
$noticePenalty   = min($notice * 0.2, 10);  // max -10 punti

$healthScore = max(0, 100 - $criticalPenalty - $warningPenalty - $noticePenalty);
```

### Interpretazione
| Score | Stato | Colore |
|-------|-------|--------|
| 70-100 | Ottimo | Verde |
| 40-69 | Da migliorare | Giallo |
| 0-39 | Critico | Rosso |

---

## Crediti

| Azione | Costo |
|--------|-------|
| Crawl per pagina | 0.2 |
| GSC sync | 5 |
| AI overview | 15 |
| AI categoria | 3 |

---

## Models

### Project.php
```php
find($id, $userId = null)     # Trova progetto
create($data)                  # Crea progetto
update($id, $data)            # Aggiorna
updateStats($projectId)       # Ricalcola conteggi da sa_pages
getStats($projectId)          # Statistiche progetto
```

### Page.php
```php
findByProject($projectId)     # Tutte le pagine
findPending($projectId, $limit) # Pagine da crawlare
upsert($projectId, $url, $data) # Crea/aggiorna (include session_id)
getStats($projectId)          # Conteggi per status
```

### Issue.php
```php
findByProject($projectId)     # Tutte le issue
findByCategory($projectId, $category)
countBySeverity($projectId)   # {critical, warning, notice, total}
create($data)                 # Singola issue (include session_id)
```

### CrawlSession.php
```php
create($projectId, $config)   # Nuova sessione
findActiveByProject($projectId)
start($sessionId)             # Imposta status = running
complete($sessionId)          # Calcola stats + health_score + salva (UPDATED)
setPagesFound/Crawled()       # Aggiorna contatori
```

### IssueDetector.php
```php
init($projectId)              # Inizializza
setSessionId($sessionId)      # Imposta session per tracking (NEW)
analyzePage($pageData)        # Rileva issues
saveIssues($pageId, $issues)  # Salva con session_id (UPDATED)
```

### CrawlerService.php
```php
init($projectId, $userId)
setSessionId($sessionId)      # Imposta session per tracking
crawlPage($url)               # Crawl singola pagina
savePage($data)               # Salva con session_id (UPDATED)
```

---

## Bug Noti / Fix Recenti

| Data | Bug | Fix |
|------|-----|-----|
| 2026-01-09 | Storico crawl mancante | Aggiunto session_id su issues/pages, vista /history |
| 2026-01-09 | Dashboard e Pages mostravano numeri diversi | Corretto `updateStats()` per usare conteggi reali |
| 2026-01-08 | GSC sync 0 risultati con token scaduto | Aggiunto token refresh automatico |
| 2026-01-07 | Encoding HTML rotto nel database | Corretto salvataggio con charset UTF-8 |

---

## Note Implementazione

1. **OAuth GSC** - Usa `GoogleOAuthService` centralizzato in `/services/`
2. **AiAnalysisService** - Usa `AiService('seo-audit')`, mai curl diretto
3. **CrawlerService** - Usa `SitemapService` e `ScraperService` condivisi
4. **IssueDetector** - Severity: critical, warning, notice, info
5. **Sidebar** - Submenu in `nav-items.php` con detection progetto via regex
6. **Autoloader** - Richiede `Modules\SeoAudit\Models\*` via autoloader in `index.php`
7. **Storico** - Ogni crawl salva session_id su issues/pages per confronto
8. **Health Score** - Calcolato in `CrawlSession::complete()` e salvato nella sessione

---

## GAP AI - Da Implementare (FASE 1)

### Stato Attuale AI
- ✅ Analisi AI panoramica globale
- ✅ Analisi AI per categoria
- ⚠️ Output generico, non azionabile

### Feature Mancante: AI Fix Generator

**Obiettivo:** Per ogni issue rilevata, fornire fix specifico pronto all'uso.

**Input:** Lista issue rilevate dal crawl

**Output per ogni issue:**
```json
{
  "issue_type": "missing_meta_description",
  "priority": 8,
  "difficulty": "facile",
  "time_estimate": "2 minuti",
  "fix_code": "<meta name=\"description\" content=\"...\">",
  "explanation": "La meta description mancante riduce il CTR...",
  "impact": "Miglioramento CTR stimato: +15-25%"
}
```

**Implementazione:**
1. Nuovo metodo `generateFix($issue)` in `AiAnalysisService.php`
2. Prompt specifico per tipo di issue
3. Output strutturato JSON
4. Vista "Fix Suggestions" con copia-incolla
5. Export "To-Do List" azionabile

**Priorità:** 🔴 ALTA - FASE 1 Roadmap

📄 Vedi: [ROADMAP.md](../ROADMAP.md)

---

*Spec aggiornata - 2026-01-19*
