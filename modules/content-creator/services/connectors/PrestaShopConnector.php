<?php

namespace Modules\ContentCreator\Services\Connectors;

use Services\ApiLoggerService;

/**
 * PrestaShop Webservice API Connector
 *
 * Autenticazione: HTTP Basic con api_key come username, password vuota
 * Formato: JSON (output_format=JSON) con fallback XML per update (PUT richiede XML)
 * Supporta: Products, Categories, CMS Pages
 */
class PrestaShopConnector implements ConnectorInterface
{
    private string $url;
    private string $apiKey;

    private const TIMEOUT = 30;
    private const PROVIDER = 'prestashop_api';
    private const MODULE = 'content-creator';

    /**
     * @param array $config ['url' => string, 'api_key' => string]
     * @throws \InvalidArgumentException Se mancano parametri obbligatori
     */
    public function __construct(array $config)
    {
        if (empty($config['url'])) {
            throw new \InvalidArgumentException('URL del sito PrestaShop obbligatorio');
        }
        if (empty($config['api_key'])) {
            throw new \InvalidArgumentException('API Key PrestaShop obbligatoria');
        }

        $this->url = rtrim($config['url'], '/');
        $this->apiKey = $config['api_key'];
    }

    /**
     * {@inheritdoc}
     */
    public function test(): array
    {
        $result = $this->makeRequest('GET', '/api/?output_format=JSON');

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Connessione fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
                'details' => []
            ];
        }

        $apiInfo = $result['data']['api'] ?? $result['data'] ?? [];
        $resources = [];
        if (is_array($apiInfo)) {
            $resources = array_keys($apiInfo);
        }

        return [
            'success' => true,
            'message' => 'Connessione a PrestaShop riuscita',
            'details' => [
                'url' => $this->url,
                'available_resources' => $resources,
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

        $fields = $this->getFieldsForType($entityType);
        $queryString = '?display=[' . implode(',', $fields) . ']&limit=' . $limit . '&output_format=JSON';
        $result = $this->makeRequest('GET', $endpoint . $queryString);

        if (!$result['success']) {
            return [
                'success' => false,
                'items' => [],
                'total' => 0,
                'error' => $result['error'] ?? 'Errore nel recupero degli elementi'
            ];
        }

        $items = [];
        $dataKey = $this->getDataKeyForType($entityType);
        $rawItems = $result['data'][$dataKey] ?? [];

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

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
        $result = $this->makeRequest(
            'GET',
            '/api/categories?display=[id,name,link_rewrite,meta_title,meta_description,active,id_parent]&limit=100&output_format=JSON'
        );

        if (!$result['success']) {
            return [
                'success' => false,
                'categories' => [],
                'error' => $result['error'] ?? 'Errore nel recupero delle categorie'
            ];
        }

        $categories = [];
        foreach ($result['data']['categories'] ?? [] as $cat) {
            $categories[] = [
                'id' => (string) ($cat['id'] ?? ''),
                'name' => $this->extractLangValue($cat['name'] ?? ''),
                'slug' => $this->extractLangValue($cat['link_rewrite'] ?? ''),
                'parent_id' => (string) ($cat['id_parent'] ?? '0'),
                'active' => (bool) ($cat['active'] ?? false),
                'meta_title' => $this->extractLangValue($cat['meta_title'] ?? ''),
                'meta_description' => $this->extractLangValue($cat['meta_description'] ?? ''),
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
        // PrestaShop richiede il GET completo della risorsa prima del PUT
        $resourceType = $this->getSingularResourceType($entityType);
        if ($resourceType === null) {
            return [
                'success' => false,
                'message' => "Tipo entita' non supportato per l'aggiornamento: {$entityType}"
            ];
        }

        $pluralType = $this->getPluralResourceType($entityType);
        $getEndpoint = '/api/' . $pluralType . '/' . $entityId . '?output_format=JSON';

        // 1. GET risorsa completa
        $getResult = $this->makeRequest('GET', $getEndpoint);

        if (!$getResult['success']) {
            return [
                'success' => false,
                'message' => 'Errore nel recupero della risorsa: ' . ($getResult['error'] ?? 'Errore sconosciuto')
            ];
        }

        $resource = $getResult['data'][$resourceType] ?? null;
        if ($resource === null) {
            return [
                'success' => false,
                'message' => "Risorsa {$entityType} #{$entityId} non trovata"
            ];
        }

        // 2. Modifica i campi SEO
        if (isset($data['meta_title'])) {
            $resource['meta_title'] = $this->wrapLangValue($data['meta_title'], $resource['meta_title'] ?? '');
        }
        if (isset($data['meta_description'])) {
            $resource['meta_description'] = $this->wrapLangValue($data['meta_description'], $resource['meta_description'] ?? '');
        }
        if (isset($data['page_description'])) {
            $resource['description'] = $this->wrapLangValue($data['page_description'], $resource['description'] ?? '');
        }

        // 3. PUT con risorsa modificata (XML)
        $xml = $this->buildXml($resourceType, $pluralType, $resource);

        $putEndpoint = '/api/' . $pluralType . '/' . $entityId;
        $putResult = $this->makeRequestRaw('PUT', $putEndpoint, $xml, 'application/xml');

        if (!$putResult['success']) {
            $label = $this->getEntityLabel($entityType);
            return [
                'success' => false,
                'message' => "Errore aggiornamento {$label}: " . ($putResult['error'] ?? 'Errore sconosciuto')
            ];
        }

        $label = $this->getEntityLabel($entityType);
        return [
            'success' => true,
            'message' => ucfirst($label) . ' aggiornato con successo'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'prestashop';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Restituisce l'endpoint per il tipo di entita'
     */
    private function getEndpointForType(string $entityType): ?string
    {
        $map = [
            'products' => '/api/products',
            'categories' => '/api/categories',
            'pages' => '/api/cms',
        ];

        return $map[$entityType] ?? null;
    }

    /**
     * Campi da richiedere per tipo
     */
    private function getFieldsForType(string $entityType): array
    {
        $map = [
            'products' => ['id', 'name', 'link_rewrite', 'meta_title', 'meta_description', 'description', 'description_short'],
            'categories' => ['id', 'name', 'link_rewrite', 'meta_title', 'meta_description'],
            'pages' => ['id', 'meta_title', 'meta_description', 'content', 'link_rewrite'],
        ];

        return $map[$entityType] ?? ['id', 'name'];
    }

    /**
     * Chiave dati nella risposta JSON
     */
    private function getDataKeyForType(string $entityType): string
    {
        $map = [
            'products' => 'products',
            'categories' => 'categories',
            'pages' => 'cms',
        ];

        return $map[$entityType] ?? $entityType;
    }

    /**
     * Tipo risorsa singolare per API
     */
    private function getSingularResourceType(string $entityType): ?string
    {
        $map = [
            'product' => 'product',
            'category' => 'category',
            'page' => 'cms',
        ];

        return $map[$entityType] ?? null;
    }

    /**
     * Tipo risorsa plurale per API
     */
    private function getPluralResourceType(string $entityType): string
    {
        $map = [
            'product' => 'products',
            'category' => 'categories',
            'page' => 'cms',
        ];

        return $map[$entityType] ?? $entityType . 's';
    }

    /**
     * Label entita' per messaggi in italiano
     */
    private function getEntityLabel(string $entityType): string
    {
        $map = [
            'product' => 'prodotto',
            'category' => 'categoria',
            'page' => 'pagina CMS',
        ];

        return $map[$entityType] ?? $entityType;
    }

    /**
     * Estrai valore dalla struttura multilingua PrestaShop
     * Può essere stringa semplice o array [['id' => 1, 'value' => 'testo']]
     */
    private function extractLangValue($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Formato [['id' => 1, 'value' => 'testo']]
            if (isset($value[0]['value'])) {
                return (string) $value[0]['value'];
            }
            // Formato ['language' => [['attrs' => ['id' => 1], 'value' => 'testo']]]
            if (isset($value['language'])) {
                $lang = $value['language'];
                if (is_array($lang) && isset($lang[0]['value'])) {
                    return (string) $lang[0]['value'];
                }
                if (is_array($lang) && isset($lang['value'])) {
                    return (string) $lang['value'];
                }
            }
        }

        return '';
    }

    /**
     * Wrappa un valore nella struttura multilingua PrestaShop
     * Mantiene la struttura esistente, aggiornando solo il valore
     */
    private function wrapLangValue(string $newValue, $existingValue): string
    {
        // Per il formato JSON semplice, ritorna la stringa
        return $newValue;
    }

    /**
     * Normalizza un item dalla risposta API
     */
    private function normalizeItem(array $item, string $entityType): array
    {
        $linkRewrite = $this->extractLangValue($item['link_rewrite'] ?? '');

        if ($entityType === 'products') {
            return [
                'id' => (string) ($item['id'] ?? ''),
                'title' => $this->extractLangValue($item['name'] ?? ''),
                'url' => $this->url . '/' . $linkRewrite,
                'type' => 'product',
                'meta_title' => $this->extractLangValue($item['meta_title'] ?? ''),
                'meta_description' => $this->extractLangValue($item['meta_description'] ?? ''),
                'description' => $this->extractLangValue($item['description'] ?? ''),
                'short_description' => $this->extractLangValue($item['description_short'] ?? ''),
            ];
        }

        if ($entityType === 'categories') {
            return [
                'id' => (string) ($item['id'] ?? ''),
                'title' => $this->extractLangValue($item['name'] ?? ''),
                'url' => $this->url . '/' . $linkRewrite,
                'type' => 'category',
                'meta_title' => $this->extractLangValue($item['meta_title'] ?? ''),
                'meta_description' => $this->extractLangValue($item['meta_description'] ?? ''),
            ];
        }

        // CMS Pages
        return [
            'id' => (string) ($item['id'] ?? ''),
            'title' => $this->extractLangValue($item['meta_title'] ?? ''),
            'url' => $this->url . '/content/' . $linkRewrite,
            'type' => 'page',
            'meta_title' => $this->extractLangValue($item['meta_title'] ?? ''),
            'meta_description' => $this->extractLangValue($item['meta_description'] ?? ''),
            'content' => $this->extractLangValue($item['content'] ?? ''),
        ];
    }

    /**
     * Costruisce XML per PUT PrestaShop
     */
    private function buildXml(string $resourceType, string $pluralType, array $resource): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">' . "\n";
        $xml .= '  <' . $resourceType . '>' . "\n";

        foreach ($resource as $key => $value) {
            if ($key === 'associations' || $key === 'position_in_category') {
                continue; // Salta campi complessi non necessari
            }

            if (is_array($value)) {
                // Campo multilingua
                if (isset($value[0]['id'])) {
                    $xml .= '    <' . $key . '>' . "\n";
                    foreach ($value as $lang) {
                        $langId = $lang['id'] ?? 1;
                        $langValue = htmlspecialchars((string) ($lang['value'] ?? ''), ENT_XML1, 'UTF-8');
                        $xml .= '      <language id="' . $langId . '"><![CDATA[' . ($lang['value'] ?? '') . ']]></language>' . "\n";
                    }
                    $xml .= '    </' . $key . '>' . "\n";
                }
                continue;
            }

            $xml .= '    <' . $key . '><![CDATA[' . (string) $value . ']]></' . $key . '>' . "\n";
        }

        $xml .= '  </' . $resourceType . '>' . "\n";
        $xml .= '</prestashop>';

        return $xml;
    }

    /**
     * Esegue una richiesta HTTP JSON verso l'API PrestaShop
     *
     * @param string $method GET|POST|PUT|DELETE
     * @param string $endpoint Endpoint relativo (es. /api/products)
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
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->apiKey . ':',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Payload per logging
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
                'error' => 'Risposta non valida dal server PrestaShop',
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
            if (isset($data['errors'])) {
                $errors = $data['errors'];
                if (is_array($errors) && isset($errors[0]['message'])) {
                    $errorMsg = $errors[0]['message'];
                } elseif (is_string($errors)) {
                    $errorMsg = $errors;
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

    /**
     * Esegue una richiesta HTTP raw (per XML PUT)
     *
     * @param string $method HTTP method
     * @param string $endpoint Endpoint relativo
     * @param string $body Raw body
     * @param string $contentType Content type
     * @return array ['success' => bool, 'error' => string|null, 'http_code' => int]
     */
    private function makeRequestRaw(string $method, string $endpoint, string $body, string $contentType): array
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
            CURLOPT_USERPWD => $this->apiKey . ':',
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $contentType,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Payload per logging (non loggiamo il body XML intero)
        $logRequest = [
            'method' => $method,
            'endpoint' => $endpoint,
            'content_type' => $contentType,
            'body_length' => strlen($body),
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
                'error' => 'Errore di connessione: ' . $curlError,
                'http_code' => 0
            ];
        }

        // Prova a decodificare come JSON per il log
        $logResponse = json_decode($response, true) ?? ['raw_response' => substr($response, 0, 500)];

        // Log chiamata API
        ApiLoggerService::log(self::PROVIDER, $endpoint, $logRequest, $logResponse, $httpCode, $startTime, [
            'module' => self::MODULE,
            'method' => $method,
        ]);

        // Errore HTTP
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => 'Errore HTTP ' . $httpCode,
                'http_code' => $httpCode
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'http_code' => $httpCode
        ];
    }
}
