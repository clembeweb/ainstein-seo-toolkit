<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Services;

/**
 * JWT HS256 minimale (nessuna dipendenza esterna) per gli api_token short-lived.
 * Payload tipico: { user_id, site_id, iss, iat, exp }.
 */
class JwtService
{
    private string $secret;
    private int $ttl;
    private string $issuer;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/editorial.php';
        $this->secret = (string) ($config['jwt']['secret'] ?? '');
        $this->ttl    = (int) ($config['jwt']['ttl'] ?? 86400);
        $this->issuer = (string) ($config['jwt']['issuer'] ?? 'ainstein-editorial');
    }

    /**
     * Emette un token firmato.
     *
     * @param array<string,mixed> $claims
     * @return array{token:string,expires_at:int,expires_in:int}
     */
    public function issue(array $claims): array
    {
        $now = time();
        $exp = $now + $this->ttl;
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $exp,
        ]);

        $header  = $this->b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']) ?: '');
        $body    = $this->b64(json_encode($payload) ?: '');
        $sig     = $this->b64($this->sign($header . '.' . $body));

        return [
            'token'      => $header . '.' . $body . '.' . $sig,
            'expires_at' => $exp,
            'expires_in' => $this->ttl,
        ];
    }

    /**
     * Verifica firma + scadenza. Ritorna il payload o null se invalido/scaduto.
     *
     * @return array<string,mixed>|null
     */
    public function verify(string $token): ?array
    {
        if ($this->secret === '') {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $body, $sig] = $parts;

        $expected = $this->b64($this->sign($header . '.' . $body));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $payload = json_decode($this->b64decode($body), true);
        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }
        return $payload;
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, true);
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
