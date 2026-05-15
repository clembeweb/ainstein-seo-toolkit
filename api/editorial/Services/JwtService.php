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
 * Algoritmo: HS256 (simmetrico).
 *
 * Secret rotation (M1.9.6):
 *   - EDITORIAL_JWT_SECRET       = secret corrente, usato per issue() (kid='current')
 *   - EDITORIAL_JWT_SECRET_PREV  = secret precedente, accettato solo da verify() (kid='previous')
 *
 * Durante rotation:
 *   1. Imposta EDITORIAL_JWT_SECRET_PREV = vecchio secret, EDITORIAL_JWT_SECRET = nuovo secret
 *   2. Tutti i nuovi JWT vengono emessi con kid='current' + nuovo secret
 *   3. JWT vecchi ancora validi: kid='previous' → verify() li accetta usando PREV
 *   4. Dopo TTL massimo (24h), rimuovi EDITORIAL_JWT_SECRET_PREV
 *
 * Payload standard:
 *   - sub  = "user:<uid>:site:<sid>" (string identifier)
 *   - uid  = aied_users.id
 *   - sid  = aied_sites.id
 *   - iat  = issued at
 *   - exp  = expires at
 *   - iss  = "ainstein-editorial"
 *   - kid  = "current" | "previous"  (header, per disambiguazione secret)
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
    private const KID_CURRENT = 'current';
    private const KID_PREVIOUS = 'previous';

    private static ?string $cachedSecret = null;
    private static ?string $cachedSecretPrev = null;
    private static bool $secretPrevResolved = false;
    private static bool $devWarningLogged = false;

    /**
     * Emette un nuovo JWT. Sempre firmato con il secret corrente (kid='current').
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

        return JWT::encode($payload, self::secret(), self::ALGO, self::KID_CURRENT);
    }

    /**
     * Verifica un JWT e ritorna il payload decodificato.
     *
     * Strategy: tenta secret corrente. Se SignatureInvalidException E c'e' un
     * secret PREV configurato (rotation in corso), ritenta col precedente.
     * Tutte le altre eccezioni (expired, malformed, missing claims) NON triggerano
     * il fallback — sono problemi del token, non del key set.
     *
     * @return array{uid:int, sid:int, iat:int, exp:int}
     * @throws ExpiredException        Token scaduto
     * @throws SignatureInvalidException Firma non valida con NESSUN secret disponibile
     * @throws UnexpectedValueException  Token malformato / payload incompleto
     */
    public function verify(string $token): array
    {
        if ($token === '') {
            throw new UnexpectedValueException('Empty token');
        }

        // Tentativo con secret CURRENT
        try {
            $decoded = JWT::decode($token, new Key(self::secret(), self::ALGO));
            return self::extractClaims($decoded);
        } catch (SignatureInvalidException $signatureError) {
            $prev = self::secretPrev();
            if ($prev === null) {
                throw $signatureError;
            }
            // Tentativo con secret PREVIOUS (rotation grace period)
            $decoded = JWT::decode($token, new Key($prev, self::ALGO));
            return self::extractClaims($decoded);
        }
    }

    /**
     * Estrae e valida i claim dal payload decodificato.
     *
     * @return array{uid:int, sid:int, iat:int, exp:int}
     * @throws UnexpectedValueException se payload incompleto
     */
    private static function extractClaims(object $decoded): array
    {
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
     * Risolve il secret di rotation (PREV) da env. Ritorna null se non configurato.
     * Cached per request.
     */
    private static function secretPrev(): ?string
    {
        if (self::$secretPrevResolved) {
            return self::$cachedSecretPrev;
        }
        self::$secretPrevResolved = true;

        if (!defined('ENV_LOADED')) {
            $envFile = dirname(__DIR__, 3) . '/config/environment.php';
            if (is_file($envFile)) {
                require_once $envFile;
            }
        }

        $prev = (string) (getenv('EDITORIAL_JWT_SECRET_PREV') ?: ($_ENV['EDITORIAL_JWT_SECRET_PREV'] ?? ''));
        if ($prev === '' || strlen($prev) < 16) {
            // Troppo corto o assente → rotation non attiva
            self::$cachedSecretPrev = null;
            return null;
        }
        self::$cachedSecretPrev = $prev;
        return self::$cachedSecretPrev;
    }

    /**
     * Util per test: resetta cache (es. dopo cambio env).
     */
    public static function resetCache(): void
    {
        self::$cachedSecret = null;
        self::$cachedSecretPrev = null;
        self::$secretPrevResolved = false;
        self::$devWarningLogged = false;
    }
}
