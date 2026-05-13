<?php

namespace Editorial\Lib;

/**
 * RealLemonSqueezyClient
 *
 * STUB: l'implementazione reale sara' attivata quando il task M1.2 (creazione
 * account + prodotti Lemon Squeezy) sara' completato dal cliente umano.
 *
 * Per ora ogni metodo lancia LemonSqueezyException con messaggio chiaro,
 * cosi' se la factory ritorna questo client per errore l'origine del problema
 * e' immediatamente evidente.
 *
 * Quando M1.2 sara' fatto:
 *   1. Riempire i metodi con cURL POST/GET su https://api.lemonsqueezy.com/v1
 *   2. Usare bearer token da $this->apiKey
 *   3. Mappare la risposta JSON sui contratti dell'interfaccia
 */
class RealLemonSqueezyClient implements LemonSqueezyClientInterface
{
    private const BASE_URL = 'https://api.lemonsqueezy.com/v1';

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) (getenv('LEMONSQUEEZY_API_KEY') ?: '');
    }

    public function validateLicense(string $key): array
    {
        $this->failNotConfigured('validateLicense');
    }

    public function activateInstance(string $key, string $instanceName): array
    {
        $this->failNotConfigured('activateInstance');
    }

    public function deactivateInstance(string $key, string $instanceId): bool
    {
        $this->failNotConfigured('deactivateInstance');
    }

    public function getLicenseInfo(string $key): array
    {
        $this->failNotConfigured('getLicenseInfo');
    }

    public function getCustomer(string $customerId): array
    {
        $this->failNotConfigured('getCustomer');
    }

    /**
     * @return never
     */
    private function failNotConfigured(string $method): void
    {
        throw new LemonSqueezyException(
            "RealLemonSqueezyClient::{$method}() is not yet configured. "
            . "Complete task M1.2 (Lemon Squeezy account + products) and implement "
            . "the real API calls before switching LEMONSQUEEZY_MODE to 'real'."
        );
    }
}
