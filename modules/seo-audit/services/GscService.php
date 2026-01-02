<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Core\Credits;
use Modules\SeoAudit\Models\Project;
use Services\ScraperService;

/**
 * GscService
 *
 * Servizio per integrazione Google Search Console API
 * Uses shared ScraperService for HTTP requests
 */
class GscService
{
    private const MODULE_SLUG = 'seo-audit';
    private const OAUTH_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GSC_API_BASE = 'https://searchconsole.googleapis.com/webmasters/v3';
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $encryptionKey = null;
    private bool $mockMode = false;
    private ScraperService $scraper;

    public function __construct()
    {
        $this->scraper = new ScraperService();
        $this->loadSettings();
    }

    /**
     * Carica impostazioni GSC da DB o module settings
     */
    private function loadSettings(): void
    {
        // Load settings from settings table
        $clientId = Database::fetch("SELECT value FROM settings WHERE key_name = 'gsc_client_id'");
        $clientSecret = Database::fetch("SELECT value FROM settings WHERE key_name = 'gsc_client_secret'");

        $this->clientId = $clientId['value'] ?? null;
        $this->clientSecret = $clientSecret['value'] ?? null;

        // Encryption key from config
        $configFile = dirname(__DIR__, 3) . '/config/app.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $this->encryptionKey = $config['encryption_key'] ?? $config['app_key'] ?? 'seo-toolkit-default-key';

        // Enable mock mode if credentials not configured
        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->mockMode = true;
        }
    }

    /**
     * Verifica se GSC è configurato
     */
    public function isConfigured(): bool
    {
        return !$this->mockMode && !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Verifica se è in mock mode
     */
    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * Genera URL per OAuth consent
     */
    public function getAuthUrl(int $projectId): string
    {
        if ($this->mockMode) {
            return url('/seo-audit/gsc/callback?mock=1&project_id=' . $projectId);
        }

        $state = base64_encode(json_encode([
            'project_id' => $projectId,
            'csrf' => bin2hex(random_bytes(16)),
        ]));

        // Store state in session
        $_SESSION['gsc_oauth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::OAUTH_AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Redirect URI per OAuth callback
     * MUST return full URL for Google OAuth (not relative path)
     */
    public function getRedirectUri(): string
    {
        // Load APP_URL from config
        $configFile = dirname(__DIR__, 3) . '/config/app.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $appUrl = $config['url'] ?? 'http://localhost';

        return rtrim($appUrl, '/') . '/seo-audit/gsc/callback';
    }

    /**
     * Scambia authorization code per tokens
     */
    public function exchangeCode(string $code): array
    {
        if ($this->mockMode) {
            return $this->getMockTokens();
        }

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getRedirectUri(),
        ];

        $response = $this->httpPost(self::OAUTH_TOKEN_URL, $data);

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['error_description'] ?? $response['error'],
            ];
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_in' => $response['expires_in'] ?? 3600,
        ];
    }

    /**
     * Refresh token se necessario
     */
    public function refreshTokenIfNeeded(array $connection): ?array
    {
        if ($this->mockMode) {
            return $this->getMockTokens();
        }

        $expiresAt = strtotime($connection['token_expires_at']);

        // Refresh se scade entro 5 minuti
        if ($expiresAt > time() + 300) {
            return null; // Token still valid
        }

        $refreshToken = $this->decryptToken($connection['refresh_token']);

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->httpPost(self::OAUTH_TOKEN_URL, $data);

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['error_description'] ?? 'Token refresh failed',
            ];
        }

        $newTokens = [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $refreshToken,
            'expires_in' => $response['expires_in'] ?? 3600,
        ];

        // Update in database
        $this->updateConnectionTokens($connection['id'], $newTokens);

        return $newTokens;
    }

    /**
     * Lista proprietà GSC disponibili
     */
    public function listProperties(string $accessToken): array
    {
        if ($this->mockMode) {
            return $this->getMockProperties();
        }

        $response = $this->httpGet(
            self::GSC_API_BASE . '/sites',
            $accessToken
        );

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['error']['message'] ?? 'Failed to list properties',
            ];
        }

        $properties = [];
        foreach ($response['siteEntry'] ?? [] as $site) {
            $properties[] = [
                'url' => $site['siteUrl'],
                'type' => strpos($site['siteUrl'], 'sc-domain:') === 0 ? 'DOMAIN' : 'URL_PREFIX',
                'permission_level' => $site['permissionLevel'] ?? 'siteUnverifiedUser',
            ];
        }

        return ['properties' => $properties];
    }

    /**
     * Fetch performance data (queries e pages)
     */
    public function fetchPerformanceData(int $projectId, string $startDate, string $endDate): array
    {
        $connection = $this->getConnection($projectId);
        if (!$connection) {
            return ['error' => true, 'message' => 'GSC non connesso'];
        }

        if ($this->mockMode) {
            return $this->getMockPerformanceData($startDate, $endDate);
        }

        // Refresh token if needed
        $tokens = $this->refreshTokenIfNeeded($connection);
        $accessToken = $tokens ? $tokens['access_token'] : $this->decryptToken($connection['access_token']);

        $propertyUrl = $connection['property_url'];

        // Fetch queries
        $queryData = $this->searchAnalyticsQuery($accessToken, $propertyUrl, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['query'],
            'rowLimit' => 1000,
        ]);

        // Fetch pages
        $pageData = $this->searchAnalyticsQuery($accessToken, $propertyUrl, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['page'],
            'rowLimit' => 1000,
        ]);

        // Fetch daily totals
        $dailyData = $this->searchAnalyticsQuery($accessToken, $propertyUrl, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['date'],
            'rowLimit' => 100,
        ]);

        return [
            'queries' => $queryData['rows'] ?? [],
            'pages' => $pageData['rows'] ?? [],
            'daily' => $dailyData['rows'] ?? [],
            'totals' => [
                'clicks' => array_sum(array_column($dailyData['rows'] ?? [], 'clicks')),
                'impressions' => array_sum(array_column($dailyData['rows'] ?? [], 'impressions')),
                'ctr' => $this->calculateAvgCtr($dailyData['rows'] ?? []),
                'position' => $this->calculateAvgPosition($dailyData['rows'] ?? []),
            ],
        ];
    }

    /**
     * Search Analytics API query
     */
    private function searchAnalyticsQuery(string $accessToken, string $siteUrl, array $params): array
    {
        $url = self::GSC_API_BASE . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query';

        $response = $this->httpPost($url, $params, $accessToken);

        if (isset($response['error'])) {
            return ['error' => true, 'message' => $response['error']['message'] ?? 'Query failed'];
        }

        // Transform rows
        $rows = [];
        foreach ($response['rows'] ?? [] as $row) {
            $item = [
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => $row['ctr'] ?? 0,
                'position' => $row['position'] ?? 0,
            ];

            // Add dimension keys
            foreach ($params['dimensions'] as $i => $dim) {
                $item[$dim] = $row['keys'][$i] ?? null;
            }

            $rows[] = $item;
        }

        return ['rows' => $rows];
    }

    /**
     * Sync dati giornalieri degli ultimi N giorni
     */
    public function syncDailyData(int $projectId, int $days = 3): array
    {
        $connection = $this->getConnection($projectId);
        if (!$connection) {
            return ['error' => true, 'message' => 'GSC non connesso'];
        }

        $endDate = date('Y-m-d', strtotime('-1 day')); // GSC data available after 1 day
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $data = $this->fetchPerformanceData($projectId, $startDate, $endDate);

        if (isset($data['error'])) {
            return $data;
        }

        // Save to database
        $saved = $this->savePerformanceData($projectId, $data, $startDate, $endDate);

        // Update last sync
        Database::update('sa_gsc_connections', ['last_sync_at' => date('Y-m-d H:i:s')], 'project_id = ?', [$projectId]);

        // Log sync
        Database::insert('sa_gsc_sync_log', [
            'project_id' => $projectId,
            'sync_type' => 'performance',
            'date_range_start' => $startDate,
            'date_range_end' => $endDate,
            'rows_fetched' => count($data['queries']) + count($data['pages']),
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'queries_synced' => count($data['queries']),
            'pages_synced' => count($data['pages']),
            'date_range' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    /**
     * Salva performance data nel DB
     */
    private function savePerformanceData(int $projectId, array $data, string $startDate, string $endDate): int
    {
        // Clear existing data for date range
        Database::delete('sa_gsc_performance', 'project_id = ? AND date BETWEEN ? AND ?', [$projectId, $startDate, $endDate]);

        $count = 0;

        // Save query data
        foreach ($data['queries'] as $row) {
            Database::insert('sa_gsc_performance', [
                'project_id' => $projectId,
                'date' => $endDate, // Use end date for aggregate
                'query' => $row['query'] ?? null,
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'ctr' => $row['ctr'],
                'position' => $row['position'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        // Save page data
        foreach ($data['pages'] as $row) {
            Database::insert('sa_gsc_performance', [
                'project_id' => $projectId,
                'date' => $endDate,
                'page' => $row['page'] ?? null,
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'ctr' => $row['ctr'],
                'position' => $row['position'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        // Save daily data
        foreach ($data['daily'] as $row) {
            if (isset($row['date'])) {
                Database::insert('sa_gsc_performance', [
                    'project_id' => $projectId,
                    'date' => $row['date'],
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'ctr' => $row['ctr'],
                    'position' => $row['position'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $count;
    }

    /**
     * Salva connessione GSC
     */
    public function saveConnection(int $projectId, int $userId, array $tokens, string $propertyUrl, string $propertyType): int
    {
        // Remove existing connection
        Database::delete('sa_gsc_connections', 'project_id = ?', [$projectId]);

        $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

        $connectionId = Database::insert('sa_gsc_connections', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'access_token' => $this->encryptToken($tokens['access_token']),
            'refresh_token' => $this->encryptToken($tokens['refresh_token'] ?? ''),
            'token_expires_at' => $expiresAt,
            'property_url' => $propertyUrl,
            'property_type' => $propertyType,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update project gsc_connected flag
        Database::update('sa_projects', ['gsc_connected' => 1], 'id = ?', [$projectId]);

        return $connectionId;
    }

    /**
     * Rimuovi connessione GSC
     */
    public function disconnect(int $projectId): bool
    {
        Database::delete('sa_gsc_connections', 'project_id = ?', [$projectId]);
        Database::delete('sa_gsc_performance', 'project_id = ?', [$projectId]);
        Database::update('sa_projects', ['gsc_connected' => 0], 'id = ?', [$projectId]);

        return true;
    }

    /**
     * Ottieni connessione per progetto
     */
    public function getConnection(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM sa_gsc_connections WHERE project_id = ? AND is_active = 1",
            [$projectId]
        ) ?: null;
    }

    /**
     * Ottieni statistiche GSC aggregate
     */
    public function getStats(int $projectId, int $days = 28): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        // Totali aggregati (solo righe senza query/page specifici = totali giornalieri)
        $totals = Database::fetch("
            SELECT
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(ctr) as avg_ctr,
                AVG(position) as avg_position
            FROM sa_gsc_performance
            WHERE project_id = ? AND date >= ? AND query IS NULL AND page IS NULL
        ", [$projectId, $startDate]);

        // Top queries
        $topQueries = Database::fetchAll("
            SELECT query, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position
            FROM sa_gsc_performance
            WHERE project_id = ? AND date >= ? AND query IS NOT NULL
            GROUP BY query
            ORDER BY clicks DESC
            LIMIT 10
        ", [$projectId, $startDate]);

        // Top pages
        $topPages = Database::fetchAll("
            SELECT page, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position
            FROM sa_gsc_performance
            WHERE project_id = ? AND date >= ? AND page IS NOT NULL
            GROUP BY page
            ORDER BY clicks DESC
            LIMIT 10
        ", [$projectId, $startDate]);

        // Daily trend
        $dailyTrend = Database::fetchAll("
            SELECT date, SUM(clicks) as clicks, SUM(impressions) as impressions
            FROM sa_gsc_performance
            WHERE project_id = ? AND date >= ? AND query IS NULL AND page IS NULL
            GROUP BY date
            ORDER BY date ASC
        ", [$projectId, $startDate]);

        return [
            'totals' => [
                'clicks' => (int) ($totals['total_clicks'] ?? 0),
                'impressions' => (int) ($totals['total_impressions'] ?? 0),
                'ctr' => round(($totals['avg_ctr'] ?? 0) * 100, 2),
                'position' => round($totals['avg_position'] ?? 0, 1),
            ],
            'top_queries' => $topQueries,
            'top_pages' => $topPages,
            'daily_trend' => $dailyTrend,
        ];
    }

    /**
     * Aggiorna tokens della connessione
     */
    private function updateConnectionTokens(int $connectionId, array $tokens): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

        Database::update('sa_gsc_connections', [
            'access_token' => $this->encryptToken($tokens['access_token']),
            'refresh_token' => $this->encryptToken($tokens['refresh_token']),
            'token_expires_at' => $expiresAt,
        ], 'id = ?', [$connectionId]);
    }

    /**
     * Cripta token
     */
    private function encryptToken(string $token): string
    {
        if (empty($token)) return '';

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($token, 'aes-256-cbc', $this->encryptionKey, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decripta token
     */
    private function decryptToken(string $encrypted): string
    {
        if (empty($encrypted)) return '';

        $data = base64_decode($encrypted);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryptionKey, 0, $iv) ?: '';
    }

    /**
     * HTTP GET request via shared ScraperService
     */
    private function httpGet(string $url, string $accessToken): array
    {
        $result = $this->scraper->fetchJson($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
        ]);

        return $result['data'] ?? [];
    }

    /**
     * HTTP POST request via shared ScraperService
     */
    private function httpPost(string $url, array $data, ?string $accessToken = null): array
    {
        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $result = $this->scraper->postJson($url, $data, $headers);

        return $result['data'] ?? [];
    }

    /**
     * Calcola CTR medio
     */
    private function calculateAvgCtr(array $rows): float
    {
        if (empty($rows)) return 0;
        $total = array_sum(array_column($rows, 'ctr'));
        return $total / count($rows);
    }

    /**
     * Calcola posizione media
     */
    private function calculateAvgPosition(array $rows): float
    {
        if (empty($rows)) return 0;
        $total = array_sum(array_column($rows, 'position'));
        return $total / count($rows);
    }

    // =========================================
    // MOCK DATA (per testing senza API reali)
    // =========================================

    private function getMockTokens(): array
    {
        return [
            'access_token' => 'mock_access_token_' . bin2hex(random_bytes(16)),
            'refresh_token' => 'mock_refresh_token_' . bin2hex(random_bytes(16)),
            'expires_in' => 3600,
        ];
    }

    private function getMockProperties(): array
    {
        return [
            'properties' => [
                [
                    'url' => 'https://example.com/',
                    'type' => 'URL_PREFIX',
                    'permission_level' => 'siteOwner',
                ],
                [
                    'url' => 'sc-domain:example.com',
                    'type' => 'DOMAIN',
                    'permission_level' => 'siteOwner',
                ],
            ],
        ];
    }

    private function getMockPerformanceData(string $startDate, string $endDate): array
    {
        $queries = [];
        $pages = [];
        $daily = [];

        // Generate mock queries
        $mockQueries = ['seo tools', 'internal links', 'website audit', 'keyword research', 'site analysis'];
        foreach ($mockQueries as $i => $query) {
            $queries[] = [
                'query' => $query,
                'clicks' => rand(10, 500),
                'impressions' => rand(1000, 10000),
                'ctr' => rand(1, 10) / 100,
                'position' => rand(1, 50),
            ];
        }

        // Generate mock pages
        $mockPages = ['/', '/features', '/pricing', '/blog', '/contact'];
        foreach ($mockPages as $page) {
            $pages[] = [
                'page' => 'https://example.com' . $page,
                'clicks' => rand(50, 1000),
                'impressions' => rand(2000, 20000),
                'ctr' => rand(2, 15) / 100,
                'position' => rand(1, 30),
            ];
        }

        // Generate daily data
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        while ($start <= $end) {
            $daily[] = [
                'date' => $start->format('Y-m-d'),
                'clicks' => rand(100, 1000),
                'impressions' => rand(5000, 50000),
                'ctr' => rand(1, 5) / 100,
                'position' => rand(5, 25),
            ];
            $start->modify('+1 day');
        }

        return [
            'queries' => $queries,
            'pages' => $pages,
            'daily' => $daily,
            'totals' => [
                'clicks' => array_sum(array_column($daily, 'clicks')),
                'impressions' => array_sum(array_column($daily, 'impressions')),
                'ctr' => $this->calculateAvgCtr($daily),
                'position' => $this->calculateAvgPosition($daily),
            ],
        ];
    }
}
