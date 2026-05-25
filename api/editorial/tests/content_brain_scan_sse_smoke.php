<?php

declare(strict_types=1);

/**
 * Smoke test isolato M2.2 — Definition of Done check.
 *
 * Verifica che POST /content-brain/scan (handler ContentBrainController::scan):
 *   - setti gli SSE headers corretti
 *   - invochi ContentBrainService::scan() col callback $emit
 *   - inoltri sullo stream gli eventi nel formato 'event: name\ndata: json\n\n'
 *   - chiuda lo stream con exit
 *
 * Non chiama DB ne' AI reale: usa mock di ContentBrainService.
 * Non passa per il Router: invoca direttamente il Controller con service injection.
 *
 * Esecuzione:
 *   php api/editorial/tests/content_brain_scan_sse_smoke.php
 *
 * Atteso: 6/6 check PASS + exit code 0.
 */

require_once __DIR__ . '/bootstrap.php';

use Editorial\Controllers\ContentBrainController;
use Editorial\Middleware\LicenseAuthMiddleware;
use Editorial\Services\ContentBrainService;

// ---------------------------------------------------------------
// Mock ContentBrainService: emette una sequenza fissa di eventi
// ---------------------------------------------------------------

$mockService = new class extends ContentBrainService {
    /** @noinspection MagicMethodsValidityInspection */
    public function __construct() { /* skip parent: nessuna AI/scraper reale */ }

    public function scan(int $siteId, array $articleUrls = [], ?callable $emit = null): array
    {
        $emit ??= static function (string $e, array $d): void {};
        $emit('started', ['total_urls' => 2, 'sample_size' => 2, 'site_id' => $siteId]);
        $emit('article_scraped', ['index' => 1, 'total' => 2, 'url' => $articleUrls[0] ?? 'a', 'title' => 'Articolo 1', 'word_count' => 800]);
        $emit('article_scraped', ['index' => 2, 'total' => 2, 'url' => $articleUrls[1] ?? 'b', 'title' => 'Articolo 2', 'word_count' => 650]);
        $emit('aggregating', ['scraped_count' => 2]);
        $emit('analyzing', ['status' => 'AI sta leggendo i tuoi articoli...']);
        $emit('completed', ['brain' => ['tone' => 'friendly', 'language' => 'it', 'scanned_articles_count' => 2]]);
        return ['success' => true];
    }
};

// ---------------------------------------------------------------
// Mock Controller: override jsonInput per simulare body POST
// ---------------------------------------------------------------

$controller = new class($mockService) extends ContentBrainController {
    public array $mockInput = [];
    protected function jsonInput(): array
    {
        return $this->mockInput;
    }
};
$controller->mockInput = [
    'article_urls' => [
        'https://example.test/articolo-uno',
        'https://example.test/articolo-due',
    ],
];

// ---------------------------------------------------------------
// Mock middleware: site_id finto, il mock service ignora il valore reale
// ---------------------------------------------------------------

LicenseAuthMiddleware::$currentSite = [
    'id' => 999,
    'user_id' => 1,
    'domain' => 'example.test',
    'status' => 'active',
];

// ---------------------------------------------------------------
// Cattura stdout via ob_start. setupSseHeaders() chiude i buffer
// prima di emettere; quindi tutto cio' che echoiamo dopo finisce
// direttamente su STDOUT del processo PHP. Per un test in-process
// rilancio ob_start dentro la callback emit del mock, lavorando
// su un buffer separato gestito qui.
// ---------------------------------------------------------------

// Apro un buffer "esterno" che pero' verra' chiuso da setupSseHeaders().
// Strategia alternativa: registro un output handler che cattura.
$captured = '';

// Uso un wrapper di STDOUT via stream filter? Troppo. Vado di esecuzione subprocess:
// se questo file e' eseguito direttamente (test entrypoint), invoco un subprocess
// che esegue solo la parte di scan ed io qui leggo l'output.

if (getenv('AIED_SSE_SMOKE_CHILD') === '1') {
    // Modalita' figlio: esegui il flow scan e termina. Output va a STDOUT.
    $controller->scan();
    // unreachable per via di exit dentro scan(), ma per sicurezza:
    exit(0);
}

if (getenv('AIED_SSE_SMOKE_ERROR_CHILD') === '1') {
    // Modalita' figlio "error path": service che throw exception → controller
    // deve catturare e emettere event: error invece di propagare.
    $throwingService = new class extends ContentBrainService {
        public function __construct() {}
        public function scan(int $siteId, array $articleUrls = [], ?callable $emit = null): array {
            throw new \RuntimeException('AI provider down');
        }
    };
    $errCtl = new class($throwingService) extends ContentBrainController {
        protected function jsonInput(): array { return []; }
    };
    LicenseAuthMiddleware::$currentSite = ['id' => 7, 'user_id' => 1, 'domain' => 'x.test', 'status' => 'active'];
    $errCtl->scan();
    exit(0);
}

// Modalita' padre: spawna se stesso come figlio, leggi output.
$phpBin = PHP_BINARY;
$selfPath = __FILE__;
$cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($selfPath);

$env = $_ENV + getenv() + ['AIED_SSE_SMOKE_CHILD' => '1'];
$envPairs = [];
foreach ($env as $k => $v) {
    if (!is_string($k) || !is_scalar($v)) continue;
    $envPairs[$k] = (string) $v;
}

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$proc = proc_open($cmd, $descriptors, $pipes, null, $envPairs);
if (!is_resource($proc)) {
    fwrite(STDERR, "[smoke] proc_open FAILED\n");
    exit(2);
}
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);

// ---------------------------------------------------------------
// Assertions sull'output catturato
// ---------------------------------------------------------------

$checks = [];

$checks['exit_code_ok'] = $exitCode === 0;
$checks['has_started_event'] = (bool) preg_match('/^event: started$/m', $stdout);
$checks['has_article_scraped_x2'] = preg_match_all('/^event: article_scraped$/m', $stdout) === 2;
$checks['has_aggregating_event'] = (bool) preg_match('/^event: aggregating$/m', $stdout);
$checks['has_analyzing_event'] = (bool) preg_match('/^event: analyzing$/m', $stdout);
$checks['has_completed_event'] = (bool) preg_match('/^event: completed$/m', $stdout);
$checks['data_lines_json'] = (bool) preg_match('/^data: \{.*"tone":"friendly".*\}$/m', $stdout);
$checks['sse_event_format'] = (bool) preg_match('/event: started\ndata: \{[^}]*"sample_size":2[^}]*\}\n\n/', $stdout);
$checks['event_order_started_before_completed'] = strpos($stdout, 'event: started') < strpos($stdout, 'event: completed');
$checks['site_id_propagated'] = (bool) preg_match('/"site_id":999/', $stdout);

// ---------------------------------------------------------------
// Secondo subprocess: verifica error path
// ---------------------------------------------------------------

$errEnvPairs = $envPairs;
unset($errEnvPairs['AIED_SSE_SMOKE_CHILD']);
$errEnvPairs['AIED_SSE_SMOKE_ERROR_CHILD'] = '1';

$proc2 = proc_open($cmd, $descriptors, $pipes2, null, $errEnvPairs);
$errStdout = '';
if (is_resource($proc2)) {
    fclose($pipes2[0]);
    $errStdout = stream_get_contents($pipes2[1]);
    fclose($pipes2[1]);
    fclose($pipes2[2]);
    proc_close($proc2);
}

$checks['error_path_emits_error_event'] = (bool) preg_match('/^event: error$/m', $errStdout);
$checks['error_path_contains_message'] = (bool) preg_match('/AI provider down/', $errStdout);
$checks['error_path_retry_possible'] = (bool) preg_match('/"retry_possible":true/', $errStdout);

$pass = 0;
$fail = 0;
foreach ($checks as $name => $ok) {
    if ($ok) {
        $pass++;
        echo "[PASS] {$name}\n";
    } else {
        $fail++;
        echo "[FAIL] {$name}\n";
    }
}

echo "\n----- captured stdout (first 1500 chars) -----\n";
echo substr($stdout, 0, 1500) . "\n";
echo "----- captured stderr (first 500 chars) -----\n";
echo substr($stderr, 0, 500) . "\n";
echo "----- summary -----\n";
echo "PASS: {$pass} / " . count($checks) . " | FAIL: {$fail}\n";

exit($fail === 0 ? 0 : 1);
