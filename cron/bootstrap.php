<?php
/**
 * CLI Bootstrap
 *
 * Bootstrap leggero per script CLI (cron jobs)
 * Non include routing, sessioni o dispatch
 */

// Evita esecuzione da browser
if (php_sapi_name() !== 'cli') {
    die('Questo script puo essere eseguito solo da CLI');
}

// Timezone Italia
date_default_timezone_set('Europe/Rome');

// Definizioni
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', BASE_PATH);
define('DEBUG', false);

// Carica Composer autoloader (per librerie esterne come Readability)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Autoloader semplice
spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\' => BASE_PATH . '/services/',
        'Admin\\Controllers\\' => BASE_PATH . '/admin/controllers/',
    ];

    foreach ($paths as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // Autoload moduli
    if (str_starts_with($class, 'Modules\\')) {
        $parts = explode('\\', $class);
        if (count($parts) >= 4) {
            $moduleName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $parts[1]));
            $type = strtolower($parts[2]);
            $className = implode('/', array_slice($parts, 3));

            $file = BASE_PATH . '/modules/' . $moduleName . '/' . $type . '/' . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Inizializza Database (la classe Database si auto-inizializza al primo uso)
// Basta caricare la configurazione
use Core\Database;

// Forza inizializzazione lazy del database
// Database::init() viene chiamato automaticamente al primo query
