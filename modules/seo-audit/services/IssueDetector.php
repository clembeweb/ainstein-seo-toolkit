<?php

namespace Modules\SeoAudit\Services;

use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\Page;
use Core\Database;

/**
 * IssueDetector
 *
 * Rileva problemi SEO nelle pagine crawlate
 * Usa i tipi issue definiti in Issue::ISSUE_TYPES
 */
class IssueDetector
{
    private Issue $issueModel;
    private Page $pageModel;
    private int $projectId;

    // Soglie configurabili
    private array $thresholds = [
        'title_min_length' => 30,
        'title_max_length' => 60,
        'description_min_length' => 70,
        'description_max_length' => 160,
        'h1_max_length' => 70,
        'alt_max_length' => 125,
        'min_word_count' => 300,
        'max_links_per_page' => 100,
    ];

    public function __construct()
    {
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
    }

    /**
     * Inizializza detector per un progetto
     */
    public function init(int $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * Analizza pagina e rileva issues
     */
    public function analyzePage(array $pageData): array
    {
        $issues = [];

        // Meta Tags
        $issues = array_merge($issues, $this->checkMetaTags($pageData));

        // Headings
        $issues = array_merge($issues, $this->checkHeadings($pageData));

        // Images
        $issues = array_merge($issues, $this->checkImages($pageData));

        // Links
        $issues = array_merge($issues, $this->checkLinks($pageData));

        // Content
        $issues = array_merge($issues, $this->checkContent($pageData));

        // Technical
        $issues = array_merge($issues, $this->checkTechnical($pageData));

        // Schema
        $issues = array_merge($issues, $this->checkSchema($pageData));

        return $issues;
    }

    /**
     * Salva issues nel database
     */
    public function saveIssues(int $pageId, array $issues): int
    {
        $saved = 0;

        foreach ($issues as $issue) {
            $this->issueModel->create([
                'project_id' => $this->projectId,
                'page_id' => $pageId,
                'category' => $issue['category'],
                'issue_type' => $issue['type'],
                'severity' => $issue['severity'],
                'title' => $issue['title'],
                'description' => $issue['description'] ?? null,
                'affected_element' => $issue['element'] ?? null,
                'recommendation' => $issue['recommendation'] ?? null,
                'source' => 'crawler',
            ]);
            $saved++;
        }

        return $saved;
    }

    /**
     * Analizza e salva issues per una pagina
     */
    public function analyzeAndSave(array $pageData, int $pageId): int
    {
        $issues = $this->analyzePage($pageData);
        return $this->saveIssues($pageId, $issues);
    }

    /**
     * Check Meta Tags
     */
    private function checkMetaTags(array $data): array
    {
        $issues = [];
        $titleLength = $data['title_length'] ?? 0;
        $descLength = $data['meta_description_length'] ?? 0;

        // Title checks
        if (empty($data['title'])) {
            $issues[] = $this->createIssue('missing_title');
        } elseif ($titleLength < $this->thresholds['title_min_length']) {
            $issues[] = $this->createIssue('title_too_short', "Lunghezza: {$titleLength} caratteri");
        } elseif ($titleLength > $this->thresholds['title_max_length']) {
            $issues[] = $this->createIssue('title_too_long', "Lunghezza: {$titleLength} caratteri");
        }

        // Description checks
        if (empty($data['meta_description'])) {
            $issues[] = $this->createIssue('missing_description');
        } elseif ($descLength < $this->thresholds['description_min_length']) {
            $issues[] = $this->createIssue('description_too_short', "Lunghezza: {$descLength} caratteri");
        } elseif ($descLength > $this->thresholds['description_max_length']) {
            $issues[] = $this->createIssue('description_too_long', "Lunghezza: {$descLength} caratteri");
        }

        // OG Tags
        if (empty($data['og_title']) && empty($data['og_description']) && empty($data['og_image'])) {
            $issues[] = $this->createIssue('missing_og_tags');
        }

        return $issues;
    }

    /**
     * Check Headings
     */
    private function checkHeadings(array $data): array
    {
        $issues = [];

        $h1Count = $data['h1_count'] ?? 0;
        $h1Texts = json_decode($data['h1_texts'] ?? '[]', true);

        // H1 checks
        if ($h1Count === 0) {
            $issues[] = $this->createIssue('missing_h1');
        } elseif ($h1Count > 1) {
            $issues[] = $this->createIssue('multiple_h1', "Trovati {$h1Count} tag H1");
        }

        // H1 length
        foreach ($h1Texts as $h1) {
            if (strlen($h1) > $this->thresholds['h1_max_length']) {
                $issues[] = $this->createIssue('h1_too_long', substr($h1, 0, 100) . '...');
                break;
            }
            if (empty(trim($h1))) {
                $issues[] = $this->createIssue('empty_heading', 'Tag H1 vuoto');
                break;
            }
        }

        // Check heading hierarchy (skip levels)
        $h2Count = $data['h2_count'] ?? 0;
        $h3Count = $data['h3_count'] ?? 0;

        if ($h1Count > 0 && $h2Count === 0 && $h3Count > 0) {
            $issues[] = $this->createIssue('skipped_heading_level', 'H2 mancante tra H1 e H3');
        }

        return $issues;
    }

    /**
     * Check Images
     */
    private function checkImages(array $data): array
    {
        $issues = [];

        $imagesWithoutAlt = $data['images_without_alt'] ?? 0;
        $imagesData = json_decode($data['images_data'] ?? '[]', true);

        // Missing alt
        if ($imagesWithoutAlt > 0) {
            $issues[] = $this->createIssue('missing_alt', "{$imagesWithoutAlt} immagini senza attributo alt");
        }

        // Check individual images
        foreach ($imagesData as $image) {
            // Alt too long
            if (!empty($image['alt']) && strlen($image['alt']) > $this->thresholds['alt_max_length']) {
                $issues[] = $this->createIssue('alt_too_long', substr($image['alt'], 0, 50) . '...');
                break; // Solo prima occorrenza
            }
        }

        return $issues;
    }

    /**
     * Check Links
     */
    private function checkLinks(array $data): array
    {
        $issues = [];

        $internalLinks = $data['internal_links_count'] ?? 0;
        $externalLinks = $data['external_links_count'] ?? 0;
        $totalLinks = $internalLinks + $externalLinks;
        $nofollowCount = $data['nofollow_links_count'] ?? 0;

        // Too many links
        if ($totalLinks > $this->thresholds['max_links_per_page']) {
            $issues[] = $this->createIssue('too_many_links', "{$totalLinks} link in pagina");
        }

        // Nofollow on internal (if significant)
        if ($nofollowCount > 0 && $internalLinks > 0) {
            // Check if nofollow are on internal links
            $linksData = json_decode($data['links_data'] ?? '[]', true);
            // Simplified check - potrebbe essere migliorato
            if ($nofollowCount > 5) {
                $issues[] = $this->createIssue('nofollow_internal', "{$nofollowCount} link con rel=nofollow");
            }
        }

        return $issues;
    }

    /**
     * Check Content
     */
    private function checkContent(array $data): array
    {
        $issues = [];

        $wordCount = $data['word_count'] ?? 0;

        // Thin content
        if ($wordCount === 0) {
            $issues[] = $this->createIssue('no_content');
        } elseif ($wordCount < $this->thresholds['min_word_count']) {
            $issues[] = $this->createIssue('thin_content', "{$wordCount} parole");
        }

        return $issues;
    }

    /**
     * Check Technical SEO
     */
    private function checkTechnical(array $data): array
    {
        $issues = [];

        // Canonical
        if (empty($data['canonical_url'])) {
            $issues[] = $this->createIssue('missing_canonical');
        } elseif (!filter_var($data['canonical_url'], FILTER_VALIDATE_URL)) {
            $issues[] = $this->createIssue('wrong_canonical', $data['canonical_url']);
        }

        // Robots noindex in sitemap (da verificare a livello progetto)
        // Questo check è fatto separatamente

        return $issues;
    }

    /**
     * Check Schema markup
     */
    private function checkSchema(array $data): array
    {
        $issues = [];

        $hasSchema = $data['has_schema'] ?? false;

        if (!$hasSchema) {
            $issues[] = $this->createIssue('missing_schema');
        }

        return $issues;
    }

    /**
     * Rileva duplicati a livello di progetto
     */
    public function detectDuplicates(): int
    {
        $issuesCreated = 0;

        // Title duplicati
        $duplicateTitles = $this->pageModel->getDuplicateTitles($this->projectId);
        foreach ($duplicateTitles as $dup) {
            foreach ($dup['urls'] as $url) {
                $page = $this->pageModel->findByUrl($this->projectId, $url);
                if ($page) {
                    $this->issueModel->create([
                        'project_id' => $this->projectId,
                        'page_id' => $page['id'],
                        'category' => 'meta',
                        'issue_type' => 'duplicate_title',
                        'severity' => 'warning',
                        'title' => 'Titolo duplicato',
                        'description' => "Condiviso con altre {$dup['count']} pagine",
                        'affected_element' => $dup['title'],
                        'recommendation' => 'Ogni pagina dovrebbe avere un titolo unico.',
                        'source' => 'crawler',
                    ]);
                    $issuesCreated++;
                }
            }
        }

        // Description duplicate
        $duplicateDescriptions = $this->pageModel->getDuplicateDescriptions($this->projectId);
        foreach ($duplicateDescriptions as $dup) {
            foreach ($dup['urls'] as $url) {
                $page = $this->pageModel->findByUrl($this->projectId, $url);
                if ($page) {
                    $this->issueModel->create([
                        'project_id' => $this->projectId,
                        'page_id' => $page['id'],
                        'category' => 'meta',
                        'issue_type' => 'duplicate_description',
                        'severity' => 'warning',
                        'title' => 'Meta description duplicata',
                        'description' => "Condivisa con altre {$dup['count']} pagine",
                        'affected_element' => substr($dup['meta_description'], 0, 100) . '...',
                        'recommendation' => 'Ogni pagina dovrebbe avere una meta description unica.',
                        'source' => 'crawler',
                    ]);
                    $issuesCreated++;
                }
            }
        }

        return $issuesCreated;
    }

    /**
     * Rileva pagine orfane
     */
    public function detectOrphanPages(): int
    {
        $issuesCreated = 0;

        // Trova pagine senza link in entrata
        $sql = "
            SELECT p.id, p.url
            FROM sa_pages p
            WHERE p.project_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM sa_pages p2
                WHERE p2.project_id = p.project_id
                AND p2.id != p.id
                AND JSON_CONTAINS(p2.links_data, JSON_QUOTE(p.url), '$.internal[*].url')
            )
        ";

        // Query semplificata per ora
        $allPages = Database::fetchAll("SELECT id, url, links_data FROM sa_pages WHERE project_id = ?", [$this->projectId]);

        $linkedUrls = [];
        foreach ($allPages as $page) {
            $links = json_decode($page['links_data'] ?? '[]', true);
            foreach ($links['internal'] ?? [] as $link) {
                $linkedUrls[strtolower(rtrim($link['url'], '/'))] = true;
            }
        }

        foreach ($allPages as $page) {
            $normalizedUrl = strtolower(rtrim($page['url'], '/'));
            if (!isset($linkedUrls[$normalizedUrl])) {
                $this->issueModel->create([
                    'project_id' => $this->projectId,
                    'page_id' => $page['id'],
                    'category' => 'links',
                    'issue_type' => 'orphan_page',
                    'severity' => 'warning',
                    'title' => 'Pagina orfana',
                    'description' => 'Nessun link interno punta a questa pagina',
                    'affected_element' => $page['url'],
                    'recommendation' => 'Aggiungi link interni a questa pagina per migliorare la crawlability.',
                    'source' => 'crawler',
                ]);
                $issuesCreated++;
            }
        }

        return $issuesCreated;
    }

    /**
     * Rileva problemi di sicurezza
     */
    public function detectSecurityIssues(): int
    {
        $issuesCreated = 0;

        // Check HTTPS a livello progetto
        $config = Database::fetch("SELECT * FROM sa_site_config WHERE project_id = ?", [$this->projectId]);

        if ($config && !$config['is_https']) {
            $this->issueModel->create([
                'project_id' => $this->projectId,
                'page_id' => null,
                'category' => 'security',
                'issue_type' => 'not_https',
                'severity' => 'critical',
                'title' => 'Sito non sicuro (HTTP)',
                'description' => 'Il sito non utilizza HTTPS',
                'recommendation' => 'Migra il sito a HTTPS per sicurezza e ranking.',
                'source' => 'crawler',
            ]);
            $issuesCreated++;
        }

        return $issuesCreated;
    }

    /**
     * Crea oggetto issue da tipo predefinito
     */
    private function createIssue(string $type, ?string $element = null): array
    {
        $typeInfo = Issue::ISSUE_TYPES[$type] ?? null;

        if (!$typeInfo) {
            throw new \InvalidArgumentException("Unknown issue type: {$type}");
        }

        return [
            'type' => $type,
            'category' => $typeInfo['category'],
            'severity' => $typeInfo['severity'],
            'title' => $typeInfo['title'],
            'recommendation' => $typeInfo['recommendation'],
            'element' => $element,
        ];
    }

    /**
     * Esegui tutti i check a livello progetto
     */
    public function runProjectLevelChecks(): int
    {
        $total = 0;
        $total += $this->detectDuplicates();
        $total += $this->detectOrphanPages();
        $total += $this->detectSecurityIssues();
        return $total;
    }
}
