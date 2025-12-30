<?php

namespace Modules\AiContent\Services;

use Services\ScraperService;

/**
 * ContentScraperService
 *
 * Extracts clean content from web pages for article generation
 */
class ContentScraperService
{
    private ScraperService $scraper;
    private int $timeout;

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
        '.elementor-widget-container script',
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

    public function __construct(int $timeout = 30)
    {
        $this->scraper = new ScraperService();
        $this->timeout = $timeout;
    }

    /**
     * Extract content from URL
     *
     * @param string $url URL to scrape
     * @param int $userId User ID for credit tracking
     * @return array{success: bool, content?: string, headings?: array, word_count?: int, title?: string, error?: string}
     */
    public function extractContent(string $url, int $userId): array
    {
        try {
            // Fetch HTML using shared scraper
            $result = $this->scraper->fetch($userId, $url, 'ai-content');

            if (isset($result['error']) && $result['error']) {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Errore durante il fetch'
                ];
            }

            $html = $result['html'] ?? '';

            if (empty($html)) {
                return [
                    'success' => false,
                    'error' => 'Contenuto HTML vuoto'
                ];
            }

            // Extract meta for title
            $meta = $this->scraper->extractMeta($html);
            $title = $meta['title'] ?? '';

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
                    'error' => 'Impossibile estrarre contenuto testuale'
                ];
            }

            // Extract headings from main content
            $headings = $this->extractHeadings($mainContent);

            // Count words
            $wordCount = str_word_count($textContent);

            return [
                'success' => true,
                'content' => $textContent,
                'headings' => $headings,
                'word_count' => $wordCount,
                'title' => $title,
                'url' => $result['url'] ?? $url,
                'credits_used' => $result['credits_used'] ?? 0
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Eccezione: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Batch extract content from multiple URLs
     *
     * @param array $urls Array of URLs
     * @param int $userId User ID
     * @return array Results indexed by URL
     */
    public function extractBatch(array $urls, int $userId): array
    {
        $results = [];

        foreach ($urls as $url) {
            $results[$url] = $this->extractContent($url, $userId);

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        return $results;
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
        // Use DOMDocument for reliable parsing
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

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
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

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
        $html = preg_replace('/<li[^>]*>/i', "\n• ", $html);

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
     * Extract headings structure from HTML
     *
     * @return array{h1: string[], h2: string[], h3: string[], h4: string[], h5: string[], h6: string[]}
     */
    private function extractHeadings(string $html): array
    {
        $headings = [
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
        ];

        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $html, $matches);

            foreach ($matches[1] as $heading) {
                $cleanHeading = trim(strip_tags($heading));
                if (!empty($cleanHeading)) {
                    $headings["h{$i}"][] = $cleanHeading;
                }
            }
        }

        return $headings;
    }

    /**
     * Build hierarchical heading structure
     *
     * @return array Nested array of headings
     */
    public function buildHeadingTree(array $headings): array
    {
        $tree = [];
        $flatList = [];

        // Flatten headings with level info
        foreach ($headings as $level => $items) {
            $levelNum = (int) substr($level, 1);
            foreach ($items as $text) {
                $flatList[] = ['level' => $levelNum, 'text' => $text];
            }
        }

        // Build tree structure
        $stack = [&$tree];
        $lastLevel = 0;

        foreach ($flatList as $item) {
            $node = ['text' => $item['text'], 'level' => $item['level'], 'children' => []];

            if ($item['level'] > $lastLevel) {
                // Go deeper
                $parent = &$stack[count($stack) - 1];
                if (!empty($parent)) {
                    $lastItem = &$parent[count($parent) - 1];
                    $lastItem['children'][] = $node;
                    $stack[] = &$lastItem['children'];
                } else {
                    $parent[] = $node;
                }
            } elseif ($item['level'] < $lastLevel) {
                // Go up
                $diff = $lastLevel - $item['level'];
                for ($i = 0; $i < $diff && count($stack) > 1; $i++) {
                    array_pop($stack);
                }
                $parent = &$stack[count($stack) - 1];
                $parent[] = $node;
            } else {
                // Same level
                $parent = &$stack[count($stack) - 1];
                $parent[] = $node;
            }

            $lastLevel = $item['level'];
        }

        return $tree;
    }

    /**
     * Get content summary
     */
    public function getSummary(string $content, int $maxLength = 300): string
    {
        $summary = substr($content, 0, $maxLength);

        // Cut at last sentence boundary
        $lastDot = strrpos($summary, '.');
        if ($lastDot !== false && $lastDot > $maxLength * 0.5) {
            $summary = substr($summary, 0, $lastDot + 1);
        } else {
            // Cut at last word boundary
            $lastSpace = strrpos($summary, ' ');
            if ($lastSpace !== false) {
                $summary = substr($summary, 0, $lastSpace) . '...';
            }
        }

        return $summary;
    }
}
