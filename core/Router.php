<?php

namespace Core;

class Router
{
    private static array $routes = [];
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        self::$basePath = $path;
    }

    public static function get(string $path, callable|array $handler): void
    {
        self::addRoute('GET', $path, $handler);
    }

    public static function post(string $path, callable|array $handler): void
    {
        self::addRoute('POST', $path, $handler);
    }

    public static function any(string $path, callable|array $handler): void
    {
        self::addRoute('GET', $path, $handler);
        self::addRoute('POST', $path, $handler);
    }

    private static function addRoute(string $method, string $path, callable|array $handler): void
    {
        $pattern = self::pathToPattern($path);
        self::$routes[$method][$pattern] = [
            'handler' => $handler,
            'path' => $path,
        ];
    }

    private static function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Rimuovi query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Rimuovi base path
        if (str_starts_with($uri, self::$basePath)) {
            $uri = substr($uri, strlen(self::$basePath));
        }

        $uri = $uri ?: '/';
        $uri = rtrim($uri, '/') ?: '/';

        // Cerca route corrispondente
        if (isset(self::$routes[$method])) {
            foreach (self::$routes[$method] as $pattern => $route) {
                if (preg_match($pattern, $uri, $matches)) {
                    // Estrai parametri nominati
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    $handler = $route['handler'];

                    if (is_array($handler)) {
                        [$class, $method] = $handler;
                        $controller = new $class();
                        echo $controller->$method(...array_values($params));
                    } else {
                        echo $handler(...array_values($params));
                    }
                    return;
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        View::render('errors/404');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::$basePath . $path);
        exit;
    }

    public static function url(string $path): string
    {
        return self::$basePath . $path;
    }

    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? self::$basePath . '/';
        header('Location: ' . $referer);
        exit;
    }

    public static function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        if (str_starts_with($uri, self::$basePath)) {
            $uri = substr($uri, strlen(self::$basePath));
        }
        return $uri ?: '/';
    }
}
