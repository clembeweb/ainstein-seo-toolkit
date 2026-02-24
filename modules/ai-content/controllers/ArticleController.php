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
use Modules\AiContent\Models\Project;

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
    public function index(?int $projectId = null): string
    {
        $user = Auth::user();
        $page = (int) ($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? null;
        $project = null;

        $filters = [
            'sort' => $_GET['sort'] ?? null,
            'dir' => $_GET['dir'] ?? null,
        ];

        // Se projectId specificato, verifica ownership e filtra
        if ($projectId !== null) {
            $projectModel = new Project();
            $project = $projectModel->findAccessible($projectId, $user['id']);

            if (!$project) {
                $_SESSION['_flash']['error'] = 'Progetto non trovato';
                header('Location: ' . url('/ai-content'));
                exit;
            }

            $articlesData = $this->article->allByProject($projectId, $page, 20, $status, $filters);
            $stats = $this->article->getStatsByProject($projectId);
            $title = 'Articoli - ' . $project['name'];
        } else {
            $articlesData = $this->article->allByUser($user['id'], $page, 20, $status, $filters);
            $stats = $this->article->getStats($user['id']);
            $title = 'Articoli - AI Content';
        }

        $wpSites = $this->wpSite->getActiveSites($user['id']);

        return View::render('ai-content/articles/index', [
            'title' => $title,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'articles' => $articlesData['data'],
            'pagination' => $articlesData,
            'currentStatus' => $status,
            'stats' => $stats,
            'wpSites' => $wpSites,
            'project' => $project,
            'projectId' => $projectId,
            'filters' => $filters
        ]);
    }

    /**
     * Show article detail with HTML preview
     */
    public function show(int $id, ?int $projectId = null): string
    {
        $user = Auth::user();
        $project = null;

        // Se projectId specificato, verifica ownership
        if ($projectId !== null) {
            $projectModel = new Project();
            $project = $projectModel->findAccessible($projectId, $user['id']);

            if (!$project) {
                $_SESSION['_flash']['error'] = 'Progetto non trovato';
                header('Location: ' . url('/ai-content'));
                exit;
            }
        }

        $article = $this->article->findWithRelations($id);

        if (!$article) {
            $_SESSION['_flash']['error'] = 'Articolo non trovato';
            $redirectUrl = $projectId ? url('/ai-content/projects/' . $projectId . '/articles') : url('/ai-content/articles');
            header('Location: ' . $redirectUrl);
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
            'wpSites' => $wpSites,
            'project' => $project,
            'projectId' => $projectId
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

        // Verify keyword exists
        $keyword = $this->keyword->find($keywordId);
        if (!$keyword) {
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }

        // Calculate credits needed
        $scrapeCostPerUrl = Credits::getCost('content_scrape', 'ai-content');
        $generateCost = Credits::getCost('article_generation', 'ai-content');
        $scrapeCredits = count($sourceUrls) * $scrapeCostPerUrl;
        $totalCredits = $scrapeCredits + $generateCost;

        // Check credits
        if (!Credits::hasEnough($user['id'], $totalCredits)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$totalCredits} (scraping: {$scrapeCredits} + generazione: {$generateCost})"
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
        $article = $this->article->find($id);
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
        $article = $this->article->find($id);
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
            ]);

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
        $article = $this->article->find($id);

        // Get project_id for redirect (before deletion)
        $projectId = $article['project_id'] ?? null;
        $redirectUrl = $projectId
            ? url('/ai-content/projects/' . $projectId . '/articles')
            : url('/ai-content/articles');

        if (!$article) {
            $_SESSION['_flash']['error'] = 'Articolo non trovato';
            header('Location: ' . $redirectUrl);
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
            $this->article->delete($id);

            $_SESSION['_flash']['success'] = 'Articolo eliminato';

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore durante l\'eliminazione';
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Regenerate existing article
     * Pattern: verify credits -> save original status -> set generating -> generate -> reconnect -> save -> consume credits
     * On error: rollback status
     */
    public function regenerate(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();

        // Verify ownership
        $article = $this->article->find($id);
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Articolo non trovato']);
            exit;
        }

        // Cannot regenerate published articles
        if ($article['status'] === 'published') {
            echo json_encode(['success' => false, 'error' => 'Non puoi rigenerare un articolo pubblicato']);
            exit;
        }

        // Cannot regenerate if already generating
        if ($article['status'] === 'generating') {
            echo json_encode(['success' => false, 'error' => 'Articolo già in generazione. Usa "Reset" se bloccato.']);
            exit;
        }

        // 1. Check credits BEFORE
        $generateCost = Credits::getCost('article_generation', 'ai-content');
        if (!Credits::hasEnough($user['id'], $generateCost)) {
            echo json_encode([
                'success' => false,
                'error' => 'Crediti insufficienti. Richiesti: ' . $generateCost
            ]);
            exit;
        }

        // 2. Save original status for rollback
        $originalStatus = $article['status'];

        // 3. Update status to generating (with original status saved in brief_data)
        $briefData = json_decode($article['brief_data'] ?? '{}', true);
        $briefData['original_status'] = $originalStatus;
        $briefData['generation_started_at'] = date('Y-m-d H:i:s');

        $this->article->update($id, [
            'status' => 'generating',
            'brief_data' => json_encode($briefData)
        ]);

        try {
            // 4. Build brief and generate
            $generatorService = new \Modules\AiContent\Services\ArticleGeneratorService();

            $result = $generatorService->generate($briefData['brief'] ?? $briefData, 1500, $user['id']);

            // 5. IMPORTANT: reconnect after long AI operation
            \Core\Database::reconnect();

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Generazione fallita');
            }

            // 6. Save content and update status to ready
            $this->article->update($id, [
                'title' => $result['title'],
                'content' => $result['content'],
                'meta_description' => $result['meta_description'],
                'word_count' => $result['word_count'],
                'status' => 'ready',
                'ai_model' => $result['model_used'] ?? null,
                'generation_time_ms' => $result['generation_time_ms'] ?? null,
                'credits_used' => ($article['credits_used'] ?? 0) + $generateCost,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 7. Consume credits ONLY on success
            Credits::consume(
                $user['id'],
                $generateCost,
                'article_regeneration',
                'ai-content',
                ['article_id' => $id]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Articolo rigenerato con successo',
                'credits_used' => $generateCost,
                'redirect' => url('/ai-content/articles/' . $id)
            ]);

        } catch (\Exception $e) {
            // 8. ROLLBACK: reconnect and restore status
            \Core\Database::reconnect();

            $briefData['error'] = $e->getMessage();
            $briefData['failed_at'] = date('Y-m-d H:i:s');

            $this->article->update($id, [
                'status' => $originalStatus,
                'brief_data' => json_encode($briefData)
            ]);

            echo json_encode([
                'success' => false,
                'error' => 'Rigenerazione fallita: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Reset article status (for stuck "generating" articles)
     * Only allowed if article has been generating for more than 5 minutes
     */
    public function resetStatus(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();

        // Verify ownership
        $article = $this->article->find($id);
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Articolo non trovato']);
            exit;
        }

        // Only reset if status is "generating"
        if ($article['status'] !== 'generating') {
            echo json_encode(['success' => false, 'error' => 'L\'articolo non è in stato di generazione']);
            exit;
        }

        // Check if generating for more than 5 minutes (optional safety)
        $briefData = json_decode($article['brief_data'] ?? '{}', true);
        $startedAt = $briefData['generation_started_at'] ?? null;

        if ($startedAt) {
            $startedTime = strtotime($startedAt);
            $elapsedMinutes = (time() - $startedTime) / 60;

            if ($elapsedMinutes < 5) {
                $remainingMinutes = ceil(5 - $elapsedMinutes);
                echo json_encode([
                    'success' => false,
                    'error' => "Attendi ancora {$remainingMinutes} minuti prima di resettare. La generazione potrebbe essere ancora in corso."
                ]);
                exit;
            }
        }

        try {
            // Get original status or default to 'draft'
            $originalStatus = $briefData['original_status'] ?? 'draft';

            // Don't restore to 'generating' or 'published'
            if (in_array($originalStatus, ['generating', 'published'])) {
                $originalStatus = 'draft';
            }

            // Update brief_data with reset info
            $briefData['reset_at'] = date('Y-m-d H:i:s');
            $briefData['reset_from_status'] = 'generating';
            unset($briefData['generation_started_at']);
            unset($briefData['original_status']);

            $this->article->update($id, [
                'status' => $originalStatus,
                'brief_data' => json_encode($briefData)
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Status resettato a "' . $originalStatus . '"',
                'new_status' => $originalStatus
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Errore durante il reset: ' . $e->getMessage()
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
     * Genera o rigenera immagine di copertina per un articolo
     */
    public function regenerateCover(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();

        $article = $this->article->findWithRelations($id);
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Articolo non trovato']);
            exit;
        }

        if (empty($article['content'])) {
            echo json_encode(['success' => false, 'error' => 'L\'articolo non ha contenuto. Genera prima l\'articolo.']);
            exit;
        }

        // Check credits
        $coverCost = Credits::getCost('cover_image_generation', 'ai-content');
        if (!Credits::hasEnough($user['id'], $coverCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Richiesti: {$coverCost}"
            ]);
            exit;
        }

        try {
            // Se esiste già una cover, elimina il file
            if (!empty($article['cover_image_path'])) {
                $coverService = new \Modules\AiContent\Services\CoverImageService();
                $coverService->deleteImage($article['cover_image_path']);
            }

            $coverService = $coverService ?? new \Modules\AiContent\Services\CoverImageService();
            $coverResult = $coverService->generate(
                $id,
                $article['title'],
                $article['keyword'] ?? '',
                mb_substr(strip_tags($article['content']), 0, 500),
                $user['id']
            );

            \Core\Database::reconnect();

            if (!$coverResult['success']) {
                echo json_encode([
                    'success' => false,
                    'error' => $coverResult['error'] ?? 'Errore generazione immagine'
                ]);
                exit;
            }

            $this->article->updateCoverImage($id, $coverResult['path']);

            Credits::consume($user['id'], $coverCost, 'cover_image_generation', 'ai-content', [
                'article_id' => $id
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Immagine di copertina generata',
                'cover_path' => $coverResult['path'],
                'cover_url' => url('/ai-content/cover/' . $id)
            ]);

        } catch (\Exception $e) {
            \Core\Database::reconnect();
            echo json_encode([
                'success' => false,
                'error' => 'Errore: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Rimuovi immagine di copertina
     */
    public function removeCover(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();

        $article = $this->article->find($id);
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Articolo non trovato']);
            exit;
        }

        if (!empty($article['cover_image_path'])) {
            $coverService = new \Modules\AiContent\Services\CoverImageService();
            $coverService->deleteImage($article['cover_image_path']);
        }

        $this->article->removeCoverImage($id);

        echo json_encode([
            'success' => true,
            'message' => 'Immagine di copertina rimossa'
        ]);

        exit;
    }

    /**
     * Serve immagine di copertina
     */
    public function serveCover(int $id): void
    {
        $user = Auth::user();

        $article = $this->article->find($id);
        if (!$article || empty($article['cover_image_path'])) {
            http_response_code(404);
            exit;
        }

        $fullPath = \ROOT_PATH . '/' . $article['cover_image_path'];

        if (!file_exists($fullPath)) {
            http_response_code(404);
            exit;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];

        $contentType = $mimeTypes[$extension] ?? 'image/png';

        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=86400');

        readfile($fullPath);
        exit;
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
