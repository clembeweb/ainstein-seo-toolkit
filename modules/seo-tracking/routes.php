<?php

/**
 * SEO Position Tracking Module - Routes
 */

use Core\Router;
use Core\Middleware;

// Controllers
use Modules\SeoTracking\Controllers\ProjectController;
use Modules\SeoTracking\Controllers\DashboardController;
use Modules\SeoTracking\Controllers\KeywordController;
use Modules\SeoTracking\Controllers\GscController;
use Modules\SeoTracking\Controllers\Ga4Controller;
use Modules\SeoTracking\Controllers\AlertController;
use Modules\SeoTracking\Controllers\ReportController;
use Modules\SeoTracking\Controllers\AiController;
use Modules\SeoTracking\Controllers\ExportController;
use Modules\SeoTracking\Controllers\ApiController;

// =============================================
// PROGETTI
// =============================================

// Lista progetti (home modulo)
Router::get('/seo-tracking', function () {
    Middleware::auth();
    return (new ProjectController())->index();
});

// Crea progetto
Router::get('/seo-tracking/projects/create', function () {
    Middleware::auth();
    return (new ProjectController())->create();
});

Router::post('/seo-tracking/projects/store', function () {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->store();
});

// Dashboard progetto
Router::get('/seo-tracking/projects/{id}', function ($id) {
    Middleware::auth();
    return (new DashboardController())->index((int) $id);
});

// Impostazioni progetto
Router::get('/seo-tracking/projects/{id}/settings', function ($id) {
    Middleware::auth();
    return (new ProjectController())->settings((int) $id);
});

Router::post('/seo-tracking/projects/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->updateSettings((int) $id);
});

// Elimina progetto
Router::post('/seo-tracking/projects/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->destroy((int) $id);
});

// =============================================
// GOOGLE SEARCH CONSOLE
// =============================================

// Avvia connessione OAuth
Router::get('/seo-tracking/projects/{id}/gsc/connect', function ($id) {
    Middleware::auth();
    return (new GscController())->connect((int) $id);
});

// Callback OAuth (NO CSRF - viene da Google)
Router::get('/seo-tracking/gsc/callback', function () {
    return (new GscController())->callback();
});

// Lista proprietà GSC disponibili
Router::get('/seo-tracking/projects/{id}/gsc/properties', function ($id) {
    Middleware::auth();
    return (new GscController())->properties((int) $id);
});

// Seleziona proprietà GSC
Router::post('/seo-tracking/projects/{id}/gsc/select-property', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->selectProperty((int) $id);
});

// Sync giornaliero manuale
Router::post('/seo-tracking/projects/{id}/gsc/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->sync((int) $id);
});

// Sync storico completo (16 mesi)
Router::post('/seo-tracking/projects/{id}/gsc/sync-full', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->syncFull((int) $id);
});

// Disconnetti GSC
Router::post('/seo-tracking/projects/{id}/gsc/disconnect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->disconnect((int) $id);
});

// =============================================
// GOOGLE ANALYTICS 4
// =============================================

// Pagina connessione GA4
Router::get('/seo-tracking/projects/{id}/ga4/connect', function ($id) {
    Middleware::auth();
    return (new Ga4Controller())->connect((int) $id);
});

// Upload Service Account JSON
Router::post('/seo-tracking/projects/{id}/ga4/upload-credentials', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->uploadCredentials((int) $id);
});

// Lista proprietà GA4 disponibili
Router::get('/seo-tracking/projects/{id}/ga4/properties', function ($id) {
    Middleware::auth();
    return (new Ga4Controller())->properties((int) $id);
});

// Seleziona proprietà GA4
Router::post('/seo-tracking/projects/{id}/ga4/select-property', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->selectProperty((int) $id);
});

// Sync manuale
Router::post('/seo-tracking/projects/{id}/ga4/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->sync((int) $id);
});

// Disconnetti GA4
Router::post('/seo-tracking/projects/{id}/ga4/disconnect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->disconnect((int) $id);
});

// =============================================
// DASHBOARD E DATI
// =============================================

Router::get('/seo-tracking/projects/{id}/dashboard', function ($id) {
    Middleware::auth();
    return (new DashboardController())->index((int) $id);
});

Router::get('/seo-tracking/projects/{id}/keywords-overview', function ($id) {
    Middleware::auth();
    return (new DashboardController())->keywords((int) $id);
});

Router::get('/seo-tracking/projects/{id}/pages', function ($id) {
    Middleware::auth();
    return (new DashboardController())->pages((int) $id);
});

Router::get('/seo-tracking/projects/{id}/revenue', function ($id) {
    Middleware::auth();
    return (new DashboardController())->revenue((int) $id);
});

// =============================================
// KEYWORD TRACKING
// =============================================

// Lista keyword tracciate
Router::get('/seo-tracking/projects/{id}/keywords', function ($id) {
    Middleware::auth();
    return (new KeywordController())->index((int) $id);
});

// Tutte le keyword (da GSC)
Router::get('/seo-tracking/projects/{id}/keywords/all', function ($id) {
    Middleware::auth();
    return (new KeywordController())->all((int) $id);
});

// Form aggiungi keyword
Router::get('/seo-tracking/projects/{id}/keywords/add', function ($id) {
    Middleware::auth();
    return (new KeywordController())->add((int) $id);
});

// Salva keyword
Router::post('/seo-tracking/projects/{id}/keywords/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->store((int) $id);
});

// Import keyword da CSV
Router::post('/seo-tracking/projects/{id}/keywords/import', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->import((int) $id);
});

// Dettaglio keyword
Router::get('/seo-tracking/projects/{id}/keywords/{keywordId}', function ($id, $keywordId) {
    Middleware::auth();
    return (new KeywordController())->detail((int) $id, (int) $keywordId);
});

// Aggiorna keyword
Router::post('/seo-tracking/projects/{id}/keywords/{keywordId}/update', function ($id, $keywordId) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->update((int) $id, (int) $keywordId);
});

// Elimina keyword
Router::post('/seo-tracking/projects/{id}/keywords/{keywordId}/delete', function ($id, $keywordId) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->destroy((int) $id, (int) $keywordId);
});

// =============================================
// ALERT
// =============================================

// Lista alert attivi
Router::get('/seo-tracking/projects/{id}/alerts', function ($id) {
    Middleware::auth();
    return (new AlertController())->index((int) $id);
});

// Impostazioni alert
Router::get('/seo-tracking/projects/{id}/alerts/settings', function ($id) {
    Middleware::auth();
    return (new AlertController())->settings((int) $id);
});

Router::post('/seo-tracking/projects/{id}/alerts/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AlertController())->updateSettings((int) $id);
});

// Storico alert
Router::get('/seo-tracking/projects/{id}/alerts/history', function ($id) {
    Middleware::auth();
    return (new AlertController())->history((int) $id);
});

// Marca alert come letto
Router::post('/seo-tracking/projects/{id}/alerts/{alertId}/read', function ($id, $alertId) {
    Middleware::auth();
    Middleware::csrf();
    return (new AlertController())->markRead((int) $id, (int) $alertId);
});

// Archivia/Elimina alert
Router::post('/seo-tracking/projects/{id}/alerts/{alertId}/dismiss', function ($id, $alertId) {
    Middleware::auth();
    Middleware::csrf();
    return (new AlertController())->archive((int) $alertId);
});

// =============================================
// REPORT AI
// =============================================

// Lista report
Router::get('/seo-tracking/projects/{id}/reports', function ($id) {
    Middleware::auth();
    return (new ReportController())->index((int) $id);
});

// Report settimanali
Router::get('/seo-tracking/projects/{id}/reports/weekly', function ($id) {
    Middleware::auth();
    return (new ReportController())->weekly((int) $id);
});

// Report mensili
Router::get('/seo-tracking/projects/{id}/reports/monthly', function ($id) {
    Middleware::auth();
    return (new ReportController())->monthly((int) $id);
});

// Genera Weekly Digest
Router::post('/seo-tracking/projects/{id}/reports/generate/weekly', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateWeekly((int) $id);
});

// Genera Monthly Executive
Router::post('/seo-tracking/projects/{id}/reports/generate/monthly', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateMonthly((int) $id);
});

// Genera Keyword Analysis
Router::post('/seo-tracking/projects/{id}/reports/generate/keywords', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateKeywordAnalysis((int) $id);
});

// Genera Revenue Attribution
Router::post('/seo-tracking/projects/{id}/reports/generate/revenue', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateRevenueAttribution((int) $id);
});

// Visualizza report
Router::get('/seo-tracking/projects/{id}/reports/{reportId}', function ($id, $reportId) {
    Middleware::auth();
    return (new ReportController())->show((int) $id, (int) $reportId);
});

// Download report PDF
Router::get('/seo-tracking/projects/{id}/reports/{reportId}/download', function ($id, $reportId) {
    Middleware::auth();
    return (new ReportController())->download((int) $id, (int) $reportId);
});

// Elimina report
Router::post('/seo-tracking/projects/{id}/reports/{reportId}/delete', function ($id, $reportId) {
    Middleware::auth();
    Middleware::csrf();
    return (new ReportController())->destroy((int) $id, (int) $reportId);
});

// =============================================
// EXPORT
// =============================================

Router::get('/seo-tracking/projects/{id}/export/keywords', function ($id) {
    Middleware::auth();
    return (new ExportController())->keywords((int) $id);
});

Router::get('/seo-tracking/projects/{id}/export/positions', function ($id) {
    Middleware::auth();
    return (new ExportController())->positions((int) $id);
});

Router::get('/seo-tracking/projects/{id}/export/revenue', function ($id) {
    Middleware::auth();
    return (new ExportController())->revenue((int) $id);
});

// =============================================
// API AJAX (per grafici e aggiornamenti)
// =============================================

Router::get('/seo-tracking/api/projects/{id}/chart/traffic', function ($id) {
    Middleware::auth();
    return (new ApiController())->trafficChart((int) $id);
});

Router::get('/seo-tracking/api/projects/{id}/chart/revenue', function ($id) {
    Middleware::auth();
    return (new ApiController())->revenueChart((int) $id);
});

Router::get('/seo-tracking/api/projects/{id}/chart/positions', function ($id) {
    Middleware::auth();
    return (new ApiController())->positionsChart((int) $id);
});

Router::get('/seo-tracking/api/projects/{id}/sync-status', function ($id) {
    Middleware::auth();
    return (new ApiController())->syncStatus((int) $id);
});

// Stats per dashboard
Router::get('/seo-tracking/api/projects/{id}/stats', function ($id) {
    Middleware::auth();
    return (new ApiController())->stats((int) $id);
});

// Top keywords
Router::get('/seo-tracking/api/projects/{id}/top-keywords', function ($id) {
    Middleware::auth();
    return (new ApiController())->topKeywords((int) $id);
});

// Recent alerts
Router::get('/seo-tracking/api/projects/{id}/recent-alerts', function ($id) {
    Middleware::auth();
    return (new ApiController())->recentAlerts((int) $id);
});
