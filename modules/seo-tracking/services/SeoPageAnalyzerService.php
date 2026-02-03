<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Core\Credits;
use Services\ScraperService;

require_once __DIR__ . '/../../../services/AiService.php';

/**
 * SeoPageAnalyzerService
 *
 * Analizza una pagina confrontandola con i competitor SERP
 * per suggerire miglioramenti SEO specifici.
 *
 * Workflow:
 * 1. Scrape pagina utente
 * 2. Ottieni/fetch competitor da SERP
 * 3. Scrape pagine competitor (top 3)
 * 4. AI analizza e confronta
 * 5. Restituisce suggerimenti actionable
 */
class SeoPageAnalyzerService
{
    private \Services\AiService $aiService;
    private ScraperService $scraper;
    private RankCheckerService $rankChecker;

    private const MAX_CONTENT_LENGTH = 6000; // Caratteri per pagina
    private const MAX_COMPETITORS = 3;
    private const CACHE_DAYS = 7; // Giorni di validità cache scraping

    public function __construct()
    {
        $this->aiService = new \Services\AiService('seo-tracking');
        $this->scraper = new ScraperService();
        $this->rankChecker = new RankCheckerService();
    }

    /**
     * Verifica se il servizio è configurato
     */
    public function isConfigured(): bool
    {
        return $this->aiService->isConfigured() && $this->rankChecker->isConfigured();
    }

    /**
     * Analizza una pagina per una keyword specifica
     *
     * @param int $projectId ID progetto
     * @param int $userId ID utente (per crediti)
     * @param string $keyword Keyword target
     * @param string $targetUrl URL della pagina da analizzare
     * @param int|null $currentPosition Posizione attuale (opzionale)
     * @param array $options Opzioni: location_code, device, force_fresh_serp
     * @return array Risultato analisi
     */
    public function analyze(
        int $projectId,
        int $userId,
        string $keyword,
        string $targetUrl,
        ?int $currentPosition = null,
        array $options = []
    ): array {
        // Verifica crediti (2 crediti: 1 per scraping + 1 per AI)
        $creditCost = Credits::getCost('page_analysis', 'seo-tracking');
        if (!Credits::hasEnough($userId, $creditCost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti. Richiesti: ' . $creditCost,
                'credits_required' => $creditCost,
            ];
        }

        // Ottieni info progetto
        $project = Database::fetch(
            "SELECT name, domain FROM st_projects WHERE id = ?",
            [$projectId]
        );

        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato'];
        }

        $locationCode = $options['location_code'] ?? 'IT';
        $device = $options['device'] ?? 'desktop';
        $forceFreshSerp = $options['force_fresh_serp'] ?? false;

        try {
            // 1. Scrape pagina target
            $targetPageData = $this->scrapePage($projectId, $targetUrl);

            if (isset($targetPageData['error'])) {
                return [
                    'error' => true,
                    'message' => 'Impossibile analizzare la pagina: ' . ($targetPageData['message'] ?? 'errore scraping'),
                ];
            }

            // 2. Ottieni competitor
            $competitors = $this->getCompetitors(
                $projectId,
                $keyword,
                $project['domain'],
                $locationCode,
                $device,
                $forceFreshSerp
            );

            // 3. Scrape pagine competitor
            $competitorPages = [];
            foreach (array_slice($competitors, 0, self::MAX_COMPETITORS) as $competitor) {
                try {
                    $pageData = $this->scrapePage($projectId, $competitor['url']);
                    if (!isset($pageData['error'])) {
                        $pageData['position'] = $competitor['position'];
                        $pageData['domain'] = $competitor['domain'];
                        $competitorPages[] = $pageData;
                    }
                } catch (\Exception $e) {
                    // Skip competitor se scraping fallisce
                    error_log("Competitor scraping failed: " . $e->getMessage());
                }
            }

            // 4. Costruisci prompt AI
            $prompt = $this->buildPrompt(
                $project,
                $keyword,
                $currentPosition,
                $targetPageData,
                $competitorPages
            );

            // 5. Chiama AI
            $response = $this->aiService->analyze(
                $userId,
                $prompt,
                '',
                'seo-tracking'
            );

            // IMPORTANTE: Riconnetti DB dopo chiamata AI
            Database::reconnect();

            if (isset($response['error'])) {
                return [
                    'error' => true,
                    'message' => $response['message'] ?? 'Errore AI',
                ];
            }

            // 6. Parse risposta
            $analysisResult = $this->parseResponse($response['result']);

            // 7. Salva analisi
            $analysisId = $this->saveAnalysis(
                $projectId,
                $keyword,
                $targetUrl,
                $currentPosition,
                $analysisResult,
                $competitorPages,
                $response['credits_used'] ?? $creditCost
            );

            return [
                'success' => true,
                'analysis_id' => $analysisId,
                'data' => $analysisResult,
                'target_page' => [
                    'url' => $targetUrl,
                    'title' => $targetPageData['title'] ?? '',
                    'word_count' => $targetPageData['word_count'] ?? 0,
                ],
                'competitors_analyzed' => count($competitorPages),
                'credits_used' => $response['credits_used'] ?? $creditCost,
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
    private function scrapePage(int $projectId, string $url): array
    {
        // Genera hash URL per lookup
        $urlHash = hash('sha256', $url);

        // Controlla cache
        $cached = Database::fetch(
            "SELECT * FROM st_page_analysis
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
                'headings' => json_decode($cached['headings_json'], true) ?? [],
                'content' => $cached['content_text'],
                'word_count' => $cached['word_count'],
                'from_cache' => true,
            ];
        }

        // Scrape fresh
        try {
            $scraped = $this->scraper->scrape($url);

            // Limita lunghezza contenuto
            $content = $scraped['content'] ?? '';
            if (strlen($content) > self::MAX_CONTENT_LENGTH) {
                $content = substr($content, 0, self::MAX_CONTENT_LENGTH) . '...';
            }

            // Estrai H1
            $h1 = '';
            if (!empty($scraped['headings']['h1'])) {
                $h1 = is_array($scraped['headings']['h1'])
                    ? implode(' | ', $scraped['headings']['h1'])
                    : $scraped['headings']['h1'];
            }

            // Salva in cache
            $this->savePageCache($projectId, $url, $urlHash, [
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
            // Salva errore in cache
            $this->savePageCache($projectId, $url, $urlHash, [
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
     * Salva dati pagina in cache
     */
    private function savePageCache(int $projectId, string $url, string $urlHash, array $data): void
    {
        // Usa REPLACE per aggiornare se esiste
        $sql = "REPLACE INTO st_page_analysis SET
                project_id = ?,
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
                scraped_at = NOW()";

        Database::query($sql, [
            $projectId,
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
        ]);
    }

    /**
     * Ottieni competitor da cache o SERP fresh
     */
    private function getCompetitors(
        int $projectId,
        string $keyword,
        string $targetDomain,
        string $locationCode,
        string $device,
        bool $forceFresh
    ): array {
        // Prima controlla cache recente
        if (!$forceFresh) {
            $cached = $this->rankChecker->getRecentCompetitors(
                $projectId,
                $keyword,
                self::MAX_COMPETITORS + 2, // +2 per escludere target se presente
                self::CACHE_DAYS
            );

            if (count($cached) >= self::MAX_COMPETITORS) {
                return $cached;
            }
        }

        // Fetch fresh da SERP
        try {
            $serpResult = $this->rankChecker->checkPositionWithAllResults(
                $keyword,
                $targetDomain,
                [
                    'location_code' => $locationCode,
                    'device' => $device,
                    'max_results' => 10,
                ]
            );

            // Salva risultati SERP
            if (!empty($serpResult['all_results'])) {
                $this->rankChecker->saveSerpResults(
                    $projectId,
                    $keyword,
                    $serpResult['all_results'],
                    null,
                    $locationCode,
                    $device
                );

                // Filtra competitor (escludi target domain)
                return array_filter($serpResult['all_results'], function ($r) {
                    return !$r['is_target'];
                });
            }
        } catch (\Exception $e) {
            error_log("SERP fetch failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Costruisce il prompt per l'AI
     */
    private function buildPrompt(
        array $project,
        string $keyword,
        ?int $currentPosition,
        array $targetPage,
        array $competitorPages
    ): string {
        $positionText = $currentPosition ? "Posizione attuale: {$currentPosition}" : "Posizione: non in top 100";

        // Prepara heading structure per la pagina target
        $targetHeadings = $this->formatHeadingsForPrompt($targetPage['headings'] ?? []);

        // Prepara dati competitor
        $competitorSections = [];
        foreach ($competitorPages as $i => $comp) {
            $compNum = $i + 1;
            $compHeadings = $this->formatHeadingsForPrompt($comp['headings'] ?? []);
            $compContent = substr($comp['content'] ?? '', 0, 2000);

            $competitorSections[] = <<<COMP
--- COMPETITOR #{$compNum} (Posizione {$comp['position']}) ---
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

        $targetContent = substr($targetPage['content'] ?? '', 0, 3000);

        return <<<PROMPT
Sei un esperto SEO senior. Analizza questa pagina confrontandola con i competitor in SERP
e fornisci suggerimenti CONCRETI e SPECIFICI per migliorare il posizionamento.

=== CONTESTO ===
Progetto: {$project['name']}
Dominio: {$project['domain']}
Keyword target: "{$keyword}"
{$positionText}

=== PAGINA DA ANALIZZARE ===
URL: {$targetPage['url']}
Title: {$targetPage['title']}
Meta Description: {$targetPage['description']}
H1: {$targetPage['h1']}
Parole totali: {$targetPage['word_count']}

Struttura Heading:
{$targetHeadings}

Contenuto principale:
{$targetContent}

=== COMPETITOR TOP 3 ===
{$competitorsText}

=== ISTRUZIONI ANALISI ===

Analizza in dettaglio:
1. GAP ANALYSIS: Cosa hanno i competitor che manca alla pagina target?
2. ON-PAGE SEO: Title, meta, H1, keyword density, struttura heading
3. CONTENUTO: Lunghezza, completezza, sezioni mancanti
4. USER INTENT: La pagina risponde all'intento di ricerca della keyword?

Fornisci suggerimenti SPECIFICI e ACTIONABLE, non generici.

Rispondi SOLO con JSON valido (senza markdown, senza backtick):
{
  "summary": {
    "score": numero_da_1_a_100,
    "main_issues": ["issue 1", "issue 2", "issue 3"],
    "estimated_position_gain": numero_posizioni_guadagnabili
  },
  "gap_analysis": {
    "missing_topics": ["topic che i competitor trattano ma tu no"],
    "missing_sections": ["sezioni mancanti es. FAQ, tabelle comparazione"],
    "content_depth": "analisi profondità contenuto vs competitor"
  },
  "on_page_seo": {
    "title": {
      "current": "title attuale",
      "issues": ["problemi"],
      "suggestion": "nuovo title suggerito"
    },
    "meta_description": {
      "current": "meta attuale",
      "issues": ["problemi"],
      "suggestion": "nuova meta suggerita"
    },
    "h1": {
      "current": "h1 attuale",
      "issues": ["problemi"],
      "suggestion": "nuovo h1 suggerito"
    },
    "heading_structure": {
      "issues": ["problemi struttura"],
      "suggestions": ["H2 da aggiungere"]
    }
  },
  "content": {
    "word_count_analysis": "analisi lunghezza vs competitor",
    "recommended_word_count": numero_parole_consigliato,
    "sections_to_add": [
      {
        "title": "Titolo sezione",
        "description": "Cosa dovrebbe contenere",
        "priority": "alta|media|bassa"
      }
    ]
  },
  "quick_wins": [
    {
      "action": "Azione specifica da fare",
      "impact": "alto|medio|basso",
      "effort": "facile|medio|difficile",
      "details": "Dettagli implementazione"
    }
  ],
  "competitor_insights": [
    "Insight 1 dai competitor",
    "Insight 2 dai competitor"
  ]
}
PROMPT;
    }

    /**
     * Formatta heading per il prompt
     */
    private function formatHeadingsForPrompt(array $headings): string
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
        // Rimuovi markdown se presente
        $jsonStr = preg_replace('/```json\s*/i', '', $text);
        $jsonStr = preg_replace('/```\s*/', '', $jsonStr);

        // Estrai JSON
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
     * Salva analisi nel database
     */
    private function saveAnalysis(
        int $projectId,
        string $keyword,
        string $targetUrl,
        ?int $position,
        array $analysisData,
        array $competitorPages,
        float $creditsUsed
    ): int {
        $competitorsJson = json_encode(array_map(function ($c) {
            return [
                'position' => $c['position'],
                'domain' => $c['domain'],
                'url' => $c['url'],
                'title' => $c['title'],
                'word_count' => $c['word_count'],
            ];
        }, $competitorPages), JSON_UNESCAPED_UNICODE);

        Database::insert('st_page_ai_analysis', [
            'project_id' => $projectId,
            'keyword' => $keyword,
            'target_url' => $targetUrl,
            'target_position' => $position,
            'analysis_json' => json_encode($analysisData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'summary' => $analysisData['summary']['main_issues'][0] ?? null,
            'competitors_analyzed' => count($competitorPages),
            'competitors_json' => $competitorsJson,
            'credits_used' => $creditsUsed,
        ]);

        return Database::lastInsertId();
    }

    /**
     * Ottieni analisi precedenti per un progetto
     */
    public function getRecentAnalyses(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT id, keyword, target_url, target_position, summary,
                    competitors_analyzed, credits_used, created_at
             FROM st_page_ai_analysis
             WHERE project_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Ottieni analisi per ID
     */
    public function getAnalysis(int $analysisId): ?array
    {
        $analysis = Database::fetch(
            "SELECT * FROM st_page_ai_analysis WHERE id = ?",
            [$analysisId]
        );

        if ($analysis) {
            $analysis['analysis_data'] = json_decode($analysis['analysis_json'], true);
            $analysis['competitors'] = json_decode($analysis['competitors_json'], true);
        }

        return $analysis;
    }

    /**
     * Ottieni costo in crediti
     */
    public function getCreditCost(): float
    {
        return Credits::getCost('page_analysis', 'seo-tracking');
    }
}
