<?php

/**
 * Editorial API — Route definitions
 *
 * Tutti gli endpoint REST per il plugin WordPress "Ainstein Editorial".
 * Caricato da public/index.php prima di Router::dispatch().
 *
 * Convenzione URL: /api/editorial/v1/{resource}/{action}
 *
 * Endpoint pubblici (no auth):
 *   - POST /activate, /deactivate, /refresh-token
 *   - POST /webhooks/lemonsqueezy
 *
 * Endpoint autenticati (LicenseAuthMiddleware + RateLimitMiddleware + QuotaMiddleware):
 *   - tutti gli altri
 *
 * Stato implementazione: M1.3 = scheletro, tutti gli handler restituiscono 501
 * tranne quelli implementati nelle milestone successive (M1.4 attivazione, etc.).
 */

use Core\Router;

// =========================================
// Helper: wrapper middleware → controller
// =========================================

/**
 * Esegue una catena di middleware e poi delega al controller method.
 * Cattura header X-License-Key per il rate-limit bucket.
 */
$editorialAuth = function (string $controllerClass, string $method) {
    return function (...$args) use ($controllerClass, $method) {
        $auth = \Editorial\Middleware\LicenseAuthMiddleware::handle();
        \Editorial\Middleware\RateLimitMiddleware::handle($auth['license_key']);
        \Editorial\Middleware\QuotaMiddleware::handle($auth['license_key']);
        $controller = new $controllerClass();
        return $controller->{$method}(...$args);
    };
};

/**
 * Wrapper per endpoint pubblici (no auth).
 * Applica solo rate-limit per IP (no license_key disponibile).
 */
$editorialPublic = function (string $controllerClass, string $method) {
    return function (...$args) use ($controllerClass, $method) {
        \Editorial\Middleware\RateLimitMiddleware::handle(); // per-IP
        $controller = new $controllerClass();
        return $controller->{$method}(...$args);
    };
};

$base = '/api/editorial/v1';

// =========================================
// PUBLIC (no auth)
// =========================================

Router::post($base . '/activate',                $editorialPublic(\Editorial\Controllers\ActivationController::class, 'activate'));
Router::post($base . '/deactivate',              $editorialPublic(\Editorial\Controllers\ActivationController::class, 'deactivate'));
Router::post($base . '/refresh-token',           $editorialPublic(\Editorial\Controllers\ActivationController::class, 'refreshToken'));
Router::post($base . '/webhooks/lemonsqueezy',   $editorialPublic(\Editorial\Controllers\WebhookController::class,   'lemonsqueezy'));

// =========================================
// AUTHENTICATED (LicenseAuthMiddleware required)
// =========================================

// --- Content Brain (M2) ---
Router::get ($base . '/content-brain',                       $editorialAuth(\Editorial\Controllers\ContentBrainController::class, 'show'));
Router::post($base . '/content-brain/scan',                  $editorialAuth(\Editorial\Controllers\ContentBrainController::class, 'scan'));
Router::get ($base . '/content-brain/scan/{job_id}/stream',  $editorialAuth(\Editorial\Controllers\ContentBrainController::class, 'scanStream'));
Router::post($base . '/content-brain',                       $editorialAuth(\Editorial\Controllers\ContentBrainController::class, 'update')); // PUT mappato come POST (Router non ha put())

// --- Keyword Research (M4) ---
Router::post($base . '/keywords/research',                   $editorialAuth(\Editorial\Controllers\KeywordResearchController::class, 'research'));
Router::get ($base . '/keywords/research/{job_id}/stream',   $editorialAuth(\Editorial\Controllers\KeywordResearchController::class, 'researchStream'));

// --- Editorial Plans (M4) ---
Router::get ($base . '/editorial-plans',                     $editorialAuth(\Editorial\Controllers\EditorialPlanController::class, 'index'));
Router::post($base . '/editorial-plans',                     $editorialAuth(\Editorial\Controllers\EditorialPlanController::class, 'store'));
Router::post($base . '/editorial-plans/{id}',                $editorialAuth(\Editorial\Controllers\EditorialPlanController::class, 'update')); // PUT mappato come POST
Router::post($base . '/editorial-plans/{id}/activate',       $editorialAuth(\Editorial\Controllers\EditorialPlanController::class, 'activate'));

// --- Articles (M3) ---
Router::post($base . '/articles/generate',                   $editorialAuth(\Editorial\Controllers\ArticleController::class, 'generate'));
Router::get ($base . '/articles/generate/{job_id}/stream',   $editorialAuth(\Editorial\Controllers\ArticleController::class, 'generateStream'));
Router::get ($base . '/articles',                            $editorialAuth(\Editorial\Controllers\ArticleController::class, 'index'));
Router::get ($base . '/articles/{id}',                       $editorialAuth(\Editorial\Controllers\ArticleController::class, 'show'));
Router::post($base . '/articles/{id}/publish-to-wp',         $editorialAuth(\Editorial\Controllers\ArticleController::class, 'publishToWp'));
Router::post($base . '/articles/{id}/regenerate',            $editorialAuth(\Editorial\Controllers\ArticleController::class, 'regenerate'));

// --- Internal Links (M5) ---
Router::post($base . '/links/suggest',                       $editorialAuth(\Editorial\Controllers\InternalLinkController::class, 'suggest'));
Router::post($base . '/links/apply',                         $editorialAuth(\Editorial\Controllers\InternalLinkController::class, 'apply'));

// --- Subscription (M1.4 partial, M6 full) ---
Router::get ($base . '/subscription/status',                 $editorialAuth(\Editorial\Controllers\SubscriptionController::class, 'status'));
Router::post($base . '/subscription/portal-url',             $editorialAuth(\Editorial\Controllers\SubscriptionController::class, 'portalUrl'));

// =========================================
// NOTA: il Router corrente non supporta PUT/DELETE nativamente.
// Endpoint REST "PUT" sopra sono mappati come POST (idempotenza garantita
// dal payload, non dal verbo). Quando Router::put() sara aggiunto in
// Core\Router, aggiornare le route corrispondenti (commento "PUT mappato come POST").
// =========================================

unset($editorialAuth, $editorialPublic, $base);
