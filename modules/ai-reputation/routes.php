<?php

/**
 * AI Reputation Radar - Routes
 *
 * Pattern: /ai-reputation/project/{id}/...
 */

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\AiReputation\Controllers\ProjectController;
use Modules\AiReputation\Controllers\DashboardController;
use Modules\AiReputation\Controllers\PromptController;
use Modules\AiReputation\Controllers\RunController;

if (!ModuleLoader::isModuleActive('ai-reputation')) {
    return;
}

// =============================================
// PROGETTI
// =============================================

Router::get('/ai-reputation', function () {
    Middleware::auth();
    return (new ProjectController())->index();
});

// Nuovo progetto → sempre da Global Projects
Router::get('/ai-reputation/projects/create', function () {
    Router::redirect('/projects/create');
});

Router::get('/ai-reputation/project/{id}', function ($id) {
    Middleware::auth();
    return (new DashboardController())->index((int) $id);
});

Router::get('/ai-reputation/project/{id}/settings', function ($id) {
    Middleware::auth();
    return (new ProjectController())->settings((int) $id);
});

Router::post('/ai-reputation/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->updateSettings((int) $id);
});

Router::post('/ai-reputation/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->destroy((int) $id);
});

// =============================================
// PROMPT (domande monitorate)
// =============================================

Router::get('/ai-reputation/project/{id}/prompts', function ($id) {
    Middleware::auth();
    return (new PromptController())->index((int) $id);
});

Router::post('/ai-reputation/project/{id}/prompts', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new PromptController())->store((int) $id);
});

Router::post('/ai-reputation/project/{id}/prompts/seed', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new PromptController())->seed((int) $id);
});

Router::post('/ai-reputation/project/{id}/prompts/{promptId}/toggle', function ($id, $promptId) {
    Middleware::auth();
    Middleware::csrf();
    return (new PromptController())->toggle((int) $id, (int) $promptId);
});

Router::post('/ai-reputation/project/{id}/prompts/{promptId}/delete', function ($id, $promptId) {
    Middleware::auth();
    Middleware::csrf();
    return (new PromptController())->destroy((int) $id, (int) $promptId);
});

// =============================================
// RUN (collector in background, SSE)
// =============================================

Router::post('/ai-reputation/project/{id}/runs/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new RunController())->start((int) $id);
});

// SSE: auth gestita internamente (niente redirect su stream)
Router::get('/ai-reputation/project/{id}/runs/stream', function ($id) {
    return (new RunController())->stream((int) $id);
});

Router::get('/ai-reputation/project/{id}/runs/status', function ($id) {
    Middleware::auth();
    return (new RunController())->status((int) $id);
});

Router::post('/ai-reputation/project/{id}/runs/cancel', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new RunController())->cancel((int) $id);
});

Router::post('/ai-reputation/project/{id}/runs/{runId}/reanalyze', function ($id, $runId) {
    Middleware::auth();
    Middleware::csrf();
    return (new RunController())->reanalyze((int) $id, (int) $runId);
});

Router::get('/ai-reputation/project/{id}/runs/{runId}', function ($id, $runId) {
    Middleware::auth();
    return (new RunController())->show((int) $id, (int) $runId);
});

// Omonimi da confermare (ADR-008): confirm = e' lui, reject = e' un altro
Router::post('/ai-reputation/project/{id}/facts/{factId}/{decision}', function ($id, $factId, $decision) {
    Middleware::auth();
    Middleware::csrf();
    return (new RunController())->decideFact((int) $id, (int) $factId, (string) $decision);
});
