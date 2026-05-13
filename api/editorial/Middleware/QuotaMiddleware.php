<?php

namespace Editorial\Middleware;

/**
 * QuotaMiddleware
 *
 * No-op stub per M1.3.
 *
 * Implementazione completa in M3 quando avremo:
 *  - Tabelle ed_subscriptions / ed_quota_usage
 *  - Conteggio articoli generati / mese
 *  - Limiti per piano (Starter / Pro / Agency)
 *
 * Fino ad allora restituisce silenziosamente.
 */
class QuotaMiddleware
{
    /**
     * @param string|null $licenseKey License key del chiamante (sara usata in M3).
     * @param string      $resource   Risorsa consumata (articles, scans, etc.) - sara usata in M3.
     */
    public static function handle(?string $licenseKey = null, string $resource = 'articles'): void
    {
        // No-op stub. M3 implementera il check effettivo:
        //   1. Carica subscription dalla license_key
        //   2. Conta uso corrente per $resource nel periodo di billing
        //   3. Confronta con piano (Starter: N/mese, Pro: M/mese, Agency: K/mese)
        //   4. Se superato: 403 con error="Quota exceeded" + upgrade_url
        unset($licenseKey, $resource);
    }
}
