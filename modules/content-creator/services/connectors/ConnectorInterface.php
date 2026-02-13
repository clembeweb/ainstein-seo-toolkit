<?php

namespace Modules\ContentCreator\Services\Connectors;

interface ConnectorInterface
{
    /**
     * Test connection to CMS
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function test(): array;

    /**
     * Fetch items (products/pages/categories) from CMS
     * @param string $entityType products|categories|pages
     * @param int $limit
     * @return array ['success' => bool, 'items' => array, 'total' => int]
     */
    public function fetchItems(string $entityType = 'products', int $limit = 100): array;

    /**
     * Fetch categories from CMS
     * @return array ['success' => bool, 'categories' => array]
     */
    public function fetchCategories(): array;

    /**
     * Update item's content in CMS
     * @param string $entityId CMS entity ID (product/page/category ID)
     * @param string $entityType product|category|page
     * @param array $data ['content' => string (HTML body), 'h1' => string (title/heading)]
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateItem(string $entityId, string $entityType, array $data): array;

    /**
     * Get connector type identifier
     * @return string wordpress|shopify|prestashop|magento
     */
    public function getType(): string;
}
