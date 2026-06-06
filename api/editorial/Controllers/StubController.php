<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Controllers;

use Ainstein\Editorial\Http\Request;

/**
 * Endpoint non ancora implementati: ritornano 501 con la milestone target.
 * Permette al plugin di scoprire l'API completa già da M1.
 */
class StubController extends BaseController
{
    public function m2(Request $request): never
    {
        $this->notImplemented('M2');
    }

    public function m3(Request $request): never
    {
        $this->notImplemented('M3');
    }

    public function m4(Request $request): never
    {
        $this->notImplemented('M4');
    }

    public function m5(Request $request): never
    {
        $this->notImplemented('M5');
    }
}
