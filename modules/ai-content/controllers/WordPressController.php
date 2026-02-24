<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AiContent\Models\WpSite;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\Project;
use Services\ScraperService;

/**
 * WordPressController
 *
 * Manages WordPress site connections and article publishing
 * Uses shared ScraperService for HTTP requests
 */
class WordPressController
{
    private WpSite $wpSite;
    private Article $article;
    private ScraperService $scraper;

    // API endpoints
    private const ENDPOINT_PING = '/wp-json/seo-toolkit/v1/ping';
    private const ENDPOINT_CATEGORIES = '/wp-json/seo-toolkit/v1/categories';
    private const ENDPOINT_POSTS = '/wp-json/seo-toolkit/v1/posts';

    // HTTP timeout
    private const HTTP_TIMEOUT = 30;

    public function __construct()
    {
        $this->wpSite = new WpSite();
        $this->article = new Article();
        $this->scraper = new ScraperService();
    }

    /**
     * Display list of connected WordPress sites
     *
     * @param int|null $projectId Optional project ID for context
     */
    public function index(?int $projectId = null): string
    {
        $user = Auth::user();
        $project = null;

        // If projectId specified, verify ownership
        if ($projectId !== null) {
            $projectModel = new Project();
            $project = $projectModel->findAccessible($projectId, $user['id']);

            if (!$project) {
                $_SESSION['_flash']['error'] = 'Progetto non trovato';
                header('Location: ' . url('/ai-content'));
                exit;
            }
        }

        $sites = $this->wpSite->allByUser($user['id']);

        return View::render('ai-content/wordpress/index', [
            'title' => 'Siti WordPress - AI Content',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'sites' => $sites,
            'project' => $project,
            'projectId' => $projectId
        ]);
    }

    /**
     * Add new WordPress site
     *
     * @param int|null $projectId Optional project ID for context
     */
    public function addSite(?int $projectId = null): void
    {
        // Cattura qualsiasi output accidentale
        ob_start();

        $user = Auth::user();

        // Parse JSON body or form data
        $input = $this->getInput();

        // projectId da parametro route o da input (legacy)
        if ($projectId === null && !empty($input['project_id'])) {
            $projectId = (int) $input['project_id'];
        }

        $name = trim($input['name'] ?? '');
        $url = trim($input['url'] ?? '');
        $apiKey = trim($input['api_key'] ?? '');

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del sito è obbligatorio';
        }

        if (empty($url)) {
            $errors[] = 'L\'URL del sito è obbligatorio';
        } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'L\'URL non è valido';
        }

        if (empty($apiKey)) {
            $errors[] = 'L\'API Key è obbligatoria';
        } elseif (strlen($apiKey) < 32) {
            $errors[] = 'L\'API Key non sembra valida (troppo corta)';
        }

        if (!empty($errors)) {
            $this->jsonResponse(['success' => false, 'error' => implode('. ', $errors)]);
        }

        // Normalize URL
        $url = rtrim($url, '/');

        // Check if URL already exists for user
        if ($this->wpSite->urlExists($url, $user['id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Questo sito è già collegato']);
        }

        // Test connection before saving
        $testResult = $this->testSiteConnection($url, $apiKey);

        if (!$testResult['success']) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Impossibile connettersi al sito: ' . $testResult['error']
            ]);
        }

        try {
            // Create site record
            $siteId = $this->wpSite->create([
                'user_id' => $user['id'],
                'name' => $name,
                'url' => $url,
                'api_key' => $apiKey,
                'is_active' => true
            ]);

            // Try to sync categories
            $this->syncSiteCategories($siteId, $url, $apiKey);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Sito WordPress collegato con successo',
                'site_id' => $siteId
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Errore durante il salvataggio: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove WordPress site
     */
    public function removeSite(int $id): void
    {
        ob_start();

        $user = Auth::user();

        // Verify ownership
        $site = $this->wpSite->find($id, $user['id']);

        if (!$site) {
            $this->jsonResponse(['success' => false, 'error' => 'Sito non trovato']);
        }

        try {
            $this->wpSite->delete($id, $user['id']);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Sito rimosso con successo'
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Errore durante la rimozione: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test connection to WordPress site
     */
    public function testConnection(int $id): void
    {
        ob_start();

        $user = Auth::user();

        // Verify ownership
        $site = $this->wpSite->find($id, $user['id']);

        if (!$site) {
            $this->jsonResponse(['success' => false, 'error' => 'Sito non trovato']);
        }

        $result = $this->testSiteConnection($site['url'], $site['api_key']);

        if ($result['success']) {
            // Update last sync timestamp
            $this->wpSite->update($id, ['last_sync_at' => date('Y-m-d H:i:s')], $user['id']);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Connessione riuscita!',
                'wp_version' => $result['wp_version'] ?? null,
                'plugin_version' => $result['plugin_version'] ?? null
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Sync categories from WordPress
     */
    public function syncCategories(int $id): void
    {
        ob_start();

        $user = Auth::user();

        // Verify ownership
        $site = $this->wpSite->find($id, $user['id']);

        if (!$site) {
            $this->jsonResponse(['success' => false, 'error' => 'Sito non trovato']);
        }

        $result = $this->syncSiteCategories($id, $site['url'], $site['api_key']);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Categorie sincronizzate',
                'categories_count' => $result['count']
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Get cached categories for a site (AJAX)
     */
    public function getCategories(int $id): void
    {
        ob_start();

        $user = Auth::user();

        // Verify ownership
        $site = $this->wpSite->find($id, $user['id']);

        if (!$site) {
            $this->jsonResponse(['success' => false, 'error' => 'Sito non trovato']);
        }

        $categories = $site['categories'] ?? [];

        // If no cached categories, try to sync
        if (empty($categories)) {
            $result = $this->syncSiteCategories($id, $site['url'], $site['api_key']);
            if ($result['success']) {
                $categories = $result['categories'];
            }
        }

        $this->jsonResponse([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Publish article to WordPress site
     */
    public function publish(int $articleId): void
    {
        ob_start();

        $user = Auth::user();

        // Parse input
        $input = $this->getInput();

        $wpSiteId = (int) ($input['wp_site_id'] ?? 0);
        $categoryId = $input['category_id'] ?? null;
        $categoryName = trim($input['category'] ?? '');
        $wpStatus = $input['status'] ?? 'draft';

        // Validate WordPress site ID
        if (!$wpSiteId) {
            $this->jsonResponse(['success' => false, 'error' => 'Seleziona un sito WordPress']);
        }

        // Verify article exists and status
        $article = $this->article->find($articleId);

        if (!$article) {
            $this->jsonResponse(['success' => false, 'error' => 'Articolo non trovato']);
        }

        if ($article['status'] !== 'ready') {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Solo gli articoli con status "ready" possono essere pubblicati. Status attuale: ' . $article['status']
            ]);
        }

        if (empty($article['content'])) {
            $this->jsonResponse(['success' => false, 'error' => 'L\'articolo non ha contenuto']);
        }

        // Verify WordPress site exists
        $wpSite = $this->wpSite->find($wpSiteId);

        if (!$wpSite) {
            $this->jsonResponse(['success' => false, 'error' => 'Sito WordPress non trovato']);
        }

        if (!$wpSite['is_active']) {
            $this->jsonResponse(['success' => false, 'error' => 'Il sito WordPress non è attivo']);
        }

        // Validate status
        $allowedStatuses = ['draft', 'publish', 'pending', 'private'];
        if (!in_array($wpStatus, $allowedStatuses)) {
            $wpStatus = 'draft';
        }

        // Prepare post data
        $postData = [
            'title' => $article['title'],
            'content' => $article['content'],
            'meta_description' => $article['meta_description'] ?? '',
            'status' => $wpStatus,
        ];

        // Add category if provided
        if ($categoryId) {
            $postData['category_id'] = (int) $categoryId;
        } elseif ($categoryName) {
            $postData['category_name'] = $categoryName;
        }

        // Make API call to WordPress
        $result = $this->callWordPressApi(
            $wpSite['url'] . self::ENDPOINT_POSTS,
            $wpSite['api_key'],
            'POST',
            $postData
        );

        if (!$result['success']) {
            // Log failed attempt
            $this->wpSite->logPublish($articleId, $wpSiteId, 'failed', null, [
                'error' => $result['error'],
                'http_code' => $result['http_code'] ?? null
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => 'Errore pubblicazione: ' . $result['error']
            ]);
        }

        $wpPostId = $result['data']['post_id'] ?? null;
        $wpPostUrl = $result['data']['post_url'] ?? null;

        if (!$wpPostId) {
            // Log failed attempt
            $this->wpSite->logPublish($articleId, $wpSiteId, 'failed', null, [
                'error' => 'Risposta WP non contiene post_id',
                'response' => $result['data']
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => 'Risposta WordPress non valida: post_id mancante'
            ]);
        }

        try {
            // Update article as published
            $this->article->markPublished($articleId, $wpSiteId, $wpPostId);

            // Save published URL if available
            if ($wpPostUrl) {
                $this->article->update($articleId, ['published_url' => $wpPostUrl]);
            }

            // Log successful publish
            $this->wpSite->logPublish($articleId, $wpSiteId, 'success', $wpPostId, [
                'post_url' => $wpPostUrl,
                'wp_status' => $wpStatus
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Articolo pubblicato con successo!',
                'wp_post_id' => $wpPostId,
                'wp_post_url' => $wpPostUrl
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Articolo pubblicato su WP ma errore nel salvataggio locale: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle site active status
     */
    public function toggleActive(int $id): void
    {
        ob_start();

        $user = Auth::user();

        $site = $this->wpSite->find($id, $user['id']);

        if (!$site) {
            $this->jsonResponse(['success' => false, 'error' => 'Sito non trovato']);
        }

        try {
            $this->wpSite->toggleActive($id, $user['id']);
            $newStatus = !$site['is_active'];

            $this->jsonResponse([
                'success' => true,
                'message' => $newStatus ? 'Sito attivato' : 'Sito disattivato',
                'is_active' => $newStatus
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test connection to a WordPress site
     */
    private function testSiteConnection(string $url, string $apiKey): array
    {
        $result = $this->callWordPressApi(
            $url . self::ENDPOINT_PING,
            $apiKey,
            'GET'
        );

        if (!$result['success']) {
            return $result;
        }

        // Check response structure
        if (!isset($result['data']['success']) || !$result['data']['success']) {
            return [
                'success' => false,
                'error' => $result['data']['message'] ?? 'Risposta ping non valida'
            ];
        }

        return [
            'success' => true,
            'wp_version' => $result['data']['wp_version'] ?? null,
            'plugin_version' => $result['data']['plugin_version'] ?? null
        ];
    }

    /**
     * Sync categories from a WordPress site
     */
    private function syncSiteCategories(int $siteId, string $url, string $apiKey): array
    {
        $result = $this->callWordPressApi(
            $url . self::ENDPOINT_CATEGORIES,
            $apiKey,
            'GET'
        );

        if (!$result['success']) {
            return $result;
        }

        $categories = $result['data']['categories'] ?? $result['data'] ?? [];

        // Ensure it's an array
        if (!is_array($categories)) {
            return [
                'success' => false,
                'error' => 'Formato categorie non valido'
            ];
        }

        // Save to cache
        $this->wpSite->updateCategoriesCache($siteId, $categories);

        return [
            'success' => true,
            'categories' => $categories,
            'count' => count($categories)
        ];
    }

    /**
     * Make HTTP call to WordPress API via shared ScraperService
     */
    private function callWordPressApi(string $url, string $apiKey, string $method = 'GET', ?array $data = null): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-SEO-Toolkit-Key: ' . $apiKey
        ];

        $options = [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
            'api_mode' => true, // Skip default browser headers that may trigger WAF
        ];

        if ($method === 'GET') {
            $result = $this->scraper->fetchJson($url, $options);
        } else {
            // POST or other methods - pass api_mode in options
            $result = $this->scraper->postJson($url, $data ?? [], $headers, $options);
        }

        if (isset($result['error'])) {
            // Map error to user-friendly message
            $errorMessage = $result['message'] ?? 'Errore connessione';

            if (strpos($errorMessage, 'resolve') !== false) {
                $errorMessage = 'Impossibile risolvere il dominio. Verifica l\'URL.';
            } elseif (strpos($errorMessage, 'connect') !== false) {
                $errorMessage = 'Impossibile connettersi al server. Verifica che il sito sia online.';
            } elseif (strpos($errorMessage, 'timeout') !== false) {
                $errorMessage = 'Connessione scaduta. Il server non risponde.';
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'http_code' => $result['http_code'] ?? 0
            ];
        }

        // Handle HTTP errors
        if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            return $this->handleHttpError($result['http_code'], json_encode($result['data'] ?? []));
        }

        return [
            'success' => true,
            'data' => $result['data'],
            'http_code' => $result['http_code']
        ];
    }

    /**
     * Handle cURL errors with user-friendly messages
     */
    private function handleCurlError(int $errno, string $error): array
    {
        $messages = [
            CURLE_COULDNT_RESOLVE_HOST => 'Impossibile risolvere il dominio. Verifica l\'URL.',
            CURLE_COULDNT_CONNECT => 'Impossibile connettersi al server. Verifica che il sito sia online.',
            CURLE_OPERATION_TIMEDOUT => 'Connessione scaduta. Il server non risponde.',
            CURLE_SSL_CONNECT_ERROR => 'Errore SSL. Verifica il certificato del sito.',
            CURLE_SSL_CERTPROBLEM => 'Problema con il certificato SSL.',
            CURLE_SSL_CIPHER => 'Errore cifratura SSL.',
        ];

        $message = $messages[$errno] ?? "Errore di connessione: {$error}";

        return [
            'success' => false,
            'error' => $message,
            'curl_errno' => $errno
        ];
    }

    /**
     * Handle HTTP errors with user-friendly messages
     */
    private function handleHttpError(int $httpCode, string $response): array
    {
        // Try to parse error from response
        $decoded = json_decode($response, true);
        $serverMessage = $decoded['message'] ?? $decoded['error'] ?? null;

        $messages = [
            400 => 'Richiesta non valida',
            401 => 'API Key non valida o scaduta',
            403 => 'Accesso negato. Verifica i permessi dell\'API Key.',
            404 => 'Endpoint non trovato. Verifica che il plugin SEO Toolkit sia installato.',
            405 => 'Metodo non consentito',
            429 => 'Troppe richieste. Riprova tra qualche minuto.',
            500 => 'Errore interno del server WordPress',
            502 => 'Bad Gateway - Il server non è raggiungibile',
            503 => 'Servizio non disponibile. Il sito potrebbe essere in manutenzione.',
            504 => 'Gateway Timeout - Il server non risponde'
        ];

        $message = $messages[$httpCode] ?? "Errore HTTP {$httpCode}";

        if ($serverMessage) {
            $message .= ": {$serverMessage}";
        }

        return [
            'success' => false,
            'error' => $message,
            'http_code' => $httpCode
        ];
    }

    /**
     * Download WordPress plugin as ZIP
     */
    public function downloadPlugin(): void
    {
        $pluginDir = \ROOT_PATH . '/storage/plugins/seo-toolkit-connector';
        $zipFilename = 'seo-toolkit-connector.zip';

        // Check if plugin directory exists
        if (!is_dir($pluginDir)) {
            http_response_code(404);
            echo 'Plugin non trovato';
            exit;
        }

        // Create temporary ZIP file
        $tempFile = sys_get_temp_dir() . '/' . uniqid('seo-toolkit-', true) . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo 'Impossibile creare il file ZIP';
            exit;
        }

        // Add all files from plugin directory
        $this->addDirToZip($zip, $pluginDir, 'seo-toolkit-connector');

        $zip->close();

        // Verify ZIP was created
        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
            http_response_code(500);
            echo 'Errore nella creazione del file ZIP';
            exit;
        }

        // Send headers for download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output file content
        readfile($tempFile);

        // Clean up temp file
        @unlink($tempFile);

        exit;
    }

    /**
     * Recursively add directory to ZIP archive
     */
    private function addDirToZip(\ZipArchive $zip, string $dir, string $zipPath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . '/' . substr($filePath, strlen($dir) + 1);

            // Convert Windows paths to Unix for ZIP
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Get input data from JSON body or POST
     */
    private function getInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $decoded = json_decode($json, true);

            // Verifica errori JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Fallback a $_POST se JSON non valido
                return $_POST;
            }

            return $decoded ?: [];
        }

        return $_POST;
    }

    /**
     * Invia risposta JSON pulita
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        // Pulisci qualsiasi output accidentale
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
