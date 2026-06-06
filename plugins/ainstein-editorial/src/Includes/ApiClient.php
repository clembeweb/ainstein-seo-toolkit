<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Includes;

use Ainstein\Editorial\Utils\Logger;
use WP_Error;

/**
 * Client HTTP verso il backend Ainstein Editorial.
 *
 * Aggiunge automaticamente gli header di autenticazione (X-License-Key,
 * X-Site-Domain, X-Api-Token) quando disponibili. Su 401 tenta un refresh
 * del token e riprova una volta. Errori → WP_Error con messaggio italiano.
 */
class ApiClient
{
    private string $base;
    private bool $autoRefresh;

    public function __construct(bool $autoRefresh = true)
    {
        $base = defined('AIED_API_BASE') ? AIED_API_BASE : '';
        /** @var string $base */
        $this->base = rtrim((string) apply_filters('aied_api_base', $base), '/');
        $this->autoRefresh = $autoRefresh;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>|WP_Error
     */
    public function post(string $endpoint, array $payload = [], bool $withAuth = true): array|WP_Error
    {
        return $this->request('POST', $endpoint, $payload, $withAuth);
    }

    /**
     * @return array<string,mixed>|WP_Error
     */
    public function get(string $endpoint, bool $withAuth = true): array|WP_Error
    {
        return $this->request('GET', $endpoint, null, $withAuth);
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return array<string,mixed>|WP_Error
     */
    private function request(string $method, string $endpoint, ?array $payload, bool $withAuth, bool $isRetry = false): array|WP_Error
    {
        $args = [
            'method'  => $method,
            'timeout' => 300, // generazioni lunghe (M3+)
            'headers' => $this->headers($withAuth),
        ];
        if ($payload !== null) {
            $args['body'] = wp_json_encode($payload);
        }

        $url      = $this->base . '/' . ltrim($endpoint, '/');
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            Logger::error('Richiesta API fallita', ['endpoint' => $endpoint, 'error' => $response->get_error_message()]);
            return new WP_Error('aied_connection', 'Impossibile contattare il servizio Ainstein Editorial. Verifica la connessione e riprova.');
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            $body = [];
        }

        // 401 → refresh token + retry una volta
        if ($code === 401 && $withAuth && $this->autoRefresh && !$isRetry) {
            if ((new LicenseManager())->refresh()) {
                return $this->request($method, $endpoint, $payload, $withAuth, true);
            }
        }

        if ($code >= 200 && $code < 300) {
            return $body;
        }

        $message = isset($body['error']) && is_string($body['error'])
            ? $body['error']
            : 'Si è verificato un errore (HTTP ' . $code . ').';

        return new WP_Error('aied_http_' . $code, $message, ['status' => $code, 'body' => $body]);
    }

    /**
     * @return array<string,string>
     */
    private function headers(bool $withAuth): array
    {
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        if ($withAuth) {
            $license = (string) get_option('aied_license_key', '');
            $token   = (string) get_option('aied_api_token', '');
            if ($license !== '') {
                $headers['X-License-Key'] = $license;
            }
            if ($token !== '') {
                $headers['X-Api-Token'] = $token;
            }
            $headers['X-Site-Domain'] = home_url();
        }
        return $headers;
    }
}
