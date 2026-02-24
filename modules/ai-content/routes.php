<?php

/**
 * AI SEO Content Generator - Routes
 *
 * Modulo per generazione articoli SEO con AI
 */

use Core\Router;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\ModuleLoader;
use Modules\AiContent\Controllers\ProjectController;
use Modules\AiContent\Controllers\DashboardController;
use Modules\AiContent\Controllers\KeywordController;
use Modules\AiContent\Controllers\SerpController;
use Modules\AiContent\Controllers\ArticleController;
use Modules\AiContent\Controllers\WordPressController;
use Modules\AiContent\Controllers\WizardController;
use Modules\AiContent\Controllers\AutoController;
use Modules\AiContent\Controllers\InternalLinksController;
use Modules\AiContent\Controllers\MetaTagController;

$moduleSlug = 'ai-content';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECT ROUTES
// ============================================

// Entry dashboard con 3 mode cards (homepage modulo)
Router::get('/ai-content', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->entryDashboard();
});

// Lista progetti con tabs (Manual, Auto, Meta Tags)
Router::get('/ai-content/projects', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Form nuovo progetto
Router::get('/ai-content/projects/create', function () {
    \Core\Router::redirect('/projects/create');
});

// Salva nuovo progetto
Router::post('/ai-content/projects', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->store();
});

// Dashboard progetto - distingue AUTO vs MANUAL
Router::get('/ai-content/projects/{id}', function ($id) {
    Middleware::auth();
    $project = (new \Modules\AiContent\Models\Project())->findAccessible((int) $id, Auth::user()['id']);

    if (!$project) {
        $_SESSION['_flash']['error'] = 'Progetto non trovato';
        Router::redirect('/ai-content');
        return;
    }

    // Se progetto AUTO, redirect alla dashboard auto
    if ($project['type'] === 'auto') {
        Router::redirect('/ai-content/projects/' . $id . '/auto');
        return;
    }

    // Se progetto META-TAG, redirect alla dashboard meta-tags
    if ($project['type'] === 'meta-tag') {
        Router::redirect('/ai-content/projects/' . $id . '/meta-tags');
        return;
    }

    // Progetto MANUAL → DashboardController
    $controller = new DashboardController();
    return $controller->index((int) $id);
});

// Impostazioni progetto
Router::get('/ai-content/projects/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->settings((int) $id);
});

// Aggiorna progetto
Router::post('/ai-content/projects/{id}/update', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->update((int) $id);
});

// Elimina progetto
Router::post('/ai-content/projects/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->destroy((int) $id);
});

// ============================================
// DASHBOARD ROUTES (retrocompatibilita)
// ============================================

// Dashboard senza progetto (mostra tutto - retrocompatibilita)
Router::get('/ai-content/dashboard', function () {
    Middleware::auth();
    $controller = new DashboardController();
    return $controller->index();
});

// ============================================
// KEYWORDS ROUTES (dentro progetto)
// ============================================

// Lista keywords progetto
Router::get('/ai-content/projects/{id}/keywords', function ($id) {
    Middleware::auth();
    $controller = new KeywordController();
    return $controller->index((int) $id);
});

// Salva nuova keyword in progetto
Router::post('/ai-content/projects/{id}/keywords', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new KeywordController();
    return $controller->store((int) $id);
});

// Elimina keyword
Router::post('/ai-content/projects/{id}/keywords/{kwId}/delete', function ($id, $kwId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new KeywordController();
    return $controller->delete((int) $kwId, (int) $id);
});

Router::post('/ai-content/projects/{id}/keywords/bulk-delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new KeywordController();
    return $controller->bulkDelete((int) $id);
});

// Wizard per generazione articolo
Router::get('/ai-content/projects/{id}/keywords/{kwId}/wizard', function ($id, $kwId) {
    Middleware::auth();
    $controller = new KeywordController();
    return $controller->wizard((int) $kwId, (int) $id);
});

// ============================================
// KEYWORDS ROUTES (legacy - redirect)
// ============================================

// Redirect vecchia route a nuova
Router::get('/ai-content/keywords', function () {
    Middleware::auth();
    $projectId = $_GET['project_id'] ?? null;
    if ($projectId) {
        Router::redirect('/ai-content/projects/' . (int) $projectId . '/keywords');
        return;
    }
    // Lista globale (fallback)
    $controller = new KeywordController();
    return $controller->index();
});

// Legacy POST redirect
Router::post('/ai-content/keywords', function () {
    Middleware::auth();
    Middleware::csrf();
    $projectId = $_POST['project_id'] ?? null;
    if ($projectId) {
        // Forward to new route handler
        $controller = new KeywordController();
        return $controller->store((int) $projectId);
    }
    $_SESSION['_flash']['error'] = 'Project ID richiesto';
    Router::redirect('/ai-content');
});

// Legacy delete
Router::post('/ai-content/keywords/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new KeywordController();
    return $controller->delete((int) $id);
});

// Legacy wizard
Router::get('/ai-content/keywords/{id}/wizard', function ($id) {
    Middleware::auth();
    $controller = new KeywordController();
    return $controller->wizard((int) $id);
});

// ============================================
// WIZARD ROUTES
// ============================================

// Generate brief (AJAX)
Router::post('/ai-content/wizard/{id}/brief', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WizardController();
    return $controller->generateBrief((int) $id);
});

// Generate article (AJAX)
Router::post('/ai-content/wizard/{id}/article', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WizardController();
    return $controller->generateArticle((int) $id);
});

// ============================================
// SERP ROUTES
// ============================================

// Estrai SERP per keyword (AJAX)
Router::post('/ai-content/keywords/{id}/serp', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new SerpController();
    return $controller->extract((int) $id);
});

// Visualizza risultati SERP
Router::get('/ai-content/keywords/{id}/serp', function ($id) {
    Middleware::auth();
    $controller = new SerpController();
    return $controller->show((int) $id);
});

// ============================================
// ARTICLES ROUTES (dentro progetto)
// ============================================

// Lista articoli progetto
Router::get('/ai-content/projects/{id}/articles', function ($id) {
    Middleware::auth();
    $controller = new ArticleController();
    return $controller->index((int) $id);
});

// Dettaglio articolo nel progetto
Router::get('/ai-content/projects/{id}/articles/{articleId}', function ($id, $articleId) {
    Middleware::auth();
    $controller = new ArticleController();
    return $controller->show((int) $articleId, (int) $id);
});

// ============================================
// ARTICLES ROUTES (globali/legacy)
// ============================================

// Lista articoli
Router::get('/ai-content/articles', function () {
    Middleware::auth();
    $controller = new ArticleController();
    return $controller->index();
});

// Visualizza articolo
Router::get('/ai-content/articles/{id}', function ($id) {
    Middleware::auth();
    $controller = new ArticleController();
    return $controller->show((int) $id);
});

// Genera articolo (AJAX)
Router::post('/ai-content/articles/generate', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->generate();
});

// Progress generazione (polling)
Router::get('/ai-content/articles/{id}/progress', function ($id) {
    Middleware::auth();
    $controller = new ArticleController();
    return $controller->progress((int) $id);
});

// Aggiorna articolo (form standard)
Router::post('/ai-content/articles/{id}', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->update((int) $id);
});

// Aggiorna articolo (alias per compatibilità)
Router::post('/ai-content/articles/{id}/update', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->update((int) $id);
});

// Elimina articolo
Router::post('/ai-content/articles/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->delete((int) $id);
});

// Rigenera articolo
Router::post('/ai-content/articles/{id}/regenerate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->regenerate((int) $id);
});

// Reset status articolo bloccato
Router::post('/ai-content/articles/{id}/reset', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->resetStatus((int) $id);
});

// Serve immagine di copertina articolo
Router::get('/ai-content/cover/{id}', function ($id) {
    Middleware::auth();
    $controller = new ArticleController();
    return $controller->serveCover((int) $id);
});

// Genera/rigenera immagine di copertina
Router::post('/ai-content/articles/{id}/regenerate-cover', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->regenerateCover((int) $id);
});

// Rimuovi immagine di copertina
Router::post('/ai-content/articles/{id}/remove-cover', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArticleController();
    return $controller->removeCover((int) $id);
});

// ============================================
// WORDPRESS ROUTES (dentro progetto)
// ============================================

// Lista siti WordPress del progetto
Router::get('/ai-content/projects/{id}/wordpress', function ($id) {
    Middleware::auth();
    $controller = new WordPressController();
    return $controller->index((int) $id);
});

// Aggiungi sito WordPress al progetto
Router::post('/ai-content/projects/{id}/wordpress/sites', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WordPressController();
    return $controller->addSite((int) $id);
});

// ============================================
// WORDPRESS ROUTES (globali/legacy)
// ============================================

// Lista siti WordPress → redirect a progetti (gestione WP centralizzata)
Router::get('/ai-content/wordpress', function () {
    Middleware::auth();
    $_SESSION['_flash']['info'] = 'I siti WordPress si gestiscono ora dalla dashboard progetto.';
    header('Location: ' . url('/projects'));
    exit;
});

// Aggiungi sito WordPress
Router::post('/ai-content/wordpress/sites', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WordPressController();
    return $controller->addSite();
});

// Rimuovi sito WordPress
Router::post('/ai-content/wordpress/sites/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WordPressController();
    return $controller->removeSite((int) $id);
});

// Sync categorie WordPress (AJAX)
Router::post('/ai-content/wordpress/sites/{id}/sync', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WordPressController();
    return $controller->syncCategories((int) $id);
});

// Get categorie WordPress (AJAX)
Router::get('/ai-content/wordpress/sites/{id}/categories', function ($id) {
    Middleware::auth();
    $controller = new WordPressController();
    return $controller->getCategories((int) $id);
});

// Pubblica articolo su WordPress
Router::post('/ai-content/articles/{id}/publish', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WordPressController();
    return $controller->publish((int) $id);
});

// Test connessione WordPress (AJAX)
Router::post('/ai-content/wordpress/sites/{id}/test', function ($id) {
    Middleware::auth();
    $controller = new WordPressController();
    return $controller->testConnection((int) $id);
});

// Toggle attivo sito WordPress
Router::post('/ai-content/wordpress/sites/{id}/toggle', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new WordPressController();
    return $controller->toggleActive((int) $id);
});

// Download plugin WordPress
Router::get('/ai-content/wordpress/download-plugin', function () {
    Middleware::auth();
    $controller = new WordPressController();
    return $controller->downloadPlugin();
});

// ============================================
// AUTO MODE ROUTES (progetti type='auto')
// ============================================

// Dashboard automazione
Router::get('/ai-content/projects/{id}/auto', function ($id) {
    Middleware::auth();
    $controller = new AutoController();
    return $controller->dashboard((int) $id);
});

// Form aggiunta keyword bulk
Router::get('/ai-content/projects/{id}/auto/add', function ($id) {
    Middleware::auth();
    $controller = new AutoController();
    return $controller->addKeywords((int) $id);
});

// Salva keyword in coda
Router::post('/ai-content/projects/{id}/auto/add', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->storeKeywords((int) $id);
});

// Lista coda
Router::get('/ai-content/projects/{id}/auto/queue', function ($id) {
    Middleware::auth();
    $controller = new AutoController();
    return $controller->queue((int) $id);
});

// Impostazioni automazione
Router::get('/ai-content/projects/{id}/auto/settings', function ($id) {
    Middleware::auth();
    $controller = new AutoController();
    return $controller->settings((int) $id);
});

// Salva impostazioni
Router::post('/ai-content/projects/{id}/auto/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->updateSettings((int) $id);
});

// Toggle attivo/disattivo
Router::post('/ai-content/projects/{id}/auto/toggle', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->toggle((int) $id);
});

// Elimina singolo item dalla coda
Router::post('/ai-content/projects/{id}/auto/queue/{queueId}/delete', function ($id, $queueId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->deleteQueueItem((int) $id, (int) $queueId);
});

// Retry item in errore
Router::post('/ai-content/projects/{id}/auto/queue/{queueId}/retry', function ($id, $queueId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->retryQueueItem((int) $id, (int) $queueId);
});

// AJAX: Update singolo item coda
Router::post('/ai-content/projects/{id}/auto/queue/{queueId}/update', function ($id, $queueId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\AiContent\Controllers\AutoController();
    return $controller->updateQueueItem((int) $id, (int) $queueId);
});

// Svuota coda pending
Router::post('/ai-content/projects/{id}/auto/queue/clear', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->clearQueue((int) $id);
});

// ============================================
// PROCESS CONTROL API ROUTES (JSON)
// ============================================

// Start manual processing
Router::post('/ai-content/projects/{id}/auto/process/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->startProcess((int) $id);
});

// Get process status (polling)
Router::get('/ai-content/projects/{id}/auto/process/status', function ($id) {
    Middleware::auth();
    $controller = new AutoController();
    return $controller->getProcessStatus((int) $id);
});

// Process stream (SSE) - for real-time processing
Router::get('/ai-content/projects/{id}/auto/process/stream', function ($id) {
    Middleware::auth();
    $controller = new AutoController();
    return $controller->processStream((int) $id);
});

// Cancel running process
Router::post('/ai-content/projects/{id}/auto/process/cancel', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->cancelProcess((int) $id);
});

// ============================================
// INTERNAL LINKS ROUTES
// ============================================

// Lista pool link interni
Router::get('/ai-content/projects/{id}/internal-links', function ($id) {
    Middleware::auth();
    $controller = new InternalLinksController();
    return $controller->index((int) $id);
});

// Wizard import
Router::get('/ai-content/projects/{id}/internal-links/import', function ($id) {
    Middleware::auth();
    $controller = new InternalLinksController();
    return $controller->import((int) $id);
});

// Discover sitemap (AJAX)
Router::post('/ai-content/projects/{id}/internal-links/discover', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->discover((int) $id);
});

// Preview URL dalle sitemap (AJAX)
Router::post('/ai-content/projects/{id}/internal-links/preview', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->preview((int) $id);
});

// Salva URL nel pool (AJAX)
Router::post('/ai-content/projects/{id}/internal-links/store', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->store((int) $id);
});

// Scrape batch (AJAX)
Router::post('/ai-content/projects/{id}/internal-links/scrape', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->scrape((int) $id);
});

// Edit singolo link
Router::get('/ai-content/projects/{id}/internal-links/{linkId}/edit', function ($id, $linkId) {
    Middleware::auth();
    $controller = new InternalLinksController();
    return $controller->edit((int) $id, (int) $linkId);
});

// Update singolo link
Router::post('/ai-content/projects/{id}/internal-links/{linkId}/update', function ($id, $linkId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->update((int) $id, (int) $linkId);
});

// Delete singolo link
Router::post('/ai-content/projects/{id}/internal-links/{linkId}/delete', function ($id, $linkId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->delete((int) $id, (int) $linkId);
});

// Toggle attivo (AJAX)
Router::post('/ai-content/projects/{id}/internal-links/{linkId}/toggle', function ($id, $linkId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->toggle((int) $id, (int) $linkId);
});

// Azioni bulk
Router::post('/ai-content/projects/{id}/internal-links/bulk', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->bulk((int) $id);
});

// Reset errori
Router::post('/ai-content/projects/{id}/internal-links/reset-errors', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->resetErrors((int) $id);
});

// Svuota pool
Router::post('/ai-content/projects/{id}/internal-links/clear', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->clear((int) $id);
});

// Store da CSV
Router::post('/ai-content/projects/{id}/internal-links/store-csv', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->storeCsv((int) $id);
});

// Store manuale
Router::post('/ai-content/projects/{id}/internal-links/store-manual', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new InternalLinksController();
    return $controller->storeManual((int) $id);
});

// ============================================
// META TAGS ROUTES (progetti type='meta-tag')
// ============================================

// Dashboard meta tags
Router::get('/ai-content/projects/{id}/meta-tags', function ($id) {
    Middleware::auth();
    $controller = new MetaTagController();
    return $controller->dashboard((int) $id);
});

// Lista meta tags con filtri
Router::get('/ai-content/projects/{id}/meta-tags/list', function ($id) {
    Middleware::auth();
    $controller = new MetaTagController();
    return $controller->list((int) $id);
});

// Wizard import
Router::get('/ai-content/projects/{id}/meta-tags/import', function ($id) {
    Middleware::auth();
    $controller = new MetaTagController();
    return $controller->import((int) $id);
});

// Import da WordPress (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/import/wp', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->storeFromWp((int) $id);
});

// Discover sitemap (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/discover', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->discover((int) $id);
});

// Import da sitemap (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/import/sitemap', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->storeFromSitemap((int) $id);
});

// Import da CSV (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/import/csv', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->storeFromCsv((int) $id);
});

// Import manuale (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/import/manual', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->storeManual((int) $id);
});

// Scrape batch (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/scrape', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->scrape((int) $id);
});

// Generate batch (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/generate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->generate((int) $id);
});

// Bulk approve (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/bulk-approve', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->bulkApprove((int) $id);
});

// Bulk publish (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/bulk-publish', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->bulkPublish((int) $id);
});

// Bulk delete (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/bulk-delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->bulkDelete((int) $id);
});

// ============================================
// META TAGS - BACKGROUND JOB PROCESSING (SSE)
// IMPORTANTE: Queste routes devono stare PRIMA di {tagId} wildcard!
// ============================================

// Avvia job di scraping in background
Router::post('/ai-content/projects/{id}/meta-tags/start-scrape-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->startScrapeJob((int) $id);
});

// SSE Stream per progress scraping real-time
Router::get('/ai-content/projects/{id}/meta-tags/scrape-stream', function ($id) {
    // Auth verificata nel controller per evitare redirect su SSE
    $controller = new MetaTagController();
    return $controller->scrapeStream((int) $id);
});

// Polling fallback per status job
Router::get('/ai-content/projects/{id}/meta-tags/scrape-job-status', function ($id) {
    Middleware::auth();
    $controller = new MetaTagController();
    return $controller->scrapeJobStatus((int) $id);
});

// Annulla job in esecuzione
Router::post('/ai-content/projects/{id}/meta-tags/cancel-scrape-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->cancelScrapeJob((int) $id);
});

// Avvia job di generazione AI in background
Router::post('/ai-content/projects/{id}/meta-tags/start-generate-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->startGenerateJob((int) $id);
});

// SSE Stream per progress generazione AI real-time
Router::get('/ai-content/projects/{id}/meta-tags/generate-stream', function ($id) {
    $controller = new MetaTagController();
    return $controller->generateStream((int) $id);
});

// Polling fallback per status job generazione
Router::get('/ai-content/projects/{id}/meta-tags/generate-job-status', function ($id) {
    Middleware::auth();
    $controller = new MetaTagController();
    return $controller->generateJobStatus((int) $id);
});

// Annulla job di generazione
Router::post('/ai-content/projects/{id}/meta-tags/cancel-generate-job', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->cancelGenerateJob((int) $id);
});

// ============================================
// META TAGS - ROUTES CON WILDCARD {tagId}
// IMPORTANTE: Queste routes devono stare DOPO le routes specifiche!
// ============================================

// Preview/Edit singolo meta tag
Router::get('/ai-content/projects/{id}/meta-tags/{tagId}', function ($id, $tagId) {
    Middleware::auth();
    $controller = new MetaTagController();
    return $controller->preview((int) $id, (int) $tagId);
});

// Update singolo meta tag (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/{tagId}/update', function ($id, $tagId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->update((int) $id, (int) $tagId);
});

// Approve singolo (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/{tagId}/approve', function ($id, $tagId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->approve((int) $id, (int) $tagId);
});

// Publish singolo (AJAX)
Router::post('/ai-content/projects/{id}/meta-tags/{tagId}/publish', function ($id, $tagId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->publish((int) $id, (int) $tagId);
});

// Delete singolo
Router::post('/ai-content/projects/{id}/meta-tags/{tagId}/delete', function ($id, $tagId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->delete((int) $id, (int) $tagId);
});

// Reset errori scraping
Router::post('/ai-content/projects/{id}/meta-tags/reset-scrape-errors', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->resetScrapeErrors((int) $id);
});

// Reset errori generazione
Router::post('/ai-content/projects/{id}/meta-tags/reset-generation-errors', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new MetaTagController();
    return $controller->resetGenerationErrors((int) $id);
});
