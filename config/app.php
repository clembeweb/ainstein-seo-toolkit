<?php

// Load environment variables
require_once __DIR__ . '/environment.php';

return [
    'name' => env('APP_NAME', 'SEO Toolkit'),
    'url' => env('APP_URL', 'http://localhost/seo-toolkit'),
    'debug' => env('APP_DEBUG', false),

    // Crediti free tier
    'free_credits' => 30,

    // Costi operazioni (crediti) - 4 livelli: Gratis(0), Base(1), Standard(3), Premium(10)
    'credit_costs' => [
        // Gratis (0 cr) - consultazione, export, visualizzazione
        'export_csv' => 0,
        'export_excel' => 0,

        // Base (1 cr) - operazioni singole leggere
        'scrape_url' => 1,
        'content_scrape' => 1,

        // Standard (3 cr) - operazioni AI singole
        'ai_analysis_small' => 3,
        'ai_analysis_medium' => 3,
        'ai_analysis_large' => 3,
        'serp_extraction' => 3,
        'cover_image_generation' => 3,

        // Premium (10 cr) - operazioni AI complesse multi-step
        'article_generation' => 10,
    ],

    // Stripe (predisposizione)
    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', false),
        'public_key' => env('STRIPE_PUBLIC_KEY', ''),
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],

    // Claude API
    'claude' => [
        'api_key' => env('CLAUDE_API_KEY', ''),
        'model' => env('CLAUDE_MODEL', 'claude-sonnet-4-20250514'),
    ],

    // SerpAPI
    'serpapi' => [
        'api_key' => env('SERPAPI_KEY', ''),
    ],

    // Google APIs
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    ],

    // SMTP
    'smtp' => [
        'host' => env('SMTP_HOST', ''),
        'port' => env('SMTP_PORT', 587),
        'username' => env('SMTP_USER', ''),
        'password' => env('SMTP_PASS', ''),
        'from_email' => env('SMTP_FROM_EMAIL', 'noreply@seo-toolkit.local'),
        'from_name' => env('SMTP_FROM_NAME', 'SEO Toolkit'),
    ],
];
