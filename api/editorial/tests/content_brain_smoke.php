<?php

declare(strict_types=1);

/**
 * Smoke test isolato M2.1 — Definition of Done check.
 *
 * Verifica che (new ContentBrainService())->scan(siteId, [3 URL], $emit)
 * produca un record valido in `aied_content_brain` su DB locale.
 *
 * Mocka AiService + ScraperService per:
 *   - evitare consumo crediti AI reali
 *   - evitare dipendenza da network
 *
 * Esecuzione:
 *   php api/editorial/tests/content_brain_smoke.php
 *
 * Prerequisiti:
 *   - DB locale `seo_toolkit` raggiungibile via env
 *   - Tabelle aied_users + aied_sites + aied_content_brain create
 *     (database/migrations/2026_05_15_create_aied_schema.sql)
 */

require_once __DIR__ . '/bootstrap.php';

use Core\Database;
use Editorial\Services\ContentBrainService;
use Services\AiService;
use Services\ScraperService;

// ---------------------------------------------------------------
// Setup: assicura una riga di test in aied_users + aied_sites
// ---------------------------------------------------------------

$pdo = Database::getConnection();

echo "[smoke] connecting to DB... ";
$pdo->query('SELECT 1');
echo "ok\n";

// Cleanup test rows da run precedenti
$pdo->exec("DELETE FROM aied_content_brain WHERE site_id IN (SELECT id FROM aied_sites WHERE domain = 'smoke-test.example.com')");
$pdo->exec("DELETE FROM aied_sites WHERE domain = 'smoke-test.example.com'");
$pdo->exec("DELETE FROM aied_users WHERE email = 'smoke@editorial.test'");

// Insert test user + site
$stmt = $pdo->prepare("INSERT INTO aied_users (email, license_key, tier) VALUES (?, ?, 'starter')");
$stmt->execute(['smoke@editorial.test', 'SMOKE-' . bin2hex(random_bytes(8))]);
$userId = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO aied_sites (user_id, domain, api_token, status) VALUES (?, ?, ?, 'active')");
$stmt->execute([$userId, 'smoke-test.example.com', bin2hex(random_bytes(16))]);
$siteId = (int) $pdo->lastInsertId();

echo "[smoke] test fixtures: user_id={$userId}, site_id={$siteId}\n";

// ---------------------------------------------------------------
// Mock AiService — ritorna JSON valido
// ---------------------------------------------------------------

$aiMock = new class('editorial-content-brain') extends AiService {
    public function __construct(string $moduleSlug = 'editorial-content-brain') {
        // skip parent constructor (evita load Settings/DB)
    }

    public function analyze(int $userId, string $prompt, string $content, ?string $moduleSlug = null): array {
        $fakeJson = json_encode([
            'tone' => 'friendly',
            'tone_confidence' => 0.92,
            'sentence_style' => 'mixed',
            'voice_traits' => ['uso prima persona', 'storytelling familiare'],
            'vocabulary' => ['vino', 'tradizione', 'famiglia'],
            'brand_names_detected' => ['Vinicola Smoke'],
            'structure_notes' => 'paragrafi corti, H2 frequenti',
            'examples' => ['Esempio 1...', 'Esempio 2...', 'Esempio 3...'],
            'language' => 'it',
            'summary' => 'Tono amichevole e familiare con focus sulla tradizione enologica.',
        ], JSON_UNESCAPED_UNICODE);
        return [
            'success' => true,
            'result' => "```json\n{$fakeJson}\n```",  // wrapper markdown per testare parseAiJson
            'credits_used' => 1,
        ];
    }
};

// ---------------------------------------------------------------
// Mock ScraperService — ritorna 3 articoli fittizi
// ---------------------------------------------------------------

$scraperMock = new class extends ScraperService {
    public function __construct() {
        // skip parent constructor
    }

    public function scrape(string $url): array {
        return [
            'url' => $url,
            'title' => 'Articolo smoke ' . substr(md5($url), 0, 6),
            'content' => str_repeat('Questo è un articolo sul vino artigianale e la tradizione familiare. ', 30),
            'word_count' => 250,
            'headings' => [],
            'internal_links' => [],
        ];
    }
};

// ---------------------------------------------------------------
// Run scan
// ---------------------------------------------------------------

$service = new ContentBrainService($aiMock, $scraperMock);

$events = [];
$emit = function (string $event, array $data) use (&$events): void {
    $events[] = ['event' => $event, 'data' => $data];
    $brief = $event;
    if (!empty($data['url'])) {
        $brief .= " — {$data['url']}";
    } elseif (!empty($data['message'])) {
        $brief .= " — {$data['message']}";
    } elseif (!empty($data['status'])) {
        $brief .= " — {$data['status']}";
    }
    echo "  [event] {$brief}\n";
};

$articleUrls = [
    'https://smoke-test.example.com/articolo-1',
    'https://smoke-test.example.com/articolo-2',
    'https://smoke-test.example.com/articolo-3',
];

echo "[smoke] running scan() with 3 URLs...\n";
$result = $service->scan($siteId, $articleUrls, $emit);

if (!$result['success']) {
    echo "[smoke] FAIL: scan returned success=false: " . json_encode($result) . "\n";
    exit(1);
}

echo "[smoke] scan OK\n";

// ---------------------------------------------------------------
// Verify DB record
// ---------------------------------------------------------------

$brain = $service->get($siteId);
if ($brain === null) {
    echo "[smoke] FAIL: nessun record content_brain trovato\n";
    exit(1);
}

$checks = [
    'site_id matches' => $brain['site_id'] === $siteId,
    'tone is friendly' => $brain['tone'] === 'friendly',
    'language is it' => $brain['language'] === 'it',
    'scanned_articles_count is 3' => $brain['scanned_articles_count'] === 3,
    'last_scan_at not null' => !empty($brain['last_scan_at']),
    'brand_voice_summary not empty' => !empty($brain['brand_voice_summary']),
    'brand_voice_examples is array with 3' => is_array($brain['brand_voice_examples']) && count($brain['brand_voice_examples']) === 3,
    'glossary has brand_names' => !empty($brain['glossary']['brand_names']) && $brain['glossary']['brand_names'][0] === 'Vinicola Smoke',
    'glossary preferred_terms populated' => !empty($brain['glossary']['preferred_terms']),
    'editorial_guidelines.do populated' => !empty($brain['editorial_guidelines']['do']),
];

$failed = 0;
foreach ($checks as $name => $ok) {
    $mark = $ok ? '✓' : '✗';
    echo "  {$mark} {$name}\n";
    if (!$ok) $failed++;
}

// ---------------------------------------------------------------
// Test update() validation real DB path
// ---------------------------------------------------------------

echo "[smoke] testing update() with valid patch...\n";
$updateResult = $service->update($siteId, [
    'site_topic' => 'Smoke test topic',
    'target_audience' => 'Smoke test audience',
    'tone' => 'professional',
]);
$updateChecks = [
    'update success' => ($updateResult['success'] ?? false) === true,
    'tone updated to professional' => ($updateResult['content_brain']['tone'] ?? null) === 'professional',
    'site_topic persisted' => ($updateResult['content_brain']['site_topic'] ?? null) === 'Smoke test topic',
];
foreach ($updateChecks as $name => $ok) {
    $mark = $ok ? '✓' : '✗';
    echo "  {$mark} {$name}\n";
    if (!$ok) $failed++;
}

// ---------------------------------------------------------------
// Cleanup
// ---------------------------------------------------------------

$pdo->exec("DELETE FROM aied_content_brain WHERE site_id = {$siteId}");
$pdo->exec("DELETE FROM aied_sites WHERE id = {$siteId}");
$pdo->exec("DELETE FROM aied_users WHERE id = {$userId}");
echo "[smoke] cleanup ok\n";

if ($failed > 0) {
    echo "[smoke] FAIL: {$failed} check(s) failed\n";
    exit(1);
}
echo "[smoke] PASS (" . count($checks) + count($updateChecks) . " checks)\n";
