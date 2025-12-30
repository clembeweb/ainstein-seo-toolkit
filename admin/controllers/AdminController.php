<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Core\Middleware;

class AdminController
{
    public function __construct()
    {
        Middleware::admin();
    }

    public function dashboard(): string
    {
        $stats = [
            'total_users' => Database::count('users'),
            'active_users' => Database::count('users', 'is_active = 1'),
            'users_today' => Database::count('users', 'DATE(created_at) = CURDATE()'),
            'credits_today' => Credits::getTotalConsumedToday(),
            'credits_month' => Credits::getTotalConsumedMonth(),
            'total_modules' => Database::count('modules'),
            'active_modules' => Database::count('modules', 'is_active = 1'),
        ];

        $recentUsers = Database::fetchAll(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
        );

        $topUsers = Credits::getTopUsers(5, 'month');

        return View::render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'topUsers' => $topUsers,
        ]);
    }

    public function users(): string
    {
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';

        $where = '1=1';
        $params = [];

        if ($search) {
            $where .= ' AND (email LIKE ? OR name LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($role) {
            $where .= ' AND role = ?';
            $params[] = $role;
        }

        if ($status !== '') {
            $where .= ' AND is_active = ?';
            $params[] = $status === 'active' ? 1 : 0;
        }

        $users = Database::fetchAll(
            "SELECT * FROM users WHERE {$where} ORDER BY created_at DESC",
            $params
        );

        return View::render('admin/users/index', [
            'title' => 'Gestione Utenti',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function userShow(string $id): string
    {
        $targetUser = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$targetUser) {
            http_response_code(404);
            return View::render('errors/404');
        }

        $transactions = Credits::getTransactionHistory((int) $id, 20);
        $usageStats = Credits::getUsageStats((int) $id, 'month');

        return View::render('admin/users/show', [
            'title' => 'Dettaglio Utente',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'targetUser' => $targetUser,
            'transactions' => $transactions,
            'usageStats' => $usageStats,
        ]);
    }

    public function userUpdate(string $id): string
    {
        Middleware::csrf();

        $targetUser = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$targetUser) {
            http_response_code(404);
            return View::render('errors/404');
        }

        $name = trim($_POST['name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        Database::update(
            'users',
            [
                'name' => $name,
                'role' => $role,
                'is_active' => $isActive,
            ],
            'id = ?',
            [$id]
        );

        $_SESSION['_flash']['success'] = 'Utente aggiornato con successo';
        \Core\Router::redirect('/admin/users/' . $id);
        return '';
    }

    public function userCredits(string $id): string
    {
        Middleware::csrf();

        $amount = (float) ($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'manual';
        $description = trim($_POST['description'] ?? '');

        if (!$amount || !$description) {
            $_SESSION['_flash']['error'] = 'Compila tutti i campi';
            \Core\Router::redirect('/admin/users/' . $id);
            return '';
        }

        Credits::add((int) $id, $amount, $type, $description, Auth::id());

        $_SESSION['_flash']['success'] = 'Crediti aggiornati con successo';
        \Core\Router::redirect('/admin/users/' . $id);
        return '';
    }

    public function settings(): string
    {
        $settings = Database::fetchAll("SELECT * FROM settings ORDER BY key_name");
        $settingsMap = [];
        foreach ($settings as $s) {
            $settingsMap[$s['key_name']] = $s;
        }

        return View::render('admin/settings', [
            'title' => 'Impostazioni',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'settings' => $settingsMap,
        ]);
    }

    public function settingsUpdate(): string
    {
        Middleware::csrf();

        foreach ($_POST as $key => $value) {
            if ($key === '_csrf_token') continue;

            $existing = Database::fetch("SELECT id FROM settings WHERE key_name = ?", [$key]);

            if ($existing) {
                Database::update(
                    'settings',
                    ['value' => $value, 'updated_by' => Auth::id()],
                    'key_name = ?',
                    [$key]
                );
            } else {
                Database::insert('settings', [
                    'key_name' => $key,
                    'value' => $value,
                    'updated_by' => Auth::id(),
                ]);
            }
        }

        $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
        \Core\Router::redirect('/admin/settings');
        return '';
    }

    public function modules(): string
    {
        $modules = Database::fetchAll("SELECT * FROM modules ORDER BY name");

        return View::render('admin/modules', [
            'title' => 'Gestione Moduli',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'allModules' => $modules,
        ]);
    }

    public function moduleToggle(string $id): string
    {
        Middleware::csrf();

        $module = Database::fetch("SELECT * FROM modules WHERE id = ?", [$id]);

        if (!$module) {
            http_response_code(404);
            return View::json(['error' => 'Modulo non trovato']);
        }

        $newStatus = !$module['is_active'];
        Database::update('modules', ['is_active' => $newStatus], 'id = ?', [$id]);

        $_SESSION['_flash']['success'] = $newStatus ? 'Modulo attivato' : 'Modulo disattivato';
        \Core\Router::redirect('/admin/modules');
        return '';
    }

    /**
     * Show module settings form
     */
    public function moduleSettings(string $id): string
    {
        $module = Database::fetch("SELECT * FROM modules WHERE id = ?", [$id]);

        if (!$module) {
            http_response_code(404);
            return View::render('errors/404');
        }

        // Load module.json for settings schema
        $moduleJsonPath = \ROOT_PATH . '/modules/' . $module['slug'] . '/module.json';
        $settingsSchema = [];

        if (file_exists($moduleJsonPath)) {
            $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
            $settingsSchema = $moduleJson['settings'] ?? [];
        }

        // Get current settings values
        $currentSettings = json_decode($module['settings'] ?? '{}', true) ?: [];

        return View::render('admin/module-settings', [
            'title' => 'Impostazioni - ' . $module['name'],
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'module' => $module,
            'settingsSchema' => $settingsSchema,
            'currentSettings' => $currentSettings,
        ]);
    }

    /**
     * Save module settings
     */
    public function moduleSettingsUpdate(string $id): string
    {
        Middleware::csrf();

        $module = Database::fetch("SELECT * FROM modules WHERE id = ?", [$id]);

        if (!$module) {
            http_response_code(404);
            return View::json(['error' => 'Modulo non trovato']);
        }

        // Load module.json for settings schema validation
        $moduleJsonPath = \ROOT_PATH . '/modules/' . $module['slug'] . '/module.json';
        $settingsSchema = [];

        if (file_exists($moduleJsonPath)) {
            $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
            $settingsSchema = $moduleJson['settings'] ?? [];
        }

        // Get current settings
        $currentSettings = json_decode($module['settings'] ?? '{}', true) ?: [];

        // Update only settings that exist in schema
        foreach ($settingsSchema as $key => $schema) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];

                // Type coercion based on schema
                switch ($schema['type'] ?? 'text') {
                    case 'number':
                        $value = (int) $value;
                        break;
                    case 'boolean':
                    case 'checkbox':
                        $value = (bool) $value;
                        break;
                    default:
                        $value = trim($value);
                }

                $currentSettings[$key] = $value;
            } elseif (($schema['type'] ?? '') === 'checkbox') {
                // Checkbox not sent means false
                $currentSettings[$key] = false;
            }
        }

        // Save to database
        Database::update(
            'modules',
            ['settings' => json_encode($currentSettings)],
            'id = ?',
            [$id]
        );

        $_SESSION['_flash']['success'] = 'Impostazioni modulo salvate';
        \Core\Router::redirect('/admin/modules/' . $id . '/settings');
        return '';
    }

    public function plans(): string
    {
        $plans = Database::fetchAll("SELECT * FROM plans ORDER BY price_monthly");

        return View::render('admin/plans', [
            'title' => 'Gestione Piani',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'plans' => $plans,
        ]);
    }

    public function planUpdate(string $id): string
    {
        Middleware::csrf();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'credits_monthly' => (int) ($_POST['credits_monthly'] ?? 0),
            'price_monthly' => (float) ($_POST['price_monthly'] ?? 0),
            'price_yearly' => (float) ($_POST['price_yearly'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        Database::update('plans', $data, 'id = ?', [$id]);

        $_SESSION['_flash']['success'] = 'Piano aggiornato';
        \Core\Router::redirect('/admin/plans');
        return '';
    }
}
