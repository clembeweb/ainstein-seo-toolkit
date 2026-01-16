<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Services\GscService;
use Services\GoogleOAuthService;

/**
 * GscController
 *
 * Gestisce integrazione Google Search Console
 * Usa GoogleOAuthService centralizzato per OAuth
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
     * Pagina connessione GSC - mostra stato e pulsante connetti
     */
    public function connect(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            exit;
        }

        // Check existing connection
        $connection = $this->gscService->getConnection($id);

        // Usa servizio OAuth centralizzato per authUrl
        $oauth = new GoogleOAuthService();
        $isConfigured = $oauth->isConfigured();
        $isMockMode = !$isConfigured;

        // Genera authUrl usando servizio centralizzato (se configurato)
        $authUrl = $isConfigured
            ? $oauth->getAuthUrl('seo-audit', $id)
            : url('/seo-audit/project/' . $id . '/gsc/mock-connect');

        return View::render('seo-audit/gsc/connect', [
            'title' => $project['name'] . ' - Google Search Console',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'connection' => $connection,
            'authUrl' => $authUrl,
            'isConfigured' => $isConfigured,
            'isMockMode' => $isMockMode,
        ]);
    }

    /**
     * Avvia flow OAuth - redirect diretto a Google
     * Usa GoogleOAuthService centralizzato
     */
    public function authorize(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            return;
        }

        $oauth = new GoogleOAuthService();

        if (!$oauth->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Credenziali Google non configurate. Contatta l\'amministratore.';
            Router::redirect('/seo-audit/project/' . $id . '/gsc/connect');
            return;
        }

        $authUrl = $oauth->getAuthUrl('seo-audit', $id);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Mock connect per modalità demo (senza credenziali Google)
     */
    public function mockConnect(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            return;
        }

        // Genera mock tokens
        $_SESSION['gsc_temp_tokens'] = [
            'access_token' => 'mock_access_token_' . bin2hex(random_bytes(16)),
            'refresh_token' => 'mock_refresh_token_' . bin2hex(random_bytes(16)),
            'expires_in' => 3600,
        ];
        $_SESSION['gsc_temp_project_id'] = $id;

        Router::redirect('/seo-audit/project/' . $id . '/gsc/properties');
    }

    /**
     * Riceve redirect dal callback OAuth centralizzato
     * I tokens sono in $_SESSION['google_oauth_tokens'] (salvati da OAuthController)
     */
    public function connected(): void
    {
        $user = Auth::user();

        // Leggi tokens dalla sessione (salvati da OAuthController centralizzato)
        $tokenData = $_SESSION['google_oauth_tokens'] ?? null;

        if (!$tokenData) {
            $_SESSION['_flash']['error'] = 'Sessione OAuth scaduta o non valida';
            Router::redirect('/seo-audit');
            return;
        }

        $projectId = $tokenData['project_id'];

        // Pulisci sessione OAuth
        unset($_SESSION['google_oauth_tokens']);

        // Verifica accesso progetto
        $project = $this->projectModel->findWithStats($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            return;
        }

        // Salva tokens temporaneamente per selezione property
        $_SESSION['gsc_temp_tokens'] = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_in' => $tokenData['expires_in'] ?? 3600,
        ];
        $_SESSION['gsc_temp_project_id'] = $projectId;

        $_SESSION['_flash']['success'] = 'Autorizzazione completata! Ora seleziona la property.';
        Router::redirect('/seo-audit/project/' . $projectId . '/gsc/properties');
    }

    /**
     * Mostra proprietà disponibili per selezione
     */
    public function properties(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            exit;
        }

        // Check for temp tokens
        $tokens = $_SESSION['gsc_temp_tokens'] ?? null;
        $tempProjectId = $_SESSION['gsc_temp_project_id'] ?? null;

        if (!$tokens || $tempProjectId !== $id) {
            $_SESSION['_flash']['error'] = 'Sessione OAuth scaduta. Riprova la connessione.';
            Router::redirect('/seo-audit/project/' . $id . '/gsc/connect');
            exit;
        }

        // Get properties - usa servizio centralizzato se non mock
        $oauth = new GoogleOAuthService();
        $isMockMode = !$oauth->isConfigured();

        $accessToken = $tokens['access_token'];
        $propertiesResult = $this->gscService->listProperties($accessToken);

        if (isset($propertiesResult['error'])) {
            $_SESSION['_flash']['error'] = 'Errore caricamento proprietà: ' . $propertiesResult['message'];
            Router::redirect('/seo-audit/project/' . $id . '/gsc/connect');
            exit;
        }

        return View::render('seo-audit/gsc/properties', [
            'title' => $project['name'] . ' - Seleziona Proprietà GSC',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'properties' => $propertiesResult['properties'],
            'isMockMode' => $isMockMode,
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
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            return;
        }

        $tokens = $_SESSION['gsc_temp_tokens'] ?? null;
        $tempProjectId = $_SESSION['gsc_temp_project_id'] ?? null;

        if (!$tokens || $tempProjectId !== $id) {
            $_SESSION['_flash']['error'] = 'Sessione scaduta. Riprova.';
            Router::redirect('/seo-audit/project/' . $id . '/gsc/connect');
            return;
        }

        $propertyUrl = $_POST['property_url'] ?? '';
        $propertyType = $_POST['property_type'] ?? 'URL_PREFIX';

        if (empty($propertyUrl)) {
            $_SESSION['_flash']['error'] = 'Seleziona una proprietà';
            Router::redirect('/seo-audit/project/' . $id . '/gsc/properties');
            return;
        }

        // Save connection
        $this->gscService->saveConnection($id, $user['id'], $tokens, $propertyUrl, $propertyType);

        // Clear temp data
        unset($_SESSION['gsc_temp_tokens'], $_SESSION['gsc_temp_project_id']);

        $_SESSION['_flash']['success'] = 'Google Search Console connesso con successo!';
        Router::redirect('/seo-audit/project/' . $id . '/gsc');
    }

    /**
     * Dashboard GSC
     */
    public function dashboard(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            exit;
        }

        $connection = $this->gscService->getConnection($id);

        if (!$connection) {
            Router::redirect('/seo-audit/project/' . $id . '/gsc/connect');
            exit;
        }

        // Get GSC stats
        $stats = $this->gscService->getStats($id, 28);

        // Credit for sync
        $syncCost = Credits::getCost('gsc_sync') ?? 5;
        $creditBalance = Credits::getBalance($user['id']);

        // Mock mode check
        $oauth = new GoogleOAuthService();
        $isMockMode = !$oauth->isConfigured();

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
            'isMockMode' => $isMockMode,
        ]);
    }

    /**
     * Sync manuale GSC
     */
    public function sync(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            jsonResponse(['error' => true, 'message' => 'Progetto non trovato'], 404);
            return;
        }

        // Check credits
        $syncCost = Credits::getCost('gsc_sync') ?? 5;
        if (!Credits::hasEnough($user['id'], $syncCost)) {
            jsonResponse(['error' => true, 'message' => 'Crediti insufficienti. Richiesti: ' . $syncCost]);
            return;
        }

        try {
            // Perform sync
            $result = $this->gscService->syncDailyData($id, 28); // Last 28 days

            if (isset($result['error'])) {
                jsonResponse($result);
                return;
            }

            // Consume credits
            Credits::consume($user['id'], $syncCost, 'gsc_sync', 'seo-audit', [
                'project_id' => $id,
                'queries_synced' => $result['queries_synced'] ?? 0,
                'pages_synced' => $result['pages_synced'] ?? 0,
            ]);

            $result['credits_used'] = $syncCost;
            jsonResponse($result);
        } catch (\Throwable $e) {
            error_log("GSC SYNC ERROR: " . $e->getMessage());
            jsonResponse(['error' => true, 'message' => 'Errore sync: ' . $e->getMessage()]);
        }
    }

    /**
     * Disconnetti GSC
     */
    public function disconnect(int $id): void
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-audit');
            return;
        }

        $this->gscService->disconnect($id);

        $_SESSION['_flash']['success'] = 'Google Search Console disconnesso';
        Router::redirect('/seo-audit/project/' . $id . '/gsc/connect');
    }
}
