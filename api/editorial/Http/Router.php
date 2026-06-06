<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Http;

/**
 * Router minimale per l'API editorial: matching method+path con parametri {param},
 * pipeline di middleware per-rotta, dispatch verso [Controller::class, 'method'].
 *
 * I controller emettono la risposta via Response::json() (exit). I middleware
 * lanciano HttpException per gli errori. Tutto incapsulato in try/catch → JSON.
 */
class Router
{
    /** @var list<array{method:string,pattern:string,handler:array,middleware:list<string>}> */
    private array $routes = [];

    /**
     * @param array{0:class-string,1:string} $handler
     * @param list<class-string> $middleware
     */
    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $pattern = '#^' . preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        try {
            $allowedMethods = [];

            foreach ($this->routes as $route) {
                if (!preg_match($route['pattern'], $request->path, $matches)) {
                    continue;
                }
                if ($route['method'] !== $request->method) {
                    $allowedMethods[] = $route['method'];
                    continue;
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Pipeline middleware
                foreach ($route['middleware'] as $mwClass) {
                    /** @var \Ainstein\Editorial\Middleware\MiddlewareInterface $mw */
                    $mw = new $mwClass();
                    $mw->handle($request);
                }

                [$class, $method] = $route['handler'];
                $controller = new $class();
                $controller->{$method}($request, ...array_values($params));
                return; // i controller terminano con Response::json (exit); difesa extra:
            }

            if ($allowedMethods !== []) {
                throw new HttpException(405, 'Metodo non consentito per questa risorsa.', [
                    'allowed' => array_values(array_unique($allowedMethods)),
                ]);
            }

            throw new HttpException(404, 'Endpoint non trovato.');
        } catch (HttpException $e) {
            Response::json($e->toPayload(), $e->status);
        }
    }
}
