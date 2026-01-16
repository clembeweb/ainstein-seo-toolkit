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
use Modules\SeoTracking\Controllers\GroupController;
use Modules\SeoTracking\Controllers\GscController;
use Modules\SeoTracking\Controllers\Ga4Controller;
use Modules\SeoTracking\Controllers\AlertController;
use Modules\SeoTracking\Controllers\ReportController;
use Modules\SeoTracking\Controllers\AiController;
use Modules\SeoTracking\Controllers\ExportController;
use Modules\SeoTracking\Controllers\ApiController;
use Modules\SeoTracking\Controllers\CronController;

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

// Stop sync manuale
Router::post('/seo-tracking/projects/{id}/sync/stop', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->stopSync((int) $id);
});

// =============================================
// GOOGLE SEARCH CONSOLE
// =============================================

// Avvia connessione OAuth
Router::get('/seo-tracking/projects/{id}/gsc/connect', function ($id) {
    Middleware::auth();
    return (new GscController())->connect((int) $id);
});

// Callback OAuth centralizzato - riceve redirect da /oauth/google/callback
Router::get('/seo-tracking/gsc/connected', function () {
    Middleware::auth();
    return (new GscController())->connected();
});

// Lista proprietà GSC disponibili
Router::get('/seo-tracking/projects/{id}/gsc/properties', function ($id) {
    Middleware::auth();
    return (new GscController())->properties((int) $id);
});

// Pagina selezione proprietà GSC (GET)
Router::get('/seo-tracking/projects/{id}/gsc/select-property', function ($id) {
    Middleware::auth();
    return (new GscController())->selectProperty((int) $id);
});

// Salva proprietà GSC selezionata (POST)
Router::post('/seo-tracking/projects/{id}/gsc/select-property', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->saveProperty((int) $id);
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
    return (new GscController())->fullSync((int) $id);
});

// Sync GSC con progress SSE (batch mensili - no timeout)
// NOTA: Auth gestito internamente nel controller per evitare 302 redirect su SSE
Router::get('/seo-tracking/projects/{id}/gsc/sync-progress', function ($id) {
    return (new GscController())->syncWithProgress((int) $id);
});

// Disconnetti GSC
Router::post('/seo-tracking/projects/{id}/gsc/disconnect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->disconnect((int) $id);
});

// =============================================
// GOOGLE ANALYTICS 4 (OAuth)
// =============================================

// Avvia connessione OAuth GA4
Router::get('/seo-tracking/projects/{id}/ga4/connect', function ($id) {
    Middleware::auth();
    return (new Ga4Controller())->connect((int) $id);
});

// Callback OAuth centralizzato - riceve redirect da /oauth/google/callback
Router::get('/seo-tracking/ga4/connected', function () {
    Middleware::auth();
    return (new Ga4Controller())->connected();
});

// Pagina selezione property GA4 (GET)
Router::get('/seo-tracking/projects/{id}/ga4/select-property', function ($id) {
    Middleware::auth();
    return (new Ga4Controller())->selectProperty((int) $id);
});

// Salva property GA4 selezionata (POST)
Router::post('/seo-tracking/projects/{id}/ga4/select-property', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->saveProperty((int) $id);
});

// Sync manuale GA4
Router::post('/seo-tracking/projects/{id}/ga4/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->sync((int) $id);
});

// Sync GA4 con progress SSE (batch mensili - no timeout)
// NOTA: Auth gestito internamente nel controller per evitare 302 redirect su SSE
Router::get('/seo-tracking/projects/{id}/ga4/sync-progress', function ($id) {
    return (new Ga4Controller())->syncWithProgress((int) $id);
});

// Disconnetti GA4
Router::post('/seo-tracking/projects/{id}/ga4/disconnect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new Ga4Controller())->disconnect((int) $id);
});

// Test connessione GA4 (API)
Router::get('/seo-tracking/projects/{id}/ga4/test', function ($id) {
    Middleware::auth();
    return (new Ga4Controller())->test((int) $id);
});

// Status connessione GA4 (API)
Router::get('/seo-tracking/projects/{id}/ga4/status', function ($id) {
    Middleware::auth();
    return (new Ga4Controller())->status((int) $id);
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

// Delete singola keyword (AJAX)
Router::post('/seo-tracking/projects/{id}/keywords/delete/{keywordId}', function ($id, $keywordId) {
    Middleware::auth();
    return (new DashboardController())->deleteKeyword((int) $id, (int) $keywordId);
});

// Bulk delete keywords (AJAX)
Router::post('/seo-tracking/projects/{id}/keywords/bulk-delete', function ($id) {
    Middleware::auth();
    return (new DashboardController())->bulkDeleteKeywords((int) $id);
});

Router::get('/seo-tracking/projects/{id}/pages', function ($id) {
    Middleware::auth();
    return (new DashboardController())->pages((int) $id);
});

// Delete singola pagina (AJAX)
Router::post('/seo-tracking/projects/{id}/pages/delete/{pageId}', function ($id, $pageId) {
    Middleware::auth();
    return (new DashboardController())->deletePage((int) $id, (int) $pageId);
});

// Bulk delete pagine (AJAX)
Router::post('/seo-tracking/projects/{id}/pages/bulk-delete', function ($id) {
    Middleware::auth();
    return (new DashboardController())->bulkDeletePages((int) $id);
});

// Revenue dashboard rimosso - funzionalità non attiva
// Router::get('/seo-tracking/projects/{id}/revenue', function ($id) {
//     Middleware::auth();
//     return (new DashboardController())->revenue((int) $id);
// });

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
// KEYWORD GROUPS
// =============================================

// Lista gruppi
Router::get('/seo-tracking/projects/{id}/groups', function ($id) {
    Middleware::auth();
    return (new GroupController())->index((int) $id);
});

// Form crea gruppo
Router::get('/seo-tracking/projects/{id}/groups/create', function ($id) {
    Middleware::auth();
    return (new GroupController())->create((int) $id);
});

// Salva gruppo
Router::post('/seo-tracking/projects/{id}/groups/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GroupController())->store((int) $id);
});

// Dettaglio gruppo
Router::get('/seo-tracking/projects/{id}/groups/{groupId}', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->show((int) $id, (int) $groupId);
});

// Form modifica gruppo
Router::get('/seo-tracking/projects/{id}/groups/{groupId}/edit', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->edit((int) $id, (int) $groupId);
});

// Aggiorna gruppo
Router::post('/seo-tracking/projects/{id}/groups/{groupId}/update', function ($id, $groupId) {
    Middleware::auth();
    Middleware::csrf();
    return (new GroupController())->update((int) $id, (int) $groupId);
});

// Elimina gruppo
Router::post('/seo-tracking/projects/{id}/groups/{groupId}/delete', function ($id, $groupId) {
    Middleware::auth();
    Middleware::csrf();
    return (new GroupController())->destroy((int) $id, (int) $groupId);
});

// API: Aggiungi keyword a gruppo
Router::post('/seo-tracking/projects/{id}/groups/{groupId}/add-keyword', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->addKeyword((int) $id, (int) $groupId);
});

// API: Rimuovi keyword da gruppo
Router::post('/seo-tracking/projects/{id}/groups/{groupId}/remove-keyword', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->removeKeyword((int) $id, (int) $groupId);
});

// API: Dati grafico trend gruppo
Router::get('/seo-tracking/api/projects/{id}/groups/{groupId}/chart', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->trendChart((int) $id, (int) $groupId);
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
// QUICK WINS AI
// =============================================

// Quick Wins (progetto)
Router::get('/seo-tracking/projects/{id}/ai/quick-wins', function ($id) {
    Middleware::auth();
    return (new AiController())->quickWins((int) $id);
});

Router::post('/seo-tracking/projects/{id}/ai/quick-wins/analyze', function ($id) {
    Middleware::auth();
    return (new AiController())->analyzeQuickWins((int) $id);
});

// Quick Wins (gruppo)
Router::get('/seo-tracking/projects/{id}/groups/{groupId}/ai/quick-wins', function ($id, $groupId) {
    Middleware::auth();
    return (new AiController())->quickWinsGroup((int) $id, (int) $groupId);
});

Router::post('/seo-tracking/projects/{id}/groups/{groupId}/ai/quick-wins/analyze', function ($id, $groupId) {
    Middleware::auth();
    return (new AiController())->analyzeQuickWinsGroup((int) $id, (int) $groupId);
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

// =============================================
// CRON (Sync automatici - NO AUTH)
// =============================================

// Sync giornaliero automatico
// URL: /seo-tracking/cron/daily-sync?secret=CRON_SECRET
Router::get('/seo-tracking/cron/daily-sync', function () {
    return (new CronController())->dailySync();
});

// Status cron (per monitoring)
Router::get('/seo-tracking/cron/status', function () {
    return (new CronController())->status();
});

// Sync singolo progetto (per test/debug)
Router::get('/seo-tracking/cron/sync-project', function () {
    return (new CronController())->syncProject();
});
