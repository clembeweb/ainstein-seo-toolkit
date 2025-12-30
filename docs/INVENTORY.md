# INVENTARIO FILE - SEO Toolkit

Data audit: 2025-12-19

## RIEPILOGO GENERALE

| Tipo | Conteggio |
|------|-----------|
| **File PHP Totali** | 179 |
| **Moduli** | 4 (+1 template) |
| **Servizi Condivisi** | 5 |
| **Core Files** | 8 |

---

## STRUTTURA PER DIRECTORY

| Directory | File PHP | Descrizione |
|-----------|----------|-------------|
| `/core/` | 8 | Framework core (Router, Database, Auth, etc.) |
| `/services/` | 5 | Servizi condivisi (AI, Scraper, CSV, Sitemap, Export) |
| `/admin/` | 12 | Pannello amministrazione |
| `/shared/` | 11 | Layout, componenti condivisi, auth views |
| `/config/` | 3 | Configurazione app, database, moduli |
| `/modules/internal-links/` | 28 | Modulo Internal Links Analyzer |
| `/modules/ai-content/` | 23 | Modulo AI SEO Content Generator |
| `/modules/seo-audit/` | 26 | Modulo SEO Audit |
| `/modules/seo-tracking/` | 53 | Modulo SEO Position Tracking |
| `/modules/_template/` | 2 | Template per nuovi moduli |
| `/cron/` | 2 | Job schedulati |
| `/tests/` | 3 | File di test |
| `/storage/plugins/` | 1 | WordPress Connector Plugin |
| `/public/` | 1 | Entry point |

---

## DETTAGLIO CORE (/core/)

| File | Descrizione |
|------|-------------|
| Auth.php | Autenticazione utenti |
| Credits.php | Gestione crediti |
| Database.php | PDO wrapper |
| Middleware.php | Middleware auth/CSRF |
| ModuleLoader.php | Caricamento moduli dinamico |
| Router.php | Routing |
| Settings.php | Gestione impostazioni |
| View.php | Template engine |

---

## DETTAGLIO SERVIZI CONDIVISI (/services/)

| File | Descrizione | Usato da |
|------|-------------|----------|
| AiService.php | Claude API wrapper | ai-content, seo-audit, seo-tracking |
| ScraperService.php | HTTP fetch con Guzzle/DomCrawler | internal-links, ai-content, seo-audit |
| CsvImportService.php | Parser CSV | internal-links |
| SitemapService.php | Parser sitemap XML | internal-links, seo-audit |
| ExportService.php | Export CSV/PDF | tutti |

---

## DETTAGLIO MODULO: internal-links (28 file)

### Controllers (4)
- AnalysisController.php
- LinkController.php
- ProjectController.php
- UrlController.php

### Models (4)
- InternalLink.php
- Project.php
- Snapshot.php
- Url.php

### Services (2)
- LinkExtractor.php
- Scraper.php (LOCAL - potenziale duplicato!)

### Views (17)
- analysis/index.php
- analyzer/index.php
- compare/index.php
- index.php
- links/graph.php
- links/index.php
- links/orphans.php
- projects/create.php
- projects/index.php
- projects/settings.php
- projects/show.php
- reports/anchors.php
- reports/juice.php
- reports/orphans.php
- scraper/index.php
- urls/import.php
- urls/index.php

### Config
- routes.php

---

## DETTAGLIO MODULO: ai-content (23 file)

### Controllers (6)
- ArticleController.php
- DashboardController.php
- KeywordController.php
- SerpController.php
- WizardController.php
- WordPressController.php

### Models (5)
- Article.php
- Keyword.php
- SerpResult.php
- Source.php
- WpSite.php

### Services (4)
- ArticleGeneratorService.php
- BriefBuilderService.php
- ContentScraperService.php (LOCAL - potenziale duplicato!)
- SerpApiService.php

### Views (7)
- articles/index.php
- articles/show.php
- dashboard.php
- keywords/index.php
- keywords/serp-results.php
- keywords/wizard.php
- wordpress/index.php

### Config
- routes.php

---

## DETTAGLIO MODULO: seo-audit (26 file)

### Controllers (5)
- AuditController.php
- CrawlController.php
- GscController.php
- ProjectController.php
- ReportController.php

### Models (3)
- Issue.php
- Page.php
- Project.php

### Services (5)
- AiAnalysisService.php
- AuditAnalyzer.php
- CrawlerService.php
- GscService.php
- IssueDetector.php

### Views (12)
- analysis/category.php
- analysis/overview.php
- audit/category.php
- audit/dashboard.php
- audit/issues.php
- audit/page-detail.php
- audit/pages.php
- gsc/connect.php
- gsc/dashboard.php
- gsc/properties.php
- projects/create.php
- projects/index.php

### Config
- routes.php

---

## DETTAGLIO MODULO: seo-tracking (53 file)

### Controllers (10)
- AiController.php
- AlertController.php
- ApiController.php
- DashboardController.php
- ExportController.php
- Ga4Controller.php
- GscController.php
- KeywordController.php
- ProjectController.php
- ReportController.php

### Models (14)
- AiReport.php
- Alert.php
- AlertSettings.php
- Ga4Connection.php
- Ga4Daily.php
- Ga4Data.php
- GscConnection.php
- GscDaily.php
- GscData.php
- Keyword.php
- KeywordPosition.php
- KeywordRevenue.php
- Project.php
- SyncLog.php

### Services (5)
- AiReportService.php
- AlertService.php
- Ga4Service.php
- GscService.php
- MarkdownService.php

### Views (17)
- alerts/index.php
- alerts/show.php
- dashboard/index.php
- dashboard/keywords.php
- dashboard/pages.php
- dashboard/revenue.php
- ga4/connect.php
- gsc/select-property.php
- keywords/create.php
- keywords/edit.php
- keywords/index.php
- keywords/show.php
- partials/project-nav.php
- projects/create.php
- projects/index.php
- projects/settings.php
- reports/create.php
- reports/index.php
- reports/show.php

### Cron (3)
- cron/daily-sync.php
- cron/monthly-reports.php
- cron/weekly-reports.php

### Helpers
- helpers.php

### Config
- routes.php

---

## MODULI MANCANTI (da specs)

| Modulo | Slug | Stato |
|--------|------|-------|
| AI Content Bulk Creator | content-creator | NON IMPLEMENTATO |

---

## FILE SOSPETTI / DA VERIFICARE

| File | Motivo |
|------|--------|
| test-serpapi-fix.php | File test in root |
| test-wp-ping.php | File test in root |
| tests/full-audit.php | File test |
| tests/test-scraper-service.php | File test |
| modules/internal-links/services/Scraper.php | Potenziale duplicato di ScraperService |
| modules/ai-content/services/ContentScraperService.php | Potenziale duplicato di ScraperService |

---

## PREFISSI DATABASE

| Modulo | Prefisso Atteso | Da Verificare |
|--------|-----------------|---------------|
| internal-links | il_ | ✓ |
| ai-content | aic_ | ✓ |
| seo-audit | sa_ | ✓ |
| seo-tracking | st_ | ✓ |
| content-creator | cc_ | N/A (non implementato) |
