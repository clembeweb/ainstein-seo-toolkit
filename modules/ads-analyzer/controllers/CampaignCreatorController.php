<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\CreatorGeneration;
use Modules\AdsAnalyzer\Models\CreatorKeyword;
use Modules\AdsAnalyzer\Models\CreatorCampaign;
use Modules\AdsAnalyzer\Services\CampaignCreatorService;
use Modules\AdsAnalyzer\Services\ContextExtractorService;
use Services\GoogleAdsService;
use Core\Logger;

class CampaignCreatorController
{
    /**
     * Wizard principale (GET)
     */
    public function wizard(int $id): string
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project || $project['type'] !== 'campaign-creator') {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        // Determina step corrente in base allo stato
        $kwGeneration = CreatorGeneration::getLatestCompletedByProjectAndStep($id, 'keywords');
        $campaignGeneration = CreatorGeneration::getLatestCompletedByProjectAndStep($id, 'campaign');
        $keywords = CreatorKeyword::getByProject($id);
        $campaign = CreatorCampaign::getLatestByProject($id);

        $currentStep = 0;
        if ($kwGeneration) $currentStep = 1;
        if ($campaignGeneration && $campaign) $currentStep = 2;

        // Conta keyword
        $kwCounts = CreatorKeyword::countByProject($id);

        // Phase A/B per Step 0
        $inputMode = $project['input_mode'] ?? 'url';
        $hasScrapedData = !empty($project['scraped_content']);
        $phaseAComplete = ($inputMode === 'brief') || $hasScrapedData;

        return View::render('ads-analyzer/campaign-creator/wizard', [
            'title' => $project['name'] . ' - Campaign Creator',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentStep' => $currentStep,
            'keywords' => $keywords,
            'kwCounts' => $kwCounts,
            'campaign' => $campaign,
            'kwGeneration' => $kwGeneration,
            'campaignGeneration' => $campaignGeneration,
            'phaseAComplete' => $phaseAComplete,
            'scrapedContext' => $project['scraped_context'] ?? '',
            'hasScrapedData' => $hasScrapedData,
            'access_role' => $project['access_role'] ?? 'owner',
        ]);
    }

    /**
     * Analizza landing page: scraping + auto-brief (POST AJAX lungo)
     */
    public function analyzeLanding(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $id);

            if (!$project || $project['type'] !== 'campaign-creator') {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['error' => 'Progetto non trovato']);
                exit;
            }

            // Viewer cannot perform write operations
            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
                exit;
            }

            if (empty($project['landing_url'])) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Nessun URL landing configurato']);
                exit;
            }

            // Idempotente: se gia scraping fatto, ritorna dati esistenti
            if (!empty($project['scraped_content'])) {
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'scraped_context' => $project['scraped_context'] ?? '',
                    'brief' => $project['brief'] ?? '',
                    'is_auto_brief' => false,
                    'already_done' => true,
                ]);
                exit;
            }

            // Route credits to project owner
            $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

            $scrapeCost = CampaignCreatorService::getCost('scrape');

            if (!Credits::hasEnough($creditUserId, $scrapeCost)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => "Crediti insufficienti. Richiesti: {$scrapeCost}"]);
                exit;
            }

            session_write_close();

            // Scraping landing page
            $extractor = new ContextExtractorService();
            $scrapeResult = $extractor->extractFromUrl($user['id'], $project['landing_url'], 'campaign');

            Database::reconnect();

            if (!$scrapeResult['success']) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Errore scraping: ' . ($scrapeResult['error'] ?? 'Pagina non accessibile')]);
                exit;
            }

            // Salva contenuto scraping nel progetto
            Project::update($id, [
                'scraped_content' => $scrapeResult['scraped_content'],
                'scraped_context' => $scrapeResult['extracted_context'],
            ]);

            Credits::consume($creditUserId, $scrapeCost, 'creator_scrape', 'ads-analyzer');

            // Rileggi progetto
            $project = Project::find($id);

            // Auto-brief solo se input_mode 'url' e brief vuoto
            $inputMode = $project['input_mode'] ?? 'url';
            $isAutoBrief = false;

            if ($inputMode === 'url' && empty($project['brief']) && !empty($project['scraped_context'])) {
                $autoBrief = CampaignCreatorService::generateBriefFromContext(
                    $user['id'],
                    $project['scraped_context'],
                    $project['scraped_content'] ?? '',
                    $project['campaign_type_gads']
                );

                Database::reconnect();

                if (!empty($autoBrief)) {
                    Project::update($id, ['brief' => $autoBrief]);
                    $project['brief'] = $autoBrief;
                    $isAutoBrief = true;
                }
            }

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'scraped_context' => $project['scraped_context'] ?? '',
                'brief' => $project['brief'] ?? '',
                'is_auto_brief' => $isAutoBrief,
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ai')->error("CampaignCreator analyzeLanding error", ['error' => $e->getMessage()]);
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore interno: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Salva brief editato dall'utente (POST AJAX rapido)
     */
    public function updateBrief(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project || $project['type'] !== 'campaign-creator') {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        $brief = trim($_POST['brief'] ?? '');

        if (mb_strlen($brief) < 20) {
            http_response_code(400);
            echo json_encode(['error' => 'Il brief deve contenere almeno 20 caratteri']);
            exit;
        }

        Project::update($id, ['brief' => $brief]);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Keyword Research a 3 fasi (POST AJAX lungo)
     * 1. AI genera seed keywords
     * 2. API espande seed → keyword reali con volumi
     * 3. AI organizza in ad groups
     */
    public function generateKeywords(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $id);

            if (!$project || $project['type'] !== 'campaign-creator') {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['error' => 'Progetto non trovato']);
                exit;
            }

            // Viewer cannot perform write operations
            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
                exit;
            }

            // Prerequisito: brief o scraped_context devono esistere
            if (empty($project['brief']) && empty($project['scraped_context'])) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Prima analizza la landing page o fornisci un brief']);
                exit;
            }

            // Route credits to project owner
            $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

            $kwCost = CampaignCreatorService::getCost('keywords');

            if (!Credits::hasEnough($creditUserId, $kwCost)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => "Crediti insufficienti. Richiesti: {$kwCost}"]);
                exit;
            }

            session_write_close();

            $generation = CreatorGeneration::create([
                'project_id' => $id,
                'user_id' => $user['id'],
                'step' => 'keywords',
                'status' => 'processing',
            ]);

            // === FASE 1: AI genera seed keywords ===
            $seedResult = CampaignCreatorService::generateSeedKeywords($user['id'], $project);
            Database::reconnect();

            $realKeywords = [];
            $useRealVolumes = false;

            if (!empty($seedResult['success']) && !empty($seedResult['seeds'])) {
                // === FASE 2: API espande seed → keyword reali con volumi ===
                $location = $seedResult['location'] ?? 'US';
                $lang = $seedResult['lang'] ?? 'en';
                $expandResult = CampaignCreatorService::expandAndFilterKeywords($seedResult['seeds'], $location, $lang);
                Database::reconnect();

                if (!empty($expandResult['success']) && !empty($expandResult['keywords'])) {
                    $realKeywords = $expandResult['keywords'];
                    $useRealVolumes = true;
                }
            }

            // === FASE 3: AI organizza keyword ===
            $kwResult = CampaignCreatorService::generateKeywordResearch($user['id'], $project, $realKeywords);
            Database::reconnect();

            if (!empty($kwResult['error'])) {
                CreatorGeneration::updateStatus($generation, 'error', $kwResult['message'] ?? 'Errore AI');
                ob_end_clean();
                http_response_code(500);
                echo json_encode(['error' => $kwResult['message'] ?? 'Errore nella keyword research']);
                exit;
            }

            // Salva risposta AI nella generation
            CreatorGeneration::update($generation, [
                'ai_response' => json_encode($kwResult['data']),
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'credits_used' => $kwCost,
            ]);

            // Mappa keyword reali per lookup veloce (per recuperare volume/CPC)
            $volumeMap = [];
            if ($useRealVolumes) {
                foreach ($realKeywords as $rk) {
                    $volumeMap[mb_strtolower($rk['text'])] = $rk;
                }
            }

            // Cancella keyword precedenti e inserisci nuove con volumi
            CreatorKeyword::deleteByProject($id);
            $savedCount = self::saveKeywordsFromAiResponse($id, $generation, $kwResult['data'], $project['campaign_type_gads'], $volumeMap);

            Credits::consume($creditUserId, $kwCost, 'creator_keywords', 'ads-analyzer');

            // Aggiorna status progetto
            Project::update($id, ['status' => 'analyzing']);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'keywords_count' => $savedCount,
                'has_volumes' => $useRealVolumes,
                'data' => $kwResult['data'],
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ai')->error("CampaignCreator generateKeywords error", ['error' => $e->getMessage()]);
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore interno: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Toggle keyword selezionata (POST AJAX rapido)
     */
    public function toggleKeyword(int $id, int $kwId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);
        if (!$project || $project['type'] !== 'campaign-creator') {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        $kw = CreatorKeyword::find($kwId);
        if (!$kw || (int)$kw['project_id'] !== $id) {
            http_response_code(404);
            echo json_encode(['error' => 'Keyword non trovata']);
            exit;
        }

        CreatorKeyword::toggleSelected($kwId);
        $counts = CreatorKeyword::countByProject($id);

        echo json_encode(['success' => true, 'counts' => $counts]);
        exit;
    }

    /**
     * Aggiorna match type keyword (POST AJAX rapido)
     */
    public function updateMatchType(int $id, int $kwId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);
        if (!$project || $project['type'] !== 'campaign-creator') {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        $matchType = $_POST['match_type'] ?? '';
        if (!in_array($matchType, ['broad', 'phrase', 'exact'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Match type non valido']);
            exit;
        }

        CreatorKeyword::updateMatchType($kwId, $matchType);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Genera campagna completa (POST AJAX lungo)
     */
    public function generateCampaign(int $id): void
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');

        try {
            $user = Auth::user();
            $project = Project::findAccessible($user['id'], $id);

            if (!$project || $project['type'] !== 'campaign-creator') {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['error' => 'Progetto non trovato']);
                exit;
            }

            // Viewer cannot perform write operations
            if (($project['access_role'] ?? 'owner') === 'viewer') {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
                exit;
            }

            // Route credits to project owner
            $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);

            $campaignCost = CampaignCreatorService::getCost('campaign');

            if (!Credits::hasEnough($creditUserId, $campaignCost)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => "Crediti insufficienti. Richiesti: {$campaignCost}"]);
                exit;
            }

            // Prendi keyword selezionate
            $keywords = CreatorKeyword::getSelectedByProject($id);
            if (empty($keywords)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Nessuna keyword selezionata. Torna allo step 2 e seleziona almeno una keyword.']);
                exit;
            }

            session_write_close();

            // Crea generation record
            $generation = CreatorGeneration::create([
                'project_id' => $id,
                'user_id' => $user['id'],
                'step' => 'campaign',
                'status' => 'processing',
            ]);

            $result = CampaignCreatorService::generateCampaign($user['id'], $project, $keywords);

            Database::reconnect();

            if (!empty($result['error'])) {
                CreatorGeneration::updateStatus($generation, 'error', $result['message'] ?? 'Errore AI');
                ob_end_clean();
                http_response_code(500);
                echo json_encode(['error' => $result['message'] ?? 'Errore nella generazione campagna']);
                exit;
            }

            // Salva generation
            CreatorGeneration::update($generation, [
                'ai_response' => json_encode($result['data']),
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'credits_used' => $campaignCost,
            ]);

            // Cancella campagna precedente e crea nuova
            CreatorCampaign::deleteByProject($id);
            CreatorCampaign::create([
                'project_id' => $id,
                'generation_id' => $generation,
                'campaign_type' => $project['campaign_type_gads'],
                'campaign_name' => $result['data']['campaign_name'] ?? $project['name'],
                'assets_json' => $result['data'],
            ]);

            Credits::consume($creditUserId, $campaignCost, 'creator_campaign', 'ads-analyzer');

            // Aggiorna status progetto
            Project::update($id, ['status' => 'completed']);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'data' => $result['data'],
            ]);
            exit;

        } catch (\Exception $e) {
            Logger::channel('ai')->error("CampaignCreator generateCampaign error", ['error' => $e->getMessage()]);
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Errore interno: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Copia testo formattato (POST AJAX rapido)
     */
    public function copyText(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project || $project['type'] !== 'campaign-creator') {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $campaign = CreatorCampaign::getLatestByProject($id);
        if (!$campaign) {
            http_response_code(400);
            echo json_encode(['error' => 'Nessuna campagna generata']);
            exit;
        }

        $keywords = CreatorKeyword::getSelectedByProject($id);
        $section = $_POST['section'] ?? 'all';

        if ($section === 'all') {
            $text = CampaignCreatorService::generateCopyText($campaign, $project['campaign_type_gads'], $keywords);
        } else {
            $text = self::generateSectionText($campaign, $project['campaign_type_gads'], $keywords, $section);
        }

        echo json_encode(['success' => true, 'text' => $text]);
        exit;
    }

    /**
     * Esporta CSV per Google Ads Editor (GET download)
     */
    public function exportCsv(int $id): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project || $project['type'] !== 'campaign-creator') {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }

        $campaign = CreatorCampaign::getLatestByProject($id);
        if (!$campaign) {
            $_SESSION['_flash']['error'] = 'Nessuna campagna generata';
            header('Location: ' . url("/ads-analyzer/projects/{$id}/campaign-creator"));
            exit;
        }

        $keywords = CreatorKeyword::getSelectedByProject($id);
        $budgetLevel = $_GET['budget'] ?? 'moderate';
        if (!in_array($budgetLevel, ['conservative', 'moderate', 'aggressive'])) {
            $budgetLevel = 'moderate';
        }
        $csv = CampaignCreatorService::generateCsvExport($campaign, $project['campaign_type_gads'], $keywords, $project['landing_url'] ?? '', $budgetLevel);

        $filename = 'campaign_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['name']) . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    /**
     * Rigenera (elimina generation + keyword e redirige al wizard)
     */
    public function regenerate(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $id);

        if (!$project || $project['type'] !== 'campaign-creator') {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        $step = $_POST['step'] ?? 'keywords';

        if ($step === 'landing') {
            // Rigenera landing: cancella scraping, brief auto (solo url mode), generations, keywords, campagne
            $inputMode = $project['input_mode'] ?? 'url';
            $updateData = [
                'scraped_content' => null,
                'scraped_context' => null,
                'status' => 'draft',
            ];
            // Reset brief solo se auto-generato (input_mode url)
            if ($inputMode === 'url') {
                $updateData['brief'] = null;
            }
            CreatorGeneration::deleteByProject($id);
            CreatorKeyword::deleteByProject($id);
            CreatorCampaign::deleteByProject($id);
            Project::update($id, $updateData);
        } elseif ($step === 'keywords') {
            // Rigenera keywords: cancella generation kw + campagna (preserva scraping e brief)
            CreatorGeneration::deleteByProject($id);
            CreatorKeyword::deleteByProject($id);
            CreatorCampaign::deleteByProject($id);
            Project::update($id, ['status' => 'draft']);
        } else {
            // Rigenera solo campagna
            CreatorGeneration::deleteByProjectAndStep($id, 'campaign');
            CreatorCampaign::deleteByProject($id);
            Project::update($id, ['status' => 'analyzing']);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Pubblica la campagna generata su Google Ads via API grouped mutate
     * POST AJAX
     */
    public function publishToGoogleAds(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project || $project['type'] !== 'campaign-creator') {
            http_response_code(404);
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        // Viewer cannot perform write operations
        if (($project['access_role'] ?? 'owner') === 'viewer') {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai i permessi per questa operazione']);
            exit;
        }

        if (empty($project['google_ads_customer_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nessun account Google Ads collegato. Collega il tuo account nelle impostazioni del progetto.']);
            exit;
        }

        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        header('Content-Type: application/json');
        session_write_close();

        $customerId = $project['google_ads_customer_id'];

        // Load generated campaign data
        $campaign = CreatorCampaign::getLatestByProject($projectId);
        $keywords = CreatorKeyword::getSelectedByProject($projectId);

        if (!$campaign) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Nessuna campagna generata']);
            exit;
        }

        // Parse campaign JSON data
        $assets = is_string($campaign['assets_json'] ?? null)
            ? (json_decode($campaign['assets_json'], true) ?? [])
            : ($campaign['assets_json'] ?? []);

        // Build grouped mutate operations with temporary resource names
        $loginCustomerId = isset($project['login_customer_id']) ? $project['login_customer_id'] : '';
        $gads = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);

        $mutateOperations = [];
        $tempIdCounter = -1;

        // 1. Campaign Budget
        $budgetTempId = $tempIdCounter--;
        $mutateOperations[] = [
            'campaignBudgetOperation' => [
                'create' => [
                    'resourceName' => "customers/{$customerId}/campaignBudgets/{$budgetTempId}",
                    'name' => $campaign['campaign_name'] . ' Budget',
                    'amountMicros' => ($assets['daily_budget'] ?? 10) * 1000000,
                    'deliveryMethod' => 'STANDARD'
                ]
            ]
        ];

        // 2. Campaign (PAUSED)
        $campaignTempId = $tempIdCounter--;
        $mutateOperations[] = [
            'campaignOperation' => [
                'create' => [
                    'resourceName' => "customers/{$customerId}/campaigns/{$campaignTempId}",
                    'name' => $campaign['campaign_name'],
                    'status' => 'PAUSED',
                    'advertisingChannelType' => strtoupper($campaign['campaign_type'] ?? 'SEARCH'),
                    'campaignBudget' => "customers/{$customerId}/campaignBudgets/{$budgetTempId}",
                ]
            ]
        ];

        // 3. Ad Groups (one per unique ad_group_name in keywords)
        $adGroups = [];
        foreach ($keywords as $kw) {
            $agName = $kw['ad_group_name'] ?? 'Default';
            if (!isset($adGroups[$agName])) {
                $agTempId = $tempIdCounter--;
                $adGroups[$agName] = $agTempId;
                $mutateOperations[] = [
                    'adGroupOperation' => [
                        'create' => [
                            'resourceName' => "customers/{$customerId}/adGroups/{$agTempId}",
                            'name' => $agName,
                            'campaign' => "customers/{$customerId}/campaigns/{$campaignTempId}",
                            'status' => 'ENABLED',
                            'type' => 'SEARCH_STANDARD'
                        ]
                    ]
                ];
            }
        }

        // 4. Keywords (positive)
        foreach ($keywords as $kw) {
            if ($kw['is_negative']) continue;
            $agTempId = $adGroups[$kw['ad_group_name'] ?? 'Default'];
            $mutateOperations[] = [
                'adGroupCriterionOperation' => [
                    'create' => [
                        'adGroup' => "customers/{$customerId}/adGroups/{$agTempId}",
                        'status' => 'ENABLED',
                        'keyword' => [
                            'text' => $kw['keyword'],
                            'matchType' => strtoupper($kw['match_type'] ?? 'BROAD')
                        ]
                    ]
                ]
            ];
        }

        // 5. Negative Keywords (campaign level)
        foreach ($keywords as $kw) {
            if (!$kw['is_negative']) continue;
            $mutateOperations[] = [
                'campaignCriterionOperation' => [
                    'create' => [
                        'campaign' => "customers/{$customerId}/campaigns/{$campaignTempId}",
                        'negative' => true,
                        'keyword' => [
                            'text' => $kw['keyword'],
                            'matchType' => strtoupper($kw['match_type'] ?? 'PHRASE')
                        ]
                    ]
                ]
            ];
        }

        // 6. Responsive Search Ads (from assets)
        if (!empty($assets['ad_groups'])) {
            foreach ($assets['ad_groups'] as $ag) {
                $agName = $ag['name'] ?? 'Default';
                $agTempId = $adGroups[$agName] ?? null;
                if (!$agTempId || empty($ag['ads'])) continue;

                foreach ($ag['ads'] as $ad) {
                    $headlines = array_map(fn($h) => ['text' => $h], $ad['headlines'] ?? []);
                    $descriptions = array_map(fn($d) => ['text' => $d], $ad['descriptions'] ?? []);

                    $mutateOperations[] = [
                        'adGroupAdOperation' => [
                            'create' => [
                                'adGroup' => "customers/{$customerId}/adGroups/{$agTempId}",
                                'status' => 'ENABLED',
                                'ad' => [
                                    'responsiveSearchAd' => [
                                        'headlines' => $headlines,
                                        'descriptions' => $descriptions
                                    ],
                                    'finalUrls' => [$project['landing_url'] ?? $ad['final_url'] ?? '']
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        try {
            $result = $gads->groupedMutate($mutateOperations);
            Database::reconnect();

            // Save publish info
            CreatorCampaign::update($campaign['id'], [
                'published_to_google_ads' => 1,
                'google_ads_campaign_id' => $result['mutateOperationResponses'][1]['campaignResult']['resourceName'] ?? null,
                'published_at' => date('Y-m-d H:i:s')
            ]);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Campagna pubblicata su Google Ads (in pausa)',
                'operations' => count($mutateOperations)
            ]);
        } catch (\Exception $e) {
            Database::reconnect();
            Logger::channel('ai')->error("CampaignCreator publishToGoogleAds error", ['error' => $e->getMessage()]);
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Errore pubblicazione: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ===== HELPER PRIVATI =====

    /**
     * Salva keyword dalla risposta AI con volume reali (se disponibili)
     */
    private static function saveKeywordsFromAiResponse(int $projectId, int $generationId, array $data, string $type, array $volumeMap = []): int
    {
        $count = 0;
        $order = 0;

        if ($type === 'search') {
            // Search: keyword per ad group
            foreach (($data['ad_groups'] ?? []) as $group) {
                $groupName = $group['name'] ?? 'Generale';
                foreach (($group['keywords'] ?? []) as $kw) {
                    $kwText = $kw['text'] ?? $kw['keyword'] ?? '';
                    $vol = $volumeMap[mb_strtolower($kwText)] ?? null;
                    CreatorKeyword::create([
                        'project_id' => $projectId,
                        'generation_id' => $generationId,
                        'keyword' => $kwText,
                        'match_type' => $kw['match_type'] ?? 'broad',
                        'ad_group_name' => $groupName,
                        'intent' => $kw['intent'] ?? ($vol['intent'] ?? null),
                        'search_volume' => $vol['volume'] ?? null,
                        'cpc' => $vol ? round(($vol['high_bid'] ?? $vol['low_bid'] ?? 0), 2) : null,
                        'competition_level' => $vol['competition_level'] ?? null,
                        'competition_index' => $vol['competition_index'] ?? null,
                        'is_negative' => 0,
                        'is_selected' => 1,
                        'sort_order' => $order++,
                    ]);
                    $count++;
                }
            }
        } else {
            // PMax: search themes
            foreach (($data['search_themes'] ?? []) as $theme) {
                $kwText = $theme['text'] ?? $theme['keyword'] ?? '';
                $vol = $volumeMap[mb_strtolower($kwText)] ?? null;
                CreatorKeyword::create([
                    'project_id' => $projectId,
                    'generation_id' => $generationId,
                    'keyword' => $kwText,
                    'match_type' => 'broad',
                    'ad_group_name' => null,
                    'intent' => $vol['intent'] ?? null,
                    'search_volume' => $vol['volume'] ?? null,
                    'cpc' => $vol ? round(($vol['high_bid'] ?? $vol['low_bid'] ?? 0), 2) : null,
                    'competition_level' => $vol['competition_level'] ?? null,
                    'competition_index' => $vol['competition_index'] ?? null,
                    'is_negative' => 0,
                    'is_selected' => 1,
                    'sort_order' => $order++,
                ]);
                $count++;
            }
        }

        // Negative keywords (entrambi i tipi) — no volumi
        foreach (($data['negative_keywords'] ?? []) as $nk) {
            CreatorKeyword::create([
                'project_id' => $projectId,
                'generation_id' => $generationId,
                'keyword' => $nk['text'] ?? $nk['keyword'] ?? '',
                'match_type' => $nk['match_type'] ?? 'phrase',
                'ad_group_name' => null,
                'intent' => null,
                'is_negative' => 1,
                'is_selected' => 1,
                'reason' => $nk['reason'] ?? null,
                'sort_order' => $order++,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Genera testo per una singola sezione
     */
    private static function generateSectionText(array $campaign, string $type, array $keywords, string $section): string
    {
        $assets = $campaign['assets'] ?? [];
        $text = '';

        switch ($section) {
            case 'headlines':
                foreach (($assets['headlines'] ?? []) as $i => $h) {
                    $text .= ($i + 1) . ". {$h}\n";
                }
                break;

            case 'long_headlines':
                foreach (($assets['long_headlines'] ?? []) as $i => $lh) {
                    $text .= ($i + 1) . ". {$lh}\n";
                }
                break;

            case 'descriptions':
                foreach (($assets['descriptions'] ?? []) as $i => $d) {
                    $text .= ($i + 1) . ". {$d}\n";
                }
                break;

            case 'keywords':
                $positiveKw = array_filter($keywords, fn($kw) => !$kw['is_negative']);
                if ($type === 'search') {
                    $groups = [];
                    foreach ($positiveKw as $kw) {
                        $groups[$kw['ad_group_name'] ?? 'Generale'][] = $kw;
                    }
                    foreach ($groups as $name => $kws) {
                        $text .= "Ad Group: {$name}\n";
                        foreach ($kws as $kw) {
                            $text .= self::formatMatchType($kw['keyword'], $kw['match_type'] ?? 'broad') . "\n";
                        }
                        $text .= "\n";
                    }
                } else {
                    foreach ($positiveKw as $kw) {
                        $text .= $kw['keyword'] . "\n";
                    }
                }
                break;

            case 'negatives':
                $negatives = array_filter($keywords, fn($kw) => $kw['is_negative']);
                foreach ($negatives as $nk) {
                    $text .= self::formatMatchType($nk['keyword'], $nk['match_type'] ?? 'phrase') . "\n";
                }
                break;

            case 'sitelinks':
                foreach (($assets['sitelinks'] ?? []) as $sl) {
                    $text .= ($sl['title'] ?? '') . "\n";
                    $text .= "  " . ($sl['desc1'] ?? '') . "\n";
                    $text .= "  " . ($sl['desc2'] ?? '') . "\n\n";
                }
                break;

            case 'callouts':
                foreach (($assets['callouts'] ?? []) as $c) {
                    $text .= $c . "\n";
                }
                break;

            case 'snippets':
                foreach (($assets['structured_snippets'] ?? []) as $snippet) {
                    $text .= ($snippet['header'] ?? '') . ": " . implode(', ', $snippet['values'] ?? []) . "\n";
                }
                break;
        }

        return $text;
    }

    private static function formatMatchType(string $keyword, string $matchType): string
    {
        return match ($matchType) {
            'exact' => "[{$keyword}]",
            'phrase' => "\"{$keyword}\"",
            default => $keyword,
        };
    }
}
