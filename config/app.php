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
        'rank_check' => 1,
        'volume_refresh' => 1,
        'link_analysis' => 1,

        // Standard (3 cr) - operazioni AI singole
        'serp_extraction' => 3,
        'brief_generation' => 3,
        'cover_image_generation' => 3,
        'meta_generate' => 3,
        'clustering' => 3,
        'quick_wins' => 3,
        'weekly_digest' => 3,
        'keyword_analysis' => 3,
        'anomaly_detection' => 3,
        'page_analysis' => 3,
        'gap_analysis' => 3,
        'context_extraction' => 3,
        'ad_group_analysis' => 3,
        'ai_generate' => 3,

        // Premium (10 cr) - operazioni AI complesse multi-step
        'article_generation' => 10,
        'gsc_full_sync' => 10,
        'monthly_executive' => 10,
        'action_plan_generate' => 10,
        'architecture' => 10,
        'editorial_plan' => 10,
        'campaign_evaluation' => 10,
        'creator_campaign' => 10,
        'full_optimization' => 10,
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
