<?php

namespace Services;

use Core\Settings;
use Core\Database;
use Modules\SeoTracking\Models\Location;

/**
 * DataForSeoService
 *
 * Ottieni volumi di ricerca, CPC, competition e trend mensili da DataForSEO API
 */
class DataForSeoService
{
    private ?string $login;
    private ?string $password;
    private string $baseUrl = 'https://api.dataforseo.com/v3';
    private Location $locationModel;

    // Cache durata: 7 giorni (volumi non cambiano spesso)
    private int $cacheDays = 7;

    public function __construct()
    {
        $this->login = Settings::get('dataforseo_login');
        $this->password = Settings::get('dataforseo_password');
        $this->locationModel = new Location();
    }

    /**
     * Verifica se API è configurata
     */
    public function isConfigured(): bool
    {
        return !empty($this->login) && !empty($this->password);
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
            return ['success' => false, 'error' => 'DataForSEO non configurato. Vai in Admin > Impostazioni'];
        }

        if (empty($keywords)) {
            return ['success' => true, 'data' => []];
        }

        // Ottieni parametri DataForSEO dalla location
        $locationParams = $this->locationModel->getDataForSeoParams($countryCode);
        $locationCode = $locationParams['location_code'];
        $languageCode = $locationParams['language_code'];

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

        // Chiama API per keyword non in cache (max 1000 per chiamata)
        $chunks = array_chunk($uncachedKeywords, 1000);

        foreach ($chunks as $chunk) {
            $apiResult = $this->callSearchVolumeApi($chunk, $locationCode, $languageCode);

            if ($apiResult['success'] && !empty($apiResult['data'])) {
                foreach ($apiResult['data'] as $keyword => $data) {
                    $results[$keyword] = $data;
                    $this->saveToCache($keyword, $locationCode, $data);
                }
            } else {
                // Log errore ma continua
                error_log("[DataForSEO] API error: " . ($apiResult['error'] ?? 'unknown'));
            }
        }

        return [
            'success' => true,
            'data' => $results,
            'cached' => count($keywords) - count($uncachedKeywords),
            'fetched' => count($uncachedKeywords)
        ];
    }

    /**
     * Chiamata API Search Volume
     */
    private function callSearchVolumeApi(array $keywords, int $locationCode, string $languageCode): array
    {
        $startTime = microtime(true);
        $endpoint = '/keywords_data/google_ads/search_volume/live';

        try {
            $postData = [
                [
                    'keywords' => array_values($keywords),
                    'location_code' => $locationCode,
                    'language_code' => $languageCode,
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($this->login . ':' . $this->password),
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 60,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                // Log errore
                ApiLoggerService::log('dataforseo', $endpoint, $postData, null, 0, $startTime, [
                    'module' => 'seo-tracking',
                    'error' => "CURL error: {$error}",
                    'context' => 'keywords=' . count($keywords),
                ]);
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            $data = json_decode($response, true);
            $cost = $data['cost'] ?? 0;

            // Log chiamata
            ApiLoggerService::log('dataforseo', $endpoint, $postData, $data, $httpCode, $startTime, [
                'module' => 'seo-tracking',
                'cost' => $cost,
                'context' => 'keywords=' . count($keywords),
            ]);

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "HTTP {$httpCode}: " . substr($response, 0, 200)];
            }

            if (!$data) {
                return ['success' => false, 'error' => 'Invalid JSON response'];
            }

            // Verifica status code DataForSEO
            $statusCode = $data['status_code'] ?? 0;
            if ($statusCode !== 20000) {
                return ['success' => false, 'error' => $data['status_message'] ?? "API error code: {$statusCode}"];
            }

            // Parse risultati
            $results = [];
            $tasks = $data['tasks'] ?? [];

            foreach ($tasks as $task) {
                $taskResult = $task['result'] ?? [];
                foreach ($taskResult as $item) {
                    $keyword = $item['keyword'] ?? '';
                    if ($keyword) {
                        // NOTA: DataForSEO API restituisce:
                        // - 'competition' = stringa ('LOW', 'MEDIUM', 'HIGH')
                        // - 'competition_index' = intero 0-100
                        // Noi mappiamo:
                        // - competition_index / 100 -> competition (decimal 0-1)
                        // - competition -> competition_level (string)
                        $competitionIndex = $item['competition_index'] ?? null;
                        $competitionValue = $competitionIndex !== null ? round($competitionIndex / 100, 4) : null;

                        $results[$keyword] = [
                            'search_volume' => (int) ($item['search_volume'] ?? 0),
                            'cpc' => (float) ($item['cpc'] ?? 0),
                            'competition' => $competitionValue,
                            'competition_level' => $item['competition'] ?? null, // 'LOW', 'MEDIUM', 'HIGH'
                            'monthly_searches' => $item['monthly_searches'] ?? [],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                }
            }

            return ['success' => true, 'data' => $results];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
            error_log("[DataForSEO] Cache save error: " . $e->getMessage());
        }
    }

    /**
     * Test connessione API
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Credenziali non configurate'];
        }

        // Usa location di default per test
        $locationParams = $this->locationModel->getDataForSeoParams('IT');

        // Testa con una keyword semplice
        $result = $this->callSearchVolumeApi(
            ['test'],
            $locationParams['location_code'],
            $locationParams['language_code']
        );

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Connessione DataForSEO funzionante',
                'sample' => $result['data']['test'] ?? null
            ];
        }

        return $result;
    }

    /**
     * Ottieni lista locations disponibili
     */
    public function getAvailableLocations(): array
    {
        return $this->locationModel->all();
    }

    // =========================================================================
    // SERP RANK CHECK
    // =========================================================================

    /**
     * Verifica posizione SERP per una keyword
     *
     * @param string $keyword La keyword da cercare
     * @param string $targetDomain Il dominio da trovare (es: example.com)
     * @param string $countryCode Codice paese ISO (IT, US, etc.)
     * @param string $device desktop o mobile
     * @param int $maxResults Numero massimo di risultati da analizzare (default 100)
     * @return array
     */
    public function checkSerpPosition(
        string $keyword,
        string $targetDomain,
        string $countryCode = 'IT',
        string $device = 'desktop',
        int $maxResults = 100
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'DataForSEO non configurato. Vai in Admin > Impostazioni'
            ];
        }

        // Ottieni parametri DataForSEO dalla location
        $locationParams = $this->locationModel->getDataForSeoParams($countryCode);
        $location = $this->locationModel->findByCountryCode($countryCode)
            ?? $this->locationModel->getDefault();

        // Normalizza il dominio target
        $targetDomain = $this->normalizeDomain($targetDomain);

        $startTime = microtime(true);
        $endpoint = '/serp/google/organic/live/regular';

        try {
            // Prepara la richiesta
            // Usiamo depth per ottenere più risultati per pagina
            $depth = min($maxResults, 100); // Max 100 per pagina secondo docs

            $postData = [
                [
                    'keyword' => $keyword,
                    'location_code' => $locationParams['location_code'],
                    'language_code' => $locationParams['language_code'],
                    'device' => $device,
                    'os' => $device === 'mobile' ? 'android' : 'windows',
                    'depth' => $depth,
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($this->login . ':' . $this->password),
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 60,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                ApiLoggerService::log('dataforseo', $endpoint, $postData, null, 0, $startTime, [
                    'module' => 'seo-tracking',
                    'error' => "CURL error: {$error}",
                    'context' => "keyword={$keyword}",
                ]);
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            $data = json_decode($response, true);
            $cost = $data['cost'] ?? 0;

            // Log chiamata API
            ApiLoggerService::log('dataforseo', $endpoint, $postData, $data, $httpCode, $startTime, [
                'module' => 'seo-tracking',
                'cost' => $cost,
                'context' => "keyword={$keyword}, target={$targetDomain}",
            ]);

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "HTTP {$httpCode}: " . substr($response, 0, 200)];
            }

            if (!$data) {
                return ['success' => false, 'error' => 'Invalid JSON response'];
            }

            // Verifica status code DataForSEO
            $statusCode = $data['status_code'] ?? 0;
            if ($statusCode !== 20000) {
                return ['success' => false, 'error' => $data['status_message'] ?? "API error code: {$statusCode}"];
            }

            // Parse risultati
            $tasks = $data['tasks'] ?? [];
            $organicResults = [];
            $foundPosition = null;
            $foundUrl = null;
            $foundTitle = null;
            $foundSnippet = null;

            foreach ($tasks as $task) {
                $taskResults = $task['result'] ?? [];
                foreach ($taskResults as $result) {
                    $items = $result['items'] ?? [];

                    foreach ($items as $item) {
                        // Considera solo risultati organici
                        if (($item['type'] ?? '') !== 'organic') {
                            continue;
                        }

                        $position = $item['rank_absolute'] ?? $item['rank_group'] ?? count($organicResults) + 1;
                        $resultUrl = $item['url'] ?? '';
                        $resultDomain = $this->normalizeDomain($item['domain'] ?? '');

                        $organicResults[] = [
                            'position' => $position,
                            'domain' => $resultDomain,
                            'url' => $resultUrl,
                            'title' => $item['title'] ?? '',
                            'snippet' => $item['description'] ?? '',
                        ];

                        // Cerca il dominio target
                        if ($foundPosition === null) {
                            if ($resultDomain === $targetDomain ||
                                str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                                $foundPosition = $position;
                                $foundUrl = $resultUrl;
                                $foundTitle = $item['title'] ?? '';
                                $foundSnippet = $item['description'] ?? '';
                                error_log("[DataForSEO SERP] Found target at position {$position}: {$resultUrl}");
                            }
                        }
                    }
                }
            }

            error_log("[DataForSEO SERP] Total organic results: " . count($organicResults) . ", Found: " . ($foundPosition ? "Yes at {$foundPosition}" : "No"));

            return [
                'success' => true,
                'found' => $foundPosition !== null,
                'position' => $foundPosition,
                'url' => $foundUrl,
                'title' => $foundTitle,
                'snippet' => $foundSnippet,
                'total_organic_results' => count($organicResults),
                'keyword' => $keyword,
                'target_domain' => $targetDomain,
                'location' => $location['name'],
                'location_code' => $countryCode,
                'language' => $locationParams['language_code'],
                'device' => $device,
                'cost' => $cost,
                'provider' => 'DataForSEO',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verifica posizioni SERP per multiple keywords (batch)
     * Più efficiente per chiamate bulk
     *
     * @param array $keywords Lista keyword
     * @param string $targetDomain Dominio target
     * @param string $countryCode Codice paese
     * @param string $device desktop/mobile
     * @param int $depth Risultati per keyword (default 100)
     * @return array
     */
    public function checkSerpPositionBatch(
        array $keywords,
        string $targetDomain,
        string $countryCode = 'IT',
        string $device = 'desktop',
        int $depth = 100
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'DataForSEO non configurato'
            ];
        }

        if (empty($keywords)) {
            return ['success' => true, 'results' => []];
        }

        $locationParams = $this->locationModel->getDataForSeoParams($countryCode);
        $location = $this->locationModel->findByCountryCode($countryCode)
            ?? $this->locationModel->getDefault();
        $targetDomain = $this->normalizeDomain($targetDomain);

        // DataForSEO supporta fino a 100 task per chiamata
        $chunks = array_chunk($keywords, 100);
        $allResults = [];
        $totalCost = 0;

        foreach ($chunks as $chunk) {
            // Prepara batch di task
            $postData = [];
            foreach ($chunk as $keyword) {
                $postData[] = [
                    'keyword' => $keyword,
                    'location_code' => $locationParams['location_code'],
                    'language_code' => $locationParams['language_code'],
                    'device' => $device,
                    'os' => $device === 'mobile' ? 'android' : 'windows',
                    'depth' => min($depth, 100),
                ];
            }

            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->baseUrl . '/serp/google/organic/live/regular',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($postData),
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Basic ' . base64_encode($this->login . ':' . $this->password),
                        'Content-Type: application/json',
                    ],
                    CURLOPT_TIMEOUT => 120, // Timeout più lungo per batch
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error || $httpCode !== 200) {
                    // In caso di errore, segna tutte le keyword del chunk come fallite
                    foreach ($chunk as $keyword) {
                        $allResults[$keyword] = [
                            'success' => false,
                            'found' => false,
                            'position' => null,
                            'error' => $error ?: "HTTP {$httpCode}",
                            'keyword' => $keyword,
                        ];
                    }
                    continue;
                }

                $data = json_decode($response, true);
                $totalCost += $data['cost'] ?? 0;

                // Parse ogni task
                $tasks = $data['tasks'] ?? [];
                foreach ($tasks as $task) {
                    $taskKeyword = null;
                    $taskResults = $task['result'] ?? [];

                    foreach ($taskResults as $result) {
                        $taskKeyword = $result['keyword'] ?? $taskKeyword;
                        $items = $result['items'] ?? [];

                        $foundPosition = null;
                        $foundUrl = null;
                        $foundTitle = null;
                        $foundSnippet = null;
                        $organicCount = 0;

                        foreach ($items as $item) {
                            if (($item['type'] ?? '') !== 'organic') {
                                continue;
                            }

                            $organicCount++;
                            $resultDomain = $this->normalizeDomain($item['domain'] ?? '');

                            if ($foundPosition === null) {
                                if ($resultDomain === $targetDomain ||
                                    str_ends_with(strtolower($resultDomain), '.' . strtolower($targetDomain))) {
                                    $foundPosition = $item['rank_absolute'] ?? $item['rank_group'] ?? $organicCount;
                                    $foundUrl = $item['url'] ?? '';
                                    $foundTitle = $item['title'] ?? '';
                                    $foundSnippet = $item['description'] ?? '';
                                }
                            }
                        }

                        if ($taskKeyword) {
                            $allResults[$taskKeyword] = [
                                'success' => true,
                                'found' => $foundPosition !== null,
                                'position' => $foundPosition,
                                'url' => $foundUrl,
                                'title' => $foundTitle,
                                'snippet' => $foundSnippet,
                                'total_organic_results' => $organicCount,
                                'keyword' => $taskKeyword,
                                'target_domain' => $targetDomain,
                                'location' => $location['name'],
                                'location_code' => $countryCode,
                                'device' => $device,
                                'provider' => 'DataForSEO',
                            ];
                        }
                    }
                }

            } catch (\Exception $e) {
                foreach ($chunk as $keyword) {
                    $allResults[$keyword] = [
                        'success' => false,
                        'found' => false,
                        'position' => null,
                        'error' => $e->getMessage(),
                        'keyword' => $keyword,
                    ];
                }
            }

            // Pausa tra chunk per rispettare rate limits
            if (count($chunks) > 1) {
                usleep(100000); // 100ms
            }
        }

        return [
            'success' => true,
            'results' => $allResults,
            'total_cost' => $totalCost,
            'keywords_checked' => count($keywords),
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
     * Test connessione SERP API
     */
    public function testSerpConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Credenziali non configurate'];
        }

        // Testa con una keyword semplice cercando google.com
        $result = $this->checkSerpPosition('test', 'google.com', 'IT', 'desktop', 10);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'DataForSEO SERP API funzionante',
                'found' => $result['found'],
                'position' => $result['position'],
                'cost' => $result['cost'] ?? 0,
            ];
        }

        return $result;
    }

    // =========================================================================
    // ON-PAGE SEO ANALYSIS
    // =========================================================================

    /**
     * Analisi on-page istantanea di una singola pagina
     * Usa endpoint: POST /on_page/instant_pages
     *
     * @param string $url URL completo della pagina da analizzare
     * @param array $options Opzioni aggiuntive
     * @return array
     */
    public function analyzeInstantPage(string $url, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'DataForSEO non configurato. Vai in Admin > Impostazioni'
            ];
        }

        $startTime = microtime(true);
        $endpoint = '/on_page/instant_pages';

        // Opzioni di default
        $enableJavascript = $options['enable_javascript'] ?? true;
        $enableBrowserRendering = $options['enable_browser_rendering'] ?? false;
        $checkSpell = $options['check_spell'] ?? false;
        $validateMicromarkup = $options['validate_micromarkup'] ?? true;

        try {
            $postData = [
                [
                    'url' => $url,
                    'enable_javascript' => $enableJavascript,
                    'enable_browser_rendering' => $enableBrowserRendering,
                    'check_spell' => $checkSpell,
                    'validate_micromarkup' => $validateMicromarkup,
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($this->login . ':' . $this->password),
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 120, // Timeout più lungo per rendering
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            $data = json_decode($response, true);
            $cost = $data['cost'] ?? 0;

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "HTTP {$httpCode}: " . substr($response, 0, 200)];
            }

            if (!$data) {
                return ['success' => false, 'error' => 'Invalid JSON response'];
            }

            // Verifica status code DataForSEO
            $statusCode = $data['status_code'] ?? 0;
            if ($statusCode !== 20000) {
                return ['success' => false, 'error' => $data['status_message'] ?? "API error code: {$statusCode}"];
            }

            // Parse risultati
            $tasks = $data['tasks'] ?? [];
            $pageData = null;
            $checks = [];

            foreach ($tasks as $task) {
                $taskResults = $task['result'] ?? [];
                foreach ($taskResults as $result) {
                    $items = $result['items'] ?? [];
                    foreach ($items as $item) {
                        // Dati pagina principale
                        $pageData = [
                            'url' => $item['url'] ?? $url,
                            'status_code' => $item['status_code'] ?? null,
                            'meta' => [
                                'title' => $item['meta']['title'] ?? null,
                                'description' => $item['meta']['description'] ?? null,
                                'charset' => $item['meta']['charset'] ?? null,
                                'canonical' => $item['meta']['canonical'] ?? null,
                            ],
                            'htags' => $item['meta']['htags'] ?? [],
                            'content' => [
                                'plain_text_word_count' => $item['meta']['content']['plain_text_word_count'] ?? 0,
                                'automated_readability_index' => $item['meta']['content']['automated_readability_index'] ?? null,
                            ],
                            'internal_links_count' => $item['meta']['internal_links_count'] ?? 0,
                            'external_links_count' => $item['meta']['external_links_count'] ?? 0,
                            'images_count' => $item['meta']['images_count'] ?? 0,
                            'images_without_alt' => [],
                            'size' => $item['page_resource_size'] ?? 0,
                            'is_indexable' => !($item['meta']['htags']['noindex'] ?? false),
                            'schema_types' => $item['meta']['social_media_tags'] ?? null,
                            'page_timing' => $item['page_timing'] ?? [],
                        ];

                        // Raccogli checks (problemi rilevati)
                        $checks = $this->extractOnPageChecks($item);
                    }
                }
            }

            if (!$pageData) {
                return ['success' => false, 'error' => 'Nessun dato ricevuto per la pagina'];
            }

            // Calcola on-page score (semplificato, basato sui checks)
            $onpageScore = $this->calculateOnPageScore($checks);

            return [
                'success' => true,
                'url' => $url,
                'onpage_score' => $onpageScore,
                'page_data' => $pageData,
                'checks' => $checks,
                'cost' => $cost,
                'provider' => 'DataForSEO',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Estrai checks on-page dai dati della pagina
     */
    private function extractOnPageChecks(array $item): array
    {
        $checks = [];
        $meta = $item['meta'] ?? [];

        // Title checks
        $title = $meta['title'] ?? '';
        $titleLen = strlen($title);
        if (empty($title)) {
            $checks[] = ['name' => 'title_missing', 'passed' => false, 'priority' => 'high', 'message' => 'Meta title mancante'];
        } elseif ($titleLen < 30) {
            $checks[] = ['name' => 'title_too_short', 'passed' => false, 'priority' => 'medium', 'message' => "Meta title troppo corto ({$titleLen} caratteri, consigliati 50-60)"];
        } elseif ($titleLen > 70) {
            $checks[] = ['name' => 'title_too_long', 'passed' => false, 'priority' => 'medium', 'message' => "Meta title troppo lungo ({$titleLen} caratteri, max 60)"];
        } else {
            $checks[] = ['name' => 'title_ok', 'passed' => true, 'priority' => 'low', 'message' => 'Meta title ottimale'];
        }

        // Description checks
        $desc = $meta['description'] ?? '';
        $descLen = strlen($desc);
        if (empty($desc)) {
            $checks[] = ['name' => 'description_missing', 'passed' => false, 'priority' => 'high', 'message' => 'Meta description mancante'];
        } elseif ($descLen < 70) {
            $checks[] = ['name' => 'description_too_short', 'passed' => false, 'priority' => 'medium', 'message' => "Meta description troppo corta ({$descLen} caratteri, consigliati 150-160)"];
        } elseif ($descLen > 170) {
            $checks[] = ['name' => 'description_too_long', 'passed' => false, 'priority' => 'low', 'message' => "Meta description troppo lunga ({$descLen} caratteri, max 160)"];
        } else {
            $checks[] = ['name' => 'description_ok', 'passed' => true, 'priority' => 'low', 'message' => 'Meta description ottimale'];
        }

        // H1 checks
        $htags = $meta['htags'] ?? [];
        $h1s = $htags['h1'] ?? [];
        $h1Count = count($h1s);
        if ($h1Count === 0) {
            $checks[] = ['name' => 'h1_missing', 'passed' => false, 'priority' => 'high', 'message' => 'Tag H1 mancante'];
        } elseif ($h1Count > 1) {
            $checks[] = ['name' => 'h1_multiple', 'passed' => false, 'priority' => 'medium', 'message' => "Trovati {$h1Count} tag H1 (consigliato 1)"];
        } else {
            $checks[] = ['name' => 'h1_ok', 'passed' => true, 'priority' => 'low', 'message' => 'Un solo H1 presente'];
        }

        // Content checks
        $wordCount = $meta['content']['plain_text_word_count'] ?? 0;
        if ($wordCount < 300) {
            $checks[] = ['name' => 'content_thin', 'passed' => false, 'priority' => 'high', 'message' => "Contenuto scarso ({$wordCount} parole, consigliato 300+)"];
        } elseif ($wordCount < 600) {
            $checks[] = ['name' => 'content_short', 'passed' => false, 'priority' => 'low', 'message' => "Contenuto breve ({$wordCount} parole, consigliato 600+)"];
        } else {
            $checks[] = ['name' => 'content_ok', 'passed' => true, 'priority' => 'low', 'message' => "Contenuto adeguato ({$wordCount} parole)"];
        }

        // Images checks
        $imagesCount = $meta['images_count'] ?? 0;
        // Nota: DataForSEO instant_pages non restituisce direttamente images_without_alt
        // Lo gestiamo in modo semplificato
        if ($imagesCount > 0) {
            $checks[] = ['name' => 'images_present', 'passed' => true, 'priority' => 'low', 'message' => "{$imagesCount} immagini trovate"];
        }

        // Links checks
        $internalLinks = $meta['internal_links_count'] ?? 0;
        $externalLinks = $meta['external_links_count'] ?? 0;
        if ($internalLinks === 0) {
            $checks[] = ['name' => 'no_internal_links', 'passed' => false, 'priority' => 'medium', 'message' => 'Nessun link interno trovato'];
        }
        if ($internalLinks > 100) {
            $checks[] = ['name' => 'too_many_links', 'passed' => false, 'priority' => 'low', 'message' => "Troppi link interni ({$internalLinks})"];
        }

        // Canonical check
        $canonical = $meta['canonical'] ?? '';
        if (empty($canonical)) {
            $checks[] = ['name' => 'canonical_missing', 'passed' => false, 'priority' => 'medium', 'message' => 'URL canonical mancante'];
        } else {
            $checks[] = ['name' => 'canonical_present', 'passed' => true, 'priority' => 'low', 'message' => 'URL canonical presente'];
        }

        return $checks;
    }

    /**
     * Calcola score on-page basato sui checks
     */
    private function calculateOnPageScore(array $checks): int
    {
        $totalPoints = 0;
        $maxPoints = 0;

        $pointsByPriority = [
            'high' => 20,
            'medium' => 10,
            'low' => 5,
        ];

        foreach ($checks as $check) {
            $priority = $check['priority'] ?? 'low';
            $points = $pointsByPriority[$priority] ?? 5;
            $maxPoints += $points;

            if ($check['passed'] ?? false) {
                $totalPoints += $points;
            }
        }

        if ($maxPoints === 0) {
            return 50; // Default se nessun check
        }

        return (int) round(($totalPoints / $maxPoints) * 100);
    }

    /**
     * Test connessione On-Page API
     */
    public function testOnPageConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Credenziali non configurate'];
        }

        // Testa con una pagina semplice
        $result = $this->analyzeInstantPage('https://example.com', [
            'enable_javascript' => false,
            'enable_browser_rendering' => false,
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'DataForSEO On-Page API funzionante',
                'onpage_score' => $result['onpage_score'] ?? null,
                'cost' => $result['cost'] ?? 0,
            ];
        }

        return $result;
    }
}
