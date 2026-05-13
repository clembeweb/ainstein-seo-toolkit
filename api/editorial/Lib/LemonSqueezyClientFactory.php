<?php

namespace Editorial\Lib;

/**
 * LemonSqueezyClientFactory
 *
 * Restituisce l'implementazione di LemonSqueezyClientInterface in base
 * all'env var LEMONSQUEEZY_MODE.
 *
 *   LEMONSQUEEZY_MODE=mock  (default) → MockLemonSqueezyClient
 *   LEMONSQUEEZY_MODE=real             → RealLemonSqueezyClient (richiede M1.2)
 *
 * Il client viene cachato a livello di processo (singleton) per evitare letture
 * multiple del file di stato del mock all'interno della stessa request.
 */
class LemonSqueezyClientFactory
{
    private static ?LemonSqueezyClientInterface $instance = null;

    public static function make(): LemonSqueezyClientInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $mode = strtolower((string) (getenv('LEMONSQUEEZY_MODE') ?: 'mock'));

        if ($mode === 'real') {
            $apiKey = (string) (getenv('LEMONSQUEEZY_API_KEY') ?: '');
            self::$instance = new RealLemonSqueezyClient($apiKey);
        } else {
            self::$instance = new MockLemonSqueezyClient();
        }

        return self::$instance;
    }

    /**
     * Util per test: forza un'implementazione specifica.
     */
    public static function set(LemonSqueezyClientInterface $client): void
    {
        self::$instance = $client;
    }

    /**
     * Util per test: resetta il singleton.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
