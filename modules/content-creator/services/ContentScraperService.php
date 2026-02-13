<?php

namespace Modules\ContentCreator\Services;

use Services\ScraperService;

class ContentScraperService
{
    private ScraperService $scraper;

    public function __construct()
    {
        $this->scraper = new ScraperService();
    }

    /**
     * Scrape URL and extract structured data for content creator
     * Returns: title, h1, meta_title, meta_description, content, price, word_count, slug
     */
    public function scrapeUrl(string $url): array
    {
        // Uses shared ScraperService::scrape() (Readability-based)
        $result = $this->scraper->scrape($url);

        // Extract slug from URL path
        $slug = $this->extractSlug($url);

        // Extract price from content (basic pattern for e-commerce)
        $price = $this->extractPrice($result['content'] ?? '');

        return [
            'title' => $result['title'] ?? '',
            'h1' => $result['headings']['h1'][0] ?? '',
            'meta_title' => $result['title'] ?? '',
            'meta_description' => $result['description'] ?? '',
            'content' => mb_substr($result['content'] ?? '', 0, 50000),
            'price' => $price,
            'word_count' => $result['word_count'] ?? 0,
            'slug' => $slug,
        ];
    }

    /**
     * Extract slug from URL path
     */
    private function extractSlug(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) return '';

        $path = rtrim($path, '/');
        $parts = explode('/', $path);
        $slug = end($parts);

        // Remove file extensions
        $slug = preg_replace('/\.(html?|php|aspx?)$/i', '', $slug);

        return $slug ?: '';
    }

    /**
     * Extract price from content text (basic patterns)
     */
    private function extractPrice(string $content): ?string
    {
        // Common price patterns: €12.99, $29.99, 12,99€, 12.99 EUR
        if (preg_match('/[€\$£]\s*[\d.,]+|[\d.,]+\s*[€\$£]|[\d.,]+\s*(EUR|USD|GBP)/i', $content, $matches)) {
            return trim($matches[0]);
        }
        return null;
    }
}
