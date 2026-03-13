# Chunk 3: ImageCapableConnectorInterface + CMS Connector Extensions

> **Parent plan:** [Plan Index](./2026-03-12-image-generation-plan-index.md)
> **Design spec:** [Design Spec](./2026-03-12-content-creator-image-generation-design.md) — Section 5
> **Depends on:** Chunk 1 (models)

---

## Task 8: ImageCapableConnectorInterface

**Files:**
- Create: `modules/content-creator/services/connectors/ImageCapableConnectorInterface.php`

**Key design:** Extends existing `ConnectorInterface` (which remains unchanged). Only connectors that implement the image interface show the "Push CMS" button for images.

- [ ] **Step 1: Write interface**

```php
<?php

namespace Modules\ContentCreator\Services\Connectors;

/**
 * Estensione dell'interfaccia connettore per supporto immagini.
 *
 * I connettori che implementano questa interfaccia possono:
 * - Recuperare prodotti con le loro immagini dal CMS
 * - Caricare immagini generate sul CMS
 *
 * ConnectorInterface base NON viene modificata — retrocompatibilità totale.
 */
interface ImageCapableConnectorInterface extends ConnectorInterface
{
    /**
     * Recupera prodotti con le loro immagini.
     *
     * @param string $entityType products|categories|pages
     * @param int $limit Items per pagina
     * @param int $page Numero pagina (1-based)
     * @return array{
     *   success: bool,
     *   items: array<array{
     *     id: string,
     *     name: string,
     *     sku: string,
     *     url: string,
     *     image_url: string,
     *     category: string,
     *     price: string
     *   }>,
     *   total: int,
     *   has_more: bool,
     *   error: ?string
     * }
     */
    public function fetchProductImages(string $entityType = 'products', int $limit = 100, int $page = 1): array;

    /**
     * Carica un'immagine su un prodotto nel CMS.
     *
     * @param string $entityId Product/page ID nel CMS
     * @param string $entityType product|category|page
     * @param string $imagePath Path assoluto all'immagine locale
     * @param array $meta ['alt' => string, 'position' => int, 'filename' => string]
     * @return array{success: bool, cms_image_id: ?string, error: ?string}
     */
    public function uploadImage(string $entityId, string $entityType, string $imagePath, array $meta = []): array;

    /**
     * Verifica se il connettore supporta upload immagini.
     * Utile per connettori che possono fetch ma non upload (es. API read-only).
     */
    public function supportsImageUpload(): bool;
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/services/connectors/ImageCapableConnectorInterface.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/services/connectors/ImageCapableConnectorInterface.php
git commit -m "feat(content-creator): add ImageCapableConnectorInterface extending ConnectorInterface"
```

---

## Task 9: WordPress Connector — Image Methods

**Files:**
- Modify: `modules/content-creator/services/connectors/WordPressConnector.php`

**IMPORTANT:** The existing WP connector uses a custom plugin API (`/wp-json/seo-toolkit/v1/`) with `X-SEO-Toolkit-Key` auth header — NOT the WooCommerce REST API. The `makeRequest()` method returns `{success, data, error, http_code}` — NO headers.

**Strategy:**
- `fetchProductImages` → uses the same plugin endpoint (`/seo-toolkit/v1/all-content?type=product`) which returns products. The plugin MUST be extended to include `featured_image` in the response. If it doesn't, we add a field mapping.
- `uploadImage` → uses the plugin endpoint `/seo-toolkit/v1/upload-image` (new endpoint to add in the WP plugin). Fallback: direct WP REST API `/wp-json/wp/v2/media` with the same `X-SEO-Toolkit-Key` auth.

**Note:** Read the existing file first to verify field names and existing patterns.

- [ ] **Step 1: Read the existing file**

Read `modules/content-creator/services/connectors/WordPressConnector.php` completely to understand:
- Properties: `$this->url`, `$this->apiKey`
- `makeRequest()` signature and return format
- How `fetchItems()` already works for products
- Auth pattern (`X-SEO-Toolkit-Key` header)

- [ ] **Step 2: Update class declaration**

Change:
```php
class WordPressConnector implements ConnectorInterface
```
To:
```php
class WordPressConnector implements ConnectorInterface, ImageCapableConnectorInterface
```

- [ ] **Step 3: Add image methods at end of class (before closing brace)**

```php
    // ========== ImageCapableConnectorInterface ==========

    /**
     * Fetch products with their images via SEO Toolkit plugin API.
     *
     * Uses the same /seo-toolkit/v1/all-content endpoint as fetchItems(),
     * but maps response to include image_url field.
     * The plugin response includes 'featured_image' for WooCommerce products.
     */
    public function fetchProductImages(string $entityType = 'products', int $limit = 100, int $page = 1): array
    {
        $startTime = microtime(true);
        $offset = ($page - 1) * $limit;

        // Use the same custom plugin endpoint already used by fetchItems()
        $endpoint = '/wp-json/seo-toolkit/v1/all-content?type=product&per_page=' . $limit . '&offset=' . $offset;
        $response = $this->makeRequest('GET', $endpoint);

        if (!$response['success']) {
            return ['success' => false, 'items' => [], 'total' => 0, 'has_more' => false, 'error' => $response['error'] ?? 'Errore API WordPress'];
        }

        $products = $response['data'] ?? [];
        $items = [];

        foreach ($products as $product) {
            // The plugin response may include 'featured_image' or 'image' field
            $imageUrl = $product['featured_image'] ?? $product['image'] ?? $product['thumbnail'] ?? '';

            // Skip products without images
            if (empty($imageUrl)) continue;

            $items[] = [
                'id' => (string) ($product['id'] ?? ''),
                'name' => $product['title'] ?? '',
                'sku' => $product['sku'] ?? '',
                'url' => $product['url'] ?? '',
                'image_url' => $imageUrl,
                'category' => $product['category'] ?? '',
                'price' => $product['price'] ?? '',
            ];
        }

        $hasMore = count($products) >= $limit;

        ApiLoggerService::log(self::PROVIDER, $endpoint, ['page' => $page, 'limit' => $limit], ['count' => count($items)], 200, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "fetchProductImages page={$page}",
        ]);

        return [
            'success' => true,
            'items' => $items,
            'total' => count($items),
            'has_more' => $hasMore,
            'error' => null,
        ];
    }

    /**
     * Upload image via SEO Toolkit plugin API.
     *
     * Uses /seo-toolkit/v1/upload-image endpoint (requires plugin v1.3+).
     * The plugin handles media library upload + product assignment internally.
     */
    public function uploadImage(string $entityId, string $entityType, string $imagePath, array $meta = []): array
    {
        $startTime = microtime(true);

        if (!file_exists($imagePath)) {
            return ['success' => false, 'cms_image_id' => null, 'error' => 'File non trovato'];
        }

        $filename = $meta['filename'] ?? basename($imagePath);
        $alt = $meta['alt'] ?? '';

        // Use plugin endpoint for image upload
        $endpoint = '/wp-json/seo-toolkit/v1/upload-image';
        $url = $this->url . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'image' => new \CURLFile($imagePath, mime_content_type($imagePath) ?: 'image/jpeg', $filename),
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'alt' => $alt,
                'position' => $meta['position'] ?? '',
            ],
            CURLOPT_HTTPHEADER => [
                'X-SEO-Toolkit-Key: ' . $this->apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60, // Longer timeout for upload
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = json_decode($responseBody, true);

        ApiLoggerService::log(self::PROVIDER, $endpoint, ['entity' => $entityId, 'filename' => $filename], $data ?? [], $httpCode, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "uploadImage entity={$entityId}",
        ]);

        if ($curlError) {
            return ['success' => false, 'cms_image_id' => null, 'error' => "Errore connessione: {$curlError}"];
        }

        if ($httpCode >= 200 && $httpCode < 300 && !empty($data['success'])) {
            return [
                'success' => true,
                'cms_image_id' => (string) ($data['media_id'] ?? ''),
                'error' => null,
            ];
        }

        return ['success' => false, 'cms_image_id' => null, 'error' => $data['message'] ?? "HTTP {$httpCode}"];
    }

    /**
     * WordPress supports image upload (requires seo-toolkit plugin v1.3+)
     */
    public function supportsImageUpload(): bool
    {
        return true;
    }
```

- [ ] **Step 4: Verify syntax**

```bash
php -l modules/content-creator/services/connectors/WordPressConnector.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/content-creator/services/connectors/WordPressConnector.php
git commit -m "feat(content-creator): add image fetch/upload to WordPressConnector (WooCommerce)"
```

---

## Task 10: Shopify Connector — Image Methods

**Files:**
- Modify: `modules/content-creator/services/connectors/ShopifyConnector.php`

**API endpoints:**
- `GET /admin/api/2024-01/products.json` — fetch with images
- `POST /admin/api/2024-01/products/{id}/images.json` — upload image

- [ ] **Step 1: Read existing file and update class declaration**

Add `ImageCapableConnectorInterface` to implements clause.

- [ ] **Step 2: Add image methods**

```php
    // ========== ImageCapableConnectorInterface ==========

    public function fetchProductImages(string $entityType = 'products', int $limit = 100, int $page = 1): array
    {
        $startTime = microtime(true);

        $params = [
            'limit' => min($limit, 250),
            'fields' => 'id,title,handle,variants,images,product_type',
            'status' => 'active',
        ];

        // Shopify uses cursor-based pagination with page_info
        // For simplicity, use since_id approach for page > 1
        if ($page > 1) {
            // Fetch previous page to get last ID (simplified pagination)
            $params['limit'] = min($limit, 250);
        }

        $response = $this->makeRequest('GET', '/products.json', $params);

        if (!$response['success']) {
            return ['success' => false, 'items' => [], 'total' => 0, 'has_more' => false, 'error' => $response['message'] ?? 'Errore API Shopify'];
        }

        $products = $response['data']['products'] ?? [];
        $items = [];

        foreach ($products as $product) {
            $imageUrl = '';
            if (!empty($product['images'])) {
                $imageUrl = $product['images'][0]['src'] ?? '';
            } elseif (!empty($product['image'])) {
                $imageUrl = $product['image']['src'] ?? '';
            }

            if (empty($imageUrl)) continue;

            $sku = '';
            if (!empty($product['variants'])) {
                $sku = $product['variants'][0]['sku'] ?? '';
            }

            $items[] = [
                'id' => (string) ($product['id'] ?? ''),
                'name' => $product['title'] ?? '',
                'sku' => $sku,
                'url' => "https://{$this->shopDomain}/products/{$product['handle']}",
                'image_url' => $imageUrl,
                'category' => $product['product_type'] ?? '',
                'price' => !empty($product['variants']) ? ($product['variants'][0]['price'] ?? '') : '',
            ];
        }

        $hasMore = count($products) >= $limit;

        ApiLoggerService::log(self::PROVIDER, '/products.json', $params, ['count' => count($items)], 200, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "fetchProductImages page={$page}",
        ]);

        return ['success' => true, 'items' => $items, 'total' => count($items), 'has_more' => $hasMore, 'error' => null];
    }

    public function uploadImage(string $entityId, string $entityType, string $imagePath, array $meta = []): array
    {
        $startTime = microtime(true);

        if (!file_exists($imagePath)) {
            return ['success' => false, 'cms_image_id' => null, 'error' => 'File non trovato'];
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $filename = $meta['filename'] ?? basename($imagePath);
        $alt = $meta['alt'] ?? '';
        $position = $meta['position'] ?? null;

        $payload = [
            'image' => [
                'attachment' => $imageData,
                'filename' => $filename,
                'alt' => $alt,
            ],
        ];
        if ($position !== null) {
            $payload['image']['position'] = $position;
        }

        $response = $this->makeRequest('POST', "/products/{$entityId}/images.json", $payload);

        ApiLoggerService::log(self::PROVIDER, "/products/{$entityId}/images.json", ['filename' => $filename], $response['data'] ?? [], $response['http_code'] ?? 0, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "uploadImage product={$entityId}",
        ]);

        if (!$response['success']) {
            return ['success' => false, 'cms_image_id' => null, 'error' => $response['message'] ?? 'Upload fallito'];
        }

        $imageId = (string) ($response['data']['image']['id'] ?? '');
        return ['success' => true, 'cms_image_id' => $imageId, 'error' => null];
    }

    public function supportsImageUpload(): bool
    {
        return true;
    }
```

- [ ] **Step 3: Verify syntax**

```bash
php -l modules/content-creator/services/connectors/ShopifyConnector.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/services/connectors/ShopifyConnector.php
git commit -m "feat(content-creator): add image fetch/upload to ShopifyConnector"
```

---

## Task 11: PrestaShop Connector — Image Methods

**Files:**
- Modify: `modules/content-creator/services/connectors/PrestaShopConnector.php`

**API endpoints:**
- `GET /api/products` — fetch with id_default_image
- `POST /api/images/products/{id}` — multipart upload

- [ ] **Step 1: Read existing file and update class declaration**

- [ ] **Step 2: Add image methods**

```php
    // ========== ImageCapableConnectorInterface ==========

    public function fetchProductImages(string $entityType = 'products', int $limit = 100, int $page = 1): array
    {
        $startTime = microtime(true);
        $offset = ($page - 1) * $limit;

        $params = [
            'display' => '[id,name,reference,link_rewrite,id_default_image,id_category_default,price]',
            'filter[active]' => '[1]',
            'limit' => "{$offset},{$limit}",
            'output_format' => 'JSON',
        ];

        $response = $this->makeRequest('GET', '/api/products', $params);

        if (!$response['success']) {
            return ['success' => false, 'items' => [], 'total' => 0, 'has_more' => false, 'error' => $response['message'] ?? 'Errore API PrestaShop'];
        }

        $products = $response['data']['products'] ?? [];
        $items = [];

        foreach ($products as $product) {
            $productId = $product['id'] ?? '';
            $imageId = $product['id_default_image'] ?? '';

            if (empty($imageId)) continue;

            // PrestaShop image URL pattern
            $imageUrl = rtrim($this->url, '/') . "/api/images/products/{$productId}/{$imageId}";

            $name = is_array($product['name']) ? ($product['name'][0]['value'] ?? '') : ($product['name'] ?? '');
            $linkRewrite = is_array($product['link_rewrite']) ? ($product['link_rewrite'][0]['value'] ?? '') : ($product['link_rewrite'] ?? '');

            $items[] = [
                'id' => (string) $productId,
                'name' => $name,
                'sku' => $product['reference'] ?? '',
                'url' => rtrim($this->url, '/') . "/{$linkRewrite}",
                'image_url' => $imageUrl,
                'category' => (string) ($product['id_category_default'] ?? ''),
                'price' => $product['price'] ?? '',
            ];
        }

        $hasMore = count($products) >= $limit;

        ApiLoggerService::log(self::PROVIDER, '/api/products', $params, ['count' => count($items)], 200, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "fetchProductImages page={$page}",
        ]);

        return ['success' => true, 'items' => $items, 'total' => count($items), 'has_more' => $hasMore, 'error' => null];
    }

    public function uploadImage(string $entityId, string $entityType, string $imagePath, array $meta = []): array
    {
        $startTime = microtime(true);

        if (!file_exists($imagePath)) {
            return ['success' => false, 'cms_image_id' => null, 'error' => 'File non trovato'];
        }

        // PrestaShop uses multipart upload
        $endpoint = "/api/images/products/{$entityId}";
        $url = rtrim($this->url, '/') . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'image' => new \CURLFile($imagePath, mime_content_type($imagePath) ?: 'image/jpeg', $meta['filename'] ?? basename($imagePath)),
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        ApiLoggerService::log(self::PROVIDER, $endpoint, ['entity' => $entityId], ['http_code' => $httpCode], $httpCode, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "uploadImage product={$entityId}",
        ]);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'cms_image_id' => null, 'error' => null];
        }

        return ['success' => false, 'cms_image_id' => null, 'error' => "Upload fallito: HTTP {$httpCode}"];
    }

    public function supportsImageUpload(): bool
    {
        return true;
    }
```

- [ ] **Step 3: Verify syntax + commit**

```bash
php -l modules/content-creator/services/connectors/PrestaShopConnector.php
git add modules/content-creator/services/connectors/PrestaShopConnector.php
git commit -m "feat(content-creator): add image fetch/upload to PrestaShopConnector"
```

---

## Task 12: Magento Connector — Image Methods

**Files:**
- Modify: `modules/content-creator/services/connectors/MagentoConnector.php`

**API endpoints:**
- `GET /V1/products` — fetch with media_gallery
- `POST /V1/products/{sku}/media` — upload (base64 in body)

- [ ] **Step 1: Read existing file and update class declaration**

- [ ] **Step 2: Add image methods**

```php
    // ========== ImageCapableConnectorInterface ==========

    public function fetchProductImages(string $entityType = 'products', int $limit = 100, int $page = 1): array
    {
        $startTime = microtime(true);

        $params = [
            'searchCriteria[pageSize]' => $limit,
            'searchCriteria[currentPage]' => $page,
            'searchCriteria[filter_groups][0][filters][0][field]' => 'status',
            'searchCriteria[filter_groups][0][filters][0][value]' => '1',
            'fields' => 'items[id,sku,name,media_gallery_entries,custom_attributes,price],total_count',
        ];

        $response = $this->makeRequest('GET', '/V1/products', $params);

        if (!$response['success']) {
            return ['success' => false, 'items' => [], 'total' => 0, 'has_more' => false, 'error' => $response['message'] ?? 'Errore API Magento'];
        }

        $products = $response['data']['items'] ?? [];
        $total = (int) ($response['data']['total_count'] ?? 0);
        $items = [];

        foreach ($products as $product) {
            $imageUrl = '';
            if (!empty($product['media_gallery_entries'])) {
                foreach ($product['media_gallery_entries'] as $media) {
                    if (in_array('image', $media['types'] ?? [])) {
                        $imageUrl = rtrim($this->url, '/') . '/pub/media/catalog/product' . $media['file'];
                        break;
                    }
                }
                // Fallback to first image
                if (empty($imageUrl) && !empty($product['media_gallery_entries'][0]['file'])) {
                    $imageUrl = rtrim($this->url, '/') . '/pub/media/catalog/product' . $product['media_gallery_entries'][0]['file'];
                }
            }

            if (empty($imageUrl)) continue;

            // Extract URL key from custom attributes
            $urlKey = '';
            foreach ($product['custom_attributes'] ?? [] as $attr) {
                if ($attr['attribute_code'] === 'url_key') {
                    $urlKey = $attr['value'];
                    break;
                }
            }

            $items[] = [
                'id' => (string) ($product['id'] ?? ''),
                'name' => $product['name'] ?? '',
                'sku' => $product['sku'] ?? '',
                'url' => $urlKey ? rtrim($this->url, '/') . '/' . $urlKey . '.html' : '',
                'image_url' => $imageUrl,
                'category' => '',
                'price' => (string) ($product['price'] ?? ''),
            ];
        }

        $hasMore = ($page * $limit) < $total;

        ApiLoggerService::log(self::PROVIDER, '/V1/products', $params, ['count' => count($items), 'total' => $total], 200, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "fetchProductImages page={$page}",
        ]);

        return ['success' => true, 'items' => $items, 'total' => $total, 'has_more' => $hasMore, 'error' => null];
    }

    public function uploadImage(string $entityId, string $entityType, string $imagePath, array $meta = []): array
    {
        $startTime = microtime(true);

        if (!file_exists($imagePath)) {
            return ['success' => false, 'cms_image_id' => null, 'error' => 'File non trovato'];
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $filename = $meta['filename'] ?? basename($imagePath);

        // Magento uses base64 in JSON body
        $payload = [
            'entry' => [
                'media_type' => 'image',
                'label' => $meta['alt'] ?? '',
                'position' => $meta['position'] ?? 0,
                'disabled' => false,
                'types' => [], // Empty = additional image, ['image','small_image','thumbnail'] = main
                'content' => [
                    'base64_encoded_data' => $imageData,
                    'type' => $mimeType,
                    'name' => $filename,
                ],
            ],
        ];

        // Use SKU for Magento endpoint (entityId could be SKU or numeric ID)
        $response = $this->makeRequest('POST', "/V1/products/{$entityId}/media", $payload);

        ApiLoggerService::log(self::PROVIDER, "/V1/products/{$entityId}/media", ['filename' => $filename], $response['data'] ?? [], $response['http_code'] ?? 0, $startTime, [
            'module' => self::MODULE,
            'cost' => 0,
            'context' => "uploadImage product={$entityId}",
        ]);

        if (!$response['success']) {
            return ['success' => false, 'cms_image_id' => null, 'error' => $response['message'] ?? 'Upload fallito'];
        }

        $mediaId = (string) ($response['data'] ?? '');
        return ['success' => true, 'cms_image_id' => $mediaId, 'error' => null];
    }

    public function supportsImageUpload(): bool
    {
        return true;
    }
```

- [ ] **Step 3: Verify syntax + commit**

```bash
php -l modules/content-creator/services/connectors/MagentoConnector.php
git add modules/content-creator/services/connectors/MagentoConnector.php
git commit -m "feat(content-creator): add image fetch/upload to MagentoConnector"
```

---

## Chunk 3 Complete

**Verify all:**
```bash
php -l modules/content-creator/services/connectors/ImageCapableConnectorInterface.php
php -l modules/content-creator/services/connectors/WordPressConnector.php
php -l modules/content-creator/services/connectors/ShopifyConnector.php
php -l modules/content-creator/services/connectors/PrestaShopConnector.php
php -l modules/content-creator/services/connectors/MagentoConnector.php
```

All must report `No syntax errors detected`.
