<?php

namespace Editorial\Controllers;

/**
 * MetaTagController
 *
 * Generazione meta title / meta description per gli articoli.
 * Implementazione completa in M3.
 *
 * Nota: nessuna route registrata in M1.3 (gli endpoint meta tag verranno
 * definiti in M3 quando sara chiaro il flow). Lo stub esiste per allineare
 * il codebase al piano (M1.3.i).
 */
class MetaTagController extends BaseController
{
    /** POST /articles/{id}/meta-tags (placeholder) */
    public function generate(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('3');
    }
}
