<?php

namespace Services;

use Core\Credits;

class ScraperService
{
    private array $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Cache-Control: max-age=0',
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
            CURLOPT_ENCODING => '',  // Auto-decompress gzip/deflate/br
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
        $followRedirects = $options['follow_redirects'] ?? true;

        // For API calls, skip default browser headers that may trigger WAF
        if ($options['api_mode'] ?? false) {
            $headers = $options['headers'] ?? [];
        } else {
            // Smart merge: custom headers override defaults by header name
            $headers = $this->mergeHeaders($this->defaultHeaders, $options['headers'] ?? []);
        }

        $ch = curl_init($url);

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',  // Auto-decompress gzip/deflate/br
        ];

        // SSL verification: enable by default for API calls, disable only if explicitly requested
        if ($options['skip_ssl_verify'] ?? false) {
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        curl_setopt_array($ch, $curlOptions);

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
        $isJson = $options['json'] ?? true;

        // For API calls, skip default browser headers that may trigger WAF
        if ($options['api_mode'] ?? false) {
            $headers = array_merge(['Content-Type: application/json'], $options['headers'] ?? []);
        } else {
            // Smart merge: custom headers override defaults by header name
            $defaultPostHeaders = array_merge($this->defaultHeaders, ['Content-Type: application/json']);
            $headers = $this->mergeHeaders($defaultPostHeaders, $options['headers'] ?? []);
        }

        $ch = curl_init($url);

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $isJson ? json_encode($data) : $data,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
        ];

        // SSL verification: enable by default, disable only if explicitly requested
        if ($options['skip_ssl_verify'] ?? false) {
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        curl_setopt_array($ch, $curlOptions);

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
     * Merge headers intelligently - custom headers override defaults by header name
     * e.g. 'Accept: application/json' will replace 'Accept: text/html,...'
     */
    private function mergeHeaders(array $defaults, array $custom): array
    {
        // Parse default headers into name => full_header map
        $headerMap = [];
        foreach ($defaults as $header) {
            $colonPos = strpos($header, ':');
            if ($colonPos !== false) {
                $name = strtolower(trim(substr($header, 0, $colonPos)));
                $headerMap[$name] = $header;
            } else {
                $headerMap[$header] = $header;
            }
        }

        // Override with custom headers
        foreach ($custom as $header) {
            $colonPos = strpos($header, ':');
            if ($colonPos !== false) {
                $name = strtolower(trim(substr($header, 0, $colonPos)));
                $headerMap[$name] = $header;
            } else {
                $headerMap[$header] = $header;
            }
        }

        return array_values($headerMap);
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
    public function postJson(string $url, array $data, array $headers = [], array $options = []): array
    {
        $defaultHeaders = ['Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);

        $postOptions = array_merge($options, ['headers' => $headers, 'json' => true]);
        $result = $this->postRaw($url, $data, $postOptions);

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

        // Check HTTP status code
        $httpCode = $result['http_code'] ?? 0;
        if ($httpCode >= 400) {
            throw new \Exception("HTTP Error {$httpCode}: impossibile accedere alla pagina");
        }

        $html = $result['body'] ?? '';
        $finalUrl = $result['final_url'] ?? $url;

        if (empty($html)) {
            throw new \Exception('Empty response from URL');
        }

        // Ensure HTML is valid UTF-8 (external sites may use different encodings)
        $html = $this->ensureUtf8($html);

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
        $text = $this->sanitizeUtf8($text);
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

    /**
     * Ensure HTML content is valid UTF-8
     * Handles common encodings from web pages
     */
    private function ensureUtf8(string $html): string
    {
        // Try to detect encoding from HTML meta tags
        $encoding = null;

        // Check for <meta charset="...">
        if (preg_match('/<meta[^>]+charset=["\']?([^"\'\s>]+)/i', $html, $matches)) {
            $encoding = strtoupper(trim($matches[1]));
        }
        // Check for <meta http-equiv="Content-Type" content="...; charset=...">
        elseif (preg_match('/<meta[^>]+content=["\'][^"\']*charset=([^"\'\s;]+)/i', $html, $matches)) {
            $encoding = strtoupper(trim($matches[1]));
        }

        // If encoding is already UTF-8, just sanitize
        if ($encoding === 'UTF-8' || $encoding === 'UTF8') {
            return $this->sanitizeUtf8($html);
        }

        // If we found an encoding, try to convert
        if ($encoding) {
            $converted = @mb_convert_encoding($html, 'UTF-8', $encoding);
            if ($converted !== false) {
                return $this->sanitizeUtf8($converted);
            }
        }

        // Try auto-detection
        if (!mb_check_encoding($html, 'UTF-8')) {
            $detected = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ISO-8859-15', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $converted = @mb_convert_encoding($html, 'UTF-8', $detected);
                if ($converted !== false) {
                    return $this->sanitizeUtf8($converted);
                }
            }
        }

        // Fallback: sanitize as-is
        return $this->sanitizeUtf8($html);
    }

    /**
     * Sanitize string for valid UTF-8 encoding
     * Removes invalid UTF-8 sequences
     */
    private function sanitizeUtf8(string $text): string
    {
        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Remove invalid UTF-8 sequences (control characters except tabs, newlines, carriage returns)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Use iconv to remove any remaining invalid sequences
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return $clean !== false ? $clean : $text;
    }
}
