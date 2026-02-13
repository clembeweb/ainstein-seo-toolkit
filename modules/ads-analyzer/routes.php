<?php

/**
 * Google Ads Analyzer - Routes
 *
 * Modulo per analisi termini di ricerca Google Ads ed estrazione keyword negative con AI
 */

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Controllers\DashboardController;
use Modules\AdsAnalyzer\Controllers\ProjectController;
use Modules\AdsAnalyzer\Controllers\SettingsController;
use Modules\AdsAnalyzer\Controllers\ApiController;
use Modules\AdsAnalyzer\Controllers\ScriptController;
use Modules\AdsAnalyzer\Controllers\CampaignController;
use Modules\AdsAnalyzer\Controllers\SearchTermAnalysisController;

$moduleSlug = 'ads-analyzer';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// API PUBBLICA (no auth sessione, no CSRF)
// ============================================

Router::post('/api/v1/ads-analyzer/ingest', function () {
    $controller = new ApiController();
    return $controller->ingest();
});

// ============================================
// DASHBOARD
// ============================================

Router::get('/ads-analyzer', function () {
    Middleware::auth();
    $controller = new DashboardController();
    return $controller->index();
});

// ============================================
// PROGETTI CRUD
// ============================================

// Lista progetti
Router::get('/ads-analyzer/projects', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Nuovo progetto (form)
Router::get('/ads-analyzer/projects/create', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->create();
});

// Salva nuovo progetto
Router::post('/ads-analyzer/projects/store', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->store();
});

// Visualizza progetto (redirect a dashboard campagne)
Router::get('/ads-analyzer/projects/{id}', function ($id) {
    Middleware::auth();
    $user = \Core\Auth::user();
    $project = \Modules\AdsAnalyzer\Models\Project::findByUserAndId($user['id'], (int) $id);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    // Tutti i progetti vanno alla dashboard campagne
    // (progetti negative-kw legacy vengono reindirizzati)
    if (($project['type'] ?? 'negative-kw') === 'negative-kw') {
        $_SESSION['flash_error'] = 'Questa modalita non e piu disponibile. I progetti Keyword Negative sono stati rimossi.';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    header('Location: ' . url("/ads-analyzer/projects/{$id}/campaign-dashboard"));
    exit;
});

// Dashboard progetto campagne
Router::get('/ads-analyzer/projects/{id}/campaign-dashboard', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->dashboard((int) $id);
});

// Modifica progetto (form)
Router::get('/ads-analyzer/projects/{id}/edit', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->edit((int) $id);
});

// Salva modifiche progetto
Router::post('/ads-analyzer/projects/{id}/update', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->update((int) $id);
});

// Elimina progetto
Router::post('/ads-analyzer/projects/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->destroy((int) $id);
});

// Duplica progetto
Router::post('/ads-analyzer/projects/{id}/duplicate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->duplicate((int) $id);
});

// Toggle archiviazione
Router::post('/ads-analyzer/projects/{id}/toggle-archive', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->toggleArchive((int) $id);
});

// ============================================
// GOOGLE ADS SCRIPT
// ============================================

// Setup script (genera, copia, configura)
Router::get('/ads-analyzer/projects/{id}/script', function ($id) {
    Middleware::auth();
    $controller = new ScriptController();
    return $controller->setup((int) $id);
});

// Rigenera token API (AJAX)
Router::post('/ads-analyzer/projects/{id}/script/regenerate-token', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ScriptController();
    return $controller->regenerateToken((int) $id);
});

// Aggiorna configurazione script
Router::post('/ads-analyzer/projects/{id}/script/config', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ScriptController();
    return $controller->updateConfig((int) $id);
});

// Storico esecuzioni script
Router::get('/ads-analyzer/projects/{id}/script/runs', function ($id) {
    Middleware::auth();
    $controller = new ScriptController();
    return $controller->runs((int) $id);
});

// ============================================
// DATI CAMPAGNE (Tool 2)
// ============================================

// Lista campagne per progetto
Router::get('/ads-analyzer/projects/{id}/campaigns', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->index((int) $id);
});

// Dettaglio run campagne
Router::get('/ads-analyzer/projects/{id}/campaigns/{runId}', function ($id, $runId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->show((int) $id, (int) $runId);
});

// Avvia valutazione AI campagne (AJAX)
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->evaluate((int) $id);
});

// Toggle auto-valutazione (AJAX)
Router::post('/ads-analyzer/projects/{id}/campaigns/toggle-auto-evaluate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->toggleAutoEvaluate((int) $id);
});

// Dettaglio valutazione AI
Router::get('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}', function ($id, $evalId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->evaluationShow((int) $id, (int) $evalId);
});

// ============================================
// ANALISI KEYWORD NEGATIVE (Campaign Projects)
// ============================================

// Pagina principale
Router::get('/ads-analyzer/projects/{id}/search-term-analysis', function ($id) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->index((int) $id);
});

// AJAX: dati per run selezionato
Router::get('/ads-analyzer/projects/{id}/search-term-analysis/run-data', function ($id) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->getRunData((int) $id);
});

// AJAX: rileva URL landing dagli annunci
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/detect-urls', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SearchTermAnalysisController();
    return $controller->detectLandingUrls((int) $id);
});

// AJAX: estrai contesti landing pages
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/extract-contexts', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SearchTermAnalysisController();
    return $controller->extractContexts((int) $id);
});

// AJAX: analisi AI keyword negative
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/analyze', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SearchTermAnalysisController();
    return $controller->analyze((int) $id);
});

// AJAX: risultati analisi
Router::get('/ads-analyzer/projects/{id}/search-term-analysis/results', function ($id) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->getResults((int) $id);
});

// AJAX: toggle keyword
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/keywords/{keywordId}/toggle', function ($id, $keywordId) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->toggleKeyword((int) $id, (int) $keywordId);
});

// AJAX: azioni bulk categoria
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/categories/{categoryId}/{action}', function ($id, $categoryId, $action) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->toggleCategory((int) $id, (int) $categoryId, $action);
});

// AJAX: copia keyword selezionate come testo
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/copy-text', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SearchTermAnalysisController();
    return $controller->copyText((int) $id);
});

// Export CSV / Google Ads Editor
Router::get('/ads-analyzer/projects/{id}/search-term-analysis/export', function ($id) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->export((int) $id);
});

// ============================================
// CONTESTI BUSINESS SALVATI
// ============================================

// Salva contesto (AJAX)
Router::post('/ads-analyzer/contexts/save', function () {
    Middleware::auth();
    $controller = new SettingsController();
    return $controller->saveContext();
});

// Elimina contesto
Router::post('/ads-analyzer/contexts/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SettingsController();
    return $controller->deleteContext((int) $id);
});

// ============================================
// SETTINGS
// ============================================

Router::get('/ads-analyzer/settings', function () {
    Middleware::auth();
    $controller = new SettingsController();
    return $controller->index();
});

Router::post('/ads-analyzer/settings', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SettingsController();
    return $controller->update();
});
