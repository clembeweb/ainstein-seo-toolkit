<?php
/**
 * Environment Configuration Loader
 *
 * Loads .env file and provides env() helper function.
 * Include this file at the very beginning of your application bootstrap.
 */

// Prevent multiple inclusions
if (function_exists('env')) {
    return;
}

/**
 * Load .env file into environment
 */
function loadEnv(string $path): bool
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove surrounding quotes
            if (preg_match('/^"(.+)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    return true;
}

/**
 * Get environment variable with optional default value
 */
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // Convert string booleans
    $lower = strtolower($value);
    if ($lower === 'true') return true;
    if ($lower === 'false') return false;
    if ($lower === 'null') return null;

    // Remove quotes if present
    if (preg_match('/^"(.+)"$/', $value, $matches)) {
        return $matches[1];
    }
    if (preg_match("/^'(.+)'$/", $value, $matches)) {
        return $matches[1];
    }

    return $value;
}

// Auto-load .env from project root
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);
