<?php

namespace Services;

use Core\Database;
use Core\Settings;

/**
 * GoogleOAuthService - Servizio centralizzato per OAuth2 Google
 *
 * Gestisce autenticazione OAuth per Google Search Console e GA4.
 * Usato da: seo-audit, seo-tracking (e futuri moduli)
 */
class GoogleOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    // Scope disponibili
    public const SCOPE_GSC = 'https://www.googleapis.com/auth/webmasters.readonly';
    public const SCOPE_GA4 = 'https://www.googleapis.com/auth/analytics.readonly';
    public const SCOPE_LOGIN = 'openid email profile';

    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $appUrl = null;

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Carica credenziali OAuth da settings globali
     */
    private function loadSettings(): void
    {
        $this->clientId = Settings::get('gsc_client_id', '');
        $this->clientSecret = Settings::get('gsc_client_secret', '');

        // Carica APP_URL da config
        $configFile = dirname(__DIR__) . '/config/app.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $this->appUrl = rtrim($config['url'] ?? 'http://localhost', '/');
    }

    /**
     * Verifica se OAuth è configurato
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Redirect URI centralizzato - generato dinamicamente dal request corrente
     */
    public function getRedirectUri(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Determina il base path (es: /seo-toolkit se presente)
        // Esclude /public che è la cartella pubblica, non un base path
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (preg_match('#^(/[^/]+)/(?:public/)?#', $scriptName, $matches)) {
            // Solo se non è /public come primo segmento
            if ($matches[1] !== '/public') {
                $basePath = $matches[1];
            }
        }

        return $scheme . '://' . $host . $basePath . '/oauth/google/callback';
    }

    /**
     * Genera URL per OAuth consent
     *
     * @param string $moduleSlug es: 'seo-audit', 'seo-tracking'
     * @param int $projectId ID progetto del modulo
     * @param string $scope Scope richiesto (default: GSC readonly)
     * @param string $type Tipo connessione: 'gsc' o 'ga4' (per redirect dopo callback)
     * @return string URL per redirect a Google
     */
    public function getAuthUrl(string $moduleSlug, int $projectId, string $scope = self::SCOPE_GSC, string $type = 'gsc'): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Google OAuth non configurato. Configura Client ID e Secret in Admin > Impostazioni.');
        }

        // State contiene info per il callback
        $state = base64_encode(json_encode([
            'module' => $moduleSlug,
            'project_id' => $projectId,
            'type' => $type,
            'csrf' => bin2hex(random_bytes(16)),
        ]));

        // Salva state in sessione per verifica
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Scambia authorization code per tokens
     *
     * @param string $code Authorization code da Google
     * @return array ['access_token', 'refresh_token', 'expires_in'] o ['error', 'message']
     */
    public function exchangeCode(string $code): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getRedirectUri(),
        ];

        $response = $this->httpPost(self::TOKEN_URL, $data);

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['error_description'] ?? $response['error'],
            ];
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_in' => $response['expires_in'] ?? 3600,
        ];
    }

    /**
     * Rinnova access token
     *
     * @param string $refreshToken Refresh token salvato
     * @return array Nuovi tokens o errore
     */
    public function refreshToken(string $refreshToken): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->httpPost(self::TOKEN_URL, $data);

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['error_description'] ?? $response['error'],
            ];
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $refreshToken,
            'expires_in' => $response['expires_in'] ?? 3600,
        ];
    }

    /**
     * Verifica e decodifica state dal callback
     *
     * @param string $state State ricevuto da Google
     * @return array|null ['module', 'project_id'] o null se invalido
     */
    public function verifyState(string $state): ?array
    {
        // Verifica CSRF
        if (!isset($_SESSION['google_oauth_state']) || $_SESSION['google_oauth_state'] !== $state) {
            return null;
        }

        // Pulisci sessione
        unset($_SESSION['google_oauth_state']);

        $decoded = json_decode(base64_decode($state), true);

        if (!$decoded || !isset($decoded['module']) || !isset($decoded['project_id'])) {
            return null;
        }

        return [
            'module' => $decoded['module'],
            'project_id' => (int) $decoded['project_id'],
            'type' => $decoded['type'] ?? 'gsc', // gsc o ga4
        ];
    }

    // =========================================
    // LOGIN / REGISTRAZIONE con Google
    // =========================================

    /**
     * Genera URL per OAuth login/registrazione
     *
     * @param string|null $intendedUrl URL a cui redirigere dopo il login
     * @return string URL per redirect a Google
     */
    public function getLoginAuthUrl(?string $intendedUrl = null): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Google OAuth non configurato. Configura Client ID e Secret in Admin > Impostazioni.');
        }

        $state = base64_encode(json_encode([
            'action' => 'login',
            'csrf' => bin2hex(random_bytes(16)),
            'intended' => $intendedUrl,
        ]));

        $_SESSION['google_auth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->getLoginRedirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE_LOGIN,
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Redirect URI per login - usa lo stesso callback di GSC/GA4
     * Google Cloud Console ha già questa URI configurata
     */
    public function getLoginRedirectUri(): string
    {
        return $this->getRedirectUri();
    }

    /**
     * Scambia code per token (login flow - usa login redirect URI)
     */
    public function exchangeLoginCode(string $code): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getLoginRedirectUri(),
        ];

        $response = $this->httpPost(self::TOKEN_URL, $data);

        if (isset($response['error'])) {
            return [
                'error' => true,
                'message' => $response['error_description'] ?? $response['error'],
            ];
        }

        return [
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'] ?? 3600,
        ];
    }

    /**
     * Verifica state dal callback login
     *
     * @return array|null ['action', 'intended'] o null se invalido
     */
    public function verifyLoginState(string $state): ?array
    {
        if (!isset($_SESSION['google_auth_state']) || $_SESSION['google_auth_state'] !== $state) {
            return null;
        }

        unset($_SESSION['google_auth_state']);

        $decoded = json_decode(base64_decode($state), true);

        if (!$decoded || ($decoded['action'] ?? '') !== 'login') {
            return null;
        }

        return [
            'action' => 'login',
            'intended' => $decoded['intended'] ?? null,
        ];
    }

    /**
     * Ottieni info utente da Google
     *
     * @return array ['sub', 'email', 'email_verified', 'name', 'picture'] o ['error' => true]
     */
    public function getUserInfo(string $accessToken): array
    {
        $ch = curl_init(self::USERINFO_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return ['error' => true, 'message' => $error ?: "HTTP {$httpCode}"];
        }

        $data = json_decode($response, true);

        if (!$data || empty($data['sub']) || empty($data['email'])) {
            return ['error' => true, 'message' => 'Risposta Google userinfo non valida'];
        }

        return $data;
    }

    /**
     * HTTP POST request
     */
    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'curl_error', 'error_description' => $error];
        }

        return json_decode($response, true) ?? ['error' => 'json_error'];
    }

    /**
     * Ottieni Client ID (per debug/display)
     */
    public function getClientId(): ?string
    {
        return $this->clientId;
    }
}
