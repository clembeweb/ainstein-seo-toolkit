<?php

namespace Editorial\Services;

use Core\Database;
use Core\Logger;
use PDO;
use RuntimeException;

/**
 * WebhookProcessor — orchestratore eventi LemonSqueezy.
 *
 * Riceve payload già verificato (firma HMAC validata a monte) e instrada
 * sul handler dell'evento. Tutti i log in aied_api_logs con external_event_id
 * per garantire idempotency (LS retries idempotenti).
 *
 * Eventi gestiti in M1.5:
 *   - subscription_created       → trace log
 *   - subscription_updated       → aggiorna tier + status + renews_at su aied_users
 *   - subscription_cancelled     → set subscription_status='cancelled' (active fino a renews_at)
 *   - subscription_payment_failed → set subscription_status='past_due' + last_payment_failed_at
 *   - order_created              → se top-up pack: credita topup_balance_articles
 *   - license_key_created        → trace log
 *
 * Altri eventi: log + skip ("event not handled").
 */
class WebhookProcessor
{
    /**
     * Esito processamento per il controller.
     *
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
            return $this->logAndReturn(null, '?', null, 'error', 'Missing meta.event_name', $rawBody, 422);
        }

        // Idempotency: se già loggato con successo, return duplicate (200 OK)
        if ($externalEventId !== null && $this->alreadyProcessed($externalEventId)) {
            return [
                'status' => 'duplicate',
                'event_name' => $eventName,
                'external_event_id' => $externalEventId,
                'user_id' => null,
                'message' => 'Event already processed (idempotent skip)',
            ];
        }

        try {
            switch ($eventName) {
                case 'subscription_created':
                    $userId = $this->handleSubscriptionCreated($payload);
                    return $this->logAndReturn($userId, $eventName, $externalEventId, 'processed', null, $rawBody, 200);

                case 'subscription_updated':
                    $userId = $this->handleSubscriptionUpdated($payload);
                    return $this->logAndReturn($userId, $eventName, $externalEventId, 'processed', null, $rawBody, 200);

                case 'subscription_cancelled':
                    $userId = $this->handleSubscriptionCancelled($payload);
                    return $this->logAndReturn($userId, $eventName, $externalEventId, 'processed', null, $rawBody, 200);

                case 'subscription_payment_failed':
                    $userId = $this->handleSubscriptionPaymentFailed($payload);
                    return $this->logAndReturn($userId, $eventName, $externalEventId, 'processed', null, $rawBody, 200);

                case 'order_created':
                    [$userId, $msg] = $this->handleOrderCreated($payload);
                    return $this->logAndReturn($userId, $eventName, $externalEventId, 'processed', $msg, $rawBody, 200);

                case 'license_key_created':
                    $userId = $this->handleLicenseKeyCreated($payload);
                    return $this->logAndReturn($userId, $eventName, $externalEventId, 'processed', null, $rawBody, 200);

                default:
                    return $this->logAndReturn(null, $eventName, $externalEventId, 'unhandled', 'Event not handled', $rawBody, 200);
            }
        } catch (\Throwable $e) {
            Logger::channel('editorial')->error('Webhook handler exception', [
                'event' => $eventName,
                'event_id' => $externalEventId,
                'err' => $e->getMessage(),
            ]);
            return $this->logAndReturn(null, $eventName, $externalEventId, 'error', $e->getMessage(), $rawBody, 500);
        }
    }

    // -------------------- Event handlers --------------------

    /**
     * subscription_created: trace log. L'account utente viene creato dal flusso /activate
     * (plugin → backend) usando la license_key; qui solo traccia per audit.
     * Se l'utente esiste già (per license_key) → aggiorna i metadati LS.
     */
    private function handleSubscriptionCreated(array $payload): ?int
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return null;

        $userId = $this->findUserBySubscriptionOrCustomer(
            (string) ($attrs['subscription_id'] ?? ''),
            (string) ($attrs['customer_id'] ?? ''),
            (string) ($attrs['user_email'] ?? '')
        );
        if (!$userId) {
            return null;
        }

        $pdo = Database::getConnection();
        $pdo->prepare(
            "UPDATE aied_users
             SET lemon_squeezy_customer_id = COALESCE(NULLIF(?, ''), lemon_squeezy_customer_id),
                 lemon_squeezy_subscription_id = COALESCE(NULLIF(?, ''), lemon_squeezy_subscription_id),
                 tier = ?,
                 subscription_status = 'active',
                 subscription_renews_at = ?
             WHERE id = ?"
        )->execute([
            (string) ($attrs['customer_id'] ?? ''),
            (string) ($attrs['subscription_id'] ?? ''),
            $attrs['tier'] ?? 'starter',
            $attrs['renews_at'] ?? null,
            $userId,
        ]);
        return $userId;
    }

    private function handleSubscriptionUpdated(array $payload): ?int
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return null;

        $userId = $this->findUserBySubscriptionOrCustomer(
            (string) ($attrs['subscription_id'] ?? ''),
            (string) ($attrs['customer_id'] ?? ''),
            (string) ($attrs['user_email'] ?? '')
        );
        if (!$userId) {
            return null;
        }

        // Mapping status LS → DB enum
        $dbStatus = $this->mapSubscriptionStatus((string) ($attrs['status'] ?? 'active'));

        $pdo = Database::getConnection();
        $pdo->prepare(
            "UPDATE aied_users
             SET tier = ?,
                 subscription_status = ?,
                 subscription_renews_at = ?,
                 lemon_squeezy_customer_id = COALESCE(NULLIF(?, ''), lemon_squeezy_customer_id),
                 lemon_squeezy_subscription_id = COALESCE(NULLIF(?, ''), lemon_squeezy_subscription_id)
             WHERE id = ?"
        )->execute([
            $attrs['tier'] ?? 'starter',
            $dbStatus,
            $attrs['renews_at'] ?? null,
            (string) ($attrs['customer_id'] ?? ''),
            (string) ($attrs['subscription_id'] ?? ''),
            $userId,
        ]);
        return $userId;
    }

    private function handleSubscriptionCancelled(array $payload): ?int
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return null;

        $userId = $this->findUserBySubscriptionOrCustomer(
            (string) ($attrs['subscription_id'] ?? ''),
            (string) ($attrs['customer_id'] ?? ''),
            (string) ($attrs['user_email'] ?? '')
        );
        if (!$userId) {
            return null;
        }

        $pdo = Database::getConnection();
        // NB: lasciamo subscription_renews_at intatto: l'utente resta operativo fino a quella data.
        $pdo->prepare(
            "UPDATE aied_users SET subscription_status = 'cancelled' WHERE id = ?"
        )->execute([$userId]);
        return $userId;
    }

    private function handleSubscriptionPaymentFailed(array $payload): ?int
    {
        $attrs = $this->subscriptionAttrs($payload);
        if (!$attrs) return null;

        $userId = $this->findUserBySubscriptionOrCustomer(
            (string) ($attrs['subscription_id'] ?? ''),
            (string) ($attrs['customer_id'] ?? ''),
            (string) ($attrs['user_email'] ?? '')
        );
        if (!$userId) {
            return null;
        }

        $pdo = Database::getConnection();
        $pdo->prepare(
            "UPDATE aied_users
             SET subscription_status = 'past_due',
                 last_payment_failed_at = NOW()
             WHERE id = ?"
        )->execute([$userId]);
        return $userId;
    }

    /**
     * order_created: se la variant corrisponde a un top-up pack configurato,
     * credita gli articoli sul saldo dell'utente. Altrimenti trace log.
     *
     * @return array{0: ?int, 1: ?string}  [user_id, message]
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
            return [null, "order_created: variant '{$variantName}' (id={$variantId}) is not a top-up pack"];
        }
        $articlesToAdd = (int) $topups[$variantId];
        if ($articlesToAdd <= 0) {
            return [null, "order_created: top-up variant has non-positive credit amount"];
        }

        $userId = $this->findUserBySubscriptionOrCustomer('', $customerId, $userEmail);
        if (!$userId) {
            return [null, "order_created: no aied_users matching customer/email"];
        }

        $pdo = Database::getConnection();
        $pdo->prepare(
            "UPDATE aied_users
             SET topup_balance_articles = topup_balance_articles + ?
             WHERE id = ?"
        )->execute([$articlesToAdd, $userId]);

        return [$userId, "Credited {$articlesToAdd} articles to user {$userId} (variant {$variantId})"];
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

    // -------------------- Helpers --------------------

    /**
     * Estrae l'event_id univoco LS dal payload.
     * LS lo invia in `meta.webhook_id` (UUID).
     */
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

    private function alreadyProcessed(string $externalEventId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT id FROM aied_api_logs
             WHERE provider = 'lemonsqueezy_webhook' AND external_event_id = ? AND response_status BETWEEN 200 AND 299
             LIMIT 1"
        );
        $stmt->execute([$externalEventId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Normalizza attributi subscription_* dal payload LS.
     *
     * @return array{
     *   subscription_id: string,
     *   customer_id: string,
     *   user_email: string,
     *   variant_id: string,
     *   status: string,
     *   renews_at: ?string,
     *   tier: string
     * }|null
     */
    private function subscriptionAttrs(array $payload): ?array
    {
        $data = $payload['data'] ?? [];
        if (!is_array($data) || empty($data['attributes'])) return null;
        $a = $data['attributes'];

        $variantId = (string) ($a['variant_id'] ?? '');
        $tier = $this->tierFromVariant($variantId);

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
     * Mappa lo status LS sull'enum aied_users.subscription_status.
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

    private function tierFromVariant(string $variantId): string
    {
        $map = $this->subscriptionVariants();
        return $map[$variantId] ?? 'starter';
    }

    /** @return array<string,string> variant_id → tier */
    private function subscriptionVariants(): array
    {
        $config = $this->loadConfig();
        return $config['subscriptions'] ?? [];
    }

    /** @return array<string,int> variant_id → articoli */
    private function topupVariants(): array
    {
        $config = $this->loadConfig();
        return $config['topups'] ?? [];
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
     * Cerca user su DB con cascata: subscription_id → customer_id → email.
     */
    private function findUserBySubscriptionOrCustomer(string $subId, string $customerId, string $email): ?int
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

    /**
     * Inserisce/aggiorna riga in aied_api_logs e ritorna l'esito normalizzato per il controller.
     *
     * Idempotency: UNIQUE (provider, external_event_id) → in caso di race (LS retry concomitante)
     * gestiamo INSERT IGNORE e ritorniamo comunque 200.
     */
    private function logAndReturn(
        ?int $userId,
        string $eventName,
        ?string $externalEventId,
        string $status,
        ?string $message,
        string $rawBody,
        int $httpStatus
    ): array {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO aied_api_logs
                    (user_id, provider, external_event_id, endpoint, request_payload, response_status, response_summary, created_at)
                 VALUES (?, 'lemonsqueezy_webhook', ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $externalEventId,
                $eventName,
                mb_substr($rawBody, 0, 60000),
                $httpStatus,
                $message !== null ? mb_substr($message, 0, 500) : $status,
            ]);
        } catch (\Throwable $e) {
            Logger::channel('editorial')->error('Webhook log insert failed', ['err' => $e->getMessage()]);
        }

        return [
            'status' => $status,
            'event_name' => $eventName,
            'external_event_id' => $externalEventId,
            'user_id' => $userId,
            'message' => $message,
        ];
    }
}
