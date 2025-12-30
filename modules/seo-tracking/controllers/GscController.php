<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\Credits;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\GscConnection;
use Modules\SeoTracking\Services\GscService;

/**
 * GscController
 * Gestisce connessione e sync Google Search Console
 */
class GscController
{
    private Project $project;
    private GscConnection $gscConnection;
    private GscService $gscService;

    public function __construct()
    {
        $this->project = new Project();
        $this->gscConnection = new GscConnection();
        $this->gscService = new GscService();
    }

    /**
     * Inizia flow OAuth - redirect a Google
     */
    public function connect(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$this->gscService->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Credenziali GSC non configurate. Contatta l\'amministratore.';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        $authUrl = $this->gscService->getAuthUrl($id);
        Router::redirect($authUrl);
    }

    /**
     * Callback OAuth da Google
     */
    public function callback(): void
    {
        $user = Auth::user();

        // Verifica state
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['gsc_oauth_state'] ?? '';

        if (empty($state) || $state !== $savedState) {
            $_SESSION['_flash']['error'] = 'Stato OAuth non valido';
            Router::redirect('/seo-tracking');
            return;
        }

        unset($_SESSION['gsc_oauth_state']);

        // Decode state
        $stateData = json_decode(base64_decode($state), true);
        $projectId = $stateData['project_id'] ?? 0;

        if (!$projectId) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato nel callback';
            Router::redirect('/seo-tracking');
            return;
        }

        // Verifica accesso progetto
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        // Errore OAuth?
        if (isset($_GET['error'])) {
            $_SESSION['_flash']['error'] = 'Autorizzazione negata: ' . ($_GET['error_description'] ?? $_GET['error']);
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');
            return;
        }

        // Scambia code per token
        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            $_SESSION['_flash']['error'] = 'Codice autorizzazione mancante';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');
            return;
        }

        try {
            $tokens = $this->gscService->exchangeCode($code);

            // Salva connessione
            $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

            $this->gscConnection->upsert($projectId, [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => $expiresAt,
                'scope' => $tokens['scope'] ?? '',
            ]);

            // Salva in sessione per selezione property
            $_SESSION['gsc_temp_project_id'] = $projectId;

            $_SESSION['_flash']['success'] = 'Autorizzazione completata! Ora seleziona la property.';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/gsc/select-property');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore OAuth: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');
        }
    }

    /**
     * Pagina selezione property GSC
     */
    public function selectProperty(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            exit;
        }

        try {
            $sites = $this->gscService->listSites($id);

            // Filtra per dominio progetto
            $domain = $project['domain'];
            $matchingSites = [];
            $otherSites = [];

            foreach ($sites as $site) {
                $siteUrl = $site['siteUrl'] ?? '';
                if (stripos($siteUrl, $domain) !== false) {
                    $matchingSites[] = $site;
                } else {
                    $otherSites[] = $site;
                }
            }

            return View::render('seo-tracking/gsc/select-property', [
                'title' => 'Seleziona Property GSC',
                'user' => $user,
                'modules' => ModuleLoader::getUserModules($user['id']),
                'project' => $project,
                'matchingSites' => $matchingSites,
                'otherSites' => $otherSites,
            ]);

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nel recupero properties: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            exit;
        }
    }

    /**
     * Salva property selezionata
     */
    public function saveProperty(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $siteUrl = $_POST['site_url'] ?? '';

        if (empty($siteUrl)) {
            $_SESSION['_flash']['error'] = 'Seleziona una property';
            Router::redirect('/seo-tracking/projects/' . $id . '/gsc/select-property');
            return;
        }

        // Verifica accesso
        if (!$this->gscService->verifySiteAccess($id, $siteUrl)) {
            $_SESSION['_flash']['error'] = 'Non hai accesso a questa property';
            Router::redirect('/seo-tracking/projects/' . $id . '/gsc/select-property');
            return;
        }

        // Aggiorna connessione
        $this->gscConnection->updateSiteUrl($id, $siteUrl);
        $this->project->setGscConnected($id, true);

        $_SESSION['_flash']['success'] = 'Google Search Console connesso con successo!';
        Router::redirect('/seo-tracking/projects/' . $id . '/settings');
    }

    /**
     * Disconnetti GSC
     */
    public function disconnect(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        $this->gscConnection->delete($id);
        $this->project->setGscConnected($id, false);

        $_SESSION['_flash']['success'] = 'Google Search Console disconnesso';
        Router::redirect('/seo-tracking/projects/' . $id . '/settings');
    }

    /**
     * Sync manuale GSC
     */
    public function sync(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$project['gsc_connected']) {
            $_SESSION['_flash']['error'] = 'GSC non connesso';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        try {
            $result = $this->gscService->syncSearchAnalytics($id);

            $_SESSION['_flash']['success'] = 'Sincronizzazione completata: ' . $result['records_fetched'] . ' record elaborati';
            Router::redirect('/seo-tracking/projects/' . $id . '/dashboard');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore sync: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
        }
    }

    /**
     * Full sync storico (usa crediti)
     */
    public function fullSync(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        if (!$project['gsc_connected']) {
            $_SESSION['_flash']['error'] = 'GSC non connesso';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        // Verifica crediti
        $creditCost = 10; // Costo full sync
        if (!Credits::check($user['id'], $creditCost)) {
            $_SESSION['_flash']['error'] = 'Crediti insufficienti. Richiesti: ' . $creditCost;
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        try {
            // Consuma crediti
            Credits::consume($user['id'], $creditCost, 'seo-tracking', 'gsc_full_sync', [
                'project_id' => $id,
            ]);

            $result = $this->gscService->fullHistoricalSync($id);

            $_SESSION['_flash']['success'] = 'Sincronizzazione storica completata: ' . $result['records_fetched'] . ' record elaborati';
            Router::redirect('/seo-tracking/projects/' . $id . '/dashboard');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore sync storico: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
        }
    }

    /**
     * API: Stato sync
     */
    public function syncStatus(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        return View::json([
            'sync_status' => $project['sync_status'],
            'last_sync_at' => $project['last_sync_at'],
            'gsc_connected' => (bool) $project['gsc_connected'],
        ]);
    }
}
