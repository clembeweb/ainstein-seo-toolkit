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
 * Get environment variable with optional default value
 *
 * @param string $key     Environment variable name
 * @param mixed  $default Default value if not found
 * @return mixed
 */
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    // Remove quotes if present
    if (preg_match('/^"(.+)"$/', $value, $matches)) {
        return $matches[1];
    }
    if (preg_match("/^'(.+)'$/", $value, $matches)) {
        return $matches[1];
    }

    return $value;
}

/**
 * Load .env file into environment
 *
 * @param string $path Path to .env file
 * @return bool
 */
function loadEnv(string $path): bool
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Parse KEY=value
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

            // Set in environment
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    return true;
}

// Auto-load .env from project root
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);
