<?php

/**
 * Ainstein Editorial — Front controller API (JSON)
 *
 * Incluso da public/index.php quando l'URI contiene `/api/editorial/`.
 * Stack completamente separato dall'HTML di Ainstein: ogni risposta è JSON.
 *
 * Milestone M1.3 — API skeleton.
 */

declare(strict_types=1);

namespace { // forza scope globale per le definizioni sotto

    use Ainstein\Editorial\Http\Request;
    use Ainstein\Editorial\Http\Response;
    use Ainstein\Editorial\Http\Router;

    // ----------------------------------------------------------------
    // Autoloader PSR-4 per il namespace Ainstein\Editorial\
    // ----------------------------------------------------------------
    spl_autoload_register(function (string $class): void {
        $prefix = 'Ainstein\\Editorial\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });

    // ----------------------------------------------------------------
    // Error/exception handling → sempre JSON, mai stacktrace all'utente
    // ----------------------------------------------------------------
    $aiedDebug = (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1');

    set_exception_handler(function (\Throwable $e) use ($aiedDebug): void {
        $payload = ['error' => 'Errore interno del server.'];
        if ($aiedDebug) {
            $payload['debug'] = [
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ];
        }
        Response::json($payload, 500);
    });

    // ----------------------------------------------------------------
    // Dispatch
    // ----------------------------------------------------------------
    $request = Request::capture();
    $router  = new Router();

    // routes.php riceve $router e registra tutte le rotte
    (require __DIR__ . '/routes.php')($router);

    $router->dispatch($request);
}
