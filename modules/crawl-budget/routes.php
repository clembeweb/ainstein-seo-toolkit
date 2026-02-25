<?php

/**
 * Crawl Budget Optimizer Module - Routes
 *
 * Modulo per analisi crawl budget: redirect chains, pagine spreco, conflitti indexability
 */

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\CrawlBudget\Controllers\ProjectController;
use Modules\CrawlBudget\Controllers\CrawlController;
use Modules\CrawlBudget\Controllers\ResultsController;
use Modules\CrawlBudget\Controllers\ReportController;

$moduleSlug = 'crawl-budget';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECTS
// ============================================

// Lista progetti (dashboard modulo)
Router::get('/crawl-budget', function () {
    Middleware::auth();
    return (new ProjectController())->index();
});

// Nuovo progetto - redirect a hub globale
Router::get('/crawl-budget/create', function () {
    Middleware::auth();
    \Core\Router::redirect('/projects/create');
});

// Dashboard progetto
Router::get('/crawl-budget/projects/{id}', function ($id) {
    Middleware::auth();
    return (new ProjectController())->dashboard((int) $id);
});

// Impostazioni progetto
Router::get('/crawl-budget/projects/{id}/settings', function ($id) {
    Middleware::auth();
    return (new ProjectController())->settings((int) $id);
});

Router::post('/crawl-budget/projects/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->updateSettings((int) $id);
});

// Elimina progetto
Router::post('/crawl-budget/projects/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    (new ProjectController())->destroy((int) $id);
});

// ============================================
// CRAWL
// ============================================

// Avvia crawl
Router::post('/crawl-budget/projects/{id}/crawl/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->start((int) $id);
});

// SSE stream
Router::get('/crawl-budget/projects/{id}/crawl/stream', function ($id) {
    Middleware::auth();
    $controller = new CrawlController();
    $controller->processStream((int) $id);
});

// Cancella crawl
Router::post('/crawl-budget/projects/{id}/crawl/cancel', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->cancel((int) $id);
});

// Polling fallback
Router::get('/crawl-budget/projects/{id}/crawl/job-status', function ($id) {
    Middleware::auth();
    $controller = new CrawlController();
    return $controller->jobStatus((int) $id);
});

// ============================================
// RESULTS
// ============================================

// Overview risultati
Router::get('/crawl-budget/projects/{id}/results', function ($id) {
    Middleware::auth();
    return (new ResultsController())->overview((int) $id);
});

// Tab redirect
Router::get('/crawl-budget/projects/{id}/results/redirects', function ($id) {
    Middleware::auth();
    return (new ResultsController())->redirects((int) $id);
});

// Tab waste
Router::get('/crawl-budget/projects/{id}/results/waste', function ($id) {
    Middleware::auth();
    return (new ResultsController())->waste((int) $id);
});

// Tab indexability
Router::get('/crawl-budget/projects/{id}/results/indexability', function ($id) {
    Middleware::auth();
    return (new ResultsController())->indexability((int) $id);
});

// Tab tutte le pagine
Router::get('/crawl-budget/projects/{id}/results/pages', function ($id) {
    Middleware::auth();
    return (new ResultsController())->pages((int) $id);
});

// ============================================
// REPORT AI
// ============================================

// Genera report AI
Router::post('/crawl-budget/projects/{id}/report/generate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ReportController())->generate((int) $id);
});

// Visualizza report
Router::get('/crawl-budget/projects/{id}/report', function ($id) {
    Middleware::auth();
    return (new ReportController())->view((int) $id);
});
