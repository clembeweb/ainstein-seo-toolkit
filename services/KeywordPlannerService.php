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
        'IT' => 'languageConstants/1004',
        'US' => 'languageConstants/1000',
        'GB' => 'languageConstants/1000',
        'AU' => 'languageConstants/1000',
        'CA' => 'languageConstants/1000',
        'IE' => 'languageConstants/1000',
        'DE' => 'languageConstants/1001',
        'AT' => 'languageConstants/1001',
        'CH' => 'languageConstants/1001',
        'FR' => 'languageConstants/1002',
        'BE' => 'languageConstants/1002',
        'ES' => 'languageConstants/1003',
        'MX' => 'languageConstants/1003',
        'AR' => 'languageConstants/1003',
        'CL' => 'languageConstants/1003',
        'CO' => 'languageConstants/1003',
        'BR' => 'languageConstants/1014',
        'PT' => 'languageConstants/1014',
        'NL' => 'languageConstants/1010',
        'SE' => 'languageConstants/1015',
        'NO' => 'languageConstants/1013',
        'DK' => 'languageConstants/1009',
        'FI' => 'languageConstants/1011',
        'PL' => 'languageConstants/1030',
        'CZ' => 'languageConstants/1021',
        'RO' => 'languageConstants/1032',
        'HU' => 'languageConstants/1024',
        'GR' => 'languageConstants/1022',
        'HR' => 'languageConstants/1039',
        'JP' => 'languageConstants/1005',
        'KR' => 'languageConstants/1012',
        'IN' => 'languageConstants/1023',
        'RU' => 'languageConstants/1031',
    ];

    public function __construct(?int $userId = null)
    {
        $user = Auth::user();
        $this->userId = $userId ?? ($user['id'] ?? 0);
        $this->cacheTtlDays = (int) Settings::get('kp_cache_ttl_days', 7);
    }

    public function isConfigured(): bool
    {
        return (bool) Settings::get('kp_enabled', false)
            && !empty(Settings::get('gads_developer_token'))
            && !empty(Settings::get('gads_mcc_customer_id'));
    }

    /**
     * Cascade-compatible: volumi per keyword.
     * Firma identica agli altri provider.
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

        if (empty($uncachedKeywords)) {
            return [
                'success' => true,
                'data' => $cachedData,
                'cached' => count($cachedData),
                'fetched' => 0,
                'provider' => 'keyword_planner',
            ];
        }

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

    /**
     * Convenience: genera idee keyword passando solo country code.
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

    // =========================================================================
    // Parsing
    // =========================================================================

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

            $normalized = $this->normalizeMetrics($metrics);
            $normalized['keyword_intent'] = self::classifyIntent($text);
            $results[strtolower($text)] = $normalized;
        }

        return $results;
    }

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
            $normalized['keyword_intent'] = self::classifyIntent($text);
            $ideas[] = $normalized;
        }

        usort($ideas, fn($a, $b) => ($b['search_volume'] ?? 0) - ($a['search_volume'] ?? 0));

        return $ideas;
    }

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
            'keyword_intent' => null,
        ];
    }

    /**
     * Classifica l'intent di una keyword con pattern matching euristico.
     * Usato quando il provider non fornisce intent (es. Google Keyword Planner).
     */
    public static function classifyIntent(string $keyword): string
    {
        $kw = strtolower(trim($keyword));

        // Transactional
        $transactional = [
            'compra', 'comprare', 'acquista', 'acquistare', 'prezzo', 'prezzi',
            'costo', 'costi', 'offerta', 'offerte', 'sconto', 'sconti',
            'economico', 'economici', 'economica', 'economiche',
            'buy', 'price', 'cheap', 'discount', 'deal', 'coupon',
            'ordina', 'ordinare', 'preventivo', 'abbonamento',
        ];
        foreach ($transactional as $t) {
            if (str_contains($kw, $t)) return 'transactional';
        }

        // Commercial investigation
        $commercial = [
            'migliore', 'migliori', 'best', 'top', 'confronto', 'vs',
            'recensione', 'recensioni', 'review', 'reviews', 'opinioni',
            'classifica', 'alternativa', 'alternative', 'quale scegliere',
            'consiglio', 'consigli', 'consigliato', 'consigliati',
            'pro e contro', 'vantaggi', 'svantaggi',
        ];
        foreach ($commercial as $c) {
            if (str_contains($kw, $c)) return 'commercial';
        }

        // Informational
        $informational = [
            'come', 'cosa', 'quando', 'perche', 'perché', 'dove', 'chi',
            'what', 'how', 'why', 'when', 'where', 'who',
            'guida', 'tutorial', 'significato', 'definizione', 'cos\'è',
            'spiegazione', 'differenza', 'esempio', 'esempi',
        ];
        foreach ($informational as $i) {
            if (str_contains($kw, $i)) return 'informational';
        }

        // Navigational (brand-like, short)
        if (str_word_count($kw) <= 2 && !str_contains($kw, ' ')) {
            return 'navigational';
        }

        // Default: commercial (most keyword research queries have commercial intent)
        return 'commercial';
    }

    // =========================================================================
    // Cache
    // =========================================================================

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

        if ($row['cpc_low'] !== null && !isset($data['cpc_low'])) {
            $data['cpc_low'] = (float) $row['cpc_low'];
        }

        return $data;
    }

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
}
