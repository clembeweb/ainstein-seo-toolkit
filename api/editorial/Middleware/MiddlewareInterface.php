<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Middleware;

use Ainstein\Editorial\Http\Request;

interface MiddlewareInterface
{
    /**
     * Esegue il middleware. In caso di violazione lancia HttpException.
     * In caso di successo popola $request->context e ritorna void.
     */
    public function handle(Request $request): void;
}
