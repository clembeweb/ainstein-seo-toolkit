<?php

namespace Modules\KeywordResearch\Services;

use Core\Settings;
use Services\ApiLoggerService;

/**
 * KeywordInsightService
 *
 * Wrapper per Google Keyword Insight API (RapidAPI)
 * Host: google-keyword-insight1.p.rapidapi.com
 */
class KeywordInsightService
{
    private ?string $apiKey;
    private string $apiHost = 'google-keyword-insight1.p.rapidapi.com';
    private string $baseUrl = 'https://google-keyword-insight1.p.rapidapi.com';

    public function __construct()
    {
        $this->apiKey = Settings::get('rapidapi_keyword_key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Keyword suggestions con volumi e metriche
     */
    public function keySuggest(string $keyword, string $location = 'IT', string $lang = 'it', array $options = []): array
    {
        $params = [
            'keyword' => $keyword,
            'location' => $location,
            'lang' => $lang,
            'return_intent' => 'true',
        ];

        if (!empty($options['min_search_vol'])) {
            $params['min_search_vol'] = $options['min_search_vol'];
        }

        return $this->callApi('/keysuggest', $params);
    }

    /**
     * Top keywords per query
     */
    public function topKeys(string $keyword, string $location = 'IT', string $lang = 'it', int $num = 10): array
    {
        return $this->callApi('/topkeys', [
            'keyword' => $keyword,
            'location' => $location,
            'lang' => $lang,
            'num' => $num,
        ]);
    }

    /**
     * Domande correlate
     */
    public function questions(string $keyword, string $location = 'IT', string $lang = 'it'): array
    {
        return $this->callApi('/questions', [
            'keyword' => $keyword,
            'location' => $location,
            'lang' => $lang,
        ]);
    }

    /**
     * Espande una lista di seed keyword, merge + deduplica
     */
    public function expandSeeds(array $seeds, string $location = 'IT', string $lang = 'it', array $options = []): array
    {
        $allKeywords = [];
        $errors = [];
        $totalApiTime = 0;

        foreach ($seeds as $seed) {
            $startTime = microtime(true);
            $result = $this->keySuggest($seed, $location, $lang, $options);
            $totalApiTime += (int) round((microtime(true) - $startTime) * 1000);

            if (!$result['success']) {
                $errors[] = ['seed' => $seed, 'error' => $result['error']];
                continue;
            }

            foreach ($result['data'] as $item) {
                $text = $item['text'] ?? '';
                if ($text && !isset($allKeywords[$text])) {
                    $allKeywords[$text] = [
                        'text' => $text,
                        'volume' => (int) ($item['volume'] ?? 0),
                        'competition_level' => $item['competition_level'] ?? '',
                        'competition_index' => (int) ($item['competition_index'] ?? 0),
                        'low_bid' => (float) ($item['low_bid'] ?? 0),
                        'high_bid' => (float) ($item['high_bid'] ?? 0),
                        'trend' => (float) ($item['trend'] ?? 0),
                        'intent' => $item['intent'] ?? '',
                    ];
                }
            }
        }

        return [
            'keywords' => array_values($allKeywords),
            'errors' => $errors,
            'api_time_ms' => $totalApiTime,
        ];
    }

    /**
     * Filtra keyword per volume minimo e parole escluse
     */
    public function filterKeywords(array $keywords, array $exclusions = [], int $minVolume = 10): array
    {
        $filtered = [];
        $excluded = ['volume_low' => 0, 'exclusion_match' => 0];

        foreach ($keywords as $kw) {
            if ($kw['volume'] < $minVolume) {
                $excluded['volume_low']++;
                continue;
            }

            $isExcluded = false;
            foreach ($exclusions as $ex) {
                if (stripos($kw['text'], $ex) !== false) {
                    $isExcluded = true;
                    $excluded['exclusion_match']++;
                    break;
                }
            }

            if (!$isExcluded) {
                $filtered[] = $kw;
            }
        }

        // Ordina per volume decrescente
        usort($filtered, fn($a, $b) => $b['volume'] - $a['volume']);

        return [
            'keywords' => $filtered,
            'excluded_stats' => $excluded,
        ];
    }

    /**
     * Chiamata generica all'API
     */
    private function callApi(string $endpoint, array $params): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'API key non configurata', 'data' => []];
        }

        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
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

        // Log API call
        $requestPayload = $params;
        $responseData = $response ? json_decode($response, true) : null;

        ApiLoggerService::log('rapidapi_keyword_insight', $endpoint, $requestPayload, $responseData, $httpCode, $startTime, [
            'module' => 'keyword-research',
            'context' => "keyword={$params['keyword']}, location=" . ($params['location'] ?? 'IT'),
        ]);

        if ($error) {
            return ['success' => false, 'error' => "CURL: {$error}", 'data' => []];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP {$httpCode}", 'data' => []];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Risposta JSON non valida', 'data' => []];
        }

        return ['success' => true, 'data' => $data, 'error' => null];
    }
}
