<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\SerpResult;
use Modules\AiContent\Models\Source;
use Modules\AiContent\Models\WpSite;

/**
 * KeywordController
 *
 * Handles keyword management for AI Content module
 */
class KeywordController
{
    private Keyword $keyword;
    private Article $article;
    private SerpResult $serpResult;
    private Source $source;
    private WpSite $wpSite;

    public function __construct()
    {
        $this->keyword = new Keyword();
        $this->article = new Article();
        $this->serpResult = new SerpResult();
        $this->source = new Source();
        $this->wpSite = new WpSite();
    }

    /**
     * Display wizard for keyword article generation
     */
    public function wizard(int $id): string
    {
        $user = Auth::user();

        // Load keyword with ownership check
        $keyword = $this->keyword->find($id, $user['id']);

        if (!$keyword) {
            $_SESSION['flash_error'] = 'Keyword non trovata';
            header('Location: ' . url('/ai-content/keywords'));
            exit;
        }

        // Load SERP results
        $serpResults = $this->serpResult->getByKeyword($id);
        $serpExtracted = !empty($serpResults);

        // Load PAA questions
        $paaQuestions = $this->keyword->getPaaQuestions($id);

        // Load article if exists
        $articles = $this->article->getByKeyword($id);
        $article = !empty($articles) ? $articles[0] : null;

        // Load sources if article exists
        $sources = [];
        $selectedSources = [];
        $selectedPaa = [];
        $customUrls = [];
        $briefData = null;
        $articleData = null;

        if ($article) {
            $sources = $this->source->getByArticle($article['id']);

            // Parse selected sources from SERP
            foreach ($sources as $src) {
                if (!$src['is_custom']) {
                    $selectedSources[] = [
                        'url' => $src['url'],
                        'title' => $src['title'],
                        'position' => 0
                    ];
                } else {
                    $customUrls[] = $src['url'];
                }
            }

            // Parse brief data
            if ($article['brief_data']) {
                $briefData = json_decode($article['brief_data'], true);
                $selectedPaa = $briefData['paaQuestions'] ?? [];
            }

            // Parse article data if generated
            if ($article['status'] !== 'draft' && $article['content']) {
                $articleData = [
                    'title' => $article['title'],
                    'metaDescription' => $article['meta_description'],
                    'content' => $article['content'],
                    'wordCount' => $article['word_count'] ?? 0
                ];
            }
        }

        // Calculate current step based on data
        $currentStep = $this->calculateWizardStep($serpExtracted, $sources, $article);

        // Load WordPress sites for step 4
        $wpSites = $this->wpSite->getActiveSites($user['id']);

        // Prepare wizard data for JavaScript
        $wizardData = [
            'keywordId' => $id,
            'articleId' => $article['id'] ?? null,
            'currentStep' => $currentStep,
            'serpExtracted' => $serpExtracted,
            'serpResults' => $serpResults,
            'paaQuestions' => $paaQuestions,
            'selectedSources' => $selectedSources,
            'selectedPaa' => $selectedPaa,
            'customUrls' => $customUrls,
            'briefGenerated' => $briefData !== null,
            'briefData' => $briefData ?? [
                'searchIntent' => '',
                'intentDescription' => '',
                'suggestedHeadings' => [],
                'entities' => [],
                'targetWordCount' => 1500,
                'additionalNotes' => ''
            ],
            'articleGenerated' => $articleData !== null,
            'articleData' => $articleData ?? [
                'title' => '',
                'metaDescription' => '',
                'content' => '',
                'wordCount' => 0
            ],
            'wpSites' => array_map(function($site) {
                return [
                    'id' => $site['id'],
                    'name' => $site['name'],
                    'url' => $site['url']
                ];
            }, $wpSites)
        ];

        return View::render('ai-content/keywords/wizard', [
            'title' => 'Wizard - ' . $keyword['keyword'],
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keyword' => $keyword,
            'wizardData' => $wizardData
        ]);
    }

    /**
     * Calculate wizard step based on existing data
     */
    private function calculateWizardStep(bool $serpExtracted, array $sources, ?array $article): int
    {
        // No SERP extracted → step 1
        if (!$serpExtracted) {
            return 1;
        }

        // SERP but no sources selected → step 1
        if (empty($sources)) {
            return 1;
        }

        // No article created yet → step 2
        if (!$article) {
            return 2;
        }

        // Article exists but no content → step 2 (brief stage)
        if (empty($article['content']) || $article['status'] === 'draft') {
            // Check if brief is generated
            if (!empty($article['brief_data'])) {
                return 3; // Ready for article generation
            }
            return 2;
        }

        // Article ready but not published → step 4
        if ($article['status'] === 'ready') {
            return 4;
        }

        // Article published → step 4 (show success)
        if ($article['status'] === 'published') {
            return 4;
        }

        // Default to step 3 (article stage)
        return 3;
    }

    /**
     * Display list of keywords
     */
    public function index(): string
    {
        $user = Auth::user();
        $page = (int) ($_GET['page'] ?? 1);

        $keywordsData = $this->keyword->allByUser($user['id'], $page, 20);

        return View::render('ai-content/keywords/index', [
            'title' => 'Keywords - AI Content',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keywords' => $keywordsData['data'],
            'pagination' => [
                'current_page' => $keywordsData['current_page'],
                'last_page' => $keywordsData['last_page'],
                'total' => $keywordsData['total'],
                'per_page' => $keywordsData['per_page']
            ]
        ]);
    }

    /**
     * Store new keyword
     */
    public function store(): void
    {
        $user = Auth::user();

        $keyword = trim($_POST['keyword'] ?? '');
        $language = $_POST['language'] ?? 'it';
        $location = $_POST['location'] ?? 'Italy';

        // Validation
        if (empty($keyword)) {
            $_SESSION['flash_error'] = 'La keyword è obbligatoria';
            header('Location: ' . url('/ai-content/keywords'));
            exit;
        }

        // Check duplicate
        if ($this->keyword->exists($keyword, $user['id'])) {
            $_SESSION['flash_error'] = 'Questa keyword esiste già';
            header('Location: ' . url('/ai-content/keywords'));
            exit;
        }

        try {
            $this->keyword->create([
                'user_id' => $user['id'],
                'keyword' => $keyword,
                'language' => $language,
                'location' => $location
            ]);

            $_SESSION['flash_success'] = 'Keyword aggiunta con successo';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Errore durante il salvataggio: ' . $e->getMessage();
        }

        header('Location: ' . url('/ai-content/keywords'));
        exit;
    }

    /**
     * Delete keyword
     */
    public function delete(int $id): void
    {
        $user = Auth::user();

        // Check if keyword has articles
        $keyword = $this->keyword->find($id, $user['id']);

        if (!$keyword) {
            $_SESSION['flash_error'] = 'Keyword non trovata';
            header('Location: ' . url('/ai-content/keywords'));
            exit;
        }

        // Check for associated articles
        $articleModel = new Article();
        $articles = $articleModel->getByKeyword($id);

        if (!empty($articles)) {
            $_SESSION['flash_error'] = 'Impossibile eliminare: ci sono ' . count($articles) . ' articoli associati a questa keyword';
            header('Location: ' . url('/ai-content/keywords'));
            exit;
        }

        try {
            $this->keyword->delete($id, $user['id']);
            $_SESSION['flash_success'] = 'Keyword eliminata';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Errore durante l\'eliminazione';
        }

        header('Location: ' . url('/ai-content/keywords'));
        exit;
    }
}
