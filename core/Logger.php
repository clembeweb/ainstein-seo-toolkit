<?php

namespace Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
    private static array $channels = [];
    private static string $logDir = '';

    private static function getLogDir(): string
    {
        if (self::$logDir === '') {
            self::$logDir = dirname(__DIR__) . '/storage/logs';
        }
        return self::$logDir;
    }

    /**
     * Get a logger instance for a specific channel
     */
    public static function channel(string $name = 'app'): MonologLogger
    {
        if (!isset(self::$channels[$name])) {
            self::$channels[$name] = self::createChannel($name);
        }
        return self::$channels[$name];
    }

    private static function createChannel(string $name): MonologLogger
    {
        $logger = new MonologLogger($name);

        $handler = new RotatingFileHandler(
            self::getLogDir() . "/{$name}.log",
            14, // 14 days retention
            MonologLogger::DEBUG
        );

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            'Y-m-d H:i:s',
            true,  // allow inline line breaks
            true   // ignore empty context
        );

        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    }
}
