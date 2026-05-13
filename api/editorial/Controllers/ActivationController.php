<?php

namespace Editorial\Controllers;

use Editorial\Middleware\LicenseAuthMiddleware;
use Editorial\Services\LicenseManager;
use Editorial\Lib\LemonSqueezyException;
use RuntimeException;

/**
 * ActivationController
 *
 * Endpoint pubblici (no LicenseAuthMiddleware tranne /deactivate):
 *   POST /activate         crea/aggiorna sito + emette JWT
 *   POST /deactivate       libera uno slot sito (richiede auth)
 *   POST /refresh-token    riemette JWT per (license_key, domain)
 *
 * Implementazione M1.4.
 */
class ActivationController extends BaseController
{
    /** POST /activate */
    public function activate(): string
    {
        $input = $this->jsonInput();

        $licenseKey   = (string) ($input['license_key']     ?? '');
        $domain       = (string) ($input['domain']          ?? '');
        $wpVersion    = (string) ($input['wp_version']      ?? '');
        $pluginVersion = (string) ($input['plugin_version'] ?? '');
        $adminEmail   = (string) ($input['admin_email']     ?? '');

        $missing = [];
        if ($licenseKey === '')   $missing[] = 'license_key';
        if ($domain === '')       $missing[] = 'domain';
        if ($wpVersion === '')    $missing[] = 'wp_version';
        if ($pluginVersion === '') $missing[] = 'plugin_version';
        if ($adminEmail === '')   $missing[] = 'admin_email';

        if (!empty($missing)) {
            return $this->jsonError('Missing required fields', 400, ['missing' => $missing]);
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError('Invalid admin_email', 400);
        }

        try {
            $result = (new LicenseManager())->activate(
                $licenseKey, $domain, $wpVersion, $pluginVersion, $adminEmail
            );
            return $this->jsonOk($result, 200);
        } catch (RuntimeException $e) {
            $code = $e->getCode() ?: 422;
            return $this->jsonError($e->getMessage(), $code);
        } catch (LemonSqueezyException $e) {
            return $this->jsonError('License provider error: ' . $e->getMessage(), 502);
        } catch (\Throwable $e) {
            \Core\Logger::channel('editorial')->error('Activation failure', ['err' => $e->getMessage()]);
            return $this->jsonError('Internal error during activation', 500);
        }
    }

    /**
     * POST /deactivate
     *
     * Endpoint pubblico nelle routes ma DEVE essere autenticato:
     * il middleware non e' agganciato in routes.php per evitare loop sui token
     * "morti", quindi qui invochiamo manualmente LicenseAuthMiddleware::handle().
     */
    public function deactivate(): string
    {
        // Auth manuale (routes.php usa $editorialPublic per questo endpoint)
        LicenseAuthMiddleware::handle();
        $user = LicenseAuthMiddleware::$currentUser;

        $input = $this->jsonInput();
        $siteId = (int) ($input['site_id'] ?? 0);
        if ($siteId <= 0) {
            return $this->jsonError('Missing or invalid site_id', 400);
        }

        try {
            $result = (new LicenseManager())->deactivate($siteId, (int) $user['id']);
            return $this->jsonOk($result, 200);
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 422);
        } catch (LemonSqueezyException $e) {
            return $this->jsonError('License provider error: ' . $e->getMessage(), 502);
        } catch (\Throwable $e) {
            \Core\Logger::channel('editorial')->error('Deactivation failure', ['err' => $e->getMessage()]);
            return $this->jsonError('Internal error during deactivation', 500);
        }
    }

    /** POST /refresh-token */
    public function refreshToken(): string
    {
        $input = $this->jsonInput();
        $licenseKey = (string) ($input['license_key'] ?? '');
        $domain = (string) ($input['domain'] ?? '');

        if ($licenseKey === '' || $domain === '') {
            return $this->jsonError('Missing license_key or domain', 400);
        }

        try {
            $result = (new LicenseManager())->refreshToken($licenseKey, $domain);
            return $this->jsonOk($result, 200);
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 422);
        } catch (LemonSqueezyException $e) {
            return $this->jsonError('License provider error: ' . $e->getMessage(), 502);
        } catch (\Throwable $e) {
            \Core\Logger::channel('editorial')->error('Refresh failure', ['err' => $e->getMessage()]);
            return $this->jsonError('Internal error during refresh', 500);
        }
    }
}
