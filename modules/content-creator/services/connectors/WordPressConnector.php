<?php

namespace Modules\ContentCreator\Services\Connectors;

use Services\ApiLoggerService;

/**
 * WordPress Connector via SEO Toolkit Plugin API
 *
 * Autenticazione: X-SEO-Toolkit-Key header (plugin seo-toolkit-connector)
 * Supporta: Posts, Pages, Products (WooCommerce)
 * Push: contenuto HTML completo (body)
 */
class WordPressConnector implements ConnectorInterface
{
    private string $url;
    private string $apiKey;

    private const TIMEOUT = 30;
    private const PROVIDER = 'wordpress_api';
    private const MODULE = 'content-creator';

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
     * {@inheritdoc}
     */
    public function test(): array
    {
        $result = $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/ping');

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Connessione fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
                'details' => []
            ];
        }

        $data = $result['data'] ?? [];

        return [
            'success' => true,
            'message' => 'Connessione a WordPress riuscita',
            'details' => [
                'site_name' => $data['site_name'] ?? '',
                'wp_version' => $data['wp_version'] ?? '',
                'seo_plugin' => $data['seo_plugin'] ?? 'none',
                'woocommerce' => $data['woocommerce'] ?? false,
                'url' => $this->url
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchItems(string $entityType = 'products', int $limit = 100): array
    {
        // Usa endpoint all-content del plugin per posts e pages
        if ($entityType === 'posts' || $entityType === 'pages') {
            $type = $entityType === 'posts' ? 'post' : 'page';
            $result = $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/all-content?type=' . $type . '&per_page=' . $limit);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'items' => [],
                    'total' => 0,
                    'error' => $result['error'] ?? 'Errore nel recupero degli elementi'
                ];
            }

            $items = [];
            foreach ($result['data'] ?? [] as $item) {
                $items[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? '',
                    'type' => $type,
                    'content' => $item['content'] ?? '',
                    'word_count' => $item['word_count'] ?? 0,
                ];
            }

            return [
                'success' => true,
                'items' => $items,
                'total' => count($items)
            ];
        }

        // Per prodotti WooCommerce, usa endpoint posts con filtro
        if ($entityType === 'products') {
            $result = $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/all-content?type=product&per_page=' . $limit);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'items' => [],
                    'total' => 0,
                    'error' => $result['error'] ?? 'Errore nel recupero dei prodotti'
                ];
            }

            $items = [];
            foreach ($result['data'] ?? [] as $item) {
                $items[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? '',
                    'type' => 'product',
                    'content' => $item['content'] ?? '',
                    'word_count' => $item['word_count'] ?? 0,
                ];
            }

            return [
                'success' => true,
                'items' => $items,
                'total' => count($items)
            ];
        }

        return [
            'success' => false,
            'items' => [],
            'total' => 0,
            'error' => "Tipo entita' non supportato: {$entityType}"
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchCategories(): array
    {
        $result = $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/categories');

        if (!$result['success']) {
            return [
                'success' => false,
                'categories' => [],
                'error' => $result['error'] ?? 'Errore nel recupero delle categorie'
            ];
        }

        $categories = [];
        foreach ($result['data'] ?? [] as $cat) {
            $categories[] = [
                'id' => (string) ($cat['id'] ?? ''),
                'name' => $cat['name'] ?? '',
                'slug' => $cat['slug'] ?? '',
                'parent_id' => (string) ($cat['parent'] ?? '0'),
                'count' => $cat['count'] ?? 0
            ];
        }

        return [
            'success' => true,
            'categories' => $categories
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateItem(string $entityId, string $entityType, array $data): array
    {
        $body = [];

        if (isset($data['h1'])) {
            $body['title'] = $data['h1'];
        }
        if (isset($data['content'])) {
            $body['content'] = $data['content'];
        }

        if (empty($body)) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest('PUT', '/wp-json/seo-toolkit/v1/posts/' . $entityId, $body);

        if (!$result['success']) {
            $labels = [
                'product' => 'prodotto',
                'page' => 'pagina',
                'post' => 'articolo',
                'category' => 'categoria',
            ];
            $label = $labels[$entityType] ?? 'elemento';
            return [
                'success' => false,
                'message' => "Errore aggiornamento {$label}: " . ($result['error'] ?? 'Errore sconosciuto')
            ];
        }

        return [
            'success' => true,
            'message' => 'Contenuto aggiornato con successo'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'wordpress';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Esegue una richiesta HTTP verso l'API WordPress (plugin SEO Toolkit)
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
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-SEO-Toolkit-Key: ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
                'http_code' => 0
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
                'http_code' => $httpCode
            ];
        }

        // Log chiamata API
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
                'http_code' => $httpCode
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'error' => null,
            'http_code' => $httpCode
        ];
    }
}
