<?php

/**
 * SEO Onpage Optimizer Module - Routes
 *
 * Pattern: /seo-onpage/project/{id}/...
 */

use Core\Router;
use Core\Middleware;

// Controllers
use Modules\SeoOnpage\Controllers\ProjectController;
use Modules\SeoOnpage\Controllers\DashboardController;
use Modules\SeoOnpage\Controllers\PageController;
use Modules\SeoOnpage\Controllers\AuditController;
use Modules\SeoOnpage\Controllers\IssueController;
use Modules\SeoOnpage\Controllers\AiController;

// =============================================
// PROGETTI
// =============================================

// Lista progetti (home modulo)
Router::get('/seo-onpage', function () {
    Middleware::auth();
    return (new ProjectController())->index();
});

// Crea progetto → redirect a Global Projects
Router::get('/seo-onpage/projects/create', function () {
    \Core\Router::redirect('/projects/create');
});

Router::post('/seo-onpage/projects', function () {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->store();
});

// Dashboard progetto
Router::get('/seo-onpage/project/{id}', function ($id) {
    Middleware::auth();
    return (new DashboardController())->index((int) $id);
});

// Impostazioni progetto
Router::get('/seo-onpage/project/{id}/settings', function ($id) {
    Middleware::auth();
    return (new ProjectController())->settings((int) $id);
});

Router::post('/seo-onpage/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->updateSettings((int) $id);
});

// Elimina progetto
Router::post('/seo-onpage/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->destroy((int) $id);
});

// =============================================
// PAGINE
// =============================================

// Lista pagine
Router::get('/seo-onpage/project/{id}/pages', function ($id) {
    Middleware::auth();
    return (new PageController())->index((int) $id);
});

// Form import pagine
Router::get('/seo-onpage/project/{id}/pages/import', function ($id) {
    Middleware::auth();
    return (new PageController())->import((int) $id);
});

// Import da sitemap
Router::post('/seo-onpage/project/{id}/pages/import/sitemap', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new PageController())->importSitemap((int) $id);
});

// Import da CSV
Router::post('/seo-onpage/project/{id}/pages/import/csv', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new PageController())->importCsv((int) $id);
});

// Import manuale
Router::post('/seo-onpage/project/{id}/pages/import/manual', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new PageController())->importManual((int) $id);
});

// Dettaglio pagina
Router::get('/seo-onpage/project/{id}/pages/{pageId}', function ($id, $pageId) {
    Middleware::auth();
    return (new PageController())->show((int) $id, (int) $pageId);
});

// Elimina pagina
Router::post('/seo-onpage/project/{id}/pages/{pageId}/delete', function ($id, $pageId) {
    Middleware::auth();
    Middleware::csrf();
    return (new PageController())->destroy((int) $id, (int) $pageId);
});

// =============================================
// AUDIT (Background Job + SSE)
// =============================================

// Vista audit
Router::get('/seo-onpage/project/{id}/audit', function ($id) {
    Middleware::auth();
    return (new AuditController())->index((int) $id);
});

// Avvia audit (crea job)
Router::post('/seo-onpage/project/{id}/audit/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AuditController())->start((int) $id);
});

// SSE Stream per progress (NO auth middleware - gestito internamente)
Router::get('/seo-onpage/project/{id}/audit/stream', function ($id) {
    return (new AuditController())->stream((int) $id);
});

// Polling fallback per status
Router::get('/seo-onpage/project/{id}/audit/status', function ($id) {
    Middleware::auth();
    return (new AuditController())->status((int) $id);
});

// Annulla audit
Router::post('/seo-onpage/project/{id}/audit/cancel', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AuditController())->cancel((int) $id);
});

// =============================================
// ISSUES
// =============================================

// Lista issues
Router::get('/seo-onpage/project/{id}/issues', function ($id) {
    Middleware::auth();
    return (new IssueController())->index((int) $id);
});

// Aggiorna status issue
Router::post('/seo-onpage/project/{id}/issues/{issueId}/status', function ($id, $issueId) {
    Middleware::auth();
    Middleware::csrf();
    return (new IssueController())->updateStatus((int) $id, (int) $issueId);
});

// Bulk update issues
Router::post('/seo-onpage/project/{id}/issues/bulk', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new IssueController())->bulkUpdate((int) $id);
});

// =============================================
// AI SUGGESTIONS
// =============================================

// Lista suggerimenti
Router::get('/seo-onpage/project/{id}/ai', function ($id) {
    Middleware::auth();
    return (new AiController())->index((int) $id);
});

// Genera suggerimenti per pagina
Router::post('/seo-onpage/project/{id}/ai/generate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generate((int) $id);
});

// Marca suggerimento come applicato
Router::post('/seo-onpage/project/{id}/ai/{suggestionId}/apply', function ($id, $suggestionId) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->apply((int) $id, (int) $suggestionId);
});

// Rifiuta suggerimento
Router::post('/seo-onpage/project/{id}/ai/{suggestionId}/reject', function ($id, $suggestionId) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->reject((int) $id, (int) $suggestionId);
});

// Costo suggerimenti (AJAX)
Router::get('/seo-onpage/project/{id}/ai/cost', function ($id) {
    Middleware::auth();
    return (new AiController())->getCost((int) $id);
});
