<?php

namespace Editorial\Controllers;

/**
 * InternalLinkController
 *
 * Suggerimento e applicazione link interni tra articoli.
 * Implementazione completa in M5.
 */
class InternalLinkController extends BaseController
{
    /** POST /links/suggest */
    public function suggest(): string
    {
        return $this->jsonNotImplemented('5');
    }

    /** POST /links/apply */
    public function apply(): string
    {
        return $this->jsonNotImplemented('5');
    }
}
