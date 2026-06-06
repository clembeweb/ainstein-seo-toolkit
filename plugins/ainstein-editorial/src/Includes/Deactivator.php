<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Includes;

/**
 * Callback register_deactivation_hook.
 * M1: mantiene i dati (nessuna cancellazione options). Pulisce eventuali cron.
 * La deattivazione della license lato backend è un'azione esplicita dell'utente
 * (bottone "Disattiva su questo sito"), non avviene allo spegnimento del plugin.
 */
class Deactivator
{
    public static function deactivate(): void
    {
        // M1 non registra ancora cron; predisposizione per M4.
        $timestamp = wp_next_scheduled('aied_weekly_autopilot');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aied_weekly_autopilot');
        }
        delete_transient('aied_activation_redirect');
    }
}
