<?php

namespace Core;

/**
 * Google Analytics 4 della piattaforma Ainstein (il sito stesso, non i siti dei clienti).
 *
 * - Measurement ID in Admin > Impostazioni > Avanzate (`ga4_measurement_id`), mai nei file (Golden Rule 5).
 * - Nessun tag se l'ID manca o se chi naviga è admin (i nostri giri di prova non sporcano i numeri).
 * - Consent Mode v2: analytics_storage parte "denied"; il banner (components/cookie-banner) salva la scelta
 *   in localStorage `ainstein_consent` e aggiorna il consenso. Senza consenso GA4 riceve solo ping senza cookie.
 * - Eventi lato server (sign_up, login, project_created, module_activated): `Analytics::event()` li mette in coda
 *   nella sessione e la pagina successiva li manda con gtag('event'). I crediti spesi (`credits_spent`) non passano
 *   da qui: il browser li legge da usage_log tramite GET /credits/balance?since=ID (vedi layout.php).
 *
 * Documentazione: docs/GA4-AINSTEIN.md
 */
class Analytics
{
    public const SESSION_KEY = '_ga_events';
    private const MAX_QUEUE = 20;

    /** Measurement ID valido (G-XXXXXXXXXX) oppure null. */
    public static function measurementId(): ?string
    {
        $id = strtoupper(trim((string) Settings::get('ga4_measurement_id', '')));
        return preg_match('/^G-[A-Z0-9]{6,14}$/', $id) ? $id : null;
    }

    /** Tracciamento attivo per questa richiesta: ID impostato e visitatore non admin. */
    public static function enabled(): bool
    {
        if (self::measurementId() === null) {
            return false;
        }
        try {
            if (Auth::isAdmin()) {
                return false;
            }
        } catch (\Throwable $e) {
            // Senza sessione/DB (pagine pubbliche in errore): tracciamo comunque
        }
        return true;
    }

    /** In locale/debug gli eventi arrivano in GA4 > Amministrazione > DebugView. */
    public static function isDebug(): bool
    {
        if (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1') {
            return true;
        }
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return $host === 'localhost' || str_starts_with($host, 'localhost:') || str_starts_with($host, '127.0.0.1');
    }

    /**
     * Mette in coda un evento da mandare con la prossima pagina (dopo un redirect).
     * Parametri: solo valori semplici, mai dati personali (email, nomi).
     */
    public static function event(string $name, array $params = []): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !self::enabled()) {
            return;
        }
        $queue = $_SESSION[self::SESSION_KEY] ?? [];
        if (count($queue) >= self::MAX_QUEUE) {
            array_shift($queue);
        }
        $queue[] = ['name' => $name, 'params' => $params];
        $_SESSION[self::SESSION_KEY] = $queue;
    }

    /** Svuota la coda e la restituisce. */
    public static function pullEvents(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        $queue = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);
        return is_array($queue) ? $queue : [];
    }

    /**
     * Snippet per <head>: gtag.js + consenso + config + eventi in coda.
     * $pageEvents: eventi da mandare subito su questa pagina (es. pricing_view).
     * Ritorna stringa vuota se il tracciamento non è attivo.
     */
    public static function headScript(array $pageEvents = []): string
    {
        // Una sola volta per richiesta: le pagine /docs oggi annidano layout.php dentro docs/layout.php
        // (due <head>), e due tag darebbero page_view doppie.
        static $printed = false;
        if ($printed || !self::enabled()) {
            return '';
        }
        $printed = true;
        $id = self::measurementId();
        $config = ['send_page_view' => true];
        if (self::isDebug()) {
            $config['debug_mode'] = true;
        }
        $events = array_merge(self::pullEvents(), $pageEvents);

        $js = "window.dataLayer = window.dataLayer || [];\n"
            . "function gtag(){dataLayer.push(arguments);}\n"
            . "gtag('consent', 'default', {analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', wait_for_update: 500});\n"
            . "try { if (localStorage.getItem('ainstein_consent') === 'granted') { gtag('consent', 'update', {analytics_storage: 'granted'}); } } catch (e) {}\n"
            . "gtag('js', new Date());\n"
            . "gtag('config', " . json_encode($id) . ", " . json_encode($config) . ");\n"
            . "window.ainsteinTrack = function (name, params) { try { gtag('event', name, params || {}); } catch (e) {} };\n";
        foreach ($events as $ev) {
            $name = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($ev['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $params = is_array($ev['params'] ?? null) ? $ev['params'] : [];
            $js .= "gtag('event', " . json_encode($name) . ", " . json_encode((object) $params, JSON_UNESCAPED_UNICODE) . ");\n";
        }

        return "<!-- Google Analytics 4 (ID da Admin > Impostazioni; docs/GA4-AINSTEIN.md) -->\n"
            . '<script async src="https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($id) . "\"></script>\n"
            . "<script>\n" . $js . "</script>\n";
    }

    /** Banner cookie (prima di </body>). Vuoto se il tracciamento non è attivo. */
    public static function banner(): string
    {
        static $printed = false;
        if ($printed || !self::enabled()) {
            return '';
        }
        $printed = true;
        return View::partial('components/cookie-banner');
    }

    /**
     * Ultimo id di usage_log dell'utente: il browser parte da qui per gli eventi credits_spent
     * (così non rimanda la storia passata). Vedi GET /credits/balance.
     */
    public static function usageSeed(int $userId): int
    {
        try {
            $row = Database::fetch("SELECT MAX(id) AS m FROM usage_log WHERE user_id = ?", [$userId]);
            return (int) ($row['m'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Righe di usage_log dopo $sinceId, già nel formato dell'evento credits_spent. */
    public static function usageSince(int $userId, int $sinceId, int $limit = 50): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT id, module_slug, action, credits_used FROM usage_log WHERE user_id = ? AND id > ? ORDER BY id ASC LIMIT " . (int) $limit,
                [$userId, $sinceId]
            );
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'module' => (string) ($r['module_slug'] ?? ''),
                'operation' => (string) $r['action'],
                'credits' => (float) $r['credits_used'],
            ];
        }
        return $out;
    }
}
