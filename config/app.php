<?php

return [
    'name' => 'SEO Toolkit',
    'url' => 'http://localhost/seo-toolkit',
    'debug' => true,

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
    ],

    // Stripe (predisposizione)
    'stripe' => [
        'enabled' => false,
        'public_key' => '',
        'secret_key' => '',
        'webhook_secret' => '',
    ],

    // Claude API
    'claude' => [
        'api_key' => '',
        'model' => 'claude-sonnet-4-20250514',
    ],

    // SMTP
    'smtp' => [
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'from_email' => 'noreply@seo-toolkit.local',
        'from_name' => 'SEO Toolkit',
    ],
];
