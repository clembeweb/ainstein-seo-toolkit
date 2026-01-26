<?php

namespace Core {

class View
{
    private static array $data = [];
    private static ?string $layout = 'layout';

    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        self::$data = array_merge(self::$data, $data);

        $viewPath = self::resolvePath($view);

        if (!file_exists($viewPath)) {
            throw new \Exception("View non trovata: {$view}");
        }

        $content = self::capture($viewPath, self::$data);

        // Se layout specificato o default
        $useLayout = $layout ?? self::$layout;

        if ($useLayout) {
            $layoutPath = __DIR__ . '/../shared/views/' . $useLayout . '.php';
            if (file_exists($layoutPath)) {
                self::$data['content'] = $content;
                return self::capture($layoutPath, self::$data);
            }
        }

        return $content;
    }

    public static function partial(string $view, array $data = []): string
    {
        $viewPath = self::resolvePath($view);

        if (!file_exists($viewPath)) {
            return '';
        }

        return self::capture($viewPath, array_merge(self::$data, $data));
    }

    private static function resolvePath(string $view): string
    {
        // Supporta sintassi module::view (es. seo-tracking::rank-check/index)
        if (str_contains($view, '::')) {
            [$module, $viewName] = explode('::', $view, 2);
            $modulePath = __DIR__ . '/../modules/' . $module . '/views/' . $viewName . '.php';
            if (file_exists($modulePath)) {
                return $modulePath;
            }
        }

        // Se inizia con 'admin/', cerca in admin/views/ senza il prefisso
        if (str_starts_with($view, 'admin/')) {
            $adminView = substr($view, 6); // Rimuove 'admin/'
            $adminPath = __DIR__ . '/../admin/views/' . $adminView . '.php';
            if (file_exists($adminPath)) {
                return $adminPath;
            }
        }

        // Cerca in shared/views
        $sharedPath = __DIR__ . '/../shared/views/' . $view . '.php';
        if (file_exists($sharedPath)) {
            return $sharedPath;
        }

        // Cerca in admin/views (path completo)
        $adminPath = __DIR__ . '/../admin/views/' . $view . '.php';
        if (file_exists($adminPath)) {
            return $adminPath;
        }

        // Cerca nei moduli
        $parts = explode('/', $view);
        if (count($parts) >= 2) {
            $modulePath = __DIR__ . '/../modules/' . $parts[0] . '/views/' . implode('/', array_slice($parts, 1)) . '.php';
            if (file_exists($modulePath)) {
                return $modulePath;
            }
        }

        return $sharedPath;
    }

    private static function capture(string $path, array $data): string
    {
        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    public static function setLayout(?string $layout): void
    {
        self::$layout = $layout;
    }

    public static function noLayout(): void
    {
        self::$layout = null;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function json(mixed $data, int $statusCode = 200): string
    {
        self::noLayout();
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}

} // end namespace Core

// Helper functions in global namespace
namespace {

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path): string
    {
        return \Core\Router::url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return \Core\Router::url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = $_SESSION['_csrf_token'] ?? '';
        return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return $_SESSION['_csrf_token'] ?? '';
    }
}

if (!function_exists('__')) {
    /**
     * Translation helper function
     * Returns the translation for the given key, or the key itself if not found
     */
    function __(string $key, ?string $default = null): string
    {
        // For now, return the key or default (translations can be added later)
        return $default ?? $key;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value (supports dot notation: 'serpapi.api_key')
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $configFile = __DIR__ . '/../config/app.php';
            $config = file_exists($configFile) ? require $configFile : [];
        }

        // Support dot notation for nested keys
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = $config;
            foreach ($keys as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }
                $value = $value[$segment];
            }
            return $value;
        }

        return $config[$key] ?? $default;
    }
}

if (!function_exists('getModuleSetting')) {
    /**
     * Get a module setting from database
     */
    function getModuleSetting(string $moduleSlug, string $key, mixed $default = null): mixed
    {
        return \Core\ModuleLoader::getSetting($moduleSlug, $key, $default);
    }
}

if (!function_exists('jsonResponse')) {
    /**
     * Send JSON response and exit
     */
    function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

} // end global namespace
