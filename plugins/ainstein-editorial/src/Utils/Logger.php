<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Utils;

/**
 * Logger minimale del plugin. Scrive su error_log solo se WP_DEBUG attivo.
 */
class Logger
{
    /** @param array<string,mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /** @param array<string,mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /** @param array<string,mixed> $context */
    private static function write(string $level, string $message, array $context): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        $line = '[Ainstein Editorial][' . $level . '] ' . $message;
        if ($context !== []) {
            $line .= ' ' . wp_json_encode($context);
        }
        error_log($line);
    }
}
