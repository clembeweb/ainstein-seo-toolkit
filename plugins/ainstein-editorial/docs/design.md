# Design Tecnico — Ainstein Editorial

> Documento di architettura completo. Decisioni di alto livello in `decisions.md`. Milestone di esecuzione in `roadmap.md`.
> Ultimo aggiornamento: 2026-05-12

---

## Indice

1. [Visione di sistema](#1-visione-di-sistema)
2. [Architettura high-level](#2-architettura-high-level)
3. [Stack tecnologico](#3-stack-tecnologico)
4. [Database schema](#4-database-schema)
5. [Backend API contract](#5-backend-api-contract)
6. [Plugin WordPress — struttura](#6-plugin-wordpress--struttura)
7. [Flow funzionali principali](#7-flow-funzionali-principali)
8. [Content Brain — specifica](#8-content-brain--specifica)
9. [Auto-pilot — architettura](#9-auto-pilot--architettura)
10. [Internal linking algorithm](#10-internal-linking-algorithm)
11. [Auth & License flow](#11-auth--license-flow)
12. [UX/UI Design System](#12-uxui-design-system)
13. [Refactoring backend Ainstein](#13-refactoring-backend-ainstein)
14. [Error handling & monitoring](#14-error-handling--monitoring)
15. [Testing strategy](#15-testing-strategy)
16. [Deployment & operations](#16-deployment--operations)
17. [Roadmap v1.1+](#17-roadmap-v11)

---

## 1. Visione di sistema

Ainstein Editorial è un **sistema distribuito su 3 componenti** che cooperano per fornire content marketing automatizzato a siti WordPress:

```
┌─────────────────────┐         ┌──────────────────────┐         ┌──────────────────────┐
│  Plugin WP installato│ ◀────▶ │  Backend Ainstein    │ ◀────▶ │  Lemon Squeezy        │
│  sul sito utente    │  REST   │  (server condiviso)  │  REST   │  (MoR + license API) │
│                     │  SSE    │                      │         │                      │
│  - Admin UI         │         │  - API endpoints     │         │  - Billing           │
│  - Cron settimanale │         │  - AI services       │         │  - License validate  │
│  - Settings WP      │         │  - DB MySQL          │         │  - Customer portal   │
└─────────────────────┘         └──────────────────────┘         └──────────────────────┘
                                          │
                                          │ riusa
                                          ▼
                                ┌──────────────────────┐
                                │  Servizi Ainstein     │
                                │  esistenti           │
                                │  - AiService         │
                                │  - SerpApiService    │
                                │  - ScraperService    │
                                │  - GoogleOAuthService│
                                │  - ApiLoggerService  │
                                └──────────────────────┘
```

**Plugin**: thin client. Tutta la logica AI/SEO è server-side. Plugin si occupa solo di UI, settings WP locali, e chiamate al backend.

**Backend**: server Hetzner che ospita anche Ainstein. Plugin chiama endpoint dedicati `/api/editorial/*` autenticati via license key. Backend riusa servizi Ainstein esistenti.

**Lemon Squeezy**: source of truth per billing/abbonamenti/licenze. Backend NON gestisce billing direttamente.

---

## 2. Architettura high-level

### Componenti del backend

```
seo-toolkit/api/editorial/
├── routes.php                          # Definizione endpoint REST
├── middleware/
│   ├── LicenseAuthMiddleware.php       # Valida X-License-Key + X-Site-Domain
│   ├── QuotaMiddleware.php             # Verifica quota azioni del tier
│   └── RateLimitMiddleware.php         # Anti-abuse (es. 60 req/min per license)
├── controllers/
│   ├── ActivationController.php        # POST /activate, POST /deactivate
│   ├── ContentBrainController.php      # CRUD content brain config
│   ├── KeywordResearchController.php   # POST /keywords/research
│   ├── EditorialPlanController.php     # CRUD piani editoriali
│   ├── ArticleController.php           # POST /articles/generate, SSE stream
│   ├── MetaTagController.php           # POST /meta/generate
│   ├── ImageController.php             # POST /images/generate
│   ├── InternalLinkController.php      # POST /links/suggest
│   ├── SubscriptionController.php      # GET /subscription/status
│   └── WebhookController.php           # POST /webhooks/lemonsqueezy
└── services/                           # Logica business plugin-specific
    ├── ContentBrainService.php         # Brand voice scanner + glossario
    ├── EditorialPlanService.php        # Genera calendario da topic+keyword
    ├── InternalLinkerService.php       # Algoritmo linking bidirezionale
    └── QuotaService.php                # Quota tracking e enforcement
```

### Componenti del plugin

```
plugins/ainstein-editorial/src/
├── plugin.php                          # Bootstrap WP plugin (header + autoload)
├── Includes/
│   ├── Plugin.php                      # Main class, singleton
│   ├── Activator.php                   # Hook activate (install DB options)
│   ├── Deactivator.php                 # Hook deactivate (cleanup)
│   ├── ApiClient.php                   # HTTP client verso backend (wp_remote_*)
│   ├── LicenseManager.php              # Gestione license key locale
│   ├── ContentBrainScanner.php         # Scansione articoli esistenti per brand voice
│   ├── WpPublisher.php                 # Crea wp_posts come draft
│   ├── InternalLinkInjector.php        # Inserisce link in post esistenti
│   └── CronManager.php                 # Pianifica auto-pilot weekly
├── Admin/
│   ├── AdminPages.php                  # Registra menu WP admin
│   ├── Pages/
│   │   ├── Dashboard.php               # Overview siti + stats
│   │   ├── Onboarding.php              # Wizard 5 domande post-activation
│   │   ├── EditorialPlan.php           # Visualizzazione + edit calendario
│   │   ├── Articles.php                # Lista articoli generati
│   │   ├── ContentBrain.php            # Edit brand voice, glossario
│   │   ├── Settings.php                # License key, preferenze
│   │   └── Billing.php                 # Embed Lemon Squeezy customer portal
│   └── Assets.php                      # Enqueue Tailwind + Alpine.js
├── Api/
│   └── (chiamate REST verso backend, in ApiClient.php)
├── Email/
│   ├── WeeklyDigest.php                # Email "ho scritto X questa settimana"
│   └── MonthlyReport.php               # Email mensile con stats
└── Utils/
    ├── Sanitizer.php                   # WP sanitization wrappers
    └── Logger.php                      # Log locale plugin (debug)
```

---

## 3. Stack tecnologico

### Backend
- **PHP 8.0+** (stesso runtime di Ainstein)
- **MySQL 8.0+** (stesso database di Ainstein, namespace `aied_*`)
- **Apache 2.4** con mod_rewrite per routing API
- **Cron Hetzner** per dispatcher job (riuso pattern Ainstein)
- **Server-Sent Events (SSE)** per streaming AI output

### Plugin WordPress
- **PHP 8.0+** (compatibile WordPress 6.0+)
- **Tailwind CSS** (compilato in CSS statico, no runtime)
- **Alpine.js 3.x** (interattività dichiarativa)
- **i18n** via `__()` standard WP (file .po/.mo in `languages/`)

### Provider esterni
- **AI**: Anthropic Claude (primario) + OpenAI (fallback) — via `AiService` Ainstein
- **SERP**: Serper.dev (primario) + SerpAPI/ValueSerp (fallback) — via `SerpApiService` Ainstein
- **Keyword volumes**: RapidAPI Keyword Insight — via `KeywordInsightService` Ainstein
- **Image generation**: Google Gemini (primario) — via `GeminiImageProvider` Ainstein
- **Web scraping**: Mozilla Readability — via `ScraperService` Ainstein
- **Payments & licenses**: Lemon Squeezy API

---

## 4. Database schema

Tutte le tabelle hanno prefisso `aied_` (Ainstein Editorial). Separazione totale dalle tabelle Ainstein.

### `aied_users`
Account utenti del plugin. Creati silenziosamente all'attivazione license.

```sql
CREATE TABLE aied_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    license_key VARCHAR(255) NOT NULL UNIQUE,
    lemon_squeezy_customer_id VARCHAR(255),
    lemon_squeezy_subscription_id VARCHAR(255),
    tier ENUM('starter', 'pro', 'business', 'agency') NOT NULL DEFAULT 'starter',
    subscription_status ENUM('active', 'past_due', 'cancelled', 'expired') DEFAULT 'active',
    subscription_renews_at TIMESTAMP NULL,
    locale VARCHAR(10) DEFAULT 'it',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NULL,
    INDEX idx_license (license_key),
    INDEX idx_lemon_subscription (lemon_squeezy_subscription_id)
);
```

### `aied_sites`
Siti WordPress collegati a un user. Limite N siti per tier (vedi `aied_users.tier`).

```sql
CREATE TABLE aied_sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    wp_version VARCHAR(20),
    plugin_version VARCHAR(20),
    api_token VARCHAR(255) NOT NULL UNIQUE,    -- JWT short-lived per chiamate plugin → backend
    api_token_expires_at TIMESTAMP NULL,
    status ENUM('active', 'paused', 'deactivated') DEFAULT 'active',
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    settings JSON,                              -- Preferenze utente (locale tone, audience, post freq)
    FOREIGN KEY (user_id) REFERENCES aied_users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_domain (user_id, domain),
    INDEX idx_token (api_token)
);
```

### `aied_actions_log`
Log consumo "azioni" per quota tracking. Ogni operazione conta 1+ azioni (vedi tier).

```sql
CREATE TABLE aied_actions_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('article', 'meta_tag', 'image', 'keyword_research', 'internal_link_scan') NOT NULL,
    cost_in_units INT NOT NULL DEFAULT 1,
    billing_period_start DATE NOT NULL,         -- per quota tracking mensile
    metadata JSON,                              -- es. keyword target, article_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES aied_users(id),
    FOREIGN KEY (site_id) REFERENCES aied_sites(id),
    INDEX idx_user_period (user_id, billing_period_start),
    INDEX idx_action_type (action_type)
);
```

### `aied_content_brain`
Configurazione "Content Brain" per sito: brand voice scanner output + glossario + editorial guidelines.

```sql
CREATE TABLE aied_content_brain (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL UNIQUE,
    brand_voice_summary TEXT,                   -- AI-generated da scansione
    brand_voice_examples JSON,                  -- 3-5 paragrafi esempio
    tone ENUM('professional', 'friendly', 'fun', 'authoritative', 'casual') DEFAULT 'friendly',
    target_audience TEXT,                       -- "Appassionati di vino, 30-60, italiani"
    site_topic TEXT,                            -- "Vendo vino artigianale italiano"
    glossary JSON,                              -- {brand_names: [], products: [], avoid_terms: []}
    editorial_guidelines JSON,                  -- {do: [], dont: [], emphasize: []}
    language VARCHAR(10) DEFAULT 'it',
    last_scan_at TIMESTAMP NULL,
    scanned_articles_count INT DEFAULT 0,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE
);
```

### `aied_editorial_plans`
Calendari editoriali. 1 sito può avere 1 piano attivo + storico.

```sql
CREATE TABLE aied_editorial_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    posts_per_month INT NOT NULL DEFAULT 4,
    auto_publish BOOLEAN DEFAULT FALSE,         -- opt-in v1.1
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    INDEX idx_site_status (site_id, status)
);
```

### `aied_editorial_items`
Singoli articoli pianificati nel calendario.

```sql
CREATE TABLE aied_editorial_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    keyword VARCHAR(500) NOT NULL,
    keyword_difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    monthly_volume INT DEFAULT 0,
    intent ENUM('informational', 'commercial', 'navigational', 'transactional') DEFAULT 'informational',
    proposed_title VARCHAR(500),
    proposed_outline JSON,
    scheduled_for DATE NOT NULL,
    status ENUM('pending', 'in_progress', 'generated', 'published', 'failed', 'skipped') DEFAULT 'pending',
    article_id BIGINT UNSIGNED NULL,            -- FK → aied_articles dopo generazione
    failure_reason TEXT NULL,
    FOREIGN KEY (plan_id) REFERENCES aied_editorial_plans(id) ON DELETE CASCADE,
    INDEX idx_plan_scheduled (plan_id, scheduled_for),
    INDEX idx_status (status)
);
```

### `aied_articles`
Articoli generati. Output AI + metadata + tracking pubblicazione WP.

```sql
CREATE TABLE aied_articles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    editorial_item_id BIGINT UNSIGNED NULL,     -- FK se generato da piano editoriale
    keyword VARCHAR(500) NOT NULL,
    title VARCHAR(500) NOT NULL,
    content LONGTEXT NOT NULL,                  -- HTML formatted
    word_count INT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    cover_image_url VARCHAR(1000),
    cover_image_attachment_id BIGINT NULL,      -- ID nel media library WP del sito
    wp_post_id BIGINT NULL,                     -- ID in wp_posts del sito utente
    wp_post_status VARCHAR(20),                 -- 'draft', 'publish', etc.
    serp_data JSON,                             -- top 10 SERP analizzati
    sources JSON,                               -- URL scraped per il brief
    brief JSON,                                 -- brief AI usato per generation
    quality_score INT NULL,                     -- 0-100 score AI-generated post-creation
    generation_time_seconds INT,
    ai_cost_eur DECIMAL(10,4),                  -- costo reale per analytics
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (editorial_item_id) REFERENCES aied_editorial_items(id) ON DELETE SET NULL,
    INDEX idx_site_created (site_id, created_at),
    INDEX idx_wp_post (wp_post_id)
);
```

### `aied_internal_links`
Tracking link inseriti automaticamente. Permette undo + audit.

```sql
CREATE TABLE aied_internal_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    from_article_id BIGINT UNSIGNED NOT NULL,   -- wp_posts.ID dell'articolo modificato
    to_article_id BIGINT UNSIGNED NOT NULL,     -- wp_posts.ID target del link
    anchor_text VARCHAR(500),
    context_snippet TEXT,                       -- frase circostante per debug/undo
    direction ENUM('forward', 'backward') NOT NULL,  -- nuovo→vecchio o vecchio→nuovo
    inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    removed_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    INDEX idx_site_articles (site_id, from_article_id, to_article_id)
);
```

### `aied_api_logs`
Log chiamate AI/SERP/external per debugging + analytics costi.

```sql
CREATE TABLE aied_api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    site_id BIGINT UNSIGNED NULL,
    provider VARCHAR(50),                       -- 'anthropic', 'serper', 'gemini', etc.
    endpoint VARCHAR(255),
    request_payload JSON,                       -- truncated se enorme
    response_status INT,
    response_summary TEXT,
    duration_ms INT,
    cost_eur DECIMAL(10,6),
    error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_provider (provider)
);
```

### `aied_email_log`
Log email inviate per debugging unsubscribe e deliverability.

```sql
CREATE TABLE aied_email_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('welcome', 'weekly_digest', 'monthly_report', 'license_recovery', 'quota_warning') NOT NULL,
    subject VARCHAR(500),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    opened_at TIMESTAMP NULL,
    INDEX idx_user_type (user_id, type)
);
```

---

## 5. Backend API contract

Base URL: `https://[dominio-prodotto-finale]/api/editorial/v1` (oppure `https://api.editorial.com/v1` se si decide di usare sottodominio API dedicato).

Auth: ogni request (eccetto `/activate`) richiede headers:
```
X-License-Key: <license_key dell'utente>
X-Site-Domain: <home_url() del sito WP>
X-Api-Token: <api_token short-lived ottenuto da /activate, refresh ogni 24h>
```

### Endpoints

#### Auth & Activation
```
POST   /activate
       Body: { license_key, domain, wp_version, plugin_version, admin_email }
       Response: { api_token, expires_in, tier, sites_remaining, user: { email } }

POST   /deactivate
       Body: { site_id }
       Response: { success, sites_freed }

POST   /refresh-token
       Body: { license_key, domain }
       Response: { api_token, expires_in }
```

#### Content Brain
```
GET    /content-brain
       Response: { brand_voice_summary, tone, target_audience, glossary, guidelines }

POST   /content-brain/scan
       Body: { article_urls: [], sample_size: 20 }
       Response: { scan_job_id }
       SSE: /content-brain/scan/{scan_job_id}/stream
            → events: scanning, analyzing, completed
            → final event: { brand_voice_summary, examples, tone_detected, glossary }

PUT    /content-brain
       Body: { tone, target_audience, site_topic, glossary, editorial_guidelines }
       Response: { updated_at }
```

#### Keyword Research
```
POST   /keywords/research
       Body: { topic, audience, language, count: 30 }
       SSE stream: /keywords/research/{job_id}/stream
            → events: searching, analyzing_serp, scoring, completed
            → final event: { keywords: [{ keyword, volume, difficulty, intent, opportunity_score }] }
       Cost: 5 azioni
```

#### Editorial Plan
```
GET    /editorial-plans
       Response: { plans: [...], active_plan_id }

POST   /editorial-plans
       Body: { name, keywords: [], posts_per_month, starts_at, ends_at }
       Response: { plan_id, items: [...] }

PUT    /editorial-plans/{id}
       Body: { items: [{ id, status, scheduled_for, ... }] }

POST   /editorial-plans/{id}/activate
       Response: { active: true, next_article_scheduled_for }
```

#### Article generation
```
POST   /articles/generate
       Body: { keyword, intent?, word_count?, custom_brief? }
       SSE stream: /articles/generate/{job_id}/stream
            → events:
                - serp_extracted
                - sources_scraped (progress: 1/6, 2/6, ...)
                - brief_built
                - article_writing (streaming text chunks)
                - article_completed
                - meta_generated
                - image_generated
                - internal_links_suggested
            → final event: { article_id, content, meta, image_url, suggested_links }
       Cost: 10 azioni (1 articolo "completo")

GET    /articles
       Query: ?site_id, ?status, ?limit, ?offset
       Response: { articles: [...], total }

GET    /articles/{id}
       Response: { article: { ...full data } }

POST   /articles/{id}/publish-to-wp
       Body: { auto_publish: false }       # false = draft, true = publish (v1.1)
       Response: { wp_post_id, status: 'draft' }

POST   /articles/{id}/regenerate
       Body: { section: 'all'|'intro'|'conclusion', notes }
       Cost: 5 azioni (regeneration parziale costa meno)
```

#### Internal links
```
POST   /links/suggest
       Body: { article_id }
       Response: {
         forward_links: [{ to_post_id, anchor, snippet, position_hint }],
         backward_links: [{ from_post_id, anchor, snippet, position_hint, modification_preview }]
       }

POST   /links/apply
       Body: { article_id, forward: [...accepted_ids], backward: [...accepted_ids] }
       Response: { applied: { forward: N, backward: N }, modified_post_ids: [...] }
       Cost: 1 azione per articolo
```

#### Subscription & quota
```
GET    /subscription/status
       Response: {
         tier, subscription_status, renews_at,
         quota: { articles_used: 3, articles_total: 12, ... },
         topup_balance: { articles: 0, ... }
       }

POST   /subscription/portal-url
       Response: { url: 'https://lemonsqueezy.../customer-portal/...' }
```

#### Webhooks (interno, da Lemon Squeezy)
```
POST   /webhooks/lemonsqueezy
       Signature header validation
       Handles: subscription_created, subscription_updated, subscription_cancelled,
                order_created (top-up purchase), license_key_created
```

---

## 6. Plugin WordPress — struttura

### Plugin header (`src/plugin.php`)

```php
<?php
/**
 * Plugin Name: Ainstein Editorial
 * Plugin URI: https://[dominio-prodotto].com
 * Description: Il tuo SEO Pro autonomo per WordPress. Trova keyword, scrive articoli, fa internal linking. Tutto da solo.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Ainstein
 * Author URI: https://ainstein.it
 * License: GPLv2 or later
 * Text Domain: ainstein-editorial
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('AIED_VERSION', '1.0.0');
define('AIED_PATH', plugin_dir_path(__FILE__));
define('AIED_URL', plugin_dir_url(__FILE__));
define('AIED_API_BASE', 'https://[dominio-prodotto].com/api/editorial/v1');

require_once AIED_PATH . 'vendor/autoload.php';  // composer per dipendenze

register_activation_hook(__FILE__, ['Ainstein\Editorial\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Ainstein\Editorial\Deactivator', 'deactivate']);

add_action('plugins_loaded', function () {
    \Ainstein\Editorial\Plugin::instance()->run();
});
```

### Menu admin (struttura)

```
WP Admin sidebar:
├── 🌟 Ainstein Editorial               (top-level menu)
│   ├── Dashboard                       (overview, stats, prossimi articoli)
│   ├── Calendario editoriale           (visualizza piano)
│   ├── Articoli                        (lista, edit, publish)
│   ├── Content Brain                   (brand voice, glossario, guidelines)
│   ├── Idee keyword                    (browse keyword research history)
│   ├── Statistiche                     (quota, articoli generati, link inseriti)
│   └── Impostazioni                    (license, preferenze, billing portal link)
```

### wp_options usate dal plugin

Tutte con prefisso `aied_`:

- `aied_license_key` — string
- `aied_api_token` — string (refresh ogni 24h)
- `aied_api_token_expires_at` — int timestamp
- `aied_user_email` — string
- `aied_tier` — string ('starter'|'pro'|'business'|'agency')
- `aied_settings` — array serialized (preferenze utente)
- `aied_onboarding_completed` — bool
- `aied_content_brain_last_scan` — int timestamp
- `aied_cron_last_run` — int timestamp

---

## 7. Flow funzionali principali

### 7.1 First-time activation flow

```
1. Utente compra plugin su [dominio-prodotto].com
   ↓ Lemon Squeezy checkout
2. Riceve email con license key + link download plugin .zip
3. WP Admin → Plugins → Upload plugin → installa → attiva
4. Plugin redirect a admin page "Ainstein Editorial → Setup"
5. Utente incolla license key + click "Attiva"
6. Plugin POST /api/editorial/v1/activate
   Backend:
   - Valida key con Lemon Squeezy License API
   - Crea/aggiorna aied_users
   - Crea aied_sites con api_token
   - Returns api_token + tier info
7. Plugin salva api_token in wp_options
8. Redirect a Onboarding Wizard (5 domande)
```

### 7.2 Onboarding wizard flow

```
Step 1: "Di cosa parla il tuo sito?"
   - Textarea libera + auto-suggestion da scan articoli esistenti
Step 2: "Chi sono i tuoi clienti?"
   - Textarea libera
Step 3: "Che tono usi?"
   - 5 card visive con esempi: Professionale / Amichevole / Divertente / Autorevole / Casual
Step 4: "Quanti articoli al mese?"
   - Slider 1-30 con preview "circa 1 a settimana"
   - Hint: rispetto tier corrente (es. "Il tuo piano Starter include 4 articoli/mese")
Step 5: "Cosa NON pubblicare mai sul tuo blog?"
   - Textarea + esempi ("contenuti politici, recensioni competitor, ...")

Click "Inizia magia":
   POST /content-brain/scan → SSE stream → progress bar visuale ("Sto leggendo i tuoi articoli...")
   Dopo completion: redirect a "Idee keyword" con prima ricerca automatica
```

### 7.3 Keyword research → editorial plan flow

```
1. Admin page "Idee keyword" → click "Genera nuove idee"
2. POST /keywords/research con topic + audience da content brain
3. SSE stream → UI mostra "Sto cercando opportunità per te..."
   Eventi visuali:
   - "Analizzando le tendenze..." (animazione globe)
   - "Confrontando con i tuoi concorrenti..." (animazione bar chart)
   - "Selezionando le migliori..." (animazione filter)
4. Risultato: lista 30 keyword con badge difficulty (🟢🟡🔴) + volume in linguaggio umano
   ("1.200 persone al mese" invece di "1200 search volume")
5. User clicca toggle su keyword da includere → "Genera calendario"
6. POST /editorial-plans con keyword + posts_per_month → backend crea plan + items
7. UI mostra calendario visuale (mese view) con titoli proposti per ogni data
8. User clicca "Approva e attiva"
9. Plan attivato → cron settimanale prende il primo item pending
```

### 7.4 Article generation flow (manuale)

```
1. Admin page "Articoli" → click "Genera ora"
2. Form: keyword + (optional) brief custom + word count
3. POST /articles/generate → ritorna job_id
4. UI apre modal full-screen "Generazione in corso"
5. EventSource si collega a /articles/generate/{job_id}/stream
6. UI mostra steps animati:
   ✓ Sto leggendo i primi 10 risultati Google...
   ✓ Sto analizzando i contenuti dei competitor...
   ✓ Sto costruendo la traccia dell'articolo...
   ⏳ Sto scrivendo... (testo appare carattere per carattere — EFFETTO WOW)
   ✓ Sto generando i meta tag...
   ✓ Sto creando l'immagine di copertina...
   ✓ Sto suggerendo i collegamenti interni...
7. Completion: redirect a /articles/{id} con preview articolo
8. User click "Pubblica come bozza" → POST /articles/{id}/publish-to-wp
9. wp_insert_post con status='draft' → redirect a editor WP
10. (opzionale) User clicca "Mostra collegamenti suggeriti" → modal con accept/reject
```

### 7.5 Auto-pilot weekly flow

```
WP Cron settimanale (ogni lunedì 08:00 ora server):

1. Plugin invoca wp_remote_post → backend hook /editorial-plans/auto-tick
   (con api_token per autenticazione)
2. Backend per ogni sito con piano attivo:
   a. Trova prossimo aied_editorial_items.status='pending' con scheduled_for <= today
   b. Verifica quota disponibile (aied_actions_log conteggio mensile)
   c. Se OK:
      - Marca status='in_progress'
      - Avvia generazione articolo (stesso flow di 7.4 ma async)
      - Salva article in aied_articles
      - Crea wp_post via REST WP API (auth con api_token + capability application_password)
      - Suggerisce internal links → applica forward + backward auto (no review se opt-in)
      - Marca status='generated', aggiorna article_id
   d. Email "Ho scritto X questa settimana, è in bozza" a aied_users.email
3. Plugin riceve response → log success/failure
4. Se più articoli pendenti per stesso sito (es. 2/settimana): tutti processati in sequenza
```

---

## 8. Content Brain — specifica

### Obiettivo
Far apprendere all'AI il tono, vocabolario, e identità del sito utente prima di generare contenuti, per output coerente con la voce del brand.

### Componenti

#### 8.1 Brand Voice Scanner

**Input**: lista URL articoli esistenti del sito (da sitemap o `wp_posts WHERE post_status='publish'`).

**Processo**:
1. Sample N articoli (default 20, sufficiente per pattern recognition)
2. Per ogni articolo: `ScraperService::scrape()` → estrae title + content + headings
3. Aggregazione: invio bulk a Claude con prompt:
   ```
   Analizza questi 20 articoli del sito [topic]. Estrai:
   1. Tono dominante (professionale/amichevole/divertente/autorevole/casual)
   2. Lunghezza media frasi (corte/medie/lunghe)
   3. Uso di domande retoriche, esclamazioni, prima/seconda persona
   4. Vocabolario tipico (parole ricorrenti, evita banali)
   5. Struttura tipica articoli (heading style, paragrafi corti/lunghi, uso liste)
   6. Brand names e prodotti menzionati spesso
   7. 3 paragrafi di esempio rappresentativi della voice
   
   Output JSON: { tone, sentence_style, voice_traits, vocabulary, structure, brand_names, examples }
   ```
4. Salvataggio in `aied_content_brain.brand_voice_summary` + `brand_voice_examples`

**Quando ri-eseguire**: opzionale manualmente, e auto ogni 6 mesi se l'utente ha pubblicato 10+ articoli nuovi dall'ultimo scan.

#### 8.2 Glossario

**Input manuale** (utente compila form) + **auto-suggestion** dal brand voice scanner.

**Struttura JSON**:
```json
{
  "brand_names": ["Vinicola Rossi", "Cantina XYZ"],
  "products": ["Chianti Riserva", "Brunello 2020"],
  "people": ["Giovanni Rossi", "Marco Bianchi (sommelier)"],
  "avoid_terms": ["competitor X", "espressione volgare"],
  "preferred_terms": {"vino italiano": "vino artigianale italiano"}
}
```

**Uso in generation**: ogni prompt AI include il glossario come constraint:
> "Usa SEMPRE 'vino artigianale italiano' invece di 'vino italiano'. Menziona Giovanni Rossi quando rilevante. NON menzionare mai competitor X."

#### 8.3 Editorial Guidelines

**Input manuale** (3 textarea brevi).

**Struttura JSON**:
```json
{
  "do": ["Enfatizza la tradizione familiare", "Cita esempi concreti di abbinamenti"],
  "dont": ["Non parlare di politica", "Non recensire negativamente altri vini"],
  "emphasize": ["Sostenibilità", "Made in Italy"]
}
```

**Uso**: appended ai prompt AI come system message constraints.

---

## 9. Auto-pilot — architettura

### Trigger
WP Cron event `aied_weekly_autopilot` schedulato il giorno della settimana scelto dall'utente (default lunedì 08:00 ora server).

### Implementation pattern

```php
// CronManager.php
register_activation_hook(..., function () {
    if (!wp_next_scheduled('aied_weekly_autopilot')) {
        wp_schedule_event(
            strtotime('next monday 08:00'),
            'weekly',
            'aied_weekly_autopilot'
        );
    }
});

add_action('aied_weekly_autopilot', ['Ainstein\Editorial\CronManager', 'runAutopilot']);

class CronManager {
    public static function runAutopilot() {
        $apiClient = new ApiClient();
        $response = $apiClient->post('/editorial-plans/auto-tick', [
            'domain' => home_url(),
            'date' => current_time('Y-m-d')
        ]);
        
        if ($response['success'] && !empty($response['articles_generated'])) {
            foreach ($response['articles_generated'] as $article) {
                self::createWpDraft($article);
            }
            self::sendWeeklyDigest($response);
        }
        update_option('aied_cron_last_run', time());
    }
}
```

### Backup: WP Cron unreliability mitigation

WP Cron è triggered da page views. Sui siti low-traffic non parte → soluzioni:
1. Plugin documenta come settare server-side cron (`wget` su wp-cron.php) — alternativa pro
2. **Fallback robusto**: backend Ainstein cron (gestito da noi) può chiamare endpoint `/editorial-plans/auto-tick-server-side` se sa che il sito utente è inattivo da > 7 giorni e ha plan attivo. Da implementare con cautela (rispetto privacy + opt-in).

### Quota enforcement

Prima di ogni generation auto-pilot, controllo `aied_actions_log` mese corrente:
```php
$used = QuotaService::getArticlesUsedThisMonth($userId);
$tier_limit = TierConfig::getArticlesLimit($user->tier);
$topup_balance = QuotaService::getTopupBalance($userId, 'articles');

if ($used >= $tier_limit && $topup_balance <= 0) {
    // Pausa generation, notifica via email "quota esaurita, fai top-up"
    // Marca editorial_items.status='skipped' (riprese mese successivo)
    return;
}
```

---

## 10. Internal linking algorithm

### Obiettivo
Quando AI genera articolo nuovo, inserire link contestuali:
- **Forward**: dal nuovo articolo verso articoli esistenti rilevanti
- **Backward**: da articoli esistenti rilevanti verso il nuovo articolo

### Algoritmo

#### Fase 1: candidate selection
1. Estrai 20 keyword/entity principali dal nuovo articolo (NLP basic via Claude)
2. Query articoli esistenti del sito: full-text search match su title + content
3. Score ogni candidato:
   - Match keyword nel title: +5
   - Match keyword nel content (≥3 occorrenze): +3
   - Recency (post pubblicato negli ultimi 12 mesi): +1
   - Authority (post con più commenti/link inbound): +2
4. Top 10 candidati → step 2

#### Fase 2: contextual placement (forward links)
Per ogni candidato top 10:
1. Trova nel testo del nuovo articolo la frase più correlata al candidato
2. Verifica che la frase contenga uno spunto naturale per linkare (no link su parola random)
3. Genera anchor text contestualmente (max 4-6 parole, no "click here", no keyword-stuff)
4. Inserisci `<a href="{url_candidato}" data-aied-link="forward">{anchor}</a>`

Massimo **5 forward links per articolo** (oltre = spam SEO).

#### Fase 3: backward links (modifiche articoli vecchi)
Per ogni candidato top 5:
1. Verifica che il candidato non abbia già 3+ outbound link interni (no link-stuffing)
2. Trova nel testo del candidato la frase più correlata al nuovo articolo
3. Genera anchor text + posizione
4. **PRIMA di modificare**: salva snapshot del post in `wp_postmeta` (`_aied_pre_backlink_content_<timestamp>`)
5. Inserisci link con tag `data-aied-link="backward"` per future identificazione
6. `wp_update_post` con nuovo contenuto

Massimo **1 backward link per articolo vecchio** + max **3 articoli vecchi modificati per ogni nuovo articolo** (totale: max 3 modifiche per cycle).

### Sicurezza & rollback

- Tutti i link inseriti taggati con `data-aied-link` per ricognizione
- Tabella `aied_internal_links` traccia ogni link (snapshot context_snippet)
- UI plugin: "Articoli modificati questa settimana" con bottone "Rimuovi link" per ogni
- Articoli opt-out: utente può marcare `aied_no_modify=true` in `wp_postmeta` → plugin skipped

---

## 11. Auth & License flow

### Plugin → Backend authentication

**First activation:**
```
1. User incolla license_key + admin email
2. Plugin POST /activate { license_key, domain, wp_version, plugin_version, admin_email }
3. Backend:
   a. Chiama Lemon Squeezy License API: GET /licenses/{key}/instances
   b. Verifica subscription_status='active' o 'on_trial'
   c. Verifica instances_count < tier_limit (1 per Starter, 3 per Pro, etc.)
   d. Se OK: crea/find aied_users + aied_sites
   e. Lemon Squeezy POST /licenses/{key}/activate con instance_name=domain
   f. Genera api_token JWT (24h expiry, payload: user_id + site_id)
   g. Returns { api_token, expires_in, tier, ... }
4. Plugin salva in wp_options
```

**Each request after:**
```
1. Plugin invia X-Api-Token + X-Site-Domain in header
2. Backend middleware:
   a. Decode JWT → verifica scadenza
   b. Se scaduto: response 401 → plugin auto-refresh via /refresh-token
   c. Verifica X-Site-Domain match con JWT payload (no token leak)
   d. Load user + site from DB
   e. Procedi con controller
```

**Refresh token:**
```
- Plugin background process (su admin_init) controlla expires_at
- Se < 1h da expiry: POST /refresh-token con license_key + domain
- Backend valida license ancora attiva, emette nuovo JWT
```

### Lemon Squeezy webhook handling

Eventi critici da gestire:
- `subscription_created` — nuovo signup, ma activation reale via plugin license key
- `subscription_updated` — tier upgrade/downgrade → update `aied_users.tier`
- `subscription_cancelled` — flag user, mantieni active fino a end_period
- `subscription_payment_failed` — alert email, grace period 7gg
- `subscription_resumed` — riattivazione
- `order_created` (top-up) — credita azioni in `aied_users.topup_balance_articles`

Webhook URL: `/api/editorial/v1/webhooks/lemonsqueezy`. Signature validation via shared secret.

---

## 12. UX/UI Design System

### Filosofia
> "Sembra un prodotto da $200/mo anche se costa €19."

### Palette colori

```css
/* Primary brand */
--aied-primary: #6366F1;        /* Indigo 500 — accent buttons, links */
--aied-primary-dark: #4F46E5;
--aied-primary-light: #818CF8;

/* Neutral (sfondi, testo) */
--aied-slate-50: #F8FAFC;
--aied-slate-100: #F1F5F9;
--aied-slate-200: #E2E8F0;
--aied-slate-700: #334155;
--aied-slate-800: #1E293B;
--aied-slate-900: #0F172A;

/* Status */
--aied-success: #10B981;        /* Emerald */
--aied-warning: #F59E0B;        /* Amber */
--aied-danger: #EF4444;          /* Red */
--aied-info: #3B82F6;            /* Blue */

/* Dark mode (auto da WP user preferences) */
[data-theme="dark"] { /* invert appropriately */ }
```

### Typography

- **Font primario**: Inter (Google Fonts loaded once, fallback system)
- **Font monospace**: JetBrains Mono (per code/keys)
- **Scale**:
  - H1: 30px / 36px line-height / 700 weight
  - H2: 24px / 32px / 600
  - H3: 20px / 28px / 600
  - Body: 15px / 24px / 400
  - Small: 13px / 20px / 400

### Componenti base

Riusati da Ainstein con prefisso CSS `.aied-*` per evitare conflitti WP:

- `.aied-btn-primary`, `.aied-btn-secondary`, `.aied-btn-ghost`
- `.aied-card` (rounded-xl, shadow-sm, border-slate-200)
- `.aied-input`, `.aied-textarea`, `.aied-select`
- `.aied-table` (rounded-xl, px-4 py-3, dark:bg-slate-700/50)
- `.aied-badge-success/warning/danger/info`
- `.aied-modal`, `.aied-dropdown` (Alpine.js)
- `.aied-skeleton` (loading shimmer)
- `.aied-progress` (gauge animato)

### "WOW moments" da costruire (priorità design)

1. **Onboarding wizard animato** — step transitions con framer-motion-like (Alpine.js + CSS transitions). Background gradient subtle che cambia per ogni step.

2. **Streaming AI output** — testo articolo appare carattere per carattere come ChatGPT, con cursor blinking. Effetto "magico" rispetto a spinner statici dei competitor.

3. **SERP preview interattiva** — top 10 SERP result mostrati come card; click su una card → tooltip con headings scrapate. Trasparenza sul processo AI.

4. **Brief approval con editor** — brief AI-generated mostrato in editor tipo Notion (Alpine.js drag-drop sections), utente può approvare/modificare/aggiungere sezioni.

5. **Quality score gauge** — gauge animato (SVG circle stroke-dasharray) tipo PageSpeed Insights con punteggio SEO/Readability/Brand fit.

6. **Empty state illustrati** — illustrazioni SVG custom (no stock images) per "Nessun articolo ancora", "Calendario vuoto", etc. Microcopy con personalità ("Tutto silenzioso qui... Vuoi che iniziamo?").

7. **Toast notifications soft** — slide-in dall'alto-destra, auto-dismiss 5s, stack se multiple. No alert WP nativi (anni 2010).

8. **Calendario editoriale drag-drop** — Alpine.js + Sortable.js. Trascini articoli tra date. Smooth.

9. **Confetti animation** quando primo articolo generato — gratificazione momento. Solo prima volta (poi non spam).

10. **Dark mode pixel-perfect** — non solo "scuro", colori dark-mode-first (slate-800/900 con contrast WCAG AAA).

### Microcopy guidelines

- Tono coerente con brand voice italiano amichevole-professionale
- No "click qui", no jargon SEO se non spiegato
- Error messages: cosa è successo + cosa fare ("Quota esaurita. Compra un boost pack o aspetta il rinnovo del 1° del mese.")
- Success messages: celebra ("✓ Articolo pronto! È in bozza, ti aspetta nel tuo editor WordPress.")

---

## 13. Refactoring backend Ainstein

### Cosa va modificato in `seo-toolkit/`

#### 13.1 Nuova directory `api/editorial/`
Creazione da zero, no impatto su codice esistente. Routes registrate in `public/index.php` come route group.

#### 13.2 Nuove tabelle DB
Migration in `database/migrations/2026-06-XX-aied-schema.sql`. Zero conflitto con tabelle esistenti.

#### 13.3 AiService — niente cambiamenti
Riuso 100% as-is. Logging cost via `ApiLoggerService` esistente.

#### 13.4 ScraperService — niente cambiamenti
Riuso 100%.

#### 13.5 SerpApiService — niente cambiamenti
Riuso 100%, con possibile aggiunta di fallback ValueSerp se necessario.

#### 13.6 BriefBuilderService — minor refactor
Estrarre logica brief generation in metodo standalone callable dal nostro contesto. Probabile aggiunta parametro `$contentBrain` per injection brand voice constraints.

#### 13.7 ArticleGeneratorService — minor refactor
Stessa cosa: parametro `$contentBrain` per inject brand voice + glossary nei prompt.

#### 13.8 KeywordInsightService — niente cambiamenti
Riuso 100%.

#### 13.9 Nuovo service: ContentBrainService
Da creare in `api/editorial/services/`. Standalone, non interferisce con codice Ainstein.

#### 13.10 Nuovo service: InternalLinkerService
Da creare in `api/editorial/services/`. Algoritmo internal linking bidirezionale (vedi §10).

#### 13.11 Email infrastructure
Riuso `EmailService` Ainstein. Aggiunti template specifici plugin in `shared/views/emails/editorial/`.

### Impatto stimato su Ainstein

- **Tabelle modificate**: 0 (solo nuove)
- **Servizi modificati**: 2 (BriefBuilder + ArticleGenerator, aggiunta param opzionale `$contentBrain`)
- **Routes aggiunte**: ~20 endpoint sotto `/api/editorial/*`
- **Rischio rotture**: BASSO (cambi additivi, backward compatible)

---

## 14. Error handling & monitoring

### Plugin side (utente)
- **Errori AI**: messaggio user-friendly + bottone "Riprova" + email log
- **Errori network**: retry con exponential backoff (3 tentativi) + offline indicator
- **Quota esaurita**: modal con CTA "Compra boost pack" o "Aspetta rinnovo"
- **License invalid**: modal con CTA "Verifica sul portale clienti" + magic link

### Backend side
- **API rate limiting**: 60 req/min per license key (anti-abuse, mitiga plugin compromessi)
- **AI quota dei provider**: fallback Claude → OpenAI gestito da `AiService` (già esistente)
- **SERP quota**: fallback Serper → SerpAPI → ValueSerp
- **Image generation fail**: skip image (article rimane senza cover), notifica plugin
- **WP REST API fail (per pubblicazione)**: retry 2x, poi notifica utente "Salvato articolo, ma errore creazione draft in WP — clicca per provare manualmente"

### Monitoring & alerting
- **`aied_api_logs`** popolato per ogni request (analitica costi + debug)
- **Slack webhook** (notifiche admin Ainstein): error rate > 5%, AI cost > €X/giorno, signup spike
- **Email weekly** all'admin Ainstein: report numero utenti attivi, generation totale, costi AI vs revenue
- **Sentry** (opzionale v1.1): exception tracking lato backend

### Definizione "fallimento articolo"
Se generation fail dopo 3 retry → `aied_editorial_items.status='failed'` + `failure_reason` + notifica utente "Ho avuto difficoltà su questo articolo, riprova dalla dashboard". NON conta come azione consumata.

---

## 15. Testing strategy

### Unit tests (backend)
- **PHPUnit** suite in `seo-toolkit/tests/editorial/`
- Coverage target: 70%+ su services (ContentBrainService, InternalLinkerService, QuotaService)
- Mock per `AiService`, `SerpApiService`, Lemon Squeezy API

### Integration tests (backend)
- Test endpoint REST end-to-end con database test
- Test webhook handling (mock Lemon Squeezy payloads)

### Plugin tests (WordPress)
- **WP_UnitTestCase** per classi PHP
- **Plugin Check** (tool ufficiale WP marketplace) per security/standards compliance
- Test attivazione/disattivazione plugin su WP 6.0, 6.1, 6.2, ..., latest

### Manual QA checklist
- Generation flow end-to-end su 3 siti diversi (wp 6.0, 6.4, latest)
- Test su multisite WP
- Test conflict con plugin popolari (Yoast, RankMath, WP Rocket, Elementor)
- Test su PHP 8.0, 8.1, 8.2, 8.3

### Beta testing
- 30 utenti dalla waitlist, free 30gg, NDA leggero
- Survey post-uso settimanale: pain points, "WOW moments", churn risk

---

## 16. Deployment & operations

### Backend deployment
- Stessi Hetzner VPS di Ainstein (riuso infra)
- Migration applicate via `mysql < migration.sql` (riuso pattern Ainstein)
- Zero downtime deploy (PHP-FPM reload, no DB schema breaking)
- Rollback strategy: git revert + reverse migration (preparato per ogni release)

### Plugin distribution
- Build via `build.sh`:
  1. Compila Tailwind CSS in `assets/css/admin.min.css`
  2. Minify Alpine.js components
  3. Genera i18n .mo files da .po
  4. `composer install --no-dev --optimize-autoloader`
  5. Zip della cartella `plugins/ainstein-editorial/` (escluse `tests/`, `docs/`, `node_modules/`)
  6. Output: `ainstein-editorial-vX.Y.Z.zip`
- Hosting download: sito prodotto `[dominio].com/download/latest` (auth via license key valida)
- Auto-update: plugin si auto-aggiorna usando custom WP plugin update API endpoint (`/api/editorial/v1/plugin/update-check`)

### Logging produzione
- `aied_api_logs` rotazione daily (cron `cron/cleanup-aied-logs.php`, retention 90gg)
- Web server log Apache: rotazione standard
- AI provider logs: già gestiti da `ApiLoggerService` Ainstein

### Backup
- Riuso backup quotidiano Ainstein (include tutte tabelle `aied_*`)
- Retention 7 giorni come Ainstein

### Cron jobs (server-side)
```cron
# /etc/cron.d/ainstein-editorial (su Hetzner)

# Auto-pilot dispatcher per siti con WP Cron unreliable
0 9 * * 1   ainstein  /usr/bin/php /var/www/ainstein.it/public_html/cron/editorial-autopilot-dispatcher.php >> /var/log/ainstein/editorial-cron.log

# Quota reset mensile
5 0 1 * *   ainstein  /usr/bin/php /var/www/ainstein.it/public_html/cron/editorial-quota-reset.php >> /var/log/ainstein/editorial-cron.log

# Cleanup vecchi logs
0 3 * * *   ainstein  /usr/bin/php /var/www/ainstein.it/public_html/cron/cleanup-aied-logs.php >> /var/log/ainstein/cleanup.log

# Weekly digest email
0 8 * * 1   ainstein  /usr/bin/php /var/www/ainstein.it/public_html/cron/editorial-weekly-digest.php >> /var/log/ainstein/editorial-cron.log

# Monthly report email
0 9 1 * *   ainstein  /usr/bin/php /var/www/ainstein.it/public_html/cron/editorial-monthly-report.php >> /var/log/ainstein/editorial-cron.log
```

---

## 17. Roadmap v1.1+

Feature pianificate post-MVP (non incluse nel MVP per scope discipline):

### v1.1 (1-2 mesi post-launch)
- **GSC integration**: OAuth Google + low-hanging fruit detection + closed-loop ROI dashboard
- **Auto-publish opt-in**: post supervisione utente primi 5 articoli, poi sblocco
- **Schedule pubblicazione**: scegli giorno/ora per ogni articolo del calendario
- **Editor inline preview**: invece di redirect a editor WP, edit articolo dentro plugin

### v1.2 (3-4 mesi post-launch)
- **Gutenberg sidebar block "AI Assist"**: genera intro/sezione/conclusione contestuali mentre scrivi un post manualmente
- **Multi-language support**: generation in EN, ES, FR, DE (i18n già pronto, basta sbloccare)
- **Affiliate program**: per agency che portano clienti, 20% recurring

### v2.0 (6+ mesi)
- **Multi-CMS expansion**: lancio plugin per Shopify (article + product descriptions), PrestaShop, Magento — riuso connettori già in `content-creator`
- **API pubblica**: per agency che vogliono integrare con loro workflow
- **White-label completo**: agency tier può rebrandare il plugin per clienti (interfaccia "powered by [agency]")

### v3.0+ (1+ anno)
- **Video script generation**: short videos per Instagram/TikTok/YouTube Shorts
- **Newsletter integration**: Mailchimp/MailerLite — articoli → newsletter automatica
- **Repurposing automatico**: articolo lungo → post social media + email + video script

---

*Fine design tecnico. Per implementation step-by-step → `roadmap.md`.*
