<?php

namespace Controllers;

use Core\Router;
use Core\View;
use Core\Middleware;
use Services\GoogleOAuthService;

/**
 * OAuthController - Gestisce callback OAuth centralizzati
 */
class OAuthController
{
    /**
     * Callback OAuth Google
     * GET /oauth/google/callback
     *
     * Riceve il code da Google, scambia per tokens,
     * e redirige al modulo che ha iniziato il flusso OAuth.
     */
    public function googleCallback(): string
    {
        Middleware::auth();

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        // Errore da Google (es: user ha annullato)
        if ($error) {
            $_SESSION['_flash']['error'] = 'Autorizzazione Google annullata: ' . $error;
            Router::redirect('/dashboard');
            return '';
        }

        // Parametri mancanti
        if (!$code || !$state) {
            $_SESSION['_flash']['error'] = 'Parametri OAuth mancanti';
            Router::redirect('/dashboard');
            return '';
        }

        $oauth = new GoogleOAuthService();

        // Verifica state (CSRF + decode module/project)
        $stateData = $oauth->verifyState($state);
        if (!$stateData) {
            $_SESSION['_flash']['error'] = 'State OAuth non valido o scaduto';
            Router::redirect('/dashboard');
            return '';
        }

        $moduleSlug = $stateData['module'];
        $projectId = $stateData['project_id'];
        $type = $stateData['type'] ?? 'gsc'; // Default a GSC per retrocompatibilita

        // Scambia code per tokens
        $tokens = $oauth->exchangeCode($code);

        if (isset($tokens['error'])) {
            $_SESSION['_flash']['error'] = 'Errore OAuth: ' . $tokens['message'];
            Router::redirect("/{$moduleSlug}/projects/{$projectId}");
            return '';
        }

        // Salva tokens in sessione temporanea per il modulo
        // Il modulo leggerà questi dati e li salverà nel proprio DB
        $_SESSION['google_oauth_tokens'] = [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'project_id' => $projectId,
            'type' => $type,
        ];

        // Redirect al modulo per completare la connessione
        // GSC: /{module}/gsc/connected
        // GA4: /{module}/ga4/connected
        $endpoint = ($type === 'ga4') ? 'ga4' : 'gsc';
        Router::redirect("/{$moduleSlug}/{$endpoint}/connected");
        return '';
    }
}
