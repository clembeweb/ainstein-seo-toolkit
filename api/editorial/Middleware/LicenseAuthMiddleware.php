<?php

namespace Editorial\Middleware;

/**
 * LicenseAuthMiddleware
 *
 * STUB per M1.3: verifica solo la presenza degli header richiesti.
 * Implementazione completa (validazione JWT, lookup license_key, etc.)
 * arrivera in M1.4 con ActivationController.
 *
 * Headers attesi:
 *   - X-License-Key: chiave licenza emessa al momento dell'attivazione
 *   - X-Site-Domain: dominio del sito WordPress chiamante
 *   - X-Api-Token: JWT a breve scadenza ottenuto via /activate
 *
 * Endpoint pubblici (activate, deactivate, refresh-token, webhooks)
 * NON devono chiamare questo middleware.
 */
class LicenseAuthMiddleware
{
    /**
     * Esegue il check. Risponde 401 ed esce se gli header mancano.
     *
     * @return array{license_key:string, site_domain:string, api_token:string}
     *         Header validati (utili al controller chiamante).
     */
    public static function handle(): array
    {
        $licenseKey = self::header('X-License-Key');
        $siteDomain = self::header('X-Site-Domain');
        $apiToken   = self::header('X-Api-Token');

        $missing = [];
        if ($licenseKey === '') {
            $missing[] = 'X-License-Key';
        }
        if ($siteDomain === '') {
            $missing[] = 'X-Site-Domain';
        }
        if ($apiToken === '') {
            $missing[] = 'X-Api-Token';
        }

        if (!empty($missing)) {
            self::respondUnauthorized('Missing authentication', $missing);
        }

        // TODO M1.4: validare JWT, caricare license dal DB, verificare site_domain match.
        return [
            'license_key' => $licenseKey,
            'site_domain' => $siteDomain,
            'api_token'   => $apiToken,
        ];
    }

    /**
     * Legge un header HTTP in modo case-insensitive.
     */
    private static function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$key])) {
            return trim((string) $_SERVER[$key]);
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $k => $v) {
                    if (strcasecmp($k, $name) === 0) {
                        return trim((string) $v);
                    }
                }
            }
        }
        return '';
    }

    /**
     * Emette 401 Unauthorized e termina.
     */
    private static function respondUnauthorized(string $message, array $missing = []): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['error' => $message];
        if (!empty($missing)) {
            $payload['missing_headers'] = $missing;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
