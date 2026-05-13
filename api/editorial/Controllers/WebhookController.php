<?php

namespace Editorial\Controllers;

/**
 * WebhookController
 *
 * Endpoint pubblici per webhooks da provider esterni (LemonSqueezy billing,
 * eventuali altri in futuro). Implementazione completa in M1.5.
 *
 * Nota: questi endpoint NON usano LicenseAuthMiddleware (chiamati da terze
 * parti senza JWT), ma fanno verifica di firma HMAC interna.
 */
class WebhookController extends BaseController
{
    /** POST /webhooks/lemonsqueezy */
    public function lemonsqueezy(): string
    {
        return $this->jsonNotImplemented('1.5');
    }
}
