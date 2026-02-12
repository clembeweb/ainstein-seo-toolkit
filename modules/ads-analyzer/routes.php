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
use Modules\AdsAnalyzer\Controllers\AnalysisController;
use Modules\AdsAnalyzer\Controllers\AnalysisHistoryController;
use Modules\AdsAnalyzer\Controllers\ExportController;
use Modules\AdsAnalyzer\Controllers\SettingsController;
use Modules\AdsAnalyzer\Controllers\ApiController;
use Modules\AdsAnalyzer\Controllers\ScriptController;
use Modules\AdsAnalyzer\Controllers\CampaignController;

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

// Visualizza progetto (redirect basato su tipo)
Router::get('/ads-analyzer/projects/{id}', function ($id) {
    Middleware::auth();
    $user = \Core\Auth::user();
    $project = \Modules\AdsAnalyzer\Models\Project::findByUserAndId($user['id'], (int) $id);

    if (!$project) {
        $_SESSION['flash_error'] = 'Progetto non trovato';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    if (($project['type'] ?? 'negative-kw') === 'campaign') {
        header('Location: ' . url("/ads-analyzer/projects/{$id}/campaign-dashboard"));
        exit;
    }

    $controller = new ProjectController();
    return $controller->show((int) $id);
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
// ANALISI FLOW
// ============================================

// Step 1: Upload CSV (form)
Router::get('/ads-analyzer/projects/{id}/upload', function ($id) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->upload((int) $id);
});

// Step 1: Process upload (AJAX)
Router::post('/ads-analyzer/projects/{id}/upload', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AnalysisController();
    return $controller->processUpload((int) $id);
});

// Step 2: Contesto business (form)
Router::get('/ads-analyzer/projects/{id}/context', function ($id) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->context((int) $id);
});

// Step 2 alternativo: Landing URLs (form)
Router::get('/ads-analyzer/projects/{id}/landing-urls', function ($id) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->landingUrls((int) $id);
});

// Salva URL landing per Ad Group (AJAX)
Router::post('/ads-analyzer/projects/{id}/ad-groups/{adGroupId}/landing-url', function ($id, $adGroupId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AnalysisController();
    return $controller->saveLandingUrl((int) $id, (int) $adGroupId);
});

// Estrai contesto da landing page (AJAX)
Router::post('/ads-analyzer/projects/{id}/ad-groups/{adGroupId}/extract-context', function ($id, $adGroupId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AnalysisController();
    return $controller->extractContext((int) $id, (int) $adGroupId);
});

// Estrai contesto da tutte le landing pages (AJAX)
Router::post('/ads-analyzer/projects/{id}/extract-all-contexts', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AnalysisController();
    return $controller->extractAllContexts((int) $id);
});

// Step 3: Avvia analisi AI (AJAX)
Router::post('/ads-analyzer/projects/{id}/analyze', function ($id) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->analyze((int) $id);
});

// Step 4: Risultati
Router::get('/ads-analyzer/projects/{id}/results', function ($id) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->results((int) $id);
});

// Toggle keyword (AJAX)
Router::post('/ads-analyzer/projects/{id}/keywords/{keywordId}/toggle', function ($id, $keywordId) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->toggleKeyword((int) $id, (int) $keywordId);
});

// Toggle categoria (AJAX)
Router::post('/ads-analyzer/projects/{id}/categories/{categoryId}/{action}', function ($id, $categoryId, $action) {
    Middleware::auth();
    $controller = new AnalysisController();
    return $controller->toggleCategory((int) $id, (int) $categoryId, $action);
});

// ============================================
// STORICO ANALISI
// ============================================

// Lista analisi per progetto
Router::get('/ads-analyzer/projects/{id}/analyses', function ($id) {
    Middleware::auth();
    $controller = new AnalysisHistoryController();
    return $controller->index((int) $id);
});

// Dettaglio singola analisi
Router::get('/ads-analyzer/projects/{id}/analyses/{analysisId}', function ($id, $analysisId) {
    Middleware::auth();
    $controller = new AnalysisHistoryController();
    return $controller->show((int) $id, (int) $analysisId);
});

// Elimina analisi (AJAX)
Router::post('/ads-analyzer/projects/{id}/analyses/{analysisId}/delete', function ($id, $analysisId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AnalysisHistoryController();
    return $controller->delete((int) $id, (int) $analysisId);
});

// Export analisi
Router::get('/ads-analyzer/projects/{id}/analyses/{analysisId}/export', function ($id, $analysisId) {
    Middleware::auth();
    $controller = new AnalysisHistoryController();
    return $controller->export((int) $id, (int) $analysisId);
});

// Toggle keyword analisi (AJAX)
Router::post('/ads-analyzer/projects/{id}/analyses/{analysisId}/keywords/{keywordId}/toggle', function ($id, $analysisId, $keywordId) {
    Middleware::auth();
    $controller = new AnalysisHistoryController();
    return $controller->toggleKeyword((int) $id, (int) $analysisId, (int) $keywordId);
});

// Toggle categoria analisi (AJAX)
Router::post('/ads-analyzer/projects/{id}/analyses/{analysisId}/categories/{categoryId}/{action}', function ($id, $analysisId, $categoryId, $action) {
    Middleware::auth();
    $controller = new AnalysisHistoryController();
    return $controller->toggleCategory((int) $id, (int) $analysisId, (int) $categoryId, $action);
});

// ============================================
// EXPORT
// ============================================

// Export CSV tutti
Router::get('/ads-analyzer/projects/{id}/export/csv', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->exportAll((int) $id);
});

// Export CSV per Ad Group
Router::get('/ads-analyzer/projects/{id}/export/ad-group/{adGroupId}', function ($id, $adGroupId) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->exportAdGroup((int) $id, (int) $adGroupId);
});

// Export Google Ads Editor
Router::get('/ads-analyzer/projects/{id}/export/google-ads-editor', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->exportGoogleAdsEditor((int) $id);
});

// Copy text (AJAX)
Router::post('/ads-analyzer/projects/{id}/copy-text', function ($id) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->copyText((int) $id);
});

Router::post('/ads-analyzer/projects/{id}/copy-text/{adGroupId}', function ($id, $adGroupId) {
    Middleware::auth();
    $controller = new ExportController();
    return $controller->copyText((int) $id, (int) $adGroupId);
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

// Dettaglio valutazione AI
Router::get('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}', function ($id, $evalId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->evaluationShow((int) $id, (int) $evalId);
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
