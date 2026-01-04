# SEO AUDIT - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-audit` |
| **Prefisso DB** | `sa_` |
| **Files** | 26 |
| **Stato** | ✅ Completato (95%) |
| **Ultimo update** | 2026-01-02 |

Modulo per audit SEO completo con crawling, analisi issue e integrazione Google Search Console.

---

## Architettura Implementata

```
modules/seo-audit/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php      # CRUD progetti
│   ├── CrawlController.php        # Gestione crawl
│   ├── AuditController.php        # Dashboard e categorie
│   ├── GscController.php          # OAuth Google Search Console
│   └── ReportController.php       # Export
├── models/
│   ├── Project.php
│   ├── Page.php
│   ├── Issue.php
│   ├── AiAnalysis.php
│   └── GscConnection.php
├── services/
│   ├── CrawlerService.php         # Sitemap + Spider
│   ├── IssueDetector.php          # 50+ tipi issue
│   ├── AiAnalysisService.php      # Integrato con AiService
│   └── GscService.php             # API Google
└── views/
    ├── projects/
    ├── dashboard/
    ├── categories/
    └── reports/
```

---

## Database Schema

```sql
-- 12 tabelle con prefisso sa_
sa_projects           -- Progetti audit
sa_pages              -- Pagine crawlate
sa_issues             -- Issue rilevate
sa_ai_analyses        -- Analisi AI generate
sa_site_config        -- Config robots/sitemap
sa_gsc_connections    -- Token OAuth GSC
sa_gsc_performance    -- Dati performance GSC
sa_gsc_coverage       -- Copertura indice
sa_gsc_core_web_vitals
sa_gsc_mobile_usability
sa_gsc_sync_log
sa_categories         -- Categorie issue
```

---

## Funzionalità Implementate

### Crawling
- [x] Sitemap discovery (index + nested)
- [x] Spider ricorsivo con depth limit
- [x] Rate limiting (5 req/sec)
- [x] Progress real-time SSE

### Issue Detection (50+ tipi)
- [x] Meta tags (title, description, OG)
- [x] Headings (H1 mancante/multiplo)
- [x] Images (alt mancanti)
- [x] Links (broken, redirect chains)
- [x] Content (thin, duplicati)
- [x] Technical (canonical, robots, noindex)
- [x] Schema markup
- [x] Security (HTTPS, mixed content)

### Google Search Console
- [x] OAuth2 flow
- [x] Lista proprietà
- [x] Import performance data
- [x] Import coverage data
- [x] Core Web Vitals

### AI Analysis
- [x] Overview globale (15 crediti)
- [x] Analisi per categoria (3 crediti)
- [x] Integrato con AiService centralizzato

### Export
- [x] CSV issues
- [ ] PDF report (TODO)

---

## Crediti

| Azione | Costo |
|--------|-------|
| Crawl per pagina | 0.2 |
| GSC sync | 5 |
| AI overview | 15 |
| AI categoria | 3 |

---

## Routes Principali

```php
GET  /seo-audit                           # Lista progetti
GET  /seo-audit/projects/create           # Form creazione
POST /seo-audit/projects/store            # Salva progetto
GET  /seo-audit/projects/{id}             # Dashboard audit
POST /seo-audit/projects/{id}/crawl       # Avvia crawl
GET  /seo-audit/projects/{id}/category/{slug}  # Dettaglio categoria
POST /seo-audit/projects/{id}/analyze     # Genera analisi AI
GET  /seo-audit/projects/{id}/export/csv  # Export CSV
```

---

## Bug Noti

| Bug | Severity | Status |
|-----|----------|--------|
| - | - | Nessun bug critico |

---

## Note Implementazione

1. **OAuth GSC** condiviso con seo-tracking via `GoogleOAuthService`
2. **AiAnalysisService** usa AiService centralizzato (no curl diretto)
3. **CrawlerService** gestisce timeout e retry automatici
4. **IssueDetector** severity: critical, warning, notice, info

---

*Spec verificata - 2026-01-02*
