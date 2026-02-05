<?php

/**
 * SEO Toolkit - Entry Point
 */

// Errori in dev
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sessione
session_start();

// Timezone Italia
date_default_timezone_set('Europe/Rome');

// Definizioni
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', BASE_PATH);  // Alias per compatibilità
define('DEBUG', true);

// Carica Composer autoloader (per librerie esterne come Readability)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Autoloader semplice
spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\' => BASE_PATH . '/services/',
        'Controllers\\' => BASE_PATH . '/controllers/',
        'Admin\\Controllers\\' => BASE_PATH . '/admin/controllers/',
    ];

    foreach ($paths as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // Autoload moduli: Modules\{ModuleName}\{Type}\{Class}
    // Es: Modules\InternalLinks\Controllers\ProjectController
    if (str_starts_with($class, 'Modules\\')) {
        $parts = explode('\\', $class);
        if (count($parts) >= 4) {
            // $parts[0] = 'Modules'
            // $parts[1] = 'InternalLinks' -> 'internal-links'
            // $parts[2] = 'Controllers' -> 'controllers'
            // $parts[3+] = ClassName
            $moduleName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $parts[1]));
            $type = strtolower($parts[2]);
            $className = implode('/', array_slice($parts, 3));

            $file = BASE_PATH . '/modules/' . $moduleName . '/' . $type . '/' . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

use Core\Router;
use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\Database;
use Core\Credits;
use Core\ModuleLoader;

// Auto-detect basePath per supportare sia vhost che subfolder
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($scriptName), '/\\');
if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
// Rimuovi /public dal basePath se presente (htaccess root redirige a public/)
// REQUEST_URI non contiene /public, quindi basePath non deve contenerlo
if (str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}
Router::setBasePath($basePath);

// Carica View.php per registrare le funzioni helper globali (url, e, csrf_field, etc.)
require_once BASE_PATH . '/core/View.php';

// Genera CSRF token
Middleware::generateCsrfToken();

// =========================================
// ROUTES
// =========================================

// --- Public Routes ---

Router::get('/', function () {
    if (Auth::check()) {
        Router::redirect('/dashboard');
    }
    // Landing page per visitatori non loggati
    require BASE_PATH . '/public/landing.php';
    exit;
});

Router::get('/login', function () {
    Middleware::guest();
    return View::render('auth/login', ['title' => 'Login'], null);
});

Router::post('/login', function () {
    Middleware::guest();
    Middleware::csrf();

    if (!Middleware::rateLimit('login', 5, 1)) {
        return View::render('auth/login', [
            'title' => 'Login',
            'error' => 'Troppi tentativi. Riprova tra un minuto.',
        ], null);
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (Auth::attempt($email, $password, $remember)) {
        $intended = $_SESSION['_intended_url'] ?? '/dashboard';
        unset($_SESSION['_intended_url']);
        Router::redirect($intended);
    }

    $_SESSION['_old_input'] = ['email' => $email];

    return View::render('auth/login', [
        'title' => 'Login',
        'error' => 'Credenziali non valide',
    ], null);
});

Router::get('/register', function () {
    Middleware::guest();
    return View::render('auth/register', ['title' => 'Registrazione'], null);
});

Router::post('/register', function () {
    Middleware::guest();
    Middleware::csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    // Validazione
    $errors = [];

    if (empty($name)) $errors[] = 'Il nome e obbligatorio';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida';
    if (strlen($password) < 8) $errors[] = 'La password deve essere di almeno 8 caratteri';
    if ($password !== $passwordConfirmation) $errors[] = 'Le password non coincidono';

    // Verifica email esistente
    if (Database::fetch("SELECT id FROM users WHERE email = ?", [$email])) {
        $errors[] = 'Email gia registrata';
    }

    if (!empty($errors)) {
        $_SESSION['_old_input'] = ['name' => $name, 'email' => $email];
        return View::render('auth/register', [
            'title' => 'Registrazione',
            'error' => implode('<br>', $errors),
        ], null);
    }

    // Registra utente
    $userId = Auth::register([
        'name' => $name,
        'email' => $email,
        'password' => $password,
    ]);

    // Login automatico
    $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    Auth::login($user);

    $_SESSION['_flash']['success'] = 'Registrazione completata! Benvenuto in SEO Toolkit.';
    Router::redirect('/dashboard');
});

Router::post('/logout', function () {
    Middleware::csrf();
    Auth::logout();
    Router::redirect('/login');
});

Router::get('/forgot-password', function () {
    Middleware::guest();
    return View::render('auth/forgot-password', ['title' => 'Password dimenticata'], null);
});

Router::post('/forgot-password', function () {
    Middleware::guest();
    Middleware::csrf();

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        return View::render('auth/forgot-password', [
            'title' => 'Password dimenticata',
            'error' => 'Inserisci la tua email',
        ], null);
    }

    // Crea token (anche se email non esiste, per sicurezza)
    Auth::createPasswordResetToken($email);

    return View::render('auth/forgot-password', [
        'title' => 'Password dimenticata',
        'success' => 'Se l\'email esiste, riceverai un link per il reset.',
    ], null);
});

// --- Protected Routes ---

Router::get('/dashboard', function () {
    Middleware::auth();

    $user = Auth::user();
    $modules = ModuleLoader::getUserModules($user['id']);

    // Stats utilizzo
    $usageToday = Database::fetch(
        "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE user_id = ? AND DATE(created_at) = CURDATE()",
        [$user['id']]
    )['total'];

    $usageMonth = Database::fetch(
        "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())",
        [$user['id']]
    )['total'];

    $projectsCount = Database::count('projects', 'user_id = ?', [$user['id']]);

    $recentUsage = Database::fetchAll(
        "SELECT * FROM usage_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
        [$user['id']]
    );

    return View::render('dashboard', [
        'title' => 'Dashboard',
        'user' => $user,
        'modules' => $modules,
        'usageToday' => $usageToday,
        'usageMonth' => $usageMonth,
        'projectsCount' => $projectsCount,
        'recentUsage' => $recentUsage,
    ]);
});

Router::get('/profile', function () {
    Middleware::auth();

    $user = Auth::user();

    return View::render('profile', [
        'title' => 'Profilo',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
    ]);
});

Router::post('/profile', function () {
    Middleware::auth();
    Middleware::csrf();

    $name = trim($_POST['name'] ?? '');

    Database::update(
        'users',
        ['name' => $name],
        'id = ?',
        [Auth::id()]
    );

    $_SESSION['_flash']['success'] = 'Profilo aggiornato';
    Router::redirect('/profile');
});

Router::post('/profile/password', function () {
    Middleware::auth();
    Middleware::csrf();

    $user = Auth::user();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $newPasswordConfirmation = $_POST['new_password_confirmation'] ?? '';

    if (!password_verify($currentPassword, $user['password'])) {
        $_SESSION['_flash']['error'] = 'Password attuale non corretta';
        Router::redirect('/profile');
    }

    if (strlen($newPassword) < 8) {
        $_SESSION['_flash']['error'] = 'La nuova password deve essere di almeno 8 caratteri';
        Router::redirect('/profile');
    }

    if ($newPassword !== $newPasswordConfirmation) {
        $_SESSION['_flash']['error'] = 'Le password non coincidono';
        Router::redirect('/profile');
    }

    Auth::updatePassword($user['id'], $newPassword);

    $_SESSION['_flash']['success'] = 'Password aggiornata';
    Router::redirect('/profile');
});

// --- OAuth Routes (centralizzati) ---
Router::get('/oauth/google/callback', [Controllers\OAuthController::class, 'googleCallback']);

// --- Admin Routes ---
require_once BASE_PATH . '/admin/routes.php';

// --- Module Routes ---
ModuleLoader::loadAll();

// =========================================
// DISPATCH
// =========================================

Router::dispatch();
