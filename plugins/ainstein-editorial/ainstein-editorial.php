<?php
/**
 * Plugin Name:       Ainstein Editorial
 * Plugin URI:        https://ainstein.it/editorial
 * Description:       Il tuo SEO Pro autonomo per WordPress. Pianifica, scrive, ottimizza e linka articoli SEO in autopilota.
 * Version:           0.1.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Ainstein
 * Author URI:        https://ainstein.it
 * License:           Proprietary
 * Text Domain:       ainstein-editorial
 * Domain Path:       /languages
 * Update URI:        false
 *
 * Update URI: false — blocca check update da wordpress.org (proteggiamo contro
 * collisioni nome con plugin omonimi pubblicati su WP.org). Verra' impostato
 * sul nostro update server quando avremo il dominio prodotto e M7 (auto-update).
 *
 * @package Ainstein\Editorial
 */

if (!defined('ABSPATH')) {
    exit; // No direct access
}

// =============================================================================
// Constants
// =============================================================================

define('AIED_VERSION', '0.1.1');
define('AIED_PLUGIN_FILE', __FILE__);
define('AIED_PATH', plugin_dir_path(__FILE__));
define('AIED_URL', plugin_dir_url(__FILE__));
define('AIED_BASENAME', plugin_basename(__FILE__));

/**
 * Base URL del backend API.
 * Override via constant in wp-config.php:
 *   define('AIED_API_BASE', 'https://staging.ainstein.it/api/editorial/v1');
 * Default: produzione ainstein.it (verra' aggiornato col dominio finale post-launch).
 */
if (!defined('AIED_API_BASE')) {
    define('AIED_API_BASE', 'https://ainstein.it/api/editorial/v1');
}

// =============================================================================
// Composer autoload
// =============================================================================

if (file_exists(AIED_PATH . 'vendor/autoload.php')) {
    require_once AIED_PATH . 'vendor/autoload.php';
} else {
    // Fallback PSR-4 autoloader minimale (utile in dev senza composer install)
    spl_autoload_register(function ($class) {
        $prefixes = [
            'Ainstein\\Editorial\\Includes\\' => AIED_PATH . 'src/Includes/',
            'Ainstein\\Editorial\\Admin\\' => AIED_PATH . 'src/Admin/',
            'Ainstein\\Editorial\\Utils\\' => AIED_PATH . 'src/Utils/',
        ];
        foreach ($prefixes as $prefix => $base) {
            if (str_starts_with($class, $prefix)) {
                $rel = substr($class, strlen($prefix));
                $file = $base . str_replace('\\', '/', $rel) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        }
    });
}

// =============================================================================
// Activation / Deactivation hooks
// =============================================================================

register_activation_hook(__FILE__, ['Ainstein\Editorial\Includes\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Ainstein\Editorial\Includes\Deactivator', 'deactivate']);

// =============================================================================
// Bootstrap
// =============================================================================

add_action('plugins_loaded', function () {
    if (class_exists('Ainstein\Editorial\Includes\Plugin')) {
        \Ainstein\Editorial\Includes\Plugin::instance()->run();
    }
});
