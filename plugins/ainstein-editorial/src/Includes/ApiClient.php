<?php

namespace Ainstein\Editorial\Includes;

/**
 * ApiClient — wrapper su wp_remote_post/wp_remote_get per chiamare il backend.
 *
 * Responsabilita':
 *  - Compone URL (AIED_API_BASE . $endpoint)
 *  - Aggiunge headers standard (Content-Type, User-Agent versionato)
 *  - Iniezione automatica di:
 *      - X-License-Key  (da wp_option aied_license_key, se presente)
 *      - X-Site-Domain  (da home_url())
 *      - Authorization: Bearer <api_token>  (da wp_option aied_api_token, se presente)
 *  - Su 401: prova refresh-token UNA VOLTA, poi retry. Se fallisce → ritorna WP_Error.
 *  - Logga error_log su errori non recuperabili (WP debug.log).
 *
 * NON gestisce direttamente activate/deactivate: e' compito di LicenseManager
 * che usa ApiClient::post().
 *
 * Risposta: sempre array di forma
 *   ['ok' => bool, 'status' => int, 'data' => array|null, 'error' => ?string]
 * Non lancia eccezioni: error path testabile + UI puo' decidere come mostrare.
 */
class ApiClient
{
    /** Timeout default per chiamate "veloci" (activate, status, etc.) */
    private const DEFAULT_TIMEOUT = 15;
    /** Timeout per chiamate AI lunghe (generate article). Usato solo se invocato esplicitamente. */
    public const LONG_TIMEOUT = 300;

    private bool $hasRetried = false;

    /**
     * @param array $payload  Body JSON. NB: per /activate viene rimosso X-Api-Token e Bearer
     *                        (non disponibile ancora). Decidiamo per endpoint quali header usare.
     * @param array $opts     ['timeout' => int, 'auth' => 'license'|'token'|'public']
     * @return array{ok:bool, status:int, data:?array, error:?string}
     */
    public function post(string $endpoint, array $payload = [], array $opts = []): array
    {
        return $this->request('POST', $endpoint, $payload, $opts);
    }

    /**
     * @return array{ok:bool, status:int, data:?array, error:?string}
     */
    public function get(string $endpoint, array $opts = []): array
    {
        return $this->request('GET', $endpoint, null, $opts);
    }

    /**
     * @return array{ok:bool, status:int, data:?array, error:?string}
     */
    private function request(string $method, string $endpoint, ?array $body, array $opts): array
    {
        $url = $this->buildUrl($endpoint);
        $authMode = $opts['auth'] ?? 'token';  // default: usa Bearer token (endpoint autenticati)
        $timeout = (int) ($opts['timeout'] ?? self::DEFAULT_TIMEOUT);

        $headers = $this->buildHeaders($authMode);
        $args = [
            'method'  => $method,
            'headers' => $headers,
            'timeout' => $timeout,
            'redirection' => 2,
            'sslverify' => true,
        ];
        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $resp = wp_remote_request($url, $args);

        if (is_wp_error($resp)) {
            $msg = $resp->get_error_message();
            $this->logError("ApiClient {$method} {$endpoint}: network error: {$msg}");
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $this->translateNetworkError($msg)];
        }

        $status = (int) wp_remote_retrieve_response_code($resp);
        $rawBody = (string) wp_remote_retrieve_body($resp);
        $data = $this->parseJson($rawBody);

        // Auto-refresh + retry su 401 (token scaduto/non valido), solo se non e' un endpoint pubblico
        if ($status === 401 && $authMode === 'token' && !$this->hasRetried) {
            $refreshed = $this->tryRefreshToken();
            if ($refreshed) {
                $this->hasRetried = true;
                $result = $this->request($method, $endpoint, $body, $opts);
                $this->hasRetried = false;
                return $result;
            }
        }

        if ($status < 200 || $status >= 300) {
            $err = is_array($data) && isset($data['error'])
                ? (string) $data['error']
                : "HTTP {$status}";
            return ['ok' => false, 'status' => $status, 'data' => $data, 'error' => $err];
        }

        return ['ok' => true, 'status' => $status, 'data' => $data, 'error' => null];
    }

    private function buildUrl(string $endpoint): string
    {
        $base = rtrim(AIED_API_BASE, '/');
        $endpoint = '/' . ltrim($endpoint, '/');
        return $base . $endpoint;
    }

    /**
     * @return array<string,string>
     */
    private function buildHeaders(string $authMode): array
    {
        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'   => 'AinsteinEditorial/' . AIED_VERSION . ' WordPress/' . get_bloginfo('version'),
            'X-Site-Domain' => $this->siteDomain(),
        ];

        // License key (sempre disponibile se attivato; serve per /activate stesso e per auth-fallback)
        $licenseKey = (string) get_option('aied_license_key', '');
        if ($licenseKey !== '') {
            $headers['X-License-Key'] = $licenseKey;
        }

        // Bearer token (per endpoint authenticated)
        if ($authMode === 'token') {
            $token = (string) get_option('aied_api_token', '');
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
        }

        return $headers;
    }

    private function siteDomain(): string
    {
        $url = function_exists('home_url') ? home_url('/') : '';
        $parsed = parse_url($url);
        return strtolower((string) ($parsed['host'] ?? ''));
    }

    private function parseJson(string $raw): ?array
    {
        if ($raw === '') return null;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Tenta refresh del token via POST /refresh-token.
     * Usa direttamente wp_remote_post per evitare ricorsione su request().
     */
    private function tryRefreshToken(): bool
    {
        $licenseKey = (string) get_option('aied_license_key', '');
        if ($licenseKey === '') {
            return false;
        }

        $url = rtrim(AIED_API_BASE, '/') . '/refresh-token';
        $resp = wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-License-Key' => $licenseKey,
                'X-Site-Domain' => $this->siteDomain(),
            ],
            'body' => wp_json_encode([
                'license_key' => $licenseKey,
                'domain' => $this->siteDomain(),
            ]),
        ]);

        if (is_wp_error($resp)) {
            $this->logError('ApiClient refresh-token network error: ' . $resp->get_error_message());
            return false;
        }
        $status = (int) wp_remote_retrieve_response_code($resp);
        if ($status !== 200) {
            $this->logError("ApiClient refresh-token HTTP {$status}");
            return false;
        }
        $data = json_decode((string) wp_remote_retrieve_body($resp), true);
        if (!is_array($data) || empty($data['api_token'])) {
            return false;
        }

        update_option('aied_api_token', (string) $data['api_token'], false);
        $expiresIn = (int) ($data['expires_in'] ?? 86400);
        update_option('aied_api_token_expires_at', time() + $expiresIn, false);
        return true;
    }

    /**
     * Traduce error message tecnici in messaggi user-friendly italiano.
     * Usato solo nel campo 'error' di return (la UI li mostra in modale/notice).
     */
    private function translateNetworkError(string $msg): string
    {
        $msg = strtolower($msg);
        if (str_contains($msg, 'could not resolve host') || str_contains($msg, 'name resolution')) {
            return 'Impossibile contattare il server Ainstein. Verifica la connessione internet.';
        }
        if (str_contains($msg, 'timed out') || str_contains($msg, 'timeout')) {
            return 'Il server Ainstein non risponde in tempo. Riprova tra qualche secondo.';
        }
        if (str_contains($msg, 'ssl') || str_contains($msg, 'certificate')) {
            return 'Errore certificato SSL nella comunicazione con Ainstein. Contatta il supporto.';
        }
        return 'Errore di rete: impossibile contattare Ainstein in questo momento.';
    }

    private function logError(string $msg): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[ainstein-editorial] ' . $msg);
        }
    }
}
