<?php

namespace Modules\AiContent\Services;

use Services\AiService;
use Core\Logger;

/**
 * BriefBuilderService
 *
 * Builds structured briefs for AI article generation
 * by analyzing SERP results, competitor content, and PAA questions
 */
class BriefBuilderService
{
    private AiService $aiService;

    public function __construct()
    {
        $this->aiService = new AiService('ai-content');
    }

    // Stop words to exclude from entity extraction (Italian + English)
    private array $stopWords = [
        // Italian
        'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una', 'di', 'a', 'da', 'in', 'con', 'su', 'per',
        'tra', 'fra', 'che', 'chi', 'cui', 'non', 'come', 'dove', 'quando', 'perché', 'se', 'ma', 'però',
        'anche', 'ancora', 'già', 'sempre', 'mai', 'solo', 'proprio', 'altro', 'stesso', 'tutto', 'ogni',
        'questo', 'quello', 'quale', 'quanto', 'molto', 'poco', 'troppo', 'più', 'meno', 'così', 'quindi',
        'essere', 'avere', 'fare', 'dire', 'potere', 'dovere', 'volere', 'sono', 'è', 'siamo', 'hanno',
        'della', 'dello', 'delle', 'degli', 'nel', 'nella', 'nelle', 'negli', 'sul', 'sulla', 'sulle',
        // English
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these',
        'those', 'it', 'its', 'you', 'your', 'we', 'our', 'they', 'their', 'what', 'which', 'who',
        'how', 'when', 'where', 'why', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other',
        'some', 'such', 'no', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just',
    ];

    // Intent indicators
    private array $intentIndicators = [
        'informational' => [
            'come', 'cosa', 'perché', 'quando', 'dove', 'chi', 'quale', 'quanto',
            'how', 'what', 'why', 'when', 'where', 'who', 'which',
            'guida', 'guide', 'tutorial', 'spiegazione', 'significato', 'definizione',
            'cos\'è', 'what is', 'how to', 'ways to', 'tips', 'consigli',
        ],
        'commercial' => [
            'migliore', 'migliori', 'best', 'top', 'recensione', 'review', 'reviews',
            'confronto', 'comparison', 'vs', 'versus', 'alternative', 'alternatives',
            'quale scegliere', 'which to choose', 'pro e contro', 'pros and cons',
        ],
        'transactional' => [
            'comprare', 'acquistare', 'buy', 'purchase', 'prezzo', 'price', 'costo', 'cost',
            'sconto', 'discount', 'offerta', 'offer', 'coupon', 'codice',
            'ordina', 'order', 'abbonamento', 'subscription', 'gratis', 'free trial',
        ],
        'navigational' => [
            'login', 'accedi', 'registrazione', 'sign up', 'download', 'scarica',
            'sito ufficiale', 'official site', 'contatti', 'contact',
        ],
    ];

    /**
     * Build comprehensive brief for AI generation
     *
     * @param array $keyword Keyword data with 'keyword', 'language', 'location'
     * @param array $serpResults Array of SERP results with 'title', 'url', 'snippet'
     * @param array $paaQuestions Array of PAA questions
     * @param array $scrapedSources Array of scraped source data with 'content', 'headings', 'word_count'
     * @return array Structured brief
     */
    public function build(array $keyword, array $serpResults, array $paaQuestions, array $scrapedSources, int $userId): array
    {
        $keywordText = $keyword['keyword'] ?? '';
        $language = $keyword['language'] ?? 'it';

        // Analyze competitor titles
        $serpTitles = $this->extractSerpTitles($serpResults);

        // Detect search intent
        $searchIntent = $this->detectSearchIntent($keywordText, $serpTitles, $paaQuestions, $language);

        // Analyze competitor headings
        $competitorHeadings = $this->analyzeCompetitorHeadings($scrapedSources);

        // Extract key entities from content
        $keyEntities = $this->extractKeyEntities($scrapedSources, $keywordText, $language);

        // Calculate word count statistics
        $wordCountStats = $this->calculateWordCountStats($scrapedSources);

        // Build sources summary
        $sourcesSummary = $this->buildSourcesSummary($scrapedSources);

        // Build recommended structure
        $recommendedStructure = $this->buildRecommendedStructure($competitorHeadings, $searchIntent);

        // Format PAA questions
        $formattedPaa = $this->formatPaaQuestions($paaQuestions);

        // AI Strategic Analysis (content gaps, unique angles, winning titles)
        $aiAnalysis = $this->getAiStrategicAnalysis(
            $keywordText,
            $language,
            $searchIntent,
            $serpTitles,
            $competitorHeadings,
            $formattedPaa,
            $userId
        );

        return [
            // Core info
            'keyword' => $keywordText,
            'language' => $language,
            'location' => $keyword['location'] ?? 'Italy',
            'search_intent' => $searchIntent,

            // SERP analysis
            'serp_titles' => $serpTitles,
            'serp_snippets' => $this->extractSerpSnippets($serpResults),

            // Questions
            'paa_questions' => $formattedPaa,

            // Competitor analysis
            'competitor_headings' => $competitorHeadings,
            'key_entities' => $keyEntities,

            // Word count
            'avg_word_count' => $wordCountStats['average'],
            'min_word_count' => $wordCountStats['min'],
            'max_word_count' => $wordCountStats['max'],
            'recommended_word_count' => $wordCountStats['recommended'],

            // Content structure
            'recommended_structure' => $recommendedStructure,
            'sources_summary' => $sourcesSummary,
            'sources_count' => count($scrapedSources),

            'ai_strategic_analysis' => $aiAnalysis,

            'brief_version' => '1.0',
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Extract titles from SERP results
     */
    private function extractSerpTitles(array $serpResults): array
    {
        $titles = [];

        foreach ($serpResults as $result) {
            if (!empty($result['title'])) {
                $titles[] = [
                    'title' => $result['title'],
                    'position' => $result['position'] ?? 0,
                    'domain' => $result['domain'] ?? parse_url($result['url'] ?? '', PHP_URL_HOST),
                ];
            }
        }

        return $titles;
    }

    /**
     * Extract snippets from SERP results
     */
    private function extractSerpSnippets(array $serpResults): array
    {
        $snippets = [];

        foreach ($serpResults as $result) {
            if (!empty($result['snippet'])) {
                $snippets[] = $result['snippet'];
            }
        }

        return $snippets;
    }

    /**
     * Detect search intent based on keyword and SERP analysis
     */
    private function detectSearchIntent(string $keyword, array $titles, array $paaQuestions, string $language): array
    {
        $scores = [
            'informational' => 0,
            'commercial' => 0,
            'transactional' => 0,
            'navigational' => 0,
        ];

        $keywordLower = strtolower($keyword);

        // Analyze keyword
        foreach ($this->intentIndicators as $intent => $indicators) {
            foreach ($indicators as $indicator) {
                if (stripos($keywordLower, $indicator) !== false) {
                    $scores[$intent] += 3;
                }
            }
        }

        // Analyze titles
        foreach ($titles as $titleData) {
            $titleLower = strtolower($titleData['title']);
            foreach ($this->intentIndicators as $intent => $indicators) {
                foreach ($indicators as $indicator) {
                    if (stripos($titleLower, $indicator) !== false) {
                        $scores[$intent] += 1;
                    }
                }
            }
        }

        // PAA questions usually indicate informational intent
        if (count($paaQuestions) > 2) {
            $scores['informational'] += 2;
        }

        // Determine primary intent
        arsort($scores);
        $primaryIntent = array_key_first($scores);

        // If scores are too close, default to informational
        $sortedScores = array_values($scores);
        if ($sortedScores[0] - $sortedScores[1] < 2) {
            $primaryIntent = 'informational';
        }

        return [
            'primary' => $primaryIntent,
            'scores' => $scores,
            'confidence' => $this->calculateIntentConfidence($scores),
        ];
    }

    /**
     * Calculate intent confidence
     */
    private function calculateIntentConfidence(array $scores): string
    {
        $total = array_sum($scores);
        if ($total === 0) {
            return 'low';
        }

        $maxScore = max($scores);
        $ratio = $maxScore / $total;

        if ($ratio > 0.6) {
            return 'high';
        } elseif ($ratio > 0.4) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Analyze headings from competitor content
     */
    private function analyzeCompetitorHeadings(array $scrapedSources): array
    {
        $allHeadings = [
            'h1' => [],
            'h2' => [],
            'h3' => [],
        ];

        $headingCounts = [];
        $structurePatterns = [];

        foreach ($scrapedSources as $source) {
            if (empty($source['headings'])) {
                continue;
            }

            $headings = $source['headings'];
            $sourceUrl = $source['url'] ?? 'unknown';

            // Collect headings
            foreach (['h1', 'h2', 'h3'] as $level) {
                if (!empty($headings[$level])) {
                    foreach ($headings[$level] as $heading) {
                        $allHeadings[$level][] = $this->sanitizeUtf8($heading);
                    }
                }
            }

            // Count structure
            $h2Count = count($headings['h2'] ?? []);
            $h3Count = count($headings['h3'] ?? []);
            $headingCounts[] = [
                'h2' => $h2Count,
                'h3' => $h3Count,
                'total' => $h2Count + $h3Count,
            ];
        }

        // Analyze frequency of similar headings
        $frequentH2 = $this->findFrequentHeadings($allHeadings['h2']);
        $frequentH3 = $this->findFrequentHeadings($allHeadings['h3']);

        // Calculate average structure
        $avgH2 = $this->calculateAverage(array_column($headingCounts, 'h2'));
        $avgH3 = $this->calculateAverage(array_column($headingCounts, 'h3'));

        return [
            'frequent_h2' => $frequentH2,
            'frequent_h3' => $frequentH3,
            'all_h2' => array_unique($allHeadings['h2']),
            'all_h3' => array_unique($allHeadings['h3']),
            'avg_h2_count' => round($avgH2),
            'avg_h3_count' => round($avgH3),
            'recommended_h2_count' => max(3, min(8, round($avgH2 * 1.2))),
        ];
    }

    /**
     * Find frequently occurring headings (similar patterns)
     */
    private function findFrequentHeadings(array $headings): array
    {
        $normalized = [];

        foreach ($headings as $heading) {
            // Normalize for comparison
            $key = $this->normalizeHeading($heading);
            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'text' => $heading,
                    'count' => 0,
                    'variations' => [],
                ];
            }
            $normalized[$key]['count']++;
            $normalized[$key]['variations'][] = $heading;
        }

        // Sort by frequency
        uasort($normalized, fn($a, $b) => $b['count'] - $a['count']);

        // Return top frequent headings
        $frequent = [];
        foreach (array_slice($normalized, 0, 10) as $data) {
            if ($data['count'] >= 2) {
                $frequent[] = [
                    'topic' => $data['text'],
                    'frequency' => $data['count'],
                    'variations' => array_unique($data['variations']),
                ];
            }
        }

        return $frequent;
    }

    /**
     * Normalize heading for comparison
     */
    private function normalizeHeading(string $heading): string
    {
        $heading = strtolower($heading);
        $heading = preg_replace('/[0-9]+/', '#', $heading);
        $heading = preg_replace('/\s+/', ' ', $heading);

        // Remove common variations
        $heading = preg_replace('/\b(come|how|cosa|what|perché|why|quando|when)\b/i', '', $heading);

        return trim($heading);
    }

    /**
     * Extract key entities/terms from scraped content
     */
    private function extractKeyEntities(array $scrapedSources, string $keyword, string $language): array
    {
        $allText = '';

        foreach ($scrapedSources as $source) {
            if (!empty($source['content'])) {
                $allText .= ' ' . $this->sanitizeUtf8($source['content']);
            }
        }

        if (empty(trim($allText))) {
            return [];
        }

        // Tokenize
        $words = preg_split('/[\s\p{P}]+/u', strtolower($allText), -1, PREG_SPLIT_NO_EMPTY);

        // Count frequency
        $frequency = [];
        foreach ($words as $word) {
            // Skip short words and stop words
            if (strlen($word) < 3 || in_array($word, $this->stopWords)) {
                continue;
            }

            // Skip numbers
            if (is_numeric($word)) {
                continue;
            }

            $frequency[$word] = ($frequency[$word] ?? 0) + 1;
        }

        // Sort by frequency
        arsort($frequency);

        // Extract bigrams (2-word phrases)
        $bigrams = $this->extractBigrams($allText);

        // Build entities list
        $entities = [
            'single_terms' => [],
            'phrases' => [],
            'keyword_related' => [],
        ];

        // Top single terms
        $count = 0;
        foreach ($frequency as $word => $freq) {
            if ($freq >= 3 && $count < 20) {
                $entities['single_terms'][] = [
                    'term' => $word,
                    'frequency' => $freq,
                ];
                $count++;
            }
        }

        // Top phrases
        $entities['phrases'] = array_slice($bigrams, 0, 15);

        // Find keyword-related terms
        $keywordWords = preg_split('/\s+/', strtolower($keyword));
        foreach ($frequency as $word => $freq) {
            foreach ($keywordWords as $kw) {
                if (strlen($kw) > 3 && (
                    stripos($word, $kw) !== false ||
                    stripos($kw, $word) !== false ||
                    levenshtein($word, $kw) <= 2
                )) {
                    $entities['keyword_related'][] = [
                        'term' => $word,
                        'frequency' => $freq,
                        'related_to' => $kw,
                    ];
                    break;
                }
            }
        }

        $entities['keyword_related'] = array_slice($entities['keyword_related'], 0, 10);

        return $entities;
    }

    /**
     * Extract frequent bigrams (2-word phrases)
     */
    private function extractBigrams(string $text): array
    {
        $text = strtolower($text);
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $bigrams = [];

        for ($i = 0; $i < count($words) - 1; $i++) {
            $word1 = $words[$i];
            $word2 = $words[$i + 1];

            // Skip if either is a stop word or too short
            if (strlen($word1) < 3 || strlen($word2) < 3) {
                continue;
            }
            if (in_array($word1, $this->stopWords) || in_array($word2, $this->stopWords)) {
                continue;
            }

            $bigram = $word1 . ' ' . $word2;
            $bigrams[$bigram] = ($bigrams[$bigram] ?? 0) + 1;
        }

        // Filter and sort
        $bigrams = array_filter($bigrams, fn($count) => $count >= 2);
        arsort($bigrams);

        $result = [];
        foreach ($bigrams as $phrase => $freq) {
            $result[] = [
                'phrase' => $phrase,
                'frequency' => $freq,
            ];
        }

        return $result;
    }

    /**
     * Calculate word count statistics
     */
    private function calculateWordCountStats(array $scrapedSources): array
    {
        $wordCounts = [];

        foreach ($scrapedSources as $source) {
            if (isset($source['word_count']) && $source['word_count'] > 100) {
                $wordCounts[] = $source['word_count'];
            }
        }

        if (empty($wordCounts)) {
            return [
                'average' => 1500,
                'min' => 1000,
                'max' => 2000,
                'recommended' => 1500,
            ];
        }

        $average = $this->calculateAverage($wordCounts);
        $min = min($wordCounts);
        $max = max($wordCounts);

        // Recommended: slightly above average, within reasonable bounds
        $recommended = min(3000, max(1000, round($average * 1.1)));

        return [
            'average' => round($average),
            'min' => $min,
            'max' => $max,
            'recommended' => $recommended,
        ];
    }

    /**
     * Build sources summary
     */
    private function buildSourcesSummary(array $scrapedSources): array
    {
        $summary = [];

        foreach ($scrapedSources as $index => $source) {
            $summary[] = [
                'index' => $index + 1,
                'url' => $source['url'] ?? 'unknown',
                'title' => $this->sanitizeUtf8($source['title'] ?? ''),
                'word_count' => $source['word_count'] ?? 0,
                'h2_count' => count($source['headings']['h2'] ?? []),
                'h3_count' => count($source['headings']['h3'] ?? []),
                'content_preview' => $this->truncateText($this->sanitizeUtf8($source['content'] ?? ''), 200),
            ];
        }

        return $summary;
    }

    /**
     * Build recommended article structure
     */
    private function buildRecommendedStructure(array $competitorHeadings, array $searchIntent): array
    {
        $structure = [
            'intro' => 'Introduzione con risposta diretta alla query',
            'sections' => [],
            'conclusion' => 'Conclusione con riepilogo e call-to-action',
        ];

        // Add sections based on frequent H2s
        if (!empty($competitorHeadings['frequent_h2'])) {
            foreach (array_slice($competitorHeadings['frequent_h2'], 0, 5) as $h2) {
                $structure['sections'][] = [
                    'suggested_h2' => $h2['topic'],
                    'based_on' => $h2['variations'][0] ?? $h2['topic'],
                    'frequency' => $h2['frequency'],
                ];
            }
        }

        // Add intent-specific sections
        switch ($searchIntent['primary']) {
            case 'informational':
                $structure['recommended_elements'] = [
                    'definizione_chiara',
                    'esempi_pratici',
                    'step_by_step',
                    'faq_section',
                ];
                break;

            case 'commercial':
                $structure['recommended_elements'] = [
                    'confronto_prodotti',
                    'pro_contro',
                    'criteri_scelta',
                    'raccomandazioni',
                ];
                break;

            case 'transactional':
                $structure['recommended_elements'] = [
                    'caratteristiche_principali',
                    'prezzi_opzioni',
                    'come_acquistare',
                    'garanzie_supporto',
                ];
                break;

            default:
                $structure['recommended_elements'] = [
                    'panoramica',
                    'dettagli',
                    'conclusioni',
                ];
        }

        return $structure;
    }

    /**
     * Format PAA questions
     */
    private function formatPaaQuestions(array $paaQuestions): array
    {
        $formatted = [];

        foreach ($paaQuestions as $index => $question) {
            $formatted[] = [
                'question' => $question['question'] ?? $question,
                'position' => $question['position'] ?? $index + 1,
                'snippet' => $question['snippet'] ?? null,
            ];
        }

        return $formatted;
    }

    /**
     * Calculate average of array
     */
    private function calculateAverage(array $numbers): float
    {
        if (empty($numbers)) {
            return 0;
        }

        return array_sum($numbers) / count($numbers);
    }

    /**
     * Truncate text to specified length
     */
    private function truncateText(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        $truncated = substr($text, 0, $length);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated . '...';
    }

    /**
     * Generate brief for AI prompt
     */
    public function generatePromptBrief(array $brief): string
    {
        $prompt = "## BRIEF PER GENERAZIONE ARTICOLO\n\n";

        $prompt .= "**Keyword target:** {$brief['keyword']}\n";
        $prompt .= "**Lingua:** {$brief['language']}\n";
        $prompt .= "**Search Intent:** {$brief['search_intent']['primary']} (confidence: {$brief['search_intent']['confidence']})\n";
        $prompt .= "**Word count target:** {$brief['recommended_word_count']} parole\n\n";

        $prompt .= "### TITOLI COMPETITOR (SERP)\n";
        foreach (array_slice($brief['serp_titles'], 0, 5) as $t) {
            $prompt .= "- [{$t['position']}] {$t['title']}\n";
        }
        $prompt .= "\n";

        if (!empty($brief['paa_questions'])) {
            $prompt .= "### DOMANDE FREQUENTI (PAA)\n";
            foreach ($brief['paa_questions'] as $q) {
                $prompt .= "- {$q['question']}\n";
            }
            $prompt .= "\n";
        }

        if (!empty($brief['competitor_headings']['frequent_h2'])) {
            $prompt .= "### SEZIONI COMUNI NEI COMPETITOR\n";
            foreach ($brief['competitor_headings']['frequent_h2'] as $h) {
                $prompt .= "- {$h['topic']} (presente in {$h['frequency']} fonti)\n";
            }
            $prompt .= "\n";
        }

        // Termini chiave: preferisci override utente se disponibile
        if (!empty($brief['user_entities'])) {
            $prompt .= "### TERMINI CHIAVE DA INCLUDERE\n";
            $prompt .= implode(', ', $brief['user_entities']) . "\n\n";
        } elseif (!empty($brief['key_entities']['phrases'])) {
            $prompt .= "### TERMINI CHIAVE DA INCLUDERE\n";
            $terms = array_column(array_slice($brief['key_entities']['phrases'], 0, 10), 'phrase');
            $prompt .= implode(', ', $terms) . "\n\n";
        }

        // Struttura: preferisci headings editati dall'utente se disponibili
        $prompt .= "### STRUTTURA CONSIGLIATA\n";
        if (!empty($brief['user_headings'])) {
            foreach ($brief['user_headings'] as $heading) {
                $tag = $heading['tag'] ?? 'H2';
                $text = $heading['text'] ?? '';
                if (empty($text)) {
                    continue;
                }
                if ($tag === 'H1') {
                    $prompt .= "- **{$text}** (titolo principale)\n";
                } else {
                    $prompt .= "- {$text}\n";
                }
            }
        } else {
            $prompt .= "- Introduzione\n";
            foreach ($brief['recommended_structure']['sections'] ?? [] as $section) {
                $prompt .= "- {$section['suggested_h2']}\n";
            }
            $prompt .= "- Conclusione\n";
        }

        // Note utente: istruzioni aggiuntive
        if (!empty($brief['additional_notes'])) {
            $prompt .= "\n### NOTE E ISTRUZIONI SPECIFICHE DELL'UTENTE\n";
            $prompt .= $brief['additional_notes'] . "\n";
        }

        return $prompt;
    }

    /**
     * Get AI strategic analysis for content gaps and unique angles
     *
     * @param string $keyword Main keyword
     * @param string $language Content language
     * @param array $searchIntent Detected search intent
     * @param array $serpTitles Competitor titles from SERP
     * @param array $competitorHeadings Analyzed headings
     * @param array $paaQuestions PAA questions
     * @param int $userId User ID for credit consumption
     * @return array AI analysis results
     */
    private function getAiStrategicAnalysis(
        string $keyword,
        string $language,
        array $searchIntent,
        array $serpTitles,
        array $competitorHeadings,
        array $paaQuestions,
        int $userId
    ): array {
        // Check if AI is configured
        if (!$this->aiService->isConfigured()) {
            return [
                'enabled' => false,
                'reason' => 'AI service not configured'
            ];
        }

        // Build context for AI analysis
        $titlesText = $this->formatTitlesForAi($serpTitles);
        $headingsText = $this->formatHeadingsForAi($competitorHeadings);
        $paaText = $this->formatPaaForAi($paaQuestions);

        $systemPrompt = "Sei un esperto SEO strategist. Analizza i dati SERP forniti e identifica opportunità per creare contenuto superiore alla concorrenza. Rispondi SOLO in formato JSON valido.";

        $userPrompt = <<<PROMPT
Analizza i seguenti dati per la keyword "{$keyword}" ({$language}):

## TITOLI COMPETITOR (top 10):
{$titlesText}

## SEZIONI COMUNI (H2):
{$headingsText}

## DOMANDE PAA:
{$paaText}

## SEARCH INTENT RILEVATO:
{$searchIntent['primary']} (confidence: {$searchIntent['confidence']})

Fornisci un'analisi strategica in JSON con questa struttura:
```json
{
  "content_gaps": ["Argomento mancante 1", "Argomento mancante 2"],
  "unique_angles": ["Angolo unico 1", "Angolo differenziante 2"],
  "winning_title_suggestions": ["Titolo vincente 1", "Titolo vincente 2", "Titolo vincente 3"],
  "recommended_h2_structure": ["H2 consigliato 1", "H2 consigliato 2", "H2 consigliato 3"],
  "key_differentiators": ["Cosa fare diversamente 1", "Cosa fare diversamente 2"],
  "content_strategy": "Breve strategia (2-3 frasi) per superare i competitor"
}
```
PROMPT;

        try {
            $result = $this->aiService->analyzeWithSystem(
                $userId,
                $systemPrompt,
                $userPrompt,
                '',
                'ai-content'
            );

            if (isset($result['error'])) {
                Logger::channel('ai')->error("AI Strategic Analysis error", ['error' => $result['message'] ?? 'Unknown']);
                return [
                    'enabled' => false,
                    'reason' => $result['message'] ?? 'AI analysis failed'
                ];
            }

            // Parse JSON from response
            $aiText = $result['result'] ?? '';
            $parsed = $this->parseAiJsonResponse($aiText);

            if ($parsed) {
                return [
                    'enabled' => true,
                    'credits_used' => $result['credits_used'] ?? 0,
                    'analysis' => $parsed
                ];
            }

            return [
                'enabled' => false,
                'reason' => 'Could not parse AI response'
            ];

        } catch (\Exception $e) {
            Logger::channel('ai')->error("AI Strategic Analysis exception", ['error' => $e->getMessage()]);
            return [
                'enabled' => false,
                'reason' => $e->getMessage()
            ];
        }
    }

    /**
     * Format titles for AI prompt
     */
    private function formatTitlesForAi(array $titles): string
    {
        $output = "";
        foreach (array_slice($titles, 0, 10) as $t) {
            $pos = $t['position'] ?? '?';
            $title = $t['title'] ?? '';
            $output .= "- [{$pos}] {$title}\n";
        }
        return $output ?: "Nessun titolo disponibile";
    }

    /**
     * Format headings for AI prompt
     */
    private function formatHeadingsForAi(array $headings): string
    {
        $output = "";
        if (!empty($headings['frequent_h2'])) {
            foreach (array_slice($headings['frequent_h2'], 0, 8) as $h) {
                $topic = $h['topic'] ?? '';
                $freq = $h['frequency'] ?? 1;
                $output .= "- {$topic} (presente in {$freq} fonti)\n";
            }
        }
        return $output ?: "Nessun heading frequente";
    }

    /**
     * Format PAA questions for AI prompt
     */
    private function formatPaaForAi(array $paaQuestions): string
    {
        $output = "";
        foreach (array_slice($paaQuestions, 0, 8) as $q) {
            $question = $q['question'] ?? (is_string($q) ? $q : '');
            if ($question) {
                $output .= "- {$question}\n";
            }
        }
        return $output ?: "Nessuna domanda PAA";
    }

    /**
     * Parse JSON from AI response (handles code blocks)
     */
    private function parseAiJsonResponse(string $text): ?array
    {
        // Try to extract JSON from code block
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $jsonStr = trim($matches[1]);
        } else {
            // Try to find JSON object directly
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $jsonStr = $matches[0];
            } else {
                return null;
            }
        }

        $decoded = json_decode($jsonStr, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Sanitize string for valid UTF-8 encoding
     * Removes invalid UTF-8 sequences that would cause json_encode to fail
     */
    private function sanitizeUtf8(string $text): string
    {
        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Convert to UTF-8 if not already valid
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to detect encoding and convert
            $detected = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $detected);
            } else {
                // Force UTF-8 by filtering invalid sequences
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        // Remove any remaining invalid UTF-8 sequences (control characters except tabs, newlines)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Remove non-printable unicode characters (except newlines, tabs, etc)
        $text = preg_replace('/[^\PC\s]/u', '', $text);

        return $text;
    }
}
