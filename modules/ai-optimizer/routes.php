<?php

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive('ai-optimizer')) {
    return;
}

// ============================================
// PROGETTI
// ============================================

// Lista progetti (homepage modulo)
Router::get('/ai-optimizer', function () {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\ProjectController();
    return $controller->index();
});

// Form creazione progetto → redirect a Global Projects
Router::get('/ai-optimizer/projects/create', function () {
    \Core\Router::redirect('/projects/create');
});

// Salva nuovo progetto
Router::post('/ai-optimizer/projects', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\AiOptimizer\Controllers\ProjectController();
    $controller->store();
});

// Dashboard progetto
Router::get('/ai-optimizer/project/{id}', function ($id) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\ProjectController();
    return $controller->show((int)$id);
});

// Impostazioni progetto
Router::get('/ai-optimizer/project/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\ProjectController();
    return $controller->settings((int)$id);
});

// Aggiorna progetto
Router::post('/ai-optimizer/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\AiOptimizer\Controllers\ProjectController();
    $controller->update((int)$id);
});

// Elimina progetto
Router::post('/ai-optimizer/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\AiOptimizer\Controllers\ProjectController();
    $controller->delete((int)$id);
});

// ============================================
// WIZARD OTTIMIZZAZIONE (4 STEP)
// ============================================

// Step 1: Form import articolo
Router::get('/ai-optimizer/project/{id}/optimize', function ($id) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    return $controller->import((int)$id);
});

// Step 1: Salva import
Router::post('/ai-optimizer/project/{id}/optimize', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    $controller->storeImport((int)$id);
});

// Step 2: Pagina analisi
Router::get('/ai-optimizer/project/{id}/optimize/{optId}/analyze', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    return $controller->analyze((int)$id, (int)$optId);
});

// Step 2: Esegui analisi (AJAX)
Router::post('/ai-optimizer/project/{id}/optimize/{optId}/analyze', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    $controller->runAnalysis((int)$id, (int)$optId);
});

// Step 3: Pagina riscrittura
Router::get('/ai-optimizer/project/{id}/optimize/{optId}/refactor', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    return $controller->refactor((int)$id, (int)$optId);
});

// Step 3: Esegui riscrittura (AJAX)
Router::post('/ai-optimizer/project/{id}/optimize/{optId}/refactor', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    $controller->runRefactor((int)$id, (int)$optId);
});

// Step 4: Export/Risultato
Router::get('/ai-optimizer/project/{id}/optimize/{optId}/export', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    return $controller->export((int)$id, (int)$optId);
});

// ============================================
// ALTRE AZIONI OTTIMIZZAZIONE
// ============================================

// Visualizza ottimizzazione (redirect allo step appropriato)
Router::get('/ai-optimizer/project/{id}/optimize/{optId}', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    $controller->show((int)$id, (int)$optId);
});

// Elimina ottimizzazione
Router::post('/ai-optimizer/project/{id}/optimize/{optId}/delete', function ($id, $optId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    $controller->delete((int)$id, (int)$optId);
});

// Download HTML
Router::get('/ai-optimizer/project/{id}/optimize/{optId}/download', function ($id, $optId) {
    Middleware::auth();
    $controller = new \Modules\AiOptimizer\Controllers\OptimizationController();
    $controller->downloadHtml((int)$id, (int)$optId);
});
