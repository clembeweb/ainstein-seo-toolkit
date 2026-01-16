<?php

namespace Modules\SeoTracking\Services;

use Core\Database;
use Modules\SeoTracking\Helpers\UrlHelper;

/**
 * DataMergeService
 * Unisce dati GSC e GA4 per viste aggregate
 */
class DataMergeService
{
    /**
     * Ottiene dati pagine con merge GSC + GA4
     * Aggregato per URL normalizzato
     *
     * @param int $projectId
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return array
     */
    public function getPagesMerged(int $projectId, string $startDate, string $endDate): array
    {
        // Query GSC aggregata per pagina
        $gscSql = "
            SELECT
                TRIM(TRAILING '/' FROM
                    CASE
                        WHEN page LIKE 'https://%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(page, '://', -1), '/', -99)
                        WHEN page LIKE 'http://%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(page, '://', -1), '/', -99)
                        ELSE page
                    END
                ) as normalized_url,
                page as original_url,
                SUM(clicks) as gsc_clicks,
                SUM(impressions) as gsc_impressions,
                AVG(position) as gsc_avg_position,
                AVG(ctr) as gsc_avg_ctr,
                COUNT(DISTINCT query) as keyword_count
            FROM st_gsc_data
            WHERE project_id = ? AND date BETWEEN ? AND ? AND page != ''
            GROUP BY normalized_url, original_url
        ";

        $gscData = Database::fetchAll($gscSql, [$projectId, $startDate, $endDate]);

        // Query GA4 aggregata per pagina (SOLO traffico organico)
        $ga4Sql = "
            SELECT
                landing_page as normalized_url,
                SUM(sessions) as ga4_sessions,
                SUM(users) as ga4_users,
                AVG(engagement_rate) as ga4_engagement_rate,
                SUM(purchases) as ga4_conversions
            FROM st_ga4_data
            WHERE project_id = ? AND date BETWEEN ? AND ?
            AND (source_medium = 'google / organic' OR source_medium LIKE '%organic%')
            GROUP BY landing_page
        ";

        $ga4Data = Database::fetchAll($ga4Sql, [$projectId, $startDate, $endDate]);

        // Crea lookup table GA4 (normalizzata)
        $ga4Lookup = [];
        foreach ($ga4Data as $row) {
            $url = $row['normalized_url'] ?? '';
            if (empty($url)) {
                continue; // Salta record senza URL
            }
            $normalized = UrlHelper::normalize($url);
            $ga4Lookup[$normalized] = $row;
        }

        // Merge GSC con GA4
        $merged = [];
        foreach ($gscData as $gsc) {
            // Normalizza URL GSC per match
            $originalUrl = $gsc['original_url'] ?? '';
            if (empty($originalUrl)) {
                continue; // Salta record senza URL
            }
            $normalizedForMatch = UrlHelper::normalize($originalUrl);
            $ga4 = $ga4Lookup[$normalizedForMatch] ?? null;

            $merged[] = [
                'url' => $normalizedForMatch,
                'original_url' => $gsc['original_url'],
                'keyword_count' => (int) $gsc['keyword_count'],
                // GSC metrics
                'clicks' => (int) $gsc['gsc_clicks'],
                'impressions' => (int) $gsc['gsc_impressions'],
                'avg_position' => round((float) $gsc['gsc_avg_position'], 1),
                'avg_ctr' => round((float) $gsc['gsc_avg_ctr'] * 100, 2),
                // GA4 metrics
                'sessions' => $ga4 ? (int) $ga4['ga4_sessions'] : null,
                'users' => $ga4 ? (int) $ga4['ga4_users'] : null,
                'engagement_rate' => $ga4 ? round((float) $ga4['ga4_engagement_rate'] * 100, 1) : null,
                'conversions' => $ga4 ? (int) $ga4['ga4_conversions'] : null,
                // Match status
                'has_ga4_data' => $ga4 !== null,
            ];
        }

        // Ordina per click DESC
        usort($merged, fn($a, $b) => $b['clicks'] - $a['clicks']);

        return $merged;
    }

    /**
     * Ottiene keywords arricchite con dati GA4 della pagina
     *
     * @param int $projectId
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */
    public function getKeywordsWithGa4(int $projectId, string $startDate, string $endDate, int $limit = 100): array
    {
        $sql = "
            SELECT
                query as keyword,
                page as url,
                SUM(clicks) as clicks,
                SUM(impressions) as impressions,
                AVG(position) as avg_position,
                AVG(ctr) as avg_ctr
            FROM st_gsc_data
            WHERE project_id = ? AND date BETWEEN ? AND ? AND query != ''
            GROUP BY query, page
            ORDER BY SUM(clicks) DESC
            LIMIT ?
        ";

        $keywords = Database::fetchAll($sql, [$projectId, $startDate, $endDate, $limit]);

        // Fetch GA4 data (SOLO traffico organico)
        $ga4Sql = "
            SELECT landing_page, SUM(sessions) as sessions, SUM(users) as users, SUM(purchases) as conversions
            FROM st_ga4_data
            WHERE project_id = ? AND date BETWEEN ? AND ?
            AND (source_medium = 'google / organic' OR source_medium LIKE '%organic%')
            GROUP BY landing_page
        ";
        $ga4Data = Database::fetchAll($ga4Sql, [$projectId, $startDate, $endDate]);

        // Create lookup with normalized URLs
        $ga4Lookup = [];
        foreach ($ga4Data as $row) {
            $url = $row['landing_page'] ?? '';
            if (empty($url)) {
                continue; // Salta record senza URL
            }
            $normalized = UrlHelper::normalize($url);
            $ga4Lookup[$normalized] = $row;
        }

        // Merge - includi anche keywords senza URL (mostra dati GSC)
        $result = [];
        foreach ($keywords as $kw) {
            $url = $kw['url'] ?? '';
            $normalizedUrl = !empty($url) ? UrlHelper::normalize($url) : '';
            $ga4 = !empty($normalizedUrl) ? ($ga4Lookup[$normalizedUrl] ?? null) : null;

            $result[] = [
                'keyword' => $kw['keyword'] ?? '',
                'url' => $url,
                'clicks' => (int) ($kw['clicks'] ?? 0),
                'impressions' => (int) ($kw['impressions'] ?? 0),
                'avg_position' => round((float) ($kw['avg_position'] ?? 0), 1),
                'avg_ctr' => round((float) ($kw['avg_ctr'] ?? 0) * 100, 2),
                'ga4_sessions' => $ga4 ? (int) $ga4['sessions'] : null,
                'ga4_users' => $ga4 ? (int) $ga4['users'] : null,
                'ga4_conversions' => $ga4 ? (int) $ga4['conversions'] : null,
                'normalized_url' => $normalizedUrl,
            ];
        }

        return $result;
    }

    /**
     * Ottiene keywords per una specifica pagina
     *
     * @param int $projectId
     * @param string $pageUrl (path normalizzato, es: /blog/post)
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getKeywordsForPage(int $projectId, string $pageUrl, string $startDate, string $endDate): array
    {
        // Fetch all GSC data for the project and date range
        $sql = "
            SELECT
                query as keyword,
                page,
                SUM(clicks) as clicks,
                SUM(impressions) as impressions,
                AVG(position) as avg_position,
                AVG(ctr) as avg_ctr
            FROM st_gsc_data
            WHERE project_id = ? AND date BETWEEN ? AND ? AND query != ''
            GROUP BY query, page
            ORDER BY SUM(clicks) DESC
        ";

        $allKeywords = Database::fetchAll($sql, [$projectId, $startDate, $endDate]);

        // Filter by normalized URL
        $normalizedTarget = UrlHelper::normalize($pageUrl);
        $result = [];

        foreach ($allKeywords as $kw) {
            $page = $kw['page'] ?? '';
            if (empty($page)) {
                continue; // Salta keyword senza pagina
            }
            $normalizedKwUrl = UrlHelper::normalize($page);
            if ($normalizedKwUrl === $normalizedTarget) {
                $result[] = [
                    'keyword' => $kw['keyword'],
                    'clicks' => (int) $kw['clicks'],
                    'impressions' => (int) $kw['impressions'],
                    'avg_position' => round((float) $kw['avg_position'], 1),
                    'avg_ctr' => round((float) $kw['avg_ctr'] * 100, 2),
                ];
            }
        }

        return $result;
    }

    /**
     * Statistiche riassuntive merge
     *
     * @param int $projectId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getSummaryStats(int $projectId, string $startDate, string $endDate): array
    {
        $pages = $this->getPagesMerged($projectId, $startDate, $endDate);

        $totalClicks = array_sum(array_column($pages, 'clicks'));
        $totalImpressions = array_sum(array_column($pages, 'impressions'));
        $totalSessions = array_sum(array_filter(array_column($pages, 'sessions'), fn($v) => $v !== null));
        $totalConversions = array_sum(array_filter(array_column($pages, 'conversions'), fn($v) => $v !== null));
        $pagesWithGa4 = count(array_filter($pages, fn($p) => $p['has_ga4_data']));

        return [
            'total_pages' => count($pages),
            'pages_with_ga4' => $pagesWithGa4,
            'pages_without_ga4' => count($pages) - $pagesWithGa4,
            'total_clicks' => $totalClicks,
            'total_impressions' => $totalImpressions,
            'total_sessions' => $totalSessions,
            'total_conversions' => $totalConversions,
            'avg_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
            'ga4_coverage_pct' => count($pages) > 0 ? round(($pagesWithGa4 / count($pages)) * 100, 1) : 0,
        ];
    }

    /**
     * Top pages per opportunità (alto traffico GSC, basso engagement GA4)
     *
     * @param int $projectId
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */
    public function getOpportunityPages(int $projectId, string $startDate, string $endDate, int $limit = 20): array
    {
        $pages = $this->getPagesMerged($projectId, $startDate, $endDate);

        // Filtra solo pagine con dati GA4 e ordina per potenziale miglioramento
        $opportunities = array_filter($pages, function ($p) {
            return $p['has_ga4_data']
                && $p['clicks'] > 0
                && $p['engagement_rate'] !== null
                && $p['engagement_rate'] < 50; // Engagement rate sotto 50%
        });

        // Ordina per click (alto potenziale)
        usort($opportunities, fn($a, $b) => $b['clicks'] - $a['clicks']);

        return array_slice($opportunities, 0, $limit);
    }
}
