<?php

declare(strict_types=1);

/**
 * Smoke test isolato M2.3 — Definition of Done check.
 *
 * Verifica i 4 scenari DoD degli endpoint GET / PUT /content-brain
 * (handler ContentBrainController::show / ::update):
 *
 *   1. GET pre-scan  → 404 { error }                        (content_brain inesistente)
 *   2. GET post-scan → 200 { content_brain: {...} }         (record presente)
 *   3. PUT partial   → 200 { content_brain: {...merged} }   (patch valido)
 *   4. PUT tone bad  → 422 { error, errors: [{field,...}] } (validazione fallisce)
 *
 * Non chiama DB ne' AI reale: usa mock di ContentBrainService (validatePatch reale,
 * get/update mockati). Non passa per il Router: invoca direttamente il Controller con
 * service injection + mock di jsonInput() e LicenseAuthMiddleware::$currentSite.
 *
 * Lo status HTTP in CLI non viene "spedito": lo cattura uno shutdown handler che
 * stampa __STATUS__:<code> appena prima dell'exit dentro BaseController::emit().
 *
 * Esecuzione:
 *   php api/editorial/tests/content_brain_get_put_smoke.php
 *
 * Atteso: tutti i check PASS + exit code 0.
 */

require_once __DIR__ . '/bootstrap.php';

use Editorial\Controllers\ContentBrainController;
use Editorial\Middleware\LicenseAuthMiddleware;
use Editorial\Services\ContentBrainService;

// ---------------------------------------------------------------
// Fixture brain (ritornato dai mock get()/update())
// ---------------------------------------------------------------

function aied_smoke_brain(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'site_id' => 42,
        'domain' => 'smoke.test',
        'brand_voice_summary' => 'Tono amichevole e familiare.',
        'brand_voice_examples' => ['Esempio 1', 'Esempio 2', 'Esempio 3'],
        'tone' => 'friendly',
        'target_audience' => 'Appassionati di vino',
        'site_topic' => 'Vino artigianale italiano',
        'glossary' => [
            'brand_names' => ['Vinicola Smoke'],
            'products' => [],
            'people' => [],
            'avoid_terms' => [],
            'preferred_terms' => [],
        ],
        'editorial_guidelines' => ['do' => [], 'dont' => [], 'emphasize' => []],
        'language' => 'it',
        'last_scan_at' => '2026-05-25 12:00:00',
        'scanned_articles_count' => 12,
    ], $overrides);
}

// ---------------------------------------------------------------
// Child mode: esegue un singolo scenario e termina.
// Lo scenario e' selezionato da AIED_CB_CASE.
// ---------------------------------------------------------------

$case = getenv('AIED_CB_CASE');
if ($case !== false && $case !== '') {

    // Cattura status HTTP: BaseController::emit() chiama http_response_code()
    // poi exit → lo shutdown handler stampa il codice corrente.
    register_shutdown_function(static function (): void {
        $code = http_response_code();
        fwrite(STDOUT, "\n__STATUS__:" . ($code === false ? 0 : (int) $code) . "\n");
    });

    LicenseAuthMiddleware::$currentSite = [
        'id' => 42, 'user_id' => 1, 'domain' => 'smoke.test', 'status' => 'active',
    ];

    // Mock service: validatePatch reale (logica pura), get/update mockati.
    $mkService = static function (string $mode): ContentBrainService {
        return new class($mode) extends ContentBrainService {
            private string $mode;
            /** @noinspection MagicMethodsValidityInspection */
            public function __construct(string $mode = '') { $this->mode = $mode; }

            public function get(int $siteId): ?array
            {
                return $this->mode === 'no_brain' ? null : aied_smoke_brain();
            }

            public function update(int $siteId, array $patch): array
            {
                if ($this->mode === 'no_brain') {
                    return ['success' => false, 'errors' => ['content_brain_not_found']];
                }
                // Simula merge: applica tone dal patch
                $brain = aied_smoke_brain();
                if (array_key_exists('tone', $patch)) {
                    $brain['tone'] = (string) $patch['tone'];
                }
                if (array_key_exists('site_topic', $patch)) {
                    $brain['site_topic'] = (string) $patch['site_topic'];
                }
                return ['success' => true, 'content_brain' => $brain];
            }
        };
    };

    $mkController = static function (ContentBrainService $svc, array $input): ContentBrainController {
        $c = new class($svc) extends ContentBrainController {
            public array $mockInput = [];
            protected function jsonInput(): array { return $this->mockInput; }
        };
        $c->mockInput = $input;
        return $c;
    };

    switch ($case) {
        case 'get_404':
            $mkController($mkService('no_brain'), [])->show();
            break;
        case 'get_200':
            $mkController($mkService('ok'), [])->show();
            break;
        case 'put_200':
            $mkController($mkService('ok'), ['tone' => 'professional', 'site_topic' => 'Nuovo topic'])->update();
            break;
        case 'put_422':
            $mkController($mkService('ok'), ['tone' => 'invalid-tone'])->update();
            break;
        case 'put_404':
            $mkController($mkService('no_brain'), ['tone' => 'professional'])->update();
            break;
    }
    exit(0); // unreachable: emit() fa exit
}

// ---------------------------------------------------------------
// Parent mode: spawna un figlio per scenario, parse output.
// ---------------------------------------------------------------

$phpBin = PHP_BINARY;
$selfPath = __FILE__;
$cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($selfPath);

$baseEnv = [];
foreach ($_ENV + getenv() as $k => $v) {
    if (is_string($k) && is_scalar($v)) {
        $baseEnv[$k] = (string) $v;
    }
}

$runCase = static function (string $case) use ($cmd, $baseEnv): array {
    $env = $baseEnv;
    $env['AIED_CB_CASE'] = $case;
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptors, $pipes, null, $env);
    if (!is_resource($proc)) {
        return ['status' => -1, 'body' => '', 'raw' => ''];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    $status = 0;
    if (preg_match('/__STATUS__:(\d+)/', $out, $m)) {
        $status = (int) $m[1];
    }
    // Body = output prima del marker __STATUS__
    $body = preg_replace('/\n?__STATUS__:\d+\n?/', '', $out);
    $json = json_decode(trim((string) $body), true);
    return ['status' => $status, 'body' => is_array($json) ? $json : null, 'raw' => $out];
};

$checks = [];

// --- Scenario 1: GET pre-scan → 404 ---
$r = $runCase('get_404');
$checks['get_404_status'] = $r['status'] === 404;
$checks['get_404_has_error'] = isset($r['body']['error']) && str_contains((string) $r['body']['error'], 'onboarding');

// --- Scenario 2: GET post-scan → 200 ---
$r = $runCase('get_200');
$checks['get_200_status'] = $r['status'] === 200;
$checks['get_200_has_brain'] = isset($r['body']['content_brain']['tone']);
$checks['get_200_tone_friendly'] = ($r['body']['content_brain']['tone'] ?? null) === 'friendly';
$checks['get_200_has_examples'] = is_array($r['body']['content_brain']['brand_voice_examples'] ?? null);

// --- Scenario 3: PUT partial valido → 200 ---
$r = $runCase('put_200');
$checks['put_200_status'] = $r['status'] === 200;
$checks['put_200_tone_updated'] = ($r['body']['content_brain']['tone'] ?? null) === 'professional';
$checks['put_200_topic_merged'] = ($r['body']['content_brain']['site_topic'] ?? null) === 'Nuovo topic';

// --- Scenario 4: PUT tone invalido → 422 ---
$r = $runCase('put_422');
$checks['put_422_status'] = $r['status'] === 422;
$checks['put_422_has_errors'] = is_array($r['body']['errors'] ?? null) && count($r['body']['errors']) >= 1;
$checks['put_422_error_on_tone'] = ($r['body']['errors'][0]['field'] ?? null) === 'tone';

// --- Scenario 5 (bonus): PUT su brain inesistente → 404 ---
$r = $runCase('put_404');
$checks['put_404_status'] = $r['status'] === 404;

// ---------------------------------------------------------------
// Report
// ---------------------------------------------------------------

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

echo "\n----- summary -----\n";
echo "PASS: {$pass} / " . count($checks) . " | FAIL: {$fail}\n";

exit($fail === 0 ? 0 : 1);
