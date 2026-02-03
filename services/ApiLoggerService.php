<?php

namespace Services;

use Core\Database;
use Core\Auth;

/**
 * ApiLoggerService
 *
 * Servizio centralizzato per il logging di tutte le chiamate API esterne.
 * Traccia: DataForSEO, SerpAPI, Serper.dev, Google APIs, ecc.
 *
 * Uso:
 * ```php
 * $startTime = microtime(true);
 * // ... chiamata API ...
 * ApiLoggerService::log('dataforseo', '/endpoint', $request, $response, $httpCode, $startTime, [
 *     'module' => 'seo-tracking',
 *     'cost' => 0.002,
 *     'context' => 'keyword=test'
 * ]);
 * ```
 */
class ApiLoggerService
{
    // Dimensione massima payload in bytes (50KB)
    private const MAX_PAYLOAD_SIZE = 50000;

    // Provider noti con i loro costi medi per riferimento
    public const PROVIDERS = [
        'dataforseo' => 'DataForSEO',
        'serpapi' => 'SERP API',
        'serper' => 'Serper.dev',
        'google_gsc' => 'Google Search Console',
        'google_oauth' => 'Google OAuth',
        'google_ga4' => 'Google Analytics 4',
    ];

    /**
     * Log una chiamata API
     *
     * @param string $provider Nome provider (dataforseo, serpapi, serper, google_*)
     * @param string $endpoint Endpoint chiamato (es: /serp/google/organic/live/regular)
     * @param array $request Payload della richiesta
     * @param array|null $response Payload della risposta (null se errore prima della risposta)
     * @param int $httpCode Codice HTTP della risposta
     * @param float $startTime Timestamp di inizio (da microtime(true))
     * @param array $options Opzioni aggiuntive:
     *                       - user_id: ID utente (default: utente loggato)
     *                       - module: Slug modulo (default: 'unknown')
     *                       - method: Metodo HTTP (default: 'POST')
     *                       - cost: Costo in USD (default: 0)
     *                       - credits: Crediti piattaforma usati (default: 0)
     *                       - context: Contesto aggiuntivo (default: null)
     *                       - error: Messaggio errore custom (default: estratto da response)
     */
    public static function log(
        string $provider,
        string $endpoint,
        array $request,
        ?array $response,
        int $httpCode,
        float $startTime,
        array $options = []
    ): void {
        try {
            // Calcola durata
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Determina user_id
            $userId = $options['user_id'] ?? null;
            if ($userId === null && class_exists('\Core\Auth') && Auth::check()) {
                $userId = Auth::id();
            }

            // Trunca payload se troppo grandi
            $requestJson = self::truncatePayload($request);
            $responseJson = $response !== null ? self::truncatePayload($response) : null;

            // Determina status
            $status = self::determineStatus($httpCode, $response);

            // Estrai errore
            $errorMessage = $options['error'] ?? self::extractError($response, $httpCode);

            // Prepara dati per insert
            $data = [
                'user_id' => $userId,
                'module_slug' => $options['module'] ?? 'unknown',
                'provider' => $provider,
                'endpoint' => self::truncateString($endpoint, 255),
                'method' => strtoupper($options['method'] ?? 'POST'),
                'request_payload' => $requestJson,
                'response_payload' => $responseJson,
                'response_code' => $httpCode,
                'duration_ms' => $durationMs,
                'cost' => (float) ($options['cost'] ?? 0),
                'credits_used' => (float) ($options['credits'] ?? 0),
                'status' => $status,
                'error_message' => $errorMessage ? self::truncateString($errorMessage, 65535) : null,
                'context' => isset($options['context']) ? self::truncateString($options['context'], 500) : null,
                'ip_address' => self::getClientIp(),
            ];

            Database::insert('api_logs', $data);

        } catch (\Exception $e) {
            // Non bloccare mai il flusso principale per errori di logging
            error_log("[ApiLoggerService] Failed to log API call: " . $e->getMessage());
        }
    }

    /**
     * Log rapido per chiamate di successo
     */
    public static function logSuccess(
        string $provider,
        string $endpoint,
        array $request,
        array $response,
        int $httpCode,
        float $startTime,
        string $module,
        float $cost = 0,
        ?string $context = null
    ): void {
        self::log($provider, $endpoint, $request, $response, $httpCode, $startTime, [
            'module' => $module,
            'cost' => $cost,
            'context' => $context,
        ]);
    }

    /**
     * Log rapido per errori
     */
    public static function logError(
        string $provider,
        string $endpoint,
        array $request,
        ?array $response,
        int $httpCode,
        float $startTime,
        string $module,
        string $errorMessage,
        ?string $context = null
    ): void {
        self::log($provider, $endpoint, $request, $response, $httpCode, $startTime, [
            'module' => $module,
            'error' => $errorMessage,
            'context' => $context,
        ]);
    }

    /**
     * Trunca payload JSON mantenendo struttura leggibile
     */
    private static function truncatePayload(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return json_encode(['_error' => 'Failed to encode payload']);
        }

        if (strlen($json) <= self::MAX_PAYLOAD_SIZE) {
            return $json;
        }

        // Payload troppo grande - trunca intelligentemente
        $truncated = $data;
        $truncated['_truncated'] = true;
        $truncated['_original_size_bytes'] = strlen($json);

        // Rimuovi o riduci campi comuni che possono essere grandi
        $largeFields = ['items', 'organic_results', 'results', 'data', 'tasks', 'keywords'];

        foreach ($largeFields as $field) {
            if (isset($truncated[$field]) && is_array($truncated[$field])) {
                $originalCount = count($truncated[$field]);
                $truncated[$field] = array_slice($truncated[$field], 0, 5);
                $truncated["_{$field}_truncated_from"] = $originalCount;
            }
        }

        // Se ancora troppo grande, riduci ulteriormente
        $json = json_encode($truncated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (strlen($json) > self::MAX_PAYLOAD_SIZE) {
            // Ultima risorsa: tronca brutalmente mantenendo JSON valido
            return json_encode([
                '_truncated' => true,
                '_original_size_bytes' => strlen(json_encode($data)),
                '_message' => 'Payload too large to store',
                '_keys' => array_keys($data),
            ], JSON_UNESCAPED_UNICODE);
        }

        return $json;
    }

    /**
     * Determina lo status della chiamata
     */
    private static function determineStatus(int $httpCode, ?array $response): string
    {
        // Rate limited
        if ($httpCode === 429) {
            return 'rate_limited';
        }

        // Errore HTTP
        if ($httpCode >= 400) {
            return 'error';
        }

        // Errore nel payload (vari formati)
        if ($response !== null) {
            // DataForSEO format
            if (isset($response['status_code']) && $response['status_code'] !== 20000) {
                return 'error';
            }

            // Generic error fields
            if (isset($response['error']) && $response['error'] !== false && $response['error'] !== null) {
                return 'error';
            }

            // Success false
            if (isset($response['success']) && $response['success'] === false) {
                return 'error';
            }
        }

        return 'success';
    }

    /**
     * Estrai messaggio di errore dalla risposta
     */
    private static function extractError(?array $response, int $httpCode): ?string
    {
        if ($response === null) {
            return $httpCode >= 400 ? "HTTP Error {$httpCode}" : null;
        }

        // Vari formati di errore
        $errorFields = [
            'error',
            'error_message',
            'message',
            'status_message',
            'errorMessage',
            'msg',
        ];

        foreach ($errorFields as $field) {
            if (isset($response[$field])) {
                $value = $response[$field];
                if (is_string($value) && !empty($value)) {
                    return $value;
                }
                if (is_array($value) && isset($value['message'])) {
                    return $value['message'];
                }
            }
        }

        // DataForSEO specific
        if (isset($response['tasks'][0]['status_message'])) {
            $msg = $response['tasks'][0]['status_message'];
            if ($msg !== 'Ok.') {
                return $msg;
            }
        }

        return $httpCode >= 400 ? "HTTP Error {$httpCode}" : null;
    }

    /**
     * Tronca stringa a lunghezza massima
     */
    private static function truncateString(string $str, int $maxLength): string
    {
        if (strlen($str) <= $maxLength) {
            return $str;
        }
        return substr($str, 0, $maxLength - 3) . '...';
    }

    /**
     * Ottieni IP client
     */
    private static function getClientIp(): ?string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR',               // Direct
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For può contenere multipli IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Ottieni statistiche per admin dashboard
     */
    public static function getStats(int $hours24 = 24, int $days30 = 30): array
    {
        try {
            // Stats ultime 24 ore
            $stats24h = Database::fetch(
                "SELECT
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
                    SUM(CASE WHEN status = 'rate_limited' THEN 1 ELSE 0 END) as rate_limited_count,
                    SUM(cost) as total_cost,
                    AVG(duration_ms) as avg_duration
                 FROM api_logs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)",
                [$hours24]
            );

            // Stats ultimi 30 giorni
            $stats30d = Database::fetch(
                "SELECT
                    COUNT(*) as total_calls,
                    SUM(cost) as total_cost,
                    SUM(credits_used) as total_credits
                 FROM api_logs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days30]
            );

            // Breakdown per provider (ultimi 30 giorni)
            $byProvider = Database::fetchAll(
                "SELECT
                    provider,
                    COUNT(*) as calls,
                    SUM(cost) as cost,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
                 FROM api_logs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY provider
                 ORDER BY calls DESC",
                [$days30]
            );

            return [
                'last_24h' => [
                    'total_calls' => (int) ($stats24h['total_calls'] ?? 0),
                    'success_count' => (int) ($stats24h['success_count'] ?? 0),
                    'error_count' => (int) ($stats24h['error_count'] ?? 0),
                    'rate_limited_count' => (int) ($stats24h['rate_limited_count'] ?? 0),
                    'total_cost' => (float) ($stats24h['total_cost'] ?? 0),
                    'avg_duration_ms' => (int) ($stats24h['avg_duration'] ?? 0),
                ],
                'last_30d' => [
                    'total_calls' => (int) ($stats30d['total_calls'] ?? 0),
                    'total_cost' => (float) ($stats30d['total_cost'] ?? 0),
                    'total_credits' => (float) ($stats30d['total_credits'] ?? 0),
                ],
                'by_provider' => $byProvider,
            ];
        } catch (\Exception $e) {
            error_log("[ApiLoggerService] Failed to get stats: " . $e->getMessage());
            return [
                'last_24h' => ['total_calls' => 0, 'success_count' => 0, 'error_count' => 0, 'rate_limited_count' => 0, 'total_cost' => 0, 'avg_duration_ms' => 0],
                'last_30d' => ['total_calls' => 0, 'total_cost' => 0, 'total_credits' => 0],
                'by_provider' => [],
            ];
        }
    }
}
