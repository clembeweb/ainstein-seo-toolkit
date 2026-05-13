<?php

namespace Editorial\Controllers;

/**
 * ImageController
 *
 * Generazione immagini featured per gli articoli (provider Gemini/DALL-E/SDXL).
 * Implementazione completa in M3.
 *
 * Nota: nessuna route registrata in M1.3 (gli endpoint image verranno
 * definiti in M3). Lo stub esiste per allineare il codebase al piano (M1.3.i).
 */
class ImageController extends BaseController
{
    /** POST /articles/{id}/images (placeholder) */
    public function generate(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('3');
    }
}
