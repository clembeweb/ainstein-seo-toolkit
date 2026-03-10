<?php

namespace Modules\SeoAudit\Services;

/**
 * SitemapParser
 *
 * Parser standalone per sitemap XML con supporto ricorsivo per sitemap index.
 * Ported da CrawlBudget\BudgetCrawlerService::parseSitemap() e integrato
 * con il pattern del platform SitemapService.
 *
 * Funzionalita:
 * - Discovery sitemap da robots.txt (via RobotsTxtParser) con fallback a /sitemap.xml
 * - Parse ricorsivo di sitemap index (max 3 livelli di profondita)
 * - Cap a 10.000 URL
 * - Deduplicazione URL
 * - Filtro same-domain
 */
class SitemapParser
{
    /** Numero massimo di URL da raccogliere */
    private const MAX_URLS = 10000;

    /** Profondita massima per sitemap index annidate */
    private const MAX_DEPTH = 3;

    /** Timeout per le richieste HTTP in secondi */
    private const FETCH_TIMEOUT = 15;

    /** Timeout connessione in secondi */
    private const CONNECT_TIMEOUT = 5;

    /** @var RobotsTxtParser Parser per robots.txt */
    private RobotsTxtParser $robotsParser;

    /** @var array Errori durante il parsing */
    private array $errors = [];

    /** @var array URL delle sitemap scoperte */
    private array $discoveredSitemaps = [];

    public function __construct(?RobotsTxtParser $robotsParser = null)
    {
        $this->robotsParser = $robotsParser ?? new RobotsTxtParser();
    }

    /**
     * Scopri e parsa tutte le sitemap di un sito
     *
     * Flusso:
     * 1. Se fornito robots.txt, estrae URL sitemap da li (via RobotsTxtParser)
     * 2. Fallback a {baseUrl}/sitemap.xml se nessuna sitemap trovata
     * 3. Parse ricorsivo di ogni sitemap (supporto sitemap index)
     * 4. Deduplicazione, filtro same-domain, cap a MAX_URLS
     *
     * @param string      $baseUrl    URL base del sito (es. "https://example.com")
     * @param string|null $robotsTxt  Contenuto raw di robots.txt (opzionale)
     * @return array ['urls' => [...], 'sitemaps' => [...], 'total' => int, 'errors' => [...]]
     */
    public function discoverAndParse(string $baseUrl, ?string $robotsTxt = null): array
    {
        $this->errors = [];
        $this->discoveredSitemaps = [];

        $baseUrl = rtrim($baseUrl, '/');
        $domain = $this->extractDomain($baseUrl);

        // 1. Estrai URL sitemap da robots.txt (se fornito)
        $sitemapUrls = [];
        if ($robotsTxt !== null && $robotsTxt !== '') {
            $sitemapUrls = $this->robotsParser->extractSitemaps($robotsTxt);
        }

        // 2. Fallback a /sitemap.xml
        if (empty($sitemapUrls)) {
            $sitemapUrls = [$baseUrl . '/sitemap.xml'];
        }

        $this->discoveredSitemaps = $sitemapUrls;

        // 3. Parse ricorsivo di ogni sitemap
        $allUrls = [];
        foreach ($sitemapUrls as $sitemapUrl) {
            $parsed = $this->parseSitemap($sitemapUrl, 0);
            $allUrls = array_merge($allUrls, $parsed);
            if (count($allUrls) >= self::MAX_URLS) {
                $allUrls = array_slice($allUrls, 0, self::MAX_URLS);
                break;
            }
        }

        // 4. Deduplicazione e filtro same-domain
        $seen = [];
        $filteredUrls = [];
        foreach ($allUrls as $url) {
            $normalized = strtolower(rtrim($url, '/'));
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;

            // Solo URL dello stesso dominio
            if ($this->extractDomain($url) !== $domain) {
                continue;
            }

            $filteredUrls[] = $url;

            if (count($filteredUrls) >= self::MAX_URLS) {
                break;
            }
        }

        return [
            'urls' => $filteredUrls,
            'sitemaps' => $this->discoveredSitemaps,
            'total' => count($filteredUrls),
            'errors' => $this->errors,
        ];
    }

    /**
     * Parsa una singola sitemap (supporta sitemap index con ricorsione)
     *
     * Ported da BudgetCrawlerService::parseSitemap().
     * Gestisce sia sitemap normali (<url><loc>...) che sitemap index
     * (<sitemapindex><sitemap><loc>...) con ricorsione fino a MAX_DEPTH livelli.
     *
     * Usa regex per il parsing XML (come nel CB originale) per gestire
     * anche XML malformato o non standard.
     *
     * @param string $url   URL della sitemap da parsare
     * @param int    $depth Livello corrente di profondita (0 = root)
     * @return array Lista di URL trovate
     */
    public function parseSitemap(string $url, int $depth = 0): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return [];
        }

        $content = $this->fetchRaw($url);
        if ($content === null) {
            $this->errors[] = "Impossibile recuperare sitemap: {$url}";
            return [];
        }

        $urls = [];

        // Rileva se e un sitemap index
        if (strpos($content, '<sitemapindex') !== false) {
            // Sitemap index: estrai URL delle sub-sitemap
            // Prova SimpleXML prima, fallback a regex
            $childSitemaps = $this->extractSitemapIndexUrls($content);

            foreach ($childSitemaps as $subSitemapUrl) {
                // Traccia le sub-sitemap scoperte
                if (!in_array($subSitemapUrl, $this->discoveredSitemaps)) {
                    $this->discoveredSitemaps[] = $subSitemapUrl;
                }

                $subUrls = $this->parseSitemap($subSitemapUrl, $depth + 1);
                $urls = array_merge($urls, $subUrls);

                if (count($urls) >= self::MAX_URLS) {
                    $urls = array_slice($urls, 0, self::MAX_URLS);
                    break;
                }
            }
        } else {
            // Sitemap normale: estrai URL delle pagine
            $urls = $this->extractUrlEntries($content);
        }

        return $urls;
    }

    /**
     * Getter per gli errori dell'ultima operazione
     *
     * @return array Lista di messaggi di errore
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Getter per le sitemap scoperte durante l'ultima operazione
     *
     * @return array Lista di URL sitemap trovate
     */
    public function getDiscoveredSitemaps(): array
    {
        return $this->discoveredSitemaps;
    }

    // =========================================================================
    // PRIVATE: XML PARSING
    // =========================================================================

    /**
     * Estrai URL delle sub-sitemap da un sitemap index
     *
     * Usa @simplexml_load_string con fallback a regex per XML malformato.
     *
     * @param string $content Contenuto XML del sitemap index
     * @return array Lista di URL delle sub-sitemap
     */
    private function extractSitemapIndexUrls(string $content): array
    {
        $urls = [];

        // Prova SimpleXML
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content);

        if ($xml !== false) {
            foreach ($xml->sitemap as $sitemap) {
                $loc = (string) $sitemap->loc;
                $loc = trim($loc);
                if (!empty($loc)) {
                    $urls[] = $loc;
                }
            }
        } else {
            // Fallback a regex per XML malformato
            if (preg_match_all('/<sitemap>\s*<loc>([^<]+)<\/loc>/i', $content, $matches)) {
                foreach ($matches[1] as $loc) {
                    $loc = trim($loc);
                    if (!empty($loc)) {
                        $urls[] = $loc;
                    }
                }
            }
        }

        libxml_clear_errors();

        return $urls;
    }

    /**
     * Estrai URL delle pagine da una sitemap normale
     *
     * Usa @simplexml_load_string con fallback a regex per XML malformato.
     *
     * @param string $content Contenuto XML della sitemap
     * @return array Lista di URL delle pagine
     */
    private function extractUrlEntries(string $content): array
    {
        $urls = [];

        // Prova SimpleXML
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content);

        if ($xml !== false) {
            foreach ($xml->url as $urlEntry) {
                $loc = (string) $urlEntry->loc;
                $loc = trim($loc);
                if (!empty($loc)) {
                    $urls[] = $loc;
                }
            }
        } else {
            // Fallback a regex per XML malformato
            if (preg_match_all('/<url>\s*<loc>([^<]+)<\/loc>/i', $content, $matches)) {
                foreach ($matches[1] as $loc) {
                    $loc = trim($loc);
                    if (!empty($loc)) {
                        $urls[] = $loc;
                    }
                }
            }
        }

        libxml_clear_errors();

        return $urls;
    }

    // =========================================================================
    // PRIVATE: HTTP
    // =========================================================================

    /**
     * Fetch raw content di un URL
     *
     * Usa curl con follow redirect, timeout, e gestione gzip.
     * Pattern identico a BudgetCrawlerService::fetchRaw().
     *
     * @param string $url URL da recuperare
     * @return string|null Contenuto o null in caso di errore
     */
    private function fetchRaw(string $url): ?string
    {
        $startTime = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => self::FETCH_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml, text/xml, text/html, */*',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.7',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
            ],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log sitemap fetch (GR #14)
        \Services\ApiLoggerService::log(
            'seo_audit_sitemap',
            $url,
            [],
            ['size' => strlen($content ?: ''), 'error' => $error ?: null],
            $httpCode,
            $startTime,
            ['module' => 'seo-audit', 'cost' => 0, 'context' => 'Sitemap fetch']
        );

        if ($error) {
            $this->errors[] = "Errore curl per {$url}: {$error}";
            return null;
        }

        if ($httpCode >= 200 && $httpCode < 300 && $content) {
            return $content;
        }

        if ($httpCode !== 200) {
            $this->errors[] = "HTTP {$httpCode} per {$url}";
        }

        return null;
    }

    // =========================================================================
    // PRIVATE: HELPERS
    // =========================================================================

    /**
     * Estrai dominio da URL (lowercase)
     *
     * @param string $url URL completo o dominio
     * @return string Dominio in lowercase
     */
    private function extractDomain(string $url): string
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return strtolower(parse_url($url, PHP_URL_HOST) ?? $url);
    }
}
