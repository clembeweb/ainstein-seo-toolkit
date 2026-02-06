<?php

// Load environment variables
require_once __DIR__ . '/environment.php';

return [
    'name' => env('APP_NAME', 'SEO Toolkit'),
    'url' => env('APP_URL', 'http://localhost/seo-toolkit'),
    'debug' => env('APP_DEBUG', false),

    // Crediti free tier
    'free_credits' => 50,

    // Costi operazioni (crediti)
    'credit_costs' => [
        'scrape_url' => 0.1,
        'ai_analysis_small' => 1,
        'ai_analysis_medium' => 2,
        'ai_analysis_large' => 5,
        'export_csv' => 0,
        'export_excel' => 0.5,
        // AI Content module costs
        'serp_extraction' => 3,
        'content_scrape' => 1,
        'article_generation' => 10,
        'cover_image_generation' => 3,
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
