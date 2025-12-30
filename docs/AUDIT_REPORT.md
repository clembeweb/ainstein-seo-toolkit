# AUDIT REPORT - SEO TOOLKIT

Data: 2025-12-19
Auditor: Claude Code (Opus 4.5)

---

## 1. RIEPILOGO STATO MODULI

| Modulo | Files PHP | Tabelle DB | Compliance % | Bug Critici | Bug Warning | Stato |
|--------|-----------|------------|--------------|-------------|-------------|-------|
| **internal-links** | 28 | il_* (4) | 85% | 0 | 3 | BUONO |
| **ai-content** | 23 | aic_* (5) | 98% | 0 | 0 | ECCELLENTE |
| **seo-audit** | 26 | sa_* (9) | 90% | 0 | 2 | BUONO |
| **seo-tracking** | 53 | st_* (14) | 70% | 5 | 2 | RICHIEDE INTERVENTO |
| **content-creator** | 0 | cc_* (0) | N/A | N/A | N/A | NON IMPLEMENTATO |

---

## 2. COMPLIANCE DOCUMENTAZIONE

| Requisito (da docs/) | Rispettato? | Note |
|---------------------|-------------|------|
| UI in italiano | PASS | Verificato su tutti i moduli |
| Heroicons (non Lucide) | FAIL | internal-links usa Lucide icons |
| Prefissi DB corretti | PASS | il_, aic_, sa_, st_ verificati |
| CSRF su form POST | PASS | Token presente su tutti i form |
| AiService con module_slug | FAIL | seo-tracking usa curl diretto |
| Modello claude-sonnet-4-20250514 | FAIL | internal-links ha modelli deprecati |
| ScraperService per HTTP | PASS | Usato correttamente (eccetto API Google) |
| CsvImportService per CSV | FAIL | Non usato da nessun modulo |
| Prepared statements | PASS | Tutte le query usano parametri |
| Naming conventions | PASS | Controller PascalCase, views kebab-case |

---

## 3. USO SERVIZI CONDIVISI

| Modulo | AiService | ScraperService | CsvImport | Sitemap | Export |
|--------|-----------|----------------|-----------|---------|--------|
| internal-links | PASS | PASS (via wrapper) | FAIL (non usato) | PASS | N/A |
| ai-content | PASS | PASS | N/A | N/A | N/A |
| seo-audit | PASS | PASS | N/A | PASS | PASS |
| seo-tracking | FAIL (curl diretto) | N/A | N/A | N/A | N/A |

---

## 4. BUG TROVATI (ordinati per priorita)

### CRITICAL (5) - Bloccanti

| # | Modulo | File:Linea | Descrizione | Fix Suggerito |
|---|--------|------------|-------------|---------------|
| 1 | seo-tracking | services/AiReportService.php:444 | Usa curl diretto invece di AiService | Refactoring per usare AiService condiviso |
| 2 | seo-tracking | controllers/KeywordController.php | Metodo add() non esiste | Creare metodo add() o cambiare route a create() |
| 3 | seo-tracking | controllers/KeywordController.php:118,155,274,302,350,394,458,495 | Redirect path errati | Cambiare da /seo-tracking/keywords/{id}/add a /seo-tracking/projects/{id}/keywords/add |
| 4 | seo-tracking | controllers/KeywordController.php:241 | Signature update() non corrisponde a route | Cambiare signature a update(int $projectId, int $keywordId) |
| 5 | seo-tracking | controllers/GscController.php:126 | Redirect path errato dopo OAuth | Cambiare da /seo-tracking/gsc/{id}/select-property a /seo-tracking/projects/{id}/gsc/select-property |

### HIGH (4) - Importanti

| # | Modulo | File:Linea | Descrizione | Fix Suggerito |
|---|--------|------------|-------------|---------------|
| 6 | internal-links | views/links/index.php, orphans.php, reports/*.php | Usa Lucide icons invece di Heroicons | Sostituire data-lucide con SVG inline Heroicons |
| 7 | seo-tracking | controllers/KeywordController.php | Metodo all() non esiste | Implementare all() o rimuovere route |
| 8 | seo-tracking | controllers/KeywordController.php:161 | Metodo show() chiamato come detail() da routes | Rinominare show() in detail() o aggiornare route |
| 9 | internal-links | views/analysis/index.php:134-135 | Modelli AI deprecati claude-3-haiku/claude-3-sonnet | Aggiornare a modelli validi (claude-sonnet-4-20250514) |

### MEDIUM (4) - Da sistemare

| # | Modulo | File:Linea | Descrizione | Fix Suggerito |
|---|--------|------------|-------------|---------------|
| 10 | seo-audit | controllers/CrawlController.php:138 | Query DB diretta invece di usare Model | Creare SiteConfig model |
| 11 | seo-audit | controllers/CrawlController.php:291 | Logic error: status sempre 'completed' | Cambiare in $stopped ? 'stopped' : 'completed' |
| 12 | internal-links | routes.php:107-108 | GET parameters non sanitizzati | Aggiungere validazione per $_GET['status'] e $_GET['search'] |
| 13 | seo-tracking | services/AlertService.php:237 | @mail() sopprime errori | Rimuovere @ e aggiungere error logging |

### LOW (3) - Miglioramenti

| # | Modulo | File:Linea | Descrizione | Fix Suggerito |
|---|--------|------------|-------------|---------------|
| 14 | internal-links | controllers/AnalysisController.php | Controller non implementato (solo TODO) | Implementare o rimuovere |
| 15 | internal-links | controllers/LinkController.php | Controller non implementato (solo TODO) | Implementare o rimuovere |
| 16 | internal-links | controllers/UrlController.php | Controller non implementato (solo TODO) | Implementare o rimuovere |

---

## 5. INCONSISTENZE UI

| View | Problema | Fix |
|------|----------|-----|
| internal-links/views/links/index.php | Lucide icons (data-lucide="download") | Sostituire con SVG Heroicons |
| internal-links/views/links/orphans.php | Lucide icons (6 istanze) | Sostituire con SVG Heroicons |
| internal-links/views/reports/anchors.php | Lucide icons | Sostituire con SVG Heroicons |
| internal-links/views/reports/juice.php | Lucide icons | Sostituire con SVG Heroicons |
| internal-links/views/reports/orphans.php | Lucide icons | Sostituire con SVG Heroicons |
| internal-links/views/analysis/index.php | Opzioni modello AI obsolete | Rimuovere claude-3-haiku e claude-3-sonnet |

---

## 6. PROBLEMI SICUREZZA

| Severity | File | Problema | Fix |
|----------|------|----------|-----|
| LOW | internal-links/routes.php:107-108 | GET params non sanitizzati | Sanitizzare prima uso in query |
| INFO | core/Database.php | PDO con prepared statements | OK - Nessun fix richiesto |
| INFO | Tutti i form | CSRF token presente | OK - Nessun fix richiesto |
| INFO | admin/views/settings.php | Placeholder API key (non hardcoded) | OK - Nessun fix richiesto |

**Note positive sulla sicurezza:**
- Tutte le query usano prepared statements (PDO::ATTR_EMULATE_PREPARES => false)
- CSRF token implementato su tutti i form POST
- Nessuna API key hardcoded nel codice
- Input numerici castati a (int) correttamente

---

## 7. AZIONI RICHIESTE (ordinate per priorita)

### IMMEDIATO (Blockers)

1. **[CRITICAL]** seo-tracking: Refactoring AiReportService per usare AiService condiviso
   - File: modules/seo-tracking/services/AiReportService.php
   - Azione: Sostituire callClaude() con chiamata a AiService('seo-tracking')

2. **[CRITICAL]** seo-tracking: Fix route/controller mismatches in KeywordController
   - Files: routes.php + controllers/KeywordController.php
   - Azione: Allineare metodi e paths

3. **[CRITICAL]** seo-tracking: Fix redirect paths in controllers
   - Files: KeywordController.php, GscController.php
   - Azione: Aggiornare tutti i redirect() a paths corretti

### ALTA PRIORITA

4. **[HIGH]** internal-links: Migrare da Lucide a Heroicons
   - Files: views/links/*.php, views/reports/*.php
   - Azione: Sostituire data-lucide con SVG inline

5. **[HIGH]** internal-links: Rimuovere modelli AI deprecati
   - File: views/analysis/index.php:134-135
   - Azione: Rimuovere opzioni claude-3-haiku e claude-3-sonnet

### MEDIA PRIORITA

6. **[MEDIUM]** seo-audit: Creare SiteConfig model
   - File: controllers/CrawlController.php:138
   - Azione: Creare models/SiteConfig.php e usarlo

7. **[MEDIUM]** seo-audit: Fix logic error status crawl
   - File: controllers/CrawlController.php:291
   - Azione: Correggere ternario

8. **[MEDIUM]** internal-links: Sanitizzare GET parameters
   - File: routes.php:107-108
   - Azione: Validare status e search prima di uso

### BASSA PRIORITA

9. **[LOW]** Implementare o rimuovere controller vuoti in internal-links
10. **[LOW]** Migliorare error handling in AlertService email

---

## 8. MODULI MANCANTI

### content-creator

**Stato:** NON IMPLEMENTATO

Secondo le specifiche in `docs/specs/ai-content-bulk-creator-specs.md`:

| Componente | Specifiche | Implementato |
|------------|-----------|--------------|
| module.json | Definito | NO |
| Database (cc_*) | 4 tabelle specificate | NO |
| Controllers | 4 controller specificati | NO |
| Models | 4 model specificati | NO |
| Services | 4 service specificati | NO |
| Views | UI completa specificata | NO |
| WordPress Connector | WooCommerce integration | NO |

**Azione richiesta:** Implementare l'intero modulo seguendo le specifiche

---

## 9. STATISTICHE GENERALI

### File Count
- **Totale file PHP:** 179
- **Core:** 8 file
- **Services condivisi:** 5 file
- **Moduli:** 130 file
- **Admin:** 12 file
- **Shared:** 11 file

### Database
- **Tabelle totali:** 32
- **internal-links:** 4 tabelle (il_*)
- **ai-content:** 5 tabelle (aic_*)
- **seo-audit:** 9 tabelle (sa_*)
- **seo-tracking:** 14 tabelle (st_*)

### Test Coverage
- File di test presenti: 3
- Coverage stimata: Non calcolabile (nessun framework di test)

---

## 10. RACCOMANDAZIONI ARCHITETTURALI

### Immediate
1. Centralizzare tutte le chiamate HTTP in ScraperService
2. Standardizzare l'uso di AiService in tutti i moduli
3. Implementare CsvImportService nel modulo internal-links

### A medio termine
1. Aggiungere unit tests per i servizi critici
2. Implementare il modulo content-creator
3. Creare documentazione API per ogni modulo

### A lungo termine
1. Considerare migrazione a framework PHP (Laravel/Symfony)
2. Implementare sistema di caching centralizzato
3. Aggiungere logging strutturato (PSR-3)

---

## 11. CONCLUSIONI

Il progetto SEO Toolkit ha una struttura solida e segue buone pratiche per la maggior parte dei componenti. I problemi principali sono concentrati nel modulo **seo-tracking** che richiede interventi immediati.

**Punti di forza:**
- Architettura modulare ben definita
- Servizi condivisi riutilizzabili
- Sicurezza SQL (prepared statements)
- UI italiana coerente
- Sistema di crediti funzionante

**Aree di miglioramento:**
- Standardizzare uso servizi condivisi (specialmente AiService)
- Completare migrazione a Heroicons
- Implementare modulo content-creator
- Aggiungere test automatizzati

---

*Report generato automaticamente dall'audit completo del 2025-12-19*
