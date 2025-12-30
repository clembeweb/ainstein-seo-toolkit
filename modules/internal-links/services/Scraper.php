<?php

namespace Modules\InternalLinks\Services;

use Services\ScraperService;
use Modules\InternalLinks\Models\Project;
use Modules\InternalLinks\Models\Url;
use Modules\InternalLinks\Models\InternalLink;
use Core\Credits;

/**
 * Module Scraper Service
 *
 * Handles scraping operations for internal links analysis
 * Uses platform's ScraperService for HTTP requests
 */
class Scraper
{
    private Project $projectModel;
    private Url $urlModel;
    private InternalLink $linkModel;
    private LinkExtractor $linkExtractor;

    private array $projectConfig;
    private int $projectId;
    private int $userId;
    private bool $shouldStop = false;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->urlModel = new Url();
        $this->linkModel = new InternalLink();
        $this->linkExtractor = new LinkExtractor();
    }

    /**
     * Initialize scraper for a project
     */
    public function init(int $projectId, int $userId): self
    {
        $project = $this->projectModel->find($projectId, $userId);

        if (!$project) {
            throw new \Exception('Project not found');
        }

        $this->projectId = $projectId;
        $this->userId = $userId;
        $this->projectConfig = $project;

        return $this;
    }

    /**
     * Scrape a batch of URLs
     */
    public function scrapeBatch(string $mode = 'pending', int $batchSize = 10): array
    {
        $results = [
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'no_content' => 0,
            'links_found' => 0,
            'credits_used' => 0,
        ];

        $urls = $mode === 'all'
            ? $this->urlModel->getAllForScraping($this->projectId, $batchSize)
            : $this->urlModel->getPending($this->projectId, $batchSize);

        if (empty($urls)) {
            return $results;
        }

        $delay = $this->projectConfig['scrape_delay'] ?? 1000;

        foreach ($urls as $url) {
            if ($this->shouldStop) {
                break;
            }

            // Check credits before scraping
            $creditCost = Credits::getCost('scrape_url');
            if (!Credits::hasEnough($this->userId, $creditCost)) {
                $results['errors']++;
                break;
            }

            $result = $this->scrapeUrl($url);
            $results['processed']++;

            if ($result['success']) {
                // Consume credits on success
                Credits::consume($this->userId, $creditCost, 'scrape_url', 'internal-links', [
                    'project_id' => $this->projectId,
                    'url' => $url['url'],
                ]);
                $results['credits_used'] += $creditCost;

                if ($result['has_content']) {
                    $results['success']++;
                    $results['links_found'] += $result['links_count'];
                } else {
                    $results['no_content']++;
                }
            } else {
                $results['errors']++;
            }

            if ($delay > 0 && $results['processed'] < count($urls)) {
                usleep($delay * 1000);
            }
        }

        $this->projectModel->updateStats($this->projectId);

        return $results;
    }

    /**
     * Scrape a single URL
     */
    public function scrapeUrl(array $urlData): array
    {
        $urlId = $urlData['id'];
        $url = $urlData['url'];

        $fullUrl = $this->buildFullUrl($url);

        $this->urlModel->markAsScraping($urlId);

        $result = [
            'success' => false,
            'has_content' => false,
            'links_count' => 0,
            'error' => null,
        ];

        try {
            // Use platform's ScraperService (fetchRaw doesn't consume credits - we handle credits in batch loop)
            $scraper = new ScraperService();
            $headers = [];
            if (!empty($this->projectConfig['user_agent'])) {
                $headers[] = 'User-Agent: ' . $this->projectConfig['user_agent'];
            }
            $response = $scraper->fetchRaw($fullUrl, [
                'headers' => $headers,
                'timeout' => 30,
            ]);

            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Failed to fetch URL');
            }

            $httpStatus = $response['http_code'] ?? 200;
            $rawHtml = $response['body'] ?? '';

            $cssSelector = $this->projectConfig['css_selector'] ?? null;
            $blockRegex = $this->projectConfig['html_block_regex'] ?? null;

            $contentHtml = $this->linkExtractor->extractContentFromSelectors($rawHtml, $cssSelector);

            $this->urlModel->markAsScraped($urlId, $rawHtml, $contentHtml, $httpStatus);

            $result['success'] = true;
            $result['has_content'] = !empty($contentHtml);

            if ($rawHtml) {
                $links = $this->linkExtractor->extractWithSelectors(
                    $rawHtml,
                    $this->projectConfig['base_url'],
                    $fullUrl,
                    $cssSelector,
                    $blockRegex
                );

                $this->linkModel->deleteBySourceUrl($urlId);

                if (!empty($links)) {
                    $linksData = [];
                    foreach ($links as $link) {
                        $sourceBlock = $link['source_block'] ?? null;
                        if ($sourceBlock && strlen($sourceBlock) > 10000) {
                            $sourceBlock = substr($sourceBlock, 0, 10000) . '...';
                        }

                        $linksData[] = [
                            'project_id' => $this->projectId,
                            'source_url_id' => $urlId,
                            'destination_url' => $link['url'],
                            'anchor_text' => $link['anchor'],
                            'anchor_text_clean' => InternalLink::cleanAnchor($link['anchor']),
                            'link_position' => $link['position'],
                            'source_block' => $sourceBlock,
                            'is_internal' => $link['is_internal'],
                            'is_valid' => true,
                        ];
                    }

                    $this->linkModel->bulkInsert($linksData);
                    $result['links_count'] = count($linksData);
                }
            }

            $this->projectModel->logActivity($this->projectId, $this->userId, 'url_scraped', [
                'url' => $url,
                'links_found' => $result['links_count'],
            ]);

        } catch (\Exception $e) {
            $this->urlModel->markAsError($urlId, $e->getMessage());
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Build full URL from relative or absolute path
     */
    private function buildFullUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $baseUrl = rtrim($this->projectConfig['base_url'], '/');

        if (strpos($url, '/') === 0) {
            $parsed = parse_url($baseUrl);
            return $parsed['scheme'] . '://' . $parsed['host'] . $url;
        }

        return $baseUrl . '/' . ltrim($url, '/');
    }

    /**
     * Stop scraping
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Get scraping progress
     */
    public function getProgress(): array
    {
        return $this->projectModel->getScrapingProgress($this->projectId);
    }
}
