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
            'message' => "{$inserted} prodotti importati" . (!empty($errors) ? " (" . count($errors) . " errori)" : ''),
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

        // Determine file path (all paths are relative to storage/images/)
        $storagePath = dirname(__DIR__, 3) . '/storage/images';
        if ($type === 'source') {
            $filePath = $image['source_image_path'];
        } else {
            $filePath = $variant['image_path'];
        }

        $fullPath = $storagePath . '/' . $filePath;

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
}
