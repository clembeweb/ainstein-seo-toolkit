<?php

namespace Modules\SeoAudit\Services;

use Core\Database;

/**
 * SiteProfileDetector
 *
 * Analizza i dati crawlati per rilevare il profilo del sito
 * (tipo, dimensione, struttura) per la contestualizzazione AI.
 */
class SiteProfileDetector
{
    /**
     * Pattern URL per rilevamento tipo sito
     */
    private const TYPE_PATTERNS = [
        'e-commerce' => [
            'url' => ['/product', '/prodott', '/cart', '/carrello', '/shop', '/negozio', '/categoria', '/checkout', '/wishlist', '/add-to-cart'],
            'title' => ['shop', 'negozio', 'prodotto', 'product', 'carrello', 'cart', 'acquista', 'prezzo', 'price', 'compra'],
        ],
        'blog' => [
            'url' => ['/blog', '/article', '/articol', '/post', '/category', '/tag/', '/author/', '/autore/'],
            'title' => ['blog', 'articolo', 'article', 'post', 'redazione', 'editorial'],
        ],
        'saas' => [
            'url' => ['/pricing', '/prezzi', '/features', '/funzionalit', '/docs', '/api', '/documentation', '/integrations', '/changelog', '/signup', '/register'],
            'title' => ['pricing', 'prezzi', 'features', 'funzionalit', 'api', 'docs', 'documentation', 'integrazione', 'signup'],
        ],
        'corporate' => [
            'url' => ['/about', '/chi-siamo', '/team', '/servizi', '/services', '/contatti', '/contact', '/mission', '/portfolio', '/lavora-con-noi', '/careers'],
            'title' => ['chi siamo', 'about', 'team', 'servizi', 'services', 'contatti', 'contact', 'mission', 'azienda', 'company'],
        ],
        'news' => [
            'url' => ['/news', '/notizie', '/cronaca', '/politica', '/economia', '/sport', '/cultura', '/archivio', '/ultime-notizie'],
            'title' => ['news', 'notizie', 'cronaca', 'ultime', 'breaking', 'redazione', 'giornale', 'quotidiano'],
        ],
    ];

    /**
     * Rileva il profilo del sito basandosi sui dati crawlati.
     *
     * @return array{
     *   type: string,
     *   size: string,
     *   size_label: string,
     *   total_pages: int,
     *   avg_depth: float,
     *   has_sitemap: bool,
     *   internal_links_ratio: float
     * }
     */
    public function detect(int $projectId): array
    {
        $totalPages = $this->getTotalPages($projectId);
        $type = $this->detectType($projectId);
        $size = $this->detectSize($totalPages);
        $avgDepth = $this->getAvgDepth($projectId);
        $hasSitemap = $this->hasSitemap($projectId);
        $internalLinksRatio = $this->getInternalLinksRatio($projectId, $totalPages);

        return [
            'type' => $type,
            'size' => $size,
            'size_label' => $this->buildSizeLabel($size, $totalPages),
            'total_pages' => $totalPages,
            'avg_depth' => $avgDepth,
            'has_sitemap' => $hasSitemap,
            'internal_links_ratio' => $internalLinksRatio,
        ];
    }

    /**
     * Conta pagine crawlate totali
     */
    private function getTotalPages(int $projectId): int
    {
        return Database::count('sa_pages', "project_id = ? AND status = 'crawled'", [$projectId]);
    }

    /**
     * Rileva il tipo di sito analizzando URL e titoli delle pagine.
     * Conta i match di ogni tipo e ritorna quello con piu occorrenze.
     */
    private function detectType(int $projectId): string
    {
        // Recupera URL e titoli delle pagine crawlate (max 500 per performance)
        $pages = Database::fetchAll(
            "SELECT url, title FROM sa_pages
             WHERE project_id = ? AND status = 'crawled'
             ORDER BY id
             LIMIT 500",
            [$projectId]
        );

        if (empty($pages)) {
            return 'generic';
        }

        $scores = [];
        foreach (self::TYPE_PATTERNS as $type => $patterns) {
            $scores[$type] = 0;

            foreach ($pages as $page) {
                $url = mb_strtolower($page['url'] ?? '');
                $title = mb_strtolower($page['title'] ?? '');

                // Match URL patterns
                foreach ($patterns['url'] as $pattern) {
                    if (str_contains($url, $pattern)) {
                        $scores[$type] += 2; // URL match pesa di piu
                        break; // Una sola corrispondenza URL per pagina
                    }
                }

                // Match title patterns
                foreach ($patterns['title'] as $pattern) {
                    if (str_contains($title, $pattern)) {
                        $scores[$type] += 1;
                        break; // Una sola corrispondenza title per pagina
                    }
                }
            }
        }

        // Trova il tipo con score massimo
        arsort($scores);
        $topType = array_key_first($scores);
        $topScore = $scores[$topType] ?? 0;

        // Soglia minima: almeno il 5% delle pagine deve matchare
        $threshold = max(1, count($pages) * 0.05);

        if ($topScore < $threshold) {
            return 'generic';
        }

        return $topType;
    }

    /**
     * Determina la dimensione del sito in base al numero di pagine.
     */
    private function detectSize(int $totalPages): string
    {
        if ($totalPages < 20) {
            return 'micro';
        }
        if ($totalPages <= 100) {
            return 'piccolo';
        }
        if ($totalPages <= 500) {
            return 'medio';
        }
        return 'grande';
    }

    /**
     * Genera label leggibile per la dimensione.
     */
    private function buildSizeLabel(string $size, int $totalPages): string
    {
        $labels = [
            'micro' => 'Micro',
            'piccolo' => 'Piccolo',
            'medio' => 'Medio',
            'grande' => 'Grande',
        ];

        $label = $labels[$size] ?? ucfirst($size);
        return "{$label} ({$totalPages} pagine)";
    }

    /**
     * Calcola la profondita media delle pagine.
     */
    private function getAvgDepth(int $projectId): float
    {
        $result = Database::fetch(
            "SELECT AVG(COALESCE(depth, 0)) as avg_depth
             FROM sa_pages
             WHERE project_id = ? AND status = 'crawled'",
            [$projectId]
        );

        return round((float) ($result['avg_depth'] ?? 0), 2);
    }

    /**
     * Verifica se il sito ha una sitemap configurata.
     */
    private function hasSitemap(int $projectId): bool
    {
        $config = Database::fetch(
            "SELECT sitemap_urls, has_sitemap
             FROM sa_site_config
             WHERE project_id = ?",
            [$projectId]
        );

        if (!$config) {
            return false;
        }

        // has_sitemap flag diretto
        if (!empty($config['has_sitemap'])) {
            return true;
        }

        // Fallback: controlla se sitemap_urls contiene dati
        if (!empty($config['sitemap_urls'])) {
            $urls = json_decode($config['sitemap_urls'], true);
            return is_array($urls) && count($urls) > 0;
        }

        return false;
    }

    /**
     * Calcola il rapporto di pagine con link interni (0.0-1.0).
     */
    private function getInternalLinksRatio(int $projectId, int $totalPages): float
    {
        if ($totalPages === 0) {
            return 0.0;
        }

        $result = Database::fetch(
            "SELECT COUNT(*) as cnt
             FROM sa_pages
             WHERE project_id = ? AND status = 'crawled' AND internal_links_count > 0",
            [$projectId]
        );

        $pagesWithLinks = (int) ($result['cnt'] ?? 0);

        return round($pagesWithLinks / $totalPages, 4);
    }
}
