<?php
/**
 * M1.6.k — Smoke test plugin-side LicenseManager + ApiClient
 *
 * Test in isolamento (NO WordPress installato): mockiamo le funzioni WP
 * essenziali (get_option/update_option/delete_option/wp_remote_*) e verifichiamo
 * il flusso activate → token salvato → status() corretto → deactivate → state pulito.
 *
 * Uso:
 *   /c/laragon/bin/php/.../php.exe plugins/ainstein-editorial/tests/plugin_license_smoke.php
 *
 * Cosa NON copre questo test:
 *   - render UI License page (richiede browser/WP)
 *   - admin-post.php submit flow (richiede WP routing)
 *   - capability check / nonce verify (richiede WP)
 * Quelli verranno coperti in M1.8 (smoke E2E su WP locale).
 */

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('AIED_VERSION', '0.1.0-test');
define('AIED_PATH', dirname(__DIR__) . '/');
define('AIED_URL', 'http://test.local/wp-content/plugins/ainstein-editorial/');
define('AIED_BASENAME', 'ainstein-editorial/plugin.php');
define('AIED_API_BASE', 'http://localhost/seo-toolkit/api/editorial/v1');

// =============================================================================
// Mock minimo di WordPress
// =============================================================================

$GLOBALS['_test_options'] = [];
$GLOBALS['_test_http_log'] = [];      // log delle wp_remote_* call
$GLOBALS['_test_http_responses'] = [];  // queue di risposte per le call

function get_option(string $key, $default = false) {
    return $GLOBALS['_test_options'][$key] ?? $default;
}
function update_option(string $key, $value, bool $autoload = true): bool {
    $GLOBALS['_test_options'][$key] = $value;
    return true;
}
function delete_option(string $key): bool {
    unset($GLOBALS['_test_options'][$key]);
    return true;
}
function get_bloginfo(string $what = ''): string {
    return $what === 'version' ? '6.4.2' : 'Test Blog';
}
function home_url(string $path = '/'): string {
    return 'https://test-blog.local' . $path;
}
function wp_json_encode($data): string {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
function is_wp_error($thing): bool {
    return $thing instanceof WP_Error;
}
function wp_remote_retrieve_response_code($resp): int {
    return is_array($resp) ? (int) ($resp['response']['code'] ?? 0) : 0;
}
function wp_remote_retrieve_body($resp): string {
    return is_array($resp) ? (string) ($resp['body'] ?? '') : '';
}

/**
 * Simula wp_remote_request: registra la call, restituisce la prossima risposta in queue.
 * Se queue vuota → ritorna WP_Error "no mock response".
 */
function wp_remote_request(string $url, array $args = []) {
    $GLOBALS['_test_http_log'][] = ['url' => $url, 'method' => $args['method'] ?? 'GET', 'args' => $args];
    $responses = &$GLOBALS['_test_http_responses'];
    if (empty($responses)) {
        return new WP_Error('no_mock', 'No mock response queued for ' . $url);
    }
    return array_shift($responses);
}
function wp_remote_post(string $url, array $args = []) {
    $args['method'] = 'POST';
    return wp_remote_request($url, $args);
}
function wp_remote_get(string $url, array $args = []) {
    $args['method'] = 'GET';
    return wp_remote_request($url, $args);
}

class WP_Error {
    public function __construct(public string $code = '', public string $message = '') {}
    public function get_error_message(): string { return $this->message; }
}

// Helper per costruire response mock
function mockResponse(int $status, array $body): array {
    return [
        'response' => ['code' => $status],
        'body' => json_encode($body),
    ];
}
function queueResponse($resp): void {
    // Accetta array (mock response) o WP_Error (network failure simulato)
    $GLOBALS['_test_http_responses'][] = $resp;
}
function resetMocks(): void {
    $GLOBALS['_test_options'] = [];
    $GLOBALS['_test_http_log'] = [];
    $GLOBALS['_test_http_responses'] = [];
}

// =============================================================================
// Autoload plugin classes
// =============================================================================

spl_autoload_register(function ($class) {
    $prefixes = [
        'Ainstein\\Editorial\\Includes\\' => AIED_PATH . 'src/Includes/',
        'Ainstein\\Editorial\\Admin\\' => AIED_PATH . 'src/Admin/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $rel = substr($class, strlen($prefix));
            $file = $base . str_replace('\\', '/', $rel) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

use Ainstein\Editorial\Includes\ApiClient;
use Ainstein\Editorial\Includes\LicenseManager;

// =============================================================================
// Test harness
// =============================================================================

$passed = 0;
$failed = 0;

function step(string $name, callable $fn) {
    global $passed, $failed;
    try {
        $msg = (string) $fn();
        printf("  [OK]    %s  %s\n", $name, $msg);
        $passed++;
    } catch (\Throwable $e) {
        printf("  [FAIL]  %s  %s\n", $name, $e->getMessage());
        $failed++;
    }
}

function assertEq($expected, $actual, string $what): string {
    if ($expected !== $actual) {
        throw new RuntimeException("$what: expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
    return "$what=" . var_export($actual, true);
}

// =============================================================================
// TEST 1: activate happy path
// =============================================================================
echo "Test 1: activate() happy path\n";
resetMocks();
queueResponse(mockResponse(200, [
    'api_token' => 'jwt.fake.token123',
    'expires_in' => 86400,
    'tier' => 'pro',
    'sites_used' => 1,
    'sites_limit' => 3,
    'site_id' => 42,
    'user' => ['email' => 'test@example.com'],
]));

$lm = new LicenseManager();
step('activate returns ok', function () use ($lm) {
    $r = $lm->activate('PRO-XXXX-YYYY-ZZZZ', 'test@example.com');
    assertEq(true, $r['ok'], 'ok');
    assertEq('pro', $r['tier'], 'tier');
    assertEq(3, $r['sites_limit'], 'sites_limit');
    return 'tier=pro sites=1/3';
});

step('wp_options populated', function () {
    assertEq('jwt.fake.token123', get_option('aied_api_token'), 'aied_api_token');
    assertEq('pro', get_option('aied_tier'), 'aied_tier');
    assertEq(3, get_option('aied_sites_limit'), 'aied_sites_limit');
    assertEq(42, get_option('aied_site_id'), 'aied_site_id');
    assertEq('test@example.com', get_option('aied_user_email'), 'aied_user_email');
    return 'all options stored';
});

step('http call sent to /activate with correct headers', function () {
    $log = $GLOBALS['_test_http_log'];
    if (count($log) !== 1) throw new RuntimeException('expected 1 http call, got ' . count($log));
    $call = $log[0];
    assertEq('POST', $call['method'], 'method');
    if (!str_ends_with($call['url'], '/activate')) {
        throw new RuntimeException('wrong endpoint: ' . $call['url']);
    }
    $hdrs = $call['args']['headers'];
    assertEq('PRO-XXXX-YYYY-ZZZZ', $hdrs['X-License-Key'], 'X-License-Key');
    assertEq('test-blog.local', $hdrs['X-Site-Domain'], 'X-Site-Domain');
    if (isset($hdrs['Authorization'])) {
        throw new RuntimeException('Authorization header must NOT be present on /activate (auth=license)');
    }
    return 'headers ok';
});

// =============================================================================
// TEST 2: isActive + status
// =============================================================================
echo "\nTest 2: isActive() + status() after activation\n";
step('isActive=true', function () use ($lm) {
    assertEq(true, $lm->isActive(), 'isActive');
    return '';
});
step('status() reflects activated state', function () use ($lm) {
    $s = $lm->status();
    assertEq(true, $s['active'], 'active');
    assertEq('pro', $s['tier'], 'tier');
    assertEq('test@example.com', $s['email'], 'email');
    assertEq(1, $s['sites_used'], 'sites_used');
    return 'ok';
});

// =============================================================================
// TEST 3: activate con license invalida → backend ritorna 422
// =============================================================================
echo "\nTest 3: activate() with invalid license → translated message\n";
resetMocks();
queueResponse(mockResponse(422, ['error' => 'License key not valid']));

$lm = new LicenseManager();
step('returns ok=false + translated message', function () use ($lm) {
    $r = $lm->activate('BAD-KEY', 'test@example.com');
    assertEq(false, $r['ok'], 'ok');
    if (!str_contains($r['message'], 'License key non valida')) {
        throw new RuntimeException('expected italian translation, got: ' . $r['message']);
    }
    return "msg='{$r['message']}'";
});
step('aied_license_key cleared on failure', function () {
    if (get_option('aied_license_key', false) !== false) {
        throw new RuntimeException('aied_license_key should be deleted after failed activation');
    }
    return 'cleared';
});

// =============================================================================
// TEST 4: activate con site limit raggiunto
// =============================================================================
echo "\nTest 4: activate() site limit reached\n";
resetMocks();
queueResponse(mockResponse(422, ['error' => "Site limit reached. Your tier 'pro' allows 3 sites."]));
$lm = new LicenseManager();
step('returns translated site-limit message', function () use ($lm) {
    $r = $lm->activate('PRO-XXXX', 'test@example.com');
    assertEq(false, $r['ok'], 'ok');
    if (!str_contains(strtolower($r['message']), 'limite di siti')) {
        throw new RuntimeException('expected italian site-limit translation, got: ' . $r['message']);
    }
    return $r['message'];
});

// =============================================================================
// TEST 5: deactivate happy path
// =============================================================================
echo "\nTest 5: deactivate() clears local state\n";
resetMocks();
queueResponse(mockResponse(200, [
    'api_token' => 'jwt.fake', 'expires_in' => 86400, 'tier' => 'pro',
    'sites_used' => 1, 'sites_limit' => 3, 'site_id' => 7, 'user' => ['email' => 'x@y.com'],
]));
$lm = new LicenseManager();
$lm->activate('K', 'x@y.com');

queueResponse(mockResponse(200, ['success' => true, 'sites_freed' => 1]));
step('deactivate ok', function () use ($lm) {
    $r = $lm->deactivate();
    assertEq(true, $r['ok'], 'ok');
    return $r['message'];
});
step('local state cleared', function () {
    if (get_option('aied_api_token', false) !== false) throw new RuntimeException('aied_api_token should be cleared');
    if (get_option('aied_tier', false) !== false) throw new RuntimeException('aied_tier should be cleared');
    if (get_option('aied_site_id', false) !== false) throw new RuntimeException('aied_site_id should be cleared');
    return 'all transient options cleared';
});
step('license_key preserved for UX', function () {
    assertEq('K', get_option('aied_license_key'), 'aied_license_key (kept)');
    return 'kept';
});

// =============================================================================
// TEST 6: deactivate con backend giù → comunque pulisce locale + warning
// =============================================================================
echo "\nTest 6: deactivate() with backend network error\n";
resetMocks();
queueResponse(mockResponse(200, [
    'api_token' => 'jwt.fake', 'expires_in' => 86400, 'tier' => 'pro',
    'sites_used' => 1, 'sites_limit' => 3, 'site_id' => 7, 'user' => ['email' => 'x@y.com'],
]));
$lm = new LicenseManager();
$lm->activate('K', 'x@y.com');

queueResponse(new WP_Error('http_request_failed', 'cURL error 7: Could not resolve host'));
step('returns ok=false + Italian warning', function () use ($lm) {
    $r = $lm->deactivate();
    assertEq(false, $r['ok'], 'ok');
    if (!str_contains($r['message'], 'Disattivazione locale completata')) {
        throw new RuntimeException('expected partial-success warning, got: ' . $r['message']);
    }
    return 'partial';
});
step('local state still cleared (best-effort)', function () {
    if (get_option('aied_api_token', false) !== false) throw new RuntimeException('local state should be cleared');
    return 'cleared anyway';
});

// =============================================================================
// TEST 7: ApiClient 401 → auto refresh + retry
// =============================================================================
echo "\nTest 7: ApiClient auto-refresh on 401\n";
resetMocks();
// State: license + token salvati (simuliamo utente già attivato)
update_option('aied_license_key', 'KEY-XXX', false);
update_option('aied_api_token', 'OLD-TOKEN', false);
update_option('aied_api_token_expires_at', time() + 100, false);  // ancora valido per ApiClient (lui controlla 401 dal server)

// Response 1: 401 al primo POST /content-brain
queueResponse(mockResponse(401, ['error' => 'TOKEN_EXPIRED']));
// Response 2: refresh-token success
queueResponse(mockResponse(200, ['api_token' => 'NEW-TOKEN', 'expires_in' => 86400, 'site_id' => 7]));
// Response 3: retry POST /content-brain → 200
queueResponse(mockResponse(200, ['ok' => true, 'brand_voice' => 'crisp']));

$api = new ApiClient();
step('first call 401 → refresh → retry 200', function () use ($api) {
    $r = $api->post('/content-brain', ['url' => 'https://test.com']);
    assertEq(true, $r['ok'], 'ok');
    assertEq(200, $r['status'], 'status');
    return 'retry success';
});
step('new token stored', function () {
    assertEq('NEW-TOKEN', get_option('aied_api_token'), 'aied_api_token');
    return '';
});
step('http log has 3 calls (orig POST + refresh + retry)', function () {
    assertEq(3, count($GLOBALS['_test_http_log']), 'http_call_count');
    return '3 calls as expected';
});

// =============================================================================
// TEST 8: ApiClient network error → translated user-friendly message
// =============================================================================
echo "\nTest 8: ApiClient network error translation\n";
resetMocks();
update_option('aied_license_key', 'K', false);
queueResponse(new WP_Error('http_request_failed', 'cURL error 28: Operation timed out'));
$api = new ApiClient();
step('timeout error → italian message', function () use ($api) {
    $r = $api->post('/articles/generate', ['kw' => 'x']);
    assertEq(false, $r['ok'], 'ok');
    if (!str_contains($r['error'], 'non risponde in tempo')) {
        throw new RuntimeException('expected timeout translation, got: ' . $r['error']);
    }
    return $r['error'];
});

// =============================================================================
// Summary
// =============================================================================
echo "\n" . str_repeat('=', 70) . "\n";
echo "Risultato plugin-side: {$passed} passati / {$failed} falliti\n";
exit($failed > 0 ? 1 : 0);
