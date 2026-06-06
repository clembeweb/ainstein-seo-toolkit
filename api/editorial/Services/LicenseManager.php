<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Services;

use Ainstein\Editorial\Http\HttpException;
use Ainstein\Editorial\Lib\LemonSqueezyClient;
use Core\Database;

/**
 * Logica di attivazione/disattivazione license e gestione account plugin.
 *
 * Lemon Squeezy è single source of truth (Golden Rule #4 plugin): la license
 * viene sempre validata via LS. Il backend tiene solo cache locale (aied_users,
 * aied_sites). Il tier è derivato dall'activation_limit della license LS.
 */
class LicenseManager
{
    private LemonSqueezyClient $ls;
    private JwtService $jwt;

    /** activation_limit LS → tier */
    private const LIMIT_TO_TIER = [
        1  => 'starter',
        3  => 'pro',
        10 => 'business',
        50 => 'agency',
    ];

    public function __construct(?LemonSqueezyClient $ls = null, ?JwtService $jwt = null)
    {
        $this->ls  = $ls ?? new LemonSqueezyClient();
        $this->jwt = $jwt ?? new JwtService();
    }

    /**
     * Attiva il plugin su un dominio. Idempotente per (license, domain).
     *
     * @return array{api_token:string,expires_in:int,tier:string,sites_remaining:int,user:array{email:string}}
     */
    public function activate(
        string $licenseKey,
        string $domain,
        ?string $wpVersion,
        ?string $pluginVersion,
        string $adminEmail
    ): array {
        $domain = $this->normalizeDomain($domain);

        // 1. Valida la license con Lemon Squeezy
        [$status, $body] = $this->ls->validateLicense($licenseKey);
        $this->logLs('/licenses/validate', $status, $body);

        if ($status === 0) {
            throw new HttpException(503, 'Servizio di licenze non raggiungibile. Riprova tra poco.');
        }
        if (empty($body['valid'])) {
            $msg = $body['error'] ?? 'License key non valida.';
            throw new HttpException(422, is_string($msg) ? $msg : 'License key non valida.', ['code' => 'license_invalid']);
        }

        $lk            = $body['license_key'] ?? [];
        $meta          = $body['meta'] ?? [];
        $activationLimit = (int) ($lk['activation_limit'] ?? 1);
        $tier          = self::LIMIT_TO_TIER[$activationLimit] ?? 'starter';

        // 2. Trova o crea l'utente
        $user = Database::fetch('SELECT * FROM aied_users WHERE license_key = ?', [$licenseKey]);
        if ($user === null) {
            $userId = Database::insert('aied_users', [
                'email'                         => $adminEmail,
                'license_key'                   => $licenseKey,
                'lemon_squeezy_customer_id'     => $meta['customer_id'] ?? null,
                'lemon_squeezy_subscription_id' => $meta['subscription_id'] ?? null,
                'tier'                          => $tier,
                'subscription_status'           => 'active',
            ]);
            $user = Database::fetch('SELECT * FROM aied_users WHERE id = ?', [$userId]);
        } elseif (($user['tier'] ?? null) !== $tier) {
            Database::update('aied_users', ['tier' => $tier], 'id = ?', [(int) $user['id']]);
            $user['tier'] = $tier;
        }
        $userId = (int) $user['id'];

        // 3. Sito già attivo per questo dominio → idempotente: riemetti token
        $existing = Database::fetch(
            'SELECT * FROM aied_sites WHERE user_id = ? AND domain = ?',
            [$userId, $domain]
        );

        if ($existing !== null && $existing['status'] === 'active') {
            return $this->reissueForSite((int) $existing['id'], $userId, $tier, $domain, $licenseKey, $wpVersion, $pluginVersion, (string) $user['email']);
        }

        // 4. Verifica limite siti (slot attivi vs activation_limit)
        $activeSites = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM aied_sites WHERE user_id = ? AND status = "active"',
            [$userId]
        );
        if ($existing === null && $activeSites >= $activationLimit) {
            throw new HttpException(422, 'Hai raggiunto il numero massimo di siti per il tuo piano (' . $activationLimit . ').', [
                'code'  => 'site_limit_reached',
                'limit' => $activationLimit,
            ]);
        }

        // 5. Attiva istanza su Lemon Squeezy
        [$aStatus, $aBody] = $this->ls->activateLicense($licenseKey, $domain);
        $this->logLs('/licenses/activate', $aStatus, $aBody);
        $instanceId = $aBody['instance']['id'] ?? null;
        if (empty($aBody['activated']) && $existing === null) {
            $msg = $aBody['error'] ?? 'Attivazione license non riuscita.';
            throw new HttpException(422, is_string($msg) ? $msg : 'Attivazione license non riuscita.', ['code' => 'ls_activation_failed']);
        }

        // 6. Crea/riattiva il sito + emetti JWT
        if ($existing !== null) {
            $siteId = (int) $existing['id'];
            Database::update('aied_sites', [
                'status'         => 'active',
                'wp_version'     => $wpVersion,
                'plugin_version' => $pluginVersion,
                'ls_instance_id' => $instanceId,
                'deactivated_at' => null,
            ], 'id = ?', [$siteId]);
        } else {
            $siteId = Database::insert('aied_sites', [
                'user_id'        => $userId,
                'domain'         => $domain,
                'wp_version'     => $wpVersion,
                'plugin_version' => $pluginVersion,
                'api_token'      => 'pending-' . bin2hex(random_bytes(16)),
                'ls_instance_id' => $instanceId,
                'status'         => 'active',
            ]);
        }

        return $this->reissueForSite($siteId, $userId, $tier, $domain, $licenseKey, $wpVersion, $pluginVersion, (string) $user['email']);
    }

    /**
     * Disattiva un sito, liberando lo slot license su Lemon Squeezy.
     *
     * @return array{success:bool,sites_freed:int}
     */
    public function deactivate(int $siteId, int $userId): array
    {
        $site = Database::fetch('SELECT * FROM aied_sites WHERE id = ? AND user_id = ?', [$siteId, $userId]);
        if ($site === null) {
            throw new HttpException(404, 'Sito non trovato.');
        }

        $user = Database::fetch('SELECT license_key FROM aied_users WHERE id = ?', [$userId]);
        if ($user !== null && !empty($site['ls_instance_id'])) {
            [$status, $body] = $this->ls->deactivateLicense((string) $user['license_key'], (string) $site['ls_instance_id']);
            $this->logLs('/licenses/deactivate', $status, $body);
        }

        Database::update('aied_sites', [
            'status'         => 'deactivated',
            'deactivated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$siteId]);

        return ['success' => true, 'sites_freed' => 1];
    }

    /**
     * Rinnova l'api_token di un sito esistente riconfermando la license.
     *
     * @return array{api_token:string,expires_in:int}
     */
    public function refreshToken(string $licenseKey, string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        [$status, $body] = $this->ls->validateLicense($licenseKey);
        $this->logLs('/licenses/validate', $status, $body);
        if ($status === 0) {
            throw new HttpException(503, 'Servizio di licenze non raggiungibile. Riprova tra poco.');
        }
        if (empty($body['valid'])) {
            throw new HttpException(422, 'License key non più valida.', ['code' => 'license_invalid']);
        }

        $site = Database::fetch(
            'SELECT s.* FROM aied_sites s JOIN aied_users u ON u.id = s.user_id
              WHERE u.license_key = ? AND s.domain = ? AND s.status = "active"',
            [$licenseKey, $domain]
        );
        if ($site === null) {
            throw new HttpException(404, 'Sito non attivo. Riattiva il plugin.', ['code' => 'site_unknown']);
        }

        $issued = $this->jwt->issue(['user_id' => (int) $site['user_id'], 'site_id' => (int) $site['id']]);
        Database::update('aied_sites', [
            'api_token'            => $issued['token'],
            'api_token_expires_at' => date('Y-m-d H:i:s', $issued['expires_at']),
        ], 'id = ?', [(int) $site['id']]);

        return ['api_token' => $issued['token'], 'expires_in' => $issued['expires_in']];
    }

    // ----------------------------------------------------------------

    /**
     * Emette un nuovo JWT per il sito e ritorna la response di attivazione.
     *
     * @return array{api_token:string,expires_in:int,tier:string,sites_remaining:int,user:array{email:string}}
     */
    private function reissueForSite(
        int $siteId,
        int $userId,
        string $tier,
        string $domain,
        string $licenseKey,
        ?string $wpVersion,
        ?string $pluginVersion,
        string $email
    ): array {
        $issued = $this->jwt->issue(['user_id' => $userId, 'site_id' => $siteId]);
        Database::update('aied_sites', [
            'api_token'            => $issued['token'],
            'api_token_expires_at' => date('Y-m-d H:i:s', $issued['expires_at']),
            'wp_version'           => $wpVersion,
            'plugin_version'       => $pluginVersion,
        ], 'id = ?', [$siteId]);

        $limit = array_search($tier, self::LIMIT_TO_TIER, true);
        $limit = is_int($limit) ? $limit : 1;
        $activeSites = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM aied_sites WHERE user_id = ? AND status = "active"',
            [$userId]
        );

        return [
            'api_token'       => $issued['token'],
            'expires_in'      => $issued['expires_in'],
            'tier'            => $tier,
            'sites_remaining' => max(0, $limit - $activeSites),
            'user'            => ['email' => $email],
        ];
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        // Normalizza a host senza schema/trailing slash per coerenza con uniq_user_domain
        $parsed = parse_url($domain);
        if (isset($parsed['host'])) {
            $domain = $parsed['host'] . (isset($parsed['path']) ? rtrim($parsed['path'], '/') : '');
        }
        return rtrim($domain, '/');
    }

    /** @param array<string,mixed> $body */
    private function logLs(string $endpoint, int $status, array $body): void
    {
        try {
            Database::insert('aied_api_logs', [
                'provider'         => 'lemonsqueezy',
                'endpoint'         => $endpoint,
                'response_status'  => $status,
                'response_summary' => substr(json_encode($body, JSON_UNESCAPED_UNICODE) ?: '', 0, 1000),
            ]);
        } catch (\Throwable) {
            // logging best-effort, non deve mai bloccare l'attivazione
        }
    }
}
