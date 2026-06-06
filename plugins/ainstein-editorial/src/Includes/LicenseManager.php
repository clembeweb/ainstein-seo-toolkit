<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Includes;

use WP_Error;

/**
 * Gestione license lato plugin: attivazione, cache stato in wp_options,
 * refresh automatico dell'api_token, disattivazione.
 */
class LicenseManager
{
    /** Soglia per il refresh proattivo del token (1h prima della scadenza) */
    private const REFRESH_THRESHOLD = 3600;

    /**
     * Attiva la license su questo sito.
     *
     * @return true|WP_Error
     */
    public function activate(string $licenseKey): bool|WP_Error
    {
        $licenseKey = trim($licenseKey);
        if ($licenseKey === '') {
            return new WP_Error('aied_empty_key', 'Inserisci una license key valida.');
        }

        $adminEmail = function_exists('wp_get_current_user') ? wp_get_current_user()->user_email : '';
        if ($adminEmail === '') {
            $adminEmail = (string) get_option('admin_email', '');
        }

        $response = (new ApiClient(false))->post('/activate', [
            'license_key'    => $licenseKey,
            'domain'         => home_url(),
            'wp_version'     => get_bloginfo('version'),
            'plugin_version' => AIED_VERSION,
            'admin_email'    => $adminEmail,
        ], false);

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['api_token'])) {
            return new WP_Error('aied_activation', 'Attivazione non riuscita. Riprova.');
        }

        update_option('aied_license_key', $licenseKey);
        update_option('aied_api_token', (string) $response['api_token']);
        update_option('aied_api_token_expires_at', time() + (int) ($response['expires_in'] ?? 0));
        update_option('aied_tier', (string) ($response['tier'] ?? 'starter'));
        update_option('aied_user_email', (string) ($response['user']['email'] ?? $adminEmail));

        return true;
    }

    public function isActive(): bool
    {
        return get_option('aied_license_key', '') !== '' && get_option('aied_api_token', '') !== '';
    }

    public function getTier(): string
    {
        return (string) get_option('aied_tier', 'starter');
    }

    public function getEmail(): string
    {
        return (string) get_option('aied_user_email', '');
    }

    /**
     * Ritorna l'api_token corrente, rinnovandolo se vicino alla scadenza.
     */
    public function getApiToken(): string
    {
        $expiresAt = (int) get_option('aied_api_token_expires_at', 0);
        if ($expiresAt > 0 && ($expiresAt - time()) < self::REFRESH_THRESHOLD) {
            $this->refresh();
        }
        return (string) get_option('aied_api_token', '');
    }

    /**
     * Rinnova l'api_token via /refresh-token. Ritorna true se riuscito.
     */
    public function refresh(): bool
    {
        $license = (string) get_option('aied_license_key', '');
        if ($license === '') {
            return false;
        }

        $response = (new ApiClient(false))->post('/refresh-token', [
            'license_key' => $license,
            'domain'      => home_url(),
        ], false);

        if (is_wp_error($response) || empty($response['api_token'])) {
            return false;
        }

        update_option('aied_api_token', (string) $response['api_token']);
        update_option('aied_api_token_expires_at', time() + (int) ($response['expires_in'] ?? 0));
        return true;
    }

    /**
     * Disattiva la license su questo sito e pulisce le options.
     *
     * @return true|WP_Error
     */
    public function deactivate(): bool|WP_Error
    {
        $license = (string) get_option('aied_license_key', '');
        $siteId  = (int) get_option('aied_site_id', 0);

        $response = (new ApiClient(false))->post('/deactivate', [
            'license_key' => $license,
            'site_id'     => $siteId,
        ], false);

        // Anche se il backend fallisce, ripuliamo lo stato locale per permettere
        // una nuova attivazione (il backend libererà lo slot via refresh/validate).
        update_option('aied_license_key', '');
        update_option('aied_api_token', '');
        update_option('aied_api_token_expires_at', 0);
        update_option('aied_tier', '');
        update_option('aied_user_email', '');
        update_option('aied_site_id', 0);

        if (is_wp_error($response)) {
            return $response;
        }
        return true;
    }
}
