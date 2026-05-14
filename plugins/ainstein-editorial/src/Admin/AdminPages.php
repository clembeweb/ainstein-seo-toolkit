<?php

namespace Ainstein\Editorial\Admin;

use Ainstein\Editorial\Admin\Pages\License;
use Ainstein\Editorial\Includes\LicenseManager;

/**
 * AdminPages — registra menu admin del plugin + handler form license.
 *
 * In M1 esponiamo solo la pagina "License" (slug 'aied-license') sotto un menu
 * top-level "Ainstein Editorial". Le altre pagine (Dashboard, Calendario,
 * Articoli, Content Brain, Statistiche, Impostazioni) verranno aggiunte
 * progressivamente dalle milestone successive.
 *
 * Capability richiesta: `manage_options` (standard admin WP).
 */
class AdminPages
{
    public const MENU_SLUG = 'aied-license';
    public const CAPABILITY = 'manage_options';

    private LicenseManager $license;

    public function __construct(?LicenseManager $license = null)
    {
        $this->license = $license ?? new LicenseManager();
    }

    public function register(): void
    {
        add_menu_page(
            __('Ainstein Editorial', 'ainstein-editorial'),
            __('Ainstein Editorial', 'ainstein-editorial'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderLicensePage'],
            'dashicons-edit-large',
            58  // dopo Comments (25), prima di Appearance (60)
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('License', 'ainstein-editorial'),
            __('License', 'ainstein-editorial'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderLicensePage']
        );
    }

    /**
     * Render License page. Logica + markup in Pages/License.
     */
    public function renderLicensePage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Non hai i permessi per accedere a questa pagina.', 'ainstein-editorial'));
        }
        $page = new License($this->license);
        $page->render();
    }

    // -------------------- Form handlers (admin-post.php) --------------------

    /**
     * Handler POST /wp-admin/admin-post.php?action=aied_activate_license
     */
    public function handleActivateSubmit(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Permesso negato.', 'ainstein-editorial'));
        }
        check_admin_referer('aied_activate_license');

        $key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
        $email = isset($_POST['admin_email']) ? sanitize_email(wp_unslash($_POST['admin_email'])) : '';

        $result = $this->license->activate($key, $email);

        $notice = $result['ok'] ? 'activated' : 'activation_failed';
        $message = rawurlencode((string) ($result['message'] ?? ''));
        wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&notice=' . $notice . '&msg=' . $message));
        exit;
    }

    /**
     * Handler POST /wp-admin/admin-post.php?action=aied_deactivate_license
     */
    public function handleDeactivateSubmit(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Permesso negato.', 'ainstein-editorial'));
        }
        check_admin_referer('aied_deactivate_license');

        $result = $this->license->deactivate();

        $notice = $result['ok'] ? 'deactivated' : 'deactivation_partial';
        $message = rawurlencode((string) ($result['message'] ?? ''));
        wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&notice=' . $notice . '&msg=' . $message));
        exit;
    }
}
