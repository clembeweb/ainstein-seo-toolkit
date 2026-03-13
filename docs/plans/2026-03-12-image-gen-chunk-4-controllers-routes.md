# Chunk 4: Controllers + Routes

> **Parent plan:** [Plan Index](./2026-03-12-image-generation-plan-index.md)
> **Design spec:** [Design Spec](./2026-03-12-content-creator-image-generation-design.md) — Sections 6, 7
> **Depends on:** Chunk 1 (models), Chunk 2 (service), Chunk 3 (connectors)

---

## Task 13: ImageController

**Files:**
- Create: `modules/content-creator/controllers/ImageController.php`

**Responsibility:** CRUD, import (CMS/CSV/manual), approve/reject variants, bulk ops, export ZIP, serve images.

**Reference patterns from:** `UrlController.php` (import), `ProjectController.php` (view rendering), `ExportController.php` (ZIP export)

- [ ] **Step 1: Write ImageController**

```php
<?php

namespace Modules\ContentCreator\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Database;
use Core\ModuleLoader;
use Core\Credits;
use Core\Pagination;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Image;
use Modules\ContentCreator\Models\ImageVariant;
use Modules\ContentCreator\Models\Connector;
use Modules\ContentCreator\Services\ImageGenerationService;
use Modules\ContentCreator\Services\Connectors\ImageCapableConnectorInterface;
use Services\ProjectAccessService;

/**
 * Controller per la gestione immagini nel Content Creator.
 *
 * Gestisce: lista, import (CMS/CSV/upload), preview varianti,
 * approvazione/rifiuto, bulk ops, export ZIP, push CMS, serve immagini.
 */
class ImageController
{
    private Project $project;
    private Image $image;
    private ImageVariant $variant;

    public function __construct()
    {
        $this->project = new Project();
        $this->image = new Image();
        $this->variant = new ImageVariant();
    }

    /**
     * Helper: get project with access check
     */
    private function getProject(int $id, int $userId): ?array
    {
        $project = $this->project->findAccessible($id, $userId);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return null;
        }
        return $project;
    }

    /**
     * Lista immagini con paginazione e filtri
     * GET /content-creator/projects/{id}/images
     */
    public function index(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) return View::render('errors/404', ['user' => $user, 'title' => 'Progetto non trovato', 'modules' => ModuleLoader::getUserModules($user['id'])]);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $filters = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null,
            'category' => $_GET['category'] ?? null,
            'sort' => $_GET['sort'] ?? 'created_at',
            'dir' => $_GET['dir'] ?? 'DESC',
        ];

        $result = $this->image->getPaginated($id, $page, $perPage, $filters);
        $stats = $this->image->getStats($id);
        $pagination = Pagination::make($result['total'], $page, $perPage);

        // Check connector capabilities
        $connector = null;
        $connectorSupportsImages = false;
        if (!empty($project['connector_id'])) {
            $connectorModel = new Connector();
            $connData = $connectorModel->find($project['connector_id']);
            if ($connData) {
                $connectorSupportsImages = $this->connectorSupportsImages($connData);
            }
        }

        $approvedVariantCount = $this->variant->countApprovedByProject($id);

        return View::render('content-creator/images/index', [
            'user' => $user,
            'project' => $project,
            'images' => $result['data'],
            'pagination' => $pagination,
            'stats' => $stats,
            'filters' => $filters,
            'connectorSupportsImages' => $connectorSupportsImages,
            'approvedVariantCount' => $approvedVariantCount,
            'currentPage' => 'images',
            'imageMode' => true,
            'title' => 'Immagini - ' . $project['name'],
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Pagina import immagini
     * GET /content-creator/projects/{id}/images/import
     */
    public function import(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) return View::render('errors/404', ['user' => $user, 'title' => 'Progetto non trovato', 'modules' => ModuleLoader::getUserModules($user['id'])]);

        // Check viewer permission
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            header('Location: ' . url("/content-creator/projects/{$id}/images"));
            exit;
        }

        // Load connector if available and image-capable
        $connector = null;
        $connectorSupportsImages = false;
        if (!empty($project['connector_id'])) {
            $connectorModel = new Connector();
            $connData = $connectorModel->find($project['connector_id']);
            if ($connData) {
                $connectorSupportsImages = $this->connectorSupportsImages($connData);
                if ($connectorSupportsImages) {
                    $connector = $connData;
                }
            }
        }

        $defaults = ImageGenerationService::getProjectDefaults($project);

        return View::render('content-creator/images/import', [
            'user' => $user,
            'project' => $project,
            'connector' => $connector,
            'connectorSupportsImages' => $connectorSupportsImages,
            'defaults' => $defaults,
            'currentPage' => 'images-import',
            'imageMode' => true,
            'title' => 'Importa Prodotti - ' . $project['name'],
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Esegui import
     * POST /content-creator/projects/{id}/images/import
     */
    public function importStore(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi per importare']);
            return;
        }

        $source = $_POST['source'] ?? '';

        ob_start();
        header('Content-Type: application/json');
        ignore_user_abort(true);
        set_time_limit(300);
        session_write_close();

        $service = new ImageGenerationService();
        $inserted = 0;
        $errors = [];

        try {
            switch ($source) {
                case 'cms':
                    $result = $this->importFromCms($id, $user['id'], $project, $service);
                    $inserted = $result['inserted'];
                    $errors = $result['errors'];
                    break;

                case 'csv':
                    $result = $this->importFromCsv($id, $user['id'], $service);
                    $inserted = $result['inserted'];
                    $errors = $result['errors'];
                    break;

                case 'manual':
                    $result = $this->importManual($id, $user['id'], $service);
                    $inserted = $result['inserted'];
                    $errors = $result['errors'];
                    break;

                default:
                    ob_end_clean();
                    echo json_encode(['error' => true, 'message' => 'Sorgente import non valida']);
                    exit;
            }
        } catch (\Exception $e) {
            ob_end_clean();
            echo json_encode(['error' => true, 'message' => 'Errore import: ' . $e->getMessage()]);
            exit;
        }

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'inserted' => $inserted,
            'errors' => $errors,
            'message' => "{$inserted} prodotti importati" . (!empty($errors) ? " ({$this->countVal($errors)} errori)" : ''),
        ]);
        exit;
    }

    /**
     * Import from CMS connector
     */
    private function importFromCms(int $projectId, int $userId, array $project, ImageGenerationService $service): array
    {
        $selectedIds = $_POST['selected_ids'] ?? [];
        $categories = $_POST['categories'] ?? [];

        if (empty($selectedIds) || !is_array($selectedIds)) {
            return ['inserted' => 0, 'errors' => ['Nessun prodotto selezionato']];
        }

        $connectorModel = new Connector();
        $connData = $connectorModel->find($project['connector_id']);
        if (!$connData) {
            return ['inserted' => 0, 'errors' => ['Connettore non trovato']];
        }

        $connector = $connectorModel->createInstance($connData);
        if (!($connector instanceof ImageCapableConnectorInterface)) {
            return ['inserted' => 0, 'errors' => ['Connettore non supporta immagini']];
        }

        // Fetch products with images
        $fetchResult = $connector->fetchProductImages('products', 250);
        if (!$fetchResult['success']) {
            return ['inserted' => 0, 'errors' => [$fetchResult['error'] ?? 'Errore fetch prodotti']];
        }

        // Filter to selected IDs
        $productsById = [];
        foreach ($fetchResult['items'] as $item) {
            $productsById[$item['id']] = $item;
        }

        $inserted = 0;
        $errors = [];
        $defaultCategory = $_POST['default_category'] ?? 'fashion';

        foreach ($selectedIds as $cmsId) {
            if (!isset($productsById[$cmsId])) continue;
            $product = $productsById[$cmsId];
            $category = $categories[$cmsId] ?? $defaultCategory;

            // Create image record
            $imageId = $this->image->create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'product_url' => $product['url'],
                'sku' => $product['sku'],
                'product_name' => $product['name'],
                'category' => $category,
                'source_image_url' => $product['image_url'],
                'source_type' => Image::SOURCE_CMS,
                'connector_id' => $project['connector_id'],
                'cms_entity_id' => $cmsId,
                'cms_entity_type' => 'product',
                'status' => Image::STATUS_PENDING,
            ]);

            // Download source image
            $downloadResult = $service->downloadSourceImage($product['image_url'], $imageId);
            if ($downloadResult['success']) {
                $this->image->update($imageId, [
                    'source_image_path' => $downloadResult['path'],
                    'status' => Image::STATUS_SOURCE_ACQUIRED,
                ]);
            } else {
                $this->image->markError($imageId, $downloadResult['error']);
                $errors[] = "{$product['name']}: {$downloadResult['error']}";
            }

            Database::reconnect();
            $inserted++;
        }

        return ['inserted' => $inserted, 'errors' => $errors];
    }

    /**
     * Import from CSV file
     */
    private function importFromCsv(int $projectId, int $userId, ImageGenerationService $service): array
    {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            return ['inserted' => 0, 'errors' => ['Nessun file CSV caricato']];
        }

        $colImageUrl = (int) ($_POST['col_image_url'] ?? 0);
        $colName = (int) ($_POST['col_name'] ?? 1);
        $colSku = (int) ($_POST['col_sku'] ?? -1);
        $colCategory = (int) ($_POST['col_category'] ?? -1);
        $defaultCategory = $_POST['default_category'] ?? 'fashion';
        $hasHeader = isset($_POST['has_header']);

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) return ['inserted' => 0, 'errors' => ['Impossibile leggere il file CSV']];

        if ($hasHeader) fgetcsv($handle); // Skip header

        $inserted = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $imageUrl = trim($row[$colImageUrl] ?? '');
            $name = trim($row[$colName] ?? '');

            if (empty($imageUrl) || empty($name)) continue;

            $sku = $colSku >= 0 ? trim($row[$colSku] ?? '') : '';
            $category = $colCategory >= 0 ? trim($row[$colCategory] ?? '') : $defaultCategory;

            // Validate category
            if (!in_array($category, ['fashion', 'home', 'custom'])) {
                $category = $defaultCategory;
            }

            $imageId = $this->image->create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'product_name' => $name,
                'sku' => $sku,
                'category' => $category,
                'source_image_url' => $imageUrl,
                'source_type' => Image::SOURCE_URL,
                'status' => Image::STATUS_PENDING,
            ]);

            // Download source image
            $downloadResult = $service->downloadSourceImage($imageUrl, $imageId);
            if ($downloadResult['success']) {
                $this->image->update($imageId, [
                    'source_image_path' => $downloadResult['path'],
                    'status' => Image::STATUS_SOURCE_ACQUIRED,
                ]);
            } else {
                $this->image->markError($imageId, $downloadResult['error']);
                $errors[] = "{$name}: {$downloadResult['error']}";
            }

            Database::reconnect();
            $inserted++;
        }

        fclose($handle);
        return ['inserted' => $inserted, 'errors' => $errors];
    }

    /**
     * Import manual upload
     */
    private function importManual(int $projectId, int $userId, ImageGenerationService $service): array
    {
        if (empty($_FILES['image_file']['tmp_name'])) {
            return ['inserted' => 0, 'errors' => ['Nessun file caricato']];
        }

        $name = trim($_POST['product_name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $category = $_POST['category'] ?? 'fashion';

        if (empty($name)) {
            return ['inserted' => 0, 'errors' => ['Nome prodotto obbligatorio']];
        }

        // Validate file
        $tmpPath = $_FILES['image_file']['tmp_name'];
        $fileSize = $_FILES['image_file']['size'];
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

        if ($fileSize > 10 * 1024 * 1024) {
            return ['inserted' => 0, 'errors' => ['File troppo grande (max 10MB)']];
        }
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            return ['inserted' => 0, 'errors' => ['Formato non supportato. Usa PNG, JPG o WebP']];
        }

        $imageId = $this->image->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'product_name' => $name,
            'sku' => $sku,
            'category' => $category,
            'source_type' => Image::SOURCE_UPLOAD,
            'status' => Image::STATUS_PENDING,
        ]);

        // Save uploaded file
        $imageData = file_get_contents($tmpPath);
        $savedPath = $service->saveSourceImage($imageData, $imageId, $ext);

        if ($savedPath) {
            $this->image->update($imageId, [
                'source_image_path' => $savedPath,
                'status' => Image::STATUS_SOURCE_ACQUIRED,
            ]);
            return ['inserted' => 1, 'errors' => []];
        }

        $this->image->markError($imageId, 'Errore salvataggio file');
        return ['inserted' => 0, 'errors' => ['Errore salvataggio file su disco']];
    }

    /**
     * Preview varianti per un prodotto
     * GET /content-creator/projects/{id}/images/{imgId}
     */
    public function preview(int $id, int $imgId): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) return View::render('errors/404', ['user' => $user, 'title' => 'Progetto non trovato', 'modules' => ModuleLoader::getUserModules($user['id'])]);

        $image = $this->image->findByProject($imgId, $id);
        if (!$image) return View::render('errors/404', ['user' => $user, 'title' => 'Immagine non trovata', 'modules' => ModuleLoader::getUserModules($user['id'])]);

        $variants = $this->variant->getByImage($imgId);
        $defaults = ImageGenerationService::getProjectDefaults($project);

        // Per-item override (merged)
        $itemSettings = $defaults;
        if (!empty($image['generation_settings'])) {
            $override = json_decode($image['generation_settings'], true);
            if (is_array($override)) {
                $itemSettings = array_merge($itemSettings, $override);
            }
        }

        return View::render('content-creator/images/preview', [
            'user' => $user,
            'project' => $project,
            'image' => $image,
            'variants' => $variants,
            'settings' => $itemSettings,
            'defaults' => $defaults,
            'categoryOptions' => ImageGenerationService::CATEGORY_OPTIONS,
            'genderOptions' => ImageGenerationService::GENDER_OPTIONS,
            'backgroundOptions' => ImageGenerationService::BACKGROUND_OPTIONS,
            'environmentOptions' => ImageGenerationService::ENVIRONMENT_OPTIONS,
            'photoStyleOptions' => ImageGenerationService::PHOTO_STYLE_OPTIONS,
            'currentPage' => 'images',
            'imageMode' => true,
            'title' => $image['product_name'] . ' - Varianti',
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Approva una variante
     * POST /content-creator/projects/{id}/images/{imgId}/approve
     */
    public function approveVariant(int $id, int $imgId): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        $image = $this->image->findByProject($imgId, $id);
        if (!$image) {
            echo json_encode(['error' => true, 'message' => 'Immagine non trovata']);
            return;
        }

        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $variant = $this->variant->findByImage($variantId, $imgId);
        if (!$variant) {
            echo json_encode(['error' => true, 'message' => 'Variante non trovata']);
            return;
        }

        $this->variant->approve($variantId);

        // Auto-set image status to approved if it was 'generated'
        if ($image['status'] === Image::STATUS_GENERATED) {
            $this->image->approve($imgId);
        }

        echo json_encode(['success' => true, 'message' => 'Variante approvata']);
    }

    /**
     * Rifiuta una variante
     * POST /content-creator/projects/{id}/images/{imgId}/reject
     */
    public function rejectVariant(int $id, int $imgId): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        $image = $this->image->findByProject($imgId, $id);
        if (!$image) {
            echo json_encode(['error' => true, 'message' => 'Immagine non trovata']);
            return;
        }

        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $variant = $this->variant->findByImage($variantId, $imgId);
        if (!$variant) {
            echo json_encode(['error' => true, 'message' => 'Variante non trovata']);
            return;
        }

        $this->variant->reject($variantId);

        // If no more approved variants, revert image to 'generated'
        if ($image['status'] === Image::STATUS_APPROVED) {
            $approvedCount = $this->variant->countApproved($imgId);
            if ($approvedCount === 0) {
                $this->image->unapprove($imgId);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Variante rifiutata']);
    }

    /**
     * Rigenera varianti
     * POST /content-creator/projects/{id}/images/{imgId}/regenerate
     */
    public function regenerate(int $id, int $imgId): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi']);
            return;
        }

        $image = $this->image->findByProject($imgId, $id);
        if (!$image) {
            echo json_encode(['error' => true, 'message' => 'Immagine non trovata']);
            return;
        }

        $variantId = !empty($_POST['variant_id']) ? (int) $_POST['variant_id'] : null;

        if ($variantId) {
            // Regenerate single variant
            $variant = $this->variant->findByImage($variantId, $imgId);
            if (!$variant) {
                echo json_encode(['error' => true, 'message' => 'Variante non trovata']);
                return;
            }

            // Delete old file
            $service = new ImageGenerationService();
            $service->deleteFile($variant['image_path']);
            $this->variant->delete($variantId);
        } else {
            // Regenerate all: delete all existing variants
            $existingVariants = $this->variant->getByImage($imgId);
            $service = new ImageGenerationService();
            foreach ($existingVariants as $v) {
                $service->deleteFile($v['image_path']);
            }
            $this->variant->deleteByImage($imgId);
        }

        // Reset image status to source_acquired for re-generation
        $this->image->updateStatus($imgId, Image::STATUS_SOURCE_ACQUIRED);

        echo json_encode([
            'success' => true,
            'message' => $variantId ? 'Variante eliminata, pronta per rigenerazione' : 'Tutte le varianti eliminate, pronte per rigenerazione',
        ]);
    }

    /**
     * Bulk approve items
     * POST /content-creator/projects/{id}/images/approve-bulk
     */
    public function approveBulk(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        $imageIds = $_POST['image_ids'] ?? [];
        if (!is_array($imageIds)) $imageIds = [];
        $imageIds = array_map('intval', $imageIds);

        $count = $this->image->approveBulk($imageIds, $id);

        echo json_encode(['success' => true, 'message' => "{$count} immagini approvate", 'count' => $count]);
    }

    /**
     * Bulk delete items
     * POST /content-creator/projects/{id}/images/delete-bulk
     */
    public function deleteBulk(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi']);
            return;
        }

        $imageIds = $_POST['image_ids'] ?? [];
        if (!is_array($imageIds)) $imageIds = [];
        $imageIds = array_map('intval', $imageIds);

        // Delete files first
        $service = new ImageGenerationService();
        foreach ($imageIds as $imgId) {
            $image = $this->image->findByProject($imgId, $id);
            if ($image) {
                // Delete source
                if (!empty($image['source_image_path'])) {
                    $service->deleteFile($image['source_image_path']);
                }
                // Delete variants files
                $variants = $this->variant->getByImage($imgId);
                foreach ($variants as $v) {
                    $service->deleteFile($v['image_path']);
                }
            }
        }

        $count = $this->image->deleteBulk($imageIds, $id);

        echo json_encode(['success' => true, 'message' => "{$count} immagini eliminate", 'count' => $count]);
    }

    /**
     * Export ZIP di varianti approvate
     * GET /content-creator/projects/{id}/images/export/zip
     */
    public function exportZip(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            echo 'Progetto non trovato';
            return;
        }

        $variants = $this->variant->getApprovedByProject($id);
        if (empty($variants)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Nessuna variante approvata da esportare';
            return;
        }

        $service = new ImageGenerationService();
        $outputFormat = ModuleLoader::getSetting('content-creator', 'image_output_format', 'webp');
        $outputQuality = (int) ModuleLoader::getSetting('content-creator', 'image_output_quality', 85);

        // Create ZIP
        $zipPath = sys_get_temp_dir() . '/ainstein_export_' . $id . '_' . time() . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Errore creazione ZIP';
            return;
        }

        // CSV manifest
        $manifest = "SKU,Nome Prodotto,URL Prodotto,File,Dimensione\n";

        foreach ($variants as $v) {
            $sku = $v['sku'] ?: 'no-sku';
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $v['product_name'])));
            $folder = "{$sku}_{$slug}";

            // Convert format if needed
            $sourcePath = $v['image_path'];
            if ($outputFormat !== 'png') {
                $converted = $service->convertFormat($sourcePath, $outputFormat, $outputQuality);
                if ($converted) $sourcePath = $converted;
            }

            $fullPath = $service->getAbsolutePath($sourcePath);
            if (!file_exists($fullPath)) continue;

            $filename = "variante-{$v['variant_number']}.{$outputFormat}";
            $zip->addFile($fullPath, "{$folder}/{$filename}");

            $fileSize = filesize($fullPath);
            $manifest .= "\"{$sku}\",\"{$v['product_name']}\",\"{$v['product_url']}\",\"{$folder}/{$filename}\",{$fileSize}\n";
        }

        $zip->addFromString('manifest.csv', $manifest);
        $zip->close();

        // Send ZIP
        $projectSlug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $project['name'])));
        $downloadName = "immagini-{$projectSlug}-" . date('Y-m-d') . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exit;
    }

    /**
     * Serve immagine (source o generated) con access control
     * GET /content-creator/images/serve/{type}/{filename}
     */
    public function serve(string $type, string $filename): void
    {
        Middleware::auth();
        $user = Auth::user();

        // Validate type
        if (!in_array($type, ['source', 'generated'])) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }

        // Sanitize filename (prevent path traversal)
        $filename = basename($filename);

        // Access control: extract image_id from filename and verify project access
        if ($type === 'source') {
            $image = $this->image->findByFilename($filename);
        } else {
            $variant = $this->variant->findByFilename($filename);
            $image = $variant ? $this->image->find((int) $variant['image_id']) : null;
        }

        if (!$image) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Verify user has access to this project
        $project = $this->project->findAccessible((int) $image['project_id'], $user['id']);
        if (!$project) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        // Determine file path
        $storagePath = dirname(__DIR__, 2) . '/storage/images';
        if ($type === 'source') {
            $filePath = $image['source_image_path'];
        } else {
            $filePath = $variant['image_path'];
        }

        // Source paths can be absolute or relative
        if (!str_starts_with($filePath, '/') && !str_starts_with($filePath, 'C:')) {
            $fullPath = $storagePath . '/' . $filePath;
        } else {
            $fullPath = $filePath;
        }

        if (!file_exists($fullPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, max-age=3600');
        readfile($fullPath);
        exit;
    }

    /**
     * Fetch CMS products (AJAX for import page)
     * POST /content-creator/projects/{id}/images/fetch-cms
     */
    public function fetchCmsProducts(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (empty($project['connector_id'])) {
            echo json_encode(['error' => true, 'message' => 'Nessun connettore CMS configurato']);
            return;
        }

        $connectorModel = new Connector();
        $connData = $connectorModel->find($project['connector_id']);
        if (!$connData) {
            echo json_encode(['error' => true, 'message' => 'Connettore non trovato']);
            return;
        }

        $connector = $connectorModel->createInstance($connData);
        if (!($connector instanceof ImageCapableConnectorInterface)) {
            echo json_encode(['error' => true, 'message' => 'Il connettore non supporta il fetch immagini']);
            return;
        }

        $page = (int) ($_POST['page'] ?? 1);
        $limit = (int) ($_POST['limit'] ?? 100);

        $result = $connector->fetchProductImages('products', $limit, $page);

        echo json_encode($result);
    }

    /**
     * Check if connector supports images
     */
    private function connectorSupportsImages(array $connData): bool
    {
        try {
            $connectorModel = new Connector();
            $instance = $connectorModel->createInstance($connData);
            return $instance instanceof ImageCapableConnectorInterface;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Count array values helper
     */
    private function countVal(array $arr): int
    {
        return count($arr);
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/controllers/ImageController.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/controllers/ImageController.php
git commit -m "feat(content-creator): add ImageController with import, approve/reject, bulk, export ZIP, serve"
```

---

## Task 14: ImageGeneratorController (SSE)

**Files:**
- Create: `modules/content-creator/controllers/ImageGeneratorController.php`

**Responsibility:** SSE for batch image generation and CMS push. Follows exact same patterns as `GeneratorController.php`.

- [ ] **Step 1: Write ImageGeneratorController**

```php
<?php

namespace Modules\ContentCreator\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\Database;
use Core\Credits;
use Core\ModuleLoader;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Image;
use Modules\ContentCreator\Models\ImageVariant;
use Modules\ContentCreator\Models\Job;
use Modules\ContentCreator\Models\Connector;
use Modules\ContentCreator\Services\ImageGenerationService;
use Modules\ContentCreator\Services\Connectors\ImageCapableConnectorInterface;
use Services\ProjectAccessService;

/**
 * SSE Controller per generazione immagini e push CMS.
 *
 * Pattern identico a GeneratorController.php:
 * startJob → stream (SSE) → jobStatus (polling fallback) → cancel
 */
class ImageGeneratorController
{
    private Project $project;
    private Image $image;
    private ImageVariant $variant;

    private const GENERATE_CREDIT_COST = 2; // Per variant
    private const API_DELAY_MS = 2000000;   // 2 seconds between API calls (rate limit protection)

    public function __construct()
    {
        $this->project = new Project();
        $this->image = new Image();
        $this->variant = new ImageVariant();
    }

    /**
     * Send SSE event
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    /**
     * Get project with access check (JSON error response)
     */
    private function getProject(int $id, int $userId): ?array
    {
        $project = $this->project->findAccessible($id, $userId);
        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            return null;
        }
        return $project;
    }

    // ===== IMAGE GENERATION SSE =====

    /**
     * Start generate job
     * POST /content-creator/projects/{id}/images/start-generate-job
     */
    public function startGenerateJob(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();
        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi per generare']);
            return;
        }

        $jobModel = new Job();
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_IMAGE_GENERATE);
        if ($activeJob) {
            echo json_encode(['error' => true, 'message' => 'Generazione già in corso', 'job_id' => (int) $activeJob['id']]);
            return;
        }

        $readyCount = $this->image->countReadyForGeneration($id);
        if ($readyCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessun prodotto pronto per la generazione']);
            return;
        }

        // Estimate credits: items × variants_count × cost_per_variant
        $defaults = ImageGenerationService::getProjectDefaults($project);
        $variantsCount = (int) ($defaults['variants_count'] ?? 3);
        $estimatedCredits = $readyCount * $variantsCount * self::GENERATE_CREDIT_COST;

        $creditUserId = ProjectAccessService::getCreditUserId($project, $user['id']);
        if (!Credits::hasEnough($creditUserId, $estimatedCredits)) {
            $balance = Credits::getBalance($creditUserId);
            echo json_encode(['error' => true, 'message' => "Crediti insufficienti. Richiesti: {$estimatedCredits}, disponibili: {$balance}"]);
            return;
        }

        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_IMAGE_GENERATE,
            'items_requested' => $readyCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $readyCount,
            'estimated_credits' => $estimatedCredits,
        ]);
    }

    /**
     * SSE stream for image generation
     * GET /content-creator/projects/{id}/images/generate-stream
     */
    public function generateStream(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['type'] !== Job::TYPE_IMAGE_GENERATE) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project) return;

        // SSE setup (critical pattern — Golden Rules)
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $jobModel->start($jobId);

        $defaults = ImageGenerationService::getProjectDefaults($project);
        $variantsCount = (int) ($defaults['variants_count'] ?? 3);
        $creditUserId = ProjectAccessService::getCreditUserId($project, $user['id']);

        $service = new ImageGenerationService();
        $completed = 0;
        $failed = 0;
        $creditsUsed = 0;
        $total = (int) $job['items_requested'];

        $this->sendEvent('started', ['job_id' => $jobId, 'total' => $total]);

        while (true) {
            Database::reconnect();

            // Check cancellation
            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            // Get next item
            $items = $this->image->getNextForGeneration($id, 1);
            if (empty($items)) {
                // All done
                $jobModel->complete($jobId);
                $this->sendEvent('completed', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'failed' => $failed,
                    'credits_used' => $creditsUsed,
                ]);
                break;
            }

            $item = $items[0];
            $itemName = $item['product_name'];

            $jobModel->updateProgress($jobId, [
                'current_item' => $itemName,
                'current_item_id' => $item['id'],
            ]);

            $this->sendEvent('progress', [
                'current_item' => $itemName,
                'completed' => $completed,
                'failed' => $failed,
                'total' => $total,
                'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
            ]);

            $itemSuccess = false;
            $variantsGenerated = 0;

            // Generate variants
            for ($v = 1; $v <= $variantsCount; $v++) {
                // Check credits before each variant
                if (!Credits::hasEnough($creditUserId, self::GENERATE_CREDIT_COST)) {
                    $this->sendEvent('item_error', [
                        'image_id' => $item['id'],
                        'product_name' => $itemName,
                        'error' => 'Crediti insufficienti',
                        'completed' => $completed,
                        'failed' => $failed,
                        'total' => $total,
                    ]);
                    $failed++;
                    $jobModel->incrementFailed($jobId);
                    break 2; // Exit both loops — no more credits
                }

                set_time_limit(300); // Reset per variant

                $result = $service->generateVariant($item, $defaults, $v);
                Database::reconnect();

                if ($result['success']) {
                    $this->variant->create($result['variant_data']);
                    Credits::consume($creditUserId, self::GENERATE_CREDIT_COST, 'image_generate', 'content-creator');
                    $creditsUsed += self::GENERATE_CREDIT_COST;
                    $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);
                    $variantsGenerated++;
                } else {
                    $error = $result['error'] ?? 'Errore sconosciuto';

                    // Content policy → skip (no retry)
                    if ($service->getProvider()->isContentPolicyError($error)) {
                        $this->image->markError((int) $item['id'], $error);
                        break; // Skip remaining variants for this item
                    }

                    // Transient error → retry once with backoff
                    if ($service->getProvider()->isTransientError($error)) {
                        usleep(2000000); // 2s backoff
                        Database::reconnect();
                        $retryResult = $service->generateVariant($item, $defaults, $v);
                        Database::reconnect();

                        if ($retryResult['success']) {
                            $this->variant->create($retryResult['variant_data']);
                            Credits::consume($creditUserId, self::GENERATE_CREDIT_COST, 'image_generate', 'content-creator');
                            $creditsUsed += self::GENERATE_CREDIT_COST;
                            $jobModel->addCreditsUsed($jobId, self::GENERATE_CREDIT_COST);
                            $variantsGenerated++;
                            continue;
                        }
                    }

                    // Failed variant — continue with remaining variants
                    // Error logged but item not marked as error yet
                }

                // Rate limit delay between API calls
                usleep(self::API_DELAY_MS);
            }

            Database::reconnect();

            if ($variantsGenerated > 0) {
                // At least 1 variant generated — mark as generated
                $this->image->updateStatus((int) $item['id'], Image::STATUS_GENERATED);
                $completed++;
                $jobModel->incrementCompleted($jobId);

                $this->sendEvent('item_completed', [
                    'image_id' => $item['id'],
                    'product_name' => $itemName,
                    'variants_generated' => $variantsGenerated,
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
                ]);
            } else {
                // All variants failed
                if ($item['status'] !== Image::STATUS_ERROR) {
                    $this->image->markError((int) $item['id'], 'Tutte le varianti fallite');
                }
                $failed++;
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'image_id' => $item['id'],
                    'product_name' => $itemName,
                    'error' => 'Generazione fallita',
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
                ]);
            }
        }

        exit;
    }

    /**
     * Job status (polling fallback)
     * GET /content-creator/projects/{id}/images/generate-job-status
     */
    public function generateJobStatus(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        header('Content-Type: application/json');

        if (!$job) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
    }

    /**
     * Cancel job
     * POST /content-creator/projects/{id}/images/cancel-job
     */
    public function cancelJob(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_POST['job_id'] ?? $_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        header('Content-Type: application/json');

        if (!$job) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $jobModel->cancel($jobId);
        echo json_encode(['success' => true, 'message' => 'Job annullato']);
    }

    // ===== CMS PUSH SSE =====

    /**
     * Start push job
     * POST /content-creator/projects/{id}/images/start-push-job
     */
    public function startPushJob(int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();
        header('Content-Type: application/json');

        $project = $this->getProject($id, $user['id']);
        if (!$project) return;

        if (($project['access_role'] ?? 'owner') === 'viewer') {
            echo json_encode(['error' => true, 'message' => 'Non hai i permessi']);
            return;
        }

        // Count approved variants ready for push
        $approvedCount = $this->variant->countApprovedByProject($id);
        if ($approvedCount === 0) {
            echo json_encode(['error' => true, 'message' => 'Nessuna variante approvata da pubblicare']);
            return;
        }

        $jobModel = new Job();
        $activeJob = $jobModel->getActiveForProject($id, Job::TYPE_IMAGE_PUSH);
        if ($activeJob) {
            echo json_encode(['error' => true, 'message' => 'Push già in corso', 'job_id' => (int) $activeJob['id']]);
            return;
        }

        $jobId = $jobModel->create([
            'project_id' => $id,
            'user_id' => $user['id'],
            'type' => Job::TYPE_IMAGE_PUSH,
            'items_requested' => $approvedCount,
        ]);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'items_queued' => $approvedCount,
        ]);
    }

    /**
     * SSE stream for CMS push
     * GET /content-creator/projects/{id}/images/push-stream
     */
    public function pushStream(int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['type'] !== Job::TYPE_IMAGE_PUSH) {
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        $project = $this->project->findAccessible($id, $user['id']);
        if (!$project || empty($project['connector_id'])) return;

        // SSE setup
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $jobModel->start($jobId);

        // Get connector
        $connectorModel = new Connector();
        $connData = $connectorModel->find($project['connector_id']);
        $connector = $connectorModel->createInstance($connData);

        if (!($connector instanceof ImageCapableConnectorInterface)) {
            $this->sendEvent('completed', ['job_id' => $jobId, 'error' => 'Connettore non supporta upload immagini']);
            exit;
        }

        $service = new ImageGenerationService();
        $defaults = ImageGenerationService::getProjectDefaults($project);
        $pushMode = $defaults['push_mode'] ?? ModuleLoader::getSetting('content-creator', 'image_push_mode', 'add_as_gallery');

        $variants = $this->variant->getApprovedByProject($id);
        $total = count($variants);
        $completed = 0;
        $failed = 0;

        $this->sendEvent('started', ['job_id' => $jobId, 'total' => $total]);

        foreach ($variants as $v) {
            Database::reconnect();

            if ($jobModel->isCancelled($jobId)) {
                $this->sendEvent('cancelled', ['job_id' => $jobId, 'completed' => $completed, 'failed' => $failed]);
                break;
            }

            if (empty($v['cms_entity_id'])) {
                $failed++;
                $jobModel->incrementFailed($jobId);
                $this->variant->markPushError((int) $v['id'], 'Nessun entity ID CMS');
                continue;
            }

            $imagePath = $service->getAbsolutePath($v['image_path']);
            $alt = $v['product_name'] . ' - Variante ' . $v['variant_number'];
            $position = $pushMode === 'replace_main' ? 0 : null;

            $result = $connector->uploadImage($v['cms_entity_id'], $v['cms_entity_type'] ?? 'product', $imagePath, [
                'alt' => $alt,
                'position' => $position,
                'filename' => ($v['sku'] ?: 'img') . '-v' . $v['variant_number'] . '.webp',
            ]);

            Database::reconnect();

            if ($result['success']) {
                $this->variant->markPushed((int) $v['id']);

                // Update image status to published
                $this->image->markPublished((int) $v['image_id']);

                $completed++;
                $jobModel->incrementCompleted($jobId);

                $this->sendEvent('item_completed', [
                    'variant_id' => $v['id'],
                    'product_name' => $v['product_name'],
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                    'percent' => $total > 0 ? round(($completed + $failed) / $total * 100) : 0,
                ]);
            } else {
                $this->variant->markPushError((int) $v['id'], $result['error'] ?? 'Errore upload');
                $failed++;
                $jobModel->incrementFailed($jobId);

                $this->sendEvent('item_error', [
                    'variant_id' => $v['id'],
                    'product_name' => $v['product_name'],
                    'error' => $result['error'] ?? 'Errore upload',
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                ]);
            }

            usleep(500000); // 500ms between push calls
        }

        if (!$jobModel->isCancelled($jobId)) {
            $jobModel->complete($jobId);
            $this->sendEvent('completed', [
                'job_id' => $jobId,
                'completed' => $completed,
                'failed' => $failed,
            ]);
        }

        exit;
    }

    /**
     * Push job status (polling fallback)
     * GET /content-creator/projects/{id}/images/push-job-status
     */
    public function pushJobStatus(int $id): void
    {
        // Same pattern as generateJobStatus
        Middleware::auth();
        $user = Auth::user();

        $jobId = (int) ($_GET['job_id'] ?? 0);
        $jobModel = new Job();
        $job = $jobModel->findByUser($jobId, $user['id']);

        header('Content-Type: application/json');

        if (!$job) {
            echo json_encode(['error' => true, 'message' => 'Job non trovato']);
            return;
        }

        echo json_encode(['success' => true, 'job' => $jobModel->getJobResponse($jobId)]);
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/controllers/ImageGeneratorController.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/controllers/ImageGeneratorController.php
git commit -m "feat(content-creator): add ImageGeneratorController with SSE generate + push streams"
```

---

## Task 15: Add Routes

**Files:**
- Modify: `modules/content-creator/routes.php`

**Important:** New image routes must be placed BEFORE the existing wildcard routes (line ~347).

- [ ] **Step 1: Read routes.php to find insertion point**

Read `modules/content-creator/routes.php` and find the line before the wildcard routes at the end:

```php
// These wildcard routes MUST be last
Router::get('/content-creator/projects/{id}/urls/{urlId}', ...
Router::get('/content-creator/projects/{id}', ...
```

- [ ] **Step 2: Add image routes BEFORE wildcards**

Insert the following block before the wildcard routes:

```php
// ===== IMAGE ROUTES =====

// Image serve (outside project context — global route)
Router::get('/content-creator/images/serve/{type}/{filename}', function ($type, $filename) {
    return (new \Modules\ContentCreator\Controllers\ImageController())->serve($type, $filename);
});

// Image list & import
Router::get('/content-creator/projects/{id}/images', function ($id) {
    return (new \Modules\ContentCreator\Controllers\ImageController())->index($id);
});
Router::get('/content-creator/projects/{id}/images/import', function ($id) {
    return (new \Modules\ContentCreator\Controllers\ImageController())->import($id);
});
Router::post('/content-creator/projects/{id}/images/import', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageController())->importStore($id);
});
Router::post('/content-creator/projects/{id}/images/fetch-cms', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageController())->fetchCmsProducts($id);
});

// Image approve/reject/regenerate
Router::post('/content-creator/projects/{id}/images/approve-bulk', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageController())->approveBulk($id);
});
Router::post('/content-creator/projects/{id}/images/delete-bulk', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageController())->deleteBulk($id);
});
Router::post('/content-creator/projects/{id}/images/{imgId}/approve', function ($id, $imgId) {
    (new \Modules\ContentCreator\Controllers\ImageController())->approveVariant($id, $imgId);
});
Router::post('/content-creator/projects/{id}/images/{imgId}/reject', function ($id, $imgId) {
    (new \Modules\ContentCreator\Controllers\ImageController())->rejectVariant($id, $imgId);
});
Router::post('/content-creator/projects/{id}/images/{imgId}/regenerate', function ($id, $imgId) {
    (new \Modules\ContentCreator\Controllers\ImageController())->regenerate($id, $imgId);
});

// Image generation SSE
Router::post('/content-creator/projects/{id}/images/start-generate-job', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->startGenerateJob($id);
});
Router::get('/content-creator/projects/{id}/images/generate-stream', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->generateStream($id);
});
Router::get('/content-creator/projects/{id}/images/generate-job-status', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->generateJobStatus($id);
});

// Image push SSE
Router::post('/content-creator/projects/{id}/images/start-push-job', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->startPushJob($id);
});
Router::get('/content-creator/projects/{id}/images/push-stream', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->pushStream($id);
});
Router::get('/content-creator/projects/{id}/images/push-job-status', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->pushJobStatus($id);
});
Router::post('/content-creator/projects/{id}/images/cancel-job', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageGeneratorController())->cancelJob($id);
});

// Image export
Router::get('/content-creator/projects/{id}/images/export/zip', function ($id) {
    (new \Modules\ContentCreator\Controllers\ImageController())->exportZip($id);
});

// Image preview (MUST be after specific /images/* routes)
Router::get('/content-creator/projects/{id}/images/{imgId}', function ($id, $imgId) {
    return (new \Modules\ContentCreator\Controllers\ImageController())->preview($id, $imgId);
});
```

- [ ] **Step 3: Verify syntax**

```bash
php -l modules/content-creator/routes.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/routes.php
git commit -m "feat(content-creator): add image routes — CRUD, SSE generate/push, serve, export"
```

---

## Task 16: Verify Connector Model has createInstance()

**Files:**
- Read: `modules/content-creator/models/Connector.php`

The `ImageController` and `ImageGeneratorController` both call `$connectorModel->createInstance($connData)`. Verify this method exists.

- [ ] **Step 1: Read Connector.php and check for createInstance method**

If the method doesn't exist, add it:

```php
/**
 * Create connector instance from config data
 */
public function createInstance(array $connData): \Modules\ContentCreator\Services\Connectors\ConnectorInterface
{
    $config = json_decode($connData['config'] ?? '{}', true);
    $config['url'] = $config['url'] ?? '';
    $config['api_key'] = $connData['api_key'] ?? $config['api_key'] ?? '';

    return match ($connData['type']) {
        'wordpress' => new \Modules\ContentCreator\Services\Connectors\WordPressConnector($config),
        'shopify' => new \Modules\ContentCreator\Services\Connectors\ShopifyConnector($config),
        'prestashop' => new \Modules\ContentCreator\Services\Connectors\PrestaShopConnector($config),
        'magento' => new \Modules\ContentCreator\Services\Connectors\MagentoConnector($config),
        default => throw new \RuntimeException("Tipo connettore non supportato: {$connData['type']}"),
    };
}
```

- [ ] **Step 2: Verify syntax + commit if modified**

```bash
php -l modules/content-creator/models/Connector.php
```

---

## Chunk 4 Complete

**Verify all controllers:**
```bash
php -l modules/content-creator/controllers/ImageController.php
php -l modules/content-creator/controllers/ImageGeneratorController.php
php -l modules/content-creator/routes.php
```
