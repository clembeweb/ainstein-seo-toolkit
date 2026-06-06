<?php

declare(strict_types=1);

/**
 * Definizione rotte API Ainstein Editorial — base path `/api/editorial/v1`.
 *
 * Endpoint implementati in M1: activate, deactivate, refresh-token,
 * subscription/status, webhooks/lemonsqueezy. Tutti gli altri sono stub 501
 * che dichiarano la milestone target.
 *
 * Riferimento: plugins/ainstein-editorial/docs/design.md §5.
 */

use Ainstein\Editorial\Http\Router;
use Ainstein\Editorial\Controllers\ActivationController;
use Ainstein\Editorial\Controllers\SubscriptionController;
use Ainstein\Editorial\Controllers\WebhookController;
use Ainstein\Editorial\Controllers\StubController;
use Ainstein\Editorial\Controllers\HealthController;
use Ainstein\Editorial\Middleware\LicenseAuthMiddleware;
use Ainstein\Editorial\Middleware\RateLimitMiddleware;
use Ainstein\Editorial\Middleware\QuotaMiddleware;

return function (Router $r): void {
    $rate  = [RateLimitMiddleware::class];
    $auth  = [RateLimitMiddleware::class, LicenseAuthMiddleware::class];
    $authQ = [RateLimitMiddleware::class, LicenseAuthMiddleware::class, QuotaMiddleware::class];

    // --- Auth & Activation (pubblici, no LicenseAuth) ---
    $r->add('POST', '/activate',       [ActivationController::class, 'activate'],     $rate);
    $r->add('POST', '/deactivate',     [ActivationController::class, 'deactivate'],   $rate);
    $r->add('POST', '/refresh-token',  [ActivationController::class, 'refreshToken'], $rate);

    // --- Webhooks (autenticati via firma HMAC, no LicenseAuth/Rate) ---
    $r->add('POST', '/webhooks/lemonsqueezy', [WebhookController::class, 'lemonsqueezy']);

    // --- Subscription & quota ---
    $r->add('GET',  '/subscription/status',    [SubscriptionController::class, 'status'],     $auth);
    $r->add('POST', '/subscription/portal-url', [SubscriptionController::class, 'portalUrl'], $auth);

    // --- Content Brain (M2) ---
    $r->add('GET',  '/content-brain',       [StubController::class, 'm2'], $rate);
    $r->add('POST', '/content-brain/scan',  [StubController::class, 'm2'], $rate);
    $r->add('PUT',  '/content-brain',       [StubController::class, 'm2'], $rate);

    // --- Keyword research (M4) ---
    $r->add('POST', '/keywords/research', [StubController::class, 'm4'], $rate);

    // --- Editorial plan (M4) ---
    $r->add('GET',  '/editorial-plans',            [StubController::class, 'm4'], $rate);
    $r->add('POST', '/editorial-plans',            [StubController::class, 'm4'], $rate);
    $r->add('PUT',  '/editorial-plans/{id}',       [StubController::class, 'm4'], $rate);
    $r->add('POST', '/editorial-plans/{id}/activate', [StubController::class, 'm4'], $rate);

    // --- Article generation (M3) ---
    $r->add('POST', '/articles/generate',            [StubController::class, 'm3'], $rate);
    $r->add('GET',  '/articles',                     [StubController::class, 'm3'], $rate);
    $r->add('GET',  '/articles/{id}',                [StubController::class, 'm3'], $rate);
    $r->add('POST', '/articles/{id}/publish-to-wp',  [StubController::class, 'm3'], $rate);
    $r->add('POST', '/articles/{id}/regenerate',     [StubController::class, 'm3'], $rate);

    // --- Internal links (M5) ---
    $r->add('POST', '/links/suggest', [StubController::class, 'm5'], $rate);
    $r->add('POST', '/links/apply',   [StubController::class, 'm5'], $rate);

    // --- Health check (sempre disponibile, no auth) ---
    $r->add('GET', '/ping', [HealthController::class, 'ping'], []);
};
