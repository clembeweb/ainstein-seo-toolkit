<?php

namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Credits;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Services\CrawlerService;
use Modules\SeoAudit\Services\IssueDetector;

/**
 * CrawlController
 *
 * Gestisce avvio, monitoraggio e stop del crawl
 */
class CrawlController
{
    private Project $projectModel;
    private Page $pageModel;
    private Issue $issueModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
    }

    /**
     * Avvia crawl (risposta JSON per progress)
     */
    public function start(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Verifica che non sia già in corso
        if ($project['status'] === 'crawling') {
            echo json_encode(['error' => 'Crawl già in corso']);
            exit;
        }

        // Verifica crediti
        $crawlCost = Credits::getCost('crawl_per_page') ?? 0.2;
        $estimatedCost = $project['max_pages'] * $crawlCost;

        if (!Credits::hasEnough($user['id'], $estimatedCost * 0.1)) { // Almeno 10% del costo
            echo json_encode(['error' => 'Crediti insufficienti']);
            exit;
        }

        try {
            $crawler = new CrawlerService();
            $crawler->init($id, $user['id']);

            // Aggiorna stato
            $this->projectModel->update($id, ['status' => 'crawling']);

            // Fase 1: Discovery URL
            $urls = $crawler->discoverUrls();

            if (empty($urls)) {
                $this->projectModel->update($id, ['status' => 'failed']);
                echo json_encode(['error' => 'Nessun URL trovato nel sito']);
                exit;
            }

            // Log activity
            $this->projectModel->logActivity($id, $user['id'], 'crawl_started', [
                'urls_found' => count($urls),
                'crawl_mode' => $project['crawl_mode'],
            ]);

            echo json_encode([
                'success' => true,
                'phase' => 'discovery',
                'urls_found' => count($urls),
                'message' => 'Trovati ' . count($urls) . ' URL da scansionare',
            ]);

        } catch (\Exception $e) {
            $this->projectModel->update($id, ['status' => 'failed']);
            echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Crawl batch di pagine (chiamato via polling AJAX)
     */
    public function crawlBatch(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project || $project['status'] !== 'crawling') {
            echo json_encode(['error' => 'Crawl non attivo']);
            exit;
        }

        $batchSize = min((int) ($_POST['batch_size'] ?? 10), 20);

        try {
            $crawler = new CrawlerService();
            $crawler->init($id, $user['id']);

            $issueDetector = new IssueDetector();
            $issueDetector->init($id);

            // Ottieni URL da processare
            $progress = $this->projectModel->getCrawlProgress($id);
            $pagesCrawled = $progress['pages_crawled'];
            $pagesFound = $progress['pages_found'];

            if ($pagesCrawled >= $pagesFound) {
                // Completa
                $this->finalizeCrawl($id, $user['id']);
                echo json_encode([
                    'success' => true,
                    'complete' => true,
                    'message' => 'Scansione completata',
                ]);
                exit;
            }

            // Fetch URL pendenti da sitemap/discovery
            $config = \Core\Database::fetch("SELECT sitemap_urls FROM sa_site_config WHERE project_id = ?", [$id]);
            $allUrls = [];

            if ($config && !empty($config['sitemap_urls'])) {
                $sitemapService = new \Services\SitemapService();
                $sitemapUrls = json_decode($config['sitemap_urls'], true);
                $allUrls = $sitemapService->parseMultiple($sitemapUrls);
            }

            // Prendi batch non ancora crawlato
            $crawledUrls = \Core\Database::fetchAll(
                "SELECT url FROM sa_pages WHERE project_id = ?",
                [$id]
            );
            $crawledSet = array_flip(array_column($crawledUrls, 'url'));

            $toCrawl = [];
            foreach ($allUrls as $url) {
                if (!isset($crawledSet[$url])) {
                    $toCrawl[] = $url;
                }
                if (count($toCrawl) >= $batchSize) break;
            }

            $crawled = 0;
            $issuesFound = 0;

            foreach ($toCrawl as $url) {
                // Crawl pagina
                $pageData = $crawler->crawlPage($url);

                if ($pageData && empty($pageData['error'])) {
                    // Salva pagina
                    $pageId = $crawler->savePage($pageData);

                    // Rileva issues
                    $issuesFound += $issueDetector->analyzeAndSave($pageData, $pageId);

                    $crawled++;
                }

                // Aggiorna conteggio
                $this->projectModel->update($id, [
                    'pages_crawled' => $pagesCrawled + $crawled,
                ]);
            }

            // Aggiorna stats
            $this->projectModel->updateStats($id);

            $newProgress = $this->projectModel->getCrawlProgress($id);
            $isComplete = $newProgress['pages_crawled'] >= $newProgress['pages_found'];

            if ($isComplete) {
                $this->finalizeCrawl($id, $user['id']);
            }

            echo json_encode([
                'success' => true,
                'crawled' => $crawled,
                'issues_found' => $issuesFound,
                'progress' => $newProgress,
                'complete' => $isComplete,
            ]);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Stato crawl (polling)
     */
    public function status(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $progress = $this->projectModel->getCrawlProgress($id);
        $issueStats = $this->issueModel->countBySeverity($id);

        echo json_encode([
            'status' => $project['status'],
            'progress' => $progress,
            'issues' => $issueStats,
            'health_score' => $project['health_score'],
        ]);
        exit;
    }

    /**
     * Stop crawl
     */
    public function stop(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($id, $user['id']);

        if (!$project) {
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        if ($project['status'] !== 'crawling') {
            echo json_encode(['error' => 'Nessun crawl in corso']);
            exit;
        }

        try {
            // Finalizza con quello che abbiamo
            $this->finalizeCrawl($id, $user['id'], true);

            $this->projectModel->logActivity($id, $user['id'], 'crawl_stopped', [
                'pages_crawled' => $project['pages_crawled'],
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Crawl interrotto',
            ]);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Finalizza crawl: check a livello progetto e calcolo score
     */
    private function finalizeCrawl(int $projectId, int $userId, bool $stopped = false): void
    {
        $issueDetector = new IssueDetector();
        $issueDetector->init($projectId);

        // Check a livello progetto
        $issueDetector->runProjectLevelChecks();

        // Aggiorna stats e health score
        $this->projectModel->updateStats($projectId);

        // Aggiorna stato
        $this->projectModel->update($projectId, [
            'status' => $stopped ? 'completed' : 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->projectModel->logActivity($projectId, $userId, 'crawl_completed');
    }
}
