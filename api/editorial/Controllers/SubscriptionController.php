<?php

namespace Editorial\Controllers;

/**
 * SubscriptionController
 *
 * Stato sottoscrizione + portal-url per gestione billing (LemonSqueezy).
 * Implementazione parziale in M1.4 (status), completa in M6 (portal-url).
 */
class SubscriptionController extends BaseController
{
    /** GET /subscription/status */
    public function status(): string
    {
        return $this->jsonNotImplemented('1.4');
    }

    /** POST /subscription/portal-url */
    public function portalUrl(): string
    {
        return $this->jsonNotImplemented('6');
    }
}
