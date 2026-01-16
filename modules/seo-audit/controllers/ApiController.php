<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Database;
use Modules\SeoAudit\Models\Project;

/**
 * ApiController
 *
 * Gestisce API per import URL (sitemap, spider, manual)
 */
class ApiController
{
    private Project $projectModel;

    public function __construct()
    {
        $this->projectModel = new Project();
    }

    /**
     * Store imported URLs (from any source)
     */
    public function storeUrls(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $importType = $input['import_type'] ?? 'manual';
        $urls = [];

        switch ($importType) {
            case 'manual':
                // Parse text area input
                $urlsText = $input['urls_text'] ?? '';
                $lines = explode("\n", $urlsText);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) continue;

                    // Support "url, keyword" or "url\tkeyword" format
                    $parts = preg_split('/[\t,]/', $line, 2);
                    $url = trim($parts[0]);

                    if (!empty($url)) {
                        $urls[] = $this->normalizeUrl($url, $project['base_url']);
                    }
                }
                break;

            case 'spider':
            case 'sitemap':
                $urls = $input['urls'] ?? [];
                break;

            case 'csv':
                // Handle CSV upload - for now just extract URLs
                if (isset($_FILES['csv_file'])) {
                    $urls = $this->parseCsvFile($_FILES['csv_file'], $input);
                }
                break;
        }

        // Filter and deduplicate
        $urls = array_unique(array_filter($urls));

        if (empty($urls)) {
            echo json_encode(['success' => false, 'error' => 'Nessun URL valido trovato']);
            exit;
        }

        // Save URLs for crawling
        $imported = $this->saveUrlsForCrawl($projectId, $urls);

        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'total' => count($urls),
            'message' => "{$imported} URL importati per la scansione",
        ]);
        exit;
    }

    /**
     * Save URLs to sa_pages as pending and to sa_site_config for crawling
     */
    public function saveUrlsForCrawl(int $projectId, array $urls): int
    {
        if (empty($urls)) return 0;

        $newCount = 0;

        // Get existing URLs from sa_pages
        $existingPages = Database::fetchAll(
            "SELECT url FROM sa_pages WHERE project_id = ?",
            [$projectId]
        );
        $existingUrls = array_column($existingPages, 'url');

        // Insert new pending pages
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) continue;

            // Skip if already exists
            if (in_array($url, $existingUrls)) continue;

            try {
                Database::execute("
                    INSERT INTO sa_pages (project_id, url, status, created_at)
                    VALUES (?, ?, 'pending', NOW())
                ", [$projectId, $url]);
                $newCount++;
                $existingUrls[] = $url;
            } catch (\Exception $e) {
                // URL might already exist, skip
                continue;
            }
        }

        // Also update sa_site_config.discovered_urls (for backward compatibility)
        $allUrls = array_unique(array_merge($existingUrls, $urls));
        Database::execute("
            INSERT INTO sa_site_config (project_id, discovered_urls)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE discovered_urls = VALUES(discovered_urls)
        ", [
            $projectId,
            json_encode(array_values($allUrls)),
        ]);

        // Update project pages_found (count pending pages)
        $pendingCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'pending'",
            [$projectId]
        )['cnt'] ?? 0;

        $crawledCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'crawled'",
            [$projectId]
        )['cnt'] ?? 0;

        $this->projectModel->update($projectId, [
            'pages_found' => $pendingCount + $crawledCount,
            'pages_crawled' => $crawledCount,
            'status' => 'pending',
        ]);

        return $newCount > 0 ? $newCount : count($urls);
    }

    /**
     * Normalize URL relative to base
     */
    private function normalizeUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);

        // Already absolute
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        // Protocol-relative
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        // Relative to root
        if (strpos($url, '/') === 0) {
            return rtrim($baseUrl, '/') . $url;
        }

        // Relative to current (append to base)
        return rtrim($baseUrl, '/') . '/' . $url;
    }

    /**
     * Parse CSV file for URLs
     */
    private function parseCsvFile(array $file, array $options): array
    {
        $urls = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $urls;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return $urls;

        $delimiter = $options['delimiter'] ?? ',';
        if ($delimiter === 'auto') {
            $delimiter = ',';
        }

        $urlColumn = (int) ($options['url_column'] ?? 0);
        $hasHeader = isset($options['has_header']);
        $rowNum = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;

            // Skip header
            if ($hasHeader && $rowNum === 1) continue;

            if (isset($row[$urlColumn]) && !empty(trim($row[$urlColumn]))) {
                $urls[] = trim($row[$urlColumn]);
            }
        }

        fclose($handle);
        return $urls;
    }
}
