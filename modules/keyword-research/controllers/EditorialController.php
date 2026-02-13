<?php

namespace Modules\KeywordResearch\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\Credits;
use Core\ModuleLoader;
use Services\AiService;
use Services\ApiLoggerService;
use Modules\KeywordResearch\Models\Project;
use Modules\KeywordResearch\Models\Research;
use Modules\KeywordResearch\Models\EditorialItem;
use Modules\KeywordResearch\Services\KeywordInsightService;

class EditorialController
{
    private Project $projectModel;
    private Research $researchModel;
    private EditorialItem $editorialModel;

    private const LOCATION_NAMES = [
        'IT' => 'Italy',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'DE' => 'Germany',
        'FR' => 'France',
        'ES' => 'Spain',
    ];

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->researchModel = new Research();
        $this->editorialModel = new EditorialItem();
    }

    /**
     * Wizard 4-step per Piano Editoriale
     */
    public function wizard(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            exit;
        }

        $researches = $this->researchModel->findByProject($projectId, 'editorial');

        return View::render('keyword-research::editorial/wizard', [
            'title' => 'Piano Editoriale - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'researches' => $researches,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Step 1: Avvia raccolta (crea research + ritorna ID)
     */
    public function startCollection(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
            return;
        }

        $rawCategories = $_POST['categories'] ?? '';
        if (is_array($rawCategories)) {
            $categories = array_filter(array_map('trim', $rawCategories));
        } else {
            $categories = array_filter(array_map('trim', explode(',', $rawCategories)));
        }

        $brief = [
            'theme' => trim($_POST['theme'] ?? ''),
            'categories' => $categories,
            'months' => (int) ($_POST['months'] ?? 6),
            'articles_per_month' => (int) ($_POST['articles_per_month'] ?? 4),
            'target' => trim($_POST['target'] ?? 'B2C'),
            'geography' => trim($_POST['geography'] ?? $project['default_location']),
        ];

        if (empty($brief['theme'])) {
            echo json_encode(['success' => false, 'error' => 'Il tema del blog è obbligatorio.']);
            return;
        }

        if (count($brief['categories']) < 2) {
            echo json_encode(['success' => false, 'error' => 'Inserisci almeno 2 categorie.']);
            return;
        }

        if (count($brief['categories']) > 6) {
            $brief['categories'] = array_slice($brief['categories'], 0, 6);
        }

        $researchId = $this->researchModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => 'editorial',
            'status' => 'collecting',
            'brief' => $brief,
        ]);

        echo json_encode(['success' => true, 'research_id' => $researchId]);
    }

    /**
     * Step 2: SSE stream raccolta keyword + SERP per categoria
     */
    public function collectionStream(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Progetto non trovato.']);
            return;
        }

        $researchId = (int) ($_GET['research_id'] ?? 0);
        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$research || $research['project_id'] !== $projectId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ricerca non trovata.']);
            return;
        }

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        ignore_user_abort(true);
        set_time_limit(0);
        session_write_close();

        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data) . "\n\n";
            if (ob_get_level()) ob_flush();
            flush();
        };

        $brief = json_decode($research['brief'], true);
        $categories = $brief['categories'] ?? [];
        $theme = $brief['theme'] ?? '';
        $location = $brief['geography'] ?? $project['default_location'];
        $lang = $project['default_language'] ?? 'it';
        $locationName = self::LOCATION_NAMES[$location] ?? 'Italy';

        // Servizio keyword
        $kwService = new KeywordInsightService();
        if (!$kwService->isConfigured()) {
            $sendEvent('error', ['message' => 'API RapidAPI non configurata.']);
            return;
        }

        // Servizio SERP (opzionale)
        $serpService = null;
        $serpAvailable = false;
        try {
            $serpService = new \Modules\AiContent\Services\SerpApiService();
            $serpAvailable = true;
        } catch (\Exception $e) {
            // SERP non configurata: procediamo solo con keyword
            error_log("[Editorial] SERP non disponibile: " . $e->getMessage());
        }

        $sendEvent('started', [
            'total_categories' => count($categories),
            'research_id' => $researchId,
            'serp_available' => $serpAvailable,
        ]);

        $allCategoriesData = [];
        $totalApiTime = 0;
        $allKeywords = [];
        $minVolume = (int) (ModuleLoader::getSetting('keyword-research', 'min_search_volume') ?? 10);

        foreach ($categories as $i => $category) {
            $sendEvent('category_started', [
                'category' => $category,
                'index' => $i,
                'total' => count($categories),
            ]);

            $categoryData = [
                'keywords' => [],
                'serp_titles' => [],
                'paa' => [],
            ];

            // 1. Raccolta keyword per questa categoria
            $startTime = microtime(true);
            $result = $kwService->keySuggest($category, $location, $lang);
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $totalApiTime += $elapsed;

            Database::reconnect();

            $categoryKwCount = 0;
            if ($result['success']) {
                foreach ($result['data'] as $item) {
                    $text = $item['text'] ?? '';
                    $vol = (int) ($item['volume'] ?? 0);
                    if ($text && $vol >= $minVolume && !isset($allKeywords[$text])) {
                        $kw = [
                            'text' => $text,
                            'volume' => $vol,
                            'competition_level' => $item['competition_level'] ?? '',
                            'competition_index' => (int) ($item['competition_index'] ?? 0),
                            'low_bid' => (float) ($item['low_bid'] ?? 0),
                            'high_bid' => (float) ($item['high_bid'] ?? 0),
                            'trend' => (float) ($item['trend'] ?? 0),
                            'intent' => $item['intent'] ?? '',
                            'category' => $category,
                        ];
                        $allKeywords[$text] = $kw;
                        $categoryData['keywords'][] = $kw;
                        $categoryKwCount++;
                    }
                }
            }

            $sendEvent('category_keywords', [
                'category' => $category,
                'new_keywords' => $categoryKwCount,
                'total_keywords' => count($allKeywords),
                'elapsed_ms' => $elapsed,
            ]);

            // 2. SERP competitor titles (se disponibile)
            if ($serpAvailable && $serpService) {
                $serpQuery = $category;
                $serpData = $this->getCachedSerp($serpQuery, $location, $lang);

                if (!$serpData) {
                    try {
                        $serpStart = microtime(true);
                        $serpData = $serpService->search($serpQuery, $lang, $locationName);
                        $serpElapsed = (int) round((microtime(true) - $serpStart) * 1000);
                        $totalApiTime += $serpElapsed;

                        Database::reconnect();

                        // Salva in cache
                        $this->saveSerpCache($serpQuery, $location, $lang, $serpData);
                    } catch (\Exception $e) {
                        error_log("[Editorial] SERP error per '{$category}': " . $e->getMessage());
                        $serpData = ['organic' => [], 'paa' => [], 'related' => []];
                    }
                }

                $categoryData['serp_titles'] = array_map(function ($r) {
                    return [
                        'position' => $r['position'] ?? 0,
                        'title' => $r['title'] ?? '',
                        'snippet' => $r['snippet'] ?? '',
                        'domain' => $r['domain'] ?? '',
                    ];
                }, $serpData['organic'] ?? []);

                $categoryData['paa'] = $serpData['paa'] ?? [];

                $sendEvent('category_serp', [
                    'category' => $category,
                    'titles_count' => count($categoryData['serp_titles']),
                    'paa_count' => count($categoryData['paa']),
                ]);
            }

            $allCategoriesData[$category] = $categoryData;

            $sendEvent('category_completed', [
                'category' => $category,
                'keywords_count' => $categoryKwCount,
                'serp_titles_count' => count($categoryData['serp_titles']),
            ]);

            // Pausa tra categorie
            if ($i < count($categories) - 1) {
                usleep(300000); // 300ms
            }
        }

        // Salva risultati nel DB PRIMA dell'evento completed (polling fallback)
        Database::reconnect();
        $this->researchModel->saveResults($researchId, [
            'raw_keywords_count' => count($allKeywords),
            'filtered_keywords_count' => count($allKeywords),
            'api_time_ms' => $totalApiTime,
            'ai_response' => [
                'categories_data' => $allCategoriesData,
                'total_keywords' => count($allKeywords),
            ],
            'status' => 'draft',
        ]);

        $sendEvent('completed', [
            'total_keywords' => count($allKeywords),
            'total_categories' => count($categories),
            'serp_available' => $serpAvailable,
            'api_time_ms' => $totalApiTime,
            'categories_data' => $allCategoriesData,
        ]);
    }

    /**
     * Polling fallback per risultati raccolta
     */
    public function collectionResults(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $researchId = (int) ($_GET['research_id'] ?? 0);
        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
            return;
        }

        if ($research['status'] === 'collecting') {
            echo json_encode(['success' => true, 'status' => 'collecting']);
            return;
        }

        $aiResponse = json_decode($research['ai_response'] ?? '{}', true);

        echo json_encode([
            'success' => true,
            'status' => $research['status'],
            'total_keywords' => $research['raw_keywords_count'] ?? 0,
            'api_time_ms' => $research['api_time_ms'] ?? 0,
            'categories_data' => $aiResponse['categories_data'] ?? [],
        ]);
    }

    /**
     * Step 3: AI genera il piano editoriale
     */
    public function aiAnalyze(int $projectId): void
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = $this->projectModel->find($projectId, $user['id']);

            if (!$project) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
                exit;
            }

            $researchId = (int) ($_POST['research_id'] ?? 0);
            $research = $this->researchModel->find($researchId, $user['id']);

            if (!$research || $research['project_id'] !== $projectId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
                exit;
            }

            // Costo crediti
            $cost = (float) (ModuleLoader::getSetting('keyword-research', 'cost_kr_editorial_plan') ?? 5);

            if (!Credits::hasEnough($user['id'], $cost)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$cost}"]);
                exit;
            }

            $brief = json_decode($research['brief'], true);
            $aiResponse = json_decode($research['ai_response'] ?? '{}', true);
            $categoriesData = $aiResponse['categories_data'] ?? [];

            if (empty($categoriesData)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Nessun dato raccolto. Ripeti la raccolta.']);
                exit;
            }

            session_write_close();

            // Costruisci prompt
            $prompt = $this->buildAiPrompt($brief, $categoriesData);

            $ai = new AiService('keyword-research');
            if (!$ai->isConfigured()) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'AI non configurata.']);
                exit;
            }

            $systemPrompt = "Sei un esperto SEO italiano e content strategist specializzato in pianificazione editoriale per blog. Rispondi SOLO con JSON valido, senza markdown, senza commenti, senza testo prima o dopo il JSON.";

            $aiStart = microtime(true);
            $aiResult = $ai->analyzeWithSystem($user['id'], $systemPrompt, $prompt, 'keyword-research');
            $aiElapsed = (int) round((microtime(true) - $aiStart) * 1000);

            Database::reconnect();

            if (isset($aiResult['error']) && $aiResult['error']) {
                $this->researchModel->updateStatus($researchId, 'error');
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => $aiResult['message'] ?? 'Errore AI.']);
                exit;
            }

            // Parse JSON response
            $aiContent = $aiResult['result'] ?? '';
            $aiContent = preg_replace('/^```(?:json)?\s*/i', '', $aiContent);
            $aiContent = preg_replace('/\s*```\s*$/', '', $aiContent);
            $aiContent = trim($aiContent);

            $parsed = json_decode($aiContent, true);

            if ($parsed === null || !isset($parsed['months'])) {
                $this->researchModel->updateStatus($researchId, 'error');
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Risposta AI non valida. ' . json_last_error_msg()]);
                exit;
            }

            // Salva editorial items nel DB
            $totalArticles = 0;
            foreach ($parsed['months'] as $month) {
                $monthNum = (int) ($month['month_number'] ?? 0);
                $articles = $month['articles'] ?? [];

                foreach ($articles as $sortIdx => $article) {
                    $this->editorialModel->create([
                        'research_id' => $researchId,
                        'month_number' => $monthNum,
                        'category' => $article['category'] ?? '',
                        'title' => $article['title'] ?? '',
                        'main_keyword' => $article['main_keyword'] ?? '',
                        'main_volume' => (int) ($article['main_volume'] ?? 0),
                        'secondary_keywords' => $article['secondary_keywords'] ?? [],
                        'intent' => $article['intent'] ?? null,
                        'difficulty' => $article['difficulty'] ?? 'medium',
                        'content_type' => $article['content_type'] ?? null,
                        'notes' => $article['notes'] ?? null,
                        'seasonal_note' => $article['seasonal_note'] ?? null,
                        'serp_gap' => $article['serp_gap'] ?? null,
                        'sort_order' => $sortIdx,
                    ]);
                    $totalArticles++;
                }
            }

            // Consuma crediti
            Credits::consume($user['id'], $cost, 'kr_editorial_plan', 'keyword-research', [
                'research_id' => $researchId,
                'articles_count' => $totalArticles,
            ]);

            // Aggiorna research
            $this->researchModel->saveResults($researchId, [
                'strategy_note' => $parsed['strategy_note'] ?? null,
                'credits_used' => $cost,
                'ai_time_ms' => $aiElapsed,
                'status' => 'completed',
            ]);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'research_id' => $researchId,
                'articles_count' => $totalArticles,
                'credits_used' => $cost,
                'redirect' => url('/keyword-research/project/' . $projectId . '/editorial/' . $researchId),
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("[Editorial] aiAnalyze error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Step 4: Risultati piano editoriale
     */
    public function results(int $projectId, int $researchId): string
    {
        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            exit;
        }

        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$research || $research['project_id'] !== $projectId || $research['type'] !== 'editorial') {
            $_SESSION['_flash']['error'] = 'Piano editoriale non trovato.';
            Router::redirect('/keyword-research/project/' . $projectId . '/editorial');
            exit;
        }

        $itemsByMonth = $this->editorialModel->findGroupedByMonth($researchId);
        $stats = $this->editorialModel->getStats($researchId);
        $brief = json_decode($research['brief'], true);

        // Dati AI per note mensili
        $aiResponse = json_decode($research['ai_response'] ?? '{}', true);
        $monthNotes = [];
        if (isset($aiResponse['categories_data'])) {
            // Le note mensili sono salvate negli editorial items, non serve estrarre qui
        }

        // Progetti ai-content per integrazione
        $aicProjects = [];
        if (ModuleLoader::isModuleActive('ai-content')) {
            $aicProjects = Database::fetchAll(
                "SELECT id, name FROM aic_projects WHERE user_id = ? ORDER BY name",
                [$user['id']]
            );
        }

        return View::render('keyword-research::editorial/results', [
            'title' => 'Piano Editoriale - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'research' => $research,
            'itemsByMonth' => $itemsByMonth,
            'stats' => $stats,
            'brief' => $brief,
            'aicProjects' => $aicProjects,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Export CSV del piano editoriale
     */
    public function exportCsv(int $projectId, int $researchId): void
    {
        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);
        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$project || !$research || $research['project_id'] !== $projectId) {
            $_SESSION['_flash']['error'] = 'Dati non trovati.';
            Router::redirect('/keyword-research/projects');
            return;
        }

        $items = $this->editorialModel->findByResearch($researchId);
        $brief = json_decode($research['brief'], true);

        $filename = 'piano-editoriale-' . $project['id'] . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, ['Mese', 'Categoria', 'Titolo', 'Keyword Principale', 'Volume', 'Tipo Contenuto', 'Intent', 'Difficolta', 'Note']);

        foreach ($items as $item) {
            fputcsv($output, [
                'Mese ' . $item['month_number'],
                $item['category'],
                $item['title'],
                $item['main_keyword'],
                $item['main_volume'],
                $item['content_type'],
                $item['intent'],
                $item['difficulty'],
                $item['notes'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Invia articoli selezionati a AI Content
     */
    public function sendToContent(int $projectId, int $researchId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);
        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$project || !$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Dati non trovati.']);
            return;
        }

        if (!ModuleLoader::isModuleActive('ai-content')) {
            echo json_encode(['success' => false, 'error' => 'Modulo AI Content non attivo.']);
            return;
        }

        $rawItemIds = $_POST['item_ids'] ?? '[]';
        $itemIds = is_array($rawItemIds) ? $rawItemIds : json_decode($rawItemIds, true);
        $aicProjectId = (int) ($_POST['aic_project_id'] ?? 0);

        if (empty($itemIds) || !$aicProjectId) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno un articolo e un progetto AI Content.']);
            return;
        }

        // Verifica progetto ai-content
        $aicProject = Database::fetch(
            "SELECT id FROM aic_projects WHERE id = ? AND user_id = ?",
            [$aicProjectId, $user['id']]
        );

        if (!$aicProject) {
            echo json_encode(['success' => false, 'error' => 'Progetto AI Content non trovato.']);
            return;
        }

        $brief = json_decode($research['brief'], true);
        $lang = $project['default_language'] ?? 'it';
        $locationName = self::LOCATION_NAMES[$brief['geography'] ?? 'IT'] ?? 'Italy';

        $added = 0;
        $skipped = 0;
        $sentIds = [];

        foreach ($itemIds as $itemId) {
            $item = $this->editorialModel->find((int) $itemId);
            if (!$item || $item['research_id'] !== $researchId) {
                $skipped++;
                continue;
            }

            if ($item['sent_to_content']) {
                $skipped++;
                continue;
            }

            // Verifica duplicato in aic_queue
            $existing = Database::fetch(
                "SELECT id FROM aic_queue WHERE project_id = ? AND keyword = ? AND status IN ('pending', 'processing')",
                [$aicProjectId, $item['main_keyword']]
            );

            if ($existing) {
                $skipped++;
                continue;
            }

            Database::insert('aic_queue', [
                'user_id' => $user['id'],
                'project_id' => $aicProjectId,
                'keyword' => $item['main_keyword'],
                'language' => $lang,
                'location' => $locationName,
                'sources_count' => 3,
                'scheduled_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $sentIds[] = (int) $itemId;
            $added++;
        }

        if (!empty($sentIds)) {
            $this->editorialModel->markSentToContent($sentIds);
        }

        echo json_encode([
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'message' => "{$added} articoli aggiunti alla coda AI Content." . ($skipped > 0 ? " {$skipped} saltati (duplicati o già inviati)." : ''),
        ]);
    }

    /**
     * Costruisce il prompt AI per il piano editoriale
     */
    private function buildAiPrompt(array $brief, array $categoriesData): string
    {
        $theme = $brief['theme'] ?? '';
        $target = $brief['target'] ?? 'B2C';
        $geography = $brief['geography'] ?? 'IT';
        $months = (int) ($brief['months'] ?? 6);
        $articlesPerMonth = (int) ($brief['articles_per_month'] ?? 4);
        $categories = $brief['categories'] ?? [];
        $totalArticles = $months * $articlesPerMonth;

        $prompt = "Crea un piano editoriale per un blog.

TEMA DEL BLOG: {$theme}
TARGET: {$target}
GEOGRAFIA: {$geography}
PERIODO: {$months} mesi
ARTICOLI AL MESE: {$articlesPerMonth}
TOTALE ARTICOLI: {$totalArticles}
CATEGORIE: " . implode(', ', $categories) . "

DATI KEYWORD E COMPETITOR PER CATEGORIA:\n\n";

        foreach ($categoriesData as $catName => $catData) {
            $prompt .= "## {$catName}\n";

            // Top 30 keyword per volume
            $keywords = $catData['keywords'] ?? [];
            usort($keywords, fn($a, $b) => ($b['volume'] ?? 0) - ($a['volume'] ?? 0));
            $topKeywords = array_slice($keywords, 0, 30);

            if (!empty($topKeywords)) {
                $prompt .= "Keywords (" . count($topKeywords) . "):\n";
                foreach ($topKeywords as $kw) {
                    $prompt .= "- {$kw['text']} (vol: {$kw['volume']}, intent: {$kw['intent']})\n";
                }
            }

            // Titoli SERP competitor
            $serpTitles = $catData['serp_titles'] ?? [];
            if (!empty($serpTitles)) {
                $prompt .= "\nTitoli competitor SERP per \"{$catName}\":\n";
                foreach ($serpTitles as $r) {
                    $prompt .= "- {$r['title']} ({$r['domain']})\n";
                }
            }

            // PAA
            $paa = $catData['paa'] ?? [];
            if (!empty($paa)) {
                $prompt .= "\nDomande frequenti (PAA):\n";
                foreach (array_slice($paa, 0, 5) as $q) {
                    $question = $q['question'] ?? ($q['snippet'] ?? '');
                    if ($question) {
                        $prompt .= "- {$question}\n";
                    }
                }
            }

            $prompt .= "\n";
        }

        $prompt .= "ISTRUZIONI:
1. Crea esattamente {$articlesPerMonth} articoli per ognuno dei {$months} mesi (totale {$totalArticles} articoli)
2. Distribuisci equamente le categorie nei mesi
3. Per ogni articolo suggerisci:
   - Titolo accattivante e SEO-ottimizzato (diverso dai competitor trovati nelle SERP)
   - Keyword principale (DEVE essere una delle keyword fornite sopra, con volume reale)
   - 2-4 keyword secondarie correlate (dalle keyword fornite)
   - Tipo di contenuto: guida, tutorial, listicle, case-study, confronto, how-to, opinione, checklist, intervista, approfondimento
   - Difficolta: low, medium, high (basata sulla competition delle keyword)
4. Considera la stagionalita e posiziona contenuti rilevanti nei mesi appropriati
5. Varia i tipi di contenuto per mantenere il blog interessante e diversificato
6. Identifica gap nei contenuti dei competitor (cosa manca nelle SERP)
7. Aggiungi note strategiche per ogni mese
8. Non ripetere mai la stessa keyword principale in articoli diversi

RISPONDI SOLO IN JSON CON QUESTA STRUTTURA ESATTA:
{
  \"months\": [
    {
      \"month_number\": 1,
      \"month_note\": \"Nota strategica per questo mese\",
      \"articles\": [
        {
          \"title\": \"Titolo articolo SEO-ottimizzato\",
          \"main_keyword\": \"keyword principale\",
          \"main_volume\": 1000,
          \"secondary_keywords\": [
            {\"text\": \"keyword correlata\", \"volume\": 500}
          ],
          \"category\": \"nome categoria esatta\",
          \"content_type\": \"guida\",
          \"intent\": \"informational\",
          \"difficulty\": \"medium\",
          \"notes\": \"Perche questo articolo e importante\",
          \"seasonal_note\": \"Rilevanza stagionale (o null)\",
          \"serp_gap\": \"Cosa manca nei risultati attuali\"
        }
      ]
    }
  ],
  \"strategy_note\": \"Strategia complessiva del piano editoriale\"
}";

        return $prompt;
    }

    /**
     * Cache SERP: leggi
     */
    private function getCachedSerp(string $query, string $location, string $lang): ?array
    {
        $row = Database::fetch(
            "SELECT organic_results, paa, related_searches FROM kr_serp_cache
             WHERE query = ? AND location = ? AND language = ?
             AND cached_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            [$query, $location, $lang]
        );

        if ($row) {
            return [
                'organic' => json_decode($row['organic_results'], true) ?: [],
                'paa' => json_decode($row['paa'], true) ?: [],
                'related' => json_decode($row['related_searches'], true) ?: [],
            ];
        }
        return null;
    }

    /**
     * Cache SERP: salva
     */
    private function saveSerpCache(string $query, string $location, string $lang, array $data): void
    {
        Database::query(
            "INSERT INTO kr_serp_cache (query, location, language, organic_results, paa, related_searches, cached_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE organic_results = VALUES(organic_results), paa = VALUES(paa), related_searches = VALUES(related_searches), cached_at = NOW()",
            [
                $query,
                $location,
                $lang,
                json_encode($data['organic'] ?? []),
                json_encode($data['paa'] ?? []),
                json_encode($data['related'] ?? []),
            ]
        );
    }
}
