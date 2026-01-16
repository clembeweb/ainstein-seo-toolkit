<?php

namespace Modules\AdsAnalyzer\Services;

require_once __DIR__ . '/../../../services/AiService.php';
require_once __DIR__ . '/../../../services/ScraperService.php';

class ContextExtractorService
{
    private \Services\AiService $aiService;
    private \Services\ScraperService $scraper;

    // Selectors for main content areas (priority order)
    private array $contentSelectors = [
        'article',
        'main',
        '[role="main"]',
        '.post-content',
        '.entry-content',
        '.article-content',
        '.content',
        '.post-body',
        '.article-body',
        '#content',
        '#main-content',
        '.main-content',
        '.product-description',
        '.product-content',
        '.landing-content',
    ];

    // Elements to remove
    private array $removeSelectors = [
        'header',
        'footer',
        'nav',
        'aside',
        '.sidebar',
        '.navigation',
        '.menu',
        '.breadcrumb',
        '.breadcrumbs',
        '.social-share',
        '.share-buttons',
        '.related-posts',
        '.comments',
        '#comments',
        '.comment-section',
        '.author-box',
        '.author-bio',
        '.advertisement',
        '.ad',
        '.ads',
        '[class*="cookie"]',
        '[class*="popup"]',
        '[class*="modal"]',
        '[class*="newsletter"]',
        '.wp-block-buttons',
    ];

    // Tags to completely remove
    private array $removeTags = [
        'script',
        'style',
        'noscript',
        'iframe',
        'form',
        'button',
        'input',
        'select',
        'textarea',
        'svg',
        'canvas',
    ];

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ads-analyzer');
        $this->scraper = new \Services\ScraperService();
    }

    /**
     * Scrape landing page e estrai contesto con AI
     */
    public function extractFromUrl(int $userId, string $url): array
    {
        error_log("=== ContextExtractor: START ===");
        error_log("URL: $url");

        // Step 1: Fetch HTML
        try {
            $result = $this->scraper->fetchRaw($url, ['timeout' => 30]);

            if (isset($result['error'])) {
                error_log("Fetch error: " . ($result['message'] ?? 'Unknown'));
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Errore durante il fetch'
                ];
            }

            // Check HTTP status
            $httpCode = $result['http_code'] ?? 0;
            error_log("HTTP Code: $httpCode");

            if ($httpCode >= 400) {
                return [
                    'success' => false,
                    'error' => "HTTP Error {$httpCode}: impossibile accedere alla pagina"
                ];
            }

            $html = $result['body'] ?? '';

            if (empty($html)) {
                return [
                    'success' => false,
                    'error' => 'Contenuto HTML vuoto'
                ];
            }

            error_log("HTML length: " . strlen($html));

            // Extract meta for title
            $meta = $this->scraper->extractMeta($html);
            $title = $meta['title'] ?? '';
            $description = $meta['description'] ?? '';

            // Clean and extract content
            $cleanedHtml = $this->cleanHtml($html);
            $mainContent = $this->extractMainContent($cleanedHtml);

            if (empty($mainContent)) {
                // Fallback: use body content
                $mainContent = $this->extractBodyContent($cleanedHtml);
            }

            // Convert to plain text
            $textContent = $this->htmlToText($mainContent);

            if (empty(trim($textContent))) {
                return [
                    'success' => false,
                    'error' => 'Impossibile estrarre contenuto testuale dalla pagina'
                ];
            }

            // Extract headings
            $headings = $this->scraper->extractHeadings($html);

            // Format scraped content
            $scrapedContent = $this->formatScrapedContent([
                'title' => $title,
                'description' => $description,
                'headings' => $headings,
                'content' => $textContent,
            ]);

            error_log("Scraped content length: " . strlen($scrapedContent));

        } catch (\Exception $e) {
            error_log("Scraping error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Errore scraping: ' . $e->getMessage()
            ];
        }

        // Step 2: Limita contenuto per prompt (max 8000 caratteri)
        $contentForPrompt = substr($scrapedContent, 0, 8000);

        // Step 3: AI estrae contesto
        $prompt = $this->buildExtractionPrompt($contentForPrompt);

        error_log("Calling AI for context extraction...");

        $response = $this->aiService->analyze(
            $userId,
            $prompt,
            '',
            'ads-analyzer-context'
        );

        error_log("AI response keys: " . implode(', ', array_keys($response)));

        if (isset($response['error'])) {
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Errore AI'
            ];
        }

        $extractedContext = $this->parseContextResponse($response['result'] ?? '');

        error_log("Extracted context length: " . strlen($extractedContext));
        error_log("=== ContextExtractor: SUCCESS ===");

        return [
            'success' => true,
            'scraped_content' => $scrapedContent,
            'extracted_context' => $extractedContext
        ];
    }

    /**
     * Clean HTML by removing unwanted elements
     */
    private function cleanHtml(string $html): string
    {
        // Remove tags completely (including content)
        foreach ($this->removeTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $html);
            $html = preg_replace('/<' . $tag . '\b[^>]*\/?>/i', '', $html);
        }

        // Remove comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Remove inline styles and scripts
        $html = preg_replace('/\s+style\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Convert to DOM for selector-based removal
        $html = $this->removeBySelectors($html, $this->removeSelectors);

        return $html;
    }

    /**
     * Remove elements by CSS-like selectors
     */
    private function removeBySelectors(string $html, array $selectors): string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);

        foreach ($selectors as $selector) {
            $xpathQuery = $this->cssToXpath($selector);

            if ($xpathQuery) {
                $nodes = $xpath->query($xpathQuery);

                if ($nodes) {
                    foreach ($nodes as $node) {
                        if ($node->parentNode) {
                            $node->parentNode->removeChild($node);
                        }
                    }
                }
            }
        }

        libxml_clear_errors();

        return $dom->saveHTML();
    }

    /**
     * Extract main content using content selectors
     */
    private function extractMainContent(string $html): string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);

        // Try each content selector in priority order
        foreach ($this->contentSelectors as $selector) {
            $xpathQuery = $this->cssToXpath($selector);

            if ($xpathQuery) {
                $nodes = $xpath->query($xpathQuery);

                if ($nodes && $nodes->length > 0) {
                    // Get the first matching element
                    $node = $nodes->item(0);
                    $content = $dom->saveHTML($node);

                    // Check if it has meaningful content
                    $textLength = strlen(strip_tags($content));
                    if ($textLength > 200) {
                        libxml_clear_errors();
                        return $content;
                    }
                }
            }
        }

        libxml_clear_errors();
        return '';
    }

    /**
     * Extract body content as fallback
     */
    private function extractBodyContent(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }

    /**
     * Convert simple CSS selector to XPath
     */
    private function cssToXpath(string $selector): string
    {
        $selector = trim($selector);

        // Tag name
        if (preg_match('/^[a-z][a-z0-9]*$/i', $selector)) {
            return '//' . $selector;
        }

        // ID selector
        if (preg_match('/^#([a-z][a-z0-9_-]*)$/i', $selector, $m)) {
            return '//*[@id="' . $m[1] . '"]';
        }

        // Class selector
        if (preg_match('/^\.([a-z][a-z0-9_-]*)$/i', $selector, $m)) {
            return '//*[contains(concat(" ", normalize-space(@class), " "), " ' . $m[1] . ' ")]';
        }

        // Attribute contains
        if (preg_match('/^\[([a-z]+)\*="([^"]+)"\]$/i', $selector, $m)) {
            return '//*[contains(@' . $m[1] . ', "' . $m[2] . '")]';
        }

        // Attribute equals
        if (preg_match('/^\[([a-z]+)="([^"]+)"\]$/i', $selector, $m)) {
            return '//*[@' . $m[1] . '="' . $m[2] . '"]';
        }

        // Tag with class
        if (preg_match('/^([a-z]+)\.([a-z][a-z0-9_-]*)$/i', $selector, $m)) {
            return '//' . $m[1] . '[contains(concat(" ", normalize-space(@class), " "), " ' . $m[2] . ' ")]';
        }

        return '';
    }

    /**
     * Convert HTML to clean text
     */
    private function htmlToText(string $html): string
    {
        // Replace block elements with newlines
        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|br)[^>]*>/i', "\n", $html);

        // Replace list items with bullet
        $html = preg_replace('/<li[^>]*>/i', "\n- ", $html);

        // Strip remaining tags
        $text = strip_tags($html);

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);

        // Trim lines
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", array_filter($lines, fn($line) => $line !== ''));

        return trim($text);
    }

    /**
     * Formatta i dati scrappati in testo strutturato
     */
    private function formatScrapedContent(array $data): string
    {
        $parts = [];

        // Title
        if (!empty($data['title'])) {
            $parts[] = "TITOLO: " . $data['title'];
        }

        // Description
        if (!empty($data['description'])) {
            $parts[] = "DESCRIZIONE: " . $data['description'];
        }

        // Headings
        if (!empty($data['headings'])) {
            $headingTexts = [];
            foreach ($data['headings'] as $level => $headings) {
                foreach ($headings as $heading) {
                    if (!empty($heading)) {
                        $headingTexts[] = strtoupper($level) . ": " . $heading;
                    }
                }
            }
            if (!empty($headingTexts)) {
                $parts[] = "TITOLI PAGINA:\n" . implode("\n", $headingTexts);
            }
        }

        // Content
        if (!empty($data['content'])) {
            $parts[] = "CONTENUTO:\n" . $data['content'];
        }

        return implode("\n\n", $parts);
    }

    /**
     * Prompt per estrazione contesto
     */
    private function buildExtractionPrompt(string $content): string
    {
        return <<<PROMPT
Sei un esperto di Google Ads. Analizza il contenuto di questa landing page e genera un CONTESTO BUSINESS per l'analisi delle keyword negative.

CONTENUTO PAGINA:
{$content}

GENERA un contesto business che descriva:
1. Cosa vende/promuove questa pagina
2. Il target di riferimento
3. Cosa NON vende/offre (importante per keyword negative)
4. Eventuali brand/prodotti menzionati

FORMATO OUTPUT:
Scrivi un paragrafo di 100-200 parole che descriva chiaramente:
- L'attivita/servizio promosso
- Il pubblico target
- Cosa esplicitamente NON viene offerto

Rispondi SOLO con il testo del contesto, senza preamboli o spiegazioni.
PROMPT;
    }

    /**
     * Pulisce la risposta AI
     */
    private function parseContextResponse(string $response): string
    {
        // Rimuovi eventuali markdown o wrapper
        $context = trim($response);
        $context = preg_replace('/^```[\w]*\n?/', '', $context);
        $context = preg_replace('/\n?```$/', '', $context);
        return trim($context);
    }
}
