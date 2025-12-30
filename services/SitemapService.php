<?php

namespace Services;

/**
 * Sitemap Service
 *
 * Reusable service for discovering and parsing XML sitemaps.
 * Can be used by any module that needs sitemap import functionality.
 */
class SitemapService
{
    protected int $timeout = 30;
    protected int $maxUrls = 10000;
    protected array $errors = [];
    protected array $commonSitemapPaths = [
        '/sitemap.xml',
        '/sitemap_index.xml',
        '/sitemap-index.xml',
        '/sitemaps/sitemap.xml',
        '/wp-sitemap.xml',
    ];

    /**
     * Set request timeout
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set maximum URLs to parse
     */
    public function setMaxUrls(int $maxUrls): self
    {
        $this->maxUrls = $maxUrls;
        return $this;
    }

    /**
     * Get errors from last operation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Discover sitemaps from robots.txt
     */
    public function discoverFromRobotsTxt(string $baseUrl, bool $getCounts = false): array
    {
        $this->errors = [];
        $sitemaps = [];

        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';

        try {
            $content = $this->fetchUrl($robotsUrl);

            if ($content === false) {
                // Try common sitemap paths as fallback
                return $this->discoverCommonPaths($baseUrl, $getCounts);
            }

            // Parse robots.txt for Sitemap directives
            preg_match_all('/^Sitemap:\s*(.+)$/mi', $content, $matches);

            foreach ($matches[1] ?? [] as $sitemapUrl) {
                $sitemapUrl = trim($sitemapUrl);
                if (!empty($sitemapUrl)) {
                    $sitemap = [
                        'url' => $sitemapUrl,
                        'source' => 'robots.txt',
                        'url_count' => null,
                    ];

                    if ($getCounts) {
                        $sitemap['url_count'] = $this->getSitemapUrlCount($sitemapUrl);
                    }

                    $sitemaps[] = $sitemap;
                }
            }

            // If no sitemaps found in robots.txt, try common paths
            if (empty($sitemaps)) {
                $sitemaps = $this->discoverCommonPaths($baseUrl, $getCounts);
            }
        } catch (\Exception $e) {
            $this->errors[] = 'Error fetching robots.txt: ' . $e->getMessage();
            // Try common paths as fallback
            $sitemaps = $this->discoverCommonPaths($baseUrl, $getCounts);
        }

        return $sitemaps;
    }

    /**
     * Discover sitemaps from common paths
     */
    protected function discoverCommonPaths(string $baseUrl, bool $getCounts = false): array
    {
        $sitemaps = [];
        $baseUrl = rtrim($baseUrl, '/');

        foreach ($this->commonSitemapPaths as $path) {
            $url = $baseUrl . $path;
            if ($this->sitemapExists($url)) {
                $sitemap = [
                    'url' => $url,
                    'source' => 'common_path',
                    'url_count' => null,
                ];

                if ($getCounts) {
                    $sitemap['url_count'] = $this->getSitemapUrlCount($url);
                }

                $sitemaps[] = $sitemap;
            }
        }

        return $sitemaps;
    }

    /**
     * Check if sitemap URL exists
     */
    protected function sitemapExists(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Get approximate URL count from sitemap
     */
    protected function getSitemapUrlCount(string $url): ?int
    {
        $content = $this->fetchUrl($url);
        if ($content === false) {
            return null;
        }

        // Check if it's a sitemap index
        if (strpos($content, '<sitemapindex') !== false) {
            // Count child sitemaps and estimate
            preg_match_all('/<sitemap>/i', $content, $matches);
            return count($matches[0]) * 1000; // Rough estimate
        }

        // Count URLs
        preg_match_all('/<url>/i', $content, $matches);
        return count($matches[0]);
    }

    /**
     * Parse sitemap XML and extract URLs
     */
    public function parse(string $sitemapUrl, ?string $filter = null): array
    {
        $this->errors = [];
        $urls = [];

        $content = $this->fetchUrl($sitemapUrl);
        if ($content === false) {
            $this->errors[] = 'Failed to fetch sitemap: ' . $sitemapUrl;
            return [];
        }

        // Check if it's a sitemap index
        if (strpos($content, '<sitemapindex') !== false) {
            $urls = $this->parseSitemapIndex($content, $filter);
        } else {
            $urls = $this->parseSitemapUrls($content, $filter);
        }

        // Apply max limit
        if (count($urls) > $this->maxUrls) {
            $urls = array_slice($urls, 0, $this->maxUrls);
        }

        return $urls;
    }

    /**
     * Parse sitemap index (recursive)
     */
    protected function parseSitemapIndex(string $content, ?string $filter = null): array
    {
        $urls = [];

        // Use SimpleXML with error suppression for malformed XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            // Fallback to regex
            preg_match_all('/<loc>([^<]+)<\/loc>/i', $content, $matches);
            $childSitemaps = $matches[1] ?? [];
        } else {
            $childSitemaps = [];
            foreach ($xml->sitemap as $sitemap) {
                $childSitemaps[] = (string) $sitemap->loc;
            }
        }

        // Parse each child sitemap
        foreach ($childSitemaps as $childUrl) {
            if (count($urls) >= $this->maxUrls) break;

            $childUrls = $this->parse($childUrl, $filter);
            $urls = array_merge($urls, $childUrls);
        }

        return $urls;
    }

    /**
     * Parse URL entries from sitemap
     */
    protected function parseSitemapUrls(string $content, ?string $filter = null): array
    {
        $urls = [];

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            // Fallback to regex for malformed XML
            preg_match_all('/<loc>([^<]+)<\/loc>/i', $content, $matches);
            $extractedUrls = $matches[1] ?? [];
        } else {
            $extractedUrls = [];
            foreach ($xml->url as $url) {
                $extractedUrls[] = (string) $url->loc;
            }
        }

        foreach ($extractedUrls as $url) {
            $url = trim($url);
            if (empty($url)) continue;

            // Apply filter
            if ($filter && !$this->matchesFilter($url, $filter)) {
                continue;
            }

            $urls[] = $url;
        }

        return $urls;
    }

    /**
     * Parse multiple sitemaps and merge URLs
     */
    public function parseMultiple(array $sitemapUrls, ?string $filter = null): array
    {
        $allUrls = [];
        $seen = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            if (count($allUrls) >= $this->maxUrls) break;

            $urls = $this->parse($sitemapUrl, $filter);

            foreach ($urls as $url) {
                $normalized = strtolower(rtrim($url, '/'));
                if (isset($seen[$normalized])) continue;
                $seen[$normalized] = true;
                $allUrls[] = $url;

                if (count($allUrls) >= $this->maxUrls) break;
            }
        }

        return $allUrls;
    }

    /**
     * Check if URL matches filter pattern
     */
    public function matchesFilter(string $url, string $filter): bool
    {
        if (empty($filter)) return true;

        // Convert wildcard pattern to regex
        $pattern = str_replace(
            ['*', '?'],
            ['.*', '.'],
            preg_quote($filter, '#')
        );

        return (bool) preg_match('#' . $pattern . '#i', $url);
    }

    /**
     * Filter URLs by pattern
     */
    public function filterUrls(array $urls, string $filter): array
    {
        if (empty($filter)) return $urls;

        return array_values(array_filter($urls, fn($url) => $this->matchesFilter($url, $filter)));
    }

    /**
     * Fetch URL content
     */
    protected function fetchUrl(string $url): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SEOToolkit/1.0; +https://example.com/bot)',
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml, text/xml, */*',
                'Accept-Encoding: gzip, deflate',
            ],
            CURLOPT_ENCODING => '',
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->errors[] = "CURL error for {$url}: {$error}";
            return false;
        }

        if ($httpCode !== 200) {
            $this->errors[] = "HTTP {$httpCode} for {$url}";
            return false;
        }

        return $content;
    }

    /**
     * Preview URLs from sitemap (limited)
     */
    public function preview(string $sitemapUrl, int $limit = 50): array
    {
        $originalMax = $this->maxUrls;
        $this->maxUrls = $limit;

        $urls = $this->parse($sitemapUrl);

        $this->maxUrls = $originalMax;

        return [
            'urls' => $urls,
            'total' => count($urls),
            'preview_urls' => array_slice($urls, 0, $limit),
            'total_found' => count($urls),
        ];
    }

    /**
     * Preview multiple sitemaps
     */
    public function previewMultiple(array $sitemapUrls, ?string $filter = null, int $maxUrls = 10000): array
    {
        $this->maxUrls = $maxUrls;

        $allUrls = [];
        $seen = [];
        $duplicates = 0;

        foreach ($sitemapUrls as $sitemapUrl) {
            $urls = $this->parse($sitemapUrl, $filter);

            foreach ($urls as $url) {
                $normalized = strtolower(rtrim($url, '/'));
                if (isset($seen[$normalized])) {
                    $duplicates++;
                    continue;
                }
                $seen[$normalized] = true;
                $allUrls[] = $url;

                if (count($allUrls) >= $maxUrls) break;
            }

            if (count($allUrls) >= $maxUrls) break;
        }

        return [
            'urls' => $allUrls,
            'total' => count($allUrls),
            'total_unique' => count($allUrls),
            'duplicates_removed' => $duplicates,
        ];
    }
}
