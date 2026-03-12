# Keyword Planner Integration — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate Google Keyword Planner as the priority volume provider (volumes, CPC range, seasonality, keyword suggestions) across seo-tracking and keyword-research modules, using the platform MCC account.

**Architecture:** New `KeywordPlannerService` in `services/` uses `GoogleAdsService::forMcc()` factory to call Keyword Planner endpoints with platform MCC credentials. Inserts as first provider in existing cascade (`KP → RapidAPI → DataForSEO → KE`). Separate rate limiting via `kp_usage` table. No OAuth per-utente required.

**Tech Stack:** PHP 8+, Google Ads API v20 REST, MySQL, existing GoogleAdsService/GoogleOAuthService

**Spec:** `docs/plans/2026-03-12-keyword-planner-integration-design.md`

---

## Chunk 1: Infrastructure (Database + GoogleAdsService MCC + KeywordPlannerService)

### Task 1: Database Migration

**Files:**
- Create: `database/migrations/2026_03_12_keyword_planner.sql`

- [ ] **Step 1: Create migration file**

```sql
-- Keyword Planner Integration Migration
-- 2026-03-12

-- Rate limiting table (cross-module, kp_ prefix)
CREATE TABLE IF NOT EXISTS kp_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    operations_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add provider and cpc_low to keyword volumes cache
ALTER TABLE st_keyword_volumes
    ADD COLUMN IF NOT EXISTS provider VARCHAR(30) DEFAULT 'unknown' AFTER location_code,
    ADD COLUMN IF NOT EXISTS cpc_low DECIMAL(10,2) NULL AFTER provider;

-- Add cpc_low to tracked keywords (DECIMAL(10,2) come cpc esistente)
ALTER TABLE st_keywords
    ADD COLUMN IF NOT EXISTS cpc_low DECIMAL(10,2) NULL AFTER cpc;
```

- [ ] **Step 2: Run migration locally**

```bash
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit < database/migrations/2026_03_12_keyword_planner.sql
```

Expected: no errors. Verify:
```bash
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit -e "DESCRIBE kp_usage;"
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit -e "SHOW COLUMNS FROM st_keyword_volumes LIKE 'provider';"
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit -e "SHOW COLUMNS FROM st_keywords LIKE 'cpc_low';"
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_03_12_keyword_planner.sql
git commit -m "feat(keyword-planner): add migration for kp_usage, cpc_low columns"
```

---

### Task 2: GoogleAdsService — MCC Factory Method + Keyword Planner Endpoints

**Files:**
- Modify: `services/GoogleAdsService.php` (lines 44-54 constructor, line 206 existing stub, lines 454-468 loadTokens, ~line 409 refreshTokenIfNeeded, ~line 297 incrementUsage, ~line 423 checkRateLimit, ~lines 303/318 ApiLoggerService)

**Context:** The constructor currently requires `int $userId, string $customerId`. We need a `forMcc()` static factory that creates an instance using platform MCC credentials (userId=0, service='google_ads_mcc'). Also need `generateKeywordHistoricalMetrics()` method (the existing `generateKeywordIdeas()` at line 206 needs updating). **CRITICAL:** Multiple methods hardcode `'google_ads'` service and `'ads-analyzer'` module — all must be made MCC-aware.

- [ ] **Step 1: Add isMccMode property and forMcc() factory method**

Add property after class fields:
```php
private bool $isMccMode = false;
private string $module = 'ads-analyzer';
```

Add after line 54 (end of constructor):

```php
/**
 * Factory: crea istanza per MCC piattaforma (Keyword Planner, no OAuth utente).
 * Token MCC in google_oauth_tokens con user_id=0, service='google_ads_mcc'.
 */
public static function forMcc(): self
{
    $mccId = \Core\Settings::get('gads_mcc_customer_id');
    if (empty($mccId)) {
        throw new \RuntimeException('MCC Customer ID non configurato (gads_mcc_customer_id)');
    }
    $instance = new self(0, $mccId, $mccId);
    $instance->isMccMode = true;
    $instance->module = 'keyword-planner';
    return $instance;
}
```

- [ ] **Step 2: Update loadTokens() to support MCC service type**

In `loadTokens()` (line ~454), the service type is hardcoded to `'google_ads'`. Modify to support MCC:

Find the line:
```php
$row = Database::fetch($sql, [$this->userId, 'google_ads']);
```

Replace with:
```php
$service = $this->isMccMode ? 'google_ads_mcc' : 'google_ads';
$row = Database::fetch($sql, [$this->userId, $service]);
```

- [ ] **Step 2b: Update refreshTokenIfNeeded() for MCC service type**

In `refreshTokenIfNeeded()` (line ~409), the UPDATE query also hardcodes `service = 'google_ads'`. Apply same fix:

Find:
```php
WHERE user_id = ? AND service = 'google_ads'
```

Replace with:
```php
$service = $this->isMccMode ? 'google_ads_mcc' : 'google_ads';
// ... use $service in the WHERE clause
```

- [ ] **Step 2c: Skip ads-analyzer rate limiting for MCC calls**

In `request()` method (~line 297), `$this->incrementUsage()` calls `ApiUsage::increment($this->userId)` which writes to `ga_api_usage`. Similarly `checkRateLimit()` (~line 423) checks `ga_api_usage`. For MCC mode (userId=0), skip both:

Wrap the rate-limit calls:
```php
if (!$this->isMccMode) {
    $this->incrementUsage();  // solo per ads-analyzer user calls
}
```

And similarly for `checkRateLimit()`:
```php
if (!$this->isMccMode) {
    // existing rate limit check
}
```

KP rate limiting is handled separately by `KeywordPlannerService` via `kp_usage` table.

- [ ] **Step 2d: Update ApiLoggerService module for MCC calls**

In `request()` (~lines 303 and 318), `ApiLoggerService::log()` hardcodes `'module' => 'ads-analyzer'`. Change to use `$this->module`:

```php
ApiLoggerService::log('google_ads', $endpoint, ..., [
    'module' => $this->module,  // 'ads-analyzer' o 'keyword-planner'
    ...
]);
```

- [ ] **Step 2e: Verify no other callers of generateKeywordIdeas()**

Before changing the signature, check that nothing else calls the old `generateKeywordIdeas(array $params)`:

```bash
cd /c/laragon/www/seo-toolkit && grep -rn "generateKeywordIdeas" --include="*.php" .
```

If the only caller is the stub itself, safe to change. If other callers exist, update them.

- [ ] **Step 3: Add generateKeywordHistoricalMetrics() method**

Add before the existing `generateKeywordIdeas()` method (line 206):

```php
/**
 * Keyword Planner: volumi storici per keyword specifiche.
 *
 * @param string[] $keywords Lista keyword (max ~700 per batch)
 * @param string $language Es. 'languageConstants/1004' (italiano)
 * @param string[] $geoTargets Es. ['geoTargetConstants/2380'] (Italia)
 * @return array Risultati con metriche per keyword
 */
public function generateKeywordHistoricalMetrics(array $keywords, string $language, array $geoTargets): array
{
    $url = self::BASE_URL . '/customers/' . $this->customerId . ':generateKeywordHistoricalMetrics';
    $body = [
        'keywords' => $keywords,
        'language' => $language,
        'geoTargetConstants' => $geoTargets,
        'keywordPlanNetwork' => 'GOOGLE_SEARCH',
    ];
    return $this->request('POST', $url, $body, 'keyword historical metrics');
}
```

- [ ] **Step 4: Update existing generateKeywordIdeas() to accept structured params**

Replace the existing stub at line 206:

```php
/**
 * Keyword Planner: genera idee keyword da seed o URL.
 *
 * @param string[] $seedKeywords Seed keywords
 * @param string|null $url URL sorgente (opzionale)
 * @param string $language Es. 'languageConstants/1004'
 * @param string[] $geoTargets Es. ['geoTargetConstants/2380']
 * @return array Risultati con keyword suggerite e metriche
 */
public function generateKeywordIdeas(
    array $seedKeywords = [],
    ?string $url = null,
    string $language = 'languageConstants/1004',
    array $geoTargets = ['geoTargetConstants/2380']
): array {
    $apiUrl = self::BASE_URL . '/customers/' . $this->customerId . ':generateKeywordIdeas';

    $seed = [];
    if (!empty($seedKeywords)) {
        $seed['keywords'] = $seedKeywords;
    }
    if (!empty($url)) {
        $seed['url'] = $url;
    }

    $body = [
        'language' => $language,
        'geoTargetConstants' => $geoTargets,
        'keywordPlanNetwork' => 'GOOGLE_SEARCH',
    ];

    // Scegli il tipo di seed appropriato
    if (!empty($seedKeywords) && !empty($url)) {
        $body['keywordAndUrlSeed'] = $seed;
    } elseif (!empty($url)) {
        $body['urlSeed'] = ['url' => $url];
    } elseif (!empty($seedKeywords)) {
        $body['keywordSeed'] = ['keywords' => $seedKeywords];
    }

    return $this->request('POST', $apiUrl, $body, 'keyword ideas');
}
```

- [ ] **Step 5: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l services/GoogleAdsService.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add services/GoogleAdsService.php
git commit -m "feat(keyword-planner): add forMcc() factory and KP endpoints to GoogleAdsService"
```

---

### Task 3: KeywordPlannerService — Core Service

**Files:**
- Create: `services/KeywordPlannerService.php`

**Context:** Cross-module service. Must implement `isConfigured()` and `getSearchVolumes(array $keywords, string $countryCode = 'IT')` to be compatible with the existing cascade in `Keyword::getAllVolumeServices()`. Internally uses `GoogleAdsService::forMcc()`. Handles rate limiting via `kp_usage` table, caching via `st_keyword_volumes`, batch chunking for >700 keywords.

- [ ] **Step 1: Create KeywordPlannerService.php**

```php
<?php

namespace Services;

use Core\Auth;
use Core\Database;
use Core\Settings;
use Core\Logger;
use Services\ApiLoggerService;
use Services\GoogleAdsService;

class KeywordPlannerService
{
    private int $userId;
    private int $cacheTtlDays;

    /** Country code → Google Ads geoTargetConstant ID */
    private array $locationMap = [
        'IT' => 2380, 'US' => 2840, 'GB' => 2826,
        'DE' => 2276, 'FR' => 2250, 'ES' => 2724,
        'BR' => 2076, 'AU' => 2036, 'CA' => 2124,
        'NL' => 2528, 'PT' => 2620, 'CH' => 2756,
        'AT' => 2040, 'BE' => 2056, 'SE' => 2752,
        'NO' => 2578, 'DK' => 2208, 'FI' => 2246,
        'PL' => 2616, 'CZ' => 2203, 'RO' => 2642,
        'HU' => 2348, 'GR' => 2300, 'HR' => 2191,
        'IE' => 2372, 'MX' => 2484, 'AR' => 2032,
        'CL' => 2152, 'CO' => 2170, 'JP' => 2392,
        'KR' => 2410, 'IN' => 2356, 'RU' => 2643,
    ];

    /** Country code → Google Ads languageConstant */
    private array $languageMap = [
        'IT' => 'languageConstants/1004',  // Italiano
        'US' => 'languageConstants/1000',  // Inglese
        'GB' => 'languageConstants/1000',  // Inglese
        'AU' => 'languageConstants/1000',  // Inglese
        'CA' => 'languageConstants/1000',  // Inglese
        'IE' => 'languageConstants/1000',  // Inglese
        'DE' => 'languageConstants/1001',  // Tedesco
        'AT' => 'languageConstants/1001',  // Tedesco
        'CH' => 'languageConstants/1001',  // Tedesco
        'FR' => 'languageConstants/1002',  // Francese
        'BE' => 'languageConstants/1002',  // Francese
        'ES' => 'languageConstants/1003',  // Spagnolo
        'MX' => 'languageConstants/1003',  // Spagnolo
        'AR' => 'languageConstants/1003',  // Spagnolo
        'CL' => 'languageConstants/1003',  // Spagnolo
        'CO' => 'languageConstants/1003',  // Spagnolo
        'BR' => 'languageConstants/1014',  // Portoghese
        'PT' => 'languageConstants/1014',  // Portoghese
        'NL' => 'languageConstants/1010',  // Olandese
        'SE' => 'languageConstants/1015',  // Svedese
        'NO' => 'languageConstants/1013',  // Norvegese
        'DK' => 'languageConstants/1009',  // Danese
        'FI' => 'languageConstants/1011',  // Finlandese
        'PL' => 'languageConstants/1030',  // Polacco
        'CZ' => 'languageConstants/1021',  // Ceco
        'RO' => 'languageConstants/1032',  // Rumeno
        'HU' => 'languageConstants/1024',  // Ungherese
        'GR' => 'languageConstants/1022',  // Greco
        'HR' => 'languageConstants/1039',  // Croato
        'JP' => 'languageConstants/1005',  // Giapponese
        'KR' => 'languageConstants/1012',  // Coreano
        'IN' => 'languageConstants/1023',  // Hindi
        'RU' => 'languageConstants/1031',  // Russo
    ];

    public function __construct(?int $userId = null)
    {
        $user = Auth::user();
        $this->userId = $userId ?? ($user['id'] ?? 0);
        $this->cacheTtlDays = (int) Settings::get('kp_cache_ttl_days', 7);
    }

    /**
     * Verifica se il service è configurato e utilizzabile.
     */
    public function isConfigured(): bool
    {
        return (bool) Settings::get('kp_enabled', false)
            && !empty(Settings::get('gads_developer_token'))
            && !empty(Settings::get('gads_mcc_customer_id'));
    }

    /**
     * Cascade-compatible: volumi per keyword.
     * Firma identica agli altri provider (RapidApiKeywordService, DataForSeoService, etc.).
     *
     * @param string[] $keywords
     * @param string $countryCode ISO 3166-1 alpha-2 ('IT', 'US', etc.)
     * @return array ['success' => bool, 'data' => [...], 'error' => string|null]
     */
    public function getSearchVolumes(array $keywords, string $countryCode = 'IT'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Keyword Planner non configurato'];
        }

        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Keyword Planner: quota giornaliera esaurita'];
        }

        $locationCode = $this->countryToLocationCode($countryCode);
        $locationConstant = "geoTargetConstants/{$locationCode}";
        $languageConstant = $this->countryToLanguageConstant($countryCode);

        // Check cache — accetta dati da qualsiasi provider se freschi
        $uncachedKeywords = [];
        $cachedData = [];
        foreach ($keywords as $kw) {
            $cached = $this->getCachedVolume($kw, $locationCode);
            if ($cached !== null) {
                $cachedData[$kw] = $cached;
            } else {
                $uncachedKeywords[] = $kw;
            }
        }

        // Se tutte in cache, ritorna subito
        if (empty($uncachedKeywords)) {
            return [
                'success' => true,
                'data' => $cachedData,
                'cached' => count($cachedData),
                'fetched' => 0,
                'provider' => 'keyword_planner',
            ];
        }

        // Fetch da API in chunk da 700
        $allResults = [];
        $chunks = array_chunk($uncachedKeywords, 700);

        try {
            $gads = GoogleAdsService::forMcc();

            foreach ($chunks as $chunk) {
                $response = $gads->generateKeywordHistoricalMetrics(
                    $chunk,
                    $languageConstant,
                    [$locationConstant]
                );
                Database::reconnect();

                $parsed = $this->parseHistoricalMetrics($response);
                $allResults = array_merge($allResults, $parsed);

                $this->incrementUsage();
            }
        } catch (\Exception $e) {
            Logger::channel('api')->error("[KeywordPlanner] API error: " . $e->getMessage());

            // Risultati parziali: restituisci quello che abbiamo (cache + chunk completati)
            if (!empty($allResults) || !empty($cachedData)) {
                $this->saveToCache($allResults, $locationCode);
                return [
                    'success' => true,
                    'data' => array_merge($cachedData, $allResults),
                    'cached' => count($cachedData),
                    'fetched' => count($allResults),
                    'provider' => 'keyword_planner',
                    'warning' => 'Risultati parziali: ' . $e->getMessage(),
                ];
            }

            return ['success' => false, 'error' => 'Google Ads API error: ' . $e->getMessage()];
        }

        // Salva in cache
        $this->saveToCache($allResults, $locationCode);

        return [
            'success' => true,
            'data' => array_merge($cachedData, $allResults),
            'cached' => count($cachedData),
            'fetched' => count($allResults),
            'provider' => 'keyword_planner',
        ];
    }

    /**
     * Volumi storici per keyword specifiche (metodo diretto, non cascade).
     *
     * @param string[] $keywords
     * @param string $language languageConstants/...
     * @param string[] $geoTargets geoTargetConstants/...
     * @return array [keyword => [search_volume, cpc, cpc_low, competition, ...]]
     */
    public function getHistoricalMetrics(
        array $keywords,
        string $language = 'languageConstants/1004',
        array $geoTargets = ['geoTargetConstants/2380']
    ): array {
        if (!$this->isConfigured()) {
            return [];
        }

        $allResults = [];
        $chunks = array_chunk($keywords, 700);

        try {
            $gads = GoogleAdsService::forMcc();

            foreach ($chunks as $chunk) {
                if (!$this->checkRateLimit()) {
                    Logger::channel('api')->warning("[KeywordPlanner] Rate limit raggiunto durante batch");
                    break;
                }

                $response = $gads->generateKeywordHistoricalMetrics($chunk, $language, $geoTargets);
                Database::reconnect();

                $parsed = $this->parseHistoricalMetrics($response);
                $allResults = array_merge($allResults, $parsed);

                $this->incrementUsage();
            }
        } catch (\Exception $e) {
            Logger::channel('api')->error("[KeywordPlanner] getHistoricalMetrics error: " . $e->getMessage());
        }

        return $allResults;
    }

    /**
     * Genera idee keyword da seed keywords o URL.
     *
     * @param string[] $seedKeywords
     * @param string|null $url
     * @param string $language
     * @param string[] $geoTargets
     * @param int $limit Max risultati
     * @return array ['success' => bool, 'data' => [...]]
     */
    public function generateKeywordIdeas(
        array $seedKeywords = [],
        ?string $url = null,
        string $language = 'languageConstants/1004',
        array $geoTargets = ['geoTargetConstants/2380'],
        int $limit = 200
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Keyword Planner non configurato'];
        }

        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Keyword Planner: quota giornaliera esaurita'];
        }

        try {
            $gads = GoogleAdsService::forMcc();
            $response = $gads->generateKeywordIdeas($seedKeywords, $url, $language, $geoTargets);
            Database::reconnect();

            $this->incrementUsage();

            $ideas = $this->parseKeywordIdeas($response, $limit);

            return [
                'success' => true,
                'data' => $ideas,
                'count' => count($ideas),
                'provider' => 'keyword_planner',
            ];
        } catch (\Exception $e) {
            Logger::channel('api')->error("[KeywordPlanner] generateKeywordIdeas error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Google Ads API error: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // Parsing
    // =========================================================================

    /**
     * Parse risposta generateKeywordHistoricalMetrics.
     */
    private function parseHistoricalMetrics(array $response): array
    {
        $results = [];
        $items = $response['results'] ?? [];

        foreach ($items as $item) {
            $text = $item['text'] ?? null;
            $metrics = $item['keywordMetrics'] ?? [];

            if (empty($text) || empty($metrics)) {
                continue;
            }

            $results[strtolower($text)] = $this->normalizeMetrics($metrics);
        }

        return $results;
    }

    /**
     * Parse risposta generateKeywordIdeas.
     */
    private function parseKeywordIdeas(array $response, int $limit): array
    {
        $ideas = [];
        $items = $response['results'] ?? [];

        foreach ($items as $item) {
            if (count($ideas) >= $limit) {
                break;
            }

            $text = $item['text'] ?? null;
            $metrics = $item['keywordIdeaMetrics'] ?? [];

            if (empty($text)) {
                continue;
            }

            $normalized = $this->normalizeMetrics($metrics);
            $normalized['keyword'] = $text;
            $ideas[] = $normalized;
        }

        // Ordina per volume decrescente
        usort($ideas, fn($a, $b) => ($b['search_volume'] ?? 0) - ($a['search_volume'] ?? 0));

        return $ideas;
    }

    /**
     * Normalizza metriche Google Ads → formato standard cascade.
     */
    private function normalizeMetrics(array $metrics): array
    {
        $competitionMap = [
            'LOW' => 'LOW',
            'MEDIUM' => 'MEDIUM',
            'HIGH' => 'HIGH',
            'UNSPECIFIED' => null,
            'UNKNOWN' => null,
        ];

        $competitionIndex = $metrics['competitionIndex'] ?? null;
        $competitionEnum = $metrics['competition'] ?? 'UNSPECIFIED';

        // Monthly search volumes (stagionalità)
        $monthlySearches = [];
        foreach ($metrics['monthlySearchVolumes'] ?? [] as $mv) {
            $monthlySearches[] = [
                'year' => (int) ($mv['year'] ?? 0),
                'month' => (int) ($mv['month'] ?? 0),
                'search_volume' => (int) ($mv['monthlySearches'] ?? 0),
            ];
        }

        return [
            'search_volume' => (int) ($metrics['avgMonthlySearches'] ?? 0),
            'cpc' => isset($metrics['highTopOfPageBidMicros'])
                ? round((int) $metrics['highTopOfPageBidMicros'] / 1_000_000, 2)
                : null,
            'cpc_low' => isset($metrics['lowTopOfPageBidMicros'])
                ? round((int) $metrics['lowTopOfPageBidMicros'] / 1_000_000, 2)
                : null,
            'competition' => $competitionIndex !== null
                ? round($competitionIndex / 100, 2)
                : null,
            'competition_level' => $competitionMap[$competitionEnum] ?? null,
            'monthly_searches' => $monthlySearches,
            'keyword_intent' => null, // KP non fornisce intent
        ];
    }

    // =========================================================================
    // Cache
    // =========================================================================

    /**
     * Cerca keyword in cache (qualsiasi provider, entro TTL).
     */
    private function getCachedVolume(string $keyword, int $locationCode): ?array
    {
        $row = Database::fetch(
            "SELECT data, cpc_low FROM st_keyword_volumes
             WHERE keyword = ? AND location_code = ?
             AND updated_at > DATE_SUB(NOW(), INTERVAL ? DAY)",
            [strtolower($keyword), $locationCode, $this->cacheTtlDays]
        );

        if (!$row) {
            return null;
        }

        $data = json_decode($row['data'], true);
        if (!$data) {
            return null;
        }

        // Aggiungi cpc_low dalla colonna dedicata se presente
        if ($row['cpc_low'] !== null && !isset($data['cpc_low'])) {
            $data['cpc_low'] = (float) $row['cpc_low'];
        }

        return $data;
    }

    /**
     * Salva risultati in cache st_keyword_volumes.
     */
    private function saveToCache(array $results, int $locationCode): void
    {
        foreach ($results as $keyword => $data) {
            $jsonData = json_encode([
                'search_volume' => $data['search_volume'] ?? 0,
                'cpc' => $data['cpc'],
                'competition' => $data['competition'],
                'competition_level' => $data['competition_level'],
                'monthly_searches' => $data['monthly_searches'] ?? [],
                'keyword_intent' => $data['keyword_intent'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            Database::execute(
                "INSERT INTO st_keyword_volumes (keyword, location_code, provider, cpc_low, data, updated_at)
                 VALUES (?, ?, 'keyword_planner', ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    provider = 'keyword_planner',
                    cpc_low = VALUES(cpc_low),
                    data = VALUES(data),
                    updated_at = NOW()",
                [strtolower($keyword), $locationCode, $data['cpc_low'], $jsonData]
            );
        }
    }

    // =========================================================================
    // Rate Limiting
    // =========================================================================

    private function checkRateLimit(): bool
    {
        $today = date('Y-m-d');

        // Check per-utente
        if ($this->userId > 0) {
            $userRow = Database::fetch(
                "SELECT operations_count FROM kp_usage WHERE user_id = ? AND date = ?",
                [$this->userId, $today]
            );
            $userLimit = (int) Settings::get('kp_daily_limit_per_user', 100);
            if ($userRow && (int) $userRow['operations_count'] >= $userLimit) {
                return false;
            }
        }

        // Check globale
        $globalRow = Database::fetch(
            "SELECT SUM(operations_count) as total FROM kp_usage WHERE date = ?",
            [$today]
        );
        $globalLimit = (int) Settings::get('kp_daily_limit_global', 5000);
        if ($globalRow && (int) ($globalRow['total'] ?? 0) >= $globalLimit) {
            return false;
        }

        return true;
    }

    private function incrementUsage(): void
    {
        $today = date('Y-m-d');
        Database::execute(
            "INSERT INTO kp_usage (user_id, date, operations_count)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE operations_count = operations_count + 1",
            [$this->userId, $today]
        );
    }

    // =========================================================================
    // Mapping helpers
    // =========================================================================

    private function countryToLocationCode(string $countryCode): int
    {
        return $this->locationMap[strtoupper($countryCode)] ?? 2380;
    }

    private function countryToLanguageConstant(string $countryCode): string
    {
        return $this->languageMap[strtoupper($countryCode)] ?? 'languageConstants/1004';
    }

    /**
     * Convenience: genera idee keyword passando solo country code (per keyword-research controllers).
     * Gestisce internamente la conversione country→geoTarget e country→language.
     */
    public function generateKeywordIdeasForCountry(
        array $seedKeywords = [],
        ?string $url = null,
        string $countryCode = 'IT',
        int $limit = 200
    ): array {
        $locationCode = $this->countryToLocationCode($countryCode);
        $locationConstant = "geoTargetConstants/{$locationCode}";
        $languageConstant = $this->countryToLanguageConstant($countryCode);

        return $this->generateKeywordIdeas($seedKeywords, $url, $languageConstant, [$locationConstant], $limit);
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l services/KeywordPlannerService.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add services/KeywordPlannerService.php
git commit -m "feat(keyword-planner): add KeywordPlannerService with cascade, cache, rate limiting"
```

---

## Chunk 2: seo-tracking Integration (Cascade + CPC Low + Views)

### Task 4: Integrate KP into Volume Cascade

**Files:**
- Modify: `modules/seo-tracking/models/Keyword.php` (lines 851-888 `getAllVolumeServices()`, lines 709-843 `updateSearchVolumesForIds()`)

**Context:** `getAllVolumeServices()` returns an array of provider instances. The cascade in `updateSearchVolumesForIds()` calls `$service->getSearchVolumes($keywordTexts, $locationCode)` on each. We add KP as first provider. The `$locationCode` variable in the cascade is a string country code like `'IT'`. **Note:** There may also be a `getSpecificVolumeService()` helper and/or a `getVolumeService()` (singular) method — both need the `keyword_planner` case.

- [ ] **Step 1: Add KP as first provider in getAllVolumeServices()**

At the top of `Keyword.php`, add the `use` for KeywordPlannerService if not already present:

```php
use Services\KeywordPlannerService;
```

In `getAllVolumeServices()`, find the section that handles specific provider selection (around line 862, inside `getSpecificVolumeService()` if it's a separate method). Add a case for `keyword_planner`:

```php
if ($configured === 'keyword_planner') {
    $kp = new KeywordPlannerService();
    return $kp->isConfigured() ? ['KeywordPlanner' => $kp] : [];
}
```

Then in the auto mode section (around line 870, BEFORE RapidAPI), add:

```php
// Keyword Planner primo (se configurato)
$kpService = new KeywordPlannerService();
if ($kpService->isConfigured()) {
    $providers['KeywordPlanner'] = $kpService;
}
```

Also check if `getSpecificVolumeService($provider)` exists as a separate method. If so, add:
```php
case 'keyword_planner':
    $kp = new KeywordPlannerService();
    return $kp->isConfigured() ? $kp : null;
```

And if `getVolumeService()` (singular, returning one service) exists, add KP there too following the same pattern.

- [ ] **Step 2: Handle cpc_low in updateSearchVolumesForIds()**

In `updateSearchVolumesForIds()`, find the UPDATE query for `st_keywords` (around line 806-813). Add `cpc_low` to the SET clause.

Find the existing UPDATE (approximately):
```php
Database::execute("UPDATE {$this->table} SET
    search_volume = ?,
    cpc = ?,
    competition = ?,
    competition_level = ?,
    keyword_intent = ?,
    volume_updated_at = NOW()
    WHERE id = ?",
    [$volume, $cpc, $competition, $competitionLevel, $intent, $kwId]);
```

Change to:
```php
Database::execute("UPDATE {$this->table} SET
    search_volume = ?,
    cpc = ?,
    cpc_low = ?,
    competition = ?,
    competition_level = ?,
    keyword_intent = ?,
    volume_updated_at = NOW()
    WHERE id = ?",
    [$volume, $cpc, $volumeData['cpc_low'] ?? null, $competition, $competitionLevel, $intent, $kwId]);
```

The exact variable names may differ — match the existing code's variable names for `$volume`, `$cpc`, etc. and add `$volumeData['cpc_low'] ?? null` in the corresponding position.

- [ ] **Step 3: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/seo-tracking/models/Keyword.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/seo-tracking/models/Keyword.php
git commit -m "feat(seo-tracking): add KeywordPlanner as priority volume provider in cascade"
```

---

### Task 5: Add keyword_planner Option to module.json

**Files:**
- Modify: `modules/seo-tracking/module.json` (lines 137-149, volume_provider setting)

- [ ] **Step 1: Add keyword_planner option**

In the `volume_provider` setting options array, add `keyword_planner` as first option after `auto`:

```json
{"value": "auto", "label": "Auto (cascata: KP → RapidAPI → DataForSEO → Keywords Everywhere)"},
{"value": "keyword_planner", "label": "Google Keyword Planner (MCC)"},
{"value": "rapidapi", "label": "RapidAPI"},
{"value": "dataforseo", "label": "DataForSEO"},
{"value": "keywordseverywhere", "label": "Keywords Everywhere"}
```

Update the auto label to include KP in the cascade description.

- [ ] **Step 2: Commit**

```bash
git add modules/seo-tracking/module.json
git commit -m "feat(seo-tracking): add Keyword Planner option to volume_provider setting"
```

---

### Task 6: CPC Range Display in Views

**Files:**
- Modify: `modules/seo-tracking/views/keywords/index.php` (lines 423 header, lines 557-561 cell)

**Context:** Currently shows single CPC value. When `cpc_low` is available, show range `€0.85 - €2.35`.

- [ ] **Step 1: Update CPC cell rendering**

Find the CPC cell (around line 557-561):

```php
<td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
    <?php if (!empty($kw['cpc'])): ?>
        €<?= number_format($kw['cpc'], 2) ?>
    <?php else: ?>
        <span class="text-slate-400 dark:text-slate-500">-</span>
    <?php endif; ?>
</td>
```

Replace with:

```php
<td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
    <?php if (!empty($kw['cpc'])): ?>
        <?php if (!empty($kw['cpc_low']) && $kw['cpc_low'] != $kw['cpc']): ?>
            <span title="Range CPC: €<?= number_format($kw['cpc_low'], 2) ?> - €<?= number_format($kw['cpc'], 2) ?>">
                €<?= number_format($kw['cpc_low'], 2) ?> - €<?= number_format($kw['cpc'], 2) ?>
            </span>
        <?php else: ?>
            €<?= number_format($kw['cpc'], 2) ?>
        <?php endif; ?>
    <?php else: ?>
        <span class="text-slate-400 dark:text-slate-500">-</span>
    <?php endif; ?>
</td>
```

- [ ] **Step 2: Ensure cpc_low is fetched in queries**

Check that the query in `Keyword::allWithPositions()` or `Keyword::allWithVolumes()` includes `cpc_low` in the SELECT. If it uses `SELECT *`, no change needed. If it lists columns explicitly, add `cpc_low`.

- [ ] **Step 3: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/seo-tracking/views/keywords/index.php
```

- [ ] **Step 4: Test manually**

Open `http://localhost/seo-toolkit` → login (`admin@seo-toolkit.local` / `admin123`) → SEO Tracking → un progetto con keyword → verifica che la colonna CPC renderizza senza errori. Se cpc_low è NULL, mostra solo CPC singolo come prima.

- [ ] **Step 5: Commit**

```bash
git add modules/seo-tracking/views/keywords/index.php
git commit -m "feat(seo-tracking): display CPC range when cpc_low available from Keyword Planner"
```

---

## Chunk 3: keyword-research Integration

### Task 7: KP as Priority Source for Keyword Suggestions

**Files:**
- Modify: `modules/keyword-research/controllers/ResearchController.php` (where KeywordInsightService::expandSeeds/keySuggest is called)
- Modify: `modules/keyword-research/controllers/ArchitectureController.php` (same pattern)
- Modify: `modules/keyword-research/controllers/EditorialController.php` (same pattern)

**Context:** Each controller uses `KeywordInsightService` for seed expansion via `keySuggest()` (Research line ~170, Architecture line ~151, Editorial line ~219). We add KP as priority with fallback to KeywordInsight. KP's `generateKeywordIdeas()` returns suggestions with volumes, CPC range, competition — richer data than KeywordInsight.

**Important:** The `KeywordPlannerService` handles location/language mapping internally — the controllers just pass the country code string (e.g. `'IT'`). Do NOT reference non-existent `$this->getLocationConstant()` helpers.

- [ ] **Step 1: Update ResearchController**

Find where `KeywordInsightService::keySuggest()` is called (around line 170). Add KP before it:

```php
use Services\KeywordPlannerService;

// Prima: prova Keyword Planner per suggestions
$kpService = new KeywordPlannerService();
$kpResults = null;
if ($kpService->isConfigured()) {
    // generateKeywordIdeasForCountry gestisce internamente country→geoTarget/language
    $kpResults = $kpService->generateKeywordIdeasForCountry(
        seedKeywords: $seeds,
        url: $targetUrl ?? null,
        countryCode: $location,  // 'IT', 'US', etc.
        limit: 200
    );
}

if ($kpResults && $kpResults['success'] && !empty($kpResults['data'])) {
    $expandedKeywords = $kpResults['data'];
} else {
    // Fallback a KeywordInsightService::keySuggest() (invariato)
    // ... codice esistente inalterato ...
}
```

**IMPORTANT:** Read the actual controller code first. The variable names (`$seeds`, `$location`, `$targetUrl`) and the flow depend on the existing code. The principle is: wrap the existing `keySuggest()` call in a KP-first-then-fallback pattern. The convenience method `generateKeywordIdeasForCountry()` handles all location/language mapping internally — just pass the country code string.

- [ ] **Step 2: Apply same pattern to ArchitectureController**

Same logic as Step 1, adapted to ArchitectureController's flow.

- [ ] **Step 3: Apply same pattern to EditorialController**

Same logic as Step 1, adapted to EditorialController's flow.

- [ ] **Step 4: Verify syntax on all 3 files**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/keyword-research/controllers/ResearchController.php
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/keyword-research/controllers/ArchitectureController.php
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/keyword-research/controllers/EditorialController.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/keyword-research/controllers/ResearchController.php
git add modules/keyword-research/controllers/ArchitectureController.php
git add modules/keyword-research/controllers/EditorialController.php
git commit -m "feat(keyword-research): use Keyword Planner for suggestions with KeywordInsight fallback"
```

---

## Chunk 4: Admin Settings + MCC OAuth

### Task 8: Admin Settings for Keyword Planner

**Files:**
- Modify: Admin settings (insert KP settings into `settings` table)

**Context:** KP settings are global (not module-specific), stored in `settings` table. The admin panel at `/admin/settings` reads from this table. We need to add the default values.

- [ ] **Step 1: Add default settings via migration**

Append to the migration file `database/migrations/2026_03_12_keyword_planner.sql`:

```sql
-- Keyword Planner settings (defaults)
INSERT INTO settings (`key`, `value`) VALUES
    ('kp_enabled', '0'),
    ('kp_daily_limit_per_user', '100'),
    ('kp_daily_limit_global', '5000'),
    ('kp_default_language', 'languageConstants/1004'),
    ('kp_default_location', 'geoTargetConstants/2380'),
    ('kp_cache_ttl_days', '7')
ON DUPLICATE KEY UPDATE `key` = `key`;
```

- [ ] **Step 2: Run the updated migration**

```bash
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root seo_toolkit -e "INSERT INTO settings (\`key\`, \`value\`) VALUES ('kp_enabled', '0'), ('kp_daily_limit_per_user', '100'), ('kp_daily_limit_global', '5000'), ('kp_default_language', 'languageConstants/1004'), ('kp_default_location', 'geoTargetConstants/2380'), ('kp_cache_ttl_days', '7') ON DUPLICATE KEY UPDATE \`key\` = \`key\`;"
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_03_12_keyword_planner.sql
git commit -m "feat(keyword-planner): add admin settings defaults"
```

---

### Task 9: MCC OAuth Token Setup in Admin

**Files:**
- Modify: `services/GoogleOAuthService.php` (add scope constant, MCC token save method)
- Modify: `admin/controllers/AdminController.php` (add KP settings section, MCC connect action)

**Context:** The admin needs a one-time OAuth flow to authorize the platform MCC account. This stores a refresh token in `google_oauth_tokens` with `user_id=0, service='google_ads_mcc'`. The GoogleOAuthService already handles OAuth flows for GSC — we extend it.

- [ ] **Step 1: Add MCC scope and save method to GoogleOAuthService**

Add constant:
```php
const SCOPE_GOOGLE_ADS = 'https://www.googleapis.com/auth/adwords';
```

Add method to save MCC token:
```php
/**
 * Salva token OAuth per MCC piattaforma (user_id=0).
 */
public function saveMccToken(string $accessToken, string $refreshToken, int $expiresIn): void
{
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

    Database::execute(
        "INSERT INTO google_oauth_tokens (user_id, service, access_token, refresh_token, token_expires_at, created_at, updated_at)
         VALUES (0, 'google_ads_mcc', ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            access_token = VALUES(access_token),
            refresh_token = VALUES(refresh_token),
            token_expires_at = VALUES(token_expires_at),
            updated_at = NOW()",
        [$accessToken, $refreshToken, $expiresAt]
    );
}
```

- [ ] **Step 2: Add KP settings section to AdminController**

In the admin settings page, add a "Google Keyword Planner" section with:
- Toggle: `kp_enabled` (Attiva/Disattiva)
- Input: `kp_daily_limit_per_user` (Limite giornaliero per utente)
- Input: `kp_daily_limit_global` (Limite giornaliero globale)
- Input: `kp_cache_ttl_days` (Giorni cache)
- Button: "Connetti Account MCC Google Ads" (avvia OAuth con scope adwords)
- Status: mostra se token MCC è presente e valido

The exact implementation depends on how the admin settings page is structured. Follow the existing pattern for other settings sections.

- [ ] **Step 3: Add OAuth callback handling for MCC**

The OAuth callback `/oauth/google/callback` already exists. Add handling for `state` containing `mcc_connect`:

```php
if ($state === 'mcc_connect') {
    $oauth = new GoogleOAuthService();
    $tokens = $oauth->exchangeCode($code);
    if (!isset($tokens['error'])) {
        $oauth->saveMccToken($tokens['access_token'], $tokens['refresh_token'], $tokens['expires_in']);
        // Redirect to admin settings con success message
    }
}
```

- [ ] **Step 4: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l services/GoogleOAuthService.php
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l admin/controllers/AdminController.php
```

- [ ] **Step 5: Commit**

```bash
git add services/GoogleOAuthService.php admin/controllers/AdminController.php
git commit -m "feat(keyword-planner): MCC OAuth flow and admin settings for Keyword Planner"
```

---

## Chunk 5: API Logging + Docs

### Task 10: API Logging + kp_usage Cleanup

**Context:** API logging is handled by Task 2 Step 2d (module field uses `$this->module` which is `'keyword-planner'` for MCC calls). No additional logging code needed. But we need cleanup for `kp_usage` table.

- [ ] **Step 1: Add kp_usage cleanup to cron/cleanup-data.php**

Add cleanup for `kp_usage` table (keep last 30 days):

```php
// Pulizia kp_usage (Keyword Planner rate limiting)
Database::execute("DELETE FROM kp_usage WHERE date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
```

Add this in the existing cleanup-data.php where other tables are cleaned.

- [ ] **Step 2: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l cron/cleanup-data.php
```

- [ ] **Step 3: Commit**

```bash
git add cron/cleanup-data.php
git commit -m "feat(keyword-planner): add kp_usage cleanup to daily cron"
```

---

### Task 11: Update Documentation

**Files:**
- Modify: `shared/views/docs/seo-tracking.php` (add KP info to volume provider docs)
- Modify: `shared/views/docs/keyword-research.php` (add KP info)

- [ ] **Step 1: Update seo-tracking docs**

Add a section about Keyword Planner as volume provider:
- Mention it's the most accurate source (Google native data)
- Explain CPC range (low-high) vs single CPC
- Note: requires admin to connect MCC account
- Fallback to other providers if not configured

- [ ] **Step 2: Update keyword-research docs**

Add a section about KP for keyword suggestions:
- Mentions KP provides suggestions with precise volumes
- Fallback to RapidAPI KeywordInsight if KP not configured

- [ ] **Step 3: Verify syntax**

```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/docs/seo-tracking.php
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l shared/views/docs/keyword-research.php
```

- [ ] **Step 4: Commit**

```bash
git add shared/views/docs/seo-tracking.php shared/views/docs/keyword-research.php
git commit -m "docs: update user docs with Keyword Planner integration info"
```

---

## Chunk 6: Production Deploy

### Task 12: Deploy to Production

- [ ] **Step 1: Run migration on production**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < database/migrations/2026_03_12_keyword_planner.sql"
```

- [ ] **Step 2: Deploy code**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && git pull origin main"
```

- [ ] **Step 3: Configure MCC OAuth in admin**

Go to `https://ainstein.it/admin/settings` → sezione Keyword Planner → "Connetti Account MCC" → completa OAuth flow → attiva `kp_enabled`.

- [ ] **Step 4: Verify**

Go to SEO Tracking → un progetto con keyword → clicca "Aggiorna Volumi" → verifica nei API Logs che le chiamate vanno a `google_ads` con context `keyword historical metrics`.

---

## Dependencies Between Tasks

```
Task 1 (Migration) ──→ Task 2 (GoogleAdsService)
                   ──→ Task 3 (KeywordPlannerService) ──→ Task 4 (Cascade)
                                                       ──→ Task 7 (KR controllers)
Task 4 (Cascade) ──→ Task 5 (module.json)
                 ──→ Task 6 (CPC view)
Task 2 ──→ Task 9 (MCC OAuth)
Task 8 (Settings) → can run in parallel with Tasks 4-7
Task 11 (Docs) → after all features complete
Task 12 (Deploy) → after everything
```

**Parallelizable tasks:** Tasks 4+5+6 (seo-tracking) can run in parallel with Task 7 (keyword-research), after Task 3 is complete.
