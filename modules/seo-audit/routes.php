<?php

/**
 * SEO Audit Module - Routes
 *
 * Modulo per audit SEO completo con Google Search Console e analisi AI
 */

use Core\Router;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\ModuleLoader;
use Modules\SeoAudit\Controllers\ProjectController;
use Modules\SeoAudit\Controllers\CrawlController;
use Modules\SeoAudit\Controllers\AuditController;
use Modules\SeoAudit\Controllers\GscController;
use Modules\SeoAudit\Controllers\ReportController;

$moduleSlug = 'seo-audit';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECTS ROUTES
// ============================================

// Lista progetti (dashboard modulo)
Router::get('/seo-audit', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Nuovo progetto - form
Router::get('/seo-audit/create', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->create();
});

// Nuovo progetto - store
Router::post('/seo-audit/store', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->store();
});

// Dashboard progetto (redirect a project/{id}/dashboard)
Router::get('/seo-audit/project/{id}', function ($id) {
    header('Location: ' . url('/seo-audit/project/' . $id . '/dashboard'));
    exit;
});

// Impostazioni progetto - view
Router::get('/seo-audit/project/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->settings((int) $id);
});

// Impostazioni progetto - update
Router::post('/seo-audit/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->updateSettings((int) $id);
});

// Elimina progetto
Router::post('/seo-audit/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    $controller->destroy((int) $id);
});

// ============================================
// CRAWL ROUTES
// ============================================

// Avvia crawl
Router::post('/seo-audit/project/{id}/crawl', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    $controller->start((int) $id);
});

// Stato crawl (polling AJAX)
Router::get('/seo-audit/project/{id}/crawl/status', function ($id) {
    Middleware::auth();
    $controller = new CrawlController();
    $controller->status((int) $id);
});

// Stop crawl
Router::post('/seo-audit/project/{id}/crawl/stop', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CrawlController();
    $controller->stop((int) $id);
});

// ============================================
// GSC (GOOGLE SEARCH CONSOLE) ROUTES
// ============================================

// Dashboard GSC
Router::get('/seo-audit/project/{id}/gsc', function ($id) {
    Middleware::auth();
    $controller = new GscController();
    return $controller->dashboard((int) $id);
});

// Inizia connessione GSC
Router::get('/seo-audit/project/{id}/gsc/connect', function ($id) {
    Middleware::auth();
    $controller = new GscController();
    return $controller->connect((int) $id);
});

// Callback OAuth GSC
Router::get('/seo-audit/gsc/callback', function () {
    Middleware::auth();
    $controller = new GscController();
    $controller->callback();
});

// Lista proprietà GSC
Router::get('/seo-audit/project/{id}/gsc/properties', function ($id) {
    Middleware::auth();
    $controller = new GscController();
    return $controller->properties((int) $id);
});

// Seleziona proprietà GSC
Router::post('/seo-audit/project/{id}/gsc/select-property', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new GscController();
    $controller->selectProperty((int) $id);
});

// Sincronizza dati GSC
Router::post('/seo-audit/project/{id}/gsc/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new GscController();
    $controller->sync((int) $id);
});

// Disconnetti GSC
Router::post('/seo-audit/project/{id}/gsc/disconnect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new GscController();
    $controller->disconnect((int) $id);
});

// ============================================
// AUDIT DASHBOARD & CATEGORIES ROUTES
// ============================================

// Dashboard progetto
Router::get('/seo-audit/project/{id}/dashboard', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->dashboard((int) $id);
});

// Lista pagine crawlate
Router::get('/seo-audit/project/{id}/pages', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->pages((int) $id);
});

// Dettaglio singola pagina
Router::get('/seo-audit/project/{id}/page/{pageId}', function ($id, $pageId) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->pageDetail((int) $id, (int) $pageId);
});

// Lista issues
Router::get('/seo-audit/project/{id}/issues', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->issues((int) $id);
});

// Dettaglio categoria
Router::get('/seo-audit/project/{id}/category/{slug}', function ($id, $slug) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->category((int) $id, $slug);
});

// ============================================
// AI ANALYSIS ROUTES
// ============================================

// Genera analisi AI panoramica
Router::post('/seo-audit/project/{id}/analyze/overview', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AuditController();
    $controller->analyzeOverview((int) $id);
});

// Genera analisi AI per categoria
Router::post('/seo-audit/project/{id}/analyze/{category}', function ($id, $category) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AuditController();
    $controller->analyzeCategory((int) $id, $category);
});

// Pagina analisi AI
Router::get('/seo-audit/project/{id}/analysis', function ($id) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->analysis((int) $id);
});

// Analisi AI per categoria
Router::get('/seo-audit/project/{id}/analysis/{category}', function ($id, $category) {
    Middleware::auth();
    $controller = new AuditController();
    return $controller->analysisCategory((int) $id, $category);
});

// ============================================
// EXPORT / REPORTS ROUTES
// ============================================

// Export CSV issues
Router::get('/seo-audit/project/{id}/export/csv', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportCsv((int) $id);
});

// Export CSV per categoria
Router::get('/seo-audit/project/{id}/export/csv/{category}', function ($id, $category) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportCategoryCsv((int) $id, $category);
});

// Export CSV pagine
Router::get('/seo-audit/project/{id}/export/pages-csv', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportPagesCsv((int) $id);
});

// Export PDF (placeholder)
Router::get('/seo-audit/project/{id}/export/pdf', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportPdf((int) $id);
});

// Report JSON summary
Router::get('/seo-audit/project/{id}/export/summary', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->summary((int) $id);
});

// Pagina esporta (redirect a CSV per ora)
Router::get('/seo-audit/project/{id}/export', function ($id) {
    Middleware::auth();
    $controller = new ReportController();
    $controller->exportCsv((int) $id);
});
