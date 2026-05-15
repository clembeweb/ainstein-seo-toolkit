<?php

namespace Editorial\Services;

use Core\Database;
use Core\Logger;
use Editorial\Lib\LemonSqueezyClientFactory;
use Editorial\Lib\LemonSqueezyClientInterface;
use Editorial\Lib\LemonSqueezyException;
use RuntimeException;

/**
 * LicenseManager
 *
 * Orchestratore della logica di attivazione/deattivazione/refresh.
 * Coordina:
 *   - LemonSqueezyClient (validazione + activate/deactivate remoti)
 *   - DB (aied_users + aied_sites)
 *   - JwtService (emissione token)
 *
 * Convenzione errori: lancia RuntimeException con messaggio ITA-friendly
 * + codice (default 422 per errori di business). I controller intercettano
 * e mappano sulla risposta HTTP.
 */
class LicenseManager
{
    /**
     * Limiti hardcoded per tier — saranno config-driven in M3.
     * articles = numero articoli/mese inclusi nel piano.
     */
    public const TIER_LIMITS = [
        'starter'  => ['articles' => 4],
        'pro'      => ['articles' => 12],
        'business' => ['articles' => 30],
        'agency'   => ['articles' => 100],
    ];

    private LemonSqueezyClientInterface $ls;
    private JwtService $jwt;

    public function __construct(?LemonSqueezyClientInterface $ls = null, ?JwtService $jwt = null)
    {
        $this->ls = $ls ?? LemonSqueezyClientFactory::make();
        $this->jwt = $jwt ?? new JwtService();
    }

    /**
     * Attiva una licenza su un dominio. Crea/aggiorna user + site, emette JWT.
     *
     * @return array{
     *     api_token: string,
     *     expires_in: int,
     *     tier: string,
     *     sites_used: int,
     *     sites_limit: int,
     *     site_id: int,
     *     user: array{email:string}
     * }
     */
    public function activate(
        string $licenseKey,
        string $domain,
        string $wpVersion,
        string $pluginVersion,
        string $adminEmail
    ): array {
        $licenseKey = trim($licenseKey);
        $domain = $this->normalizeDomain($domain);
        $adminEmail = trim($adminEmail);

        if ($licenseKey === '' || $domain === '' || $adminEmail === '') {
            throw new RuntimeException('Missing required fields: license_key, domain, admin_email', 400);
        }

        // 1) Valida licenza su LS
        $info = $this->ls->validateLicense($licenseKey);
        if (empty($info['valid'])) {
            throw new RuntimeException('License key not valid', 422);
        }
        if (($info['status'] ?? '') !== 'active') {
            throw new RuntimeException("License is not active (status: {$info['status']})", 422);
        }

        $tier = (string) ($info['tier'] ?? 'starter');
        $maxInstances = (int) ($info['max_instances'] ?? 1);

        // 2) Carica/crea user
        $pdo = Database::getConnection();
        $userId = $this->upsertUser($pdo, $licenseKey, $tier, $info, $adminEmail);

        // 3) Verifica sito esistente per (user, domain)
        $site = $this->fetchSite($pdo, $userId, $domain);

        // 3a) Conta siti attivi del user (non deactivated)
        $activeCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM aied_sites WHERE user_id = {$userId} AND status = 'active'"
        )->fetchColumn();

        // Se il sito esiste e gia' attivo → riusa (rotazione token)
        if ($site && $site['status'] === 'active') {
            $this->ls->activateInstance($licenseKey, $domain); // idempotente
            $token = $this->jwt->issue((int) $userId, (int) $site['id']);
            $this->updateSiteToken($pdo, (int) $site['id'], $token, $wpVersion, $pluginVersion);
            return $this->buildActivationResponse($pdo, $userId, (int) $site['id'], $token, $tier, $maxInstances, $adminEmail);
        }

        // Se sito non esiste o e' deactivated → richiede slot disponibile
        $isReactivation = $site && $site['status'] !== 'active';
        $effectiveCount = $isReactivation ? $activeCount : $activeCount + 0; // riattivazione consuma comunque 1 nuovo slot
        if ($activeCount >= $maxInstances) {
            throw new RuntimeException(
                "Site limit reached. Your tier '{$tier}' allows {$maxInstances} site" . ($maxInstances === 1 ? '' : 's') . '.',
                422
            );
        }

        // 4) Chiama LS activate
        $act = $this->ls->activateInstance($licenseKey, $domain);
        if (empty($act['activated'])) {
            $err = $act['error'] ?? 'Lemon Squeezy refused activation';
            throw new RuntimeException($err, 422);
        }

        // 5) Insert/update sito
        $token = $this->jwt->issue((int) $userId, $site['id'] ?? 0); // placeholder, riemesso dopo insert
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);
        $settingsJson = json_encode([
            'lemon_squeezy_instance_id' => $act['instance_id'] ?? null,
        ], JSON_UNESCAPED_SLASHES);

        if ($site) {
            // Reactivate existing row
            $stmt = $pdo->prepare(
                "UPDATE aied_sites
                 SET wp_version = ?, plugin_version = ?, api_token = ?, api_token_expires_at = ?,
                     status = 'active', activated_at = ?, deactivated_at = NULL, settings = ?
                 WHERE id = ?"
            );
            $stmt->execute([$wpVersion, $pluginVersion, $token, $expiresAt, $now, $settingsJson, $site['id']]);
            $siteId = (int) $site['id'];
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO aied_sites
                    (user_id, domain, wp_version, plugin_version, api_token, api_token_expires_at, status, activated_at, settings)
                 VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?)"
            );
            $stmt->execute([$userId, $domain, $wpVersion, $pluginVersion, $token, $expiresAt, $now, $settingsJson]);
            $siteId = (int) $pdo->lastInsertId();
        }

        // 6) Riemissione JWT con sid corretto e aggiornamento DB
        $token = $this->jwt->issue((int) $userId, $siteId);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);
        $upd = $pdo->prepare("UPDATE aied_sites SET api_token = ?, api_token_expires_at = ? WHERE id = ?");
        $upd->execute([$token, $expiresAt, $siteId]);

        Logger::channel('editorial')->info('License activated', [
            'license_key' => $this->maskLicense($licenseKey),
            'user_id' => $userId,
            'site_id' => $siteId,
            'domain' => $domain,
            'tier' => $tier,
        ]);

        return $this->buildActivationResponse($pdo, $userId, $siteId, $token, $tier, $maxInstances, $adminEmail);
    }

    /**
     * Disattiva un sito (chiamata autenticata: il middleware ha gia' verificato JWT).
     *
     * @return array{success:bool, sites_freed:int}
     */
    public function deactivate(int $siteId, int $authUserId): array
    {
        if ($siteId <= 0) {
            throw new RuntimeException('Invalid site_id', 400);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT s.*, u.license_key
             FROM aied_sites s JOIN aied_users u ON u.id = s.user_id
             WHERE s.id = ?"
        );
        $stmt->execute([$siteId]);
        $site = $stmt->fetch();

        if (!$site) {
            throw new RuntimeException('Site not found', 404);
        }
        if ((int) $site['user_id'] !== $authUserId) {
            throw new RuntimeException('Forbidden — site does not belong to authenticated user', 403);
        }
        if ($site['status'] === 'deactivated') {
            return ['success' => true, 'sites_freed' => 0];
        }

        // Chiamata LS (best-effort: se LS fallisce, logghiamo ma deattiviamo localmente)
        $instanceId = null;
        if (!empty($site['settings'])) {
            $settings = json_decode((string) $site['settings'], true);
            if (is_array($settings)) {
                $instanceId = $settings['lemon_squeezy_instance_id'] ?? null;
            }
        }
        if ($instanceId) {
            try {
                $this->ls->deactivateInstance((string) $site['license_key'], (string) $instanceId);
            } catch (LemonSqueezyException $e) {
                Logger::channel('editorial')->warning('LS deactivate failed (continuing)', [
                    'site_id' => $siteId, 'err' => $e->getMessage(),
                ]);
            }
        }

        $pdo->prepare(
            "UPDATE aied_sites SET status = 'deactivated', deactivated_at = NOW(), api_token_expires_at = NOW() WHERE id = ?"
        )->execute([$siteId]);

        Logger::channel('editorial')->info('Site deactivated', ['site_id' => $siteId, 'user_id' => $authUserId]);

        return ['success' => true, 'sites_freed' => 1];
    }

    /**
     * Re-valida licenza ed emette nuovo JWT per (license_key, domain).
     *
     * @return array{api_token:string, expires_in:int, site_id:int}
     */
    public function refreshToken(string $licenseKey, string $domain): array
    {
        $licenseKey = trim($licenseKey);
        $domain = $this->normalizeDomain($domain);
        if ($licenseKey === '' || $domain === '') {
            throw new RuntimeException('Missing license_key or domain', 400);
        }

        $info = $this->ls->validateLicense($licenseKey);
        if (empty($info['valid'])) {
            throw new RuntimeException('License key not valid', 422);
        }
        if (($info['status'] ?? '') !== 'active') {
            throw new RuntimeException("License is not active (status: {$info['status']})", 422);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT s.id, s.status
             FROM aied_sites s JOIN aied_users u ON u.id = s.user_id
             WHERE u.license_key = ? AND s.domain = ?"
        );
        $stmt->execute([$licenseKey, $domain]);
        $site = $stmt->fetch();

        if (!$site) {
            throw new RuntimeException('Site not activated for this license/domain pair', 404);
        }
        if ($site['status'] !== 'active') {
            throw new RuntimeException('Site is not active (status: ' . $site['status'] . ')', 422);
        }

        // Risali a user_id
        $userIdStmt = $pdo->prepare("SELECT user_id FROM aied_sites WHERE id = ?");
        $userIdStmt->execute([(int) $site['id']]);
        $userId = (int) $userIdStmt->fetchColumn();

        $token = $this->jwt->issue($userId, (int) $site['id']);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);
        $pdo->prepare("UPDATE aied_sites SET api_token = ?, api_token_expires_at = ? WHERE id = ?")
            ->execute([$token, $expiresAt, (int) $site['id']]);

        return [
            'api_token' => $token,
            'expires_in' => 86400,
            'site_id' => (int) $site['id'],
        ];
    }

    // -------------------- Helpers --------------------

    private function upsertUser(\PDO $pdo, string $licenseKey, string $tier, array $info, string $adminEmail): int
    {
        $existing = $pdo->prepare("SELECT id FROM aied_users WHERE license_key = ? LIMIT 1");
        $existing->execute([$licenseKey]);
        $userId = $existing->fetchColumn();

        if ($userId) {
            $pdo->prepare(
                "UPDATE aied_users
                 SET email = COALESCE(NULLIF(?, ''), email),
                     tier = ?,
                     subscription_status = 'active',
                     lemon_squeezy_customer_id = COALESCE(?, lemon_squeezy_customer_id),
                     lemon_squeezy_subscription_id = COALESCE(?, lemon_squeezy_subscription_id),
                     subscription_renews_at = COALESCE(?, subscription_renews_at),
                     last_seen_at = NOW()
                 WHERE id = ?"
            )->execute([
                $adminEmail,
                $tier,
                $info['customer_id'] ?? null,
                $info['subscription_id'] ?? null,
                isset($info['renews_at']) && $info['renews_at']
                    ? gmdate('Y-m-d H:i:s', strtotime((string) $info['renews_at']))
                    : null,
                $userId,
            ]);
            return (int) $userId;
        }

        $pdo->prepare(
            "INSERT INTO aied_users
                (email, license_key, lemon_squeezy_customer_id, lemon_squeezy_subscription_id,
                 tier, subscription_status, subscription_renews_at, locale, created_at, last_seen_at)
             VALUES (?, ?, ?, ?, ?, 'active', ?, 'it', NOW(), NOW())"
        )->execute([
            $adminEmail,
            $licenseKey,
            $info['customer_id'] ?? null,
            $info['subscription_id'] ?? null,
            $tier,
            isset($info['renews_at']) && $info['renews_at']
                ? gmdate('Y-m-d H:i:s', strtotime((string) $info['renews_at']))
                : null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    private function fetchSite(\PDO $pdo, int $userId, string $domain): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM aied_sites WHERE user_id = ? AND domain = ? LIMIT 1");
        $stmt->execute([$userId, $domain]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function updateSiteToken(\PDO $pdo, int $siteId, string $token, string $wpVersion, string $pluginVersion): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);
        $pdo->prepare(
            "UPDATE aied_sites
             SET api_token = ?, api_token_expires_at = ?, wp_version = ?, plugin_version = ?
             WHERE id = ?"
        )->execute([$token, $expiresAt, $wpVersion, $pluginVersion, $siteId]);
    }

    private function buildActivationResponse(
        \PDO $pdo,
        int $userId,
        int $siteId,
        string $token,
        string $tier,
        int $maxInstances,
        string $adminEmail
    ): array {
        $sitesUsed = (int) $pdo->query(
            "SELECT COUNT(*) FROM aied_sites WHERE user_id = {$userId} AND status = 'active'"
        )->fetchColumn();

        return [
            'api_token' => $token,
            'expires_in' => 86400,
            'tier' => $tier,
            'sites_used' => $sitesUsed,
            'sites_limit' => $maxInstances,
            'site_id' => $siteId,
            'user' => ['email' => $adminEmail],
        ];
    }

    /**
     * Normalizza il dominio per garantire match consistente.
     * "https://Test1.com/path/" → "test1.com"
     * "http://localhost:8080/wp-admin" → "localhost:8080"
     *
     * Preserva port (necessario per dev locali e installazioni WP multi-tenant su porte diverse).
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') return '';

        // Se c'e' uno schema (http://, https://), estrai host+port via parse_url
        if (preg_match('#^https?://#i', $domain)) {
            $parsed = parse_url($domain);
            $host = (string) ($parsed['host'] ?? '');
            if ($host !== '' && isset($parsed['port'])) {
                $host .= ':' . (int) $parsed['port'];
            }
        } else {
            // Stringa "host[:port]" o "host[:port]/path" — prendi tutto fino al primo '/'
            $host = explode('/', $domain)[0];
        }

        return strtolower(trim($host));
    }

    private function maskLicense(string $key): string
    {
        if (strlen($key) <= 8) return str_repeat('*', strlen($key));
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }
}
