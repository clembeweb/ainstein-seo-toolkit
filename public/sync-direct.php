<?php
/**
 * Direct SSE Sync - bypass router for GSC/GA4 sync
 * Supporta:
 * - Auth via sessione (richieste da browser)
 * - Auth via token (richieste background post-OAuth)
 * - Auth via cron secret (sync automatici)
 */

define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', BASE_PATH);

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\' => BASE_PATH . '/services/',
        'Modules\\SeoTracking\\' => BASE_PATH . '/modules/seo-tracking/',
    ];
    foreach ($paths as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});

// Parametri
$type = $_GET['type'] ?? 'gsc';
$projectId = (int)($_GET['id'] ?? 0);
$months = (float)($_GET['months'] ?? 1);
$token = $_GET['token'] ?? '';
$cronSecret = $_GET['cron_secret'] ?? '';

if ($projectId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID progetto mancante']);
    exit;
}

// === AUTENTICAZIONE ===
$userId = null;
$authMethod = 'none';

// 1. Token auth (per richieste background post-OAuth)
if (!empty($token)) {
    $secret = getenv('APP_KEY') ?: 'seo-toolkit-secret-key';
    $expectedToken = hash('sha256', $projectId . $secret . date('Y-m-d'));

    if (hash_equals($expectedToken, $token)) {
        $authMethod = 'token';
    } else {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Token non valido']);
        exit;
    }
}
// 2. Cron secret (per sync automatici)
elseif (!empty($cronSecret)) {
    $expectedCronSecret = getenv('CRON_SECRET') ?: 'cron-secret-change-me';

    if (hash_equals($expectedCronSecret, $cronSecret)) {
        $authMethod = 'cron';
    } else {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Cron secret non valido']);
        exit;
    }
}
// 3. Session auth (per richieste da browser)
else {
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    session_write_close();

    if ($userId) {
        $authMethod = 'session';
    } else {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato']);
        exit;
    }
}

// Disabilita buffer
while (ob_get_level()) ob_end_clean();

// SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Content-Encoding: none');

ob_implicit_flush(true);
flush();

// Ping iniziale
echo ": ping\n\n";
flush();

set_time_limit(0);
ignore_user_abort(false);

function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

try {
    \Core\Database::getInstance();

    // Verifica accesso progetto
    if ($authMethod === 'session') {
        // Per sessione, verifica che l'utente sia proprietario
        $project = \Core\Database::fetch(
            "SELECT * FROM st_projects WHERE id = ? AND user_id = ?",
            [$projectId, $userId]
        );
    } else {
        // Per token/cron, il progetto deve esistere (già autorizzato)
        $project = \Core\Database::fetch(
            "SELECT * FROM st_projects WHERE id = ?",
            [$projectId]
        );
    }

    if (!$project) {
        sendSSE(['status' => 'error', 'message' => 'Progetto non trovato', 'error' => true]);
        exit;
    }

    // Calcola date
    $days = max(1, (int)round($months * 30));
    $periodLabel = $months < 1 ? "{$days} giorni" : "{$months} mesi";

    // Log per debug
    error_log("[sync-direct] Auth: {$authMethod}, Project: {$projectId}, Type: {$type}, Days: {$days}");

    if ($type === 'gsc') {
        if (!$project['gsc_connected']) {
            sendSSE(['status' => 'error', 'message' => 'GSC non connesso', 'error' => true]);
            exit;
        }

        $gscService = new \Modules\SeoTracking\Services\GscService();

        $endDate = new \DateTime('-3 days');
        $startDate = (clone $endDate)->modify("-{$days} days");

        sendSSE([
            'status' => 'starting',
            'message' => "Avvio sync GSC: {$periodLabel}",
            'progress' => 0
        ]);

        $start = microtime(true);
        $count = $gscService->syncDateRange($projectId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $elapsed = round(microtime(true) - $start, 1);

        // Aggiorna timestamp
        \Core\Database::execute(
            "UPDATE st_projects SET last_sync_at = NOW(), sync_status = 'completed' WHERE id = ?",
            [$projectId]
        );

        sendSSE([
            'status' => 'complete',
            'message' => "GSC completato: {$count} record in {$elapsed}s",
            'total_records' => $count,
            'progress' => 100
        ]);

    } elseif ($type === 'ga4') {
        if (!$project['ga4_connected']) {
            sendSSE(['status' => 'error', 'message' => 'GA4 non connesso', 'error' => true]);
            exit;
        }

        $ga4Service = new \Modules\SeoTracking\Services\Ga4Service();

        $endDate = new \DateTime('-1 day');
        $startDate = (clone $endDate)->modify("-{$days} days");

        sendSSE([
            'status' => 'starting',
            'message' => "Avvio sync GA4: {$periodLabel}",
            'progress' => 0
        ]);

        $start = microtime(true);
        $count = $ga4Service->syncDateRange($projectId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $elapsed = round(microtime(true) - $start, 1);

        // Aggiorna timestamp GA4
        \Core\Database::execute(
            "UPDATE st_ga4_connections SET last_sync_at = NOW() WHERE project_id = ?",
            [$projectId]
        );

        sendSSE([
            'status' => 'complete',
            'message' => "GA4 completato: {$count} record in {$elapsed}s",
            'total_records' => $count,
            'progress' => 100
        ]);

    } else {
        sendSSE(['status' => 'error', 'message' => 'Tipo sync non valido', 'error' => true]);
    }

} catch (\Throwable $e) {
    error_log("Sync Direct Error [{$authMethod}]: " . $e->getMessage());
    sendSSE(['status' => 'error', 'message' => 'Errore: ' . $e->getMessage(), 'error' => true]);
}
