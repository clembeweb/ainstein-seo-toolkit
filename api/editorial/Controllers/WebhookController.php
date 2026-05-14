<?php

namespace Editorial\Controllers;

use Core\Logger;
use Editorial\Services\WebhookProcessor;
use Editorial\Services\WebhookVerifier;

/**
 * WebhookController
 *
 * Endpoint pubblici per webhooks da provider esterni (LemonSqueezy billing).
 * Non passa da LicenseAuthMiddleware: la verifica firma HMAC SHA256 fa da auth.
 *
 * Pattern:
 *   1. Legge raw body (NON jsonInput: serve la stringa esatta per HMAC)
 *   2. Verifica X-Signature contro hash_hmac('sha256', body, secret)
 *   3. Delega a WebhookProcessor che applica idempotency + handler evento
 *   4. Risponde sempre 2xx se l'evento è stato registrato (anche se "unhandled"),
 *      cosicché LS non lo metta in retry loop. Solo 4xx/5xx su errori reali
 *      (firma invalida, secret mancante, eccezione handler).
 */
class WebhookController extends BaseController
{
    /** POST /api/editorial/v1/webhooks/lemonsqueezy */
    public function lemonsqueezy(): string
    {
        $rawBody = (string) file_get_contents('php://input');
        $signature = $this->headerValue('X-Signature');

        $verifier = new WebhookVerifier();

        if (!$verifier->hasSecret()) {
            Logger::channel('editorial')->error('LS webhook secret not configured (LEMONSQUEEZY_WEBHOOK_SECRET)');
            return $this->jsonError('Webhook secret not configured', 500, ['code' => 'WEBHOOK_SECRET_MISSING']);
        }

        if (!$verifier->verify($rawBody, $signature)) {
            Logger::channel('editorial')->warning('LS webhook signature invalid', [
                'sig_provided_prefix' => substr($signature, 0, 8),
                'body_len' => strlen($rawBody),
            ]);
            return $this->jsonError('Invalid signature', 401, ['code' => 'INVALID_SIGNATURE']);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->jsonError('Invalid JSON payload', 400, ['code' => 'INVALID_JSON']);
        }

        $result = (new WebhookProcessor())->process($payload, $rawBody);

        $httpStatus = match ($result['status']) {
            'error' => 500,
            default => 200,
        };

        return $this->jsonOk([
            'received' => true,
            'event' => $result['event_name'],
            'event_id' => $result['external_event_id'],
            'status' => $result['status'],
            'user_id' => $result['user_id'],
            'message' => $result['message'] ?? null,
        ], $httpStatus);
    }

    /**
     * Estrae header HTTP normalizzando varianti (X-Signature vs HTTP_X_SIGNATURE).
     */
    private function headerValue(string $name): string
    {
        $normalized = strtoupper(str_replace('-', '_', $name));

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $hKey => $hVal) {
                if (strcasecmp($hKey, $name) === 0) {
                    return (string) $hVal;
                }
            }
        }

        $serverKey = 'HTTP_' . $normalized;
        return (string) ($_SERVER[$serverKey] ?? '');
    }
}
