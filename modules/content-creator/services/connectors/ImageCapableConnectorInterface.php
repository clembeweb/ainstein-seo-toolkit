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
     */
    public function supportsImageUpload(): bool;
}
