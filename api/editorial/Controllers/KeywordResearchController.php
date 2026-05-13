<?php

namespace Editorial\Controllers;

/**
 * KeywordResearchController
 *
 * Ricerca keyword via API esterna (RapidAPI Google Keyword Insight),
 * SSE per streaming risultati. Implementazione completa in M4.
 */
class KeywordResearchController extends BaseController
{
    /** POST /keywords/research */
    public function research(): string
    {
        return $this->jsonNotImplemented('4');
    }

    /** GET /keywords/research/{job_id}/stream */
    public function researchStream(string $jobId): string
    {
        unset($jobId);
        return $this->jsonNotImplemented('4');
    }
}
