<?php

/**
 * Test Flusso Completo: AI Keyword Research
 * Brief → API Keywords → Pre-filtro → AI Clustering → Output
 *
 * Uso: php public/test-keyword-research-flow.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Rome');

define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', BASE_PATH);

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

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
use Services\AiService;

$totalStart = microtime(true);

// ============================================================
// INPUT SIMULATO
// ============================================================
$brief = [
    'business' => 'Agenzia SEO a Milano che offre consulenza e servizi di ottimizzazione per PMI',
    'target' => 'B2B',
    'geography' => 'Italia',
    'objective' => 'SEO',
    'seeds' => ['consulente seo', 'agenzia seo milano'],
    'exclusions' => ['gratis', 'free', 'corso'],
];

$testUserId = 1;

echo str_repeat('=', 80) . "\n";
echo "AI KEYWORD RESEARCH - TEST FLUSSO COMPLETO\n";
echo str_repeat('=', 80) . "\n";
echo "Business: {$brief['business']}\n";
echo "Target: {$brief['target']} | Geo: {$brief['geography']}\n";
echo "Seeds: " . implode(', ', $brief['seeds']) . "\n";
echo "Exclusions: " . implode(', ', $brief['exclusions']) . "\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================
// STEP 1 - CHIAMATA API KEYWORD INSIGHT
// ============================================================
echo "STEP 1: Raccolta keyword da API\n";
echo str_repeat('-', 60) . "\n";

$apiHost = 'google-keyword-insight1.p.rapidapi.com';
$baseUrl = 'https://google-keyword-insight1.p.rapidapi.com';
$apiKey = Settings::get('rapidapi_keyword_key');

if (empty($apiKey)) {
    echo "ERRORE: API key RapidAPI non trovata.\n";
    exit(1);
}

$allKeywords = [];

foreach ($brief['seeds'] as $seed) {
    echo "  Fetching: \"{$seed}\" ... ";
    $stepStart = microtime(true);

    $url = $baseUrl . '/keysuggest?' . http_build_query([
        'keyword' => $seed,
        'location' => 'IT',
        'lang' => 'it',
        'return_intent' => 'true',
    ]);

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

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $elapsed = round((microtime(true) - $stepStart) * 1000);

    if ($error) {
        echo "CURL ERROR: {$error}\n";
        continue;
    }

    if ($httpCode !== 200) {
        echo "HTTP {$httpCode}\n";
        continue;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        echo "JSON ERROR\n";
        continue;
    }

    $count = count($data);
    echo "{$count} keywords ({$elapsed}ms)\n";

    foreach ($data as $item) {
        $text = $item['text'] ?? '';
        if ($text && !isset($allKeywords[$text])) {
            $allKeywords[$text] = [
                'text' => $text,
                'volume' => (int) ($item['volume'] ?? 0),
                'competition_level' => $item['competition_level'] ?? '',
                'competition_index' => (int) ($item['competition_index'] ?? 0),
                'low_bid' => (float) ($item['low_bid'] ?? 0),
                'high_bid' => (float) ($item['high_bid'] ?? 0),
                'trend' => (float) ($item['trend'] ?? 0),
                'intent' => $item['intent'] ?? '',
            ];
        }
    }
}

$totalRaw = count($allKeywords);
echo "\n  Totale keyword raccolte (deduplicate): {$totalRaw}\n\n";

if ($totalRaw === 0) {
    echo "ERRORE: Nessuna keyword raccolta. Interruzione.\n";
    exit(1);
}

// ============================================================
// STEP 2 - PRE-FILTRO PHP
// ============================================================
echo "STEP 2: Pre-filtro PHP\n";
echo str_repeat('-', 60) . "\n";

$filtered = [];
$excludedByRule = ['volume_low' => 0, 'exclusion_match' => 0];

foreach ($allKeywords as $kw) {
    // Filtro volume
    if ($kw['volume'] < 10) {
        $excludedByRule['volume_low']++;
        continue;
    }

    // Filtro exclusions
    $excluded = false;
    foreach ($brief['exclusions'] as $ex) {
        if (stripos($kw['text'], $ex) !== false) {
            $excluded = true;
            $excludedByRule['exclusion_match']++;
            break;
        }
    }
    if ($excluded) continue;

    $filtered[] = $kw;
}

echo "  Pre-filtro: {$totalRaw} keyword -> " . count($filtered) . " keyword\n";
echo "  Escluse per volume < 10: {$excludedByRule['volume_low']}\n";
echo "  Escluse per exclusion match: {$excludedByRule['exclusion_match']}\n\n";

if (empty($filtered)) {
    echo "ERRORE: Nessuna keyword dopo il filtro. Interruzione.\n";
    exit(1);
}

// Ordina per volume decrescente
usort($filtered, fn($a, $b) => $b['volume'] - $a['volume']);

// ============================================================
// STEP 3 - AI CLUSTERING
// ============================================================
echo "STEP 3: AI Clustering\n";
echo str_repeat('-', 60) . "\n";

$ai = new AiService('keyword-research');

if (!$ai->isConfigured()) {
    echo "ERRORE: AiService non configurato.\n";
    exit(1);
}

echo "  Provider: {$ai->getProvider()} | Model: {$ai->getModel()}\n";

// Prepara lista keyword per il prompt
$kwListLines = [];
foreach ($filtered as $kw) {
    $kwListLines[] = "- {$kw['text']} (vol: {$kw['volume']}, comp: {$kw['competition_level']}, intent: {$kw['intent']})";
}
$kwListText = implode("\n", $kwListLines);

$systemPrompt = "Sei un esperto SEO italiano specializzato in keyword research e strategia dei contenuti. Rispondi SOLO con JSON valido, senza markdown, senza commenti, senza testo prima o dopo il JSON.";

$userPrompt = <<<PROMPT
Analizza queste keyword per il seguente business:
Business: {$brief['business']}
Target: {$brief['target']}
Geografia: {$brief['geography']}
Obiettivo: {$brief['objective']}

KEYWORD DA ANALIZZARE ({count} keyword):
{$kwListText}

TASK:
1. Rimuovi keyword non pertinenti al business (troppo generiche, non in target, off-topic)
2. Raggruppa le keyword pertinenti in cluster semantici (max 8 cluster)
3. Per ogni cluster indica la main keyword (quella con volume piu' alto)
4. Classifica l'intent dominante di ogni cluster

RISPONDI SOLO IN JSON CON QUESTA STRUTTURA ESATTA:
{
  "clusters": [
    {
      "name": "Nome cluster descrittivo",
      "main_keyword": "keyword principale del cluster",
      "main_volume": 1000,
      "keywords": ["kw1", "kw2", "kw3"],
      "total_volume": 2500,
      "intent": "informational|transactional|commercial|navigational",
      "note": "Breve nota strategica per questo cluster"
    }
  ],
  "excluded": ["keyword1 esclusa", "keyword2 esclusa"],
  "excluded_reason": "Motivo generale esclusione",
  "strategy_note": "Nota strategica complessiva per il business"
}
PROMPT;

$userPrompt = str_replace('{count}', (string) count($filtered), $userPrompt);

echo "  Keyword inviate all'AI: " . count($filtered) . "\n";
echo "  Chiamata AI in corso ... ";

$aiStart = microtime(true);

Database::reconnect();
$aiResult = $ai->analyzeWithSystem($testUserId, $systemPrompt, $userPrompt, 'keyword-research');

$aiElapsed = round((microtime(true) - $aiStart) * 1000);

if (isset($aiResult['error']) && $aiResult['error']) {
    echo "ERRORE\n";
    echo "  Messaggio: {$aiResult['message']}\n";
    exit(1);
}

$creditsUsed = $aiResult['credits_used'] ?? 0;
echo "OK ({$aiElapsed}ms, {$creditsUsed} crediti)\n\n";

// ============================================================
// STEP 4 - PARSING E OUTPUT
// ============================================================
echo "STEP 4: Output strutturato\n";
echo str_repeat('-', 60) . "\n\n";

$aiContent = $aiResult['result'] ?? '';

// Pulizia: rimuovi eventuali ```json ... ``` wrapper
$aiContent = preg_replace('/^```(?:json)?\s*/i', '', $aiContent);
$aiContent = preg_replace('/\s*```\s*$/', '', $aiContent);
$aiContent = trim($aiContent);

$parsed = json_decode($aiContent, true);

if ($parsed === null) {
    echo "ERRORE: Impossibile parsare JSON dalla risposta AI.\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "\nRisposta raw AI (primi 2000 chars):\n";
    echo str_repeat('-', 40) . "\n";
    echo substr($aiContent, 0, 2000) . "\n";
    exit(1);
}

// --- Cluster ---
$clusters = $parsed['clusters'] ?? [];
$totalClusters = count($clusters);
$totalKwInClusters = 0;
$totalVolume = 0;

echo str_repeat('=', 80) . "\n";
echo "RISULTATO: {$totalClusters} CLUSTER IDENTIFICATI\n";
echo str_repeat('=', 80) . "\n\n";

foreach ($clusters as $i => $cluster) {
    $num = $i + 1;
    $name = $cluster['name'] ?? 'N/A';
    $mainKw = $cluster['main_keyword'] ?? 'N/A';
    $mainVol = $cluster['main_volume'] ?? 0;
    $keywords = $cluster['keywords'] ?? [];
    $clusterTotalVol = $cluster['total_volume'] ?? 0;
    $intent = $cluster['intent'] ?? 'N/A';
    $note = $cluster['note'] ?? '';
    $kwCount = count($keywords);

    $totalKwInClusters += $kwCount;
    $totalVolume += $clusterTotalVol;

    // Intent badge
    $intentBadge = strtoupper($intent);

    echo "  CLUSTER {$num}: {$name}\n";
    echo "  " . str_repeat('-', 50) . "\n";
    echo "  Main keyword:  {$mainKw} (vol: {$mainVol})\n";
    echo "  Intent:        [{$intentBadge}]\n";
    echo "  Keywords ({$kwCount}): " . implode(', ', array_slice($keywords, 0, 8));
    if ($kwCount > 8) echo " ... +" . ($kwCount - 8) . " altre";
    echo "\n";
    echo "  Volume totale: {$clusterTotalVol}\n";
    if ($note) {
        echo "  Nota:          {$note}\n";
    }
    echo "\n";
}

// --- Keyword escluse dall'AI ---
$excluded = $parsed['excluded'] ?? [];
if (!empty($excluded)) {
    echo str_repeat('-', 60) . "\n";
    echo "KEYWORD ESCLUSE DALL'AI (" . count($excluded) . "):\n";
    echo "  " . implode(', ', array_slice($excluded, 0, 15));
    if (count($excluded) > 15) echo " ... +" . (count($excluded) - 15) . " altre";
    echo "\n";
    $reason = $parsed['excluded_reason'] ?? '';
    if ($reason) echo "  Motivo: {$reason}\n";
    echo "\n";
}

// --- Nota strategica ---
$strategyNote = $parsed['strategy_note'] ?? '';
if ($strategyNote) {
    echo str_repeat('-', 60) . "\n";
    echo "NOTA STRATEGICA:\n";
    echo "  {$strategyNote}\n\n";
}

// ============================================================
// RIEPILOGO FINALE
// ============================================================
$totalElapsed = round((microtime(true) - $totalStart), 1);

echo str_repeat('=', 80) . "\n";
echo "RIEPILOGO\n";
echo str_repeat('=', 80) . "\n";
echo "  Seeds analizzati:       " . count($brief['seeds']) . "\n";
echo "  Keyword raccolte (API): {$totalRaw}\n";
echo "  Dopo pre-filtro PHP:    " . count($filtered) . "\n";
echo "  Cluster AI:             {$totalClusters}\n";
echo "  Keyword nei cluster:    {$totalKwInClusters}\n";
echo "  Keyword escluse AI:     " . count($excluded) . "\n";
echo "  Volume totale cluster:  {$totalVolume}\n";
echo "  Crediti AI usati:       {$creditsUsed}\n";
echo "  Tempo totale:           {$totalElapsed}s\n";
echo str_repeat('=', 80) . "\n";
