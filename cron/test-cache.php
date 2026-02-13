<?php
/**
 * Test cache keyword research - da eliminare dopo l'uso
 * Verifica: 1a chiamata = API call (cache miss), 2a chiamata = cache hit
 */
require_once __DIR__ . '/bootstrap.php';

use Core\Database;
use Modules\KeywordResearch\Services\KeywordInsightService;

$service = new KeywordInsightService();

if (!$service->isConfigured()) {
    echo "ERRORE: API key RapidAPI non configurata\n";
    exit(1);
}

$testKeyword = 'consulente seo roma';
$location = 'IT';
$lang = 'it';

echo "=== Test Cache Keyword Research ===\n\n";

// Pulisci cache per questa keyword (per test pulito)
try {
    Database::execute(
        "DELETE FROM kr_keyword_cache WHERE seed_keyword = ? AND location = ? AND language = ?",
        [$testKeyword, $location, $lang]
    );
    echo "Cache pulita per '{$testKeyword}'\n\n";
} catch (\Exception $e) {
    echo "Nota: " . $e->getMessage() . "\n\n";
}

// --- PRIMA CHIAMATA (cache miss → API call) ---
echo "--- 1a chiamata (atteso: API call) ---\n";
$start1 = microtime(true);
$result1 = $service->keySuggest($testKeyword, $location, $lang);
$time1 = round((microtime(true) - $start1) * 1000);

echo "Tempo: {$time1}ms\n";
echo "Successo: " . ($result1['success'] ? 'SI' : 'NO') . "\n";
echo "Da cache: " . (isset($result1['from_cache']) && $result1['from_cache'] ? 'SI' : 'NO') . "\n";

if ($result1['success']) {
    echo "Keyword trovate: " . count($result1['data']) . "\n";
    foreach (array_slice($result1['data'], 0, 3) as $kw) {
        $text = $kw['text'] ?? '?';
        $vol = $kw['volume'] ?? 0;
        echo "  - {$text} (vol: {$vol})\n";
    }
} else {
    echo "Errore: {$result1['error']}\n";
}

// Verifica che sia in cache
try {
    $cached = Database::fetch(
        "SELECT id, results_count, cached_at FROM kr_keyword_cache WHERE seed_keyword = ? AND location = ? AND language = ?",
        [$testKeyword, $location, $lang]
    );
    echo "In cache: " . ($cached ? "SI (id={$cached['id']}, count={$cached['results_count']}, at={$cached['cached_at']})" : "NO") . "\n";
} catch (\Exception $e) {
    echo "Errore check cache: " . $e->getMessage() . "\n";
}

echo "\n";

// --- SECONDA CHIAMATA (cache hit) ---
echo "--- 2a chiamata (atteso: cache hit) ---\n";
$start2 = microtime(true);
$result2 = $service->keySuggest($testKeyword, $location, $lang);
$time2 = round((microtime(true) - $start2) * 1000);

echo "Tempo: {$time2}ms\n";
echo "Successo: " . ($result2['success'] ? 'SI' : 'NO') . "\n";
echo "Da cache: " . (isset($result2['from_cache']) && $result2['from_cache'] ? 'SI' : 'NO') . "\n";

if ($result2['success']) {
    echo "Keyword trovate: " . count($result2['data']) . "\n";
}

echo "\n=== Risultato ===\n";
echo "1a chiamata: {$time1}ms (API)\n";
echo "2a chiamata: {$time2}ms (cache)\n";

if ($time2 < $time1 && isset($result2['from_cache']) && $result2['from_cache']) {
    echo "CACHE FUNZIONANTE! Risparmio: " . ($time1 - $time2) . "ms\n";
} else {
    echo "ATTENZIONE: verificare cache\n";
}

// Verifica api_logs - solo ultima chiamata
try {
    $logs = Database::fetchAll(
        "SELECT id, endpoint, http_code, response_time_ms, created_at FROM api_logs WHERE provider = 'rapidapi_keyword_insight' ORDER BY id DESC LIMIT 2"
    );
    echo "\nUltime 2 entry api_logs (rapidapi_keyword_insight):\n";
    foreach ($logs as $l) {
        echo "  #{$l['id']} {$l['endpoint']} HTTP {$l['http_code']} {$l['response_time_ms']}ms ({$l['created_at']})\n";
    }
} catch (\Exception $e) {
    // skip
}
