<?php

namespace Editorial\Middleware;

use Core\Database;
use Editorial\Services\JwtService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * LicenseAuthMiddleware
 *
 * Verifica:
 *   - Header X-License-Key, X-Site-Domain, X-Api-Token presenti
 *   - X-Api-Token e' un JWT valido (firma + exp)
 *   - DB: aied_users + aied_sites esistono e sono attivi
 *   - X-Site-Domain coincide con il domain registrato per il site_id nel JWT
 *     (impedisce che un token attivo su test1.com venga usato su evil.com)
 *
 * Espone i dati caricati a controller via proprieta' static:
 *   LicenseAuthMiddleware::$currentUser
 *   LicenseAuthMiddleware::$currentSite
 *   LicenseAuthMiddleware::$currentAuth   (array {license_key, site_domain, api_token})
 *
 * Risposte di errore:
 *   401 + code=TOKEN_EXPIRED     → token scaduto, plugin auto-refresh
 *   401 + code=INVALID_TOKEN     → firma/payload non validi
 *   401 + code=DOMAIN_MISMATCH   → dominio non corrisponde
 *   401 + code=MISSING_HEADERS   → header obbligatori mancanti
 *   404 + code=USER_NOT_FOUND    → user/site non in DB (token "orfano")
 *   403 + code=SITE_INACTIVE     → site disattivato
 */
class LicenseAuthMiddleware
{
    /** @var array{id:int, email:string, license_key:string, tier:string, subscription_status:string}|null */
    public static ?array $currentUser = null;

    /** @var array{id:int, user_id:int, domain:string, status:string}|null */
    public static ?array $currentSite = null;

    /** @var array{license_key:string, site_domain:string, api_token:string}|null */
    public static ?array $currentAuth = null;

    /**
     * Esegue il check completo. Risponde 401/403/404 ed esce in caso di errore.
     *
     * @return array{license_key:string, site_domain:string, api_token:string}
     */
    public static function handle(): array
    {
        $licenseKey = self::header('X-License-Key');
        $siteDomain = self::header('X-Site-Domain');
        $apiToken   = self::header('X-Api-Token');

        $missing = [];
        if ($licenseKey === '') $missing[] = 'X-License-Key';
        if ($siteDomain === '') $missing[] = 'X-Site-Domain';
        if ($apiToken === '')   $missing[] = 'X-Api-Token';

        if (!empty($missing)) {
            self::respond(401, [
                'error' => 'Missing authentication',
                'code' => 'MISSING_HEADERS',
                'missing_headers' => $missing,
            ]);
        }

        // 1) Verifica JWT
        $jwt = new JwtService();
        try {
            $payload = $jwt->verify($apiToken);
        } catch (ExpiredException $e) {
            self::respond(401, ['error' => 'Token expired', 'code' => 'TOKEN_EXPIRED']);
        } catch (SignatureInvalidException $e) {
            self::respond(401, ['error' => 'Invalid token signature', 'code' => 'INVALID_TOKEN']);
        } catch (\Throwable $e) {
            self::respond(401, ['error' => 'Invalid token', 'code' => 'INVALID_TOKEN']);
        }

        // 2) Carica user + site dal DB
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT u.id AS user_id, u.email, u.license_key, u.tier, u.subscription_status,
                    s.id AS site_id, s.domain, s.status AS site_status
             FROM aied_users u
             JOIN aied_sites s ON s.user_id = u.id
             WHERE u.id = ? AND s.id = ?
             LIMIT 1"
        );
        $stmt->execute([$payload['uid'], $payload['sid']]);
        $row = $stmt->fetch();

        if (!$row) {
            self::respond(404, ['error' => 'User or site not found for this token', 'code' => 'USER_NOT_FOUND']);
        }

        if ($row['site_status'] !== 'active') {
            self::respond(403, [
                'error' => "Site is {$row['site_status']}",
                'code' => 'SITE_INACTIVE',
            ]);
        }

        // 3) Verifica match dominio (anti token-leak cross-domain)
        $expectedDomain = self::normalizeDomain((string) $row['domain']);
        $providedDomain = self::normalizeDomain($siteDomain);
        if ($expectedDomain !== $providedDomain) {
            self::respond(401, [
                'error' => 'Token-domain mismatch',
                'code' => 'DOMAIN_MISMATCH',
            ]);
        }

        // 4) Verifica match licenza (sanity check: license_key in header coincide con quella del user)
        if ((string) $row['license_key'] !== $licenseKey) {
            self::respond(401, [
                'error' => 'Token-license mismatch',
                'code' => 'LICENSE_MISMATCH',
            ]);
        }

        // 5) Update last_seen_at (best-effort, non blocca)
        try {
            $pdo->prepare("UPDATE aied_users SET last_seen_at = NOW() WHERE id = ?")
                ->execute([(int) $row['user_id']]);
        } catch (\Throwable $e) { /* ignore */ }

        // 6) Espone dati ai controller
        self::$currentUser = [
            'id' => (int) $row['user_id'],
            'email' => (string) $row['email'],
            'license_key' => (string) $row['license_key'],
            'tier' => (string) $row['tier'],
            'subscription_status' => (string) $row['subscription_status'],
        ];
        self::$currentSite = [
            'id' => (int) $row['site_id'],
            'user_id' => (int) $row['user_id'],
            'domain' => (string) $row['domain'],
            'status' => (string) $row['site_status'],
        ];
        self::$currentAuth = [
            'license_key' => $licenseKey,
            'site_domain' => $siteDomain,
            'api_token' => $apiToken,
        ];

        return self::$currentAuth;
    }

    /**
     * Normalizza dominio per confronto (host lowercase, no schema, no trailing slash).
     */
    private static function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') return '';
        $parsed = parse_url($domain);
        if (isset($parsed['host'])) {
            return strtolower((string) $parsed['host']);
        }
        $host = explode('/', $domain)[0];
        return strtolower($host);
    }

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
     * Emette JSON response e termina.
     */
    private static function respond(int $status, array $payload): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
