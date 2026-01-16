<?php

namespace Core;

/**
 * Settings
 * Gestisce le impostazioni globali dalla tabella settings
 */
class Settings
{
    private static array $cache = [];
    private static bool $loaded = false;

    /**
     * Carica tutte le impostazioni in cache
     */
    private static function loadAll(): void
    {
        if (self::$loaded) {
            return;
        }

        try {
            $results = Database::fetchAll("SELECT key_name, value FROM settings");
            foreach ($results as $row) {
                self::$cache[$row['key_name']] = $row['value'];
            }
            self::$loaded = true;
        } catch (\Exception $e) {
            self::$loaded = true;
        }
    }

    /**
     * Ottieni valore impostazione
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    /**
     * Imposta valore
     */
    public static function set(string $key, mixed $value, ?int $userId = null): bool
    {
        try {
            $existing = Database::fetch(
                "SELECT id FROM settings WHERE key_name = ?",
                [$key]
            );

            if ($existing) {
                Database::update('settings', [
                    'value' => $value,
                    'updated_by' => $userId,
                ], 'key_name = ?', [$key]);
            } else {
                Database::insert('settings', [
                    'key_name' => $key,
                    'value' => $value,
                    'updated_by' => $userId,
                ]);
            }

            self::$cache[$key] = $value;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica se esiste
     */
    public static function has(string $key): bool
    {
        self::loadAll();
        return isset(self::$cache[$key]);
    }

    /**
     * Ottieni multiple impostazioni
     */
    public static function getMany(array $keys): array
    {
        self::loadAll();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::$cache[$key] ?? null;
        }
        return $result;
    }

    /**
     * Ottieni tutte le impostazioni (non segrete)
     */
    public static function all(): array
    {
        self::loadAll();
        return self::$cache;
    }

    /**
     * Invalida cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$loaded = false;
    }
}
