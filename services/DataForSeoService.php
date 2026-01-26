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
                CURLOPT_URL => $this->baseUrl . '/keywords_data/google_ads/search_volume/live',
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
                return ['success' => false, 'error' => "CURL error: {$error}"];
            }

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "HTTP {$httpCode}: " . substr($response, 0, 200)];
            }

            $data = json_decode($response, true);

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
}
