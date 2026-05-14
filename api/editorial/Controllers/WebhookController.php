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
            // Bug operativo: il secret non e' configurato sul server.
            // Ritorniamo 200 OK (no LS retry loop esponenziale) ma logghiamo
            // l'errore per monitoring + traccia in aied_api_logs.
            Logger::channel('editorial')->critical('LS webhook secret not configured (LEMONSQUEEZY_WEBHOOK_SECRET)', [
                'body_len' => strlen($rawBody),
            ]);
            $this->logSecretMissing($rawBody);
            return $this->jsonOk([
                'received' => true,
                'status' => 'config_error',
                'message' => 'Webhook secret not configured on server (logged for admin review)',
            ], 200);
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
     * Logga in aied_api_logs il fatto che il webhook è arrivato ma il secret non era configurato.
     * NB: non possiamo usare external_event_id dal payload perche' non abbiamo verificato la firma:
     * un attaccante potrebbe inviare event_id collidenti per inquinare il log. Usiamo NULL.
     */
    private function logSecretMissing(string $rawBody): void
    {
        try {
            $pdo = \Core\Database::getConnection();
            $pdo->prepare(
                "INSERT INTO aied_api_logs
                    (provider, external_event_id, endpoint, request_payload, response_status, response_summary, created_at)
                 VALUES ('lemonsqueezy_webhook', NULL, 'config_error', ?, 0, 'WEBHOOK_SECRET_MISSING', NOW())"
            )->execute([mb_substr($rawBody, 0, 2000)]);
        } catch (\Throwable $e) {
            // best-effort: il log e' importante ma non blocchiamo la risposta
        }
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
