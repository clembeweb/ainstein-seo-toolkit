<?php

namespace Ainstein\Editorial\Includes;

/**
 * Activator — eseguito da register_activation_hook al primo enable del plugin.
 *
 * In M1 ci limitiamo a:
 *  - segnare il timestamp prima attivazione (solo se mai stato attivato)
 *  - flaggare un transient per redirect alla License page al primo admin load
 *  - NON crea tabelle DB (tutto nel backend Ainstein lato API, namespace aied_*)
 *  - NON crea wp_options pre-impostate vuote (le option si creano on-demand)
 *
 * In M2+ aggiungeremo:
 *  - schedule cron WP per auto-pilot
 *  - capability custom (se ne servono)
 *  - DB schema upgrades cross-version (versioning option aied_db_version)
 */
class Activator
{
    public static function activate(): void
    {
        // Segna prima attivazione (idempotente: non sovrascrive)
        if (!get_option('aied_first_activated_at')) {
            update_option('aied_first_activated_at', time(), false);
        }

        // Marker versione plugin (per future migration cross-version)
        update_option('aied_plugin_version', AIED_VERSION, false);

        // Transient one-shot: Plugin::handleActivationRedirect() lo intercetta
        // e reindirizza alla License page al primo admin load.
        set_transient('aied_show_activation_redirect', 1, 30);
    }
}
