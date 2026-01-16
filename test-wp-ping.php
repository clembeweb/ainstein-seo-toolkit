<?php
/**
 * Test WordPress Ping with Real API Key
 */

echo "=== TEST WORDPRESS PING ===\n\n";

$siteUrl = 'https://clementeteodonno.it';
$apiKey = 'stk_e738bd058a3c056bb1f8a30c81a4ac13d04730dcc1818e6d';

// Test 1: Ping endpoint
echo "1. PING ENDPOINT\n";
echo str_repeat('-', 50) . "\n";

$pingEndpoint = $siteUrl . '/wp-json/seo-toolkit/v1/ping';
echo "URL: $pingEndpoint\n";
echo "API Key: " . substr($apiKey, 0, 15) . "...\n\n";

$ch = curl_init($pingEndpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'X-SEO-Toolkit-Key: ' . $apiKey,
        'User-Agent: SEO-Toolkit/1.0',
        'Accept: application/json',
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error: " . ($error ?: 'none') . " (errno: $errno)\n";
echo "Response: $response\n\n";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        if (isset($data['success']) && $data['success']) {
            echo "✓ SUCCESS! Plugin connesso.\n";
            echo "  WP Version: " . ($data['wp_version'] ?? 'N/A') . "\n";
            echo "  Plugin Version: " . ($data['plugin_version'] ?? 'N/A') . "\n";
        } else {
            echo "✗ ERROR: " . ($data['message'] ?? $data['code'] ?? 'Unknown') . "\n";
        }
    }
}

echo "\n";

// Test 2: Categories endpoint
echo "2. CATEGORIES ENDPOINT\n";
echo str_repeat('-', 50) . "\n";

$catEndpoint = $siteUrl . '/wp-json/seo-toolkit/v1/categories';
echo "URL: $catEndpoint\n\n";

$ch = curl_init($catEndpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'X-SEO-Toolkit-Key: ' . $apiKey,
        'User-Agent: SEO-Toolkit/1.0',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['categories'])) {
        echo "✓ Found " . count($data['categories']) . " categories\n";
        foreach (array_slice($data['categories'], 0, 5) as $cat) {
            echo "  - [{$cat['id']}] {$cat['name']}\n";
        }
    }
}

echo "\n=== TEST COMPLETE ===\n";
