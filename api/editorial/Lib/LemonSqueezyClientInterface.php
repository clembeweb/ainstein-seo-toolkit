<?php

namespace Editorial\Lib;

/**
 * LemonSqueezyClientInterface
 *
 * Astrazione del client Lemon Squeezy per disaccoppiare la logica di business
 * (LicenseManager) dalla concreta integrazione API.
 *
 * Implementazioni:
 *   - MockLemonSqueezyClient   → simulazione locale per dev/test (no LS account)
 *   - RealLemonSqueezyClient   → chiamate reali a https://api.lemonsqueezy.com/v1
 *
 * Selezione runtime via LemonSqueezyClientFactory (env LEMONSQUEEZY_MODE).
 *
 * Tutti i metodi devono lanciare LemonSqueezyException su errore (network, auth, etc).
 * Risposta "license invalid" NON e' un'eccezione: ritorna array con valid=false.
 */
interface LemonSqueezyClientInterface
{
    /**
     * Valida una license key. Non modifica stato remoto.
     *
     * @return array{
     *     valid: bool,
     *     status?: string,                  // active|expired|disabled|...
     *     tier?: string,                    // starter|pro|business|agency
     *     max_instances?: int,              // limite siti per la licenza
     *     activation_usage?: int,           // siti gia' attivati
     *     customer_id?: string,             // ID Lemon Squeezy customer
     *     customer_email?: string,
     *     subscription_id?: string,
     *     renews_at?: ?string,              // ISO datetime o null
     *     error?: string                    // se valid=false
     * }
     */
    public function validateLicense(string $key): array;

    /**
     * Attiva una nuova "instance" (sito) per la license key.
     *
     * @param string $key            License key
     * @param string $instanceName   Nome leggibile (es. dominio del sito)
     *
     * @return array{
     *     activated: bool,
     *     instance_id?: string,             // ID univoco assegnato dalla LS
     *     instance_name?: string,
     *     activation_usage?: int,
     *     activation_limit?: int,
     *     error?: string
     * }
     */
    public function activateInstance(string $key, string $instanceName): array;

    /**
     * Disattiva un'instance precedentemente attivata.
     */
    public function deactivateInstance(string $key, string $instanceId): bool;

    /**
     * Info estese di una licenza (include subscription/customer).
     * Stesso schema di validateLicense() ma sempre con dati completi.
     */
    public function getLicenseInfo(string $key): array;

    /**
     * Info di un customer Lemon Squeezy.
     *
     * @return array{
     *     id: string,
     *     email?: string,
     *     name?: string,
     *     status?: string,
     *     city?: ?string,
     *     country?: ?string
     * }
     */
    public function getCustomer(string $customerId): array;
}
