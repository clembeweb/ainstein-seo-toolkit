<?php

namespace Core;

class ModuleLoader
{
    private static array $loadedModules = [];

    public static function getActiveModules(): array
    {
        return Cache::get('active_modules', function () {
            return Database::fetchAll(
                "SELECT * FROM modules WHERE is_active = 1 ORDER BY name"
            );
        }, 300);
    }

    public static function getModule(string $slug): ?array
    {
        return Cache::get("module_{$slug}", function () use ($slug) {
            return Database::fetch(
                "SELECT * FROM modules WHERE slug = ?",
                [$slug]
            );
        }, 300);
    }

    public static function isModuleActive(string $slug): bool
    {
        $module = self::getModule($slug);
        return $module && $module['is_active'];
    }

    public static function load(string $slug): bool
    {
        if (isset(self::$loadedModules[$slug])) {
            return true;
        }

        if (!self::isModuleActive($slug)) {
            return false;
        }

        $modulePath = __DIR__ . '/../modules/' . $slug;

        if (!is_dir($modulePath)) {
            return false;
        }

        // Carica routes del modulo
        $routesFile = $modulePath . '/routes.php';
        if (file_exists($routesFile)) {
            require_once $routesFile;
        }

        self::$loadedModules[$slug] = true;
        return true;
    }

    public static function loadAll(): void
    {
        $modules = self::getActiveModules();

        foreach ($modules as $module) {
            self::load($module['slug']);
        }
    }

    public static function register(string $slug, string $name, string $description = '', string $version = '1.0.0'): int
    {
        $existing = self::getModule($slug);

        if ($existing) {
            Database::update(
                'modules',
                [
                    'name' => $name,
                    'description' => $description,
                    'version' => $version,
                ],
                'slug = ?',
                [$slug]
            );
            return $existing['id'];
        }

        return Database::insert('modules', [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'version' => $version,
            'is_active' => true,
        ]);
    }

    public static function enable(string $slug): bool
    {
        $result = Database::update(
            'modules',
            ['is_active' => true],
            'slug = ?',
            [$slug]
        ) > 0;

        Cache::delete("module_{$slug}");
        Cache::delete('active_modules');

        return $result;
    }

    public static function disable(string $slug): bool
    {
        $result = Database::update(
            'modules',
            ['is_active' => false],
            'slug = ?',
            [$slug]
        ) > 0;

        Cache::delete("module_{$slug}");
        Cache::delete('active_modules');

        return $result;
    }

    public static function getModuleSettings(string $slug): array
    {
        return Cache::get("ms_all_{$slug}", function () use ($slug) {
            $module = self::getModule($slug);

            if (!$module || !$module['settings']) {
                return [];
            }

            return json_decode($module['settings'], true) ?? [];
        }, 300);
    }

    /**
     * Get a single module setting by key
     */
    public static function getSetting(string $slug, string $key, mixed $default = null): mixed
    {
        $settings = self::getModuleSettings($slug);
        return $settings[$key] ?? $default;
    }

    public static function updateModuleSettings(string $slug, array $settings): bool
    {
        $result = Database::update(
            'modules',
            ['settings' => json_encode($settings)],
            'slug = ?',
            [$slug]
        ) > 0;

        // Invalidate all related caches (including cost_* which can't be enumerated)
        Cache::clear();

        return $result;
    }

    public static function getUserModules(?int $userId = null): array
    {
        // Per ora ritorna tutti i moduli attivi
        // In futuro filtrare per piano utente
        return self::getActiveModules();
    }
}
