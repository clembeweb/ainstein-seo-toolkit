# Google Ads API Integration — Design Document

> Data: 2026-03-09 | Modulo: ads-analyzer | Stato: Draft

---

## 1. Obiettivo

Sostituire completamente il flusso Google Ads Script con integrazione API diretta. L'utente connette il proprio account Google Ads via OAuth, Ainstein interroga l'API per leggere dati campagne/search terms e scrivere (creare campagne, applicare negative keywords).

### Cosa cambia

| Aspetto | Prima (Script) | Dopo (API) |
|---------|----------------|------------|
| Setup | Utente copia/incolla script JS in Google Ads | OAuth one-click |
| Dati | Script invia payload a `/api/v1/ads-analyzer/ingest` | Ainstein chiama Google Ads API con GAQL |
| Frequenza | Schedule script (es. daily) | On-demand + cron giornaliero |
| Date range | Fisso (deciso dallo script) | Selezionabile dall'utente (date picker) |
| Write | Solo export CSV | Creazione campagne + applicazione negative KW via API |
| Multi-account | 1 script per account | OAuth multi-account |

### Cosa NON cambia

- CampaignEvaluatorService (valutazione AI) — stessa logica, stessi dati
- MetricComparisonService — stessi confronti KPI
- SearchTermAnalysisController — stessa analisi negative KW, fonte dati diversa
- CampaignCreatorController — stesso wizard, aggiunge publish su Google Ads
- Tutte le view campagne (dashboard, evaluation, search-terms)

---

## 2. Architettura API

### 2.1 REST Endpoints Google Ads API v18+

Base URL: `https://googleads.googleapis.com/v18/customers/{CUSTOMER_ID}`

#### Headers richiesti per ogni chiamata

```
Content-Type: application/json
Authorization: Bearer {ACCESS_TOKEN}
developer-token: {DEVELOPER_TOKEN}
login-customer-id: {MCC_CUSTOMER_ID}    # solo se si accede via MCC
```

#### Read — GAQL Queries

Endpoint: `POST /googleAds:searchStream`

```json
{
  "query": "SELECT campaign.name, metrics.clicks FROM campaign WHERE segments.date DURING LAST_7_DAYS"
}
```

Risposta: array JSON con risultati (searchStream = singola risposta, no paginazione).

#### Write — Mutations

Endpoint: `POST /campaigns:mutate`, `/adGroupCriteria:mutate`, `/googleAds:mutate` (grouped)

```json
{
  "operations": [
    { "create": { "status": "PAUSED", "name": "...", ... } }
  ]
}
```

Grouped mutate con temporary resource names (`#temp_id`) per creare budget + campaign + ad groups + ads + keywords in una singola chiamata.

#### Keyword Planner

Endpoint: `POST /customers/{CUSTOMER_ID}:generateKeywordIdeas`

```json
{
  "language": "languageConstants/1000",
  "geoTargetConstants": ["geoTargetConstants/2380"],
  "keywordPlanNetwork": "GOOGLE_SEARCH",
  "keywordAndUrlSeed": {
    "keywords": ["keyword1"],
    "url": "https://example.com"
  }
}
```

Risposta: `avgMonthlySearches`, `competition`, `competitionIndex`, `lowTopOfPageBidMicros`, `highTopOfPageBidMicros`.

> **Nota**: Keyword Planner è fase 2 (cross-modulo). In fase 1 ci concentriamo su read/write campagne.

### 2.2 Credenziali Server-Side

| Credenziale | Storage | Chi la usa |
|-------------|---------|------------|
| Developer Token (`oChCo-54qVQFIRwGUhudcQ`) | `settings` table (admin) | GoogleAdsService |
| MCC Customer ID (`284-349-6968`) | `settings` table (admin) | GoogleAdsService (header `login-customer-id`) |
| OAuth Client ID + Secret | `settings` table (admin) | GoogleOAuthService (già esistente) |
| Access Token + Refresh Token (per utente) | `google_oauth_tokens` (già esistente) | GoogleAdsService (per-request) |
| Customer ID account Google Ads | `ga_projects.google_ads_customer_id` | GoogleAdsService (per-project) |

---

## 3. OAuth — Estensione GoogleOAuthService

### 3.1 Nuovo scope

Aggiungo `SCOPE_GOOGLE_ADS = 'https://www.googleapis.com/auth/adwords'` a `GoogleOAuthService`.

Il flusso OAuth resta identico a GSC:
1. Utente clicca "Connetti Google Ads" nel progetto
2. Redirect a Google OAuth consent con scope `adwords`
3. Callback su `/oauth/google/callback` (già esistente)
4. Token salvati in `google_oauth_tokens` con `service = 'google_ads'`

### 3.2 Selezione Account

Dopo OAuth, serve un passo aggiuntivo: l'utente deve scegliere QUALE account Google Ads collegare. La API `CustomerService.listAccessibleCustomers` restituisce la lista degli account accessibili. L'utente seleziona e il `customer_id` viene salvato in `ga_projects.google_ads_customer_id`.

Endpoint: `GET https://googleads.googleapis.com/v18/customers:listAccessibleCustomers`

### 3.3 Redirect URI

Callback: `/oauth/google/callback` (già in uso per GSC). Il parametro `state` nel flusso OAuth contiene il contesto (modulo, project_id) per sapere dove redirectare dopo il callback.

---

## 4. Nuovo Service: GoogleAdsService

`services/GoogleAdsService.php` — service centralizzato per tutte le chiamate Google Ads API.

### 4.1 Responsabilità

- Gestione access token (refresh automatico via GoogleOAuthService)
- Esecuzione query GAQL (read)
- Esecuzione mutations (write)
- Rate limiting (1 req/sec per utente, contatore giornaliero)
- Logging via ApiLoggerService (provider: `google_ads`)
- Retry con exponential backoff su errori transitori

### 4.2 Metodi principali

```php
class GoogleAdsService
{
    public function __construct(int $userId, string $customerId) {}

    // === READ ===
    public function search(string $gaql): array {}
    public function searchStream(string $gaql): array {}
    public function listAccessibleCustomers(): array {}

    // === WRITE ===
    public function mutateCampaigns(array $operations): array {}
    public function mutateAdGroups(array $operations): array {}
    public function mutateAdGroupAds(array $operations): array {}
    public function mutateAdGroupCriteria(array $operations): array {}
    public function mutateCampaignCriteria(array $operations): array {}
    public function mutateCampaignBudgets(array $operations): array {}
    public function groupedMutate(array $mutateOperations): array {}

    // === KEYWORD PLANNER (fase 2) ===
    public function generateKeywordIdeas(array $params): array {}

    // === INTERNAL ===
    private function request(string $method, string $endpoint, ?array $body = null): array {}
    private function getAccessToken(): string {}
    private function refreshTokenIfNeeded(): void {}
}
```

### 4.3 Pattern implementativo

```php
// Esempio: fetch campagne ultimi 7 giorni
$gads = new GoogleAdsService($userId, $project['google_ads_customer_id']);
$results = $gads->searchStream("
    SELECT campaign.id, campaign.name, campaign.status,
           campaign.advertising_channel_type, campaign_budget.amount_micros,
           campaign.bidding_strategy_type,
           metrics.clicks, metrics.impressions, metrics.ctr,
           metrics.average_cpc, metrics.cost_micros,
           metrics.conversions, metrics.conversions_value
    FROM campaign
    WHERE segments.date DURING LAST_7_DAYS
      AND campaign.status != 'REMOVED'
");
```

Internamente `request()` usa `curl` come tutti gli altri service (DataForSeoService, etc.) + `ApiLoggerService::log()`.

---

## 5. Nuovo Service: CampaignSyncService

`modules/ads-analyzer/services/CampaignSyncService.php` — orchestratore sync dati.

### 5.1 Responsabilità

- Sincronizzazione completa: campagne → ad groups → keywords → ads → extensions → search terms
- Salvataggio in tabelle `ga_*` esistenti
- Gestione date range (da parametro utente o default)
- Creazione record `ga_syncs` per tracciamento

### 5.2 Metodi

```php
class CampaignSyncService
{
    public function __construct(GoogleAdsService $gadsService, int $projectId) {}

    // Sync completa (on-demand o cron)
    public function syncAll(string $dateFrom, string $dateTo): array {}

    // Sync singole risorse
    public function syncCampaigns(string $dateFrom, string $dateTo): int {}
    public function syncAdGroups(string $dateFrom, string $dateTo): int {}
    public function syncKeywords(string $dateFrom, string $dateTo): int {}
    public function syncAds(string $dateFrom, string $dateTo): int {}
    public function syncExtensions(): int {}
    public function syncSearchTerms(string $dateFrom, string $dateTo): int {}
}
```

---

## 6. Database — Migrazioni

### 6.1 Nuova tabella: `ga_syncs` (sostituisce `ga_script_runs`)

```sql
CREATE TABLE ga_syncs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    sync_type ENUM('manual', 'cron', 'on_demand') DEFAULT 'manual',
    status ENUM('running', 'completed', 'error') DEFAULT 'running',
    date_range_start DATE NULL,
    date_range_end DATE NULL,
    campaigns_synced INT DEFAULT 0,
    ad_groups_synced INT DEFAULT 0,
    keywords_synced INT DEFAULT 0,
    ads_synced INT DEFAULT 0,
    search_terms_synced INT DEFAULT 0,
    error_message TEXT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE
);
```

### 6.2 Modifiche a `ga_projects`

```sql
ALTER TABLE ga_projects
    ADD COLUMN google_ads_customer_id VARCHAR(20) NULL AFTER user_id,
    ADD COLUMN google_ads_account_name VARCHAR(255) NULL AFTER google_ads_customer_id,
    ADD COLUMN oauth_token_id INT NULL AFTER google_ads_account_name,
    ADD COLUMN last_sync_at TIMESTAMP NULL,
    ADD COLUMN sync_enabled TINYINT(1) DEFAULT 1,
    DROP COLUMN api_token,
    DROP COLUMN api_token_created_at,
    DROP COLUMN script_config;
```

### 6.3 Modifiche tabelle campagne esistenti

Le tabelle `ga_campaigns`, `ga_campaign_ad_groups`, `ga_ad_group_keywords`, `ga_ads`, `ga_extensions` restano strutturalmente identiche. Cambio:

```sql
-- Rinomina run_id → sync_id in tutte le tabelle
ALTER TABLE ga_campaigns CHANGE run_id sync_id INT NOT NULL;
ALTER TABLE ga_campaign_ad_groups CHANGE run_id sync_id INT NOT NULL;
ALTER TABLE ga_ad_group_keywords CHANGE run_id sync_id INT NOT NULL;
ALTER TABLE ga_ads CHANGE run_id sync_id INT NOT NULL;
ALTER TABLE ga_extensions CHANGE run_id sync_id INT NOT NULL;

-- Aggiungi google_ads_id per reference alla risorsa originale
ALTER TABLE ga_campaigns ADD COLUMN google_ads_id BIGINT NULL AFTER id;
ALTER TABLE ga_campaign_ad_groups ADD COLUMN google_ads_id BIGINT NULL AFTER id;
ALTER TABLE ga_ads ADD COLUMN google_ads_id BIGINT NULL AFTER id;

-- FK verso ga_syncs
ALTER TABLE ga_campaigns ADD FOREIGN KEY (sync_id) REFERENCES ga_syncs(id) ON DELETE CASCADE;
-- ... idem per le altre tabelle
```

### 6.4 Tabelle da rimuovere

```sql
DROP TABLE IF EXISTS ga_script_runs;
-- Le tabelle del vecchio flusso negative-kw (ga_ad_groups legacy, ga_search_terms legacy)
-- vengono pulite nella stessa migration
```

### 6.5 Tabella search terms — adattamento

`ga_search_terms` ha già le colonne giuste (`term`, `clicks`, `impressions`, `ctr`, `cost`, `conversions`, `conversion_value`). Aggiungo:

```sql
ALTER TABLE ga_search_terms
    ADD COLUMN sync_id INT NULL,
    ADD COLUMN campaign_name VARCHAR(255) NULL,
    ADD COLUMN ad_group_name VARCHAR(255) NULL;
```

---

## 7. Modifiche ai Controller Esistenti

### 7.1 CampaignController

- `dashboard()` — sostituisce `ScriptRun::getByProject()` con `Sync::getByProject()`. Aggiunge date picker per selezionare periodo. Aggiunge pulsante "Sincronizza ora".
- Nuovo metodo `sync()` — POST AJAX, avvia sync on-demand via CampaignSyncService
- Nuovo metodo `syncStatus()` — GET AJAX, polling stato sync
- `evaluate()` — invariato (usa stesse tabelle campagne)
- `evaluationShow()` — invariato
- `generateFix()` — invariato
- `exportPdf()` / `exportCsv()` — invariati

### 7.2 SearchTermAnalysisController

- `index()` — invece di mostrare "run disponibili" (dallo script), mostra date picker
- `getRunData()` → `getSyncData()` — legge search terms dal sync selezionato
- `analyze()` — invariato (AI analysis sugli stessi dati)
- Nuovo: `applyNegativeKeywords()` — POST, applica negative KW direttamente su Google Ads via API mutation

### 7.3 CampaignCreatorController

- `generateCampaign()` — invariato (genera struttura AI)
- Nuovo: `publishToGoogleAds()` — POST, crea campagna completa su Google Ads via grouped mutate (budget + campaign + ad groups + keywords + ads)
- `exportCsv()` — resta come alternativa

### 7.4 ProjectController

- `store()` — dopo creazione progetto, redirect a flusso OAuth se non connesso
- Nuovo: `connectGoogleAds()` — avvia OAuth flow
- Nuovo: `selectAccount()` — mostra lista account accessibili, salva customer_id
- Nuovo: `disconnectGoogleAds()` — rimuove token, resetta customer_id

### 7.5 Rimozioni

- **ScriptController** — eliminato (tutto il flusso script)
- **ApiController** — eliminato (endpoint ingest)
- **ScriptGeneratorService** — eliminato

---

## 8. Nuovo Cron: `sync-dispatcher.php`

Sostituisce il vecchio flusso "aspetta che lo script giri".

```php
// cron/sync-dispatcher.php — ogni 6 ore (o daily)
// 1. Trova tutti i progetti con sync_enabled = 1 e google_ads_customer_id != NULL
// 2. Per ogni progetto: CampaignSyncService->syncAll(last 7 days)
// 3. Se sync ok + auto_evaluate attivo: accoda valutazione AI
```

Frequenza: `0 */6 * * *` (ogni 6 ore) o `0 2 * * *` (daily alle 2:00).

Il cron `auto-evaluate.php` resta invariato — viene triggerato dopo sync come prima veniva triggerato dopo ingest.

---

## 9. Rate Limiting

### 9.1 Budget giornaliero

15.000 operazioni/giorno condivise tra tutti gli utenti.

```sql
CREATE TABLE ga_api_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    operations_count INT DEFAULT 0,
    UNIQUE KEY (user_id, date)
);
```

GoogleAdsService incrementa il contatore ad ogni chiamata. Limiti:
- Per-utente: max 1.000 ops/giorno (configurabile admin)
- Globale: max 12.000 ops/giorno (lascia margine 3k)
- Per-richiesta: max 1 req/sec per utente

### 9.2 Settings admin

```
gads_developer_token          → token sviluppatore
gads_mcc_customer_id          → MCC ID (senza trattini: 2843496968)
gads_daily_limit_per_user     → 1000 (default)
gads_daily_limit_global       → 12000 (default)
gads_sync_frequency_hours     → 6 (default)
```

---

## 10. View — Modifiche UI

### 10.1 Dashboard progetto (`campaigns/dashboard.php`)

- Rimuove sezione "Ultimo invio script" e "Configura Script"
- Aggiunge **date picker** (ultimi 7g, 30g, 90g, custom) per selezionare periodo dati
- Aggiunge pulsante **"Sincronizza"** con stato (spinner, ultimo sync)
- Badge connessione Google Ads (connesso/non connesso, nome account)
- KPI e lista campagne restano identici (stesse tabelle)

### 10.2 Setup connessione (nuova view `campaigns/connect.php`)

- Stato connessione (connesso/non connesso)
- Pulsante "Connetti Google Ads" (avvia OAuth)
- Selezione account (lista dropdown dopo OAuth)
- Pulsante "Disconnetti" con conferma

### 10.3 Search Terms Analysis (`campaigns/search-terms.php`)

- Date picker sostituisce il selettore "run"
- Nuovo pulsante "Applica su Google Ads" per negative keywords selezionate
- Resto invariato

### 10.4 Campaign Creator wizard

- Nuovo Step finale: "Pubblica su Google Ads" (alternativa a Export CSV)
- Conferma prima della pubblicazione (campagna creata in stato PAUSED)

### 10.5 Rimozioni view

- `script/setup.php` — eliminata
- `script/runs.php` — eliminata

---

## 11. File da Creare / Modificare / Eliminare

### Nuovi file

| File | Descrizione |
|------|-------------|
| `services/GoogleAdsService.php` | Service centralizzato API Google Ads |
| `modules/ads-analyzer/services/CampaignSyncService.php` | Orchestratore sync dati |
| `modules/ads-analyzer/database/migrations/011_google_ads_api.sql` | Migration DB |
| `modules/ads-analyzer/views/campaigns/connect.php` | View connessione OAuth |
| `modules/ads-analyzer/cron/sync-dispatcher.php` | Cron sync giornaliero |
| `modules/ads-analyzer/models/Sync.php` | Model per ga_syncs |
| `modules/ads-analyzer/models/ApiUsage.php` | Model per ga_api_usage |

### File da modificare

| File | Modifiche |
|------|-----------|
| `services/GoogleOAuthService.php` | Aggiunta scope `adwords`, metodo `listAccessibleCustomers()` |
| `modules/ads-analyzer/routes.php` | Rimuove route script/ingest, aggiunge route OAuth/sync |
| `modules/ads-analyzer/controllers/CampaignController.php` | Sync on-demand, date picker, rimuove ref a ScriptRun |
| `modules/ads-analyzer/controllers/SearchTermAnalysisController.php` | Fonte dati da sync, apply negative KW |
| `modules/ads-analyzer/controllers/CampaignCreatorController.php` | Publish to Google Ads |
| `modules/ads-analyzer/controllers/ProjectController.php` | Connect/disconnect/select account |
| `modules/ads-analyzer/controllers/DashboardController.php` | Rimuove ref a script |
| `modules/ads-analyzer/models/Project.php` | Nuovi campi OAuth, rimuove metodi token/script |
| `modules/ads-analyzer/views/campaigns/dashboard.php` | Date picker, sync button, rimuove script |
| `modules/ads-analyzer/views/campaigns/search-terms.php` | Apply negative KW button |
| `modules/ads-analyzer/views/campaign-creator/wizard.php` | Publish step |
| `modules/ads-analyzer/views/partials/project-nav.php` | Rimuove link script |
| `modules/ads-analyzer/module.json` | Rimuove settings script, aggiunge settings API |

### File da eliminare

| File | Motivo |
|------|--------|
| `modules/ads-analyzer/controllers/ScriptController.php` | Flusso script rimosso |
| `modules/ads-analyzer/controllers/ApiController.php` | Endpoint ingest rimosso |
| `modules/ads-analyzer/services/ScriptGeneratorService.php` | Generazione script rimossa |
| `modules/ads-analyzer/services/IngestService.php` | Ingest dallo script rimosso |
| `modules/ads-analyzer/models/ScriptRun.php` | Sostituito da Sync.php |
| `modules/ads-analyzer/views/script/setup.php` | View script rimossa |
| `modules/ads-analyzer/views/script/runs.php` | View storico script rimossa |

---

## 12. Fasi di Implementazione

### Fase 1: Infrastruttura (Foundation)
1. GoogleAdsService (REST client + auth + rate limiting)
2. Estensione GoogleOAuthService (scope adwords)
3. Migration DB (ga_syncs, modifiche ga_projects, rename run_id)
4. Model Sync, ApiUsage

### Fase 2: Read — Sync Dati
5. CampaignSyncService (fetch e salvataggio)
6. CampaignController: sync on-demand + date picker
7. Cron sync-dispatcher.php
8. Aggiornamento dashboard view

### Fase 3: OAuth Flow UI
9. ProjectController: connect/disconnect/select account
10. View connessione (connect.php)
11. Aggiornamento project-nav e dashboard

### Fase 4: Write — Negative Keywords
12. SearchTermAnalysisController: apply negative KW via API
13. View aggiornamento (bottone "Applica su Google Ads")

### Fase 5: Write — Campaign Creator Publish
14. CampaignCreatorController: publish to Google Ads (grouped mutate)
15. View wizard: step pubblicazione

### Fase 6: Cleanup
16. Rimozione ScriptController, ApiController, ScriptGeneratorService, IngestService
17. Rimozione view script, route script/ingest
18. Rimozione ScriptRun model
19. Drop tabella ga_script_runs
20. Aggiornamento docs e module.json

---

## 13. Rischi e Mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Rate limit 15k ops/giorno | Contatore DB + limiti per-utente configurabili |
| Token refresh fallisce | Retry + notifica utente "Riconnetti Google Ads" |
| API v18 deprecata | Versione API in Settings admin, upgrade facile (solo cambiare URL) |
| Google consent screen in testing | Richiedere verifica produzione prima del go-live |
| Keyword Planner richiede spesa attiva | Fase 2, con fallback ai provider attuali |
| Dati vecchi (pre-migrazione) persi | Backup modulo creato, progetti test ricreabili |

---

*Design document per implementazione Google Ads API — 2026-03-09*
