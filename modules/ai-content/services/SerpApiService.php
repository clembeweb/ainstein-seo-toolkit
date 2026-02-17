<?php

namespace Modules\AiContent\Services;

use Core\Settings;
use Services\ScraperService;
use Services\ApiLoggerService;
use Core\Logger;

/**
 * SerpApiService
 *
 * Service for extracting SERP results via SerpAPI or Serper.dev.
 * Provider is selected from module settings (serp_provider).
 */
class SerpApiService
{
    private string $apiKey;
    private string $provider;
    private ?string $fallbackApiKey = null;
    private ?string $fallbackProvider = null;
    private ScraperService $scraper;

    public function __construct(?string $apiKey = null)
    {
        // Determine provider from module settings (default: serper)
        $this->provider = getModuleSetting('ai-content', 'serp_provider', 'serper');
        $this->scraper = new ScraperService();

        if ($this->provider === 'serper') {
            $this->apiKey = $apiKey ?? Settings::get('serper_api_key', '');
            if (empty($this->apiKey)) {
                throw new \Exception('Serper.dev API key non configurata. Vai in Admin > Impostazioni > Essenziali > Rank Tracking');
            }
            // Setup fallback: SerpAPI
            $serpApiKey = getModuleSetting('ai-content', 'serpapi_key', '');
            if (!empty($serpApiKey)) {
                $this->fallbackProvider = 'serpapi';
                $this->fallbackApiKey = $serpApiKey;
            }
        } else {
            $this->provider = 'serpapi';
            $this->apiKey = $apiKey ?? getModuleSetting('ai-content', 'serpapi_key', '');
            if (empty($this->apiKey)) {
                throw new \Exception('SerpAPI key non configurata. Vai in Admin > Moduli > AI Content > Impostazioni');
            }
            // Setup fallback: Serper.dev
            $serperKey = Settings::get('serper_api_key', '');
            if (!empty($serperKey)) {
                $this->fallbackProvider = 'serper';
                $this->fallbackApiKey = $serperKey;
            }
        }
    }

    /**
     * Search Google and extract SERP results + PAA
     * Con fallback automatico sull'altro provider se il primario fallisce.
     *
     * @param string $keyword Search query
     * @param string $language Language code (it, en, es, de, fr)
     * @param string $location Location name
     * @return array{organic: array, paa: array, related: array}
     */
    public function search(string $keyword, string $language = 'it', string $location = 'Italy'): array
    {
        // Prova provider primario
        try {
            if ($this->provider === 'serper') {
                return $this->searchWithSerper($keyword, $language, $location, $this->apiKey);
            }
            return $this->searchWithSerpApi($keyword, $language, $location, $this->apiKey);
        } catch (\Exception $primaryError) {
            // Se non c'e' fallback, rilancia l'errore
            if (!$this->fallbackProvider || !$this->fallbackApiKey) {
                throw $primaryError;
            }

            Logger::channel('api')->warning("[SerpService] Primario ({$this->provider}) fallito - Provo fallback ({$this->fallbackProvider})", ['error' => $primaryError->getMessage()]);

            // Prova fallback
            try {
                if ($this->fallbackProvider === 'serper') {
                    return $this->searchWithSerper($keyword, $language, $location, $this->fallbackApiKey);
                }
                return $this->searchWithSerpApi($keyword, $language, $location, $this->fallbackApiKey);
            } catch (\Exception $fallbackError) {
                // Entrambi falliti: lancia errore con info su entrambi
                throw new \Exception(
                    "Primario ({$this->provider}): " . $primaryError->getMessage() .
                    " | Fallback ({$this->fallbackProvider}): " . $fallbackError->getMessage()
                );
            }
        }
    }

    /**
     * Search via SerpAPI
     */
    private function searchWithSerpApi(string $keyword, string $language, string $location, ?string $apiKey = null): array
    {
        $key = $apiKey ?? $this->apiKey;
        $params = [
            'engine' => 'google',
            'q' => $keyword,
            'location' => $location,
            'hl' => $language,
            'gl' => $this->getCountryCode($location),
            'google_domain' => $this->getGoogleDomain($language),
            'num' => 10,
            'api_key' => $key
        ];

        $url = 'https://serpapi.com/search.json?' . http_build_query($params);

        $startTime = microtime(true);
        $response = $this->makeRequest($url);

        ApiLoggerService::log('serpapi', '/search.json', ['q' => $keyword, 'location' => $location], $response['data'] ?? null, $response['success'] ? 200 : 500, $startTime, [
            'module' => 'ai-content',
            'context' => 'serp_extraction',
        ]);

        if (!$response['success']) {
            throw new \Exception('Errore SerpAPI: ' . ($response['error'] ?? 'Unknown error'));
        }

        $data = $response['data'];

        return [
            'organic' => $this->parseOrganicResults($data['organic_results'] ?? []),
            'paa' => $this->parsePaaResults($data['related_questions'] ?? []),
            'related' => $this->parseRelatedSearches($data['related_searches'] ?? [])
        ];
    }

    /**
     * Search via Serper.dev
     */
    private function searchWithSerper(string $keyword, string $language, string $location, ?string $apiKey = null): array
    {
        $key = $apiKey ?? $this->apiKey;
        $postData = [
            'q' => $keyword,
            'gl' => $this->getCountryCode($location),
            'hl' => $language,
            'num' => 10,
        ];

        $jsonData = json_encode($postData, JSON_UNESCAPED_UNICODE);

        $startTime = microtime(true);

        $ch = curl_init('https://google.serper.dev/search');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-KEY: ' . $key,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        ApiLoggerService::log('serper', '/search', $postData, $data, $httpCode, $startTime, [
            'module' => 'ai-content',
            'context' => 'serp_extraction',
        ]);

        if ($curlError) {
            throw new \Exception('Errore connessione Serper.dev: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errorMsg = $data['message'] ?? "Errore Serper.dev (HTTP {$httpCode})";
            throw new \Exception($errorMsg);
        }

        return [
            'organic' => $this->parseSerperOrganicResults($data['organic'] ?? []),
            'paa' => $this->parseSerperPaaResults($data['peopleAlsoAsk'] ?? []),
            'related' => $this->parseSerperRelatedSearches($data['relatedSearches'] ?? [])
        ];
    }

    /**
     * Parse organic results from SerpAPI format
     */
    private function parseOrganicResults(array $results): array
    {
        $parsed = [];

        foreach ($results as $index => $item) {
            if (empty($item['link'])) {
                continue;
            }

            $parsed[] = [
                'position' => $index + 1,
                'title' => $item['title'] ?? '',
                'url' => $item['link'],
                'snippet' => $item['snippet'] ?? '',
                'domain' => parse_url($item['link'], PHP_URL_HOST)
            ];
        }

        return $parsed;
    }

    /**
     * Parse organic results from Serper.dev format
     */
    private function parseSerperOrganicResults(array $results): array
    {
        $parsed = [];

        foreach ($results as $index => $item) {
            if (empty($item['link'])) {
                continue;
            }

            $parsed[] = [
                'position' => $item['position'] ?? ($index + 1),
                'title' => $item['title'] ?? '',
                'url' => $item['link'],
                'snippet' => $item['snippet'] ?? '',
                'domain' => parse_url($item['link'], PHP_URL_HOST)
            ];
        }

        return $parsed;
    }

    /**
     * Parse PAA from SerpAPI format
     */
    private function parsePaaResults(array $results): array
    {
        $parsed = [];

        foreach ($results as $index => $item) {
            $parsed[] = [
                'position' => $index + 1,
                'question' => $item['question'] ?? '',
                'snippet' => $item['snippet'] ?? ($item['answer'] ?? '')
            ];
        }

        return $parsed;
    }

    /**
     * Parse PAA from Serper.dev format
     */
    private function parseSerperPaaResults(array $results): array
    {
        $parsed = [];

        foreach ($results as $index => $item) {
            $parsed[] = [
                'position' => $index + 1,
                'question' => $item['question'] ?? '',
                'snippet' => $item['snippet'] ?? ($item['title'] ?? '')
            ];
        }

        return $parsed;
    }

    /**
     * Parse related searches from SerpAPI format
     */
    private function parseRelatedSearches(array $results): array
    {
        return array_map(function ($item) {
            return $item['query'] ?? '';
        }, $results);
    }

    /**
     * Parse related searches from Serper.dev format
     */
    private function parseSerperRelatedSearches(array $results): array
    {
        return array_map(function ($item) {
            return $item['query'] ?? '';
        }, $results);
    }

    /**
     * Get Google domain for language
     */
    private function getGoogleDomain(string $lang): string
    {
        $domains = [
            'it' => 'google.it',
            'en' => 'google.com',
            'es' => 'google.es',
            'de' => 'google.de',
            'fr' => 'google.fr',
            'pt' => 'google.pt',
            'nl' => 'google.nl'
        ];

        return $domains[$lang] ?? 'google.com';
    }

    /**
     * Get country code from location name
     */
    private function getCountryCode(string $location): string
    {
        $codes = [
            'Italy' => 'it',
            'United States' => 'us',
            'United Kingdom' => 'uk',
            'Spain' => 'es',
            'Germany' => 'de',
            'France' => 'fr',
            'Portugal' => 'pt',
            'Netherlands' => 'nl'
        ];

        return $codes[$location] ?? 'us';
    }

    /**
     * Make HTTP request via shared ScraperService (used by SerpAPI)
     */
    private function makeRequest(string $url): array
    {
        $result = $this->scraper->fetchJson($url, [
            'timeout' => 30,
            'headers' => ['Accept: application/json'],
        ]);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Request failed'
            ];
        }

        if (isset($result['data']['error'])) {
            return [
                'success' => false,
                'error' => $result['data']['error']
            ];
        }

        return [
            'success' => true,
            'data' => $result['data']
        ];
    }

    /**
     * Get the active provider name
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $result = $this->search('test', 'en', 'United States');

            $providerLabel = $this->provider === 'serper' ? 'Serper.dev' : 'SerpAPI';

            return [
                'success' => true,
                'message' => "Connessione {$providerLabel} funzionante",
                'results_count' => count($result['organic']),
                'provider' => $this->provider
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
