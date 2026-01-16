<?php

/**
 * Template Module Routes
 *
 * Copia questa cartella e rinominala per creare un nuovo modulo.
 * Es: modules/keyword-research/
 *
 * Registra il modulo nel database:
 * INSERT INTO modules (slug, name, description, version, is_active)
 * VALUES ('keyword-research', 'Keyword Research', 'Ricerca e analisi keyword', '1.0.0', 1);
 */

use Core\Router;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Credits;
use Core\ModuleLoader;

// Slug del modulo (deve corrispondere al nome della cartella)
$moduleSlug = '_template';

// Verifica che il modulo sia attivo
if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// --- Routes del modulo ---

Router::get('/' . $moduleSlug, function () use ($moduleSlug) {
    Middleware::auth();

    $user = Auth::user();

    // Per moduli con sub-entità (es. progetti), vedere docs/MODULE_NAVIGATION.md
    // La navigazione deve essere integrata in shared/views/components/nav-items.php

    return View::render($moduleSlug . '/index', [
        'title' => 'Template Module',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'moduleSlug' => $moduleSlug,
    ]);
});

Router::post('/' . $moduleSlug . '/analyze', function () use ($moduleSlug) {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();

    // Esempio: verifica crediti prima dell'operazione
    $cost = Credits::getCost('ai_analysis_small');

    if (!Middleware::hasCredits($cost)) {
        return View::json([
            'error' => true,
            'message' => 'Crediti insufficienti',
            'credits_required' => $cost,
            'credits_available' => $user['credits'],
        ], 402);
    }

    // Esempio: consuma crediti
    // Credits::consume($user['id'], $cost, 'template_analyze', $moduleSlug, ['input' => $input]);

    return View::json([
        'success' => true,
        'message' => 'Operazione completata',
    ]);
});
