<?php

/**
 * Test Google Keyword Insight API (RapidAPI)
 *
 * Host: google-keyword-insight1.p.rapidapi.com
 * Scopo: esplorare struttura response per integrazione modulo keyword-research
 *
 * Uso: php public/test-keyword-insight-api.php
 */

// Bootstrap minimo
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Rome');

define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', BASE_PATH);

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

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

use Core\Settings;
use Core\Database;

// --- Config ---
$apiHost = 'google-keyword-insight1.p.rapidapi.com';
$baseUrl = 'https://google-keyword-insight1.p.rapidapi.com';
$keyword = 'consulente seo';
$location = 'IT';
$lang = 'it';

// --- Recupera API Key dal sistema ---
$apiKey = Settings::get('rapidapi_keyword_key');

if (empty($apiKey)) {
    echo "ERRORE: API key RapidAPI non trovata in settings (rapidapi_keyword_key)\n";
    echo "Configurala in Admin > Impostazioni\n";
    exit(1);
}

echo str_repeat('=', 80) . "\n";
echo "GOOGLE KEYWORD INSIGHT API - TEST\n";
echo str_repeat('=', 80) . "\n";
echo "Keyword: {$keyword}\n";
echo "Location: {$location} | Lang: {$lang}\n";
echo "Host: {$apiHost}\n";
echo "API Key: " . substr($apiKey, 0, 8) . "..." . substr($apiKey, -4) . "\n";
echo str_repeat('=', 80) . "\n\n";

/**
 * Chiamata generica all'API
 */
function callApi(string $endpoint, array $params, string $baseUrl, string $apiHost, string $apiKey): array
{
    $url = $baseUrl . $endpoint . '?' . http_build_query($params);

    echo ">>> GET {$endpoint}?" . http_build_query($params) . "\n";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'x-rapidapi-host: ' . $apiHost,
            'x-rapidapi-key: ' . $apiKey,
        ],
    ]);

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $elapsed = round((microtime(true) - $startTime) * 1000);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "<<< HTTP {$httpCode} ({$elapsed}ms)\n";

    if ($error) {
        echo "CURL ERROR: {$error}\n";
        return ['success' => false, 'error' => $error];
    }

    $data = json_decode($response, true);

    if ($data === null) {
        echo "JSON DECODE ERROR - Raw response (first 500 chars):\n";
        echo substr($response, 0, 500) . "\n";
        return ['success' => false, 'error' => 'Invalid JSON'];
    }

    return ['success' => true, 'data' => $data, 'http_code' => $httpCode];
}

/**
 * Stampa struttura response
 */
function printResponse(array $result, int $maxItems = 5): void
{
    if (!$result['success']) {
        echo "  ERRORE: {$result['error']}\n\n";
        return;
    }

    $data = $result['data'];

    // Mostra struttura top-level
    echo "\n  STRUTTURA TOP-LEVEL:\n";
    foreach ($data as $key => $value) {
        $type = gettype($value);
        if (is_array($value)) {
            $count = count($value);
            // Verifica se array sequenziale o associativo
            $isSequential = array_is_list($value);
            $type = $isSequential ? "array[{$count}]" : "object{" . implode(',', array_keys($value)) . "}";
        } elseif (is_string($value)) {
            $type = 'string("' . substr($value, 0, 50) . '")';
        } elseif (is_int($value) || is_float($value)) {
            $type = "number({$value})";
        } elseif (is_bool($value)) {
            $type = 'bool(' . ($value ? 'true' : 'false') . ')';
        } elseif (is_null($value)) {
            $type = 'null';
        }
        echo "    [{$key}] => {$type}\n";
    }

    // Trova array di risultati (cerca il primo array sequenziale grande)
    $resultsArray = null;
    $resultsKey = null;
    foreach ($data as $key => $value) {
        if (is_array($value) && !empty($value) && array_is_list($value)) {
            $resultsArray = $value;
            $resultsKey = $key;
            break;
        }
    }

    if ($resultsArray) {
        $total = count($resultsArray);
        $showing = min($maxItems, $total);
        echo "\n  RISULTATI ({$resultsKey}): {$total} totali, mostro primi {$showing}\n";
        echo "  " . str_repeat('-', 70) . "\n";

        // Mostra campi del primo item
        $firstItem = $resultsArray[0];
        echo "\n  CAMPI DISPONIBILI (primo item):\n";
        foreach ($firstItem as $key => $value) {
            $display = $value;
            if (is_array($value)) {
                $display = json_encode($value, JSON_UNESCAPED_UNICODE);
                if (strlen($display) > 80) {
                    $display = substr($display, 0, 80) . '...';
                }
            } elseif (is_null($value)) {
                $display = 'null';
            } elseif (is_bool($value)) {
                $display = $value ? 'true' : 'false';
            }
            echo "    {$key}: {$display}\n";
        }

        // Mostra primi N items in formato compatto
        echo "\n  PRIMI {$showing} RISULTATI:\n";
        for ($i = 0; $i < $showing; $i++) {
            $item = $resultsArray[$i];
            echo "\n  [{$i}] ";
            // Mostra keyword/text se presente
            $label = $item['keyword'] ?? $item['text'] ?? $item['query'] ?? $item['key'] ?? '(no label)';
            echo "{$label}\n";
            foreach ($item as $k => $v) {
                if (in_array($k, ['keyword', 'text', 'query', 'key'])) continue;
                if (is_array($v)) {
                    $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                    if (strlen($v) > 100) $v = substr($v, 0, 100) . '...';
                }
                echo "      {$k}: {$v}\n";
            }
        }
    } else {
        // Nessun array di risultati trovato, stampa tutto il JSON formattato
        echo "\n  RESPONSE COMPLETA:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n";
}

// ============================================================
// TEST 1: /keysuggest (base)
// ============================================================
echo str_repeat('-', 80) . "\n";
echo "TEST 1: /keysuggest (base)\n";
echo str_repeat('-', 80) . "\n";

$result1 = callApi('/keysuggest', [
    'keyword' => $keyword,
    'location' => $location,
    'lang' => $lang,
], $baseUrl, $apiHost, $apiKey);

printResponse($result1);

// ============================================================
// TEST 2: /keysuggest con return_intent e min_search_vol
// ============================================================
echo str_repeat('-', 80) . "\n";
echo "TEST 2: /keysuggest con intent + min_search_vol\n";
echo str_repeat('-', 80) . "\n";

$result2 = callApi('/keysuggest', [
    'keyword' => $keyword,
    'location' => $location,
    'lang' => $lang,
    'return_intent' => 'true',
    'min_search_vol' => 10,
], $baseUrl, $apiHost, $apiKey);

printResponse($result2);

// ============================================================
// TEST 3: /topkeys
// ============================================================
echo str_repeat('-', 80) . "\n";
echo "TEST 3: /topkeys (num=10)\n";
echo str_repeat('-', 80) . "\n";

$result3 = callApi('/topkeys', [
    'keyword' => $keyword,
    'location' => $location,
    'lang' => $lang,
    'num' => 10,
], $baseUrl, $apiHost, $apiKey);

printResponse($result3);

// ============================================================
// TEST 4: /questions
// ============================================================
echo str_repeat('-', 80) . "\n";
echo "TEST 4: /questions\n";
echo str_repeat('-', 80) . "\n";

$result4 = callApi('/questions', [
    'keyword' => $keyword,
    'location' => $location,
    'lang' => $lang,
], $baseUrl, $apiHost, $apiKey);

printResponse($result4);

// ============================================================
// RIEPILOGO
// ============================================================
echo str_repeat('=', 80) . "\n";
echo "RIEPILOGO CAMPI PER ENDPOINT\n";
echo str_repeat('=', 80) . "\n";

$endpoints = [
    'keysuggest (base)' => $result1,
    'keysuggest (intent)' => $result2,
    'topkeys' => $result3,
    'questions' => $result4,
];

foreach ($endpoints as $name => $result) {
    echo "\n{$name}:\n";
    if (!$result['success']) {
        echo "  ERRORE\n";
        continue;
    }

    $data = $result['data'];
    // Trova primo array di risultati
    foreach ($data as $key => $value) {
        if (is_array($value) && !empty($value) && array_is_list($value)) {
            $fields = array_keys($value[0]);
            echo "  Chiave risultati: {$key}\n";
            echo "  Num risultati: " . count($value) . "\n";
            echo "  Campi: " . implode(', ', $fields) . "\n";
            break;
        }
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "TEST COMPLETATO\n";
echo str_repeat('=', 80) . "\n";
