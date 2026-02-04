<?php

namespace Services;

use Core\Settings;
use Core\Database;

/**
 * KeywordsEverywhereService
 *
 * Provider alternativo per volumi di ricerca, CPC e competition.
 * Usato come fallback quando DataForSEO non è configurato o non ha crediti.
 *
 * API Docs: https://keywordseverywhere.com/api-documentation.html
 */
class KeywordsEverywhereService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.keywordseverywhere.com/v1';

    // Cache durata: 7 giorni (volumi non cambiano spesso)
    private int $cacheDays = 7;

    // Max keyword per richiesta API
    private int $maxKeywordsPerRequest = 100;

    // Mapping country code -> parametri Keywords Everywhere
    private array $countryMap = [
        'IT' => ['country' => 'it', 'currency' => 'eur'],
        'US' => ['country' => 'us', 'currency' => 'usd'],
        'GB' => ['country' => 'uk', 'currency' => 'gbp'],
        'UK' => ['country' => 'uk', 'currency' => 'gbp'],
        'DE' => ['country' => 'de', 'currency' => 'eur'],
        'FR' => ['country' => 'fr', 'currency' => 'eur'],
        'ES' => ['country' => 'es', 'currency' => 'eur'],
        'NL' => ['country' => 'nl', 'currency' => 'eur'],
        'BE' => ['country' => 'be', 'currency' => 'eur'],
        'AT' => ['country' => 'at', 'currency' => 'eur'],
        'CH' => ['country' => 'ch', 'currency' => 'chf'],
        'PT' => ['country' => 'pt', 'currency' => 'eur'],
        'PL' => ['country' => 'pl', 'currency' => 'pln'],
        'SE' => ['country' => 'se', 'currency' => 'sek'],
        'NO' => ['country' => 'no', 'currency' => 'nok'],
        'DK' => ['country' => 'dk', 'currency' => 'dkk'],
        'FI' => ['country' => 'fi', 'currency' => 'eur'],
        'IE' => ['country' => 'ie', 'currency' => 'eur'],
        'AU' => ['country' => 'au', 'currency' => 'aud'],
        'NZ' => ['country' => 'nz', 'currency' => 'nzd'],
        'CA' => ['country' => 'ca', 'currency' => 'cad'],
        'BR' => ['country' => 'br', 'currency' => 'brl'],
        'MX' => ['country' => 'mx', 'currency' => 'mxn'],
        'IN' => ['country' => 'in', 'currency' => 'inr'],
        'JP' => ['country' => 'jp', 'currency' => 'jpy'],
    ];

    public function __construct()
    {
        $this->apiKey = Settings::get('keywordseverywhere_api_key');
    }

    /**
     * Verifica se API è configurata
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Ottieni volumi di ricerca per array di keyword
     *
     * @param array $keywords Lista keyword
     * @param string $countryCode Codice paese ISO (IT, US, DE, etc.)
     * @return array
     */
    public function getSearchVolumes(array $keywords, string $countryCode = 'IT'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Keywords Everywhere non configurato. Vai in Admin > Impostazioni'];
        }

        if (empty($keywords)) {
            return ['success' => true, 'data' => []];
        }

        // Ottieni parametri per il paese
        $countryParams = $this->getCountryParams($countryCode);
        $country = $countryParams['country'];
        $currency = $countryParams['currency'];

        // Converti country code a location_code per la cache (compatibilita' con DataForSEO)
        $locationCode = $this->getLocationCodeForCache($countryCode);

        // Normalizza keyword (rimuovi duplicati, trim)
        $keywords = array_unique(array_map('trim', $keywords));
        $keywords = array_filter($keywords);

        // Controlla cache prima
        $results = [];
        $uncachedKeywords = [];

        foreach ($keywords as $keyword) {
            $cached = $this->getFromCache($keyword, $locationCode);
            if ($cached) {
                $results[$keyword] = $cached;
            } else {
                $uncachedKeywords[] = $keyword;
            }
        }

        // Se tutte in cache, ritorna
        if (empty($uncachedKeywords)) {
            return ['success' => true, 'data' => $results, 'from_cache' => true];
        }

        // Chiama API per keyword non in cache (max 100 per chiamata)
        $chunks = array_chunk($uncachedKeywords, $this->maxKeywordsPerRequest);

        foreach ($chunks as $chunk) {
            $apiResult = $this->callKeywordDataApi($chunk, $country, $currency);

            if ($apiResult['success'] && !empty($apiResult['data'])) {
                foreach ($apiResult['data'] as $keyword => $data) {
                    $results[$keyword] = $data;
                    $this->saveToCache($keyword, $locationCode, $data);
                }
            } else {
                // Log errore ma continua
                error_log("[KeywordsEverywhere] API error: " . ($apiResult['error'] ?? 'unknown'));
            }
        }

        return [
            'success' => true,
            'data' => $results,
            'cached' => count($keywords) - count($uncachedKeywords),
            'fetched' => count($uncachedKeywords),
            'provider' => 'KeywordsEverywhere',
        ];
    }

    /**
     * Chiamata API Keywords Everywhere
     */
    private function callKeywordDataApi(array $keywords, string $country, string $currency): array
    {
        $startTime = microtime(true);
        $endpoint = '/get_keyword_data';

        try {
            // Prepara dati POST (form-urlencoded come da docs)
            $postData = [
                'country' => $country,
                'currency' => $currency,
                'dataSource' => 'gkp', // Google Keyword Planner
                'kw' => $keywords,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 60,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                ApiLoggerService::log('keywordseverywhere', $endpoint, $postData, null, 0, $startTime, [
                    'module' => 'seo-tracking',
                    'error' => "CURL error: {$error}",
                    'context' => 'keywords=' . count($keywords),
                ]);
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            $data = json_decode($response, true);

            // Log chiamata
            ApiLoggerService::log('keywordseverywhere', $endpoint, $postData, $data, $httpCode, $startTime, [
                'module' => 'seo-tracking',
                'context' => 'keywords=' . count($keywords) . ', country=' . $country,
            ]);

            if ($httpCode !== 200) {
                $errorMsg = $data['message'] ?? "HTTP {$httpCode}";
                return ['success' => false, 'error' => $errorMsg];
            }

            if (!$data) {
                return ['success' => false, 'error' => 'Invalid JSON response'];
            }

            // Parse risultati Keywords Everywhere
            // Formato risposta: { "data": [ { "keyword": "...", "vol": 1000, "cpc": { "value": "1.5", "currency": "EUR" }, "competition": 0.5, "trend": [...] } ] }
            $results = [];
            $keywordData = $data['data'] ?? [];

            foreach ($keywordData as $item) {
                $keyword = $item['keyword'] ?? '';
                if ($keyword) {
                    // Mappa competition (0-1) a competition_level
                    $competition = $item['competition'] ?? null;
                    $competitionLevel = $this->mapCompetitionLevel($competition);

                    $results[$keyword] = [
                        'search_volume' => (int) ($item['vol'] ?? 0),
                        'cpc' => $this->parseCpc($item['cpc'] ?? null),
                        'competition' => $competition !== null ? round((float) $competition, 4) : null,
                        'competition_level' => $competitionLevel,
                        'monthly_searches' => $this->parseTrend($item['trend'] ?? []),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }

            return ['success' => true, 'data' => $results];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse CPC da formato Keywords Everywhere
     */
    private function parseCpc($cpc): float
    {
        if ($cpc === null) {
            return 0.0;
        }

        if (is_array($cpc)) {
            return (float) ($cpc['value'] ?? 0);
        }

        return (float) $cpc;
    }

    /**
     * Parse trend mensile da Keywords Everywhere
     */
    private function parseTrend(array $trend): array
    {
        // Keywords Everywhere restituisce trend come array di valori mensili
        // Lo convertiamo nel formato compatibile con DataForSEO
        $result = [];
        $months = ['January', 'February', 'March', 'April', 'May', 'June',
                   'July', 'August', 'September', 'October', 'November', 'December'];

        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        foreach ($trend as $index => $value) {
            $monthIndex = ($currentMonth - count($trend) + $index + 12) % 12;
            $year = $currentYear;
            if ($monthIndex > $currentMonth - 1) {
                $year--;
            }

            $result[] = [
                'year' => $year,
                'month' => $months[$monthIndex],
                'search_volume' => (int) $value,
            ];
        }

        return $result;
    }

    /**
     * Mappa competition value a competition level
     */
    private function mapCompetitionLevel(?float $competition): ?string
    {
        if ($competition === null) {
            return null;
        }

        if ($competition <= 0.33) {
            return 'LOW';
        } elseif ($competition <= 0.66) {
            return 'MEDIUM';
        } else {
            return 'HIGH';
        }
    }

    /**
     * Ottieni parametri per paese
     */
    private function getCountryParams(string $countryCode): array
    {
        $countryCode = strtoupper($countryCode);
        return $this->countryMap[$countryCode] ?? $this->countryMap['US'];
    }

    /**
     * Converti country code a location code per la cache
     * Usiamo codici numerici semplici per compatibilità con DataForSEO cache
     */
    private function getLocationCodeForCache(string $countryCode): int
    {
        // Mapping semplificato country -> location code
        // Questi sono i location_code di DataForSEO per compatibilità cache
        $locationCodes = [
            'IT' => 2380,
            'US' => 2840,
            'GB' => 2826,
            'UK' => 2826,
            'DE' => 2276,
            'FR' => 2250,
            'ES' => 2724,
            'NL' => 2528,
            'BE' => 2056,
            'AT' => 2040,
            'CH' => 2756,
            'PT' => 2620,
            'PL' => 2616,
            'SE' => 2752,
            'NO' => 2578,
            'DK' => 2208,
            'FI' => 2246,
            'IE' => 2372,
            'AU' => 2036,
            'NZ' => 2554,
            'CA' => 2124,
            'BR' => 2076,
            'MX' => 2484,
            'IN' => 2356,
            'JP' => 2392,
        ];

        $countryCode = strtoupper($countryCode);
        return $locationCodes[$countryCode] ?? 2840; // Default US
    }

    /**
     * Ottieni singola keyword da cache
     */
    private function getFromCache(string $keyword, int $locationCode): ?array
    {
        try {
            $sql = "SELECT data, updated_at FROM st_keyword_volumes
                    WHERE keyword = ? AND location_code = ?
                    AND updated_at > DATE_SUB(NOW(), INTERVAL ? DAY)";

            $row = Database::fetch($sql, [$keyword, $locationCode, $this->cacheDays]);

            if ($row) {
                return json_decode($row['data'], true);
            }
        } catch (\Exception $e) {
            // Tabella non esiste ancora, ignora
        }

        return null;
    }

    /**
     * Salva in cache
     */
    private function saveToCache(string $keyword, int $locationCode, array $data): void
    {
        try {
            $sql = "INSERT INTO st_keyword_volumes (keyword, location_code, data, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()";

            Database::execute($sql, [$keyword, $locationCode, json_encode($data)]);
        } catch (\Exception $e) {
            // Tabella non esiste ancora, ignora
            error_log("[KeywordsEverywhere] Cache save error: " . $e->getMessage());
        }
    }

    /**
     * Test connessione API
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'API key non configurata'];
        }

        // Testa con una keyword semplice
        $result = $this->callKeywordDataApi(['test'], 'it', 'eur');

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Connessione Keywords Everywhere funzionante',
                'sample' => $result['data']['test'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Ottieni crediti rimanenti
     */
    public function getCredits(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'API key non configurata'];
        }

        $startTime = microtime(true);
        $endpoint = '/account/credits';

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            $data = json_decode($response, true);

            // Log chiamata
            ApiLoggerService::log('keywordseverywhere', $endpoint, [], $data, $httpCode, $startTime, [
                'module' => 'seo-tracking',
                'context' => 'credits_check',
            ]);

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => $data['message'] ?? "HTTP {$httpCode}"];
            }

            return [
                'success' => true,
                'credits' => $data['credits'] ?? 0,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
