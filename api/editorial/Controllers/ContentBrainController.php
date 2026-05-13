<?php

namespace Editorial\Controllers;

/**
 * ContentBrainController
 *
 * Gestione del "Content Brain" del progetto: scan iniziale del sito,
 * analisi tono di voce, target audience, content pillars.
 * Implementazione completa in M2.
 */
class ContentBrainController extends BaseController
{
    /** GET /content-brain */
    public function show(): string
    {
        return $this->jsonNotImplemented('2');
    }

    /** POST /content-brain/scan */
    public function scan(): string
    {
        return $this->jsonNotImplemented('2');
    }

    /** GET /content-brain/scan/{job_id}/stream */
    public function scanStream(string $jobId): string
    {
        unset($jobId);
        return $this->jsonNotImplemented('2');
    }

    /** PUT /content-brain */
    public function update(): string
    {
        return $this->jsonNotImplemented('2');
    }
}
