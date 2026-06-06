<?php

/**
 * Plugin Name:       Ainstein Editorial
 * Plugin URI:        https://ainstein.it/editorial
 * Description:       Il tuo SEO Pro autonomo per WordPress. Pianifica, scrive e ottimizza articoli al posto tuo. (An Ainstein product)
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Ainstein
 * Author URI:        https://ainstein.it
 * License:           GPL-2.0-or-later
 * Text Domain:       ainstein-editorial
 * Domain Path:       /languages
 *
 * @package Ainstein\Editorial
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // Accesso diretto vietato
}

// --- Costanti plugin ---
define('AIED_VERSION', '0.1.0');
define('AIED_PLUGIN_FILE', __FILE__);
define('AIED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIED_MIN_PHP', '8.0');

// Base URL del backend API. Override possibile via:
//   1. costante AIED_API_BASE definita in wp-config.php (dev)
//   2. filtro 'aied_api_base'
//   3. default produzione
if (!defined('AIED_API_BASE')) {
    define('AIED_API_BASE', 'https://ainstein.it/api/editorial/v1');
}

// --- Autoloader ---
$aiedComposer = AIED_PLUGIN_DIR . 'vendor/autoload.php';
if (is_file($aiedComposer)) {
    require_once $aiedComposer;
} else {
    // Fallback PSR-4 minimale (utile in dev via symlink senza composer install)
    spl_autoload_register(function (string $class): void {
        $prefix = 'Ainstein\\Editorial\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = AIED_PLUGIN_DIR . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

// --- Guard versione PHP ---
if (version_compare(PHP_VERSION, AIED_MIN_PHP, '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html(sprintf(
            'Ainstein Editorial richiede PHP %s o superiore. Versione attuale: %s.',
            AIED_MIN_PHP,
            PHP_VERSION
        ));
        echo '</p></div>';
    });
    return;
}

// --- Hook attivazione / disattivazione ---
register_activation_hook(__FILE__, [\Ainstein\Editorial\Includes\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\Ainstein\Editorial\Includes\Deactivator::class, 'deactivate']);

// --- Bootstrap ---
add_action('plugins_loaded', function () {
    \Ainstein\Editorial\Includes\Plugin::instance()->boot();
});
