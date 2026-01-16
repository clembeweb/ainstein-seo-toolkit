<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Core\Credits;
use Services\SitemapService;
use Services\ScraperService;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\CrawlSession;

/**
 * CrawlerService
 *
 * Servizio per crawling pagine SEO audit
 * Usa ScraperService e SitemapService condivisi
 * Supporta stop da DB via CrawlSession
 */
class CrawlerService
{
    private Project $projectModel;
    private Page $pageModel;
    private CrawlSession $sessionModel;
    private SitemapService $sitemapService;
    private ScraperService $scraperService;

    private int $projectId;
    private int $userId;
    private array $project;
    private ?int $sessionId = null;

    // Configurazione spider (configurabile via setConfig)
    private int $requestDelay = 200;      // ms tra richieste
    private int $timeout = 20;            // timeout HTTP in secondi
    private int $maxDepth = 5;            // profondità massima link
    private int $maxRetries = 2;          // tentativi su errore
    private string $userAgent = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    private bool $followRedirects = true;
    private bool $respectRobots = true;

    // Rate limiting
    private float $lastRequestTime = 0;

    // Crawl state
    private array $crawledUrls = [];
    private array $pendingUrls = [];
    private bool $shouldStopFlag = false;

    // Check interval per stop da DB (ogni N pagine)
    private int $stopCheckInterval = 5;
    private int $pagesSinceLastCheck = 0;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->sessionModel = new CrawlSession();
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
     * Imposta ID sessione per tracking
     */
    public function setSessionId(int $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * Ottieni ID sessione corrente
     */
    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    /**
     * Configura parametri spider
     * @param array $config Configurazione:
     *   - request_delay: ms tra richieste (0-5000)
     *   - timeout: timeout HTTP in secondi (5-60)
     *   - max_depth: profondità massima link (1-10)
     *   - max_retries: tentativi su errore (0-5)
     *   - user_agent: stringa User-Agent o preset
     *   - follow_redirects: segui redirect (bool)
     *   - respect_robots: rispetta robots.txt (bool)
     */
    public function setConfig(array $config): self
    {
        // Request delay (0-5000ms)
        if (isset($config['request_delay'])) {
            $this->requestDelay = max(0, min((int) $config['request_delay'], 5000));
        }

        // Timeout (5-60 secondi)
        if (isset($config['timeout'])) {
            $this->timeout = max(5, min((int) $config['timeout'], 60));
        }

        // Max depth (1-10)
        if (isset($config['max_depth'])) {
            $this->maxDepth = max(1, min((int) $config['max_depth'], 10));
        }

        // Max retries (0-5)
        if (isset($config['max_retries'])) {
            $this->maxRetries = max(0, min((int) $config['max_retries'], 5));
        }

        // User-Agent (preset o custom)
        if (isset($config['user_agent'])) {
            $userAgentPresets = [
                'default' => 'SEOToolkit Spider/1.0',
                'googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'googlebot-mobile' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];
            $ua = $config['user_agent'];
            $this->userAgent = $userAgentPresets[$ua] ?? $ua;
        }

        // Follow redirects
        if (isset($config['follow_redirects'])) {
            $this->followRedirects = (bool) $config['follow_redirects'];
        }

        // Respect robots.txt
        if (isset($config['respect_robots'])) {
            $this->respectRobots = (bool) $config['respect_robots'];
        }

        return $this;
    }

    /**
     * Ottieni configurazione corrente
     */
    public function getConfig(): array
    {
        return [
            'request_delay' => $this->requestDelay,
            'timeout' => $this->timeout,
            'max_depth' => $this->maxDepth,
            'max_retries' => $this->maxRetries,
            'user_agent' => $this->userAgent,
            'follow_redirects' => $this->followRedirects,
            'respect_robots' => $this->respectRobots,
        ];
    }

    /**
     * Scopri URL da scansionare
     * Prima controlla se ci sono URL già importate (da import page)
     * Altrimenti usa spider ricorsivo dalla homepage
     */
    public function discoverUrls(): array
    {
        // Prima controlla se ci sono URL già importate (in sa_site_config)
        $existingUrls = $this->getDiscoveredUrls();
        if (!empty($existingUrls)) {
            // Usa URL già importate
            $maxPages = $this->project['max_pages'] ?? 2000;
            $urls = array_slice($existingUrls, 0, $maxPages);

            // SEMPRE aggiorna pages_found per nuovo crawl
            $this->projectModel->update($this->projectId, [
                'pages_found' => count($urls),
            ]);

            return $urls;
        }

        // Nessun URL pre-importato: spider ricorsivo dalla homepage
        $baseUrl = $this->project['base_url'];
        $maxPages = $this->project['max_pages'] ?? 500;

        // Spider dalla homepage
        $urls = $this->spiderFromHomepage($baseUrl, $maxPages);

        // Salva info site config (robots.txt)
        $this->saveSiteConfigForSpider();

        // Applica limite
        $urls = array_slice($urls, 0, $maxPages);

        // Aggiorna conteggio (status resta 'crawling' - impostato da CrawlController)
        $this->projectModel->update($this->projectId, [
            'pages_found' => count($urls),
        ]);

        // Salva URL scoperti per crawlBatch
        $this->saveDiscoveredUrls($urls);

        return $urls;
    }

    /**
     * Salva URL scoperti per uso successivo in crawlBatch
     */
    private function saveDiscoveredUrls(array $urls): void
    {
        Database::execute("
            INSERT INTO sa_site_config (project_id, discovered_urls)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE discovered_urls = VALUES(discovered_urls)
        ", [
            $this->projectId,
            json_encode($urls),
        ]);
    }

    /**
     * Ottieni URL scoperti da DB (pending pages from sa_pages)
     */
    public function getDiscoveredUrls(): array
    {
        // Prima prova da sa_pages (nuovo sistema con status)
        $pendingPages = Database::fetchAll(
            "SELECT url FROM sa_pages WHERE project_id = ? AND status = 'pending' ORDER BY id",
            [$this->projectId]
        );

        if (!empty($pendingPages)) {
            return array_column($pendingPages, 'url');
        }

        // Fallback: leggi da sa_site_config per backward compatibility
        $config = Database::fetch(
            "SELECT discovered_urls FROM sa_site_config WHERE project_id = ?",
            [$this->projectId]
        );

        if (!$config || empty($config['discovered_urls'])) {
            return [];
        }

        return json_decode($config['discovered_urls'], true) ?: [];
    }

    /**
     * Spider ricorsivo dalla homepage - segue tutti i link interni
     * Funziona come Googlebot: scarica, estrae link, continua
     * Gestisce redirect (es. bewebagency.com -> www.bewebagency.com)
     * Rispetta maxDepth per limitare la profondità di esplorazione
     */
    private function spiderFromHomepage(string $baseUrl, int $limit): array
    {
        // Prima fai un fetch per ottenere l'URL finale (dopo redirect)
        $actualBaseUrl = $this->resolveRedirects($baseUrl);
        $actualBaseUrl = $this->normalizeUrl($actualBaseUrl, $actualBaseUrl) ?? $actualBaseUrl;

        $discovered = [$actualBaseUrl];
        // toVisit ora contiene [url, depth]
        $toVisit = [[$actualBaseUrl, 0]];
        $visited = [];

        // Usa il dominio effettivo (dopo redirect)
        $baseDomain = strtolower(parse_url($actualBaseUrl, PHP_URL_HOST));

        // Accetta anche varianti www/non-www dello stesso dominio
        $domainVariants = $this->getDomainVariants($baseDomain);

        $failedCount = 0;
        $maxFailures = 10;

        while (!empty($toVisit) && count($discovered) < $limit) {
            [$currentUrl, $currentDepth] = array_shift($toVisit);

            // Skip se già visitato
            if (isset($visited[$currentUrl])) {
                continue;
            }
            $visited[$currentUrl] = true;

            // Skip se superato maxDepth (ma mantieni URL già scoperto)
            if ($currentDepth >= $this->maxDepth) {
                continue;
            }

            // Rate limit
            $this->rateLimit();

            // Fetch pagina
            $html = $this->fetchUrlRaw($currentUrl);
            if (!$html) {
                $failedCount++;
                if ($failedCount >= $maxFailures) {
                    break;
                }
                continue;
            }
            $failedCount = 0;

            // Estrai tutti i link
            $links = $this->extractAllLinks($html, $currentUrl);

            foreach ($links as $href) {
                // Normalizza URL usando la pagina corrente come contesto
                $normalizedUrl = $this->normalizeUrl($href, $actualBaseUrl, $currentUrl);
                if (!$normalizedUrl) continue;

                // Verifica stesso dominio (accetta varianti www/non-www)
                $hrefDomain = strtolower(parse_url($normalizedUrl, PHP_URL_HOST) ?? '');
                if (!in_array($hrefDomain, $domainVariants)) continue;

                // Skip risorse statiche
                if ($this->isStaticResource($normalizedUrl)) continue;

                // Skip se già scoperto
                if (in_array($normalizedUrl, $discovered)) continue;

                // Aggiungi alla coda con depth+1
                $discovered[] = $normalizedUrl;
                $toVisit[] = [$normalizedUrl, $currentDepth + 1];

                if (count($discovered) >= $limit) break;
            }
        }

        return $discovered;
    }

    /**
     * Risolvi redirect e ottieni URL finale
     */
    private function resolveRedirects(string $url): string
    {
        $result = $this->scraperService->fetchRaw($url, [
            'timeout' => $this->timeout,
            'follow_redirects' => $this->followRedirects,
            'skip_ssl_verify' => true,
            'headers' => [
                'User-Agent: ' . $this->userAgent,
            ],
        ]);

        // Ritorna l'URL finale dopo i redirect
        return $result['final_url'] ?? $url;
    }

    /**
     * Ottieni varianti dominio (con e senza www)
     */
    private function getDomainVariants(string $domain): array
    {
        $domain = strtolower($domain);
        $variants = [$domain];

        if (str_starts_with($domain, 'www.')) {
            // Aggiungi versione senza www
            $variants[] = substr($domain, 4);
        } else {
            // Aggiungi versione con www
            $variants[] = 'www.' . $domain;
        }

        return $variants;
    }

    /**
     * Estrai tutti i link da HTML - regex robusta
     */
    private function extractAllLinks(string $html, string $currentUrl): array
    {
        $links = [];

        // Pattern 1: href con quotes (singole o doppie)
        // Cattura: href="url", href='url', href = "url"
        preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>/is', $html, $matches1);
        $links = array_merge($links, $matches1[1] ?? []);

        // Pattern 2: href senza quotes (raro ma possibile)
        // Cattura: href=url (fino a spazio o >)
        preg_match_all('/<a\s[^>]*href\s*=\s*([^\s"\'>]+)[^>]*>/is', $html, $matches2);
        $links = array_merge($links, $matches2[1] ?? []);

        // Pulisci e deduplicata
        $links = array_map('trim', $links);
        $links = array_filter($links, fn($l) => !empty($l) && $l !== '#');
        $links = array_unique($links);

        return array_values($links);
    }

    /**
     * Verifica se URL è risorsa statica da escludere
     */
    private function isStaticResource(string $url): bool
    {
        // Estensioni file statici da ignorare
        $staticExtensions = [
            'css', 'js', 'map',
            'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'tiff',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', 'tar', 'gz', '7z',
            'xml', 'json', 'rss', 'atom',
            'woff', 'woff2', 'ttf', 'eot', 'otf',
            'mp3', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm',
            'txt', 'log', 'csv',
        ];

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, $staticExtensions);
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

        // Sanitizza HTML per UTF-8 (evita errori MySQL "Incorrect string value")
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $html);
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
        $data['has_schema'] = (int) !empty($schemaTypes);
        $data['schema_types'] = json_encode($schemaTypes);

        // Hreflang
        $hreflangTags = $this->extractHreflang($html);
        $data['hreflang_tags'] = json_encode($hreflangTags);

        // Indexability
        $indexability = $this->checkIndexability($data);
        $data['is_indexable'] = (int) $indexability['indexable'];
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

        $statusCode = $data['status_code'] ?? 200;

        if ($statusCode >= 400) {
            return ['indexable' => false, 'reason' => 'HTTP error ' . $statusCode];
        }

        if ($statusCode >= 300 && $statusCode < 400) {
            return ['indexable' => false, 'reason' => 'Redirect ' . $statusCode];
        }

        return ['indexable' => true, 'reason' => null];
    }

    /**
     * Salva dati pagina nel database
     * Normalizza URL per evitare duplicati (trailing slash)
     */
    public function savePage(array $data): int
    {
        // Aggiungi session_id per tracking storico
        if ($this->sessionId) {
            $data['session_id'] = $this->sessionId;
        }

        // Normalizza URL prima di salvare (evita duplicati con/senza trailing slash)
        $normalizedUrl = $this->normalizeUrlForStorage($data['url']);
        $data['url'] = $normalizedUrl;

        return $this->pageModel->upsert($this->projectId, $normalizedUrl, $data);
    }

    /**
     * Normalizza URL per storage (rimuove trailing slash, lowercase domain)
     */
    public function normalizeUrlForStorage(string $url): string
    {
        $parsed = parse_url($url);

        // Ricostruisci URL normalizzato
        $scheme = $parsed['scheme'] ?? 'https';
        $host = strtolower($parsed['host'] ?? '');
        $path = $parsed['path'] ?? '/';

        // Rimuovi trailing slash (eccetto root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * Salva configurazione sito per spider (solo robots.txt)
     */
    private function saveSiteConfigForSpider(): void
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
            INSERT INTO sa_site_config (project_id, robots_txt, robots_allows, robots_disallows, has_robots, is_https)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                robots_txt = VALUES(robots_txt),
                robots_allows = VALUES(robots_allows),
                robots_disallows = VALUES(robots_disallows),
                has_robots = VALUES(has_robots),
                is_https = VALUES(is_https)
        ", [
            $this->projectId,
            $robotsTxt ?: null,
            json_encode($allows),
            json_encode($disallows),
            (int) (bool) $robotsTxt,
            (int) $isHttps,
        ]);
    }

    /**
     * Fetch URL senza consumare crediti (per discovery)
     * Usa configurazione spider (timeout, user-agent, etc.)
     */
    private function fetchUrlRaw(string $url, int $retryCount = 0): ?string
    {
        $result = $this->scraperService->fetchRaw($url, [
            'timeout' => $this->timeout,
            'follow_redirects' => $this->followRedirects,
            'skip_ssl_verify' => true, // Per siti con certificati self-signed
            'headers' => [
                'User-Agent: ' . $this->userAgent,
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.8',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
            ],
        ]);

        // Accetta qualsiasi risposta 2xx come successo
        $httpCode = $result['http_code'] ?? 0;
        if (isset($result['error']) || $httpCode < 200 || $httpCode >= 300) {
            // Retry su errore se configurato
            if ($retryCount < $this->maxRetries) {
                usleep(500000); // 500ms prima del retry
                return $this->fetchUrlRaw($url, $retryCount + 1);
            }
            return null;
        }

        return $result['body'] ?? null;
    }

    /**
     * Normalizza URL - rimuove trailing slash, query string, fragment
     * Converte URL relativi in assoluti
     */
    private function normalizeUrl(string $href, string $baseUrl, string $currentPageUrl = null): ?string
    {
        $href = trim($href);

        // Escludi URL vuoti, anchor puri, javascript
        if (empty($href) || $href === '#' || str_starts_with($href, 'javascript:')) {
            return null;
        }

        // Rimuovi fragment (#anchor)
        if (($hashPos = strpos($href, '#')) !== false) {
            $href = substr($href, 0, $hashPos);
            if (empty($href)) return null;
        }

        // Escludi schemi non-HTTP (tel, mailto, whatsapp, skype, fax, sms, etc.)
        if (preg_match('/^(tel|mailto|whatsapp|skype|fax|sms|viber|tg|callto|data):/i', $href)) {
            return null;
        }

        // Parse base URL
        $baseParsed = parse_url($baseUrl);
        $baseScheme = $baseParsed['scheme'] ?? 'https';
        $baseHost = $baseParsed['host'] ?? '';

        // URL protocol-relative (//example.com/page)
        if (str_starts_with($href, '//')) {
            $href = $baseScheme . ':' . $href;
        }
        // URL root-relative (/page)
        elseif (str_starts_with($href, '/')) {
            $href = $baseScheme . '://' . $baseHost . $href;
        }
        // URL assoluto (http:// o https://)
        elseif (preg_match('#^https?://#i', $href)) {
            // Già assoluto, ok
        }
        // URL relativo (page.html, ../page, ./page)
        else {
            // Usa la pagina corrente come base se disponibile
            $contextUrl = $currentPageUrl ?: $baseUrl;
            $contextParsed = parse_url($contextUrl);
            $contextPath = $contextParsed['path'] ?? '/';

            // Rimuovi filename dalla path (es. /blog/post.html -> /blog/)
            $contextDir = preg_replace('#/[^/]*$#', '/', $contextPath);
            if (empty($contextDir)) $contextDir = '/';

            // Risolvi ../ e ./
            $href = $this->resolveRelativePath($contextDir, $href);
            $href = $baseScheme . '://' . $baseHost . $href;
        }

        // Normalizza: rimuovi trailing slash (eccetto root)
        $parsed = parse_url($href);
        $path = $parsed['path'] ?? '/';

        // Rimuovi trailing slash se non è root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Ricostruisci URL normalizzato (senza query string per dedup)
        $normalized = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $normalized .= $path;

        // Converti a lowercase per host (case-insensitive)
        $normalized = preg_replace_callback('#^(https?://)([^/]+)#i', function($m) {
            return $m[1] . strtolower($m[2]);
        }, $normalized);

        return $normalized;
    }

    /**
     * Risolvi path relativo (gestisce ../ e ./)
     */
    private function resolveRelativePath(string $basePath, string $relativePath): string
    {
        // Se inizia con ./, rimuovilo
        if (str_starts_with($relativePath, './')) {
            $relativePath = substr($relativePath, 2);
        }

        // Combina base + relative
        $path = $basePath . $relativePath;

        // Risolvi ../
        $parts = explode('/', $path);
        $resolved = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } elseif ($part !== '' && $part !== '.') {
                $resolved[] = $part;
            }
        }

        return '/' . implode('/', $resolved);
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
     * Imposta flag per fermare crawl (in memoria)
     */
    public function stop(): void
    {
        $this->shouldStopFlag = true;
    }

    /**
     * Verifica se deve fermarsi
     * Controlla sia flag in memoria che DB (ogni N pagine per efficienza)
     */
    public function shouldStop(): bool
    {
        // Check flag in memoria (immediato)
        if ($this->shouldStopFlag) {
            return true;
        }

        // Check da DB ogni N pagine per non sovraccaricare
        $this->pagesSinceLastCheck++;

        if ($this->sessionId && $this->pagesSinceLastCheck >= $this->stopCheckInterval) {
            $this->pagesSinceLastCheck = 0;

            // Riconnetti DB per evitare "gone away"
            Database::reconnect();

            if ($this->sessionModel->shouldStop($this->sessionId)) {
                $this->shouldStopFlag = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Aggiorna progresso sessione
     */
    public function updateSessionProgress(int $pagesCrawled, ?string $currentUrl = null, ?int $issuesFound = null): void
    {
        if ($this->sessionId) {
            $this->sessionModel->updateProgress($this->sessionId, $pagesCrawled, $currentUrl, $issuesFound);
        }
    }

    /**
     * Completa sessione
     */
    public function completeSession(): void
    {
        if ($this->sessionId) {
            $this->sessionModel->complete($this->sessionId);
        }
    }

    /**
     * Segna sessione come stoppata
     */
    public function stopSession(): void
    {
        if ($this->sessionId) {
            $this->sessionModel->stop($this->sessionId);
        }
    }

    /**
     * Segna sessione come fallita
     */
    public function failSession(string $error): void
    {
        if ($this->sessionId) {
            $this->sessionModel->fail($this->sessionId, $error);
        }
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
