<?php

namespace Modules\ContentCreator\Services\Connectors;

use Services\ApiLoggerService;

/**
 * WordPress REST API + WooCommerce v3 + Yoast SEO Connector
 *
 * Autenticazione: HTTP Basic con username + Application Password
 * Supporta: Products (WooCommerce), Pages, Posts, Categories (WooCommerce)
 * SEO Meta: Yoast SEO (_yoast_wpseo_title, _yoast_wpseo_metadesc)
 */
class WordPressConnector implements ConnectorInterface
{
    private string $url;
    private string $username;
    private string $applicationPassword;

    private const TIMEOUT = 30;
    private const PROVIDER = 'wordpress_api';
    private const MODULE = 'content-creator';

    /**
     * @param array $config ['url' => string, 'username' => string, 'application_password' => string]
     * @throws \InvalidArgumentException Se mancano parametri obbligatori
     */
    public function __construct(array $config)
    {
        if (empty($config['url'])) {
            throw new \InvalidArgumentException('URL del sito WordPress obbligatorio');
        }
        if (empty($config['username'])) {
            throw new \InvalidArgumentException('Username WordPress obbligatorio');
        }
        if (empty($config['application_password'])) {
            throw new \InvalidArgumentException('Application Password WordPress obbligatoria');
        }

        $this->url = rtrim($config['url'], '/');
        $this->username = $config['username'];
        $this->applicationPassword = $config['application_password'];
    }

    /**
     * {@inheritdoc}
     */
    public function test(): array
    {
        $result = $this->makeRequest('GET', '/wp-json/wp/v2/types');

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Connessione fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
                'details' => []
            ];
        }

        // Verifica se WooCommerce e' attivo
        $hasWooCommerce = false;
        $wooCheck = $this->makeRequest('GET', '/wp-json/wc/v3/system_status');
        if ($wooCheck['success']) {
            $hasWooCommerce = true;
        }

        $types = array_keys($result['data'] ?? []);

        return [
            'success' => true,
            'message' => 'Connessione a WordPress riuscita',
            'details' => [
                'post_types' => $types,
                'woocommerce' => $hasWooCommerce,
                'url' => $this->url
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

        $separator = strpos($endpoint, '?') !== false ? '&' : '?';
        $limitParam = $entityType === 'products' || $entityType === 'categories' ? 'per_page' : 'per_page';
        $result = $this->makeRequest('GET', $endpoint . $separator . $limitParam . '=' . $limit);

        if (!$result['success']) {
            return [
                'success' => false,
                'items' => [],
                'total' => 0,
                'error' => $result['error'] ?? 'Errore nel recupero degli elementi'
            ];
        }

        $items = [];
        $rawItems = $result['data'] ?? [];

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
        $result = $this->makeRequest('GET', '/wp-json/wc/v3/products/categories?per_page=100');

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
        if ($entityType === 'product') {
            return $this->updateProduct($entityId, $data);
        }

        if ($entityType === 'page' || $entityType === 'post') {
            return $this->updatePageOrPost($entityId, $entityType, $data);
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
        return 'wordpress';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Aggiorna prodotto WooCommerce con meta Yoast SEO
     */
    private function updateProduct(string $entityId, array $data): array
    {
        $body = [];

        if (isset($data['page_description'])) {
            $body['description'] = $data['page_description'];
        }

        if (isset($data['meta_title'])) {
            $body['name'] = $data['meta_title'];
        }

        // Yoast SEO meta
        $metaData = [];
        if (isset($data['meta_title'])) {
            $metaData[] = ['key' => '_yoast_wpseo_title', 'value' => $data['meta_title']];
        }
        if (isset($data['meta_description'])) {
            $metaData[] = ['key' => '_yoast_wpseo_metadesc', 'value' => $data['meta_description']];
        }
        if (!empty($metaData)) {
            $body['meta_data'] = $metaData;
        }

        if (empty($body)) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest('PUT', '/wp-json/wc/v3/products/' . $entityId, $body);

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
     * Aggiorna pagina o post WordPress con meta Yoast SEO
     */
    private function updatePageOrPost(string $entityId, string $type, array $data): array
    {
        $endpoint = $type === 'page' ? '/wp-json/wp/v2/pages/' : '/wp-json/wp/v2/posts/';
        $body = [];

        if (isset($data['meta_title'])) {
            $body['title'] = $data['meta_title'];
        }
        if (isset($data['meta_description'])) {
            $body['excerpt'] = $data['meta_description'];
        }
        if (isset($data['page_description'])) {
            $body['content'] = $data['page_description'];
        }

        // Yoast SEO meta
        $meta = [];
        if (isset($data['meta_title'])) {
            $meta['_yoast_wpseo_title'] = $data['meta_title'];
        }
        if (isset($data['meta_description'])) {
            $meta['_yoast_wpseo_metadesc'] = $data['meta_description'];
        }
        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        if (empty($body)) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest('POST', $endpoint . $entityId, $body);

        if (!$result['success']) {
            $label = $type === 'page' ? 'pagina' : 'articolo';
            return [
                'success' => false,
                'message' => "Errore aggiornamento {$label}: " . ($result['error'] ?? 'Errore sconosciuto')
            ];
        }

        $label = $type === 'page' ? 'Pagina aggiornata' : 'Articolo aggiornato';
        return [
            'success' => true,
            'message' => $label . ' con successo'
        ];
    }

    /**
     * Aggiorna categoria WooCommerce
     */
    private function updateCategory(string $entityId, array $data): array
    {
        $body = [];

        if (isset($data['meta_title'])) {
            $body['name'] = $data['meta_title'];
        }
        if (isset($data['page_description'])) {
            $body['description'] = $data['page_description'];
        }

        if (empty($body)) {
            return ['success' => false, 'message' => 'Nessun dato da aggiornare'];
        }

        $result = $this->makeRequest('PUT', '/wp-json/wc/v3/products/categories/' . $entityId, $body);

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
     * Restituisce l'endpoint API per il tipo di entita'
     */
    private function getEndpointForType(string $entityType): ?string
    {
        $map = [
            'products' => '/wp-json/wc/v3/products',
            'pages' => '/wp-json/wp/v2/pages',
            'posts' => '/wp-json/wp/v2/posts',
            'categories' => '/wp-json/wc/v3/products/categories',
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
                'title' => $item['name'] ?? '',
                'url' => $item['permalink'] ?? '',
                'type' => 'product',
                'meta_title' => $this->extractYoastMeta($item, '_yoast_wpseo_title'),
                'meta_description' => $this->extractYoastMeta($item, '_yoast_wpseo_metadesc'),
                'description' => $item['description'] ?? '',
                'short_description' => $item['short_description'] ?? '',
            ];
        }

        if ($entityType === 'categories') {
            return [
                'id' => (string) ($item['id'] ?? ''),
                'title' => $item['name'] ?? '',
                'url' => $item['_links']['self'][0]['href'] ?? '',
                'type' => 'category',
                'description' => $item['description'] ?? '',
            ];
        }

        // Pages / Posts
        $type = $entityType === 'pages' ? 'page' : 'post';
        return [
            'id' => (string) ($item['id'] ?? ''),
            'title' => $item['title']['rendered'] ?? '',
            'url' => $item['link'] ?? '',
            'type' => $type,
            'meta_title' => $item['meta']['_yoast_wpseo_title'] ?? '',
            'meta_description' => $item['meta']['_yoast_wpseo_metadesc'] ?? '',
            'content' => $item['content']['rendered'] ?? '',
            'excerpt' => $item['excerpt']['rendered'] ?? '',
        ];
    }

    /**
     * Estrai valore meta Yoast dai meta_data WooCommerce
     */
    private function extractYoastMeta(array $item, string $key): string
    {
        if (!isset($item['meta_data']) || !is_array($item['meta_data'])) {
            return '';
        }

        foreach ($item['meta_data'] as $meta) {
            if (isset($meta['key']) && $meta['key'] === $key) {
                return (string) ($meta['value'] ?? '');
            }
        }

        return '';
    }

    /**
     * Esegue una richiesta HTTP verso l'API WordPress/WooCommerce
     *
     * @param string $method GET|POST|PUT|DELETE
     * @param string $endpoint Endpoint relativo (es. /wp-json/wp/v2/pages)
     * @param array|null $body Body della richiesta (per POST/PUT)
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
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->username . ':' . $this->applicationPassword,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
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
