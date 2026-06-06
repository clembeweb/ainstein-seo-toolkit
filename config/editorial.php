<?php

/**
 * Ainstein Editorial — Configurazione backend API
 *
 * Prodotto SaaS autonomo (plugin WordPress) che riusa l'infra Ainstein.
 * Credenziali sensibili (JWT secret, Lemon Squeezy) SOLO via env (.env / .env.local).
 *
 * Riferimento: plugins/ainstein-editorial/docs/design.md §11, milestones/M1-foundation.md
 */

require_once __DIR__ . '/environment.php';

return [
    'api_version' => 'v1',

    // JWT short-lived per autenticazione plugin → backend (HS256)
    'jwt' => [
        'secret' => env('AIED_JWT_SECRET', ''),
        'ttl'    => 86400, // 24h
        'issuer' => 'ainstein-editorial',
    ],

    // Lemon Squeezy = single source of truth per billing (Golden Rule #4 plugin)
    'lemonsqueezy' => [
        'api_key'        => env('LEMONSQUEEZY_API_KEY', ''),
        'store_id'       => env('LEMONSQUEEZY_STORE_ID', ''),
        'webhook_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET', ''),
        'api_base'       => 'https://api.lemonsqueezy.com/v1',
        'api_accept'     => 'application/vnd.api+json',
    ],

    // Limiti per tier. `sites` = activation limit license. `articles` = quota mensile (enforced M3).
    'tiers' => [
        'starter'  => ['sites' => 1,  'articles' => 12],
        'pro'      => ['sites' => 3,  'articles' => 40],
        'business' => ['sites' => 10, 'articles' => 120],
        'agency'   => ['sites' => 50, 'articles' => 500],
    ],

    // Costo in "azioni" per operazione (vedi design.md §5; enforcement quota in M3)
    'action_costs' => [
        'article'            => 10,
        'keyword_research'   => 5,
        'meta_tag'           => 1,
        'image'              => 3,
        'internal_link_scan' => 1,
    ],

    // Rate limit per license key
    'rate_limit' => [
        'per_minute' => 60,
    ],
];
