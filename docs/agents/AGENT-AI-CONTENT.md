# AGENTE: AI SEO Content Generator

> **Ultimo aggiornamento:** 2026-01-29

## CONTESTO

**Modulo:** `ai-content`
**Stato:** 100% Completato
**Prefisso DB:** `aic_`

Modulo per generare articoli SEO-ottimizzati con due modalità:

### Modalità MANUAL (Wizard 4 step)
1. **Keyword** - Inserimento keyword target
2. **SERP** - Estrazione risultati Google via SerpAPI
3. **Fonti** - Selezione competitor da scrappare
4. **Generazione** - Creazione articolo AI con brief

### Modalità AUTO (Scheduling per-keyword)
1. **Aggiungi Keyword** - Lista keyword senza data (bulk insert)
2. **Coda** - Inline edit per data/ora e numero fonti per ogni keyword
3. **Dispatcher CRON** - Processa automaticamente keyword con `scheduled_at <= NOW()`
4. **Auto-publish** - Opzionale su WordPress collegato

Include integrazione WordPress per pubblicazione diretta.

---

## FILE CHIAVE

```
modules/ai-content/
├── routes.php                              # Tutte le route del modulo
├── controllers/
│   ├── DashboardController.php             # Home modulo
│   ├── KeywordController.php               # CRUD keyword
│   ├── WizardController.php                # Flow 4 step (MANUAL mode)
│   ├── ArticleController.php               # Gestione articoli
│   ├── AutoController.php                  # AUTO mode (dashboard, queue, settings)
│   ├── JobController.php                   # Background jobs
│   └── WordPressController.php             # Integrazione WP
├── models/
│   ├── Keyword.php
│   ├── Article.php
│   ├── Queue.php                           # Coda keyword AUTO mode
│   ├── AutoConfig.php                      # Config automazione (semplificata)
│   ├── ProcessJob.php                      # Job processing
│   └── WpSite.php
├── services/
│   ├── SerpApiService.php                  # Chiamate SerpAPI
│   ├── ContentScraperService.php           # Scraping competitor
│   ├── BriefBuilderService.php             # Costruzione brief
│   └── ArticleGeneratorService.php         # Generazione AI
├── cron/
│   └── dispatcher.php                      # CRON job per AUTO mode
└── views/
    ├── dashboard.php
    ├── keywords/wizard.php                 # Wizard principale
    ├── articles/show.php                   # Preview articolo
    └── auto/
        ├── dashboard.php                   # Dashboard AUTO mode
        ├── add-keywords.php                # Form aggiunta keyword (solo lista)
        ├── queue.php                       # Coda con inline edit data/fonti
        └── settings.php                    # Solo auto-publish e WP site
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `aic_keywords` | Keyword salvate con lingua/location |
| `aic_serp_results` | Risultati SERP estratti |
| `aic_paa_questions` | People Also Ask |
| `aic_sources` | Fonti scrappate per articolo |
| `aic_articles` | Articoli generati |
| `aic_wp_sites` | Siti WordPress collegati |
| `aic_wp_publish_log` | Log pubblicazioni |

---

## BUG APERTI

| Bug | Severity | Status |
|-----|----------|--------|
| Nessun bug critico | - | ✅ |

**Fix recenti (2 Gen 2026):**
- Fix "MySQL server has gone away" con Database::reconnect()
- Fix UI preview articolo (classe `prose` Tailwind)
- Card Keywords cliccabile in dashboard

---

## GOLDEN RULES SPECIFICHE

1. **SerpAPI** - Usare SerpApiService, mai chiamate dirette
2. **Scraping** - ContentScraperService usa ScraperService condiviso
3. **AI** - ArticleGeneratorService usa AiService('ai-content')
4. **Database::reconnect()** - Chiamare PRIMA di salvare dopo operazioni AI lunghe
5. **Crediti** - Usare `Credits::getCost()` per costi dinamici (configurabili da admin):
   ```php
   $cost = Credits::getCost('serp_extraction', 'ai-content');
   $cost = Credits::getCost('content_scrape', 'ai-content');
   $cost = Credits::getCost('brief_generation', 'ai-content');
   $cost = Credits::getCost('article_generation', 'ai-content');
   ```
   Vedi: `docs/core/CREDITS-SYSTEM.md`

---

## PROMPT PRONTI

### 1. Fix bug nel wizard
```
Analizza il wizard in modules/ai-content/controllers/WizardController.php

BUG: [descrizione]

PRIMA leggi:
- docs/GOLDEN-RULES.md
- Il controller completo

Proponi fix specifico.
```

### 2. Migliorare generazione articoli
```
Migliora il prompt AI in modules/ai-content/services/ArticleGeneratorService.php

OBIETTIVO: [es. articoli più lunghi, più keyword, struttura diversa]

VINCOLI:
- Usare AiService centralizzato
- Mantenere format output (title, meta, html)
- Database::reconnect() dopo chiamata AI
```

### 3. Aggiungere nuova feature al wizard
```
Aggiungi [feature] al wizard ai-content.

REFERENCE:
- WizardController.php per flow esistente
- views/keywords/wizard.php per UI

REGOLE:
- UI in italiano
- Icone Heroicons SVG
- Gestione crediti se necessario
```

### 4. Debug integrazione WordPress
```
Debug integrazione WordPress in modules/ai-content/

FILE:
- controllers/WordPressController.php
- models/WpSite.php
- Plugin: storage/plugins/seo-toolkit-connector.php

PROBLEMA: [descrizione]
```

### 5. Ottimizzare scraping competitor
```
Ottimizza ContentScraperService.php per:
- [es. estrarre meglio i contenuti, gestire timeout, etc.]

USA ScraperService condiviso per HTTP.
Gestisci errori e retry.
```
