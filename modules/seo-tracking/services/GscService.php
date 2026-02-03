<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Core\Settings;
use Modules\SeoTracking\Models\GscConnection;
use Modules\SeoTracking\Models\GscData;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\KeywordPosition;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\SyncLog;

/**
 * GscService
 * Gestisce OAuth2 e API Google Search Console
 */
class GscService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_BASE = 'https://www.googleapis.com/webmasters/v3';
    private const SEARCHANALYTICS_URL = 'https://searchconsole.googleapis.com/webmasters/v3';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private GscConnection $gscConnection;
    private GscData $gscData;
    private GscDaily $gscDaily;
    private Keyword $keyword;
    private KeywordPosition $keywordPosition;
    private Project $project;
    private SyncLog $syncLog;

    public function __construct()
    {
        $this->clientId = Settings::get('gsc_client_id', '');
        $this->clientSecret = Settings::get('gsc_client_secret', '');
        $this->redirectUri = Settings::get('gsc_redirect_uri', '');

        $this->gscConnection = new GscConnection();
        $this->gscData = new GscData();
        $this->gscDaily = new GscDaily();
        $this->keyword = new Keyword();
        $this->keywordPosition = new KeywordPosition();
        $this->project = new Project();
        $this->syncLog = new SyncLog();
    }

    /**
     * Verifica se le credenziali sono configurate
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Genera URL autorizzazione OAuth2
     */
    public function getAuthUrl(int $projectId): string
    {
        $state = base64_encode(json_encode([
            'project_id' => $projectId,
            'csrf' => bin2hex(random_bytes(16)),
        ]));

        $_SESSION['gsc_oauth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
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
        $connection = $this->gscConnection->getByProject($projectId);

        if (!$connection) {
            return null;
        }

        // Token scaduto?
        if (strtotime($connection['token_expires_at']) < time()) {
            try {
                $tokens = $this->refreshToken($connection['refresh_token']);

                $expiresAt = time() + ($tokens['expires_in'] ?? 3600);

                $this->gscConnection->updateAccessToken($projectId, $tokens['access_token'], $expiresAt);

                return $tokens['access_token'];
            } catch (\Exception $e) {
                // Token invalido, disconnetti
                $this->gscConnection->delete($projectId);
                $this->project->setGscConnected($projectId, false);
                return null;
            }
        }

        return $connection['access_token'];
    }

    /**
     * Lista siti disponibili in GSC
     */
    public function listSites(int $projectId): array
    {
        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        $response = $this->httpGet(self::API_BASE . '/sites', $token);

        if (isset($response['error'])) {
            throw new \Exception('API error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['siteEntry'] ?? [];
    }

    /**
     * Verifica accesso a property
     */
    public function verifySiteAccess(int $projectId, string $siteUrl): bool
    {
        $token = $this->getValidToken($projectId);

        if (!$token) {
            return false;
        }

        $encodedUrl = urlencode($siteUrl);
        $response = $this->httpGet(self::API_BASE . '/sites/' . $encodedUrl, $token);

        return !isset($response['error']);
    }

    /**
     * Sync dati Search Analytics
     */
    public function syncSearchAnalytics(int $projectId, ?string $startDate = null, ?string $endDate = null): array
    {
        $connection = $this->gscConnection->getByProject($projectId);

        if (!$connection || !$connection['property_url']) {
            throw new \Exception('Connessione GSC non configurata');
        }

        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        // Date default: ultimi 7 giorni (GSC ha delay 2-3 giorni)
        $endDate = $endDate ?? date('Y-m-d', strtotime('-3 days'));
        $startDate = $startDate ?? date('Y-m-d', strtotime('-10 days'));

        // Crea log sync
        $logId = $this->syncLog->create([
            'project_id' => $projectId,
            'sync_type' => 'gsc_daily',
            'status' => 'running',
        ]);

        $this->project->updateSyncStatus($projectId, 'running');

        try {
            $result = [
                'records_fetched' => 0,
                'records_inserted' => 0,
                'records_updated' => 0,
            ];

            // 1. Fetch dati per query + page (keyword con URL associato)
            // Dimensions: query[0], page[1], date[2]
            $queryPageData = $this->fetchSearchAnalytics($token, $connection['property_url'], $startDate, $endDate, ['query', 'page', 'date']);
            $result['records_fetched'] += count($queryPageData);

            foreach ($queryPageData as $row) {
                $this->gscData->upsert([
                    'project_id' => $projectId,
                    'query' => $row['keys'][0],
                    'page' => $row['keys'][1],
                    'date' => $row['keys'][2],
                    'clicks' => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr' => $row['ctr'] ?? 0,
                    'position' => $row['position'] ?? 0,
                ]);
                $result['records_inserted']++;
            }

            // 3. Aggrega dati giornalieri
            $this->aggregateDailyData($projectId, $startDate, $endDate);

            // 4. Aggiorna posizioni keyword tracciate
            $this->updateTrackedKeywordPositions($projectId, $startDate, $endDate);

            // 5. Auto-discovery nuove keyword
            $this->autoDiscoverKeywords($projectId, $startDate, $endDate);

            $this->syncLog->complete($logId, [
                'status' => 'completed',
                'records_fetched' => $result['records_fetched'],
                'records_inserted' => $result['records_inserted'],
            ]);

            $this->project->updateSyncStatus($projectId, 'completed');

            // Aggiorna last_sync_at nella connessione GSC
            $this->gscConnection->updateLastSync($projectId);

            return $result;

        } catch (\Exception $e) {
            $this->syncLog->fail($logId, $e->getMessage());
            $this->project->updateSyncStatus($projectId, 'failed');
            throw $e;
        }
    }

    /**
     * Fetch Search Analytics da API
     */
    private function fetchSearchAnalytics(string $token, string $siteUrl, string $startDate, string $endDate, array $dimensions): array
    {
        $encodedUrl = urlencode($siteUrl);
        $url = self::SEARCHANALYTICS_URL . '/sites/' . $encodedUrl . '/searchAnalytics/query';

        $allRows = [];
        $startRow = 0;
        $rowLimit = 25000;

        do {
            $body = [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => $dimensions,
                'rowLimit' => $rowLimit,
                'startRow' => $startRow,
            ];

            $response = $this->httpPostJson($url, $body, $token);

            if (isset($response['error'])) {
                throw new \Exception('Search Analytics error: ' . ($response['error']['message'] ?? 'Unknown'));
            }

            $rows = $response['rows'] ?? [];
            $allRows = array_merge($allRows, $rows);
            $startRow += $rowLimit;

        } while (count($rows) === $rowLimit);

        return $allRows;
    }

    /**
     * Aggrega dati giornalieri
     */
    private function aggregateDailyData(int $projectId, string $startDate, string $endDate): void
    {
        $dailyAggregates = $this->gscData->getDailyAggregates($projectId, $startDate, $endDate);

        foreach ($dailyAggregates as $day) {
            $this->gscDaily->upsert([
                'project_id' => $projectId,
                'date' => $day['date'],
                'total_clicks' => $day['total_clicks'],
                'total_impressions' => $day['total_impressions'],
                'avg_ctr' => $day['avg_ctr'],
                'avg_position' => $day['avg_position'],
                'unique_queries' => $day['unique_queries'],
                'unique_pages' => $day['unique_pages'],
            ]);
        }
    }

    /**
     * Aggiorna posizioni keyword tracciate
     */
    private function updateTrackedKeywordPositions(int $projectId, string $startDate, string $endDate): void
    {
        $trackedKeywords = $this->keyword->allByProject($projectId, ['is_tracked' => 1]);

        foreach ($trackedKeywords as $kw) {
            $positions = $this->gscData->getKeywordPositions($projectId, $kw['keyword'], $startDate, $endDate);

            foreach ($positions as $pos) {
                $this->keywordPosition->upsert([
                    'project_id' => $projectId,
                    'keyword_id' => $kw['id'],
                    'date' => $pos['date'],
                    'avg_position' => $pos['position'],
                    'total_clicks' => $pos['clicks'],
                    'total_impressions' => $pos['impressions'],
                    'avg_ctr' => $pos['ctr'],
                ]);
            }

            // Aggiorna cache posizione attuale
            if (!empty($positions)) {
                $latest = end($positions);
                $this->keyword->updatePositionCache($kw['id'], [
                    'position' => $latest['position'],
                    'clicks' => $latest['clicks'],
                    'impressions' => $latest['impressions'],
                    'ctr' => $latest['ctr'],
                ]);
            }
        }
    }

    /**
     * Auto-discovery keyword con volume significativo
     */
    private function autoDiscoverKeywords(int $projectId, string $startDate, string $endDate): void
    {
        // Keyword con almeno 10 click negli ultimi 7 giorni
        $topQueries = $this->gscData->getTopQueries($projectId, $startDate, $endDate, 100, 10);

        foreach ($topQueries as $query) {
            // Skip query vuote o null
            if (empty($query['query'])) {
                continue;
            }

            // Verifica se esiste gia
            $existing = $this->keyword->findByKeyword($projectId, $query['query']);

            if (!$existing) {
                $this->keyword->create([
                    'project_id' => $projectId,
                    'keyword' => $query['query'],
                    'is_tracked' => 0,
                    'source' => 'auto_discovery',
                    'last_position' => $query['avg_position'],
                    'last_clicks' => $query['total_clicks'],
                    'last_impressions' => $query['total_impressions'],
                ]);
            } else {
                // Aggiorna metriche
                $this->keyword->update($existing['id'], [
                    'last_position' => $query['avg_position'],
                    'last_clicks' => $query['total_clicks'],
                    'last_impressions' => $query['total_impressions'],
                ]);
            }
        }
    }

    /**
     * Sync dati GSC per un range di date specifico (STREAMING - low memory)
     * @return int Numero di record importati
     */
    public function syncDateRange(int $projectId, string $startDate, string $endDate): int
    {
        $connection = $this->gscConnection->getByProject($projectId);

        if (!$connection || !$connection['property_url']) {
            throw new \Exception('Connessione GSC non configurata');
        }

        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        $db = Database::getInstance();
        $totalInserted = 0;

        // Usa streaming: fetch + insert in blocchi da API (max 5000 righe per request)
        $encodedUrl = urlencode($connection['property_url']);
        $url = self::SEARCHANALYTICS_URL . '/sites/' . $encodedUrl . '/searchAnalytics/query';

        $startRow = 0;
        $rowLimit = 5000; // Ridotto per risparmiare memoria

        do {
            $body = [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => ['query', 'page', 'date'],
                'rowLimit' => $rowLimit,
                'startRow' => $startRow,
            ];

            $response = $this->httpPostJson($url, $body, $token);

            if (isset($response['error'])) {
                throw new \Exception('Search Analytics error: ' . ($response['error']['message'] ?? 'Unknown'));
            }

            $rows = $response['rows'] ?? [];

            if (!empty($rows)) {
                // Converti e inserisci subito questo blocco
                $batch = [];
                foreach ($rows as $row) {
                    $batch[] = [
                        'project_id' => $projectId,
                        'query' => mb_substr($row['keys'][0] ?? '', 0, 990),
                        'page' => mb_substr($row['keys'][1] ?? '', 0, 990),
                        'date' => $row['keys'][2] ?? $startDate,
                        'clicks' => (int)($row['clicks'] ?? 0),
                        'impressions' => (int)($row['impressions'] ?? 0),
                        'ctr' => (float)($row['ctr'] ?? 0),
                        'position' => (float)($row['position'] ?? 0),
                        'country' => '',
                        'device' => null,
                    ];
                }

                $totalInserted += $this->batchUpsertGsc($db, $batch);
                unset($batch); // Libera memoria
            }

            $startRow += $rowLimit;
            $fetchedCount = count($rows);
            unset($rows, $response); // Libera memoria

        } while ($fetchedCount === $rowLimit);

        return $totalInserted;
    }

    /**
     * Batch upsert per st_gsc_data
     * @return int Numero di righe affected
     */
    private function batchUpsertGsc(\PDO $db, array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        $columns = ['project_id', 'query', 'page', 'date', 'clicks', 'impressions', 'ctr', 'position', 'country', 'device'];
        $columnList = implode(', ', $columns);

        // Placeholder per ogni record
        $placeholders = [];
        $values = [];

        foreach ($records as $record) {
            $rowPlaceholders = array_fill(0, count($columns), '?');
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';

            foreach ($columns as $col) {
                $values[] = $record[$col] ?? null;
            }
        }

        // UPDATE clause per ON DUPLICATE KEY
        $updateFields = ['clicks', 'impressions', 'ctr', 'position'];
        $updateClauses = [];
        foreach ($updateFields as $field) {
            $updateClauses[] = "{$field} = VALUES({$field})";
        }
        $updateClause = implode(', ', $updateClauses);

        $sql = "INSERT INTO st_gsc_data ({$columnList}) VALUES "
             . implode(', ', $placeholders)
             . " ON DUPLICATE KEY UPDATE {$updateClause}";

        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }

    /**
     * Sync metriche GSC SOLO per keyword tracciate (is_tracked=1)
     * Usato dal cron job automatico - nessuna auto-discovery
     *
     * @param int $projectId ID del progetto
     * @return array Risultato sync con count aggiornamenti
     */
    public function syncTrackedKeywordsOnly(int $projectId): array
    {
        $connection = $this->gscConnection->getByProject($projectId);

        if (!$connection || !$connection['property_url']) {
            throw new \Exception('Connessione GSC non configurata');
        }

        $token = $this->getValidToken($projectId);

        if (!$token) {
            throw new \Exception('Token non valido');
        }

        // Date: ultimi 7 giorni (GSC ha delay 2-3 giorni)
        $endDate = date('Y-m-d', strtotime('-3 days'));
        $startDate = date('Y-m-d', strtotime('-10 days'));

        // Ottieni keyword tracciate del progetto
        $trackedKeywords = $this->keyword->allByProject($projectId, ['is_tracked' => 1]);

        if (empty($trackedKeywords)) {
            return [
                'keywords_processed' => 0,
                'keywords_updated' => 0,
                'message' => 'Nessuna keyword tracciata nel progetto'
            ];
        }

        $keywordsUpdated = 0;

        // Fetch dati GSC per tutte le query del progetto
        $queryData = $this->fetchSearchAnalytics(
            $token,
            $connection['property_url'],
            $startDate,
            $endDate,
            ['query', 'date']
        );

        // Indicizza i dati GSC per keyword
        $gscDataByKeyword = [];
        foreach ($queryData as $row) {
            $query = $row['keys'][0];
            if (!isset($gscDataByKeyword[$query])) {
                $gscDataByKeyword[$query] = [];
            }
            $gscDataByKeyword[$query][] = [
                'date' => $row['keys'][1],
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => $row['ctr'] ?? 0,
                'position' => $row['position'] ?? 0,
            ];
        }

        // Aggiorna solo le keyword tracciate
        foreach ($trackedKeywords as $kw) {
            $keywordText = $kw['keyword'];

            // Cerca i dati GSC per questa keyword
            $matchingData = $gscDataByKeyword[$keywordText] ?? [];

            if (empty($matchingData)) {
                continue;
            }

            // Calcola metriche aggregate (media ultimi 7 giorni)
            $totalClicks = 0;
            $totalImpressions = 0;
            $positionSum = 0;
            $daysWithData = 0;

            foreach ($matchingData as $dayData) {
                $totalClicks += $dayData['clicks'];
                $totalImpressions += $dayData['impressions'];
                if ($dayData['impressions'] > 0) {
                    $positionSum += $dayData['position'];
                    $daysWithData++;
                }
            }

            $avgPosition = $daysWithData > 0 ? round($positionSum / $daysWithData, 1) : null;
            $avgCtr = $totalImpressions > 0 ? round($totalClicks / $totalImpressions, 4) : 0;

            // Aggiorna i dati cached nella keyword
            $this->keyword->update($kw['id'], [
                'last_position' => $avgPosition,
                'last_clicks' => $totalClicks,
                'last_impressions' => $totalImpressions,
                'last_ctr' => $avgCtr,
                'last_updated_at' => date('Y-m-d H:i:s'),
            ]);

            $keywordsUpdated++;

            // Salva anche in st_keyword_positions per lo storico giornaliero
            foreach ($matchingData as $dayData) {
                $this->keywordPosition->upsert([
                    'project_id' => $projectId,
                    'keyword_id' => $kw['id'],
                    'date' => $dayData['date'],
                    'avg_position' => $dayData['position'],
                    'total_clicks' => $dayData['clicks'],
                    'total_impressions' => $dayData['impressions'],
                    'avg_ctr' => $dayData['impressions'] > 0 ? $dayData['clicks'] / $dayData['impressions'] : 0,
                ]);
            }
        }

        // Aggiorna last_sync_at nella connessione GSC
        $this->gscConnection->updateLastSync($projectId);

        return [
            'keywords_processed' => count($trackedKeywords),
            'keywords_updated' => $keywordsUpdated,
            'date_range' => "{$startDate} - {$endDate}",
        ];
    }

    /**
     * Full sync storico (16 mesi)
     */
    public function fullHistoricalSync(int $projectId): array
    {
        $endDate = date('Y-m-d', strtotime('-3 days'));
        $startDate = date('Y-m-d', strtotime('-16 months'));

        // Crea log sync
        $logId = $this->syncLog->create([
            'project_id' => $projectId,
            'sync_type' => 'gsc_full',
            'status' => 'running',
        ]);

        $this->project->updateSyncStatus($projectId, 'running');

        try {
            $totalRecords = 0;

            // Sync mese per mese per evitare timeout
            $currentStart = $startDate;

            while ($currentStart < $endDate) {
                $currentEnd = date('Y-m-d', min(
                    strtotime($currentStart . ' +1 month'),
                    strtotime($endDate)
                ));

                $result = $this->syncSearchAnalytics($projectId, $currentStart, $currentEnd);
                $totalRecords += $result['records_fetched'];

                $currentStart = date('Y-m-d', strtotime($currentEnd . ' +1 day'));
            }

            $this->syncLog->complete($logId, [
                'status' => 'completed',
                'records_fetched' => $totalRecords,
            ]);

            $this->project->updateSyncStatus($projectId, 'completed');

            return ['records_fetched' => $totalRecords];

        } catch (\Exception $e) {
            $this->syncLog->fail($logId, $e->getMessage());
            $this->project->updateSyncStatus($projectId, 'failed');
            throw $e;
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
