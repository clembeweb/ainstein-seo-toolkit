<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Lib;

/**
 * Wrapper HTTP per le API di Lemon Squeezy.
 *
 * Usa due famiglie di endpoint:
 *  - License API (validate/activate/deactivate): non richiede API key, accetta la license key.
 *  - Data API (subscriptions, ecc.): richiede Bearer API key + Accept vnd.api+json.
 *
 * Riferimento: design.md §11. Astrazione dietro questa classe per isolare eventuali
 * breaking change dell'API LS (vedi risk register M1).
 */
class LemonSqueezyClient
{
    private string $apiBase;
    private string $apiKey;
    private string $apiAccept;
    private string $webhookSecret;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/editorial.php';
        $ls = $config['lemonsqueezy'];
        $this->apiBase       = rtrim((string) $ls['api_base'], '/');
        $this->apiKey        = (string) $ls['api_key'];
        $this->apiAccept     = (string) ($ls['api_accept'] ?? 'application/vnd.api+json');
        $this->webhookSecret = (string) $ls['webhook_secret'];
    }

    /**
     * Valida una license key (opzionalmente legata a un instance_id).
     *
     * @return array{0:int,1:array<string,mixed>} [httpStatus, body]
     */
    public function validateLicense(string $licenseKey, ?string $instanceId = null): array
    {
        $body = ['license_key' => $licenseKey];
        if ($instanceId !== null) {
            $body['instance_id'] = $instanceId;
        }
        return $this->licenseRequest('/licenses/validate', $body);
    }

    /**
     * Attiva un'istanza per la license key. instance_name = dominio del sito.
     *
     * @return array{0:int,1:array<string,mixed>}
     */
    public function activateLicense(string $licenseKey, string $instanceName): array
    {
        return $this->licenseRequest('/licenses/activate', [
            'license_key'   => $licenseKey,
            'instance_name' => $instanceName,
        ]);
    }

    /**
     * Disattiva un'istanza, liberando uno slot.
     *
     * @return array{0:int,1:array<string,mixed>}
     */
    public function deactivateLicense(string $licenseKey, string $instanceId): array
    {
        return $this->licenseRequest('/licenses/deactivate', [
            'license_key' => $licenseKey,
            'instance_id' => $instanceId,
        ]);
    }

    /**
     * Recupera una subscription dal Data API (richiede API key).
     *
     * @return array{0:int,1:array<string,mixed>}
     */
    public function getSubscription(string $subscriptionId): array
    {
        return $this->dataRequest('GET', '/subscriptions/' . rawurlencode($subscriptionId));
    }

    /**
     * Verifica la firma HMAC-SHA256 di un payload webhook.
     */
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool
    {
        if ($this->webhookSecret === '' || $signatureHeader === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $this->webhookSecret);
        return hash_equals($expected, $signatureHeader);
    }

    // ----------------------------------------------------------------

    /**
     * @param array<string,mixed> $body
     * @return array{0:int,1:array<string,mixed>}
     */
    private function licenseRequest(string $path, array $body): array
    {
        return $this->request('POST', $path, [
            'Accept: application/json',
            'Content-Type: application/json',
        ], json_encode($body) ?: '{}');
    }

    /**
     * @return array{0:int,1:array<string,mixed>}
     */
    private function dataRequest(string $method, string $path): array
    {
        return $this->request($method, $path, [
            'Accept: ' . $this->apiAccept,
            'Content-Type: ' . $this->apiAccept,
            'Authorization: Bearer ' . $this->apiKey,
        ], null);
    }

    /**
     * @param list<string> $headers
     * @return array{0:int,1:array<string,mixed>}
     */
    private function request(string $method, string $path, array $headers, ?string $body): array
    {
        $ch = curl_init($this->apiBase . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [0, ['error' => 'lemonsqueezy_unreachable', 'detail' => $err]];
        }

        $decoded = json_decode((string) $raw, true);
        return [$status, is_array($decoded) ? $decoded : ['raw' => (string) $raw]];
    }
}
