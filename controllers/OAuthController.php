<?php

namespace Controllers;

use Core\Auth;
use Core\Database;
use Core\Router;
use Core\Middleware;
use Services\GoogleOAuthService;

/**
 * OAuthController - Gestisce callback OAuth centralizzati
 *
 * Gestisce sia il flusso login/registrazione che il flusso GSC/GA4 moduli.
 * Discrimina tramite il campo 'action' nello state:
 * - action=login → flusso autenticazione
 * - altrimenti → flusso modulo (richiede auth)
 */
class OAuthController
{
    /**
     * Callback OAuth Google
     * GET /oauth/google/callback
     */
    public function googleCallback(): string
    {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        // Errore da Google (es: user ha annullato)
        if ($error) {
            $_SESSION['_flash']['error'] = 'Autorizzazione Google annullata.';
            Router::redirect('/login');
            return '';
        }

        // Parametri mancanti
        if (!$code || !$state) {
            $_SESSION['_flash']['error'] = 'Parametri OAuth mancanti.';
            Router::redirect('/login');
            return '';
        }

        $oauth = new GoogleOAuthService();

        // 1. Prova flusso LOGIN/REGISTRAZIONE (utente non autenticato)
        $loginState = $oauth->verifyLoginState($state);
        if ($loginState) {
            return $this->handleLoginCallback($oauth, $code, $loginState);
        }

        // 2. Flusso MODULO (GSC/GA4) - richiede autenticazione
        Middleware::auth();

        $stateData = $oauth->verifyState($state);
        if (!$stateData) {
            $_SESSION['_flash']['error'] = 'State OAuth non valido o scaduto';
            Router::redirect('/dashboard');
            return '';
        }

        $moduleSlug = $stateData['module'];
        $projectId = $stateData['project_id'];
        $type = $stateData['type'] ?? 'gsc';

        // Scambia code per tokens
        $tokens = $oauth->exchangeCode($code);

        if (isset($tokens['error'])) {
            $_SESSION['_flash']['error'] = 'Errore OAuth: ' . $tokens['message'];
            Router::redirect("/{$moduleSlug}/projects/{$projectId}");
            return '';
        }

        // Salva tokens in sessione temporanea per il modulo
        $_SESSION['google_oauth_tokens'] = [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'project_id' => $projectId,
            'type' => $type,
        ];

        // Redirect al modulo per completare la connessione
        $endpoint = ($type === 'ga4') ? 'ga4' : 'gsc';
        Router::redirect("/{$moduleSlug}/{$endpoint}/connected");
        return '';
    }

    /**
     * Gestisce callback per login/registrazione con Google
     */
    private function handleLoginCallback(GoogleOAuthService $oauth, string $code, array $stateData): string
    {
        // Scambia code per token
        $tokens = $oauth->exchangeLoginCode($code);
        if (isset($tokens['error'])) {
            $_SESSION['_flash']['error'] = 'Errore autenticazione Google: ' . $tokens['message'];
            Router::redirect('/login');
            return '';
        }

        // Ottieni info utente da Google
        $googleUser = $oauth->getUserInfo($tokens['access_token']);
        if (isset($googleUser['error'])) {
            $_SESSION['_flash']['error'] = 'Impossibile ottenere i dati del profilo Google.';
            Router::redirect('/login');
            return '';
        }

        // Verifica email confermata
        if (empty($googleUser['email_verified'])) {
            $_SESSION['_flash']['error'] = 'L\'email Google non è verificata. Verifica la tua email e riprova.';
            Router::redirect('/login');
            return '';
        }

        // Trova o crea utente
        $user = Auth::findOrCreateFromGoogle($googleUser);
        if (!$user) {
            $_SESSION['_flash']['error'] = 'Errore durante la creazione dell\'account.';
            Router::redirect('/login');
            return '';
        }

        // Login
        Auth::login($user, true);

        // Redirect
        $intended = $stateData['intended'] ?? '/dashboard';
        $_SESSION['_flash']['success'] = 'Benvenuto, ' . htmlspecialchars($user['name'] ?? 'utente') . '!';
        Router::redirect($intended);
        return '';
    }
}
