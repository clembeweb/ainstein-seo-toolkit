<?php

namespace Modules\AiContent\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\AiContent\Models\Keyword;
use Modules\AiContent\Models\Article;
use Modules\AiContent\Models\Project;
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
    public function wizard(int $id, ?int $projectId = null): string
    {
        $user = Auth::user();

        // Load keyword with ownership check
        $keyword = $this->keyword->find($id, $user['id']);

        // Redirect URL
        $redirectUrl = $projectId
            ? url('/ai-content/projects/' . $projectId . '/keywords')
            : url('/ai-content/keywords');

        if (!$keyword) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Use keyword's project_id if not provided
        if ($projectId === null && !empty($keyword['project_id'])) {
            $projectId = (int) $keyword['project_id'];
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

            // Parse brief data - estrai selectedPaa dal raw brief
            if ($article['brief_data']) {
                $rawBrief = json_decode($article['brief_data'], true);
                $selectedPaa = $rawBrief['selected_paa'] ?? [];  // FIX: chiave corretta
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

        // PRIORITA' 1: brief da keyword (formato wizard) - sempre preferito se esiste
        if ($this->keyword->hasBrief($id)) {
            $briefData = $this->keyword->getBrief($id);
        }
        // PRIORITA' 2: converti raw brief da article a formato wizard
        elseif ($article && !empty($article['brief_data'])) {
            $rawBrief = json_decode($article['brief_data'], true);
            if (isset($rawBrief['brief'])) {
                $briefData = $this->convertRawBriefToWizardFormat($rawBrief['brief']);
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
     *
     * @param int|null $projectId Optional project ID to filter by
     */
    public function index(?int $projectId = null): string
    {
        $user = Auth::user();
        $page = (int) ($_GET['page'] ?? 1);
        $project = null;

        // Leggi project_id anche da query string se non passato come parametro
        if ($projectId === null && !empty($_GET['project_id'])) {
            $projectId = (int) $_GET['project_id'];
        }

        // Se projectId specificato, verifica ownership e filtra per progetto
        if ($projectId !== null) {
            $projectModel = new Project();
            $project = $projectModel->find($projectId, $user['id']);

            if (!$project) {
                $_SESSION['_flash']['error'] = 'Progetto non trovato';
                header('Location: ' . url('/ai-content'));
                exit;
            }

            $keywordsData = $this->keyword->allByProject($projectId, $page, 20);
            $title = 'Keywords - ' . $project['name'];
        } else {
            // Lista globale utente (fallback)
            $keywordsData = $this->keyword->allByUser($user['id'], $page, 20);
            $title = 'Keywords - AI Content';
        }

        return View::render('ai-content/keywords/index', [
            'title' => $title,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keywords' => $keywordsData['data'],
            'pagination' => [
                'current_page' => $keywordsData['current_page'],
                'last_page' => $keywordsData['last_page'],
                'total' => $keywordsData['total'],
                'per_page' => $keywordsData['per_page']
            ],
            'projectId' => $projectId,
            'project' => $project
        ]);
    }

    /**
     * Store new keyword
     */
    public function store(?int $projectId = null): void
    {
        $user = Auth::user();

        $keyword = trim($_POST['keyword'] ?? '');
        $language = $_POST['language'] ?? 'it';
        $location = $_POST['location'] ?? 'Italy';

        // projectId da parametro route o da POST (legacy)
        if ($projectId === null && !empty($_POST['project_id'])) {
            $projectId = (int) $_POST['project_id'];
        }

        // URL di redirect
        $redirectUrl = $projectId
            ? url('/ai-content/projects/' . $projectId . '/keywords')
            : url('/ai-content/keywords');

        // Validation
        if (empty($keyword)) {
            $_SESSION['_flash']['error'] = 'La keyword è obbligatoria';
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Check duplicate (within same project)
        if ($this->keyword->exists($keyword, $user['id'], $projectId)) {
            $_SESSION['_flash']['error'] = 'Questa keyword esiste già in questo progetto';
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            $this->keyword->create([
                'user_id' => $user['id'],
                'project_id' => $projectId,
                'keyword' => $keyword,
                'language' => $language,
                'location' => $location
            ]);

            $_SESSION['_flash']['success'] = 'Keyword aggiunta con successo';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore durante il salvataggio: ' . $e->getMessage();
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Delete keyword
     */
    public function delete(int $id, ?int $projectId = null): void
    {
        $user = Auth::user();

        // Check if keyword has articles
        $keyword = $this->keyword->find($id, $user['id']);

        // Redirect URL
        $redirectUrl = $projectId
            ? url('/ai-content/projects/' . $projectId . '/keywords')
            : url('/ai-content/keywords');

        if (!$keyword) {
            $_SESSION['_flash']['error'] = 'Keyword non trovata';
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Use keyword's project_id if not provided
        if ($projectId === null && !empty($keyword['project_id'])) {
            $projectId = (int) $keyword['project_id'];
            $redirectUrl = url('/ai-content/projects/' . $projectId . '/keywords');
        }

        // Check for associated articles
        $articleModel = new Article();
        $articles = $articleModel->getByKeyword($id);

        if (!empty($articles)) {
            $_SESSION['_flash']['error'] = 'Impossibile eliminare: ci sono ' . count($articles) . ' articoli associati a questa keyword';
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            $this->keyword->delete($id, $user['id']);
            $_SESSION['_flash']['success'] = 'Keyword eliminata';
        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore durante l\'eliminazione';
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Converte brief raw (da AI) a formato wizard (per JS)
     */
    private function convertRawBriefToWizardFormat(array $rawBrief): array
    {
        $intent = $rawBrief['search_intent']['primary'] ?? 'informational';

        return [
            'searchIntent' => $intent,
            'intentDescription' => $this->getIntentDescription($intent),
            'suggestedHeadings' => $this->extractHeadingsFromBrief($rawBrief),
            'entities' => $this->extractEntitiesFromBrief($rawBrief),
            'targetWordCount' => $rawBrief['recommended_word_count'] ?? 1500,
            'additionalNotes' => ''
        ];
    }

    /**
     * Descrizione search intent
     */
    private function getIntentDescription(string $intent): string
    {
        $descriptions = [
            'informational' => 'L\'utente cerca informazioni su questo argomento',
            'transactional' => 'L\'utente vuole compiere un\'azione o acquisto',
            'navigational' => 'L\'utente cerca un sito o pagina specifica',
            'commercial' => 'L\'utente sta valutando opzioni prima di acquistare'
        ];
        return $descriptions[$intent] ?? $descriptions['informational'];
    }

    /**
     * Estrai headings dal brief raw
     */
    private function extractHeadingsFromBrief(array $rawBrief): array
    {
        $headings = [];

        // Prova recommended_structure.sections
        if (isset($rawBrief['recommended_structure']['sections'])) {
            foreach ($rawBrief['recommended_structure']['sections'] as $section) {
                $headings[] = [
                    'tag' => 'H2',
                    'text' => $section['suggested_h2'] ?? $section['title'] ?? ''
                ];
            }
        }
        // Fallback: structure.sections
        elseif (isset($rawBrief['structure']['sections'])) {
            foreach ($rawBrief['structure']['sections'] as $section) {
                $headings[] = [
                    'tag' => $section['tag'] ?? 'H2',
                    'text' => $section['title'] ?? $section['suggested_h2'] ?? ''
                ];
            }
        }

        // Se vuoto, struttura default
        if (empty($headings)) {
            $headings = [
                ['tag' => 'H1', 'text' => $rawBrief['keyword'] ?? ''],
                ['tag' => 'H2', 'text' => 'Introduzione'],
                ['tag' => 'H2', 'text' => 'Come funziona'],
                ['tag' => 'H2', 'text' => 'Vantaggi'],
                ['tag' => 'H2', 'text' => 'Conclusioni']
            ];
        }

        return $headings;
    }

    /**
     * Estrai entita dal brief raw
     */
    private function extractEntitiesFromBrief(array $rawBrief): array
    {
        $entities = [];

        // Prova key_entities.phrases
        if (isset($rawBrief['key_entities']['phrases'])) {
            foreach (array_slice($rawBrief['key_entities']['phrases'], 0, 10) as $phrase) {
                $entities[] = $phrase['phrase'] ?? $phrase;
            }
        }
        // Prova key_entities.single_terms
        if (isset($rawBrief['key_entities']['single_terms'])) {
            foreach (array_slice($rawBrief['key_entities']['single_terms'], 0, 5) as $term) {
                $termText = $term['term'] ?? $term;
                if (!in_array($termText, $entities)) {
                    $entities[] = $termText;
                }
            }
        }
        // Fallback: entities diretto
        if (empty($entities) && isset($rawBrief['entities'])) {
            $entities = array_slice($rawBrief['entities'], 0, 15);
        }

        return $entities;
    }
}
