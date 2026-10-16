<?php

/**
 * AI Keywords Module - Routes
 */

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\KeywordResearch\Controllers\DashboardController;
use Modules\KeywordResearch\Controllers\ProjectController;
use Modules\KeywordResearch\Controllers\ResearchController;
use Modules\KeywordResearch\Controllers\ArchitectureController;
use Modules\KeywordResearch\Controllers\QuickCheckController;
use Modules\KeywordResearch\Controllers\EditorialController;
use Modules\KeywordResearch\Controllers\ProfileController;

$moduleSlug = 'keyword-research';

if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// DASHBOARD (entry point modulo)
// ============================================

Router::get('/keyword-research', function () {
    Middleware::auth();
    $controller = new DashboardController();
    return $controller->index();
});

// ============================================
// PROJECTS
// ============================================

Router::get('/keyword-research/projects', function () {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->index();
});

Router::get('/keyword-research/projects/create', function () {
    \Core\Router::redirect('/projects/create');
});

Router::post('/keyword-research/projects', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->store();
});

// Ingresso del progetto (dal progetto globale e dall'elenco): apre l'ultimo passo usato, altrimenti la Ricerca keyword
Router::get('/keyword-research/project/{id}', function ($id) {
    Middleware::auth();
    $last = \Core\Database::fetch(
        "SELECT r.type FROM kr_researches r JOIN kr_projects p ON p.id = r.project_id WHERE r.project_id = ? AND p.user_id = ? ORDER BY r.id DESC LIMIT 1",
        [(int) $id, (int) (\Core\Auth::user()['id'] ?? 0)]
    );
    $step = in_array($last['type'] ?? '', ['architecture', 'editorial'], true) ? $last['type'] : 'research';
    \Core\Router::redirect('/keyword-research/project/' . (int) $id . '/' . $step);
});

Router::get('/keyword-research/project/{id}/settings', function ($id) {
    Middleware::auth();
    $controller = new ProjectController();
    return $controller->settings((int) $id);
});

Router::post('/keyword-research/project/{id}/settings', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->updateSettings((int) $id);
});

Router::post('/keyword-research/project/{id}/delete', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ProjectController();
    return $controller->destroy((int) $id);
});

// ============================================
// PROFILO DELL'ATTIVITÀ (ingresso dei 3 strumenti)
// ============================================

Router::post('/keyword-research/project/{id}/profile', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    return (new ProfileController())->build((int) $id);
});

// ============================================
// RESEARCH GUIDATA
// ============================================

Router::get('/keyword-research/project/{id}/research', function ($id) {
    Middleware::auth();
    $controller = new ResearchController();
    return $controller->wizard((int) $id);
});

Router::post('/keyword-research/project/{id}/research/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ResearchController();
    return $controller->startCollection((int) $id);
});

Router::get('/keyword-research/project/{id}/research/stream', function ($id) {
    Middleware::auth();
    $controller = new ResearchController();
    return $controller->collectionStream((int) $id);
});

Router::get('/keyword-research/project/{id}/research/collection-results', function ($id) {
    Middleware::auth();
    $controller = new ResearchController();
    return $controller->collectionResults((int) $id);
});

Router::post('/keyword-research/project/{id}/research/analyze', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ResearchController();
    return $controller->aiAnalyze((int) $id);
});

Router::get('/keyword-research/project/{id}/research/{researchId}', function ($id, $researchId) {
    Middleware::auth();
    $controller = new ResearchController();
    return $controller->results((int) $id, (int) $researchId);
});

Router::get('/keyword-research/project/{id}/research/{researchId}/export', function ($id, $researchId) {
    Middleware::auth();
    $controller = new ResearchController();
    return $controller->exportCsv((int) $id, (int) $researchId);
});

// ============================================
// ARCHITETTURA SITO
// ============================================

Router::get('/keyword-research/project/{id}/architecture', function ($id) {
    Middleware::auth();
    $controller = new ArchitectureController();
    return $controller->wizard((int) $id);
});

Router::post('/keyword-research/project/{id}/architecture/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArchitectureController();
    return $controller->startCollection((int) $id);
});

Router::get('/keyword-research/project/{id}/architecture/stream', function ($id) {
    Middleware::auth();
    $controller = new ArchitectureController();
    return $controller->collectionStream((int) $id);
});

Router::get('/keyword-research/project/{id}/architecture/collection-results', function ($id) {
    Middleware::auth();
    $controller = new ArchitectureController();
    return $controller->collectionResults((int) $id);
});

Router::post('/keyword-research/project/{id}/architecture/analyze', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArchitectureController();
    return $controller->aiAnalyze((int) $id);
});

Router::get('/keyword-research/project/{id}/architecture/{researchId}', function ($id, $researchId) {
    Middleware::auth();
    $controller = new ArchitectureController();
    return $controller->results((int) $id, (int) $researchId);
});

Router::post('/keyword-research/project/{id}/architecture/{researchId}/send-to-content-creator', function ($id, $researchId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new ArchitectureController();
    return $controller->sendToContentCreator((int) $id, (int) $researchId);
});

// ============================================
// PIANO EDITORIALE
// ============================================

Router::get('/keyword-research/project/{id}/editorial', function ($id) {
    Middleware::auth();
    $controller = new EditorialController();
    return $controller->wizard((int) $id);
});

Router::post('/keyword-research/project/{id}/editorial/start', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new EditorialController();
    return $controller->startCollection((int) $id);
});

Router::get('/keyword-research/project/{id}/editorial/stream', function ($id) {
    Middleware::auth();
    $controller = new EditorialController();
    return $controller->collectionStream((int) $id);
});

Router::get('/keyword-research/project/{id}/editorial/collection-results', function ($id) {
    Middleware::auth();
    $controller = new EditorialController();
    return $controller->collectionResults((int) $id);
});

Router::post('/keyword-research/project/{id}/editorial/analyze', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new EditorialController();
    return $controller->aiAnalyze((int) $id);
});

Router::get('/keyword-research/project/{id}/editorial/{researchId}', function ($id, $researchId) {
    Middleware::auth();
    $controller = new EditorialController();
    return $controller->results((int) $id, (int) $researchId);
});

Router::get('/keyword-research/project/{id}/editorial/{researchId}/export', function ($id, $researchId) {
    Middleware::auth();
    $controller = new EditorialController();
    return $controller->exportCsv((int) $id, (int) $researchId);
});

Router::post('/keyword-research/project/{id}/editorial/{researchId}/send-to-content', function ($id, $researchId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new EditorialController();
    return $controller->sendToContent((int) $id, (int) $researchId);
});

// ============================================
// QUICK CHECK (no progetto)
// ============================================

Router::get('/keyword-research/quick-check', function () {
    Middleware::auth();
    $controller = new QuickCheckController();
    return $controller->index();
});

Router::post('/keyword-research/quick-check/search', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new QuickCheckController();
    return $controller->search();
});

Router::post('/keyword-research/quick-check/send-to-tracking', function () {
    Middleware::auth();
    Middleware::csrf();
    $controller = new QuickCheckController();
    return $controller->sendToTracking();
});

Router::get('/keyword-research/quick-check/project-groups', function () {
    Middleware::auth();
    $controller = new QuickCheckController();
    return $controller->projectGroups();
});
