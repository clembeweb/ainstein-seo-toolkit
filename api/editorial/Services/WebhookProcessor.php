<?php

namespace Editorial\Services;

use Core\Database;
use Core\Logger;
use PDO;
use RuntimeException;

/**
 * WebhookProcessor — orchestratore eventi LemonSqueezy.
 *
 * Pattern claim-then-process per idempotency atomica:
 *   1. INSERT IGNORE log row in aied_api_logs (claim via UNIQUE provider+event_id).
 *      Se rowCount=0 → un altro tentativo ha già claimato → duplicate (no-op).
 *   2. BEGIN TRANSACTION → handler → COMMIT → UPDATE log con response_status=200.
 *   3. Su exception → ROLLBACK (annulla side-effect su aied_users) → UPDATE log
 *      con response_status=500 e dettaglio errore.
 *
 * Eventi gestiti in M1.5:
 *   - subscription_created          → log + best-effort metadata refresh
 *   - subscription_updated          → tier + status + renews_at; reset last_payment_failed_at su recovery
 *   - subscription_cancelled        → status='cancelled' (renews_at preservato)
 *   - subscription_payment_failed   → status='past_due' + last_payment_failed_at
 *   - order_created                 → top-up: credita topup_balance_articles
 *   - license_key_created           → trace log
 *
 * Tier mapping:
 *   - variant_id sconosciuto: NON degrada il tier (lascia invariato). Logga
 *     "unknown_variant" come response_summary per audit. NO retry su LS.
 *
 * Limitazioni note M1.5:
 *   - Se il process crasha tra INSERT del claim e UPDATE finale, il log resta
 *     con response_status=0. Retry LS lo vedrà come duplicate e lo salterà.
 *     L'admin deve intervenire manualmente. Un retry-job verrà fatto in M6.
 */
class WebhookProcessor
{
    private const PROVIDER = 'lemonsqueezy_webhook';

    /**
     * @return array{
     *   status: 'processed'|'duplicate'|'unhandled'|'error',
     *   event_name: string,
     *   external_event_id: ?string,
     *   user_id: ?int,
     *   message?: string
     * }
     */
    public function process(array $payload, string $rawBody): array
    {
        $eventName = (string) ($payload['meta']['event_name'] ?? '');
        $externalEventId = $this->extractEventId($payload);

        if ($eventName === '') {
            // Niente claim possibile (non riconosciamo l'evento). Log diretto.
            $this->insertDirectLog(null, '?', $externalEventId, $rawBody, 422, 'Missing meta.event_name');
            return $this->result('error', '?', $externalEventId, null, 'Missing meta.event_name');
        }

        if ($externalEventId === null) {
            // LS reale invia sempre webhook_id. Senza event_id non possiamo
            // garantire idempotency: processiamo comunque ma logghiamo warning.
            Logger::channel('editorial')->warning('LS webhook without webhook_id', ['event' => $eventName]);
            return $this->processWithoutClaim($eventName, $payload, $rawBody);
        }

        // Claim atomico via INSERT IGNORE su UNIQUE (provider, external_event_id)
        $logId = $this->claimLog($externalEventId, $eventName, $rawBody);
        if ($logId === null) {
            // rowCount=0 → riga già esistente → duplicate
            return $this->result('duplicate', $eventName, $externalEventId, null, 'Event already processed (idempotent skip)');
        }

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            [$userId, $message, $isUnhandled] = $this->dispatch($eventName, $payload);
            $pdo->commit();

            // UPDATE log con esito (fuori transaction → persistito anche se segue altro errore)
            $this->finalizeLog($logId, $userId, 200, $message ?? ($isUnhandled ? 'Event not handled' : 'processed'));

            return $this->result(
                $isUnhandled ? 'unhandled' : 'processed',
                $eventName,
                $externalEventId,
                $userId,
                $message
            );
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = 'handler_exception: ' . $e->getMessage();
            Logger::channel('editorial')->error('Webhook handler exception', [
                'event' => $eventName,
                'event_id' => $externalEventId,
                'err' => $e->getMessage(),
            ]);
            $this->finalizeLog($logId, null, 500, $err);
            return $this->result('error', $eventName, $externalEventId, null, $err);
        }
    }

    /**
     * Path per eventi senza webhook_id (raro, fallback): nessun claim, nessuna
     * protezione idempotency. Comportamento M1.4-style.
     */
    private function processWithoutClaim(string $eventName, array $payload, string $rawBody): array
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            [$userId, $message, $isUnhandled] = $this->dispatch($eventName, $payload);
            $pdo->commit();
            $this->insertDirectLog($userId, $eventName, null, $rawBody, 200, $message ?? ($isUnhandled ? 'unhandled' : 'processed'));
            return $this->result($isUnhandled ? 'unhandled' : 'processed', $eventName, null, $userId, $message);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = 'handler_exception: ' . $e->getMessage();
            $this->insertDirectLog(null, $eventName, null, $rawBody, 500, $err);
            return $this->result('error', $eventName, null, null, $err);
        }
    }

    /**
     * Dispatch al handler dell'evento.
     *
     * @return array{0: ?int, 1: ?string, 2: bool}  [user_id, message, is_unhandled]
     */
    private function dispatch(string $eventName, array $payload): array
    {
        switch ($eventName) {
            case 'subscription_created':
                [$uid, $msg] = $this->handleSubscriptionCreated($payload);
                return [$uid, $msg, false];

            case 'subscription_updated':
                [$uid, $msg] = $this->handleSubscriptionUpdated($payload);
                return [$uid, $msg, false];

            case 'subscription_cancelled':
                $uid = $this->handleSubscriptionCancelled($payload);
                return [$uid, null, false];

            case 'subscription_payment_failed':
                $uid = $this->handleSubscriptionPaymentFailed($payload);
                return [$uid, null, false];

            case 'order_created':
                [$uid, $msg] = $this->handleOrderCreated($payload);
                return [$uid, $msg, false];

            case 'license_key_created':
                $uid = $this->handleLicenseKeyCreated($payload);
                return [$uid, null, false];

            default:
                return [null, 'Event not handled', true];
        }
    }

    // ============================================================
    // Event handlers
    // ============================================================

    /**
     * subscription_created: l'utente di solito non esiste ancora in DB
     * (verra' creato dal flusso /activate quando il plugin chiama backend).
     * Qui best-effort: se esiste, aggiorniamo i metadati LS.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function handleSubscriptionCreated(array $payload): array
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return [null, 'invalid_payload'];

        $userId = $this->findUser($attrs['subscription_id'], $attrs['customer_id'], $attrs['user_email']);
        if (!$userId) {
            return [null, 'user_not_yet_provisioned (expected: plugin will create on /activate)'];
        }

        $pdo = Database::getConnection();

        if ($attrs['tier'] === null) {
            // variant sconosciuto: aggiorna comunque metadati LS ma NON tocca tier
            $pdo->prepare(
                "UPDATE aied_users
                 SET lemon_squeezy_customer_id = COALESCE(NULLIF(?, ''), lemon_squeezy_customer_id),
                     lemon_squeezy_subscription_id = COALESCE(NULLIF(?, ''), lemon_squeezy_subscription_id),
                     subscription_status = 'active',
                     subscription_renews_at = ?
                 WHERE id = ?"
            )->execute([$attrs['customer_id'], $attrs['subscription_id'], $attrs['renews_at'], $userId]);
            return [$userId, $this->unknownVariantMessage($attrs['variant_id'])];
        }

        $pdo->prepare(
            "UPDATE aied_users
             SET lemon_squeezy_customer_id = COALESCE(NULLIF(?, ''), lemon_squeezy_customer_id),
                 lemon_squeezy_subscription_id = COALESCE(NULLIF(?, ''), lemon_squeezy_subscription_id),
                 tier = ?,
                 subscription_status = 'active',
                 subscription_renews_at = ?
             WHERE id = ?"
        )->execute([$attrs['customer_id'], $attrs['subscription_id'], $attrs['tier'], $attrs['renews_at'], $userId]);

        return [$userId, null];
    }

    /**
     * subscription_updated:
     *  - aggiorna status (mapping LS → DB enum)
     *  - aggiorna renews_at
     *  - aggiorna tier SOLO se variant_id riconosciuto (no downgrade silenzioso)
     *  - su recovery (status active|on_trial) → reset last_payment_failed_at
     */
    private function handleSubscriptionUpdated(array $payload): array
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return [null, 'invalid_payload'];

        $userId = $this->findUser($attrs['subscription_id'], $attrs['customer_id'], $attrs['user_email']);
        if (!$userId) {
            return [null, 'user_not_found'];
        }

        $dbStatus = $this->mapSubscriptionStatus($attrs['status']);
        $isRecovery = in_array($attrs['status'], ['active', 'on_trial'], true);

        $pdo = Database::getConnection();

        if ($attrs['tier'] === null) {
            // variant sconosciuto: aggiorna status/renews ma NON il tier (preserva valore corrente)
            $pdo->prepare(
                "UPDATE aied_users
                 SET subscription_status = ?,
                     subscription_renews_at = ?,
                     lemon_squeezy_customer_id = COALESCE(NULLIF(?, ''), lemon_squeezy_customer_id),
                     lemon_squeezy_subscription_id = COALESCE(NULLIF(?, ''), lemon_squeezy_subscription_id),
                     last_payment_failed_at = CASE WHEN ? = 1 THEN NULL ELSE last_payment_failed_at END
                 WHERE id = ?"
            )->execute([
                $dbStatus, $attrs['renews_at'],
                $attrs['customer_id'], $attrs['subscription_id'],
                $isRecovery ? 1 : 0,
                $userId,
            ]);
            return [$userId, $this->unknownVariantMessage($attrs['variant_id'])];
        }

        $pdo->prepare(
            "UPDATE aied_users
             SET tier = ?,
                 subscription_status = ?,
                 subscription_renews_at = ?,
                 lemon_squeezy_customer_id = COALESCE(NULLIF(?, ''), lemon_squeezy_customer_id),
                 lemon_squeezy_subscription_id = COALESCE(NULLIF(?, ''), lemon_squeezy_subscription_id),
                 last_payment_failed_at = CASE WHEN ? = 1 THEN NULL ELSE last_payment_failed_at END
             WHERE id = ?"
        )->execute([
            $attrs['tier'], $dbStatus, $attrs['renews_at'],
            $attrs['customer_id'], $attrs['subscription_id'],
            $isRecovery ? 1 : 0,
            $userId,
        ]);

        return [$userId, $isRecovery ? 'recovery_cleared_payment_failed' : null];
    }

    private function handleSubscriptionCancelled(array $payload): ?int
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return null;

        $userId = $this->findUser($attrs['subscription_id'], $attrs['customer_id'], $attrs['user_email']);
        if (!$userId) return null;

        $pdo = Database::getConnection();
        // renews_at preservato: utente operativo fino alla scadenza periodo pagato
        $pdo->prepare("UPDATE aied_users SET subscription_status = 'cancelled' WHERE id = ?")
            ->execute([$userId]);
        return $userId;
    }

    private function handleSubscriptionPaymentFailed(array $payload): ?int
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return null;

        $userId = $this->findUser($attrs['subscription_id'], $attrs['customer_id'], $attrs['user_email']);
        if (!$userId) return null;

        $pdo = Database::getConnection();
        $pdo->prepare(
            "UPDATE aied_users SET subscription_status = 'past_due', last_payment_failed_at = NOW() WHERE id = ?"
        )->execute([$userId]);
        return $userId;
    }

    /**
     * order_created — gestiamo solo top-up pack (variant in config 'topups').
     * Order per subscription iniziale: skip (subscription_created se ne occupa).
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function handleOrderCreated(array $payload): array
    {
        $data = $payload['data']['attributes'] ?? [];
        if (!is_array($data)) return [null, 'order_created without attributes'];

        $customerId = (string) ($data['customer_id'] ?? '');
        $userEmail = (string) ($data['user_email'] ?? '');
        $firstOrderItem = $data['first_order_item'] ?? [];
        $variantId = (string) ($firstOrderItem['variant_id'] ?? '');
        $variantName = (string) ($firstOrderItem['variant_name'] ?? '');

        $topups = $this->topupVariants();
        if (!isset($topups[$variantId])) {
            return [null, "not_topup: variant '{$variantName}' (id={$variantId})"];
        }
        $articlesToAdd = (int) $topups[$variantId];
        if ($articlesToAdd <= 0) {
            return [null, "topup_variant_has_non_positive_credit"];
        }

        $userId = $this->findUser('', $customerId, $userEmail);
        if (!$userId) {
            return [null, 'order_created: no aied_users matching customer/email'];
        }

        $pdo = Database::getConnection();
        $pdo->prepare(
            "UPDATE aied_users SET topup_balance_articles = topup_balance_articles + ? WHERE id = ?"
        )->execute([$articlesToAdd, $userId]);

        return [$userId, "credited_{$articlesToAdd}_articles"];
    }

    private function handleLicenseKeyCreated(array $payload): ?int
    {
        $licenseKey = (string) ($payload['data']['attributes']['key'] ?? '');
        if ($licenseKey === '') return null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM aied_users WHERE license_key = ? LIMIT 1");
        $stmt->execute([$licenseKey]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    // ============================================================
    // Idempotency & logging
    // ============================================================

    /**
     * Claim atomico: INSERT IGNORE su UNIQUE (provider, external_event_id).
     *
     * @return ?int  log row id se claim ottenuto; null se duplicate
     */
    private function claimLog(string $externalEventId, string $eventName, string $rawBody): ?int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO aied_api_logs
                (user_id, provider, external_event_id, endpoint, request_payload, response_status, response_summary, created_at)
             VALUES (NULL, ?, ?, ?, ?, 0, 'pending', NOW())"
        );
        $stmt->execute([
            self::PROVIDER,
            $externalEventId,
            $eventName,
            mb_substr($rawBody, 0, 60000),
        ]);

        if ($stmt->rowCount() === 0) {
            // Riga già presente: o altro processo l'ha già claimato, o l'ha completata
            return null;
        }
        return (int) $pdo->lastInsertId();
    }

    /**
     * Aggiorna il log row con esito finale (fuori transaction).
     */
    private function finalizeLog(int $logId, ?int $userId, int $httpStatus, string $summary): void
    {
        try {
            $pdo = Database::getConnection();
            $pdo->prepare(
                "UPDATE aied_api_logs
                 SET user_id = ?, response_status = ?, response_summary = ?
                 WHERE id = ?"
            )->execute([
                $userId,
                $httpStatus,
                mb_substr($summary, 0, 500),
                $logId,
            ]);
        } catch (\Throwable $e) {
            Logger::channel('editorial')->error('Webhook finalizeLog failed', ['err' => $e->getMessage(), 'log_id' => $logId]);
        }
    }

    /**
     * Log diretto (no claim): per eventi senza event_id o errori di parse.
     */
    private function insertDirectLog(?int $userId, string $eventName, ?string $externalEventId, string $rawBody, int $httpStatus, string $summary): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO aied_api_logs
                    (user_id, provider, external_event_id, endpoint, request_payload, response_status, response_summary, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                self::PROVIDER,
                $externalEventId,
                $eventName,
                mb_substr($rawBody, 0, 60000),
                $httpStatus,
                mb_substr($summary, 0, 500),
            ]);
        } catch (\Throwable $e) {
            Logger::channel('editorial')->error('Webhook insertDirectLog failed', ['err' => $e->getMessage()]);
        }
    }

    private function result(string $status, string $eventName, ?string $externalEventId, ?int $userId, ?string $message): array
    {
        return [
            'status' => $status,
            'event_name' => $eventName,
            'external_event_id' => $externalEventId,
            'user_id' => $userId,
            'message' => $message,
        ];
    }

    // ============================================================
    // Payload parsing helpers
    // ============================================================

    private function extractEventId(array $payload): ?string
    {
        $candidates = [
            $payload['meta']['webhook_id'] ?? null,
            $payload['meta']['event_id'] ?? null,
            $payload['meta']['custom_data']['event_id'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_string($c) && $c !== '') return $c;
        }
        return null;
    }

    /**
     * @return array{
     *   subscription_id: string,
     *   customer_id: string,
     *   user_email: string,
     *   variant_id: string,
     *   status: string,
     *   renews_at: ?string,
     *   tier: ?string
     * }|null
     */
    private function subscriptionAttrs(array $payload): ?array
    {
        $data = $payload['data'] ?? [];
        if (!is_array($data) || empty($data['attributes'])) return null;
        $a = $data['attributes'];

        $variantId = (string) ($a['variant_id'] ?? '');
        $tier = $this->tierFromVariant($variantId);  // null se sconosciuto

        $renewsRaw = $a['renews_at'] ?? null;
        $renewsAt = null;
        if (is_string($renewsRaw) && $renewsRaw !== '') {
            $ts = strtotime($renewsRaw);
            if ($ts !== false) $renewsAt = gmdate('Y-m-d H:i:s', $ts);
        }

        return [
            'subscription_id' => (string) ($data['id'] ?? ''),
            'customer_id' => (string) ($a['customer_id'] ?? ''),
            'user_email' => (string) ($a['user_email'] ?? ''),
            'variant_id' => $variantId,
            'status' => (string) ($a['status'] ?? 'active'),
            'renews_at' => $renewsAt,
            'tier' => $tier,
        ];
    }

    /**
     * LS values: 'on_trial', 'active', 'paused', 'past_due', 'unpaid', 'cancelled', 'expired'.
     */
    private function mapSubscriptionStatus(string $lsStatus): string
    {
        return match ($lsStatus) {
            'active', 'on_trial' => 'active',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
            'past_due', 'unpaid', 'paused' => 'past_due',
            default => 'active',
        };
    }

    /**
     * Ritorna il tier mappato dal variant_id, o NULL se sconosciuto.
     * NULL → handler salta UPDATE tier (preserva valore corrente, no downgrade).
     */
    private function tierFromVariant(string $variantId): ?string
    {
        $map = $this->subscriptionVariants();
        return $map[$variantId] ?? null;
    }

    private function unknownVariantMessage(string $variantId): string
    {
        return "unknown_variant_id={$variantId}_tier_preserved_check_config";
    }

    /** @return array<string,string> */
    private function subscriptionVariants(): array
    {
        return $this->loadConfig()['subscriptions'] ?? [];
    }

    /** @return array<string,int> */
    private function topupVariants(): array
    {
        return $this->loadConfig()['topups'] ?? [];
    }

    /** @return array{subscriptions: array<string,string>, topups: array<string,int>} */
    private function loadConfig(): array
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        if (!defined('ENV_LOADED')) {
            $envFile = dirname(__DIR__, 3) . '/config/environment.php';
            if (is_file($envFile)) {
                require_once $envFile;
            }
        }
        $path = dirname(__DIR__, 3) . '/config/editorial.php';
        if (!is_file($path)) {
            $cached = ['subscriptions' => [], 'topups' => []];
            return $cached;
        }
        $full = require $path;
        $cached = $full['lemonsqueezy'] ?? ['subscriptions' => [], 'topups' => []];
        return $cached;
    }

    /**
     * Cerca user: subscription_id → customer_id → email.
     */
    private function findUser(string $subId, string $customerId, string $email): ?int
    {
        $pdo = Database::getConnection();
        if ($subId !== '') {
            $stmt = $pdo->prepare("SELECT id FROM aied_users WHERE lemon_squeezy_subscription_id = ? LIMIT 1");
            $stmt->execute([$subId]);
            $id = $stmt->fetchColumn();
            if ($id) return (int) $id;
        }
        if ($customerId !== '') {
            $stmt = $pdo->prepare("SELECT id FROM aied_users WHERE lemon_squeezy_customer_id = ? LIMIT 1");
            $stmt->execute([$customerId]);
            $id = $stmt->fetchColumn();
            if ($id) return (int) $id;
        }
        if ($email !== '') {
            $stmt = $pdo->prepare("SELECT id FROM aied_users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $id = $stmt->fetchColumn();
            if ($id) return (int) $id;
        }
        return null;
    }
}
