<?php

namespace Modules\AiContent\Services;

use Services\ScraperService;

/**
 * SerpApiService
 *
 * Service for extracting SERP results via SerpAPI
 * Uses shared ScraperService for HTTP requests
 */
class SerpApiService
{
    private string $apiKey;
    private string $baseUrl = 'https://serpapi.com/search.json';
    private ScraperService $scraper;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? getModuleSetting('ai-content', 'serpapi_key', '');
        $this->scraper = new ScraperService();

        if (empty($this->apiKey)) {
            throw new \Exception('SerpAPI key non configurata. Vai in Admin > Moduli > AI Content > Impostazioni');
        }
    }

    /**
     * Search Google and extract SERP results + PAA
     *
     * @param string $keyword Search query
     * @param string $language Language code (it, en, es, de, fr)
     * @param string $location Location name
     * @return array{organic: array, paa: array, related: array}
     */
    public function search(string $keyword, string $language = 'it', string $location = 'Italy'): array
    {
        $params = [
            'engine' => 'google',  // REQUIRED parameter
            'q' => $keyword,
            'location' => $location,
            'hl' => $language,
            'gl' => $this->getCountryCode($location),
            'google_domain' => $this->getGoogleDomain($language),
            'num' => 10,
            'api_key' => $this->apiKey
        ];

        $url = $this->baseUrl . '?' . http_build_query($params);

        $response = $this->makeRequest($url);

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
     * Parse organic search results
     */
    private function parseOrganicResults(array $results): array
    {
        $parsed = [];

        foreach ($results as $index => $item) {
            // Skip non-organic results (ads, featured snippets embedded, etc.)
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
     * Parse People Also Ask questions
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
     * Parse related searches
     */
    private function parseRelatedSearches(array $results): array
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
     * Make HTTP request to SerpAPI via shared ScraperService
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

        // Check for SerpAPI errors in response
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
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $result = $this->search('test', 'en', 'United States');

            return [
                'success' => true,
                'message' => 'Connessione SerpAPI funzionante',
                'results_count' => count($result['organic'])
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
