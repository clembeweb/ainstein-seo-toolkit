<?php

namespace Modules\SeoAudit\Services;

use Core\Database;

/**
 * LinkStructureService
 *
 * Analizza la struttura dei link interni usando i dati già disponibili dal crawl
 * (sa_pages.links_data, sa_pages.internal_links_count)
 */
class LinkStructureService
{
    /**
     * Analizza struttura link del progetto
     */
    public function analyzeStructure(int $projectId): array
    {
        $pages = Database::fetchAll(
            "SELECT id, url, internal_links_count, external_links_count, links_data
             FROM sa_pages WHERE project_id = ? AND status = 'crawled'",
            [$projectId]
        );

        $totalInternal = 0;
        $totalExternal = 0;
        $pagesWithNoInternal = 0;
        $maxInternalLinks = 0;
        $maxInternalUrl = '';

        // Costruisci mappa URL -> inbound links
        $inboundMap = []; // url => count of pages linking to it
        $allCrawledUrls = [];

        foreach ($pages as $page) {
            $allCrawledUrls[$page['url']] = $page['id'];
            $totalInternal += (int) $page['internal_links_count'];
            $totalExternal += (int) $page['external_links_count'];

            if ((int) $page['internal_links_count'] === 0) {
                $pagesWithNoInternal++;
            }

            if ((int) $page['internal_links_count'] > $maxInternalLinks) {
                $maxInternalLinks = (int) $page['internal_links_count'];
                $maxInternalUrl = $page['url'];
            }

            // Parse links_data per contare inbound
            $linksData = json_decode($page['links_data'] ?? '[]', true);
            $internalLinks = $linksData['internal'] ?? [];

            foreach ($internalLinks as $link) {
                $targetUrl = $link['url'] ?? '';
                if (!$targetUrl) continue;

                // Normalizza URL (rimuovi trailing slash e fragment)
                $targetUrl = rtrim(strtok($targetUrl, '#'), '/');

                if (!isset($inboundMap[$targetUrl])) {
                    $inboundMap[$targetUrl] = 0;
                }
                $inboundMap[$targetUrl]++;
            }
        }

        // Conta pagine orfane (crawlate ma senza link in entrata)
        $orphanCount = 0;
        foreach ($allCrawledUrls as $url => $pageId) {
            $normalizedUrl = rtrim($url, '/');
            if (!isset($inboundMap[$normalizedUrl]) && !isset($inboundMap[$normalizedUrl . '/'])) {
                $orphanCount++;
            }
        }

        $totalPages = count($pages);
        $avgInternalPerPage = $totalPages > 0 ? round($totalInternal / $totalPages, 1) : 0;

        return [
            'total_pages' => $totalPages,
            'total_internal_links' => $totalInternal,
            'total_external_links' => $totalExternal,
            'avg_internal_per_page' => $avgInternalPerPage,
            'pages_without_outlinks' => $pagesWithNoInternal,
            'orphan_pages' => $orphanCount,
            'max_internal_links' => $maxInternalLinks,
            'max_internal_url' => $maxInternalUrl,
        ];
    }

    /**
     * Ottieni pagine orfane (nessun link in entrata da altre pagine crawlate)
     */
    public function getOrphanPages(int $projectId): array
    {
        $pages = Database::fetchAll(
            "SELECT id, url, title, internal_links_count, links_data
             FROM sa_pages WHERE project_id = ? AND status = 'crawled'",
            [$projectId]
        );

        // Costruisci mappa inbound
        $inboundMap = [];
        foreach ($pages as $page) {
            $linksData = json_decode($page['links_data'] ?? '[]', true);
            foreach (($linksData['internal'] ?? []) as $link) {
                $targetUrl = rtrim(strtok($link['url'] ?? '', '#'), '/');
                if ($targetUrl) {
                    $inboundMap[$targetUrl] = ($inboundMap[$targetUrl] ?? 0) + 1;
                }
            }
        }

        // Trova pagine senza inbound
        $orphans = [];
        foreach ($pages as $page) {
            $normalizedUrl = rtrim($page['url'], '/');
            $hasInbound = isset($inboundMap[$normalizedUrl]) || isset($inboundMap[$normalizedUrl . '/']);

            if (!$hasInbound) {
                $orphans[] = [
                    'id' => $page['id'],
                    'url' => $page['url'],
                    'title' => $page['title'],
                    'outgoing_links' => (int) $page['internal_links_count'],
                ];
            }
        }

        return $orphans;
    }

    /**
     * Analisi anchor text (distribuzione e duplicati)
     */
    public function getAnchorAnalysis(int $projectId): array
    {
        $pages = Database::fetchAll(
            "SELECT links_data FROM sa_pages WHERE project_id = ? AND status = 'crawled'",
            [$projectId]
        );

        $anchorCounts = []; // anchor => count
        $anchorTargets = []; // anchor => [urls]
        $emptyAnchors = 0;
        $totalLinks = 0;

        foreach ($pages as $page) {
            $linksData = json_decode($page['links_data'] ?? '[]', true);

            foreach (($linksData['internal'] ?? []) as $link) {
                $totalLinks++;
                $anchor = trim($link['text'] ?? '');

                if ($anchor === '') {
                    $emptyAnchors++;
                    continue;
                }

                $anchorLower = mb_strtolower($anchor);
                $anchorCounts[$anchorLower] = ($anchorCounts[$anchorLower] ?? 0) + 1;

                $targetUrl = $link['url'] ?? '';
                if ($targetUrl && !in_array($targetUrl, $anchorTargets[$anchorLower] ?? [])) {
                    $anchorTargets[$anchorLower][] = $targetUrl;
                }
            }
        }

        // Ordina per frequenza decrescente
        arsort($anchorCounts);

        // Top anchors (max 50)
        $topAnchors = [];
        $count = 0;
        foreach ($anchorCounts as $anchor => $frequency) {
            if ($count >= 50) break;
            $topAnchors[] = [
                'anchor' => $anchor,
                'count' => $frequency,
                'targets' => count($anchorTargets[$anchor] ?? []),
            ];
            $count++;
        }

        // Anchor generici (clicca qui, leggi, etc.)
        $genericPatterns = ['clicca qui', 'click here', 'leggi', 'scopri', 'vai', 'link', 'qui', 'read more', 'learn more'];
        $genericAnchors = [];
        foreach ($anchorCounts as $anchor => $frequency) {
            foreach ($genericPatterns as $pattern) {
                if (str_contains($anchor, $pattern)) {
                    $genericAnchors[] = [
                        'anchor' => $anchor,
                        'count' => $frequency,
                    ];
                    break;
                }
            }
        }

        return [
            'total_links' => $totalLinks,
            'unique_anchors' => count($anchorCounts),
            'empty_anchors' => $emptyAnchors,
            'generic_anchors' => $genericAnchors,
            'top_anchors' => $topAnchors,
        ];
    }

    /**
     * Distribuzione juice (rapporto link in/out per pagina)
     */
    public function getJuiceDistribution(int $projectId): array
    {
        $pages = Database::fetchAll(
            "SELECT id, url, title, internal_links_count, links_data
             FROM sa_pages WHERE project_id = ? AND status = 'crawled'
             ORDER BY internal_links_count DESC",
            [$projectId]
        );

        // Costruisci mappa inbound
        $inboundMap = [];
        foreach ($pages as $page) {
            $linksData = json_decode($page['links_data'] ?? '[]', true);
            foreach (($linksData['internal'] ?? []) as $link) {
                $targetUrl = rtrim(strtok($link['url'] ?? '', '#'), '/');
                if ($targetUrl) {
                    $inboundMap[$targetUrl] = ($inboundMap[$targetUrl] ?? 0) + 1;
                }
            }
        }

        // Calcola distribuzione per ogni pagina
        $distribution = [];
        foreach ($pages as $page) {
            $normalizedUrl = rtrim($page['url'], '/');
            $inbound = $inboundMap[$normalizedUrl] ?? ($inboundMap[$normalizedUrl . '/'] ?? 0);
            $outbound = (int) $page['internal_links_count'];

            $distribution[] = [
                'id' => $page['id'],
                'url' => $page['url'],
                'title' => $page['title'],
                'inbound' => $inbound,
                'outbound' => $outbound,
                'ratio' => $outbound > 0 ? round($inbound / $outbound, 2) : ($inbound > 0 ? 999 : 0),
            ];
        }

        // Ordina per inbound decrescente
        usort($distribution, fn($a, $b) => $b['inbound'] - $a['inbound']);

        return $distribution;
    }

    /**
     * Grafo link (nodi e edges per visualizzazione)
     */
    public function getLinkGraph(int $projectId, int $limit = 50): array
    {
        $pages = Database::fetchAll(
            "SELECT id, url, title, internal_links_count, links_data
             FROM sa_pages WHERE project_id = ? AND status = 'crawled'
             ORDER BY internal_links_count DESC
             LIMIT ?",
            [$projectId, $limit]
        );

        $nodes = [];
        $edges = [];
        $urlToId = [];

        // Crea nodi
        foreach ($pages as $page) {
            $urlToId[$page['url']] = $page['id'];
            $normalizedUrl = rtrim($page['url'], '/');
            $urlToId[$normalizedUrl] = $page['id'];

            // Estrai path per label breve
            $path = parse_url($page['url'], PHP_URL_PATH) ?: '/';

            $nodes[] = [
                'id' => $page['id'],
                'label' => strlen($path) > 30 ? substr($path, 0, 27) . '...' : $path,
                'title' => $page['title'] ?: $page['url'],
                'value' => (int) $page['internal_links_count'] + 1,
            ];
        }

        // Crea edges
        foreach ($pages as $page) {
            $linksData = json_decode($page['links_data'] ?? '[]', true);
            foreach (($linksData['internal'] ?? []) as $link) {
                $targetUrl = $link['url'] ?? '';
                $normalizedTarget = rtrim(strtok($targetUrl, '#'), '/');

                $targetId = $urlToId[$targetUrl] ?? ($urlToId[$normalizedTarget] ?? null);
                if ($targetId && $targetId !== $page['id']) {
                    $edges[] = [
                        'from' => $page['id'],
                        'to' => $targetId,
                    ];
                }
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
