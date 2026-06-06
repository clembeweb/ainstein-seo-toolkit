<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Middleware;

use Ainstein\Editorial\Http\Request;

/**
 * Stub M1: lascia passare tutto.
 *
 * In M3 verrà implementato l'enforcement reale: confronto azioni consumate nel
 * periodo di billing (aied_actions_log) vs quota del tier + topup_balance.
 * Vedi QuotaService.
 */
class QuotaMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        // M1: no-op. Enforcement in M3.
    }
}
