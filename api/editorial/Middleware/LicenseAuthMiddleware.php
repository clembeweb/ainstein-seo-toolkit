<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Middleware;

use Ainstein\Editorial\Http\HttpException;
use Ainstein\Editorial\Http\Request;
use Ainstein\Editorial\Services\JwtService;
use Core\Database;

/**
 * Autentica le richieste plugin → backend.
 *
 * Header richiesti: X-Api-Token (JWT), X-License-Key, X-Site-Domain.
 * Verifica il JWT, carica `aied_users` + `aied_sites` nel context della request.
 *
 * NON va applicato a /activate e /webhooks/* (vedi routes.php).
 */
class LicenseAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $token   = $request->header('x-api-token');
        $license = $request->header('x-license-key');
        $domain  = $request->header('x-site-domain');

        if (!$token || !$license || !$domain) {
            throw new HttpException(401, 'Autenticazione richiesta: header mancanti.');
        }

        $payload = (new JwtService())->verify($token);
        if ($payload === null) {
            throw new HttpException(401, 'Token scaduto o non valido. Riconnetti il plugin.', [
                'code' => 'token_invalid',
            ]);
        }

        $site = Database::fetch(
            'SELECT s.*, u.license_key, u.email, u.tier, u.subscription_status
               FROM aied_sites s
               JOIN aied_users u ON u.id = s.user_id
              WHERE s.id = ? AND s.api_token = ? AND s.status = "active"',
            [(int) ($payload['site_id'] ?? 0), $token]
        );

        if ($site === null) {
            throw new HttpException(401, 'Sito non riconosciuto o disattivato.', ['code' => 'site_unknown']);
        }

        if (!hash_equals((string) $site['license_key'], (string) $license)) {
            throw new HttpException(401, 'License key non corrispondente al sito.', ['code' => 'license_mismatch']);
        }

        // Aggiorna heartbeat (best effort, non bloccante)
        Database::execute('UPDATE aied_users SET last_seen_at = NOW() WHERE id = ?', [(int) $site['user_id']]);

        $request->context['site']    = $site;
        $request->context['user_id'] = (int) $site['user_id'];
        $request->context['site_id'] = (int) $site['id'];
        $request->context['tier']    = (string) $site['tier'];
    }
}
