<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Source;
use Modules\AiContent\Models\WpSite;

/**
 * ArticleController
 *
 * Handles article management for AI Content module
 */
class ArticleController
{
    private Article $article;
    private Keyword $keyword;
    private Source $source;
    private WpSite $wpSite;

    // Credit costs
    private const CREDIT_GENERATE = 10;
    private const CREDIT_SCRAPE_PER_URL = 1;

    public function __construct()
    {
        $this->article = new Article();
        $this->keyword = new Keyword();
        $this->source = new Source();
        $this->wpSite = new WpSite();
    }

    /**
     * Display list of articles with pagination
     */
    public function index(): string
    {
        $user = Auth::user();
        $page = (int) ($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? null;

        $articlesData = $this->article->allByUser($user['id'], $page, 20, $status);
        $wpSites = $this->wpSite->getActiveSites($user['id']);

        return View::render('ai-content/articles/index', [
            'title' => 'Articoli - AI Content',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'articles' => $articlesData['data'],
            'pagination' => [
                'current_page' => $articlesData['current_page'],
                'last_page' => $articlesData['last_page'],
                'total' => $articlesData['total'],
                'per_page' => $articlesData['per_page']
            ],
            'currentStatus' => $status,
            'stats' => $this->article->getStats($user['id']),
            'wpSites' => $wpSites
        ]);
    }

    /**
     * Show article detail with HTML preview
     */
    public function show(int $id): string
    {
        $user = Auth::user();

        $article = $this->article->findWithRelations($id, $user['id']);

        if (!$article) {
            $_SESSION['_flash']['error'] = 'Articolo non trovato';
            header('Location: ' . url('/ai-content/articles'));
            exit;
        }

        // Decode brief_data if JSON string
        if (isset($article['brief_data']) && is_string($article['brief_data'])) {
            $article['brief_data'] = json_decode($article['brief_data'], true);
        }

        $wpSites = $this->wpSite->getActiveSites($user['id']);

        return View::render('ai-content/articles/show', [
            'title' => ($article['title'] ?: 'Articolo #' . $id) . ' - AI Content',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'article' => $article,
            'wpSites' => $wpSites
        ]);
    }

    /**
     * Generate new article (AJAX)
     * Receives: keyword_id, sources (array of URLs)
     */
    public function generate(): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();

        // Parse JSON body
        $input = json_decode(file_get_contents('php://input'), true);

        $keywordId = (int) ($input['keyword_id'] ?? 0);
        $sourceUrls = $input['sources'] ?? [];

        // Validation
        if (!$keywordId) {
            echo json_encode(['success' => false, 'error' => 'Keyword ID richiesto']);
            exit;
        }

        if (empty($sourceUrls)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una fonte']);
            exit;
        }

        if (count($sourceUrls) > 6) {
            echo json_encode(['success' => false, 'error' => 'Massimo 6 fonti consentite']);
            exit;
        }

        // Verify keyword ownership
        $keyword = $this->keyword->find($keywordId, $user['id']);
        if (!$keyword) {
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }

        // Calculate credits needed
        $scrapeCredits = count($sourceUrls) * self::CREDIT_SCRAPE_PER_URL;
        $totalCredits = $scrapeCredits + self::CREDIT_GENERATE;

        // Check credits
        if (!Credits::hasEnough($user['id'], $totalCredits)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$totalCredits} (scraping: {$scrapeCredits} + generazione: " . self::CREDIT_GENERATE . ")"
            ]);
            exit;
        }

        try {
            // Create article record
            $articleId = $this->article->create([
                'user_id' => $user['id'],
                'keyword_id' => $keywordId,
                'status' => 'generating',
                'brief_data' => [
                    'keyword' => $keyword['keyword'],
                    'language' => $keyword['language'],
                    'location' => $keyword['location'],
                    'source_urls' => $sourceUrls,
                    'requested_at' => date('Y-m-d H:i:s')
                ]
            ]);

            // Create source records
            foreach ($sourceUrls as $url) {
                $this->source->create([
                    'article_id' => $articleId,
                    'url' => $url,
                    'is_custom' => !$this->isFromSerp($keywordId, $url)
                ]);
            }

            // Consume credits upfront
            Credits::consume(
                $user['id'],
                $totalCredits,
                'article_generation',
                'ai-content',
                ['keyword' => $keyword['keyword'], 'sources_count' => count($sourceUrls)]
            );

            // Update article with credits used
            $this->article->update($articleId, [
                'credits_used' => $totalCredits
            ]);

            // Start async generation (in real implementation, this would queue a job)
            // For now, we'll handle it synchronously in a separate request
            $this->startGeneration($articleId);

            echo json_encode([
                'success' => true,
                'message' => 'Generazione avviata',
                'article_id' => $articleId,
                'credits_used' => $totalCredits,
                'redirect' => url('/ai-content/articles/' . $articleId)
            ]);

        } catch (\Exception $e) {
            // If article was created, mark as failed
            if (isset($articleId)) {
                $this->article->updateStatus($articleId, 'failed');
            }

            echo json_encode([
                'success' => false,
                'error' => 'Errore durante la creazione: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * SSE endpoint for real-time progress
     */
    public function progress(int $id): void
    {
        $user = Auth::user();

        // Verify ownership
        $article = $this->article->find($id, $user['id']);
        if (!$article) {
            http_response_code(404);
            exit;
        }

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        $lastStatus = '';
        $maxIterations = 120; // 2 minutes max
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $article = $this->article->find($id);

            if (!$article) {
                $this->sendSSE('error', ['message' => 'Articolo non trovato']);
                break;
            }

            $sourceStats = $this->source->countByArticle($id);

            $data = [
                'status' => $article['status'],
                'sources' => [
                    'total' => (int) $sourceStats['total'],
                    'success' => (int) $sourceStats['success'],
                    'pending' => (int) $sourceStats['pending'],
                    'failed' => (int) $sourceStats['failed']
                ],
                'title' => $article['title'],
                'word_count' => $article['word_count']
            ];

            // Send update if status changed or every 2 seconds
            if ($article['status'] !== $lastStatus || $iteration % 2 === 0) {
                $this->sendSSE('progress', $data);
                $lastStatus = $article['status'];
            }

            // Exit if completed or failed
            if (in_array($article['status'], ['ready', 'published', 'failed'])) {
                $this->sendSSE('complete', $data);
                break;
            }

            $iteration++;
            sleep(1);

            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }
        }

        exit;
    }

    /**
     * Update article content
     */
    public function update(int $id): void
    {
        $user = Auth::user();

        // Verify ownership
        $article = $this->article->find($id, $user['id']);
        if (!$article) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Articolo non trovato']);
                exit;
            }
            $_SESSION['_flash']['error'] = 'Articolo non trovato';
            header('Location: ' . url('/ai-content/articles'));
            exit;
        }

        // Get input
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $metaDescription = trim($_POST['meta_description'] ?? '');

        // Validation
        if (empty($title)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Il titolo è obbligatorio']);
                exit;
            }
            $_SESSION['_flash']['error'] = 'Il titolo è obbligatorio';
            header('Location: ' . url('/ai-content/articles/' . $id));
            exit;
        }

        try {
            $this->article->update($id, [
                'title' => $title,
                'content' => $content,
                'meta_description' => $metaDescription,
                'word_count' => str_word_count(strip_tags($content)),
                'updated_at' => date('Y-m-d H:i:s')
            ], $user['id']);

            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Articolo salvato']);
                exit;
            }

            $_SESSION['_flash']['success'] = 'Articolo aggiornato';

        } catch (\Exception $e) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $_SESSION['_flash']['error'] = 'Errore durante il salvataggio';
        }

        header('Location: ' . url('/ai-content/articles/' . $id));
        exit;
    }

    /**
     * Delete article
     */
    public function delete(int $id): void
    {
        $user = Auth::user();

        // Verify ownership
        $article = $this->article->find($id, $user['id']);
        if (!$article) {
            $_SESSION['_flash']['error'] = 'Articolo non trovato';
            header('Location: ' . url('/ai-content/articles'));
            exit;
        }

        // Cannot delete published articles
        if ($article['status'] === 'published') {
            $_SESSION['_flash']['error'] = 'Non puoi eliminare un articolo pubblicato';
            header('Location: ' . url('/ai-content/articles/' . $id));
            exit;
        }

        try {
            // Delete sources first (cascade should handle this, but explicit is better)
            $this->source->deleteByArticle($id);

            // Delete article
            $this->article->delete($id, $user['id']);

            $_SESSION['_flash']['success'] = 'Articolo eliminato';

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore durante l\'eliminazione';
        }

        header('Location: ' . url('/ai-content/articles'));
        exit;
    }

    /**
     * Regenerate existing article
     */
    public function regenerate(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();

        // Verify ownership
        $article = $this->article->find($id, $user['id']);
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Articolo non trovato']);
            exit;
        }

        // Cannot regenerate published articles
        if ($article['status'] === 'published') {
            echo json_encode(['success' => false, 'error' => 'Non puoi rigenerare un articolo pubblicato']);
            exit;
        }

        // Check credits
        if (!Credits::hasEnough($user['id'], self::CREDIT_GENERATE)) {
            echo json_encode([
                'success' => false,
                'error' => 'Crediti insufficienti. Richiesti: ' . self::CREDIT_GENERATE
            ]);
            exit;
        }

        try {
            // Consume credits
            Credits::consume(
                $user['id'],
                self::CREDIT_GENERATE,
                'article_regeneration',
                'ai-content',
                ['article_id' => $id]
            );

            // Update status and credits
            $this->article->update($id, [
                'status' => 'generating',
                'credits_used' => ($article['credits_used'] ?? 0) + self::CREDIT_GENERATE
            ]);

            // Start regeneration
            $this->startGeneration($id, true);

            echo json_encode([
                'success' => true,
                'message' => 'Rigenerazione avviata',
                'credits_used' => self::CREDIT_GENERATE,
                'redirect' => url('/ai-content/articles/' . $id)
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Errore durante la rigenerazione: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Check if URL is from SERP results
     */
    private function isFromSerp(int $keywordId, string $url): bool
    {
        $serpResults = \Core\Database::fetchAll(
            "SELECT url FROM aic_serp_results WHERE keyword_id = ?",
            [$keywordId]
        );

        $serpUrls = array_column($serpResults, 'url');
        return in_array($url, $serpUrls);
    }

    /**
     * Start article generation process
     * In production, this would queue a background job
     */
    private function startGeneration(int $articleId, bool $isRegeneration = false): void
    {
        // For now, this is a placeholder that marks the article for processing
        // In a real implementation, you would:
        // 1. Queue a job to ContentScraperService to scrape sources
        // 2. Queue a job to BriefBuilderService to build the brief
        // 3. Queue a job to ArticleGeneratorService to generate content

        // The actual generation will be handled by a separate worker/cron
        // or can be triggered via a webhook/API call

        // Update brief_data with generation start time
        $article = $this->article->find($articleId);
        $briefData = json_decode($article['brief_data'] ?? '{}', true);
        $briefData['generation_started_at'] = date('Y-m-d H:i:s');
        $briefData['is_regeneration'] = $isRegeneration;

        $this->article->update($articleId, [
            'brief_data' => json_encode($briefData)
        ]);
    }

    /**
     * Send SSE event
     */
    private function sendSSE(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Check if request is AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
