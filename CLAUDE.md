# AINSTEIN - Istruzioni Claude Code

> Questo file viene caricato automaticamente ad ogni sessione.
> Ultimo aggiornamento: 2026-02-16 (Golden Rule #20: tabelle standard, uniformazione CSS tutti i moduli)

---

## CONTESTO PROGETTO

| Aspetto | Dettaglio |
|---------|-----------|
| **Nome** | Ainstein SEO Toolkit |
| **Directory locale** | `C:\laragon\www\seo-toolkit` |
| **Produzione** | https://ainstein.it |
| **Stack** | PHP 8+, MySQL, Tailwind CSS, Alpine.js, HTMX |
| **AI Provider** | Claude API (Anthropic) + OpenAI fallback |
| **Lingua UI** | Italiano (sempre) |
| **Database** | MySQL `seo_toolkit` (locale) / `dbj0xoiwysdlk1` (prod) |

---

## GOLDEN RULES (INVIOLABILI)

```
1. AiService SEMPRE centralizzato    → new AiService('modulo-slug')
2. Icone SOLO Heroicons SVG          → MAI Lucide, MAI FontAwesome
3. Lingua UI ITALIANO                → Tutti i testi visibili
4. Prefisso DB per modulo            → aic_, sa_, st_, il_, ga_, cc_, kr_
5. API Keys in Database              → MAI in .env, MAI hardcoded
6. Routes pattern standard           → /modulo/projects/{id}/sezione
7. Prepared statements SEMPRE        → MAI concatenare SQL
8. CSRF token su form POST           → campo `_csrf_token` (con underscore!)
9. ai-content è il reference         → Copia pattern da lì
10. Database::reconnect()            → Prima di salvare dopo AI call
11. OAuth GSC pattern seo-tracking   → GoogleOAuthService centralizzato
12. Scraping SEMPRE con Readability  → ScraperService::scrape() per tutto
13. Background jobs per operazioni lunghe → SSE + job queue (vedi sezione dedicata)
14. ApiLoggerService per API     → Tutte le chiamate API esterne loggano
15. ignore_user_abort(true)      → SEMPRE in SSE e AJAX lunghi (proxy chiude connessione)
16. ModuleLoader::getSetting()   → NON getModuleSetting() (non esiste)
17. ob_start() in AJAX lunghi    → Output buffering OBBLIGATORIO (vedi sezione dedicata)
18. Aggiornare docs dopo sviluppo → Documentazione utente + Data Model (vedi sezione dedicata)
19. Link project-scoped nelle view → MAI usare percorsi legacy dentro contesto progetto
20. Tabelle standard CSS uniforme  → rounded-xl, px-4 py-3, bg-slate-700/50, componenti shared
21. return View::render() SEMPRE   → Controller E route handler devono ritornare il risultato
```

---

## GLOBAL PROJECTS (Hub Centralizzato)

Il sistema Global Projects (`/projects`) è l'hub centralizzato per la gestione progetti. Un progetto globale raggruppa i moduli di un singolo cliente/sito.

### Architettura

| Livello | Tabella | Route | Esempio |
|---------|---------|-------|---------|
| Globale | `projects` | `/projects/{id}` | Dashboard progetto, attivazione moduli |
| Modulo | `{prefix}_projects` | `/modulo/projects/{id}/sezione` | Funzionalità specifiche del modulo |
| FK | `global_project_id` | — | Collega modulo ↔ progetto globale (nullable) |

### Flusso creazione progetto

1. Utente va a `/projects/create` → crea progetto globale (nome, dominio, colore)
2. Dashboard progetto (`/projects/{id}`) → mostra moduli disponibili
3. "Attiva modulo" → per moduli CON tipi (ai-content, keyword-research, ads-analyzer) apre modal selezione tipo
4. POST a `/projects/{id}/activate-module` → crea record in `{prefix}_projects` con `global_project_id`

### File chiave

| File | Ruolo |
|------|-------|
| `core/Models/GlobalProject.php` | Model con `MODULE_CONFIG`, `MODULE_TYPES`, `activateModule()` |
| `controllers/GlobalProjectController.php` | CRUD progetti + attivazione moduli |
| `shared/views/projects/dashboard.php` | Dashboard con moduli attivi/disponibili + modal tipo |
| `shared/views/projects/create.php` | Form creazione progetto |
| `shared/views/components/orphaned-project-notice.php` | Banner per progetti modulo senza global_project_id |

### Regole

- **"Nuovo Progetto" nei moduli** → DEVE puntare a `url('/projects/create')`, MAI a URL modulo-specifiche
- **Route GET create dei moduli** → DEVE fare redirect a `/projects/create`
- **Controller store() dei moduli** → Redirect errori validazione verso `url('/projects/create')`
- **Moduli con tipi**: ai-content (manual/auto/meta-tag), keyword-research (research/architecture/editorial), ads-analyzer (campaign/campaign-creator)
- **Progetti orfani** (senza `global_project_id`) → Funzionano normalmente + banner informativo amber
- **KPI dashboard**: ogni modulo implementa `getProjectKpi($moduleProjectId)` nel proprio model

---

## LANDING PAGE PATTERN ("Scopri cosa puoi fare")

Ogni modulo DEVE avere la sezione "Scopri cosa puoi fare" nella pagina landing/index, con 7 sezioni in ordine fisso.

### Reference

**File:** `modules/keyword-research/views/dashboard.php` (da linea 150)

### Struttura obbligatoria (7 sezioni)

| # | Sezione | Descrizione |
|---|---------|-------------|
| 1 | **Separator divider** | Linea gradient con label "Scopri cosa puoi fare" centrata |
| 2 | **Hero educativo** | Sfondo gradient, grid 2 colonne (testo + mockup visuale), badge modulo, CTA |
| 3 | **Come funziona** | 3 step card con badge numerico (`-top-4`), icona e descrizione |
| 4 | **Feature blocks (3x)** | Alternanza: testo SX/visual DX (white bg) → visual SX/testo DX (slate bg) → testo SX/visual DX (white bg) |
| 5 | **Cosa puoi fare** | Grid 6 card con icona, titolo e descrizione |
| 6 | **FAQ accordion** | Alpine.js `x-data="{ openFaq: null }"`, 5-6 domande |
| 7 | **CTA finale** | Banner gradient con cerchi decorativi, titolo e pulsante |

### Colori per modulo

| Modulo | Colore primario | Gradient CTA |
|--------|----------------|--------------|
| ai-content | amber | amber-500 → orange-500 |
| seo-audit | emerald | emerald-500 → teal-500 |
| seo-tracking | blue | blue-500 → indigo-500 |
| keyword-research | purple | purple-500 → indigo-500 |
| ads-analyzer | rose | rose-500 → orange-500 |
| internal-links | cyan | cyan-500 → blue-500 |

### Regole

- **Lingua**: italiano (come tutta la UI)
- **Icone**: solo Heroicons SVG inline
- **Visual mockup**: HTML/CSS-only con dati finti (NON screenshot). Mockup che illustrano la feature con dati realistici
- **FAQ**: Alpine.js accordion con transizioni (`x-transition`, `x-cloak`)
- **CTA links**: SEMPRE `url('/projects/create')`
- **Dark mode**: ogni elemento DEVE avere varianti `dark:`
- **Alternanza feature blocks**: bianco (Feature 1, 3) → slate-50 (Feature 2) — NON invertire l'ordine
- **Inserire DOPO** il contenuto operativo (project grid, empty state, info box) — la sezione educativa va in fondo

---

## STATO MODULI

| Modulo | Slug | Prefisso DB | Stato | Note |
|--------|------|-------------|-------|------|
| AI Content Generator | `ai-content` | `aic_` | 100% | Reference pattern, scheduling per-keyword, cover image DALL-E 3, brief AI strategico, user edits nel prompt, SERP provider SerpAPI/Serper.dev con fallback |
| SEO Audit | `seo-audit` | `sa_` | 100% | Action Plan AI completato |
| Google Ads Analyzer | `ads-analyzer` | `ga_` | 100% | Completo |
| Internal Links | `internal-links` | `il_` | 85% | Manca AI Suggester |
| SEO Tracking | `seo-tracking` | `st_` | 95% | Rank Check con DataForSEO/SerpAPI/Serper, API Logs integrato, provider SERP/Volume configurabili da admin |
| AI Keyword Research | `keyword-research` | `kr_` | 100% | Research Guidata, Architettura Sito, Piano Editoriale, Quick Check |
| Content Creator | `content-creator` | `cc_` | 90% | Generazione contenuti HTML pagina, 4 CMS connectors (WP plugin, Shopify, PrestaShop, Magento), import da KR |

---

## DOCS DI RIFERIMENTO

**Prima di modificare qualsiasi cosa, leggi i file pertinenti in `/mnt/project/`:**

| Quando | Leggi |
|--------|-------|
| Sempre | `GOLDEN-RULES.md` |
| Nuovo modulo | `PLATFORM_OVERVIEW.md`, `MODULE_NAVIGATION.md` |
| Import URL | `IMPORT_STANDARDS.md` |
| Integrazione AI | `AI_SERVICE_STANDARDS.md` |
| Sistema crediti | `CREDITS-SYSTEM.md` |
| Modulo specifico | `specs/[modulo].md` o `AGENT-[MODULO].md` |
| API esterne | `API-LOGS.md` |
| Deploy | `DEPLOY.md`, `DEPLOY-VERIFY-AINSTEIN.md` |

---

## STANDARD module.json (Admin Settings)

Ogni modulo DEVE avere `settings_groups` nel suo `module.json` per organizzare la pagina admin `/admin/modules/{id}/settings`.

**Ordine gruppi standard:**

| Order | Gruppo | Descrizione | Note |
|-------|--------|-------------|------|
| 0 | `ai_config` | Provider/modello AI | Gestito da componente custom |
| 0.5 | `provider_config` | Provider esterni (SERP, volumi) | Solo se necessario |
| 1 | `general` | Impostazioni specifiche modulo | - |
| 2+ | Feature groups | Cron, sync, etc. | Specifici per modulo |
| 99 | `costs` | Costi operazioni | **Sempre collapsed: true** |

**Reference**: `modules/seo-tracking/module.json` (implementazione completa)

**Regole:**
- Tutti i settings DEVONO avere `"group"` assegnato (no settings "ungrouped")
- Costi SEMPRE nel gruppo `costs` con `collapsed: true`
- Moduli AI DEVONO avere `ai_provider`, `ai_model`, `ai_fallback_enabled` in gruppo `ai_config`
- Icone gruppi: `sparkles`, `globe-alt`, `cog`, `chart-bar`, `refresh`, `currency-euro`

---

## STRUTTURA PROGETTO

```
seo-toolkit/
├── admin/                   # Area amministrazione
│   ├── controllers/
│   │   ├── AdminController.php    # Dashboard, utenti, piani, moduli, settings
│   │   ├── FinanceController.php  # Dashboard finanziaria (4 tab)
│   │   ├── AiLogsController.php   # Log chiamate AI
│   │   └── ApiLogsController.php  # Log chiamate API
│   ├── views/
│   └── routes.php
├── core/                    # Framework (Router, Database, Auth, Credits)
├── services/                # Servizi CONDIVISI
│   ├── AiService.php        # Claude API - USARE SEMPRE
│   ├── ScraperService.php   # Scraping con Readability - USARE SEMPRE
│   ├── GoogleOAuthService.php
│   ├── SitemapService.php
│   ├── CsvImportService.php
│   ├── DataForSeoService.php # Rank check API
│   ├── ApiLoggerService.php # Logging chiamate API - USARE SEMPRE
│   └── RapidApiKeywordService.php # Keyword volumes (seo-tracking)
├── modules/
│   ├── ai-content/          # REFERENCE per nuovi moduli
│   ├── seo-audit/
│   ├── ads-analyzer/
│   ├── internal-links/
│   ├── seo-tracking/
│   └── keyword-research/    # AI Keyword Research
├── core/
│   └── Pagination.php       # Paginazione standard (make, sqlLimit)
├── shared/views/
│   ├── layout.php
│   └── components/
│       ├── nav-items.php            # Sidebar con accordion
│       ├── import-tabs.php          # Componente import URL
│       ├── table-pagination.php     # Paginazione tabelle (shared)
│       ├── table-empty-state.php    # Empty state tabelle (shared)
│       ├── table-helpers.php        # Sort headers, badges, bulk init
│       └── table-bulk-bar.php       # Barra azioni bulk (shared)
├── docs/
│   └── data-model.html      # Schema DB standalone (Mermaid.js ER diagrams)
├── shared/views/docs/        # Documentazione utente pubblica (/docs)
│   ├── layout.php            # Layout standalone docs (no login)
│   ├── index.php             # Landing page Centro Assistenza
│   ├── getting-started.php   # Primi Passi
│   ├── ai-content.php        # Guida AI Content Generator
│   ├── seo-audit.php         # Guida SEO Audit
│   ├── seo-tracking.php      # Guida SEO Tracking
│   ├── keyword-research.php  # Guida Keyword Research
│   ├── internal-links.php    # Guida Internal Links
│   ├── ads-analyzer.php      # Guida Google Ads Analyzer
│   ├── credits.php           # Sistema Crediti
│   └── faq.php               # FAQ
└── config/
```

---

## DOCUMENTAZIONE UTENTE E DATA MODEL (Golden Rule #18)

**OBBLIGO: Dopo ogni sviluppo significativo, aggiornare la documentazione.**

### Quando aggiornare

| Evento | Docs utente (`/docs`) | Data Model (`docs/data-model.html`) |
|--------|----------------------|-------------------------------------|
| Nuova feature in modulo esistente | Aggiornare la pagina del modulo | - |
| Nuovo modulo | Creare nuova pagina + aggiungere a `index.php` routes | Aggiungere sezione con ER + tabelle |
| Nuova tabella DB / colonne significative | - | Aggiornare sezione del modulo |
| Cambio costi crediti | Aggiornare tabella costi nella pagina modulo + `credits.php` | - |
| Nuova integrazione (API, OAuth) | Aggiornare Quick Start del modulo | - |
| Rimozione feature/modulo | Rimuovere/aggiornare pagina | Rimuovere/aggiornare sezione |

### Cosa aggiornare - Docs utente

**File**: `shared/views/docs/{modulo-slug}.php`

Ogni pagina modulo ha questa struttura (da mantenere):
1. **Cos'e** - Descrizione 2-3 frasi
2. **Quick Start** - Passi numerati (creare progetto → configurare → eseguire → risultati)
3. **Funzionalita principali** - Lista feature con icone
4. **Costi in crediti** - Tabella operazione/costi
5. **Suggerimenti** - 2-3 tips pratici

**Regole:**
- Lingua: italiano (come tutta la UI)
- Icone: solo Heroicons SVG
- Stile: Tailwind con dark mode (`dark:` variants)
- Layout: contenuto HTML puro (no `<html>`/`<head>`, renderizzato dentro `layout.php`)
- Nuova pagina modulo → aggiungere route in `public/index.php` array `$validPages`

### Cosa aggiornare - Data Model

**File**: `docs/data-model.html` (standalone, aprire direttamente nel browser)

Per ogni modulo la sezione contiene:
1. **Diagramma ER** (Mermaid.js `erDiagram`) con relazioni tra tabelle
2. **Details collassabili** per ogni tabella con colonne, tipi, PK/FK/UK

**Regole:**
- Aggiungere nuove tabelle nel diagramma ER della sezione modulo
- Aggiungere `<details>` con schema colonne per tabelle nuove
- Se nuova colonna significativa in tabella esistente → aggiornare il `<details>` corrispondente
- Mantenere dual-mode light/dark (`bg-white dark:bg-slate-800`, etc.)
- Bordi colorati per modulo: amber(aic), emerald(sa), blue(st), purple(kr), cyan(il), rose(ga)
- Aggiungere link nella sidebar nav se nuovo modulo

### Checklist rapida post-sviluppo

```
Dopo feature significativa:
[ ] Pagina docs modulo aggiornata (nuove feature, costi, Quick Start)
[ ] Data Model aggiornato (nuove tabelle/colonne nel diagramma ER)
[ ] Route /docs aggiornata se nuova pagina (public/index.php $validPages)
[ ] Sidebar docs aggiornata se nuova pagina (layout.php $navItems)
[ ] Testare /docs/{slug} in locale prima di deploy
```

---

## COMANDI FREQUENTI

### SSH Produzione
```bash
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html
```

### Git Deploy
```bash
# Locale
git add .
git commit -m "tipo: descrizione"
git push origin main

# Produzione (da SSH)
git pull origin main
```

### Verifica Sintassi PHP
```bash
php -l path/to/file.php
```

### Test Connessione DB (locale)
```bash
mysql -u root seo_toolkit -e "SHOW TABLES;"
```

### Database Produzione
```bash
# Credenziali
# Host: localhost
# DB: dbj0xoiwysdlk1
# User: u6iaaermphtha
# Password: exkwryfz7ieh

# Eseguire migration in produzione
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 < modules/modulo/database/migrations/NNN_nome.sql

# Verificare tabelle
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "SHOW TABLES LIKE 'prefisso_%';"

# Query rapida
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "SELECT * FROM tabella LIMIT 5;"
```

### Cron Jobs SiteGround
```
# Formato OBBLIGATORIO (path assoluti, NO ~, NO >>):
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/<path-script>.php

# Esempi cron attivi:
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/cron/cleanup-data.php                              # Once Daily (0 2 * * *)
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/ai-content/cron/dispatcher.php              # Every Minute (* * * * *)
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/ads-analyzer/cron/auto-evaluate.php         # Every 5 Min (*/5 * * * *)
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/seo-tracking/cron/rank-dispatcher.php       # Every 5 Min (*/5 * * * *)
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/seo-tracking/cron/gsc-sync-dispatcher.php   # Once Hourly (0 * * * *)
/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/modules/seo-tracking/cron/ai-report-dispatcher.php  # Once Hourly (0 * * * *)
```

**Regole:**
- PHP binary: `/usr/bin/php` (NON `/usr/local/bin/php`)
- Path base: `/home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/` (NON `~` o `$HOME`)
- NON aggiungere redirect output (`>>`) — SiteGround lo gestisce internamente
- I log vengono scritti dallo script stesso (es. `storage/logs/data-cleanup.log`)

---

## TEST LOCALE

### Credenziali Test
```
URL Base: http://localhost/seo-toolkit
Email: admin@seo-toolkit.local
Password: admin123
```

### Test con cURL (da terminale)
```bash
# 1. Login e salva cookie
curl -c cookies.txt -b cookies.txt "http://localhost/seo-toolkit/login" -s | grep -oP 'name="csrf_token"\s+value="\K[^"]+'
# Usa il token ottenuto:
curl -c cookies.txt -b cookies.txt -X POST "http://localhost/seo-toolkit/login" -d "email=admin@seo-toolkit.local&password=admin123&csrf_token=TOKEN_QUI"

# 2. Test pagine autenticate
curl -b cookies.txt "http://localhost/seo-toolkit/ai-content" -s -o /dev/null -w "%{http_code}"
```

### Pagine da Testare (Meta Tags)
- Dashboard: `/ai-content/projects/{id}/meta-tags`
- Import: `/ai-content/projects/{id}/meta-tags/import`
- Lista: `/ai-content/projects/{id}/meta-tags/list`
- Preview: `/ai-content/projects/{id}/meta-tags/{tagId}/preview`

---

## PATTERN DI SVILUPPO

### Nuovo Controller
```php
<?php
namespace Modules\NomeModulo\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;

class NomeController
{
    // IMPORTANTE: return type DEVE essere string (non void!)
    public function index(): string
    {
        Middleware::auth();
        $user = Auth::user();

        // logica...

        // IMPORTANTE: SEMPRE return View::render() (non solo View::render()!)
        return View::render('nome-modulo::vista', [
            'data' => $data,
            'modules' => \Core\ModuleLoader::getActiveModules()
        ]);
    }
}
```

**CRITICO - Pattern return nelle route:**
```php
// In public/index.php - le route GET che renderizzano view DEVONO usare return
Router::get('/percorso', function () {
    $controller = new Controllers\NomeController();
    return $controller->index();  // ← RETURN obbligatorio!
});

// Le route POST che fanno solo redirect NON servono return
Router::post('/percorso', function () {
    $controller = new Controllers\NomeController();
    $controller->store();  // ← OK senza return (fa redirect)
});
```

**Perche:** Il Router fa `echo $handler()`. Se la funzione non ritorna la stringa HTML, la pagina sara vuota (blank page). `View::render()` ritorna una stringa, non la stampa direttamente.

### Chiamata AI
```php
use Services\AiService;

$ai = new AiService('nome-modulo'); // SEMPRE specificare modulo

if (!$ai->isConfigured()) {
    return ['error' => true, 'message' => 'AI non configurata'];
}

$result = $ai->analyze($userId, $prompt, $content, 'nome-modulo');

Database::reconnect(); // PRIMA di salvare
$model->save($data);
```

### Consumo Crediti
```php
use Core\Credits;

$cost = Credits::getCost('operazione', 'nome-modulo');

if (!Credits::hasEnough($userId, $cost)) {
    return ['error' => "Crediti insufficienti. Richiesti: {$cost}"];
}

// Esegui operazione...

Credits::consume($userId, $cost, 'operazione', 'nome-modulo');
```

### Scraping Contenuti Web
```php
use Services\ScraperService;

$scraper = new ScraperService();
$result = $scraper->scrape($url);  // USA SEMPRE scrape()

if ($result['success']) {
    $title = $result['title'];
    $content = $result['content'];          // Testo pulito via Readability
    $headings = $result['headings'];        // Array struttura H1-H6
    $wordCount = $result['word_count'];
    $internalLinks = $result['internal_links']; // Link interni estratti
}

// MAI usare DOMDocument/XPath diretto per estrarre contenuti!
// MAI creare servizi di scraping custom per modulo!
```

### Logging Chiamate API Esterne
```php
use Services\ApiLoggerService;

$startTime = microtime(true);  // PRIMA della chiamata

// Esegui chiamata API...
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);

// Log DOPO la chiamata
ApiLoggerService::log('provider-name', '/endpoint', $requestPayload, $data, $httpCode, $startTime, [
    'module' => 'nome-modulo',
    'cost' => $data['cost'] ?? 0,
    'context' => "keyword={$keyword}",
]);

// Provider validi: dataforseo, serpapi, serper, google_gsc, google_oauth, google_ga4
// IMPORTANTE: Redact API keys prima del log!
```

### AJAX Sincrono Lungo (Scraping + AI senza SSE)

**Quando usare questo pattern:**
- Operazioni AJAX che durano 30s-300s (scraping + AI call)
- Quando SSE/background job è eccessivo per il caso d'uso
- Reference: `ai-content/WizardController::generateBrief()`, `ads-analyzer/CampaignController::evaluate()`

**Pattern OBBLIGATORIO:**
```php
public function longOperation(): void
{
    // 1. Protezione processo (proxy SiteGround uccide connessioni)
    ignore_user_abort(true);
    set_time_limit(0);

    // 2. Output buffering OBBLIGATORIO — cattura warning/errori PHP
    ob_start();
    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        // Validazioni rapide...

        // 3. Chiudi sessione PRIMA delle operazioni lunghe
        session_write_close();

        // 4. Operazione lunga (scraping, AI, etc.)
        $result = $this->doLongWork();
        Database::reconnect(); // Sempre dopo operazione lunga

        // 5. Risposta success: pulisci buffer PRIMA di echo
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $result]);
        exit;

    } catch (\Exception $e) {
        error_log("Error: " . $e->getMessage());

        // 6. Risposta errore: pulisci buffer PRIMA di echo
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
```

**CRITICO — Perché `ob_start()` è obbligatorio:**
- Senza `ob_start()`, qualsiasi PHP warning/notice durante scraping o AI corrompe il JSON
- Il frontend riceve HTML misto a JSON → `response.json()` lancia eccezione
- In produzione il processo muore silenziosamente → record DB bloccato in stato intermedio
- **NON usare `jsonResponse()` helper** — non fa `ob_end_clean()`, usa `echo json_encode()` + `exit`

**Frontend AJAX per operazioni lunghe:**
```javascript
const resp = await fetch(url, { method: 'POST', body: formData });

// SEMPRE controllare resp.ok PRIMA di resp.json()
if (!resp.ok) {
    // Proxy timeout (502/504): backend potrebbe continuare in background
    this.errorMsg = 'Operazione avviata. Potrebbe richiedere qualche minuto. Ricarica tra poco.';
    setTimeout(() => location.reload(), 15000);
    return;
}

const data = await resp.json();
```

---

### Background Processing (Operazioni Lunghe)

**Quando usare job in background:**
- Operazioni > 5 secondi
- Elaborazione batch (3+ items)
- Chiamate API esterne con rate limits
- Operazioni che devono essere cancellabili dall'utente

**Pattern Database:**
```sql
-- Tabella job tracking (esempio: st_rank_jobs)
CREATE TABLE {prefix}_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('cron', 'manual') DEFAULT 'manual',
    status ENUM('pending', 'running', 'completed', 'error', 'cancelled') DEFAULT 'pending',

    -- Progress
    items_requested INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500) DEFAULT NULL,

    -- Timestamps
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_project (project_id)
);

-- Aggiungi job_id alla tabella queue esistente
ALTER TABLE {prefix}_queue ADD COLUMN job_id INT DEFAULT NULL;
```

**Pattern Backend (Controller):**
```php
// 1. POST /start-job - Crea job e ritorna job_id
public function startJob(int $projectId): void {
    // Verifica auth, crediti, progetto
    // Crea record in {prefix}_jobs
    // Popola {prefix}_queue con job_id
    echo json_encode(['success' => true, 'job_id' => $jobId]);
}

// 2. GET /stream - SSE per progress real-time
public function processStream(int $projectId): void {
    // Headers SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    // CRITICO: Continua esecuzione anche se proxy chiude connessione
    ignore_user_abort(true);
    set_time_limit(0);

    // IMPORTANTE: Chiudi sessione PRIMA del loop
    session_write_close();

    // Loop elaborazione
    while ($item = $queue->getNextPendingForJob($jobId)) {
        // IMPORTANTE: Riconnetti DB dopo ogni chiamata API
        Database::reconnect();

        // Check cancellazione
        if ($jobModel->isCancelled($jobId)) {
            $this->sendEvent('cancelled', [...]);
            break;
        }

        // Elabora item
        // ...

        // Invia evento SSE
        $this->sendEvent('item_completed', [...]);
    }

    $this->sendEvent('completed', [...]);
}

// Helper per SSE
private function sendEvent(string $event, array $data): void {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();  // Guard: server potrebbe non avere buffer
    flush();
}

// 3. GET /job-status - Polling fallback
public function jobStatus(int $projectId): void {
    echo json_encode(['success' => true, 'job' => $jobModel->getJobResponse($jobId)]);
}

// 4. POST /cancel-job - Annulla job
public function cancelJob(int $projectId): void {
    $jobModel->cancel($jobId);
    echo json_encode(['success' => true]);
}
```

**Pattern Frontend (Alpine.js):**
```javascript
// Avvia job
async startJob() {
    const resp = await fetch('/start-job', { method: 'POST', body: formData });
    const data = await resp.json();
    this.jobId = data.job_id;
    this.connectSSE();
}

// Connetti SSE
connectSSE() {
    this.eventSource = new EventSource(`/stream?job_id=${this.jobId}`);

    this.eventSource.addEventListener('item_completed', (e) => {
        const data = JSON.parse(e.data);
        // Aggiorna UI
    });

    this.eventSource.addEventListener('completed', (e) => {
        this.eventSource.close();
        // Finalizza
    });

    // Errori custom dal server (event: error nel stream)
    this.eventSource.addEventListener('error', (e) => {
        try {
            const d = JSON.parse(e.data);
            // Errore server-sent con messaggio
            this.status = 'Errore: ' + d.message;
            this.eventSource.close();
        } catch (_) {
            // Errore nativo SSE (no data) - gestito da onerror sotto
        }
    });

    // Fallback a polling se SSE si disconnette (proxy timeout)
    this.eventSource.onerror = () => {
        this.eventSource.close();
        this.startPolling();
    };
}

// Polling fallback
async startPolling() {
    const resp = await fetch(`/job-status?job_id=${this.jobId}`);
    const data = await resp.json();
    // Aggiorna UI
    if (data.job.status === 'running') {
        setTimeout(() => this.startPolling(), 2000);
    }
}
```

**Eventi SSE standard:**
- `started` - Job avviato con totale items
- `progress` - Aggiornamento progresso (current_item, percent)
- `item_completed` - Singolo item completato con risultati
- `item_error` - Errore su singolo item
- `completed` - Job terminato con successo
- `cancelled` - Job annullato dall'utente

**Reference Implementation:** `modules/seo-tracking/controllers/RankCheckController.php`

---

## PATTERN TABELLE STANDARD

### Componenti Shared

Tutti i moduli DEVONO usare i componenti condivisi per tabelle:

| Componente | File | Uso |
|-----------|------|-----|
| Pagination | `core/Pagination.php` | `Pagination::make($total, $page, $perPage)` |
| Pagination HTML | `shared/views/components/table-pagination.php` | `View::partial('components/table-pagination', [...])` |
| Empty State | `shared/views/components/table-empty-state.php` | `View::partial('components/table-empty-state', [...])` |
| Sort Headers | `shared/views/components/table-helpers.php` | `table_sort_header($label, $field, ...)` |
| Bulk Bar | `shared/views/components/table-bulk-bar.php` | `View::partial('components/table-bulk-bar', [...])` |

### Struttura Dati Paginazione (Standard Unico)

```php
// Generata da Pagination::make() o dal model
[
    'current_page' => 1,
    'last_page' => 10,
    'total' => 250,
    'per_page' => 25,
    'from' => 1,    // 1-based
    'to' => 25,
]
```

### CSS Classi Standard Tabelle

```html
<!-- Wrapper -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-slate-50 dark:bg-slate-700/50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
          <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
```

**Standard stabiliti (Golden Rule #20):**
- Bordo container: `rounded-xl` (NON `rounded-lg` ne `rounded-2xl`)
- Tabella: `w-full` (NON `min-w-full`)
- Padding celle th/td: `px-4 py-3` (NON `px-6 py-3`, `px-6 py-4`, `px-4 py-2`)
- Header card: `px-4 py-3 border-b` (NON `px-6 py-4`)
- Header th: `text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider`
- Badge status: `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium`
- Hover riga: `hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors` (NON `/30`)
- Thead dark: `dark:bg-slate-700/50` (NON `dark:bg-slate-800/50`)

**NON usare MAI nelle tabelle:**

| Sbagliato | Corretto | Motivo |
|-----------|----------|--------|
| `rounded-2xl` / `rounded-lg` | `rounded-xl` | Standard uniforme container |
| `px-6 py-3` / `px-6 py-4` | `px-4 py-3` | Padding compatto uniforme |
| `dark:bg-slate-800/50` (thead) | `dark:bg-slate-700/50` | Contrasto corretto dark mode |
| `dark:hover:bg-slate-700/30` | `dark:hover:bg-slate-700/50` | Hover visibile |
| `min-w-full` | `w-full` | Evita overflow |
| `font-medium` senza `text-xs uppercase tracking-wider` | `text-xs font-medium uppercase tracking-wider` | Header standard |
| Paginazione inline copy-paste | `View::partial('components/table-pagination', [...])` | Componente shared |
| Bulk selection vanilla JS | Alpine `selectedIds` + `table_bulk_init()` | Pattern unico |

### Pattern Empty State

```php
// Nella view — usa View::partial
<?= \Core\View::partial('components/table-empty-state', [
    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    'heading' => 'Nessun elemento trovato',
    'message' => 'Inizia aggiungendo il primo elemento.',
    'ctaText' => 'Aggiungi',         // opzionale
    'ctaUrl' => url('/modulo/add'),   // opzionale
]) ?>
```

### Pattern Sorting (Server-Side)

```php
// Nel controller
$filters = [
    'sort' => $_GET['sort'] ?? 'created_at',
    'dir'  => $_GET['dir'] ?? 'desc',
];

// Nel model — allowlist obbligatoria
$allowedSort = ['keyword', 'status', 'created_at'];
$sortField = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'created_at';
$sortDir = (strtolower($filters['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';
```

```php
// Nella view — include helper + genera headers
include __DIR__ . '/../../../../shared/views/components/table-helpers.php';
$currentSort = $filters['sort'] ?? 'created_at';
$currentDir = $filters['dir'] ?? 'desc';
$baseUrl = url('/modulo/projects/' . $project['id'] . '/sezione');

// Nell'header tabella
<?= table_sort_header('Keyword', 'keyword', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
<?= table_header('Azioni', 'right') ?> // non sortable
```

### Pattern Paginazione (View)

```php
// Nella view — usa View::partial (scope isolato, evita conflitti variabili)
<?= \Core\View::partial('components/table-pagination', [
    'pagination' => $pagination,
    'baseUrl' => $baseUrl,
    'filters' => $paginationFilters, // preserva sort/dir/status nei link
]) ?>
```

### Pattern Bulk Operations

```php
// Nella view
<?php include __DIR__ . '/../../../../shared/views/components/table-helpers.php'; ?>
<div x-data="<?= table_bulk_init(array_column($items, 'id')) ?>">
    <?= \Core\View::partial('components/table-bulk-bar', [
        'actions' => [
            ['label' => 'Elimina', 'action' => 'bulkDelete()', 'color' => 'red'],
        ]
    ]) ?>
    <!-- Tabella con checkbox -->
    <?= table_checkbox_header() ?>
    <?= table_checkbox_cell($item['id']) ?>
</div>
```

---

## ICONE HEROICONS (più usate)

```html
<!-- Check -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- X/Close -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
</svg>

<!-- Plus -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
</svg>

<!-- Search -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
</svg>

<!-- Download -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
</svg>

<!-- Refresh -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
</svg>

<!-- Trash -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
</svg>
```

---

## CHECKLIST PRE-COMMIT

```
[ ] Nessun curl diretto per API AI
[ ] Nessuna icona Lucide/FontAwesome
[ ] Tutti i testi UI in italiano
[ ] Tabelle con prefisso modulo corretto
[ ] Nessuna API key in file
[ ] Query SQL con prepared statements
[ ] CSRF token: campo `_csrf_token` in form/FormData, `csrf_token()` per il valore
[ ] Database::reconnect() dopo chiamate lunghe
[ ] Scraping usa ScraperService::scrape()
[ ] Chiamate API esterne loggano con ApiLoggerService
[ ] php -l su file modificati
[ ] Operazioni lunghe usano job in background (SSE) o pattern AJAX lungo
[ ] session_write_close() prima di loop SSE e prima di operazioni AJAX lunghe
[ ] ignore_user_abort(true) + set_time_limit(0) in SSE streams e AJAX lunghi
[ ] ob_start() + ob_end_clean() in AJAX lunghi (NON usare jsonResponse(), echo+exit)
[ ] ob_flush() con guard: if (ob_get_level()) ob_flush() (solo SSE)
[ ] ModuleLoader::getSetting() per settings modulo (NON getModuleSetting)
[ ] SSE: salvare risultati nel DB PRIMA dell'evento completed (polling fallback)
[ ] Testare in produzione: deploy → creare tabelle DB → attivare modulo
[ ] Docs utente aggiornate se feature significativa (shared/views/docs/)
[ ] Data Model aggiornato se nuove tabelle/colonne (docs/data-model.html)
[ ] Link nelle view usano percorsi project-scoped (MAI percorsi legacy dentro contesto progetto)
[ ] Paginazione preserva contesto progetto
[ ] Nessun valore hardcoded nelle view (crediti, limiti → Credits::getCost() dal controller)
[ ] Tabelle usano componenti shared (table-pagination, table-helpers, table-empty-state)
[ ] Container wrapper: rounded-xl (NON rounded-lg/rounded-2xl), w-full (NON min-w-full)
[ ] Thead dark mode: bg-slate-700/50 (NON bg-slate-800/50)
[ ] Controller: `return View::render(...)` (NON solo `View::render(...)`) + route handler: `return $controller->method()`
```

---

## WORKFLOW CONSIGLIATO

1. **Leggi docs pertinenti** prima di iniziare
2. **Un task alla volta** - fermati per conferma
3. **Verifica sintassi** dopo ogni modifica PHP
4. **Test manuale** in browser prima di commit
5. **Aggiorna documentazione** se sviluppo significativo (docs utente + data model)
6. **Commit atomici** con messaggi descrittivi

---

## TROUBLESHOOTING RAPIDO

| Problema | Causa probabile | Fix |
|----------|-----------------|-----|
| 500 Error | Sintassi PHP | `php -l file.php` |
| "MySQL gone away" | Connessione scaduta | `Database::reconnect()` |
| Sidebar non appare | `$modules` non passato | Aggiungi a View::render() |
| Icone non visibili | Lucide invece di Heroicons | Sostituisci con SVG |
| Crediti non scalano | `Credits::consume()` mancante | Aggiungi dopo operazione |
| "Database is limited" | Limite SiteGround temporaneo | Attendi qualche minuto e riprova |
| Scraping poche parole | CSS selectors invece di Readability | Usa `ScraperService::scrape()` |
| SSE blocca altre request | Sessione non chiusa | `session_write_close()` prima del loop |
| SSE non invia eventi | Output buffering | `ob_flush(); flush();` dopo ogni evento |
| Job bloccato in processing | Processo crashato | `resetStuckProcessing(30)` nel model queue |
| API call non loggata | ApiLoggerService mancante | Aggiungi log dopo ogni chiamata API |
| "Errore di connessione" AJAX POST | Campo CSRF con nome sbagliato | Usare `_csrf_token` (con underscore), NON `csrf_token` |
| SSE timeout dopo 30s | `set_time_limit` non rimosso | `set_time_limit(0)` prima del loop SSE e prima di AI call |
| SSE "Connessione persa" 90% | Proxy SiteGround chiude SSE | `ignore_user_abort(true)` + polling fallback |
| SSE dati persi dopo disconnect | Risultati inviati solo via SSE | Salvare nel DB PRIMA dell'evento `completed` |
| `ob_flush(): Failed to flush` | Nessun buffer attivo | `if (ob_get_level()) ob_flush()` |
| `getModuleSetting()` undefined | Metodo inesistente | Usare `ModuleLoader::getSetting(slug, key, default)` |
| Modulo non visibile in prod | Tabelle/record assenti | Creare tabelle + INSERT in `modules` |
| AJAX lungo: processo muore silenzioso | Manca `ob_start()` | Aggiungere `ob_start()` + `ob_end_clean()` prima di echo (Golden Rule #17) |
| AJAX lungo: JSON corrotto | Warning PHP nel body | `ob_start()` cattura warning, `ob_end_clean()` li scarta |
| AJAX lungo: record DB bloccato | Processo ucciso senza cleanup | `ignore_user_abort(true)` + salvare stato nel DB a ogni step |
| `jsonResponse()` in operazione lunga | Non fa `ob_end_clean()` | Usare `ob_end_clean()` + `echo json_encode()` + `exit` |
| Bottone non funziona dalla dashboard | CustomEvent senza listener nella pagina | Usare `<a>` link con `?param=1` + `x-init` nella pagina target |
| Paginazione perde contesto progetto | Link hardcoded a percorso legacy | Usare `$paginationBase` project-aware (Golden Rule #19) |
| Link articoli/keyword vanno a vista globale | Percorso legacy `/modulo/sezione` | Usare `$baseUrl` project-scoped: `/modulo/projects/{id}/sezione` |
| Costi crediti sbagliati in dashboard | Valori hardcoded nella view | Passare `Credits::getCost()` dal controller |
| Paginazione non uniforme | Componente inline | Usare `View::partial('components/table-pagination', ...)` |
| Sort headers non funzionano | Manca include table-helpers | `include __DIR__ . '/../../../../shared/views/components/table-helpers.php'` |
| Variabili paginazione in conflitto | `include` condivide scope | Usare `View::partial()` (scope isolato) |
| Pagina vuota (blank page) | Manca `return` in controller o route handler | Controller: `return View::render(...)`, Route: `return $controller->method()` (Golden Rule #21) |

---

*File generato per Claude Code - Non modificare manualmente senza aggiornare la data*
