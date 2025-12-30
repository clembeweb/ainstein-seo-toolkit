# SEO AUDIT MODULE - Specifiche Tecniche v2

## Overview
Modulo per audit SEO completo di siti web con analisi AI integrata e dati Google Search Console.
Ispirato a SEMrush Site Audit.

---

## ⚠️ STANDARD PIATTAFORMA - LEGGERE PRIMA DI SVILUPPARE

Prima di iniziare l'implementazione, **OBBLIGATORIO** leggere:

| Documento | Path | Motivo |
|-----------|------|--------|
| PLATFORM_STANDARDS.md | `docs/PLATFORM_STANDARDS.md` | Convenzioni globali, **LINGUA ITALIANA** |
| MODULE_NAVIGATION.md | `docs/MODULE_NAVIGATION.md` | **NAVIGAZIONE ACCORDION** in sidebar |
| IMPORT_STANDARDS.md | `docs/IMPORT_STANDARDS.md` | Pattern import URL (se necessario) |

### Regole chiave da rispettare:
1. **LINGUA**: Tutta l'interfaccia utente DEVE essere in **ITALIANO**
2. **SIDEBAR**: Usare **accordion** nella sidebar principale, NO sidebar separate nel modulo
3. **VIEWS**: Le views usano `<div class="space-y-6">` senza wrapper sidebar
4. **SERVIZI**: Usare i servizi condivisi della piattaforma (`/services/`)
5. **ICONE**: SVG Heroicons inline (NO Lucide, NO icon fonts)
6. **PREFISSO DB**: `sa_` per tutte le tabelle

---

## ARCHITETTURA

```
modules/seo-audit/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php
│   ├── CrawlController.php
│   ├── AuditController.php
│   ├── GscController.php          # Google Search Console
│   └── ReportController.php
├── models/
│   ├── Project.php
│   ├── Page.php
│   ├── Issue.php
│   ├── AiAnalysis.php
│   ├── GscConnection.php          # Token OAuth
│   └── GscData.php                # Dati importati
├── views/                         # ⚠️ NO SIDEBAR PROPRIA
│   ├── projects/
│   │   ├── index.php              # Lista progetti
│   │   ├── create.php             # Nuovo audit
│   │   └── settings.php           # Impostazioni progetto
│   ├── audit/
│   │   ├── dashboard.php          # Dashboard con health score
│   │   ├── category.php           # Dettaglio categoria
│   │   └── page-detail.php        # Dettaglio singola pagina
│   ├── gsc/
│   │   ├── connect.php            # Selezione proprietà
│   │   └── dashboard.php          # Dati GSC
│   ├── analysis/
│   │   ├── overview.php           # Analisi AI panoramica
│   │   └── category.php           # Analisi AI per categoria
│   └── reports/
│       └── export.php             # Export CSV/PDF
└── services/
    ├── CrawlerService.php         # USA SitemapService della piattaforma
    ├── AuditAnalyzer.php
    ├── IssueDetector.php
    └── GscService.php             # API Google
```

---

## NAVIGAZIONE SIDEBAR (ACCORDION)

Seguendo `docs/MODULE_NAVIGATION.md`, la navigazione del modulo è gestita nella sidebar principale.

### Struttura Accordion
```
SEO Audit ▼
  └── [Nome Progetto] ▼
        ├── Dashboard
        ├── Pagine Crawlate
        ├── Issues
        ├── ─── CATEGORIE ───
        ├── Meta Tags
        ├── Headings
        ├── Immagini
        ├── Link
        ├── Contenuti
        ├── Tecnico
        ├── Schema
        ├── Sicurezza
        ├── ─── GOOGLE GSC ───
        ├── Performance
        ├── Indicizzazione
        ├── Core Web Vitals
        ├── Mobile
        ├── ─── ANALISI AI ───
        ├── Panoramica AI
        ├── ─── REPORTS ───
        ├── Esporta
        └── Impostazioni
```

### Implementazione in nav-items.php
Aggiungere blocco per SEO Audit simile a Internal Links Analyzer:
- Espande quando si è dentro un progetto
- Evidenzia la voce attiva
- Categorie GSC disabilitate se non connesso

---

## SERVIZI PIATTAFORMA DA UTILIZZARE

| Servizio | Path | Uso nel modulo |
|----------|------|----------------|
| `SitemapService` | `services/SitemapService.php` | Parse sitemap.xml durante crawl |
| `AiService` | `services/AiService.php` | Generazione analisi AI |
| `ExportService` | `services/ExportService.php` | Export CSV/PDF |
| `ScraperService` | `services/ScraperService.php` | Fetch pagine durante crawl |

### Esempio integrazione SitemapService
```php
// In CrawlerService.php
use Services\SitemapService;

public function discoverUrls(string $baseUrl, string $mode): array {
    $urls = [];
    
    if ($mode === 'sitemap' || $mode === 'both') {
        $sitemapService = new SitemapService();
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';
        $sitemaps = $sitemapService->discoverFromRobotsTxt($robotsUrl);
        
        foreach ($sitemaps as $sitemapUrl) {
            $urls = array_merge($urls, $sitemapService->parse($sitemapUrl));
        }
    }
    
    if ($mode === 'spider' || $mode === 'both') {
        $urls = array_merge($urls, $this->spider($baseUrl));
    }
    
    return array_unique($urls);
}
```

---

## GOOGLE SEARCH CONSOLE - CONFIGURAZIONE

### Admin Settings (config globale)
Aggiungere in Admin > Impostazioni > tab "Google Search Console":

| Campo | Tipo | Label IT |
|-------|------|----------|
| gsc_client_id | text | Client ID Google |
| gsc_client_secret | password | Client Secret Google |
| gsc_redirect_uri | readonly | URI di Reindirizzamento |

```php
// Salvati in tabella settings
'gsc_client_id' => 'xxx.apps.googleusercontent.com',
'gsc_client_secret' => 'GOCSPX-xxx',
'gsc_redirect_uri' => 'https://tuodominio.com/seo-audit/gsc/callback',
```

### Scopes OAuth richiesti
```
https://www.googleapis.com/auth/webmasters.readonly
```

### Flusso OAuth per Progetto
1. Utente clicca "Connetti Google Search Console" nel progetto
2. Redirect a Google OAuth consent
3. Callback salva access_token + refresh_token per quel progetto
4. Utente seleziona proprietà GSC dalla lista
5. Token e proprietà salvati in `sa_gsc_connections`

---

## DATABASE

```sql
-- =============================================
-- TABELLE CORE AUDIT (prefisso sa_)
-- =============================================

-- Progetti audit
CREATE TABLE sa_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    crawl_mode ENUM('sitemap', 'spider', 'both') DEFAULT 'both',
    max_pages INT DEFAULT 500,
    status ENUM('pending', 'crawling', 'analyzing', 'completed', 'failed') DEFAULT 'pending',
    pages_found INT DEFAULT 0,
    pages_crawled INT DEFAULT 0,
    issues_count INT DEFAULT 0,
    health_score INT DEFAULT NULL,
    
    -- GSC link
    gsc_connected BOOLEAN DEFAULT FALSE,
    gsc_property VARCHAR(500) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pagine crawlate
CREATE TABLE sa_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    url VARCHAR(2000) NOT NULL,
    status_code INT,
    load_time_ms INT,
    content_length INT,
    
    -- Meta
    title VARCHAR(500),
    title_length INT,
    meta_description TEXT,
    meta_description_length INT,
    meta_robots VARCHAR(255),
    canonical_url VARCHAR(2000),
    
    -- OG Tags
    og_title VARCHAR(500),
    og_description TEXT,
    og_image VARCHAR(2000),
    
    -- Content
    h1_count INT DEFAULT 0,
    h1_texts JSON,
    h2_count INT DEFAULT 0,
    h3_count INT DEFAULT 0,
    h4_count INT DEFAULT 0,
    h5_count INT DEFAULT 0,
    h6_count INT DEFAULT 0,
    word_count INT DEFAULT 0,
    
    -- Images
    images_count INT DEFAULT 0,
    images_without_alt INT DEFAULT 0,
    images_data JSON,
    
    -- Links
    internal_links_count INT DEFAULT 0,
    external_links_count INT DEFAULT 0,
    broken_links_count INT DEFAULT 0,
    nofollow_links_count INT DEFAULT 0,
    links_data JSON,
    
    -- Technical
    has_schema BOOLEAN DEFAULT FALSE,
    schema_types JSON,
    hreflang_tags JSON,
    is_indexable BOOLEAN DEFAULT TRUE,
    indexability_reason VARCHAR(255),
    
    -- Raw data
    html_content LONGTEXT,
    crawled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_url (project_id, url(255))
);

-- Issues rilevate
CREATE TABLE sa_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    page_id INT,
    category VARCHAR(50) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    severity ENUM('critical', 'warning', 'notice', 'info') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    affected_element TEXT,
    recommendation TEXT,
    
    -- Source: 'crawler' o 'gsc'
    source ENUM('crawler', 'gsc') DEFAULT 'crawler',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sa_pages(id) ON DELETE CASCADE,
    INDEX idx_project_category (project_id, category),
    INDEX idx_project_severity (project_id, severity)
);

-- Analisi AI
CREATE TABLE sa_ai_analyses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    type ENUM('overview', 'category') NOT NULL,
    category VARCHAR(50) NULL,
    content LONGTEXT NOT NULL,
    credits_used INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_type (project_id, type)
);

-- Configurazione robots.txt e sitemap
CREATE TABLE sa_site_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    robots_txt TEXT,
    robots_allows JSON,
    robots_disallows JSON,
    sitemap_urls JSON,
    has_sitemap BOOLEAN DEFAULT FALSE,
    has_robots BOOLEAN DEFAULT FALSE,
    is_https BOOLEAN DEFAULT FALSE,
    ssl_valid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE
);

-- =============================================
-- TABELLE GOOGLE SEARCH CONSOLE
-- =============================================

-- Connessioni OAuth GSC (per progetto)
CREATE TABLE sa_gsc_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    
    -- OAuth tokens (CRIPTATI con openssl_encrypt)
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,
    
    -- Proprietà selezionata
    property_url VARCHAR(500) NOT NULL,
    property_type ENUM('URL_PREFIX', 'DOMAIN') NOT NULL,
    
    -- Stato
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Performance data (query/pagine)
CREATE TABLE sa_gsc_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    -- Dimensioni
    date DATE NOT NULL,
    query VARCHAR(500) NULL,
    page VARCHAR(2000) NULL,
    device ENUM('DESKTOP', 'MOBILE', 'TABLET') NULL,
    country VARCHAR(10) NULL,
    
    -- Metriche
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_date (project_id, date),
    INDEX idx_project_page (project_id, page(255)),
    INDEX idx_project_query (project_id, query(255))
);

-- Copertura indice
CREATE TABLE sa_gsc_coverage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    url VARCHAR(2000) NOT NULL,
    
    -- Stato indicizzazione
    coverage_state ENUM(
        'SUBMITTED_AND_INDEXED',
        'DUPLICATE_WITHOUT_CANONICAL',
        'DUPLICATE_GOOGLE_CHOSE_DIFFERENT_CANONICAL',
        'NOT_FOUND_404',
        'SOFT_404',
        'REDIRECT',
        'BLOCKED_BY_ROBOTS_TXT',
        'BLOCKED_BY_TAG',
        'CRAWLED_NOT_INDEXED',
        'DISCOVERED_NOT_INDEXED',
        'OTHER'
    ) NOT NULL,
    
    -- Dettagli
    verdict ENUM('PASS', 'NEUTRAL', 'FAIL') NOT NULL,
    robots_txt_state VARCHAR(50),
    indexing_state VARCHAR(50),
    page_fetch_state VARCHAR(50),
    google_canonical VARCHAR(2000),
    user_canonical VARCHAR(2000),
    
    last_crawl_time TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_state (project_id, coverage_state)
);

-- Core Web Vitals
CREATE TABLE sa_gsc_core_web_vitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    -- Tipo
    form_factor ENUM('PHONE', 'DESKTOP') NOT NULL,
    metric_type ENUM('LCP', 'INP', 'CLS') NOT NULL,
    
    -- Valori aggregati
    good_percent DECIMAL(5,2) DEFAULT 0,
    needs_improvement_percent DECIMAL(5,2) DEFAULT 0,
    poor_percent DECIMAL(5,2) DEFAULT 0,
    
    -- Percentile 75
    p75_value DECIMAL(10,3),
    p75_unit VARCHAR(20),
    
    -- Periodo
    date_range_start DATE,
    date_range_end DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_metric (project_id, form_factor, metric_type)
);

-- Mobile Usability Issues
CREATE TABLE sa_gsc_mobile_usability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    url VARCHAR(2000) NOT NULL,
    
    -- Issue type
    issue_type ENUM(
        'MOBILE_FRIENDLY',
        'NOT_MOBILE_FRIENDLY',
        'USES_INCOMPATIBLE_PLUGINS',
        'CONFIGURE_VIEWPORT',
        'FIXED_WIDTH_VIEWPORT',
        'TEXT_TOO_SMALL_TO_READ',
        'CONTENT_WIDER_THAN_SCREEN',
        'CLICKABLE_ELEMENTS_TOO_CLOSE_TOGETHER'
    ) NOT NULL,
    
    severity ENUM('warning', 'critical') NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_issue (project_id, issue_type)
);

-- Sync log
CREATE TABLE sa_gsc_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    sync_type ENUM('performance', 'coverage', 'cwv', 'mobile') NOT NULL,
    date_range_start DATE,
    date_range_end DATE,
    records_imported INT DEFAULT 0,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    error_message TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE
);
```

---

## CATEGORIE AUDIT

| Categoria | Slug | Checks | Source | Label IT |
|-----------|------|--------|--------|----------|
| Meta Tags | `meta` | Title, description, lunghezze, duplicati | Crawler | Meta Tags |
| Headings | `headings` | H1 mancante/multiplo, gerarchia, keyword | Crawler | Intestazioni |
| Immagini | `images` | Alt mancanti, dimensioni, lazy load | Crawler | Immagini |
| Link | `links` | Broken, redirect, nofollow, orphan pages | Crawler | Link |
| Content | `content` | Thin content, duplicati, word count | Crawler | Contenuti |
| Technical | `technical` | Canonical, robots, noindex, hreflang | Crawler | Tecnico |
| Schema | `schema` | Presenza, validità, tipi | Crawler | Schema Markup |
| Security | `security` | HTTPS, mixed content, SSL | Crawler | Sicurezza |
| Sitemap | `sitemap` | Presenza, validità, copertura | Crawler | Sitemap |
| Robots.txt | `robots` | Presenza, regole, blocchi critici | Crawler | Robots.txt |
| **Indicizzazione** | `indexing` | Copertura, errori, escluse | **GSC** | Indicizzazione |
| **Performance** | `performance` | Click, impressions, CTR, posizione | **GSC** | Performance |
| **Core Web Vitals** | `cwv` | LCP, INP, CLS | **GSC** | Core Web Vitals |
| **Mobile** | `mobile` | Usability issues | **GSC** | Mobile |

---

## ISSUE TYPES CON TRADUZIONI IT

### Meta Tags
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| missing_title | critical | Title vuoto | Titolo mancante |
| title_too_short | warning | < 30 caratteri | Titolo troppo corto |
| title_too_long | warning | > 60 caratteri | Titolo troppo lungo |
| missing_description | warning | Description vuota | Meta description mancante |
| description_too_short | notice | < 70 caratteri | Meta description troppo corta |
| description_too_long | warning | > 160 caratteri | Meta description troppo lunga |
| duplicate_title | warning | Title identico ad altra pagina | Titolo duplicato |
| duplicate_description | warning | Description identica | Meta description duplicata |
| missing_og_tags | notice | OG tags mancanti | Open Graph mancanti |

### Headings
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| missing_h1 | critical | Nessun H1 | H1 mancante |
| multiple_h1 | warning | Più di 1 H1 | H1 multipli |
| h1_too_long | notice | H1 > 70 caratteri | H1 troppo lungo |
| empty_heading | warning | Heading vuoto | Intestazione vuota |
| skipped_heading_level | notice | Es. H1 → H3 senza H2 | Livello heading saltato |

### Immagini
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| missing_alt | warning | Alt vuoto | Alt mancante |
| alt_too_long | notice | Alt > 125 caratteri | Alt troppo lungo |
| missing_dimensions | notice | Width/height non specificati | Dimensioni mancanti |
| large_image | notice | > 200KB | Immagine troppo pesante |

### Link
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| broken_internal_link | critical | 404 interno | Link interno rotto |
| broken_external_link | warning | 404 esterno | Link esterno rotto |
| redirect_chain | warning | 3+ redirect | Catena di redirect |
| orphan_page | warning | Pagina senza link in entrata | Pagina orfana |
| too_many_links | notice | > 100 link in pagina | Troppi link |
| nofollow_internal | notice | Link interno nofollow | Nofollow su link interno |

### Content
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| thin_content | warning | < 300 parole | Contenuto scarso |
| duplicate_content | warning | Contenuto simile > 80% | Contenuto duplicato |
| no_content | critical | Pagina vuota | Nessun contenuto |

### Technical
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| missing_canonical | warning | Canonical non definito | Canonical mancante |
| wrong_canonical | critical | Canonical punta a URL non valido | Canonical errato |
| noindex_in_sitemap | warning | Pagina noindex in sitemap | Noindex presente in sitemap |
| blocked_by_robots | notice | Bloccata da robots.txt | Bloccata da robots.txt |
| missing_hreflang | notice | Multilingua senza hreflang | Hreflang mancante |
| conflicting_hreflang | warning | Hreflang non reciproco | Hreflang in conflitto |

### Schema
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| missing_schema | notice | Nessun structured data | Schema markup mancante |
| invalid_schema | warning | Schema con errori sintassi | Schema markup non valido |

### Security
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| not_https | critical | Sito non HTTPS | Sito non sicuro (HTTP) |
| mixed_content | warning | Risorse HTTP su HTTPS | Contenuto misto |
| ssl_expiring | warning | SSL scade < 30 giorni | Certificato SSL in scadenza |
| ssl_invalid | critical | Certificato non valido | Certificato SSL non valido |

### GSC - Indicizzazione
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| not_indexed | warning | Pagina non indicizzata | Pagina non indicizzata |
| duplicate_no_canonical | warning | Duplicato senza canonical | Duplicato senza canonical |
| soft_404 | critical | Soft 404 rilevato | Soft 404 |
| crawl_error | critical | Errore crawl Google | Errore di scansione |
| redirect_error | warning | Redirect non valido | Errore di redirect |

### GSC - Performance
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| low_ctr | notice | CTR < 2% con impressions > 1000 | CTR basso |
| declining_clicks | warning | Click -20% vs periodo precedente | Click in calo |
| low_position | notice | Posizione media > 20 | Posizione bassa |
| zero_impressions | notice | Pagina con 0 impressions | Nessuna impressione |

### GSC - Core Web Vitals
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| poor_lcp | critical | LCP poor > 25% | LCP scarso |
| poor_inp | critical | INP poor > 25% | INP scarso |
| poor_cls | warning | CLS poor > 25% | CLS scarso |
| needs_improvement_lcp | warning | LCP needs improvement > 50% | LCP da migliorare |
| needs_improvement_inp | warning | INP needs improvement > 50% | INP da migliorare |
| needs_improvement_cls | notice | CLS needs improvement > 50% | CLS da migliorare |

### GSC - Mobile
| Issue | Severity | Condizione | Titolo IT |
|-------|----------|------------|-----------|
| not_mobile_friendly | critical | Pagina non mobile-friendly | Non ottimizzato per mobile |
| text_too_small | warning | Testo troppo piccolo | Testo troppo piccolo |
| viewport_not_set | warning | Viewport non configurato | Viewport non configurato |
| content_wider_than_screen | warning | Contenuto troppo largo | Contenuto troppo largo |
| clickable_too_close | notice | Elementi cliccabili troppo vicini | Elementi tap troppo vicini |

---

## SISTEMA CREDITI

| Operazione | Costo | Label IT |
|------------|-------|----------|
| crawl_per_page | 0.2 | Scansione pagina |
| gsc_sync | 5 | Sincronizzazione GSC |
| ai_overview | 15 | Analisi AI panoramica |
| ai_category | 3 | Analisi AI categoria |

### Esempio costi:

**Audit 100 pagine (completo):**
- Crawl: 20 crediti
- GSC sync: 5 crediti
- AI Panoramica: 15 crediti
- AI 10 categorie: 30 crediti
- **Totale: 70 crediti**

**Audit 500 pagine (completo):**
- Crawl: 100 crediti
- GSC sync: 5 crediti
- AI Panoramica: 15 crediti
- AI 10 categorie: 30 crediti
- **Totale: 150 crediti**

---

## FLUSSO UI (in ITALIANO)

### 1. Lista Progetti
- Card per ogni progetto con: nome, URL, health score, data, status, badge GSC
- Filtri: stato, data, con/senza GSC
- CTA: "Nuovo Audit"

### 2. Creazione Progetto
- Form:
  - Nome progetto
  - URL sito (con validazione)
  - Modalità crawl: Sitemap / Spider / Entrambi
  - Limite pagine (default 500)
- Stima crediti prima di avviare
- Checkbox: "Connetti Google Search Console dopo creazione"

### 3. Dashboard Progetto
- Header: nome progetto, URL, Health Score (gauge 0-100), data ultimo crawl
- Badge GSC (connesso/non connesso)
- **Cards categorie**: 
  - 10 categorie crawler + 4 categorie GSC
  - Ogni card mostra: nome, count issues per severity, mini progress bar
  - Categorie GSC disabilitate/grigie se non connesso
- Sezione "Problemi Principali" (top 10 issues critiche)
- Se GSC connesso: mini-chart performance, top 5 query
- CTA: "Genera Analisi AI" (mostra costo crediti)

### 4. Dettaglio Categoria
- Tabella issues con colonne:
  - URL (link a dettaglio pagina)
  - Problema
  - Gravità (badge colorato)
  - Elemento coinvolto
  - Fonte (Crawler/GSC)
- Filtri: gravità, tipo problema, fonte
- CTA: "Esporta CSV"

### 5. Dettaglio Pagina
- Info generali: URL, status code, tempo caricamento
- Tab "Dati Crawl": meta, headings, immagini, link, schema
- Tab "Google GSC" (se connesso):
  - Stato indicizzazione
  - Query posizionate
  - Click/Impressions/CTR/Posizione
  - Core Web Vitals
  - Mobile usability
- Lista issues unificate per questa pagina

### 6. Connessione GSC
- Pulsante "Connetti Google Search Console"
- OAuth redirect → Google consent → callback
- Selezione proprietà dalla lista
- Selezione periodo dati: 7 / 28 / 90 giorni / Personalizzato
- CTA: "Importa Dati" (mostra costo: 5 crediti)

### 7. Analisi AI
- Tab: Panoramica + 14 categorie
- Per ogni tab:
  - Se analisi già generata: mostra contenuto
  - Se non generata: CTA "Genera Analisi" con costo crediti
- Categorie GSC disabilitate se GSC non connesso
- CTA: "Esporta Report PDF"

### 8. Impostazioni Progetto
- Gestione GSC: disconnetti/riconnetti, cambia periodo
- Re-sync dati GSC (mostra costo crediti)
- Elimina progetto (con conferma)

---

## MODULE.JSON

```json
{
    "name": "SEO Audit",
    "slug": "seo-audit", 
    "version": "1.0.0",
    "description": "Audit SEO completo con Google Search Console e analisi AI",
    "icon": "clipboard-document-check",
    "menu_order": 20,
    "requires": {
        "php": ">=8.0",
        "services": ["scraper", "ai", "sitemap"]
    },
    "credits": {
        "crawl_per_page": {
            "cost": 0.2,
            "description": "Scansione singola pagina"
        },
        "gsc_sync": {
            "cost": 5,
            "description": "Sincronizzazione dati Google Search Console"
        },
        "ai_overview": {
            "cost": 15,
            "description": "Analisi AI panoramica completa"
        },
        "ai_category": {
            "cost": 3,
            "description": "Analisi AI singola categoria"
        }
    },
    "settings": {
        "gsc_client_id": {
            "type": "text",
            "label": "Google Client ID",
            "default": "",
            "admin_only": true
        },
        "gsc_client_secret": {
            "type": "password",
            "label": "Google Client Secret",
            "default": "",
            "admin_only": true
        }
    },
    "routes_prefix": "/seo-audit"
}
```

---

## ROUTES

```php
// Lista progetti
$router->get('/seo-audit', 'ProjectController@index');
$router->get('/seo-audit/create', 'ProjectController@create');
$router->post('/seo-audit/store', 'ProjectController@store');
$router->get('/seo-audit/project/{id}', 'ProjectController@show');
$router->get('/seo-audit/project/{id}/settings', 'ProjectController@settings');
$router->post('/seo-audit/project/{id}/settings', 'ProjectController@updateSettings');
$router->delete('/seo-audit/project/{id}', 'ProjectController@destroy');

// Crawl
$router->post('/seo-audit/project/{id}/crawl', 'CrawlController@start');
$router->get('/seo-audit/project/{id}/crawl/status', 'CrawlController@status');
$router->post('/seo-audit/project/{id}/crawl/stop', 'CrawlController@stop');

// GSC
$router->get('/seo-audit/project/{id}/gsc/connect', 'GscController@connect');
$router->get('/seo-audit/gsc/callback', 'GscController@callback');
$router->get('/seo-audit/project/{id}/gsc/properties', 'GscController@properties');
$router->post('/seo-audit/project/{id}/gsc/select-property', 'GscController@selectProperty');
$router->post('/seo-audit/project/{id}/gsc/sync', 'GscController@sync');
$router->delete('/seo-audit/project/{id}/gsc/disconnect', 'GscController@disconnect');

// Dashboard e categorie
$router->get('/seo-audit/project/{id}/dashboard', 'AuditController@dashboard');
$router->get('/seo-audit/project/{id}/pages', 'AuditController@pages');
$router->get('/seo-audit/project/{id}/page/{pageId}', 'AuditController@pageDetail');
$router->get('/seo-audit/project/{id}/issues', 'AuditController@issues');
$router->get('/seo-audit/project/{id}/category/{slug}', 'AuditController@category');

// AI Analysis
$router->post('/seo-audit/project/{id}/analyze/overview', 'AuditController@analyzeOverview');
$router->post('/seo-audit/project/{id}/analyze/{category}', 'AuditController@analyzeCategory');
$router->get('/seo-audit/project/{id}/analysis', 'AuditController@analysis');
$router->get('/seo-audit/project/{id}/analysis/{category}', 'AuditController@analysisCategory');

// Export
$router->get('/seo-audit/project/{id}/export/csv', 'ReportController@exportCsv');
$router->get('/seo-audit/project/{id}/export/pdf', 'ReportController@exportPdf');
```

---

## AI ANALYSIS PROMPTS

### Overview Prompt (Italiano)
```
Sei un SEO Specialist senior. Analizza i dati di questo audit SEO completo.

DATI AUDIT:
- Sito: {base_url}
- Pagine analizzate: {pages_count}
- Health Score: {health_score}/100
- Periodo GSC: {gsc_date_range}

ISSUES TROVATE:
{issues_summary_by_category}

STATISTICHE CRAWLER:
- Critiche: {critical_count}
- Warning: {warning_count}  
- Notice: {notice_count}

STATISTICHE GSC (se disponibili):
- Click totali: {total_clicks}
- Impressions totali: {total_impressions}
- CTR medio: {avg_ctr}%
- Posizione media: {avg_position}
- Pagine indicizzate: {indexed_pages}
- Pagine con errori: {error_pages}
- Core Web Vitals: LCP {lcp_status}, INP {inp_status}, CLS {cls_status}

Fornisci un'analisi strutturata con:
1. PANORAMICA GENERALE (stato del sito con focus su visibilità e salute tecnica)
2. TOP 5 PRIORITÀ (problemi più urgenti considerando impatto su ranking)
3. CORRELAZIONI (problemi tecnici che impattano performance organica)
4. PUNTI DI FORZA (cosa funziona bene)
5. ROADMAP CONSIGLIATA (ordine di intervento con stima impatto)

Scrivi in italiano. Usa un tono professionale ma accessibile. 
Sii specifico con dati e numeri. Evidenzia le correlazioni tra problemi tecnici e performance.
```

### Category Prompt (esempio Meta Tags - Italiano)
```
Sei un SEO Specialist senior. Analizza nel dettaglio la categoria "Meta Tags" di questo audit SEO.

STATISTICHE:
- Pagine totali: {total_pages}
- Pagine con problemi meta: {pages_with_issues}

ISSUES RILEVATE:
{issues_list_with_details}

TOP PAGINE PROBLEMATICHE:
{top_problem_pages}

Fornisci:
1. ANALISI DETTAGLIATA (pattern comuni nei problemi rilevati)
2. IMPATTO SEO (come questi problemi influenzano ranking e CTR)
3. SOLUZIONI SPECIFICHE (per ogni tipo di problema)
4. PRIORITÀ (ordina le fix dalla più urgente)
5. TEMPLATE (esempi di title/description ottimizzati per questo sito)

Scrivi in italiano. Sii tecnico e pratico con esempi concreti.
```

---

## NOTE IMPLEMENTAZIONE

1. **OAuth security**: Salva tokens criptati con `openssl_encrypt`
2. **Token refresh**: Automatico prima di ogni chiamata API se scaduto
3. **Rate limits GSC API**: Max 1200 query/min, implementare exponential backoff
4. **Crawl + GSC sync**: Possono girare in parallelo
5. **URL matching**: Normalizza URL per match crawler ↔ GSC (trailing slash, www, http/https)
6. **Dati GSC storici**: Mantieni storico per confronti temporali
7. **Revoca accesso**: Gestisci caso utente revoca permessi da Google
8. **URL Inspection API**: Limite 2000 richieste/giorno, prioritizza URL importanti
9. **Progress real-time**: Usa SSE o polling per progress bar crawl

---

## CHECKLIST PRE-RILASCIO

- [ ] Letto PLATFORM_STANDARDS.md
- [ ] Letto MODULE_NAVIGATION.md
- [ ] module.json completo
- [ ] Tabelle DB create con prefisso `sa_`
- [ ] Navigazione accordion in nav-items.php
- [ ] Views senza sidebar propria
- [ ] Tutta UI in italiano
- [ ] Icone SVG Heroicons (no Lucide)
- [ ] Integrazione AiService per analisi
- [ ] Integrazione SitemapService per crawl
- [ ] Integrazione ExportService per export
- [ ] Sistema crediti integrato
- [ ] OAuth GSC funzionante
- [ ] Progress real-time crawl
- [ ] Export CSV funzionante
- [ ] Test end-to-end
