<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Core\Credits;
use Services\SitemapService;
use Services\ScraperService;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;

/**
 * CrawlerService
 *
 * Servizio per crawling pagine SEO audit
 * Usa ScraperService e SitemapService condivisi
 */
class CrawlerService
{
    private Project $projectModel;
    private Page $pageModel;
    private SitemapService $sitemapService;
    private ScraperService $scraperService;

    private int $projectId;
    private int $userId;
    private array $project;

    // Rate limiting
    private int $requestDelay = 200; // ms tra richieste (5 req/sec)
    private float $lastRequestTime = 0;

    // Crawl state
    private array $crawledUrls = [];
    private array $pendingUrls = [];
    private bool $shouldStop = false;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->sitemapService = new SitemapService();
        $this->scraperService = new ScraperService();
    }

    /**
     * Inizializza crawler per un progetto
     */
    public function init(int $projectId, int $userId): self
    {
        $this->projectId = $projectId;
        $this->userId = $userId;
        $this->project = $this->projectModel->find($projectId);

        if (!$this->project) {
            throw new \Exception('Progetto non trovato');
        }

        return $this;
    }

    /**
     * Scopri URL da scansionare
     */
    public function discoverUrls(): array
    {
        $baseUrl = $this->project['base_url'];
        $mode = $this->project['crawl_mode'];
        $maxPages = $this->project['max_pages'];

        $urls = [];

        // Scopri da sitemap
        if ($mode === 'sitemap' || $mode === 'both') {
            $sitemaps = $this->sitemapService->discoverFromRobotsTxt($baseUrl);

            if (!empty($sitemaps)) {
                $sitemapUrls = [];
                foreach ($sitemaps as $sitemap) {
                    $sitemapUrls[] = $sitemap['url'];
                }
                $this->sitemapService->setMaxUrls($maxPages);
                $urls = $this->sitemapService->parseMultiple($sitemapUrls);

                // Salva info sitemap
                $this->saveSiteConfig($sitemaps);
            }
        }

        // Spider da homepage (se mode spider o nessun URL da sitemap)
        if ($mode === 'spider' || ($mode === 'both' && empty($urls))) {
            $spideredUrls = $this->spiderFromHomepage($baseUrl, $maxPages - count($urls));
            $urls = array_unique(array_merge($urls, $spideredUrls));
        }

        // Applica limite
        $urls = array_slice($urls, 0, $maxPages);

        // Aggiorna conteggio
        $this->projectModel->update($this->projectId, [
            'pages_found' => count($urls),
            'status' => 'pending',
        ]);

        return $urls;
    }

    /**
     * Spider link dalla homepage
     */
    private function spiderFromHomepage(string $baseUrl, int $limit): array
    {
        $discovered = [$baseUrl];
        $toVisit = [$baseUrl];
        $visited = [];
        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        while (!empty($toVisit) && count($discovered) < $limit) {
            $url = array_shift($toVisit);

            if (isset($visited[$url])) {
                continue;
            }
            $visited[$url] = true;

            // Fetch senza consumare crediti per discovery
            $html = $this->fetchUrlRaw($url);
            if (!$html) {
                continue;
            }

            // Estrai link
            preg_match_all('/<a[^>]+href=["\']([^"\'#]+)["\'][^>]*>/i', $html, $matches);

            foreach ($matches[1] as $href) {
                $href = $this->normalizeUrl($href, $baseUrl);
                if (!$href) continue;

                $hrefDomain = parse_url($href, PHP_URL_HOST);
                if ($hrefDomain !== $baseDomain) continue;

                // Skip risorse
                if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|pdf|zip|xml)$/i', $href)) continue;

                if (!in_array($href, $discovered)) {
                    $discovered[] = $href;
                    $toVisit[] = $href;
                }

                if (count($discovered) >= $limit) break;
            }

            // Rate limit
            $this->rateLimit();
        }

        return $discovered;
    }

    /**
     * Crawl singola pagina ed estrai dati SEO
     */
    public function crawlPage(string $url): ?array
    {
        // Rate limiting
        $this->rateLimit();

        // Fetch pagina usando ScraperService (consuma crediti)
        $result = $this->scraperService->fetch($this->userId, $url, 'seo-audit');

        if (!empty($result['error'])) {
            return [
                'url' => $url,
                'status_code' => 0,
                'error' => $result['message'] ?? 'Errore fetch',
            ];
        }

        $html = $result['html'];
        $httpCode = $result['http_code'];
        $startTime = microtime(true);

        // Estrai dati SEO
        $data = $this->extractPageData($html, $url);
        $data['url'] = $url;
        $data['status_code'] = $httpCode;
        $data['load_time_ms'] = (int) ((microtime(true) - $startTime) * 1000);
        $data['content_length'] = strlen($html);
        $data['html_content'] = $html;

        return $data;
    }

    /**
     * Estrai tutti i dati SEO dalla pagina
     */
    private function extractPageData(string $html, string $url): array
    {
        $data = [];

        // Meta tags
        $meta = $this->scraperService->extractMeta($html);
        $data['title'] = $meta['title'] ?? null;
        $data['title_length'] = strlen($data['title'] ?? '');
        $data['meta_description'] = $meta['description'] ?? null;
        $data['meta_description_length'] = strlen($data['meta_description'] ?? '');
        $data['meta_robots'] = $meta['robots'] ?? null;
        $data['canonical_url'] = $meta['canonical'] ?? null;

        // OG tags
        $data['og_title'] = $meta['og']['title'] ?? null;
        $data['og_description'] = $meta['og']['description'] ?? null;
        $data['og_image'] = $meta['og']['image'] ?? null;

        // Headings
        $headings = $this->scraperService->extractHeadings($html);
        $data['h1_count'] = count($headings['h1'] ?? []);
        $data['h1_texts'] = json_encode($headings['h1'] ?? []);
        $data['h2_count'] = count($headings['h2'] ?? []);
        $data['h3_count'] = count($headings['h3'] ?? []);
        $data['h4_count'] = count($headings['h4'] ?? []);
        $data['h5_count'] = count($headings['h5'] ?? []);
        $data['h6_count'] = count($headings['h6'] ?? []);

        // Word count
        $textContent = strip_tags($html);
        $textContent = preg_replace('/\s+/', ' ', $textContent);
        $data['word_count'] = str_word_count($textContent);

        // Images
        $images = $this->scraperService->extractImages($html);
        $data['images_count'] = count($images);
        $data['images_without_alt'] = count(array_filter($images, fn($img) => empty($img['alt'])));
        $data['images_data'] = json_encode($images);

        // Links
        $links = $this->scraperService->extractLinks($html, $this->project['base_url']);
        $data['internal_links_count'] = count($links['internal'] ?? []);
        $data['external_links_count'] = count($links['external'] ?? []);
        $data['links_data'] = json_encode($links);

        // Nofollow links count
        preg_match_all('/<a[^>]+rel=["\'][^"\']*nofollow[^"\']*["\'][^>]*>/i', $html, $nofollowMatches);
        $data['nofollow_links_count'] = count($nofollowMatches[0]);

        // Schema markup
        $schemaTypes = $this->extractSchemaTypes($html);
        $data['has_schema'] = !empty($schemaTypes);
        $data['schema_types'] = json_encode($schemaTypes);

        // Hreflang
        $hreflangTags = $this->extractHreflang($html);
        $data['hreflang_tags'] = json_encode($hreflangTags);

        // Indexability
        $indexability = $this->checkIndexability($data);
        $data['is_indexable'] = $indexability['indexable'];
        $data['indexability_reason'] = $indexability['reason'];

        return $data;
    }

    /**
     * Estrai tipi Schema markup
     */
    private function extractSchemaTypes(string $html): array
    {
        $types = [];

        // JSON-LD
        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonLdMatches);
        foreach ($jsonLdMatches[1] as $jsonLd) {
            $decoded = json_decode($jsonLd, true);
            if (isset($decoded['@type'])) {
                $types[] = $decoded['@type'];
            } elseif (isset($decoded['@graph'])) {
                foreach ($decoded['@graph'] as $item) {
                    if (isset($item['@type'])) {
                        $types[] = $item['@type'];
                    }
                }
            }
        }

        // Microdata
        preg_match_all('/itemtype=["\']https?:\/\/schema\.org\/([^"\']+)["\']/i', $html, $microdataMatches);
        $types = array_merge($types, $microdataMatches[1] ?? []);

        return array_unique($types);
    }

    /**
     * Estrai hreflang tags
     */
    private function extractHreflang(string $html): array
    {
        $hreflang = [];

        preg_match_all('/<link[^>]+rel=["\']alternate["\'][^>]+hreflang=["\']([^"\']+)["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $hreflang[] = [
                'lang' => $match[1],
                'url' => $match[2],
            ];
        }

        return $hreflang;
    }

    /**
     * Verifica indicizzabilità
     */
    private function checkIndexability(array $data): array
    {
        $robots = strtolower($data['meta_robots'] ?? '');

        if (strpos($robots, 'noindex') !== false) {
            return ['indexable' => false, 'reason' => 'noindex in meta robots'];
        }

        if ($data['status_code'] >= 400) {
            return ['indexable' => false, 'reason' => 'HTTP error ' . $data['status_code']];
        }

        if ($data['status_code'] >= 300 && $data['status_code'] < 400) {
            return ['indexable' => false, 'reason' => 'Redirect ' . $data['status_code']];
        }

        return ['indexable' => true, 'reason' => null];
    }

    /**
     * Salva dati pagina nel database
     */
    public function savePage(array $data): int
    {
        return $this->pageModel->upsert($this->projectId, $data['url'], $data);
    }

    /**
     * Salva configurazione sito (sitemap, robots)
     */
    private function saveSiteConfig(array $sitemaps): void
    {
        $baseUrl = $this->project['base_url'];

        // Fetch robots.txt
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';
        $robotsTxt = $this->fetchUrlRaw($robotsUrl);

        $allows = [];
        $disallows = [];

        if ($robotsTxt) {
            preg_match_all('/^Allow:\s*(.+)$/mi', $robotsTxt, $allowMatches);
            preg_match_all('/^Disallow:\s*(.+)$/mi', $robotsTxt, $disallowMatches);
            $allows = array_map('trim', $allowMatches[1] ?? []);
            $disallows = array_map('trim', $disallowMatches[1] ?? []);
        }

        $isHttps = str_starts_with($baseUrl, 'https://');

        Database::execute("
            INSERT INTO sa_site_config (project_id, robots_txt, robots_allows, robots_disallows, sitemap_urls, has_sitemap, has_robots, is_https)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                robots_txt = VALUES(robots_txt),
                robots_allows = VALUES(robots_allows),
                robots_disallows = VALUES(robots_disallows),
                sitemap_urls = VALUES(sitemap_urls),
                has_sitemap = VALUES(has_sitemap),
                has_robots = VALUES(has_robots),
                is_https = VALUES(is_https)
        ", [
            $this->projectId,
            $robotsTxt ?: null,
            json_encode($allows),
            json_encode($disallows),
            json_encode(array_column($sitemaps, 'url')),
            !empty($sitemaps),
            (bool) $robotsTxt,
            $isHttps,
        ]);
    }

    /**
     * Fetch URL senza consumare crediti (per discovery) via shared ScraperService
     */
    private function fetchUrlRaw(string $url): ?string
    {
        $result = $this->scraperService->fetchRaw($url, [
            'timeout' => 15,
            'headers' => ['User-Agent: Mozilla/5.0 (compatible; SEOToolkit/1.0)'],
        ]);

        if (isset($result['error']) || $result['http_code'] !== 200) {
            return null;
        }

        return $result['body'];
    }

    /**
     * Normalizza URL relativo
     */
    private function normalizeUrl(string $href, string $baseUrl): ?string
    {
        $href = trim($href);

        if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:')) {
            return null;
        }

        // URL relativo
        if (str_starts_with($href, '/')) {
            $parsed = parse_url($baseUrl);
            return $parsed['scheme'] . '://' . $parsed['host'] . $href;
        }

        // URL relativo senza slash
        if (!preg_match('#^https?://#i', $href)) {
            return rtrim($baseUrl, '/') . '/' . $href;
        }

        return $href;
    }

    /**
     * Rate limiting
     */
    private function rateLimit(): void
    {
        $now = microtime(true) * 1000;
        $elapsed = $now - $this->lastRequestTime;

        if ($elapsed < $this->requestDelay) {
            usleep(($this->requestDelay - $elapsed) * 1000);
        }

        $this->lastRequestTime = microtime(true) * 1000;
    }

    /**
     * Imposta flag per fermare crawl
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Verifica se deve fermarsi
     */
    public function shouldStop(): bool
    {
        return $this->shouldStop;
    }

    /**
     * Aggiorna stato progetto
     */
    public function updateProjectStatus(string $status, ?int $pagesCrawled = null): void
    {
        $data = ['status' => $status];

        if ($pagesCrawled !== null) {
            $data['pages_crawled'] = $pagesCrawled;
        }

        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->projectModel->update($this->projectId, $data);
    }
}
