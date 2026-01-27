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
        // Headers per Serper.dev
        $headers = [
            'X-API-KEY: ' . $this->serperApiKey,
            'Content-Type: application/json',
        ];

        // Usa endpoint mobile se richiesto
        $url = $device === 'mobile'
            ? 'https://google.serper.dev/search'
            : $this->serperBaseUrl;

        // Serper.dev potrebbe limitare i risultati per pagina
        // Facciamo più chiamate con paginazione per coprire le prime 30-50 posizioni
        $allOrganicResults = [];
        $maxPages = 5; // Cerca nelle prime 5 pagine (50 risultati)

        for ($page = 1; $page <= $maxPages; $page++) {
            $payload = [
                'q' => $keyword,
                'gl' => $location['serper_gl'],
                'hl' => $location['serper_hl'],
                'num' => 10,
                'page' => $page,
            ];

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

            $pageResults = $data['organic'] ?? [];

            // Aggiorna le posizioni per renderle assolute (non relative alla pagina)
            $basePosition = ($page - 1) * 10;
            foreach ($pageResults as $index => &$result) {
                // Calcola sempre la posizione assoluta
                $result['position'] = $basePosition + $index + 1;
            }

            // Se il dominio target è già stato trovato, restituisci subito
            foreach ($pageResults as $result) {
                $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
                if ($resultDomain === $targetDomain ||
                    str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                    // Trovato! Aggiungi i risultati e esci
                    $allOrganicResults = array_merge($allOrganicResults, $pageResults);
                    break 2; // Esci da entrambi i loop
                }
            }

            $allOrganicResults = array_merge($allOrganicResults, $pageResults);

            // Se non ci sono più risultati, interrompi
            if (empty($pageResults)) {
                break;
            }

            // Breve pausa tra le chiamate per rispettare rate limits
            usleep(200000); // 200ms
        }

        $organicResults = $allOrganicResults;

        // Converti formato Serper al formato standard
        $normalizedResults = [];
        foreach ($organicResults as $index => $result) {
            $normalizedResults[] = [
                'position' => $result['position'] ?? ($index + 1),
                'link' => $result['link'] ?? '',
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'source' => 'organic',
            ];
        }

        // Cerca anche nei risultati shopping (rich snippets prodotti)
        $shoppingResults = $data['shopping'] ?? [];
        foreach ($shoppingResults as $index => $result) {
            // I risultati shopping vengono aggiunti dopo gli organici
            // ma manteniamo traccia che sono di tipo shopping
            $normalizedResults[] = [
                'position' => $result['position'] ?? (count($organicResults) + $index + 1),
                'link' => $result['link'] ?? '',
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? $result['source'] ?? '',
                'source' => 'shopping',
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
     * Implementa paginazione per coprire le prime 50 posizioni
     */
    private function checkWithSerpApi(
        string $keyword,
        string $targetDomain,
        array $location,
        string $device
    ): array {
        $allOrganicResults = [];
        $maxPages = 5; // Cerca nelle prime 5 pagine (50 risultati)
        $resultsPerPage = 10;
        $totalResults = null;

        for ($page = 0; $page < $maxPages; $page++) {
            $params = [
                'engine' => 'google',
                'q' => $keyword,
                'location' => $location['serpapi_location'] ?? $location['name'],
                'hl' => $location['language_code'],
                'gl' => strtolower($location['country_code']),
                'google_domain' => $location['serpapi_google_domain'] ?? 'google.com',
                'num' => $resultsPerPage,
                'start' => $page * $resultsPerPage, // Paginazione SERP API
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

            $pageResults = $data['organic_results'] ?? [];

            // Salva total_results dalla prima pagina
            if ($page === 0) {
                $totalResults = $data['search_information']['total_results'] ?? null;
            }

            // Aggiorna le posizioni per renderle assolute (non relative alla pagina)
            $basePosition = $page * $resultsPerPage;
            foreach ($pageResults as $index => &$result) {
                // SERP API potrebbe già fornire 'position', ma ricalcoliamo per sicurezza
                $result['position'] = $basePosition + $index + 1;
            }

            // Se il dominio target è già stato trovato, restituisci subito
            foreach ($pageResults as $result) {
                $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
                if ($resultDomain === $targetDomain ||
                    str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                    // Trovato! Aggiungi i risultati e esci
                    $allOrganicResults = array_merge($allOrganicResults, $pageResults);
                    break 2; // Esci da entrambi i loop
                }
            }

            $allOrganicResults = array_merge($allOrganicResults, $pageResults);

            // Se non ci sono più risultati, interrompi
            if (empty($pageResults)) {
                break;
            }

            // Breve pausa tra le chiamate per rispettare rate limits
            usleep(200000); // 200ms
        }

        $result = $this->findDomainInResults($allOrganicResults, $targetDomain);

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

            // Match esatto o sottodominio (case-insensitive)
            if ($resultDomain === $targetDomain ||
                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
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
     * Debug: restituisce risposta raw dell'API per diagnostica (con paginazione)
     */
    public function debugSearch(string $keyword, string $targetDomain, array $options = []): array
    {
        $locationCode = $options['location_code'] ?? 'IT';
        $device = $options['device'] ?? 'desktop';
        $maxPages = $options['max_pages'] ?? 5;

        $location = $this->locationModel->findByCountryCode($locationCode);
        if (!$location) {
            $location = $this->locationModel->getDefault();
        }

        $targetDomain = $this->normalizeDomain($targetDomain);

        $headers = [
            'X-API-KEY: ' . $this->serperApiKey,
            'Content-Type: application/json',
        ];

        $allOrganicResults = [];
        $pagesChecked = 0;
        $foundIn = null;
        $foundPosition = null;
        $foundUrl = null;

        // Paginazione: cerca fino a trovare il dominio
        for ($page = 1; $page <= $maxPages; $page++) {
            $payload = [
                'q' => $keyword,
                'gl' => $location['serper_gl'],
                'hl' => $location['serper_hl'],
                'num' => 10,
                'page' => $page,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->serperBaseUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $pageResults = $data['organic'] ?? [];
            $pagesChecked++;

            // Aggiorna posizioni per renderle assolute
            $basePosition = ($page - 1) * 10;
            foreach ($pageResults as $index => &$result) {
                $result['position'] = $basePosition + $index + 1;
            }

            // Cerca il dominio
            foreach ($pageResults as $result) {
                $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
                if ($resultDomain === $targetDomain || str_contains($resultDomain, $targetDomain)) {
                    $foundIn = 'organic (page ' . $page . ')';
                    $foundPosition = $result['position']; // Ora è la posizione assoluta
                    $foundUrl = $result['link'] ?? null;
                    $allOrganicResults = array_merge($allOrganicResults, $pageResults);
                    break 2;
                }
            }

            $allOrganicResults = array_merge($allOrganicResults, $pageResults);

            if (empty($pageResults)) break;
            usleep(200000); // 200ms pausa
        }

        return [
            'keyword' => $keyword,
            'target_domain' => $targetDomain,
            'location' => $location['name'],
            'pages_checked' => $pagesChecked,
            'total_organic_count' => count($allOrganicResults),
            'found_in' => $foundIn,
            'found_position' => $foundPosition,
            'found_url' => $foundUrl,
            'first_10_organic' => array_map(function($r) {
                return [
                    'position' => $r['position'] ?? null,
                    'domain' => parse_url($r['link'] ?? '', PHP_URL_HOST),
                    'title' => substr($r['title'] ?? '', 0, 50),
                ];
            }, array_slice($allOrganicResults, 0, 10)),
            'results_11_20' => array_map(function($r) {
                return [
                    'position' => $r['position'] ?? null,
                    'domain' => parse_url($r['link'] ?? '', PHP_URL_HOST),
                    'title' => substr($r['title'] ?? '', 0, 50),
                ];
            }, array_slice($allOrganicResults, 10, 10)),
        ];
    }

    /**
     * Verifica posizione SERP e restituisce TUTTI i risultati organici
     * Utile per analisi competitiva
     *
     * @param string $keyword La keyword da cercare
     * @param string $targetDomain Il dominio da trovare
     * @param array $options Opzioni: location_code, device, max_results
     * @return array Include 'all_results' con tutti i risultati SERP
     */
    public function checkPositionWithAllResults(
        string $keyword,
        string $targetDomain,
        array $options = []
    ): array {
        if (!$this->isConfigured()) {
            throw new \Exception('Nessun provider SERP configurato. Vai in Admin > Impostazioni');
        }

        $locationCode = $options['location_code'] ?? 'IT';
        $device = $options['device'] ?? 'desktop';
        $maxResults = $options['max_results'] ?? 10; // Default top 10 competitor

        $location = $this->locationModel->findByCountryCode($locationCode);
        if (!$location) {
            $location = $this->locationModel->getDefault();
        }

        $targetDomain = $this->normalizeDomain($targetDomain);

        // Usa Serper.dev (primario)
        if ($this->hasSerper()) {
            try {
                $result = $this->checkWithSerperFullResults($keyword, $targetDomain, $location, $device, $maxResults);
                $this->lastProvider = 'serper';
                $result['provider'] = 'Serper.dev';
                return $result;
            } catch (\Exception $e) {
                if (!$this->hasSerpApi()) {
                    throw $e;
                }
            }
        }

        // Fallback a SERP API
        if ($this->hasSerpApi()) {
            $result = $this->checkWithSerpApiFullResults($keyword, $targetDomain, $location, $device, $maxResults);
            $this->lastProvider = 'serpapi';
            $result['provider'] = 'SERP API';
            return $result;
        }

        throw new \Exception('Nessun provider SERP disponibile');
    }

    /**
     * Check con Serper.dev restituendo TUTTI i risultati
     */
    private function checkWithSerperFullResults(
        string $keyword,
        string $targetDomain,
        array $location,
        string $device,
        int $maxResults
    ): array {
        $headers = [
            'X-API-KEY: ' . $this->serperApiKey,
            'Content-Type: application/json',
        ];

        $url = $device === 'mobile'
            ? 'https://google.serper.dev/search'
            : $this->serperBaseUrl;

        // Una sola chiamata per ottenere i primi 10 risultati (competitor)
        $payload = [
            'q' => $keyword,
            'gl' => $location['serper_gl'],
            'hl' => $location['serper_hl'],
            'num' => min($maxResults, 10),
        ];

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
        $allResults = [];

        foreach ($organicResults as $index => $result) {
            $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
            $isTarget = ($resultDomain === $targetDomain ||
                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain)));

            $allResults[] = [
                'position' => $index + 1,
                'domain' => $resultDomain,
                'url' => $result['link'] ?? '',
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'is_target' => $isTarget,
            ];
        }

        // Trova posizione target
        $targetResult = $this->findDomainInResults($organicResults, $targetDomain);

        return [
            'found' => $targetResult['found'],
            'position' => $targetResult['position'],
            'url' => $targetResult['url'],
            'title' => $targetResult['title'],
            'snippet' => $targetResult['snippet'],
            'all_results' => $allResults,
            'keyword' => $keyword,
            'target_domain' => $targetDomain,
            'location' => $location['name'],
            'location_code' => $location['country_code'],
            'device' => $device,
        ];
    }

    /**
     * Check con SERP API restituendo TUTTI i risultati
     */
    private function checkWithSerpApiFullResults(
        string $keyword,
        string $targetDomain,
        array $location,
        string $device,
        int $maxResults
    ): array {
        $params = [
            'engine' => 'google',
            'q' => $keyword,
            'location' => $location['serpapi_location'] ?? $location['name'],
            'hl' => $location['language_code'],
            'gl' => strtolower($location['country_code']),
            'google_domain' => $location['serpapi_google_domain'] ?? 'google.com',
            'num' => min($maxResults, 10),
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
        $organicResults = $data['organic_results'] ?? [];
        $allResults = [];

        foreach ($organicResults as $index => $result) {
            $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
            $isTarget = ($resultDomain === $targetDomain ||
                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain)));

            $allResults[] = [
                'position' => $index + 1,
                'domain' => $resultDomain,
                'url' => $result['link'] ?? '',
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'is_target' => $isTarget,
            ];
        }

        // Trova posizione target
        $targetResult = $this->findDomainInResults($organicResults, $targetDomain);

        return [
            'found' => $targetResult['found'],
            'position' => $targetResult['position'],
            'url' => $targetResult['url'],
            'title' => $targetResult['title'],
            'snippet' => $targetResult['snippet'],
            'all_results' => $allResults,
            'keyword' => $keyword,
            'target_domain' => $targetDomain,
            'location' => $location['name'],
            'location_code' => $location['country_code'],
            'device' => $device,
        ];
    }

    /**
     * Salva tutti i risultati SERP nel database
     *
     * @param int $projectId ID progetto
     * @param string $keyword Keyword cercata
     * @param array $allResults Array di risultati da checkPositionWithAllResults
     * @param int|null $rankCheckId ID del rank check associato (opzionale)
     * @return int Numero di risultati salvati
     */
    public function saveSerpResults(
        int $projectId,
        string $keyword,
        array $allResults,
        ?int $rankCheckId = null,
        string $locationCode = 'IT',
        string $device = 'desktop'
    ): int {
        $saved = 0;

        foreach ($allResults as $result) {
            try {
                \Core\Database::insert('st_serp_results', [
                    'rank_check_id' => $rankCheckId,
                    'project_id' => $projectId,
                    'keyword' => $keyword,
                    'position' => $result['position'],
                    'domain' => $result['domain'],
                    'url' => $result['url'],
                    'title' => $result['title'] ?? null,
                    'snippet' => $result['snippet'] ?? null,
                    'result_type' => 'organic',
                    'is_target_domain' => $result['is_target'] ? 1 : 0,
                    'location_code' => $locationCode,
                    'device' => $device,
                ]);
                $saved++;
            } catch (\Exception $e) {
                // Log error but continue
                error_log("Error saving SERP result: " . $e->getMessage());
            }
        }

        return $saved;
    }

    /**
     * Ottieni i competitor recenti per una keyword
     *
     * @param int $projectId ID progetto
     * @param string $keyword Keyword
     * @param int $limit Numero massimo di competitor
     * @param int $maxAgeDays Età massima dei dati in giorni
     * @return array
     */
    public function getRecentCompetitors(
        int $projectId,
        string $keyword,
        int $limit = 5,
        int $maxAgeDays = 7
    ): array {
        return \Core\Database::fetchAll(
            "SELECT DISTINCT domain, url, title, snippet, position
             FROM st_serp_results
             WHERE project_id = ?
               AND keyword = ?
               AND is_target_domain = 0
               AND checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY position ASC
             LIMIT ?",
            [$projectId, $keyword, $maxAgeDays, $limit]
        );
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
