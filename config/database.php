<?php

// Load environment variables (if not already loaded)
require_once __DIR__ . '/environment.php';

return [
    'host' => env('DB_HOST', 'localhost'),
    'dbname' => env('DB_NAME', 'seo_toolkit'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
