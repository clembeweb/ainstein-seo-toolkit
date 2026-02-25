<?php

namespace Modules\CrawlBudget\Services;

use Core\Database;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Models\SiteConfig;

/**
 * BudgetCrawlerService
 *
 * Crawler core per analisi crawl budget.
 * Segue redirect manualmente (no CURLOPT_FOLLOWLOCATION) per tracciare le chain.
 * Parsa robots.txt, sitemap, e HTML per estrarre dati rilevanti.
 */
class BudgetCrawlerService
{
    private Page $pageModel;
    private SiteConfig $siteConfigModel;

    private int $projectId;
    private int $sessionId;
    private string $domain;
    private string $baseUrl;
    private array $config;

    /** @var array Regole robots.txt parsed */
    private array $robotsRules = [];

    /** @var array URL trovate nelle sitemap */
    private array $sitemapUrls = [];

    /** @var string[] User-Agent per le richieste */
    private array $defaultHeaders = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: it-IT,it;q=0.9,en;q=0.7',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
    ];

    private const MAX_REDIRECT_HOPS = 10;
    private const TIMEOUT_PER_HOP = 10;
    private const MAX_SITEMAP_URLS = 10000;
    private const MAX_SITEMAP_DEPTH = 3;

    public function __construct()
    {
        $this->pageModel = new Page();
        $this->siteConfigModel = new SiteConfig();
    }

    /**
     * Inizializza crawler per un progetto
     */
    public function init(int $projectId, int $sessionId, string $domain, array $config = []): self
    {
        $this->projectId = $projectId;
        $this->sessionId = $sessionId;
        $this->domain = $this->extractDomain($domain);
        $this->baseUrl = $this->normalizeBaseUrl($domain);
        $this->config = array_merge([
            'max_pages' => 5000,
            'crawl_delay_ms' => 500,
            'respect_robots' => true,
            'timeout' => 10,
        ], $config);

        return $this;
    }

    // =========================================================================
    // ROBOTS.TXT
    // =========================================================================

    /**
     * Fetch e parse robots.txt, poi salva in cb_site_config
     */
    public function fetchRobotsAndSitemap(): void
    {
        $robotsUrl = $this->baseUrl . '/robots.txt';
        $robotsContent = $this->fetchRaw($robotsUrl);

        $rules = [];
        $sitemapUrls = [];

        if ($robotsContent !== null) {
            $rules = $this->parseRobotsTxt($robotsContent);

            // Estrai Sitemap directives
            if (preg_match_all('/^Sitemap:\s*(.+)$/mi', $robotsContent, $matches)) {
                $sitemapUrls = array_map('trim', $matches[1]);
            }
        }

        // Se nessuna sitemap in robots.txt, prova la posizione standard
        if (empty($sitemapUrls)) {
            $sitemapUrls = [$this->baseUrl . '/sitemap.xml'];
        }

        // Fetch e parse sitemaps
        $allPageUrls = [];
        foreach ($sitemapUrls as $sitemapUrl) {
            $parsed = $this->parseSitemap($sitemapUrl);
            $allPageUrls = array_merge($allPageUrls, $parsed);
            if (count($allPageUrls) >= self::MAX_SITEMAP_URLS) {
                $allPageUrls = array_slice($allPageUrls, 0, self::MAX_SITEMAP_URLS);
                break;
            }
        }

        $this->robotsRules = $rules;
        $this->sitemapUrls = array_unique($allPageUrls);

        // Salva in DB
        $this->siteConfigModel->upsert($this->projectId, [
            'robots_txt' => $robotsContent,
            'robots_rules' => $rules,
            'sitemaps' => $sitemapUrls,
            'sitemap_urls' => $this->sitemapUrls,
        ]);
    }

    /**
     * Parse robots.txt in regole strutturate
     */
    public function parseRobotsTxt(string $content): array
    {
        $rules = [];
        $currentAgent = null;
        $crawlDelay = null;

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);

            // Rimuovi commenti
            if (($pos = strpos($line, '#')) !== false) {
                $line = trim(substr($line, 0, $pos));
            }

            if (empty($line)) {
                continue;
            }

            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $m)) {
                $currentAgent = strtolower(trim($m[1]));
                if (!isset($rules[$currentAgent])) {
                    $rules[$currentAgent] = ['allow' => [], 'disallow' => []];
                }
            } elseif ($currentAgent !== null && preg_match('/^Allow:\s*(.+)$/i', $line, $m)) {
                $rules[$currentAgent]['allow'][] = trim($m[1]);
            } elseif ($currentAgent !== null && preg_match('/^Disallow:\s*(.+)$/i', $line, $m)) {
                $rules[$currentAgent]['disallow'][] = trim($m[1]);
            } elseif (preg_match('/^Crawl-delay:\s*(\d+)/i', $line, $m)) {
                $crawlDelay = (int) $m[1];
            }
        }

        if ($crawlDelay !== null) {
            $rules['_crawl_delay'] = $crawlDelay;
        }

        return $rules;
    }

    /**
     * Controlla se un URL e consentito da robots.txt
     */
    public function isUrlAllowed(string $url): bool
    {
        if (!$this->config['respect_robots'] || empty($this->robotsRules)) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $path .= '?' . $query;
        }

        // Controlla Googlebot first, poi *
        foreach (['googlebot', '*'] as $agent) {
            if (!isset($this->robotsRules[$agent])) {
                continue;
            }

            $agentRules = $this->robotsRules[$agent];

            // Allow ha priorita su Disallow per regole di stessa lunghezza
            // Ma prima check Disallow
            foreach ($agentRules['disallow'] as $rule) {
                if ($this->robotsRuleMatches($rule, $path)) {
                    // Verifica se c'e un Allow piu specifico
                    foreach ($agentRules['allow'] as $allowRule) {
                        if ($this->robotsRuleMatches($allowRule, $path) && strlen($allowRule) >= strlen($rule)) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Controlla se una regola robots.txt matcha un path
     */
    private function robotsRuleMatches(string $rule, string $path): bool
    {
        if ($rule === '' || $rule === '/') {
            return $rule === '/' ? true : false;
        }

        // Converti wildcards in regex
        $pattern = preg_quote($rule, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\$', '$', $pattern);

        return (bool) preg_match('#^' . $pattern . '#', $path);
    }

    // =========================================================================
    // SITEMAP
    // =========================================================================

    /**
     * Fetch e parse una sitemap (supporta sitemap index)
     */
    public function parseSitemap(string $url, int $depth = 0): array
    {
        if ($depth >= self::MAX_SITEMAP_DEPTH) {
            return [];
        }

        $content = $this->fetchRaw($url);
        if ($content === null) {
            return [];
        }

        $urls = [];

        // Rileva se e un sitemap index
        if (strpos($content, '<sitemapindex') !== false) {
            // Sitemap index: estrai URL delle sub-sitemap
            if (preg_match_all('/<sitemap>\s*<loc>([^<]+)<\/loc>/i', $content, $matches)) {
                foreach ($matches[1] as $subSitemapUrl) {
                    $subUrls = $this->parseSitemap(trim($subSitemapUrl), $depth + 1);
                    $urls = array_merge($urls, $subUrls);
                    if (count($urls) >= self::MAX_SITEMAP_URLS) {
                        break;
                    }
                }
            }
        } else {
            // Sitemap normale: estrai URL delle pagine
            if (preg_match_all('/<url>\s*<loc>([^<]+)<\/loc>/i', $content, $matches)) {
                foreach ($matches[1] as $pageUrl) {
                    $pageUrl = trim($pageUrl);
                    // Solo URL dello stesso dominio
                    if ($this->isSameDomain($pageUrl)) {
                        $urls[] = $pageUrl;
                    }
                }
            }
        }

        return $urls;
    }

    // =========================================================================
    // SEED URLs
    // =========================================================================

    /**
     * Inserisci URL seed (homepage + sitemap) in cb_pages come pending
     */
    public function seedUrls(): int
    {
        $seeded = 0;

        // Homepage sempre
        $this->pageModel->upsert($this->projectId, $this->sessionId, $this->baseUrl . '/', [
            'status' => 'pending',
            'depth' => 0,
            'in_sitemap' => in_array($this->baseUrl . '/', $this->sitemapUrls) ? 1 : 0,
        ]);
        $seeded++;

        // URL dalla sitemap
        $maxPages = (int) $this->config['max_pages'];
        foreach ($this->sitemapUrls as $url) {
            if ($seeded >= $maxPages) {
                break;
            }

            $normalizedUrl = rtrim($url, '/') . '/';
            if ($normalizedUrl === $this->baseUrl . '/') {
                continue; // Homepage gia inserita
            }

            $this->pageModel->upsert($this->projectId, $this->sessionId, $url, [
                'status' => 'pending',
                'depth' => 1,
                'in_sitemap' => 1,
            ]);
            $seeded++;
        }

        return $seeded;
    }

    // =========================================================================
    // CRAWL PAGE (core)
    // =========================================================================

    /**
     * Crawla una singola pagina con redirect chain tracing
     *
     * Segue redirect MANUALMENTE (no CURLOPT_FOLLOWLOCATION) per tracciare
     * ogni hop della catena.
     *
     * @return array Dati della pagina crawlata
     */
    public function crawlPage(string $url): array
    {
        $startTime = microtime(true);

        // Check robots.txt
        $robotsAllowed = $this->isUrlAllowed($url);

        // Segui redirect chain manualmente
        $chain = [];
        $currentUrl = $url;
        $hops = 0;
        $finalStatus = null;
        $finalHeaders = [];
        $finalBody = null;
        $contentType = null;
        $visitedUrls = [];
        $isLoop = false;

        while ($hops <= self::MAX_REDIRECT_HOPS) {
            // Loop detection
            if (in_array($currentUrl, $visitedUrls)) {
                $isLoop = true;
                $chain[] = $currentUrl . '|loop';
                break;
            }
            $visitedUrls[] = $currentUrl;

            $result = $this->curlFetchNoFollow($currentUrl);

            if ($result['error']) {
                return $this->buildPageResult($url, [
                    'http_status' => 0,
                    'error' => $result['error_message'],
                    'response_time_ms' => $this->elapsedMs($startTime),
                    'in_robots_allowed' => $robotsAllowed ? 1 : 0,
                    'redirect_chain' => !empty($chain) ? $chain : null,
                    'redirect_hops' => $hops,
                ]);
            }

            $httpStatus = $result['http_code'];
            $headers = $result['headers'];
            $body = $result['body'];
            $ct = $this->getHeaderValue($headers, 'Content-Type') ?? '';

            $chain[] = $currentUrl . '|' . $httpStatus;

            // Se e un redirect (3xx), segui
            if ($httpStatus >= 300 && $httpStatus < 400) {
                $location = $this->getHeaderValue($headers, 'Location');
                if ($location) {
                    $currentUrl = $this->resolveUrl($location, $currentUrl);
                    $hops++;
                    continue;
                }
            }

            // Destinazione finale (non redirect)
            $finalStatus = $httpStatus;
            $finalHeaders = $headers;
            $finalBody = $body;
            $contentType = $ct;
            break;
        }

        // Se abbiamo superato max hops
        if ($hops > self::MAX_REDIRECT_HOPS) {
            $isLoop = true;
        }

        $responseTimeMs = $this->elapsedMs($startTime);
        $redirectTarget = ($hops > 0) ? $currentUrl : null;

        // Parse HTML solo se content-type e text/html
        $isHtml = stripos($contentType ?? '', 'text/html') !== false;
        $title = null;
        $metaRobots = null;
        $canonicalUrl = null;
        $wordCount = null;
        $internalLinks = [];

        if ($isHtml && $finalBody) {
            $parsed = $this->parseHtml($finalBody, $redirectTarget ?? $url);
            $title = $parsed['title'];
            $metaRobots = $parsed['meta_robots'];
            $canonicalUrl = $parsed['canonical'];
            $wordCount = $parsed['word_count'];
            $internalLinks = $parsed['internal_links'];
        }

        // X-Robots-Tag header
        $xRobotsTag = $this->getHeaderValue($finalHeaders, 'X-Robots-Tag');
        if ($xRobotsTag && !$metaRobots) {
            $metaRobots = $xRobotsTag;
        } elseif ($xRobotsTag && $metaRobots) {
            $metaRobots .= ', ' . $xRobotsTag;
        }

        // Canonical dal Link header
        if (!$canonicalUrl) {
            $linkHeader = $this->getHeaderValue($finalHeaders, 'Link');
            if ($linkHeader && preg_match('/<([^>]+)>;\s*rel=["\']?canonical["\']?/i', $linkHeader, $m)) {
                $canonicalUrl = $m[1];
            }
        }

        // Determina indexability
        $effectiveUrl = $redirectTarget ?? $url;
        $isIndexable = true;
        $indexabilityReason = null;

        if ($metaRobots && preg_match('/noindex/i', $metaRobots)) {
            $isIndexable = false;
            $indexabilityReason = 'noindex in meta robots';
        } elseif (!$robotsAllowed) {
            $isIndexable = false;
            $indexabilityReason = 'bloccato da robots.txt';
        } elseif ($finalStatus && $finalStatus >= 400) {
            $isIndexable = false;
            $indexabilityReason = 'HTTP ' . $finalStatus;
        }

        // Canonical matches
        $canonicalMatches = true;
        if ($canonicalUrl !== null) {
            $canonicalMatches = (rtrim($canonicalUrl, '/') === rtrim($effectiveUrl, '/'));
        }

        // Has parameters
        $hasParameters = parse_url($url, PHP_URL_QUERY) !== null;

        return $this->buildPageResult($url, [
            'http_status' => $finalStatus ?? 0,
            'content_type' => $contentType,
            'response_time_ms' => $responseTimeMs,
            'content_length' => $finalBody ? strlen($finalBody) : 0,
            'word_count' => $wordCount,
            'title' => $title,
            'meta_robots' => $metaRobots,
            'canonical_url' => $canonicalUrl,
            'canonical_matches' => $canonicalMatches ? 1 : 0,
            'is_indexable' => $isIndexable ? 1 : 0,
            'indexability_reason' => $indexabilityReason,
            'redirect_target' => $redirectTarget,
            'redirect_chain' => !empty($chain) ? $chain : null,
            'redirect_hops' => $hops,
            'is_loop' => $isLoop,
            'in_robots_allowed' => $robotsAllowed ? 1 : 0,
            'has_parameters' => $hasParameters ? 1 : 0,
            'internal_links' => $internalLinks,
            'internal_links_out' => count($internalLinks),
        ]);
    }

    // =========================================================================
    // HTTP
    // =========================================================================

    /**
     * Fetch URL senza follow redirect (per tracciare chain)
     */
    private function curlFetchNoFollow(string $url): array
    {
        $ch = curl_init($url);

        $headerLines = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => (int) ($this->config['timeout'] ?? self::TIMEOUT_PER_HOP),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => array_merge($this->defaultHeaders, [
                'User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            ]),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$headerLines) {
                $headerLines[] = $header;
                return strlen($header);
            },
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'error_message' => $error, 'http_code' => 0, 'headers' => [], 'body' => null];
        }

        return [
            'error' => false,
            'http_code' => $httpCode,
            'headers' => $headerLines,
            'body' => $body,
        ];
    }

    /**
     * Fetch raw content di un URL (per robots.txt, sitemap)
     */
    private function fetchRaw(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $content) {
            return $content;
        }

        return null;
    }

    // =========================================================================
    // HTML PARSING
    // =========================================================================

    /**
     * Parse HTML per estrarre dati rilevanti al crawl budget
     */
    private function parseHtml(string $html, string $pageUrl): array
    {
        $title = null;
        $metaRobots = null;
        $canonical = null;
        $wordCount = 0;
        $internalLinks = [];

        // Title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            $title = mb_substr($title, 0, 512);
        }

        // Meta robots
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $metaRobots = trim($m[1]);
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']robots["\']/i', $html, $m)) {
            $metaRobots = trim($m[1]);
        }

        // Canonical
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
            $canonical = trim($m[1]);
        } elseif (preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']/i', $html, $m)) {
            $canonical = trim($m[1]);
        }

        // Word count (dal body, senza script/style)
        $textContent = $html;
        $textContent = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $textContent);
        $textContent = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $textContent);
        $textContent = strip_tags($textContent);
        $textContent = preg_replace('/\s+/', ' ', $textContent);
        $wordCount = str_word_count(trim($textContent));

        // Link interni
        $baseDomain = $this->domain;
        preg_match_all('/<a[^>]+href=["\']([^"\'#]+)["\']/i', $html, $linkMatches);

        $seen = [];
        foreach ($linkMatches[1] as $href) {
            $href = trim($href);

            // Salta javascript:, mailto:, tel:
            if (preg_match('/^(javascript|mailto|tel|data):/i', $href)) {
                continue;
            }

            // Risolvi URL relativi
            $resolved = $this->resolveUrl($href, $pageUrl);
            if (!$resolved) {
                continue;
            }

            // Solo stesso dominio
            $linkDomain = $this->extractDomain($resolved);
            if ($linkDomain !== $baseDomain) {
                continue;
            }

            // Rimuovi fragment
            $resolved = preg_replace('/#.*$/', '', $resolved);

            // Dedup
            if (isset($seen[$resolved])) {
                continue;
            }
            $seen[$resolved] = true;

            $internalLinks[] = $resolved;
        }

        return [
            'title' => $title,
            'meta_robots' => $metaRobots,
            'canonical' => $canonical,
            'word_count' => $wordCount,
            'internal_links' => $internalLinks,
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Costruisci array risultato pagina
     */
    private function buildPageResult(string $originalUrl, array $data): array
    {
        $data['url'] = $originalUrl;

        // Encode redirect chain per storage JSON
        if (isset($data['redirect_chain']) && is_array($data['redirect_chain'])) {
            $data['redirect_chain'] = json_encode($data['redirect_chain']);
        }

        return $data;
    }

    /**
     * Estrai valore di un header dalla lista di header lines
     */
    private function getHeaderValue(array $headerLines, string $name): ?string
    {
        $nameLower = strtolower($name);
        foreach ($headerLines as $line) {
            if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.+)/i', trim($line), $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    /**
     * Risolvi URL relativo rispetto a una base
     */
    private function resolveUrl(string $href, string $baseUrl): ?string
    {
        // Gia assoluto
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        // Protocol-relative
        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';
            return $scheme . ':' . $href;
        }

        $parsed = parse_url($baseUrl);
        if (!$parsed || !isset($parsed['host'])) {
            return null;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        // Root-relative
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $port . $href;
        }

        // Relative to current path
        $basePath = $parsed['path'] ?? '/';
        $baseDir = substr($basePath, 0, strrpos($basePath, '/') + 1);

        return $scheme . '://' . $host . $port . $baseDir . $href;
    }

    /**
     * Estrai dominio da URL
     */
    private function extractDomain(string $url): string
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return strtolower(parse_url($url, PHP_URL_HOST) ?? $url);
    }

    /**
     * Normalizza URL base (con scheme, senza trailing slash)
     */
    private function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);
        $url = rtrim($url, '/');
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    /**
     * Controlla se URL e dello stesso dominio
     */
    private function isSameDomain(string $url): bool
    {
        return $this->extractDomain($url) === $this->domain;
    }

    /**
     * Millisecondi trascorsi
     */
    private function elapsedMs(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }

    /**
     * Getter per le URL trovate nella sitemap
     */
    public function getSitemapUrls(): array
    {
        return $this->sitemapUrls;
    }

    /**
     * Getter per le regole robots
     */
    public function getRobotsRules(): array
    {
        return $this->robotsRules;
    }

    /**
     * Carica regole robots dal DB (per ripresa sessione)
     */
    public function loadRobotsFromDb(): void
    {
        $this->robotsRules = $this->siteConfigModel->getRobotsRules($this->projectId);
        $this->sitemapUrls = $this->siteConfigModel->getSitemapUrls($this->projectId);
    }
}
