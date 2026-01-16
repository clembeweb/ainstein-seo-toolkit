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

$moduleSlug = 'ai-content';

// TEST AJAX ENDPOINT
Router::get('/ai-content/test-ajax', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'AJAX works',
        'time' => date('H:i:s'),
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET'
    ]);
    exit;
});

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECT ROUTES
// ============================================

// Lista progetti (nuova homepage modulo)
Router::get('/ai-content', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Alias lista progetti
Router::get('/ai-content/projects', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

// Form nuovo progetto
Router::get('/ai-content/projects/create', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->create();
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
    $project = (new \Modules\AiContent\Models\Project())->find((int) $id, Auth::user()['id']);

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

// Lista siti WordPress
Router::get('/ai-content/wordpress', function () {
    Middleware::auth();
    $controller = new WordPressController();
    return $controller->index();
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

// Cancel running process
Router::post('/ai-content/projects/{id}/auto/process/cancel', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new AutoController();
    return $controller->cancelProcess((int) $id);
});
