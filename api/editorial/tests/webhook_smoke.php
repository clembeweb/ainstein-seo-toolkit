<?php
/**
 * M1.5 — Webhook smoke test runner
 *
 * Esegue 6 scenari LemonSqueezy webhook + idempotency check + signature invalid:
 *   1. subscription_created
 *   2. subscription_updated      (tier upgrade starter → pro)
 *   3. subscription_payment_failed
 *   4. subscription_cancelled
 *   5. order_created             (top-up pack)
 *   6. license_key_created
 *   7. idempotency replay        (1° evento → duplicate skip)
 *   8. signature invalid         (errore 401 dal controller)
 *
 * Uso:
 *   /c/laragon/bin/php/.../php.exe api/editorial/tests/webhook_smoke.php
 *
 * Prerequisiti:
 *   - MySQL up + migration M1.5 applicata
 *   - LEMONSQUEEZY_WEBHOOK_SECRET impostato in .env.local (o env). Se mancante,
 *     lo script setta un secret di test in runtime.
 *
 * Lo script:
 *   - Inserisce/aggiorna un aied_users di test (license TEST-WEBHOOK-XXXX-9999)
 *   - Invoca WebhookProcessor::process() in-process (no HTTP)
 *   - Verifica DB state dopo ogni evento (tier, subscription_status, etc.)
 *   - Test invalid signature usa WebhookController via callable diretto, simulando
 *     PHP_INPUT con uopz/runkit non disponibile → test ridotto al verifier
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 3));
chdir(BASE_PATH);

// --- Boot framework minimo ---
require BASE_PATH . '/config/environment.php';

if (!getenv('LEMONSQUEEZY_WEBHOOK_SECRET')) {
    putenv('LEMONSQUEEZY_WEBHOOK_SECRET=test_secret_smoke_M15_LongEnough123');
    $_ENV['LEMONSQUEEZY_WEBHOOK_SECRET'] = 'test_secret_smoke_M15_LongEnough123';
}

// Autoloader (replica public/index.php)
spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\Connectors\\' => BASE_PATH . '/services/connectors/',
        'Services\\' => BASE_PATH . '/services/',
        'Editorial\\' => BASE_PATH . '/api/editorial/',
    ];
    foreach ($paths as $prefix => $base) {
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

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

use Core\Database;
use Editorial\Services\WebhookProcessor;
use Editorial\Services\WebhookVerifier;

// --- Test runner harness ---
$passed = 0;
$failed = 0;
$results = [];

function step(string $name, callable $fn) {
    global $passed, $failed, $results;
    $ok = false;
    $msg = '';
    try {
        $msg = (string) $fn();
        $ok = true;
    } catch (\Throwable $e) {
        $msg = $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
    }
    $results[] = ['name' => $name, 'ok' => $ok, 'msg' => $msg];
    if ($ok) { $passed++; } else { $failed++; }
    printf("  %s  %s  %s\n", $ok ? '[OK]' : '[FAIL]', $name, $msg);
}

function assertEq($expected, $actual, string $what): string {
    if ($expected !== $actual) {
        throw new RuntimeException("$what: expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
    return "$what=" . var_export($actual, true);
}

// --- Seed user di test ---
$pdo = Database::getConnection();

$LICENSE = 'TEST-WEBHOOK-XXXX-9999';
$EMAIL = 'webhook-smoke@editorial.test';
$SUB_ID = 'sub_smoke_001';
$CUST_ID = 'cust_smoke_001';

// Cleanup precedente run (idempotenza dello smoke test stesso)
$pdo->prepare("DELETE FROM aied_api_logs WHERE provider = 'lemonsqueezy_webhook' AND request_payload LIKE ?")
    ->execute(['%' . $SUB_ID . '%']);

$existing = $pdo->prepare("SELECT id FROM aied_users WHERE license_key = ?");
$existing->execute([$LICENSE]);
$userId = (int) ($existing->fetchColumn() ?: 0);

if ($userId === 0) {
    $pdo->prepare(
        "INSERT INTO aied_users
           (email, license_key, lemon_squeezy_customer_id, lemon_squeezy_subscription_id,
            tier, subscription_status, locale, created_at)
         VALUES (?, ?, ?, ?, 'starter', 'active', 'it', NOW())"
    )->execute([$EMAIL, $LICENSE, $CUST_ID, $SUB_ID]);
    $userId = (int) $pdo->lastInsertId();
} else {
    $pdo->prepare(
        "UPDATE aied_users
         SET email = ?, lemon_squeezy_customer_id = ?, lemon_squeezy_subscription_id = ?,
             tier = 'starter', subscription_status = 'active',
             subscription_renews_at = NULL,
             topup_balance_articles = 0, last_payment_failed_at = NULL
         WHERE id = ?"
    )->execute([$EMAIL, $CUST_ID, $SUB_ID, $userId]);
}

echo "Seed: aied_users#$userId  license=$LICENSE  sub=$SUB_ID  cust=$CUST_ID\n";
echo str_repeat('-', 70) . "\n";

// --- Helper: fabbrica payload LS ---
function lsSubscriptionPayload(string $event, string $subId, string $customerId, string $email, string $variantId, string $status, ?string $renewsAt, string $eventId): array {
    return [
        'meta' => [
            'event_name' => $event,
            'webhook_id' => $eventId,
            'test_mode' => true,
        ],
        'data' => [
            'type' => 'subscriptions',
            'id' => $subId,
            'attributes' => [
                'customer_id' => $customerId,
                'user_email' => $email,
                'variant_id' => $variantId,
                'status' => $status,
                'renews_at' => $renewsAt,
            ],
        ],
    ];
}

function lsOrderPayload(string $customerId, string $email, string $variantId, string $variantName, string $eventId): array {
    return [
        'meta' => [
            'event_name' => 'order_created',
            'webhook_id' => $eventId,
            'test_mode' => true,
        ],
        'data' => [
            'type' => 'orders',
            'id' => 'order_' . substr($eventId, -6),
            'attributes' => [
                'customer_id' => $customerId,
                'user_email' => $email,
                'first_order_item' => [
                    'variant_id' => $variantId,
                    'variant_name' => $variantName,
                ],
            ],
        ],
    ];
}

function lsLicenseKeyPayload(string $licenseKey, string $eventId): array {
    return [
        'meta' => [
            'event_name' => 'license_key_created',
            'webhook_id' => $eventId,
            'test_mode' => true,
        ],
        'data' => [
            'type' => 'license-keys',
            'id' => 'lkey_' . substr($eventId, -6),
            'attributes' => [
                'key' => $licenseKey,
            ],
        ],
    ];
}

$proc = new WebhookProcessor();
$processor = function (array $payload) use ($proc) {
    $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
    return $proc->process($payload, $raw);
};

$reload = function () use ($pdo, $userId): array {
    $stmt = $pdo->prepare("SELECT tier, subscription_status, subscription_renews_at, topup_balance_articles, last_payment_failed_at FROM aied_users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
};

// =============================================================================
echo "Test 1: subscription_created → upserts metadata + tier=pro\n";
$evt1 = 'evt_' . bin2hex(random_bytes(6));
step('subscription_created', function () use ($processor, $reload, $evt1, $SUB_ID, $CUST_ID, $EMAIL) {
    $payload = lsSubscriptionPayload(
        'subscription_created', $SUB_ID, $CUST_ID, $EMAIL,
        'variant_pro_test', 'active', gmdate('c', strtotime('+30 days')), $evt1
    );
    $r = ($processor)($payload);
    assertEq('processed', $r['status'], 'status');
    $row = $reload();
    assertEq('pro', $row['tier'], 'tier');
    assertEq('active', $row['subscription_status'], 'subscription_status');
    return "renews_at={$row['subscription_renews_at']}";
});

// =============================================================================
echo "\nTest 2: subscription_updated → upgrade tier=business + renews_at refresh\n";
$evt2 = 'evt_' . bin2hex(random_bytes(6));
step('subscription_updated', function () use ($processor, $reload, $evt2, $SUB_ID, $CUST_ID, $EMAIL) {
    $payload = lsSubscriptionPayload(
        'subscription_updated', $SUB_ID, $CUST_ID, $EMAIL,
        'variant_business_test', 'active', gmdate('c', strtotime('+45 days')), $evt2
    );
    $r = ($processor)($payload);
    assertEq('processed', $r['status'], 'status');
    $row = $reload();
    assertEq('business', $row['tier'], 'tier');
    assertEq('active', $row['subscription_status'], 'subscription_status');
    return "renews_at={$row['subscription_renews_at']}";
});

// =============================================================================
echo "\nTest 3: subscription_payment_failed → status=past_due + last_payment_failed_at\n";
$evt3 = 'evt_' . bin2hex(random_bytes(6));
step('subscription_payment_failed', function () use ($processor, $reload, $evt3, $SUB_ID, $CUST_ID, $EMAIL) {
    $payload = lsSubscriptionPayload(
        'subscription_payment_failed', $SUB_ID, $CUST_ID, $EMAIL,
        'variant_business_test', 'past_due', gmdate('c', strtotime('+45 days')), $evt3
    );
    $r = ($processor)($payload);
    assertEq('processed', $r['status'], 'status');
    $row = $reload();
    assertEq('past_due', $row['subscription_status'], 'subscription_status');
    if (empty($row['last_payment_failed_at'])) {
        throw new RuntimeException('last_payment_failed_at not set');
    }
    return "failed_at={$row['last_payment_failed_at']}";
});

// =============================================================================
echo "\nTest 4: subscription_cancelled → status=cancelled (renews_at preserved)\n";
$evt4 = 'evt_' . bin2hex(random_bytes(6));
step('subscription_cancelled', function () use ($processor, $reload, $evt4, $SUB_ID, $CUST_ID, $EMAIL) {
    $payload = lsSubscriptionPayload(
        'subscription_cancelled', $SUB_ID, $CUST_ID, $EMAIL,
        'variant_business_test', 'cancelled', gmdate('c', strtotime('+45 days')), $evt4
    );
    $before = $reload();
    $r = ($processor)($payload);
    assertEq('processed', $r['status'], 'status');
    $row = $reload();
    assertEq('cancelled', $row['subscription_status'], 'subscription_status');
    assertEq($before['subscription_renews_at'], $row['subscription_renews_at'], 'renews_at_preserved');
    return "ok";
});

// =============================================================================
echo "\nTest 5: order_created top-up → credits +25 articoli\n";
$evt5 = 'evt_' . bin2hex(random_bytes(6));
step('order_created (top-up 25)', function () use ($processor, $reload, $evt5, $CUST_ID, $EMAIL) {
    $before = $reload();
    $beforeCredits = (int) $before['topup_balance_articles'];
    $payload = lsOrderPayload($CUST_ID, $EMAIL, 'variant_topup25_test', 'Top-up Pack 25', $evt5);
    $r = ($processor)($payload);
    assertEq('processed', $r['status'], 'status');
    $row = $reload();
    assertEq($beforeCredits + 25, (int) $row['topup_balance_articles'], 'topup_balance_articles');
    return "credits={$row['topup_balance_articles']}";
});

// =============================================================================
echo "\nTest 6: license_key_created → trace log\n";
$evt6 = 'evt_' . bin2hex(random_bytes(6));
step('license_key_created', function () use ($processor, $evt6, $LICENSE, $userId) {
    $payload = lsLicenseKeyPayload($LICENSE, $evt6);
    $r = ($processor)($payload);
    assertEq('processed', $r['status'], 'status');
    assertEq($userId, $r['user_id'], 'user_id');
    return "ok";
});

// =============================================================================
echo "\nTest 7: Idempotency replay → 1° evento già processato → 'duplicate'\n";
step('idempotency replay (evt1)', function () use ($processor, $evt1, $SUB_ID, $CUST_ID, $EMAIL) {
    $payload = lsSubscriptionPayload(
        'subscription_created', $SUB_ID, $CUST_ID, $EMAIL,
        'variant_pro_test', 'active', gmdate('c', strtotime('+30 days')), $evt1
    );
    $r = ($processor)($payload);
    assertEq('duplicate', $r['status'], 'status');
    return $r['message'] ?? '';
});

// =============================================================================
echo "\nTest 8: WebhookVerifier signature verification\n";
step('verifier rejects bad signature', function () {
    $v = new WebhookVerifier();
    $body = '{"test":1}';
    $good = $v->sign($body);
    if (!$v->verify($body, $good)) throw new RuntimeException('valid sig rejected');
    if ($v->verify($body, 'deadbeef')) throw new RuntimeException('invalid sig accepted');
    if ($v->verify($body, '')) throw new RuntimeException('empty sig accepted');
    return "ok";
});

// =============================================================================
echo "\nTest 9: aied_api_logs idempotency UNIQUE index\n";
step('aied_api_logs UNIQUE (provider, external_event_id)', function () use ($pdo, $evt1) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM aied_api_logs WHERE provider='lemonsqueezy_webhook' AND external_event_id = ?");
    $stmt->execute([$evt1]);
    $count = (int) $stmt->fetchColumn();
    // Dovrebbe essere 1 (la replay non inserisce duplicato grazie a INSERT IGNORE)
    assertEq(1, $count, 'log_count_for_evt1');
    return "single log row for event $evt1";
});

// =============================================================================
echo "\n" . str_repeat('=', 70) . "\n";
echo "Risultato: {$passed} passati / {$failed} falliti\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
