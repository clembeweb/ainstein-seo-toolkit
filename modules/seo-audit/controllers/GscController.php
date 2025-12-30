<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Services\GscService;

/**
 * GscController
 *
 * Gestisce integrazione Google Search Console
 */
class GscController
{
    private Project $projectModel;
    private GscService $gscService;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->gscService = new GscService();
    }

    /**
     * Pagina connessione GSC
     */
    public function connect(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Check existing connection
        $connection = $this->gscService->getConnection($id);

        return View::render('seo-audit/gsc/connect', [
            'title' => $project['name'] . ' - Google Search Console',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'connection' => $connection,
            'authUrl' => $this->gscService->getAuthUrl($id),
            'isConfigured' => $this->gscService->isConfigured(),
            'isMockMode' => $this->gscService->isMockMode(),
        ]);
    }

    /**
     * OAuth callback
     */
    public function callback(): void
    {
        $user = Auth::user();

        // Handle mock mode
        if (isset($_GET['mock']) && $_GET['mock'] === '1') {
            $projectId = (int) ($_GET['project_id'] ?? 0);
            if ($projectId > 0) {
                $_SESSION['gsc_temp_project_id'] = $projectId;
                $_SESSION['gsc_temp_tokens'] = $this->gscService->isMockMode()
                    ? ['access_token' => 'mock_token', 'refresh_token' => 'mock_refresh', 'expires_in' => 3600]
                    : null;

                header('Location: ' . url('/seo-audit/project/' . $projectId . '/gsc/properties'));
                exit;
            }
        }

        // Check for error
        if (isset($_GET['error'])) {
            $_SESSION['flash_error'] = 'Autorizzazione negata: ' . ($_GET['error_description'] ?? $_GET['error']);
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Validate state
        $state = $_GET['state'] ?? '';
        if (empty($state) || !isset($_SESSION['gsc_oauth_state']) || $state !== $_SESSION['gsc_oauth_state']) {
            $_SESSION['flash_error'] = 'Stato OAuth non valido';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $stateData = json_decode(base64_decode($state), true);
        $projectId = (int) ($stateData['project_id'] ?? 0);

        if ($projectId <= 0) {
            $_SESSION['flash_error'] = 'Progetto non valido';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Verify project ownership
        $project = $this->projectModel->findWithStats($projectId, $user['id']);
        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Exchange code for tokens
        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            $_SESSION['flash_error'] = 'Codice autorizzazione mancante';
            header('Location: ' . url('/seo-audit/project/' . $projectId . '/gsc/connect'));
            exit;
        }

        $tokens = $this->gscService->exchangeCode($code);

        if (isset($tokens['error'])) {
            $_SESSION['flash_error'] = 'Errore scambio token: ' . $tokens['message'];
            header('Location: ' . url('/seo-audit/project/' . $projectId . '/gsc/connect'));
            exit;
        }

        // Store tokens temporarily and redirect to property selection
        $_SESSION['gsc_temp_tokens'] = $tokens;
        $_SESSION['gsc_temp_project_id'] = $projectId;
        unset($_SESSION['gsc_oauth_state']);

        header('Location: ' . url('/seo-audit/project/' . $projectId . '/gsc/properties'));
        exit;
    }

    /**
     * Mostra proprietà disponibili per selezione
     */
    public function properties(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        // Check for temp tokens
        $tokens = $_SESSION['gsc_temp_tokens'] ?? null;
        $tempProjectId = $_SESSION['gsc_temp_project_id'] ?? null;

        if (!$tokens || $tempProjectId !== $id) {
            $_SESSION['flash_error'] = 'Sessione OAuth scaduta. Riprova la connessione.';
            header('Location: ' . url('/seo-audit/project/' . $id . '/gsc/connect'));
            exit;
        }

        // Get properties
        $accessToken = $tokens['access_token'];
        $propertiesResult = $this->gscService->listProperties($accessToken);

        if (isset($propertiesResult['error'])) {
            $_SESSION['flash_error'] = 'Errore caricamento proprietà: ' . $propertiesResult['message'];
            header('Location: ' . url('/seo-audit/project/' . $id . '/gsc/connect'));
            exit;
        }

        return View::render('seo-audit/gsc/properties', [
            'title' => $project['name'] . ' - Seleziona Proprietà GSC',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'properties' => $propertiesResult['properties'],
            'isMockMode' => $this->gscService->isMockMode(),
        ]);
    }

    /**
     * Salva proprietà selezionata
     */
    public function selectProperty(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $tokens = $_SESSION['gsc_temp_tokens'] ?? null;
        $tempProjectId = $_SESSION['gsc_temp_project_id'] ?? null;

        if (!$tokens || $tempProjectId !== $id) {
            $_SESSION['flash_error'] = 'Sessione scaduta. Riprova.';
            header('Location: ' . url('/seo-audit/project/' . $id . '/gsc/connect'));
            exit;
        }

        $propertyUrl = $_POST['property_url'] ?? '';
        $propertyType = $_POST['property_type'] ?? 'URL_PREFIX';

        if (empty($propertyUrl)) {
            $_SESSION['flash_error'] = 'Seleziona una proprietà';
            header('Location: ' . url('/seo-audit/project/' . $id . '/gsc/properties'));
            exit;
        }

        // Save connection
        $this->gscService->saveConnection($id, $user['id'], $tokens, $propertyUrl, $propertyType);

        // Clear temp data
        unset($_SESSION['gsc_temp_tokens'], $_SESSION['gsc_temp_project_id']);

        $_SESSION['flash_success'] = 'Google Search Console connesso con successo!';
        header('Location: ' . url('/seo-audit/project/' . $id . '/gsc'));
        exit;
    }

    /**
     * Dashboard GSC
     */
    public function dashboard(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $connection = $this->gscService->getConnection($id);

        if (!$connection) {
            header('Location: ' . url('/seo-audit/project/' . $id . '/gsc/connect'));
            exit;
        }

        // Get GSC stats
        $stats = $this->gscService->getStats($id, 28);

        // Credit for sync
        $syncCost = Credits::getCost('gsc_sync') ?? 5;
        $creditBalance = Credits::getBalance($user['id']);

        return View::render('seo-audit/gsc/dashboard', [
            'title' => $project['name'] . ' - Google Search Console',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'connection' => $connection,
            'stats' => $stats,
            'credits' => [
                'balance' => $creditBalance,
                'sync_cost' => $syncCost,
            ],
            'isMockMode' => $this->gscService->isMockMode(),
        ]);
    }

    /**
     * Sync manuale GSC
     */
    public function sync(int $id): void
    {
        header('Content-Type: application/json');

        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            echo json_encode(['error' => true, 'message' => 'Progetto non trovato']);
            exit;
        }

        // Check credits
        $syncCost = Credits::getCost('gsc_sync') ?? 5;
        if (!Credits::hasEnough($user['id'], $syncCost)) {
            echo json_encode(['error' => true, 'message' => 'Crediti insufficienti. Richiesti: ' . $syncCost]);
            exit;
        }

        // Perform sync
        $result = $this->gscService->syncDailyData($id, 28); // Last 28 days

        if (isset($result['error'])) {
            echo json_encode($result);
            exit;
        }

        // Consume credits
        Credits::consume($user['id'], $syncCost, 'gsc_sync', 'seo-audit', [
            'project_id' => $id,
            'queries_synced' => $result['queries_synced'],
            'pages_synced' => $result['pages_synced'],
        ]);

        $result['credits_used'] = $syncCost;
        echo json_encode($result);
        exit;
    }

    /**
     * Disconnetti GSC
     */
    public function disconnect(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $this->gscService->disconnect($id);

        $_SESSION['flash_success'] = 'Google Search Console disconnesso';
        header('Location: ' . url('/seo-audit/project/' . $id . '/gsc/connect'));
        exit;
    }
}
