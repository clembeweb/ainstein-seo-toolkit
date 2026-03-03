# Design: Merge Crawl Budget in SEO Audit

> Data: 2026-03-03 | Stato: Approvato | Fase: 1 (merge graduale)

## Contesto

Il modulo Crawl Budget Optimizer (`crawl-budget`, prefisso `cb_`) viene integrato gradualmente nel modulo SEO Audit (`seo-audit`, prefisso `sa_`). L'obiettivo è offrire un'analisi unificata: un solo crawl raccoglie sia dati on-page sia dati crawl budget. Il modulo CB resta funzionante ma diventa legacy con banner redirect.

Il report AI finale adotta lo stile del reference (`amevista/report/audit/audit_seo_onpage_produzione.html`): issue card espandibili, griglia 2 colonne, severity colorata, test replicabili, timeline prossimi passi. Viene generato in doppio formato: HTML standalone scaricabile + vista integrata nella piattaforma.

## Decisioni architetturali

| Decisione | Scelta | Motivazione |
|-----------|--------|-------------|
| Approccio merge | Estensione diretta crawler SA | Dati coerenti, un solo crawl, correlazioni on-page + CB |
| Database | Colonne nullable su `sa_pages` + nuove categorie in `sa_issues` | Zero nuove tabelle, schema pulito, retrocompatibile |
| CB legacy | Banner amber, modulo funzionante | Nessun breaking change per utenti esistenti |
| Report AI | HTML standalone + vista integrata | Condivisibile con clienti + fruibile nella piattaforma |
| GSC | Fase 2 (non in scope) | Riduce complessità fase 1 |

## 1. Estensione Database

### 1.1 Nuove colonne `sa_pages` (tutte nullable)

```sql
ALTER TABLE sa_pages
  ADD COLUMN redirect_chain JSON DEFAULT NULL COMMENT 'Array hop: [{"url":"...","status":301}, ...]',
  ADD COLUMN redirect_hops TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Numero hop redirect (0=nessuno)',
  ADD COLUMN redirect_target VARCHAR(2048) DEFAULT NULL COMMENT 'URL finale dopo redirect chain',
  ADD COLUMN is_redirect_loop TINYINT(1) DEFAULT 0 COMMENT 'Loop rilevato nella chain',
  ADD COLUMN depth TINYINT(3) UNSIGNED DEFAULT 0 COMMENT 'Profondità dal root (0=homepage)',
  ADD COLUMN discovered_from VARCHAR(2048) DEFAULT NULL COMMENT 'URL che ha scoperto questa pagina',
  ADD COLUMN has_parameters TINYINT(1) DEFAULT 0 COMMENT 'URL contiene query string',
  ADD COLUMN in_sitemap TINYINT(1) DEFAULT NULL COMMENT 'URL trovata nella sitemap XML',
  ADD COLUMN in_robots_allowed TINYINT(1) DEFAULT NULL COMMENT 'URL permessa da robots.txt',
  ADD COLUMN crawl_source ENUM('spider','sitemap','import') DEFAULT 'spider' COMMENT 'Come è stata scoperta';
```

### 1.2 Nuove colonne `sa_site_config`

```sql
ALTER TABLE sa_site_config
  ADD COLUMN sitemap_urls LONGTEXT DEFAULT NULL COMMENT 'JSON array URL dalle sitemap',
  ADD COLUMN crawl_delay INT DEFAULT NULL COMMENT 'Crawl-Delay da robots.txt',
  ADD COLUMN robots_rules JSON DEFAULT NULL COMMENT 'Regole parsed per User-Agent';
```

### 1.3 Nuova tabella `sa_unified_reports`

```sql
CREATE TABLE sa_unified_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  session_id INT DEFAULT NULL,
  report_type ENUM('unified','crawl_budget','on_page') DEFAULT 'unified',
  html_content LONGTEXT NOT NULL COMMENT 'Report HTML completo self-contained',
  summary TEXT DEFAULT NULL COMMENT 'Executive summary estratto',
  priority_actions JSON DEFAULT NULL COMMENT 'Top 5 azioni prioritarie',
  estimated_impact JSON DEFAULT NULL COMMENT 'Metriche impatto stimato',
  site_profile JSON DEFAULT NULL COMMENT 'Profilo sito rilevato (tipo, dimensione, settore)',
  health_score TINYINT(3) UNSIGNED DEFAULT NULL,
  budget_score TINYINT(3) UNSIGNED DEFAULT NULL,
  waste_percentage DECIMAL(5,2) DEFAULT NULL,
  credits_used INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
  INDEX idx_project_date (project_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 2. Estensione Crawler

### 2.1 Modifica `CrawlerService::crawlPage()`

Il flusso attuale:
```
fetch URL (auto-follow) → parse HTML → salva sa_pages
```

Il nuovo flusso:
```
[NUOVO] pre-fetch senza follow → traccia redirect chain
         ↓
fetch URL finale (auto-follow) → parse HTML → salva sa_pages (con dati CB)
         ↓
[NUOVO] post-parse: calcola depth, has_parameters, in_sitemap, in_robots
```

**Dettaglio pre-fetch redirect chain:**

```php
private function traceRedirectChain(string $url): array
{
    $chain = [];
    $visited = [];
    $maxHops = 10;
    $currentUrl = $url;
    $isLoop = false;

    for ($hop = 0; $hop < $maxHops; $hop++) {
        if (in_array($currentUrl, $visited)) {
            $isLoop = true;
            break;
        }
        $visited[] = $currentUrl;

        // Fetch SENZA follow
        $ch = curl_init($currentUrl);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,        // Solo header, veloce
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => $this->userAgent,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 300 && $httpCode < 400) {
            // Estrai Location header
            if (preg_match('/^Location:\s*(.+)$/mi', $response, $m)) {
                $nextUrl = $this->resolveUrl(trim($m[1]), $currentUrl);
                $chain[] = ['url' => $currentUrl, 'status' => $httpCode];
                $currentUrl = $nextUrl;
                continue;
            }
        }
        // Non è redirect: fine chain
        $chain[] = ['url' => $currentUrl, 'status' => $httpCode];
        break;
    }

    return [
        'chain' => $chain,
        'hops' => max(0, count($chain) - 1),
        'target' => $currentUrl,
        'is_loop' => $isLoop,
    ];
}
```

**Ottimizzazione**: il pre-fetch usa `CURLOPT_NOBODY` (solo header, nessun body). Se la prima richiesta ritorna 200, non ci sono hop aggiuntivi = overhead zero per pagine senza redirect.

### 2.2 Discovery da sitemap

Prima del crawl, aggiungo il parsing sitemap (logica presa da CB):
- Leggi `robots.txt` per trovare `Sitemap:` directives
- Fallback a `/sitemap.xml`
- Parsing ricorsivo (max 3 livelli index, cap 10K URL)
- Salva `sitemap_urls` in `sa_site_config`
- Ogni URL scoperta via sitemap: `crawl_source = 'sitemap'`, `in_sitemap = 1`

### 2.3 Robots.txt compliance check

Per ogni URL crawlata:
- Controlla le regole `robots_rules` in `sa_site_config`
- Match per User-Agent (Googlebot prioritario, poi `*`)
- Salva `in_robots_allowed` = 0/1

### 2.4 Depth tracking

- Homepage = depth 0
- Ogni pagina scoperta da un link: depth = parent.depth + 1
- Salva `discovered_from` = URL della pagina che l'ha scoperta

## 3. Nuovi Issue Detector per Crawl Budget

Nuovo service: `modules/seo-audit/services/BudgetIssueDetector.php`

Separato da `IssueDetector.php` per non toccare il codice esistente. Viene chiamato DOPO il detector on-page, sulle stesse pagine.

### 3.1 Issue per-pagina (durante crawl)

**Categoria `redirect`:**
| Tipo | Severity | Condizione |
|------|----------|------------|
| `redirect_loop` | critical | `is_redirect_loop = 1` |
| `redirect_to_4xx` | critical | Chain termina con 4xx |
| `redirect_to_5xx` | critical | Chain termina con 5xx |
| `redirect_chain` | warning | `redirect_hops >= 2` |
| `redirect_temporary` | warning | Chain contiene 302/307 |

**Categoria `waste`:**
| Tipo | Severity | Condizione |
|------|----------|------------|
| `soft_404` | critical | Status 200 + (title contiene "404"/"not found" OPPURE word_count < 50) |
| `empty_page` | critical | Status 200 + word_count = 0 |
| `thin_content` | warning | Status 200 + word_count 1-99 |
| `parameter_url_crawled` | warning | `has_parameters = 1` + canonical auto-referenziale |
| `deep_page` | notice | `depth > 4` |

**Categoria `indexability`:**
| Tipo | Severity | Condizione |
|------|----------|------------|
| `noindex_in_sitemap` | critical | meta robots contiene noindex + `in_sitemap = 1` |
| `mixed_signals` | critical | noindex + canonical punta a URL diversa |
| `blocked_but_linked` | warning | `in_robots_allowed = 0` + riceve link interni |
| `canonical_mismatch` | warning | canonical URL ≠ page URL (e non è noindex) |

### 3.2 Issue post-analisi (dopo tutte le pagine)

Eseguite una volta completato il crawl:

| Tipo | Severity | Condizione |
|------|----------|------------|
| `orphan_page` | notice | 0 link interni in ingresso + depth > 0 |
| `duplicate_title` | warning | Stesso title su pagine multiple |
| `canonical_chain` | critical | A→B→C (canonical che punta a pagina con canonical diversa) |
| `noindex_receives_links` | warning | noindex + ≥3 link interni in ingresso |

## 4. Score Crawl Budget

Nuovo metodo in `AuditAnalyzer` (o service dedicato). Lo score on-page SA resta invariato.

```
budget_score = 100
budget_score -= min(40, critical_count * 3)
budget_score -= min(30, warning_count * 1.5)
budget_score -= min(10, notice_count * 0.5)
budget_score -= min(20, waste_percentage * 0.4)

waste_percentage = (waste_pages / total_crawled) * 100
waste_pages = pagine con (status != 2xx OR soft_404 OR empty OR thin OR params_senza_canonical)
```

Label:
- ≥90: Eccellente (emerald)
- ≥70: Buono (blue)
- ≥50: Migliorabile (amber)
- <50: Critico (red)

## 5. UI: Tab Crawl Budget

### 5.1 Posizione nella navigazione

```
Dashboard | Pagine | Problemi | Struttura Link | Crawl Budget | Action Plan | Storico | Impostazioni
```

### 5.2 Layout tab principale

**Hero KPI (4 card in riga):**
1. **Crawl Budget Score** — Ring SVG 0-100 con colore + label
2. **% Spreco** — Numero grande rosso/verde con trend vs precedente
3. **Pagine Sprecate** — "234 / 1.250" con progress bar
4. **Redirect Chain** — Conteggio totale catene

**Sotto-tab con conteggi:**
```
Panoramica (45) | Redirect (12) | Pagine Spreco (23) | Indicizzabilità (10)
```

**Panoramica:**
- Score + severity breakdown (dot rosso/amber/blu + conteggi)
- Status distribution (barre 2xx/3xx/4xx/5xx)
- Top 10 issue ordinate per severity
- Bottone "Genera Report AI"

**Redirect / Waste / Indicizzabilità:**
- Filtro severity (Tutti/Critici/Warning/Notice)
- Tabella issue: URL, Tipo, Severità badge, Dettagli (da JSON)
- Paginazione shared component

### 5.3 Dark mode

Tutti i componenti usano `dark:` prefix Tailwind, coerente con il resto della piattaforma.

## 6. Report AI Unificato

### 6.1 Service: `UnifiedReportService`

File: `modules/seo-audit/services/UnifiedReportService.php`

**Responsabilità:**
1. Raccolta dati aggregati (on-page + crawl budget)
2. Rilevamento profilo sito
3. Costruzione prompt contestualizzato
4. Chiamata AiService
5. Generazione HTML standalone
6. Salvataggio in `sa_unified_reports`

### 6.2 Rilevamento profilo sito

```php
private function detectSiteProfile(int $projectId): array
{
    // Tipo sito (da URL pattern + content)
    // e-commerce: /product, /cart, /checkout, /shop, schema Product
    // blog: /blog, /article, /post, /news, /category
    // saas: /pricing, /features, /docs, /api
    // corporate: /about, /team, /contact, /services
    // portfolio: poche pagine, /works, /projects

    // Dimensione
    // micro: <50 pagine
    // piccolo: 50-500
    // medio: 500-5000
    // grande: >5000

    // Settore (da title/description delle top 10 pagine)

    return [
        'type' => 'e-commerce',
        'size' => 'medio',
        'size_label' => '1.250 pagine',
        'sector' => 'Moda/Accessori',
        'languages' => ['it', 'en'],
        'avg_depth' => 2.4,
        'internal_links_ratio' => 85,
    ];
}
```

### 6.3 Prompt AI

```
RUOLO: Sei un consulente SEO senior specializzato in audit tecnici.
Devi produrre un report dettagliato e contestualizzato per il sito analizzato.

PROFILO SITO:
- Tipo: {type} | Dimensione: {size} ({pages} pagine) | Settore: {sector}
- Score On-Page: {health_score}/100 | Score Crawl Budget: {budget_score}/100
- Spreco Crawl Budget: {waste}%

REGOLE:
1. Contestualizza OGNI issue sulla tipologia e dimensione del sito
   (es. "Per un e-commerce con 1.250 prodotti, il 23% di waste significa ~290 pagine
   che Google crawla inutilmente, rallentando l'indicizzazione di nuovi prodotti")
2. Stima l'impatto SEO reale, non la severity tecnica
3. Proponi fix con code snippet concreti (HTML, htaccess, robots.txt)
4. Cita URL specifici dal crawl come esempi
5. Quando possibile, includi il comando curl per verificare il problema

OUTPUT: JSON con questa struttura esatta:
{
  "issues": [
    {
      "id": 1,
      "severity": "critical|important|minor",
      "title": "Titolo conciso del problema",
      "impact": "ALTO|MEDIO|BASSO",
      "description": "Paragrafi HTML con <p>, <table>, <pre><code>",
      "fix": "HTML del box fix con <strong>Fix:</strong> e dettagli",
      "test_command": "curl -s ... | grep ..."  // opzionale
    }
  ],
  "positives": ["Cosa funziona bene 1", "Cosa funziona bene 2", ...],
  "timeline": {
    "week1": "Fix urgenti da fare subito",
    "week2_3": "Fix importanti a medio termine",
    "week4_plus": "Ottimizzazioni e miglioramenti"
  },
  "executive_summary": "3-4 righe di sintesi"
}

DATI CRAWL:
{data_context}
```

### 6.4 Template HTML standalone

File template: `modules/seo-audit/views/report/unified-template.php`

Genera un file HTML self-contained con:
- CSS inline (stile amevista: Inter font, CSS variables per severity, griglia 2 colonne)
- JS inline (espandi/comprimi, filtri severity, nascondi issue con localStorage, counters)
- Struttura: Header → Toolbar → Sezioni severity → Positivi → Test replicabili → Timeline → Footer
- `@media print` per stampa pulita
- Responsive: 2 colonne → 1 colonna su mobile

Il PHP renderizza il template con i dati JSON dall'AI, producendo HTML statico.

### 6.5 Vista integrata

File: `modules/seo-audit/views/report/unified-view.php`

Stessa struttura del template standalone ma:
- Dentro il layout Ainstein (`layout.php` con sidebar)
- Tailwind CSS + Alpine.js (invece di CSS/JS inline vanilla)
- Dark mode completo
- Link "Scarica HTML" per la versione standalone
- Breadcrumb di navigazione al progetto

### 6.6 Costo crediti

- Report unificato: **15 crediti**
- Configurabile in `module.json` come `cost_unified_report`

## 7. Modulo Crawl Budget Legacy

### 7.1 Banner

In cima alla dashboard CB, prima di qualsiasi contenuto:

```html
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 mb-6">
  <div class="flex items-center gap-3">
    <svg><!-- info icon --></svg>
    <div>
      <p class="font-medium text-amber-800 dark:text-amber-200">
        L'analisi Crawl Budget è ora integrata in SEO Audit
      </p>
      <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
        Puoi continuare a usare questo modulo, oppure passare a SEO Audit
        per un'analisi unificata con report avanzato.
      </p>
    </div>
    <a href="/seo-audit/project/{sa_project_id}/crawl-budget" class="btn-primary">
      Vai a SEO Audit →
    </a>
  </div>
</div>
```

Il link punta al progetto SA corrispondente (trovato via `global_project_id`).

### 7.2 Nessuna rimozione

- Tutte le funzionalità CB restano operative
- Nessuna tabella CB rimossa
- Nessuna route CB rimossa
- Il cron CB continua a funzionare

## 8. Cosa NON cambia

Per garantire zero regressioni:

| Componente | Stato |
|-----------|-------|
| View SA esistenti (dashboard, issues, pages, page-detail, history, category) | Invariate |
| IssueDetector check on-page (60+ tipi) | Invariato |
| Health score SA | Invariato (score separato da budget_score) |
| Action Plan SA | Invariato |
| Modulo CB completo | Funzionante al 100% |
| Tabelle sa_* colonne esistenti | Nessuna modifica |
| Routes SA esistenti | Nessuna modifica |
| Routes CB esistenti | Nessuna modifica |
| Cron job entrambi i moduli | Invariati |

## 9. File da creare/modificare

### Nuovi file:
- `modules/seo-audit/services/BudgetIssueDetector.php` — Issue detector crawl budget
- `modules/seo-audit/services/BudgetScoreCalculator.php` — Score crawl budget
- `modules/seo-audit/services/UnifiedReportService.php` — Generazione report AI
- `modules/seo-audit/services/SiteProfileDetector.php` — Rilevamento tipo/dimensione sito
- `modules/seo-audit/services/RobotsTxtParser.php` — Parser robots.txt
- `modules/seo-audit/services/SitemapParser.php` — Parser sitemap (o riuso SitemapService)
- `modules/seo-audit/controllers/BudgetResultsController.php` — Controller tab crawl budget
- `modules/seo-audit/controllers/UnifiedReportController.php` — Controller report unificato
- `modules/seo-audit/models/UnifiedReport.php` — Model report
- `modules/seo-audit/views/budget/overview.php` — Tab panoramica CB
- `modules/seo-audit/views/budget/redirects.php` — Tab redirect
- `modules/seo-audit/views/budget/waste.php` — Tab waste
- `modules/seo-audit/views/budget/indexability.php` — Tab indicizzabilità
- `modules/seo-audit/views/report/unified-view.php` — Vista report integrata
- `modules/seo-audit/views/report/unified-template.php` — Template HTML standalone
- `modules/seo-audit/migrations/add_budget_columns.sql` — Migration SQL

### File da modificare:
- `modules/seo-audit/services/CrawlerService.php` — Aggiunta redirect tracing + metadata CB
- `modules/seo-audit/services/AuditAnalyzer.php` — Aggiunta calcolo budget_score
- `modules/seo-audit/views/audit/dashboard.php` — Aggiunta tab "Crawl Budget" nella nav
- `modules/seo-audit/models/Page.php` — Nuove colonne nel model
- `modules/seo-audit/models/CrawlSession.php` — budget_score nel complete()
- `public/index.php` — Nuove routes per tab CB e report
- `modules/crawl-budget/views/dashboard.php` — Banner legacy

## 10. Fase 2 (futura, non in scope)

- Integrazione GSC: dati copertura (indicizzate/escluse/errori) correlati ai problemi
- Nascondi modulo CB per nuovi utenti
- Migrazione dati CB → SA per progetti esistenti
- Confronto report nel tempo (trend)
