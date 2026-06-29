<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Controllers;

use Ainstein\Editorial\Http\Request;
use Ainstein\Editorial\Lib\LemonSqueezyClient;
use Core\Database;

/**
 * Handler webhook Lemon Squeezy. NON protetto da LicenseAuthMiddleware
 * (autenticato via firma HMAC). Idempotente via aied_api_logs.external_event_id.
 *
 * Eventi gestiti (M1.5):
 *  - subscription_created, subscription_updated, subscription_cancelled
 *  - subscription_payment_failed
 *  - order_created (top-up pack)
 *  - license_key_created
 */
class WebhookController extends BaseController
{
    public function lemonsqueezy(Request $request): never
    {
        $signature = $request->header('x-signature', '');
        $client = new LemonSqueezyClient();

        if (!$client->verifyWebhookSignature($request->rawBody, (string) $signature)) {
            $this->ok(['error' => 'Firma webhook non valida.'], 401);
        }

        $payload   = $request->body;
        $eventName = $payload['meta']['event_name'] ?? '';
        $data      = $payload['data'] ?? [];
        $dataId    = (string) ($data['id'] ?? '');
        $attrs     = $data['attributes'] ?? [];
        $eventId   = $eventName . ':' . $dataId;

        // Idempotency: se già processato, ack senza ri-eseguire
        $seen = Database::fetchColumn(
            'SELECT COUNT(*) FROM aied_api_logs WHERE provider = "lemonsqueezy" AND external_event_id = ?',
            [$eventId]
        );
        if ((int) $seen > 0) {
            $this->ok(['success' => true, 'idempotent' => true]);
        }

        switch ($eventName) {
            case 'subscription_created':
                // Account reale creato via /activate dal plugin; qui solo trace.
                break;

            case 'subscription_updated':
                $this->updateSubscription($dataId, $attrs);
                break;

            case 'subscription_cancelled':
                $this->setSubscriptionStatus($dataId, 'cancelled', $attrs);
                break;

            case 'subscription_payment_failed':
                $this->setSubscriptionStatus($dataId, 'past_due', $attrs);
                break;

            case 'order_created':
                $this->handleTopup($attrs);
                break;

            case 'license_key_created':
                // trace per debugging
                break;

            default:
                // evento non gestito: logghiamo comunque per audit
                break;
        }

        $this->logWebhook($eventId, $eventName, $payload);
        $this->ok(['success' => true]);
    }

    /** @param array<string,mixed> $attrs */
    private function updateSubscription(string $subscriptionId, array $attrs): void
    {
        $renewsAt = null;
        if (!empty($attrs['renews_at'])) {
            $ts = strtotime((string) $attrs['renews_at']);
            if ($ts !== false) {
                $renewsAt = date('Y-m-d H:i:s', $ts);
            }
        }
        $update = [
            'subscription_status'    => $this->mapStatus($attrs['status'] ?? 'active'),
            'subscription_renews_at' => $renewsAt,
        ];
        Database::update('aied_users', $update, 'lemon_squeezy_subscription_id = ?', [$subscriptionId]);
    }

    /** @param array<string,mixed> $attrs */
    private function setSubscriptionStatus(string $subscriptionId, string $status, array $attrs): void
    {
        Database::update('aied_users', ['subscription_status' => $status], 'lemon_squeezy_subscription_id = ?', [$subscriptionId]);
    }

    /** @param array<string,mixed> $attrs */
    private function handleTopup(array $attrs): void
    {
        // Mapping variant → articoli: configurabile in futuro. M1: trace + best effort
        // su custom_data se il checkout LS la include.
        $customerId = (string) ($attrs['customer_id'] ?? '');
        if ($customerId === '') {
            return;
        }
        // L'accredito reale del balance verrà usato in M3; qui registriamo l'intento.
    }

    private function mapStatus(string $lsStatus): string
    {
        return match ($lsStatus) {
            'active', 'on_trial' => 'active',
            'past_due', 'unpaid' => 'past_due',
            'cancelled', 'expired' => 'cancelled',
            default => 'active',
        };
    }

    /** @param array<string,mixed> $payload */
    private function logWebhook(string $eventId, string $eventName, array $payload): void
    {
        try {
            Database::insert('aied_api_logs', [
                'provider'          => 'lemonsqueezy',
                'endpoint'          => 'webhook:' . $eventName,
                'external_event_id' => $eventId,
                'response_status'   => 200,
                'response_summary'  => substr(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '', 0, 2000),
            ]);
        } catch (\Throwable) {
            // best effort
        }
    }
}
