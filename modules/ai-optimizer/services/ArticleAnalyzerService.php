<?php

namespace Modules\AiOptimizer\Services;

use Core\Database;
use Core\Credits;
use Core\Settings;
use Services\ScraperService;
use Core\Logger;

require_once __DIR__ . '/../../../services/AiService.php';

/**
 * ArticleAnalyzerService
 *
 * Analizza un articolo confrontandolo con i top competitor SERP
 * per identificare gap e opportunità di miglioramento.
 *
 * Workflow:
 * 1. Scrape articolo target
 * 2. Fetch top competitor da SERP
 * 3. Scrape pagine competitor
 * 4. AI analizza e confronta
 * 5. Restituisce gap analysis dettagliata
 */
class ArticleAnalyzerService
{
    private \Services\AiService $aiService;
    private ScraperService $scraper;

    private const MAX_CONTENT_LENGTH = 6000;
    private const MAX_COMPETITORS = 4;
    private const CACHE_DAYS = 7;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ai-optimizer');
        $this->scraper = new ScraperService();
    }

    /**
     * Verifica se il servizio è configurato
     */
    public function isConfigured(): bool
    {
        return $this->aiService->isConfigured() && $this->hasSerpProvider();
    }

    /**
     * Verifica se c'è almeno un provider SERP configurato
     */
    private function hasSerpProvider(): bool
    {
        return !empty(Settings::get('serper_api_key')) || !empty(Settings::get('serp_api_key'));
    }

    /**
     * Analizza un articolo vs competitor
     *
     * @param int $userId ID utente
     * @param string $targetUrl URL articolo da analizzare
     * @param string $keyword Keyword target
     * @param array $options location_code, language, max_competitors
     * @return array Risultato analisi
     */
    public function analyze(
        int $userId,
        string $targetUrl,
        string $keyword,
        array $options = []
    ): array {
        // Verifica crediti
        $creditCost = Credits::getCost('gap_analysis', 'ai-optimizer');
        if (!Credits::hasEnough($userId, $creditCost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti. Richiesti: ' . $creditCost,
                'credits_required' => $creditCost,
            ];
        }

        $locationCode = $options['location_code'] ?? 'IT';
        $language = $options['language'] ?? 'it';
        $maxCompetitors = min($options['max_competitors'] ?? self::MAX_COMPETITORS, 5);

        try {
            // 1. Scrape articolo target
            $targetPage = $this->scrapePage($targetUrl);

            if (isset($targetPage['error'])) {
                return [
                    'error' => true,
                    'message' => 'Impossibile analizzare la pagina: ' . ($targetPage['message'] ?? 'errore scraping'),
                ];
            }

            // 2. Ottieni competitor dalla SERP
            $serpResults = $this->getSerpResults($keyword, $locationCode, $language);

            if (isset($serpResults['error'])) {
                return [
                    'error' => true,
                    'message' => 'Errore SERP: ' . ($serpResults['message'] ?? 'impossibile ottenere risultati'),
                ];
            }

            // 3. Scrape pagine competitor (top N)
            $competitorPages = [];
            $targetDomain = $this->extractDomain($targetUrl);

            foreach ($serpResults['results'] as $result) {
                // Salta se è lo stesso dominio del target
                $resultDomain = $this->extractDomain($result['link'] ?? '');
                if ($resultDomain === $targetDomain) {
                    continue;
                }

                // Scrape competitor
                try {
                    $pageData = $this->scrapePage($result['link']);
                    if (!isset($pageData['error'])) {
                        $pageData['position'] = $result['position'];
                        $pageData['domain'] = $resultDomain;
                        $competitorPages[] = $pageData;
                    }
                } catch (\Exception $e) {
                    Logger::channel('ai')->warning("Competitor scraping failed", ['error' => $e->getMessage()]);
                }

                // Stop quando raggiungiamo il max
                if (count($competitorPages) >= $maxCompetitors) {
                    break;
                }
            }

            if (empty($competitorPages)) {
                return [
                    'error' => true,
                    'message' => 'Nessun competitor trovato o analizzabile per questa keyword',
                ];
            }

            // 4. Costruisci prompt e chiama AI
            $prompt = $this->buildAnalysisPrompt($keyword, $targetPage, $competitorPages);

            $response = $this->aiService->analyze($userId, $prompt, '', 'ai-optimizer');

            // IMPORTANTE: Riconnetti DB dopo chiamata AI
            Database::reconnect();

            if (isset($response['error'])) {
                return [
                    'error' => true,
                    'message' => $response['message'] ?? 'Errore AI',
                ];
            }

            // 5. Parse risposta
            $analysisResult = $this->parseResponse($response['result']);

            // Consuma crediti
            Credits::consume($userId, $creditCost, 'gap_analysis', 'ai-optimizer', [
                'keyword' => $keyword,
                'url' => $targetUrl,
                'competitors' => count($competitorPages),
            ]);

            return [
                'success' => true,
                'data' => $analysisResult,
                'target_page' => [
                    'url' => $targetUrl,
                    'title' => $targetPage['title'] ?? '',
                    'h1' => $targetPage['h1'] ?? '',
                    'word_count' => $targetPage['word_count'] ?? 0,
                    'description' => $targetPage['description'] ?? '',
                    'headings' => $targetPage['headings'] ?? [],
                    'content' => $targetPage['content'] ?? '',
                ],
                'competitors' => array_map(function ($c) {
                    return [
                        'position' => $c['position'],
                        'domain' => $c['domain'],
                        'url' => $c['url'],
                        'title' => $c['title'],
                        'word_count' => $c['word_count'],
                    ];
                }, $competitorPages),
                'seo_score' => $analysisResult['summary']['score'] ?? 50,
                'credits_used' => $creditCost,
            ];

        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Errore analisi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Scrape una pagina (con caching)
     */
    private function scrapePage(string $url): array
    {
        $urlHash = hash('sha256', $url);

        // Controlla cache
        $cached = Database::fetch(
            "SELECT * FROM aio_page_cache
             WHERE url_hash = ?
               AND scraped_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND scrape_status = 'success'",
            [$urlHash, self::CACHE_DAYS]
        );

        if ($cached) {
            return [
                'url' => $url,
                'title' => $cached['title'],
                'description' => $cached['meta_description'],
                'h1' => $cached['h1'],
                'headings' => json_decode($cached['headings_json'] ?? '[]', true),
                'content' => $cached['content_text'],
                'word_count' => (int)$cached['word_count'],
                'from_cache' => true,
            ];
        }

        // Scrape fresh
        try {
            $scraped = $this->scraper->scrape($url);

            $content = $scraped['content'] ?? '';
            if (strlen($content) > self::MAX_CONTENT_LENGTH) {
                $content = substr($content, 0, self::MAX_CONTENT_LENGTH) . '...';
            }

            $h1 = '';
            if (!empty($scraped['headings']['h1'])) {
                $h1 = is_array($scraped['headings']['h1'])
                    ? implode(' | ', $scraped['headings']['h1'])
                    : $scraped['headings']['h1'];
            }

            // Salva in cache
            $this->savePageCache($url, $urlHash, [
                'title' => $scraped['title'] ?? '',
                'meta_description' => $scraped['description'] ?? '',
                'h1' => $h1,
                'headings_json' => json_encode($scraped['headings'] ?? []),
                'content_text' => $content,
                'word_count' => $scraped['word_count'] ?? 0,
                'scrape_status' => 'success',
            ]);

            return [
                'url' => $url,
                'title' => $scraped['title'] ?? '',
                'description' => $scraped['description'] ?? '',
                'h1' => $h1,
                'headings' => $scraped['headings'] ?? [],
                'content' => $content,
                'word_count' => $scraped['word_count'] ?? 0,
                'from_cache' => false,
            ];

        } catch (\Exception $e) {
            $this->savePageCache($url, $urlHash, [
                'scrape_status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Salva in cache
     */
    private function savePageCache(string $url, string $urlHash, array $data): void
    {
        Database::query(
            "REPLACE INTO aio_page_cache SET
                url = ?,
                url_hash = ?,
                title = ?,
                meta_description = ?,
                h1 = ?,
                headings_json = ?,
                content_text = ?,
                word_count = ?,
                scrape_status = ?,
                error_message = ?,
                scraped_at = NOW()",
            [
                $url,
                $urlHash,
                $data['title'] ?? null,
                $data['meta_description'] ?? null,
                $data['h1'] ?? null,
                $data['headings_json'] ?? null,
                $data['content_text'] ?? null,
                $data['word_count'] ?? 0,
                $data['scrape_status'] ?? 'success',
                $data['error_message'] ?? null,
            ]
        );
    }

    /**
     * Ottieni risultati SERP (con caching)
     */
    private function getSerpResults(string $keyword, string $locationCode, string $language): array
    {
        // Controlla cache
        $cached = Database::fetch(
            "SELECT * FROM aio_serp_cache
             WHERE keyword = ? AND location_code = ? AND language = ?
               AND fetched_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$keyword, $locationCode, $language, self::CACHE_DAYS]
        );

        if ($cached) {
            return [
                'success' => true,
                'results' => json_decode($cached['results_json'], true),
                'from_cache' => true,
            ];
        }

        // Fetch fresh
        $serperKey = Settings::get('serper_api_key');
        $serpApiKey = Settings::get('serp_api_key');

        if (!empty($serperKey)) {
            $results = $this->fetchSerper($keyword, $locationCode, $language, $serperKey);
        } elseif (!empty($serpApiKey)) {
            $results = $this->fetchSerpApi($keyword, $locationCode, $language, $serpApiKey);
        } else {
            return ['error' => true, 'message' => 'Nessun provider SERP configurato'];
        }

        if (isset($results['error'])) {
            return $results;
        }

        // Salva in cache
        Database::query(
            "REPLACE INTO aio_serp_cache SET
                keyword = ?,
                location_code = ?,
                language = ?,
                results_json = ?,
                results_count = ?,
                fetched_at = NOW()",
            [
                $keyword,
                $locationCode,
                $language,
                json_encode($results['results']),
                count($results['results']),
            ]
        );

        return $results;
    }

    /**
     * Fetch da Serper.dev
     */
    private function fetchSerper(string $keyword, string $locationCode, string $language, string $apiKey): array
    {
        $glMap = ['IT' => 'it', 'US' => 'us', 'UK' => 'uk', 'DE' => 'de', 'FR' => 'fr', 'ES' => 'es'];
        $gl = $glMap[$locationCode] ?? 'it';

        $payload = [
            'q' => $keyword,
            'gl' => $gl,
            'hl' => $language,
            'num' => 10,
        ];

        $ch = curl_init('https://google.serper.dev/search');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'Errore Serper: ' . $error];
        }

        if ($httpCode !== 200) {
            return ['error' => true, 'message' => 'Errore Serper HTTP ' . $httpCode];
        }

        $data = json_decode($response, true);

        if (isset($data['message'])) {
            return ['error' => true, 'message' => 'Errore Serper: ' . $data['message']];
        }

        $results = [];
        foreach ($data['organic'] ?? [] as $index => $item) {
            $results[] = [
                'position' => $index + 1,
                'link' => $item['link'] ?? '',
                'title' => $item['title'] ?? '',
                'snippet' => $item['snippet'] ?? '',
            ];
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Fetch da SerpAPI
     */
    private function fetchSerpApi(string $keyword, string $locationCode, string $language, string $apiKey): array
    {
        $params = [
            'engine' => 'google',
            'q' => $keyword,
            'location' => $this->getLocationName($locationCode),
            'hl' => $language,
            'gl' => strtolower($locationCode),
            'num' => 10,
            'api_key' => $apiKey,
        ];

        $url = 'https://serpapi.com/search.json?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'Errore SerpAPI: ' . $error];
        }

        if ($httpCode !== 200) {
            return ['error' => true, 'message' => 'Errore SerpAPI HTTP ' . $httpCode];
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            return ['error' => true, 'message' => 'Errore SerpAPI: ' . $data['error']];
        }

        $results = [];
        foreach ($data['organic_results'] ?? [] as $index => $item) {
            $results[] = [
                'position' => $item['position'] ?? ($index + 1),
                'link' => $item['link'] ?? '',
                'title' => $item['title'] ?? '',
                'snippet' => $item['snippet'] ?? '',
            ];
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Mappa location code a nome
     */
    private function getLocationName(string $code): string
    {
        $map = [
            'IT' => 'Italy',
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
        ];
        return $map[$code] ?? 'Italy';
    }

    /**
     * Estrai dominio da URL
     */
    private function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return preg_replace('/^www\./', '', strtolower($host));
    }

    /**
     * Costruisci prompt per analisi
     */
    private function buildAnalysisPrompt(string $keyword, array $targetPage, array $competitors): string
    {
        $targetHeadings = $this->formatHeadings($targetPage['headings'] ?? []);
        $targetContent = substr($targetPage['content'] ?? '', 0, 3000);

        $competitorSections = [];
        foreach ($competitors as $i => $comp) {
            $num = $i + 1;
            $compHeadings = $this->formatHeadings($comp['headings'] ?? []);
            $compContent = substr($comp['content'] ?? '', 0, 2000);

            $competitorSections[] = <<<COMP
--- COMPETITOR #{$num} (Posizione {$comp['position']}) ---
Dominio: {$comp['domain']}
URL: {$comp['url']}
Title: {$comp['title']}
H1: {$comp['h1']}
Parole: {$comp['word_count']}
Struttura Heading:
{$compHeadings}
Estratto contenuto:
{$compContent}
COMP;
        }

        $competitorsText = implode("\n\n", $competitorSections);

        return <<<PROMPT
Sei un esperto SEO senior italiano. Analizza questo articolo confrontandolo con i top competitor in SERP
e fornisci un'analisi GAP dettagliata con suggerimenti CONCRETI e SPECIFICI per migliorare il posizionamento.

=== KEYWORD TARGET ===
"{$keyword}"

=== ARTICOLO DA OTTIMIZZARE ===
URL: {$targetPage['url']}
Title: {$targetPage['title']}
Meta Description: {$targetPage['description']}
H1: {$targetPage['h1']}
Parole totali: {$targetPage['word_count']}

Struttura Heading:
{$targetHeadings}

Contenuto principale:
{$targetContent}

=== TOP COMPETITOR SERP ===
{$competitorsText}

=== ISTRUZIONI ===

Analizza in dettaglio:
1. GAP ANALYSIS: Cosa hanno i competitor che manca all'articolo target?
2. ON-PAGE SEO: Title, meta, H1, struttura heading
3. CONTENUTO: Lunghezza, completezza, profondità, sezioni mancanti
4. USER INTENT: L'articolo risponde all'intento di ricerca?

Fornisci suggerimenti SPECIFICI, ACTIONABLE, in ITALIANO.

Rispondi SOLO con JSON valido (senza markdown, senza backtick):
{
  "summary": {
    "score": numero_da_1_a_100,
    "main_issues": ["problema 1", "problema 2", "problema 3"],
    "estimated_position_gain": numero_posizioni_guadagnabili,
    "verdict": "breve giudizio complessivo in 1-2 frasi"
  },
  "gap_analysis": {
    "missing_topics": ["topic che i competitor trattano ma l'articolo no"],
    "missing_sections": ["sezioni mancanti es. FAQ, tabelle, esempi pratici"],
    "content_depth": "analisi profondità contenuto vs competitor",
    "keyword_usage": "analisi uso della keyword nel testo"
  },
  "on_page_seo": {
    "title": {
      "current": "title attuale",
      "issues": ["problemi identificati"],
      "suggestion": "nuovo title ottimizzato con keyword"
    },
    "meta_description": {
      "current": "meta attuale",
      "issues": ["problemi identificati"],
      "suggestion": "nuova meta description ottimizzata"
    },
    "h1": {
      "current": "h1 attuale",
      "issues": ["problemi identificati"],
      "suggestion": "nuovo h1 ottimizzato"
    },
    "heading_structure": {
      "issues": ["problemi struttura heading"],
      "suggestions": ["H2 e H3 da aggiungere con testo suggerito"]
    }
  },
  "content": {
    "word_count_analysis": "analisi lunghezza vs competitor (media competitor, gap)",
    "recommended_word_count": numero_parole_consigliato,
    "sections_to_add": [
      {
        "title": "Titolo sezione H2 da aggiungere",
        "description": "Cosa dovrebbe contenere questa sezione",
        "priority": "alta|media|bassa",
        "estimated_words": numero_parole_suggerito
      }
    ],
    "paragraphs_to_expand": ["paragrafi esistenti da espandere con più dettagli"]
  },
  "quick_wins": [
    {
      "action": "Azione specifica da implementare",
      "impact": "alto|medio|basso",
      "effort": "facile|medio|difficile",
      "details": "Dettagli su come implementare"
    }
  ],
  "competitor_insights": [
    "Insight chiave dai competitor che puoi sfruttare"
  ]
}
PROMPT;
    }

    /**
     * Formatta headings per prompt
     */
    private function formatHeadings(array $headings): string
    {
        $lines = [];

        for ($i = 1; $i <= 3; $i++) {
            $key = "h{$i}";
            if (!empty($headings[$key])) {
                $hList = is_array($headings[$key]) ? $headings[$key] : [$headings[$key]];
                foreach ($hList as $h) {
                    $lines[] = str_repeat('  ', $i - 1) . "H{$i}: {$h}";
                }
            }
        }

        return empty($lines) ? "(nessun heading trovato)" : implode("\n", $lines);
    }

    /**
     * Parse risposta AI
     */
    private function parseResponse(string $text): array
    {
        $jsonStr = preg_replace('/```json\s*/i', '', $text);
        $jsonStr = preg_replace('/```\s*/', '', $jsonStr);

        $firstBrace = strpos($jsonStr, '{');
        $lastBrace = strrpos($jsonStr, '}');

        if ($firstBrace === false || $lastBrace === false) {
            throw new \Exception('Nessun JSON trovato nella risposta AI');
        }

        $jsonStr = substr($jsonStr, $firstBrace, $lastBrace - $firstBrace + 1);
        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON non valido: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Ottieni costo crediti
     */
    public function getCreditCost(): float
    {
        return Credits::getCost('gap_analysis', 'ai-optimizer');
    }
}
