<?php

declare(strict_types=1);

namespace Editorial\Tests\Unit\Services;

use Editorial\Services\JwtService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use RuntimeException;

final class JwtServiceTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        // bootstrap.php già imposta EDITORIAL_JWT_SECRET via phpunit.xml <php><env>.
        // Forziamo il reset della cache per essere certi del secret usato.
        JwtService::resetCache();
        $this->jwt = new JwtService();
    }

    public function testIssueAndVerifyRoundtrip(): void
    {
        $token = $this->jwt->issue(42, 7, 3600);
        $payload = $this->jwt->verify($token);

        $this->assertSame(42, $payload['uid']);
        $this->assertSame(7, $payload['sid']);
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertLessThanOrEqual(time(), $payload['iat']);
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        // Issue con TTL minimo (60s, clamp interno).
        $token = $this->jwt->issue(1, 1, 60);

        // Decodifichiamo e ri-encodiamo manualmente con exp nel passato per simulare.
        // Più semplice: lib firebase/jwt accetta solo exp futuri in issue(),
        // quindi usiamo Reflection sul service per forzare un token scaduto.
        $reflection = new \ReflectionClass(JwtService::class);
        $secretMethod = $reflection->getMethod('secret');
        $secretMethod->setAccessible(true);
        $secret = $secretMethod->invoke(null);

        $expired = \Firebase\JWT\JWT::encode([
            'iss' => 'ainstein-editorial',
            'sub' => 'user:1:site:1',
            'uid' => 1,
            'sid' => 1,
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ], $secret, 'HS256');

        $this->expectException(ExpiredException::class);
        $this->jwt->verify($expired);
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $token = $this->jwt->issue(1, 1);
        // Cambiamo l'ultimo carattere della firma
        $tampered = substr($token, 0, -1) . (str_ends_with($token, 'A') ? 'B' : 'A');

        $this->expectException(SignatureInvalidException::class);
        $this->jwt->verify($tampered);
    }

    public function testVerifyRejectsMalformedToken(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->jwt->verify('not.a.valid.jwt');
    }

    public function testVerifyRejectsEmptyToken(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->jwt->verify('');
    }

    public function testIssueClampsMinimumTtlTo60Seconds(): void
    {
        $token = $this->jwt->issue(1, 1, 1); // valore minuscolo
        $payload = $this->jwt->verify($token);

        $this->assertGreaterThanOrEqual(time() + 59, $payload['exp']);
    }

    public function testIssueProducesDifferentTokensForDifferentSites(): void
    {
        $t1 = $this->jwt->issue(1, 1);
        // PHP sleep 1s per garantire diverso `iat`
        sleep(1);
        $t2 = $this->jwt->issue(1, 2);

        $this->assertNotSame($t1, $t2);
    }

    public function testRefreshIssuesNewTokenWithSameClaims(): void
    {
        $old = $this->jwt->issue(99, 5, 3600);
        sleep(1);
        $new = $this->jwt->refresh($old, 3600);

        $this->assertNotSame($old, $new);

        $p1 = $this->jwt->verify($old);
        $p2 = $this->jwt->verify($new);
        $this->assertSame($p1['uid'], $p2['uid']);
        $this->assertSame($p1['sid'], $p2['sid']);
        $this->assertGreaterThan($p1['iat'], $p2['iat']);
    }

    public function testRefreshRejectsExpiredToken(): void
    {
        $reflection = new \ReflectionClass(JwtService::class);
        $secretMethod = $reflection->getMethod('secret');
        $secretMethod->setAccessible(true);
        $secret = $secretMethod->invoke(null);

        $expired = \Firebase\JWT\JWT::encode([
            'iss' => 'ainstein-editorial',
            'sub' => 'user:1:site:1',
            'uid' => 1,
            'sid' => 1,
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ], $secret, 'HS256');

        $this->expectException(ExpiredException::class);
        $this->jwt->refresh($expired);
    }

    public function testSecretShorterThan16CharsThrows(): void
    {
        JwtService::resetCache();
        $original = getenv('EDITORIAL_JWT_SECRET');
        putenv('EDITORIAL_JWT_SECRET=short');
        $_ENV['EDITORIAL_JWT_SECRET'] = 'short';

        try {
            $this->expectException(RuntimeException::class);
            (new JwtService())->issue(1, 1);
        } finally {
            // Restore env per non sporcare gli altri test
            putenv('EDITORIAL_JWT_SECRET=' . ($original === false ? '' : $original));
            $_ENV['EDITORIAL_JWT_SECRET'] = $original === false ? null : $original;
            JwtService::resetCache();
        }
    }

    public function testTokenPayloadIncludesRequiredClaims(): void
    {
        $token = $this->jwt->issue(123, 456);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT must have 3 segments (header.payload.signature)');

        $payload = json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );

        $this->assertSame('ainstein-editorial', $payload['iss']);
        $this->assertSame('user:123:site:456', $payload['sub']);
        $this->assertSame(123, $payload['uid']);
        $this->assertSame(456, $payload['sid']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testTokenHeaderIncludesKid(): void
    {
        $token = $this->jwt->issue(1, 1);
        $parts = explode('.', $token);
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame('current', $header['kid'], 'New tokens must be signed with kid=current');
    }

    public function testVerifyAcceptsTokenSignedWithPreviousSecretDuringRotation(): void
    {
        // Setup: emette un token col secret OLD, poi imposta env per simulare rotation
        $oldSecret = 'old-secret-rotation-test-1234567890';
        $newSecret = 'new-secret-rotation-test-abcdefghij';

        // Token emesso con il vecchio secret
        $oldToken = \Firebase\JWT\JWT::encode([
            'iss' => 'ainstein-editorial',
            'sub' => 'user:1:site:1',
            'uid' => 1,
            'sid' => 1,
            'iat' => time(),
            'exp' => time() + 3600,
        ], $oldSecret, 'HS256', 'previous');

        // Simula rotation: CURRENT = new, PREV = old
        $originalCurrent = getenv('EDITORIAL_JWT_SECRET');
        $originalPrev = getenv('EDITORIAL_JWT_SECRET_PREV');

        putenv('EDITORIAL_JWT_SECRET=' . $newSecret);
        $_ENV['EDITORIAL_JWT_SECRET'] = $newSecret;
        putenv('EDITORIAL_JWT_SECRET_PREV=' . $oldSecret);
        $_ENV['EDITORIAL_JWT_SECRET_PREV'] = $oldSecret;
        JwtService::resetCache();

        try {
            $payload = (new JwtService())->verify($oldToken);
            $this->assertSame(1, $payload['uid'], 'Token con kid=previous deve essere accettato durante rotation');
        } finally {
            // Restore
            putenv('EDITORIAL_JWT_SECRET=' . ($originalCurrent === false ? '' : $originalCurrent));
            $_ENV['EDITORIAL_JWT_SECRET'] = $originalCurrent === false ? null : $originalCurrent;
            putenv('EDITORIAL_JWT_SECRET_PREV=' . ($originalPrev === false ? '' : $originalPrev));
            $_ENV['EDITORIAL_JWT_SECRET_PREV'] = $originalPrev === false ? null : $originalPrev;
            JwtService::resetCache();
        }
    }

    public function testVerifyRejectsTokenWhenNoSecretMatches(): void
    {
        // Token firmato con secret completamente diverso, NESSUN PREV configurato
        $strangerToken = \Firebase\JWT\JWT::encode([
            'iss' => 'ainstein-editorial',
            'sub' => 'user:1:site:1',
            'uid' => 1,
            'sid' => 1,
            'iat' => time(),
            'exp' => time() + 3600,
        ], 'completely-different-secret-9999999', 'HS256');

        // Assicuriamoci che PREV NON sia configurato
        $originalPrev = getenv('EDITORIAL_JWT_SECRET_PREV');
        putenv('EDITORIAL_JWT_SECRET_PREV=');
        $_ENV['EDITORIAL_JWT_SECRET_PREV'] = null;
        JwtService::resetCache();

        try {
            $this->expectException(SignatureInvalidException::class);
            (new JwtService())->verify($strangerToken);
        } finally {
            putenv('EDITORIAL_JWT_SECRET_PREV=' . ($originalPrev === false ? '' : $originalPrev));
            $_ENV['EDITORIAL_JWT_SECRET_PREV'] = $originalPrev === false ? null : $originalPrev;
            JwtService::resetCache();
        }
    }

    public function testPrevSecretTooShortIsIgnored(): void
    {
        // PREV < 16 chars → secretPrev() ritorna null → comportamento come no PREV
        $strangerToken = \Firebase\JWT\JWT::encode([
            'iss' => 'ainstein-editorial',
            'uid' => 1, 'sid' => 1, 'iat' => time(), 'exp' => time() + 3600,
        ], 'random-secret-only-15chr-x', 'HS256');

        $originalPrev = getenv('EDITORIAL_JWT_SECRET_PREV');
        putenv('EDITORIAL_JWT_SECRET_PREV=short');
        $_ENV['EDITORIAL_JWT_SECRET_PREV'] = 'short';
        JwtService::resetCache();

        try {
            $this->expectException(SignatureInvalidException::class);
            (new JwtService())->verify($strangerToken);
        } finally {
            putenv('EDITORIAL_JWT_SECRET_PREV=' . ($originalPrev === false ? '' : $originalPrev));
            $_ENV['EDITORIAL_JWT_SECRET_PREV'] = $originalPrev === false ? null : $originalPrev;
            JwtService::resetCache();
        }
    }
}
