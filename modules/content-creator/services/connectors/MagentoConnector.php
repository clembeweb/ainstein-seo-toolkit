<?php

namespace Modules\ContentCreator\Services\Connectors;

use Services\ApiLoggerService;

/**
 * Magento 2 REST API Connector
 *
 * Autenticazione: Bearer token (Integration access token o Admin token)
 * Supporta: Products (per SKU), Categories
 * SEO: custom_attributes (meta_title, meta_description, description)
 */
class MagentoConnector implements ConnectorInterface
{
    private string $url;
    private string $accessToken;

    private const TIMEOUT = 30;
    private const PROVIDER = 'magento_api';
    private const MODULE = 'content-creator';

    /**
     * @param array $config ['url' => string, 'access_token' => string]
     * @throws \InvalidArgumentException Se mancano parametri obbligatori
     */
    public function __construct(array $config)
    {
        if (empty($config['url'])) {
            throw new \InvalidArgumentException('URL del sito Magento obbligatorio');
        }
        if (empty($config['access_token'])) {
            throw new \InvalidArgumentException('Access Token Magento obbligatorio');
        }

        $this->url = rtrim($config['url'], '/');
        $this->accessToken = $config['access_token'];
    }

    /**
     * {@inheritdoc}
     */
    public function test(): array
    {
        $result = $this->makeRequest('GET', '/rest/V1/store/storeConfigs');

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Connessione fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
                'details' => []
            ];
        }

        $configs = $result['data'] ?? [];
        $storeInfo = is_array($configs) && isset($configs[0]) ? $configs[0] : $configs;

        return [
            'success' => true,
            'message' => 'Connessione a Magento riuscita',
            'details' => [
                'base_url' => $storeInfo['base_url'] ?? $this->url,
                'store_name' => $storeInfo['store_name'] ?? '',
                'locale' => $storeInfo['locale'] ?? '',
                'base_currency' => $storeInfo['base_currency_code'] ?? '',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchItems(string $entityType = 'products', int $limit = 100): array
    {
        if ($entityType === 'products') {
            return $this->fetchProducts($limit);
        }

        if ($entityType === 'categories') {
            return $this->fetchCategoriesAsItems($limit);
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
        $result = $this->makeRequest('GET', '/rest/V1/categories');

        if (!$result['success']) {
            return [
                'success' => false,
                'categories' => [],
                'error' => $result['error'] ?? 'Errore nel recupero delle categorie'
            ];
        }

        $categories = [];
        $this->flattenCategories($result['data'] ?? [], $categories);

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

        if ($entityType === 'category') {
            return $this->updateCategory($entityId, $data);
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
        return 'magento';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Recupera prodotti da Magento
     */
    private function fetchProducts(int $limit): array
    {
        $endpoint = '/rest/V1/products?searchCriteria[pageSize]=' . $limit
            . '&searchCriteria[currentPage]=1';

        $result = $this->makeRequest('GET', $endpoint);

        if (!$result['success']) {
            return [
                'success' => false,
                'items' => [],
                'total' => 0,
                'error' => $result['error'] ?? 'Errore nel recupero dei prodotti'
            ];
        }

        $items = [];
        $products = $result['data']['items'] ?? [];
        $total = $result['data']['total_count'] ?? count($products);

        foreach ($products as $product) {
            $items[] = $this->normalizeProduct($product);
        }

        return [
            'success' => true,
            'items' => $items,
            'total' => (int) $total
        ];
    }

    /**
     * Recupera categorie come items (per fetchItems)
     */
    private function fetchCategoriesAsItems(int $limit): array
    {
        $categoriesResult = $this->fetchCategories();

        if (!$categoriesResult['success']) {
            return [
                'success' => false,
                'items' => [],
                'total' => 0,
                'error' => $categoriesResult['error'] ?? 'Errore nel recupero delle categorie'
            ];
        }

        $items = [];
        $categories = array_slice($categoriesResult['categories'], 0, $limit);

        foreach ($categories as $cat) {
            $items[] = [
                'id' => $cat['id'],
                'title' => $cat['name'],
                'url' => '',
                'type' => 'category',
            ];
        }

        return [
            'success' => true,
            'items' => $items,
            'total' => count($items)
        ];
    }

    /**
     * Appiattisce l'albero categorie Magento
     */
    private function flattenCategories(array $category, array &$result): void
    {
        if (isset($category['id'])) {
            $result[] = [
                'id' => (string) $category['id'],
                'name' => $category['name'] ?? '',
                'parent_id' => (string) ($category['parent_id'] ?? '0'),
                'level' => (int) ($category['level'] ?? 0),
                'is_active' => (bool) ($category['is_active'] ?? true),
                'product_count' => (int) ($category['product_count'] ?? 0),
            ];
        }

        if (isset($category['children_data']) && is_array($category['children_data'])) {
            foreach ($category['children_data'] as $child) {
                $this->flattenCategories($child, $result);
            }
        }
    }

    /**
     * Normalizza prodotto Magento
     */
    private function normalizeProduct(array $product): array
    {
        $customAttrs = $this->indexCustomAttributes($product['custom_attributes'] ?? []);

        return [
            'id' => (string) ($product['sku'] ?? $product['id'] ?? ''),
            'title' => $product['name'] ?? '',
            'url' => $customAttrs['url_key'] ?? '',
            'type' => 'product',
            'sku' => $product['sku'] ?? '',
            'meta_title' => $customAttrs['meta_title'] ?? '',
            'meta_description' => $customAttrs['meta_description'] ?? '',
            'description' => $customAttrs['description'] ?? '',
            'short_description' => $customAttrs['short_description'] ?? '',
            'status' => (int) ($product['status'] ?? 1),
        ];
    }

    /**
     * Indicizza custom_attributes per accesso rapido
     */
    private function indexCustomAttributes(array $attributes): array
    {
        $indexed = [];
        foreach ($attributes as $attr) {
            if (isset($attr['attribute_code'], $attr['value'])) {
                $indexed[$attr['attribute_code']] = $attr['value'];
            }
        }
        return $indexed;
    }

    /**
     * Aggiorna prodotto Magento tramite SKU
     */
    private function updateProduct(string $sku, array $data): array
    {
        $customAttributes = [];
        $productBody = ['sku' => $sku];

        if (isset($data['h1'])) {
            $productBody['name'] = $data['h1'];
        }

        if (isset($data['content'])) {
            $customAttributes[] = [
                'attribute_code' => 'description',
                'value' => $data['content']
            ];
        }

        if (!empty($customAttributes)) {
            $productBody['custom_attributes'] = $customAttributes;
        }

        if (count($productBody) <= 1) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $body = ['product' => $productBody];

        $encodedSku = rawurlencode($sku);
        $result = $this->makeRequest('PUT', '/rest/V1/products/' . $encodedSku, $body);

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
     * Aggiorna categoria Magento
     */
    private function updateCategory(string $categoryId, array $data): array
    {
        $body = ['category' => []];

        if (isset($data['h1'])) {
            $body['category']['name'] = $data['h1'];
        }

        if (isset($data['content'])) {
            $body['category']['custom_attributes'][] = [
                'attribute_code' => 'description',
                'value' => $data['content']
            ];
        }

        if (empty($body['category'])) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest('PUT', '/rest/V1/categories/' . $categoryId, $body);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Errore aggiornamento categoria: ' . ($result['error'] ?? 'Errore sconosciuto')
            ];
        }

        return [
            'success' => true,
            'message' => 'Categoria aggiornata con successo'
        ];
    }

    /**
     * Esegue una richiesta HTTP verso l'API Magento 2
     *
     * @param string $method GET|POST|PUT|DELETE
     * @param string $endpoint Endpoint relativo (es. /rest/V1/products)
     * @param array|null $body Body della richiesta
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null, 'http_code' => int]
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
                'Authorization: Bearer ' . $this->accessToken,
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
                'error' => 'Risposta non valida dal server Magento',
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
            $errorMsg = 'Errore HTTP ' . $httpCode;
            if (isset($data['message'])) {
                $errorMsg = $data['message'];
                // Magento usa parametri come %1, %fieldName
                if (isset($data['parameters']) && is_array($data['parameters'])) {
                    foreach ($data['parameters'] as $key => $value) {
                        if (is_string($value)) {
                            $errorMsg = str_replace('%' . $key, $value, $errorMsg);
                            $errorMsg = str_replace('%' . ($key + 1), $value, $errorMsg);
                        }
                    }
                }
            }

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
