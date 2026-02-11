<?php

namespace Core;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        self::login($user, $remember);
        return true;
    }

    public static function login(array $user, bool $remember = false): void
    {
        $_SESSION['user_id'] = $user['id'];
        self::$user = $user;

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);

            Database::update(
                'users',
                ['remember_token' => $hashedToken],
                'id = ?',
                [$user['id']]
            );

            setcookie('remember_token', $token, [
                'expires' => time() + (86400 * 30), // 30 giorni
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function logout(): void
    {
        if (self::check()) {
            Database::update(
                'users',
                ['remember_token' => null],
                'id = ?',
                [self::id()]
            );
        }

        unset($_SESSION['user_id']);
        self::$user = null;

        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
        ]);

        session_destroy();
    }

    public static function check(): bool
    {
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        // Verifica remember token
        if (isset($_COOKIE['remember_token'])) {
            $hashedToken = hash('sha256', $_COOKIE['remember_token']);
            $user = Database::fetch(
                "SELECT * FROM users WHERE remember_token = ? AND is_active = 1",
                [$hashedToken]
            );

            if ($user) {
                self::login($user);
                return true;
            }
        }

        return false;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        if (!self::check()) {
            return null;
        }

        self::$user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND is_active = 1",
            [$_SESSION['user_id']]
        );

        return self::$user;
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    public static function register(array $data): int
    {
        $config = require __DIR__ . '/../config/app.php';

        $userId = Database::insert('users', [
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'name' => $data['name'] ?? '',
            'role' => 'user',
            'credits' => $config['free_credits'] ?? 50,
            'is_active' => true,
        ]);

        // Log transazione crediti iniziali
        Database::insert('credit_transactions', [
            'user_id' => $userId,
            'amount' => $config['free_credits'] ?? 50,
            'type' => 'bonus',
            'description' => 'Crediti benvenuto',
            'balance_after' => $config['free_credits'] ?? 50,
        ]);

        return $userId;
    }

    /**
     * Trova o crea utente da Google OAuth
     *
     * @param array $googleUser Dati da Google userinfo (sub, email, name, picture)
     * @return array|null Utente trovato/creato o null se errore
     */
    public static function findOrCreateFromGoogle(array $googleUser): ?array
    {
        $googleId = $googleUser['sub'];
        $email = $googleUser['email'];
        $name = $googleUser['name'] ?? '';
        $avatar = $googleUser['picture'] ?? null;

        // 1. Cerca per google_id
        $user = Database::fetch(
            "SELECT * FROM users WHERE google_id = ? AND is_active = 1",
            [$googleId]
        );

        if ($user) {
            // Aggiorna avatar se cambiato
            if ($avatar && ($user['avatar'] ?? '') !== $avatar) {
                Database::update('users', ['avatar' => $avatar], 'id = ?', [$user['id']]);
            }
            return $user;
        }

        // 2. Cerca per email (utente esistente che collega Google)
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );

        if ($user) {
            // Collega google_id all'account esistente
            Database::update('users', [
                'google_id' => $googleId,
                'avatar' => $avatar,
            ], 'id = ?', [$user['id']]);

            $user['google_id'] = $googleId;
            $user['avatar'] = $avatar;
            return $user;
        }

        // 3. Nuovo utente - registra
        $config = require __DIR__ . '/../config/app.php';
        $freeCredits = $config['free_credits'] ?? 50;

        $userId = Database::insert('users', [
            'email' => $email,
            'password' => null,
            'name' => $name,
            'role' => 'user',
            'credits' => $freeCredits,
            'is_active' => true,
            'google_id' => $googleId,
            'avatar' => $avatar,
        ]);

        // Log crediti benvenuto
        Database::insert('credit_transactions', [
            'user_id' => $userId,
            'amount' => $freeCredits,
            'type' => 'bonus',
            'description' => 'Crediti benvenuto (registrazione Google)',
            'balance_after' => $freeCredits,
        ]);

        return Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    public static function updatePassword(int $userId, string $password): void
    {
        Database::update(
            'users',
            ['password' => password_hash($password, PASSWORD_DEFAULT)],
            'id = ?',
            [$userId]
        );
    }

    public static function createPasswordResetToken(string $email): ?string
    {
        $user = Database::fetch("SELECT id FROM users WHERE email = ?", [$email]);

        if (!$user) {
            return null;
        }

        // Elimina token precedenti
        Database::delete('password_resets', 'email = ?', [$email]);

        $token = bin2hex(random_bytes(32));

        Database::insert('password_resets', [
            'email' => $email,
            'token' => hash('sha256', $token),
        ]);

        return $token;
    }

    public static function validatePasswordResetToken(string $token): ?string
    {
        $hashedToken = hash('sha256', $token);

        $reset = Database::fetch(
            "SELECT email FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$hashedToken]
        );

        return $reset['email'] ?? null;
    }

    public static function resetPassword(string $token, string $password): bool
    {
        $email = self::validatePasswordResetToken($token);

        if (!$email) {
            return false;
        }

        $user = Database::fetch("SELECT id FROM users WHERE email = ?", [$email]);

        if (!$user) {
            return false;
        }

        self::updatePassword($user['id'], $password);

        // Elimina token usato
        Database::delete('password_resets', 'email = ?', [$email]);

        return true;
    }

    public static function refresh(): void
    {
        self::$user = null;
    }
}
