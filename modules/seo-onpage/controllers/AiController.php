<?php

namespace Modules\SeoOnpage\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Core\Credits;
use Modules\SeoOnpage\Models\Project;
use Modules\SeoOnpage\Models\Page;
use Modules\SeoOnpage\Models\Analysis;
use Modules\SeoOnpage\Models\Issue;
use Modules\SeoOnpage\Models\AiSuggestion;
use Modules\SeoOnpage\Services\AiSuggestionService;

/**
 * AiController
 * Gestisce suggerimenti AI per ottimizzazione SEO
 */
class AiController
{
    private Project $project;
    private Page $page;
    private Analysis $analysis;
    private Issue $issue;
    private AiSuggestion $suggestion;

    public function __construct()
    {
        $this->project = new Project();
        $this->page = new Page();
        $this->analysis = new Analysis();
        $this->issue = new Issue();
        $this->suggestion = new AiSuggestion();
    }

    /**
     * Lista suggerimenti AI
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-onpage');
            exit;
        }

        // Filtri
        $filters = [
            'status' => $_GET['status'] ?? 'pending',
            'priority' => $_GET['priority'] ?? null,
        ];

        // Page filter
        $pageId = isset($_GET['page_id']) ? (int) $_GET['page_id'] : null;
        $selectedPage = null;

        if ($pageId) {
            $selectedPage = $this->page->find($pageId, $projectId);
            $suggestions = $this->suggestion->allByPage($pageId, $filters);
        } else {
            $suggestions = $this->suggestion->allByProject($projectId, $filters);
        }

        // Pages con suggerimenti pendenti
        $pagesWithSuggestions = $this->getPagesWithPendingSuggestions($projectId);

        // Costo suggerimenti
        $aiCost = Credits::getCost('ai_suggestions', 'seo-onpage');
        $userCredits = Credits::getBalance($user['id']);

        // Statistiche
        $stats = [
            'pending' => $this->suggestion->countByProject($projectId, 'pending'),
            'applied' => $this->suggestion->countByProject($projectId, 'applied'),
            'rejected' => $this->suggestion->countByProject($projectId, 'rejected'),
        ];

        return View::render('seo-onpage/ai/suggestions', [
            'title' => 'Suggerimenti AI - ' . $project['name'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'suggestions' => $suggestions,
            'selectedPage' => $selectedPage,
            'pagesWithSuggestions' => $pagesWithSuggestions,
            'filters' => $filters,
            'stats' => $stats,
            'aiCost' => $aiCost,
            'userCredits' => $userCredits,
        ]);
    }

    /**
     * Genera suggerimenti AI per pagina
     */
    public function generate(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $pageId = (int) ($_POST['page_id'] ?? 0);
        if (!$pageId) {
            echo json_encode(['success' => false, 'error' => 'Page ID mancante']);
            return;
        }

        $page = $this->page->find($pageId, $projectId);
        if (!$page) {
            echo json_encode(['success' => false, 'error' => 'Pagina non trovata']);
            return;
        }

        // Get latest analysis
        $latestAnalysis = $this->analysis->getLatestForPage($pageId);
        if (!$latestAnalysis) {
            echo json_encode(['success' => false, 'error' => 'Pagina non ancora analizzata. Esegui prima un audit.']);
            return;
        }

        // Get issues
        $issues = $this->issue->allByAnalysis($latestAnalysis['id']);

        // Generate suggestions
        $aiService = new AiSuggestionService();
        $result = $aiService->generateForPage(
            $user['id'],
            $projectId,
            $pageId,
            $page,
            $latestAnalysis,
            $issues
        );

        echo json_encode($result);
    }

    /**
     * Marca suggerimento come applicato
     */
    public function apply(int $projectId, int $suggestionId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $suggestion = $this->suggestion->find($suggestionId);
        if (!$suggestion) {
            echo json_encode(['success' => false, 'error' => 'Suggerimento non trovato']);
            return;
        }

        $this->suggestion->markApplied($suggestionId);

        echo json_encode(['success' => true, 'message' => 'Suggerimento marcato come applicato']);
    }

    /**
     * Rifiuta suggerimento
     */
    public function reject(int $projectId, int $suggestionId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $suggestion = $this->suggestion->find($suggestionId);
        if (!$suggestion) {
            echo json_encode(['success' => false, 'error' => 'Suggerimento non trovato']);
            return;
        }

        $this->suggestion->markRejected($suggestionId);

        echo json_encode(['success' => true, 'message' => 'Suggerimento rifiutato']);
    }

    /**
     * Get AI cost for suggestions
     */
    public function getCost(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        $cost = Credits::getCost('ai_suggestions', 'seo-onpage');
        $balance = Credits::getBalance($user['id']);

        echo json_encode([
            'success' => true,
            'cost' => $cost,
            'balance' => $balance,
            'can_afford' => $balance >= $cost,
        ]);
    }

    /**
     * Get pages with pending suggestions
     */
    private function getPagesWithPendingSuggestions(int $projectId): array
    {
        $sql = "SELECT p.id, p.url, p.title, p.onpage_score,
                       COUNT(s.id) as suggestions_count
                FROM sop_pages p
                JOIN sop_ai_suggestions s ON s.page_id = p.id
                WHERE p.project_id = ? AND s.status = 'pending'
                GROUP BY p.id
                ORDER BY suggestions_count DESC";

        return \Core\Database::fetchAll($sql, [$projectId]);
    }
}
