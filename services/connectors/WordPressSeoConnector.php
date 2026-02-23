<?php

namespace Services\Connectors;

use Services\ApiLoggerService;

/**
 * WordPress SEO Connector - Recupera dati SEO audit dal plugin WordPress
 *
 * Autenticazione: X-SEO-Toolkit-Key header (plugin seo-toolkit-connector)
 * Endpoint: /wp-json/seo-toolkit/v1/seo-audit
 * Uso: importazione dati SEO da WordPress per modulo seo-audit
 */
class WordPressSeoConnector
{
    private string $url;
    private string $apiKey;

    private const TIMEOUT = 60; // Siti grandi possono avere centinaia di pagine
    private const CONNECT_TIMEOUT = 10;
    private const PROVIDER = 'wordpress_api';
    private const MODULE = 'seo-audit';

    /**
     * @param array $config ['url' => string, 'api_key' => string]
     * @throws \InvalidArgumentException Se mancano parametri obbligatori
     */
    public function __construct(array $config)
    {
        if (empty($config['url'])) {
            throw new \InvalidArgumentException('URL del sito WordPress obbligatorio');
        }
        if (empty($config['api_key'])) {
            throw new \InvalidArgumentException('API Key del plugin SEO Toolkit obbligatoria');
        }

        $this->url = rtrim($config['url'], '/');
        $this->apiKey = $config['api_key'];
    }

    /**
     * Test connessione al sito WordPress
     * Ritorna informazioni sul sito in caso di successo
     */
    public function test(): array
    {
        $result = $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/ping');

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Connessione fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
                'details' => [],
            ];
        }

        $data = $result['data'] ?? [];

        return [
            'success' => true,
            'message' => 'Connessione a WordPress riuscita',
            'details' => [
                'site_name' => $data['site_name'] ?? '',
                'wp_version' => $data['wp_version'] ?? '',
                'plugin_version' => $data['plugin_version'] ?? '',
                'seo_plugin' => $data['seo_plugin'] ?? 'none',
                'url' => $this->url,
            ],
            'seo_plugin' => $data['seo_plugin'] ?? 'none',
            'wp_version' => $data['wp_version'] ?? '',
            'plugin_version' => $data['plugin_version'] ?? '',
            'site_name' => $data['site_name'] ?? '',
        ];
    }

    /**
     * Recupera dati SEO audit da WordPress
     * Ritorna pagine paginate con tutti i dati SEO pre-estratti
     *
     * @param int $page Pagina corrente (1-based)
     * @param int $perPage Elementi per pagina (max 100)
     * @param string $types Tipi di contenuto separati da virgola (es. 'post,page')
     */
    public function fetchSeoAudit(int $page = 1, int $perPage = 50, string $types = 'post,page'): array
    {
        $params = http_build_query([
            'page' => $page,
            'per_page' => min($perPage, 100),
            'type' => $types,
        ]);

        $result = $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/seo-audit?' . $params);

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Errore durante il recupero dati',
                'total' => 0,
                'total_pages' => 0,
                'current_page' => $page,
                'pages' => [],
            ];
        }

        $data = $result['data'] ?? [];

        return [
            'success' => true,
            'total' => $data['total'] ?? 0,
            'total_pages' => $data['total_pages'] ?? 0,
            'current_page' => $data['current_page'] ?? $page,
            'per_page' => $data['per_page'] ?? $perPage,
            'site_info' => $data['site_info'] ?? null,
            'pages' => $data['pages'] ?? [],
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Esegue una richiesta HTTP verso l'API WordPress (plugin SEO Toolkit)
     * Pattern identico a content-creator WordPressConnector::makeRequest()
     */
    private function makeRequest(string $method, string $endpoint, ?array $body = null): array
    {
        $url = $this->url . $endpoint;
        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-SEO-Toolkit-Key: ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Prepara payload per logging (senza credenziali)
        $logRequest = [
            'method' => $method,
            'endpoint' => $endpoint,
            'has_body' => $body !== null,
        ];

        // Errore cURL
        if ($response === false) {
            $logResponse = ['error' => $curlError];
            ApiLoggerService::log(self::PROVIDER, $endpoint, $logRequest, $logResponse, 0, $startTime, [
                'module' => self::MODULE,
                'method' => $method,
                'error' => $curlError,
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Errore di connessione: ' . $curlError,
                'http_code' => 0,
            ];
        }

        // Decodifica JSON
        $data = json_decode($response, true);

        if ($data === null && !empty($response)) {
            $logResponse = ['raw_response' => substr($response, 0, 500)];
            ApiLoggerService::log(self::PROVIDER, $endpoint, $logRequest, $logResponse, $httpCode, $startTime, [
                'module' => self::MODULE,
                'method' => $method,
                'error' => 'Risposta non valida (JSON decode error)',
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Risposta non valida dal server WordPress',
                'http_code' => $httpCode,
            ];
        }

        // Log chiamata API (sempre, anche per successo)
        ApiLoggerService::log(self::PROVIDER, $endpoint, $logRequest, $data ?? [], $httpCode, $startTime, [
            'module' => self::MODULE,
            'method' => $method,
        ]);

        // Errore HTTP
        if ($httpCode >= 400) {
            $errorMsg = $data['message'] ?? $data['error'] ?? "Errore HTTP {$httpCode}";
            return [
                'success' => false,
                'data' => $data,
                'error' => $errorMsg,
                'http_code' => $httpCode,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'error' => null,
            'http_code' => $httpCode,
        ];
    }
}
