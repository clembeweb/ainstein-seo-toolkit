<?php
/**
 * Test SerpAPI con parametro engine=google
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
define('ROOT_PATH', BASE_PATH);

// Autoloader
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

// Load helpers
require_once BASE_PATH . '/core/View.php';

echo "=== TEST SERPAPI con engine=google ===\n\n";

// Get API key from settings
$apiKey = getModuleSetting('ai-content', 'serpapi_key', '');

if (empty($apiKey)) {
    die("ERROR: serpapi_key non configurata nelle impostazioni\n");
}

echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Build URL exactly as SerpApiService does (now with engine parameter)
$params = [
    'engine' => 'google',  // NEW - REQUIRED
    'q' => 'seo tips',
    'location' => 'Italy',
    'hl' => 'it',
    'gl' => 'it',
    'google_domain' => 'google.it',
    'num' => 10,
    'api_key' => $apiKey
];

$url = 'https://serpapi.com/search.json?' . http_build_query($params);

echo "URL: " . preg_replace('/api_key=[^&]+/', 'api_key=***', $url) . "\n\n";

// Make request
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data) {
    echo "ERROR: Invalid JSON response\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

if (isset($data['error'])) {
    echo "SerpAPI Error: " . $data['error'] . "\n";
    exit(1);
}

// Check results
$organic = $data['organic_results'] ?? [];
$paa = $data['related_questions'] ?? [];
$related = $data['related_searches'] ?? [];

echo "\n=== RISULTATI ===\n";
echo "Organic results: " . count($organic) . "\n";
echo "PAA questions: " . count($paa) . "\n";
echo "Related searches: " . count($related) . "\n";

if (count($organic) > 0) {
    echo "\n--- Top 3 Organic ---\n";
    foreach (array_slice($organic, 0, 3) as $i => $r) {
        echo ($i + 1) . ". " . ($r['title'] ?? 'N/A') . "\n";
        echo "   " . ($r['link'] ?? 'N/A') . "\n";
    }
}

if (count($paa) > 0) {
    echo "\n--- PAA Questions ---\n";
    foreach (array_slice($paa, 0, 4) as $p) {
        echo "- " . ($p['question'] ?? 'N/A') . "\n";
    }
}

echo "\n=== TEST COMPLETATO CON SUCCESSO ===\n";
