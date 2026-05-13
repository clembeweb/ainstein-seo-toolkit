<?php

namespace Editorial\Services;

use Core\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use UnexpectedValueException;

/**
 * JwtService
 *
 * Emissione e verifica JWT per il plugin Ainstein Editorial.
 * Algoritmo: HS256 (simmetrico). Segreto da env EDITORIAL_JWT_SECRET.
 *
 * Payload standard:
 *   - sub  = "user:<uid>:site:<sid>" (string identifier)
 *   - uid  = aied_users.id
 *   - sid  = aied_sites.id
 *   - iat  = issued at
 *   - exp  = expires at
 *   - iss  = "ainstein-editorial"
 *
 * In dev locale, se EDITORIAL_JWT_SECRET non e' impostato, si usa un secret
 * fallback (con warning a log) per non bloccare lo sviluppo.
 */
class JwtService
{
    private const ALGO = 'HS256';
    private const ISSUER = 'ainstein-editorial';
    private const DEFAULT_TTL_SECONDS = 86400; // 24h
    private const DEV_FALLBACK_SECRET = 'dev-fallback-EDITORIAL_JWT_SECRET-NOT-SET-' . 'replace-me';

    private static ?string $cachedSecret = null;
    private static bool $devWarningLogged = false;

    /**
     * Emette un nuovo JWT.
     */
    public function issue(int $userId, int $siteId, int $expiresInSeconds = self::DEFAULT_TTL_SECONDS): string
    {
        $now = time();
        $payload = [
            'iss' => self::ISSUER,
            'sub' => "user:{$userId}:site:{$siteId}",
            'uid' => $userId,
            'sid' => $siteId,
            'iat' => $now,
            'exp' => $now + max(60, $expiresInSeconds),
        ];

        return JWT::encode($payload, self::secret(), self::ALGO);
    }

    /**
     * Verifica un JWT e ritorna il payload decodificato.
     *
     * @return array{uid:int, sid:int, iat:int, exp:int}
     * @throws ExpiredException        Token scaduto
     * @throws SignatureInvalidException Firma non valida
     * @throws UnexpectedValueException  Token malformato / payload incompleto
     */
    public function verify(string $token): array
    {
        if ($token === '') {
            throw new UnexpectedValueException('Empty token');
        }

        $decoded = JWT::decode($token, new Key(self::secret(), self::ALGO));
        $arr = (array) $decoded;

        if (!isset($arr['uid'], $arr['sid'], $arr['iat'], $arr['exp'])) {
            throw new UnexpectedValueException('Token payload missing required claims');
        }

        return [
            'uid' => (int) $arr['uid'],
            'sid' => (int) $arr['sid'],
            'iat' => (int) $arr['iat'],
            'exp' => (int) $arr['exp'],
        ];
    }

    /**
     * Verifica un token (anche se scaduto NON lo accetta, per sicurezza non
     * permette refresh di token gia' compromessi).
     * Per refresh affidabile usare il flusso /refresh-token con license_key + domain.
     */
    public function refresh(string $oldToken, int $expiresInSeconds = self::DEFAULT_TTL_SECONDS): string
    {
        $payload = $this->verify($oldToken);
        return $this->issue($payload['uid'], $payload['sid'], $expiresInSeconds);
    }

    /**
     * Risolve il segreto JWT da env (con fallback dev e warning).
     */
    private static function secret(): string
    {
        if (self::$cachedSecret !== null) {
            return self::$cachedSecret;
        }

        // Carica .env / .env.local on demand se non gia' fatto
        if (!defined('ENV_LOADED')) {
            $envFile = dirname(__DIR__, 3) . '/config/environment.php';
            if (is_file($envFile)) {
                require_once $envFile;
            }
        }

        $secret = (string) (getenv('EDITORIAL_JWT_SECRET') ?: ($_ENV['EDITORIAL_JWT_SECRET'] ?? ''));

        if ($secret === '') {
            if (!self::$devWarningLogged) {
                self::$devWarningLogged = true;
                @Logger::channel('editorial')->warning(
                    'EDITORIAL_JWT_SECRET not set — using DEV fallback. Never deploy without setting it.'
                );
            }
            $secret = self::DEV_FALLBACK_SECRET;
        }

        if (strlen($secret) < 16) {
            throw new \RuntimeException('EDITORIAL_JWT_SECRET too short (need >=16 chars)');
        }

        self::$cachedSecret = $secret;
        return self::$cachedSecret;
    }

    /**
     * Util per test: resetta cache (es. dopo cambio env).
     */
    public static function resetCache(): void
    {
        self::$cachedSecret = null;
        self::$devWarningLogged = false;
    }
}
