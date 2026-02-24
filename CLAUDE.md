# AINSTEIN - Istruzioni Claude Code

> Caricato automaticamente ad ogni sessione. Ultimo aggiornamento: 2026-02-24 (notifiche)

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
1.  AiService centralizzato         → new AiService('modulo-slug')
2.  Icone SOLO Heroicons SVG        → MAI Lucide, MAI FontAwesome
3.  Lingua UI ITALIANO              → Tutti i testi visibili
4.  Prefisso DB per modulo          → aic_, sa_, st_, il_, ga_, cc_, kr_, ao_, so_
5.  API Keys in Database            → MAI in .env, MAI hardcoded
6.  Routes pattern standard         → /modulo/projects/{id}/sezione
7.  Prepared statements SEMPRE      → MAI concatenare SQL
8.  CSRF token su form POST         → campo `_csrf_token` (con underscore!)
9.  ai-content e il reference       → Copia pattern da li
10. Database::reconnect()           → Prima di salvare dopo AI call
11. OAuth GSC pattern seo-tracking  → GoogleOAuthService centralizzato
12. Scraping con Readability        → ScraperService::scrape() per tutto
13. Background jobs per op. lunghe  → SSE + job queue
14. ApiLoggerService per API        → Tutte le chiamate API esterne loggano
15. ignore_user_abort(true)         → SEMPRE in SSE e AJAX lunghi
16. ModuleLoader::getSetting()      → NON getModuleSetting() (non esiste)
17. ob_start() in AJAX lunghi       → Output buffering OBBLIGATORIO
18. Aggiornare docs dopo sviluppo   → Docs utente + Data Model
19. Link project-scoped nelle view  → MAI percorsi legacy in contesto progetto
20. Tabelle standard CSS uniforme   → rounded-xl, px-4 py-3, bg-slate-700/50
21. return View::render() SEMPRE    → Controller E route handler ritornano il risultato
```

---

## STATO MODULI

| Modulo | Slug | Prefisso DB | Stato |
|--------|------|-------------|-------|
| AI Content Generator | `ai-content` | `aic_` | Completo (reference pattern) |
| SEO Audit | `seo-audit` | `sa_` | Completo |
| Google Ads Analyzer | `ads-analyzer` | `ga_` | Completo |
| SEO Tracking | `seo-tracking` | `st_` | Completo |
| AI Keyword Research | `keyword-research` | `kr_` | Completo |
| Content Creator | `content-creator` | `cc_` | Completo (4 CMS connectors) |
| Internal Links | `internal-links` | `il_` | 85% (manca AI Suggester) |
| AI Optimizer | `ai-optimizer` | `ao_` | In sviluppo |
| SEO On-Page | `seo-onpage` | `so_` | In sviluppo |

---

## GLOBAL PROJECTS (Hub Centralizzato)

Hub `/projects` che raggruppa moduli per cliente/sito.

| Livello | Tabella | Route |
|---------|---------|-------|
| Globale | `projects` | `/projects/{id}` |
| Modulo | `{prefix}_projects` | `/modulo/projects/{id}/sezione` |
| FK | `global_project_id` (nullable) | Collega modulo ↔ progetto globale |

**Flusso**: `/projects/create` → Dashboard `/projects/{id}` → "Attiva modulo" → modal tipo (se tipizzato) → POST `/projects/{id}/activate-module`

**Moduli con tipi** (modal selezione): ai-content (manual/auto/meta-tag), keyword-research (research/architecture/editorial), ads-analyzer (campaign/campaign-creator)

**File chiave**: `core/Models/GlobalProject.php` (MODULE_CONFIG, MODULE_TYPES), `controllers/GlobalProjectController.php`, `shared/views/projects/`

**Regole**:
- "Nuovo Progetto" nei moduli → `url('/projects/create')` SEMPRE
- Route GET create dei moduli → redirect a `/projects/create`
- Progetti orfani → funzionano + banner amber (`shared/views/components/orphaned-project-notice.php`)
- KPI dashboard: ogni modulo implementa `getProjectKpi($moduleProjectId)`

### Condivisione Progetti

Progetti condivisibili tra utenti con 3 ruoli: Owner, Editor, Viewer.

| Tabella | Scopo |
|---------|-------|
| `project_members` | Membri condivisi (ruolo, accettazione) |
| `project_member_modules` | Moduli accessibili per membro |
| `project_invitations` | Inviti via email (token, scadenza) |

**Services**: `ProjectAccessService` (autorizzazione), `ProjectSharingService` (operazioni)

**Pattern**: `findAccessible($id, $userId)` sostituisce `find($id, $userId)` nei controller per supportare accesso condiviso.

**Crediti**: sempre scalati dall'owner via `ProjectAccessService::getOwnerId()`.

### Sistema Notifiche In-App

| Tabella | Scopo |
|---------|-------|
| `notifications` | Notifiche in-app (tipo, titolo, read_at, action_url) |
| `notification_preferences` | Preferenze email per tipo notifica |

**Service**: `NotificationService::send($userId, $type, $title, $options)`

**Tipi v1**: `project_invite`, `project_invite_accepted`, `project_invite_declined`, `operation_completed`, `operation_failed`

**Polling**: `/notifications/unread-count` ogni 30s, Alpine.js `notificationBell()` in layout.php

**Pagina**: `/notifications` — lista completa con filtri e paginazione

**Preferenze**: Toggle email in `/profile`, salvate in `notification_preferences`

---

## STRUTTURA PROGETTO

```
seo-toolkit/
├── admin/controllers/
│   ├── AdminController.php        # Dashboard, utenti, piani, moduli, settings
│   ├── FinanceController.php      # Dashboard finanziaria
│   ├── AiLogsController.php       # Log chiamate AI
│   ├── ApiLogsController.php      # Log chiamate API
│   └── JobsController.php         # Gestione job background
├── core/
│   ├── Auth.php, Database.php, Router.php, View.php, Middleware.php
│   ├── Credits.php, ModuleLoader.php, Settings.php, Pagination.php
│   ├── Cache.php, Logger.php, BrandingHelper.php, OnboardingService.php
│   └── Models/GlobalProject.php
├── services/
│   ├── AiService.php              # Claude API - USARE SEMPRE
│   ├── ScraperService.php         # Scraping Readability - USARE SEMPRE
│   ├── ApiLoggerService.php       # Log API esterne - USARE SEMPRE
│   ├── GoogleOAuthService.php     # OAuth GSC
│   ├── DataForSeoService.php      # Rank check API
│   ├── RapidApiKeywordService.php # Keyword volumes
│   ├── KeywordsEverywhereService.php # Volume fallback
│   ├── SitemapService.php, CsvImportService.php
│   ├── NotificationService.php     # Notifiche in-app + email
│   ├── EmailService.php, ExportService.php
│   └── connectors/                # CMS connectors (WP, Shopify, etc.)
├── modules/
│   ├── ai-content/                # REFERENCE per nuovi moduli
│   ├── seo-audit/, ads-analyzer/, internal-links/
│   ├── seo-tracking/, keyword-research/, content-creator/
│   ├── ai-optimizer/, seo-onpage/
├── controllers/GlobalProjectController.php
├── shared/views/
│   ├── layout.php
│   ├── projects/                  # index, create, dashboard, settings
│   ├── components/
│   │   ├── nav-items.php          # Sidebar accordion
│   │   ├── import-tabs.php        # Import URL
│   │   ├── table-pagination.php, table-empty-state.php, table-helpers.php, table-bulk-bar.php
│   │   ├── dashboard-hero-banner.php, dashboard-kpi-card.php, dashboard-stats-row.php
│   │   ├── dashboard-how-it-works.php, dashboard-mode-card.php
│   │   ├── credit-badge.php, orphaned-project-notice.php
│   │   └── module-ai-settings.php, module-provider-settings.php
│   └── docs/                      # Documentazione utente pubblica (/docs)
├── docs/                          # Docs tecniche (GOLDEN-RULES.md, specs/, data-model.html, etc.)
├── public/                        # Entry point, landing pages, assets
└── config/                        # app.php, database.php, modules.php
```

---

## DOCS TECNICHE DI RIFERIMENTO

| Quando | File (in `docs/`) |
|--------|-------------------|
| Sempre | `GOLDEN-RULES.md` |
| Nuovo modulo | `PLATFORM_OVERVIEW.md`, `MODULE_NAVIGATION.md` |
| Import URL | `IMPORT_STANDARDS.md` |
| Integrazione AI | `AI_SERVICE_STANDARDS.md` |
| Modulo specifico | `specs/{modulo}.md` |
| API esterne | `API-LOGS.md` |
| Deploy | `DEPLOY.md` |

---

## PATTERN DI SVILUPPO

### Controller (Golden Rule #21)

```php
// Controller DEVE ritornare string
public function index(): string {
    Middleware::auth();
    return View::render('modulo::vista', [  // ← RETURN obbligatorio!
        'data' => $data,
        'modules' => \Core\ModuleLoader::getActiveModules()
    ]);
}

// Route handler DEVE ritornare
Router::get('/path', fn() => (new Controller())->index());  // ← return implicito
```

### Chiamata AI

```php
$ai = new AiService('nome-modulo');
$result = $ai->analyze($userId, $prompt, $content, 'nome-modulo');
Database::reconnect();  // SEMPRE dopo AI call
```

### Crediti

```php
$cost = Credits::getCost('operazione', 'nome-modulo');
if (!Credits::hasEnough($userId, $cost)) { /* errore */ }
// ... operazione ...
Credits::consume($userId, $cost, 'operazione', 'nome-modulo');
```

### Scraping

```php
$result = (new ScraperService())->scrape($url);
// Ritorna: success, title, content, headings, word_count, internal_links
// MAI DOMDocument/XPath diretto, MAI servizi scraping custom
```

### API Logging

```php
$startTime = microtime(true);
// ... chiamata API ...
ApiLoggerService::log('provider', '/endpoint', $request, $response, $httpCode, $startTime, [
    'module' => 'nome-modulo', 'cost' => 0, 'context' => "info"
]);
// Provider: dataforseo, serpapi, serper, google_gsc, google_oauth, google_ga4, rapidapi_keyword_insight
```

### AJAX Sincrono Lungo (30s-300s, senza SSE)

**Reference**: `ai-content/WizardController::generateBrief()`, `ads-analyzer/CampaignController::evaluate()`

Pattern obbligatorio:
1. `ignore_user_abort(true)` + `set_time_limit(0)`
2. `ob_start()` + `header('Content-Type: application/json')`
3. `session_write_close()` prima operazioni lunghe
4. Operazione + `Database::reconnect()`
5. `ob_end_clean()` + `echo json_encode([...])` + `exit` (NON `jsonResponse()`)
6. Frontend: controllare `resp.ok` PRIMA di `resp.json()`

### Background Processing (SSE)

**Reference**: `modules/seo-tracking/controllers/RankCheckController.php`

Usare quando: operazioni > 5s, batch 3+ items, rate limits, cancellabile.

Pattern: `startJob()` → `processStream()` (SSE) → `jobStatus()` (polling fallback) → `cancelJob()`

Regole critiche SSE:
- `ignore_user_abort(true)` + `set_time_limit(0)` + `session_write_close()`
- `Database::reconnect()` dopo ogni chiamata API
- `if (ob_get_level()) ob_flush(); flush();` per inviare eventi
- Salvare risultati nel DB PRIMA dell'evento `completed`
- Polling fallback obbligatorio (proxy SiteGround chiude SSE)

Eventi standard: `started`, `progress`, `item_completed`, `item_error`, `completed`, `cancelled`

---

## STANDARD TABELLE CSS (Golden Rule #20)

### Componenti Shared (obbligatori)

| Componente | Uso |
|-----------|-----|
| `core/Pagination.php` | `Pagination::make($total, $page, $perPage)` |
| `components/table-pagination` | `View::partial('components/table-pagination', [...])` |
| `components/table-empty-state` | `View::partial('components/table-empty-state', [...])` |
| `components/table-helpers` | `table_sort_header()`, `table_bulk_init()`, `table_checkbox_*()` |
| `components/table-bulk-bar` | `View::partial('components/table-bulk-bar', [...])` |

### Classi CSS obbligatorie

| Elemento | Classe |
|----------|--------|
| Container | `rounded-xl` (NON rounded-lg/2xl) |
| Table | `w-full` (NON min-w-full) |
| Celle th/td | `px-4 py-3` (NON px-6) |
| Header th | `text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider` |
| Thead dark | `dark:bg-slate-700/50` (NON /800) |
| Hover riga | `hover:bg-slate-50 dark:hover:bg-slate-700/50` (NON /30) |
| Badge status | `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` |

---

## LANDING PAGE PATTERN ("Scopri cosa puoi fare")

Ogni modulo ha sezione educativa in fondo alla pagina landing, con 7 sezioni in ordine fisso.

**Reference**: `modules/keyword-research/views/dashboard.php` (da linea 150)

**7 sezioni**: Separator divider → Hero educativo (grid 2 col) → Come funziona (3 step card) → Feature blocks (3x alternanza bianco/slate) → Cosa puoi fare (grid 6 card) → FAQ accordion (Alpine.js) → CTA finale (gradient)

**Colori modulo**: ai-content (amber), seo-audit (emerald), seo-tracking (blue), keyword-research (purple), ads-analyzer (rose), internal-links (cyan)

**Regole**: icone Heroicons SVG, visual mockup HTML/CSS (NON screenshot), CTA → `url('/projects/create')`, dark mode su tutto, dopo contenuto operativo

---

## STANDARD module.json (Admin Settings)

Ordine gruppi: `ai_config` (0) → `provider_config` (0.5, se necessario) → `general` (1) → feature groups (2+) → `costs` (99, collapsed: true)

**Reference**: `modules/seo-tracking/module.json`

Regole: tutti i settings con `"group"` assegnato, moduli AI con `ai_provider`/`ai_model`/`ai_fallback_enabled`

---

## DOCUMENTAZIONE (Golden Rule #18)

Dopo sviluppo significativo aggiornare:

1. **Docs utente** (`shared/views/docs/{slug}.php`) — struttura: Cos'e, Quick Start, Funzionalita, Costi crediti, Suggerimenti
2. **Data Model** (`docs/data-model.html`) — Mermaid.js erDiagram + details per tabella
3. Se nuova pagina: aggiungere route in `public/index.php` `$validPages` + sidebar in `layout.php` `$navItems`

Colori bordi modulo: amber(aic), emerald(sa), blue(st), purple(kr), cyan(il), rose(ga)

---

## COMANDI FREQUENTI

```bash
# SSH Produzione
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html

# Deploy
git push origin main          # locale
git pull origin main          # produzione (da SSH)

# Verifica sintassi PHP
php -l path/to/file.php

# Database produzione
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 < file.sql
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "SHOW TABLES LIKE 'prefisso_%';"

# Database locale
mysql -u root seo_toolkit -e "SHOW TABLES;"

# Test locale
# URL: http://localhost/seo-toolkit
# Email: admin@seo-toolkit.local | Pass: admin123

# Test produzione
# URL: https://ainstein.it
# Email: clementeborghetti@gmail.com | Pass: (accesso via Google OAuth)
```

### Credenziali Admin per Test

| Ambiente | URL | Email | Password |
|----------|-----|-------|----------|
| Locale | `http://localhost/seo-toolkit` | `admin@seo-toolkit.local` | `admin123` |
| Produzione | `https://ainstein.it` | `clementeborghetti@gmail.com` | Google OAuth |

### Cron Jobs SiteGround

Formato: `/usr/bin/php /home/u1608-ykgnd3z1twn4/www/ainstein.it/public_html/<path>.php`

Regole: NON usare `~`, `$HOME`, redirect `>>`. Logs scritti dallo script stesso.

```
cron/cleanup-data.php                              # Daily (0 2 * * *)
modules/ai-content/cron/dispatcher.php             # Every Min
modules/ads-analyzer/cron/auto-evaluate.php        # Every 5 Min
modules/seo-tracking/cron/rank-dispatcher.php      # Every 5 Min
modules/seo-tracking/cron/gsc-sync-dispatcher.php  # Hourly
modules/seo-audit/cron/crawl-dispatcher.php        # Every 5 Min
modules/seo-tracking/cron/ai-report-dispatcher.php # Hourly
```

---

## CHECKLIST PRE-COMMIT

```
[ ] Nessun curl diretto per API AI → AiService
[ ] Icone solo Heroicons SVG (no Lucide/FontAwesome)
[ ] Testi UI in italiano
[ ] Prefisso DB corretto per modulo
[ ] Nessuna API key in file
[ ] Query SQL con prepared statements
[ ] CSRF: campo `_csrf_token`, valore `csrf_token()`
[ ] Database::reconnect() dopo chiamate lunghe
[ ] Scraping via ScraperService::scrape()
[ ] API esterne loggate con ApiLoggerService
[ ] php -l su file modificati
[ ] Operazioni lunghe: SSE o pattern AJAX lungo (ob_start + ignore_user_abort)
[ ] return View::render() nel controller + return nel route handler
[ ] Tabelle: componenti shared, rounded-xl, px-4 py-3, dark:bg-slate-700/50
[ ] Link project-scoped (MAI percorsi legacy)
[ ] Docs aggiornate se feature significativa
```

---

## TROUBLESHOOTING

| Problema | Fix |
|----------|-----|
| Pagina vuota (blank page) | Manca `return` in controller/route handler (GR #21) |
| 500 Error | `php -l file.php` per errori sintassi |
| "MySQL gone away" | `Database::reconnect()` dopo operazioni lunghe |
| Sidebar non appare | `$modules` non passato a `View::render()` |
| "Errore di connessione" AJAX | CSRF: usare `_csrf_token` (con underscore!) |
| AJAX lungo: processo muore | Manca `ob_start()` → warning corrompe JSON (GR #17) |
| AJAX lungo: JSON corrotto | `ob_end_clean()` prima di `echo json_encode()` + `exit` |
| SSE blocca altre request | `session_write_close()` prima del loop |
| SSE "Connessione persa" | `ignore_user_abort(true)` + polling fallback (proxy SiteGround) |
| SSE dati persi | Salvare nel DB PRIMA dell'evento `completed` |
| `ob_flush(): Failed to flush` | `if (ob_get_level()) ob_flush()` |
| `getModuleSetting()` undefined | Usare `ModuleLoader::getSetting(slug, key, default)` |
| Modulo non visibile in prod | Creare tabelle DB + INSERT in `modules` |
| Crediti sbagliati in view | Passare `Credits::getCost()` dal controller (no hardcode) |
| Paginazione perde contesto | Usare `$baseUrl` project-scoped con `View::partial()` |

---

## WORKFLOW

1. Leggi docs pertinenti (`docs/`) prima di iniziare
2. Un task alla volta — fermati per conferma
3. `php -l` dopo ogni modifica PHP
4. Test manuale in browser prima di commit
5. Aggiorna documentazione se sviluppo significativo
6. Commit atomici con messaggi descrittivi

---

*File per Claude Code — Aggiornare la data ad ogni modifica*
