<?php

namespace Modules\KeywordResearch\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Database;
use Core\Credits;
use Core\ModuleLoader;
use Services\AiService;
use Modules\KeywordResearch\Models\Project;
use Modules\KeywordResearch\Models\Research;
use Modules\KeywordResearch\Models\Cluster;
use Modules\KeywordResearch\Services\KeywordInsightService;

class ArchitectureController
{
    private Project $projectModel;
    private Research $researchModel;
    private Cluster $clusterModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->researchModel = new Research();
        $this->clusterModel = new Cluster();
    }

    public function wizard(int $projectId): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            exit;
        }

        $researches = $this->researchModel->findByProject($projectId, 'architecture');

        return View::render('keyword-research::architecture/wizard', [
            'title' => 'Architettura Sito - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'researches' => $researches,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    public function startCollection(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
            return;
        }

        $brief = [
            'business' => trim($_POST['business'] ?? ''),
            'target' => trim($_POST['target'] ?? 'B2B'),
            'geography' => trim($_POST['geography'] ?? $project['default_location']),
            'site_type' => trim($_POST['site_type'] ?? 'corporate'),
            'seeds' => array_filter(array_map('trim', explode(',', $_POST['seeds'] ?? ''))),
        ];

        if (empty($brief['business']) || empty($brief['seeds'])) {
            echo json_encode(['success' => false, 'error' => 'Business e seed keyword sono obbligatori.']);
            return;
        }

        $researchId = $this->researchModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => 'architecture',
            'status' => 'collecting',
            'brief' => $brief,
        ]);

        echo json_encode(['success' => true, 'research_id' => $researchId]);
    }

    public function collectionStream(int $projectId): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Progetto non trovato.']);
            return;
        }

        $researchId = (int) ($_GET['research_id'] ?? 0);
        $research = $this->researchModel->find($researchId);

        if (!$research || $research['project_id'] !== $projectId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ricerca non trovata.']);
            return;
        }

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
        $seeds = $brief['seeds'] ?? [];
        $location = $brief['geography'] ?? $project['default_location'];
        $lang = $project['default_language'] ?? 'it';

        $service = new KeywordInsightService();

        if (!$service->isConfigured()) {
            $sendEvent('error', ['message' => 'API RapidAPI non configurata.']);
            return;
        }

        $sendEvent('started', ['total_seeds' => count($seeds), 'research_id' => $researchId]);

        $allKeywords = [];
        $totalApiTime = 0;

        foreach ($seeds as $i => $seed) {
            $sendEvent('seed_started', ['seed' => $seed, 'index' => $i, 'total' => count($seeds)]);

            $startTime = microtime(true);
            $result = $service->keySuggest($seed, $location, $lang);
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $totalApiTime += $elapsed;

            Database::reconnect();

            if (!$result['success']) {
                $sendEvent('seed_error', ['seed' => $seed, 'error' => $result['error']]);
                continue;
            }

            $seedCount = 0;
            foreach ($result['data'] as $item) {
                $text = $item['text'] ?? '';
                if ($text && !isset($allKeywords[$text])) {
                    $allKeywords[$text] = [
                        'text' => $text,
                        'volume' => (int) ($item['volume'] ?? 0),
                        'competition_level' => $item['competition_level'] ?? '',
                        'competition_index' => (int) ($item['competition_index'] ?? 0),
                        'low_bid' => (float) ($item['low_bid'] ?? 0),
                        'high_bid' => (float) ($item['high_bid'] ?? 0),
                        'trend' => (float) ($item['trend'] ?? 0),
                        'intent' => $item['intent'] ?? '',
                    ];
                    $seedCount++;
                }
            }

            $sendEvent('seed_completed', [
                'seed' => $seed,
                'new_keywords' => $seedCount,
                'total_keywords' => count($allKeywords),
                'elapsed_ms' => $elapsed,
            ]);

            if ($i < count($seeds) - 1) usleep(300000);
        }

        $sendEvent('filtering', ['total_raw' => count($allKeywords)]);

        $minVolume = (int) (ModuleLoader::getSetting('keyword-research', 'min_search_volume') ?? 10);
        $filterResult = $service->filterKeywords(array_values($allKeywords), [], $minVolume);
        $filtered = $filterResult['keywords'];

        // Salva risultati nel DB (incluse keyword filtrate per fallback polling)
        Database::reconnect();
        $this->researchModel->saveResults($researchId, [
            'raw_keywords_count' => count($allKeywords),
            'filtered_keywords_count' => count($filtered),
            'api_time_ms' => $totalApiTime,
            'ai_response' => ['filtered_keywords' => $filtered, 'excluded_stats' => $filterResult['excluded_stats']],
            'status' => 'draft',
        ]);

        $sendEvent('completed', [
            'raw_keywords' => count($allKeywords),
            'filtered_keywords' => count($filtered),
            'excluded_volume_low' => $filterResult['excluded_stats']['volume_low'],
            'api_time_ms' => $totalApiTime,
            'keywords' => $filtered,
        ]);
    }

    /**
     * Polling fallback per risultati raccolta (quando SSE si disconnette)
     */
    public function collectionResults(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $researchId = (int) ($_GET['research_id'] ?? 0);
        $research = $this->researchModel->find($researchId);

        if (!$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
            return;
        }

        if ($research['status'] === 'collecting') {
            echo json_encode(['success' => true, 'status' => 'collecting']);
            return;
        }

        $aiResponse = json_decode($research['ai_response'] ?? '{}', true);
        $filtered = $aiResponse['filtered_keywords'] ?? [];
        $excludedStats = $aiResponse['excluded_stats'] ?? ['volume_low' => 0];

        echo json_encode([
            'success' => true,
            'status' => $research['status'],
            'raw_keywords' => $research['raw_keywords_count'],
            'filtered_keywords' => count($filtered),
            'excluded_volume_low' => $excludedStats['volume_low'],
            'api_time_ms' => $research['api_time_ms'],
            'keywords' => $filtered,
        ]);
    }

    public function aiAnalyze(int $projectId): void
    {
        set_time_limit(0);
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
            return;
        }

        $researchId = (int) ($_POST['research_id'] ?? 0);
        $research = $this->researchModel->find($researchId);

        if (!$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
            return;
        }

        $keywordsJson = $_POST['keywords'] ?? '[]';
        $keywords = json_decode($keywordsJson, true);

        if (empty($keywords)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna keyword da analizzare.']);
            return;
        }

        $cost = (float) (ModuleLoader::getSetting('keyword-research', 'cost_kr_ai_architecture') ?? 5);

        if (!Credits::hasEnough($user['id'], $cost)) {
            echo json_encode(['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$cost}"]);
            return;
        }

        $brief = json_decode($research['brief'], true);
        $maxClusters = (int) (ModuleLoader::getSetting('keyword-research', 'max_clusters') ?? 8);

        $kwLines = [];
        foreach ($keywords as $kw) {
            $kwLines[] = "- {$kw['text']} (vol: {$kw['volume']}, intent: {$kw['intent']})";
        }
        $kwListText = implode("\n", $kwLines);

        $siteTypes = [
            'corporate' => 'sito corporate/aziendale',
            'ecommerce' => 'e-commerce',
            'blog' => 'blog/magazine',
            'saas' => 'SaaS/software',
            'local' => 'attivita\' locale',
            'portfolio' => 'portfolio/freelance',
        ];
        $siteTypeLabel = $siteTypes[$brief['site_type'] ?? 'corporate'] ?? 'sito web';

        $systemPrompt = "Sei un esperto SEO italiano e information architect. Rispondi SOLO con JSON valido, senza markdown, senza commenti, senza testo prima o dopo il JSON.";

        $userPrompt = "Progetta l'architettura di un {$siteTypeLabel} per questo business:
Business: {$brief['business']}
Target: {$brief['target']}
Geografia: {$brief['geography']}
Tipo sito: {$siteTypeLabel}

KEYWORD DISPONIBILI (" . count($keywords) . " keyword):
{$kwListText}

TASK:
1. Proponi le pagine principali del sito basate sui volumi di ricerca reali
2. Per ogni pagina suggerisci URL/slug, H1 e le keyword che copre
3. Raggruppa le keyword in max {$maxClusters} pagine/sezioni
4. Indica l'intent dominante per ogni pagina
5. Ordina per priorita' (volume + importanza strategica)

RISPONDI SOLO IN JSON CON QUESTA STRUTTURA ESATTA:
{
  \"clusters\": [
    {
      \"name\": \"Nome pagina/sezione\",
      \"main_keyword\": \"keyword principale\",
      \"main_volume\": 1000,
      \"keywords\": [\"kw1\", \"kw2\", \"kw3\"],
      \"total_volume\": 2500,
      \"intent\": \"informational|transactional|commercial|navigational\",
      \"suggested_url\": \"/url-suggerito\",
      \"suggested_h1\": \"H1 ottimizzato per la pagina\",
      \"note\": \"Note su contenuto e struttura della pagina\"
    }
  ],
  \"excluded\": [\"keyword1 non usata\"],
  \"excluded_reason\": \"Motivo esclusione\",
  \"strategy_note\": \"Nota strategica sull'architettura proposta\"
}";

        $ai = new AiService('keyword-research');

        if (!$ai->isConfigured()) {
            echo json_encode(['success' => false, 'error' => 'AI non configurata.']);
            return;
        }

        $aiStart = microtime(true);
        $aiResult = $ai->analyzeWithSystem($user['id'], $systemPrompt, $userPrompt, 'keyword-research');
        $aiElapsed = (int) round((microtime(true) - $aiStart) * 1000);

        Database::reconnect();

        if (isset($aiResult['error']) && $aiResult['error']) {
            $this->researchModel->updateStatus($researchId, 'error');
            echo json_encode(['success' => false, 'error' => $aiResult['message'] ?? 'Errore AI.']);
            return;
        }

        $aiContent = $aiResult['result'] ?? '';
        $aiContent = preg_replace('/^```(?:json)?\s*/i', '', $aiContent);
        $aiContent = preg_replace('/\s*```\s*$/', '', $aiContent);
        $aiContent = trim($aiContent);

        $parsed = json_decode($aiContent, true);

        if ($parsed === null) {
            $this->researchModel->updateStatus($researchId, 'error');
            echo json_encode(['success' => false, 'error' => 'Impossibile parsare la risposta AI.']);
            return;
        }

        $clusters = $parsed['clusters'] ?? [];
        $excluded = $parsed['excluded'] ?? [];

        $kwMap = [];
        foreach ($keywords as $kw) {
            $kwMap[strtolower($kw['text'])] = $kw;
        }

        foreach ($clusters as $i => $cluster) {
            $clusterId = $this->clusterModel->create([
                'research_id' => $researchId,
                'name' => $cluster['name'] ?? 'Pagina ' . ($i + 1),
                'main_keyword' => $cluster['main_keyword'] ?? '',
                'main_volume' => (int) ($cluster['main_volume'] ?? 0),
                'total_volume' => (int) ($cluster['total_volume'] ?? 0),
                'keywords_count' => count($cluster['keywords'] ?? []),
                'intent' => $cluster['intent'] ?? null,
                'note' => $cluster['note'] ?? null,
                'suggested_url' => $cluster['suggested_url'] ?? null,
                'suggested_h1' => $cluster['suggested_h1'] ?? null,
                'sort_order' => $i,
            ]);

            foreach ($cluster['keywords'] ?? [] as $kwText) {
                $kwData = $kwMap[strtolower($kwText)] ?? null;
                $isMain = strtolower($kwText) === strtolower($cluster['main_keyword'] ?? '');

                $this->clusterModel->saveKeyword([
                    'research_id' => $researchId,
                    'cluster_id' => $clusterId,
                    'text' => $kwText,
                    'volume' => $kwData['volume'] ?? 0,
                    'competition_level' => $kwData['competition_level'] ?? null,
                    'competition_index' => $kwData['competition_index'] ?? 0,
                    'low_bid' => $kwData['low_bid'] ?? 0,
                    'high_bid' => $kwData['high_bid'] ?? 0,
                    'trend' => $kwData['trend'] ?? 0,
                    'intent' => $kwData['intent'] ?? null,
                    'is_main' => $isMain ? 1 : 0,
                    'is_excluded' => 0,
                ]);
            }
        }

        foreach ($excluded as $exText) {
            $kwData = $kwMap[strtolower($exText)] ?? null;
            $this->clusterModel->saveKeyword([
                'research_id' => $researchId,
                'cluster_id' => null,
                'text' => $exText,
                'volume' => $kwData['volume'] ?? 0,
                'competition_level' => $kwData['competition_level'] ?? null,
                'competition_index' => $kwData['competition_index'] ?? 0,
                'low_bid' => $kwData['low_bid'] ?? 0,
                'high_bid' => $kwData['high_bid'] ?? 0,
                'trend' => $kwData['trend'] ?? 0,
                'intent' => $kwData['intent'] ?? null,
                'is_main' => 0,
                'is_excluded' => 1,
            ]);
        }

        Credits::consume($user['id'], $cost, 'kr_ai_architecture', 'keyword-research', [
            'research_id' => $researchId,
            'keywords_count' => count($keywords),
        ]);

        $this->researchModel->saveResults($researchId, [
            'ai_response' => $parsed,
            'strategy_note' => $parsed['strategy_note'] ?? null,
            'credits_used' => $cost,
            'ai_time_ms' => $aiElapsed,
            'status' => 'completed',
        ]);

        echo json_encode([
            'success' => true,
            'research_id' => $researchId,
            'clusters_count' => count($clusters),
            'credits_used' => $cost,
            'redirect' => url('/keyword-research/project/' . $projectId . '/architecture/' . $researchId),
        ]);
    }

    public function results(int $projectId, int $researchId): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato.';
            Router::redirect('/keyword-research/projects');
            exit;
        }

        $research = $this->researchModel->find($researchId);

        if (!$research || $research['project_id'] !== $projectId) {
            $_SESSION['_flash']['error'] = 'Ricerca non trovata.';
            Router::redirect('/keyword-research/project/' . $projectId . '/architecture');
            exit;
        }

        $clusters = $this->clusterModel->findByResearch($researchId);

        foreach ($clusters as &$cluster) {
            $cluster['keywords_list'] = $this->clusterModel->getKeywords($cluster['id']);
        }
        unset($cluster);

        $excludedKeywords = $this->clusterModel->getExcludedKeywords($researchId);
        $brief = json_decode($research['brief'], true);

        $totalVolume = 0;
        foreach ($clusters as $c) {
            $totalVolume += $c['total_volume'];
        }

        // Carica progetti Content Creator per il dropdown "Invia a CC"
        $ccProjects = [];
        if (ModuleLoader::isModuleActive('content-creator')) {
            $ccProjects = Database::fetchAll(
                "SELECT id, name FROM cc_projects WHERE user_id = ? ORDER BY name ASC",
                [$user['id']]
            );
        }

        return View::render('keyword-research::architecture/results', [
            'title' => 'Architettura Sito - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'research' => $research,
            'clusters' => $clusters,
            'excludedKeywords' => $excludedKeywords,
            'brief' => $brief,
            'totalVolume' => $totalVolume,
            'ccProjects' => $ccProjects,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Invia cluster selezionati a Content Creator
     */
    public function sendToContentCreator(int $projectId, int $researchId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findAccessible($projectId, $user['id']);
        $research = $this->researchModel->find($researchId);

        if (!$project || !$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Dati non trovati.']);
            return;
        }

        if (!ModuleLoader::isModuleActive('content-creator')) {
            echo json_encode(['success' => false, 'error' => 'Modulo Content Creator non attivo.']);
            return;
        }

        $rawClusterIds = $_POST['cluster_ids'] ?? '[]';
        $clusterIds = is_array($rawClusterIds) ? $rawClusterIds : json_decode($rawClusterIds, true);
        $ccProjectId = (int) ($_POST['cc_project_id'] ?? 0);

        if (empty($clusterIds) || !$ccProjectId) {
            echo json_encode(['success' => false, 'error' => 'Seleziona almeno un cluster e un progetto Content Creator.']);
            return;
        }

        // Verifica progetto content-creator
        $ccProject = Database::fetch(
            "SELECT id FROM cc_projects WHERE id = ? AND user_id = ?",
            [$ccProjectId, $user['id']]
        );

        if (!$ccProject) {
            echo json_encode(['success' => false, 'error' => 'Progetto Content Creator non trovato.']);
            return;
        }

        $added = 0;
        $skipped = 0;

        foreach ($clusterIds as $clusterId) {
            $cluster = $this->clusterModel->find((int) $clusterId);
            if (!$cluster || $cluster['research_id'] !== $researchId) {
                $skipped++;
                continue;
            }

            $url = trim($cluster['suggested_url'] ?? '');
            if (empty($url)) {
                $skipped++;
                continue;
            }

            // Verifica duplicato in cc_urls
            $existing = Database::fetch(
                "SELECT id FROM cc_urls WHERE project_id = ? AND (url = ? OR keyword = ?) LIMIT 1",
                [$ccProjectId, $url, $cluster['main_keyword']]
            );

            if ($existing) {
                $skipped++;
                continue;
            }

            // Raccogli secondary keywords dal cluster
            $keywords = $this->clusterModel->getKeywords($cluster['id']);
            $secondaryKw = [];
            foreach ($keywords as $kw) {
                if (!$kw['is_main']) {
                    $secondaryKw[] = $kw['text'];
                }
            }

            // Estrai slug dalla URL suggerita
            $slug = trim($url, '/');
            if (str_contains($slug, '/')) {
                $slug = basename($slug);
            }

            Database::insert('cc_urls', [
                'project_id' => $ccProjectId,
                'user_id' => $user['id'],
                'url' => $url,
                'slug' => $slug,
                'keyword' => $cluster['main_keyword'],
                'secondary_keywords' => !empty($secondaryKw) ? json_encode($secondaryKw) : null,
                'intent' => $cluster['intent'] ?? null,
                'category' => $cluster['name'],
                'source_type' => 'keyword_research',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $added++;
        }

        echo json_encode([
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'message' => "{$added} pagine aggiunte al progetto Content Creator." . ($skipped > 0 ? " {$skipped} saltate (duplicati o senza URL)." : ''),
        ]);
    }
}
