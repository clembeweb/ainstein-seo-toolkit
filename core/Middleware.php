<?php

namespace Core;

class Middleware
{
    public static function auth(): void
    {
        if (!Auth::check()) {
            $_SESSION['_intended_url'] = $_SERVER['REQUEST_URI'];
            Router::redirect('/login');
        }
    }

    public static function guest(): void
    {
        if (Auth::check()) {
            Router::redirect('/dashboard');
        }
    }

    public static function role(string $role): void
    {
        self::auth();

        $user = Auth::user();

        if ($user['role'] !== $role) {
            http_response_code(403);
            echo View::render('errors/403', ['message' => 'Accesso non autorizzato']);
            exit;
        }
    }

    public static function admin(): void
    {
        self::role('admin');
    }

    public static function hasCredits(float $required): bool
    {
        self::auth();

        $user = Auth::user();

        if ((float) $user['credits'] < $required) {
            return false;
        }

        return true;
    }

    public static function requireCredits(float $required): void
    {
        if (!self::hasCredits($required)) {
            if (self::isAjax()) {
                http_response_code(402);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => true,
                    'message' => 'Crediti insufficienti',
                    'credits_required' => $required,
                    'credits_available' => Auth::user()['credits'],
                ]);
                exit;
            }

            $_SESSION['_flash']['error'] = 'Crediti insufficienti per questa operazione. Richiesti: ' . $required;
            Router::redirect('/dashboard?upgrade=1');
        }
    }

    public static function csrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = '';

            // 1. Check $_POST (form submissions)
            if (!empty($_POST['_csrf_token'])) {
                $token = $_POST['_csrf_token'];
            } elseif (!empty($_POST['_token'])) {
                $token = $_POST['_token'];
            }

            // 2. Check HTTP header (AJAX)
            if (empty($token) && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }

            // 3. Check JSON body (fetch with application/json)
            if (empty($token)) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $rawInput = file_get_contents('php://input');
                    $jsonData = json_decode($rawInput, true);
                    if (is_array($jsonData)) {
                        $token = $jsonData['_csrf_token'] ?? $jsonData['_token'] ?? '';
                    }
                }
            }

            if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
                http_response_code(403);
                if (self::isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Token CSRF non valido']);
                } else {
                    echo View::render('errors/403', ['message' => 'Token CSRF non valido']);
                }
                exit;
            }
        }
    }

    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function rateLimit(string $key, int $maxAttempts, int $decayMinutes = 1): bool
    {
        $cacheKey = 'rate_limit_' . $key . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');

        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'reset_at' => time() + ($decayMinutes * 60),
            ];
        }

        $data = $_SESSION[$cacheKey];

        // Reset se scaduto
        if (time() > $data['reset_at']) {
            $_SESSION[$cacheKey] = [
                'attempts' => 1,
                'reset_at' => time() + ($decayMinutes * 60),
            ];
            return true;
        }

        // Incrementa tentativi
        $_SESSION[$cacheKey]['attempts']++;

        if ($_SESSION[$cacheKey]['attempts'] > $maxAttempts) {
            return false;
        }

        return true;
    }
}
