<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Admin;

use Ainstein\Editorial\Admin\Pages\License;

/**
 * Registra il menu admin di Ainstein Editorial.
 * M1: solo voce "License". Dashboard e altre pagine arrivano in M2-M6.
 */
class AdminPages
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'handleForms']);
        add_action('admin_init', [$this, 'maybeRedirect']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    public function menu(): void
    {
        add_menu_page(
            'Ainstein Editorial',
            'Ainstein Editorial',
            'manage_options',
            'ainstein-editorial',
            [new License(), 'render'],
            'dashicons-edit-page',
            58
        );

        add_submenu_page(
            'ainstein-editorial',
            'License',
            'License',
            'manage_options',
            'ainstein-editorial',
            [new License(), 'render']
        );
    }

    /**
     * Gestisce il submit dei form admin (attiva / disattiva).
     */
    public function handleForms(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        $action = isset($_POST['aied_action']) ? sanitize_key((string) $_POST['aied_action']) : '';
        if ($action === '') {
            return;
        }
        (new License())->handle($action);
    }

    public function maybeRedirect(): void
    {
        if (get_transient('aied_activation_redirect')) {
            delete_transient('aied_activation_redirect');
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=ainstein-editorial'));
                exit;
            }
        }
    }

    public function assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_ainstein-editorial') {
            return;
        }
        wp_enqueue_style(
            'ainstein-editorial-admin',
            AIED_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AIED_VERSION
        );
    }
}
