<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Database;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\RankCheck;
use Modules\SeoTracking\Services\RankCheckerService;

/**
 * RankCheckController
 * Gestisce la verifica posizioni SERP reali via SerpAPI
 */
class RankCheckController
{
    private const CREDIT_COST = 1;
    private const MAX_BULK_KEYWORDS = 10;

    /**
     * Vista principale - Form + storico recente
     */
    public function index(int $projectId): string
    {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find($projectId, $user['id']);

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
        $project = $projectModel->find($projectId, $user['id']);

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
        $project = $projectModel->find($projectId, $user['id']);

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
     * Storico completo con filtri (GET)
     */
    public function history(int $projectId): string
    {
        $user = Auth::user();
        $projectModel = new Project();
        $project = $projectModel->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: /seo-tracking');
            exit;
        }

        $rankCheckModel = new RankCheck();

        // Filtri
        $filters = [
            'keyword' => $_GET['keyword'] ?? '',
            'device' => $_GET['device'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'found_only' => isset($_GET['found_only']),
            'not_found_only' => isset($_GET['not_found_only']),
            'limit' => 100,
        ];

        $checks = $rankCheckModel->search($projectId, $filters);
        $stats = $rankCheckModel->getStats($projectId);

        return View::render('seo-tracking/rank-check/history', [
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'checks' => $checks,
            'stats' => $stats,
            'filters' => $filters,
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
        $project = (new Project())->find($projectId, $user['id']);

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
        $project = (new Project())->find($projectId, $user['id']);

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
        $project = (new Project())->find($projectId, $user['id']);

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
}
