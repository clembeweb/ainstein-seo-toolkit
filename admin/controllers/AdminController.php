<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\Cache;
use Core\Credits;
use Core\ModuleLoader;
use Core\Middleware;
use Core\Settings;
use Core\BrandingHelper;

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

        // Dati reali grafici ultimi 7 giorni
        $creditsByDay = Database::fetchAll(
            "SELECT DATE(created_at) as day, SUM(ABS(amount)) as total
             FROM credit_transactions
             WHERE type = 'consume' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );

        $usersByDay = Database::fetchAll(
            "SELECT DATE(created_at) as day, COUNT(*) as total
             FROM users
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );

        // Costi API/AI del mese
        $apiCostMonth = Database::fetch(
            "SELECT SUM(cost) as total, COUNT(*) as calls,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
             FROM api_logs WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        $aiCostMonth = Database::fetch(
            "SELECT SUM(estimated_cost) as total, COUNT(*) as calls, SUM(tokens_total) as tokens
             FROM ai_logs WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        // Top 3 provider per spesa
        $topProviders = Database::fetchAll(
            "SELECT provider, SUM(cost) as total_cost, COUNT(*) as calls,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
             FROM api_logs WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
             GROUP BY provider ORDER BY total_cost DESC LIMIT 3"
        );

        return View::render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'topUsers' => $topUsers,
            'creditsByDay' => $creditsByDay ?: [],
            'usersByDay' => $usersByDay ?: [],
            'apiCostMonth' => $apiCostMonth ?: ['total' => 0, 'calls' => 0, 'errors' => 0],
            'aiCostMonth' => $aiCostMonth ?: ['total' => 0, 'calls' => 0, 'tokens' => 0],
            'topProviders' => $topProviders ?: [],
        ]);
    }

    public function users(): string
    {
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

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

        // Count totale
        $total = Database::fetch(
            "SELECT COUNT(*) as total FROM users WHERE {$where}",
            $params
        )['total'] ?? 0;
        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;

        $users = Database::fetchAll(
            "SELECT * FROM users WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
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
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'total_items' => $total,
                'per_page' => $perPage,
            ],
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

            // Solo aggiornare chiavi già esistenti in DB (whitelist implicita)
            $existing = Database::fetch("SELECT id FROM settings WHERE key_name = ?", [$key]);

            if ($existing) {
                Database::update(
                    'settings',
                    ['value' => $value, 'updated_by' => Auth::id()],
                    'key_name = ?',
                    [$key]
                );
            }
            // Ignora chiavi non esistenti - previene iniezione di settings arbitrari
        }

        Settings::clearCache();

        $_SESSION['_flash']['success'] = 'Impostazioni salvate con successo';
        \Core\Router::redirect('/admin/settings');
        return '';
    }

    /**
     * Test connessione SMTP (AJAX)
     */
    public function testSmtp(): string
    {
        Middleware::csrf();

        $result = \Services\EmailService::testConnection();

        header('Content-Type: application/json');
        return json_encode($result);
    }

    /**
     * Invia email di test (AJAX)
     */
    public function testEmail(): string
    {
        Middleware::csrf();

        $adminEmail = Auth::user()['email'] ?? '';

        if (empty($adminEmail)) {
            header('Content-Type: application/json');
            return json_encode(['success' => false, 'message' => 'Email admin non trovata']);
        }

        $result = \Services\EmailService::sendTestEmail($adminEmail);

        if ($result['success']) {
            $result['message'] = "Email di test inviata a {$adminEmail}";
        }

        header('Content-Type: application/json');
        return json_encode($result);
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

        if ($module['is_active']) {
            ModuleLoader::disable($module['slug']);
            $_SESSION['_flash']['success'] = 'Modulo disattivato';
        } else {
            ModuleLoader::enable($module['slug']);
            $_SESSION['_flash']['success'] = 'Modulo attivato';
        }
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
        $settingsGroups = [];

        if (file_exists($moduleJsonPath)) {
            $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
            $settingsSchema = $moduleJson['settings'] ?? [];
            $settingsGroups = $moduleJson['settings_groups'] ?? [];
        }

        // Get current settings values
        $currentSettings = json_decode($module['settings'] ?? '{}', true) ?: [];

        return View::render('admin/module-settings', [
            'title' => 'Impostazioni - ' . $module['name'],
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'module' => $module,
            'settingsSchema' => $settingsSchema,
            'settingsGroups' => $settingsGroups,
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

        // Save to database (via ModuleLoader to invalidate cache)
        ModuleLoader::updateModuleSettings($module['slug'], $currentSettings);

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

    /**
     * Rinomina un modulo
     */
    public function moduleRename(string $id): string
    {
        Middleware::csrf();

        $module = Database::fetch("SELECT * FROM modules WHERE id = ?", [$id]);
        if (!$module) {
            http_response_code(404);
            return View::render('errors/404');
        }

        $newName = trim($_POST['module_name'] ?? '');
        if (empty($newName)) {
            $_SESSION['_flash']['error'] = 'Il nome non può essere vuoto';
            \Core\Router::redirect('/admin/modules/' . $id . '/settings');
            return '';
        }

        if (mb_strlen($newName) > 100) {
            $newName = mb_substr($newName, 0, 100);
        }

        Database::update('modules', ['name' => $newName], 'id = ?', [$id]);
        Cache::delete("module_{$module['slug']}");
        Cache::delete('active_modules');

        $_SESSION['_flash']['success'] = 'Nome modulo aggiornato';
        \Core\Router::redirect('/admin/modules/' . $id . '/settings');
        return '';
    }

    /**
     * Salva impostazioni branding (colori, font, loghi)
     */
    public function brandingUpdate(): string
    {
        Middleware::csrf();

        $userId = Auth::id();

        // 1. Salva colori (validazione hex)
        $colorKeys = ['brand_color_primary', 'brand_color_secondary', 'brand_color_accent'];
        foreach ($colorKeys as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    Settings::set($key, $value, $userId);
                }
            }
        }

        // 2. Salva font (whitelist)
        $allowedFonts = BrandingHelper::getAllowedFonts();
        $font = $_POST['brand_font'] ?? 'Inter';
        if (in_array($font, $allowedFonts)) {
            Settings::set('brand_font', $font, $userId);
        }

        // 2b. Salva impostazioni branding email
        if (isset($_POST['email_brand_color'])) {
            $emailColor = trim($_POST['email_brand_color']);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $emailColor)) {
                Settings::set('email_brand_color', $emailColor, $userId);
            }
        }
        if (isset($_POST['email_logo_url'])) {
            $emailLogoUrl = trim($_POST['email_logo_url']);
            Settings::set('email_logo_url', $emailLogoUrl, $userId);
        }
        if (isset($_POST['email_footer_text'])) {
            $emailFooterText = trim($_POST['email_footer_text']);
            Settings::set('email_footer_text', $emailFooterText, $userId);
        }

        // 3. Gestisci upload loghi
        $uploadDir = \ROOT_PATH . '/public/assets/images/branding';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $logoFields = [
            'brand_logo_horizontal' => ['max_size' => 2097152, 'accept' => ['png', 'jpg', 'jpeg', 'svg', 'webp']],
            'brand_logo_square'     => ['max_size' => 2097152, 'accept' => ['png', 'jpg', 'jpeg', 'svg', 'webp']],
            'brand_favicon'         => ['max_size' => 524288,  'accept' => ['svg', 'png', 'ico']],
        ];

        foreach ($logoFields as $field => $rules) {
            // Check rimozione logo custom
            $removeKey = 'remove_' . str_replace('brand_', '', $field);
            if (!empty($_POST[$removeKey])) {
                $oldPath = Settings::get($field, '');
                if ($oldPath && str_contains($oldPath, 'branding/')) {
                    $fullOld = \ROOT_PATH . '/public/' . $oldPath;
                    if (file_exists($fullOld)) {
                        unlink($fullOld);
                    }
                }
                Settings::set($field, '', $userId);
                continue;
            }

            // Upload nuovo file
            if (!empty($_FILES[$field]['tmp_name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];

                // Validazione dimensione
                if ($file['size'] > $rules['max_size']) {
                    continue;
                }

                // Validazione estensione
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $rules['accept'])) {
                    continue;
                }

                // Rimuovi vecchio file custom
                $oldPath = Settings::get($field, '');
                if ($oldPath && str_contains($oldPath, 'branding/')) {
                    $fullOld = \ROOT_PATH . '/public/' . $oldPath;
                    if (file_exists($fullOld)) {
                        unlink($fullOld);
                    }
                }

                // Salva nuovo file
                $filename = str_replace('brand_', '', $field) . '-' . time() . '.' . $ext;
                $dest = $uploadDir . '/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    Settings::set($field, 'assets/images/branding/' . $filename, $userId);
                }
            }
        }

        Settings::clearCache();

        $_SESSION['_flash']['success'] = 'Aspetto aggiornato con successo';
        \Core\Router::redirect('/admin/settings?tab=branding');
        return '';
    }

    // ─── Cache Management ──────────────────────────────

    public function cache(): string
    {
        $stats = Cache::getStats();
        $keys = Cache::getKnownKeys();

        // Group keys
        $groups = [];
        foreach ($keys as $k) {
            $groups[$k['group']][] = $k;
        }

        // Log files info
        $logDir = __DIR__ . '/../../storage/logs';
        $logFiles = [];
        if (is_dir($logDir)) {
            foreach (glob($logDir . '/*.log') as $file) {
                $logFiles[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                ];
            }
            usort($logFiles, fn($a, $b) => $b['modified'] - $a['modified']);
        }

        return View::render('admin/cache', [
            'title' => 'Gestione Cache',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'stats' => $stats,
            'groups' => $groups,
            'logFiles' => $logFiles,
        ]);
    }

    public function cacheClear(): string
    {
        Middleware::csrf();

        Cache::clear();
        Settings::clearCache();

        $_SESSION['_flash']['success'] = 'Cache svuotata completamente';
        \Core\Router::redirect('/admin/cache');
        return '';
    }

    public function cacheClearKey(): string
    {
        Middleware::csrf();

        $key = $_POST['key'] ?? '';
        if ($key) {
            Cache::delete($key);
            $_SESSION['_flash']['success'] = "Cache '{$key}' invalidata";
        }

        \Core\Router::redirect('/admin/cache');
        return '';
    }
}
