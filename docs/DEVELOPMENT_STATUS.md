# SEO TOOLKIT - Stato Sviluppo

**Ultimo aggiornamento:** 2025-12-19
**Versione:** 1.0.0-beta

---

## 📊 PANORAMICA MODULI

| Modulo | Slug | Stato | Funzionalità | Note |
|--------|------|-------|--------------|------|
| **AI SEO Content Generator** | `ai-content` | ✅ FUNZIONANTE | 98% | Modulo principale, wizard completo |
| **Internal Links Analyzer** | `internal-links` | ⚠️ PARZIALE | 85% | Bug UI minori (icone, modelli AI) |
| **SEO Audit** | `seo-audit` | ⚠️ PARZIALE | 90% | Bug logica crawl, manca model |
| **SEO Position Tracking** | `seo-tracking` | 🔴 DA FIXARE | 70% | 5 bug critici routes/controller |
| **AI Content Bulk Creator** | `content-creator` | ❌ NON IMPLEMENTATO | 0% | Solo specifiche pronte |

---

## ✅ AI SEO CONTENT GENERATOR (ai-content)

### Stato: FUNZIONANTE ✅

Il modulo più maturo della piattaforma. Wizard a 4 step completamente operativo.

### Funzionalità Operative
- ✅ Gestione keyword con status workflow
- ✅ Step 1: Analisi SERP via SerpAPI
- ✅ Step 2: Scraping competitor e generazione brief AI
- ✅ Step 3: Generazione articolo completo con AI
- ✅ Step 4: Pubblicazione su WordPress
- ✅ Integrazione AiService corretta con module_slug
- ✅ Integrazione ScraperService per HTTP
- ✅ WordPress connector funzionante

### File Chiave
```
modules/ai-content/
├── controllers/
│   ├── WizardController.php      # Gestisce i 4 step
│   ├── KeywordController.php     # CRUD keyword
│   └── WordPressController.php   # Integrazione WP
├── services/
│   ├── SerpApiService.php        # Chiamate SerpAPI
│   ├── ContentScraperService.php # Scraping pagine
│   ├── BriefBuilderService.php   # Generazione brief AI
│   └── ArticleGeneratorService.php # Generazione articolo AI
└── views/keywords/wizard.php     # UI wizard 4 step
```

### Modello AI Utilizzato
```php
'model' => 'claude-sonnet-4-20250514'  // ✅ Corretto
```

### Nessun Bug Critico
Il modulo è reference per gli altri.

---

## ⚠️ INTERNAL LINKS ANALYZER (internal-links)

### Stato: PARZIALE - Bug UI ⚠️

Funzionalità core operative, problemi di UI e modelli AI deprecati.

### Funzionalità Operative
- ✅ CRUD progetti
- ✅ Import URL (CSV, Sitemap, Manual)
- ✅ Scraping batch URL
- ✅ Estrazione link interni
- ✅ Navigazione accordion sidebar

### Bug da Fixare

| # | Severity | File | Problema |
|---|----------|------|----------|
| 1 | HIGH | views/links/*.php, views/reports/*.php | Usa Lucide icons invece di Heroicons |
| 2 | HIGH | views/analysis/index.php:134-135 | Modelli AI deprecati (claude-3-haiku/sonnet) |
| 3 | MEDIUM | routes.php:107-108 | GET params non sanitizzati |
| 4 | LOW | controllers/AnalysisController.php | Non implementato (solo TODO) |
| 5 | LOW | controllers/LinkController.php | Non implementato (solo TODO) |
| 6 | LOW | controllers/UrlController.php | Non implementato (solo TODO) |

### Fix Richiesti
1. Sostituire `data-lucide="icon"` con SVG Heroicons inline
2. Rimuovere opzioni modello `claude-3-haiku` e `claude-3-sonnet`
3. Sanitizzare `$_GET['status']` e `$_GET['search']`
4. Implementare o rimuovere controller vuoti

---

## ⚠️ SEO AUDIT (seo-audit)

### Stato: PARZIALE - Bug Logica ⚠️

Struttura completa, bug nella logica del crawl.

### Funzionalità Operative
- ✅ CRUD progetti audit
- ✅ Crawl pagine via sitemap/spider
- ✅ Rilevamento issues SEO
- ✅ Dashboard con health score
- ✅ Analisi AI per categoria
- ✅ Integrazione GSC (OAuth)

### Bug da Fixare

| # | Severity | File:Linea | Problema |
|---|----------|------------|----------|
| 1 | MEDIUM | controllers/CrawlController.php:138 | Query DB diretta, manca SiteConfig model |
| 2 | MEDIUM | controllers/CrawlController.php:291 | Logic error: status sempre 'completed' |

### Fix Richiesti
1. Creare `models/SiteConfig.php` e usarlo nel controller
2. Correggere ternario: `$stopped ? 'stopped' : 'completed'`

---

## 🔴 SEO POSITION TRACKING (seo-tracking)

### Stato: DA FIXARE - Bug Critici 🔴

Modulo con più file (53) ma con problemi strutturali routes/controller.

### Funzionalità Previste
- Tracking posizioni keyword
- Integrazione GSC + GA4
- Report AI automatici
- Sistema alert
- Dashboard revenue

### Bug CRITICI (Bloccanti)

| # | File | Problema | Fix |
|---|------|----------|-----|
| 1 | services/AiReportService.php:444 | Usa curl diretto invece di AiService | Refactoring a AiService('seo-tracking') |
| 2 | controllers/KeywordController.php | Metodo add() non esiste | Creare add() o cambiare route |
| 3 | controllers/KeywordController.php | Metodo all() non esiste | Creare all() o rimuovere route |
| 4 | controllers/KeywordController.php:161 | show() vs detail() mismatch | Rinominare o aggiornare route |
| 5 | controllers/KeywordController.php:241 | Signature update() errata | Fix: update(int $projectId, int $keywordId) |

### Bug HIGH (Redirect Paths Errati)

| Linee | Path Errato | Path Corretto |
|-------|-------------|---------------|
| 118,155,274,302,350,394,458,495 | /seo-tracking/keywords/{id}/add | /seo-tracking/projects/{id}/keywords/add |
| GscController:126 | /seo-tracking/gsc/{id}/select-property | /seo-tracking/projects/{id}/gsc/select-property |

### Bug MEDIUM

| File | Problema |
|------|----------|
| services/AlertService.php:237 | @mail() sopprime errori, aggiungere logging |

---

## ❌ AI CONTENT BULK CREATOR (content-creator)

### Stato: NON IMPLEMENTATO ❌

Le specifiche sono complete in `docs/specs/ai-content-bulk-creator-specs.md`.

### Da Implementare
- 4 tabelle DB (cc_projects, cc_urls, cc_connectors, cc_operations_log)
- 4 controller
- 4 model
- 4 service
- UI completa
- WordPress/WooCommerce connector

### Prefisso DB: `cc_`

---

## 🔧 SERVIZI CONDIVISI

### /services/ - Stato Utilizzo

| Servizio | ai-content | internal-links | seo-audit | seo-tracking |
|----------|------------|----------------|-----------|--------------|
| AiService.php | ✅ | ✅ | ✅ | ❌ (usa curl) |
| ScraperService.php | ✅ | ✅ | ✅ | N/A |
| CsvImportService.php | N/A | ❌ (non usato) | N/A | N/A |
| SitemapService.php | N/A | ✅ | ✅ | N/A |
| ExportService.php | N/A | N/A | ✅ | N/A |

### Regola Fondamentale
```php
// ✅ CORRETTO - Sempre specificare module_slug
$aiService = new AiService('nome-modulo');

// ❌ SBAGLIATO - Mai usare curl diretto per AI
$ch = curl_init('https://api.anthropic.com/...');
```

---

## 📋 PIANO FIX ORDINATO

### Fase 1: CRITICAL (seo-tracking) - ~30 min
1. [ ] Refactoring AiReportService → AiService
2. [ ] Fix KeywordController metodi mancanti
3. [ ] Fix KeywordController signature update()
4. [ ] Fix tutti i redirect paths (8 occorrenze)
5. [ ] Fix GscController redirect OAuth

### Fase 2: HIGH (internal-links + seo-tracking) - ~20 min
6. [ ] Migrare Lucide → Heroicons (5 file views)
7. [ ] Rimuovere modelli AI deprecati
8. [ ] Creare metodo detail() in KeywordController

### Fase 3: MEDIUM - ~15 min
9. [ ] Creare SiteConfig model in seo-audit
10. [ ] Fix logic error status crawl
11. [ ] Sanitizzare GET params in routes.php
12. [ ] Fix @mail() error handling

### Fase 4: LOW - ~10 min
13. [ ] Decidere su controller vuoti (implementare o rimuovere)

### Fase 5: MIGLIORAMENTI
14. [ ] Implementare CsvImportService in internal-links
15. [ ] Aggiungere test automatizzati
16. [ ] Implementare modulo content-creator

---

## 📁 STATISTICHE PROGETTO

| Metrica | Valore |
|---------|--------|
| File PHP totali | 179 |
| Moduli attivi | 4 |
| Moduli da implementare | 1 |
| Tabelle DB | 32 |
| Bug CRITICAL | 5 |
| Bug HIGH | 4 |
| Bug MEDIUM | 4 |
| Bug LOW | 3 |
| **Bug TOTALI** | **16** |

---

## 🔐 SICUREZZA

### ✅ Punti Positivi
- Tutte le query SQL usano prepared statements
- CSRF token presente su tutti i form POST
- Nessuna API key hardcoded
- Input numerici castati correttamente

### ⚠️ Da Migliorare
- Sanitizzare alcuni GET parameters
- Error logging su operazioni critiche

---

## 📝 NOTE SVILUPPO

### Modello AI Corretto
```
claude-sonnet-4-20250514
```
NON usare: claude-3-haiku, claude-3-sonnet, claude-3-opus

### Icone
Usare **Heroicons** (SVG inline), NON Lucide icons.

### Lingua UI
Tutto in **ITALIANO**, eccetto termini tecnici (URL, CSV, API, SEO, etc.)

### Pattern Routes
```
/modulo/projects/{projectId}/sezione
```

### AiService
Sempre con module_slug per logging:
```php
$ai = new AiService('nome-modulo');
```

---

## 📚 DOCUMENTAZIONE

| File | Contenuto |
|------|-----------|
| docs/PLATFORM_STANDARDS.md | Convenzioni globali, lingua IT |
| docs/PLATFORM_OVERVIEW.md | Architettura completa |
| docs/MODULE_NAVIGATION.md | Standard navigazione sidebar |
| docs/IMPORT_STANDARDS.md | Pattern import URL |
| docs/AI_SERVICE_STANDARDS.md | Standard chiamate AI |
| docs/COMPLIANCE_CHECKLIST.md | Checklist requisiti |
| docs/specs/*.md | Specifiche tecniche moduli |

---

## 🎯 OBIETTIVO IMMEDIATO

**Portare tutti i moduli a stato FUNZIONANTE:**

1. seo-tracking: 70% → 95% (fix bug critici)
2. internal-links: 85% → 95% (fix UI)
3. seo-audit: 90% → 95% (fix logica)
4. content-creator: 0% → 100% (implementazione)

---

*Documento generato dall'audit del 2025-12-19*
