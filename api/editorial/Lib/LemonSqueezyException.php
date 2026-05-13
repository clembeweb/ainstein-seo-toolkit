<?php

namespace Editorial\Lib;

/**
 * LemonSqueezyException
 *
 * Errori "tecnici" del client Lemon Squeezy (network, 5xx, auth).
 * Errori "di business" (license non valida, limite raggiunto) NON usano questa
 * eccezione: i metodi del client ritornano array con flag esplicito.
 */
class LemonSqueezyException extends \RuntimeException
{
}
