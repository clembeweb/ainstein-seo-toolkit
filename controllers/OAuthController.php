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
        ];

        // Redirect al modulo per completare la connessione
        // Ogni modulo ha il proprio endpoint /gsc/connected che:
        // 1. Legge $_SESSION['google_oauth_tokens']
        // 2. Salva i tokens nel proprio database
        // 3. Pulisce la sessione
        Router::redirect("/{$moduleSlug}/gsc/connected");
        return '';
    }
}
