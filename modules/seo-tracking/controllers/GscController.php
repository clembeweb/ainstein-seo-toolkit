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
     * Usa GoogleOAuthService centralizzato
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

        // Usa servizio OAuth centralizzato
        $oauth = new \Services\GoogleOAuthService();

        if (!$oauth->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Credenziali Google non configurate. Contatta l\'amministratore.';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        $authUrl = $oauth->getAuthUrl('seo-tracking', $id);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Riceve redirect dal callback OAuth centralizzato
     * I tokens sono in $_SESSION['google_oauth_tokens'] (salvati da OAuthController)
     */
    public function connected(): void
    {
        $user = Auth::user();

        // Leggi tokens dalla sessione (salvati da OAuthController)
        $tokenData = $_SESSION['google_oauth_tokens'] ?? null;

        if (!$tokenData) {
            $_SESSION['_flash']['error'] = 'Sessione OAuth scaduta o non valida';
            Router::redirect('/seo-tracking');
            return;
        }

        $projectId = $tokenData['project_id'];

        // Pulisci sessione
        unset($_SESSION['google_oauth_tokens']);

        // Verifica accesso progetto
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        // Salva connessione
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600));

        $this->gscConnection->upsert($projectId, [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'token_expires_at' => $expiresAt,
        ]);

        // Salva in sessione per selezione property
        $_SESSION['gsc_temp_project_id'] = $projectId;

        $_SESSION['_flash']['success'] = 'Autorizzazione completata! Ora seleziona la property.';
        Router::redirect('/seo-tracking/projects/' . $projectId . '/gsc/select-property');
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

        // NUOVO: Trigger sync automatico in background (30 giorni)
        $this->triggerBackgroundSync($id, 'gsc', 30);

        $_SESSION['_flash']['success'] = 'Google Search Console connesso! Sincronizzazione in corso...';
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
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        if (!$project['gsc_connected']) {
            $this->jsonResponse(['success' => false, 'error' => 'GSC non connesso']);
            return;
        }

        try {
            $this->project->updateSyncStatus($id, 'running');
            $result = $this->gscService->syncSearchAnalytics($id);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Sincronizzazione GSC completata: ' . ($result['records_fetched'] ?? 0) . ' record elaborati',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            error_log("[GscController] Sync error project $id: " . $e->getMessage());
            $this->project->updateSyncStatus($id, 'failed');

            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper per risposta JSON
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Sync GSC con progress SSE (per grandi moli di dati - 16 mesi)
     */
    public function syncWithProgress(int $id): void
    {
        error_log("[SSE] syncWithProgress called for project $id");

        // Disabilita TUTTI i buffer di output
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Setup SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');

        // Forza invio headers
        ob_implicit_flush(true);
        flush();

        // Evento iniziale per "aprire" connessione
        echo ": ping\n\n";
        flush();

        // Aumenta timeout
        set_time_limit(0);
        ignore_user_abort(false);

        $user = Auth::user();
        session_write_close(); // RILASCIA SESSIONE

        if (!$user) {
            $this->sendSSE(['status' => 'error', 'message' => 'Non autenticato', 'error' => true]);
            exit;
        }

        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $this->sendSSE(['status' => 'error', 'message' => 'Progetto non trovato', 'error' => true]);
            return;
        }

        if (!$project['gsc_connected']) {
            $this->sendSSE(['status' => 'error', 'message' => 'GSC non connesso', 'error' => true]);
            return;
        }

        try {
            $this->project->updateSyncStatus($id, 'running');

            // Leggi mesi da parametro GET o da progetto (default 16)
            $months = (float)($_GET['months'] ?? $project['data_retention_months'] ?? 16);
            // Limita a max 16 mesi (limite GSC API)
            $months = max(0.03, min($months, 16));

            // Converti mesi in giorni per valori decimali (0.03 = ~1 giorno)
            $days = max(1, (int)round($months * 30));
            // Determina range date (con -3 giorni per delay GSC)
            $endDate = new \DateTime('-3 days');
            $startDate = (clone $endDate)->modify("-{$days} days");

            // Dividi in batch mensili
            $batches = $this->generateMonthlyBatches($startDate, $endDate);
            $totalBatches = count($batches);
            $totalRecords = 0;

            $periodLabel = $months < 1 ? "{$days} giorni" : "{$months} mesi";
            $this->sendSSE([
                'status' => 'starting',
                'message' => "Avvio sync GSC: {$periodLabel} ({$totalBatches} batch)",
                'total' => $totalBatches,
                'progress' => 0
            ]);

            foreach ($batches as $index => $batch) {
                $batchNum = $index + 1;
                $monthLabel = $batch['start']->format('M Y');

                $this->sendSSE([
                    'status' => 'processing',
                    'message' => "Elaborazione {$monthLabel}...",
                    'current' => $batchNum,
                    'total' => $totalBatches,
                    'progress' => round(($batchNum / $totalBatches) * 100)
                ]);

                // Sync questo mese
                $records = $this->gscService->syncDateRange(
                    $id,
                    $batch['start']->format('Y-m-d'),
                    $batch['end']->format('Y-m-d')
                );

                $totalRecords += $records;

                $this->sendSSE([
                    'status' => 'batch_complete',
                    'message' => "{$monthLabel}: {$records} record",
                    'current' => $batchNum,
                    'total' => $totalBatches,
                    'progress' => round(($batchNum / $totalBatches) * 100),
                    'records' => $records
                ]);

                // Breve pausa per non sovraccaricare API
                usleep(100000); // 100ms

                // Check se client ha chiuso connessione
                if (connection_aborted()) {
                    break;
                }
            }

            // Aggiorna timestamp sync
            $this->project->updateSyncStatus($id, 'completed');

            $this->sendSSE([
                'status' => 'complete',
                'message' => "Sync completato! {$totalRecords} record totali",
                'total_records' => $totalRecords,
                'progress' => 100
            ]);

        } catch (\Throwable $e) {
            error_log("GSC Sync SSE Error: " . $e->getMessage());
            $this->project->updateSyncStatus($id, 'failed');
            $this->sendSSE([
                'status' => 'error',
                'message' => 'Errore: ' . $e->getMessage(),
                'error' => true
            ]);
        }

        exit; // Termina script SSE
    }

    /**
     * Genera batch mensili per sync
     */
    private function generateMonthlyBatches(\DateTime $start, \DateTime $end): array
    {
        $batches = [];
        $current = clone $start;
        $current->modify('first day of this month');

        while ($current <= $end) {
            $batchStart = clone $current;
            $batchEnd = clone $current;
            $batchEnd->modify('last day of this month');

            // Non superare data fine
            if ($batchEnd > $end) {
                $batchEnd = clone $end;
            }

            $batches[] = [
                'start' => $batchStart,
                'end' => $batchEnd
            ];

            $current->modify('+1 month');
        }

        return $batches;
    }

    /**
     * Helper per inviare evento SSE
     */
    private function sendSSE(array $data): void
    {
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
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
        $creditCost = Credits::getCost('gsc_full_sync', 'seo-tracking');
        if (!Credits::hasEnough($user['id'], $creditCost)) {
            $_SESSION['_flash']['error'] = 'Crediti insufficienti. Richiesti: ' . $creditCost;
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        try {
            // Consuma crediti
            Credits::consume($user['id'], $creditCost, 'gsc_full_sync', 'seo-tracking', [
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

    /**
     * Trigger sync in background (non-blocking)
     */
    private function triggerBackgroundSync(int $projectId, string $type, int $days): void
    {
        $token = $this->generateSyncToken($projectId);
        $months = round($days / 30, 2);

        // Costruisci URL base
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = ($basePath === '/' || $basePath === '\\') ? '' : $basePath;

        $url = "{$protocol}://{$host}{$basePath}/sync-direct.php?type={$type}&id={$projectId}&months={$months}&token={$token}";

        // Fire and forget - non aspetta risposta
        $this->fireAndForget($url);
    }

    /**
     * Esegue richiesta HTTP senza attendere risposta
     */
    private function fireAndForget(string $url): void
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80);
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        $scheme = $parts['scheme'] ?? 'http';

        // Per HTTPS usa ssl://
        $socketHost = ($scheme === 'https') ? "ssl://{$host}" : $host;

        $fp = @fsockopen($socketHost, $port, $errno, $errstr, 5);
        if ($fp) {
            $out = "GET {$path} HTTP/1.1\r\n";
            $out .= "Host: {$host}\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "User-Agent: SEO-Toolkit-BackgroundSync/1.0\r\n";
            $out .= "\r\n";
            fwrite($fp, $out);
            fclose($fp); // Chiude subito, non aspetta risposta
        } else {
            error_log("[GscController] fireAndForget failed: {$errno} {$errstr} - {$url}");
        }
    }

    /**
     * Genera token per validare richieste sync background
     */
    private function generateSyncToken(int $projectId): string
    {
        $secret = getenv('APP_KEY') ?: 'seo-toolkit-secret-key';
        return hash('sha256', $projectId . $secret . date('Y-m-d'));
    }
}
