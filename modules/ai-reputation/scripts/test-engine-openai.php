<?php
/**
 * AI Reputation Radar — Test empirico engine OpenAI (Responses API + web_search)
 *
 * Run: php modules/ai-reputation/scripts/test-engine-openai.php [model] [search_context_size]
 * Default: gpt-5-mini, medium
 *
 * Output: docs/test-empirici/AAAA-MM-GG-openai-<model>.json (risposte grezze complete)
 * Requisiti: openai_api_key in admin settings, .env presente.
 * Verificato online il 2026-10-05: https://developers.openai.com/api/docs/guides/tools-web-search
 */

// Bootstrap CLI minimo (stesso autoloader di public/index.php)
define('BASE_PATH', dirname(__DIR__, 3));
define('ROOT_PATH', BASE_PATH);
date_default_timezone_set('Europe/Rome');
// Nel worktree manca vendor/ (gitignored, lock non installabile) → usa quello del checkout principale
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

$model = $argv[1] ?? 'gpt-5-mini';
$contextSize = $argv[2] ?? 'medium';

$apiKey = \Core\Settings::get('openai_api_key');
if (empty($apiKey)) {
    fwrite(STDERR, "ERRORE: openai_api_key non configurata.\n");
    exit(1);
}

$prompts = [
    ['cluster' => 'nav',  'lang' => 'it', 'text' => 'Chi è Federico Marcaccini?'],
    ['cluster' => 'rep',  'lang' => 'it', 'text' => 'Federico Marcaccini imprenditore immobiliare: è affidabile? Ci sono recensioni o problemi?'],
    ['cluster' => 'comm', 'lang' => 'it', 'text' => 'Quali sono i migliori esperti di investimenti in hotel di lusso e riqualificazione alberghiera in Italia?'],
];

$outDir = dirname(__DIR__) . '/docs/test-empirici';
if (!is_dir($outDir)) mkdir($outDir, 0775, true);
$outFile = $outDir . '/' . date('Y-m-d') . "-openai-{$model}.json";

$results = [];
echo "=== OpenAI Responses API + web_search | model={$model} | context={$contextSize} ===\n\n";

foreach ($prompts as $i => $p) {
    $body = [
        'model' => $model,
        'tools' => [[
            'type' => 'web_search',
            'search_context_size' => $contextSize,
            'user_location' => ['type' => 'approximate', 'country' => 'IT'],
        ]],
        'include' => ['web_search_call.action.sources'],
        'input' => $p['text'],
    ];

    $t0 = microtime(true);
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_TIMEOUT => 180,
    ]);
    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $ms = (int) round((microtime(true) - $t0) * 1000);

    $json = json_decode($raw, true);
    $text = '';
    $citations = [];
    $searchCalls = [];
    foreach ($json['output'] ?? [] as $item) {
        if (($item['type'] ?? '') === 'web_search_call') {
            $searchCalls[] = [
                'action' => $item['action']['type'] ?? null,
                'query' => $item['action']['query'] ?? null,
                'sources' => array_column($item['action']['sources'] ?? [], 'url'),
            ];
        }
        if (($item['type'] ?? '') === 'message') {
            foreach ($item['content'] ?? [] as $c) {
                $text .= $c['text'] ?? '';
                foreach ($c['annotations'] ?? [] as $a) {
                    if (($a['type'] ?? '') === 'url_citation') {
                        $citations[] = ['url' => $a['url'], 'title' => $a['title'] ?? null];
                    }
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
        'tokens_in' => $usage['input_tokens'] ?? null,
        'tokens_out' => $usage['output_tokens'] ?? null,
        'search_calls' => $searchCalls,
        'citations' => $citations,
        'text' => $text,
        'error' => $json['error'] ?? null,
    ];
    $results[] = ['summary' => $summary, 'raw' => $json];

    echo "--- [{$p['cluster']}/{$p['lang']}] {$p['text']}\n";
    echo "HTTP {$http} | {$ms} ms | in={$summary['tokens_in']} out={$summary['tokens_out']} | model={$summary['model_used']}\n";
    if ($summary['error']) { echo "ERRORE API: " . json_encode($summary['error'], JSON_UNESCAPED_UNICODE) . "\n\n"; continue; }
    echo "Ricerche: " . count($searchCalls) . " | Citazioni: " . count($citations) . "\n";
    foreach ($citations as $c) echo "  - " . parse_url($c['url'], PHP_URL_HOST) . "  " . ($c['title'] ?? '') . "\n";
    echo "Testo (primi 400 car.): " . mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400) . "…\n\n";
}

file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Salvato: {$outFile}\n";
