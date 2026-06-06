<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Http;

/**
 * Rappresenta una richiesta HTTP in ingresso verso l'API editorial.
 * Il path è normalizzato relativamente al prefisso `/api/editorial/{version}`.
 */
class Request
{
    /** @var array<string,mixed> Corpo JSON decodificato */
    public array $body = [];
    /** @var array<string,string> */
    public array $query = [];
    /** @var array<string,string> Header normalizzati (lowercase) */
    public array $headers = [];
    /** @var array<string,mixed> Contesto popolato dai middleware (es. user, site) */
    public array $context = [];

    public string $method = 'GET';
    public string $path = '/';
    public string $rawBody = '';

    public static function capture(): self
    {
        $req = new self();
        $req->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Path relativo al prefisso API. Es. URI:
        //   /seo-toolkit/api/editorial/v1/activate  →  /activate
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $config  = require __DIR__ . '/../../../config/editorial.php';
        $version = $config['api_version'];
        $marker  = '/api/editorial/' . $version;
        if (($mp = strpos($uri, $marker)) !== false) {
            $uri = substr($uri, $mp + strlen($marker));
        } elseif (($mp = strpos($uri, '/api/editorial')) !== false) {
            // Richiesta senza versione: lasciamo il path grezzo per gestire 404 coerente
            $uri = substr($uri, $mp + strlen('/api/editorial'));
        }
        $req->path = '/' . trim($uri, '/');

        // Query string
        $req->query = $_GET;

        // Header (lowercase keys)
        foreach (self::collectHeaders() as $name => $value) {
            $req->headers[strtolower($name)] = $value;
        }

        // Body JSON
        $req->rawBody = file_get_contents('php://input') ?: '';
        if ($req->rawBody !== '') {
            $decoded = json_decode($req->rawBody, true);
            if (is_array($decoded)) {
                $req->body = $decoded;
            }
        }

        return $req;
    }

    /** @return array<string,string> */
    private static function collectHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            if (is_array($h)) {
                return $h;
            }
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }
}
