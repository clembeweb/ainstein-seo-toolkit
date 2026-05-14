<?php

namespace Ainstein\Editorial\Includes;

/**
 * Deactivator — eseguito da register_deactivation_hook al disable del plugin.
 *
 * In M1:
 *  - cancella tutti i WP cron events del plugin (prefisso 'aied_')
 *  - MANTIENE wp_options (l'utente potrebbe riattivare e ripartire senza re-incollare la license)
 *  - MANTIENE api_token nel DB (verra' invalidato lato backend dal flow /deactivate quando l'utente
 *    clicca esplicitamente "Disattiva su questo sito" dalla License page; la deactivation del
 *    plugin in WordPress NON e' la stessa cosa della deactivation della license)
 *
 * uninstall.php (TODO M6): pulisce tutto se l'utente vuole rimuovere completamente.
 */
class Deactivator
{
    public static function deactivate(): void
    {
        // Cancella tutti i cron events del plugin
        self::clearCronEvents();
    }

    private static function clearCronEvents(): void
    {
        $cronArray = _get_cron_array();
        if (empty($cronArray) || !is_array($cronArray)) {
            return;
        }

        foreach ($cronArray as $timestamp => $hooks) {
            if (!is_array($hooks)) continue;
            foreach (array_keys($hooks) as $hookName) {
                if (is_string($hookName) && str_starts_with($hookName, 'aied_')) {
                    wp_clear_scheduled_hook($hookName);
                }
            }
        }
    }
}
