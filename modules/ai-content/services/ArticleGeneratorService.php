<?php

namespace Modules\AiContent\Services;

use Core\Database;
use Services\AiService;

/**
 * ArticleGeneratorService
 *
 * Generates SEO-optimized articles using Claude AI
 * Uses shared AiService for API calls
 */
class ArticleGeneratorService
{
    private AiService $aiService;
    private BriefBuilderService $briefBuilder;

    // Generation parameters
    private float $temperature = 0.7;
    private int $maxRetries = 2;
    private int $retryDelayMs = 2000;
    private string $model = 'claude-sonnet-4-20250514';

    // Output format markers
    private const TITLE_START = '```title';
    private const TITLE_END = '```';
    private const META_START = '```meta';
    private const META_END = '```';
    private const HTML_START = '```html';
    private const HTML_END = '```';

    public function __construct()
    {
        $this->aiService = new AiService('ai-content');
        $this->briefBuilder = new BriefBuilderService();
    }

    /**
     * Generate article from brief
     *
     * @param array $brief Brief from BriefBuilderService::build()
     * @param int $targetWords Target word count
     * @return array{success: bool, title?: string, meta_description?: string, content?: string, word_count?: int, generation_time_ms?: int, model_used?: string, error?: string}
     */
    public function generate(array $brief, int $targetWords = 1500, int $userId = 0): array
    {
        if (!$this->aiService->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API Key Claude non configurata. Vai in Admin > Impostazioni.'
            ];
        }

        $startTime = microtime(true);

        // Build the prompt
        $prompt = $this->buildPrompt($brief, $targetWords);

        // Calculate max tokens (roughly 1.3 tokens per word + buffer for formatting)
        $maxTokens = max(4096, (int) ($targetWords * 1.5) + 1000);

        // Try generation with retries
        $lastError = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            $response = $this->callClaudeApi($prompt, $maxTokens, $userId);

            if ($response['success']) {
                $parsed = $this->parseResponse($response['content']);

                if ($parsed['success']) {
                    $endTime = microtime(true);
                    $generationTimeMs = (int) (($endTime - $startTime) * 1000);

                    return [
                        'success' => true,
                        'title' => $this->sanitizeTitle($parsed['title'], $brief['keyword']),
                        'meta_description' => $this->sanitizeMetaDescription($parsed['meta_description'], $brief['keyword']),
                        'content' => $this->sanitizeHtml($parsed['content']),
                        'word_count' => str_word_count(strip_tags($parsed['content'])),
                        'generation_time_ms' => $generationTimeMs,
                        'model_used' => $this->model,
                        'tokens_used' => $response['tokens_used'] ?? null,
                    ];
                }

                $lastError = $parsed['error'];
            } else {
                $lastError = $response['error'];
            }

            // Wait before retry
            if ($attempt < $this->maxRetries) {
                usleep($this->retryDelayMs * 1000);
            }
        }

        $endTime = microtime(true);

        return [
            'success' => false,
            'error' => $lastError ?? 'Generazione fallita dopo ' . $this->maxRetries . ' tentativi',
            'generation_time_ms' => (int) (($endTime - $startTime) * 1000),
        ];
    }

    /**
     * Build SEO-optimized prompt for article generation
     */
    private function buildPrompt(array $brief, int $targetWords): string
    {
        $keyword = $brief['keyword'];
        $language = $brief['language'] ?? 'it';
        $intent = $brief['search_intent']['primary'] ?? 'informational';

        $languageName = match ($language) {
            'it' => 'italiano',
            'en' => 'inglese',
            'es' => 'spagnolo',
            'de' => 'tedesco',
            'fr' => 'francese',
            default => 'italiano'
        };

        // Build sources summary from scraped content
        $sourcesSummary = $this->buildSourcesSummary($brief['scraped_sources'] ?? []);
        $sourcesContent = $this->buildSourcesContent($brief['scraped_sources'] ?? []);

        // Get brief text
        $briefText = $this->briefBuilder->generatePromptBrief($brief);

        // Build internal links section if available
        $internalLinksSection = $this->buildInternalLinksSection($brief['internal_links_pool'] ?? []);

        $prompt = <<<PROMPT
Sei un esperto SEO copywriter. Il tuo obiettivo è creare un articolo che sia MIGLIORE di tutte le fonti analizzate, combinando il meglio di ognuna.

## KEYWORD TARGET
"{$keyword}"

## SEARCH INTENT
{$intent}

{$briefText}

## FONTI ANALIZZATE (TOP SERP)
{$sourcesSummary}

## CONTENUTO ESTRATTO DALLE FONTI
{$sourcesContent}
{$internalLinksSection}
## ISTRUZIONI DI GENERAZIONE

1. **Lingua**: Scrivi INTERAMENTE in {$languageName}
2. **Lunghezza**: Circa {$targetWords} parole
3. **Obiettivo**: Crea un articolo più COMPLETO e UTILE di qualsiasi singola fonte
4. **Struttura**:
   - Titolo H1 accattivante con keyword (max 60 caratteri)
   - Introduzione che risponde subito alla query
   - Sezioni H2 che coprono TUTTI gli argomenti delle fonti
   - Sottosezioni H3 dove appropriato
   - Paragrafi brevi e leggibili
   - Conclusione con riepilogo e valore aggiunto

5. **SEO On-Page**:
   - Keyword nel titolo, primo paragrafo, e sparsa naturalmente
   - Usa sinonimi e varianti della keyword
   - Includi termini correlati dal brief (key_entities)
   - Meta description persuasiva con keyword (max 155 caratteri)

6. **Qualità contenuto**:
   - COMBINA le informazioni migliori da tutte le fonti
   - Aggiungi VALORE rispondendo alle domande PAA
   - Esempi pratici dove possibile
   - Tono professionale ma accessibile
   - NO contenuto generico o filler
   - L'articolo finale deve essere PIÙ COMPLETO di ogni singola fonte

7. **Formattazione HTML**:
   - Usa <h2> e <h3> per i titoli
   - Usa <p> per i paragrafi
   - Usa <ul>/<ol> per liste
   - Usa <strong> per enfasi
   - NO <h1> nel contenuto (sarà il titolo)

## FORMATO OUTPUT RICHIESTO

Rispondi ESATTAMENTE in questo formato:

```title
[Titolo articolo - max 60 caratteri, deve contenere la keyword]
```

```meta
[Meta description - max 155 caratteri, persuasiva, con keyword]
```

```html
[Contenuto HTML completo dell'articolo]
```

IMPORTANTE:
- Rispetta ESATTAMENTE il formato con i blocchi ```title, ```meta, ```html
- Il contenuto HTML deve essere valido e ben formattato
- Non aggiungere commenti o testo fuori dai blocchi
PROMPT;

        return $prompt;
    }

    /**
     * Build summary of scraped sources
     */
    private function buildSourcesSummary(array $sources): string
    {
        if (empty($sources)) {
            return "Nessuna fonte analizzata.";
        }

        $summary = "Ho analizzato " . count($sources) . " fonti dalla top SERP:\n\n";

        foreach ($sources as $index => $source) {
            $num = $index + 1;
            $wordCount = $source['word_count'] ?? 0;
            $h2Count = count($source['headings']['h2'] ?? []);
            $title = $this->sanitizeUtf8($source['title'] ?? 'Titolo non disponibile');
            $url = $source['url'] ?? 'URL non disponibile';
            $summary .= "{$num}. **{$title}**\n";
            $summary .= "   URL: {$url}\n";
            $summary .= "   Parole: {$wordCount} | Sezioni H2: {$h2Count}\n\n";
        }

        return $summary;
    }

    /**
     * Build extracted content from sources for AI context
     */
    private function buildSourcesContent(array $sources): string
    {
        if (empty($sources)) {
            return "";
        }

        $content = "";
        $maxContentPerSource = 2000; // Limit content per source to avoid token overflow

        foreach ($sources as $index => $source) {
            $num = $index + 1;
            $title = $this->sanitizeUtf8($source['title'] ?? 'Fonte sconosciuta');
            $content .= "### FONTE {$num}: {$title}\n\n";

            // Add headings structure
            if (!empty($source['headings']['h2'])) {
                $content .= "**Struttura H2:**\n";
                foreach (array_slice($source['headings']['h2'], 0, 8) as $h2) {
                    $h2 = $this->sanitizeUtf8($h2);
                    $content .= "- {$h2}\n";
                }
                $content .= "\n";
            }

            // Add content preview (truncated)
            if (!empty($source['content'])) {
                $sourceContent = $this->sanitizeUtf8($source['content']);
                if (strlen($sourceContent) > $maxContentPerSource) {
                    $sourceContent = substr($sourceContent, 0, $maxContentPerSource) . "...";
                }
                $content .= "**Contenuto chiave:**\n{$sourceContent}\n\n";
            }

            $content .= "---\n\n";
        }

        return $content;
    }

    /**
     * Build internal links section for the prompt
     * SEO-optimized instructions for natural, strategic internal linking
     */
    private function buildInternalLinksSection(array $internalLinks): string
    {
        if (empty($internalLinks)) {
            return "";
        }

        $section = "\n## LINK INTERNI (SEO STRATEGICI)\n\n";

        $section .= "### REGOLE DI INSERIMENTO (OBBLIGATORIE)\n\n";

        $section .= "**PERTINENZA SEMANTICA (PRIORITA' ASSOLUTA)**\n";
        $section .= "- Inserisci SOLO link che sono SEMANTICAMENTE CORRELATI al paragrafo in cui li metti\n";
        $section .= "- Il link deve AGGIUNGERE VALORE al lettore, approfondendo un concetto menzionato\n";
        $section .= "- Se l'articolo parla di 'SEO', linka solo pagine su SEO, non su 'web design' o argomenti non correlati\n";
        $section .= "- SE NESSUN LINK E' PERTINENTE ALL'ARGOMENTO DELL'ARTICOLO, NON INSERIRNE NESSUNO\n\n";

        $section .= "**ANCHOR TEXT SEO-FRIENDLY**\n";
        $section .= "- USA anchor text DESCRITTIVI che indicano il contenuto della pagina di destinazione\n";
        $section .= "- VIETATO: 'clicca qui', 'leggi di piu', 'questo articolo', URL nude\n";
        $section .= "- CORRETTO: 'strategie SEO avanzate', 'guida al content marketing', 'ottimizzazione Core Web Vitals'\n";
        $section .= "- L'anchor text deve essere una frase NATURALE che si integra nel discorso\n";
        $section .= "- NON ripetere lo stesso anchor text per link diversi\n\n";

        $section .= "**POSIZIONAMENTO STRATEGICO**\n";
        $section .= "- Distribuisci i link in SEZIONI DIVERSE dell'articolo (non tutti nello stesso paragrafo)\n";
        $section .= "- Preferisci inserire almeno 1 link nei primi 2-3 paragrafi (valore SEO maggiore)\n";
        $section .= "- Inserisci i link dove il contesto NATURALMENTE richiede un approfondimento\n";
        $section .= "- Massimo 2-4 link interni totali, NON di piu'\n\n";

        $section .= "**FORMATO HTML**\n";
        $section .= "- Usa il formato: <a href=\"URL_COMPLETO\">anchor text naturale</a>\n";
        $section .= "- Il link deve essere INLINE nel testo, non in elenchi puntati separati\n\n";

        $section .= "### POOL DI LINK DISPONIBILI\n";
        $section .= "Scegli SOLO quelli pertinenti all'argomento dell'articolo:\n\n";

        foreach ($internalLinks as $link) {
            $title = $this->sanitizeUtf8($link['title'] ?? 'Pagina');
            // Rimuovi suffisso sito dal titolo per renderlo più leggibile
            $title = preg_replace('/\s*[-|]\s*[^-|]+$/', '', $title);
            $url = $link['url'] ?? '';
            $desc = !empty($link['description'])
                ? $this->sanitizeUtf8(mb_substr($link['description'], 0, 150))
                : '';

            $section .= "- **{$title}**\n";
            $section .= "  URL: {$url}\n";
            if ($desc) {
                $section .= "  Tema: {$desc}\n";
            }
            $section .= "\n";
        }

        $section .= "---\n";
        $section .= "RICORDA: E' MEGLIO NON INSERIRE LINK che inserire link non pertinenti. ";
        $section .= "La qualita' e pertinenza sono piu' importanti della quantita'.\n\n";

        return $section;
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

        // Remove any remaining invalid UTF-8 sequences
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Remove non-printable unicode characters (except newlines, tabs)
        $text = preg_replace('/[^\PC\s]/u', '', $text);

        return $text;
    }

    /**
     * Call Claude API via shared AiService
     */
    private function callClaudeApi(string $prompt, int $maxTokens, int $userId = 0): array
    {
        if (!$this->aiService->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API Key Claude non configurata. Verifica la configurazione.'
            ];
        }

        // Use the shared AiService complete method
        $result = $this->aiService->complete($userId, [
            ['role' => 'user', 'content' => $prompt],
        ], [
            'max_tokens' => $maxTokens,
            'model' => $this->model,
        ], 'ai-content');

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Errore API Claude',
            ];
        }

        return [
            'success' => true,
            'content' => $result['result'] ?? '',
            'tokens_used' => [
                'input' => 0,
                'output' => 0,
            ],
            'credits_used' => $result['credits_used'] ?? 0,
        ];
    }

    /**
     * Parse Claude response into structured data
     */
    private function parseResponse(string $response): array
    {
        $title = $this->extractBlock($response, self::TITLE_START, self::TITLE_END);
        $meta = $this->extractBlock($response, self::META_START, self::META_END);
        $html = $this->extractBlock($response, self::HTML_START, self::HTML_END);

        // Validate extracted content
        if (empty($title)) {
            return [
                'success' => false,
                'error' => 'Impossibile estrarre il titolo dalla risposta'
            ];
        }

        if (empty($html)) {
            return [
                'success' => false,
                'error' => 'Impossibile estrarre il contenuto HTML dalla risposta'
            ];
        }

        // Meta description is optional, generate from content if missing
        if (empty($meta)) {
            $meta = $this->generateMetaFromContent($html);
        }

        return [
            'success' => true,
            'title' => trim($title),
            'meta_description' => trim($meta),
            'content' => trim($html),
        ];
    }

    /**
     * Extract content block from response
     */
    private function extractBlock(string $response, string $startMarker, string $endMarker): string
    {
        // Find start marker
        $startPos = strpos($response, $startMarker);
        if ($startPos === false) {
            return '';
        }

        // Move past the marker and newline
        $startPos += strlen($startMarker);
        while ($startPos < strlen($response) && ($response[$startPos] === "\n" || $response[$startPos] === "\r")) {
            $startPos++;
        }

        // Find the closing marker after start
        $endPos = strpos($response, $endMarker, $startPos);
        if ($endPos === false) {
            return '';
        }

        // Go back to remove trailing newlines before end marker
        while ($endPos > $startPos && ($response[$endPos - 1] === "\n" || $response[$endPos - 1] === "\r")) {
            $endPos--;
        }

        return substr($response, $startPos, $endPos - $startPos);
    }

    /**
     * Generate meta description from content if not provided
     */
    private function generateMetaFromContent(string $html): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Get first ~150 characters, cut at word boundary
        if (strlen($text) > 155) {
            $text = substr($text, 0, 152);
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace > 100) {
                $text = substr($text, 0, $lastSpace);
            }
            $text .= '...';
        }

        return $text;
    }

    /**
     * Sanitize and validate title
     */
    private function sanitizeTitle(string $title, string $keyword): string
    {
        // Remove any HTML
        $title = strip_tags($title);

        // Remove quotes that might have been added
        $title = trim($title, '"\'');

        // Truncate if too long
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }

        // Ensure keyword is present (case-insensitive check)
        if (stripos($title, $keyword) === false) {
            // Try to prepend keyword if title is short enough
            $keywordTitle = $keyword . ': ' . $title;
            if (strlen($keywordTitle) <= 60) {
                $title = $keywordTitle;
            }
        }

        return $title;
    }

    /**
     * Sanitize and validate meta description
     */
    private function sanitizeMetaDescription(string $meta, string $keyword): string
    {
        // Remove any HTML
        $meta = strip_tags($meta);

        // Remove quotes
        $meta = trim($meta, '"\'');

        // Truncate if too long
        if (strlen($meta) > 155) {
            $meta = substr($meta, 0, 152) . '...';
        }

        return $meta;
    }

    /**
     * Sanitize HTML content
     */
    private function sanitizeHtml(string $html): string
    {
        // Remove potentially dangerous tags
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);

        // Remove on* event handlers
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Clean up whitespace
        $html = preg_replace('/>\s+</', ">\n<", $html);

        // Ensure proper paragraph wrapping
        $html = $this->ensureParagraphWrapping($html);

        return trim($html);
    }

    /**
     * Ensure text is properly wrapped in paragraphs
     */
    private function ensureParagraphWrapping(string $html): string
    {
        // Split by double newlines
        $blocks = preg_split('/\n\n+/', $html);

        $result = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            // Check if already wrapped in a block element
            if (preg_match('/^<(h[1-6]|p|ul|ol|div|blockquote|table|figure)/i', $block)) {
                $result[] = $block;
            } else {
                // Wrap in paragraph
                $result[] = '<p>' . $block . '</p>';
            }
        }

        return implode("\n\n", $result);
    }

    /**
     * Check if API is configured
     */
    public function isConfigured(): bool
    {
        return $this->aiService->isConfigured();
    }

    /**
     * Get current model
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set custom temperature
     */
    public function setTemperature(float $temperature): void
    {
        $this->temperature = max(0, min(1, $temperature));
    }

    /**
     * Set custom model
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Regenerate article with modifications
     */
    public function regenerate(array $brief, string $existingContent, string $instructions, int $targetWords = 1500): array
    {
        if (!$this->aiService->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API Key Claude non configurata'
            ];
        }

        $startTime = microtime(true);

        $prompt = $this->buildRegeneratePrompt($brief, $existingContent, $instructions, $targetWords);
        $maxTokens = max(4096, (int) ($targetWords * 1.5) + 1000);

        $response = $this->callClaudeApi($prompt, $maxTokens);

        if (!$response['success']) {
            return $response;
        }

        $parsed = $this->parseResponse($response['content']);

        if (!$parsed['success']) {
            return $parsed;
        }

        $endTime = microtime(true);

        return [
            'success' => true,
            'title' => $this->sanitizeTitle($parsed['title'], $brief['keyword']),
            'meta_description' => $this->sanitizeMetaDescription($parsed['meta_description'], $brief['keyword']),
            'content' => $this->sanitizeHtml($parsed['content']),
            'word_count' => str_word_count(strip_tags($parsed['content'])),
            'generation_time_ms' => (int) (($endTime - $startTime) * 1000),
            'model_used' => $this->model,
        ];
    }

    /**
     * Build prompt for regeneration with existing content
     */
    private function buildRegeneratePrompt(array $brief, string $existingContent, string $instructions, int $targetWords): string
    {
        $keyword = $brief['keyword'];

        $prompt = <<<PROMPT
Sei un esperto SEO copywriter. Devi RIGENERARE un articolo esistente sulla keyword "{$keyword}".

## CONTENUTO ESISTENTE
{$existingContent}

## ISTRUZIONI DI MODIFICA
{$instructions}

## REQUISITI
- Lunghezza target: {$targetWords} parole
- Mantieni la struttura SEO-ottimizzata
- Keyword nel titolo e contenuto
- Meta description persuasiva (max 155 caratteri)

## FORMATO OUTPUT RICHIESTO

```title
[Nuovo titolo - max 60 caratteri]
```

```meta
[Nuova meta description - max 155 caratteri]
```

```html
[Contenuto HTML rigenerato]
```
PROMPT;

        return $prompt;
    }
}
