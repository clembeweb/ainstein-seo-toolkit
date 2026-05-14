# M1 — Foundation

> **Status**: 📋 Spec scritta, attende approvazione cliente
> **Effort stimato**: 35-50 ore (~1-1.5 settimane full-time)
> **Dipendenze**: Nessuna (è la prima milestone)
> **Sblocca**: Tutte le milestone successive (M2-M6)

---

## 1. Overview

### Cosa costruiamo in M1
Lo **scheletro completo** del sistema su cui poggerà tutto il resto. Niente UX visibile, niente AI generation. Solo:
- Tabelle DB pronte e popolate (test data)
- Endpoint API skeleton funzionanti
- Plugin WP che si installa, attiva, valida license, parla con backend
- Account Lemon Squeezy configurato in test mode con tier + license keys
- Build script che produce zip distribuibile

### Perché è la prima milestone
Tutte le feature successive (Content Brain, Article Generation, Auto-pilot, etc.) richiedono che il plugin sia **già attivabile e autenticato col backend**. M1 sblocca tutto.

### Cosa M1 NON include
- ❌ Generazione AI articoli (M3)
- ❌ Content Brain (M2)
- ❌ Onboarding wizard utente (M2)
- ❌ Keyword research (M4)
- ❌ Auto-pilot cron (M4)
- ❌ Internal linking (M5)
- ❌ UX polish (sparso M3-M6)
- ❌ Email notifications (M6)

L'utente che installa il plugin dopo M1 vede: "License attivata ✓, dashboard vuota". E basta. Non è prodotto-utile-per-lui ancora, è scheletro per noi.

---

## 2. Spec tecnica

### 2.1 Database schema (9 tabelle `aied_*`)

Migration file: `seo-toolkit/database/migrations/2026-05-15-aied-schema.sql`

Tutte le tabelle definite in `design.md` §4. Riassunto qui per referenza rapida:

| Tabella | Scopo | Righe stimate (1k utenti) |
|---------|-------|---------------------------|
| `aied_users` | Account plugin | 1.000 |
| `aied_sites` | Siti WP collegati (1-50 per user) | 5.000 |
| `aied_actions_log` | Log consumo azioni mensili | 50.000/mese |
| `aied_content_brain` | Brand voice + glossary per sito | 5.000 |
| `aied_editorial_plans` | Calendari editoriali | 5.000 |
| `aied_editorial_items` | Articoli pianificati | 50.000 |
| `aied_articles` | Output AI generato | 50.000 |
| `aied_internal_links` | Link inseriti per audit | 200.000 |
| `aied_api_logs` | Log chiamate AI/SERP | 500.000/mese (rotazione) |

**Storage stimato a 1.000 utenti attivi**: ~5GB DB. Sostenibile su MySQL Hetzner attuale.

**Indici critici**:
- `aied_users.license_key` UNIQUE (lookup ad ogni request)
- `aied_sites.api_token` UNIQUE (auth)
- `aied_actions_log (user_id, billing_period_start)` (quota check ogni generation)
- `aied_articles (site_id, created_at)` (dashboard listing)

### 2.2 API skeleton

Cartella: `seo-toolkit/api/editorial/`

Struttura:
```
api/editorial/
├── routes.php                          # Tutti gli endpoint (anche stub)
├── bootstrap.php                       # Inclusione middleware, autoload
├── Middleware/
│   ├── LicenseAuthMiddleware.php       # Valida X-License-Key + carica user/site
│   ├── QuotaMiddleware.php             # Verifica quota (stub in M1, completo in M3)
│   └── RateLimitMiddleware.php         # 60 req/min per license
├── Controllers/
│   ├── BaseController.php              # Response helpers (jsonOk, jsonError, sse)
│   ├── ActivationController.php        # POST /activate, /deactivate, /refresh-token (M1 IMPL)
│   ├── ContentBrainController.php      # Stub M1, full M2
│   ├── ArticleController.php           # Stub M1, full M3
│   ├── KeywordResearchController.php   # Stub M1, full M4
│   ├── EditorialPlanController.php     # Stub M1, full M4
│   ├── InternalLinkController.php      # Stub M1, full M5
│   ├── SubscriptionController.php      # GET /status (M1 IMPL base), portal (M6)
│   └── WebhookController.php           # POST /webhooks/lemonsqueezy (M1 IMPL parziale)
├── Services/
│   ├── LicenseManager.php              # Lemon Squeezy API wrapper
│   ├── JwtService.php                  # Issue + verify api_token
│   └── QuotaService.php                # Stub M1 (count actions), enforcement M3
└── Lib/
    └── LemonSqueezyClient.php          # HTTP client + signature verify webhook
```

Endpoint **completamente implementati in M1**:
- `POST /api/editorial/v1/activate`
- `POST /api/editorial/v1/deactivate`
- `POST /api/editorial/v1/refresh-token`
- `GET /api/editorial/v1/subscription/status` (versione base)
- `POST /api/editorial/v1/webhooks/lemonsqueezy` (handle subscription_updated + license_key_created)

Tutti gli altri endpoint: **stub che ritornano 501 Not Implemented** con messaggio "Coming in M{N}".

### 2.3 Plugin WordPress shell

Cartella: `plugins/ainstein-editorial/src/`

Struttura M1:
```
src/
├── plugin.php                          # Header WP + bootstrap
├── composer.json                       # Autoload PSR-4 Ainstein\Editorial\
├── Includes/
│   ├── Plugin.php                      # Singleton main class
│   ├── Activator.php                   # register_activation_hook callback
│   ├── Deactivator.php                 # register_deactivation_hook callback
│   ├── ApiClient.php                   # wp_remote_post wrapper
│   └── LicenseManager.php              # Local cache + activation flow
├── Admin/
│   ├── AdminPages.php                  # Registra menu admin minimo (M1: solo "License")
│   └── Pages/
│       └── License.php                 # Form activation: input license + bottone Attiva
└── Utils/
    └── Logger.php                      # Plugin local log (debug)
```

**`wp_options` create da M1**:
- `aied_license_key` (string)
- `aied_api_token` (string, JWT)
- `aied_api_token_expires_at` (int timestamp)
- `aied_user_email` (string)
- `aied_tier` (string)
- `aied_plugin_version` (string)
- `aied_first_activated_at` (int timestamp)

**Flow attivazione end-to-end**:
1. User installa plugin via zip
2. WP attiva plugin → `Activator::activate()` crea `aied_*` options vuote, redirect a `License` page
3. User incolla license key + click "Attiva"
4. `License.php` form submit → `ApiClient::post('/activate', {key, domain, wp_version, plugin_version, admin_email})`
5. Backend valida con LS, crea `aied_users` + `aied_sites`, ritorna `{api_token, expires_in, tier, user.email}`
6. Plugin salva tutto in `wp_options`
7. License page mostra "✓ Attivata. Tier: {tier}. Sei pronto."

### 2.4 Lemon Squeezy account setup

**Manuale, NON code** ma è blocker per testare M1.

Step:
1. Sign up `app.lemonsqueezy.com` (free tier ok per test)
2. Create Store: nome "Ainstein Editorial Test"
3. Create 4 **subscription products** (test mode):
   - Starter — $29/mo (test currency USD ok per dev)
   - Pro — $69/mo
   - Business — $149/mo
   - Agency — $349/mo
   Per ognuno: enable "License Keys" feature, set "Activation Limit" = tier limit (1, 3, 10, 50)
4. Create 3 **one-time products** (top-up packs):
   - Boost +20 articles — $15
   - Boost +50 articles — $30
   - Boost +100 articles — $50
5. Configure **Webhook**:
   - URL: `https://[hetzner-ainstein-domain]/api/editorial/v1/webhooks/lemonsqueezy`
   - For dev locale: usare ngrok per esporre `http://localhost/seo-toolkit/api/editorial/v1/webhooks/lemonsqueezy`
   - Events: subscription_created, subscription_updated, subscription_cancelled, order_created, license_key_created
   - Save signing secret
6. **Test mode**: generare 2-3 license key di test (1 per Starter, 1 per Pro, 1 per Business) per testare M1 activation flow

Output del setup salvato in: file `.env.local` (gitignored) con:
```
LEMONSQUEEZY_API_KEY=...
LEMONSQUEEZY_STORE_ID=...
LEMONSQUEEZY_WEBHOOK_SECRET=...
TEST_LICENSE_KEYS_STARTER=...
TEST_LICENSE_KEYS_PRO=...
TEST_LICENSE_KEYS_BUSINESS=...
```

### 2.5 Build script

File: `plugins/ainstein-editorial/build.sh`

Funzionalità:
- Legge versione da `src/plugin.php` header (`Version: X.Y.Z`)
- Esegue `composer install --no-dev --optimize-autoloader` in `src/`
- Compila Tailwind CSS (anche minimal in M1, full in M3)
- Crea zip `dist/ainstein-editorial-v{X.Y.Z}.zip` includendo solo:
  - `src/`
  - `assets/` (compiled CSS/JS)
  - `languages/` (M5+)
  - `readme.txt` (M6)
- Esclude: `tests/`, `docs/`, `node_modules/`, `.env*`, `build.sh`, `composer.lock` (opzionale)
- Output finale: `dist/ainstein-editorial-v0.1.0.zip` installabile su WP

Test: download zip, install su WP fresh in cartella separata (Laragon `wp-test/`), attivazione plugin, verifica funziona.

---

## 3. Out of scope M1 (esplicitato)

- Frontend admin pages oltre la "License" page
- Tailwind compilato completo (basta CSS minimal in M1)
- AI generation di alcun tipo
- WP cron registration
- Email infrastructure
- i18n .po/.mo files
- Lemon Squeezy customer portal embed
- Top-up balance tracking
- Admin pages "Dashboard", "Articles", "Editorial Plan", etc.
- Streaming SSE endpoints
- Image generation Gemini

Tutto questo arriva in M2-M6.

---

## 4. Piano di lavoro

> Task ID: `M1.X` o `M1.X.y` per sotto-task. Effort in ore.

### M1.1 — Database migration `aied_*` tables
**Effort**: 4-6 ore  
**Dipendenze**: Nessuna  
**Deliverable**: file SQL eseguibile + rollback script + verifica DB locale

- [ ] M1.1.a Creare `seo-toolkit/database/migrations/2026-05-15-aied-schema.sql` con 9 CREATE TABLE statements (vedi `design.md` §4 per schema completo)
- [ ] M1.1.b Aggiungere FK + indici critici (sezione 2.1 sopra)
- [ ] M1.1.c Creare `seo-toolkit/database/migrations/rollback/2026-05-15-aied-schema-rollback.sql` con DROP TABLE in ordine inverso (rispetto FK)
- [ ] M1.1.d Eseguire migration su DB locale Laragon: `mysql seo_toolkit < migration.sql`
- [ ] M1.1.e Verifica: `mysql seo_toolkit -e "SHOW TABLES LIKE 'aied_%'"` → 9 tabelle
- [ ] M1.1.f Verifica indici: `mysql seo_toolkit -e "SHOW INDEX FROM aied_users"` → idx_license presente
- [ ] M1.1.g Inserire **3 record di test** in `aied_users` (uno per tier Starter/Pro/Business) per testare query downstream

**DoD M1.1**: 9 tabelle esistono, FK rispettate, 3 user di test inseriti, rollback testato.

---

### M1.2 — Lemon Squeezy account setup
**Effort**: 2-3 ore  
**Dipendenze**: Nessuna (parallelo a M1.1)  
**Deliverable**: account LS configurato + 6 prodotti + 3 license key test + `.env.local`

- [ ] M1.2.a Sign up `app.lemonsqueezy.com` con email founder
- [ ] M1.2.b Verificare email + completare profilo (paese, tax info)
- [ ] M1.2.c Create Store "Ainstein Editorial Test" — currency EUR
- [ ] M1.2.d Create 4 subscription products (Starter/Pro/Business/Agency) con License Keys enabled, activation limit corretto
- [ ] M1.2.e Create 3 one-time products (top-up packs)
- [ ] M1.2.f Configure webhook (URL placeholder: ngrok per dev, dominio prod per launch)
- [ ] M1.2.g Generate 3 test license key (Starter/Pro/Business) via "Issue License" button
- [ ] M1.2.h Salvare credenziali in `seo-toolkit/.env.local` (verificare che `.env.local` sia in `.gitignore`)
- [ ] M1.2.i Test API call manuale: `curl -H "Authorization: Bearer $LS_API_KEY" https://api.lemonsqueezy.com/v1/licenses` → 200 OK

**DoD M1.2**: Account configurato, 3 license key utilizzabili per testing, credenziali salvate localmente.

---

### M1.3 — Backend API skeleton
**Effort**: 6-8 ore  
**Dipendenze**: M1.1 (tabelle esistenti per query)  
**Deliverable**: cartella `api/editorial/` con routing funzionante + middleware base + tutti gli endpoint stub

- [ ] M1.3.a Creare struttura cartelle `seo-toolkit/api/editorial/` (Controllers, Middleware, Services, Lib)
- [ ] M1.3.b Creare `routes.php` con definizione di tutti ~25 endpoint (vedi `design.md` §5). Endpoint M1-impl: real handler. Altri: stub return `[501, {error: 'Coming in M{N}'}]`.
- [ ] M1.3.c Integrare routing in `seo-toolkit/public/index.php`: route group prefix `/api/editorial/v1/*` → carica `api/editorial/routes.php`
- [ ] M1.3.d Implementare `BaseController` con metodi `jsonOk($data, $status=200)`, `jsonError($message, $status=400)`, `sseStream($callback)`
- [ ] M1.3.e Implementare `LicenseAuthMiddleware` (skip per `/activate` + `/webhooks/*`): legge `X-License-Key` + `X-Site-Domain` + `X-Api-Token`, valida JWT, carica `aied_users` + `aied_sites` in request context
- [ ] M1.3.f Implementare `RateLimitMiddleware` con cache file/Redis: 60 req/min per license_key
- [ ] M1.3.g Stub `QuotaMiddleware` (in M1 lascia passare tutto, full impl in M3)
- [ ] M1.3.h Test endpoint stub: `curl http://localhost/seo-toolkit/api/editorial/v1/articles/generate` → 501 con JSON `{error: "Coming in M3"}`

**DoD M1.3**: Tutti gli endpoint registrati e raggiungibili. Middleware funzionano. Stub ritornano 501 con messaggio.

---

### M1.4 — `LicenseManager` + `JwtService` + activation logic
**Effort**: 8-12 ore  
**Dipendenze**: M1.2 (LS test license), M1.3 (middleware + base)  
**Deliverable**: endpoint `/activate` `/deactivate` `/refresh-token` end-to-end funzionanti

- [ ] M1.4.a Implementare `Lib/LemonSqueezyClient.php`: HTTP wrapper per Lemon Squeezy API v1 (validate license, activate instance, deactivate instance, get license info)
- [ ] M1.4.b Implementare `Services/JwtService.php`: issue token (payload: user_id, site_id, exp 24h), verify token, refresh logic
- [ ] M1.4.c Implementare `Services/LicenseManager.php`:
  - `activate(license_key, domain, wp_version, plugin_version, admin_email)` → valida con LS, crea/finds `aied_users`, crea `aied_sites`, attiva instance LS, return token
  - `deactivate(api_token, site_id)` → mark `aied_sites.status='deactivated'`, deactivate instance LS, free slot
  - `refreshToken(license_key, domain)` → riconfere LS license, emette nuovo JWT
- [ ] M1.4.d Implementare `Controllers/ActivationController.php` con 3 metodi
- [ ] M1.4.e Implementare `Controllers/SubscriptionController::status` (versione base: ritorna tier + subscription_status da `aied_users`, quota count da `aied_actions_log` mese corrente)
- [ ] M1.4.f Test end-to-end con curl + license key di test:
  - Activate: `curl -X POST .../activate -d '{license_key, domain, wp_version, plugin_version, admin_email}'` → 200 + api_token
  - Verify DB: `aied_users` + `aied_sites` records creati, `api_token` salvato
  - Status: `curl -H "X-Api-Token: ..." .../subscription/status` → 200 + JSON con tier
  - Re-activate stesso domain: idempotente (no errore, return stesso token o nuovo)
  - Activate 2° domain con stesso Starter (limit 1): error 422 "Site limit reached"
  - Refresh: `curl -X POST .../refresh-token -d '{license_key, domain}'` → 200 + nuovo token
  - Deactivate: `curl -X POST .../deactivate -d '{site_id}'` → 200, slot liberato

**DoD M1.4**: Tutti i 4 flow (activate, status, refresh, deactivate) testati end-to-end con curl. Edge case (limit reached, invalid key, expired token) gestiti con messaggi italiani user-friendly.

---

### M1.5 — Webhook handler Lemon Squeezy
**Effort**: 4-6 ore  
**Dipendenze**: M1.4 (`LicenseManager`)  
**Deliverable**: endpoint `/webhooks/lemonsqueezy` che gestisce 5 eventi critici

- [x] M1.5.a Implementare signature verification con LS webhook secret (HMAC SHA256) — `Editorial\Services\WebhookVerifier` con `hash_equals` timing-safe
- [x] M1.5.b Implementare `Controllers/WebhookController::lemonsqueezy(Request)`:
  - `subscription_created`: log event (account creation reale via /activate plugin, qui solo trace)
  - `subscription_updated`: update `aied_users.tier` + `subscription_status` + `subscription_renews_at`
  - `subscription_cancelled`: set `aied_users.subscription_status='cancelled'` (ma mantieni active fino a end_period)
  - `subscription_payment_failed`: set `subscription_status='past_due'` + `last_payment_failed_at` (warning email in M6)
  - `order_created`: se è top-up pack (variant_id mapping in `config/editorial.php`), credita `aied_users.topup_balance_articles`
  - `license_key_created`: trace log per debugging
- [x] M1.5.c Implementare retry/idempotency: log `aied_api_logs` ogni webhook ricevuto con `external_event_id` LS + UNIQUE INDEX `(provider, external_event_id)` → INSERT IGNORE garantisce no double-processing
- [x] M1.5.d Test runner `api/editorial/tests/webhook_smoke.php` → 9/9 in-process + 5/5 HTTP via curl (firma valida, replay, bad sig, missing sig, malformed JSON)

**DoD M1.5** ✅: 6 eventi gestiti, signature validata, idempotency garantita, log completo in `aied_api_logs`.

**Note implementative**:
- Migration aggiuntiva `2026_05_14_aied_webhook_columns.sql` — aggiunge `aied_users.topup_balance_articles`, `aied_users.last_payment_failed_at`, `aied_api_logs.external_event_id` + UNIQUE INDEX.
- Mapping variant_id → tier/topup centralizzato in `config/editorial.php` (overridabile via env `LEMONSQUEEZY_VARIANT_*_ID` quando il cliente configurerà l'account LS in M1.2).
- Tutti i servizi (`WebhookVerifier`, `WebhookProcessor`) usano lazy-load di `config/environment.php` (pattern come `JwtService`) per evitare bug "secret missing" quando l'env non è ancora stato caricato dalla request.
- Provider log in `aied_api_logs` = `lemonsqueezy_webhook` (separato da futuro `lemonsqueezy_api` per chiamate outbound).

---

### M1.6 — Plugin WP shell + ApiClient
**Effort**: 8-12 ore  
**Dipendenze**: M1.4 (endpoint activate per test integration)  
**Deliverable**: plugin WP installabile da zip, attivabile, license activation funzionante via UI

- [x] M1.6.a `plugins/ainstein-editorial/ainstein-editorial.php` — Plugin Header WP 0.1.0, costanti AIED_*, autoload fallback PSR-4 (post-fix: file in root, non `src/`, per essere visibile a WordPress)
- [x] M1.6.b `composer.json` con autoload PSR-4 `Ainstein\Editorial\Includes\|Admin\|Utils\`
- [x] M1.6.c `composer install` eseguito, vendor/ generato (0 dipendenze require, solo autoload)
- [x] M1.6.d `Includes/Plugin.php` — singleton, hook admin_menu/admin_enqueue/admin_init/admin_post_aied_*
- [x] M1.6.e `Includes/Activator.php` — flag `aied_first_activated_at`, transient redirect a License page
- [x] M1.6.f `Includes/Deactivator.php` — cleanup WP cron events `aied_*`, mantiene wp_options
- [x] M1.6.g `Includes/ApiClient.php` — wrapper `wp_remote_request`, auto-headers (X-License-Key, X-Site-Domain, Bearer), auto-refresh 401 + retry una volta, traduzione errori IT
- [x] M1.6.h `Includes/LicenseManager.php` (plugin-side) — activate/deactivate/isActive/getApiToken con refresh proattivo <1h, status(), traduzione errori backend in italiano
- [x] M1.6.i `Admin/AdminPages.php` — menu top-level "Ainstein Editorial" + sottomenu License, handler admin-post.php
- [x] M1.6.j `Admin/Pages/License.php` — UI 2 stati (form attivazione / card attivato), nonce + capability check, redirect post-action con notice
- [x] M1.6.k `assets/css/admin.css` — stili minimal Ainstein-like (card, badge, tabella status)
- [x] M1.6.l Test E2E browser **spostato a M1.8**: per ora coperti da 17/17 smoke test unit (`tests/plugin_license_smoke.php` con mock WP) — copertura happy path + 5 error path

**DoD M1.6** ✅ (parziale): Plugin compila, lint pulito, unit smoke 17/17. Test browser end-to-end (form submission via WP admin) confluisce in M1.8.

**Note implementative**:
- ApiClient ritorna sempre array `['ok', 'status', 'data', 'error']` invece di throw: error path testabile, UI può decidere come mostrare.
- LicenseManager pulisce le wp_options anche se la deactivate remote fallisce (best-effort) — l'utente vuole disattivare HW, lo stato locale deve essere coerente.
- `clearLocalState()` NON cancella `aied_license_key` e `aied_user_email`: ripopolano il form quando l'utente fa riattivazione successiva (UX).
- Auto-refresh JWT: ApiClient retry una volta su 401, LicenseManager fa refresh proattivo se TTL <1h.
- Capability `manage_options` su tutti i form. Nonce obbligatorio (`check_admin_referer`).
- AIED_API_BASE definibile via constant in `wp-config.php` (per staging/dev). Default produzione `https://ainstein.it/api/editorial/v1`.

---

### M1.7 — Build script + distribuzione zip
**Effort**: 3-4 ore  
**Dipendenze**: M1.6 (codice plugin completo)  
**Deliverable**: `build.sh` produce zip installabile

- [ ] M1.7.a Creare `plugins/ainstein-editorial/build.sh` (bash script)
- [ ] M1.7.b Logic: leggi version da plugin.php header, composer install no-dev, copia file in tempdir, zip
- [ ] M1.7.c Esclusioni: tests/, docs/, node_modules/, .env*, build.sh, *.log
- [ ] M1.7.d Output: `dist/ainstein-editorial-v0.1.0.zip` (~50-100 KB in M1, sarà più grande in M3+)
- [ ] M1.7.e Test: scarica zip, installa su WP fresh diverso da quello di sviluppo, attiva, License page, attivazione → tutto funziona
- [ ] M1.7.f Documentare uso build.sh in `plugins/ainstein-editorial/README.md`

**DoD M1.7**: Zip prodotto installabile su WP pulito. Plugin attiva + license activation funziona.

---

### M1.8 — End-to-end QA + smoke test
**Effort**: 2-3 ore  
**Dipendenze**: Tutti i task M1 precedenti  
**Deliverable**: report smoke test scritto, screenshot del flow funzionante

- [ ] M1.8.a Setup WP locale Laragon nuovo (cartella `wp-test-clean/`)
- [ ] M1.8.b Installa plugin via zip (M1.7 output)
- [ ] M1.8.c Attiva plugin → verifica redirect a License page
- [ ] M1.8.d Inserisci test license Starter → verifica success
- [ ] M1.8.e Verifica DB: `aied_users` + `aied_sites` record creati con dati corretti
- [ ] M1.8.f Verifica `wp_options`: `aied_api_token`, `aied_tier`, etc. salvati
- [ ] M1.8.g Test refresh token: aspetta 23h fittizie (modifica `aied_api_token_expires_at` a `time()-100`), refresh page → background refresh dovrebbe rinnovare
- [ ] M1.8.h Test multi-site limit: prova ad attivare stesso license su seconda installazione WP → errore "Site limit reached"
- [ ] M1.8.i Test deactivate: bottone disattiva → slot liberato + activation possibile su altro sito
- [ ] M1.8.j Test webhook: forza trigger LS test webhook → verifica DB aggiornato
- [ ] M1.8.k Screenshot: License page (states attivato + non attivato), DB query risultati
- [ ] M1.8.l Compila report `docs/milestones/M1-foundation-test-report.md` con risultati + screenshot + eventuali issue scoperte (in tal caso aprire come task per M2 o fix subito)

**DoD M1.8**: Tutti gli step E2E passano. Report compilato e committato.

---

## 5. Definition of Done globale M1

M1 è completa quando:

- ✅ DB schema `aied_*` completo e popolato (test data)
- ✅ Account Lemon Squeezy configurato con 7 prodotti + 3 license key test
- ✅ Backend API `/api/editorial/v1/*` raggiungibile, con activation/deactivation/refresh/status implementati
- ✅ Webhook handler funzionante per 5 eventi LS critici
- ✅ Plugin WP installabile via zip
- ✅ License activation end-to-end funziona via UI
- ✅ Build script produce zip distribuibile
- ✅ Smoke test passato con report
- ✅ `docs/decisions.md` aggiornato se decisioni nuove emergenti durante implementazione
- ✅ Commit atomici per ogni task M1.X
- ✅ Roadmap.md aggiornata con checkbox completati

---

## 6. Risk register M1

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| Lemon Squeezy API breaking change durante dev | Bassa | Alto | Pin API version (`2023-10-30`), abstract behind LemonSqueezyClient |
| Conflitti DB schema con tabelle Ainstein esistenti | Bassissima | Medio | Prefisso `aied_` evita collision (tested con SHOW TABLES) |
| WP Cron unreliable (problema generale, non M1) | Alta | Medio (M4+) | Documentato per M4. M1 non usa cron. |
| Plugin attivo conflitta con altri plugin (Yoast, RankMath) | Media | Basso (M1) | M1 non tocca SEO meta, conflitti emergeranno in M3-M5 |
| ngrok URL change durante dev test webhook | Alta | Basso | Usare ngrok pro per static URL, oppure aggiornare webhook URL ad ogni restart |
| JWT secret leak | Bassissima | Critico | Secret in env var, mai committato. Rotation possibile via fresh deploy |

---

## 7. Test plan M1

### Unit tests (PHPUnit)
- `LemonSqueezyClientTest`: mock HTTP, verifica chiamate corrette + parsing response
- `JwtServiceTest`: issue/verify/refresh con casi edge (expired, invalid signature, tampered payload)
- `LicenseManagerTest`: activate/deactivate logic con mock LS

### Integration tests
- `ActivationFlowTest`: end-to-end backend (DB + LS mock) per i 4 endpoint
- `WebhookHandlerTest`: simula payload LS firmati, verifica DB updates

### Manual smoke test
Vedi M1.8 sopra.

### Coverage target
60%+ su servizi (LemonSqueezyClient, LicenseManager, JwtService). 30%+ su controllers (sono thin wrapper sui services).

---

## 8. Effort totale stimato

| Task | Effort |
|------|--------|
| M1.1 DB migration | 4-6h |
| M1.2 Lemon Squeezy setup | 2-3h |
| M1.3 API skeleton | 6-8h |
| M1.4 Activation logic | 8-12h |
| M1.5 Webhook handler | 4-6h |
| M1.6 Plugin WP shell | 8-12h |
| M1.7 Build script | 3-4h |
| M1.8 E2E smoke test | 2-3h |
| **TOTALE M1** | **37-54 ore** |

**Range realistico**: 1.5-2 settimane full-time, oppure 3-4 settimane part-time (15h/settimana).

---

## 9. Cosa otteniamo a fine M1

Demo dimostrabile a fine M1:
- Installi plugin su WP fresh
- Incolli test license key Starter
- Vedi "✓ Attivato. Tier: Starter."
- Apri DB e vedi user + site creati
- Disattivi su questo sito → posso attivare su altro sito

**Non è ancora un prodotto vendibile**, ma è la "fondamenta" senza cui nulla del resto può esistere. È onesto presentarlo come "scheletro tecnico funzionante".

---

## 10. Approvazione

Cliente approva questa spec? Una volta approvata, parte M1.1.

> ☐ APPROVATA — `[firma + data]`
> ☐ MODIFICHE RICHIESTE — `[lista modifiche]`

---

*Spec scritta: 2026-05-13 · Architetto: Claude*
