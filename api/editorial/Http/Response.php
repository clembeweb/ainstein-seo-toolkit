<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Http;

/**
 * Helper per emettere risposte JSON e terminare la richiesta.
 */
class Response
{
    /**
     * Emette una risposta JSON e termina l'esecuzione.
     *
     * @param array<string,mixed> $data
     */
    public static function json(array $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
