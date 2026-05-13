<?php

namespace Editorial\Controllers;

/**
 * ActivationController
 *
 * Endpoints per attivazione licenza, deattivazione, refresh JWT.
 * Implementazione completa in M1.4.
 */
class ActivationController extends BaseController
{
    /** POST /activate */
    public function activate(): string
    {
        return $this->jsonNotImplemented('1.4');
    }

    /** POST /deactivate */
    public function deactivate(): string
    {
        return $this->jsonNotImplemented('1.4');
    }

    /** POST /refresh-token */
    public function refreshToken(): string
    {
        return $this->jsonNotImplemented('1.4');
    }
}
