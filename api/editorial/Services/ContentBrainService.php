<?php

declare(strict_types=1);

namespace Editorial\Services;

use Core\Database;
use Core\Settings;
use Services\AiService;
use Services\ScraperService;
use Services\ApiLoggerService;
use Throwable;

/**
 * ContentBrainService — M2.1
 *
 * Scansiona articoli del sito utente, estrae brand voice via AiService,
 * persiste in `aied_content_brain`. Riuso totale di servizi Ainstein
 * (AiService, ScraperService, ApiLoggerService) come da CLAUDE.md GR #1.
 *
 * Posizione: api/editorial/Services/ContentBrainService.php
 * Spec: plugins/ainstein-editorial/docs/milestones/M2-content-brain.md §2.1
 */
class ContentBrainService
{
    private const MODULE_SLUG = 'editorial-content-brain';
    private const MAX_SAMPLE_SIZE = 20;
    private const ARTICLE_CONTENT_TRUNCATE = 500;
    private const VALID_TONES = ['professional', 'friendly', 'fun', 'authoritative', 'casual'];
    private const VALID_GLOSSARY_KEYS = ['brand_names', 'products', 'people', 'avoid_terms', 'preferred_terms'];
    private const VALID_GUIDELINES_KEYS = ['do', 'dont', 'emphasize'];

    private AiService $ai;
    private ScraperService $scraper;
    private int $serviceUserId;

    public function __construct(?AiService $ai = null, ?ScraperService $scraper = null)
    {
        $this->ai = $ai ?? new AiService(self::MODULE_SLUG);
        $this->scraper = $scraper ?? new ScraperService();
        $this->serviceUserId = (int) Settings::get('editorial_service_user_id', 1);
    }

    // =====================================================================
    // PUBLIC API
    // =====================================================================

    /**
     * Esegue scan completo per un sito. Streaming via callback (eventi SSE).
     *
     * @param int            $siteId       FK aied_sites.id
     * @param string[]       $articleUrls  URL da scansionare (subset; max 20). Se vuoto, discovery automatica.
     * @param callable|null  $emit         function(string $event, array $data): void per eventi SSE
     * @return array{success:bool,brain?:array,errors?:array,partial_scan?:bool}
     */
    public function scan(int $siteId, array $articleUrls = [], ?callable $emit = null): array
    {
        $emit ??= static function (string $e, array $d): void {};

        // Risolvi site_url se discovery richiesta
        $siteRow = $this->fetchSite($siteId);
        if ($siteRow === null) {
            $emit('error', ['message' => 'Sito non trovato', 'retry_possible' => false]);
            return ['success' => false, 'errors' => ['site_not_found']];
        }
        $siteUrl = $this->normalizeSiteUrl($siteRow['domain']);

        if (empty($articleUrls)) {
            $articleUrls = $this->discoverArticleUrls($siteUrl, 30);
        }

        $articleUrls = array_slice($articleUrls, 0, self::MAX_SAMPLE_SIZE);
        $total = count($articleUrls);

        $emit('started', [
            'total_urls' => $total,
            'sample_size' => $total,
        ]);

        if ($total === 0) {
            $emit('error', [
                'message' => 'Nessun articolo trovato sul sito. Inserisci manualmente il tono.',
                'retry_possible' => false,
            ]);
            return ['success' => false, 'errors' => ['no_urls']];
        }

        // 1. Scrape articoli
        $articles = [];
        foreach ($articleUrls as $index => $url) {
            $scrapeStart = microtime(true);
            try {
                $scraped = $this->scraper->scrape($url);
                ApiLoggerService::log(
                    'editorial_scraper',
                    $url,
                    ['url' => $url],
                    ['title' => $scraped['title'] ?? null, 'word_count' => $scraped['word_count'] ?? 0],
                    200,
                    $scrapeStart,
                    [
                        'user_id' => $this->serviceUserId,
                        'module' => self::MODULE_SLUG,
                        'method' => 'GET',
                        'context' => "scan site_id={$siteId}",
                    ]
                );

                if (empty($scraped['content']) || ($scraped['word_count'] ?? 0) < 50) {
                    $emit('article_error', [
                        'index' => $index + 1,
                        'total' => $total,
                        'url' => $url,
                        'reason' => 'Contenuto troppo breve',
                    ]);
                    continue;
                }

                $articles[] = [
                    'url' => $url,
                    'title' => $scraped['title'] ?? '(senza titolo)',
                    'content' => $scraped['content'],
                    'word_count' => $scraped['word_count'] ?? 0,
                ];

                $emit('article_scraped', [
                    'index' => $index + 1,
                    'total' => $total,
                    'url' => $url,
                    'title' => $scraped['title'] ?? '(senza titolo)',
                    'word_count' => $scraped['word_count'] ?? 0,
                ]);
            } catch (Throwable $e) {
                ApiLoggerService::log(
                    'editorial_scraper',
                    $url,
                    ['url' => $url],
                    null,
                    0,
                    $scrapeStart,
                    [
                        'user_id' => $this->serviceUserId,
                        'module' => self::MODULE_SLUG,
                        'method' => 'GET',
                        'error' => $e->getMessage(),
                        'context' => "scan site_id={$siteId}",
                    ]
                );

                $emit('article_error', [
                    'index' => $index + 1,
                    'total' => $total,
                    'url' => $url,
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        $scrapedCount = count($articles);
        if ($scrapedCount < 1) {
            $emit('error', [
                'message' => 'Non sono riuscito a leggere nessun articolo del tuo sito.',
                'retry_possible' => true,
            ]);
            return ['success' => false, 'errors' => ['no_articles_scraped']];
        }

        $partialScan = $scrapedCount < 5;
        $emit('aggregating', ['scraped_count' => $scrapedCount]);

        // 2. Costruisce prompt bulk
        [$prompt, $payload] = $this->buildPrompt($articles, $siteRow);
        $emit('analyzing', ['status' => 'AI sta leggendo i tuoi articoli...']);

        // 3. Chiamata AI
        $aiStart = microtime(true);
        $aiResult = $this->ai->analyze($this->serviceUserId, $prompt, $payload, self::MODULE_SLUG);
        Database::reconnect();

        ApiLoggerService::log(
            'claude_anthropic',
            'messages',
            ['prompt_length' => strlen($prompt), 'payload_length' => strlen($payload)],
            isset($aiResult['result']) ? ['content_length' => strlen($aiResult['result'])] : null,
            !empty($aiResult['error']) ? 500 : 200,
            $aiStart,
            [
                'user_id' => $this->serviceUserId,
                'module' => self::MODULE_SLUG,
                'context' => "scan site_id={$siteId} articles=" . $scrapedCount,
                'error' => $aiResult['error'] ?? null,
            ]
        );

        if (!empty($aiResult['error']) || empty($aiResult['result'])) {
            $emit('error', [
                'message' => $aiResult['message'] ?? 'AI non ha risposto correttamente.',
                'retry_possible' => true,
            ]);
            return ['success' => false, 'errors' => ['ai_failed']];
        }

        // 4. Parsing JSON con retry 1 volta
        $parsed = $this->parseAiJson($aiResult['result']);
        if ($parsed === null) {
            // Retry con prompt strict
            $aiStart2 = microtime(true);
            $strictPrompt = $prompt . "\n\nRispondi SOLO con JSON valido, senza testo extra, senza wrapper markdown.";
            $aiResult2 = $this->ai->analyze($this->serviceUserId, $strictPrompt, $payload, self::MODULE_SLUG);
            Database::reconnect();

            ApiLoggerService::log(
                'claude_anthropic',
                'messages',
                ['retry' => true],
                isset($aiResult2['result']) ? ['content_length' => strlen($aiResult2['result'])] : null,
                !empty($aiResult2['error']) ? 500 : 200,
                $aiStart2,
                [
                    'user_id' => $this->serviceUserId,
                    'module' => self::MODULE_SLUG,
                    'context' => "scan retry site_id={$siteId}",
                ]
            );

            if (empty($aiResult2['error']) && !empty($aiResult2['result'])) {
                $parsed = $this->parseAiJson($aiResult2['result']);
            }
        }

        if ($parsed === null) {
            $emit('error', [
                'message' => 'Non sono riuscito a leggere la voce del tuo sito. Prova a inserire manualmente il tono.',
                'retry_possible' => true,
            ]);
            return ['success' => false, 'errors' => ['ai_parse_failed']];
        }

        // 5. Persistenza
        $brain = $this->persistBrain($siteId, $parsed, $scrapedCount, $partialScan);
        $emit('completed', ['brain' => $brain]);

        return [
            'success' => true,
            'brain' => $brain,
            'partial_scan' => $partialScan,
        ];
    }

    /**
     * Lettura completa Content Brain di un sito.
     *
     * @return array|null Null se record non esiste.
     */
    public function get(int $siteId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT cb.*, s.domain
             FROM aied_content_brain cb
             INNER JOIN aied_sites s ON s.id = cb.site_id
             WHERE cb.site_id = :site_id
             LIMIT 1'
        );
        $stmt->execute(['site_id' => $siteId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return $this->hydrateRow($row);
    }

    /**
     * Update manuale dei campi editabili dall'utente.
     * Partial update via JSON merge.
     *
     * Campi editabili: site_topic, target_audience, tone, glossary, editorial_guidelines.
     * Campi read-only (modificati solo da scan): brand_voice_summary, brand_voice_examples,
     * scanned_articles_count, last_scan_at, language.
     *
     * @return array{success:bool, content_brain?:array, errors?:array}
     */
    public function update(int $siteId, array $patch): array
    {
        $errors = $this->validatePatch($patch);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $existing = $this->get($siteId);
        if ($existing === null) {
            return ['success' => false, 'errors' => ['content_brain_not_found']];
        }

        $updates = [];

        if (array_key_exists('site_topic', $patch)) {
            $updates['site_topic'] = $this->trimOrNull((string) $patch['site_topic']);
        }
        if (array_key_exists('target_audience', $patch)) {
            $updates['target_audience'] = $this->trimOrNull((string) $patch['target_audience']);
        }
        if (array_key_exists('tone', $patch)) {
            $updates['tone'] = (string) $patch['tone'];
        }
        if (array_key_exists('glossary', $patch)) {
            $merged = $this->mergeGlossary($existing['glossary'] ?? [], (array) $patch['glossary']);
            $updates['glossary'] = json_encode($merged, JSON_UNESCAPED_UNICODE);
        }
        if (array_key_exists('editorial_guidelines', $patch)) {
            $merged = $this->mergeGuidelines($existing['editorial_guidelines'] ?? [], (array) $patch['editorial_guidelines']);
            $updates['editorial_guidelines'] = json_encode($merged, JSON_UNESCAPED_UNICODE);
        }

        if (empty($updates)) {
            return ['success' => true, 'content_brain' => $existing];
        }

        $pdo = Database::getConnection();
        $set = implode(', ', array_map(static fn(string $col): string => "{$col} = :{$col}", array_keys($updates)));
        $stmt = $pdo->prepare("UPDATE aied_content_brain SET {$set} WHERE site_id = :site_id");
        $params = array_merge($updates, ['site_id' => $siteId]);
        $stmt->execute($params);

        return ['success' => true, 'content_brain' => $this->get($siteId)];
    }

    /**
     * Discovery URL articoli dalla sitemap del sito o via WP REST API.
     * Strategia in ordine (primo che ritorna >= 5 URL vince).
     *
     * @return string[] URL articoli (max $maxCount, shuffled).
     */
    public function discoverArticleUrls(string $siteUrl, int $maxCount = 30): array
    {
        $siteUrl = rtrim($siteUrl, '/');

        // 1. WP REST API
        $urls = $this->discoverFromWpRestApi($siteUrl, $maxCount);
        if (count($urls) >= 5) {
            shuffle($urls);
            return array_slice($urls, 0, $maxCount);
        }

        // 2. Sitemap XML
        $urls = $this->discoverFromSitemap($siteUrl, $maxCount);
        if (count($urls) >= 5) {
            shuffle($urls);
            return array_slice($urls, 0, $maxCount);
        }

        // 3. Spider light homepage
        $urls = $this->discoverFromSpider($siteUrl, $maxCount);
        shuffle($urls);
        return array_slice($urls, 0, $maxCount);
    }

    /**
     * Helper pubblico per testing: gestisce ```json wrapper + parsing.
     * Ritorna null su parse fail (no throw — il chiamante gestisce retry).
     */
    public function parseAiJson(string $content): ?array
    {
        $clean = trim($content);
        if ($clean === '') {
            return null;
        }
        // Rimuovi wrapper ```json ... ``` o ``` ... ```
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```\s*$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        // Se inizia con testo non-JSON, prova a estrarre il primo blocco { ... }
        if ($clean !== '' && $clean[0] !== '{' && $clean[0] !== '[') {
            if (preg_match('/(\{[\s\S]*\})/', $clean, $m)) {
                $clean = $m[1];
            }
        }

        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return is_array($decoded) ? $decoded : null;
    }

    // =====================================================================
    // INTERNAL — Persistence
    // =====================================================================

    private function fetchSite(int $siteId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, user_id, domain FROM aied_sites WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $siteId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    private function persistBrain(int $siteId, array $aiPayload, int $scrapedCount, bool $partialScan): array
    {
        $tone = in_array($aiPayload['tone'] ?? '', self::VALID_TONES, true)
            ? $aiPayload['tone']
            : 'friendly';
        $language = (string) ($aiPayload['language'] ?? 'it');

        $summary = (string) ($aiPayload['summary'] ?? '');
        if ($partialScan) {
            $summary = trim($summary . ' (Scan parziale: meno di 5 articoli analizzati.)');
        }

        $examples = $aiPayload['examples'] ?? [];
        $glossary = $this->buildGlossaryFromAi($aiPayload);
        $guidelines = $this->buildGuidelinesFromAi($aiPayload);

        $pdo = Database::getConnection();

        // Preserva site_topic/target_audience se già presenti (impostati dall'utente in onboarding)
        $existing = $this->get($siteId);
        $siteTopic = $existing['site_topic'] ?? null;
        $targetAudience = $existing['target_audience'] ?? null;
        $existingGlossary = $existing['glossary'] ?? [];
        $existingGuidelines = $existing['editorial_guidelines'] ?? [];

        // Merge: AI extracts new, but preserve user-edited overrides
        $finalGlossary = $this->mergeGlossary($existingGlossary, $glossary);
        $finalGuidelines = $this->mergeGuidelines($existingGuidelines, $guidelines);

        if ($existing === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO aied_content_brain
                 (site_id, brand_voice_summary, brand_voice_examples, tone, target_audience, site_topic,
                  glossary, editorial_guidelines, language, last_scan_at, scanned_articles_count)
                 VALUES
                 (:site_id, :summary, :examples, :tone, :audience, :topic,
                  :glossary, :guidelines, :language, NOW(), :count)'
            );
            $stmt->execute([
                'site_id' => $siteId,
                'summary' => $summary,
                'examples' => json_encode($examples, JSON_UNESCAPED_UNICODE),
                'tone' => $tone,
                'audience' => $targetAudience,
                'topic' => $siteTopic,
                'glossary' => json_encode($finalGlossary, JSON_UNESCAPED_UNICODE),
                'guidelines' => json_encode($finalGuidelines, JSON_UNESCAPED_UNICODE),
                'language' => $language,
                'count' => $scrapedCount,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE aied_content_brain SET
                    brand_voice_summary = :summary,
                    brand_voice_examples = :examples,
                    tone = :tone,
                    glossary = :glossary,
                    editorial_guidelines = :guidelines,
                    language = :language,
                    last_scan_at = NOW(),
                    scanned_articles_count = :count
                 WHERE site_id = :site_id'
            );
            $stmt->execute([
                'summary' => $summary,
                'examples' => json_encode($examples, JSON_UNESCAPED_UNICODE),
                'tone' => $tone,
                'glossary' => json_encode($finalGlossary, JSON_UNESCAPED_UNICODE),
                'guidelines' => json_encode($finalGuidelines, JSON_UNESCAPED_UNICODE),
                'language' => $language,
                'count' => $scrapedCount,
                'site_id' => $siteId,
            ]);
        }

        return $this->get($siteId);
    }

    private function hydrateRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'site_id' => (int) $row['site_id'],
            'domain' => $row['domain'] ?? null,
            'brand_voice_summary' => $row['brand_voice_summary'] ?? '',
            'brand_voice_examples' => $this->decodeJson($row['brand_voice_examples'] ?? null, []),
            'tone' => $row['tone'] ?? 'friendly',
            'target_audience' => $row['target_audience'] ?? null,
            'site_topic' => $row['site_topic'] ?? null,
            'glossary' => $this->decodeJson($row['glossary'] ?? null, $this->emptyGlossary()),
            'editorial_guidelines' => $this->decodeJson($row['editorial_guidelines'] ?? null, $this->emptyGuidelines()),
            'language' => $row['language'] ?? 'it',
            'last_scan_at' => $row['last_scan_at'] ?? null,
            'scanned_articles_count' => (int) ($row['scanned_articles_count'] ?? 0),
        ];
    }

    // =====================================================================
    // INTERNAL — Prompt building
    // =====================================================================

    /**
     * @return array{0:string,1:string} [prompt, payload concatenato articoli]
     */
    private function buildPrompt(array $articles, array $siteRow): array
    {
        $siteTopic = '';
        // Recupera site_topic da content_brain esistente per personalizzare il prompt
        $existing = $this->get((int) $siteRow['id']);
        if ($existing !== null && !empty($existing['site_topic'])) {
            $siteTopic = (string) $existing['site_topic'];
        }

        $instruction = "Sei un analista di brand voice esperto. Ti fornirò una serie di articoli del blog del cliente";
        if ($siteTopic !== '') {
            $instruction .= " (descritto come: \"{$siteTopic}\")";
        }
        $instruction .= ". Devi estrarre la \"voce\" del brand per permettere a un assistente AI di scrivere nuovi articoli nello stesso stile.\n\n";

        $instruction .= "Analizza ed estrai (output JSON):\n\n";
        $instruction .= <<<JSON
{
  "tone": "professional|friendly|fun|authoritative|casual",
  "tone_confidence": 0.0,
  "sentence_style": "short|mixed|long",
  "voice_traits": ["..."],
  "vocabulary": ["..."],
  "brand_names_detected": [],
  "structure_notes": "...",
  "examples": ["...", "...", "..."],
  "language": "it",
  "summary": "Una sintesi 2-3 frasi della voce del brand"
}

Rispondi SOLO con JSON valido. No prefacing, no markdown.
JSON;

        $payloadLines = [];
        foreach ($articles as $i => $a) {
            $idx = $i + 1;
            $content = mb_substr((string) $a['content'], 0, self::ARTICLE_CONTENT_TRUNCATE);
            $payloadLines[] = "[{$idx}] {$a['title']}\n{$content}";
        }

        return [$instruction, implode("\n\n---\n\n", $payloadLines)];
    }

    // =====================================================================
    // INTERNAL — Discovery
    // =====================================================================

    private function discoverFromWpRestApi(string $siteUrl, int $maxCount): array
    {
        $endpoint = $siteUrl . '/wp-json/wp/v2/posts?per_page=' . min($maxCount, 30) . '&status=publish&_fields=link';
        try {
            $result = $this->scraper->fetchJson($endpoint, ['timeout' => 15]);
            if (empty($result) || !is_array($result)) {
                return [];
            }
            $body = $result['body'] ?? $result;
            if (!is_array($body)) {
                return [];
            }
            $urls = [];
            foreach ($body as $post) {
                if (is_array($post) && !empty($post['link'])) {
                    $urls[] = (string) $post['link'];
                }
            }
            return $urls;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function discoverFromSitemap(string $siteUrl, int $maxCount): array
    {
        foreach (['/sitemap_index.xml', '/sitemap.xml', '/wp-sitemap.xml'] as $path) {
            try {
                $result = $this->scraper->fetchRaw($siteUrl . $path, ['timeout' => 15]);
                if (($result['http_code'] ?? 0) !== 200 || empty($result['body'])) {
                    continue;
                }
                $urls = $this->parseSitemapXml((string) $result['body'], $siteUrl, $maxCount);
                if (count($urls) >= 5) {
                    return $urls;
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return [];
    }

    private function parseSitemapXml(string $xml, string $siteUrl, int $maxCount): array
    {
        $urls = [];
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = simplexml_load_string($xml);
            if ($doc === false) {
                return [];
            }
            // Sitemap index → ricorsione su singole sitemap
            if (isset($doc->sitemap)) {
                foreach ($doc->sitemap as $sm) {
                    $loc = (string) ($sm->loc ?? '');
                    if ($loc === '') continue;
                    try {
                        $sub = $this->scraper->fetchRaw($loc, ['timeout' => 15]);
                        if (($sub['http_code'] ?? 0) === 200 && !empty($sub['body'])) {
                            $urls = array_merge($urls, $this->parseSitemapXml((string) $sub['body'], $siteUrl, $maxCount));
                        }
                    } catch (Throwable $e) {
                        continue;
                    }
                    if (count($urls) >= $maxCount) break;
                }
            }
            // Sitemap diretta urlset
            if (isset($doc->url)) {
                foreach ($doc->url as $u) {
                    $loc = (string) ($u->loc ?? '');
                    if ($loc !== '' && $this->looksLikeArticleUrl($loc, $siteUrl)) {
                        $urls[] = $loc;
                    }
                    if (count($urls) >= $maxCount) break;
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return array_values(array_unique($urls));
    }

    private function discoverFromSpider(string $siteUrl, int $maxCount): array
    {
        try {
            $result = $this->scraper->scrape($siteUrl);
            $links = $result['internal_links'] ?? [];
            $urls = [];
            foreach ($links as $link) {
                $href = is_array($link) ? ($link['url'] ?? $link['href'] ?? '') : (string) $link;
                if ($href !== '' && $this->looksLikeArticleUrl($href, $siteUrl)) {
                    $urls[] = $href;
                }
                if (count($urls) >= $maxCount) break;
            }
            return array_values(array_unique($urls));
        } catch (Throwable $e) {
            return [];
        }
    }

    private function looksLikeArticleUrl(string $url, string $siteUrl): bool
    {
        $host = parse_url($siteUrl, PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);
        if ($host !== null && $urlHost !== null && $urlHost !== $host) {
            return false;
        }
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        if ($path === '' || $path === '/') {
            return false;
        }
        // Esclude path tecnici WP
        $excludePatterns = ['/wp-admin', '/wp-content', '/wp-login', '/wp-json', '/feed', '/category/', '/tag/', '/author/', '/page/'];
        foreach ($excludePatterns as $exc) {
            if (str_contains($path, $exc)) {
                return false;
            }
        }
        // Esclude estensioni file
        if (preg_match('/\.(jpg|jpeg|png|gif|pdf|zip|css|js|xml)$/i', $path)) {
            return false;
        }
        return true;
    }

    private function normalizeSiteUrl(string $domain): string
    {
        $domain = trim($domain);
        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . $domain;
        }
        return rtrim($domain, '/');
    }

    // =====================================================================
    // Validation + merge (validatePatch pubblico per pre-flight check del Controller M2.3)
    // =====================================================================

    public function validatePatch(array $patch): array
    {
        $errors = [];
        if (array_key_exists('tone', $patch) && !in_array($patch['tone'], self::VALID_TONES, true)) {
            $errors[] = [
                'field' => 'tone',
                'message' => 'Tono non valido. Valori ammessi: ' . implode(', ', self::VALID_TONES),
            ];
        }
        if (array_key_exists('glossary', $patch)) {
            if (!is_array($patch['glossary'])) {
                $errors[] = ['field' => 'glossary', 'message' => 'glossary deve essere un oggetto'];
            } else {
                foreach ($patch['glossary'] as $key => $val) {
                    if (!in_array($key, self::VALID_GLOSSARY_KEYS, true)) {
                        $errors[] = ['field' => "glossary.{$key}", 'message' => 'Chiave glossary non valida'];
                    }
                }
            }
        }
        if (array_key_exists('editorial_guidelines', $patch)) {
            if (!is_array($patch['editorial_guidelines'])) {
                $errors[] = ['field' => 'editorial_guidelines', 'message' => 'editorial_guidelines deve essere un oggetto'];
            } else {
                foreach ($patch['editorial_guidelines'] as $key => $val) {
                    if (!in_array($key, self::VALID_GUIDELINES_KEYS, true)) {
                        $errors[] = ['field' => "editorial_guidelines.{$key}", 'message' => 'Chiave guidelines non valida'];
                    }
                }
            }
        }
        return $errors;
    }

    private function mergeGlossary(array $existing, array $patch): array
    {
        $result = $this->emptyGlossary();
        foreach (self::VALID_GLOSSARY_KEYS as $key) {
            if ($key === 'preferred_terms') {
                $existingMap = (array) ($existing[$key] ?? []);
                $patchMap = (array) ($patch[$key] ?? []);
                $result[$key] = $patchMap !== [] ? $patchMap : $existingMap;
            } else {
                $existingList = (array) ($existing[$key] ?? []);
                $patchList = (array) ($patch[$key] ?? []);
                $result[$key] = $patchList !== [] ? array_values(array_unique($patchList)) : $existingList;
            }
        }
        return $result;
    }

    private function mergeGuidelines(array $existing, array $patch): array
    {
        $result = $this->emptyGuidelines();
        foreach (self::VALID_GUIDELINES_KEYS as $key) {
            $existingList = (array) ($existing[$key] ?? []);
            $patchList = (array) ($patch[$key] ?? []);
            $result[$key] = $patchList !== [] ? array_values(array_unique($patchList)) : $existingList;
        }
        return $result;
    }

    private function buildGlossaryFromAi(array $ai): array
    {
        $g = $this->emptyGlossary();
        if (!empty($ai['brand_names_detected']) && is_array($ai['brand_names_detected'])) {
            $g['brand_names'] = array_values(array_unique(array_map('strval', $ai['brand_names_detected'])));
        }
        if (!empty($ai['vocabulary']) && is_array($ai['vocabulary'])) {
            // Vocabulary AI → preferred_terms come hint (keyword → keyword, mappa identity)
            $g['preferred_terms'] = [];
            foreach (array_slice($ai['vocabulary'], 0, 15) as $word) {
                $g['preferred_terms'][(string) $word] = (string) $word;
            }
        }
        return $g;
    }

    private function buildGuidelinesFromAi(array $ai): array
    {
        $guidelines = $this->emptyGuidelines();
        if (!empty($ai['voice_traits']) && is_array($ai['voice_traits'])) {
            $guidelines['do'] = array_values(array_map('strval', $ai['voice_traits']));
        }
        return $guidelines;
    }

    private function emptyGlossary(): array
    {
        return [
            'brand_names' => [],
            'products' => [],
            'people' => [],
            'avoid_terms' => [],
            'preferred_terms' => [],
        ];
    }

    private function emptyGuidelines(): array
    {
        return ['do' => [], 'dont' => [], 'emphasize' => []];
    }

    // =====================================================================
    // INTERNAL — Misc helpers
    // =====================================================================

    private function decodeJson(?string $raw, $default)
    {
        if ($raw === null || $raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    private function trimOrNull(string $value): ?string
    {
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}
