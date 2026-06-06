<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Controllers;

use Ainstein\Editorial\Http\Request;
use Ainstein\Editorial\Services\LicenseManager;
use Core\Database;

/**
 * Endpoint di attivazione/disattivazione/refresh del plugin.
 * NON protetti da LicenseAuthMiddleware (sono il punto di ingresso).
 */
class ActivationController extends BaseController
{
    public function activate(Request $request): never
    {
        $this->validate($request, ['license_key', 'domain', 'admin_email']);

        $result = (new LicenseManager())->activate(
            (string) $request->input('license_key'),
            (string) $request->input('domain'),
            $request->input('wp_version') !== null ? (string) $request->input('wp_version') : null,
            $request->input('plugin_version') !== null ? (string) $request->input('plugin_version') : null,
            (string) $request->input('admin_email')
        );

        $this->ok($result);
    }

    public function deactivate(Request $request): never
    {
        $this->validate($request, ['license_key', 'site_id']);

        // Per disattivare servono license + site_id: ricaviamo lo user dalla license.
        $user = Database::fetch('SELECT id FROM aied_users WHERE license_key = ?', [(string) $request->input('license_key')]);
        if ($user === null) {
            $this->ok(['success' => false, 'error' => 'License non trovata.'], 404);
        }

        $result = (new LicenseManager())->deactivate(
            (int) $request->input('site_id'),
            (int) $user['id']
        );

        $this->ok($result);
    }

    public function refreshToken(Request $request): never
    {
        $this->validate($request, ['license_key', 'domain']);

        $result = (new LicenseManager())->refreshToken(
            (string) $request->input('license_key'),
            (string) $request->input('domain')
        );

        $this->ok($result);
    }
}
