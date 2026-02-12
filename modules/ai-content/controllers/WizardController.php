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
        // Prevent proxy/PHP timeout killing the script mid-execution
        // Scraping 3-5 URLs (30s each) + AI call (120s) can take 270s+
        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();

        // Get JSON input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? [];

        // Find keyword
        $keyword = $this->keyword->find($keywordId, $user['id']);
        if (!$keyword) {
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }

        // Get sources from input
        $sources = $input['sources'] ?? [];
        $customUrls = $input['customUrls'] ?? [];
        $selectedPaa = $input['paaQuestions'] ?? [];

        // Build list of URLs to scrape
        $urlsToScrape = array_column($sources, 'url');
        $urlsToScrape = array_merge($urlsToScrape, $customUrls);

        if (empty($urlsToScrape)) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno una fonte da analizzare']);
            exit;
        }

        // Check credits for scraping + brief generation
        $scrapeCostPerUrl = Credits::getCost('content_scrape', 'ai-content');
        $scrapingCost = $scrapeCostPerUrl * count($urlsToScrape);
        $briefCost = Credits::getCost('brief_generation', 'ai-content');
        $totalCost = $scrapingCost + $briefCost;

        $currentBalance = Credits::getBalance($user['id']);

        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode(['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$totalCost}, Disponibili: {$currentBalance}"]);
            exit;
        }

        try {
            // Scrape sources
            $scraper = new ScraperService();
            $scrapedSources = [];

            foreach ($urlsToScrape as $url) {
                try {
                    $scraped = $scraper->scrape($url);
                    if ($scraped && !empty($scraped['content'])) {
                        $scrapedSources[] = $scraped;
                    }
                } catch (\Exception $e) {
                    // Skip failed URLs silently
                }
            }

            if (empty($scrapedSources)) {
                echo json_encode(['success' => false, 'error' => 'Impossibile estrarre contenuto dalle fonti selezionate']);
                exit;
            }

            // Consume scraping credits
            Credits::consume($user['id'], $scrapingCost, 'source_scraping', 'ai-content', ['urls_count' => count($scrapedSources)]);

            // Get SERP results
            $serpResults = $this->serpResult->getByKeyword($keywordId);

            // Get PAA questions
            $paaQuestions = $this->keyword->getPaaQuestions($keywordId);

            // Build brief
            $briefBuilder = new BriefBuilderService();
            $brief = $briefBuilder->build($keyword, $serpResults, $paaQuestions, $scrapedSources, $user['id']);

            // Reconnect DB after long AI operation (prevents "MySQL server has gone away")
            Database::reconnect();

            // Verify brief is valid
            if (empty($brief) || empty($brief['recommended_word_count'])) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Brief generation failed - empty data']);
                exit;
            }

            // Consume brief credits
            Credits::consume($user['id'], $briefCost, 'brief_generation', 'ai-content', ['keyword' => $keyword['keyword']]);

            // Create or update article with brief
            $articleId = $this->article->createWithBrief($keywordId, $user['id'], $brief, $sources, $customUrls, $selectedPaa);

            // Save scraped sources to aic_sources table
            $sourceModel = new Source();
            $sourceModel->deleteByArticle($articleId); // Clear old sources

            foreach ($scrapedSources as $scraped) {
                $isCustom = in_array($scraped['url'], $customUrls);
                $sourceId = $sourceModel->create([
                    'article_id' => $articleId,
                    'url' => $scraped['url'],
                    'title' => $scraped['title'] ?? null,
                    'is_custom' => $isCustom
                ]);
                $sourceModel->updateScraped($sourceId, [
                    'content' => $scraped['content'],
                    'headings' => $scraped['headings'],
                    'word_count' => $scraped['word_count']
                ]);
            }

            // Format response for wizard
            $wizardBrief = [
                'searchIntent' => $brief['search_intent']['primary'],
                'intentDescription' => $this->getIntentDescription($brief['search_intent']['primary']),
                'suggestedHeadings' => $this->formatHeadingsForWizard($brief),
                'entities' => $this->formatEntitiesForWizard($brief),
                'targetWordCount' => $brief['recommended_word_count'],
                'additionalNotes' => '',
                'aiAnalysis' => $this->formatAiAnalysisForWizard($brief),
            ];

            // Save brief to aic_keywords for persistence
            $this->keyword->saveBrief($keywordId, $wizardBrief);

            ob_end_clean();

            echo json_encode([
                'success' => true,
                'brief' => $wizardBrief,
                'articleId' => $articleId,
                'message' => 'Brief generato con successo',
            ]);

        } catch (\Exception $e) {
            error_log("WizardController::generateBrief error: " . $e->getMessage());
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
        // Prevent proxy/PHP timeout killing the script mid-execution
        // AI generation + cover image can take 300s+
        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();
        header('Content-Type: application/json');

        $user = Auth::user();

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $articleId = $input['articleId'] ?? null;
        $briefData = $input['briefData'] ?? [];

        // Find keyword
        $keyword = $this->keyword->find($keywordId, $user['id']);
        if (!$keyword) {
            echo json_encode(['success' => false, 'error' => 'Keyword non trovata']);
            exit;
        }

        // Check credits for article generation
        $cost = Credits::getCost('article_generation', 'ai-content');
        $currentBalance = Credits::getBalance($user['id']);

        if (!Credits::hasEnough($user['id'], $cost)) {
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

            // Inject user overrides from wizard (headings, entities, notes)
            if (!empty($briefData['suggestedHeadings'])) {
                $brief['user_headings'] = $briefData['suggestedHeadings'];
            }
            if (!empty($briefData['entities'])) {
                $brief['user_entities'] = $briefData['entities'];
            }
            if (!empty($briefData['additionalNotes'])) {
                $brief['additional_notes'] = $briefData['additionalNotes'];
            }

            // Load scraped sources from database
            $sourceModel = new Source();
            $scrapedSources = $sourceModel->getScrapedByArticle($articleId);

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

            // Add internal links pool if project has them
            if (!empty($keyword['project_id'])) {
                $internalLinksPool = new \Modules\AiContent\Models\InternalLinksPool();
                $internalLinks = $internalLinksPool->getActiveByProject($keyword['project_id'], 50);
                if (!empty($internalLinks)) {
                    $brief['internal_links_pool'] = $internalLinks;
                }
            }

            // Determine target word count
            $targetWords = $briefData['targetWordCount'] ?? $brief['recommended_word_count'] ?? 1500;

            // Generate article using AI
            $generator = new ArticleGeneratorService();
            $result = $generator->generate($brief, (int) $targetWords, $user['id']);

            // Reconnect DB after long AI operation (prevents "MySQL server has gone away")
            Database::reconnect();

            if (!$result['success']) {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Errore generazione articolo']);
                exit;
            }

            // Update article in database
            $this->article->updateContent($articleId, $result);

            // Consume credits
            Credits::consume($user['id'], $cost, 'article_generation', 'ai-content', ['keyword' => $keyword['keyword']]);

            // Generate cover image (optional)
            $coverPath = null;
            $generateCover = (bool) ($input['generateCover'] ?? ($briefData['generateCover'] ?? true));

            if ($generateCover) {
                try {
                    $coverCost = Credits::getCost('cover_image_generation', 'ai-content');

                    if (Credits::hasEnough($user['id'], $coverCost)) {
                        $coverService = new \Modules\AiContent\Services\CoverImageService();
                        $coverResult = $coverService->generate(
                            $articleId,
                            $result['title'],
                            $keyword['keyword'],
                            mb_substr(strip_tags($result['content']), 0, 500),
                            $user['id']
                        );

                        Database::reconnect();

                        if ($coverResult['success']) {
                            $this->article->updateCoverImage($articleId, $coverResult['path']);
                            $coverPath = $coverResult['path'];

                            Credits::consume($user['id'], $coverCost, 'cover_image_generation', 'ai-content', [
                                'keyword' => $keyword['keyword'],
                                'article_id' => $articleId
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // Non blocca: l'immagine è opzionale
                    Database::reconnect();
                }
            }

            ob_end_clean();

            echo json_encode([
                'success' => true,
                'article' => [
                    'title' => $result['title'],
                    'metaDescription' => $result['meta_description'],
                    'content' => $result['content'],
                    'wordCount' => $result['word_count'],
                    'coverImagePath' => $coverPath,
                ],
                'message' => 'Articolo generato con successo',
            ]);

        } catch (\Exception $e) {
            error_log("WizardController::generateArticle error: " . $e->getMessage());
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
        $aiAnalysis = $brief['ai_strategic_analysis'] ?? [];
        $hasAi = !empty($aiAnalysis['enabled']) && !empty($aiAnalysis['analysis']);

        // H1 - AI winning title se disponibile, altrimenti keyword
        if ($hasAi && !empty($aiAnalysis['analysis']['winning_title_suggestions'])) {
            $headings[] = ['tag' => 'H1', 'text' => $aiAnalysis['analysis']['winning_title_suggestions'][0]];
        } else {
            $headings[] = ['tag' => 'H1', 'text' => $brief['keyword']];
        }

        // H2 - AI recommended structure se disponibile, altrimenti competitor frequency
        if ($hasAi && !empty($aiAnalysis['analysis']['recommended_h2_structure'])) {
            foreach ($aiAnalysis['analysis']['recommended_h2_structure'] as $h2) {
                $headings[] = ['tag' => 'H2', 'text' => $h2];
            }
        } elseif (!empty($brief['recommended_structure']['sections'])) {
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

    /**
     * Format AI strategic analysis for wizard display
     */
    private function formatAiAnalysisForWizard(array $brief): ?array
    {
        $aiAnalysis = $brief['ai_strategic_analysis'] ?? [];

        if (empty($aiAnalysis['enabled']) || empty($aiAnalysis['analysis'])) {
            return null;
        }

        $analysis = $aiAnalysis['analysis'];

        return [
            'contentGaps' => $analysis['content_gaps'] ?? [],
            'uniqueAngles' => $analysis['unique_angles'] ?? [],
            'winningTitles' => $analysis['winning_title_suggestions'] ?? [],
            'keyDifferentiators' => $analysis['key_differentiators'] ?? [],
            'contentStrategy' => $analysis['content_strategy'] ?? '',
        ];
    }
}
