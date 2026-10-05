<?php
/**
 * AI Reputation Radar — Test empirico engine Perplexity (Agent API /v1/agent + web_search)
 *
 * Run: php modules/ai-reputation/scripts/test-engine-perplexity.php [preset|model] [search_context_size]
 * Default: fast, medium
 *   - preset:  fast | low | medium | high | xhigh   (fast = sostituto ufficiale di sonar / sonar-pro)
 *   - model:   id con prefisso provider, es. perplexity/sonar, anthropic/claude-sonnet-5-5
 *
 * Output: docs/test-empirici/AAAA-MM-GG-perplexity-<preset|model>.json (risposte grezze complete)
 * Requisiti: perplexity_api_key in admin settings, .env presente.
 * Verificato online il 2026-10-05:
 *   https://docs.perplexity.ai/docs/agent-api/migrate-from-sonar/overview.md
 *     → "Sonar Chat Completions support ended on September 27, 2026": sonar → preset fast,
 *       sonar-pro → fast, sonar-reasoning-pro → low, sonar-deep-research → high
 *   https://docs.perplexity.ai/api-reference/agent-post.md
 *     → POST https://api.perplexity.ai/v1/agent · input · preset|model · tools[] · instructions
 *   https://docs.perplexity.ai/docs/agent-api/tools/web-search.md
 *     → tool web_search 2,50 $/1.000 invocazioni (1 $ con search_type fast); search_context_size low|medium|high;
 *       user_location.country ISO-2; risultati in output item "search_results" (url, title, snippet, date)
 *   https://docs.perplexity.ai/docs/agent-api/models.md
 *     → perplexity/sonar 0,25 $/M in · 2,50 $/M out
 *   usage.cost.total_cost è restituito dall'API: costo reale, non stimato.
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

$target = $argv[1] ?? 'fast';           // preset (fast|low|medium|high|xhigh) oppure model "provider/nome"
$contextSize = $argv[2] ?? 'medium';    // low | medium | high
$isModel = str_contains($target, '/');

$apiKey = \Core\Settings::get('perplexity_api_key');
if (empty($apiKey)) {
    fwrite(STDERR, "ERRORE: perplexity_api_key non configurata (admin settings o scripts/set-api-key.php).\n");
    exit(1);
}

$prompts = [
    ['cluster' => 'nav',  'lang' => 'it', 'text' => 'Chi è Federico Marcaccini?'],
    ['cluster' => 'rep',  'lang' => 'it', 'text' => 'Federico Marcaccini imprenditore immobiliare: è affidabile? Ci sono recensioni o problemi?'],
    ['cluster' => 'comm', 'lang' => 'it', 'text' => 'Quali sono i migliori esperti di investimenti in hotel di lusso e riqualificazione alberghiera in Italia?'],
];

$outDir = dirname(__DIR__) . '/docs/test-empirici';
if (!is_dir($outDir)) mkdir($outDir, 0775, true);
$label = str_replace('/', '_', $target);
$outFile = $outDir . '/' . date('Y-m-d') . "-perplexity-{$label}.json";

$results = [];
echo "=== Perplexity Agent API /v1/agent | " . ($isModel ? "model={$target}" : "preset={$target}") . " | context={$contextSize} ===\n\n";

foreach ($prompts as $p) {
    $body = [
        'input' => $p['text'],
        'tools' => [[
            'type' => 'web_search',
            'search_context_size' => $contextSize,
            'user_location' => ['country' => 'IT'],
        ]],
    ];
    if ($isModel) {
        $body['model'] = $target;
        $body['max_output_tokens'] = 4096;   // obbligatorio per i modelli Anthropic, innocuo per gli altri
    } else {
        $body['preset'] = $target;
    }

    $t0 = microtime(true);
    $ch = curl_init('https://api.perplexity.ai/v1/agent');
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
    $citations = [];      // url_citation nelle annotazioni del messaggio (cosa ha CITATO)
    $searchResults = [];  // output item search_results (cosa ha LETTO)
    $queries = [];
    foreach ($json['output'] ?? [] as $item) {
        $type = $item['type'] ?? '';
        if ($type === 'search_results') {
            $queries = array_merge($queries, $item['queries'] ?? []);
            foreach ($item['results'] ?? [] as $r) {
                $searchResults[] = [
                    'id' => $r['id'] ?? null,
                    'url' => $r['url'] ?? null,
                    'title' => $r['title'] ?? null,
                    'date' => $r['date'] ?? null,
                    'snippet' => isset($r['snippet']) ? mb_substr($r['snippet'], 0, 200) : null,
                ];
            }
        }
        if ($type === 'message') {
            foreach ($item['content'] ?? [] as $c) {
                $text .= $c['text'] ?? '';
                foreach ($c['annotations'] ?? [] as $a) {
                    if (($a['type'] ?? '') === 'url_citation') {
                        $citations[] = ['url' => $a['url'] ?? null, 'title' => $a['title'] ?? null];
                    }
                }
            }
        }
    }
    if ($text === '' && !empty($json['output_text'])) $text = $json['output_text'];

    // Citazioni inline [n] nel testo → risolte sui search_results (id = n), come fa Sonar
    preg_match_all('/\[(\d+)\]/', $text, $m);
    $inlineIds = array_values(array_unique(array_map('intval', $m[1] ?? [])));
    $inlineCited = [];
    foreach ($searchResults as $r) {
        if (in_array((int) $r['id'], $inlineIds, true)) $inlineCited[] = ['url' => $r['url'], 'title' => $r['title']];
    }

    $usage = $json['usage'] ?? [];
    $summary = [
        'prompt' => $p,
        'http' => $http,
        'curl_error' => $err ?: null,
        'latency_ms' => $ms,
        'status' => $json['status'] ?? null,
        'model_used' => $json['model'] ?? null,
        'tokens_in' => $usage['input_tokens'] ?? null,
        'tokens_out' => $usage['output_tokens'] ?? null,
        'search_invocations' => $usage['tool_calls_details']['web_search']['invocation'] ?? null,
        'cost_usd' => $usage['cost']['total_cost'] ?? null,
        'queries' => $queries,
        'search_results' => $searchResults,
        'citations_annotations' => $citations,
        'citations_inline_ids' => $inlineIds,
        'citations_inline' => $inlineCited,
        'text' => $text,
        'error' => $json['error'] ?? (($http >= 400) ? ($json ?: $raw) : null),
    ];
    $results[] = ['summary' => $summary, 'raw' => $json];

    echo "--- [{$p['cluster']}/{$p['lang']}] {$p['text']}\n";
    echo "HTTP {$http} | {$ms} ms | in={$summary['tokens_in']} out={$summary['tokens_out']} | model={$summary['model_used']} | status={$summary['status']}\n";
    if ($summary['error']) { echo "ERRORE API: " . json_encode($summary['error'], JSON_UNESCAPED_UNICODE) . "\n\n"; continue; }
    echo "Ricerche: " . ($summary['search_invocations'] ?? '?') . " | Query: " . count($queries) . " | Risultati letti: " . count($searchResults)
        . " | Citazioni annotazioni: " . count($citations) . " | Citazioni inline [n]: " . count($inlineIds)
        . " | Costo: " . ($summary['cost_usd'] !== null ? number_format($summary['cost_usd'], 4) . ' $' : '?') . "\n";
    foreach ($queries as $q) echo "  q: {$q}\n";
    $shown = $citations ?: $inlineCited;
    foreach ($shown as $c) echo "  - " . parse_url((string) $c['url'], PHP_URL_HOST) . "  " . ($c['title'] ?? '') . "\n";
    echo "Testo (primi 400 car.): " . mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400) . "…\n\n";
}

file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Salvato: {$outFile}\n";
