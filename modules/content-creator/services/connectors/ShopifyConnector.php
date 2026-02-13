<?php

namespace Modules\ContentCreator\Services\Connectors;

use Services\ApiLoggerService;

/**
 * Shopify Admin REST API Connector
 *
 * Autenticazione: X-Shopify-Access-Token header
 * API Version: 2024-01
 * Supporta: Products, Pages, Metafields SEO (title_tag, description_tag)
 */
class ShopifyConnector implements ConnectorInterface
{
    private string $storeUrl;
    private string $accessToken;

    private const TIMEOUT = 30;
    private const API_VERSION = '2024-01';
    private const PROVIDER = 'shopify_api';
    private const MODULE = 'content-creator';

    /**
     * @param array $config ['store_url' => string, 'access_token' => string]
     * @throws \InvalidArgumentException Se mancano parametri obbligatori
     */
    public function __construct(array $config)
    {
        if (empty($config['store_url'])) {
            throw new \InvalidArgumentException('URL dello store Shopify obbligatorio');
        }
        if (empty($config['access_token'])) {
            throw new \InvalidArgumentException('Access Token Shopify obbligatorio');
        }

        // Normalizza URL: rimuovi protocollo e trailing slash
        $storeUrl = $config['store_url'];
        $storeUrl = preg_replace('#^https?://#', '', $storeUrl);
        $storeUrl = rtrim($storeUrl, '/');
        $this->storeUrl = $storeUrl;

        $this->accessToken = $config['access_token'];
    }

    /**
     * {@inheritdoc}
     */
    public function test(): array
    {
        $result = $this->makeRequest('GET', '/admin/api/' . self::API_VERSION . '/shop.json');

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Connessione fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
                'details' => []
            ];
        }

        $shop = $result['data']['shop'] ?? [];

        return [
            'success' => true,
            'message' => 'Connessione a Shopify riuscita',
            'details' => [
                'shop_name' => $shop['name'] ?? '',
                'domain' => $shop['domain'] ?? '',
                'plan' => $shop['plan_display_name'] ?? '',
                'currency' => $shop['currency'] ?? '',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchItems(string $entityType = 'products', int $limit = 100): array
    {
        $endpoint = $this->getEndpointForType($entityType);
        if ($endpoint === null) {
            return [
                'success' => false,
                'items' => [],
                'total' => 0,
                'error' => "Tipo entita' non supportato: {$entityType}"
            ];
        }

        $clampedLimit = min($limit, 250); // Shopify max 250 per pagina
        $result = $this->makeRequest('GET', $endpoint . '?limit=' . $clampedLimit);

        if (!$result['success']) {
            return [
                'success' => false,
                'items' => [],
                'total' => 0,
                'error' => $result['error'] ?? 'Errore nel recupero degli elementi'
            ];
        }

        $items = [];
        $dataKey = $entityType === 'products' ? 'products' : 'pages';
        $rawItems = $result['data'][$dataKey] ?? [];

        foreach ($rawItems as $item) {
            $items[] = $this->normalizeItem($item, $entityType);
        }

        return [
            'success' => true,
            'items' => $items,
            'total' => count($items)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchCategories(): array
    {
        $result = $this->makeRequest('GET', '/admin/api/' . self::API_VERSION . '/custom_collections.json?limit=250');

        if (!$result['success']) {
            return [
                'success' => false,
                'categories' => [],
                'error' => $result['error'] ?? 'Errore nel recupero delle collezioni'
            ];
        }

        $categories = [];
        foreach ($result['data']['custom_collections'] ?? [] as $collection) {
            $categories[] = [
                'id' => (string) ($collection['id'] ?? ''),
                'name' => $collection['title'] ?? '',
                'handle' => $collection['handle'] ?? '',
                'products_count' => $collection['products_count'] ?? 0,
            ];
        }

        // Aggiungi anche smart collections
        $smartResult = $this->makeRequest('GET', '/admin/api/' . self::API_VERSION . '/smart_collections.json?limit=250');
        if ($smartResult['success']) {
            foreach ($smartResult['data']['smart_collections'] ?? [] as $collection) {
                $categories[] = [
                    'id' => (string) ($collection['id'] ?? ''),
                    'name' => $collection['title'] ?? '',
                    'handle' => $collection['handle'] ?? '',
                    'products_count' => $collection['products_count'] ?? 0,
                ];
            }
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
        if ($entityType === 'product') {
            return $this->updateProduct($entityId, $data);
        }

        if ($entityType === 'page') {
            return $this->updatePage($entityId, $data);
        }

        return [
            'success' => false,
            'message' => "Tipo entita' non supportato per l'aggiornamento: {$entityType}"
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'shopify';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Aggiorna prodotto Shopify con contenuto HTML
     */
    private function updateProduct(string $entityId, array $data): array
    {
        $productBody = [];

        if (isset($data['h1'])) {
            $productBody['title'] = $data['h1'];
        }
        if (isset($data['content'])) {
            $productBody['body_html'] = $data['content'];
        }

        if (empty($productBody)) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest(
            'PUT',
            '/admin/api/' . self::API_VERSION . '/products/' . $entityId . '.json',
            ['product' => array_merge(['id' => (int) $entityId], $productBody)]
        );

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Errore aggiornamento prodotto: ' . ($result['error'] ?? 'Errore sconosciuto')
            ];
        }

        return [
            'success' => true,
            'message' => 'Prodotto aggiornato con successo'
        ];
    }

    /**
     * Aggiorna pagina Shopify con contenuto HTML
     */
    private function updatePage(string $entityId, array $data): array
    {
        $pageBody = [];

        if (isset($data['h1'])) {
            $pageBody['title'] = $data['h1'];
        }
        if (isset($data['content'])) {
            $pageBody['body_html'] = $data['content'];
        }

        if (empty($pageBody)) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest(
            'PUT',
            '/admin/api/' . self::API_VERSION . '/pages/' . $entityId . '.json',
            ['page' => array_merge(['id' => (int) $entityId], $pageBody)]
        );

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Errore aggiornamento pagina: ' . ($result['error'] ?? 'Errore sconosciuto')
            ];
        }

        return [
            'success' => true,
            'message' => 'Pagina aggiornata con successo'
        ];
    }

    /**
     * Restituisce l'endpoint API per il tipo di entita'
     */
    private function getEndpointForType(string $entityType): ?string
    {
        $map = [
            'products' => '/admin/api/' . self::API_VERSION . '/products.json',
            'pages' => '/admin/api/' . self::API_VERSION . '/pages.json',
        ];

        return $map[$entityType] ?? null;
    }

    /**
     * Normalizza un item dalla risposta API
     */
    private function normalizeItem(array $item, string $entityType): array
    {
        if ($entityType === 'products') {
            return [
                'id' => (string) ($item['id'] ?? ''),
                'title' => $item['title'] ?? '',
                'url' => isset($item['handle']) ? 'https://' . $this->storeUrl . '/products/' . $item['handle'] : '',
                'type' => 'product',
                'description' => $item['body_html'] ?? '',
                'vendor' => $item['vendor'] ?? '',
                'product_type' => $item['product_type'] ?? '',
            ];
        }

        // Pages
        return [
            'id' => (string) ($item['id'] ?? ''),
            'title' => $item['title'] ?? '',
            'url' => isset($item['handle']) ? 'https://' . $this->storeUrl . '/pages/' . $item['handle'] : '',
            'type' => 'page',
            'description' => $item['body_html'] ?? '',
        ];
    }

    /**
     * Esegue una richiesta HTTP verso l'API Shopify
     *
     * @param string $method GET|POST|PUT|DELETE
     * @param string $endpoint Endpoint relativo (es. /admin/api/2024-01/products.json)
     * @param array|null $body Body della richiesta
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null, 'http_code' => int]
     */
    private function makeRequest(string $method, string $endpoint, ?array $body = null): array
    {
        $url = 'https://' . $this->storeUrl . $endpoint;
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
                'X-Shopify-Access-Token: ' . $this->accessToken,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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

        // Payload per logging (senza credenziali)
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
                'error' => 'Risposta non valida dal server Shopify',
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
            $errorMsg = $data['errors'] ?? $data['error'] ?? "Errore HTTP {$httpCode}";
            if (is_array($errorMsg)) {
                $errorMsg = json_encode($errorMsg);
            }
            return [
                'success' => false,
                'data' => $data,
                'error' => (string) $errorMsg,
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
