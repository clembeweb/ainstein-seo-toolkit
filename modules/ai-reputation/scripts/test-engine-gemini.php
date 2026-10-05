<?php
/**
 * AI Reputation Radar — Test empirico engine Gemini (Grounding with Google Search)
 *
 * Run: php modules/ai-reputation/scripts/test-engine-gemini.php [model] [interactions|generate]
 * Default: gemini-3.8-flash, interactions
 *   - interactions: POST /v1beta/interactions, tools [{type: google_search}] → steps + annotations url_citation
 *                   (API GA da giugno 2026, "raccomandata per i nuovi progetti")
 *   - generate:     POST /v1beta/models/{model}:generateContent, tools [{googleSearch: {}}] → groundingMetadata
 *                   (groundingChunks[].web.{uri,title,domain}, groundingSupports, webSearchQueries)
 *
 * Output: docs/test-empirici/AAAA-MM-GG-gemini-<model>-<mode>.json (risposte grezze complete)
 * Requisiti: google_gemini_api_key in admin settings, .env presente.
 * Verificato online il 2026-10-05:
 *   https://ai.google.dev/gemini-api/docs/google-search      → Interactions API, tool {type: google_search}
 *   https://ai.google.dev/api/generate-content               → generateContent, tool {googleSearch: {}}, GroundingMetadata
 *   https://ai.google.dev/gemini-api/docs/pricing            → Gemini 3.x: 5.000 ricerche gratis/mese poi 14 $/1.000;
 *                                                               gemini-3.8-flash 0,75 $/M in · 3,75 $/M out (fino al 2026-12-31)
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

$model = $argv[1] ?? 'gemini-3.8-flash';
$mode = $argv[2] ?? 'interactions';   // interactions | generate
if (!in_array($mode, ['interactions', 'generate'], true)) { fwrite(STDERR, "mode: interactions|generate\n"); exit(1); }

$apiKey = \Core\Settings::get('google_gemini_api_key');
if (empty($apiKey)) {
    fwrite(STDERR, "ERRORE: google_gemini_api_key non configurata (admin settings o scripts/set-api-key.php).\n");
    exit(1);
}

$prompts = [
    ['cluster' => 'nav',  'lang' => 'it', 'text' => 'Chi è Federico Marcaccini?'],
    ['cluster' => 'rep',  'lang' => 'it', 'text' => 'Federico Marcaccini imprenditore immobiliare: è affidabile? Ci sono recensioni o problemi?'],
    ['cluster' => 'comm', 'lang' => 'it', 'text' => 'Quali sono i migliori esperti di investimenti in hotel di lusso e riqualificazione alberghiera in Italia?'],
];

$outDir = dirname(__DIR__) . '/docs/test-empirici';
if (!is_dir($outDir)) mkdir($outDir, 0775, true);
$outFile = $outDir . '/' . date('Y-m-d') . "-gemini-{$model}-{$mode}.json";

/** Risolve un URL di redirect (vertexaisearch.cloud.google.com/grounding-api-redirect/…) con una HEAD senza seguire */
function resolveRedirect(string $url): ?string {
    if (!str_contains($url, 'vertexaisearch.cloud.google.com')) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
    curl_exec($ch);
    $loc = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return $loc ?: null;
}

$results = [];
echo "=== Gemini {$mode} + google_search | model={$model} ===\n\n";

foreach ($prompts as $p) {
    if ($mode === 'interactions') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/interactions';
        $body = ['model' => $model, 'input' => $p['text'], 'tools' => [['type' => 'google_search']]];
    } else {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $body = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $p['text']]]]],
            'tools' => [['googleSearch' => (object) []]],
        ];
    }

    $t0 = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey],
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
    $queries = [];
    $citations = [];   // cosa ha CITATO (annotazioni / groundingSupports)
    $chunks = [];      // cosa ha LETTO (groundingChunks, solo generate)
    $tokensIn = $tokensOut = $tokensTool = null;
    $modelUsed = $json['model'] ?? $json['modelVersion'] ?? null;

    if ($mode === 'interactions') {
        foreach ($json['steps'] ?? [] as $step) {
            $type = $step['type'] ?? '';
            if ($type === 'google_search_call') $queries = array_merge($queries, $step['arguments']['queries'] ?? $step['queries'] ?? []);
            if ($type === 'model_output') {
                foreach ($step['content'] ?? [] as $c) {
                    $text .= $c['text'] ?? '';
                    foreach ($c['annotations'] ?? [] as $a) {
                        if (($a['type'] ?? '') === 'url_citation') {
                            $citations[] = ['url' => $a['url'] ?? null, 'title' => $a['title'] ?? null, 'resolved' => resolveRedirect((string) ($a['url'] ?? ''))];
                        }
                    }
                }
            }
        }
        // alcune risposte mettono l'output anche in outputs[]
        if ($text === '') {
            foreach ($json['outputs'] ?? [] as $o) { $text .= $o['text'] ?? ''; foreach ($o['annotations'] ?? [] as $a) { if (($a['type'] ?? '') === 'url_citation') $citations[] = ['url' => $a['url'] ?? null, 'title' => $a['title'] ?? null, 'resolved' => resolveRedirect((string) ($a['url'] ?? ''))]; } }
        }
        $u = $json['usage'] ?? [];
        $tokensIn = $u['total_input_tokens'] ?? null;
        $tokensOut = $u['total_output_tokens'] ?? null;
        $tokensTool = $u['total_tool_use_tokens'] ?? null;
    } else {
        $cand = $json['candidates'][0] ?? [];
        foreach ($cand['content']['parts'] ?? [] as $part) $text .= $part['text'] ?? '';
        $gm = $cand['groundingMetadata'] ?? [];
        $queries = $gm['webSearchQueries'] ?? [];
        foreach ($gm['groundingChunks'] ?? [] as $i => $gc) {
            $w = $gc['web'] ?? [];
            $chunks[] = ['idx' => $i, 'uri' => $w['uri'] ?? null, 'title' => $w['title'] ?? null, 'domain' => $w['domain'] ?? null, 'resolved' => resolveRedirect((string) ($w['uri'] ?? ''))];
        }
        $citedIdx = [];
        foreach ($gm['groundingSupports'] ?? [] as $gs) foreach ($gs['groundingChunkIndices'] ?? [] as $ci) $citedIdx[$ci] = true;
        foreach ($chunks as $c) if (isset($citedIdx[$c['idx']])) $citations[] = ['url' => $c['uri'], 'title' => $c['title'], 'domain' => $c['domain'], 'resolved' => $c['resolved']];
        $u = $json['usageMetadata'] ?? [];
        $tokensIn = $u['promptTokenCount'] ?? null;
        $tokensOut = $u['candidatesTokenCount'] ?? null;
        $tokensTool = $u['toolUsePromptTokenCount'] ?? null;
    }

    $summary = [
        'prompt' => $p,
        'mode' => $mode,
        'http' => $http,
        'curl_error' => $err ?: null,
        'latency_ms' => $ms,
        'model_used' => $modelUsed,
        'tokens_in' => $tokensIn,
        'tokens_out' => $tokensOut,
        'tokens_tool_use' => $tokensTool,
        'queries' => $queries,
        'grounding_chunks' => $chunks,
        'citations' => $citations,
        'text' => $text,
        'error' => $json['error'] ?? (($http >= 400) ? ($json ?: $raw) : null),
    ];
    $results[] = ['summary' => $summary, 'raw' => $json];

    echo "--- [{$p['cluster']}/{$p['lang']}] {$p['text']}\n";
    echo "HTTP {$http} | {$ms} ms | in={$tokensIn} out={$tokensOut} tool={$tokensTool} | model={$modelUsed}\n";
    if ($summary['error']) { echo "ERRORE API: " . json_encode($summary['error'], JSON_UNESCAPED_UNICODE) . "\n\n"; continue; }
    echo "Query: " . count($queries) . " | Chunk letti: " . count($chunks) . " | Citazioni: " . count($citations) . "\n";
    foreach ($queries as $q) echo "  q: {$q}\n";
    $seen = [];
    foreach ($citations as $c) {
        $shown = $c['resolved'] ?? $c['url'];
        $host = parse_url((string) $shown, PHP_URL_HOST) ?: $c['domain'] ?? '?';
        if (isset($seen[$host . ($c['title'] ?? '')])) continue; $seen[$host . ($c['title'] ?? '')] = 1;
        echo "  - {$host}  " . ($c['title'] ?? '') . (str_contains((string) $c['url'], 'vertexaisearch') ? '  [redirect]' : '') . "\n";
    }
    echo "Testo (primi 400 car.): " . mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400) . "…\n\n";
}

file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Salvato: {$outFile}\n";
