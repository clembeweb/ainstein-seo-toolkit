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

class ResearchController
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

    /**
     * Wizard 4-step per Research Guidata
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

        // Ricerche passate del progetto
        $researches = $this->researchModel->findByProject($projectId, 'research');

        return View::render('keyword-research::research/wizard', [
            'title' => 'Research Guidata - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'researches' => $researches,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Step 2: Avvia raccolta keyword (crea research + ritorna ID)
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

        $brief = [
            'business' => trim($_POST['business'] ?? ''),
            'target' => trim($_POST['target'] ?? 'B2B'),
            'geography' => trim($_POST['geography'] ?? $project['default_location']),
            'objective' => trim($_POST['objective'] ?? 'SEO'),
            'seeds' => array_filter(array_map('trim', explode(',', $_POST['seeds'] ?? ''))),
            'exclusions' => array_filter(array_map('trim', explode(',', $_POST['exclusions'] ?? ''))),
        ];

        if (empty($brief['business'])) {
            echo json_encode(['success' => false, 'error' => 'La descrizione del business è obbligatoria.']);
            return;
        }

        if (empty($brief['seeds'])) {
            echo json_encode(['success' => false, 'error' => 'Inserisci almeno una seed keyword.']);
            return;
        }

        $researchId = $this->researchModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => 'research',
            'status' => 'collecting',
            'brief' => $brief,
        ]);

        echo json_encode(['success' => true, 'research_id' => $researchId]);
    }

    /**
     * Step 2: SSE stream raccolta keyword
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
            ob_flush();
            flush();
        };

        $brief = json_decode($research['brief'], true);
        $seeds = $brief['seeds'] ?? [];
        $exclusions = $brief['exclusions'] ?? [];
        $location = $brief['geography'] ?? $project['default_location'];
        $lang = $project['default_language'] ?? 'it';

        $service = new KeywordInsightService();

        if (!$service->isConfigured()) {
            $sendEvent('error', ['message' => 'API RapidAPI non configurata.']);
            return;
        }

        $sendEvent('started', [
            'total_seeds' => count($seeds),
            'research_id' => $researchId,
        ]);

        $allKeywords = [];
        $totalApiTime = 0;

        foreach ($seeds as $i => $seed) {
            $sendEvent('seed_started', [
                'seed' => $seed,
                'index' => $i,
                'total' => count($seeds),
            ]);

            $startTime = microtime(true);
            $result = $service->keySuggest($seed, $location, $lang);
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $totalApiTime += $elapsed;

            Database::reconnect();

            if (!$result['success']) {
                $sendEvent('seed_error', [
                    'seed' => $seed,
                    'error' => $result['error'],
                ]);
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

            // Pausa tra chiamate API
            if ($i < count($seeds) - 1) {
                usleep(300000); // 300ms
            }
        }

        // Pre-filtro
        $sendEvent('filtering', ['total_raw' => count($allKeywords)]);

        $minVolume = (int) (ModuleLoader::getModuleSetting('keyword-research', 'min_search_volume') ?? 10);
        $filterResult = $service->filterKeywords(array_values($allKeywords), $exclusions, $minVolume);
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
            'excluded_match' => $filterResult['excluded_stats']['exclusion_match'],
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
        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
            return;
        }

        // Se status è ancora 'collecting', la raccolta non è finita
        if ($research['status'] === 'collecting') {
            echo json_encode(['success' => true, 'status' => 'collecting']);
            return;
        }

        // Status 'draft' = raccolta completata, keyword salvate in ai_response
        $aiResponse = json_decode($research['ai_response'] ?? '{}', true);
        $filtered = $aiResponse['filtered_keywords'] ?? [];
        $excludedStats = $aiResponse['excluded_stats'] ?? ['volume_low' => 0, 'exclusion_match' => 0];

        echo json_encode([
            'success' => true,
            'status' => $research['status'],
            'raw_keywords' => $research['raw_keywords_count'],
            'filtered_keywords' => count($filtered),
            'excluded_volume_low' => $excludedStats['volume_low'],
            'excluded_match' => $excludedStats['exclusion_match'],
            'api_time_ms' => $research['api_time_ms'],
            'keywords' => $filtered,
        ]);
    }

    /**
     * Step 3: AI Clustering
     */
    public function aiAnalyze(int $projectId): void
    {
        set_time_limit(0);
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->find($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato.']);
            return;
        }

        $researchId = (int) ($_POST['research_id'] ?? 0);
        $research = $this->researchModel->find($researchId, $user['id']);

        if (!$research || $research['project_id'] !== $projectId) {
            echo json_encode(['success' => false, 'error' => 'Ricerca non trovata.']);
            return;
        }

        // Keyword dal frontend (JSON)
        $keywordsJson = $_POST['keywords'] ?? '[]';
        $keywords = json_decode($keywordsJson, true);

        if (empty($keywords)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna keyword da analizzare.']);
            return;
        }

        // Costo crediti
        $costKey = count($keywords) > 100 ? 'cost_kr_ai_clustering_large' : 'cost_kr_ai_clustering';
        $cost = (float) (ModuleLoader::getModuleSetting('keyword-research', $costKey) ?? (count($keywords) > 100 ? 5 : 2));

        if (!Credits::hasEnough($user['id'], $cost)) {
            echo json_encode(['success' => false, 'error' => "Crediti insufficienti. Richiesti: {$cost}"]);
            return;
        }

        $brief = json_decode($research['brief'], true);
        $maxClusters = (int) (ModuleLoader::getModuleSetting('keyword-research', 'max_clusters') ?? 8);

        // Prepara prompt
        $kwLines = [];
        foreach ($keywords as $kw) {
            $kwLines[] = "- {$kw['text']} (vol: {$kw['volume']}, comp: {$kw['competition_level']}, intent: {$kw['intent']})";
        }
        $kwListText = implode("\n", $kwLines);

        $systemPrompt = "Sei un esperto SEO italiano specializzato in keyword research e strategia dei contenuti. Rispondi SOLO con JSON valido, senza markdown, senza commenti, senza testo prima o dopo il JSON.";

        $userPrompt = "Analizza queste keyword per il seguente business:
Business: {$brief['business']}
Target: {$brief['target']}
Geografia: {$brief['geography']}
Obiettivo: {$brief['objective']}

KEYWORD DA ANALIZZARE (" . count($keywords) . " keyword):
{$kwListText}

TASK:
1. Rimuovi keyword non pertinenti al business (troppo generiche, non in target, off-topic)
2. Raggruppa le keyword pertinenti in cluster semantici (max {$maxClusters} cluster)
3. Per ogni cluster indica la main keyword (quella con volume piu' alto)
4. Classifica l'intent dominante di ogni cluster

RISPONDI SOLO IN JSON CON QUESTA STRUTTURA ESATTA:
{
  \"clusters\": [
    {
      \"name\": \"Nome cluster descrittivo\",
      \"main_keyword\": \"keyword principale del cluster\",
      \"main_volume\": 1000,
      \"keywords\": [\"kw1\", \"kw2\", \"kw3\"],
      \"total_volume\": 2500,
      \"intent\": \"informational|transactional|commercial|navigational\",
      \"note\": \"Breve nota strategica per questo cluster\"
    }
  ],
  \"excluded\": [\"keyword1 esclusa\", \"keyword2 esclusa\"],
  \"excluded_reason\": \"Motivo generale esclusione\",
  \"strategy_note\": \"Nota strategica complessiva per il business\"
}";

        // Chiamata AI
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

        // Parse JSON response
        $aiContent = $aiResult['result'] ?? '';
        $aiContent = preg_replace('/^```(?:json)?\s*/i', '', $aiContent);
        $aiContent = preg_replace('/\s*```\s*$/', '', $aiContent);
        $aiContent = trim($aiContent);

        $parsed = json_decode($aiContent, true);

        if ($parsed === null) {
            $this->researchModel->updateStatus($researchId, 'error');
            echo json_encode(['success' => false, 'error' => 'Impossibile parsare la risposta AI. ' . json_last_error_msg()]);
            return;
        }

        // Salva cluster e keyword nel DB
        $clusters = $parsed['clusters'] ?? [];
        $excluded = $parsed['excluded'] ?? [];

        // Mappa keyword per lookup veloce
        $kwMap = [];
        foreach ($keywords as $kw) {
            $kwMap[strtolower($kw['text'])] = $kw;
        }

        foreach ($clusters as $i => $cluster) {
            $clusterId = $this->clusterModel->create([
                'research_id' => $researchId,
                'name' => $cluster['name'] ?? 'Cluster ' . ($i + 1),
                'main_keyword' => $cluster['main_keyword'] ?? '',
                'main_volume' => (int) ($cluster['main_volume'] ?? 0),
                'total_volume' => (int) ($cluster['total_volume'] ?? 0),
                'keywords_count' => count($cluster['keywords'] ?? []),
                'intent' => $cluster['intent'] ?? null,
                'note' => $cluster['note'] ?? null,
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

        // Salva keyword escluse
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

        // Consuma crediti
        Credits::consume($user['id'], $cost, 'kr_ai_clustering', 'keyword-research', [
            'research_id' => $researchId,
            'keywords_count' => count($keywords),
        ]);

        // Aggiorna research
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
            'excluded_count' => count($excluded),
            'credits_used' => $cost,
            'redirect' => url('/keyword-research/project/' . $projectId . '/research/' . $researchId),
        ]);
    }

    /**
     * Step 4: Risultati
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

        if (!$research || $research['project_id'] !== $projectId) {
            $_SESSION['_flash']['error'] = 'Ricerca non trovata.';
            Router::redirect('/keyword-research/project/' . $projectId . '/research');
            exit;
        }

        $clusters = $this->clusterModel->findByResearch($researchId);

        // Carica keyword per ogni cluster
        foreach ($clusters as &$cluster) {
            $cluster['keywords_list'] = $this->clusterModel->getKeywords($cluster['id']);
        }
        unset($cluster);

        $excludedKeywords = $this->clusterModel->getExcludedKeywords($researchId);
        $brief = json_decode($research['brief'], true);

        // Calcola totali
        $totalKeywordsInClusters = 0;
        $totalVolume = 0;
        foreach ($clusters as $c) {
            $totalKeywordsInClusters += $c['keywords_count'];
            $totalVolume += $c['total_volume'];
        }

        return View::render('keyword-research::research/results', [
            'title' => 'Risultati Research - ' . $project['name'],
            'user' => $user,
            'project' => $project,
            'research' => $research,
            'clusters' => $clusters,
            'excludedKeywords' => $excludedKeywords,
            'brief' => $brief,
            'totalKeywords' => $totalKeywordsInClusters,
            'totalVolume' => $totalVolume,
            'modules' => ModuleLoader::getUserModules($user['id']),
        ]);
    }

    /**
     * Export CSV
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

        $allKeywords = $this->clusterModel->getAllKeywords($researchId);

        $filename = 'keyword-research-' . $project['id'] . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, ['Keyword', 'Cluster', 'Volume', 'Competition', 'CPC Low', 'CPC High', 'Intent', 'Trend', 'Main', 'Esclusa']);

        foreach ($allKeywords as $kw) {
            fputcsv($output, [
                $kw['text'],
                $kw['cluster_name'] ?? 'Esclusa',
                $kw['volume'],
                $kw['competition_level'],
                $kw['low_bid'],
                $kw['high_bid'],
                $kw['intent'],
                $kw['trend'],
                $kw['is_main'] ? 'Si' : '',
                $kw['is_excluded'] ? 'Si' : '',
            ]);
        }

        fclose($output);
        exit;
    }
}
