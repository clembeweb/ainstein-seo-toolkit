<?php

namespace Services;

use Core\Settings;
use Core\Database;

/**
 * RapidApiKeywordService
 *
 * Provider per volumi di ricerca tramite RapidAPI (Google SEO Keyword Research AI).
 * Usato come provider primario per i volumi di ricerca.
 *
 * API: https://rapidapi.com/developer-developer-default/api/google-seo-keyword-research-ai
 */
class RapidApiKeywordService
{
    private ?string $apiKey;
    private string $apiHost = 'google-seo-keyword-research-ai.p.rapidapi.com';
    private string $baseUrl = 'https://google-seo-keyword-research-ai.p.rapidapi.com';

    // Cache durata: 7 giorni (volumi non cambiano spesso)
    private int $cacheDays = 7;

    // Mapping country code ISO -> parametro API
    private array $countryMap = [
        'IT' => ['country' => 'it', 'language' => 'it'],
        'US' => ['country' => 'us', 'language' => 'en'],
        'GB' => ['country' => 'uk', 'language' => 'en'],
        'UK' => ['country' => 'uk', 'language' => 'en'],
        'DE' => ['country' => 'de', 'language' => 'de'],
        'FR' => ['country' => 'fr', 'language' => 'fr'],
        'ES' => ['country' => 'es', 'language' => 'es'],
        'NL' => ['country' => 'nl', 'language' => 'nl'],
        'BE' => ['country' => 'be', 'language' => 'nl'],
        'AT' => ['country' => 'at', 'language' => 'de'],
        'CH' => ['country' => 'ch', 'language' => 'de'],
        'PT' => ['country' => 'pt', 'language' => 'pt'],
        'PL' => ['country' => 'pl', 'language' => 'pl'],
        'SE' => ['country' => 'se', 'language' => 'sv'],
        'NO' => ['country' => 'no', 'language' => 'no'],
        'DK' => ['country' => 'dk', 'language' => 'da'],
        'FI' => ['country' => 'fi', 'language' => 'fi'],
        'IE' => ['country' => 'ie', 'language' => 'en'],
        'AU' => ['country' => 'au', 'language' => 'en'],
        'NZ' => ['country' => 'nz', 'language' => 'en'],
        'CA' => ['country' => 'ca', 'language' => 'en'],
        'BR' => ['country' => 'br', 'language' => 'pt'],
        'MX' => ['country' => 'mx', 'language' => 'es'],
        'IN' => ['country' => 'in', 'language' => 'en'],
        'JP' => ['country' => 'jp', 'language' => 'ja'],
    ];

    // Mapping country code -> location_code per cache (compatibilita' DataForSEO)
    private array $locationCodes = [
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

    public function __construct()
    {
        $this->apiKey = Settings::get('rapidapi_keyword_key');
    }

    /**
     * Verifica se API e' configurata
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
            return ['success' => false, 'error' => 'RapidAPI Keyword non configurato. Vai in Admin > Impostazioni'];
        }

        if (empty($keywords)) {
            return ['success' => true, 'data' => []];
        }

        // Ottieni parametri per il paese
        $countryParams = $this->getCountryParams($countryCode);
        $country = $countryParams['country'];
        $language = $countryParams['language'];

        // Converti country code a location_code per la cache
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
            return ['success' => true, 'data' => $results, 'from_cache' => true, 'provider' => 'RapidAPI'];
        }

        // Chiama API per keyword non in cache (una alla volta - no batch)
        foreach ($uncachedKeywords as $keyword) {
            $apiResult = $this->callKeywordApi($keyword, $country, $language);

            if ($apiResult['success'] && !empty($apiResult['data'])) {
                $results[$keyword] = $apiResult['data'];
                $this->saveToCache($keyword, $locationCode, $apiResult['data']);
            } else {
                // Log errore ma continua con le altre keyword
                error_log("[RapidApiKeyword] API error for '{$keyword}': " . ($apiResult['error'] ?? 'unknown'));
            }
        }

        return [
            'success' => true,
            'data' => $results,
            'cached' => count($keywords) - count($uncachedKeywords),
            'fetched' => count($uncachedKeywords),
            'provider' => 'RapidAPI',
        ];
    }

    /**
     * Chiamata API RapidAPI per singola keyword
     */
    private function callKeywordApi(string $keyword, string $country, string $language): array
    {
        $startTime = microtime(true);
        $endpoint = '/keyword-research';

        try {
            // Costruisci query string
            $queryParams = http_build_query([
                'keyword' => $keyword,
                'country' => $country,
                'language_code' => $language,
                'network_type' => 'search-only',
            ]);

            $url = $this->baseUrl . $endpoint . '?' . $queryParams;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'x-rapidapi-host: ' . $this->apiHost,
                    'x-rapidapi-key: ' . $this->apiKey,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Prepara payload per log (senza API key)
            $logPayload = [
                'keyword' => $keyword,
                'country' => $country,
                'language_code' => $language,
            ];

            if ($error) {
                ApiLoggerService::log('rapidapi_keyword', $endpoint, $logPayload, null, 0, $startTime, [
                    'module' => 'seo-tracking',
                    'error' => "CURL error: {$error}",
                    'context' => "keyword={$keyword}",
                ]);
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            $data = json_decode($response, true);

            // Log chiamata
            ApiLoggerService::log('rapidapi_keyword', $endpoint, $logPayload, $data, $httpCode, $startTime, [
                'module' => 'seo-tracking',
                'context' => "keyword={$keyword}, country={$country}",
            ]);

            if ($httpCode !== 200) {
                $errorMsg = $data['message'] ?? "HTTP {$httpCode}";
                return ['success' => false, 'error' => $errorMsg];
            }

            if (!$data || !isset($data['result'])) {
                return ['success' => false, 'error' => 'Invalid JSON response'];
            }

            // Parse risultato - cerca la keyword esatta nel primo risultato
            $resultData = $this->parseKeywordResult($keyword, $data['result']);

            if ($resultData === null) {
                return ['success' => false, 'error' => 'Keyword non trovata nei risultati'];
            }

            return ['success' => true, 'data' => $resultData];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse risultato API per estrarre i dati della keyword esatta
     *
     * Campi API reali:
     * - avg_monthly_searches: int (volume medio mensile)
     * - Low CPC / High CPC: string "$0.72" (costo per click)
     * - competition_index: int 0-100 (indice competizione)
     * - competition_value: string "low"/"medium"/"high"
     * - monthly_search_volumes: array con trend mensile
     * - intent: string "commercial"/"informational"/etc.
     */
    private function parseKeywordResult(string $searchKeyword, array $results): ?array
    {
        if (empty($results)) {
            return null;
        }

        // Il primo risultato dovrebbe essere la keyword esatta
        $item = $results[0] ?? null;

        if (!$item) {
            return null;
        }

        // Estrai volume (avg_monthly_searches e' gia' un intero)
        $searchVolume = isset($item['avg_monthly_searches']) ? (int) $item['avg_monthly_searches'] : null;

        // CPC: usa High CPC se disponibile, altrimenti Low CPC
        $cpcRaw = $item['High CPC'] ?? $item['Low CPC'] ?? null;
        $cpc = $this->parseCpc($cpcRaw);

        // Competition: competition_index e' 0-100, convertiamo a 0-1
        $competitionIndex = isset($item['competition_index']) ? (int) $item['competition_index'] : null;
        $competition = $competitionIndex !== null ? round($competitionIndex / 100, 4) : null;

        // Competition level: usa competition_value direttamente (low/medium/high)
        $competitionValue = $item['competition_value'] ?? null;
        $competitionLevel = $this->mapCompetitionValue($competitionValue);

        // Parse trend mensile (monthly_search_volumes)
        $monthlySearches = $this->parseTrend($item['monthly_search_volumes'] ?? []);

        // Intent puo' essere stringa o array - normalizza a stringa
        $intent = $item['intent'] ?? null;
        if (is_array($intent)) {
            $intent = implode(', ', $intent);
        }

        return [
            'search_volume' => $searchVolume,
            'cpc' => $cpc,
            'competition' => $competition,
            'competition_level' => $competitionLevel,
            'monthly_searches' => $monthlySearches,
            'keyword_intent' => $intent,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Parse volume da stringa (es. "31.0M", "1.5K", "500") a numero intero
     */
    private function parseVolume(?string $volume): ?int
    {
        if ($volume === null || $volume === '') {
            return null;
        }

        $volume = trim($volume);

        // Rimuovi eventuali virgole
        $volume = str_replace(',', '', $volume);

        // Controlla suffissi
        $multiplier = 1;
        if (preg_match('/^([\d.]+)\s*([KMB])?$/i', $volume, $matches)) {
            $number = (float) $matches[1];
            $suffix = strtoupper($matches[2] ?? '');

            switch ($suffix) {
                case 'K':
                    $multiplier = 1000;
                    break;
                case 'M':
                    $multiplier = 1000000;
                    break;
                case 'B':
                    $multiplier = 1000000000;
                    break;
            }

            return (int) round($number * $multiplier);
        }

        // Numero semplice
        return (int) $volume;
    }

    /**
     * Parse CPC da stringa (es. "$0.11", "EUR0.15") a float
     */
    private function parseCpc(?string $cpc): float
    {
        if ($cpc === null || $cpc === '') {
            return 0.0;
        }

        // Rimuovi simboli valuta e spazi
        $cpc = preg_replace('/[^\d.,]/', '', $cpc);

        // Gestisci formato europeo (virgola decimale)
        if (strpos($cpc, ',') !== false && strpos($cpc, '.') === false) {
            $cpc = str_replace(',', '.', $cpc);
        }

        return (float) $cpc;
    }

    /**
     * Parse difficulty da stringa (es. "68%") a intero (0-100)
     */
    private function parseDifficulty(?string $difficulty): ?int
    {
        if ($difficulty === null || $difficulty === '') {
            return null;
        }

        // Rimuovi % e spazi
        $difficulty = preg_replace('/[^\d]/', '', $difficulty);

        return (int) $difficulty;
    }

    /**
     * Mappa competition_value (low/medium/high) a competition_level (LOW/MEDIUM/HIGH)
     */
    private function mapCompetitionValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        $mapping = [
            'low' => 'LOW',
            'medium' => 'MEDIUM',
            'high' => 'HIGH',
        ];

        return $mapping[$value] ?? 'MEDIUM';
    }

    /**
     * Parse trend mensile da RapidAPI
     */
    private function parseTrend(array $trend): array
    {
        $result = [];

        foreach ($trend as $item) {
            if (isset($item['searches'])) {
                $result[] = [
                    'year' => $item['year'] ?? date('Y'),
                    'month' => $item['month'] ?? null,
                    'search_volume' => (int) $item['searches'],
                ];
            }
        }

        return $result;
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
     */
    private function getLocationCodeForCache(string $countryCode): int
    {
        $countryCode = strtoupper($countryCode);
        return $this->locationCodes[$countryCode] ?? 2840; // Default US
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
            error_log("[RapidApiKeyword] Cache save error: " . $e->getMessage());
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
        $result = $this->callKeywordApi('test', 'us', 'en');

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Connessione RapidAPI Keyword funzionante',
                'sample' => $result['data'] ?? null,
            ];
        }

        return $result;
    }
}
