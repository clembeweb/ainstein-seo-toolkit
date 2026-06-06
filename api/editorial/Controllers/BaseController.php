<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Controllers;

use Ainstein\Editorial\Http\HttpException;
use Ainstein\Editorial\Http\Request;
use Ainstein\Editorial\Http\Response;

/**
 * Base per i controller dell'API editorial: helper di risposta e validazione.
 */
abstract class BaseController
{
    /** @param array<string,mixed> $data */
    protected function ok(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function notImplemented(string $milestone): never
    {
        Response::json([
            'error'     => 'Funzionalità non ancora disponibile.',
            'milestone' => $milestone,
            'detail'    => "Coming in {$milestone}.",
        ], 501);
    }

    /**
     * Verifica che i campi richiesti siano presenti e non vuoti nel body.
     *
     * @param list<string> $fields
     * @return array<string,mixed>
     */
    protected function validate(Request $request, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value === null || $value === '') {
                $missing[] = $field;
            }
        }
        if ($missing !== []) {
            throw new HttpException(422, 'Dati mancanti o non validi.', ['fields' => $missing]);
        }
        return $request->body;
    }
}
