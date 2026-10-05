<?php
/**
 * AI Reputation Radar — Test empirico engine Anthropic (Messages API + web_search server tool)
 *
 * Run: php modules/ai-reputation/scripts/test-engine-anthropic.php [model] [max_uses] [direct|dynamic]
 * Default: claude-opus-5-5, 5, direct
 *
 * Output: docs/test-empirici/AAAA-MM-GG-anthropic-<model>.json (risposte grezze complete)
 * Requisiti: anthropic_api_key in admin settings, .env presente.
 * Verificato online il 2026-10-05:
 *   https://platform.claude.com/docs/en/agents-and-tools/tool-use/web-search-tool
 *   tool type web_search_20260318 · 10 $ / 1.000 ricerche + token · citazioni web_search_result_location
 * Nota: curl diretto (come AiService) perché il repo non usa l'SDK PHP Anthropic.
 */

// Bootstrap CLI minimo (stesso autoloader di public/index.php)
define('BASE_PATH', dirname(__DIR__, 3));
define('ROOT_PATH', BASE_PATH);
date_default_timezone_set('Europe/Rome');
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) $autoload = 'C:/xampp/htdocs/seo-toolkit/vendor/autoload.php';
require_once $autoload;
spl_autoload_register(function ($class) {
    $paths = ['Core\\' => BASE_PATH . '/core/', 'Services\\' => BASE_PATH . '/services/'];
    foreach ($paths as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $file = $basePath . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});
require_once BASE_PATH . '/config/app.php';

$model = $argv[1] ?? 'claude-opus-5-5';
$maxUses = (int) ($argv[2] ?? 5);
$mode = $argv[3] ?? 'direct';   // direct = senza filtraggio dinamico (citazioni nel testo) | dynamic = default del tool

$apiKey = \Core\Settings::get('anthropic_api_key');
if (empty($apiKey)) {
    fwrite(STDERR, "ERRORE: anthropic_api_key non configurata.\n");
    exit(1);
}

$prompts = [
    ['cluster' => 'nav',  'lang' => 'it', 'text' => 'Chi è Federico Marcaccini?'],
    ['cluster' => 'rep',  'lang' => 'it', 'text' => 'Federico Marcaccini imprenditore immobiliare: è affidabile? Ci sono recensioni o problemi?'],
    ['cluster' => 'comm', 'lang' => 'it', 'text' => 'Quali sono i migliori esperti di investimenti in hotel di lusso e riqualificazione alberghiera in Italia?'],
];

$outDir = dirname(__DIR__) . '/docs/test-empirici';
if (!is_dir($outDir)) mkdir($outDir, 0775, true);
$outFile = $outDir . '/' . date('Y-m-d') . "-anthropic-{$model}-{$mode}.json";

$results = [];
echo "=== Anthropic Messages API + web_search_20260318 | model={$model} | max_uses={$maxUses} ===\n\n";

foreach ($prompts as $p) {
    $body = [
        'model' => $model,
        'max_tokens' => 4096,
        'messages' => [['role' => 'user', 'content' => $p['text']]],
        'tools' => [[
            'type' => 'web_search_20260318',
            'name' => 'web_search',
            'max_uses' => $maxUses,
            'user_location' => ['type' => 'approximate', 'country' => 'IT', 'timezone' => 'Europe/Rome'],
        ] + ($mode === 'direct' ? ['allowed_callers' => ['direct']] : [])],
    ];

    $t0 = microtime(true);
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_TIMEOUT => 300,
    ]);
    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $ms = (int) round((microtime(true) - $t0) * 1000);

    $json = json_decode($raw, true);
    $text = '';
    $citations = [];
    $searchQueries = [];
    $searchResults = [];
    foreach ($json['content'] ?? [] as $block) {
        $type = $block['type'] ?? '';
        if ($type === 'text') {
            $text .= $block['text'] ?? '';
            foreach ($block['citations'] ?? [] as $c) {
                if (($c['type'] ?? '') === 'web_search_result_location') {
                    $citations[] = ['url' => $c['url'], 'title' => $c['title'] ?? null, 'cited_text' => $c['cited_text'] ?? null];
                }
            }
        } elseif ($type === 'server_tool_use' && ($block['name'] ?? '') === 'web_search') {
            $searchQueries[] = $block['input']['query'] ?? null;
        } elseif ($type === 'web_search_tool_result') {
            if (isset($block['content']['type']) && $block['content']['type'] === 'web_search_tool_result_error') {
                $searchResults[] = ['error' => $block['content']['error_code']];
            } else {
                foreach ($block['content'] ?? [] as $r) {
                    $searchResults[] = ['url' => $r['url'] ?? null, 'title' => $r['title'] ?? null, 'page_age' => $r['page_age'] ?? null];
                }
            }
        }
    }

    $usage = $json['usage'] ?? [];
    $summary = [
        'prompt' => $p,
        'http' => $http,
        'curl_error' => $err ?: null,
        'latency_ms' => $ms,
        'model_used' => $json['model'] ?? null,
        'stop_reason' => $json['stop_reason'] ?? null,
        'tokens_in' => $usage['input_tokens'] ?? null,
        'tokens_out' => $usage['output_tokens'] ?? null,
        'web_search_requests' => $usage['server_tool_use']['web_search_requests'] ?? null,
        'search_queries' => $searchQueries,
        'search_results_count' => count($searchResults),
        'citations' => $citations,
        'text' => $text,
        'error' => $json['error'] ?? null,
    ];
    $results[] = ['summary' => $summary, 'search_results' => $searchResults, 'raw' => $json];

    echo "--- [{$p['cluster']}/{$p['lang']}] {$p['text']}\n";
    echo "HTTP {$http} | {$ms} ms | in={$summary['tokens_in']} out={$summary['tokens_out']} | stop={$summary['stop_reason']} | model={$summary['model_used']}\n";
    if ($summary['error']) { echo "ERRORE API: " . json_encode($summary['error'], JSON_UNESCAPED_UNICODE) . "\n\n"; continue; }
    echo "Ricerche: {$summary['web_search_requests']} | Risultati: " . count($searchResults) . " | Citazioni: " . count($citations) . "\n";
    foreach ($searchQueries as $q) echo "   q: {$q}\n";
    $seen = [];
    foreach ($citations as $c) {
        $host = parse_url($c['url'], PHP_URL_HOST);
        if (isset($seen[$c['url']])) continue;
        $seen[$c['url']] = true;
        echo "  - {$host}  " . ($c['title'] ?? '') . "\n";
    }
    echo "Testo (primi 400 car.): " . mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400) . "…\n\n";
}

file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Salvato: {$outFile}\n";
