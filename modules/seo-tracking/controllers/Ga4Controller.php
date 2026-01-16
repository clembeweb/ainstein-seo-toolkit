<?php

namespace Modules\SeoTracking\Controllers;

use Core\View;
use Core\Auth;
use Core\Router;
use Core\ModuleLoader;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\Ga4Connection;
use Modules\SeoTracking\Services\Ga4Service;

/**
 * Ga4Controller
 * Gestisce connessione OAuth e sync Google Analytics 4
 */
class Ga4Controller
{
    private Project $project;
    private Ga4Connection $ga4Connection;
    private Ga4Service $ga4Service;

    public function __construct()
    {
        $this->project = new Project();
        $this->ga4Connection = new Ga4Connection();
        $this->ga4Service = new Ga4Service();
    }

    /**
     * Inizia flow OAuth - redirect a Google
     * Usa GoogleOAuthService centralizzato (stesso di GSC)
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

        // Usa servizio OAuth centralizzato (come GscController)
        $oauth = new \Services\GoogleOAuthService();

        if (!$oauth->isConfigured()) {
            $_SESSION['_flash']['error'] = 'Credenziali Google OAuth non configurate. Contatta l\'amministratore.';
            Router::redirect('/seo-tracking/projects/' . $id . '/settings');
            return;
        }

        // Usa scope GA4 e type 'ga4' per redirect corretto dopo callback
        $authUrl = $oauth->getAuthUrl(
            'seo-tracking',
            $id,
            \Services\GoogleOAuthService::SCOPE_GA4,
            'ga4'
        );
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Callback da OAuth centralizzato - riceve tokens dalla sessione
     * Route: GET /seo-tracking/ga4/connected
     */
    public function connected(): void
    {
        $user = Auth::user();

        // Leggi tokens dalla sessione (salvati da OAuthController)
        $tokenData = $_SESSION['google_oauth_tokens'] ?? null;

        if (!$tokenData || ($tokenData['type'] ?? null) !== 'ga4') {
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

        $this->ga4Connection->upsert($projectId, [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'token_expires_at' => $expiresAt,
        ]);

        $_SESSION['_flash']['success'] = 'Autorizzazione GA4 completata! Ora seleziona la property.';
        Router::redirect('/seo-tracking/projects/' . $projectId . '/ga4/select-property');
    }

    /**
     * Callback OAuth - riceve authorization code
     * @deprecated Usare /oauth/google/callback centralizzato + connected()
     */
    public function callback(): void
    {
        // Verifica state (CSRF)
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['ga4_oauth_state'] ?? '';

        if (empty($state) || $state !== $savedState) {
            $_SESSION['_flash']['error'] = 'Errore di sicurezza OAuth (state non valido)';
            Router::redirect('/seo-tracking');
            return;
        }

        // Decodifica state
        $stateData = json_decode(base64_decode($state), true);
        $projectId = $stateData['project_id'] ?? 0;

        // Pulisci state dalla sessione
        unset($_SESSION['ga4_oauth_state']);

        // Verifica errori da Google
        if (isset($_GET['error'])) {
            $_SESSION['_flash']['error'] = 'Autorizzazione negata: ' . ($_GET['error_description'] ?? $_GET['error']);
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');
            return;
        }

        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            $_SESSION['_flash']['error'] = 'Codice di autorizzazione mancante';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');
            return;
        }

        // Verifica accesso al progetto
        $user = Auth::user();
        $project = $this->project->find($projectId, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            Router::redirect('/seo-tracking');
            return;
        }

        try {
            // Scambia code per tokens
            $tokens = $this->ga4Service->exchangeCode($code);

            // Salva connessione
            $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

            $this->ga4Connection->upsert($projectId, [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => $expiresAt,
            ]);

            $_SESSION['_flash']['success'] = 'Autorizzazione completata! Ora seleziona la property GA4.';
            Router::redirect('/seo-tracking/projects/' . $projectId . '/ga4/select-property');

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore OAuth: ' . $e->getMessage();
            Router::redirect('/seo-tracking/projects/' . $projectId . '/settings');
        }
    }

    /**
     * Pagina selezione property GA4
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
            $properties = $this->ga4Service->listProperties($id);

            // Filtra per dominio progetto
            $domain = $project['domain'];
            $matchingProperties = [];
            $otherProperties = [];

            foreach ($properties as $prop) {
                $propName = strtolower($prop['property_name'] ?? '');
                if (stripos($propName, $domain) !== false || stripos($domain, $propName) !== false) {
                    $matchingProperties[] = $prop;
                } else {
                    $otherProperties[] = $prop;
                }
            }

            return View::render('seo-tracking/ga4/select-property', [
                'title' => 'Seleziona Property GA4',
                'user' => $user,
                'modules' => ModuleLoader::getUserModules($user['id']),
                'project' => $project,
                'matchingProperties' => $matchingProperties,
                'otherProperties' => $otherProperties,
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

        $propertyId = trim($_POST['property_id'] ?? '');
        $propertyName = trim($_POST['property_name'] ?? '');

        if (empty($propertyId)) {
            $_SESSION['_flash']['error'] = 'Seleziona una property';
            Router::redirect('/seo-tracking/projects/' . $id . '/ga4/select-property');
            return;
        }

        // Verifica accesso
        if (!$this->ga4Service->verifyPropertyAccess($id, $propertyId)) {
            $_SESSION['_flash']['error'] = 'Non hai accesso a questa property';
            Router::redirect('/seo-tracking/projects/' . $id . '/ga4/select-property');
            return;
        }

        // Aggiorna connessione con property
        $this->ga4Connection->updateProperty($id, $propertyId, $propertyName ?: null);
        $this->project->setGa4Connected($id, true);

        // NUOVO: Trigger sync automatico in background (30 giorni)
        $this->triggerBackgroundSync($id, 'ga4', 30);

        $_SESSION['_flash']['success'] = 'Google Analytics 4 connesso! Sincronizzazione in corso...';
        Router::redirect('/seo-tracking/projects/' . $id . '/settings');
    }

    /**
     * Disconnetti GA4
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

        $this->ga4Connection->delete($id);
        $this->project->setGa4Connected($id, false);

        $_SESSION['_flash']['success'] = 'Google Analytics 4 disconnesso';
        Router::redirect('/seo-tracking/projects/' . $id . '/settings');
    }

    /**
     * Sync manuale GA4 - restituisce JSON per chiamate AJAX
     */
    public function sync(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $this->jsonResponse(['success' => false, 'error' => 'Progetto non trovato']);
            return;
        }

        if (!$project['ga4_connected']) {
            $this->jsonResponse(['success' => false, 'error' => 'GA4 non connesso']);
            return;
        }

        try {
            $result = $this->ga4Service->syncAnalytics($id);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Sincronizzazione GA4 completata: ' . ($result['records_fetched'] ?? 0) . ' record elaborati',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            error_log("[Ga4Controller] Sync error project $id: " . $e->getMessage());

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
     * Sync GA4 con progress SSE (per grandi moli di dati - 12 mesi)
     */
    public function syncWithProgress(int $id): void
    {
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

        if (!$project['ga4_connected']) {
            $this->sendSSE(['status' => 'error', 'message' => 'GA4 non connesso', 'error' => true]);
            return;
        }

        try {
            // Leggi mesi da parametro GET o da progetto (default 12)
            $months = (float)($_GET['months'] ?? $project['data_retention_months'] ?? 12);
            // Limita a max 14 mesi (limite GA4 API)
            $months = max(0.03, min($months, 14));

            // Converti mesi in giorni per valori decimali (0.03 = ~1 giorno)
            $days = max(1, (int)round($months * 30));
            // Determina range date (con -1 giorno per delay GA4)
            $endDate = new \DateTime('-1 day');
            $startDate = (clone $endDate)->modify("-{$days} days");

            $batches = $this->generateMonthlyBatches($startDate, $endDate);
            $totalBatches = count($batches);
            $totalRecords = 0;

            $periodLabel = $months < 1 ? "{$days} giorni" : "{$months} mesi";
            $this->sendSSE([
                'status' => 'starting',
                'message' => "Avvio sync GA4: {$periodLabel} ({$totalBatches} batch)",
                'total' => $totalBatches,
                'progress' => 0
            ]);

            foreach ($batches as $index => $batch) {
                $batchNum = $index + 1;
                $monthLabel = $batch['start']->format('M Y');

                $this->sendSSE([
                    'status' => 'processing',
                    'message' => "GA4: {$monthLabel}...",
                    'current' => $batchNum,
                    'total' => $totalBatches,
                    'progress' => round(($batchNum / $totalBatches) * 100)
                ]);

                $records = $this->ga4Service->syncDateRange(
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

                usleep(100000); // 100ms

                if (connection_aborted()) break;
            }

            // Aggiorna last sync
            $this->ga4Connection->updateLastSync($id);

            $this->sendSSE([
                'status' => 'complete',
                'message' => "GA4 completato! {$totalRecords} record totali",
                'total_records' => $totalRecords,
                'progress' => 100
            ]);

        } catch (\Throwable $e) {
            error_log("GA4 Sync SSE Error: " . $e->getMessage());
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
     * Test connessione GA4
     */
    public function test(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $connection = $this->ga4Connection->getByProject($id);

        if (!$connection || empty($connection['property_id'])) {
            return View::json(['error' => 'Connessione GA4 non configurata'], 404);
        }

        try {
            $verified = $this->ga4Service->verifyPropertyAccess($id, $connection['property_id']);

            return View::json([
                'success' => $verified,
                'message' => $verified ? 'Connessione verificata' : 'Impossibile accedere alla property',
            ]);

        } catch (\Exception $e) {
            return View::json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Stato connessione
     */
    public function status(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            return View::json(['error' => 'Progetto non trovato'], 404);
        }

        $connection = $this->ga4Connection->getByProject($id);

        return View::json([
            'connected' => (bool) $project['ga4_connected'],
            'property_id' => $connection['property_id'] ?? null,
            'property_name' => $connection['property_name'] ?? null,
            'last_sync' => $connection['last_sync_at'] ?? null,
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
            error_log("[Ga4Controller] fireAndForget failed: {$errno} {$errstr} - {$url}");
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
