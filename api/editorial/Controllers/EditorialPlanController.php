<?php

namespace Editorial\Controllers;

/**
 * EditorialPlanController
 *
 * CRUD piani editoriali (collezioni di keyword + cluster + briefing).
 * Implementazione completa in M4.
 */
class EditorialPlanController extends BaseController
{
    /** GET /editorial-plans */
    public function index(): string
    {
        return $this->jsonNotImplemented('4');
    }

    /** POST /editorial-plans */
    public function store(): string
    {
        return $this->jsonNotImplemented('4');
    }

    /** PUT /editorial-plans/{id} */
    public function update(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('4');
    }

    /** POST /editorial-plans/{id}/activate */
    public function activate(string $id): string
    {
        unset($id);
        return $this->jsonNotImplemented('4');
    }
}
