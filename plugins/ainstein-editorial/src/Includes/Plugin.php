<?php

namespace Ainstein\Editorial\Includes;

use Ainstein\Editorial\Admin\AdminPages;

/**
 * Plugin — Singleton main class.
 *
 * Orchestratore principale: registra hook WP, istanzia componenti
 * (AdminPages, etc.), espone metadati utili (version, paths).
 *
 * Pattern singleton perche':
 * - Garantisce un solo entrypoint (no doppia inizializzazione su plugins_loaded)
 * - Permette accesso da hook callback senza dover passare istanze
 *
 * Hook registrati:
 * - admin_menu       → AdminPages::register()
 * - admin_enqueue_scripts → enqueue CSS admin (solo su pagine plugin)
 * - admin_init       → handler redirect post-activation
 * - admin_post_aied_activate / aied_deactivate → form submit license
 */
class Plugin
{
    private static ?Plugin $instance = null;

    private AdminPages $adminPages;
    private bool $running = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->adminPages = new AdminPages();
    }

    /**
     * Registra tutti gli hook WP. Chiamato una volta da plugin.php su plugins_loaded.
     */
    public function run(): void
    {
        if ($this->running) {
            return;
        }
        $this->running = true;

        // Admin menu + pages
        add_action('admin_menu', [$this->adminPages, 'register']);

        // Asset enqueue (solo nelle pagine plugin)
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);

        // Redirect post-activation alla License page (transient one-shot)
        add_action('admin_init', [$this, 'handleActivationRedirect']);

        // Form submit handlers (admin-post.php pattern)
        add_action('admin_post_aied_activate_license', [$this->adminPages, 'handleActivateSubmit']);
        add_action('admin_post_aied_deactivate_license', [$this->adminPages, 'handleDeactivateSubmit']);

        // Text domain per i18n
        add_action('init', [$this, 'loadTextDomain']);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain('ainstein-editorial', false, dirname(AIED_BASENAME) . '/languages');
    }

    /**
     * Carica CSS solo sulle pagine del plugin (slug 'aied').
     */
    public function enqueueAdminAssets(string $hook): void
    {
        if (!str_contains($hook, 'aied') && !str_contains($hook, 'ainstein-editorial')) {
            return;
        }
        wp_enqueue_style(
            'aied-admin',
            AIED_URL . 'assets/css/admin.css',
            [],
            AIED_VERSION
        );
    }

    /**
     * Se il transient 'aied_show_activation_redirect' e' presente (settato
     * dall'Activator), redireziona alla License page al primo load admin.
     *
     * Skip in contesto CLI (WP-CLI, deploy script, test harness): wp-cli
     * intercetta wp_redirect con WP_CLI::error() → exit 255, rompendo qualsiasi
     * `wp eval`/`wp eval-file` eseguito nei primi 30s post-attivazione. Vedi
     * ADR-029 + tests/wp-compat/smoke.sh per il bug originale scoperto in M1.9.7.
     */
    public function handleActivationRedirect(): void
    {
        // M2.0: short-circuit in CLI prima di leggere il transient (no side effects).
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        if (!get_transient('aied_show_activation_redirect')) {
            return;
        }
        delete_transient('aied_show_activation_redirect');

        // Evita redirect se siamo in attivazione bulk o cron
        if (wp_doing_ajax() || (defined('DOING_CRON') && DOING_CRON)) {
            return;
        }

        // Evita redirect se l'admin attiva un altro plugin nello stesso request
        if (isset($_GET['activate-multi'])) {
            return;
        }

        wp_safe_redirect(admin_url('admin.php?page=aied-license'));
        exit;
    }

    public function version(): string
    {
        return AIED_VERSION;
    }
}
