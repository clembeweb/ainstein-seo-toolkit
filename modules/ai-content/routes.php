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
use Modules\AiContent\Controllers\DashboardController;
use Modules\AiContent\Controllers\KeywordController;
use Modules\AiContent\Controllers\SerpController;
use Modules\AiContent\Controllers\ArticleController;
use Modules\AiContent\Controllers\WordPressController;
use Modules\AiContent\Controllers\WizardController;

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
// DASHBOARD ROUTES
// ============================================

Router::get('/ai-content', function () {
    Middleware::auth();
    $controller = new DashboardController();
    return $controller->index();
});

// ============================================
// KEYWORDS ROUTES
// ============================================

// Lista keywords
Router::get('/ai-content/keywords', function () {
    Middleware::auth();
    $controller = new KeywordController();
    return $controller->index();
});

// Salva nuova keyword
Router::post('/ai-content/keywords', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new KeywordController();
    return $controller->store();
});

// Elimina keyword
Router::post('/ai-content/keywords/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new KeywordController();
    return $controller->delete((int) $id);
});

// Wizard per generazione articolo
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
// ARTICLES ROUTES
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

// ============================================
// WORDPRESS ROUTES
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
