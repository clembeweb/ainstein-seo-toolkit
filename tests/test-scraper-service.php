<?php
/**
 * Test ScraperService methods after refactoring
 */

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', BASE_PATH);

// Simple autoloader
spl_autoload_register(function ($class) {
    $paths = [
        'Core\\' => BASE_PATH . '/core/',
        'Services\\' => BASE_PATH . '/services/',
    ];

    foreach ($paths as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

use Services\ScraperService;

echo "=== Test ScraperService Methods ===\n\n";

$scraper = new ScraperService();
$passed = 0;
$failed = 0;

// Test 1: fetchRaw with custom headers preserves User-Agent
echo "1. Testing fetchRaw with custom headers...\n";
$result = $scraper->fetchRaw('https://httpbin.org/headers', [
    'headers' => ['X-Custom-Header: test-value'],
    'timeout' => 15,
]);

if ($result['success'] ?? false) {
    $data = json_decode($result['body'], true);
    $headers = $data['headers'] ?? [];

    // Check if User-Agent is present
    $hasUserAgent = isset($headers['User-Agent']) && strpos($headers['User-Agent'], 'Mozilla') !== false;
    $hasCustomHeader = isset($headers['X-Custom-Header']) && $headers['X-Custom-Header'] === 'test-value';

    if ($hasUserAgent && $hasCustomHeader) {
        echo "   ✓ PASS: User-Agent preserved and custom header sent\n";
        echo "   User-Agent: " . substr($headers['User-Agent'], 0, 50) . "...\n";
        echo "   X-Custom-Header: {$headers['X-Custom-Header']}\n";
        $passed++;
    } else {
        echo "   ✗ FAIL: Headers not correctly merged\n";
        echo "   User-Agent present: " . ($hasUserAgent ? 'yes' : 'no') . "\n";
        echo "   Custom header present: " . ($hasCustomHeader ? 'yes' : 'no') . "\n";
        $failed++;
    }
} else {
    echo "   ✗ FAIL: Request failed - " . ($result['message'] ?? 'unknown error') . "\n";
    $failed++;
}

echo "\n";

// Test 2: fetchJson returns proper structure
echo "2. Testing fetchJson with JSON endpoint...\n";
$result = $scraper->fetchJson('https://httpbin.org/json', [
    'timeout' => 15,
]);

if (($result['success'] ?? false) && isset($result['data'])) {
    echo "   ✓ PASS: fetchJson returned valid data structure\n";
    echo "   HTTP Code: {$result['http_code']}\n";
    echo "   Data type: " . gettype($result['data']) . "\n";
    $passed++;
} else {
    echo "   ✗ FAIL: " . ($result['message'] ?? 'Invalid response structure') . "\n";
    $failed++;
}

echo "\n";

// Test 3: postRaw with custom headers preserves User-Agent
echo "3. Testing postRaw with custom headers...\n";
$result = $scraper->postRaw('https://httpbin.org/post', ['test' => 'data'], [
    'headers' => ['X-API-Key: test-key', 'Accept: application/json'],
    'timeout' => 15,
]);

if ($result['success'] ?? false) {
    $data = json_decode($result['body'], true);
    $headers = $data['headers'] ?? [];

    $hasUserAgent = isset($headers['User-Agent']) && strpos($headers['User-Agent'], 'Mozilla') !== false;
    $hasApiKey = isset($headers['X-Api-Key']) && $headers['X-Api-Key'] === 'test-key';
    $hasContentType = isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') !== false;

    if ($hasUserAgent && $hasApiKey && $hasContentType) {
        echo "   ✓ PASS: All headers correctly merged\n";
        echo "   User-Agent: present\n";
        echo "   X-API-Key: present\n";
        echo "   Content-Type: {$headers['Content-Type']}\n";
        $passed++;
    } else {
        echo "   ✗ FAIL: Headers not correctly merged\n";
        echo "   User-Agent present: " . ($hasUserAgent ? 'yes' : 'no') . "\n";
        echo "   X-API-Key present: " . ($hasApiKey ? 'yes' : 'no') . "\n";
        echo "   Content-Type present: " . ($hasContentType ? 'yes' : 'no') . "\n";
        $failed++;
    }
} else {
    echo "   ✗ FAIL: Request failed - " . ($result['message'] ?? 'unknown error') . "\n";
    $failed++;
}

echo "\n";

// Test 4: postJson returns proper structure
echo "4. Testing postJson with JSON data...\n";
$result = $scraper->postJson('https://httpbin.org/post', ['key' => 'value'], [
    'X-Custom: header',
]);

if (($result['success'] ?? false) && isset($result['data'])) {
    $sentData = $result['data']['json'] ?? [];
    if (isset($sentData['key']) && $sentData['key'] === 'value') {
        echo "   ✓ PASS: postJson sent and received JSON correctly\n";
        echo "   HTTP Code: {$result['http_code']}\n";
        echo "   Sent data received: key = {$sentData['key']}\n";
        $passed++;
    } else {
        echo "   ✗ FAIL: JSON data not correctly transmitted\n";
        $failed++;
    }
} else {
    echo "   ✗ FAIL: " . ($result['message'] ?? 'Invalid response structure') . "\n";
    $failed++;
}

echo "\n";

// Test 5: head method works
echo "5. Testing head method...\n";
$result = $scraper->head('https://httpbin.org/status/200', 15);

if (($result['success'] ?? false) && $result['http_code'] === 200) {
    echo "   ✓ PASS: HEAD request successful\n";
    echo "   HTTP Code: {$result['http_code']}\n";
    $passed++;
} else {
    echo "   ✗ FAIL: " . ($result['message'] ?? 'HEAD request failed') . "\n";
    $failed++;
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed! ScraperService is working correctly.\n";
} else {
    echo "\n✗ Some tests failed. Please review the issues above.\n";
}
