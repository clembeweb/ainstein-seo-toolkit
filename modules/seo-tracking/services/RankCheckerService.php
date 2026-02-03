<?php

namespace Modules\SeoTracking\Services;

use Services\ScraperService;
use Services\DataForSeoService;
use Modules\SeoTracking\Models\Location;

/**
 * RankCheckerService
 *
 * Verifica posizioni SERP reali tramite (in ordine di priorità):
 * 1. DataForSEO (primario - usa stesse credenziali dei volumi, economico)
 * 2. SERP API (secondario - più affidabile, 100 query/mese gratis)
 * 3. Serper.dev (fallback - 2.500 query/mese gratis ma risultati inconsistenti)
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
    private ?DataForSeoService $dataForSeo = null;
    private string $lastProvider = '';

    public function __construct(?string $serperKey = null, ?string $serpKey = null)
    {
        // Leggi chiavi API dalle impostazioni globali
        $this->serperApiKey = $serperKey ?? \Core\Settings::get('serper_api_key', '');
        $this->serpApiKey = $serpKey ?? \Core\Settings::get('serp_api_key', '');
        $this->scraper = new ScraperService();
        $this->locationModel = new Location();

        // Inizializza DataForSEO (usa stesse credenziali dei volumi)
        $this->dataForSeo = new DataForSeoService();
    }

    /**
     * Verifica se almeno un provider è configurato
     */
    public function isConfigured(): bool
    {
        return $this->hasDataForSeo() || !empty($this->serperApiKey) || !empty($this->serpApiKey);
    }

    /**
     * Verifica se DataForSEO è configurato
     */
    public function hasDataForSeo(): bool
    {
        return $this->dataForSeo && $this->dataForSeo->isConfigured();
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
            'dataforseo' => [
                'configured' => $this->hasDataForSeo(),
                'name' => 'DataForSEO',
                'type' => 'primary',
            ],
            'serpapi' => [
                'configured' => $this->hasSerpApi(),
                'name' => 'SERP API',
                'type' => 'secondary',
            ],
            'serper' => [
                'configured' => $this->hasSerper(),
                'name' => 'Serper.dev',
                'type' => 'fallback',
            ],
        ];
    }

    /**
     * Verifica posizione SERP per una keyword e dominio target
     * Ordine provider: DataForSEO (primario) → SERP API (secondario) → Serper.dev (fallback)
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

        // Variabili per tracking risultati
        $primaryResult = null;
        $lastError = null;

        // =====================================================================
        // 1. PROVA DataForSEO (PRIMARIO - economico, usa stesse credenziali volumi)
        // =====================================================================
        if ($this->hasDataForSeo()) {
            try {
                $dataForSeoResult = $this->dataForSeo->checkSerpPosition(
                    $keyword,
                    $targetDomain,
                    $locationCode,
                    $device,
                    100 // max 100 risultati
                );

                if ($dataForSeoResult['success']) {
                    $this->lastProvider = 'dataforseo';
                    $primaryResult = [
                        'found' => $dataForSeoResult['found'],
                        'position' => $dataForSeoResult['position'],
                        'url' => $dataForSeoResult['url'],
                        'title' => $dataForSeoResult['title'],
                        'snippet' => $dataForSeoResult['snippet'],
                        'total_organic_results' => $dataForSeoResult['total_organic_results'],
                        'keyword' => $keyword,
                        'target_domain' => $targetDomain,
                        'location' => $dataForSeoResult['location'],
                        'location_code' => $locationCode,
                        'language' => $dataForSeoResult['language'],
                        'device' => $device,
                        'provider' => 'DataForSEO',
                        'cost' => $dataForSeoResult['cost'] ?? 0,
                    ];

                    // Se trovato, ritorna subito
                    if ($primaryResult['found']) {
                        return $primaryResult;
                    }

                    error_log("[RankChecker] DataForSEO: keyword '{$keyword}' non trovata nelle prime 100 posizioni, provo fallback");
                } else {
                    $lastError = $dataForSeoResult['error'] ?? 'Unknown DataForSEO error';
                    error_log("[RankChecker] DataForSEO error: {$lastError}");
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                error_log("[RankChecker] DataForSEO exception: {$lastError}");
            }
        }

        // =====================================================================
        // 2. PROVA SERP API (SECONDARIO)
        // =====================================================================
        if ($this->hasSerpApi()) {
            try {
                $serpApiResult = $this->checkWithSerpApi($keyword, $targetDomain, $location, $device);
                $this->lastProvider = 'serpapi';
                $serpApiResult['provider'] = 'SERP API';

                // Se trovato, ritorna subito
                if ($serpApiResult['found']) {
                    return $serpApiResult;
                }

                // Se non abbiamo ancora un risultato primario, usa questo
                if ($primaryResult === null) {
                    $primaryResult = $serpApiResult;
                }

                error_log("[RankChecker] SERP API: keyword '{$keyword}' non trovata, provo fallback Serper.dev");
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                error_log("[RankChecker] SERP API fallito: {$lastError}");
            }
        }

        // =====================================================================
        // 3. FALLBACK Serper.dev
        // =====================================================================
        if ($this->hasSerper()) {
            try {
                $serperResult = $this->checkWithSerper($keyword, $targetDomain, $location, $device);
                $this->lastProvider = 'serper';
                $serperResult['provider'] = 'Serper.dev';

                // Se trovato con fallback, ritorna questo risultato
                if ($serperResult['found']) {
                    error_log("[RankChecker] Serper.dev: keyword '{$keyword}' trovata in posizione " . $serperResult['position']);
                    return $serperResult;
                }

                // Se non abbiamo ancora un risultato primario, usa questo
                if ($primaryResult === null) {
                    $primaryResult = $serperResult;
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                error_log("[RankChecker] Serper.dev fallback fallito: {$lastError}");
            }
        }

        // Se abbiamo un risultato primario (anche se non trovato), ritornalo
        if ($primaryResult !== null) {
            return $primaryResult;
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
        $maxPages = 10; // Cerca nelle prime 10 pagine (100 risultati)

        for ($page = 1; $page <= $maxPages; $page++) {
            // Serper.dev usa solo gl (country) e hl (language)
            // NON supporta 'location' testuale come SerpApi
            $payload = [
                'q' => $keyword,
                'gl' => $location['serper_gl'] ?? 'it',
                'hl' => $location['serper_hl'] ?? 'it',
                'num' => 10,
                'page' => $page,
            ];

            // DEBUG: Log parametri location
            if ($page === 1) {
                error_log("[RankChecker Serper] Location params: gl=" . ($location['serper_gl'] ?? 'NULL') . ", hl=" . ($location['serper_hl'] ?? 'NULL'));
                error_log("[RankChecker Serper] Payload: " . json_encode($payload));
            }

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

            // DEBUG
            error_log("[RankChecker] Page {$page}: received " . count($pageResults) . " organic results");
            if (!empty($pageResults[0])) {
                error_log("[RankChecker] First result: " . ($pageResults[0]['link'] ?? 'no link'));
            }

            // Aggiorna le posizioni per renderle assolute (non relative alla pagina)
            $basePosition = ($page - 1) * 10;
            foreach ($pageResults as $index => &$result) {
                // Calcola sempre la posizione assoluta
                $result['position'] = $basePosition + $index + 1;
            }
            unset($result); // IMPORTANTE: evita problemi con reference

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

        // DEBUG: Log per diagnostica
        error_log("[RankChecker DEBUG] Total organic results: " . count($organicResults));
        error_log("[RankChecker DEBUG] Target domain: " . $targetDomain);
        if (!empty($organicResults)) {
            error_log("[RankChecker DEBUG] First result link: " . ($organicResults[0]['link'] ?? 'N/A'));
            $firstDomain = $this->normalizeDomain(parse_url($organicResults[0]['link'] ?? '', PHP_URL_HOST) ?? '');
            error_log("[RankChecker DEBUG] First result domain normalized: " . $firstDomain);
        }

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
        error_log("[RankChecker DEBUG] findDomainInResults returned found: " . ($result['found'] ? 'YES' : 'NO'));

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
        $maxPages = 10; // Cerca nelle prime 10 pagine (100 risultati)
        $resultsPerPage = 10;
        $totalResults = null;

        for ($page = 0; $page < $maxPages; $page++) {
            // Parametri base per SerpAPI - seguendo documentazione ufficiale
            // gl = country, hl = language, google_domain = quale Google usare
            // NON usiamo 'location' (è per city-level targeting, non necessario per country-level)
            $params = [
                'engine' => 'google',
                'q' => $keyword,
                'hl' => $location['language_code'] ?? 'it',
                'gl' => strtolower($location['country_code'] ?? 'it'),
                'google_domain' => $location['serpapi_google_domain'] ?? 'google.it',
                'num' => $resultsPerPage,
                'start' => $page * $resultsPerPage,
                'api_key' => $this->serpApiKey
            ];

            if ($device === 'mobile') {
                $params['device'] = 'mobile';
            }

            // DEBUG: Log parametri (solo prima pagina)
            if ($page === 0) {
                error_log("[RankChecker SerpAPI] Params: gl=" . $params['gl'] . ", hl=" . $params['hl'] . ", google_domain=" . $params['google_domain']);
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
        error_log("[RankChecker findDomain] Looking for target: '{$targetDomain}' in " . count($organicResults) . " results");

        foreach ($organicResults as $index => $result) {
            $resultUrl = $result['link'] ?? '';

            if (empty($resultUrl)) {
                continue;
            }

            // parse_url può restituire false per URL malformati
            $parsedHost = parse_url($resultUrl, PHP_URL_HOST);
            if ($parsedHost === false || $parsedHost === null) {
                error_log("[RankChecker findDomain] Invalid URL at index {$index}: {$resultUrl}");
                continue;
            }

            $resultDomain = $this->normalizeDomain($parsedHost);

            // DEBUG: Log per i primi 3 risultati
            if ($index < 3) {
                error_log("[RankChecker findDomain] Result {$index}: '{$resultDomain}' vs target '{$targetDomain}'");
            }

            // Match esatto o sottodominio (case-insensitive)
            if ($resultDomain === $targetDomain ||
                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                error_log("[RankChecker findDomain] MATCH FOUND at position " . ($result['position'] ?? $index + 1));
                return [
                    'found' => true,
                    'position' => ($result['position'] ?? $index + 1),
                    'url' => $resultUrl,
                    'title' => $result['title'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                ];
            }
        }

        error_log("[RankChecker findDomain] NO MATCH found for '{$targetDomain}'");
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
        $maxPages = $options['max_pages'] ?? 10;

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
            // Serper.dev usa solo gl e hl, non 'location'
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

        // Usa SERP API (primario - più affidabile)
        $primaryResult = null;

        if ($this->hasSerpApi()) {
            try {
                $primaryResult = $this->checkWithSerpApiFullResults($keyword, $targetDomain, $location, $device, $maxResults);
                $this->lastProvider = 'serpapi';
                $primaryResult['provider'] = 'SERP API';

                // Se trovato, ritorna subito
                if ($primaryResult['found']) {
                    return $primaryResult;
                }

                // Non trovato - prova fallback se disponibile
                error_log("[RankChecker FullResults] SERP API: keyword '{$keyword}' non trovata, provo fallback Serper.dev");
            } catch (\Exception $e) {
                if (!$this->hasSerper()) {
                    throw $e;
                }
                error_log("SERP API fallito, uso Serper.dev fallback: " . $e->getMessage());
            }
        }

        // Fallback a Serper.dev (anche se SERP API ha restituito "non trovata")
        if ($this->hasSerper()) {
            try {
                $fallbackResult = $this->checkWithSerperFullResults($keyword, $targetDomain, $location, $device, $maxResults);
                $this->lastProvider = 'serper';
                $fallbackResult['provider'] = 'Serper.dev';

                // Se trovato con fallback, ritorna questo risultato
                if ($fallbackResult['found']) {
                    error_log("[RankChecker FullResults] Serper.dev fallback: keyword '{$keyword}' trovata in posizione " . $fallbackResult['position']);
                    return $fallbackResult;
                }
            } catch (\Exception $e) {
                error_log("[RankChecker FullResults] Serper.dev fallback fallito: " . $e->getMessage());
            }
        }

        // Se abbiamo un risultato primario (anche se non trovato), ritornalo
        if ($primaryResult !== null) {
            return $primaryResult;
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

        // Paginazione per coprire fino a 100 posizioni
        $allOrganicResults = [];
        $resultsPerPage = 10;
        $maxPages = min(10, ceil($maxResults / $resultsPerPage)); // Max 10 pagine = 100 risultati

        for ($page = 1; $page <= $maxPages; $page++) {
            $payload = [
                'q' => $keyword,
                'gl' => $location['serper_gl'] ?? 'it',
                'hl' => $location['serper_hl'] ?? 'it',
                'num' => $resultsPerPage,
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

            if (empty($pageResults)) {
                break; // Nessun altro risultato
            }

            // Calcola posizioni assolute
            $basePosition = ($page - 1) * $resultsPerPage;
            foreach ($pageResults as $index => &$result) {
                $result['position'] = $basePosition + $index + 1;
            }
            unset($result);

            // Cerca il dominio target
            foreach ($pageResults as $result) {
                $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');

                if ($resultDomain === $targetDomain ||
                    str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                    // Trovato! Aggiungi e esci
                    $allOrganicResults = array_merge($allOrganicResults, $pageResults);
                    break 2;
                }
            }

            $allOrganicResults = array_merge($allOrganicResults, $pageResults);

            // Limita ai primi maxResults
            if (count($allOrganicResults) >= $maxResults) {
                $allOrganicResults = array_slice($allOrganicResults, 0, $maxResults);
                break;
            }

            // Pausa tra chiamate per rate limit (aumentata per evitare limitazioni)
            if ($page < $maxPages) {
                usleep(500000); // 500ms tra le chiamate
            }
        }

        // Costruisci array risultati formattato
        $allResults = [];
        foreach ($allOrganicResults as $result) {
            $resultUrl = $result['link'] ?? '';
            $parsedHost = parse_url($resultUrl, PHP_URL_HOST);

            if ($parsedHost === false || $parsedHost === null) {
                $resultDomain = '';
            } else {
                $resultDomain = $this->normalizeDomain($parsedHost);
            }

            $isTarget = !empty($resultDomain) && (
                $resultDomain === $targetDomain ||
                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))
            );

            $allResults[] = [
                'position' => $result['position'] ?? 0,
                'domain' => $resultDomain,
                'url' => $resultUrl,
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'is_target' => $isTarget,
            ];
        }

        // Trova posizione target
        $targetResult = $this->findDomainInResults($allOrganicResults, $targetDomain);

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
     * Implementa paginazione per coprire fino a 100 posizioni
     */
    private function checkWithSerpApiFullResults(
        string $keyword,
        string $targetDomain,
        array $location,
        string $device,
        int $maxResults
    ): array {
        $allOrganicResults = [];
        $resultsPerPage = 10;
        $maxPages = min(10, ceil($maxResults / $resultsPerPage)); // Max 10 pagine = 100 risultati
        $foundPosition = null;
        $foundUrl = null;

        for ($page = 0; $page < $maxPages; $page++) {
            // Parametri base per SerpAPI - seguendo documentazione ufficiale
            // gl = country, hl = language, google_domain = quale Google usare
            // NON usiamo 'location' (è per city-level targeting)
            $params = [
                'engine' => 'google',
                'q' => $keyword,
                'hl' => $location['language_code'] ?? 'it',
                'gl' => strtolower($location['country_code'] ?? 'it'),
                'google_domain' => $location['serpapi_google_domain'] ?? 'google.it',
                'num' => $resultsPerPage,
                'start' => $page * $resultsPerPage,
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
            $pageResults = $data['organic_results'] ?? [];

            if (empty($pageResults)) {
                break; // Nessun altro risultato
            }

            // Calcola posizioni assolute
            $basePosition = $page * $resultsPerPage;
            foreach ($pageResults as $index => &$result) {
                $result['position'] = $basePosition + $index + 1;
            }
            unset($result);

            // Cerca il dominio target
            foreach ($pageResults as $result) {
                $resultDomain = $this->normalizeDomain(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
                if ($resultDomain === $targetDomain ||
                    str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                    $foundPosition = $result['position'];
                    $foundUrl = $result['link'] ?? null;
                    $allOrganicResults = array_merge($allOrganicResults, $pageResults);
                    break 2; // Trovato! Esci dai loop
                }
            }

            $allOrganicResults = array_merge($allOrganicResults, $pageResults);

            // Limita ai primi maxResults
            if (count($allOrganicResults) >= $maxResults) {
                $allOrganicResults = array_slice($allOrganicResults, 0, $maxResults);
                break;
            }

            // Pausa tra chiamate per rate limit
            if ($page < $maxPages - 1) {
                usleep(200000); // 200ms
            }
        }

        // Costruisci array risultati formattato
        $allResults = [];
        foreach ($allOrganicResults as $result) {
            $parsedHost = parse_url($result['link'] ?? '', PHP_URL_HOST);
            $resultDomain = $parsedHost ? $this->normalizeDomain($parsedHost) : '';
            $isTarget = !empty($resultDomain) && (
                $resultDomain === $targetDomain ||
                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))
            );

            $allResults[] = [
                'position' => $result['position'] ?? 0,
                'domain' => $resultDomain,
                'url' => $result['link'] ?? '',
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'is_target' => $isTarget,
            ];
        }

        // Trova posizione target dai risultati raccolti
        $targetResult = $this->findDomainInResults($allOrganicResults, $targetDomain);

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

        // Test DataForSEO (primario)
        if ($this->hasDataForSeo()) {
            $testResult = $this->dataForSeo->testSerpConnection();
            $results['dataforseo'] = [
                'success' => $testResult['success'],
                'message' => $testResult['success'] ? 'DataForSEO SERP funzionante' : ($testResult['error'] ?? 'Errore'),
            ];
        }

        // Test SERP API (secondario)
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

        // Test Serper.dev (fallback)
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
