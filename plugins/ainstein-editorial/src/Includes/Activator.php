<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Includes;

/**
 * Callback register_activation_hook.
 * Crea le wp_options di default e imposta il redirect alla pagina License.
 */
class Activator
{
    /** Opzioni gestite dal plugin (prefisso aied_) */
    public const OPTIONS = [
        'aied_license_key',
        'aied_api_token',
        'aied_api_token_expires_at',
        'aied_user_email',
        'aied_tier',
        'aied_site_id',
        'aied_plugin_version',
        'aied_first_activated_at',
    ];

    public static function activate(): void
    {
        foreach (self::OPTIONS as $option) {
            if (get_option($option, null) === null) {
                add_option($option, '');
            }
        }

        update_option('aied_plugin_version', AIED_VERSION);

        if ((int) get_option('aied_first_activated_at', 0) === 0) {
            update_option('aied_first_activated_at', time());
        }

        // Redirect alla pagina License al primo caricamento admin
        set_transient('aied_activation_redirect', 1, 60);
    }
}
