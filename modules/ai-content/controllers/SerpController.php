<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\SerpResult;
use Modules\AiContent\Services\SerpApiService;

/**
 * SerpController
 *
 * Handles SERP extraction for AI Content module
 */
class SerpController
{
    private Keyword $keyword;
    private SerpResult $serpResult;

    public function __construct()
    {
        $this->keyword = new Keyword();
        $this->serpResult = new SerpResult();
    }

    /**
     * Extract SERP results for a keyword (AJAX)
     */
    public function extract(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(60);
        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();

        // Find keyword with ownership check
        $keyword = $this->keyword->find($id);
        if (!$keyword) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }
        $project = Project::findAccessible($user['id'], $keyword['project_id']);
        if (!$project) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
            exit;
        }

        // Check credits for SERP extraction
        $cost = Credits::getCost('serp_extraction', 'ai-content');
        if (!Credits::hasEnough($user['id'], $cost)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Crediti insufficienti. Richiesti: ' . $cost]);
            exit;
        }

        try {
            // Call SerpAPI
            $serpService = new SerpApiService();
            $results = $serpService->search(
                $keyword['keyword'],
                $keyword['language'],
                $keyword['location']
            );

            // Save organic results
            $count = $this->serpResult->saveForKeyword($id, $results['organic']);

            // Save PAA questions
            $this->keyword->savePaaQuestions($id, $results['paa']);

            // Mark as extracted
            $this->keyword->markSerpExtracted($id);

            // Consume credits
            Credits::consume($user['id'], $cost, 'serp_extraction', 'ai-content', ['keyword' => $keyword['keyword']]);

            // Reload SERP results from database (with proper structure)
            $savedResults = $this->serpResult->getByKeyword($id);
            $savedPaa = $this->keyword->getPaaQuestions($id);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => "Estratti {$count} risultati SERP",
                'organic' => $savedResults,
                'paa' => $savedPaa,
                'organic_count' => $count,
                'paa_count' => count($savedPaa),
                'related_count' => count($results['related'] ?? []),
                'redirect' => url('/ai-content/keywords/' . $id . '/serp')
            ]);

        } catch (\Exception $e) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Errore estrazione SERP: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Show SERP results for a keyword
     */
    public function show(int $id): string
    {
        $user = Auth::user();

        // Get keyword with SERP data
        $keyword = $this->keyword->findWithSerp($id);

        if (!$keyword) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            header('Location: ' . url('/ai-content/keywords'));
            exit;
        }

        return View::render('ai-content/keywords/serp-results', [
            'title' => 'SERP: ' . $keyword['keyword'] . ' - AI Content',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keyword' => $keyword
        ]);
    }
}
