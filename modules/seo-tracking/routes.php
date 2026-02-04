<?php

/**
 * SEO Position Tracking Module - Routes
 *
 * Pattern: /seo-tracking/project/{id}/...
 * Allineato con seo-audit e internal-links
 */

use Core\Router;
use Core\Middleware;

// Controllers
use Modules\SeoTracking\Controllers\ProjectController;
use Modules\SeoTracking\Controllers\DashboardController;
use Modules\SeoTracking\Controllers\KeywordController;
use Modules\SeoTracking\Controllers\GroupController;
use Modules\SeoTracking\Controllers\GscController;
use Modules\SeoTracking\Controllers\AlertController;
use Modules\SeoTracking\Controllers\ReportController;
use Modules\SeoTracking\Controllers\AiController;
use Modules\SeoTracking\Controllers\ExportController;
use Modules\SeoTracking\Controllers\ApiController;
use Modules\SeoTracking\Controllers\CronController;
use Modules\SeoTracking\Controllers\CompareController;
use Modules\SeoTracking\Controllers\RankCheckController;
use Modules\SeoTracking\Controllers\UrlController;

// =============================================
// PROGETTI
// =============================================

// Lista progetti (home modulo)
Router::get('/seo-tracking', function () {
    Middleware::auth();
    return (new ProjectController())->index();
});

// Crea progetto
Router::get('/seo-tracking/project/create', function () {
    Middleware::auth();
    return (new ProjectController())->create();
});

Router::post('/seo-tracking/project/store', function () {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->store();
});

// Dashboard progetto (redirect a /seo-tracking/project/{id}/dashboard)
Router::get('/seo-tracking/project/{id}', function ($id) {
    Middleware::auth();
    return (new DashboardController())->index((int) $id);
});

// Impostazioni progetto
Router::get('/seo-tracking/project/{id}/settings', function ($id) {
    Middleware::auth();
    return (new ProjectController())->settings((int) $id);
});

Router::post('/seo-tracking/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->updateSettings((int) $id);
});

// Elimina progetto
Router::post('/seo-tracking/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->destroy((int) $id);
});

// Stop sync manuale
Router::post('/seo-tracking/project/{id}/sync/stop', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProjectController())->stopSync((int) $id);
});

// =============================================
// GOOGLE SEARCH CONSOLE
// =============================================

// Avvia connessione OAuth
Router::get('/seo-tracking/project/{id}/gsc/connect', function ($id) {
    Middleware::auth();
    return (new GscController())->connect((int) $id);
});

// Callback OAuth centralizzato - riceve redirect da /oauth/google/callback
Router::get('/seo-tracking/gsc/connected', function () {
    Middleware::auth();
    return (new GscController())->connected();
});

// Lista proprietà GSC disponibili
Router::get('/seo-tracking/project/{id}/gsc/properties', function ($id) {
    Middleware::auth();
    return (new GscController())->properties((int) $id);
});

// Pagina selezione proprietà GSC (GET)
Router::get('/seo-tracking/project/{id}/gsc/select-property', function ($id) {
    Middleware::auth();
    return (new GscController())->selectProperty((int) $id);
});

// Salva proprietà GSC selezionata (POST)
Router::post('/seo-tracking/project/{id}/gsc/select-property', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->saveProperty((int) $id);
});

// Sync giornaliero manuale
Router::post('/seo-tracking/project/{id}/gsc/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->sync((int) $id);
});

// Sync storico completo (16 mesi)
Router::post('/seo-tracking/project/{id}/gsc/sync-full', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->fullSync((int) $id);
});

// Sync GSC con progress SSE (batch mensili - no timeout)
// NOTA: Auth gestito internamente nel controller per evitare 302 redirect su SSE
Router::get('/seo-tracking/project/{id}/gsc/sync-progress', function ($id) {
    return (new GscController())->syncWithProgress((int) $id);
});

// Disconnetti GSC
Router::post('/seo-tracking/project/{id}/gsc/disconnect', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GscController())->disconnect((int) $id);
});

// =============================================
// DASHBOARD E DATI
// =============================================

Router::get('/seo-tracking/project/{id}/dashboard', function ($id) {
    Middleware::auth();
    return (new DashboardController())->index((int) $id);
});

Router::get('/seo-tracking/project/{id}/keywords-overview', function ($id) {
    Middleware::auth();
    return (new DashboardController())->keywords((int) $id);
});

// Delete singola keyword (AJAX)
Router::post('/seo-tracking/project/{id}/keywords/delete/{keywordId}', function ($id, $keywordId) {
    Middleware::auth();
    return (new DashboardController())->deleteKeyword((int) $id, (int) $keywordId);
});

// Bulk delete keywords (AJAX)
Router::post('/seo-tracking/project/{id}/keywords/bulk-delete', function ($id) {
    Middleware::auth();
    return (new DashboardController())->bulkDeleteKeywords((int) $id);
});

// =============================================
// TREND (ex POSITION COMPARE)
// =============================================

// Vista confronto posizioni
Router::get('/seo-tracking/project/{id}/trend', function ($id) {
    Middleware::auth();
    return (new CompareController())->index((int) $id);
});

// API dati confronto (AJAX)
Router::post('/seo-tracking/project/{id}/trend/data', function ($id) {
    Middleware::auth();
    return (new CompareController())->getData((int) $id);
});

// Export CSV confronto
Router::get('/seo-tracking/project/{id}/trend/export', function ($id) {
    Middleware::auth();
    return (new CompareController())->export((int) $id);
});

// =============================================
// RANK CHECKER (SERP Position Check)
// =============================================

// Vista principale rank checker
Router::get('/seo-tracking/project/{id}/rank-check', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->index((int) $id);
});

// Check singolo (AJAX)
Router::post('/seo-tracking/project/{id}/rank-check', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new RankCheckController())->check((int) $id);
});

// Check bulk (AJAX)
Router::post('/seo-tracking/project/{id}/rank-check/bulk', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new RankCheckController())->checkBulk((int) $id);
});

// Storico completo
Router::get('/seo-tracking/project/{id}/rank-check/history', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->history((int) $id);
});

// API: Carica keyword da GSC
Router::get('/seo-tracking/project/{id}/rank-check/gsc-keywords', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->getGscKeywords((int) $id);
});

// API: Check singolo keyword (per bulk sequenziale)
Router::post('/seo-tracking/project/{id}/rank-check/single', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->checkSingle((int) $id);
});

// API: Import keyword manuali (textarea o CSV)
Router::post('/seo-tracking/project/{id}/rank-check/import-keywords', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->importKeywords((int) $id);
});

// =============================================
// RANK CHECK - BACKGROUND JOB PROCESSING
// =============================================

// Avvia job in background
Router::post('/seo-tracking/project/{id}/rank-check/start-job', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->startJob((int) $id);
});

// SSE Stream per progress in tempo reale (NO auth middleware - gestito internamente)
Router::get('/seo-tracking/project/{id}/rank-check/stream', function ($id) {
    return (new RankCheckController())->processStream((int) $id);
});

// Polling fallback per status job
Router::get('/seo-tracking/project/{id}/rank-check/job-status', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->jobStatus((int) $id);
});

// Annulla job in esecuzione
Router::post('/seo-tracking/project/{id}/rank-check/cancel-job', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->cancelJob((int) $id);
});

// Lista keyword tracciate per job
Router::get('/seo-tracking/project/{id}/rank-check/tracked-keywords', function ($id) {
    Middleware::auth();
    return (new RankCheckController())->getTrackedKeywords((int) $id);
});

// =============================================
// KEYWORD TRACKING
// =============================================

// Lista keyword tracciate
Router::get('/seo-tracking/project/{id}/keywords', function ($id) {
    Middleware::auth();
    return (new KeywordController())->index((int) $id);
});

// Tutte le keyword (da GSC)
Router::get('/seo-tracking/project/{id}/keywords/all', function ($id) {
    Middleware::auth();
    return (new KeywordController())->all((int) $id);
});

// Form aggiungi keyword
Router::get('/seo-tracking/project/{id}/keywords/add', function ($id) {
    Middleware::auth();
    return (new KeywordController())->add((int) $id);
});

// Salva keyword
Router::post('/seo-tracking/project/{id}/keywords/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->store((int) $id);
});

// Import keyword da CSV
Router::post('/seo-tracking/project/{id}/keywords/import', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->import((int) $id);
});

// Azioni bulk su keyword
Router::post('/seo-tracking/project/{id}/keywords/bulk', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->bulkAction((int) $id);
});

// =============================================
// KEYWORDS - BACKGROUND JOB PROCESSING
// (MUST be BEFORE {keywordId} wildcard route!)
// =============================================

// Avvia job posizioni in background
Router::post('/seo-tracking/project/{id}/keywords/start-positions-job', function ($id) {
    Middleware::auth();
    return (new KeywordController())->startPositionsJob((int) $id);
});

// SSE Stream per progress posizioni (NO auth middleware - gestito internamente)
Router::get('/seo-tracking/project/{id}/keywords/positions-stream', function ($id) {
    return (new KeywordController())->processPositionsStream((int) $id);
});

// Polling fallback per status job posizioni
Router::get('/seo-tracking/project/{id}/keywords/positions-job-status', function ($id) {
    Middleware::auth();
    return (new KeywordController())->positionsJobStatus((int) $id);
});

// Annulla job posizioni in esecuzione
Router::post('/seo-tracking/project/{id}/keywords/cancel-positions-job', function ($id) {
    Middleware::auth();
    return (new KeywordController())->cancelPositionsJob((int) $id);
});

// =============================================
// KEYWORD DETAIL - WILDCARD ROUTES (must be LAST)
// =============================================

// Dettaglio keyword
Router::get('/seo-tracking/project/{id}/keywords/{keywordId}', function ($id, $keywordId) {
    Middleware::auth();
    return (new KeywordController())->detail((int) $id, (int) $keywordId);
});

// Aggiorna keyword
Router::post('/seo-tracking/project/{id}/keywords/{keywordId}/update', function ($id, $keywordId) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->update((int) $id, (int) $keywordId);
});

// Elimina keyword
Router::post('/seo-tracking/project/{id}/keywords/{keywordId}/delete', function ($id, $keywordId) {
    Middleware::auth();
    Middleware::csrf();
    return (new KeywordController())->destroy((int) $id, (int) $keywordId);
});

// Aggiorna volumi di ricerca (AJAX - DataForSEO) - DEPRECATO, usa refresh-volumes
Router::post('/seo-tracking/project/{id}/keywords/update-volumes', function ($id) {
    Middleware::auth();
    return (new KeywordController())->updateVolumes((int) $id);
});

// Verifica configurazione DataForSEO
Router::get('/seo-tracking/project/{id}/keywords/check-volume-service', function ($id) {
    Middleware::auth();
    return (new KeywordController())->checkVolumeService((int) $id);
});

// =============================================
// REFRESH DATI KEYWORD (con crediti)
// =============================================

// Refresh volumi (DataForSEO) - con consumo crediti
Router::post('/seo-tracking/project/{id}/keywords/refresh-volumes', function ($id) {
    Middleware::auth();
    return (new KeywordController())->refreshVolumes((int) $id);
});

// Refresh posizioni SERP - con consumo crediti
Router::post('/seo-tracking/project/{id}/keywords/refresh-positions', function ($id) {
    Middleware::auth();
    return (new KeywordController())->refreshPositions((int) $id);
});

// Refresh completo (volumi + posizioni) - con consumo crediti
Router::post('/seo-tracking/project/{id}/keywords/refresh-all', function ($id) {
    Middleware::auth();
    return (new KeywordController())->refreshAll((int) $id);
});

// Calcola costo refresh (AJAX - per preview)
Router::get('/seo-tracking/project/{id}/keywords/refresh-cost', function ($id) {
    Middleware::auth();
    return (new KeywordController())->getRefreshCost((int) $id);
});

// Debug SERP check (temporaneo per diagnostica)
Router::get('/seo-tracking/project/{id}/debug-serp', function ($id) {
    Middleware::auth();

    $user = \Core\Auth::user();
    $project = (new \Modules\SeoTracking\Models\Project())->find((int) $id, $user['id']);

    if (!$project) {
        return \Core\View::json(['error' => 'Progetto non trovato'], 404);
    }

    $keyword = $_GET['keyword'] ?? 'test';
    $domain = $project['domain'];

    $rankChecker = new \Modules\SeoTracking\Services\RankCheckerService();
    $result = $rankChecker->debugSearch($keyword, $domain);

    return \Core\View::json($result);
});

// =============================================
// KEYWORD GROUPS
// =============================================

// Lista gruppi
Router::get('/seo-tracking/project/{id}/groups', function ($id) {
    Middleware::auth();
    return (new GroupController())->index((int) $id);
});

// Form crea gruppo
Router::get('/seo-tracking/project/{id}/groups/create', function ($id) {
    Middleware::auth();
    return (new GroupController())->create((int) $id);
});

// Salva gruppo
Router::post('/seo-tracking/project/{id}/groups/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new GroupController())->store((int) $id);
});

// Dettaglio gruppo
Router::get('/seo-tracking/project/{id}/groups/{groupId}', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->show((int) $id, (int) $groupId);
});

// Form modifica gruppo
Router::get('/seo-tracking/project/{id}/groups/{groupId}/edit', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->edit((int) $id, (int) $groupId);
});

// Aggiorna gruppo
Router::post('/seo-tracking/project/{id}/groups/{groupId}/update', function ($id, $groupId) {
    Middleware::auth();
    Middleware::csrf();
    return (new GroupController())->update((int) $id, (int) $groupId);
});

// Elimina gruppo
Router::post('/seo-tracking/project/{id}/groups/{groupId}/delete', function ($id, $groupId) {
    Middleware::auth();
    Middleware::csrf();
    return (new GroupController())->destroy((int) $id, (int) $groupId);
});

// API: Aggiungi keyword a gruppo
Router::post('/seo-tracking/project/{id}/groups/{groupId}/add-keyword', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->addKeyword((int) $id, (int) $groupId);
});

// API: Rimuovi keyword da gruppo
Router::post('/seo-tracking/project/{id}/groups/{groupId}/remove-keyword', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->removeKeyword((int) $id, (int) $groupId);
});

// API: Sincronizza gruppi da st_keywords.group_name
Router::post('/seo-tracking/project/{id}/groups/sync-from-keywords', function ($id) {
    Middleware::auth();
    return (new GroupController())->syncFromKeywords((int) $id);
});

// API: Dati grafico trend gruppo
Router::get('/seo-tracking/api/project/{id}/groups/{groupId}/chart', function ($id, $groupId) {
    Middleware::auth();
    return (new GroupController())->trendChart((int) $id, (int) $groupId);
});

// =============================================
// URL RANKING (Raggruppamento per URL)
// =============================================

// Lista URLs con keyword raggruppate
Router::get('/seo-tracking/project/{id}/urls', function ($id) {
    Middleware::auth();
    return (new UrlController())->index((int) $id);
});

// =============================================
// ALERT
// =============================================

// Lista alert attivi
Router::get('/seo-tracking/project/{id}/alerts', function ($id) {
    Middleware::auth();
    return (new AlertController())->index((int) $id);
});

// Impostazioni alert
Router::get('/seo-tracking/project/{id}/alerts/settings', function ($id) {
    Middleware::auth();
    return (new AlertController())->settings((int) $id);
});

Router::post('/seo-tracking/project/{id}/alerts/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AlertController())->updateSettings((int) $id);
});

// Storico alert
Router::get('/seo-tracking/project/{id}/alerts/history', function ($id) {
    Middleware::auth();
    return (new AlertController())->history((int) $id);
});

// Marca alert come letto
Router::post('/seo-tracking/project/{id}/alerts/{alertId}/read', function ($id, $alertId) {
    Middleware::auth();
    Middleware::csrf();
    return (new AlertController())->markRead((int) $id, (int) $alertId);
});

// Archivia/Elimina alert
Router::post('/seo-tracking/project/{id}/alerts/{alertId}/dismiss', function ($id, $alertId) {
    Middleware::auth();
    Middleware::csrf();
    return (new AlertController())->archive((int) $alertId);
});

// =============================================
// QUICK WINS AI
// =============================================

// Quick Wins (progetto)
Router::get('/seo-tracking/project/{id}/quick-wins', function ($id) {
    Middleware::auth();
    return (new AiController())->quickWins((int) $id);
});

Router::post('/seo-tracking/project/{id}/quick-wins/analyze', function ($id) {
    Middleware::auth();
    return (new AiController())->analyzeQuickWins((int) $id);
});

// Quick Wins (gruppo)
Router::get('/seo-tracking/project/{id}/groups/{groupId}/quick-wins', function ($id, $groupId) {
    Middleware::auth();
    return (new AiController())->quickWinsGroup((int) $id, (int) $groupId);
});

Router::post('/seo-tracking/project/{id}/groups/{groupId}/quick-wins/analyze', function ($id, $groupId) {
    Middleware::auth();
    return (new AiController())->analyzeQuickWinsGroup((int) $id, (int) $groupId);
});

// =============================================
// SEO PAGE ANALYZER
// =============================================

// Pagina dedicata Page Analyzer
Router::get('/seo-tracking/project/{id}/page-analyzer', function ($id) {
    Middleware::auth();
    return (new AiController())->pageAnalyzerView((int) $id);
});

// Analizza pagina per keyword (POST AJAX)
Router::post('/seo-tracking/project/{id}/analyze-page', function ($id) {
    Middleware::auth();
    return (new AiController())->analyzePage((int) $id);
});

// Ottieni analisi per ID (GET AJAX)
Router::get('/seo-tracking/project/{id}/page-analysis/{analysisId}', function ($id, $analysisId) {
    Middleware::auth();
    return (new AiController())->getPageAnalysis((int) $id, (int) $analysisId);
});

// Lista analisi recenti (GET AJAX)
Router::get('/seo-tracking/project/{id}/page-analyses', function ($id) {
    Middleware::auth();
    return (new AiController())->listPageAnalyses((int) $id);
});

// Costo analisi (GET AJAX)
Router::get('/seo-tracking/project/{id}/analyze-page/cost', function ($id) {
    Middleware::auth();
    return (new AiController())->getPageAnalysisCost((int) $id);
});

// =============================================
// REPORT AI
// =============================================

// Lista report
Router::get('/seo-tracking/project/{id}/reports', function ($id) {
    Middleware::auth();
    return (new ReportController())->index((int) $id);
});

// Report settimanali
Router::get('/seo-tracking/project/{id}/reports/weekly', function ($id) {
    Middleware::auth();
    return (new ReportController())->weekly((int) $id);
});

// Report mensili
Router::get('/seo-tracking/project/{id}/reports/monthly', function ($id) {
    Middleware::auth();
    return (new ReportController())->monthly((int) $id);
});

// Genera Weekly Digest
Router::post('/seo-tracking/project/{id}/reports/generate/weekly', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateWeekly((int) $id);
});

// Genera Monthly Executive
Router::post('/seo-tracking/project/{id}/reports/generate/monthly', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateMonthly((int) $id);
});

// Genera Keyword Analysis
Router::post('/seo-tracking/project/{id}/reports/generate/keywords', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new AiController())->generateKeywordAnalysis((int) $id);
});

// Visualizza report
Router::get('/seo-tracking/project/{id}/reports/{reportId}', function ($id, $reportId) {
    Middleware::auth();
    return (new ReportController())->show((int) $id, (int) $reportId);
});

// Download report PDF
Router::get('/seo-tracking/project/{id}/reports/{reportId}/download', function ($id, $reportId) {
    Middleware::auth();
    return (new ReportController())->download((int) $id, (int) $reportId);
});

// Elimina report
Router::post('/seo-tracking/project/{id}/reports/{reportId}/delete', function ($id, $reportId) {
    Middleware::auth();
    Middleware::csrf();
    return (new ReportController())->destroy((int) $id, (int) $reportId);
});

// =============================================
// EXPORT
// =============================================

Router::get('/seo-tracking/project/{id}/export/keywords', function ($id) {
    Middleware::auth();
    return (new ExportController())->keywords((int) $id);
});

Router::get('/seo-tracking/project/{id}/export/positions', function ($id) {
    Middleware::auth();
    return (new ExportController())->positions((int) $id);
});

// =============================================
// API AJAX (per grafici e aggiornamenti)
// =============================================

Router::get('/seo-tracking/api/project/{id}/chart/traffic', function ($id) {
    Middleware::auth();
    return (new ApiController())->trafficChart((int) $id);
});

Router::get('/seo-tracking/api/project/{id}/chart/positions', function ($id) {
    Middleware::auth();
    return (new ApiController())->positionsChart((int) $id);
});

Router::get('/seo-tracking/api/project/{id}/sync-status', function ($id) {
    Middleware::auth();
    return (new ApiController())->syncStatus((int) $id);
});

// Stats per dashboard
Router::get('/seo-tracking/api/project/{id}/stats', function ($id) {
    Middleware::auth();
    return (new ApiController())->stats((int) $id);
});

// Top keywords
Router::get('/seo-tracking/api/project/{id}/top-keywords', function ($id) {
    Middleware::auth();
    return (new ApiController())->topKeywords((int) $id);
});

// Recent alerts
Router::get('/seo-tracking/api/project/{id}/recent-alerts', function ($id) {
    Middleware::auth();
    return (new ApiController())->recentAlerts((int) $id);
});

// Tracked keywords (per rank-check)
Router::get('/seo-tracking/project/{id}/api/tracked-keywords', function ($id) {
    Middleware::auth();
    return (new ApiController())->trackedKeywords((int) $id);
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

