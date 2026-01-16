<?php

namespace Modules\AiContent\Controllers;

use Core\Auth;
use Core\Credits;
use Core\Database;
use Services\ScraperService;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\SerpResult;
use Modules\AiContent\Models\Source;
use Modules\AiContent\Services\BriefBuilderService;
use Modules\AiContent\Services\ArticleGeneratorService;

/**
 * WizardController
 *
 * Handles the wizard flow for AI Content generation
 */
class WizardController
{
    private Keyword $keyword;
    private Article $article;
    private SerpResult $serpResult;

    public function __construct()
    {
        $this->keyword = new Keyword();
        $this->article = new Article();
        $this->serpResult = new SerpResult();
    }

    /**
     * Generate brief for keyword (AJAX)
     */
    public function generateBrief(int $keywordId): void
    {
        // Cattura qualsiasi output accidentale (warning, notice)
        ob_start();

        header('Content-Type: application/json');

        // ========== DEBUG LOGGING ==========
        error_log("========================================");
        error_log("=== WIZARD GENERATE BRIEF START ===");
        error_log("Keyword ID: " . $keywordId);

        $user = Auth::user();
        error_log("User ID: " . ($user['id'] ?? 'NULL'));
        error_log("User credits: " . ($user['credits'] ?? 'NULL'));

        // Get JSON input
        $rawInput = file_get_contents('php://input');
        error_log("Raw input length: " . strlen($rawInput));
        $input = json_decode($rawInput, true) ?? [];
        error_log("Parsed input keys: " . json_encode(array_keys($input)));

        // Find keyword
        $keyword = $this->keyword->find($keywordId, $user['id']);
        if (!$keyword) {
            error_log("ERROR: Keyword not found");
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }
        error_log("Keyword found: " . $keyword['keyword']);

        // Get sources from input
        $sources = $input['sources'] ?? [];
        $customUrls = $input['customUrls'] ?? [];
        $selectedPaa = $input['paaQuestions'] ?? [];

        error_log("Sources count: " . count($sources));
        error_log("Sources data: " . json_encode($sources));
        error_log("Custom URLs: " . json_encode($customUrls));
        error_log("Selected PAA count: " . count($selectedPaa));

        // Build list of URLs to scrape
        $urlsToScrape = array_column($sources, 'url');
        $urlsToScrape = array_merge($urlsToScrape, $customUrls);

        error_log("URLs to scrape: " . json_encode($urlsToScrape));

        if (empty($urlsToScrape)) {
            error_log("ERROR: No URLs to scrape");
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una fonte da analizzare']);
            exit;
        }

        // Check credits for scraping + brief generation
        $scrapeCostPerUrl = Credits::getCost('content_scrape', 'ai-content');
        $scrapingCost = $scrapeCostPerUrl * count($urlsToScrape);
        $briefCost = Credits::getCost('brief_generation', 'ai-content');
        $totalCost = $scrapingCost + $briefCost;

        $currentBalance = Credits::getBalance($user['id']);
        error_log("=== CREDIT CHECK ===");
        error_log("User ID: " . $user['id']);
        error_log("Current balance (from Credits::getBalance): " . $currentBalance);
        error_log("Required credits: " . $totalCost);
        error_log("Has enough: " . (Credits::hasEnough($user['id'], $totalCost) ? 'YES' : 'NO'));

        if (!Credits::hasEnough($user['id'], $totalCost)) {
            error_log("ERROR: Insufficient credits");
            echo json_encode(['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$totalCost}, Disponibili: {$currentBalance}"]);
            exit;
        }

        try {
            // Scrape sources
            error_log("=== STARTING SCRAPING ===");
            $scraper = new ScraperService();
            $scrapedSources = [];

            foreach ($urlsToScrape as $index => $url) {
                error_log("Scraping [{$index}]: {$url}");
                try {
                    $scraped = $scraper->scrape($url);
                    error_log("  - Result: " . ($scraped ? 'OK' : 'EMPTY'));
                    error_log("  - Content length: " . strlen($scraped['content'] ?? ''));
                    error_log("  - Word count: " . ($scraped['word_count'] ?? 0));
                    error_log("  - Headings H2 count: " . count($scraped['headings']['h2'] ?? []));
                    if ($scraped && !empty($scraped['content'])) {
                        $scrapedSources[] = $scraped;
                        error_log("  - Added to sources");
                    } else {
                        error_log("  - SKIPPED: empty content");
                    }
                } catch (\Exception $e) {
                    error_log("  - EXCEPTION: " . $e->getMessage());
                }
            }

            error_log("=== SCRAPING COMPLETE ===");
            error_log("Scraped sources count: " . count($scrapedSources));

            if (empty($scrapedSources)) {
                error_log("ERROR: No sources scraped successfully");
                echo json_encode(['success' => false, 'error' => 'Impossibile estrarre contenuto dalle fonti selezionate']);
                exit;
            }

            // Log first scraped content preview
            if (!empty($scrapedSources[0]['content'])) {
                error_log("First source content preview: " . substr($scrapedSources[0]['content'], 0, 300));
            }

            // Consume scraping credits
            Credits::consume($user['id'], $scrapingCost, 'source_scraping', 'ai-content', ['urls_count' => count($scrapedSources)]);
            error_log("Scraping credits consumed: " . $scrapingCost);

            // Get SERP results
            $serpResults = $this->serpResult->getByKeyword($keywordId);
            error_log("SERP results count: " . count($serpResults));

            // Get PAA questions
            $paaQuestions = $this->keyword->getPaaQuestions($keywordId);
            error_log("PAA questions count: " . count($paaQuestions));

            // Build brief
            error_log("=== BUILDING BRIEF ===");
            $briefBuilder = new BriefBuilderService();
            $brief = $briefBuilder->build($keyword, $serpResults, $paaQuestions, $scrapedSources, $user['id']);
            error_log("Brief built successfully");
            error_log("Brief recommended_word_count: " . ($brief['recommended_word_count'] ?? 'N/A'));
            error_log("Brief key_entities phrases count: " . count($brief['key_entities']['phrases'] ?? []));

            // Verifica che il brief sia valido prima di salvare
            if (empty($brief) || empty($brief['recommended_word_count'])) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Brief generation failed - empty data']);
                exit;
            }
            // Consume brief credits
            Credits::consume($user['id'], $briefCost, 'brief_generation', 'ai-content', ['keyword' => $keyword['keyword']]);

            // Create or update article with brief
            $articleId = $this->article->createWithBrief($keywordId, $user['id'], $brief, $sources, $customUrls, $selectedPaa);
            error_log("Article created/updated with ID: " . $articleId);

            // Save scraped sources to aic_sources table
            $sourceModel = new Source();
            $sourceModel->deleteByArticle($articleId); // Clear old sources

            foreach ($scrapedSources as $index => $scraped) {
                $isCustom = in_array($scraped['url'], $customUrls);
                $sourceId = $sourceModel->create([
                    'article_id' => $articleId,
                    'url' => $scraped['url'],
                    'title' => $scraped['title'] ?? null,
                    'is_custom' => $isCustom
                ]);
                // Update with scraped content
                $sourceModel->updateScraped($sourceId, [
                    'content' => $scraped['content'],
                    'headings' => $scraped['headings'],
                    'word_count' => $scraped['word_count']
                ]);
            }
            error_log("Saved " . count($scrapedSources) . " sources to aic_sources");

            // Format response for wizard
            $wizardBrief = [
                'searchIntent' => $brief['search_intent']['primary'],
                'intentDescription' => $this->getIntentDescription($brief['search_intent']['primary']),
                'suggestedHeadings' => $this->formatHeadingsForWizard($brief),
                'entities' => $this->formatEntitiesForWizard($brief),
                'targetWordCount' => $brief['recommended_word_count'],
                'additionalNotes' => '',
            ];

            // Salva brief anche nella tabella aic_keywords per persistenza
            $this->keyword->saveBrief($keywordId, $wizardBrief);
            error_log("Brief saved to aic_keywords for keyword ID: " . $keywordId);

            // Pulisci buffer prima di inviare JSON
            ob_end_clean();

            echo json_encode([
                'success' => true,
                'brief' => $wizardBrief,
                'articleId' => $articleId,
                'message' => 'Brief generato con successo',
            ]);

        } catch (\Exception $e) {
            error_log("Brief generation error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Errore generazione brief: ' . $e->getMessage()]);
        }

        exit;
    }

    /**
     * Generate article from brief (AJAX)
     */
    public function generateArticle(int $keywordId): void
    {
        // Cattura qualsiasi output accidentale
        ob_start();

        header('Content-Type: application/json');

        // ========== DEBUG LOGGING ==========
        error_log("========================================");
        error_log("=== WIZARD GENERATE ARTICLE START ===");
        error_log("Keyword ID: " . $keywordId);

        $user = Auth::user();
        error_log("User ID: " . ($user['id'] ?? 'NULL'));
        error_log("User credits from session: " . ($user['credits'] ?? 'NULL'));

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $articleId = $input['articleId'] ?? null;
        $briefData = $input['briefData'] ?? [];

        error_log("Article ID: " . ($articleId ?? 'NULL'));
        error_log("Brief data keys: " . json_encode(array_keys($briefData)));

        // Find keyword
        $keyword = $this->keyword->find($keywordId, $user['id']);
        if (!$keyword) {
            error_log("ERROR: Keyword not found");
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }
        error_log("Keyword found: " . $keyword['keyword']);

        // Check credits for article generation
        $cost = Credits::getCost('article_generation', 'ai-content');
        $currentBalance = Credits::getBalance($user['id']);
        error_log("=== CREDIT CHECK ===");
        error_log("User ID: " . $user['id']);
        error_log("Current balance (from Credits::getBalance): " . $currentBalance);
        error_log("Required credits: " . $cost);
        error_log("Has enough: " . (Credits::hasEnough($user['id'], $cost) ? 'YES' : 'NO'));

        if (!Credits::hasEnough($user['id'], $cost)) {
            error_log("ERROR: Insufficient credits");
            echo json_encode(['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$cost}, Disponibili: {$currentBalance}"]);
            exit;
        }

        try {
            // Get article
            $article = $articleId ? $this->article->find($articleId, $user['id']) : null;
            if (!$article) {
                echo json_encode(['success' => false, 'error' => 'Articolo non trovato. Genera prima il brief.']);
                exit;
            }

            // Get brief from article or input
            $storedBrief = $article['brief_data'] ? json_decode($article['brief_data'], true) : null;
            $brief = $storedBrief['brief'] ?? $briefData;

            if (empty($brief)) {
                echo json_encode(['success' => false, 'error' => 'Brief non trovato. Genera prima il brief.']);
                exit;
            }

            // Add keyword info to brief if missing
            if (empty($brief['keyword'])) {
                $brief['keyword'] = $keyword['keyword'];
                $brief['language'] = $keyword['language'];
                $brief['location'] = $keyword['location'];
            }

            // Load scraped sources from database
            $sourceModel = new Source();
            $scrapedSources = $sourceModel->getScrapedByArticle($articleId);
            error_log("Loaded " . count($scrapedSources) . " scraped sources from DB");

            // Add scraped content to brief for AI generation
            $brief['scraped_sources'] = array_map(function($source) {
                return [
                    'url' => $source['url'],
                    'title' => $source['title'],
                    'content' => $source['content_extracted'],
                    'headings' => json_decode($source['headings_json'] ?? '{}', true),
                    'word_count' => $source['word_count']
                ];
            }, $scrapedSources);

            // Determine target word count
            $targetWords = $briefData['targetWordCount'] ?? $brief['recommended_word_count'] ?? 1500;

            // Generate article using AI
            error_log("=== GENERATING ARTICLE ===");
            error_log("Target words: " . $targetWords);
            error_log("Sources count: " . count($brief['scraped_sources']));

            $generator = new ArticleGeneratorService();
            $result = $generator->generate($brief, (int) $targetWords, $user['id']);

            error_log("Article generation result: " . json_encode($result));

            if (!$result['success']) {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Errore generazione articolo']);
                exit;
            }

            // Update article in database
            $this->article->updateContent($articleId, $result);

            // Consume credits
            Credits::consume($user['id'], $cost, 'article_generation', 'ai-content', ['keyword' => $keyword['keyword']]);

            // Pulisci buffer prima di inviare JSON
            ob_end_clean();

            echo json_encode([
                'success' => true,
                'article' => [
                    'title' => $result['title'],
                    'metaDescription' => $result['meta_description'],
                    'content' => $result['content'],
                    'wordCount' => $result['word_count'],
                ],
                'message' => 'Articolo generato con successo',
            ]);

        } catch (\Exception $e) {
            error_log("Article generation error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Errore generazione articolo: ' . $e->getMessage()]);
        }

        exit;
    }

    /**
     * Get intent description
     */
    private function getIntentDescription(string $intent): string
    {
        $descriptions = [
            'informational' => 'L\'utente cerca informazioni, guide o spiegazioni su questo argomento.',
            'commercial' => 'L\'utente sta valutando opzioni e confrontando prodotti/servizi.',
            'transactional' => 'L\'utente è pronto ad acquistare o completare un\'azione.',
            'navigational' => 'L\'utente cerca un sito o una pagina specifica.',
        ];

        return $descriptions[$intent] ?? 'Search intent non determinato.';
    }

    /**
     * Format headings for wizard
     */
    private function formatHeadingsForWizard(array $brief): array
    {
        $headings = [];

        // H1 - Title suggestion based on SERP
        $headings[] = ['tag' => 'H1', 'text' => $brief['keyword']];

        // H2s from recommended structure
        if (!empty($brief['recommended_structure']['sections'])) {
            foreach ($brief['recommended_structure']['sections'] as $section) {
                $headings[] = ['tag' => 'H2', 'text' => $section['suggested_h2']];
            }
        } else {
            // Default structure
            $headings[] = ['tag' => 'H2', 'text' => 'Introduzione'];
            $headings[] = ['tag' => 'H2', 'text' => 'Come funziona'];
            $headings[] = ['tag' => 'H2', 'text' => 'Vantaggi e svantaggi'];
            $headings[] = ['tag' => 'H2', 'text' => 'Conclusioni'];
        }

        return $headings;
    }

    /**
     * Format entities for wizard
     */
    private function formatEntitiesForWizard(array $brief): array
    {
        $entities = [];

        // Add phrases
        if (!empty($brief['key_entities']['phrases'])) {
            foreach (array_slice($brief['key_entities']['phrases'], 0, 10) as $phrase) {
                $entities[] = $phrase['phrase'];
            }
        }

        // Add single terms
        if (!empty($brief['key_entities']['single_terms'])) {
            foreach (array_slice($brief['key_entities']['single_terms'], 0, 5) as $term) {
                if (!in_array($term['term'], $entities)) {
                    $entities[] = $term['term'];
                }
            }
        }

        return $entities;
    }
}
