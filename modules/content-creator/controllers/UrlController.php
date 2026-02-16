<?php

namespace Modules\ContentCreator\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\ContentCreator\Models\Project;
use Modules\ContentCreator\Models\Url;
use Modules\ContentCreator\Models\Connector;
use Modules\ContentCreator\Services\Connectors\ConnectorInterface;
use Modules\ContentCreator\Services\Connectors\WordPressConnector;
use Modules\ContentCreator\Services\Connectors\ShopifyConnector;
use Modules\ContentCreator\Services\Connectors\PrestaShopConnector;
use Modules\ContentCreator\Services\Connectors\MagentoConnector;
use Services\SitemapService;

/**
 * UrlController
 *
 * Gestisce l'import e la gestione delle URL nel modulo Content Creator.
 * Workflow: Import (CSV/Sitemap/Manuale/CMS) -> Manage -> Approve/Reject -> Preview
 */
class UrlController
{
    private Project $project;
    private Url $url;

    public function __construct()
    {
        $this->project = new Project();
        $this->url = new Url();
    }

    /**
     * Verifica progetto e ownership
     */
    private function getProject(int $id, int $userId): ?array
    {
        $project = $this->project->findByUser($id, $userId);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            return null;
        }

        return $project;
    }

    /**
     * Wizard import URL
     * GET /content-creator/projects/{id}/import
     */
    public function import(int $id): string
    {
        $user = Auth::user();
        $project = $this->getProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/content-creator');
            exit;
        }

        // Carica connettori attivi per tab CMS
        $connectorModel = new Connector();
        $connectors = $connectorModel->getActive($user['id']);

        return View::render('content-creator/urls/import', [
            'title' => 'Importa URL - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'connectors' => $connectors,
            'currentPage' => 'import',
        ]);
    }

    /**
     * Import da CSV (AJAX)
     * POST /content-creator/projects/{id}/import/csv
     */
    public function importCsv(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            echo json_encode(['success' => false, 'error' => 'Nessun file caricato']);
            return;
        }

        $hasHeader = isset($_POST['has_header']);
        $delimiter = $_POST['delimiter'] ?? 'auto';
        $urlColumn = (int) ($_POST['url_column'] ?? 0);
        $keywordColumn = (int) ($_POST['keyword_column'] ?? -1);
        $categoryColumn = (int) ($_POST['category_column'] ?? -1);

        // Auto-detect delimiter
        if ($delimiter === 'auto') {
            $firstLine = fgets(fopen($_FILES['csv_file']['tmp_name'], 'r'));
            if (str_contains($firstLine, ';')) {
                $delimiter = ';';
            } elseif (str_contains($firstLine, "\t")) {
                $delimiter = "\t";
            } else {
                $delimiter = ',';
            }
        }

        try {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $urls = [];
            $lineNum = 0;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNum++;

                if ($lineNum === 1 && $hasHeader) {
                    continue;
                }

                $url = trim($row[$urlColumn] ?? '');

                if (empty($url)) {
                    continue;
                }

                // Normalizza URL
                if (!preg_match('#^https?://#', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $keyword = ($keywordColumn >= 0) ? trim($row[$keywordColumn] ?? '') : null;
                $category = ($categoryColumn >= 0) ? trim($row[$categoryColumn] ?? '') : null;

                $urls[] = [
                    'url' => $url,
                    'keyword' => $keyword ?: null,
                    'category' => $category ?: null,
                ];
            }

            fclose($handle);

            if (empty($urls)) {
                echo json_encode(['success' => false, 'error' => 'Nessuna URL valida trovata nel CSV']);
                return;
            }

            $inserted = $this->url->addBulk($id, $user['id'], $urls);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($urls),
                'message' => "Importate {$inserted} URL dal CSV",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import da sitemap (AJAX)
     * POST /content-creator/projects/{id}/import/sitemap
     */
    public function importSitemap(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $sitemapUrls = $_POST['sitemaps'] ?? [];
        $filter = trim($_POST['filter'] ?? '');

        if (empty($sitemapUrls)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una sitemap']);
            return;
        }

        try {
            $sitemapService = new SitemapService();
            $result = $sitemapService->previewMultiple($sitemapUrls, $filter, 2000);

            if (empty($result['urls'])) {
                echo json_encode(['success' => false, 'error' => 'Nessuna URL trovata nelle sitemap']);
                return;
            }

            $inserted = $this->url->addBulk($id, $user['id'], $result['urls']);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($result['urls']),
                'message' => "Importate {$inserted} URL dalla sitemap",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import da CMS (AJAX)
     * POST /content-creator/projects/{id}/import/cms
     */
    public function importFromCms(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $connectorId = (int) ($_POST['connector_id'] ?? 0);
        $entityType = trim($_POST['entity_type'] ?? 'products');

        if (!$connectorId) {
            echo json_encode(['success' => false, 'error' => 'Seleziona un connettore']);
            return;
        }

        if (!in_array($entityType, ['products', 'categories', 'pages'])) {
            echo json_encode(['success' => false, 'error' => 'Tipo contenuto non valido']);
            return;
        }

        $connectorModel = new Connector();
        $connector = $connectorModel->findByUser($connectorId, $user['id']);

        if (!$connector) {
            echo json_encode(['success' => false, 'error' => 'Connettore non trovato']);
            return;
        }

        try {
            $config = json_decode($connector['config'] ?? '{}', true);
            if (!is_array($config)) {
                $config = [];
            }

            $service = $this->createConnectorService($connector['type'], $config);
            $result = $service->fetchItems($entityType, 100);

            if (!$result['success']) {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Errore nel recupero dati dal CMS']);
                return;
            }

            $items = $result['items'] ?? [];
            if (empty($items)) {
                echo json_encode(['success' => false, 'error' => 'Nessun elemento trovato nel CMS']);
                return;
            }

            // Normalizza items per addBulkFromCms
            $normalizedItems = [];
            foreach ($items as $item) {
                if (empty($item['url'])) {
                    continue;
                }
                $normalizedItems[] = [
                    'url' => $item['url'],
                    'keyword' => $item['title'] ?? $item['name'] ?? null,
                    'category' => $item['category'] ?? null,
                    'entity_id' => $item['id'] ?? null,
                    'entity_type' => $this->mapEntityType($entityType),
                ];
            }

            $inserted = $this->url->addBulkFromCms($id, $user['id'], $connectorId, $normalizedItems);

            echo json_encode([
                'success' => true,
                'message' => "Importate {$inserted} URL da " . ucfirst($connector['type']),
                'inserted' => $inserted,
                'total_found' => count($items),
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Import manuale (AJAX)
     * POST /content-creator/projects/{id}/import/manual
     */
    public function importManual(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlsText = $_POST['urls_text'] ?? '';

        if (empty(trim($urlsText))) {
            echo json_encode(['success' => false, 'error' => 'Inserisci almeno un URL']);
            return;
        }

        try {
            $lines = explode("\n", $urlsText);
            $urls = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                $url = $line;

                if (empty($url)) {
                    continue;
                }

                // Normalizza URL
                if (!preg_match('#^https?://#', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }

                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }

            if (empty($urls)) {
                echo json_encode(['success' => false, 'error' => 'Nessuna URL valida trovata']);
                return;
            }

            $inserted = $this->url->addBulk($id, $user['id'], $urls);

            echo json_encode([
                'success' => true,
                'inserted' => $inserted,
                'total_found' => count($urls),
                'message' => "Importate {$inserted} URL",
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Scopri sitemap da URL sito (AJAX)
     * POST /content-creator/projects/{id}/discover
     */
    public function discover(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $siteUrl = trim($_POST['site_url'] ?? '');

        if (empty($siteUrl)) {
            echo json_encode(['success' => false, 'error' => 'Inserisci URL del sito']);
            return;
        }

        // Normalizza URL
        if (!preg_match('#^https?://#', $siteUrl)) {
            $siteUrl = 'https://' . $siteUrl;
        }
        $siteUrl = rtrim($siteUrl, '/');

        if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'URL non valido']);
            return;
        }

        try {
            $sitemapService = new SitemapService();
            $sitemaps = $sitemapService->discoverFromRobotsTxt($siteUrl, true);

            if (empty($sitemaps)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Nessuna sitemap trovata. Verifica che il sito abbia una sitemap.'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'site_url' => $siteUrl,
                'sitemaps' => $sitemaps,
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk approve URL (AJAX)
     * POST /content-creator/projects/{id}/urls/bulk-approve
     */
    public function bulkApprove(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlIds = $_POST['url_ids'] ?? [];

        if (empty($urlIds)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una URL']);
            return;
        }

        $urlIds = array_map('intval', $urlIds);

        try {
            $count = $this->url->approveBulk($urlIds);
            echo json_encode([
                'success' => true,
                'approved' => $count,
                'message' => "Approvate {$count} URL",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk delete URL (AJAX)
     * POST /content-creator/projects/{id}/urls/bulk-delete
     */
    public function bulkDelete(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlIds = $_POST['url_ids'] ?? [];

        if (empty($urlIds)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una URL']);
            return;
        }

        $urlIds = array_map('intval', $urlIds);

        try {
            $count = $this->url->deleteBulk($urlIds);
            echo json_encode([
                'success' => true,
                'deleted' => $count,
                'message' => "Eliminate {$count} URL",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Reset errori scraping (AJAX)
     * POST /content-creator/projects/{id}/urls/reset-scrape-errors
     */
    public function resetScrapeErrors(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        try {
            $count = $this->url->resetScrapeErrors($id);
            echo json_encode([
                'success' => true,
                'reset' => $count,
                'message' => "Reset {$count} errori scraping",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Reset errori generazione (AJAX)
     * POST /content-creator/projects/{id}/urls/reset-generation-errors
     */
    public function resetGenerationErrors(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        try {
            $count = $this->url->resetGenerationErrors($id);
            echo json_encode([
                'success' => true,
                'reset' => $count,
                'message' => "Reset {$count} errori generazione",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Aggiorna singola URL (AJAX)
     * POST /content-creator/projects/{id}/urls/{urlId}/update
     */
    public function updateUrl(int $id, int $urlId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlRecord = $this->url->findByProject($urlId, $id);

        if (!$urlRecord) {
            echo json_encode(['success' => false, 'error' => 'URL non trovata']);
            return;
        }

        $aiH1 = trim($_POST['ai_h1'] ?? '');
        $aiContent = trim($_POST['ai_content'] ?? '');
        $aiWordCount = (int) ($_POST['ai_word_count'] ?? 0);

        if (empty($aiH1) && empty($aiContent)) {
            echo json_encode(['success' => false, 'error' => 'Inserisci almeno un campo']);
            return;
        }

        // Ricalcola word count se non fornito
        if ($aiWordCount === 0 && !empty($aiContent)) {
            $aiWordCount = str_word_count(strip_tags($aiContent));
        }

        try {
            $this->url->update($urlId, [
                'ai_h1' => $aiH1 ?: null,
                'ai_content' => $aiContent ?: null,
                'ai_word_count' => $aiWordCount,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'URL aggiornata',
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Approva singola URL (AJAX)
     * POST /content-creator/projects/{id}/urls/{urlId}/approve
     */
    public function approve(int $id, int $urlId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlRecord = $this->url->findByProject($urlId, $id);

        if (!$urlRecord) {
            echo json_encode(['success' => false, 'error' => 'URL non trovata']);
            return;
        }

        try {
            $this->url->approve($urlId);
            echo json_encode(['success' => true, 'message' => 'URL approvata']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Rifiuta singola URL (AJAX)
     * POST /content-creator/projects/{id}/urls/{urlId}/reject
     */
    public function reject(int $id, int $urlId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlRecord = $this->url->findByProject($urlId, $id);

        if (!$urlRecord) {
            echo json_encode(['success' => false, 'error' => 'URL non trovata']);
            return;
        }

        try {
            $this->url->reject($urlId);
            echo json_encode(['success' => true, 'message' => 'URL rifiutata']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Elimina singola URL (AJAX)
     * POST /content-creator/projects/{id}/urls/{urlId}/delete
     */
    public function delete(int $id, int $urlId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->findByUser($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $urlRecord = $this->url->findByProject($urlId, $id);

        if (!$urlRecord) {
            echo json_encode(['success' => false, 'error' => 'URL non trovata']);
            return;
        }

        try {
            $this->url->delete($urlId);
            echo json_encode(['success' => true, 'message' => 'URL eliminata']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Import da Keyword Research (chiamata da KR oppure diretta)
     * POST /content-creator/projects/{id}/import/keyword-research
     */
    public function importFromKR(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->getProject($id, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
            return;
        }

        $researchId = (int) ($_POST['research_id'] ?? 0);

        if (!$researchId) {
            echo json_encode(['success' => false, 'error' => 'ID ricerca mancante.']);
            return;
        }

        // Verifica che la ricerca esista e sia dell'utente
        $research = \Core\Database::fetch(
            "SELECT r.* FROM kr_researches r WHERE r.id = ? AND r.user_id = ?",
            [$researchId, $user['id']]
        );

        if (!$research) {
            echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
            return;
        }

        // Carica cluster della ricerca con le keyword
        $clusters = \Core\Database::fetchAll(
            "SELECT * FROM kr_clusters WHERE research_id = ? ORDER BY sort_order ASC",
            [$researchId]
        );

        if (empty($clusters)) {
            echo json_encode(['success' => false, 'error' => 'Nessun cluster trovato.']);
            return;
        }

        // Arricchisci cluster con secondary keywords
        foreach ($clusters as &$cluster) {
            $keywords = \Core\Database::fetchAll(
                "SELECT text FROM kr_keywords WHERE cluster_id = ? AND is_main = 0 ORDER BY volume DESC",
                [$cluster['id']]
            );
            $cluster['secondary_keywords'] = array_column($keywords, 'text');
        }
        unset($cluster);

        $inserted = $this->url->addBulkFromKR($id, $user['id'], $clusters);

        echo json_encode([
            'success' => true,
            'added' => $inserted,
            'total' => count($clusters),
            'message' => "{$inserted} pagine importate da Keyword Research." . ($inserted < count($clusters) ? ' ' . (count($clusters) - $inserted) . ' saltate (duplicati o senza URL).' : ''),
        ]);
    }

    /**
     * Preview singola URL
     * GET /content-creator/projects/{id}/urls/{urlId}
     */
    public function preview(int $id, int $urlId): string
    {
        $user = Auth::user();
        $project = $this->getProject($id, $user['id']);

        if (!$project) {
            Router::redirect('/content-creator');
            exit;
        }

        $urlRecord = $this->url->findByProject($urlId, $id);

        if (!$urlRecord) {
            $_SESSION['_flash']['error'] = 'URL non trovata';
            Router::redirect("/content-creator/projects/{$id}/results");
            exit;
        }

        return View::render('content-creator/urls/preview', [
            'title' => 'Preview URL - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'url' => $urlRecord,
            'currentPage' => 'results',
        ]);
    }

    /**
     * Factory per istanziare il servizio connettore corretto
     */
    private function createConnectorService(string $type, array $config): ConnectorInterface
    {
        return match ($type) {
            'wordpress' => new WordPressConnector($config),
            'shopify' => new ShopifyConnector($config),
            'prestashop' => new PrestaShopConnector($config),
            'magento' => new MagentoConnector($config),
            default => throw new \Exception("Tipo connettore non supportato: {$type}"),
        };
    }

    /**
     * Mappa entity type plurale → singolare per il DB
     */
    private function mapEntityType(string $entityType): string
    {
        return match ($entityType) {
            'products' => 'product',
            'categories' => 'category',
            'pages' => 'page',
            default => $entityType,
        };
    }
}
