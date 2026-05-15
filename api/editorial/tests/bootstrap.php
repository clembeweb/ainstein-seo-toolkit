<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap per i test backend Editorial.
 *
 * Carica:
 *   - Composer autoloader (per dipendenze: firebase/php-jwt, ecc.)
 *   - Autoloader manuale per Core\* e Editorial\* (no PSR-4 nel composer root)
 *   - .env tramite config/environment.php se disponibile
 *
 * Note: i test NON toccano il DB di produzione. Le classi che usano Core\Database
 * vanno testate con mock (PHPUnit doubles) o tramite test integration separati.
 */

define('ABSPATH_TESTS', __DIR__ . '/');
define('EDITORIAL_TEST_MODE', true);

// Composer autoload
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

// Manual autoloader per le classi del progetto (Ainstein non usa PSR-4 nel root)
spl_autoload_register(function (string $class): void {
    $root = dirname(__DIR__, 3);

    // Editorial namespace → api/editorial/
    if (str_starts_with($class, 'Editorial\\')) {
        $relative = substr($class, strlen('Editorial\\'));
        $path = $root . '/api/editorial/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
        return;
    }

    // Core namespace → core/
    if (str_starts_with($class, 'Core\\')) {
        $relative = substr($class, strlen('Core\\'));
        $path = $root . '/core/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});
