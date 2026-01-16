# AGENTE: SEO Audit

## CONTESTO

**Modulo:** `seo-audit`
**Stato:** 100% Completato
**Prefisso DB:** `sa_`
**Ultimo aggiornamento:** 2026-01-12

Modulo per audit SEO completo di siti web:
- Crawling (sitemap + spider) con sessioni persistenti
- Rilevamento 50+ tipi di issue con severity
- **Storico scansioni** con tracking session_id e trend health score
- Integrazione Google Search Console (OAuth)
- Analisi AI per categoria e panoramica
- Export CSV/PDF

Ispirato a SEMrush Site Audit.

---

## FILE CHIAVE

```
modules/seo-audit/
├── routes.php                               # 60+ routes definite
├── controllers/
│   ├── ProjectController.php               # CRUD progetti + settings
│   ├── CrawlController.php                 # Crawl batch/polling/stop
│   ├── AuditController.php                 # Dashboard + pages + issues + categories
│   ├── GscController.php                   # OAuth Google Search Console
│   ├── ReportController.php                # Export CSV/PDF/JSON
│   └── ApiController.php                   # API sitemap/spider/URLs
├── models/
│   ├── Project.php                         # CRUD + updateStats()
│   ├── Page.php                            # Pagine crawlate
│   ├── Issue.php                           # Issue + countBySeverity()
│   ├── CrawlSession.php                    # Sessioni crawl
│   └── GscConnection.php                   # Token OAuth (legacy)
├── services/
│   ├── CrawlerService.php                  # Engine crawl
│   ├── IssueDetector.php                   # 50+ tipi issue
│   ├── AiAnalysisService.php               # Analisi AI
│   ├── AuditAnalyzer.php                   # Helper analyzer
│   └── GscService.php                      # API Google
└── views/
    ├── audit/dashboard.php                 # Health score + stats + trend
    ├── audit/pages.php                     # Lista pagine crawlate
    ├── audit/page-detail.php               # Dettaglio singola pagina
    ├── audit/issues.php                    # Lista tutte le issue
    ├── audit/category.php                  # Dettaglio categoria
    ├── audit/history.php                   # Storico scansioni + grafico trend
    ├── analysis/overview.php               # AI panoramica
    ├── analysis/category.php               # AI per categoria
    ├── gsc/dashboard.php                   # Dashboard GSC
    ├── gsc/connect.php                     # OAuth flow
    ├── projects/settings.php               # Config progetto
    └── urls/import.php                     # Import URL
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `sa_projects` | Progetti con status, health_score, current_session_id |
| `sa_pages` | Pagine crawlate (url, status, html_content, meta, **session_id**) |
| `sa_issues` | Issue rilevate (category, severity, type, page_id, **session_id**) |
| `sa_crawl_sessions` | Sessioni crawl + **health_score, critical/warning/notice_count** |
| `sa_ai_analyses` | Analisi AI generate |
| `sa_site_config` | Config robots.txt e sitemap |
| `sa_gsc_connections` | Token OAuth GSC |
| `sa_gsc_performance` | Dati performance GSC |
| `sa_gsc_coverage` | Copertura indice |
| `sa_gsc_core_web_vitals` | LCP, INP, CLS |
| `sa_gsc_mobile_usability` | Issue mobile |
| `sa_gsc_sync_log` | Log sincronizzazioni |

### Health Score Formula
```php
$criticalPenalty = min($critical * 5, 40);  // max -40 punti
$warningPenalty  = min($warning * 1, 30);   // max -30 punti
$noticePenalty   = min($notice * 0.2, 10);  // max -10 punti
$healthScore = max(0, 100 - $criticalPenalty - $warningPenalty - $noticePenalty);
```

---

## ROUTES PATTERN

**IMPORTANTE:** Le route usano `/seo-audit/project/{id}/...` (singolare), NON `/seo-audit/projects/{id}/...`

```php
// Dashboard progetto
GET /seo-audit/project/{id}/dashboard

// Pagine e Issues
GET /seo-audit/project/{id}/pages
GET /seo-audit/project/{id}/issues
GET /seo-audit/project/{id}/category/{slug}
GET /seo-audit/project/{id}/history           # Storico scansioni

// Crawl
POST /seo-audit/project/{id}/crawl/start
GET  /seo-audit/project/{id}/crawl/status
POST /seo-audit/project/{id}/crawl/batch

// GSC
GET /seo-audit/project/{id}/gsc
POST /seo-audit/project/{id}/gsc/sync

// AI Analysis
GET /seo-audit/project/{id}/analysis
POST /seo-audit/project/{id}/analyze/overview
```

---

## BUG RISOLTI RECENTEMENTE

| Data | Bug | Fix |
|------|-----|-----|
| 2026-01-09 | Storico crawl mancante | Aggiunto session_id su issues/pages, vista /history |
| 2026-01-09 | Dashboard/Pages numeri diversi | `Project::updateStats()` ora usa conteggi reali |
| 2026-01-08 | GSC sync 0 risultati | Token refresh automatico in `GscService` |
| 2026-01-07 | Encoding HTML rotto | Charset UTF-8 su connessione DB |

---

## GOLDEN RULES SPECIFICHE

1. **Routes** - Usare `/project/{id}/` (singolare), MAI `/projects/{id}/`
2. **CrawlerService** - Usa `SitemapService` e `ScraperService` condivisi
3. **IssueDetector** - Severity: `critical`, `warning`, `notice`, `info`
4. **AiAnalysisService** - Usa `AiService('seo-audit')`, MAI curl diretto
5. **OAuth GSC** - `GoogleOAuthService` centralizzato in `/services/`
6. **Categorie issue** - 10 crawler + 4 GSC (se connesso)
7. **Progress crawl** - Polling AJAX in `CrawlController`, NO SSE
8. **Stats sync** - Dopo crawl chiamare `Project::updateStats($projectId)`
9. **Sidebar** - Submenu gestito in `nav-items.php` con regex su path
10. **Session tracking** - Issues e pages salvano `session_id` per storico
11. **Health Score** - Calcolato in `CrawlSession::complete()` con formula cap

**Crediti** - Usare `Credits::getCost()` per costi dinamici (configurabili da admin):
```php
$cost = Credits::getCost('ai_overview', 'seo-audit');
$cost = Credits::getCost('ai_category', 'seo-audit');
$cost = Credits::getCost('gsc_sync', 'seo-audit');
```
Vedi: `docs/core/CREDITS-SYSTEM.md`

---

## PROMPT PRONTI

### 1. Aggiungere nuovo tipo di issue
```
Aggiungi nuovo tipo issue in modules/seo-audit/services/IssueDetector.php

ISSUE: [nome_issue]
CATEGORIA: [meta|headings|images|links|content|technical|schema|security]
SEVERITY: [critical|warning|notice|info]
CONDIZIONE: [quando rilevare]
TITOLO IT: [titolo in italiano]

Segui pattern esistente in detectMetaIssues(), detectHeadingIssues(), etc.
```

### 2. Fix problema crawl
```
Debug CrawlerService.php

PROBLEMA: [descrizione - es. timeout, pagine mancanti, loop]

FILE:
- modules/seo-audit/services/CrawlerService.php
- modules/seo-audit/controllers/CrawlController.php
- modules/seo-audit/models/CrawlSession.php

USA:
- SitemapService per sitemap
- ScraperService per fetch
```

### 3. Fix disallineamento stats
```
Le statistiche nel dashboard non corrispondono alla lista pagine/issues.

FILE:
- modules/seo-audit/models/Project.php (updateStats)
- modules/seo-audit/views/audit/dashboard.php

VERIFICA:
1. Dashboard usa $pageStats (conteggi reali) o $project (cache)?
2. updateStats() conta correttamente da sa_pages?
3. Dopo crawl viene chiamato updateStats()?
```

### 4. Migliorare analisi AI
```
Migliora prompt AI in modules/seo-audit/services/AiAnalysisService.php

OBIETTIVO: [es. analisi più dettagliata, consigli più specifici]

REGOLE:
- AiService('seo-audit') centralizzato
- Database::reconnect() dopo chiamata
- Prompt in italiano
- Cache in sa_ai_analyses
```

### 5. Debug OAuth GSC
```
Debug flusso OAuth GSC

FILE:
- modules/seo-audit/controllers/GscController.php
- services/GoogleOAuthService.php
- modules/seo-audit/services/GscService.php

PROBLEMA: [es. callback fallisce, token non salvato, refresh fallito]

FLOW:
1. /gsc/authorize -> Google OAuth
2. /oauth/google/callback -> GoogleOAuthService
3. /seo-audit/gsc/connected -> GscController::connected()
4. Token salvato in sa_gsc_connections
```

### 6. Aggiungere nuova vista
```
Aggiungi nuova vista al modulo seo-audit

VISTA: [nome]
PATH: /seo-audit/project/{id}/[path]
CONTROLLER: [AuditController o nuovo]

TEMPLATE BASE:
- Copia da views/audit/dashboard.php
- Layout con sidebar: $modules passato da controller
- Usa View::render('seo-audit::nome-vista', [...])
```

### 7. Fix sidebar submenu
```
Submenu sidebar non appare quando dentro un progetto

FILE:
- shared/views/components/nav-items.php

VERIFICA:
1. Regex match su currentPath: preg_match('#^/seo-audit/project/(\d+)#', ...)
2. Project model trova progetto: $projectModel->find($projectId)
3. Autoloader carica Modules\SeoAudit\Models\Project
4. Condizione: $seoAuditProjectId && $seoAuditProject
```

### 8. Debug storico/trend
```
Lo storico scansioni non mostra dati corretti

FILE:
- modules/seo-audit/models/CrawlSession.php (complete())
- modules/seo-audit/controllers/AuditController.php (history())
- modules/seo-audit/views/audit/history.php

VERIFICA:
1. session_id salvato su issues e pages durante crawl?
2. CrawlSession::complete() calcola health_score e counts?
3. Query in history() include health_score, critical_count, etc?
4. Dashboard mostra trend confrontando con sessione precedente?
```

---

## TESTING

```bash
# Test sidebar rendering
php tests/test-full-sidebar.php

# Test project lookup
php tests/test-project-lookup.php

# Test production autoloader
php tests/test-production-autoloader.php

# Test history view
php tests/test-history-view.php

# Verifica conteggi
php -r "
require 'config/environment.php';
require 'core/Database.php';
\$stats = Core\Database::fetch('SELECT COUNT(*) as total, SUM(status=\"crawled\") as crawled FROM sa_pages WHERE project_id = 1');
print_r(\$stats);
"

# Verifica storico sessioni
php -r "
require 'config/environment.php';
require 'core/Database.php';
\$sessions = Core\Database::fetchAll('SELECT id, health_score, critical_count, warning_count FROM sa_crawl_sessions WHERE project_id = 1 ORDER BY id DESC LIMIT 5');
print_r(\$sessions);
"
```

---

*Agente aggiornato - 2026-01-09*
