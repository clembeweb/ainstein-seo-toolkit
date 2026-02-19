<?php

/**
 * Content Creator - Routes
 *
 * Modulo per generazione massiva di contenuti pagina (body HTML) con AI
 */

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\ContentCreator\Controllers\ProjectController;
use Modules\ContentCreator\Controllers\UrlController;
use Modules\ContentCreator\Controllers\GeneratorController;
use Modules\ContentCreator\Controllers\ConnectorController;
use Modules\ContentCreator\Controllers\ExportController;

$moduleSlug = 'content-creator';

if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROGETTI
// ============================================

Router::get('/content-creator', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

Router::get('/content-creator/projects/create', function () {
    \Core\Router::redirect('/projects/create');
});

Router::post('/content-creator/projects', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->store();
});

Router::get('/content-creator/projects/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->settings((int) $id);
});

Router::post('/content-creator/projects/{id}/update', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->update((int) $id);
});

Router::post('/content-creator/projects/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->destroy((int) $id);
});

// ============================================
// IMPORT URL
// ============================================

Router::get('/content-creator/projects/{id}/import', function ($id) {
    Middleware::auth();
    $controller = new UrlController();
    return $controller->import((int) $id);
});

Router::post('/content-creator/projects/{id}/import/csv', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->importCsv((int) $id);
});

Router::post('/content-creator/projects/{id}/import/sitemap', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->importSitemap((int) $id);
});

Router::post('/content-creator/projects/{id}/import/cms', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->importFromCms((int) $id);
});

Router::post('/content-creator/projects/{id}/import/manual', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->importManual((int) $id);
});

Router::post('/content-creator/projects/{id}/discover', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->discover((int) $id);
});

Router::post('/content-creator/projects/{id}/import/keyword-research', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->importFromKR((int) $id);
});

// ============================================
// URL MANAGEMENT
// ============================================

Router::post('/content-creator/projects/{id}/urls/bulk-approve', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->bulkApprove((int) $id);
});

Router::post('/content-creator/projects/{id}/urls/bulk-delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->bulkDelete((int) $id);
});

Router::post('/content-creator/projects/{id}/urls/reset-scrape-errors', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->resetScrapeErrors((int) $id);
});

Router::post('/content-creator/projects/{id}/urls/reset-generation-errors', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->resetGenerationErrors((int) $id);
});

Router::post('/content-creator/projects/{id}/urls/{urlId}/update', function ($id, $urlId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->updateUrl((int) $id, (int) $urlId);
});

Router::post('/content-creator/projects/{id}/urls/{urlId}/approve', function ($id, $urlId) {
    Middleware::auth();
    $controller = new UrlController();
    return $controller->approve((int) $id, (int) $urlId);
});

Router::post('/content-creator/projects/{id}/urls/{urlId}/reject', function ($id, $urlId) {
    Middleware::auth();
    $controller = new UrlController();
    return $controller->reject((int) $id, (int) $urlId);
});

Router::post('/content-creator/projects/{id}/urls/{urlId}/delete', function ($id, $urlId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new UrlController();
    return $controller->delete((int) $id, (int) $urlId);
});

// ============================================
// SSE SCRAPING (route specifiche PRIMA dei wildcard)
// ============================================

Router::post('/content-creator/projects/{id}/start-scrape-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new GeneratorController();
    return $controller->startScrapeJob((int) $id);
});

Router::get('/content-creator/projects/{id}/scrape-stream', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->scrapeStream((int) $id);
});

Router::get('/content-creator/projects/{id}/scrape-job-status', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->scrapeJobStatus((int) $id);
});

Router::post('/content-creator/projects/{id}/cancel-scrape-job', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->cancelScrapeJob((int) $id);
});

// ============================================
// SSE AI GENERATION
// ============================================

Router::post('/content-creator/projects/{id}/start-generate-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new GeneratorController();
    return $controller->startGenerateJob((int) $id);
});

Router::get('/content-creator/projects/{id}/generate-stream', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->generateStream((int) $id);
});

Router::get('/content-creator/projects/{id}/generate-job-status', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->generateJobStatus((int) $id);
});

Router::post('/content-creator/projects/{id}/cancel-generate-job', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->cancelGenerateJob((int) $id);
});

// ============================================
// RISULTATI
// ============================================

Router::get('/content-creator/projects/{id}/results', function ($id) {
    Middleware::auth();
    $controller = new GeneratorController();
    return $controller->results((int) $id);
});

// ============================================
// EXPORT & CMS PUSH
// ============================================

Router::get('/content-creator/projects/{id}/export/csv', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->exportCsv((int) $id);
});

Router::post('/content-creator/projects/{id}/start-push-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ExportController();
    return $controller->startPushJob((int) $id);
});

Router::get('/content-creator/projects/{id}/push-stream', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->pushStream((int) $id);
});

Router::get('/content-creator/projects/{id}/push-job-status', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->pushJobStatus((int) $id);
});

Router::post('/content-creator/projects/{id}/cancel-push-job', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->cancelPushJob((int) $id);
});

// ============================================
// CONNETTORI CMS
// ============================================

Router::get('/content-creator/connectors', function () {
    Middleware::auth();
    $controller = new ConnectorController();
    return $controller->index();
});

Router::get('/content-creator/connectors/create', function () {
    Middleware::auth();
    $controller = new ConnectorController();
    return $controller->create();
});

Router::post('/content-creator/connectors', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ConnectorController();
    return $controller->store();
});

Router::post('/content-creator/connectors/{id}/test', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ConnectorController();
    return $controller->test((int) $id);
});

Router::post('/content-creator/connectors/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ConnectorController();
    return $controller->delete((int) $id);
});

Router::post('/content-creator/connectors/{id}/toggle', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ConnectorController();
    return $controller->toggle((int) $id);
});

Router::get('/content-creator/connectors/download-plugin/{type}', function ($type) {
    Middleware::auth();
    $controller = new ConnectorController();
    return $controller->downloadPlugin($type);
});

Router::post('/content-creator/connectors/{id}/sync-categories', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ConnectorController();
    return $controller->syncCategories((int) $id);
});

Router::get('/content-creator/connectors/{id}/items', function ($id) {
    Middleware::auth();
    $controller = new ConnectorController();
    return $controller->fetchItems((int) $id);
});

// ============================================
// URL PREVIEW (wildcard - DEVE stare per ultimo!)
// ============================================

Router::get('/content-creator/projects/{id}/urls/{urlId}', function ($id, $urlId) {
    Middleware::auth();
    $controller = new UrlController();
    return $controller->preview((int) $id, (int) $urlId);
});

// ============================================
// DASHBOARD PROGETTO (wildcard - DEVE stare per ultimo!)
// ============================================

Router::get('/content-creator/projects/{id}', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->show((int) $id);
});
