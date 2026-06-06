<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Controllers;

use Ainstein\Editorial\Http\Request;

/**
 * Health check pubblico: il plugin lo usa per verificare la raggiungibilità
 * del backend prima dell'attivazione.
 */
class HealthController extends BaseController
{
    public function ping(Request $request): never
    {
        $config = require __DIR__ . '/../../../config/editorial.php';
        $this->ok([
            'status'      => 'ok',
            'service'     => 'ainstein-editorial-api',
            'api_version' => $config['api_version'],
            'time'        => date('c'),
        ]);
    }
}
