<?php

namespace Modules\SeoTracking\Services;

use Services\ScraperService;
use Modules\SeoTracking\Models\Location;

/**
 * RankCheckerService
 *
 * Verifica posizioni SERP reali tramite:
 * - Serper.dev (primario - 2.500 query/mese gratis)
 * - SERP API (fallback - 100 query/mese gratis)
 *
 * Supporta locations dinamiche dal database.
 */
class RankCheckerService
{
    private string $serperApiKey;
    private string $serpApiKey;
    private string $serperBaseUrl = 'https://google.serper.dev/search';
    private string $serpApiBaseUrl = 'https://serpapi.com/search.json';
    private ScraperService $scraper;
    private Location $locationModel;
    private string $lastProvider = '';

    public function __construct(?string $serperKey = null, ?string $serpKey = null)
    {
        // Leggi chiavi API dalle impostazioni globali
        $this->serperApiKey = $serperKey ?? \Core\Settings::get('serper_api_key', '');
        $this->serpApiKey = $serpKey ?? \Core\Settings::get('serp_api_key', '');
        $this->scraper = new ScraperService();
        $this->locationModel = new Location();
    }

    /**
     * Verifica se almeno un provider è configurato
     */
    public function isConfigured(): bool
    {
        return !empty($this->serperApiKey) || !empty($this->serpApiKey);
    }

    /**
     * Verifica se Serper.dev è configurato
     */
    public function hasSerper(): bool
    {
        return !empty($this->serperApiKey);
    }

    /**
     * Verifica se SERP API è configurato
     */
    public function hasSerpApi(): bool
    {
        return !empty($this->serpApiKey);
    }

    /**
     * Ritorna il provider attivo (ultimo usato)
     */
    public function getLastProvider(): string
    {
        return $this->lastProvider;
    }

    /**
     * Ritorna info sui provider configurati
     */
    public function getProvidersInfo(): array
    {
        return [
            'serper' => [
                'configured' => $this->hasSerper(),
                'name' => 'Serper.dev',
                'type' => 'primary',
            ],
            'serpapi' => [
                'configured' => $this->hasSerpApi(),
                'name' => 'SERP API',
                'type' => 'fallback',
            ],
        ];
    }

    /**
     * Verifica posizione SERP per una keyword e dominio target
     * Usa Serper.dev come primario, SERP API come fallback
     *
     * @param string $keyword La keyword da cercare
     * @param string $targetDomain Il dominio da trovare (es: example.com)
     * @param array $options Opzioni: location_code (default: IT), device (desktop/mobile)
     * @return array
     */
    public function checkPosition(
        string $keyword,
        string $targetDomain,
        array $options = []
    ): array {
        if (!$this->isConfigured()) {
            throw new \Exception('Nessun provider SERP configurato. Vai in Admin > Impostazioni');
        }

        // Estrai opzioni
        $locationCode = $options['location_code'] ?? 'IT';
        $device = $options['device'] ?? 'desktop';

        // Carica dati location dal DB
        $location = $this->locationModel->findByCountryCode($locationCode);
        if (!$location) {
            $location = $this->locationModel->getDefault();
        }

        // Normalizza il dominio (rimuovi protocollo e www se presenti)
        $targetDomain = $this->normalizeDomain($targetDomain);

        // Prova prima Serper.dev (primario)
        if ($this->hasSerper()) {
            try {
                $result = $this->checkWithSerper($keyword, $targetDomain, $location, $device);
                $this->lastProvider = 'serper';
                $result['provider'] = 'Serper.dev';
                return $result;
            } catch (\Exception $e) {
                // Se fallisce e abbiamo SERP API, prova quello
                if ($this->hasSerpApi()) {
                    error_log("Serper.dev fallito, uso SERP API fallback: " . $e->getMessage());
                } else {
                    throw $e; // Nessun fallback disponibile
                }
            }
        }

        // Fallback a SERP API
        if ($this->hasSerpApi()) {
            $result = $this->checkWithSerpApi($keyword, $targetDomain, $location, $device);
            $this->lastProvider = 'serpapi';
            $result['provider'] = 'SERP API';
            return $result;
        }

        throw new \Exception('Nessun provider SERP disponibile');
    }

    /**
     * Check con Serper.dev (primario)
     */
    private function checkWithSerper(
        string $keyword,
        string $targetDomain,
        array $location,
        string $device
    ): array {
        $payload = [
            'q' => $keyword,
            'gl' => $location['serper_gl'],
            'hl' => $location['serper_hl'],
            'num' => 100, // Cerca nelle prime 100 posizioni
        ];

        // Headers per Serper.dev
        $headers = [
            'X-API-KEY: ' . $this->serperApiKey,
            'Content-Type: application/json',
        ];

        // Usa endpoint mobile se richiesto
        $url = $device === 'mobile'
            ? 'https://google.serper.dev/search'
            : $this->serperBaseUrl;

        // Fai la richiesta POST a Serper.dev
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('Errore Serper.dev: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Errore Serper.dev HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);

        if (isset($data['message'])) {
            throw new \Exception('Errore Serper.dev: ' . $data['message']);
        }

        $organicResults = $data['organic'] ?? [];

        // Converti formato Serper al formato standard
        $normalizedResults = [];
        foreach ($organicResults as $index => $result) {
            $normalizedResults[] = [
                'position' => $result['position'] ?? ($index + 1),
                'link' => $result['link'] ?? '',
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
            ];
        }

        // Cerca il dominio target nei risultati
        $result = $this->findDomainInResults($normalizedResults, $targetDomain);

        return [
            'found' => $result['found'],
            'position' => $result['position'],
            'url' => $result['url'],
            'title' => $result['title'],
            'snippet' => $result['snippet'],
            'total_organic_results' => count($organicResults),
            'keyword' => $keyword,
            'target_domain' => $targetDomain,
            'location' => $location['name'],
            'location_code' => $location['country_code'],
            'language' => $location['language_code'],
            'device' => $device,
        ];
    }

    /**
     * Check con SERP API (fallback)
     */
    private function checkWithSerpApi(
        string $keyword,
        string $targetDomain,
        array $location,
        string $device
    ): array {
        $params = [
            'engine' => 'google',
            'q' => $keyword,
            'location' => $location['serpapi_location'] ?? $location['name'],
            'hl' => $location['language_code'],
            'gl' => strtolower($location['country_code']),
            'google_domain' => $location['serpapi_google_domain'] ?? 'google.com',
            'num' => 100,
            'api_key' => $this->serpApiKey
        ];

        if ($device === 'mobile') {
            $params['device'] = 'mobile';
        }

        $url = $this->serpApiBaseUrl . '?' . http_build_query($params);

        $response = $this->scraper->fetchJson($url, [
            'timeout' => 30,
            'api_mode' => true,
        ]);

        if (isset($response['error'])) {
            throw new \Exception('Errore SERP API: ' . ($response['message'] ?? 'Unknown error'));
        }

        $data = $response['data'] ?? [];

        if (isset($data['error'])) {
            throw new \Exception('SERP API Error: ' . $data['error']);
        }

        $organicResults = $data['organic_results'] ?? [];
        $totalResults = $data['search_information']['total_results'] ?? null;

        $result = $this->findDomainInResults($organicResults, $targetDomain);

        return [
            'found' => $result['found'],
            'position' => $result['position'],
            'url' => $result['url'],
            'title' => $result['title'],
            'snippet' => $result['snippet'],
            'total_organic_results' => $totalResults,
            'keyword' => $keyword,
            'target_domain' => $targetDomain,
            'location' => $location['name'],
            'location_code' => $location['country_code'],
            'language' => $location['language_code'],
            'device' => $device,
        ];
    }

    /**
     * Check multiplo per più keyword
     *
     * @param array $keywords Lista di keyword
     * @param string $targetDomain Dominio target
     * @param array $options Opzioni: location_code, device
     * @return array
     */
    public function checkMultiple(
        array $keywords,
        string $targetDomain,
        array $options = []
    ): array {
        $results = [];

        foreach ($keywords as $keyword) {
            try {
                $results[] = $this->checkPosition($keyword, $targetDomain, $options);
            } catch (\Exception $e) {
                $results[] = [
                    'found' => false,
                    'position' => null,
                    'url' => null,
                    'title' => null,
                    'snippet' => null,
                    'keyword' => $keyword,
                    'target_domain' => $targetDomain,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Cerca il dominio target nei risultati organici
     */
    private function findDomainInResults(array $organicResults, string $targetDomain): array
    {
        foreach ($organicResults as $index => $result) {
            $resultUrl = $result['link'] ?? '';

            if (empty($resultUrl)) {
                continue;
            }

            $resultDomain = $this->normalizeDomain(parse_url($resultUrl, PHP_URL_HOST) ?? '');

            // Match esatto o sottodominio
            if ($resultDomain === $targetDomain || str_ends_with($resultDomain, '.' . $targetDomain)) {
                return [
                    'found' => true,
                    'position' => ($result['position'] ?? $index + 1),
                    'url' => $resultUrl,
                    'title' => $result['title'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                ];
            }
        }

        return [
            'found' => false,
            'position' => null,
            'url' => null,
            'title' => null,
            'snippet' => null,
        ];
    }

    /**
     * Normalizza il dominio (rimuovi www, protocollo, trailing slash)
     */
    private function normalizeDomain(string $domain): string
    {
        // Rimuovi protocollo se presente
        $domain = preg_replace('#^https?://#', '', $domain);

        // Rimuovi path e query string
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];

        // Rimuovi www.
        $domain = preg_replace('/^www\./i', '', $domain);

        return strtolower(trim($domain));
    }

    /**
     * Test connessione API (testa tutti i provider configurati)
     */
    public function testConnection(): array
    {
        $results = [];

        // Usa location di default per test
        $testLocation = $this->locationModel->getDefault();

        // Test Serper.dev
        if ($this->hasSerper()) {
            try {
                $this->checkWithSerper('test', 'google.com', $testLocation, 'desktop');
                $results['serper'] = [
                    'success' => true,
                    'message' => 'Serper.dev funzionante',
                ];
            } catch (\Exception $e) {
                $results['serper'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Test SERP API
        if ($this->hasSerpApi()) {
            try {
                $this->checkWithSerpApi('test', 'google.com', $testLocation, 'desktop');
                $results['serpapi'] = [
                    'success' => true,
                    'message' => 'SERP API funzionante',
                ];
            } catch (\Exception $e) {
                $results['serpapi'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (empty($results)) {
            return [
                'success' => false,
                'error' => 'Nessun provider configurato',
            ];
        }

        // Ritorna successo se almeno un provider funziona
        $anySuccess = false;
        foreach ($results as $r) {
            if ($r['success']) {
                $anySuccess = true;
                break;
            }
        }

        return [
            'success' => $anySuccess,
            'providers' => $results,
            'message' => $anySuccess ? 'Almeno un provider SERP funzionante' : 'Tutti i provider hanno fallito',
        ];
    }
}
