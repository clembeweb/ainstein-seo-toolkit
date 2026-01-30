<?php

namespace Modules\AiOptimizer\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;
use Core\Credits;
use Modules\AiOptimizer\Models\Project;
use Modules\AiOptimizer\Models\Optimization;
use Modules\AiOptimizer\Services\ArticleAnalyzerService;
use Modules\AiOptimizer\Services\ContentRefactorService;

/**
 * Controller per gestione ottimizzazioni (wizard 4 step)
 */
class OptimizationController
{
    private Project $projectModel;
    private Optimization $optimizationModel;
    private ArticleAnalyzerService $analyzerService;
    private ContentRefactorService $refactorService;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->optimizationModel = new Optimization();
        $this->analyzerService = new ArticleAnalyzerService();
        $this->refactorService = new ContentRefactorService();
    }

    /**
     * Step 1: Form import articolo
     */
    public function import(int $projectId)
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        return View::render('ai-optimizer::optimize/step1-import', [
            'title' => 'Importa Articolo - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'projectId' => $projectId,
            'isConfigured' => $this->analyzerService->isConfigured(),
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Step 1: Salva import e scrape pagina
     */
    public function storeImport(int $projectId): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        $url = trim($_POST['url'] ?? '');
        $keyword = trim($_POST['keyword'] ?? '');

        if (empty($url) || empty($keyword)) {
            $_SESSION['_flash']['error'] = 'URL e keyword sono obbligatori';
            header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize'));
            exit;
        }

        // Valida URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $_SESSION['_flash']['error'] = 'URL non valido';
            header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize'));
            exit;
        }

        // Scrape pagina per ottenere contenuto originale
        $scraper = new \Services\ScraperService();
        try {
            $scraped = $scraper->scrape($url);
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Impossibile scaricare la pagina: ' . $e->getMessage();
            header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize'));
            exit;
        }

        // Estrai H1
        $h1 = '';
        if (!empty($scraped['headings']['h1'])) {
            $h1 = is_array($scraped['headings']['h1'])
                ? implode(' | ', $scraped['headings']['h1'])
                : $scraped['headings']['h1'];
        }

        // Crea ottimizzazione
        $optimizationId = $this->optimizationModel->create([
            'user_id' => $user['id'],
            'project_id' => $projectId,
            'original_url' => $url,
            'keyword' => $keyword,
            'original_title' => $scraped['title'] ?? '',
            'original_meta_description' => $scraped['description'] ?? '',
            'original_h1' => $h1,
            'original_content' => $scraped['content'] ?? '',
            'original_word_count' => $scraped['word_count'] ?? 0,
            'original_headings' => $scraped['headings'] ?? [],
        ]);

        $_SESSION['_flash']['success'] = 'Articolo importato. Procedi con l\'analisi.';
        header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize/' . $optimizationId . '/analyze'));
        exit;
    }

    /**
     * Step 2: Pagina analisi gap
     */
    public function analyze(int $projectId, int $id)
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);
        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$project || !$optimization) {
            $_SESSION['_flash']['error'] = 'Ottimizzazione non trovata';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        $creditCost = $this->analyzerService->getCreditCost();
        $userCredits = Credits::getBalance($user['id']);

        return View::render('ai-optimizer::optimize/step2-analyze', [
            'title' => 'Analisi Gap - ' . $optimization['keyword'],
            'user' => $user,
            'project' => $project,
            'projectId' => $projectId,
            'optimization' => $optimization,
            'creditCost' => $creditCost,
            'userCredits' => $userCredits,
            'isConfigured' => $this->analyzerService->isConfigured(),
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Step 2: Esegui analisi gap (AJAX)
     */
    public function runAnalysis(int $projectId, int $id): void
    {
        Middleware::auth();
        header('Content-Type: application/json');

        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);
        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$project || !$optimization) {
            echo json_encode(['error' => true, 'message' => 'Ottimizzazione non trovata']);
            exit;
        }

        // Aggiorna stato
        $this->optimizationModel->updateStatus($id, 'analyzing');

        // Esegui analisi
        $result = $this->analyzerService->analyze(
            $user['id'],
            $optimization['original_url'],
            $optimization['keyword'],
            [
                'location_code' => $project['location_code'] ?? 'IT',
                'language' => $project['language'] ?? 'it',
            ]
        );

        if (isset($result['error'])) {
            $this->optimizationModel->updateStatus($id, 'failed', $result['message']);
            echo json_encode($result);
            exit;
        }

        // Salva risultati analisi
        $this->optimizationModel->updateAnalysis(
            $id,
            $result['data'],
            $result['competitors'],
            $result['seo_score']
        );

        // Aggiorna crediti usati
        $this->optimizationModel->addCreditsUsed($id, $result['credits_used'] ?? 0);

        echo json_encode([
            'success' => true,
            'data' => $result['data'],
            'seo_score' => $result['seo_score'],
            'competitors' => $result['competitors'],
            'redirect' => url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/refactor'),
        ]);
        exit;
    }

    /**
     * Step 3: Pagina riscrittura
     */
    public function refactor(int $projectId, int $id)
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);
        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$project || !$optimization) {
            $_SESSION['_flash']['error'] = 'Ottimizzazione non trovata';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        // Deve essere almeno analizzato
        if (!in_array($optimization['status'], ['analyzed', 'refactored'])) {
            $_SESSION['_flash']['error'] = 'Devi prima completare l\'analisi';
            header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/analyze'));
            exit;
        }

        $creditCost = $this->refactorService->getCreditCost();
        $userCredits = Credits::getBalance($user['id']);

        return View::render('ai-optimizer::optimize/step3-refactor', [
            'title' => 'Riscrittura - ' . $optimization['keyword'],
            'user' => $user,
            'project' => $project,
            'projectId' => $projectId,
            'optimization' => $optimization,
            'creditCost' => $creditCost,
            'userCredits' => $userCredits,
            'isConfigured' => $this->refactorService->isConfigured(),
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Step 3: Esegui riscrittura (AJAX)
     */
    public function runRefactor(int $projectId, int $id): void
    {
        Middleware::auth();
        header('Content-Type: application/json');

        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);
        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$project || !$optimization) {
            echo json_encode(['error' => true, 'message' => 'Ottimizzazione non trovata']);
            exit;
        }

        if (empty($optimization['analysis_data'])) {
            echo json_encode(['error' => true, 'message' => 'Analisi non completata']);
            exit;
        }

        // Aggiorna stato
        $this->optimizationModel->updateStatus($id, 'refactoring');

        // Prepara dati articolo originale
        $originalArticle = [
            'title' => $optimization['original_title'],
            'description' => $optimization['original_meta_description'],
            'h1' => $optimization['original_h1'],
            'content' => $optimization['original_content'],
            'word_count' => $optimization['original_word_count'],
            'headings' => $optimization['original_headings'],
        ];

        // Opzioni dalla richiesta
        $options = [
            'target_word_count' => (int)($_POST['target_word_count'] ?? 0) ?: null,
            'tone' => $_POST['tone'] ?? 'professionale',
            'keep_original_structure' => ($_POST['keep_structure'] ?? '0') === '1',
        ];

        // Esegui riscrittura
        $result = $this->refactorService->refactor(
            $user['id'],
            $originalArticle,
            $optimization['analysis_data'],
            $optimization['keyword'],
            $options
        );

        if (isset($result['error'])) {
            $this->optimizationModel->updateStatus($id, 'analyzed', $result['message']);
            echo json_encode($result);
            exit;
        }

        // Salva contenuto ottimizzato
        $this->optimizationModel->updateOptimized($id, $result['data']);

        // Aggiorna crediti usati
        $this->optimizationModel->addCreditsUsed($id, $result['credits_used'] ?? 0);

        echo json_encode([
            'success' => true,
            'data' => $result['data'],
            'redirect' => url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/export'),
        ]);
        exit;
    }

    /**
     * Step 4: Pagina export/risultato
     */
    public function export(int $projectId, int $id)
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);
        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$project || !$optimization) {
            $_SESSION['_flash']['error'] = 'Ottimizzazione non trovata';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        return View::render('ai-optimizer::optimize/step4-export', [
            'title' => 'Export - ' . $optimization['keyword'],
            'user' => $user,
            'project' => $project,
            'projectId' => $projectId,
            'optimization' => $optimization,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Visualizza dettaglio ottimizzazione
     */
    public function show(int $projectId, int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $project = $this->projectModel->find($projectId, $user['id']);
        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$project || !$optimization) {
            $_SESSION['_flash']['error'] = 'Ottimizzazione non trovata';
            header('Location: ' . url('/ai-optimizer'));
            exit;
        }

        // Redirect allo step appropriato in base allo stato
        switch ($optimization['status']) {
            case 'imported':
                header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/analyze'));
                break;
            case 'analyzing':
            case 'analyzed':
                header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/refactor'));
                break;
            case 'refactoring':
            case 'refactored':
            case 'exported':
                header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/export'));
                break;
            default:
                header('Location: ' . url('/ai-optimizer/project/' . $projectId));
        }
        exit;
    }

    /**
     * Elimina ottimizzazione
     */
    public function delete(int $projectId, int $id): void
    {
        Middleware::auth();
        Middleware::csrf();
        $user = Auth::user();

        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$optimization) {
            $_SESSION['_flash']['error'] = 'Ottimizzazione non trovata';
            header('Location: ' . url('/ai-optimizer/project/' . $projectId));
            exit;
        }

        $this->optimizationModel->delete($id, $user['id']);

        $_SESSION['_flash']['success'] = 'Ottimizzazione eliminata';
        header('Location: ' . url('/ai-optimizer/project/' . $projectId));
        exit;
    }

    /**
     * Download contenuto come HTML
     */
    public function downloadHtml(int $projectId, int $id): void
    {
        Middleware::auth();
        $user = Auth::user();

        $optimization = $this->optimizationModel->find($id, $user['id']);

        if (!$optimization || empty($optimization['optimized_content'])) {
            $_SESSION['_flash']['error'] = 'Contenuto non disponibile';
            header('Location: ' . url('/ai-optimizer/project/' . $projectId . '/optimize/' . $id . '/export'));
            exit;
        }

        $filename = 'articolo-ottimizzato-' . date('Y-m-d') . '.html';
        $html = $this->buildHtmlExport($optimization);

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($html));

        echo $html;
        exit;
    }

    /**
     * Costruisci HTML per export
     */
    private function buildHtmlExport(array $optimization): string
    {
        $title = htmlspecialchars($optimization['optimized_title'] ?? '');
        $meta = htmlspecialchars($optimization['optimized_meta_description'] ?? '');
        $h1 = htmlspecialchars($optimization['optimized_h1'] ?? $title);
        $content = $optimization['optimized_content'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$meta}">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; line-height: 1.6; }
        h1 { color: #1a202c; margin-bottom: 1.5rem; }
        h2 { color: #2d3748; margin-top: 2rem; }
        h3 { color: #4a5568; }
        p { margin-bottom: 1rem; }
        ul, ol { margin-bottom: 1rem; padding-left: 1.5rem; }
    </style>
</head>
<body>
    <h1>{$h1}</h1>
    {$content}
</body>
</html>
HTML;
    }
}
