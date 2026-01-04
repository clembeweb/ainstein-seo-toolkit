# AGENTE: SEO Audit

## CONTESTO

**Modulo:** `seo-audit`
**Stato:** 95% Completato
**Prefisso DB:** `sa_`

Modulo per audit SEO completo di siti web:
- Crawling (sitemap + spider)
- Rilevamento 50+ tipi di issue
- Integrazione Google Search Console
- Analisi AI per categoria
- Export CSV

Ispirato a SEMrush Site Audit.

---

## FILE CHIAVE

```
modules/seo-audit/
├── routes.php
├── controllers/
│   ├── ProjectController.php               # CRUD progetti
│   ├── CrawlController.php                 # Gestione crawl SSE
│   ├── AuditController.php                 # Dashboard + categorie
│   ├── GscController.php                   # OAuth Google
│   └── ReportController.php                # Export
├── models/
│   ├── Project.php
│   ├── Page.php
│   ├── Issue.php
│   └── GscConnection.php
├── services/
│   ├── CrawlerService.php                  # Sitemap + Spider
│   ├── IssueDetector.php                   # 50+ tipi issue
│   ├── AiAnalysisService.php               # Analisi AI
│   └── GscService.php                      # API Google
└── views/
    ├── audit/dashboard.php                 # Health score
    ├── audit/category.php                  # Dettaglio categoria
    └── gsc/connect.php                     # OAuth flow
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `sa_projects` | Progetti audit con status e health_score |
| `sa_pages` | Pagine crawlate con tutti i dati estratti |
| `sa_issues` | Issue rilevate (category, severity, type) |
| `sa_ai_analyses` | Analisi AI generate |
| `sa_site_config` | Config robots.txt e sitemap |
| `sa_gsc_connections` | Token OAuth GSC |
| `sa_gsc_performance` | Dati performance GSC |
| `sa_gsc_coverage` | Copertura indice |
| `sa_gsc_core_web_vitals` | LCP, INP, CLS |
| `sa_gsc_mobile_usability` | Issue mobile |
| `sa_gsc_sync_log` | Log sync |

---

## BUG APERTI

| Bug | Severity | Status |
|-----|----------|--------|
| Nessun bug critico | - | ✅ |

**Note:**
- OAuth GSC condiviso con seo-tracking via GoogleOAuthService
- PDF export non ancora implementato (solo CSV)

---

## GOLDEN RULES SPECIFICHE

1. **CrawlerService** - Usa SitemapService e ScraperService condivisi
2. **IssueDetector** - Severity: critical, warning, notice, info
3. **AiAnalysisService** - Usa AiService('seo-audit'), mai curl
4. **OAuth** - GoogleOAuthService centralizzato in /services/
5. **Categorie issue** - 10 crawler + 4 GSC (se connesso)
6. **Progress crawl** - SSE real-time in CrawlController
7. **Crediti:**
   - Crawl per pagina: 0.2 crediti
   - GSC sync: 5 crediti
   - AI overview: 15 crediti
   - AI categoria: 3 crediti

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
- services/CrawlerService.php
- controllers/CrawlController.php

USA:
- SitemapService per sitemap
- ScraperService per fetch
```

### 3. Migliorare analisi AI
```
Migliora prompt AI in modules/seo-audit/services/AiAnalysisService.php

OBIETTIVO: [es. analisi più dettagliata, consigli più specifici]

REGOLE:
- AiService('seo-audit') centralizzato
- Database::reconnect() dopo chiamata
- Prompt in italiano
```

### 4. Debug OAuth GSC
```
Debug flusso OAuth GSC

FILE:
- modules/seo-audit/controllers/GscController.php
- services/GoogleOAuthService.php

PROBLEMA: [es. callback fallisce, token non salvato]

Redirect URI: https://ainstein.it/oauth/google/callback
```

### 5. Aggiungere export PDF
```
Implementa export PDF in modules/seo-audit/controllers/ReportController.php

USA ExportService condiviso.
Includi: health score, top issues, grafici categorie.
Output in italiano.
```
