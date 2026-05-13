<?php

namespace Editorial\Controllers;

/**
 * BaseController per Editorial API
 *
 * Helper per risposte JSON consistenti su tutti gli endpoint REST.
 * Tutti i controller dell'Editorial API estendono questa classe.
 */
abstract class BaseController
{
    /**
     * Risposta JSON di successo.
     */
    protected function jsonOk(array|object $data = [], int $status = 200): string
    {
        $this->emit($status, $data);
        return '';
    }

    /**
     * Risposta JSON di errore.
     */
    protected function jsonError(string $message, int $status = 400, array $extra = []): string
    {
        $payload = array_merge(['error' => $message], $extra);
        $this->emit($status, $payload);
        return '';
    }

    /**
     * Risposta 501 Not Implemented per stub.
     * Usata da tutti gli endpoint che saranno implementati in milestone successive.
     */
    protected function jsonNotImplemented(string $milestone): string
    {
        $payload = [
            'error' => "Coming in M{$milestone}",
            'milestone' => $milestone,
            'status' => 'not_implemented',
        ];
        $this->emit(501, $payload);
        return '';
    }

    /**
     * Emette risposta JSON con headers e termina l'esecuzione.
     */
    private function emit(int $status, array|object $payload): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Legge il body JSON della richiesta come array.
     */
    protected function jsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
