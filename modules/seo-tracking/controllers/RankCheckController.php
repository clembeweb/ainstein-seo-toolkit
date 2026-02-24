<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\RankCheck;
use Modules\SeoTracking\Models\RankJob;
use Modules\SeoTracking\Models\RankQueue;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\KeywordPosition;
use Modules\SeoTracking\Services\RankCheckerService;

/**
 * RankCheckController
 * Gestisce la verifica posizioni SERP reali via SerpAPI
 */
class RankCheckController
{
    /** Costo crediti per rank check: livello Base (1 cr) */
    private const CREDIT_COST = 1; // Allineato a livello Base - valore letto da getCost() dove possibile
    private const MAX_BULK_KEYWORDS = 10;

    /**
     * Vista principale - Form + storico recente
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: /seo-tracking');
            exit;
        }

        $rankCheckModel = new RankCheck();
        $rankCheckerService = new RankCheckerService();

        // Ultimi check
        $recentChecks = $rankCheckModel->findByProject($projectId, 20);

        // Statistiche
        $stats = $rankCheckModel->getStats($projectId);

        // Verifica configurazione provider SERP
        $serpApiConfigured = $rankCheckerService->isConfigured();
        $providersInfo = $rankCheckerService->getProvidersInfo();

        // Dominio default dal progetto
        $defaultDomain = $this->extractDomain($project['domain'] ?? '');

        return View::render('seo-tracking/rank-check/index', [
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'recentChecks' => $recentChecks,
            'stats' => $stats,
            'serpApiConfigured' => $serpApiConfigured,
            'providersInfo' => $providersInfo,
            'defaultDomain' => $defaultDomain,
            'creditCost' => self::CREDIT_COST,
            'maxBulkKeywords' => self::MAX_BULK_KEYWORDS,
            'userCredits' => Credits::getBalance($user['id']),
        ]);
    }

    /**
     * Check singolo (POST AJAX)
     */
    public function check(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        // Input
        $keyword = trim($_POST['keyword'] ?? '');
        $targetDomain = trim($_POST['target_domain'] ?? '');
        $locationCode = $_POST['location'] ?? 'IT'; // Codice paese (IT, US, DE, etc.)
        $device = $_POST['device'] ?? 'desktop';

        // Validazione
        if (empty($keyword)) {
            echo json_encode(['success' => false, 'error' => 'Keyword obbligatoria']);
            exit;
        }

        if (empty($targetDomain)) {
            echo json_encode(['success' => false, 'error' => 'Dominio target obbligatorio']);
            exit;
        }

        // Verifica crediti
        if (!Credits::hasEnough($user['id'], self::CREDIT_COST)) {
            echo json_encode([
                'success' => false,
                'error' => 'Crediti insufficienti. Necessari: ' . self::CREDIT_COST
            ]);
            exit;
        }

        try {
            $service = new RankCheckerService();

            if (!$service->isConfigured()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'SerpAPI non configurata. Contatta l\'amministratore.'
                ]);
                exit;
            }

            // Esegui check con nuovo formato options
            $result = $service->checkPosition($keyword, $targetDomain, [
                'location_code' => $locationCode,
                'device' => $device,
            ]);

            // Recupera posizione GSC per confronto
            $gscPosition = $this->getGscPosition($projectId, $keyword);
            $positionDiff = null;

            if ($result['found'] && $gscPosition !== null) {
                $positionDiff = $result['position'] - $gscPosition;
            }

            // Salva nel database
            $rankCheckModel = new RankCheck();
            $checkId = $rankCheckModel->create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'keyword' => $keyword,
                'target_domain' => $targetDomain,
                'location' => $result['location'] ?? $locationCode,
                'language' => $result['language'] ?? 'it',
                'device' => $device,
                'serp_position' => $result['found'] ? $result['position'] : null,
                'serp_url' => $result['url'],
                'serp_title' => $result['title'],
                'serp_snippet' => $result['snippet'],
                'gsc_position' => $gscPosition,
                'position_diff' => $positionDiff,
                'total_organic_results' => $result['total_organic_results'],
                'credits_used' => self::CREDIT_COST,
            ]);

            // Aggiorna last_position nella tabella keywords se trovato
            if ($result['found'] && $result['position'] !== null) {
                $keywordModel = new Keyword();
                $existingKeyword = $keywordModel->findByKeyword($projectId, $keyword);
                if ($existingKeyword) {
                    $keywordModel->update($existingKeyword['id'], [
                        'last_position' => $result['position'],
                        'last_updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Upsert snapshot giornaliero per storico posizioni
                    $kpModel = new KeywordPosition();
                    $kpModel->upsert([
                        'project_id' => $projectId,
                        'keyword_id' => $existingKeyword['id'],
                        'date' => date('Y-m-d'),
                        'avg_position' => $result['position'],
                    ]);
                }
            }

            // Scala crediti
            Credits::consume($user['id'], self::CREDIT_COST, 'rank_check', 'seo-tracking', [
                'project_id' => $projectId,
                'keyword' => $keyword,
                'check_id' => $checkId,
            ]);

            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $checkId,
                    'found' => $result['found'],
                    'serp_position' => $result['position'],
                    'serp_url' => $result['url'],
                    'serp_title' => $result['title'],
                    'serp_snippet' => $result['snippet'],
                    'gsc_position' => $gscPosition,
                    'position_diff' => $positionDiff,
                    'keyword' => $keyword,
                    'device' => $device,
                    'credits_used' => self::CREDIT_COST,
                    'new_balance' => Credits::getBalance($user['id']),
                ]
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Errore durante il check: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Check bulk - max 10 keyword (POST AJAX)
     */
    public function checkBulk(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        // Input
        $keywordsRaw = trim($_POST['keywords'] ?? '');
        $targetDomain = trim($_POST['target_domain'] ?? '');
        $locationCode = $_POST['location'] ?? 'IT'; // Codice paese (IT, US, DE, etc.)
        $device = $_POST['device'] ?? 'desktop';

        // Parsing keywords (una per riga)
        $keywords = array_filter(
            array_map('trim', explode("\n", $keywordsRaw)),
            fn($k) => !empty($k)
        );
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, self::MAX_BULK_KEYWORDS);

        if (empty($keywords)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna keyword valida']);
            exit;
        }

        if (empty($targetDomain)) {
            echo json_encode(['success' => false, 'error' => 'Dominio target obbligatorio']);
            exit;
        }

        $totalCost = count($keywords) * self::CREDIT_COST;

        // Verifica crediti
        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Necessari: {$totalCost} (hai: " . Credits::getBalance($user['id']) . ")"
            ]);
            exit;
        }

        try {
            $service = new RankCheckerService();

            if (!$service->isConfigured()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'SerpAPI non configurata. Contatta l\'amministratore.'
                ]);
                exit;
            }

            $rankCheckModel = new RankCheck();
            $results = [];

            foreach ($keywords as $keyword) {
                try {
                    $result = $service->checkPosition($keyword, $targetDomain, [
                        'location_code' => $locationCode,
                        'device' => $device,
                    ]);

                    // Recupera posizione GSC
                    $gscPosition = $this->getGscPosition($projectId, $keyword);
                    $positionDiff = null;

                    if ($result['found'] && $gscPosition !== null) {
                        $positionDiff = $result['position'] - $gscPosition;
                    }

                    // Salva
                    $checkId = $rankCheckModel->create([
                        'project_id' => $projectId,
                        'user_id' => $user['id'],
                        'keyword' => $keyword,
                        'target_domain' => $targetDomain,
                        'location' => $result['location'] ?? $locationCode,
                        'language' => $result['language'] ?? 'it',
                        'device' => $device,
                        'serp_position' => $result['found'] ? $result['position'] : null,
                        'serp_url' => $result['url'],
                        'serp_title' => $result['title'],
                        'serp_snippet' => $result['snippet'],
                        'gsc_position' => $gscPosition,
                        'position_diff' => $positionDiff,
                        'total_organic_results' => $result['total_organic_results'],
                        'credits_used' => self::CREDIT_COST,
                    ]);

                    // Aggiorna last_position nella tabella keywords se trovato
                    if ($result['found'] && $result['position'] !== null) {
                        $keywordModel = new Keyword();
                        $existingKeyword = $keywordModel->findByKeyword($projectId, $keyword);
                        if ($existingKeyword) {
                            $keywordModel->update($existingKeyword['id'], [
                                'last_position' => $result['position'],
                                'last_updated_at' => date('Y-m-d H:i:s'),
                            ]);

                            // Upsert snapshot giornaliero per storico posizioni
                            $kpModel = new KeywordPosition();
                            $kpModel->upsert([
                                'project_id' => $projectId,
                                'keyword_id' => $existingKeyword['id'],
                                'date' => date('Y-m-d'),
                                'avg_position' => $result['position'],
                            ]);
                        }
                    }

                    // Scala crediti per questa keyword
                    Credits::consume($user['id'], self::CREDIT_COST, 'rank_check', 'seo-tracking', [
                        'project_id' => $projectId,
                        'keyword' => $keyword,
                        'check_id' => $checkId,
                        'bulk' => true,
                    ]);

                    $results[] = [
                        'keyword' => $keyword,
                        'success' => true,
                        'found' => $result['found'],
                        'serp_position' => $result['position'],
                        'serp_url' => $result['url'],
                        'gsc_position' => $gscPosition,
                        'position_diff' => $positionDiff,
                    ];

                } catch (\Exception $e) {
                    $results[] = [
                        'keyword' => $keyword,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }

                // Breve pausa per non sovraccaricare l'API
                usleep(200000); // 200ms
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'results' => $results,
                    'total_checked' => count($results),
                    'credits_used' => count(array_filter($results, fn($r) => $r['success'])) * self::CREDIT_COST,
                    'new_balance' => Credits::getBalance($user['id']),
                ]
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Errore durante il check bulk: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Storico completo con filtri e paginazione (GET)
     */
    public function history(int $projectId): string
    {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->findAccessible($projectId, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: /seo-tracking');
            exit;
        }

        $rankCheckModel = new RankCheck();

        // Paginazione
        $perPage = 25;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Filtri
        $filters = [
            'keyword' => $_GET['keyword'] ?? '',
            'device' => $_GET['device'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'found_only' => isset($_GET['found_only']),
            'not_found_only' => isset($_GET['not_found_only']),
        ];

        // Conta totale con filtri
        $totalCount = $rankCheckModel->countWithFilters($projectId, $filters);
        $totalPages = ceil($totalCount / $perPage);

        // Aggiungi limit e offset ai filtri per la query
        $filters['limit'] = $perPage;
        $filters['offset'] = $offset;

        $checks = $rankCheckModel->search($projectId, $filters);
        $stats = $rankCheckModel->getStats($projectId);

        return View::render('seo-tracking/rank-check/history', [
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'checks' => $checks,
            'stats' => $stats,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Ottieni posizione media GSC per una keyword (ultimi 7 giorni)
     */
    private function getGscPosition(int $projectId, string $keyword): ?float
    {
        $result = Database::fetch(
            "SELECT AVG(position) as avg_position
             FROM st_gsc_data
             WHERE project_id = ?
               AND query = ?
               AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND impressions > 0",
            [$projectId, $keyword]
        );

        if ($result && $result['avg_position'] !== null) {
            return round((float) $result['avg_position'], 2);
        }

        return null;
    }

    /**
     * Estrai dominio da URL
     */
    private function extractDomain(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        // Aggiungi protocollo se mancante per parse_url
        if (!preg_match('#^https?://#', $url)) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST) ?? '';

        // Rimuovi www.
        return preg_replace('/^www\./i', '', $host);
    }

    /**
     * API: Carica keyword da GSC per il progetto
     */
    public function getGscKeywords(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $filter = $_GET['filter'] ?? 'top_clicks';
        $limit = min((int) ($_GET['limit'] ?? 20), 100);
        $minPosition = (int) ($_GET['min_position'] ?? 1);
        $maxPosition = (int) ($_GET['max_position'] ?? 100);

        $orderBy = match ($filter) {
            'top_impressions' => 'SUM(impressions) DESC',
            'top_position' => 'AVG(position) ASC',
            'worst_position' => 'AVG(position) DESC',
            default => 'SUM(clicks) DESC'
        };

        $keywords = Database::fetchAll(
            "SELECT
                query as keyword,
                ROUND(AVG(position), 1) as gsc_position,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions
             FROM st_gsc_data
             WHERE project_id = ?
               AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY query
             HAVING AVG(position) BETWEEN ? AND ?
             ORDER BY {$orderBy}
             LIMIT ?",
            [$projectId, $minPosition, $maxPosition, $limit]
        );

        $projectDomain = $this->extractDomain($project['domain'] ?? '');

        echo json_encode([
            'success' => true,
            'keywords' => $keywords,
            'count' => count($keywords),
            'filter' => $filter,
            'project_domain' => $projectDomain
        ]);
        exit;
    }

    /**
     * API: Check singolo keyword (per bulk sequenziale da frontend)
     */
    public function checkSingle(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['error' => 'Progetto non trovato']);
            exit;
        }

        $keyword = trim($_POST['keyword'] ?? '');
        $targetDomain = trim($_POST['target_domain'] ?? '');
        if (empty($targetDomain)) {
            $targetDomain = $this->extractDomain($project['domain'] ?? '');
        }
        $locationCode = $_POST['location'] ?? 'IT'; // Codice paese (IT, US, DE, etc.)
        $device = $_POST['device'] ?? 'desktop';
        $gscPosition = isset($_POST['gsc_position']) && $_POST['gsc_position'] !== ''
            ? (float) $_POST['gsc_position']
            : null;

        if (empty($keyword)) {
            echo json_encode(['error' => 'Keyword richiesta']);
            exit;
        }

        // Verifica crediti
        $cost = self::CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $cost)) {
            echo json_encode(['error' => 'Crediti insufficienti']);
            exit;
        }

        try {
            $service = new RankCheckerService();

            if (!$service->isConfigured()) {
                echo json_encode(['error' => 'Nessun provider SERP configurato. Vai in Admin > Impostazioni.']);
                exit;
            }

            $result = $service->checkPosition($keyword, $targetDomain, [
                'location_code' => $locationCode,
                'device' => $device,
            ]);
            $providerUsed = $result['provider'] ?? $service->getLastProvider();

            // Se gsc_position non passata, cerca in DB
            if ($gscPosition === null) {
                $gscPosition = $this->getGscPosition($projectId, $keyword);
            }

            $positionDiff = null;
            if ($result['found'] && $result['position'] !== null && $gscPosition !== null) {
                $positionDiff = round($result['position'] - $gscPosition, 1);
            }

            // Salva risultato
            $model = new RankCheck();
            $model->create([
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'keyword' => $keyword,
                'target_domain' => $targetDomain,
                'location' => $result['location'] ?? $locationCode,
                'language' => $result['language'] ?? 'it',
                'device' => $device,
                'serp_position' => $result['found'] ? $result['position'] : null,
                'serp_url' => $result['url'],
                'serp_title' => $result['title'],
                'serp_snippet' => $result['snippet'],
                'gsc_position' => $gscPosition,
                'position_diff' => $positionDiff,
                'total_organic_results' => $result['total_organic_results'] ?? null,
                'credits_used' => $cost,
            ]);

            // Aggiorna last_position nella tabella keywords se trovato
            if ($result['found'] && $result['position'] !== null) {
                $keywordModel = new \Modules\SeoTracking\Models\Keyword();
                $existingKeyword = $keywordModel->findByKeyword($projectId, $keyword);
                if ($existingKeyword) {
                    $keywordModel->update($existingKeyword['id'], [
                        'last_position' => $result['position'],
                        'last_updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            // Scala crediti
            Credits::consume($user['id'], $cost, 'rank_check', 'seo-tracking', [
                'keyword' => $keyword,
                'project_id' => $projectId,
            ]);

            echo json_encode([
                'success' => true,
                'keyword' => $keyword,
                'serp_position' => $result['found'] ? $result['position'] : null,
                'gsc_position' => $gscPosition,
                'position_diff' => $positionDiff,
                'serp_url' => $result['url'],
                'found' => $result['found'],
                'provider' => $providerUsed
            ]);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * API: Import keyword manuali (textarea o CSV)
     */
    public function importKeywords(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $keywords = [];

        // Modalità 1: Textarea (una keyword per riga)
        if (!empty($_POST['keywords_text'])) {
            $lines = explode("\n", $_POST['keywords_text']);
            foreach ($lines as $line) {
                $kw = trim($line);
                if (!empty($kw) && strlen($kw) <= 500) {
                    $keywords[] = $kw;
                }
            }
        }

        // Modalità 2: File CSV
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');

            if ($handle) {
                // Rileva se prima riga è header
                $firstRow = fgetcsv($handle);
                if ($firstRow) {
                    // Se non sembra un header (es. contiene "keyword" case-insensitive), aggiungila
                    $firstVal = strtolower(trim($firstRow[0] ?? ''));
                    if (!in_array($firstVal, ['keyword', 'keywords', 'query', 'parola chiave', 'kw'])) {
                        $kw = trim($firstRow[0] ?? '');
                        if (!empty($kw) && strlen($kw) <= 500) {
                            $keywords[] = $kw;
                        }
                    }
                }

                // Leggi resto del file
                while (($row = fgetcsv($handle)) !== false) {
                    $kw = trim($row[0] ?? '');
                    if (!empty($kw) && strlen($kw) <= 500) {
                        $keywords[] = $kw;
                    }
                }
                fclose($handle);
            }
        }

        // Rimuovi duplicati e limita
        $keywords = array_unique($keywords);
        $maxKeywords = 100;
        $total = count($keywords);
        $keywords = array_slice($keywords, 0, $maxKeywords);

        if (empty($keywords)) {
            echo json_encode([
                'success' => false,
                'error' => 'Nessuna keyword valida trovata'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'keywords' => array_values($keywords),
            'count' => count($keywords),
            'total_found' => $total,
            'truncated' => $total > $maxKeywords
        ]);
        exit;
    }

    // =========================================
    // BACKGROUND JOB PROCESSING
    // =========================================

    /**
     * Avvia un job di rank check in background
     * POST /seo-tracking/project/{id}/rank-check/start-job
     */
    public function startJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        // Input
        $keywordIds = $_POST['keyword_ids'] ?? [];
        $device = $_POST['device'] ?? 'desktop';

        // Se passate come stringa comma-separated
        if (is_string($keywordIds)) {
            $keywordIds = array_filter(array_map('intval', explode(',', $keywordIds)));
        }

        if (empty($keywordIds)) {
            echo json_encode(['success' => false, 'error' => 'Nessuna keyword selezionata']);
            exit;
        }

        // Verifica crediti
        $totalCost = count($keywordIds) * self::CREDIT_COST;
        if (!Credits::hasEnough($user['id'], $totalCost)) {
            echo json_encode([
                'success' => false,
                'error' => "Crediti insufficienti. Necessari: {$totalCost}, disponibili: " . Credits::getBalance($user['id'])
            ]);
            exit;
        }

        // Verifica che non ci sia già un job attivo per questo progetto
        $jobModel = new RankJob();
        $activeJob = $jobModel->getActiveForProject($projectId);
        if ($activeJob) {
            echo json_encode([
                'success' => false,
                'error' => 'Esiste già un job in esecuzione per questo progetto',
                'active_job_id' => $activeJob['id']
            ]);
            exit;
        }

        // Verifica service configurato
        $service = new RankCheckerService();
        if (!$service->isConfigured()) {
            echo json_encode(['success' => false, 'error' => 'Nessun provider SERP configurato']);
            exit;
        }

        // Crea il job
        $jobId = $jobModel->create([
            'project_id' => $projectId,
            'user_id' => $user['id'],
            'type' => RankJob::TYPE_MANUAL,
            'keywords_requested' => count($keywordIds),
        ]);

        // Aggiungi keywords alla coda
        $queueModel = new RankQueue();
        $inserted = $queueModel->addBulkForJob($projectId, $jobId, $keywordIds, $device);

        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'keywords_queued' => $inserted,
            'estimated_credits' => $inserted * self::CREDIT_COST,
        ]);
        exit;
    }

    /**
     * SSE Stream per progress in tempo reale
     * GET /seo-tracking/project/{id}/rank-check/stream?job_id=X
     *
     * Elabora le keyword in coda e invia eventi SSE
     */
    public function processStream(int $projectId): void
    {
        // Verifica auth manualmente per evitare redirect su SSE
        $user = Auth::user();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }

        $project = (new Project())->findAccessible($projectId, $user['id']);
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            exit('Progetto non trovato');
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            header('HTTP/1.1 400 Bad Request');
            exit('job_id richiesto');
        }

        $jobModel = new RankJob();
        $job = $jobModel->findByUser($jobId, $user['id']);
        if (!$job || $job['project_id'] != $projectId) {
            header('HTTP/1.1 404 Not Found');
            exit('Job non trovato');
        }

        // Setup SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Chiudi la sessione PRIMA del loop per non bloccare altre richieste
        session_write_close();

        // Funzione helper per inviare eventi SSE
        $sendEvent = function (string $event, array $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data) . "\n\n";
            ob_flush();
            flush();
        };

        // Avvia il job se pending
        if ($job['status'] === RankJob::STATUS_PENDING) {
            $jobModel->start($jobId);
            $sendEvent('started', [
                'job_id' => $jobId,
                'total_keywords' => $job['keywords_requested'],
            ]);
        }

        // Setup services
        $service = new RankCheckerService();
        $queueModel = new RankQueue();
        $rankCheckModel = new RankCheck();

        $completed = 0;
        $failed = 0;
        $found = 0;
        $creditsUsed = 0;

        // Loop di elaborazione
        while (true) {
            // Riconnetti DB per evitare timeout
            Database::reconnect();

            // Verifica cancellazione
            if ($jobModel->isCancelled($jobId)) {
                $sendEvent('cancelled', [
                    'job_id' => $jobId,
                    'completed' => $completed,
                    'message' => 'Job annullato dall\'utente',
                ]);

                // Notifica annullamento
                try {
                    Database::reconnect();
                    \Services\NotificationService::send($user['id'], 'operation_failed',
                        'Rank check annullato', [
                        'icon' => 'exclamation-triangle',
                        'color' => 'red',
                        'action_url' => '/seo-tracking/projects/' . $projectId . '/keywords',
                        'body' => 'Il rank check e stato annullato dall\'utente.',
                        'data' => ['module' => 'seo-tracking', 'project_id' => $projectId],
                    ]);
                } catch (\Exception $e) {
                    // silently fail
                }

                break;
            }

            // Ottieni prossimo item dalla coda
            $item = $queueModel->getNextPendingForJob($jobId);

            if (!$item) {
                // Coda vuota - job completato
                $jobModel->complete($jobId);
                $sendEvent('completed', [
                    'job_id' => $jobId,
                    'total_completed' => $completed,
                    'total_failed' => $failed,
                    'total_found' => $found,
                    'credits_used' => $creditsUsed,
                ]);

                // Notifica completamento
                try {
                    Database::reconnect();
                    $projectName = $project['domain'] ?? "Progetto #{$projectId}";
                    \Services\NotificationService::send($user['id'], 'operation_completed',
                        "Rank check completato per {$projectName}", [
                        'icon' => 'check-circle',
                        'color' => 'emerald',
                        'action_url' => '/seo-tracking/projects/' . $projectId . '/keywords',
                        'body' => "Verificate {$completed} keyword, {$found} trovate in SERP.",
                        'data' => ['module' => 'seo-tracking', 'project_id' => $projectId],
                    ]);
                } catch (\Exception $e) {
                    // Non-blocking: notification failure should not affect the operation
                }

                break;
            }

            // Marca come in elaborazione
            $queueModel->updateStatus($item['id'], 'processing');

            // Aggiorna job con keyword corrente
            $jobModel->updateProgress($jobId, [
                'current_keyword_id' => $item['keyword_id'],
                'current_keyword' => $item['keyword'],
            ]);

            // Invia evento progress
            $sendEvent('progress', [
                'job_id' => $jobId,
                'current_keyword' => $item['keyword'],
                'completed' => $completed,
                'total' => $job['keywords_requested'],
                'percent' => round(($completed / $job['keywords_requested']) * 100),
            ]);

            try {
                // Esegui check SERP
                $result = $service->checkPosition($item['keyword'], $item['target_domain'], [
                    'location_code' => $item['location_code'] ?? 'IT',
                    'device' => $item['device'] ?? 'desktop',
                ]);

                Database::reconnect();

                // Recupera posizione GSC per confronto
                $gscPosition = $this->getGscPosition($projectId, $item['keyword']);
                $positionDiff = null;
                if ($result['found'] && $gscPosition !== null) {
                    $positionDiff = $result['position'] - $gscPosition;
                }

                // Salva in st_rank_checks
                $checkId = $rankCheckModel->create([
                    'project_id' => $projectId,
                    'user_id' => $user['id'],
                    'keyword' => $item['keyword'],
                    'target_domain' => $item['target_domain'],
                    'location' => $result['location'] ?? $item['location_code'],
                    'language' => $result['language'] ?? 'it',
                    'device' => $item['device'],
                    'serp_position' => $result['found'] ? $result['position'] : null,
                    'serp_url' => $result['url'],
                    'serp_title' => $result['title'],
                    'serp_snippet' => $result['snippet'],
                    'gsc_position' => $gscPosition,
                    'position_diff' => $positionDiff,
                    'total_organic_results' => $result['total_organic_results'] ?? null,
                    'credits_used' => self::CREDIT_COST,
                ]);

                // Aggiorna last_position nella tabella keywords
                if ($result['found'] && $result['position'] !== null && $item['keyword_id']) {
                    $keywordModel = new Keyword();
                    $keywordModel->update($item['keyword_id'], [
                        'last_position' => $result['position'],
                        'last_updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Upsert snapshot giornaliero per storico posizioni
                    $kpModel = new KeywordPosition();
                    $kpModel->upsert([
                        'project_id' => $projectId,
                        'keyword_id' => $item['keyword_id'],
                        'date' => date('Y-m-d'),
                        'avg_position' => $result['position'],
                    ]);
                }

                // Marca coda come completata
                $queueModel->markCompleted($item['id'], $result['position'], $result['url'], $checkId);

                // Scala crediti
                Credits::consume($user['id'], self::CREDIT_COST, 'rank_check', 'seo-tracking', [
                    'keyword' => $item['keyword'],
                    'project_id' => $projectId,
                    'job_id' => $jobId,
                ]);

                $completed++;
                $creditsUsed += self::CREDIT_COST;
                if ($result['found']) {
                    $found++;
                }

                // Aggiorna job
                $jobModel->incrementCompleted($jobId, $result['found']);
                $jobModel->addCreditsUsed($jobId, self::CREDIT_COST);

                // Invia evento keyword completata
                $sendEvent('keyword_completed', [
                    'keyword' => $item['keyword'],
                    'found' => $result['found'],
                    'position' => $result['position'],
                    'url' => $result['url'],
                    'gsc_position' => $gscPosition,
                    'position_diff' => $positionDiff,
                    'provider' => $result['provider'] ?? $service->getLastProvider(),
                    'credits_remaining' => Credits::getBalance($user['id']),
                ]);

            } catch (\Exception $e) {
                Database::reconnect();

                // Marca come errore
                $queueModel->markError($item['id'], $e->getMessage());
                $jobModel->incrementFailed($jobId);
                $failed++;

                $sendEvent('keyword_error', [
                    'keyword' => $item['keyword'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Pausa tra le chiamate per rate limiting
            usleep(500000); // 500ms
        }

        exit;
    }

    /**
     * Polling fallback per status job
     * GET /seo-tracking/project/{id}/rank-check/job-status?job_id=X
     */
    public function jobStatus(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $jobId = (int) ($_GET['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            exit;
        }

        $jobModel = new RankJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'job' => $jobModel->getJobResponse($jobId),
        ]);
        exit;
    }

    /**
     * Annulla un job in esecuzione
     * POST /seo-tracking/project/{id}/rank-check/cancel-job
     */
    public function cancelJob(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['success' => false, 'error' => 'job_id richiesto']);
            exit;
        }

        $jobModel = new RankJob();
        $job = $jobModel->findByUser($jobId, $user['id']);

        if (!$job || $job['project_id'] != $projectId) {
            echo json_encode(['success' => false, 'error' => 'Job non trovato']);
            exit;
        }

        if (!in_array($job['status'], [RankJob::STATUS_PENDING, RankJob::STATUS_RUNNING])) {
            echo json_encode(['success' => false, 'error' => 'Il job non può essere annullato']);
            exit;
        }

        $cancelled = $jobModel->cancel($jobId);

        echo json_encode([
            'success' => $cancelled,
            'message' => $cancelled ? 'Job annullato' : 'Impossibile annullare il job',
        ]);
        exit;
    }

    /**
     * Ottieni le keyword tracciate per avviare un job
     * GET /seo-tracking/project/{id}/rank-check/tracked-keywords
     */
    public function getTrackedKeywords(int $projectId): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = (new Project())->findAccessible($projectId, $user['id']);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Progetto non trovato']);
            exit;
        }

        $keywordModel = new Keyword();
        $keywords = $keywordModel->getTrackedByProject($projectId);

        echo json_encode([
            'success' => true,
            'keywords' => $keywords,
            'count' => count($keywords),
            'credit_cost' => self::CREDIT_COST,
            'total_cost' => count($keywords) * self::CREDIT_COST,
        ]);
        exit;
    }
}
