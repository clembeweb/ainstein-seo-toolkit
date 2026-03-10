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
use Modules\AdsAnalyzer\Controllers\CampaignController;
use Modules\AdsAnalyzer\Controllers\CampaignCreatorController;
use Modules\AdsAnalyzer\Controllers\SearchTermAnalysisController;

$moduleSlug = 'ads-analyzer';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

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
    \Core\Router::redirect('/projects/create');
});

// Salva nuovo progetto
Router::post('/ads-analyzer/projects/store', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->store();
});

// Visualizza progetto (redirect in base al tipo)
Router::get('/ads-analyzer/projects/{id}', function ($id) {
    Middleware::auth();
    $user = \Core\Auth::user();
    $project = \Modules\AdsAnalyzer\Models\Project::findAccessible($user['id'], (int) $id);

    if (!$project) {
        $_SESSION['_flash']['error'] = 'Progetto non trovato';
        header('Location: ' . url('/ads-analyzer'));
        exit;
    }

    if ($project['type'] === 'campaign-creator') {
        header('Location: ' . url("/ads-analyzer/projects/{$id}/campaign-creator"));
        exit;
    }

    if (($project['type'] ?? 'negative-kw') === 'negative-kw') {
        $_SESSION['_flash']['error'] = 'Questa modalita non e piu disponibile.';
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
// GOOGLE ADS API CONNECTION
// ============================================

// Selezione account Google Ads (GET: mostra, POST: salva)
Router::get('/ads-analyzer/projects/{id}/connect', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->selectAccount((int) $id);
});

Router::post('/ads-analyzer/projects/{id}/connect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->selectAccount((int) $id);
});

// Redirect a OAuth Google Ads
Router::get('/ads-analyzer/projects/{id}/connect-google-ads', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->connectGoogleAds((int) $id);
});

// Disconnetti account Google Ads
Router::post('/ads-analyzer/projects/{id}/disconnect-google-ads', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->disconnectGoogleAds((int) $id);
});

// ============================================
// DATI CAMPAGNE
// ============================================

// Sync campagne da Google Ads API (AJAX)
Router::post('/ads-analyzer/projects/{id}/campaigns/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->sync((int) $id);
});

// Stato sync in corso (polling)
Router::get('/ads-analyzer/projects/{id}/campaigns/sync-status', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->syncStatus((int) $id);
});

// Lista campagne per progetto
Router::get('/ads-analyzer/projects/{id}/campaigns', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->index((int) $id);
});

// Sync disponibili per selettore periodo (AJAX) — DEVE stare PRIMA di campaigns/{syncId}
Router::get('/ads-analyzer/projects/{id}/campaigns/available-syncs', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->availableSyncs((int) $id);
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

// Dettaglio sync campagne — DOPO le route specifiche per evitare conflitti con {syncId}
Router::get('/ads-analyzer/projects/{id}/campaigns/{syncId}', function ($id, $syncId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->show((int) $id, (int) $syncId);
});

// Dettaglio valutazione AI
Router::get('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}', function ($id, $evalId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->evaluationShow((int) $id, (int) $evalId);
});

// Genera contenuto AI per fix issue/suggerimento valutazione
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/generate', function ($id, $evalId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->generateFix((int) $id, (int) $evalId);
});

// Export PDF valutazione
Router::get('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/export-pdf', function ($id, $evalId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->exportPdf((int) $id, (int) $evalId);
});

// Export CSV fix AI per Google Ads Editor
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/export-csv', function ($id, $evalId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->exportCsv((int) $id, (int) $evalId);
});

// Applica suggerimento generato direttamente su Google Ads
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/apply', function ($id, $evalId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->applyToGoogleAds((int) $id, (int) $evalId);
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

// AJAX: dati per sync selezionata
Router::get('/ads-analyzer/projects/{id}/search-term-analysis/sync-data', function ($id) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->getSyncData((int) $id);
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
    Middleware::csrf();
    $controller = new SearchTermAnalysisController();
    return $controller->toggleKeyword((int) $id, (int) $keywordId);
});

// AJAX: azioni bulk categoria
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/categories/{categoryId}/{action}', function ($id, $categoryId, $action) {
    Middleware::auth();
    Middleware::csrf();
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

// AJAX: lista campagne per modale applicazione negatives
Router::get('/ads-analyzer/projects/{id}/campaigns-list', function ($id) {
    Middleware::auth();
    $controller = new SearchTermAnalysisController();
    return $controller->campaignsList((int) $id);
});

// AJAX: applica negative keywords su Google Ads via API
Router::post('/ads-analyzer/projects/{id}/search-term-analysis/apply-negatives', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SearchTermAnalysisController();
    return $controller->applyNegativeKeywords((int) $id);
});

// ============================================
// CAMPAIGN CREATOR (Wizard AI)
// ============================================

// Wizard principale
Router::get('/ads-analyzer/projects/{id}/campaign-creator', function ($id) {
    Middleware::auth();
    $controller = new CampaignCreatorController();
    return $controller->wizard((int) $id);
});

// Analizza landing page (scraping + brief auto) (AJAX lungo)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/analyze-landing', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->analyzeLanding((int) $id);
});

// Salva brief editato (AJAX rapido)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/update-brief', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->updateBrief((int) $id);
});

// Keyword Research AI (AJAX lungo)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/generate-kw', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->generateKeywords((int) $id);
});

// Toggle keyword selezionata (AJAX rapido)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/toggle-kw/{kwId}', function ($id, $kwId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->toggleKeyword((int) $id, (int) $kwId);
});

// Aggiorna match type keyword (AJAX rapido)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/update-match/{kwId}', function ($id, $kwId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->updateMatchType((int) $id, (int) $kwId);
});

// Genera campagna completa (AJAX lungo)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/generate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->generateCampaign((int) $id);
});

// Copia testo (AJAX rapido)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/copy-text', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->copyText((int) $id);
});

// Export CSV Google Ads Editor
Router::get('/ads-analyzer/projects/{id}/campaign-creator/export', function ($id) {
    Middleware::auth();
    $controller = new CampaignCreatorController();
    return $controller->exportCsv((int) $id);
});

// Pubblica su Google Ads (AJAX lungo)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/publish', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->publishToGoogleAds((int) $id);
});

// Rigenera (AJAX rapido)
Router::post('/ads-analyzer/projects/{id}/campaign-creator/regenerate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignCreatorController();
    return $controller->regenerate((int) $id);
});

// ============================================
// CONTESTI BUSINESS SALVATI
// ============================================

// Salva contesto (AJAX)
Router::post('/ads-analyzer/contexts/save', function () {
    Middleware::auth();
    Middleware::csrf();
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
