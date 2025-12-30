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
        // DEBUG LOGGING
        error_log("=== SERP EXTRACT CALLED ===");
        error_log("ID: " . $id);
        error_log("POST: " . json_encode($_POST));
        error_log("RAW INPUT: " . file_get_contents('php://input'));
        error_log("SESSION user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET'));
        error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NOT SET'));

        header('Content-Type: application/json');

        $user = Auth::user();

        // Find keyword
        $keyword = $this->keyword->find($id, $user['id']);

        if (!$keyword) {
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }

        // Check credits (3 credits for SERP extraction)
        $cost = 3;
        if (!Credits::hasEnough($user['id'], $cost)) {
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
        $keyword = $this->keyword->findWithSerp($id, $user['id']);

        if (!$keyword) {
            $_SESSION['flash_error'] = 'Keyword non trovata';
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
