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
use Core\OnboardingService;

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
    require BASE_PATH . '/public/coming-soon.php';
    exit;
});

Router::get('/home', function () {
    if (Auth::check()) {
        Router::redirect('/dashboard');
    }
    require BASE_PATH . '/public/landing4.php';
    exit;
});

Router::get('/pricing', function () {
    require BASE_PATH . '/public/pricing.php';
    exit;
});

Router::get('/landing', function () {
    require BASE_PATH . '/public/landing.php';
    exit;
});

Router::get('/landing2', function () {
    require BASE_PATH . '/public/landing2.php';
    exit;
});

Router::get('/landing3', function () {
    require BASE_PATH . '/public/landing3.php';
    exit;
});

// Email preferences (unsubscribe) — public, no login required
Router::get('/email/preferences', function () {
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        Router::redirect('/login');
        return '';
    }

    $tokenRecord = Database::fetch(
        "SELECT user_id FROM email_unsubscribe_tokens WHERE token = ?",
        [$token]
    );

    if (!$tokenRecord) {
        return View::render('email-preferences', [
            'title' => 'Preferenze Email',
            'error' => 'Link non valido o scaduto.',
            'token' => '',
            'preferences' => [],
            'success' => null,
        ], null);
    }

    $preferences = \Services\NotificationService::getPreferences($tokenRecord['user_id']);

    return View::render('email-preferences', [
        'title' => 'Preferenze Email',
        'token' => $token,
        'preferences' => $preferences,
        'error' => null,
        'success' => null,
    ], null);
});

Router::post('/email/preferences', function () {
    Middleware::csrf();

    $token = $_POST['token'] ?? '';
    $tokenRecord = Database::fetch(
        "SELECT user_id FROM email_unsubscribe_tokens WHERE token = ?",
        [$token]
    );

    if (!$tokenRecord) {
        Router::redirect('/login');
        return '';
    }

    $prefs = [];
    $types = ['project_invite', 'project_invite_accepted', 'project_invite_declined', 'operation_completed', 'operation_failed'];
    foreach ($types as $type) {
        $prefs[$type] = isset($_POST['email_' . $type]);
    }

    \Services\NotificationService::updatePreferences($tokenRecord['user_id'], $prefs);

    $preferences = \Services\NotificationService::getPreferences($tokenRecord['user_id']);

    return View::render('email-preferences', [
        'title' => 'Preferenze Email',
        'token' => $token,
        'preferences' => $preferences,
        'error' => null,
        'success' => 'Preferenze salvate con successo.',
    ], null);
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
        // Check for pending invite token (project sharing)
        if (!empty($_SESSION['invite_token'])) {
            $token = $_SESSION['invite_token'];
            unset($_SESSION['invite_token']);
            Router::redirect('/invite/accept?token=' . urlencode($token));
            return '';
        }

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

    // Email di benvenuto (non bloccante - errori loggati silenziosamente)
    try {
        $config = require BASE_PATH . '/config/app.php';
        \Services\EmailService::sendWelcome($email, $name, $config['free_credits'] ?? 30, $userId);
    } catch (\Exception $e) {
        // Non bloccare la registrazione se l'email fallisce
        error_log('Welcome email failed: ' . $e->getMessage());
    }

    $_SESSION['_flash']['success'] = 'Registrazione completata! Benvenuto in SEO Toolkit.';

    // Check for pending invite token (project sharing)
    if (!empty($_SESSION['invite_token'])) {
        $token = $_SESSION['invite_token'];
        unset($_SESSION['invite_token']);
        Router::redirect('/invite/accept?token=' . urlencode($token));
        return '';
    }

    Router::redirect('/dashboard');
});

Router::post('/logout', function () {
    Middleware::csrf();
    Auth::logout();
    Router::redirect('/login');
});

// =========================================
// GOOGLE AUTH (Login/Registrazione)
// =========================================

Router::get('/auth/google', function () {
    Middleware::guest();

    $oauth = new \Services\GoogleOAuthService();

    if (!$oauth->isConfigured()) {
        $_SESSION['_flash']['error'] = 'Login con Google non disponibile. OAuth non configurato.';
        Router::redirect('/login');
        return;
    }

    $intended = $_SESSION['_intended_url'] ?? null;

    try {
        $url = $oauth->getLoginAuthUrl($intended);
        header('Location: ' . $url);
        exit;
    } catch (\Exception $e) {
        $_SESSION['_flash']['error'] = 'Errore configurazione Google OAuth: ' . $e->getMessage();
        Router::redirect('/login');
    }
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

    // Crea token (ritorna null se email non esiste)
    $token = Auth::createPasswordResetToken($email);

    // Invia email con link di reset (solo se token creato = email esiste)
    if ($token) {
        try {
            \Services\EmailService::sendPasswordReset($email, $token);
        } catch (\Exception $e) {
            error_log('Password reset email failed: ' . $e->getMessage());
        }
    }

    // Messaggio generico per sicurezza (non rivelare se email esiste)
    return View::render('auth/forgot-password', [
        'title' => 'Password dimenticata',
        'success' => 'Se l\'email esiste nel sistema, riceverai un link per reimpostare la password.',
    ], null);
});

// --- Password Reset (completamento) ---

Router::get('/reset-password', function () {
    Middleware::guest();

    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        $_SESSION['_flash']['error'] = 'Link di reset non valido.';
        Router::redirect('/forgot-password');
        return;
    }

    // Valida token
    $email = Auth::validatePasswordResetToken($token);

    if (!$email) {
        return View::render('auth/reset-password', [
            'title' => 'Reimposta Password',
            'error' => 'Il link di reset e scaduto o non valido. Richiedi un nuovo link.',
            'token' => '',
        ], null);
    }

    return View::render('auth/reset-password', [
        'title' => 'Reimposta Password',
        'token' => $token,
        'email' => $email,
    ], null);
});

Router::post('/reset-password', function () {
    Middleware::guest();
    Middleware::csrf();

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    // Validazione
    $errors = [];

    if (empty($token)) {
        $errors[] = 'Token mancante';
    }
    if (strlen($password) < 8) {
        $errors[] = 'La password deve essere di almeno 8 caratteri';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'Le password non coincidono';
    }

    if (!empty($errors)) {
        return View::render('auth/reset-password', [
            'title' => 'Reimposta Password',
            'error' => implode('<br>', $errors),
            'token' => $token,
        ], null);
    }

    // Reset password
    $success = Auth::resetPassword($token, $password);

    if (!$success) {
        return View::render('auth/reset-password', [
            'title' => 'Reimposta Password',
            'error' => 'Il link di reset e scaduto o non valido. Richiedi un nuovo link.',
            'token' => '',
        ], null);
    }

    $_SESSION['_flash']['success'] = 'Password reimpostata con successo. Puoi effettuare il login.';
    Router::redirect('/login');
});

// --- Documentation Routes (Public) ---

Router::get('/docs', function () {
    $content = View::render('docs/index', ['currentPage' => 'index'], null);

    // Render con layout docs custom (standalone, no main layout)
    ob_start();
    $title = 'Documentazione - Ainstein';
    $currentPage = 'index';
    include BASE_PATH . '/shared/views/docs/layout.php';
    $html = ob_get_clean();
    echo $html;
    exit;
});

Router::get('/docs/{slug}', function (string $slug) {
    $validPages = [
        'getting-started' => 'Primi Passi',
        'modules-overview' => 'Panoramica Moduli',
        'ai-content' => 'AI Content Generator',
        'seo-audit' => 'SEO Audit',
        'seo-tracking' => 'SEO Tracking',
        'keyword-research' => 'Keyword Research',
        'internal-links' => 'Internal Links',
        'ads-analyzer' => 'Google Ads Analyzer',
        'content-creator' => 'Content Creator',
        'credits' => 'Sistema Crediti',
        'faq' => 'FAQ',
    ];

    if (!isset($validPages[$slug])) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'Pagina non trovata']);
    }

    $viewPath = BASE_PATH . '/shared/views/docs/' . $slug . '.php';
    if (!file_exists($viewPath)) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'Pagina non trovata']);
    }

    $content = View::render('docs/' . $slug, ['currentPage' => $slug], null);

    ob_start();
    $title = $validPages[$slug] . ' - Documentazione Ainstein';
    $currentPage = $slug;
    include BASE_PATH . '/shared/views/docs/layout.php';
    $html = ob_get_clean();
    echo $html;
    exit;
});

// --- Protected Routes ---

Router::get('/dashboard', function () {
    Middleware::auth();

    $user = Auth::user();
    $uid = $user['id'];
    $modules = ModuleLoader::getUserModules($uid);
    $_credits = (float) ($user['credits'] ?? 0);

    // --- Stats header ---
    $usageToday = (float) Database::fetch(
        "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$uid]
    )['total'];

    $usageMonth = (float) Database::fetch(
        "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$uid]
    )['total'];

    // --- Global Projects con KPI per modulo (batch queries) ---
    $gpModel = new \Core\Models\GlobalProject();
    $globalProjects = [];
    try {
        $globalProjects = $gpModel->allWithDashboardData($uid);
    } catch (\Exception $e) {}

    // --- Conta azioni urgenti ---
    $urgentActionsCount = 0;
    foreach ($globalProjects as $gp) {
        if ($gp['primary_action'] && ($gp['primary_action']['severity'] ?? '') !== 'suggestion') {
            $urgentActionsCount++;
        }
    }

    // --- Moduli non usati (per "Scopri cosa puoi fare") ---
    $usedModuleSlugs = [];
    foreach ($globalProjects as $gp) {
        foreach ($gp['active_modules'] as $slug) {
            $usedModuleSlugs[$slug] = true;
        }
    }
    $allModuleSlugs = array_column($modules, 'slug');
    $unusedModuleSlugs = array_diff($allModuleSlugs, array_keys($usedModuleSlugs));

    // --- WordPress collegato (user-level, non project-level) ---
    $wpConnected = false;
    try {
        $wpConnected = (bool) Database::fetch(
            "SELECT COUNT(*) as cnt FROM aic_wp_sites WHERE user_id = ? AND is_active = 1", [$uid]
        )['cnt'];
    } catch (\Exception $e) {}

    // --- Switch new/active user ---
    $isNewUser = empty($globalProjects);

    return View::render('dashboard', [
        'title' => 'Dashboard',
        'user' => $user,
        'modules' => $modules,
        'credits' => $_credits,
        'usageToday' => $usageToday,
        'usageMonth' => $usageMonth,
        'globalProjects' => $globalProjects,
        'urgentActionsCount' => $urgentActionsCount,
        'unusedModuleSlugs' => $unusedModuleSlugs,
        'wpConnected' => $wpConnected,
        'isNewUser' => $isNewUser,
    ]);
});

Router::get('/profile', function () {
    Middleware::auth();

    $user = Auth::user();

    return View::render('profile', [
        'title' => 'Profilo',
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'notificationPrefs' => \Services\NotificationService::getPreferences($user['id']),
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

    // Notifica cambio password via email
    try {
        \Services\EmailService::sendTemplate(
            $user['email'],
            'Password modificata',
            'password-changed',
            [
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'changed_at' => date('d/m/Y H:i'),
            ],
            $user['id']
        );
    } catch (\Exception $e) {
        error_log('Password changed email failed: ' . $e->getMessage());
    }

    $_SESSION['_flash']['success'] = 'Password aggiornata';
    Router::redirect('/profile');
});

Router::post('/profile/notification-preferences', function () {
    Middleware::auth();
    Middleware::csrf();

    $prefs = [];
    $types = ['project_invite', 'project_invite_accepted', 'project_invite_declined', 'operation_completed', 'operation_failed'];

    foreach ($types as $type) {
        $prefs[$type] = isset($_POST['notif_' . $type]);
    }

    \Services\NotificationService::updatePreferences(Auth::id(), $prefs);

    $_SESSION['_flash']['success'] = 'Preferenze notifiche aggiornate';
    Router::redirect('/profile');
});

// --- Onboarding Routes ---
Router::post('/onboarding/welcome/complete', function () {
    Middleware::auth();
    Middleware::csrf();
    OnboardingService::completeWelcome(Auth::id());
    echo json_encode(['success' => true]);
});

Router::post('/onboarding/{moduleSlug}/complete', function (string $moduleSlug) {
    Middleware::auth();
    Middleware::csrf();
    OnboardingService::completeModule(Auth::id(), $moduleSlug);
    echo json_encode(['success' => true]);
});

Router::post('/onboarding/{moduleSlug}/reset', function (string $moduleSlug) {
    Middleware::auth();
    Middleware::csrf();
    OnboardingService::resetModule(Auth::id(), $moduleSlug);
    echo json_encode(['success' => true]);
});

Router::get('/onboarding/status', function () {
    Middleware::auth();
    $userId = Auth::id();
    echo json_encode([
        'success' => true,
        'welcome_completed' => OnboardingService::isWelcomeCompleted($userId),
        'completed_modules' => OnboardingService::getCompletedModules($userId),
    ]);
});

// --- Notifications Routes ---
Router::get('/notifications/unread-count', fn() => (new Controllers\NotificationController())->unreadCount());
Router::get('/notifications/recent', fn() => (new Controllers\NotificationController())->recent());
Router::get('/notifications', fn() => (new Controllers\NotificationController())->index());
Router::post('/notifications/read-all', fn() => (new Controllers\NotificationController())->markAllRead());
Router::post('/notifications/{id}/read', fn($id) => (new Controllers\NotificationController())->markRead((int) $id));

// --- Global Projects Routes ---
Router::get('/projects', function () {
    $controller = new Controllers\GlobalProjectController();
    return $controller->index();
});

Router::get('/projects/create', function () {
    $controller = new Controllers\GlobalProjectController();
    return $controller->create();
});

Router::post('/projects', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->store();
});

// Download plugin WordPress (MUST be before /projects/{id} to avoid :id matching "download-plugin")
Router::get('/projects/download-plugin/wordpress', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->downloadPlugin();
});

Router::get('/projects/{id}', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    return $controller->dashboard((int) $id);
});

Router::get('/projects/{id}/settings', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    return $controller->settings((int) $id);
});

Router::post('/projects/{id}/settings', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->update((int) $id);
});

Router::post('/projects/{id}/activate-module', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->activateModule((int) $id);
});

Router::post('/projects/{id}/delete', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->destroy((int) $id);
});

// WordPress site management (unified via aic_wp_sites)
Router::post('/projects/{id}/wp-sites', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->addWpSite((int) $id);
});

Router::post('/projects/{id}/wp-sites/link', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->linkWpSite((int) $id);
});

Router::post('/projects/{id}/wp-sites/unlink', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->unlinkWpSite((int) $id);
});

Router::post('/projects/{id}/wp-sites/test', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->testWpSite((int) $id);
});

Router::post('/projects/{id}/wp-sites/update', function ($id) {
    $controller = new Controllers\GlobalProjectController();
    $controller->updateWpSite((int) $id);
});

Router::post('/wp-sites/test', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->testWpSiteGlobal();
});

Router::post('/wp-sites/delete', function () {
    $controller = new Controllers\GlobalProjectController();
    $controller->deleteWpSite();
});

// Project Sharing
Router::get('/projects/{id}/sharing', function ($id) {
    return (new Controllers\GlobalProjectController())->sharing((int) $id);
});
Router::post('/projects/{id}/sharing/invite', function ($id) {
    return (new Controllers\GlobalProjectController())->invite((int) $id);
});
Router::post('/projects/{id}/sharing/remove/{userId}', function ($id, $userId) {
    return (new Controllers\GlobalProjectController())->removeMember((int) $id, (int) $userId);
});
Router::post('/projects/{id}/sharing/update/{userId}', function ($id, $userId) {
    return (new Controllers\GlobalProjectController())->updateMember((int) $id, (int) $userId);
});
Router::post('/projects/{id}/sharing/cancel-invite/{inviteId}', function ($id, $inviteId) {
    return (new Controllers\GlobalProjectController())->cancelInvitation((int) $id, (int) $inviteId);
});

// Invitation acceptance
Router::get('/invite/accept', function () {
    return (new Controllers\GlobalProjectController())->acceptInviteByToken();
});
Router::post('/invite/{id}/accept', function ($id) {
    return (new Controllers\GlobalProjectController())->acceptInternalInvite((int) $id);
});
Router::post('/invite/{id}/decline', function ($id) {
    return (new Controllers\GlobalProjectController())->declineInternalInvite((int) $id);
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
