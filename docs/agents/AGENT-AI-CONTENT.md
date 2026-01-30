# AGENTE: AI SEO Content Generator

> **Ultimo aggiornamento:** 2026-01-30

## CONTESTO

**Modulo:** `ai-content`
**Stato:** 100% Completato
**Prefisso DB:** `aic_`

Modulo per generare contenuti SEO-ottimizzati con **3 tipologie di progetto**:

---

## ARCHITETTURA UI

### Dashboard Principale (Tabbed Interface)
La dashboard principale `/ai-content` presenta una **interfaccia a tab** con 3 tipologie di progetto:

| Tab | Tipo Progetto | Descrizione |
|-----|---------------|-------------|
| **Articoli Manuali** | `manual` | Generazione articoli uno alla volta con controllo completo |
| **Articoli Automatici** | `auto` | Generazione batch da lista keyword con scheduling |
| **SEO Meta Tags** | `meta-tag` | Generazione title/description per pagine esistenti |

### Stats sulle Card Progetto (per tipo)

**Manual:**
- Keywords (conteggio)
- Articoli (conteggio)
- Pubblicati (conteggio)

**Auto:**
- In Coda (keyword pending)
- Generati (articoli)
- Pubblicati (articoli)

**Meta-Tag:**
- URL (totali importate)
- Scrappate (URL processate)
- Generate (meta tags creati)
- Pubblicate (sincronizzate su WP)

### Link Globali nella Sidebar/Header
Sempre visibili nella parte superiore della pagina:
- **Gestione Job** (`/ai-content/jobs`) - Monitoraggio job in background
- **Siti WordPress** (`/ai-content/wordpress`) - Gestione connessioni WP

---

## NAVIGAZIONE PROGETTO (Submenu Dinamico)

Quando si entra in un progetto, appare una **navigazione a tab orizzontale** che varia in base al tipo progetto.

### Progetto MANUAL
```
Dashboard | Keywords | Articoli | Link Interni | Impostazioni
```

### Progetto AUTO
```
Dashboard | Coda | Articoli | Link Interni | Impostazioni
```

### Progetto META-TAG
```
Dashboard | Meta Tags | Import | Impostazioni
```

File: `views/partials/project-nav.php`

---

## TIPOLOGIE DI PROGETTO

### 1. Modalita MANUAL (Wizard 4 step)
1. **Keyword** - Inserimento keyword target
2. **SERP** - Estrazione risultati Google via SerpAPI
3. **Fonti** - Selezione competitor da scrappare
4. **Generazione** - Creazione articolo AI con brief

### 2. Modalita AUTO (Scheduling per-keyword)
1. **Aggiungi Keyword** - Lista keyword senza data (bulk insert)
2. **Coda** - Inline edit per data/ora e numero fonti per ogni keyword
3. **Dispatcher CRON** - Processa automaticamente keyword con `scheduled_at <= NOW()`
4. **Auto-publish** - Opzionale su WordPress collegato

### 3. Modalita META-TAG (Bulk meta generation)
1. **Import URL** - Da CSV, sitemap o manuale
2. **Scrape** - Estrazione contenuto pagine
3. **Generazione** - Creazione meta title/description con AI
4. **Approvazione** - Review e modifica manuale
5. **Pubblicazione** - Sync su WordPress via REST API

Include integrazione WordPress per pubblicazione diretta.

---

## FILE CHIAVE

```
modules/ai-content/
├── routes.php                              # Tutte le route del modulo
├── controllers/
│   ├── DashboardController.php             # Home modulo (redirect a projects)
│   ├── ProjectController.php               # CRUD progetti + dashboard tabbed
│   ├── KeywordController.php               # CRUD keyword (MANUAL mode)
│   ├── WizardController.php                # Flow 4 step (MANUAL mode)
│   ├── ArticleController.php               # Gestione articoli
│   ├── AutoController.php                  # AUTO mode (dashboard, queue, settings)
│   ├── MetaTagController.php               # META-TAG mode (dashboard, list, import)
│   ├── InternalLinksController.php         # Pool link interni per progetti
│   ├── JobController.php                   # Background jobs (globale)
│   ├── SerpController.php                  # Estrazione SERP
│   └── WordPressController.php             # Integrazione WP (globale)
├── models/
│   ├── Project.php                         # Progetti con campo `type`
│   ├── Keyword.php
│   ├── Article.php
│   ├── Queue.php                           # Coda keyword AUTO mode
│   ├── AutoConfig.php                      # Config automazione
│   ├── ProcessJob.php                      # Job processing
│   ├── InternalLinksPool.php               # Pool link interni
│   └── WpSite.php
├── services/
│   ├── SerpApiService.php                  # Chiamate SerpAPI
│   ├── ContentScraperService.php           # DEPRECATO - usa ScraperService
│   ├── BriefBuilderService.php             # Costruzione brief
│   └── ArticleGeneratorService.php         # Generazione AI (usa pool link)
├── cron/
│   ├── dispatcher.php                      # CRON job per AUTO mode
│   └── process_queue.php                   # Processore coda
└── views/
    ├── dashboard.php                       # Dashboard singolo progetto
    ├── projects/
    │   ├── index.php                       # Lista progetti con tabs per tipo
    │   ├── create.php                      # Form creazione progetto
    │   └── settings.php                    # Impostazioni progetto
    ├── partials/
    │   └── project-nav.php                 # Navigation tabs (dinamica per tipo)
    ├── keywords/
    │   ├── index.php                       # Lista keyword
    │   ├── wizard.php                      # Wizard 4 step
    │   └── serp-results.php                # Risultati SERP
    ├── articles/
    │   ├── index.php                       # Lista articoli
    │   └── show.php                        # Preview articolo
    ├── auto/
    │   ├── dashboard.php                   # Dashboard AUTO mode
    │   ├── add-keywords.php                # Form bulk keywords
    │   ├── queue.php                       # Gestione coda
    │   └── settings.php                    # Impostazioni AUTO
    ├── meta-tags/
    │   ├── dashboard.php                   # Dashboard META-TAG mode
    │   ├── list.php                        # Lista URL con meta
    │   ├── import.php                      # Import URL
    │   └── preview.php                     # Preview meta generati
    ├── internal-links/
    │   ├── index.php                       # Lista pool link
    │   ├── import.php                      # Import da sitemap
    │   └── edit.php                        # Edit singolo link
    ├── wordpress/
    │   └── index.php                       # Gestione siti WP (globale)
    └── jobs/
        └── index.php                       # Gestione job (globale)
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `aic_projects` | Progetti con campo `type` (manual/auto/meta-tag) |
| `aic_keywords` | Keyword salvate con lingua/location |
| `aic_serp_results` | Risultati SERP estratti |
| `aic_paa_questions` | People Also Ask |
| `aic_sources` | Fonti scrappate per articolo |
| `aic_articles` | Articoli generati |
| `aic_queue` | Coda keyword per AUTO mode |
| `aic_internal_links_pool` | Pool link interni per progetto |
| `aic_meta_urls` | URL per META-TAG mode |
| `aic_wp_sites` | Siti WordPress collegati (globale) |
| `aic_wp_publish_log` | Log pubblicazioni |

---

## ROUTES PRINCIPALI

```
# Dashboard e Progetti
GET  /ai-content                           → ProjectController@index (dashboard tabbed)
GET  /ai-content/projects/create           → ProjectController@create
POST /ai-content/projects/store            → ProjectController@store
GET  /ai-content/projects/{id}             → DashboardController@index (redirect per tipo)
GET  /ai-content/projects/{id}/settings    → ProjectController@settings

# Modalita MANUAL
GET  /ai-content/projects/{id}/keywords    → KeywordController@index
GET  /ai-content/projects/{id}/keywords/new → WizardController@step1
GET  /ai-content/projects/{id}/articles    → ArticleController@index

# Modalita AUTO
GET  /ai-content/projects/{id}/auto        → AutoController@dashboard
GET  /ai-content/projects/{id}/auto/queue  → AutoController@queue
GET  /ai-content/projects/{id}/auto/add    → AutoController@addKeywords
GET  /ai-content/projects/{id}/auto/settings → AutoController@settings

# Modalita META-TAG
GET  /ai-content/projects/{id}/meta-tags   → MetaTagController@dashboard
GET  /ai-content/projects/{id}/meta-tags/list → MetaTagController@list
GET  /ai-content/projects/{id}/meta-tags/import → MetaTagController@import

# Link Interni (condiviso MANUAL/AUTO)
GET  /ai-content/projects/{id}/internal-links → InternalLinksController@index

# Globali (non legati a progetto)
GET  /ai-content/wordpress                 → WordPressController@index
GET  /ai-content/jobs                      → JobController@index
```

---

## BUG APERTI

| Bug | Severity | Status |
|-----|----------|--------|
| Nessun bug critico | - | OK |

**Fix recenti (Gen 2026):**
- Fix "MySQL server has gone away" con Database::reconnect()
- Fix UI preview articolo (classe `prose` Tailwind)
- Card Keywords cliccabile in dashboard
- Refactoring completo UI con dashboard tabbed per tipo progetto

---

## GOLDEN RULES SPECIFICHE

1. **SerpAPI** - Usare SerpApiService, mai chiamate dirette
2. **Scraping** - Usare `ScraperService::scrape()` con Readability (Golden Rule #12)
3. **AI** - ArticleGeneratorService usa AiService('ai-content')
4. **ContentScraperService** - DEPRECATO, non usare per nuovo codice
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

### 1. Fix bug nel wizard (MANUAL mode)
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

### 5. Modificare navigazione progetto
```
Modifica la navigazione tabs del progetto ai-content.

FILE:
- views/partials/project-nav.php

NOTA: La nav e dinamica in base a $project['type']:
- manual: Dashboard, Keywords, Articoli, Link Interni, Impostazioni
- auto: Dashboard, Coda, Articoli, Link Interni, Impostazioni
- meta-tag: Dashboard, Meta Tags, Import, Impostazioni

REGOLE:
- Icone Heroicons SVG inline (array $icons nel file)
- Usare funzione isActiveTabAic() per stato attivo
- UI in italiano
```

### 6. Aggiungere nuovo tipo progetto
```
Aggiungi un nuovo tipo progetto al modulo ai-content.

FILE DA MODIFICARE:
- views/projects/index.php (aggiungere tab)
- views/projects/create.php (opzione nel form)
- views/partials/project-nav.php (definire tabs)
- ProjectController.php (gestire nuovo tipo)
- models/Project.php (validare tipo)

REGOLE:
- Campo type nella tabella aic_projects
- Stats card specifiche per il tipo
- Navigation tabs dedicate
```

### 7. Meta-Tag mode: aggiungere feature
```
Aggiungi [feature] alla modalita Meta-Tag.

REFERENCE:
- MetaTagController.php per logica
- views/meta-tags/*.php per UI

FLOW:
1. Import URL (CSV/sitemap/manuale)
2. Scrape contenuto pagine
3. Genera meta title/description con AI
4. Review e approvazione
5. Pubblica su WordPress
```
