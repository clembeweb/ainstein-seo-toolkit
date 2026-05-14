<?php

namespace Editorial\Services;

/**
 * Verifica firma HMAC SHA256 dei webhook LemonSqueezy.
 *
 * LS firma il body raw con secret condiviso e invia hex digest in header X-Signature.
 * Confronto timing-safe via hash_equals.
 *
 * Doc: https://docs.lemonsqueezy.com/help/webhooks/signing-requests
 */
class WebhookVerifier
{
    private string $secret;

    public function __construct(?string $secret = null)
    {
        if ($secret !== null) {
            $this->secret = $secret;
            return;
        }
        // Lazy-load .env / .env.local se non gia' caricato (pattern come JwtService)
        if (!defined('ENV_LOADED')) {
            $envFile = dirname(__DIR__, 3) . '/config/environment.php';
            if (is_file($envFile)) {
                require_once $envFile;
            }
        }
        $this->secret = (string) (getenv('LEMONSQUEEZY_WEBHOOK_SECRET')
            ?: ($_ENV['LEMONSQUEEZY_WEBHOOK_SECRET'] ?? ''));
    }

    /**
     * @return bool true se la firma è valida.
     */
    public function verify(string $rawBody, string $providedSignature): bool
    {
        if ($this->secret === '' || $providedSignature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->secret);
        return hash_equals($expected, $providedSignature);
    }

    public function hasSecret(): bool
    {
        return $this->secret !== '';
    }

    /**
     * Helper per i test: genera la firma valida per un body.
     * NON usare in produzione: solo per fixture/test runner.
     */
    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $this->secret);
    }
}
