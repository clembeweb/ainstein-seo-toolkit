# Keyword Planner Integration — Design Document

> Data: 2026-03-12 | Scope: Cross-modulo | Stato: Approvato

---

## 1. Obiettivo

Integrare Google Keyword Planner come provider prioritario per volumi di ricerca, CPC, stagionalità e keyword suggestions. Il servizio usa l'account MCC di piattaforma (sempre con spesa attiva), garantendo dati precisi per tutti gli utenti senza richiedere OAuth individuale.

### Vincoli

- **Zero breaking changes**: la cascade esistente (RapidAPI → DataForSEO → KeywordsEverywhere) resta intatta come fallback
- **MCC centralizzato**: credenziali di piattaforma, nessun OAuth per-utente per Keyword Planner
- **Rate limiting separato**: quota KP indipendente da operazioni campagne ads-analyzer
- **Quota per-utente**: protezione contro esaurimento quota da singolo utente

---

## 2. Architettura

### 2.1 Nuovo Service: KeywordPlannerService

`services/KeywordPlannerService.php` — service centralizzato cross-modulo.

Usa `GoogleAdsService` internamente con le credenziali MCC di piattaforma (da `settings` table), non quelle dell'utente.

```php
class KeywordPlannerService
{
    // Credenziali MCC piattaforma (da settings)
    private string $mccCustomerId;
    private GoogleAdsService $gadsService;
    private int $userId;

    public function __construct(?int $userId = null)
    {
        // Legge credenziali MCC da settings table (admin)
        // NON richiede OAuth utente
        // userId per rate limiting (da Auth::user() se non passato)
        $this->userId = $userId ?? (Auth::user()['id'] ?? 0);
    }

    /**
     * Verifica se il service è configurato e utilizzabile.
     * Compatibile con pattern esistente (isConfigured() su tutti i provider).
     */
    public function isConfigured(): bool
    {
        return Settings::get('kp_enabled', false)
            && !empty(Settings::get('gads_developer_token'))
            && !empty(Settings::get('gads_mcc_customer_id'));
    }

    /**
     * Volumi storici per keyword specifiche.
     * Use case: seo-tracking (aggiornamento volumi), keyword-research (arricchimento dati)
     *
     * Keywords > 700 vengono automaticamente splittate in chunk.
     * Risultati parziali: se chunk 2/3 fallisce, i risultati dei chunk completati
     * vengono comunque restituiti e salvati in cache.
     *
     * @param string[] $keywords Qualsiasi numero (chunked internamente a 700)
     * @param string $language languageConstants/1004 (default italiano)
     * @param string[] $geoTargets geoTargetConstants/2380 (default Italia)
     * @return array [keyword => [search_volume, cpc, cpc_low, competition, ...]]
     */
    public function getHistoricalMetrics(
        array $keywords,
        string $language = 'languageConstants/1004',
        array $geoTargets = ['geoTargetConstants/2380']
    ): array {}

    /**
     * Genera idee keyword da seed keywords o URL.
     * Use case: keyword-research (espansione seed, suggestions)
     *
     * @return array Lista keyword con metriche
     */
    public function generateKeywordIdeas(
        array $seedKeywords = [],
        ?string $url = null,
        string $language = 'languageConstants/1004',
        array $geoTargets = ['geoTargetConstants/2380'],
        int $limit = 100
    ): array {}

    /**
     * Implementazione compatibile con cascade esistente.
     * Chiamato da Keyword::getAllVolumeServices().
     * Firma identica agli altri provider: accetta string $countryCode ('IT', 'US').
     *
     * @return array ['success' => bool, 'data' => [...], 'error' => string|null]
     */
    public function getSearchVolumes(array $keywords, string $countryCode = 'IT'): array {}

    // --- Rate Limiting ---
    private function checkRateLimit(): bool {}   // usa $this->userId
    private function incrementUsage(): void {}    // usa $this->userId

    // --- Mapping ---
    private function countryToLocationConstant(string $countryCode): string {}
    private function countryToLanguageConstant(string $countryCode): string {}

    // --- Batch ---
    private function chunkKeywords(array $keywords, int $chunkSize = 700): array {}
}
```

### 2.2 Endpoint Google Ads API utilizzati

#### generateKeywordHistoricalMetrics

```
POST https://googleads.googleapis.com/v{API_VERSION}/customers/{MCC_ID}:generateKeywordHistoricalMetrics

Nota: la versione API (v18, v20, etc.) è condivisa con GoogleAdsService tramite
costante o setting admin. Usare la stessa versione per tutte le chiamate Google Ads.

Headers:
  Authorization: Bearer {ACCESS_TOKEN}
  developer-token: {DEVELOPER_TOKEN}
  login-customer-id: {MCC_CUSTOMER_ID}

Body:
{
  "keywords": ["keyword1", "keyword2", ...],
  "language": "languageConstants/1004",
  "geoTargetConstants": ["geoTargetConstants/2380"],
  "keywordPlanNetwork": "GOOGLE_SEARCH"
}
```

Risposta per ogni keyword:
- `avgMonthlySearches` (int)
- `monthlySearchVolumes` (array 12 mesi: year, month, monthly_searches)
- `competition` (enum: UNSPECIFIED, UNKNOWN, LOW, MEDIUM, HIGH)
- `competitionIndex` (int 0-100)
- `lowTopOfPageBidMicros` (int64, in micros → dividi per 1.000.000)
- `highTopOfPageBidMicros` (int64, in micros → dividi per 1.000.000)

#### generateKeywordIdeas

```
POST https://googleads.googleapis.com/v{API_VERSION}/customers/{MCC_ID}:generateKeywordIdeas

Body:
{
  "language": "languageConstants/1004",
  "geoTargetConstants": ["geoTargetConstants/2380"],
  "keywordPlanNetwork": "GOOGLE_SEARCH",
  "keywordAndUrlSeed": {
    "keywords": ["seed1", "seed2"],
    "url": "https://example.com"
  }
}
```

Stessi campi metrici di `generateKeywordHistoricalMetrics` + campo `text` con la keyword suggerita.

### 2.3 Flusso cascade aggiornato

```
Richiesta volumi keyword
        │
        ▼
┌─ KeywordPlannerService (MCC piattaforma) ──┐
│  - Rate limit OK?                          │
│  - Cache st_keyword_volumes valida?        │
│  - Chiamata API generateKeywordHistorical  │
│  - Salva in cache                          │
└────────────────────────────────────────────┘
        │ fallback (errore, quota, non configurato)
        ▼
┌─ RapidApiKeywordService ───────────────────┐
│  (invariato, come oggi)                    │
└────────────────────────────────────────────┘
        │ fallback
        ▼
┌─ DataForSeoService ────────────────────────┐
│  (invariato, come oggi)                    │
└────────────────────────────────────────────┘
        │ fallback
        ▼
┌─ KeywordsEverywhereService ────────────────┐
│  (invariato, come oggi)                    │
└────────────────────────────────────────────┘
```

---

## 3. Dati normalizzati

Output di `getSearchVolumes()` (compatibile con cascade):

```php
[
    'keyword1' => [
        'search_volume'     => 12100,        // avgMonthlySearches
        'cpc'               => 2.35,         // highTopOfPageBidMicros / 1_000_000
        'cpc_low'           => 0.85,         // lowTopOfPageBidMicros / 1_000_000 (NUOVO)
        'competition'       => 0.67,         // competitionIndex / 100 (0-1 decimale)
        'competition_level' => 'HIGH',       // competition enum
        'monthly_searches'  => [             // monthlySearchVolumes
            ['year' => 2025, 'month' => 3, 'search_volume' => 14800],
            ['year' => 2025, 'month' => 4, 'search_volume' => 11200],
            // ... 12 mesi
        ],
        'keyword_intent'    => null,         // KP non fornisce intent, mantenuto da cache
    ],
]
```

### Nuovo campo: cpc_low

Keyword Planner fornisce un range CPC (low/high). I provider attuali hanno solo un valore CPC.

- `cpc` → mappato a `highTopOfPageBidMicros` (comparabile con CPC degli altri provider)
- `cpc_low` → mappato a `lowTopOfPageBidMicros` (dato aggiuntivo, NULL per altri provider)

---

## 4. Database

### 4.1 Nuova tabella: kp_usage

Rate limiting separato per Keyword Planner. Prefisso `kp_` (non `ga_`) perché il service è cross-modulo, non specifico di ads-analyzer (Golden Rule #4).

```sql
CREATE TABLE kp_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    operations_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.2 Modifiche a st_keyword_volumes

```sql
ALTER TABLE st_keyword_volumes
    ADD COLUMN provider VARCHAR(30) DEFAULT 'unknown' AFTER location_code,
    ADD COLUMN cpc_low DECIMAL(10,2) NULL AFTER provider;
```

### 4.3 Modifiche a st_keywords

```sql
ALTER TABLE st_keywords
    ADD COLUMN cpc_low DECIMAL(10,2) NULL AFTER cpc;
```

### 4.4 Location mapping

La cascade esistente passa country code stringa ('IT', 'US'). Il service converte internamente:

```php
private function countryToLocationConstant(string $countryCode): string
{
    // Country code ISO 3166-1 alpha-2 → Google Ads geoTargetConstant
    $map = [
        'IT' => 2380, 'US' => 2840, 'GB' => 2826,
        'DE' => 2276, 'FR' => 2250, 'ES' => 2724,
        'BR' => 2076, 'AU' => 2036, 'CA' => 2124,
        'NL' => 2528, 'PT' => 2620, 'CH' => 2756,
        // ... estendibile
    ];
    $code = $map[strtoupper($countryCode)] ?? 2380; // default Italia
    return "geoTargetConstants/{$code}";
}
```

### 4.5 Language mapping

```php
private function countryToLanguageConstant(string $countryCode): string
{
    // Country code → lingua principale Google Ads
    $map = [
        'IT' => 'languageConstants/1004', // Italiano
        'US' => 'languageConstants/1000', // Inglese
        'GB' => 'languageConstants/1000', // Inglese
        'DE' => 'languageConstants/1001', // Tedesco
        'FR' => 'languageConstants/1002', // Francese
        'ES' => 'languageConstants/1003', // Spagnolo
        'BR' => 'languageConstants/1014', // Portoghese
        'PT' => 'languageConstants/1014', // Portoghese
        // ... estendibile
    ];
    return $map[strtoupper($countryCode)] ?? 'languageConstants/1004'; // default italiano
}
```

### 4.6 Cache lookup

La cache in `st_keyword_volumes` usa la chiave `(keyword, location_code)`. Con il nuovo campo `provider`:

```php
// Cache lookup: accetta dati da QUALSIASI provider se freschi
// (non solo da KP — se RapidAPI ha dati freschi di ieri, non richiama KP)
$cached = Database::fetch(
    "SELECT data, provider, cpc_low FROM st_keyword_volumes
     WHERE keyword = ? AND location_code = ? AND updated_at > DATE_SUB(NOW(), INTERVAL ? DAY)",
    [$keyword, $locationCode, $cacheTtlDays]
);

if ($cached) {
    return json_decode($cached['data'], true); // Cache hit, qualsiasi provider
}

// Cache miss → chiama KP API, salva con provider = 'keyword_planner'
```

Il campo `provider` serve per analytics (sapere da dove vengono i dati), non per filtrare la cache. Questo evita chiamate API ridondanti quando un altro provider ha già dati freschi.

---

## 5. Rate Limiting

### 5.1 Tabella kp_usage

Contatore separato dalle operazioni campagne (tabella `ga_api_usage` del design ads-analyzer).

### 5.2 Limiti

| Limite | Default | Configurabile |
|--------|---------|---------------|
| Per-utente/giorno | 100 chiamate | `kp_daily_limit_per_user` |
| Globale/giorno | 5.000 chiamate | `kp_daily_limit_global` |

Una "chiamata" = 1 request API. Ogni request può contenere fino a ~700 keyword (batch).

### 5.3 Logica

```php
private function checkRateLimit(): bool
{
    $today = date('Y-m-d');

    // Check per-utente (usa $this->userId)
    $userCount = Database::fetch(
        "SELECT operations_count FROM kp_usage WHERE user_id = ? AND date = ?",
        [$this->userId, $today]
    );
    $userLimit = Settings::get('kp_daily_limit_per_user', 100);
    if ($userCount && $userCount['operations_count'] >= $userLimit) {
        return false;
    }

    // Check globale
    $globalCount = Database::fetch(
        "SELECT SUM(operations_count) as total FROM kp_usage WHERE date = ?",
        [$today]
    );
    $globalLimit = Settings::get('kp_daily_limit_global', 5000);
    if ($globalCount && $globalCount['total'] >= $globalLimit) {
        return false;
    }

    return true;
}
```

### 5.4 Database::reconnect()

Dopo ogni chiamata API Google Ads (come da Golden Rule #10):

```php
$response = $this->gadsService->generateKeywordHistoricalMetrics($params);
Database::reconnect(); // SEMPRE dopo chiamata API esterna
```

### 5.5 Formato errore (compatibile cascade)

```php
// Successo
['success' => true, 'data' => ['keyword1' => [...], ...]]

// Errore generico
['success' => false, 'error' => 'Google Ads API error: PERMISSION_DENIED']

// Rate limit
['success' => false, 'error' => 'Keyword Planner: quota giornaliera esaurita (utente)']

// Non configurato
['success' => false, 'error' => 'Keyword Planner non configurato']
```

La cascade in `Keyword::updateSearchVolumesForIds()` controlla `$result['success']` — se `false`, passa al provider successivo. Nessuna modifica necessaria alla logica cascade.

---

## 6. Integrazione seo-tracking

### 6.1 Cascade in Keyword::getAllVolumeServices()

```php
public static function getAllVolumeServices(): array
{
    $provider = ModuleLoader::getSetting('seo-tracking', 'volume_provider', 'auto');
    $services = [];

    // Se provider specifico selezionato, restituisci solo quello
    if ($provider !== 'auto') {
        // ... logica esistente per provider specifico ...
        // Aggiungere case 'keyword_planner'
        if ($provider === 'keyword_planner') {
            return ['keyword_planner' => new KeywordPlannerService()];
        }
        // ... altri provider ...
    }

    // Auto mode: Keyword Planner primo (se configurato)
    $kpService = new KeywordPlannerService();
    if ($kpService->isConfigured()) {
        $services['keyword_planner'] = $kpService;
    }

    // ... poi RapidAPI, DataForSEO, KeywordsEverywhere (codice esistente invariato)

    return $services;
}
```

### 6.2 Salvataggio cpc_low

In `Keyword::updateSearchVolumesForIds()`, aggiungere il salvataggio di `cpc_low` quando il provider lo fornisce:

```php
// Dopo aver ricevuto risultati dal provider
if (isset($data['cpc_low'])) {
    // Salva in st_keywords
    Database::query(
        "UPDATE st_keywords SET cpc_low = ? WHERE id = ?",
        [$data['cpc_low'], $keywordId]
    );
}
```

### 6.3 Visualizzazione CPC range

Nelle view seo-tracking dove si mostra CPC, se `cpc_low` è disponibile mostrare il range:

```
€0.85 - €2.35  (invece di solo €2.35)
```

---

## 7. Integrazione keyword-research

### 7.1 Espansione seed keywords

`KeywordInsightService` oggi usa RapidAPI per `keySuggest()` e `expandSeeds()`. Si aggiunge Keyword Planner come sorgente prioritaria:

```php
// In controller Research/Architecture/Editorial
// Prima: KeywordInsightService::expandSeeds()
// Dopo:
$kpService = new KeywordPlannerService();
$ideas = $kpService->generateKeywordIdeas(
    seedKeywords: $seeds,
    url: $targetUrl,
    language: $languageConstant,
    geoTargets: [$locationConstant],
    limit: 200
);

if (empty($ideas)) {
    // Fallback a KeywordInsightService
    $ideas = $insightService->expandSeeds($seeds, $location, $language);
}
```

### 7.2 Arricchimento volumi

Quando keyword-research mostra volumi nelle tabelle risultati, usa `getHistoricalMetrics()` per dati precisi:

```php
$metrics = $kpService->getHistoricalMetrics($keywordTexts, $language, [$location]);
```

---

## 8. Settings Admin

Aggiunti nella tabella `settings` (gestiti da admin panel):

| Key | Default | Descrizione |
|-----|---------|-------------|
| `kp_enabled` | `false` | Abilita Keyword Planner come provider |
| `kp_daily_limit_per_user` | `100` | Max chiamate KP per utente/giorno |
| `kp_daily_limit_global` | `5000` | Max chiamate KP globali/giorno |
| `kp_default_language` | `languageConstants/1004` | Lingua default (Italiano) |
| `kp_default_location` | `geoTargetConstants/2380` | Location default (Italia) |
| `kp_cache_ttl_days` | `7` | TTL cache in giorni |

Le credenziali MCC (`gads_developer_token`, `gads_mcc_customer_id`) sono quelle già previste nel design Google Ads API. Non servono settings aggiuntivi per le credenziali.

**Nota**: il refresh token MCC va nella tabella `google_oauth_tokens` con `service = 'google_ads_mcc'` e `user_id = 0` (account piattaforma, non utente).

---

## 9. Autenticazione MCC

### 9.1 Token MCC piattaforma

Il Keyword Planner usa l'account MCC di piattaforma. Serve un OAuth one-time fatto dall'admin:

1. Admin va in `/admin/settings` → sezione "Google Ads API"
2. Clicca "Connetti Account MCC"
3. OAuth flow con scope `adwords` → salva refresh token in `google_oauth_tokens` con `user_id = 0, service = 'google_ads_mcc'`
4. Il `KeywordPlannerService` usa questo token per tutte le chiamate

### 9.2 GoogleAdsService: modalità MCC

`GoogleAdsService` oggi richiede `$userId` nel costruttore per cercare il token OAuth dell'utente. Si aggiunge un factory method statico per la modalità MCC:

```php
class GoogleAdsService
{
    // Esistente: per-utente (ads-analyzer)
    public function __construct(int $userId, string $customerId) {}

    // Nuovo: MCC piattaforma (Keyword Planner)
    public static function forMcc(): self
    {
        $mccId = Settings::get('gads_mcc_customer_id');
        $instance = new self(0, $mccId); // userId=0 → token MCC
        return $instance;
    }
}
```

Il metodo `getAccessToken()` già gestisce il refresh automatico. Con `userId=0` cerca in `google_oauth_tokens WHERE user_id = 0 AND service = 'google_ads_mcc'`.

---

## 10. API Logging

Tutte le chiamate Keyword Planner loggate via `ApiLoggerService`:

```php
ApiLoggerService::log('google_keyword_planner', $endpoint, $request, $response, $httpCode, $startTime, [
    'module' => $callingModule, // 'seo-tracking' o 'keyword-research'
    'cost' => 0,
    'context' => "keywords: " . count($keywords)
]);
```

Provider: `google_keyword_planner` (separato da `google_ads` per le operazioni campagne).

---

## 11. File da creare / modificare

### Nuovi file

| File | Descrizione |
|------|-------------|
| `services/KeywordPlannerService.php` | Service centralizzato Keyword Planner |
| `database/migrations/xxx_keyword_planner.sql` | Migration DB (kp_usage, ALTER st_keywords, ALTER st_keyword_volumes) |

### File da modificare

| File | Modifiche |
|------|-----------|
| `services/GoogleAdsService.php` | Factory method `forMcc()`, metodi `generateKeywordHistoricalMetrics()` e `generateKeywordIdeas()` |
| `modules/seo-tracking/models/Keyword.php` | Aggiungere KP in `getAllVolumeServices()`, gestione `cpc_low` |
| `modules/keyword-research/controllers/ResearchController.php` | Usare KP per suggestions (fallback a KeywordInsight) |
| `modules/keyword-research/controllers/ArchitectureController.php` | Idem |
| `modules/keyword-research/controllers/EditorialController.php` | Idem |
| `modules/seo-tracking/views/` | Mostrare CPC range dove disponibile |
| `modules/keyword-research/views/` | Mostrare CPC range dove disponibile |
| `admin/controllers/AdminController.php` | Settings KP nel pannello admin |

### File NON toccati

- `services/RapidApiKeywordService.php` — invariato
- `services/DataForSeoService.php` — invariato
- `services/KeywordsEverywhereService.php` — invariato
- `modules/keyword-research/services/KeywordInsightService.php` — invariato (usato come fallback)
- Tutte le view e controller di ads-analyzer — invariati

---

## 12. Fasi di implementazione

### Fase 1: Infrastruttura
1. Migration DB (kp_usage, ALTER st_keywords, ALTER st_keyword_volumes)
2. `GoogleAdsService::forMcc()` factory method
3. Metodi KP in GoogleAdsService (generateKeywordHistoricalMetrics, generateKeywordIdeas)
4. `KeywordPlannerService` con rate limiting, cache, normalizzazione

### Fase 2: Integrazione seo-tracking
5. `Keyword::getAllVolumeServices()` — aggiungere KP come primo provider
6. Gestione `cpc_low` in salvataggio e visualizzazione
7. Setting `volume_provider` → aggiungere opzione 'keyword_planner'

### Fase 3: Integrazione keyword-research
8. Controller Research/Architecture/Editorial → KP per suggestions
9. Fallback a KeywordInsightService

### Fase 4: Admin & Settings
10. Settings admin per KP (enabled, limiti, defaults)
11. OAuth MCC one-time nel pannello admin
12. Pagina usage/stats KP nell'admin

### Fase 5: Docs
13. Documentazione utente aggiornata
14. Data model aggiornato

---

## 13. Rischi e mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Quota KP esaurita | Fallback automatico a provider esistenti (cascade invariata) |
| MCC token scaduto/revocato | Refresh automatico + alert admin se fallisce |
| Dati KP non disponibili per locale raro | Fallback a provider esistenti che coprono più locali |
| Keyword Planner non restituisce intent | Mantenere intent da cache se presente, altrimenti null (non è un dato critico) |
| Batch > 700 keyword | Split automatico in chunk da 700 |
| Costi API Google Ads | Keyword Planner è gratuito per account con spesa attiva (MCC) |

---

*Design document Keyword Planner Integration — 2026-03-12*
