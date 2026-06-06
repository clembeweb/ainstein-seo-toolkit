<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Includes;

use Ainstein\Editorial\Admin\AdminPages;

/**
 * Classe principale del plugin (singleton). Inizializza gli hook su plugins_loaded.
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    public function boot(): void
    {
        load_plugin_textdomain('ainstein-editorial', false, dirname(plugin_basename(AIED_PLUGIN_FILE)) . '/languages');

        if (is_admin()) {
            (new AdminPages())->register();
        }
    }
}
