<?php

namespace Services;

use Core\Credits;

class ScraperService
{
    private array $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
    ];

    public function fetch(int $userId, string $url, ?string $moduleSlug = null): array
    {
        $cost = Credits::getCost('scrape_url');

        // Verifica crediti
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti',
                'credits_required' => $cost,
            ];
        }

        // Valida URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => true, 'message' => 'URL non valido'];
        }

        // Fetch
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'Errore fetch: ' . $error];
        }

        if ($httpCode >= 400) {
            return ['error' => true, 'message' => 'HTTP Error: ' . $httpCode];
        }

        // Consuma crediti
        Credits::consume($userId, $cost, 'scrape_url', $moduleSlug, [
            'url' => $url,
            'final_url' => $finalUrl,
            'http_code' => $httpCode,
            'size' => strlen($html),
        ]);

        return [
            'success' => true,
            'html' => $html,
            'url' => $finalUrl,
            'http_code' => $httpCode,
            'credits_used' => $cost,
        ];
    }

    public function extractMeta(string $html): array
    {
        $meta = [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'canonical' => '',
            'robots' => '',
            'og' => [],
            'twitter' => [],
        ];

        // Title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $meta['title'] = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        // Meta tags
        preg_match_all('/<meta[^>]+>/i', $html, $metaTags);

        foreach ($metaTags[0] as $tag) {
            // Name-based meta
            if (preg_match('/name=["\']([^"\']+)["\'].*content=["\']([^"\']*)["\']|content=["\']([^"\']*)["\'].*name=["\']([^"\']+)["\']/i', $tag, $m)) {
                $name = strtolower(($m[1] ?? '') ?: ($m[4] ?? ''));
                $content = ($m[2] ?? '') ?: ($m[3] ?? '');

                if ($name === 'description') $meta['description'] = $content;
                elseif ($name === 'keywords') $meta['keywords'] = $content;
                elseif ($name === 'robots') $meta['robots'] = $content;
            }

            // Property-based meta (Open Graph)
            if (preg_match('/property=["\']([^"\']+)["\'].*content=["\']([^"\']*)["\']|content=["\']([^"\']*)["\'].*property=["\']([^"\']+)["\']/i', $tag, $m)) {
                $property = strtolower(($m[1] ?? '') ?: ($m[4] ?? ''));
                $content = ($m[2] ?? '') ?: ($m[3] ?? '');

                if (str_starts_with($property, 'og:')) {
                    $meta['og'][substr($property, 3)] = $content;
                } elseif (str_starts_with($property, 'twitter:')) {
                    $meta['twitter'][substr($property, 8)] = $content;
                }
            }
        }

        // Canonical
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
            $meta['canonical'] = $matches[1];
        }

        return $meta;
    }

    public function extractHeadings(string $html): array
    {
        $headings = [];

        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $html, $matches);
            $headings["h{$i}"] = array_map(function ($h) {
                return trim(strip_tags($h));
            }, $matches[1]);
        }

        return $headings;
    }

    public function extractLinks(string $html, string $baseUrl): array
    {
        $links = [
            'internal' => [],
            'external' => [],
        ];

        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        preg_match_all('/<a[^>]+href=["\']([^"\'#]+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $href = $match[1];
            $text = trim(strip_tags($match[2]));

            // Normalizza URL relativo
            if (str_starts_with($href, '/')) {
                $href = parse_url($baseUrl, PHP_URL_SCHEME) . '://' . $baseDomain . $href;
            }

            if (!filter_var($href, FILTER_VALIDATE_URL)) {
                continue;
            }

            $linkDomain = parse_url($href, PHP_URL_HOST);

            $linkData = [
                'url' => $href,
                'text' => $text,
            ];

            if ($linkDomain === $baseDomain) {
                $links['internal'][] = $linkData;
            } else {
                $links['external'][] = $linkData;
            }
        }

        return $links;
    }

    public function extractImages(string $html): array
    {
        $images = [];

        preg_match_all('/<img[^>]+>/i', $html, $imgTags);

        foreach ($imgTags[0] as $tag) {
            $image = ['src' => '', 'alt' => '', 'title' => ''];

            if (preg_match('/src=["\']([^"\']+)["\']/i', $tag, $m)) {
                $image['src'] = $m[1];
            }
            if (preg_match('/alt=["\']([^"\']*?)["\']/i', $tag, $m)) {
                $image['alt'] = $m[1];
            }
            if (preg_match('/title=["\']([^"\']*?)["\']/i', $tag, $m)) {
                $image['title'] = $m[1];
            }

            if ($image['src']) {
                $images[] = $image;
            }
        }

        return $images;
    }

    /**
     * Fetch URL without consuming credits (for internal API calls)
     * Use this for WordPress API, SerpAPI, Google APIs, etc.
     */
    public function fetchRaw(string $url, array $options = []): array
    {
        $timeout = $options['timeout'] ?? 30;
        // Merge custom headers with defaults (custom headers override defaults)
        $headers = array_merge($this->defaultHeaders, $options['headers'] ?? []);
        $followRedirects = $options['follow_redirects'] ?? true;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlErrno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'Errore fetch: ' . $error, 'curl_errno' => $curlErrno];
        }

        return [
            'success' => true,
            'body' => $response,
            'http_code' => $httpCode,
            'final_url' => $finalUrl,
        ];
    }

    /**
     * POST request without consuming credits (for API integrations)
     */
    public function postRaw(string $url, $data, array $options = []): array
    {
        $timeout = $options['timeout'] ?? 30;
        // Merge with default headers to ensure User-Agent is always sent
        $defaultPostHeaders = array_merge($this->defaultHeaders, ['Content-Type: application/json']);
        $headers = array_merge($defaultPostHeaders, $options['headers'] ?? []);
        $isJson = $options['json'] ?? true;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $isJson ? json_encode($data) : $data,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'Errore POST: ' . $error, 'curl_errno' => $curlErrno];
        }

        return [
            'success' => true,
            'body' => $response,
            'http_code' => $httpCode,
        ];
    }

    /**
     * HEAD request to check URL status without downloading content
     */
    public function head(string $url, int $timeout = 10): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlErrno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => $error, 'curl_errno' => $curlErrno];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'final_url' => $finalUrl,
            'content_type' => $contentType,
        ];
    }

    /**
     * Fetch JSON from URL (convenience method)
     */
    public function fetchJson(string $url, array $options = []): array
    {
        $result = $this->fetchRaw($url, $options);

        if (isset($result['error'])) {
            return $result;
        }

        $decoded = json_decode($result['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => true, 'message' => 'Invalid JSON response'];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'http_code' => $result['http_code'],
        ];
    }

    /**
     * Post JSON and get JSON response (convenience method for APIs)
     */
    public function postJson(string $url, array $data, array $headers = []): array
    {
        $defaultHeaders = ['Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);

        $result = $this->postRaw($url, $data, ['headers' => $headers, 'json' => true]);

        if (isset($result['error'])) {
            return $result;
        }

        $decoded = json_decode($result['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => true,
                'message' => 'Invalid JSON response',
                'raw_body' => $result['body'],
                'http_code' => $result['http_code'],
            ];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'http_code' => $result['http_code'],
        ];
    }

    /**
     * Scrape a URL and extract structured content for AI analysis
     *
     * @param string $url URL to scrape
     * @return array Structured content with headings, text, word count
     */
    public function scrape(string $url): array
    {
        // Fetch raw HTML
        $result = $this->fetchRaw($url, ['timeout' => 30]);

        if (isset($result['error'])) {
            throw new \Exception($result['message'] ?? 'Scraping failed');
        }

        $html = $result['body'] ?? '';
        $finalUrl = $result['url'] ?? $url;

        if (empty($html)) {
            throw new \Exception('Empty response from URL');
        }

        // Extract meta
        $meta = $this->extractMeta($html);

        // Extract headings
        $headings = $this->extractHeadings($html);

        // Extract main content text
        $content = $this->extractMainContent($html);

        // Count words
        $wordCount = str_word_count(strip_tags($content));

        return [
            'url' => $finalUrl,
            'title' => $meta['title'] ?? '',
            'description' => $meta['description'] ?? '',
            'headings' => $headings,
            'content' => $content,
            'word_count' => $wordCount,
        ];
    }

    /**
     * Extract main content text from HTML
     */
    private function extractMainContent(string $html): string
    {
        // Remove scripts, styles, and comments
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Remove navigation, header, footer, sidebar
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $html);
        $html = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $html);
        $html = preg_replace('/<aside[^>]*>.*?<\/aside>/is', '', $html);

        // Try to find main content area
        $mainContent = '';

        // Look for article or main tags
        if (preg_match('/<article[^>]*>(.*?)<\/article>/is', $html, $matches)) {
            $mainContent = $matches[1];
        } elseif (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $matches)) {
            $mainContent = $matches[1];
        } elseif (preg_match('/<div[^>]*class=["\'][^"\']*(?:content|post|entry|article)[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $matches)) {
            $mainContent = $matches[1];
        } else {
            // Fallback: use body
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
                $mainContent = $matches[1];
            } else {
                $mainContent = $html;
            }
        }

        // Extract paragraphs and headings text
        $text = '';

        // Get heading text
        if (preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $mainContent, $matches)) {
            foreach ($matches[1] as $heading) {
                $text .= strip_tags($heading) . "\n\n";
            }
        }

        // Get paragraph text
        if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $mainContent, $matches)) {
            foreach ($matches[1] as $para) {
                $cleanPara = trim(strip_tags($para));
                if (strlen($cleanPara) > 20) {
                    $text .= $cleanPara . "\n\n";
                }
            }
        }

        // Get list items
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $mainContent, $matches)) {
            foreach ($matches[1] as $item) {
                $cleanItem = trim(strip_tags($item));
                if (strlen($cleanItem) > 10) {
                    $text .= "- " . $cleanItem . "\n";
                }
            }
        }

        // Clean up
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // If still empty, just strip all tags
        if (empty($text)) {
            $text = strip_tags($mainContent);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
        }

        return $text;
    }
}
