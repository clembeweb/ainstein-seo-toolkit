<?php

namespace Ainstein\Editorial\Includes;

/**
 * LicenseManager (plugin-side) — facade per attivazione/disattivazione license dal plugin.
 *
 * NB: NON e' lo stesso del LicenseManager backend (Editorial\Services\LicenseManager).
 * Questo opera in WordPress: legge/scrive wp_options, chiama ApiClient verso il backend.
 *
 * wp_options usate (tutte autoload=no per ridurre overhead):
 *   aied_license_key            string
 *   aied_api_token              string (JWT short-lived)
 *   aied_api_token_expires_at   int unix timestamp
 *   aied_tier                   string  (starter|pro|business|agency)
 *   aied_user_email             string
 *   aied_sites_used             int
 *   aied_sites_limit            int
 *   aied_site_id                int
 *   aied_activated_at           int unix timestamp
 */
class LicenseManager
{
    /** Soglia per refresh proattivo del token: se scade entro 1h, refresh prima della prossima call. */
    private const REFRESH_THRESHOLD_SECONDS = 3600;

    private ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    /**
     * Attiva la license sul backend. Salva token + tier in wp_options.
     *
     * @return array{ok:bool, message:string, tier?:string, sites_used?:int, sites_limit?:int}
     */
    public function activate(string $licenseKey, string $adminEmail): array
    {
        $licenseKey = trim($licenseKey);
        $adminEmail = trim($adminEmail);

        if ($licenseKey === '') {
            return ['ok' => false, 'message' => 'Inserisci la license key.'];
        }
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Email amministratore non valida.'];
        }

        $payload = [
            'license_key'    => $licenseKey,
            'domain'         => $this->siteDomain(),
            'wp_version'     => function_exists('get_bloginfo') ? (string) get_bloginfo('version') : '',
            'plugin_version' => AIED_VERSION,
            'admin_email'    => $adminEmail,
        ];

        // Salva subito la license_key cosi' che ApiClient la invii via header X-License-Key
        update_option('aied_license_key', $licenseKey, false);

        $resp = $this->api->post('/activate', $payload, ['auth' => 'license']);

        if (!$resp['ok']) {
            // Pulisci la license appena salvata: attivazione fallita
            delete_option('aied_license_key');
            return [
                'ok' => false,
                'message' => $this->translateBackendError((string) ($resp['error'] ?? 'Attivazione fallita.')),
            ];
        }

        $data = $resp['data'] ?? [];
        if (!is_array($data) || empty($data['api_token'])) {
            delete_option('aied_license_key');
            return ['ok' => false, 'message' => 'Risposta backend non valida.'];
        }

        // Persisti tutti i metadati
        $expiresIn = (int) ($data['expires_in'] ?? 86400);
        update_option('aied_api_token', (string) $data['api_token'], false);
        update_option('aied_api_token_expires_at', time() + $expiresIn, false);
        update_option('aied_tier', (string) ($data['tier'] ?? 'starter'), false);
        update_option('aied_sites_used', (int) ($data['sites_used'] ?? 1), false);
        update_option('aied_sites_limit', (int) ($data['sites_limit'] ?? 1), false);
        update_option('aied_site_id', (int) ($data['site_id'] ?? 0), false);
        update_option('aied_user_email', (string) ($data['user']['email'] ?? $adminEmail), false);
        update_option('aied_activated_at', time(), false);

        return [
            'ok' => true,
            'message' => 'License attivata correttamente.',
            'tier' => (string) ($data['tier'] ?? 'starter'),
            'sites_used' => (int) ($data['sites_used'] ?? 1),
            'sites_limit' => (int) ($data['sites_limit'] ?? 1),
        ];
    }

    /**
     * Disattiva il sito corrente. Mantiene la license key salvata
     * (l'utente puo' riattivare lo stesso sito senza re-incollare).
     *
     * @return array{ok:bool, message:string}
     */
    public function deactivate(): array
    {
        $siteId = (int) get_option('aied_site_id', 0);
        if ($siteId <= 0) {
            return ['ok' => false, 'message' => 'Nessuna licenza attiva su questo sito.'];
        }

        $resp = $this->api->post('/deactivate', ['site_id' => $siteId]);

        // Pulisci comunque le option locali, anche se backend ritorna errore:
        // dal punto di vista utente "voglio disattivare qui" e' definitivo.
        // Se il backend e' down, lasciamo cmq lo stato locale coerente.
        $this->clearLocalState();

        if (!$resp['ok']) {
            return [
                'ok' => false,
                'message' => 'Disattivazione locale completata, ma il backend non ha risposto correttamente: '
                    . (string) ($resp['error'] ?? 'errore sconosciuto')
                    . '. Lo slot remoto potrebbe rimanere occupato — riapri questa pagina tra qualche minuto per riprovare.',
            ];
        }
        return ['ok' => true, 'message' => 'Licenza disattivata su questo sito.'];
    }

    public function isActive(): bool
    {
        $token = (string) get_option('aied_api_token', '');
        if ($token === '') {
            return false;
        }
        $exp = (int) get_option('aied_api_token_expires_at', 0);
        // Token scaduto: ApiClient gestira' il refresh on-demand, qui restituiamo cmq true
        // perche' "isActive" significa "license configurata", non "token valido in questo istante".
        return $exp > 0;
    }

    /**
     * Ritorna il token corrente. Se sta per scadere (<1h), tenta refresh proattivo
     * prima di restituirlo.
     */
    public function getApiToken(): string
    {
        $token = (string) get_option('aied_api_token', '');
        $exp = (int) get_option('aied_api_token_expires_at', 0);

        if ($token !== '' && $exp > 0 && ($exp - time()) < self::REFRESH_THRESHOLD_SECONDS) {
            $this->refreshToken();
            $token = (string) get_option('aied_api_token', '');
        }
        return $token;
    }

    /**
     * Recupera dati pubblici per UI (license page).
     *
     * @return array{
     *   active:bool, tier:string, email:string,
     *   sites_used:int, sites_limit:int, activated_at:int,
     *   expires_at:int
     * }
     */
    public function status(): array
    {
        return [
            'active'       => $this->isActive(),
            'tier'         => (string) get_option('aied_tier', ''),
            'email'        => (string) get_option('aied_user_email', ''),
            'sites_used'   => (int) get_option('aied_sites_used', 0),
            'sites_limit'  => (int) get_option('aied_sites_limit', 0),
            'activated_at' => (int) get_option('aied_activated_at', 0),
            'expires_at'   => (int) get_option('aied_api_token_expires_at', 0),
        ];
    }

    // -------------------- Helpers --------------------

    private function refreshToken(): bool
    {
        $licenseKey = (string) get_option('aied_license_key', '');
        if ($licenseKey === '') return false;

        $resp = $this->api->post('/refresh-token', [
            'license_key' => $licenseKey,
            'domain' => $this->siteDomain(),
        ], ['auth' => 'license']);

        if (!$resp['ok'] || empty($resp['data']['api_token'])) {
            return false;
        }
        $expiresIn = (int) ($resp['data']['expires_in'] ?? 86400);
        update_option('aied_api_token', (string) $resp['data']['api_token'], false);
        update_option('aied_api_token_expires_at', time() + $expiresIn, false);
        return true;
    }

    private function clearLocalState(): void
    {
        delete_option('aied_api_token');
        delete_option('aied_api_token_expires_at');
        delete_option('aied_tier');
        delete_option('aied_sites_used');
        delete_option('aied_sites_limit');
        delete_option('aied_site_id');
        delete_option('aied_activated_at');
        // license_key e user_email rimangono per UX (ripopolare il form al prossimo activate)
    }

    private function siteDomain(): string
    {
        $url = function_exists('home_url') ? home_url('/') : '';
        $parsed = parse_url($url);
        return strtolower((string) ($parsed['host'] ?? ''));
    }

    /**
     * Traduce error codes backend in messaggi italiano. Match su pattern noti.
     */
    private function translateBackendError(string $err): string
    {
        $lower = strtolower($err);
        if (str_contains($lower, 'license key not valid') || str_contains($lower, 'invalid')) {
            return 'License key non valida. Controlla di averla copiata correttamente dall\'email di acquisto.';
        }
        if (str_contains($lower, 'site limit')) {
            return 'Hai raggiunto il limite di siti per il tuo piano. Disattiva la license su un altro sito prima di attivarla qui, oppure passa a un piano superiore.';
        }
        if (str_contains($lower, 'license is not active') || str_contains($lower, 'expired')) {
            return 'La license non e\' attiva (potrebbe essere scaduta o annullata). Verifica il tuo abbonamento nella pagina account.';
        }
        if (str_contains($lower, 'missing required')) {
            return 'Campi obbligatori mancanti. Riprova compilando tutto.';
        }
        return $err;
    }
}
