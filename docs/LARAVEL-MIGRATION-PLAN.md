# Piano di Migrazione: Ainstein SEO Toolkit → Laravel 12

> **Tipo:** Documento di architettura e roadmap (solo pianificazione)
> **Data:** 2026-02-13

---

## 1. STATO ATTUALE — Analisi Completa

### 1.1 Dimensioni del Progetto

| Metrica | Valore |
|---------|--------|
| **Tabelle database** | 92 |
| **Route totali** | 514 |
| **Controller** | 40+ |
| **Servizi condivisi** | 12 |
| **Moduli attivi** | 7 (6 completati + 1 da implementare) |
| **Linee di codice stimate** | ~50.000+ |
| **Views PHP** | ~150+ |

### 1.2 Stack Tecnologico Attuale

| Componente | Tecnologia | Note |
|------------|-----------|------|
| **Backend** | PHP 8+ custom framework | No Composer, no autoloading PSR-4 |
| **Database** | MySQL 8 + PDO singleton | Prepared statements, auto-reconnect |
| **Frontend** | Alpine.js + Tailwind CSS (CDN) + HTMX | Nessun build system |
| **AI** | Claude API (Anthropic) + OpenAI fallback | Dual-provider con fallback automatico |
| **Auth** | Session + Remember Cookie | Google OAuth per login + GSC/GA4 |
| **Background Jobs** | SSE streaming + polling fallback | No queue system (Redis/DB) |
| **Deploy** | Git pull su SiteGround | Nessun CI/CD |

### 1.3 Architettura Core Attuale

```
public/index.php          → Entry point + route definitions
core/Router.php            → Pattern matcher {param} → regex
core/Database.php          → PDO singleton con auto-reconnect (2 retry)
core/Auth.php              → Session + remember token (SHA256, 30gg)
core/Credits.php           → Dual logging (credit_transactions + usage_log)
core/ModuleLoader.php      → Plugin registry + lazy route loading
core/View.php              → Template engine con extract() + layout composition
core/Middleware.php         → Static guards (auth, csrf, rate limit)
core/Settings.php          → Key-value con in-memory cache
core/OnboardingService.php → Tour progress per modulo
```

### 1.4 Moduli e Route per Modulo

| Modulo | Slug | Prefisso DB | Route | Controller | Tabelle |
|--------|------|-------------|-------|------------|---------|
| AI Content Generator | `ai-content` | `aic_` | 128 | 8 (Project, Keyword, Wizard, Serp, Article, WordPress, Auto, MetaTag, InternalLinks, Job) | 7 |
| SEO Audit | `seo-audit` | `sa_` | 66 | 6 (Project, Crawl, Gsc, Audit, ActionPlan, LinkStructure, Report, Api) | 12 |
| SEO Tracking | `seo-tracking` | `st_` | 105 | 8 (Project, Dashboard, Gsc, Keyword, Group, RankCheck, Alert, Ai, Report, Url, Export, Api) | 14 |
| Keyword Research | `keyword-research` | `kr_` | 47 | 5 (Dashboard, Project, Research, Architecture, Editorial, QuickCheck) | 4 |
| Internal Links | `internal-links` | `il_` | 77 | 1 (ProjectController + inline closures) | 7 |
| Google Ads Analyzer | `ads-analyzer` | `ga_` | 65 | 8 (Dashboard, Project, Analysis, AnalysisHistory, Campaign, SearchTermAnalysis, Export, Script, Settings, Api) | 22 |
| Content Creator | `content-creator` | `cc_` | 0 | 0 (non implementato) | 5 (schema pronto) |

### 1.5 Servizi Condivisi

| Servizio | File | Dipendenze | Complessità Migrazione |
|----------|------|-----------|----------------------|
| **AiService** | `services/AiService.php` | Database, Settings, Credits, ApiLoggerService | Alta — dual provider, fallback, token logging |
| **ScraperService** | `services/ScraperService.php` | cURL, Readability | Media — wrapper stateless |
| **GoogleOAuthService** | `services/GoogleOAuthService.php` | Database, Settings | Media — OAuth2 flow manuale |
| **ApiLoggerService** | `services/ApiLoggerService.php` | Database | Bassa — logging puro |
| **DataForSeoService** | `services/DataForSeoService.php` | Database, Settings, ApiLoggerService | Media — API wrapper |
| **SitemapService** | `services/SitemapService.php` | cURL, SimpleXML | Bassa — stateless |
| **CsvImportService** | `services/CsvImportService.php` | — | Bassa — stateless |
| **RapidApiKeywordService** | `services/RapidApiKeywordService.php` | Settings, ApiLoggerService | Media — API wrapper |
| **KeywordsEverywhereService** | `services/KeywordsEverywhereService.php` | Settings, ApiLoggerService | Media — API wrapper |
| **ExportService** | `services/ExportService.php` | — | Bassa — CSV/Excel helper |
| **SerpApiService** | `modules/ai-content/services/SerpApiService.php` | Settings, ApiLoggerService | Media — modulo-specifico ma usato cross-module |
| **KeywordInsightService** | `modules/keyword-research/services/KeywordInsightService.php` | Settings, ApiLoggerService | Media — modulo-specifico |

### 1.6 Pattern Critici da Preservare

1. **SSE Streaming** — Usato in 5+ posti per operazioni lunghe (rank check, keyword collection, scraping batch, meta tags generation, auto article processing)
2. **AJAX Lungo con ob_start()** — Pattern obbligatorio per operazioni 30-300s (brief generation, campaign evaluation)
3. **Dual-Provider AI** — Anthropic primario + OpenAI fallback con logging unificato
4. **Credits System** — getCost() con cascata modulo→settings→config + dual logging
5. **Google OAuth** — Centralizzato per login + GSC + GA4 (3 scopi diversi)
6. **Module Settings** — JSON in modules.settings, configurabile da admin panel
7. **ignore_user_abort(true)** — Critico per proxy SiteGround che chiude connessioni
8. **Database::reconnect()** — Dopo ogni operazione lunga/AI call

---

## 2. ARCHITETTURA TARGET — Laravel 12

### 2.1 Stack Target

| Componente | Tecnologia | Package |
|------------|-----------|---------|
| **Framework** | Laravel 12 | `laravel/laravel` |
| **Moduli** | nwidart/laravel-modules | `nwidart/laravel-modules` v11+ |
| **Frontend** | Livewire 3 + Alpine.js | `livewire/livewire` |
| **CSS** | Tailwind CSS (Vite) | `tailwindcss` via Vite |
| **Auth** | Laravel Breeze (customizzato) | `laravel/breeze` |
| **OAuth** | Laravel Socialite | `laravel/socialite` (Google) |
| **Queue** | Laravel Queue (database driver) | Built-in |
| **Cache** | Laravel Cache (file/Redis) | Built-in |
| **Admin** | Filament v3 | `filament/filament` |
| **AI** | Custom Service (da migrare) | — |
| **API Logging** | Custom Service (da migrare) | — |
| **PDF/Export** | Maatwebsite/Excel + DomPDF | `maatwebsite/excel`, `barryvdh/laravel-dompdf` |

### 2.2 Struttura Directory (nwidart/laravel-modules)

```
ainstein/
├── app/
│   ├── Models/              # Modelli core (User, Plan, Subscription, Module, Setting)
│   ├── Services/            # Servizi condivisi
│   │   ├── AiService.php
│   │   ├── ScraperService.php
│   │   ├── GoogleOAuthService.php
│   │   ├── ApiLoggerService.php
│   │   ├── DataForSeoService.php
│   │   ├── SitemapService.php
│   │   ├── CsvImportService.php
│   │   ├── CreditService.php
│   │   └── ...
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── CheckCredits.php
│   │   │   ├── ModuleActive.php
│   │   │   └── ...
│   │   └── Controllers/
│   │       ├── Auth/         # Login, Register, OAuth, Password Reset
│   │       ├── DashboardController.php
│   │       ├── ProfileController.php
│   │       └── DocsController.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AiServiceProvider.php    # Registra AiService nel container
│   │   └── ...
│   └── Console/
│       └── Commands/
│           ├── ProcessQueue.php      # Sostituisce cron/process_queue.php
│           ├── DailySync.php         # Sostituisce cron/daily_sync.php
│           └── ...
├── Modules/                          # nwidart/laravel-modules
│   ├── AiContent/
│   │   ├── app/
│   │   │   ├── Http/Controllers/
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Jobs/               # Queue jobs (sostituiscono SSE)
│   │   │   ├── Livewire/           # Componenti Livewire
│   │   │   └── Events/
│   │   ├── config/
│   │   ├── database/migrations/
│   │   ├── resources/views/
│   │   ├── routes/
│   │   │   ├── web.php
│   │   │   └── api.php
│   │   └── module.json
│   ├── SeoAudit/
│   ├── SeoTracking/
│   ├── KeywordResearch/
│   ├── InternalLinks/
│   ├── AdsAnalyzer/
│   └── ContentCreator/
├── config/
│   ├── modules.php           # Config nwidart
│   ├── ai.php                # Config AI providers
│   └── credits.php           # Config costi default
├── database/
│   └── migrations/           # Solo tabelle core
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php        # Layout autenticato
│   │   │   ├── guest.blade.php      # Layout guest
│   │   │   └── docs.blade.php       # Layout docs
│   │   ├── components/
│   │   │   ├── nav-items.blade.php
│   │   │   ├── import-tabs.blade.php
│   │   │   └── ...
│   │   ├── docs/
│   │   └── livewire/
│   └── css/
│       └── app.css           # Tailwind entry
├── routes/
│   ├── web.php               # Route core + auth + docs
│   └── api.php               # API routes (ads-analyzer ingest)
└── vite.config.js
```

### 2.3 Mapping Core → Laravel

| Attuale | Laravel | Note |
|---------|---------|------|
| `core/Router.php` | `routes/web.php` + module `routes/web.php` | Route groups con middleware |
| `core/Database.php` | Eloquent ORM + `DB` facade | Auto-reconnect via `sticky`, `retry_after` |
| `core/Auth.php` | Laravel Auth (Guards + Providers) | `Auth::user()`, `Auth::check()` |
| `core/Credits.php` | `app/Services/CreditService.php` | Registrato come singleton nel container |
| `core/ModuleLoader.php` | nwidart/laravel-modules | ServiceProvider per modulo |
| `core/View.php` | Blade template engine | `@extends`, `@section`, `@component` |
| `core/Middleware.php` | `app/Http/Middleware/` | CheckCredits, ModuleActive, etc. |
| `core/Settings.php` | `app/Models/Setting.php` + Cache | `Setting::get('key')` con cache Redis/file |
| `core/OnboardingService.php` | `app/Services/OnboardingService.php` | Invariato nella logica |
| `public/index.php` | `public/index.php` (Laravel bootstrap) | Completamente diverso |
| `config/app.php` | `config/app.php` + `.env` | Variabili ambiente in .env |
| SSE streams | Laravel Queue + Broadcasting | `php artisan queue:work` |
| `cron/dispatcher.php` | `app/Console/Kernel.php` | `$schedule->command()` |
| `session_write_close()` | Non necessario (Queue separate) | Jobs in processi separati |
| `ignore_user_abort(true)` | Non necessario (Queue separate) | Jobs non dipendono dalla connessione HTTP |
| `ob_start()` pattern | Non necessario | Blade gestisce output buffering |

---

## 3. MAPPING DATABASE

### 3.1 Strategia: Migrazioni dallo Schema Esistente

Generare migrazioni Laravel (`php artisan make:migration`) che replicano esattamente lo schema attuale. **Nessuna modifica ai nomi tabelle/colonne** per garantire compatibilità con i dati esistenti.

### 3.2 Modelli Eloquent — Core

| Tabella | Modello | Relazioni |
|---------|---------|-----------|
| `users` | `App\Models\User` | hasMany(CreditTransaction, UsageLog, Subscription), hasOne(Subscription active) |
| `plans` | `App\Models\Plan` | hasMany(Subscription) |
| `subscriptions` | `App\Models\Subscription` | belongsTo(User, Plan) |
| `credit_transactions` | `App\Models\CreditTransaction` | belongsTo(User, Admin:User) |
| `usage_log` | `App\Models\UsageLog` | belongsTo(User) |
| `modules` | `App\Models\Module` | — (JSON settings cast) |
| `settings` | `App\Models\Setting` | — |
| `password_resets` | — | Usa tabella built-in Laravel |
| `projects` | `App\Models\Project` | belongsTo(User), morphTo per tipo modulo |

### 3.3 Modelli Eloquent — Per Modulo (esempio AI Content)

```
Modules/AiContent/app/Models/
├── Keyword.php          → aic_keywords (belongsTo User, hasMany SerpResult, hasOne Article)
├── SerpResult.php       → aic_serp_results (belongsTo Keyword)
├── PaaQuestion.php      → aic_paa_questions (belongsTo Keyword)
├── Article.php          → aic_articles (belongsTo User, Keyword, WpSite; hasMany Source)
├── Source.php           → aic_sources (belongsTo Article)
├── WpSite.php           → aic_wp_sites (belongsTo User, hasMany Article, WpPublishLog)
└── WpPublishLog.php     → aic_wp_publish_log (belongsTo Article, WpSite)
```

**Pattern per tutti i moduli:** Ogni modello specifica `$table = 'prefisso_nome'` per mantenere i nomi attuali.

### 3.4 Eloquent Casts Necessari

```php
// Esempio per il modello Module
protected $casts = [
    'settings' => 'array',     // JSON → array automatico
    'is_active' => 'boolean',
];

// Esempio per Research
protected $casts = [
    'brief' => 'array',        // JSON → array
    'ai_response' => 'array',  // JSON → array
    'credits_used' => 'decimal:2',
];
```

### 3.5 Tabelle Totali per Modulo

| Modulo | Tabelle | Modelli Eloquent | Note Migrazione |
|--------|---------|-----------------|-----------------|
| Core | 9 | 7 (User, Plan, Subscription, CreditTransaction, UsageLog, Module, Setting) | password_resets gestito da Laravel |
| AI Content | 7 | 7 | Relazioni keyword→article→sources |
| SEO Audit | 12 | 12 | GSC connections con OAuth tokens |
| SEO Tracking | 14+ | 14+ | Tabelle BIGINT per dati giornalieri |
| Keyword Research | 4 | 4 | JSON pesante in ai_response |
| Internal Links | 7 | 7 | Snapshot/compare mode |
| Content Creator | 5 | 5 | Schema pronto, no dati |
| Google Ads | 22 | 15+ | Molte tabelle da migrations incrementali |
| **Totale** | **~80+** | **~70+** | — |

---

## 4. MAPPING SERVIZI

### 4.1 AiService → Laravel Service

**Attuale:** Classe PHP con constructor che carica settings dal DB, metodi `analyze()`, `complete()`, `analyzeWithSystem()`.

**Target:** Service registrato nel container IoC.

```php
// app/Providers/AiServiceProvider.php
class AiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(AiService::class, function ($app, $params) {
            return new AiService($params['module'] ?? null);
        });
    }
}

// Uso nei controller
class MyController extends Controller
{
    public function analyze(Request $request)
    {
        $ai = app(AiService::class, ['module' => 'ai-content']);
        $result = $ai->analyzeWithSystem($userId, $prompt, $content, 'ai-content');
    }
}
```

**Cambiamenti necessari:**
- Sostituire `Database::fetch()` → `DB::table()` o Eloquent
- Sostituire `Settings::get()` → `Setting::get()` o `config()`
- Sostituire `Credits::consume()` → `app(CreditService::class)->consume()`
- Mantere la logica dual-provider e fallback identica

### 4.2 CreditService → Laravel Service

```php
// app/Services/CreditService.php
class CreditService
{
    public function getBalance(int $userId): float;
    public function hasEnough(int $userId, float $amount): bool;
    public function consume(int $userId, float $amount, string $action, ?string $module, array $meta = []): bool;
    public function add(int $userId, float $amount, string $type, string $description, ?int $adminId = null): void;
    public function getCost(string $operation, ?string $module = null): float;
}
```

### 4.3 GoogleOAuthService → Laravel Socialite + Custom

**Login Google:** Gestito da `laravel/socialite` (`Socialite::driver('google')`).

**GSC/GA4 OAuth:** Servizio custom che usa i token OAuth (access_token + refresh_token) per le API Google. Non è un semplice login — è un OAuth flow separato per dare permessi GSC.

```php
// app/Services/GoogleOAuthService.php (custom, non Socialite)
class GoogleOAuthService
{
    public function getAuthorizationUrl(string $redirectUri, array $scopes): string;
    public function exchangeCode(string $code, string $redirectUri): array; // tokens
    public function refreshToken(string $refreshToken): array;
    public function revokeToken(string $token): bool;
}
```

### 4.4 Background Jobs — Da SSE a Laravel Queue

**Problema attuale:** SSE è fragile (proxy timeout, `ignore_user_abort`, `session_write_close`, `ob_flush`). Il 90% dei bug in produzione sono legati a SSE.

**Soluzione Laravel:**

```
┌─────────────────────┐     ┌──────────────────────┐
│  Controller          │     │  Laravel Queue Worker │
│  dispatch(Job)       │────▶│  php artisan queue:work│
│  return job_id       │     │  (processo separato)   │
└─────────────────────┘     └──────────────────────┘
         │                            │
         │ polling                    │ broadcast
         ▼                            ▼
┌─────────────────────┐     ┌──────────────────────┐
│  GET /job-status     │     │  Laravel Broadcasting │
│  (JSON polling)      │     │  (Pusher/Reverb)      │
└─────────────────────┘     └──────────────────────┘
```

**Opzione A — Polling semplice (consigliata per SiteGround):**
- Controller crea Job e ritorna `job_id`
- Job gira come processo separato (`php artisan queue:work`)
- Frontend fa polling ogni 2s su endpoint `/job-status?id=X`
- Zero problemi di proxy, zero SSE
- `database` come queue driver (nessuna dipendenza aggiuntiva)

**Opzione B — Laravel Reverb (WebSocket):**
- Real-time via WebSocket nativo Laravel
- Richiede processo Reverb (`php artisan reverb:start`)
- Più complesso su hosting condiviso (SiteGround)

**Raccomandazione:** Opzione A (polling) per ora, con possibilità di aggiungere Reverb in futuro.

### 4.5 Mapping Job per Operazione

| Operazione Attuale (SSE) | Laravel Job | Modulo |
|--------------------------|-------------|--------|
| Rank Check batch | `RankCheckJob` | SeoTracking |
| Keyword Collection (seeds) | `KeywordCollectionJob` | KeywordResearch |
| SERP Extraction | `SerpExtractionJob` | AiContent |
| Meta Tags Scrape batch | `MetaTagScrapeJob` | AiContent |
| Meta Tags Generate batch | `MetaTagGenerateJob` | AiContent |
| Auto Article Processing | `AutoArticleJob` | AiContent |
| Internal Links Scrape | `LinkScrapeJob` | InternalLinks |
| GSC Full Sync | `GscSyncJob` | SeoTracking / SeoAudit |

### 4.6 Cron → Laravel Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Equivalente di cron/dispatcher.php
    $schedule->command('queue:work --once')->everyMinute();

    // Sync giornaliero GSC
    $schedule->command('seo:sync-gsc')->dailyAt('03:00');

    // Report settimanali AI
    $schedule->command('seo:weekly-reports')->weeklyOn(1, '08:00');

    // Report mensili AI
    $schedule->command('seo:monthly-reports')->monthlyOn(1, '08:00');

    // Cleanup vecchi job
    $schedule->command('queue:prune-batches --hours=48')->daily();

    // Auto-evaluate Ads
    $schedule->command('ads:auto-evaluate')->everyFifteenMinutes();
}
```

---

## 5. MAPPING ROUTE

### 5.1 Da Route Flat a Route Groups

**Attuale** (`public/index.php` + `modules/*/routes.php`):
```php
Router::get('/seo-tracking/project/{id}/keywords', [KeywordController::class, 'index']);
Router::post('/seo-tracking/project/{id}/keywords/store', [KeywordController::class, 'store']);
```

**Laravel** (`Modules/SeoTracking/routes/web.php`):
```php
Route::middleware(['auth', 'module:seo-tracking'])->prefix('seo-tracking')->group(function () {
    Route::resource('project.keywords', KeywordController::class)->scoped();

    Route::prefix('project/{project}')->group(function () {
        // Background jobs
        Route::post('rank-check/start-job', [RankCheckController::class, 'startJob']);
        Route::get('rank-check/job-status', [RankCheckController::class, 'jobStatus']);
        Route::post('rank-check/cancel-job', [RankCheckController::class, 'cancelJob']);

        // API data
        Route::get('api/chart/traffic', [ApiController::class, 'trafficChart']);
    });
});
```

### 5.2 Middleware Stack

| Attuale | Laravel Middleware | Tipo |
|---------|-------------------|------|
| `Middleware::auth()` | `auth` | Built-in |
| `Middleware::guest()` | `guest` | Built-in |
| `Middleware::admin()` | `can:admin` | Gate/Policy |
| `Middleware::csrf()` | `VerifyCsrfToken` | Built-in (automatico) |
| `Middleware::hasCredits()` | `check.credits:5` | Custom |
| `Middleware::rateLimit()` | `throttle:60,1` | Built-in |
| — (nuovo) | `module.active:slug` | Custom |

### 5.3 Route Totali Stimate post-Migrazione

Le 514 route attuali si ridurranno a ~400 grazie a:
- `Route::resource()` per CRUD standard
- Rimozione route legacy/duplicate (ai-content ha ~20 route legacy)
- `Route::apiResource()` per endpoint JSON

---

## 6. MAPPING FRONTEND

### 6.1 Views PHP → Blade Templates

**Attuale:** File `.php` con `<?= $var ?>` e helper globali (`e()`, `url()`, `csrf_field()`).

**Laravel Blade:** Conversione quasi 1:1.

| PHP Attuale | Blade Equivalente |
|-------------|-------------------|
| `<?= e($var) ?>` | `{{ $var }}` |
| `<?= $var ?>` (no escape) | `{!! $var !!}` |
| `<?= url('/path') ?>` | `{{ url('/path') }}` o `{{ route('name') }}` |
| `<?= csrf_field() ?>` | `@csrf` |
| `<?php if ($x): ?>...<?php endif; ?>` | `@if($x)...@endif` |
| `<?php foreach ($items as $item): ?>` | `@foreach($items as $item)` |
| `<?php echo View::partial('comp', $data) ?>` | `@include('comp', $data)` o `<x-comp :data="$data" />` |
| Layout `View::render('module::view', [...])` | `@extends('layouts.app')` + `@section('content')` |

### 6.2 Alpine.js → Livewire + Alpine.js

**Pattern attuale:** Alpine.js gestisce tutto il JS inline (wizard multi-step, form validation, AJAX fetch, SSE).

**Target Livewire 3:**

| Pattern Attuale | Livewire Equivalente |
|-----------------|---------------------|
| Alpine.js wizard multi-step | Livewire component con `$step` property |
| `fetch()` AJAX → JSON | `wire:click="doAction"` → metodo PHP |
| SSE EventSource | Livewire polling `wire:poll.2s` + Job status |
| Alpine.js sorting tabelle | `wire:click="sortBy('column')"` |
| Alpine.js filtri inline | `wire:model.live="filter"` |
| Alpine.js modal/dropdown | Rimane Alpine.js (UI pura) |

**Cosa rimane Alpine.js:**
- Toggle dark mode
- Dropdown, modal, tooltip (UI components)
- Animazioni e transizioni
- Tutto ciò che è puramente client-side

**Cosa migra a Livewire:**
- Wizard multi-step (stato lato server)
- Tabelle con sorting/filtri/paginazione
- Form con validazione
- Dashboard con refresh dati
- Progress monitoring (polling)

### 6.3 Tailwind CSS — Da CDN a Vite

**Attuale:** Tailwind CSS CDN (`<script src="https://cdn.tailwindcss.com">`). Funziona ma:
- Non tree-shaking (carica tutto Tailwind)
- Non custom plugin support
- Più lento al caricamento

**Target:** Vite + Tailwind CSS PostCSS

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom brand colors (da tailwind.config.js) */
```

**Impatto:** Tutte le classi Tailwind esistenti funzionano identiche. Solo il metodo di caricamento cambia.

### 6.4 Componenti Blade Condivisi

```
resources/views/components/
├── layouts/
│   ├── app.blade.php           # Layout principale (sidebar + topbar)
│   ├── guest.blade.php         # Layout login/register
│   └── docs.blade.php          # Layout docs
├── nav-items.blade.php         # Sidebar navigation con accordion
├── import-tabs.blade.php       # Componente import URL (sitemap/csv/manual)
├── cluster-card.blade.php      # Card cluster keyword research
├── table-helpers.blade.php     # JS sorting/filtri condiviso
├── onboarding-spotlight.blade.php  # Tour onboarding
└── stats-card.blade.php        # Card statistiche
```

---

## 7. ADMIN PANEL

### 7.1 Da Admin Custom a Filament v3

**Attuale:** Admin panel custom con controller dedicati (`admin/controllers/`) e views PHP (`admin/views/`).

**Target:** Filament v3 — admin panel Laravel con:
- Dashboard con widget
- CRUD automatico per Users, Modules, Settings
- Form builder per Module Settings (sostituisce `admin/views/module-settings.php`)
- Table builder per API Logs, Credit Transactions

### 7.2 Filament Resources

```
app/Filament/Resources/
├── UserResource.php           # Users CRUD + credits management
├── ModuleResource.php         # Modules + settings JSON editor
├── SettingResource.php        # Global settings
├── PlanResource.php           # Piani abbonamento
├── CreditTransactionResource.php  # Log crediti (read-only)
├── UsageLogResource.php       # Log utilizzo (read-only)
└── ApiLogResource.php         # Log API (read-only)
```

### 7.3 Filament Widgets (Dashboard Admin)

```
app/Filament/Widgets/
├── StatsOverview.php          # Utenti attivi, crediti totali, ricavi
├── RecentTransactions.php     # Ultime transazioni crediti
├── ApiUsageChart.php          # Grafico utilizzo API
└── ModuleUsageChart.php       # Utilizzo per modulo
```

---

## 8. AUTENTICAZIONE

### 8.1 Da Custom Auth a Laravel Auth

| Feature Attuale | Laravel Equivalente |
|----------------|---------------------|
| `Auth::attempt(email, pass)` | `Auth::attempt(['email' => $e, 'password' => $p])` |
| `Auth::login(user, remember)` | `Auth::login($user, $remember)` |
| `Auth::logout()` | `Auth::logout()` |
| `Auth::check()` | `Auth::check()` |
| `Auth::user()` → array | `Auth::user()` → Eloquent model |
| `Auth::isAdmin()` | `Auth::user()->isAdmin()` o Policy |
| Remember token (SHA256, 30gg) | Laravel built-in (60 chars, configurable) |
| Google OAuth custom | `Socialite::driver('google')` |

### 8.2 Google OAuth — Doppio Uso

**Login:** `Socialite::driver('google')->redirect()` → `Socialite::driver('google')->user()`

**GSC/GA4:** Servizio custom separato (NON Socialite). Necessita di scope specifici (`webmasters.readonly`, `analytics.readonly`) e salva token per uso continuativo.

```php
// Login via Socialite
Route::get('/auth/google', fn() => Socialite::driver('google')->redirect());
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// GSC via custom service (scope diversi)
Route::get('/seo-tracking/project/{id}/gsc/authorize', [GscController::class, 'authorize']);
Route::get('/seo-tracking/gsc/connected', [GscController::class, 'callback']);
```

---

## 9. ROADMAP FASI DI MIGRAZIONE

### Fase 0 — Preparazione (1-2 settimane)

| Task | Dettaglio |
|------|----------|
| Setup progetto Laravel 12 | `composer create-project laravel/laravel ainstein` |
| Installare packages | nwidart/laravel-modules, livewire, filament, socialite |
| Configurare Vite | Tailwind CSS + Alpine.js + Livewire |
| Configurare database | `.env` con MySQL esistente |
| Generare migrazioni | Da schema SQL esistente (92 tabelle) |
| Verificare migrazioni | `php artisan migrate` su DB vuoto di test |

### Fase 1 — Core Infrastructure (2-3 settimane)

| Task | Dettaglio | Dipendenze |
|------|----------|------------|
| Modello User + Auth | Laravel Breeze customizzato, Google OAuth con Socialite | — |
| CreditService | Port da `Credits.php`, registra singleton nel container | User model |
| SettingService | Port da `Settings.php` con cache Laravel | — |
| AiService | Port completo con dual-provider, registra nel container | Settings, Credits |
| ApiLoggerService | Port da servizio attuale | — |
| Middleware custom | CheckCredits, ModuleActive, VerifyProjectOwnership | Auth, Credits |
| Layout Blade | Port del layout attuale (sidebar, topbar, dark mode) | — |
| Route core | Login, Register, Dashboard, Profile, Docs | Auth |

### Fase 2 — Admin Panel (1-2 settimane)

| Task | Dettaglio | Dipendenze |
|------|----------|------------|
| Filament setup | Installazione e config base | Fase 1 |
| UserResource | CRUD utenti + gestione crediti | User model |
| ModuleResource | CRUD moduli + settings JSON | Module model |
| SettingResource | Settings globali | Setting model |
| Dashboard widgets | Stats, grafici, log recenti | Tutti i modelli |

### Fase 3 — Servizi Condivisi (1-2 settimane)

| Task | Dettaglio | Dipendenze |
|------|----------|------------|
| ScraperService | Port con HTTP client Laravel | — |
| GoogleOAuthService | Port per GSC/GA4 (non Socialite) | — |
| SitemapService | Port con HTTP client | — |
| CsvImportService | Port o sostituire con Maatwebsite/Excel | — |
| DataForSeoService | Port con API logging | ApiLogger |
| RapidApiKeywordService | Port con API logging | ApiLogger |
| ExportService | Port o sostituire con Maatwebsite/Excel | — |
| Queue infrastructure | Tabella jobs, Worker config | — |

### Fase 4 — Moduli (ordinati per complessità crescente)

#### 4A — Keyword Research (1-2 settimane) — 4 tabelle, 47 route
- Il più semplice tra i moduli completi
- 4 modelli Eloquent
- 5 controller
- Wizard multi-step → Livewire component
- SSE collection → Queue Job + polling
- Quick Check → Livewire component semplice

#### 4B — Internal Links (1-2 settimane) — 7 tabelle, 77 route
- Molte route inline (closures) → refactor in controller
- Scraping batch → Queue Job
- Snapshot/compare mode
- Graph visualizzazione

#### 4C — Google Ads Analyzer (2-3 settimane) — 22 tabelle, 65 route
- Molte tabelle ma logica relativamente lineare
- API ingest endpoint (pubblico)
- Campaign evaluation → Queue Job
- Export Google Ads Editor format

#### 4D — SEO Audit (2-3 settimane) — 12 tabelle, 66 route
- Crawl engine → Queue Job con batch processing
- GSC OAuth + data sync
- AI analysis per categoria
- Action Plan AI
- PDF export

#### 4E — SEO Tracking (3-4 settimane) — 14 tabelle, 105 route
- Il più complesso: GSC + GA4 + Keywords + Groups + Alerts + Reports
- Rank Check con DataForSEO/SerpAPI → Queue Job
- Multiple SSE streams → Queue Jobs
- AI Reports (weekly/monthly) → Scheduled Commands
- Alert system → Notifications Laravel

#### 4F — AI Content Generator (3-4 settimane) — 7 tabelle, 128 route
- Il più grande per route (128!)
- Wizard multi-step (brief → article) → Livewire
- Auto mode con queue processing
- Meta Tags batch operations → Queue Jobs
- WordPress integration
- Cover image DALL-E 3
- Internal Links pool

#### 4G — Content Creator (1 settimana) — 5 tabelle, 0 route
- Schema DB pronto, nessuna implementazione
- Implementare da zero in Laravel
- CMS connectors (WordPress, Shopify, PrestaShop)

### Fase 5 — Testing & QA (2-3 settimane)

| Task | Dettaglio |
|------|----------|
| Feature tests | Test HTTP per ogni route critica |
| Unit tests | Test per servizi (AiService, CreditService) |
| Browser tests | Laravel Dusk per wizard multi-step |
| Migrazione dati | Script per verificare compatibilità dati esistenti |
| Performance | Benchmark vs versione attuale |

### Fase 6 — Deploy & Cutover (1 settimana)

| Task | Dettaglio |
|------|----------|
| Setup server | PHP 8.3, Composer, Node.js su SiteGround |
| Configure .env | DB, API keys, mail, queue |
| Build assets | `npm run build` (Vite) |
| Migrate database | `php artisan migrate` (solo nuove tabelle Laravel) |
| Queue worker | Configurare supervisor o cron per `queue:work` |
| DNS cutover | Puntare dominio alla nuova app |
| Monitor | Log errori prime 48h |

---

## 10. STIMA TEMPI

| Fase | Durata Stimata | Sviluppatori |
|------|---------------|-------------|
| Fase 0 — Preparazione | 1-2 settimane | 1 |
| Fase 1 — Core | 2-3 settimane | 1 |
| Fase 2 — Admin | 1-2 settimane | 1 |
| Fase 3 — Servizi | 1-2 settimane | 1 |
| Fase 4 — Moduli (tutti) | 13-19 settimane | 1-2 |
| Fase 5 — Testing | 2-3 settimane | 1 |
| Fase 6 — Deploy | 1 settimana | 1 |
| **TOTALE** | **~21-32 settimane** | **~5-8 mesi** |

Con 2 sviluppatori in parallelo (uno su core+servizi, uno su moduli) si può ridurre a **~4-5 mesi**.

---

## 11. RISCHI E MITIGAZIONI

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| **Hosting SiteGround limitato** | Alta | Alto | Verificare supporto: Composer, Node.js, supervisor per queue worker, processi background |
| **Queue worker non disponibile** | Media | Alto | Fallback: `php artisan queue:work --once` via cron ogni minuto |
| **Dati esistenti incompatibili** | Bassa | Alto | Migrazioni da schema esistente, non rinominare tabelle |
| **Livewire performance** | Media | Medio | Fallback ad Alpine.js puro dove Livewire è troppo pesante |
| **SSE → Polling regressione UX** | Media | Basso | Polling ogni 1-2s è comunque accettabile; Reverb in futuro |
| **Filament troppo pesante** | Bassa | Medio | Admin panel custom se Filament rallenta troppo |
| **Tempo sottostimato** | Alta | Alto | Rilasci incrementali: core+1 modulo alla volta in produzione |
| **Feature parity** | Media | Alto | Checklist funzionalità per ogni modulo prima del cutover |

---

## 12. VANTAGGI POST-MIGRAZIONE

| Aspetto | Attuale | Laravel |
|---------|---------|---------|
| **Dependency management** | Nessuno (include manuali) | Composer (autoloading PSR-4) |
| **ORM** | Query SQL manuali | Eloquent (relazioni, scopes, events, casts) |
| **Background jobs** | SSE fragile + polling | Laravel Queue (robusto, retry, fail handling) |
| **Testing** | Nessuno | PHPUnit + Feature tests + Dusk |
| **Caching** | In-memory per request | Redis/File cache persistente |
| **Mail** | Nessuno | Laravel Mail + Notifications |
| **Auth** | Custom session | Guards, Policies, Sanctum/Passport |
| **Validation** | Manuale | Form Requests con regole dichiarative |
| **Security** | CSRF manuale | Automatico (middleware stack) |
| **Rate limiting** | Session-based | Cache-based (distribuito) |
| **Asset management** | CDN manuali | Vite (bundle, tree-shake, HMR) |
| **Error handling** | error_log() | Exception handler, Sentry, logging strutturato |
| **CI/CD** | Git pull manuale | GitHub Actions, Laravel Forge, Envoyer |
| **API** | Nessuna struttura | Laravel Sanctum per API token |
| **Documentazione** | CLAUDE.md manuale | PHPDoc + IDE support completo |

---

## 13. DECISIONI ARCHITETTURALI CHIAVE

### 13.1 Mantenere i nomi tabelle attuali
**Decisione:** SI — `$table = 'aic_keywords'` nei modelli Eloquent.
**Motivo:** Zero rischio perdita dati, migrazione seamless.

### 13.2 Queue driver: database
**Decisione:** `database` (non Redis).
**Motivo:** SiteGround potrebbe non supportare Redis. Il driver database è affidabile per il volume attuale.

### 13.3 Filament per admin, non Nova
**Decisione:** Filament v3 (open source) vs Laravel Nova (a pagamento).
**Motivo:** Filament è gratuito, ben mantenuto, e supporta form builder per i JSON settings dei moduli.

### 13.4 Livewire per interattività, non Inertia
**Decisione:** Livewire 3 + Alpine.js.
**Motivo:** Più vicino all'architettura attuale (server-rendered + JS progressivo). Inertia richiederebbe riscrittura totale del frontend.

### 13.5 Polling per job progress, non WebSocket
**Decisione:** Polling HTTP ogni 1-2 secondi.
**Motivo:** Affidabile su hosting condiviso. WebSocket (Reverb) è un upgrade futuro opzionale.

### 13.6 SerpApiService e KeywordInsightService restano nei moduli
**Decisione:** Servizi modulo-specifici restano nel modulo.
**Motivo:** Usati solo da 1-2 moduli, non sono veramente "condivisi".

---

*Piano generato il 2026-02-13 — Solo documento di analisi e pianificazione*
