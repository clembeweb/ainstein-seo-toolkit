<?php
/**
 * Configurazione Editorial — mapping prodotti/varianti LemonSqueezy.
 *
 * I variant_id reali saranno popolati dal cliente in M1.2 (account LS setup).
 * Fino ad allora si usano placeholder coerenti coi test webhook fixture.
 *
 * Override locali: definire ENV `LEMONSQUEEZY_VARIANT_<TIER>_ID` (es. LEMONSQUEEZY_VARIANT_STARTER_ID=12345).
 * Topup: definire `LEMONSQUEEZY_VARIANT_TOPUP_<PACK>_ID` (es. LEMONSQUEEZY_VARIANT_TOPUP_10_ID=99001).
 *
 * Convenzione:
 *   - 'subscriptions'  → variant_id → tier subscription
 *   - 'topups'         → variant_id → numero articoli accreditati
 */

return [
    'lemonsqueezy' => [
        'webhook_secret' => (string) (getenv('LEMONSQUEEZY_WEBHOOK_SECRET') ?: ''),

        // Subscriptions: variant_id (string) → tier
        'subscriptions' => [
            (string) (getenv('LEMONSQUEEZY_VARIANT_STARTER_ID')  ?: 'variant_starter_test')  => 'starter',
            (string) (getenv('LEMONSQUEEZY_VARIANT_PRO_ID')      ?: 'variant_pro_test')      => 'pro',
            (string) (getenv('LEMONSQUEEZY_VARIANT_BUSINESS_ID') ?: 'variant_business_test') => 'business',
            (string) (getenv('LEMONSQUEEZY_VARIANT_AGENCY_ID')   ?: 'variant_agency_test')   => 'agency',
        ],

        // Top-up packs: variant_id (string) → articoli accreditati
        'topups' => [
            (string) (getenv('LEMONSQUEEZY_VARIANT_TOPUP_10_ID')  ?: 'variant_topup10_test')  => 10,
            (string) (getenv('LEMONSQUEEZY_VARIANT_TOPUP_25_ID')  ?: 'variant_topup25_test')  => 25,
            (string) (getenv('LEMONSQUEEZY_VARIANT_TOPUP_100_ID') ?: 'variant_topup100_test') => 100,
        ],
    ],
];
