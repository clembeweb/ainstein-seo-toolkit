<?php

namespace Modules\SeoTracking\Services;

use Modules\SeoTracking\Models\Ga4Connection;
use Modules\SeoTracking\Models\Ga4Data;
use Modules\SeoTracking\Models\Ga4Daily;
use Modules\SeoTracking\Models\KeywordRevenue;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\SyncLog;
use Modules\SeoTracking\Models\GscData;

/**
 * Ga4Service
 * Gestisce Service Account JWT e API Google Analytics 4 Data API
 */
class Ga4Service
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GA4_API_BASE = 'https://analyticsdata.googleapis.com/v1beta';

    private Ga4Connection $ga4Connection;
    private Ga4Data $ga4Data;
    private Ga4Daily $ga4Daily;
    private KeywordRevenue $keywordRevenue;
    private Project $project;
    private SyncLog $syncLog;
    private GscData $gscData;

    public function __construct()
    {
        $this->ga4Connection = new Ga4Connection();
        $this->ga4Data = new Ga4Data();
        $this->ga4Daily = new Ga4Daily();
        $this->keywordRevenue = new KeywordRevenue();
        $this->project = new Project();
        $this->syncLog = new SyncLog();
        $this->gscData = new GscData();
    }

    /**
     * Valida JSON Service Account
     */
    public function validateServiceAccount(string $jsonContent): array
    {
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON non valido');
        }

        $required = ['type', 'project_id', 'private_key', 'client_email', 'token_uri'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Campo mancante: {$field}");
            }
        }

        if ($data['type'] !== 'service_account') {
            throw new \Exception('Il file deve essere di tipo service_account');
        }

        return $data;
    }

    /**
     * Genera JWT per Service Account
     */
    private function generateJwt(array $serviceAccount): string
    {
        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = $headerEncoded . '.' . $payloadEncoded;

        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);

        if (!$privateKey) {
            throw new \Exception('Chiave privata non valida');
        }

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Ottieni access token da Service Account
     */
    public function getAccessToken(int $projectId): string
    {
        $connection = $this->ga4Connection->getByProject($projectId);

        if (!$connection) {
            throw new \Exception('Connessione GA4 non trovata');
        }

        // Token ancora valido?
        if ($connection['token_expires_at'] && strtotime($connection['token_expires_at']) > time()) {
            return $connection['access_token'];
        }

        // Genera nuovo token
        $serviceAccount = json_decode($connection['service_account_json'], true);
        $jwt = $this->generateJwt($serviceAccount);

        $response = $this->httpPost(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (isset($response['error'])) {
            throw new \Exception('Token error: ' . ($response['error_description'] ?? $response['error']));
        }

        $accessToken = $response['access_token'];
        $expiresAt = date('Y-m-d H:i:s', time() + ($response['expires_in'] ?? 3600) - 60);

        // Salva token
        $this->ga4Connection->updateToken($projectId, $accessToken, $expiresAt);

        return $accessToken;
    }

    /**
     * Verifica accesso a property GA4
     */
    public function verifyPropertyAccess(int $projectId, string $propertyId): bool
    {
        try {
            $token = $this->getAccessToken($projectId);

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
     * Lista properties accessibili (richiede Admin API)
     */
    public function listProperties(int $projectId): array
    {
        // GA4 Admin API per listare properties
        // Nota: richiede scope aggiuntivo analytics.readonly non basta
        // Per semplicita, l'utente inserisce manualmente il Property ID
        return [];
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

        $token = $this->getAccessToken($projectId);
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
                    'users' => $row['users'] ?? 0,
                    'new_users' => $row['newUsers'] ?? 0,
                    'avg_session_duration' => $row['averageSessionDuration'] ?? 0,
                    'bounce_rate' => $row['bounceRate'] ?? 0,
                    'engagement_rate' => $row['engagementRate'] ?? 0,
                    'add_to_carts' => $row['addToCarts'] ?? 0,
                    'begin_checkouts' => $row['checkouts'] ?? 0,
                    'purchases' => $row['purchases'] ?? 0,
                    'revenue' => $row['purchaseRevenue'] ?? 0,
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
                    'users' => $row['users'] ?? 0,
                    'engaged_sessions' => $row['engagedSessions'] ?? 0,
                    'engagement_rate' => $row['engagementRate'] ?? 0,
                    'add_to_carts' => $row['addToCarts'] ?? 0,
                    'purchases' => $row['purchases'] ?? 0,
                    'revenue' => $row['purchaseRevenue'] ?? 0,
                ]);
            }

            // 3. Revenue attribution (collega GSC queries a GA4 conversioni)
            $this->attributeRevenue($projectId, $startDate, $endDate);

            $this->syncLog->complete($logId, [
                'status' => 'completed',
                'records_fetched' => $result['records_fetched'],
                'records_inserted' => $result['records_inserted'],
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->syncLog->fail($logId, $e->getMessage());
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
                ['name' => 'addToCarts'],
                ['name' => 'checkouts'],
                ['name' => 'purchases'],
                ['name' => 'purchaseRevenue'],
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
     * Fetch dati per landing page (solo traffico organico)
     */
    private function fetchLandingPageData(string $token, string $propertyId, string $startDate, string $endDate): array
    {
        $url = self::GA4_API_BASE . '/properties/' . $propertyId . ':runReport';

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
                ['name' => 'addToCarts'],
                ['name' => 'purchases'],
                ['name' => 'purchaseRevenue'],
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
            'limit' => 10000,
        ];

        $response = $this->httpPostJson($url, $body, $token);

        if (isset($response['error'])) {
            throw new \Exception('GA4 API error: ' . ($response['error']['message'] ?? 'Unknown'));
        }

        return $this->parseReportResponse($response, ['date', 'landingPage']);
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
