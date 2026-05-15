<?php

declare(strict_types=1);

namespace Editorial\Tests\Unit\Services;

use Editorial\Services\WebhookVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'test-secret-for-webhook-unit-tests-12345';

    private WebhookVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new WebhookVerifier(self::SECRET);
    }

    public function testVerifyAcceptsValidSignature(): void
    {
        $body = '{"meta":{"event_name":"subscription_created"},"data":{"id":"123"}}';
        $signature = hash_hmac('sha256', $body, self::SECRET);

        $this->assertTrue($this->verifier->verify($body, $signature));
    }

    public function testVerifyRejectsTamperedBody(): void
    {
        $body = '{"meta":{"event_name":"order_created"},"data":{"id":"123"}}';
        $signature = hash_hmac('sha256', $body, self::SECRET);

        $tamperedBody = str_replace('order_created', 'subscription_created', $body);

        $this->assertFalse($this->verifier->verify($tamperedBody, $signature));
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $body = '{"meta":{"event_name":"x"}}';
        $signature = hash_hmac('sha256', $body, self::SECRET);
        $tampered = substr($signature, 0, -1) . 'f';

        $this->assertFalse($this->verifier->verify($body, $tampered));
    }

    public function testVerifyRejectsWrongSecret(): void
    {
        $body = '{"a":1}';
        $signature = hash_hmac('sha256', $body, 'wrong-secret');

        $this->assertFalse($this->verifier->verify($body, $signature));
    }

    public function testVerifyRejectsEmptySignature(): void
    {
        $this->assertFalse($this->verifier->verify('any body', ''));
    }

    public function testVerifyRejectsEmptyBody(): void
    {
        // Even with empty body, computing HMAC of empty string should not magically match.
        $this->assertFalse($this->verifier->verify('', 'deadbeef'));
    }

    public function testVerifyWithEmptySecretAlwaysFails(): void
    {
        $verifierNoSecret = new WebhookVerifier('');
        $body = '{"a":1}';
        $sig = hash_hmac('sha256', $body, '');

        $this->assertFalse(
            $verifierNoSecret->verify($body, $sig),
            'Empty secret must short-circuit to false to avoid signature confusion'
        );
    }

    public function testHasSecretReflectsConstructorArg(): void
    {
        $this->assertTrue((new WebhookVerifier(self::SECRET))->hasSecret());
        $this->assertFalse((new WebhookVerifier(''))->hasSecret());
    }

    public function testSignProducesSignatureThatVerifies(): void
    {
        $body = '{"event":"test"}';
        $sig = $this->verifier->sign($body);

        $this->assertTrue($this->verifier->verify($body, $sig));
    }

    public function testVerifyIsTimingSafe(): void
    {
        // We cannot truly assert timing here, but we can confirm hash_equals semantics:
        // signatures of different lengths must not throw, must just return false.
        $body = '{"a":1}';
        $sig = hash_hmac('sha256', $body, self::SECRET);

        $this->assertFalse($this->verifier->verify($body, substr($sig, 0, 10)));
        $this->assertFalse($this->verifier->verify($body, $sig . 'extra'));
    }
}
