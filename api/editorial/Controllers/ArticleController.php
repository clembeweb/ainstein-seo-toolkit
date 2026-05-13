<?php

namespace Editorial\Controllers;

/**
 * ArticleController
 *
 * Generazione, listing, dettaglio, regenerate, publish-to-WP degli articoli.
 * Implementazione completa in M3.
 */
class ArticleController extends BaseController
{
    /** POST /articles/generate */
    public function generate(): string
    {
        return $this->jsonNotImplemented('3');
    }

    /** GET /articles/generate/{job_id}/stream */
    public function generateStream(string $jobId): string
    {
        unset($jobId);
        return $this->jsonNotImplemented('3');
    }

    /** GET /articles */
    public function index(): string
    {
        return $this->jsonNotImplemented('3');
    }

    /** GET /articles/{id} */
    public function show(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('3');
    }

    /** POST /articles/{id}/publish-to-wp */
    public function publishToWp(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('3');
    }

    /** POST /articles/{id}/regenerate */
    public function regenerate(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('3');
    }
}
