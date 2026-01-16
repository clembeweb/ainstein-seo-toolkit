<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Core\Settings;
use Modules\SeoTracking\Models\Ga4Connection;
use Modules\SeoTracking\Models\Ga4Data;
use Modules\SeoTracking\Models\Ga4Daily;
use Modules\SeoTracking\Models\KeywordRevenue;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\SyncLog;
use Modules\SeoTracking\Models\GscData;

/**
 * Ga4Service
 * Gestisce OAuth2 e API Google Analytics 4 Data API
 */
class Ga4Service
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GA4_API_BASE = 'https://analyticsdata.googleapis.com/v1beta';
    private const GA4_ADMIN_API = 'https://analyticsadmin.googleapis.com/v1beta';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private Ga4Connection $ga4Connection;
    private Ga4Data $ga4Data;
    private Ga4Daily $ga4Daily;
    private KeywordRevenue $keywordRevenue;
    private Project $project;
    private SyncLog $syncLog;
    private GscData $gscData;

    public function __construct()
    {
        // USA LE STESSE CREDENZIALI DI GSC (Google OAuth centralizzato)
        $this->clientId = Settings::get('gsc_client_id', '');
        $this->clientSecret = Settings::get('gsc_client_secret', '');
        $this->redirectUri = $this->buildRedirectUri();

        $this->ga4Connection = new Ga4Connection();
        $this->ga4Data = new Ga4Data();
        $this->ga4Daily = new Ga4Daily();
        $this->keywordRevenue = new KeywordRevenue();
        $this->project = new Project();
        $this->syncLog = new SyncLog();
        $this->gscData = new GscData();
    }

    /**
     * Costruisce redirect URI dinamico
     * Usa stessa logica di GoogleOAuthService per consistenza
     */
    private function buildRedirectUri(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Determina il base path (es: /seo-toolkit se presente)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (preg_match('#^(/[^/]+)/(?:public/)?#', $scriptName, $matches)) {
            if ($matches[1] !== '/public') {
                $basePath = $matches[1];
            }
        }

        return $scheme . '://' . $host . $basePath . '/oauth/google/callback';
    }

    /**
     * Verifica se le credenziali OAuth sono configurate
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Genera URL autorizzazione OAuth2
     * USA STESSO FORMATO STATE DI GSC per callback centralizzato
     */
    public function getAuthUrl(int $projectId): string
    {
        $state = base64_encode(json_encode([
            'module' => 'seo-tracking',
            'project_id' => $projectId,
            'type' => 'ga4',
            'csrf' => bin2hex(random_bytes(16)),
        ]));

        // Usa stessa session key di GoogleOAuthService per callback centralizzato
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Scambia authorization code per tokens
     */
    public function exchangeCode(string $code): array
    {
        $response = $this->httpPost(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (isset($response['error'])) {
            throw new \Exception('OAuth error: ' . ($response['error_description'] ?? $response['error']));
        }

        return $response;
    }

    /**
     * Rinnova access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = $this->httpPost(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (isset($response['error'])) {
            throw new \Exception('Token refresh error: ' . ($response['error_description'] ?? $response['error']));
        }

        return $response;
    }

    /**
     * Ottieni access token valido per progetto
     */
    public function getValidToken(int $projectId): ?string
    {
        $connection = $this->ga4Connection->getByProject($projectId);

        if (!$connection || empty($connection['refresh_token'])) {
            return null;
        }

        // Token scaduto o assente?
        if (empty($connection['access_token']) ||
            (isset($connection['token_expires_at']) && strtotime($connection['token_expires_at']) < time())) {
            try {
                $tokens = $this->refreshToken($connection['refresh_token']);

                $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

                $this->ga4Connection->updateToken($projectId, $tokens['access_token'], $expiresAt);

                return $tokens['access_token'];
            } catch (\Exception $e) {
                // Token invalido, disconnetti
                $this->ga4Connection->delete($projectId);
                return null;
            }
        }

        return $connection['access_token'];
    }

    /**
     * Lista properties GA4 accessibili
     */
    public function listProperties(int $projectId): array
    {
        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        // Prima ottieni account summaries
        $response = $this->httpGet(self::GA4_ADMIN_API . '/accountSummaries', $token);

        if (isset($response['error'])) {
            throw new \Exception('API error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        $properties = [];

        foreach ($response['accountSummaries'] ?? [] as $account) {
            foreach ($account['propertySummaries'] ?? [] as $property) {
                // property e nel formato "properties/123456789"
                $propertyId = str_replace('properties/', '', $property['property']);
                $properties[] = [
                    'property_id' => $propertyId,
                    'property_name' => $property['displayName'] ?? $propertyId,
                    'account_name' => $account['displayName'] ?? '',
                ];
            }
        }

        return $properties;
    }

    /**
     * Verifica accesso a property GA4
     */
    public function verifyPropertyAccess(int $projectId, string $propertyId): bool
    {
        try {
            $token = $this->getValidToken($projectId);

            if (!$token) {
                return false;
            }

            // Prova a fare una query minima
            $url = self::GA4_API_BASE . '/properties/' . $propertyId . ':runReport';

            $body = [
                'dateRanges' => [
                    ['startDate' => 'yesterday', 'endDate' => 'yesterday'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                ],
                'limit' => 1,
            ];

            $response = $this->httpPostJson($url, $body, $token);

            return !isset($response['error']);

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync dati GA4
     */
    public function syncAnalytics(int $projectId, ?string $startDate = null, ?string $endDate = null): array
    {
        $connection = $this->ga4Connection->getByProject($projectId);

        if (!$connection || !$connection['property_id']) {
            throw new \Exception('Connessione GA4 non configurata');
        }

        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        $propertyId = $connection['property_id'];

        // Date default: ultimi 7 giorni
        $endDate = $endDate ?? date('Y-m-d', strtotime('-1 day'));
        $startDate = $startDate ?? date('Y-m-d', strtotime('-7 days'));

        // Crea log sync
        $logId = $this->syncLog->create([
            'project_id' => $projectId,
            'sync_type' => 'ga4_daily',
            'status' => 'running',
        ]);

        try {
            $result = [
                'records_fetched' => 0,
                'records_inserted' => 0,
            ];

            // 1. Metriche giornaliere aggregate
            $dailyMetrics = $this->fetchDailyMetrics($token, $propertyId, $startDate, $endDate);
            $result['records_fetched'] += count($dailyMetrics);

            foreach ($dailyMetrics as $row) {
                $this->ga4Daily->upsert([
                    'project_id' => $projectId,
                    'date' => $row['date'],
                    'sessions' => $row['sessions'] ?? 0,
                    'users' => $row['totalUsers'] ?? 0,
                    'new_users' => $row['newUsers'] ?? 0,
                    'avg_session_duration' => $row['averageSessionDuration'] ?? 0,
                    'bounce_rate' => $row['bounceRate'] ?? 0,
                    'engagement_rate' => $row['engagementRate'] ?? 0,
                    'add_to_carts' => 0, // Richiede e-commerce
                    'begin_checkouts' => 0, // Richiede e-commerce
                    'purchases' => $row['conversions'] ?? 0, // Usa conversions al posto di purchases
                    'revenue' => 0, // Richiede e-commerce
                ]);
                $result['records_inserted']++;
            }

            // 2. Dati per landing page (traffico organico)
            $landingPageData = $this->fetchLandingPageData($token, $propertyId, $startDate, $endDate);

            foreach ($landingPageData as $row) {
                $this->ga4Data->upsert([
                    'project_id' => $projectId,
                    'date' => $row['date'],
                    'landing_page' => $row['landingPage'],
                    'sessions' => $row['sessions'] ?? 0,
                    'users' => $row['totalUsers'] ?? 0,
                    'engaged_sessions' => $row['engagedSessions'] ?? 0,
                    'engagement_rate' => $row['engagementRate'] ?? 0,
                    'add_to_carts' => 0, // Richiede e-commerce
                    'purchases' => $row['conversions'] ?? 0, // Usa conversions
                    'revenue' => 0, // Richiede e-commerce
                ]);
            }

            // 3. Revenue attribution (collega GSC queries a GA4 conversioni)
            $this->attributeRevenue($projectId, $startDate, $endDate);

            // 4. Aggiorna last sync
            $this->ga4Connection->updateLastSync($projectId);

            $this->syncLog->complete($logId, [
                'status' => 'completed',
                'records_fetched' => $result['records_fetched'],
                'records_inserted' => $result['records_inserted'],
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->syncLog->fail($logId, $e->getMessage());
            $this->ga4Connection->updateLastSync($projectId, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch metriche giornaliere
     */
    private function fetchDailyMetrics(string $token, string $propertyId, string $startDate, string $endDate): array
    {
        $url = self::GA4_API_BASE . '/properties/' . $propertyId . ':runReport';

        $body = [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate],
            ],
            'dimensions' => [
                ['name' => 'date'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'totalUsers'],
                ['name' => 'newUsers'],
                ['name' => 'averageSessionDuration'],
                ['name' => 'bounceRate'],
                ['name' => 'engagementRate'],
                ['name' => 'screenPageViews'],
                ['name' => 'conversions'],
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'sessionDefaultChannelGroup',
                    'stringFilter' => [
                        'matchType' => 'EXACT',
                        'value' => 'Organic Search',
                    ],
                ],
            ],
            'orderBys' => [
                ['dimension' => ['dimensionName' => 'date']],
            ],
        ];

        $response = $this->httpPostJson($url, $body, $token);

        if (isset($response['error'])) {
            throw new \Exception('GA4 API error: ' . ($response['error']['message'] ?? 'Unknown'));
        }

        return $this->parseReportResponse($response);
    }

    /**
     * Fetch dati per landing page (solo traffico organico) - con paginazione
     */
    private function fetchLandingPageData(string $token, string $propertyId, string $startDate, string $endDate): array
    {
        $url = self::GA4_API_BASE . '/properties/' . $propertyId . ':runReport';
        $allRows = [];
        $offset = 0;
        $limit = 10000;

        do {
            $body = [
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'dimensions' => [
                    ['name' => 'date'],
                    ['name' => 'landingPage'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalUsers'],
                    ['name' => 'engagedSessions'],
                    ['name' => 'engagementRate'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'conversions'],
                ],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName' => 'sessionDefaultChannelGroup',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => 'Organic Search',
                        ],
                    ],
                ],
                'limit' => $limit,
                'offset' => $offset,
            ];

            $response = $this->httpPostJson($url, $body, $token);

            if (isset($response['error'])) {
                throw new \Exception('GA4 API error: ' . ($response['error']['message'] ?? 'Unknown'));
            }

            $parsedRows = $this->parseReportResponse($response, ['date', 'landingPage']);
            $fetchedCount = count($parsedRows);

            if ($fetchedCount > 0) {
                $allRows = array_merge($allRows, $parsedRows);
            }

            $offset += $limit;

            // Libera memoria
            unset($response, $parsedRows);

        } while ($fetchedCount === $limit);

        return $allRows;
    }

    /**
     * Parse risposta report GA4
     */
    private function parseReportResponse(array $response, array $dimensionNames = ['date']): array
    {
        $rows = [];

        if (empty($response['rows'])) {
            return $rows;
        }

        $metricHeaders = array_map(fn($h) => $h['name'], $response['metricHeaders'] ?? []);

        foreach ($response['rows'] as $row) {
            $parsed = [];

            // Parse dimensions
            foreach ($row['dimensionValues'] as $i => $dim) {
                $dimName = $dimensionNames[$i] ?? 'dim' . $i;

                if ($dimName === 'date') {
                    // Formato YYYYMMDD -> YYYY-MM-DD
                    $parsed['date'] = substr($dim['value'], 0, 4) . '-' .
                                     substr($dim['value'], 4, 2) . '-' .
                                     substr($dim['value'], 6, 2);
                } else {
                    $parsed[$dimName] = $dim['value'];
                }
            }

            // Parse metrics
            foreach ($row['metricValues'] as $i => $metric) {
                $metricName = $metricHeaders[$i] ?? 'metric' . $i;
                $parsed[$metricName] = floatval($metric['value']);
            }

            $rows[] = $parsed;
        }

        return $rows;
    }

    /**
     * Sync dati GA4 per un range di date specifico (STREAMING + BATCH INSERT)
     * @return int Numero di record importati
     */
    public function syncDateRange(int $projectId, string $startDate, string $endDate): int
    {
        $connection = $this->ga4Connection->getByProject($projectId);

        if (!$connection || !$connection['property_id']) {
            throw new \Exception('Connessione GA4 non configurata');
        }

        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        $propertyId = $connection['property_id'];
        $db = Database::getInstance();
        $batchSize = 500;
        $count = 0;

        // 1. Metriche giornaliere aggregate (batch) - tipicamente pochi record
        $dailyMetrics = $this->fetchDailyMetrics($token, $propertyId, $startDate, $endDate);

        if (!empty($dailyMetrics)) {
            $dailyRecords = [];
            foreach ($dailyMetrics as $row) {
                $dailyRecords[] = [
                    'project_id' => $projectId,
                    'date' => $row['date'],
                    'sessions' => (int)($row['sessions'] ?? 0),
                    'users' => (int)($row['totalUsers'] ?? 0),
                    'new_users' => (int)($row['newUsers'] ?? 0),
                    'avg_session_duration' => (float)($row['averageSessionDuration'] ?? 0),
                    'bounce_rate' => (float)($row['bounceRate'] ?? 0),
                    'engagement_rate' => (float)($row['engagementRate'] ?? 0),
                    'add_to_carts' => 0,
                    'begin_checkouts' => 0,
                    'purchases' => (int)($row['conversions'] ?? 0),
                    'revenue' => 0,
                ];
            }

            $chunks = array_chunk($dailyRecords, $batchSize);
            foreach ($chunks as $chunk) {
                $count += $this->batchUpsertGa4Daily($db, $chunk);
            }
            unset($dailyMetrics, $dailyRecords, $chunks);
        }

        // 2. Dati per landing page - streaming con batch immediati
        $count += $this->streamLandingPageData($db, $token, $propertyId, $projectId, $startDate, $endDate, $batchSize);

        return $count;
    }

    /**
     * Stream landing page data con batch insert immediati
     */
    private function streamLandingPageData(\PDO $db, string $token, string $propertyId, int $projectId, string $startDate, string $endDate, int $batchSize): int
    {
        $url = self::GA4_API_BASE . '/properties/' . $propertyId . ':runReport';
        $offset = 0;
        $limit = 10000;
        $totalInserted = 0;

        do {
            $body = [
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'dimensions' => [
                    ['name' => 'date'],
                    ['name' => 'landingPage'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'totalUsers'],
                    ['name' => 'engagedSessions'],
                    ['name' => 'engagementRate'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'conversions'],
                ],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName' => 'sessionDefaultChannelGroup',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => 'Organic Search',
                        ],
                    ],
                ],
                'limit' => $limit,
                'offset' => $offset,
            ];

            $response = $this->httpPostJson($url, $body, $token);

            if (isset($response['error'])) {
                throw new \Exception('GA4 API error: ' . ($response['error']['message'] ?? 'Unknown'));
            }

            $parsedRows = $this->parseReportResponse($response, ['date', 'landingPage']);
            $fetchedCount = count($parsedRows);

            // Converti e inserisci immediatamente in batch
            if ($fetchedCount > 0) {
                $batch = [];
                foreach ($parsedRows as $row) {
                    $batch[] = [
                        'project_id' => $projectId,
                        'date' => $row['date'],
                        'landing_page' => $row['landingPage'] ?? '',
                        'source_medium' => 'google / organic',
                        'country' => null,
                        'device_category' => null,
                        'sessions' => (int)($row['sessions'] ?? 0),
                        'users' => (int)($row['totalUsers'] ?? 0),
                        'new_users' => 0,
                        'avg_session_duration' => 0,
                        'bounce_rate' => 0,
                        'engagement_rate' => (float)($row['engagementRate'] ?? 0),
                        'add_to_carts' => 0,
                        'begin_checkouts' => 0,
                        'purchases' => (int)($row['conversions'] ?? 0),
                        'revenue' => 0,
                    ];

                    // Insert quando il batch è pieno
                    if (count($batch) >= $batchSize) {
                        $totalInserted += $this->batchUpsertGa4Data($db, $batch);
                        $batch = [];
                    }
                }

                // Insert remaining
                if (!empty($batch)) {
                    $totalInserted += $this->batchUpsertGa4Data($db, $batch);
                }
            }

            $offset += $limit;

            // Libera memoria
            unset($response, $parsedRows, $batch);

        } while ($fetchedCount === $limit);

        return $totalInserted;
    }

    /**
     * Batch upsert per st_ga4_daily
     */
    private function batchUpsertGa4Daily(\PDO $db, array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        $columns = ['project_id', 'date', 'sessions', 'users', 'new_users', 'avg_session_duration',
                    'bounce_rate', 'engagement_rate', 'add_to_carts', 'begin_checkouts', 'purchases', 'revenue'];
        $columnList = implode(', ', $columns);

        $placeholders = [];
        $values = [];

        foreach ($records as $record) {
            $rowPlaceholders = array_fill(0, count($columns), '?');
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            foreach ($columns as $col) {
                $values[] = $record[$col] ?? null;
            }
        }

        $updateFields = ['sessions', 'users', 'new_users', 'avg_session_duration', 'bounce_rate',
                         'engagement_rate', 'add_to_carts', 'begin_checkouts', 'purchases', 'revenue'];
        $updateClauses = array_map(fn($f) => "{$f} = VALUES({$f})", $updateFields);

        $sql = "INSERT INTO st_ga4_daily ({$columnList}) VALUES "
             . implode(', ', $placeholders)
             . " ON DUPLICATE KEY UPDATE " . implode(', ', $updateClauses);

        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    /**
     * Batch upsert per st_ga4_data
     */
    private function batchUpsertGa4Data(\PDO $db, array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        $columns = ['project_id', 'date', 'landing_page', 'source_medium', 'country', 'device_category',
                    'sessions', 'users', 'new_users', 'avg_session_duration', 'bounce_rate', 'engagement_rate',
                    'add_to_carts', 'begin_checkouts', 'purchases', 'revenue'];
        $columnList = implode(', ', $columns);

        $placeholders = [];
        $values = [];

        foreach ($records as $record) {
            $rowPlaceholders = array_fill(0, count($columns), '?');
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            foreach ($columns as $col) {
                $values[] = $record[$col] ?? null;
            }
        }

        $updateFields = ['sessions', 'users', 'engagement_rate', 'purchases', 'revenue'];
        $updateClauses = array_map(fn($f) => "{$f} = VALUES({$f})", $updateFields);

        $sql = "INSERT INTO st_ga4_data ({$columnList}) VALUES "
             . implode(', ', $placeholders)
             . " ON DUPLICATE KEY UPDATE " . implode(', ', $updateClauses);

        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    /**
     * Revenue attribution: collega keyword GSC a conversioni GA4
     */
    private function attributeRevenue(int $projectId, string $startDate, string $endDate): void
    {
        // Per ogni giorno, distribuisce revenue proporzionalmente ai click GSC per landing page
        $ga4LandingData = $this->ga4Data->getByDateRange($projectId, $startDate, $endDate);

        // Raggruppa per data e landing page
        $revenueByPage = [];
        foreach ($ga4LandingData as $row) {
            $key = $row['date'] . '|' . $row['landing_page'];
            $revenueByPage[$key] = [
                'revenue' => $row['revenue'] ?? 0,
                'purchases' => $row['purchases'] ?? 0,
            ];
        }

        // Per ogni landing page con revenue, attribuisci alle keyword GSC
        foreach ($revenueByPage as $key => $data) {
            if ($data['revenue'] <= 0) {
                continue;
            }

            [$date, $landingPage] = explode('|', $key, 2);

            // Trova keyword GSC che hanno portato click a questa pagina
            $gscKeywords = $this->gscData->getKeywordsByPage($projectId, $landingPage, $date);

            if (empty($gscKeywords)) {
                continue;
            }

            // Calcola totale click
            $totalClicks = array_sum(array_column($gscKeywords, 'clicks'));

            if ($totalClicks <= 0) {
                continue;
            }

            // Distribuisci revenue proporzionalmente ai click
            foreach ($gscKeywords as $kw) {
                $clickShare = $kw['clicks'] / $totalClicks;
                $attributedRevenue = $data['revenue'] * $clickShare;
                $attributedPurchases = $data['purchases'] * $clickShare;

                $this->keywordRevenue->upsert([
                    'project_id' => $projectId,
                    'keyword' => $kw['query'],
                    'landing_page' => $landingPage,
                    'date' => $date,
                    'clicks' => $kw['clicks'],
                    'revenue' => $attributedRevenue,
                    'purchases' => $attributedPurchases,
                ]);
            }
        }
    }

    /**
     * HTTP GET request
     */
    private function httpGet(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $error = json_decode($response, true);
            return ['error' => $error['error'] ?? ['message' => 'HTTP ' . $httpCode]];
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * HTTP POST form-urlencoded
     */
    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    /**
     * HTTP POST JSON
     */
    private function httpPostJson(string $url, array $data, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $error = json_decode($response, true);
            return ['error' => $error['error'] ?? ['message' => 'HTTP ' . $httpCode]];
        }

        return json_decode($response, true) ?? [];
    }
}
