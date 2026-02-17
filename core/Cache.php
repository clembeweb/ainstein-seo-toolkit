<?php

namespace Core;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Psr\Cache\CacheItemInterface;

class Cache
{
    private static ?FilesystemAdapter $adapter = null;

    private static function getAdapter(): FilesystemAdapter
    {
        if (self::$adapter === null) {
            self::$adapter = new FilesystemAdapter(
                'ainstein',
                300, // default TTL 5 minutes
                dirname(__DIR__) . '/storage/cache'
            );
        }
        return self::$adapter;
    }

    /**
     * Get cached value or compute and store it
     */
    public static function get(string $key, callable $callback, int $ttl = 300): mixed
    {
        $item = self::getAdapter()->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $value = $callback();

        $item->set($value);
        $item->expiresAfter($ttl);
        self::getAdapter()->save($item);

        return $value;
    }

    /**
     * Delete a cached item
     */
    public static function delete(string $key): bool
    {
        return self::getAdapter()->deleteItem($key);
    }

    /**
     * Clear all cache
     */
    public static function clear(): bool
    {
        return self::getAdapter()->clear();
    }

    /**
     * Check if key exists and is not expired
     */
    public static function has(string $key): bool
    {
        return self::getAdapter()->hasItem($key);
    }

    /**
     * Get cache directory stats (file count, total size)
     */
    public static function getStats(): array
    {
        $cacheDir = dirname(__DIR__) . '/storage/cache/ainstein';
        $stats = ['files' => 0, 'size' => 0, 'directory' => $cacheDir];

        if (!is_dir($cacheDir)) {
            return $stats;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $stats['files']++;
                $stats['size'] += $file->getSize();
            }
        }

        return $stats;
    }

    /**
     * Get known cache keys status
     */
    public static function getKnownKeys(): array
    {
        $keys = [
            ['key' => 'all_settings', 'label' => 'Settings globali', 'group' => 'core', 'ttl' => '5 min'],
            ['key' => 'active_modules', 'label' => 'Moduli attivi', 'group' => 'core', 'ttl' => '5 min'],
        ];

        // Add module-specific keys
        try {
            $modules = Database::fetchAll("SELECT slug, name FROM modules WHERE is_active = 1");
            foreach ($modules as $mod) {
                $keys[] = [
                    'key' => "module_{$mod['slug']}",
                    'label' => "Modulo: {$mod['name']}",
                    'group' => 'modules',
                    'ttl' => '5 min',
                ];
                $keys[] = [
                    'key' => "ms_all_{$mod['slug']}",
                    'label' => "Settings: {$mod['name']}",
                    'group' => 'modules',
                    'ttl' => '5 min',
                ];
            }
        } catch (\Exception $e) {
            // DB not available
        }

        // Check which are cached
        foreach ($keys as &$k) {
            $k['cached'] = self::has($k['key']);
        }

        return $keys;
    }
}
