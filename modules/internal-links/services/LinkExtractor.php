<?php

namespace Modules\InternalLinks\Services;

/**
 * LinkExtractor Service
 *
 * Extracts and normalizes links from HTML content using PHP's DOMDocument
 */
class LinkExtractor
{
    /**
     * Patterns to ignore
     */
    private array $ignorePatterns = [
        '#^javascript:#i',
        '#^mailto:#i',
        '#^tel:#i',
        '#^fax:#i',
        '#^sms:#i',
        '#^data:#i',
        '#^#$#',
        '#^$#',
    ];

    /**
     * Extract links from HTML content
     */
    public function extract(string $html, string $baseUrl, string $pageUrl, ?string $sourceBlock = null): array
    {
        $links = [];
        $position = 0;

        if (empty($html)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $anchors = $doc->getElementsByTagName('a');

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');

            if (empty($href) || $this->shouldIgnore($href)) {
                continue;
            }

            $anchorText = $this->extractAnchorText($anchor);
            $normalizedUrl = $this->normalizeUrl($href, $baseUrl, $pageUrl);

            if ($normalizedUrl === null) {
                continue;
            }

            $isInternal = $this->isInternalUrl($normalizedUrl, $baseUrl);
            $position++;

            $links[] = [
                'url' => $normalizedUrl,
                'anchor' => $anchorText,
                'position' => $position,
                'is_internal' => $isInternal,
                'original_href' => $href,
                'source_block' => $sourceBlock,
            ];
        }

        return $links;
    }

    /**
     * Extract links with CSS selectors and optional regex blocks
     */
    public function extractWithSelectors(
        string $rawHtml,
        string $baseUrl,
        string $pageUrl,
        ?string $cssSelector = null,
        ?string $blockRegex = null
    ): array {
        $contentHtml = $this->extractContentFromSelectors($rawHtml, $cssSelector);

        if (empty($contentHtml)) {
            return [];
        }

        if (!empty($blockRegex)) {
            return $this->extractFromRegexBlocks($contentHtml, $baseUrl, $pageUrl, $blockRegex);
        }

        return $this->extract($contentHtml, $baseUrl, $pageUrl);
    }

    /**
     * Extract content from CSS selectors using DOMXPath
     */
    public function extractContentFromSelectors(string $html, ?string $cssSelector = null): ?string
    {
        if (empty($html)) {
            return null;
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        if (empty($cssSelector)) {
            $body = $doc->getElementsByTagName('body')->item(0);
            if ($body) {
                return $doc->saveHTML($body);
            }
            return $html;
        }

        $xpath = new \DOMXPath($doc);
        $selectors = array_map('trim', explode(',', $cssSelector));
        $contentParts = [];

        foreach ($selectors as $selector) {
            if (empty($selector)) {
                continue;
            }

            $xpathQuery = $this->cssToXpath($selector);
            if ($xpathQuery === null) {
                continue;
            }

            $nodes = $xpath->query($xpathQuery);
            if ($nodes) {
                foreach ($nodes as $node) {
                    $contentParts[] = $doc->saveHTML($node);
                }
            }
        }

        return empty($contentParts) ? null : implode("\n", $contentParts);
    }

    /**
     * Convert simple CSS selector to XPath
     */
    private function cssToXpath(string $selector): ?string
    {
        $selector = trim($selector);

        // ID selector: #id
        if (preg_match('/^#([\w-]+)$/', $selector, $m)) {
            return "//*[@id='{$m[1]}']";
        }

        // Class selector: .class
        if (preg_match('/^\.([\w-]+)$/', $selector, $m)) {
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$m[1]} ')]";
        }

        // Tag selector: tag
        if (preg_match('/^([\w-]+)$/', $selector, $m)) {
            return "//{$m[1]}";
        }

        // Tag with class: tag.class
        if (preg_match('/^([\w-]+)\.([\w-]+)$/', $selector, $m)) {
            return "//{$m[1]}[contains(concat(' ', normalize-space(@class), ' '), ' {$m[2]} ')]";
        }

        // Tag with ID: tag#id
        if (preg_match('/^([\w-]+)#([\w-]+)$/', $selector, $m)) {
            return "//{$m[1]}[@id='{$m[2]}']";
        }

        return null;
    }

    /**
     * Extract links from regex-matched blocks
     */
    public function extractFromRegexBlocks(string $html, string $baseUrl, string $pageUrl, string $blockRegex): array
    {
        $allLinks = [];
        $globalPosition = 0;

        $blocks = $this->findRegexBlocks($html, $blockRegex);

        foreach ($blocks as $blockIndex => $block) {
            $blockLinks = $this->extract($block, $baseUrl, $pageUrl, $block);

            foreach ($blockLinks as $link) {
                $globalPosition++;
                $link['position'] = $globalPosition;
                $link['block_index'] = $blockIndex;
                $allLinks[] = $link;
            }
        }

        return $allLinks;
    }

    /**
     * Find blocks matching a regex pattern
     */
    public function findRegexBlocks(string $html, string $regex): array
    {
        $blocks = [];

        if (!$this->isValidRegex($regex)) {
            return $blocks;
        }

        if (preg_match_all($regex, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $blocks[] = $match[1] ?? $match[0];
            }
        }

        return $blocks;
    }

    /**
     * Validate a regex pattern
     */
    public function isValidRegex(string $regex): bool
    {
        if (empty($regex)) {
            return false;
        }

        set_error_handler(function() {}, E_WARNING);
        $isValid = @preg_match($regex, '') !== false;
        restore_error_handler();

        return $isValid;
    }

    /**
     * Check if URL should be ignored
     */
    private function shouldIgnore(string $url): bool
    {
        $url = trim($url);

        foreach ($this->ignorePatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract anchor text from link node
     */
    private function extractAnchorText(\DOMElement $node): ?string
    {
        $text = $node->textContent;

        if (empty(trim($text))) {
            $text = $node->getAttribute('title');
        }

        if (empty(trim($text))) {
            $imgs = $node->getElementsByTagName('img');
            if ($imgs->length > 0) {
                $text = $imgs->item(0)->getAttribute('alt');
            }
        }

        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);

        if (strlen($text) > 500) {
            $text = substr($text, 0, 497) . '...';
        }

        return $text ?: null;
    }

    /**
     * Normalize URL to absolute form
     */
    private function normalizeUrl(string $url, string $baseUrl, string $pageUrl): ?string
    {
        $url = trim($url);

        if (($pos = strpos($url, '#')) !== false) {
            $url = substr($url, 0, $pos);
        }

        if (empty($url)) {
            return null;
        }

        $baseParsed = parse_url($baseUrl);
        $baseScheme = $baseParsed['scheme'] ?? 'https';
        $baseHost = $baseParsed['host'] ?? '';

        if (strpos($url, '//') === 0) {
            $url = $baseScheme . ':' . $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $this->cleanUrl($url);
        }

        if (strpos($url, '/') === 0) {
            return $this->cleanUrl($baseScheme . '://' . $baseHost . $url);
        }

        $pageParsed = parse_url($pageUrl);
        $pagePath = $pageParsed['path'] ?? '/';
        $pageDir = dirname($pagePath);
        if ($pageDir === '\\' || $pageDir === '.') {
            $pageDir = '/';
        }

        $resolvedPath = $this->resolvePath($pageDir . '/' . $url);

        return $this->cleanUrl($baseScheme . '://' . $baseHost . $resolvedPath);
    }

    /**
     * Resolve relative path components
     */
    private function resolvePath(string $path): string
    {
        $parts = explode('/', $path);
        $resolved = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } elseif ($part !== '.' && $part !== '') {
                $resolved[] = $part;
            }
        }

        return '/' . implode('/', $resolved);
    }

    /**
     * Clean and normalize URL
     */
    private function cleanUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            return $url;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) && !in_array($parsed['port'], [80, 443]) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        return $scheme . '://' . $host . $port . $path . $query;
    }

    /**
     * Check if URL is internal to the base domain
     */
    private function isInternalUrl(string $url, string $baseUrl): bool
    {
        $urlParsed = parse_url($url);
        $baseParsed = parse_url($baseUrl);

        if (!$urlParsed || !$baseParsed) {
            return false;
        }

        $urlHost = strtolower($urlParsed['host'] ?? '');
        $baseHost = strtolower($baseParsed['host'] ?? '');

        if ($urlHost === $baseHost) {
            return true;
        }

        $urlHostWithoutWww = preg_replace('/^www\./i', '', $urlHost);
        $baseHostWithoutWww = preg_replace('/^www\./i', '', $baseHost);

        if ($urlHostWithoutWww === $baseHostWithoutWww) {
            return true;
        }

        if (str_ends_with($urlHost, '.' . $baseHostWithoutWww)) {
            return true;
        }

        return false;
    }

    /**
     * Count links in HTML by type
     */
    public function countLinks(string $html, string $baseUrl): array
    {
        $links = $this->extract($html, $baseUrl, $baseUrl);

        $counts = [
            'total' => count($links),
            'internal' => 0,
            'external' => 0,
            'with_anchor' => 0,
            'empty_anchor' => 0,
        ];

        foreach ($links as $link) {
            if ($link['is_internal']) {
                $counts['internal']++;
            } else {
                $counts['external']++;
            }

            if (!empty($link['anchor'])) {
                $counts['with_anchor']++;
            } else {
                $counts['empty_anchor']++;
            }
        }

        return $counts;
    }
}
